<?php
// public/vas/servicios_vas.php
require_once __DIR__ . '/../../app/db.php';
$cia = db_all("SELECT cve_cia, des_cia FROM c_compania ORDER BY des_cia");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>VAS · Servicios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    body{font-size:12px;}
    .ap-title{font-weight:700;font-size:16px}
    table.dataTable tbody td{font-size:10px; white-space:nowrap;}
    .dt-scroll{overflow:auto;}
    .kpi{border-radius:10px;padding:10px;background:#f6f9ff;border:1px solid #dfe8ff}
    .kpi .v{font-size:18px;font-weight:800}
  </style>
</head>
<body>
<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid mt-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="ap-title">VAS · Administración de Servicios</div>
    <button class="btn btn-primary btn-sm" onclick="openModalNew()"><i class="fa fa-plus"></i> Nuevo</button>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Compañía (Dueño almacén)</label>
          <select id="cve_cia" class="form-select form-select-sm">
            <option value="">-- Seleccionar --</option>
            <?php foreach($cia as $r): ?>
              <option value="<?= htmlspecialchars($r['cve_cia']) ?>"><?= htmlspecialchars($r['des_cia']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Almacén (contexto)</label>
          <select id="almacenp_id" class="form-select form-select-sm" disabled>
            <option value="">--</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Buscar</label>
          <input id="q" class="form-control form-control-sm" placeholder="clave / nombre">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" onclick="loadServicios()">Aplicar</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="resetFiltros()">Limpiar</button>
        </div>
      </div>
      <div class="text-muted mt-2" style="font-size:11px">
        * El almacén es contextual. El catálogo VAS se gestiona por empresa (IdEmpresa = cve_cia).
      </div>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-md-3"><div class="kpi"><div class="t">Servicios activos</div><div class="v" id="kpi_activos">0</div></div></div>
    <div class="col-md-3"><div class="kpi"><div class="t">Servicios total</div><div class="v" id="kpi_total">0</div></div></div>
  </div>

  <div class="card">
    <div class="card-body dt-scroll">
      <table id="tbl" class="table table-striped table-bordered table-sm w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Clave</th>
            <th>Servicio</th>
            <th>Tipo Cobro</th>
            <th>Precio Base</th>
            <th>Moneda</th>
            <th>Activo</th>
            <th style="width:90px">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mSrv" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mTitle">Servicio VAS</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="id_servicio">
        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label">Clave</label>
            <input id="clave_servicio" class="form-control form-control-sm" maxlength="20">
          </div>
          <div class="col-md-5">
            <label class="form-label">Nombre</label>
            <input id="nombre" class="form-control form-control-sm" maxlength="150">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo Cobro</label>
            <select id="tipo_cobro" class="form-select form-select-sm">
              <option value="fijo">Fijo</option>
              <option value="por_pieza">Por pieza</option>
              <option value="por_pedido">Por pedido</option>
              <option value="por_hora">Por hora</option>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Descripción</label>
            <input id="descripcion" class="form-control form-control-sm" maxlength="255">
          </div>
          <div class="col-md-2">
            <label class="form-label">Precio Base</label>
            <input id="precio_base" type="number" step="0.01" class="form-control form-control-sm" value="0">
          </div>
          <div class="col-md-2">
            <label class="form-label">Moneda</label>
            <input id="moneda" class="form-control form-control-sm" value="MXN" maxlength="3">
          </div>
          <div class="col-md-2">
            <label class="form-label">Activo</label>
            <select id="Activo" class="form-select form-select-sm">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>
        <div class="alert alert-warning mt-3 py-2" style="font-size:11px">
          Consejo: define el tipo de cobro pensando en facturación (por pieza / pedido / hora). Esto impacta reglas y reportes.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" onclick="saveServicio()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
const API_SERV = "../api/vas/servicios.php";
const API_ALM  = "../api/catalogos/almacenes.php"; // si ya existe; si no, lo creamos
let dt, modal;

function apiGet(url){
  return fetch(url, {credentials:'same-origin'}).then(r=>r.json());
}
function apiSend(url, method, data){
  return fetch(url, {
    method,
    credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(data||{})
  }).then(r=>r.json());
}

function resetFiltros(){
  document.getElementById('q').value='';
  // no reseteo cia para evitar fricción
  loadServicios();
}

async function loadAlmacenes(){
  const cve_cia = document.getElementById('cve_cia').value;
  const sel = document.getElementById('almacenp_id');
  sel.innerHTML = '<option value="">--</option>';
  if(!cve_cia){ sel.disabled=true; return; }
  sel.disabled=false;

  // intenta consumir API de almacenes existente, si no hay, no rompe UI
  try{
    const js = await apiGet(`${API_ALM}?cve_cia=${encodeURIComponent(cve_cia)}`);
    if(js.ok){
      js.data.forEach(a=>{
        const opt=document.createElement('option');
        opt.value=a.id ?? a.almacenp_id ?? '';
        opt.textContent=(a.clave? (a.clave+' · ') : '') + (a.nombre||a.des||'');
        sel.appendChild(opt);
      });
    }
  }catch(e){
    // silent
  }
}

async function loadServicios(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  if(!IdEmpresa){ alert('Selecciona Compañía'); return; }
  const q = document.getElementById('q').value.trim();
  const url = `${API_SERV}?IdEmpresa=${encodeURIComponent(IdEmpresa)}&Activo=1&search=${encodeURIComponent(q)}`;
  const js = await apiGet(url);
  if(!js.ok){ alert(js.msg||'Error'); return; }

  const rows = js.data || [];
  const total = rows.length;
  document.getElementById('kpi_total').textContent = total;
  document.getElementById('kpi_activos').textContent = total;

  dt.clear();
  rows.forEach(r=>{
    dt.row.add([
      r.id_servicio,
      r.clave_servicio,
      r.nombre,
      r.tipo_cobro,
      Number(r.precio_base).toFixed(2),
      r.moneda,
      r.Activo==1 ? '✔' : '—',
      `<div class="btn-group btn-group-sm">
        <button class="btn btn-outline-primary" onclick='openModalEdit(${JSON.stringify(r)})'>✏️</button>
        <button class="btn btn-outline-danger" onclick='delServicio(${r.id_servicio})'>🗑️</button>
      </div>`
    ]);
  });
  dt.draw(false);
}

function openModalNew(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  if(!IdEmpresa){ alert('Selecciona Compañía'); return; }
  document.getElementById('mTitle').textContent='Nuevo Servicio VAS';
  ['id_servicio','clave_servicio','nombre','descripcion','precio_base','moneda'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('tipo_cobro').value='fijo';
  document.getElementById('precio_base').value=0;
  document.getElementById('moneda').value='MXN';
  document.getElementById('Activo').value='1';
  modal.show();
}

function openModalEdit(r){
  document.getElementById('mTitle').textContent='Editar Servicio VAS';
  document.getElementById('id_servicio').value=r.id_servicio;
  document.getElementById('clave_servicio').value=r.clave_servicio;
  document.getElementById('nombre').value=r.nombre;
  document.getElementById('descripcion').value=r.descripcion||'';
  document.getElementById('tipo_cobro').value=r.tipo_cobro;
  document.getElementById('precio_base').value=r.precio_base;
  document.getElementById('moneda').value=r.moneda||'MXN';
  document.getElementById('Activo').value=r.Activo;
  modal.show();
}

async function saveServicio(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  const id = document.getElementById('id_servicio').value;
  const payload = {
    IdEmpresa,
    clave_servicio: document.getElementById('clave_servicio').value.trim().toUpperCase(),
    nombre: document.getElementById('nombre').value.trim(),
    descripcion: document.getElementById('descripcion').value.trim(),
    tipo_cobro: document.getElementById('tipo_cobro').value,
    precio_base: parseFloat(document.getElementById('precio_base').value||0),
    moneda: document.getElementById('moneda').value.trim().toUpperCase(),
    Activo: parseInt(document.getElementById('Activo').value||1)
  };
  if(!payload.clave_servicio || !payload.nombre){ alert('Clave y nombre son requeridos'); return; }

  let js;
  if(id){
    js = await apiSend(`${API_SERV}?id=${id}`, 'PUT', payload);
  }else{
    js = await apiSend(API_SERV, 'POST', payload);
  }
  if(!js.ok){ alert(js.msg||'Error'); return; }
  modal.hide();
  loadServicios();
}

async function delServicio(id){
  const IdEmpresa = document.getElementById('cve_cia').value;
  if(!confirm('¿Desactivar servicio?')) return;
  const js = await apiSend(`${API_SERV}?id=${id}`, 'DELETE', {IdEmpresa});
  if(!js.ok){ alert(js.msg||'Error'); return; }
  loadServicios();
}

document.addEventListener('DOMContentLoaded', ()=>{
  modal = new bootstrap.Modal(document.getElementById('mSrv'));
  dt = new DataTable('#tbl', {
    pageLength: 25,
    scrollX: true,
    order: [[0,'desc']]
  });
  document.getElementById('cve_cia').addEventListener('change', ()=>{ loadAlmacenes(); loadServicios(); });
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
