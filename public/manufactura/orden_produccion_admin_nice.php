<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- ========================= -->
<!-- DEPENDENCIAS -->
<!-- ========================= -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
/* ===== ASSISTPRO STYLE ===== */
table.dataTable tbody tr { font-size: 10px; }

.ap-filters-wrapper {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #dbe3f0;
    border-top: 3px solid #0F5AAD;
}

.ap-filter-label {
    font-size: 11px;
    font-weight: 600;
    color: #2f3b52;
    margin-bottom: 3px;
}

.ap-filter-input,
.ap-filter-select {
    font-size: 11px;
    border-radius: 6px;
}

.ap-btn-primary {
    background-color: #0F6AFF;
    color: #fff;
}
</style>

<div class="container-fluid mt-3">

    <!-- ========================= -->
    <!-- ENCABEZADO -->
    <!-- ========================= -->
    <div class="row mb-2">
        <div class="col">
            <h4 class="fw-bold mb-0" style="color:#0F5AAD;">
                <i class="fa fa-industry me-1"></i> Administración de Manufactura
            </h4>
            <div style="font-size:11px;color:#666;">
                Control y seguimiento de órdenes de producción
            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- SECCIÓN 1 – FILTROS -->
    <!-- ========================= -->
    <div class="card mb-3 ap-filters-wrapper">
        <div class="card-body p-3">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-building me-1"></i> Empresa
                    </label>
                    <select id="f_empresa" class="form-select ap-filter-select">
                        <option value="">Seleccione empresa...</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-warehouse me-1"></i> Almacén
                    </label>
                    <select id="f_almacen" class="form-select ap-filter-select">
                        <option value="">Seleccione almacén...</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-toggle-on me-1"></i> Status OT
                    </label>
                    <select id="f_status" class="form-select ap-filter-select">
                        <option value="">Todos</option>
                        <option value="P">Pendiente</option>
                        <option value="I">En producción</option>
                        <option value="T">Terminada</option>
                        <option value="C">Cancelada</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-search me-1"></i> Buscar
                    </label>
                    <input type="text" id="f_buscar"
                           class="form-control ap-filter-input"
                           placeholder="Folio, artículo, etc.">
                </div>

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-barcode me-1"></i> Buscar LP
                    </label>
                    <input type="text" id="f_lp"
                           class="form-control ap-filter-input"
                           placeholder="LP">
                </div>

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-calendar me-1"></i> Fecha inicio
                    </label>
                    <input type="date" id="f_fecha_ini"
                           class="form-control ap-filter-input">
                </div>

                <div class="col-md-3">
                    <label class="ap-filter-label">
                        <i class="fa fa-calendar me-1"></i> Fecha fin
                    </label>
                    <input type="date" id="f_fecha_fin"
                           class="form-control ap-filter-input">
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button id="btnFiltrar"
                            class="btn ap-btn-primary w-100">
                        <i class="fa fa-search"></i> Aplicar filtros
                    </button>
                    <button id="btnLimpiar"
                            class="btn btn-outline-secondary w-100">
                        <i class="fa fa-eraser"></i> Limpiar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================= -->
    <!-- GRID -->
    <!-- ========================= -->
    <div class="card mb-4">
        <div class="card-header py-2">
            <strong>Órdenes de Producción</strong>
        </div>

        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="grid-table"
                       class="table table-striped table-bordered table-hover w-100">
                    <thead>
                    <tr>
                        <th style="width:90px;">Acciones</th>
                        <th>Folio</th>
                        <th>Artículo</th>
                        <th>Lote</th>
                        <th>Cantidad</th>
                        <th>Prod.</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
var tablaOT;

$(document).ready(function () {

    tablaOT = $('#grid-table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        scrollY: '420px',
        pageLength: 25,
        ordering: false,
        searching: false,
        ajax: {
            url: '../api/orden_produccion_admin_data.php',
            type: 'POST',
            data: function (d) {
                d.empresa   = $('#f_empresa').val();
                d.almacen   = $('#f_almacen').val();
                d.status    = $('#f_status').val();
                d.buscar    = $('#f_buscar').val();
                d.lp        = $('#f_lp').val();
                d.fecha_ini = $('#f_fecha_ini').val();
                d.fecha_fin = $('#f_fecha_fin').val();
            }
        },
        columns: [
            { data: 'acciones', orderable: false },
            { data: 'Folio_Pro' },
            { data: 'Cve_Articulo' },
            { data: 'Cve_Lote' },
            { data: 'Cantidad', className: 'text-end' },
            { data: 'Cant_Prod', className: 'text-end' },
            { data: 'Cve_Usuario' },
            { data: 'Fecha' },
            { data: 'Status' }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    $('#btnFiltrar').on('click', function () {
        tablaOT.ajax.reload();
    });

    $('#btnLimpiar').on('click', function () {
        $('#f_empresa, #f_almacen, #f_status').val('');
        $('#f_buscar, #f_lp, #f_fecha_ini, #f_fecha_fin').val('');
        tablaOT.ajax.reload();
    });

});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
