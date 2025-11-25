<?php
// public/procesos/servicio_depot/servicio_ingreso_pdf.php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID de servicio inválido');
}

// Traer datos del caso + almacén + cliente
$sql = "SELECT s.*, a.des_almac,
               c.RazonSocial AS cliente_nombre,
               c.Cve_Clte    AS cliente_clave
        FROM th_servicio_caso s
        LEFT JOIN c_almacen a ON a.cve_almac = s.origen_almacen_id
        LEFT JOIN c_cliente c ON c.id_cliente = s.cliente_id
        WHERE s.id = :id";
$st = $pdo->prepare($sql);
$st->execute([':id' => $id]);
$caso = $st->fetch(PDO::FETCH_ASSOC);

if (!$caso) {
    die('Servicio no encontrado');
}

$folio        = $caso['folio'];
$fechaAlta    = $caso['fecha_alta'];
$almacen      = $caso['des_almac'] ?? '';
$clienteNom   = $caso['cliente_nombre'] ?? '';
$clienteClave = $caso['cliente_clave'] ?? '';
$articulo     = $caso['articulo'];
$serie        = $caso['serie'];
$motivo       = $caso['motivo'];
$observacion  = $caso['observacion_inicial'];

// Logo (si existe). Ajusta la ruta según tu estructura real de imágenes.
$logoWebPath   = '/public/img/logo_adventech.png'; // ruta web sugerida
$logoFilePath  = __DIR__ . '/../../img/logo_adventech.png';
$logoTag = '';
if (is_file($logoFilePath)) {
    // Usamos ruta absoluta de archivo para dompdf
    $logoTag = '<img src="' . htmlspecialchars($logoFilePath) . '" style="height:45px;">';
}

// HTML del documento
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingreso ' . htmlspecialchars($folio) . '</title>
<style>
    @page {
        margin: 20mm;
    }
    body {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #333;
    }
    .header {
        border-bottom: 2px solid #004C97;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }
    .header-table {
        width: 100%;
    }
    .header-title {
        color: #004C97;
        font-size: 14pt;
        font-weight: bold;
        text-align: right;
    }
    .header-subtitle {
        font-size: 9pt;
        text-align: right;
        color: #555;
    }
    .doc-title {
        text-align: center;
        font-size: 12pt;
        font-weight: bold;
        color: #004C97;
        margin-bottom: 5px;
    }
    .doc-subtitle {
        text-align: center;
        font-size: 9pt;
        color: #666;
        margin-bottom: 15px;
    }
    .block {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 6px 8px;
        margin-bottom: 8px;
    }
    .block-title {
        font-weight: bold;
        font-size: 9.5pt;
        color: #004C97;
        margin-bottom: 4px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 2px;
    }
    .label {
        font-weight: bold;
        color: #444;
    }
    .value {
        font-weight: normal;
    }
    .row {
        display: flex;
        flex-wrap: nowrap;
        margin-bottom: 2px;
    }
    .col-50 {
        width: 50%;
        box-sizing: border-box;
    }
    .col-33 {
        width: 33.33%;
        box-sizing: border-box;
    }
    .col-100 {
        width: 100%;
        box-sizing: border-box;
    }
    .mt-5 { margin-top: 5px; }
    .mt-10 { margin-top: 10px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .small { font-size: 8pt; color: #777; }
    .obs-box {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 4px 6px;
        min-height: 45mm;
        white-space: pre-wrap;
    }
    .sign-row {
        margin-top: 18mm;
        display: flex;
        justify-content: space-between;
    }
    .sign-box {
        width: 45%;
        text-align: center;
        font-size: 9pt;
    }
    .sign-line {
        border-bottom: 1px solid #333;
        height: 18px;
        margin-bottom: 2px;
    }
</style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:50%; vertical-align:middle;">' . $logoTag . '</td>
            <td style="width:50%; text-align:right; vertical-align:middle;">
                <div class="header-title">Adventech Logística</div>
                <div class="header-subtitle">Módulo de Servicio y Garantías</div>
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">Documento de Ingreso de Equipo a Servicio</div>
<div class="doc-subtitle">Folio: <strong>' . htmlspecialchars($folio) . '</strong> &nbsp;&nbsp;|&nbsp;&nbsp; Fecha de ingreso: ' . htmlspecialchars($fechaAlta) . '</div>

<div class="block">
    <div class="block-title">Datos Generales</div>
    <div class="row">
        <div class="col-50">
            <span class="label">Almacén (Depot): </span>
            <span class="value">' . htmlspecialchars($almacen) . '</span>
        </div>
        <div class="col-50 text-right">
            <span class="label">ID Servicio: </span>
            <span class="value">' . (int)$id . '</span>
        </div>
    </div>
</div>

<div class="block">
    <div class="block-title">Datos del Cliente</div>
    <div class="row">
        <div class="col-33">
            <span class="label">Clave Cliente: </span>
            <span class="value">' . htmlspecialchars($clienteClave) . '</span>
        </div>
        <div class="col-67">
            <span class="label">Nombre: </span>
            <span class="value">' . htmlspecialchars($clienteNom) . '</span>
        </div>
    </div>
</div>

<div class="block">
    <div class="block-title">Datos del Equipo</div>
    <div class="row">
        <div class="col-50">
            <span class="label">Artículo: </span>
            <span class="value">' . htmlspecialchars($articulo) . '</span>
        </div>
        <div class="col-50">
            <span class="label">Motivo: </span>
            <span class="value">' . htmlspecialchars($motivo) . '</span>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-50">
            <span class="label">Número de serie: </span>
            <span class="value">' . htmlspecialchars($serie) . '</span>
        </div>
    </div>
</div>

<div class="block">
    <div class="block-title">Observaciones Iniciales del Cliente / Depot</div>
    <div class="obs-box">'
        . htmlspecialchars($observacion ?: 'N/A') .
    '</div>
</div>

<div class="block">
    <div class="block-title">Uso Interno (Diagnóstico / Comentarios Técnicos)</div>
    <div class="obs-box"></div>
</div>

<div class="sign-row">
    <div class="sign-box">
        <div class="sign-line"></div>
        <div>Recibió (Depot)</div>
    </div>
    <div class="sign-box">
        <div class="sign-line"></div>
        <div>Cliente</div>
    </div>
</div>

<div class="mt-10 small">
    Este documento ampara únicamente la recepción del equipo para revisión y/o reparación. La aceptación de costos
    de servicio, refacciones y condiciones de garantía se realizará a través de la cotización correspondiente.
</div>

</body>
</html>
';

// Configuración de Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$dompdf->stream('Ingreso_' . $folio . '.pdf', ['Attachment' => false]);
exit;
