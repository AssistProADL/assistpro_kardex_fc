<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
/* =========================================================
   ASSISTPRO – GRUPO DE CLIENTES
========================================================= */
body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6fb; margin: 0; }
.ap-container { padding: 20px; font-size: 13px; max-width: 1400px; margin: 0 auto; }

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
.ap-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 15px; background: #fff; padding: 10px; border-radius: 10px; border: 1px solid #e0e6ed; }
.ap-search { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 300px; background: #f8f9fa; padding: 6px 12px; border-radius: 8px; border: 1px solid #dee2e6; }
.ap-search i { color: #6c757d; }
.ap-search input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; }

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
.ap-chip:hover { background: #e9ecef; color: #212529; border-color: #ced4da; }
.ap-chip.ok { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
.ap-chip.warn { background: #fff3cd; color: #664d03; border-color: #ffecb5; }
button.ap-chip { font-family: inherit; }

/* GRID */
.ap-grid {
  background: #fff;
  border: 1px solid #e0e6ed;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  max-height: 600px;
  overflow-y: auto;
}
.ap-grid table { width: 100%; border-collapse: collapse; }
.ap-grid th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #495057; border-bottom: 1px solid #dee2e6; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
.ap-grid td { padding: 10px 12px; border-bottom: 1px solid #f1f3f5; color: #212529; vertical-align: middle; }
.ap-grid tr:hover td { background: #f8f9fa; }
.ap-actions i { cursor: pointer; margin-right: 12px; color: #6c757d; transition: color 0.2s; font-size: 14px; }
.ap-actions i:hover { color: #0b5ed7; }

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
.ap-pager button:disabled { opacity: 0.5; cursor: default; }
.ap-pager button:hover:not(:disabled) { background: #f8f9fa; border-color: #ced4da; }
.ap-pager select { padding: 6px; border-radius: 6px; border: 1px solid #dee2e6; color: #495057; margin-left: 5px; }

/* MODAL */
.ap-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
.ap-modal[style*="display: block"] { display: flex !important; }
.ap-modal-content { background: #fff; width: 700px; max-width: 95%; max-height: 90vh; border-radius: 12px; display: flex; flex-direction: column; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 20px; }

.ap-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
.ap-field { display: flex; flex-direction: column; gap: 5px; }
.ap-label { font-weight: 500; font-size: 13px; color: #495057; }
.ap-input { display: flex; align-items: center; gap: 10px; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px 12px; background: #fff; transition: all 0.2s; }
.ap-input:focus-within { border-color: #0b5ed7; box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.1); }
.ap-input i { color: #adb5bd; }
.ap-input input, .ap-input select { border: none; outline: none; width: 100%; font-size: 14px; color: #212529; background: transparent; }

button.primary { background: #0b5ed7; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
button.primary:hover { background: #0a58ca; }
button.ghost { background: #fff; color: #495057; border: 1px solid #dee2e6; padding: 8px 16px; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
button.ghost:hover { background: #f1f3f5; border-color: #ced4da; }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-layer-group"></i> Catálogo | Grupo de Clientes</div>

  <div class="ap-toolbar">
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
    <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Inactivos</button>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>ID</th>
          <th>Clave</th>
          <th>Descripción</th>
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
      <h3 style="margin:0"><i class="fa fa-layer-group"></i> Grupo de Cliente</h3>
      <button onclick="cerrarModal('mdl')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="id">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Clave</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="cve_grupo" maxlength="50" placeholder="GRUPO-A"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_grupo" maxlength="200" placeholder="Descripción del grupo"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo"><option value="1">Activo</option><option value="0">Inactivo</option></select>
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
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar grupos</h3>
      <button onclick="cerrarModal('mdlImport')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">Layout: <b>id,cve_grupo,des_grupo,Activo</b></div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="fileCsv" accept=".csv">
    </div>

    <div style="margin-top:15px;display:flex;gap:10px">
      <button class="ghost" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
      <button class="primary" onclick="importarCsv()"><i class="fa fa-upload"></i> Importar</button>
    </div>

    <div class="ap-chip" id="importMsg" style="margin-top:15px; width:100%; display:none; justify-content:center;"></div>
  </div>
</div>

<script>
const API = '../api/clientes_grupo_api.php';

let verInactivos = false;
let qLast = '';
let page = 1;
let perPage = 25;
let total = 0;
let lastRows = [];

/* ===== Paginación ===== */
function setPager(){
  const start = total>0 ? ((page-1)*perPage + (lastRows.length?1:0)) : 0;
  let end   = total>0 ? Math.min(page*perPage, total) : 0;
  if(total===0) { end = 0; }
  
  lblRange.innerText = `Mostrando ${start}–${end}` + (total>0 ? ` de ${total}` : '');

  const maxPages = total>0 ? Math.max(1, Math.ceil(total/perPage)) : 1;
  selPage.innerHTML='';
  for(let i=1;i<=maxPages;i++){
    const o=document.createElement('option');
    o.value=i; o.textContent=i;
    if(i===page) o.selected=true;
    selPage.appendChild(o);
  }
  btnPrev.disabled = (page<=1);
  btnNext.disabled = total>0 ? (page>=maxPages) : (lastRows.length < perPage);
}
function prevPage(){ if(page>1){ page--; cargar(); } }
function nextPage(){
  const maxPages = total>0 ? Math.ceil(total/perPage) : 1;
  if(page < maxPages) { page++; cargar(); }
  else if(total===0 && lastRows.length===perPage) { page++; cargar(); } 
}
function goPage(p){ page = Math.max(1, parseInt(p,10)||1); cargar(); }
function setPerPage(v){ perPage = parseInt(v,10)||25; page=1; cargar(); }

function cargar(){
  const activo = verInactivos ? '' : '1';
  const url = API+'?action=list'
    + '&page='+page
    + '&limit='+perPage
    + '&q='+encodeURIComponent(qLast||'')
    + '&activo='+activo;

  fetch(url).then(r=>r.json()).then(resp=>{
    if(!resp.ok){
      tb.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#dc3545;padding:20px">Error: ${resp.msg||'Error desconocido'}</td></tr>`;
      return;
    }

    const rows = resp.data || [];
    total = Number(resp.total||0) || 0;
    lastRows = rows;

    let h='';
    rows.forEach(r=>{
      const activo = Number(r.Activo||1)===1;
      h+=`
      <tr>
        <td class="ap-actions">
          <i class="fa fa-edit" title="Editar" onclick="editar(${r.id})"></i>
          ${activo
            ? `<i class="fa fa-lock" title="Inactivar" onclick="toggleActivo(${r.id},0)"></i>`
            : `<i class="fa fa-undo" title="Recuperar" onclick="toggleActivo(${r.id},1)"></i>`}
          <i class="fa fa-trash" title="Eliminar" onclick="eliminar(${r.id})"></i>
        </td>
        <td>${r.id||''}</td>
        <td><b>${r.cve_grupo||''}</b></td>
        <td>${r.des_grupo||''}</td>
        <td>${activo ? '<span class="ap-chip ok">Activo</span>' : '<span class="ap-chip warn">Inactivo</span>'}</td>
      </tr>`;
    });
    tb.innerHTML = h || `<tr><td colspan="5" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    setPager();
  });
}

function buscar(){ qLast = q.value.trim(); page=1; cargar(); }
function limpiar(){ q.value=''; qLast=''; page=1; cargar(); }
function toggleInactivos(){ verInactivos=!verInactivos; page=1; cargar(); }

function nuevo(){
  id.value='';
  cve_grupo.value='';
  des_grupo.value='';
  Activo.value='1';
  mdl.style.display='block';
}

function editar(idVal){
  const r = lastRows.find(x=>String(x.id)===String(idVal));
  if(!r) return;

  id.value = r.id;
  cve_grupo.value = r.cve_grupo||'';
  des_grupo.value = r.des_grupo||'';
  Activo.value = String(r.Activo||'1');
  mdl.style.display='block';
}

function guardar(){
  const data = {
    id: id.value,
    cve_grupo: (cve_grupo.value||'').toUpperCase().trim(),
    des_grupo: (des_grupo.value||'').trim(),
    Activo: Activo.value
  };

  if(!data.cve_grupo || !data.des_grupo){
    alert('Clave y descripción son obligatorias.');
    return;
  }

  fetch(API+'?action=save',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({data})
  })
    .then(r=>r.json())
    .then(resp=>{
      if(!resp.ok){
        alert('Error: '+(resp.msg||'Error desconocido'));
        return;
      }
      cerrarModal('mdl');
      cargar();
      alert(resp.msg);
    });
}

function toggleActivo(idVal, val){
  if(!confirm(val===1 ? '¿Recuperar (Activo=1) este grupo?' : '¿Inactivar (Activo=0) este grupo?')) return;

  fetch(API+'?action=toggle',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({id:idVal, Activo:val})
  })
    .then(r=>r.json())
    .then(resp=>{
      if(!resp.ok){
        alert('Error: '+(resp.msg||'Error desconocido'));
        return;
      }
      cargar();
      alert(resp.msg);
    });
}

function eliminar(idVal){
  if(!confirm('¿Eliminar (Hard Delete) este registro? No se puede revertir.')) return;

  fetch(API+'?action=delete',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({id:idVal})
  })
    .then(r=>r.json())
    .then(resp=>{
      if(!resp.ok){
        alert('Error: '+(resp.msg||'Error desconocido'));
        return;
      }
      cargar();
      alert(resp.msg);
    });
}

function exportarDatos(){
  const activo = verInactivos ? '' : '1';
  fetch(API+'?action=export&activo='+activo)
    .then(r=>r.json())
    .then(resp=>{
      if(!resp.ok){
        alert('Error: '+(resp.msg||'Error desconocido'));
        return;
      }
      const blob = new Blob([resp.csv], {type:'text/csv;charset=utf-8;'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = resp.filename || 'c_gpoclientes.csv';
      a.click();
      URL.revokeObjectURL(a.href);
    });
}

function descargarLayout(){
  const csv = 'id,cve_grupo,des_grupo,Activo\n';
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'c_gpoclientes_layout.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

function abrirImport(){
  fileCsv.value='';
  importMsg.style.display='none';
  mdlImport.style.display='block';
}

function importarCsv(){
  const f=fileCsv.files[0];
  if(!f){ alert('Selecciona un CSV'); return; }

  const fd=new FormData();
  fd.append('file',f);

  fetch(API+'?action=import',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      importMsg.style.display='flex';
      if(!resp.ok){
        importMsg.className='ap-chip warn';
        importMsg.innerHTML = `<b>Error:</b> ${resp.msg||'Error desconocido'}`;
        return;
      }
      importMsg.className='ap-chip ok';
      importMsg.innerHTML = `<b>Importación:</b> ${resp.msg}`;
      setTimeout(() => { cerrarModal('mdlImport'); cargar(); }, 2000);
    });
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded', ()=>{
  selPerPage.value='25';
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
