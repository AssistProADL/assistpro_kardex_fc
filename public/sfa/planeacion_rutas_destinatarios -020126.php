 
<?php
include __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  .table-sm td, .table-sm th{ padding:.25rem; font-size:11px; white-space:nowrap; }
  #map{ height: 520px; width: 100%; border-radius: 12px; border:1px solid #e6eef9; }
  .ap-hint{ font-size:12px; color:#6c757d; }
  .ap-box{ border:1px solid #e6eef9; border-radius:12px; }
  .badge-soft{ display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; background:#f1f5ff; border:1px solid #dbe7ff; }
  .mini{ font-size:12px; color:#6c757d; }
  .map-tools .btn{ border-radius:10px; }
  .ap-debug{ font-family: ui-monospace, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:12px; white-space:pre-wrap; }
</style>

<!-- IMPORTANTE: drawing + geometry para geocerca -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC5xF7JtKzw9cTRRXcDAqTThbYnMCiYOVM&libraries=drawing,geometry&callback=initMap" async defer></script>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="row mb-2">
    <div class="col-md-6">
      <h4 class="fw-bold mb-0">Planeación de Rutas | Asignación de Clientes</h4>
      <div class="ap-hint">Selecciona Almacén → carga clientes → usa geocerca para seleccionar masivamente.</div>
    </div>
    <div class="col-md-6 text-end">
      <a href="resumen_rutas.php" class="btn btn-outline-primary btn-sm">📊 Resumen</a>
      <a href="geo_distribucion_clientes.php" class="btn btn-outline-success btn-sm">🌍 Georreferencia</a>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="card mb-2 ap-box">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Almacén (IdEmpresa)</label>
          <select id="f_almacen" class="form-select form-select-sm">
            <option value="">Cargando...</option>
          </select>
          <div class="ap-hint" id="hint_almacen"></div>
        </div>

        <div class="col-md-5">
          <label class="form-label mb-1">Buscar</label>
          <input id="f_buscar" class="form-control form-control-sm"
                 placeholder="Cliente / Destinatario / Colonia / CP">
        </div>

        <div class="col-md-2">
          <button id="btn_buscar" class="btn btn-primary btn-sm w-100">Buscar</button>
        </div>

        <div class="col-md-2">
          <button id="btn_refrescar" class="btn btn-outline-secondary btn-sm w-100">Refrescar</button>
        </div>

        <div class="col-12 mt-1">
          <span class="badge-soft" id="k_total">0 clientes</span>
          <span class="badge-soft" id="k_gps">0 con GPS</span>
          <span class="badge-soft" id="k_sel">0 seleccionados</span>
          <span class="ms-2 badge bg-light text-dark" id="badge_status">Sin consulta</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ACCIONES MASIVAS -->
  <div class="card mb-2 ap-box">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="form-label fw-bold mb-1">Ruta destino (global)</label>
          <select id="ruta_global" class="form-select form-select-sm">
            <option value="">Cargando...</option>
          </select>
          <div class="ap-hint" id="hint_rutas"></div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold mb-1">Días de visita (global)</label><br>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Lu"> L</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Ma"> M</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Mi"> Mi</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Ju"> J</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Vi"> V</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Sa"> S</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Do"> Do</label>
        </div>

        <div class="col-md-3 text-end">
          <button id="btn_guardar" class="btn btn-success btn-sm">Guardar planeación</button>
        </div>

      </div>
    </div>
  </div>

  <!-- LAYOUT: TABLA + MAPA -->
  <div class="row g-2">
    <div class="col-lg-8">
      <div class="card ap-box">
        <div class="card-body p-1">
          <div style="max-height:520px; overflow:auto;">
            <table class="table table-bordered table-sm align-middle mb-0">
              <thead class="table-light" style="position:sticky; top:0; z-index:2;">
                <tr>
                  <th style="width:36px"><input type="checkbox" id="chk_all"></th>
                  <th>Cliente</th>
                  <th>Destinatario</th>
                  <th>Dirección</th>
                  <th>Colonia</th>
                  <th>CP</th>
                  <th>Ciudad</th>
                  <th>Estado</th>
                  <th>Lat</th>
                  <th>Lng</th>
                  <th>Ruta Act</th>
                  <th>Ruta New</th>
                  <th>Días</th>
                  <th>Seq</th>
                </tr>
              </thead>
              <tbody id="tabla_destinatarios">
                <tr>
                  <td colspan="14" class="text-center text-muted">Seleccione un almacén</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card ap-box mt-2">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold">Diagnóstico</div>
            <button class="btn btn-outline-secondary btn-sm" id="btn_toggle_debug">Mostrar/Ocultar</button>
          </div>
          <div id="debug_box" class="ap-debug mt-2" style="display:none;">(sin logs)</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card ap-box">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-end mb-2">
            <div>
              <div class="fw-bold">Mapa (Selección por Geocerca)</div>
              <div class="mini">Dibuja polígono → selecciona clientes en la tabla.</div>
            </div>
            <div class="map-tools d-flex gap-2">
              <button class="btn btn-outline-dark btn-sm" id="btn_geocerca">🧿 Geocerca</button>
              <button class="btn btn-outline-danger btn-sm" id="btn_limpiar_geo">🧹 Limpiar</button>
            </div>
          </div>
          <div id="map"></div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
let pagina = 1;
let RUTAS = [];          // [{id,nombre}]
let CLIENTES = [];       // data cargada del API
let map, drawingManager, polygon = null;
let markers = [];
let byId = new Map();    // id_destinatario -> row record
let selected = new Set();

const API_ALMACENES_CANDIDATOS = [
  "../api/almacenes.php",
  "../api/catalogo_almacenes.php",
  "../api/almacenes_api.php"
];
const API_RUTAS_CANDIDATOS = [
  "../api/catalogo_rutas.php",
  "../api/rutas_api.php"
];

function logDebug(msg, obj=null){
  const box = document.getElementById("debug_box");
  const ts = new Date().toISOString().slice(11,19);
  let line = `[${ts}] ${msg}`;
  if(obj!==null){
    try{ line += "\n" + JSON.stringify(obj, null, 2); }catch(e){}
  }
  box.textContent = (box.textContent === "(sin logs)") ? line : (box.textContent + "\n\n" + line);
}

document.getElementById("btn_toggle_debug").addEventListener("click", ()=>{
  const box = document.getElementById("debug_box");
  box.style.display = (box.style.display === "none") ? "block" : "none";
});

function pickField(obj, names){
  for(const n of names){
    if(obj && Object.prototype.hasOwnProperty.call(obj, n)) return obj[n];
  }
  return null;
}
function normalizeAlmacenRow(a){
  const id = pickField(a, ["IdEmpresa","id","ID","clave","Clave","empresa","cve_almacenp","cve_almac","Cve_Almac"]);
  const nombre = pickField(a, ["nombre","Nombre","descripcion","Descripcion","razonsocial","RazonSocial","almacen","Almacen"]);
  if(id===null) return null;
  return { id: String(id), nombre: nombre ? String(nombre) : ("Almacén " + id) };
}
function normalizeRutaRow(r){
  const id = pickField(r, ["ID_Ruta","id_ruta","id","IdRuta","ruta_id"]);
  const nombre = pickField(r, ["descripcion","Descripcion","cve_ruta","Cve_Ruta","ruta","Ruta","nombre","Nombre"]);
  if(id===null) return null;
  return { id: String(id), nombre: nombre ? String(nombre) : ("Ruta " + id) };
}

async function fetchFirstOk(urls, opts=null){
  for(const u of urls){
    try{
      const res = await fetch(u, opts || {cache:"no-store"});
      if(!res.ok){ logDebug("Endpoint no OK: "+u, {status:res.status}); continue; }
      const json = await res.json();
      if(json && json.error){ logDebug("Endpoint error: "+u, json); continue; }
      if(Array.isArray(json)) return {url:u, data:json};
      if(json && Array.isArray(json.data)) return {url:u, data:json.data};
      // algunos regresan {almacenes:[...]}
      for(const k of ["almacenes","rutas","items","rows"]){
        if(json && Array.isArray(json[k])) return {url:u, data:json[k]};
      }
      logDebug("Endpoint sin formato usable: "+u, json);
    }catch(e){
      logDebug("Fetch error: "+u, {error:String(e)});
    }
  }
  return null;
}

/* ===========================
   MAPA + GEOCERCA
   =========================== */
function initMap(){
  map = new google.maps.Map(document.getElementById('map'),{
    zoom: 11,
    center: {lat: 19.432608, lng: -99.133209}
  });

  drawingManager = new google.maps.drawing.DrawingManager({
    drawingControl: false,
    polygonOptions: {
      fillColor: "#1f6feb",
      fillOpacity: 0.12,
      strokeWeight: 2,
      clickable: false,
      editable: true,
      zIndex: 1
    }
  });
  drawingManager.setMap(map);

  google.maps.event.addListener(drawingManager, 'polygoncomplete', function(poly){
    if (polygon) polygon.setMap(null);
    polygon = poly;
    drawingManager.setDrawingMode(null);
    recomputeSelectionFromPolygon();

    google.maps.event.addListener(polygon.getPath(), 'set_at', recomputeSelectionFromPolygon);
    google.maps.event.addListener(polygon.getPath(), 'insert_at', recomputeSelectionFromPolygon);
    google.maps.event.addListener(polygon.getPath(), 'remove_at', recomputeSelectionFromPolygon);
  });

  document.getElementById("btn_geocerca").addEventListener("click", ()=>{
    if(!drawingManager) return;
    drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
  });

  document.getElementById("btn_limpiar_geo").addEventListener("click", ()=>{
    if(polygon){ polygon.setMap(null); polygon=null; }
    selected.clear();
    syncSelectedToTable();
  });
}

function paintMarkers(rows){
  markers.forEach(m=>m.setMap(null));
  markers = [];

  if(!rows || rows.length===0) return;

  const bounds = new google.maps.LatLngBounds();
  rows.forEach(r=>{
    const lat = parseFloat(r.latitud);
    const lng = parseFloat(r.longitud);
    if(!isFinite(lat) || !isFinite(lng)) return;

    const pos = {lat,lng};
    bounds.extend(pos);

    const marker = new google.maps.Marker({
      position: pos,
      map: map,
      title: r.destinatario || r.cliente || ("Destinatario "+r.id)
    });

    marker.__id = String(r.id); // id_destinatario
    markers.push(marker);
  });

  map.fitBounds(bounds);
}

function recomputeSelectionFromPolygon(){
  if(!polygon) return;
  selected.clear();

  markers.forEach(m=>{
    const inside = google.maps.geometry.poly.containsLocation(m.getPosition(), polygon);
    if(inside) selected.add(String(m.__id));
  });

  syncSelectedToTable();
}

function syncSelectedToTable(){
  document.querySelectorAll(".chk-row").forEach(chk=>{
    chk.checked = selected.has(String(chk.dataset.id));
  });
  document.getElementById("k_sel").textContent = `${selected.size} seleccionados`;
}

/* ===========================
   CARGA SELECTS (robusto)
   =========================== */
async function cargarAlmacenes(){
  const sel = document.getElementById("f_almacen");
  sel.innerHTML = `<option value="">Cargando...</option>`;
  document.getElementById("hint_almacen").textContent = "";

  const resp = await fetchFirstOk(API_ALMACENES_CANDIDATOS);
  if(!resp){
    sel.innerHTML = `<option value="">(Sin almacenes)</option>`;
    document.getElementById("hint_almacen").textContent = "No pude cargar almacenes (revisar endpoint).";
    return;
  }

  const norm = (resp.data||[]).map(normalizeAlmacenRow).filter(x=>x);
  norm.sort((a,b)=>a.nombre.localeCompare(b.nombre,"es"));

  sel.innerHTML = `<option value="">Seleccione</option>`;
  norm.forEach(a=>{
    sel.innerHTML += `<option value="${a.id}">${a.nombre}</option>`;
  });

  document.getElementById("hint_almacen").textContent = `Fuente: ${resp.url} | ${norm.length} almacenes`;
}

async function cargarRutas(almacen){
  const sel = document.getElementById("ruta_global");
  sel.innerHTML = `<option value="">Cargando...</option>`;
  document.getElementById("hint_rutas").textContent = "";

  // algunos endpoints requieren almacén, otros no. probamos ambos
  const urls = [];
  API_RUTAS_CANDIDATOS.forEach(u=>{
    urls.push(u);
    if(almacen) urls.push(u + (u.includes("?") ? "&" : "?") + "almacen=" + encodeURIComponent(almacen));
    if(almacen) urls.push(u + (u.includes("?") ? "&" : "?") + "IdEmpresa=" + encodeURIComponent(almacen));
  });

  const resp = await fetchFirstOk(urls);
  if(!resp){
    sel.innerHTML = `<option value="">(Sin rutas)</option>`;
    document.getElementById("hint_rutas").textContent = "No pude cargar rutas (revisar endpoint).";
    RUTAS = [];
    return;
  }

  RUTAS = (resp.data||[]).map(normalizeRutaRow).filter(x=>x);
  RUTAS.sort((a,b)=>a.nombre.localeCompare(b.nombre,"es"));

  sel.innerHTML = `<option value="">Seleccione Ruta</option>`;
  RUTAS.forEach(r=>{
    sel.innerHTML += `<option value="${r.id}">${r.nombre}</option>`;
  });

  document.getElementById("hint_rutas").textContent = `Fuente: ${resp.url} | ${RUTAS.length} rutas`;
}

/* ===========================
   CARGA DATOS
   =========================== */
async function cargarDatos(){
  const alm = document.getElementById('f_almacen').value;
  if(!alm) return;

  const fd = new FormData();
  // tolerante: mandamos ambos nombres
  fd.append('almacen', alm);
  fd.append('IdEmpresa', alm);
  fd.append('buscar', document.getElementById('f_buscar').value || '');
  fd.append('pagina', pagina);

  document.getElementById("badge_status").textContent = "Consultando...";
  logDebug("POST clientes_asignacion_data.php", {almacen:alm, buscar:document.getElementById('f_buscar').value, pagina});

  try{
    const resp = await fetch('../api/clientes_asignacion_data.php',{method:'POST',body:fd});
    const j = await resp.json();

    if(j && j.error){
      document.getElementById("badge_status").textContent = "Error API";
      logDebug("API error clientes_asignacion_data.php", j);
      renderTabla({data:[]}, j.error);
      return;
    }

    document.getElementById("badge_status").textContent = "OK";
    logDebug("API OK clientes_asignacion_data.php", j);
    renderTabla(j);

  }catch(e){
    document.getElementById("badge_status").textContent = "Error fetch";
    logDebug("Fetch exception", {error:String(e)});
    renderTabla({data:[]}, "Error de comunicación con API.");
  }
}

/* ===========================
   RENDER TABLA + MAPA
   =========================== */
function renderTabla(resp, errMsg=null){
  const tb = document.getElementById('tabla_destinatarios');
  tb.innerHTML = '';

  const rows = (resp && Array.isArray(resp.data)) ? resp.data : [];
  CLIENTES = rows;

  if(errMsg){
    tb.innerHTML = `<tr><td colspan="14" class="text-center text-danger">${errMsg}</td></tr>`;
    document.getElementById("k_total").textContent = `0 clientes`;
    document.getElementById("k_gps").textContent = `0 con GPS`;
    document.getElementById("k_sel").textContent = `0 seleccionados`;
    paintMarkers([]);
    return;
  }

  if(rows.length===0){
    tb.innerHTML = `<tr><td colspan="14" class="text-center text-muted">Sin datos</td></tr>`;
    document.getElementById("k_total").textContent = `0 clientes`;
    document.getElementById("k_gps").textContent = `0 con GPS`;
    document.getElementById("k_sel").textContent = `0 seleccionados`;
    paintMarkers([]);
    return;
  }

  // KPIs
  let gps = 0;
  rows.forEach(r=>{
    if((r.latitud||'')!=='' && (r.longitud||'')!=='') gps++;
  });
  document.getElementById("k_total").textContent = `${rows.length} clientes`;
  document.getElementById("k_gps").textContent = `${gps} con GPS`;
  document.getElementById("k_sel").textContent = `${selected.size} seleccionados`;

  // Render
  rows.forEach(r=>{
    const id = String(r.id); // id_destinatario
    const optRutas = [`<option value="">(global)</option>`]
      .concat(RUTAS.map(x=>`<option value="${x.id}">${x.nombre}</option>`))
      .join('');

    tb.innerHTML += `
      <tr data-id="${id}">
        <td><input type="checkbox" class="chk-row" data-id="${id}"></td>
        <td>[${r.clave_cliente ?? ''}] ${r.cliente ?? ''}</td>
        <td>[${r.id}] ${r.clave_destinatario ?? ''} ${r.destinatario ?? ''}</td>
        <td>${r.direccion ?? ''}</td>
        <td>${r.colonia ?? ''}</td>
        <td>${r.postal ?? ''}</td>
        <td>${r.ciudad ?? ''}</td>
        <td>${r.estado ?? ''}</td>
        <td>${r.latitud ?? ''}</td>
        <td>${r.longitud ?? ''}</td>
        <td>${r.ruta ?? '--'}</td>
        <td>
          <select class="form-select form-select-sm ruta-fila">
            ${optRutas}
          </select>
        </td>
        <td class="dias-fila">
          <label class="me-1"><input type="checkbox" value="Lu">L</label>
          <label class="me-1"><input type="checkbox" value="Ma">M</label>
          <label class="me-1"><input type="checkbox" value="Mi">Mi</label>
          <label class="me-1"><input type="checkbox" value="Ju">J</label>
          <label class="me-1"><input type="checkbox" value="Vi">V</label>
          <label class="me-1"><input type="checkbox" value="Sa">S</label>
          <label class="me-1"><input type="checkbox" value="Do">Do</label>
        </td>
        <td><input type="number" class="form-control form-control-sm secuencia" style="width:60px"></td>
      </tr>
    `;
  });

  // eventos check individuales
  document.querySelectorAll(".chk-row").forEach(chk=>{
    chk.addEventListener("change", ()=>{
      const id = String(chk.dataset.id);
      if(chk.checked) selected.add(id); else selected.delete(id);
      document.getElementById("k_sel").textContent = `${selected.size} seleccionados`;
    });
  });

  // Pinta mapa con los que tienen GPS
  paintMarkers(rows);
}

/* ===========================
   GUARDAR
   =========================== */
document.getElementById('btn_guardar').onclick = async ()=>{

  const almacen = document.getElementById('f_almacen').value;
  const rutaGlobal = document.getElementById('ruta_global').value;
  const diasGlobal = [...document.querySelectorAll('.dia-global:checked')].map(d=>d.value);

  const items = [];
  document.querySelectorAll('#tabla_destinatarios tr').forEach(tr=>{
    const chk = tr.querySelector('.chk-row');
    if(!chk || !chk.checked) return;

    items.push({
      id_destinatario: chk.dataset.id,
      ruta: tr.querySelector('.ruta-fila').value,
      dias: [...tr.querySelectorAll('.dias-fila input:checked')].map(d=>d.value),
      secuencia: tr.querySelector('.secuencia').value
    });
  });

  if(items.length===0){ alert('Seleccione al menos un destinatario'); return; }
  if(!rutaGlobal && items.every(i=>!i.ruta)){ alert('Seleccione ruta'); return; }
  if(diasGlobal.length===0 && items.every(i=>i.dias.length===0)){ alert('Seleccione días'); return; }

  try{
    const resp = await fetch('../api/clientes_asignacion_save.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        almacen: almacen,
        IdEmpresa: almacen,
        ruta_global: rutaGlobal,
        dias_global: diasGlobal,
        items: items
      })
    }).then(r=>r.json());

    if(resp.error){ alert(resp.error); return; }
    alert(resp.mensaje || 'Planeación guardada');
    cargarDatos();
  }catch(e){
    alert('Error guardando: ' + e);
  }
};

/* ===========================
   EVENTOS
   =========================== */
document.getElementById('btn_buscar').onclick=()=>{ pagina=1; cargarDatos(); };
document.getElementById('btn_refrescar').onclick=()=>{ cargarDatos(); };

document.getElementById('chk_all').onchange = e => {
  document.querySelectorAll('.chk-row').forEach(c=>{
    c.checked = e.target.checked;
    const id = String(c.dataset.id);
    if(c.checked) selected.add(id); else selected.delete(id);
  });
  document.getElementById("k_sel").textContent = `${selected.size} seleccionados`;
};

document.addEventListener('DOMContentLoaded', async ()=>{
  await cargarAlmacenes();

  document.getElementById('f_almacen').addEventListener('change', async e=>{
    const alm = e.target.value;
    selected.clear();
    if(alm){
      await cargarRutas(alm);
      await cargarDatos();
    }
  });
});
</script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
?>
