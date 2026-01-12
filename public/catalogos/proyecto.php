<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO – PROYECTOS | CC
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
  <div class="ap-title"><i class="fa fa-project-diagram"></i> Catálogo - Proyectos | CC</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-chip" id="filtroLabel"><i class="fa fa-filter"></i> Almacén: <b>Todos</b></div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave o descripción…" onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
    <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Req</th>
          <th>ID</th>
          <th>Clave</th>
          <th>Descripción</th>
          <th>Almacén</th>
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
      <h3 style="margin:0"><i class="fa fa-project-diagram"></i> Proyecto | CC</h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span> Obligatorio:
      <b>Clave</b></div>

    <input type="hidden" id="Id">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Clave *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="Cve_Proyecto" placeholder="CC-001"></div>
        <div class="ap-error" id="err_cve">Clave obligatoria.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="Des_Proyecto"
            placeholder="Descripción del proyecto / centro de costo"></div>
      </div>

      <div class="ap-field" style="grid-column: span 2">
        <div class="ap-label">Almacén</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <select id="id_almacen"></select>
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
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar proyectos</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>Cve_Proyecto</b>. id_almacen debe ser el <b>Id</b> del
      API almacenes.php</div>

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
  const API = '../api/proyecto.php';
  const KPI = '../api/proyecto_kpi.php';
  const API_ALMACENES = '../api/almacenes.php';

  let filtroAlmacen = 0;
  let qLast = '';
  let page = 1;
  let perPage = 25;
  let total = 0;
  let lastRows = [];
  let almacenesMap = {};

  /* ===== Escape HTML ===== */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function reqDot(r) {
    const ok = !!(r.Cve_Proyecto && String(r.Cve_Proyecto).trim() !== '');
    return `<span class="ap-req-dot ${ok ? 'ap-req-ok' : ''}" title="${ok ? 'OK' : 'Falta clave'}"></span>`;
  }

  function loadAlmacenes() {
    fetch(API_ALMACENES).then(r => r.json()).then(resp => {
      const rows = Array.isArray(resp) ? resp : (resp.rows || resp.data || []);
      const sel = document.getElementById('id_almacen');
      sel.innerHTML = '';
      almacenesMap = {};

      const o0 = document.createElement('option'); o0.value = '0'; o0.textContent = 'SIN ALMACÉN';
      sel.appendChild(o0);
      almacenesMap[0] = { Id: 0, Des: 'SIN ALMACÉN' };

      rows.forEach(a => {
        const id = Number(a.Id ?? a.id ?? a.ID ?? 0) || 0;
        const des = String(a.Des_Almacen ?? a.des_almacen ?? a.Nombre ?? a.nombre ?? a.Descripcion ?? a.descripcion ?? ('ALMACEN #' + id));
        const op = document.createElement('option');
        op.value = String(id);
        op.textContent = des;
        sel.appendChild(op);
        almacenesMap[id] = { Id: id, Des: des };
      });

      loadCards();
    }).catch(() => {
      const sel = document.getElementById('id_almacen');
      sel.innerHTML = '<option value="0">SIN ALMACÉN</option>';
      almacenesMap = { 0: { Id: 0, Des: 'SIN ALMACÉN' } };
      loadCards();
    });
  }

  function loadCards() {
    fetch(KPI + '?action=kpi').then(r => r.json()).then(rows => {
      let h = '';
      rows.forEach(x => {
        const idA = Number(x.IdAlmacen || 0) || 0;
        const nombre = (almacenesMap[idA] && almacenesMap[idA].Des) ? almacenesMap[idA].Des : (x.Almacen || ('ALMACEN #' + idA));
        h += `
      <div class="ap-card" onclick="setAlmacen(${idA})">
        <div class="h">
          <b><i class="fa fa-warehouse"></i> ${escapeHtml(nombre)}</b>
          <span class="ap-chip ok">${x.total} Total</span>
        </div>
        <div class="k">
          <span class="ap-chip">Inconsistentes: ${x.inconsistentes || 0}</span>
        </div>
      </div>`;
      });
      cards.innerHTML = h || `<div class="ap-chip warn">Sin datos</div>`;
    });
  }

  function setAlmacen(idA) {
    filtroAlmacen = Number(idA || 0) || 0;
    const nombre = (almacenesMap[filtroAlmacen] && almacenesMap[filtroAlmacen].Des) ? almacenesMap[filtroAlmacen].Des : (filtroAlmacen ? ('ALMACEN #' + filtroAlmacen) : 'Todos');
    filtroLabel.innerHTML = `<i class="fa fa-filter"></i> Almacén: <b>${filtroAlmacen ? escapeHtml(nombre) : 'Todos'}</b> ${filtroAlmacen ? '<span class="ap-chip" style="cursor:pointer" onclick="setAlmacen(0)">Quitar</span>' : ''
      }`;
    page = 1; cargar();
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

  function cargar() {
    const offset = (page - 1) * perPage;
    const url = API + '?action=list'
      + '&id_almacen=' + encodeURIComponent(filtroAlmacen || 0)
      + '&q=' + encodeURIComponent(qLast || '')
      + '&limit=' + encodeURIComponent(perPage)
      + '&offset=' + encodeURIComponent(offset);

    fetch(url).then(r => r.json()).then(resp => {
      const rows = resp.rows || [];
      total = Number(resp.total || 0) || 0;
      lastRows = rows;

      let h = '';
      rows.forEach(r => {
        const proyId = parseInt(r.Id) || 0;
        const almNombre = (almacenesMap[r.id_almacen] && almacenesMap[r.id_almacen].Des) ? almacenesMap[r.id_almacen].Des : (r.id_almacen ? 'ALMACEN #' + r.id_almacen : 'Sin almacén');
        h += `
      <tr>
        <td class="ap-actions">
          <i class="fa fa-edit" title="Editar" onclick="editar(${proyId})"></i>
          <i class="fa fa-trash" title="Eliminar" onclick="eliminar(${proyId})"></i>
        </td>
        <td>${reqDot(r)}</td>
        <td>${escapeHtml(String(r.Id || ''))}</td>
        <td><b>${escapeHtml(String(r.Cve_Proyecto || ''))}</b></td>
        <td>${escapeHtml(String(r.Des_Proyecto || ''))}</td>
        <td>${escapeHtml(almNombre)}</td>
      </tr>`;
      });
      tb.innerHTML = h || `<tr><td colspan="6" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
      setPager();
    });
  }

  function buscar() { qLast = q.value.trim(); page = 1; cargar(); }
  function limpiar() { q.value = ''; qLast = ''; page = 1; cargar(); }

  function hideErrors() { err_cve.style.display = 'none'; }

  function validar() {
    hideErrors();
    let ok = true;
    if (!Cve_Proyecto.value.trim()) { err_cve.style.display = 'block'; ok = false; }
    return ok;
  }

  function nuevo() {
    Id.value = '';
    Cve_Proyecto.value = '';
    Des_Proyecto.value = '';
    id_almacen.value = String(filtroAlmacen || 0);
    hideErrors();
    mdl.style.display = 'block';
  }

  function editar(id) {
    fetch(API + '?action=get&id=' + id).then(r => r.json()).then(p => {
      Id.value = p.Id || '';
      Cve_Proyecto.value = p.Cve_Proyecto || '';
      Des_Proyecto.value = p.Des_Proyecto || '';
      id_almacen.value = String(p.id_almacen ?? 0);
      hideErrors();
      mdl.style.display = 'block';
    });
  }

  function guardar() {
    if (!validar()) return;

    const fd = new FormData();
    const action = Id.value ? 'update' : 'create';
    fd.append('action', action);
    fd.append('Id', Id.value);
    fd.append('Cve_Proyecto', Cve_Proyecto.value);
    fd.append('Des_Proyecto', Des_Proyecto.value);
    fd.append('id_almacen', id_almacen.value);

    console.log('Guardando proyecto:', {
      action: action,
      Id: Id.value,
      Cve_Proyecto: Cve_Proyecto.value,
      Des_Proyecto: Des_Proyecto.value,
      id_almacen: id_almacen.value
    });

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        console.log('Respuesta del API:', resp);
        if (resp && resp.error) {
          alert('Error: ' + resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
          return;
        }
        if (!resp || resp.ok === false) {
          alert('Error: El API no respondió correctamente');
          return;
        }
        alert('Proyecto guardado correctamente');
        cerrarModal('mdl');
        loadCards();
        cargar();
      })
      .catch(err => {
        console.error('Error en fetch:', err);
        alert('Error de red: ' + err.message);
      });
  }

  function eliminar(id) {
    if (!confirm('¿Eliminar proyecto? (delete físico)')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
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
    fd.append('action', 'import_csv');
    fd.append('file', fileCsv.files[0]);

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
        setTimeout(() => { cerrarModal('mdlImport'); loadCards(); cargar(); }, 2000);
      });
  }

  function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

  document.addEventListener('DOMContentLoaded', () => {
    selPerPage.value = '25';
    loadAlmacenes();
    cargar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>