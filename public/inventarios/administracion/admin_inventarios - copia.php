<?php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';

$rows = db_all("
SELECT 
 ID_Inventario,
 Nombre,
 Status,
 Inv_Inicial,
 Fecha
FROM th_inventario
ORDER BY ID_Inventario DESC
LIMIT 50
");
?>

<div class="container-fluid mt-3">

<h3>📊 Administración de Inventarios</h3>

<div style="overflow:auto; max-height:600px;">
<table class="table table-sm table-striped table-bordered">
<thead class="table-dark">
<tr style="font-size:10px">
<th>ID</th>
<th>Folio</th>
<th>Tipo</th>
<th>Status</th>
<th>Fecha</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr style="font-size:10px">
<td><?= $r['ID_Inventario'] ?></td>
<td><?= htmlspecialchars($r['Nombre'] ?? '') ?></td>
<td><?= $r['Inv_Inicial'] ? 'INICIAL' : 'FÍSICO' ?></td>
<td><?= $r['Status'] ?></td>
<td><?= $r['Fecha'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
