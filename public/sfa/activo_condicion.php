<?php
// public/sfa/activo_condicion.php
include __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
    <div>
      <h4 class="mb-0">Catálogo | Condición del Activo</h4>
      <div class="text-muted" style="font-size:12px;">
        Clasifica estado físico (Nuevo, Bueno, Dañado). | Softdelete | CSV
      </div>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnExport">
        <i class="fa fa-file-csv me-1"></i> Exportar CSV
      </button>
      <button class="btn btn-outline-primary btn-sm" id="btnImport">
        <i class="fa fa-upload me-1"></i> Importar CSV
      </button>
      <button class="btn btn-primary btn-sm" id="btnNuevo">
        <i class="fa fa-plus me-1"></i> Nuevo
      </button>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
    <span class="badge bg-primary" id="kpiTotal">Total: 0</span>
    <span class="badge bg-success" id="kpiActivos">Activos: 0</span>

    <div class="ms-auto" style="min-width:320px;">
      <input type="text" class="form-control form-control-sm" id="q" placeholder="Buscar clave / nombre...">
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-2">
      <div class="text-muted mb-2" style="font-size:12px;">
        Recomendación: usa claves en MAYÚSCULAS sin espacios (ej. NUEVO, BUENO, DANADO).
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0" id="tbl" style="width:100%;">
          <thead class="table-light">
            <tr>
              <th style="width:120px;">Acciones</th>
              <th style="width:90px;">ID</th>
              <th style="width:180px;">Clave</th>
              <th>Nombre</th>
              <th style="width:100px;">Activo</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<!-- Modal: Nuevo/Editar -->
<div class="modal fade" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlTitle">Condición</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="id_condicion">

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label mb-1" style="font-size:12px;">Clave</label>
            <input type="text" class="form-control form-control-sm" id="clave" placeholder="NUEVO">
          </div>
          <div class="col-md-8">
            <label class="form-label mb-1" style="font-size:12px;">Nombre</label>
            <input type="text" class="form-control form-control-sm" id="nombre" placeholder="Nuevo">
          </div>
          <div class="col-12 pt-1">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="activo" checked>
              <label class="form-check-label" for="activo">Activo</label>
            </div>
          </div>
        </div>

        <div class="alert alert-light border mt-2 mb-0" style="font-size:12px;">
          Tip: La clave se normaliza a MAYÚSCULAS y sin espacios automáticamente.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary btn-sm" id="btnGuardar"><i class="fa fa-save me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Import CSV -->
<div class="modal fade" id="mdlImp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Importar CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 mb-2">
          <button class="btn btn-outline-secondary btn-sm" id="btnLayout">
            <i class="fa fa-download me-1"></i> Descargar layout
          </button>
          <div class="text-muted" style="font-size:12px; align-self:center;">
            Columnas: clave,nombre,activo
          </div>
        </div>

        <input type="file" class="form-control form-control-sm" id="impFile" accept=".csv,text/csv">
        <div class="text-muted mt-2" style="font-size:12px;">
          Nota: el import hace UPSERT por el UNIQUE actual (normalmente por <b>nombre</b> en tu tabla).
        </div>

        <div id="impResult" class="mt-2" style="font-size:12px;"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" id="btnDoImport"><i class="fa fa-upload me-1"></i> Importar</button>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const API = "../api/activo_condicion_api.php";

  let dt = null;
  const el = (id) => document.getElementById(id);

  function toast(msg, type='info'){
    // fallback simple (puedes conectar a tu toast corporativo si ya lo tienes)
    if(type==='error') alert(msg);
    else console.log(msg);
  }

  async function jget(url){
    const r = await fetch(url, {credentials:'same-origin'});
    return await r.json();
  }
  async function jpost(url, body){
    const r = await fetch(url, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
      credentials:'same-origin'
    });
    return await r.json();
  }

  async function loadKpis(){
    const j = await jget(API + "?action=kpis");
    if(j.success){
      el('kpiTotal').textContent   = "Total: " + (j.total ?? 0);
      el('kpiActivos').textContent = "Activos: " + (j.activos ?? 0);
    }
  }

  function activoBadge(v){
    return v==1
      ? '<span class="badge bg-success">Sí</span>'
      : '<span class="badge bg-secondary">No</span>';
  }

  async function loadTable(){
    const q = encodeURIComponent(el('q').value.trim());
    const j = await jget(API + "?action=list&q=" + q);

    if(!j.success){
      toast(j.error || "No se pudo cargar", 'error');
      return;
    }

    const rows = j.rows || [];
    const tb = document.querySelector("#tbl tbody");
    tb.innerHTML = rows.map(r => `
      <tr>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" data-edit="${r.id_condicion}">
              <i class="fa fa-pen"></i>
            </button>
            <button class="btn btn-outline-danger" data-del="${r.id_condicion}">
              <i class="fa fa-trash"></i>
            </button>
          </div>
        </td>
        <td>${r.id_condicion}</td>
        <td><span class="badge bg-light text-dark border">${(r.clave||'')}</span></td>
        <td>${(r.nombre||'')}</td>
        <td>${activoBadge(r.activo)}</td>
      </tr>
    `).join('');

    if (window.DataTable) {
      if (dt) dt.destroy();
      dt = new DataTable('#tbl', {
        pageLength: 25,
        searching: false,
        lengthChange: false,
        info: false,
        ordering: true
      });
    }

    await loadKpis();
  }

  function openModal(title){
    el('mdlTitle').textContent = title;
    new bootstrap.Modal(el('mdl')).show();
  }
  function openImport(){
    el('impFile').value = '';
    el('impResult').innerHTML = '';
    new bootstrap.Modal(el('mdlImp')).show();
  }

  function clearForm(){
    el('id_condicion').value = '';
    el('clave').value = '';
    el('nombre').value = '';
    el('activo').checked = true;
  }

  async function edit(id){
    const j = await jget(API + "?action=get&id=" + id);
    if(!j.success){ toast(j.error||"No se pudo cargar", 'error'); return; }

    const d = j.data || {};
    el('id_condicion').value = d.id_condicion || '';
    el('clave').value = d.clave || '';
    el('nombre').value = d.nombre || '';
    el('activo').checked = (d.activo==1);

    openModal("Editar Condición");
  }

  async function save(){
    const payload = {
      id_condicion: el('id_condicion').value ? parseInt(el('id_condicion').value,10) : 0,
      clave: el('clave').value.trim(),
      nombre: el('nombre').value.trim(),
      activo: el('activo').checked ? 1 : 0
    };

    const j = await jpost(API + "?action=save", payload);
    if(!j.success){ toast(j.error||"No se pudo guardar", 'error'); return; }

    bootstrap.Modal.getInstance(el('mdl')).hide();
    await loadTable();
  }

  async function del(id){
    if(!confirm("¿Eliminar (softdelete) este registro?")) return;
    const j = await jget(API + "?action=delete&id=" + id);
    if(!j.success){ toast(j.error||"No se pudo eliminar", 'error'); return; }
    await loadTable();
  }

  async function doExport(){
    window.location = API + "?action=export";
  }
  async function doLayout(){
    window.location = API + "?action=layout";
  }

  async function doImport(){
    const f = el('impFile').files[0];
    if(!f){ toast("Selecciona un CSV", 'error'); return; }

    const fd = new FormData();
    fd.append('file', f);

    const r = await fetch(API + "?action=import", { method:'POST', body: fd, credentials:'same-origin' });
    const j = await r.json();

    if(!j.success){
      el('impResult').innerHTML = `<div class="alert alert-danger py-2 mb-0">${j.error||'Error'}</div>`;
      return;
    }

    const errs = (j.errs||[]).slice(0,10).map(x=>`<div>${x}</div>`).join('');
    el('impResult').innerHTML = `
      <div class="alert alert-success py-2 mb-2">Importación OK: <b>${j.ok||0}</b> | Errores: <b>${j.err||0}</b></div>
      ${errs ? `<div class="alert alert-warning py-2 mb-0"><b>Primeros errores:</b>${errs}</div>` : ``}
    `;
    await loadTable();
  }

  // Events
  el('btnNuevo').addEventListener('click', () => { clearForm(); openModal("Nueva Condición"); });
  el('btnGuardar').addEventListener('click', save);
  el('btnExport').addEventListener('click', doExport);
  el('btnImport').addEventListener('click', openImport);
  el('btnLayout').addEventListener('click', doLayout);
  el('btnDoImport').addEventListener('click', doImport);

  el('q').addEventListener('input', () => {
    clearTimeout(window.__tq);
    window.__tq = setTimeout(loadTable, 250);
  });

  document.querySelector("#tbl").addEventListener('click', (e) => {
    const b = e.target.closest('button');
    if(!b) return;
    if(b.dataset.edit) edit(b.dataset.edit);
    if(b.dataset.del) del(b.dataset.del);
  });

  // Dependencias (mismo enfoque que rutas.php: robustez operativa)
  async function ensureLibs(){
    // Bootstrap (normalmente ya está por _menu_global)
    if(!window.bootstrap){
      const s = document.createElement('script');
      s.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js";
      document.head.appendChild(s);
      await new Promise(res => s.onload = res);
    }

    // DataTables v2 (opcional). Si no, se queda como tabla simple (no rompe).
    if(!window.DataTable){
      const s = document.createElement('script');
      s.src = "https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js";
      document.head.appendChild(s);
      await new Promise(res => s.onload = res);

      const l = document.createElement('link');
      l.rel = "stylesheet";
      l.href = "https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/css/dataTables.dataTables.min.css";
      document.head.appendChild(l);
    }
  }

  (async function init(){
    await ensureLibs();
    await loadKpis();
    await loadTable();
  })();

})();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
