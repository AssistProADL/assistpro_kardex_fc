<?php
// /public/sfa/promociones/promociones.php
// UI Promociones — UX homogeneizada con Listas de Precios (JS + API)

$menuStart = __DIR__ . '/../../bi/_menu_global.php';
$menuEnd   = __DIR__ . '/../../bi/_menu_global_end.php';
if (file_exists($menuStart)) require_once $menuStart;
?>

<style>
  .ap-wrap {
    font-size: 10px
  }

  .ap-title {
    font-weight: 700;
    font-size: 14px;
    color: #0F5AAD
  }

  .ap-btn {
    font-size: 10px;
    padding: 6px 10px
  }

  .badge-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block
  }

  .ap-muted {
    color: #6c757d
  }

  .ap-kpi .badge {
    font-size: 10px
  }
</style>

<div class="container-fluid px-3 ap-wrap">

  <!-- Header -->
  <div class="row mb-2">
    <div class="col-6">
      <div class="ap-title">Administración de Promociones</div>
      <small class="text-muted">Motor: Rules / Scope / Rewards</small>
    </div>
    <div class="col-6 text-end">
      <button class="btn btn-success btn-sm ap-btn" onclick="gotoNewPromo()">+ Nueva promoción</button>

    </div>
  </div>

  <div class="row">

    <!-- Filtros + tabla -->
    <div class="col-md-8">

      <!-- Filtros -->
      <div class="card mb-2">
        <div class="card-header py-1">Filtros</div>
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
                <option value="FUTURA">Futuras</option>
                <option value="VENCIDA">Vencidas</option>
                <option value="TODAS">Todas</option>
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
                <input id="f_buscar" class="form-control" placeholder="Clave o descripción…">
                <button class="btn btn-primary btn-sm ap-btn" onclick="loadPromos()">Aplicar</button>
                <button class="btn btn-outline-secondary btn-sm ap-btn" onclick="clearFilters()">Limpiar</button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card">
        <div class="card-header py-1">Promociones</div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:420px;overflow:auto">
            <table class="table table-striped table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:200px">Acciones</th>
                  <th>Vigencia</th>
                  <th>Activa</th>
                  <th>ID</th>
                  <th>Clave</th>
                  <th>Descripción</th>
                  <th>Inicio</th>
                  <th>Fin</th>
                  <th>Rules</th>
                  <th>Scope</th>
                </tr>
              </thead>
              <tbody id="tb"></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <!-- KPIs -->
    <div class="col-md-4">
      <div class="card ap-kpi">
        <div class="card-header py-1">Resumen promociones</div>
        <div class="card-body">
          <div><strong>Total:</strong> <span id="k_total">0</span></div>
          <div class="mt-1"><span class="badge bg-success">Vigentes: <span id="k_vig">0</span></span></div>
          <div class="mt-1"><span class="badge bg-info">Futuras: <span id="k_fut">0</span></span></div>
          <div class="mt-1"><span class="badge bg-danger">Vencidas: <span id="k_ven">0</span></span></div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal Promoción -->
<div class="modal fade" id="mdlPromo" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title fw-bold">
          Promoción <span class="badge bg-secondary ms-2">Solo lectura</span>
        </div>

        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:10px">
        <input type="hidden" id="p_id">

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Almacén*</label>
            <select id="p_id_almacen" class="form-select form-select-sm"></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Clave*</label>
            <input id="p_cve" class="form-control form-control-sm">
          </div>
          <div class="col-md-4">
            <label class="form-label">Estatus</label>
            <select id="p_activo" class="form-select form-select-sm">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
          </div>

          <div class="col-md-12">
            <label class="form-label">Descripción*</label>
            <input id="p_desc" class="form-control form-control-sm">
          </div>

          <div class="col-md-4">
            <label class="form-label">Inicio</label>
            <input id="p_fi" type="date" class="form-control form-control-sm">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fin</label>
            <input id="p_ff" type="date" class="form-control form-control-sm">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <input id="p_tipo" class="form-control form-control-sm">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary ap-btn" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary ap-btn" onclick="savePromo()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
  const API = '../../api/promociones/promociones_api.php';
  let mdlPromo;
  let promosCache = [];

  document.addEventListener('DOMContentLoaded', async () => {
    mdlPromo = new bootstrap.Modal(document.getElementById('mdlPromo'));
    await loadAlmacenes();
    $('almacen_id').addEventListener('change', loadPromos);
    $('f_vigencia').addEventListener('change', renderPromos);
    $('f_tipo').addEventListener('change', renderPromos);



  });

  async function apiGet(p) {
    const r = await fetch(API + '?' + new URLSearchParams(p));
    return r.json();
  }
  async function apiPost(p) {
    const fd = new FormData();
    Object.entries(p).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(API, {
      method: 'POST',
      body: fd
    });
    return r.json();
  }

  async function loadAlmacenes() {
    const j = await apiGet({
      action: 'almacenes'
    });

    const a1 = $('almacen_id');
    const a2 = $('p_id_almacen'); // puede NO existir

    if (a1) a1.innerHTML = '';
    if (a2) a2.innerHTML = '';

    j.rows.forEach((a, idx) => {
      const opt1 = new Option(`(${a.id}) ${a.nombre}`, a.id);
      if (a1) a1.add(opt1);

      if (a2) {
        const opt2 = new Option(`(${a.id}) ${a.nombre}`, a.id);
        a2.add(opt2);
      }

      if (idx === 0 && a1) opt1.selected = true;
    });

    loadPromos();
  }


  function clearFilters() {
    $('f_buscar').value = '';
    $('f_vigencia').value = 'TODAS';
    $('f_tipo').value = 'TODOS';
    renderPromos();
  }


  async function loadPromos() {
    const almacen_id = $('almacen_id').value;
    const j = await apiGet({
      action: 'list',
      almacen_id
    });
    promosCache = j.rows || [];
    renderPromos();
  }

  async function loadPromo(id) {
    document.querySelector('#mdlPromo .btn-primary')?.classList.add('d-none');

    const j = await apiGet({
      action: 'get',
      id: id
    });

    if (!j || !j.promo) {
      alert('No se pudo cargar la promoción');
      console.error(j);
      return;
    }

    const r = j.promo;

    // ID
    $('p_id').value = r.id;

    // Llenar campos (ojo con los nombres reales)
    $('p_id_almacen').value = r.id_almacen;
    $('p_cve').value = r.cve_gpoart || '';
    $('p_activo').value = r.Activo ?? 1;
    $('p_desc').value = r.des_gpoart || '';
    $('p_fi').value = r.FechaI || '';
    $('p_ff').value = r.FechaF || '';
    $('p_tipo').value = r.Tipo || '';

    // 🔒 Modo VER – todo solo lectura
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
    return (
      r.tipo ||
      r.Tipo ||
      r.tipo_promo ||
      r.TipoPromo ||
      ''
    ).toString().toUpperCase();
  }

  function renderPromos() {
    const tb = $('tb');
    tb.innerHTML = '';

    // 🔹 Normalizar filtros
    const vig = $('f_vigencia').value;
    const tipo = $('f_tipo').value;
    const q = $('f_buscar').value.toLowerCase();

    let k = {
      t: 0,
      v: 0,
      f: 0,
      e: 0
    };

    promosCache.forEach(r => {

      const fi = r.fecha_inicio ? new Date(r.fecha_inicio) : null;
      const ff = r.fecha_fin ? new Date(r.fecha_fin) : null;
      const hoy = new Date();

      let st = 'VIGENTE';
      if (fi && fi > hoy) st = 'FUTURA';
      if (ff && ff < hoy) st = 'VENCIDA';

      // KPIs (siempre se cuentan)
      k.t++;
      if (st === 'VIGENTE') k.v++;
      if (st === 'FUTURA') k.f++;
      if (st === 'VENCIDA') k.e++;

      // 🔹 Filtro por vigencia
      if (vig !== 'TODAS' && vig !== st) return;

      // Filtro por tipo
      const tipoRow = normalizeTipo(r);
      if (tipo !== 'TODOS' && tipoRow !== tipo) return;


      // 🔹 Filtro texto
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
    onchange="togglePromo(${r.id}, this.checked)"
  >
</td>

<td>${r.id}</td>

        <td>${r.clave || ''}</td>
        <td>${r.descripcion || ''}</td>
        <td>${r.fecha_inicio || ''}</td>
        <td>${r.fecha_fin || ''}</td>
        <td>${r.total_rules || 0}</td>
        <td>${r.total_scope || 0}</td>
      </tr>
    `);
    });

    $('k_total').textContent = k.t;
    $('k_vig').textContent = k.v;
    $('k_fut').textContent = k.f;
    $('k_ven').textContent = k.e;
  }


  function $(id) {
    return document.getElementById(id);
  }

  function gotoRules(id) {
    location.href = 'promocion_reglas.php?promo_id=' + id;
  }

  function gotoRewards(id) {
    location.href = 'promocion_beneficios.php?promo_id=' + id;
  }

  function gotoScope(id) {
    location.href = 'promocion_scope.php?promo_id=' + id;
  }

  function openPromo(id) {
    loadPromo(id)
  }

  function savePromo() {
    alert('Guardar (ya lo tienes conectado)');
  }

  function gotoNewPromo() {
    const almacenId = $('almacen_id')?.value || '';
    let url = 'promo_design.php';
    if (almacenId) {
      url += '?almacen_id=' + encodeURIComponent(almacenId);
    }
    location.href = url;
  }

  async function togglePromo(id, checked) {
    const activo = checked ? 1 : 0;

    const j = await apiPost({
      action: 'toggle',
      id: id,
      Activo: activo
    });

    if (!j || j.ok !== 1) {
      alert('No se pudo actualizar el estatus');
      // rollback visual
      loadPromos();
      return;
    }
  }
</script>

<?php if (file_exists($menuEnd)) require_once $menuEnd; ?>