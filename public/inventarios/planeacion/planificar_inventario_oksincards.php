<?php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Planeación de Inventario</title>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
table.dataTable tbody tr { font-size: 11px; }
</style>
</head>

<body>

<div class="container-fluid mt-4">

<h4 class="mb-3">📦 Planeación de Inventario</h4>

<div class="card mb-3">
<div class="card-body">
<div class="row g-3 align-items-end">

<div class="col-md-4">
<label class="form-label">Empresa</label>
<select id="empresa" class="form-select" disabled></select>
</div>

<div class="col-md-4">
<label class="form-label">Almacén</label>
<select id="almacen" class="form-select">
<option value="">Seleccione almacén</option>
</select>
</div>

<div class="col-md-2">
<button id="btnConsultar" class="btn btn-primary w-100">Consultar BLs</button>
</div>

</div>
</div>
</div>

<div class="card">
<div class="card-body">

<table id="tblUbicaciones" class="display nowrap" style="width:100%">
<thead>
<tr>
<th>BL</th>
<th>Pasillo</th>
<th>Rack</th>
<th>Nivel</th>
<th>Sección</th>
<th>Posición</th>
</tr>
</thead>
<tbody></tbody>
</table>

</div>
</div>

</div>

<script>
let tabla;

$(document).ready(function () {

    // =========================
    // Empresa (1 sola)
    // =========================
    $.getJSON('/assistpro_kardex_fc/public/api/filtros_empresas.php', function(data){
        if (data.length) {
            $('#empresa').append(
                `<option value="${data[0].cve_cia}" selected>${data[0].des_cia}</option>`
            );
        }
    });

    // =========================
    // Almacenes (c_almacenp)
    // =========================
    $.getJSON('/assistpro_kardex_fc/public/api/filtros_almacenes.php', function(data){
        data.forEach(a => {
            $('#almacen').append(
                `<option value="${a.id}">${a.nombre}</option>`
            );
        });
    });

    // =========================
    // DataTable
    // =========================
    tabla = $('#tblUbicaciones').DataTable({
        pageLength: 25,
        scrollX: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        columns: [
            { data: 'bl' },
            { data: 'pasillo' },
            { data: 'rack' },
            { data: 'nivel' },
            { data: 'seccion' },
            { data: 'posicion' }
        ]
    });

    // =========================
    // Consultar BLs
    // =========================
    $('#btnConsultar').on('click', function () {

        const almacenp = $('#almacen').val();
        if (!almacenp) {
            alert('Seleccione un almacén');
            return;
        }

        tabla.clear().draw();

        $.getJSON(
            '/assistpro_kardex_fc/public/api/ubicaciones_por_almacen.php',
            { almacenp_id: almacenp },
            function (data) {
                tabla.rows.add(data).draw();
            }
        );
    });

});
</script>

</body>
</html>
