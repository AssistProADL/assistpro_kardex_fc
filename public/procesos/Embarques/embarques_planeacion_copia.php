<?php
// public/procesos/embarques/planeacion_embarques.php

$TITLE = 'Planeación y Administración de Embarques';

// Ruta de menús según tu estructura
require_once __DIR__ . '../../../bi/_menu_global.php';
?>

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<style>
    .ap-card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(15,90,173,0.08);
        border: 1px solid #e1e5eb;
        margin-bottom: 15px;
    }
    .ap-card-body {
        padding: 12px;
    }

    .ap-label {
        font-size: 11px;
        font-weight: 600;
        color: #555;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .ap-label i {
        font-size: 11px;
        color: #0F5AAD;
    }
    .ap-form-control {
        font-size: 12px;
        height: 32px;
        padding: 4px 8px;
    }

    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_info {
        font-size: 11px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        font-size: 11px;
    }
    table.dataTable tbody td {
        font-size: 11px;
        vertical-align: middle;
        white-space: nowrap;
    }
    table.dataTable thead th {
        font-size: 11px;
        white-space: nowrap;
    }

    .ap-page-header-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(15,90,173,0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    .ap-page-header-icon i {
        color: #0F5AAD;
        font-size: 18px;
    }
    .ap-toolbar {
        font-size: 11px;
    }

    /* KPI cards */
    .ap-kpi-card {
        border-radius: 10px;
        border: 1px solid #e1e5eb;
        background: #ffffff;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        height: 76px;
    }
    .ap-kpi-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .ap-kpi-icon-primary {
        background: rgba(15,90,173,0.10);
        color: #0F5AAD;
    }
    .ap-kpi-icon-success {
        background: rgba(25,135,84,0.10);
        color: #198754;
    }
    .ap-kpi-icon-info {
        background: rgba(13,202,240,0.10);
        color: #0dcaf0;
    }
    .ap-kpi-icon-danger {
        background: rgba(220,53,69,0.10);
        color: #dc3545;
    }
    .ap-kpi-label {
        font-size: 11px;
        font-weight: 600;
        color: #555;
    }
    .ap-kpi-sub {
        font-size: 10px;
        color: #888;
    }
    .ap-kpi-value {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        line-height: 1;
    }

    /* Filtros */
    .ap-filters-quick .form-select,
    .ap-filters-quick .form-control {
        font-size: 12px;
        height: 32px;
        padding: 4px 8px;
    }
    .ap-filters-quick .col-filter {
        margin-bottom: 8px;
    }

    .ap-filters-advanced-toggle {
        font-size: 11px;
        cursor: pointer;
        color: #0F5AAD;
    }
    .ap-filters-advanced-toggle i {
        font-size: 11px;
    }
    .ap-filters-advanced {
        border-top: 1px dashed #e1e5eb;
        margin-top: 8px;
        padding-top: 8px;
    }

    .ap-filters-actions {
        border-top: 1px solid #f0f2f5;
        margin-top: 10px;
        padding-top: 8px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ap-accordion-main .accordion-button {
        background: linear-gradient(90deg, #0F5AAD 0%, #174f8f 100%);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 10px 10px 0 0;
    }
    .ap-accordion-main .accordion-button:not(.collapsed) {
        box-shadow: none;
    }
    .ap-accordion-main .accordion-button::after {
        filter: invert(1);
    }
    .ap-accordion-main small {
        font-weight: 400;
        opacity: 0.8;
    }

    @media (max-width: 767.98px) {
        .ap-toolbar {
            justify-content: flex-start !important;
        }
        .ap-toolbar > * {
            margin-top: 4px;
        }
        .ap-filters-actions {
            justify-content: stretch;
        }
        .ap-filters-actions button {
            flex: 1 1 auto;
        }
    }
</style>

<div class="container-fluid py-3">
    <!-- HEADER -->
    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <div class="ap-page-header-icon">
                    <i class="fa fa-truck"></i>
                </div>
                <div>
                    <h5 class="mb-0 text-primary fw-bold">
                        Planeación y Administración de Embarques
                    </h5>
                    <small class="text-muted">
                        <i class="fa fa-route me-1"></i>Organiza embarques por almacén, ruta, cliente y ventana de entrega.
                    </small>
                </div>
            </div>
            <div class="ap-toolbar d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <div class="input-group input-group-sm" style="max-width:260px;">
                    <span class="input-group-text">
                        <i class="fa fa-search"></i>
                    </span>
                    <input type="text"
                           class="form-control"
                           id="buscar"
                           placeholder="Buscar por pedido / folio / cliente">
                </div>
            </div>
        </div>
    </div>

    <!-- ACCORDION PRINCIPAL DE FILTROS -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="accordion ap-accordion-main" id="accordionFiltrosMain">
                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="headingFiltrosMain">
                        <button class="accordion-button" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseFiltrosMain"
                                aria-expanded="true"
                                aria-controls="collapseFiltrosMain">
                            <i class="fa fa-filter me-2"></i>
                            <span>Filtros de planeación</span>
                            <small class="ms-2 d-none d-md-inline">
                                Primero seleccione Almacén y Ruta; luego refine según sea necesario.
                            </small>
                        </button>
                    </h2>
                    <div id="collapseFiltrosMain"
                         class="accordion-collapse collapse show"
                         aria-labelledby="headingFiltrosMain"
                         data-bs-parent="#accordionFiltrosMain">
                        <div class="accordion-body p-0">
                            <div class="ap-card mb-0" style="border-radius: 0 0 10px 10px; border-top: 0;">
                                <div class="ap-card-body">
                                    <!-- Filtros rápidos -->
                                    <!-- Filtros rápidos -->
                                    <div class="row ap-filters-quick align-items-end">
                                        <div class="col-12 col-md-3 col-filter">
                                            <label for="empresa" class="ap-label">Empresa</label>
                                            <select class="form-select form-select-sm ap-form-control"
                                                    id="empresa" name="empresa">
                                                <option value="">Todas</option>
                                                <!-- Se llenará vía filtros_assistpro.php -->
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3 col-filter">
                                            <label for="almacen" class="ap-label">Almacén</label>
                                            <select class="form-select form-select-sm ap-form-control"
                                                    id="almacen" name="almacen" disabled>
                                                <option value="">Todos</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3 col-filter">
                                            <label for="ruta" class="ap-label">Ruta</label>
                                            <select class="form-select form-select-sm ap-form-control"
                                                    id="ruta" name="ruta" disabled>
                                                <option value="">Todas</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3 col-filter">
                                            <label for="isla" class="ap-label">Área de embarque (Isla)</label>
                                            <select class="form-select form-select-sm ap-form-control"
                                                    id="isla" name="isla" disabled>
                                                <option value="">Todas</option>
                                            </select>
                                        </div>
                                    </div>


                                    <!-- Filtros avanzados (collapse interno) -->
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <div class="ap-filters-advanced-toggle"
                                             data-bs-toggle="collapse"
                                             data-bs-target="#filtrosAvanzados"
                                             aria-expanded="false"
                                             aria-controls="filtrosAvanzados">
                                            <i class="fa fa-chevron-down me-1"></i>
                                            Más filtros (colonia, CP, área de embarque, transporte...)
                                        </div>
                                        <small class="text-muted d-none d-md-inline">
                                            Los filtros avanzados son opcionales y refinan la planeación.
                                        </small>
                                    </div>

                                    <div class="collapse ap-filters-advanced mt-2" id="filtrosAvanzados">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-3">
                                                <label for="colonia" class="ap-label">
                                                    <i class="fa fa-map-marker-alt"></i> Colonia
                                                </label>
                                                <select class="form-select form-select-sm ap-form-control"
                                                        id="colonia" name="colonia" disabled>
                                                    <option value="">Todas</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label for="cpostal" class="ap-label">
                                                    <i class="fa fa-mail-bulk"></i> Código Postal
                                                </label>
                                                <select class="form-select form-select-sm ap-form-control"
                                                        id="cpostal" name="cpostal" disabled>
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label for="isla" class="ap-label">
                                                    <i class="fa fa-ship"></i> Área de embarque (Isla)
                                                </label>
                                                <select class="form-select form-select-sm ap-form-control"
                                                        id="isla" name="isla" disabled>
                                                    <option value="">Todas</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label for="cve_transportadora" class="ap-label">
                                                    <i class="fa fa-building"></i> Transportista
                                                </label>
                                                <select class="form-select form-select-sm ap-form-control"
                                                        id="cve_transportadora" name="cve_transportadora">
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label for="select_transporte" class="ap-label">
                                                    <i class="fa fa-truck-loading"></i> Transporte / Unidad
                                                </label>
                                                <select class="form-select form-select-sm ap-form-control"
                                                        id="select_transporte" name="select_transporte">
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-2">
                                                <label for="contenedor" class="ap-label">
                                                    <i class="fa fa-boxes"></i> Contenedor / Pallet
                                                </label>
                                                <select class="form-select form-select-sm ap-form-control"
                                                        id="contenedor" name="contenedor">
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Acciones de filtros -->
                                    <div class="ap-filters-actions">
                                        <button type="button"
                                                class="btn btn-sm btn-light"
                                                id="btn_limpiar_filtros">
                                            <i class="fa fa-eraser me-1"></i> Limpiar
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-warning"
                                                id="btn_aplicar_filtros">
                                            <i class="fa fa-search me-1"></i> Aplicar filtros
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div><!-- /collapseFiltrosMain -->
                </div>
            </div>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-3">
            <div class="ap-kpi-card">
                <div class="ap-kpi-icon ap-kpi-icon-primary">
                    <i class="fa fa-calendar-day"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ap-kpi-label">Embarques del día</div>
                    <div class="ap-kpi-sub">Programados para hoy</div>
                </div>
                <div class="text-end">
                    <div id="kpi_embarques_dia" class="ap-kpi-value">-</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ap-kpi-card">
                <div class="ap-kpi-icon ap-kpi-icon-success">
                    <i class="fa fa-clipboard-check"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ap-kpi-label">Planeados</div>
                    <div class="ap-kpi-sub">En ventana de 7 días</div>
                </div>
                <div class="text-end">
                    <div id="kpi_planeados_7d" class="ap-kpi-value">-</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ap-kpi-card">
                <div class="ap-kpi-icon ap-kpi-icon-info">
                    <i class="fa fa-truck-moving"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ap-kpi-label">En ruta</div>
                    <div class="ap-kpi-sub">Unidades en tránsito</div>
                </div>
                <div class="text-end">
                    <div id="kpi_en_ruta" class="ap-kpi-value">-</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ap-kpi-card">
                <div class="ap-kpi-icon ap-kpi-icon-danger">
                    <i class="fa fa-clock"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ap-kpi-label">Retrasados</div>
                    <div class="ap-kpi-sub">Fuera de ventana</div>
                </div>
                <div class="text-end">
                    <div id="kpi_retrasados" class="ap-kpi-value">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA PRINCIPAL -->
    <div class="row">
        <div class="col-12">
            <div class="ap-card">
                <div class="ap-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa fa-list-ul text-primary"></i>
                            <span class="fw-semibold" style="font-size: 13px;">
                                Embarques planeados y pedidos agregados
                            </span>
                        </div>
                        <small class="text-muted">
                            Máx. 25 registros por página con scroll horizontal.
                        </small>
                    </div>

                    <div class="mb-2 ap-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Ajuste los filtros y use <strong>Aplicar filtros</strong> para actualizar esta vista.
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table id="tabla_embarques"
                               class="table table-sm table-striped table-bordered table-hover nowrap w-100">
                            <thead>
                            <tr>
                                <th><i class="fa fa-cog"></i> Acción</th>
                                <th><i class="fa fa-truck-loading"></i> Embarcar</th>
                                <th>FolioHide</th>
                                <th>Sufijo</th>
                                <th>Folio</th>
                                <th><i class="fa fa-calendar-day"></i> Fecha Pedido</th>
                                <th><i class="fa fa-calendar-check"></i> Fecha Entrega</th>
                                <th>Pedido</th>
                                <th>C Dest</th>
                                <th><i class="fa fa-user"></i> Destinatario</th>
                                <th>C de SUC</th>
                                <th><i class="fa fa-map-marker-alt"></i> Dirección</th>
                                <th>C. Postal</th>
                                <th>Colonia</th>
                                <th>Latitud</th>
                                <th>Longitud</th>
                                <th><i class="fa fa-route"></i> Ruta</th>
                                <th>Horario Planeado</th>
                                <th>Total guías</th>
                                <th>Peso total</th>
                                <th>Volumen</th>
                                <th>Piezas</th>
                                <th>Total Cajas</th>
                                <th>Total Pallets</th>
                                <th>Clave Cliente</th>
                                <th>Cliente | Empresa</th>
                                <th>Razón Social</th>
                                <th>Zona Embarque (Isla)</th>
                                <th>Listo Para Entrega</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn_generar_reportes">
                            <i class="fa fa-file-alt me-1"></i> Generar reportes / etiquetas
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="btn_exportar_excel">
                            <i class="fa fa-file-excel me-1"></i> Exportar a Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn_exportar_pdf">
                            <i class="fa fa-file-pdf me-1"></i> Exportar a PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '../../../bi/_menu_global_end.php';
?>
<script>
    $(document).ready(function () {

        /* ===========================================================
           CONSTANTES
        ============================================================ */
        const API_FILTROS_URL   = '../../api/filtros_assistpro.php';
        const API_EMBARQUES_URL = '../../api/api_embarques_planeacion.php';

        /* ===========================================================
           DATATABLE PRINCIPAL
        ============================================================ */
        var tablaEmbarques = $('#tabla_embarques').DataTable({
            processing: true,
            serverSide: false,
            paging: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            scrollX: true,
            autoWidth: false,
            language: {
                url: '../../app/template/datatables/es_es.json'
            },
            columnDefs: [
                {targets: 0, orderable: false, searchable: false}, // Acción
                {targets: 1, orderable: false, searchable: false}, // Embarcar
                {targets: 2, visible: false},                      // FolioHide
                {targets: [14, 15], visible: false}                // Lat/Long
            ]
        });

        /* ===========================================================
           HELPERS
        ============================================================ */

        function getProp(obj, keys) {
            if (!obj) return undefined;
            for (var i = 0; i < keys.length; i++) {
                var k = keys[i];
                if (Object.prototype.hasOwnProperty.call(obj, k) && obj[k] != null) {
                    return obj[k];
                }
            }
            return undefined;
        }

        /**
         * Extrae una lista "plana" de objetos desde cualquier JSON:
         * - Si es array → lo devuelve
         * - Si es objeto → concatena todos los valores que sean arrays
         *   (incluyendo los que estén dentro de data)
         */
        function extraerLista(payload) {
            if (!payload) return [];

            // Si ya es un array directo
            if (Array.isArray(payload)) {
                return payload;
            }

            var listas = [];

            // Si viene como { ok, data: [...] } o { data: { ... } }
            if (payload.data !== undefined) {
                var d = payload.data;
                if (Array.isArray(d)) {
                    listas.push(d);
                } else if (d && typeof d === 'object') {
                    Object.keys(d).forEach(function (k) {
                        if (Array.isArray(d[k])) {
                            listas.push(d[k]);
                        }
                    });
                }
            }

            // Además, revisar el propio objeto raíz
            if (payload && typeof payload === 'object') {
                Object.keys(payload).forEach(function (k) {
                    if (k === 'data' || k === 'ok' || k === 'error') return;
                    if (Array.isArray(payload[k])) {
                        listas.push(payload[k]);
                    }
                });
            }

            // Aplanar y quitar nulls
            var out = [];
            listas.forEach(function (arr) {
                arr.forEach(function (item) {
                    if (item && typeof item === 'object') {
                        out.push(item);
                    }
                });
            });

            return out;
        }

        /**
         * Llenar un select de forma genérica
         */
        function llenarSelectGenerico($select, lista, placeholder) {
            if (!$select || $select.length === 0) return;

            var firstText = placeholder || $select.find('option').first().text() || 'Todos';

            $select.empty();
            $select.append($('<option>', { value: '', text: firstText }));

            if (!Array.isArray(lista)) return;

            lista.forEach(function (item) {
                if (!item || typeof item !== 'object') return;

                var value = getProp(item, [
                    'id', 'id_almacen', 'id_almacenp',
                    'cve_cia', 'cve_empresa',
                    'clave', 'cve', 'cve_almacen', 'cve_almac',
                    'codigo', 'valor'
                ]);
                if (value === undefined || value === null || value === '') return;

                var nombre = getProp(item, [
                    'nombre', 'descripcion', 'texto', 'label',
                    'razon_social', 'razonsocial',
                    'destinatario', 'cliente'
                ]);

                var clave = getProp(item, [
                    'cve_cia', 'cve_empresa',
                    'clave', 'cve_almacen', 'cve_almac',
                    'codigo'
                ]);

                var textoFinal = '';
                if (clave && nombre && clave !== nombre) {
                    textoFinal = clave + ' - ' + nombre;
                } else if (nombre) {
                    textoFinal = nombre;
                } else if (clave) {
                    textoFinal = clave;
                } else {
                    textoFinal = String(value);
                }

                $select.append($('<option>', {
                    value: value,
                    text: textoFinal
                }));
            });
        }

        /**
         * Consumir filtros_assistpro.php usando fn=...
         * fnName: string (empresas, almacenes, rutas, islas, etc.)
         * extraParams: parámetros adicionales (empresa, almacen, ruta...)
         * cb: callback(lista)
         */
        function solicitarFiltros(fnName, extraParams, cb) {
            var dataSend = $.extend({}, extraParams || {}, { fn: fnName });

            $.ajax({
                url: API_FILTROS_URL,
                method: 'GET',
                dataType: 'json',
                data: dataSend
            }).done(function (resp) {
                var lista = extraerLista(resp);
                if (!Array.isArray(lista)) lista = [];
                if (typeof cb === 'function') cb(lista);
            }).fail(function (xhr, status, error) {
                console.error('Error filtros_assistpro.php fn=' + fnName, status, error, xhr.responseText);
                if (typeof cb === 'function') cb([]);
            });
        }

        /* ===========================================================
           CARGA DE CATÁLOGOS (EMPRESA / ALMACÉN / RUTA / ISLA)
        ============================================================ */

        function cargarEmpresas() {
            solicitarFiltros('empresas', {}, function (listaRaw) {
                // NO filtramos nada: mostramos TODO lo que regrese el API
                var $sel = $('#empresa');
                llenarSelectGenerico($sel, listaRaw, 'Todas');
                $sel.prop('disabled', false);
            });
        }

        function cargarAlmacenes(empresa) {
            var $alm = $('#almacen');

            $alm.prop('disabled', true);
            $('#ruta').prop('disabled', true);
            $('#isla').prop('disabled', true);

            $alm.empty().append('<option value="">Todos</option>');
            $('#ruta').empty().append('<option value="">Todas</option>');
            $('#isla').empty().append('<option value="">Todas</option>');

            if (!empresa) return;

            solicitarFiltros(
                'almacenes',
                { empresa: empresa },
                function (listaRaw) {
                    // De nuevo: NO filtramos nada, usamos todo el arreglo de almacenes
                    llenarSelectGenerico($alm, listaRaw, 'Todos');
                    $alm.prop('disabled', false);
                }
            );
        }

        function cargarRutas(empresa, almacen) {
            var $ruta = $('#ruta');
            var $isla = $('#isla');

            $ruta.prop('disabled', true);
            $isla.prop('disabled', true);

            $ruta.empty().append('<option value="">Todas</option>');
            $isla.empty().append('<option value="">Todas</option>');

            if (!almacen) return;

            solicitarFiltros(
                'rutas',
                { empresa: empresa, almacen: almacen },
                function (listaRaw) {
                    llenarSelectGenerico($ruta, listaRaw, 'Todas');
                    $ruta.prop('disabled', false);
                }
            );
        }

        function cargarIslas(empresa, almacen, ruta) {
            var $isla = $('#isla');
            $isla.prop('disabled', true);
            $isla.empty().append('<option value="">Todas</option>');

            if (!almacen) return;

            solicitarFiltros(
                'islas',
                { empresa: empresa, almacen: almacen, ruta: ruta || '' },
                function (listaRaw) {
                    llenarSelectGenerico($isla, listaRaw, 'Todas');
                    $isla.prop('disabled', false);
                }
            );
        }

        function cargarClientes(empresa, almacen, ruta) {
            solicitarFiltros(
                'clientes',
                { empresa: empresa, almacen: almacen, ruta: ruta || '' },
                function (listaRaw) {
                    llenarSelectGenerico($('#cliente'), listaRaw, 'Todos');
                }
            );
        }

        function cargarColonias(empresa, almacen, ruta) {
            solicitarFiltros(
                'colonias',
                { empresa: empresa, almacen: almacen, ruta: ruta || '' },
                function (listaRaw) {
                    llenarSelectGenerico($('#colonia'), listaRaw, 'Todas');
                }
            );
        }

        function cargarCodigosPostales(empresa, almacen, ruta) {
            solicitarFiltros(
                'codigos_postales',
                { empresa: empresa, almacen: almacen, ruta: ruta || '' },
                function (listaRaw) {
                    llenarSelectGenerico($('#cpostal'), listaRaw, 'Todos');
                }
            );
        }

        /* ===========================================================
           ENCADENAMIENTO DE SELECTS
        ============================================================ */

        $('#empresa').on('change', function () {
            var empresa = $(this).val() || '';

            $('#almacen').val('');
            $('#ruta').val('');
            $('#isla').val('');
            $('#cliente').val('');
            $('#colonia').val('');
            $('#cpostal').val('');

            $('#almacen, #ruta, #isla').prop('disabled', true);

            if (empresa) {
                cargarAlmacenes(empresa);
            }

            recargarEmbarques();
        });

        $('#almacen').on('change', function () {
            var empresa = $('#empresa').val() || '';
            var almacen = $(this).val() || '';

            $('#ruta').val('');
            $('#isla').val('');
            $('#cliente').val('');
            $('#colonia').val('');
            $('#cpostal').val('');

            $('#ruta, #isla').prop('disabled', true);

            if (almacen) {
                cargarRutas(empresa, almacen);
                cargarIslas(empresa, almacen, '');
                cargarClientes(empresa, almacen, '');
                cargarColonias(empresa, almacen, '');
                cargarCodigosPostales(empresa, almacen, '');
            }

            recargarEmbarques();
        });

        $('#ruta').on('change', function () {
            var empresa = $('#empresa').val() || '';
            var almacen = $('#almacen').val() || '';
            var ruta    = $(this).val() || '';

            cargarIslas(empresa, almacen, ruta);
            cargarClientes(empresa, almacen, ruta);
            cargarColonias(empresa, almacen, ruta);
            cargarCodigosPostales(empresa, almacen, ruta);

            recargarEmbarques();
        });

        $('#isla').on('change', function () {
            recargarEmbarques();
        });

        $('#cliente, #colonia, #cpostal, #fecha_desde, #fecha_hasta, #estatus').on('change', function () {
            recargarEmbarques();
        });

        $('#buscar').on('keyup', function (e) {
            if (e.key === 'Enter') {
                recargarEmbarques();
            }
        });

        $('#btn_aplicar_filtros').on('click', function () {
            recargarEmbarques();
        });

        $('#btn_limpiar_filtros').on('click', function () {
            $('#empresa').val('');
            $('#almacen').val('').prop('disabled', true);
            $('#ruta').val('').prop('disabled', true);
            $('#isla').val('').prop('disabled', true);
            $('#cliente').val('');
            $('#colonia').val('');
            $('#cpostal').val('');
            $('#fecha_desde').val('');
            $('#fecha_hasta').val('');
            $('#estatus').val('');
            $('#buscar').val('');

            cargarEmpresas();
            recargarEmbarques();
        });

        $('#btn_limpiar_filtros_transporte').on('click', function () {
            $('#cve_transportadora').val('');
            $('#select_transporte').val('');
            $('#tipo_carga').val('');
            $('#contenedor').val('');
            recargarEmbarques();
        });

        $('#btn_refrescar_tabla').on('click', function () {
            recargarEmbarques();
        });

        /* ===========================================================
           API DE EMBARQUES
        ============================================================ */

        function obtenerFiltros() {
            return {
                action     : 'cargarGridPrincipal',
                empresa    : $('#empresa').val() || '',
                almacen    : $('#almacen').val() || '',
                ruta       : $('#ruta').val() || '',
                isla       : $('#isla').val() || '',
                cliente    : $('#cliente').val() || '',
                colonia    : $('#colonia').val() || '',
                cpostal    : $('#cpostal').val() || '',
                fecha_desde: $('#fecha_desde').val() || '',
                fecha_hasta: $('#fecha_hasta').val() || '',
                estatus    : $('#estatus').val() || '',
                texto      : $('#buscar').val() || '',
                sin_paginacion: 1
            };
        }

        function recargarEmbarques() {
            var filtros = obtenerFiltros();
            console.log('recargarEmbarques -> filtros', filtros);

            tablaEmbarques.clear().draw(false);
            $('#kpi_embarques_dia, #kpi_planeados_7d, #kpi_en_ruta, #kpi_retrasados').text('-');

            $.ajax({
                url: API_EMBARQUES_URL,
                method: 'GET',
                dataType: 'json',
                data: filtros
            }).done(function (resp) {
                if (!resp || resp.ok === false) {
                    console.error('Error API embarques:', resp ? resp.error : 'sin respuesta');
                    return;
                }

                var data = Array.isArray(resp.data) ? resp.data : [];

                data.forEach(function (r) {

                    var folio   = r.folio   || '';
                    var sufijo  = r.sufijo  || '';
                    var fPed    = r.fecha_pedido  || '';
                    var fEnt    = r.fecha_entrega || '';
                    var cveCli  = r.cve_cliente   || '';
                    var razon   = r.razon_social  || '';
                    var idDest  = r.id_destinatario || '';
                    var claveSuc= r.clave_sucursal  || '';
                    var dir     = r.direccion_cliente || '';
                    var cp      = r.codigo_postal || '';
                    var col     = r.colonia || '';
                    var lat     = r.latitud  || '';
                    var lon     = r.longitud || '';
                    var rutaTxt = r.ruta     || '';
                    var isla    = r.isla     || '';
                    var rango   = r.rango_hora || '';
                    var status  = r.status_subpedido || '';

                    var totalGuias   = (r.total_guias   != null) ? r.total_guias   : '';
                    var pesoTotal    = (r.peso_total    != null) ? r.peso_total    : '';
                    var volumen      = (r.volumen       != null) ? r.volumen       : '';
                    var piezas       = (r.piezas        != null) ? r.piezas        : '';
                    var totalCajas   = (r.total_cajas   != null) ? r.total_cajas   : '';
                    var totalPallets = (r.total_pallets != null) ? r.total_pallets : '';

                    var clienteEmpresa = (cveCli ? (cveCli + ' | ') : '') + (razon || '');

                    var btnAccion = '<button type="button" class="btn btn-xs btn-outline-primary btn-sm" ' +
                        'data-folio="' + folio + '">' +
                        '<i class="fa fa-cog"></i></button>';

                    var btnEmbarcar = '<button type="button" class="btn btn-xs btn-outline-success btn-sm btn-embarcar" ' +
                        'data-folio="' + folio + '">' +
                        '<i class="fa fa-truck-loading"></i></button>';

                    tablaEmbarques.row.add([
                        btnAccion,                  // 0 Acción
                        btnEmbarcar,                // 1 Embarcar
                        folio,                      // 2 FolioHide
                        sufijo,                     // 3 Sufijo
                        folio,                      // 4 Folio
                        fPed,                       // 5 Fecha Pedido
                        fEnt,                       // 6 Fecha Entrega
                        folio,                      // 7 Pedido
                        idDest,                     // 8 C Dest
                        razon,                      // 9 Destinatario
                        claveSuc,                   // 10 C de SUC
                        dir,                        // 11 Dirección
                        cp,                         // 12 C. Postal
                        col,                        // 13 Colonia
                        lat,                        // 14 Latitud
                        lon,                        // 15 Longitud
                        rutaTxt,                    // 16 Ruta
                        (r.almacen_label || ''),    // 17 Almacén (si lo regresas en la API)
                        rango,                      // 18 Horario Planeado
                        totalGuias,                 // 19 Total guías
                        pesoTotal,                  // 20 Peso total
                        volumen,                    // 21 Volumen
                        piezas,                     // 22 Piezas
                        totalCajas,                 // 23 Total Cajas
                        totalPallets,               // 24 Total Pallets
                        cveCli,                     // 25 Clave Cliente
                        clienteEmpresa,             // 26 Cliente | Empresa
                        razon,                      // 27 Razón Social
                        isla,                       // 28 Zona Embarque (Isla)
                        status                      // 29 Estatus
                    ]);
                });

                tablaEmbarques.draw(false);

                if (resp.kpis) {
                    $('#kpi_embarques_dia').text(
                        resp.kpis.embarques_dia !== undefined ? resp.kpis.embarques_dia : '-'
                    );
                    $('#kpi_planeados_7d').text(
                        resp.kpis.planeados_7d !== undefined ? resp.kpis.planeados_7d : '-'
                    );
                    $('#kpi_en_ruta').text(
                        resp.kpis.en_ruta !== undefined ? resp.kpis.en_ruta : '-'
                    );
                    $('#kpi_retrasados').text(
                        resp.kpis.retrasados !== undefined ? resp.kpis.retrasados : '-'
                    );
                }

            }).fail(function (xhr, status, error) {
                console.error('Fallo al cargar embarques:', status, error, xhr.responseText);
            });
        }

        /* ===========================================================
           BOTONES (stub para lógica legacy)
        ============================================================ */

        $('#btn_generar_reportes').on('click', function () {
            console.log('Generar reportes / etiquetas (pendiente)');
        });
        $('#btn_exportar_excel').on('click', function () {
            console.log('Exportar a Excel (pendiente)');
        });
        $('#btn_exportar_pdf').on('click', function () {
            console.log('Exportar a PDF (pendiente)');
        });

        $('#tabla_embarques').on('click', '.btn-embarcar', function () {
            var folio = $(this).data('folio');
            console.log('Embarcar folio:', folio);
            // Aquí se enchufa luego la lógica legacy
        });

        /* ===========================================================
           CARGA INICIAL
        ============================================================ */
        $('#almacen').prop('disabled', true);
        $('#ruta').prop('disabled', true);
        $('#isla').prop('disabled', true);

        cargarEmpresas();
        recargarEmbarques();

    });
</script>
