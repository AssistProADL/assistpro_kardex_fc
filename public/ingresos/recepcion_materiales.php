<?php
// public/procesos/recepcion_materiales.php

require_once __DIR__ . '/../bi/_menu_global.php';

$TITLE = 'Recepción de Materiales';
?>
<style>
    .ap-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        border: 1px solid #e1e5eb;
        margin-bottom: 15px;
    }
    .ap-card-header {
        background: #0F5AAD;
        color: #ffffff;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px 8px 0 0;
    }
    .ap-card-body {
        padding: 12px;
    }
    .ap-label {
        font-size: 11px;
        font-weight: 600;
        color: #555;
        margin-bottom: 2px;
    }
    .ap-form-control {
        font-size: 12px;
        height: 32px;
        padding: 4px 8px;
    }
    .ap-small {
        font-size: 10px;
    }

    /* Botones */
    .ap-btn-primary {
        background-color: #0F5AAD;
        border-color: #0F5AAD;
        font-size: 12px;
        padding: 5px 14px;
        border-radius: 4px;
        color: #fff;
    }
    .ap-btn-secondary {
        background-color: #f5f5f5;
        border-color: #ced4da;
        color: #333;
        font-size: 12px;
        padding: 5px 14px;
        border-radius: 4px;
    }
    .ap-btn-recibir {
        background-color: #00A3E0;
        border-color: #00A3E0;
        color: #fff;
        font-size: 12px;
        padding: 5px 14px;
        border-radius: 4px;
    }
    .ap-btn-guardar {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
        font-size: 12px;
        padding: 5px 18px;
        border-radius: 4px;
        font-weight: 600;
    }
    .ap-btn-contpallet {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 4px;
    }

    .ap-section-title {
        font-size: 12px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
        margin-top: 2px;
    }

    /* Tablas estilo AssistPro */
    table.dataTable thead th {
        background-color: #f4f6f9;
        color: #555;
        font-size: 10px !important;
        font-weight: 600;
        padding: 6px 4px;
        border-bottom: 1px solid #dee2e6;
        white-space: nowrap;
    }
    table.dataTable tbody td {
        font-size: 10px !important;
        padding: 4px 4px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        font-size: 10px;
    }
    .dataTables_wrapper .dataTables_info {
        font-size: 9px;
    }

    /* Grilla POS sección 3 */
    #tblPOS thead th {
        background-color: #f4f6f9;
        font-size: 10px;
        white-space: nowrap;
        padding: 4px 4px;
    }
    #tblPOS tbody td {
        padding: 2px 2px;
        vertical-align: middle;
    }
    #tblPOS input,
    #tblPOS select {
        font-size: 10px;
        height: 26px;
        padding: 2px 4px;
    }

    /* Píldoras de estado */
    .pill-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
        color: #fff;
    }
    .pill-red {
        background-color: #dc3545;
    }
    .pill-yellow {
        background-color: #ffc107;
        color: #212529;
    }
    .pill-green {
        background-color: #28a745;
    }
</style>

<div class="container-fluid">

    <!-- ====== ENCABEZADO RECEPCIÓN ====== -->
    <div class="ap-card">
        <div class="ap-card-header">
            Recepción de Materiales
        </div>
        <div class="ap-card-body">

            <!-- Tipo de recepción -->
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

            <!-- ====== DATOS GENERALES (Empresa, Almacén, Zona de Recepción) ====== -->
            <div class="ap-section-title">Datos generales</div>
            <div class="row">
                <!-- Empresa -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboEmpresa">Empresa</label>
                        <select id="cboEmpresa" class="form-control ap-form-control">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                </div>

                <!-- Almacén -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboAlmacen">Almacén</label>
                        <select id="cboAlmacen" class="form-control ap-form-control">
                            <option value="">[Seleccione un almacén]</option>
                        </select>
                    </div>
                </div>

                <!-- Zona de Recepción -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboZonaRecepcion">Zona de Recepción*</label>
                        <select id="cboZonaRecepcion" class="form-control ap-form-control">
                            <option value="">Seleccione una Zona de Recepción</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Zona destino (CrossDocking) -->
            <div class="row">
                <!-- Zona de Almacenaje destino -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboZonaDestino">Zona de Almacenaje destino</label>
                        <select id="cboZonaDestino" class="form-control ap-form-control" disabled>
                            <option value="">Seleccione Zona destino</option>
                        </select>
                    </div>
                </div>
                <!-- BL destino -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboBLDestino">BL destino</label>
                        <select id="cboBLDestino" class="form-control ap-form-control" disabled>
                            <option value="">Seleccione BL destino</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ====== DATOS COMERCIALES ====== -->
            <div class="ap-section-title mt-2">Datos comerciales</div>
            <div class="row">
                <!-- Proveedor -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="txtProveedor">Proveedor</label>
                        <input type="text" id="txtProveedor" class="form-control ap-form-control" readonly>
                    </div>
                </div>

                <!-- Número de Orden de Compra -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboOC">Número de Orden de Compra</label>
                        <select id="cboOC" class="form-control ap-form-control" disabled>
                            <option value="">Seleccione una OC</option>
                        </select>
                    </div>
                </div>

                <!-- Folio Recepción RL -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="txtFolioRL">Folio de Recepción RL</label>
                        <input type="text" id="txtFolioRL" class="form-control ap-form-control">
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Folio Recepción Cross Docking -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="txtFolioCross">Folio de Recepción Cross Docking</label>
                        <input type="text" id="txtFolioCross" class="form-control ap-form-control">
                    </div>
                </div>

                <!-- Factura / Remisión -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="txtFactura">Factura | Remisión</label>
                        <input type="text" id="txtFactura" class="form-control ap-form-control">
                    </div>
                </div>

                <!-- Proyecto -->
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="ap-label" for="cboProyecto">Proyecto</label>
                        <select id="cboProyecto" class="form-control ap-form-control">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- folio interno temporal -->
            <input type="hidden" id="txtFolioTmp" value="">

            <!-- ====== CAPTURA POS DE ARTÍCULOS ====== -->
            <div class="ap-section-title mt-2">Captura POS de artículos</div>

            <div class="table-responsive">
                <table id="tblPOS" class="table table-bordered table-sm">
                    <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Artículo</th>
                        <th>Descripción</th>
                        <th>UOM</th>
                        <th>Lote o Serie</th>
                        <th>Caducidad</th>
                        <th style="display:none;">Cantidad Captura</th> <!-- Oculto en UI -->
                        <th>Cantidad Solicitada</th>
                        <th>Cantidad Recibida</th>
                        <th>Costo</th>
                        <th>Contenedor</th>
                        <th>LP Contenedor</th>
                        <th>Pallet</th>
                        <th>LP Pallet</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <!-- Usuario -->
                        <td>
                            <input type="text" id="txtUsuario" class="form-control" readonly
                                   value="<?php echo isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : '(wmsmaster) Usuario WMS Admin'; ?>">
                        </td>
                        <!-- Artículo -->
                        <td style="min-width:150px;">
                            <select id="cboArticulo" class="form-control">
                                <option value="">Seleccione</option>
                            </select>
                        </td>
                        <!-- Descripción -->
                        <td style="min-width:200px;">
                            <input type="text" id="txtDescArticulo" class="form-control" readonly>
                        </td>
                        <!-- UOM -->
                        <td>
                            <select id="cboUM" class="form-control">
                                <option value="">UM</option>
                            </select>
                        </td>
                        <!-- Lote / Serie -->
                        <td>
                            <input type="text" id="txtLoteSerie" class="form-control">
                        </td>
                        <!-- Caducidad -->
                        <td>
                            <input type="date" id="txtCaducidad" class="form-control">
                        </td>
                        <!-- Cantidad Captura (oculta) -->
                        <td style="display:none;">
                            <input type="number" min="0" step="0.0001" id="txtCantidad" class="form-control">
                        </td>
                        <!-- Cantidad Solicitada (de la OC) -->
                        <td>
                            <input type="number" id="txtSolicitada" class="form-control" readonly>
                        </td>
                        <!-- Cantidad Recibida acumulada -->
                        <td>
                            <input type="number" id="txtRecibidaAcum" class="form-control" readonly>
                        </td>
                        <!-- Costo -->
                        <td>
                            <input type="number" min="0" step="0.0001" id="txtCostoTotal" class="form-control">
                        </td>
                        <!-- Contenedor -->
                        <td>
                            <input type="text" id="txtContenedor" class="form-control">
                        </td>
                        <!-- LP Contenedor -->
                        <td>
                            <input type="text" id="txtLPContenedor" class="form-control">
                        </td>
                        <!-- Pallet -->
                        <td>
                            <select id="cboPallet" class="form-control">
                                <option value="">Pallet</option>
                            </select>
                        </td>
                        <!-- LP Pallet -->
                        <td>
                            <input type="text" id="txtLPPallet" class="form-control">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Botón para gestionar contenedores / pallets -->
            <div class="text-end mt-2">
                <button type="button" id="btnAgregarContPallet" class="btn ap-btn-contpallet btn-sm">
                    Agregar Contenedor o Pallet
                </button>
            </div>

        </div>
    </div>

    <!-- ====== PRODUCTOS RECIBIDOS (INCLUYE ESTATUS OC) ====== -->
    <div class="ap-card">
        <div class="ap-card-body">

            <!-- Botón Recibir artículo -->
            <div class="d-flex justify-content-start mb-2">
                <button type="button" id="btnRecibirArticulo" class="btn ap-btn-recibir">
                    Recibir artículo
                </button>
            </div>

            <!-- Tabla de productos recibidos / esperados -->
            <div class="ap-section-title">Productos recibidos / esperados por la OC</div>
            <div class="table-responsive">
                <table id="tblRecibidos" class="table table-striped table-bordered table-sm" style="width:100%;">
                    <thead>
                    <tr>
                        <th>Estatus</th>
                        <th>Usuario</th>
                        <th>Artículo</th>
                        <th>Descripción</th>
                        <th>UOM</th>
                        <th>Lote o Serie</th>
                        <th>Caducidad</th>
                        <th>Cantidad Solicitada</th>
                        <th>Cantidad Recibida</th>
                        <th>Costo</th>
                        <th>Contenedor</th>
                        <th>LP Contenedor</th>
                        <th>Pallet</th>
                        <th>LP Pallet</th>
                        <th>Zona de Recepción</th>
                        <th>DateStamp</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <!-- Botones inferiores -->
            <div class="d-flex justify-content-between mt-3">
                <button type="button" id="btnGuardar" class="btn ap-btn-guardar">
                    Guardar
                </button>
                <button type="button" id="btnCerrar" class="btn ap-btn-secondary">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL: AGREGAR CONTENEDOR O PALLET -->
<div class="modal fade" id="modalContPallet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Asignar Contenedor / Pallet</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" style="font-size:11px;">
                <div class="mb-2">
                    <div class="ap-label">Tipo de agrupador</div>
                    <div class="form-check form-check-inline ap-small">
                        <input class="form-check-input" type="radio" name="tipoCP" id="tipoCont" value="CONT" checked>
                        <label class="form-check-label" for="tipoCont">Contenedor</label>
                    </div>
                    <div class="form-check form-check-inline ap-small">
                        <input class="form-check-input" type="radio" name="tipoCP" id="tipoPal" value="PAL">
                        <label class="form-check-label" for="tipoPal">Pallet</label>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="ap-label">Clave generada</label>
                    <input type="text" id="txtIdCP" class="form-control ap-form-control" readonly>
                </div>

                <div class="mb-2">
                    <label class="ap-label">LP generado</label>
                    <input type="text" id="txtLpCP" class="form-control ap-form-control" readonly>
                </div>

                <div id="grpUsarPallet" class="mb-2" style="display:none;">
                    <div class="form-check ap-small">
                        <input type="checkbox" class="form-check-input" id="chkUsarPallet">
                        <label class="form-check-label" for="chkUsarPallet">
                            Usar también Pallet para este Contenedor
                        </label>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="form-check ap-small">
                        <input type="checkbox" class="form-check-input" id="chkCerrarPrincipal">
                        <label class="form-check-label" for="chkCerrarPrincipal">
                            Cerrar el agrupador después de asignar
                        </label>
                    </div>
                </div>

                <small class="text-muted">
                    Se agruparán los productos recibidos que aún no tengan Contenedor/Pallet asignado.
                </small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm ap-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm ap-btn-primary" id="btnAplicarContPallet">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<script>
var tablaRecibidos = null;

// Estructuras simples para contenedores y pallets (demo)
var contenedores = [];
var pallets = [];
var contCounter = 0;
var palletCounter = 0;

$(document).ready(function () {
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

    cargarAlmacenes();
    cargarEmpresas();

    $('#cboAlmacen').on('change', function () {
        cargarOCs();
        cargarZonasRecepcion();
        cargarZonasDestino();
    });

    $('input[name="tipo_recepcion"]').on('change', function () {
        onTipoRecepcionChange();
    });

    $('#cboArticulo').on('change', function () {
        var txt = $('#cboArticulo option:selected').text();
        $('#txtDescArticulo').val(txt);
        cargarUnidadesMedida();
        // luego: ligar Cantidad Solicitada desde OC para este artículo
    });

    $('#btnRecibirArticulo').on('click', agregarArticuloRecibido);
    $('#btnGuardar').on('click', guardarRecepcion);
    $('#btnCerrar').on('click', function () {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '../dashboard/index.php';
        }
    });

    // Botón para abrir modal de contenedor/pallet
    $('#btnAgregarContPallet').on('click', function () {
        prepararModalContPallet();
        var modal = new bootstrap.Modal(document.getElementById('modalContPallet'));
        modal.show();
    });

    // Cambio de tipo en modal
    $('input[name="tipoCP"]').on('change', function () {
        actualizarVistaModalCP();
        generarIdYLP_CP();
    });

    // Aplicar asignación de contenedor/pallet
    $('#btnAplicarContPallet').on('click', function () {
        aplicarContPallet();
    });

    // Folio interno TMP
    if (!$('#txtFolioTmp').val()) {
        var now = new Date();
        var folioTmp = 'TMP-' +
            now.getFullYear().toString().slice(-2) +
            ('0' + (now.getMonth() + 1)).slice(-2) +
            ('0' + now.getDate()).slice(-2) + '-' +
            ('0' + now.getHours()).slice(-2) +
            ('0' + now.getMinutes()).slice(-2);
        $('#txtFolioTmp').val(folioTmp);
    }

    // Inicializar modo (por default OC)
    onTipoRecepcionChange();
});

// ====== CARGAS (placeholders AJAX) ======
function cargarAlmacenes() {
    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'almacenes'},
        success: function (resp) {
            var $cbo = $('#cboAlmacen');
            $cbo.empty().append('<option value="">[Seleccione un almacén]</option>');
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cbo.append(
                        $('<option>', {
                            value: row.id || row.cve_almac || '',
                            text: '[' + (row.clave || row.cve_almac || '') + '] - ' + (row.nombre || row.des_almac || '')
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudo cargar almacenes (placeholder).');
        }
    });
}

function cargarEmpresas() {
    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'empresas'},
        success: function (resp) {
            var $cbo = $('#cboEmpresa');
            $cbo.empty().append('<option value="">Seleccione</option>');
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cbo.append(
                        $('<option>', {
                            value: row.id || row.empresa_id || '',
                            text: row.nombre || row.descripcion || ''
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudo cargar empresas (placeholder).');
        }
    });
}

function cargarOCs() {
    var cveAlmac = $('#cboAlmacen').val();
    var $cbo = $('#cboOC');
    $cbo.prop('disabled', !cveAlmac);
    $cbo.empty().append('<option value="">Seleccione una OC</option>');
    if (!cveAlmac) return;

    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'ordenes_compra', cve_almac: cveAlmac},
        success: function (resp) {
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cbo.append(
                        $('<option>', {
                            value: row.folio || row.id || '',
                            text: (row.folio || '') + ' - ' + (row.proveedor || '')
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudieron cargar OCs (placeholder).');
        }
    });
}

function cargarZonasRecepcion() {
    var cveAlmac = $('#cboAlmacen').val();
    var $cbo = $('#cboZonaRecepcion');
    $cbo.empty().append('<option value="">Seleccione una Zona de Recepción</option>');
    if (!cveAlmac) return;

    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'zonas', cve_almac: cveAlmac},
        success: function (resp) {
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cbo.append(
                        $('<option>', {
                            value: row.id || row.idy_ubica || '',
                            text: row.nombre || row.CodigoCSD || ''
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudieron cargar zonas (placeholder).');
        }
    });
}

function cargarZonasDestino() {
    var cveAlmac = $('#cboAlmacen').val();
    var $cbo = $('#cboZonaDestino');
    $cbo.empty().append('<option value="">Seleccione Zona destino</option>');
    if (!cveAlmac) return;

    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'zonas_destino', cve_almac: cveAlmac},
        success: function (resp) {
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cbo.append(
                        $('<option>', {
                            value: row.id || row.idy_ubica || '',
                            text: row.nombre || row.CodigoCSD || ''
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudieron cargar zonas destino (placeholder).');
        }
    });
}

function cargarBLDestino() {
    var zonaDest = $('#cboZonaDestino').val();
    var $cbo = $('#cboBLDestino');
    $cbo.empty().append('<option value="">Seleccione BL destino</option>');
    if (!zonaDest) return;

    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'bl_destino', id_zona: zonaDest},
        success: function (resp) {
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cbo.append(
                        $('<option>', {
                            value: row.id || row.idy_ubica || '',
                            text: row.CodigoCSD || row.bl || ''
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudieron cargar BL destino (placeholder).');
        }
    });
}

function cargarUnidadesMedida() {
    var cveArticulo = $('#cboArticulo').val();
    var $cboUM = $('#cboUM');
    $cboUM.empty().append('<option value="">UM</option>');
    if (!cveArticulo) return;

    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'GET',
        dataType: 'json',
        data: {action: 'unidades_articulo', cve_articulo: cveArticulo},
        success: function (resp) {
            if (resp && resp.ok && Array.isArray(resp.data)) {
                resp.data.forEach(function (row) {
                    $cboUM.append(
                        $('<option>', {
                            value: row.cve_unimed || row.id || '',
                            text: row.descripcion || row.cve_unimed || ''
                        })
                    );
                });
            }
        },
        error: function () {
            console.warn('No se pudieron cargar UM (placeholder).');
        }
    });
}

function onTipoRecepcionChange() {
    var tipo = $('input[name="tipo_recepcion"]:checked').val();
    var isCross = (tipo === 'CROSS');

    $('#cboZonaDestino').prop('disabled', !isCross);
    $('#cboBLDestino').prop('disabled', !isCross);

    if (!isCross) {
        $('#cboZonaDestino').val('');
        $('#cboBLDestino').val('');
    } else {
        cargarZonasDestino();
        $('#cboZonaDestino').off('change').on('change', function () {
            cargarBLDestino();
        });
    }
}

// ====== RECIBIR ARTÍCULO DESDE GRILLA POS ======
function agregarArticuloRecibido() {
    var tipoRecep   = $('input[name="tipo_recepcion"]:checked').val();
    var usuario      = $('#txtUsuario').val();
    var articulo     = $('#cboArticulo').val();
    var desc         = $('#txtDescArticulo').val() || $('#cboArticulo option:selected').text();
    var uom          = $('#cboUM option:selected').text();
    var loteSerie    = $('#txtLoteSerie').val();
    var caducidad    = $('#txtCaducidad').val();
    var cantidadCap  = $('#txtCantidad').val();  // sigue siendo el valor de captura, pero oculto en UI
    var solicitada   = $('#txtSolicitada').val();
    var recibidaAcum = $('#txtRecibidaAcum').val();
    var costo        = $('#txtCostoTotal').val();
    var contenedor   = $('#txtContenedor').val();
    var lpContenedor = $('#txtLPContenedor').val();
    var palletTxt    = $('#cboPallet option:selected').text();
    var lpPallet     = $('#txtLPPallet').val();
    var zonaRecep    = $('#cboZonaRecepcion option:selected').text();

    if (!articulo) {
        alert('Seleccione un artículo.');
        return;
    }
    if (!cantidadCap || parseFloat(cantidadCap) <= 0) {
        alert('Capture una cantidad válida (interna).');
        return;
    }
    if (!zonaRecep) {
        alert('Seleccione una Zona de Recepción.');
        return;
    }

    // Actualizar cantidad recibida acumulada
    var actualAcum = parseFloat(recibidaAcum || '0');
    var nuevaAcum  = actualAcum + parseFloat(cantidadCap);
    $('#txtRecibidaAcum').val(nuevaAcum);

    // DateStamp completo
    var now = new Date();
    var ds  = now.getFullYear() + '-' +
              ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
              ('0' + now.getDate()).slice(-2) + ' ' +
              ('0' + now.getHours()).slice(-2) + ':' +
              ('0' + now.getMinutes()).slice(-2) + ':' +
              ('0' + now.getSeconds()).slice(-2);

    // Determinar estado (sólo aplica para OC, no para RL)
    var statusHtml = '';
    var solNum = parseFloat(solicitada || '0');
    var recNum = parseFloat(nuevaAcum || '0');

    if (tipoRecep === 'OC' && solNum > 0) {
        if (recNum <= 0) {
            statusHtml = '<span class="pill-status pill-red">No recibido</span>';
        } else if (recNum < solNum) {
            statusHtml = '<span class="pill-status pill-yellow">Parcial</span>';
        } else {
            statusHtml = '<span class="pill-status pill-green">Completo</span>';
        }
    }

    // Agregar fila a la grilla de productos recibidos
    tablaRecibidos.row.add([
        statusHtml,        // 0 Estatus
        usuario,           // 1
        articulo,          // 2
        desc,              // 3
        uom,               // 4
        loteSerie,         // 5
        caducidad,         // 6
        solicitada,        // 7 Cantidad Solicitada
        nuevaAcum,         // 8 Cantidad Recibida
        costo,             // 9
        contenedor,        // 10
        lpContenedor,      // 11
        palletTxt,         // 12
        lpPallet,          // 13
        zonaRecep,         // 14
        ds                 // 15
    ]).draw();

    // Limpieza de campos de captura de movimiento (no de acumulados ni de artículo)
    $('#txtCantidad').val('');
    $('#txtCostoTotal').val('');
    $('#txtLoteSerie').val('');
    $('#txtCaducidad').val('');
    $('#txtContenedor').val('');
    $('#txtLPContenedor').val('');
    $('#txtLPPallet').val('');
}

// ====== MODAL CONTENEDOR / PALLET ======
function prepararModalContPallet() {
    // Default: Contenedor
    $('#tipoCont').prop('checked', true);
    $('#tipoPal').prop('checked', false);
    $('#chkUsarPallet').prop('checked', false);
    $('#chkCerrarPrincipal').prop('checked', false);
    actualizarVistaModalCP();
    generarIdYLP_CP();
}

function actualizarVistaModalCP() {
    var tipo = $('input[name="tipoCP"]:checked').val(); // CONT / PAL
    if (tipo === 'CONT') {
        $('#grpUsarPallet').show();
    } else {
        $('#grpUsarPallet').hide();
    }
}

function generarIdYLP_CP() {
    var tipo = $('input[name="tipoCP"]:checked').val(); // CONT / PAL
    var now = new Date();
    var baseTS = now.getFullYear().toString() +
        ('0' + (now.getMonth() + 1)).slice(-2) +
        ('0' + now.getDate()).slice(-2) +
        ('0' + now.getHours()).slice(-2) +
        ('0' + now.getMinutes()).slice(-2) +
        ('0' + now.getSeconds()).slice(-2);

    if (tipo === 'CONT') {
        contCounter++;
        var id = 'CONT-' + ('000' + contCounter).slice(-3);
        var lp = 'LPC-' + baseTS + '-' + contCounter;
        $('#txtIdCP').val(id);
        $('#txtLpCP').val(lp);
    } else {
        palletCounter++;
        var idP = 'PAL-' + ('000' + palletCounter).slice(-3);
        var lpP = 'LPP-' + baseTS + '-' + palletCounter;
        $('#txtIdCP').val(idP);
        $('#txtLpCP').val(lpP);
    }
}

function aplicarContPallet() {
    var tipo = $('input[name="tipoCP"]:checked').val(); // CONT / PAL
    var idCP = $('#txtIdCP').val();
    var lpCP = $('#txtLpCP').val();
    var usarPallet   = $('#chkUsarPallet').is(':checked');
    var cerrar       = $('#chkCerrarPrincipal').is(':checked');

    if (!idCP || !lpCP) {
        alert('No se ha generado la clave / LP correctamente.');
        return;
    }

    var now = new Date();
    var baseTS = now.getFullYear().toString() +
        ('0' + (now.getMonth() + 1)).slice(-2) +
        ('0' + now.getDate()).slice(-2) +
        ('0' + now.getHours()).slice(-2) +
        ('0' + now.getMinutes()).slice(-2) +
        ('0' + now.getSeconds()).slice(-2);

    var palletForContainer = null;

    if (tipo === 'CONT') {
        contenedores.push({
            id: idCP,
            lp: lpCP,
            cerrado: cerrar,
            ts_crea: baseTS
        });

        if (usarPallet) {
            palletCounter++;
            var idP = 'PAL-' + ('000' + palletCounter).slice(-3);
            var lpP = 'LPP-' + baseTS + '-' + palletCounter;
            palletForContainer = {
                id: idP,
                lp: lpP,
                cerrado: false,
                ts_crea: baseTS
            };
            pallets.push(palletForContainer);
        }
    } else { // PAL
        pallets.push({
            id: idCP,
            lp: lpCP,
            cerrado: cerrar,
            ts_crea: baseTS
        });
    }

    // Asignar a filas sin contenedor/pallet
    tablaRecibidos.rows().every(function () {
        var d = this.data();

        // Columnas:
        // 10: Contenedor, 11: LP Contenedor, 12: Pallet, 13: LP Pallet
        if (tipo === 'CONT') {
            if (!d[10]) { // sin contenedor aún
                d[10] = idCP;
                d[11] = lpCP;

                if (usarPallet && palletForContainer) {
                    d[12] = palletForContainer.id;
                    d[13] = palletForContainer.lp;
                }

                this.data(d);
            }
        } else { // tipo PAL
            if (!d[12]) { // sin pallet
                d[12] = idCP;
                d[13] = lpCP;
                this.data(d);
            }
        }
    });

    tablaRecibidos.draw(false);

    var modalEl = document.getElementById('modalContPallet');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

// ====== GUARDAR RECEPCIÓN ======
function guardarRecepcion() {
    if (tablaRecibidos.rows().count() === 0) {
        alert('No hay productos recibidos para guardar.');
        return;
    }

    var header = {
        tipo_recepcion: $('input[name="tipo_recepcion"]:checked').val(),
        empresa_id: $('#cboEmpresa').val(),
        empresa_des: $('#cboEmpresa option:selected').text(),
        cve_almac: $('#cboAlmacen').val(),
        almac_des: $('#cboAlmacen option:selected').text(),
        zona_recepcion: $('#cboZonaRecepcion').val(),
        zona_recepcion_des: $('#cboZonaRecepcion option:selected').text(),
        zona_destino: $('#cboZonaDestino').val(),
        zona_destino_des: $('#cboZonaDestino option:selected').text(),
        bl_destino: $('#cboBLDestino').val(),
        bl_destino_des: $('#cboBLDestino option:selected').text(),
        proveedor: $('#txtProveedor').val(),
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
            descripcion: d[3],
            uom: d[4],
            lote_serie: d[5],
            caducidad: d[6],
            cantidad_solicitada: d[7],
            cantidad_recibida: d[8],
            costo: d[9],
            contenedor: d[10],
            lp_contenedor: d[11],
            pallet: d[12],
            lp_pallet: d[13],
            zona_recepcion: d[14],
            datestamp: d[15]
        });
    });

    $.ajax({
        url: '../api/recepcion_materiales.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'guardar',
            header: JSON.stringify(header),
            detalles: JSON.stringify(detalles)
        },
        success: function (resp) {
            if (resp && resp.ok) {
                alert('Recepción guardada correctamente (demo).');
            } else {
                alert('No se pudo guardar la recepción.\n' +
                    (resp && resp.error ? resp.error : 'Error desconocido.'));
            }
        },
        error: function () {
            alert('Error al comunicarse con el servidor (placeholder).');
        }
    });
}
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
