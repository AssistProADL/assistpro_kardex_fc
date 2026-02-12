<?php
// /public/ingresos/recepcion_materiales.php
include __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">

  <div class="card shadow-sm mt-2">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:#0F5AAD;color:#fff;">
      <div>
        <div class="fw-semibold">Recepción de Materiales</div>
        <div style="font-size:9px;opacity:.85;">Orden de Compra, Recepción Libre y Cross Docking</div>
      </div>
      <button class="btn btn-outline-light btn-sm" onclick="location.href='ingresos_admin.php'">Cerrar</button>
    </div>

    <div class="card-body">

      <div class="row g-2">
        <div class="col-12">
          <label class="form-label mb-0">Tipo</label><br>
          <label class="me-3"><input type="radio" name="tipo" value="OC" checked> Orden de Compra</label>
          <label class="me-3"><input type="radio" name="tipo" value="RL"> Recepción Libre</label>
          <label class="me-3"><input type="radio" name="tipo" value="CD"> Cross Docking</label>
        </div>
      </div>

      <hr class="my-2">

      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label mb-0">Empresa</label>
          <select id="empresa" class="form-select form-select-sm">
            <option value="">Seleccione</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Almacén</label>
          <select id="almacen" class="form-select form-select-sm">
            <option value="">[Seleccione un almacén]</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Zona de Recepción *</label>
          <select id="zona_recepcion" class="form-select form-select-sm">
            <option value="">Seleccione una Zona de Recepción</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label mb-0">Zona de Almacenaje destino</label>
          <select id="zona_destino" class="form-select form-select-sm">
            <option value="">Seleccione Zona destino</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label mb-0">BL destino</label>
          <select id="bl_destino" class="form-select form-select-sm">
            <option value="">Seleccione BL destino</option>
          </select>
        </div>
      </div>

      <hr class="my-2">

      <!-- OC primero, proveedor auto -->
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label mb-0">Número de Orden de Compra</label>
          <select id="oc_folio" class="form-select form-select-sm">
            <option value="">Seleccione una OC</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label mb-0">Proveedor (auto por OC)</label>
          <select id="proveedor" class="form-select form-select-sm" disabled>
            <option value="">Seleccione</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Folio de Recepción RL</label>
          <input id="folio_rl" class="form-control form-control-sm" placeholder="">
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Folio Recepción Cross Docking</label>
          <input id="folio_cd" class="form-control form-control-sm" placeholder="">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Factura / Remisión</label>
          <input id="factura" class="form-control form-control-sm" placeholder="">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Proyecto</label>
          <input id="proyecto" class="form-control form-control-sm" placeholder="Seleccione">
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex justify-content-end mb-2">
        <button id="btnAdd" class="btn btn-secondary btn-sm">+ Agregar Contenedor o Pallet</button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered" style="font-size:10px;">
          <thead class="table-light">
          <tr>
            <th>Usuario</th>
            <th>Artículo</th>
            <th>Descripción</th>
            <th>UM</th>
            <th>UM Primaria</th>
            <th>Pzas/Caja</th>
            <th class="text-end">Cant. Sol.</th>
            <th class="text-end">Cant. Rec.</th>
            <th class="text-center">Acciones</th>
</tr>
          </thead>
          <tbody>
  <tr>
    <td><input id="usuario" class="form-control form-control-sm" value="Usuario" /></td>
    <td>
      <input id="articulo" class="form-control form-control-sm" value="" list="dl_articulos" autocomplete="off" />
      
    </td>
    <td><input id="descripcion" class="form-control form-control-sm" value="" readonly /></td>
    <td><input id="uom" class="form-control form-control-sm" value="" readonly /></td>
    <td><input id="um_primaria" class="form-control form-control-sm" value="" readonly /></td>
    <td><input id="pzas_caja" class="form-control form-control-sm" value="" readonly /></td>
    <td><input id="cant_sol" class="form-control form-control-sm text-end" value="0" /></td>
    <td><input id="cant_rec" class="form-control form-control-sm text-end" value="0" /></td>
    <td class="text-center"><button id="btnRecibir" class="btn btn-primary btn-sm">Recibir</button></td>
  </tr>
  <tr>
    <td colspan="9">
      <div class="row g-2">
        <div class="col-md-2">
          <label class="form-label small mb-1">Lote / Serie</label>
          <input id="lote" class="form-control form-control-sm" value="" />
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Caducidad</label>
          <input id="caducidad" class="form-control form-control-sm" placeholder="dd/mm/aaaa" />
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Contenedor</label>
          <input id="contenedor" class="form-control form-control-sm" value="" />
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">LP Contenedor</label>
          <input id="lp_contenedor" class="form-control form-control-sm" value="" />
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Pallet</label>
          <input id="pallet" class="form-control form-control-sm" value="Pallet" />
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">LP Pallet</label>
          <input id="lp_pallet" class="form-control form-control-sm" value="" />
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Costo</label>
          <input id="costo" class="form-control form-control-sm text-end" value="0.00" />
        </div>
      </div>
    </td>
  </tr>
</tbody>
        </table>
      </div>

      <!-- ✅ GRID INFERIOR: ESPERADOS OC -->
      <div id="wrapOCDetalle" style="display:none;">
        <hr class="my-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <div class="fw-semibold">Productos esperados por la Orden de Compra</div>
          <div style="font-size:9px;opacity:.75;" id="lblOCInfo"></div>
        </div>
        <div class="table-responsive" style="max-height:240px; overflow:auto;">
          <table class="table table-sm table-bordered" id="tblOCDetalle" style="font-size:10px;">
            <thead class="table-light" style="position:sticky;top:0;z-index:2;">
            <tr>
              <th>ID Det</th>
              <th>Artículo</th>
              <th>Lote/Serie</th>
              <th>Caducidad</th>
              <th class="text-end">Cantidad</th>
              <th class="text-end">Ingresado</th>
              <th>Num Orden</th>
              <th>Activo</th>
            </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <hr class="my-3">

      <div class="table-responsive">
        <table class="table table-sm table-bordered" id="tblRecibido" style="font-size:10px;">
          <thead class="table-light">
          <tr>
            <th>Usuario</th>
            <th>Artículo</th>
            <th>Descripción</th>
            <th>UM</th>
            <th>UM Primaria</th>
            <th>Pzas/Caja</th>
            <th class="text-end">Cant. Sol.</th>
            <th class="text-end">Cant. Rec.</th>
            <th class="text-center">Acciones</th>
</tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="mt-2 d-flex gap-2">
        <button id="btnGuardar" class="btn btn-success btn-sm">Guardar</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="location.href='ingresos_admin.php'">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<!-- ✅ Autocomplete nativo -->
<datalist id="dl_articulos"></datalist>

<script>
// ============================
// ENDPOINTS
// ============================
const API_EMPRESAS   = '../api/empresas_api.php';
const API_FILTROS    = '../api/filtros_assistpro.php';         // zonas_recep (tubicacionesretencion) + almacenes
const API_OCS        = '../api/recepcion/recepcion_oc_api.php';
const API_OC_DETALLE = '../api/recepcion/recepcion_oc_detalle_api.php';
const API_RECEPCION  = '../api/recepcion/recepcion_api.php';  // BL destino, guardar
const API_ARTICULOS  = '../api/articulos_api.php';            // B_Lote  B_Caducidad desde c_articulo

function qs(id){ return document.getElementById(id); }
  // Safe getter: evita crash cuando el input no existe
  function val(id){
    const el = qs(id);
    if(!el) return '';
    return (el.value||'').trim();
  }

  // Numeric helper: convierte a numero seguro
  function num(v){
    if(v===null||v===undefined) return 0;
    const s = String(v).replace(/,/g,'').trim();
    const n = parseFloat(s);
    return isNaN(n)?0:n;
  }
  // Escape HTML para pintar en tablas (evita XSS y rompe layout)
  function esc(v){
    return String(v ?? '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;')
      .replace(/`/g,'&#96;');
  }

  // Formato numero amigable
  function fmtN(v, dec=0){
    const n = Number(v);
    if(!isFinite(n)) return '0';
    return n.toLocaleString('es-MX',{minimumFractionDigits:dec, maximumFractionDigits:dec});
  }


async function fetchJson(url, opt){
  const r = await fetch(url, opt || {cache:'no-store'});
  const t = await r.text();
  try { return JSON.parse(t); }
  catch(e){ return { ok:0, error:'Respuesta no JSON', detail:t.slice(0,700) }; }
}

function getTipo(){
  return document.querySelector('input[name="tipo"]:checked')?.value || 'OC';
}

// Alias por compatibilidad: algunas rutinas llaman tipoRecepcion()
function tipoRecepcion(){
  return getTipo();
}

function fillSelect(sel, rows, cfg){
  sel.innerHTML = '';
  const op0 = document.createElement('option');
  op0.value = cfg.placeholder?.value ?? '';
  op0.textContent = cfg.placeholder?.text ?? 'Seleccione';
  sel.appendChild(op0);

  (rows||[]).forEach(r=>{
    const o = document.createElement('option');
    o.value = (r[cfg.value] ?? '');
    o.textContent = (r[cfg.text] ?? '');
    if(cfg.dataset){
      Object.keys(cfg.dataset).forEach(k=>{
        o.dataset[k] = (r[cfg.dataset[k]] ?? '');
      });
    }
    sel.appendChild(o);
  });
}

// ============================
// CACHE
// ============================
let CACHE_FILTROS = null;
let CACHE_OCS = [];
let CACHE_OC_DET = []; // líneas OC esperadas (td_aduana)

// ============================
// STATE RECEPCION (tabla inferior)
// ============================
let RECIBIDOS = []; // fuente de verdad para tabla + guardado

function renderRecibidos(){
  const tb = qs('tblRecibido')?.querySelector('tbody');
  if(!tb) return;
  tb.innerHTML = '';

  RECIBIDOS.forEach((L, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${esc(L.usuario)}</td>
      <td>${esc(L.articulo)}</td>
      <td>${esc(L.descripcion)}</td>
      <td>${esc(L.um)}</td>
      <td>${esc(L.um_primaria)}</td>
      <td class="text-end">${fmtN(L.pzas_caja)}</td>
      <td class="text-end">${fmtN(L.cant_sol)}</td>
      <td class="text-end">${fmtN(L.cant_rec)}</td>
      <td>${esc(L.pallet || '')}</td>
      <td>${esc(L.bl_destino || '')}</td>
      <td>${esc(L.fec)}</td>
      <td class="text-center">
        <button class="btn btn-sm btn-danger" type="button" data-act="del" data-idx="${idx}">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;
    tb.appendChild(tr);
  });
}

function removeLinea(idx){
  if(idx < 0 || idx >= RECIBIDOS.length) return;
  RECIBIDOS.splice(idx,1);
  renderRecibidos();
}

async function loadFiltros(){
  if(CACHE_FILTROS) return CACHE_FILTROS;
  CACHE_FILTROS = await fetchJson(API_FILTROS);
  return CACHE_FILTROS;
}


let CACHE_ART_LAST = [];          // últimos resultados de búsqueda (datalist)
let CACHE_ART_LOOKUP = {};        // cache por cve_articulo -> data lookup
let ART_SEARCH_TIMER = null;

function buildDatalistArticulos(rows){
  const dl = qs('dl_articulos');
  if(!dl) return;
  dl.innerHTML = '';
  (rows || []).forEach(a=>{
    const o = document.createElement('option');
    o.value = String(a.cve_articulo ?? a.cve_articulo ?? '').trim();
    // soporta variantes de campos
    o.label = String(a.des_articulo ?? a.descripcion ?? a.des ?? '').trim();
    dl.appendChild(o);
  });
  qs('articulo').setAttribute('list','dl_articulos');
}

async function searchArticulos(term){
  const q = String(term||'').trim();
  if(q.length < 2){
    CACHE_ART_LAST = [];
    buildDatalistArticulos([]);
    return;
  }
  const u = new URL(API_ARTICULOS, window.location.href);
  u.searchParams.set('q', q);
  u.searchParams.set('limit', '20');
  const j = await fetchJson(u.toString());
  if(!j.ok){
    console.warn('Busqueda articulos_api', j);
    return;
  }
  CACHE_ART_LAST = j.data || [];
  buildDatalistArticulos(CACHE_ART_LAST);
}

async function lookupArticulo(cveArticulo){
  const k = String(cveArticulo||'').trim();
  if(!k) return null;
  if(CACHE_ART_LOOKUP[k]) return CACHE_ART_LOOKUP[k];

  const idp = getIdAlmacenSeleccionado(); // id numerico de almacén (c_almacenp.id)
  if(!idp){
    console.warn('lookupArticulo: falta almacén seleccionado');
  }

  const u = new URL(API_ARTICULOS, window.location.href);
  u.searchParams.set('cve_articulo', k);
  if(idp) u.searchParams.set('cve_almac', idp);

  const j = await fetchJson(u.toString());
  if(!j.ok){
    console.warn('lookupArticulo error', j);
    return null;
  }
  CACHE_ART_LOOKUP[k] = j.data || null;
  return CACHE_ART_LOOKUP[k];
}

function getExpectedForArticulo(cveArticulo){
  const k = String(cveArticulo||'').trim();
  if(!k || !Array.isArray(CACHE_OC_DET)) return null;

  const lines = CACHE_OC_DET.filter(x => String(x.cve_articulo||'').trim() === k);
  if(!lines.length) return null;

  const tot = lines.reduce((a,x)=>a + Number(x.cantidad||0), 0);
  const ing = lines.reduce((a,x)=>a + Number(x.ingresado||0), 0);
  let pend;
  // 'ingresado' en legacy a veces es FLAG (0/1) y no cantidad. Heurística:
  if(tot > 1 && ing >= 0 && ing <= 1){
    pend = tot; // tratar como no recibido aún
  } else {
    pend = Math.max(0, tot - ing);
  }

  // Si hay lote/caducidad, tomamos el primero no vacío (business rule simple)
  const lote = (lines.find(x=>String(x.cve_lote||'').trim())||{}).cve_lote || '';
  const cad  = (lines.find(x=>x.caducidad && !String(x.caducidad).startsWith('0000-00-00'))||{}).caducidad || '';

  return { total: tot, ingresado: ing, pendiente: pend, lote, caducidad: cad, lines };
}

// ============================
// REGLAS LOTE / CADUCIDAD
 // CADUCIDAD
// ============================

function applyArticuloRulesFromMeta(meta){
  const inpLote = qs('lote');
  const inpCad  = qs('caducidad');

  if(!meta){
    inpLote.disabled = false;
    inpCad.disabled  = false;
    if(!inpCad.placeholder) inpCad.placeholder = 'dd/mm/aaaa';
    return;
  }

  // compatibilidad: legacy / nuevo
  const pideLote = (Number(meta.B_Lote||0) === 1) || (Number(meta.B_Serie||0) === 1) ||
                   (String(meta.control_lotes||'').toUpperCase() === 'S') ||
                   (String(meta.control_numero_series||'').toUpperCase() === 'S');

  const pideCad  = (Number(meta.B_Caducidad||0) === 1) ||
                   (String(meta.Caduca||'').toUpperCase() === 'S') ||
                   (String(meta.control_caducidad||'').toUpperCase() === 'S');

  // Lote/Serie
  inpLote.disabled = !pideLote;
  if(inpLote.disabled) inpLote.value = '';

  // Caducidad
  inpCad.disabled = !pideCad;
  if(inpCad.disabled){
    inpCad.value = '';
    inpCad.placeholder = '';
  }else{
    inpCad.placeholder = 'dd/mm/aaaa';
  }

  if(!pideLote && !pideCad){
    inpLote.value = '';
    inpCad.value = '';
  }
}

async function applyArticuloToInputs(cveArticulo){
  const k = String(cveArticulo||'').trim();
  if(!k) return;

  // 1) Esperado por OC (si aplica)
  const exp = getExpectedForArticulo(k);
  if(exp){
    qs('cant_sol').value = String(exp.total);
    // si el usuario aún no capturó recibida, precargamos pendiente
    if(!val('cant_rec') || Number(val('cant_rec')) === 0){
      // Por OC: por default recibida = solicitada (editable)
      qs('cant_rec').value = String(exp.total);
    }
    // precarga lote/cad si la regla lo permite (se aplica luego con meta)
    if(exp.lote && !qs('lote').disabled) qs('lote').value = exp.lote;
    if(exp.caducidad && !qs('caducidad').disabled) qs('caducidad').value = exp.caducidad;
  }

  // 2) Lookup artículo (desc + UM + reglas)
  const meta = await lookupArticulo(k);
  if(meta){
    qs('descripcion').value = meta.des_articulo || meta.descripcion || '';
    qs('uom').value = meta.unidadMedida_nombre || meta.UM || qs('uom').value;
    if(qs('um_primaria')) qs('um_primaria').value = meta.unidadMedida_nombre || '';
    if(qs('pzas_caja'))   qs('pzas_caja').value   = (meta.num_multiplo!=null? String(meta.num_multiplo):'');
  }

  applyArticuloRulesFromMeta(meta || (CACHE_ART_LAST.find(x=>String(x.cve_articulo||'').trim()===k) || null));
}

// ============================
// UI: tipo OC no usa destino/BL
// ============================
function applyTipoUI(){
  const tipo = getTipo();
  const isOC = (tipo === 'OC');

  qs('oc_folio').disabled = !isOC;

  // Si es OC, deshabilitar folios manuales (RL/CD)
  if(qs('folio_rl')) qs('folio_rl').disabled = isOC;
  if(qs('folio_cd')) qs('folio_cd').disabled = isOC;

  qs('zona_destino').disabled = isOC;
  qs('bl_destino').disabled   = isOC;

  if(isOC){
    qs('zona_destino').value = '';
    qs('bl_destino').value = '';
  }else{
    qs('wrapOCDetalle').style.display = 'none';
    qs('tblOCDetalle').querySelector('tbody').innerHTML = '';
    qs('lblOCInfo').textContent = '';
    qs('oc_folio').value = '';
    fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'N/A'}});
  }
}

// ============================
// Empresas
// ============================
async function loadEmpresas(){
  const j = await fetchJson(API_EMPRESAS);
  if(!j || !j.ok){
    alert((j && (j.error||j.msg)) ? (j.error||j.msg) : 'Error cargando empresas');
    return;
  }
  fillSelect(qs('empresa'), (j.data||[]), {
    value:'cve_cia',
    text:'des_cia',
    placeholder:{value:'',text:'Seleccione'}
  });
}

// ============================
// Almacenes desde filtros_assistpro
// ============================
async function loadAlmacenes(){
  const f = await loadFiltros();

  // estandar: value = id numerico (c_almacenp.id)
  // display = clave (wh8) o descripcion
  const almacenes = (f.almacenes || []).map(a=>{
    // filtros_assistpro expone: idp (numerico) + clave (wh8)
    // value debe ser idp para cruzar contra tubicacionesretencion.cve_almacp
    const id = (a.idp ?? a.id ?? a.almacen_id ?? '');
    const clave = (a.clave_almacen ?? a.cve_almac ?? a.clave ?? a.des_almac ?? '');
    const nombre = (a.nombre ?? '');
    const text  = (nombre ? (`${clave} - ${nombre}`) : String(clave));
    return {
      cve_almac: String(id),
      clave_almacen: String(clave),
      des_almac: String(text)
    };
  });

  fillSelect(qs('almacen'), almacenes, {
    value:'cve_almac',        // ej: 8
    text:'des_almac',         // ej: WH8 o nombre visible
    dataset:{ clave:'clave_almacen' }, // guarda clave (WH8)
    placeholder:{value:'',text:'[Seleccione un almacén]'}
  });

  if(qs('almacen').options.length > 1){
    qs('almacen').selectedIndex = 1;
    await onAlmacenChange();
  }
}

function getClaveAlmacenSeleccionado(){
  // ahora el value es el id numerico, la clave va en dataset
  const sel = qs('almacen');
  const opt = sel.options[sel.selectedIndex];
  return opt?.dataset?.clave ? String(opt.dataset.clave) : '';
}
function getCveAlmacSeleccionado(){
  return val('almacen'); // id numerico como string
}


// Alias legacy/compat: algunas rutinas esperan este helper
function getIdAlmacenSeleccionado(){
  return getCveAlmacSeleccionado();
}

// ============================
// ✅ ZONAS RECEPCIÓN (tubicacionesretencion)
// filtros_assistpro.php -> zonas_recep
// FIX: match robusto para ligar con almacén seleccionado
// ============================
async function loadZonasPorAlmacen(){
  const f = await loadFiltros();

  const almacen_id = parseInt(getCveAlmacSeleccionado() || '0', 10); // c_almacenp.id
  if(!almacen_id){
    fillSelect(qs('zona_recepcion'), [], {
      value:'cve_ubicacion',
      text:'descripcion',
      placeholder:{value:'',text:'Seleccione una Zona de Recepción'}
    });
    return;
  }

  // soporta ambos nombres por compatibilidad: zonas_recep / zonas_recepcion
  const zr = (f.zonas_recep || f.zonas_recepcion || []);

  // normaliza: en filtros viene como cve_almac = tubicacionesretencion.cve_almacp (int)
  const zonasRec = zr.filter(z => parseInt(z.cve_almac ?? z.cve_almacp ?? z.almacen_id ?? '0', 10) === almacen_id);

  fillSelect(qs('zona_recepcion'), zonasRec, {
    value:'cve_ubicacion',
    text:'descripcion',
    placeholder:{value:'',text:'Seleccione una Zona de Recepción'}
  });

  // zonas destino (rl/cd) - si existe el catalogo en filtros
  const za = (f.zonas_almacenaje || []);
  const zonasDest = za.filter(z => parseInt(z.cve_almac ?? z.cve_almacp ?? z.almacen_id ?? '0', 10) === almacen_id);

  fillSelect(qs('zona_destino'), zonasDest, {
    value:'cve_ubicacion',
    text:'descripcion',
    placeholder:{value:'',text:'Seleccione Zona destino'}
  });

  // bl destino depende de zona destino
  fillSelect(qs('bl_destino'), [], {
    value:'bl',
    text:'bl',
    placeholder:{value:'',text:'Seleccione BL destino'}
  });
}

// ============================
// OCs por almacén / proveedor auto
// ============================
async function loadOCsPorAlmacen(){
  const tipo = getTipo();
  if(tipo !== 'OC'){
    CACHE_OCS = [];
    fillSelect(qs('oc_folio'), [], {value:'id_oc', text:'label', placeholder:{value:'',text:'N/A'}});
    fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'N/A'}});
    return;
  }

  const almClave = getClaveAlmacenSeleccionado();
  if(!almClave){
    CACHE_OCS = [];
    fillSelect(qs('oc_folio'), [], {value:'id_oc', text:'label', placeholder:{value:'',text:'Seleccione una OC'}});
    fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'Seleccione'}});
    return;
  }

  const u = new URL(API_OCS, window.location.href);
  u.searchParams.set('almacen', almClave);

  const j = await fetchJson(u.toString());
  if(!j.ok){
    console.warn('Error OCs', j);
    CACHE_OCS = [];
    fillSelect(qs('oc_folio'), [], {value:'id_oc', text:'label', placeholder:{value:'',text:'Seleccione una OC'}});
    fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'Seleccione'}});
    return;
  }

  CACHE_OCS = j.data || [];

  const rows = CACHE_OCS.map(x=>({
    // ID técnico real del movimiento / OC
    id_oc: String(x.ID_Aduana),

    // Folio visible (estándar nuevo)
    label: `${x.folio_mov ?? x.Pedimento ?? x.ID_Aduana}`,

    // Proveedor
    id_proveedor: String(x.ID_Provedor ?? ''),
    proveedor: String(x.Nombre ?? '')
  }));

  fillSelect(qs('oc_folio'), rows, {
    value:'id_oc',
    text:'label',
    dataset:{ prov_id:'id_proveedor', prov_nom:'proveedor' },
    placeholder:{value:'',text:'Seleccione una OC'}
  });

  fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'Seleccione una OC primero'}});
}

function syncProveedorFromOC(){
  const sel = qs('oc_folio');
  const opt = sel.options[sel.selectedIndex];
  const pid = opt?.dataset?.prov_id ? String(opt.dataset.prov_id) : '';
  const pnom = opt?.dataset?.prov_nom ? String(opt.dataset.prov_nom) : '';

  if(!pid && !pnom){
    fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'Seleccione una OC primero'}});
    return;
  }

  fillSelect(qs('proveedor'), [{id: pid, text: pnom}], {
    value:'id',
    text:'text',
    placeholder:{value:'',text:'Proveedor'}
  });
  qs('proveedor').value = pid || '';
}

// ============================
// Detalle OC + CLICK para pasar a recepción (Opción 2)
// ============================
async function loadOCDetalleGrid(){
  const tipo = getTipo();
  if(tipo !== 'OC') return;

  const id_oc = val('oc_folio');
  if(!id_oc){
    qs('wrapOCDetalle').style.display = 'none';
    qs('tblOCDetalle').querySelector('tbody').innerHTML = '';
    qs('lblOCInfo').textContent = '';
    return;
  }

  const u = new URL(API_OC_DETALLE, window.location.href);
  u.searchParams.set('id_oc', id_oc);

  const j = await fetchJson(u.toString());
  if(!j.ok){
    alert(j.error || 'Error cargando detalle OC');
    return;
  }

  CACHE_OC_DET = j.data || [];

  const tb = qs('tblOCDetalle').querySelector('tbody');
  tb.innerHTML = '';

  (CACHE_OC_DET || []).forEach(r=>{
    const tr = document.createElement('tr');
    tr.style.cursor = 'pointer';
    tr.title = 'Click para pasar a recepción';

    const cad = (r.caducidad && !String(r.caducidad).startsWith('0000-00-00')) ? String(r.caducidad) : '';

    tr.innerHTML = `
      <td>${r.id_det ?? ''}</td>
      <td>${r.cve_articulo ?? ''}</td>
      <td>${r.cve_lote ?? ''}</td>
      <td>${cad}</td>
      <td class="text-end">${r.cantidad ?? 0}</td>
      <td class="text-end">${r.ingresado ?? 0}</td>
      <td>${r.num_orden ?? ''}</td>
      <td>${r.activo ?? ''}</td>
    `;

    tr.addEventListener('click', ()=>{
      const art = String(r.cve_articulo ?? '').trim();
      if(!art) return;

      // set articulo + desc/uom desde c_articulo
      qs('articulo').value = art;
      applyArticuloToInputs(art);

      // pendiente = cantidad - ingresado
      const cant = Number(r.cantidad ?? 0);
      const ing  = Number(r.ingresado ?? 0);
      const pend = Math.max(0, cant - ing);

      qs('cant_sol').value = String(cant);
      qs('cant_rec').value = String(pend);

      // caducidad solo si aplica
      if(cad && !qs('caducidad').disabled) qs('caducidad').value = cad;

      // foco operativo
      qs('cant_rec').focus();
    });

    tb.appendChild(tr);
  });

  qs('lblOCInfo').textContent = `OC ID: ${id_oc} · Líneas: ${(j.total ?? (j.data||[]).length)}`;
  qs('wrapOCDetalle').style.display = 'block';
}

function renderOCDetalleFromCache(){
    const tbody = qs('oc_tbody');
    if(!tbody) return;
    tbody.innerHTML = '';
    for(const r of (CACHE_OC_DET||[])){
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${esc(r.id_det||'')}</td>
          <td>${esc(r.cve_articulo||'')}</td>
          <td>${esc(r.descripcion||'')}</td>
          <td>${esc(r.um_base||'')}</td>
          <td>${esc(r.um_empaque||'-')}</td>
          <td class="text-end">${num(r.factor||0)}</td>
          <td class="text-end">${num(r.cantidad||0)}</td>
          <td class="text-end">${num(r.ingresado||0)}</td>
          <td class="text-end">${num(r.pendiente||0)}</td>
          <td>${esc(r.num_orden||'')}</td>
          <td class="text-center">${(r.activo==1||r.activo==='1')?'1':'0'}</td>
        `;
        tr.addEventListener('click', ()=> preloadFromOCRow(r));
        tbody.appendChild(tr);
    }
}


// ============================
// BL destino (solo RL/CD)
// ============================
async function loadBLDestino(){
  const tipo = getTipo();
  if(tipo === 'OC') return;

  const almClave = getClaveAlmacenSeleccionado();
  const zonaDestino = val('zona_destino');

  if(!almClave || !zonaDestino){
    fillSelect(qs('bl_destino'), [], {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
    return;
  }

  const u = new URL(API_RECEPCION, window.location.href);
  u.searchParams.set('action','bl_destino');
  u.searchParams.set('almacen', almClave);
  u.searchParams.set('zona', zonaDestino);

  const j = await fetchJson(u.toString());
  if(!j.ok){
    alert(j.error || 'Error cargando BL destino');
    return;
  }
  fillSelect(qs('bl_destino'), (j.data||[]), {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
}

// ============================
// Operación: Recibir / Guardar
// ============================
function addLineaFromInputs(){
  const L = {
    usuario: (val('usuario')||'').trim(),
    cve_articulo: (val('articulo')||'').trim(),
    descripcion: (val('descripcion')||'').trim(),
    // El input real en la UI es #uom (columna UM)
    um: (val('uom')||'').trim(),
    um_primaria: (val('um_primaria')||'').trim(),
    pzas_caja: num(val('pzas_caja')),
    cant_sol: num(val('cant_sol')),
    cant_rec: num(val('cant_rec')),
    cantidad: num(val('cant_rec')),
    cve_lote: (val('lote')||'').trim(),
    caducidad: (val('caducidad')||'').trim(),
    contenedor: (val('contenedor')||'').trim(),
    lp_contenedor: (val('lp_contenedor')||'').trim(),
    pallet: (val('pallet')||'').trim(),
    lp_pallet: (val('lp_pallet')||'').trim(),
  };

  // regla básica: no aceptar líneas sin artículo o sin cantidad
  if(!L.cve_articulo){ alert('Captura un artículo'); return; }
  if(L.cantidad<=0){ alert('Cantidad a recibir debe ser mayor a 0'); return; }

  RECIBIDOS.push(L);
    // Actualiza cache de OC detalle (solo UI) para reflejar Ingresado/Pendiente
    if(tipoRecepcion()==='OC' && (CACHE_OC_DET||[]).length){
        const keyArt=(L.cve_articulo||'').toUpperCase();
        const keyLote=(L.cve_lote||'').toUpperCase();
        for(const r of CACHE_OC_DET){
            if(String(r.cve_articulo||'').toUpperCase()!==keyArt) continue;
            if(keyLote && String(r.lote||'').toUpperCase() && String(r.lote||'').toUpperCase()!==keyLote) continue;
            const ing=Number(r.ingresado||0)+Number(L.cant_rec||0);
            const cant=Number(r.cantidad||0);
            r.ingresado = (cant && ing>cant)?cant:ing;
            r.pendiente = (cant? Math.max(0, cant-Number(r.ingresado||0)) : r.pendiente);
            break;
        }
        renderOCDetalleFromCache();
    }
    renderRecibidos();
}


async function onGuardar(){
  if(!RECIBIDOS.length){ alert('No hay líneas recibidas'); return; }

  const tipo = getTipo();

  let proveedor_id = val('proveedor');
  if(!proveedor_id){
    const opt = qs('oc_folio').options[qs('oc_folio').selectedIndex];
    proveedor_id = opt?.dataset?.prov_id ? String(opt.dataset.prov_id) : '';
  }

  const payload = {
    tipo,
	  // Migración: num_pedimento/folio_oc -> folio_mov. Mantener contrato sin tocar UI.
	  // Mandamos NULL por default para que el backend genere el folio de movimiento.
	  folio_mov: null,
    empresa: val('empresa'),
    almacen: getClaveAlmacenSeleccionado(),
    zona_recepcion: val('zona_recepcion'),
    zona_destino: (tipo==='OC' ? '' : val('zona_destino')),
    bl_destino:   (tipo==='OC' ? '' : val('bl_destino')),
    proveedor: proveedor_id,
    oc_folio: val('oc_folio'),
    folio_rl: val('folio_rl'),
    folio_cd: val('folio_cd'),
    factura: val('factura'),
    proyecto: val('proyecto'),
    lineas: RECIBIDOS.map(L => ({
      usuario: L.usuario,
      cve_articulo: L.cve_articulo,
      descripcion: L.descripcion,
      um: L.um,
      um_primaria: L.um_primaria,
      pzas_caja: L.pzas_caja,
      cant_sol: L.cant_sol,
      cant_rec: (L.cant_rec ?? L.cantidad ?? 0),
      cantidad: (L.cant_rec ?? L.cantidad ?? 0),
      cve_lote: L.cve_lote,
      caducidad: L.caducidad,
      contenedor: L.contenedor,
      lp_contenedor: L.lp_contenedor,
      pallet: L.pallet,
      lp_pallet: L.lp_pallet
    }))
  };

  const u = new URL(API_RECEPCION, window.location.href);
  u.searchParams.set('action','guardar_recepcion');

  const j = await fetchJson(u.toString(), {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });

  if(!j.ok){
    alert((j.error||'Error') + (j.detail?(' | '+j.detail):'') + (j.msg?(' | '+j.msg):''));
    return;
  }
  alert('Recepción guardada correctamente');
  RECIBIDOS = [];
  renderRecibidos();
}

// ============================
// Eventos
// ============================
async function onTipoChange(){
  applyTipoUI();
  await loadOCsPorAlmacen();
}

async function onAlmacenChange(){
  qs('zona_recepcion').value = '';
  qs('zona_destino').value = '';
  fillSelect(qs('bl_destino'), [], {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
  await loadZonasPorAlmacen();
  await loadOCsPorAlmacen();
  applyTipoUI();
}

document.addEventListener('DOMContentLoaded', async ()=>{
  // carga maestros
  await loadEmpresas();
  await loadFiltros();
  // Artículos: búsqueda incremental + lookup puntual

  await loadAlmacenes();
  await loadZonasPorAlmacen();

  applyTipoUI();
  await loadOCsPorAlmacen();

  // listeners
  document.querySelectorAll('input[name="tipo"]').forEach(r=>r.addEventListener('change', onTipoChange));
  qs('almacen').addEventListener('change', onAlmacenChange);

  qs('oc_folio').addEventListener('change', async ()=>{
    syncProveedorFromOC();
    await loadOCDetalleGrid();
  });

  // Artículo: búsqueda incremental (datalist) + autollenado
  qs('articulo').addEventListener('input', (e)=>{
    clearTimeout(ART_SEARCH_TIMER);
    ART_SEARCH_TIMER = setTimeout(()=> searchArticulos(e.target.value), 180);
  });
  qs('articulo').addEventListener('change', ()=> applyArticuloToInputs(val('articulo')));
  qs('articulo').addEventListener('blur',   ()=> applyArticuloToInputs(val('articulo')));
  qs('articulo').addEventListener('keydown', (ev)=>{
    if(ev.key === 'Enter'){
      ev.preventDefault();
      applyArticuloToInputs(val('articulo'));
      qs('cant_rec').focus();
    }
  });


  qs('zona_destino').addEventListener('change', loadBLDestino);

  // autocomplete / reglas de articulo
  qs('articulo').addEventListener('change', ()=>{
    const art = val('articulo');
    applyArticuloToInputs(art);
  });

  qs('btnAdd').addEventListener('click', (e)=>{ e.preventDefault(); });
  qs('btnRecibir').addEventListener('click', (e)=>{ e.preventDefault(); addLineaFromInputs(); });
  qs('btnGuardar').addEventListener('click', (e)=>{ e.preventDefault(); onGuardar(); });

  // eliminar líneas en tabla inferior
  qs('tblRecibido')?.addEventListener('click', (e)=>{
    const btn = e.target.closest('button[data-act="del"]');
    if(!btn) return;
    const idx = parseInt(btn.dataset.idx || '-1', 10);
    removeLinea(idx);
  });
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>