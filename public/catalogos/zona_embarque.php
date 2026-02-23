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
.btn-xs{padding:2px 6px;font-size:10px}
</style>

<div class="ap-container">

<div class="ap-title">Zonas de Embarque</div>

<div class="ap-card">
<div class="row g-2">

<div class="col-md-4">
<label>Empresa</label>
<select id="f_empresa" class="form-select form-select-sm"></select>
</div>

<div class="col-md-4">
<label>Almacén</label>
<select id="f_almacen" class="form-select form-select-sm"></select>
</div>

<div class="col-md-4 align-self-end">
<button id="btnNuevo" class="btn btn-success btn-sm">+ Nuevo</button>
<button class="btn btn-outline-secondary btn-sm" onclick="load()">Refrescar</button>
<button id="btnToggle" class="btn btn-outline-dark btn-sm" onclick="toggle()">Ver Inactivas</button>
</div>

</div>
</div>

<div class="ap-card">
<div class="ap-scroll">
<table class="table table-bordered table-sm table-hover">
<thead>
<tr>
<th width="110">Acciones</th>
<th>Clave</th>
<th>Descripción</th>
<th>Staging</th>
<th>Status</th>
<th>Estado</th>
</tr>
</thead>
<tbody id="tbody"></tbody>
</table>
</div>
</div>

</div>

<!-- MODAL -->

<div class="modal fade" id="modalEmbarque">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Zona de Embarque</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" id="embarque_id">

<div class="mb-2">
<label>Clave</label>
<input id="embarque_clave" class="form-control form-control-sm" maxlength="20">
</div>

<div class="mb-2">
<label>Descripción</label>
<input id="embarque_desc" class="form-control form-control-sm">
</div>

<div class="form-check">
<input class="form-check-input" type="checkbox" id="embarque_staging">
<label class="form-check-label">Área Staging</label>
</div>

</div>

<div class="modal-footer">
<button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
<button class="btn btn-primary btn-sm" id="btnGuardar">Guardar</button>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

let empresa=null;
let almacen=null;
let verInactivas=false;
let modal=null;

document.addEventListener("DOMContentLoaded", function() {

modal = new bootstrap.Modal(document.getElementById('modalEmbarque'));

document.getElementById('btnNuevo').addEventListener('click', nuevo);
document.getElementById('btnGuardar').addEventListener('click', guardar);

init();

});

/* ================= INIT ================= */

function init(){
fetch('../api/catalogos/filtros_assistpro_header.php?action=empresas')
.then(r=>r.json())
.then(data=>{
let html='<option value="">Seleccione</option>';
data.forEach(e=>{
html+=`<option value="${e.cve_cia}">
${e.clave_empresa?e.clave_empresa+' - ':''}${e.des_cia}
</option>`;
});
f_empresa.innerHTML=html;
});
}

/* ================= FILTROS ================= */

f_empresa.onchange=function(){
empresa=this.value;
tbody.innerHTML='';

document.querySelectorAll('[data-bs-toggle="tooltip"]')
.forEach(el => new bootstrap.Tooltip(el));

fetch(`../api/catalogos/filtros_assistpro_header.php?action=almacenes&empresa=${empresa}`)
.then(r=>r.json())
.then(data=>{
let html='<option value="">Seleccione</option>';
data.forEach(a=>{
html+=`<option value="${a.id}">
${a.clave?a.clave+' - ':''}${a.nombre}
</option>`;
});
f_almacen.innerHTML=html;
});
};

f_almacen.onchange=function(){
almacen=this.value;
load();
};

/* ================= LISTA ================= */

function load(){

if(!almacen){
tbody.innerHTML='';
return;
}

fetch(`../api/catalogos/embarque_list.php?almacen=${almacen}&estado=${verInactivas?0:1}`)
.then(r=>r.json())
.then(data=>{

let html='';

if(!Array.isArray(data) || data.length===0){
html=`
<tr>
<td colspan="6" class="text-center text-muted">
No hay registros
</td>
</tr>`;
} else {

data.forEach(z=>{

const staging = z.AreaStagging === 'S';

html+=`
<tr>
<td>
<button class="btn btn-warning btn-xs"
        onclick="editar(${z.id})"
        data-bs-toggle="tooltip"
        title="Editar">
✏</button>

<button class="btn btn-danger btn-xs"
        onclick="estado(${z.id},${z.Activo?0:1})"
        data-bs-toggle="tooltip"
        title="${z.Activo?'Inactivar':'Activar'}">
${z.Activo? '⛔':'🔄'}
</button>
</td>
<td>${z.cve_ubicacion ?? ''}</td>
<td>${z.descripcion ?? ''}</td>
<td class="text-center">${staging?'✔':'✖'}</td>
<td>${z.status ?? ''}</td>
<td>${z.Activo?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-danger">Inactivo</span>'}</td>
</tr>`;
});

}

tbody.innerHTML=html;

});
}

/* ================= NUEVO ================= */

function nuevo(){
if(!almacen){
alert('Seleccione un almacén primero');
return;
}

embarque_id.value='';
embarque_clave.value='';
embarque_desc.value='';
embarque_staging.checked=false;

modal.show();
}

/* ================= EDITAR ================= */

function editar(id){

fetch(`../api/catalogos/embarque_list.php?almacen=${almacen}&estado=1`)
.then(r=>r.json())
.then(data=>{

const z=data.find(x=>x.id==id);
if(!z) return;

embarque_id.value=z.id;
embarque_clave.value=z.cve_ubicacion;
embarque_desc.value=z.descripcion;
embarque_staging.checked = z.AreaStagging==='S';

modal.show();
});
}

/* ================= GUARDAR ================= */

function guardar(){

const payload={
id:embarque_id.value || null,
almacen:almacen,
clave:embarque_clave.value.trim().toUpperCase().replace(/[^A-Z0-9]/g,''),
descripcion:embarque_desc.value.trim(),
stagging:embarque_staging.checked?'S':'N'
};

fetch('../api/catalogos/embarque_save.php',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify(payload)
})
.then(r=>r.json())
.then(res=>{
if(res.ok){
modal.hide();
load();
}else{
alert(res.error||'Error al guardar');
}
});
}

/* ================= ESTADO ================= */

function estado(id,valor){

fetch('../api/catalogos/embarque_estado.php',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({id,estado:valor})
})
.then(r=>r.json())
.then(res=>{
if(res.ok){
load();
}
});
}

/* ================= TOGGLE ================= */

function toggle(){
verInactivas=!verInactivas;
btnToggle.innerText=verInactivas?'Ver Activas':'Ver Inactivas';
load();
}

</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>