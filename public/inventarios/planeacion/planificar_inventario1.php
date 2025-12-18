<?php
require_once __DIR__ . '/../../../app/db.php';
include __DIR__ . '/../../bi/_menu_global.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
table.dataTable tbody tr { font-size: 10px; }
.filter-row input {
    width:100%;
    font-size:11px;
    padding:3px;
}
</style>

<div class="container-fluid">
<h4 class="mb-3">📦 Planeación de Inventario</h4>

<div class="row mb-3">
    <div class="col-md-4">
        <label>Empresa</label>
        <select id="empresa" class="form-control"></select>
    </div>
    <div class="col-md-4">
        <label>Almacén</label>
        <select id="almacen" class="form-control"></select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button id="btnConsultar" class="btn btn-primary w-100">
            Consultar BLs
        </button>
    </div>
</div>

<table id="tblUbicaciones" class="display compact" style="width:100%">
<thead>
<tr>
    <th></th>
    <th>BL</th>
    <th>Pasillo</th>
    <th>Rack</th>
    <th>Nivel</th>
    <th>Sección</th>
    <th>Posición</th>
</tr>
<tr class="filter-row">
    <th></th>
    <th><input type="text" placeholder="BL"></th>
    <th><input type="text" placeholder="Pasillo"></th>
    <th><input type="text" placeholder="Rack"></th>
    <th><input type="text" placeholder="Nivel"></th>
    <th><input type="text" placeholder="Sección"></th>
    <th><input type="text" placeholder="Posición"></th>
</tr>
</thead>
</table>
</div>

<script>
let table;

$(document).ready(function(){

/* Empresas */
$.getJSON('../../api/filtros_empresas.php', function(data){
    $('#empresa').append('<option value="">Seleccione...</option>');
    data.forEach(e=>{
        $('#empresa').append(`<option value="${e.cve_cia}">${e.des_cia}</option>`);
    });
});

/* Almacenes */
$('#empresa').change(function(){
    $('#almacen').empty();
    $.getJSON('../../api/filtros_almacenes.php?cve_cia='+this.value, function(data){
        $('#almacen').append('<option value="">Seleccione...</option>');
        data.forEach(a=>{
            $('#almacen').append(`<option value="${a.clave}">${a.nombre}</option>`);
        });
    });
});

/* DataTable base (SIN lógica extra) */
table = $('#tblUbicaciones').DataTable({
    ordering: true,
    searching: true,
    paging: true,
    ajax: null,
    columns: [
        { data:null, orderable:false, render:()=>'<input type="checkbox">' },
        { data:'bl' },
        { data:'pasillo' },
        { data:'rack' },
        { data:'nivel' },
        { data:'seccion' },
        { data:'posicion' }
    ]
});

/* Filtros por columna */
table.columns().every(function(i){
    $('input', $('.filter-row th')[i]).on('keyup change', ()=>{
        table.column(i).search(
            $('input', $('.filter-row th')[i]).val()
        ).draw();
    });
});

/* Consultar BLs (API ORIGINAL) */
$('#btnConsultar').click(function(){
    let alm = $('#almacen').val();
    if(!alm) return alert('Seleccione almacén');

    table.ajax.url(
        '../../api/ubicaciones_por_almacen.php?almacen_clave='+alm
    ).load();
});

});
</script>
