<?php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';

$inventarios = db_all("
    SELECT ID_Inventario, Nombre, Status, Fecha, Inv_Inicial
    FROM th_inventario
    ORDER BY ID_Inventario DESC
");
?>

<div class="container-fluid mt-3">

<h3>📊 Administración de Inventarios</h3>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Total inventarios</h6>
                <h2><?= count($inventarios) ?></h2>
            </div>
        </div>
    </div>
</div>

<table class="table table-sm table-hover">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Folio</th>
    <th>Tipo</th>
    <th>Status</th>
    <th>Fecha</th>
</tr>
</thead>
<tbody>
<?php foreach ($inventarios as $i): ?>
<tr>
    <td><?= $i['ID_Inventario'] ?></td>
    <td><?= htmlspecialchars($i['Nombre'] ?? '') ?></td>
    <td><?= $i['Inv_Inicial'] ? 'INICIAL' : 'FÍSICO' ?></td>
    <td><?= $i['Status'] ?></td>
    <td><?= $i['Fecha'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
