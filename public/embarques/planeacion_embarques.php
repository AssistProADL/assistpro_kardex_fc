<?php
// ======================================================================
//  MÓDULO VISUAL: PLANEACIÓN DE EMBARQUES (modal Pedidos Agregados)
//  Ruta: /public/procesos/planeacion_embarques.php
// ======================================================================

require_once __DIR__ . '/../bi/_menu_global.php';

$hoy = date('Y-m-d');
$manana = date('Y-m-d', strtotime('+1 day'));
?>

<style>
    :root {
        --azul-primario: #0F5AAD;
        /* Corporativo */
        --azul-acento: #00A3E0;
        --gris-borde: #dbe3f4;
        --gris-fondo: #f5f7fb;
        --gris-td: #f9fbff;
        --gris-header: #e5e6e7;
        /* NO tocar */
        --texto: #1f2937;
    }

    html,
    body {
        font-family: Calibri, "Segoe UI", Roboto, Arial, sans-serif;
        color: var(--texto);
        font-size: 12px;
    }

    .main-panel-emb {
        padding: 15px;
    }

    .ibox-title h3 {
        font-weight: 700;
        color: #1b2a52;
    }

    /* Cards KPI */
    .card-resumen {
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 15px;
        color: #fff;
        box-shadow: 0 1px 3px rgba(15, 90, 173, .12);
    }

    .bg-hoy {
        background: linear-gradient(90deg, var(--azul-primario), #2566c7);
    }

    .bg-planeados {
        background: linear-gradient(90deg, var(--azul-acento), #27b7ef);
    }

    .bg-enruta {
        background: linear-gradient(90deg, #ffb04d, #f39c3d);
    }

    .bg-retrasados {
        background: linear-gradient(90deg, #ef5b6b, #d94151);
    }

    .card-resumen .titulo {
        font-size: 10px;
        text-transform: uppercase;
        opacity: .9;
        letter-spacing: .3px;
    }

    .card-resumen .valor {
        font-size: 22px;
        font-weight: 800;
    }

    .card-resumen .detalle {
        font-size: 10px;
        opacity: .95;
    }

    /* Bloques encabezado en color corporativo */
    .box-header-dark {
        background: var(--azul-primario);
        color: #fff;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 8px 8px 0 0;
        letter-spacing: .2px;
    }

    .box-body-light {
        background: var(--gris-fondo);
        border: 1px solid var(--gris-borde);
        border-top: none;
        padding: 12px;
        border-radius: 0 0 8px 8px;
    }

    /* Filtros */
    .filtros .form-group {
        margin-bottom: 8px;
    }

    .filtros label {
        font-size: 10px;
        font-weight: 700;
        color: #2b3e6b;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: .2px;
    }

    .filtros .form-control {
        font-size: 12px;
        height: 32px;
        padding: 4px 8px;
        border-radius: 8px;
        border: 1px solid var(--gris-borde);
        background: #fff;
    }

    .filtros .form-control:focus {
        outline: none;
        border-color: var(--azul-acento);
        box-shadow: 0 0 0 3px rgba(0, 163, 224, .15);
    }

    .btn.btn-primary {
        background: var(--azul-primario);
        border-color: var(--azul-primario);
    }

    .btn.btn-primary:hover {
        filter: brightness(.95);
    }

    /* Tabs */
    .nav-tabs>li>a {
        font-size: 11px;
        padding: 8px 12px;
        font-weight: 600;
        color: #2b3e6b;
    }

    .nav-tabs>li.active>a,
    .nav-tabs>li>a:focus,
    .nav-tabs>li>a:hover {
        border-color: var(--azul-primario) var(--azul-primario) transparent;
        color: var(--azul-primario);
    }

    /* Tablas (mantener grises) */
    .tabla,
    .tabla-resumen-carga {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border: 1px solid var(--gris-borde);
        border-radius: 8px;
        overflow: hidden;
    }

    .tabla th,
    .tabla td,
    .tabla-resumen-carga th,
    .tabla-resumen-carga td {
        font-size: 10px !important;
        white-space: nowrap;
        vertical-align: middle;
        padding: 8px 10px;
    }

    .tabla-resumen-carga thead th {
        background: var(--gris-header);
        color: #333;
        border-bottom: 0;
        font-weight: 700;
    }

    .tabla tbody tr:nth-child(even) {
        background: var(--gris-td);
    }

    .tabla tbody tr:hover {
        background: #eef5ff;
    }

    /* DataTables */
    .dataTables_wrapper .dataTables_filter input {
        height: 30px;
        border-radius: 8px;
        border: 1px solid var(--gris-borde);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid var(--gris-borde) !important;
        border-radius: 6px !important;
        padding: 2px 8px !important;
        margin: 0 2px !important;
        background: #fff !important;
        color: #2b3e6b !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--azul-primario) !important;
        color: #fff !important;
        border-color: var(--azul-primario) !important;
    }

    .dataTables_wrapper .dataTables_length select {
        height: 28px;
        border-radius: 6px;
        border: 1px solid var(--gris-borde);
        font-size: 10px;
    }

    .badge-subtitle {
        font-size: 10px;
        font-weight: 700;
        color: #2b3e6b;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    textarea.form-control {
        font-size: 12px;
        border-radius: 8px;
        border: 1px solid var(--gris-borde);
    }

    .btn-brand {
        background: var(--azul-acento);
        border-color: var(--azul-acento);
        color: #fff;
    }

    .btn-brand:hover {
        filter: brightness(.95);
    }

    .mb-5px {
        margin-bottom: 5px;
    }

    .mb-10px {
        margin-bottom: 10px;
    }

    .mt-10px {
        margin-top: 10px;
    }

    .toolbar-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
</style>

<div class="main-panel-emb">
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox">
                    <div class="ibox-title">
                        <h3><i class="fa fa-calendar-check"></i> Planeación y Administración de Embarques</h3>
                    </div>

                    <div class="ibox-content">

                        <!-- CARDS -->
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-hoy text-center">
                                    <div class="titulo">Embarques del día</div>
                                    <div class="valor" id="kpi_embarques_dia">0</div>
                                    <div class="detalle">Programados para hoy</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-planeados text-center">
                                    <div class="titulo">Planeados</div>
                                    <div class="valor" id="kpi_planeados">0</div>
                                    <div class="detalle">En ventana de 7 días</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-enruta text-center">
                                    <div class="titulo">En ruta</div>
                                    <div class="valor" id="kpi_en_ruta">0</div>
                                    <div class="detalle">Unidades en tránsito</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-retrasados text-center">
                                    <div class="titulo">Retrasados</div>
                                    <div class="valor" id="kpi_retrasados">0</div>
                                    <div class="detalle">Fuera de ventana</div>
                                </div>
                            </div>
                        </div>


                        <hr>

                        <!-- TABS -->
                        <ul class="nav nav-tabs">
                            <li class="active"><a data-toggle="tab" href="#t_planeacion"><i class="fa fa-calendar"></i>
                                    Planeación</a></li>
                            <li><a data-toggle="tab" href="#t_dia"><i class="fa fa-road"></i> Embarques del día</a></li>
                            <li><a data-toggle="tab" href="#t_hist"><i class="fa fa-archive"></i> Histórico</a></li>
                        </ul>

                        <div class="tab-content" style="margin-top:10px;">
                            <!-- TAB PLANEACION -->
                            <div id="t_planeacion" class="tab-pane active">

                                <div class="box-header-dark">Planeación de Embarques</div>
                                <div class="box-body-light">

                                    <!-- Filtros específicos -->
                                    <div class="row mb-5px">
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Almacén</span>
                                            <select class="form-control" id="almacen_planeacion">
                                                <option>Cargando almacenes...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Área de embarque</span>
                                            <select class="form-control" id="area_embarque_planeacion">
                                                <option value="">Seleccione área</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Ruta</span>
                                            <select class="form-control" id="ruta_planeacion">
                                                <option value="">[Todas]</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-5px">
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Cliente</span>
                                            <input type="text" id="filtro_cliente" class="form-control"
                                                placeholder="Cliente...">
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Colonia</span>
                                            <input type="text" id="filtro_colonia" class="form-control"
                                                placeholder="Colonia...">
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Código Postal</span>
                                            <input type="text" id="filtro_cp" class="form-control"
                                                placeholder="Código Postal">
                                        </div>
                                    </div>

                                    <!-- Resumen + búsqueda -->
                                    <div class="row mb-10px">
                                        <div class="col-md-9">
                                            <table class="table table-bordered tabla-resumen-carga">
                                                <thead>
                                                    <tr>
                                                        <th>No. de Pedidos</th>
                                                        <th>No. Entregas</th>
                                                        <th>Peso Total KG</th>
                                                        <th>Volumen Total m3</th>
                                                        <th>Total de Piezas</th>
                                                        <th>Total de Guías</th>
                                                        <th>Total de Pallets</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td id="res_pedidos">0</td>
                                                        <td id="res_entregas">0</td>
                                                        <td id="res_peso">0</td>
                                                        <td id="res_volumen">0</td>
                                                        <td id="res_piezas">0</td>
                                                        <td id="res_guias">0</td>
                                                        <td id="res_pallets">0</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge-subtitle">Número de pedido</span>
                                            <div class="input-group">
                                                <input type="text" id="filtro_numero_pedido" class="form-control"
                                                    placeholder="Número de pedido">
                                                <span class="input-group-btn">
                                                    <button id="btn_buscar_pedido" class="btn btn-primary btn-sm"
                                                        type="button"><i class="fa fa-search"></i> Buscar</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Transporte -->
                                    <div class="row mb-10px">
                                        <div class="col-md-6">
                                            <span class="badge-subtitle">Transporte</span>
                                            <select id="select_transporte" class="form-control">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Capacidades / Dimensiones -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="box-header-dark text-center">Transporte</div>
                                            <div class="box-body-light">
                                                <div class="row mb-10px">
                                                    <div class="col-md-6">
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <thead>
                                                                <tr>
                                                                    <th>Capacidad Carga KG</th>
                                                                    <th>Capacidad Volumétrica m3</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td id="capacidad_peso">0</td>
                                                                    <td id="capacidad_volumen">0</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <thead>
                                                                <tr>
                                                                    <th colspan="3">Dimensiones del Vehículo</th>
                                                                </tr>
                                                                <tr>
                                                                    <th>Altura Mts</th>
                                                                    <th>Ancho Mts</th>
                                                                    <th>Fondo Mts</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td id="altura">0</td>
                                                                    <td id="ancho">0</td>
                                                                    <td id="fondo">0</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <thead>
                                                                <tr>
                                                                    <th>Capacidad Utilizada KG</th>
                                                                    <th>Volumen Ocupado m3</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td id="peso_utilizado">0</td>
                                                                    <td id="volumen_utilizado">0</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /.box-body-light -->

                                <!-- LISTA DE PEDIDOS -->
                                <div class="section-title mt-10px" style="font-weight:700;">Pedidos disponibles para
                                    embarque</div>
                                <div class="toolbar-actions mb-5px">
                                    <label class="checkbox-inline"><input type="checkbox"> Generar Todos los
                                        Reportes</label>
                                    <label class="checkbox-inline" style="margin-left:15px;"><input type="checkbox">
                                        Entregar a Cliente</label>

                                    <!-- BOTÓN Bootstrap 5 -->
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalPedidosAgregados">
                                        <i class="fa fa-boxes-stacked"></i> Pedidos Agregados
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover tabla"
                                        id="tabla_pedidos">
                                        <thead>
                                            <tr>
                                                <th>Acción</th>
                                                <th>Embarcar</th>
                                                <th>Folio</th>
                                                <th>Fecha Pedido</th>
                                                <th>Fecha Entrega</th>
                                                <th>Horario Planeado</th>
                                                <th>Ruta</th>
                                                <th>Clave Cliente</th>
                                                <th>C Dest</th>
                                                <th>Destinatario</th>
                                                <th>Dirección</th>
                                                <th>C. Postal</th>
                                                <th>Colonia</th>
                                                <th>Latitud</th>
                                                <th>Longitud</th>
                                                <th>Total Cajas</th>
                                                <th>Piezas</th>
                                                <th>Total guías</th>
                                                <th>Peso total</th>
                                                <th>Volumen</th>
                                                <th>Cliente | Empresa</th>
                                                <th>Zona Embarque (Isla)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gridPedidos"></tbody>
                                    </table>
                                </div>
                                <div class="mt-10px mb-10px">
                                    <button class="btn btn-primary btn-sm">Generar Reportes Etiquetas</button>
                                </div>

                                <!-- (El formulario embebido ya no está aquí) -->
                            </div><!-- /TAB PLANEACION -->

                            <!-- TAB DÍA -->
                            <div id="t_dia" class="tab-pane">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover tabla" id="tabla_dia">
                                        <thead>
                                            <tr>
                                                <th>Folio</th>
                                                <th>Salida Prog.</th>
                                                <th>Salida Real</th>
                                                <th>Almacén</th>
                                                <th>Cliente</th>
                                                <th>Ruta</th>
                                                <th>Transportista</th>
                                                <th>Operador</th>
                                                <th>Cajas</th>
                                                <th>Tarimas</th>
                                                <th>Destino</th>
                                                <th>Estatus</th>
                                                <th>Desviación</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gridDia"></tbody>

                                    </table>
                                </div>
                            </div>

                            <!-- TAB HISTÓRICO -->
                            <div id="t_hist" class="tab-pane">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover tabla" id="tabla_hist">
                                        <thead>
                                            <tr>
                                                <th>Folio</th>
                                                <th>Fecha</th>
                                                <th>Almacén</th>
                                                <th>Cliente</th>
                                                <th>Ruta</th>
                                                <th>Transportista</th>
                                                <th>Tipo</th>
                                                <th>Cajas</th>
                                                <th>Tarimas</th>
                                                <th>Destino</th>
                                                <th>Estatus</th>
                                                <th>Entrega</th>
                                                <th>On Time</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gridHist"></tbody>
                                    </table>
                                </div>
                            </div>

                        </div><!-- /.tab-content -->
                    </div><!-- /.ibox-content -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Pedidos Agregados (Bootstrap 5) -->
<div class="modal fade" id="modalPedidosAgregados" tabindex="-1" aria-labelledby="modalPedidosAgregadosLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width:1200px;">
        <div class="modal-content">
            <div class="modal-header" style="background:#0F5AAD;color:#fff;">
                <h5 class="modal-title" id="modalPedidosAgregadosLabel"><i class="fa fa-boxes-stacked"></i> Pedidos
                    agregados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"
                    style="filter:invert(1); opacity:1;"></button>
            </div>
            <div class="modal-body" style="background:#f7f9ff;">
                <div class="container-fluid">
                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">Guía de Transporte:</span>
                            <input type="text" class="form-control" placeholder="GUÍA DE TRANSPORTE...">
                        </div>
                        <div class="col-md-6">
                            <span class="badge-subtitle">Contacto:</span>
                            <input type="text" class="form-control" placeholder="Contacto...">
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">ID Chofer:</span>
                            <input type="text" class="form-control" placeholder="ID Chofer...">
                        </div>
                        <div class="col-md-6">
                            <span class="badge-subtitle">Chofer:</span>
                            <input type="text" class="form-control" placeholder="Chofer...">
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">Número de Unidad:</span>
                            <input type="text" class="form-control" placeholder="Número de Unidad...">
                        </div>
                        <div class="col-md-6">
                            <span class="badge-subtitle">Clave Transportadora:</span>
                            <select class="form-control">
                                <option>Seleccione una Transportadora</option>
                                <option>TRN001 – Transportes del Norte</option>
                                <option>LEX002 – Logística Express</option>
                                <option>FLE003 – Fletes MX</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">Placa:</span>
                            <input type="text" class="form-control" placeholder="Placa...">
                        </div>
                        <div class="col-md-6">
                            <span class="badge-subtitle">Sello/Precinto:</span>
                            <input type="text" class="form-control" placeholder="Sello/Precinto...">
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">Seguro de la Carga:</span>
                            <input type="text" class="form-control" placeholder="Seguro...">
                        </div>
                        <div class="col-md-6">
                            <span class="badge-subtitle">Flete Pagadero en:</span>
                            <input type="text" class="form-control" placeholder="Flete...">
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-12">
                            <span class="badge-subtitle">Origen:</span>
                            <textarea class="form-control" rows="6" placeholder="Remitente:
RFC:
Domicilio:
Ciudad:
Se Recogerá En:"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background:#f1f5ff;">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-brand"><i class="fa fa-truck-loading"></i> Embarcar</button>
            </div>
        </div>
    </div>
</div>


<script>
    // Mejoras para `planeacion_embarques.php` - VERSION CON DEBUG
    document.addEventListener('DOMContentLoaded', function () {

        /* ================= CONTEXTO GLOBAL ================= */
        let ID_ALMACEN_ACTUAL = null;
        let AREA_EMBARQUE_ACTUAL = null;
        let RUTA_ACTUAL = null;
        let DATA_PEDIDOS = [];
        let FILTRO_CLIENTE = '';
        let FILTRO_COLONIA = '';
        let FILTRO_CP = '';
        let FILTRO_NUMERO_PEDIDO = '';
        let TRANSPORTE_ACTUAL = null;

        /* ================= APIS ================= */
        const apiAlmacenes = '../api/filtros_almacenes.php';
        const apiZonasEmbarque = '../api/zona_embarques.php';
        const apiPedidosEmbarcable = '../api/pedidos_embarcable.php';
        const apiUbicaciones = '../api/ubicaciones_por_almacen.php';
        const apiRutas = '../api/rutas.php';
        const apiTiposTransporte = '../api/tipos_transporte.php';

        /* ================= ELEMENTOS ================= */
        const selectsAlmacen = Array.from(document.querySelectorAll('#almacen_planeacion'));
        const selectArea = document.getElementById('area_embarque_planeacion');
        const selectsRuta = [
            document.getElementById('ruta_filtro_top'),
            document.getElementById('ruta_planeacion')
        ].filter(el => el !== null);
        const gridPedidos = document.getElementById('gridPedidos');

        // Inputs de filtro de texto
        const inputCliente = document.getElementById('filtro_cliente');
        const inputColonia = document.getElementById('filtro_colonia');
        const inputCP = document.getElementById('filtro_cp');
        const inputNumeroPedido = document.getElementById('filtro_numero_pedido');
        const btnBuscarPedido = document.getElementById('btn_buscar_pedido');
        const selectTransporte = document.getElementById('select_transporte');

        /* ================= CACHE ================= */
        const zonasCache = new Map();
        const ubicacionesCache = new Map();
        const rutasCache = new Map();
        const pedidosCache = new Map();

        /* ================= HELPERS ================= */
        function escapeHtml(s) {
            if (s === null || s === undefined) return '';
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function fetchJson(url, { timeout = 8000 } = {}) {
            console.log('🔵 Fetching:', url);
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);
            try {
                const res = await fetch(url, {
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json' }
                });
                clearTimeout(id);

                console.log('📊 Response status:', res.status, res.statusText);

                if (!res.ok) {
                    console.error('❌ HTTP Error:', res.status);
                    throw new Error('HTTP ' + res.status);
                }

                const text = await res.text();
                console.log('📄 Response text:', text.substring(0, 200));

                if (!text || text.trim() === '') {
                    console.warn('⚠️ Empty response');
                    return null;
                }

                const json = JSON.parse(text);
                console.log('✅ Parsed JSON:', json);
                return json;

            } catch (err) {
                clearTimeout(id);
                console.error('❌ fetchJson error', url, err);
                return null;
            }
        }

        function mapAlmacen(a) {
            return {
                id: a.id ?? a.cve_almacenp ?? a.cve_almacen ?? a.cve_almac ?? null,
                nombre: a.nombre ?? a.Nombre ?? a.descripcion ?? a.Descripcion ??
                    (a.cve_almacenp ? `Almacen ${a.cve_almacenp}` :
                        a.id ? `Almacen ${a.id}` : 'Sin nombre')
            };
        }

        function setSelectsAlmacenHtml(html) {
            selectsAlmacen.forEach(s => s.innerHTML = html);
        }

        /* ================== ALMACENES ================== */
        async function cargarAlmacenes() {
            console.log('🏢 Cargando almacenes...');
            if (!selectsAlmacen.length) {
                console.warn('⚠️ No se encontraron selects de almacén');
                return;
            }

            setSelectsAlmacenHtml('<option value="">Cargando almacenes...</option>');

            const data = await fetchJson(apiAlmacenes);

            if (!data) {
                console.error('❌ No se recibieron datos de almacenes');
                setSelectsAlmacenHtml('<option value="">Error al cargar</option>');
                return;
            }

            if (!Array.isArray(data)) {
                console.error('❌ La respuesta no es un array:', data);
                setSelectsAlmacenHtml('<option value="">Error: formato incorrecto</option>');
                return;
            }

            console.log('✅ Almacenes recibidos:', data.length);

            const opciones = ['<option value="">Seleccione un almacén</option>']
                .concat(data.map(a => {
                    const m = mapAlmacen(a);
                    console.log('  - Almacén:', m);
                    return `<option value="${escapeHtml(m.id)}">${escapeHtml(m.nombre)}</option>`;
                }));

            setSelectsAlmacenHtml(opciones.join(''));

            selectsAlmacen.forEach(s => {
                s.onchange = onAlmacenChange;
            });
        }

        /* ================== ZONAS DE EMBARQUE ================== */
        async function cargarZonasEmbarque(idAlmacen) {
            console.log('📦 Cargando zonas de embarque para almacén:', idAlmacen);

            if (!selectArea) {
                console.warn('⚠️ No se encontró select de área de embarque');
                return null;
            }

            if (!idAlmacen) {
                selectArea.innerHTML = '<option value="">Seleccione área</option>';
                return null;
            }

            // Verificar cache
            if (zonasCache.has(idAlmacen)) {
                console.log('💾 Usando zonas desde cache');
                renderZonas(zonasCache.get(idAlmacen));
                return zonasCache.get(idAlmacen);
            }

            selectArea.innerHTML = '<option value="">Cargando áreas...</option>';
            const url = `${apiZonasEmbarque}?cve_almac=${encodeURIComponent(idAlmacen)}`;
            const resp = await fetchJson(url);

            if (!resp) {
                console.error('❌ No se recibieron zonas de embarque');
                selectArea.innerHTML = '<option value="">Error al cargar áreas</option>';
                return null;
            }

            // La API devuelve directamente un array
            let zonas = [];
            if (Array.isArray(resp)) {
                zonas = resp;
            } else if (resp.data && Array.isArray(resp.data)) {
                zonas = resp.data;
            } else {
                console.error('❌ Formato de respuesta no reconocido:', resp);
                selectArea.innerHTML = '<option value="">Error: formato incorrecto</option>';
                return null;
            }

            console.log('✅ Zonas recibidas:', zonas.length, zonas);

            if (zonas.length === 0) {
                selectArea.innerHTML = '<option value="">Sin áreas disponibles</option>';
                return null;
            }

            zonasCache.set(idAlmacen, zonas);
            renderZonas(zonas);
            return zonas;
        }

        function renderZonas(zonas) {
            if (!selectArea) return;

            if (!Array.isArray(zonas) || zonas.length === 0) {
                selectArea.innerHTML = '<option value="">Sin áreas disponibles</option>';
                return;
            }

            const opciones = ['<option value="">Seleccione área</option>']
                .concat(zonas.map(z => {
                    const val = z.cve_ubicacion ?? z.id ?? z.Cve ?? '';
                    const desc = z.descripcion ?? z.des_almac ?? '';
                    const label = val + (desc ? ` - ${desc}` : '');
                    console.log('  - Zona:', { val, label, original: z });
                    return `<option value="${escapeHtml(val)}">${escapeHtml(label)}</option>`;
                }));

            selectArea.innerHTML = opciones.join('');

            // Auto-seleccionar si solo hay una zona
            if (zonas.length === 1) {
                const val = zonas[0].cve_ubicacion ?? zonas[0].id ?? '';
                selectArea.value = val;
                AREA_EMBARQUE_ACTUAL = val;
                console.log('🎯 Auto-seleccionada única zona:', val);
                cargarPedidosEmbarcables();
            }
        }

        /* ================== UBICACIONES ================== */
        async function cargarUbicacionesPorAlmacen(idAlmacen) {
            console.log('📍 Cargando ubicaciones para almacén:', idAlmacen);

            if (!idAlmacen) return null;
            if (ubicacionesCache.has(idAlmacen)) {
                console.log('💾 Usando ubicaciones desde cache');
                return ubicacionesCache.get(idAlmacen);
            }

            const resp = await fetchJson(`${apiUbicaciones}?almacenp_id=${encodeURIComponent(idAlmacen)}`);

            if (!Array.isArray(resp)) {
                console.warn('⚠️ No se recibieron ubicaciones o formato incorrecto');
                ubicacionesCache.set(idAlmacen, []);
                return [];
            }

            console.log('✅ Ubicaciones recibidas:', resp.length);
            ubicacionesCache.set(idAlmacen, resp);
            return resp;
        }

        /* ================== RUTAS ================== */
        async function cargarRutas(idAlmacen) {
            console.log('🛣️ Cargando rutas para almacén:', idAlmacen);

            // Limpiar selects de ruta si no hay almacén
            if (!idAlmacen) {
                selectsRuta.forEach(sel => {
                    sel.innerHTML = '<option value="">[Todas]</option>';
                });
                return null;
            }

            // Verificar cache
            if (rutasCache.has(idAlmacen)) {
                console.log('💾 Usando rutas desde cache');
                renderRutas(rutasCache.get(idAlmacen));
                return rutasCache.get(idAlmacen);
            }

            // Mostrar "Cargando..."
            selectsRuta.forEach(sel => {
                sel.innerHTML = '<option value="">Cargando rutas...</option>';
            });

            const resp = await fetchJson(`${apiRutas}?almacenp_id=${encodeURIComponent(idAlmacen)}`);

            // Manejar respuesta
            let rutas = [];
            if (resp && resp.success && Array.isArray(resp.data)) {
                rutas = resp.data;
            } else if (Array.isArray(resp)) {
                rutas = resp;
            }

            console.log('✅ Rutas recibidas:', rutas.length);

            // Guardar en cache
            rutasCache.set(idAlmacen, rutas);
            renderRutas(rutas);
            return rutas;
        }

        function renderRutas(rutas) {
            const html = '<option value="">[Todas]</option>' +
                rutas.map(r => {
                    const cve = escapeHtml(r.cve_ruta || '');
                    const desc = escapeHtml(r.descripcion || r.cve_ruta || '');
                    return `<option value="${cve}">${desc}</option>`;
                }).join('');

            selectsRuta.forEach(sel => {
                sel.innerHTML = html;
            });
        }

        /* ================== TRANSPORTES ================== */
        async function cargarTiposTransporte() {
            console.log('🚚 Cargando tipos de transporte...');

            try {
                const resp = await fetchJson(apiTiposTransporte);
                const tipos = resp?.data || resp || [];

                console.log('✅ Tipos de transporte cargados:', tipos.length);

                renderTiposTransporte(tipos);
                return tipos;
            } catch (error) {
                console.error('❌ Error al cargar tipos de transporte:', error);
                return [];
            }
        }

        function renderTiposTransporte(tipos) {
            console.log('🔧 renderTiposTransporte - selectTransporte:', selectTransporte);
            console.log('🔧 renderTiposTransporte - tipos recibidos:', tipos);
            console.log('🔧 renderTiposTransporte - cantidad de tipos:', tipos.length);

            if (!selectTransporte) {
                console.error('❌ selectTransporte no encontrado!');
                return;
            }

            const html = '<option value="">Seleccione</option>' +
                tipos.map(t => {
                    console.log('🔧 Procesando tipo:', t);
                    const id = escapeHtml(t.id || '');
                    const nombre = escapeHtml(t.nombre || t.desc_ttransporte || '');
                    console.log(`🔧 ID: ${id}, Nombre: ${nombre}`);
                    return `<option value="${id}">${nombre}</option>`;
                }).join('');

            console.log('🔧 HTML generado:', html);
            selectTransporte.innerHTML = html;
            console.log('✅ Select poblado con', selectTransporte.options.length, 'opciones');
        }

        async function onTransporteChange(transporteId) {
            console.log('🚚 Transporte seleccionado:', transporteId);

            if (!transporteId) {
                TRANSPORTE_ACTUAL = null;
                document.getElementById('capacidad_peso').textContent = '0';
                document.getElementById('capacidad_volumen').textContent = '0.00';
                document.getElementById('altura').textContent = '0.00';
                document.getElementById('ancho').textContent = '0.00';
                document.getElementById('fondo').textContent = '0.00';
                document.getElementById('peso_utilizado').textContent = '0';
                document.getElementById('volumen_utilizado').textContent = '0.00';
                return;
            }

            try {
                const resp = await fetchJson(apiTiposTransporte);
                const tipos = resp?.data || resp || [];
                const transporte = tipos.find(t => t.id == transporteId);

                if (transporte) {
                    TRANSPORTE_ACTUAL = transporte;
                    actualizarCapacidadesTransporte(transporte);
                }
            } catch (error) {
                console.error('❌ Error al obtener datos del transporte:', error);
            }
        }

        function actualizarCapacidadesTransporte(transporte) {
            console.log('🚚 Actualizando capacidades del transporte:', transporte);

            const capacidadPeso = parseFloat(transporte.capacidad_peso_kg || 0);
            const capacidadVolumen = parseFloat(transporte.capacidad_volumen_m3 || 0);

            document.getElementById('capacidad_peso').textContent = capacidadPeso.toLocaleString('es-MX');
            document.getElementById('capacidad_volumen').textContent = capacidadVolumen.toFixed(2);

            document.getElementById('altura').textContent = parseFloat(transporte.altura_mts || 0).toFixed(2);
            document.getElementById('ancho').textContent = parseFloat(transporte.ancho_mts || 0).toFixed(2);
            document.getElementById('fondo').textContent = parseFloat(transporte.fondo_mts || 0).toFixed(2);

            calcularCapacidadUtilizada();
        }

        function calcularCapacidadUtilizada() {
            if (!TRANSPORTE_ACTUAL || !DATA_PEDIDOS || DATA_PEDIDOS.length === 0) {
                document.getElementById('peso_utilizado').textContent = '0';
                document.getElementById('volumen_utilizado').textContent = '0.00';
                return;
            }

            const pesoTotal = DATA_PEDIDOS.reduce((sum, p) => sum + parseFloat(p.peso_total || 0), 0);
            const volumenTotal = DATA_PEDIDOS.reduce((sum, p) => sum + parseFloat(p.volumen_total || 0), 0);

            document.getElementById('peso_utilizado').textContent = pesoTotal.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('volumen_utilizado').textContent = volumenTotal.toFixed(2);

            const capacidadPeso = parseFloat(TRANSPORTE_ACTUAL.capacidad_peso_kg || 0);
            const capacidadVolumen = parseFloat(TRANSPORTE_ACTUAL.capacidad_volumen_m3 || 0);

            if (capacidadPeso > 0 && pesoTotal > capacidadPeso) {
                console.warn('⚠️ EXCEDE CAPACIDAD DE PESO:', pesoTotal, '>', capacidadPeso);
            }
            if (capacidadVolumen > 0 && volumenTotal > capacidadVolumen) {
                console.warn('⚠️ EXCEDE CAPACIDAD VOLUMÉTRICA:', volumenTotal, '>', capacidadVolumen);
            }
        }

        /* ================== KPIs / CARDS ================== */
        function actualizarKPIs(pedidos) {
            if (!pedidos || pedidos.length === 0) {
                document.getElementById('kpi_embarques_dia').textContent = '0';
                document.getElementById('kpi_planeados').textContent = '0';
                document.getElementById('kpi_en_ruta').textContent = '0';
                document.getElementById('kpi_retrasados').textContent = '0';
                return;
            }

            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            const en7Dias = new Date();
            en7Dias.setDate(en7Dias.getDate() + 7);
            en7Dias.setHours(23, 59, 59, 999);

            let embarquesHoy = 0;
            let planeados = 0;
            let enRuta = 0;
            let retrasados = 0;

            pedidos.forEach(p => {
                // Fecha de embarque del pedido
                const fechaEmbarque = p.fecha_embarque ? new Date(p.fecha_embarque) : null;
                
                // Embarques del día (fecha de embarque = hoy)
                if (fechaEmbarque) {
                    const fechaSolo = new Date(fechaEmbarque);
                    fechaSolo.setHours(0, 0, 0, 0);
                    
                    if (fechaSolo.getTime() === hoy.getTime()) {
                        embarquesHoy++;
                    }
                    
                    // Planeados (en ventana de 7 días)
                    if (fechaEmbarque >= hoy && fechaEmbarque <= en7Dias) {
                        planeados++;
                    }
                    
                    // Retrasados (fecha pasada y aún no embarcado)
                    if (fechaEmbarque < hoy && p.status !== 'E') {
                        retrasados++;
                    }
                }
                
                // En ruta (status = 'E' o similar)
                if (p.status === 'E' || p.status === 'R') {
                    enRuta++;
                }
            });

            document.getElementById('kpi_embarques_dia').textContent = embarquesHoy;
            document.getElementById('kpi_planeados').textContent = planeados;
            document.getElementById('kpi_en_ruta').textContent = enRuta;
            document.getElementById('kpi_retrasados').textContent = retrasados;
            
            console.log('📊 KPIs actualizados:', {
                embarquesHoy,
                planeados,
                enRuta,
                retrasados
            });
        }


        /* ================== PEDIDOS EMBARCABLES ================== */
        async function cargarPedidosEmbarcables() {
            console.log('📋 Cargando pedidos embarcables...');
            console.log('  Almacén:', ID_ALMACEN_ACTUAL);
            console.log('  Área:', AREA_EMBARQUE_ACTUAL);
            console.log('  Ruta:', RUTA_ACTUAL);

            if (!ID_ALMACEN_ACTUAL || !AREA_EMBARQUE_ACTUAL) {
                console.warn('⚠️ Faltan parámetros para cargar pedidos');
                return;
            }

            const cacheKey = `${ID_ALMACEN_ACTUAL}|${AREA_EMBARQUE_ACTUAL}|${RUTA_ACTUAL || ''}`;
            if (pedidosCache.has(cacheKey)) {
                console.log('💾 Usando pedidos desde cache');
                DATA_PEDIDOS = pedidosCache.get(cacheKey);
                pintarGridPedidos(DATA_PEDIDOS);
                pintarResumen(DATA_PEDIDOS);
                return;
            }

            if (gridPedidos) {
                gridPedidos.innerHTML = `<tr><td colspan="22" class="text-center">Cargando pedidos...</td></tr>`;
            }

            let url = `${apiPedidosEmbarcable}?almacen=${encodeURIComponent(ID_ALMACEN_ACTUAL)}&ubicacion=${encodeURIComponent(AREA_EMBARQUE_ACTUAL)}`;
            if (RUTA_ACTUAL) {
                url += `&ruta=${encodeURIComponent(RUTA_ACTUAL)}`;
            }
            const resp = await fetchJson(url);

            let pedidos = [];
            if (Array.isArray(resp)) {
                pedidos = resp;
            } else if (resp && Array.isArray(resp.data)) {
                pedidos = resp.data;
            } else if (resp && resp.data) {
                pedidos = resp.data;
            }

            console.log('✅ Pedidos recibidos:', pedidos.length);
            DATA_PEDIDOS = pedidos || [];
            pedidosCache.set(cacheKey, DATA_PEDIDOS);

            pintarGridPedidos(DATA_PEDIDOS);
            pintarResumen(DATA_PEDIDOS);
        }

        /* ================== FILTRADO DE TEXTO ================== */
        function aplicarFiltrosTexto() {
            console.log('🔍 Aplicando filtros de texto:', {
                cliente: FILTRO_CLIENTE,
                colonia: FILTRO_COLONIA,
                cp: FILTRO_CP
            });

            let datosFiltrados = DATA_PEDIDOS;

            // Filtrar por cliente (busca en Cve_clte y cliente_nombre)
            if (FILTRO_CLIENTE && FILTRO_CLIENTE.trim() !== '') {
                const filtroCliente = FILTRO_CLIENTE.toLowerCase().trim();
                datosFiltrados = datosFiltrados.filter(p => {
                    const cveCliente = (p.Cve_clte || '').toString().toLowerCase();
                    const nombreCliente = (p.cliente_nombre || '').toString().toLowerCase();

                    return cveCliente.includes(filtroCliente) || nombreCliente.includes(filtroCliente);
                });
            }

            // Filtrar por colonia
            if (FILTRO_COLONIA && FILTRO_COLONIA.trim() !== '') {
                const filtroColonia = FILTRO_COLONIA.toLowerCase().trim();
                datosFiltrados = datosFiltrados.filter(p => {
                    const colonia = (p.colonia || '').toLowerCase();
                    return colonia.includes(filtroColonia);
                });
            }

            // Filtrar por código postal
            if (FILTRO_CP && FILTRO_CP.trim() !== '') {
                const filtroCP = FILTRO_CP.trim();
                datosFiltrados = datosFiltrados.filter(p => {
                    const cp = (p.cp || '').toString();
                    return cp.includes(filtroCP);
                });
            }

            // Filtrar por número de pedido
            if (FILTRO_NUMERO_PEDIDO && FILTRO_NUMERO_PEDIDO.trim() !== '') {
                const filtroPedido = FILTRO_NUMERO_PEDIDO.toUpperCase().trim();
                datosFiltrados = datosFiltrados.filter(p => {
                    const folio = (p.Fol_Folio || '').toString().toUpperCase();
                    return folio.includes(filtroPedido);
                });
            }

            console.log(`✅ Filtrado: ${DATA_PEDIDOS.length} → ${datosFiltrados.length} pedidos`);
            
            // Pintar grid con datos filtrados
            pintarGridPedidos(datosFiltrados);
            pintarResumen(datosFiltrados);
            
            // Actualizar KPIs de las cards
            actualizarKPIs(DATA_PEDIDOS);
        }

        /* ================== GRID ================== */
        function pintarGridPedidos(data) {
            if (!gridPedidos) return;

            if (!Array.isArray(data) || data.length === 0) {
                gridPedidos.innerHTML = `
                <tr>
                    <td colspan="22" class="text-center">Sin pedidos disponibles</td>
                </tr>`;
                return;
            }

            gridPedidos.innerHTML = data.map(p => {
                const fol = escapeHtml(p.Fol_Folio ?? p.folio ?? p.FOLIO ?? '');
                const fechaPedido = escapeHtml(p.Fecha_pedido ?? p.fecha_pedido ?? p.fecha ?? '');
                const fechaEntrega = escapeHtml(p.Fecha_entrega ?? p.fecha_entrega ?? p.fecha_entrega_programada ?? '');
                const ruta = escapeHtml(p.ruta ?? '');
                const cveCliente = escapeHtml(p.Cve_clte ?? p.cliente ?? '');
                const clienteNombre = escapeHtml(p.cliente_nombre ?? p.razon_social ?? '');

                const direccion = escapeHtml(p.direccion ?? '');
                const cp = escapeHtml(p.cp ?? '');
                const colonia = escapeHtml(p.colonia ?? '');
                const lat = escapeHtml(p.latitud ?? '');
                const lon = escapeHtml(p.longitud ?? '');

                const area = escapeHtml(p.AreaStagging ?? p.area ?? p.area_embarque ?? '');
                const piezas = escapeHtml(p.total_piezas ?? p.piezas ?? '0');
                const cajas = escapeHtml(p.total_cajas ?? p.cajas ?? '0');
                const peso = escapeHtml(p.peso_total ?? '0');
                const volumen = escapeHtml(p.volumen_total ?? '0');

                return `
            <tr>
                <td>
                    <button class="btn btn-xs btn-default" title="Ver"><i class="fa fa-search"></i></button>
                    <button class="btn btn-xs btn-default" title="Detalle"><i class="fa fa-list"></i></button>
                </td>
                <td><button class="btn btn-xs btn-warning" title="Embarcar">&gt;&gt;</button></td>
                <td>${fol}</td>
                <td>${fechaPedido}</td>
                <td>${fechaEntrega}</td>
                <td></td> <!-- Horario Planeado -->
                <td>${ruta}</td>
                <td>${cveCliente}</td>
                <td></td> <!-- C Dest -->
                <td>${clienteNombre}</td> <!-- Destinatario -->
                <td>${direccion}</td>
                <td>${cp}</td>
                <td>${colonia}</td>
                <td>${lat}</td>
                <td>${lon}</td>
                <td>${cajas}</td>
                <td>${piezas}</td>
                <td></td> <!-- Total guías -->
                <td>${peso}</td>
                <td>${volumen}</td>
                <td>${clienteNombre}</td> <!-- Cliente | Empresa -->
                <td>${area}</td>
            </tr>`;
            }).join('');
        }

        /* ================== RESUMEN ================== */
        function pintarResumen(data) {
            const pedidosCount = Array.isArray(data) ? data.length : 0;
            const piezas = Array.isArray(data) ? data.reduce((s, p) => s + Number(p.total_piezas || p.piezas || 0), 0) : 0;

            const elPedidos = document.getElementById('res_pedidos');
            const elEntregas = document.getElementById('res_entregas');
            const elPiezas = document.getElementById('res_piezas');

            if (elPedidos) elPedidos.innerText = pedidosCount;
            if (elEntregas) elEntregas.innerText = pedidosCount;
            if (elPiezas) elPiezas.innerText = piezas;
        }

        /* ================== EVENT HANDLERS ================== */
        async function onAlmacenChange(ev) {
            const value = ev.target.value;
            console.log('🔄 Cambio de almacén:', value);

            if (!value) {
                ID_ALMACEN_ACTUAL = null;
                AREA_EMBARQUE_ACTUAL = null;
                RUTA_ACTUAL = null;
                if (selectArea) selectArea.innerHTML = '<option value="">Seleccione área</option>';
                selectsRuta.forEach(sel => sel.innerHTML = '<option value="">[Todas]</option>');
                pintarGridPedidos([]);
                return;
            }

            ID_ALMACEN_ACTUAL = value;
            AREA_EMBARQUE_ACTUAL = null;
            RUTA_ACTUAL = null;
            DATA_PEDIDOS = [];
            pintarGridPedidos([]);

            // Cargar zonas, ubicaciones y rutas en paralelo
            await Promise.allSettled([
                cargarZonasEmbarque(ID_ALMACEN_ACTUAL),
                cargarUbicacionesPorAlmacen(ID_ALMACEN_ACTUAL),
                cargarRutas(ID_ALMACEN_ACTUAL)
            ]);
        }

        // Área → carga pedidos
        if (selectArea) {
            selectArea.addEventListener('change', function () {
                const val = this.value;
                console.log('🔄 Cambio de área:', val);

                if (!val) {
                    AREA_EMBARQUE_ACTUAL = null;
                    pintarGridPedidos([]);
                    return;
                }

                AREA_EMBARQUE_ACTUAL = val;
                cargarPedidosEmbarcables();
            });
        }

        // Ruta → recarga pedidos
        selectsRuta.forEach(sel => {
            sel.addEventListener('change', function () {
                RUTA_ACTUAL = this.value || null;
                console.log('🔄 Cambio de ruta:', RUTA_ACTUAL);

                // Sincronizar el otro combo si existe
                selectsRuta.forEach(other => {
                    if (other !== this) other.value = this.value;
                });

                // Recargar pedidos si ya hay almacén y área seleccionados
                if (ID_ALMACEN_ACTUAL && AREA_EMBARQUE_ACTUAL) {
                    cargarPedidosEmbarcables();
                }
            });
        });


        /* ================== EVENT LISTENERS: FILTROS DE TEXTO ================== */
        // Filtro de Cliente
        if (inputCliente) {
            inputCliente.addEventListener('input', function () {
                FILTRO_CLIENTE = this.value;
                console.log('🔍 Filtro Cliente:', FILTRO_CLIENTE);

                // Solo aplicar si hay datos cargados
                if (DATA_PEDIDOS.length > 0) {
                    aplicarFiltrosTexto();
                }
            });
        }

        // Filtro de Colonia
        if (inputColonia) {
            inputColonia.addEventListener('input', function () {
                FILTRO_COLONIA = this.value;
                console.log('🔍 Filtro Colonia:', FILTRO_COLONIA);

                if (DATA_PEDIDOS.length > 0) {
                    aplicarFiltrosTexto();
                }
            });
        }

        // Filtro de Código Postal
        if (inputCP) {
            inputCP.addEventListener('input', function () {
                FILTRO_CP = this.value;
                console.log('🔍 Filtro CP:', FILTRO_CP);

                if (DATA_PEDIDOS.length > 0) {
                    aplicarFiltrosTexto();
                }
            });
        }

        // Filtro de Número de Pedido (input)
        if (inputNumeroPedido) {
            inputNumeroPedido.addEventListener('input', function () {
                FILTRO_NUMERO_PEDIDO = this.value;
                console.log('🔍 Filtro Número Pedido:', FILTRO_NUMERO_PEDIDO);

                if (DATA_PEDIDOS.length > 0) {
                    aplicarFiltrosTexto();
                }
            });
        }

        // Botón de Búsqueda de Pedido
        if (btnBuscarPedido && inputNumeroPedido) {
            btnBuscarPedido.addEventListener('click', function () {
                FILTRO_NUMERO_PEDIDO = inputNumeroPedido.value;
                console.log('🔍 Buscar Pedido:', FILTRO_NUMERO_PEDIDO);

                if (DATA_PEDIDOS.length > 0) {
                    aplicarFiltrosTexto();
                }
            });

            // También permitir buscar con Enter
            inputNumeroPedido.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    FILTRO_NUMERO_PEDIDO = this.value;
                    console.log('🔍 Buscar Pedido (Enter):', FILTRO_NUMERO_PEDIDO);

                    if (DATA_PEDIDOS.length > 0) {
                        aplicarFiltrosTexto();
                    }
                }
            });
        }

        // Event listener para select de transporte
        if (selectTransporte) {
            selectTransporte.addEventListener('change', function () {
                onTransporteChange(this.value);
            });
        }


        /* ================== INIT ================== */
        console.log('🚀 Inicializando módulo de planeación de embarques...');

        // Cargar tipos de transporte
        cargarTiposTransporte();

        // Cargar almacenes
        cargarAlmacenes();

    });

</script>


<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>