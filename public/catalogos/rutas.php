<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO – RUTAS
========================================================= */
  body {
    font-family: system-ui, -apple-system, sans-serif;
    background: #f4f6fb;
    margin: 0;
  }

  .ap-container {
    padding: 20px;
    font-size: 13px;
    max-width: 1600px;
    margin: 0 auto;
  }

  .ap-title {
    font-size: 20px;
    font-weight: 600;
    color: #0b5ed7;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* CARDS (KPIs) */
  .ap-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }

  .ap-card {
    background: #fff;
    border: 1px solid #e0e6ed;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    transition: all 0.2s;
  }

  .ap-card:hover {
    border-color: #0b5ed7;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(11, 94, 215, 0.1);
  }

  .ap-card .h {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-weight: 600;
    color: #333;
  }

  .ap-card .k {
    font-size: 24px;
    font-weight: 700;
    color: #0b5ed7;
  }

  /* TOOLBAR */
  .ap-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
    background: #fff;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #e0e6ed;
  }

  .ap-search {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 300px;
    background: #f8f9fa;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
  }

  .ap-search i {
    color: #6c757d;
  }

  .ap-search input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 13px;
  }

  /* CHIPS */
  .ap-chip {
    font-size: 12px;
    background: #f1f3f5;
    color: #495057;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 5px 12px;
    display: inline-flex;
    gap: 6px;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
  }

  .ap-chip:hover {
    background: #e9ecef;
    color: #212529;
    border-color: #ced4da;
  }

  .ap-chip.ok {
    background: #d1e7dd;
    color: #0f5132;
    border-color: #badbcc;
  }

  .ap-chip.warn {
    background: #fff3cd;
    color: #664d03;
    border-color: #ffecb5;
  }

  .ap-chip i {
    font-size: 12px;
  }

  button.ap-chip {
    font-family: inherit;
  }

  /* GRID */
  .ap-grid {
    background: #fff;
    border: 1px solid #e0e6ed;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    max-height: 600px;
    overflow-y: auto;
  }

  .ap-grid table {
    width: 100%;
    border-collapse: collapse;
  }

  .ap-grid th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 1px solid #dee2e6;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .ap-grid td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
    vertical-align: middle;
  }

  .ap-grid tr:hover td {
    background: #f8f9fa;
  }

  .ap-actions i {
    cursor: pointer;
    margin-right: 12px;
    color: #6c757d;
    transition: color 0.2s;
    font-size: 14px;
  }

  .ap-actions i:hover {
    color: #0b5ed7;
  }

  /* PAGER */
  .ap-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding: 0 5px;
  }

  .ap-pager button {
    background: #fff;
    border: 1px solid #dee2e6;
    padding: 6px 14px;
    border-radius: 6px;
    cursor: pointer;
    color: #495057;
  }

  .ap-pager button:disabled {
    opacity: 0.5;
    cursor: default;
  }

  .ap-pager button:hover:not(:disabled) {
    background: #f8f9fa;
    border-color: #ced4da;
  }

  .ap-pager select {
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    color: #495057;
    margin-left: 5px;
  }

  /* MODAL */
  .ap-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
  }

  .ap-modal[style*="display: block"] {
    display: flex !important;
  }

  .ap-modal-content {
    background: #fff;
    width: 980px;
    max-width: 95%;
    max-height: 90vh;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    padding: 20px;
  }

  .ap-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
  }

  .ap-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .ap-label {
    font-weight: 500;
    font-size: 13px;
    color: #495057;
  }

  .ap-input {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 12px;
    background: #fff;
    transition: all 0.2s;
  }

  .ap-input:focus-within {
    border-color: #0b5ed7;
    box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.1);
  }

  .ap-input i {
    color: #adb5bd;
  }

  .ap-input input,
  .ap-input select {
    border: none;
    outline: none;
    width: 100%;
    font-size: 14px;
    color: #212529;
    background: transparent;
  }

  .ap-error {
    font-size: 12px;
    color: #dc3545;
    display: none;
    margin-top: 4px;
  }

  button.primary {
    background: #0b5ed7;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
  }

  button.primary:hover {
    background: #0a58ca;
  }

  button.ghost {
    background: #fff;
    color: #495057;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  button.ghost:hover {
    background: #f1f3f5;
    border-color: #ced4da;
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-route"></i> Catálogo de Rutas</div>

  <div class="ap-cards">
    <div class="ap-card">
      <div class="h"><span>Total</span><i class="fa fa-list"></i></div>
      <div class="k" id="kpi_total">0</div>
    </div>
    <div class="ap-card">
      <div class="h"><span>Activos</span><i class="fa fa-check-circle" style="color:#198754"></i></div>
      <div class="k" id="kpi_activos">0</div>
    </div>
    <div class="ap-card">
      <div class="h"><span>Preventa</span><i class="fa fa-truck-fast"></i></div>
      <div class="k" id="kpi_preventa">0</div>
    </div>
    <div class="ap-card">
      <div class="h"><span>Entrega</span><i class="fa fa-truck-ramp-box"></i></div>
      <div class="k" id="kpi_entrega">0</div>
    </div>
  </div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, descripción, almacén…" onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
    <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
    <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Inactivos</button>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>ID</th>
          <th>Clave</th>
          <th>Descripción</th>
          <th>Status</th>
          <th>Almacén</th>
          <th>Preventa</th>
          <th>Entrega</th>
          <th>Control Pallets</th>
          <th>Consig Pallets</th>
          <th>Consig Cont</th>
          <th>Proveedor</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <!-- Paginación -->
  <div class="ap-pager">
    <div class="left">
      <button onclick="prevPage()" id="btnPrev"><i class="fa fa-chevron-left"></i> Anterior</button>
      <button onclick="nextPage()" id="btnNext">Siguiente <i class="fa fa-chevron-right"></i></button>
      <span class="ap-chip" id="lblRange" style="background:transparent; border:none; padding:0;">Mostrando 0–0</span>
    </div>
    <div class="right" style="display:flex; align-items:center;">
      <span>Página:</span>
      <select id="selPage" onchange="goPage(this.value)"></select>

      <span style="margin-left:15px">Por página:</span>
      <select id="selPerPage" onchange="setPerPage(this.value)">
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-route"></i> Ruta</h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios:
      <b>Clave</b>, <b>Descripción</b></div>

    <input type="hidden" id="ID_Ruta">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Clave *</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="cve_ruta" maxlength="20" placeholder="ENF-114"></div>
        <div class="ap-error" id="err_cve">Clave obligatoria.</div>
      </div>

      <div class="ap-field" style="grid-column: span 2">
        <div class="ap-label">Descripción *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="descripcion" maxlength="50"
            placeholder="Ruta Entrega 114"></div>
        <div class="ap-error" id="err_desc">Descripción obligatoria.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Status</div>
        <div class="ap-input"><i class="fa fa-traffic-light"></i>
          <select id="status">
            <option value="A">A</option>
            <option value="B">B</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Almacén</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i><input id="cve_almacenp" type="number" value="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Preventa</div>
        <div class="ap-input"><i class="fa fa-truck-fast"></i>
          <select id="venta_preventa">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Entrega</div>
        <div class="ap-input"><i class="fa fa-truck-ramp-box"></i>
          <select id="es_entrega">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Control Pallets</div>
        <div class="ap-input"><i class="fa fa-box-open"></i>
          <select id="control_pallets_cont">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Consig Pallets</div>
        <div class="ap-input"><i class="fa fa-pallet"></i><input id="consig_pallets" type="number" value="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Consig Contenedores</div>
        <div class="ap-input"><i class="fa fa-box"></i><input id="consig_cont" type="number" value="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Proveedor</div>
        <div class="ap-input"><i class="fa fa-building"></i><input id="ID_Proveedor" type="number" value="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
    </div>

    <div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px">
      <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
      <button class="primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content" style="width:700px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar rutas</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">Layout FULL con UPSERT por <b>cve_ruta</b>. Previsualiza antes de
      importar.</div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="fileCsv" accept=".csv">
    </div>

    <div style="margin-top:15px;display:flex;gap:10px">
      <button class="ghost" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
      <button class="primary" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
    </div>

    <div id="csvPreviewWrap" style="display:none;margin-top:15px">
      <h4 style="margin:0 0 10px; font-size:14px; color:#555;">Previsualización</h4>
      <div class="ap-grid" style="height:200px">
        <table style="font-size:12px;">
          <thead id="csvHead"></thead>
          <tbody id="csvBody"></tbody>
        </table>
      </div>

      <div class="ap-chip" id="importMsg" style="margin-top:15px; width:100%; display:none; justify-content:center;">
      </div>
    </div>

    <div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px">
      <button class="ghost" onclick="cerrarModal('mdlImport')">Cerrar</button>
      <button class="primary" onclick="importarCsv()" id="btnImportarFinal" style="display:none;">Importar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/rutas_api.php';

  let verInactivos = false;
  let qLast = '';
  let page = 1;
  let perPage = 25;
  let total = 0;
  let lastRows = [];

  // KPIs se cargan junto con la lista

  /* ===== Paginación ===== */
  function setPager() {
    const start = total > 0 ? ((page - 1) * perPage + (lastRows.length ? 1 : 0)) : 0;
    let end = total > 0 ? Math.min(page * perPage, total) : 0;
    if (total === 0) { end = 0; }

    lblRange.innerText = `Mostrando ${start}–${end}` + (total > 0 ? ` de ${total}` : '');

    const maxPages = total > 0 ? Math.max(1, Math.ceil(total / perPage)) : 1;
    selPage.innerHTML = '';
    for (let i = 1; i <= maxPages; i++) {
      const o = document.createElement('option');
      o.value = i; o.textContent = i;
      if (i === page) o.selected = true;
      selPage.appendChild(o);
    }
    btnPrev.disabled = (page <= 1);
    btnNext.disabled = total > 0 ? (page >= maxPages) : (lastRows.length < perPage);
  }
  function prevPage() { if (page > 1) { page--; cargar(); } }
  function nextPage() {
    const maxPages = total > 0 ? Math.ceil(total / perPage) : 1;
    if (page < maxPages) { page++; cargar(); }
    else if (total === 0 && lastRows.length === perPage) { page++; cargar(); }
  }
  function goPage(p) { page = Math.max(1, parseInt(p, 10) || 1); cargar(); }
  function setPerPage(v) { perPage = parseInt(v, 10) || 25; page = 1; cargar(); }

  function cargar() {
    const pageNum = page;
    const url = API + '?action=list'
      + '&show_inactivos=' + (verInactivos ? 1 : 0)
      + '&q=' + encodeURIComponent(qLast || '')
      + '&page=' + pageNum
      + '&pageSize=' + perPage;

    fetch(url).then(r => r.json()).then(resp => {
      const rows = resp.data || [];
      total = Number(resp.total || 0) || 0;
      lastRows = rows;

      // Update KPIs
      const k = resp.kpis || {};
      kpi_total.textContent = k.total || 0;
      kpi_activos.textContent = k.activos || 0;
      kpi_preventa.textContent = k.preventa || 0;
      kpi_entrega.textContent = k.entrega || 0;

      let h = '';
      rows.forEach(r => {
        h += `
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${r.ID_Ruta})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${r.ID_Ruta})"></i>
               <i class="fa fa-trash" title="Eliminar" onclick="eliminar(${r.ID_Ruta})"></i>`}
        </td>
        <td>${r.ID_Ruta || ''}</td>
        <td><b>${r.cve_ruta || ''}</b></td>
        <td>${r.descripcion || ''}</td>
        <td>${r.status || ''}</td>
        <td>${r.cve_almacenp || ''}</td>
        <td>${Number(r.venta_preventa || 0) === 1 ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip warn">No</span>'}</td>
        <td>${Number(r.es_entrega || 0) === 1 ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip warn">No</span>'}</td>
        <td>${(r.control_pallets_cont || 'N') === 'S' ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip warn">No</span>'}</td>
        <td>${r.consig_pallets || 0}</td>
        <td>${r.consig_cont || 0}</td>
        <td>${r.ID_Proveedor || 0}</td>
        <td>${Number(r.Activo || 1) === 1 ? '1' : '0'}</td>
      </tr>`;
      });
      tb.innerHTML = h || `<tr><td colspan="13" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
      setPager();
    });
  }

  function buscar() { qLast = q.value.trim(); page = 1; cargar(); }
  function limpiar() { q.value = ''; qLast = ''; page = 1; cargar(); }
  function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }

  function hideErrors() { err_cve.style.display = 'none'; err_desc.style.display = 'none'; }
  function validar() {
    hideErrors();
    let ok = true;
    if (!cve_ruta.value.trim()) { err_cve.style.display = 'block'; ok = false; }
    if (!descripcion.value.trim()) { err_desc.style.display = 'block'; ok = false; }
    return ok;
  }

  function nuevo() {
    document.querySelectorAll('#mdl input').forEach(i => i.value = '');
    document.querySelectorAll('#mdl select').forEach(s => {
      if (s.id === 'status') s.value = 'A';
      else if (s.id === 'Activo') s.value = '1';
      else if (s.id === 'venta_preventa') s.value = '1';
      else if (s.id === 'es_entrega') s.value = '0';
      else if (s.id === 'control_pallets_cont') s.value = 'N';
    });
    cve_almacenp.value = '0';
    consig_pallets.value = '0';
    consig_cont.value = '0';
    ID_Proveedor.value = '0';
    ID_Ruta.value = '';
    hideErrors();
    mdl.style.display = 'block';
  }

  function editar(id) {
    fetch(API + '?action=get&id=' + id).then(r => r.json()).then(resp => {
      const r = resp.data;
      ID_Ruta.value = r.ID_Ruta || '';
      cve_ruta.value = r.cve_ruta || '';
      descripcion.value = r.descripcion || '';
      status.value = r.status || 'A';
      cve_almacenp.value = r.cve_almacenp || 0;
      venta_preventa.value = String(r.venta_preventa || 1);
      es_entrega.value = String(r.es_entrega || 0);
      control_pallets_cont.value = r.control_pallets_cont || 'N';
      consig_pallets.value = r.consig_pallets || 0;
      consig_cont.value = r.consig_cont || 0;
      ID_Proveedor.value = r.ID_Proveedor || 0;
      Activo.value = String(r.Activo || 1);
      hideErrors();
      mdl.style.display = 'block';
    });
  }

  function guardar() {
    if (!validar()) return;

    const data = {
      ID_Ruta: ID_Ruta.value ? parseInt(ID_Ruta.value, 10) : null,
      cve_ruta: cve_ruta.value.trim(),
      descripcion: descripcion.value.trim(),
      status: status.value,
      cve_almacenp: parseInt(cve_almacenp.value || '0', 10),
      venta_preventa: parseInt(venta_preventa.value || '1', 10),
      es_entrega: parseInt(es_entrega.value || '0', 10),
      control_pallets_cont: control_pallets_cont.value,
      consig_pallets: parseInt(consig_pallets.value || '0', 10),
      consig_cont: parseInt(consig_cont.value || '0', 10),
      ID_Proveedor: parseInt(ID_Proveedor.value || '0', 10),
      Activo: parseInt(Activo.value || '1', 10)
    };

    fetch(API + '?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.error) {
          alert(resp.error);
          return;
        }
        cerrarModal('mdl');
        cargar();
      });
  }

  function eliminar(id) {
    if (!confirm('¿Eliminar ruta?')) return;
    fetch(API + '?action=delete&id=' + id, { method: 'POST' }).then(() => cargar());
  }
  function recuperar(id) {
    // Restore = set Activo=1
    fetch(API + '?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ID_Ruta: id, Activo: 1 })
    }).then(() => cargar());
  }

  function exportarDatos() {
    // Export all data as CSV
    fetch(API + '?action=list&pageSize=10000&show_inactivos=1')
      .then(r => r.json())
      .then(resp => {
        const rows = resp.data || [];
        let csv = 'ID,Clave,Descripción,Status,Almacén,Preventa,Entrega,Control Pallets,Consig Pallets,Consig Cont,Proveedor,Activo\n';
        rows.forEach(r => {
          csv += `${r.ID_Ruta || ''},${r.cve_ruta || ''},"${(r.descripcion || '').replace(/"/g, '""')}",${r.status || ''},${r.cve_almacenp || ''},${r.venta_preventa || 0},${r.es_entrega || 0},${r.control_pallets_cont || 'N'},${r.consig_pallets || 0},${r.consig_cont || 0},${r.ID_Proveedor || ''},${r.Activo || 1}\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'rutas_export.csv';
        a.click();
        URL.revokeObjectURL(a.href);
      });
  }
  function descargarLayout() {
    const header = 'Clave,Descripción,Status,Almacén,Preventa,Entrega,Control Pallets,Consig Pallets,Consig Cont,Proveedor,Activo\n';
    const blob = new Blob([header], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'rutas_layout.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function abrirImport() {
    fileCsv.value = '';
    csvPreviewWrap.style.display = 'none';
    importMsg.style.display = 'none';
    document.getElementById('btnImportarFinal').style.display = 'none';
    mdlImport.style.display = 'block';
  }
  function previsualizarCsv() {
    const f = fileCsv.files[0];
    if (!f) { alert('Selecciona un CSV'); return; }
    const r = new FileReader();
    r.onload = e => {
      const rows = e.target.result.split('\n').filter(x => x.trim() !== '');
      csvHead.innerHTML = '<tr>' + rows[0].split(',').map(h => `<th>${h}</th>`).join('') + '</tr>';
      csvBody.innerHTML = rows.slice(1, 6).map(r => '<tr>' + r.split(',').map(c => `<td>${c}</td>`).join('') + '</tr>').join('');
      csvPreviewWrap.style.display = 'block';
      importMsg.style.display = 'none';
      document.getElementById('btnImportarFinal').style.display = 'block';
    };
    r.readAsText(f);
  }
  function importarCsv() {
    const fd = new FormData();
    fd.append('file', fileCsv.files[0]);

    fetch(API + '?action=import_csv', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        importMsg.style.display = 'flex';
        if (!resp.success) {
          importMsg.className = 'ap-chip warn';
          importMsg.innerHTML = `<b>Error:</b> ${resp.message || 'Error desconocido'}`;
          return;
        }
        importMsg.className = 'ap-chip ok';
        importMsg.innerHTML = `<b>Importación:</b> OK ${resp.total_ok || 0} | Err ${resp.total_err || 0}`;
        setTimeout(() => { cerrarModal('mdlImport'); cargar(); }, 2000);
      });
  }

  function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

  document.addEventListener('DOMContentLoaded', () => {
    selPerPage.value = '25';
    cargar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>