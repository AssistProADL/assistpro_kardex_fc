<?php
// public/dispositivos/dispositivos.php
session_start();
require_once __DIR__ . '/../bi/_menu_global.php';

$TITLE = 'Catálogo de Dispositivos Móviles';
?>
<div class="container-fluid mt-2">

    <div class="row">
        <!-- Filtros -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header py-2" style="background:#0F5AAD;color:#fff;">
                    <strong style="font-size:12px;">Filtros</strong>
                </div>
                <div class="card-body py-2" style="font-size:10px;">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Almacén</label>
                            <select id="f_id_almacen" class="form-control form-control-sm">
                                <option value="">[Todos]</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Estatus</label>
                            <select id="f_estatus" class="form-control form-control-sm">
                                <option value="">[Todos]</option>
                                <option value="ACTIVO">Activo</option>
                                <option value="INACTIVO">Inactivo</option>
                                <option value="REPARACION">Reparación</option>
                                <option value="BAJA">Baja</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Tipo</label>
                            <select id="f_tipo" class="form-control form-control-sm">
                                <option value="">[Todos]</option>
                                <option value="HANDHELD">Handheld</option>
                                <option value="TABLET">Tablet</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 align-self-end">
                            <button id="btnBuscar" class="btn btn-primary btn-sm">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="form-group col-md-2 align-self-end">
                            <button id="btnNuevo" class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> Nuevo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grilla -->
        <div class="col-md-12 mt-2">
            <div class="card shadow-sm">
                <div class="card-header py-2" style="background:#0F5AAD;color:#fff;">
                    <strong style="font-size:12px;">Dispositivos</strong>
                </div>
                <div class="card-body p-1">
                    <div class="table-responsive" style="max-height:420px;overflow:auto;">
                        <table id="tblDisp" class="table table-sm table-bordered table-striped" style="font-size:10px;white-space:nowrap;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Id</th>
                                    <th>Almacén</th>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Serie</th>
                                    <th>IMEI</th>
                                    <th>MAC WiFi</th>
                                    <th>MAC BT</th>
                                    <th>IP</th>
                                    <th>Usuario</th>
                                    <th>Estatus</th>
                                    <th>Alta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalDisp" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="frmDisp" class="modal-content">
      <div class="modal-header py-2" style="background:#0F5AAD;color:#fff;">
        <h6 class="modal-title" id="modalTitleDisp">Dispositivo</h6>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" style="font-size:10px;">
        <input type="hidden" name="id" id="id">

        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Almacén</label>
            <select name="id_almacen" id="id_almacen" class="form-control form-control-sm" required>
              <option value="">[Seleccione]</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>Tipo</label>
            <select name="tipo" id="tipo" class="form-control form-control-sm">
              <option value="HANDHELD">Handheld</option>
              <option value="TABLET">Tablet</option>
              <option value="OTRO">Otro</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>Usuario asignado</label>
            <input type="text" name="usuario_asignado" id="usuario_asignado" class="form-control form-control-sm">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Marca</label>
            <input type="text" name="marca" id="marca" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Modelo</label>
            <input type="text" name="modelo" id="modelo" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Serie</label>
            <input type="text" name="serie" id="serie" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>IMEI</label>
            <input type="text" name="imei" id="imei" class="form-control form-control-sm">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label>MAC WiFi</label>
            <input type="text" name="mac_wifi" id="mac_wifi" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>MAC BT</label>
            <input type="text" name="mac_bt" id="mac_bt" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>IP</label>
            <input type="text" name="ip" id="ip" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Estatus</label>
            <select name="estatus" id="estatus" class="form-control form-control-sm">
              <option value="ACTIVO">Activo</option>
              <option value="INACTIVO">Inactivo</option>
              <option value="REPARACION">Reparación</option>
              <option value="BAJA">Baja</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-12">
            <label>Comentarios</label>
            <textarea name="comentarios" id="comentarios" rows="2" class="form-control form-control-sm"></textarea>
          </div>
        </div>

      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
$(function(){
    // Almacenes desde filtros_assistpro.php
    function cargarAlmacenesSelect(selector, incluirTodos){
        $.getJSON('../api/filtros_assistpro.php', {
            action: 'init',
            secciones: 'almacenes'
        }, function(resp){
            if (!resp) {
                alert('Respuesta vacía de filtros_assistpro.php');
                return;
            }
            if (resp.almacenes_error) {
                alert('Error almacenes: ' + resp.almacenes_error);
                return;
            }
            var lista = resp.almacenes || [];
            var sel = $(selector);
            sel.empty();
            if (incluirTodos){
                sel.append('<option value="">[Todos]</option>');
            } else {
                sel.append('<option value="">[Seleccione]</option>');
            }
            lista.forEach(function(a){
                // cve_almac = clave; usamos clave como id_almacen lógico del filtro
                sel.append('<option value="'+a.cve_almac+'">'+a.clave_almacen+' - '+a.des_almac+'</option>');
            });
        });
    }

    var tabla = $('#tblDisp').DataTable({
        paging:true,
        pageLength:25,
        searching:false,
        ordering:true,
        info:true,
        language:{ url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' },
        columns:[
            {data:'id'},
            {data:null, render:function(r){ return r.almacen_clave+' - '+r.almacen_nombre; }},
            {data:'tipo'},
            {data:'marca'},
            {data:'modelo'},
            {data:'serie'},
            {data:'imei'},
            {data:'mac_wifi'},
            {data:'mac_bt'},
            {data:'ip'},
            {data:'usuario_asignado'},
            {data:'estatus'},
            {data:'fecha_alta'},
            {data:null, render:function(r){
                return '<button class="btn btn-xs btn-primary btn-edit" data-id="'+r.id+'"><i class="fa fa-edit"></i></button>';
            }}
        ]
    });

    function cargarDatos(){
        var params = {
            action:'list',
            id_almacen: $('#f_id_almacen').val(),
            estatus: $('#f_estatus').val()
        };
        $.getJSON('../api/dispositivos.php', params, function(resp){
            if (!resp.ok){ alert(resp.error || 'Error'); return; }
            var data = resp.data || [];
            // Si se filtró por tipo en UI, lo filtramos en front (API no tiene filtro "tipo")
            var tipoFiltro = $('#f_tipo').val();
            if (tipoFiltro){
                data = data.filter(function(r){ return r.tipo === tipoFiltro; });
            }
            tabla.clear().rows.add(data).draw();
        });
    }

    $('#btnBuscar').on('click', cargarDatos);

    $('#btnNuevo').on('click', function(){
        $('#frmDisp')[0].reset();
        $('#id').val('');
        $('#modalTitleDisp').text('Nuevo dispositivo');
        $('#modalDisp').modal('show');
    });

    $('#tblDisp').on('click','.btn-edit', function(){
        var id = $(this).data('id');
        $.getJSON('../api/dispositivos.php',{action:'get', id:id}, function(resp){
            if (!resp.ok){ alert(resp.error || 'Error'); return; }
            var d = resp.data;
            $('#id').val(d.id);
            $('#id_almacen').val(d.id_almacen);
            $('#tipo').val(d.tipo);
            $('#usuario_asignado').val(d.usuario_asignado);
            $('#marca').val(d.marca);
            $('#modelo').val(d.modelo);
            $('#serie').val(d.serie);
            $('#imei').val(d.imei);
            $('#mac_wifi').val(d.mac_wifi);
            $('#mac_bt').val(d.mac_bt);
            $('#ip').val(d.ip);
            $('#estatus').val(d.estatus);
            $('#comentarios').val(d.comentarios);
            $('#modalTitleDisp').text('Editar dispositivo');
            $('#modalDisp').modal('show');
        });
    });

    $('#frmDisp').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({name:'action', value:'save'});
        $.post('../api/dispositivos.php', data, function(resp){
            try{ resp = JSON.parse(resp); }catch(e){ alert('Error JSON'); return; }
            if (!resp.ok){ alert(resp.error || 'Error al guardar'); return; }
            $('#modalDisp').modal('hide');
            cargarDatos();
        });
    });

    // combos de almacén desde filtros_assistpro
    cargarAlmacenesSelect('#f_id_almacen', true);
    cargarAlmacenesSelect('#id_almacen', false);

    // carga inicial
    cargarDatos();
});
</script>
