<?php
// Auditoría y Empaque – Módulo QA
// Versión integrada al template global AssistPro (sidebar _menu_global.php)

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- Contenido principal -->
<div class="content p-3 p-md-4" style="font-size: 10px;">

    <div class="container-fluid">

        <!-- Título -->
        <div class="row mb-3">
            <div class="col-12">
                <h5 class="mb-0 fw-bold" style="color:#0F5AAD;">
                    <i class="fa fa-clipboard-check me-2"></i>Auditoría y Empaque
                </h5>
                <small class="text-muted">QA &bull; Revisión de pedidos antes de embarque</small>
            </div>
        </div>

        <!-- Filtros principales -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Almacén</label>
                        <select class="form-select form-select-sm" id="filtro-almacen">
                            <option value="">Seleccione un almacén</option>
                            <!-- TODO: llenar desde BD -->
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">Listos para Auditar | Auditando</label>
                        <select class="form-select form-select-sm" id="filtro-pedido">
                            <option value="">Seleccione un pedido</option>
                            <!-- TODO: llenar desde BD -->
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Áreas de Revisión</label>
                        <select class="form-select form-select-sm" id="filtro-area">
                            <option value="">Seleccione un área</option>
                            <!-- TODO: llenar desde BD -->
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-sm btn-primary" id="btn-auditar">
                            <i class="fa fa-play me-1"></i> Auditar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs superiores -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100" style="background: linear-gradient(135deg,#6a5acd,#9370db); color:#fff;">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold" style="font-size:10px;"><i class="fa fa-user me-1"></i>CLIENTE</span>
                        </div>
                        <div class="fw-bold" id="kpi-cliente" style="font-size:11px;">-</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100" style="background: linear-gradient(135deg,#004e92,#000428); color:#fff;">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold" style="font-size:10px;">PZAS FACTURA</span>
                            <i class="fa fa-file-invoice"></i>
                        </div>
                        <div class="fw-bold fs-5" id="kpi-pzas-factura">0</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100" style="background: linear-gradient(135deg,#00c9a7,#009f7f); color:#fff;">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold" style="font-size:10px;">PZAS REVISADAS</span>
                            <i class="fa fa-check-double"></i>
                        </div>
                        <div class="fw-bold fs-5" id="kpi-pzas-revisadas">0</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card shadow-sm h-100" style="background: linear-gradient(135deg,#ffb347,#ff7b00); color:#fff;">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold" style="font-size:10px;">PROGRESO</span>
                            <i class="fa fa-chart-line"></i>
                        </div>
                        <div class="fw-bold" id="kpi-progreso" style="font-size:18px;">0%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revisión de artículos -->
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
                <span class="fw-semibold" style="font-size:11px;">
                    <i class="fa fa-barcode me-1"></i>Revisión de Artículos
                </span>
            </div>
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Código de Barras</label>
                        <input type="text" class="form-control form-control-sm" id="input-codigo" placeholder="Escanee / capture código">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1">Unidad de Medida</label>
                        <select class="form-select form-select-sm" id="input-uom">
                            <option>Pieza</option>
                            <!-- TODO: llenar catálogo UOM -->
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1">Artículo</label>
                        <input type="text" class="form-control form-control-sm" id="input-articulo" placeholder="">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1">Cantidad</label>
                        <input type="number" class="form-control form-control-sm" id="input-cantidad" min="0" value="0">
                    </div>

                    <div class="col-6 col-md-2">
                        <div class="d-flex justify-content-between">
                            <label class="form-label mb-1">Lote / Serie</label>
                            <small class="text-muted mb-1">Modo Automático</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="input-lote">
                                <option value="">Seleccione un lote</option>
                            </select>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="switch-modo-auto">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row g-2 mt-3">
                    <div class="col-6 col-md-3 d-grid">
                        <button class="btn btn-sm btn-success" id="btn-revisar">
                            <i class="fa fa-check me-1"></i>Revisar Artículo
                        </button>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button class="btn btn-sm btn-secondary" id="btn-empacar">
                            <i class="fa fa-boxes-stacked me-1"></i>Empacar <span class="d-none d-md-inline">(F5)</span>
                        </button>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button class="btn btn-sm btn-info" id="btn-reiniciar">
                            <i class="fa fa-rotate-left me-1"></i>Reiniciar
                        </button>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button class="btn btn-sm btn-warning" id="btn-terminar">
                            <i class="fa fa-flag-checkered me-1"></i>Terminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs inferiores -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-2">
                        <div class="fw-semibold mb-1" style="font-size:11px;">
                            <i class="fa fa-list me-1"></i>Total artículos
                        </div>
                        <div class="fs-4 fw-bold" id="kpi-total-articulos">0</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-2">
                        <div class="fw-semibold mb-1" style="font-size:11px;">
                            <i class="fa fa-clock me-1"></i>Pendientes
                        </div>
                        <div class="fs-4 fw-bold text-warning" id="kpi-pendientes">0</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-2">
                        <div class="fw-semibold mb-1" style="font-size:11px;">
                            <i class="fa fa-check-circle me-1"></i>Revisadas
                        </div>
                        <div class="fs-4 fw-bold text-success" id="kpi-revisadas">0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progreso de auditoría -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold" style="font-size:11px;">
                        <i class="fa fa-bars-progress me-1"></i>Progreso de Auditoría
                    </span>
                    <span id="lbl-progreso" style="font-size:10px;">0%</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" id="bar-progreso" role="progressbar" style="width:0%;"></div>
                </div>
            </div>
        </div>

        <!-- Tabla principal de artículos -->
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="background:#0F5AAD;color:#fff;">
                <span style="font-size:11px;">
                    <i class="fa fa-boxes-stacked me-1"></i>Artículos a Auditar
                </span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0" id="tabla-articulos" style="font-size:10px;">
                        <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th>Lote</th>
                            <th>Serie</th>
                            <th>Caducidad</th>
                            <th>Surtidas</th>
                            <th>Pend. Auditar</th>
                            <th>Revisadas</th>
                            <th>A Empacar</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- TODO: llenar dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabla de artículos empacados -->
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="background:#00A36C;color:#fff;">
                <span style="font-size:11px;">
                    <i class="fa fa-box-open me-1"></i>Artículos Empacados
                </span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0" id="tabla-empacados" style="font-size:10px;">
                        <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th>Lote</th>
                            <th>Serie</th>
                            <th>Empacadas</th>
                            <th>No. Caja</th>
                            <th>LP Pallet</th>
                            <th>LP Contenedor</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- TODO: llenar dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- /container-fluid -->
</div> <!-- /content -->

<!-- Librerías específicas del módulo (si aún no se cargan globalmente) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    $(function () {
        // Inicializar DataTables con fuente pequeña y 25 registros
        $('#tabla-articulos, #tabla-empacados').DataTable({
            pageLength: 25,
            lengthChange: false,
            ordering: false,
            info: true,
            searching: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });

        // TODO: integrar AJAX / lógica funcional con la BD actual
        $('#btn-auditar').on('click', function () {
            // Placeholder para lógica de inicio de auditoría
            console.log('Iniciar auditoría');
        });
    });
</script>
