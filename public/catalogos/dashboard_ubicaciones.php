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

  /* tabla compacta */
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
  let datosGlobal = [];
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

  function setSelectOptions(selectEl, options, placeholder = 'Seleccione') {
    selectEl.innerHTML = `<option value="">${placeholder}</option>` + options.join('');
  }

  function normalizeNivel(n) {
    // soporta 0, "0", "00", "DA" (si existiera), etc.
    const s = (n ?? '').toString().trim();
    // si viene "00" o "0" => piso
    if (s === '0' || s === '00') return '0';
    // si viene number 0
    if (n === 0) return '0';
    return s;
  }

  async function cargarCatalogos() {
    const res = await fetch(HEADER_API, { cache: 'no-store' });
    const json = await res.json();

    if (!json || json.success !== true) {
      console.error('api_header error:', json);
      return;
    }

    catalogos = json;

    // Empresas
    const empresaOpts = catalogos.empresas.map(e => {
      const id = e.empresa_id;
      const txt = `${e.empresa_clave} - ${e.empresa}`;
      return `<option value="${id}">${txt}</option>`;
    });

    setSelectOptions($empresa, empresaOpts, 'Seleccione');

    // reset selects
    resetAlmacenZona();
  }

  function resetAlmacenZona() {
    $almacen.disabled = true;
    $zona.disabled = true;
    setSelectOptions($almacen, [], 'Seleccione');
    setSelectOptions($zona, [], 'Seleccione');
  }

  function cargarAlmacenesPorEmpresa(empresaId) {
    const filtrados = catalogos.almacenes.filter(a => String(a.empresa_id) === String(empresaId));

    const opts = filtrados.map(a => {
      const id = a.almacen_id;
      const txt = `${a.almacen_clave} - ${a.almacen}`;
      return `<option value="${id}">${txt}</option>`;
    });

    $almacen.disabled = false;
    setSelectOptions($almacen, opts, 'Seleccione');

    // zona depende del almacén
    $zona.disabled = true;
    setSelectOptions($zona, [], 'Seleccione');
  }

  function cargarZonasPorAlmacen(almacenId) {
    const filtradas = catalogos.zonas.filter(z => String(z.almacen_id) === String(almacenId));

    const opts = filtradas.map(z => {
      const id = z.zona_id;
      const txt = `${z.zona}`;
      return `<option value="${id}">${txt}</option>`;
    });

    $zona.disabled = false;
    setSelectOptions($zona, opts, 'Seleccione');
  }

  function buildParams({ empresa, almacen, zona, exportCsv=false } = {}) {
    const params = new URLSearchParams();
    if (empresa) params.append('empresa', empresa);
    if (almacen) params.append('almacen', almacen);
    if (zona)    params.append('zona', zona);
    if (exportCsv) params.append('export', 'csv');
    // limit alto (si el API lo soporta)
    params.append('limit', '15000');
    return params;
  }

  async function ejecutarBusqueda() {
    const empresa = $empresa.value;
    const almacen = $almacen.value;
    const zona    = $zona.value;

    // Si no hay empresa, limpiar todo
    if (!empresa) {
      datosGlobal = [];
      paginaActual = 1;
      renderKPIs([]);
      renderGrafica([]);
      pintarPagina();
      resetAlmacenZona();
      return;
    }

    const params = buildParams({ empresa, almacen, zona, exportCsv:false });

    const res = await fetch(`${UBIC_API}?${params.toString()}`, { cache: 'no-store' });
    const json = await res.json();

    // api_ubicaciones puede responder {success:true, data:[...]} o sólo [...]
    const data = Array.isArray(json) ? json : (json.data || []);

    datosGlobal = data;
    paginaActual = 1;

    renderKPIs(datosGlobal);
    renderGrafica(datosGlobal);
    pintarPagina();
  }

  function renderKPIs(data) {
    const total = data.length;

    // Piso: nivel == 0/"0"/"00"
    const piso = data.filter(x => normalizeNivel(x.cve_nivel) === '0').length;

    const pasillos = new Set(data.map(x => (x.cve_pasillo ?? '').toString()));
    const racks    = new Set(data.map(x => (x.cve_rack ?? '').toString()));
    const niveles  = new Set(data.map(x => (x.cve_nivel ?? '').toString()));

    // quitar vacíos del set
    pasillos.delete('');
    racks.delete('');
    niveles.delete('');

    $kpiTotal.innerText   = total;
    $kpiPiso.innerText    = piso;
    $kpiPasillo.innerText = pasillos.size;
    $kpiRack.innerText    = racks.size;
    $kpiNivel.innerText   = niveles.size;
  }

  function renderGrafica(data) {
    if (chart) chart.destroy();

    const conteo = {};
    for (const row of data) {
      const rack = (row.cve_rack ?? 'Sin Rack').toString().trim() || 'Sin Rack';
      conteo[rack] = (conteo[rack] || 0) + 1;
    }

    const labels = Object.keys(conteo);
    const values = Object.values(conteo);

    chart = new Chart(document.getElementById('grafica'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Ubicaciones por Rack',
          data: values
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });
  }

  function pintarPagina() {
    $tbody.innerHTML = '';

    const total = datosGlobal.length;
    const inicio = (paginaActual - 1) * registrosPorPagina;
    const fin = Math.min(inicio + registrosPorPagina, total);
    const pagina = datosGlobal.slice(inicio, fin);

    for (const row of pagina) {
      const almacenTxt = row.almacen_clave && row.almacen
        ? `${row.almacen_clave} - ${row.almacen}`
        : (row.almacen ?? '');

      const zonaTxt = row.zona ?? '';
      const bl = row.CodigoCSD ?? ''; // BL Bin Location

      const pasillo = row.cve_pasillo ?? '';
      const rack = row.cve_rack ?? '';
      const nivel = row.cve_nivel ?? '';

      $tbody.innerHTML += `
        <tr>
          <td>${escapeHtml(almacenTxt)}</td>
          <td>${escapeHtml(zonaTxt)}</td>
          <td>${escapeHtml(bl)}</td>
          <td>${escapeHtml(pasillo)}</td>
          <td>${escapeHtml(rack)}</td>
          <td>${escapeHtml(nivel)}</td>
        </tr>
      `;
    }

    if (total === 0) {
      $info.innerText = 'Mostrando 0 - 0 de 0';
    } else {
      $info.innerText = `Mostrando ${inicio + 1} - ${fin} de ${total}`;
    }
  }

  function nextPage() {
    const total = datosGlobal.length;
    if (paginaActual * registrosPorPagina < total) {
      paginaActual++;
      pintarPagina();
    }
  }

  function prevPage() {
    if (paginaActual > 1) {
      paginaActual--;
      pintarPagina();
    }
  }

function exportarCSV() {

    const empresa = document.getElementById("empresa").value;
    const almacen = document.getElementById("almacen").value;
    const zona    = document.getElementById("zona").value;

    let url = "../api/ubicaciones/api_ubicaciones.php?mode=csv";

    url += "&empresa=" + empresa;

    if (almacen) url += "&almacen=" + almacen;
    if (zona)    url += "&zona=" + zona;

    window.open(url, "_blank");
}

 

  // Seguridad mínima para HTML
  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  // LISTENERS
  $empresa.addEventListener('change', () => {
    const empresaId = $empresa.value;

    if (!empresaId) {
      resetAlmacenZona();
      ejecutarBusqueda();
      return;
    }

    cargarAlmacenesPorEmpresa(empresaId);

    // al cambiar empresa: ejecuta (cuenta todo empresa)
    ejecutarBusqueda();
  });

  $almacen.addEventListener('change', () => {
    const almacenId = $almacen.value;

    if (!almacenId) {
      // si quita almacén: deshabilitar zona y ejecutar (cuenta todo empresa)
      $zona.disabled = true;
      setSelectOptions($zona, [], 'Seleccione');
      ejecutarBusqueda();
      return;
    }

    cargarZonasPorAlmacen(almacenId);

    // ejecutar con empresa+almacen
    ejecutarBusqueda();
  });

  $zona.addEventListener('change', () => {
    // ejecutar con empresa+almacen+zona (si hay)
    ejecutarBusqueda();
  });

  document.getElementById('btnNext').addEventListener('click', nextPage);
  document.getElementById('btnPrev').addEventListener('click', prevPage);
  document.getElementById('btnExport').addEventListener('click', exportarCSV);

  // INIT
  (async function init(){
    await cargarCatalogos();
    // opcional: no auto-selecciona empresa; si quieres autoseleccionar la 1ra, descomenta:
    // if ($empresa.options.length > 1) { $empresa.selectedIndex = 1; $empresa.dispatchEvent(new Event('change')); }
  })();
</script>

</body>
</html>
