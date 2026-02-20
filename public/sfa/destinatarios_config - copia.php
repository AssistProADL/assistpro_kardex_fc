<?php
// public/sfa/destinatarios_config.php
require_once __DIR__ . '/../bi/_menu_global.php';

// Ajusta el path si tu carpeta cambia
$API_URL = '/assistpro_kardex_fc/public/api/sfa/destinatarios_config_api.php';
?>

<style>
  /* Compacto: 10px global */
  .sfa-10, .sfa-10 * { font-size: 10px !important; }

  .sfa-wrap { padding: 12px 14px; }
  .sfa-title { font-weight: 700; font-size: 16px !important; margin: 0 0 2px; }
  .sfa-sub { color: #6b7280; margin: 0 0 12px; }

  .sfa-topbar { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
  .sfa-filters { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; flex: 1; }
  .sfa-field { display:flex; flex-direction:column; gap:4px; min-width: 220px; }
  .sfa-field label { color:#6b7280; font-weight:600; }
  .sfa-field select, .sfa-field input {
    height: 32px; padding: 6px 8px; border:1px solid #e5e7eb; border-radius:10px;
    outline:none;
  }
  .sfa-actions { display:flex; gap:8px; align-items:center; }
  .sfa-btn {
    height: 32px; padding: 0 12px; border-radius:10px; border:1px solid #0b2aa0;
    background:#0b2aa0; color:#fff; font-weight:700; cursor:pointer;
  }
  .sfa-btn.secondary { background:#fff; color:#0b2aa0; }
  .sfa-btn:disabled { opacity:.55; cursor:not-allowed; }

  .sfa-cards { display:grid; grid-template-columns: repeat(5, minmax(170px, 1fr)); gap:10px; margin-top: 12px; }
  @media (max-width: 1200px){ .sfa-cards{ grid-template-columns: repeat(2, minmax(170px, 1fr)); } }
  .sfa-card {
    border:1px solid #e5e7eb; border-radius:14px; padding:10px 12px; background:#fff;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    display:flex; flex-direction:column; gap:6px;
  }
  .sfa-card .k { color:#6b7280; font-weight:700; }
  .sfa-card .v { font-size: 18px !important; font-weight:800; text-align:center; }
  .sfa-card .b { color:#6b7280; text-align:center; }

  .sfa-tablewrap {
    margin-top: 12px; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff;
  }
  .sfa-tablehead {
    padding:10px 12px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; gap:10px; align-items:center;
  }
  .sfa-tablehead .meta { color:#6b7280; font-weight:600; }
  .sfa-gridwrap { max-height: 62vh; overflow:auto; }
  table.sfa-grid { border-collapse: collapse; width: max(1200px, 100%); }
  table.sfa-grid th, table.sfa-grid td {
    border-bottom:1px solid #f1f5f9; padding: 4px 6px; vertical-align: middle;
    line-height: 1.1; /* sin doble espacio */
    white-space: nowrap;
  }
  table.sfa-grid th {
    position: sticky; top: 0; z-index: 2;
    background: #f8fafc; border-bottom:1px solid #e5e7eb;
    font-weight: 800; color:#111827;
  }
  .td-razon { white-space: normal; min-width: 220px; }
  .td-rutas { white-space: normal; min-width: 200px; color:#374151; }
  .td-actions { min-width: 170px; }
  .row-select { width: 14px; height: 14px; vertical-align: middle; }

  .sel {
    height: 28px; padding: 4px 6px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;
    min-width: 140px;
  }

  .mini-btn {
    height: 28px; padding: 0 10px; border-radius:10px; border:1px solid #10b981;
    background:#10b981; color:#fff; font-weight:800; cursor:pointer;
  }
  .mini-btn.gray { border-color:#94a3b8; background:#fff; color:#0f172a; }

  /* Toast */
  .toast {
    position: fixed; right: 16px; bottom: 16px; z-index: 9999;
    background: #0f172a; color:#fff; padding:10px 12px; border-radius:12px;
    box-shadow: 0 10px 20px rgba(0,0,0,.18);
    display:none;
  }
  .toast.ok { background:#065f46; }
  .toast.err { background:#7f1d1d; }
</style>

<div class="sfa-10 sfa-wrap">
  <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
    <div>
      <h1 class="sfa-title">Asignación de Listas por Destinatario</h1>
      <p class="sfa-sub">Gobierno comercial: precios, descuentos y promociones a nivel cliente (legacy compatible).</p>
    </div>
    <div class="sfa-actions">
      <button class="sfa-btn secondary" id="btnBulk" disabled>Guardar seleccionados</button>
      <button class="sfa-btn" id="btnRefresh">Actualizar</button>
    </div>
  </div>

  <div class="sfa-topbar">
    <div class="sfa-filters">
      <div class="sfa-field">
        <label>Empresa</label>
        <select id="empresa"></select>
      </div>
      <div class="sfa-field">
        <label>Almacén</label>
        <select id="almacen"></select>
      </div>
      <div class="sfa-field">
        <label>Ruta</label>
        <select id="ruta">
          <option value="">Todas</option>
        </select>
      </div>
    </div>

    <div class="sfa-field" style="min-width: 320px;">
      <label>Buscar</label>
      <input id="q" placeholder="Buscar (razón social, clave, Cve_Clte, id)..." />
    </div>
  </div>

  <div class="sfa-cards" id="cards">
    <div class="sfa-card"><div class="k">Destinatarios</div><div class="v" id="c_total">0</div><div class="b">Base filtrada</div></div>
    <div class="sfa-card"><div class="k">Con Lista Precios</div><div class="v" id="c_lp">0</div><div class="b">ListaP</div></div>
    <div class="sfa-card"><div class="k">Con Lista Descuentos</div><div class="v" id="c_ld">0</div><div class="b">ListaD</div></div>
    <div class="sfa-card"><div class="k">Con Promoción</div><div class="v" id="c_pr">0</div><div class="b">ListaPromo</div></div>
    <div class="sfa-card"><div class="k">Con Día Visita</div><div class="v" id="c_dv">0</div><div class="b">DiaVisita</div></div>
  </div>

  <div class="sfa-tablewrap">
    <div class="sfa-tablehead">
      <div style="display:flex; gap:12px; align-items:center;">
        <div class="meta" id="meta">Vista operativa: empresa=— | almacén=— | ruta=— | búsqueda=—</div>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <label style="display:flex; gap:6px; align-items:center; font-weight:800; color:#111827;">
          <input type="checkbox" id="chkAll" class="row-select" />
          Seleccionar todo
        </label>
      </div>
    </div>

    <div class="sfa-gridwrap">
      <table class="sfa-grid">
        <thead>
          <tr>
            <th style="left:0;">Acción</th>
            <th>ID</th>
            <th>Clave</th>
            <th>Cve_Clte</th>
            <th class="td-razon">Razón Social</th>
            <th class="td-rutas">Ruta(s)</th>
            <th>Lista Precios</th>
            <th>Lista Descuentos</th>
            <th>Lista Promociones</th>
            <th>Día Visita</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="10" style="padding:12px; color:#6b7280;">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const API = <?= json_encode($API_URL) ?>;

const $empresa = document.getElementById('empresa');
const $almacen = document.getElementById('almacen');
const $ruta    = document.getElementById('ruta');
const $q       = document.getElementById('q');
const $tbody   = document.getElementById('tbody');
const $meta    = document.getElementById('meta');
const $chkAll  = document.getElementById('chkAll');
const $btnRefresh = document.getElementById('btnRefresh');
const $btnBulk = document.getElementById('btnBulk');

let LISTAS = {P:[], D:[], PROMO:[]};
let GRID_ROWS = [];
let debounceT = null;

function toast(msg, ok=true){
  const el = document.getElementById('toast');
  el.className = 'toast ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(()=>{ el.style.display='none'; }, 2200);
}

async function apiGet(params){
  const u = new URL(API, window.location.origin);
  Object.entries(params).forEach(([k,v])=> u.searchParams.set(k, v ?? ''));
  const r = await fetch(u.toString(), { credentials:'same-origin' });
  return r.json();
}

async function apiPost(action, body){
  const fd = new FormData();
  fd.append('action', action);
  Object.entries(body||{}).forEach(([k,v])=> fd.append(k, v ?? ''));
  const r = await fetch(API + '?action=' + encodeURIComponent(action), {
    method:'POST', body: fd, credentials:'same-origin'
  });
  return r.json();
}

function optHTML(list, placeholder='—'){
  let h = `<option value="">${placeholder}</option>`;
  for(const it of list){
    h += `<option value="${it.id}">${escapeHtml(it.nombre)}</option>`;
  }
  return h;
}
function escapeHtml(s){
  return (s??'').toString()
    .replaceAll('&','&amp;').replaceAll('<','&lt;')
    .replaceAll('>','&gt;').replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');
}

async function loadOptions(){
  const empresa = $empresa.value || '';
  const almacen = $almacen.value || '';
  const res = await apiGet({ action:'options', empresa, almacen });
  if(!res.ok){ toast(res.msg||'Error options', false); return; }

  // Empresas
  $empresa.innerHTML = '';
  for(const e of res.empresas||[]){
    const op = document.createElement('option');
    op.value = e.id;
    op.textContent = e.nombre;
    $empresa.appendChild(op);
  }
  if(res.defaults?.empresa) $empresa.value = res.defaults.empresa;

  // Almacenes
  $almacen.innerHTML = '';
  for(const a of res.almacenes||[]){
    const op = document.createElement('option');
    op.value = a.id;
    op.textContent = a.nombre;
    $almacen.appendChild(op);
  }
  if(res.defaults?.almacen) $almacen.value = res.defaults.almacen;

  // Rutas (siempre mostramos todas; filtro opcional)
  const keepRuta = $ruta.value;
  $ruta.innerHTML = `<option value="">Todas</option>`;
  for(const r of res.rutas||[]){
    const op = document.createElement('option');
    op.value = r.id;
    op.textContent = r.nombre;
    $ruta.appendChild(op);
  }
  if(keepRuta) $ruta.value = keepRuta;

  LISTAS = res.listas || LISTAS;
}

function updateCards(cards){
  document.getElementById('c_total').textContent = cards?.destinatarios ?? 0;
  document.getElementById('c_lp').textContent = cards?.con_listaP ?? 0;
  document.getElementById('c_ld').textContent = cards?.con_listaD ?? 0;
  document.getElementById('c_pr').textContent = cards?.con_promo ?? 0;
  document.getElementById('c_dv').textContent = cards?.con_dia ?? 0;
}

function rowTemplate(r){
  const lp = Number(r.ListaP||0) || '';
  const ld = Number(r.ListaD||0) || '';
  const pr = Number(r.ListaPromo||0) || '';
  const dv = (r.DiaVisita ?? '') !== null ? (r.DiaVisita ?? '') : '';

  const selP = renderSelect('P', r.id_destinatario, lp);
  const selD = renderSelect('D', r.id_destinatario, ld);
  const selR = renderSelect('PROMO', r.id_destinatario, pr);
  const selDV = renderDia('DV', r.id_destinatario, dv);

  return `
    <tr data-id="${r.id_destinatario}">
      <td class="td-actions">
        <div style="display:flex; gap:8px; align-items:center;">
          <input type="checkbox" class="row-select chkRow" />
          <button class="mini-btn" onclick="saveRow(${r.id_destinatario})">Guardar</button>
        </div>
      </td>
      <td>${escapeHtml(r.id_destinatario)}</td>
      <td>${escapeHtml(r.clave_destinatario||'')}</td>
      <td>${escapeHtml(r.Cve_Clte||'')}</td>
      <td class="td-razon">${escapeHtml(r.razonsocial||'')}</td>
      <td class="td-rutas">${escapeHtml(r.rutas||'—')}</td>
      <td>${selP}</td>
      <td>${selD}</td>
      <td>${selR}</td>
      <td>${selDV}</td>
    </tr>
  `;
}

function renderSelect(tipo, id, val){
  const list = LISTAS[tipo] || [];
  let h = `<select class="sel" data-tipo="${tipo}" data-id="${id}"><option value="">—</option>`;
  for(const it of list){
    const selected = (String(it.id) === String(val)) ? 'selected' : '';
    h += `<option value="${it.id}" ${selected}>${escapeHtml(it.nombre)}</option>`;
  }
  h += `</select>`;
  return h;
}

function renderDia(tipo, id, val){
  let h = `<select class="sel" data-tipo="DV" data-id="${id}"><option value="">—</option>`;
  // 1..7 (ajusta si tu legacy usa otro catálogo)
  const dias = [
    {id:1,n:'Lun'},{id:2,n:'Mar'},{id:3,n:'Mié'},{id:4,n:'Jue'},{id:5,n:'Vie'},{id:6,n:'Sáb'},{id:7,n:'Dom'},
  ];
  for(const d of dias){
    const selected = (String(d.id) === String(val)) ? 'selected' : '';
    h += `<option value="${d.id}" ${selected}>${d.n}</option>`;
  }
  h += `</select>`;
  return h;
}

async function loadGrid(){
  const empresa = $empresa.value || '';
  const almacen = $almacen.value || '';
  const ruta    = $ruta.value || '';
  const q       = $q.value || '';

  $meta.textContent = `Vista operativa: empresa=${empresa||'—'} | almacén=${almacen||'—'} | ruta=${ruta||'todas'} | búsqueda="${q}"`;
  $tbody.innerHTML = `<tr><td colspan="10" style="padding:12px; color:#6b7280;">Cargando...</td></tr>`;
  $chkAll.checked = false;
  $btnBulk.disabled = true;

  const res = await apiGet({ action:'list', empresa, almacen, ruta, q });
  if(!res.ok){ $tbody.innerHTML = `<tr><td colspan="10" style="padding:12px; color:#b91c1c;">${escapeHtml(res.msg||'Error')}</td></tr>`; return; }

  LISTAS = res.listas || LISTAS;
  GRID_ROWS = res.rows || [];
  updateCards(res.cards||{});

  if(GRID_ROWS.length === 0){
    $tbody.innerHTML = `<tr><td colspan="10" style="padding:12px; color:#6b7280;">Sin resultados.</td></tr>`;
    return;
  }

  $tbody.innerHTML = GRID_ROWS.map(rowTemplate).join('');

  hookRowCheckboxes();
}

function hookRowCheckboxes(){
  const chks = document.querySelectorAll('.chkRow');
  chks.forEach(c => c.addEventListener('change', ()=>{
    const any = Array.from(document.querySelectorAll('.chkRow')).some(x=>x.checked);
    $btnBulk.disabled = !any;
  }));
}

async function saveRow(id){
  const tr = document.querySelector(`tr[data-id="${id}"]`);
  if(!tr) return;

  const listap = tr.querySelector(`select[data-tipo="P"][data-id="${id}"]`)?.value ?? '';
  const listad = tr.querySelector(`select[data-tipo="D"][data-id="${id}"]`)?.value ?? '';
  const listapromo = tr.querySelector(`select[data-tipo="PROMO"][data-id="${id}"]`)?.value ?? '';
  const diavisita = tr.querySelector(`select[data-tipo="DV"][data-id="${id}"]`)?.value ?? '';

  const res = await apiPost('save', { id_destinatario:id, listap, listad, listapromo, diavisita });
  if(res.ok){
    toast(res.msg || 'Guardado');
    // refresca cards sin perder contexto
    await loadGrid();
  }else{
    toast(res.msg || 'Error al guardar', false);
  }
}

async function bulkSave(){
  const trs = Array.from(document.querySelectorAll('tbody tr[data-id]'));
  const items = [];
  for(const tr of trs){
    const chk = tr.querySelector('.chkRow');
    if(!chk || !chk.checked) continue;
    const id = tr.getAttribute('data-id');
    items.push({
      id_destinatario: id,
      listap: tr.querySelector(`select[data-tipo="P"][data-id="${id}"]`)?.value ?? '',
      listad: tr.querySelector(`select[data-tipo="D"][data-id="${id}"]`)?.value ?? '',
      listapromo: tr.querySelector(`select[data-tipo="PROMO"][data-id="${id}"]`)?.value ?? '',
      diavisita: tr.querySelector(`select[data-tipo="DV"][data-id="${id}"]`)?.value ?? ''
    });
  }
  if(items.length === 0){ toast('No hay seleccionados', false); return; }

  const res = await apiPost('bulk_save', { items: JSON.stringify(items) });
  if(res.ok){
    toast(res.msg || 'Guardado masivo');
    await loadGrid();
  }else{
    toast(res.msg || 'Error masivo', false);
  }
}

$chkAll.addEventListener('change', ()=>{
  const v = $chkAll.checked;
  document.querySelectorAll('.chkRow').forEach(c=> c.checked = v);
  $btnBulk.disabled = !v;
});

$btnRefresh.addEventListener('click', async ()=>{
  await loadOptions();
  await loadGrid();
});

$btnBulk.addEventListener('click', bulkSave);

$empresa.addEventListener('change', async ()=>{
  await loadOptions();
  await loadGrid();
});

$almacen.addEventListener('change', async ()=>{
  await loadOptions();
  await loadGrid();
});

$ruta.addEventListener('change', loadGrid);

$q.addEventListener('input', ()=>{
  clearTimeout(debounceT);
  debounceT = setTimeout(loadGrid, 280);
});

// INIT
(async function init(){
  await loadOptions();
  await loadGrid();
})();
</script>
