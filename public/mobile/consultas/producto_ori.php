<?php
// public/mobile/consultas/producto.php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Producto · AssistPro ER</title>
  <link rel="stylesheet" href="../css/rf.css?v=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Mantener look & feel */
    .wrap{max-width:420px;margin:18px auto;padding:0 12px}
    .card{background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.10);overflow:hidden}
    .hdr{display:flex;align-items:center;gap:10px;padding:16px 16px 8px}
    .logoMini{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#0b4;color:#fff;font-weight:900}
    .ttl{font-size:16px;font-weight:900;margin:0;line-height:1.1}
    .sub{font-size:12px;color:#667;margin:2px 0 0}
    .badge{margin-left:auto;font-size:12px;font-weight:800;background:#eef2ff;color:#1e3a8a;padding:6px 10px;border-radius:999px}
    .body{padding:0 16px 16px}
    .tabs{display:flex;gap:8px;margin:10px 0 10px}
    .tab{flex:1;border:1px solid #e7eaf1;border-radius:12px;padding:10px 10px;font-weight:900;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
    .tab.on{background:#0b1220;color:#fff;border-color:#0b1220}
    .inpRow{display:flex;gap:10px;align-items:center;margin-top:10px}
    .inpIcon{width:36px;height:36px;border-radius:12px;background:#f2f4f7;display:grid;place-items:center;font-weight:900}
    input[type="text"]{flex:1;width:100%;padding:12px 12px;border:1px solid #dde0e6;border-radius:12px;outline:none;font-size:14px}
    .hint{font-size:12px;color:#667;margin:8px 2px 0}
    .msg{margin-top:10px;padding:10px 12px;border-radius:12px;font-weight:800;font-size:12px;display:none}
    .msg.err{background:#ffe9ea;border:1px solid #ffc2c6;color:#991b1b}
    .list{margin-top:10px;border:1px solid #e7eaf1;border-radius:12px;overflow:hidden;display:none}
    .item{padding:10px 12px;border-bottom:1px solid #eef1f6;cursor:pointer}
    .item:last-child{border-bottom:none}
    .item b{display:block;font-size:13px}
    .item small{display:block;font-size:12px;color:#667;margin-top:2px}
    .sel{display:none;margin-top:10px;background:#0b1220;color:#fff;border-radius:14px;padding:12px}
    .sel .sku{font-weight:900}
    .sel .des{font-size:12px;opacity:.85;margin-top:2px}
    .kpis{display:none;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}
    .kpi{background:#0b1220;color:#fff;border-radius:14px;padding:10px 12px}
    .kpi .n{font-size:18px;font-weight:900}
    .kpi .t{font-size:11px;opacity:.8;margin-top:2px}
    .tbl{display:none;margin-top:12px;border:1px solid #e7eaf1;border-radius:14px;overflow:hidden}
    .tblHead,.tblRow{display:grid;grid-template-columns:1.2fr 1.4fr 1fr .8fr .8fr;gap:8px;padding:10px 10px}
    .tblHead{background:#f7f9fc;font-size:11px;font-weight:900;color:#445}
    .tblRow{font-size:12px;border-top:1px solid #eef1f6;align-items:start}
    .muted{color:#64748b}
    .click{cursor:pointer}
    .btns{display:flex;gap:10px;margin-top:14px}
    .btn{flex:1;border:0;border-radius:14px;padding:12px 12px;font-weight:900;cursor:pointer}
    .btnDark{background:#111827;color:#fff}
    .btnBlue{background:#1d4ed8;color:#fff}
    .ft{text-align:center;font-size:11px;color:#667;padding:10px 0 14px}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="hdr">
      <div class="logoMini">ER</div>
      <div>
        <p class="ttl" id="titleTop">Producto</p>
        <p class="sub" id="subTop">Consulta por clave o descripción</p>
      </div>
      <div class="badge" id="almBadge">ALM: —</div>
    </div>

    <div class="body">

      <div class="tabs">
        <button class="tab on" id="tabProd"><i class="fa-solid fa-box"></i> Producto</button>
        <button class="tab" id="tabBL"><i class="fa-solid fa-location-dot"></i> BL</button>
        <button class="tab" id="tabLP"><i class="fa-solid fa-tag"></i> LP/Cont</button>
      </div>

      <div class="inpRow">
        <div class="inpIcon"><i class="fa-solid fa-barcode"></i></div>
        <input type="text" id="q" placeholder="Buscar (2+ caracteres)" autocomplete="off" />
      </div>
      <div class="hint" id="hintTxt">Coincidencias por <b>clave</b> y <b>descripción</b>. Enter selecciona la primera coincidencia.</div>

      <div class="msg err" id="msg"></div>

      <div class="list" id="matches"></div>

      <div class="sel" id="selBox">
        <div class="sku" id="selSku">—</div>
        <div class="des" id="selDes">—</div>
        <div style="margin-top:8px;">
          <span style="font-size:11px;background:rgba(255,255,255,.12);padding:4px 8px;border-radius:999px;font-weight:900" id="selTag">PRODUCTO</span>
        </div>
      </div>

      <div class="kpis" id="kpis">
        <div class="kpi">
          <div class="n" id="kTot">0</div>
          <div class="t">Total</div>
        </div>
        <div class="kpi">
          <div class="n" id="kUbi">0</div>
          <div class="t">Ubicaciones</div>
        </div>
      </div>

      <div class="tbl" id="tbl">
        <div class="tblHead">
          <div>Ubicación (BL)</div>
          <div>LP/Cont</div>
          <div>Lote</div>
          <div>Cad</div>
          <div>Cant</div>
        </div>
        <div id="rows"></div>
      </div>

      <div class="btns">
        <button class="btn btnDark" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Volver</button>
        <button class="btn btnBlue" onclick="location.href='../index.html'"><i class="fa-solid fa-house"></i> Menú</button>
      </div>

      <div class="ft">Powered by <b>Adventech Logística</b></div>
    </div>
  </div>
</div>

<script>
/**
 * RUTAS:
 * - Artículos: catálogo (NO tocar, es el que ya funciona)
 * - Stock: existencias_ubicacion_total (API que trae KPIs + rows)
 */
const API_ART = '../../api/articulos_api.php';
const API_STK = '../../api/stock/existencias_ubicacion_total.php';

let mode = "producto";   // producto | bl | lp
let debounceT = null;
let lastQuery = '';
let selected = null;

// badge almacén desde localStorage
try{
  const alm = localStorage.getItem('mobile_almacen') || localStorage.getItem('alm_clave') || '';
  if(alm) document.getElementById('almBadge').textContent = 'ALM: ' + alm;
}catch(e){}

function setMode(m){
  mode = m;

  document.getElementById('tabProd').classList.toggle('on', m==='producto');
  document.getElementById('tabBL').classList.toggle('on', m==='bl');
  document.getElementById('tabLP').classList.toggle('on', m==='lp');

  if(m==='producto'){
    document.getElementById('titleTop').textContent = 'Producto';
    document.getElementById('subTop').textContent = 'Consulta por clave o descripción';
    document.getElementById('hintTxt').innerHTML = 'Coincidencias por <b>clave</b> y <b>descripción</b>. Enter selecciona la primera coincidencia.';
    document.getElementById('q').placeholder = 'Buscar (2+ caracteres)';
  }else if(m==='bl'){
    document.getElementById('titleTop').textContent = 'BL';
    document.getElementById('subTop').textContent = 'Consulta por BL (CódigoCSD/Ubicación)';
    document.getElementById('hintTxt').innerHTML = 'Escribe un BL (<b>CódigoCSD</b>). Enter selecciona la primera coincidencia.';
    document.getElementById('q').placeholder = 'Buscar BL (2+ caracteres)';
  }else{
    document.getElementById('titleTop').textContent = 'LP/Cont';
    document.getElementById('subTop').textContent = 'Consulta por License Plate / Contenedor';
    document.getElementById('hintTxt').innerHTML = 'Escribe un LP/Contenedor. Enter selecciona la primera coincidencia.';
    document.getElementById('q').placeholder = 'Buscar LP/Cont (2+ caracteres)';
  }

  document.getElementById('q').value = '';
  selected = null;
  clearMsg();
  hideMatches();
  hideSelection();
  hideResult();
}

document.getElementById('tabProd').addEventListener('click', ()=>setMode('producto'));
document.getElementById('tabBL').addEventListener('click', ()=>setMode('bl'));
document.getElementById('tabLP').addEventListener('click', ()=>setMode('lp'));

function showMsg(t){
  const el = document.getElementById('msg');
  el.textContent = t;
  el.style.display = 'block';
}
function clearMsg(){ document.getElementById('msg').style.display = 'none'; }
function showMatches(html){
  const el = document.getElementById('matches');
  el.innerHTML = html;
  el.style.display = 'block';
}
function hideMatches(){ document.getElementById('matches').style.display = 'none'; }
function showSelection(){
  document.getElementById('selBox').style.display='block';
  document.getElementById('selSku').textContent = selected?.key || '—';
  document.getElementById('selDes').textContent = selected?.desc || '';
  document.getElementById('selTag').textContent = selected?.tag || '—';
}
function hideSelection(){ document.getElementById('selBox').style.display='none'; }
function showResult(){
  document.getElementById('kpis').style.display='grid';
  document.getElementById('tbl').style.display='block';
}
function hideResult(){
  document.getElementById('kpis').style.display='none';
  document.getElementById('tbl').style.display='none';
  document.getElementById('rows').innerHTML='';
}

function escapeHtml(s){
  return String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}
function pick(row, keys, def=""){
  for(const k of keys){
    if(row && row[k] !== undefined && row[k] !== null && String(row[k]).trim() !== ""){
      return String(row[k]).trim();
    }
  }
  return def;
}
function normalizeRows(j){
  if(Array.isArray(j)) return j;
  if(j && Array.isArray(j.rows)) return j.rows;
  if(j && Array.isArray(j.data)) return j.data;
  if(j && j.data && Array.isArray(j.data.rows)) return j.data.rows;
  if(j && Array.isArray(j.result)) return j.result;
  if(j && j.data && Array.isArray(j.data.data)) return j.data.data;
  return [];
}
async function fetchJson(url){
  const r = await fetch(url, {cache:'no-store'});
  const txt = await r.text();
  try{ return JSON.parse(txt); }catch(e){ return { ok:false, _raw: txt.substring(0,220) }; }
}

// input behavior
const elQ = document.getElementById('q');
elQ.addEventListener('input', ()=>{
  const v = elQ.value.trim();
  if(v.length < 2){ clearMsg(); hideMatches(); return; }
  clearTimeout(debounceT);
  debounceT = setTimeout(()=> searchMatches(v), 220);
});
elQ.addEventListener('keydown', (e)=>{
  if(e.key === 'Enter'){
    e.preventDefault();
    const first = document.querySelector('#matches .item');
    if(first) first.click();
  }
});

// --- search matches (NO tocar lógica de producto) ---
async function searchMatches(q){
  if(q === lastQuery) return;
  lastQuery = q;
  clearMsg();
  hideResult();
  hideSelection();

  if(mode === "producto"){
    const url = `${API_ART}?action=list&q=${encodeURIComponent(q)}&limit=15`;
    const j = await fetchJson(url);

    if(j && j.error){
      showMsg("No pude buscar coincidencias: " + j.error);
      hideMatches();
      return;
    }

    const rows = normalizeRows(j);
    const list = rows.slice(0, 12).map(r=>({
      key: pick(r, ['cve_articulo','sku','articulo'], ''),
      desc: pick(r, ['des_articulo','descripcion','des_detallada','nombre'], '')
    })).filter(x=>x.key);

    if(!list.length){
      showMsg("Sin coincidencias.");
      hideMatches();
      return;
    }

    renderMatchList(list, "PRODUCTO");
    return;
  }

  // BL / LP: aquí puedes mantener tus match si ya tienes endpoint “match”
  showMsg("Para BL/LP usa selección directa (no se cambió este flujo).");
  hideMatches();
}

function renderMatchList(list, tag){
  const html = list.map(it=>`
    <div class="item" data-key="${escapeHtml(it.key)}" data-desc="${escapeHtml(it.desc||'')}">
      <b>${escapeHtml(it.key)}</b>
      <small>${escapeHtml(it.desc||'')}</small>
    </div>
  `).join('');
  showMatches(html);

  document.querySelectorAll('#matches .item').forEach(el=>{
    el.addEventListener('click', ()=>{
      const key = el.getAttribute('data-key') || '';
      const desc= el.getAttribute('data-desc') || '';
      selectKey(key, desc, tag);
    });
  });
}

async function selectKey(key, desc, tag){
  selected = { key, desc, tag: tag };
  hideMatches();
  clearMsg();
  showSelection();
  await loadDetail();
}

// --- detalle (AHORA usa existencia_total + CveLP) ---
async function loadDetail(){
  const alm = localStorage.getItem('mobile_almacen') || '';
  const key = selected.key;

  // Producto: este endpoint es el bueno
  const url = `${API_STK}?cve_articulo=${encodeURIComponent(key)}&almacen=${encodeURIComponent(alm)}&limit=500`;
  const json = await fetchJson(url);

  if(json && json.ok === 0){
    showMsg(json.error || "No se pudo consultar existencias.");
    hideResult();
    return;
  }

  const rows = normalizeRows(json);
  if(!rows.length){
    showMsg("No se encontró detalle para: " + key);
    hideResult();
    return;
  }

  const box = document.getElementById('rows');
  box.innerHTML = '';

  // KPI total: usa el KPI del API si viene, si no recalcula
  let total = 0;
  if(json && json.kpis && json.kpis.existencia_total !== undefined){
    total = parseFloat(String(json.kpis.existencia_total).replaceAll(',','')) || 0;
  }else{
    rows.forEach(r=>{
      const cantS = pick(r, ['existencia_total','cantidad','existencia','qty','cant','total'], '0');
      const cant = parseFloat(String(cantS).replaceAll(',','')) || 0;
      total += cant;
    });
  }

  const ubis = new Set();

  rows.forEach(r=>{
    // BL: mostrar CodigoCSD (ya lo traes en "bl")
    const ub   = pick(r, ['bl','CodigoCSD','codigo_csd','ubicacion','cve_ubic'], '—');

    // LP/Cont: mostrar CveLP (clave visible)
    const lp   = pick(r, ['CveLP','LP','lp','license_plate','contenedor','charola'], '—');

    const lote = pick(r, ['cve_lote','lote','lot','batch'], '—');
    const cad  = pick(r, ['Caducidad','caducidad','fecha_caducidad','cad'], '—');

    const cantS= pick(r, ['existencia_total','cantidad','existencia','qty','cant','total'], '0');
    const cant = parseFloat(String(cantS).replaceAll(',','')) || 0;

    if(ub && ub !== '—') ubis.add(ub);

    const row = document.createElement('div');
    row.className = 'tblRow';
    row.innerHTML = `
      <div><b>${escapeHtml(ub)}</b></div>
      <div class="muted">${escapeHtml(lp)}</div>
      <div class="muted">${escapeHtml(lote)}</div>
      <div class="muted">${escapeHtml(cad)}</div>
      <div><b>${cant.toLocaleString()}</b></div>
    `;
    box.appendChild(row);
  });

  document.getElementById('kTot').textContent = total.toLocaleString();
  document.getElementById('kUbi').textContent = ubis.size.toLocaleString();
  showResult();
}

// init
setMode('producto');
</script>
</body>
</html>
