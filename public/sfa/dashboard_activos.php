<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<style>
.kpi { border-radius:8px; padding:16px; text-align:center; }
.kpi h3 { margin:0; font-size:28px; }
.map { height:520px; border-radius:8px; }
.panel-detalle { height:520px; overflow:auto; }
.det-label { font-weight:600; color:#555; }
</style>

<div class="container-fluid">

<h4 class="fw-bold mb-3">Dashboard de Activos</h4>

<!-- Selector Empresa -->
<div class="row mb-3">
  <div class="col-md-4">
    <label class="fw-bold">Empresa</label>
    <select id="empresa_select" class="form-select"></select>
  </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="kpi bg-light"><h3 id="k_total">0</h3>Total</div></div>
  <div class="col-md-3"><div class="kpi bg-success text-white"><h3 id="k_verde">0</h3>Verde</div></div>
  <div class="col-md-3"><div class="kpi bg-warning"><h3 id="k_amarillo">0</h3>Amarillo</div></div>
  <div class="col-md-3"><div class="kpi bg-danger text-white"><h3 id="k_rojo">0</h3>Rojo</div></div>
</div>

<!-- MAPA + PANEL -->
<div class="row mb-3">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <h6 class="fw-bold">Mapa de Activos</h6>
        <div id="map" class="map"></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card panel-detalle">
      <div class="card-body">
        <h6 class="fw-bold">Detalle del Activo</h6>
        <div id="detalle_activo">
          <div class="text-muted">Seleccione un activo en el mapa</div>
        </div>
      </div>
    </div>
  </div>
</div>

</div>

<script>
let map;
let markers = [];
let EMPRESA_ACTIVA = null;

function initMap(){
  map = new google.maps.Map(document.getElementById("map"), {
    zoom: 6,
    center: { lat: 19.4326, lng: -99.1332 }
  });

  cargarEmpresas();
}

function cargarEmpresas(){

  fetch('../api/empresas_api.php')
  .then(r=>r.json())
  .then(resp=>{

    if(!resp.ok) return;

    const select = document.getElementById('empresa_select');
    select.innerHTML = '';

    resp.data.forEach(e=>{
      select.innerHTML += `<option value="${e.cve_cia}">${e.des_cia}</option>`;
    });

    EMPRESA_ACTIVA = select.value;
    cargarActivos();

    select.addEventListener('change', function(){
      EMPRESA_ACTIVA = this.value;
      cargarActivos();
    });
  });
}

function limpiarMarkers(){
  markers.forEach(m => m.setMap(null));
  markers = [];
}

function mostrarDetalle(x){
  document.getElementById('detalle_activo').innerHTML = `
    <div class="mb-2"><span class="det-label">Serie:</span> ${x.numero_serie}</div>
    <div class="mb-2"><span class="det-label">Tipo:</span> ${x.tipo}</div>
    <div class="mb-2"><span class="det-label">Estado:</span> ${x.estado}</div>
    <div class="mb-2"><span class="det-label">Almacén:</span> ${x.almacen || '-'}</div>
    <div class="mb-2"><span class="det-label">Marca:</span> ${x.marca || '-'}</div>
    <div class="mb-2"><span class="det-label">Modelo:</span> ${x.modelo || '-'}</div>
    <div class="mb-2"><span class="det-label">Proveedor:</span> ${x.proveedor || '-'}</div>
    <div class="mb-2"><span class="det-label">Ubicación:</span> ${x.latitud}, ${x.longitud}</div>
  `;
}

function cargarActivos(){

  if(!EMPRESA_ACTIVA) return;

  fetch(`../api/activos_api.php?action=list&cve_cia=${EMPRESA_ACTIVA}&pageSize=500`)
  .then(r=>r.json())
  .then(resp=>{

    if(!resp.ok || !resp.data) return;

    let v=0,a=0,rj=0;
    limpiarMarkers();

    resp.data.forEach(x=>{

      if(x.semaforo==='VERDE') v++;
      if(x.semaforo==='AMARILLO') a++;
      if(x.semaforo==='ROJO') rj++;

      if(x.latitud && x.longitud){

        const color =
          x.semaforo==='VERDE' ? "#28a745" :
          x.semaforo==='AMARILLO' ? "#ffc107" :
          "#dc3545";

        const marker = new google.maps.Marker({
          position:{
            lat:parseFloat(x.latitud),
            lng:parseFloat(x.longitud)
          },
          map:map,
          icon:{
            path:google.maps.SymbolPath.CIRCLE,
            scale:6,
            fillColor:color,
            fillOpacity:1,
            strokeWeight:1
          }
        });

        marker.addListener("click",()=>mostrarDetalle(x));
        markers.push(marker);
      }
    });

    document.getElementById('k_total').innerText = resp.total;
    document.getElementById('k_verde').innerText = v;
    document.getElementById('k_amarillo').innerText = a;
    document.getElementById('k_rojo').innerText = rj;

    if(markers.length>0){
      const bounds = new google.maps.LatLngBounds();
      markers.forEach(m=>bounds.extend(m.getPosition()));
      map.fitBounds(bounds);
    }

  });
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC5xF7JtKzw9cTRRXcDAqTThbYnMCiYOVM&callback=initMap" async defer></script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
