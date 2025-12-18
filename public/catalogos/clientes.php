<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7}
.ap-toolbar{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.ap-toolbar button{padding:4px 10px;font-size:12px}
.ap-search{
  display:flex;align-items:center;gap:6px;
  border:1px solid #d0d7e2;border-radius:6px;padding:4px 8px;background:#fff
}
.ap-search i{color:#0b5ed7}
.ap-search input{border:0;outline:0;font-size:12px;width:260px}
.ap-grid{border:1px solid #dcdcdc;height:520px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:8px;color:#0b5ed7}
.ap-req-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc3545;margin-right:6px}
.ap-req-ok{background:#198754}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:860px;margin:2.5% auto;padding:15px;border-radius:8px}
.ap-modal-content h3{margin:0 0 10px 0}
.ap-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{
  display:flex;align-items:center;gap:8px;
  border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff
}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input{
  border:0;outline:0;font-size:12px;width:100%;background:transparent
}
.ap-help{font-size:11px;color:#6c757d}
.ap-error{color:#dc3545;font-size:11px;display:none}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
.ap-row{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
</style>

<div class="ap-container">

  <div class="ap-header">
    <div class="ap-title"><i class="fa fa-users"></i> Catálogo de Clientes</div>

    <div class="ap-toolbar">
      <div class="ap-search" title="Buscar por clave, razón social, comercial, RFC, ciudad, estado, teléfono, email">
        <i class="fa fa-search"></i>
        <input id="q" placeholder="Buscar cliente…" onkeydown="if(event.key==='Enter')buscar()">
        <button class="ghost" onclick="limpiarBusqueda()" title="Limpiar"><i class="fa fa-times"></i></button>
      </div>

      <button onclick="nuevoCliente()">➕ Agregar</button>
      <button onclick="exportarDatos()">⬇ Exportar</button>
      <button onclick="abrirImport()">⬆ Importar</button>
      <button onclick="toggleInactivos()">👁 Inactivos</button>
    </div>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Req</th>
          <th>Clave</th>
          <th>Razón Social</th>
          <th>Comercial</th>
          <th>RFC</th>
          <th>Ciudad</th>
          <th>Estado</th>
          <th>Teléfono</th>
          <th>Email</th>
          <th>Crédito</th>
        </tr>
      </thead>
      <tbody id="tbodyClientes"></tbody>
    </table>
  </div>

</div>

<!-- MODAL CLIENTE -->
<div class="ap-modal" id="mdlCliente">
  <div class="ap-modal-content">
    <div class="ap-row">
      <h3>Cliente</h3>
      <div class="ap-help"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Clave</b> y <b>Razón Social</b></div>
    </div>

    <input type="hidden" id="id_cliente">

    <div class="ap-form-grid">

      <div class="ap-field">
        <div class="ap-label">Clave <span style="color:#dc3545">*</span></div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="Cve_Clte" placeholder="Ej. CLI-001"></div>
        <div class="ap-error" id="err_cve">Clave obligatoria.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Razón Social <span style="color:#dc3545">*</span></div>
        <div class="ap-input"><i class="fa fa-building"></i><input id="RazonSocial" placeholder="Razón Social"></div>
        <div class="ap-error" id="err_razon">Razón Social obligatoria.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Razón Comercial</div>
        <div class="ap-input"><i class="fa fa-store"></i><input id="RazonComercial" placeholder="Comercial"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">RFC</div>
        <div class="ap-input"><i class="fa fa-id-card"></i><input id="RFC" placeholder="RFC"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ciudad</div>
        <div class="ap-input"><i class="fa fa-city"></i><input id="Ciudad" placeholder="Ciudad"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Estado</div>
        <div class="ap-input"><i class="fa fa-flag"></i><input id="Estado" placeholder="Estado"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Teléfono</div>
        <div class="ap-input"><i class="fa fa-phone"></i>
          <input id="Telefono1" placeholder="Solo números" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Email</div>
        <div class="ap-input"><i class="fa fa-envelope"></i><input id="email_cliente" placeholder="correo@dominio.com"></div>
        <div class="ap-error" id="err_email">Email inválido.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Crédito</div>
        <div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="credito" placeholder="0.00"></div>
      </div>

    </div>

    <div style="text-align:right;margin-top:10px">
      <button class="primary" onclick="guardarCliente()">Guardar</button>
      <button class="ghost" onclick="cerrarModal('mdlCliente')">Cancelar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content">
    <h3>Importar clientes</h3>
    <div class="ap-help">Layout con previsualización. Importa con UPSERT por Clave.</div>

    <input type="file" id="fileCsv" accept=".csv">

    <div style="margin:10px 0">
      <button class="ghost" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout CSV</button>
      <button class="primary" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
    </div>

    <div id="csvPreviewWrap" style="display:none;margin-top:10px">
      <div class="ap-grid" style="max-height:260px">
        <table>
          <thead id="csvHead"></thead>
          <tbody id="csvBody"></tbody>
        </table>
      </div>
      <div style="text-align:right;margin-top:8px">
        <button class="primary" onclick="importarCsv()"><i class="fa fa-upload"></i> Importar</button>
        <button class="ghost" onclick="cerrarModal('mdlImport')">Cancelar</button>
      </div>
      <div class="ap-help" id="importMsg" style="margin-top:8px"></div>
    </div>

  </div>
</div>

<script>
const API = '../api/clientes.php';
let verInactivos = false;
let qLast = '';

function reqIndicator(c){
  const hasCve = !!(c.Cve_Clte && String(c.Cve_Clte).trim()!=='');
  const hasRaz = !!(c.RazonSocial && String(c.RazonSocial).trim()!=='');
  const ok = hasCve && hasRaz;
  return `<span class="ap-req-dot ${ok?'ap-req-ok':''}" title="${ok?'OK':'Faltan obligatorios'}"></span>`;
}

function cargar(){
  const url = API + '?action=list&inactivos='+(verInactivos?1:0)+'&q='+encodeURIComponent(qLast||'');
  fetch(url).then(r=>r.json()).then(rows=>{
    let h='';
    rows.forEach(c=>{
      h+=`
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${c.id_cliente})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${c.id_cliente})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${c.id_cliente})"></i>`}
        </td>
        <td>${reqIndicator(c)}</td>
        <td>${c.Cve_Clte||''}</td>
        <td>${c.RazonSocial||''}</td>
        <td>${c.RazonComercial||''}</td>
        <td>${c.RFC||''}</td>
        <td>${c.Ciudad||''}</td>
        <td>${c.Estado||''}</td>
        <td>${c.Telefono1||''}</td>
        <td>${c.email_cliente||''}</td>
        <td>${c.credito||0}</td>
      </tr>`;
    });
    tbodyClientes.innerHTML = h || `<tr><td colspan="11" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
  });
}

function buscar(){ qLast = q.value.trim(); cargar(); }
function limpiarBusqueda(){ q.value=''; qLast=''; cargar(); }

function hideErrors(){
  err_cve.style.display='none';
  err_razon.style.display='none';
  err_email.style.display='none';
}

function nuevoCliente(){
  document.querySelectorAll('#mdlCliente input').forEach(i=>i.value='');
  hideErrors();
  mdlCliente.style.display='block';
}

function editar(id){
  fetch(API+'?action=get&id_cliente='+id)
    .then(r=>r.json())
    .then(c=>{
      for(let k in c){
        const el = document.getElementById(k);
        if(el) el.value = (c[k]===null||c[k]===undefined)?'':c[k];
      }
      hideErrors();
      mdlCliente.style.display='block';
    });
}

function emailValido(v){
  v = (v||'').trim();
  if(v==='') return true;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

function validar(){
  hideErrors();
  let ok=true;
  if(!Cve_Clte.value.trim()){ err_cve.style.display='block'; ok=false; }
  if(!RazonSocial.value.trim()){ err_razon.style.display='block'; ok=false; }
  if(!emailValido(email_cliente.value)){ err_email.style.display='block'; ok=false; }
  Telefono1.value = (Telefono1.value||'').replace(/[^0-9]/g,'');
  return ok;
}

function guardarCliente(){
  if(!validar()) return;

  const fd=new FormData();
  fd.append('action', id_cliente.value ? 'update' : 'create');
  document.querySelectorAll('#mdlCliente input').forEach(i=>fd.append(i.id,i.value));

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp && resp.error){
        alert(resp.error + (resp.detalles ? "\\n- " + resp.detalles.join("\\n- ") : ''));
        return;
      }
      cerrarModal('mdlCliente');
      cargar();
    });
}

function eliminar(id){
  if(!confirm('¿Inactivar cliente?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id_cliente',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}

function recuperar(id){
  const fd=new FormData(); fd.append('action','restore'); fd.append('id_cliente',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}

function exportarDatos(){ window.open(API+'?action=export_csv&tipo=datos','_blank'); }
function descargarLayout(){ window.open(API+'?action=export_csv&tipo=layout','_blank'); }

function abrirImport(){
  fileCsv.value='';
  csvPreviewWrap.style.display='none';
  importMsg.innerHTML='';
  mdlImport.style.display='block';
}

function previsualizarCsv(){
  const f=fileCsv.files[0];
  if(!f){ alert('Selecciona un CSV'); return; }
  const r=new FileReader();
  r.onload=e=>{
    const rows=e.target.result.split('\n').filter(x=>x.trim()!=='');
    csvHead.innerHTML='<tr>'+rows[0].split(',').map(h=>`<th>${h}</th>`).join('')+'</tr>';
    csvBody.innerHTML=rows.slice(1,6).map(r=>'<tr>'+r.split(',').map(c=>`<td>${c}</td>`).join('')+'</tr>').join('');
    csvPreviewWrap.style.display='block';
    importMsg.innerHTML='';
  };
  r.readAsText(f);
}

function importarCsv(){
  const fd=new FormData();
  fd.append('action','import_csv');
  fd.append('file',fileCsv.files[0]);

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp.error){
        importMsg.innerHTML = `<span style="color:#dc3545"><b>Error:</b> ${resp.error}</span>`;
        return;
      }
      const ok = resp.rows_ok ?? 0;
      const err= resp.rows_err ?? 0;
      importMsg.innerHTML = `<span style="color:#0f5132"><b>Importación:</b> OK ${ok} | Err ${err}</span>`;
      if(err>0 && resp.errores){
        importMsg.innerHTML += `<div class="ap-help">Primeros errores: ${resp.errores.slice(0,3).map(e=>`Fila ${e.fila}: ${e.motivo}`).join(' · ')}</div>`;
      }
      cerrarModal('mdlImport');
      cargar();
    });
}

function toggleInactivos(){ verInactivos=!verInactivos; cargar(); }
function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded',cargar);
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
