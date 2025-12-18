<?php
// public/config/correo_test.php
//@session_start();

require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/mailer_common.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$smtps = db_all("SELECT id, nombre FROM c_smtp_config ORDER BY id");
$plantillas = db_all("SELECT id, codigo, descripcion, asunto, cuerpo_html, cuerpo_texto
                      FROM c_correo_plantilla
                      WHERE activo = 1
                      ORDER BY codigo");

$mensajeOk = null;
$mensajeErr = null;

// --- POST: probar envío ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = trim($_POST['to_email'] ?? '');
    $smtp_config_id = (int) ($_POST['smtp_config_id'] ?? 1);
    $alias_smtp = trim($_POST['alias_smtp'] ?? '');
    $plantilla_id = (int) ($_POST['plantilla_id'] ?? 0);
    $asuntoLibre = trim($_POST['asunto_libre'] ?? '');
    $cuerpoLibre = $_POST['cuerpo_libre'] ?? '';

    if ($toEmail === '') {
        $mensajeErr = 'Debes especificar el correo destino.';
    } else {
        try {
            // 1) Config SMTP
            $cfg = mailer_get_smtp_config($smtp_config_id, $alias_smtp ?: null);
            if (!$cfg) {
                throw new Exception('No se encontró configuración SMTP.');
            }

            // 2) Resolver asunto / cuerpo
            $asunto = $asuntoLibre;
            $html = $cuerpoLibre;
            $texto = strip_tags($cuerpoLibre);

            if ($plantilla_id > 0) {
                $tpl = null;
                foreach ($plantillas as $p) {
                    if ((int) $p['id'] === $plantilla_id) {
                        $tpl = $p;
                        break;
                    }
                }
                if ($tpl) {
                    // Datos fake para probar placeholders
                    $data = [
                        'NOMBRE' => 'Prueba AssistPro',
                        'EMAIL' => $toEmail,
                        'FECHA' => date('d/m/Y'),
                    ];
                    $asunto = mailer_render_template($tpl['asunto'], $data);
                    $html = mailer_render_template($tpl['cuerpo_html'], $data);
                    $texto = mailer_render_template($tpl['cuerpo_texto'] ?: strip_tags($html), $data);
                }
            }

            if ($asunto === '') {
                $asunto = 'Prueba de correo AssistPro';
            }
            if ($html === '') {
                $html = '<p>Prueba de correo AssistPro.</p>';
            }

            // 3) PHPMailer directo (sin cola)
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

            $mail->CharSet = 'UTF-8';

            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($toEmail);

            $mail->Subject = $asunto;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $texto;

            $mail->send();

            $mensajeOk = "Correo de prueba enviado correctamente a {$toEmail}.";
            $logger->info('Correo de prueba enviado', [
                'to' => $toEmail,
                'smtp' => $smtp_config_id,
                'alias' => $alias_smtp,
                'plantilla_id' => $plantilla_id,
            ]);

        } catch (Exception $e) {
            $mensajeErr = "Error al enviar correo de prueba: " . $e->getMessage();
            $logger->error('Error en correo de prueba', [
                'error' => $e->getMessage(),
                'to' => $toEmail,
                'smtp' => $smtp_config_id,
            ]);
        } catch (\Throwable $e) {
            $mensajeErr = "Error general: " . $e->getMessage();
            $logger->error('Error general en correo de prueba', [
                'error' => $e->getMessage(),
                'to' => $toEmail,
                'smtp' => $smtp_config_id,
            ]);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Prueba de Envío de Correo - AssistPro</title>
    <link rel="stylesheet" href="/assistpro_kardex_fc/public/assets/bootstrap.min.css">
    <style>
        body {
            font-size: 12px;
        }

        .container-main {
            padding: 15px;
        }

        textarea {
            font-size: 11px;
            height: 160px;
        }
    </style>
</head>

<body>
    <div class="container-main">
        <h4>Prueba de Envío de Correo</h4>
        <hr>

        <?php if ($mensajeOk): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensajeOk) ?></div>
        <?php elseif ($mensajeErr): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensajeErr) ?></div>
        <?php endif; ?>

        <form method="post" action="correo_test.php">
            <div class="row">
                <div class="col-md-5">
                    <div class="mb-2">
                        <label class="form-label">Correo destino</label>
                        <input type="email" name="to_email" class="form-control form-control-sm" required
                            placeholder="alguien@dominio.com" value="<?= htmlspecialchars($_POST['to_email'] ?? '') ?>">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Configuración SMTP</label>
                        <select name="smtp_config_id" class="form-select form-select-sm">
                            <?php foreach ($smtps as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= isset($_POST['smtp_config_id']) && (int) $_POST['smtp_config_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= (int) $s['id'] . ' - ' . htmlspecialchars($s['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Alias SMTP (opcional)</label>
                        <input type="text" name="alias_smtp" class="form-control form-control-sm"
                            placeholder="facturacion, soporte..."
                            value="<?= htmlspecialchars($_POST['alias_smtp'] ?? '') ?>">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Plantilla (opcional)</label>
                        <select name="plantilla_id" class="form-select form-select-sm">
                            <option value="0">-- Sin plantilla (usar asunto/cuerpo libre) --</option>
                            <?php foreach ($plantillas as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= isset($_POST['plantilla_id']) && (int) $_POST['plantilla_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['codigo'] . ' - ' . $p['descripcion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            Si eliges plantilla, se ignorarán el asunto/cuerpo libres
                            y se usarán marcadores como {NOMBRE}, {FECHA}, {EMAIL}.
                        </small>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="mb-2">
                        <label class="form-label">Asunto (si no usas plantilla)</label>
                        <input type="text" name="asunto_libre" class="form-control form-control-sm"
                            placeholder="Prueba de correo AssistPro"
                            value="<?= htmlspecialchars($_POST['asunto_libre'] ?? '') ?>">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Cuerpo HTML (si no usas plantilla)</label>
                        <textarea name="cuerpo_libre" class="form-control form-control-sm"
                            placeholder="<p>Contenido de prueba</p>"><?= htmlspecialchars($_POST['cuerpo_libre'] ?? '') ?></textarea>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Enviar correo de prueba
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>

</html>