<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Motivos No Venta</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* =========================================================
           ASSISTPRO – BASE
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
      cursor: pointer;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
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

    /* Force flex for centering */
    .ap-modal-content {
      background: #fff;
      width: 600px;
      max-width: 95%;
      max-height: 90vh;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .ap-modal-header {
      padding: 15px 20px;
      border-bottom: 1px solid #e9ecef;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
    }

    .ap-modal-header h3 {
      margin: 0;
      font-size: 18px;
      color: #212529;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .ap-modal-body {
      padding: 25px;
      overflow-y: auto;
    }

    .ap-form {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
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

    .ap-modal-footer {
      padding: 15px 20px;
      background: #f8f9fa;
      border-top: 1px solid #e9ecef;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
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

    #csvPreviewWrap .ap-grid {
      margin-top: 15px;
      height: 300px;
      display: block;
      overflow: auto;
    }
  </style>
</head>

<body>

  <div class="ap-container">
    <div class="ap-title"><i class="fa fa-ban"></i> Motivos No Venta</div>

    <div class="ap-cards">
      <div class="ap-card" id="card1" style="cursor: default;">
        <div class="h">
          <span><i class="fa fa-chart-pie"></i> Resumen</span>
          <span class="ap-chip ok" id="kpiAct">0 Act</span>
        </div>
        <div class="k">
          <span class="ap-chip" id="kpiTot">Total: 0</span>
          <span class="ap-chip warn" id="kpiInac">Inac: 0</span>
          <span class="ap-chip" id="kpiBad">Bad: 0</span>
        </div>
      </div>

      <div class="ap-card" onclick="verInactivos=false;page=1;cargar();">
        <div class="h">
          <span><i class="fa fa-check-circle"></i> Activos</span>
          <span class="ap-chip ok">Ver</span>
        </div>
        <div class="k">
          <span class="ap-chip">Status=1</span>
        </div>
      </div>

      <div class="ap-card" onclick="verInactivos=true;page=1;cargar();">
        <div class="h">
          <span><i class="fa fa-eye"></i> Inactivos</span>
          <span class="ap-chip warn">Ver</span>
        </div>
        <div class="k">
          <span class="ap-chip">Status=0</span>
        </div>
      </div>

      <div class="ap-card" onclick="abrirImport();">
        <div class="h">
          <span><i class="fa fa-upload"></i> Importación</span>
          <span class="ap-chip">CSV</span>
        </div>
        <div class="k">
          <span class="ap-chip">UPSERT por Clave</span>
        </div>
      </div>
    </div>

    <div class="ap-toolbar">
      <div class="ap-search">
        <i class="fa fa-search"></i>
        <input id="q" placeholder="Buscar por Clave o Motivo…" onkeydown="if(event.key==='Enter')buscar()">
      </div>
      <button class="ap-chip" onclick="buscar()">Buscar</button>
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>

      <div style="flex:1"></div>

      <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
      <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
      <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-filter"></i> Toggle Inactivos</button>
    </div>

    <div class="ap-grid">
      <table>
        <thead>
          <tr>
            <th>Acciones</th>
            <th>Req</th>
            <th>ID</th>
            <th>Clave</th>
            <th>Motivo</th>
            <th>Status</th>
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

  <!-- MODAL CRUD -->
  <div class="ap-modal" id="mdl">
    <div class="ap-modal-content">
      <div class="ap-modal-header">
        <h3><i class="fa fa-ban"></i> Motivo No Venta</h3>
        <button onclick="cerrarModal('mdl')"
          style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
            class="fa fa-times"></i></button>
      </div>

      <div class="ap-modal-body">
        <div style="margin-bottom:15px; font-size:12px; color:#666;">
          <span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Clave</b>, <b>Motivo</b>
        </div>

        <input type="hidden" id="IdMot">

        <div class="ap-form">
          <div class="ap-field">
            <div class="ap-label">Clave *</div>
            <div class="ap-input"><i class="fa fa-hashtag"></i><input id="Clave" placeholder="MNV-001"></div>
            <div class="ap-error" id="err_clave">Clave obligatoria.</div>
          </div>

          <div class="ap-field">
            <div class="ap-label">Motivo *</div>
            <div class="ap-input"><i class="fa fa-align-left"></i><input id="Motivo"
                placeholder="Cliente no estaba / Sin stock / No recibió..."></div>
            <div class="ap-error" id="err_motivo">Motivo obligatorio.</div>
          </div>

          <div class="ap-field">
            <div class="ap-label">Status</div>
            <div class="ap-input"><i class="fa fa-toggle-on"></i>
              <select id="Status">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
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
    <div class="ap-modal-content" style="width:700px;">
      <div class="ap-modal-header">
        <h3><i class="fa fa-upload"></i> Importar Motivos No Venta</h3>
        <button onclick="cerrarModal('mdlImport')"
          style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
            class="fa fa-times"></i></button>
      </div>

      <div class="ap-modal-body">
        <div class="ap-chip" style="width:100%; justify-content:center; margin-bottom:15px;">Layout FULL con UPSERT por
          <b>Clave</b>. Previsualiza antes de importar.
        </div>

        <div class="ap-input">
          <i class="fa fa-file-csv"></i>
          <input type="file" id="fileCsv" accept=".csv">
        </div>

        <div style="margin-top:15px; display:flex; gap:10px;">
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

          <div class="ap-chip" id="importMsg"
            style="margin-top:15px; width:100%; display:none; justify-content:center;"></div>
        </div>
      </div>

      <div class="ap-modal-footer">
        <button class="ghost" onclick="cerrarModal('mdlImport')">Cerrar</button>
        <button class="primary" onclick="importarCsv()" id="btnImportarFinal" style="display:none;">Importar</button>
      </div>
    </div>
  </div>

  <script>
    const API = '../api/motivos_no_venta.php';
    const KPI = '../api/motivos_no_venta_kpi.php';

    let verInactivos = false;
    let qLast = '';

    let page = 1;
    let perPage = 25;
    let total = 0;
    let lastRows = [];

    function reqDot(r) {
      const ok = !!(String(r.Clave || '').trim() !== '' && String(r.Motivo || '').trim() !== '');
      return `<span class="ap-req-dot ${ok ? 'ap-req-ok' : ''}" title="${ok ? 'OK' : 'Faltan obligatorios'}"></span>`;
    }

    /* ===== KPI ===== */
    function loadKpi() {
      fetch(KPI + '?action=kpi').then(r => r.json()).then(k => {
        if (k.error) { console.error('KPI Error:', k.error); return; }
        const tot = Number(k.total || 0) || 0;
        const act = Number(k.activos || 0) || 0;
        const ina = Number(k.inactivos || 0) || 0;
        const bad = Number(k.inconsistentes || 0) || 0;
        if (document.getElementById('kpiTot')) kpiTot.innerText = 'Total: ' + tot;
        if (document.getElementById('kpiAct')) kpiAct.innerText = act + ' Act';
        if (document.getElementById('kpiInac')) kpiInac.innerText = 'Inac: ' + ina;
        if (document.getElementById('kpiBad')) kpiBad.innerText = 'Bad: ' + bad;
      });
    }

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

    /* ===== Listado ===== */
    function cargar() {
      const offset = (page - 1) * perPage;
      const url = API + '?action=list'
        + '&inactivos=' + (verInactivos ? 1 : 0)
        + '&q=' + encodeURIComponent(qLast || '')
        + '&limit=' + encodeURIComponent(perPage)
        + '&offset=' + encodeURIComponent(offset);

      fetch(url).then(r => r.json()).then(resp => {
        if (resp.error) {
          alert(resp.error);
          return;
        }
        const rows = resp.rows || [];
        total = Number(resp.total || 0) || 0;
        lastRows = rows;

        let h = '';
        rows.forEach(r => {
          const st = Number(r.Status || 0) === 1
            ? '<span class="ap-chip ok" style="padding:2px 8px; font-size:11px;">Activo</span>'
            : '<span class="ap-chip warn" style="padding:2px 8px; font-size:11px;">Inactivo</span>';

          h += `
      <tr>
        <td class="ap-actions">
          ${verInactivos
              ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${r.IdMot})"></i>`
              : `<i class="fa fa-edit" title="Editar" onclick="editar(${r.IdMot})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${r.IdMot})"></i>`}
        </td>
        <td>${reqDot(r)}</td>
        <td>${r.IdMot || ''}</td>
        <td>${r.Clave || ''}</td>
        <td>${r.Motivo || ''}</td>
        <td>${st}</td>
      </tr>`;
        });

        tb.innerHTML = h || `<tr><td colspan="6" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
        setPager();
      }).catch(e => console.error(e));
    }

    function buscar() { qLast = document.getElementById('q').value.trim(); page = 1; cargar(); }
    function limpiar() { document.getElementById('q').value = ''; qLast = ''; page = 1; cargar(); }
    function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }

    /* ===== CRUD ===== */
    function hideErrors() {
      err_clave.style.display = 'none';
      err_motivo.style.display = 'none';
    }
    function validar() {
      hideErrors();
      let ok = true;
      if (!Clave.value.trim()) { err_clave.style.display = 'block'; ok = false; }
      if (!Motivo.value.trim()) { err_motivo.style.display = 'block'; ok = false; }
      return ok;
    }
    function nuevo() {
      document.querySelectorAll('#mdl input').forEach(i => i.value = '');
      Status.value = '1';
      IdMot.value = '';
      hideErrors();
      mdl.style.display = 'block'; // Use display:flex via CSS rule for .ap-modal[style*='block']
    }
    function editar(id) {
      fetch(API + '?action=get&id=' + id).then(r => r.json()).then(x => {
        for (let k in x) {
          const el = document.getElementById(k);
          if (el) el.value = (x[k] === null || x[k] === undefined) ? '' : x[k];
        }
        hideErrors();
        mdl.style.display = 'block';
      });
    }
    function guardar() {
      if (!validar()) return;
      const fd = new FormData();
      fd.append('action', IdMot.value ? 'update' : 'create');
      document.querySelectorAll('#mdl input').forEach(i => fd.append(i.id, i.value));
      document.querySelectorAll('#mdl select').forEach(s => fd.append(s.id, s.value));

      fetch(API, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
          if (resp && resp.error) {
            alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
            return;
          }
          cerrarModal('mdl');
          loadKpi();
          cargar();
        });
    }
    function eliminar(id) {
      if (!confirm('¿Inactivar motivo?')) return;
      const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
      fetch(API, { method: 'POST', body: fd }).then(() => { loadKpi(); cargar(); });
    }
    function recuperar(id) {
      const fd = new FormData(); fd.append('action', 'restore'); fd.append('id', id);
      fetch(API, { method: 'POST', body: fd }).then(() => { loadKpi(); cargar(); });
    }

    /* ===== CSV ===== */
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

        csvHead.innerHTML = '<tr>' + rows[0].split(',').map(h => `<th>${h}</th>`).join('') + '</tr>';
        csvBody.innerHTML = rows.slice(1, 6).map(rr => '<tr>' + rr.split(',').map(c => `<td>${c}</td>`).join('') + '</tr>').join('');

        csvPreviewWrap.style.display = 'block';
        importMsg.style.display = 'none';
        document.getElementById('btnImportarFinal').style.display = 'block';
      };
      r.readAsText(f);
    }
    function importarCsv() {
      const f = fileCsv.files[0];
      if (!f) { alert('Selecciona un CSV'); return; }

      const fd = new FormData();
      fd.append('action', 'import_csv');
      fd.append('file', f);

      fetch(API, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
          importMsg.style.display = 'flex';
          if (resp.error) {
            importMsg.className = 'ap-chip warn';
            importMsg.innerHTML = `<b>Error:</b> ${resp.error}`;
            return;
          }
          importMsg.className = 'ap-chip ok';
          importMsg.innerHTML = `<b>Importación:</b> OK ${resp.rows_ok || 0} | Err ${resp.rows_err || 0}`;
          setTimeout(() => { cerrarModal('mdlImport'); loadKpi(); cargar(); }, 2000);
        });
    }

    function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

    document.addEventListener('DOMContentLoaded', () => {
      selPerPage.value = '25';
      loadKpi();
      cargar();
    });
  </script>

</body>

</html>