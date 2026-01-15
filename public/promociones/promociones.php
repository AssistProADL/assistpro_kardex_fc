<?php
// Promociones (UI Master)
// Ruta esperada: /public/sfa/promociones/promociones.php
// API: /public/api/promociones/promociones_api.php

// Si tu proyecto tiene header/footer comunes, puedes incluirlos aquí.
$__base = realpath(__DIR__);
$__maybeHeader = dirname(__DIR__, 2).'/includes/header.php';
$__maybeFooter = dirname(__DIR__, 2).'/includes/footer.php';
$__hasLayout = file_exists($__maybeHeader) && file_exists($__maybeFooter);
if ($__hasLayout) {
  require_once $__maybeHeader;
}
?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h4 class="mb-0">Administración de Promociones</h4>
      <div class="text-muted small">Motor: Unidad + Monto + Mixta (Rules / Scope / Rewards)</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnDebug">Debug</button>
      <button class="btn btn-primary btn-sm" id="btnBuscar">Buscar</button>
      <button class="btn btn-success btn-sm" id="btnNuevo">+ Nuevo</button>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <label class="form-label">Seleccione un almacén</label>
      <div class="d-flex gap-2">
        <select class="form-select" id="selAlmacen"></select>
      </div>
      <div class="mt-2" id="debugBox" style="display:none;">
        <pre class="small bg-light p-2 border rounded" id="debugText" style="max-height:140px; overflow:auto;"></pre>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" id="tblPromos">
          <thead>
            <tr>
              <th style="width:90px;">Acciones</th>
              <th style="width:70px;">Status</th>
              <th style="width:70px;">ID</th>
              <th style="width:160px;">Clave</th>
              <th>Descripción</th>
              <th style="width:120px;">Inicio</th>
              <th style="width:120px;">Fin</th>
              <th style="width:80px;">Rules</th>
              <th style="width:80px;">Scope</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Promoción -->
<div class="modal fade" id="mdlPromo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlTitle">Promoción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Almacén</label>
            <select class="form-select" id="f_almacen"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Clave</label>
            <input type="text" class="form-control" id="f_clave" placeholder="Clave de la Promoción">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nombre / Descripción</label>
            <input type="text" class="form-control" id="f_desc" placeholder="Nombre de la Promoción">
          </div>

          <div class="col-md-2">
            <label class="form-label">Inicio</label>
            <input type="date" class="form-control" id="f_inicio">
          </div>
          <div class="col-md-2">
            <label class="form-label">Fin</label>
            <input type="date" class="form-control" id="f_fin">
          </div>
          <div class="col-md-2">
            <label class="form-label">Activa</label>
            <select class="form-select" id="f_activo">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <hr class="my-3" />

        <ul class="nav nav-tabs" id="promoTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabRules" data-bs-toggle="tab" data-bs-target="#paneRules" type="button" role="tab">Rules</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabRewards" data-bs-toggle="tab" data-bs-target="#paneRewards" type="button" role="tab">Rewards</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabScope" data-bs-toggle="tab" data-bs-target="#paneScope" type="button" role="tab">Scope</button>
          </li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3" id="promoTabContent">
          <!-- RULES -->
          <div class="tab-pane fade show active" id="paneRules" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold">Reglas / Escalones</div>
              <button class="btn btn-sm btn-outline-primary" id="btnRuleNuevo">+ Regla</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm" id="tblRules">
                <thead>
                  <tr>
                    <th style="width:80px;">Nivel</th>
                    <th style="width:160px;">Trigger</th>
                    <th style="width:140px;">Monto</th>
                    <th style="width:140px;">Qty</th>
                    <th style="width:140px;">Acumula</th>
                    <th style="width:160px;">Por</th>
                    <th>Obs</th>
                    <th style="width:110px;">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- REWARDS -->
          <div class="tab-pane fade" id="paneRewards" role="tabpanel">
            <div class="alert alert-info py-2 mb-2 small">
              Los <b>Rewards</b> se cuelgan de una <b>Regla</b>. Selecciona una regla para ver/editar sus recompensas.
            </div>
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <label class="form-label">Regla</label>
                <select class="form-select" id="selRuleForReward"></select>
              </div>
              <div class="col-md-6 d-flex align-items-end justify-content-end">
                <button class="btn btn-sm btn-outline-primary" id="btnRewardNuevo">+ Reward</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm" id="tblRewards">
                <thead>
                  <tr>
                    <th style="width:150px;">Tipo</th>
                    <th style="width:140px;">Valor</th>
                    <th style="width:140px;">Tope</th>
                    <th style="width:170px;">Artículo</th>
                    <th style="width:120px;">Qty</th>
                    <th style="width:120px;">UM</th>
                    <th style="width:120px;">Aplica</th>
                    <th>Obs</th>
                    <th style="width:110px;">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- SCOPE -->
          <div class="tab-pane fade" id="paneScope" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold">Alcance (a quién aplica)</div>
              <button class="btn btn-sm btn-outline-primary" id="btnScopeNuevo">+ Scope</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm" id="tblScope">
                <thead>
                  <tr>
                    <th style="width:160px;">Tipo</th>
                    <th style="width:220px;">ID</th>
                    <th style="width:120px;">Exclusión</th>
                    <th style="width:110px;">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarPromo">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap (si ya existe en tu layout, puedes quitar esto) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(() => {
  // Resuelve path de API de forma robusta (evita errores 404 por rutas)
  const apiBase = new URL('../../api/promociones/promociones_api.php', window.location.href).href;

  const $ = (q) => document.querySelector(q);
  const $$ = (q) => Array.from(document.querySelectorAll(q));

  const debugBox = $('#debugBox');
  const debugText = $('#debugText');
  const log = (msg) => {
    const ts = new Date().toISOString().substring(11,19);
    debugText.textContent = `[${ts}] ${msg}\n` + debugText.textContent;
  };

  async function apiGet(params) {
    const url = apiBase + '?' + new URLSearchParams(params).toString();
    log('GET ' + url.replace(window.location.origin, ''));
    const r = await fetch(url, {credentials:'same-origin'});
    const t = await r.text();
    let j;
    try { j = JSON.parse(t); } catch(e) { throw new Error('Respuesta no-JSON: ' + t.slice(0,200)); }
    if (!j.ok) throw new Error(j.error + (j.detalle?(' · '+j.detalle):''));
    return j;
  }

  async function apiPost(action, data) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(data||{}).forEach(([k,v]) => fd.append(k, v===null? '': String(v)));
    log('POST ' + action);
    const r = await fetch(apiBase, {method:'POST', body:fd, credentials:'same-origin'});
    const t = await r.text();
    let j;
    try { j = JSON.parse(t); } catch(e) { throw new Error('Respuesta no-JSON: ' + t.slice(0,200)); }
    if (!j.ok) throw new Error(j.error + (j.detalle?(' · '+j.detalle):''));
    return j;
  }

  function fillSelect(sel, rows, {value='id', label='nombre', emptyLabel='Seleccione...'}={}) {
    sel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = emptyLabel;
    sel.appendChild(opt0);
    rows.forEach(r => {
      const opt = document.createElement('option');
      opt.value = r[value];
      opt.textContent = r[label];
      sel.appendChild(opt);
    });
  }

  function badgeActivo(v){
    return v==1 ? '<span class="badge bg-success">●</span>' : '<span class="badge bg-secondary">●</span>';
  }

  let currentPromoId = null;
  let currentRules = [];

  async function loadAlmacenes() {
    const j = await apiGet({action:'almacenes'});
    fillSelect($('#selAlmacen'), j.rows, {value:'id', label:'nombre', emptyLabel:'Seleccione un almacén'});
    fillSelect($('#f_almacen'), j.rows, {value:'id', label:'nombre', emptyLabel:'Seleccione un almacén'});
  }

  async function loadPromos() {
    const almacen_id = $('#selAlmacen').value;
    if (!almacen_id) return;
    const j = await apiGet({action:'list', almacen_id});
    const tb = $('#tblPromos tbody');
    tb.innerHTML = '';
    j.rows.forEach(p => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-outline-primary" data-act="ver" data-id="${p.id}">Ver</button>
            <button class="btn btn-outline-secondary" data-act="rules" data-id="${p.id}" title="Reglas / Escalones">Rules</button>
            <button class="btn btn-outline-secondary" data-act="rewards" data-id="${p.id}" title="Beneficios / Bonificaciones">Rewards</button>
            <button class="btn btn-outline-secondary" data-act="scope" data-id="${p.id}" title="Alcance / Clientes / Rutas / Vendedores">Scope</button>
          </div>
        </td>
        <td>${badgeActivo(p.Activo)}</td>
        <td>${p.id}</td>
        <td>${(p.clave||'')}</td>
        <td>${(p.descripcion||'')}</td>
        <td>${(p.fecha_inicio||'')}</td>
        <td>${(p.fecha_fin||'')}</td>
        <td>${p.total_rules||0}</td>
        <td>${p.total_scope||0}</td>
      `;
      tb.appendChild(tr);
    });
  }

  async function openPromo(id) {
    const mdl = new bootstrap.Modal($('#mdlPromo'));
    currentPromoId = id || null;

    // reset
    $('#mdlTitle').textContent = id ? ('Promoción #' + id) : 'Nueva Promoción';
    $('#f_clave').value='';
    $('#f_desc').value='';
    $('#f_inicio').value='';
    $('#f_fin').value='';
    $('#f_activo').value='1';

    if (id) {
      const j = await apiGet({action:'get', id});
      const p = j.row;
      $('#f_almacen').value = p.id_almacen;
      $('#f_clave').value = p.clave||'';
      $('#f_desc').value = p.descripcion||'';
      $('#f_inicio').value = p.fecha_inicio||'';
      $('#f_fin').value = p.fecha_fin||'';
      $('#f_activo').value = String(p.Activo||0);
    } else {
      $('#f_almacen').value = $('#selAlmacen').value || '';
    }

    await refreshRules();
    await refreshScope();
    await refreshRewardRulesPicklist();
    await refreshRewards();

    mdl.show();
  }

  async function savePromo() {
    const data = {
      id: currentPromoId || '',
      id_almacen: $('#f_almacen').value,
      clave: $('#f_clave').value.trim(),
      descripcion: $('#f_desc').value.trim(),
      Fechal: $('#f_inicio').value,
      FechaF: $('#f_fin').value,
      Activo: $('#f_activo').value,
      // Nota: por_depcont / por_depfical quedan en 0 (ya no se usan en UI)
      por_depcont: 0,
      por_depfical: 0,
    };
    const j = await apiPost('save', data);
    currentPromoId = j.id;
    $('#mdlTitle').textContent = 'Promoción #' + currentPromoId;
    await loadPromos();
  }

  // RULES
  async function refreshRules(){
    if (!currentPromoId) { $('#tblRules tbody').innerHTML=''; return; }
    const j = await apiGet({action:'rule_list', promo_id: currentPromoId});
    currentRules = j.rows || [];
    const tb = $('#tblRules tbody');
    tb.innerHTML='';
    currentRules.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.nivel}</td>
        <td>${r.trigger_tipo}</td>
        <td>${r.threshold_monto ?? ''}</td>
        <td>${r.threshold_qty ?? ''}</td>
        <td>${r.acumula}</td>
        <td>${r.acumula_por}</td>
        <td>${(r.observaciones||'')}</td>
        <td>
          <button class="btn btn-sm btn-outline-secondary" data-act="ruleEdit" data-id="${r.id_rule}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-act="ruleDel" data-id="${r.id_rule}">X</button>
        </td>
      `;
      tb.appendChild(tr);
    });
  }

  async function refreshRewardRulesPicklist(){
    const sel = $('#selRuleForReward');
    sel.innerHTML='';
    const opt0 = document.createElement('option'); opt0.value=''; opt0.textContent='Seleccione regla';
    sel.appendChild(opt0);
    currentRules.forEach(r => {
      const opt = document.createElement('option');
      opt.value = r.id_rule;
      opt.textContent = `Nivel ${r.nivel} · ${r.trigger_tipo} · M:${r.threshold_monto ?? '-'} · Q:${r.threshold_qty ?? '-'}`;
      sel.appendChild(opt);
    });
    if (currentRules[0]) sel.value = currentRules[0].id_rule;
  }

  async function upsertRule(rule){
    if (!currentPromoId) throw new Error('Primero guarda la promoción (header)');
    const j = await apiPost('rule_save', Object.assign({promo_id: currentPromoId}, rule));
    await refreshRules();
    await refreshRewardRulesPicklist();
    return j.id_rule;
  }

  async function delRule(id_rule){
    await apiPost('rule_del', {id_rule});
    await refreshRules();
    await refreshRewardRulesPicklist();
    await refreshRewards();
  }

  // REWARDS
  async function refreshRewards(){
    const id_rule = $('#selRuleForReward').value;
    if (!id_rule) { $('#tblRewards tbody').innerHTML=''; return; }
    const j = await apiGet({action:'reward_list', id_rule});
    const tb = $('#tblRewards tbody');
    tb.innerHTML='';
    (j.rows||[]).forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.reward_tipo}</td>
        <td>${r.valor ?? ''}</td>
        <td>${r.tope_valor ?? ''}</td>
        <td>${r.cve_articulo ?? ''}</td>
        <td>${r.qty ?? ''}</td>
        <td>${r.unimed ?? ''}</td>
        <td>${r.aplica_sobre ?? ''}</td>
        <td>${r.observaciones ?? ''}</td>
        <td>
          <button class="btn btn-sm btn-outline-secondary" data-act="rewardEdit" data-id="${r.id_reward}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-act="rewardDel" data-id="${r.id_reward}">X</button>
        </td>
      `;
      tb.appendChild(tr);
    });
  }

  async function upsertReward(reward){
    const id_rule = $('#selRuleForReward').value;
    if (!id_rule) throw new Error('Selecciona una regla');
    await apiPost('reward_save', Object.assign({id_rule}, reward));
    await refreshRewards();
  }

  async function delReward(id_reward){
    await apiPost('reward_del', {id_reward});
    await refreshRewards();
  }

  // SCOPE
  async function refreshScope(){
    if (!currentPromoId) { $('#tblScope tbody').innerHTML=''; return; }
    const j = await apiGet({action:'scope_list', promo_id: currentPromoId});
    const tb = $('#tblScope tbody');
    tb.innerHTML='';
    (j.rows||[]).forEach(s => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${s.scope_tipo}</td>
        <td>${s.scope_id}</td>
        <td>${s.exclusion}</td>
        <td>
          <button class="btn btn-sm btn-outline-secondary" data-act="scopeEdit" data-id="${s.id_scope}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-act="scopeDel" data-id="${s.id_scope}">X</button>
        </td>
      `;
      tb.appendChild(tr);
    });
  }

  async function upsertScope(scope){
    if (!currentPromoId) throw new Error('Primero guarda la promoción (header)');
    await apiPost('scope_save', Object.assign({promo_id: currentPromoId}, scope));
    await refreshScope();
    await loadPromos();
  }

  async function delScope(id_scope){
    await apiPost('scope_del', {id_scope});
    await refreshScope();
    await loadPromos();
  }

  // UI prompts rápidos (MVP)
  async function promptRuleEdit(existing){
    const r = Object.assign({id_rule:'', nivel:1, trigger_tipo:'MONTO', threshold_monto:'', threshold_qty:'', acumula:'S', acumula_por:'PERIODO', min_items_distintos:'', observaciones:''}, existing||{});
    r.nivel = prompt('Nivel (escalón):', r.nivel);
    if (r.nivel===null) return;
    r.trigger_tipo = prompt('Trigger (UNIDADES|MONTO|MIXTO|UNIDADES_Y_MONTO):', r.trigger_tipo);
    if (r.trigger_tipo===null) return;
    r.threshold_monto = prompt('Threshold Monto (vacío si no aplica):', r.threshold_monto ?? '');
    if (r.threshold_monto===null) return;
    r.threshold_qty = prompt('Threshold Qty (vacío si no aplica):', r.threshold_qty ?? '');
    if (r.threshold_qty===null) return;
    r.acumula = prompt('Acumula (S|N):', r.acumula);
    if (r.acumula===null) return;
    r.acumula_por = prompt('Acumula Por (TICKET|DIA|PERIODO):', r.acumula_por);
    if (r.acumula_por===null) return;
    r.observaciones = prompt('Observaciones:', r.observaciones||'') || '';

    // Normaliza vacíos
    ['threshold_monto','threshold_qty'].forEach(k => { if (r[k]==='') r[k]=''; });

    await upsertRule(r);
  }

  async function promptRewardEdit(existing){
    const r = Object.assign({id_reward:'', reward_tipo:'DESC_MONTO', valor:'', tope_valor:'', cve_articulo:'', qty:'', unimed:'', aplica_sobre:'TOTAL', observaciones:''}, existing||{});
    r.reward_tipo = prompt('Tipo (BONIFIE_PRODUCTO|DESC_PCT|DESC_MONTO):', r.reward_tipo);
    if (r.reward_tipo===null) return;
    r.valor = prompt('Valor (ej. 10 / 0.1 / 100):', r.valor ?? '');
    if (r.valor===null) return;
    r.tope_valor = prompt('Tope valor (opcional):', r.tope_valor ?? '');
    if (r.tope_valor===null) return;
    r.cve_articulo = prompt('Artículo (si BONIFIE_PRODUCTO):', r.cve_articulo ?? '');
    if (r.cve_articulo===null) return;
    r.qty = prompt('Qty (si BONIFIE_PRODUCTO):', r.qty ?? '');
    if (r.qty===null) return;
    r.unimed = prompt('UM (ej. PZA):', r.unimed ?? '');
    if (r.unimed===null) return;
    r.aplica_sobre = prompt('Aplica sobre (SUBTOTAL|TOTAL):', r.aplica_sobre ?? 'TOTAL');
    if (r.aplica_sobre===null) return;
    r.observaciones = prompt('Observaciones:', r.observaciones||'') || '';
    await upsertReward(r);
  }

  async function promptScopeEdit(existing){
    const s = Object.assign({id_scope:'', scope_tipo:'CLIENTE', scope_id:'', exclusion:'N'}, existing||{});
    s.scope_tipo = prompt('Tipo (CLIENTE|RUTA|VENDEDOR|CANAL|GRUPO):', s.scope_tipo);
    if (s.scope_tipo===null) return;
    s.scope_id = prompt('ID (clave del scope):', s.scope_id);
    if (s.scope_id===null) return;
    s.exclusion = prompt('Exclusión (S|N):', s.exclusion);
    if (s.exclusion===null) return;
    await upsertScope(s);
  }

  // Events
  $('#btnDebug').addEventListener('click', () => {
    debugBox.style.display = (debugBox.style.display==='none') ? 'block' : 'none';
  });

  $('#btnBuscar').addEventListener('click', () => loadPromos().catch(e=>alert(e.message)));
  $('#btnNuevo').addEventListener('click', () => openPromo(null).catch(e=>alert(e.message)));
  $('#btnGuardarPromo').addEventListener('click', () => savePromo().catch(e=>alert(e.message)));

  $('#selAlmacen').addEventListener('change', () => loadPromos().catch(e=>alert(e.message)));

  $('#tblPromos').addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const act = btn.dataset.act;
    const id = btn.dataset.id;
    if (['ver','rules','rewards','scope'].includes(act)) {
      const tab = (act==='ver') ? 'rules' : act;
      openPromo(id, tab).catch(e=>alert(e.message));
    }
  });

  $('#btnRuleNuevo').addEventListener('click', () => promptRuleEdit(null).catch(e=>alert(e.message)));
  $('#tblRules').addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const id_rule = btn.dataset.id;
    const act = btn.dataset.act;
    if (act==='ruleDel') {
      if (confirm('¿Eliminar regla?')) delRule(id_rule).catch(e=>alert(e.message));
    }
    if (act==='ruleEdit') {
      const r = currentRules.find(x => String(x.id_rule)===String(id_rule));
      promptRuleEdit(r).catch(e=>alert(e.message));
    }
  });

  $('#selRuleForReward').addEventListener('change', () => refreshRewards().catch(e=>alert(e.message)));
  $('#btnRewardNuevo').addEventListener('click', () => promptRewardEdit(null).catch(e=>alert(e.message)));
  $('#tblRewards').addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const act = btn.dataset.act;
    const id = btn.dataset.id;
    if (act==='rewardDel') {
      if (confirm('¿Eliminar reward?')) delReward(id).catch(e=>alert(e.message));
    }
    if (act==='rewardEdit') {
      // Para MVP no cargamos el row completo; se edita recreando (recomendado: endpoint reward_get)
      alert('Edición rápida: elimina y crea de nuevo (o agregamos reward_get si lo requieres).');
    }
  });

  $('#btnScopeNuevo').addEventListener('click', () => promptScopeEdit(null).catch(e=>alert(e.message)));
  $('#tblScope').addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const act = btn.dataset.act;
    const id = btn.dataset.id;
    if (act==='scopeDel') {
      if (confirm('¿Eliminar scope?')) delScope(id).catch(e=>alert(e.message));
    }
    if (act==='scopeEdit') {
      alert('Para edición: elimina y crea de nuevo (o agregamos scope_get).');
    }
  });

  // Init
  loadAlmacenes()
    .then(() => loadPromos())
    .catch(e => alert(e.message));
})();
</script>

<?php if ($__hasLayout) { require_once $__maybeFooter; } ?>
