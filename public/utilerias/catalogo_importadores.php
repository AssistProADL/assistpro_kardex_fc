<?php
require_once __DIR__ . '/../bi/_menu_global.php';

$USR = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? ($_SESSION['usuario'] ?? 'SISTEMA'));
?>
<style>
  .ap-card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);border:1px solid #e1e5eb;margin-bottom:15px}
  .ap-card-header{background:#0F5AAD;color:#fff;padding:10px 14px;font-size:13px;font-weight:700;border-radius:10px 10px 0 0}
  .ap-card-body{padding:12px;font-size:11px}
  .ap-form-control{font-size:11px;height:30px;padding:2px 8px}
  .ap-label{font-size:11px;font-weight:700;margin-bottom:3px}
  .table-sm th,.table-sm td{font-size:10px;padding:5px 7px;white-space:nowrap;vertical-align:middle}
  .scroll-table{max-height:520px;overflow-x:auto;overflow-y:auto}

  .ap-badge{display:inline-block;font-size:10px;padding:2px 8px;border-radius:10px;border:1px solid #d0d7e2;background:#f7f9fc}
  .ap-badge.ok{background:#d1e7dd;border-color:#badbcc;color:#0f5132}
  .ap-badge.warn{background:#fff3cd;border-color:#ffecb5;color:#664d03}
  .ap-badge.err{background:#f8d7da;border-color:#f5c2c7;color:#842029}

  .ap-help{color:#6c757d;font-size:11px}
  .ap-actions .btn{font-size:10px;padding:2px 8px}
  .ap-title-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
  .ap-title-row h4{margin:0}
  .ap-muted{color:#6c757d}

  /* ✅ Tabs/chips */
  .ap-chips{display:flex;flex-wrap:wrap;gap:8px}
  .ap-chip{
    cursor:pointer; user-select:none;
    border:1px solid #d0d7e2; background:#f7f9fc;
    border-radius:999px; padding:6px 10px; font-size:11px;
    display:inline-flex; gap:8px; align-items:center;
  }
  .ap-chip.active{
    background:#0F5AAD; border-color:#0F5AAD; color:#fff;
  }
  .ap-chip .cnt{
    font-size:10px; padding:2px 8px; border-radius:999px;
    background:rgba(0,0,0,0.06);
  }
  .ap-chip.active .cnt{ background:rgba(255,255,255,0.25); }

  .ap-mini{font-size:10px;color:#6c757d}
</style>

<div class="container-fluid">

  <div class="ap-title-row mb-3">
    <div>
      <h4><i class="fa fa-cogs"></i> Utilerías | Catálogo de Importadores</h4>
      <div class="ap-help">Gobernanza de plataforma: tipos, reglas, estado, y rutas de API. Minimiza confusión y elimina hardcode.</div>
    </div>
    <div class="d-flex" style="gap:8px;">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPingMasivo">
        <i class="fa fa-stethoscope"></i> Ping APIs
      </button>
      <button type="button" class="btn btn-sm btn-primary" id="btnNuevo">
        <i class="fa fa-plus"></i> Nuevo importador
      </button>
    </div>
  </div>

  <!-- ✅ Chips por tipo -->
  <div class="ap-card">
    <div class="ap-card-header">Segmentación rápida</div>
    <div class="ap-card-body">
      <div class="ap-chips" id="chipsTipo">
        <!-- render dinámico -->
      </div>
      <div class="ap-mini mt-2">
        Click en un chip para filtrar por tipo. “TODOS” restablece el tablero.
      </div>
    </div>
  </div>

  <div class="ap-card">
    <div class="ap-card-header">Filtros</div>
    <div class="ap-card-body">
      <div class="row">
        <div class="col-md-3">
          <label class="ap-label">Tipo</label>
          <select id="fTipo" class="form-control ap-form-control">
            <option value="TODOS">TODOS</option>
            <option value="INGRESO">INGRESO</option>
            <option value="EGRESO">EGRESO</option>
            <option value="TRASLADOS">TRASLADOS</option>
            <option value="PEDIDOS">PEDIDOS</option>
            <option value="OT">OT</option>
            <option value="INVENTARIOS">INVENTARIOS</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="ap-label">Activo</label>
          <select id="fActivo" class="form-control ap-form-control">
            <option value="TODOS">TODOS</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        <div class="col-md-5">
          <label class="ap-label">Buscar</label>
          <input id="fSearch" class="form-control ap-form-control" placeholder="Clave o descripción (ej. TRALM / OCN / ORDEN...)">
        </div>

        <div class="col-md-2 d-flex align-items-end justify-content-end">
          <button class="btn btn-sm btn-outline-secondary mr-2" id="btnLimpiar">
            <i class="fa fa-eraser"></i> Limpiar
          </button>
          <button class="btn btn-sm btn-outline-primary" id="btnBuscar">
            <i class="fa fa-search"></i> Buscar
          </button>
        </div>
      </div>

      <div class="mt-2 ap-muted">
        <i class="fa fa-info-circle"></i>
        Tip: Si hay muchos importadores, usa chips por <b>TIPO</b> para evitar confusión.
      </div>
    </div>
  </div>

  <div class="ap-card">
    <div class="ap-card-header">Importadores</div>
    <div class="ap-card-body">
      <div class="row mb-2">
        <div class="col-md-7">
          <div id="kpi" style="font-size:11px;">Total: 0 | Activos: 0 | Inactivos: 0</div>
          <div class="ap-mini" id="kpi2"></div>
        </div>
        <div class="col-md-5 d-flex justify-content-end" style="gap:8px;">
          <button class="btn btn-sm btn-outline-secondary" id="btnRefrescar">
            <i class="fa fa-refresh"></i> Refrescar
          </button>
        </div>
      </div>

      <div class="scroll-table">
        <table class="table table-sm table-striped table-bordered" id="tabla">
          <thead>
            <tr>
              <th>Acciones</th>
              <th>Clave</th>
              <th>Descripción</th>
              <th>Tipo</th>
              <th>Activo</th>
              <th>Rollback</th>
              <th>Kardex</th>
              <th>Layout</th>
              <th>BL Origen</th>
              <th>Lote</th>
              <th>Serie</th>
              <th>Retención</th>
              <th>Ruta API</th>
              <th>Versión</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- Modal Alta/Edición -->
<div class="modal fade" id="modalImp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:12px; overflow:hidden;">
      <div class="modal-header" style="background:#0F5AAD;color:#fff;">
        <h6 class="modal-title" id="modalTitle"><i class="fa fa-pencil"></i> Importador</h6>
        <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="color:#fff;opacity:1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body" style="font-size:11px;">
        <input type="hidden" id="m_id_importador" value="0">

        <div class="row">
          <div class="col-md-3">
            <label class="ap-label">Clave</label>
            <input id="m_clave" class="form-control ap-form-control" placeholder="OCN, OCI, TRALM..." maxlength="30">
            <small class="ap-help">Única. No se edita después.</small>
          </div>

          <div class="col-md-5">
            <label class="ap-label">Descripción</label>
            <input id="m_descripcion" class="form-control ap-form-control" placeholder="Descripción operacional del importador" maxlength="120">
          </div>

          <div class="col-md-4">
            <label class="ap-label">Tipo</label>
            <select id="m_tipo" class="form-control ap-form-control">
              <option value="">[Seleccione]</option>
              <option value="INGRESO">INGRESO</option>
              <option value="EGRESO">EGRESO</option>
              <option value="TRASLADOS">TRASLADOS</option>
              <option value="PEDIDOS">PEDIDOS</option>
              <option value="OT">OT</option>
              <option value="INVENTARIOS">INVENTARIOS</option>
            </select>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-3">
            <label class="ap-label">Activo</label>
            <select id="m_activo" class="form-control ap-form-control">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Permite Rollback</label>
            <select id="m_perm_rollback" class="form-control ap-form-control">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Impacta Kardex</label>
            <select id="m_kardex" class="form-control ap-form-control">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Requiere Layout</label>
            <select id="m_layout" class="form-control ap-form-control">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>
        </div>

        <div class="row mt-2">
          <div class="col-md-3">
            <label class="ap-label">Requiere BL Origen</label>
            <select id="m_bl" class="form-control ap-form-control">
              <option value="0">NO</option>
              <option value="1">SI</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Lote obligatorio si aplica</label>
            <select id="m_lote" class="form-control ap-form-control">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Serie obligatoria si aplica</label>
            <select id="m_serie" class="form-control ap-form-control">
              <option value="1">SI</option>
              <option value="0">NO</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Destino Retención obligatorio</label>
            <select id="m_ret" class="form-control ap-form-control">
              <option value="0">NO</option>
              <option value="1">SI</option>
            </select>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-9">
            <label class="ap-label">Ruta API</label>
            <input id="m_ruta_api" class="form-control ap-form-control" placeholder="public/api/importadores/imp_tralm.php" maxlength="150">
            <small class="ap-help">Se usa para QA (Ping) y para mapear módulos.</small>
          </div>
          <div class="col-md-3">
            <label class="ap-label">Versión</label>
            <input id="m_version" type="number" class="form-control ap-form-control" value="1" min="1" step="1">
          </div>
        </div>

        <div id="m_msg" style="margin-top:10px;border:1px solid #eee;padding:6px;max-height:90px;overflow:auto;"></div>
      </div>

      <div class="modal-footer" style="padding:10px 12px; gap:8px;">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelarModal">
          <i class="fa fa-times"></i> Cancelar
        </button>

        <!-- ✅ Clonar -->
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnClonar" style="display:none;">
          <i class="fa fa-copy"></i> Clonar
        </button>

        <!-- ✅ Ping -->
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPing" style="display:none;">
          <i class="fa fa-stethoscope"></i> Ping API
        </button>

        <button type="button" class="btn btn-sm btn-success" id="btnGuardar">
          <i class="fa fa-save"></i> Guardar
        </button>
      </div>

    </div>
  </div>
</div>

<script>
const AP_USR = <?php echo json_encode($USR); ?>;

// API Catálogo
const API_CAT = '../api/utilerias/catalogo_importadores.php';

// modal helpers (BS5 / BS4+jQ / fallback)
function apShowModal(id){
  const el = document.getElementById(id); if(!el) return;
  if(window.bootstrap && bootstrap.Modal){ bootstrap.Modal.getOrCreateInstance(el).show(); return; }
  if(window.jQuery && jQuery.fn && jQuery.fn.modal){ jQuery(el).modal('show'); return; }
  el.classList.add('show'); el.style.display='block'; document.body.classList.add('modal-open');
}
function apHideModal(id){
  const el = document.getElementById(id); if(!el) return;
  if(window.bootstrap && bootstrap.Modal){ bootstrap.Modal.getOrCreateInstance(el).hide(); return; }
  if(window.jQuery && jQuery.fn && jQuery.fn.modal){ jQuery(el).modal('hide'); return; }
  el.classList.remove('show'); el.style.display='none'; document.body.classList.remove('modal-open');
}

function up(v){ return (v||'').toString().trim().toUpperCase(); }
function setModalMsg(msg, isErr=false){
  const el=document.getElementById('m_msg');
  el.style.color = isErr ? '#b02a37' : '#0f5132';
  el.textContent = msg || '';
}
function badgeBool(v){
  const b = (v===1||v==='1'||v===true||up(v)==='SI');
  return b ? '<span class="ap-badge ok">SI</span>' : '<span class="ap-badge warn">NO</span>';
}
function badgeActivo(v){
  const b = (v===1||v==='1'||v===true);
  return b ? '<span class="ap-badge ok">ACTIVO</span>' : '<span class="ap-badge warn">INACTIVO</span>';
}

async function apiGet(url){
  const r = await fetch(url);
  const t = await r.text();
  try { return JSON.parse(t); } catch(e){ return {ok:false, msg:'Respuesta no-JSON', raw:t}; }
}
async function apiPost(params){
  const r = await fetch(API_CAT, {method:'POST', body: new URLSearchParams(params)});
  const t = await r.text();
  try { return JSON.parse(t); } catch(e){ return {ok:false, msg:'Respuesta no-JSON', raw:t}; }
}

function getFilters(){
  return {
    tipo: up(document.getElementById('fTipo').value || 'TODOS'),
    activo: document.getElementById('fActivo').value || 'TODOS',
    search: up(document.getElementById('fSearch').value || '')
  };
}

function renderChips(counts, currentTipo){
  const el = document.getElementById('chipsTipo');
  el.innerHTML = '';

  const tiposOrden = ['TODOS','INGRESO','EGRESO','TRASLADOS','PEDIDOS','OT','INVENTARIOS'];
  tiposOrden.forEach(tp=>{
    const cnt = (tp==='TODOS')
      ? Object.values(counts||{}).reduce((a,b)=>a+b,0)
      : (counts && counts[tp] ? counts[tp] : 0);

    const chip = document.createElement('div');
    chip.className = 'ap-chip' + ((currentTipo===tp) ? ' active' : '');
    chip.innerHTML = `<span>${tp}</span><span class="cnt">${cnt}</span>`;
    chip.onclick = ()=>{
      document.getElementById('fTipo').value = tp;
      cargarListado();
    };
    el.appendChild(chip);
  });
}

async function cargarListado(){
  const f = getFilters();
  const qs = new URLSearchParams({action:'list', tipo:f.tipo, activo:f.activo, search:f.search});
  const data = await apiGet(API_CAT + '?' + qs.toString());

  const tb = document.querySelector('#tabla tbody');
  tb.innerHTML = '';

  if(!data || !data.ok){
    const tr=document.createElement('tr');
    tr.innerHTML = `<td colspan="14" style="color:#b02a37;"><b>Error:</b> ${(data&&data.msg)?data.msg:'No se pudo cargar'} ${data&&data.raw?('<div style="white-space:normal;">'+String(data.raw).slice(0,500)+'</div>'):''}</td>`;
    tb.appendChild(tr);
    document.getElementById('kpi').textContent = 'Total: 0 | Activos: 0 | Inactivos: 0';
    document.getElementById('kpi2').textContent = '';
    renderChips({}, f.tipo);
    return;
  }

  const rows = Array.isArray(data.rows) ? data.rows : [];
  let act=0, inact=0;
  const tipoCounts = {};
  rows.forEach(r=>{
    const t = up(r.tipo||'');
    tipoCounts[t] = (tipoCounts[t]||0) + 1;
    const activo = (r.activo==1||r.activo==='1');
    if(activo) act++; else inact++;

    const tr=document.createElement('tr');
    tr.innerHTML = `
      <td class="ap-actions" style="text-align:center;">
        <button class="btn btn-sm btn-outline-primary mr-1" title="Editar" onclick="editar(${r.id_importador})"><i class="fa fa-pencil"></i></button>
        <button class="btn btn-sm btn-outline-${activo?'warning':'success'} mr-1" title="Activar/Inactivar" onclick="toggleActivo(${r.id_importador})">
          <i class="fa fa-power-off"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary mr-1" title="Ping API" onclick="pingOne(${r.id_importador})">
          <i class="fa fa-stethoscope"></i>
        </button>
        <button class="btn btn-sm btn-outline-info" title="Clonar" onclick="clonar(${r.id_importador})">
          <i class="fa fa-copy"></i>
        </button>
      </td>
      <td><b>${up(r.clave||'')}</b></td>
      <td style="white-space:normal;min-width:220px;">${(r.descripcion||'')}</td>
      <td><span class="ap-badge">${up(r.tipo||'')}</span></td>
      <td>${badgeActivo(r.activo)}</td>
      <td>${badgeBool(r.permite_rollback)}</td>
      <td>${badgeBool(r.impacta_kardex_default)}</td>
      <td>${badgeBool(r.requiere_layout)}</td>
      <td>${badgeBool(r.requiere_bl_origen)}</td>
      <td>${badgeBool(r.requiere_lote_si_aplica)}</td>
      <td>${badgeBool(r.requiere_serie_si_aplica)}</td>
      <td>${badgeBool(r.destino_retencion_obligatorio)}</td>
      <td style="white-space:normal;min-width:220px;">${(r.ruta_api||'')}</td>
      <td>${r.version ?? ''}</td>
    `;
    tb.appendChild(tr);
  });

  document.getElementById('kpi').textContent = `Total: ${rows.length} | Activos: ${act} | Inactivos: ${inact}`;
  document.getElementById('kpi2').textContent = (f.tipo && f.tipo!=='TODOS') ? `Filtro Tipo: ${f.tipo}` : 'Filtro Tipo: TODOS';

  // Para chips: si el usuario tiene filtros de activo/búsqueda, counts pueden ser parciales, pero sirven igual para UX.
  renderChips(tipoCounts, f.tipo);
}

function limpiarModal(){
  document.getElementById('m_id_importador').value = '0';
  document.getElementById('m_clave').value = '';
  document.getElementById('m_descripcion').value = '';
  document.getElementById('m_tipo').value = '';
  document.getElementById('m_activo').value = '1';
  document.getElementById('m_perm_rollback').value = '1';
  document.getElementById('m_kardex').value = '1';
  document.getElementById('m_layout').value = '1';
  document.getElementById('m_bl').value = '0';
  document.getElementById('m_lote').value = '1';
  document.getElementById('m_serie').value = '1';
  document.getElementById('m_ret').value = '0';
  document.getElementById('m_ruta_api').value = '';
  document.getElementById('m_version').value = '1';
  document.getElementById('m_clave').disabled = false;
  setModalMsg('');

  document.getElementById('btnClonar').style.display = 'none';
  document.getElementById('btnPing').style.display = 'none';
}

function abrirNuevo(){
  limpiarModal();
  document.getElementById('modalTitle').innerHTML = '<i class="fa fa-plus"></i> Nuevo importador';
  apShowModal('modalImp');
}

async function editar(id){
  limpiarModal();
  const data = await apiGet(API_CAT + '?action=get&id_importador=' + encodeURIComponent(id));
  if(!data || !data.ok){ alert((data&&data.msg)?data.msg:'No se pudo cargar'); return; }

  const r = data.row || {};
  document.getElementById('m_id_importador').value = r.id_importador || 0;
  document.getElementById('m_clave').value = up(r.clave||'');
  document.getElementById('m_descripcion').value = r.descripcion || '';
  document.getElementById('m_tipo').value = up(r.tipo||'');
  document.getElementById('m_activo').value = (r.activo==1||r.activo==='1') ? '1' : '0';
  document.getElementById('m_perm_rollback').value = (r.permite_rollback==1||r.permite_rollback==='1') ? '1' : '0';
  document.getElementById('m_kardex').value = (r.impacta_kardex_default==1||r.impacta_kardex_default==='1') ? '1' : '0';
  document.getElementById('m_layout').value = (r.requiere_layout==1||r.requiere_layout==='1') ? '1' : '0';
  document.getElementById('m_bl').value = (r.requiere_bl_origen==1||r.requiere_bl_origen==='1') ? '1' : '0';
  document.getElementById('m_lote').value = (r.requiere_lote_si_aplica==1||r.requiere_lote_si_aplica==='1') ? '1' : '0';
  document.getElementById('m_serie').value = (r.requiere_serie_si_aplica==1||r.requiere_serie_si_aplica==='1') ? '1' : '0';
  document.getElementById('m_ret').value = (r.destino_retencion_obligatorio==1||r.destino_retencion_obligatorio==='1') ? '1' : '0';
  document.getElementById('m_ruta_api').value = r.ruta_api || '';
  document.getElementById('m_version').value = r.version ?? 1;

  // Gobernanza: clave NO editable
  document.getElementById('m_clave').disabled = true;

  // ✅ botones premium
  document.getElementById('btnClonar').style.display = '';
  document.getElementById('btnPing').style.display = '';

  document.getElementById('modalTitle').innerHTML = '<i class="fa fa-pencil"></i> Editar importador';
  apShowModal('modalImp');
}

async function toggleActivo(id){
  if(!confirm('¿Cambiar estado (activar/inactivar) del importador?')) return;
  const data = await apiPost({action:'toggle_activo', id_importador:String(id)});
  if(!data || !data.ok){ alert((data&&data.msg)?data.msg:'No se pudo actualizar'); return; }
  await cargarListado();
}

/* ✅ Ping de API de un registro */
async function pingOne(id){
  const data = await apiPost({action:'ping', id_importador:String(id)});
  if(!data || !data.ok){
    alert((data&&data.msg)?data.msg:'Ping falló');
    return;
  }
  alert(data.msg);
}

/* ✅ Ping masivo */
async function pingMasivo(){
  const data = await apiPost({action:'ping_all'});
  if(!data || !data.ok){
    alert((data&&data.msg)?data.msg:'Ping masivo falló'); return;
  }
  // Resumen friendly
  const ok = data.ok_count || 0;
  const err = data.err_count || 0;
  alert(`Ping terminado. OK: ${ok} | Error: ${err}`);
}

/* ✅ Clonar (plantilla) */
async function clonar(id){
  const nuevaClave = prompt('Nueva clave (ej. TRALM_V2):');
  if(!nuevaClave) return;

  const data = await apiPost({action:'clone', id_importador:String(id), nueva_clave:up(nuevaClave)});
  if(!data || !data.ok){
    alert((data&&data.msg)?data.msg:'Clone falló'); return;
  }
  alert('Clonado OK');
  await cargarListado();
}

async function guardar(){
  const id = parseInt(document.getElementById('m_id_importador').value||'0',10);

  const clave = up(document.getElementById('m_clave').value||'');
  const descripcion = (document.getElementById('m_descripcion').value||'').trim();
  const tipo = up(document.getElementById('m_tipo').value||'');

  if(id===0 && !clave){ setModalMsg('Clave obligatoria.', true); return; }
  if(!tipo){ setModalMsg('Tipo obligatorio.', true); return; }
  if(!descripcion){ setModalMsg('Descripción obligatoria.', true); return; }

  if(id===0) document.getElementById('m_clave').value = clave;
  document.getElementById('m_tipo').value = tipo;

  const payload = {
    action: (id===0 ? 'create' : 'update'),
    id_importador: String(id),

    clave: clave,
    descripcion: descripcion,
    tipo: tipo,

    activo: document.getElementById('m_activo').value,
    permite_rollback: document.getElementById('m_perm_rollback').value,
    impacta_kardex_default: document.getElementById('m_kardex').value,
    requiere_layout: document.getElementById('m_layout').value,

    requiere_bl_origen: document.getElementById('m_bl').value,
    requiere_lote_si_aplica: document.getElementById('m_lote').value,
    requiere_serie_si_aplica: document.getElementById('m_serie').value,
    destino_retencion_obligatorio: document.getElementById('m_ret').value,

    ruta_api: (document.getElementById('m_ruta_api').value||'').trim(),
    version: String(parseInt(document.getElementById('m_version').value||'1',10))
  };

  setModalMsg('Guardando...', false);
  const data = await apiPost(payload);

  if(!data || !data.ok){
    setModalMsg((data&&data.msg)?data.msg:'Error', true);
    return;
  }

  setModalMsg('Guardado OK.', false);
  apHideModal('modalImp');
  await cargarListado();
}

document.addEventListener('DOMContentLoaded', function(){
  cargarListado();

  document.getElementById('btnNuevo').addEventListener('click', abrirNuevo);
  document.getElementById('btnRefrescar').addEventListener('click', cargarListado);
  document.getElementById('btnBuscar').addEventListener('click', cargarListado);
  document.getElementById('btnPingMasivo').addEventListener('click', pingMasivo);

  document.getElementById('btnLimpiar').addEventListener('click', ()=>{
    document.getElementById('fTipo').value='TODOS';
    document.getElementById('fActivo').value='TODOS';
    document.getElementById('fSearch').value='';
    cargarListado();
  });

  document.getElementById('fSearch').addEventListener('keydown', (e)=>{
    if(e.key === 'Enter'){ e.preventDefault(); cargarListado(); }
  });

  document.getElementById('btnCancelarModal').addEventListener('click', ()=> apHideModal('modalImp'));
  document.getElementById('btnGuardar').addEventListener('click', guardar);

  document.getElementById('btnClonar').addEventListener('click', ()=>{
    const id = parseInt(document.getElementById('m_id_importador').value||'0',10);
    if(id>0) clonar(id);
  });

  document.getElementById('btnPing').addEventListener('click', ()=>{
    const id = parseInt(document.getElementById('m_id_importador').value||'0',10);
    if(id>0) pingOne(id);
  });

  document.getElementById('m_clave').addEventListener('input', function(){
    this.value = up(this.value).replace(/[^A-Z0-9_]/g,'');
  });
  document.getElementById('m_tipo').addEventListener('change', function(){
    this.value = up(this.value);
  });
});

// Exponer para onclick de tabla
window.editar = editar;
window.toggleActivo = toggleActivo;
window.pingOne = pingOne;
window.clonar = clonar;
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
