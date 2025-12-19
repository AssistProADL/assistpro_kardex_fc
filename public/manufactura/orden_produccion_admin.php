<?php
// ======================================================================
//  MÓDULO VISUAL: ADMINISTRACIÓN DE ORDENES DE PRODUCCIÓN
//  Ruta: /public/manufactura/orden_produccion_admin.php
// ======================================================================

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <title>Administración de Manufactura</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>

    <link rel="stylesheet"
          href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"/>
    <link rel="stylesheet"
          href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css"/>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"/>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"/>

    <style>
        .ap-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f4e79;
            margin-bottom: .25rem;
        }

        .ap-section-subtitle {
            font-size: .85rem;
            color: #6c757d;
        }

        .ap-kpi-title {
            font-size: .8rem;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: .25rem;
        }

        .ap-kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f4e79;
        }

        .ap-card-kpi {
            border-radius: .75rem;
            border: 1px solid #e5e7eb;
            padding: .75rem .9rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            height: 100%;
        }

        .ap-card-grid {
            border-radius: .75rem;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        }

        .ap-card-grid-header {
            padding: .75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ap-card-grid-body {
            padding: .75rem;
        }

        table.dataTable thead th {
            white-space: nowrap;
        }

        table.dataTable tbody td {
            font-size: 0.80rem;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-3">

    <div class="row mb-2">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <div>
                <div class="ap-section-title">
                    Administración de Manufactura
                </div>
                <div class="ap-section-subtitle">
                    Control y seguimiento de órdenes de trabajo
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12">
            <div class="accordion" id="filtrosAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFiltros">
                        <button class="accordion-button" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseFiltros"
                                aria-expanded="true"
                                aria-controls="collapseFiltros">
                            <i class="fa fa-filter me-2"></i> Filtros de búsqueda
                        </button>
                    </h2>
                    <div id="collapseFiltros"
                         class="accordion-collapse collapse show"
                         aria-labelledby="headingFiltros"
                         data-bs-parent="#filtrosAccordion">
                        <div class="accordion-body">

                            <div class="row g-2 align-items-end mb-2">

                                <div class="col-md-3 col-sm-6">
                                    <label for="empresa" class="form-label mb-1">
                                        Empresa
                                    </label>
                                    <select name="empresa" id="empresa"
                                            class="form-select form-select-sm select2">
                                        <option value="">Seleccione empresa...</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label for="almacen" class="form-label mb-1">
                                        Almacén
                                    </label>
                                    <select name="almacen" id="almacen"
                                            class="form-select form-select-sm select2">
                                        <option value="">Seleccione almacén...</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label for="Proveedr" class="form-label mb-1">
                                        Proveedor
                                    </label>
                                    <select name="Proveedr" id="Proveedr"
                                            class="form-select form-select-sm select2">
                                        <option value="">Seleccione proveedor...</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label for="statusOT" class="form-label mb-1">
                                        Status OT
                                    </label>
                                    <select name="statusOT" id="statusOT"
                                            class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                        <option value="P">Pendiente</option>
                                        <option value="I">En Producción</option>
                                        <option value="T">Terminada</option>
                                        <option value="C">Cancelada</option>
                                    </select>
                                </div>

                            </div>

                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-3 col-sm-6">
                                    <label for="criteriob" class="form-label mb-1">
                                        Buscar
                                    </label>
                                    <input type="text" id="criteriob"
                                           class="form-control form-control-sm"
                                           placeholder="Folio, artículo, etc.">
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label for="criteriobLP" class="form-label mb-1">
                                        Buscar LP
                                    </label>
                                    <input type="text" id="criteriobLP"
                                           class="form-control form-control-sm"
                                           placeholder="LP, referencia...">
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label for="fechai" class="form-label mb-1">
                                        Fecha inicio
                                    </label>
                                    <input type="text" id="fechai"
                                           class="form-control form-control-sm flatpickr"
                                           placeholder="YYYY-MM-DD">
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <label for="fechaf" class="form-label mb-1">
                                        Fecha fin
                                    </label>
                                    <input type="text" id="fechaf"
                                           class="form-control form-control-sm flatpickr"
                                           placeholder="YYYY-MM-DD">
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-4 col-sm-6 d-grid mb-1">
                                    <button type="button" class="btn btn-primary btn-sm"
                                            onclick="ReloadGrid();">
                                        <i class="fa fa-search me-1"></i> Aplicar filtros
                                    </button>
                                </div>

                                <div class="col-md-4 col-sm-6 d-grid mb-1">
                                    <button type="button" id="btnLimpiarFiltros"
                                            class="btn btn-light btn-sm border">
                                        <i class="fa fa-eraser me-1"></i> Limpiar filtros
                                    </button>
                                </div>

                                <div class="col-md-4 text-md-end text-start mb-1">
                                    <button type="button" id="btnExportarPendientes"
                                            class="btn btn-outline-secondary btn-sm">
                                        <i class="fa fa-file-excel-o me-1"></i> Exportar OT pendientes
                                    </button>
                                </div>
                            </div>

                        </div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3 g-2">
        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="ap-kpi-title">OT pendientes</div>
                        <div class="ap-kpi-value" id="kpi_ot_pendientes">0</div>
                    </div>
                    <i class="fa fa-clock-o"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="ap-kpi-title">OT en producción</div>
                        <div class="ap-kpi-value" id="kpi_en_produccion">0</div>
                    </div>
                    <i class="fa fa-industry"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="ap-kpi-title">OT terminadas</div>
                        <div class="ap-kpi-value" id="kpi_terminadas">0</div>
                    </div>
                    <i class="fa fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="ap-kpi-title">OT canceladas</div>
                        <div class="ap-kpi-value" id="kpi_canceladas">0</div>
                    </div>
                    <i class="fa fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="ap-card-grid">
                <div class="ap-card-grid-header">
                    <div class="fw-semibold text-secondary">
                        Órdenes de Producción
                    </div>
                </div>
                <div class="ap-card-grid-body">
                    <div class="table-responsive">
                        <table id="grid-table"
                               class="table table-striped table-bordered table-hover w-100">
                            <thead class="table-light">
                            <tr>
                                <th>Acciones</th>
                                <th>Fecha OT</th>
                                <th>Hora OT</th>
                                <th>Folio OT</th>
                                <th>Pedido</th>
                                <th>Clave Artículo</th>
                                <th>Descripción</th>
                                <th>Lote</th>
                                <th>Caducidad</th>
                                <th>Cantidad</th>
                                <th>Cantidad Producida</th>
                                <th>Usuario</th>
                                <th>Fecha Compromiso</th>
                                <th>Status</th>
                                <th>Fecha Inicio</th>
                                <th>Hora Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Hora Fin</th>
                                <th>Proveedor</th>
                                <th>Status OT</th>
                                <th>Almacén</th>
                                <th>Zona Almacén</th>
                                <th>Traslado</th>
                                <th>Área Prod.</th>
                                <th>Palletizado</th>
                                <th>Documentos</th>
                                <th>Tipo OT</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    // IMPORTANTE: Asegúrate de que esta ruta sea correcta según dónde esté este archivo
    // Si este archivo está en /public/manufactura/ y la api en /public/api/, entonces "../api/" está bien.
    const API_FILTROS     = "../api/filtros_assistpro.php";
    const API_MANUFACTURA = "../api/api_manufactura.php";

    let AP_PROVEEDORES = [];
    let tablaOT        = null;

    flatpickr(".flatpickr", {
        locale: "es",
        dateFormat: "Y-m-d",
        allowInput: true
    });

    function getSection(resp, key) {
        if (!resp) return [];
        if (resp[key] && Array.isArray(resp[key])) return resp[key];
        if (resp.data && resp.data[key] && Array.isArray(resp.data[key])) return resp.data[key];
        return [];
    }

    function cargarFiltrosDesdeApi() {
        $.ajax({
            url: API_FILTROS,
            type: "GET",
            dataType: "json",
            data: { secciones: "empresas,proveedores" }
        }).done(function(resp){
            const empresas    = getSection(resp, 'empresas');
            const proveedores = getSection(resp, 'proveedores');

            const $empresa = $("#empresa");
            $empresa.empty().append('<option value="">Seleccione empresa...</option>');
            empresas.forEach(function(e){
                const id    = e.cve_cia || e.Cve_Cia || e.id || e.Empresa || e.empresa_id;
                const desc  = e.des_cia || e.Des_cia || e.Des_Cia || e.razon_social || e.nombre || e.Nombre;
                const clave = e.clave_empresa || e.ClaveEmpresa || e.clave || e.Clave;
                if (!id) return;
                let text = desc || '';
                if (clave) text = text ? text + ' (' + clave + ')' : clave;
                $empresa.append($("<option>", { value: id, text: text }));
            });

            AP_PROVEEDORES = proveedores;
            rellenarProveedores();
        }).fail(function(err){
            console.error("Error filtros empresas/proveedores", err);
            // Si falla silenciosamente, revisa la consola, pero no bloquea la UI completa
        });
    }
  });
}

    function cargarAlmacenesPorEmpresa() {
        const empresaSel = $("#empresa").val();
        const $alm       = $("#almacen");
        $alm.empty().append('<option value="">Seleccione almacén...</option>');
        if (!empresaSel) {
            $alm.trigger("change.select2");
            return;
        }
        $.ajax({
            url: API_FILTROS,
            type: "POST",
            dataType: "json",
            data: { secciones: "almacenes", empresa: empresaSel }
        }).done(function(resp){
            const almacenes = getSection(resp, 'almacenes');
            almacenes.forEach(function(a){
                const value = a.clave_almacen || a.cve_almac || a.clave || a.id;
                const text  = a.des_almac     || a.nombre   || a.descripcion || value;
                if (!value) return;
                $alm.append($("<option>", { value: value, text: text }));
            });
            $alm.trigger("change.select2");
        }).fail(function(err){
            console.error("Error filtros almacenes", err);
        });
    }

    function rellenarProveedores() {
        const $prov = $("#Proveedr");
        $prov.empty().append('<option value="">Seleccione proveedor...</option>');
        AP_PROVEEDORES.forEach(function(p){
            const id    = p.ID_Proveedor || p.Id_Proveedor || p.id || p.ID_Prov;
            const clave = p.cve_proveedor || p.Cve_Prov || p.cve || p.Cve_prov;
            const razon = p.RazonSocial || p.razonsocial || p.Nombre || p.nombre;
            if (!id && !razon && !clave) return;
            let text = razon || '';
            if (clave) text = text ? text + ' (' + clave + ')' : clave;
            if (!text && id) text = "Proveedor " + id;
            $prov.append($("<option>", { value: id, text: text }));
        });
        $prov.trigger("change.select2");
    }

    function limpiarFiltros() {
        $("#empresa").val("").trigger("change.select2");
        $("#almacen").val("").trigger("change.select2");
        $("#Proveedr").val("").trigger("change.select2");
        $("#statusOT").val("");
        $("#criteriob").val("");
        $("#criteriobLP").val("");
        $("#fechai").val("");
        $("#fechaf").val("");
    }

    function exportarPendientes() {
        Swal.fire({
            icon: 'info',
            title: 'Exportar OT pendientes',
            text: 'Función de exportación pendiente de implementar.'
        });
    }

    function ReloadGrid() {
        if (tablaOT) tablaOT.ajax.reload(null, true);
    }

    $(document).ready(function () {
        $('.select2').select2({ theme: "bootstrap-5", width: '100%' });

        cargarFiltrosDesdeApi();

        $("#empresa").on("change", function(){
            cargarAlmacenesPorEmpresa();
            ReloadGrid();
        });

        $("#almacen, #Proveedr, #statusOT").on("change", function(){
            ReloadGrid();
        });

        $("#btnLimpiarFiltros").on("click", function(){
            limpiarFiltros();
            ReloadGrid();
        });

        $("#btnExportarPendientes").on("click", exportarPendientes);

        tablaOT = $('#grid-table').DataTable({
            pageLength: 25,
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            responsive: true,
            scrollX: true,
            scrollY: "420px",
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            searching: false,
            colReorder: true,
            deferRender: true,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            ajax: {
                url: API_MANUFACTURA,
                type: "POST",
                data: function (d) {
                    d.action      = 'list';
                    d.empresa     = $("#empresa").val();
                    d.almacen     = $("#almacen").val();
                    d.Proveedor   = $("#Proveedr").val();
                    d.statusOT    = $("#statusOT").val();
                    d.criterio    = $("#criteriob").val();
                    d.criterioLP  = $("#criteriobLP").val();
                    d.fechaInicio = $("#fechai").val();
                    d.fechaFin    = $("#fechaf").val();
                },
                dataSrc: function (json) {
                    if (!json || json.ok === false) {
                        let msg = (json && json.error) ? json.error : 'Error desconocido en API.';
                        console.error("API Response Error:", json);
                        // No mostramos alerta intrusiva si solo es que no hay datos, pero si hay error explícito sí
                        if(json && json.error) Swal.fire('Aviso', msg, 'warning');
                        return [];
                    }
                    const rows = json.data || [];

                    // Cálculo de KPIs en el cliente (basado en la página actual o resultado filtrado)
                    // Nota: Para KPIs precisos globales, lo ideal es que la API los devuelva en un objeto aparte.
                    // Aquí usamos lo que devuelve la tabla actual.
                    let p = 0, i = 0, t = 0, c = 0;
                    rows.forEach(function(r){
                        const st = (r.statusOt || '').toUpperCase();
                        if (st === 'P') p++;
                        else if (st === 'I') i++;
                        else if (st === 'T') t++;
                        else if (st === 'C') c++;
                    });

                    // Solo actualizamos KPIs si hay datos, o los ponemos en 0 si no.
                    $("#kpi_ot_pendientes").text(p);
                    $("#kpi_en_produccion").text(i);
                    $("#kpi_terminadas").text(t);
                    $("#kpi_canceladas").text(c);

                    return rows;
                },
                error: function (xhr, error, thrown) {
                    console.error("Error AJAX:", xhr.responseText);
                    Swal.fire('Error', 'Error de conexión con el servidor de manufactura.', 'error');
                }
            },
            // IMPORTANTE: defaultContent: "" evita el error de "Requested unknown parameter"
            columns: [
                {
                    data: 'acciones',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    defaultContent: "",
                    render: function(data, type, row) {
                        return '<button class="btn btn-sm btn-info text-white"><i class="fa fa-eye"></i></button>';
                    }
                },
                { data: 'fechaOt', defaultContent: "" },
                { data: 'horaOt', defaultContent: "" },
                { data: 'folioOt', defaultContent: "" },
                { data: 'pedidoFolio', defaultContent: "" },
                { data: 'articuloClave', defaultContent: "" },
                { data: 'articuloDescripcion', defaultContent: "" },
                { data: 'lote', defaultContent: "" },
                { data: 'caducidad', defaultContent: "" },
                { data: 'cantidad',          className: 'text-end', defaultContent: "0" },
                { data: 'cantidadProducida', className: 'text-end', defaultContent: "0" },
                { data: 'usuario', defaultContent: "" },
                { data: 'fechaCompromiso', defaultContent: "" },
                { data: 'status', defaultContent: "" },
                { data: 'fechaInicio', defaultContent: "" },
                { data: 'horaInicio', defaultContent: "" },
                { data: 'fechaFin', defaultContent: "" },
                { data: 'horaFin', defaultContent: "" },
                { data: 'proveedorNombre', defaultContent: "" },
                { data: 'statusOt', defaultContent: "" },
                { data: 'almacenClave', defaultContent: "" },
                { data: 'zonaAlmacen', defaultContent: "" },
                { data: 'traslado', defaultContent: "" },
                { data: 'areaProduccion', defaultContent: "" },
                { data: 'palletizado', defaultContent: "" },
                { data: 'documentos', defaultContent: "" },
                { data: 'tipoOt', defaultContent: "" }
            ]
        });
    });
  });
}

// =============================
// EVENTOS
// =============================

$('#f_empresa').on('change', function(){
  let empresa = this.value;
  $('#f_almacen').val('');
  cargarAlmacenes(empresa);
});

// Inicial
cargarEmpresas();

tablaOT = $('#grid-table').DataTable({
serverSide:true,
processing:true,
scrollX:true,
scrollY:'420px',
pageLength:25,
ordering:false,
ajax:{
url:'../api/orden_produccion_admin_data.php',
type:'POST',
data:function(d){
d.empresa=$('#f_empresa').val();
d.almacen=$('#f_almacen').val();
d.status=$('#f_status').val();
d.buscar=$('#f_buscar').val();
d.lp=$('#f_lp').val();
d.fecha_ini=$('#f_fecha_ini').val();
d.fecha_fin=$('#f_fecha_fin').val();
}
},
columns:[
{data:'acciones',orderable:false},
{data:'Folio_Pro'},
{data:'Cve_Articulo'},
{data:'Cve_Lote'},
{data:'Cantidad'},
{data:'Cant_Prod'},
{data:'Cve_Usuario'},
{data:'Fecha'},
{data:'Status'}
]
});

$('#btnFiltrar').click(()=>tablaOT.ajax.reload());
$('#btnLimpiar').click(()=>{
$('input,select').val('');
tablaOT.ajax.reload();
});

$(document).on('click','.btnVerOT',function(){
let id=$(this).data('id');
$('#tablaDetalleOT').html('<tr><td colspan="5">Cargando...</td></tr>');

$.post('../api/orden_produccion_detalle.php',{id:id},function(resp){
let d=JSON.parse(resp);

$('#d_folio').text(d.ot.Folio_Pro);
$('#d_articulo').text(d.ot.Cve_Articulo);
$('#d_lote').text(d.ot.Cve_Lote);
$('#d_cantidad').text(d.ot.Cantidad);
$('#d_status').text(d.ot.Status);

let h='';
d.detalle.forEach(r=>{
h+=`<tr>
<td>${r.Cve_Articulo}</td>
<td>${r.Cve_Lote}</td>
<td>${r.Cantidad}</td>
<td>${r.Fecha_Prod}</td>
<td>${r.Usr_Armo}</td>
</tr>`;
});
$('#tablaDetalleOT').html(h);
$('#modalDetalleOT').modal('show');
});
});

});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
</body>
</html>
