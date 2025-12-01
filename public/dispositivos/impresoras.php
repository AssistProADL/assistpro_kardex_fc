<?php
// public/dispositivos/impresoras.php
session_start();
require_once __DIR__ . '/../bi/_menu_global.php';

// Título que usa _menu_global para <title> y encabezados
$TITLE = 'Catálogo de Impresoras y Etiquetadoras';
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
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3 mb-1">
                            <label>Almacén</label>
                            <select id="f_id_almacen" class="form-control form-control-sm">
                                <option value="">[Todos]</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 mb-1">
                            <label>Activas</label>
                            <select id="f_activo" class="form-control form-control-sm">
                                <option value="">[Todas]</option>
                                <option value="1">Activas</option>
                                <option value="0">Inactivas</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 mb-1">
                            <button id="btnBuscar" class="btn btn-primary btn-sm btn-block">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="form-group col-md-2 mb-1">
                            <button id="btnNueva" class="btn btn-success btn-sm btn-block">
                                <i class="fa fa-plus"></i> Nueva
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
                    <strong style="font-size:12px;">Impresoras registradas</strong>
                </div>
                <div class="card-body p-1">
                    <div class="table-responsive">
                        <table id="tblImpresoras" class="table table-sm table-bordered table-striped"
                               style="font-size:10px;white-space:nowrap;width:100%;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:110px;">Acciones</th>
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
        <button type="button" id="btnCerrarModal" class="close text-white" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="font-size:10px;">
        <input type="hidden" name="id" id="id">
        <input type="hidden" name="IP" id="IP"><!-- se llena desde los 4 octetos -->

        <!-- Sección 1 compacta -->
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Almacén</label>
            <select name="id_almacen" id="id_almacen" class="form-control form-control-sm" required>
              <option value="">[Seleccione]</option>
            </select>
          </div>

          <div class="form-group col-md-4">
            <label>IP</label>
            <div class="d-flex">
                <input type="number" id="ip1" class="form-control form-control-sm text-center mr-1"
                       min="0" max="255" style="width:55px;">
                <span class="align-self-center">.</span>
                <input type="number" id="ip2" class="form-control form-control-sm text-center mx-1"
                       min="0" max="255" style="width:55px;">
                <span class="align-self-center">.</span>
                <input type="number" id="ip3" class="form-control form-control-sm text-center mx-1"
                       min="0" max="255" style="width:55px;">
                <span class="align-self-center">.</span>
                <input type="number" id="ip4" class="form-control form-control-sm text-center ml-1"
                       min="0" max="255" style="width:55px;">
            </div>
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

        <!-- Sección 2 -->
        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Nombre lógico</label>
            <input type="text" name="NOMBRE" id="NOMBRE" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Marca</label>
            <!-- después se liga a catálogo real -->
            <input type="text" name="Marca" id="Marca" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>Modelo</label>
            <input type="text" name="Modelo" id="Modelo" class="form-control form-control-sm">
          </div>
          <div class="form-group col-md-3">
            <label>DPI</label>
            <input type="number" name="Densidad_Imp" id="Densidad_Imp"
                   class="form-control form-control-sm" value="203">
          </div>
        </div>

        <!-- Sección 3 -->
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
            <input type="number" name="TiempoEspera" id="TiempoEspera"
                   class="form-control form-control-sm" value="100">
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
        <button type="button" class="btn btn-secondary btn-sm" id="btnCerrarModalPie">Cerrar</button>
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<!-- jQuery + DataTables CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<style>
    /* Nombre sin recorte, aunque la tabla tenga nowrap */
    .ap-col-nombre {
        white-space: normal !important;
    }
</style>

<script>
$(function() {

    // ===== Helpers IP =====
    function setIPInputs(ip) {
        var parts = (ip || '').split('.');
        $('#ip1').val(parts[0] || '');
        $('#ip2').val(parts[1] || '');
        $('#ip3').val(parts[2] || '');
        $('#ip4').val(parts[3] || '');
    }

    function getIPFromInputs() {
        var p1 = $('#ip1').val(), p2 = $('#ip2').val(),
            p3 = $('#ip3').val(), p4 = $('#ip4').val();
        if (!p1 || !p2 || !p3 || !p4) return '';
        return [p1,p2,p3,p4].join('.');
    }

    // ===== Cerrar modal (header y footer) =====
    $('#btnCerrarModal, #btnCerrarModalPie').on('click', function(){
        $('#modalImpresora').modal('hide');
    });

    // ===== Almacenes desde filtros_assistpro (value = cve_almac) =====
    function cargarAlmacenesSelect(selector, incluirTodos) {
        $.getJSON('../api/filtros_assistpro.php', { action: 'init' }, function(resp){
            if (!resp || resp.ok === false) {
                alert((resp && resp.error) ? resp.error : 'Error al cargar almacenes');
                return;
            }

            var lista = resp.almacenes || [];
            var $sel  = $(selector);
            $sel.empty();

            if (incluirTodos) {
                $sel.append('<option value="">[Todos]</option>');
            } else {
                $sel.append('<option value="">[Seleccione]</option>');
            }

            lista.forEach(function(a){
                var value = a.cve_almac; // WH1, WH8, ...
                var text  = a.clave_almacen + ' - ' + a.des_almac;
                $sel.append('<option value="'+ value +'">'+ text +'</option>');
            });
        });
    }

    // ===== DataTable =====
    var tabla = $('#tblImpresoras').DataTable({
        paging: true,
        pageLength: 25,
        lengthChange: false,
        searching: false,
        ordering: true,
        info: true,
        scrollY: '360px',
        scrollX: true,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' },
        columns: [
            {   // Acciones (izquierda)
                data: null,
                orderable: false,
                render: function(row){
                    var btnEdit = '<button class="btn btn-xs btn-outline-primary mr-1 btn-edit" data-id="'+row.id+'" title="Editar"><i class="fa fa-pen"></i></button>';
                    var btnToggle;
                    if (row.Activo == 1) {
                        btnToggle = '<button class="btn btn-xs btn-outline-danger mr-1 btn-toggle" data-id="'+row.id+'" data-activo="0" title="Desactivar"><i class="fa fa-trash-alt"></i></button>';
                    } else {
                        btnToggle = '<button class="btn btn-xs btn-outline-success mr-1 btn-toggle" data-id="'+row.id+'" data-activo="1" title="Recuperar"><i class="fa fa-undo-alt"></i></button>';
                    }
                    var btnTest = '<button class="btn btn-xs btn-outline-secondary btn-test" data-id="'+row.id+'" title="Probar impresión"><i class="fa fa-print"></i></button>';
                    return btnEdit + btnToggle + btnTest;
                }
            },
            { data: 'id' },
            { data: null, render: function(row){ return row.almacen_clave + ' - ' + row.almacen_nombre; } },
            { data: 'IP' },
            { data: 'TIPO_IMPRESORA' },
            { data: 'NOMBRE', render: function(v){ return '<span class="ap-col-nombre">'+(v || '')+'</span>'; } },
            { data: 'Marca' },
            { data: 'Modelo' },
            { data: 'Densidad_Imp' },
            { data: 'TIPO_CONEXION' },
            { data: 'PUERTO' },
            { data: 'TiempoEspera' },
            { data: 'Activo', render: function(v){ return v == 1 ? 'Sí' : 'No'; } }
        ]
    });

    function cargarDatos() {
        var params = {
            action: 'list',
            activo: $('#f_activo').val(),
            id_almacen: $('#f_id_almacen').val() || ''
        };
        $.getJSON('../api/impresoras.php', params, function(resp){
            if (!resp.ok) {
                alert(resp.error || 'Error al cargar impresoras');
                return;
            }
            tabla.clear().rows.add(resp.data || []).draw();
        });
    }

    $('#btnBuscar').on('click', cargarDatos);

    $('#btnNueva').on('click', function(){
        $('#frmImpresora')[0].reset();
        $('#id').val('');
        $('#Activo').val('1');
        $('#IP').val('');
        setIPInputs('');
        $('#modalTitle').text('Nueva impresora');
        $('#modalImpresora').modal('show');
    });

    // Editar
    $('#tblImpresoras').on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        $.getJSON('../api/impresoras.php', {action:'get', id:id}, function(resp){
            if (!resp.ok) { alert(resp.error || 'Error'); return; }
            var d = resp.data;
            $('#id').val(d.id);
            $('#id_almacen').val(d.almacen_clave); // clave WHx
            $('#IP').val(d.IP);
            setIPInputs(d.IP);
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

    // Eliminar / Recuperar
    $('#tblImpresoras').on('click', '.btn-toggle', function(){
        var id     = $(this).data('id');
        var activo = $(this).data('activo');
        var msg = (activo == 0) ? '¿Desactivar impresora?' : '¿Recuperar impresora?';
        if (!confirm(msg)) return;

        $.ajax({
            url: '../api/impresoras.php',
            method: 'POST',
            dataType: 'json',
            data: {action:'toggle_active', id:id, activo:activo},
            success: function(resp){
                if (!resp.ok){
                    alert(resp.error || 'Error al actualizar');
                    return;
                }
                cargarDatos();
            },
            error: function(xhr){
                console.error('Respuesta cruda API toggle:', xhr.responseText);
                alert('Error API (debug): ' + xhr.responseText);
            }
        });
    });

    // Guardar
    $('#frmImpresora').on('submit', function(e){
        e.preventDefault();
        var ip = getIPFromInputs();
        $('#IP').val(ip);

        var data = $(this).serializeArray();
        data.push({name:'action', value:'save'});

        $.ajax({
            url: '../api/impresoras.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(resp){
                if (!resp.ok){
                    alert(resp.error || 'Error al guardar');
                    return;
                }
                $('#modalImpresora').modal('hide');
                cargarDatos();
            },
            error: function(xhr){
                console.error('Respuesta cruda API impresoras:', xhr.responseText);
                alert('Error API (debug): ' + xhr.responseText);
            }
        });
    });

    // Probar impresión
    $('#tblImpresoras').on('click', '.btn-test', function(){
        var id = $(this).data('id');
        if (!confirm('¿Enviar etiqueta de prueba a esta impresora?')) return;

        $.ajax({
            url: '../api/impresoras.php',
            method: 'POST',
            data: {action:'test', id:id},
            dataType: 'json',
            success: function(resp){
                if (!resp.ok) {
                    alert(resp.error || 'Error al probar impresión');
                    return;
                }
                alert(resp.mensaje || 'Etiqueta de prueba enviada (simulada)');
            },
            error: function(xhr){
                console.error('Respuesta cruda API test:', xhr.responseText);
                alert('Error API test (debug): ' + xhr.responseText);
            }
        });
    });

    // init
    cargarAlmacenesSelect('#f_id_almacen', true);
    cargarAlmacenesSelect('#id_almacen', false);
    cargarDatos();
});
</script>
