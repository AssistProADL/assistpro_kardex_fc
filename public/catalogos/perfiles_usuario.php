<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
/* mismo CSS base */
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px}
.ap-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px}
.ap-btn{border:1px solid #d0d7e2;background:#fff;border-radius:8px;padding:6px 10px;font-size:12px;cursor:pointer}
.ap-btn.primary{background:#0b5ed7;color:#fff;border-color:#0b5ed7}
.ap-btn.ok{background:#198754;color:#fff;border-color:#198754}
.ap-search{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:8px;padding:6px 10px;background:#fff}
.ap-search i{color:#0b5ed7}
.ap-search input{border:0;outline:0;font-size:12px;width:280px}
.ap-grid{border:1px solid #dcdcdc;height:500px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc;white-space:nowrap}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:8px;color:#0b5ed7}
.ap-actions i.fa-trash{color:#dc3545}
.ap-actions i.fa-undo{color:#198754}
.ap-chip{font-size:11px;background:#eef2ff;color:#1e3a8a;border-radius:10px;padding:2px 8px}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.warn{background:#fff3cd;color:#664d03}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:980px;margin:2.5% auto;padding:15px;border-radius:10px}
.ap-modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.ap-modal-title{font-size:16px;font-weight:700;color:#0b5ed7}
.ap-x{cursor:pointer;font-size:18px}
.ap-form{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input,.ap-input select{border:0;outline:0;width:100%;font-size:12px;background:transparent}
.ap-foot{display:flex;justify-content:flex-end;gap:8px;margin-top:10px}
.ap-err{display:none;background:#f8d7da;color:#842029;border:1px solid #f5c2c7;padding:8px;border-radius:8px;margin-bottom:8px}
.ap-drop{border:1px dashed #9aa7b7;border-radius:10px;padding:10px;background:#f8fafc}
.ap-pre{max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-id-badge"></i> Catálogo de Perfiles de Usuario</div>

  <div class="ap-toolbar">
    <button class="ap-btn primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-btn" onclick="toggleInactivos()" id="btnInact"><i class="fa fa-eye"></i> Ver inactivos</button>
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por empresa_id, ID_PERFIL, nombre..." onkeyup="buscar(event)">
    </div>
    <button class="ap-btn" onclick="cargar()"><i class="fa fa-rotate"></i> Refrescar</button>
    <button class="ap-btn" onclick="exportarDatos()"><i class="fa fa-file-export"></i> Exportar CSV</button>
    <button class="ap-btn ok" onclick="abrirImport()"><i class="fa fa-file-import"></i> Importar CSV</button>
    <button class="ap-btn" onclick="descargarLayout()"><i class="fa fa-download"></i> Layout</button>
  </div>

  <div class="ap-err" id="errBox"></div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th style="width:90px">Acciones</th>
          <th>empresa_id</th>
          <th>ID_PERFIL</th>
          <th>PER_NOMBRE</th>
          <th>cve_cia</th>
          <th>activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
</div>

<!-- Modal CRUD -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div class="ap-modal-head">
      <div class="ap-modal-title" id="mdlTitle"><i class="fa fa-id-badge"></i> Perfil</div>
      <div class="ap-x" onclick="cerrarModal('mdl')"><i class="fa fa-times"></i></div>
    </div>

    <div class="ap-err" id="mdlErr"></div>

    <input type="hidden" id="k_empresa_id">
    <input type="hidden" id="k_ID_PERFIL">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">empresa_id *</div>
        <div class="ap-input"><i class="fa fa-building"></i><input id="empresa_id"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">ID_PERFIL *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="ID_PERFIL"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">PER_NOMBRE *</div>
        <div class="ap-input"><i class="fa fa-font"></i><input id="PER_NOMBRE"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">cve_cia</div>
        <div class="ap-input"><i class="fa fa-sitemap"></i><input id="cve_cia"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">1 (Activo)</option>
            <option value="0">0 (Inactivo)</option>
          </select>
        </div>
      </div>
    </div>

    <div class="ap-foot">
      <button class="ap-btn" onclick="cerrarModal('mdl')">Cerrar</button>
      <button class="ap-btn primary" onclick="guardar()"><i class="fa fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Import -->
<div class="ap-modal" id="mdlImp">
  <div class="ap-modal-content">
    <div class="ap-modal-head">
      <div class="ap-modal-title"><i class="fa fa-file-import"></i> Importar Perfiles (CSV)</div>
      <div class="ap-x" onclick="cerrarModal('mdlImp')"><i class="fa fa-times"></i></div>
    </div>

    <div class="ap-err" id="impErr"></div>

    <div class="ap-toolbar" style="margin-bottom:10px">
      <button class="ap-btn" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
      <button class="ap-btn" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
      <button class="ap-btn primary" onclick="importarCsv()"><i class="fa fa-cloud-upload"></i> Importar</button>
    </div>

    <div class="ap-drop">
      <div><b>Archivo CSV</b> (UTF-8). Debe respetar el layout.</div>
      <div style="margin-top:8px"><input type="file" id="csvFile" accept=".csv,text/csv"></div>
    </div>

    <div style="margin-top:10px">
      <div class="ap-label">Vista previa</div>
      <div class="ap-pre" id="impPreview">(sin datos)</div>
    </div>

    <div class="ap-foot">
      <button class="ap-btn" onclick="cerrarModal('mdlImp')">Cerrar</button>
    </div>
  </div>
</div>

<script>
const API = '../api/perfiles_usuarios.php';
const KPI = '../api/perfiles_usuarios_kpi.php';

let verInactivos=false;
let qLast='';

function showError(id,msg){
  const el=document.getElementById(id);
  if(!msg){el.style.display='none'; el.innerHTML=''; return;}
  el.style.display='block'; el.innerHTML=msg;
}
function hideErrors(){ showError('errBox',''); showError('mdlErr',''); showError('impErr',''); }

function toggleInactivos(){
  verInactivos=!verInactivos;
  btnInact.innerHTML = verInactivos ? '<i class="fa fa-eye-slash"></i> Ocultar inactivos' : '<i class="fa fa-eye"></i> Ver inactivos';
  cargar();
}
function buscar(ev){
  if(ev && ev.key && ev.key!=='Enter') return;
  qLast=(q.value||'').trim();
  cargar();
}
function cargar(){
  hideErrors();
  fetch(API+'?action=list&inactivos='+(verInactivos?1:0)+'&q='+encodeURIComponent(qLast||''))
    .then(r=>r.json()).then(resp=>{
      if(resp.error){ showError('errBox',resp.error); return; }
      const rows=resp.rows||[];
      let h='';
      rows.forEach(c=>{
        const activo=(String(c.Activo||'1')==='1')?'<span class="ap-chip ok">Activo</span>':'<span class="ap-chip warn">Inactivo</span>';
        h+=`<tr>
          <td class="ap-actions">
            ${(String(c.Activo||'1')==='1' && !verInactivos)
              ? `<i class="fa fa-edit" title="Editar" onclick="editar('${c.empresa_id}','${c.ID_PERFIL}')"></i>
                 <i class="fa fa-trash" title="Inactivar" onclick="eliminar('${c.empresa_id}','${c.ID_PERFIL}')"></i>`
              : `<i class="fa fa-undo" title="Recuperar" onclick="recuperar('${c.empresa_id}','${c.ID_PERFIL}')"></i>`}
          </td>
          <td>${c.empresa_id||''}</td>
          <td><b>${c.ID_PERFIL||''}</b></td>
          <td>${c.PER_NOMBRE||''}</td>
          <td>${c.cve_cia||''}</td>
          <td>${activo}</td>
        </tr>`;
      });
      tb.innerHTML = h || '<tr><td colspan="6" style="padding:10px;color:#6c757d">Sin registros</td></tr>';
    });
}
function validar(){
  hideErrors();
  const d=[];
  const emp=(empresa_id.value||'').trim();
  const pid=(ID_PERFIL.value||'').trim();
  const nom=(PER_NOMBRE.value||'').trim();
  if(!emp) d.push('empresa_id obligatorio.');
  if(!pid) d.push('ID_PERFIL obligatorio.');
  if(!nom) d.push('PER_NOMBRE obligatorio.');
  if(d.length){ showError('mdlErr','Validación:<br>- '+d.join('<br>- ')); return false; }
  return true;
}
function nuevo(){
  hideErrors();
  mdlTitle.innerHTML='<i class="fa fa-id-badge"></i> Nuevo Perfil';
  k_empresa_id.value=''; k_ID_PERFIL.value='';
  empresa_id.value=''; ID_PERFIL.value=''; PER_NOMBRE.value=''; cve_cia.value=''; Activo.value='1';
  abrirModal('mdl');
}
function editar(emp,pid){
  hideErrors();
  fetch(API+'?action=get&empresa_id='+encodeURIComponent(emp)+'&ID_PERFIL='+encodeURIComponent(pid))
    .then(r=>r.json()).then(c=>{
      if(c.error){ showError('errBox',c.error); return; }
      mdlTitle.innerHTML='<i class="fa fa-id-badge"></i> Editar Perfil';
      k_empresa_id.value=c.empresa_id||''; k_ID_PERFIL.value=c.ID_PERFIL||'';
      empresa_id.value=c.empresa_id||''; ID_PERFIL.value=c.ID_PERFIL||''; PER_NOMBRE.value=c.PER_NOMBRE||'';
      cve_cia.value=c.cve_cia||''; Activo.value=String(c.Activo||'1');
      abrirModal('mdl');
    });
}
function guardar(){
  if(!validar()) return;
  const fd=new FormData();
  fd.append('action',(k_empresa_id.value && k_ID_PERFIL.value)?'update':'create');
  document.querySelectorAll('#mdl input').forEach(i=>fd.append(i.id,i.value));
  document.querySelectorAll('#mdl select').forEach(s=>fd.append(s.id,s.value));
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){ alert(resp.error + (resp.detalles?("\n- "+resp.detalles.join("\n- ")):'') ); return; }
    cerrarModal('mdl'); cargar();
  });
}
function eliminar(emp,pid){
  if(!confirm('¿Inactivar perfil?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('empresa_id',emp); fd.append('ID_PERFIL',pid);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{ if(resp.error) alert(resp.error); cargar(); });
}
function recuperar(emp,pid){
  if(!confirm('¿Recuperar perfil?')) return;
  const fd=new FormData(); fd.append('action','restore'); fd.append('empresa_id',emp); fd.append('ID_PERFIL',pid);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{ if(resp.error) alert(resp.error); cargar(); });
}
function exportarDatos(){
  window.open(API+'?action=export&inactivos='+(verInactivos?1:0)+'&q='+encodeURIComponent(qLast||''),'_blank');
}
function descargarLayout(){ window.open(API+'?action=layout','_blank'); }
function abrirImport(){ hideErrors(); csvFile.value=''; impPreview.innerText='(sin datos)'; abrirModal('mdlImp'); }
function previsualizarCsv(){
  hideErrors();
  const f=csvFile.files[0]; if(!f){ showError('impErr','Selecciona un CSV.'); return; }
  const fd=new FormData(); fd.append('action','import_preview'); fd.append('csv',f);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){ showError('impErr',resp.error + (resp.detalles?'<br>- '+resp.detalles.join('<br>- '):'')); return; }
    impPreview.innerText=resp.preview_text||'(sin datos)';
  });
}
function importarCsv(){
  hideErrors();
  const f=csvFile.files[0]; if(!f){ showError('impErr','Selecciona un CSV.'); return; }
  if(!confirm('¿Importar CSV? UPSERT por empresa_id + ID_PERFIL.')) return;
  const fd=new FormData(); fd.append('action','import'); fd.append('csv',f);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){ showError('impErr',resp.error + (resp.detalles?'<br>- '+resp.detalles.join('<br>- '):'')); return; }
    cerrarModal('mdlImp'); cargar();
    alert('Importación OK. Insertados: '+(resp.inserted||0)+' / Actualizados: '+(resp.updated||0)+' / Errores: '+(resp.errors||0));
  });
}
function abrirModal(id){ document.getElementById(id).style.display='block'; }
function cerrarModal(id){ document.getElementById(id).style.display='none'; }

cargar();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
