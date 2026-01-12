<?php
require_once __DIR__ . '/../bi/_menu_global.php';

$preCveClte = trim((string) ($_GET['Cve_Clte'] ?? ''));
?>

<style>
/* =========================================================
   ASSISTPRO – DESTINATARIOS
========================================================= */
body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6fb; margin: 0; }
.ap-container { padding: 20px; font-size: 13px; max-width: 1800px; margin: 0 auto; }

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
.ap-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
.ap-card {
  background: #fff;
  border: 1px solid #e0e6ed;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  transition: all 0.2s;
}
.ap-card:hover { border-color: #0b5ed7; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(11, 94, 215, 0.1); }
.ap-card .h { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-weight: 600; color: #333; }
.ap-card .k { font-size: 24px; font-weight: 700; color: #0b5ed7; }

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
.ap-grid table { width: 100%; border-collapse: collapse; font-size: 11px; }
.ap-grid th { background: #f8f9fa; padding: 10px 8px; text-align: left; font-weight: 600; color: #495057; border-bottom: 1px solid #dee2e6; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
.ap-grid td { padding: 8px; border-bottom: 1px solid #f1f3f5; color: #212529; vertical-align: middle; }
.ap-grid tr:hover td { background: #f8f9fa; }
.ap-actions i { cursor: pointer; margin-right: 10px; color: #6c757d; transition: color 0.2s; font-size: 13px; }
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
.ap-modal-content { background: #fff; width: 1100px; max-width: 95%; max-height: 90vh; border-radius: 12px; display: flex; flex-direction: column; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 20px; }

.ap-form { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px; }
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
  <div class="ap-title"><i class="fa fa-map-marked-alt"></i> Catálogo de Destinatarios</div>

  <div class="ap-cards">
    <div class="ap-card">
      <div class="h"><span>Total</span><i class="fa fa-list"></i></div>
      <div class="k" id="kTotal">0</div>
    </div>
    <div class="ap-card">
      <div class="h"><span>Activos</span><i class="fa fa-check-circle" style="color:#198754"></i></div>
      <div class="k" id="kActivos">0</div>
    </div>
    <div class="ap-card">
      <div class="h"><span>Inactivos</span><i class="fa fa-times-circle" style="color:#dc3545"></i></div>
      <div class="k" id="kInactivos">0</div>
    </div>
    <div class="ap-card">
      <div class="h"><span>Principales</span><i class="fa fa-star" style="color:#ffc107"></i></div>
      <div class="k" id="kPrincipales">0</div>
    </div>
  </div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar razón social, dirección, contacto…" onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
    <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
    <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Inactivos</button>
  </div>

  <div style="margin-bottom:10px; display:flex; gap:10px; align-items:center;">
    <label style="font-size:13px; font-weight:500;">Cliente:</label>
    <input id="fCveClte" style="padding:6px 12px; border:1px solid #dee2e6; border-radius:6px; font-size:13px; width:200px;" placeholder="Ej: CLI0001" value="<?= htmlspecialchars($preCveClte) ?>" <?= $preCveClte ? 'readonly' : '' ?>>
    <button class="ap-chip" onclick="buscar()">Filtrar</button>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>ID</th>
          <th>Cliente</th>
          <th>Clave</th>
          <th>Razón Social</th>
          <th>Dirección</th>
          <th>Colonia</th>
          <th>CP</th>
          <th>Ciudad</th>
          <th>Estado</th>
          <th>Contacto</th>
          <th>Teléfono</th>
          <th>Email</th>
          <th>Vendedor</th>
          <th>Principal</th>
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
      <h3 style="margin:0"><i class="fa fa-map-marker-alt"></i> Destinatario</h3>
      <button onclick="cerrarModal('mdl')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Cliente</b>, <b>Clave</b></div>

    <input type="hidden" id="id_destinatario">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Cliente *</div>
        <div class="ap-input"><i class="fa fa-user"></i><input id="Cve_Clte" placeholder="CLI0001"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clave *</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="clave_destinatario" placeholder="CLI0001-PRINC"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Razón Social</div>
        <div class="ap-input"><i class="fa fa-building"></i><input id="razonsocial" placeholder="Razón Social"></div>
      </div>

      <div class="ap-field" style="grid-column: span 2">
        <div class="ap-label">Dirección</div>
        <div class="ap-input"><i class="fa fa-map-marker"></i><input id="direccion" placeholder="Calle y número"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Colonia</div>
        <div class="ap-input"><i class="fa fa-home"></i><input id="colonia" placeholder="Colonia"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Código Postal</div>
        <div class="ap-input"><i class="fa fa-envelope"></i><input id="postal" placeholder="00000"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ciudad</div>
        <div class="ap-input"><i class="fa fa-city"></i><input id="ciudad" placeholder="Ciudad"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Estado</div>
        <div class="ap-input"><i class="fa fa-flag"></i><input id="estado" placeholder="Estado"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Contacto</div>
        <div class="ap-input"><i class="fa fa-user-circle"></i><input id="contacto" placeholder="Nombre contacto"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Teléfono</div>
        <div class="ap-input"><i class="fa fa-phone"></i><input id="telefono" placeholder="1234567890"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Email</div>
        <div class="ap-input"><i class="fa fa-at"></i><input id="email_destinatario" placeholder="correo@dominio.com"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Vendedor</div>
        <div class="ap-input"><i class="fa fa-user-tie"></i><input id="cve_vendedor" placeholder="Clave vendedor"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Principal</div>
        <div class="ap-input"><i class="fa fa-star"></i>
          <select id="dir_principal"><option value="0">No</option><option value="1">Sí</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo"><option value="1">Activo</option><option value="0">Inactivo</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Latitud</div>
        <div class="ap-input"><i class="fa fa-map-pin"></i><input id="latitud" placeholder="0.000000"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Longitud</div>
        <div class="ap-input"><i class="fa fa-map-pin"></i><input id="longitud" placeholder="0.000000"></div>
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
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar destinatarios</h3>
      <button onclick="cerrarModal('mdlImport')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>Cve_Clte + clave_destinatario</b>. Descarga layout primero.</div>

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
const API = '../api/destinatarios.php';
const PRE_CVE = <?= json_encode($preCveClte) ?>;

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
  const cveClte = fCveClte.value.trim();
  const url = API+'?action=list'
    + '&inactivos='+(verInactivos?1:0)
    + '&Cve_Clte='+encodeURIComponent(cveClte)
    + '&q='+encodeURIComponent(qLast||'')
    + '&limit='+perPage
    + '&offset='+((page-1)*perPage);

  fetch(url).then(r=>r.json()).then(resp=>{
    const rows = resp.rows || resp || [];
    total = Number(resp.total||0) || 0;
    lastRows = rows;

    // KPIs
    kTotal.textContent = total;
    const activos = rows.filter(r=>Number(r.Activo||1)===1).length;
    kActivos.textContent = activos;
    kInactivos.textContent = total - activos;
    kPrincipales.textContent = rows.filter(r=>Number(r.dir_principal||0)===1).length;

    let h='';
    rows.forEach(r=>{
      const activo = Number(r.Activo||1)===1;
      h+=`
      <tr>
        <td class="ap-actions">
          <i class="fa fa-edit" title="Editar" onclick="editar(${r.id_destinatario})"></i>
          ${activo
            ? `<i class="fa fa-trash" title="Inactivar" onclick="eliminar(${r.id_destinatario})"></i>`
            : `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${r.id_destinatario})"></i>`}
        </td>
        <td>${r.id_destinatario||''}</td>
        <td>${r.Cve_Clte||''}</td>
        <td><b>${r.clave_destinatario||''}</b></td>
        <td>${r.razonsocial||''}</td>
        <td>${r.direccion||''}</td>
        <td>${r.colonia||''}</td>
        <td>${r.postal||''}</td>
        <td>${r.ciudad||''}</td>
        <td>${r.estado||''}</td>
        <td>${r.contacto||''}</td>
        <td>${r.telefono||''}</td>
        <td>${r.email_destinatario||''}</td>
        <td>${r.cve_vendedor||''}</td>
        <td>${Number(r.dir_principal||0)===1 ? '<span class="ap-chip" style="background:#fff3cd;color:#664d03">★</span>' : ''}</td>
        <td>${activo ? '1':'0'}</td>
      </tr>`;
    });
    tb.innerHTML = h || `<tr><td colspan="16" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    setPager();
  });
}

function buscar(){ qLast = q.value.trim(); page=1; cargar(); }
function limpiar(){ q.value=''; qLast=''; if(!PRE_CVE) fCveClte.value=''; page=1; cargar(); }
function toggleInactivos(){ verInactivos=!verInactivos; page=1; cargar(); }

function nuevo(){
  id_destinatario.value='';
  Cve_Clte.value = fCveClte.value.trim() || '';
  clave_destinatario.value = Cve_Clte.value ? Cve_Clte.value+'-PRINC' : '';
  razonsocial.value='';
  direccion.value='';
  colonia.value='';
  postal.value='';
  ciudad.value='';
  estado.value='';
  contacto.value='';
  telefono.value='';
  email_destinatario.value='';
  cve_vendedor.value='';
  dir_principal.value='1';
  Activo.value='1';
  latitud.value='';
  longitud.value='';
  if(PRE_CVE) Cve_Clte.readOnly=true;
  mdl.style.display='block';
}

function editar(id){
  fetch(API+'?action=get&id_destinatario='+id).then(r=>r.json()).then(resp=>{
    const d = resp.data || resp;
    id_destinatario.value = d.id_destinatario||'';
    Cve_Clte.value = d.Cve_Clte||'';
    clave_destinatario.value = d.clave_destinatario||'';
    razonsocial.value = d.razonsocial||'';
    direccion.value = d.direccion||'';
    colonia.value = d.colonia||'';
    postal.value = d.postal||'';
    ciudad.value = d.ciudad||'';
    estado.value = d.estado||'';
    contacto.value = d.contacto||'';
    telefono.value = d.telefono||'';
    email_destinatario.value = d.email_destinatario||'';
    cve_vendedor.value = d.cve_vendedor||'';
    dir_principal.value = String(d.dir_principal||'0');
    Activo.value = String(d.Activo||'1');
    latitud.value = d.latitud||'';
    longitud.value = d.longitud||'';
    if(PRE_CVE) Cve_Clte.readOnly=true;
    mdl.style.display='block';
  });
}

function guardar(){
  if(!Cve_Clte.value.trim() || !clave_destinatario.value.trim()){
    alert('Cliente y Clave son obligatorios');
    return;
  }

  const fd=new FormData();
  fd.append('action', id_destinatario.value ? 'update' : 'create');
  fd.append('id_destinatario', id_destinatario.value);
  fd.append('Cve_Clte', Cve_Clte.value);
  fd.append('clave_destinatario', clave_destinatario.value);
  fd.append('razonsocial', razonsocial.value);
  fd.append('direccion', direccion.value);
  fd.append('colonia', colonia.value);
  fd.append('postal', postal.value);
  fd.append('ciudad', ciudad.value);
  fd.append('estado', estado.value);
  fd.append('contacto', contacto.value);
  fd.append('telefono', telefono.value);
  fd.append('email_destinatario', email_destinatario.value);
  fd.append('cve_vendedor', cve_vendedor.value);
  fd.append('dir_principal', dir_principal.value);
  fd.append('Activo', Activo.value);
  fd.append('latitud', latitud.value);
  fd.append('longitud', longitud.value);

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp && resp.error){
        alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ''));
        return;
      }
      cerrarModal('mdl');
      cargar();
    });
}

function eliminar(id){
  if(!confirm('¿Inactivar destinatario?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id_destinatario',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}

function recuperar(id){
  const fd=new FormData(); fd.append('action','restore'); fd.append('id_destinatario',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}

function exportarDatos(){ window.open(API+'?action=export_csv&tipo=datos','_blank'); }
function descargarLayout(){ window.open(API+'?action=export_csv&tipo=layout','_blank'); }

function abrirImport(){
  fileCsv.value='';
  importMsg.style.display='none';
  mdlImport.style.display='block';
}

function importarCsv(){
  const f=fileCsv.files[0];
  if(!f){ alert('Selecciona un CSV'); return; }

  const fd=new FormData();
  fd.append('action','import_csv');
  fd.append('file',f);

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      importMsg.style.display='flex';
      if(resp.error){
        importMsg.className='ap-chip warn';
        importMsg.innerHTML = `<b>Error:</b> ${resp.error}`;
        return;
      }
      importMsg.className='ap-chip ok';
      importMsg.innerHTML = `<b>Importación:</b> OK ${resp.rows_ok||0} | Err ${resp.rows_err||0}`;
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
