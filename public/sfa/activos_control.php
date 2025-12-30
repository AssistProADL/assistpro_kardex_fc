<?php
require_once __DIR__ . '/../../app/db.php';
include __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h4 class="mb-0">Control y Asignación de Activos</h4>
      <div class="text-muted" style="font-size:12px;">
        Vista única (Disponibles + Asignados) | Asignación a Destinatario | Regla: 1 vigencia vigente por activo
      </div>
    </div>
    <button class="btn btn-outline-secondary btn-sm" id="btnRefresh">
      <i class="fa fa-rotate"></i> Refrescar
    </button>
  </div>

  <!-- KPIs -->
  <div class="row g-2 mb-2">
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-3">
          <div class="text-muted" style="font-size:12px;">Total Activos</div>
          <div class="fs-4 fw-bold" id="k_total">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-3">
          <div class="text-muted" style="font-size:12px;">Disponibles</div>
          <div class="fs-4 fw-bold" id="k_disp">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-3">
          <div class="text-muted" style="font-size:12px;">Asignados</div>
          <div class="fs-4 fw-bold" id="k_asig">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-3">
          <div class="text-muted" style="font-size:12px;">En Alerta</div>
          <div class="fs-4 fw-bold" id="k_alert">0</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card shadow-sm mb-2">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-1" style="font-size:12px;">Buscar</label>
          <input type="text" class="form-control form-control-sm" id="f_q" placeholder="Serie / modelo / marca / descripción">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1" style="font-size:12px;">Estatus</label>
          <select class="form-select form-select-sm" id="f_estatus">
            <option value="">Todos</option>
            <option value="DISPONIBLE">Disponibles</option>
            <option value="ASIGNADO">Asignados</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1" style="font-size:12px;">Destinatario</label>
          <select class="form-select form-select-sm" id="f_dest">
            <option value="">— Todos —</option>
          </select>
        </div>

        <div class="col-md-1 d-grid">
          <button class="btn btn-primary btn-sm" id="btnBuscar">
            <i class="fa fa-search"></i> Buscar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Grid -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:70vh; overflow:auto;">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
          <thead class="table-light" style="position:sticky; top:0; z-index:1;">
            <tr>
              <th style="width:90px;">Acción</th>
              <th style="width:70px;">ID</th>
              <th style="width:120px;">Serie</th>
              <th style="width:120px;">Tipo</th>
              <th style="width:120px;">Marca</th>
              <th style="width:120px;">Modelo</th>
              <th>Descripción</th>
              <th style="width:120px;">Estatus</th>
              <th style="width:220px;">Almacén</th>
              <th style="width:240px;">Destinatario</th>
              <th style="width:140px;">Desde</th>
              <th style="width:110px;">Vigencia</th>
            </tr>
          </thead>
          <tbody id="tbRows"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Modal Asignar -->
<div class="modal fade" id="mdlAsignar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="fa fa-link"></i> Asignar Activo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted" style="font-size:12px;" id="mdlSub"></div>

        <div class="row g-2 mt-2">
          <div class="col-md-12">
            <label class="form-label mb-1" style="font-size:12px;">Destinatario <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="m_dest"></select>
            <div class="text-muted mt-1" style="font-size:11px;">Se usa el catálogo <b>c_destinatarios</b>.</div>
          </div>
          <div class="col-md-12">
            <label class="form-label mb-1" style="font-size:12px;">Observaciones</label>
            <textarea class="form-control form-control-sm" id="m_obs" rows="3" placeholder="Motivo / referencia operativa"></textarea>
          </div>
        </div>

        <div class="alert alert-info mt-3 mb-0" style="font-size:12px;">
          <b>Regla:</b> vigencia única. Si el activo ya está asignado, se cerrará su asignación vigente y se creará una nueva.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary btn-sm" id="btnConfirmar">
          <i class="fa fa-check"></i> Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const API = '/assistpro_kardex_fc/public/api/activos_control_api.php';

let CURRENT = { id_activo: 0, serie:'', desc:'' };
let DESTS = [];

function fmtDate(dt){
  if(!dt) return '';
  // dt: "2025-12-30 09:55:00"
  const d = new Date(dt.replace(' ','T'));
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yy = d.getFullYear();
  return `${dd}/${mm}/${yy}`;
}

async function apiGet(params){
  const url = API + '?' + new URLSearchParams(params).toString();
  const r = await fetch(url, {credentials:'same-origin'});
  return await r.json();
}
async function apiPost(form){
  const fd = new FormData();
  Object.keys(form).forEach(k=>fd.append(k, form[k]));
  const r = await fetch(API, {method:'POST', body:fd, credentials:'same-origin'});
  return await r.json();
}

function renderRows(rows){
  const tb = document.getElementById('tbRows');
  tb.innerHTML = '';

  rows.forEach(x=>{
    const isAsign = (x.estatus_asignacion === 'ASIGNADO');
    const vigBadge = isAsign
      ? `<span class="badge bg-success">Vigente</span>`
      : `<span class="badge bg-secondary">Disponible</span>`;

    const dest = isAsign
      ? `<div><b>${(x.destinatario_clave||'')}</b> | ${x.destinatario_nombre||''}</div>`
      : `<span class="text-muted">-</span>`;

    const desde = isAsign ? fmtDate(x.fecha_inicio) : `<span class="text-muted">-</span>`;

    const alm = `${x.almacen_clave||''} | ${x.almacen_nombre||''}`;

    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td>
          <button class="btn btn-sm btn-primary" ${isAsign ? '' : ''} onclick="openAsignar(${x.id_activo}, '${(x.clave_activo||'').replace(/'/g,"\\'")}', '${(x.descripcion||'').replace(/'/g,"\\'")}')">
            Asignar
          </button>
          ${isAsign ? `
            <button class="btn btn-sm btn-outline-danger ms-1" onclick="desasignar(${x.id_activo})" title="Cerrar vigencia">
              <i class="fa fa-unlink"></i>
            </button>` : ``}
        </td>
        <td>${x.id_activo}</td>
        <td><b>${x.clave_activo||''}</b></td>
        <td>${x.tipo_activo||''}</td>
        <td>${x.marca||''}</td>
        <td>${x.modelo||''}</td>
        <td>${x.descripcion||''}</td>
        <td>${x.estado_activo||''}</td>
        <td>${alm}</td>
        <td>${dest}</td>
        <td>${desde}</td>
        <td>${vigBadge}</td>
      </tr>
    `);
  });
}

async function loadKpis(){
  const j = await apiGet({action:'kpis'});
  if(j.ok){
    document.getElementById('k_total').textContent = j.total ?? 0;
    document.getElementById('k_disp').textContent  = j.disponibles ?? 0;
    document.getElementById('k_asig').textContent  = j.asignados ?? 0;
    document.getElementById('k_alert').textContent = j.alertas ?? 0;
  }
}

async function loadDestinatarios(){
  const j = await apiGet({action:'destinatarios'});
  if(!j.ok){
    alert('Error cargando destinatarios: ' + (j.detalle||j.error||''));
    return;
  }
  DESTS = j.rows || [];
  const f = document.getElementById('f_dest');
  const m = document.getElementById('m_dest');

  // filtro
  f.innerHTML = `<option value="">— Todos —</option>`;
  DESTS.forEach(d=>{
    f.insertAdjacentHTML('beforeend', `<option value="${d.id_destinatario}">${d.clave} | ${d.razonsocial}</option>`);
  });

  // modal
  m.innerHTML = '';
  DESTS.forEach(d=>{
    m.insertAdjacentHTML('beforeend', `<option value="${d.id_destinatario}">${d.clave} | ${d.razonsocial}</option>`);
  });
}

async function loadList(){
  const q = document.getElementById('f_q').value.trim();
  const est = document.getElementById('f_estatus').value;
  const dest = document.getElementById('f_dest').value;

  const j = await apiGet({
    action:'list',
    q:q,
    estatus:est,
    id_destinatario:dest
  });

  if(!j.ok){
    alert('Error listando: ' + (j.detalle||j.error||''));
    return;
  }
  renderRows(j.rows||[]);
}

window.openAsignar = function(id, serie, desc){
  CURRENT = {id_activo:id, serie, desc};
  document.getElementById('mdlSub').textContent = `Activo #${id} | ${serie} | ${desc}`;
  document.getElementById('m_obs').value = '';
  const mdl = new bootstrap.Modal(document.getElementById('mdlAsignar'));
  mdl.show();
}

document.getElementById('btnConfirmar').addEventListener('click', async ()=>{
  const id_dest = document.getElementById('m_dest').value;
  const obs = document.getElementById('m_obs').value.trim();

  const j = await apiPost({
    action:'asignar',
    id_activo: CURRENT.id_activo,
    id_destinatario: id_dest,
    observaciones: obs
  });

  if(!j.ok){
    alert('Error asignando: ' + (j.detalle||j.error||''));
    return;
  }

  // cerrar modal
  const modalEl = document.getElementById('mdlAsignar');
  bootstrap.Modal.getInstance(modalEl).hide();

  await loadKpis();
  await loadList();
});

window.desasignar = async function(id_activo){
  if(!confirm('¿Cerrar la asignación vigente de este activo?')) return;

  const j = await apiPost({action:'desasignar', id_activo});
  if(!j.ok){
    alert('Error desasignando: ' + (j.detalle||j.error||''));
    return;
  }
  await loadKpis();
  await loadList();
}

document.getElementById('btnBuscar').addEventListener('click', loadList);
document.getElementById('btnRefresh').addEventListener('click', async ()=>{
  await loadKpis();
  await loadList();
});

// init
(async function(){
  await loadDestinatarios();
  await loadKpis();
  await loadList();
})();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
