<?php
/* =========================================================
   UI - DISEÑO DE PROMOCIÓN (UI + selects catálogo)
   Ubicación: /public/sfa/promociones/promo_design.php
   ========================================================= */

require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<style>
  .ap-title{font-weight:700;letter-spacing:.2px}
  .ap-card{border:1px solid rgba(0,0,0,.08);border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.05);background:#fff}
  .ap-help{font-size:12px;color:#6c757d}
  .ap-pill{font-size:12px;border-radius:999px;padding:.15rem .55rem;background:#f1f3f5;border:1px solid rgba(0,0,0,.06)}
  .ap-result{max-height:240px;overflow:auto;border:1px solid rgba(0,0,0,.08);border-radius:10px}
  .ap-result .list-group-item{cursor:pointer}
  .ap-result .list-group-item:hover{background:#f8f9fa}
  .ap-chip{display:inline-flex;align-items:center;gap:.4rem;border:1px solid rgba(0,0,0,.12);border-radius:999px;padding:.25rem .6rem;margin:.15rem .15rem 0 0;font-size:12px;background:#fff}
  .ap-chip button{border:none;background:transparent;padding:0;line-height:1;font-size:14px;cursor:pointer;color:#dc3545}
  .ap-divider{border-top:1px solid rgba(0,0,0,.08)}
  .kbd{font-size:11px;border:1px solid rgba(0,0,0,.15);border-bottom-width:2px;border-radius:6px;padding:0 6px;background:#fff}
</style>

<div class="container-fluid mt-3">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="ap-title" style="font-size:18px;">Diseñador de Promociones</div>
      <div class="ap-help">
        Búsqueda tipo autocomplete: escribe y sugiere, <span class="kbd">Enter</span> selecciona si hay coincidencia / único resultado.
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.history.back()">Regresar</button>
      <button class="btn btn-primary btn-sm" type="button" disabled title="Se conecta al guardado en la siguiente fase">Generar ID / Guardar</button>
    </div>
  </div>

  <div class="row g-3">
    <!-- Config general -->
    <div class="col-lg-4">
      <div class="ap-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="fw-bold">Configuración general</div>
          <span class="ap-pill">Fase: UI</span>
        </div>

        <div class="mb-2">
          <label class="form-label">Tipo de promoción</label>
          <select id="tipo_promo" class="form-select form-select-sm">
            <option value="UNIDADES">Unidades (piezas/cajas/etc)</option>
            <option value="TICKET">Ticket de Venta</option>
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
              <label class="form-label">Unidad de medida</label>
              <select id="th_um" class="form-select form-select-sm">
                <option value="PZA">Pieza</option>
                <option value="CAJ">Caja</option>
                <option value="PAQ">Paquete</option>
                <option value="TAR">Tarima</option>
              </select>
            </div>
          </div>

          <div class="ap-divider pt-3 mt-3">
            <div class="fw-bold mb-2">Vigencia</div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Inicio</label>
                <input id="vig_ini" type="date" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label">Fin</label>
                <input id="vig_fin" type="date" class="form-control form-control-sm">
              </div>
            </div>
          </div>
        </div>

        <div id="bloque_ticket" class="ap-divider pt-3 mt-3" style="display:none;">
          <div class="fw-bold mb-2">Ticket de Venta</div>
          <label class="form-label mt-2">Monto mínimo del ticket</label>
          <input type="number" class="form-control form-control-sm" placeholder="Ej. 25000">
        </div>

        <div id="bloque_acumulada" class="ap-divider pt-3 mt-3" style="display:none;">
          <div class="fw-bold mb-2">Venta Acumulada</div>
          <label class="form-label mt-2">Monto acumulado objetivo</label>
          <input type="number" class="form-control form-control-sm" placeholder="Ej. 100000">
          <label class="form-label mt-2">Periodo</label>
          <select class="form-select form-select-sm">
            <option>Últimos 30 días</option>
            <option>Mes calendario</option>
          </select>
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
                  <option value="PRODUCTO">Producto</option>
                  <option value="GRUPO">Grupo de productos</option>
                </select>
              </div>

              <div class="col-md-8">
                <label class="form-label">Buscar (auto)</label>
                <div class="input-group input-group-sm">
                  <input id="base_q" class="form-control" placeholder="Clave / descripción... (Enter = seleccionar)">
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
                  <option value="PZA">Pieza</option>
                  <option value="CAJ">Caja</option>
                  <option value="PAQ">Paquete</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Modo</label>
                <select id="rw_modo" class="form-select form-select-sm">
                  <option value="PRODUCTO">Producto</option>
                  <option value="GRUPO">Grupo de productos</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Buscar (auto)</label>
                <div class="input-group input-group-sm">
                  <input id="rw_q" class="form-control" placeholder="Clave / descripción... (Enter = seleccionar)">
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
                      <option value="PRODUCTO">Producto</option>
                      <option value="GRUPO">Grupo</option>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Buscar equivalente (auto)</label>
                    <div class="input-group input-group-sm">
                      <input id="alt_q" class="form-control" placeholder="Clave / descripción... (Enter = agregar)">
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

  <div class="mt-3 ap-help">
    API detectada: <code id="api_info"></code>
  </div>

</div>

<script>
  // =========================================================
  //  RUTAS CORRECTAS A API (AUTO-DETECTA BASE /public)
  //  Ej local:
  //  /assistpro_kardex_fc/public/sfa/promociones/promo_design.php
  //  => basePublic = /assistpro_kardex_fc/public
  // =========================================================
  const PATH = window.location.pathname;
  const basePublic = PATH.includes('/public/')
    ? PATH.split('/public/')[0] + '/public'
    : '/public';

  const API_ARTICULOS = basePublic + '/api/articulos_api.php';
  const API_GRUPOS    = basePublic + '/api/api_grupos.php';

  document.getElementById('api_info').textContent = `${API_ARTICULOS} | ${API_GRUPOS}`;

  // Config UX
  const LIVE_MIN_CHARS = 2;
  const LIVE_DEBOUNCE  = 220;
  const MAX_ROWS       = 25;

  const alt = [];
  const timers = {};
  const lastRows = { base: [], rw: [], alt: [] };

  function $(id){ return document.getElementById(id); }

  function setBloquePorTipo(){
    const t = $('tipo_promo').value;
    $('bloque_unidades').style.display  = (t==='UNIDADES') ? 'block' : 'none';
    $('bloque_ticket').style.display    = (t==='TICKET') ? 'block' : 'none';
    $('bloque_acumulada').style.display = (t==='ACUMULADA') ? 'block' : 'none';
  }
  $('tipo_promo').addEventListener('change', setBloquePorTipo);
  setBloquePorTipo();

  function escapeHtml(s){
    return (s||'').toString().replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  async function fetchJson(url){
    const r = await fetch(url, {credentials:'same-origin', cache:'no-store'});
    const txt = await r.text();

    if(!r.ok){
      throw new Error(`HTTP ${r.status} ${r.statusText}: ${txt.slice(0,200)}`);
    }
    try { return JSON.parse(txt); }
    catch(e){ throw new Error('Respuesta no JSON: ' + txt.slice(0,200)); }
  }

  function normalizeRows(j){
    if (j && Array.isArray(j.rows)) return j.rows;
    if (j && Array.isArray(j.data)) return j.data;
    if (j && j.data && Array.isArray(j.data.rows)) return j.data.rows;
    return [];
  }

  function rowToPick(r, tipo){
    let val = '', label = '';
    if(tipo === 'PRODUCTO'){
      val = (r.cve_articulo ?? r.Clave ?? r.clave ?? r.id ?? '').toString();
      const des = (r.des_articulo ?? r.Descripcion ?? r.descripcion ?? '').toString();
      label = des ? `${val} - ${des}` : `${val}`;
    } else {
      val = (r.cve_gpoart ?? r.Clave ?? r.clave ?? r.id ?? '').toString();
      const des = (r.des_gpoart ?? r.Descripcion ?? r.descripcion ?? '').toString();
      label = des ? `${val} - ${des}` : `${val}`;
    }
    return { val, label };
  }

  function renderList(scope, rows, tipo, onPick){
    const box = $(scope + '_res');
    box.innerHTML = '';

    if(!rows.length){
      box.innerHTML = '<div class="list-group-item small text-muted">Sin resultados</div>';
      return;
    }

    rows.slice(0, MAX_ROWS).forEach(r=>{
      const pick = rowToPick(r, tipo);
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action';
      a.innerHTML = `<div class="small"><b>${escapeHtml(pick.val)}</b> <span class="text-muted">${escapeHtml(pick.label.replace(pick.val+' - ',''))}</span></div>`;
      a.addEventListener('click', ()=>onPick(pick));
      box.appendChild(a);
    });
  }

  function setSelected(scope, tipo, val, label){
    $(scope + '_tipo_sel').value  = tipo;
    $(scope + '_val_sel').value   = val;
    $(scope + '_label_sel').value = label;

    $(scope + '_sel').innerHTML = `
      <span class="ap-chip">
        <span class="text-muted">${tipo}</span>
        <b>${escapeHtml(label)}</b>
        <button type="button" title="Quitar" onclick="clearSelected('${scope}')">&times;</button>
      </span>
    `;
    $(scope + '_res').innerHTML = '';
  }

  function clearSelected(scope){
    $(scope + '_tipo_sel').value = '';
    $(scope + '_val_sel').value = '';
    $(scope + '_label_sel').value = '';
    $(scope + '_sel').innerHTML = '<span class="small text-muted">Sin selección</span>';
  }
  clearSelected('base');
  clearSelected('rw');

  function toggleAlt(){
    const b = $('alt_box');
    b.style.display = (b.style.display === 'none') ? 'block' : 'none';
  }

  function addAlt(tipo, val, label){
    if(alt.some(x => x.tipo===tipo && x.val===val)) return;
    alt.push({tipo, val, label});
    renderAlt();
    $('alt_res').innerHTML = '';
    $('alt_q').value = '';
  }

  function delAlt(idx){
    alt.splice(idx,1);
    renderAlt();
  }

  function renderAlt(){
    const box = $('alt_list');
    if(!alt.length){
      box.innerHTML = '<span class="small text-muted">Sin equivalentes</span>';
      return;
    }
    box.innerHTML = alt.map((x,i)=>`
      <span class="ap-chip">
        <span class="text-muted">${x.tipo}</span>
        <b>${escapeHtml(x.label)}</b>
        <button type="button" title="Quitar" onclick="delAlt(${i})">&times;</button>
      </span>
    `).join('');
  }

  async function buscar(scope, allowAutoPick=false){
    const modo = $(scope + '_modo') ? $(scope + '_modo').value : 'PRODUCTO';
    const q = ($(scope + '_q') ? $(scope + '_q').value : '').trim();

    if(!q){
      $(scope + '_res').innerHTML = '';
      lastRows[scope] = [];
      return;
    }

    let url = '';
    if(modo === 'PRODUCTO'){
      url = API_ARTICULOS + '?action=list&limit=' + MAX_ROWS + '&page=1&q=' + encodeURIComponent(q);
    } else {
      url = API_GRUPOS + '?action=list&limit=' + MAX_ROWS + '&page=1&q=' + encodeURIComponent(q);
    }

    let j;
    try{
      j = await fetchJson(url);
    }catch(e){
      console.error(e);
      $(scope + '_res').innerHTML =
        `<div class="list-group-item small text-danger">${escapeHtml(e.message)}</div>`;
      return;
    }

    if (j && (j.ok === 0 || j.ok === false || j.success === false)) {
      $(scope + '_res').innerHTML =
        `<div class="list-group-item small text-danger">${escapeHtml(j.msg || j.error || 'Error al consultar catálogo')}</div>`;
      return;
    }
    if (j && j.error) {
      $(scope + '_res').innerHTML =
        `<div class="list-group-item small text-danger">${escapeHtml(j.error)}</div>`;
      return;
    }

    const rows = normalizeRows(j);
    lastRows[scope] = rows;

    const qUpper = q.toUpperCase();
    const exact = rows.find(r=>{
      const pick = rowToPick(r, modo);
      return (pick.val || '').toUpperCase() === qUpper;
    });

    const onPick = (pick)=>{
      if(scope === 'alt') addAlt(modo, pick.val, pick.label);
      else setSelected(scope, modo, pick.val, pick.label);
    };

    if(exact){
      onPick(rowToPick(exact, modo));
      return;
    }

    if(allowAutoPick && rows.length === 1){
      onPick(rowToPick(rows[0], modo));
      return;
    }

    renderList(scope, rows, modo, onPick);
  }

  function bindLive(scope){
    const input = $(scope + '_q');
    if(!input) return;

    input.addEventListener('input', ()=>{
      const v = input.value.trim();
      if(v.length < LIVE_MIN_CHARS){
        $(scope + '_res').innerHTML = '';
        lastRows[scope] = [];
        return;
      }
      clearTimeout(timers[scope]);
      timers[scope] = setTimeout(()=> buscar(scope, false), LIVE_DEBOUNCE);
    });

    input.addEventListener('keydown', (ev)=>{
      if(ev.key === 'Enter'){
        ev.preventDefault();
        buscar(scope, true);
      }
      if(ev.key === 'Escape'){
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
</script>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
?>
