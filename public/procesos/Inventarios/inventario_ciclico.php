<?php
// public/procesos/inventarios/inventario_ciclico.php
// Vista visual – Programación de Inventario Cíclico

require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<div class="container-fluid">

    <!-- TÍTULO Y DESCRIPCIÓN -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-semibold">Programación de Inventario Cíclico</h5>
            <small class="text-muted">Definición de artículos y criterios para conteos cíclicos.</small>
        </div>
    </div>

    <!-- TARJETA PRINCIPAL -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <!-- FILA: ALMACÉN + BOTÓN ARTÍCULOS -->
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-4 col-sm-12">
                    <label for="cboAlmacen" class="form-label mb-1">
                        Almacén<span class="text-danger">*</span>
                    </label>
                    <select id="cboAlmacen" class="form-select form-select-sm">
                        <option value="">Seleccione un almacén...</option>
                        <!-- Opción de ejemplo, solo visual -->
                        <option value="100">(100)Producto Terminado ADVL</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <button type="button" class="btn btn-primary btn-sm w-100 mt-3 mt-md-0">
                        <i class="fa fa-search me-1"></i> Artículos
                    </button>
                </div>
            </div>

            <!-- BLOQUE: PRODUCTOS EN EL INVENTARIO -->
            <div class="border rounded-3 p-3 bg-white">
                <h6 class="fw-semibold mb-3">
                    Productos en el inventario<span class="text-danger">*</span>
                </h6>

                <!-- CHECKBOXES DE FILTRO -->
                <div class="row g-3 mb-2 small">
                    <div class="col-md-4 col-sm-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkProduccion">
                            <label class="form-check-label" for="chkProduccion">
                                Incluir Artículos en Producción
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkCuarentena">
                            <label class="form-check-label" for="chkCuarentena">
                                Incluir Artículos en Cuarentena
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkObsoletos">
                            <label class="form-check-label" for="chkObsoletos">
                                Incluir Artículos Obsoletos
                            </label>
                        </div>
                    </div>
                </div>

                <!-- TABLA PRINCIPAL -->
                <div class="table-responsive" style="max-height: 360px;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:10px;">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Clave</th>
                            <th>Descripción</th>
                            <th class="text-end" style="width: 120px;">Stock Físico</th>
                            <th class="text-center" style="width: 110px;">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Fila placeholder mientras no haya datos -->
                        <tr class="text-muted">
                            <td colspan="4" class="text-center py-4">
                                No hay productos cargados. Utilice el botón
                                <strong>Artículos</strong> para consultar.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN (VISUAL, 30 REGISTROS) -->
                <div class="d-flex justify-content-between align-items-center mt-2 small">
                    <div>
                        Página
                        <input type="number" min="1" value="1"
                               class="form-control form-control-sm d-inline-block"
                               style="width:70px;">
                        de <span>0</span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span>Registros por página:</span>
                        <select class="form-select form-select-sm" style="width:80px;">
                            <option selected>30</option>
                            <option>25</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- FIN BLOQUE PRODUCTOS -->

        </div>
    </div>

    <!-- BOTÓN PRINCIPAL ABAJO A LA DERECHA -->
    <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-primary btn-sm px-4">
            Planificar
        </button>
    </div>

</div> <!-- /.container-fluid -->

</div> <!-- /.content-wrapper -->
</body>
</html>
