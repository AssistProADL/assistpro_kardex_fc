@extends('layouts.master')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('assets/css/ajuste-existencias.css') ?>">

    <style>
        :root {
            --ap-primary: #0d6efd;
            --ap-primary-dark: #0a58ca;
            --ap-secondary: #6c757d;
            --ap-success: #198754;
            --ap-info: #0dcaf0;
            --ap-warning: #ffc107;
            --ap-danger: #dc3545;
            --ap-light: #f8f9fa;
            --ap-dark: #212529;
        }

        .ap-page-header {
            background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(13, 110, 253, 0.15);
        }

        .ap-page-header-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-primary {
            background-color: var(--ap-primary);
            border-color: var(--ap-primary);
        }

        .btn-primary:hover {
            background-color: var(--ap-primary-dark);
            border-color: var(--ap-primary-dark);
        }

        .ap-kpi-icon-primary {
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--ap-primary);
        }

        /* Modal Styles */
        .modal-header {
            background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%);
            color: white;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
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
                    <input type="text" class="form-control" id="buscar_global" placeholder="Buscar en todo el inventario..."
                        style="min-width: 280px;">
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
                                        <select class="form-select ap-form-control select2" id="almacen">
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="zona" class="ap-label">
                                            <i class="fa fa-map-marked-alt"></i> Zona
                                        </label>
                                        <select class="form-select ap-form-control select2" id="zona" disabled>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="tipo" class="ap-label">
                                            <i class="fa fa-tag"></i> Tipo
                                        </label>
                                        <select class="form-select ap-form-control select2" id="tipo">
                                            <option value="">Todos</option>
                                            <option value="L">Libre</option>
                                            <option value="R">Restringida</option>
                                            <option value="Q">Cuarentena</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="estado" class="ap-label">
                                            <i class="fa fa-battery-half"></i> Estado
                                        </label>
                                        <select class="form-select ap-form-control select2" id="estado">
                                            <option value="">Todos</option>
                                            <option value="vacio">Vacío</option>
                                            <option value="ocupado">Ocupado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="activo" class="ap-label">
                                            <i class="fa fa-power-off"></i> Activo
                                        </label>
                                        <select class="form-select ap-form-control select2" id="activo">
                                            <option value="">Todos</option>
                                            <option value="1">Si</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="articulo" class="ap-label">
                                            <i class="fa fa-cube"></i> Artículo
                                        </label>
                                        <input type="text" class="form-control ap-form-control select2" id="articulo"
                                            placeholder="Código de artículo...">
                                    </div>
                                    <div>
                                        <label for="buscar_bl" class="ap-label">
                                            <i class="fa fa-barcode"></i> BL
                                        </label>
                                        <input type="text" class="form-control ap-form-control select2" id="buscar_bl"
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
                            <th>Acción</th>
                            <th style="width: 50px;"></th> <!-- Icono -->
                            <th>Almacén / Zona</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>BL</th>
                            <th>Zona de Almacenaje</th>
                            <th>Activo</th>
                            <th>Peso%</th>
                            <th>Volumen%</th>
                            <th>Dimensiones (Alt. X Anc. X Lar. )</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Moderno -->
    <div class="modal modal-xl fade" id="modalDetalle" tabindex="-1">
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

    <!-- Modal Motivos -->
    <div class="modal modal-xl fade" id="modalMotivos" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%); color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title"><i class="fa fa-clipboard-list me-2"></i>Seleccionar Motivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="ap-label">
                        <i class="fa fa-info-circle"></i> Motivo del Ajuste
                    </label>
                    <select class="form-select ap-form-control select2" id="motivo_selector">
                        <option value="">Seleccione un motivo...</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn_guardar_ajuste">
                        <i class="fa fa-save"></i> Guardar Ajuste
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Lista Genérica -->
    <div class="modal modal-xl fade" id="modalLista" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%); color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title" id="modalListaTitle">Lista</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush" id="modalListaContent">
                        <!-- Items dinámicos -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
    <script src="<?= asset('assets/js/ajustes-existencias.js') ?>"></script>
@endsection