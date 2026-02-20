<?php
/* =========================================================
   UI - DETALLE DE CORRIDA DE IMPORTACIÓN
   Ruta: /public/importadores/import_run_detalle.php
   ========================================================= */

$run_id = isset($_GET['run_id']) ? intval($_GET['run_id']) : 0;
if ($run_id <= 0) {
  die("run_id requerido");
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<div class="container-fluid px-4" style="font-size:10px;">
  <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
    <div>
      <h4 class="mb-1">Admin de Importaciones · Detalle de Corrida</h4>
      <div class="text-muted" id="lblFolio">Cargando corrida.</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="import_runs.php">
        <i class="fa fa-arrow-left"></i> Regresar
      </a>

      <button class="btn btn-outline-success" id="btnExportCsv" disabled>
        <i class="fa fa-file-csv"></i> CSV Validación
      </button>

      <button class="btn btn-primary" id="btnApply" disabled>
        <i class="fa fa-bolt"></i> Aplicar Importación
      </button>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Líneas</div>
        <div class="fs-4" id="kpiTotal">-</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">OK</div>
        <div class="fs-4 text-success" id="kpiOk">-</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Errores</div>
        <div class="fs-4 text-danger" id="kpiErr">-</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Estatus</div>
        <div class="fs-5" id="kpiStatus">-</div>
      </div></div>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Estado línea</label>
          <select class="form-select" id="fEstado">
            <option value="ALL">Todas</option>
            <option value="OK">OK</option>
            <option value="ERR">Errores</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label small text-muted mb-1">Buscar (mensaje o data_json)</label>
          <input class="form-control" id="fQ" placeholder="Ej: PSO2-1, RET-01, LP000123, lote." />
        </div>

        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Tamaño página</label>
          <select class="form-select" id="fPageSize">
            <option value="50">50</option>
            <option value="200" selected>200</option>
            <option value="500">500</option>
            <option value="1000">1000</option>
          </select>
        </div>

        <div class="col-md-2 d-grid">
          <button class="btn btn-dark" id="btnBuscar">
            <i class="fa fa-search"></i> Consultar
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small" id="lblPaging">-</div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary btn-sm" id="btnPrev" disabled>
            <i class="fa fa-chevron-left"></i>
          </button>
          <button class="btn btn-outline-secondary btn-sm" id="btnNext" disabled>
            <i class="fa fa-chevron-right"></i>
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:80px;">Línea</th>
              <th style="width:90px;">Estado</th>
              <th>BL Origen</th>
              <th>LP o Producto</th>
              <th>Lote/Serie</th>
              <th style="width:120px;">Cantidad</th>
              <th>ZRD_BL</th>
              <th>Mensaje</th>
            </tr>
          </thead>
          <tbody id="tbRows">
            <tr><td colspan="8" class="text-muted">Cargando.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const RUN_ID = <?= (int)$run_id ?>;
  const API_GET   = `../api/importadores/api_import_run_get.php`;
  const API_CSV   = `../api/importadores/api_import_run_export_csv.php`;
  const API_APPLY = `../api/importadores/api_import_run_apply.php`;

  const el = (id)=>document.getElementById(id);
  const tb = el('tbRows');

  let page = 1;
  let totalPages = 1;

  function badgeEstado(v){
    if(v === 'OK') return '<span class="badge bg-success">OK</span>';
    if(v === 'ERR') return '<span class="badge bg-danger">ERR</span>';
    return '<span class="badge bg-secondary">'+(v||'-')+'</span>';
  }

  function safe(v){ return (v === null || v === undefined || v === '') ? '-' : String(v); }

  function getDataField(row, key){
    if(!row || !row.data) return '-';
    if(row.data[key] !== undefined && row.data[key] !== null && row.data[key] !== '') return row.data[key];
    return '-';
  }

  async function load(){
    tb.innerHTML = '<tr><td colspan="8" class="text-muted">Consultando.</td></tr>';

    const estado = el('fEstado').value;
    const q = el('fQ').value.trim();
    const pageSize = parseInt(el('fPageSize').value, 10) || 200;

    const url = `${API_GET}?run_id=${RUN_ID}&estado=${encodeURIComponent(estado)}&page=${page}&page_size=${pageSize}&q=${encodeURIComponent(q)}`;
    const r = await fetch(url, { cache: 'no-store' });
    const j = await r.json();

    if(!j.ok){
      tb.innerHTML = `<tr><td colspan="8" class="text-danger">${safe(j.error)}${j.detail ? ("<br><small>"+safe(j.detail)+"</small>") : ""}</td></tr>`;
      return;
    }

    el('lblFolio').innerText = `${safe(j.run.folio_importacion)} · ${safe(j.run.tipo_ingreso)} · ${safe(j.run.importador?.descripcion)} · Archivo: ${safe(j.run.archivo_nombre)}`;
    el('kpiTotal').innerText = safe(j.run.totales?.total_lineas);
    el('kpiOk').innerText = safe(j.run.totales?.total_ok);
    el('kpiErr').innerText = safe(j.run.totales?.total_err);
    el('kpiStatus').innerText = safe(j.run.status);

    const totalLineas = parseInt(j.run.totales?.total_lineas || 0, 10);
    el('btnExportCsv').disabled = !(totalLineas > 0);

    const totalErr = parseInt(j.run.totales?.total_err || 0, 10);
    const st = String(j.run.status || '').toUpperCase();
    el('btnApply').disabled = !(st === 'VALIDADO' && totalErr === 0);

    totalPages = j.paging.total_pages || 1;
    el('lblPaging').innerText = `Página ${j.paging.page} de ${j.paging.total_pages} · ${j.paging.total_rows} renglones`;
    el('btnPrev').disabled = (page <= 1);
    el('btnNext').disabled = (page >= totalPages);

    const rows = Array.isArray(j.rows) ? j.rows : [];
    if(rows.length === 0){
      tb.innerHTML = '<tr><td colspan="8" class="text-muted">Sin resultados.</td></tr>';
      return;
    }

    tb.innerHTML = rows.map(rw=>{
      const bl   = getDataField(rw, 'BL_ORIGEN');
      const lp   = getDataField(rw, 'LP_O_PRODUCTO');
      const lote = getDataField(rw, 'LOTE_SERIE');
      const cant = getDataField(rw, 'CANTIDAD');
      const zrd  = getDataField(rw, 'ZRD_BL');

      return `
        <tr>
          <td>${rw.linea_num}</td>
          <td>${badgeEstado(rw.estado)}</td>
          <td>${safe(bl)}</td>
          <td>${safe(lp)}</td>
          <td>${safe(lote)}</td>
          <td class="text-end">${safe(cant)}</td>
          <td>${safe(zrd)}</td>
          <td class="text-muted">${safe(rw.mensaje)}</td>
        </tr>
      `;
    }).join('');
  }

  el('btnBuscar').addEventListener('click', ()=>{ page = 1; load(); });
  el('btnPrev').addEventListener('click', ()=>{ if(page>1){ page--; load(); }});
  el('btnNext').addEventListener('click', ()=>{ if(page<totalPages){ page++; load(); }});

  el('btnExportCsv').addEventListener('click', ()=>{
    const estado = el('fEstado').value;
    const q = el('fQ').value.trim();
    const url = `${API_CSV}?run_id=${RUN_ID}&estado=${encodeURIComponent(estado)}&q=${encodeURIComponent(q)}`;
    window.location.href = url;
  });

  el('btnApply').addEventListener('click', async ()=>{
    if(!confirm('¿Aplicar importación del run_id ' + RUN_ID + '?')) return;

    el('btnApply').disabled = true;

    try{
      const r = await fetch(API_APPLY, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ run_id: RUN_ID })
      });
      const j = await r.json();
      if(!j.ok){
        alert((j.error||'Error') + (j.detail?("\n"+j.detail):""));
        el('btnApply').disabled = false;
        return;
      }
      alert('Aplicación OK: ' + (j.message || 'Importación aplicada.'));
      load();
    }catch(e){
      alert('Error de red/servidor: ' + (e && e.message ? e.message : e));
      el('btnApply').disabled = false;
    }
  });

  load();
})();
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
