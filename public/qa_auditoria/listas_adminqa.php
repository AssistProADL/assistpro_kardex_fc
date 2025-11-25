<?php
// ADMINISTRADOR DE QA – CONTROL DE CALIDAD / CUARENTENA
// Versión SOLO VISUAL (sin funcionalidad / sin llamadas a BD ni clases externas)

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- Contenido principal -->
<div class="content p-3 p-md-4" style="font-size:10px;">
    <div class="container-fluid">

        <!-- Título -->
        <div class="row mb-3">
            <div class="col-12">
                <h5 class="mb-0 fw-bold" style="color:#0F5AAD;">
                    <i class="fa fa-vial-circle-check me-2"></i>QA | Control de Calidad | Cuarentena
                </h5>
                <small class="text-muted">Administrador de QA – Vista sólo visual (sin funcionalidad aún)</small>
            </div>
        </div>

        <!-- Filtros + PDF + Estadísticas -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2">

                    <!-- Almacén -->
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Almacén</label>
                        <select class="form-select form-select-sm" id="almacen">
                            <option value="">Seleccione</option>
                            <!-- Opciones de ejemplo únicamente visuales -->
                            <option value="WH100">(100) - Producto Terminado ADVL</option>
                            <option value="WH200">(200) - Materia Prima</option>
                        </select>
                    </div>

                    <!-- Artículo -->
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Artículo</label>
                        <select class="form-select form-select-sm" id="articulo" disabled>
                            <option value="">Seleccione</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select class="form-select form-select-sm" id="status">
                            <option value="">Seleccione</option>
                            <option value="Abierto">Abierto</option>
                            <option value="Cerrado">Cerrado</option>
                        </select>
                    </div>

                    <!-- Buscar Folio -->
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Buscar Folio</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="buscar_folio"
                               placeholder="Buscar Folio...">
                    </div>

                    <!-- Buscar BL + botón Buscar -->
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Buscar BL</label>
                        <div class="input-group input-group-sm">
                            <input type="text"
                                   class="form-control"
                                   id="buscar_bl"
                                   placeholder="Buscar BL...">
                            <button type="button" class="btn btn-primary" id="btn-buscar">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <div class="row mt-2 align-items-center">
                    <div class="col-12 col-md-4 d-flex align-items-center gap-2">
                        <button type="button"
                                id="reportepdf"
                                class="btn btn-sm btn-outline-primary"
                                disabled>
                            <i class="fa fa-file-pdf"></i> PDF
                        </button>
                        <small class="text-muted">Función pendiente de implementación</small>
                    </div>
                    <div class="col-12 col-md-8 text-md-end mt-2 mt-md-0">
                        <small class="text-muted">
                            | Total ubicaciones:
                            <span id="total_ubicaciones">0</span>
                            | Porcentaje de ocupación:
                            <span id="porcentaje_ocupadas">0%</span>
                            | Ubicaciones Vacías:
                            <span id="vacias">0</span>
                        </small>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tabla principal -->
        <div class="card shadow-sm">
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="tabla-qa-cc"
                           class="table table-sm table-striped table-hover mb-0"
                           style="font-size:10px; width:100%;">
                        <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th>BL</th>
                            <th>Pallet / Conten</th>
                            <th>Status</th>
                            <th>Fecha QA</th>
                            <th>Motivo Ingreso</th>
                            <th>Usuario Ingreso</th>
                            <th>Fecha Liberación</th>
                            <th>Tiempo en QA</th>
                            <th>Motivo Salida</th>
                            <th>Usuario Salida</th>
                            <th>Almacén</th>
                            <th>Zona de Almacenaje</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!--
                            Filas de ejemplo opcionales para validar el layout.
                            Puedes dejar el tbody vacío si prefieres arrancar completamente sin datos.
                        -->
                        <!--
                        <tr>
                            <td>QA202501612</td>
                            <td>47</td>
                            <td>Corona 18S/70R13 BSS SL</td>
                            <td>2-1-2-25</td>
                            <td>J0R0000001</td>
                            <td>Abierto</td>
                            <td>23-10-2025 15:41:33</td>
                            <td>Caducidad Límite</td>
                            <td>Usuario WMS Admin</td>
                            <td>23-10-2025 21:23:31</td>
                            <td>21 21:23:31</td>
                            <td>Conforme a Norma</td>
                            <td>Usuario WMS Admin</td>
                            <td>Producto Terminado ADVL</td>
                            <td>Almacenamiento General</td>
                        </tr>
                        -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- /container-fluid -->
</div> <!-- /content -->

<!-- Librerías necesarias SOLO para vista -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script type="text/javascript">
    $(function () {
        // Inicializar DataTable sólo para presentación (sin AJAX)
        $('#tabla-qa-cc').DataTable({
            pageLength: 30,
            lengthChange: false,
            ordering: false,
            searching: false,
            info: true,
            scrollX: true,
            scrollY: '50vh',
            scrollCollapse: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });

        // Controles deshabilitados (sólo visual)
        $('#btn-buscar').on('click', function () {
            alert('Módulo en modo sólo visual. La funcionalidad de búsqueda se implementará más adelante.');
        });
    });
</script>
