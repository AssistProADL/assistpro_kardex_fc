<?php
include __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  .ap-title { font-weight: 700; }
  .ap-sub { color:#6c757d; font-size:12px; }
  .box { background:#fff; border:1px solid #e9ecef; border-radius:10px; }
  .map-wrap { height: 68vh; min-height: 520px; border-radius:10px; overflow:hidden; }
  .mini { font-size:12px; color:#6c757d; }
  .kpi-line { font-size:12px; color:#495057; }
  .pill { border:1px solid #dee2e6; border-radius:999px; padding:2px 10px; font-size:12px; background:#f8f9fa; }
  .sel-list { max-height: 160px; overflow:auto; border:1px solid #e9ecef; border-radius:8px; padding:8px; background:#fcfcfd; }
  .sel-item { font-size:12px; padding:6px; border-bottom:1px dashed #eee; }
  .sel-item:last-child { border-bottom:0; }
</style>

<div class="container-fluid">

  <div class="d-flex align-items-start justify-content-between mb-2">
    <div>
      <h4 class="ap-title mb-0">Distribución de Clientes en el Mapa 2</h4>
      <div class="ap-sub">Geocerca + reasignación en línea (relclirutas) con herencia a reldaycli. Incluye crédito y saldo.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-primary btn-sm" href="planeacion_rutas_destinatarios.php">Asignar Clientes</a>
      <a class="btn btn-outline-secondary btn-sm" href="resumen_rutas.php">Resumen Rutas</a>
    </div>
  </div>

  <!-- filtros -->
  <div class="box p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label mb-1">Empresa (IdEmpresa)</label>
        <select id="f_empresa" class="form-select form-select-sm"></select>
      </div>
      <div class="col-md-5">
        <label class="form-label mb-1">Ruta</label>
        <select id="f_ruta" class="form-select form-select-sm">
          <option value="0">(Todas)</option>
        </select>
      </div>
      <div class="col-md-2">
        <button id="btn_actualizar" class="btn btn-primary btn-sm w-100">Actualizar</button>
      </div>
      <div class="col-md-2 text-end">
        <div class="pill" id="kpi_top">Total: 0 | Con GPS: 0 | Saldo deudor: $0.00</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-9">
      <div class="box p-2">
        <div id="map" class="map-wrap"></div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="box p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-bold">Selección por Geocerca</div>
          <div class="d-flex gap-2">
            <button id="btn_geo" class="btn btn-outline-primary btn-sm">Geocerca</button>
            <button id="btn_clear" class="btn btn-outline-danger btn-sm">Limpiar</button>
          </div>
        </div>

        <div class="mini mb-2">Dibuja un polígono. Se seleccionan clientes dentro y podrás reasignarlos a otra ruta.</div>

        <label class="form-label mb-1">Ruta destino</label>
        <select id="f_ruta_destino" class="form-select form-select-sm mb-2">
          <option value="">Seleccione</option>
        </select>

        <div class="row g-2 mb-2">
          <div class="col-6"><button id="btn_preview" class="btn btn-secondary btn-sm w-100">Preview</button></div>
          <div class="col-6"><button id="btn_apply" class="btn btn-success btn-sm w-100">Aplicar</button></div>
        </div>

        <div class="kpi-line">Seleccionados: <b id="k_sel">0</b></div>
        <div class="kpi-line">Con GPS: <b id="k_sel_gps">0</b></div>
        <div class="kpi-line">Saldo total: <b id="k_sel_saldo">$0.00</b></div>

        <hr class="my-2">

        <div class="fw-bold mb-1" style="font-size:13px;">Cliente seleccionado</div>
        <div id="cliente_info" class="mini">—</div>
      </div>

      <div class="box p-3">
        <div class="fw-bold mb-2">Clientes seleccionados</div>
        <div id="sel_list" class="sel-list"><div class="mini">Sin selección</div></div>
      </div>
    </div>
  </div>

</div>

<script>
const API_ALMACENES = "../api/catalogo_almacenes.php";
const API_RUTAS     = "../api/sfa/catalogo_rutas.php";
const API_DATA      = "../api/geo_clientes_data.php";
const API_APPLY     = "../api/geo_clientes_apply.php";

let map, drawingManager, polygon = null;
let markers = [];
let clientes = [];         // data completa desde API
let selectedIds = new Set();

function money(n){
  try { return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(n||0)); }
  catch(e){ return '$' + (Number(n||0).toFixed(2)); }
}

function setTopKPI(){
  const total = clientes.length;
  const conGps = clientes.filter(x => x.lat && x.lng).length;
  const saldo = clientes.reduce((a,x)=> a + (Number(x.saldo_actual||0)), 0);
  document.getElementById('kpi_top').innerText = `Total: ${total} | Con GPS: ${conGps} | Saldo deudor: ${money(saldo)}`;
}

function setSelKPI(){
  const arr = clientes.filter(x => selectedIds.has(String(x.id_destinatario)));
  const conGps = arr.filter(x => x.lat && x.lng).length;
  const saldo = arr.reduce((a,x)=> a + (Number(x.saldo_actual||0)), 0);
  document.getElementById('k_sel').innerText = arr.length;
  document.getElementById('k_sel_gps').innerText = conGps;
  document.getElementById('k_sel_saldo').innerText = money(saldo);

  const box = document.getElementById('sel_list');
  if (!arr.length) {
    box.innerHTML = `<div class="mini">Sin selección</div>`;
    return;
  }
  box.innerHTML = arr.map(x => `
    <div class="sel-item">
      <div><b>${x.cliente_nombre || x.Cve_Clte || 'Cliente'}</b></div>
      <div class="mini">${x.destinatario || ''}</div>
      <div class="mini">Crédito: ${x.dias_credito||0} días · Saldo: ${money(x.saldo_actual||0)}</div>
    </div>
  `).join('');
}

function infoCliente(x){
  const el = document.getElementById('cliente_info');
  if(!x){ el.innerHTML = '—'; return; }
  el.innerHTML = `
    <div><b>${x.cliente_nombre || x.Cve_Clte || 'Cliente'}</b></div>
    <div>${x.destinatario || ''}</div>
    <div class="mini">${x.direccion||''} · ${x.colonia||''} · CP ${x.postal||''}</div>
    <div class="mini">${x.ciudad||''}, ${x.estado||''}</div>
    <div class="mini">Crédito: <b>${x.dias_credito||0}</b> días · Límite: <b>${money(x.limite_credito||0)}</b></div>
    <div class="mini">Saldo deudor: <b>${money(x.saldo_actual||0)}</b></div>
  `;
}

function clearMarkers(){
  markers.forEach(m => m.setMap(null));
  markers = [];
}

function renderMarkers(){
  clearMarkers();

  clientes.forEach(x => {
    if(!x.lat || !x.lng) return;

    const m = new google.maps.Marker({
      position: {lat: x.lat, lng: x.lng},
      map,
      title: (x.destinatario || x.cliente_nombre || '') + '',
      // icon could be customized later
    });

    const infow = new google.maps.InfoWindow({
      content: `
        <div style="font-size:12px">
          <div><b>${x.cliente_nombre || x.Cve_Clte || 'Cliente'}</b></div>
          <div>${x.destinatario || ''}</div>
          <div>Crédito: ${x.dias_credito||0} días · Saldo: ${money(x.saldo_actual||0)}</div>
          <div class="mini">${x.cve_ruta || ''} - ${x.ruta_nombre || ''}</div>
        </div>
      `
    });

    m.addListener('click', () => {
      infoCliente(x);
      infow.open({anchor:m, map});
    });

    markers.push(m);
  });

  // Fit bounds
  if (markers.length){
    const b = new google.maps.LatLngBounds();
    markers.forEach(m => b.extend(m.getPosition()));
    map.fitBounds(b);
  }
}

async function loadAlmacenes(){
  const r = await fetch(API_ALMACENES);
  const data = await r.json();

  const sel = document.getElementById('f_empresa');
  sel.innerHTML = `<option value="">Seleccione</option>`;
  (data||[]).forEach(a => {
    // tu API usualmente regresa {id,nombre} o {clave,nombre}
    const val = (a.id ?? a.clave ?? a.IdEmpresa ?? '');
    const txt = (a.nombre ?? a.Nombre ?? a.descripcion ?? val);
    sel.innerHTML += `<option value="${val}">${txt}</option>`;
  });
}

async function loadRutas(){
  const emp = document.getElementById('f_empresa').value;

  const selRuta    = document.getElementById('f_ruta');
  const selDestino = document.getElementById('f_ruta_destino');

  // Reset selects
  selRuta.innerHTML    = `<option value="0">(Todas)</option>`;
  selDestino.innerHTML = `<option value="">Seleccione</option>`;

  if(!emp) return;

  // OJO: aquí SOLO armamos el endpoint correcto con querystring
  const url = `${API_RUTAS}?almacen_id=${encodeURIComponent(emp)}`;

  const r = await fetch(url, { cache: 'no-store' });
  const json = await r.json();

  // Tu API regresa { success:true, almacen_id:x, data:[...] }
  const rutas = Array.isArray(json?.data) ? json.data : [];

  rutas.forEach(rt => {
    const id   = rt.id_ruta ?? rt.id ?? rt.IdRuta ?? '';
    const name = rt.descripcion ?? rt.Cve_Ruta ?? rt.cve_ruta ?? `Ruta ${id}`;

    if(!id) return;

    selRuta.insertAdjacentHTML('beforeend', `<option value="${id}">${name}</option>`);
    selDestino.insertAdjacentHTML('beforeend', `<option value="${id}">${name}</option>`);
  });
}

async function cargarClientes(){
  const emp = document.getElementById('f_empresa').value;
  const ruta = document.getElementById('f_ruta').value || '0';

  if(!emp){
    clientes = [];
    setTopKPI();
    clearMarkers();
    return;
  }

  const url = `${API_DATA}?IdEmpresa=${encodeURIComponent(emp)}&ruta_id=${encodeURIComponent(ruta)}`;
  const r = await fetch(url);
  const resp = await r.json();

  if(!resp || resp.error){
    alert(resp?.error ? (resp.error + (resp.detalle ? ("\n" + resp.detalle) : "")) : "Error consultando");
    return;
  }

  clientes = resp.data || [];
  selectedIds.clear();
  setTopKPI();
  setSelKPI();
  infoCliente(null);
  renderMarkers();
}

function enableGeocerca(){
  if(!drawingManager) return;
  drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
}

function clearGeocerca(){
  if(polygon){ polygon.setMap(null); polygon=null; }
  selectedIds.clear();
  setSelKPI();
  infoCliente(null);
}

function computeSelection(){
  if(!polygon) return;

  selectedIds.clear();

  clientes.forEach(x => {
    if(!x.lat || !x.lng) return;
    const pt = new google.maps.LatLng(x.lat, x.lng);
    if (google.maps.geometry.poly.containsLocation(pt, polygon)) {
      selectedIds.add(String(x.id_destinatario));
    }
  });

  setSelKPI();
}

async function applyReasignacion(){
  const emp = document.getElementById('f_empresa').value;
  const dst = document.getElementById('f_ruta_destino').value;

  if(!emp){ alert("Seleccione Empresa."); return; }
  if(!dst){ alert("Seleccione Ruta destino."); return; }
  if(selectedIds.size === 0){ alert("No hay clientes seleccionados."); return; }

  // Días globales (si quieres heredar días desde esta pantalla, aquí los mandaríamos.
  // Por ahora manda Do=1 por defecto si lo deseas; lo dejamos en 0 (solo cambia ruta).
  const fd = new FormData();
  fd.append('IdEmpresa', emp);
  fd.append('ruta_nueva', dst);
  fd.append('ids_destinatario', Array.from(selectedIds).join(','));

  const r = await fetch(API_APPLY, { method:'POST', body: fd });
  const resp = await r.json();

  if(resp.error){
    alert(resp.error + (resp.detalle ? ("\n"+resp.detalle) : ""));
    return;
  }

  alert(`OK. relclirutas movidos: ${resp.movidos_relclirutas} · reldaycli actualizados: ${resp.actualizados_reldaycli}`);
  await cargarClientes(); // refresca mapa
}

function initMap(){
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 19.4326, lng: -99.1332 },
    zoom: 10,
    mapTypeControl: true,
    streetViewControl: false,
    fullscreenControl: true
  });

  drawingManager = new google.maps.drawing.DrawingManager({
    drawingMode: null,
    drawingControl: false,
    polygonOptions: {
      fillColor: "#0d6efd",
      fillOpacity: 0.15,
      strokeColor: "#0d6efd",
      strokeWeight: 2,
      clickable: false,
      editable: true
    }
  });

  drawingManager.setMap(map);

  google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
    if (polygon) polygon.setMap(null);
    polygon = event.overlay;
    drawingManager.setDrawingMode(null);

    // al editar polígono, recalcular
    computeSelection();
    google.maps.event.addListener(polygon.getPath(), 'set_at', computeSelection);
    google.maps.event.addListener(polygon.getPath(), 'insert_at', computeSelection);
  });
}

// eventos
document.addEventListener('DOMContentLoaded', async () => {
  await loadAlmacenes();
  await loadRutas();

  document.getElementById('btn_actualizar').addEventListener('click', cargarClientes);
 

 document.getElementById('f_empresa').addEventListener('change', async () => {
  selectedIds.clear();
  await loadRutas();     // 👈 recarga rutas del almacén/empresa seleccionada
  await cargarClientes(); // 👈 ya con rutas correctas
});

 document.getElementById('f_ruta').addEventListener('change', () => { selectedIds.clear(); cargarClientes(); });

  document.getElementById('btn_geo').addEventListener('click', enableGeocerca);
  document.getElementById('btn_clear').addEventListener('click', clearGeocerca);
  document.getElementById('btn_preview').addEventListener('click', computeSelection);
  document.getElementById('btn_apply').addEventListener('click', applyReasignacion);
});
</script>

<!-- Google Maps: reemplaza YOUR_GOOGLE_MAPS_KEY -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC5xF7JtKzw9cTRRXcDAqTThbYnMCiYOVM&libraries=drawing,geometry&callback=initMap" async defer></script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
?>
