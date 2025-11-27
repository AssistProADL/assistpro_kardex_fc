<?php
// app/mailer_common.php
//
// Funciones comunes para el motor de correos AssistPro:
//  - Usa bootstrap.php (PDO, .env, logger).
//  - Calcula próximas ejecuciones de jobs.
//  - Renderiza plantillas {CAMPO}.
//  - Obtiene destinatarios desde catálogos.
//  - Obtiene configuración SMTP (DB + defaults .env).
//
// IMPORTANTE:
//  - Requiere que existan las tablas c_smtp_config, c_correo_plantilla,
//    t_correo_job, t_correo_queue y catálogos c_cliente / c_proveedores / usuarios.
//
// Puedes ajustar nombres de columnas/tablas en mailer_get_destinatarios().

use Monolog\Logger;

require_once __DIR__ . '/bootstrap.php';   // trae env(), db(), app_logger(), etc.

/**
 * Asegura que exista al menos una config SMTP en c_smtp_config.
 * Si la tabla está vacía, inserta un registro con los valores de .env.
 */
function mailer_ensure_default_smtp()
{
    $count = (int) db_val("SELECT COUNT(*) FROM c_smtp_config");
    if ($count > 0) {
        return;
    }

    $host     = env('SMTP_HOST', 'localhost');
    $port     = (int) env('SMTP_PORT', '25');
    $security = strtolower(env('SMTP_SECURITY', 'none'));
    if (!in_array($security, ['none', 'ssl', 'tls'], true)) {
        $security = 'none';
    }

    $user     = env('SMTP_USER', '');
    $pass     = env('SMTP_PASS', '');
    $fromMail = env('SMTP_FROM_EMAIL', $user ?: 'no-reply@localhost');
    $fromName = env('SMTP_FROM_NAME', 'AssistPro Notificaciones');

    $sql = "INSERT INTO c_smtp_config
            (nombre, host, puerto, seguridad, usuario, password, from_email, from_name, aliases_json, activo)
            VALUES
            (:nombre, :host, :puerto, :seguridad, :usuario, :password, :from_email, :from_name, NULL, 1)";

    db_exec($sql, [
        ':nombre'     => 'SMTP por defecto (.env)',
        ':host'       => $host,
        ':puerto'     => $port,
        ':seguridad'  => $security,
        ':usuario'    => $user,
        ':password'   => $pass,
        ':from_email' => $fromMail,
        ':from_name'  => $fromName,
    ]);

    app_logger()->info('SMTP por defecto creado en c_smtp_config desde .env');
}

/**
 * Calcula la próxima ejecución de un job en formato 'Y-m-d H:i:s'
 * o NULL si el job es ON_DEMAND.
 */
function mailer_calcular_proxima_ejecucion(array $job): ?string
{
    $tipo = $job['tipo_frecuencia'];
    $hora = !empty($job['hora_envio']) ? $job['hora_envio'] : '09:00:00';
    $now  = new DateTime();

    if ($tipo === 'ON_DEMAND') {
        return null;
    }

    if ($tipo === 'DIA') {
        $next = new DateTime(date('Y-m-d') . ' ' . $hora);
        if ($next <= $now) {
            $next->modify('+1 day');
        }
        return $next->format('Y-m-d H:i:s');
    }

    if ($tipo === 'HORA') {
        $hours = (int) $job['intervalo_horas'];
        if ($hours <= 0) {
            $hours = 1;
        }
        $next = clone $now;
        $next->modify('+' . $hours . ' hour');
        return $next->format('Y-m-d H:i:s');
    }

    if ($tipo === 'SEMANA') {
        // 1 = lunes ... 7 = domingo
        $diaSemana = (int) $job['dia_semana'];
        if ($diaSemana < 1 || $diaSemana > 7) {
            $diaSemana = 1;
        }

        $next = new DateTime(date('Y-m-d') . ' ' . $hora);
        $todayDow = (int) $now->format('N'); // 1..7

        $daysToAdd = $diaSemana - $todayDow;
        if ($daysToAdd < 0 || ($daysToAdd === 0 && $next <= $now)) {
            $daysToAdd += 7;
        }
        if ($daysToAdd > 0) {
            $next->modify('+' . $daysToAdd . ' day');
        }

        return $next->format('Y-m-d H:i:s');
    }

    if ($tipo === 'MES') {
        $diaMes = (int) $job['dia_mes'];
        if ($diaMes < 1)  $diaMes = 1;
        if ($diaMes > 31) $diaMes = 31;

        $year  = (int) $now->format('Y');
        $month = (int) $now->format('m');

        $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
        if ($diaMes > $daysInMonth) {
            $diaMes = $daysInMonth;
        }

        $next = new DateTime("$year-$month-$diaMes $hora");
        if ($next <= $now) {
            // siguiente mes
            $next->modify('first day of next month');
            $year  = (int) $next->format('Y');
            $month = (int) $next->format('m');
            $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
            if ($diaMes > $daysInMonth) {
                $diaMes = $daysInMonth;
            }
            $next = new DateTime("$year-$month-$diaMes $hora");
        }

        return $next->format('Y-m-d H:i:s');
    }

    return null;
}

/**
 * Renderiza una plantilla reemplazando {CAMPO} con valores del row.
 * Es case-insensitive: {nombre}, {NOMBRE}, {Nombre} se buscan igual.
 */
function mailer_render_template(?string $tpl, array $row): string
{
    if ($tpl === null || $tpl === '') {
        return '';
    }

    $map = [];
    foreach ($row as $k => $v) {
        $map[strtolower($k)] = $v;
    }

    return preg_replace_callback('~\{([A-Z0-9_]+)\}~i', function ($m) use ($map) {
        $key = strtolower($m[1]);
        return array_key_exists($key, $map) ? (string) $map[$key] : '';
    }, $tpl);
}

/**
 * Obtiene los destinatarios en función de tipo_destino del job.
 * AJUSTA las tablas/campos según tu estructura real.
 */
function mailer_get_destinatarios(array $job): array
{
    $tipo    = $job['tipo_destino']; // CLIENTE / PROVEEDOR / USUARIO / LIBRE
    $filtro  = [];

    if (!empty($job['filtro_json'])) {
        $tmp = json_decode($job['filtro_json'], true);
        if (is_array($tmp)) {
            $filtro = $tmp;
        }
    }

    $where  = [];
    $params = [];

    if (!empty($filtro['empresa_id'])) {
        $where[]               = 'empresa_id = :empresa_id';
        $params[':empresa_id'] = (int) $filtro['empresa_id'];
    }
    if (!empty($filtro['solo_activos'])) {
        $where[] = 'activo = 1';
    }

    $destinatarios = [];

    if ($tipo === 'CLIENTE') {
        // AJUSTA a tu c_cliente real (campos usados de ejemplo)
        $sql = "SELECT 
                    id              AS destino_id,
                    RazonSocial     AS nombre,
                    Correo          AS email_to
                FROM c_cliente
                WHERE Correo IS NOT NULL
                  AND Correo <> ''";

        if ($where) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $destinatarios = db_all($sql, $params);

    } elseif ($tipo === 'PROVEEDOR') {
        // AJUSTA a tu c_proveedores real
        $sql = "SELECT
                    ID_Proveedor    AS destino_id,
                    Nombre          AS nombre,
                    Correo          AS email_to
                FROM c_proveedores
                WHERE Correo IS NOT NULL
                  AND Correo <> ''";

        if ($where) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $destinatarios = db_all($sql, $params);

    } elseif ($tipo === 'USUARIO') {
        // AJUSTA a tu tabla usuarios real
        $sql = "SELECT
                    id          AS destino_id,
                    nombre      AS nombre,
                    email       AS email_to
                FROM usuarios
                WHERE email IS NOT NULL
                  AND email <> ''";

        if ($where) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $destinatarios = db_all($sql, $params);

    } else {
        // LIBRE: aquí podrías usar otra vista/tabla específica
        $destinatarios = [];
    }

    return $destinatarios;
}

/**
 * Obtiene la configuración SMTP desde c_smtp_config y aplica alias si existe.
 * Si no encuentra config, intenta crear una por defecto desde .env.
 */
function mailer_get_smtp_config(int $smtp_config_id = 1, ?string $alias_smtp = null): ?array
{
    mailer_ensure_default_smtp();

    $cfg = db_one(
        "SELECT * FROM c_smtp_config WHERE id = :id AND activo = 1",
        [':id' => $smtp_config_id]
    );

    if (!$cfg) {
        return null;
    }

    // Aplicar alias
    if ($alias_smtp && !empty($cfg['aliases_json'])) {
        $aliases = json_decode($cfg['aliases_json'], true);
        if (is_array($aliases)) {
            foreach ($aliases as $a) {
                if (!empty($a['alias']) && $a['alias'] === $alias_smtp) {
                    if (!empty($a['email'])) {
                        $cfg['from_email'] = $a['email'];
                    }
                    if (!empty($a['name'])) {
                        $cfg['from_name'] = $a['name'];
                    }
                    break;
                }
            }
        }
    }

    // Fallback por seguridad: si algo está vacío, tomar de .env
    if (empty($cfg['from_email'])) {
        $cfg['from_email'] = env('SMTP_FROM_EMAIL', env('SMTP_USER', 'no-reply@localhost'));
    }
    if (empty($cfg['from_name'])) {
        $cfg['from_name'] = env('SMTP_FROM_NAME', 'AssistPro Notificaciones');
    }
    if (empty($cfg['host'])) {
        $cfg['host'] = env('SMTP_HOST', 'localhost');
    }
    if (empty($cfg['puerto'])) {
        $cfg['puerto'] = (int) env('SMTP_PORT', '25');
    }
    if (empty($cfg['seguridad'])) {
        $cfg['seguridad'] = env('SMTP_SECURITY', 'none');
    }
    if (empty($cfg['usuario'])) {
        $cfg['usuario'] = env('SMTP_USER', '');
    }
    if (empty($cfg['password'])) {
        $cfg['password'] = env('SMTP_PASS', '');
    }

    return $cfg;
}
