<?php
// public/api/impresoras.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

if (function_exists('db')) {
    $pdo = db();
} else {
    global $pdo;
    if (!$pdo instanceof PDO) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Sin conexión a BD']);
        exit;
    }
}

$action = $_REQUEST['action'] ?? 'list';

try {
    switch ($action) {
        case 'get':
            api_get($pdo);
            break;
        case 'save':
            api_save($pdo);
            break;
        case 'test':
            api_test($pdo);
            break;
        case 'toggle_active':
            api_toggle_active($pdo);
            break;
        case 'list':
        default:
            api_list($pdo);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * LISTAR IMPRESORAS
 */
function api_list(PDO $pdo)
{
    $id_almacen_raw = $_GET['id_almacen'] ?? '';
    $activo         = $_GET['activo'] ?? '';

    $sql = "SELECT 
                i.id,
                i.id_almacen,
                a.clave   AS almacen_clave,
                a.nombre  AS almacen_nombre,
                i.IP,
                i.TIPO_IMPRESORA,
                i.NOMBRE,
                i.Marca,
                i.Modelo,
                i.Densidad_Imp,
                i.TIPO_CONEXION,
                i.PUERTO,
                i.TiempoEspera,
                i.Activo
            FROM s_impresoras i
            JOIN c_almacenp a ON a.id = i.id_almacen
            WHERE 1=1";
    $params = [];

    // Filtro por almacén (acepta ID o clave WHx)
    if ($id_almacen_raw !== '') {
        $id_almacen = (int)$id_almacen_raw;
        if ($id_almacen <= 0) {
            $stmtA = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = :clave LIMIT 1");
            $stmtA->execute([':clave' => $id_almacen_raw]);
            $rowA = $stmtA->fetch(PDO::FETCH_ASSOC);
            $id_almacen = $rowA ? (int)$rowA['id'] : 0;
        }
        if ($id_almacen > 0) {
            $sql .= " AND i.id_almacen = :id_almacen";
            $params[':id_almacen'] = $id_almacen;
        }
    }

    // Filtro Activo
    if ($activo !== '' && $activo !== null) {
        $sql .= " AND i.Activo = :activo";
        $params[':activo'] = (int)$activo;
    }

    $sql .= " ORDER BY a.clave, i.NOMBRE";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
}

/**
 * OBTENER UNA IMPRESORA
 */
function api_get(PDO $pdo)
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $sql = "SELECT 
                i.id,
                i.id_almacen,
                a.clave   AS almacen_clave,
                a.nombre  AS almacen_nombre,
                i.IP,
                i.TIPO_IMPRESORA,
                i.NOMBRE,
                i.Marca,
                i.Modelo,
                i.Densidad_Imp,
                i.TIPO_CONEXION,
                i.PUERTO,
                i.TiempoEspera,
                i.Activo
            FROM s_impresoras i
            JOIN c_almacenp a ON a.id = i.id_almacen
            WHERE i.id = :id
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Registro no encontrado'], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
}

/**
 * GUARDAR / ACTUALIZAR IMPRESORA
 */
function api_save(PDO $pdo)
{
    $data = $_POST;

    $id = isset($data['id']) ? (int)$data['id'] : 0;

    // id_almacen puede venir como ID numérico o como clave WHx
    $id_almacen_raw = trim($data['id_almacen'] ?? '');
    $id_almacen     = (int)$id_almacen_raw;

    if ($id_almacen <= 0 && $id_almacen_raw !== '') {
        $stmtAlm = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = :clave LIMIT 1");
        $stmtAlm->execute([':clave' => $id_almacen_raw]);
        $rowAlm = $stmtAlm->fetch(PDO::FETCH_ASSOC);
        if ($rowAlm) {
            $id_almacen = (int)$rowAlm['id'];
        }
    }

    $IP             = trim($data['IP'] ?? '');
    $TIPO_IMPRESORA = $data['TIPO_IMPRESORA'] ?? 'ZPL';
    $NOMBRE         = trim($data['NOMBRE'] ?? '');
    $Marca          = trim($data['Marca'] ?? '');
    $Modelo         = trim($data['Modelo'] ?? '');
    $Densidad_Imp   = (int)($data['Densidad_Imp'] ?? 203);
    $TIPO_CONEXION  = $data['TIPO_CONEXION'] ?? 'USB';
    $PUERTO         = (int)($data['PUERTO'] ?? 0);
    $TiempoEspera   = (int)($data['TiempoEspera'] ?? 0);
    $Activo         = (int)($data['Activo'] ?? 1);

    if ($id_almacen <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Almacén requerido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($id > 0) {
        $sql = "UPDATE s_impresoras SET
                    id_almacen     = :id_almacen,
                    IP             = :IP,
                    TIPO_IMPRESORA = :TIPO_IMPRESORA,
                    NOMBRE         = :NOMBRE,
                    Marca          = :Marca,
                    Modelo         = :Modelo,
                    Densidad_Imp   = :Densidad_Imp,
                    TIPO_CONEXION  = :TIPO_CONEXION,
                    PUERTO         = :PUERTO,
                    TiempoEspera   = :TiempoEspera,
                    Activo         = :Activo
                WHERE id = :id";
        $params = [
            ':id_almacen'     => $id_almacen,
            ':IP'             => $IP,
            ':TIPO_IMPRESORA' => $TIPO_IMPRESORA,
            ':NOMBRE'         => $NOMBRE,
            ':Marca'          => $Marca,
            ':Modelo'         => $Modelo,
            ':Densidad_Imp'   => $Densidad_Imp,
            ':TIPO_CONEXION'  => $TIPO_CONEXION,
            ':PUERTO'         => $PUERTO,
            ':TiempoEspera'   => $TiempoEspera,
            ':Activo'         => $Activo,
            ':id'             => $id,
        ];
    } else {
        $sql = "INSERT INTO s_impresoras
                    (id_almacen, IP, TIPO_IMPRESORA, NOMBRE, Marca, Modelo,
                     Densidad_Imp, TIPO_CONEXION, PUERTO, TiempoEspera, Activo)
                VALUES
                    (:id_almacen, :IP, :TIPO_IMPRESORA, :NOMBRE, :Marca, :Modelo,
                     :Densidad_Imp, :TIPO_CONEXION, :PUERTO, :TiempoEspera, :Activo)";
        $params = [
            ':id_almacen'     => $id_almacen,
            ':IP'             => $IP,
            ':TIPO_IMPRESORA' => $TIPO_IMPRESORA,
            ':NOMBRE'         => $NOMBRE,
            ':Marca'          => $Marca,
            ':Modelo'         => $Modelo,
            ':Densidad_Imp'   => $Densidad_Imp,
            ':TIPO_CONEXION'  => $TIPO_CONEXION,
            ':PUERTO'         => $PUERTO,
            ':TiempoEspera'   => $TiempoEspera,
            ':Activo'         => $Activo,
        ];
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);

    if ($id <= 0) {
        $id = (int)$pdo->lastInsertId();
    }

    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
}

/**
 * PROBAR IMPRESIÓN
 */
function api_test(PDO $pdo)
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Aquí iría el envío real de ZPL; por ahora sólo simulación
    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Prueba de impresión simulada para impresora ID ' . $id
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * ACTIVAR / DESACTIVAR (Eliminar / Recuperar)
 */
function api_toggle_active(PDO $pdo)
{
    $id     = (int)($_POST['id'] ?? 0);
    $activo = (int)($_POST['activo'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $st = $pdo->prepare("UPDATE s_impresoras SET Activo = :a WHERE id = :id");
    $st->execute([':a' => $activo, ':id' => $id]);

    if (!$imp) {
        echo json_encode(['ok' => false, 'error' => 'Impresora no encontrada'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $ip = trim($imp['IP'] ?? '');
    $puerto = (int) ($imp['PUERTO'] ?? 0);
    $tipo = $imp['TIPO_IMPRESORA'] ?? 'ZPL';
    $nombre = $imp['NOMBRE'] ?? '';
    $modelo = $imp['Modelo'] ?? '';

    if ($ip === '') {
        echo json_encode(['ok' => false, 'error' => 'La impresora no tiene IP configurada'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if ($puerto <= 0) {
        $puerto = 9100; // default Zebra
    }

    if ($tipo !== 'ZPL') {
        echo json_encode(['ok' => false, 'error' => 'Solo se soporta prueba ZPL por ahora (impresora configurada como ' . $tipo . ')'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Etiqueta ZPL de prueba
    $zpl = "^XA";
    $zpl .= "^CF0,40,40";
    $zpl .= "^FO50,50^FDTEST IMPRESORA ASSISTPRO^FS";
    $zpl .= "^CF0,30,30";
    $zpl .= "^FO50,110^FDNombre: " . substr($nombre, 0, 20) . "^FS";
    $zpl .= "^FO50,150^FDModelo: " . substr($modelo, 0, 20) . "^FS";
    $zpl .= "^FO50,200^FDFecha: " . date('d/m/Y H:i') . "^FS";
    $zpl .= "^XZ";

    $errno = 0;
    $errstr = '';
    $timeout = 5;

    $fp = @fsockopen($ip, $puerto, $errno, $errstr, $timeout);
    if (!$fp) {
        echo json_encode([
            'ok' => false,
            'error' => "No se pudo conectar a {$ip}:{$puerto} ({$errno} - {$errstr})"
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    stream_set_timeout($fp, $timeout);
    $bytes = fwrite($fp, $zpl);
    fclose($fp);

    if ($bytes === false || $bytes <= 0) {
        echo json_encode([
            'ok' => false,
            'error' => 'No se pudo enviar datos a la impresora'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Etiqueta de prueba enviada correctamente'
    ], JSON_UNESCAPED_UNICODE);
}
