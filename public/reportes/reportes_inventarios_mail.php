<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$reporte   = $_POST['reporte']   ?? 'existencias';
$almacen   = $_POST['almacen']   ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';
$f_ini     = $_POST['f_ini']     ?? '';
$f_fin     = $_POST['f_fin']     ?? '';

$mail_to      = $_POST['mail_to']      ?? '';
$mail_subject = $_POST['mail_subject'] ?? 'Reporte de Inventarios';
$mail_body    = $_POST['mail_body']    ?? 'Adjunto envío reporte.';

if (!$mail_to) {
    echo "Debe indicar un destinatario.";
    exit;
}

// Datos de la empresa para el encabezado del Excel
$cia = [
    'nombre'   => 'Adventech Logística',
    'rfc'      => '',
    'direccion'=> ''
];

try {
    $tmp = db_one("SELECT nombre, rfc, direccion 
                   FROM c_compania 
                   WHERE id = 1");
    if ($tmp) {
        $cia = array_merge($cia, array_filter($tmp));
    }
} catch (Exception $e) {
    // Si falla, usamos defaults
}

// Mismo mapeo que en la API
$config = [
    'existencias' => [
        'vista'      => 'v_rpt_inv_existencias_ubicacion',
        'titulo'     => 'Existencias por Ubicación',
        'usa_fecha'  => false,
        'campo_fecha'=> null
    ],
    'kardex' => [
        'vista'      => 'v_rpt_inv_kardex',
        'titulo'     => 'Kardex Inventario',
        'usa_fecha'  => true,
        'campo_fecha'=> 'fecha_mov'
    ],
    'maxmin' => [
        'vista'      => 'v_rpt_inv_maxmin',
        'titulo'     => 'Máximos y Mínimos',
        'usa_fecha'  => false,
        'campo_fecha'=> null
    ],
];

if (!isset($config[$reporte])) {
    echo "Reporte no soportado.";
    exit;
}

$vista      = $config[$reporte]['vista'];
$titulo     = $config[$reporte]['titulo'];
$usa_fecha  = $config[$reporte]['usa_fecha'];
$campo_fecha= $config[$reporte]['campo_fecha'];

// Armar SQL (igual criterio que API)
$sql = "SELECT * FROM {$vista} WHERE 1=1 ";
$params = [];

if ($almacen) {
    $sql .= " AND cve_almac = ? ";
    $params[] = $almacen;
}
if ($ubicacion) {
    $sql .= " AND ubicacion_id = ? ";
    $params[] = $ubicacion;
}
if ($usa_fecha && $campo_fecha) {
    if ($f_ini) {
        $sql .= " AND {$campo_fecha} >= ? ";
        $params[] = $f_ini . " 00:00:00";
    }
    if ($f_fin) {
        $sql .= " AND {$campo_fecha} <= ? ";
        $params[] = $f_fin . " 23:59:59";
    }
}

$sql .= " ORDER BY 1 DESC LIMIT 5000";

$rows = db_all($sql, $params);

// ===============================
// Generar Excel en memoria
// ===============================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(substr($titulo,0,31));

// Encabezado corporativo
$row = 1;
$sheet->setCellValue("A{$row}", $cia['nombre']);    $row++;
$sheet->setCellValue("A{$row}", $cia['rfc']);       $row++;
$sheet->setCellValue("A{$row}", $cia['direccion']); $row++;
$sheet->setCellValue("A{$row}", $titulo);           $row++;
$sheet->setCellValue("A{$row}", 'Fecha: ' . date('d/m/Y H:i')); $row += 2;

// Encabezados y datos
if (!empty($rows)) {
    $headers = array_keys($rows[0]);
    $colIdx = 1;
    foreach($headers as $h){
        $sheet->setCellValueByColumnAndRow($colIdx, $row, $h);
        $colIdx++;
    }

    $row++;
    foreach($rows as $r){
        $colIdx = 1;
        foreach($headers as $h){
            $sheet->setCellValueByColumnAndRow($colIdx, $row, $r[$h]);
            $colIdx++;
        }
        $row++;
    }
}

$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "reporte_inv_" . uniqid() . ".xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($tmpFile);

// ===============================
// Enviar correo
// ===============================
try {
    $mail = new PHPMailer(true);

    // Config SMTP (ajusta a tu .env o tabla de configuración)
    $mail->isSMTP();
    $mail->Host       = getenv('MAIL_HOST') ?: 'smtp.tudominio.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('MAIL_USER') ?: 'usuario@tudominio.com';
    $mail->Password   = getenv('MAIL_PASS') ?: 'password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = getenv('MAIL_PORT') ?: 587;

    $mail->setFrom(getenv('MAIL_FROM') ?: 'no-reply@tudominio.com', 'AssistPro WMS');
    $mail->addAddress($mail_to);

    $mail->Subject = $mail_subject;
    $mail->Body    = $mail_body;
    $mail->isHTML(false);

    $mail->addAttachment($tmpFile, $titulo . ".xlsx");

    $mail->send();

    @unlink($tmpFile);

    echo "Correo enviado correctamente.";
} catch (Exception $e) {
    @unlink($tmpFile);
    echo "Error al enviar correo: " . $mail->ErrorInfo;
}
