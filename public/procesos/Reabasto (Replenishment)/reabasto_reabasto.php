<?php
// public/procesos/Reabasto (Replenishment)/reabasto.php

// Frame corporativo + conexión BD
require_once __DIR__ . '/../../bi/_menu_global.php';
require_once __DIR__ . '/../../../app/db.php';

// ===================== Catálogo de almacenes =====================
try {
    // Ajusta este SELECT si los nombres de columnas son distintos en c_almacenp
    $almacenes = db_all("
        SELECT 
            id       AS id,
            clave    AS clave,
            nombre   AS nombre
        FROM c_almacenp
        ORDER BY clave
    ");
} catch (Throwable $e) {
    $almacenes = [];
}
?>
<link href="/css/plugins/datapicker/datepicker3.css" rel="stylesheet">
<link href="/css/plugins/ladda/ladda-themeless.min.css" rel="stylesheet">
<link href="/css/plugins/chosen/chosen.css" rel="stylesheet">
<link href="/css/plugins/iCheck/custom.css" rel="stylesheet">
<link href="/css/plugins/awesome-bootstrap-checkbox/awesome-bootstrap-checkbox.css" rel="stylesheet">
<!-- Sweet Alert -->
<link href="/css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

<style>
    /* Contenedor principal dentro del content-wrapper del frame */
    #list {
        width: 100%;
        position: relative;
        z-index: 1;
    }

    #FORM {
        width: 100%;
        position: relative;
        z-index: 1;
    }

    /* Estilo corporativo AssistPro para esta vista */
    #list h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 4px 0 14px 0;
        color: #1b2a4a;
    }

    .ibox {
        border-radius: 6px;
        border: 1px solid #dde3f0;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 90, 173, 0.08);
    }

    .ibox-content {
        padding: 12px 14px;
    }

    .form-group label {
        font-size: 11px;
        font-weight: 600;
        color: #344767;
    }

    .form-control,
    .chosen-container-single .chosen-single {
        font-size: 11px;
        height: 28px;
        padding: 3px 8px;
    }

    .btn {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 4px;
    }

    .btn-primary {
        background-color: #0F5AAD;
        border-color: #0F5AAD;
    }

    .btn-primary:hover {
        background-color: #0c447f;
        border-color: #0c447f;
    }

    .jqGrid_wrapper .ui-jqgrid,
    .jqGrid_wrapper .ui-jqgrid * {
        font-size: 10px !important;
    }

    .jqGrid_wrapper .ui-jqgrid .ui-jqgrid-hdiv {
        background-color: #f1f4fb;
    }
</style>

<input type="hidden" id="toogle_reabasto" value="0">

<div id="list" class="animated">
    <h3>Reabasto Max | Min</h3>

    <div class="row">
        <div class="col-lg-12">
            <div class="ibox">
                <div class="ibox-content">

                    <!-- ===================== Filtros ===================== -->
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label>Almacén</label>
                                <select name="almacen" id="almacen" class="chosen-select form-control">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($almacenes as $a): ?>
                                        <option value="<?= htmlspecialchars($a['id']) ?>">
                                            <?= '(' . htmlspecialchars($a['clave']) . ') - ' . htmlspecialchars($a['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Tipo de ubicación</label>
                                <select name="tipoUbica" id="tipoUbica" class="chosen-select form-control">
                                    <option value="">Seleccione</option>
                                    <option value="Picking" selected>Picking</option>
                                    <option value="PTL">PTL</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Tipo de Reabasto</label>
                                <select name="tipoReab" id="tipoReab" class="chosen-select form-control">
                                    <option value="">Seleccione</option>
                                    <option value="P">Piezas</option>
                                    <option value="C">Cajas</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-1">
                            <br>
                            <button type="button" id="btn-buscar" class="btn btn-primary btn-sm" onclick="buscar()">
                                Buscar
                            </button>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="input-group">
                                    <input type="text" name="buscar" id="buscar" class="form-control input-sm"
                                           placeholder="Buscar por clave / descripción">
                                    <div class="input-group-btn">
                                        <button class="btn btn-sm btn-primary" onclick="buscar()">Buscar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================== Grilla ===================== -->
                    <div class="row">
                        <div class="col-lg-12">
                            <br>
                            <div class="ibox-content">
                                <label for="btn-asignarTodo">
                                    <input type="checkbox" name="asignarTodo" id="btn-asignarTodo"
                                           style="margin-right: 6px;margin-bottom: 10px;">
                                    Seleccionar Todo
                                </label>

                                <div class="jqGrid_wrapper">
                                    <table id="grid-tabla"></table>
                                    <div id="grid-page"></div>
                                </div>

                                <br>
                                <div class="form-group">
                                    <div class="input-group-btn">
                                        <button id="btn-asignar" type="button"
                                                class="btn btn-m btn-primary permiso_registrar disabled">
                                            Reabastecer
                                        </button>
                                    </div>
                                </div>

                            </div><!-- /.ibox-content (grilla) -->
                        </div>
                    </div>

                </div><!-- /.ibox-content filtros+grilla -->
            </div><!-- /.ibox -->
        </div>
    </div>
</div>

<!-- ===================== Modal Editar Máx/Mín ===================== -->
<div class="modal inmodal fade" id="editar" tabindex="-1" role="dialog" aria-hidden="false">
    <div class="modal-dialog">
        <div class="modal-content animated bounceInRight">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span><span class="sr-only">Cerrar</span>
                </button>
                <h4 class="modal-title">Editar Max | Min</h4>
            </div>

            <div class="modal-body">
                <div id="FORM">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>Artículo</label>
                                <input type="text" name="articulo" id="articulo" class="form-control" readonly>
                                <input type="hidden" name="cve_articulo" id="cve_articulo" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>BL</label>
                                <input type="text" name="BL" id="BL" class="form-control" readonly>
                                <input type="hidden" name="idy_ubica" id="idy_ubica" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label>Mínimo</label>
                                <input type="text" name="minimo" id="minimo" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label>Máximo</label>
                                <input type="text" name="maximo" id="maximo" class="form-control">
                            </div>
                        </div>
                    </div>

                </div><!-- /#FORM -->
            </div><!-- /.modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-dismiss="modal">Cerrar</button>
                <button type="button" id="btn-save" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== Scripts ===================== -->
<script src="/js/jquery-2.1.1.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

<!-- jqGrid -->
<script src="/js/plugins/jqGrid/i18n/grid.locale-es.js"></script>
<script src="/js/plugins/jqGrid/jquery.jqGrid.min.js"></script>

<!-- Custom and plugin javascript -->
<script src="/js/inspinia.js"></script>
<script src="/js/plugins/pace/pace.min.js"></script>

<script src="/js/plugins/jquery-ui/jquery-ui.min.js"></script>

<script src="/js/plugins/ladda/spin.min.js"></script>
<script src="/js/plugins/ladda/ladda.min.js"></script>
<script src="/js/plugins/ladda/ladda.jquery.min.js"></script>

<!-- Select -->
<script src="/js/plugins/chosen/chosen.jquery.js"></script>
<!-- iCheck -->
<script src="/js/plugins/iCheck/icheck.min.js"></script>
<!-- Sweet alert -->
<script src="/js/plugins/sweetalert/sweetalert.min.js"></script>

<script type="text/javascript">
    var grid_selector = "#grid-tabla";
    var page_selector = "#grid-page";

    function ReloadGrid() {
        $(grid_selector).jqGrid('setGridParam', {datatype: 'json', page: 1}).trigger("reloadGrid");
    }

    function imageFormat(cellvalue, options, rowObject) {
        var html = '';
        html += '<div class="btn-group">';
        html += '<button type="button" class="btn btn-xs btn-primary" title="Editar" ' +
            'onclick="editar(\'' + rowObject.clave + '\',\'' + rowObject.BL + '\',\'' +
            rowObject.idy_ubica + '\',\'' + rowObject.minimo + '\',\'' + rowObject.maximo + '\')">' +
            '<i class="fa fa-pencil"></i></button>';
        html += '</div>';
        return html;
    }

    function imageFormat2(cellvalue, options, rowObject) {
        return '<input type="checkbox" name="asignar' + rowObject.idy_ubica + '" />';
    }

    function buscar() {
        $(grid_selector).jqGrid('clearGridData');
        $(grid_selector).setGridParam({
            datatype: 'json',
            page: 1,
            postData: {
                almacen: $("#almacen").val(),
                tipoUbica: $("#tipoUbica").val(),
                tipoReab: $("#tipoReab").val(),
                buscar: $("#buscar").val()
            }
        }).trigger('reloadGrid');
    }

    function editar(clave, BL, idy_ubica, minimo, maximo) {
        $("#articulo").val(clave);
        $("#BL").val(BL);
        $("#cve_articulo").val(clave);
        $("#idy_ubica").val(idy_ubica);
        $("#minimo").val(minimo);
        $("#maximo").val(maximo);
        $("#editar").modal('show');
    }

    function guardar() {
        $.ajax({
            url: '/api/reabasto/update/index.php',
            type: 'POST',
            data: {
                cve_articulo: $("#cve_articulo").val(),
                idy_ubica: $("#idy_ubica").val(),
                minimo: $("#minimo").val(),
                maximo: $("#maximo").val(),
                action: 'guardar'
            }
        }).always(function () {
            ReloadGrid();
            $("#editar").modal('hide');
        });
    }

    $(document).ready(function () {

        $('.chosen-select').chosen({width: "100%"});

        // iCheck (si se usa en otros elementos)
        $('.i-checks').iCheck({
            checkboxClass: 'icheckbox_square-green',
            radioClass: 'iradio_square-green'
        });

        Ladda.bind('#btn-buscar');
        $("#btn-asignar").addClass("disabled");

        // ===================== jqGrid =====================
        $(grid_selector).jqGrid({
            url: '/api/reabasto/lista/index.php',
            mtype: "POST",
            shrinkToFit: false,
            cache: false,
            height: 'auto',
            datatype: 'local',
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            colNames: [
                'Acción',
                'Reabastecer',
                'Clave',
                'Descripción',
                'BL',
                'Tipo de Ubicación',
                'Tipo Reabasto',
                'Maximo',
                'Minimo',
                'Existencia',
                'Reabasto (Piezas)',
                'Reabasto (Cajas)',
                'IDY',
                'Almacén',
                'Zona Almacenaje'
            ],
            colModel: [
                {name: 'myac', index: '', width: 60, sortable: false, resize: false, formatter: imageFormat, hidden: true},
                {name: 'asignar', index: 'asignar', width: 80, sortable: false, resize: false, align: 'center', formatter: imageFormat2},
                {name: 'clave', index: 'clave', width: 90, editable: false, sortable: false},
                {name: 'descripcion', index: 'descripcion', width: 280, editable: false, sortable: false},
                {name: 'BL', index: 'BL', width: 80, editable: false, sortable: false},
                {name: 'tipo', index: 'tipo', width: 110, editable: false, sortable: false},
                {name: 'tipo_reab', index: 'tipo_reab', width: 110, editable: false, sortable: false},
                {name: 'maximo', index: 'maximo', width: 80, editable: false, sortable: false, align: 'right'},
                {name: 'minimo', index: 'minimo', width: 80, editable: false, sortable: false, align: 'right'},
                {name: 'existencia', index: 'existencia', width: 90, editable: false, sortable: false, align: 'right'},
                {name: 'reabastop', index: 'reabastop', width: 110, editable: false, sortable: false, align: 'right'},
                {name: 'reabastoc', index: 'reabastoc', width: 110, editable: false, sortable: false, align: 'right'},
                {name: 'idy_ubica', index: 'idy_ubica', width: 0, editable: false, sortable: false, hidden: true},
                {name: 'almacen', index: 'almacen', width: 180, editable: false, sortable: false},
                {name: 'zona', index: 'zona', width: 180, editable: false, sortable: false}
            ],
            viewrecords: true,
            rowNum: 25,
            rowList: [25, 50, 100],
            pager: page_selector,
            altRows: true,
            multiselect: false,
            loadonce: true,
            sortname: 'clave',
            sortorder: "ASC",
            caption: "",
            width: 1240,
            loadComplete: function () {
                $("#btn-asignar").removeClass("disabled");
            }
        });

        $(grid_selector).jqGrid('navGrid', page_selector, {edit: false, add: false, del: false});
        $(grid_selector).setGridParam({datatype: 'json'});

        $("#buscar").keyup(function (e) {
            if (e.keyCode === 13) {
                buscar();
            }
        });

        $("#btn-asignarTodo").click(function () {
            var rs = $(grid_selector).jqGrid('getRowData');
            var marcar = $("#btn-asignarTodo").is(':checked');
            $.each(rs, function (i, item) {
                var checkbox = "input[name=asignar" + item.idy_ubica + "]";
                $(checkbox).prop("checked", marcar);
            });
        });

        $("#btn-asignar").click(function () {
            var rs = $(grid_selector).jqGrid('getRowData');
            var listado = "";
            $.each(rs, function (i, item) {
                var checkbox = "input[name=asignar" + item.idy_ubica + "]";
                if ($(checkbox).is(':checked')) {
                    listado += item.idy_ubica + ",";
                }
            });

            if (listado !== "") {
                swal({
                    title: "¿Desea reabastecer los artículos seleccionados?",
                    text: "",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#1ab394",
                    confirmButtonText: "Sí",
                    cancelButtonText: "No",
                    closeOnConfirm: false
                }, function () {
                    $.ajax({
                        url: '/api/reabasto/item/index.php',
                        type: 'POST',
                        data: {
                            listado: listado,
                            tipoReab: $("#tipoReab").val(),
                            tipoUbica: $("#tipoUbica").val(),
                            action: 'guardar'
                        }
                    }).always(function () {
                        swal("Reabasto", "Se reabastecieron los artículos seleccionados.", "success");
                        $("#btn-asignarTodo").prop("checked", false);
                        $("#btn-asignar").addClass("disabled");
                        ReloadGrid();
                    });
                });
            }
        });

        $("#btn-save").click(function () {
            guardar();
        });
    });
</script>
