<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:15px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;margin-bottom:15px}
.ap-card{background:#fff;border:1px solid #e9ecef;border-radius:6px;padding:12px;margin-bottom:12px}
.table{font-size:11px}
.ap-scroll{max-height:420px;overflow-y:auto}
.badge-activo{background:#198754;font-size:10px}
.badge-inactivo{background:#dc3545;font-size:10px}
.btn-xs{padding:2px 6px;font-size:10px}
</style>

<div class="ap-container">

<div class="ap-title">Zona de Almacenaje</div>

<div class="ap-card">
<div class="row g-2">

<div class="col-md-3">
<label>Empresa</label>
<select id="f_empresa" class="form-select form-select-sm"></select>
</div>

<div class="col-md-3">
<label>Almacén</label>
<select id="f_almacen" class="form-select form-select-sm"></select>
</div>

<div class="col-md-4 align-self-end">
<button class="btn btn-success btn-sm" onclick="nuevo()">+ Nuevo</button>
<button class="btn btn-outline-secondary btn-sm" onclick="loadZonas()">Refrescar</button>
<button id="btnToggle" class="btn btn-outline-dark btn-sm" onclick="toggleEstado()">Ver Inactivas</button>
</div>

</div>
</div>

<div class="ap-card">
<div class="ap-scroll">
<table class="table table-bordered table-sm table-hover">
<thead>
<tr>
<th width="90">Acciones</th>
<th>Clave</th>
<th>Descripción</th>
<th>Estado</th>
</tr>
</thead>
<tbody id="tbodyZonas"></tbody>
</table>
</div>
</div>

</div>

<!-- MODAL -->
<div class="modal fade" id="modalZona" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Zona</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input type="hidden" id="z_id">

<div class="mb-2">
<label>Clave</label>
<input type="text" id="z_clave" class="form-control form-control-sm" autocomplete="off">
</div>

<div class="mb-2">
<label>Descripción</label>
<input type="text" id="z_descripcion" class="form-control form-control-sm">
</div>

</div>

<div class="modal-footer">
<button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
<button class="btn btn-success btn-sm" onclick="guardarZona()">Guardar</button>
</div>

</div>
</div>
</div>

<script>

let empresaActual=null;
let almacenActual=null;
let verInactivas=false;

init();

/* ============================
   INIT
============================ */
function init(){
fetch('../api/catalogos/filtros_assistpro_header.php?action=empresas')
.then(r=>r.json())
.then(empresas=>{
let html='<option value="">Seleccione</option>';
empresas.forEach(e=>{
html+=`<option value="${e.cve_cia}">
${e.clave_empresa ? e.clave_empresa+' - ' : ''}${e.des_cia}
</option>`;
});
document.getElementById('f_empresa').innerHTML=html;
});
}

/* ============================
   TOOLTIP INIT
============================ */
function activarTooltips(){

// 🔥 destruir tooltips existentes
document.querySelectorAll('.tooltip').forEach(t => t.remove());

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{
new bootstrap.Tooltip(el, {
trigger: 'hover'
});
});

}

/* ============================
   EMPRESA CHANGE
============================ */
document.getElementById('f_empresa').addEventListener('change',function(){
empresaActual=this.value;
if(!empresaActual) return;

fetch(`../api/catalogos/filtros_assistpro_header.php?action=almacenes&empresa=${empresaActual}`)
.then(r=>r.json())
.then(almacenes=>{
let html='<option value="">Seleccione</option>';
almacenes.forEach(a=>{
html+=`<option value="${a.id}">
${a.clave ? a.clave+' - ' : ''}${a.nombre}
</option>`;
});
document.getElementById('f_almacen').innerHTML=html;
});
});

/* ============================
   ALMACEN CHANGE
============================ */
document.getElementById('f_almacen').addEventListener('change',function(){
almacenActual=this.value;
loadZonas();
});

/* ============================
   TOGGLE ESTADO
============================ */
function toggleEstado(){
verInactivas=!verInactivas;
document.getElementById('btnToggle').innerText=
verInactivas?'Ver Activas':'Ver Inactivas';
loadZonas();
}

/* ============================
   LOAD ZONAS
============================ */
function loadZonas(){
if(!empresaActual || !almacenActual) return;

fetch(`../api/catalogos/filtros_assistpro_header.php?action=zonas&empresa=${empresaActual}&almacen=${almacenActual}&estado=${verInactivas?0:1}`)
.then(r=>r.json())
.then(zonas=>{
let html='';
if(!zonas.length){
html=`<tr><td colspan="4" class="text-center text-muted">Sin registros</td></tr>`;
}
zonas.forEach(z=>{
let activo=Number(z.Activo)===1;

html+=`
<tr>
<td>
<button class="btn btn-warning btn-xs"
data-bs-toggle="tooltip"
title="Editar zona"
onclick="editar(${z.cve_almac})">✏</button>

${activo
? `<button class="btn btn-danger btn-xs"
data-bs-toggle="tooltip"
title="Inactivar zona"
onclick="cambiarEstado(${z.cve_almac},0)">⛔</button>`
: `<button class="btn btn-success btn-xs"
data-bs-toggle="tooltip"
title="Reactivar zona"
onclick="cambiarEstado(${z.cve_almac},1)">🔄</button>`}
</td>
<td>${z.clave_almacen}</td>
<td>${z.des_almac}</td>
<td>
${activo
? '<span class="badge badge-activo">Activo</span>'
: '<span class="badge badge-inactivo">Inactivo</span>'}
</td>
</tr>`;
});
document.getElementById('tbodyZonas').innerHTML=html;
activarTooltips();
});
}

/* ============================
   NUEVO
============================ */
function nuevo(){
if(!almacenActual){
alert('Seleccione almacén');
return;
}
z_id.value='';
z_clave.value='';
z_descripcion.value='';
new bootstrap.Modal(document.getElementById('modalZona')).show();
}

/* ============================
   EDITAR
============================ */
function editar(id){
fetch(`../api/catalogos/filtros_assistpro_header.php?action=zonas&empresa=${empresaActual}&almacen=${almacenActual}&estado=${verInactivas?0:1}`)
.then(r=>r.json())
.then(zonas=>{
let z=zonas.find(x=>x.cve_almac==id);
if(!z) return;
z_id.value=z.cve_almac;
z_clave.value=z.clave_almacen;
z_descripcion.value=z.des_almac;
new bootstrap.Modal(document.getElementById('modalZona')).show();
});
}

/* ============================
   NORMALIZACION CLAVE FUERTE
============================ */
document.getElementById('z_clave').addEventListener('input',function(){
let v=this.value;
v=v.replace(/\s/g,'');
v=v.toUpperCase();
v=v.replace(/[^A-Z0-9]/g,'');
this.value=v;
});

/* ============================
   GUARDAR
============================ */
function guardarZona(){
let data={
id:z_id.value,
clave:z_clave.value,
descripcion:z_descripcion.value,
almacen:almacenActual
};

fetch('../api/catalogos/zona_save.php',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify(data)
})
.then(r=>r.json())
.then(res=>{
if(res.ok){
bootstrap.Modal.getInstance(document.getElementById('modalZona')).hide();
loadZonas();
}else{
alert(res.error || 'Error');
}
});
}

/* ============================
   CAMBIAR ESTADO
============================ */
function cambiarEstado(id,estado){
fetch('../api/catalogos/zona_estado.php',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({id,estado})
})
.then(r=>r.json())
.then(res=>{
if(res.ok) loadZonas();
});
}

</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>