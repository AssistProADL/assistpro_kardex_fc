<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO – PROVEEDORES
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
    width: 1000px;
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
  <div class="ap-title"><i class="fa fa-industry"></i> Proveedores</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-chip" id="filtroLabel"><i class="fa fa-filter"></i> Empresa: <b>Todas</b></div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, empresa, nombre, ciudad, estado, país…"
        onkeydown="if(event.key==='Enter')buscar()">
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
          <th>Clave</th>
          <th>Razón Social</th>
          <th>Dirección</th>
          <th>Código Dane</th>
          <th>Departamento/Estado</th>
          <th>Municipio/Ciudad</th>
          <th>Es Transportadora</th>
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
      <h3 style="margin:0"><i class="fa fa-industry"></i> <span id="mdlTitle">Editar Proveedor</span></h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="ID_Proveedor">

    <div class="ap-form" style="grid-template-columns: 1fr 1fr;">
      <!-- COLUMNA IZQUIERDA -->
      <div style="display:flex; flex-direction:column; gap:15px;">
        <div class="ap-field">
          <div class="ap-label">Clave Proveedor</div>
          <div class="ap-input"><input id="cve_proveedor" placeholder="1000"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Razón Social *</div>
          <div class="ap-input"><input id="Empresa" placeholder="Razón Social"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Dirección</div>
          <div class="ap-input"><input id="direccion" placeholder="Dirección"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Colonia</div>
          <div class="ap-input"><input id="colonia" placeholder="Colonia"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Código Dane / Código postal *</div>
          <div class="ap-input"><input id="cve_dane" placeholder="9070"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Municipio/Ciudad</div>
          <div class="ap-input"><input id="ciudad" placeholder="Ciudad"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Departamento/Estado</div>
          <div class="ap-input"><input id="estado" placeholder="Estado"></div>
        </div>
      </div>

      <!-- COLUMNA DERECHA -->
      <div style="display:flex; flex-direction:column; gap:15px;">
        <div class="ap-field">
          <div class="ap-label">País *</div>
          <div class="ap-input"><input id="pais" placeholder="México"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">RUT/RFC</div>
          <div class="ap-input"><input id="RUT" placeholder="RFC/RUT"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Teléfono 1</div>
          <div class="ap-input"><input id="telefono1" placeholder="5512345678"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Teléfono 2</div>
          <div class="ap-input"><input id="telefono2" placeholder="Teléfono 2"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
        </div>

        <div class="ap-field">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" id="es_cliente" value="1" style="width:auto;">
            <span>Empresas / Proveedor</span>
          </label>
        </div>

        <div class="ap-field">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" id="es_transportista" value="1" style="width:auto;">
            <span>Es Transportadora</span>
          </label>
        </div>

        <div class="ap-field">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" id="envio_correo_automatico" value="1" style="width:auto;">
            <span>Enviar reportes automáticos por correo</span>
          </label>
        </div>

        <div class="ap-field">
          <div class="ap-label">Correo 1</div>
          <div style="display:flex; gap:5px;">
            <div class="ap-input" style="flex:1;"><input id="correo1" type="email" placeholder="correo@ejemplo.com">
            </div>
            <button class="ghost" style="padding:8px 12px; white-space:nowrap;">Eliminar</button>
          </div>
        </div>

        <div class="ap-field">
          <div class="ap-label" style="font-size:11px; color:#666;">Reporte para este correo</div>
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:12px;">
            <input type="checkbox" id="reporte_existencia" value="1" style="width:auto;">
            <span>Reporte de Existencia Por Ubicación</span>
          </label>
        </div>

        <div class="ap-field">
          <div class="ap-label">Frecuencia (Días de la semana)</div>
          <div style="display:flex; flex-wrap:wrap; gap:8px; font-size:11px;">
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_lunes" value="1" style="width:auto;"> Lunes
            </label>
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_martes" value="1" style="width:auto;"> Martes
            </label>
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_miercoles" value="1" style="width:auto;"> Miércoles
            </label>
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_jueves" value="1" style="width:auto;"> Jueves
            </label>
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_viernes" value="1" style="width:auto;"> Viernes
            </label>
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_sabado" value="1" style="width:auto;"> Sábado
            </label>
            <label style="display:flex; align-items:center; gap:4px; cursor:pointer;">
              <input type="checkbox" id="freq_domingo" value="1" style="width:auto;"> Domingo
            </label>
          </div>
        </div>

        <div class="ap-field">
          <div class="ap-label">Hora de envío</div>
          <div class="ap-input"><input id="hora_envio" type="time" placeholder="02:19 p.m."></div>
        </div>

        <div class="ap-field">
          <button class="ap-chip primary" onclick="agregarCorreo()"><i class="fa fa-plus"></i> Agregar Correo</button>
        </div>
      </div>
    </div>

    <div style="text-align:right;margin-top:15px;display:flex;justify-content:space-between;gap:10px">
      <button class="ghost" onclick="cerrarModal('mdl')">Cerrar</button>
      <button class="primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content" style="width:700px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar proveedores</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">Layout FULL con UPSERT por <b>cve_proveedor</b>. Previsualiza antes
      de importar.</div>

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
  const API = '../api/proveedores.php';
  const KPI = '../api/proveedores_kpi.php';

  let filtroEmpresa = '';
  let verInactivos = false;
  let qLast = '';
  let page = 1;
  let perPage = 25;
  let total = 0;
  let lastRows = [];

  function reqDot(p) {
    const hasCve = !!(p.cve_proveedor && String(p.cve_proveedor).trim() !== '');
    const hasEN = !!((p.Empresa && String(p.Empresa).trim() !== '') || (p.Nombre && String(p.Nombre).trim() !== ''));
    const hasPais = !!(p.pais && String(p.pais).trim() !== '');
    const ok = hasCve && hasEN && hasPais;
    return `<span class="ap-req-dot ${ok ? 'ap-req-ok' : ''}" title="${ok ? 'OK' : 'Faltan obligatorios'}"></span>`;
  }

  /* ===== Cards KPI ===== */
  function loadCards() {
    fetch(KPI + '?action=kpi').then(r => r.json()).then(rows => {
      let h = '';
      rows.forEach(x => {
        const emp = x.Empresa || '';
        h += `
      <div class="ap-card" onclick="setEmpresa('${String(emp).replace(/'/g, "\\'")}')">
        <div class="h">
          <b><i class="fa fa-building"></i> ${emp || 'SIN EMPRESA'}</b>
          <span class="ap-chip ok">${x.activos} Act</span>
        </div>
        <div class="k">
          <span class="ap-chip">Total: ${x.total}</span>
          <span class="ap-chip warn">Inac: ${x.inactivos}</span>
          <span class="ap-chip">Bad: ${x.inconsistentes}</span>
        </div>
      </div>`;
      });
      cards.innerHTML = h || `<div class="ap-chip warn">Sin datos</div>`;
    });
  }

  function setEmpresa(emp) {
    filtroEmpresa = emp || '';
    filtroLabel.innerHTML = `<i class="fa fa-filter"></i> Empresa: <b>${filtroEmpresa ? filtroEmpresa : 'Todas'}</b> ${filtroEmpresa ? '<span class="ap-chip" style="cursor:pointer" onclick="setEmpresa(\'\')">Quitar</span>' : ''
      }`;
    page = 1;
    cargar();
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
      + '&empresa=' + encodeURIComponent(filtroEmpresa || '')
      + '&inactivos=' + (verInactivos ? 1 : 0)
      + '&q=' + encodeURIComponent(qLast || '')
      + '&limit=' + encodeURIComponent(perPage)
      + '&offset=' + encodeURIComponent(offset);

    fetch(url).then(r => r.json()).then(resp => {
      const rows = resp.rows || [];
      total = Number(resp.total || 0) || 0;
      lastRows = rows;

      let h = '';
      rows.forEach(p => {
        const esTransp = Number(p.es_transportista || 0) === 1 ? 'Sí' : 'No';
        h += `
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${p.ID_Proveedor})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${p.ID_Proveedor})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${p.ID_Proveedor})"></i>`}
        </td>
        <td><b>${p.cve_proveedor || ''}</b></td>
        <td>${p.Empresa || ''}</td>
        <td>${p.direccion || ''}</td>
        <td>${p.cve_dane || ''}</td>
        <td>${p.estado || ''}</td>
        <td>${p.ciudad || ''}</td>
        <td>${esTransp}</td>
      </tr>`;
      });
      tb.innerHTML = h || `<tr><td colspan="8" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
      setPager();
    });
  }

  function buscar() { qLast = q.value.trim(); page = 1; cargar(); }
  function limpiar() { q.value = ''; qLast = ''; page = 1; cargar(); }
  function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }

  function nuevo() {
    document.getElementById('mdlTitle').innerText = 'Nuevo Proveedor';
    document.querySelectorAll('#mdl input[type="text"], #mdl input[type="email"], #mdl input[type="time"]').forEach(i => i.value = '');
    document.querySelectorAll('#mdl input[type="checkbox"]').forEach(c => c.checked = false);
    ID_Proveedor.value = '';
    mdl.style.display = 'block';
  }

  function editar(id) {
    fetch(API + '?action=get&id=' + id).then(r => r.json()).then(p => {
      document.getElementById('mdlTitle').innerText = 'Editar Proveedor #' + p.ID_Proveedor;
      
      // Text inputs
      ['ID_Proveedor', 'cve_proveedor', 'Empresa', 'direccion', 'colonia', 'cve_dane', 
       'ciudad', 'estado', 'pais', 'RUT', 'telefono1', 'telefono2'].forEach(k => {
        const el = document.getElementById(k);
        if (el) el.value = (p[k] === null || p[k] === undefined) ? '' : p[k];
      });
      
      // Checkboxes
      document.getElementById('es_cliente').checked = Number(p.es_cliente || 0) === 1;
      document.getElementById('es_transportista').checked = Number(p.es_transportista || 0) === 1;
      document.getElementById('envio_correo_automatico').checked = Number(p.envio_correo_automatico || 0) === 1;
      
      mdl.style.display = 'block';
    });
  }

  function guardar() {
    const fd = new FormData();
    fd.append('action', ID_Proveedor.value ? 'update' : 'create');
    
    // Text fields
    ['ID_Proveedor', 'cve_proveedor', 'Empresa', 'direccion', 'colonia', 'cve_dane',
     'ciudad', 'estado', 'pais', 'RUT', 'telefono1', 'telefono2'].forEach(id => {
      const el = document.getElementById(id);
      if (el) fd.append(id, el.value);
    });
    
    // Checkboxes (send 1 or 0)
    fd.append('es_cliente', document.getElementById('es_cliente').checked ? 1 : 0);
    fd.append('es_transportista', document.getElementById('es_transportista').checked ? 1 : 0);
    fd.append('envio_correo_automatico', document.getElementById('envio_correo_automatico').checked ? 1 : 0);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.error) {
          alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
          return;
        }
        cerrarModal('mdl');
        loadCards();
        cargar();
      });
  }

  function agregarCorreo() {
    alert('Funcionalidad de agregar correo en desarrollo');
  }

  function eliminar(id) {
    if (!confirm('¿Inactivar proveedor?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
    fetch(API, { method: 'POST', body: fd }).then(() => { loadCards(); cargar(); });
  }

  function recuperar(id) {
    const fd = new FormData(); fd.append('action', 'restore'); fd.append('id', id);
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
    loadCards();
    cargar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>