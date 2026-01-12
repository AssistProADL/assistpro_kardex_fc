<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO – CONTACTOS
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
    font-size: 11px;
  }

  .ap-grid th {
    background: #f8f9fa;
    padding: 10px 8px;
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
    padding: 8px;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
    vertical-align: middle;
  }

  .ap-grid tr:hover td {
    background: #f8f9fa;
  }

  .ap-actions i {
    cursor: pointer;
    margin-right: 10px;
    color: #6c757d;
    transition: color 0.2s;
    font-size: 13px;
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
    grid-template-columns: repeat(3, 1fr);
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

  .ap-input input {
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
  <div class="ap-title"><i class="fa fa-address-book"></i> Catálogo - Contactos</div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, nombre, correo, teléfono, ubicación…"
        onkeydown="if(event.key==='Enter')buscar()">
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
          <th>ID</th>
          <th>Clave</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Correo</th>
          <th>Teléfono 1</th>
          <th>Teléfono 2</th>
          <th>País</th>
          <th>Estado</th>
          <th>Ciudad</th>
          <th>Dirección</th>
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
      <h3 style="margin:0"><i class="fa fa-address-book"></i> Contacto</h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios:
      <b>Clave</b>, <b>Nombre</b>
    </div>

    <input type="hidden" id="id">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Clave *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="clave" maxlength="50" placeholder="C01"></div>
        <div class="ap-error" id="err_clave">Clave obligatoria.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Nombre *</div>
        <div class="ap-input"><i class="fa fa-user"></i><input id="nombre" maxlength="100" placeholder="Juan"></div>
        <div class="ap-error" id="err_nombre">Nombre obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Apellido</div>
        <div class="ap-input"><i class="fa fa-user"></i><input id="apellido" maxlength="100" placeholder="Pérez"></div>
      </div>

      <div class="ap-field" style="grid-column: span 2">
        <div class="ap-label">Correo</div>
        <div class="ap-input"><i class="fa fa-envelope"></i><input id="correo" maxlength="100"
            placeholder="correo@dominio.com"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Teléfono 1</div>
        <div class="ap-input"><i class="fa fa-phone"></i><input id="telefono1" maxlength="50" placeholder="1234567890">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Teléfono 2</div>
        <div class="ap-input"><i class="fa fa-phone"></i><input id="telefono2" maxlength="50" placeholder="0987654321">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">País</div>
        <div class="ap-input"><i class="fa fa-globe"></i><input id="pais" maxlength="100" placeholder="México"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Estado</div>
        <div class="ap-input"><i class="fa fa-flag"></i><input id="estado" maxlength="100" placeholder="Estado"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ciudad</div>
        <div class="ap-input"><i class="fa fa-city"></i><input id="ciudad" maxlength="100" placeholder="Ciudad"></div>
      </div>

      <div class="ap-field" style="grid-column: span 3">
        <div class="ap-label">Dirección</div>
        <div class="ap-input"><i class="fa fa-map-marker-alt"></i><input id="direccion" maxlength="200"
            placeholder="Calle y número"></div>
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
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar contactos</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>clave</b>. Layout:
      clave,nombre,apellido,correo,telefono1,telefono2,pais,estado,ciudad,direccion</div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="fileCsv" accept=".csv">
    </div>

    <div style="margin-top:15px;display:flex;gap:10px">
      <button class="primary" onclick="importarCsv()"><i class="fa fa-upload"></i> Importar</button>
    </div>

    <div class="ap-chip" id="importMsg" style="margin-top:15px; width:100%; display:none; justify-content:center;">
    </div>
  </div>
</div>

<script>
  const API = '../api/api_contactos.php';

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
    const url = API + '?action=list'
      + '&draw=' + page
      + '&q=' + encodeURIComponent(qLast || '')
      + '&start=' + ((page - 1) * perPage)
      + '&length=' + perPage;

    fetch(url).then(r => r.json()).then(resp => {
      const rows = resp.data || [];
      if (rows.length > 0) { }

      total = Number(resp.recordsFiltered || 0) || 0;
      lastRows = rows;

      let h = '';
      rows.forEach(c => {
        const contactId = parseInt(c.id) || 0;
        h += `
      <tr>
        <td class="ap-actions">
          <i class="fa fa-edit" title="Editar" onclick="editar(${contactId})"></i>
          <i class="fa fa-trash" title="Eliminar" onclick="eliminar(${contactId})"></i>
        </td>
        <td>${escapeHtml(String(c.id || ''))}</td>
        <td><b>${escapeHtml(String(c.clave || ''))}</b></td>
        <td>${escapeHtml(String(c.nombre || ''))}</td>
        <td>${escapeHtml(String(c.apellido || ''))}</td>
        <td>${escapeHtml(String(c.correo || ''))}</td>
        <td>${escapeHtml(String(c.telefono1 || ''))}</td>
        <td>${escapeHtml(String(c.telefono2 || ''))}</td>
        <td>${escapeHtml(String(c.pais || ''))}</td>
        <td>${escapeHtml(String(c.estado || ''))}</td>
        <td>${escapeHtml(String(c.ciudad || ''))}</td>
        <td>${escapeHtml(String(c.direccion || ''))}</td>
      </tr>`;
      });
      tb.innerHTML = h || `<tr><td colspan="12" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
      setPager();
    });
  }

  function buscar() { qLast = q.value.trim(); page = 1; cargar(); }
  function limpiar() { q.value = ''; qLast = ''; page = 1; cargar(); }

  function hideErrors() {
    err_clave.style.display = 'none';
    err_nombre.style.display = 'none';
  }

  function validar() {
    hideErrors();
    let ok = true;
    if (!clave.value.trim()) { err_clave.style.display = 'block'; ok = false; }
    if (!nombre.value.trim()) { err_nombre.style.display = 'block'; ok = false; }
    return ok;
  }

  function nuevo() {
    id.value = '';
    clave.value = '';
    nombre.value = '';
    apellido.value = '';
    correo.value = '';
    telefono1.value = '';
    telefono2.value = '';
    pais.value = '';
    estado.value = '';
    ciudad.value = '';
    direccion.value = '';
    hideErrors();
    mdl.style.display = 'block';
  }

  function editar(idVal) {

    if (!idVal || idVal === 0) {
      alert('Error: ID inválido (0 o undefined)');
      return;
    }

    fetch(API + '?action=get&id=' + idVal).then(r => r.json()).then(resp => {
      if (!resp.ok) {
        alert('Error: ' + (resp.msg || 'No se pudo cargar'));
        return;
      }
      const d = resp.data;
      id.value = d.id || '';
      clave.value = d.clave || '';
      nombre.value = d.nombre || '';
      apellido.value = d.apellido || '';
      correo.value = d.correo || '';
      telefono1.value = d.telefono1 || '';
      telefono2.value = d.telefono2 || '';
      pais.value = d.pais || '';
      estado.value = d.estado || '';
      ciudad.value = d.ciudad || '';
      direccion.value = d.direccion || '';
      hideErrors();
      mdl.style.display = 'block';
    }).catch(err => {
      console.error('Error en fetch:', err);
      alert('Error de red: ' + err.message);
    });
  }

  function guardar() {
    if (!validar()) return;

    const fd = new FormData();
    fd.append('action', id.value ? 'update' : 'create');
    fd.append('id', id.value);
    fd.append('clave', clave.value.trim());
    fd.append('nombre', nombre.value.trim());
    fd.append('apellido', apellido.value.trim());
    fd.append('correo', correo.value.trim());
    fd.append('telefono1', telefono1.value.trim());
    fd.append('telefono2', telefono2.value.trim());
    fd.append('pais', pais.value.trim());
    fd.append('estado', estado.value.trim());
    fd.append('ciudad', ciudad.value.trim());
    fd.append('direccion', direccion.value.trim());

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        if (!resp.ok) {
          alert('Error: ' + (resp.msg || 'Error desconocido'));
          return;
        }
        cerrarModal('mdl');
        cargar();
      });
  }

  function eliminar(idVal) {
    if (!confirm('¿Eliminar contacto (Hard Delete)?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', idVal);
    fetch(API, { method: 'POST', body: fd }).then(() => cargar());
  }

  function exportarDatos() { window.open(API + '?action=export_csv', '_blank'); }

  function abrirImport() {
    fileCsv.value = '';
    importMsg.style.display = 'none';
    mdlImport.style.display = 'block';
  }

  function importarCsv() {
    const f = fileCsv.files[0];
    if (!f) { alert('Selecciona un CSV'); return; }

    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('archivo', f);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        importMsg.style.display = 'flex';
        if (!resp.ok) {
          importMsg.className = 'ap-chip warn';
          importMsg.innerHTML = `<b>Error:</b> ${resp.msg || 'Error desconocido'}`;
          return;
        }
        importMsg.className = 'ap-chip ok';
        importMsg.innerHTML = `<b>Importación:</b> OK ${resp.total_ok || 0}`;
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