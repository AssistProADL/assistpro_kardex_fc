<?php
// public/pedidos/picking_admin.php
// Administrador de Picking (maquetación + consumo de filtros_assistpro.php y th_pedido)

$TITLE = 'Administrador de Picking';
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

// ================== Parámetros por defecto (últimos 7 días, solo visual) ==================
$FECHA_FIN = date('Y-m-d');
$FECHA_INI = date('Y-m-d', strtotime('-7 days'));

// ================== Consulta básica a th_pedido (solo header, sin JOIN pesados) ==================
$pedidos = [];
$totalPedidos = 0;

try {
    $sql = "
        SELECT
            h.id_pedido,
            h.Fol_folio,
            h.TipoPedido,
            h.status,
            h.cve_almac,
            a.clave AS clave_almacen,
            h.ruta,
            h.Cve_clte,
            h.Fec_Pedido,
            h.Fec_Entrega,
            h.rango_hora,
            h.ID_Tipoprioridad,
            h.fuente_id,
            h.fuente_detalle,
            h.Observaciones,
            h.Cve_Usuario
        FROM th_pedido h
        LEFT JOIN c_almacenp a 
            ON (a.clave = h.cve_almac OR a.id = h.cve_almac)
        WHERE IFNULL(h.Activo,1) = 1
        ORDER BY h.Fec_Pedido DESC, h.Fol_folio DESC
        LIMIT 25
    ";

    $pedidos = db_all($sql);
    $totalPedidos = is_array($pedidos) ? count($pedidos) : 0;
} catch (Throwable $e) {
    $pedidos = [];
    $totalPedidos = 0;
    // error_log('Error cargando pedidos picking_admin: ' . $e->getMessage());
}

?>
<style>
    .status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #6c757d;
    }
    .status-verde { background-color: #28a745; }
    .status-amarillo { background-color: #ffc107; }
    .status-rojo { background-color: #dc3545; }

    .btn-acciones {
        padding: 0 6px;
    }

    .filtro-toggle-bar {
        background-color: #f0f4fb;
        border: 1px solid #d0d7e2;
        color: #0F5AAD;
        font-weight: 600;
        font-size: 10px;
        border-radius: 4px;
    }
    .filtro-toggle-bar .chevron-icon {
        transition: transform 0.2s ease-in-out;
    }
    .filtro-toggle-bar.collapsed .chevron-icon {
        transform: rotate(180deg);
    }

    .card-tipo-pedido {
        border-radius: 6px;
        border: 1px solid #dde2ee;
        border-top: 3px solid #0F5AAD;
        background: #f8f9fc;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .card-tipo-pedido:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        border-color: #00A3E0;
    }
    .card-tipo-pedido-icon {
        font-size: 16px;
        margin-bottom: 2px;
        color: #0F5AAD;
    }
    .card-tipo-pedido-label {
        font-size: 9px;
        color: #6c757d;
    }
    .kpi-card {
        border-radius: 6px;
        border: 1px solid #dde2ee;
        background: linear-gradient(135deg, #ffffff 0%, #f3f7ff 100%);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .kpi-card .kpi-value {
        font-size: 18px;
        font-weight: 600;
        color: #0F5AAD;
    }

    #tblPickingAdmin {
        font-size: 10px;
    }
    #tblPickingAdmin th,
    #tblPickingAdmin td {
        white-space: nowrap;
        vertical-align: middle;
    }

    #modalDetallePedido .table-sm th,
    #modalDetallePedido .table-sm td {
        font-size: 10px;
        white-space: nowrap;
    }
</style>

<div class="container-fluid mt-3 mb-4" style="font-size:10px;">

    <div class="row mb-1">
        <div class="col-12">
            <h4 class="mb-0" style="font-weight:600;">Administrador de Picking</h4>
            <small class="text-muted">
                Planeación, asignación y control de pedidos para surtido.
            </small>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12">
            <button class="btn btn-sm w-100 d-flex justify-content-between align-items-center filtro-toggle-bar"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#filtrosPicking"
                    aria-expanded="true"
                    aria-controls="filtrosPicking">
                <span>
                    <i class="fa fa-filter me-2"></i>
                    Filtros de picking
                </span>
                <i class="fa fa-chevron-up chevron-icon"></i>
            </button>
        </div>
    </div>

    <!-- ========== SECCIÓN 1: FILTROS (COLAPSABLE) ========== -->
    <div class="row">
        <div class="col-12">
            <div id="filtrosPicking" class="collapse show">
                <div class="card shadow-sm" style="border-radius:6px;">
                    <div class="card-body py-2">

                        <div class="row align-items-end">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Almacén</label>
                                <select id="filtro_almacen" class="form-select form-select-sm">
                                    <option value="">Seleccione un almacén</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Status de Orden</label>
                                <select id="filtro_status" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="LISTO">Listo por asignar</option>
                                    <option value="SURTIENDO">Surtiendo</option>
                                    <option value="PEND_AUDITAR">Pendiente de auditar</option>
                                    <option value="AUDITANDO">Auditando</option>
                                    <option value="PEND_EMPAQUE">Pendiente de empaque</option>
                                    <option value="EMPACANDO">Empacando</option>
                                    <option value="PEND_EMBARQUE">Pendiente de embarque</option>
                                    <option value="EMBARCANDO">Embarcando</option>
                                    <option value="ENVIADO">Enviado</option>
                                    <option value="ENTREGADO">Entregado</option>
                                    <option value="CANCELADO">Cancelado</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Fecha Inicio</label>
                                <input id="filtro_fecha_ini" type="date" class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($FECHA_INI); ?>">
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Fecha Fin</label>
                                <input id="filtro_fecha_fin" type="date" class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($FECHA_FIN); ?>">
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                                <label class="form-label mb-1">Buscar / Actualizar</label>
                                <div class="input-group input-group-sm">
                                    <input id="filtro_buscar" type="text" class="form-control" placeholder="Folio, contenedor, pallet...">
                                    <button id="btnBuscar" class="btn text-white" style="background-color:#0F5AAD;">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <button id="btnLimpiarFiltros" class="btn btn-sm btn-outline-secondary w-100 mt-1">
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>

                        <div class="row align-items-end mt-1">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Ruta</label>
                                <select id="filtro_ruta" class="form-select form-select-sm">
                                    <option value="">Seleccione una ruta</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Cliente</label>
                                <input id="filtro_cliente"
                                       type="text"
                                       class="form-control form-control-sm"
                                       placeholder="Nombre o código de cliente"
                                       list="clientes_list">
                                <datalist id="clientes_list"></datalist>
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Ciudad</label>
                                <input id="filtro_ciudad" type="text" class="form-control form-control-sm" placeholder="Ciudad">
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Ventana de Entrega</label>
                                <div class="input-group input-group-sm">
                                    <input id="filtro_hora_ini" type="time" class="form-control">
                                    <span class="input-group-text">a</span>
                                    <input id="filtro_hora_fin" type="time" class="form-control">
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Prioridad</label>
                                <select id="filtro_prioridad" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <option value="ALTA">Alta</option>
                                    <option value="MEDIA">Media</option>
                                    <option value="BAJA">Baja</option>
                                </select>
                            </div>
                        </div>

                        <div class="row align-items-center mt-1">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="form-label mb-1">Fuente</label>
                                <select id="filtro_fuente" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <option value="INTERNO">Interno (OT / Reabasto / Traslado)</option>
                                    <option value="POS">POS</option>
                                    <option value="PORTAL">Portal Clientes</option>
                                    <option value="RUTA">Pedidos de Rutas</option>
                                    <option value="WS">Interfase / WS</option>
                                    <option value="CROSS">CrossDocking Layout</option>
                                    <option value="DIRIGIDO">Pedido Dirigido</option>
                                </select>
                            </div>

                            <div class="col-lg-9 col-md-8 col-sm-12 mb-2">
                                <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
                                    <span>Semáforo:</span>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="chkSemVerde" checked>
                                        <label class="form-check-label" for="chkSemVerde">
                                            Verde (full existencias)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="chkSemAmarillo" checked>
                                        <label class="form-check-label" for="chkSemAmarillo">
                                            Amarillo (parcial)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="chkSemRojo" checked>
                                        <label class="form-check-label" for="chkSemRojo">
                                            Rojo (sin existencias)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- card-body -->
                </div>
            </div>
        </div>
    </div>

    <!-- ========== SECCIÓN 2: CARDS TIPO DE PEDIDO + KPIs ========== -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-2">

                    <div class="d-flex flex-nowrap overflow-auto mb-2" style="gap:8px;">
                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-shopping-cart"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Pedidos General</div>
                                <div class="kpi-count kpi-value" data-tipo="Pedidos General">0</div>
                            </div>
                        </div>

                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-tools"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Orden de Trabajo</div>
                                <div class="kpi-count kpi-value" data-tipo="Orden de Trabajo">0</div>
                            </div>
                        </div>

                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-exchange-alt"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Traslado int. almacenes</div>
                                <div class="kpi-count kpi-value" data-tipo="Traslado int. almacenes">0</div>
                            </div>
                        </div>

                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-truck-loading"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Traslado ext. almacenes</div>
                                <div class="kpi-count kpi-value" data-tipo="Traslado ext. almacenes">0</div>
                            </div>
                        </div>

                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-layer-group"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Wave Set (Ola)</div>
                                <div class="kpi-count kpi-value" data-tipo="Wave Set (Ola)">0</div>
                            </div>
                        </div>

                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-project-diagram"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Ola de Olas</div>
                                <div class="kpi-count kpi-value" data-tipo="Ola de Olas">0</div>
                            </div>
                        </div>

                        <div class="card shadow-sm card-tipo-pedido" style="cursor:pointer; min-width:160px;">
                            <div class="card-body py-2 text-center">
                                <div class="card-tipo-pedido-icon">
                                    <i class="fa fa-random"></i>
                                </div>
                                <div class="card-tipo-pedido-label">Cross Docking</div>
                                <div class="kpi-count kpi-value" data-tipo="Cross Docking">0</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4 col-sm-4 col-12">
                            <div class="card shadow-sm text-center kpi-card">
                                <div class="card-body py-2">
                                    <div class="text-muted">Pedidos activos (máx. 25)</div>
                                    <div id="kpi_pedidos" class="kpi-value">
                                        <?php echo (int)$totalPedidos; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-12">
                            <div class="card shadow-sm text-center kpi-card">
                                <div class="card-body py-2">
                                    <div class="text-muted">En picking</div>
                                    <div id="kpi_enpicking" class="kpi-value">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-12">
                            <div class="card shadow-sm text-center kpi-card">
                                <div class="card-body py-2">
                                    <div class="text-muted">Completados hoy</div>
                                    <div id="kpi_completados" class="kpi-value">0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- card-body -->
            </div>
        </div>
    </div>

    <!-- ========== SECCIÓN 3: GRILLA PRINCIPAL ========== -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius:6px;">
                <div class="card-body py-2">

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2" style="gap:10px;">
                        <div class="d-flex align-items-center" style="gap:15px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkAsignarTodo">
                                <label class="form-check-label" for="chkAsignarTodo">
                                    Asignar Todo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkPlanificarTodo">
                                <label class="form-check-label" for="chkPlanificarTodo">
                                    Planificar Todo | Cambiar Status
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap" style="gap:10px;">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fa fa-file-alt me-1"></i>Reportes
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Reporte de Salidas</a></li>
                                    <li><a class="dropdown-item" href="#">Resumen Pedidos x Artículos</a></li>
                                    <li><a class="dropdown-item" href="#">Resumen Pedidos</a></li>
                                </ul>
                            </div>

                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-columns me-1"></i>Configurar columnas
                            </button>
                        </div>
                    </div>

                    <p class="text-muted mb-2" style="font-size:9px;">
                        Mostrando hasta 25 pedidos activos más recientes (sin filtrar aún por fecha ni otros criterios).
                    </p>

                    <div class="table-responsive" style="max-height:55vh;overflow:auto;">
                        <table id="tblPickingAdmin" class="table table-striped table-bordered table-hover table-sm align-middle" style="min-width:1200px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:30px;">Asignar</th>
                                    <th>Status</th>
                                    <th>Acciones</th>
                                    <th>Folio Pedido</th>
                                    <th>Ola / WaveSet</th>
                                    <th>Fuente</th>
                                    <th>Tipo Pedido</th>
                                    <th>Status Pedido</th>
                                    <th>Almacén</th>
                                    <th>Ruta</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha Pedido</th>
                                    <th>Fecha Compromiso</th>
                                    <th>Ventana Entrega</th>
                                    <th>Prioridad</th>
                                    <th>Líneas</th>
                                    <th>Piezas</th>
                                    <th>Peso</th>
                                    <th>Volumen</th>
                                    <th>Picker(s)</th>
                                    <th>Inicio Picking</th>
                                    <th>Fin Picking</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($totalPedidos === 0): ?>
                                    <tr>
                                        <td colspan="23" class="text-center text-muted">
                                            No se encontraron pedidos activos en la tabla th_pedido.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pedidos as $p): ?>
                                        <?php
                                            $fecPedRaw = $p['Fec_Pedido'] ?? '';
                                            $fecComRaw = $p['Fec_Entrega'] ?? '';
                                            $fecPed = '';
                                            $fecCom = '';
                                            if ($fecPedRaw && strtotime($fecPedRaw) !== false) {
                                                $fecPed = date('d/m/Y', strtotime($fecPedRaw));
                                            }
                                            if ($fecComRaw && strtotime($fecComRaw) !== false) {
                                                $fecCom = date('d/m/Y', strtotime($fecComRaw));
                                            }
                                        ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input chk-pedido">
                                            </td>
                                            <td class="text-center">
                                                <span class="status-dot"></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary btn-acciones dropdown-toggle"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="javascript:void(0);"
                                                               class="dropdown-item link-ver-detalle"
                                                               data-folio="<?php echo htmlspecialchars($p['Fol_folio']); ?>">
                                                                <i class="fa fa-eye me-1"></i>Ver detalle
                                                            </a>
                                                        </li>
                                                        <li><a class="dropdown-item" href="#"><i class="fa fa-list-ul me-1"></i>Ver líneas del pedido</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fa fa-exchange-alt me-1"></i>Cambiar status</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fa fa-cut me-1"></i>Dividir pedido</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fa fa-check-circle me-1"></i>Validar existencias</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fa fa-history me-1"></i>Ver trazabilidad</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($p['Fol_folio']); ?></td>
                                            <td></td>
                                            <td><?php echo htmlspecialchars($p['fuente_detalle'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['TipoPedido'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['status'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['clave_almacen'] ?? $p['cve_almac'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['ruta'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['Cve_clte'] ?? ''); ?></td>
                                            <td></td>
                                            <td><?php echo htmlspecialchars($fecPed); ?></td>
                                            <td><?php echo htmlspecialchars($fecCom); ?></td>
                                            <td><?php echo htmlspecialchars($p['rango_hora'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($p['ID_Tipoprioridad'] ?? ''); ?></td>
                                            <td class="text-end"></td>
                                            <td class="text-end"></td>
                                            <td class="text-end"></td>
                                            <td class="text-end"></td>
                                            <td><?php echo htmlspecialchars($p['Cve_Usuario'] ?? ''); ?></td>
                                            <td></td>
                                            <td></td>
                                            <td><?php echo htmlspecialchars($p['Observaciones'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2" style="gap:10px;">
                        <div class="text-muted" style="font-size:9px;">
                            Acciones sobre pedidos seleccionados
                        </div>
                        <div class="d-flex flex-wrap" style="gap:10px;">
                            <button class="btn btn-sm text-white" style="background-color:#0F5AAD;">
                                Asignar
                            </button>
                            <button class="btn btn-sm text-white" style="background-color:#0F5AAD;">
                                Planificar Ola
                            </button>
                        </div>
                    </div>

                </div><!-- card-body -->
            </div>
        </div>
    </div>

</div><!-- container-fluid -->

<!-- ================== MODAL DETALLE PEDIDO ================== -->
<div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="font-size:10px;">
            <div class="modal-header" style="background-color:#0F5AAD;color:#fff;">
                <h6 class="modal-title">
                    Detalle de Pedido <span id="md_folio"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">

                <div class="row mb-2">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Cliente:</strong>
                        <div id="md_cliente"></div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Almacén:</strong>
                        <div id="md_almacen"></div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Ruta:</strong>
                        <div id="md_ruta"></div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Fuente:</strong>
                        <div id="md_fuente"></div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Tipo Pedido:</strong>
                        <div id="md_tipo"></div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Status:</strong>
                        <div id="md_status"></div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Fec. Pedido:</strong>
                        <div id="md_fec_pedido"></div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <strong>Fec. Compromiso:</strong>
                        <div id="md_fec_comp"></div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-12">
                        <strong>Observaciones:</strong>
                        <div id="md_obs" class="text-muted"></div>
                    </div>
                </div>

                <hr class="my-2">

                <div class="table-responsive" style="max-height:50vh;overflow:auto;">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Artículo</th>
                                <th>Cantidad</th>
                                <th>U.M.</th>
                                <th>Lote</th>
                                <th>Surt. Cajas</th>
                                <th>Surt. Piezas</th>
                                <th>Revisadas</th>
                                <th>Empacadas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="md_tbody">
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    Sin información de detalle.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const API_FILTROS = '../api/filtros_assistpro.php';
    const API_DETALLE = 'pedido_detalle_api.php';

    function q(selector) {
        return document.querySelector(selector);
    }

    function fillSelect(selectEl, data, valueField, textField, placeholder) {
        if (!selectEl || !Array.isArray(data)) return;

        const first = selectEl.querySelector('option');
        selectEl.innerHTML = '';
        if (first) {
            selectEl.appendChild(first);
            if (placeholder) first.textContent = placeholder;
        } else if (placeholder) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            selectEl.appendChild(opt);
        }

        data.forEach(row => {
            const val = row[valueField];
            const txt = row[textField];
            if (val == null || txt == null) return;
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = txt;
            selectEl.appendChild(opt);
        });
    }

    function formatoFecha(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (isNaN(d.getTime())) {
            const parts = iso.split('-');
            if (parts.length === 3) {
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return iso;
        }
        const dd = ('0' + d.getDate()).slice(-2);
        const mm = ('0' + (d.getMonth() + 1)).slice(-2);
        const yyyy = d.getFullYear();
        return dd + '/' + mm + '/' + yyyy;
    }

    async function cargarFiltrosPicking() {
        try {
            const url = API_FILTROS + '?action=init';
            const resp = await fetch(url, { method: 'GET' });
            if (!resp.ok) {
                console.error('Error HTTP filtros_assistpro:', resp.status, resp.statusText);
                return;
            }

            const json = await resp.json();
            const data = json.data || json;

            const almacenes = Array.isArray(data.almacenes) ? data.almacenes : [];
            fillSelect(
                q('#filtro_almacen'),
                almacenes,
                'cve_almac',       // viene del API como alias de clave
                'des_almac',
                'Seleccione un almacén'
            );

            const rutas = Array.isArray(data.rutas) ? data.rutas : [];
            if (rutas.length > 0) {
                fillSelect(
                    q('#filtro_ruta'),
                    rutas,
                    'cve_ruta',
                    'descripcion',
                    'Seleccione una ruta'
                );
            }

            const clientes = Array.isArray(data.clientes) ? data.clientes : [];
            const dl = document.getElementById('clientes_list');
            if (dl) {
                dl.innerHTML = '';
                clientes.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.Cve_Clte;
                    const label = (c.RazonSocial || '') + " (" + c.Cve_Clte + ")";
                    opt.label = label;
                    opt.textContent = label;
                    dl.appendChild(opt);
                });
            }
        } catch (err) {
            console.error('Error cargando filtros picking:', err);
        }
    }

    function initToggleChevron() {
        const bar = q('.filtro-toggle-bar');
        if (!bar) return;
        const targetSel = bar.getAttribute('data-bs-target');
        const targetEl = document.querySelector(targetSel);
        if (!targetEl) return;

        targetEl.addEventListener('shown.bs.collapse', function () {
            bar.classList.remove('collapsed');
        });
        targetEl.addEventListener('hidden.bs.collapse', function () {
            bar.classList.add('collapsed');
        });
    }

    function initVerDetalle() {
        const modalEl = document.getElementById('modalDetallePedido');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);

        function limpiarModal() {
            q('#md_folio').textContent = '';
            q('#md_cliente').textContent = '';
            q('#md_almacen').textContent = '';
            q('#md_ruta').textContent = '';
            q('#md_fuente').textContent = '';
            q('#md_tipo').textContent = '';
            q('#md_status').textContent = '';
            q('#md_fec_pedido').textContent = '';
            q('#md_fec_comp').textContent = '';
            q('#md_obs').textContent = '';
            const tbody = q('#md_tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Cargando detalle...</td></tr>';
            }
        }

        async function cargarDetallePorFolio(folio) {
            if (!folio) return;
            limpiarModal();
            q('#md_folio').textContent = '#' + folio;

            try {
                const resp = await fetch(API_DETALLE + '?folio=' + encodeURIComponent(folio));
                if (!resp.ok) {
                    console.error('Error HTTP detalle pedido:', resp.status, resp.statusText);
                    const tbody = q('#md_tbody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error al cargar el detalle.</td></tr>';
                    }
                    return;
                }

                const json = await resp.json();
                if (!json.ok) {
                    const tbody = q('#md_tbody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">' +
                            (json.error || 'No fue posible obtener el detalle del pedido.') +
                            '</td></tr>';
                    }
                    return;
                }

                const ped = json.pedido || {};
                const det = Array.isArray(json.detalle) ? json.detalle : [];

                q('#md_cliente').textContent  = ped.Cve_clte || '';
                q('#md_almacen').textContent  = ped.clave_almacen || ped.cve_almac || '';
                q('#md_ruta').textContent     = ped.ruta || '';
                q('#md_fuente').textContent   = ped.fuente_detalle || '';
                q('#md_tipo').textContent     = ped.TipoPedido || '';
                q('#md_status').textContent   = ped.status || '';
                q('#md_fec_pedido').textContent = formatoFecha(ped.Fec_Pedido || '');
                q('#md_fec_comp').textContent   = formatoFecha(ped.Fec_Entrega || '');
                q('#md_obs').textContent      = ped.Observaciones || '';

                const tbody = q('#md_tbody');
                if (!tbody) return;

                if (det.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">El pedido no tiene líneas de detalle.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                det.forEach((r, idx) => {
                    const tr = document.createElement('tr');

                    const tdIdx = document.createElement('td');
                    tdIdx.textContent = (idx + 1);
                    tr.appendChild(tdIdx);

                    const tdArt = document.createElement('td');
                    tdArt.textContent = r.Cve_articulo || '';
                    tr.appendChild(tdArt);

                    const tdCant = document.createElement('td');
                    tdCant.className = 'text-end';
                    tdCant.textContent = (r.Num_cantidad != null ? r.Num_cantidad : '');
                    tr.appendChild(tdCant);

                    const tdUM = document.createElement('td');
                    tdUM.textContent = r.id_unimed || '';
                    tr.appendChild(tdUM);

                    const tdLote = document.createElement('td');
                    tdLote.textContent = r.cve_lote || '';
                    tr.appendChild(tdLote);

                    const tdSC = document.createElement('td');
                    tdSC.className = 'text-end';
                    tdSC.textContent = (r.SurtidoXCajas != null ? r.SurtidoXCajas : '');
                    tr.appendChild(tdSC);

                    const tdSP = document.createElement('td');
                    tdSP.className = 'text-end';
                    tdSP.textContent = (r.SurtidoXPiezas != null ? r.SurtidoXPiezas : '');
                    tr.appendChild(tdSP);

                    const tdRev = document.createElement('td');
                    tdRev.className = 'text-end';
                    tdRev.textContent = (r.Num_revisadas != null ? r.Num_revisadas : '');
                    tr.appendChild(tdRev);

                    const tdEmp = document.createElement('td');
                    tdEmp.className = 'text-end';
                    tdEmp.textContent = (r.Num_Empacados != null ? r.Num_Empacados : '');
                    tr.appendChild(tdEmp);

                    const tdSt = document.createElement('td');
                    tdSt.textContent = r.status || '';
                    tr.appendChild(tdSt);

                    tbody.appendChild(tr);
                });

            } catch (err) {
                console.error('Error detalle pedido:', err);
                const tbody = q('#md_tbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error inesperado al cargar el detalle.</td></tr>';
                }
            }
        }

        // Delegación de eventos para todos los .link-ver-detalle
        document.addEventListener('click', function (ev) {
            const link = ev.target.closest('.link-ver-detalle');
            if (!link) return;
            ev.preventDefault();
            const folio = link.getAttribute('data-folio');
            if (!folio) return;
            cargarDetallePorFolio(folio);
            modal.show();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cargarFiltrosPicking();
        initToggleChevron();
        initVerDetalle();
    });
})();
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
