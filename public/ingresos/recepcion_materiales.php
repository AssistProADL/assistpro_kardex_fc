<?php
// public/ingresos/recepcion_materiales.php

require_once __DIR__ . '/../bi/_menu_global.php';

// Cargar órdenes de compra (aduana) desde BD
$ordenesOC = [];
try {
    $ordenesOC = db_all("
        SELECT 
            a.ID_Aduana,
            a.num_pedimento,
            a.Factura,
            a.Cve_Almac,
            a.ID_Proveedor,
            p.Nombre AS proveedor_nombre
        FROM th_aduana a
        LEFT JOIN c_proveedores p ON p.ID_Proveedor = a.ID_Proveedor
        WHERE COALESCE(a.Activo,1) = 1
        ORDER BY a.ID_Aduana DESC
        LIMIT 500
    ");
} catch (Throwable $e) {
    $ordenesOC = [];
}

$TITLE = 'Recepción de Materiales';
?>

<!-- Dependencias mínimas (por si el template no las carga) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
    .ap-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        border: 1px solid #e1e5eb;
        margin-bottom: 15px;
    }
    .ap-card-header {
        padding: 8px 12px;
        border-bottom: 1px solid #e1e5eb;
        font-size: 13px;
        font-weight: 600;
        color: #1f2933;
        background: #f7f9fc;
    }
    .ap-card-body {
        padding: 10px 12px;
        font-size: 11px;
    }
    .ap-section-title {
        font-size: 11px;
        font-weight: 700;
        color: #1f2933;
        margin-bottom: 4px;
        border-left: 3px solid #2563eb;
        padding-left: 6px;
    }
    .ap-label {
        font-size: 11px;
        font-weight: 600;
        color: #4b5563;
    }
    .ap-form-control {
        font-size: 11px;
        padding: 3px 6px;
        height: 24px;
    }
    .ap-form-control[readonly] {
        background-color: #f9fafb;
    }
    .ap-small {
        font-size: 11px;
    }
    .ap-table-sm th,
    .ap-table-sm td {
        padding: 3px 4px !important;
        font-size: 11px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .ap-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 999px;
    }
    .ap-badge-oc { background: #eff6ff; color: #1d4ed8; }
    .ap-badge-rl { background: #ecfdf5; color: #047857; }
    .ap-badge-cross { background: #fef3c7; color: #92400e; }

    .ap-btn-xs {
        padding: 2px 6px;
        font-size: 10px;
        line-height: 1.2;
    }
    .ap-btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    .ap-btn-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }
    .ap-btn-secondary {
        background: #ffffff;
        border-color: #d1d5db;
        color: #374151;
    }
    .ap-btn-secondary:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }
    .pill-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
    }
    .pill-green { background: #dcfce7; color: #166534; }
    .pill-yellow { background: #fef9c3; color: #854d0e; }
    .pill-red { background: #fee2e2; color: #991b1b; }

    .ap-kpi-label {
        font-size: 10px;
        color: #6b7280;
    }
    .ap-kpi-value {
        font-size: 12px;
        font-weight: 700;
        color: #111827;
    }

    #debugPanel { margin-top: 15px; }
    #debugLog {
        width: 100%;
        height: 160px;
        font-size: 10px;
        font-family: Consolas, monospace;
        background: #0b1020;
        color: #e5e7eb;
        border-radius: 4px;
        border: 1px solid #374151;
        padding: 6px;
        resize: vertical;
        white-space: pre;
        overflow: auto;
    }
    .d-none { display: none !important; }
</style>

<div class="container-fluid">

    <!-- ========== ENCABEZADO ========== -->
    <div class="ap-card">
        <div class="ap-card-header">
            Recepción de Materiales
        </div>
        <div class="ap-card-body">

            <!-- Tipo recepción -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <div class="ap-label mb-1">Tipo</div>
                    <div class="form-check form-check-inline ap-small">
                        <input class="form-check-input" type="radio" name="tipo_recepcion" id="tipo_oc" value="OC" checked>
                        <label class="form-check-label" for="tipo_oc">Orden de Compra</label>
                    </div>
                    <div class="form-check form-check-inline ap-small">
                        <input class="form-check-input" type="radio" name="tipo_recepcion" id="tipo_libre" value="LIBRE">
                        <label class="form-check-label" for="tipo_libre">Recepción Libre</label>
                    </div>
                    <div class="form-check form-check-inline ap-small">
                        <input class="form-check-input" type="radio" name="tipo_recepcion" id="tipo_cross" value="CROSS">
                        <label class="form-check-label" for="tipo_cross">Cross Docking</label>
                    </div>
                </div>
            </div>

            <!-- Datos generales -->
            <div class="ap-section-title">Datos generales</div>
            <div class="row mb-2">
                <div class="col-md-4">
                    <label class="ap-label" for="cboEmpresa">Empresa</label>
                    <select id="cboEmpresa" class="form-control ap-form-control">
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ap-label" for="cboAlmacen">Almacén</label>
                    <select id="cboAlmacen" class="form-control ap-form-control">
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ap-label" for="cboZonaRecepcion">Zona de Recepción</label>
                    <select id="cboZonaRecepcion" class="form-control ap-form-control">
                        <option value="">Seleccione una Zona de Recepción</option>
                    </select>
                </div>
            </div>

            <!-- Datos comerciales -->
            <div class="ap-section-title mt-2">Datos comerciales</div>
            <div class="row">
                <!-- Proveedor -->
                <div class="col-md-4">
                    <label class="ap-label" for="txtProveedor">Proveedor</label>
                    <!-- Para OC/CROSS: texto solo lectura -->
                    <input type="text" id="txtProveedor" class="form-control ap-form-control" readonly>
                    <!-- Para Recepción Libre: combo -->
                    <select id="cboProveedor" class="form-control ap-form-control d-none">
                        <option value="">Seleccione proveedor</option>
                    </select>
                </div>

                <!-- OC -->
                <div class="col-md-4">
                    <label class="ap-label" for="cboOC">Número de Orden de Compra</label>
                    <select id="cboOC" class="form-control ap-form-control">
                        <option value="">Seleccione una OC</option>
                    </select>
                </div>

                <!-- Folio RL -->
                <div class="col-md-4">
                    <label class="ap-label" for="txtFolioRL">Folio Recepción RL</label>
                    <input type="text" id="txtFolioRL" class="form-control ap-form-control"
                           placeholder="Sólo Recepción Libre" readonly>
                </div>
            </div>

            <div class="row">
                <!-- Folio Cross -->
                <div class="col-md-4">
                    <label class="ap-label" for="txtFolioCross">Folio Cross Docking</label>
                    <input type="text" id="txtFolioCross" class="form-control ap-form-control"
                           placeholder="Sólo Cross Docking" readonly>
                </div>
                <!-- Folio TMP -->
                <div class="col-md-4">
                    <label class="ap-label" for="txtFolioTmp">Folio Temporal Recepción</label>
                    <input type="text" id="txtFolioTmp" class="form-control ap-form-control" readonly>
                </div>
                <!-- Factura -->
                <div class="col-md-4">
                    <label class="ap-label" for="txtFactura">Factura / Documento</label>
                    <input type="text" id="txtFactura" class="form-control ap-form-control"
                           placeholder="Factura o documento comercial">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="ap-label" for="cboProyecto">Proyecto / Centro de costo</label>
                    <select id="cboProyecto" class="form-control ap-form-control">
                        <option value="">No aplica</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ap-label" for="txtUsuario">Usuario</label>
                    <input type="text" id="txtUsuario" class="form-control ap-form-control" readonly
                           value="<?php echo isset($_SESSION['usuario_nombre'])
                               ? htmlspecialchars($_SESSION['usuario_nombre'])
                               : '(wmsmaster) Usuario WMS Admin'; ?>">
                </div>
                <div class="col-md-4">
                    <label class="ap-label">Estado Recepción</label><br>
                    <span class="ap-badge ap-badge-oc" id="lblTipoSeleccionado">OC</span>
                    <span class="ap-badge" style="background:#fef9c3;color:#92400e;">Pendiente de guardar</span>
                </div>
            </div>

        </div>
    </div>

    <!-- ========== CAPTURA LÍNEAS ========== -->
    <div class="ap-card">
        <div class="ap-card-header">
            Captura de líneas de recepción
        </div>
        <div class="ap-card-body">

            <div class="row mb-2">
                <div class="col-md-2">
                    <div class="ap-kpi-label">Líneas capturadas</div>
                    <div class="ap-kpi-value" id="kpiLineas">0</div>
                </div>
                <div class="col-md-2">
                    <div class="ap-kpi-label">Piezas recibidas</div>
                    <div class="ap-kpi-value" id="kpiPiezas">0</div>
                </div>
                <div class="col-md-2">
                    <div class="ap-kpi-label">Contenedores</div>
                    <div class="ap-kpi-value" id="kpiContenedores">0</div>
                </div>
                <div class="col-md-2">
                    <div class="ap-kpi-label">Pallets</div>
                    <div class="ap-kpi-value" id="kpiPallets">0</div>
                </div>
                <div class="col-md-4 text-end">
                    <button id="btnGuardarRecepcion" class="btn btn-sm ap-btn-primary ap-btn-xs">
                        <i class="fa fa-save"></i> Guardar recepción
                    </button>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3">
                    <label class="ap-label" for="cboArticulo">Artículo</label>
                    <select id="cboArticulo" class="form-control ap-form-control">
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="ap-label" for="txtDescArticulo">Descripción</label>
                    <input type="text" id="txtDescArticulo" class="form-control ap-form-control" readonly>
                </div>
                <div class="col-md-1">
                    <label class="ap-label" for="cboUM">UM</label>
                    <select id="cboUM" class="form-control ap-form-control">
                        <option value="PZA">PZA</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="txtLoteSerie">Lote / Serie</label>
                    <input type="text" id="txtLoteSerie" class="form-control ap-form-control">
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="txtCaducidad">Caducidad</label>
                    <input type="date" id="txtCaducidad" class="form-control ap-form-control">
                </div>
                <div class="col-md-1">
                    <label class="ap-label" for="txtSolicitada">Solicitada</label>
                    <input type="number" id="txtSolicitada" class="form-control ap-form-control" value="0" readonly>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-2">
                    <label class="ap-label" for="txtRecibidaAcum">Recibida acum.</label>
                    <input type="number" id="txtRecibidaAcum" class="form-control ap-form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="txtCantidad">Cantidad a capturar</label>
                    <input type="number" id="txtCantidad" class="form-control ap-form-control" value="0">
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="txtCostoTotal">Costo Total</label>
                    <input type="number" id="txtCostoTotal" class="form-control ap-form-control"
                           value="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="txtContenedor">Contenedor (CT)</label>
                    <input type="text" id="txtContenedor" class="form-control ap-form-control"
                           placeholder="LP/CT">
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="txtLPContenedor">LP CT</label>
                    <input type="text" id="txtLPContenedor" class="form-control ap-form-control"
                           placeholder="LP asociado">
                </div>
                <div class="col-md-2">
                    <label class="ap-label" for="cboPallet">Pallet (LP)</label>
                    <select id="cboPallet" class="form-control ap-form-control">
                        <option value="">Sin pallet</option>
                    </select>
                </div>
            </div>

            <div class="row mb-1">
                <div class="col-md-3">
                    <div class="form-check ap-small">
                        <input class="form-check-input" type="checkbox" id="chkGenerarContenedor" checked>
                        <label class="form-check-label" for="chkGenerarContenedor">
                            Generar LP para contenedor
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check ap-small">
                        <input class="form-check-input" type="checkbox" id="chkGenerarPallet">
                        <label class="form-check-label" for="chkGenerarPallet">
                            Generar LP para pallet
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check ap-small">
                        <input class="form-check-input" type="checkbox" id="chkCerrarContenedor">
                        <label class="form-check-label" for="chkCerrarContenedor">
                            Cerrar contenedor al capturar
                        </label>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button id="btnAgregarLinea" class="btn btn-sm ap-btn-secondary ap-btn-xs">
                        <i class="fa fa-plus"></i> Agregar línea
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ========== DETALLE ========== -->
    <div class="ap-card">
        <div class="ap-card-header">
            Detalle de líneas capturadas
        </div>
        <div class="ap-card-body">
            <div class="table-responsive">
                <table id="tblRecibidos"
                       class="table table-striped table-bordered ap-table-sm" style="width:100%;">
                    <thead>
                    <tr>
                        <th>Estatus</th>
                        <th>Usuario</th>
                        <th>Clave</th>
                        <th>Descripción</th>
                        <th>UM</th>
                        <th>Lote/Serie</th>
                        <th>Caducidad</th>
                        <th>Solicitada</th>
                        <th>Recibida acum.</th>
                        <th>Captura</th>
                        <th>Saldo</th>
                        <th>Costo</th>
                        <th>Costo Unit.</th>
                        <th>Moneda</th>
                        <th>CT</th>
                        <th>LP CT</th>
                        <th>Pallet</th>
                        <th>Fecha/Hora</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== DEBUG ========== -->
    <div id="debugPanel" class="ap-card">
        <div class="ap-card-header">
            Debug Recepción de Materiales
            <button type="button" id="btnLimpiarDebug"
                    class="btn btn-xs btn-secondary ap-btn-xs float-end">
                Limpiar
            </button>
        </div>
        <div class="ap-card-body">
            <textarea id="debugLog" readonly></textarea>
        </div>
    </div>

</div>

<script>
var DEBUG_REC_MAT = true;

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btnLimpiarDebug');
    if (btn) {
        btn.addEventListener('click', function () {
            var log = document.getElementById('debugLog');
            if (log) log.value = '';
        });
    }
});

function dbgRecMat(msg, data) {
    if (!DEBUG_REC_MAT) return;
    try { console.log('[REC_MAT]', msg, data ?? ''); } catch (e) {}

    var log = document.getElementById('debugLog');
    if (!log) return;
    var ts = new Date().toLocaleTimeString();
    var line = '[' + ts + '] ' + msg;
    if (typeof data !== 'undefined') {
        try {
            line += ' -> ' + (typeof data === 'string'
                ? data
                : JSON.stringify(data));
        } catch (e) {
            line += ' -> [object]';
        }
    }
    log.value += line + "\n";
    log.scrollTop = log.scrollHeight;
}

// =================== DATA GLOBAL ===================

var ORDENES_OC_ADUANA = <?php echo json_encode($ordenesOC, JSON_UNESCAPED_UNICODE); ?>;
if (!Array.isArray(ORDENES_OC_ADUANA)) {
    ORDENES_OC_ADUANA = [];
}
dbgRecMat('ORDENES_OC_ADUANA length', ORDENES_OC_ADUANA.length);

var tablaRecibidos = null;
const API_FILTROS_URL = '../api/filtros_assistpro.php';
var filtrosAssistPro = null;

// Mapa clave producto -> objeto completo (para banderas, UM, etc.)
var MAP_PRODUCTOS = {};

$(document).ready(function () {
    dbgRecMat('Inicializando Recepción de Materiales...');
    dbgRecMat('API_FILTROS_URL', API_FILTROS_URL);

    tablaRecibidos = $('#tblRecibidos').DataTable({
        paging: true,
        pageLength: 25,
        lengthChange: false,
        searching: false,
        ordering: false,
        info: true,
        scrollX: true,
        scrollY: '220px',
        autoWidth: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
        }
    });

    cargarFiltrosAssistPro();

    $('#cboAlmacen').on('change', function () {
        dbgRecMat('cboAlmacen change', $('#cboAlmacen').val());
        cargarZonasRecepcionDesdeFiltros();
        cargarOrdenesCompraPorAlmacen();
    });

    $('input[name="tipo_recepcion"]').on('change', function () {
        var tipo = $('input[name="tipo_recepcion"]:checked').val();
        dbgRecMat('tipo_recepcion change', tipo);
        actualizarTipoRecepcionUI();
    });

    $('#cboArticulo').on('change', function () {
        onArticuloChange();
    });

    $('#cboOC').on('change', function () {
        onOrdenCompraChange();
    });

    $('#btnAgregarLinea').on('click', function (e) {
        e.preventDefault();
        agregarLineaRecepcion();
    });

    $('#btnGuardarRecepcion').on('click', function () {
        guardarRecepcion();
    });

    actualizarTipoRecepcionUI();
});

// ============= filtros_assistpro API =============

function cargarFiltrosAssistPro() {
    dbgRecMat('Llamando a filtros_assistpro...', { url: API_FILTROS_URL });

    $.ajax({
        url: API_FILTROS_URL,
        method: 'GET',
        dataType: 'json',
        success: function (resp) {
            dbgRecMat('Respuesta cruda filtros_assistpro', resp);

            if (!resp) {
                dbgRecMat('Error: respuesta vacía de filtros_assistpro');
                alert('filtros_assistpro devolvió respuesta vacía');
                return;
            }

            if (typeof resp.ok !== 'undefined' && resp.ok === false) {
                dbgRecMat('Error lógico en filtros_assistpro',
                    resp.error || '(sin detalle)');
                alert('Error en filtros_assistpro: ' + (resp.error || 'sin detalle'));
                return;
            }

            var payload = resp.data || resp;
            filtrosAssistPro = payload || {};
            dbgRecMat('Payload filtrosAssistPro procesado', filtrosAssistPro);

            dbgRecMat('Conteos iniciales', {
                empresas: Array.isArray(filtrosAssistPro.empresas) ? filtrosAssistPro.empresas.length : 0,
                almacenes: Array.isArray(filtrosAssistPro.almacenes) ? filtrosAssistPro.almacenes.length : 0,
                zonas_recep: Array.isArray(filtrosAssistPro.zonas_recep) ? filtrosAssistPro.zonas_recep.length : 0,
                productos: Array.isArray(filtrosAssistPro.productos) ? filtrosAssistPro.productos.length : 0,
                proveedores: Array.isArray(filtrosAssistPro.proveedores) ? filtrosAssistPro.proveedores.length : 0
            });

            cargarEmpresasDesdeFiltros();
            cargarAlmacenesDesdeFiltros();
            cargarZonasRecepcionDesdeFiltros();
            cargarArticulosDesdeFiltros();
            cargarProveedoresDesdeFiltros(); // RL
            cargarOrdenesCompraPorAlmacen();
        },
        error: function (xhr, status, error) {
            dbgRecMat('Error AJAX filtros_assistpro', {
                status: status,
                error: error,
                responseText: xhr && xhr.responseText ? xhr.responseText : ''
            });
            alert('No se pudo cargar filtros_assistpro.php\nRevisa consola y panel debug.');
        }
    });
}

function cargarEmpresasDesdeFiltros() {
    var $cbo = $('#cboEmpresa');
    $cbo.empty().append('<option value="">Seleccione</option>');

    if (!filtrosAssistPro || !Array.isArray(filtrosAssistPro.empresas)) {
        dbgRecMat('cargarEmpresasDesdeFiltros: sin empresas');
        return;
    }

    filtrosAssistPro.empresas.forEach(function (row, idx) {
        var val = row.empresa_id || row.cve_cia || row.clave_empresa || '';
        var txt = row.des_cia || row.nombre || row.descripcion || '';
        if (row.clave_empresa) txt += ' (' + row.clave_empresa + ')';
        $cbo.append($('<option>', { value: val, text: txt }));
        if (idx < 3) dbgRecMat('Empresa[' + idx + ']', row);
    });

    dbgRecMat('Empresas cargadas', { count: filtrosAssistPro.empresas.length });
}

function cargarAlmacenesDesdeFiltros() {
    var $cbo = $('#cboAlmacen');
    $cbo.empty().append('<option value="">[Seleccione un almacén]</option>';

    if (!filtrosAssistPro || !Array.isArray(filtrosAssistPro.almacenes)) {
        dbgRecMat('cargarAlmacenesDesdeFiltros: sin almacenes');
        return;
    }

    filtrosAssistPro.almacenes.forEach(function (row, idx) {
        var cve  = row.cve_almac || row.Cve_Almac || row.clave_almacen || '';
        var des  = row.des_almac || row.nombre || row.descripcion || '';
        var cap  = row.cve_almacp || row.cve_almac_p || row.cap_id || row.id_cap || row.id || '';
        var txt  = '[' + (cve || '') + '] ' + (des || '');

        $cbo.append($('<option>', {
            value: cve,
            text: txt,
            'data-cap': cap
        }));
        if (idx < 3) dbgRecMat('Almacen[' + idx + ']', row);
    });

    dbgRecMat('Almacenes cargados', { count: filtrosAssistPro.almacenes.length });
}

function cargarZonasRecepcionDesdeFiltros() {
    var $cbo = $('#cboZonaRecepcion');
    $cbo.empty().append('<option value="">Seleccione una Zona de Recepción</option>');

    if (!filtrosAssistPro || !Array.isArray(filtrosAssistPro.zonas_recep)) {
        dbgRecMat('cargarZonasRecepcionDesdeFiltros: sin zonas_recep');
        return;
    }

    var almSelVal = $('#cboAlmacen').val();
    var $optSel   = $('#cboAlmacen option:selected');
    var almSelCap = $optSel.data('cap') != null ? String($optSel.data('cap')) : '';

    dbgRecMat('cargarZonasRecepcionDesdeFiltros: almSel', {
        almSelVal: almSelVal,
        almSelCap: almSelCap
    });

    var count = 0;

    filtrosAssistPro.zonas_recep.forEach(function (z, idx) {
        var capZona = (z.cve_almac || z.cve_almacp || '').toString();
        var clave   = z.cve_ubicacion || '';
        var desc    = z.descripcion || z.desc_ubicacion || '';

        // 1) Intento por id numérico (cve_almacp)
        if (almSelCap && capZona && capZona !== almSelCap) return;

        // 2) Si no tenemos cap, fallback por coincidencia con código de almacén (WH8 en ZRWH8)
        if (!almSelCap && almSelVal) {
            var code = almSelVal.toString().toUpperCase();
            var claveUp = (clave || '').toUpperCase();
            var descUp  = (desc || '').toUpperCase();
            if (claveUp.indexOf(code) === -1 && descUp.indexOf(code) === -1) {
                return;
            }
        }

        var id  = z.ID_URecepcion || z.id_urecepcion || z.id;
        var txtOpt = (clave ? '[' + clave + '] ' : '') + (desc || '');

        $cbo.append($('<option>', {
            value: id || clave,
            text: txtOpt
        }));

        if (idx < 3) dbgRecMat('ZonaRecep[' + idx + ']', z);
        count++;
    });

    dbgRecMat('Zonas Recepción cargadas (tubicacionesretencion)', {
        almacen: almSelVal,
        almacen_cap: almSelCap,
        count: count
    });
}

function cargarArticulosDesdeFiltros() {
    var $cbo = $('#cboArticulo');
    $cbo.empty().append('<option value="">Seleccione</option>');
    MAP_PRODUCTOS = {};

    if (!filtrosAssistPro) {
        dbgRecMat('cargarArticulosDesdeFiltros: filtrosAssistPro null');
        return;
    }

    var prods = filtrosAssistPro.productos || filtrosAssistPro.articulos || [];
    if (!Array.isArray(prods)) {
        dbgRecMat('cargarArticulosDesdeFiltros: productos/articulos no es array');
        return;
    }

    prods.forEach(function (p, idx) {
        var cve = p.cve_articulo || p.Cve_Articulo || p.codigo || '';
        var des = p.des_articulo || p.descripcion || p.Descripcion || p.nombre || '';
        if (!cve && !des) return;

        var txt = cve ? (cve + ' — ' + des) : des;
        $cbo.append($('<option>', { value: cve, text: txt }));

        MAP_PRODUCTOS[String(cve)] = p;

        if (idx < 3) dbgRecMat('Articulo[' + idx + ']', p);
    });

    dbgRecMat('Artículos cargados', {
        count: prods.length,
        keys_mapa: Object.keys(MAP_PRODUCTOS).length
    });
}

function cargarProveedoresDesdeFiltros() {
    var $cbo = $('#cboProveedor');
    $cbo.empty().append('<option value="">Seleccione proveedor</option>');

    // Proveedores nativos
    if (filtrosAssistPro && Array.isArray(filtrosAssistPro.proveedores) &&
        filtrosAssistPro.proveedores.length > 0) {

        filtrosAssistPro.proveedores.forEach(function (p, idx) {
            var id  = p.ID_Proveedor || p.id_proveedor || p.id;
            var nom = p.Nombre || p.nombre || p.Empresa || '';
            $cbo.append($('<option>', { value: id, text: nom }));
            if (idx < 3) dbgRecMat('Proveedor[' + idx + ']', p);
        });

        dbgRecMat('Proveedores cargados', { count: filtrosAssistPro.proveedores.length });
        return;
    }

    // Fallback: clientes como proveedores RL
    if (filtrosAssistPro && Array.isArray(filtrosAssistPro.clientes)) {
        filtrosAssistPro.clientes.forEach(function (p, idx) {
            var id  = p.id_cliente || p.ID_Cliente || p.id;
            var nom = p.RazonSocial || p.RFC || p.Cve_Clte || '';
            $cbo.append($('<option>', { value: id, text: nom }));
            if (idx < 3) dbgRecMat('ProveedorFallbackCliente[' + idx + ']', p);
        });
        dbgRecMat('Proveedores cargados desde clientes', { count: filtrosAssistPro.clientes.length });
    } else {
        dbgRecMat('cargarProveedoresDesdeFiltros: sin proveedores ni clientes');
    }
}

// ============= Órdenes de compra (aduana) =============

function cargarOrdenesCompraPorAlmacen() {
    var $cbo = $('#cboOC');
    var almClave = $('#cboAlmacen').val();                 // clave alfanumérica WH8, WHCR...
    var almTexto = $('#cboAlmacen option:selected').text();
    $cbo.empty().append('<option value="">Seleccione una OC</option>');

    if (!Array.isArray(ORDENES_OC_ADUANA)) {
        dbgRecMat('cargarOrdenesCompraPorAlmacen: ORDENES_OC_ADUANA no es array');
        return;
    }

    var claveSel = (almClave || '').toString().trim().toUpperCase();
    var total = 0;
    var filtradas = 0;

    ORDENES_OC_ADUANA.forEach(function (oc, idx) {
        total++;

        var almOC = (oc.Cve_Almac || oc.cve_almac || '').toString().trim().toUpperCase();

        // Si hay almacén seleccionado, filtrar por la clave alfanumérica
        if (claveSel && almOC && almOC !== claveSel) {
            return;
        }

        var id   = oc.ID_Aduana;
        var ped  = oc.num_pedimento || oc.Pedimento || oc.num_orden || '';
        var fac  = oc.Factura || '';
        var prov = oc.proveedor_nombre || oc.proveedor || '';
        var txt  = ped + ' — ' + fac + (prov ? ' — ' + prov : '') +
                   (almOC ? ' [' + almOC + ']' : '');

        $cbo.append($('<option>', {
            value: id,
            text: txt,
            'data-proveedor': prov,
            'data-almoc': almOC
        }));

        if (idx < 5) {
            dbgRecMat('OC cargada', {
                ID_Aduana: id,
                num_pedimento: ped,
                Factura: fac,
                Cve_Almac: almOC,
                proveedor: prov
            });
        }
        filtradas++;
    });

    dbgRecMat('OCs cargadas en combo', {
        almac_seleccionado_val: almClave,
        almac_seleccionado_txt: almTexto,
        total_bd: total,
        total_combo: filtradas
    });
}

function onOrdenCompraChange() {
    var idSel = $('#cboOC').val();
    if (!idSel) {
        $('#txtProveedor').val('');
        $('#txtFactura').val('');
        return;
    }
    var oc = ORDENES_OC_ADUANA.find(function (x) {
        return String(x.ID_Aduana) === String(idSel);
    });
    if (!oc) {
        dbgRecMat('onOrdenCompraChange: OC no encontrada', idSel);
        return;
    }

    var prov = oc.proveedor_nombre || '';
    var fac  = oc.Factura || '';

    $('#txtProveedor').val(prov);
    if (!$('#txtFactura').val()) {
        $('#txtFactura').val(fac);
    }

    dbgRecMat('OC seleccionada', oc);
}

// ========= PRODUCTO (RL: banderas, UM, etc.) =========

function onArticuloChange() {
    var cve = $('#cboArticulo').val();
    var txt = $('#cboArticulo option:selected').text() || '';
    var parts = txt.split(' — ');
    $('#txtDescArticulo').val(parts[1] || '');

    var prod = MAP_PRODUCTOS[String(cve)] || null;
    configurarCamposProducto(prod);

    dbgRecMat('cboArticulo change', { value: cve, text: txt, prod: prod });
}

function getFlagBool(prod, keys, defaultVal) {
    if (!prod) return defaultVal;
    for (var i = 0; i < keys.length; i++) {
        var k = keys[i];
        if (!Object.prototype.hasOwnProperty.call(prod, k)) continue;
        var v = prod[k];
        if (v === null || typeof v === 'undefined') continue;

        if (typeof v === 'string') {
            v = v.trim().toUpperCase();
            if (v === 'S' || v === 'Y' || v === 'SI') return true;
            if (v === 'N' || v === 'NO') return false;
            if (v === '1') return true;
            if (v === '0') return false;
        } else if (typeof v === 'number') {
            if (v === 1) return true;
            if (v === 0) return false;
        } else if (typeof v === 'boolean') {
            return v;
        }
    }
    return defaultVal;
}

function configurarCamposProducto(prod) {
    // ===== UM =====
    var um = null;
    if (prod) {
        um = prod.UM || prod.um || prod.unidad || prod.Unidad || prod.UnidadMedida || prod.unidad_medida || null;
    }
    if (um) {
        var $cboUM = $('#cboUM');
        var existe = false;
        $cboUM.find('option').each(function () {
            if ($(this).val() === um || $(this).text() === um) {
                existe = true;
            }
        });
        if (!existe) {
            $cboUM.append($('<option>', { value: um, text: um }));
        }
        $cboUM.val(um);
    }

    // ===== Banderas lote / serie / caducidad =====
    var manejaLote = getFlagBool(prod, ['ManejaLote', 'B_Lote'], true);
    var manejaSerie = getFlagBool(prod, ['ManejaSerie', 'B_Serie'], false);
    var manejaCad = getFlagBool(prod, ['ManejaCaducidad', 'B_Caducidad'], false);

    var $txtLoteSerie = $('#txtLoteSerie');
    var $txtCad       = $('#txtCaducidad');

    // Lote / Serie
    if (!manejaLote && !manejaSerie) {
        $txtLoteSerie.prop('readonly', false).attr('placeholder', 'Lote / Serie (libre)');
    } else if (manejaSerie && !manejaLote) {
        $txtLoteSerie.prop('readonly', false).attr('placeholder', 'Serie');
    } else if (manejaLote && !manejaSerie) {
        $txtLoteSerie.prop('readonly', false).attr('placeholder', 'Lote');
    } else {
        $txtLoteSerie.prop('readonly', false).attr('placeholder', 'Lote / Serie');
    }

    // Caducidad
    $txtCad.prop('disabled', !manejaCad);

    dbgRecMat('configurarCamposProducto', {
        um: um,
        manejaLote: manejaLote,
        manejaSerie: manejaSerie,
        manejaCaducidad: manejaCad
    });
}

// ========= UI tipo recepción =========

function actualizarTipoRecepcionUI() {
    var tipo = $('input[name="tipo_recepcion"]:checked').val();
    var lbl = $('#lblTipoSeleccionado');
    lbl.removeClass('ap-badge-oc ap-badge-rl ap-badge-cross');

    $('#cboOC').prop('disabled', true);
    $('#txtFolioRL').prop('readonly', true);
    $('#txtFolioCross').prop('readonly', true);

    if (tipo === 'LIBRE') {
        lbl.text('RL').addClass('ap-badge-rl');
        $('#txtFolioRL').prop('readonly', false);

        // RL: proveedor por combo, OC deshabilitada
        $('#cboProveedor').removeClass('d-none');
        $('#txtProveedor').addClass('d-none');
        $('#cboOC').prop('disabled', true);
    } else {
        if (tipo === 'OC') {
            lbl.text('OC').addClass('ap-badge-oc');
            $('#cboOC').prop('disabled', false);
        } else if (tipo === 'CROSS') {
            lbl.text('CROSS').addClass('ap-badge-cross');
            $('#txtFolioCross').prop('readonly', false);
        }

        // OC/CROSS: proveedor de la OC (texto), RL hidden
        $('#cboProveedor').addClass('d-none');
        $('#txtProveedor').removeClass('d-none');
    }

    dbgRecMat('actualizarTipoRecepcionUI', tipo);
}

// ========= LÓGICA DE LÍNEAS =========

function agregarLineaRecepcion() {
    var tipoRecep    = $('input[name="tipo_recepcion"]:checked').val();
    var empresaId    = $('#cboEmpresa').val();
    var almacId      = $('#cboAlmacen').val();
    var zonaRec      = $('#cboZonaRecepcion').val();

    if (!empresaId || !almacId || !zonaRec) {
        alert('Debe seleccionar Empresa, Almacén y Zona de Recepción.');
        dbgRecMat('Validación agregarLinea: faltan datos', {
            empresaId, almacId, zonaRec
        });
        return;
    }

    var usuario      = $('#txtUsuario').val();
    var articulo     = $('#cboArticulo').val();
    var desc         = $('#txtDescArticulo').val()
        || $('#cboArticulo option:selected').text();
    var uom          = $('#cboUM option:selected').text();
    var loteSerie    = $('#txtLoteSerie').val();
    var caducidad    = $('#txtCaducidad').prop('disabled') ? '' : $('#txtCaducidad').val();
    var cantidadCap  = $('#txtCantidad').val();
    var solicitada   = $('#txtSolicitada').val();
    var recibidaAcum = $('#txtRecibidaAcum').val();
    var costo        = $('#txtCostoTotal').val();
    var contenedor   = $('#txtContenedor').val();
    var lpContenedor = $('#txtLPContenedor').val();
    var palletTxt    = $('#cboPallet option:selected').text();
    var moneda       = 'MXN';

    if (!articulo) {
        alert('Debe seleccionar un artículo.');
        dbgRecMat('Validación agregarLinea: sin artículo');
        return;
    }
    if (!cantidadCap || parseFloat(cantidadCap) <= 0) {
        alert('La cantidad a capturar debe ser mayor a 0.');
        dbgRecMat('Validación agregarLinea: cantidad <= 0', cantidadCap);
        return;
    }

    var actualAcum = parseFloat(recibidaAcum || '0');
    var nuevaAcum  = actualAcum + parseFloat(cantidadCap || '0');
    $('#txtRecibidaAcum').val(nuevaAcum);

    var now = new Date();
    var ds  = now.getFullYear() + '-' +
        ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
        ('0' + now.getDate()).slice(-2) + ' ' +
        ('0' + now.getHours()).slice(-2) + ':' +
        ('0' + now.getMinutes()).slice(-2) + ':' +
        ('0' + now.getSeconds()).slice(-2);

    var statusHtml = '';
    var solNum = parseFloat(solicitada || '0');
    var recNum = nuevaAcum;

    if (tipoRecep === 'OC' && solNum > 0) {
        if (recNum <= 0) {
            statusHtml = '<span class="pill-status pill-red">No recibido</span>';
        } else if (recNum < solNum) {
            statusHtml = '<span class="pill-status pill-yellow">Parcial</span>';
        } else {
            statusHtml = '<span class="pill-status pill-green">Completo</span>';
        }
    } else {
        statusHtml = '<span class="pill-status pill-yellow">RL / CROSS</span>';
    }

    var costoNum      = parseFloat(costo || '0');
    var cantidadNum   = parseFloat(cantidadCap || '0');
    var costoUnitario = cantidadNum > 0 ? (costoNum / cantidadNum) : 0;

    var saldo = 0;
    if (tipoRecep === 'OC' && solNum > 0) {
        saldo = solNum - recNum;
    }

    tablaRecibidos.row.add([
        statusHtml,
        usuario,
        articulo,
        desc,
        uom,
        loteSerie,
        caducidad,
        solicitada,
        recibidaAcum,
        cantidadCap,
        saldo,
        costoNum.toFixed(2),
        costoUnitario.toFixed(4),
        moneda,
        contenedor,
        lpContenedor,
        palletTxt,
        ds,
        '<button class="btn btn-xs btn-danger ap-btn-xs" ' +
        'onclick="eliminarLinea(this)"><i class="fa fa-trash"></i></button>'
    ]).draw(false);

    dbgRecMat('Línea agregada', {
        tipoRecep, articulo, cantidadCap, contenedor, lpContenedor, palletTxt
    });

    actualizarKPIs();
}

function eliminarLinea(btn) {
    var row = $(btn).closest('tr');
    tablaRecibidos.row(row).remove().draw(false);
    dbgRecMat('Línea eliminada');
    actualizarKPIs();
}

function actualizarKPIs() {
    var count  = tablaRecibidos.rows().count();
    var piezas = 0;
    var setCT  = new Set();
    var setPL  = new Set();

    tablaRecibidos.rows().every(function () {
        var d = this.data();
        var cant = parseFloat(d[9] || '0');
        piezas += cant;
        if (d[14]) setCT.add(d[14]);
        if (d[16]) setPL.add(d[16]);
    });

    $('#kpiLineas').text(count);
    $('#kpiPiezas').text(piezas);
    $('#kpiContenedores').text(setCT.size);
    $('#kpiPallets').text(setPL.size);

    dbgRecMat('KPIs actualizados', {
        lineas: count,
        piezas: piezas,
        contenedores: setCT.size,
        pallets: setPL.size
    });
}

// ========= GUARDAR =========

function guardarRecepcion() {
    if (tablaRecibidos.rows().count() === 0) {
        alert('No hay productos recibidos para guardar.');
        dbgRecMat('guardarRecepcion: sin líneas');
        return;
    }

    var tipo = $('input[name="tipo_recepcion"]:checked').val();

    var header = {
        tipo_recepcion: tipo,
        empresa_id: $('#cboEmpresa').val(),
        empresa_des: $('#cboEmpresa option:selected').text(),
        cve_almac: $('#cboAlmacen').val(),
        almac_des: $('#cboAlmacen option:selected').text(),
        zona_recepcion: $('#cboZonaRecepcion').val(),
        zona_recepcion_des: $('#cboZonaRecepcion option:selected').text(),
        proveedor_id: tipo === 'LIBRE' ? $('#cboProveedor').val() : null,
        proveedor_des: tipo === 'LIBRE'
            ? $('#cboProveedor option:selected').text()
            : $('#txtProveedor').val(),
        folio_oc: $('#cboOC').val(),
        folio_recepcion_rl: $('#txtFolioRL').val(),
        folio_recepcion_cross: $('#txtFolioCross').val(),
        folio_tmp: $('#txtFolioTmp').val(),
        factura: $('#txtFactura').val(),
        proyecto: $('#cboProyecto').val(),
        usuario: $('#txtUsuario').val()
    };

    var detalles = [];
    tablaRecibidos.rows().every(function () {
        var d = this.data();
        detalles.push({
            estatus_html: d[0],
            usuario: d[1],
            cve_articulo: d[2],
            des_articulo: d[3],
            um: d[4],
            lote_serie: d[5],
            caducidad: d[6],
            cant_solicitada: d[7],
            cant_recibida_acum: d[8],
            cant_captura: d[9],
            cant_saldo: d[10],
            costo_total: d[11],
            costo_unitario: d[12],
            moneda: d[13],
            contenedor: d[14],
            lp_contenedor: d[15],
            pallet: d[16],
            ts_captura: d[17]
        });
    });

    var payload = {
        header: header,
        detalles: detalles
    };

    dbgRecMat('guardarRecepcion: payload', payload);

    $.ajax({
        url: '../api/recepcion_materiales_guardar.php',
        method: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify(payload),
        success: function (resp) {
            dbgRecMat('Respuesta guardarRecepcion', resp);
            if (resp && resp.ok) {
                alert('Recepción guardada correctamente (placeholder).');
            } else {
                alert('No se pudo guardar la recepción.\n' +
                    (resp && resp.error ? resp.error : 'Error desconocido.'));
            }
        },
        error: function (xhr, status, error) {
            dbgRecMat('Error AJAX guardarRecepcion', {
                status: status,
                error: error,
                responseText: xhr && xhr.responseText ? xhr.responseText : ''
            });
            alert('Error al comunicarse con el servidor (placeholder).');
        }
    });
}
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
