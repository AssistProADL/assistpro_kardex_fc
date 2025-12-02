@extends('layouts.master')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        /* Modern 2025 UX/UI Design */
        :root {
            --ap-primary: #0F5AAD;
            --ap-primary-dark: #174f8f;
            --ap-success: #198754;
            --ap-info: #0dcaf0;
            --ap-warning: #ffc107;
            --ap-danger: #dc3545;
            --ap-gray-50: #f8f9fa;
            --ap-gray-100: #f0f2f5;
            --ap-gray-200: #e1e5eb;
            --ap-gray-600: #6c757d;
            --ap-gray-900: #212529;
            --ap-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --ap-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --ap-shadow-lg: 0 10px 24px rgba(0, 0, 0, 0.12);
            --ap-radius: 12px;
            --ap-radius-lg: 16px;
        }

        body {
            background: var(--ap-gray-50);
        }

        /* Cards con glassmorphism sutil */
        .ap-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--ap-radius);
            box-shadow: var(--ap-shadow-sm);
            border: 1px solid var(--ap-gray-200);
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .ap-card:hover {
            box-shadow: var(--ap-shadow-md);
            transform: translateY(-2px);
        }

        .ap-card-body {
            padding: 20px;
        }

        /* Header moderno */
        .ap-page-header {
            background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%);
            border-radius: var(--ap-radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--ap-shadow-md);
            color: white;
        }

        .ap-page-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        .ap-page-header-icon i {
            color: white;
            font-size: 24px;
        }

        .ap-page-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .ap-page-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 4px 0 0 0;
        }

        /* Labels modernos */
        .ap-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--ap-gray-900);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ap-label i {
            font-size: 14px;
            color: var(--ap-primary);
        }

        /* Form controls mejorados */
        .ap-form-control {
            font-size: 14px;
            height: 42px;
            padding: 8px 14px;
            border: 2px solid var(--ap-gray-200);
            border-radius: 10px;
            transition: all 0.2s ease;
            background: white;
        }

        .ap-form-control:focus {
            border-color: var(--ap-primary);
            box-shadow: 0 0 0 4px rgba(15, 90, 173, 0.1);
            outline: none;
        }

        .ap-form-control:disabled {
            background: var(--ap-gray-50);
            cursor: not-allowed;
        }

        /* KPI Cards Premium */
        .ap-kpi-card {
            border-radius: var(--ap-radius);
            border: 1px solid var(--ap-gray-200);
            background: white;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--ap-shadow-sm);
            height: 90px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .ap-kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--kpi-color) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .ap-kpi-card:hover {
            box-shadow: var(--ap-shadow-md);
            transform: translateY(-4px);
        }

        .ap-kpi-card:hover::before {
            opacity: 1;
        }

        .ap-kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .ap-kpi-icon-primary {
            background: linear-gradient(135deg, rgba(15, 90, 173, 0.15) 0%, rgba(15, 90, 173, 0.05) 100%);
            color: var(--ap-primary);
        }

        .ap-kpi-icon-success {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.15) 0%, rgba(25, 135, 84, 0.05) 100%);
            color: var(--ap-success);
        }

        .ap-kpi-icon-info {
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.15) 0%, rgba(13, 202, 240, 0.05) 100%);
            color: var(--ap-info);
        }

        .ap-kpi-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--ap-gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ap-kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--ap-gray-900);
            line-height: 1;
            margin-top: 4px;
        }

        /* Accordion moderno */
        .ap-accordion-main .accordion-button {
            background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%);
            color: white;
            font-size: 14px;
            font-weight: 600;
            padding: 16px 20px;
            border-radius: var(--ap-radius) var(--ap-radius) 0 0;
            border: none;
            box-shadow: none;
        }

        .ap-accordion-main .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, var(--ap-primary-dark) 0%, var(--ap-primary) 100%);
        }

        .ap-accordion-main .accordion-button::after {
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }

        .ap-accordion-main .accordion-button:not(.collapsed)::after {
            transform: rotate(180deg);
        }

        .ap-accordion-main small {
            font-weight: 400;
            opacity: 0.9;
        }

        .ap-accordion-main .accordion-body {
            padding: 0;
        }

        /* Filtros grid moderno */
        .ap-filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        /* Botones modernos */
        .ap-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .ap-btn-primary {
            background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(15, 90, 173, 0.2);
        }

        .ap-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 90, 173, 0.3);
        }

        .ap-btn-light {
            background: white;
            color: var(--ap-gray-900);
            border: 2px solid var(--ap-gray-200);
        }

        .ap-btn-light:hover {
            background: var(--ap-gray-50);
            border-color: var(--ap-gray-600);
        }

        .ap-filters-actions {
            border-top: 1px solid var(--ap-gray-200);
            margin-top: 20px;
            padding-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* DataTable moderno */
        .ap-table-container {
            background: white;
            border-radius: var(--ap-radius);
            padding: 20px;
            box-shadow: var(--ap-shadow-sm);
            overflow-x: auto;
        }

        table.dataTable {
            border-collapse: separate;
            border-spacing: 0;
        }

        table.dataTable thead th {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--ap-gray-900);
            background: var(--ap-gray-50);
            border-bottom: 2px solid var(--ap-gray-200);
            padding: 14px 12px;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            font-size: 13px;
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid var(--ap-gray-100);
        }

        table.dataTable tbody tr {
            transition: all 0.2s ease;
        }

        table.dataTable tbody tr:hover {
            background: var(--ap-gray-50);
        }

        /* Badge moderno */
        .ap-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ap-badge-success {
            background: rgba(25, 135, 84, 0.1);
            color: var(--ap-success);
        }

        .ap-badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }

        .ap-badge-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--ap-danger);
        }

        .ap-badge-info {
            background: rgba(13, 202, 240, 0.1);
            color: var(--ap-info);
        }

        /* Botón de acción en tabla */
        .ap-btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ap-btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--ap-shadow-sm);
        }

        /* Search bar moderno */
        .ap-search-bar {
            position: relative;
        }

        .ap-search-bar input {
            padding-left: 40px;
            border-radius: 12px;
            border: 2px solid var(--ap-gray-200);
            transition: all 0.2s ease;
        }

        .ap-search-bar input:focus {
            border-color: var(--ap-primary);
            box-shadow: 0 0 0 4px rgba(15, 90, 173, 0.1);
        }

        .ap-search-bar i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ap-gray-600);
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .ap-filters-grid {
                grid-template-columns: 1fr;
            }

            .ap-kpi-card {
                height: auto;
                min-height: 80px;
            }

            .ap-page-header {
                padding: 16px;
            }

            .ap-page-title {
                font-size: 20px;
            }

            .ap-table-container {
                padding: 12px;
            }

            table.dataTable thead th,
            table.dataTable tbody td {
                font-size: 11px;
                padding: 8px 6px;
            }

            .ap-btn-action {
                padding: 4px 8px;
                font-size: 11px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
        <!-- HEADER MODERNO -->
        <div class="ap-page-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="ap-page-header-icon">
                        <i class="fa fa-boxes"></i>
                    </div>
                    <div>
                        <h1 class="ap-page-title">Ajustes de Existencia</h1>
                        <p class="ap-page-subtitle mb-0">
                            <i class="fa fa-info-circle me-1"></i>
                            Gestión inteligente de inventario por ubicación
                        </p>
                    </div>
                </div>
                <div class="ap-search-bar">
                    <i class="fa fa-search"></i>
                    <input type="text" class="form-control" id="buscar_global"
                        placeholder="Buscar en todo el inventario..." style="min-width: 280px;">
                </div>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="row mb-4 g-3">
            <div class="col-12 col-md-4">
                <div class="ap-kpi-card" style="--kpi-color: var(--ap-primary)">
                    <div class="ap-kpi-icon ap-kpi-icon-primary">
                        <i class="fa fa-th"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="ap-kpi-label">Total Ubicaciones</div>
                        <div class="ap-kpi-value" id="kpi_total">-</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="ap-kpi-card" style="--kpi-color: var(--ap-success)">
                    <div class="ap-kpi-icon ap-kpi-icon-success">
                        <i class="fa fa-chart-pie"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="ap-kpi-label">Ocupación</div>
                        <div class="ap-kpi-value" id="kpi_ocupacion">-</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="ap-kpi-card" style="--kpi-color: var(--ap-info)">
                    <div class="ap-kpi-icon ap-kpi-icon-info">
                        <i class="fa fa-inbox"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="ap-kpi-label">Ubicaciones Vacías</div>
                        <div class="ap-kpi-value" id="kpi_vacias">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCORDION DE FILTROS -->
        <div class="accordion ap-accordion-main mb-4" id="accordionFiltros">
            <div class="accordion-item border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFiltros" aria-expanded="true">
                        <i class="fa fa-sliders-h me-2"></i>
                        <span>Filtros Avanzados</span>
                        <small class="ms-2 d-none d-md-inline">
                            Configure los criterios de búsqueda
                        </small>
                    </button>
                </h2>
                <div id="collapseFiltros" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="ap-card mb-0" style="border-radius: 0 0 12px 12px; border-top: 0;">
                            <div class="ap-card-body">
                                <div class="ap-filters-grid">
                                    <div>
                                        <label for="almacen" class="ap-label">
                                            <i class="fa fa-warehouse"></i> Almacén
                                        </label>
                                        <select class="form-select ap-form-control" id="almacen">
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="zona" class="ap-label">
                                            <i class="fa fa-map-marked-alt"></i> Zona
                                        </label>
                                        <select class="form-select ap-form-control" id="zona" disabled>
                                            <option value="">Todas</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="tipo" class="ap-label">
                                            <i class="fa fa-tag"></i> Tipo
                                        </label>
                                        <select class="form-select ap-form-control" id="tipo">
                                            <option value="">Todos</option>
                                            <option value="L">Libre</option>
                                            <option value="R">Restringida</option>
                                            <option value="Q">Cuarentena</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="articulo" class="ap-label">
                                            <i class="fa fa-cube"></i> Artículo
                                        </label>
                                        <input type="text" class="form-control ap-form-control" id="articulo"
                                            placeholder="Código de artículo...">
                                    </div>
                                    <div>
                                        <label for="buscar_bl" class="ap-label">
                                            <i class="fa fa-barcode"></i> BL
                                        </label>
                                        <input type="text" class="form-control ap-form-control" id="buscar_bl"
                                            placeholder="Código BL...">
                                    </div>
                                </div>

                                <div class="ap-filters-actions">
                                    <button type="button" class="ap-btn ap-btn-light" id="btn_limpiar">
                                        <i class="fa fa-redo"></i> Limpiar
                                    </button>
                                    <button type="button" class="ap-btn ap-btn-primary" id="btn_aplicar">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATATABLE -->
        <div class="ap-table-container">
            <div class="table-responsive">
                <table id="tblAjustes" class="table table-hover nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>BL</th>
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Contenedor</th>
                            <th>LP</th>
                            <th>Dimensiones</th>
                            <th>Vol. m³</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Moderno -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%); color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title"><i class="fa fa-box-open me-2"></i>Detalle de Ubicación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            let table;
            let currentFilters = {};

            // Cargar almacenes
            loadAlmacenes();

            // Eventos
            $('#almacen').on('change', function() {
                const almacen = $(this).val();
                if (almacen) {
                    loadZonas(almacen);
                    $('#zona').prop('disabled', false);
                } else {
                    $('#zona').html('<option value="">Todas</option>').prop('disabled', true);
                }
            });

            $('#btn_aplicar').on('click', applyFilters);
            $('#btn_limpiar').on('click', clearFilters);

            function applyFilters() {
                currentFilters = {
                    almacen: $('#almacen').val(),
                    almacenaje: $('#zona').val(),
                    tipo: $('#tipo').val(),
                    articulo: $('#articulo').val(),
                    search: $('#buscar_bl').val()
                };
                loadKPIs();
                if (table) {
                    table.ajax.reload();
                } else {
                    initDataTable();
                }
            }

            function clearFilters() {
                $('#almacen, #zona, #tipo, #articulo, #buscar_bl').val('');
                $('#zona').prop('disabled', true);
                currentFilters = {};
                if (table) table.ajax.reload();
            }

            function loadAlmacenes() {
                $.get('/assistpro_kardex_fc/public/api/almacen/predeterminado', function(resp) {
                    if (resp.success && resp.data.almacenes) {
                        const $select = $('#almacen');
                        resp.data.almacenes.forEach(alm => {
                            $select.append(`<option value="${alm.clave}">${alm.nombre}</option>`);
                        });
                    }
                });
            }

            function loadZonas(almacen) {
                $.get('/assistpro_kardex_fc/public/api/almacen/zonas', {
                    almacen
                }, function(resp) {
                    if (resp.success && resp.data) {
                        const $select = $('#zona');
                        $select.html('<option value="">Todas</option>');
                        resp.data.forEach(zona => {
                            $select.append(
                                `<option value="${zona.cve_almac}">${zona.des_almac}</option>`);
                        });
                    }
                });
            }

            function loadKPIs() {
                $.get('/assistpro_kardex_fc/public/api/ajustes/existencias/kpis', currentFilters, function(
                    resp) {
                    if (resp.success) {
                        $('#kpi_total').text(resp.data.total_ubicaciones || 0);
                        $('#kpi_ocupacion').text((resp.data.porcentaje_ocupacion || 0) + '%');
                        $('#kpi_vacias').text(resp.data.vacias || 0);
                    }
                });
            }

            function initDataTable() {
                table = $('#tblAjustes').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    scrollX: true,
                    ajax: {
                        url: '/assistpro_kardex_fc/public/api/ajustes/existencias',
                        data: function(d) {
                            return $.extend({}, d, currentFilters, {
                                limit: d.length,
                                offset: d.start
                            });
                        }
                    },
                    columns: [{
                            data: 'BL'
                        },
                        {
                            data: null,
                            render: (d) =>
                                `${d.pasillo || '-'} / ${d.rack || '-'} / ${d.nivel || '-'} / ${d.posicion || '-'}`
                        },
                        {
                            data: 'tipo_ubicacion',
                            render: (d) => {
                                const badges = {
                                    'Libre': 'success',
                                    'Restringida': 'warning',
                                    'Cuarentena': 'danger'
                                };
                                return `<span class="ap-badge ap-badge-${badges[d] || 'info'}">${d}</span>`;
                            }
                        },
                        {
                            data: 'clave_contenedor'
                        },
                        {
                            data: 'CveLP'
                        },
                        {
                            data: null,
                            render: (d) =>
                                `${d.num_alto}x${d.num_ancho}x${d.num_largo} mm`
                        },
                        {
                            data: 'volumen_m3'
                        },
                        {
                            data: null,
                            render: (d) =>
                                `<button class="btn btn-primary btn-sm ap-btn-action btn-detalle" data-id="${d.idy_ubica}" data-area="${d.AreaProduccion}">
                                    <i class="fa fa-eye"></i> Ver Detalle
                                </button>`
                        }
                    ],
                    pageLength: 25,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-MX.json'
                    }
                });

                $('#tblAjustes').on('click', '.btn-detalle', function() {
                    const id = $(this).data('id');
                    const area = $(this).data('area');
                    loadDetalle(id, area);
                });
            }

            function loadDetalle(ubicacion, areaProduccion) {
                $('#modalDetalle').modal('show');
                $.get('/assistpro_kardex_fc/public/api/ajustes/existencias/detalles', {
                    ubicacion,
                    almacen: currentFilters.almacen,
                    areaProduccion
                }, function(resp) {
                    if (resp.success) {
                        renderDetalle(resp.data);
                    }
                });
            }

            function renderDetalle(items) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th>Lote</th>
                                    <th>Caducidad</th>
                                    <th>Existencia</th>
                                    <th>Contenedor</th>
                                    <th>Proveedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>`;

                items.forEach(item => {
                    html += `<tr>
                        <td><strong>${item.cve_articulo}</strong></td>
                        <td>${item.descripcion}</td>
                        <td>${item.lote || '-'}</td>
                        <td>${item.caducidad || '-'}</td>
                        <td><span class="badge bg-primary">${item.Existencia_Total}</span></td>
                        <td>${item.contenedor || '-'}</td>
                        <td>${item.proveedor || '-'}</td>
                        <td>
                            <button class="btn btn-warning btn-sm ap-btn-action">
                                <i class="fa fa-edit"></i> Ajustar
                            </button>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                $('#detalleContent').html(html);
            }
        });
    </script>
@endsection