<?php
// public/utilerias/mailer_worker.php
//
// Worker del motor de correos AssistPro:
//  - Toma correos pendientes de t_correo_queue
//  - Obtiene config SMTP desde c_smtp_config (con alias)
//  - Envía con PHPMailer
//  - Registra intentos / errores
//  - Adjuntos opcionales:
//      * PDF  (MAILER_ATTACH_PDF=true)
//      * CSV  (MAILER_ATTACH_CSV=true)
//      * XLSX (MAILER_ATTACH_EXCEL=true)
//
// Requiere:
//  - app/bootstrap.php
//  - app/mailer_common.php
//  - vendor/autoload.php (incluido por bootstrap)

require_once __DIR__ . '/../../app/mailer_common.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$logger = app_logger();

echo "[" . date('Y-m-d H:i:s') . "] Mailer Worker iniciado\n";
$logger->info('Mailer Worker iniciado');

// --------- 1) Obtener correos pendientes de la cola ---------

$sql = "SELECT 
            q.*,
            j.smtp_config_id,
            j.alias_smtp
        FROM t_correo_queue q
        LEFT JOIN t_correo_job j ON j.id = q.job_id
        WHERE q.enviado = 0
          AND q.intentos < 5
        ORDER BY q.fecha_creacion ASC
        LIMIT :lim";

$stmt = db()->prepare($sql);
$stmt->bindValue(':lim', 200, PDO::PARAM_INT);
$stmt->execute();
$queue = $stmt->fetchAll();

if (!$queue) {
    echo "No hay correos pendientes.\n";
    $logger->info('No hay correos pendientes');
    exit;
}

echo "Correos pendientes: " . count($queue) . "\n";
$logger->info('Correos pendientes', ['total' => count($queue)]);

// Sentencias preparadas para actualizar estado
$sqlOk = "UPDATE t_correo_queue
          SET enviado = 1,
              fecha_enviado = NOW(),
              intentos = intentos + 1,
              ultimo_intento = NOW(),
              error_msg = NULL
          WHERE id = :id";
$stmtOk = db()->prepare($sqlOk);

$sqlErr = "UPDATE t_correo_queue
           SET intentos = intentos + 1,
               ultimo_intento = NOW(),
               error_msg = :err
           WHERE id = :id";
$stmtErr = db()->prepare($sqlErr);

// --------- 2) Helpers para adjuntos ---------

/**
 * Genera un PDF básico para el registro de cola.
 */
function mailer_generate_pdf_for_queue(array $q, Logger $logger): ?string
{
    $attach = filter_var(env('MAILER_ATTACH_PDF', 'false'), FILTER_VALIDATE_BOOLEAN);
    if (!$attach) {
        return null;
    }

    try {
        $html = "
            <h2>Detalle de notificación AssistPro</h2>
            <p><strong>Destinatario:</strong> " . htmlspecialchars($q['email_to']) . "</p>
            <p><strong>Asunto:</strong> " . htmlspecialchars($q['asunto_resuelto']) . "</p>
            <p><strong>Fecha de generación:</strong> " . date('d/m/Y H:i:s') . "</p>
            <hr>
            <p>Este documento fue generado automáticamente por el motor de correos AssistPro.</p>
        ";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();

        $baseDir = realpath(__DIR__ . '/../../');
        $pdfDir  = $baseDir . '/temp/mailer_pdfs';
        if (!is_dir($pdfDir)) {
            @mkdir($pdfDir, 0777, true);
        }

        $filePath = $pdfDir . '/mail_' . (int)$q['id'] . '_' . date('Ymd_His') . '.pdf';
        file_put_contents($filePath, $output);

        $logger->info('PDF generado para correo', [
            'queue_id' => (int)$q['id'],
            'path'     => $filePath,
        ]);

        return $filePath;
    } catch (\Throwable $e) {
        $logger->error('Error generando PDF', [
            'queue_id' => (int)$q['id'],
            'error'    => $e->getMessage(),
        ]);
        return null;
    }
}

/**
 * Genera un CSV sencillo con info del correo.
 */
function mailer_generate_csv_for_queue(array $q, Logger $logger): ?string
{
    $attach = filter_var(env('MAILER_ATTACH_CSV', 'false'), FILTER_VALIDATE_BOOLEAN);
    if (!$attach) {
        return null;
    }

    try {
        $rows = [
            ['Campo',        'Valor'],
            ['Correo destino', $q['email_to']],
            ['Asunto',         $q['asunto_resuelto']],
            ['Fecha',          date('Y-m-d H:i:s')],
        ];

        $csv  = '';
        foreach ($rows as $r) {
            $csv .= '"' . implode('","', array_map('str_replace', array_fill(0, count($r), '"'), array_fill(0, count($r), '""'), $r)) . "\"\r\n";
        }

        $baseDir = realpath(__DIR__ . '/../../');
        $csvDir  = $baseDir . '/temp/mailer_csv';
        if (!is_dir($csvDir)) {
            @mkdir($csvDir, 0777, true);
        }

        $filePath = $csvDir . '/mail_' . (int)$q['id'] . '_' . date('Ymd_His') . '.csv';
        file_put_contents($filePath, $csv);

        $logger->info('CSV generado para correo', [
            'queue_id' => (int)$q['id'],
            'path'     => $filePath,
        ]);

        return $filePath;
    } catch (\Throwable $e) {
        $logger->error('Error generando CSV', [
            'queue_id' => (int)$q['id'],
            'error'    => $e->getMessage(),
        ]);
        return null;
    }
}

/**
 * Genera un XLSX sencillo con info del correo usando PhpSpreadsheet.
 */
function mailer_generate_excel_for_queue(array $q, Logger $logger): ?string
{
    $attach = filter_var(env('MAILER_ATTACH_EXCEL', 'false'), FILTER_VALIDATE_BOOLEAN);
    if (!$attach) {
        return null;
    }

    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Campo');
        $sheet->setCellValue('B1', 'Valor');

        $sheet->setCellValue('A2', 'Correo destino');
        $sheet->setCellValue('B2', $q['email_to']);

        $sheet->setCellValue('A3', 'Asunto');
        $sheet->setCellValue('B3', $q['asunto_resuelto']);

        $sheet->setCellValue('A4', 'Fecha');
        $sheet->setCellValue('B4', date('Y-m-d H:i:s'));

        $baseDir = realpath(__DIR__ . '/../../');
        $xlsxDir = $baseDir . '/temp/mailer_xlsx';
        if (!is_dir($xlsxDir)) {
            @mkdir($xlsxDir, 0777, true);
        }

        $filePath = $xlsxDir . '/mail_' . (int)$q['id'] . '_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $logger->info('XLSX generado para correo', [
            'queue_id' => (int)$q['id'],
            'path'     => $filePath,
        ]);

        return $filePath;
    } catch (\Throwable $e) {
        $logger->error('Error generando XLSX', [
            'queue_id' => (int)$q['id'],
            'error'    => $e->getMessage(),
        ]);
        return null;
    }
}

// --------- 3) Procesar cada registro de la cola ---------

foreach ($queue as $q) {
    $idQueue = (int)$q['id'];

    echo "Procesando #{$idQueue} -> {$q['email_to']}\n";
    $logger->info('Procesando correo en cola', [
        'queue_id' => $idQueue,
        'to'       => $q['email_to'],
    ]);

    // 3.1) Config SMTP (con alias)
    $smtpId = !empty($q['smtp_config_id']) ? (int)$q['smtp_config_id'] : 1;
    $alias  = !empty($q['alias_smtp']) ? $q['alias_smtp'] : null;

    $cfg = mailer_get_smtp_config($smtpId, $alias);
    if (!$cfg) {
        echo "  [#{$idQueue}] ERROR: Config SMTP no encontrada (id={$smtpId})\n";
        $logger->error('Config SMTP no encontrada', [
            'queue_id' => $idQueue,
            'smtp_id'  => $smtpId,
        ]);

        $stmtErr->execute([
            ':id'  => $idQueue,
            ':err' => 'Config SMTP no encontrada',
        ]);
        continue;
    }

    $mail = new PHPMailer(true);

    // rutas para limpiar al final
    $pdfPath   = null;
    $csvPath   = null;
    $excelPath = null;

    try {
        // 3.2) Configurar PHPMailer
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = (int)$cfg['puerto'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['usuario'];
        $mail->Password   = $cfg['password'];

        if ($cfg['seguridad'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($cfg['seguridad'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false;
        }

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($q['email_to']);

        $mail->Subject = $q['asunto_resuelto'];

        if (!empty($q['cuerpo_resuelto_html'])) {
            $mail->isHTML(true);
            $mail->Body = $q['cuerpo_resuelto_html'];
            if (!empty($q['cuerpo_resuelto_texto'])) {
                $mail->AltBody = $q['cuerpo_resuelto_texto'];
            }
        } else {
            $mail->isHTML(false);
            $mail->Body = $q['cuerpo_resuelto_texto'] ?? '';
        }

        // 3.3) Generar adjuntos según .env
        $pdfPath   = mailer_generate_pdf_for_queue($q, $logger);
        $csvPath   = mailer_generate_csv_for_queue($q, $logger);
        $excelPath = mailer_generate_excel_for_queue($q, $logger);

        if ($pdfPath) {
            $mail->addAttachment($pdfPath, 'detalle_notificacion.pdf');
        }
        if ($csvPath) {
            $mail->addAttachment($csvPath, 'detalle.csv');
        }
        if ($excelPath) {
            $mail->addAttachment($excelPath, 'detalle.xlsx');
        }

        // 3.4) Enviar
        $mail->send();

        echo "  [#{$idQueue}] OK enviado\n";
        $logger->info('Correo enviado correctamente', [
            'queue_id' => $idQueue,
            'to'       => $q['email_to'],
        ]);

        $stmtOk->execute([':id' => $idQueue]);

    } catch (Exception $e) {
        $msgErr = 'Mailer Error: ' . $e->getMessage();
        echo "  [#{$idQueue}] ERROR: {$msgErr}\n";
        $logger->error('Error enviando correo', [
            'queue_id' => $idQueue,
            'to'       => $q['email_to'],
            'error'    => $msgErr,
        ]);

        $stmtErr->execute([
            ':id'  => $idQueue,
            ':err' => substr($msgErr, 0, 1000),
        ]);
    } catch (\Throwable $e) {
        $msgErr = 'Error general: ' . $e->getMessage();
        echo "  [#{$idQueue}] ERROR: {$msgErr}\n";
        $logger->error('Error general enviando correo', [
            'queue_id' => $idQueue,
            'to'       => $q['email_to'],
            'error'    => $msgErr,
        ]);

        $stmtErr->execute([
            ':id'  => $idQueue,
            ':err' => substr($msgErr, 0, 1000),
        ]);
    } finally {
        // Borrar archivos temporales
        foreach ([$pdfPath, $csvPath, $excelPath] as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}

echo "Mailer Worker terminado.\n";
$logger->info('Mailer Worker terminado');
