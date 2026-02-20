<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard Ubicaciones</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  body { font-size: 13px; }
  .container-fluid { padding: 10px !important; }
  .card { padding: 10px !important; }
  .card-kpi { border-left: 4px solid #0d6efd; }
  .card-kpi h6 { font-size: 12px; margin-bottom: 5px; }
  .card-kpi h3 { font-size: 20px; margin: 0; }

  #grafica { max-height: 200px !important; }
  .table-container { max-height: 400px; overflow: auto; }

  table.table td, table.table th { white-space: nowrap; }
</style>
</head>

<body>
<div class="container-fluid">

  <h4 class="mb-3">Dashboard Ubicaciones</h4>

  <!-- FILTROS -->
  <div class="card mb-3">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Empresa</label>
        <select id="empresa" class="form-select form-select-sm"></select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Almacén</label>
        <select id="almacen" class="form-select form-select-sm" disabled></select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Zona</label>
        <select id="zona" class="form-select form-select-sm" disabled></select>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-2 mb-3">
    <div class="col-md-2">
      <div class="card card-kpi">
        <h6>Total Ubicaciones</h6>
        <h3 id="kpi_total">0</h3>
      </div>
    </div>

    <div class="col-md-2">
      <div class="card card-kpi">
        <h6>Ubicaciones a Piso</h6>
        <h3 id="kpi_piso">0</h3>
      </div>
    </div>

    <div class="col-md-2">
      <div class="card card-kpi">
        <h6>Pasillos</h6>
        <h3 id="kpi_pasillos">0</h3>
      </div>
    </div>

    <div class="col-md-2">
      <div class="card card-kpi">
        <h6>Racks</h6>
        <h3 id="kpi_racks">0</h3>
      </div>
    </div>

    <div class="col-md-2">
      <div class="card card-kpi">
        <h6>Niveles</h6>
        <h3 id="kpi_niveles">0</h3>
      </div>
    </div>
  </div>

  <!-- GRÁFICA -->
  <div class="card mb-3">
    <div style="height:200px;">
      <canvas id="grafica"></canvas>
    </div>
  </div>

  <!-- TABLA -->
  <div class="card">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <strong>Ubicaciones</strong>
      <button class="btn btn-success btn-sm" id="btnExport" type="button">Exportar CSV</button>
    </div>

    <div class="table-container">
      <table class="table table-bordered table-sm table-hover">
        <thead class="table-light">
          <tr>
            <th>Almacén</th>
            <th>Zona</th>
            <th>BL (Bin Location)</th>
            <th>Pasillo</th>
            <th>Rack</th>
            <th>Nivel</th>
          </tr>
        </thead>
        <tbody id="tablaBody"></tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
      <small id="infoRegistros">Mostrando 0 - 0 de 0</small>
      <div class="d-flex gap-1">
        <button class="btn btn-outline-secondary btn-sm" id="btnPrev" type="button">Anterior</button>
        <button class="btn btn-outline-secondary btn-sm" id="btnNext" type="button">Siguiente</button>
      </div>
    </div>
  </div>

</div>

<script>
const HEADER_API = '../api/ubicaciones/api_header.php';
const UBIC_API   = '../api/ubicaciones/api_ubicaciones.php';

let catalogos = { empresas: [], almacenes: [], zonas: [] };
let paginaActual = 1;
const registrosPorPagina = 25;

let chart = null;

const $empresa = document.getElementById('empresa');
const $almacen = document.getElementById('almacen');
const $zona    = document.getElementById('zona');

const $tbody = document.getElementById('tablaBody');
const $info  = document.getElementById('infoRegistros');

const $kpiTotal   = document.getElementById('kpi_total');
const $kpiPiso    = document.getElementById('kpi_piso');
const $kpiPasillo = document.getElementById('kpi_pasillos');
const $kpiRack    = document.getElementById('kpi_racks');
const $kpiNivel   = document.getElementById('kpi_niveles');

const $btnPrev   = document.getElementById('btnPrev');
const $btnNext   = document.getElementById('btnNext');
const $btnExport = document.getElementById('btnExport');

// -------------------------------
// Helpers UI
// -------------------------------
function setSelectOptions(selectEl, items, placeholder = 'Seleccione') {
  const opts = ['<option value="">'+placeholder+'</option>'].concat(
    items.map(it => `<option value="${it.value}">${it.label}</option>`)
  );
  selectEl.innerHTML = opts.join('');
}

function pad2(n){ return String(n).padStart(2,'0'); }
function ymd(){
  const d = new Date();
  return `${d.getFullYear()}${pad2(d.getMonth()+1)}${pad2(d.getDate())}`;
}

function getTextOrEmpty(sel){
  return sel?.selectedOptions?.[0]?.text ? sel.selectedOptions[0].text : '';
}

// -------------------------------
// Carga catálogos (header)
// -------------------------------
async function cargarCatalogos() {
  const res = await fetch(HEADER_API, { cache: 'no-store' });
  const json = await res.json();

  if (!json.success) {
    console.error('HEADER_API error:', json);
    alert('No se pudo cargar catálogos.');
    return;
  }

  catalogos.empresas  = json.empresas  || [];
  catalogos.almacenes = json.almacenes || [];
  catalogos.zonas     = json.zonas     || [];

  // Empresas: "empresa_clave - empresa"
  const empresasOpt = catalogos.empresas.map(e => ({
    value: e.cve_cia,
    label: `${e.clave_empresa} - ${e.des_cia}`
  }));
  setSelectOptions($empresa, empresasOpt, 'Seleccione');

  // reset almacén/zona
  setSelectOptions($almacen, [], 'Seleccione');
  setSelectOptions($zona, [], 'Seleccione');
  $almacen.disabled = true;
  $zona.disabled = true;
}

// -------------------------------
// Cascada: almacenes por empresa
// -------------------------------
function cargarAlmacenesPorEmpresa() {
  const emp = $empresa.value;
  if (!emp) {
    setSelectOptions($almacen, [], 'Seleccione');
    setSelectOptions($zona, [], 'Seleccione');
    $almacen.disabled = true;
    $zona.disabled = true;
    return;
  }

  const almacenesFil = catalogos.almacenes
    .filter(a => String(a.cve_cia) === String(emp))
    .map(a => ({
      value: a.id, // OJO: tu API usa almacen = c_almacenp.id
      label: `${a.clave} - ${a.nombre}`
    }))
    .sort((a,b) => a.label.localeCompare(b.label));

  setSelectOptions($almacen, almacenesFil, 'Seleccione');
  $almacen.disabled = false;

  // reset zonas al cambiar empresa
  setSelectOptions($zona, [], 'Seleccione');
  $zona.disabled = true;
}

// -------------------------------
// Cascada: zonas por almacén
// -------------------------------
function cargarZonasPorAlmacen() {
  const almacenId = $almacen.value;
  if (!almacenId) {
    setSelectOptions($zona, [], 'Seleccione');
    $zona.disabled = true;
    return;
  }

  // tu api_header trae: cve_almacenp (id de almacenp), cve_almac (id zona), des_almac
  const zonasFil = catalogos.zonas
    .filter(z => String(z.cve_almacenp) === String(almacenId))
    .map(z => ({
      value: z.cve_almac,
      label: (z.clave_almacen ? `${z.clave_almacen} - ` : '') + (z.des_almac || z.nombre || '')
    }))
    .sort((a,b) => a.label.localeCompare(b.label));

  setSelectOptions($zona, zonasFil, 'Seleccione');
  $zona.disabled = false;
}

// -------------------------------
// KPIs + Piso (nivel = 0)
// -------------------------------
function contarPisoDesdeTabla(dataRows) {
  // “piso” = Nivel == 0 o "0" o "00"
  let c = 0;
  for (const r of dataRows) {
    const n = (r.cve_nivel ?? '').toString().trim();
    if (n === '0' || n === '00') c++;
  }
  return c;
}

async function cargarCardsYTablaYChart() {
  const empresa = $empresa.value;
  const almacen = $almacen.value;
  const zona    = $zona.value;

  if (!empresa) {
    // limpia
    $kpiTotal.textContent = '0';
    $kpiPiso.textContent  = '0';
    $kpiPasillo.textContent = '0';
    $kpiRack.textContent = '0';
    $kpiNivel.textContent = '0';
    $tbody.innerHTML = '';
    $info.textContent = 'Mostrando 0 - 0 de 0';
    renderChart([], []);
    return;
  }

  // 1) Cards
  {
    let url = `${UBIC_API}?mode=cards&empresa=${encodeURIComponent(empresa)}`;
    if (almacen) url += `&almacen=${encodeURIComponent(almacen)}`;
    if (zona)    url += `&zona=${encodeURIComponent(zona)}`;

    const res = await fetch(url, { cache: 'no-store' });
    const json = await res.json();

    if (json.success && json.cards) {
      $kpiTotal.textContent   = (json.cards.total_ubicaciones ?? 0);
      $kpiPasillo.textContent = (json.cards.total_pasillos ?? 0);
      $kpiRack.textContent    = (json.cards.total_racks ?? 0);
      $kpiNivel.textContent   = (json.cards.total_niveles ?? 0);
      // kpi_piso lo vamos a obtener con un query “ligero” desde la tabla de esta página
      // (si quieres 100% exacto global, lo agregamos en el API con COUNT DISTINCT WHERE nivel=0)
    } else {
      console.warn('Cards error:', json);
    }
  }

  // 2) Tabla paginada
  let total = 0;
  let totalPages = 0;
  let rows = [];
  {
    let url = `${UBIC_API}?empresa=${encodeURIComponent(empresa)}&page=${paginaActual}&limit=${registrosPorPagina}&order=codigo&dir=ASC`;
    if (almacen) url += `&almacen=${encodeURIComponent(almacen)}`;
    if (zona)    url += `&zona=${encodeURIComponent(zona)}`;

    const res = await fetch(url, { cache: 'no-store' });
    const json = await res.json();

    if (!json.success) {
      console.error('Tabla error:', json);
      $tbody.innerHTML = '';
      $info.textContent = 'Mostrando 0 - 0 de 0';
      renderChart([], []);
      return;
    }

    total = json.pagination?.total ?? 0;
    totalPages = json.pagination?.total_pages ?? 0;
    rows = json.data || [];

    // Render tabla
    $tbody.innerHTML = rows.map(r => {
      const almacenTxt = `${r.almacen_clave ?? ''} - ${r.almacen ?? ''}`.trim();
      const zonaTxt    = (r.zona ?? '');
      const bl         = (r.CodigoCSD ?? '');
      const pasillo    = (r.cve_pasillo ?? '');
      const rack       = (r.cve_rack ?? '');
      const nivel      = (r.cve_nivel ?? '');
      return `
        <tr>
          <td>${escapeHtml(almacenTxt)}</td>
          <td>${escapeHtml(zonaTxt)}</td>
          <td>${escapeHtml(bl)}</td>
          <td>${escapeHtml(pasillo)}</td>
          <td>${escapeHtml(rack)}</td>
          <td>${escapeHtml(nivel)}</td>
        </tr>
      `;
    }).join('');

    const start = total === 0 ? 0 : ((paginaActual - 1) * registrosPorPagina) + 1;
    const end   = Math.min(paginaActual * registrosPorPagina, total);
    $info.textContent = `Mostrando ${start} - ${end} de ${total}`;

    $btnPrev.disabled = paginaActual <= 1;
    $btnNext.disabled = paginaActual >= totalPages;

    // Piso aproximado (de esta página)
    $kpiPiso.textContent = contarPisoDesdeTabla(rows);
  }

  // 3) Chart (Ubicaciones por Rack)
  {
    let url = `${UBIC_API}?mode=grafico&chart=rack&empresa=${encodeURIComponent(empresa)}`;
    if (almacen) url += `&almacen=${encodeURIComponent(almacen)}`;
    if (zona)    url += `&zona=${encodeURIComponent(zona)}`;

    const res = await fetch(url, { cache: 'no-store' });
    const json = await res.json();

    if (json.success && Array.isArray(json.data)) {
      const labels  = json.data.map(x => x.categoria ?? '');
      const valores = json.data.map(x => Number(x.total ?? 0));
      renderChart(labels, valores);
    } else {
      renderChart([], []);
    }
  }
}

function escapeHtml(s) {
  return (s ?? '').toString()
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

// -------------------------------
// Gráfica corporativa (con título)
// -------------------------------
function buildChartTitle() {
  const fecha = new Date().toLocaleDateString('es-MX');
  const empresaTxt = getTextOrEmpty($empresa);
  const almacenTxt = getTextOrEmpty($almacen);
  const zonaTxt    = getTextOrEmpty($zona);

  let t = 'Ubicaciones por Rack';
  if (almacenTxt && almacenTxt !== 'Seleccione') t += ` - ${almacenTxt}`;
  if (zonaTxt && zonaTxt !== 'Seleccione') t += ` - ${zonaTxt}`;

  // Empresa puede quedar largo; lo dejamos al final como “firma”
  if (empresaTxt && empresaTxt !== 'Seleccione') t += ` | ${empresaTxt}`;
  t += ` | ${fecha}`;

  return t;
}

function renderChart(labels, valores) {
  const canvas = document.getElementById('grafica');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const tituloGrafico = buildChartTitle();

  if (chart) chart.destroy();

  chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Ubicaciones',
        data: valores,
        backgroundColor: '#1e88e5',
        borderRadius: 6,
        barPercentage: 0.8,
        categoryPercentage: 0.9
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: tituloGrafico,
          color: '#0d47a1',
          font: { size: 14, weight: 'bold' },
          padding: { top: 6, bottom: 10 }
        },
        legend: { display: false }
      },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true }
      }
    }
  });
}

// -------------------------------
// Exportar CSV (con filename sugerido)
// -------------------------------
function exportarCSV() {
  const empresa = $empresa.value;
  const almacen = $almacen.value;
  const zona    = $zona.value;

  if (!empresa) {
    alert("Seleccione empresa");
    return;
  }

  const almacenTxt = getTextOrEmpty($almacen);
  // intenta tomar clave antes de " - "
  const almacenClave = (almacenTxt || '').split(' - ')[0] || 'ALL';

  let url = `${UBIC_API}?mode=csv&empresa=${encodeURIComponent(empresa)}`;
  if (almacen) url += `&almacen=${encodeURIComponent(almacen)}`;
  if (zona)    url += `&zona=${encodeURIComponent(zona)}`;

  // nombre sugerido (si tu API lo soporta; si no, lo ignora sin problema)
  const filename = `ubicaciones_${almacenClave}_${ymd()}.csv`;
  url += `&filename=${encodeURIComponent(filename)}`;

  // descarga
  window.location.href = url;
}

// -------------------------------
// Eventos
// -------------------------------
$empresa.addEventListener('change', async () => {
  paginaActual = 1;
  cargarAlmacenesPorEmpresa();
  await cargarCardsYTablaYChart();
});

$almacen.addEventListener('change', async () => {
  paginaActual = 1;
  cargarZonasPorAlmacen();
  await cargarCardsYTablaYChart();
});

$zona.addEventListener('change', async () => {
  paginaActual = 1;
  await cargarCardsYTablaYChart();
});

$btnPrev.addEventListener('click', async () => {
  if (paginaActual > 1) {
    paginaActual--;
    await cargarCardsYTablaYChart();
  }
});

$btnNext.addEventListener('click', async () => {
  paginaActual++;
  await cargarCardsYTablaYChart();
});

$btnExport.addEventListener('click', exportarCSV);

// Init
document.addEventListener('DOMContentLoaded', async () => {
  await cargarCatalogos();
  renderChart([], []);
});
</script>

</body>
</html>
