<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO STYLES - Rutas
   ========================================================= */
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #e9ecef;
    margin: 0;
    color: #212529;
  }

  .ap-container {
    padding: 15px;
    font-size: 12px;
    max-width: 100%;
    margin: 0 auto;
  }

  /* TITLE */
  .ap-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* CARDS */
  .ap-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
  }

  .ap-card {
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 12px 15px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s;
  }

  .ap-card:hover {
    border-color: #adb5bd;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  }

  .ap-card .h {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-weight: 600;
    color: #6c757d;
    font-size: 12px;
  }

  .ap-card .k {
    font-size: 24px;
    font-weight: 700;
    color: #212529;
  }

  /* TOOLBAR */
  .ap-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 15px;
    background: #fff;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
  }

  .ap-search {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 250px;
    background: #fff;
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #ced4da;
  }

  .ap-search i {
    color: #6c757d;
  }

  .ap-search input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 12px;
    color: #212529;
  }

  /* CHIPS */
  .ap-chip {
    font-size: 11px;
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 6px 12px;
    display: inline-flex;
    gap: 6px;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    user-select: none;
  }

  .ap-chip:hover {
    background: #e9ecef;
    border-color: #adb5bd;
  }

  .ap-chip.primary {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
  }

  .ap-chip.primary:hover {
    background: #0b5ed7;
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

  /* BUTTONS */
  button.ap-chip {
    font-family: inherit;
  }

  /* GRID */
  .ap-grid {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .ap-grid-wrapper {
    max-height: calc(100vh - 280px);
    overflow-y: auto;
  }

  .ap-grid table {
    width: 100%;
    border-collapse: collapse;
  }

  .ap-grid th {
    background: #f8f9fa;
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 11px;
  }

  .ap-grid td {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
    vertical-align: middle;
    white-space: nowrap;
    font-size: 11px;
  }

  .ap-grid tr:hover td {
    background: #f8f9fa;
  }

  .ap-actions i {
    cursor: pointer;
    margin-right: 8px;
    color: #6c757d;
    transition: color 0.2s;
    font-size: 13px;
  }

  .ap-actions i:hover {
    color: #0d6efd;
  }

  /* PAGER */
  .ap-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    padding: 0 5px;
    font-size: 11px;
    color: #6c757d;
  }

  .ap-pager-controls {
    display: flex;
    gap: 5px;
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
    width: 800px;
    max-width: 95%;
    max-height: 90vh;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    padding: 20px;
  }

  .ap-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
  }

  .ap-modal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .ap-modal-header button {
    background: transparent;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
  }

  .ap-modal-header button:hover {
    background: #f8f9fa;
    color: #212529;
  }

  .ap-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 10px;
  }

  .ap-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .ap-label {
    font-weight: 500;
    font-size: 11px;
    color: #495057;
  }

  .ap-input {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 6px 10px;
    background: #fff;
    transition: all 0.2s;
  }

  .ap-input:focus-within {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
  }

  .ap-input i {
    color: #6c757d;
    font-size: 12px;
  }

  .ap-input input,
  .ap-input select {
    border: none;
    outline: none;
    width: 100%;
    font-size: 12px;
    color: #212529;
    background: transparent;
    font-family: inherit;
  }

  /* MODAL FOOTER */
  .ap-modal-footer {
    text-align: right;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  button.primary {
    background: #0d6efd;
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 12px;
  }

  button.primary:hover {
    background: #0b5ed7;
  }

  button.ghost {
    background: #fff;
    color: #495057;
    border: 1px solid #dee2e6;
    padding: 6px 14px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 12px;
  }

  button.ghost:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-route"></i> Catálogo de Rutas</div>

  <div class="ap-cards" id="kpiCards">
    <!-- KPIs loaded via JS -->
  </div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, descripción..." onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="border-left:1px solid #dee2e6; height:24px; margin:0 5px;"></div>

    <button class="ap-chip" id="btnToggleInactive" onclick="toggleInactivos()">
      <i class="fa fa-eye"></i> Ver Inactivos
    </button>

    <div style="flex:1"></div>

    <button class="ap-chip primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip" onclick="exportarCSV()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar CSV</button>
  </div>

  <span class="ap-chip" id="msg" style="display:none; margin-bottom:10px;"></span>

  <!-- GRID -->
  <div class="ap-grid">
    <div class="ap-grid-wrapper">
      <table>
        <thead>
          <tr>
            <th>Acciones</th>
            <th>ID</th>
            <th>Clave</th>
            <th>Descripción</th>
            <th>Almacén</th>
            <th>Status</th>
            <th>Preventa</th>
            <th>Entrega</th>
            <th>Activo</th>
          </tr>
        </thead>
        <tbody id="tb"></tbody>
      </table>
    </div>
  </div>

  <!-- PAGER -->
  <div id="pager" class="ap-pager"></div>
</div>

<!-- MODAL EDIT/NEW -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div class="ap-modal-header">
      <h3><i class="fa fa-route"></i> <span id="mdlTitle">Ruta</span></h3>
      <button onclick="cerrarModal('mdl')"><i class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="ID_Ruta" value="0">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Clave Ruta</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="cve_ruta" placeholder="Clave única"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Descripción</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="descripcion"
            placeholder="Descripción de la ruta"></div>
      </div>

      <div class="ap-field" style="grid-column: span 2">
        <div class="ap-label">Almacén (Base)</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <select id="cve_almacenp">
            <option value="0">Seleccione...</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Status</div>
        <div class="ap-input"><i class="fa fa-traffic-light"></i>
          <select id="status">
            <option value="A">Activo (A)</option>
            <option value="B">Baja (B)</option>
          </select>
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">¿Es Preventa?</div>
        <div class="ap-input"><i class="fa fa-user-clock"></i>
          <select id="venta_preventa">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">¿Es Entrega?</div>
        <div class="ap-input"><i class="fa fa-truck"></i>
          <select id="es_entrega">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Control Pallets (Cont)</div>
        <div class="ap-input"><i class="fa fa-box-open"></i>
          <select id="control_pallets_cont">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Consig. Pallets</div>
        <div class="ap-input"><i class="fa fa-pallet"></i><input id="consig_pallets" type="number" placeholder="0">
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Consig. Contenedores</div>
        <div class="ap-input"><i class="fa fa-box"></i><input id="consig_cont" type="number" placeholder="0"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">ID Proveedor</div>
        <div class="ap-input"><i class="fa fa-truck-field"></i><input id="ID_Proveedor" type="number" placeholder="0">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
    </div>

    <div class="ap-modal-footer">
      <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
      <button class="primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content" style="width:600px">
    <div class="ap-modal-header">
      <h3><i class="fa fa-upload"></i> Importar CSV</h3>
      <button onclick="cerrarModal('mdlImport')"><i class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px; display:block; text-align:center;">
      Layout: cve_ruta, descripcion, status, cve_almacenp, venta_preventa, es_entrega, ...
    </div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="csvFile" accept=".csv">
    </div>

    <div id="importResult" style="margin-top:15px;"></div>

    <div class="ap-modal-footer">
      <button class="primary" onclick="importarCSV()"><i class="fa fa-cloud-arrow-up"></i> Importar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/rutas_api.php';
  let curPage = 1;
  let viewInactive = false;

  // Render Helpers
  function esc(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[m]));
  }

  function showMsg(txt, cls = '') {
    const m = document.getElementById('msg');
    m.style.display = 'inline-flex';
    m.className = 'ap-chip ' + cls;
    m.innerHTML = txt;
    setTimeout(() => { m.style.display = 'none' }, 3500);
  }

  function abrirModal(id) { document.getElementById(id).style.display = 'block'; }
  function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

  function toggleInactivos() {
    viewInactive = !viewInactive;
    const btn = document.getElementById('btnToggleInactive');
    if (viewInactive) {
      btn.classList.add('warn');
      btn.innerHTML = '<i class="fa fa-eye-slash"></i> Ocultar Inactivos';
    } else {
      btn.classList.remove('warn');
      btn.innerHTML = '<i class="fa fa-eye"></i> Ver Inactivos';
    }
    refrescar(1);
  }

  // Cargar lista
  function refrescar(p = 1) {
    curPage = p;
    const q = document.getElementById('q').value;

    const params = new URLSearchParams();
    params.append('action', 'list');
    params.append('page', curPage);
    params.append('pageSize', 25);
    params.append('q', q);
    params.append('show_inactivos', viewInactive ? 1 : 0);

    fetch(API + '?' + params.toString())
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          const kpis = d.kpis || {};
          const inactivosCount = parseInt(kpis.inactivos || 0);

          // Lógica botón
          const btn = document.getElementById('btnToggleInactive');
          if (inactivosCount > 0) {
            btn.style.display = 'inline-flex';
          } else {
            btn.style.display = 'none';
            // Si estábamos viendo inactives y ya no hay (se reactivaron todos),
            // volvemos a la vista normal automáticamente
            if (viewInactive) {
              viewInactive = false;
              btn.classList.remove('warn');
              btn.innerHTML = '<i class="fa fa-eye"></i> Ver Inactivos';
              refrescar(1); // Recarga recursiva una vez para volver a actives
              return;
            }
          }

          renderGrid(d.data || []);
          renderPager(d.total, d.pageSize);
          renderKPIs(kpis);
        } else {
          showMsg('Error cargando lista', 'warn');
        }
      })
      .catch(e => console.error(e));
  }

  function renderGrid(rows) {
    const tb = document.getElementById('tb');
    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
      return;
    }

    tb.innerHTML = rows.map(r => {
      const activo = parseInt(r.Activo ?? 1);
      const isOk = activo === 1;
      const statusCls = isOk ? 'ok' : 'warn';
      const statusTxt = isOk ? 'Activo' : 'Inactivo';
      const rowStyle = !isOk ? 'background:#f8f9fa;color:#adb5bd' : '';

      return `<tr style="${rowStyle}">
        <td class="ap-actions">
           <i class="fa fa-pen" title="Editar" onclick="editar(${r.ID_Ruta})"></i>
           ${isOk ? `<i class="fa fa-ban" title="Baja" onclick="eliminar(${r.ID_Ruta})"></i>`
          : `<i class="fa fa-rotate-left" title="Recuperar" onclick="recuperar(${r.ID_Ruta})"></i>`}
        </td>
        <td>${esc(r.ID_Ruta)}</td>
        <td><b>${esc(r.cve_ruta)}</b></td>
        <td>${esc(r.descripcion)}</td>
        <td>${esc(r.cve_almacenp)}</td>
        <td>${esc(r.status)}</td>
        <td>${r.venta_preventa == 1 ? '<i class="fa fa-check text-success"></i>' : '-'}</td>
        <td>${r.es_entrega == 1 ? '<i class="fa fa-check text-success"></i>' : '-'}</td>
        <td><span class="ap-chip ${statusCls}" style="padding:2px 8px;font-size:10px">${statusTxt}</span></td>
      </tr>`;
    }).join('');
  }

  function renderPager(total, pageSize) {
    const p = document.getElementById('pager');
    const totalPages = Math.ceil(total / pageSize);
    const start = total > 0 ? (curPage - 1) * pageSize + 1 : 0;
    const end = Math.min(curPage * pageSize, total);

    const prev = curPage > 1 ? `<button class="ap-chip" onclick="refrescar(${curPage - 1})"><i class="fa fa-chevron-left"></i></button>` : '';
    const next = curPage < totalPages ? `<button class="ap-chip" onclick="refrescar(${curPage + 1})"><i class="fa fa-chevron-right"></i></button>` : '';

    p.innerHTML = `
      <div class="ap-pager-info">Mostrando ${start}-${end} de ${total} registros</div>
      <div class="ap-pager-controls">
        ${prev}
        <span class="ap-chip" style="cursor:default">Página ${curPage}</span>
        ${next}
      </div>
    `;
  }

  function renderKPIs(k) {
    const c = document.getElementById('kpiCards');
    c.innerHTML = `
      <div class="ap-card" onclick="refrescar(1)">
        <div class="h">Total Rutas <i class="fa fa-list"></i></div>
        <div class="k">${k.total || 0}</div>
      </div>
      <div class="ap-card">
        <div class="h">Activas <i class="fa fa-check-circle" style="color:#198754"></i></div>
        <div class="k">${k.activos || 0}</div>
      </div>
      <div class="ap-card">
        <div class="h">Preventa <i class="fa fa-user-clock"></i></div>
        <div class="k">${k.preventa || 0}</div>
      </div>
      <div class="ap-card">
        <div class="h">Entrega <i class="fa fa-truck"></i></div>
        <div class="k">${k.entrega || 0}</div>
      </div>
    `;
  }

  // --- CRUD Operations ---

  function cargarAlmacenes() {
    fetch(API + '?action=almacenes')
      .then(r => r.json())
      .then(j => {
        if (j.success && j.data) {
          const sel = document.getElementById('cve_almacenp');
          const current = sel.value;
          sel.innerHTML = '<option value="0">Seleccione...</option>' +
            j.data.map(a => `<option value="${a.id}">${a.nombre}</option>`).join('');
          if (current) sel.value = current;
        }
      });
  }

  function nuevo() {
    document.getElementById('mdlTitle').innerText = 'Nueva Ruta';
    ['ID_Ruta', 'cve_ruta', 'descripcion', 'consig_pallets', 'consig_cont', 'ID_Proveedor'].forEach(id => document.getElementById(id).value = '');

    document.getElementById('ID_Ruta').value = 0;
    // Defaults
    document.getElementById('cve_almacenp').value = 0;
    document.getElementById('status').value = 'A';
    document.getElementById('venta_preventa').value = 1;
    document.getElementById('es_entrega').value = 0;
    document.getElementById('control_pallets_cont').value = 'N';
    document.getElementById('Activo').value = 1;

    abrirModal('mdl');
  }

  function editar(id) {
    fetch(API + '?action=get&id=' + id)
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          const d = j.data;
          document.getElementById('mdlTitle').innerText = 'Editar Ruta ' + d.cve_ruta;

          document.getElementById('ID_Ruta').value = d.ID_Ruta;
          document.getElementById('cve_ruta').value = d.cve_ruta;
          document.getElementById('descripcion').value = d.descripcion;

          // Select Almacen
          document.getElementById('cve_almacenp').value = d.cve_almacenp || 0;

          document.getElementById('status').value = d.status;
          document.getElementById('venta_preventa').value = d.venta_preventa;
          document.getElementById('es_entrega').value = d.es_entrega;
          document.getElementById('control_pallets_cont').value = d.control_pallets_cont;

          document.getElementById('consig_pallets').value = d.consig_pallets;
          document.getElementById('consig_cont').value = d.consig_cont;
          document.getElementById('ID_Proveedor').value = d.ID_Proveedor || '';
          document.getElementById('Activo').value = d.Activo;

          abrirModal('mdl');
        } else {
          showMsg('No encontrado', 'warn');
        }
      });
  }

  function guardar() {
    const id = document.getElementById('ID_Ruta').value;
    const cve = document.getElementById('cve_ruta').value.trim();
    const des = document.getElementById('descripcion').value.trim();
    const alm = document.getElementById('cve_almacenp').value;

    if (!cve || !des) { showMsg('Clave y Descripción requeridos', 'warn'); return; }
    if (alm == 0) { showMsg('Seleccione un Almacén', 'warn'); return; }

    const data = {
      ID_Ruta: id,
      cve_ruta: cve,
      descripcion: des,
      cve_almacenp: alm,
      status: document.getElementById('status').value,
      venta_preventa: document.getElementById('venta_preventa').value,
      es_entrega: document.getElementById('es_entrega').value,
      control_pallets_cont: document.getElementById('control_pallets_cont').value,
      consig_pallets: document.getElementById('consig_pallets').value,
      consig_cont: document.getElementById('consig_cont').value,
      ID_Proveedor: document.getElementById('ID_Proveedor').value,
      Activo: document.getElementById('Activo').value
    };

    fetch(API + '?action=save', {
      method: 'POST',
      body: JSON.stringify(data)
    })
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          showMsg('Guardado correctamente', 'ok');
          cerrarModal('mdl');
          refrescar(curPage);
        } else {
          showMsg(j.message || 'Error al guardar', 'warn');
        }
      });
  }

  function eliminar(id) {
    if (!confirm('¿Inactivar ruta?')) return;
    fetch(API + '?action=delete&id=' + id)
      .then(r => r.json())
      .then(j => {
        if (j.success) { showMsg('Ruta inactivada', 'ok'); refrescar(curPage); }
        else showMsg('Error al eliminar', 'warn');
      });
  }

  function recuperar(id) {
    fetch(API + '?action=recover&id=' + id, { method: 'POST' })
      .then(r => r.json())
      .then(j => {
        if (j.success) { showMsg('Recuperado', 'ok'); refrescar(curPage); }
        else showMsg(j.message || 'Error al recuperar', 'warn');
      });
  }

  function buscar() { refrescar(1); }
  function limpiar() { document.getElementById('q').value = ''; refrescar(1); }
  function exportarCSV() { window.open(API + '?action=layout_csv', '_blank'); } // Usar layout o crear uno nuevo de export

  // Imports
  function abrirImport() {
    document.getElementById('csvFile').value = '';
    document.getElementById('importResult').innerHTML = '';
    abrirModal('mdlImport');
  }

  function importarCSV() {
    const f = document.getElementById('csvFile').files[0];
    if (!f) { alert('Selecciona archivo'); return; }
    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('file', f);

    document.getElementById('importResult').innerHTML = 'Calculando...';

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        const d = document.getElementById('importResult');
        if (j.success) {
          d.innerHTML = `<div class="ap-chip ok">Importados: ${j.total_ok} <br> Errores: ${j.total_err}</div>`;
          setTimeout(() => { cerrarModal('mdlImport'); refrescar(1); }, 3000);
        } else {
          d.innerHTML = `<div style="color:red">${j.message}</div>`;
        }
      });
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    cargarAlmacenes();
    refrescar(1);
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>