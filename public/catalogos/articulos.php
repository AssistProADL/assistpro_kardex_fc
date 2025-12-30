<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px}
.ap-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ap-card{width:250px;background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ap-card:hover{border-color:#0b5ed7}
.ap-card .h{display:flex;justify-content:space-between;align-items:center}
.ap-card .h b{font-size:14px}
.ap-card .k{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.ap-chip{font-size:11px;background:#eef2ff;color:#1e3a8a;border-radius:10px;padding:2px 8px;display:inline-flex;gap:6px;align-items:center}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.warn{background:#fff3cd;color:#7a5d00}
.ap-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
.ap-search{display:flex;align-items:center;gap:6px;border:1px solid #d0d7e2;border-radius:6px;padding:4px 8px;background:#fff}
.ap-search i{color:#0b5ed7}
.ap-search input{border:0;outline:0;font-size:12px;width:320px}
.ap-grid{border:1px solid #dcdcdc;height:520px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:10px;color:#0b5ed7}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:1080px;margin:2.5% auto;padding:15px;border-radius:10px}
.ap-form{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input,.ap-input select, .ap-input textarea{border:0;outline:0;font-size:12px;width:100%;background:transparent}
.ap-input textarea{min-height:60px;resize:vertical}
.ap-error{display:none;color:#dc3545;font-size:11px}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-boxes-stacked"></i> Catálogo de Artículos</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por cve_articulo, descripción, SAP, grupo, tipo, barras…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()"><i class="fa fa-eraser"></i> Limpiar</button>
    </div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip" onclick="refrescar()"><i class="fa fa-rotate"></i> Refrescar</button>
    <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar CSV</button>
    <button class="ap-chip ok" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar CSV</button>
    <button class="ap-chip" onclick="descargarLayout()"><i class="fa fa-file-csv"></i> Layout</button>
    <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Ver inactivos</button>

    <span class="ap-chip" id="msg" style="display:none"></span>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>cve_almac</th>
          <th>id</th>
          <th>cve_articulo</th>
          <th>des_articulo</th>
          <th>unidadMedida</th>
          <th>cve_umed</th>
          <th>imp_costo</th>
          <th>PrecioVenta</th>
          <th>tipo</th>
          <th>grupo</th>
          <th>clasificación</th>
          <th>Compuesto</th>
          <th>Caduca</th>
          <th>lotes</th>
          <th>series</th>
          <th>garantía</th>
          <th>ecom_activo</th>
          <th>ecom_cat</th>
          <th>ecom_sub</th>
          <th>destacado</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h3 style="margin:0"><i class="fa fa-cube"></i> Artículo</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>cve_almac</b>, <b>id</b>, <b>cve_articulo</b>, <b>des_articulo</b></div>
    </div>

    <input type="hidden" id="k_cve_almac">
    <input type="hidden" id="k_id">

    <div class="ap-form" style="margin-top:10px">
      <div class="ap-field">
        <div class="ap-label">cve_almac *</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <input id="cve_almac" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1">
        </div>
        <div class="ap-error" id="err_cve_almac">cve_almac obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">id *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i>
          <input id="id" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1001">
        </div>
        <div class="ap-error" id="err_id">id obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">cve_articulo *</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="cve_articulo" placeholder="ART-001"></div>
        <div class="ap-error" id="err_cve_articulo">cve_articulo obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">des_articulo *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_articulo" placeholder="Descripción corta"></div>
        <div class="ap-error" id="err_des_articulo">des_articulo obligatorio.</div>
      </div>

      <div class="ap-field" style="grid-column:span 2">
        <div class="ap-label">des_detallada</div>
        <div class="ap-input"><i class="fa fa-file-lines"></i><textarea id="des_detallada" placeholder="Descripción detallada"></textarea></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">unidadMedida</div>
        <div class="ap-input"><i class="fa fa-ruler"></i><input id="unidadMedida" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">cve_umed</div>
        <div class="ap-input"><i class="fa fa-ruler-combined"></i><input id="cve_umed" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">imp_costo</div>
        <div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="imp_costo" placeholder="12.50"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">PrecioVenta</div>
        <div class="ap-input"><i class="fa fa-cash-register"></i><input id="PrecioVenta" placeholder="18.90"></div>
      </div>

      <div class="ap-field"><div class="ap-label">tipo</div><div class="ap-input"><i class="fa fa-tag"></i><input id="tipo" placeholder="PT"></div></div>
      <div class="ap-field"><div class="ap-label">grupo</div><div class="ap-input"><i class="fa fa-layer-group"></i><input id="grupo" placeholder="GPO1"></div></div>
      <div class="ap-field"><div class="ap-label">clasificacion</div><div class="ap-input"><i class="fa fa-sitemap"></i><input id="clasificacion" placeholder="CLAS1"></div></div>

      <div class="ap-field"><div class="ap-label">Compuesto</div><div class="ap-input"><i class="fa fa-diagram-project"></i><select id="Compuesto"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select></div></div>
      <div class="ap-field"><div class="ap-label">Caduca</div><div class="ap-input"><i class="fa fa-hourglass"></i><select id="Caduca"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select></div></div>

      <div class="ap-field"><div class="ap-label">control_lotes</div><div class="ap-input"><i class="fa fa-box"></i><select id="control_lotes"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select></div></div>
      <div class="ap-field"><div class="ap-label">control_numero_series</div><div class="ap-input"><i class="fa fa-fingerprint"></i><select id="control_numero_series"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select></div></div>

      <div class="ap-field">
        <div class="ap-label">control_garantia</div>
        <div class="ap-input"><i class="fa fa-shield"></i><select id="control_garantia"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">tipo_garantia</div>
        <div class="ap-input"><i class="fa fa-calendar"></i>
          <select id="tipo_garantia">
            <option value="">(vacío)</option>
            <option value="MESES">MESES</option>
            <option value="ANIOS">ANIOS</option>
            <option value="HORAS_USO">HORAS_USO</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">valor_garantia</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="valor_garantia" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field"><div class="ap-label">Cve_SAP</div><div class="ap-input"><i class="fa fa-link"></i><input id="Cve_SAP" placeholder="SAP-001"></div></div>
      <div class="ap-field"><div class="ap-label">cve_alt</div><div class="ap-input"><i class="fa fa-code-branch"></i><input id="cve_alt" placeholder="ALT-001"></div></div>

      <div class="ap-field"><div class="ap-label">barras2</div><div class="ap-input"><i class="fa fa-barcode"></i><input id="barras2" placeholder="750..."></div></div>
      <div class="ap-field"><div class="ap-label">barras3</div><div class="ap-input"><i class="fa fa-barcode"></i><input id="barras3" placeholder="750..."></div></div>

      <div class="ap-field"><div class="ap-label">ecommerce_activo</div><div class="ap-input"><i class="fa fa-cart-shopping"></i><select id="ecommerce_activo"><option value="0">No</option><option value="1">Sí</option></select></div></div>
      <div class="ap-field"><div class="ap-label">ecommerce_categoria</div><div class="ap-input"><i class="fa fa-tags"></i><input id="ecommerce_categoria" placeholder="CAT"></div></div>
      <div class="ap-field"><div class="ap-label">ecommerce_subcategoria</div><div class="ap-input"><i class="fa fa-tag"></i><input id="ecommerce_subcategoria" placeholder="SUB"></div></div>

      <div class="ap-field"><div class="ap-label">ecommerce_destacado</div><div class="ap-input"><i class="fa fa-star"></i><select id="ecommerce_destacado"><option value="0">No</option><option value="1">Sí</option></select></div></div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i><select id="Activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
      </div>
    </div>

    <div style="text-align:right;margin-top:10px">
      <button class="primary" onclick="guardar()">Guardar</button>
      <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content">
    <h3 style="margin:0 0 8px 0"><i class="fa fa-upload"></i> Importar artículos</h3>
    <div class="ap-chip">Layout con UPSERT por <b>cve_almac + id</b>. Primero previsualiza, luego importas.</div>

    <input type="file" id="fileCsv" accept=".csv" style="margin-top:10px">

    <div style="margin:10px 0">
      <button class="ap-chip" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
      <button class="ap-chip ok" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
    </div>

    <div id="csvPreviewWrap" style="display:none;margin-top:10px">
      <div class="ap-grid" style="height:260px">
        <table>
          <thead id="csvHead"></thead>
          <tbody id="csvBody"></tbody>
        </table>
      </div>

      <div style="text-align:right;margin-top:8px">
        <button class="primary" onclick="importarCsv()"><i class="fa fa-upload"></i> Importar</button>
        <button class="ghost" onclick="cerrarModal('mdlImport')">Cancelar</button>
      </div>

      <div class="ap-chip" id="importMsg" style="margin-top:10px;display:none"></div>
    </div>
  </div>
</div>

<script>
const API = '../api/articulos_api.php';
const KPI = '../api/articulos_api_kpi.php';

let verInactivos = 0;
let cacheRows = [];
let previewRows = [];

function showMsg(txt, cls=''){ const m=document.getElementById('msg'); m.style.display='inline-flex'; m.className='ap-chip '+cls; m.innerHTML=txt; setTimeout(()=>{m.style.display='none'},3500); }

function abrirModal(id){ document.getElementById(id).style.display='block'; }
function cerrarModal(id){ document.getElementById(id).style.display='none'; }

function card(title, icon, chips){
  return `
    <div class="ap-card" onclick="refrescar()">
      <div class="h"><b>${title}</b><i class="fa ${icon}"></i></div>
      <div class="k">${chips.map(c=>`<span class="ap-chip ${c.cls||''}">${c.txt}</span>`).join('')}</div>
    </div>`;
}

async function cargarKPI(){
  const r = await fetch(KPI+'?action=kpi'); const j = await r.json();
  const cards = document.getElementById('cards');
  cards.innerHTML =
    card('Total','fa-database',[{txt:`${j.total} Registros`}]) +
    card('Activos','fa-circle-check',[{txt:`${j.activos} Operando`,cls:'ok'}]) +
    card('Inactivos','fa-trash',[{txt:`${j.inactivos} Depurados`,cls:'warn'}]);
}

function limpiar(){ document.getElementById('q').value=''; refrescar(); }
function buscar(){ refrescar(); }

function toggleInactivos(){
  verInactivos = verInactivos ? 0 : 1;
  showMsg(verInactivos ? '<i class="fa fa-eye"></i> Mostrando inactivos' : '<i class="fa fa-eye-slash"></i> Solo activos');
  refrescar();
}

async function refrescar(){
  await cargarKPI();
  const q = encodeURIComponent(document.getElementById('q').value || '');
  const url = `${API}?action=list&inactivos=${verInactivos}&q=${q}`;
  const r = await fetch(url);
  const j = await r.json();
  if(j.error){ showMsg('Error: '+j.error,'warn'); return; }
  cacheRows = j.rows || [];
  renderGrid(cacheRows);
}

function esc(s){ return (s??'').toString().replace(/[&<>"']/g,m=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;" }[m])); }

function renderGrid(rows){
  const tb = document.getElementById('tb');
  tb.innerHTML = rows.map(r=>{
    const act = (parseInt(r.Activo??1,10)===1);
    return `<tr>
      <td class="ap-actions">
        <i class="fa fa-pen" title="Editar" onclick='editar(${JSON.stringify({cve_almac:r.cve_almac,id:r.id}).replace(/'/g,"&#039;")})'></i>
        ${act
          ? `<i class="fa fa-trash" title="Desactivar" onclick='baja(${r.cve_almac},${r.id})'></i>`
          : `<i class="fa fa-rotate-left" title="Reactivar" onclick='alta(${r.cve_almac},${r.id})'></i>`
        }
      </td>
      <td>${esc(r.cve_almac)}</td>
      <td>${esc(r.id)}</td>
      <td><b>${esc(r.cve_articulo)}</b></td>
      <td>${esc(r.des_articulo)}</td>
      <td>${esc(r.unidadMedida)}</td>
      <td>${esc(r.cve_umed)}</td>
      <td>${esc(r.imp_costo)}</td>
      <td>${esc(r.PrecioVenta)}</td>
      <td>${esc(r.tipo)}</td>
      <td>${esc(r.grupo)}</td>
      <td>${esc(r.clasificacion)}</td>
      <td>${esc(r.Compuesto)}</td>
      <td>${esc(r.Caduca)}</td>
      <td>${esc(r.control_lotes)}</td>
      <td>${esc(r.control_numero_series)}</td>
      <td>${esc(r.control_garantia)}</td>
      <td>${esc(r.ecommerce_activo)}</td>
      <td>${esc(r.ecommerce_categoria)}</td>
      <td>${esc(r.ecommerce_subcategoria)}</td>
      <td>${esc(r.ecommerce_destacado)}</td>
      <td>${act ? '<span class="ap-chip ok">1</span>' : '<span class="ap-chip warn">0</span>'}</td>
    </tr>`;
  }).join('');
}

function nuevo(){
  ['k_cve_almac','k_id','cve_almac','id','cve_articulo','des_articulo','des_detallada','unidadMedida','cve_umed','imp_costo','PrecioVenta',
   'tipo','grupo','clasificacion','Compuesto','Caduca','control_lotes','control_numero_series','control_garantia','tipo_garantia','valor_garantia',
   'Cve_SAP','cve_alt','barras2','barras3','ecommerce_activo','ecommerce_categoria','ecommerce_subcategoria','ecommerce_destacado','Activo'
  ].forEach(x=>{ const el=document.getElementById(x); if(el) el.value=''; });

  document.getElementById('ecommerce_activo').value='0';
  document.getElementById('ecommerce_destacado').value='0';
  document.getElementById('Activo').value='1';

  document.getElementById('k_cve_almac').value='';
  document.getElementById('k_id').value='';
  abrirModal('mdl');
}

function setErr(id,on){
  const e=document.getElementById(id);
  if(!e) return;
  e.style.display = on ? 'block' : 'none';
}

async function editar(key){
  const r = await fetch(`${API}?action=get&cve_almac=${encodeURIComponent(key.cve_almac)}&id=${encodeURIComponent(key.id)}`);
  const j = await r.json();
  if(j.error){ showMsg('Error: '+j.error,'warn'); return; }

  document.getElementById('k_cve_almac').value = j.cve_almac;
  document.getElementById('k_id').value = j.id;

  const map = [
    'cve_almac','id','cve_articulo','des_articulo','des_detallada','unidadMedida','cve_umed','imp_costo','PrecioVenta',
    'tipo','grupo','clasificacion','Compuesto','Caduca','control_lotes','control_numero_series','control_garantia','tipo_garantia','valor_garantia',
    'Cve_SAP','cve_alt','barras2','barras3','ecommerce_activo','ecommerce_categoria','ecommerce_subcategoria','ecommerce_destacado','Activo'
  ];
  map.forEach(f=>{
    const el=document.getElementById(f);
    if(el) el.value = (j[f] ?? '');
  });

  abrirModal('mdl');
}

async function guardar(){
  const cve_almac = document.getElementById('cve_almac').value.trim();
  const id = document.getElementById('id').value.trim();
  const cve_articulo = document.getElementById('cve_articulo').value.trim();
  const des_articulo = document.getElementById('des_articulo').value.trim();

  setErr('err_cve_almac', cve_almac==='');
  setErr('err_id', id==='');
  setErr('err_cve_articulo', cve_articulo==='');
  setErr('err_des_articulo', des_articulo==='');

  if(cve_almac==='' || id==='' || cve_articulo==='' || des_articulo==='') return;

  const k_cve_almac = document.getElementById('k_cve_almac').value.trim();
  const k_id = document.getElementById('k_id').value.trim();
  const isUpdate = (k_cve_almac!=='' && k_id!=='');

  const fd = new FormData();
  fd.append('action', isUpdate ? 'update' : 'create');
  fd.append('k_cve_almac', k_cve_almac);
  fd.append('k_id', k_id);

  const fields = [
    'cve_almac','id','cve_articulo','des_articulo','des_detallada','unidadMedida','cve_umed','imp_costo','PrecioVenta',
    'tipo','grupo','clasificacion','Compuesto','Caduca','control_lotes','control_numero_series','control_garantia','tipo_garantia','valor_garantia',
    'Cve_SAP','cve_alt','barras2','barras3','ecommerce_activo','ecommerce_categoria','ecommerce_subcategoria','ecommerce_destacado','Activo'
  ];
  fields.forEach(f=>fd.append(f, (document.getElementById(f)?.value ?? '').toString().trim()));

  const r = await fetch(API, { method:'POST', body: fd });
  const j = await r.json();
  if(j.error){ showMsg('Error: '+j.error,'warn'); return; }

  cerrarModal('mdl');
  showMsg('<i class="fa fa-check"></i> Guardado','ok');
  refrescar();
}

async function baja(cve_almac,id){
  if(!confirm('¿Desactivar artículo?')) return;
  const fd = new FormData();
  fd.append('action','delete');
  fd.append('cve_almac',cve_almac);
  fd.append('id',id);
  const r=await fetch(API,{method:'POST',body:fd});
  const j=await r.json();
  if(j.error){ showMsg('Error: '+j.error,'warn'); return; }
  showMsg('<i class="fa fa-trash"></i> Desactivado','warn');
  refrescar();
}

async function alta(cve_almac,id){
  if(!confirm('¿Reactivar artículo?')) return;
  const fd = new FormData();
  fd.append('action','restore');
  fd.append('cve_almac',cve_almac);
  fd.append('id',id);
  const r=await fetch(API,{method:'POST',body:fd});
  const j=await r.json();
  if(j.error){ showMsg('Error: '+j.error,'warn'); return; }
  showMsg('<i class="fa fa-rotate-left"></i> Reactivado','ok');
  refrescar();
}

function exportarDatos(){
  const q = encodeURIComponent(document.getElementById('q').value || '');
  window.location = `${API}?action=export&inactivos=${verInactivos}&q=${q}`;
}

function descargarLayout(){
  window.location = `${API}?action=layout`;
}

function abrirImport(){
  document.getElementById('fileCsv').value='';
  document.getElementById('csvPreviewWrap').style.display='none';
  document.getElementById('importMsg').style.display='none';
  previewRows = [];
  abrirModal('mdlImport');
}

function parseCSV(text){
  const lines = text.replace(/\r/g,'').split('\n').filter(x=>x.trim()!=='');
  if(!lines.length) return {headers:[],rows:[]};
  const headers = lines[0].split(',').map(h=>h.trim());
  const rows = [];
  for(let i=1;i<lines.length;i++){
    const cols = lines[i].split(','); // CSV simple (igual que pallets_contenedores)
    const obj = {};
    headers.forEach((h,idx)=> obj[h]= (cols[idx] ?? '').trim());
    rows.push(obj);
  }
  return {headers,rows};
}

async function previsualizarCsv(){
  const f = document.getElementById('fileCsv').files[0];
  if(!f){ alert('Selecciona un CSV'); return; }
  const text = await f.text();
  const {headers,rows} = parseCSV(text);

  if(!headers.length || !rows.length){ alert('CSV vacío'); return; }

  // render preview
  document.getElementById('csvHead').innerHTML = `<tr>${headers.map(h=>`<th>${esc(h)}</th>`).join('')}</tr>`;
  document.getElementById('csvBody').innerHTML = rows.slice(0,200).map(r=>`<tr>${headers.map(h=>`<td>${esc(r[h])}</td>`).join('')}</tr>`).join('');

  previewRows = rows;
  document.getElementById('csvPreviewWrap').style.display='block';
}

async function importarCsv(){
  if(!previewRows.length){ alert('Primero previsualiza'); return; }

  const im = document.getElementById('importMsg');
  im.style.display='inline-flex';
  im.className='ap-chip';
  im.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Importando…';

  const r = await fetch(`${API}?action=import`, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({rows: previewRows})
  });

  const j = await r.json();
  if(j.error){
    im.className='ap-chip warn';
    im.innerHTML = `<i class="fa fa-triangle-exclamation"></i> ${esc(j.error)}`;
    return;
  }

  const ok = j.ok ?? 0, err = j.err ?? 0;
  im.className = err ? 'ap-chip warn' : 'ap-chip ok';
  im.innerHTML = `<i class="fa fa-check"></i> Importación OK: ${ok} | Errores: ${err}`;

  if(err && j.errores && j.errores.length){
    console.warn('Errores import:', j.errores);
  }

  refrescar();
}

refrescar();
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
