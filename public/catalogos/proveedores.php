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
.ap-badge{
  display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;
  background:#eef2ff;color:#1e3a8a;margin-right:4px
}
.ap-badge.warn{background:#fff3cd;color:#7a5d00}
.ap-badge.ok{background:#d1e7dd;color:#0f5132}
.ap-req{color:#dc3545;font-weight:700;margin-left:6px}
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
.ap-input input,.ap-input select{
  border:0;outline:0;font-size:12px;width:100%;background:transparent
}
.ap-help{font-size:11px;color:#6c757d}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
.ap-error{color:#dc3545;font-size:11px;display:none}
.ap-row{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
</style>

<div class="ap-container">

  <div class="ap-header">
    <div class="ap-title"><i class="fa fa-industry"></i> Catálogo de Proveedores</div>

    <div class="ap-toolbar">
      <div class="ap-search" title="Buscar por clave, empresa, nombre, RUT, ciudad, estado, país, teléfonos, dirección, ID externo">
        <i class="fa fa-search"></i>
        <input id="q" placeholder="Buscar proveedor…" onkeydown="if(event.key==='Enter')buscar()">
        <button class="ghost" onclick="limpiarBusqueda()" title="Limpiar"><i class="fa fa-times"></i></button>
      </div>

      <button onclick="nuevoProveedor()">➕ Agregar</button>
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
          <th>Empresa</th>
          <th>Nombre</th>
          <th>RUT</th>
          <th>Ciudad</th>
          <th>Estado</th>
          <th>País</th>
          <th>Tel 1</th>
          <th>Flags</th>
        </tr>
      </thead>
      <tbody id="tbodyProv"></tbody>
    </table>
  </div>

</div>

<!-- MODAL PROVEEDOR (FULL) -->
<div class="ap-modal" id="mdlProv">
  <div class="ap-modal-content">
    <div class="ap-row">
      <h3>Proveedor</h3>
      <div class="ap-help">
        <span class="ap-req">*</span> Campos obligatorios: <b>Clave, Empresa/Nombre, País</b>
      </div>
    </div>

    <input type="hidden" id="ID_Proveedor">

    <div class="ap-form-grid">

      <!-- cve_proveedor (obligatorio) -->
      <div class="ap-field">
        <div class="ap-label">Clave proveedor <span class="ap-req">*</span></div>
        <div class="ap-input">
          <i class="fa fa-hashtag"></i>
          <input id="cve_proveedor" placeholder="Ej. PROV-001">
        </div>
        <div class="ap-error" id="err_cve_proveedor">Clave obligatoria.</div>
      </div>

      <!-- Empresa -->
      <div class="ap-field">
        <div class="ap-label">Empresa</div>
        <div class="ap-input">
          <i class="fa fa-building"></i>
          <input id="Empresa" placeholder="Razón social / empresa">
        </div>
      </div>

      <!-- Nombre -->
      <div class="ap-field">
        <div class="ap-label">Nombre proveedor</div>
        <div class="ap-input">
          <i class="fa fa-user-tie"></i>
          <input id="Nombre" placeholder="Nombre comercial / contacto principal">
        </div>
      </div>

      <!-- RUT -->
      <div class="ap-field">
        <div class="ap-label">RUT / RFC</div>
        <div class="ap-input">
          <i class="fa fa-id-card"></i>
          <input id="RUT" placeholder="Identificador fiscal">
        </div>
      </div>

      <!-- dirección -->
      <div class="ap-field">
        <div class="ap-label">Dirección</div>
        <div class="ap-input">
          <i class="fa fa-map-marker-alt"></i>
          <input id="direccion" placeholder="Calle y número">
        </div>
      </div>

      <!-- colonia -->
      <div class="ap-field">
        <div class="ap-label">Colonia</div>
        <div class="ap-input">
          <i class="fa fa-map"></i>
          <input id="colonia" placeholder="Colonia">
        </div>
      </div>

      <!-- ciudad -->
      <div class="ap-field">
        <div class="ap-label">Ciudad</div>
        <div class="ap-input">
          <i class="fa fa-city"></i>
          <input id="ciudad" placeholder="Ciudad">
        </div>
      </div>

      <!-- estado -->
      <div class="ap-field">
        <div class="ap-label">Estado</div>
        <div class="ap-input">
          <i class="fa fa-flag"></i>
          <input id="estado" placeholder="Estado">
        </div>
      </div>

      <!-- pais (obligatorio) -->
      <div class="ap-field">
        <div class="ap-label">País <span class="ap-req">*</span></div>
        <div class="ap-input">
          <i class="fa fa-globe-americas"></i>
          <input id="pais" placeholder="País">
        </div>
        <div class="ap-error" id="err_pais">País obligatorio.</div>
      </div>

      <!-- cve_dane -->
      <div class="ap-field">
        <div class="ap-label">CVE DANE</div>
        <div class="ap-input">
          <i class="fa fa-barcode"></i>
          <input id="cve_dane" placeholder="Código DANE">
        </div>
      </div>

      <!-- ID_Externo -->
      <div class="ap-field">
        <div class="ap-label">ID Externo</div>
        <div class="ap-input">
          <i class="fa fa-link"></i>
          <input id="ID_Externo" placeholder="ID en sistema externo">
        </div>
      </div>

      <!-- telefono1 -->
      <div class="ap-field">
        <div class="ap-label">Teléfono 1</div>
        <div class="ap-input">
          <i class="fa fa-phone"></i>
          <input id="telefono1" placeholder="Solo números" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
      </div>

      <!-- telefono2 -->
      <div class="ap-field">
        <div class="ap-label">Teléfono 2</div>
        <div class="ap-input">
          <i class="fa fa-phone-alt"></i>
          <input id="telefono2" placeholder="Solo números" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
      </div>

      <!-- es_cliente -->
      <div class="ap-field">
        <div class="ap-label">¿Es Cliente?</div>
        <div class="ap-input">
          <i class="fa fa-user-check"></i>
          <select id="es_cliente">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
        </div>
      </div>

      <!-- es_transportista -->
      <div class="ap-field">
        <div class="ap-label">¿Es Transportista?</div>
        <div class="ap-input">
          <i class="fa fa-truck"></i>
          <select id="es_transportista">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
        </div>
      </div>

      <!-- envio_correo_automatico -->
      <div class="ap-field">
        <div class="ap-label">Envío correo automático</div>
        <div class="ap-input">
          <i class="fa fa-envelope"></i>
          <select id="envio_correo_automatico">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
        </div>
      </div>

      <!-- latitud -->
      <div class="ap-field">
        <div class="ap-label">Latitud</div>
        <div class="ap-input">
          <i class="fa fa-location-arrow"></i>
          <input id="latitud" placeholder="Ej. 19.4326">
        </div>
      </div>

      <!-- longitud -->
      <div class="ap-field">
        <div class="ap-label">Longitud</div>
        <div class="ap-input">
          <i class="fa fa-location-arrow"></i>
          <input id="longitud" placeholder="Ej. -99.1332">
        </div>
      </div>

      <!-- Activo -->
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

    <div class="ap-error" id="err_empresa_nombre" style="margin-top:6px">
      Debe capturar al menos <b>Empresa</b> o <b>Nombre</b>.
    </div>

    <div style="text-align:right;margin-top:10px">
      <button class="primary" onclick="guardarProveedor()">Guardar</button>
      <button class="ghost" onclick="cerrarModal('mdlProv')">Cancelar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content">
    <h3>Importar proveedores</h3>

    <div class="ap-help">Layout FULL (todos los campos). Previsualiza antes de importar.</div>
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
const API = '../api/proveedores.php';
let verInactivos = false;
let qLast = '';

function badgeFlags(p){
  let b = '';
  if((p.es_cliente||0)==1) b += `<span class="ap-badge ok"><i class="fa fa-user-check"></i> Cliente</span>`;
  if((p.es_transportista||0)==1) b += `<span class="ap-badge ok"><i class="fa fa-truck"></i> Transportista</span>`;
  if((p.envio_correo_automatico||0)==1) b += `<span class="ap-badge"><i class="fa fa-envelope"></i> AutoMail</span>`;
  return b || `<span class="ap-badge warn">N/A</span>`;
}

function reqIndicator(p){
  // Obligatorios: cve_proveedor, (Empresa o Nombre), pais
  const hasCve = !!(p.cve_proveedor && String(p.cve_proveedor).trim()!=='');
  const hasEN  = !!((p.Empresa && String(p.Empresa).trim()!=='') || (p.Nombre && String(p.Nombre).trim()!==''));
  const hasPais= !!(p.pais && String(p.pais).trim()!=='');
  const ok = hasCve && hasEN && hasPais;
  return `<span class="ap-req-dot ${ok?'ap-req-ok':''}" title="${ok?'OK':'Faltan obligatorios'}"></span>`;
}

function cargar(){
  const url = API + '?action=list&inactivos='+(verInactivos?1:0) + '&q=' + encodeURIComponent(qLast||'');
  fetch(url)
    .then(r=>r.json())
    .then(rows=>{
      let h='';
      rows.forEach(p=>{
        h+=`
        <tr>
          <td class="ap-actions">
            ${verInactivos
              ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${p.ID_Proveedor})"></i>`
              : `<i class="fa fa-edit" title="Editar" onclick="editar(${p.ID_Proveedor})"></i>
                 <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${p.ID_Proveedor})"></i>`}
          </td>
          <td>${reqIndicator(p)}</td>
          <td>${p.cve_proveedor||''}</td>
          <td>${p.Empresa||''}</td>
          <td>${p.Nombre||''}</td>
          <td>${p.RUT||''}</td>
          <td>${p.ciudad||''}</td>
          <td>${p.estado||''}</td>
          <td>${p.pais||''}</td>
          <td>${p.telefono1||''}</td>
          <td>${badgeFlags(p)}</td>
        </tr>`;
      });
      tbodyProv.innerHTML = h || `<tr><td colspan="11" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    });
}

function buscar(){
  qLast = document.getElementById('q').value.trim();
  cargar();
}
function limpiarBusqueda(){
  document.getElementById('q').value='';
  qLast = '';
  cargar();
}

function nuevoProveedor(){
  document.querySelectorAll('#mdlProv input').forEach(i=>i.value='');
  document.querySelectorAll('#mdlProv select').forEach(s=>s.value = s.id==='Activo' ? '1' : '0');
  hideErrors();
  mdlProv.style.display='block';
}

function editar(id){
  fetch(API+'?action=get&id='+id)
    .then(r=>r.json())
    .then(p=>{
      // set inputs
      for(let k in p){
        const el = document.getElementById(k);
        if(!el) continue;
        el.value = (p[k]===null || p[k]===undefined) ? '' : p[k];
      }
      hideErrors();
      mdlProv.style.display='block';
    });
}

function hideErrors(){
  ['err_cve_proveedor','err_pais','err_empresa_nombre'].forEach(id=>{
    const el=document.getElementById(id); if(el) el.style.display='none';
  });
}

function validarObligatorios(){
  let ok = true;
  const cve = (cve_proveedor.value||'').trim();
  const emp = (Empresa.value||'').trim();
  const nom = (Nombre.value||'').trim();
  const pa  = (pais.value||'').trim();

  hideErrors();

  if(!cve){ document.getElementById('err_cve_proveedor').style.display='block'; ok=false; }
  if(!pa){ document.getElementById('err_pais').style.display='block'; ok=false; }
  if(!emp && !nom){ document.getElementById('err_empresa_nombre').style.display='block'; ok=false; }

  // Normalizaciones
  telefono1.value = (telefono1.value||'').replace(/[^0-9]/g,'');
  telefono2.value = (telefono2.value||'').replace(/[^0-9]/g,'');

  return ok;
}

function guardarProveedor(){
  if(!validarObligatorios()) return;

  const fd = new FormData();
  fd.append('action', ID_Proveedor.value ? 'update' : 'create');

  // inputs + selects
  document.querySelectorAll('#mdlProv input').forEach(i=>fd.append(i.id,i.value));
  document.querySelectorAll('#mdlProv select').forEach(s=>fd.append(s.id,s.value));

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp && resp.error){ alert(resp.error); return; }
      cerrarModal('mdlProv');
      cargar();
    });
}

function eliminar(id){
  if(!confirm('¿Inactivar proveedor?')) return;
  const fd=new FormData();
  fd.append('action','delete');
  fd.append('id',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}

function recuperar(id){
  const fd=new FormData();
  fd.append('action','restore');
  fd.append('id',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}

function exportarDatos(){ window.open(API+'?action=export_csv&tipo=datos','_blank'); }
function descargarLayout(){ window.open(API+'?action=export_csv&tipo=layout','_blank'); }

function abrirImport(){
  document.getElementById('fileCsv').value = '';
  document.getElementById('csvPreviewWrap').style.display='none';
  document.getElementById('importMsg').innerHTML='';
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

function toggleInactivos(){
  verInactivos = !verInactivos;
  cargar();
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded',cargar);
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
