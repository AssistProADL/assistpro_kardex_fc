<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css"/>

<style>
#map { height: 620px; border-radius:8px; }
.panel { position:absolute; top:90px; right:20px; z-index:1000; width:320px; }
</style>

<div class="container-fluid">
  <h4 class="fw-bold mb-2">Geocercas & Reasignación de Activos</h4>
  <div id="map"></div>

  <div class="card panel">
    <div class="card-body">
      <h6 class="fw-bold">Acciones</h6>
      <button id="btn_reasignar" class="btn btn-primary btn-sm w-100">
        Reasignar activos dentro
      </button>
      <small class="text-muted d-block mt-2">
        Se reasignarán los activos contenidos en la geocerca.
      </small>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

<script>
let map, drawnItems, geofence = null, activos = [];

function init(){
  map = L.map('map').setView([19.4326,-99.1332], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  drawnItems = new L.FeatureGroup();
  map.addLayer(drawnItems);

  map.addControl(new L.Control.Draw({
    edit: { featureGroup: drawnItems },
    draw: { polygon:true, rectangle:true, circle:true, marker:false, polyline:false }
  }));

  map.on(L.Draw.Event.CREATED, e=>{
    drawnItems.clearLayers();
    drawnItems.addLayer(e.layer);
    geofence = e.layer;
    evaluarGeocerca();
  });

  cargarActivos();
}

function cargarActivos(){
  fetch('../api/activos.php?action=list&cve_cia=1&pageSize=500')
    .then(r=>r.json())
    .then(resp=>{
      if(!resp.ok) return;
      activos = resp.data;
      activos.forEach(a=>{
        if(a.latitud && a.longitud){
          L.circleMarker([a.latitud,a.longitud],{radius:6})
            .addTo(map)
            .bindPopup(a.numero_serie);
        }
      });
    });
}

function evaluarGeocerca(){
  if(!geofence) return;
  activos.forEach(a=>{
    if(!a.latitud || !a.longitud) return;
    const inside = geofence.getBounds().contains([a.latitud,a.longitud]);
    if(inside){
      console.log('Dentro:', a.numero_serie);
    }
  });
}

document.getElementById('btn_reasignar').onclick = ()=>{
  if(!geofence) return alert('Dibuja una geocerca primero');

  activos.forEach(a=>{
    if(!a.latitud || !a.longitud) return;
    if(geofence.getBounds().contains([a.latitud,a.longitud])){
      const fd = new FormData();
      fd.append('action','mover');
      fd.append('id_activo', a.id_activo);
      fd.append('cve_cia', 1);
      fd.append('latitud', a.latitud);
      fd.append('longitud', a.longitud);
      fd.append('usuario','geocerca_demo');

      fetch('../api/activos_ubicacion.php',{method:'POST', body:fd});
    }
  });

  alert('Reasignación enviada');
};

document.addEventListener('DOMContentLoaded', init);
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
