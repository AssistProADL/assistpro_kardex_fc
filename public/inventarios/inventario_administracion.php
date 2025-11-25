<?php
// public/procesos/inventarios/inventario_administracion.php
// Vista visual – Administración de Inventarios

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<div class="container-fluid">

    <!-- TÍTULO -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-semibold">Administración de Inventarios</h5>
            <small class="text-muted">
                Consulta y administración de inventarios generados (físicos y cíclicos).
            </small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm">
                <i class="fa fa-plus me-1"></i> Nuevo Inventario
            </button>
        </div>
    </div>

    <!-- TARJETA PRINCIPAL -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <!-- FILTROS -->
            <div class="border rounded-3 p-3 mb-3 bg-white">
                <div class="row g-2 align-items-end" style="font-size:10px;">

                    <div class="col-md-3 col-sm-6">
                        <label for="cboEmpresa" class="form-label mb-1">Empresa</label>
                        <select id="cboEmpresa" class="form-select form-select-sm">
                            <option value="">Seleccione empresa...</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label for="cboAlmacen" class="form-label mb-1">Almacén</label>
                        <select id="cboAlmacen" class="form-select form-select-sm">
                            <option value="">Seleccione almacén...</option>
                            <option value="100">(100)Producto Terminado ADVL</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label for="cboTipo" class="form-label mb-1">Tipo de Inventario</label>
                        <select id="cboTipo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="F">Físico</option>
                            <option value="C">Cíclico</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label for="cboEstado" class="form-label mb-1">Estado</label>
                        <select id="cboEstado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="A">Abierto</option>
                            <option value="E">En proceso</option>
                            <option value="C">Cerrado</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label for="txtFolio" class="form-label mb-1">Folio</label>
                        <input type="text" id="txtFolio" class="form-control form-control-sm" placeholder="Folio...">
                    </div>

                </div>

                <div class="row g-2 align-items-end mt-2" style="font-size:10px;">
                    <div class="col-md-2 col-sm-6">
                        <label for="fchInicio" class="form-label mb-1">Fecha inicio</label>
                        <input type="date" id="fchInicio" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label for="fchFin" class="form-label mb-1">Fecha fin</label>
                        <input type="date" id="fchFin" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-4 col-sm-12 d-flex justify-content-start justify-content-md-end mt-2 mt-md-0">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary">
                                <i class="fa fa-search me-1"></i> Buscar
                            </button>
                            <button type="button" class="btn btn-outline-secondary">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRILLA PRINCIPAL -->
            <div class="border rounded-3 p-2 bg-white">
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table id="grid-table" class="table table-sm table-hover align-middle mb-0" style="font-size:10px;">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Folio</th>
                            <th style="width: 90px;">Tipo</th>
                            <th>Almacén</th>
                            <th style="width: 110px;">Fecha</th>
                            <th style="width: 140px;">Usuario</th>
                            <th style="width: 90px;">Estado</th>
                            <th class="text-end" style="width: 100px;">Dif. Total</th>
                            <th class="text-center" style="width: 160px;">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Placeholder mientras no haya datos -->
                        <tr class="text-muted">
                            <td colspan="8" class="text-center py-4">
                                No hay inventarios para mostrar. Ajuste los filtros y presione
                                <strong>Buscar</strong>.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN (VISUAL) -->
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
                            <option selected>25</option>
                            <option>30</option>
                        </select>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary">
                            <i class="fa fa-file-excel-o"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary">
                            <i class="fa fa-file-pdf-o"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div> <!-- /.container-fluid -->

</div> <!-- /.content-wrapper -->
</body>
</html>
