<?php
// public/procesos/Reabasto (Replenishment)/ptl.php

// Frame corporativo AssistPro WMS
require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<link href="/css/plugins/ladda/ladda-themeless.min.css" rel="stylesheet">
<link href="/css/plugins/ladda/select2.css" rel="stylesheet"/>
<link href="/css/plugins/dataTables/dataTables1.min.css" rel="stylesheet"/>
<link href="/css/plugins/dataTables/buttons.dataTables.min.css" rel="stylesheet"/>
<link href="/css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
<link href="/css/plugins/datapicker/datepicker3.css" rel="stylesheet">
<link href="/css/plugins/chosen/chosen.css" rel="stylesheet">

<style>
    .bt{
        margin-right: 10px;
    }

    .btn-blue{
        background-color: blue !important;
        border-color: blue !important;
        color: white !important;
    }

    .blink {
        animation-name: blink;
        animation-duration: 4s;
        animation-timing-function: linear;
        animation-iteration-count: infinite;

        -webkit-animation-name:blink;
        -webkit-animation-duration: 4s;
        -webkit-animation-timing-function: linear;
        -webkit-animation-iteration-count: infinite;

        -moz-animation-name:blink;
        -moz-animation-duration: 4s;
        -moz-animation-timing-function: linear;
        -moz-animation-iteration-count: infinite;
    }

    @-moz-keyframes blink{
        0% { opacity: 1.0; }
        50% { opacity: 0.0; }
        100% { opacity: 1.0; }
    }

    @-webkit-keyframes blink {
        0% { opacity: 1.0; }
        50% { opacity: 0.0; }
        100% { opacity: 1.0; }
    }

    @keyframes blink {
        0% { opacity: 1.0; }
        50% { opacity: 0.0; }
        100% { opacity: 1.0; }
    }

    .verde { background: #33ff33; }
    .verde > td{ background: #33ff33; }

    .rojo > td{ background: #FF5233; }
    .rojo { background: #FF5233; }

    .amarillo > td{ background: #fffc33; }
    .amarillo { background: #fffc33; }

    .aa{
        width: 30px;
        height: 30px;
        margin-left: auto;
        margin-right: auto;
        border-radius: 50%;
    }

    /* Ajuste corporativo ligero */
    #list h3{
        font-size:16px;
        font-weight:600;
        color:#1b2a4a;
        margin:4px 0 14px 0;
    }

    #table-info{
        font-size:10px;
        width:100%;
    }
</style>

<div class="wrapper wrapper-content animated" id="list">
    <h3>Reabastecer PTL</h3>

    <div class="row">
        <div class="col-lg-12">
            <div class="tabs-container">
                <ul class="nav nav-tabs">
                    <!-- Por ahora solo PTL -->
                </ul>

                <div class="tab-content">
                    <!-- Tab PTL -->
                    <div id="tab-2" class="tab-pane active">
                        <div class="panel-body">
                            <div class="ibox">
                                <div class="ibox-title">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <!-- Espacio reservado para filtros futuros -->
                                        </div>
                                    </div>
                                </div>

                                <div class="ibox-content">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <!-- Espacio adicional para controles -->
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="table-info" class="table table-hover table-striped no-margin display nowrap">
                                            <thead>
                                            <tr>
                                                <th></th>
                                                <th>Acciones</th>
                                                <th>Clave de Producto</th>
                                                <th>Descripción</th>
                                                <th>BL</th>
                                                <th>Pasillo</th>
                                                <th>Rack</th>
                                                <th>Nivel</th>
                                                <th>Sección</th>
                                                <th>Posición</th>
                                                <!--th>Peso Màx.</th>
                                                <th>Volumen (m3)</th>
                                                <th>Dimensiones (Lar. X Anc. X Alt. )</th-->
                                                <th>Maximo</th>
                                                <th>Minimo</th>
                                                <th>Existencia</th>
                                                <th>Reabastecer</th>
                                                <th>Status</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <!-- Vacío por ahora; DataTables mostrará “No Existen Registros” -->
                                            </tbody>
                                        </table>
                                    </div><!-- /.table-responsive -->
                                </div><!-- /.ibox-content -->
                            </div><!-- /.ibox -->
                        </div><!-- /.panel-body -->
                    </div><!-- /#tab-2 -->
                </div><!-- /.tab-content -->
            </div><!-- /.tabs-container -->
        </div>
    </div>
</div>

<!-- JS -->
<script src="/js/jquery-2.1.4.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="/js/moment.js"></script>

<script src="/js/inspinia.js"></script>
<script src="/js/plugins/pace/pace.min.js"></script>
<script src="/js/plugins/ladda/spin.min.js"></script>
<script src="/js/plugins/ladda/ladda.min.js"></script>
<script src="/js/plugins/ladda/ladda.jquery.min.js"></script>
<script src="/js/plugins/sweetalert/sweetalert.min.js"></script>

<!-- Select -->
<script src="/js/select2.js"></script>
<!-- Datepicker -->
<script src="/js/plugins/datapicker/bootstrap-datepicker.js"></script>

<!-- DataTables -->
<script src="/js/plugins/dataTables/dataTables.min.js"></script>
<script src="/js/plugins/dataTables/dataTables_bootstrap.min.js"></script>
<script src="/js/plugins/dataTables/dataTables.buttons.min.js"></script>
<script src="/js/plugins/dataTables/buttons.flash.min.js"></script>
<script src="/js/plugins/dataTables/jszip.min.js"></script>
<script src="/js/plugins/dataTables/pdfmake.min.js"></script>
<script src="/js/plugins/dataTables/vfs_fonts.js"></script>
<script src="/js/plugins/dataTables/buttons.html5.min.js"></script>
<script src="/js/plugins/dataTables/buttons.print.min.js"></script>
<script src="/js/plugins/chosen/chosen.jquery.js"></script>

<script>
    $(document).ready(function(){

        // *** SOLO VISUAL ***
        // Inicializamos DataTables para mostrar botones Excel / PDF y la tabla vacía.
        $('#table-info').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'Reabastecer PTL',
                    text: 'Excel',
                    className: 'btn btn-blue bt',
                    customize: function () {
                        swal("Descargando Excel", "Su descarga empezará en breve", "success");
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: 'Reabastecer PTL',
                    text: 'PDF',
                    orientation: 'portrait',
                    pageSize: 'A4',
                    className: 'btn btn-danger bt',
                    customize: function () {
                        swal("Descargando PDF", "Su descarga empezará en breve", "success");
                    }
                }
            ],
            language: {
                emptyTable:   "No Existen Registros",
                search:       "Buscar",
                lengthMenu:   "Mostrar _MENU_ registros",
                info:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty:    "Mostrando 0 a 0 de 0 registros",
                paginate: {
                    previous:   "Anterior",
                    next:       "Siguiente"
                }
            },
            pageLength: 25,
            scrollX: true,
            order: []
        });

        // *** LÓGICA ORIGINAL COMENTADA (para activar después) ***
        /*
        var tableDataInfo = new TableDataRest(),
            TABLE = null,
            buttons = [{
                    extend: 'excelHtml5',
                    title: 'Reabastecer PTL',
                    customize: function() {
                        swal("Descargando Excel", "Su descarga empezara en breve", "success");
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: 'Reabastecer PTL',
                    orientation: 'portrait',
                    pageSize: 'A4',
                    download: 'open',
                    customize: function() {
                        swal("Descargando PDF", "Su descarga empezara en breve", "success");
                    }
                }
            ];

        function searchData() {
            $.ajax({
                url: "/api/reabasto/lista/ptl.php?m=2",
                type: "GET",
                dataType: "json",
                success: function (res) {
                    if (res.status === "ok") {
                        fillTableInfo(res.data);
                    }
                }
            });
        }

        function fillTableInfo(node){
            var data = [];
            for (var i = 0; i < node.length; i++) {
                data.push([
                    '',                 // columna vacía
                    node[i].accioness,
                    node[i].idy_ubica,
                    node[i].des_articulo,
                    node[i].CodigoCSD,
                    node[i].cve_pasillo,
                    node[i].cve_rack,
                    node[i].cve_nivel,
                    node[i].Seccion,
                    node[i].Ubicacion,
                    node[i].Maximo,
                    node[i].Minimo,
                    node[i].Existencia,
                    node[i].Reabastecer,
                    node[i].Status
                ]);
            }
            tableDataInfo.destroy();
            tableDataInfo.init("table-info", buttons, true, data);
        }

        // searchData(); // se activará cuando conectemos la API real
        */
    });
</script>
