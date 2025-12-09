<?php
// /public/portal_clientes/imprimir_pedido.php

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID de pedido inválido');
}

// Encabezado
$pedido = db_one(
    "SELECT id, fecha, cliente_id, usuario, total, estatus
       FROM assistpro_etl_fc.t_pedido_web
      WHERE id = :id",
    [':id' => $id]
);

if (!$pedido) {
    die('Pedido no encontrado');
}

// Detalle
$detalle = db_all(
    "SELECT cve_articulo, des_articulo, cantidad, precio_unit, total_renglon
       FROM assistpro_etl_fc.t_pedido_web_det
      WHERE pedido_id = :id
      ORDER BY id",
    [':id' => $id]
);

$fecha = $pedido['fecha'];
$total = (float)$pedido['total'];

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Pedido <?php echo htmlspecialchars($pedido['id']); ?></title>
<style>
body{font-family:DejaVu Sans, Arial, sans-serif;font-size:10px}
h1{font-size:16px;margin:0 0 4px;color:#0F5AAD}
h2{font-size:12px;margin:10px 0 4px}
table{width:100%;border-collapse:collapse;margin-top:6px}
th,td{border:1px solid #ccc;padding:4px 5px}
th{background:#f0f3fa}
.right{text-align:right}
.small{font-size:9px}
</style>
</head>
<body>
  <h1>AssistPro WMS — Pedido Web</h1>
  <div class="small">
    <strong>Folio:</strong> <?php echo (int)$pedido['id']; ?><br>
    <strong>Fecha:</strong> <?php echo htmlspecialchars($fecha); ?><br>
    <strong>Usuario:</strong> <?php echo htmlspecialchars($pedido['usuario'] ?? ''); ?><br>
    <strong>Estatus:</strong> <?php echo htmlspecialchars($pedido['estatus'] ?? ''); ?><br>
  </div>

  <h2>Detalle</h2>
  <table>
    <thead>
      <tr>
        <th>Clave</th>
        <th>Descripción</th>
        <th class="right">Cant.</th>
        <th class="right">Precio</th>
        <th class="right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $suma = 0.0;
      foreach ($detalle as $r):
          $cant = (float)$r['cantidad'];
          $pu   = (float)$r['precio_unit'];
          $sub  = (float)$r['total_renglon'];
          $suma += $sub;
      ?>
      <tr>
        <td><?php echo htmlspecialchars($r['cve_articulo'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['des_articulo'] ?? ''); ?></td>
        <td class="right"><?php echo number_format($cant, 2); ?></td>
        <td class="right"><?php echo number_format($pu, 2); ?></td>
        <td class="right"><?php echo number_format($sub, 2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="right"><strong>Total</strong></td>
        <td class="right"><strong><?php echo number_format($suma, 2); ?></strong></td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

// Mostrar en el navegador (no descargar forzado)
$dompdf->stream('Pedido_'.$pedido['id'].'.pdf', ['Attachment' => false]);
exit;
