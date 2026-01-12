 
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
            <option value="">Seleccione</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-0">Zona de Recepción *</label>
          <select id="zona_recepcion" class="form-select form-select-sm">
            <option value="">Seleccione una Zona de Recepción</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Zona de Almacenaje destino</label>
          <select id="zona_destino" class="form-select form-select-sm">
            <option value="">Seleccione Zona destino</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-0">BL destino</label>
          <select id="bl_destino" class="form-select form-select-sm">
            <option value="">Seleccione BL destino</option>
          </select>
        </div>

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
          <input id="folio_rl" class="form-control form-control-sm" />
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Folio Recepción Cross Docking</label>
          <input id="folio_cd" class="form-control form-control-sm" />
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Factura / Remisión</label>
          <input id="factura" class="form-control form-control-sm" />
        </div>

        <div class="col-md-4">
          <label class="form-label mb-0">Proyecto</label>
          <select id="proyecto" class="form-select form-select-sm">
            <option value="">Seleccione</option>
          </select>
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex justify-content-end mb-2">
        <button id="btnAdd" class="btn btn-outline-secondary btn-sm">+ Agregar Contenedor o Pallet</button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
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
            <th style="width:80px;">Acciones</th>
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
        <table id="tblRecibido" class="table table-sm table-striped table-bordered align-middle">
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

      <div class="mt-2">
        <button id="btnGuardar" class="btn btn-success btn-sm">Guardar</button>
        <button class="btn btn-secondary btn-sm" onclick="location.href='ingresos_admin.php'">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<script>
// ✅ API correcto: /api/recepcion/recepcion_api.php
const PROJECT_BASE = window.location.pathname.includes('/public/')
  ? window.location.pathname.split('/public/')[0]
  : '';

const API = PROJECT_BASE + '/public/api/recepcion/recepcion_api.php';
const API_EMPRESAS = PROJECT_BASE + '/public/api/empresas_api.php';

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
  (rows||[]).forEach(r=>{
    const o=document.createElement('option');
    o.value = r[map.value];
    o.textContent = r[map.text];
    sel.appendChild(o);
  });
}

function getTipo(){
  const r = document.querySelector('input[name="tipo"]:checked');
  return r ? r.value : 'OC';
}

async function loadEmpresas(){
  const u = new URL(API_EMPRESAS, window.location.href);
  u.searchParams.set('action','empresas');
  const r = await fetch(u.toString());
  const j = await r.json();
  if(!j.ok) return alert(j.error||'Error cargando empresas');
  fillSelect(qs('empresa'), j.data, {value:'id', text:'nombre', placeholder:{value:'',text:'Seleccione'}});
}

async function loadAlmacenes(){
  const j = await apiGet('almacenes', {});
  if(!j.ok) return alert(j.error||'Error cargando almacenes');
  fillSelect(qs('almacen'), j.data, {value:'id', text:'nombre', placeholder:{value:'',text:'Seleccione'}});
}

async function loadProveedores(){
  const j = await apiGet('proveedores', {});
  if(!j.ok) return alert(j.error||'Error cargando proveedores');
  fillSelect(qs('proveedor'), j.data, {value:'id', text:'nombre', placeholder:{value:'',text:'Seleccione'}});
}

async function loadOCs(){
  const tipo = getTipo();
  if(tipo !== 'OC'){
    fillSelect(qs('oc_folio'), [], {value:'folio', text:'folio', placeholder:{value:'',text:'N/A'}});
    return;
  }
  const prov = val('proveedor');
  const alm = val('almacen');
  if(!alm){
    fillSelect(qs('oc_folio'), [], {value:'folio', text:'folio', placeholder:{value:'',text:'Seleccione una OC'}});
    return;
  }
  const j = await apiGet('ocs', {almacen: alm, proveedor: prov});
  if(!j.ok) return alert(j.error||'Error');
  fillSelect(qs('oc_folio'), j.data, {value:'folio', text:'folio', placeholder:{value:'',text:'Seleccione una OC'}});
}

async function loadZonas(){
  const alm = val('almacen');
  if(!alm){
    fillSelect(qs('zona_recepcion'), [], {value:'zona',text:'zona', placeholder:{value:'',text:'Seleccione una Zona de Recepción'}});
    fillSelect(qs('zona_destino'), [], {value:'zona',text:'zona', placeholder:{value:'',text:'Seleccione Zona destino'}});
    return;
  }

  // ✅ acciones correctas en API
  const jr = await apiGet('zonas_recepcion', {almacen: alm});
  if(!jr.ok) return alert(jr.error||'Error');
  const jd = await apiGet('zonas_destino', {almacen: alm});
  if(!jd.ok) return alert(jd.error||'Error');

  fillSelect(qs('zona_recepcion'), jr.data, {value:'zona', text:'zona', placeholder:{value:'',text:'Seleccione una Zona de Recepción'}});
  fillSelect(qs('zona_destino'), jd.data, {value:'zona', text:'zona', placeholder:{value:'',text:'Seleccione Zona destino'}});
}

async function loadBL(){
  const alm = val('almacen');
  const zona = val('zona_destino');
  if(!alm || !zona){
    fillSelect(qs('bl_destino'), [], {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
    return;
  }
  // ✅ acción correcta en API
  const j = await apiGet('bl_destino', {almacen: alm, zona: zona});
  if(!j.ok) return alert(j.error||'Error');
  fillSelect(qs('bl_destino'), j.data, {value:'bl', text:'bl', placeholder:{value:'',text:'Seleccione BL destino'}});
}

async function onOCChange(){
  const folio = val('oc_folio');
  if(!folio) return;
  const j = await apiGet('oc_detalle', {folio});
  if(!j.ok) return alert(j.error||'Error');
  // opcional: auto-llenar líneas si tu API devuelve detalle
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
    <td class="text-center"><button class="btn btn-outline-danger btn-sm btnDel">X</button></td>
  `;

  tr.querySelector('.btnDel').addEventListener('click', ()=> tr.remove());
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
    usuario: val('usuario') || 'WMS',
    lineas: []
  };

  [...tb.children].forEach(tr=>
