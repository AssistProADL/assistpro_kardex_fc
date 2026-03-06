<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");
?>

<style>
  .kpi-card {
    border-radius: 14px;
    padding: 20px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transition: all .2s ease;
  }

  .kpi-card:hover {
    transform: translateY(-3px);
  }

  .kpi-title {
    font-size: 13px;
    color: #6c757d;
    text-transform: uppercase;
  }

  .kpi-value {
    font-size: 28px;
    font-weight: 700;
  }

  .kpi-sub {
    font-size: 13px;
  }

  .leaderboard-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
  }

  .medal {
    font-size: 18px;
  }
</style>

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary">
      <i class="fa fa-chart-line me-2"></i>
      Dashboard Instalaciones 360°
    </h4>
    <a href="index.php" class="btn btn-outline-secondary">
      Volver
    </a>
  </div>

  <!-- KPI ROW -->
  <div class="row mb-4">

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-title">Instalaciones Mes Actual</div>
        <div class="kpi-value" id="kpi_actual">0</div>
        <div class="kpi-sub" id="kpi_variacion"></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-title">Completadas Mes Actual</div>
        <div class="kpi-value" id="kpi_completadas">0</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-title">SLA Cumplimiento</div>
        <div class="kpi-value" id="kpi_sla">0%</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-title">Variación Mes vs Mes</div>
        <div class="kpi-value" id="kpi_delta">0%</div>
      </div>
    </div>

  </div>

  <!-- SECOND ROW -->
  <div class="row">

    <!-- Leaderboard -->
    <div class="col-md-5">
      <div class="kpi-card">
        <h6 class="text-primary mb-3">
          <i class="fa fa-trophy me-2"></i>
          Ranking Técnicos
        </h6>
        <div id="leaderboard"></div>
      </div>
    </div>

    <!-- Tendencia -->
    <div class="col-md-7">
      <div class="kpi-card">
        <h6 class="text-primary mb-3">
          <i class="fa fa-chart-area me-2"></i>
          Tendencia Mensual
        </h6>
        <canvas id="graficaTendencia" height="110"></canvas>
      </div>
    </div>

  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  async function cargarDashboard() {

    const response = await fetch('../api/instalaciones/api_instalaciones.php?action=dashboard_avanzado');
    const json = await response.json();

    if (!json.success) {
      alert("Error cargando dashboard");
      return;
    }

    const data = json.data;

    /* ================= KPI ================= */

    document.getElementById('kpi_actual').innerText = data.mes_actual_total;
    document.getElementById('kpi_completadas').innerText = data.mes_actual_completadas;

    /* SLA con semáforo */
    const sla = data.sla_pct ?? 0;
    const slaEl = document.getElementById('kpi_sla');
    slaEl.innerText = sla + '%';

    if (sla < 80) slaEl.style.color = '#dc3545';
    else if (sla < 90) slaEl.style.color = '#ffc107';
    else slaEl.style.color = '#198754';

    /* Variación */
    const delta = data.variacion_pct ?? 0;
    const deltaEl = document.getElementById('kpi_delta');
    deltaEl.innerText = delta + '%';

    if (delta > 0) deltaEl.style.color = '#198754';
    else if (delta < 0) deltaEl.style.color = '#dc3545';
    else deltaEl.style.color = '#6c757d';

    /* ================= Leaderboard ================= */

    const lb = document.getElementById('leaderboard');
    lb.innerHTML = '';

    const medals = ['🥇', '🥈', '🥉'];

    data.ranking_tecnicos.forEach((t, index) => {

      const medal = medals[index] ?? '';

      lb.innerHTML += `
            <div class="leaderboard-item">
                <div>
                    <span class="medal">${medal}</span>
                    ${t.tecnico}
                </div>
                <div><strong>${t.completadas}</strong></div>
            </div>
        `;
    });

    /* ================= Tendencia ================= */

    const labels = data.tendencia_mensual.map(r => r.mes);
    const valores = data.tendencia_mensual.map(r => r.total);

    const ctx = document.getElementById('graficaTendencia').getContext('2d');

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Instalaciones',
          data: valores,
          borderWidth: 2,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });

  }

  document.addEventListener('DOMContentLoaded', cargarDashboard);
</script>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>