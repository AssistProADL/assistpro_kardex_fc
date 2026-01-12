<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO STYLES
========================================================= */
  body {
    font-family: system-ui, -apple-system, sans-serif;
    background: #f4f6fb;
    margin: 0;
  }

  .ap-container {
    padding: 20px;
    font-size: 13px;
    max-width: 100%;
    margin: 0 auto;
  }

  /* TITLE */
  .ap-title {
    font-size: 20px;
    font-weight: 600;
    color: #0b5ed7;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* CARDS (KPIs) - Placeholder for consistency, though this module might not have KPIs defined yet */
  .ap-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
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

  /* CHIPS / BUTTONS */
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

  /* FILTERS */
  .ap-select {
    padding: 5px 12px;
    border-radius: 20px;
    border: 1px solid #dee2e6;
    background: #fff;
    font-size: 12px;
    color: #495057;
    outline: none;
    cursor: pointer;
  }

  /* GRID */
  .ap-grid {
    background: #fff;
    border: 1px solid #e0e6ed;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    height: calc(100vh - 280px);
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
    white-space: nowrap;
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
    width: 600px;
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
    grid-template-columns: 1fr 1fr;
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

  /* PAGER */
  .ap-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding: 0 5px;
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
  <div class="ap-title"><i class="fa fa-credit-card"></i> Catálogo de Formas de Pago</div>

  <!-- TOOLBAR -->
  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar forma, clave, empresa..." onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="border-left:1px solid #dee2e6; height:24px; margin:0 5px;"></div>

    <select id="fEmpresa" class="ap-select" onchange="buscar()">
      <option value="">Todas las Empresas</option>
    </select>
    <select id="fStatus" class="ap-select" onchange="buscar()">
      <option value="">Todos los Estatus</option>
      <option value="1">Activos</option>
      <option value="0">Inactivos</option>
    </select>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar CSV</button>
  </div>

  <span class="ap-chip" id="msg" style="display:none; margin-bottom:10px;"></span>

  <!-- GRID -->
  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Opciones</th>
          <th>ID</th>
          <th>Forma</th>
          <th>Clave</th>
          <th>Empresa</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <!-- PAGER -->
  <div id="pager" class="ap-pager"></div>
</div>

<!-- MODAL EDIT/NEW -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-credit-card"></i> Forma de Pago</h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="IdFpag" value="0">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Forma *</div>
        <div class="ap-input"><i class="fa fa-font"></i><input id="Forma" placeholder="Efectivo, Transferencia...">
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clave *</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="Clave" placeholder="01, 03..."></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Empresa (ID)</div>
        <div class="ap-input"><i class="fa fa-building"></i><input id="IdEmpresa" placeholder="EMP01"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Estatus</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Status">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
    </div>

    <div style="text-align:right;margin-top:20px;display:flex;justify-content:flex-end;gap:10px">
      <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
      <button class="primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content" style="width:500px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar CSV</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px; width:100%; justify-content:center;">
      Layout: Clave, Forma, IdEmpresa, Status
    </div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="fileCsv" accept=".csv">
    </div>

    <div id="importResult" style="margin-top:15px;"></div>

    <div style="margin-top:15px; display:flex; gap:10px; justify-content:flex-end;">
      <button class="ghost" onclick="descargarLayout()"><i class="fa fa-download"></i> Layout</button>
      <button class="primary" onclick="subirCsv()"><i class="fa fa-cloud-arrow-up"></i> Importar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/formaspag_api.php';
  let curPage = 1;

  function showMsg(txt, cls = '') {
    const m = document.getElementById('msg');
    m.style.display = 'inline-flex';
    m.className = 'ap-chip ' + cls;
    m.innerHTML = txt;
    setTimeout(() => { m.style.display = 'none' }, 3500);
  }

  function abrirModal(id) { document.getElementById(id).style.display = 'block'; }
  function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

  function esc(s) {
    return (s ?? '').toString().replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[m]));
  }

  async function loadEmpresas() {
    try {
      const r = await fetch(API + '?action=empresas');
      const j = await r.json();
      if (j.ok && j.data) {
        const s = document.getElementById('fEmpresa');
        // Mantener solo la primera opcion
        s.innerHTML = '<option value="">Todas las Empresas</option>';
        j.data.forEach(x => {
          if (!x.IdEmpresa) return;
          const opt = document.createElement('option');
          opt.value = x.IdEmpresa;
          opt.textContent = x.IdEmpresa;
          s.appendChild(opt);
        });
      }
    } catch (e) { console.error(e); }
  }

  function refrescar(p = 1) {
    curPage = p;
    const q = document.getElementById('q').value;
    const fEmpresa = document.getElementById('fEmpresa').value;
    const fStatus = document.getElementById('fStatus').value;

    // Mapeo a DataTables API
    const start = (curPage - 1) * 25;

    // Construir URL params
    const params = new URLSearchParams();
    params.append('action', 'list');
    params.append('draw', 1);
    params.append('start', start);
    params.append('length', 25);
    params.append('search[value]', q);
    if (fEmpresa) params.append('fEmpresa', fEmpresa);
    if (fStatus) params.append('fStatus', fStatus);

    fetch(API + '?' + params.toString())
      .then(r => r.json())
      .then(d => {
        renderGrid(d.data || []);
        renderPager(d.recordsFiltered || 0, d.recordsTotal || 0);
      })
      .catch(e => console.error(e));
  }

  function renderGrid(rows) {
    const tb = document.getElementById('tb');
    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
      return;
    }
    tb.innerHTML = rows.map(r => {
      const st = parseInt(r.Status ?? 1);
      const cls = st === 1 ? 'ok' : 'warn';
      const txt = st === 1 ? 'Activo' : 'Inactivo';

      return `<tr>
        <td class="ap-actions">
           <i class="fa fa-pen" title="Editar" onclick="editar(${r.IdFpag})"></i>
           ${st === 1
          ? `<i class="fa fa-ban" title="Inactivar" onclick="toggle(${r.IdFpag})"></i>`
          : `<i class="fa fa-rotate-left" title="Recuperar" onclick="toggle(${r.IdFpag})"></i>`
        }
           <i class="fa fa-trash" title="Eliminar Forzoso" onclick="del(${r.IdFpag})"></i>
        </td>
        <td>${esc(r.IdFpag)}</td>
        <td><b>${esc(r.Forma)}</b></td>
        <td>${esc(r.Clave)}</td>
        <td>${esc(r.IdEmpresa)}</td>
        <td><span class="ap-chip ${cls}" style="padding:2px 8px;font-size:11px;">${txt}</span></td>
      </tr>`;
    }).join('');
  }

  function renderPager(filtered, total) {
    const p = document.getElementById('pager');
    const totalPages = Math.ceil(filtered / 25);
    const start = filtered > 0 ? (curPage - 1) * 25 + 1 : 0;
    const end = Math.min(curPage * 25, filtered);

    const prev = curPage > 1 ? `<button class="ap-chip" onclick="refrescar(${curPage - 1})"><i class="fa fa-chevron-left"></i></button>` : '';
    const next = curPage < totalPages ? `<button class="ap-chip" onclick="refrescar(${curPage + 1})"><i class="fa fa-chevron-right"></i></button>` : '';

    p.innerHTML = `
      <div style="font-size:12px;color:#666">
         Mostrando ${start}-${end} de ${filtered} registros
      </div>
      <div style="display:flex;gap:5px">
        ${prev}
        <span class="ap-chip" style="cursor:default">Página ${curPage}</span>
        ${next}
      </div>
    `;
  }

  function buscar() { refrescar(1); }
  function limpiar() {
    document.getElementById('q').value = '';
    document.getElementById('fEmpresa').value = '';
    document.getElementById('fStatus').value = '';
    refrescar(1);
  }

  function nuevo() {
    document.getElementById('IdFpag').value = 0;
    document.getElementById('Forma').value = '';
    document.getElementById('Clave').value = '';
    document.getElementById('IdEmpresa').value = '';
    document.getElementById('Status').value = 1;
    abrirModal('mdl');
  }

  function editar(id) {
    fetch(API + '?action=get&id=' + id)
      .then(r => r.json())
      .then(j => {
        if (!j.ok) { showMsg(j.msg, 'warn'); return; }
        const d = j.data;
        document.getElementById('IdFpag').value = d.IdFpag;
        document.getElementById('Forma').value = d.Forma;
        document.getElementById('Clave').value = d.Clave;
        document.getElementById('IdEmpresa').value = d.IdEmpresa;
        document.getElementById('Status').value = d.Status;
        abrirModal('mdl');
      });
  }

  function guardar() {
    const id = document.getElementById('IdFpag').value;
    const Forma = document.getElementById('Forma').value.trim();
    const Clave = document.getElementById('Clave').value.trim();
    const IdEmpresa = document.getElementById('IdEmpresa').value.trim();
    const Status = document.getElementById('Status').value;

    if (!Forma || !Clave) { alert('Forma y Clave son obligatorios'); return; }

    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('IdFpag', id);
    fd.append('Forma', Forma);
    fd.append('Clave', Clave);
    fd.append('IdEmpresa', IdEmpresa);
    fd.append('Status', Status);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) {
          showMsg('Guardado correctamente', 'ok');
          cerrarModal('mdl');
          refrescar(curPage);
          loadEmpresas(); // Recargar por si hay nueva empresa
        } else {
          showMsg(j.msg || 'Error al guardar', 'warn');
        }
      });
  }

  function toggle(id) {
    if (!confirm('¿Cambiar estatus?')) return;
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) { showMsg(j.msg, 'ok'); refrescar(curPage); }
        else showMsg(j.msg, 'warn');
      });
  }

  function del(id) {
    if (!confirm('¿ELIMINAR defintivamente?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.ok) { showMsg(j.msg, 'warn'); refrescar(curPage); loadEmpresas(); }
        else showMsg(j.msg, 'warn');
      });
  }

  // Import Logic
  function abrirImport() {
    document.getElementById('fileCsv').value = '';
    document.getElementById('importResult').innerHTML = '';
    abrirModal('mdlImport');
  }

  function descargarLayout() {
    const csv = "Clave,Forma,IdEmpresa,Status\nEFECTIVO,Pago en Efectivo,EMP01,1";
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'layout_formas_pago.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  function subirCsv() {
    const f = document.getElementById('fileCsv').files[0];
    if (!f) { alert('Selecciona un archivo'); return; }
    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('file', f);

    document.getElementById('importResult').innerHTML = '<div class="ap-chip">Importando...</div>';

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        const div = document.getElementById('importResult');
        if (j.ok) {
          div.innerHTML = `<div class="ap-chip ok">${j.msg}</div>`;
          setTimeout(() => { cerrarModal('mdlImport'); refrescar(1); loadEmpresas(); }, 2000);
        } else {
          let html = `<div style="color:red;font-size:12px;margin-bottom:5px;">${j.msg}</div>`;
          if (j.errors && j.errors.length) {
            html += `<div style="max-height:100px;overflow:auto;font-size:11px;background:#fff3cd;padding:5px;">${j.errors.join('<br>')}</div>`;
          }
          div.innerHTML = html;
        }
      })
      .catch(e => document.getElementById('importResult').innerHTML = 'Error de red');
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    loadEmpresas();
    refrescar(1);
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>