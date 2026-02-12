<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Resumen Ejecutivo de Rutas</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { font-size: 12px; background:#f8fafc; }

.estado-verde { border-left: 6px solid #16a34a; }
.estado-amarillo { border-left: 6px solid #f59e0b; }
.estado-rojo { border-left: 6px solid #dc2626; }

.kpi-card {
  background:white;
  border-radius:10px;
  padding:16px;
  box-shadow:0 8px 20px rgba(0,0,0,.06);
}

.kpi-value {
  font-size:22px;
  font-weight:700;
}

.badge-alerta {
  font-size:11px;
  padding:6px 10px;
  border-radius:20px;
}

.table thead th {
  position: sticky;
  top: 0;
  background: #fff;
}
</style>
</head>

<body>

<div class="container-fluid py-3">

  <!-- ESTADO GENERAL -->
  <div class="card mb-3 shadow-sm border-0" id="cardSalud">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap">

      <div>
        <h5 class="mb-1">
          Estado General del Sistema:
          <span id="estadoTexto" class="fw-bold">—</span>
        </h5>
        <div class="small text-muted">
          Última actualización: <span id="metaTimestamp">—</span>
          | API: <span id="metaResponse">—</span> ms
          | Versión: <span id="metaVersion">—</span>
        </div>
      </div>

      <div id="alertasBox" class="mt-2 mt-md-0 text-end"></div>

    </div>
  </div>

  <!-- KPIS -->
  <div class="row g-3 mb-3">

    <div class="col-md-3">
      <div class="kpi-card">
        <div>Rutas Activas</div>
        <div class="kpi-value" id="kpiRutas">0</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div>Clientes Asignados</div>
        <div class="kpi-value" id="kpiClientes">0</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div>Cobertura Geográfica</div>
        <div class="kpi-value" id="kpiGeo">0%</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div>Clientes sin Ruta</div>
        <div class="kpi-value" id="kpiSinRuta">0</div>
      </div>
    </div>

  </div>

  <!-- TABLA -->
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>Ruta</th>
              <th>Clientes</th>
              <th>CPs</th>
              <th>% Geo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="tablaRutas">
            <tr><td colspan="5" class="text-center py-3">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
const API_URL = '../api/resumen_rutas_data.php';

function animateNumber(el, to, suffix=""){
  let start = 0;
  const duration = 600;
  const startTime = performance.now();

  function update(time){
    const progress = Math.min((time - startTime)/duration, 1);
    const value = Math.floor(progress * to);
    el.textContent = value + suffix;
    if(progress < 1) requestAnimationFrame(update);
  }

  requestAnimationFrame(update);
}

function renderSalud(data){

  const salud = data.salud;
  const meta  = data.meta;
  const alertas = data.alertas || [];

  const card = document.getElementById('cardSalud');
  card.classList.remove('estado-verde','estado-amarillo','estado-rojo');

  if(salud.color === 'verde') card.classList.add('estado-verde');
  if(salud.color === 'amarillo') card.classList.add('estado-amarillo');
  if(salud.color === 'rojo') card.classList.add('estado-rojo');

  document.getElementById('estadoTexto').textContent = salud.estado;
  document.getElementById('metaTimestamp').textContent = meta.timestamp;
  document.getElementById('metaResponse').textContent = meta.response_ms;
  document.getElementById('metaVersion').textContent = meta.version;

  const alertBox = document.getElementById('alertasBox');
  alertBox.innerHTML = '';

  alertas.forEach(a=>{
    let color = 'secondary';
    if(a.nivel === 'alto') color = 'danger';
    if(a.nivel === 'medio') color = 'warning';

    alertBox.innerHTML += `
      <span class="badge bg-${color} badge-alerta me-2">
        ${a.mensaje}
      </span>
    `;
  });

}

function renderKPIs(kpis){
  animateNumber(document.getElementById('kpiRutas'), kpis.rutas_activas);
  animateNumber(document.getElementById('kpiClientes'), kpis.clientes_asignados);
  animateNumber(document.getElementById('kpiSinRuta'), kpis.clientes_sin_ruta);
  animateNumber(document.getElementById('kpiGeo'), kpis.cobertura_geo, "%");
}

function renderRutas(rutas){

  const tbody = document.getElementById('tablaRutas');
  tbody.innerHTML = '';

  if(!rutas.length){
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3">Sin datos</td></tr>`;
    return;
  }

  rutas.forEach(r=>{
    tbody.innerHTML += `
      <tr>
        <td>${r.ruta}</td>
        <td>${r.clientes}</td>
        <td>${r.cps}</td>
        <td>${r.geo_pct}%</td>
        <td>
          <span class="badge bg-${r.estado === 'verde' ? 'success' : r.estado === 'amarillo' ? 'warning' : 'danger'}">
            ${r.estado.toUpperCase()}
          </span>
        </td>
      </tr>
    `;
  });

}

function cargarDashboard(){

  fetch(API_URL)
    .then(r=>r.json())
    .then(data=>{
      if(!data.ok){
        console.error(data);
        return;
      }

      renderSalud(data);
      renderKPIs(data.kpis);
      renderRutas(data.rutas);
    })
    .catch(err=>console.error(err));
}

document.addEventListener('DOMContentLoaded', cargarDashboard);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
