<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
.ap-container{padding:12px;font-size:10px}
.ap-title{font-size:18px;font-weight:700;color:#0b5ed7;margin:0 0 10px 0;display:flex;align-items:center;gap:10px}
.ap-sub{color:#6c757d;font-size:11px;margin:-6px 0 10px 0}
.ap-card{background:#fff;border:1px solid #d0d7e2;border-radius:12px;padding:10px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ap-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px}
.ap-toolbar .form-control,.ap-toolbar .form-select{font-size:10px;height:28px;padding:4px 8px}
.ap-btn{font-size:10px;padding:4px 8px}
.ap-table-wrap{width:100%;overflow:auto;border:1px solid #e5e7eb;border-radius:10px}
table.ap-grid{border-collapse:separate;border-spacing:0;width:1600px;font-size:10px}
table.ap-grid th{position:sticky;top:0;background:#f8fafc;z-index:2;border-bottom:1px solid #e5e7eb;padding:6px 8px;white-space:nowrap;text-align:center}
table.ap-grid td{border-bottom:1px solid #f1f5f9;padding:6px 8px;white-space:nowrap;text-align:center}
.ap-actions{display:flex;gap:6px;justify-content:center}
.ap-chip{display:inline-block;border-radius:999px;padding:2px 8px;font-size:10px}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.bad{background:#f8d7da;color:#842029}
.ap-chip.inf{background:#cff4fc;color:#055160}
.ap-spinner{display:none;align-items:center;gap:8px;color:#0b5ed7}
.ap-spinner.show{display:flex}
.ap-modal .form-control,.ap-modal .form-select{font-size:12px}
.ap-muted{color:#6c757d}
</style>

<div class="ap-container">
  <div class="ap-title">
    <i class="fa-solid fa-truck-ramp-box"></i>
    Catálogo · Zonas de Embarque
  </div>
  <div class="ap-sub">Administración operativa de ubicaciones de embarque / staging (25 renglones, scroll H/V, CSV layout estándar).</div>

  <div class="ap-card">
    <div class="ap-toolbar">
      <button class="btn btn-primary ap-btn" id="btnNuevo"><i class="fa fa-plus"></i> Nuevo</button>

      <div class="ap-spinner" id="spin">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <span>Cargando...</span>
      </div>

      <div style="flex:1"></div>

      <select class="form-select" id="fAlm" style="min-width:220px">
        <option value="0">Todos los almacenes</option>
      </select>

      <input class="form-control" id="fQ" placeholder="Buscar: ubicación, descripción, status..." style="min-width:260px">

      <div class="form-check" style="margin-left:6px">
        <input class="form-check-input" type="checkbox" id="fInactivos">
        <label class="form-check-label ap-muted" for="fInactivos">Ver inactivos</label>
      </div>

      <button class="btn btn-outline-secondary ap-btn" id="btnBuscar"><i class="fa fa-magnifying-glass"></i> Buscar</button>

      <div class="btn-group">
        <a class="btn btn-outline-success ap-btn" id="btnCsvLayout"><i class="fa fa-file-csv"></i> CSV Layout</a>
        <a class="btn btn-outline-success ap-btn" id="btnCsvDatos"><i class="fa fa-download"></i> CSV Datos</a>
      </div>

      <label class="btn btn-outline-primary ap-btn" style="margin:0">
        <i class="fa fa-upload"></i> Importar CSV
        <input type="file" id="csvFile" accept=".csv" hidden>
      </label>
    </div>

    <div class="ap-table-wrap">
      <table class="ap-grid" id="grid">
        <thead>
          <tr>
            <th style="min-width:120px">Acciones</th>
            <th>ID</th>
            <th>Almacén</th>
            <th>Ubicación</th>
            <th>Descripción</th>
            <th>Status</th>
            <th>Stagging</th>
            <th>Largo</th>
            <th>Ancho</th>
            <th>Alto</th>
            <th>Activo</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="11" class="ap-muted" style="text-align:center;padding:16px">Sin datos</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade ap-modal" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="mdlTitle" style="font-weight:700;color:#0b5ed7"><i class="fa fa-warehouse"></i> Zona de Embarque</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ID_Embarque">

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Almacén *</label>
            <select class="form-select" id="cve_almac"></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Ubicación (código) *</label>
            <input class="form-control" id="cve_ubicacion" list="dlUbic" placeholder="Ej: ZRWHCR / STG-01 / EMB-DOCK-1">
            <datalist id="dlUbic"></datalist>
            <div class="ap-muted" style="font-size:11px;margin-top:4px">Tip: escribe y te sugerimos códigos (si existen en ubicaciones).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" id="status">
              <option value="">(N/A)</option>
              <option value="A">A - Disponible</option>
              <option value="B">B - Bloqueada</option>
              <option value="M">M - Mantenimiento</option>
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Descripción</label>
            <input class="form-control" id="descripcion" maxlength="45" placeholder="Ej: Andén 1 / Staging embarques / Pre-embarque">
          </div>

          <div class="col-md-4">
            <label class="form-label">Área Stagging</label>
            <select class="form-select" id="AreaStagging">
              <option value="">(N/A)</option>
              <option value="S">S</option>
              <option value="N">N</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Largo</label>
            <input class="form-control" id="largo" type="number" step="0.01" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Ancho</label>
            <input class="form-control" id="ancho" type="number" step="0.01" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Alto</label>
            <input class="form-control" id="alto" type="number" step="0.01" min="0">
          </div>

          <div class="col-md-4">
            <label class="form-label">Activo</label>
            <select class="form-select" id="Activo">
              <option value="1">1 - Activo</option>
              <option value="0">0 - Inactivo</option>
            </select>
          </div>
        </div>

        <div id="mdlMsg" class="alert alert-warning mt-3" style="display:none;font-size:12px"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" id="btnGuardar"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
const API = '../api/zona_embarques.php';
const $ = (s)=>document.querySelector(s);
const tb = $('#tbody');
const spin = $('#spin');
let mdl;

function setSpin(v){ spin.classList.toggle('show', !!v); }

function esc(s){
  s = (s??'')+'';
  return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

async function api(action, data={}, method='POST'){
  const opt = { method };
  let url = API + '?action=' + encodeURIComponent(action);

  if(method==='GET'){
    const qs = new URLSearchParams(data).toString();
    if(qs) url += '&' + qs;
  }else{
    const fd = new FormData();
    Object.entries(data).forEach(([k,v])=> fd.append(k, v ?? ''));
    opt.body = fd;
  }
  const r = await fetch(url, opt);
  return await r.json();
}

async function cargarAlmacenes(){
  const rows = await api('almacenes', {}, 'GET');
  const f = $('#fAlm');
  const m = $('#cve_almac');
  f.innerHTML = '<option value="0">Todos los almacenes</option>';
  m.innerHTML = '<option value="">Selecciona...</option>';
  (rows||[]).forEach(x=>{
    const op1 = document.createElement('option');
    op1.value = x.cve_almac; op1.textContent = `${x.des_almac} (${x.cve_almac})`;
    f.appendChild(op1);

    const op2 = document.createElement('option');
    op2.value = x.cve_almac; op2.textContent = `${x.des_almac} (${x.cve_almac})`;
    m.appendChild(op2);
  });
}

function chip(v, okText='S', badText='Listo'){
  if(v===null || v===undefined || v==='') return '<span class="ap-chip inf">N/A</span>';
  const t = (v+'').toUpperCase();
  if(t==='1' || t==='S' || t==='A') return `<span class="ap-chip ok">${esc(v)}</span>`;
  if(t==='0' || t==='N' || t==='B') return `<span class="ap-chip bad">${esc(v)}</span>`;
  return `<span class="ap-chip inf">${esc(v)}</span>`;
}

async function listar(){
  setSpin(true);
  try{
    const data = await api('list', {
      inactivos: $('#fInactivos').checked ? 1 : 0,
      cve_almac: parseInt($('#fAlm').value||'0',10),
      q: ($('#fQ').value||'').trim()
    }, 'GET');

    if(!data || !data.length){
      tb.innerHTML = `<tr><td colspan="11" class="ap-muted" style="text-align:center;padding:16px">Sin datos</td></tr>`;
      return;
    }

    tb.innerHTML = data.map(r=>{
      const activo = (parseInt(r.Activo??1,10)===1);
      return `
        <tr>
          <td>
            <div class="ap-actions">
              <button class="btn btn-sm btn-outline-primary ap-btn" onclick="editar(${r.ID_Embarque})"><i class="fa fa-pen"></i></button>
              ${activo
                ? `<button class="btn btn-sm btn-outline-danger ap-btn" onclick="baja(${r.ID_Embarque})" title="Inactivar"><i class="fa fa-ban"></i></button>`
                : `<button class="btn btn-sm btn-outline-success ap-btn" onclick="recuperar(${r.ID_Embarque})" title="Recuperar"><i class="fa fa-rotate-left"></i></button>`
              }
            </div>
          </td>
          <td>${esc(r.ID_Embarque)}</td>
          <td>${esc(r.des_almac||('ALM '+r.cve_almac))} <span class="ap-muted">(${esc(r.cve_almac)})</span></td>
          <td><b>${esc(r.cve_ubicacion)}</b></td>
          <td>${esc(r.descripcion||'')}</td>
          <td>${chip(r.status)}</td>
          <td>${chip(r.AreaStagging)}</td>
          <td>${esc(r.largo??'')}</td>
          <td>${esc(r.ancho??'')}</td>
          <td>${esc(r.alto??'')}</td>
          <td>${activo ? '<span class="ap-chip ok">ACTIVO</span>' : '<span class="ap-chip bad">INACTIVO</span>'}</td>
        </tr>
      `;
    }).join('');
  }finally{
    setSpin(false);
  }
}

function limpiarModal(){
  $('#mdlMsg').style.display='none';
  $('#ID_Embarque').value='';
  $('#cve_almac').value='';
  $('#cve_ubicacion').value='';
  $('#status').value='';
  $('#descripcion').value='';
  $('#AreaStagging').value='';
  $('#largo').value='';
  $('#ancho').value='';
  $('#alto').value='';
  $('#Activo').value='1';
}

function showMsg(msg){
  const box = $('#mdlMsg');
  box.textContent = msg;
  box.style.display = 'block';
}

async function sugerirUbic(){
  const q = ($('#cve_ubicacion').value||'').trim();
  const rows = await api('ubicaciones_suggest', { q }, 'GET');
  const dl = $('#dlUbic');
  dl.innerHTML = '';
  (rows||[]).forEach(x=>{
    const op = document.createElement('option');
    op.value = x.cve_ubicacion;
    dl.appendChild(op);
  });
}

async function editar(id){
  setSpin(true);
  try{
    const r = await api('get', { ID_Embarque: id }, 'GET');
    limpiarModal();
    if(r && !r.error){
      $('#ID_Embarque').value = r.ID_Embarque;
      $('#cve_ubicacion').value = r.cve_ubicacion || '';
      $('#cve_almac').value = r.cve_almac || '';
      $('#status').value = r.status || '';
      $('#Activo').value = (r.Activo==null ? '1' : String(r.Activo));
      $('#descripcion').value = r.descripcion || '';
      $('#AreaStagging').value = r.AreaStagging || '';
      $('#largo').value = r.largo ?? '';
      $('#ancho').value = r.ancho ?? '';
      $('#alto').value = r.alto ?? '';
      $('#mdlTitle').innerHTML = '<i class="fa fa-pen"></i> Editar Zona de Embarque';
      mdl.show();
      sugerirUbic();
    }else{
      alert(r.error || 'No se pudo abrir el registro');
    }
  }finally{
    setSpin(false);
  }
}

async function baja(id){
  if(!confirm('¿Inactivar este registro?')) return;
  setSpin(true);
  try{
    const r = await api('delete', { ID_Embarque: id }, 'POST');
    if(r.success) listar();
    else alert(r.error || 'No se pudo inactivar');
  }finally{ setSpin(false); }
}

async function recuperar(id){
  if(!confirm('¿Recuperar este registro?')) return;
  setSpin(true);
  try{
    const r = await api('restore', { ID_Embarque: id }, 'POST');
    if(r.success) listar();
    else alert(r.error || 'No se pudo recuperar');
  }finally{ setSpin(false); }
}

async function guardar(){
  const payload = {
    ID_Embarque: $('#ID_Embarque').value,
    cve_almac: $('#cve_almac').value,
    cve_ubicacion: ($('#cve_ubicacion').value||'').trim(),
    status: $('#status').value,
    Activo: $('#Activo').value,
    descripcion: ($('#descripcion').value||'').trim(),
    AreaStagging: $('#AreaStagging').value,
    largo: $('#largo').value,
    ancho: $('#ancho').value,
    alto: $('#alto').value
  };

  if(!payload.cve_almac || payload.cve_almac===''){
    showMsg('cve_almac es obligatorio');
    return;
  }
  if(!payload.cve_ubicacion){
    showMsg('cve_ubicacion es obligatorio');
    return;
  }

  setSpin(true);
  try{
    const action = payload.ID_Embarque ? 'update' : 'create';
    const r = await api(action, payload, 'POST');
    if(r.success){
      mdl.hide();
      listar();
    }else{
      showMsg(r.error ? (r.error + (r.detalles ? ' · ' + r.detalles.join(', ') : '')) : 'No se pudo guardar');
    }
  }finally{ setSpin(false); }
}

async function importarCSV(file){
  if(!file) return;
  if(!confirm('¿Importar CSV? Se aplicará UPSERT (por ID o por clave almacén+ubicación).')) return;
  setSpin(true);
  try{
    const fd = new FormData();
    fd.append('file', file);
    const r = await fetch(API+'?action=import_csv', { method:'POST', body: fd });
    const j = await r.json();
    if(j.success){
      alert(`Importación OK\nOK: ${j.rows_ok}\nERR: ${j.rows_err}`);
      listar();
    }else{
      alert(j.error || 'Error al importar');
      console.log(j);
    }
  }finally{ setSpin(false); $('#csvFile').value=''; }
}

document.addEventListener('DOMContentLoaded', async ()=>{
  mdl = new bootstrap.Modal(document.getElementById('mdl'));

  $('#btnNuevo').addEventListener('click', ()=>{
    limpiarModal();
    $('#mdlTitle').innerHTML = '<i class="fa fa-plus"></i> Nueva Zona de Embarque';
    mdl.show();
    sugerirUbic();
  });

  $('#btnBuscar').addEventListener('click', listar);
  $('#fInactivos').addEventListener('change', listar);
  $('#fAlm').addEventListener('change', listar);
  $('#fQ').addEventListener('keydown', (e)=>{ if(e.key==='Enter') listar(); });

  $('#btnGuardar').addEventListener('click', guardar);

  $('#btnCsvLayout').href = API+'?action=export_csv&tipo=layout';
  $('#btnCsvDatos').href  = API+'?action=export_csv&tipo=datos';

  $('#csvFile').addEventListener('change', (e)=> importarCSV(e.target.files[0]||null));

  $('#cve_ubicacion').addEventListener('input', ()=>{
    // debounce sencillo
    clearTimeout(window.__tUbic);
    window.__tUbic = setTimeout(sugerirUbic, 250);
  });

  await cargarAlmacenes();
  await listar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
