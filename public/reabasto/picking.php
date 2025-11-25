<?php
// public/procesos/Reabasto (Replenishment)/picking.php

// Sidebar / layout corporativo
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

    /* Ajuste corporativo ligero */
    #list h3{
        font-size:16px;
        font-weight:600;
        color:#1b2a4a;
        margin:4px 0 14px 0;
    }

    #table-info{
        font-size:10px;
    }
</style>

<div class="wrapper wrapper-content animated" id="list">
    <h3>Reabastecer Picking</h3>
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-content">
                    <div class="table-responsive">
                        <table id="table-info"  class="table table-hover table-striped no-margin display nowrap" style="width:100%">
                            <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Clave de Producto</th>
                                <th>Descripción</th>
                                <th>Lote|Serie</th>
                                <th>Caducidad</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                            </tr>
                            </thead>
                            <tbody id="tbody">
                            <!-- Sin registros por ahora; DataTables mostrará "No Existen Registros" -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mainly scripts -->
<script src="/js/jquery-2.1.4.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="/js/moment.js"></script>

<!-- Custom and plugin javascript -->
<script src="/js/inspinia.js"></script>
<script src="/js/plugins/pace/pace.min.js"></script>
<script src="/js/plugins/ladda/spin.min.js"></script>
<script src="/js/plugins/ladda/ladda.min.js"></script>
<script src="/js/plugins/ladda/ladda.jquery.min.js"></script>
<script src="/js/plugins/sweetalert/sweetalert.min.js"></script>

<!-- Select -->
<script src="/js/select2.js"></script>
<!-- Data picker -->
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

        // SOLO VISUAL: inicializamos DataTables sin consumir APIs
        $('#table-info').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'Reabastecer Picking',
                    text: 'Excel',
                    className: 'btn btn-blue bt',
                    customize: function () {
                        swal("Descargando Excel", "Su descarga empezará en breve", "success");
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: 'Reabastecer Picking',
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

        // --- LÓGICA ORIGINAL (API, TableDataRest, etc.) COMENTADA POR AHORA ---
        /*
        var tableDataInfo = new TableDataRest(),
            TABLE = null,
            buttons = [{
                        extend: 'excelHtml5',
                        title: 'Reabastecer Picking',
                        customize: function() { swal("Descargando Excel", "Su descarga empezara en breve", "success");}
                        },
                        {
                        extend: 'pdfHtml5',
                        title: 'Reabastecer Picking',
                        orientation: 'portrait',
                        pageSize: 'A4',
                        download: 'open',
                        customize: function() { swal("Descargando PDF", "Su descarga empezara en breve", "success");},
                        }
                    ];

        searchData();

        function searchData(){
            $.ajax({
                url: '/api/reabasto/picking/index.php',
                type: 'GET',
                dataType: 'json',
                success: function(res){
                    if(res.status === "ok"){
                        fillTableInfo(res.data);
                    }
                },
                error: function(res){
                    console.log(res);
                }
            });
        }

        function fillTableInfo(node){
            var data = [];
            DATA = node;
            for(var i = 0; i < node.length ; i++){
                data.push([
                    node[i].fecha,
                    node[i].cve_articulo,
                    node[i].des_articulo,
                    node[i].cve_lote,
                    node[i].Caducidad,
                    node[i].origen,
                    node[i].destino,
                    node[i].cantidad,
                    node[i].cve_usuario
                ]);
            }
            tableDataInfo.destroy();
            tableDataInfo.init("table-info", buttons, true, data);
        }
        */
    });
</script>
