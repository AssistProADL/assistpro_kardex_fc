<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
$apiUrl = '../api/activos_catalogos_api.php?cat=tipo';
?>
<div class="container-fluid py-2">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <i class="fa-solid fa-boxes-stacked text-primary"></i>
      <div class="fw-bold">Catálogo de Tipos de Activo</div>
    </div>
    <div class="badge rounded-pill text-bg-light border">
      <span class="text-danger">*</span> Obligatorios: <b>nombre</b>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-12 col-md-3"><div class="card"><div class="card-body py-2">
      <div class="text-muted">Total</div><div class="fs-5 fw-bold" id="k_total">0</div>
    </div></div></div>
    <div class="col-12 col-md-3"><div class="card"><div class="card-body py-2">
      <div class="text-muted">Activos</div><div class="fs-5 fw-bold" id="k_activos">0</div>
    </div></div></div>

    <div class="col-12 col-md-6">
      <div class="d-flex gap-2 justify-content-end">
        <input id="q" class="form-control form-control-sm" placeholder="Buscar..." style="max-width:260px">
        <button class="btn btn-sm btn-outline-primary" id="btnBuscar"><i class="fa fa-search"></i></button>
        <button class="btn btn-sm btn-primary" id="btnNuevo"><i class="fa fa-plus"></i> Nuevo</button>

        <button class="btn btn-sm btn-outline-secondary" id="btnCSV">
          <i class="fa fa-file-arrow-up"></i> Importar CSV
        </button>
        <input type="file" id="csvFile" accept=".csv" style="display:none">
      </div>
    </div>
  </div>

  <div class="table-responsive border rounded-3">
    <table id="tbl" class="table table-sm table-striped align-middle mb-0" style="font-size:10px; min-width:900px">
      <thead class="table-light">
        <tr>
          <th style="width:90px">Acciones</th>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th class="text-center">Activo</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdl" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h6 class="modal-title fw-bold">Tipo de Activo</h6>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="id_tipo">
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Nombre <span class="text-danger">*</span></label>
          <input id="nombre" class="form-control form-control-sm">
        </div>
        <div class="col-md-6">
          <label class="form-label">Activo</label>
          <select id="activo" class="form-select form-select-sm">
            <option value="1">Sí</option><option value="0">No</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Descripción</label>
          <input id="descripcion" class="form-control form-control-sm">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      <button class="btn btn-sm btn-primary" id="btnGuardar"><i class="fa fa-save"></i> Guardar</button>
    </div>
  </div></div>
</div>

<script>
const API = <?= json_encode($apiUrl) ?>;
let dt = null, mdl=null;

function toast(msg){ alert(msg); }

async function apiGet(url){
  const r = await fetch(url,{cache:'no-store'});
  return await r.json();
}
async function apiPost(fd){
  const r = await fetch(API, {method:'POST', body: fd});
  return await r.json();
}

function setKpis(k){
  document.getElementById('k_total').textContent = k.total ?? 0;
  document.getElementById('k_activos').textContent = k.activos ?? 0;
}

function render(rows){
  const tb = document.querySelector('#tbl tbody');
  tb.innerHTML = rows.map(r=>`
    <tr>
      <td class="text-nowrap">
        <button class="btn btn-xs btn-outline-primary" onclick='edit(${JSON.stringify(r)})'><i class="fa fa-pen"></i></button>
        <button class="btn btn-xs btn-outline-danger" onclick='del(${r.id_tipo})'><i class="fa fa-trash"></i></button>
      </td>
      <td>${r.id_tipo}</td>
      <td>${r.nombre ?? ''}</td>
      <td>${r.descripcion ?? ''}</td>
      <td class="text-center">${(r.activo==1?'Sí':'No')}</td>
    </tr>
  `).join('');
}

async function load(){
  const q = document.getElementById('q').value.trim();
  const url = API + (API.includes('?') ? '&' : '?') + 'action=list' + (q?('&q='+encodeURIComponent(q)):'');
  const j = await apiGet(url);
  if(!j.success){ toast(j.error||'Error'); return; }
  setKpis(j.kpis||{});
  render(j.data||[]);
}

function clearForm(){
  document.getElementById('id_tipo').value='';
  document.getElementById('nombre').value='';
  document.getElementById('descripcion').value='';
  document.getElementById('activo').value='1';
}

function edit(r){
  clearForm();
  document.getElementById('id_tipo').value = r.id_tipo || '';
  document.getElementById('nombre').value = r.nombre || '';
  document.getElementById('descripcion').value = r.descripcion || '';
  document.getElementById('activo').value = String(r.activo ?? 1);
  mdl.show();
}

async function del(id){
  if(!confirm('¿Eliminar registro?')) return;
  const fd = new FormData();
  fd.append('action','delete');
  fd.append('id',id);
  const j = await apiPost(fd);
  if(!j.success){ toast(j.error||'Error'); return; }
  load();
}

async function save(){
  const fd = new FormData();
  fd.append('action','save');
  fd.append('id_tipo', document.getElementById('id_tipo').value);
  fd.append('nombre', document.getElementById('nombre').value);
  fd.append('descripcion', document.getElementById('descripcion').value);
  fd.append('activo', document.getElementById('activo').value);
  const j = await apiPost(fd);
  if(!j.success){ toast(j.error||'Error'); return; }
  mdl.hide();
  load();
}

async function importCSV(file){
  const fd = new FormData();
  fd.append('action','import_csv');
  fd.append('file', file);
  const r = await fetch(API, {method:'POST', body: fd});
  const j = await r.json();
  if(!j.success){ toast(j.error||'Error'); return; }
  toast((j.mensaje||'Importado') + ` | OK: ${j.total_ok||0} ERR: ${j.total_err||0}`);
  load();
}

document.addEventListener('DOMContentLoaded', ()=>{
  mdl = new bootstrap.Modal(document.getElementById('mdl'));
  document.getElementById('btnBuscar').onclick = load;
  document.getElementById('btnNuevo').onclick = ()=>{ clearForm(); mdl.show(); };
  document.getElementById('btnGuardar').onclick = save;

  document.getElementById('btnCSV').onclick = ()=>document.getElementById('csvFile').click();
  document.getElementById('csvFile').addEventListener('change', (e)=>{
    if(e.target.files && e.target.files[0]) importCSV(e.target.files[0]);
    e.target.value='';
  });

  load();
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
