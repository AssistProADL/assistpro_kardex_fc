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
window.RM = (function(){

  const API = {
    EMPRESAS:   '../api/empresas_api.php',
    FILTROS:    '../api/filtros_assistpro.php',
    OCS:        '../api/recepcion/recepcion_oc_api.php',
    RECEPCION:  '../api/recepcion/recepcion_api.php',
    ARTICULOS:  '../api/articulos_api.php'
  };

  const utils = {
    qs:id=>document.getElementById(id),
    val:id=>(document.getElementById(id)?.value||'').trim(),
    num:v=>{
      const n=parseFloat(String(v||'').replace(/,/g,''));
      return isNaN(n)?0:n;
    },
    async fetchJson(url,opt){
      try{
        const r=await fetch(url,opt||{cache:'no-store'});
        const t=await r.text();
        return JSON.parse(t);
      }catch(e){
        console.error(e);
        return {ok:0};
      }
    }
  };

  const state = {
    filtros:null,
    recibidos:[]
  };

  const selects = {

    async loadEmpresas(){
      const j=await utils.fetchJson(API.EMPRESAS);
      if(!j?.ok) return;
      this.fill(utils.qs('empresa'),j.data,'cve_cia','des_cia');
    },

    async loadFiltros(){
      if(state.filtros) return state.filtros;
      state.filtros=await utils.fetchJson(API.FILTROS);
      return state.filtros;
    },

    async loadAlmacenes(){
      const f=await this.loadFiltros();
      const rows=(f?.almacenes||[]).map(a=>({
        id:String(a.idp??a.id??''),
        clave:String(a.clave_almacen??a.cve_almac??'')
      }));
      this.fill(utils.qs('almacen'),rows,'id','clave');
    },

    fill(sel,rows,value,text){
      if(!sel) return;
      sel.innerHTML='<option value="">Seleccione</option>';
      (rows||[]).forEach(r=>{
        const o=document.createElement('option');
        o.value=r[value];
        o.textContent=r[text];
        sel.appendChild(o);
      });
    }
  };

  const articulos = {
    async apply(){
      const art=utils.val('articulo');
      if(!art) return;
      const u=new URL(API.ARTICULOS,window.location.href);
      u.searchParams.set('cve_articulo',art);
      const j=await utils.fetchJson(u.toString());
      if(!j?.ok) return;
      utils.qs('descripcion').value=j.data?.des_articulo||'';
      utils.qs('uom').value=j.data?.unidadMedida_nombre||'';
    }
  };

  const tx = {

    addLinea(){
      const art=utils.val('articulo');
      const cant=utils.num(utils.val('cant_rec'));
      if(!art||cant<=0) return;

      state.recibidos.push({
        cve_articulo:art,
        descripcion:utils.val('descripcion'),
        cant_rec:cant
      });

      this.render();
    },

    render(){
      const tb=utils.qs('tblRecibido')?.querySelector('tbody');
      if(!tb) return;
      tb.innerHTML='';
      state.recibidos.forEach((L,i)=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`
          <td>${L.cve_articulo}</td>
          <td>${L.descripcion}</td>
          <td>${L.cant_rec}</td>
          <td><button data-i="${i}">X</button></td>
        `;
        tb.appendChild(tr);
      });
    },

    async guardar(){
      if(!state.recibidos.length) return;

      const payload={
        empresa:utils.val('empresa'),
        almacen:utils.val('almacen'),
        lineas:state.recibidos
      };

      const u=new URL(API.RECEPCION,window.location.href);
      u.searchParams.set('action','guardar_recepcion');

      const j=await utils.fetchJson(u.toString(),{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
      });

      if(!j?.ok){
        alert('Error al guardar');
        return;
      }

      alert('Guardado correctamente');
      state.recibidos=[];
      this.render();
    }
  };

  async function init(){
    await selects.loadEmpresas();
    await selects.loadAlmacenes();

    utils.qs('articulo')?.addEventListener('change',()=>articulos.apply());
    utils.qs('btnRecibir')?.addEventListener('click',e=>{
      e.preventDefault();
      tx.addLinea();
    });
    utils.qs('btnGuardar')?.addEventListener('click',e=>{
      e.preventDefault();
      tx.guardar();
    });
  }

  return { init };

})();

document.addEventListener('DOMContentLoaded',()=>{
  RM.init();
});
</script>

 

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>