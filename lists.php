<?php
$contenedorAlmacen = new \AlmacenP\AlmacenP();
$almacenes = $contenedorAlmacen->getAll();
?>
<link href="/css/plugins/ladda/ladda-themeless.min.css" rel="stylesheet">
<link href="/css/plugins/chosen/chosen.css" rel="stylesheet">
<link href="/css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
<script src="/js/moment.js"></script>
<script src="/js/moment-with-locales.js"></script>
<style type="text/css">
th[role="columnheader"]{
    text-align: center;
}
</style>

<style type="text/css">
    ul.inline li{
        display: inline;
    }
    .ui-jqgrid, .ui-jqgrid-view, .ui-jqgrid-hdiv, .ui-jqgrid-bdiv, .ui-jqgrid, .ui-jqgrid-htable, #grid-table, #grid-table2, #grid-table3, #grid-table4, #grid-pager, #grid-pager2, #grid-pager3, #grid-pager4{
      max-width: 100%;
    }
</style>

<!-- Mainly scripts -->
<input type="hidden" id="id_user" value="<?php echo $_SESSION['id_user']; ?>">
<div class="wrapper wrapper-content  animated" id="list">
    <h3>Ajuste de Existencia</h3>
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-title">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>Almacén</label>
                                <select class="form-control chosen-select" name="almacen" id="almacen" >
                                    <option value="">Seleccione</option>
                                    <?php if(isset($almacenes) && !empty($almacenes)): ?>
                                        <?php foreach($almacenes as $almacen): ?>
                                            <option value="<?php echo $almacen->clave ?>" data-id="<?php echo $almacen->id ?>"><?php echo "(".$almacen->clave.") - ".$almacen->nombre ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>Zona de Almacenaje</label>
                                <select class="form-control chosen-select" name="almacenp" id="almacenp" disabled>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                      <div class="col-lg-4">
                            <div class="form-group">
                                <label for="email">Tipo de Ubicación</label>
                                <select id="select-tipoU" class="chosen-select form-control">
                                    <option value="">Seleccione el Tipo de Ubicación</option>
                                    <option value="L">Libre</option>
                                    <option value="R">Reservada</option>
                                    <option value="Q">Cuarentena</option>
                                    <option value="Picking">Picking</option>
                                    <option value="PTL">PTL</option>
                                    <option value="Mixto">Acomodo Mixto</option>
                                    <option value="Produccion">Área de Producción</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4" >
                            <div class="input-group">
                                <label>Buscar BL</label>
                                <input type="text" class="form-control input-sm" name="buscar" id="buscar" placeholder="Buscar BL...">
                                <div class="input-group-btn">
                                    <a href="#" onclick="buscar()">
                                        <button type="submit" class="btn btn-sm btn-primary" id="buscarP">
                                            Buscar
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">

                    </div>
                </div>
                <div class="ibox-content">
                  <div class="row">
                      <div class="col-lg-12" style="text-align: right">
                          <ul class="list-unstyled inline">
                            <li><b>| Total ubicaciones:</b> <span id="total_ubicaciones">0</span></li>
                            <li><b>| Porcentaje de ocupación:</b> <span id="porcentaje_ocupadas">0</span></li>
                            <li><b>| Ubicaciones Vacías:</b> <span id="vacias">0</span> <b>|</b></li>
                          </ul>
                      </div>
                  </div>
                    <div class="jqGrid_wrapper">
                        <table id="grid-table"></table>
                        <div id="grid-pager"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleModal" role="dialog">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center modal-lg" style="width: 1200px!important;">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4>Detalle</h4>
                </div>
                <div class="modal-body">
                    <h3 class="modal-title">Productos Ubicados</h3>
                    <h4><label id="detalle_ubicacion" style="text-align: center;"></label></h4>
                    <div class="row">
                      <div class="col-md-12">
                        <div class="col-md-3">
                          <label>Peso Maximo (Kg)</label>
                          <input type="text" class="form-control" id="pes_max" style="text-align:right;" disabled>
                        </div>
                        <div class="col-md-3">
                          <label>Peso Total Ocupado (Kg)</label>
                          <input type="text" class="form-control" id="pes_total" style="text-align:right;" disabled>
                        </div>
                        <div class="col-md-3">
                          <label>Peso Disponible (Kg)</label>
                          <input type="text" class="form-control" id="pes_dispo" style="text-align:right;" disabled>
                        </div>
                        <div class="col-md-3">
                          <label>% Ocupación Peso</label>
                          <input type="text" class="form-control" id="pes_porcentaje" style="text-align:right;" disabled>
                        </div>
                      </div>
                    </div>
                    <br>
                    <div class="row">
                      <div class="col-md-12">
                        <div class="col-md-3">
                          <label>Volumen Maximo (m3)</label>
                          <input type="text" class="form-control" id="vol_max" style="text-align:right;" disabled>
                        </div>
                        <div class="col-md-3">
                          <label>Volumen Total Ocupado (m3)</label>
                          <input type="text" class="form-control" id="vol_total" style="text-align:right;" disabled>
                        </div>
                        <div class="col-md-3">
                          <label>Volumen Disponible (m3)</label>
                          <input type="text" class="form-control" id="vol_dispo" style="text-align:right;" disabled>
                        </div>
                        <div class="col-md-3">
                          <label>% Ocupacion Volumen</label>
                          <input type="text" class="form-control" id="vol_porcentaje" style="text-align:right;" disabled>
                        </div>
                      </div>
                    <br>
					          <div class="ibox-content">
                        <div class="jqGrid_wrapper" id="detalle_wrapper">
                            <table id="grid-table2"></table>
                            <div id="grid-pager2"></div>
                        </div>
                    </div>
				        </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
									  <button id="btn-asignar" onclick="asignar()" type="button" type="button" class="btn btn-m btn-primary permiso_registrar" style="padding-right: 20px;">Guardar</button><br><br><br>
                    <!--button onclick="actualizar_existencias()" type="submit" id="guardar" class="btn btn-sm btn-primary">Guardar</button-->
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<!--Modal Enviar a QA | Cuarentena-->
<div class="modal fade" id="modal-asignar-motivo" role="dialog">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4>Motivos</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="motivo" class="text-center"></label>
                            <select id="motivo_selector" class="form-control">
                                <option value="">Seleccione motivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                    <button onclick="actualizar_existencias()" id="actualizar_existencias" class="btn btn-primary pull-right" type="button">
                        <i class="fa fa-plus"></i>&nbsp;&nbsp;Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mainly scripts -->
<script src="/js/jquery-2.1.1.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

<!-- Peity -->
<script src="/js/plugins/peity/jquery.peity.min.js"></script>

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
<script src="/js/plugins/sweetalert/sweetalert.min.js"></script>
<script src="/js/plugins/chosen/chosen.jquery.js"></script>

<!-- Jquery Validate -->
<script src="/js/plugins/validate/jquery.validate.min.js"></script>


<script type="text/javascript">
  
 // var select_tipoU = document.getElementById('select-tipoU'),
    /**
     * @author Ricardo Delgado.
     * Busca y selecciona el almacen predeterminado para el usuario.
     */ 
    function almacenPrede(){ 
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {
                idUser: '<?php echo $_SESSION["id_user"]?>',
                action: 'search_almacen_pre'
            },
            beforeSend: function(x) { if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
            },
            url: '/api/almacenPredeterminado/index.php',
            success: function(data) {
                if (data.success == true) {
                    document.getElementById('almacen').value = data.codigo.clave;
                    setTimeout(function() {
                        $('#almacen').trigger('change');
                        buscar();
                    }, 1000);
                }
            },
            error: function(res){
                window.console.log(res);
            }
        });
    }
    almacenPrede();
    $(function($){
        var grid_selector = "#grid-table";
        var pager_selector = "#grid-pager";

        //resize to fit page size
        $(window).on('resize.jqGrid', function () {
            $(grid_selector).jqGrid( 'setGridWidth', $(".page-content").width() - 60 );
        })
        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
            if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function() {
                    $(grid_selector).jqGrid( 'setGridWidth', parent_column.width() );
                }, 0);
            }
        })

        $(grid_selector).jqGrid({
            url: '/api/ajustesexistencias/lista/index.php',
            datatype: "local",
            autowidth: true,
            shrinkToFit: false,
            height:'auto',
            mtype: 'POST',
            colNames:["Acción","BL","Zona de Almacenaje", "Pallet|Contenedor", "License Plate (LP)", "Productos Ubicados","Existencia piezas","Peso Máximo","Volumen(m3)","Peso%","Volumen%","Dimensiones (Alt. X Anc. X Lar. )", "Área de Producción", "Ubicación"],
            colModel:[
                {name:'myac',index:'', width: 50,fixed:true, sortable:false, resize:false, formatter:imageFormat},
                {name:'BL', index:'BL', width: 70, editable:false, sortable:false},
                {name:'zona_almacenaje',index:'zona_almacenaje', width: 210, editable:false, sortable:false},
                {name:'contenedor',index:'contenedor', width: 210, editable:false, sortable:false, hidden: false},
                {name:'CveLP',index:'CveLP', width: 210, editable:false, sortable:false, hidden: false},
                {name:'total_ubicados',index:'total_ubicados',align:"right", width: 130, editable:false, sortable:false, hidden: true},
                {name:'existencia_total',index:'existencia_total',align:"right", width: 120, editable:false, sortable:false, hidden: true},
                {name:'PesoMax',index:'PesoMax', width:110, editable:false,align:"right", sortable:false, hidden: true},
                {name:'volumen_m3',index:'volumen_m3', width:110, editable:false,align:"right", sortable:false, hidden: true},
                {name:'peso',index:'peso', width: 100, align:"right", editable:false, sortable:false},
                {name:'volumen',index:'volumen', width: 110, align:"right", editable:false, sortable:false},
                {name:'dim',index:'dim',width:250, editable:false, sortable:false, align:"center"},
                {name:'areaproduccion',index:'areaproduccion',width:250,editable:false, sortable:false,align:"center", hidden: true},
                {name:'cve_ubicacion',index:'cve_ubicacion',width:250, editable:false, sortable:false, align:"center", hidden: true}
            ],
            rowNum:30,
            rowList:[30,40,50],
            pager: pager_selector,
            viewrecords: true,
            loadComplete:function(data){
                console.log("success = ", data);
            }, loadError:function(data){
                console.log("ERROR = ", data);
            }
        });

        // Setup buttons
        $("#grid-table").jqGrid('navGrid', '#grid-pager',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );

        jQuery("#grid-table").jqGrid('setGroupHeaders', {
            useColSpanStyle: true,
            groupHeaders:[
                //{startColumnName: 'peso', numberOfColumns: 2, titleText: 'Porcentaje de Ocupación'},
            ]
        });


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        //////////////////////////////////////////////////////////aqui se construye la funcion de cada item del grid//////////////////////////////////////////////////////////
        function imageFormat( cellvalue, options, rowObject ){
            var ubicacion = rowObject[13];
            var codigo_CSD = rowObject[1];
            var peso_max = rowObject[7];
            var vol_max = rowObject[8];
            var pes_porcentaje = rowObject[9];
            var vol_porcentaje = rowObject[10];
            var area_produccion = rowObject[12];
            var html = '';
            html += '<a href="#" onclick="detalle(\''+ubicacion+'\', \'' + codigo_CSD + '\', \'' + peso_max + '\', \'' + vol_max + '\', \'' + pes_porcentaje + '\', \'' + vol_porcentaje + '\', \'' + area_produccion + '\')"><i class="fa fa-search" alt="Detalle"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;';
            return html;
        }
        $(document).one('ajaxloadstart.page', function(e) {
            $(grid_selector).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
    });
    $(function($){
        var grid_selector = "#grid-table2";
        var pager_selector = "#grid-pager2";

        //resize to fit page size
        $(window).on('resize.jqGrid', function () {
            $(grid_selector).jqGrid( 'setGridWidth', $(".page-content").width() - 60 );
        })
        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
            if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function() {
                    $(grid_selector).jqGrid( 'setGridWidth', parent_column.width() );
                }, 0);
            }
        })

        $(grid_selector).jqGrid({
            url: '/api/ajustesexistencias/lista/index.php',
            datatype: "local",
            autowidth: false,
            shrinkToFit: false,
            height:'auto',
            mtype: 'POST',
            colNames:['id','Clave','Descripción','Contenedor','Lote','Caducidad','Serie','Existencia','Peso U(Kg)','Volumen U(m3)', 'Peso Total(kg)', 'Volumen Total(m3)', 'Proveedor', 'ID_Proveedor', 'Existencia_Inicial'],
            colModel:[
                {name:'id',index:'id',width: 300, editable:false, sortable:false, hidden:true},
                {name:'cve_articulo',index:'cve_articulo',width: 150, editable:false, sortable:false, align:"right"},
                {name:'descripcion',index:'descripcion',width:230, editable:false, sortable:false},
                {name:'contenedor',index:'contenedor',width:100, editable:false, sortable:false,align:'right'},
                {name:'lote',index:'lote',width:150, editable:false, sortable:false,align:'right'},
                {name:'caducidad',index:'caducidad',width:100, editable:false, sortable:false,align:'right'},
                {name:'serie',index:'serie',width:100, editable:false, sortable:false},
                {name:'Existencia_Total',index:'Existencia_Total',width:120, sortable:true, formatter:setInput},
                {name:'peso_unitario',index:'peso_unitario',width:90, editable:false, sortable:false,align:'right'},
                {name:'volumen_unitario',index:'volumen_unitario',width:132, editable:false, sortable:false,align:'right'},
                {name:'peso_total',index:'peso_total',width:100, editable:false, sortable:false,align:'right'},
                {name:'volumen_total',index:'volumen_total',width:132, editable:false, sortable:false,align:'right'},
                {name:'proveedor',index:'proveedor',width:132, editable:false, sortable:false},
                {name:'id_proveedor',index:'id_proveedor',width:132, editable:false, sortable:false, hidden: true},
                {name:'existencia_inicial',index:'existencia_inicial',width:132, editable:false, sortable:false, hidden: true},
            ],
            rowNum:30,
            rowList:[30,40,50, 100, 200],
            pager: pager_selector,
            viewrecords: true,
            loadComplete:function(data){
                console.log(data);
                console.log(data.sql);
              setTimeout(function(){ 
                if(data.total != 0)
                {
                  var peso_total = "0";
                  var pesos = data.rows;
                  window.rows = data.rows;
                  for(var i=0; i<pesos.length; i++)
                  {
                    peso_total = parseFloat(peso_total) + (parseFloat(pesos[i].cell[7])*pesos[i].cell[8]);
                    console.log(peso_total);
                  }
                  
                  $("#pes_total").val(peso_total.toFixed(4));
                  $("#pes_dispo").val(($("#pes_max").val() - peso_total).toFixed(4));
                  var porcentaje_peso = (100*$("#pes_total").val())/$("#pes_max").val();
                  $("#pes_porcentaje").val(porcentaje_peso.toFixed(4));

                  var volumen_total = "0";
                  var pesos = data.rows;
                  for(var i=0; i<pesos.length; i++)
                  {
                    volumen_total = parseFloat(volumen_total) + parseFloat(pesos[i].cell[9]);
                  }
                  $("#vol_total").val(volumen_total.toFixed(4));
                  $("#vol_dispo").val(($("#vol_max").val() - volumen_total).toFixed(4));
                  var porcentaje_vol = (100*$("#vol_total").val())/$("#vol_max").val();
                  $("#vol_porcentaje").val(porcentaje_vol.toFixed(4));

                  var input_peso = document.getElementById('pes_porcentaje');
                  var input_volumen = document.getElementById('vol_porcentaje');

                  if($("#pes_total").val() > $("#pes_max").val())
                  {
                    input_peso.style.borderColor = "red";
                  }
                  else
                  {
                    input_peso.style.borderColor = "green";
                  }

                  if($("#vol_total").val() > $("#vol_max").val())
                  {
                    input_volumen.style.borderColor = "red";
                  }
                  else
                  {
                    input_volumen.style.borderColor = "green";
                  }
                  $("#tabla_vacia").hide();
                }
                else
                {
                  $("#tabla_vacia").show();
                  console.log("deam");
                  $("#pes_total").val("0");
                  $("#pes_dispo").val($("#pes_max").val());
                  $("#pes_porcentaje").val("0");

                  $("#vol_total").val("0");
                  $("#vol_dispo").val($("#vol_max").val());
                  $("#vol_porcentaje").val("0");
                  var input_peso = document.getElementById('pes_porcentaje');
                  var input_volumen = document.getElementById('vol_porcentaje');
                  input_peso.style.borderColor = "green";
                  input_volumen.style.borderColor = "green";
                }
              }, 200);
              $(".cambio").on("change", function(e) {
                  console.log("Cambiando existencia");  
                  change_color_detalle();
              });
            }, loadError: function(data){console.log("ERROR LOAD = ", data);}
        });

        // Setup buttons
        $("#grid-table").jqGrid('navGrid', '#grid-pager',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );

        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function(e) {
            $(grid_selector).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
      
        function setInput(cellvalue, options, rowObject){
            var cve_articulo =   rowObject[1];
            var existencia_total = rowObject[7];
            
            return '<input type="number" class="form-control input-sm cambio" id="existencia_'+cve_articulo+'" value="'+existencia_total+'">';
        }
    });
  
    function change_color_detalle()
    {
        var j=0;
        var teorico = [];
        var real = [];
        var table = document.getElementById("grid-table2");
        for(var i=1; i< table.rows.length; i++)
        {
            var campo_real2 = document.getElementById("grid-table2").rows[i].cells.item(7).firstChild.value;
            var campo_teorico = window.rows[i-1].cell[7];
            teorico.push(campo_teorico); 
            real.push(campo_real2);
            var color = "#FFFFFF";
            if(campo_real2!="")
            {
                color = (teorico[j] > real[j])?"red":(teorico[j] < real[j])?"#1ab394":"#FFFFFF";
            }
            for(var k = 0;k<=11;k++)
            {
                document.getElementById("grid-table2").rows[i].cells.item(k).style.backgroundColor = color;
            }
            j++;
        }
    }
</script>

<script>
    $(document).ready(function(){

        $("#almacenp").on('change', function(e){
            loadDataToGrid(e.target.value);
        });

        $("#almacen").on('change', function(e){console.log("hola");
            $.ajax({
                type: "POST",
                dataType: "json",
                data: {
                    clave : e.target.value,
                    action : "traerZonasDeAlmacenP"
                },
                beforeSend: function(x) { if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
                },
                url: '/api/almacenp/update/index.php',
                success: function(data) {
                    $("#almacenp").removeAttr("disabled");
                    if (data.success == true) {
                        var options = $("#almacenp");
                        options.empty();
                        options.append(new Option("Seleccione", ""));
                        for (var i=0; i<data.zonas.length; i++)
                        {
                            options.append(new Option(data.zonas[i].nombre_zona, data.zonas[i].clave_zona));
                        }
                        $('.chosen-select').trigger("chosen:updated");
                    }
                }
            });
        });

        $(function() {
        $('.chosen-select').chosen();
        $('.chosen-select-deselect').chosen({ allow_single_deselect: true });
      });
    });
</script>

<script>

function buscar() {
    var almacen = $("#almacen").val();
    var id = $("#almacen option[value='"+almacen+"']").attr("data-id");
    var tipoU = $("#select-tipoU").val();
    $('#grid-table').jqGrid('clearGridData')
        .jqGrid('setGridParam', {postData: {
            almacen: id,
            almacenaje: $("#almacenp").val(),
            search: $("#buscar").val(),
            action: 'loadGrid',
            tipo: tipoU,
        }, datatype: 'json'})
        .trigger('reloadGrid',[{current:true}]);
    getStatistics();
}

function loadDataToGrid(almacenaje) {
    $('#grid-table').jqGrid('clearGridData')
        .jqGrid('setGridParam', {postData: {
            almacenaje: almacenaje,
            action: 'loadGrid'
        }, datatype: 'json'})
        .trigger('reloadGrid',[{current:true}]);
    getStatistics();
}

function getStatistics(){
  var almacen = $("#almacen").val();
  var id = $("#almacen option[value='"+almacen+"']").attr("data-id");
  $.ajax({
      url: '/api/ajustesexistencias/lista/index.php',
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'loadStatistics',
        almacen: id,
        almacenaje: $("#almacenp").val()
      }
  })
  .done(function(data){
      $("#total_ubicaciones").html(data.total)
      $("#porcentaje_ocupadas").html(data.porcentajeocupadas + '%')
      $("#vacias").html(data.vacias)
  });
}

function loadDataToGridDetails(ubicacion, almacenaje, area_produccion) {
    
    console.log("ubicacion = ", ubicacion);
    console.log("almacenaje = ", almacenaje);
    console.log("area_produccion = ", area_produccion);
    $('#grid-table2').jqGrid('clearGridData')
        .jqGrid('setGridParam', {postData: {
            ubicacion: ubicacion,
            almacenaje: almacenaje,
            areaProduccion: area_produccion,
            action: 'loadDetails'
        }, datatype: 'json'})
        .trigger('reloadGrid',[{current:true}]);
}

$("#buscar").keyup(function(event){
    if(event.keyCode == 13){
        buscar()
    }
});

function detalle(ubicacion,csd,peso_max,vol_max,pes_porcentaje,vol_porcentaje, area_produccion){
    loadDataToGridDetails(ubicacion, $("#almacen").val(), area_produccion);
    $("#tabla_vacia").hide();
    $("#pes_max").val("");
    $("#pes_total").val("");
    $("#pes_dispo").val("");
    $("#pes_porcentaje").val("");
    $("#vol_max").val("");
    $("#vol_total").val("");
    $("#vol_dispo").val("");
    $("#vol_porcentaje").val("");
    console.log(peso_max+'-'+vol_max+'-'+pes_porcentaje+'-'+vol_porcentaje);

    $("#pes_max").val(peso_max);
    $("#vol_max").val(vol_max);
    $("#pes_porcentaje").val(pes_porcentaje);
    $("#vol_porcentaje").val(vol_porcentaje);
    $("#detalle_ubicacion").html("Ubicacion "+csd);
    //$("#ver_detalles").modal("show");
    $("#detalleModal").modal('show');
}
  
function actualizar_existencias(){
    var existencia =[];
    var j = 0; k = 0;
    var id = '';
    var clave = '';
    var contenedor = '';
    var lote = '';
    var serie = '';
    var val = '';
    var id_proveedor = '';
    var existencia_inicial = '';
    console.log("actualizar_existencias()");
    $("#grid-table2>tbody>tr").each(function(i,e){
			console.log("");
/*
				if(e.cells[4].title != "")
					 {
						 console.log("no vacío");
					  	e.cells[4].title = e.cells[6].title;
					 }
*/			

        if(j>0){

            id = e.cells[0].title; 
          	clave = e.cells[1].title;  
            contenedor = e.cells[3].title;
            lote = e.cells[4].title;
            serie = e.cells[6].title;
            val = e.cells[7].firstChild.value;
            id_proveedor = e.cells[13].title;
            existencia_inicial = e.cells[14].title;

            if(val != existencia_inicial)
            {
                existencia[k] = {};
                existencia[k]["id"] = id;
                existencia[k]["clave"] = clave;
                existencia[k]["contenedor"] = contenedor;
                existencia[k]["existencia"] = val;
                existencia[k]["lote"] = lote;
                existencia[k]["id_proveedor"] = id_proveedor;
                existencia[k]["existencia_inicial"] = existencia_inicial;

    			if(lote != "")
    			{
    				existencia[k]["lote"] = lote;
    			}
    			else
    			{
    				existencia[k]["lote"] = serie;
    			}
                console.log("k = "+k+" i = "+i+" clave = "+clave+" contenedor = "+contenedor+" lote = "+existencia[k]["lote"]+" serie = "+serie+" val = "+val+" id_user = "+$("#id_user").val()+" id_proveedor = "+existencia[k]["id_proveedor"]+" existencia_inicial = "+existencia_inicial);
               k++; 
            }
        }
        //if(val != existencia_inicial && val != '')
         j++;
    });
		if ($("#motivo_selector").val() == "") 
		{
				swal("Error", "Seleccione un motivo", "error");
				return;
		}
		var almacen = $("#almacen").val();
    var ubicacion = $("#detalle_ubicacion").text().split(" ")[1];
    console.log("*********************************************************************");
    console.log("motivos: ", $('#motivo_selector').val());
    console.log("fecha: ", moment().format("MMYYYY"));
    console.log("almacen: ", almacen);
    console.log("ubicacion: ", ubicacion);
    console.log("articulos: ", existencia);
    console.log("*********************************************************************");

    //return;

    $.ajax({
        type:'POST',
        datatype: 'json',
        data:{
              idUser: '<?php echo $_SESSION["id_user"]?>',
              motivos: $('#motivo_selector').val(),
              action:'actualizar_existencias',
              fecha: moment().format("MMYYYY"),
              almacen: almacen,
              ubicacion: ubicacion,
              articulos: existencia
        },
        beforeSend: function(x) { if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
        },
        url:'/api/ajustesexistencias/lista/index.php',
        success: function(data){

            console.log(data);

            if (data["success"] == true){
                $("#detalleModal").modal('hide');
							  $("#modal-asignar-motivo").modal('hide');
                swal("Éxito","Se actualizo la Existencia","success");
                buscar();
            }
        }, error: function(data_error){

            console.log("error", data_error);

        }
    });
}

	function asignar()
	{
		  $('#motivo_selector').empty();
        $modal0 = $("#modal-asignar-motivo");
        $modal0.modal('show');
        $("#motivo_selector").append(new Option("Seleccione Motivo", ""));
        $.ajax({
            url: "/api/reportes/lista/existenciaubica.php",
            type: "POST",
            dataType: "json",
            data: {
                action:"traermotivos",
                status: "A"
            },
            success: function(data) 
            {
                console.log("DA", data);
                var j = 0;
                $.each(data.sql, function(key, value) {
                    $("#motivo_selector").append(new Option(""+value.descri+"",value.id));
                    j++;
                });
            }, error: function(data) 
            {
                console.log("ERROR", data);
            }
        });
	}
</script>
