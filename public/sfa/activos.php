<?php
require_once __DIR__ . '/../../app/db.php';
include __DIR__ . '/../bi/_menu_global.php';
?>
<style>
  .ap-10 { font-size: 10px; }
  .ap-grid-wrap { overflow:auto; }
  table.dataTable tbody td { vertical-align: middle; }
  .badge-dot { display:inline-flex; align-items:center; gap:.35rem; }
  .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
</style>

<div class="container-fluid ap-10">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h5 class="mb-0">📦 Catálogo | Activos</h5>
      <div class="text-muted">Gobierno maestro de activos (ID autonumérico). Asignación a clientes se manejará en <b>t_activo_ubicacion</b> (vigencias).</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnExport"><i class="fa fa-file-csv"></i> Exportar CSV</button>
      <button class="btn btn-outline-primary btn-sm" id="btnImport"><i class="fa fa-upload"></i> Importar CSV</button>
      <button class="btn btn-primary btn-sm" id="btnNuevo"><i class="fa fa-plus"></i> Nuevo</button>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-md-2">
      <div class="card"><div class="card-body py-2">
        <div class="text-muted">Total</div>
        <div class="fs-6 fw-bold" id="kpiTotal">0</div>
      </div></div>
    </div>
    <div class="col-md-2">
      <div class="card"><div class="card-body py-2">
        <div class="text-muted">Activos</div>
        <div class="fs-6 fw-bold" id="kpiActivos">0</div>
      </div></div>
    </div>
    <div class="col-md-8">
      <div class="card"><div class="card-body py-2">
        <div class="d-flex gap-2 align-items-center">
          <div class="flex-grow-1">
            <div class="text-muted">Búsqueda (clave / serie / marca / modelo / descripción)</div>
            <input class="form-control form-control-sm" id="q" placeholder="Buscar...">
          </div>
          <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" role="switch" id="soloActivos" checked>
            <label class="form-check-label" for="soloActivos">Solo activos</label>
          </div>
          <button class="btn btn-outline-secondary btn-sm mt-4" id="btnBuscar"><i class="fa fa-search"></i></button>
        </div>
      </div></div>
    </div>
  </div>

  <div class="ap-grid-wrap">
    <table id="tbl" class="table table-bordered table-sm w-100">
      <thead class="table-light">
        <tr>
          <th style="width:90px">Acciones</th>
          <th style="width:60px">ID</th>
          <th style="width:120px">Clave</th>
          <th style="width:230px">Almacén</th>
          <th style="width:120px">Tipo</th>
          <th style="width:120px">Serie</th>
          <th style="width:140px">Marca</th>
          <th style="width:140px">Modelo</th>
          <th>Descripción</th>
          <th style="width:110px">Estatus</th>
          <th style="width:110px">Lat</th>
          <th style="width:110px">Lng</th>
          <th style="width:60px">Activo</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">✏️ Activo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="id_activo">

        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label">Compañía *</label>
            <select class="form-select form-select-sm" id="id_compania"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Almacén *</label>
            <select class="form-select form-select-sm" id="id_almacen"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select class="form-select form-select-sm" id="tipo_activo">
              <option value="REFRIGERADOR">REFRIGERADOR</option>
              <option value="MOBILIARIO">MOBILIARIO</option>
              <option value="EQUIPO">EQUIPO</option>
              <option value="OTRO">OTRO</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Clave *</label>
            <input class="form-control form-control-sm" id="clave" placeholder="ACT-000001 / REF-0001 / etc">
          </div>

          <div class="col-md-3">
            <label class="form-label">Serie (num_serie) *</label>
            <input class="form-control form-control-sm" id="num_serie">
          </div>
          <div class="col-md-3">
            <label class="form-label">Marca</label>
            <input class="form-control form-control-sm" id="marca">
          </div>
          <div class="col-md-3">
            <label class="form-label">Modelo</label>
            <input class="form-control form-control-sm" id="modelo">
          </div>
          <div class="col-md-3">
            <label class="form-label">Descripción</label>
            <input class="form-control form-control-sm" id="descripcion">
          </div>

          <div class="col-md-3">
            <label class="form-label">Fecha compra</label>
            <input class="form-control form-control-sm" id="fecha_compra" placeholder="dd/mm/aaaa">
          </div>
          <div class="col-md-3">
            <label class="form-label">Proveedor</label>
            <select class="form-select form-select-sm" id="proveedor"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Factura</label>
            <input class="form-control form-control-sm" id="factura">
          </div>
          <div class="col-md-3">
            <label class="form-label">Ventas objetivo mensual</label>
            <input class="form-control form-control-sm" id="ventas_objetivo_mensual">
          </div>

          <div class="col-md-3">
            <label class="form-label">Estatus</label>
            <select class="form-select form-select-sm" id="estatus">
              <option value="ACTIVO">ACTIVO</option>
              <option value="EN_MANTTO">EN_MANTTO</option>
              <option value="BAJA">BAJA</option>
              <option value="TRASLADO">TRASLADO</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Latitud</label>
            <input class="form-control form-control-sm" id="latitud">
          </div>
          <div class="col-md-3">
            <label class="form-label">Longitud</label>
            <input class="form-control form-control-sm" id="longitud">
          </div>
          <div class="col-md-3">
            <label class="form-label">Activo</label>
            <select class="form-select form-select-sm" id="activo">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>

          <div class="col-md-12">
            <label class="form-label">Notas condición</label>
            <input class="form-control form-control-sm" id="notas_condicion">
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" id="btnGuardar"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Fallback Bootstrap JS (si _menu_global no lo incluyó) -->
<script>
(function ensureBootstrap(){
  if (typeof window.bootstrap !== 'undefined') return;
  var s = document.createElement('script');
  s.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js";
  s.defer = true;
  document.head.appendChild(s);
})();
</script>

<script>
const API = "../api/activos_api.php";
let META = {companias:[], almacenes:[], proveedores:[]};
let mdl;

function money0(v){ return (v===null||v===undefined||v==='') ? '' : v; }

function setKpis(total, rows){
  document.getElementById('kpiTotal').textContent = total;
  const activos = rows.filter(r => String(r.activo)==='1').length;
  document.getElementById('kpiActivos').textContent = activos;
}

function badgeActivo(v){
  return String(v)==='1' ? '<span class="badge bg-success">SI</span>' : '<span class="badge bg-secondary">NO</span>';
}

async function apiGet(url){
  const r = await fetch(url, {credentials:'same-origin'});
  return await r.json();
}
async function apiPost(form){
  const r = await fetch(API, {method:'POST', body: form, credentials:'same-origin'});
  return await r.json();
}

async function loadMeta(){
  const j = await apiGet(API + "?action=meta");
  if (!j.ok){ alert(j.error || 'No se pudo cargar meta'); return; }
  META = j;

  // compañias
  const c = document.getElementById('id_compania');
  c.innerHTML = (META.companias||[]).map(x => `<option value="${x.id}">${x.nombre}</option>`).join('');

  // almacenes
  const a = document.getElementById('id_almacen');
  a.innerHTML = (META.almacenes||[]).map(x => `<option value="${x.id}">${x.clave} - ${x.nombre}</option>`).join('');

  // proveedores
  const p = document.getElementById('proveedor');
  p.innerHTML = `<option value="">(sin proveedor)</option>` + (META.proveedores||[]).map(x => {
    const tag = x.clave ? `${x.clave} - ${x.nombre}` : x.nombre;
    return `<option value="${x.id}">${tag}</option>`;
  }).join('');
}

async function load(){
  const q = document.getElementById('q').value.trim();
  const solo = document.getElementById('soloActivos').checked ? 1 : 0;

  const j = await apiGet(API + `?action=list&solo_activos=${solo}&q=${encodeURIComponent(q)}&pageSize=200&page=1`);
  if (!j.ok){ alert(j.error || 'No se pudo cargar'); return; }

  setKpis(j.total || 0, j.rows || []);
  const tb = document.querySelector('#tbl tbody');
  tb.innerHTML = '';

  (j.rows||[]).forEach(r => {
    const alm = (r.almacen_clave && r.almacen_nombre) ? `${r.almacen_clave} - ${r.almacen_nombre}` : (r.id_almacen ?? '');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-nowrap">
        <button class="btn btn-outline-primary btn-sm me-1" title="Editar" onclick="editRow(${r.id_activo})"><i class="fa fa-pen"></i></button>
        <button class="btn btn-outline-danger btn-sm" title="Eliminar" onclick="delRow(${r.id_activo})"><i class="fa fa-trash"></i></button>
      </td>
      <td>${r.id_activo}</td>
      <td><b>${r.clave ?? ''}</b></td>
      <td>${alm}</td>
      <td>${r.tipo_activo ?? ''}</td>
      <td><b>${r.num_serie ?? ''}</b></td>
      <td>${r.marca ?? ''}</td>
      <td>${r.modelo ?? ''}</td>
      <td>${r.descripcion ?? ''}</td>
      <td>${r.estatus ?? ''}</td>
      <td>${money0(r.latitud)}</td>
      <td>${money0(r.longitud)}</td>
      <td>${badgeActivo(r.activo)}</td>
    `;
    tb.appendChild(tr);
  });
}

function openModal(){
  if (!mdl) mdl = new bootstrap.Modal(document.getElementById('mdl'));
  mdl.show();
}

function clearModal(){
  document.getElementById('id_activo').value = '';
  document.getElementById('clave').value = '';
  document.getElementById('num_serie').value = '';
  document.getElementById('marca').value = '';
  document.getElementById('modelo').value = '';
  document.getElementById('descripcion').value = '';
  document.getElementById('fecha_compra').value = '';
  document.getElementById('proveedor').value = '';
  document.getElementById('factura').value = '';
  document.getElementById('ventas_objetivo_mensual').value = '';
  document.getElementById('latitud').value = '';
  document.getElementById('longitud').value = '';
  document.getElementById('notas_condicion').value = '';
  document.getElementById('estatus').value = 'ACTIVO';
  document.getElementById('activo').value = '1';
  document.getElementById('tipo_activo').value = 'OTRO';
}

async function editRow(id){
  const j = await apiGet(API + `?action=get&id_activo=${id}`);
  if (!j.ok){ alert(j.error || 'No se pudo cargar'); return; }
  const r = j.row;

  document.getElementById('id_activo').value = r.id_activo;
  document.getElementById('id_compania').value = r.id_compania ?? '';
  document.getElementById('id_almacen').value = r.id_almacen ?? '';
  document.getElementById('tipo_activo').value = r.tipo_activo ?? 'OTRO';
  document.getElementById('clave').value = r.clave ?? '';
  document.getElementById('num_serie').value = r.num_serie ?? '';
  document.getElementById('marca').value = r.marca ?? '';
  document.getElementById('modelo').value = r.modelo ?? '';
  document.getElementById('descripcion').value = r.descripcion ?? '';
  document.getElementById('fecha_compra').value = r.fecha_compra ?? '';
  document.getElementById('proveedor').value = r.proveedor ?? '';
  document.getElementById('factura').value = r.factura ?? '';
  document.getElementById('ventas_objetivo_mensual').value = r.ventas_objetivo_mensual ?? '';
  document.getElementById('latitud').value = r.latitud ?? '';
  document.getElementById('longitud').value = r.longitud ?? '';
  document.getElementById('notas_condicion').value = r.notas_condicion ?? '';
  document.getElementById('estatus').value = r.estatus ?? 'ACTIVO';
  document.getElementById('activo').value = String(r.activo ?? '1');

  openModal();
}

async function delRow(id){
  if (!confirm('¿Eliminar activo ' + id + '?')) return;
  const f = new FormData();
  f.append('action','delete');
  f.append('id_activo', id);
  const j = await apiPost(f);
  if (!j.ok){ alert(j.error || 'No se pudo eliminar'); return; }
  load();
}

document.getElementById('btnNuevo').addEventListener('click', () => {
  clearModal();
  openModal();
});

document.getElementById('btnBuscar').addEventListener('click', load);
document.getElementById('soloActivos').addEventListener('change', load);

document.getElementById('btnGuardar').addEventListener('click', async () => {
  const id = document.getElementById('id_activo').value;

  const f = new FormData();
  f.append('action', id ? 'update' : 'create');
  if (id) f.append('id_activo', id);

  // payload
  ['clave','id_compania','id_almacen','tipo_activo','num_serie','marca','modelo','descripcion',
   'fecha_compra','proveedor','factura','ventas_objetivo_mensual','estatus','latitud','longitud','activo','notas_condicion'
  ].forEach(k => f.append(k, (document.getElementById(k)?.value ?? '').trim()));

  const j = await apiPost(f);
  if (!j.ok){ alert((j.error||'Error') + (j.detalle ? ("\n" + j.detalle) : '')); return; }
  if (mdl) mdl.hide();
  load();
});

(async function init(){
  await loadMeta();
  // Bootstrap modal object se crea hasta que exista bootstrap; si el fallback carga tarde, esto sigue funcionando.
  load();
})();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
