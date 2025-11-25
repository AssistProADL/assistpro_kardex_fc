<?php
// public/dash_inventarios.php
require_once __DIR__ . '/../app/db.php'; // <-- tu conexión PDO $pdo

// Helpers de selects
$empresas = $pdo->query("SELECT id, nombre FROM c_empresa ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$almacenes = $pdo->query("SELECT id, nombre FROM c_almacen ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard Inventarios | AssistPro SFA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- estilos SFA + un poco de flex -->
  <link rel="stylesheet" href="assets/sfa.css">
  <style>
    .kpi{display:flex;gap:12px;flex-wrap:wrap;margin:10px 0}
    .kpi .card{flex:1 1 220px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .kpi .num{font-size:22px;font-weight:700}
    .grid{overflow:auto;border:1px solid #e5e7eb;border-radius:12px;background:#fff}
    .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin:10px 0}
    .charts{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px}
    canvas{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:8px}
    .btn{background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 12px;cursor:pointer}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #eee}
    th{background:#f8fafc;text-align:left}
  </style>
</head>
<body>

<div class="container">
  <h2>Dashboard de Inventarios</h2>

  <!-- Filtros -->
  <form id="filtros" class="filters">
    <label>Empresa
      <select name="empresa_id" id="empresa_id">
        <option value="">Todas</option>
        <?php foreach($empresas as $e): ?>
          <option value="<?=$e['id']?>"><?=htmlspecialchars($e['nombre'])?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Almacén
      <select name="almacen_id" id="almacen_id">
        <option value="">Todos</option>
        <?php foreach($almacenes as $a): ?>
          <option value="<?=$a['id']?>"><?=htmlspecialchars($a['nombre'])?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Desde <input type="date" id="f_ini"></label>
    <label>Hasta <input type="date" id="f_fin"></label>
    <button type="button" class="btn" id="btnAplicar">Aplicar</button>
    <a class="btn" id="btnExport" href="api/stock_export.php" target="_blank">Exportar stock (CSV)</a>
  </form>

  <!-- KPIs -->
  <section class="kpi" id="kpis">
    <div class="card"><div>Total Productos</div><div class="num" id="kpi_prod">—</div></div>
    <div class="card"><div>Total Ubicaciones</div><div class="num" id="kpi_ubi">—</div></div>
    <div class="card"><div>Total LP</div><div class="num" id="kpi_lp">—</div></div>
    <div class="card"><div>Inventario Total</div><div class="num" id="kpi_inv">—</div></div>
  </section>

  <!-- Gráficas -->
  <section class="charts">
    <div><h4>Entradas vs Salidas (60 días)</h4><canvas id="chMov"></canvas></div>
    <div><h4>Stock por Zona</h4><canvas id="chZona"></canvas></div>
    <div><h4>LP por Estado</h4><canvas id="chLp"></canvas></div>
    <div><h4>Top 15 SKU por Stock</h4><canvas id="chTopSku"></canvas></div>
  </section>

  <!-- Grilla -->
  <h3 style="margin-top:18px">Stock detallado (ubicación / producto / lote / serie)</h3>
  <div class="grid">
    <table id="tblStock">
      <thead>
        <tr>
          <th>Almacén</th><th>Ubicación</th><th>SKU</th><th>Producto</th>
          <th>Lote</th><th>Serie</th><th style="text-align:right">Stock</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
  const qs = (s)=>document.querySelector(s);
  let chMov, chZona, chLp, chTop;

  function fetchJSON(url, params={}) {
    if (Object.keys(params).length){
      url += '?' + new URLSearchParams(params).toString();
    }
    return fetch(url).then(r=>r.json());
  }

  async function cargarKPIs(params){
    const k = await fetchJSON('api/kpi_resumen.php', params);
    qs('#kpi_prod').textContent = k.total_productos?.toLocaleString() ?? '0';
    qs('#kpi_ubi').textContent  = k.total_ubicaciones?.toLocaleString() ?? '0';
    qs('#kpi_lp').textContent   = k.total_lps?.toLocaleString() ?? '0';
    qs('#kpi_inv').textContent  = (k.inventario_total ?? 0).toLocaleString(undefined,{maximumFractionDigits:2});
  }

  function drawOrUpdateChart(ctx, oldChart, cfg){
    if (oldChart) oldChart.destroy();
    return new Chart(ctx, cfg);
  }

  async function cargarGraficas(params){
    const mov   = await fetchJSON('api/mov_diario.php', params);
    const zona  = await fetchJSON('api/stock_zona.php', params);
    const lp    = await fetchJSON('api/lp_estado.php', params);
    const top   = await fetchJSON('api/top_sku.php', params);

    chMov  = drawOrUpdateChart(qs('#chMov'), chMov, {
      type:'line',
      data:{labels:mov.map(x=>x.dia),
            datasets:[{label:'Entradas', data:mov.map(x=>+x.entradas)},
                      {label:'Salidas',  data:mov.map(x=>+x.salidas)}]},
      options:{responsive:true, plugins:{legend:{position:'bottom'}}}
    });

    chZona = drawOrUpdateChart(qs('#chZona'), chZona, {
      type:'bar',
      data:{labels:zona.map(x=>x.zona),
            datasets:[{label:'Stock', data:zona.map(x=>+x.stock)}]},
      options:{responsive:true, plugins:{legend:{display:false}}}
    });

    chLp = drawOrUpdateChart(qs('#chLp'), chLp, {
      type:'doughnut',
      data:{labels:lp.map(x=>x.estado),
            datasets:[{label:'LP', data:lp.map(x=>+x.total)}]},
      options:{responsive:true, plugins:{legend:{position:'bottom'}}}
    });

    chTop = drawOrUpdateChart(qs('#chTopSku'), chTop, {
      type:'bar',
      data:{labels:top.map(x=>x.sku),
            datasets:[{label:'Stock', data:top.map(x=>+x.stock)}]},
      options:{indexAxis:'y', responsive:true, plugins:{legend:{display:false}}}
    });
  }

  async function cargarTabla(params){
    const tb = qs('#tblStock tbody');
    tb.innerHTML = '<tr><td colspan="7">Cargando…</td></tr>';
    const rows = await fetchJSON('api/stock_tabla.php', params);
    tb.innerHTML = rows.map(r => `
      <tr>
        <td>${r.almacen_id}</td>
        <td>${r.ubicacion ?? ''}</td>
        <td>${r.sku}</td>
        <td>${r.producto ?? ''}</td>
        <td>${r.lote ?? ''}</td>
        <td>${r.serie ?? ''}</td>
        <td style="text-align:right">${(+r.stock).toLocaleString(undefined,{maximumFractionDigits:2})}</td>
      </tr>`).join('');
  }

  function params(){
    return {
      empresa_id: qs('#empresa_id').value || '',
      almacen_id: qs('#almacen_id').value || '',
      f_ini: qs('#f_ini').value || '',
      f_fin: qs('#f_fin').value || ''
    };
  }

  async function aplicar(){
    const p = params();
    // link de export
    qs('#btnExport').href = 'api/stock_export.php?' + new URLSearchParams(p).toString();
    await cargarKPIs(p);
    await cargarGraficas(p);
    await cargarTabla(p);
  }

  qs('#btnAplicar').addEventListener('click', aplicar);
  // carga inicial
  aplicar();
</script>
</body>
</html>
