<?php
// public/dispositivos/impresoras.php
session_start();
require_once __DIR__ . '/../bi/_menu_global.php';

$TITLE = 'Catálogo de Impresoras';
?>
<div class="container-fluid mt-2">

    <div class="row">
        <!-- Card de filtros -->
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
                            <label>Activas</label>
                            <select id="f_activo" class="form-control form-control-sm">
                                <option value="">[Todas]</option>
                                <option value="1">Activas</option>
                                <option value="0">Inactivas</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 align-self-end">
                            <button id="btnBuscar" class="btn btn-primary btn-sm">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="form-group col-md-2 align-self-end">
                            <button id="btnNueva" class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> Nueva
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de grilla -->
        <div class="col-md-12 mt-2">
            <div class="card shadow-sm">
                <div class="card-header py-2" style="background:#0F5AAD;color:#fff;">
                    <strong style="font-size:12px;">Impresoras registradas</strong>
                </div>
                <div class="card-body p-1">
                    <div class="table-responsive" style="max-height:420px;overflow:auto;">
                        <table id="tblImpresoras" class="table table-sm table-bordered table-striped" style="font-size:10px;white-space:nowrap;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Id</th>
                                    <th>Almacén</th>
                                    <th>IP</th>
                                    <th>Tipo</th>
                                    <th>Nombre</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>DPI</th>
                                    <th>Conexión</th>
                                    <th>Puerto</th>
                                    <th>Tiempo</th>
                                    <th>Activo</th>
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

<!-- Modal edición -->
<div class="modal fade" id="modalImpresora" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="frmImpresora" class="modal-content">
      <div class="modal-header py-2" style="background:#0F5AAD;color:#fff;">
        <h6 class="modal-title" id="modalTitle">Impresora</h6>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
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
            <label>IP</label>
            <input type="text" name="IP" id="IP" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-4">
            <label>Tipo impresora</label>
            <select name="TIPO_IMPRESORA" id="TIPO_IMPRESORA" class="form-control form-control-sm">
              <option value="ZPL">ZPL</option>
              <option value="CPCL">CPCL</option>
              <option value="ESCPOS">ESCPOS</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Nombre</label>
            <input type="text" name="NOMBRE" id="NOMBRE" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Marca</label>
            <input type="text" name="Marca" id="Marca" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Modelo</label>
            <input type="text" name="Modelo" id="Modelo" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>DPI</label>
            <input type="number" name="Densidad_Imp" id="Densidad_Imp" class="form-control form-control-sm" value="203">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Tipo conexión</label>
            <select name="TIPO_CONEXION" id="TIPO_CONEXION" class="form-control form-control-sm">
              <option value="USB">USB</option>
              <option value="BT">Bluetooth</option>
              <option value="WIFI">WiFi</option>
              <option value="TC">TCP/IP</option>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label>Puerto</label>
            <input type="number" name="PUERTO" id="PUERTO" class="form-control form-control-sm" placeholder="9100">
          </div>
          <div class="form-group col-md-3">
            <label>Tiempo espera (ms)</label>
            <input type="number" name="TiempoEspera" id="TiempoEspera" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Activo</label>
            <select name="Activo" id="Activo" class="form-control form-control-sm">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
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
$(function() {
    function cargarAlmacenesSelect(selector, incluirTodos) {
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
            if (incluirTodos) {
                sel.append('<option value="">[Todos]</option>');
            } else {
                sel.append('<option value="">[Seleccione]</option>');
            }
            lista.forEach(function(a){
                sel.append('<option value="'+a.cve_almac+'">'+a.clave_almacen+' - '+a.des_almac+'</option>');
            });
        });
    }

    var tabla = $('#tblImpresoras').DataTable({
        paging: true,
        pageLength: 25,
        searching: false,
        ordering: true,
        info: true,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' },
        columns: [
            { data: 'id' },
            { data: null, render: function(row){ return row.almacen_clave + ' - ' + row.almacen_nombre; } },
            { data: 'IP' },
            { data: 'TIPO_IMPRESORA' },
            { data: 'NOMBRE' },
            { data: 'Marca' },
            { data: 'Modelo' },
            { data: 'Densidad_Imp' },
            { data: 'TIPO_CONEXION' },
            { data: 'PUERTO' },
            { data: 'TiempoEspera' },
            { data: 'Activo', render: function(v){ return v == 1 ? 'Sí' : 'No'; } },
            { data: null, render: function(row){
                return '' +
                    '<button class="btn btn-xs btn-primary btn-edit mr-1" data-id="'+row.id+'" title="Editar"><i class="fa fa-edit"></i></button>' +
                    '<button class="btn btn-xs btn-info btn-test" data-id="'+row.id+'" title="Probar impresión"><i class="fa fa-print"></i></button>';
            }}
        ]
    });

    function cargarDatos() {
        var params = {
            action: 'list',
            id_almacen: $('#f_id_almacen').val(),
            activo: $('#f_activo').val()
        };
        $.getJSON('../api/impresoras.php', params, function(resp){
            if (!resp.ok) {
                alert(resp.error || 'Error al cargar');
                return;
            }
            tabla.clear().rows.add(resp.data || []).draw();
        });
    }

    $('#btnBuscar').on('click', function(){
        cargarDatos();
    });

    $('#btnNueva').on('click', function(){
        $('#frmImpresora')[0].reset();
        $('#id').val('');
        $('#modalTitle').text('Nueva impresora');
        $('#modalImpresora').modal('show');
    });

    $('#tblImpresoras').on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        $.getJSON('../api/impresoras.php', {action:'get', id:id}, function(resp){
            if (!resp.ok) { alert(resp.error || 'Error'); return; }
            var d = resp.data;
            $('#id').val(d.id);
            $('#id_almacen').val(d.id_almacen);
            $('#IP').val(d.IP);
            $('#TIPO_IMPRESORA').val(d.TIPO_IMPRESORA);
            $('#NOMBRE').val(d.NOMBRE);
            $('#Marca').val(d.Marca);
            $('#Modelo').val(d.Modelo);
            $('#Densidad_Imp').val(d.Densidad_Imp);
            $('#TIPO_CONEXION').val(d.TIPO_CONEXION);
            $('#PUERTO').val(d.PUERTO);
            $('#TiempoEspera').val(d.TiempoEspera);
            $('#Activo').val(d.Activo);
            $('#modalTitle').text('Editar impresora');
            $('#modalImpresora').modal('show');
        });
    });

    // Probar impresión (usa action=test del API)
    $('#tblImpresoras').on('click', '.btn-test', function(){
        var id = $(this).data('id');
        if (!confirm('¿Enviar etiqueta de prueba a esta impresora?')) return;
        $.post('../api/impresoras.php', {action:'test', id:id}, function(resp){
            try { resp = JSON.parse(resp); } catch(e){ alert('Error al interpretar respuesta'); return; }
            if (!resp.ok) {
                alert(resp.error || 'Error al probar impresión');
                return;
            }
            alert(resp.mensaje || 'Etiqueta de prueba enviada');
        });
    });

    $('#frmImpresora').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({name:'action', value:'save'});
        $.post('../api/impresoras.php', data, function(resp){
            try { resp = JSON.parse(resp); } catch(e){ alert('Error JSON'); return; }
            if (!resp.ok) { alert(resp.error || 'Error al guardar'); return; }
            $('#modalImpresora').modal('hide');
            cargarDatos();
        });
    });

    // combos desde filtros_assistpro
    cargarAlmacenesSelect('#f_id_almacen', true);
    cargarAlmacenesSelect('#id_almacen', false);

    // carga inicial
    cargarDatos();
});
</script>
