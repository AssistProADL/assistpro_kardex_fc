<?php
// public/ingresos/orden_compra_pdf.php

require_once __DIR__ . '/../../app/db.php';

function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Autoload flexible para Dompdf
$autoloads = [
  __DIR__ . '/../../vendor/autoload.php',
  __DIR__ . '/../../app/vendor/autoload.php',
  __DIR__ . '/../../lib/vendor/autoload.php',
];
$loaded = false;
foreach ($autoloads as $a) { if (is_file($a)) { require_once $a; $loaded = true; break; } }
if (!$loaded) { die('No se encontró autoload de Dompdf (vendor/autoload.php).'); }

use Dompdf\Dompdf;

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $ex) {
    die('Error BD: ' . e($ex->getMessage()));
}

$id = (int)($_GET['id_aduana'] ?? 0);
if ($id <= 0) die('id_aduana inválido');

$h = null; $d = [];

/**
 * IMPORTANTE:
 * Folio OC real:
 * 1) Pedimento (si existe)
 * 2) num_pedimento (fallback)
 * NO se arma con Consec_protocolo porque eso es consecutivo interno, no folio de negocio.
 */
$st = $pdo->prepare("
  SELECT
    h.ID_Aduana,
    h.ID_Protocolo,
    h.Consec_protocolo,
    h.num_pedimento,
    h.Pedimento,
    COALESCE(NULLIF(TRIM(h.Pedimento),''), NULLIF(TRIM(CAST(h.num_pedimento AS CHAR)),'')) AS FolioOC,
    h.Cve_Almac,
    h.fech_pedimento,
    h.fech_llegPed,
    h.status,
    h.Tipo_Cambio,
    h.Id_moneda,
    p.Nombre AS Proveedor,
    pr.descripcion AS ProtocoloDesc
  FROM th_aduana h
  LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
  LEFT JOIN t_protocolo pr ON pr.ID_Protocolo = h.ID_Protocolo
  WHERE h.ID_Aduana = ?
  LIMIT 1
");
$st->execute([$id]);
$h = $st->fetch(PDO::FETCH_ASSOC);
if (!$h) die('OC no encontrada');

$st2 = $pdo->prepare("
  SELECT
    d.cve_articulo,
    a.des_articulo,
    a.unidadMedida AS UOM,
    d.cantidad,
    d.Cve_Lote
  FROM td_aduana d
  LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
  WHERE d.ID_Aduana=?
  ORDER BY d.num_orden, d.Id_DetAduana
");
$st2->execute([$id]);
$d = $st2->fetchAll(PDO::FETCH_ASSOC);

$moneda = ((int)($h['Id_moneda'] ?? 1) === 1) ? 'MXN' : 'USD';

$fechaOC = '';
try { $fechaOC = (new DateTime($h['fech_pedimento']))->format('Y-m-d'); } catch(Throwable $e) {}

$folioOC = trim((string)($h['FolioOC'] ?? ''));
if ($folioOC === '') $folioOC = 'OC_' . (int)$h['ID_Aduana']; // fallback extremo

$html = '
<html><head><meta charset="utf-8">
<style>
  body{font-family:DejaVu Sans, Arial, sans-serif;font-size:11px;color:#111}
  .t{font-size:18px;font-weight:700;color:#0b5ed7;margin-bottom:8px}
  .grid{width:100%;border-collapse:collapse}
  .grid th,.grid td{border:1px solid #cfd6e3;padding:6px}
  .grid th{background:#f3f6fb;font-weight:700}
  .meta{width:100%;margin-bottom:10px}
  .meta td{padding:2px 0}
  .right{text-align:right}
</style>
</head><body>
  <div class="t">Orden de Compra</div>

  <table class="meta">
    <tr>
      <td><b>Folio OC:</b> '.e($folioOC).'</td>
      <td class="right"><b>ID Aduana:</b> '.e($h['ID_Aduana']).'</td>
    </tr>
    <tr>
      <td><b>Proveedor:</b> '.e($h['Proveedor']).'</td>
      <td class="right"><b>Fecha OC:</b> '.e($fechaOC).'</td>
    </tr>
    <tr>
      <td><b>Tipo OC:</b> '.e($h['ID_Protocolo']).' '.e($h['ProtocoloDesc']).'</td>
      <td class="right"><b>Almacén:</b> '.e($h['Cve_Almac']).'</td>
    </tr>
    <tr>
      <td><b>Moneda:</b> '.e($moneda).'</td>
      <td class="right"><b>TC:</b> '.e($h['Tipo_Cambio']).'</td>
    </tr>
  </table>

  <table class="grid">
    <thead>
      <tr>
        <th style="width:30px">#</th>
        <th style="width:90px">SKU</th>
        <th>Descripción</th>
        <th style="width:50px">UOM</th>
        <th style="width:70px" class="right">Cantidad</th>
        <th style="width:70px">Lote</th>
      </tr>
    </thead>
    <tbody>
';

$i=0; $tot=0.0;
foreach($d as $r){
  $i++;
  $qty = (float)($r['cantidad'] ?? 0);
  $tot += $qty;
  $html .= '<tr>
    <td class="right">'.$i.'</td>
    <td>'.e($r['cve_articulo']).'</td>
    <td>'.e($r['des_articulo']).'</td>
    <td>'.e($r['UOM']).'</td>
    <td class="right">'.number_format($qty,4).'</td>
    <td>'.e($r['Cve_Lote']).'</td>
  </tr>';
}

$html .= '
    </tbody>
    <tfoot>
      <tr>
        <th colspan="4" class="right">Total</th>
        <th class="right">'.number_format($tot,4).'</th>
        <th></th>
      </tr>
    </tfoot>
  </table>
</body></html>';

$dompdf = new Dompdf(['isRemoteEnabled' => true]);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="OC-'.preg_replace('/[^A-Za-z0-9_\-]/','_', $folioOC).'.pdf"');
echo $dompdf->output();
