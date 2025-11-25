<?php
// public/ingresos/orden_compra_pdf_sin_costos.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

$id = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;
if ($id <= 0) {
    die('ID de OC inválido.');
}

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Encabezado
$stH = $pdo->prepare("
    SELECT h.*,
           p.Nombre AS proveedor,
           pr.descripcion AS tipo_oc,
           pr.ID_Protocolo
    FROM th_aduana h
    LEFT JOIN c_proveedores p
        ON p.ID_Proveedor = h.ID_Proveedor
    LEFT JOIN t_protocolo pr
        ON pr.ID_Protocolo = h.ID_Protocolo
    WHERE h.ID_Aduana = :id
    LIMIT 1
");
$stH->execute([':id' => $id]);
$h = $stH->fetch(PDO::FETCH_ASSOC);
if (!$h) {
    die('No se encontró la orden de compra.');
}

// Empresa
$empresaNombre = 'Empresa';
$empresaDir    = '';
try {
    $rowEmp = $pdo->query("
        SELECT des_cia, des_direcc, des_cp
        FROM c_compania
        ORDER BY empresa_id
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    if ($rowEmp) {
        $empresaNombre = $rowEmp['des_cia'] ?? $empresaNombre;
        $empresaDir    = trim(($rowEmp['des_direcc'] ?? '') . ' CP ' . ($rowEmp['des_cp'] ?? ''));
    }
} catch (Throwable $e) {}

// Detalle
$stD = $pdo->prepare("
    SELECT d.*, a.des_articulo, a.cve_umed
    FROM td_aduana d
    LEFT JOIN c_articulo a
        ON a.cve_articulo = d.cve_articulo
    WHERE d.ID_Aduana = :id
    ORDER BY d.Item
");
$stD->execute([':id' => $id]);
$det = $stD->fetchAll(PDO::FETCH_ASSOC);

// Totales de cantidad
$totalCant = 0.0;
foreach ($det as $r) {
    $totalCant += (float)($r['cantidad'] ?? 0);
}

$fechaOc = $h['fech_pedimento'] ? substr((string)$h['fech_pedimento'], 0, 10) : '';
$folio   = $h['Pedimento'] ?? '';
$tipoOc  = trim(($h['ID_Protocolo'] ?? '') . ' ' . ($h['tipo_oc'] ?? ''));

$html = '
<html>
<head>
<meta charset="utf-8">
<style>
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 10px;
}
h1 {
    font-size: 14px;
    color: #0F5AAD;
}
table {
    border-collapse: collapse;
    width: 100%;
}
th, td {
    border: 0.5px solid #999;
    padding: 3px 4px;
}
th {
    background-color: #f0f4ff;
}
.text-right { text-align: right; }
.text-center { text-align: center; }
small { font-size: 8px; }
</style>
</head>
<body>

<table width="100%" style="border:none; margin-bottom:5px;">
  <tr>
    <td style="border:none;">
      <h1>Orden de Compra (Recepción)</h1>
      <strong>' . htmlspecialchars($empresaNombre) . '</strong><br>
      <small>' . htmlspecialchars($empresaDir) . '</small>
    </td>
    <td style="border:none; text-align:right;">
      <strong>Folio OC:</strong> ' . htmlspecialchars($folio) . '<br>
      <strong>ID Aduana:</strong> ' . (int)$h['ID_Aduana'] . '<br>
      <strong>Fecha OC:</strong> ' . htmlspecialchars($fechaOc) . '
    </td>
  </tr>
</table>

<table width="100%" style="border:none; margin-bottom:5px;">
  <tr>
    <td style="border:none; width:50%;">
      <strong>Proveedor:</strong><br>
      ' . htmlspecialchars($h['proveedor'] ?? '') . '
    </td>
    <td style="border:none; width:50%;">
      <strong>Tipo OC:</strong> ' . htmlspecialchars($tipoOc) . '<br>
      <strong>Almacén:</strong> ' . htmlspecialchars($h['Cve_Almac'] ?? '') . '
    </td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th style="width:20px;">#</th>
      <th style="width:80px;">Clave</th>
      <th>Descripción</th>
      <th style="width:40px;">UOM</th>
      <th style="width:80px;" class="text-right">Cantidad</th>
    </tr>
  </thead>
  <tbody>';

if (!$det) {
    $html .= '<tr><td colspan="5" class="text-center">Sin partidas.</td></tr>';
} else {
    $i = 0;
    foreach ($det as $r) {
        $i++;
        $html .= '
        <tr>
          <td class="text-center">' . $i . '</td>
          <td>' . htmlspecialchars($r['cve_articulo'] ?? '') . '</td>
          <td>' . htmlspecialchars($r['des_articulo'] ?? '') . '</td>
          <td class="text-center">' . htmlspecialchars($r['cve_umed'] ?? '') . '</td>
          <td class="text-right">' . number_format((float)$r['cantidad'], 4) . '</td>
        </tr>';
    }
    $html .= '
        <tr>
          <th colspan="4" class="text-right">Total cantidad</th>
          <th class="text-right">' . number_format($totalCant, 4) . '</th>
        </tr>';
}

$html .= '
  </tbody>
</table>

</body>
</html>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream('OC-Recepcion-' . $folio . '.pdf', ['Attachment' => false]);
