<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:700;color:#0b5ed7;margin-bottom:8px}
.ap-cards{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:10px;margin-bottom:10px}
.ap-card{border:1px solid #dcdcdc;border-radius:10px;background:#fff;padding:10px 12px;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.ap-card .h{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
.ap-card .k{margin-top:6px;display:flex;gap:6px;flex-wrap:wrap}
.ap-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ap-search{display:flex;align-items:center;gap:6px;border:1px solid #d0d7e2;border-radius:8px;padding:6px 8px;background:#fff}
.ap-search input{border:0;outline:0;font-size:12px;width:320px}
.ap-chip{border:1px solid #d0d7e2;border-radius:18px;padding:6px 10px;background:#fff;font-size:12px;cursor:pointer}
.ap-chip.ok{background:#d1e7dd;border-color:#badbcc;color:#0f5132}
.ap-chip.warn{background:#fff3cd;border-color:#ffecb5;color:#7a5d00}
.ap-grid{border:1px solid #dcdcdc;height:500px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc;white-space:nowrap}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:8px;color:#0b5ed7}
.ap-req-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc3545}
.ap-req-ok{background:#198754}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,45);z-index:9999}
.ap-modal-content{background:#fff;width:920px;margin:2.5% auto;padding:15px;border-radius:10px}
.ap-form{display:grid;grid-template-columns:1fr 2fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input,.ap-input select{border:0;outline:0;font-size:12px;width:100%;background:transparent}
.ap-error{display:none;color:#dc3545;font-size:11px}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
.ap-pager{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px}
.ap-pager .left,.ap-pager .right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ap-pager button{padding:6px 10px;border-radius:8px;border:1px solid #d0d7e2;background:#fff}
.ap-pager button:disabled{opacity:.5;cursor:not-allowed}
.ap-pager select{border:1px solid #d0d7e2;border-radius:8px;padding:6px 8px;font-size:12px}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-project-diagram"></i> Catálogo · Proyectos | CC</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-chip" id="filtroLabel"><i class="fa fa-filter"></i> Almacén: <b>Todos</b></div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave o descripción…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
      <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
      <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
    </div>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Req</th>
          <th>ID</th>
          <th>Clave Proyecto</th>
          <th>Descripción</th>
          <th>Id Almacén</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div class="ap-pager">
    <div class="left">
      <button onclick="prevPage()" id="btnPrev">◀ Prev</button>
      <button onclick="nextPage()" id="btnNext">Next ▶</button>
      <span class="ap-chip" id="lblRange">Mostrando 0–0</span>
      <span class="ap-chip">Página</span>
      <select id="selPage" onchange="goPage(this.value)"></select>
    </div>
    <div class="right">
      <span class="ap-chip">Por página</span>
      <select id="selPerPage" onchange="setPerPage(this.value)">
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
  </div>
</div>

<!-- MODAL CRUD -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h3 style="margin:0"><i class="fa fa-project-diagram"></i> Proyecto | CC</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorio: <b>Clave Proyecto</b></div>
    </div>

    <input type="hidden" id="Id">

    <div class="ap-form" style="margin-top:10px">
      <div class="ap-field">
        <div class="ap-label">Clave Proyecto *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="Cve_Proyecto" placeholder="CC-001"></div>
        <div class="ap-error" id="err_cve">Cve_Proyecto obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="Des_Proyecto" placeholder="Descripción del proyecto / centro de costo"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Almacén</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <select id="id_almacen"></select>
        </div>
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
    <h3><i class="fa fa-upload"></i> Importar Proyectos | CC</h3>
    <div class="ap-chip">Layout con UPSERT por <b>Cve_Proyecto</b>. id_almacen debe ser el <b>Id</b> del API almacenes.php</div>

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
const API = '../api/proyecto.php';
const KPI = '../api/proyecto_kpi.php';
const API_ALMACENES = '../api/almacenes.php';

let filtroAlmacen = 0;
let qLast = '';
let page = 1;
let perPage = 25;
let total = 0;
let lastRows = [];
let almacenesMap = {}; // Id -> {Id, Des}

function reqDot(r){
  const ok = !!(r.Cve_Proyecto && String(r.Cve_Proyecto).trim()!=='');
  return `<span class="ap-req-dot ${ok?'ap-req-ok':''}" title="${ok?'OK':'Falta clave'}"></span>`;
}

function loadAlmacenes(){
  // Se asume almacenes.php devuelve {rows:[...]} o [...]. Nos adaptamos.
  fetch(API_ALMACENES).then(r=>r.json()).then(resp=>{
    const rows = Array.isArray(resp) ? resp : (resp.rows || resp.data || []);
    const sel = document.getElementById('id_almacen');
    sel.innerHTML='';
    almacenesMap = {};

    // opción "Sin almacén"
    const o0=document.createElement('option'); o0.value='0'; o0.textContent='SIN ALMACÉN';
    sel.appendChild(o0);
    almacenesMap[0] = {Id:0, Des:'SIN ALMACÉN'};

    rows.forEach(a=>{
      // por instrucción: usar campo Id
      const id = Number(a.Id ?? a.id ?? a.ID ?? 0) || 0;
      const des = String(a.Des_Almacen ?? a.des_almacen ?? a.Nombre ?? a.nombre ?? a.Descripcion ?? a.descripcion ?? ('ALMACEN #'+id));
      const op=document.createElement('option');
      op.value=String(id);
      op.textContent=des;
      sel.appendChild(op);
      almacenesMap[id] = {Id:id, Des:des};
    });

    // refresca cards KPI ya con nombres si aplica
    loadCards();
  }).catch(()=>{
    // si falla, al menos dejamos el select operable
    const sel = document.getElementById('id_almacen');
    sel.innerHTML='<option value="0">SIN ALMACÉN</option>';
    almacenesMap = {0:{Id:0, Des:'SIN ALMACÉN'}};
    loadCards();
  });
}

/* Cards KPI por almacén */
function loadCards(){
  fetch(KPI+'?action=kpi').then(r=>r.json()).then(rows=>{
    let h='';
    rows.forEach(x=>{
      const idA = Number(x.IdAlmacen||0)||0;
      const nombre = (almacenesMap[idA] && almacenesMap[idA].Des) ? almacenesMap[idA].Des : (x.Almacen || ('ALMACEN #'+idA));
      h += `
      <div class="ap-card" onclick="setAlmacen(${idA})">
        <div class="h">
          <b><i class="fa fa-warehouse"></i> ${nombre}</b>
          <span class="ap-chip ok">${x.total} Total</span>
        </div>
        <div class="k">
          <span class="ap-chip">Inconsistentes: ${x.inconsistentes||0}</span>
        </div>
      </div>`;
    });
    cards.innerHTML = h || `<div class="ap-chip warn">Sin datos</div>`;
  });
}

function setAlmacen(idA){
  filtroAlmacen = Number(idA||0)||0;
  const nombre = (almacenesMap[filtroAlmacen] && almacenesMap[filtroAlmacen].Des) ? almacenesMap[filtroAlmacen].Des : (filtroAlmacen?('ALMACEN #'+filtroAlmacen):'Todos');
  filtroLabel.innerHTML = `<i class="fa fa-filter"></i> Almacén: <b>${filtroAlmacen?nombre:'Todos'}</b> ${
    filtroAlmacen?'<span class="ap-chip" style="cursor:pointer" onclick="setAlmacen(0)">Quitar</span>':''
  }`;
  page=1; cargar();
}

/* Pager */
function setPager(){
  const start = total>0 ? ((page-1)*perPage + (lastRows.length?1:0)) : ((page-1)*perPage + (lastRows.length?1:0));
  const end   = total>0 ? Math.min(page*perPage, total) : ((page-1)*perPage + lastRows.length);
  lblRange.innerText = `Mostrando ${start}-${end}` + (total>0 ? ` de ${total}` : '');

  const maxPages = total>0 ? Math.max(1, Math.ceil(total/perPage)) : Math.max(1, page + (lastRows.length===perPage ? 1 : 0));
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
  if(total>0){
    const maxPages = Math.max(1, Math.ceil(total/perPage));
    if(page<maxPages){ page++; cargar(); }
  }else{
    if(lastRows.length===perPage){ page++; cargar(); }
  }
}
function goPage(p){ page = Math.max(1, parseInt(p,10)||1); cargar(); }
function setPerPage(v){ perPage = parseInt(v,10)||25; page=1; cargar(); }

/* List */
function cargar(){
  const offset = (page-1)*perPage;
  const url = API+'?action=list'
    + '&id_almacen='+encodeURIComponent(filtroAlmacen||0)
    + '&q='+encodeURIComponent(qLast||'')
    + '&limit='+encodeURIComponent(perPage)
    + '&offset='+encodeURIComponent(offset);

  fetch(url).then(r=>r.json()).then(resp=>{
    const rows = resp.rows || [];
    total = Number(resp.total||0) || 0;
    lastRows = rows;

    let h='';
    rows.forEach(r=>{
      h += `
      <tr>
        <td class="ap-actions">
          <i class="fa fa-edit" title="Editar" onclick="editar(${r.Id})"></i>
          <i class="fa fa-trash" title="Eliminar" onclick="eliminar(${r.Id})"></i>
        </td>
        <td>${reqDot(r)}</td>
        <td>${r.Id||''}</td>
        <td>${r.Cve_Proyecto||''}</td>
        <td>${r.Des_Proyecto||''}</td>
        <td>${r.id_almacen||0}</td>
      </tr>`;
    });
    tb.innerHTML = h || `<tr><td colspan="6" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    setPager();
  });
}

function buscar(){ qLast = q.value.trim(); page=1; cargar(); }
function limpiar(){ q.value=''; qLast=''; page=1; cargar(); }

/* CRUD */
function hideErrors(){ err_cve.style.display='none'; }
function validar(){
  hideErrors();
  let ok=true;
  if(!Cve_Proyecto.value.trim()){ err_cve.style.display='block'; ok=false; }
  return ok;
}
function nuevo(){
  Id.value='';
  Cve_Proyecto.value='';
  Des_Proyecto.value='';
  id_almacen.value = String(filtroAlmacen||0);
  hideErrors();
  mdl.style.display='block';
}
function editar(id){
  fetch(API+'?action=get&id='+id).then(r=>r.json()).then(p=>{
    Id.value = p.Id || '';
    Cve_Proyecto.value = p.Cve_Proyecto || '';
    Des_Proyecto.value = p.Des_Proyecto || '';
    id_almacen.value = String(p.id_almacen ?? 0);
    hideErrors();
    mdl.style.display='block';
  });
}
function guardar(){
  if(!validar()) return;

  const fd=new FormData();
  fd.append('action', Id.value ? 'update' : 'create');
  fd.append('Id', Id.value);
  fd.append('Cve_Proyecto', Cve_Proyecto.value);
  fd.append('Des_Proyecto', Des_Proyecto.value);
  fd.append('id_almacen', id_almacen.value);

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp && resp.error){
        alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
        return;
      }
      cerrarModal('mdl');
      loadCards();
      cargar();
    });
}
function eliminar(id){
  if(!confirm('¿Eliminar proyecto? (delete físico)')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
  fetch(API,{method:'POST',body:fd}).then(()=>{ loadCards(); cargar(); });
}

function exportarDatos(){ window.open(API+'?action=export_csv&tipo=datos','_blank'); }
function descargarLayout(){ window.open(API+'?action=export_csv&tipo=layout','_blank'); }

function abrirImport(){
  fileCsv.value='';
  csvPreviewWrap.style.display='none';
  importMsg.style.display='none';
  mdlImport.style.display='block';
}
function previsualizarCsv(){
  const f=fileCsv.files[0];
  if(!f){ alert('Selecciona un CSV'); return; }
  const r=new FileReader();
  r.onload=e=>{
    const rows=e.target.result.split('\n').filter(x=>x.trim()!=='');
    csvHead.innerHTML='<tr>'+rows[0].split(',').map(h=>`<th>${h}</th>`).join('')+'</tr>';
    csvBody.innerHTML=rows.slice(1,6).map(rr=>'<tr>'+rr.split(',').map(c=>`<td>${c}</td>`).join('')+'</tr>').join('');
    csvPreviewWrap.style.display='block';
    importMsg.style.display='none';
  };
  r.readAsText(f);
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
      importMsg.style.display='block';
      if(resp.error){
        importMsg.className='ap-chip warn';
        importMsg.innerHTML = `<b>Error:</b> ${resp.error}`;
        return;
      }
      importMsg.className='ap-chip ok';
      importMsg.innerHTML = `<b>Importación:</b> OK ${resp.rows_ok||0} | Err ${resp.rows_err||0}`;
      cerrarModal('mdlImport');
      loadCards();
      cargar();
    });
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded', ()=>{
  selPerPage.value='25';
  loadAlmacenes();
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
