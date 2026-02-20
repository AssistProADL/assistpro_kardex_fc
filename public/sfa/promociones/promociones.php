<?php
// /public/sfa/promociones/promociones.php
// Mantiene el estilo (promociones1.php) + combo almacenes desde api_empresas_almacenes_rutas.php (cve_almac, nombre)

$menuStart = __DIR__ . '/../../bi/_menu_global.php';
$menuEnd   = __DIR__ . '/../../bi/_menu_global_end.php';
if (file_exists($menuStart)) require_once $menuStart;
?>

<style>
  .ap-wrap { font-size: 10px }
  .ap-title { font-weight: 700; font-size: 14px; color: #0F5AAD }
  .ap-btn { font-size: 10px; padding: 6px 10px }
  .badge-dot { width: 10px; height: 10px; display: inline-block; border-radius: 50%; }
  .ap-kpi { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
</style>

<div class="container-fluid ap-wrap">
  <div class="row g-2">
    <div class="col-12">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="ap-title">Administración de Promociones</div>
          <div class="text-muted" style="font-size:10px">Motor: Rules / Scope / Rewards</div>
        </div>
        <button class="btn btn-success ap-btn" onclick="gotoNewPromo()">+ Nueva promoción</button>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header py-2"><b>Filtros</b></div>
        <div class="card-body py-2">
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label mb-0">Almacén</label>
              <select id="almacen_id" class="form-select form-select-sm"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label mb-0">Vigencia</label>
              <select id="f_vigencia" class="form-select form-select-sm">
                <option value="VIGENTE">Vigentes</option>
                <option value="TODAS">Todas</option>
                <option value="FUTURA">Futuras</option>
                <option value="VENCIDA">Vencidas</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label mb-0">Tipo</label>
              <select id="f_tipo" class="form-select form-select-sm">
                <option value="TODOS">Todos</option>
                <option value="UNIDADES">Unidades</option>
                <option value="TICKET">Ticket</option>
                <option value="ACUMULADA">Acumulada</option>
              </select>
            </div>

            <div class="col-md-5">
              <label class="form-label mb-0">Buscar</label>
              <div class="input-group input-group-sm">
                <input id="f_buscar" class="form-control" placeholder="Clave o descripción...">
                <button class="btn btn-primary ap-btn" onclick="renderPromos()">Aplicar</button>
                <button class="btn btn-outline-secondary ap-btn" onclick="clearFilters()">Limpiar</button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="card mt-2">
        <div class="card-header py-2"><b>Promociones</b></div>
        <div class="card-body p-0">
          <div style="overflow:auto">
            <table class="table table-sm table-striped mb-0">
              <thead class="table-light">
                <tr>
                  <th style="min-width:220px">Acciones</th>
                  <th class="text-center">Vigencia</th>
                  <th class="text-center">Activa</th>
                  <th>ID</th>
                  <th>Clave</th>
                  <th>Descripción</th>
                  <th>Inicio</th>
                  <th>Fin</th>
                  <th class="text-end">Rules</th>
                  <th class="text-end">Scope</th>
                </tr>
              </thead>
              <tbody id="tb"></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <div class="col-12 col-lg-4">
      <div class="card">
        <div class="card-header py-2"><b>Resumen promociones</b></div>
        <div class="card-body">
          <div style="font-size:10px"><b>Total:</b> <span id="k_total">0</span></div>
          <div class="mt-2">
            <span class="ap-kpi bg-success text-white">Vigentes: <span id="k_vig">0</span></span>
          </div>
          <div class="mt-1">
            <span class="ap-kpi bg-info text-white">Futuras: <span id="k_fut">0</span></span>
          </div>
          <div class="mt-1">
            <span class="ap-kpi bg-danger text-white">Vencidas: <span id="k_ven">0</span></span>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal ver promo -->
<div class="modal fade" id="mdlPromo" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0">Detalle de Promoción</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body ap-wrap">
        <input type="hidden" id="p_id">

        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label mb-0">Almacén</label>
            <select id="p_id_almacen" class="form-select form-select-sm"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label mb-0">Activo</label>
            <select id="p_activo" class="form-select form-select-sm">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label mb-0">Clave</label>
            <input id="p_cve" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label mb-0">Tipo</label>
            <input id="p_tipo" class="form-control form-control-sm">
          </div>

          <div class="col-md-12">
            <label class="form-label mb-0">Descripción</label>
            <input id="p_desc" class="form-control form-control-sm">
          </div>

          <div class="col-md-3">
            <label class="form-label mb-0">Fecha inicio</label>
            <input id="p_fi" type="date" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label mb-0">Fecha fin</label>
            <input id="p_ff" type="date" class="form-control form-control-sm">
          </div>
        </div>

      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary ap-btn" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  // ✅ Este sigue siendo el API de promociones (listar/get/toggle)
  const API_PROMOS = '../../api/promociones/promociones_api.php';

  // ✅ Este es TU API corporativo para empresas/almacenes/rutas
  const API_EAR = '../../api/api_empresas_almacenes_rutas.php';

  let mdlPromo;
  let promosCache = [];

  document.addEventListener('DOMContentLoaded', async () => {
    mdlPromo = new bootstrap.Modal(document.getElementById('mdlPromo'));

    await loadAlmacenes(); // ahora viene de api_empresas_almacenes_rutas.php

    $('almacen_id').addEventListener('change', loadPromos);
    $('f_vigencia').addEventListener('change', renderPromos);
    $('f_tipo').addEventListener('change', renderPromos);
    $('f_buscar').addEventListener('keyup', (e)=>{ if(e.key==='Enter') renderPromos(); });
  });

  async function apiGetPromos(p) {
    const r = await fetch(API_PROMOS + '?' + new URLSearchParams(p));
    return r.json();
  }
  async function apiPostPromos(p) {
    const fd = new FormData();
    Object.entries(p).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(API_PROMOS, { method: 'POST', body: fd });
    return r.json();
  }

  // ✅ Carga almacenes desde api_empresas_almacenes_rutas.php usando cve_almac y nombre
  async function loadAlmacenes() {
    // Nota: no invento el contrato; lo más común en tu API es responder {data:[...]}
    // Si tu API usa otra key (rows, almacenes, etc.), lo ajusto al vuelo.
    const r = await fetch(API_EAR + '?' + new URLSearchParams({ tipo: 'almacenes' }));
    const j = await r.json();

    const list = j.data || j.rows || j.almacenes || [];
    const a1 = $('almacen_id');
    const a2 = $('p_id_almacen');

    a1.innerHTML = '';
    a2.innerHTML = '';

    list.forEach((a, idx) => {
      const cve = a.cve_almac ?? a.id ?? a.clave ?? '';
      const nom = a.nombre ?? a.des_almac ?? a.nombre_almacen ?? '';

      if (!cve) return;

      const opt1 = new Option(`(${cve}) ${nom}`, cve);
      a1.add(opt1);

      const opt2 = new Option(`(${cve}) ${nom}`, cve);
      a2.add(opt2);

      if (idx === 0) opt1.selected = true;
    });

    loadPromos();
  }

  async function loadPromos() {
    const almacen_id = $('almacen_id').value || '';
    const j = await apiGetPromos({ action: 'list', almacen_id });

    // promociones_api.php (compatible con esta vista) debe responder { ok:1, rows:[...] }
    promosCache = j.rows || [];
    renderPromos();
  }

  async function loadPromo(id) {
    const j = await apiGetPromos({ action: 'get', id });

    if (!j || !j.promo) {
      alert('No se pudo cargar la promoción');
      console.error(j);
      return;
    }

    const r = j.promo;

    $('p_id').value = r.id;
    $('p_id_almacen').value = r.id_almacen;
    $('p_cve').value = r.cve_gpoart || '';
    $('p_activo').value = (r.Activo ?? 1);
    $('p_desc').value = r.des_gpoart || '';
    $('p_fi').value = r.FechaI || '';
    $('p_ff').value = r.FechaF || '';
    $('p_tipo').value = r.Tipo || '';

    // modo ver (sin romper UX)
    $('p_id_almacen').disabled = true;
    $('p_activo').disabled = true;
    $('p_cve').readOnly = true;
    $('p_desc').readOnly = true;
    $('p_fi').readOnly = true;
    $('p_ff').readOnly = true;
    $('p_tipo').readOnly = true;

    mdlPromo.show();
  }

  function normalizeTipo(r) {
    return (r.Tipo || r.tipo || '').toString().toUpperCase();
  }

  function renderPromos() {
    const tb = $('tb');
    tb.innerHTML = '';

    const vig = $('f_vigencia').value;
    const tipo = $('f_tipo').value;
    const q = ($('f_buscar').value || '').toLowerCase();

    let k = { t:0, v:0, f:0, e:0 };

    promosCache.forEach(r => {
      const fi = r.fecha_inicio ? new Date(r.fecha_inicio) : null;
      const ff = r.fecha_fin ? new Date(r.fecha_fin) : null;
      const hoy = new Date();

      let st = 'VIGENTE';
      if (fi && fi > hoy) st = 'FUTURA';
      if (ff && ff < hoy) st = 'VENCIDA';

      k.t++;
      if (st === 'VIGENTE') k.v++;
      if (st === 'FUTURA') k.f++;
      if (st === 'VENCIDA') k.e++;

      if (vig !== 'TODAS' && vig !== st) return;

      const tipoRow = normalizeTipo(r);
      if (tipo !== 'TODOS' && tipoRow !== tipo) return;

      if (
        q &&
        !(r.clave || '').toLowerCase().includes(q) &&
        !(r.descripcion || '').toLowerCase().includes(q)
      ) return;

      const dot =
        st === 'VIGENTE' ? '#28a745' :
        st === 'FUTURA' ? '#0dcaf0' :
        '#dc3545';

      tb.insertAdjacentHTML('beforeend', `
        <tr>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary ap-btn" onclick="openPromo(${r.id})">Ver</button>
              <button class="btn btn-outline-secondary ap-btn" onclick="gotoRules(${r.id})">Rules</button>
              <button class="btn btn-outline-success ap-btn" onclick="gotoRewards(${r.id})">Rewards</button>
              <button class="btn btn-outline-warning ap-btn" onclick="gotoScope(${r.id})">Scope</button>
            </div>
          </td>

          <td class="text-center">
            <span class="badge-dot" style="background:${dot}" title="${st}"></span>
          </td>

          <td class="text-center">
            <input type="checkbox"
              ${r.Activo == 1 ? 'checked' : ''}
              ${st === 'VENCIDA' ? 'disabled' : ''}
              onchange="togglePromo(${r.id}, this.checked)">
          </td>

          <td>${r.id}</td>
          <td>${r.clave || ''}</td>
          <td>${r.descripcion || ''}</td>
          <td>${r.fecha_inicio || ''}</td>
          <td>${r.fecha_fin || ''}</td>
          <td class="text-end">${r.total_rules || 0}</td>
          <td class="text-end">${r.total_scope || 0}</td>
        </tr>
      `);
    });

    $('k_total').textContent = k.t;
    $('k_vig').textContent = k.v;
    $('k_fut').textContent = k.f;
    $('k_ven').textContent = k.e;
  }

  function clearFilters() {
    $('f_buscar').value = '';
    $('f_vigencia').value = 'TODAS';
    $('f_tipo').value = 'TODOS';
    renderPromos();
  }

  function $(id) { return document.getElementById(id); }

  function gotoRules(id) { location.href = 'promocion_reglas.php?promo_id=' + id; }
  function gotoRewards(id) { location.href = 'promocion_beneficios.php?promo_id=' + id; }
  function gotoScope(id) { location.href = 'promocion_scope.php?promo_id=' + id; }
  function openPromo(id) { loadPromo(id); }

  function gotoNewPromo() {
    const almacenId = $('almacen_id')?.value || '';
    let url = 'promo_design.php';
    if (almacenId) url += '?almacen_id=' + encodeURIComponent(almacenId);
    location.href = url;
  }

  async function togglePromo(id, checked) {
    const activo = checked ? 1 : 0;

    const j = await apiPostPromos({ action:'toggle', id, Activo: activo });

    if (!j || j.ok !== 1) {
      alert('No se pudo actualizar el estatus');
      loadPromos();
      return;
    }
  }
</script>

<?php if (file_exists($menuEnd)) require_once $menuEnd; ?>
