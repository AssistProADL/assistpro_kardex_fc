<?php
// public/catalogos/rutas.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$apiUrl = '../api/rutas_api.php';
?>

<style>
  .ap-title { font-weight: 700; }
  .ap-kpi { border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
  .ap-kpi .kpi-val { font-size: 18px; font-weight: 800; }
  table.dataTable { font-size: 10px; }

  /* IMPORTANTE: sin max-height para que se vean 25 filas completas */
  .dt-container {
    overflow-x: auto;
    overflow-y: visible;
    max-height: none;
    border: 1px solid #e6e6e6;
    border-radius: 12px;
  }

  .btn-xs { padding: .15rem .35rem; font-size: .75rem; }
  .modal .form-label { font-size: .85rem; font-weight: 700; }
  .required-chip { font-size: .75rem; }
</style>

<div class="container-fluid py-2">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <i class="fa-solid fa-route text-primary"></i>
      <div class="ap-title">Catálogo de Rutas</div>
    </div>
    <div class="required-chip badge rounded-pill text-bg-light border">
      <span class="text-danger">*</span> Obligatorios: <b>cve_ruta</b>, <b>descripcion</b>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-12 col-md-3">
      <div class="card ap-kpi">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between"><div class="text-muted">Total</div><i class="fa-regular fa-rectangle-list"></i></div>
          <div class="kpi-val" id="kpi_total">0</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card ap-kpi">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between"><div class="text-muted">Activos</div><i class="fa-solid fa-circle-check text-success"></i></div>
          <div class="kpi-val" id="kpi_activos">0</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card ap-kpi">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between"><div class="text-muted">Preventa</div><i class="fa-solid fa-truck-fast"></i></div>
          <div class="kpi-val" id="kpi_preventa">0</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card ap-kpi">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between"><div class="text-muted">Entrega</div><i class="fa-solid fa-truck-ramp-box"></i></div>
          <div class="kpi-val" id="kpi_entrega">0</div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <div class="input-group" style="max-width: 520px;">
      <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
      <input type="text" id="txtBuscar" class="form-control" placeholder="Buscar por cve_ruta, descripción, almacén, status...">
      <button class="btn btn-outline-secondary" id="btnLimpiar" type="button"><i class="fa-solid fa-eraser"></i> Limpiar</button>
    </div>

    <button class="btn btn-primary" id="btnNuevo" type="button"><i class="fa-solid fa-plus"></i> Nuevo</button>
    <button class="btn btn-outline-primary" id="btnRefrescar" type="button"><i class="fa-solid fa-rotate"></i> Refrescar</button>

    <button class="btn btn-outline-secondary" id="btnExport" type="button"><i class="fa-solid fa-file-arrow-down"></i> Exportar CSV</button>

    <label class="btn btn-outline-success mb-0" for="fileImport"><i class="fa-solid fa-file-arrow-up"></i> Importar CSV</label>
    <input type="file" id="fileImport" accept=".csv" class="d-none">

    <button class="btn btn-outline-secondary" id="btnLayout" type="button"><i class="fa-regular fa-file-lines"></i> Layout</button>

    <div class="form-check form-switch ms-auto">
      <input class="form-check-input" type="checkbox" role="switch" id="swInactivos">
      <label class="form-check-label" for="swInactivos">Ver inactivos</label>
    </div>
  </div>

  <div class="dt-container">
    <table id="tbl" class="table table-striped table-hover table-sm w-100">
      <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
        <tr>
          <th style="width:80px;">Acciones</th>
          <th>ID_Ruta</th>
          <th>cve_ruta</th>
          <th>descripcion</th>
          <th>status</th>
          <th>cve_almacenp</th>
          <th>venta_preventa</th>
          <th>es_entrega</th>
          <th>control_pallets_cont</th>
          <th>consig_pallets</th>
          <th>consig_cont</th>
          <th>ID_Proveedor</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="fa-solid fa-route me-2"></i>Ruta</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ID_Ruta">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">cve_ruta *</label>
            <input type="text" class="form-control" id="cve_ruta" maxlength="20">
          </div>
          <div class="col-md-8">
            <label class="form-label">descripcion *</label>
            <input type="text" class="form-control" id="descripcion" maxlength="50">
          </div>

          <div class="col-md-3">
            <label class="form-label">status</label>
            <select id="status" class="form-select">
              <option value="A">A</option>
              <option value="B">B</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">cve_almacenp</label>
            <input type="number" class="form-control" id="cve_almacenp" value="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">venta_preventa</label>
            <select id="venta_preventa" class="form-select">
              <option value="1">1</option>
              <option value="0">0</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">es_entrega</label>
            <select id="es_entrega" class="form-select">
              <option value="0">0</option>
              <option value="1">1</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">control_pallets_cont</label>
            <select id="control_pallets_cont" class="form-select">
              <option value="N">N</option>
              <option value="S">S</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">consig_pallets</label>
            <input type="number" class="form-control" id="consig_pallets" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">consig_cont</label>
            <input type="number" class="form-control" id="consig_cont" value="0">
          </div>

          <div class="col-md-4">
            <label class="form-label">ID_Proveedor</label>
            <input type="number" class="form-control" id="ID_Proveedor" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Activo</label>
            <select id="Activo" class="form-select">
              <option value="1">1</option>
              <option value="0">0</option>
            </select>
          </div>
        </div>

        <div class="mt-2 text-muted" style="font-size: 12px;">
          Nota: <b>ID_Ruta</b> es autoincremental y no se captura en alta.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" id="btnGuardar"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script>
  const API_URL = <?= json_encode($apiUrl) ?>;
  let dt = null;

  // Bootstrap fallback
  let mdl = null;

  function ensureBootstrap(cb){
    if (window.bootstrap && bootstrap.Modal){ cb(); return; }
    const already = document.querySelector('script[data-ap-bootstrap="1"]');
    if (already){ already.addEventListener('load', cb); return; }
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
    s.defer = true;
    s.dataset.apBootstrap = '1';
    s.onload = cb;
    s.onerror = cb;
    document.head.appendChild(s);
  }

  function getModal(){
    if (mdl) return mdl;
    if (window.bootstrap && bootstrap.Modal){
      mdl = new bootstrap.Modal(document.getElementById('mdl'));
      return mdl;
    }
    return null;
  }

  function showModal(){
    const m = getModal();
    if (m) return m.show();
    if (window.jQuery && jQuery.fn && jQuery.fn.modal) return jQuery('#mdl').modal('show');
    document.getElementById('mdl').classList.add('show');
    document.getElementById('mdl').style.display = 'block';
  }

  function hideModal(){
    const m = getModal();
    if (m) return m.hide();
    if (window.jQuery && jQuery.fn && jQuery.fn.modal) return jQuery('#mdl').modal('hide');
    document.getElementById('mdl').classList.remove('show');
    document.getElementById('mdl').style.display = 'none';
  }

  function toast(msg){ alert(msg); }

  function setKpis(k){
    document.getElementById('kpi_total').textContent = k.total ?? 0;
    document.getElementById('kpi_activos').textContent = k.activos ?? 0;
    document.getElementById('kpi_preventa').textContent = k.preventa ?? 0;
    document.getElementById('kpi_entrega').textContent = k.entrega ?? 0;
  }

  // ✅ DEFINICIÓN CORRECTA DE COLUMNAS (soluciona cols is not defined)
  const cols = [
    {
      data: null,
      orderable: false,
      render: function(row){
        const id = row.ID_Ruta ?? '';
        return `
          <div class="d-flex gap-1">
            <button class="btn btn-outline-primary btn-xs" title="Editar" onclick="editRow(${id})">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button class="btn btn-outline-danger btn-xs" title="Borrar" onclick="delRow(${id})">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        `;
      }
    },
    { data:'ID_Ruta' },
    { data:'cve_ruta' },
    { data:'descripcion' },
    { data:'status' },
    { data:'cve_almacenp' },
    { data:'venta_preventa' },
    { data:'es_entrega' },
    { data:'control_pallets_cont' },
    { data:'consig_pallets' },
    { data:'consig_cont' },
    { data:'ID_Proveedor' },
    { data:'Activo' }
  ];

  async function load(){
    const q = document.getElementById('txtBuscar').value.trim();
    const show_inactivos = document.getElementById('swInactivos').checked ? 1 : 0;

    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('action','list');
    url.searchParams.set('page','1');
    url.searchParams.set('pageSize','500');
    if(q) url.searchParams.set('q', q);
    url.searchParams.set('show_inactivos', String(show_inactivos));

    const r = await fetch(url.toString(), {cache:'no-store'});
    const j = await r.json();
    if(!j.success){ toast(j.error || 'No se pudo cargar'); return; }

    setKpis(j.kpis || {});
    const rows = j.data || [];

    // DataTables si existe
    if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
      if (dt) { dt.clear().rows.add(rows).draw(false); return; }

      dt = jQuery('#tbl').DataTable({
        data: rows,
        pageLength: 25,
        lengthMenu: [[10,25,50,100],[10,25,50,100]],
        searching: false,
        ordering: true,
        info: true,
        paging: true,
        scrollX: true,
        // ❌ NO scrollY para que se vean 25 completas
        columns: cols,
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }
      });
      return;
    }

    // fallback sin DataTables
    const tb = document.querySelector('#tbl tbody');
    tb.innerHTML = rows.map(r => {
      const tds = cols.map(c => {
        if (typeof c.render === 'function') return `<td>${c.render(r)}</td>`;
        return `<td>${(r[c.data] ?? '')}</td>`;
      }).join('');
      return `<tr>${tds}</tr>`;
    }).join('');
  }

  function getForm(){
    return {
      ID_Ruta: document.getElementById('ID_Ruta').value ? parseInt(document.getElementById('ID_Ruta').value,10) : null,
      cve_ruta: document.getElementById('cve_ruta').value.trim(),
      descripcion: document.getElementById('descripcion').value.trim(),
      status: document.getElementById('status').value,
      cve_almacenp: parseInt(document.getElementById('cve_almacenp').value||'0',10),
      venta_preventa: parseInt(document.getElementById('venta_preventa').value||'0',10),
      es_entrega: parseInt(document.getElementById('es_entrega').value||'0',10),
      control_pallets_cont: document.getElementById('control_pallets_cont').value,
      consig_pallets: parseInt(document.getElementById('consig_pallets').value||'0',10),
      consig_cont: parseInt(document.getElementById('consig_cont').value||'0',10),
      ID_Proveedor: parseInt(document.getElementById('ID_Proveedor').value||'0',10),
      Activo: parseInt(document.getElementById('Activo').value||'1',10)
    };
  }

  function setForm(r){
    document.getElementById('ID_Ruta').value = r?.ID_Ruta || '';
    document.getElementById('cve_ruta').value = r?.cve_ruta || '';
    document.getElementById('descripcion').value = r?.descripcion || '';
    document.getElementById('status').value = r?.status || 'A';
    document.getElementById('cve_almacenp').value = r?.cve_almacenp ?? 0;
    document.getElementById('venta_preventa').value = String(r?.venta_preventa ?? 1);
    document.getElementById('es_entrega').value = String(r?.es_entrega ?? 0);
    document.getElementById('control_pallets_cont').value = r?.control_pallets_cont || 'N';
    document.getElementById('consig_pallets').value = r?.consig_pallets ?? 0;
    document.getElementById('consig_cont').value = r?.consig_cont ?? 0;
    document.getElementById('ID_Proveedor').value = r?.ID_Proveedor ?? 0;
    document.getElementById('Activo').value = String(r?.Activo ?? 1);
  }

  window.editRow = async function(id){
    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('action','get');
    url.searchParams.set('id', String(id));
    const r = await fetch(url.toString(), {cache:'no-store'});
    const j = await r.json();
    if(!j.success){ toast(j.error || 'No se pudo cargar el registro'); return; }
    setForm(j.data);
    showModal();
  }

  window.delRow = async function(id){
    if(!confirm('¿Borrar esta ruta?')) return;
    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('action','delete');
    url.searchParams.set('id', String(id));
    const r = await fetch(url.toString(), {method:'POST'});
    const j = await r.json();
    if(!j.success){ toast(j.error || 'No se pudo borrar'); return; }
    await load();
  }

  async function save(){
    const row = getForm();
    if(!row.cve_ruta || !row.descripcion){ toast('Captura cve_ruta y descripcion'); return; }
    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('action','save');
    const r = await fetch(url.toString(), {
      method:'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(row)
    });
    const j = await r.json();
    if(!j.success){ toast(j.error || 'No se pudo guardar'); return; }
    hideModal();
    await load();
  }

  function download(filename, content){
    const blob = new Blob([content], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  async function exportCsv(){
    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('action','export');
    const r = await fetch(url.toString(), {cache:'no-store'});
    const txt = await r.text();
    download('rutas_export.csv', txt);
  }

  async function importCsv(file){
    const fd = new FormData();
    fd.append('file', file);
    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('action','import');
    const r = await fetch(url.toString(), {method:'POST', body: fd});
    const j = await r.json();
    if(!j.success){ toast(j.error || 'No se pudo importar'); return; }
    toast(`Importación OK: ${j.ok} OK, ${j.err} ERR`);
    await load();
  }

  function layout(){
    const header = [
      'cve_ruta','descripcion','status','cve_almacenp','venta_preventa','es_entrega','control_pallets_cont','consig_pallets','consig_cont','ID_Proveedor','Activo'
    ].join(',');
    download('rutas_layout.csv', header + "\n");
  }

  document.getElementById('btnNuevo').addEventListener('click', () => { setForm(null); showModal(); });
  document.getElementById('btnRefrescar').addEventListener('click', load);
  document.getElementById('btnGuardar').addEventListener('click', save);
  document.getElementById('btnLimpiar').addEventListener('click', () => { document.getElementById('txtBuscar').value=''; load(); });
  document.getElementById('txtBuscar').addEventListener('keydown', (e) => { if(e.key === 'Enter') load(); });
  document.getElementById('swInactivos').addEventListener('change', load);
  document.getElementById('btnExport').addEventListener('click', exportCsv);
  document.getElementById('btnLayout').addEventListener('click', layout);
  document.getElementById('fileImport').addEventListener('change', (e) => { if(e.target.files?.[0]) importCsv(e.target.files[0]); e.target.value=''; });

  ensureBootstrap(() => load());
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
