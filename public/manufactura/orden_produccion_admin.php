<?php
// ======================================================================
//  ADMINISTRACIÓN DE MANUFACTURA – ORDENES DE PRODUCCIÓN (ADMIN)
//  UI 2025 + DataTable server-side + filtros_assistpro
// ======================================================================

// ---- Stub para DataTables server-side (sin BD, solo evita errores JS) ----
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json; charset=utf-8');

    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => []
    ]);
    exit;
}

require_once __DIR__ . '/../bi/_menu_global.php';
$TITLE = "Administración de Manufactura";
?>

<!-- ================================================== -->
<!-- LIBRERÍAS DE UI -->
<!-- ================================================== -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<!-- Column reorder -->
<link href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>

<style>
    .ap-card-kpi {
        background: #ffffff;
        border-radius: 10px;
        padding: 12px 14px;
        border: 1px solid #d9e2ef;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        min-height: 70px;
    }

    .ap-kpi-value {
        font-size: 20px;
        font-weight: 700;
        color: #0F5AAD;
    }

    .ap-kpi-label {
        font-size: 11px;
        color: #666;
    }

    /* CONTENEDOR DE FILTROS */
    .ap-filters-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #dbe3f0;
        padding: 10px 14px;
        box-shadow: 0 1px 3px rgba(15, 90, 173, 0.06);
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
        border-radius: 6px !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        border: 1px solid #ced4da;
    }

    .ap-filter-input:focus,
    .ap-filter-select:focus {
        border-color: #0F5AAD;
        box-shadow: 0 0 0 0.15rem rgba(15, 90, 173, 0.25);
    }

    /* SELECT2 */
    .select2-container .select2-selection--single {
        height: 32px !important;
        padding: 2px 6px !important;
        font-size: 11px;
        border-radius: 6px !important;
        border: 1px solid #ced4da !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 28px !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #0F5AAD !important;
        box-shadow: 0 0 0 0.15rem rgba(15, 90, 173, 0.25);
    }

    /* Accordion: abierto blanco-azulado, cerrado azul */
    .accordion-button {
        font-size: 13px;
        background-color: #e6f0ff;
        color: #0F5AAD;
    }

    .accordion-button.collapsed {
        background-color: #0F5AAD;
        color: #ffffff;
    }

    .accordion-button.collapsed i {
        color: #ffffff;
    }

    table.dataTable tbody tr {
        font-size: 11px;
    }

    #grid-table thead th {
        font-size: 11px;
        white-space: nowrap;
    }
</style>


<div class="container-fluid mt-3">

    <!-- ENCABEZADO -->
    <div class="row mb-2">
        <div class="col">
            <h4 class="fw-bold mb-0" style="color:#0F5AAD;">
                <i class="fa fa-industry me-1"></i> Administración de Manufactura
            </h4>
            <small class="text-muted">Control y seguimiento de órdenes de trabajo</small>
        </div>
    </div>

    <!-- ============================================================= -->
    <!--                     FILTROS (ARRIBA, ABIERTOS)                -->
    <!-- ============================================================= -->
    <div class="accordion mb-3" id="accordionFiltros">

        <div class="accordion-item">
            <h2 class="accordion-header" id="headingFiltros">
                <button class="accordion-button fw-bold py-2" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFiltros" aria-expanded="true">
                    <i class="fa fa-filter me-2"></i> Filtros de búsqueda
                </button>
            </h2>

            <div id="collapseFiltros" class="accordion-collapse collapse show">
                <div class="accordion-body py-2">

                    <div class="ap-filters-wrapper">

                        <!-- FILA 1: Empresa / Almacén / Proveedor / Status -->
                        <div class="row g-3 mb-2">

                            <div class="col-lg-3 col-md-6">
                                <label class="ap-filter-label">
                                    <i class="fa fa-building me-1"></i>Empresa
                                </label>
                                <select class="form-select form-select-sm ap-filter-select select2" id="empresa">
                                    <option value="">Seleccione empresa...</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="ap-filter-label">
                                    <i class="fa fa-warehouse me-1"></i>Almacén
                                </label>
                                <select class="form-select form-select-sm ap-filter-select select2" id="almacen">
                                    <option value="">Seleccione almacén...</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="ap-filter-label">
                                    <i class="fa fa-user me-1"></i>Proveedor
                                </label>
                                <select class="form-select form-select-sm ap-filter-select select2" id="Proveedr">
                                    <option value="">Seleccione proveedor...</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="ap-filter-label">
                                    <i class="fa fa-traffic-light me-1"></i>Status OT
                                </label>
                                <select class="form-select form-select-sm ap-filter-select select2" id="statusOT">
                                    <option value="P">Pendiente</option>
                                    <option value="I">En Producción</option>
                                    <option value="T">Terminado</option>
                                </select>
                            </div>

                        </div>

                        <!-- FILA 2: Buscar / LP / Fechas -->
                        <div class="row g-3 mb-2">

                            <div class="col-md-3 col-sm-6">
                                <label class="ap-filter-label"><i class="fa fa-search me-1"></i>Buscar</label>
                                <input id="criteriob" type="text"
                                       class="form-control form-control-sm ap-filter-input"
                                       placeholder="Folio, artículo, etc.">
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <label class="ap-filter-label"><i class="fa fa-barcode me-1"></i>Buscar LP</label>
                                <input id="criteriobLP" type="text"
                                       class="form-control form-control-sm ap-filter-input"
                                       placeholder="LP">
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <label class="ap-filter-label"><i class="fa fa-calendar me-1"></i>Fecha inicio</label>
                                <input id="fechai" type="text"
                                       class="form-control form-control-sm ap-filter-input flatpickr">
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <label class="ap-filter-label"><i class="fa fa-calendar me-1"></i>Fecha fin</label>
                                <input id="fechaf" type="text"
                                       class="form-control form-control-sm ap-filter-input flatpickr">
                            </div>

                        </div>

                        <!-- FILA 3: acciones dentro del filtro -->
                        <div class="row g-2 align-items-center mt-1">

                            <div class="col-md-4 col-sm-6 d-grid mb-1">
                                <button type="button" class="btn btn-primary btn-sm" id="buscarC"
                                        onclick="ReloadGrid();">
                                    <i class="fa fa-search me-1"></i> Aplicar filtros
                                </button>
                            </div>

                            <div class="col-md-4 col-sm-6 d-grid mb-1">
                                <button type="button" class="btn btn-light btn-sm border" id="btnLimpiarFiltros">
                                    <i class="fa fa-eraser me-1"></i> Limpiar filtros
                                </button>
                            </div>

                            <div class="col-md-4 text-md-end text-start mb-1">
                                <a href="#" id="linkExportOtPendientes"
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fa fa-file-excel-o me-1"></i> Exportar OT pendientes
                                </a>
                            </div>

                        </div>

                    </div><!-- /.ap-filters-wrapper -->

                </div>
            </div>
        </div>
    </div>

    <!-- KPIs DEBAJO DE LOS FILTROS -->
    <div class="row mb-3 g-2">

        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="ap-kpi-value" id="kpi_ot_pendientes">0</div>
                <div class="ap-kpi-label">OT pendientes</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="ap-kpi-value" id="kpi_en_produccion">0</div>
                <div class="ap-kpi-label">OT en producción</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="ap-kpi-value" id="kpi_terminadas">0</div>
                <div class="ap-kpi-label">OT terminadas</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="ap-card-kpi">
                <div class="ap-kpi-value" id="kpi_canceladas">0</div>
                <div class="ap-kpi-label">OT canceladas</div>
            </div>
        </div>

    </div>

    <!-- GRILLA -->
    <div class="card">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="grid-table" class="table table-striped table-bordered table-sm mb-0" style="width:100%;">
                    <thead class="table-primary text-center align-middle">
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

</div><!-- container -->


<script>
    // ===================== Flatpickr =====================
    flatpickr(".flatpickr", {
        locale: "es",
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // ===================== Filtros API =====================
    const API_FILTROS = "../api/filtros_assistpro.php";
    let AP_ALMACENES = [];
    let AP_PROVEEDORES = [];

    // Carga inicial de empresas, almacenes y proveedores en una sola llamada
    function cargarFiltrosDesdeApi() {
        $.ajax({
            url: API_FILTROS,
            type: "GET",
            dataType: "json",
            data: {
                secciones: "empresas,almacenes,proveedores"
            }
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                console.error("Error en filtros_assistpro", resp);
                return;
            }

            // ----- EMPRESAS -----
            const $empresa = $("#empresa");
            $empresa.empty().append('<option value="">Seleccione empresa...</option>');
            (resp.empresas || []).forEach(function (e) {
                const id = e.cve_cia || e.Cve_Cia || e.id || "";
                const desc = e.des_cia || e.Des_Cia || e.descripcion || "";
                const clave = e.clave_empresa || e.Clave || "";
                const text = desc ? desc + (clave ? " (" + clave + ")" : "") : clave || id;
                if (id) {
                    $empresa.append(
                        $('<option>', {value: id, text: text})
                    );
                }
            });

            // ----- ALMACENES -----
            AP_ALMACENES = resp.almacenes || [];
            rellenarAlmacenes();

            // ----- PROVEEDORES -----
            AP_PROVEEDORES = resp.proveedores || [];
            rellenarProveedores();

        }).fail(function (err) {
            console.error("Error AJAX filtros_assistpro", err);
        });
    }

    // Rellena almacenes (si hay empresa seleccionada y el JSON lo soporta, filtra)
    function rellenarAlmacenes() {
        const $alm = $("#almacen");
        const empresaSel = $("#empresa").val();

        $alm.empty().append('<option value="">Seleccione almacén...</option>');

        AP_ALMACENES.forEach(function (a) {
            const empAlm = a.Empresa || a.cve_cia || a.Cve_Cia || null;
            const idAlm = a.cve_almac || a.Cve_Almac || a.id || "";
            const descAlm = a.des_almac || a.Des_Almac || a.descripcion || "";

            if (empresaSel && empAlm && empAlm != empresaSel) {
                return;
            }

            const text = descAlm ? descAlm : idAlm;
            if (idAlm || text) {
                $alm.append(
                    $('<option>', {value: idAlm, text: text})
                );
            }
        });

        $alm.trigger("change.select2");
    }

    // Rellena proveedores independientes (c_proveedores)
    function rellenarProveedores() {
        const $prov = $("#Proveedr");
        $prov.empty().append('<option value="">Seleccione proveedor...</option>');

        AP_PROVEEDORES.forEach(function (p) {
            const id =
                p.ID_Proveedor || p.ID_Prov || p.id || "";
            const nombre =
                p.Nombre || p.RazonSocial || p.nombre || "";
            const cve =
                p.cve_proveedor || p.Cve_Prov || p.clave || "";
            const empresa =
                p.Empresa || p.empresa || "";

            let text = nombre;
            if (cve) text = text ? (text + " (" + cve + ")") : cve;
            if (!text && empresa) text = empresa;
            if (!text && id) text = "Proveedor " + id;

            if (id || text) {
                $prov.append(
                    $('<option>', {value: id, text: text})
                );
            }
        });

        $prov.trigger("change.select2");
    }

    // ===================== Utilidades filtros =====================
    function limpiarFiltros() {
        $("#empresa").val("").trigger("change");
        $("#almacen").val("").trigger("change");
        $("#Proveedr").val("").trigger("change");
        $("#statusOT").val("P").trigger("change");
        $("#criteriob").val("");
        $("#criteriobLP").val("");
        $("#fechai").val("");
        $("#fechaf").val("");
    }

    function exportarOtPendientes() {
        alert("Exportar OT pendientes – integrar WS en PASO 2");
    }

    function ReloadGrid() {
        $('#grid-table').DataTable().ajax.reload();
    }

    // ===================== INIT =====================
    $(document).ready(function () {

        $('.select2').select2({
            theme: "bootstrap-5",
            width: '100%'
        });

        cargarFiltrosDesdeApi();

        $("#empresa").on("change", function () {
            rellenarAlmacenes();
        });

        $('#btnLimpiarFiltros').on('click', function () {
            limpiarFiltros();
        });

        $('#grid-table').DataTable({
            pageLength: 25,
            responsive: true,
            scrollX: true,
            scrollY: "420px",
            processing: true,
            serverSide: true,
            colReorder: true,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            ajax: {
                url: "orden_produccion_admin.php?ajax=list",
                type: "POST",
                data: function (d) {
                    d.empresa = $("#empresa").val();
                    d.almacen = $("#almacen").val();
                    d.Proveedor = $("#Proveedr").val();
                    d.statusOT = $("#statusOT").val();
                    d.criterio = $("#criteriob").val();
                    d.criterioLP = $("#criteriobLP").val();
                    d.fechaInicio = $("#fechai").val();
                    d.fechaFin = $("#fechaf").val();
                }
            }
        });
    });
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
