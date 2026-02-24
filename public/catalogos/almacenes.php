<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO STYLES - REPLICA EXACTA DE EMPRESAS.PHP
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

  .ap-search input,
  .ap-search select {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 12px;
    color: #212529;
    background: transparent;
    font-family: inherit;
  }

  /* CHIPS / BUTTONS */
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
    font-size: 12px;
  }

  button.ghost:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-warehouse"></i> Catálogo de Almacenes</div>

  <!-- KPI CARDS -->
  <div class="ap-cards" id="kpiCards">
    <!-- Loaded dynamically -->
  </div>

  <!-- TOOLBAR -->
  <div class="ap-toolbar">

    <!-- Filtro Empresa -->
    <div class="ap-search" style="flex:0 0 220px; min-width:220px;">
      <i class="fa fa-building"></i>
      <select id="filtro_empresa" onchange="cargar()">
        <option value="0">Todas las Empresas</option>
      </select>
    </div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, nombre, 3PL..." onkeydown="if(event.key==='Enter') cargar()">
    </div>
    <button class="ap-chip" onclick="cargar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="border-left:1px solid #dee2e6; height:24px; margin:0 5px;"></div>

    <button class="ap-chip" id="btnToggleInactive" onclick="toggleInactivos()">
      <i class="fa fa-eye"></i> Ver Inactivos
    </button>

    <div style="flex:1"></div>

    <button class="ap-chip primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip" onclick="exportar()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()">
      <i class="fa fa-upload"></i> Importar
    </button>
  </div>


  <span class="ap-chip" id="msg" style="display:none; margin-bottom:10px;"></span>

  <!-- GRID -->
  <div class="ap-grid">
    <div class="ap-grid-wrapper">
      <table>
        <thead>
          <tr>
            <th>Acciones</th>
            <th>Clave Empresa</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Dirección</th>
            <th>Responsable</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Es 3PL</th>
            <th>Activo</th>
          </tr>
        </thead>
        <tbody id="tb">
          <tr>
            <td colspan="10" style="text-align:center;padding:20px;color:#777">Cargando...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- =========================================================
     MODAL PRINCIPAL - ALMACÉN
========================================================= -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">

    <div class="ap-modal-header">
      <h3>
        <i class="fa fa-warehouse"></i>
        <span id="mdlTitle">Almacén</span>
      </h3>
      <button onclick="cerrarModal()">
        <i class="fa fa-times"></i>
      </button>
    </div>

    <input type="hidden" id="k_clave_empresa">
    <input type="hidden" id="k_id">

    <div class="ap-chip warn" id="mdlErr"
      style="display:none; width:100%; margin-bottom:15px;">
    </div>

    <div class="ap-form">

      <!-- Row 1 -->
      <div class="ap-field">
        <div class="ap-label">Clave Empresa *</div>
        <div class="ap-input">
          <i class="fa fa-building"></i>
          <select id="clave_empresa">
            <option value="">Seleccione...</option>
          </select>
        </div>
      </div>

      <div class="ap-field span-2">
        <div class="ap-label">Nombre *</div>
        <div class="ap-input">
          <i class="fa fa-font"></i>
          <input id="nombre" placeholder="Nombre completo">
        </div>
      </div>

      <!-- Row 2 -->
      <div class="ap-field">
        <div class="ap-label">ID / Clave *</div>
        <div class="ap-input">
          <i class="fa fa-key"></i>
          <input id="clave" placeholder="Clave Alfanumérica">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tipo</div>
        <div class="ap-input">
          <i class="fa fa-sitemap"></i>
          <input id="tipo" type="number" placeholder="ID Numérico">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Es 3PL</div>
        <div class="ap-input">
          <i class="fa fa-truck"></i>
          <select id="es_3pl">
            <option value="No">No (Propio)</option>
            <option value="Si">Si (3PL)</option>
          </select>
        </div>
      </div>

      <!-- Row 3 -->
      <div class="ap-field full">
        <div class="ap-label">Dirección</div>
        <div class="ap-input">
          <i class="fa fa-map-marker-alt"></i>
          <input id="direccion" placeholder="Calle, Número, Colonia...">
        </div>
      </div>

      <!-- Row 4 -->
      <div class="ap-field">
        <div class="ap-label">Responsable</div>
        <div class="ap-input">
          <i class="fa fa-user"></i>
          <input id="responsable" placeholder="Nombre contacto">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Teléfono</div>
        <div class="ap-input">
          <i class="fa fa-phone"></i>
          <input id="telefono" placeholder="Teléfono">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Email</div>
        <div class="ap-input">
          <i class="fa fa-envelope"></i>
          <input id="email" placeholder="correo@ejemplo.com">
        </div>
      </div>

      <!-- Row 5 -->
      <div class="ap-field full">
        <div class="ap-label">Comentarios</div>
        <div class="ap-input">
          <i class="fa fa-sticky-note"></i>
          <input id="comentarios">
        </div>
      </div>

      <!-- Row 6 -->
      <div class="ap-field">
        <div class="ap-label">Estatus</div>
        <div class="ap-input">
          <i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>

    </div>

    <div class="ap-modal-footer">
      <button class="ghost" onclick="cerrarModal()">Cerrar</button>
      <button class="primary" onclick="guardar()">Guardar</button>
    </div>

  </div>
</div>


<!-- =========================================================
     MODAL IMPORTAR ALMACENES
========================================================= -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content" style="max-width:500px;">

    <div class="ap-modal-header">
      <h3>
        <i class="fa fa-upload"></i>
        Importar Almacenes
      </h3>
      <button onclick="cerrarImport()">
        <i class="fa fa-times"></i>
      </button>
    </div>

    <div class="ap-field full">
      <div class="ap-label">Archivo CSV *</div>
      <div class="ap-input">
        <i class="fa fa-file-csv"></i>
        <input type="file" id="fileImport" accept=".csv">
      </div>
      <small style="color:#6c757d;font-size:11px;margin-top:5px;">
        Formato esperado:<br>
        clave,nombre,cve_cia,direccion,contacto,telefono,correo,es_3pl
      </small>
    </div>

    <div class="ap-modal-footer">
      <button class="ghost" onclick="cerrarImport()">Cancelar</button>
      <button class="primary" onclick="importar()">Importar</button>
    </div>

  </div>
</div>

<script>
  // =========================================================
  // CONFIG
  // =========================================================

  const API = '../api/almacenes_api.php';
  const KPI = '../api/almacenes_kpi.php';
  const EMPRESAS_API = API;

  let verInactivos = false;

  document.addEventListener('DOMContentLoaded', () => {
    loadEmpresas();
    loadKPI();
  });

  // =========================================================
  // EMPRESAS
  // =========================================================

  function loadEmpresas() {
    fetch(EMPRESAS_API + '?action=get_companies')
      .then(r => r.json())
      .then(resp => {

        const rows = resp.rows || [];
        const sel = document.getElementById('filtro_empresa');
        const selMdl = document.getElementById('clave_empresa');

        sel.innerHTML = '<option value="0">Todas las Empresas</option>';
        selMdl.innerHTML = '<option value="">Seleccione...</option>';

        rows.forEach(c => {
          const txt = (c.clave_empresa || '') + ' - ' + (c.des_cia || '');

          const opt = document.createElement('option');
          opt.value = c.cve_cia;
          opt.text = txt;
          sel.appendChild(opt);

          const optM = document.createElement('option');
          optM.value = c.cve_cia;
          optM.text = txt;
          selMdl.appendChild(optM);
        });

        cargar();
      })
      .catch(() => cargar());
  }

  // =========================================================
  // KPI
  // =========================================================

  function loadKPI() {
    fetch(KPI + '?action=kpi')
      .then(r => r.json())
      .then(d => {
        document.getElementById('kpiCards').innerHTML = `
          <div class="ap-card" onclick="cargar()">
              <div class="h">Total Almacenes <i class="fa fa-warehouse"></i></div>
              <div class="k">${d.total || 0}</div>
          </div>
          <div class="ap-card">
              <div class="h">Activos <i class="fa fa-check-circle" style="color:#198754"></i></div>
              <div class="k">${d.activos || 0}</div>
          </div>
          <div class="ap-card">
              <div class="h">Inactivos <i class="fa fa-ban" style="color:#dc3545"></i></div>
              <div class="k">${d.inactivos || 0}</div>
          </div>
        `;
      });
  }

  // =========================================================
  // LISTADO
  // =========================================================

  function cargar() {

    const q = document.getElementById('q').value;
    const cve_cia = document.getElementById('filtro_empresa').value;

    const url = API + '?action=list' +
      '&q=' + encodeURIComponent(q) +
      '&inactivos=' + (verInactivos ? 1 : 0) +
      '&cve_cia=' + cve_cia;

    fetch(url)
      .then(r => r.json())
      .then(resp => {

        if (resp.error) {
          showMsg(resp.error, 'warn');
          return;
        }

        const rows = resp.rows || [];
        const tb = document.getElementById('tb');

        if (!rows.length) {
          tb.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;color:#777">Sin registros</td></tr>';
          return;
        }

        tb.innerHTML = rows.map(r => {

          const isOk = (r.Activo == '1');

          const statusBadge = isOk ?
            '<span class="ap-chip ok" style="padding:2px 8px;font-size:10px">Activo</span>' :
            '<span class="ap-chip warn" style="padding:2px 8px;font-size:10px">Inactivo</span>';

          const actions = `
            <div class="ap-actions">
              <i class="fa fa-pen" onclick="editar('${r.clave_empresa}', '${r.id}')"></i>
              ${
                isOk
                ? `<i class="fa fa-ban" onclick="cambiarEstado('${r.clave_empresa}','${r.id}','delete')"></i>`
                : `<i class="fa fa-rotate-left" onclick="cambiarEstado('${r.clave_empresa}','${r.id}','restore')"></i>`
              }
            </div>
          `;

          return `
            <tr>
              <td>${actions}</td>
              <td>${esc(r.clave_empresa)}</td>
              <td><b>${esc(r.nombre)}</b></td>
              <td>${esc(r.tipo)}</td>
              <td>${esc(r.direccion)}</td>
              <td>${esc(r.responsable)}</td>
              <td>${esc(r.telefono)}</td>
              <td>${esc(r.email)}</td>
              <td>${esc(r.es_3pl)}</td>
              <td>${statusBadge}</td>
            </tr>
          `;
        }).join('');
      });
  }

  function limpiar() {
    document.getElementById('q').value = '';
    cargar();
  }

  function toggleInactivos() {
    verInactivos = !verInactivos;
    cargar();
  }

  // =========================================================
  // UTILIDADES
  // =========================================================

  function showMsg(txt, cls) {
    const m = document.getElementById('msg');
    m.style.display = 'inline-flex';
    m.className = 'ap-chip ' + cls;
    m.innerHTML = txt;
    setTimeout(() => m.style.display = 'none', 3500);
  }

  function esc(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, m => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    } [m]));
  }

  function abrirModal() {
    document.getElementById('mdl').style.display = 'flex';
  }

  function cerrarModal() {
    document.getElementById('mdl').style.display = 'none';
  }

  // =========================================================
  // IMPORTAR
  // =========================================================

  function abrirImport() {
    document.getElementById('fileImport').value = '';
    document.getElementById('mdlImport').style.display = 'flex';
  }

  function cerrarImport() {
    document.getElementById('mdlImport').style.display = 'none';
  }

  function importar() {

    const file = document.getElementById('fileImport').files[0];

    if (!file) {
      showMsg('Seleccione un archivo CSV', 'warn');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'import');
    fd.append('file', file);

    fetch(API, {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.error) {
          showMsg(resp.error, 'warn');
        } else {
          cerrarImport();
          loadKPI();
          cargar();
          showMsg('Importados: ' + (resp.importados || 0), 'ok');
        }
      })
      .catch(() => {
        showMsg('Error al importar', 'warn');
      });
  }

  // =========================================================
  // EXPORTAR
  // =========================================================

  function exportar() {
    window.open(API + '?action=export', '_blank');
  }
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>