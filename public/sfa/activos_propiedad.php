<?php
include __DIR__ . '/../bi/_menu_global.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-dt@2.1.8/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h5 class="mb-0">Catálogo | Propiedad del Activo</h5>
      <div class="text-muted small">Ej: PROPIO / COMODATO / ARRENDADO | Softdelete | CSV</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="../api/activo_propiedad_api.php?action=export_csv">
        <i class="fa-solid fa-file-export me-1"></i> Exportar CSV
      </a>
      <button class="btn btn-outline-primary btn-sm" id="btnImport">
        <i class="fa-solid fa-file-arrow-up me-1"></i> Importar CSV
      </button>
      <button class="btn btn-primary btn-sm" id="btnNuevo">
        <i class="fa-solid fa-plus me-1"></i> Nuevo
      </button>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-2">
          <div class="small text-muted">Total</div>
          <div class="fs-5 fw-semibold" id="kpiTotal">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-2">
          <div class="small text-muted">Activos</div>
          <div class="fs-5 fw-semibold" id="kpiActivos">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body py-2">
          <div class="small text-muted">Buscar</div>
          <input type="text" id="txtBuscar" class="form-control form-control-sm" placeholder="Buscar clave o nombre...">
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="tbl" class="display nowrap" style="width:100%">
        <thead>
          <tr>
            <th style="width:110px">Acciones</th>
            <th style="width:70px">ID</th>
            <th style="width:140px">Clave</th>
            <th>Nombre</th>
            <th style="width:80px">Activo</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="mdlEdit" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="frmEdit">
      <div class="modal-header">
        <h6 class="modal-title" id="mdlTitle">Propiedad</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_propiedad" id="id_propiedad" value="0">
        <div class="mb-2">
          <label class="form-label small">Clave (A-Z,0-9,_)</label>
          <input type="text" class="form-control form-control-sm" name="clave" id="clave" required>
        </div>
        <div class="mb-2">
          <label class="form-label small">Nombre</label>
          <input type="text" class="form-control form-control-sm" name="nombre" id="nombre" required>
        </div>
        <div class="mb-0">
          <label class="form-label small">Activo</label>
          <select class="form-select form-select-sm" name="activo" id="activo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="mdlImport" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="frmImport" enctype="multipart/form-data">
      <div class="modal-header">
        <h6 class="modal-title">Importar CSV</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="small text-muted mb-2">Columnas esperadas: <b>clave,nombre,activo</b></div>
        <input type="file" name="file" class="form-control form-control-sm" accept=".csv" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-file-arrow-up me-1"></i> Importar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js"></script>

<script>
const API = "../api/activo_propiedad_api.php";

let dt = null;
const mdlEdit = new bootstrap.Modal(document.getElementById('mdlEdit'));
const mdlImport = new bootstrap.Modal(document.getElementById('mdlImport'));

async function apiList(q=''){
  const url = API + "?action=list&q=" + encodeURIComponent(q);
  const r = await fetch(url);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'No se pudo cargar');
  return j.data || [];
}

function paintKpis(rows){
  document.getElementById('kpiTotal').textContent = rows.length;
  const act = rows.filter(x => parseInt(x.activo||0)===1).length;
  document.getElementById('kpiActivos').textContent = act;
}

async function load(){
  try{
    const q = document.getElementById('txtBuscar').value || '';
    const rows = await apiList(q);
    paintKpis(rows);

    if(!dt){
      dt = new DataTable('#tbl', {
        data: rows,
        columns: [
          { data: null, orderable:false, render: (d,t,r) => `
            <div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-sm" onclick="editRow(${r.id_propiedad})" title="Editar">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn btn-outline-danger btn-sm" onclick="delRow(${r.id_propiedad})" title="Eliminar">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>`},
          { data: 'id_propiedad' },
          { data: 'clave' },
          { data: 'nombre' },
          { data: 'activo', render:(d)=> parseInt(d||0)===1
            ? '<span class="badge text-bg-success">Sí</span>'
            : '<span class="badge text-bg-secondary">No</span>' }
        ],
        pageLength: 25,
        scrollX: true
      });
    } else {
      dt.clear(); dt.rows.add(rows); dt.draw();
    }
  }catch(e){
    alert(e.message || 'No se pudo cargar');
    console.error(e);
  }
}

async function editRow(id){
  try{
    const r = await fetch(API + "?action=get&id=" + id);
    const j = await r.json();
    if(!j.ok) throw new Error(j.error || 'No encontrado');
    const x = j.data;

    document.getElementById('id_propiedad').value = x.id_propiedad;
    document.getElementById('clave').value = x.clave || '';
    document.getElementById('nombre').value = x.nombre || '';
    document.getElementById('activo').value = String(parseInt(x.activo||1));
    document.getElementById('mdlTitle').textContent = "Editar Propiedad";
    mdlEdit.show();
  }catch(e){ alert(e.message); }
}

async function delRow(id){
  if(!confirm("¿Eliminar (softdelete) este registro?")) return;
  try{
    const fd = new FormData();
    fd.append('action','delete');
    fd.append('id', id);
    const r = await fetch(API, { method:'POST', body: fd });
    const j = await r.json();
    if(!j.ok) throw new Error(j.error || 'No se pudo eliminar');
    await load();
  }catch(e){ alert(e.message); }
}

document.getElementById('btnNuevo').addEventListener('click', ()=>{
  document.getElementById('id_propiedad').value = 0;
  document.getElementById('clave').value = '';
  document.getElementById('nombre').value = '';
  document.getElementById('activo').value = '1';
  document.getElementById('mdlTitle').textContent = "Nueva Propiedad";
  mdlEdit.show();
});

document.getElementById('frmEdit').addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  try{
    const fd = new FormData(ev.target);
    fd.append('action','save');
    const r = await fetch(API, { method:'POST', body: fd });
    const j = await r.json();
    if(!j.ok) throw new Error(j.error || 'No se pudo guardar');
    mdlEdit.hide();
    await load();
  }catch(e){ alert(e.message); }
});

document.getElementById('btnImport').addEventListener('click', ()=> mdlImport.show());

document.getElementById('frmImport').addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  try{
    const fd = new FormData(ev.target);
    fd.append('action','import_csv');
    const r = await fetch(API, { method:'POST', body: fd });
    const j = await r.json();
    if(!j.ok) throw new Error(j.error || 'Importación fallida');
    mdlImport.hide();
    ev.target.reset();
    await load();
    alert(`Importación OK. Cargados: ${j.total_ok || 0}. Errores: ${j.total_err || 0}`);
  }catch(e){ alert(e.message); }
});

let tmr=null;
document.getElementById('txtBuscar').addEventListener('input', ()=>{
  clearTimeout(tmr);
  tmr=setTimeout(load, 250);
});

load();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
