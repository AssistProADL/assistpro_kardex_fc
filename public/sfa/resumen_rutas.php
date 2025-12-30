<?php
include __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  .kpi-card{
    border-radius: 12px;
    padding: 14px 14px;
    text-align:center;
    border:1px solid #e6eef9;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    min-height: 86px;
  }
  .kpi-card h3{ margin:0; font-size:26px; font-weight:800; }
  .kpi-card small{ color:#6c757d; font-size:12px; }
  .estado-verde{ color:#198754; font-weight:700; }
  .estado-amarillo{ color:#ffc107; font-weight:700; }
  .estado-rojo{ color:#dc3545; font-weight:700; }

  .table-sm td, .table-sm th{
    font-size: 12px;
    padding: .35rem;
    white-space: nowrap;
    vertical-align: middle;
  }
  .ap-hint{ font-size:12px; color:#6c757d; }
  .ap-box{ border:1px solid #e6eef9; border-radius:12px; }
  .ap-actions .btn{ border-radius:10px; }
  .ap-debug{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 12px;
    white-space: pre-wrap;
  }
</style>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="d-flex justify-content-between align-items-end mb-2">
    <div>
      <h4 class="fw-bold mb-0">Dashboard Corporativo | Resumen de Rutas</h4>
      <div class="ap-hint">Planeación semanal + cobertura geográfica. (No carga datos hasta aplicar filtros)</div>
    </div>
    <div class="ap-actions">
      <a href="planeacion_rutas_destinatarios.php" class="btn btn-outline-primary btn-sm">✏ Asignar Clientes</a>
      <a href="geo_distribucion_clientes.php" class="btn btn-outline-success btn-sm">🌍 Georreferencia</a>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="card mb-3 ap-box">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Empresa / Almacén</label>
          <select id="f_empresa" class="form-select form-select-sm">
            <option value="">Cargando...</option>
          </select>
          <div class="ap-hint mt-1" id="hint_empresa"></div>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Ruta</label>
          <select id="f_ruta" class="form-select form-select-sm">
            <option value="0">(Todas)</option>
          </select>
          <div class="ap-hint mt-1" id="hint_ruta"></div>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Desde</label>
          <input id="f_desde" type="date" class="form-control form-control-sm" />
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Hasta</label>
          <input id="f_hasta" type="date" class="form-control form-control-sm" />
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Tipo venta</label>
          <select id="f_tipovta" class="form-select form-select-sm">
            <option value="">(Todas)</option>
            <option value="CONTADO">Contado</option>
            <option value="CREDITO">Crédito</option>
          </select>
        </div>

        <div class="col-md-2 d-grid">
          <button id="btn_actualizar" class="btn btn-primary btn-sm">Actualizar</button>
        </div>

        <div class="col-md-2 d-grid">
          <button id="btn_limpiar" class="btn btn-outline-secondary btn-sm">Limpiar</button>
        </div>

        <div class="col-12 mt-2">
          <div class="ap-hint">
            Endpoint datos: <span class="badge bg-light text-dark" id="badge_api">../api/resumen_rutas_data.php</span>
            <span class="ms-2 badge bg-light text-dark" id="badge_status">Sin consulta</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-2">
      <div class="kpi-card">
        <h3 id="kpi_rutas">—</h3>
        <small>Rutas activas</small>
      </div>
    </div>
    <div class="col-md-2">
      <div class="kpi-card">
        <h3 id="kpi_clientes">—</h3>
        <small>Clientes asignados</small>
      </div>
    </div>
    <div class="col-md-2">
      <div class="kpi-card">
        <h3 id="kpi_pendientes">—</h3>
        <small>Clientes sin ruta</small>
      </div>
    </div>
    <div class="col-md-2">
      <div class="kpi-card">
        <h3 id="kpi_geo">—</h3>
        <small>Cobertura geo</small>
      </div>
    </div>
    <div class="col-md-2">
      <div class="kpi-card">
        <h3 id="kpi_docs">—</h3>
        <small>Documentos</small>
      </div>
    </div>
    <div class="col-md-2">
      <div class="kpi-card">
        <h3 id="kpi_total">$—</h3>
        <small>Total ventas</small>
      </div>
    </div>
  </div>

  <!-- TABLA RESUMEN -->
  <div class="card mb-3 ap-box">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Resumen por Ruta</h6>
        <div class="ap-hint" id="txt_total_rutas">Sin consulta</div>
      </div>

      <div class="table-responsive" style="max-height: 340px; overflow:auto;">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:120px">Acciones</th>
              <th>Ruta</th>
              <th class="text-center">Clientes</th>
              <th class="text-center">Días</th>
              <th class="text-center">CPs</th>
              <th class="text-center">Geo %</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="tabla_rutas">
            <tr>
              <td colspan="7" class="text-center text-muted">Seleccione filtros y presione Actualizar.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-2">
        <button class="btn btn-outline-secondary btn-sm" id="btn_prev">‹ Anterior</button>
        <button class="btn btn-outline-secondary btn-sm" id="btn_next">Siguiente ›</button>
      </div>
      <div class="ap-hint mt-1" id="txt_paginacion">Página —</div>
    </div>
  </div>

  <!-- DISTRIBUCIÓN POR DÍA -->
  <div class="card ap-box">
    <div class="card-body">
      <h6 class="fw-bold mb-2">Distribución por Día</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Día</th>
              <th class="text-center">Rutas</th>
              <th class="text-center">Clientes</th>
            </tr>
          </thead>
          <tbody id="tabla_dias">
            <tr><td colspan="3" class="text-center text-muted">Sin consulta</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- DEBUG -->
  <div class="card mt-3 ap-box">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div class="fw-bold">Diagnóstico</div>
        <button class="btn btn-outline-secondary btn-sm" id="btn_toggle_debug">Mostrar/Ocultar</button>
      </div>
      <div id="debug_box" class="ap-debug mt-2" style="display:none;">(sin logs)</div>
    </div>
  </div>

</div>

<script>
/* =========================
   Configuración endpoints (fallbacks)
   ========================= */
const API_RESUMEN = "../api/resumen_rutas_data.php";
const API_RUTAS_CANDIDATOS = [
  "../api/catalogo_rutas.php",
  "../api/rutas_api.php",
  "../api/catalogo_rutas_data.php"
];
const API_ALMACENES_CANDIDATOS = [
  "../api/almacenes.php",
  "../api/catalogo_almacenes.php",
  "../api/almacenes_api.php"
];

document.getElementById("badge_api").textContent = API_RESUMEN;

let rutasCache = [];
let page = 1;
const pageSize = 10;

function logDebug(msg, obj=null){
  const box = document.getElementById("debug_box");
  const ts = new Date().toISOString().slice(11,19);
  let line = `[${ts}] ${msg}`;
  if(obj!==null){
    try{ line += "\n" + JSON.stringify(obj, null, 2); }catch(e){}
  }
  box.textContent = (box.textContent === "(sin logs)") ? line : (box.textContent + "\n\n" + line);
}

function toggleDebug(){
  const box = document.getElementById("debug_box");
  box.style.display = (box.style.display === "none") ? "block" : "none";
}
document.getElementById("btn_toggle_debug").addEventListener("click", toggleDebug);

/* =========================
   Helpers para autodetectar estructura
   ========================= */
function pickField(obj, names){
  for(const n of names){
    if(obj && Object.prototype.hasOwnProperty.call(obj, n)) return obj[n];
  }
  return null;
}
function normalizeAlmacenRow(a){
  const id = pickField(a, ["id","ID","clave","Clave","IdEmpresa","empresa","cve_almacenp","cve_almac","Cve_Almac"]);
  const nombre = pickField(a, ["nombre","Nombre","descripcion","Descripcion","razonsocial","RazonSocial","almacen","Almacen"]);
  if(id===null) return null;
  return { id: String(id), nombre: nombre ? String(nombre) : ("Almacén " + id) };
}
function normalizeRutaRow(r){
  const id = pickField(r, ["ID_Ruta","id_ruta","id","IdRuta","ruta_id"]);
  const nombre = pickField(r, ["descripcion","Descripcion","cve_ruta","Cve_Ruta","ruta","Ruta"]);
  if(id===null) return null;
  return { id: String(id), nombre: nombre ? String(nombre) : ("Ruta " + id) };
}

/* =========================
   Fetch con fallbacks
   ========================= */
async function fetchFirstOk(urls){
  for(const u of urls){
    try{
      const res = await fetch(u, {cache:"no-store"});
      if(!res.ok) { logDebug("Endpoint no OK: " + u + " ("+res.status+")"); continue; }
      const json = await res.json();
      if(json && !json.error) return {url:u, data:json};
      // algunos APIs regresan {data:[...]}
      if(json && json.data && Array.isArray(json.data)) return {url:u, data:json.data};
      if(Array.isArray(json)) return {url:u, data:json};
      logDebug("Endpoint responde pero sin data usable: " + u, json);
    }catch(e){
      logDebug("Error fetch: " + u, {error:String(e)});
    }
  }
  return null;
}

/* =========================
   Cargar almacenes
   ========================= */
async function cargarAlmacenes(){
  const sel = document.getElementById("f_empresa");
  sel.innerHTML = `<option value="">Cargando...</option>`;
  document.getElementById("hint_empresa").textContent = "";

  const resp = await fetchFirstOk(API_ALMACENES_CANDIDATOS);
  if(!resp){
    sel.innerHTML = `<option value="">(Sin almacenes)</option>`;
    document.getElementById("hint_empresa").textContent = "No pude cargar almacenes. Revisa endpoint almacenes.php / catalogo_almacenes.php.";
    return;
  }

  let arr = resp.data;
  if(resp.data && resp.data.data && Array.isArray(resp.data.data)) arr = resp.data.data;
  if(!Array.isArray(arr)) arr = [];

  const norm = arr.map(normalizeAlmacenRow).filter(x=>x);
  norm.sort((a,b)=>a.nombre.localeCompare(b.nombre, "es"));

  sel.innerHTML = `<option value="">Seleccione</option>`;
  norm.forEach(a=>{
    sel.innerHTML += `<option value="${a.id}">${a.nombre}</option>`;
  });

  document.getElementById("hint_empresa").textContent = `Fuente: ${resp.url} | ${norm.length} almacenes`;

  // si hay exactamente 1 almacén, lo seleccionamos (modo demo) y cargamos rutas
  if(norm.length === 1){
    sel.value = norm[0].id;
    await cargarRutas();
  }
}

/* =========================
   Cargar rutas
   ========================= */
async function cargarRutas(){
  const selEmp = document.getElementById("f_empresa");
  const idEmp = selEmp.value;

  const selRuta = document.getElementById("f_ruta");
  selRuta.innerHTML = `<option value="0">(Todas)</option>`;
  document.getElementById("hint_ruta").textContent = "";

  // Muchas veces catalogo_rutas regresa todas; si tu endpoint requiere filtro lo agregas aquí:
  // const urls = API_RUTAS_CANDIDATOS.map(u => u + (u.includes("?") ? "&" : "?") + "IdEmpresa=" + encodeURIComponent(idEmp));
  const urls = API_RUTAS_CANDIDATOS;

  const resp = await fetchFirstOk(urls);
  if(!resp){
    document.getElementById("hint_ruta").textContent = "No pude cargar rutas (endpoint).";
    return;
  }

  let arr = resp.data;
  if(resp.data && resp.data.data && Array.isArray(resp.data.data)) arr = resp.data.data;
  if(!Array.isArray(arr)) arr = [];

  const norm = arr.map(normalizeRutaRow).filter(x=>x);
  norm.sort((a,b)=>a.nombre.localeCompare(b.nombre, "es"));
  norm.forEach(r=>{
    selRuta.innerHTML += `<option value="${r.id}">${r.nombre}</option>`;
  });

  document.getElementById("hint_ruta").textContent = `Fuente: ${resp.url} | ${norm.length} rutas`;
}

/* =========================
   Formato moneda
   ========================= */
function money(v){
  const n = Number(v || 0);
  return n.toLocaleString("es-MX", {style:"currency", currency:"MXN"});
}

/* =========================
   Pintar tablas + paginación
   ========================= */
function renderRutas(){
  const tb = document.getElementById("tabla_rutas");
  tb.innerHTML = "";

  const total = rutasCache.length;
  if(total === 0){
    tb.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Sin datos para el filtro.</td></tr>`;
    document.getElementById("txt_total_rutas").textContent = "0 rutas";
    document.getElementById("txt_paginacion").textContent = "Página —";
    return;
  }

  const pages = Math.max(1, Math.ceil(total / pageSize));
  if(page > pages) page = pages;
  if(page < 1) page = 1;

  const start = (page-1)*pageSize;
  const slice = rutasCache.slice(start, start + pageSize);

  slice.forEach(r=>{
    let estadoTxt = "", estadoCls = "";
    if(r.estado === "verde"){ estadoTxt="🟢 OK"; estadoCls="estado-verde"; }
    if(r.estado === "amarillo"){ estadoTxt="🟡 Parcial"; estadoCls="estado-amarillo"; }
    if(r.estado === "rojo"){ estadoTxt="🔴 Atención"; estadoCls="estado-rojo"; }

    const rutaLabel = r.ruta || r.cve_ruta || "Ruta";
    const rutaId = r.cve_ruta || "";

    tb.innerHTML += `
      <tr>
        <td>
          <a class="btn btn-outline-success btn-sm" href="geo_distribucion_clientes.php?ruta_id=${encodeURIComponent(rutaId)}">Mapa</a>
        </td>
        <td>${rutaLabel}</td>
        <td class="text-center">${Number(r.clientes||0)}</td>
        <td class="text-center">${r.dias || "-"}</td>
        <td class="text-center">${Number(r.cps||0)}</td>
        <td class="text-center">${Number(r.geo_pct||0)}%</td>
        <td class="${estadoCls}">${estadoTxt}</td>
      </tr>
    `;
  });

  document.getElementById("txt_total_rutas").textContent =
    `${total.toLocaleString("es-MX")} rutas (página ${page}/${Math.ceil(total/pageSize)})`;

  document.getElementById("txt_paginacion").textContent =
    `Página ${page} de ${Math.ceil(total/pageSize)} | Mostrando ${slice.length} de ${total}`;
}

/* =========================
   Pintar dashboard (respuesta API)
   ========================= */
function pintarDashboard(resp){
  // KPIs
  const k = resp.kpis || {};
  document.getElementById("kpi_rutas").textContent = (k.rutas_activas ?? "—");
  document.getElementById("kpi_clientes").textContent = (k.clientes_asignados ?? "—");
  document.getElementById("kpi_pendientes").textContent = (k.clientes_sin_ruta ?? "—");
  document.getElementById("kpi_geo").textContent = (k.cobertura_geo ?? "—") + "%";
  document.getElementById("kpi_docs").textContent = (k.documentos ?? "—");
  document.getElementById("kpi_total").textContent = money(k.total_ventas ?? 0);

  // Rutas
  rutasCache = Array.isArray(resp.rutas) ? resp.rutas : [];
  page = 1;
  renderRutas();

  // Días
  const tbDias = document.getElementById("tabla_dias");
  tbDias.innerHTML = "";
  const dias = Array.isArray(resp.dias) ? resp.dias : [];
  if(dias.length === 0){
    tbDias.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>`;
  }else{
    dias.forEach(d=>{
      tbDias.innerHTML += `
        <tr>
          <td>${d.dia}</td>
          <td class="text-center">${Number(d.rutas||0)}</td>
          <td class="text-center">${Number(d.clientes||0)}</td>
        </tr>
      `;
    });
  }
}

/* =========================
   Consultar API
   ========================= */
async function cargarDashboard(){
  const idEmp = document.getElementById("f_empresa").value;
  const idRuta = document.getElementById("f_ruta").value || "0";
  const desde = document.getElementById("f_desde").value;
  const hasta = document.getElementById("f_hasta").value;
  const tipoVta = document.getElementById("f_tipovta").value;

  if(!idEmp){
    document.getElementById("badge_status").textContent = "Seleccione Empresa/Almacén";
    return;
  }

  const fd = new FormData();
  fd.append("IdEmpresa", idEmp);
  fd.append("IdRuta", idRuta);
  if(desde) fd.append("fecha_ini", desde);
  if(hasta) fd.append("fecha_fin", hasta);
  if(tipoVta) fd.append("TipoVta", tipoVta);

  document.getElementById("badge_status").textContent = "Consultando...";
  logDebug("POST " + API_RESUMEN, {IdEmpresa:idEmp, IdRuta:idRuta, fecha_ini:desde, fecha_fin:hasta, TipoVta:tipoVta});

  try{
    const r = await fetch(API_RESUMEN, {method:"POST", body:fd});
    const j = await r.json();

    if(j && j.error){
      document.getElementById("badge_status").textContent = "Error API";
      logDebug("API error", j);
      // limpiar UI pero mostrar mensaje
      document.getElementById("tabla_rutas").innerHTML = `<tr><td colspan="7" class="text-center text-danger">${j.error}</td></tr>`;
      document.getElementById("tabla_dias").innerHTML  = `<tr><td colspan="3" class="text-center text-danger">${j.error}</td></tr>`;
      return;
    }

    document.getElementById("badge_status").textContent = "OK";
    logDebug("API OK", j);
    pintarDashboard(j);

  }catch(e){
    document.getElementById("badge_status").textContent = "Error fetch";
    logDebug("Fetch exception", {error:String(e)});
  }
}

/* =========================
   Limpiar
   ========================= */
function limpiar(){
  document.getElementById("f_empresa").value = "";
  document.getElementById("f_ruta").value = "0";
  document.getElementById("f_desde").value = "";
  document.getElementById("f_hasta").value = "";
  document.getElementById("f_tipovta").value = "";

  document.getElementById("kpi_rutas").textContent="—";
  document.getElementById("kpi_clientes").textContent="—";
  document.getElementById("kpi_pendientes").textContent="—";
  document.getElementById("kpi_geo").textContent="—";
  document.getElementById("kpi_docs").textContent="—";
  document.getElementById("kpi_total").textContent="$—";

  rutasCache = [];
  document.getElementById("tabla_rutas").innerHTML = `<tr><td colspan="7" class="text-center text-muted">Seleccione filtros y presione Actualizar.</td></tr>`;
  document.getElementById("tabla_dias").innerHTML  = `<tr><td colspan="3" class="text-center text-muted">Sin consulta</td></tr>`;
  document.getElementById("badge_status").textContent = "Sin consulta";
  document.getElementById("txt_total_rutas").textContent = "Sin consulta";
  document.getElementById("txt_paginacion").textContent = "Página —";
}

/* =========================
   Paginación
   ========================= */
document.getElementById("btn_prev").addEventListener("click", ()=>{
  page--; renderRutas();
});
document.getElementById("btn_next").addEventListener("click", ()=>{
  page++; renderRutas();
});

/* =========================
   Eventos
   ========================= */
document.getElementById("btn_actualizar").addEventListener("click", cargarDashboard);
document.getElementById("btn_limpiar").addEventListener("click", limpiar);
document.getElementById("f_empresa").addEventListener("change", async ()=>{
  await cargarRutas();
});

/* =========================
   Init
   ========================= */
document.addEventListener("DOMContentLoaded", async ()=>{
  // Fechas default (últimos 7 días)
  const today = new Date();
  const toISO = d => new Date(d.getTime() - d.getTimezoneOffset()*60000).toISOString().slice(0,10);
  const d7 = new Date(today); d7.setDate(d7.getDate()-7);

  document.getElementById("f_hasta").value = toISO(today);
  document.getElementById("f_desde").value = toISO(d7);

  await cargarAlmacenes();
});
</script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
?>
