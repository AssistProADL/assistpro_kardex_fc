<?php
// public/config/correo_jobs.php
session_start();

// Menú global (ajusta si tu _menu_global.php vive en otra ruta)
require_once __DIR__ . '/../bi/_menu_global.php';

// Motor de BD + mailer
require_once __DIR__ . '/../../app/mailer_common.php';

$logger = app_logger();

// --- Catálogos para selects ---
$smtps      = db_all("SELECT id, nombre FROM c_smtp_config ORDER BY id");
$plantillas = db_all("SELECT id, codigo, descripcion FROM c_correo_plantilla WHERE activo = 1 ORDER BY codigo");

// --- Guardar job (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre         = trim($_POST['nombre'] ?? '');
    $tipo_destino   = $_POST['tipo_destino'] ?? 'CLIENTE';
    $filtro_json    = trim($_POST['filtro_json'] ?? '');
    $plantilla_id   = (int)($_POST['plantilla_id'] ?? 0);
    $smtp_config_id = (int)($_POST['smtp_config_id'] ?? 1);
    $alias_smtp     = trim($_POST['alias_smtp'] ?? '');
    $tipo_freq      = $_POST['tipo_frecuencia'] ?? 'ON_DEMAND';
    $hora_envio     = $_POST['hora_envio'] ?: null;
    $dia_semana     = $_POST['dia_semana'] ?: null;
    $dia_mes        = $_POST['dia_mes'] ?: null;
    $int_horas      = $_POST['intervalo_horas'] ?: null;
    $activo         = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '' || $plantilla_id <= 0 || $smtp_config_id <= 0) {
        $error = 'Nombre, plantilla y SMTP son obligatorios.';
    } else {
        if ($filtro_json === '') {
            $filtro_json = null;
        }

        // Preparamos arreglo para calcular próxima ejecución
        $jobForNext = [
            'tipo_frecuencia' => $tipo_freq,
            'hora_envio'      => $hora_envio,
            'dia_semana'      => $dia_semana,
            'dia_mes'         => $dia_mes,
            'intervalo_horas' => $int_horas,
        ];
        $next = mailer_calcular_proxima_ejecucion($jobForNext);

        $params = [
            ':nombre'         => $nombre,
            ':tipo_destino'   => $tipo_destino,
            ':filtro_json'    => $filtro_json,
            ':plantilla_id'   => $plantilla_id,
            ':smtp_config_id' => $smtp_config_id,
            ':alias_smtp'     => $alias_smtp ?: null,
            ':tipo_freq'      => $tipo_freq,
            ':hora_envio'     => $hora_envio,
            ':dia_semana'     => $dia_semana ? (int)$dia_semana : null,
            ':dia_mes'        => $dia_mes ? (int)$dia_mes : null,
            ':int_horas'      => $int_horas ? (int)$int_horas : null,
            ':proxima'        => $next,
            ':activo'         => $activo,
        ];

        if ($id > 0) {
            $sql = "UPDATE t_correo_job
                    SET nombre           = :nombre,
                        tipo_destino     = :tipo_destino,
                        filtro_json      = :filtro_json,
                        plantilla_id     = :plantilla_id,
                        smtp_config_id   = :smtp_config_id,
                        alias_smtp       = :alias_smtp,
                        tipo_frecuencia  = :tipo_freq,
                        hora_envio       = :hora_envio,
                        dia_semana       = :dia_semana,
                        dia_mes          = :dia_mes,
                        intervalo_horas  = :int_horas,
                        proxima_ejecucion= :proxima,
                        activo           = :activo
                    WHERE id = :id";
            $params[':id'] = $id;
            db_exec($sql, $params);
            $logger->info('Job correo actualizado', ['id' => $id, 'nombre' => $nombre]);
        } else {
            $sql = "INSERT INTO t_correo_job
                    (nombre, tipo_destino, filtro_json,
                     plantilla_id, smtp_config_id, alias_smtp,
                     tipo_frecuencia, hora_envio, dia_semana,
                     dia_mes, intervalo_horas, proxima_ejecucion,
                     ultima_ejecucion, activo, fecha_creacion)
                    VALUES
                    (:nombre, :tipo_destino, :filtro_json,
                     :plantilla_id, :smtp_config_id, :alias_smtp,
                     :tipo_freq, :hora_envio, :dia_semana,
                     :dia_mes, :int_horas, :proxima,
                     NULL, :activo, NOW())";
            db_exec($sql, $params);
            $logger->info('Job correo creado', ['nombre' => $nombre]);
        }

        header('Location: correo_jobs.php?ok=1');
        exit;
    }
}

// --- Cargar job para edición ---
$editId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editRow = null;
if ($editId > 0) {
    $editRow = db_one("SELECT * FROM t_correo_job WHERE id = :id", [':id' => $editId]);
}

// --- Listado de jobs ---
$jobs = db_all("SELECT j.*, p.codigo AS plantilla_codigo, s.nombre AS smtp_nombre
                FROM t_correo_job j
                LEFT JOIN c_correo_plantilla p ON p.id = j.plantilla_id
                LEFT JOIN c_smtp_config s      ON s.id = j.smtp_config_id
                ORDER BY j.id DESC");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Automatización de Correos - AssistPro</title>
    <link rel="stylesheet" href="/assistpro_kardex_fc/public/assets/bootstrap.min.css">
    <style>
        body { font-size: 12px; }
        .container-main { padding: 15px; }
        textarea { font-size: 11px; }
        select, input { font-size: 12px !important; }
    </style>
</head>
<body>
<div class="container-main">
    <h4>Automatización de Correos (Jobs)</h4>
    <hr>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (isset($_GET['ok'])): ?>
        <div class="alert alert-success">Job guardado correctamente.</div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulario -->
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">
                    <?= $editRow ? 'Editar Job de Correo' : 'Nuevo Job de Correo' ?>
                </div>
                <div class="card-body">
                    <form method="post" action="correo_jobs.php">
                        <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">

                        <div class="mb-2">
                            <label class="form-label">Nombre del Job</label>
                            <input type="text" name="nombre" class="form-control form-control-sm"
                                   required
                                   placeholder="Ej: Recordatorio semanal de cobranza"
                                   value="<?= htmlspecialchars($editRow['nombre'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Tipo destino</label>
                            <?php $td = $editRow['tipo_destino'] ?? 'CLIENTE'; ?>
                            <select name="tipo_destino" class="form-select form-select-sm">
                                <option value="CLIENTE"   <?= $td === 'CLIENTE' ? 'selected' : '' ?>>Cliente</option>
                                <option value="PROVEEDOR" <?= $td === 'PROVEEDOR' ? 'selected' : '' ?>>Proveedor</option>
                                <option value="USUARIO"   <?= $td === 'USUARIO' ? 'selected' : '' ?>>Usuario</option>
                                <option value="LIBRE"     <?= $td === 'LIBRE' ? 'selected' : '' ?>>Libre / Especial</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Filtro JSON (opcional)</label>
                            <textarea name="filtro_json" rows="3" class="form-control form-control-sm"
                                      placeholder='{"empresa_id":1,"solo_activos":1}'><?= htmlspecialchars($editRow['filtro_json'] ?? '') ?></textarea>
                            <small class="text-muted">Se pasa tal cual al motor (empresa_id, solo_activos, etc.).</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Plantilla</label>
                            <select name="plantilla_id" class="form-select form-select-sm" required>
                                <option value="">-- Selecciona plantilla --</option>
                                <?php foreach ($plantillas as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"
                                        <?= isset($editRow['plantilla_id']) && (int)$editRow['plantilla_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['codigo'] . ' - ' . $p['descripcion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Configuración SMTP</label>
                            <select name="smtp_config_id" class="form-select form-select-sm" required>
                                <?php foreach ($smtps as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>"
                                        <?= isset($editRow['smtp_config_id']) && (int)$editRow['smtp_config_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                                        <?= (int)$s['id'] . ' - ' . htmlspecialchars($s['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Alias SMTP (opcional)</label>
                            <input type="text" name="alias_smtp" class="form-control form-control-sm"
                                   placeholder="Ej: facturacion"
                                   value="<?= htmlspecialchars($editRow['alias_smtp'] ?? '') ?>">
                            <small class="text-muted">Debe existir en aliases_json de la configuración SMTP.</small>
                        </div>

                        <hr>
                        <p><strong>Frecuencia de ejecución</strong></p>

                        <?php $tf = $editRow['tipo_frecuencia'] ?? 'ON_DEMAND'; ?>
                        <div class="mb-2">
                            <label class="form-label">Tipo de frecuencia</label>
                            <select name="tipo_frecuencia" class="form-select form-select-sm">
                                <option value="ON_DEMAND" <?= $tf === 'ON_DEMAND' ? 'selected' : '' ?>>Bajo demanda (sin agenda)</option>
                                <option value="HORA"      <?= $tf === 'HORA'      ? 'selected' : '' ?>>Cada X horas</option>
                                <option value="DIA"       <?= $tf === 'DIA'       ? 'selected' : '' ?>>Diaria</option>
                                <option value="SEMANA"    <?= $tf === 'SEMANA'    ? 'selected' : '' ?>>Semanal</option>
                                <option value="MES"       <?= $tf === 'MES'       ? 'selected' : '' ?>>Mensual</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Hora envío (HH:MM)</label>
                            <input type="time" name="hora_envio" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($editRow['hora_envio'] ?? '08:00:00') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Día semana (1=Lun ... 7=Dom, sólo SEMANA)</label>
                            <input type="number" name="dia_semana" min="1" max="7"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($editRow['dia_semana'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Día mes (1..31, sólo MES)</label>
                            <input type="number" name="dia_mes" min="1" max="31"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($editRow['dia_mes'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Intervalo horas (sólo HORA)</label>
                            <input type="number" name="intervalo_horas" min="1" max="168"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($editRow['intervalo_horas'] ?? '') ?>">
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" id="chkActivoJob" name="activo" class="form-check-input"
                                   value="1" <?= (isset($editRow['activo']) ? (int)$editRow['activo'] : 1) ? 'checked' : '' ?>>
                            <label for="chkActivoJob" class="form-check-label">Activo</label>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                            <a href="correo_jobs.php" class="btn btn-secondary btn-sm">Nuevo</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de jobs -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Jobs configurados</div>
                <div class="card-body" style="max-height: 480px; overflow:auto;">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Destino</th>
                            <th>Plantilla</th>
                            <th>SMTP</th>
                            <th>Frecuencia</th>
                            <th>Próxima</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($jobs as $j): ?>
                            <tr>
                                <td><?= (int)$j['id'] ?></td>
                                <td><?= htmlspecialchars($j['nombre']) ?></td>
                                <td><?= htmlspecialchars($j['tipo_destino']) ?></td>
                                <td><?= htmlspecialchars($j['plantilla_codigo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($j['smtp_nombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($j['tipo_frecuencia']) ?></td>
                                <td><?= htmlspecialchars($j['proxima_ejecucion'] ?? '') ?></td>
                                <td><?= (int)$j['activo'] ? 'Sí' : 'No' ?></td>
                                <td>
                                    <a href="correo_jobs.php?id=<?= (int)$j['id'] ?>"
                                       class="btn btn-sm btn-outline-primary">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$jobs): ?>
                            <tr><td colspan="9" class="text-center">No hay jobs configurados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <small class="text-muted">
                        Recuerda: el scheduler (`mailer_scheduler.php`) es quien genera la cola en base a estos jobs.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
