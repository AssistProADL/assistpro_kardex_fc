<?php
// ======================================================================
//  MÓDULO VISUAL: PLANEACIÓN DE EMBARQUES (modal Pedidos Agregados)
//  Ruta: /public/procesos/planeacion_embarques.php
// ======================================================================

require_once __DIR__ . '/../bi/_menu_global.php';

$hoy     = date('Y-m-d');
$manana  = date('Y-m-d', strtotime('+1 day'));
?>

<style>
    :root{
        --azul-primario:#0F5AAD;   /* Corporativo */
        --azul-acento:#00A3E0;
        --gris-borde:#dbe3f4;
        --gris-fondo:#f5f7fb;
        --gris-td:#f9fbff;
        --gris-header:#e5e6e7;     /* NO tocar */
        --texto:#1f2937;
    }

    html, body{
        font-family: Calibri, "Segoe UI", Roboto, Arial, sans-serif;
        color: var(--texto);
        font-size: 12px;
    }

    .main-panel-emb{ padding:15px; }

    .ibox-title h3{ font-weight:700; color:#1b2a52; }

    /* Cards KPI */
    .card-resumen{ border-radius:12px; padding:16px; margin-bottom:15px; color:#fff; box-shadow:0 1px 3px rgba(15,90,173,.12);}
    .bg-hoy{background: linear-gradient(90deg,var(--azul-primario),#2566c7);}
    .bg-planeados{background: linear-gradient(90deg,var(--azul-acento),#27b7ef);}
    .bg-enruta{background: linear-gradient(90deg,#ffb04d,#f39c3d);}
    .bg-retrasados{background: linear-gradient(90deg,#ef5b6b,#d94151);}
    .card-resumen .titulo{ font-size:10px; text-transform:uppercase; opacity:.9; letter-spacing:.3px;}
    .card-resumen .valor{ font-size:22px; font-weight:800;}
    .card-resumen .detalle{ font-size:10px; opacity:.95;}

    /* Bloques encabezado en color corporativo */
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
        padding:12px;
        border-radius:0 0 8px 8px;
    }

    /* Filtros */
    .filtros .form-group{ margin-bottom:8px; }
    .filtros label{
        font-size:10px; font-weight:700; color:#2b3e6b; margin-bottom:3px;
        text-transform:uppercase; letter-spacing:.2px;
    }
    .filtros .form-control{
        font-size:12px; height:32px; padding:4px 8px; border-radius:8px;
        border:1px solid var(--gris-borde); background:#fff;
    }
    .filtros .form-control:focus{ outline:none; border-color: var(--azul-acento); box-shadow:0 0 0 3px rgba(0,163,224,.15); }
    .btn.btn-primary{ background: var(--azul-primario); border-color: var(--azul-primario); }
    .btn.btn-primary:hover{ filter:brightness(.95); }

    /* Tabs */
    .nav-tabs>li>a{ font-size:11px; padding:8px 12px; font-weight:600; color:#2b3e6b;}
    .nav-tabs>li.active>a, .nav-tabs>li>a:focus, .nav-tabs>li>a:hover{ border-color: var(--azul-primario) var(--azul-primario) transparent; color: var(--azul-primario); }

    /* Tablas (mantener grises) */
    .tabla, .tabla-resumen-carga{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid var(--gris-borde); border-radius:8px; overflow:hidden; }
    .tabla th, .tabla td, .tabla-resumen-carga th, .tabla-resumen-carga td{ font-size:10px !important; white-space:nowrap; vertical-align:middle; padding:8px 10px; }
    .tabla-resumen-carga thead th{ background: var(--gris-header); color:#333; border-bottom:0; font-weight:700; }
    .tabla tbody tr:nth-child(even){ background: var(--gris-td); }
    .tabla tbody tr:hover{ background:#eef5ff; }

    /* DataTables */
    .dataTables_wrapper .dataTables_filter input{ height:30px; border-radius:8px; border:1px solid var(--gris-borde); }
    .dataTables_wrapper .dataTables_paginate .paginate_button{
        border:1px solid var(--gris-borde)!important; border-radius:6px!important;
        padding:2px 8px!important; margin:0 2px!important; background:#fff!important; color:#2b3e6b!important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current{
        background: var(--azul-primario)!important; color:#fff!important; border-color: var(--azul-primario)!important;
    }
    .dataTables_wrapper .dataTables_length select{ height:28px; border-radius:6px; border:1px solid var(--gris-borde); font-size:10px; }

    .badge-subtitle{ font-size:10px; font-weight:700; color:#2b3e6b; margin-bottom:4px; text-transform:uppercase; }
    textarea.form-control{ font-size:12px; border-radius:8px; border:1px solid var(--gris-borde); }
    .btn-brand{ background: var(--azul-acento); border-color: var(--azul-acento); color:#fff; }
    .btn-brand:hover{ filter:brightness(.95); }

    .mb-5px{margin-bottom:5px;} .mb-10px{margin-bottom:10px;} .mt-10px{margin-top:10px;}
    .toolbar-actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
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

                        <!-- FILTROS -->
                        <div class="row filtros">
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Almacén</label>
                                    <select class="form-control">
                                        <option>[Todos]</option>
                                        <option>(100) - Producto Terminado ADVL</option>
                                        <option>(200) - Materia Prima</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Cliente</label>
                                    <input type="text" class="form-control" placeholder="Cliente...">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Ruta</label>
                                    <select class="form-control">
                                        <option>[Todas]</option>
                                        <option>Ruta 01</option>
                                        <option>Ruta 02</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Estatus</label>
                                    <select class="form-control">
                                        <option>[Todos]</option>
                                        <option>Planeado</option>
                                        <option>En ruta</option>
                                        <option>Entregado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Desde</label>
                                    <input type="date" class="form-control" value="<?= $hoy ?>">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <div class="form-group">
                                    <label>Hasta</label>
                                    <input type="date" class="form-control" value="<?= $manana ?>">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- CARDS -->
                        <div class="row">
                            <div class="col-md-3 col-sm-6"><div class="card-resumen bg-hoy text-center"><div class="titulo">Embarques del día</div><div class="valor">18</div><div class="detalle">Programados para hoy</div></div></div>
                            <div class="col-md-3 col-sm-6"><div class="card-resumen bg-planeados text-center"><div class="titulo">Planeados</div><div class="valor">42</div><div class="detalle">En ventana de 7 días</div></div></div>
                            <div class="col-md-3 col-sm-6"><div class="card-resumen bg-enruta text-center"><div class="titulo">En ruta</div><div class="valor">9</div><div class="detalle">Unidades en tránsito</div></div></div>
                            <div class="col-md-3 col-sm-6"><div class="card-resumen bg-retrasados text-center"><div class="titulo">Retrasados</div><div class="valor">3</div><div class="detalle">Fuera de ventana</div></div></div>
                        </div>

                        <hr>

                        <!-- TABS -->
                        <ul class="nav nav-tabs">
                            <li class="active"><a data-toggle="tab" href="#t_planeacion"><i class="fa fa-calendar"></i> Planeación</a></li>
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
                                            <select class="form-control">
                                                <option>(100) - Producto Terminado ADVL</option>
                                                <option>(200) - Materia Prima</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Área de embarque</span>
                                            <select class="form-control">
                                                <option>Área de Embarque 1</option>
                                                <option>Área de Embarque 2</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Ruta</span>
                                            <select class="form-control">
                                                <option>Seleccione</option>
                                                <option>Ruta 01</option>
                                                <option>Ruta 02</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-5px">
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Cliente</span>
                                            <input type="text" class="form-control" placeholder="Cliente...">
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Colonia</span>
                                            <input type="text" class="form-control" placeholder="Colonia...">
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge-subtitle">Código Postal</span>
                                            <input type="text" class="form-control" placeholder="Código Postal">
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
                                                    <td>6</td><td>6</td><td>1849.00</td><td>3.020000</td><td>3272</td><td>9</td><td>9</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge-subtitle">Número de pedido</span>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Número de pedido">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary btn-sm" type="button"><i class="fa fa-search"></i> Buscar</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Transporte -->
                                    <div class="row mb-10px">
                                        <div class="col-md-6">
                                            <span class="badge-subtitle">Transporte</span>
                                            <select class="form-control">
                                                <option>Seleccione</option>
                                                <option>Unidad 1 - Caja 53'</option>
                                                <option>Unidad 2 - Rabón</option>
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
                                                            <thead><tr><th>Capacidad Carga KG</th><th>Capacidad Volumétrica m3</th></tr></thead>
                                                            <tbody><tr><td>10,000</td><td>40.00</td></tr></tbody>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <thead>
                                                            <tr><th colspan="3">Dimensiones del Vehículo</th></tr>
                                                            <tr><th>Altura Mts</th><th>Ancho Mts</th><th>Fondo Mts</th></tr>
                                                            </thead>
                                                            <tbody><tr><td>2.70</td><td>2.40</td><td>13.60</td></tr></tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <table class="table table-bordered tabla-resumen-carga">
                                                            <thead><tr><th>Capacidad Utilizada KG</th><th>Volumen Ocupado m3</th></tr></thead>
                                                            <tbody><tr><td>1,849.00</td><td>3.02</td></tr></tbody>
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

                                    <!-- BOTÓN Bootstrap 5 -->
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
                                            <td>CANAL RODRIGUEZ MARIA DEL CARMEN</td>
                                            <td>Área de Embarque 2</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <button class="btn btn-xs btn-default"><i class="fa fa-search"></i></button>
                                                <button class="btn btn-xs btn-default"><i class="fa fa-list"></i></button>
                                            </td>
                                            <td><button class="btn btn-xs btn-warning">&gt;&gt;</button></td>
                                            <td>PED151020251</td>
                                            <td>15-10-2024</td>
                                            <td>15-10-2024</td>
                                            <td>-</td>
                                            <td>R002</td>
                                            <td>9501</td>
                                            <td>77127</td>
                                            <td>ADVL</td>
                                            <td>62 X 77 Y 75 608A</td>
                                            <td>97000</td>
                                            <td>CENTRO</td>
                                            <td>19.3611111</td>
                                            <td>-99.000000</td>
                                            <td>2</td>
                                            <td>20.0000</td>
                                            <td>2</td>
                                            <td>62.5</td>
                                            <td>0.68</td>
                                            <td>ADVL</td>
                                            <td>Área de Embarque 2</td>
                                        </tr>
                                        </tbody>
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
                                            <th>Folio</th><th>Salida Prog.</th><th>Salida Real</th><th>Almacén</th><th>Cliente</th>
                                            <th>Ruta</th><th>Transportista</th><th>Operador</th><th>Cajas</th>
                                            <th>Tarimas</th><th>Destino</th><th>Estatus</th><th>Desviación</th>
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

                        </div><!-- /.tab-content -->
                    </div><!-- /.ibox-content -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Pedidos Agregados (Bootstrap 5) -->
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
    $(function(){
        function dt(id){
            $(id).DataTable({
                pageLength: 10,
                scrollX: true,
                language: { url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json" },
                lengthChange:false
            });
        }
        dt('#tabla_pedidos');
        dt('#tabla_dia');
        dt('#tabla_hist');

        // Fallback JS por si el HTML se renderiza sin los data-bs- (no debería ser necesario)
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(btn){
            btn.addEventListener('click', function(e){
                var sel = btn.getAttribute('data-bs-target');
                if (window.bootstrap && bootstrap.Modal){
                    var el = document.querySelector(sel);
                    var modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
