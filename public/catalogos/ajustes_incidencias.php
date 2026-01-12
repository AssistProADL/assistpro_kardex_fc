<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO – AJUSTES | INCIDENCIAS
========================================================= */
  body {
    font-family: system-ui, -apple-system, sans-serif;
    background: #f4f6fb;
    margin: 0;
  }

  .ap-container {
    padding: 20px;
    font-size: 13px;
    max-width: 1800px;
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
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    cursor: pointer;
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
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
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

  button.ap-chip {
    font-family: inherit;
  }

  /* REQ INDICATOR */
  .ap-req-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #dc3545;
  }

  .ap-req-ok {
    background: #198754;
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
    width: 900px;
    max-width: 95%;
    max-height: 90vh;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    padding: 20px;
  }

  .ap-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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
  <div class="ap-title"><i class="fa fa-tools"></i> Catálogo - Ajustes | Incidencias</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <div class="ap-chip" id="scopeLabel"><i class="fa fa-filter"></i> Tipo Incidencia: <b>Todos</b></div>
      <div class="ap-chip" id="inacLabel"><i class="fa fa-eye"></i> Mostrando: <b>Activos</b></div>
    </div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar motivo…" onkeydown="if(event.key==='Enter')buscar()">
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
          <th>Req</th>
          <th>ID</th>
          <th>Tipo</th>
          <th>Motivo</th>
          <th>Clasificación</th>
          <th>Dev. Proveedor</th>
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
      <h3 style="margin:0"><i class="fa fa-tools"></i> Motivo - Ajustes/Incidencias</h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span> Obligatorio:
      <b>Motivo</b>
    </div>

    <input type="hidden" id="id">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Tipo Catálogo</div>
        <div class="ap-input"><i class="fa fa-tag"></i>
          <input id="Tipo_Cat" value="A" readonly>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Motivo *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i>
          <input id="Des_Motivo" placeholder="Ej. Diferencia de inventario / Daño / Faltante...">
        </div>
        <div class="ap-error" id="err_motivo">Motivo obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clasificación *</div>
        <div class="ap-input"><i class="fa fa-sitemap"></i>
          <select id="scope">
            <option value="DP">Devolver a Proveedor</option>
            <option value="CI">Cierre de Incidencia</option>
          </select>
        </div>
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

      <div class="ap-field" style="grid-column: span 2">
        <div class="ap-label">Dev. Proveedor (auto)</div>
        <div class="ap-input"><i class="fa fa-truck-loading"></i>
          <select id="dev_proveedor" disabled>
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
        <div class="ap-chip warn" style="margin-top:4px;cursor:default">
          Se calcula automáticamente por "Clasificación".
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
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar ajustes/incidencias</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>(Tipo + Motivo)</b>. Columnas: <b>ID, Tipo, Motivo,
        Clasificación, Activo</b>.</div>

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
  const API = '../api/ajustes_incidencias.php';
  const KPI = '../api/ajustes_incidencias_kpi.php';

  let filtroScope = '';
  let verInactivos = false;
  let qLast = '';
  let page = 1;
  let perPage = 25;
  let total = 0;
  let lastRows = [];

  /* ===== Escape HTML ===== */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function scopeTxt(s) {
    if (s === 'DP') return 'Devolver a Proveedor';
    if (s === 'CI') return 'Cierre de Incidencia';
    return 'Todos';
  }

  function reqDot(r) {
    const ok = !!(String(r.Des_Motivo || '').trim() !== '');
    return '<span class="ap-req-dot ' + (ok ? 'ap-req-ok' : '') + '" title="' + (ok ? 'OK' : 'Faltan obligatorios') + '"></span>';
  }

  function clasifFromRow(r) {
    return (Number(r.dev_proveedor || 0) === 1) ? 'DP' : 'CI';
  }

  /* Cards KPI por scope */
  function loadCards() {
    fetch(KPI + '?action=kpi').then(r => r.json()).then(rows => {
      let totalAll = 0, actAll = 0, inaAll = 0, badAll = 0;

      rows.forEach(x => {
        totalAll += Number(x.total || 0) || 0;
        actAll += Number(x.activos || 0) || 0;
        inaAll += Number(x.inactivos || 0) || 0;
        badAll += Number(x.inconsistentes || 0) || 0;
      });

      let h = '';
      h += '<div class="ap-card" onclick="setScope(\'\')">'
        + '<div class="h">'
        + '<b><i class="fa fa-layer-group"></i> Todos</b>'
        + '<span class="ap-chip ok">' + actAll + ' Act</span>'
        + '</div>'
        + '<div class="k">'
        + '<span class="ap-chip">Total: ' + totalAll + '</span>'
        + '<span class="ap-chip warn">Inac: ' + inaAll + '</span>'
        + '<span class="ap-chip">Bad: ' + badAll + '</span>'
        + '</div></div>';

      rows.forEach(x => {
        const s = String(x.scope || '');
        h += '<div class="ap-card" onclick="setScope(\'' + s + '\')">'
          + '<div class="h">'
          + '<b><i class="fa fa-sitemap"></i> ' + escapeHtml(scopeTxt(s)) + '</b>'
          + '<span class="ap-chip ok">' + x.activos + ' Act</span>'
          + '</div>'
          + '<div class="k">'
          + '<span class="ap-chip">Total: ' + x.total + '</span>'
          + '<span class="ap-chip warn">Inac: ' + x.inactivos + '</span>'
          + '<span class="ap-chip">Bad: ' + x.inconsistentes + '</span>'
          + '</div></div>';
      });

      cards.innerHTML = h || '<div class="ap-chip warn">Sin datos</div>';
    });
  }

  function setScope(s) {
    filtroScope = s || '';
    scopeLabel.innerHTML = '<i class="fa fa-filter"></i> Tipo Incidencia: <b>' + (filtroScope ? escapeHtml(scopeTxt(filtroScope)) : 'Todos') + '</b> ' + (filtroScope ? '<span class="ap-chip" style="cursor:pointer" onclick="setScope(\'\')">Quitar</span>' : '');
    page = 1; cargar();
  }

  /* ===== Paginación ===== */
  function setPager() {
    const start = total > 0 ? ((page - 1) * perPage + (lastRows.length ? 1 : 0)) : 0;
    let end = total > 0 ? Math.min(page * perPage, total) : 0;
    if (total === 0) { end = 0; }

    lblRange.innerText = 'Mostrando ' + start + '–' + end + (total > 0 ? ' de ' + total : '');

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
    // Construir URL de forma segura sin template strings para compatibilidad
    const offset = (page - 1) * perPage;
    const url = API + '?action=list'
      + '&scope=' + encodeURIComponent(filtroScope || '')
      + '&inactivos=' + (verInactivos ? 1 : 0)
      + '&q=' + encodeURIComponent(qLast || '')
      + '&limit=' + encodeURIComponent(perPage)
      + '&offset=' + encodeURIComponent(offset);

    fetch(url).then(r => r.json()).then(resp => {
      const rows = resp.rows || [];
      total = Number(resp.total || 0) || 0;
      lastRows = rows;

      let h = '';
      rows.forEach(r => {
        const ajusteId = parseInt(r.id) || 0;
        const sc = clasifFromRow(r);
        const isActivo = Number(r.Activo || 0) === 1;
        const isDev = Number(r.dev_proveedor || 0) === 1;

        let btns = '';
        if (verInactivos) {
          btns = '<i class="fa fa-undo" title="Recuperar" onclick="recuperar(' + ajusteId + ')"></i>';
        } else {
          btns = '<i class="fa fa-edit" title="Editar" onclick="editar(' + ajusteId + ')"></i>'
            + '<i class="fa fa-trash" title="Inactivar" onclick="eliminar(' + ajusteId + ')"></i>';
        }

        h += '<tr>'
          + '<td class="ap-actions">' + btns + '</td>'
          + '<td>' + reqDot(r) + '</td>'
          + '<td>' + escapeHtml(String(r.id || '')) + '</td>'
          + '<td>' + escapeHtml(String(r.Tipo_Cat || 'A')) + '</td>'
          + '<td>' + escapeHtml(String(r.Des_Motivo || '')) + '</td>'
          + '<td>' + escapeHtml(scopeTxt(sc)) + '</td>'
          + '<td>' + (isDev ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip">No</span>') + '</td>'
          + '<td>' + (isActivo ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip warn">No</span>') + '</td>'
          + '</tr>';
      });
      tb.innerHTML = h || '<tr><td colspan="8" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>';
      inacLabel.innerHTML = '<i class="fa fa-eye"></i> Mostrando: <b>' + (verInactivos ? 'Inactivos' : 'Activos') + '</b>';
      setPager();
    });
  }

  function buscar() { qLast = q.value.trim(); page = 1; cargar(); }
  function limpiar() { q.value = ''; qLast = ''; page = 1; cargar(); }
  function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }

  function hideErrors() { err_motivo.style.display = 'none'; }

  function validar() {
    hideErrors();
    let ok = true;
    if (!Des_Motivo.value.trim()) { err_motivo.style.display = 'block'; ok = false; }
    return ok;
  }

  function syncDevProveedor() {
    dev_proveedor.value = (scope.value === 'DP') ? '1' : '0';
  }

  function nuevo() {
    id.value = '';
    Tipo_Cat.value = 'A';
    Des_Motivo.value = '';
    scope.value = 'DP';
    Activo.value = '1';
    syncDevProveedor();
    hideErrors();
    mdl.style.display = 'block';
  }

  function editar(rid) {
    fetch(API + '?action=get&id=' + rid).then(r => r.json()).then(x => {
      id.value = x.id || '';
      Tipo_Cat.value = 'A';
      Des_Motivo.value = x.Des_Motivo || '';
      Activo.value = String(x.Activo ?? '1');
      scope.value = (Number(x.dev_proveedor || 0) === 1) ? 'DP' : 'CI';
      syncDevProveedor();
      hideErrors();
      mdl.style.display = 'block';
    });
  }

  function guardar() {
    if (!validar()) return;

    const fd = new FormData();
    fd.append('action', id.value ? 'update' : 'create');
    fd.append('id', id.value);
    fd.append('Tipo_Cat', 'A');
    fd.append('Des_Motivo', Des_Motivo.value);
    fd.append('scope', scope.value);
    fd.append('dev_proveedor', dev_proveedor.value);
    fd.append('Activo', Activo.value);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.error) {
          alert('Error: ' + resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
          return;
        }
        alert('Motivo guardado correctamente');
        cerrarModal('mdl');
        loadCards();
        cargar();
      })
      .catch(err => {
        console.error('Error en fetch:', err);
        alert('Error de red: ' + err.message);
      });
  }

  function eliminar(rid) {
    if (!confirm('¿Inactivar motivo?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', rid);
    fetch(API, { method: 'POST', body: fd }).then(() => { loadCards(); cargar(); });
  }

  function recuperar(rid) {
    const fd = new FormData(); fd.append('action', 'restore'); fd.append('id', rid);
    fetch(API, { method: 'POST', body: fd }).then(() => { loadCards(); cargar(); });
  }

  function exportarDatos() { window.open(API + '?action=export_csv&tipo=datos', '_blank'); }
  function descargarLayout() { window.open(API + '?action=export_csv&tipo=layout', '_blank'); }

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
      // Generación segura de HTML
      const headers = rows[0].split(',').map(h => '<th>' + h + '</th>').join('');
      csvHead.innerHTML = '<tr>' + headers + '</tr>';

      const bodyRows = rows.slice(1, 6).map(r => {
        const cells = r.split(',').map(c => '<td>' + c + '</td>').join('');
        return '<tr>' + cells + '</tr>';
      }).join('');
      csvBody.innerHTML = bodyRows;

      csvPreviewWrap.style.display = 'block';
      importMsg.style.display = 'none';
      document.getElementById('btnImportarFinal').style.display = 'block';
    };
    r.readAsText(f);
  }

  function importarCsv() {
    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('file', fileCsv.files[0]);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        importMsg.style.display = 'flex';
        if (resp.error) {
          importMsg.className = 'ap-chip warn';
          importMsg.innerHTML = '<b>Error:</b> ' + resp.error;
          return;
        }
        importMsg.className = 'ap-chip ok';
        importMsg.innerHTML = '<b>Importación:</b> OK ' + (resp.rows_ok || 0) + ' | Err ' + (resp.rows_err || 0);
        setTimeout(() => { cerrarModal('mdlImport'); loadCards(); cargar(); }, 2000);
      });
  }

  function cerrarModal(mid) { document.getElementById(mid).style.display = 'none'; }

  document.addEventListener('DOMContentLoaded', () => {
    console.log('Ajustes Incidencias Fixed Version loaded');
    selPerPage.value = '25';
    scope.addEventListener('change', syncDevProveedor);
    loadCards();
    cargar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>