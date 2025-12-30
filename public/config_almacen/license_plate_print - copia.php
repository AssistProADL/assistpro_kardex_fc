<?php
// public/config_almacen/license_plate_print.php
// Etiqueta License Plate 4x6" / 4x3" en PDF con código de barras Code128 (SVG)

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorSVG;

$lp   = isset($_GET['lp'])   ? trim($_GET['lp'])   : '';
$size = isset($_GET['size']) ? trim($_GET['size']) : '4x6'; // 4x6 o 4x3

if ($lp === '') {
    die("Falta parámetro LP");
}

/* ===========================================================
   1) Encabezado del LP
   =========================================================== */

$sql_head = "
    SELECT 
        ch.CveLP,
        ch.Clave_Contenedor,
        ch.descripcion      AS desc_contenedor,
        ch.Permanente,
        ch.tipo,
        ch.IDContenedor     AS ntarima,
        ap.clave            AS codigo_almacen,
        ap.nombre           AS nombre_almacen,
        u.CodigoCSD         AS bl_codigocsd
    FROM c_charolas ch
    LEFT JOIN ts_existenciatarima t
           ON t.ntarima = ch.IDContenedor
    LEFT JOIN c_almacenp ap
           ON ap.id = t.cve_almac
    LEFT JOIN c_ubicacion u
           ON u.idy_ubica = t.idy_ubica
    WHERE ch.CveLP = :lp
    LIMIT 1
";

$head = db_one($sql_head, ['lp' => $lp]);

if (!$head) {
    die("LP no encontrado.");
}

$ntarima = $head['ntarima'];

/* ===========================================================
   2) Detalle del contenido del LP (agrupado art + lote)
   =========================================================== */

$sql_det = "
    SELECT 
        t.cve_articulo,
        a.des_articulo,
        t.lote,
        SUM(t.existencia) AS existencia
    FROM ts_existenciatarima t
    LEFT JOIN c_articulo a ON a.cve_articulo = t.cve_articulo
    WHERE t.ntarima = :ntarima
      AND t.existencia > 0
    GROUP BY t.cve_articulo, a.des_articulo, t.lote
    ORDER BY a.des_articulo
";

$detalle = db_all($sql_det, ['ntarima' => $ntarima]);

$total_pzas = 0;
foreach ($detalle as $r) {
    $total_pzas += (float)$r['existencia'];
}

/* ===========================================================
   3) Código de barras SVG (Code128)
   =========================================================== */

$generator   = new BarcodeGeneratorSVG();
// scale = 2, height = 60 (puedes ajustar si quieres más alto)
$barcode_svg = $generator->getBarcode($lp, $generator::TYPE_CODE_128, 2, 60);

/* ===========================================================
   4) Tamaño de la hoja (solo 4x6 y 4x3 pulgadas)
   =========================================================== */

if ($size === '4x3') {
    // 4x3 pulgadas ~ 102mm x 76mm
    $page_css = '@page { size: 102mm 76mm; margin: 3mm; }';
} else {
    // 4x6 pulgadas ~ 102mm x 152mm
    $page_css = '@page { size: 102mm 152mm; margin: 4mm; }';
}

/* ===========================================================
   5) HTML de la etiqueta
   =========================================================== */

$fechaHoy = date('d/m/Y');

$html  = '<html><head><meta charset="utf-8"><style>';
$html .= $page_css . "

body { font-family: Arial, sans-serif; font-size:10px; }

.header {
    background:#0F5AAD;
    color:#fff;
    text-align:center;
    font-size:14px;
    font-weight:bold;
    padding:4px;
}

.subheader {
    margin-top:4px;
    font-size:9px;
}

.label-title {
    background:#000;
    color:#fff;
    text-align:center;
    font-size:12px;
    font-weight:bold;
    padding:4px;
    margin-top:4px;
}

.barcode {
    text-align:center;
    margin-top:4px;
    margin-bottom:2px;
}

.table {
    width:100%;
    border-collapse:collapse;
    margin-top:4px;
}

.table th, .table td {
    border:1px solid #000;
    padding:2px;
    font-size:9px;
}

.total {
    text-align:right;
    margin-top:4px;
    font-size:9px;
    font-weight:bold;
}
";
$html .= '</style></head><body>';

$html .= '
<div class="header">ADVENTECH LOGISTICA</div>

<div class="subheader">
    <strong>Fecha:</strong> ' . htmlspecialchars($fechaHoy) . '<br>
    <strong>Almacén:</strong> ' . htmlspecialchars($head["codigo_almacen"]) . ' - ' . htmlspecialchars($head["nombre_almacen"]) . '<br>
    <strong>BL:</strong> ' . htmlspecialchars((string)$head["bl_codigocsd"]) . '<br>
    <strong>Tipo:</strong> ' . htmlspecialchars((string)$head["tipo"]) . '<br>
    <strong>Contenedor:</strong> ' . htmlspecialchars((string)$head["Clave_Contenedor"]) . '
</div>

<div class="label-title">License Plate</div>

<div class="barcode">
    ' . $barcode_svg . '
    <div style="font-size:10px;">' . htmlspecialchars($lp) . '</div>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Artículo</th>
            <th>Descripción</th>
            <th>Lote</th>
            <th>Cant</th>
        </tr>
    </thead>
    <tbody>
';

if ($detalle) {
    foreach ($detalle as $r) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($r["cve_articulo"]) . '</td>
            <td>' . htmlspecialchars($r["des_articulo"]) . '</td>
            <td>' . htmlspecialchars($r["lote"]) . '</td>
            <td style="text-align:right;">' . number_format($r["existencia"], 3, ".", ",") . '</td>
        </tr>
        ';
    }
} else {
    $html .= '
        <tr>
            <td colspan="4" style="text-align:center;">SIN EXISTENCIAS PARA ESTE LP</td>
        </tr>
    ';
}

$html .= '
    </tbody>
</table>

<div class="total">
    Total piezas: ' . number_format($total_pzas, 3, ".", ",") . '
</div>

</body></html>
';

/* ===========================================================
   6) Renderizar PDF con DOMPDF
   =========================================================== */

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$dompdf->stream('LP_' . $lp . '.pdf', ['Attachment' => false]);
exit;
