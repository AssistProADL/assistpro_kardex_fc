<?php
// geo_distribucion_clientes.php (FIX)
// - Arregla consumo de APIs que viven en /public/api/sfa/
// - Soporta respuestas tipo: array directo  OR  {success:true,data:[...]}
// - Mantiene compatibilidad con endpoints legacy si existieran

require_once __DIR__ . '/../../app/db.php';

// Si manejas sesión/usuario, déjalo tal cual en tu proyecto.
// session_start();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Georreferencia | Distribución de Clientes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Mantén tus estilos corporativos / menú global tal como lo uses -->
  

  <style>
    /* Ajustes mínimos, sin tocar tu diseño base */
    #map { width: 100%; height: calc(100vh - 210px); border-radius: 10px; }
    .ap-card { border: 1px solid #e8eef6; border-radius: 12px; padding: 12px; background: #fff; }
    .ap-row { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
    .ap-row .ap-field { min-width: 240px; flex: 1; }
    label { font-size: 12px; color:#52637a; }
    select, input { width:100%; padding:8px 10px; border:1px solid #d7e2f1; border-radius:10px; font-size:12px; }
    .btn { padding:8px 12px; border-radius:10px; border:1px solid transparent; cursor:pointer; font-size:12px; }
    .btn-primary { background:#0d6efd; color:#fff; }
    .btn-outline { background:#fff; border-color:#cfe0f7; color:#0d6efd; }
    .muted { font-size:11px; color:#7b8aa0; }
    .pill { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; background:#eef5ff; color:#0d6efd; }
    .log { font-size:11px; color:#6b7c92; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
  </style>
</head>

<body class="ap-body">
<?php
// Menú global si aplica en tu estándar
 include __DIR__ . '/../bi/_menu_global.php';
?>

<div class="ap-page" style="padding:14px;">
  <div class="ap-card">
    <div class="ap-row">
      <div class="ap-field">
        <label>Empresa / Almacén</label>
        <select id="f_empresa">
          <option value="">Cargando...</option>
        </select>
        <div class="muted log" id="dbg_alm"></div>
      </div>

      <div class="ap-field">
        <label>Ruta</label>
        <select id="f_ruta">
          <option value="">(Seleccione almacén)</option>
        </select>
        <div class="muted log" id="dbg_rut"></div>
      </div>

      <div class="ap-field" style="max-width:260px;">
        <label>Buscar</label>
        <input id="f_q" placeholder="Cliente / Destinatario / Colonia / CP" />
        <div class="muted">Enter para filtrar en pantalla</div>
      </div>

      <div class="ap-field" style="max-width:160px;">
        <button class="btn btn-primary" id="btn_refresh">Actualizar</button>
        <button class="btn btn-outline" id="btn_clear" style="margin-left:6px;">Limpiar</button>
      </div>

      <div class="ap-field" style="max-width:280px;">
        <div class="muted">Modo</div>
        <span class="pill" id="dbg_status">OK</span>
        <div class="muted log" id="dbg_msg"></div>
      </div>
    </div>
  </div>

  <div style="height:12px;"></div>

  <div id="map"></div>
</div>

<?php
 include __DIR__ . '/../bi/_menu_global_end.php';
?>

<script>
/** =========================
 *  Endpoints (SIN crear APIs nuevas)
 *  ========================= */
const API_ALMACENES = "../api/catalogo_almacenes.php";

// NOTA: en este proyecto, la mayoría de APIs SFA viven en /public/api/sfa/
// (catalogo_rutas, geo_clientes_*, etc). Dejamos fallback por compatibilidad.
const API_RUTAS_PRIMARY   = "../api/sfa/catalogo_rutas.php";
const API_RUTAS_FALLBACK  = "../api/catalogo_rutas.php";

const API_DATA_PRIMARY    = "../api/geo_clientes_data.php";
const API_DATA_FALLBACK   = "../api/geo_clientes_data.php";

const API_APPLY_PRIMARY   = "../api/geo_clientes_apply.php";
const API_APPLY_FALLBACK  = "../api/geo_clientes_apply.php";

// Helpers robustos: intentan URLs en orden hasta obtener JSON válido.
async function fetchJsonTry(urls){
  let lastErr = null;
  for(const u of urls){
    try{
      const r = await fetch(u, { cache:'no-store' });
      if(!r.ok){ lastErr = new Error(`HTTP ${r.status} ${u}`); continue; }
      const j = await r.json();
      return j;
    }catch(e){ lastErr = e; }
  }
  throw lastErr || new Error("No se pudo obtener JSON");
}

async function postTry(urls, body){
  let lastErr = null;
  for(const u of urls){
    try{
      const r = await fetch(u, { method:'POST', body });
      if(!r.ok){ lastErr = new Error(`HTTP ${r.status} ${u}`); continue; }
      const j = await r.json();
      return j;
    }catch(e){ lastErr = e; }
  }
  throw lastErr || new Error("No se pudo enviar POST");
}

/** =========================
 *  UI helpers
 *  ========================= */
const $ = (id)=>document.getElementById(id);
function setStatus(ok, msg){
  $('dbg_status').textContent = ok ? 'OK' : 'ERROR';
  $('dbg_status').style.background = ok ? '#e9f7ef' : '#fdecea';
  $('dbg_status').style.color = ok ? '#157347' : '#b02a37';
  $('dbg_msg').textContent = msg || '';
}

/** =========================
 *  Google Maps + Drawing
 *  ========================= */
let map, drawingManager;
let markers = [];
let selectedIds = new Set(); // clientes incluidos en geocerca (si aplica)
let circles = [];            // geocercas dibujadas

function clearMarkers(){
  markers.forEach(m => m.setMap(null));
  markers = [];
}

function clearGeocercas(){
  circles.forEach(c => c.setMap(null));
  circles = [];
}

function addMarker(cli){
  if(!cli || cli.lat==null || cli.lng==null) return;
  const pos = {lat: parseFloat(cli.lat), lng: parseFloat(cli.lng)};
  if(Number.isNaN(pos.lat) || Number.isNaN(pos.lng)) return;

  const m = new google.maps.Marker({
    map,
    position: pos,
    title: (cli.razonsocial || cli.nombre || cli.Cve_Clte || '').toString()
  });

  m.__cli = cli;
  markers.push(m);
}

function initMap(){
  map = new google.maps.Map(document.getElementById('map'), {
    center: {lat: 20.6736, lng: -103.344},
    zoom: 10,
    mapTypeControl: true,
    streetViewControl: false
  });

  // Drawing manager (círculos)
  drawingManager = new google.maps.drawing.DrawingManager({
    drawingMode: null,
    drawingControl: true,
    drawingControlOptions: {
      position: google.maps.ControlPosition.TOP_CENTER,
      drawingModes: ['circle']
    },
    circleOptions: {
      fillOpacity: 0.15,
      strokeWeight: 2,
      clickable: true,
      editable: true,
      zIndex: 10
    }
  });

  drawingManager.setMap(map);

  google.maps.event.addListener(drawingManager, 'circlecomplete', async function(circle){
    circles.push(circle);

    // Cliente(s) dentro del círculo -> se puede aplicar como geocerca
    const center = circle.getCenter();
    const radius = circle.getRadius();

    const inside = [];
    markers.forEach(m=>{
      const p = m.getPosition();
      const d = google.maps.geometry.spherical.computeDistanceBetween(center, p);
      if(d <= radius) inside.push(m.__cli);
    });

    // Si no hay clientes dentro, no hacemos nada
    if(!inside.length){
      setStatus(false, 'Geocerca sin clientes dentro.');
      return;
    }

    // Aplicar geocerca (si tu API lo soporta)
    try{
      const emp = $('f_empresa').value || '';
      const ruta = $('f_ruta').value || '';
      if(!emp || !ruta){
        setStatus(false, 'Seleccione almacén y ruta antes de aplicar geocerca.');
        return;
      }

      const fd = new FormData();
      fd.append('IdEmpresa', emp);
      fd.append('ruta_id', ruta);
      fd.append('center_lat', center.lat());
      fd.append('center_lng', center.lng());
      fd.append('radius_m', radius);

      // ids de destinatarios/clientes (ajusta si tu API espera otro nombre)
      fd.append('items_json', JSON.stringify(inside.map(x=>({
        id_destinatario: x.id_destinatario ?? x.IdDestinatario ?? x.id ?? null,
        Cve_Clte: x.Cve_Clte ?? x.cve_clte ?? null
      }))));

      const resp = await postTry([API_APPLY_PRIMARY, API_APPLY_FALLBACK], fd);

      if(!resp.ok){
        setStatus(false, resp.error || 'Error aplicando geocerca');
        return;
      }

      setStatus(true, 'Geocerca aplicada correctamente.');
    }catch(e){
      setStatus(false, e.message || 'Error aplicando geocerca.');
    }
  });

  // Carga inicial de filtros + datos
  boot();
}

/** =========================
 *  Data loaders
 *  ========================= */
async function loadAlmacenes(){
  const j = await fetchJsonTry([API_ALMACENES]);
  const arr = (Array.isArray(j) ? j : (j.data || j.almacenes || [])) || [];

  const sel = $('f_empresa');
  sel.innerHTML = `<option value="">Seleccione almacén</option>`;

  arr.forEach(x=>{
    const id = x.IdEmpresa ?? x.id_empresa ?? x.id ?? x.IdAlmacen ?? x.id_almacen ?? '';
    const nom = x.Empresa ?? x.nombre ?? x.Nombre ?? x.Almacen ?? '';
    if(!id) return;
    const opt = document.createElement('option');
    opt.value = id;
    opt.textContent = nom ? nom : `Almacén ${id}`;
    sel.appendChild(opt);
  });

  $('dbg_alm').textContent = `Fuente: ${API_ALMACENES} | ${arr.length} almacenes`;
}

async function loadRutas(){
  const emp = $('f_empresa').value || "";
  const rutaSel = $('f_ruta');

  if(!emp){
    rutaSel.innerHTML = `<option value="">(Seleccione almacén)</option>`;
    return;
  }

  const j = await fetchJsonTry([
    `${API_RUTAS_PRIMARY}?almacen_id=${encodeURIComponent(emp)}`,
    `${API_RUTAS_FALLBACK}?IdEmpresa=${encodeURIComponent(emp)}`,
    API_RUTAS_FALLBACK
  ]);

  const arr = Array.isArray(j) ? j : (Array.isArray(j?.data) ? j.data : []);
  rutaSel.innerHTML = `<option value="">Seleccione Ruta</option>`;

  arr.forEach(x=>{
    const id = x.id_ruta ?? x.ID_Ruta ?? x.IdRuta ?? x.id ?? x.cve_ruta ?? "";
    const cve = x.cve_ruta ?? x.Cve_Ruta ?? "";
    const desc = x.descripcion ?? x.Descripcion ?? x.nombre ?? "";
    const label = (desc || cve || id) ? `${desc || cve || id}` : "Ruta";
    const opt = document.createElement('option');
    opt.value = id;
    opt.textContent = label;
    rutaSel.appendChild(opt);
  });

  $('dbg_rut').textContent = `Fuente: ${API_RUTAS_PRIMARY} (fallback ${API_RUTAS_FALLBACK}) | ${arr.length} rutas`;
}

async function cargarClientes(){
  const emp  = $('f_empresa').value || '';
  const ruta = $('f_ruta').value || '';

  if(!emp){
    setStatus(false, 'Seleccione almacén.');
    clearMarkers();
    return;
  }

  try{
    const urlList = [
      `${API_DATA_PRIMARY}?IdEmpresa=${encodeURIComponent(emp)}&ruta_id=${encodeURIComponent(ruta)}`,
      `${API_DATA_FALLBACK}?IdEmpresa=${encodeURIComponent(emp)}&ruta_id=${encodeURIComponent(ruta)}`
    ];

    const resp = await fetchJsonTry(urlList);
    const clientes = resp.data || resp || [];

    clearMarkers();
    clearGeocercas();

    // Pintar marcadores
    clientes.forEach(addMarker);

    // Ajustar vista
    if(markers.length){
      const bounds = new google.maps.LatLngBounds();
      markers.forEach(m => bounds.extend(m.getPosition()));
      map.fitBounds(bounds);
      if(markers.length === 1) map.setZoom(14);
    }

    setStatus(true, `Clientes cargados: ${clientes.length}`);
  }catch(e){
    setStatus(false, e.message || 'Error cargando clientes');
    clearMarkers();
  }
}

/** =========================
 *  Boot + eventos
 *  ========================= */
async function boot(){
  try{
    setStatus(true, 'Cargando filtros...');
    await loadAlmacenes();
    setStatus(true, 'Listo');
  }catch(e){
    setStatus(false, e.message || 'Error cargando almacenes');
  }
}

$('btn_refresh').addEventListener('click', async ()=>{
  selectedIds.clear();
  await loadRutas();
  await cargarClientes();
});

$('btn_clear').addEventListener('click', ()=>{
  $('f_q').value = '';
  setStatus(true, 'Filtro limpiado.');
});

// Al cambiar almacén => recargar rutas y clientes
$('f_empresa').addEventListener('change', async ()=>{
  selectedIds.clear();
  await loadRutas();
  await cargarClientes();
});

// Al cambiar ruta => recargar clientes
$('f_ruta').addEventListener('change', async ()=>{
  selectedIds.clear();
  await cargarClientes();
});

// Filtro local por texto (no pega a API)
$('f_q').addEventListener('keydown', (e)=>{
  if(e.key !== 'Enter') return;
  const q = ($('f_q').value || '').trim().toLowerCase();
  if(!q){
    markers.forEach(m=>m.setVisible(true));
    setStatus(true, 'Filtro removido.');
    return;
  }
  let vis = 0;
  markers.forEach(m=>{
    const c = m.__cli || {};
    const hay = [
      c.Cve_Clte, c.razonsocial, c.colonia, c.cp, c.ciudad, c.estado, c.direccion
    ].join(' ').toLowerCase();
    const ok = hay.includes(q);
    m.setVisible(ok);
    if(ok) vis++;
  });
  setStatus(true, `Filtro aplicado. Visibles: ${vis}`);
});
</script>

<!-- IMPORTANTE: geometry + drawing son requeridos para geocercas -->
<script
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC5xF7JtKzw9cTRRXcDAqTThbYnMCiYOVM& 
&libraries=geometry,drawing&callback=initMap"
  async defer></script>

</body>
</html>
