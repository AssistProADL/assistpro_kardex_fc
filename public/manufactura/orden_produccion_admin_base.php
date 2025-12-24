<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
table.dataTable tbody tr { font-size: 10px; }
.ap-filters-wrapper {
    background:#fff;
    border-radius:12px;
    border:1px solid #dbe3f0;
    border-top:3px solid #0F5AAD;
}
.ap-filter-label {
    font-size:11px;
    font-weight:600;
    color:#2f3b52;
}
.ap-filter-input, .ap-filter-select {
    font-size:11px;
    border-radius:6px;
}
</style>

<div class="container-fluid mt-3">

<!-- ENCABEZADO -->
<h4 class="fw-bold mb-2" style="color:#0F5AAD;">
    <i class="fa fa-industry me-1"></i> Administración de Manufactura
</h4>

<!-- FILTROS -->
<div class="card mb-3 ap-filters-wrapper">
<div class="card-body p-3">
<div class="row g-3">

<div class="col-md-3">
<label class="ap-filter-label">Empresa</label>
<select id="f_empresa" class="form-select ap-filter-select"></select>
</div>

<div class="col-md-3">
<label class="ap-filter-label">Almacén</label>
<select id="f_almacen" class="form-select ap-filter-select"></select>
</div>

<div class="col-md-3">
<label class="ap-filter-label">Status OT</label>
<select id="f_status" class="form-select ap-filter-select">
<option value="">Todos</option>
<option value="P">Pendiente</option>
<option value="I">Producción</option>
<option value="T">Terminada</option>
<option value="C">Cancelada</option>
</select>
</div>

<div class="col-md-3">
<label class="ap-filter-label">Buscar</label>
<input id="f_buscar" class="form-control ap-filter-input">
</div>

<div class="col-md-3">
<label class="ap-filter-label">Buscar LP</label>
<input id="f_lp" class="form-control ap-filter-input">
</div>

<div class="col-md-3">
<label class="ap-filter-label">Fecha inicio</label>
<input type="date" id="f_fecha_ini" class="form-control ap-filter-input">
</div>

<div class="col-md-3">
<label class="ap-filter-label">Fecha fin</label>
<input type="date" id="f_fecha_fin" class="form-control ap-filter-input">
</div>

<div class="col-md-3 d-flex align-items-end gap-2">
<button id="btnFiltrar" class="btn btn-primary w-100">Aplicar</button>
<button id="btnLimpiar" class="btn btn-outline-secondary w-100">Limpiar</button>
</div>

</div>
</div>
</div>

<!-- GRID -->
<div class="card">
<div class="card-body p-2">
<table id="grid-table" class="table table-bordered table-striped w-100">
<thead>
<tr>
<th>Acciones</th>
<th>Folio</th>
<th>Artículo</th>
<th>Lote</th>
<th>Cantidad</th>
<th>Prod</th>
<th>Usuario</th>
<th>Fecha</th>
<th>Status</th>
</tr>
</thead>
</table>
</div>
</div>

</div>

<!-- MODAL DETALLE -->
<div class="modal fade" id="modalDetalleOT">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Detalle Orden Producción</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="row mb-2" style="font-size:11px">
<div class="col-md-3"><b>Folio:</b> <span id="d_folio"></span></div>
<div class="col-md-3"><b>Artículo:</b> <span id="d_articulo"></span></div>
<div class="col-md-2"><b>Lote:</b> <span id="d_lote"></span></div>
<div class="col-md-2"><b>Cantidad:</b> <span id="d_cantidad"></span></div>
<div class="col-md-2"><b>Status:</b> <span id="d_status"></span></div>
</div>

<table class="table table-sm table-bordered">
<thead>
<tr>
<th>Artículo</th>
<th>Lote</th>
<th>Cantidad</th>
<th>Fecha</th>
<th>Usuario</th>
</tr>
</thead>
<tbody id="tablaDetalleOT"></tbody>
</table>

</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>

</div>
</div>
</div>

<script>
var tablaOT;

$(function(){


// =============================
// CARGA EMPRESAS / ALMACENES
// =============================

// =============================
// CARGA EMPRESA / ALMACENES (filtros_assistpro.php)
// =============================
function initFiltros(){
  // Fechas default: última semana
  const hoy = new Date();
  const fin = hoy.toISOString().slice(0,10);
  const iniDate = new Date(hoy.getTime() - (7*24*60*60*1000));
  const ini = iniDate.toISOString().slice(0,10);
  $('#f_fecha_ini').val(ini);
  $('#f_fecha_fin').val(fin);

  $.getJSON('../api/filtros_assistpro.php', { action:'init', secciones:'empresas,almacenes' }, function(resp){
    if(!resp || resp.ok !== true){
      console.error(resp);
      return;
    }

    // Empresas
    const emps = resp.empresas || [];
    $('#f_empresa').empty();
    if(emps.length === 1){
      // Única empresa: default + lock
      $('#f_empresa').append(`<option value="${emps[0].cve_cia}">${emps[0].des_cia}</option>`);
      $('#f_empresa').val(emps[0].cve_cia).prop('disabled', true);
    } else {
      $('#f_empresa').append('<option value="">Todas</option>');
      emps.forEach(e=>{
        $('#f_empresa').append(`<option value="${e.cve_cia}">${e.des_cia}</option>`);
      });
    }

    // Almacenes (inicial)
    pintarAlmacenes(resp.almacenes || []);

    // Si viene buscar por querystring
    const qs = new URLSearchParams(window.location.search);
    const buscar = qs.get('buscar') || '';
    if(buscar){
      $('#f_buscar').val(buscar);
    }

    // primera carga grid (con defaults)
    setTimeout(()=>tablaOT.ajax.reload(), 50);
  });
}

function pintarAlmacenes(lista){
  $('#f_almacen').html('<option value="">Todos</option>');
  (lista || []).forEach(a=>{
    const clave = a.cve_almac || a.clave || a.clave_almacen || '';
    const nombre = a.nombre || a.des_almac || '';
    if(!clave) return;
    $('#f_almacen').append(`<option value="${clave}">(${clave}) ${nombre}</option>`);
  });
}

// Inicial
initFiltros();

// Cambio de empresa: recargar almacenes filtrados
$('#f_empresa').on('change', function(){
  const emp = this.value || '';
  $.getJSON('../api/filtros_assistpro.php', { action:'init', secciones:'almacenes', empresa: emp }, function(resp){
    if(!resp || resp.ok !== true){ console.error(resp); return; }
    pintarAlmacenes(resp.almacenes || []);
  });
});
tablaOT = $('#grid-table').DataTable({
serverSide:true,
processing:true,
scrollX:true,
scrollY:'420px',
pageLength:25,
ordering:false,
ajax:{
url:'../api/orden_produccion_admin_data.php',
type:'POST',
data:function(d){
d.empresa=$('#f_empresa').val();
d.almacen=$('#f_almacen').val();
d.status=$('#f_status').val();
d.buscar=$('#f_buscar').val();
d.lp=$('#f_lp').val();
d.fecha_ini=$('#f_fecha_ini').val();
d.fecha_fin=$('#f_fecha_fin').val();
}
},
columns:[
{data:'acciones',orderable:false},
{data:'Folio_Pro'},
{data:'Cve_Articulo'},
{data:'Cve_Lote'},
{data:'Cantidad'},
{data:'Cant_Prod'},
{data:'Cve_Usuario'},
{data:'Fecha'},
{data:'Status'}
]
});

$('#btnFiltrar').click(()=>tablaOT.ajax.reload());
$('#btnLimpiar').click(()=>{
$('input,select').val('');
tablaOT.ajax.reload();
});

$(document).on('click','.btnVerOT',function(){
let id=$(this).data('id');
$('#tablaDetalleOT').html('<tr><td colspan="5">Cargando...</td></tr>');

$.post('../api/orden_produccion_detalle.php',{id:id},function(resp){
let d=JSON.parse(resp);

$('#d_folio').text(d.ot.Folio_Pro);
$('#d_articulo').text(d.ot.Cve_Articulo);
$('#d_lote').text(d.ot.Cve_Lote);
$('#d_cantidad').text(d.ot.Cantidad);
$('#d_status').text(d.ot.Status);

let h='';
d.detalle.forEach(r=>{
h+=`<tr>
<td>${r.Cve_Articulo}</td>
<td>${r.Cve_Lote}</td>
<td>${r.Cantidad}</td>
<td>${r.Fecha_Prod}</td>
<td>${r.Usr_Armo}</td>
</tr>`;
});
$('#tablaDetalleOT').html(h);
$('#modalDetalleOT').modal('show');
});
});

});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
