<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<div class="container-fluid">
<h4>Administración de Inventarios</h4>

<table class="table table-sm table-striped" id="tablaInventarios">
<thead>
<tr>
    <th>Folio</th>
    <th>Tipo</th>
    <th>Estado</th>
    <th>Avance</th>
    <th>Diferencias</th>
    <th>Acción</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>

<script>
fetch('/api/listar_inventarios.php')
.then(r=>r.json())
.then(data=>{
    let html='';
    data.forEach(i=>{
        html+=`
        <tr>
            <td>${i.folio}</td>
            <td>${i.tipo}</td>
            <td>${i.estado}</td>
            <td>${i.avance}%</td>
            <td>${i.diferencias}</td>
            <td>
              <a href="detalle_inventario.php?id=${i.id}" class="btn btn-sm btn-info">
                Ver
              </a>
            </td>
        </tr>`;
    });
    document.querySelector('#tablaInventarios tbody').innerHTML = html;
});
</script>
