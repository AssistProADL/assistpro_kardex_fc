<?php
// public/procesos/inventarios/inventario_fisico.php
// Vista visual – Programación de Inventario Físico

require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<div class="container-fluid">

    <!-- TÍTULO -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-semibold">Programación de Inventario Físico</h5>
            <small class="text-muted">Definición de ubicaciones y productos a considerar en el conteo físico.</small>
        </div>
    </div>

    <!-- TARJETA PRINCIPAL -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <!-- FILA 1: FECHA / ALMACÉN / ZONA / BL -->
            <div class="row g-2 mb-3">
                <div class="col-md-2 col-sm-6">
                    <label for="txtFecha" class="form-label mb-1">Fecha<span class="text-danger">*</span></label>
                    <input type="date" id="txtFecha" class="form-control form-control-sm">
                </div>

                <div class="col-md-3 col-sm-6">
                    <label for="cboAlmacen" class="form-label mb-1">Almacén<span class="text-danger">*</span></label>
                    <select id="cboAlmacen" class="form-select form-select-sm">
                        <option value="">Seleccione un almacén...</option>
                        <option value="100">(100)Producto Terminado ADVL</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label for="cboZona" class="form-label mb-1">Zona de Almacenaje</label>
                    <select id="cboZona" class="form-select form-select-sm">
                        <option value="">Seleccione una zona...</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="cboBL" class="form-label mb-1">BL</label>
                    <select id="cboBL" class="form-select form-select-sm">
                        <option value="">Seleccione BL...</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-12 d-flex align-items-end">
                    <div class="form-check mt-3 mt-md-0">
                        <input class="form-check-input" type="checkbox" id="chkRecepcion" disabled>
                        <label class="form-check-label small" for="chkRecepcion">
                            Áreas de Recepción
                        </label>
                    </div>
                </div>
            </div>

            <!-- FILA 2: PASILLO / RACK / NIVEL / POSICIÓN / PRODUCTO -->
            <div class="row g-2 mb-3">
                <div class="col-md-2 col-sm-6">
                    <label for="cboPasillo" class="form-label mb-1">Pasillo</label>
                    <select id="cboPasillo" class="form-select form-select-sm">
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="cboRack" class="form-label mb-1">Rack</label>
                    <select id="cboRack" class="form-select form-select-sm">
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="cboNivel" class="form-label mb-1">Nivel</label>
                    <select id="cboNivel" class="form-select form-select-sm">
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="cboPosicion" class="form-label mb-1">Posición</label>
                    <select id="cboPosicion" class="form-select form-select-sm">
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="col-md-4 col-sm-12">
                    <label for="cboProducto" class="form-label mb-1">Producto</label>
                    <select id="cboProducto" class="form-select form-select-sm">
                        <option value="">Seleccione un producto...</option>
                    </select>
                </div>
            </div>

            <hr class="my-3">

            <!-- TÍTULO UBICACIONES + BOTONES -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-semibold mb-0">Ubicaciones de Inventario Físico</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm">
                        Cargar Ubicaciones
                    </button>
                    <button type="button" class="btn btn-primary btn-sm">
                        Cargar Inventario Total
                    </button>
                </div>
            </div>

            <!-- LISTAS DE UBICACIONES (DISPONIBLES / ASIGNADAS) -->
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label mb-1 small" for="listaDisponibles">Ubicaciones Disponibles</label>
                    <div id="listaDisponibles" class="border rounded-3 bg-white p-2" style="min-height: 220px; max-height: 260px; overflow-y: auto; font-size:10px;">
                        <ul class="list-unstyled mb-0">
                            <li class="text-muted text-center py-3">
                                No hay ubicaciones cargadas.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-2 d-flex flex-column justify-content-center align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm px-3">
                        &gt;&gt;
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm px-3">
                        &lt;&lt;
                    </button>
                </div>

                <div class="col-md-5">
                    <label class="form-label mb-1 small" for="listaAsignadas">Ubicaciones Asignadas</label>
                    <div id="listaAsignadas" class="border rounded-3 bg-white p-2" style="min-height: 220px; max-height: 260px; overflow-y: auto; font-size:10px;">
                        <ul class="list-unstyled mb-0">
                            <li class="text-muted text-center py-3">
                                No hay ubicaciones asignadas.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- BOTÓN PLANIFICAR -->
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-primary btn-sm px-4">
                    Planificar Inventario
                </button>
            </div>

        </div>
    </div>

</div> <!-- /.container-fluid -->

</div> <!-- /.content-wrapper -->
</body>
</html>
