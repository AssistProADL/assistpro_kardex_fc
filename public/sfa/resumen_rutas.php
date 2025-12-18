<?php
include __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.kpi-card {
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}
.kpi-card h3 {
  margin: 0;
  font-size: 28px;
}
.kpi-card small {
  color: #6c757d;
}
.estado-verde { color:#198754; font-weight:600; }
.estado-amarillo { color:#ffc107; font-weight:600; }
.estado-rojo { color:#dc3545; font-weight:600; }

.table-sm td, .table-sm th {
  font-size: 12px;
  padding: 0.35rem;
  white-space: nowrap;
}
</style>

<div class="container-fluid">

  <!-- ================= HEADER ================= -->
  <div class="row mb-3">
    <div class="col-md-6">
      <h4 class="fw-bold">Dashboard Corporativo | Resumen de Rutas</h4>
    </div>
    <div class="col-md-6 text-end">
      <a href="planeacion_rutas_destinatarios.php"
         class="btn btn-outline-primary btn-sm">
        ✏ Asignar Clientes
      </a>
      <a href="geo_distribucion_clientes.php"
         class="btn btn-outline-success btn-sm">
        🌍 Georreferencia
      </a>
    </div>
  </div>

  <!-- ================= FILTROS ================= -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Almacén</label>
          <select id="f_almacen" class="form-select form-select-sm"></select>
        </div>
        <div class="col-md-2">
          <button id="btn_actualizar"
                  class="btn btn-primary btn-sm w-100">
            Actualizar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= KPIs ================= -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card kpi-card">
        <h3 id="kpi_rutas">0</h3>
        <small>Rutas activas</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card kpi-card">
        <h3 id="kpi_clientes">0</h3>
        <small>Clientes asignados</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card kpi-card">
        <h3 id="kpi_pendientes">0</h3>
        <small>Clientes sin ruta</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card kpi-card">
        <h3 id="kpi_geo">0%</h3>
        <small>Cobertura geográfica</small>
      </div>
    </div>
  </div>

  <!-- ================= TABLA RESUMEN ================= -->
  <div class="card mb-3">
    <div class="card-body">
      <h6 class="fw-bold mb-2">Resumen por Ruta</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Ruta</th>
              <th>Clientes</th>
              <th>Días</th>
              <th>CPs</th>
              <th>Geo %</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="tabla_rutas">
            <tr>
              <td colspan="6" class="text-center text-muted">
                Seleccione un almacén
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================= RESUMEN POR DÍA ================= -->
  <div class="card">
    <div class="card-body">
      <h6 class="fw-bold mb-2">Distribución por Día</h6>
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>Día</th>
            <th>Rutas</th>
            <th>Clientes</th>
          </tr>
        </thead>
        <tbody id="tabla_dias"></tbody>
      </table>
    </div>
  </div>

</div>

<script>
/* =======================
   CARGA DE ALMACENES
   ======================= */
function cargarAlmacenes() {
  fetch('../api/catalogo_almacenes.php')
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('f_almacen');
      sel.innerHTML = '<option value="">Seleccione Almacén</option>';
      data.forEach(a => {
        sel.innerHTML += `<option value="${a.id}">${a.nombre}</option>`;
      });
    });
}

/* =======================
   CARGA DASHBOARD
   ======================= */
function cargarDashboard() {

  const almacen = document.getElementById('f_almacen').value;
  if (!almacen) return;

  const fd = new FormData();
  fd.append('almacen', almacen);

  fetch('../api/resumen_rutas_data.php', {
    method: 'POST',
    body: fd
  })
  .then(r => r.json())
  .then(pintarDashboard);
}

/* =======================
   PINTAR DASHBOARD
   ======================= */
function pintarDashboard(resp) {

  // KPIs
  document.getElementById('kpi_rutas').innerText =
    resp.kpis.rutas_activas;

  document.getElementById('kpi_clientes').innerText =
    resp.kpis.clientes_asignados;

  document.getElementById('kpi_pendientes').innerText =
    resp.kpis.clientes_sin_ruta;

  document.getElementById('kpi_geo').innerText =
    resp.kpis.cobertura_geo + '%';

  // Tabla rutas
  const tb = document.getElementById('tabla_rutas');
  tb.innerHTML = '';

  resp.rutas.forEach(r => {

    let estadoTxt = '';
    let estadoCls = '';

    if (r.estado === 'verde') {
      estadoTxt = '🟢 OK';
      estadoCls = 'estado-verde';
    }
    if (r.estado === 'amarillo') {
      estadoTxt = '🟡 Parcial';
      estadoCls = 'estado-amarillo';
    }
    if (r.estado === 'rojo') {
      estadoTxt = '🔴 Atención';
      estadoCls = 'estado-rojo';
    }

    tb.innerHTML += `
      <tr>
        <td>${r.ruta}</td>
        <td class="text-center">${r.clientes}</td>
        <td class="text-center">${r.dias || '-'}</td>
        <td class="text-center">${r.cps}</td>
        <td class="text-center">${r.geo_pct}%</td>
        <td class="${estadoCls}">${estadoTxt}</td>
      </tr>
    `;
  });

  // Tabla días
  const tbDias = document.getElementById('tabla_dias');
  tbDias.innerHTML = '';

  resp.dias.forEach(d => {
    tbDias.innerHTML += `
      <tr>
        <td>${d.dia}</td>
        <td class="text-center">${d.rutas}</td>
        <td class="text-center">${d.clientes}</td>
      </tr>
    `;
  });
}

/* =======================
   EVENTOS
   ======================= */
document.getElementById('btn_actualizar')
  .addEventListener('click', cargarDashboard);

document.addEventListener('DOMContentLoaded', () => {
  cargarAlmacenes();

  document.getElementById('f_almacen')
    .addEventListener('change', cargarDashboard);
});
</script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
?>
