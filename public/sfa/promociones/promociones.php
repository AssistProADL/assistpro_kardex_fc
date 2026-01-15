<?php
// UI: Administración de Promociones (SFA)
// Ruta esperada: /public/sfa/promociones/promociones.php
// Depende de: /public/api/promociones/promociones_api.php
// Detalles (legacy separado): /public/promociones/promocion_reglas.php, /public/promociones/promocion_beneficios.php, /public/promociones/promocion_scope.php

// Menú/layout global (si existe en tu proyecto)
$menu_global = __DIR__ . '/../../bi/_menu_global.php';
$menu_global_end = __DIR__ . '/../../bi/_menu_global_end.php';
if (file_exists($menu_global)) { include $menu_global; }
?>
<div class="container-fluid py-3">
  <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h3 class="mb-0">Administración de Promociones</h3>
      <div class="text-muted small">Motor: Unidad + Monto + Mixta (Rules / Scope / Rewards)</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnDebug" type="button">Debug</button>
      <button class="btn btn-primary btn-sm" id="btnBuscar" type="button">Buscar</button>
      <button class="btn btn-success btn-sm" id="btnNuevo" type="button">+ Nuevo</button>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <label class="form-label mb-2">Seleccione un almacén</label>
      <select class="form-select" id="selAlmacen"></select>

      <pre class="mt-3 mb-0 small bg-light p-2 rounded border" id="debugBox" style="display:none; max-height:160px; overflow:auto;"></pre>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle" id="tblPromos">
          <thead>
            <tr>
              <th style="width:280px;">Acciones</th>
              <th>Status</th>
              <th>ID</th>
              <th>Clave</th>
              <th>Descripción</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th class="text-end">Rules</th>
              <th class="text-end">Scope</th>
            </tr>
          </thead>
          <tbody id="tbPromos">
            <tr><td colspan="9" class="text-muted">Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Promo (header) -->
<div class="modal fade" id="mdlPromo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlPromoTitle">Promoción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="promo_id" value="">
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label">Almacén*</label>
            <select class="form-select" id="promo_almacen"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Clave*</label>
            <input class="form-control" id="promo_clave" maxlength="80">
          </div>
          <div class="col-md-3">
            <label class="form-label">Estatus</label>
            <select class="form-select" id="promo_activa">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Descripción*</label>
            <input class="form-control" id="promo_descripcion" maxlength="255">
          </div>

          <div class="col-md-3">
            <label class="form-label">Caduca</label>
            <select class="form-select" id="promo_caduca">
              <option value="0">No</option>
              <option value="1">Sí</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Inicio</label>
            <input type="date" class="form-control" id="promo_inicio">
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Fin</label>
            <input type="date" class="form-control" id="promo_fin">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select class="form-select" id="promo_tipo">
              <option value="UNIDADES">Unidades</option>
              <option value="MONTO">Monto</option>
              <option value="MIXTO">Mixta</option>
            </select>
          </div>

          <div class="col-12">
            <div class="text-muted small">Fiscal/contable no se gestiona aquí (se eliminó del módulo).</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cerrar</button>
        <button class="btn btn-primary" id="btnGuardarPromo" type="button">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const API = "../../api/promociones/promociones_api.php"; // desde /public/sfa/promociones/
  const selAlmacen = document.getElementById('selAlmacen');
  const tbPromos = document.getElementById('tbPromos');
  const debugBox = document.getElementById('debugBox');
  const btnDebug = document.getElementById('btnDebug');
  const btnBuscar = document.getElementById('btnBuscar');
  const btnNuevo  = document.getElementById('btnNuevo');

  // Modal
  const mdlEl = document.getElementById('mdlPromo');
  const mdl = (window.bootstrap && bootstrap.Modal) ? new bootstrap.Modal(mdlEl) : null;
  const promo_id = document.getElementById('promo_id');
  const promo_almacen = document.getElementById('promo_almacen');
  const promo_clave = document.getElementById('promo_clave');
  const promo_descripcion = document.getElementById('promo_descripcion');
  const promo_activa = document.getElementById('promo_activa');
  const promo_caduca = document.getElementById('promo_caduca');
  const promo_inicio = document.getElementById('promo_inicio');
  const promo_fin = document.getElementById('promo_fin');
  const promo_tipo = document.getElementById('promo_tipo');
  const btnGuardarPromo = document.getElementById('btnGuardarPromo');

  let almacenes = [];
  let promos = [];

  function logDebug(msg){
    const ts = new Date().toLocaleTimeString();
    debugBox.textContent = `[${ts}] ${msg}\n` + debugBox.textContent;
  }

  async function apiGet(params){
    const url = API + "?" + new URLSearchParams(params).toString();
    const r = await fetch(url, {credentials:'same-origin'});
    const j = await r.json();
    if (!j.ok) throw new Error(j.detalle || j.error || "Error servidor");
    return j;
  }

  async function apiPost(params, body){
    const url = API + "?" + new URLSearchParams(params).toString();
    const r = await fetch(url, {
      method:'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: new URLSearchParams(body).toString(),
      credentials:'same-origin'
    });
    const j = await r.json();
    if (!j.ok) throw new Error(j.detalle || j.error || "Error servidor");
    return j;
  }

  function getAlmacenId(){
    const v = selAlmacen.value;
    return v ? parseInt(v, 10) : 0;
  }

  function fillAlmacenesSelect(){
    selAlmacen.innerHTML = "";
    promo_almacen.innerHTML = "";

    almacenes.forEach(a => {
      const opt = document.createElement('option');
      opt.value = a.id_almacen;
      opt.textContent = `(${a.id_almacen}) ${a.nombre}`;
      selAlmacen.appendChild(opt);

      const opt2 = opt.cloneNode(true);
      promo_almacen.appendChild(opt2);
    });
  }

  function badgeActivo(activo){
    return activo ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>';
  }

  function renderPromos(){
    if (!promos.length){
      tbPromos.innerHTML = `<tr><td colspan="9" class="text-muted">Sin promociones para este almacén.</td></tr>`;
      return;
    }
    tbPromos.innerHTML = promos.map(p => {
      const rules = p.rules_count ?? 0;
      const scope = p.scope_count ?? 0;
      const inicio = p.fecha_ini || p.inicio || '';
      const fin = p.fecha_fin || p.fin || '';
      return `
        <tr>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-primary" data-act="ver" data-id="${p.id}">Ver</button>
              <button class="btn btn-outline-secondary" data-act="rules" data-id="${p.id}">Rules</button>
              <button class="btn btn-outline-secondary" data-act="rewards" data-id="${p.id}">Rewards</button>
              <button class="btn btn-outline-secondary" data-act="scope" data-id="${p.id}">Scope</button>
            </div>
          </td>
          <td>${badgeActivo(!!p.activo)}</td>
          <td>${p.id}</td>
          <td>${(p.clave||'')}</td>
          <td>${(p.descripcion||'')}</td>
          <td>${inicio}</td>
          <td>${fin}</td>
          <td class="text-end">${rules}</td>
          <td class="text-end">${scope}</td>
        </tr>
      `;
    }).join('');
  }

  function openPromoModal(p){
    promo_id.value = p?.id || '';
    promo_almacen.value = (p?.id_almacen || getAlmacenId() || '');
    promo_clave.value = p?.clave || '';
    promo_descripcion.value = p?.descripcion || '';
    promo_activa.value = (p?.activo ?? 1) ? '1' : '0';
    promo_caduca.value = (p?.caduca ?? 0) ? '1' : '0';
    promo_inicio.value = (p?.fecha_ini || p?.inicio || '');
    promo_fin.value = (p?.fecha_fin || p?.fin || '');
    promo_tipo.value = (p?.tipo || 'MONTO');

    if (mdl) mdl.show();
    else mdlEl.style.display='block';
  }

  async function loadAlmacenes(){
    const j = await apiGet({action:'almacenes'});
    almacenes = j.almacenes || [];
    fillAlmacenesSelect();
    logDebug(`Almacenes=${almacenes.length}`);
  }

  async function loadPromos(){
    const almacenId = getAlmacenId();
    if (!almacenId){
      tbPromos.innerHTML = `<tr><td colspan="9" class="text-muted">Seleccione un almacén.</td></tr>`;
      return;
    }
    const j = await apiGet({action:'list', almacen_id: almacenId});
    promos = j.promos || [];
    logDebug(`Promos=${promos.length} (almacen=${almacenId})`);
    renderPromos();
  }

  function gotoDetalle(kind, promoId){
    const almacenId = getAlmacenId();
    if (!almacenId || !promoId){
      alert("Selecciona almacén y promo.");
      return;
    }
    // Páginas de detalle viven en /public/promociones/
    const base = "../../promociones/";
    let page = "";
    if (kind === "rules") page = "promocion_reglas.php";
    if (kind === "rewards") page = "promocion_beneficios.php";
    if (kind === "scope") page = "promocion_scope.php";
    if (!page) return;

    const url = base + page + "?" + new URLSearchParams({promo_id: promoId, almacen_id: almacenId}).toString();
    window.location.href = url;
  }

  // Eventos
  btnDebug.addEventListener('click', () => {
    debugBox.style.display = (debugBox.style.display === 'none') ? 'block' : 'none';
  });
  btnBuscar.addEventListener('click', loadPromos);

  btnNuevo.addEventListener('click', () => openPromoModal(null));

  selAlmacen.addEventListener('change', loadPromos);

  document.getElementById('tblPromos').addEventListener('click', async (ev) => {
    const b = ev.target.closest('button[data-act]');
    if (!b) return;
    const act = b.getAttribute('data-act');
    const id = parseInt(b.getAttribute('data-id'), 10);

    if (act === 'ver'){
      try{
        const j = await apiGet({action:'get', id: id});
        openPromoModal(j.promo || null);
      }catch(e){ alert(e.message); }
      return;
    }
    if (act === 'rules' || act === 'rewards' || act === 'scope'){
      gotoDetalle(act, id);
      return;
    }
  });

  btnGuardarPromo.addEventListener('click', async () => {
    try{
      const body = {
        id: promo_id.value || '',
        id_almacen: promo_almacen.value || '',
        clave: promo_clave.value.trim(),
        descripcion: promo_descripcion.value.trim(),
        activo: promo_activa.value,
        caduca: promo_caduca.value,
        fecha_ini: promo_inicio.value,
        fecha_fin: promo_fin.value,
        tipo: promo_tipo.value
      };
      if (!body.id_almacen || !body.clave || !body.descripcion){
        alert("Completa: Almacén, Clave, Descripción.");
        return;
      }
      const j = await apiPost({action:'save'}, body);
      logDebug(`POST save ok id=${j.id || ''}`);
      if (mdl) mdl.hide();
      await loadPromos();
    }catch(e){
      alert(e.message);
    }
  });

  // Init
  (async function(){
    try{
      await loadAlmacenes();
      if (almacenes.length){
        // Seleccionar primer almacén por defecto
        selAlmacen.value = almacenes[0].id_almacen;
        await loadPromos();
      }else{
        tbPromos.innerHTML = `<tr><td colspan="9" class="text-muted">No hay almacenes configurados.</td></tr>`;
      }
    }catch(e){
      tbPromos.innerHTML = `<tr><td colspan="9" class="text-danger">${e.message}</td></tr>`;
      logDebug("ERROR: " + e.message);
    }
  })();
})();
</script>
<?php
if (file_exists($menu_global_end)) { include $menu_global_end; }
?>
