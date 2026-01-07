<?php
// public/vas/vas_clientes.php
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="padding:14px;">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div style="font-weight:800;font-size:18px;color:#000F9F;">
        VAS · Servicios por Cliente / Dueño de Mercancía
      </div>
      <div style="font-size:12px;color:#666;">
        Configura qué servicios VAS se cobran por Owner (c_proveedores.es_cliente=1) o por Cliente (c_cliente).
      </div>
    </div>
    <div class="text-end" style="font-size:12px;color:#666;">
      <span class="badge" style="background:#000F9F;">AssistPro</span>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card mb-2" style="border:1px solid #e5e7eb; box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div class="card-body" style="padding:10px;">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label" style="font-size:12px;">Empresa (IdEmpresa)</label>
          <input id="IdEmpresa" class="form-control form-control-sm" value="1" />
        </div>
        <div class="col-md-3">
          <label class="form-label" style="font-size:12px;">Almacén (cve_almac)</label>
          <input id="cve_almac" class="form-control form-control-sm" placeholder="opcional" />
        </div>

        <div class="col-md-3">
          <label class="form-label" style="font-size:12px;">Owner (ID_Proveedor es_cliente=1)</label>
          <input id="owner_id" type="number" class="form-control form-control-sm" placeholder="ID_Proveedor" />
        </div>

        <div class="col-md-3">
          <label class="form-label" style="font-size:12px;">Cliente (id_cliente)</label>
          <input id="id_cliente" type="number" class="form-control form-control-sm" placeholder="id_cliente" />
        </div>

        <div class="col-md-12 d-flex gap-2 mt-1">
          <button id="btnLoad" class="btn btn-sm" style="background:#000F9F;color:#fff;">
            <i class="fa fa-rotate"></i> Cargar
          </button>
          <button id="btnSave" class="btn btn-sm" style="background:#95E1BF;color:#191817;border:1px solid #7ad7ad;">
            <i class="fa fa-floppy-disk"></i> Guardar Cambios
          </button>
          <div class="ms-auto" id="msg" style="font-size:12px;color:#666;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card" style="border:1px solid #e5e7eb; box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div class="card-body" style="padding:10px;">
      <div class="table-responsive" style="max-height:70vh; overflow:auto;">
        <table class="table table-sm table-hover align-middle" style="font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8fafc;z-index:2;">
            <tr>
              <th style="width:90px;">Asignado</th>
              <th>Servicio</th>
              <th style="width:160px;">Tipo Cobro</th>
              <th style="width:140px;" class="text-end">Precio</th>
              <th style="width:120px;" class="text-center">Activo</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="5" style="color:#999;">Cargue un Owner o Cliente…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const apiBase = '/assistpro_kardex_fc/public/api/vas';

const el = (id)=>document.getElementById(id);
const msg = (t, ok=true)=>{ el('msg').innerHTML = ok ? `<span style="color:#0a7a2f;">${t}</span>` : `<span style="color:#b42318;">${t}</span>`; };

function getQuery(){
  const IdEmpresa = el('IdEmpresa').value.trim();
  const cve_almac = el('cve_almac').value.trim();
  const owner_id = parseInt(el('owner_id').value||'0',10);
  const id_cliente = parseInt(el('id_cliente').value||'0',10);

  if(!IdEmpresa) throw new Error('Falta IdEmpresa');
  if(owner_id<=0 && id_cliente<=0) throw new Error('Capture owner_id o id_cliente');

  const p = new URLSearchParams();
  p.set('IdEmpresa', IdEmpresa);
  if(cve_almac) p.set('cve_almac', cve_almac);
  if(owner_id>0) p.set('owner_id', owner_id);
  else p.set('id_cliente', id_cliente);
  return { IdEmpresa, cve_almac, owner_id, id_cliente, qs: p.toString() };
}

function render(rows){
  const tb = el('tbody');
  tb.innerHTML = '';
  if(!rows || !rows.length){
    tb.innerHTML = `<tr><td colspan="5" style="color:#999;">Sin servicios…</td></tr>`;
    return;
  }

  for(const r of rows){
    const checked = parseInt(r.habilitado||0,10)===1;
    const activo = (r.Activo===null || r.Activo===undefined) ? 1 : parseInt(r.Activo,10);
    const tipo = (r.tipo_cobro_cliente || r.tipo_cobro_default || 'fijo');
    const precio = (r.precio_cliente!==null && r.precio_cliente!==undefined) ? r.precio_cliente : r.precio_base;

    const tr = document.createElement('tr');
    tr.dataset.id_servicio = r.id_servicio;

    tr.innerHTML = `
      <td>
        <input type="checkbox" class="chk" ${checked?'checked':''}>
      </td>
      <td>
        <div style="font-weight:700;">${escapeHtml(r.nombre||'')}</div>
        <div style="font-size:11px;color:#666;">${escapeHtml(r.clave_servicio||'')}</div>
      </td>
      <td>
        <select class="form-select form-select-sm tipo">
          ${['fijo','por_pieza','por_pedido','por_hora'].map(t=>`<option value="${t}" ${t===tipo?'selected':''}>${t}</option>`).join('')}
        </select>
      </td>
      <td class="text-end">
        <input type="number" step="0.01" class="form-control form-control-sm precio text-end" value="${precio??0}">
      </td>
      <td class="text-center">
        <select class="form-select form-select-sm act" style="max-width:110px;margin:0 auto;">
          <option value="1" ${activo===1?'selected':''}>Activo</option>
          <option value="0" ${activo===0?'selected':''}>Inactivo</option>
        </select>
      </td>
    `;
    tb.appendChild(tr);
  }
}

function escapeHtml(s){
  return (''+s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

async function load(){
  try{
    msg('Cargando…');
    const { qs } = getQuery();
    const res = await fetch(`${apiBase}/clientes_servicios.php?${qs}`);
    const js = await res.json();
    if(!js.ok) throw new Error(js.msg || 'Error');
    render(js.data.servicios || []);
    msg('Servicios cargados');
  }catch(e){
    render([]);
    msg(e.message, false);
  }
}

async function save(){
  try{
    msg('Guardando…');
    const { IdEmpresa, cve_almac, owner_id, id_cliente } = getQuery();

    const rows = Array.from(document.querySelectorAll('#tbody tr'));
    const items = rows.map(tr=>{
      const id_servicio = parseInt(tr.dataset.id_servicio||'0',10);
      const habilitado = tr.querySelector('.chk')?.checked ? 1 : 0;
      const tipo_cobro = tr.querySelector('.tipo')?.value || 'fijo';
      const precio_cliente = parseFloat(tr.querySelector('.precio')?.value || '0');
      const Activo = parseInt(tr.querySelector('.act')?.value || '1',10);
      return { id_servicio, Activo: habilitado ? Activo : 0, tipo_cobro, precio_cliente };
    });

    const payload = { IdEmpresa, cve_almac, items };
    if(owner_id>0) payload.owner_id = owner_id;
    else payload.id_cliente = id_cliente;

    const res = await fetch(`${apiBase}/clientes_servicios.php`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const js = await res.json();
    if(!js.ok) throw new Error(js.msg || 'Error guardando');
    msg('Cambios guardados');
    await load();
  }catch(e){
    msg(e.message, false);
  }
}

el('btnLoad').addEventListener('click', load);
el('btnSave').addEventListener('click', save);
</scr
