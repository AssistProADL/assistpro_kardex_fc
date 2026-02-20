<?php
/* =========================================================
   UI - DISEÑO DE PROMOCIÓN V2 (LEGACY COMPAT)
   Ubicación: /public/sfa/promociones/promo_design.php

   Objetivo:
   - Guardar ENCABEZADO en listapromo
   - Guardar DETALLE en detallegpopromo (producto disparador + regalo + equivalentes)
   - Dependencias empresa/almacén desde api_empresas_almacenes_rutas.php (sin sesión)
   - Inputs normalizados a MAYÚSCULAS
   - Fechas dd/mm/aaaa (se normaliza a ISO para DB)
   ========================================================= */

require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<style>
  /* 10px global para vista */
  .ap-wrap, .ap-wrap * { font-size: 10px !important; }

  .ap-title { font-weight: 700; letter-spacing: .2px }
  .ap-card {
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.05);
    background: #fff
  }
  .ap-help { font-size: 10px !important; color:#6c757d }
  .ap-pill {
    font-size: 10px !important;
    border-radius: 999px;
    padding: .15rem .55rem;
    background: #f1f3f5;
    border: 1px solid rgba(0,0,0,.06)
  }
  .ap-result {
    max-height: 240px;
    overflow: auto;
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 10px
  }
  .ap-result .list-group-item { cursor:pointer }
  .ap-result .list-group-item:hover { background:#f8f9fa }
  .ap-chip {
    display:inline-flex;
    align-items:center;
    gap:.4rem;
    border:1px solid rgba(0,0,0,.12);
    border-radius:999px;
    padding:.25rem .6rem;
    margin:.15rem .15rem 0 0;
    background:#fff
  }
  .ap-chip button {
    border:none;
    background:transparent;
    padding:0;
    line-height:1;
    font-size: 14px !important;
    cursor:pointer;
    color:#dc3545
  }
  .ap-divider { border-top: 1px solid rgba(0,0,0,.08) }
  .kbd {
    font-size: 10px !important;
    border: 1px solid rgba(0,0,0,.15);
    border-bottom-width: 2px;
    border-radius: 6px;
    padding: 0 6px;
    background: #fff
  }
</style>

<div class="container-fluid mt-3 ap-wrap">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="ap-title" style="font-size:18px !important;">Diseñador de Promociones V2</div>
      <div class="ap-help">
        Autocomplete: escribe y sugiere, <span class="kbd">Enter</span> selecciona si coincide / único resultado. Fechas: <span class="kbd">dd/mm/aaaa</span> (se normaliza).
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.history.back()">Regresar</button>
      <button class="btn btn-primary btn-sm" type="button" onclick="guardarPromo()">Generar Folio / Guardar</button>
    </div>
  </div>

  <div class="row g-3">

    <!-- Config general -->
    <div class="col-lg-4">
      <div class="ap-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="fw-bold">Configuración general</div>
          <span class="ap-pill">V2</span>
        </div>

        <div class="mb-2">
          <label class="form-label">Empresa <span class="text-danger">*</span></label>
          <select id="id_empresa" class="form-select form-select-sm">
            <option value="">Seleccione empresa</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Almacén <span class="text-danger">*</span></label>
          <select id="id_almacen" class="form-select form-select-sm">
            <option value="">Seleccione un almacén</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Nombre de la promoción <span class="text-danger">*</span></label>
          <input id="promo_nombre" class="form-control form-control-sm" placeholder="Ej. PROMO FEB14" autocomplete="off">
          <div class="ap-help mt-1">Se guarda como <b>Descripcion</b> en <b>listapromo</b>. Todo se normaliza a MAYÚSCULAS.</div>
        </div>

        <div class="mb-2">
          <label class="form-label">Tipo de promoción</label>
          <select id="tipo_promo" class="form-select form-select-sm">
            <option value="UNIDADES">Unidades (piezas/cajas/etc)</option>
            <option value="MONTO">Ticket de Venta</option>
            <option value="ACUMULADA">Venta Acumulada</option>
          </select>
        </div>

        <div id="bloque_unidades" class="ap-divider pt-3 mt-3">
          <div class="fw-bold mb-2">Condición por Unidades</div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Cantidad objetivo</label>
              <input id="th_qty" type="number" step="1" min="0" class="form-control form-control-sm" placeholder="Ej. 5">
            </div>
            <div class="col-6">
              <label class="form-label">UM objetivo</label>
              <select id="th_um" class="form-select form-select-sm">
                <option value="PZA">PZA</option>
                <option value="CAJ">CAJ</option>
                <option value="PAQ">PAQ</option>
                <option value="TAR">TAR</option>
              </select>
            </div>
          </div>

          <div class="ap-divider pt-3 mt-3">
            <div class="fw-bold mb-2">Vigencia</div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Inicio</label>
                <input id="vig_ini" type="text" inputmode="numeric" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
              </div>
              <div class="col-6">
                <label class="form-label">Fin</label>
                <input id="vig_fin" type="text" inputmode="numeric" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
              </div>
              <div class="col-12"><div id="vig_hint" class="ap-help">Validación: la fecha fin no puede ser menor a la inicial.</div></div>
            </div>
          </div>
        </div>

        <!-- (Se dejan los otros tipos para fase 2; hoy legacy opera principalmente por UNIDADES) -->
        <div id="bloque_ticket" class="ap-divider pt-3 mt-3" style="display:none;">
          <div class="fw-bold mb-2">Ticket de Venta</div>
          <label class="form-label mt-2">Monto mínimo del ticket</label>
          <input id="ticket_monto" type="number" class="form-control form-control-sm" placeholder="Ej. 25000">
        </div>

        <div id="bloque_acumulada" class="ap-divider pt-3 mt-3" style="display:none;">
          <div class="fw-bold mb-2">Venta Acumulada</div>
          <label class="form-label mt-2">Monto acumulado objetivo</label>
          <input id="acc_monto" type="number" class="form-control form-control-sm" placeholder="Ej. 100000">
        </div>
      </div>
    </div>

    <!-- Producto base + regalo -->
    <div class="col-lg-8">
      <div class="row g-3">

        <!-- Producto base -->
        <div class="col-12">
          <div class="ap-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Producto base (disparador)</div>
              <span class="ap-pill">Catálogo</span>
            </div>

            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Modo</label>
                <select id="base_modo" class="form-select form-select-sm">
                  <option value="PRODUCTO">PRODUCTO</option>
                  <option value="GRUPO">GRUPO</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Buscar (auto)</label>
                <div class="input-group input-group-sm">
                  <input id="base_q" class="form-control" placeholder="CLAVE / DESCRIPCIÓN... (ENTER = SELECCIONAR)">
                  <button class="btn btn-outline-primary" type="button" onclick="buscar('base', true)">Buscar</button>
                </div>
              </div>
            </div>

            <div class="mt-2 ap-result">
              <div id="base_res" class="list-group list-group-flush"></div>
            </div>

            <div class="mt-2">
              <div class="ap-help">Seleccionado:</div>
              <div id="base_sel" class="mt-1"></div>
              <input type="hidden" id="base_tipo_sel" value="">
              <input type="hidden" id="base_val_sel" value="">
              <input type="hidden" id="base_label_sel" value="">
            </div>
          </div>
        </div>

        <!-- Producto obsequio -->
        <div class="col-12">
          <div class="ap-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Producto obsequio (beneficio)</div>
              <span class="ap-pill">Catálogo</span>
            </div>

            <div class="row g-2 align-items-end">
              <div class="col-md-3">
                <label class="form-label">Cantidad regalo</label>
                <input id="rw_qty" type="number" step="1" min="0" class="form-control form-control-sm" placeholder="Ej. 1">
              </div>
              <div class="col-md-3">
                <label class="form-label">UM regalo</label>
                <select id="rw_um" class="form-select form-select-sm">
                  <option value="PZA">PZA</option>
                  <option value="CAJ">CAJ</option>
                  <option value="PAQ">PAQ</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Modo</label>
                <select id="rw_modo" class="form-select form-select-sm">
                  <option value="PRODUCTO">PRODUCTO</option>
                  <option value="GRUPO">GRUPO</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Buscar (auto)</label>
                <div class="input-group input-group-sm">
                  <input id="rw_q" class="form-control" placeholder="CLAVE / DESCRIPCIÓN... (ENTER = SELECCIONAR)">
                  <button class="btn btn-outline-primary" type="button" onclick="buscar('rw', true)">Buscar</button>
                </div>
              </div>
            </div>

            <div class="mt-2 ap-result">
              <div id="rw_res" class="list-group list-group-flush"></div>
            </div>

            <div class="mt-2">
              <div class="ap-help">Seleccionado:</div>
              <div id="rw_sel" class="mt-1"></div>
              <input type="hidden" id="rw_tipo_sel" value="">
              <input type="hidden" id="rw_val_sel" value="">
              <input type="hidden" id="rw_label_sel" value="">
            </div>

            <div class="ap-divider mt-3 pt-3">
              <div class="d-flex align-items-center justify-content-between">
                <div class="fw-bold">Producto equivalente (si se agota)</div>
                <button class="btn btn-outline-success btn-sm" type="button" onclick="toggleAlt()">+ Agregar equivalente</button>
              </div>

              <div id="alt_box" style="display:none;" class="mt-2">
                <div class="row g-2 align-items-end">
                  <div class="col-md-4">
                    <label class="form-label">Modo</label>
                    <select id="alt_modo" class="form-select form-select-sm">
                      <option value="PRODUCTO">PRODUCTO</option>
                      <option value="GRUPO">GRUPO</option>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Buscar equivalente (auto)</label>
                    <div class="input-group input-group-sm">
                      <input id="alt_q" class="form-control" placeholder="CLAVE / DESCRIPCIÓN... (ENTER = AGREGAR)">
                      <button class="btn btn-outline-primary" type="button" onclick="buscar('alt', true)">Buscar</button>
                    </div>
                  </div>
                </div>
                <div class="mt-2 ap-result">
                  <div id="alt_res" class="list-group list-group-flush"></div>
                </div>
              </div>

              <div class="mt-2">
                <div class="ap-help">Equivalentes:</div>
                <div id="alt_list" class="mt-1"></div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  /* =========================================================
     RUTAS (auto-detecta base /public)
     ========================================================= */
  const PATH = window.location.pathname;
  const basePublic = PATH.includes('/public/') ? (PATH.split('/public/')[0] + '/public') : '/public';

  const API_ARTICULOS = basePublic + '/api/articulos_api.php';
  const API_GRUPOS    = basePublic + '/api/api_grupos.php';
  const API_EMP_ALM   = basePublic + '/api/api_empresas_almacenes_rutas.php';
  const API_PROMO_V2  = basePublic + '/api/promociones/promociones_v2_api.php';

  const LIVE_MIN_CHARS = 2;
  const LIVE_DEBOUNCE  = 220;
  const MAX_ROWS       = 25;

  const alt = [];
  const timers = {};
  const lastRows = { base: [], rw: [], alt: [] };

  function $(id){ return document.getElementById(id); }

  function upper(v){ return (v ?? '').toString().trim().toUpperCase(); }

  function escapeHtml(s){
    return (s || '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }

  async function fetchJson(url, opts = {}){
    const r = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...opts });
    const txt = await r.text();
    if (!r.ok) throw new Error(`HTTP ${r.status} ${r.statusText}: ${txt.slice(0,200)}`);
    try { return JSON.parse(txt); }
    catch(e){ throw new Error('Respuesta no JSON: ' + txt.slice(0,200)); }
  }

  function normalizeRows(j){
    if (j && Array.isArray(j.rows)) return j.rows;
    if (j && Array.isArray(j.data)) return j.data;
    if (j && j.data && Array.isArray(j.data.rows)) return j.data.rows;
    return [];
  }

  /* =========================================================
     Empresas / Almacenes (sin sesión)
     ========================================================= */
  async function loadEmpresas(){
    const sel = $('id_empresa');
    sel.innerHTML = '<option value="">Seleccione empresa</option>';

    const j = await fetchJson(API_EMP_ALM + '?' + new URLSearchParams({ tipo: 'empresas' }));
    const rows = j.data || j.rows || j.empresas || [];

    rows.forEach(r => {
      const id = (r.id_empresa ?? r.IdEmpresa ?? r.id ?? r.cve_cia ?? '').toString().trim();
      const nom = (r.nombre ?? r.Nombre ?? r.razon_social ?? r.descripcion ?? '').toString().trim();
      if (!id) return;
      sel.add(new Option(nom ? `(${id}) ${nom}` : `(${id})`, id));
    });

    // preselect por URL ?empresa_id=...
    const p = new URLSearchParams(window.location.search);
    const empresaParam = p.get('empresa_id') || p.get('empresa') || '';
    if (empresaParam) sel.value = empresaParam;
  }

  async function loadAlmacenes(){
    const sel = $('id_almacen');
    sel.innerHTML = '<option value="">Seleccione un almacén</option>';

    const empresaId = $('id_empresa').value || '';
    const qs = new URLSearchParams({ tipo: 'almacenes' });
    if (empresaId) qs.append('empresa_id', empresaId);

    const j = await fetchJson(API_EMP_ALM + '?' + qs.toString());
    const rows = j.data || j.rows || j.almacenes || [];

    rows.forEach(r => {
      const cve = (r.cve_almac ?? r.Cve_Almac ?? r.id_almacen ?? r.id ?? '').toString().trim();
      const nom = (r.nombre ?? r.Nombre ?? r.des_almac ?? r.descripcion ?? '').toString().trim();
      if (!cve) return;
      sel.add(new Option(nom ? `(${cve}) ${nom}` : `(${cve})`, cve));
    });

    // preselect por URL ?almacen_id=...
    const p = new URLSearchParams(window.location.search);
    const almacenParam = p.get('almacen_id') || '';
    if (almacenParam) sel.value = almacenParam;
  }

  $('id_empresa').addEventListener('change', async () => { await loadAlmacenes(); });

  document.addEventListener('DOMContentLoaded', async () => {
    try {
      await loadEmpresas();
      await loadAlmacenes();
    } catch (e) {
      console.error(e);
      showErrorToast(e.message);
    }
  });

  /* =========================================================
     Normalización de fechas (dd/mm/aaaa o yyyy-mm-dd)
     ========================================================= */
  function normalizeDateToISO(v){
    v = (v || '').toString().trim();
    if (!v) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v; // yyyy-mm-dd
    const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return '';
    const dd = m[1], mm = m[2], yyyy = m[3];
    return `${yyyy}-${mm}-${dd}`;
  }
  function isValidISO(iso){ return /^\d{4}-\d{2}-\d{2}$/.test(iso); }
  function cmpISO(a,b){ return a.localeCompare(b); }

  function bindUpper(id){
    const el = $(id);
    if (!el) return;
    el.addEventListener('input', () => { el.value = upper(el.value); });
    el.addEventListener('blur',  () => { el.value = upper(el.value); });
  }
  bindUpper('promo_nombre');
  bindUpper('base_q');
  bindUpper('rw_q');
  bindUpper('alt_q');

  function setBloquePorTipo(){
    const t = $('tipo_promo').value;
    $('bloque_unidades').style.display = (t === 'UNIDADES') ? 'block' : 'none';
    $('bloque_ticket').style.display   = (t === 'MONTO') ? 'block' : 'none';
    $('bloque_acumulada').style.display= (t === 'ACUMULADA') ? 'block' : 'none';
  }
  $('tipo_promo').addEventListener('change', setBloquePorTipo);
  setBloquePorTipo();

  function rowToPick(r, tipo){
    let val = '', label = '';
    if (tipo === 'PRODUCTO') {
      val = (r.cve_articulo ?? r.Clave ?? r.clave ?? r.id ?? '').toString().trim();
      const des = (r.des_articulo ?? r.Descripcion ?? r.descripcion ?? '').toString().trim();
      label = des ? `${val} - ${des}` : `${val}`;
    } else {
      val = (r.cve_gpoart ?? r.Clave ?? r.clave ?? r.id ?? '').toString().trim();
      const des = (r.des_gpoart ?? r.Descripcion ?? r.descripcion ?? '').toString().trim();
      label = des ? `${val} - ${des}` : `${val}`;
    }
    return { val: upper(val), label: upper(label) };
  }

  function renderList(scope, rows, tipo, onPick){
    const box = $(scope + '_res');
    box.innerHTML = '';

    if (!rows.length){
      box.innerHTML = '<div class="list-group-item small text-muted">Sin resultados</div>';
      return;
    }

    rows.slice(0, MAX_ROWS).forEach(r => {
      const pick = rowToPick(r, tipo);
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action';
      const soloDes = pick.label.replace(pick.val + ' - ', '');
      a.innerHTML = `<div class="small"><b>${escapeHtml(pick.val)}</b> <span class="text-muted">${escapeHtml(soloDes)}</span></div>`;
      a.addEventListener('click', () => onPick(pick));
      box.appendChild(a);
    });
  }

  function setSelected(scope, tipo, val, label){
    $(scope + '_tipo_sel').value = tipo;
    $(scope + '_val_sel').value = val;
    $(scope + '_label_sel').value = label;

    $(scope + '_sel').innerHTML = `
      <span class="ap-chip">
        <span class="text-muted">${tipo}</span>
        <b>${escapeHtml(label)}</b>
        <button type="button" title="Quitar" onclick="clearSelected('${scope}')">&times;</button>
      </span>
    `;

    $(scope + '_res').innerHTML = '';
    if ($(scope + '_q')) $(scope + '_q').value = '';
  }

  function clearSelected(scope){
    $(scope + '_tipo_sel').value = '';
    $(scope + '_val_sel').value = '';
    $(scope + '_label_sel').value = '';
    $(scope + '_sel').innerHTML = '<span class="small text-muted">Sin selección</span>';
    if ($(scope + '_q')) $(scope + '_q').value = '';
  }
  clearSelected('base');
  clearSelected('rw');

  function toggleAlt(){
    const b = $('alt_box');
    b.style.display = (b.style.display === 'none') ? 'block' : 'none';
  }

  function addAlt(tipo, val, label){
    if (alt.some(x => x.tipo === tipo && x.val === val)) return;
    alt.push({ tipo, val, label });
    renderAlt();
    $('alt_res').innerHTML = '';
    $('alt_q').value = '';
  }

  function delAlt(idx){
    alt.splice(idx, 1);
    renderAlt();
  }

  function renderAlt(){
    const box = $('alt_list');
    if (!alt.length){
      box.innerHTML = '<span class="small text-muted">Sin equivalentes</span>';
      return;
    }
    box.innerHTML = alt.map((x,i) => `
      <span class="ap-chip">
        <span class="text-muted">${x.tipo}</span>
        <b>${escapeHtml(x.label)}</b>
        <button type="button" title="Quitar" onclick="delAlt(${i})">&times;</button>
      </span>
    `).join('');
  }
  renderAlt();

  async function buscar(scope, allowAutoPick=false){
    const modo = $(scope + '_modo') ? $(scope + '_modo').value : 'PRODUCTO';
    const q = upper(($(scope + '_q') ? $(scope + '_q').value : '').trim());

    if (!q){
      $(scope + '_res').innerHTML = '';
      lastRows[scope] = [];
      return;
    }

    let url = '';
    if (modo === 'PRODUCTO') {
      url = API_ARTICULOS + '?action=list&limit=' + MAX_ROWS + '&page=1&q=' + encodeURIComponent(q);
    } else {
      url = API_GRUPOS + '?action=list&limit=' + MAX_ROWS + '&page=1&q=' + encodeURIComponent(q);
    }

    let j;
    try {
      j = await fetchJson(url);
    } catch(e) {
      console.error(e);
      $(scope + '_res').innerHTML = `<div class="list-group-item small text-danger">${escapeHtml(e.message)}</div>`;
      return;
    }

    if (j && (j.ok === 0 || j.ok === false || j.success === false)){
      $(scope + '_res').innerHTML = `<div class="list-group-item small text-danger">${escapeHtml(j.msg || j.error || 'Error al consultar catálogo')}</div>`;
      return;
    }
    if (j && j.error){
      $(scope + '_res').innerHTML = `<div class="list-group-item small text-danger">${escapeHtml(j.error)}</div>`;
      return;
    }

    const rows = normalizeRows(j);
    lastRows[scope] = rows;

    const exact = rows.find(r => (rowToPick(r, modo).val || '').toUpperCase() === q);
    const onPick = (pick) => {
      if (scope === 'alt') addAlt(modo, pick.val, pick.label);
      else setSelected(scope, modo, pick.val, pick.label);
    };

    if (exact){ onPick(rowToPick(exact, modo)); return; }
    if (allowAutoPick && rows.length === 1){ onPick(rowToPick(rows[0], modo)); return; }

    renderList(scope, rows, modo, onPick);
  }

  function bindLive(scope){
    const input = $(scope + '_q');
    if (!input) return;

    input.addEventListener('input', () => {
      input.value = upper(input.value);
      const v = input.value.trim();
      if (v.length < LIVE_MIN_CHARS){
        $(scope + '_res').innerHTML = '';
        lastRows[scope] = [];
        return;
      }
      clearTimeout(timers[scope]);
      timers[scope] = setTimeout(() => buscar(scope, false), LIVE_DEBOUNCE);
    });

    input.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter'){
        ev.preventDefault();
        buscar(scope, true);
      }
      if (ev.key === 'Escape'){
        $(scope + '_res').innerHTML = '';
      }
    });
  }
  bindLive('base');
  bindLive('rw');
  bindLive('alt');

  window.buscar = buscar;
  window.toggleAlt = toggleAlt;
  window.delAlt = delAlt;

  /* =========================================================
     Toasts
     ========================================================= */
  function showSuccessToast(redirectUrl = null){
    const toastEl = document.getElementById('toastSuccess');
    const toast = new bootstrap.Toast(toastEl, { delay: 1800 });
    toast.show();
    if (redirectUrl){
      toastEl.addEventListener('hidden.bs.toast', () => { window.location.href = redirectUrl; }, { once:true });
    }
  }

  function showErrorToast(msg='Error'){
    document.getElementById('toastErrorMsg').textContent = msg;
    const toastEl = document.getElementById('toastError');
    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
  }

  /* =========================================================
     API helper (FormData)
     ========================================================= */
  async function postAPI(action, data){
    const form = new FormData();
    form.append('action', action);
    Object.entries(data).forEach(([k,v]) => {
      if (v !== undefined && v !== null && v !== '') form.append(k, v);
    });
    const j = await fetchJson(API_PROMO_V2, { method:'POST', body: form });
    if (!j.ok) throw new Error(j.error || j.msg || 'Error API');
    return j;
  }

  function validateVigencia(){
    const iniISO = normalizeDateToISO($('vig_ini').value);
    const finISO = normalizeDateToISO($('vig_fin').value);
    if (!iniISO || !finISO) return true;
    if (!isValidISO(iniISO) || !isValidISO(finISO)) return false;
    const ok = (cmpISO(finISO, iniISO) >= 0);
    $('vig_hint').className = ok ? 'ap-help' : 'ap-help text-danger';
    return ok;
  }
  $('vig_ini').addEventListener('blur', validateVigencia);
  $('vig_fin').addEventListener('blur', validateVigencia);

  /* =========================================================
     Guardado V2 (legacy tables)
     - 1) promo_save -> listapromo
     - 2) detalle_save -> detallegpopromo (y opcional lista equivalentes)
     ========================================================= */
  async function guardarPromo(){
    try {
      const empresaId = $('id_empresa').value;
      const almacenCve = $('id_almacen').value;
      const nombre = upper($('promo_nombre').value);

      if (!empresaId){ showErrorToast('Debe seleccionar una empresa'); return; }
      if (!almacenCve){ showErrorToast('Debe seleccionar un almacén'); return; }
      if (!nombre){ showErrorToast('Falta nombre de la promoción'); return; }

      const tipoPromo = $('tipo_promo').value;
      if (!$('base_val_sel').value){ showErrorToast('Falta producto base'); return; }
      if (!$('rw_val_sel').value){ showErrorToast('Falta producto obsequio'); return; }
      if (!$('rw_qty').value){ showErrorToast('Falta cantidad regalo'); return; }

      let iniISO = '';
      let finISO = '';
      let cantidadObjetivo = '';
      let umObjetivo = '';
      let monto = '';

      if (tipoPromo === 'UNIDADES'){
        cantidadObjetivo = $('th_qty').value;
        umObjetivo = $('th_um').value;
        if (!cantidadObjetivo){ showErrorToast('Falta cantidad objetivo'); return; }

        iniISO = normalizeDateToISO($('vig_ini').value);
        finISO = normalizeDateToISO($('vig_fin').value);
        if (!iniISO || !finISO){ showErrorToast('Falta vigencia (dd/mm/aaaa)'); return; }
        if (!isValidISO(iniISO) || !isValidISO(finISO)) { showErrorToast('Formato de fecha inválido (use dd/mm/aaaa)'); return; }
        if (cmpISO(finISO, iniISO) < 0){ showErrorToast('Fecha fin no puede ser menor a fecha inicio'); return; }
      }

      if (tipoPromo === 'MONTO'){
        monto = $('ticket_monto').value;
        if (!monto){ showErrorToast('Falta monto mínimo del ticket'); return; }
      }
      if (tipoPromo === 'ACUMULADA'){
        monto = $('acc_monto').value;
        if (!monto){ showErrorToast('Falta monto acumulado'); return; }
      }

      // 1) ENCABEZADO
      // Lista: si no existe, el API genera PROMO-YYYYMMDD-### (vía c_folios si está disponible)
      const hdr = await postAPI('promo_save', {
        IdEmpresa: empresaId,
        Cve_Almac: almacenCve,
        Lista: '',
        Descripcion: nombre,
        Tipo: tipoPromo,
        FechaI: iniISO,
        FechaF: finISO,
        Monto: monto,
        Activa: 1,
        Caduca: 0
      });

      const promoId = hdr.data?.id;
      const lista   = hdr.data?.Lista;
      if (!promoId){ throw new Error('No regresó promoId'); }

      // 2) DETALLE
      const detalleAlt = alt
        .filter(x => x.tipo === 'PRODUCTO')
        .map(x => ({ Articulo: x.val, Cantidad: 0 }));

      await postAPI('detalle_save', {
        PromoId: promoId,
        IdEmpresa: empresaId,
        Cve_Almac: almacenCve,
        TipoPromo: tipoPromo,
        Disparador_Articulo: $('base_val_sel').value,
        Disparador_Cantidad: cantidadObjetivo,
        Disparador_TipMed: umObjetivo,
        Regalo_Articulo: $('rw_val_sel').value,
        Regalo_Cantidad: $('rw_qty').value,
        Regalo_TipMed: $('rw_um').value,
        AltJson: JSON.stringify(detalleAlt)
      });

      // OK
      document.getElementById('toastSuccessMsg').textContent = `✅ Guardado: ${lista || 'PROMO'} (ID ${promoId})`;
      showSuccessToast(basePublic + '/sfa/promociones/promociones.php');

    } catch(e) {
      console.error(e);
      showErrorToast(e.message || 'Error inesperado');
    }
  }

  window.guardarPromo = guardarPromo;
</script>

<!-- Toasts Bootstrap -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastSuccessMsg">✅ Promoción guardada correctamente</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>

  <div id="toastError" class="toast align-items-center text-bg-danger border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastErrorMsg">❌ Error</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../bi/_menu_global_end.php'; ?>
