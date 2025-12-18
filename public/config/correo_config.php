<?php
// public/config/correo_config.php
//@session_start();

// Bootstrap (PDO, .env, logger, helpers, mailer)
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/mailer_common.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

 
$error = null;
$mensajeOk = null;
$mensajeErr = null;
$mensajeDebug = null;

/* ============================================================
   ACCIÓN: PROBAR CONEXIÓN SMTP (ANTES DE MENU)
   ============================================================ */
if (isset($_GET['test_id'])) {
    $testId = (int) $_GET['test_id'];
    $cfg = db_one("SELECT * FROM c_smtp_config WHERE id = :id", [':id' => $testId]);

    if (!$cfg) {
        $mensajeErr = "No existe la configuración SMTP con ID {$testId}.";
    } else {
        try {
            $smtpOutput = "";

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->Port = (int) $cfg['puerto'];
            $mail->SMTPAuth = true;
            $mail->Username = $cfg['usuario'];
            $mail->Password = $cfg['password'];

            if ($cfg['seguridad'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($cfg['seguridad'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
            }

            // Captura del SMTP DEBUG
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str, $level) use (&$smtpOutput) {
                $smtpOutput .= "$str\n";
            };

            // TEST sin enviar correo
            $mail->smtpConnect();

            $mensajeOk = "Conexión SMTP EXITOSA 📡<br>Host: <b>{$cfg['host']}</b> Puerto: <b>{$cfg['puerto']}</b>";
            $logger->info("Prueba SMTP exitosa", ['id' => $testId]);

            $mensajeDebug = "<pre style='background:#fafafa;border:1px solid #ccc;padding:8px;max-height:200px;overflow:auto;font-size:11px;'>"
                . htmlspecialchars($smtpOutput)
                . "</pre>";

        } catch (Exception $e) {
            $mensajeErr = "Error conectando al SMTP:<br><b>" . $e->getMessage() . "</b>";
            $logger->error("Error prueba SMTP", ['id' => $testId, 'error' => $e->getMessage()]);
        }
    }
}

/* ============================================================
   ACCIONES POST (GUARDAR / ELIMINAR) - ANTES DEL MENU
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';

    /* ---------- ELIMINAR ---------- */
    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            db_exec("DELETE FROM c_smtp_config WHERE id = :id", [':id' => $id]);
            $logger->info("SMTP eliminado", ['id' => $id]);
            header("Location: correo_config.php?ok=1");
            exit;
        } else {
            $error = "ID inválido para eliminar.";
        }
    }

    /* ---------- GUARDAR (INSERT / UPDATE) ---------- */
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $host = trim($_POST['host'] ?? '');
    $puerto = (int) ($_POST['puerto'] ?? 0);
    $seguridad = $_POST['seguridad'] ?? 'none';
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $from_email = trim($_POST['from_email'] ?? '');
    $from_name = trim($_POST['from_name'] ?? '');
    $aliases = trim($_POST['aliases_json'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (!in_array($seguridad, ['none', 'ssl', 'tls'], true)) {
        $seguridad = 'none';
    }

    if ($nombre === '' || $host === '' || $puerto <= 0) {
        $error = "Nombre, host y puerto son obligatorios.";
    } else {
        if ($aliases === '')
            $aliases = null;

        if ($id > 0) {
            // UPDATE
            $sql = "UPDATE c_smtp_config
                    SET nombre = :nombre,
                        host = :host,
                        puerto = :puerto,
                        seguridad = :seguridad,
                        usuario = :usuario,
                        password = :password,
                        from_email = :from_email,
                        from_name = :from_name,
                        aliases_json = :aliases,
                        activo = :activo
                    WHERE id = :id";
            db_exec($sql, [
                ':nombre' => $nombre,
                ':host' => $host,
                ':puerto' => $puerto,
                ':seguridad' => $seguridad,
                ':usuario' => $usuario,
                ':password' => $password,
                ':from_email' => $from_email,
                ':from_name' => $from_name,
                ':aliases' => $aliases,
                ':activo' => $activo,
                ':id' => $id
            ]);

        } else {
            // INSERT
            $sql = "INSERT INTO c_smtp_config
                    (nombre, host, puerto, seguridad, usuario, password,
                     from_email, from_name, aliases_json, activo)
                    VALUES
                    (:nombre, :host, :puerto, :seguridad, :usuario, :password,
                     :from_email, :from_name, :aliases, :activo)";
            db_exec($sql, [
                ':nombre' => $nombre,
                ':host' => $host,
                ':puerto' => $puerto,
                ':seguridad' => $seguridad,
                ':usuario' => $usuario,
                ':password' => $password,
                ':from_email' => $from_email,
                ':from_name' => $from_name,
                ':aliases' => $aliases,
                ':activo' => $activo
            ]);
        }

        header("Location: correo_config.php?ok=1");
        exit;
    }
}

/* ============================================================
   INCLUIR MENU GLOBAL AHORA (DESPUÉS DE POST)
   ============================================================ */
require_once __DIR__ . '/../bi/_menu_global.php';

/* ============================================================
   CONSULTAS PARA FORMULARIO Y TABLA
   ============================================================ */
$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editRow = $editId > 0 ? db_one("SELECT * FROM c_smtp_config WHERE id = :id", [':id' => $editId]) : null;

$rows = db_all("SELECT * FROM c_smtp_config ORDER BY id ASC");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Configuración SMTP - AssistPro</title>
    <link rel="stylesheet" href="/assistpro_kardex_fc/public/assets/bootstrap.min.css">
    <style>
        body {
            font-size: 12px;
        }

        .container-main {
            padding: 15px;
        }

        table td,
        table th {
            vertical-align: middle !important;
        }

        textarea {
            font-size: 11px;
        }
    </style>
    <script>
        function confirmarEliminar(id) {
            if (confirm("¿Eliminar esta configuración SMTP?")) {
                document.getElementById("frmDel" + id).submit();
            }
        }
        function probarConexion(id) {
            if (confirm("¿Probar conexión SMTP?")) {
                window.location = "correo_config.php?test_id=" + id;
            }
        }
    </script>
</head>

<body>
    <div class="container-main">

        <h4>Configuración de Correo (SMTP)</h4>
        <hr>

        <?php if ($mensajeOk): ?>
            <div class="alert alert-success"><?= $mensajeOk ?></div>
        <?php endif; ?>

        <?php if ($mensajeErr): ?>
            <div class="alert alert-danger"><?= $mensajeErr ?></div>
        <?php endif; ?>

        <?= $mensajeDebug ?? '' ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-success">Operación realizada correctamente.</div>
        <?php endif; ?>

        <div class="row">

            <!-- FORMULARIO -->
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header"><?= $editRow ? 'Editar Configuración SMTP' : 'Nueva Configuración SMTP' ?>
                    </div>
                    <div class="card-body">

                        <form method="post" action="correo_config.php">
                            <input type="hidden" name="accion" value="guardar">
                            <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">

                            <div class="mb-2">
                                <label>Nombre</label>
                                <input class="form-control form-control-sm" name="nombre" required
                                    value="<?= htmlspecialchars($editRow['nombre'] ?? '') ?>">
                            </div>

                            <div class="mb-2">
                                <label>Host SMTP</label>
                                <input class="form-control form-control-sm" name="host" required
                                    value="<?= htmlspecialchars($editRow['host'] ?? env('SMTP_HOST', '')) ?>">
                            </div>

                            <div class="mb-2">
                                <label>Puerto</label>
                                <input type="number" class="form-control form-control-sm" required name="puerto"
                                    value="<?= htmlspecialchars($editRow['puerto'] ?? env('SMTP_PORT', '587')) ?>">
                            </div>

                            <div class="mb-2">
                                <label>Seguridad</label>
                                <?php $seg = $editRow['seguridad'] ?? env('SMTP_SECURITY', 'tls'); ?>
                                <select class="form-select form-select-sm" name="seguridad">
                                    <option value="none" <?= $seg === 'none' ? 'selected' : '' ?>>Sin cifrado</option>
                                    <option value="ssl" <?= $seg === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="tls" <?= $seg === 'tls' ? 'selected' : '' ?>>TLS</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label>Usuario SMTP</label>
                                <input class="form-control form-control-sm" name="usuario"
                                    value="<?= htmlspecialchars($editRow['usuario'] ?? env('SMTP_USER', '')) ?>">
                            </div>

                            <div class="mb-2">
                                <label>Password SMTP</label>
                                <input type="password" class="form-control form-control-sm" name="password"
                                    value="<?= htmlspecialchars($editRow['password'] ?? env('SMTP_PASS', '')) ?>">
                            </div>

                            <div class="mb-2">
                                <label>From email</label>
                                <input type="email" class="form-control form-control-sm" name="from_email"
                                    value="<?= htmlspecialchars($editRow['from_email'] ?? env('SMTP_FROM_EMAIL', '')) ?>">
                            </div>

                            <div class="mb-2">
                                <label>From name</label>
                                <input class="form-control form-control-sm" name="from_name"
                                    value="<?= htmlspecialchars($editRow['from_name'] ?? env('SMTP_FROM_NAME', 'AssistPro Notificaciones')) ?>">
                            </div>

                            <div class="mb-2">
                                <label>Aliases JSON</label>
                                <textarea name="aliases_json" class="form-control form-control-sm" rows="3"
                                    placeholder='[{"alias":"facturacion","email":"facturacion@empresa.com","name":"Facturación"}]'><?= htmlspecialchars($editRow['aliases_json'] ?? '') ?></textarea>
                            </div>

                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="chkActivo" name="activo" value="1"
                                    <?= (isset($editRow['activo']) ? $editRow['activo'] : 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chkActivo">Activo</label>
                            </div>

                            <div class="mt-2">
                                <button class="btn btn-primary btn-sm">Guardar</button>
                                <a href="correo_config.php" class="btn btn-secondary btn-sm">Nuevo</a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>


            <!-- LISTADO -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">Configuraciones registradas</div>
                    <div class="card-body" style="max-height:420px; overflow:auto;">

                        <table class="table table-sm table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Host</th>
                                    <th>Puerto</th>
                                    <th>Seguridad</th>
                                    <th>From</th>
                                    <th>Activo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= (int) $r['id'] ?></td>
                                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                                        <td><?= htmlspecialchars($r['host']) ?></td>
                                        <td><?= htmlspecialchars($r['puerto']) ?></td>
                                        <td><?= htmlspecialchars($r['seguridad']) ?></td>
                                        <td><?= htmlspecialchars($r['from_email']) ?></td>
                                        <td><?= $r['activo'] ? 'Sí' : 'No' ?></td>
                                        <td>
                                            <div class="d-flex gap-1">

                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="probarConexion(<?= (int) $r['id'] ?>)">Probar</button>

                                                <a href="correo_config.php?id=<?= (int) $r['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary">Editar</a>

                                                <form id="frmDel<?= (int) $r['id'] ?>" method="post"
                                                    action="correo_config.php">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                    <button type="button" onclick="confirmarEliminar(<?= (int) $r['id'] ?>)"
                                                        class="btn btn-sm btn-outline-danger">Eliminar</button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay configuraciones registradas.</td>
                                    </tr>
                                <?php endif; ?>

                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        </div> <!-- row -->
    </div>

    <?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>

</html>