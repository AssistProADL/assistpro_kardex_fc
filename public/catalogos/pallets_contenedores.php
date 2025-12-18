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
.ap-chip{font-size:11px;background:#eef2ff;color:#1e3a8a;border-radius:10px;padding:2px 8px}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.warn{background:#fff3cd;color:#7a5d00}
.ap-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
.ap-search{display:flex;align-items:center;gap:6px;border:1px solid #d0d7e2;border-radius:6px;padding:4px 8px;background:#fff}
.ap-search i{color:#0b5ed7}
.ap-search input{border:0;outline:0;font-size:12px;width:280px}
.ap-grid{border:1px solid #dcdcdc;height:500px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:8px;color:#0b5ed7}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:980px;margin:2.5% auto;padding:15px;border-radius:10px}
.ap-form{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input,.ap-input select{border:0;outline:0;font-size:12px;width:100%;background:transparent}
.ap-error{display:none;color:#dc3545;font-size:11px}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-box-open"></i> Catálogo de Charolas (Contenedores)</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-chip" id="filtroLabel"><i class="fa fa-filter"></i> Almacén: <b>Todos</b></div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar clave, tipo, pedido, LP…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>
    </div>

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
          <th>Almacén</th>
          <th>Clave</th>
          <th>Tipo</th>
          <th>Pedido</th>
          <th>Permanente</th>
          <th>LP</th>
          <th>Peso</th>
          <th>PesoMax</th>
          <th>CapVol</th>
          <th>Costo</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
</div>

<!-- MODAL CHAROLA -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h3 style="margin:0"><i class="fa fa-box"></i> Charola</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>cve_almac</b>, <b>Clave_Contenedor</b></div>
    </div>

    <input type="hidden" id="IDContenedor">

    <div class="ap-form" style="margin-top:10px">
      <div class="ap-field">
        <div class="ap-label">Almacén *</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <input id="cve_almac" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="ID almacén">
        </div>
        <div class="ap-error" id="err_alm">cve_almac obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clave Contenedor *</div>
        <div class="ap-input"><i class="fa fa-qrcode"></i><input id="Clave_Contenedor" placeholder="CHAR-0001"></div>
        <div class="ap-error" id="err_clv">Clave_Contenedor obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="descripcion" placeholder="Descripción"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tipo</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="tipo" placeholder="Tipo"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Pedido</div>
        <div class="ap-input"><i class="fa fa-clipboard"></i><input id="Pedido" placeholder="Pedido"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Permanente</div>
        <div class="ap-input"><i class="fa fa-infinity"></i>
          <select id="Permanente"><option value="0">No</option><option value="1">Sí</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Sufijo</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="sufijo" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">LP asignado (CveLP)</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="CveLP" placeholder="LP00001"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">TipoGen</div>
        <div class="ap-input"><i class="fa fa-cogs"></i><input id="TipoGen" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Alto</div>
        <div class="ap-input"><i class="fa fa-ruler-vertical"></i><input id="alto" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ancho</div>
        <div class="ap-input"><i class="fa fa-ruler-horizontal"></i><input id="ancho" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Fondo</div>
        <div class="ap-input"><i class="fa fa-ruler"></i><input id="fondo" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Peso</div>
        <div class="ap-input"><i class="fa fa-weight"></i><input id="peso" placeholder="0.000"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Peso Máximo</div>
        <div class="ap-input"><i class="fa fa-weight-hanging"></i><input id="pesomax" placeholder="0.000"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Capacidad Vol</div>
        <div class="ap-input"><i class="fa fa-cube"></i><input id="capavol" placeholder="0.000"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Costo</div>
        <div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="Costo" placeholder="0.000"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo"><option value="1">Activo</option><option value="0">Inactivo</option></select>
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
    <h3>Importar charolas</h3>
    <div class="ap-chip">Layout FULL con UPSERT por <b>Clave_Contenedor</b>. Previsualiza antes de importar.</div>

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

<!-- MODAL LP DETALLE -->
<div class="ap-modal" id="mdlLP">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
      <h3 style="margin:0"><i class="fa fa-barcode"></i> Detalle LP: <span id="lblLP"></span></h3>
      <button class="ap-chip" onclick="cerrarModal('mdlLP')">Cerrar</button>
    </div>
    <div class="ap-chip" id="lpModo" style="margin-top:8px"></div>

    <div class="ap-grid" style="height:320px;margin-top:10px">
      <table>
        <thead>
          <tr><th>Artículo</th><th>Lote</th><th>Cantidad</th></tr>
        </thead>
        <tbody id="tbLP"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const API = '../api/charolas.php';
const KPI = '../api/charolas_kpi.php';

let filtroAlm=0;
let verInactivos=false;
let qLast='';

function loadCards(){
  fetch(KPI+'?action=kpi').then(r=>r.json()).then(rows=>{
    let h='';
    rows.forEach(x=>{
      h+=`
      <div class="ap-card" onclick="setAlm(${x.cve_almac})">
        <div class="h">
          <b><i class="fa fa-warehouse"></i> Almacén ${x.cve_almac}</b>
          <span class="ap-chip ok">${x.activas} Act</span>
        </div>
        <div class="k">
          <span class="ap-chip">Total: ${x.total}</span>
          <span class="ap-chip warn">Inac: ${x.inactivas}</span>
          <span class="ap-chip">Perm: ${x.permanentes}</span>
          <span class="ap-chip">Libres: ${x.libres}</span>
          <span class="ap-chip">Con LP: ${x.con_lp}</span>
        </div>
      </div>`;
    });
    cards.innerHTML = h || `<div class="ap-chip warn">Sin datos</div>`;
  });
}

function setAlm(a){
  filtroAlm = a||0;
  filtroLabel.innerHTML = `<i class="fa fa-filter"></i> Almacén: <b>${filtroAlm?filtroAlm:'Todos'}</b> ${filtroAlm?'<span class="ap-chip" style="cursor:pointer" onclick="setAlm(0)">Quitar</span>':''}`;
  cargar();
}

function cargar(){
  const url = API+'?action=list'
    + '&cve_almac='+encodeURIComponent(filtroAlm)
    + '&inactivos='+(verInactivos?1:0)
    + '&q='+encodeURIComponent(qLast||'');

  fetch(url).then(r=>r.json()).then(rows=>{
    let h='';
    rows.forEach(c=>{
      const perm = (Number(c.Permanente||0)===1) ? `<span class="ap-chip ok">Sí</span>` : `<span class="ap-chip warn">No</span>`;
      const lp = (c.CveLP && String(c.CveLP).trim()!=='')
        ? `<span class="ap-chip ok" style="cursor:pointer" onclick="verLP('${String(c.CveLP).replace(/'/g,"\\'")}')">${c.CveLP}</span>`
        : `<span class="ap-chip warn">Libre</span>`;
      h+=`
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${c.IDContenedor})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${c.IDContenedor})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${c.IDContenedor})"></i>`}
        </td>
        <td>${c.cve_almac||''}</td>
        <td><b>${c.Clave_Contenedor||''}</b></td>
        <td>${c.tipo||''}</td>
        <td>${c.Pedido||''}</td>
        <td>${perm}</td>
        <td>${lp}</td>
        <td>${c.peso||''}</td>
        <td>${c.pesomax||''}</td>
        <td>${c.capavol||''}</td>
        <td>${c.Costo||''}</td>
        <td>${Number(c.Activo||1)===1 ? '1':'0'}</td>
      </tr>`;
    });
    tb.innerHTML = h || `<tr><td colspan="12" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
  });
}

function buscar(){ qLast = q.value.trim(); cargar(); }
function limpiar(){ q.value=''; qLast=''; cargar(); }
function toggleInactivos(){ verInactivos=!verInactivos; cargar(); }

function hideErrors(){ err_alm.style.display='none'; err_clv.style.display='none'; }
function validar(){
  hideErrors();
  let ok=true;
  if(!cve_almac.value.trim()){ err_alm.style.display='block'; ok=false; }
  if(!Clave_Contenedor.value.trim()){ err_clv.style.display='block'; ok=false; }
  return ok;
}

function nuevo(){
  document.querySelectorAll('#mdl input').forEach(i=>i.value='');
  document.querySelectorAll('#mdl select').forEach(s=>{
    if(s.id==='Activo') s.value='1';
    else if(s.id==='Permanente') s.value='0';
  });
  IDContenedor.value='';
  hideErrors();
  mdl.style.display='block';
}

function editar(id){
  fetch(API+'?action=get&IDContenedor='+id).then(r=>r.json()).then(c=>{
    for(let k in c){
      const el=document.getElementById(k);
      if(el) el.value = (c[k]===null||c[k]===undefined)?'':c[k];
    }
    hideErrors();
    mdl.style.display='block';
  });
}

function guardar(){
  if(!validar()) return;

  const fd=new FormData();
  fd.append('action', IDContenedor.value ? 'update' : 'create');
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
      loadCards();
      cargar();
    });
}

function eliminar(id){
  if(!confirm('¿Inactivar charola?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('IDContenedor',id);
  fetch(API,{method:'POST',body:fd}).then(()=>{ loadCards(); cargar(); });
}
function recuperar(id){
  const fd=new FormData(); fd.append('action','restore'); fd.append('IDContenedor',id);
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
    csvBody.innerHTML=rows.slice(1,6).map(r=>'<tr>'+r.split(',').map(c=>`<td>${c}</td>`).join('')+'</tr>').join('');
    csvPreviewWrap.style.display='block';
    importMsg.style.display='none';
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

function verLP(lp){
  lblLP.textContent = lp;
  tbLP.innerHTML = `<tr><td colspan="3" style="text-align:center;color:#6c757d;padding:15px">Cargando…</td></tr>`;
  lpModo.textContent = 'Detectando fuente…';
  mdlLP.style.display='block';

  fetch(API+'?action=lp_detalle&CveLP='+encodeURIComponent(lp))
    .then(r=>r.json())
    .then(resp=>{
      lpModo.innerHTML = `<i class="fa fa-database"></i> Fuente: <b>${resp.modo||'N/A'}</b>`;
      const items = resp.items || [];
      tbLP.innerHTML = items.length
        ? items.map(it=>`<tr><td>${it.cve_articulo||''}</td><td>${it.cve_lote||''}</td><td>${it.cantidad||0}</td></tr>`).join('')
        : `<tr><td colspan="3" style="text-align:center;color:#6c757d;padding:15px">Sin detalle o tablas LP no configuradas.</td></tr>`;
    });
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded', ()=>{
  loadCards();
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
