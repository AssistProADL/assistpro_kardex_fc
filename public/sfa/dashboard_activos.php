<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<style>
.kpi { border-radius:8px; padding:16px; text-align:center; }
.kpi h3 { margin:0; font-size:28px; }
.map { height:520px; border-radius:8px; }
</style>

<div class="container-fluid">

  <h4 class="fw-bold mb-3">Dashboard de Activos</h4>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi bg-light"><h3 id="k_total">0</h3>Total</div></div>
    <div class="col-md-3"><div class="kpi bg-success text-white"><h3 id="k_verde">0</h3>Verde</div></div>
    <div class="col-md-3"><div class="kpi bg-warning"><h3 id="k_amarillo">0</h3>Amarillo</div></div>
    <div class="col-md-3"><div class="kpi bg-danger text-white"><h3 id="k_rojo">0</h3>Rojo</div></div>
  </div>

  <!-- Mapa -->
  <div class="card mb-3">
    <div class="card-body">
      <h6 class="fw-bold">Mapa de Activos</h6>
      <div id="map" class="map"></div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card">
    <div class="card-body">
      <h6 class="fw-bold">Detalle Operativo</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>Serie</th>
              <th>Tipo</th>
              <th>Estado</th>
              <th>Almacén</th>
              <th>Temp</th>
              <th>Batería</th>
            </tr>
          </thead>
          <tbody id="tabla_activos"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
let map;

function initMap(){
  map = L.map('map').setView([19.4326,-99.1332], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
}

function cargarActivos(){
  fetch('../api/activos.php?action=list&cve_cia=1&pageSize=500')
    .then(r=>r.json())
    .then(resp=>{
      if(!resp.ok) return;

      let v=0,a=0,rj=0;
      document.getElementById('tabla_activos').innerHTML='';
      map.eachLayer(l=>{ if(l instanceof L.Marker) map.removeLayer(l); });

      resp.data.forEach(x=>{
        if(x.semaforo==='VERDE') v++;
        if(x.semaforo==='AMARILLO') a++;
        if(x.semaforo==='ROJO') rj++;

        document.getElementById('tabla_activos').innerHTML += `
          <tr>
            <td>${x.numero_serie}</td>
            <td>${x.tipo}</td>
            <td>${x.estado}</td>
            <td>${x.almacen||'-'}</td>
            <td>-</td>
            <td>-</td>
          </tr>
        `;

        if(x.latitud && x.longitud){
          const color = x.semaforo==='VERDE'?'green':(x.semaforo==='AMARILLO'?'orange':'red');
          L.circleMarker([x.latitud,x.longitud],{radius:6,color}).addTo(map)
           .bindPopup(`<b>${x.numero_serie}</b><br>${x.estado}`);
        }
      });

      document.getElementById('k_total').innerText = resp.total;
      document.getElementById('k_verde').innerText = v;
      document.getElementById('k_amarillo').innerText = a;
      document.getElementById('k_rojo').innerText = rj;
    });
}

document.addEventListener('DOMContentLoaded',()=>{
  initMap();
  cargarActivos();
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
