<?php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
table.dataTable tbody tr { font-size: 10px; }
</style>

<div class="container-fluid">

    <h4 class="mb-3">
        📦 Planeación de Inventario
    </h4>

    <div class="row mb-3">
        <div class="col-md-4">
            <label>Empresa</label>
            <input type="text" class="form-control" value="FOAM CREATIONS MEXICO SA DE CV" readonly>
        </div>

        <div class="col-md-4">
            <label>Almacén</label>
            <input type="text" class="form-control" value="ALMACEN PRODUCTO SEMITERMINADO" readonly>
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <button id="btnConsultar" class="btn btn-primary me-2">
                Consultar BLs
            </button>

            <button id="btnGuardarSeleccion" class="btn btn-success">
                Guardar selección
            </button>
        </div>
    </div>

    <table id="tblUbicaciones" class="display nowrap" style="width:100%">
        <thead>
            <tr>
                <th style="width:30px;">
                    <input type="checkbox" id="chkTodos">
                </th>
                <th>BL</th>
                <th>Pasillo</th>
                <th>Rack</th>
                <th>Nivel</th>
                <th>Sección</th>
                <th>Posición</th>
            </tr>
        </thead>
    </table>

</div>

<script>
let tabla = $('#tblUbicaciones').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
        url: '/assistpro_kardex_fc/public/api/ubicaciones_por_almacen.php',
        data: function (d) {
            d.almacen = 'ALMACEN PRODUCTO SEMITERMINADO';
        }
    },
    columns: [
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                return `<input type="checkbox" class="chkBL" value="${row.bl}">`;
            }
        },
        { data: 'bl' },
        { data: 'pasillo' },
        { data: 'rack' },
        { data: 'nivel' },
        { data: 'seccion' },
        { data: 'posicion' }
    ],
    pageLength: 25,
    scrollX: true
});

$('#btnConsultar').on('click', function () {
    tabla.ajax.reload();
});

$(document).on('change', '#chkTodos', function () {
    $('.chkBL').prop('checked', this.checked);
});

$('#btnGuardarSeleccion').on('click', function () {
    let seleccionados = [];

    $('.chkBL:checked').each(function () {
        seleccionados.push($(this).val());
    });

    if (seleccionados.length === 0) {
        alert('Selecciona al menos un BL');
        return;
    }

    console.log('BLs seleccionados:', seleccionados);

    alert(
        'BLs seleccionados:\n\n' +
        seleccionados.join(', ')
    );

    // 🔒 No se envía a backend (tal como pediste)
});
</script>
