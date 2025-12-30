<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
/* Replica exacta estilo pallets_contenedores.php */
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
.ap-chip.warn{background:#fff3cd;color:#664d03}
.ap-chip.bad{background:#f8d7da;color:#842029}

.ap-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px}
.ap-btn{border:1px solid #d0d7e2;background:#fff;border-radius:8px;padding:6px 10px;font-size:12px;cursor:pointer}
.ap-btn.primary{background:#0b5ed7;color:#fff;border-color:#0b5ed7}
.ap-btn.danger{background:#dc3545;color:#fff;border-color:#dc3545}
.ap-btn.ok{background:#198754;color:#fff;border-color:#198754}
.ap-btn:disabled{opacity:.6;cursor:not-allowed}
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
.ap-note{font-size:11px;color:#6c757d}

.ap-drop{border:1px dashed #9aa7b7;border-radius:10px;padding:10px;background:#f8fafc}
.ap-drop small{color:#6c757d}
.ap-pre{max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-building"></i> Catálogo de Empresas</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <button class="ap-btn primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-btn" onclick="toggleInactivos()" id="btnInact"><i class="fa fa-eye"></i> Ver inactivos</button>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por clave, nombre, RFC, distrito..." onkeyup="buscar(event)">
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
          <th>cve_cia</th>
          <th>clave_empresa</th>
          <th>des_cia</th>
          <th>distrito</th>
          <th>des_rfc</th>
          <th>teléfono</th>
          <th>email</th>
          <th>activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div class="ap-note">Estándar: grilla con scroll H/V, acciones a la izquierda, validaciones fuertes, import/export con layout controlado.</div>
</div>

<!-- Modal CRUD -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div class="ap-modal-head">
      <div class="ap-modal-title" id="mdlTitle"><i class="fa fa-building"></i> Empresa</div>
      <div class="ap-x" onclick="cerrarModal('mdl')"><i class="fa fa-times"></i></div>
    </div>

    <div class="ap-err" id="mdlErr"></div>

    <input type="hidden" id="cve_cia">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Clave empresa *</div>
        <div class="ap-input"><i class="fa fa-key"></i><input id="clave_empresa" maxlength="255"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Nombre / Razón social *</div>
        <div class="ap-input"><i class="fa fa-font"></i><input id="des_cia" maxlength="100"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Distrito</div>
        <div class="ap-input"><i class="fa fa-map"></i><input id="distrito" maxlength="255"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">RFC</div>
        <div class="ap-input"><i class="fa fa-id-card"></i><input id="des_rfc" maxlength="20"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dirección</div>
        <div class="ap-input"><i class="fa fa-location-dot"></i><input id="des_direcc" maxlength="150"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">CP</div>
        <div class="ap-input"><i class="fa fa-envelopes-bulk"></i><input id="des_cp" maxlength="10"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Teléfono</div>
        <div class="ap-input"><i class="fa fa-phone"></i><input id="des_telef" maxlength="50"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Contacto</div>
        <div class="ap-input"><i class="fa fa-user"></i><input id="des_contacto" maxlength="100"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Email</div>
        <div class="ap-input"><i class="fa fa-envelope"></i><input id="des_email" maxlength="100"></div>
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

      <div class="ap-field" style="grid-column: span 2;">
        <div class="ap-label">Observaciones</div>
        <div class="ap-input"><i class="fa fa-note-sticky"></i><input id="des_observ" maxlength="255"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Es transportista</div>
        <div class="ap-input"><i class="fa fa-truck"></i>
          <select id="es_transportista">
            <option value="">(NULL)</option>
            <option value="1">1</option>
            <option value="0">0</option>
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
      <div class="ap-modal-title"><i class="fa fa-file-import"></i> Importar Empresas (CSV)</div>
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
      <small>Tip: Primero exporta, ajusta y reimporta.</small>
      <div style="margin-top:8px">
        <input type="file" id="csvFile" accept=".csv,text/csv">
      </div>
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
const API = '../api/empresas.php';
const KPI = '../api/empresas_kpi.php';

let verInactivos = false;
let qLast = '';

function showError(boxId, msg){
  const el = document.getElementById(boxId);
  if(!msg){ el.style.display='none'; el.innerHTML=''; return; }
  el.style.display='block';
  el.innerHTML = msg;
}
function hideErrors(){ showError('errBox',''); showError('mdlErr',''); showError('impErr',''); }

function loadCards(){
  fetch(KPI+'?action=kpi').then(r=>r.json()).then(k=>{
    const t = Number(k.total||0), a = Number(k.activos||0), i = Number(k.inactivos||0);
    document.getElementById('cards').innerHTML = `
      <div class="ap-card" onclick="setFiltroActivos('ALL')">
        <div class="h"><b>Total</b><i class="fa fa-chart-pie"></i></div>
        <div class="k"><span class="ap-chip ok">${t}</span><span class="ap-chip">Registros</span></div>
      </div>
      <div class="ap-card" onclick="setFiltroActivos('1')">
        <div class="h"><b>Activos</b><i class="fa fa-toggle-on"></i></div>
        <div class="k"><span class="ap-chip ok">${a}</span><span class="ap-chip">Operando</span></div>
      </div>
      <div class="ap-card" onclick="setFiltroActivos('0')">
        <div class="h"><b>Inactivos</b><i class="fa fa-toggle-off"></i></div>
        <div class="k"><span class="ap-chip warn">${i}</span><span class="ap-chip">Depurados</span></div>
      </div>
    `;
  });
}
let filtroActivoCard = 'ALL';
function setFiltroActivos(v){ filtroActivoCard=v; cargar(); }

function toggleInactivos(){
  verInactivos = !verInactivos;
  document.getElementById('btnInact').innerHTML = verInactivos
    ? '<i class="fa fa-eye-slash"></i> Ocultar inactivos'
    : '<i class="fa fa-eye"></i> Ver inactivos';
  cargar();
}

function cargar(){
  hideErrors();
  const url = API+'?action=list'
    + '&inactivos='+(verInactivos?1:0)
    + '&activoCard='+encodeURIComponent(filtroActivoCard)
    + '&q='+encodeURIComponent(qLast||'');

  fetch(url).then(r=>r.json()).then(resp=>{
    if(resp.error){ showError('errBox', resp.error); return; }
    const rows = resp.rows || [];
    let h='';
    rows.forEach(c=>{
      const activo = (Number(c.Activo||0)===1)
        ? `<span class="ap-chip ok">Activo</span>`
        : `<span class="ap-chip warn">Inactivo</span>`;
      h+=`
        <tr>
          <td class="ap-actions">
            ${ (Number(c.Activo||0)===1 && !verInactivos)
              ? `<i class="fa fa-edit" title="Editar" onclick="editar(${c.cve_cia})"></i>
                 <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${c.cve_cia})"></i>`
              : `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${c.cve_cia})"></i>`}
          </td>
          <td>${c.cve_cia||''}</td>
          <td><b>${c.clave_empresa||''}</b></td>
          <td>${c.des_cia||''}</td>
          <td>${c.distrito||''}</td>
          <td>${c.des_rfc||''}</td>
          <td>${c.des_telef||''}</td>
          <td>${c.des_email||''}</td>
          <td>${activo}</td>
        </tr>`;
    });
    document.getElementById('tb').innerHTML = h || '<tr><td colspan="9" style="padding:10px;color:#6c757d">Sin registros</td></tr>';
  }).catch(e=>showError('errBox','Error: '+e.message));
}

function buscar(ev){
  if(ev && ev.key && ev.key!=='Enter') return;
  qLast = (document.getElementById('q').value||'').trim();
  cargar();
}

function validar(){
  hideErrors();
  const detalles=[];
  const clave = (clave_empresa.value||'').trim();
  const nombre = (des_cia.value||'').trim();

  if(!clave) detalles.push('Clave empresa es obligatoria.');
  if(!nombre) detalles.push('Nombre / Razón social es obligatoria.');

  // Normalización ejecutiva: sin espacios extremos y mayúsculas para clave
  clave_empresa.value = clave.toUpperCase();

  // Email básico si viene
  const em = (des_email.value||'').trim();
  if(em && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) detalles.push('Email no tiene formato válido.');

  if(detalles.length){
    showError('mdlErr', 'Validación:<br>- '+detalles.join('<br>- '));
    return false;
  }
  return true;
}

function nuevo(){
  hideErrors();
  mdlTitle.innerHTML = '<i class="fa fa-building"></i> Nueva Empresa';
  cve_cia.value='';
  clave_empresa.value='';
  des_cia.value='';
  distrito.value='';
  des_rfc.value='';
  des_direcc.value='';
  des_cp.value='';
  des_telef.value='';
  des_contacto.value='';
  des_email.value='';
  des_observ.value='';
  es_transportista.value='';
  Activo.value='1';
  abrirModal('mdl');
}

function editar(id){
  hideErrors();
  fetch(API+'?action=get&cve_cia='+encodeURIComponent(id)).then(r=>r.json()).then(c=>{
    if(c.error){ showError('errBox',c.error); return; }
    mdlTitle.innerHTML = '<i class="fa fa-building"></i> Editar Empresa';
    cve_cia.value = c.cve_cia||'';
    clave_empresa.value = c.clave_empresa||'';
    des_cia.value = c.des_cia||'';
    distrito.value = c.distrito||'';
    des_rfc.value = c.des_rfc||'';
    des_direcc.value = c.des_direcc||'';
    des_cp.value = c.des_cp||'';
    des_telef.value = c.des_telef||'';
    des_contacto.value = c.des_contacto||'';
    des_email.value = c.des_email||'';
    des_observ.value = c.des_observ||'';
    es_transportista.value = (c.es_transportista===null?'':c.es_transportista);
    Activo.value = (String(c.Activo||'1'));
    abrirModal('mdl');
  });
}

function guardar(){
  if(!validar()) return;

  const fd = new FormData();
  fd.append('action', cve_cia.value ? 'update' : 'create');
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
  if(!confirm('¿Inactivar empresa?')) return;
  const fd=new FormData();
  fd.append('action','delete');
  fd.append('cve_cia',id);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){ alert(resp.error); return; }
    loadCards(); cargar();
  });
}

function recuperar(id){
  if(!confirm('¿Recuperar empresa?')) return;
  const fd=new FormData();
  fd.append('action','restore');
  fd.append('cve_cia',id);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){ alert(resp.error); return; }
    loadCards(); cargar();
  });
}

function exportarDatos(){
  const url = API+'?action=export'
    + '&inactivos='+(verInactivos?1:0)
    + '&activoCard='+encodeURIComponent(filtroActivoCard)
    + '&q='+encodeURIComponent(qLast||'');
  window.open(url,'_blank');
}

function descargarLayout(){
  window.open(API+'?action=layout','_blank');
}

function abrirImport(){
  hideErrors();
  document.getElementById('csvFile').value='';
  document.getElementById('impPreview').innerText='(sin datos)';
  abrirModal('mdlImp');
}

function previsualizarCsv(){
  hideErrors();
  const f = document.getElementById('csvFile').files[0];
  if(!f){ showError('impErr','Selecciona un CSV.'); return; }
  const fd=new FormData();
  fd.append('action','import_preview');
  fd.append('csv',f);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){ showError('impErr',resp.error + (resp.detalles?'<br>- '+resp.detalles.join('<br>- '):'')); return; }
    document.getElementById('impPreview').innerText = resp.preview_text || '(sin datos)';
  });
}

function importarCsv(){
  hideErrors();
  const f = document.getElementById('csvFile').files[0];
  if(!f){ showError('impErr','Selecciona un CSV.'); return; }
  if(!confirm('¿Importar CSV? Se aplicará UPSERT por cve_cia (o clave_empresa si viene cve_cia vacío).')) return;

  const fd=new FormData();
  fd.append('action','import');
  fd.append('csv',f);
  fetch(API,{method:'POST',body:fd}).then(r=>r.json()).then(resp=>{
    if(resp.error){
      showError('impErr',resp.error + (resp.detalles?'<br>- '+resp.detalles.join('<br>- '):''));
      return;
    }
    cerrarModal('mdlImp');
    loadCards(); cargar();
    alert('Importación OK. Insertados: '+(resp.inserted||0)+' / Actualizados: '+(resp.updated||0)+' / Errores: '+(resp.errors||0));
  });
}

function abrirModal(id){ document.getElementById(id).style.display='block'; }
function cerrarModal(id){ document.getElementById(id).style.display='none'; }

loadCards();
cargar();
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
