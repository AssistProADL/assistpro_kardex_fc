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

      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label mb-0">Proveedor</label>
          <select id="proveedor" class="form-select form-select-sm">
            <option value="">Seleccione</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Número de Orden de Compra</label>
          <select id="oc_folio" class="form-select form-select-sm">
            <option value="">Seleccione una OC</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Folio de Recepción RL</label>
          <input id="folio_rl" class="form-control form-control-sm" placeholder="">
        </div>

        <div class="col-md-6">
          <label class="form-label mb-0">Folio Recepción Cross Docking</label>
          <input id="folio_cd" class="form-control form-control-sm" placeholder="">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-0">Factura / Remisión</label>
          <input id="factura" class="form-control form-control-sm" placeholder="">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-0">Proyecto</label>
          <input id="proyecto" class="form-control form-control-sm" placeholder="Seleccione">
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex justify-content-end mb-2">
        <button id="btnAdd" class="btn btn-secondary btn-sm">
          + Agregar Contenedor o Pallet
        </button>
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
            <td class="text-center">
              <button id="btnRecibir" class="btn btn-primary btn-sm">Recibir</button>
            </td>
          </tr>
          </tbody>
        </table>
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
// ✅ RUTAS REALES (según tu estructura /public/api/recepcion/*)
const API = '../api/recepcion/recepcion_api.php';
const API_EMPRESAS = '../api/empresas_api.php';

function qs(id){ return document.getElementById(id); }
function val(id){ return (qs(id).value||'').trim(); }

async function apiGet(action, params={}){
  const u = new URL(API, window.location.href);
  u.searchParams.set('action', action);
  Object.keys(params).forEach(k=>u.searchParams.set(k, params[k]));
  const r = await fetch(u.toString());
  return await r.json();
}
async function apiPost(action, payload){
  const r = await fetch(API+'?action='+encodeURIComponent(action), {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  return await r.json();
}

function fillSelect(sel, rows, map){
  sel.innerHTML = '';
  if(map?.placeholder){
    const o=document.createElement('option');
    o.value=map.placeholder.value; o.textContent=map.placeholder.text;
    sel.appendChild(o);
  }
  (rows||[]).forEach(x=>{
    const o=document.createElement('option');
    o.value = (x[map.value] ?? '');
    o.textContent = (x[map.text] ?? '');
    sel.appendChild(o);
  });
}

async function loadEmpresas(){
  // empresas_api.php debe responder JSON. Si responde HTML (404/login) te dará "token <"
  const u = new URL(API_EMPRESAS, window.location.href);
  u.searchParams.set('action','list');
  const r = await fetch(u.toString());
  const j = await r.json();
  if(!j.ok) return alert(j.error||'Error cargando empresas');
  fillSelect(qs('empresa'), j.data, {value:'id', text:'nombre', placeholder:{value:'',text:'Seleccione'}});
}

async function loadAlmacenes(){
  const j = await apiGet('almacenes');
  if(!j.ok) return alert(j.error||'Error');
  fillSelect(qs('almacen'), j.data, {value:'id', text:'nombre', placeholder:{value:'',text:'[Seleccione un almacén]'}});
  if(qs('almacen').options.length>1){
    qs('almacen').selectedIndex = 1;
    await onAlmacenChange();
  }
}

async function loadProveedores(){
  const j = await apiGet('proveedores');
  if(!j.ok) return;
  fillSelect(qs('proveedor'), j.data, {value:'id', text:'nombre', placeholder:{value:'',text:'Seleccione'}});
}

function getTipo(){
  return document.querySelector('input[name="tipo"]:checked')?.value || 'OC';
}

async function loadZonas(){
  const alm = val('almacen');
  if(!alm){
    fillSelect(qs('zona_recepcion'), [], {value:'zona',text:'zona', placeholder:{value:'',text:'Seleccione una Zona de Recepción'}});
    fillSelect(qs('zona_destino'), [], {value:'zona',text:'zona', placeholder:{value:'',text:'Seleccione Zona destino'}});
    return;
  }
  const j = await apiGet('zonas', {almacen: alm});
  if(!j.ok) return alert(j.error||'Error');
  fillSelect(qs('zona_recepcion'), j.data, {value:'zona', text:'zona', placeholder:{value:'',text:'Seleccione una Zona de Recepción'}});
  fillSelect(qs('zona_destino'), j.data, {value:'zona', text:'zona', placeholder:{value:'',text:'Seleccione Zona destino'}});
}

async function loadBL(){
  const alm = val('almacen');
  const zona = val('zona_destino');
  if(!alm || !zona){
    fillSelect(qs('bl_destino'), [], {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
    return;
  }
  const j = await apiGet('bl', {almacen: alm, zona: zona});
  if(!j.ok) return alert(j.error||'Error');
  fillSelect(qs('bl_destino'), j.data, {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
}

async function loadOCs(){
  const tipo = getTipo();
  const alm = val('almacen');
  const prov = val('proveedor');
  qs('oc_folio').disabled = (tipo!=='OC');

  if(tipo!=='OC'){
    fillSelect(qs('oc_folio'), [], {value:'folio',text:'folio', placeholder:{value:'',text:'N/A'}});
    return;
  }
  const j = await apiGet('ocs', {almacen: alm, proveedor: prov});
  if(!j.ok) return alert(j.error||'Error');
  fillSelect(qs('oc_folio'), j.data, {value:'folio', text:'folio', placeholder:{value:'',text:'Seleccione una OC'}});
}

async function onOCChange(){
  const folio = val('oc_folio');
  if(!folio) return;
  const j = await apiGet('oc_detalle', {folio});
  if(!j.ok) return alert(j.error||'Error');
  // si tu API devuelve proveedor, se puede setear aquí (opcional)
}

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

  const payload = {
    tipo: getTipo(),
    empresa: val('empresa'),
    almacen: val('almacen'),
    zona_recepcion: val('zona_recepcion'),
    zona_destino: val('zona_destino'),
    bl_destino: val('bl_destino'),
    proveedor: val('proveedor'),
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

  const j = await apiPost('guardar_recepcion', payload);
  if(!j.ok){ alert((j.error||'Error') + (j.detail?(' | '+j.detail):'')); return; }
  alert('Recepción guardada correctamente');
  tb.innerHTML = '';
}

async function onTipoChange(){ await loadOCs(); }
async function onAlmacenChange(){ await loadZonas(); await loadOCs(); }

document.addEventListener('DOMContentLoaded', async ()=>{
  await loadEmpresas();
  await loadAlmacenes();
  await loadProveedores();

  document.querySelectorAll('input[name="tipo"]').forEach(r=>r.addEventListener('change', onTipoChange));
  qs('almacen').addEventListener('change', onAlmacenChange);
  qs('zona_destino').addEventListener('change', loadBL);
  qs('proveedor').addEventListener('change', loadOCs);
  qs('oc_folio').addEventListener('change', onOCChange);

  qs('btnAdd').addEventListener('click', (e)=>{ e.preventDefault(); /* placeholder modal */ });
  qs('btnRecibir').addEventListener('click', (e)=>{ e.preventDefault(); addLineaFromInputs(); });
  qs('btnGuardar').addEventListener('click', (e)=>{ e.preventDefault(); onGuardar(); });
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
