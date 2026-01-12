<?php
// public/pedidos/pedido_pdf.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id_pedido'] ?? 0);
if ($id <= 0) die('Pedido no válido.');

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Encabezado
$h = $pdo->prepare("
    SELECT
        h.*,
        c.RazonSocial,
        c.RFC,
        ap.nombre AS almacen_nombre
    FROM th_pedido h
    LEFT JOIN c_cliente c
      ON c.Cve_Clte COLLATE utf8mb4_unicode_ci
       = h.Cve_clte COLLATE utf8mb4_unicode_ci
    LEFT JOIN c_almacenp ap
      ON CAST(ap.id AS CHAR) COLLATE utf8mb4_unicode_ci
       = h.cve_almac COLLATE utf8mb4_unicode_ci
    WHERE h.id_pedido = ?
    LIMIT 1
");
$h->execute([$id]);
$head = $h->fetch(PDO::FETCH_ASSOC);
if (!$head) die('Pedido no encontrado.');

// Detalle
$d = $pdo->prepare("
    SELECT
        d.*,
        a.des_articulo
    FROM td_pedido d
    LEFT JOIN c_articulo a
      ON a.cve_articulo COLLATE utf8mb4_unicode_ci
       = d.Cve_articulo COLLATE utf8mb4_unicode_ci
    WHERE d.Fol_folio COLLATE utf8mb4_unicode_ci
        = ? COLLATE utf8mb4_unicode_ci
    ORDER BY d.id
");
$d->execute([$head['Fol_folio']]);
$det = $d->fetchAll(PDO::FETCH_ASSOC);

// Título dinámico
$tipo = strtoupper(trim((string)($head['TipoPedido'] ?? '')));
$titulo = ($tipo === 'RUTA') ? 'Pedido Ruta' : 'Pedido Cliente';

// Totales
$sub = 0; $iva = 0; $tot = 0;
foreach ($det as $r) {
    $line = ((float)$r['Precio_unitario'] * (float)$r['Num_cantidad']) - (float)($r['Desc_Importe'] ?? 0);
    $sub += $line;
    $iva += (float)($r['IVA'] ?? 0);
}
$tot = $sub + $iva;

// HTML
ob_start();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
body{ font-family:DejaVu Sans, sans-serif; font-size:11px; }
h1{ font-size:18px; color:#0F5AAD; margin-bottom:6px; }
table{ width:100%; border-collapse:collapse; }
th,td{ border:1px solid #d1d5db; padding:4px; }
th{ background:#f3f4f6; }
.right{text-align:right;}
</style>
</head>
<body>

<h1><?= $titulo ?></h1>

<p>
<strong>Folio:</strong> <?= htmlspecialchars($head['Fol_folio']) ?><br>
<strong>Cliente:</strong> <?= htmlspecialchars($head['RazonSocial'] ?? '') ?><br>
<strong>RFC:</strong> <?= htmlspecialchars($head['RFC'] ?? '') ?><br>
<strong>Almacén:</strong> <?= htmlspecialchars($head['almacen_nombre'] ?? '') ?><br>
<strong>Fecha Pedido:</strong> <?= htmlspecialchars($head['Fec_Pedido']) ?><br>
<strong>Fecha Entrega:</strong> <?= htmlspecialchars($head['Fec_Entrega']) ?>
</p>

<table>
<thead>
<tr>
<th>#</th>
<th>Artículo</th>
<th>Descripción</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Importe</th>
</tr>
</thead>
<tbody>
<?php foreach ($det as $i=>$r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['Cve_articulo']) ?></td>
<td><?= htmlspecialchars($r['des_articulo'] ?? '') ?></td>
<td class="right"><?= number_format($r['Num_cantidad'],2) ?></td>
<td class="right"><?= number_format($r['Precio_unitario'],2) ?></td>
<td class="right"><?= number_format(($r['Precio_unitario']*$r['Num_cantidad']) - ($r['Desc_Importe'] ?? 0),2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p class="right">
<strong>Subtotal:</strong> <?= number_format($sub,2) ?><br>
<strong>IVA:</strong> <?= number_format($iva,2) ?><br>
<strong>Total:</strong> <?= number_format($tot,2) ?>
</p>

</body>
</html>
<?php
$html = ob_get_clean();

// DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream($titulo . "_" . $head['Fol_folio'] . ".pdf", ['Attachment'=>false]);
