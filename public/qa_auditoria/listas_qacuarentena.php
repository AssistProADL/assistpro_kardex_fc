<?php
// QA | CONTROL DE CALIDAD | CUARENTENA
// Listado por BL / Zona de Almacenaje
// Versión SOLO VISUAL (sin funcionalidad implementada todavía)

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- Contenido principal -->
<div class="content p-3 p-md-4" style="font-size:10px;">
    <div class="container-fluid">

        <!-- Título -->
        <div class="row mb-3">
            <div class="col-12">
                <h5 class="mb-0 fw-bold" style="color:#0F5AAD;">
                    <i class="fa fa-boxes-packing me-2"></i>QA | Control de Calidad | Cuarentena
                </h5>
                <small class="text-muted">Vista de ubicaciones con productos en QA / Cuarentena (solo visual)</small>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2">

                    <!-- Almacén -->
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">Almacén</label>
                        <select class="form-select form-select-sm" id="almacen">
                            <option value="">Seleccione</option>
                            <!-- Opciones ejemplo visual -->
                            <option value="WH100">(100) - Producto Terminado ADVL</option>
                            <option value="WH200">(200) - Materia Prima</option>
                        </select>
                    </div>

                    <!-- Zona de Almacenaje -->
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">Zona de Almacenaje</label>
                        <select class="form-select form-select-sm" id="zona_almacenaje">
                            <option value="">Seleccione</option>
                            <option value="ZG">Almacenamiento General PT</option>
                            <option value="ZMP">Materia Prima</option>
                            <option value="ZABC">Zona ABC</option>
                        </select>
                    </div>

                    <!-- Buscar BL -->
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">Buscar BL</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="buscar_bl"
                               placeholder="Buscar BL...">
                    </div>

                </div>

                <div class="row g-2 mt-2 align-items-end">

                    <!-- Motivo QA -->
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">Motivo QA</label>
                        <select class="form-select form-select-sm" id="motivo_qa">
                            <option value="">Seleccione</option>
                            <option value="1">Caducidad Límite</option>
                            <option value="2">Producto no cumple con QA</option>
                            <option value="3">Peso Incorrecto</option>
                            <option value="4">Revisión de lotes</option>
                        </select>
                    </div>

                    <!-- Botón Buscar -->
                    <div class="col-12 col-md-2">
                        <button type="button"
                                class="btn btn-sm btn-primary w-100"
                                id="btn-buscar">
                            Buscar
                        </button>
                    </div>

                    <div class="col-12 col-md-6 text-md-end mt-2 mt-md-0">
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
                    <table id="tabla-qa-bl"
                           class="table table-sm table-striped table-hover mb-0"
                           style="font-size:10px; width:100%;">
                        <thead>
                        <tr>
                            <th style="width:40px;">Acción</th>
                            <th>BL | Zona Rec</th>
                            <th>Zona de Almacenaje</th>
                            <th>Productos en QA</th>
                            <th>Motivo</th>
                            <th>Existencias</th>
                            <th>Peso Máximo</th>
                            <th>Volumen (m3)</th>
                            <th>Peso %</th>
                            <th>Volumen %</th>
                            <th>Dimensiones (Alt. X Anc. X Lar.)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Ejemplo opcional de fila, puedes dejarlo comentado si quieres la tabla vacía -->
                        <!--
                        <tr>
                            <td class="text-center">
                                <button type="button" class="btn btn-link btn-xs p-0 text-primary" title="Ver detalle">
                                    <i class="fa fa-search"></i>
                                </button>
                            </td>
                            <td>1-1-100-1-5</td>
                            <td>Almacenamiento General PT</td>
                            <td>2.00</td>
                            <td>Producto no cumple con QA</td>
                            <td>67</td>
                            <td>2000000</td>
                            <td>1440000.00</td>
                            <td>0.025</td>
                            <td>0.0000</td>
                            <td>1200.00 x 1200.00 x 1200.00</td>
                        </tr>
                        -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- /container-fluid -->
</div> <!-- /content -->

<!-- Librerías sólo para vista -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script type="text/javascript">
    $(function () {
        // DataTable sólo para presentación (sin AJAX / sin backend)
        $('#tabla-qa-bl').DataTable({
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

        // Modo sólo visual: avisar que aún no hay lógica de búsqueda
        $('#btn-buscar').on('click', function () {
            alert('Módulo en modo sólo visual. La funcionalidad de búsqueda se implementará más adelante.');
        });
    });
</script>
