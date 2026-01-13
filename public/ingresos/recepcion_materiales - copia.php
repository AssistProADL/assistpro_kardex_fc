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

      <!-- ✅ CAMBIO DE ORDEN: PRIMERO OC, LUEGO PROVEEDOR (AUTO) -->
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
            <th>UOM</th>
            <th>Lote o Serie</th>
            <th>Caducidad</th>
            <th class="text-end">Cant. Solicitada</th>
            <th class="text-end">Cant. Recibida</th>
            <th class="text-end">Costo</th>
            <th>Contenedor</th>
            <th>LP Contenedor</th>
            <th>Pallet</th>
            <th>LP Pallet</th>
            <th style="width:60px;">Acciones</th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td><input id="usuario" class="form-control form-control-sm" value="Usuario" /></td>
            <td><input id="articulo" class="form-control form-control-sm" value="" /></td>
            <td><input id="descripcion" class="form-control form-control-sm" value="" /></td>
            <td><input id="uom" class="form-control form-control-sm" value="UM" /></td>
            <td><input id="lote" class="form-control form-control-sm" value="" /></td>
            <td><input id="caducidad" class="form-control form-control-sm" placeholder="dd/mm/aaaa" /></td>
            <td><input id="cant_sol" class="form-control form-control-sm text-end" value="0" /></td>
            <td><input id="cant_rec" class="form-control form-control-sm text-end" value="0" /></td>
            <td><input id="costo" class="form-control form-control-sm text-end" value="0.00" /></td>
            <td><input id="contenedor" class="form-control form-control-sm" value="" /></td>
            <td><input id="lp_contenedor" class="form-control form-control-sm" value="" /></td>
            <td><input id="pallet" class="form-control form-control-sm" value="Pallet" /></td>
            <td><input id="lp_pallet" class="form-control form-control-sm" value="" /></td>
            <td class="text-center"><button id="btnRecibir" class="btn btn-primary btn-sm">Recibir</button></td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- GRID INFERIOR: ESPERADOS OC -->
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
            <th>Estatus</th>
            <th>Usuario</th>
            <th>Artículo</th>
            <th>Lote</th>
            <th>Caducidad</th>
            <th class="text-end">Cant. Recibida</th>
            <th>Contenedor</th>
            <th>LP Contenedor</th>
            <th>Pallet</th>
            <th>LP Pallet</th>
            <th>Zona Recepción</th>
            <th>DateStamp</th>
            <th style="width:60px;">Acc.</th>
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

<script>
// ============================
// ✅ APIS
// ============================
const API_EMPRESAS   = '../api/empresas_api.php';
const API_FILTROS    = '../api/filtros_assistpro.php'; // ✅ aquí vive tubicacionesretencion -> zonas_recep
const API_OCS        = '../api/recepcion/recepcion_oc_api.php';
const API_OC_DETALLE = '../api/recepcion/recepcion_oc_detalle_api.php';
const API_RECEPCION  = '../api/recepcion/recepcion_api.php'; // BL destino (solo RL/CD)

function qs(id){ return document.getElementById(id); }
function val(id){ return (qs(id).value||'').trim(); }

async function fetchJson(url, opt){
  const r = await fetch(url, opt || {cache:'no-store'});
  const t = await r.text();
  try { return JSON.parse(t); }
  catch(e){ return { ok:0, error:'Respuesta no JSON', detail:t.slice(0,500) }; }
}

function getTipo(){
  return document.querySelector('input[name="tipo"]:checked')?.value || 'OC';
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
// Cache
// ============================
let CACHE_FILTROS = null;
let CACHE_OCS = [];

async function loadFiltros(){
  if(CACHE_FILTROS) return CACHE_FILTROS;
  CACHE_FILTROS = await fetchJson(API_FILTROS);
  return CACHE_FILTROS;
}

// ============================
// Regla negocio: OC NO usa destino/BL
// ============================
function applyTipoUI(){
  const tipo = getTipo();
  const isOC = (tipo === 'OC');

  qs('oc_folio').disabled = !isOC;

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
    // proveedor queda “auto”, pero para RL/CD no aplica; lo limpiamos
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
  const almacenes = (f.almacenes || []).map(a=>({
    cve_almac: a.cve_almac,
    clave_almacen: a.clave_almacen,
    des_almac: a.des_almac
  }));

  fillSelect(qs('almacen'), almacenes, {
    value:'clave_almacen', // ✅ clave usada por OC API (WH8, A42, etc.)
    text:'des_almac',
    dataset:{ cve:'cve_almac' }, // ✅ cve para filtrar zonas
    placeholder:{value:'',text:'[Seleccione un almacén]'}
  });

  if(qs('almacen').options.length > 1){
    qs('almacen').selectedIndex = 1;
    await onAlmacenChange();
  }
}

function getCveAlmacSeleccionado(){
  const sel = qs('almacen');
  const opt = sel.options[sel.selectedIndex];
  return opt?.dataset?.cve ? String(opt.dataset.cve) : '';
}
function getClaveAlmacenSeleccionado(){
  return val('almacen');
}

// ============================
// ✅ ZONA RECEPCIÓN = tubicacionesretencion
// filtros_assistpro.php -> data['zonas_recep']
// campos: cve_almacp as cve_almac, cve_ubicacion, desc_ubicacion as descripcion
// ============================
async function loadZonasPorAlmacen(){
  const cve = getCveAlmacSeleccionado();
  const f = await loadFiltros();

  // ✅ ESTA ES LA VALIDACIÓN QUE PEDISTE:
  // La vista está usando filtros_assistpro.php y toma ZONA RECEPCIÓN de tubicacionesretencion vía llave zonas_recep.
  const zonasRec = (f.zonas_recep || []).filter(z => String(z.cve_almac) === String(cve));

  fillSelect(qs('zona_recepcion'), zonasRec, {
    value:'cve_ubicacion',
    text:'descripcion',
    placeholder:{value:'',text:'Seleccione una Zona de Recepción'}
  });

  // Zonas destino (solo RL/CD) si existen en filtros
  const zonasAlmBase = (f.zonas_almacenaje || []);
  const zonasDest = zonasAlmBase.filter(z => String(z.cve_almac) === String(cve));

  fillSelect(qs('zona_destino'), zonasDest, {
    value:'cve_ubicacion',
    text:'descripcion',
    placeholder:{value:'',text:'Seleccione Zona destino'}
  });

  // reset BL
  fillSelect(qs('bl_destino'), [], {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
}

// ============================
// ✅ OCs: primero se elige OC, proveedor auto por OC
// OC label: "NUM_OC · PROVEEDOR" (+ factura opcional)
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

  // OCs con dataset para autopoblar proveedor
  const rows = CACHE_OCS.map(x=>({
    id_oc: String(x.id_oc),
    label: `${x.num_oc ?? x.id_oc} · ${x.proveedor ?? ''}${x.factura ? (' · ' + x.factura) : ''}`.trim(),
    id_proveedor: String(x.id_proveedor ?? ''),
    proveedor: String(x.proveedor ?? '')
  }));

  fillSelect(qs('oc_folio'), rows, {
    value:'id_oc',
    text:'label',
    dataset:{ prov_id:'id_proveedor', prov_nom:'proveedor' },
    placeholder:{value:'',text:'Seleccione una OC'}
  });

  // proveedor en blanco hasta que elijan OC
  fillSelect(qs('proveedor'), [], {value:'id', text:'text', placeholder:{value:'',text:'Seleccione una OC primero'}});
}

// Autollenado proveedor al elegir OC
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
// Detalle OC
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

  const tb = qs('tblOCDetalle').querySelector('tbody');
  tb.innerHTML = '';

  (j.data || []).forEach(r=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${r.id_det ?? ''}</td>
      <td>${r.cve_articulo ?? ''}</td>
      <td>${r.cve_lote ?? ''}</td>
      <td>${r.caducidad ?? ''}</td>
      <td class="text-end">${r.cantidad ?? 0}</td>
      <td class="text-end">${r.ingresado ?? 0}</td>
      <td>${r.num_orden ?? ''}</td>
      <td>${r.activo ?? ''}</td>
    `;
    tb.appendChild(tr);
  });

  qs('lblOCInfo').textContent = `OC ID: ${id_oc} · Líneas: ${(j.total ?? (j.data||[]).length)}`;
  qs('wrapOCDetalle').style.display = 'block';
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
// Operación: recibir y guardar (sin cambio)
/// ===========================
function addLineaFromInputs(){
  const tb = qs('tblRecibido').querySelector('tbody');
  const tr = document.createElement('tr');
  const now = new Date().toISOString().slice(0,19).replace('T',' ');
  tr.innerHTML = `
    <td><span class="badge bg-success">OK</span></td>
    <td>${(val('usuario')||'')}</td>
    <td>${(val('articulo')||'')}</td>
    <td>${(val('lote')||'')}</td>
    <td>${(val('caducidad')||'')}</td>
    <td class="text-end">${(val('cant_rec')||'0')}</td>
    <td>${(val('contenedor')||'')}</td>
    <td>${(val('lp_contenedor')||'')}</td>
    <td>${(val('pallet')||'')}</td>
    <td>${(val('lp_pallet')||'')}</td>
    <td>${(val('zona_recepcion')||'')}</td>
    <td>${now}</td>
    <td class="text-center"><button class="btn btn-danger btn-sm">🗑</button></td>
  `;
  tr.querySelector('button').addEventListener('click', ()=>tr.remove());
  tb.appendChild(tr);
}

async function onGuardar(){
  const tb = qs('tblRecibido').querySelector('tbody');
  if(!tb.children.length){ alert('No hay líneas recibidas'); return; }

  const tipo = getTipo();

  // proveedor_id viene del select (autollenado) o del dataset OC si hiciera falta
  let proveedor_id = val('proveedor');
  if(!proveedor_id){
    const opt = qs('oc_folio').options[qs('oc_folio').selectedIndex];
    proveedor_id = opt?.dataset?.prov_id ? String(opt.dataset.prov_id) : '';
  }

  const payload = {
    tipo,
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
    lineas: []
  };

  [...tb.querySelectorAll('tr')].forEach(tr=>{
    const tds = tr.querySelectorAll('td');
    payload.lineas.push({
      cve_articulo: tds[2].innerText.trim(),
      cve_lote: tds[3].innerText.trim(),
      caducidad: tds[4].innerText.trim(),
      cantidad: parseFloat(tds[5].innerText.trim()||'0'),
      contenedor: tds[6].innerText.trim(),
      lp_contenedor: tds[7].innerText.trim(),
      pallet: tds[8].innerText.trim(),
      lp_pallet: tds[9].innerText.trim()
    });
  });

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
  tb.innerHTML = '';
}

// ============================
// Eventos
// ============================
async function onTipoChange(){
  applyTipoUI();
  await loadOCsPorAlmacen();
  // zonas siempre se mantienen por almacén, pero destino/BL quedan bloqueados si OC
}

async function onAlmacenChange(){
  await loadZonasPorAlmacen();
  await loadOCsPorAlmacen();
  applyTipoUI();
}

document.addEventListener('DOMContentLoaded', async ()=>{
  await loadEmpresas();
  await loadAlmacenes();
  await loadZonasPorAlmacen();

  applyTipoUI();
  await loadOCsPorAlmacen();

  document.querySelectorAll('input[name="tipo"]').forEach(r=>r.addEventListener('change', onTipoChange));
  qs('almacen').addEventListener('change', onAlmacenChange);

  // ✅ OC primero -> proveedor auto + detalle
  qs('oc_folio').addEventListener('change', async ()=>{
    syncProveedorFromOC();
    await loadOCDetalleGrid();
  });

  qs('zona_destino').addEventListener('change', loadBLDestino);

  qs('btnAdd').addEventListener('click', (e)=>{ e.preventDefault(); });
  qs('btnRecibir').addEventListener('click', (e)=>{ e.preventDefault(); addLineaFromInputs(); });
  qs('btnGuardar').addEventListener('click', (e)=>{ e.preventDefault(); onGuardar(); });
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
