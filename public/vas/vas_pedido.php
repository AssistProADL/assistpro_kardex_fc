<?php
// public/vas/vas_pedido.php
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="padding:14px;">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div style="font-weight:900;font-size:18px;color:#000F9F;">VAS · Pedido</div>
      <div style="font-size:12px;color:#667085;">Busca un pedido por folio y consulta servicios registrados.</div>
    </div>
    <div class="text-end" style="font-size:12px;color:#667085;">
      <span id="src" class="badge" style="background:#000F9F;">API</span>
    </div>
  </div>

  <div class="card shadow-sm mb-2" style="border-radius:14px;">
    <div class="card-body" style="padding:14px;">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label" style="font-size:12px;">Compañía</label>
          <select id="IdEmpresa" class="form-select form-select-sm"></select>
        </div>
        <div class="col-md-5">
          <label class="form-label" style="font-size:12px;">Folio pedido</label>
          <input id="Fol_folio" class="form-control form-control-sm" placeholder="Ej. PED-20260105-211733">
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button id="btnBuscar" class="btn btn-sm btn-primary" style="background:#000F9F;border-color:#000F9F;">Buscar</button>
          <button id="btnLimpiar" class="btn btn-sm btn-outline-secondary">Limpiar</button>
        </div>
        <div class="col-md-2 text-end">
          <button class="btn btn-sm btn-success" disabled>+ Agregar servicio (fase 2)</button>
        </div>
      </div>

      <div class="row g-2 mt-2">
        <div class="col-md-3">
          <div class="card" style="border-radius:14px;">
            <div class="card-body" style="padding:12px;">
              <div style="font-size:12px;color:#667085;">Importe VAS</div>
              <div id="kpiImporte" style="font-weight:900;font-size:20px;">0.00</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card" style="border-radius:14px;">
            <div class="card-body" style="padding:12px;">
              <div style="font-size:12px;color:#667085;">Items</div>
              <div id="kpiItems" style="font-weight:900;font-size:20px;">0</div>
            </div>
          </div>
        </div>
        <div class="col-md-6 d-flex align-items-center">
          <div id="msg" style="font-size:12px;color:#667085;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm" style="border-radius:14px;">
    <div class="card-body" style="padding:14px;">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" id="tb">
          <thead>
            <tr>
              <th>Acciones</th>
              <th>ID</th>
              <th>Servicio</th>
              <th class="text-end">Cantidad</th>
              <th class="text-end">Precio</th>
              <th class="text-end">Total</th>
              <th>Estatus</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const apiBase = '/assistpro_kardex_fc/public/api/vas';
const apiEmp  = '/assistpro_kardex_fc/public/api/catalogos/empresas.php';

const el = (id)=>document.getElementById(id);

function setMsg(t, ok=true){
  el('msg').innerHTML = ok
    ? `<span style="color:#0a7a2f;">${t}</span>`
    : `<span style="color:#b42318;">${t}</span>`;
}
function fmt(n){
  return (Number(n||0)).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}

async function loadEmpresas(){
  const r = await fetch(apiEmp);
  const j = await r.json();
  const s = el('IdEmpresa');
  s.innerHTML = '';
  (j.data || []).forEach(x=>{
    const opt = document.createElement('option');
    opt.value = x.cve_cia ?? x.IdEmpresa ?? x.idEmpresa ?? x.id;
    opt.textContent = `${x.cve_cia} · ${x.des_cia}`;
    s.appendChild(opt);
  });
}

async function buscar(){
  const idEmpresa = el('IdEmpresa').value;
  const folio = el('Fol_folio').value.trim();
  if(!folio){ setMsg('Captura Folio pedido', false); return; }

  // 1) Resolver folio -> id_pedido
  const urlFind = `${apiBase}/find_pedido.php?Fol_folio=${encodeURIComponent(folio)}`;
  let r = await fetch(urlFind);
  let j = await r.json();
  if(!j.ok){ setMsg(j.msg || 'No encontrado', false); return; }

  const id_pedido = j.data?.id_pedido;
  if(!id_pedido){ setMsg('Pedido sin id_pedido', false); return; }

  // 2) Traer servicios por pedido
  const url = `${apiBase}/pedidos_servicios.php?IdEmpresa=${encodeURIComponent(idEmpresa)}&id_pedido=${encodeURIComponent(id_pedido)}`;
  el('src').textContent = 'Fuente: ' + url.replace('/assistpro_kardex_fc/public/','');

  r = await fetch(url);
  j = await r.json();
  if(!j.ok){ setMsg(j.msg || 'Error consultando servicios', false); return; }

  const rows = j.data || [];
  const tbody = el('tb').querySelector('tbody');
  tbody.innerHTML = '';

  let importe = 0;
  rows.forEach(x=>{
    const total = Number(x.total || 0);
    importe += total;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="white-space:nowrap;">
        <button class="btn btn-xs btn-outline-primary" disabled>Editar</button>
        <button class="btn btn-xs btn-outline-danger" disabled>Borrar</button>
      </td>
      <td>${x.id ?? ''}</td>
      <td>${x.servicio ?? ''}</td>
      <td class="text-end">${Number(x.cantidad||0).toLocaleString('es-MX')}</td>
      <td class="text-end">${fmt(x.precio_unitario)}</td>
      <td class="text-end"><strong>${fmt(x.total)}</strong></td>
      <td>${x.estatus ?? ''}</td>
    `;
    tbody.appendChild(tr);
  });

  el('kpiItems').textContent = rows.length;
  el('kpiImporte').textContent = fmt(importe);

  setMsg(`OK: ${rows.length} servicios`);
}

el('btnBuscar').addEventListener('click', buscar);
el('btnLimpiar').addEventListener('click', ()=>{
  el('Fol_folio').value = '';
  el('kpiItems').textContent = '0';
  el('kpiImporte').textContent = '0.00';
  el('tb').querySelector('tbody').innerHTML = '';
  setMsg('');
});

(async function init(){
  await loadEmpresas();
})();
</script>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
