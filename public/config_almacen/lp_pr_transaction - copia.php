<?php
// public/config_almacen/lp_pr_transaction.php
require_once __DIR__ . '/../bi/_menu_global.php'; // el menú/estilo base AssistPro
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LP - Transacciones (Operación) · Traslado por LP / Contenedor</title>

  <style>
    :root{
      --adl-primary:#0A2A5E;
      --adl-primary-2:#123A7A;
      --adl-bg:#F3F6FB;
      --adl-card:#FFFFFF;
      --adl-border:#D9E2F1;
      --adl-text:#1C2B3A;
      --adl-muted:#6B7A90;
      --adl-success:#1B9E58;
      --adl-danger:#C0392B;
      --adl-shadow:0 10px 22px rgba(12,25,45,.12);
      --r:14px;
      --fs:10px; /* AssistPro compact */
    }
    body{font-size:var(--fs); background:var(--adl-bg); color:var(--adl-text);}
    .wrap{padding:14px 16px 24px 16px; max-width:1400px;}
    .title{font-size:14px; font-weight:800; margin:6px 0 2px;}
    .subtitle{color:var(--adl-muted); margin-bottom:10px;}
    .card{
      background:var(--adl-card);
      border:1px solid var(--adl-border);
      border-radius:var(--r);
      box-shadow:var(--adl-shadow);
      padding:12px;
      margin-bottom:12px;
    }
    .grid{display:grid; gap:12px;}
    .grid-top{grid-template-columns: 1.2fr 1.2fr .9fr;}
    .grid-mid{grid-template-columns: 1.2fr 1.2fr;}
    .row{display:flex; gap:10px; align-items:center; flex-wrap:wrap;}
    label{font-weight:700;}
    input[type="text"], select{
      width:100%;
      padding:9px 10px;
      border:1px solid var(--adl-border);
      border-radius:10px;
      outline:none;
      background:#fff;
      font-size:var(--fs);
    }
    input[type="text"]:focus, select:focus{border-color:#9bb7e6;}
    .btn{
      border:1px solid var(--adl-border);
      background:#fff;
      padding:9px 12px;
      border-radius:12px;
      cursor:pointer;
      font-weight:800;
      font-size:var(--fs);
      user-select:none;
    }
    .btn-primary{background:var(--adl-primary); border-color:var(--adl-primary); color:#fff;}
    .btn-success{background:var(--adl-success); border-color:var(--adl-success); color:#fff;}
    .btn-muted{background:#EEF2F8;}
    .pill{
      display:inline-flex; align-items:center; gap:6px;
      padding:4px 10px; border-radius:999px;
      border:1px solid var(--adl-border);
      background:#fff; font-weight:800;
    }
    .pill-ok{border-color:#bfead3; background:#EAF8F1; color:#0f6b3a;}
    .muted{color:var(--adl-muted);}
    .small{font-size:9px;}
    .kv{display:grid; grid-template-columns: 140px 1fr; gap:6px 12px; margin-top:6px;}
    .kv b{color:var(--adl-primary);}
    .table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      overflow:hidden;
      border:1px solid var(--adl-border);
      border-radius:12px;
    }
    .table thead th{
      background:var(--adl-primary);
      color:#fff;
      text-align:left;
      padding:9px;
      font-size:var(--fs);
      position:sticky; top:0;
      z-index:2;
    }
    .table td{
      padding:8px 9px;
      border-top:1px solid var(--adl-border);
      font-size:var(--fs);
      vertical-align:top;
    }
    .table tr:hover td{background:#F6F9FF;}
    .right-actions{display:flex; gap:8px; justify-content:flex-end; align-items:center;}
    .hint{margin-top:4px; color:var(--adl-muted);}
    .count{font-weight:800; color:var(--adl-primary);}

    /* Typeahead dropdown */
    .ta-wrap{position:relative;}
    .ta-list{
      position:absolute; left:0; right:0; top:38px;
      background:#fff;
      border:1px solid var(--adl-border);
      border-radius:12px;
      box-shadow:var(--adl-shadow);
      max-height:240px;
      overflow:auto;
      display:none;
      z-index:50;
    }
    .ta-item{
      padding:8px 10px;
      cursor:pointer;
      border-top:1px solid var(--adl-border);
      font-size:var(--fs);
    }
    .ta-item:first-child{border-top:none;}
    .ta-item:hover{background:#F6F9FF;}
    .ta-strong{font-weight:900;}
    .ta-meta{color:var(--adl-muted); font-size:9px;}

    /* Modal */
    .modal-mask{
      position:fixed; inset:0;
      background:rgba(0,0,0,.35);
      display:none;
      z-index:200;
      align-items:center;
      justify-content:center;
      padding:16px;
    }
    .modal{
      width:min(980px, 96vw);
      background:#fff;
      border-radius:16px;
      border:1px solid var(--adl-border);
      box-shadow:var(--adl-shadow);
      overflow:hidden;
    }
    .modal-head{
      background:var(--adl-primary);
      color:#fff;
      padding:10px 12px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      font-weight:900;
    }
    .modal-body{padding:10px 12px; max-height:70vh; overflow:auto;}
    .modal-close{
      background:#fff;
      border:0;
      border-radius:10px;
      padding:6px 10px;
      cursor:pointer;
      font-weight:900;
      font-size:var(--fs);
    }
    .log{
      background:#081B3A;
      color:#CFE3FF;
      border-radius:12px;
      padding:10px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size:9px;
      height:120px;
      overflow:auto;
      border:1px solid rgba(255,255,255,.08);
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="title">LP - Transacciones (Operación) · Traslado por LP / Contenedor</div>
    <div class="subtitle">Validación independiente: <b>lookup_lp</b> / <b>lookup_bl</b> · Sin auth/sesión</div>

    <!-- TOP -->
    <div class="card">
      <div class="grid grid-top">
        <!-- LP -->
        <div>
          <div class="row" style="justify-content:space-between;">
            <label>LP origen (CveLP)</label>
            <span class="muted">Coincidencias: <span class="count" id="lpCount">0</span></span>
          </div>
          <div class="ta-wrap">
            <input id="lpInput" type="text" placeholder="Escanea o escribe LP">
            <div id="lpTa" class="ta-list"></div>
          </div>
          <div class="hint small">Typeahead por coincidencia. Botón Buscar abre resultados directos (lookup_lp.php).</div>
          <div style="margin-top:8px;">
            <button class="btn btn-muted" id="btnLpSearch">Buscar LP</button>
          </div>
        </div>

        <!-- BL -->
        <div>
          <div class="row" style="justify-content:space-between;">
            <label>BL destino (CodigoCSD)</label>
            <span class="muted">Coincidencias: <span class="count" id="blCount">0</span></span>
          </div>
          <div class="ta-wrap">
            <input id="blInput" type="text" placeholder="Escanea o escribe BL">
            <div id="blTa" class="ta-list"></div>
          </div>
          <div class="hint small">Lookup independiente (lookup_bl.php). Ideal para validar disponibilidad real del API.</div>

          <div class="row" style="margin-top:8px;">
            <label class="row" style="gap:6px; cursor:pointer; user-select:none;">
              <input id="chkFiltrarAlm" type="checkbox" checked>
              <span>Filtrar BL por almacén del LP</span>
            </label>
            <span class="muted small">· Almacén LP: <b id="lpAlmTag">—</b></span>
          </div>

          <div style="margin-top:8px;">
            <button class="btn btn-muted" id="btnBlSearch">Buscar BL</button>
          </div>
        </div>

        <!-- Acciones -->
        <div>
          <label>Modo destino</label>
          <select id="modoDestino">
            <option value="">Seleccionar...</option>
            <option value="MISMO_BL">Mismo BL (reubicar LP)</option>
            <option value="NUEVO_BL">Nuevo BL (crear ubicación lógica)</option>
          </select>

          <div class="hint small" style="margin-top:6px;">
            Estrategia “Nuevo LP” queda lista para fases siguientes (API lp_tr.php).
          </div>

          <div class="right-actions" style="margin-top:12px;">
            <span class="pill" id="fasePill">Fase: Lookup</span>
            <button class="btn btn-primary" id="btnPreview">Previsualizar</button>
            <button class="btn btn-success" id="btnExec">Ejecutar traslado</button>
            <button class="btn btn-muted" id="btnClear">Limpiar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MID -->
    <div class="grid grid-mid">
      <!-- LP seleccionado -->
      <div class="card">
        <div class="row" style="justify-content:space-between;">
          <b>LP seleccionado</b>
          <span class="pill pill-ok" id="lpActivoPill" style="display:none;">ACTIVO</span>
        </div>

        <div class="kv">
          <span class="muted">CveLP</span><b id="lp_cvelp">—</b>
          <span class="muted">Tipo / Activo</span><b id="lp_tipo">—</b>
          <span class="muted">Almacén</span><b id="lp_alm">—</b>
          <span class="muted">IDContenedor</span><b id="lp_idcont">—</b>
          <span class="muted">Ubicación (idy_ubica)</span><b id="lp_idy">—</b>
          <span class="muted">BL actual (CodigoCSD)</span><b id="lp_bl">—</b>
        </div>

        <div class="hint small" style="margin-top:10px;">
          Derivado del lookup LP (tu evidencia ya muestra que LP responde correctamente).
        </div>
      </div>

      <!-- BL seleccionado -->
      <div class="card">
        <div class="row" style="justify-content:space-between;">
          <b>BL destino seleccionado</b>
          <button class="btn btn-muted" id="btnCollapseBl">-</button>
        </div>

        <div class="kv" id="blInfo">
          <span class="muted">CodigoCSD</span><b id="bl_cod">—</b>
          <span class="muted">Almacén / Mixto</span><b id="bl_alm">—</b>
          <span class="muted">idy_ubica</span><b id="bl_idy">—</b>
          <span class="muted">Existencia BL (pallets / cajas)</span><b id="bl_exist">—</b>
        </div>

        <div style="margin-top:10px;">
          <div class="row" style="justify-content:space-between;">
            <b>Contenido BL destino</b>
            <span class="muted">LPs / cajas</span>
          </div>

          <table class="table" style="margin-top:6px;">
            <thead>
              <tr><th>LP</th><th>Tipo</th><th>Activo</th></tr>
            </thead>
            <tbody id="blContenido">
              <tr><td colspan="3" class="muted">Selecciona un BL para ver su contenido.</td></tr>
            </tbody>
          </table>

          <div class="hint small" style="margin-top:8px;">
            Si el API devuelve <b>contenido</b>, se renderiza aquí. Si devuelve sólo cabecera, no se rompe la UI.
          </div>
        </div>
      </div>
    </div>

    <!-- Resultado -->
    <div class="card">
      <b>Resultado / Kardex</b>
      <div class="hint small">Placeholder para integrar lp_tr.php (init/preview/execute). Aquí quedará el resumen (tx_id, filas movidas, kardex rows).</div>
      <div class="grid" style="grid-template-columns: 1fr 1fr 1fr; margin-top:10px;">
        <input type="text" placeholder="tx_id" disabled>
        <input type="text" placeholder="Destino (CodigoCSD)" disabled>
        <input type="text" placeholder="Kardex rows" disabled>
      </div>
    </div>

    <div class="card">
      <b>Log operativo</b>
      <div class="log" id="log"></div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal-mask" id="modalMask">
    <div class="modal">
      <div class="modal-head">
        <span id="modalTitle">Resultados</span>
        <button class="modal-close" id="modalClose">Cerrar</button>
      </div>
      <div class="modal-body">
        <table class="table">
          <thead id="modalThead"></thead>
          <tbody id="modalTbody"></tbody>
        </table>
        <div class="hint small" id="modalHint" style="margin-top:8px;"></div>
      </div>
    </div>
  </div>

<script>
/** =========================
 *  CONFIG (NO TOCAR APIs)
 *  ========================= */
const API_LP = "/assistpro_kardex_fc/public/api/lp/lookup_lp.php";
const API_BL = "/assistpro_kardex_fc/public/api/lp/lookup_bl.php";

const $ = (id)=>document.getElementById(id);

let selectedLP = null;
let selectedBL = null;

function log(msg){
  const el = $("log");
  const ts = new Date().toLocaleTimeString();
  el.innerHTML += `[${ts}] ${msg}\n`;
  el.scrollTop = el.scrollHeight;
}

function safe(v){ return (v===null || v===undefined || v==="") ? "—" : String(v); }

/** Build URL correctly.
 *  IMPORTANT: q must be ONLY the value, never "q=VALUE"
 */
function buildUrl(base, params){
  const u = new URL(base, window.location.origin);
  Object.entries(params).forEach(([k,v])=>{
    if(v===null || v===undefined || v==="") return;
    u.searchParams.set(k, v);
  });
  return u.toString();
}

async function fetchJson(url){
  const r = await fetch(url, {headers:{'Accept':'application/json'}});
  const t = await r.text();
  try { return JSON.parse(t); } catch(e){
    throw new Error("Respuesta no JSON: " + t.slice(0,200));
  }
}

/** =========================
 *  TYPEAHEAD (LP / BL)
 *  ========================= */
function renderTypeahead(listEl, items, type){
  listEl.innerHTML = "";
  if(!items || !items.length){
    listEl.style.display="none";
    return;
  }
  items.slice(0, 30).forEach((it)=>{
    const div = document.createElement("div");
    div.className="ta-item";
    if(type==="LP"){
      div.innerHTML = `
        <div class="ta-strong">${safe(it.CveLP)}</div>
        <div class="ta-meta">Tipo: ${safe(it.tipo)} · Alm: ${safe(it.cve_almac)} · BL: ${safe(it.BL)} · Activo: ${safe(it.Activo)}</div>
      `;
      div.addEventListener("click", ()=>selectLP(it));
    }else{
      div.innerHTML = `
        <div class="ta-strong">${safe(it.CodigoCSD || it.codigoCSD || it.BL || it.codigocsd)}</div>
        <div class="ta-meta">Alm: ${safe(it.cve_almac)} · Mixto: ${safe(it.mixto)} · idy_ubica: ${safe(it.idy_ubica)} · Activo: ${safe(it.Activo)}</div>
      `;
      div.addEventListener("click", ()=>selectBL(it));
    }
    listEl.appendChild(div);
  });
  listEl.style.display="block";
}

function debounce(fn, ms){
  let t=null;
  return (...args)=>{
    clearTimeout(t);
    t=setTimeout(()=>fn(...args), ms);
  };
}

const onLpInput = debounce(async ()=>{
  const q = $("lpInput").value.trim();
  if(q.length < 3){ $("lpTa").style.display="none"; $("lpCount").textContent="0"; return; }
  const url = buildUrl(API_LP, {q});
  const json = await fetchJson(url);
  const items = (json && json.data) ? json.data : [];
  $("lpCount").textContent = items.length;
  renderTypeahead($("lpTa"), items, "LP");
}, 220);

const onBlInput = debounce(async ()=>{
  const q = $("blInput").value.trim();
  if(q.length < 2){ $("blTa").style.display="none"; $("blCount").textContent="0"; return; }

  // if filter enabled, use selected LP almacen
  let cve_almac = "";
  if($("chkFiltrarAlm").checked && selectedLP && selectedLP.cve_almac){
    cve_almac = selectedLP.cve_almac;
  }

  const url = buildUrl(API_BL, {q, cve_almac}); // ✅ FIX: q is value only
  const json = await fetchJson(url);
  const items = (json && json.data) ? json.data : [];
  $("blCount").textContent = items.length;
  renderTypeahead($("blTa"), items, "BL");
}, 220);

$("lpInput").addEventListener("input", onLpInput);
$("blInput").addEventListener("input", onBlInput);

document.addEventListener("click",(e)=>{
  if(!e.target.closest(".ta-wrap")){
    $("lpTa").style.display="none";
    $("blTa").style.display="none";
  }
});

/** =========================
 *  SELECTION (LP / BL)
 *  ========================= */
function selectLP(it){
  selectedLP = it;
  $("lpInput").value = safe(it.CveLP);

  $("lp_cvelp").textContent = safe(it.CveLP);
  $("lp_tipo").textContent  = `${safe(it.tipo)} · ${String(it.Activo)==="1" ? "ACTIVO" : "INACTIVO"}`;
  $("lp_alm").textContent   = safe(it.cve_almac);
  $("lp_idcont").textContent= safe(it.IDContenedor);
  $("lp_idy").textContent   = safe(it.idy_ubica);
  $("lp_bl").textContent    = safe(it.BL);

  $("lpAlmTag").textContent = safe(it.cve_almac);

  if(String(it.Activo)==="1"){
    $("lpActivoPill").style.display="inline-flex";
  }else{
    $("lpActivoPill").style.display="none";
  }

  $("lpTa").style.display="none";
  log(`LP seleccionado: ${safe(it.CveLP)} (Alm=${safe(it.cve_almac)} BL=${safe(it.BL)})`);

  // Si BL filter está activo y ya hay texto en BL input, relanza búsqueda BL
  if($("chkFiltrarAlm").checked && $("blInput").value.trim().length>=2){
    onBlInput();
  }
}

function normalizeBLCode(it){
  return it.CodigoCSD || it.codigoCSD || it.codigocsd || it.BL || it.codigo || "";
}

function selectBL(it){
  selectedBL = it;
  const code = normalizeBLCode(it);
  $("blInput").value = safe(code);

  $("bl_cod").textContent = safe(code);
  $("bl_alm").textContent = `${safe(it.cve_almac)} · Mixto: ${safe(it.mixto)}`;
  $("bl_idy").textContent = safe(it.idy_ubica);

  // existencia puede venir separada o ya calculada
  const exP = it.ex_pallets ?? it.pallets ?? it.exist_pallets ?? "";
  const exC = it.ex_cajas ?? it.cajas ?? it.exist_cajas ?? "";
  $("bl_exist").textContent = (exP || exC) ? `${safe(exP)} / ${safe(exC)}` : "—";

  renderBLContenido(it);

  $("blTa").style.display="none";
  log(`BL seleccionado: ${safe(code)} (Alm=${safe(it.cve_almac)} idy=${safe(it.idy_ubica)})`);
}

function renderBLContenido(it){
  const tb = $("blContenido");
  const contenido = it.contenido || it.items || it.lps || null;

  tb.innerHTML = "";
  if(!contenido || !Array.isArray(contenido) || !contenido.length){
    tb.innerHTML = `<tr><td colspan="3" class="muted">Sin contenido disponible.</td></tr>`;
    return;
  }
  contenido.slice(0,200).forEach((r)=>{
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${safe(r.CveLP || r.lp || r.LP)}</td>
      <td>${safe(r.tipo || r.Tipo)}</td>
      <td>${safe(r.Activo)}</td>
    `;
    tb.appendChild(tr);
  });
}

/** =========================
 *  MODAL SEARCH (LP / BL)
 *  ========================= */
function openModal(title, theadHtml, rowsHtml, hint){
  $("modalTitle").textContent = title;
  $("modalThead").innerHTML = theadHtml;
  $("modalTbody").innerHTML = rowsHtml;
  $("modalHint").textContent = hint || "";
  $("modalMask").style.display="flex";
}
$("modalClose").addEventListener("click", ()=> $("modalMask").style.display="none");
$("modalMask").addEventListener("click",(e)=>{
  if(e.target.id==="modalMask") $("modalMask").style.display="none";
});

async function searchLPModal(){
  const q = $("lpInput").value.trim();
  if(q.length<3){ log("LP: mínimo 3 caracteres."); return; }
  const url = buildUrl(API_LP, {q});
  const json = await fetchJson(url);
  const items = json.data || [];
  $("lpCount").textContent = items.length;

  let rows = "";
  items.forEach((it, idx)=>{
    rows += `
      <tr data-idx="${idx}">
        <td>${safe(it.CveLP)}</td>
        <td>${safe(it.tipo)}</td>
        <td>${safe(it.cve_almac)}</td>
        <td>${safe(it.BL)}</td>
        <td>${safe(it.Activo)}</td>
      </tr>`;
  });

  openModal(
    "Resultados LP",
    "<tr><th>CveLP</th><th>Tipo</th><th>Alm</th><th>BL</th><th>Activo</th></tr>",
    rows || `<tr><td colspan="5" class="muted">Sin resultados.</td></tr>`,
    `Coincidencias: ${items.length}. Click en una fila para seleccionar.`
  );

  // bind row click
  $("modalTbody").querySelectorAll("tr[data-idx]").forEach(tr=>{
    tr.addEventListener("click", ()=>{
      const it = items[Number(tr.getAttribute("data-idx"))];
      selectLP(it);
      $("modalMask").style.display="none";
    });
  });
}

async function searchBLModal(){
  const q = $("blInput").value.trim();
  if(q.length<2){ log("BL: mínimo 2 caracteres."); return; }

  let cve_almac = "";
  if($("chkFiltrarAlm").checked && selectedLP && selectedLP.cve_almac){
    cve_almac = selectedLP.cve_almac;
  }

  const url = buildUrl(API_BL, {q, cve_almac}); // ✅ FIX: q value only
  const json = await fetchJson(url);
  const items = json.data || [];
  $("blCount").textContent = items.length;

  let rows = "";
  items.forEach((it, idx)=>{
    const code = normalizeBLCode(it);
    rows += `
      <tr data-idx="${idx}">
        <td>${safe(code)}</td>
        <td>${safe(it.cve_almac)}</td>
        <td>${safe(it.mixto)}</td>
        <td>${safe(it.idy_ubica)}</td>
        <td>${safe(it.Activo)}</td>
      </tr>`;
  });

  openModal(
    "Resultados BL",
    "<tr><th>CodigoCSD</th><th>Alm</th><th>Mixto</th><th>idy_ubica</th><th>Activo</th></tr>",
    rows || `<tr><td colspan="5" class="muted">Sin resultados.</td></tr>`,
    `Coincidencias: ${items.length}. Click en una fila para seleccionar.`
  );

  $("modalTbody").querySelectorAll("tr[data-idx]").forEach(tr=>{
    tr.addEventListener("click", ()=>{
      const it = items[Number(tr.getAttribute("data-idx"))];
      selectBL(it);
      $("modalMask").style.display="none";
    });
  });
}

$("btnLpSearch").addEventListener("click", searchLPModal);
$("btnBlSearch").addEventListener("click", searchBLModal);

/** =========================
 *  MISC UI
 *  ========================= */
$("btnClear").addEventListener("click", ()=>{
  selectedLP=null; selectedBL=null;
  $("lpInput").value=""; $("blInput").value="";
  $("lpCount").textContent="0"; $("blCount").textContent="0";
  $("lpAlmTag").textContent="—";

  ["lp_cvelp","lp_tipo","lp_alm","lp_idcont","lp_idy","lp_bl"].forEach(id=>$(id).textContent="—");
  $("lpActivoPill").style.display="none";

  ["bl_cod","bl_alm","bl_idy","bl_exist"].forEach(id=>$(id).textContent="—");
  $("blContenido").innerHTML = `<tr><td colspan="3" class="muted">Selecciona un BL para ver su contenido.</td></tr>`;

  log("UI reiniciada.");
});

$("btnCollapseBl").addEventListener("click", ()=>{
  const box = $("blInfo");
  const isHidden = box.style.display==="none";
  box.style.display = isHidden ? "grid" : "none";
  $("btnCollapseBl").textContent = isHidden ? "-" : "+";
});

$("btnPreview").addEventListener("click", ()=>{
  $("fasePill").textContent="Fase: Preview";
  log("Preview (placeholder). Aquí se conectará lp_tr.php init/preview.");
});
$("btnExec").addEventListener("click", ()=>{
  $("fasePill").textContent="Fase: Execute";
  log("Execute (placeholder). Aquí se conectará lp_tr.php execute.");
});

log("INFO: UI lista. LP/BL typeahead + modal. (Se corrigió construcción de URL q=VALUE).");
</script>
</body>
</html>
