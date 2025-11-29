<?php
// ======================================================================
//  MÓDULO VISUAL: PLANEACIÓN DE EMBARQUES (modal Pedidos Agregados)
//  Ruta: /public/procesos/Embarques/embarques_planeacion.php
// ======================================================================

require_once __DIR__ . '/../../bi/_menu_global.php';

$hoy     = date('Y-m-d');
$manana  = date('Y-m-d', strtotime('+1 day'));
?>

<style>
    :root{
        --azul-primario:#0F5AAD;   /* Corporativo */
        --azul-acento:#00A3E0;
        --gris-fondo:#f5f7fb;
        --gris-borde:#d9e2ef;
        --gris-header:#eef2fb;
        --gris-td:#f9fafc;
        --texto:#2b3e6b;
        --texto-sec:#7a8bb0;
    }
    .main-panel-emb{
        padding:15px;
        background:var(--gris-fondo);
    }
    .ibox{
        border-radius:10px;
        box-shadow:0 2px 6px rgba(15,90,173,.06);
        border:1px solid #e3e8f0;
        background:#fff;
    }
    .ibox-title{
        padding:10px 15px;
        border-bottom:1px solid #edf1f7;
    }
    .ibox-title h3{
        margin:0;
        font-size:16px;
        font-weight:700;
        color:var(--texto);
        display:flex;
        align-items:center;
        gap:8px;
    }
    .ibox-title h3 i{color:var(--azul-primario);}
    .ibox-content{padding:12px 15px 15px 15px;}

    .filtros .form-group{margin-bottom:8px;}
    .filtros label{
        font-size:10px;
        font-weight:700;
        color:var(--texto);
        margin-bottom:3px;
        text-transform:uppercase;
        letter-spacing:.2px;
    }
    .filtros .form-control{
        font-size:12px;
        height:32px;
        padding:4px 8px;
        border-radius:8px;
        border:1px solid var(--gris-borde);
        background:#fff;
    }
    .filtros .form-control:focus{
        outline:none;
        border-color:var(--azul-acento);
        box-shadow:0 0 0 3px rgba(0,163,224,.15);
    }
    .btn.btn-primary{
        background:var(--azul-primario);
        border-color:var(--azul-primario);
        font-size:11px;
        border-radius:8px;
    }
    .btn.btn-primary:hover{
        background:#0c488a;
        border-color:#0c488a;
    }

    .card-resumen{
        border-radius:10px;
        padding:14px 10px;
        margin-bottom:10px;
        color:#fff;
        box-shadow:0 1px 3px rgba(15,90,173,.12);
    }
    .bg-hoy{background:linear-gradient(135deg,#0F5AAD,#2566c7);}
    .bg-planeados{background:linear-gradient(135deg,#00A3E0,#22b7ff);}
    .bg-enruta{background:linear-gradient(135deg,#ff9800,#ffc107);}
    .bg-retrasados{background:linear-gradient(135deg,#e53935,#ff5252);}
    .card-resumen .titulo{
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:.3px;
        margin-bottom:4px;
        font-weight:600;
    }
    .card-resumen .valor{
        font-size:22px;
        font-weight:700;
        line-height:1.1;
    }
    .card-resumen .detalle{
        font-size:10px;
        opacity:.9;
    }

    .nav-tabs{
        border-bottom:1px solid var(--gris-borde);
        margin-bottom:8px;
    }
    .nav-tabs>li>a{
        font-size:11px;
        padding:8px 12px;
        font-weight:600;
        color:var(--texto);
    }
    .nav-tabs>li.active>a,
    .nav-tabs>li>a:focus,
    .nav-tabs>li>a:hover{
        border:1px solid var(--gris-borde);
        border-bottom-color:#fff;
        background:#fff;
        color:var(--azul-primario);
    }

    .section-title{
        font-size:11px;
        font-weight:700;
        color:var(--texto);
        text-transform:uppercase;
        letter-spacing:.2px;
        margin-bottom:3px;
    }
    .section-body{
        border:1px solid var(--gris-borde);
        border-radius:8px;
        padding:8px 10px;
        background:#fff;
    }

    .badge-subtitle{
        font-size:10px;
        font-weight:700;
        color:var(--texto);
        margin-bottom:4px;
        text-transform:uppercase;
    }

    .tabla-resumen-carga{
        width:100%;
        border-collapse:separate;
        border-spacing:0;
        font-size:10px;
    }
    .tabla-resumen-carga th,
    .tabla-resumen-carga td{
        border:1px solid var(--gris-borde);
        padding:4px 6px;
        text-align:center;
        white-space:nowrap;
    }
    .tabla-resumen-carga thead th{
        background:var(--gris-header);
        font-weight:700;
    }

    .tabla-wrapper{
        border:1px solid var(--gris-borde);
        border-radius:8px;
        overflow:hidden;
        background:#fff;
        margin-top:5px;
    }
    table.tabla{
        width:100%;
        font-size:10px;
        border-collapse:separate;
        border-spacing:0;
    }
    table.tabla thead th{
        background:var(--gris-header);
        color:var(--texto);
        font-weight:700;
        border-bottom:1px solid var(--gris-borde);
        padding:6px 8px;
        white-space:nowrap;
    }
    table.tabla tbody td{
        padding:4px 8px;
        white-space:nowrap;
        border-bottom:1px solid #edf1f7;
    }
    table.tabla tbody tr:nth-child(even){
        background:var(--gris-td);
    }

    .toolbar-actions{
        font-size:10px;
    }
    .toolbar-actions label{
        font-weight:normal;
        margin-right:10px;
    }

    .box-header-dark{
        background: var(--azul-primario);
        color:#fff;
        padding:8px 12px;
        font-size:12px;
        font-weight:700;
        border-radius:8px 8px 0 0;
        letter-spacing:.2px;
    }
    .box-body-light{
        background: var(--gris-fondo);
        border:1px solid var(--gris-borde);
        border-top:none;
        border-radius:0 0 8px 8px;
        padding:8px 10px;
        margin-bottom:8px;
    }

    .mt-10px{margin-top:10px;}
    .mb-10px{margin-bottom:10px;}
    .mb-5px{margin-bottom:5px;}
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

                        <!-- FILTROS SUPERIORES -->
                        <div class="row filtros">
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Empresa</label>
                                    <select class="form-control" id="filtro_empresa">
                                        <option value="">[Todas]</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Almacén</label>
                                    <select class="form-control" id="filtro_almacen">
                                        <option value="">[Todos]</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Cliente</label>
                                    <select class="form-control" id="filtro_cliente">
                                        <option value="">[Todos]</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Ruta</label>
                                    <select class="form-control" id="filtro_ruta">
                                        <option value="">[Todas]</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Estatus</label>
                                    <select class="form-control" id="filtro_estatus">
                                        <option value="">[Todos]</option>
                                        <option value="T">En ruta</option>
                                        <option value="F">Entregado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Desde</label>
                                    <input type="date" class="form-control" id="filtro_fecha_desde" value="<?= $hoy ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row filtros">
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Hasta</label>
                                    <input type="date" class="form-control" id="filtro_fecha_hasta" value="<?= $manana ?>">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-primary btn-block" id="btn_aplicar_filtros">
                                        <i class="fa fa-filter"></i> Aplicar filtros
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-8 col-sm-4">
                                <div class="form-group" style="margin-top:2px;">
                                    <label style="display:block;">&nbsp;</label>
                                    <label style="margin-right:10px;"><input type="checkbox"> Generar todos los reportes</label>
                                    <label style="margin-right:10px;"><input type="checkbox"> Entregar a cliente</label>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPedidosAgregados">
                                        <i class="fa fa-boxes-stacked"></i> Pedidos Agregados
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- CARDS KPI -->
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-hoy text-center">
                                    <div class="titulo">Embarques del día</div>
                                    <div class="valor">18</div>
                                    <div class="detalle">Programados para hoy</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-planeados text-center">
                                    <div class="titulo">Planeados</div>
                                    <div class="valor">42</div>
                                    <div class="detalle">En ventana de 7 días</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-enruta text-center">
                                    <div class="titulo">En ruta</div>
                                    <div class="valor">9</div>
                                    <div class="detalle">Unidades en tránsito</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="card-resumen bg-retrasados text-center">
                                    <div class="titulo">Retrasados</div>
                                    <div class="valor">3</div>
                                    <div class="detalle">Fuera de ventana</div>
                                </div>
                            </div>
                        </div>

                        <!-- TABS -->
                        <ul class="nav nav-tabs">
                            <li class="active"><a data-toggle="tab" href="#t_planeacion"><i class="fa fa-calendar"></i> Planeación</a></li>
                            <li><a data-toggle="tab" href="#t_dia"><i class="fa fa-road"></i> Embarques del día</a></li>
                            <li><a data-toggle="tab" href="#t_hist"><i class="fa fa-archive"></i> Histórico</a></li>
                        </ul>

                        <div class="tab-content" style="margin-top:8px;">

                            <!-- TAB PLANEACIÓN -->
                            <div id="t_planeacion" class="tab-pane active">
                                <div class="box-header-dark">Planeación de Embarques</div>
                                <div class="box-body-light">

                                    <!-- Filtros específicos -->
                                    <div class="row mb-5px">
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Almacén</span>
                                            <select class="form-control" id="filtro_almacen_plan">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Área de embarque</span>
                                            <select class="form-control" id="filtro_zona_plan">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Ruta</span>
                                            <select class="form-control" id="filtro_ruta_plan">
                                                <option value="">[Todas]</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-5px">
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Cliente</span>
                                            <select class="form-control" id="filtro_cliente_plan">
                                                <option value="">[Todos]</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Colonia</span>
                                            <input type="text" class="form-control" id="filtro_colonia_plan" placeholder="Colonia...">
                                        </div>
                                        <div class="col-md-2">
                                            <span class="badge-subtitle">Código Postal</span>
                                            <input type="text" class="form-control" id="filtro_cp_plan" placeholder="CP">
                                        </div>
                                        <div class="col-md-2">
                                            <span class="badge-subtitle">&nbsp;</span>
                                            <button class="btn btn-primary btn-block" id="btn_refrescar_planeacion">
                                                <i class="fa fa-refresh"></i> Refrescar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- RESUMEN -->
                                    <div class="row mb-5px">
                                        <div class="col-md-9">
                                            <table class="tabla-resumen-carga">
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
                                                    <td id="sum_num_pedidos">0</td>
                                                    <td id="sum_num_entregas">0</td>
                                                    <td id="sum_peso_total_kg">0</td>
                                                    <td id="sum_volumen_total_m3">0</td>
                                                    <td id="sum_total_piezas">0</td>
                                                    <td id="sum_total_guias">0</td>
                                                    <td id="sum_total_pallets">0</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge-subtitle">Número de pedido</span>
                                            <div class="input-group">
                                                <input type="text" id="input_numero_pedido" class="form-control" placeholder="Número de pedido">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary" id="btn_buscar_pedido"><i class="fa fa-search"></i></button>
                                                </span>
                                            </div>
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
                                                            <tr><th>Capacidad Carga KG</th><th>Capacidad Volumétrica m3</th></tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr><td>10,000</td><td>40.00</td></tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <thead>
                                                            <tr><th>Altura Mts</th><th>Ancho Mts</th><th>Fondo Mts</th></tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr><td>2.70</td><td>2.40</td><td>13.60</td></tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="row mb-10px">
                                                    <div class="col-md-6">
                                                        <span class="badge-subtitle">Capacidad Utilizada KG / Volumen Ocupado m3</span>
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <tbody>
                                                            <tr>
                                                                <td>1,849.00</td>
                                                                <td>3.02</td>
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
                                <div class="section-title mt-10px" style="font-weight:700;">Pedidos disponibles para embarque</div>
                                <div class="toolbar-actions mb-5px">
                                    <label class="checkbox-inline"><input type="checkbox"> Generar Todos los Reportes</label>
                                    <label class="checkbox-inline" style="margin-left:15px;"><input type="checkbox"> Entregar a Cliente</label>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPedidosAgregados">
                                        <i class="fa fa-boxes-stacked"></i> Pedidos Agregados
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover tabla" id="tabla_pedidos">
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
                                        <tbody>
                                        <!-- filas de ejemplo iniciales (serán reemplazadas por DataTables) -->
                                        <tr>
                                            <td>
                                                <button class="btn btn-xs btn-default"><i class="fa fa-search"></i></button>
                                                <button class="btn btn-xs btn-default"><i class="fa fa-list"></i></button>
                                            </td>
                                            <td><button class="btn btn-xs btn-warning">&gt;&gt;</button></td>
                                            <td>S202510161</td>
                                            <td>15-10-2025</td>
                                            <td>15-10-2025</td>
                                            <td>-</td>
                                            <td>R001</td>
                                            <td>11138403</td>
                                            <td>67497</td>
                                            <td>Monserrat de la Mora</td>
                                            <td>Marrocos 15 casa 9 Colonia México 68</td>
                                            <td>53260</td>
                                            <td>México 68</td>
                                            <td>19.3616381</td>
                                            <td>-99.110018</td>
                                            <td>1</td>
                                            <td>1.0000</td>
                                            <td>1</td>
                                            <td>0.25</td>
                                            <td>0.00</td>
                                            <td>Cliente demo 1</td>
                                            <td>Isla 1</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-10px mb-10px">
                                    <button class="btn btn-primary btn-sm">Generar Reportes Etiquetas</button>
                                </div>

                            </div><!-- /TAB PLANEACION -->

                            <!-- TAB DÍA -->
                            <div id="t_dia" class="tab-pane">
                                <div class="box-header-dark">Embarques del día</div>
                                <div class="box-body-light">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover tabla" id="tabla_dia">
                                            <thead>
                                            <tr>
                                                <th>Folio</th>
                                                <th>Hora Salida</th>
                                                <th>Hora Llegada</th>
                                                <th>Almacén</th>
                                                <th>Cliente</th>
                                                <th>Ruta</th>
                                                <th>Transportista</th>
                                                <th>Operador</th>
                                                <th>Cajas</th>
                                                <th>Tarimas</th>
                                                <th>Destino</th>
                                                <th>Estatus</th>
                                                <th>Retraso</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>EMB-240010</td><td>08:00</td><td>08:12</td><td>WH1</td><td>Cliente A</td>
                                                <td>RT01</td><td>Transportes del Norte</td><td>Juan Pérez</td>
                                                <td>380</td><td>16</td><td>CDMX</td><td>En ruta</td><td>+12 min</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB HISTÓRICO -->
                            <div id="t_hist" class="tab-pane">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover tabla" id="tabla_hist">
                                        <thead>
                                        <tr>
                                            <th>Folio</th><th>Fecha</th><th>Almacén</th><th>Cliente</th><th>Ruta</th>
                                            <th>Transportista</th><th>Tipo</th><th>Cajas</th><th>Tarimas</th>
                                            <th>Destino</th><th>Estatus</th><th>Entrega</th><th>On Time</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>EMB-239950</td><td><?= date('Y-m-d', strtotime('-3 days')) ?></td><td>WH1</td><td>Cliente A</td>
                                            <td>RT01</td><td>Transportes del Norte</td><td>Full</td><td>400</td><td>17</td>
                                            <td>CDMX</td><td>Entregado</td><td>2025-11-08 16:40</td><td>Sí</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div><!-- /tab-content -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Pedidos Agregados -->
<div class="modal fade" id="modalPedidosAgregados" tabindex="-1" aria-labelledby="modalPedidosAgregadosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width:1200px;">
        <div class="modal-content">
            <div class="modal-header" style="background:#0F5AAD;color:#fff;">
                <h5 class="modal-title" id="modalPedidosAgregadosLabel"><i class="fa fa-boxes-stacked"></i> Pedidos agregados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" style="filter:invert(1); opacity:1;"></button>
            </div>
            <div class="modal-body" style="background:#f7f9ff;">
                <div class="container-fluid">
                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">Guía de Transporte:</span>
                            <input type="text" class="form-control" placeholder="Número de guía">
                        </div>
                        <div class="col-md-6">
                            <span class="badge-subtitle">Transportista:</span>
                            <input type="text" class="form-control" placeholder="Transportista">
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-6">
                            <span class="badge-subtitle">Operador:</span>
                            <input type="text" class="form-control" placeholder="Operador">
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

                    <div class="row mb-10px">
                        <div class="col-md-12">
                            <span class="badge-subtitle">Destinatario:</span>
                            <textarea class="form-control" rows="6" placeholder="Destinatario:
RFC:
Domicilio:
Ciudad:
Se Entregará En:"></textarea>
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-12">
                            <span class="badge-subtitle">Notas:</span>
                            <textarea class="form-control" rows="4" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>

                    <div class="row mb-10px">
                        <div class="col-md-12">
                            <span class="badge-subtitle">Pedidos agregados al embarque</span>
                            <div class="tabla-wrapper">
                                <table class="table table-striped table-bordered tabla" id="tabla_pedidos_agregados">
                                    <thead>
                                    <tr>
                                        <th>Quitar</th>
                                        <th>Folio Pedido</th>
                                        <th>Cliente</th>
                                        <th>Destino</th>
                                        <th>Ruta</th>
                                        <th>Cajas</th>
                                        <th>Peso</th>
                                        <th>Volumen</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><button class="btn btn-xs btn-danger"><i class="fa fa-times"></i></button></td>
                                        <td>S202510161</td>
                                        <td>Cliente demo 1</td>
                                        <td>CDMX</td>
                                        <td>RT01</td>
                                        <td>120</td>
                                        <td>345.00</td>
                                        <td>1.23</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary">Guardar Embarque</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery y DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(function(){
        // =====================================================
        // CONFIGURACIÓN DE DATATABLES
        // =====================================================
        var tablaPedidos, tablaDia, tablaHist;

        function dt(id){
            return $(id).DataTable({
                pageLength: 10,
                scrollX: true,
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar la columna de manera ascendente",
                        "sortDescending": ": activar para ordenar la columna de manera descendente"
                    }
                },
                lengthChange: false,
                destroy: true
            });
        }

        tablaPedidos = dt('#tabla_pedidos');
        tablaDia     = dt('#tabla_dia');
        tablaHist    = dt('#tabla_hist');

        // =====================================================
        // VARIABLES GLOBALES
        // =====================================================
        var datosCache = {
            empresas: [],
            rutas   : [],
            terceros: [],
            productos: [],
            almacenes: [],
            zonas: []
        };

        // =====================================================
        // UTILIDADES
        // =====================================================
        function poblarSelect(selector, items, campoId, campoTexto, textoDefault){
            var $sel = $(selector);
            $sel.empty();

            if (textoDefault !== undefined && textoDefault !== null) {
                $sel.append($('<option>', { value:'', text:textoDefault }));
            }

            if (!Array.isArray(items)) return;

            items.forEach(function(it){
                $sel.append(
                    $('<option>', {
                        value: it[campoId],
                        text: it[campoTexto]
                    })
                );
            });
        }

        function mostrarMensaje(mensaje, tipo){
            var clase = 'alert-info';
            var icono = 'fa-info-circle';

            if (tipo === 'success') {
                clase = 'alert-success';
                icono = 'fa-check-circle';
            } else if (tipo === 'error') {
                clase = 'alert-danger';
                icono = 'fa-times-circle';
            } else if (tipo === 'warning') {
                clase = 'alert-warning';
                icono = 'fa-exclamation-triangle';
            }

            var html = '<div class="alert ' + clase + ' alert-dismissible" ' +
                'style="position:fixed;top:70px;right:20px;z-index:9999;min-width:300px;">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<i class="fa ' + icono + '"></i> ' + mensaje +
                '</div>';

            $('body').append(html);
            setTimeout(function() {
                $('.alert').fadeOut(function() { $(this).remove(); });
            }, 4000);
        }

        // =====================================================
        // CARGA DE DATOS BASE (fn=base)
        // =====================================================
        // =====================================================
// CARGA DE DATOS BASE
// =====================================================
        function cargarDatosBase() {
            console.log('⏳ Cargando datos base...');

            $.ajax({
                url: '../../api/api_embarques_planeacion.php?fn=base',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('✓ Datos base recibidos:', data);

                    datosCache.empresas  = data.empresas  || [];
                    datosCache.rutas     = data.rutas     || [];
                    datosCache.terceros  = data.terceros  || [];
                    datosCache.productos = data.productos || [];

                    poblarSelect('#filtro_empresa', datosCache.empresas, 'id', 'nombre', '[Todas]');
                    poblarSelect('#filtro_ruta',     datosCache.rutas,    'id', 'nombre', '[Todas]');
                    poblarSelect('#filtro_ruta_plan',datosCache.rutas,    'id', 'nombre', '[Todas]');
                    poblarSelect('#filtro_cliente',  datosCache.terceros, 'id', 'nombre', '[Todos]');
                    poblarSelect('#filtro_cliente_plan', datosCache.terceros, 'id', 'nombre', '[Todos]');

                    if (datosCache.empresas.length > 0) {
                        $('#filtro_empresa').val(datosCache.empresas[0].id);
                        cargarAlmacenes(datosCache.empresas[0].id);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error cargando datos base:', error, xhr.responseText);
                }
            });
        }

// =====================================================
// CARGA DE ALMACENES
// =====================================================
        function cargarAlmacenes(empresaId) {
            if (!empresaId) {
                poblarSelect('#filtro_almacen', [], 'id', 'nombre', '[Todos]');
                poblarSelect('#filtro_almacen_plan', [], 'id', 'nombre', 'Seleccione');
                return;
            }

            $.ajax({
                url: '../../api/api_embarques_planeacion.php?fn=almacenes&empresa=' + encodeURIComponent(empresaId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    datosCache.almacenes = data.almacenes || [];

                    poblarSelect('#filtro_almacen', datosCache.almacenes, 'id', 'nombre', '[Todos]');
                    poblarSelect('#filtro_almacen_plan', datosCache.almacenes, 'id', 'nombre', 'Seleccione');
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error cargando almacenes:', error, xhr.responseText);
                }
            });
        }

// =====================================================
// CARGA DE ZONAS
// =====================================================
        function cargarZonas(almacenId, empresaId) {
            var url = '../../api/api_embarques_planeacion.php?fn=zonas';
            var params = [];

            if (almacenId) params.push('almacen=' + encodeURIComponent(almacenId));
            if (empresaId) params.push('empresa=' + encodeURIComponent(empresaId));

            if (params.length) url += '&' + params.join('&');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    datosCache.zonas = data.zonas || [];
                    poblarSelect('#filtro_zona_plan', datosCache.zonas, 'id', 'nombre', 'Seleccione');
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error cargando zonas:', error, xhr.responseText);
                }
            });
        }

// =====================================================
// BUSCAR PEDIDO POR NÚMERO
// =====================================================
        function buscarPedidoNumero() {
            var numero = $('#input_numero_pedido').val().trim();

            if (!numero) {
                mostrarMensaje('Ingrese un número de pedido', 'error');
                return;
            }

            $.ajax({
                url: '../../api/api_embarques_planeacion.php?fn=buscar_pedido',
                method: 'GET',
                data: { numero: numero },
                dataType: 'json',
                success: function(data) {
                    var pedidos = data.pedidos || data.data || data.rows || [];
                    actualizarTablaPedidos(pedidos);
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error buscando pedido:', error, xhr.responseText);
                }
            });
        }

// =====================================================
// BUSCAR PEDIDOS (APLICAR FILTROS)
// =====================================================
        function buscarPedidos() {
            var filtros = {
                empresa    : $('#filtro_empresa').val(),
                almacen    : $('#filtro_almacen').val(),
                cliente    : $('#filtro_cliente').val(),
                ruta       : $('#filtro_ruta').val(),
                estatus    : $('#filtro_estatus').val(),
                fecha_desde: $('#filtro_fecha_desde').val(),
                fecha_hasta: $('#filtro_fecha_hasta').val()
            };

            $.ajax({
                url: '../../api/api_embarques_planeacion.php?fn=pedidos_embarque',
                method: 'GET',
                data: filtros,
                dataType: 'json',
                success: function(data) {
                    var pedidos = data.pedidos || data.data || data.rows || [];
                    actualizarTablaPedidos(pedidos);

                    if (data.resumen) actualizarResumen(data.resumen);
                    else actualizarResumenDesdePedidos(pedidos);
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error buscando pedidos:', error, xhr.responseText);
                }
            });
        }


        // =====================================================
        // EVENTOS
        // =====================================================
        $('#btn_aplicar_filtros').on('click', function() {
            buscarPedidos();
        });

        $('#btn_buscar_pedido').on('click', function() {
            buscarPedidoNumero();
        });

        $('#input_numero_pedido').on('keypress', function(e) {
            if (e.which === 13) { // Enter
                buscarPedidoNumero();
            }
        });

        $('#filtro_empresa').on('change', function() {
            var empresaId = $(this).val();
            cargarAlmacenes(empresaId);
        });

        $('#filtro_almacen_plan').on('change', function() {
            var almacenId = $(this).val();
            var empresaId = $('#filtro_empresa').val();

            if (almacenId) {
                cargarZonas(almacenId, empresaId);
            } else {
                poblarSelect('#filtro_zona_plan', [], 'id', 'nombre', 'Seleccione');
            }
        });

        // Botón refrescar planeación usa mismos filtros generales
        $('#btn_refrescar_planeacion').on('click', function(){
            buscarPedidos();
        });

        // =====================================================
        // INICIALIZACIÓN
        // =====================================================
        console.log('🚀 Inicializando módulo de embarques...');
        cargarDatosBase();
        console.log('✓ Módulo inicializado');
    });
</script>

<?php require_once __DIR__ . '/../../bi/_menu_global_end.php'; ?>
