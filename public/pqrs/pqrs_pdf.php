<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../../vendor/autoload.php';

/* =========================================================
   CONFIGURACIÓN DOMPDF
   ========================================================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

/* =========================================================
   LOGO CORPORATIVO (BASE64 – INFALIBLE)
   ========================================================= */
$logoPath = __DIR__ . '/../assets/branding/logo_pdf.jpg';

if (!file_exists($logoPath)) {
    die('ERROR CRÍTICO: Logo no encontrado → ' . $logoPath);
}

$logoBase64 = base64_encode(file_get_contents($logoPath));
$logoSrc    = 'data:image/jpeg;base64,' . $logoBase64;

/* =========================================================
   DATA (AJUSTA A BD / SERVICIO REAL)
   ========================================================= */
$folio       = $_GET['folio'] ?? 'PQRS-000123';
$fecha       = date('d/m/Y');
$cliente     = 'Cliente Demo SA de CV';
$descripcion = 'Detalle de la solicitud PQRS';

/* =========================================================
   HTML DEL PDF
   ========================================================= */
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 11px;
        color: #333;
    }
    .header {
        width: 100%;
        border-bottom: 2px solid #0b3a82;
        margin-bottom: 15px;
    }
    .logo {
        width: 140px;
    }
    .title {
        font-size: 16px;
        font-weight: bold;
        color: #0b3a82;
        margin-top: 10px;
    }
    .box {
        border: 1px solid #ccc;
        padding: 10px;
        margin-top: 10px;
    }
    .label {
        font-weight: bold;
        color: #555;
    }
</style>
</head>

<body>

<!-- HEADER -->
<table class="header">
    <tr>
        <td style="width:50%">
            <img src="$logoSrc" class="logo">
        </td>
        <td style="width:50%; text-align:right;">
            <div class="title">Reporte PQRS</div>
            <div>Folio: <strong>$folio</strong></div>
            <div>Fecha: $fecha</div>
        </td>
    </tr>
</table>

<!-- CUERPO -->
<div class="box">
    <div><span class="label">Cliente:</span> $cliente</div>
    <div style="margin-top:5px"><span class="label">Descripción:</span></div>
    <div>$descripcion</div>
</div>

</body>
</html>
HTML;

/* =========================================================
   RENDER PDF
   ========================================================= */
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

/* =========================================================
   OUTPUT
   ========================================================= */
$dompdf->stream(
    "PQRS_$folio.pdf",
    ["Attachment" => false] // false = inline | true = descarga
);
