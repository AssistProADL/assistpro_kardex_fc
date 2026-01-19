<?php
// public/control_patios/patios_admin.php
// Importante: NO modificar _menu_global.php / _menu_global_end.php (lo usan todas las vistas)
// Este tablero NO depende de nombres de columnas en catálogos: consume filtros_assistpro.php.

declare(strict_types=1);

$empresa_id  = isset($_GET['empresa_id']) ? trim((string)$_GET['empresa_id']) : '';
$almacenp_id = isset($_GET['almacenp_id']) ? trim((string)$_GET['almacenp_id']) : '';

include __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="max-width: 1600px;">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <div>
      <h3 class="mb-0">Administración de Patios</h3>
      <div class="small text-muted">Selecciona empresa y almacén. Luego presiona <b>Nueva visita</b> o <b>Refrescar</b>.</div>
      <div id="alertaTop" class="mt-1 small" style="display:none;"></div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnRefrescar">
        <i class="fa-solid fa-rotate"></i> Refrescar
      </button>
      <button class="btn btn-primary btn-sm" id="btnNuevaVisita">
        <i class="fa-solid fa-plus"></i> Nueva visita
      </button>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body py-3">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small text-muted mb-1">Empresa (c_compania)</label>
          <select class="form-select form-select-sm" id="empresaSelect">
            <option value="">(Seleccione)</option>
          </select>
        </div>
        <div class="col-12 col-md-5">
          <label class="form-label small text-muted mb-1">Almacén / Patio (c_almacenp.id)</label>
          <select class="form-select form-select-sm" id="almacenSelect" disabled>
            <option value="">(Seleccione)</option>
          </select>
        </div>

        <div class="col-12 col-md-3 d-flex justify-content-end gap-2">
          <button class="btn btn-outline-secondary btn-sm w-100" id="btnAplicar">
            <i class="fa-solid fa-filter"></i> Aplicar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Encabezados de columnas -->
  <div class="row g-3 mb-2">
    <div class="col-6 col-lg-3 text-center">
      <div class="fw-bold">1. CITA <span class="badge bg-light text-dark border" id="countCita">0</span></div>
    </div>
    <div class="col-6 col-lg-3 text-center">
      <div class="fw-bold">2. ARRIBO / EN PATIO <span class="badge bg-light text-dark border" id="countPATIO">0</span></div>
    </div>
    <div class="col-6 col-lg-3 text-center">
      <div class="fw-bold">3. INSPECCIÓN / QA <span class="badge bg-light text-dark border" id="countQA">0</span></div>
    </div>
    <div class="col-6 col-lg-3 text-center">
      <div class="fw-bold">4. CARGA / DESCARGA <span class="badge bg-light text-dark border" id="countCARGA">0</span></div>
    </div>
  </div>

  <!-- Tablero -->
  <div class="row g-3" id="tablero">
    <div class="col-lg-3">
      <div class="colBox" data-col="CITA">
        <div class="colBody" id="colCita"></div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="colBox" data-col="EN_PATIO">
        <div class="colBody" id="colPATIO"></div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="colBox" data-col="QA">
        <div class="colBody" id="colQA"></div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="colBox" data-col="EN_DESCARGA">
        <div class="colBody" id="colCARGA"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Vincular OCs -->
<div class="modal fade" id="modalOC" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: var(--adl-primary); color:#fff;">
        <h5 class="modal-title"><i class="fa-solid fa-link"></i> Vincular órdenes de compra</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="ocError" class="alert alert-danger py-2 small" style="display:none;"></div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <div class="small text-muted">
            Selecciona OCs pendientes y presiona <b>Vincular seleccionadas</b>.
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="btnRecargarOCs">
              <i class="fa-solid fa-rotate"></i> Recargar
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:36px;"><input type="checkbox" id="chkAllOCs"></th>
                <th>OC / Folio</th>
                <th>Proveedor</th>
                <th class="text-end">Monto</th>
                <th>Origen</th>
              </tr>
            </thead>
            <tbody id="tbodyOCs">
              <tr><td colspan="5" class="text-center text-muted py-4">Sin datos</td></tr>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" id="btnVincularSeleccionadas">
          <i class="fa-solid fa-check"></i> Vincular seleccionadas
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  /* Solo estilos locales del tablero. Todo lo global vive en _menu_global.php */
  .colBox{
    background:#fff;
    border:1px solid rgba(0,0,0,.08);
    border-radius:14px;
    min-height: 160px;
  }
  .colBody{ padding: 6px; min-height: 140px; }
  .cardVisita{
    border:1px solid rgba(0,0,0,.10);
    border-radius:14px;
    padding:10px 10px 8px 10px;
    box-shadow: 0 1px 8px rgba(0,0,0,.05);
    background: #fff;
    margin-bottom: 10px;
  }
  .cardVisita.critica{
    border-color: rgba(220,53,69,.35);
    box-shadow: 0 1px 10px rgba(220,53,69,.10);
  }
  .pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:1px solid rgba(0,0,0,.10);
    background:#f8f9fa;
    border-radius:999px;
    padding:2px 8px;
    font-size:12px;
    color:#333;
  }
  .pill-danger{ background: rgba(220,53,69,.08); border-color: rgba(220,53,69,.20); color:#b02a37; }
  .pill-muted{ background:#f3f4f6; border-color:#e5e7eb; color:#6b7280; }
  .pill-ok{ background: rgba(25,135,84,.08); border-color: rgba(25,135,84,.20); color:#146c43; }
  .btn-xs{ padding:.25rem .45rem; font-size:.78rem; border-radius:10px; }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
  .miniTitle{ font-weight:700; letter-spacing:.2px; }
  .sub{ font-size:12px; color:#6b7280; }
</style>

<script>
(function(){
  // Rutas API (ajusta si tu base path cambia)
  const API_FILTROS = "../api/filtros_assistpro.php";
  const API_PATIOS  = "../api/control_patios/api_control_patios.php";

  // Estado
  let empresa_id  = "<?= htmlspecialchars($empresa_id, ENT_QUOTES) ?>";
  let almacenp_id = "<?= htmlspecialchars($almacenp_id, ENT_QUOTES) ?>";
  let visitaActiva = null; // {id_visita, ...} para modal OC

  // DOM
  const empresaSelect = document.getElementById("empresaSelect");
  const almacenSelect = document.getElementById("almacenSelect");
  const btnAplicar    = document.getElementById("btnAplicar");
  const btnRefrescar  = document.getElementById("btnRefrescar");
  const btnNuevaVisita= document.getElementById("btnNuevaVisita");

  const colCita  = document.getElementById("colCita");
  const colPATIO = document.getElementById("colPATIO");
  const colQA    = document.getElementById("colQA");
  const colCARGA = document.getElementById("colCARGA");

  const countCita  = document.getElementById("countCita");
  const countPATIO = document.getElementById("countPATIO");
  const countQA    = document.getElementById("countQA");
  const countCARGA = document.getElementById("countCARGA");

  const alertaTop = document.getElementById("alertaTop");

  // Modal OCs
  const modalOCEl = document.getElementById("modalOC");
  const modalOC   = new bootstrap.Modal(modalOCEl);
  const tbodyOCs  = document.getElementById("tbodyOCs");
  const ocError   = document.getElementById("ocError");
  const chkAllOCs = document.getElementById("chkAllOCs");
  const btnVincularSeleccionadas = document.getElementById("btnVincularSeleccionadas");
  const btnRecargarOCs = document.getElementById("btnRecargarOCs");

  // Utils
  const fmtMoney = (n) => {
    const v = Number(n||0);
    return v.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
  };

  const minutesDiff = (iso) => {
    if(!iso) return null;
    const d = new Date(iso.replace(' ', 'T'));
    if(isNaN(d.getTime())) return null;
    return Math.floor((Date.now() - d.getTime())/60000);
  };

  const humanAging = (mins) => {
    if(mins === null) return "-";
    const h = Math.floor(mins/60);
    const m = mins%60;
    if(h <= 0) return `${m}m`;
    return `${h}h ${m}m`;
  };

  const safeText = (v) => (v === null || v === undefined) ? "" : String(v);

  async function apiGet(url){
    const r = await fetch(url, {headers: {'Accept':'application/json'}});
    const t = await r.text();
    try { return JSON.parse(t); } catch(e){ return {ok:false, error:"Respuesta no-JSON", raw:t}; }
  }

  function setTopAlert(type, html){
    alertaTop.style.display = "block";
    alertaTop.className = "mt-1 small alert py-1 px-2 " + (type === "danger" ? "alert-danger" : type === "warning" ? "alert-warning" : "alert-info");
    alertaTop.innerHTML = html;
  }
  function clearTopAlert(){
    alertaTop.style.display = "none";
    alertaTop.innerHTML = "";
  }

  // Catálogos por API existente
  async function cargarEmpresas(){
    empresaSelect.innerHTML = `<option value="">(Seleccione)</option>`;
    almacenSelect.innerHTML = `<option value="">(Seleccione)</option>`;
    almacenSelect.disabled = true;

    // filtros_assistpro suele soportar init (si en tu API el action difiere, ajusta aquí)
    const data = await apiGet(`${API_FILTROS}?action=empresas`);
    if(!data || data.ok === false){
      setTopAlert("danger", `<i class="fa-solid fa-triangle-exclamation"></i> No pude cargar empresas. ${safeText(data?.error||"")}`);
      return;
    }

    // Acepta formatos: {ok:true, data:[...]} o arreglo directo
    const arr = Array.isArray(data) ? data : (data.data || data.empresas || []);
    arr.forEach(e=>{
      const id  = e.cve_cia ?? e.id ?? e.empresa_id ?? "";
      const txt = e.des_cia ?? e.nombre ?? e.razon_social ?? e.clave_empresa ?? id;
      if(!id) return;
      const opt = document.createElement("option");
      opt.value = id;
      opt.textContent = `${id} - ${txt}`;
      empresaSelect.appendChild(opt);
    });

    if(empresa_id){
      empresaSelect.value = empresa_id;
      await cargarAlmacenes(empresa_id);
      if(almacenp_id) almacenSelect.value = almacenp_id;
    }
  }

  async function cargarAlmacenes(emp){
    almacenSelect.innerHTML = `<option value="">(Seleccione)</option>`;
    almacenSelect.disabled = true;

    if(!emp) return;

    const data = await apiGet(`${API_FILTROS}?action=almacenes&empresa_id=${encodeURIComponent(emp)}`);
    if(!data || data.ok === false){
      setTopAlert("danger", `<i class="fa-solid fa-triangle-exclamation"></i> No pude cargar almacenes. ${safeText(data?.error||"")}`);
      return;
    }

    const arr = Array.isArray(data) ? data : (data.data || data.almacenes || []);
    arr.forEach(a=>{
      const id  = a.id ?? a.id_almacenp ?? a.cve_almac ?? "";
      const txt = a.nombre ?? a.des_almac ?? a.clave ?? id;
      if(!id) return;
      const opt = document.createElement("option");
      opt.value = id;
      opt.textContent = `${id} - ${txt}`;
      almacenSelect.appendChild(opt);
    });

    almacenSelect.disabled = false;
  }

  // Render tarjetas
  function tarjetaVisita(v){
    // v debe venir del API patios (tablero)
    const idCita = safeText(v.id_cita || v.cita || v.id || "");
    const idVis  = safeText(v.id_visita || v.visita || "");
    const andEN  = safeText(v.id_anden_actual || v.anden || "-");
    const est    = safeText(v.estatus || v.status || "PENDIENTE");

    const llego  = safeText(v.fecha_llegada || v.llego || v.fecha || "");
    const mins   = minutesDiff(llego);
    const aging  = humanAging(mins);

    // criticidad configurable (ej: > 8 horas)
    const critica = (mins !== null && mins >= 8*60);

    const pillEstatus = (est === "EN_DESCARGA" || est === "CARGA" || est === "DESCARGA") ? "pill-ok"
                     : (est === "ASIGNADO_ANDEN") ? "pill-ok"
                     : (critica) ? "pill-danger" : "pill-danger";

    return `
      <div class="cardVisita ${critica ? "critica" : ""}" data-idvisita="${idVis}">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <div class="miniTitle">
              <i class="fa-solid fa-truck-moving me-1"></i>
              <span class="mono">${idCita}</span>
              <span class="text-muted">-</span>
              <span class="mono">${idVis}</span>
            </div>
            <div class="sub">Visita #${idVis} · ${est}</div>
          </div>
          <div class="d-flex flex-column align-items-end gap-1">
            <span class="pill ${pillEstatus}"><i class="fa-solid fa-circle-dot"></i> ${est}</span>
            <span class="pill pill-muted"><i class="fa-regular fa-clock"></i> Aging: ${aging}</span>
          </div>
        </div>

        <div class="mt-2 d-flex flex-wrap gap-2">
          <span class="pill"><i class="fa-solid fa-dolly"></i> Andén: ${andEN}</span>
          <span class="pill"><i class="fa-solid fa-calendar-check"></i> Llegó: ${llego || "-"}</span>
        </div>

        <div class="mt-2 d-flex gap-2">
          <button class="btn btn-outline-primary btn-xs" data-action="vincular-ocs">
            <i class="fa-solid fa-link"></i> Vincular OCs
          </button>
        </div>
      </div>
    `;
  }

  function renderTablero(items){
    const by = { CITA:[], EN_PATIO:[], QA:[], EN_DESCARGA:[] };

    (items||[]).forEach(v=>{
      // Normaliza estatus a columnas
      const est = safeText(v.estatus || "");
      if(est === "EN_PATIO" || est === "ASIGNADO_ANDEN") by.EN_PATIO.push(v);
      else if(est === "QA" || est === "INSPECCION" || est === "EN_QA") by.QA.push(v);
      else if(est === "EN_DESCARGA" || est === "CARGA" || est === "DESCARGA") by.EN_DESCARGA.push(v);
      else by.CITA.push(v);
    });

    colCita.innerHTML  = by.CITA.map(tarjetaVisita).join("")  || `<div class="text-center text-muted py-4">Sin visitas</div>`;
    colPATIO.innerHTML = by.EN_PATIO.map(tarjetaVisita).join("")|| `<div class="text-center text-muted py-4">Sin visitas</div>`;
    colQA.innerHTML    = by.QA.map(tarjetaVisita).join("")    || `<div class="text-center text-muted py-4">Sin visitas</div>`;
    colCARGA.innerHTML = by.EN_DESCARGA.map(tarjetaVisita).join("")|| `<div class="text-center text-muted py-4">Sin visitas</div>`;

    countCita.textContent  = by.CITA.length;
    countPATIO.textContent = by.EN_PATIO.length;
    countQA.textContent    = by.QA.length;
    countCARGA.textContent = by.EN_DESCARGA.length;

    // KPI superior (espera crítica)
    const crit = (items||[]).filter(v=>{
      const mins = minutesDiff(v.fecha_llegada || v.llego || "");
      return mins !== null && mins >= 8*60;
    }).length;

    if(crit > 0){
      setTopAlert("warning", `<i class="fa-solid fa-triangle-exclamation"></i> Atención: <b>${crit}</b> visitas en espera crítica`);
    }else{
      clearTopAlert();
    }
  }

  async function cargarTablero(){
    // Limpia si no hay selección
    if(!empresa_id || !almacenp_id){
      renderTablero([]);
      return;
    }

    const url = `${API_PATIOS}?action=tablero&empresa_id=${encodeURIComponent(empresa_id)}&almacenp_id=${encodeURIComponent(almacenp_id)}`;
    const data = await apiGet(url);

    if(!data || data.ok === false){
      setTopAlert("danger", `<i class="fa-solid fa-triangle-exclamation"></i> Error al cargar tablero: ${safeText(data?.error||"")}`);
      renderTablero([]);
      return;
    }

    const items = data.data || [];
    renderTablero(items);
  }

  // Modal OCs (consume tus UIs existentes si ya resuelven queries)
  async function cargarOCsPendientes(){
    ocError.style.display = "none";
    ocError.innerHTML = "";
    tbodyOCs.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr>`;

    if(!visitaActiva?.id_visita){
      tbodyOCs.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Selecciona una visita</td></tr>`;
      return;
    }

    // Tu API patios debe exponer esto (si ya existe en tu api_control_patios, perfecto)
    const url = `${API_PATIOS}?action=oc_pendientes&id_visita=${encodeURIComponent(visitaActiva.id_visita)}&empresa_id=${encodeURIComponent(empresa_id)}&almacenp_id=${encodeURIComponent(almacenp_id)}`;
    const data = await apiGet(url);

    if(!data || data.ok === false){
      ocError.style.display = "block";
      ocError.innerHTML = safeText(data?.error || "No fue posible cargar OCs");
      tbodyOCs.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Sin datos</td></tr>`;
      return;
    }

    const arr = data.data || [];
    if(!arr.length){
      tbodyOCs.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No hay OCs pendientes</td></tr>`;
      return;
    }

    tbodyOCs.innerHTML = arr.map((x,i)=>{
      const id = x.id_origen ?? x.id ?? i;
      const folio = x.folio_origen ?? x.folio ?? "-";
      const prov  = x.proveedor ?? x.proveedor_nombre ?? x.proveedor_id ?? "-";
      const monto = x.monto_total ?? x.monto ?? 0;
      const orig  = `${safeText(x.sistema_origen||"")}/${safeText(x.tabla_origen||"")}`.replace(/^\/|\/$/g,'') || "-";

      return `
        <tr>
          <td><input class="chkOC" type="checkbox" value="${safeText(id)}" data-folio="${safeText(folio)}"></td>
          <td class="mono">${safeText(folio)}</td>
          <td>${safeText(prov)}</td>
          <td class="text-end">${fmtMoney(monto)}</td>
          <td class="small text-muted">${safeText(orig)}</td>
        </tr>
      `;
    }).join("");
  }

  async function vincularOCsSeleccionadas(){
    ocError.style.display = "none";
    ocError.innerHTML = "";

    const chks = Array.from(document.querySelectorAll(".chkOC:checked"));
    if(!chks.length){
      ocError.style.display = "block";
      ocError.innerHTML = "Selecciona al menos una OC.";
      return;
    }

    const ids = chks.map(c=>c.value);

    const url = `${API_PATIOS}?action=vincular_oc`;
    const payload = new URLSearchParams();
    payload.set("empresa_id", empresa_id);
    payload.set("almacenp_id", almacenp_id);
    payload.set("id_visita", visitaActiva.id_visita);
    payload.set("ids", ids.join(","));

    const r = await fetch(url, {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: payload.toString()
    });

    const t = await r.text();
    let data = null;
    try { data = JSON.parse(t); } catch(e){ data = {ok:false, error:"Respuesta no-JSON", raw:t}; }

    if(!data || data.ok === false){
      ocError.style.display = "block";
      ocError.innerHTML = safeText(data?.error || "No fue posible vincular.");
      return;
    }

    modalOC.hide();
    await cargarTablero();
  }

  // Eventos
  empresaSelect.addEventListener("change", async ()=>{
    empresa_id = empresaSelect.value || "";
    almacenp_id = "";
    await cargarAlmacenes(empresa_id);
  });

  btnAplicar.addEventListener("click", async ()=>{
    empresa_id = empresaSelect.value || "";
    almacenp_id = almacenSelect.value || "";

    // Persistencia por querystring (mejor UX)
    const url = new URL(window.location.href);
    if(empresa_id) url.searchParams.set("empresa_id", empresa_id); else url.searchParams.delete("empresa_id");
    if(almacenp_id) url.searchParams.set("almacenp_id", almacenp_id); else url.searchParams.delete("almacenp_id");
    window.history.replaceState({}, "", url.toString());

    await cargarTablero();
  });

  btnRefrescar.addEventListener("click", cargarTablero);

  btnNuevaVisita.addEventListener("click", ()=>{
    if(!empresaSelect.value || !almacenSelect.value){
      setTopAlert("danger", `<i class="fa-solid fa-triangle-exclamation"></i> Selecciona empresa y almacén antes de crear una visita.`);
      return;
    }
    // Reutiliza tu UI existente (si ya la tienes)
    // Ajusta la ruta si cambió en tu proyecto:
    const go = `patios_nueva_visita.php?empresa_id=${encodeURIComponent(empresaSelect.value)}&almacenp_id=${encodeURIComponent(almacenSelect.value)}`;
    window.location.href = go;
  });

  document.addEventListener("click", async (ev)=>{
    const btn = ev.target.closest("[data-action]");
    if(!btn) return;

    const action = btn.getAttribute("data-action");
    const card = btn.closest(".cardVisita");
    if(!card) return;

    const idVisita = card.getAttribute("data-idvisita");
    if(!idVisita) return;

    if(action === "vincular-ocs"){
      visitaActiva = {id_visita: idVisita};
      chkAllOCs.checked = false;
      modalOC.show();
      await cargarOCsPendientes();
    }
  });

  chkAllOCs.addEventListener("change", ()=>{
    const all = Array.from(document.querySelectorAll(".chkOC"));
    all.forEach(c=>c.checked = chkAllOCs.checked);
  });

  btnVincularSeleccionadas.addEventListener("click", vincularOCsSeleccionadas);
  btnRecargarOCs.addEventListener("click", cargarOCsPendientes);

  // Init
  (async function init(){
    await cargarEmpresas();
    if(empresa_id && almacenp_id){
      await cargarTablero();
    }else{
      renderTablero([]);
    }
  })();

})();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
