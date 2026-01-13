<?php
// /public/sfa/promociones.php
require_once __DIR__ . '/../../bi/_menu_global.php';
?>
<style>
  .ap-wrap{ font-size:10px; }
  .ap-title{ font-weight:700; font-size:14px; }
  .ap-btn{ font-size:10px; padding:6px 10px; }
  .ap-table th, .ap-table td{ font-size:10px; vertical-align:middle; }
  .badge-dot{ display:inline-block; width:10px; height:10px; border-radius:50%; }
</style>

<div class="container-fluid ap-wrap">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="ap-title">Administración de Promociones</div>
  </div>

  <div class="card mb-2">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Seleccione un almacén</label>
          <select id="almacen_id" class="form-select form-select-sm"></select>
        </div>
        <div class="col-md-6 text-end">
          <button class="btn btn-primary ap-btn" onclick="loadPromos()">Buscar</button>
          <button class="btn btn-success ap-btn" onclick="openPromo()">+ Nuevo</button>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-bordered table-hover ap-table mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:90px;">Acciones</th>
              <th style="width:60px;">Status</th>
              <th style="width:70px;">ID</th>
              <th style="width:140px;">Clave</th>
              <th>Descripción</th>
              <th style="width:110px;">% DepCont</th>
              <th style="width:110px;">% DepFiscal</th>
              <th style="width:110px;">Rules</th>
              <th style="width:110px;">Scope</th>
            </tr>
          </thead>
          <tbody id="tb"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Promo -->
<div class="modal fade" id="mdlPromo" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title fw-bold" style="font-size:12px;">Promoción</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="font-size:10px;">
        <input type="hidden" id="p_id">

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Almacén*</label>
            <select id="p_id_almacen" class="form-select form-select-sm"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Clave*</label>
            <input id="p_cve" class="form-control form-control-sm" maxlength="20">
          </div>
          <div class="col-md-4">
            <label class="form-label">Estatus</label>
            <select id="p_activo" class="form-select form-select-sm">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Descripción*</label>
            <input id="p_desc" class="form-control form-control-sm" maxlength="100">
          </div>
          <div class="col-md-2">
            <label class="form-label">% DepCont</label>
            <input id="p_depcont" class="form-control form-control-sm" type="number" step="0.01">
          </div>
          <div class="col-md-2">
            <label class="form-label">% DepFiscal</label>
            <input id="p_depfiscal" class="form-control form-control-sm" type="number" step="0.01">
          </div>
        </div>

        <hr class="my-2">

        <!-- Motor Nuevo -->
        <div class="row g-2">
          <div class="col-md-6">
            <div class="fw-bold mb-1">Reglas / Escalones (Monto / Mix)</div>
            <div class="d-flex gap-1 mb-1">
              <button class="btn btn-outline-primary ap-btn" onclick="addRule()">+ Regla</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered ap-table">
                <thead class="table-light">
                  <tr>
                    <th style="width:60px;">Nivel</th>
                    <th style="width:90px;">Trigger</th>
                    <th style="width:120px;">Monto</th>
                    <th style="width:110px;">Acumula</th>
                    <th style="width:70px;">Acc</th>
                  </tr>
                </thead>
                <tbody id="tb_rules"></tbody>
              </table>
            </div>
          </div>

          <div class="col-md-6">
            <div class="fw-bold mb-1">Alcance (Scope)</div>
            <div class="row g-1 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select id="s_tipo" class="form-select form-select-sm">
                  <option value="CLIENTE">CLIENTE</option>
                  <option value="RUTA">RUTA</option>
                  <option value="VENDEDOR">VENDEDOR</option>
                </select>
              </div>
              <div class="col-md-5">
                <label class="form-label">ID/Clave</label>
                <input id="s_id" class="form-control form-control-sm">
              </div>
              <div class="col-md-3 text-end">
                <button class="btn btn-outline-primary ap-btn" onclick="addScope()">Agregar</button>
              </div>
            </div>

            <div class="table-responsive mt-1">
              <table class="table table-sm table-bordered ap-table">
                <thead class="table-light">
                  <tr>
                    <th style="width:110px;">Tipo</th>
                    <th>ID</th>
                    <th style="width:70px;">Acc</th>
                  </tr>
                </thead>
                <tbody id="tb_scope"></tbody>
              </table>
            </div>
          </div>
        </div>

        <hr class="my-2">

        <div class="fw-bold mb-1">Beneficios (Rewards) de la regla seleccionada</div>
        <div class="row g-1 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select id="rw_tipo" class="form-select form-select-sm">
              <option value="DESC_PCT">DESC_PCT (%)</option>
              <option value="DESC_MONTO">DESC_MONTO ($)</option>
              <option value="BONIF_PRODUCTO">BONIF_PRODUCTO</option>
              <option value="CUPON_PCT_NEXT">CUPON_PCT_NEXT (%)</option>
              <option value="CUPON_MONTO_NEXT">CUPON_MONTO_NEXT ($)</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Valor</label>
            <input id="rw_valor" class="form-control form-control-sm" type="number" step="0.01">
          </div>
          <div class="col-md-2">
            <label class="form-label">Artículo</label>
            <input id="rw_art" class="form-control form-control-sm" placeholder="si aplica">
          </div>
          <div class="col-md-2">
            <label class="form-label">Qty</label>
            <input id="rw_qty" class="form-control form-control-sm" type="number" step="0.0001">
          </div>
          <div class="col-md-3 text-end">
            <button class="btn btn-outline-success ap-btn" onclick="addReward()">Agregar</button>
          </div>
        </div>

        <div class="table-responsive mt-1">
          <table class="table table-sm table-bordered ap-table">
            <thead class="table-light">
              <tr>
                <th style="width:160px;">Tipo</th>
                <th style="width:110px;">Valor</th>
                <th>Artículo</th>
                <th style="width:90px;">Qty</th>
                <th style="width:70px;">Acc</th>
              </tr>
            </thead>
            <tbody id="tb_rewards"></tbody>
          </table>
        </div>

        <div class="alert alert-info py-1 mt-2 mb-0" style="font-size:10px;">
          Tip: usa reglas por monto (MONTO) y asigna rewards híbridos. Luego, desde pedidos/venta podrás “simular” y aplicar.
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary ap-btn" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary ap-btn" onclick="savePromo()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
const API = '../api/sfa/promociones_api.php';
let mdlPromo;
let currentRuleId = null;
let cache = { rules: [], rewards: [], scope: [] };

document.addEventListener('DOMContentLoaded', async () => {
  mdlPromo = new bootstrap.Modal(document.getElementById('mdlPromo'));
  await loadAlmacenes();
});

async function apiGet(params){
  const url = API + '?' + new URLSearchParams(params).toString();
  const r = await fetch(url);
  return await r.json();
}
async function apiPost(params){
  const fd = new FormData();
  Object.keys(params).forEach(k=>fd.append(k, params[k]));
  const r = await fetch(API, { method:'POST', body: fd });
  return await r.json();
}

async function loadAlmacenes(){
  const j = await apiGet({action:'almacenes'});
  if(!j.ok){ alert(j.error); return; }
  const sel = document.getElementById('almacen_id');
  const sel2 = document.getElementById('p_id_almacen');
  sel.innerHTML = '';
  sel2.innerHTML = '';
  j.rows.forEach(a=>{
    const txt = `(${a.id}) ${a.nombre}`;
    sel.add(new Option(txt, a.id));
    sel2.add(new Option(txt, a.id));
  });
  if(sel.value) loadPromos();
}

async function loadPromos(){
  const almacen_id = document.getElementById('almacen_id').value;
  const j = await apiGet({action:'list', almacen_id});
  if(!j.ok){ alert(j.error); return; }
  const tb = document.getElementById('tb');
  tb.innerHTML = '';
  j.rows.forEach(r=>{
    const dot = r.Activo==1 ? 'background:#1bb34a;' : 'background:#b9b9b9;';
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td>
          <button class="btn btn-outline-primary ap-btn" onclick="openPromo(${r.id})">Ver</button>
        </td>
        <td class="text-center"><span class="badge-dot" style="${dot}"></span></td>
        <td class="text-end">${r.id}</td>
        <td>${escapeHtml(r.clave)}</td>
        <td>${escapeHtml(r.descripcion)}</td>
        <td class="text-end">${Number(r.por_depcont||0).toFixed(2)}</td>
        <td class="text-end">${Number(r.por_depfical||0).toFixed(2)}</td>
        <td class="text-end">${r.total_rules||0}</td>
        <td class="text-end">${r.total_scope||0}</td>
      </tr>
    `);
  });
}

function openPromo(id=null){
  document.getElementById('p_id').value = '';
  document.getElementById('p_cve').value = '';
  document.getElementById('p_desc').value = '';
  document.getElementById('p_depcont').value = '0';
  document.getElementById('p_depfiscal').value = '0';
  document.getElementById('p_activo').value = '1';
  document.getElementById('p_id_almacen').value = document.getElementById('almacen_id').value;

  cache = { rules: [], rewards: [], scope: [] };
  currentRuleId = null;
  renderRules(); renderScope(); renderRewards();

  if(id){
    loadPromo(id);
  } else {
    mdlPromo.show();
  }
}

async function loadPromo(id){
  const j = await apiGet({action:'get', id});
  if(!j.ok){ alert(j.error); return; }

  const p = j.promo;
  document.getElementById('p_id').value = p.id;
  document.getElementById('p_id_almacen').value = p.id_almacen || document.getElementById('almacen_id').value;
  document.getElementById('p_cve').value = p.cve_gpoart || '';
  document.getElementById('p_desc').value = p.des_gpoart || '';
  document.getElementById('p_depcont').value = p.por_depcont || 0;
  document.getElementById('p_depfiscal').value = p.por_depfical || 0;
  document.getElementById('p_activo').value = (p.Activo==1 ? '1':'0');

  cache.rules = j.rules || [];
  cache.scope = j.scope || [];
  cache.rewards = j.rewards || [];

  currentRuleId = cache.rules.length ? cache.rules[0].id_rule : null;

  renderRules(); renderScope(); renderRewards();
  mdlPromo.show();
}

async function savePromo(){
  const id = document.getElementById('p_id').value;
  const params = {
    action:'save',
    id,
    id_almacen: document.getElementById('p_id_almacen').value,
    cve_gpoart: document.getElementById('p_cve').value.trim(),
    des_gpoart: document.getElementById('p_desc').value.trim(),
    por_depcont: document.getElementById('p_depcont').value,
    por_depfical: document.getElementById('p_depfiscal').value,
    Activo: document.getElementById('p_activo').value
  };
  const j = await apiPost(params);
  if(!j.ok){ alert(j.error + (j.detalle?("\n"+j.detalle):"")); return; }

  // set id if new
  if(!id){
    // No es crítico; solo refrescamos listado y recargamos
  }
  await loadPromos();
  alert('Guardado OK');
  mdlPromo.hide();
}

function addRule(){
  if(!document.getElementById('p_id').value){
    alert('Guarda primero el encabezado (Promoción) y luego agrega reglas.');
    return;
  }

  // UI simple por prompt para no meter otro modal
  const nivel = prompt('Nivel (1..n):', '1');
  if(!nivel) return;

  const monto = prompt('Threshold MONTO (ej. 25000):', '0');
  if(monto===null) return;

  saveRule({
    nivel: Number(nivel),
    trigger_tipo: 'MONTO',
    threshold_monto: Number(monto),
    acumula: 'S',
    acumula_por: 'PERIODO'
  });
}

async function saveRule(rule){
  const promo_id = document.getElementById('p_id').value;
  const j = await apiPost({
    action:'rule_save',
    promo_id,
    id_rule: rule.id_rule || '',
    nivel: rule.nivel || 1,
    trigger_tipo: rule.trigger_tipo || 'MONTO',
    threshold_monto: rule.threshold_monto ?? '',
    threshold_qty: rule.threshold_qty ?? '',
    acumula: rule.acumula || 'S',
    acumula_por: rule.acumula_por || 'PERIODO',
    min_items_distintos: rule.min_items_distintos ?? '',
    observaciones: rule.observaciones || ''
  });
  if(!j.ok){ alert(j.error + (j.detalle?("\n"+j.detalle):"")); return; }
  await loadPromo(promo_id);
}

async function delRule(id_rule){
  if(!confirm('¿Desactivar regla y sus rewards?')) return;
  const promo_id = document.getElementById('p_id').value;
  const j = await apiPost({action:'rule_del', id_rule});
  if(!j.ok){ alert(j.error); return; }
  await loadPromo(promo_id);
}

function selectRule(id_rule){
  currentRuleId = id_rule;
  renderRules();
  renderRewards();
}

function renderRules(){
  const tb = document.getElementById('tb_rules');
  tb.innerHTML = '';
  cache.rules.forEach(r=>{
    const sel = (currentRuleId == r.id_rule) ? 'table-primary' : '';
    tb.insertAdjacentHTML('beforeend', `
      <tr class="${sel}" onclick="selectRule(${r.id_rule})" style="cursor:pointer;">
        <td class="text-end">${r.nivel}</td>
        <td>${r.trigger_tipo}</td>
        <td class="text-end">${Number(r.threshold_monto||0).toFixed(2)}</td>
        <td>${r.acumula}/${r.acumula_por}</td>
        <td class="text-center">
          <button class="btn btn-outline-danger ap-btn" onclick="event.stopPropagation();delRule(${r.id_rule});">X</button>
        </td>
      </tr>
    `);
  });
}

async function addScope(){
  const promo_id = document.getElementById('p_id').value;
  if(!promo_id){ alert('Guarda primero el encabezado.'); return; }

  const tipo = document.getElementById('s_tipo').value;
  const sid  = document.getElementById('s_id').value.trim();
  if(!sid){ alert('Captura ID/Clave'); return; }

  const j = await apiPost({action:'scope_save', promo_id, scope_tipo: tipo, scope_id: sid, exclusion:'N'});
  if(!j.ok){ alert(j.error); return; }
  await loadPromo(promo_id);
  document.getElementById('s_id').value = '';
}

async function delScope(id_scope){
  if(!confirm('¿Quitar scope?')) return;
  const promo_id = document.getElementById('p_id').value;
  const j = await apiPost({action:'scope_del', id_scope});
  if(!j.ok){ alert(j.error); return; }
  await loadPromo(promo_id);
}

function renderScope(){
  const tb = document.getElementById('tb_scope');
  tb.innerHTML = '';
  cache.scope.forEach(s=>{
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${s.scope_tipo}</td>
        <td>${escapeHtml(s.scope_id)}</td>
        <td class="text-center">
          <button class="btn btn-outline-danger ap-btn" onclick="delScope(${s.id_scope})">X</button>
        </td>
      </tr>
    `);
  });
}

async function addReward(){
  const promo_id = document.getElementById('p_id').value;
  if(!promo_id){ alert('Guarda primero el encabezado.'); return; }
  if(!currentRuleId){ alert('Crea/selecciona una regla primero.'); return; }

  const tipo = document.getElementById('rw_tipo').value;
  const valor = document.getElementById('rw_valor').value;
  const art = document.getElementById('rw_art').value.trim();
  const qty = document.getElementById('rw_qty').value;

  const j = await apiPost({
    action:'reward_save',
    id_rule: currentRuleId,
    reward_tipo: tipo,
    valor: valor,
    cve_articulo: art,
    qty: qty,
    unimed: '',
    aplica_sobre: 'TOTAL',
    observaciones: ''
  });
  if(!j.ok){ alert(j.error); return; }
  await loadPromo(promo_id);

  document.getElementById('rw_valor').value = '';
  document.getElementById('rw_art').value = '';
  document.getElementById('rw_qty').value = '';
}

async function delReward(id_reward){
  if(!confirm('¿Quitar reward?')) return;
  const promo_id = document.getElementById('p_id').value;
  const j = await apiPost({action:'reward_del', id_reward});
  if(!j.ok){ alert(j.error); return; }
  await loadPromo(promo_id);
}

function renderRewards(){
  const tb = document.getElementById('tb_rewards');
  tb.innerHTML = '';
  const rows = cache.rewards.filter(x => String(x.id_rule) === String(currentRuleId));
  rows.forEach(r=>{
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${r.reward_tipo}</td>
        <td class="text-end">${r.valor !== null ? Number(r.valor).toFixed(2) : ''}</td>
        <td>${escapeHtml(r.cve_articulo || '')}</td>
        <td class="text-end">${r.qty !== null ? Number(r.qty).toFixed(4) : ''}</td>
        <td class="text-center">
          <button class="btn btn-outline-danger ap-btn" onclick="delReward(${r.id_reward})">X</button>
        </td>
      </tr>
    `);
  });
}

function escapeHtml(s){
  return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
</script>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
?>
