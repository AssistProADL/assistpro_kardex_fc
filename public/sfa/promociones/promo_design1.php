<?php
/* =========================================================
   UI - DISEÑADOR DE PROMOCIONES V2 (FULL)
   Ubicación: /public/sfa/promociones/promo_design.php
   - NO usa sesión (todo por selectores)
   - Empresa/Almacén desde api_empresas_almacenes_rutas.php
   - Fechas dd/mm/aaaa (se convierten a ISO para el API)
   - Inputs normalizados a MAYÚSCULAS
   - Guardado FULL: header + detalle + equivalentes en 1 POST (save_full)
   ========================================================= */

require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<style>
  /* 10px global (regla corporativa) */
  .ap-wrap, .ap-wrap * { font-size:10px !important; }

  .ap-title { font-weight: 800; letter-spacing: .2px; font-size:18px !important; }
  .ap-card {
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.05);
    background: #fff;
  }
  .ap-help { font-size: 12px !important; color: #6c757d; }
  .ap-pill {
    font-size: 12px !important;
    border-radius: 999px;
    padding: .15rem .55rem;
    background: #f1f3f5;
    border: 1px solid rgba(0,0,0,.06)
  }
  .ap-result {
    max-height: 240px;
    overflow: auto;
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 10px;
  }
  .ap-result .list-group-item { cursor:pointer; }
  .ap-result .list-group-item:hover { background:#f8f9fa; }
  .ap-chip {
    display:inline-flex;
    align-items:center;
    gap:.4rem;
    border:1px solid rgba(0,0,0,.12);
    border-radius:999px;
    padding:.25rem .6rem;
    margin:.15rem .15rem 0 0;
    font-size:12px !important;
    background:#fff;
  }
  .ap-chip button{
    border:none; background:transparent; padding:0; line-height:1;
    font-size:14px !important; cursor:pointer; color:#dc3545;
  }
  .ap-divider { border-top:1px solid rgba(0,0,0,.08); }
  .kbd{
    font-size:11px !important;
    border:1px solid rgba(0,0,0,.15);
    border-bottom-width:2px;
    border-radius:6px;
    padding:0 6px;
    background:#fff;
  }

  /* Inputs compactos */
  .form-control, .form-select { padding-top:.25rem; padding-bottom:.25rem; }
  .form-label { margin-bottom:.25rem; }

  /* Validación inline */
  .ap-inline-warn { color:#dc3545; font-size:11px !important; }
  .ap-inline-ok   { color:#198754; font-size:11px !important; }
</style>

<div class="container-fluid mt-3 ap-wrap">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="ap-title">Diseñador de Promociones V2</div>
      <div class="ap-help">
        Autocomplete: escribe y sugiere, <span class="kbd">Enter</span> selecciona si coincide / único resultado. Fechas: <span class="kbd">dd/mm/aaaa</span>.
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.history.back()">Regresar</button>
      <button class="btn btn-primary btn-sm" type="button" onclick="guardarPromoFull()">Generar Folio / Guardar</button>
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
          <select id="cve_cia" class="form-select form-select-sm">
            <option value="">Seleccione empresa</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Almacén <span class="text-danger">*</span></label>
          <select id="cve_almac" class="form-select form-select-sm">
            <option value="">Seleccione un almacén</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Nombre de la promoción <span class="text-danger">*</span></label>
          <input id="promo_nombre" class="form-control form-control-sm ap-upper" placeholder="EJ. PROMO FEB14">
          <div class="ap-help">Se guarda como <b>Descripción</b> en listapromo. Todo se normaliza a <b>MAYÚSCULAS</b>.</div>
        </div>

        <div class="mb-2">
          <label class="form-label">Tipo de promoción</label>
          <select id="tipo_promo" class="form-select form-select-sm">
            <option value="UNIDADES">UNIDADES (PIEZAS/CAJAS/ETC)</option>
            <option value="TICKET">TICKET DE VENTA (MONTO)</option>
            <option value="ACUMULADA">VENTA ACUMULADA (MONTO)</option>
          </select>
        </div>

        <!-- UNIDADES -->
        <div id="bloque_unidades" class="ap-divider pt-3 mt-3">
          <div class="fw-bold mb-2">Condición por Unidades</div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Cantidad objetivo</label>
              <input id="th_qty" type="number" step="1" min="0" class="form-control form-control-sm" placeholder="Ej. 5">
            </div>
            <div class="col-6">
              <label class="form-label">UM objetivo</label>
              <select id="th_um" class="form-select form-select-sm ap-upper">
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
                <input id="vig_ini_txt" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
                <input id="vig_ini" type="hidden" value="">
              </div>
              <div class="col-6">
                <label class="form-label">Fin</label>
                <input id="vig_fin_txt" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
                <input id="vig_fin" type="hidden" value="">
              </div>
              <div class="col-12">
                <div id="vig_msg" class="ap-inline-ok">Validación: la fecha fin no puede ser menor a la inicial.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- TICKET -->
        <div id="bloque_ticket" class="ap-divider pt-3 mt-3" style="display:none;">
          <div class="fw-bold mb-2">Ticket de Venta</div>
          <label class="form-label mt-2">Monto mínimo del ticket</label>
          <input id="ticket_monto" type="number" class="form-control form-control-sm" placeholder="Ej. 25000">
        </div>

        <!-- ACUMULADA -->
        <div id="bloque_acumulada" class="ap-divider pt-3 mt-3" style="display:none;">
          <div class="fw-bold mb-2">Venta Acumulada</div>

          <label class="form-label mt-2">Monto acumulado objetivo</label>
          <input id="acc_monto" type="number" class="form-control form-control-sm" placeholder="Ej. 100000">

          <label class="form-label mt-2">Tipo de periodo</label>
          <select id="acc_periodo_tipo" class="form-select form-select-sm ap-upper">
            <option value="RELATIVO">RELATIVO</option>
            <option value="FIJO">FIJO (FECHAS)</option>
          </select>

          <div id="acc_relativo" class="mt-2">
            <select id="acc_periodo_rel" class="form-select form-select-sm ap-upper">
              <option value="30D">ÚLTIMOS 30 DÍAS</option>
              <option value="MES">MES CALENDARIO</option>
            </select>
          </div>

          <div id="acc_fijo" class="row g-2 mt-2" style="display:none;">
            <div class="col-6">
              <label class="form-label">Desde</label>
              <input id="acc_ini_txt" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
              <input id="acc_ini" type="hidden" value="">
            </div>
            <div class="col-6">
              <label class="form-label">Hasta</label>
              <input id="acc_fin_txt" class="form-control form-control-sm" placeholder="dd/mm/aaaa">
              <input id="acc_fin" type="hidden" value="">
            </div>
            <div class="col-12">
              <div id="acc_msg" class="ap-inline-ok">Validación: la fecha fin no puede ser menor a la inicial.</div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Productos -->
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
                <select id="base_modo" class="form-select form-select-sm ap-upper">
                  <option value="PRODUCTO">PRODUCTO</option>
                  <option value="GRUPO">GRUPO</option>
                </select>
              </div>

              <div class="col-md-8">
                <label class="form-label">Buscar (auto)</label>
                <div class="input-group input-group-sm">
                  <input id="base_q" class="form-control ap-upper" placeholder="CLAVE / DESCRIPCIÓN... (ENTER = SELECCIONAR)">
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
                <select id="rw_um" class="form-select form-select-sm ap-upper">
                  <option value="PZA">PZA</option>
                  <option value="CAJ">CAJ</option>
                  <option value="PAQ">PAQ</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Modo</label>
                <select id="rw_modo" class="form-select form-select-sm ap-upper">
                  <option value="PRODUCTO">PRODUCTO</option>
                  <option value="GRUPO">GRUPO</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Buscar (auto)</label>
                <div class="input-group input-group-sm">
                  <input id="rw_q" class="form-control ap-upper" placeholder="CLAVE / DESCRIPCIÓN... (ENTER = SELECCIONAR)">
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
                    <select id="alt_modo" class="form-select form-select-sm ap-upper">
                      <option value="PRODUCTO">PRODUCTO</option>
                      <option value="GRUPO">GRUPO</option>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Buscar equivalente (auto)</label>
                    <div class="input-group input-group-sm">
                      <input id="alt_q" class="form-control ap-upper" placeholder="CLAVE / DESCRIPCIÓN... (ENTER = AGREGAR)">
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
  // =========================================================
  //  BASE PUBLIC AUTO-DETECT
  // =========================================================
  const PATH = window.location.pathname;
  const basePublic = PATH.includes('/public/')
    ? PATH.split('/public/')[0] + '/public'
    : '/public';

  // Catálogos existentes (ya los tienes operando)
  const API_ARTICULOS = basePublic + '/api/articulos_api.php';
  const API_GRUPOS    = basePublic + '/api/api_grupos.php';

  // Dependencias corporativas Empresa/Almacén/Rutas
  const API_EMP_ALM_RUT = basePublic + '/api/api_empresas_almacenes_rutas.php';

  // PROMOS V2 (tu endpoint)
  const API_PROMO_V2 = basePublic + '/api/promociones/promociones_v2_api.php';

  // =========================================================
  //  Helpers
  // =========================================================
  function $(id){ return document.getElementById(id); }

  function escapeHtml(s) {
    return (s || '').toString().replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
  }

  function toUpperInputs(){
    document.querySelectorAll('.ap-upper').forEach(el=>{
      el.addEventListener('input', ()=>{
        const start = el.selectionStart, end = el.selectionEnd;
        el.value = (el.value || '').toUpperCase();
        try { el.setSelectionRange(start, end); } catch(e){}
      });
    });
  }

  async function fetchText(url, opt = {}) {
    const r = await fetch(url, { credentials:'same-origin', cache:'no-store', ...opt });
    const t = await r.text();
    if (!r.ok) throw new Error(`HTTP ${r.status} ${r.statusText}: ${t.slice(0,200)}`);
    return t;
  }

  async function fetchJson(url, opt = {}) {
    const t = await fetchText(url, opt);
    try { return JSON.parse(t); }
    catch(e){ throw new Error('Respuesta no JSON: ' + t.slice(0,200)); }
  }

  function showSuccessToast(msg = '✅ Guardado OK', redirectUrl = null) {
    $('toastSuccessMsg').innerText = msg;
    const toast = new bootstrap.Toast($('toastSuccess'), { delay: 2200 });
    toast.show();
    if (redirectUrl) {
      $('toastSuccess').addEventListener('hidden.bs.toast', ()=>{ window.location.href = redirectUrl; }, { once:true });
    }
  }

  function showErrorToast(msg = '❌ Error') {
    $('toastErrorMsg').innerText = msg;
    const toast = new bootstrap.Toast($('toastError'), { delay: 3500 });
    toast.show();
  }

  // =========================================================
  //  Fechas dd/mm/aaaa  -> ISO yyyy-mm-dd (hidden)
  // =========================================================
  function parseDMYtoISO(dmy){
    const v = (dmy || '').trim();
    if (!v) return '';
    const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return '';
    const dd = parseInt(m[1],10), mm = parseInt(m[2],10), yy = parseInt(m[3],10);
    if (mm < 1 || mm > 12) return '';
    if (dd < 1 || dd > 31) return '';
    // Validación fecha real
    const dt = new Date(Date.UTC(yy, mm-1, dd));
    if (dt.getUTCFullYear() !== yy || (dt.getUTCMonth()+1) !== mm || dt.getUTCDate() !== dd) return '';
    const iso = `${yy}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`;
    return iso;
  }

  function isoToDMY(iso){
    const v = (iso || '').trim();
    if (!v) return '';
    const m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return '';
    return `${m[3]}/${m[2]}/${m[1]}`;
  }

  function validateRange(isoIni, isoFin){
    if (!isoIni || !isoFin) return true;
    return (isoFin >= isoIni);
  }

  function bindDatePair(txtIni, hidIni, txtFin, hidFin, msgEl){
    const f = ()=>{
      const isoI = parseDMYtoISO($(txtIni).value);
      const isoF = parseDMYtoISO($(txtFin).value);
      $(hidIni).value = isoI;
      $(hidFin).value = isoF;

      if ($(txtIni).value && !isoI) {
        $(msgEl).className = 'ap-inline-warn';
        $(msgEl).innerText = 'Formato inválido en Inicio. Use dd/mm/aaaa.';
        return;
      }
      if ($(txtFin).value && !isoF) {
        $(msgEl).className = 'ap-inline-warn';
        $(msgEl).innerText = 'Formato inválido en Fin. Use dd/mm/aaaa.';
        return;
      }
      if (isoI && isoF && !validateRange(isoI, isoF)) {
        $(msgEl).className = 'ap-inline-warn';
        $(msgEl).innerText = 'La fecha FIN no puede ser menor a la INICIAL.';
        return;
      }
      $(msgEl).className = 'ap-inline-ok';
      $(msgEl).innerText = 'Validación: la fecha fin no puede ser menor a la inicial.';
    };
    $(txtIni).addEventListener('blur', f);
    $(txtFin).addEventListener('blur', f);
    $(txtIni).addEventListener('keydown', (ev)=>{ if(ev.key==='Enter'){ ev.preventDefault(); f(); }});
    $(txtFin).addEventListener('keydown', (ev)=>{ if(ev.key==='Enter'){ ev.preventDefault(); f(); }});
  }

  // =========================================================
  //  Empresa / Almacén (NO sesión)
  // =========================================================
  let EMPRESAS = [];
  let ALMACENES = [];

  function renderEmpresas(){
    const sel = $('cve_cia');
    const cur = sel.value;
    sel.innerHTML = '<option value="">Seleccione empresa</option>';
    EMPRESAS.forEach(e=>{
      const id = (e.cve_cia ?? e.id ?? '').toString().trim();
      const des = (e.des_cia ?? e.nombre ?? '').toString().trim();
      const clave = (e.clave_empresa ?? '').toString().trim();
      if(!id) return;
      const label = clave ? `(${id}) ${clave} - ${des}` : `(${id}) ${des}`;
      sel.add(new Option(label, id));
    });
    if (cur) sel.value = cur;
  }

  function renderAlmacenes(){
    const sel = $('cve_almac');
    const cia = $('cve_cia').value;
    const cur = sel.value;

    sel.innerHTML = '<option value="">Seleccione un almacén</option>';

    // Si el JSON trae cve_cia por almacén, filtramos. Si no, mostramos todos.
    let rows = ALMACENES.slice();
    const hasCia = rows.some(a => (a.cve_cia ?? a.id_empresa ?? a.IdEmpresa) != null);
    if (cia && hasCia){
      rows = rows.filter(a => String(a.cve_cia ?? a.id_empresa ?? a.IdEmpresa) === String(cia));
    }

    rows.forEach(a=>{
      const cve = (a.cve_almac ?? a.Cve_Almac ?? a.clave_almacen ?? a.id ?? '').toString().trim();
      const nom = (a.nombre ?? a.Nombre ?? a.des_almac ?? a.descripcion ?? '').toString().trim();
      if(!cve) return;
      sel.add(new Option(`(${cve}) ${nom}`, cve));
    });

    if (cur) sel.value = cur;
  }

  async function loadEmpresaAlmacen(){
    const j = await fetchJson(API_EMP_ALM_RUT);
    if (!j || j.ok !== true) throw new Error(j?.error || 'No se pudo cargar Empresa/Almacén');

    EMPRESAS = Array.isArray(j.empresas) ? j.empresas : [];
    ALMACENES = Array.isArray(j.almacenes) ? j.almacenes : [];

    renderEmpresas();
    renderAlmacenes();

    // querystring
    const urlParams = new URLSearchParams(window.location.search);
    const ciaParam = urlParams.get('cve_cia') || urlParams.get('empresa') || '';
    const almParam = urlParams.get('cve_almac') || urlParams.get('almacen') || urlParams.get('almacen_id') || '';

    if (ciaParam) $('cve_cia').value = ciaParam;
    renderAlmacenes();
    if (almParam) $('cve_almac').value = almParam;
  }

  $('cve_cia').addEventListener('change', ()=>{
    renderAlmacenes();
  });

  // =========================================================
  //  UX: bloques por tipo
  // =========================================================
  function setPeriodoAcumulada() {
    const tipo = $('acc_periodo_tipo').value;
    $('acc_relativo').style.display = (tipo === 'RELATIVO') ? 'block' : 'none';
    $('acc_fijo').style.display     = (tipo === 'FIJO') ? 'flex' : 'none';
  }

  function setBloquePorTipo() {
    const t = $('tipo_promo').value;
    const isUnidades  = (t === 'UNIDADES');
    const isTicket    = (t === 'TICKET');
    const isAcumulada = (t === 'ACUMULADA');

    $('bloque_unidades').style.display  = isUnidades ? 'block' : 'none';
    $('bloque_ticket').style.display    = isTicket ? 'block' : 'none';
    $('bloque_acumulada').style.display = isAcumulada ? 'block' : 'none';

    if (isAcumulada) setPeriodoAcumulada();
  }

  $('acc_periodo_tipo').addEventListener('change', setPeriodoAcumulada);
  $('tipo_promo').addEventListener('change', setBloquePorTipo);

  // =========================================================
  //  Autocomplete catálogos (igual que tu base)
  // =========================================================
  const LIVE_MIN_CHARS = 2;
  const LIVE_DEBOUNCE  = 220;
  const MAX_ROWS       = 25;

  const alt = [];
  const timers = {};
  const lastRows = { base: [], rw: [], alt: [] };

  function normalizeRows(j) {
    if (j && Array.isArray(j.rows)) return j.rows;
    if (j && Array.isArray(j.data)) return j.data;
    if (j && j.data && Array.isArray(j.data.rows)) return j.data.rows;
    return [];
  }

  function rowToPick(r, tipo) {
    let val = '', label = '';
    if (tipo === 'PRODUCTO') {
      val = (r.cve_articulo ?? r.Clave ?? r.clave ?? r.id ?? '').toString();
      const des = (r.des_articulo ?? r.Descripcion ?? r.descripcion ?? '').toString();
      label = des ? `${val} - ${des}` : `${val}`;
    } else {
      val = (r.cve_gpoart ?? r.Clave ?? r.clave ?? r.id ?? '').toString();
      const des = (r.des_gpoart ?? r.Descripcion ?? r.descripcion ?? '').toString();
      label = des ? `${val} - ${des}` : `${val}`;
    }
    return { val: (val||'').toUpperCase().trim(), label: (label||'').toUpperCase().trim() };
  }

  function renderList(scope, rows, tipo, onPick) {
    const box = $(scope + '_res');
    box.innerHTML = '';

    if (!rows.length) {
      box.innerHTML = '<div class="list-group-item small text-muted">Sin resultados</div>';
      return;
    }

    rows.slice(0, MAX_ROWS).forEach(r => {
      const pick = rowToPick(r, tipo);
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action';
      a.innerHTML = `<div class="small"><b>${escapeHtml(pick.val)}</b> <span class="text-muted">${escapeHtml(pick.label.replace(pick.val+' - ',''))}</span></div>`;
      a.addEventListener('click', () => onPick(pick));
      box.appendChild(a);
    });
  }

  function setSelected(scope, tipo, val, label) {
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
    if ($(scope + '_q')) { $(scope + '_q').value = ''; }
  }

  function clearSelected(scope) {
    $(scope + '_tipo_sel').value  = '';
    $(scope + '_val_sel').value   = '';
    $(scope + '_label_sel').value = '';
    $(scope + '_sel').innerHTML = '<span class="small text-muted">Sin selección</span>';
    if ($(scope + '_q')) { $(scope + '_q').value = ''; }
  }

  function toggleAlt() {
    const b = $('alt_box');
    b.style.display = (b.style.display === 'none') ? 'block' : 'none';
  }

  function addAlt(tipo, val, label) {
    if (alt.some(x => x.tipo === tipo && x.val === val)) return;
    alt.push({ tipo, val, label });
    renderAlt();
    $('alt_res').innerHTML = '';
    $('alt_q').value = '';
  }

  function delAlt(idx) {
    alt.splice(idx, 1);
    renderAlt();
  }

  function renderAlt() {
    const box = $('alt_list');
    if (!alt.length) {
      box.innerHTML = '<span class="small text-muted">Sin equivalentes</span>';
      return;
    }
    box.innerHTML = alt.map((x, i) => `
      <span class="ap-chip">
        <span class="text-muted">${x.tipo}</span>
        <b>${escapeHtml(x.label)}</b>
        <button type="button" title="Quitar" onclick="delAlt(${i})">&times;</button>
      </span>
    `).join('');
  }

  async function buscar(scope, allowAutoPick = false) {
    const modo = $(scope + '_modo') ? $(scope + '_modo').value : 'PRODUCTO';
    const q = ($(scope + '_q') ? $(scope + '_q').value : '').trim().toUpperCase();

    if (!q) {
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
    } catch (e) {
      console.error(e);
      $(scope + '_res').innerHTML = `<div class="list-group-item small text-danger">${escapeHtml(e.message)}</div>`;
      return;
    }

    if (j && (j.ok === 0 || j.ok === false || j.success === false)) {
      $(scope + '_res').innerHTML = `<div class="list-group-item small text-danger">${escapeHtml(j.msg || j.error || 'Error al consultar catálogo')}</div>`;
      return;
    }
    if (j && j.error) {
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

    if (exact) { onPick(rowToPick(exact, modo)); return; }
    if (allowAutoPick && rows.length === 1) { onPick(rowToPick(rows[0], modo)); return; }

    renderList(scope, rows, modo, onPick);
  }

  function bindLive(scope) {
    const input = $(scope + '_q');
    if (!input) return;

    input.addEventListener('input', () => {
      const v = input.value.trim();
      if (v.length < LIVE_MIN_CHARS) {
        $(scope + '_res').innerHTML = '';
        lastRows[scope] = [];
        return;
      }
      clearTimeout(timers[scope]);
      timers[scope] = setTimeout(() => buscar(scope, false), LIVE_DEBOUNCE);
    });

    input.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter') {
        ev.preventDefault();
        buscar(scope, true);
      }
      if (ev.key === 'Escape') {
        $(scope + '_res').innerHTML = '';
      }
    });
  }

  // =========================================================
  //  Guardado FULL (V2)
  // =========================================================
  async function postAPI(action, data) {
    const form = new FormData();
    form.append('action', action);

    Object.entries(data).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') form.append(k, v);
    });

    const j = await fetchJson(API_PROMO_V2, { method:'POST', body: form });
    // Tu API usa ok (según capturas)
    if (!j || j.ok !== 1) throw (j || { error:'Error desconocido' });
    return j;
  }

  function ensureRequired() {
    const cia = $('cve_cia').value;
    const alm = $('cve_almac').value;
    const nom = ($('promo_nombre').value || '').trim().toUpperCase();

    if (!cia) return 'Falta empresa';
    if (!alm) return 'Falta almacén';
    if (!nom) return 'Falta nombre de la promoción';

    if (!$('base_val_sel').value) return 'Falta producto base';
    if (!$('rw_val_sel').value) return 'Falta producto obsequio';
    if (!$('rw_qty').value) return 'Falta cantidad de obsequio';

    const tipo = $('tipo_promo').value;

    if (tipo === 'UNIDADES') {
      if (!$('th_qty').value) return 'Falta cantidad objetivo';
      const ini = $('vig_ini').value;
      const fin = $('vig_fin').value;
      if (!ini || !fin) return 'Falta vigencia (inicio/fin)';
      if (!validateRange(ini, fin)) return 'Vigencia inválida (fin < inicio)';
    }

    if (tipo === 'TICKET') {
      if (!$('ticket_monto').value) return 'Falta monto mínimo del ticket';
    }

    if (tipo === 'ACUMULADA') {
      if (!$('acc_monto').value) return 'Falta monto acumulado';
      if ($('acc_periodo_tipo').value === 'FIJO') {
        const ini = $('acc_ini').value;
        const fin = $('acc_fin').value;
        if (!ini || !fin) return 'Falta rango de fechas (acumulada)';
        if (!validateRange(ini, fin)) return 'Rango inválido (fin < inicio)';
      }
    }

    return '';
  }

  async function guardarPromoFull() {
    try {
      const err = ensureRequired();
      if (err) { showErrorToast(err); return; }

      // Payload V2 (todo en 1 transacción del API)

const payload = {
    id_empresa: $('cve_cia').value,   // 👈 API espera esto
    id_almacen: $('cve_almac').value, // 👈 recomendable alinear también
    cve_cia: $('cve_cia').value,      // lo dejamos por compatibilidad
    cve_almac: $('cve_almac').value,

        // Nombre (Descripción) - normalizado
        promo_nombre: ($('promo_nombre').value || '').trim().toUpperCase(),
	nombre: ($('promo_nombre').value || '').trim().toUpperCase(),        // 👈 lo que probablemente espera el API
	descripcion: ($('promo_nombre').value || '').trim().toUpperCase(),   // 👈 blindaje adicional


        tipo_promo: $('tipo_promo').value,

        // UNIDADES
        th_qty: $('th_qty').value || '',
        th_um: ($('th_um').value || '').toUpperCase(),

        // Vigencia UNIDADES (ISO)
        vig_ini: $('vig_ini').value || '',
        vig_fin: $('vig_fin').value || '',

        // TICKET
        ticket_monto: $('ticket_monto').value || '',

        // ACUMULADA
        acc_monto: $('acc_monto').value || '',
        acc_periodo_tipo: ($('acc_periodo_tipo').value || '').toUpperCase(),
        acc_periodo_rel:  ($('acc_periodo_rel').value || '').toUpperCase(),
        acc_ini: $('acc_ini').value || '',
        acc_fin: $('acc_fin').value || '',

        // Base
        base_tipo: ($('base_tipo_sel').value || '').toUpperCase(),
        base_val:  ($('base_val_sel').value  || '').toUpperCase(),
        base_label: ($('base_label_sel').value || '').toUpperCase(),

        // Reward
        rw_tipo: ($('rw_tipo_sel').value || '').toUpperCase(),
        rw_val:  ($('rw_val_sel').value  || '').toUpperCase(),
        rw_label: ($('rw_label_sel').value || '').toUpperCase(),
        rw_qty:  $('rw_qty').value || '',
        rw_um:   ($('rw_um').value || '').toUpperCase(),

        // Equivalentes JSON
        alt_json: JSON.stringify(alt || [])
      };

      // ✅ Guarda FULL
      const r = await postAPI('save_full', payload);

      // Mensaje corporativo con folio si viene
      const folio = (r.folio || r.lista || r.promo_folio || r.PromoId || '').toString().trim();
      const promoId = (r.id || r.promo_id || r.PromoId || '').toString().trim();

      const msg = folio
        ? `✅ Guardado OK. Folio: ${folio}`
        : (promoId ? `✅ Guardado OK. ID: ${promoId}` : '✅ Promoción guardada correctamente');

      showSuccessToast(msg, basePublic + '/sfa/promociones/promociones.php');

    } catch (e) {
      console.error(e);
      showErrorToast(e?.error || e?.msg || e?.message || 'Error inesperado al guardar');
    }
  }

  // =========================================================
  //  Init
  // =========================================================
  document.addEventListener('DOMContentLoaded', async () => {
    try {
      toUpperInputs();

      // binds fechas
      bindDatePair('vig_ini_txt','vig_ini','vig_fin_txt','vig_fin','vig_msg');
      bindDatePair('acc_ini_txt','acc_ini','acc_fin_txt','acc_fin','acc_msg');

      // catálogos
      bindLive('base');
      bindLive('rw');
      bindLive('alt');

      // estado inicial
      clearSelected('base');
      clearSelected('rw');
      renderAlt();
      setBloquePorTipo();
      setPeriodoAcumulada();

      // carga empresa/almacén
      await loadEmpresaAlmacen();

    } catch (e) {
      console.error(e);
      showErrorToast('No se pudo inicializar: ' + (e.message || e));
    }
  });

  // Expose
  window.buscar = buscar;
  window.toggleAlt = toggleAlt;
  window.delAlt = delAlt;
  window.clearSelected = clearSelected;
  window.guardarPromoFull = guardarPromoFull;
</script>

<!-- Toasts Bootstrap -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">

  <!-- Toast éxito -->
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastSuccessMsg">✅ Guardado OK</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
        data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>

  <!-- Toast error -->
  <div id="toastError" class="toast align-items-center text-bg-danger border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastErrorMsg">❌ Error</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
        data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>

</div>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
?>
