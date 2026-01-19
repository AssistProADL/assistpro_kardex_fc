<?php
// /public/sfa/promociones/promociones.php
// UI master para Promociones (sin columnas fiscales) + path API correcto

$menuStart = __DIR__ . '/../../bi/_menu_global.php';
$menuEnd   = __DIR__ . '/../../bi/_menu_global_end.php';

if (file_exists($menuStart)) require_once $menuStart;
?>
<style>
  .ap-wrap{ font-size:10px; }
  .ap-title{ font-weight:700; font-size:14px; }
  .ap-btn{ font-size:10px; padding:6px 10px; }
  .ap-table th, .ap-table td{ font-size:10px; vertical-align:middle; }
  .badge-dot{ display:inline-block; width:10px; height:10px; border-radius:50%; }
  .ap-muted{ color:#666; }
  .ap-debug{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
             font-size:10px; background:#f8f9fa; border:1px solid #eee; padding:8px; border-radius:6px; max-height:180px; overflow:auto; }
</style>

<div class="container-fluid ap-wrap">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="ap-title">Administración de Promociones</div>
    <div class="ap-muted">Motor: Unidad + Monto + Mixta (Rules/Scope/Rewards)</div>
  </div>

  <div class="card mb-2">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Seleccione un almacén</label>
          <select id="almacen_id" class="form-select form-select-sm"></select>
        </div>
        <div class="col-md-6 text-end">
          <button class="btn btn-outline-secondary ap-btn" onclick="toggleDebug()">Debug</button>
          <button class="btn btn-primary ap-btn" onclick="loadPromos()">Buscar</button>
          <button class="btn btn-success ap-btn" onclick="openPromo()">+ Nuevo</button>
        </div>
      </div>

      <div id="debugBox" class="mt-2 ap-debug" style="display:none;"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-bordered table-hover ap-table mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:90px;">Acciones</th>
              <th style="width:60px;">Status</th>
              <th style="width:70px;">ID</th>
              <th style="width:160px;">Clave</th>
              <th>Descripción</th>
              <th style="width:110px;">Inicio</th>
              <th style="width:110px;">Fin</th>
              <th style="width:80px;">Rules</th>
              <th style="width:80px;">Scope</th>
            </tr>
          </thead>
          <tbody id="tb"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Promo -->
<div class="modal fade" id="mdlPromo" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title fw-bold" style="font-size:12px;">Promoción</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="font-size:10px;">
        <input type="hidden" id="p_id">

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Almacén*</label>
            <select id="p_id_almacen" class="form-select form-select-sm"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Clave*</label>
            <input id="p_cve" class="form-control form-control-sm" maxlength="255" placeholder="Ej. PROMO-ENERO">
          </div>
          <div class="col-md-4">
            <label class="form-label">Estatus</label>
            <select id="p_activo" class="form-select form-select-sm">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
          </div>

          <div class="col-md-12">
            <label class="form-label">Descripción*</label>
            <input id="p_desc" class="form-control form-control-sm" maxlength="255" placeholder="Ej. Compra $10,000 y obtén bonificación">
          </div>

          <div class="col-md-3">
            <label class="form-label">Caduca</label>
            <select id="p_caduca" class="form-select form-select-sm">
              <option value="0">No</option>
              <option value="1">Sí</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Inicio</label>
            <input id="p_fi" type="date" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Fin</label>
            <input id="p_ff" type="date" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <input id="p_tipo" class="form-control form-control-sm" placeholder="UNIDADES/MONTO/MIXTO">
          </div>
        </div>

        <div class="mt-2 ap-muted">
          Fiscal/contable no se gestiona aquí (se eliminó del módulo).
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary ap-btn" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary ap-btn" onclick="savePromo()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * PATH CORRECTO PARA ESTA UBICACIÓN:
 * /public/sfa/promociones/promociones.php  ->  /public/api/promociones/promociones_api.php
 */
const API = '../../api/promociones/promociones_api.php';

let mdlPromo;
let dbg = [];

document.addEventListener('DOMContentLoaded', async () => {
  mdlPromo = new bootstrap.Modal(document.getElementById('mdlPromo'));
  dbgPush('API=' + API);
  await loadAlmacenes();
});

function dbgPush(s){
  dbg.push(`[${new Date().toLocaleTimeString()}] ${s}`);
  const box = document.getElementById('debugBox');
  if(box.style.display !== 'none') box.textContent = dbg.join("\n");
}
function toggleDebug(){
  const box = document.getElementById('debugBox');
  box.style.display = (box.style.display === 'none') ? 'block' : 'none';
  box.textContent = dbg.join("\n");
}

async function apiGet(params){
  const url = API + '?' + new URLSearchParams(params).toString();
  dbgPush('GET ' + url);
  const r = await fetch(url, {cache:'no-store'});
  const txt = await r.text();

  // si es 404/HTML, esto te lo deja explícito
  if(!r.ok){
    throw new Error(`HTTP ${r.status} => ${txt.slice(0,300)}`);
  }

  let j;
  try{ j = JSON.parse(txt); }
  catch(e){
    throw new Error(`Respuesta no JSON (probable HTML/404). => ${txt.slice(0,300)}`);
  }
  return j;
}

async function apiPost(params){
  const fd = new FormData();
  Object.keys(params).forEach(k => fd.append(k, params[k]));
  dbgPush('POST ' + API + ' action=' + (params.action||''));
  const r = await fetch(API, {method:'POST', body:fd});
  const txt = await r.text();

  if(!r.ok){
    throw new Error(`HTTP ${r.status} => ${txt.slice(0,300)}`);
  }

  let j;
  try{ j = JSON.parse(txt); }
  catch(e){
    throw new Error(`Respuesta no JSON (probable HTML/404). => ${txt.slice(0,300)}`);
  }
  return j;
}

async function loadAlmacenes(){
  try{
    const j = await apiGet({action:'almacenes'});
    if(!j.ok) throw new Error(j.error || 'Error al cargar almacenes');

    const sel = document.getElementById('almacen_id');
    const sel2 = document.getElementById('p_id_almacen');
    sel.innerHTML = '';
    sel2.innerHTML = '';

    (j.rows || []).forEach(a=>{
      const txt = `(${a.id}) ${a.nombre}`;
      sel.add(new Option(txt, a.id));
      sel2.add(new Option(txt, a.id));
    });

    dbgPush('Almacenes=' + (j.rows||[]).length);

    if(sel.value){
      await loadPromos();
    }
  }catch(e){
    toggleDebug();
    alert('No cargó almacenes.\n\n' + e.message + '\n\nRevisa Debug.');
  }
}

async function loadPromos(){
  try{
    const almacen_id = document.getElementById('almacen_id').value;
    const j = await apiGet({action:'list', almacen_id});
    if(!j.ok) throw new Error(j.error || 'No se pudo listar');

    const tb = document.getElementById('tb');
    tb.innerHTML = '';

    (j.rows || []).forEach(r=>{
      const dot = (parseInt(r.Activo||0)===1) ? 'background:#1bb34a;' : 'background:#b9b9b9;';
      tb.insertAdjacentHTML('beforeend', `
        <tr>
          <td><button class="btn btn-outline-primary ap-btn" onclick="openPromo(${r.id})">Ver</button></td>
          <td class="text-center"><span class="badge-dot" style="${dot}"></span></td>
          <td class="text-end">${r.id}</td>
          <td>${escapeHtml(r.clave || r.cve_gpoart || '')}</td>
          <td>${escapeHtml(r.descripcion || r.des_gpoart || '')}</td>
          <td class="text-center">${escapeHtml(r.fecha_inicio || '')}</td>
          <td class="text-center">${escapeHtml(r.fecha_fin || '')}</td>
          <td class="text-end">${r.total_rules || 0}</td>
          <td class="text-end">${r.total_scope || 0}</td>
        </tr>
      `);
    });

    dbgPush('Promos=' + (j.rows||[]).length);
  }catch(e){
    toggleDebug();
    alert('Error al listar.\n\n' + e.message + '\n\nRevisa Debug.');
  }
}

function openPromo(id=null){
  document.getElementById('p_id').value = '';
  document.getElementById('p_cve').value = '';
  document.getElementById('p_desc').value = '';
  document.getElementById('p_activo').value = '1';
  document.getElementById('p_caduca').value = '0';
  document.getElementById('p_fi').value = '';
  document.getElementById('p_ff').value = '';
  document.getElementById('p_tipo').value = '';
  document.getElementById('p_id_almacen').value = document.getElementById('almacen_id').value;

  if(id){
    loadPromo(id);
  }else{
    mdlPromo.show();
  }
}

async function loadPromo(id){
  try{
    const j = await apiGet({action:'get', id});
    if(!j.ok) throw new Error(j.error || 'No se pudo obtener');

    const p = j.promo || {};
    document.getElementById('p_id').value = p.id || id;
    document.getElementById('p_id_almacen').value = p.id_almacen || document.getElementById('almacen_id').value;

    document.getElementById('p_cve').value = p.cve_gpoart || p.clave || '';
    document.getElementById('p_desc').value = p.des_gpoart || p.descripcion || '';
    document.getElementById('p_activo').value = (parseInt(p.Activo||0)===1 ? '1' : '0');

    document.getElementById('p_caduca').value = String(p.Caduca ?? p.caduca ?? 0);
    document.getElementById('p_fi').value = (p.FechaI || p.fecha_inicio || '').slice(0,10);
    document.getElementById('p_ff').value = (p.FechaF || p.fecha_fin || '').slice(0,10);
    document.getElementById('p_tipo').value = p.Tipo || p.tipo || '';

    mdlPromo.show();
  }catch(e){
    toggleDebug();
    alert('Error al abrir.\n\n' + e.message + '\n\nRevisa Debug.');
  }
}

async function savePromo(){
  try{
    const payload = {
      action:'save',
      id: document.getElementById('p_id').value,
      id_almacen: document.getElementById('p_id_almacen').value,
      cve_gpoart: document.getElementById('p_cve').value.trim(),
      des_gpoart: document.getElementById('p_desc').value.trim(),
      Activo: document.getElementById('p_activo').value,
      Caduca: document.getElementById('p_caduca').value,
      FechaI: document.getElementById('p_fi').value,
      FechaF: document.getElementById('p_ff').value,
      Tipo: document.getElementById('p_tipo').value.trim(),
    };

    const j = await apiPost(payload);
    if(!j.ok) throw new Error((j.error||'Error al guardar') + (j.detalle?("\n"+j.detalle):""));

    await loadPromos();
    alert('Guardado OK');
    mdlPromo.hide();
  }catch(e){
    toggleDebug();
    alert('Error al guardar.\n\n' + e.message + '\n\nRevisa Debug.');
  }
}

function escapeHtml(s){
  return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
</script>

<?php
if (file_exists($menuEnd)) require_once $menuEnd;
?>
