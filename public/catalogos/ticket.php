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

  .ap-chip.primary {
    background: #0b5ed7;
    color: #fff;
    border-color: #0b5ed7;
  }
  .ap-chip.primary:hover {
    background: #0a58ca;
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
    height: calc(100vh - 240px);
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
    width: 800px;
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
  
  .ap-form.full {
      grid-template-columns: 1fr;
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

  .ap-input input,
  .ap-input select {
    border: none;
    outline: none;
    width: 100%;
    font-size: 14px;
    color: #212529;
    background: transparent;
  }

  /* PAGER */
  .ap-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding: 0 5px;
  }
  
  img.ap-preview {
      max-height: 40px;
      border-radius: 4px;
      border: 1px solid #eee;
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
  button.primary:hover { background: #0a58ca; }
  
  button.danger {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
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
  button.ghost:hover { background: #f1f3f5; border-color: #ced4da; }

</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-ticket-alt"></i> Catálogo de Tickets</div>

  <!-- TOOLBAR -->
  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar en líneas, mensaje o empresa..." onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="border-left:1px solid #dee2e6; height:24px; margin:0 5px;"></div>
    
    <div class="ap-input" style="width:150px">
        <input id="fIdEmpresa" placeholder="Filtrar IdEmpresa" onkeydown="if(event.key==='Enter')buscar()">
    </div>

    <button class="ap-chip" id="btnToggleInactive" onclick="toggleInactivos()">
       <i class="fa fa-eye"></i> Ver Inactivos
    </button>

    <div style="flex:1"></div>

    <button class="ap-chip primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip" onclick="exportar()"><i class="fa fa-download"></i> Exportar</button>
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
          <th>Línea 1</th>
          <th>Línea 2</th>
          <th>Línea 3</th>
          <th>Línea 4</th>
          <th>Mensaje</th>
          <th>Tdv</th>
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
      <h3 style="margin:0"><i class="fa fa-ticket-alt"></i> <span id="mdlTitle">Ticket</span></h3>
      <button onclick="cerrarModal('mdl')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="mID" value="0">
    <!-- Logica logo -->
    <input type="hidden" id="mLogoBase64" value="__NOCHANGE__">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Línea 1</div>
        <div class="ap-input"><input id="mLinea1" maxlength="100"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Línea 2</div>
        <div class="ap-input"><input id="mLinea2" maxlength="100"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Línea 3</div>
        <div class="ap-input"><input id="mLinea3" maxlength="100"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Línea 4</div>
        <div class="ap-input"><input id="mLinea4" maxlength="100"></div>
      </div>
    </div>
    
    <div class="ap-form full" style="margin-top:10px; grid-template-columns: 1fr;">
       <div class="ap-field">
        <div class="ap-label">Mensaje (Pie de ticket)</div>
        <div class="ap-input"><input id="mMensaje" maxlength="255"></div>
      </div>
    </div>

    <div class="ap-form" style="margin-top:10px;">
      <div class="ap-field">
        <div class="ap-label">Tdv (Días Vigencia)</div>
        <div class="ap-input"><input type="number" id="mTdv" value="0"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">IdEmpresa</div>
        <div class="ap-input"><input id="mIdEmpresa" maxlength="50"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Estatus</div>
         <div class="ap-input">
          <select id="mMLiq">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
      
      <!-- LOGO UPLOAD -->
      <div class="ap-field">
          <div class="ap-label">Logo (Imagen)</div>
          <div style="display:flex; gap:10px; align-items:center;">
              <img id="previewLogo" src="" style="height:40px; display:none; border:1px solid #ccc; border-radius:4px;">
              <input type="file" id="fileLogo" accept="image/*" style="font-size:11px;">
              <button class="ghost" style="padding:4px 8px; font-size:11px;" onclick="quitarLogo()">Quitar</button>
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
      <button onclick="cerrarModal('mdlImport')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>
    
    <div class="ap-chip" style="margin-bottom:15px; width:100%; justify-content:center;">
        Layout: ID, Linea1..4, Mensaje, Tdv, LOGO_BASE64, MLiq, IdEmpresa
    </div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="fileCsv" accept=".csv">
    </div>

    <div id="importResult" style="margin-top:15px;"></div>

    <div style="margin-top:15px; display:flex; gap:10px; justify-content:flex-end;">
      <button class="primary" onclick="subirCsv()"><i class="fa fa-cloud-arrow-up"></i> Importar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/api_ticket.php';
  let curPage = 1;
  let viewInactive = false;

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

  function toggleInactivos(){
      viewInactive = !viewInactive;
      const btn = document.getElementById('btnToggleInactive');
      if(viewInactive){
          btn.classList.add('warn');
          btn.innerHTML = '<i class="fa fa-eye-slash"></i> Ocultar Inactivos';
      } else {
          btn.classList.remove('warn');
          btn.innerHTML = '<i class="fa fa-eye"></i> Ver Inactivos';
      }
      refrescar(1);
  }

  function refrescar(p = 1) {
    curPage = p;
    const q = document.getElementById('q').value;
    const fIdEmpresa = document.getElementById('fIdEmpresa').value;
    
    // Mapeo a DataTables API
    const start = (curPage - 1) * 25;
    
    const params = new URLSearchParams();
    params.append('action', 'list');
    params.append('draw', 1);
    params.append('start', start);
    params.append('length', 25);
    params.append('search[value]', q);
    
    if(fIdEmpresa) params.append('IdEmpresa', fIdEmpresa);
    params.append('include_inactive', viewInactive ? 1 : 0);

    fetch(API + '?' + params.toString())
      .then(r => r.json())
      .then(d => {
        if(d.success){
             renderGrid(d.data || []);
             renderPager(d.recordsFiltered || 0, d.recordsTotal || 0);
        } else {
            showMsg(d.message || 'Error al cargar', 'warn');
        }
      })
      .catch(e => console.error(e));
  }

  function renderGrid(rows) {
    const tb = document.getElementById('tb');
    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
      return;
    }
    tb.innerHTML = rows.map(r => {
      const st = parseInt(r.MLiq ?? 1);
      const cls = st === 1 ? 'ok' : 'warn';
      const txt = st === 1 ? 'Activo' : 'Inactivo';
      
      return `<tr>
        <td class="ap-actions">
           <i class="fa fa-pen" title="Editar" onclick="editar(${r.ID})"></i>
           ${ st === 1 
              ? `<i class="fa fa-ban" title="Inactivar" onclick="toggle(${r.ID}, 'inactivate')"></i>`
              : `<i class="fa fa-rotate-left" title="Recuperar" onclick="toggle(${r.ID}, 'restore')"></i>`
            }
           <i class="fa fa-trash" title="Eliminar Forzoso" onclick="del(${r.ID})"></i>
        </td>
        <td>${esc(r.ID)}</td>
        <td>${esc(r.Linea1)}</td>
        <td>${esc(r.Linea2)}</td>
        <td>${esc(r.Linea3)}</td>
        <td>${esc(r.Linea4)}</td>
        <td>${esc(r.Mensaje)}</td>
        <td>${esc(r.Tdv)}</td>
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
      document.getElementById('q').value=''; 
      document.getElementById('fIdEmpresa').value='';
      refrescar(1); 
  }

  function nuevo() {
    document.getElementById('mdlTitle').innerText = 'Nuevo Ticket';
    document.getElementById('mID').value = 0;
    
    ['mLinea1','mLinea2','mLinea3','mLinea4','mMensaje','mIdEmpresa'].forEach(id=> document.getElementById(id).value = '');
    document.getElementById('mTdv').value = 0;
    document.getElementById('mMLiq').value = 1;
    
    // Reset Logo
    document.getElementById('mLogoBase64').value = '__NOCHANGE__'; // En create se ignorará si no se cambia
    document.getElementById('previewLogo').src = '';
    document.getElementById('previewLogo').style.display='none';
    document.getElementById('fileLogo').value = '';
    
    abrirModal('mdl');
  }

  function editar(id) {
    const fd = new FormData();
    fd.append('action', 'get');
    fd.append('ID', id);

    fetch(API, {method:'POST', body:fd})
      .then(r => r.json())
      .then(j => {
        if (!j.success) { showMsg(j.message, 'warn'); return; }
        const r = j.row;
        document.getElementById('mdlTitle').innerText = 'Editar Ticket #' + r.ID;
        document.getElementById('mID').value = r.ID;
        document.getElementById('mLinea1').value = r.Linea1;
        document.getElementById('mLinea2').value = r.Linea2;
        document.getElementById('mLinea3').value = r.Linea3;
        document.getElementById('mLinea4').value = r.Linea4;
        document.getElementById('mMensaje').value = r.Mensaje;
        document.getElementById('mTdv').value = r.Tdv;
        document.getElementById('mIdEmpresa').value = r.IdEmpresa;
        document.getElementById('mMLiq').value = r.MLiq;

        // Logo
        document.getElementById('mLogoBase64').value = '__NOCHANGE__'; 
        document.getElementById('fileLogo').value = '';
        if(r.LOGO_BASE64){
             document.getElementById('previewLogo').src = 'data:image/png;base64,' + r.LOGO_BASE64;
             document.getElementById('previewLogo').style.display = 'block';
        } else {
             document.getElementById('previewLogo').style.display = 'none';
        }

        abrirModal('mdl');
      });
  }
  
  // Logic to convert file to base64
  document.getElementById('fileLogo').addEventListener('change', function(){
      if(this.files && this.files[0]){
          const reader = new FileReader();
          reader.onload = function(e){
              const b64 = e.target.result; // data:image/png;base64,...
              document.getElementById('mLogoBase64').value = b64;
              document.getElementById('previewLogo').src = b64;
              document.getElementById('previewLogo').style.display = 'block';
          };
          reader.readAsDataURL(this.files[0]);
      }
  });
  
  function quitarLogo(){
      document.getElementById('mLogoBase64').value = ''; // Empty means delete/null
      document.getElementById('previewLogo').src = '';
      document.getElementById('previewLogo').style.display='none';
      document.getElementById('fileLogo').value = '';
  }

  function guardar() {
    const id = document.getElementById('mID').value;
    const isUpdate = (parseInt(id) > 0);
    
    const fd = new FormData();
    fd.append('action', isUpdate ? 'update' : 'create');
    if(isUpdate) fd.append('ID', id);
    
    fd.append('Linea1', document.getElementById('mLinea1').value);
    fd.append('Linea2', document.getElementById('mLinea2').value);
    fd.append('Linea3', document.getElementById('mLinea3').value);
    fd.append('Linea4', document.getElementById('mLinea4').value);
    fd.append('Mensaje', document.getElementById('mMensaje').value);
    fd.append('Tdv', document.getElementById('mTdv').value);
    fd.append('IdEmpresa', document.getElementById('mIdEmpresa').value);
    fd.append('MLiq', document.getElementById('mMLiq').value);
    
    // Logo logic
    const logoB64 = document.getElementById('mLogoBase64').value;
    if(isUpdate){
         // Si es update, solo mandar si es diferente de __NOCHANGE__
         if(logoB64 !== '__NOCHANGE__') fd.append('LOGO_BASE64', logoB64);
    } else {
         // Create: si es __NOCHANGE__ significa vacío
         if(logoB64 !== '__NOCHANGE__') fd.append('LOGO_BASE64', logoB64);
    }

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          showMsg(j.message, 'ok');
          cerrarModal('mdl');
          refrescar(curPage);
        } else {
          showMsg(j.message || 'Error al guardar', 'warn');
        }
      });
  }

  function toggle(id, action) {
    if(!confirm('¿Estás seguro?')) return;
    const fd = new FormData();
    fd.append('action', action);
    fd.append('ID', id);
    fetch(API, { method:'POST', body:fd })
        .then(r=>r.json())
        .then(j=>{
            if(j.success) { showMsg(j.message, 'ok'); refrescar(curPage); }
            else showMsg(j.message, 'warn');
        });
  }

  function del(id) {
    if(!confirm('¿ELIMINAR defintivamente? Esta acción no se puede deshacer.')) return;
    const fd = new FormData();
    fd.append('action','delete');
    fd.append('ID', id);
    fetch(API, { method:'POST', body:fd })
        .then(r=>r.json())
        .then(j=>{
            if(j.success) { showMsg(j.message, 'warn'); refrescar(curPage); }
            else showMsg(j.message, 'warn');
        });
  }
  
  function exportar(){
      const q = document.getElementById('q').value;
      const fIdEmpresa = document.getElementById('fIdEmpresa').value;
      const inc = viewInactive ? 1 : 0;
      window.open(API + `?action=export_csv&IdEmpresa=${fIdEmpresa}&include_inactive=${inc}`, '_blank');
  }

  // Import Logic
  function abrirImport() {
      document.getElementById('fileCsv').value = '';
      document.getElementById('importResult').innerHTML = '';
      abrirModal('mdlImport');
  }

  function subirCsv(){
      const f = document.getElementById('fileCsv').files[0];
      if(!f) { alert('Selecciona un archivo'); return; }
      const fd = new FormData();
      fd.append('action', 'import_csv');
      fd.append('file', f);

      document.getElementById('importResult').innerHTML = '<div class="ap-chip">Importando...</div>';
      
      fetch(API, {method:'POST', body:fd})
        .then(r=>r.json())
        .then(j=>{
            const div = document.getElementById('importResult');
            if(j.success){
                div.innerHTML = `<div class="ap-chip ok">Importados: ${j.total_ok} <br> Errores: ${j.total_err}</div>`;
                setTimeout(()=>{ cerrarModal('mdlImport'); refrescar(1); }, 3000);
            } else {
                let html = `<div style="color:red;font-size:12px;margin-bottom:5px;">${j.message}</div>`;
                if(j.errors && j.errors.length){
                    html += `<div style="max-height:100px;overflow:auto;font-size:11px;background:#fff3cd;padding:5px;">${j.errors.join('<br>')}</div>`;
                }
                div.innerHTML = html;
            }
        })
        .catch(e=> document.getElementById('importResult').innerHTML = 'Error de red');
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    refrescar(1);
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
