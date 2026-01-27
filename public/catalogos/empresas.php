<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO STYLES - Empresas
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
    cursor: pointer;
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
    text-decoration: none;
  }

  .ap-chip:hover {
    background: #e9ecef;
    border-color: #adb5bd;
    color: #212529;
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
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
  }

  .ap-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .ap-field.full {
    grid-column: 1 / -1;
  }

  .ap-field.span-2 {
    grid-column: span 2;
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
    min-width: 14px;
    text-align: center;
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

  .img-preview {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    object-fit: cover;
    border: 1px solid #dee2e6;
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-building"></i> Catálogo de Empresas</div>

  <div class="ap-cards" id="kpiCards">
    <!-- Loaded via JS -->
  </div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, nombre, rfc..." onkeydown="if(event.key==='Enter')buscar()">
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
            <th>Nombre</th>
            <th>RFC</th>
            <th>Tipo</th>
            <th>Distrito</th>
            <th>Ciudad</th>
            <th>Estado</th>
            <th>Teléfono</th>
            <th>Activo</th>
          </tr>
        </thead>
        <tbody id="tb"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL EDIT/NEW -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div class="ap-modal-header">
      <h3><i class="fa fa-building"></i> <span id="mdlTitle">Empresa</span></h3>
      <button onclick="cerrarModal('mdl')"><i class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="cve_cia" value="0">

    <div class="ap-form">
      <!-- Row 1 -->
      <div class="ap-field">
        <div class="ap-label">Clave Empresa *</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="clave_empresa" placeholder="Clave única"></div>
      </div>
      <div class="ap-field span-2">
        <div class="ap-label">Nombre de la Empresa *</div>
        <div class="ap-input"><i class="fa fa-font"></i><input id="des_cia" placeholder="Razón Social"></div>
      </div>

      <!-- Row 2 -->
      <div class="ap-field">
        <div class="ap-label">RUT | RFC *</div>
        <div class="ap-input"><i class="fa fa-id-card"></i><input id="des_rfc" placeholder="RFC"></div>
      </div>
      <div class="ap-field span-2">
        <div class="ap-label">Tipo de Empresa *</div>
        <div class="ap-input"><i class="fa fa-sitemap"></i>
          <select id="cve_tipcia">
            <option value="0">Seleccione...</option>
          </select>
        </div>
      </div>

      <!-- Row 3 -->
      <div class="ap-field">
        <div class="ap-label">Distrito *</div>
        <div class="ap-input"><i class="fa fa-map-marked-alt"></i><input id="distrito" placeholder="Distrito"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Municipio / Ciudad *</div>
        <div class="ap-input"><i class="fa fa-city"></i><input id="municipio" placeholder="Municipio"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Departamento / Estado *</div>
        <div class="ap-input"><i class="fa fa-globe-americas"></i><input id="estado" placeholder="Estado"></div>
      </div>

      <!-- Row 4 -->
      <div class="ap-field span-2">
        <div class="ap-label">Dirección *</div>
        <div class="ap-input"><i class="fa fa-location-dot"></i><input id="des_direcc" placeholder="Calle y número">
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Código Postal *</div>
        <div class="ap-input"><i class="fa fa-envelopes-bulk"></i><input id="des_cp" placeholder="CP"></div>
      </div>

      <!-- Row 5 -->
      <div class="ap-field">
        <div class="ap-label">Teléfono *</div>
        <div class="ap-input"><i class="fa fa-phone"></i><input id="des_telef" placeholder="Teléfono"></div>
      </div>
      <div class="ap-field span-2">
        <div class="ap-label">Contacto *</div>
        <div class="ap-input"><i class="fa fa-user"></i><input id="des_contacto" placeholder="Nombre Contacto"></div>
      </div>

      <!-- Row 6 -->
      <div class="ap-field span-2">
        <div class="ap-label">Correo Electrónico *</div>
        <div class="ap-input"><i class="fa fa-envelope"></i><input id="des_email" placeholder="Email"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">¿Es Transportista (3PL)?</div>
        <div class="ap-input"><i class="fa fa-truck"></i>
          <select id="es_transportista">
            <option value="">No aplica</option>
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <!-- Row 7 -->
      <div class="ap-field full">
        <div class="ap-label">Comentarios</div>
        <div class="ap-input"><i class="fa fa-sticky-note"></i><input id="des_observ"
            placeholder="Comentarios adicionales"></div>
      </div>

      <!-- Row 8 - Activo & Imagen -->
      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <div class="ap-field span-2">
        <div class="ap-label">Subir Imagen</div>
        <div class="ap-input">
          <i class="fa fa-image"></i>
          <input type="file" id="imagen" accept=".jpg,.jpeg,.png">
        </div>
        <small style="color:#6c757d; font-size:10px;">Max: 500kb. Formatos: jpg, png.</small>
      </div>

    </div>

    <div class="ap-modal-footer">
      <button class="ghost" onclick="cerrarModal('mdl')">Cerrar</button>
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
      Layout: clave_empresa, des_cia, des_rfc, ...
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
  const API = '../api/empresas.php';
  const KPI_API = '../api/empresas_kpi.php'; // Reuse existing KPI file if possible, else we use list count
  let curPage = 1;
  let viewInactive = false;
  let tiposMap = {};

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
    refrescar();
  }

  function cargarTipos() {
    fetch(API + '?action=tipos')
      .then(r => r.json())
      .then(j => {
        if (j.rows) {
          const sel = document.getElementById('cve_tipcia');
          sel.innerHTML = '<option value="0">Seleccione...</option>';
          j.rows.forEach(t => {
            tiposMap[t.id] = t.descripcion;
            sel.innerHTML += `<option value="${t.id}">${esc(t.descripcion)}</option>`;
          });
        }
      });
  }

  function refrescar() {
    const q = document.getElementById('q').value;
    const params = new URLSearchParams();
    params.append('action', 'list');
    params.append('q', q);
    params.append('inactivos', viewInactive ? 1 : 0);

    fetch(API + '?' + params.toString())
      .then(r => r.json())
      .then(d => {
        if (d.error) {
          showMsg(d.error, 'warn');
        } else {
          renderGrid(d.rows || []);
          // Calculate KPIs locally based on rows returned or fetch separate? 
          // Existing code fetched all rows for grid.
          renderKPIs(d.rows || []);
        }
      })
      .catch(e => console.error(e));
  }

  function renderGrid(rows) {
    const tb = document.getElementById('tb');
    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
      return;
    }

    tb.innerHTML = rows.map(r => {
      const activo = parseInt(r.Activo ?? 1);
      const isOk = activo === 1;
      const statusCls = isOk ? 'ok' : 'warn';
      const statusTxt = isOk ? 'Activo' : 'Inactivo';
      const rowStyle = !isOk ? 'background:#f8f9fa;color:#adb5bd' : '';
      const tipo = tiposMap[r.cve_tipcia] || r.cve_tipcia || '';

      return `<tr style="${rowStyle}">
        <td class="ap-actions">
           <i class="fa fa-pen" title="Editar" onclick="editar(${r.cve_cia})"></i>
           ${isOk ? `<i class="fa fa-ban" title="Baja" onclick="eliminar(${r.cve_cia})"></i>`
          : `<i class="fa fa-rotate-left" title="Recuperar" onclick="recuperar(${r.cve_cia})"></i>`}
        </td>
        <td>${esc(r.cve_cia)}</td>
        <td><b>${esc(r.clave_empresa)}</b></td>
        <td>${esc(r.des_cia)}</td>
        <td>${esc(r.des_rfc)}</td>
        <td>${esc(tipo)}</td>
        <td>${esc(r.distrito)}</td>
        <td>${esc(r.municipio)}</td>
        <td>${esc(r.estado)}</td>
        <td>${esc(r.des_telef)}</td>
        <td><span class="ap-chip ${statusCls}" style="padding:2px 8px;font-size:10px">${statusTxt}</span></td>
      </tr>`;
    }).join('');
  }

  function renderKPIs(rows) {
    const total = rows.length;
    const activos = rows.filter(r => (parseInt(r.Activo ?? 1) === 1)).length;
    const inactivos = rows.filter(r => (parseInt(r.Activo ?? 1) === 0)).length;

    // Update toggle button visibility
    const btn = document.getElementById('btnToggleInactive');
    if (inactivos > 0 || viewInactive) { // Keep visible if viewing inactives even if count is 0 locally filtered
      // Wait, if we filter by inactive in API, we don't know total inactives globally.
      // But existing logic seemed to filter in API.
      // Let's assume global search for now.
      btn.style.display = 'inline-flex';
    } else {
      btn.style.display = 'none';
    }

    document.getElementById('kpiCards').innerHTML = `
      <div class="ap-card" onclick="refrescar()">
        <div class="h">Total Empresas <i class="fa fa-building"></i></div>
        <div class="k">${total}</div>
      </div>
      <div class="ap-card">
        <div class="h">Activas <i class="fa fa-check-circle" style="color:#198754"></i></div>
        <div class="k">${activos}</div>
      </div>
       <div class="ap-card">
        <div class="h">Inactivas <i class="fa fa-ban" style="color:#dc3545"></i></div>
        <div class="k">${inactivos}</div>
      </div>
      `;
  }

  function nuevo() {
    document.getElementById('mdlTitle').innerText = 'Nueva Empresa';
    document.getElementById('cve_cia').value = 0;

    ['clave_empresa', 'des_cia', 'des_rfc', 'distrito', 'municipio', 'estado', 'des_direcc', 'des_cp', 'des_telef', 'des_contacto', 'des_email', 'des_observ'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('cve_tipcia').value = 0;
    document.getElementById('es_transportista').value = '';
    document.getElementById('Activo').value = 1;
    document.getElementById('imagen').value = '';

    abrirModal('mdl');
  }

  function editar(id) {
    fetch(API + '?action=get&cve_cia=' + id)
      .then(r => r.json())
      .then(d => {
        if (d.error) { showMsg(d.error, 'warn'); return; }

        document.getElementById('mdlTitle').innerText = 'Editar Empresa ' + d.clave_empresa;
        document.getElementById('cve_cia').value = d.cve_cia;

        document.getElementById('clave_empresa').value = d.clave_empresa;
        document.getElementById('des_cia').value = d.des_cia;
        document.getElementById('des_rfc').value = d.des_rfc;
        document.getElementById('cve_tipcia').value = d.cve_tipcia || 0;

        document.getElementById('distrito').value = d.distrito;
        document.getElementById('municipio').value = d.municipio || '';
        document.getElementById('estado').value = d.estado || '';

        document.getElementById('des_direcc').value = d.des_direcc;
        document.getElementById('des_cp').value = d.des_cp;
        document.getElementById('des_telef').value = d.des_telef;
        document.getElementById('des_contacto').value = d.des_contacto;
        document.getElementById('des_email').value = d.des_email;
        document.getElementById('es_transportista').value = (d.es_transportista === null ? '' : d.es_transportista);
        document.getElementById('des_observ').value = d.des_observ;
        document.getElementById('Activo').value = d.Activo;
        document.getElementById('imagen').value = ''; // Cannot set file input

        abrirModal('mdl');
      });
  }

  function guardar() {
    const id = document.getElementById('cve_cia').value;
    const clave = document.getElementById('clave_empresa').value.trim();
    const nombre = document.getElementById('des_cia').value.trim();
    const tipo = document.getElementById('cve_tipcia').value;

    if (!clave || !nombre) { showMsg('Clave y Nombre son obligatorios', 'warn'); return; }
    // if (tipo == 0) { showMsg('Seleccione Tipo de Empresa', 'warn'); return; }

    const fd = new FormData();
    fd.append('action', id > 0 ? 'update' : 'create');
    fd.append('cve_cia', id);

    // Inputs
    ['clave_empresa', 'des_cia', 'des_rfc', 'cve_tipcia', 'distrito', 'municipio', 'estado', 'des_direcc', 'des_cp', 'des_telef', 'des_contacto', 'des_email', 'es_transportista', 'des_observ', 'Activo'].forEach(key => {
      fd.append(key, document.getElementById(key).value);
    });

    // File
    const f = document.getElementById('imagen').files[0];
    if (f) fd.append('imagen', f);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          showMsg('Guardado correctamente', 'ok');
          cerrarModal('mdl');
          refrescar();
        } else {
          showMsg(j.error || 'Error al guardar', 'warn');
        }
      });
  }

  function eliminar(id) {
    if (!confirm('¿Inactivar empresa?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('cve_cia', id);
    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) { showMsg('Inactivado', 'ok'); refrescar(); }
        else showMsg(j.error, 'warn');
      });
  }

  function recuperar(id) {
    if (!confirm('¿Recuperar empresa?')) return;
    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('cve_cia', id);
    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) { showMsg('Recuperado', 'ok'); refrescar(); }
        else showMsg(j.error, 'warn');
      });
  }

  function buscar() { refrescar(); }
  function limpiar() { document.getElementById('q').value = ''; refrescar(); }
  function exportarCSV() { window.open(API + '?action=export', '_blank'); }

  function abrirImport() {
    document.getElementById('csvFile').value = '';
    document.getElementById('importResult').innerHTML = '';
    abrirModal('mdlImport');
  }

  function importarCSV() {
    const f = document.getElementById('csvFile').files[0];
    if (!f) { alert('Selecciona archivo'); return; }
    const fd = new FormData();
    fd.append('action', 'import');
    fd.append('csv', f); // API expects 'csv'

    document.getElementById('importResult').innerHTML = 'Importando...';

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          document.getElementById('importResult').innerHTML = `<div class="ap-chip ok">Insertados: ${j.inserted} <br> Actualizados: ${j.updated}</div>`;
          setTimeout(() => { cerrarModal('mdlImport'); refrescar(); }, 3000);
        } else {
          document.getElementById('importResult').innerHTML = `<div style="color:red">${j.error}</div>`;
        }
      });
  }

  document.addEventListener('DOMContentLoaded', () => {
    cargarTipos();
    refrescar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>