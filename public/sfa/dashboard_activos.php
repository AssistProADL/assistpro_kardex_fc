<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<style>
.kpi { border-radius:8px; padding:16px; text-align:center; cursor:pointer; }
.kpi h3 { margin:0; font-size:28px; }
.map { height:520px; border-radius:8px; }
.panel-detalle { height:520px; overflow:auto; }
.det-label { font-weight:600; color:#555; }
.badge-asignado { background:#0d6efd; }
.badge-disponible { background:#6c757d; }
.filtro-activo { outline:3px solid #00000020; }
.legend-dot {
  display:inline-block;
  width:12px;
  height:12px;
  border-radius:50%;
  margin-right:6px;
}
</style>

<div class="container-fluid">

<h4 class="fw-bold mb-3">Dashboard de Activos</h4>

<div class="row mb-3">
  <div class="col-md-4">
    <label class="fw-bold">Empresa</label>
    <select id="empresa_select" class="form-select"></select>
  </div>
</div>

<!-- KPIs FILTRABLES -->
<div class="row g-3 mb-3">
  <div class="col-md-2">
    <div class="kpi bg-light filtro-activo" id="filtro_todos">
      <h3 id="k_total">0</h3>Total
    </div>
  </div>
  <div class="col-md-2">
    <div class="kpi bg-primary text-white" id="filtro_asignados">
      <h3 id="k_asignados">0</h3>Asignados
    </div>
  </div>
  <div class="col-md-2">
    <div class="kpi bg-secondary text-white" id="filtro_disponibles">
      <h3 id="k_disponibles">0</h3>Disponibles
    </div>
  </div>
  <div class="col-md-2"><div class="kpi bg-success text-white"><h3 id="k_verde">0</h3>Verde</div></div>
  <div class="col-md-2"><div class="kpi bg-warning"><h3 id="k_amarillo">0</h3>Amarillo</div></div>
  <div class="col-md-2"><div class="kpi bg-danger text-white"><h3 id="k_rojo">0</h3>Rojo</div></div>
</div>

<div class="mb-2">
  <span class="legend-dot" style="background:#0d6efd;"></span>Asignado
  &nbsp;&nbsp;
  <span class="legend-dot" style="background:#6c757d;"></span>Disponible
  &nbsp;&nbsp;
  <span class="legend-dot" style="background:#dc3545;"></span>Vencido
</div>

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
let FILTRO = 'TODOS';

function initMap(){
  map = new google.maps.Map(document.getElementById("map"), {
    zoom: 6,
    center: { lat: 19.4326, lng: -99.1332 }
  });

  configurarFiltros();
  cargarEmpresas();
}

function configurarFiltros(){
  document.getElementById('filtro_todos').onclick = () => cambiarFiltro('TODOS');
  document.getElementById('filtro_asignados').onclick = () => cambiarFiltro('ASIGNADOS');
  document.getElementById('filtro_disponibles').onclick = () => cambiarFiltro('DISPONIBLES');
}

function cambiarFiltro(tipo){
  FILTRO = tipo;

  document.querySelectorAll('.kpi').forEach(k=>k.classList.remove('filtro-activo'));

  if(tipo==='TODOS') document.getElementById('filtro_todos').classList.add('filtro-activo');
  if(tipo==='ASIGNADOS') document.getElementById('filtro_asignados').classList.add('filtro-activo');
  if(tipo==='DISPONIBLES') document.getElementById('filtro_disponibles').classList.add('filtro-activo');

  cargarActivos();
}

function cargarEmpresas(){
  fetch('../api/activos_api.php?action=meta')
  .then(r=>r.json())
  .then(resp=>{
    if(!resp.ok) return;

    const select = document.getElementById('empresa_select');
    select.innerHTML = '';

    resp.companias.forEach(e=>{
      select.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
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

  const esAsignado = x.id_asignacion ? true : false;

  const badgeEstado = esAsignado
      ? '<span class="badge badge-asignado">Asignado</span>'
      : '<span class="badge badge-disponible">Disponible</span>';

  const badgeVigencia = (x.vigencia == 1)
      ? '<span class="badge bg-success">Vigente</span>'
      : '<span class="badge bg-danger">Vencido</span>';

  document.getElementById('detalle_activo').innerHTML = `
    <div class="mb-2">${badgeEstado}</div>

    <div class="mb-2"><span class="det-label">Clave:</span> ${x.clave}</div>
    <div class="mb-2"><span class="det-label">Serie:</span> ${x.num_serie || '-'}</div>
    <div class="mb-2"><span class="det-label">Tipo:</span> ${x.tipo_activo}</div>
    <div class="mb-2"><span class="det-label">Estado:</span> ${x.estatus}</div>

    <hr>

    <div class="mb-2"><span class="det-label">Almacén:</span> ${x.almacen_nombre || '-'}</div>
    <div class="mb-2"><span class="det-label">Asignado a:</span> ${x.asignado_a || 'Disponible'}</div>
    <div class="mb-2"><span class="det-label">Desde:</span> ${x.fecha_desde || '-'}</div>
    <div class="mb-2"><span class="det-label">Hasta:</span> ${x.fecha_hasta || '-'}</div>
    <div class="mb-2">${esAsignado ? badgeVigencia : ''}</div>

    <hr>

    <div class="mb-2"><span class="det-label">Marca:</span> ${x.marca || '-'}</div>
    <div class="mb-2"><span class="det-label">Modelo:</span> ${x.modelo || '-'}</div>
    <div class="mb-2"><span class="det-label">Ubicación:</span> 
      ${x.latitud || '-'}, ${x.longitud || '-'}
    </div>
  `;
}

function cargarActivos(){

  if(!EMPRESA_ACTIVA) return;

  fetch(`../api/activos_api.php?action=list&cve_cia=${EMPRESA_ACTIVA}&pageSize=500`)
  .then(r=>r.json())
  .then(resp=>{

    if(!resp.ok || !resp.data) return;

    limpiarMarkers();

    let total=0, asignados=0, disponibles=0, verde=0, amarillo=0, rojo=0;

    resp.data.forEach(x=>{

      const esAsignado = x.id_asignacion ? true : false;

      if(FILTRO==='ASIGNADOS' && !esAsignado) return;
      if(FILTRO==='DISPONIBLES' && esAsignado) return;

      total++;

      if(esAsignado) asignados++;
      else disponibles++;

      if(x.semaforo==='VERDE') verde++;
      if(x.semaforo==='AMARILLO') amarillo++;
      if(x.semaforo==='ROJO') rojo++;

      if(x.latitud && x.longitud){

        let color;

        if(x.vigencia==0){
          color = "#dc3545";
        } else if(esAsignado){
          color = "#0d6efd";
        } else {
          color = "#6c757d";
        }

        const marker = new google.maps.Marker({
          position:{
            lat:parseFloat(x.latitud),
            lng:parseFloat(x.longitud)
          },
          map:map,
          icon:{
            path:google.maps.SymbolPath.CIRCLE,
            scale:7,
            fillColor:color,
            fillOpacity:1,
            strokeWeight:1
          }
        });

        marker.addListener("click",()=>mostrarDetalle(x));
        markers.push(marker);
      }
    });

    document.getElementById('k_total').innerText = total;
    document.getElementById('k_asignados').innerText = asignados;
    document.getElementById('k_disponibles').innerText = disponibles;
    document.getElementById('k_verde').innerText = verde;
    document.getElementById('k_amarillo').innerText = amarillo;
    document.getElementById('k_rojo').innerText = rojo;

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
