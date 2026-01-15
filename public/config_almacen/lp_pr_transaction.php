<?php
// public/config_almacen/lp_pr_transaction.php
require_once __DIR__ . '/../../app/db.php';

$activeSection = 'config_almacen';
$activeItem    = 'lp_pr_transaction';
$pageTitle     = 'LP · Transacciones (Operación)';

include __DIR__ . '/../bi/_menu_global.php';

// ====== Mapa cve_almac (num) => clave + descripcion (c_almacen) ======
$mapAlm = [];
try {
    $rows = db_all("
        SELECT cve_almac, clave_almacen, des_almac
        FROM c_almacen
        WHERE IFNULL(Activo,1)=1
        ORDER BY cve_almac
    ");
    foreach ($rows as $r) {
        $mapAlm[(string)$r['cve_almac']] = [
            'clave' => (string)($r['clave_almacen'] ?? ''),
            'desc'  => (string)($r['des_almac'] ?? '')
        ];
    }
} catch (Throwable $e) {
    // silencioso: la UI sigue con cve_almac si no hay tabla
}

$MAP_ALM_JSON = json_encode($mapAlm, JSON_UNESCAPED_UNICODE);

// ====== Endpoints (NO CAMBIAR) ======
$API_LOOKUP_LP = "/assistpro_kardex_fc/public/api/lp/lookup_lp.php?q=";
$API_LOOKUP_BL = "/assistpro_kardex_fc/public/api/lp/lookup_bl.php?q=";

// Filtros full (zonas)
$API_FILTROS   = "/assistpro_kardex_fc/public/api/filtros_assistpro_lps.php?sections=zonas_recep,zonas_emb,zonas_prod&almacen=";

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($pageTitle) ?></title>
<style>
  body{ font-size:10px; }
  .ap-wrap{ padding:10px; }
  .ap-card{ background:#fff; border:1px solid #e6edf5; border-radius:10px; padding:10px; margin-bottom:10px; }
  .ap-grid{ display:grid; grid-template-columns: 1fr 1fr 260px; gap:10px; align-items:end; }
  .ap-grid2{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
  .ap-row{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .ap-label{ font-weight:700; color:#0b2b4c; margin-bottom:4px; }
  .ap-input{ width:100%; border:1px solid #d7e3f0; border-radius:8px; padding:8px 10px; outline:none; }
  .ap-input:focus{ border-color:#8fb7e6; box-shadow:0 0 0 2px rgba(143,183,230,.25); }
  .btn{ border:0; border-radius:10px; padding:8px 12px; cursor:pointer; font-weight:700; }
  .btn-gray{ background:#6b7280; color:#fff; }
  .btn-blue{ background:#0b3a7c; color:#fff; }
  .btn-green{ background:#198754; color:#fff; }
  .btn-soft{ background:#eef5ff; color:#0b3a7c; border:1px solid #cfe2ff; }
  .pill{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:3px 8px; font-weight:700; font-size:10px; }
  .pill-ok{ background:#e9f8ef; color:#167a3e; border:1px solid #bfe9cf; }
  .pill-bad{ background:#fff0f0; color:#a11a1a; border:1px solid #ffd0d0; }
  .muted{ color:#6b7280; }
  .hint{ font-size:10px; color:#607086; margin-top:4px; }
  .ta{ position:relative; }
  .sug{ position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d7e3f0; border-radius:10px; margin-top:6px;
        max-height:240px; overflow:auto; z-index:50; display:none; }
  .sug .it{ padding:8px 10px; border-bottom:1px solid #eef2f7; cursor:pointer; }
  .sug .it:hover{ background:#f6faff; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ padding:7px 8px; border-bottom:1px solid #eef2f7; text-align:left; white-space:nowrap; }
  thead th{ position:sticky; top:0; background:#0b2b4c; color:#fff; z-index:1; }
  .scroll{ max-height:260px; overflow:auto; border:1px solid #e6edf5; border-radius:10px; }
  .zonebox{ border:1px solid #e6edf5; border-radius:10px; padding:10px; background:#fbfdff; }
  .zonebox h4{ margin:0 0 6px 0; font-size:11px; }
  .zone-list{ max-height:220px; overflow:auto; }
  .zone-it{ display:flex; align-items:center; gap:8px; padding:6px 4px; border-bottom:1px dashed #e6edf5; }
  .zone-it:last-child{ border-bottom:0; }
  .errbar{ display:none; margin-top:8px; padding:8px 10px; border-radius:10px; border:1px solid #ffd0d0; background:#fff0f0; color:#a11a1a; font-weight:700; }
  .okbar{ display:none; margin-top:8px; padding:8px 10px; border-radius:10px; border:1px solid #bfe9cf; background:#e9f8ef; color:#167a3e; font-weight:700; }
</style>
</head>
<body>
<div class="ap-wrap">

  <div class="ap-card">
    <div class="ap-row" style="justify-content:space-between;">
      <div>
        <div style="font-size:14px;font-weight:900;">LP · Transacciones (Operación) · Traslado por LP / Contenedor</div>
        <div class="muted">Validación independiente: lookup_lp / lookup_bl · Sin tocar APIs</div>
      </div>
      <div class="pill pill-ok" id="phasePill">Fase: Lookup</div>
    </div>

    <div class="ap-grid" style="margin-top:10px;">
      <div class="ta">
        <div class="ap-label">LP origen (CveLP)</div>
        <input id="lpInput" class="ap-input" placeholder="Escanea o escribe LP">
        <div class="hint">Typeahead por coincidencia. Botón Buscar abre resultados directos (lookup_lp.php).</div>
        <div id="lpSug" class="sug"></div>
        <div style="margin-top:8px;">
          <button id="btnLpBuscar" class="btn btn-soft">Buscar LP</button>
        </div>
      </div>

      <div class="ta">
        <div class="ap-label">BL destino (CodigoCSD)</div>
        <input id="blInput" class="ap-input" placeholder="Escanea o escribe BL">
        <div class="hint">Lookup independiente (lookup_bl.php). Ideal para validar disponibilidad real del API.</div>
        <div id="blSug" class="sug"></div>

        <div class="ap-row" style="margin-top:8px;">
          <label class="ap-row" style="gap:6px;">
            <input type="checkbox" id="chkFiltrarAlm" checked>
            <span><b>Filtrar BL por almacén del LP</b> <span class="muted">(recomendado operación)</span></span>
          </label>
          <span class="muted">· Almacén LP:</span> <b id="lpAlmTag">—</b>
        </div>

        <div style="margin-top:8px;">
          <button id="btnBlBuscar" class="btn btn-soft">Buscar BL</button>
        </div>
      </div>

      <div>
        <div class="ap-label">Modo destino</div>
        <select id="modoDestino" class="ap-input">
          <option value="">Seleccionar…</option>
          <option value="MISMO_BL">Mismo BL (solo re-etiquetar)</option>
          <option value="TRASLADO">Traslado</option>
          <option value="NUEVO_LP">Nuevo LP (fase siguiente)</option>
        </select>
        <div class="hint">Estrategia “Nuevo LP” queda lista para fases siguientes (API lp_tr.php).</div>

        <div class="ap-row" style="margin-top:10px; justify-content:flex-end;">
          <button id="btnPreview" class="btn btn-blue">Previsualizar</button>
          <button id="btnExec" class="btn btn-green">Ejecutar traslado</button>
          <button id="btnClear" class="btn btn-soft">Limpiar</button>
        </div>

        <div id="errBar" class="errbar"></div>
        <div id="okBar" class="okbar"></div>
      </div>
    </div>
  </div>

  <div class="ap-grid2">
    <div class="ap-card">
      <div class="ap-row" style="justify-content:space-between;">
        <div style="font-weight:900;">LP seleccionado</div>
        <span id="lpActivoPill" class="pill pill-ok" style="display:none;">ACTIVO</span>
      </div>

      <table style="margin-top:8px;">
        <tr><td style="width:180px;"><b>CveLP</b></td><td id="lp_cvelp">—</td></tr>
        <tr><td><b>Tipo / Activo</b></td><td id="lp_tipo">—</td></tr>
        <tr><td><b>Almacén</b></td><td id="lp_alm">—</td></tr>
        <tr><td><b>IDContenedor</b></td><td id="lp_idcont">—</td></tr>
        <tr><td><b>Ubicación (idy_ubica)</b></td><td id="lp_idy">—</td></tr>
        <tr><td><b>BL actual (CodigoCSD)</b></td><td id="lp_bl">—</td></tr>
      </table>
      <div class="hint">Derivado del lookup LP. Si BL actual viene vacío, aquí forzamos opción de “ubicar” vía Zonas.</div>
    </div>

    <div class="ap-card">
      <div class="ap-row" style="justify-content:space-between;">
        <div style="font-weight:900;">BL destino seleccionado</div>
        <span id="blOkPill" class="pill pill-ok" style="display:none;">OK</span>
      </div>

      <table style="margin-top:8px;">
        <tr><td style="width:180px;"><b>CodigoCSD</b></td><td id="bl_cod">—</td></tr>
        <tr><td><b>Almacén / Mixto</b></td><td id="bl_alm">—</td></tr>
        <tr><td><b>idy_ubica</b></td><td id="bl_idy">—</td></tr>
        <tr><td><b>Existencia BL (pallets / cajas)</b></td><td id="bl_exist">—</td></tr>
      </table>

      <div class="ap-row" style="justify-content:space-between; margin-top:10px;">
        <div style="font-weight:900;">Contenido BL destino</div>
        <div class="muted">LPs / cajas</div>
      </div>

      <div class="scroll" style="margin-top:8px;">
        <table>
          <thead><tr><th>LP</th><th>Tipo</th><th>Activo</th></tr></thead>
          <tbody id="blContenido">
            <tr><td colspan="3" class="muted">Selecciona un BL para ver su contenido.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="hint">Si el BL tiene contenido, aquí debe aparecer desde el API. (Si sale vacío, es tema del lookup_bl.php, no de UI).</div>
    </div>
  </div>

  <div class="ap-card">
    <div class="ap-row" style="justify-content:space-between;">
      <div style="font-weight:900;">Zonas rápidas (BLs operativos)</div>
      <div class="muted">Recepción / Embarque / Producción · Selecciona para ubicar y evitar NULL</div>
    </div>

    <div class="zonebox" style="margin-top:8px;">
      <h4>Aplican al almacén del LP seleccionado (<span id="zonAlm">—</span>)</h4>
      <div class="zone-list" id="zoneList">
        <div class="muted">Selecciona un LP para cargar zonas…</div>
      </div>
      <div class="hint">Estas zonas se comportan como BL destino para lookup full. No ejecutan traslado por sí solas, pero habilitan “ubicación obligatoria”.</div>
    </div>
  </div>

  <div class="ap-card">
    <div style="font-weight:900;">Resultado / Kardex</div>
    <div class="hint">Aquí se integra lp_tr.php (init/preview/execute). En esta fase dejamos listo el control operativo + validaciones.</div>
    <div class="ap-grid2" style="margin-top:8px;">
      <div>
        <div class="ap-label">tx_id</div>
        <input class="ap-input" id="tx_id" placeholder="(placeholder)">
      </div>
      <div>
        <div class="ap-label">Kardex rows</div>
        <input class="ap-input" id="k_rows" placeholder="(placeholder)">
      </div>
    </div>
  </div>

  <div class="ap-card">
    <div style="font-weight:900;">Log operativo</div>
    <pre id="log" style="margin-top:8px; background:#0b1220; color:#cfe7ff; padding:10px; border-radius:10px; max-height:220px; overflow:auto;"></pre>
  </div>

</div>

<script>
  const API_LOOKUP_LP = <?= json_encode($API_LOOKUP_LP) ?>;
  const API_LOOKUP_BL = <?= json_encode($API_LOOKUP_BL) ?>;
  const API_FILTROS   = <?= json_encode($API_FILTROS) ?>;

  const MAP_ALM = <?= $MAP_ALM_JSON ?>;

  const $ = (id)=>document.getElementById(id);
  const safe = (v)=> (v===null || typeof v==="undefined" || v==="") ? "—" : String(v);

  let selectedLP = null;
  let selectedBL = null;
  let zonesCache = null;

  function almLabel(cve){
    const k = String(cve ?? '');
    if(!k || !MAP_ALM[k]) return safe(cve);
    const a = MAP_ALM[k];
    const left = (a.clave && a.clave.trim()) ? a.clave.trim() : k;
    const right= (a.desc && a.desc.trim()) ? a.desc.trim() : '';
    return right ? `${left} · ${right}` : left;
  }

  function log(msg, lvl="OK"){
    const t = new Date().toLocaleTimeString();
    const line = `[${t}] ${lvl}: ${msg}\n`;
    $("log").textContent += line;
    $("log").scrollTop = $("log").scrollHeight;
  }

  function showErr(msg){
    $("errBar").style.display="block";
    $("errBar").textContent = msg;
    $("okBar").style.display="none";
  }
  function showOk(msg){
    $("okBar").style.display="block";
    $("okBar").textContent = msg;
    $("errBar").style.display="none";
  }
  function clearBars(){
    $("errBar").style.display="none";
    $("okBar").style.display="none";
  }

  async function fetchJson(url){
    const r = await fetch(url, {cache:"no-store"});
    const txt = await r.text();
    try { return {ok:true, json:JSON.parse(txt), status:r.status}; }
    catch(e){ return {ok:false, raw:txt, status:r.status}; }
  }

  function hideSug(id){ $(id).style.display="none"; $(id).innerHTML=""; }
  function showSug(id){ $(id).style.display="block"; }

  // ============= LP lookup =============
  async function searchLP(q, openModal=false){
    clearBars();
    if(!q || q.trim().length<3){ showErr("LP: mínimo 3 caracteres."); return; }

    const r = await fetchJson(API_LOOKUP_LP + encodeURIComponent(q.trim()));
    if(!r.ok || !r.json || r.json.ok !== true){ showErr("LP: respuesta inválida del API."); return; }

    const rows = r.json.data || [];
    renderLPSug(rows);
    if(openModal && rows.length===1) selectLP(rows[0]);
    if(openModal && rows.length===0) showErr("LP: sin coincidencias.");
  }

  function renderLPSug(rows){
    const box = $("lpSug");
    box.innerHTML = "";
    if(!rows.length){ hideSug("lpSug"); return; }

    rows.slice(0,120).forEach((it)=>{
      const div = document.createElement("div");
      div.className = "it";
      div.innerHTML = `<b>${it.CveLP}</b> <span class="muted">· ${safe(it.tipo)} · alm ${safe(it.cve_almac)} · BL ${safe(it.BL)}</span>`;
      div.addEventListener("mousedown",(e)=>{ e.preventDefault(); selectLP(it); hideSug("lpSug"); });
      box.appendChild(div);
    });
    showSug("lpSug");
  }

  function selectLP(it){
    selectedLP = it;

    $("lp_cvelp").textContent = safe(it.CveLP);
    $("lp_tipo").textContent  = `${safe(it.tipo)} · ${String(it.Activo)==="1" ? "ACTIVO" : "INACTIVO"}`;
    $("lp_alm").textContent   = almLabel(it.cve_almac);
    $("lp_idcont").textContent= safe(it.IDContenedor);
    $("lp_idy").textContent   = safe(it.idy_ubica);
    $("lp_bl").textContent    = safe(it.BL);

    $("lpAlmTag").textContent = almLabel(it.cve_almac);
    $("zonAlm").textContent   = almLabel(it.cve_almac);

    $("lpActivoPill").style.display = (String(it.Activo)==="1") ? "inline-flex":"none";

    log(`LP seleccionado: ${safe(it.CveLP)} | alm=${safe(it.cve_almac)} | BL=${safe(it.BL)}`);

    // Cargar zonas del almacén del LP (lookup full / no traslado)
    loadZonasForLP();

    // Si el usuario ya está tecleando BL y el filtro por almacén está activo, relanza BL
    if($("chkFiltrarAlm").checked && $("blInput").value.trim().length>=2){
      searchBL($("blInput").value.trim());
    }
  }

  // ============= BL lookup =============
  async function searchBL(q, openModal=false){
    clearBars();
    if(!q || q.trim().length<2){ showErr("BL: mínimo 2 caracteres."); return; }

    let url = API_LOOKUP_BL + encodeURIComponent(q.trim());
    if($("chkFiltrarAlm").checked && selectedLP && selectedLP.cve_almac){
      url += "&cve_almac=" + encodeURIComponent(String(selectedLP.cve_almac));
    }

    const r = await fetchJson(url);
    if(!r.ok || !r.json || r.json.ok !== true){ showErr("BL: respuesta inválida del API."); return; }

    const rows = r.json.data || [];
    renderBLSug(rows);
    if(openModal && rows.length===1) selectBL(rows[0]);
    if(openModal && rows.length===0) showErr("BL: sin coincidencias.");
  }

  function normalizeBLCode(it){
    return it.CodigoCSD || it.codigoCSD || it.codigocsd || it.BL || it.codigo || it.cve_ubicacion || "";
  }

  function renderBLSug(rows){
    const box = $("blSug");
    box.innerHTML = "";
    if(!rows.length){ hideSug("blSug"); return; }

    rows.slice(0,120).forEach((it)=>{
      const code = normalizeBLCode(it);
      const alm  = it.cve_almac ?? it.cve_alm ?? "—";
      const mixto= it.AcomodoMixto ?? it.mixto ?? "—";
      const div = document.createElement("div");
      div.className="it";
      div.innerHTML = `<b>${code}</b> <span class="muted">· alm ${safe(alm)} · mixto ${safe(mixto)} · idy ${safe(it.idy_ubica)}</span>`;
      div.addEventListener("mousedown",(e)=>{ e.preventDefault(); selectBL(it); hideSug("blSug"); });
      box.appendChild(div);
    });

    showSug("blSug");
  }

  function selectBL(it){
    const code = normalizeBLCode(it);
    if(!code){ showErr("BL inválido."); return; }

    // VALIDACIÓN: BL destino no puede ser igual al BL origen
    const blOri = (selectedLP && selectedLP.BL) ? String(selectedLP.BL).trim().toUpperCase() : "";
    const blDst = String(code).trim().toUpperCase();
    if(blOri && blDst && blOri === blDst){
      selectedBL = null;
      $("bl_cod").textContent="—";
      $("bl_alm").textContent="—";
      $("bl_idy").textContent="—";
      $("bl_exist").textContent="—";
      $("blContenido").innerHTML = `<tr><td colspan="3" class="muted">Selecciona un BL para ver su contenido.</td></tr>`;
      $("blOkPill").style.display="none";
      showErr("No permitido: BL destino es igual al BL origen. Selecciona otro BL.");
      log(`Bloqueado BL destino igual a origen (${blDst}).`, "ERR");
      return;
    }

    selectedBL = it;
    $("blInput").value = code;

    $("bl_cod").textContent = code;
    $("bl_alm").textContent = `${almLabel(it.cve_almac ?? it.cve_alm)} · Mixto: ${safe(it.AcomodoMixto ?? it.mixto ?? it.Mixto)}`;
    $("bl_idy").textContent = safe(it.idy_ubica);

    const exP = it.ex_pallets ?? it.pallets ?? it.exist_pallets ?? "";
    const exC = it.ex_cajas ?? it.cajas ?? it.exist_cajas ?? "";
    $("bl_exist").textContent = (exP || exC) ? `${safe(exP)} / ${safe(exC)}` : "—";

    renderBLContenido(it);
    $("blOkPill").style.display="inline-flex";
    showOk("BL destino seleccionado correctamente.");
    log(`BL seleccionado: ${code} | alm=${safe(it.cve_almac)} | idy=${safe(it.idy_ubica)}`);
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

  // ============= ZONAS (lookup full) =============
  async function loadZonasForLP(){
    $("zoneList").innerHTML = `<div class="muted">Cargando zonas…</div>`;
    zonesCache = null;

    if(!selectedLP || !selectedLP.cve_almac || !String(selectedLP.cve_almac).match(/^\d+$/)){
      $("zoneList").innerHTML = `<div class="muted">Selecciona un LP con almacén numérico para cargar zonas.</div>`;
      return;
    }

    const alm = String(selectedLP.cve_almac);
    const r = await fetchJson(API_FILTROS + encodeURIComponent(alm));
    if(!r.ok || !r.json || r.json.ok !== true){
      $("zoneList").innerHTML = `<div class="muted">No se pudieron cargar zonas (filtros_assistpro).</div>`;
      log("Error cargando zonas desde filtros_assistpro.", "ERR");
      return;
    }

    zonesCache = r.json;
    renderZonesUI();
  }

  function zoneRow(type, z){
    const code = z.cve_ubicacion || z.CodigoCSD || "";
    const desc = z.descripcion || z.desc_ubicacion || "";
    const alm  = z.cve_almac ?? z.cve_almacp ?? selectedLP?.cve_almac ?? "";
    return { type, code, desc, alm, raw:z };
  }

  function renderZonesUI(){
    const box = $("zoneList");
    box.innerHTML = "";

    const zr = (zonesCache?.zonas_recep || []).map(z=>zoneRow("RECEPCIÓN", z));
    const ze = (zonesCache?.zonas_emb   || []).map(z=>zoneRow("EMBARQUE", z));
    const zp = (zonesCache?.zonas_prod  || []).map(z=>zoneRow("PRODUCCIÓN", z));

    const all = [...zr, ...ze, ...zp].filter(x=>x.code);
    if(!all.length){
      box.innerHTML = `<div class="muted">No hay zonas activas para este almacén.</div>`;
      return;
    }

    all.forEach((z, idx)=>{
      const div = document.createElement("div");
      div.className="zone-it";
      div.innerHTML = `
        <input type="radio" name="zonePick" id="z_${idx}">
        <div style="min-width:110px;"><b>${z.type}</b></div>
        <div style="flex:1;">
          <div><b>${z.code}</b> <span class="muted">· ${safe(z.desc)}</span></div>
          <div class="muted">alm: ${almLabel(z.alm)}</div>
        </div>
      `;
      div.querySelector("input").addEventListener("change", ()=>{
        // Se comporta como BL destino “rápido”
        selectBL({
          CodigoCSD: z.code,
          cve_almac: z.alm,
          idy_ubica: z.raw?.idy_ubica ?? z.raw?.id_zona ?? null,
          mixto: z.raw?.AcomodoMixto ?? "—",
          contenido: [] // es zona rápida: si quieres contenido real, el usuario puede buscar por lookup_bl
        });
        log(`Zona rápida aplicada como BL destino: ${z.type} | ${z.code}`);
      });
      box.appendChild(div);
    });

    const note = document.createElement("div");
    note.className="hint";
    note.textContent = "Tip operativo: si el LP trae BL NULL, usa una Zona rápida para ubicarlo y eliminar nulos.";
    box.appendChild(note);
  }

  // ============= Events =============
  let lpTimer=null, blTimer=null;

  $("lpInput").addEventListener("input", ()=>{
    const q = $("lpInput").value.trim();
    if(lpTimer) clearTimeout(lpTimer);
    lpTimer = setTimeout(()=>{ if(q.length>=3) searchLP(q); else hideSug("lpSug"); }, 180);
  });

  $("blInput").addEventListener("input", ()=>{
    const q = $("blInput").value.trim();
    if(blTimer) clearTimeout(blTimer);
    blTimer = setTimeout(()=>{ if(q.length>=2) searchBL(q); else hideSug("blSug"); }, 180);
  });

  $("btnLpBuscar").addEventListener("click", ()=> searchLP($("lpInput").value.trim(), true));
  $("btnBlBuscar").addEventListener("click", ()=> searchBL($("blInput").value.trim(), true));

  $("btnPreview").addEventListener("click", ()=>{
    clearBars();
    if(!selectedLP){ showErr("Previsualizar: falta LP origen."); return; }
    if(!selectedBL){ showErr("Previsualizar: falta BL destino."); return; }
    showOk("Previsualización lista (integración lp_tr.php pendiente).");
    log("Previsualización lista para integrar lp_tr.php (init/preview).");
  });

  $("btnExec").addEventListener("click", ()=>{
    clearBars();
    if(!selectedLP){ showErr("Ejecutar: falta LP origen."); return; }
    if(!selectedBL){ showErr("Ejecutar: falta BL destino."); return; }
    showOk("Ejecución lista (integración lp_tr.php pendiente).");
    log("Ejecución lista para integrar lp_tr.php (execute).");
  });

  $("btnClear").addEventListener("click", ()=>{
    selectedLP=null; selectedBL=null; zonesCache=null;
    $("lpInput").value=""; $("blInput").value="";
    $("lp_cvelp").textContent="—"; $("lp_tipo").textContent="—"; $("lp_alm").textContent="—";
    $("lp_idcont").textContent="—"; $("lp_idy").textContent="—"; $("lp_bl").textContent="—";
    $("lpAlmTag").textContent="—"; $("zonAlm").textContent="—";
    $("lpActivoPill").style.display="none";

    $("bl_cod").textContent="—"; $("bl_alm").textContent="—"; $("bl_idy").textContent="—"; $("bl_exist").textContent="—";
    $("blContenido").innerHTML = `<tr><td colspan="3" class="muted">Selecciona un BL para ver su contenido.</td></tr>`;
    $("blOkPill").style.display="none";

    $("zoneList").innerHTML = `<div class="muted">Selecciona un LP para cargar zonas…</div>`;

    $("log").textContent="";
    clearBars();
    log("Listo. Usa typeahead o Buscar para validar lookup_lp / lookup_bl y zonas rápidas.");
  });

  document.addEventListener("click",(e)=>{
    if(!e.target.closest(".ta")){ hideSug("lpSug"); hideSug("blSug"); }
  });

  log("UI lista. Estrategia: lookup full (LP/BL) + zonas rápidas para eliminar ubicaciones NULL.");
</script>
</body>
</html>
