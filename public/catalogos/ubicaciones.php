<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7}
.ap-toolbar{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.ap-toolbar button{padding:4px 10px;font-size:12px}
.ap-search{display:flex;align-items:center;gap:6px;border:1px solid #d0d7e2;border-radius:6px;padding:4px 8px;background:#fff}
.ap-search i{color:#0b5ed7}
.ap-search input{border:0;outline:0;font-size:12px;width:280px}
.ap-grid{border:1px solid #dcdcdc;height:520px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:8px;color:#0b5ed7}
.ap-req-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc3545;margin-right:6px}
.ap-req-ok{background:#198754}
.ap-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;background:#eef2ff;color:#1e3a8a;margin-right:4px}
.ap-badge.ok{background:#d1e7dd;color:#0f5132}
.ap-badge.warn{background:#fff3cd;color:#7a5d00}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:980px;margin:2% auto;padding:15px;border-radius:8px}
.ap-modal-content h3{margin:0 0 10px 0}
.ap-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input,.ap-input select{border:0;outline:0;font-size:12px;width:100%;background:transparent}
.ap-help{font-size:11px;color:#6c757d}
.ap-error{color:#dc3545;font-size:11px;display:none}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
.ap-row{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
</style>

<div class="ap-container">
  <div class="ap-header">
    <div class="ap-title"><i class="fa fa-map-marked-alt"></i> Catálogo de Ubicaciones (BL)</div>

    <div class="ap-toolbar">
      <div class="ap-search" title="Buscar por BL (CodigoCSD), claverp, sección, ubicación, pasillo/rack/nivel, tecnología, tipo, ABC, almacén">
        <i class="fa fa-search"></i>
        <input id="q" placeholder="Buscar ubicación…" onkeydown="if(event.key==='Enter')buscar()">
        <button class="ghost" onclick="limpiarBusqueda()" title="Limpiar"><i class="fa fa-times"></i></button>
      </div>

      <button onclick="nuevo()">➕ Agregar</button>
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
          <th>Almacén</th>
          <th>BL (CodigoCSD)</th>
          <th>Sección</th>
          <th>Ubicación</th>
          <th>Pasillo</th>
          <th>Rack</th>
          <th>Nivel</th>
          <th>Status</th>
          <th>Picking</th>
          <th>Tipo</th>
          <th>Flags</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
</div>

<!-- MODAL -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div class="ap-row">
      <h3>Ubicación</h3>
      <div class="ap-help"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>cve_almac</b> y <b>CodigoCSD (BL)</b></div>
    </div>

    <input type="hidden" id="idy_ubica">

    <div class="ap-form-grid">
      <div class="ap-field">
        <div class="ap-label">Almacén <span style="color:#dc3545">*</span></div>
        <div class="ap-input"><i class="fa fa-warehouse"></i><input id="cve_almac" placeholder="ID almacén" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
        <div class="ap-error" id="err_alm">cve_almac obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">BL / CodigoCSD <span style="color:#dc3545">*</span></div>
        <div class="ap-input"><i class="fa fa-qrcode"></i><input id="CodigoCSD" placeholder="Bin Location (BL)"></div>
        <div class="ap-error" id="err_bl">CodigoCSD obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clave RP</div>
        <div class="ap-input"><i class="fa fa-link"></i><input id="claverp" placeholder="claverp"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Sección</div>
        <div class="ap-input"><i class="fa fa-layer-group"></i><input id="Seccion" placeholder="Sección"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ubicación</div>
        <div class="ap-input"><i class="fa fa-map-pin"></i><input id="Ubicacion" placeholder="Ubicación"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Pasillo</div>
        <div class="ap-input"><i class="fa fa-grip-lines-vertical"></i><input id="cve_pasillo" placeholder="Pasillo"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Rack</div>
        <div class="ap-input"><i class="fa fa-th"></i><input id="cve_rack" placeholder="Rack"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Nivel</div>
        <div class="ap-input"><i class="fa fa-sort-numeric-up"></i><input id="cve_nivel" placeholder="Nivel"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Status</div>
        <div class="ap-input"><i class="fa fa-traffic-light"></i><input id="Status" placeholder="Ej. A/I/B (1 char)"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Picking</div>
        <div class="ap-input"><i class="fa fa-hand-pointer"></i><input id="picking" placeholder="S/N (1 char)"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tecnología</div>
        <div class="ap-input"><i class="fa fa-microchip"></i><input id="TECNOLOGIA" placeholder="RFID/QR/etc"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tipo</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="Tipo" placeholder="1 char"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Acomodo Mixto</div>
        <div class="ap-input"><i class="fa fa-random"></i>
          <select id="AcomodoMixto"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Área Producción</div>
        <div class="ap-input"><i class="fa fa-industry"></i>
          <select id="AreaProduccion"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Área Stagging</div>
        <div class="ap-input"><i class="fa fa-dolly"></i>
          <select id="AreaStagging"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">CrossDocking</div>
        <div class="ap-input"><i class="fa fa-exchange-alt"></i>
          <select id="Ubicacion_CrossDocking"><option value="N">N</option><option value="S">S</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Staging Pedidos</div>
        <div class="ap-input"><i class="fa fa-clipboard-list"></i>
          <select id="Staging_Pedidos"><option value="N">N</option><option value="S">S</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">PTL</div>
        <div class="ap-input"><i class="fa fa-lightbulb"></i>
          <select id="Ptl"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Maneja Cajas</div>
        <div class="ap-input"><i class="fa fa-box"></i>
          <select id="Maneja_Cajas"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Maneja Piezas</div>
        <div class="ap-input"><i class="fa fa-cubes"></i>
          <select id="Maneja_Piezas"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Reabasto</div>
        <div class="ap-input"><i class="fa fa-sync"></i>
          <select id="Reabasto"><option value="">(vacío)</option><option value="S">S</option><option value="N">N</option></select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Orden Secuencia</div>
        <div class="ap-input"><i class="fa fa-sort-amount-up"></i><input id="orden_secuencia" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dim. Ancho</div>
        <div class="ap-input"><i class="fa fa-ruler-horizontal"></i><input id="num_ancho" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dim. Largo</div>
        <div class="ap-input"><i class="fa fa-ruler"></i><input id="num_largo" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dim. Alto</div>
        <div class="ap-input"><i class="fa fa-ruler-vertical"></i><input id="num_alto" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Volumen Disp.</div>
        <div class="ap-input"><i class="fa fa-cube"></i><input id="num_volumenDisp" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Peso Máximo</div>
        <div class="ap-input"><i class="fa fa-weight-hanging"></i><input id="PesoMaximo" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Peso Ocupado</div>
        <div class="ap-input"><i class="fa fa-weight"></i><input id="PesoOcupado" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Máximo</div>
        <div class="ap-input"><i class="fa fa-arrow-up"></i><input id="Maximo" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Mínimo</div>
        <div class="ap-input"><i class="fa fa-arrow-down"></i><input id="Minimo" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clasif ABC</div>
        <div class="ap-input"><i class="fa fa-chart-line"></i><input id="clasif_abc" placeholder="A/B/C"></div>
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

<!-- IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content">
    <h3>Importar ubicaciones</h3>
    <div class="ap-help">Layout FULL con UPSERT por BL (CodigoCSD). Previsualiza antes de importar.</div>

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
const API = '../api/ubicaciones.php';
let verInactivos=false;
let qLast='';

function reqIndicator(u){
  const ok = (String(u.cve_almac||'').trim()!=='') && (String(u.CodigoCSD||'').trim()!=='');
  return `<span class="ap-req-dot ${ok?'ap-req-ok':''}" title="${ok?'OK':'Faltan obligatorios'}"></span>`;
}
function flags(u){
  let b='';
  if((u.AcomodoMixto||'')==='S') b+=`<span class="ap-badge ok"><i class="fa fa-random"></i> Mixto</span>`;
  if((u.AreaProduccion||'')==='S') b+=`<span class="ap-badge"><i class="fa fa-industry"></i> Prod</span>`;
  if((u.AreaStagging||'')==='S') b+=`<span class="ap-badge"><i class="fa fa-dolly"></i> Stg</span>`;
  if((u.Ubicacion_CrossDocking||'')==='S') b+=`<span class="ap-badge"><i class="fa fa-exchange-alt"></i> XDock</span>`;
  if((u.Staging_Pedidos||'')==='S') b+=`<span class="ap-badge"><i class="fa fa-clipboard-list"></i> Ped</span>`;
  if((u.Ptl||'')==='S') b+=`<span class="ap-badge"><i class="fa fa-lightbulb"></i> PTL</span>`;
  return b || `<span class="ap-badge warn">N/A</span>`;
}

function cargar(){
  const url = API+'?action=list&inactivos='+(verInactivos?1:0)+'&q='+encodeURIComponent(qLast||'');
  fetch(url).then(r=>r.json()).then(rows=>{
    let h='';
    rows.forEach(u=>{
      h+=`
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${u.idy_ubica})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${u.idy_ubica})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${u.idy_ubica})"></i>`}
        </td>
        <td>${reqIndicator(u)}</td>
        <td>${u.cve_almac||''}</td>
        <td><b>${u.CodigoCSD||''}</b></td>
        <td>${u.Seccion||''}</td>
        <td>${u.Ubicacion||''}</td>
        <td>${u.cve_pasillo||''}</td>
        <td>${u.cve_rack||''}</td>
        <td>${u.cve_nivel||''}</td>
        <td>${u.Status||''}</td>
        <td>${u.picking||''}</td>
        <td>${u.Tipo||''}</td>
        <td>${flags(u)}</td>
      </tr>`;
    });
    tb.innerHTML = h || `<tr><td colspan="13" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
  });
}

function buscar(){ qLast = q.value.trim(); cargar(); }
function limpiarBusqueda(){ q.value=''; qLast=''; cargar(); }

function hideErrors(){ err_alm.style.display='none'; err_bl.style.display='none'; }
function validar(){
  hideErrors();
  let ok=true;
  if(!cve_almac.value.trim()){ err_alm.style.display='block'; ok=false; }
  if(!CodigoCSD.value.trim()){ err_bl.style.display='block'; ok=false; }
  return ok;
}

function nuevo(){
  document.querySelectorAll('#mdl input').forEach(i=>i.value='');
  document.querySelectorAll('#mdl select').forEach(s=>{
    if(s.id==='Activo') s.value='1';
    else if(s.id==='Ubicacion_CrossDocking' || s.id==='Staging_Pedidos') s.value='N';
    else s.value='';
  });
  hideErrors();
  mdl.style.display='block';
}

function editar(id){
  fetch(API+'?action=get&idy_ubica='+id).then(r=>r.json()).then(u=>{
    for(let k in u){
      const el=document.getElementById(k);
      if(el) el.value = (u[k]===null||u[k]===undefined)?'':u[k];
    }
    hideErrors();
    mdl.style.display='block';
  });
}

function guardar(){
  if(!validar()) return;

  const fd=new FormData();
  fd.append('action', idy_ubica.value ? 'update' : 'create');
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
      cargar();
    });
}

function eliminar(id){
  if(!confirm('¿Inactivar ubicación?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('idy_ubica',id);
  fetch(API,{method:'POST',body:fd}).then(()=>cargar());
}
function recuperar(id){
  const fd=new FormData(); fd.append('action','restore'); fd.append('idy_ubica',id);
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
