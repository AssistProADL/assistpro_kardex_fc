<?php
/* public/bi/index.php — Dashboard Ejecutivo (AssistPro ER®) */
$activeSection = 'dashboard';
$activeItem    = 'ejecutivo';
$pageTitle     = 'Dashboard Ejecutivo · AssistPro ER®';
include __DIR__.'/_menu_global.php';
?>
<div class="container-fluid">

  <!-- Encabezado -->
  <div class="ap-card p-4 mb-3">
    <h4 class="mb-1" style="color:#0a2a6b;">Dashboard Ejecutivo</h4>
    <p class="text-secondary m-0">Resumen de KPIs globales, tendencias y alertas — vista solo-lectura con filtros por sesión (almacén/empresas asociadas).</p>
  </div>

  <!-- KPIs principales -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Movimientos (hoy)</div>
        <div class="h4 m-0" id="kpi-mov">—</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Valor Inventario</div>
        <div class="h4 m-0" id="kpi-valor">—</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">OTIF</div>
        <div class="h4 m-0" id="kpi-otif">—</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Ajustes/Mermas (mes)</div>
        <div class="h4 m-0" id="kpi-ajustes">—</div>
      </div>
    </div>
  </div>

  <!-- Gráficas 250px (placeholders listos para conectar) -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-xl-6">
      <div class="ap-card p-3">
        <h6 class="mb-2">Tendencia de movimientos</h6>
        <div style="height:250px"><canvas id="chMov"></canvas></div>
      </div>
    </div>
    <div class="col-12 col-xl-6">
      <div class="ap-card p-3">
        <h6 class="mb-2">Valor de inventario (diario)</h6>
        <div style="height:250px"><canvas id="chValor"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Accesos rápidos a módulos -->
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="ap-card p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <div>
          <h5 class="mb-1">⚙️ Productividad Operativa</h5>
          <p class="text-secondary mb-2">KPIs, tendencias y top actores por almacén/empresa.</p>
        </div>
        <a class="btn btn-primary" href="../dashboard/kardex_productividad.php">Abrir</a>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="ap-card p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <div>
          <h5 class="mb-1">💲 Finanzas</h5>
          <p class="text-secondary mb-2">Valor, Ajustes & Mermas, Costo por movimiento.</p>
        </div>
        <a class="btn btn-outline-primary" href="finanzas.php">Abrir</a>
      </div>
    </div>
  </div>

</div>

<!-- Librerías de gráficos (placeholders) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
/* Placeholders para que veas el layout; conecta a tus endpoints cuando gustes */
(function(){
  const ch1 = document.getElementById('chMov');
  const ch2 = document.getElementById('chValor');

  // Datos de ejemplo (borra y conecta a tu API)
  const days = Array.from({length:14}, (_,i)=> {
    const d = new Date(); d.setDate(d.getDate()-13+i);
    return d.toISOString().slice(0,10);
  });

  new Chart(ch1, {
    type: 'line',
    data: { labels: days, datasets: [{ label:'Movimientos', data: days.map(()=>Math.floor(50+Math.random()*120)) }] },
    options: { responsive:true, maintainAspectRatio:false }
  });

  new Chart(ch2, {
    type: 'line',
    data: { labels: days, datasets: [{ label:'Valor', data: days.map(()=>Math.floor(1_000_000 + Math.random()*250_000)) }] },
    options: { responsive:true, maintainAspectRatio:false }
  });

  // KPIs dummy
  document.getElementById('kpi-mov').textContent    = (Math.floor(120+Math.random()*80)).toLocaleString();
  document.getElementById('kpi-valor').textContent  = (1_250_000).toLocaleString();
  document.getElementById('kpi-otif').textContent   = '96.8%';
  document.getElementById('kpi-ajustes').textContent= (Math.floor(Math.random()*5000)).toLocaleString();
})();
</script>
<?php include __DIR__.'/_menu_global_end.php'; ?>
