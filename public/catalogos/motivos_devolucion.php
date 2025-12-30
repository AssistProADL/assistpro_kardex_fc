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
  <div class="ap-title"><i class="fa fa-undo"></i> Catálogo · Motivos Devolución</div>

  <div class="ap-cards">
    <div class="ap-card">
      <div class="h">
        <b><i class="fa fa-chart-pie"></i> Resumen</b>
        <span class="ap-chip ok" id="kpiAct">0 Act</span>
      </div>
      <div class="k">
        <span class="ap-chip" id="kpiTot">Total: 0</span>
        <span class="ap-chip warn" id="kpiInac">Inac: 0</span>
        <span class="ap-chip" id="kpiBad">Bad: 0</span>
      </div>
    </div>

    <div class="ap-card" onclick="verInactivos=false;page=1;cargar();">
      <div class="h">
        <b><i class="fa fa-check-circle"></i> Activos</b>
        <span class="ap-chip ok">Ver</span>
      </div>
      <div class="k"><span class="ap-chip">Activo=1</span></div>
    </div>

    <div class="ap-card" onclick="verInactivos=true;page=1;cargar();">
      <div class="h">
        <b><i class="fa fa-eye"></i> Inactivos</b>
        <span class="ap-chip warn">Ver</span>
      </div>
      <div class="k"><span class="ap-chip">Activo=0</span></div>
    </div>

    <div class="ap-card" onclick="abrirImport();">
      <div class="h">
        <b><i class="fa fa-upload"></i> Importación</b>
        <span class="ap-chip">CSV</span>
      </div>
      <div class="k"><span class="ap-chip">UPSERT por Clave_motivo</span></div>
    </div>
  </div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por Clave o Descripción…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
      <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
      <button class="ap-chip" onclick="descargarLayout()"><i class="fa fa-file-csv"></i> Layout</button>
      <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-filter"></i> Toggle Inactivos</button>
    </div>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Req</th>
          <th>ID</th>
          <th>Clave</th>
          <th>Descripción</th>
          <th>Almacén</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <!-- Paginación -->
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
      <h3 style="margin:0"><i class="fa fa-undo"></i> Motivo Devolución</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Clave_motivo</b>, <b>MOT_DESC</b></div>
    </div>

    <input type="hidden" id="MOT_ID">

    <div class="ap-form" style="margin-top:10px">
      <div class="ap-field">
        <div class="ap-label">Clave_motivo *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="Clave_motivo" placeholder="MD-001"></div>
        <div class="ap-error" id="err_clave">Clave_motivo obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción (MOT_DESC) *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="MOT_DESC" placeholder="Producto dañado / Error surtido / Caducidad..."></div>
        <div class="ap-error" id="err_desc">MOT_DESC obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">id_almacen</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i><input id="id_almacen" placeholder="(opcional)"></div>
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
    <h3><i class="fa fa-upload"></i> Importar Motivos Devolución</h3>
    <div class="ap-chip">Layout FULL con UPSERT por <b>Clave_motivo</b>. Previsualiza antes de importar.</div>

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
const API = '../api/motivos_devolucion.php';
const KPI = '../api/motivos_devolucion_kpi.php';

let verInactivos = false;
let qLast = '';

let page = 1;
let perPage = 25;
let total = 0;
let lastRows = [];

function reqDot(r){
  const ok = !!(String(r.Clave_motivo||'').trim()!=='' && String(r.MOT_DESC||'').trim()!=='');
  return `<span class="ap-req-dot ${ok?'ap-req-ok':''}" title="${ok?'OK':'Faltan obligatorios'}"></span>`;
}

/* ===== KPI ===== */
function loadKpi(){
  fetch(KPI).then(r=>r.json()).then(k=>{
    const tot = Number(k.total||0)||0;
    const act = Number(k.activos||0)||0;
    const ina = Number(k.inactivos||0)||0;
    const bad = Number(k.inconsistentes||0)||0;
    kpiTot.innerText = 'Total: ' + tot;
    kpiAct.innerText = act + ' Act';
    kpiInac.innerText = 'Inac: ' + ina;
    kpiBad.innerText = 'Bad: ' + bad;
  });
}

/* ===== Paginación ===== */
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

/* ===== Listado ===== */
function cargar(){
  const offset = (page-1)*perPage;
  const url = API+'?action=list'
    + '&inactivos='+(verInactivos?1:0)
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
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${r.MOT_ID})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${r.MOT_ID})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${r.MOT_ID})"></i>`}
        </td>
        <td>${reqDot(r)}</td>
        <td>${r.MOT_ID||''}</td>
        <td>${r.Clave_motivo||''}</td>
        <td>${r.MOT_DESC||''}</td>
        <td>${r.id_almacen||''}</td>
        <td>${Number(r.Activo||0)===1 ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip warn">No</span>'}</td>
      </tr>`;
    });

    tb.innerHTML = h || `<tr><td colspan="7" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    setPager();
  });
}

function buscar(){ qLast = q.value.trim(); page=1; cargar(); }
function limpiar(){ q.value=''; qLast=''; page=1; cargar(); }
function toggleInactivos(){ verInactivos=!verInactivos; page=1; cargar(); }

/* ===== CRUD ===== */
function hideErrors(){
  err_clave.style.display='none';
  err_desc.style.display='none';
}
function validar(){
  hideErrors();
  let ok = true;
  if(!Clave_motivo.value.trim()){ err_clave.style.display='block'; ok=false; }
  if(!MOT_DESC.value.trim()){ err_desc.style.display='block'; ok=false; }
  return ok;
}
function nuevo(){
  document.querySelectorAll('#mdl input').forEach(i=>i.value='');
  Activo.value='1';
  MOT_ID.value='';
  hideErrors();
  mdl.style.display='block';
}
function editar(id){
  fetch(API+'?action=get&id='+id).then(r=>r.json()).then(x=>{
    for(let k in x){
      const el=document.getElementById(k);
      if(el) el.value = (x[k]===null||x[k]===undefined)?'':x[k];
    }
    hideErrors();
    mdl.style.display='block';
  });
}
function guardar(){
  if(!validar()) return;

  const fd=new FormData();
  fd.append('action', MOT_ID.value ? 'update' : 'create');
  document.querySelectorAll('#mdl input').forEach(i=>fd.append(i.id,i.value));
  document.querySelectorAll('#mdl select').forEach(s=>fd.append(s.id,s.value));

  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp && resp.error){
        alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
        return;
      }
      cerrarModal('mdl');
      loadKpi();
      cargar();
    });
}
function eliminar(id){
  if(!confirm('¿Inactivar motivo de devolución?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
  fetch(API,{method:'POST',body:fd}).then(()=>{ loadKpi(); cargar(); });
}
function recuperar(id){
  const fd=new FormData(); fd.append('action','restore'); fd.append('id',id);
  fetch(API,{method:'POST',body:fd}).then(()=>{ loadKpi(); cargar(); });
}

/* ===== CSV ===== */
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
      loadKpi();
      cargar();
    });
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded', ()=>{
  selPerPage.value='25';
  loadKpi();
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
