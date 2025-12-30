<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:10px}
.ap-title{font-size:18px;font-weight:700;color:#0b5ed7;display:flex;align-items:center;gap:10px;margin:6px 0 12px}
.ap-title i{font-size:18px}
.ap-panel{background:#fff;border:1px solid #d0d7e2;border-radius:12px;padding:10px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ap-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:10px}
.ap-toolbar .g{display:flex;flex-direction:column;gap:4px}
.ap-toolbar label{font-size:10px;color:#334155}
.ap-toolbar input,.ap-toolbar select{font-size:10px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:10px;min-width:180px}
.ap-btn{font-size:10px;padding:7px 10px;border-radius:10px;border:1px solid #cbd5e1;background:#f8fafc;cursor:pointer}
.ap-btn.primary{background:#0b5ed7;border-color:#0b5ed7;color:#fff}
.ap-btn:disabled{opacity:.6;cursor:not-allowed}
.ap-tablewrap{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
table.ap{width:100%;border-collapse:collapse;font-size:10px}
table.ap th, table.ap td{border-bottom:1px solid #eef2f7;padding:6px 6px;vertical-align:middle;text-align:center;white-space:nowrap}
table.ap th{background:#f1f5f9;color:#0f172a;font-weight:700}
.ap-scroll{max-height:58vh;overflow:auto}
.ap-pager{display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:10px;flex-wrap:wrap}
.ap-pager .info{color:#475569}
.ap-badge{padding:2px 8px;border-radius:999px;border:1px solid #cbd5e1;background:#f8fafc}
.ap-badge.ok{background:#dcfce7;border-color:#86efac}
.ap-badge.bad{background:#fee2e2;border-color:#fca5a5}
.ap-spinner{display:none;align-items:center;gap:8px;color:#0b5ed7;font-weight:700}
.ap-spinner.show{display:flex}

/* KPI Cards */
.ap-kpis{display:grid;grid-template-columns:repeat(6, 1fr);gap:10px;margin:10px 0}
.ap-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:10px;box-shadow:0 1px 3px rgba(0,0,0,.05);text-align:left}
.ap-card .h{display:flex;justify-content:space-between;align-items:center;color:#0b5ed7;font-weight:800;font-size:11px}
.ap-card .v{font-size:18px;font-weight:900;color:#0f172a;margin-top:6px}
.ap-card .s{font-size:10px;color:#64748b;margin-top:2px}
@media(max-width:1200px){ .ap-kpis{grid-template-columns:repeat(3, 1fr)} }
@media(max-width:640px){ .ap-kpis{grid-template-columns:repeat(2, 1fr)} }

.ap-muted{color:#64748b}
</style>

<div class="ap-container">
  <div class="ap-title">
    <i class="fa-solid fa-stopwatch"></i>
    <div>Bitácora de Tiempos · Consulta Ejecutiva</div>
  </div>

  <div class="ap-panel">

    <!-- KPI Cards -->
    <div class="ap-kpis">
      <div class="ap-card">
        <div class="h"><span>Total</span><i class="fa-solid fa-layer-group"></i></div>
        <div class="v" id="k_total">—</div>
        <div class="s">Registros en el alcance actual</div>
      </div>
      <div class="ap-card">
        <div class="h"><span>Abiertos</span><i class="fa-solid fa-circle-play"></i></div>
        <div class="v" id="k_abiertos">—</div>
        <div class="s">Cerrado = 0</div>
      </div>
      <div class="ap-card">
        <div class="h"><span>Cerrados</span><i class="fa-solid fa-circle-check"></i></div>
        <div class="v" id="k_cerrados">—</div>
        <div class="s">Cerrado = 1</div>
      </div>
      <div class="ap-card">
        <div class="h"><span>En curso</span><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="v" id="k_curso">—</div>
        <div class="s">HF NULL y no cerrado</div>
      </div>
      <div class="ap-card">
        <div class="h"><span>Visitas</span><i class="fa-solid fa-location-dot"></i></div>
        <div class="v" id="k_visitas">—</div>
        <div class="s">Visita = 1</div>
      </div>
      <div class="ap-card">
        <div class="h"><span>Prom. min</span><i class="fa-solid fa-gauge-high"></i></div>
        <div class="v" id="k_prom">—</div>
        <div class="s">Avg(HI→HF) minutos</div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="ap-toolbar">
      <div class="g">
        <label>Empresa</label>
        <select id="f_emp">
          <option value="">Todas</option>
        </select>
      </div>

      <div class="g">
        <label>Ruta</label>
        <select id="f_ruta">
          <option value="">Todas</option>
        </select>
      </div>

      <div class="g">
        <label>Vendedor</label>
        <select id="f_vend">
          <option value="">Todos</option>
        </select>
      </div>

      <div class="g">
        <label>Desde</label>
        <input id="f_desde" type="date">
      </div>

      <div class="g">
        <label>Hasta</label>
        <input id="f_hasta" type="date">
      </div>

      <div class="g">
        <label>Búsqueda</label>
        <input id="f_q" placeholder="Código / descripción / tip / id">
      </div>

      <button class="ap-btn primary" id="btn_buscar"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button>
      <button class="ap-btn" id="btn_export_datos"><i class="fa-solid fa-file-csv"></i> Export Datos</button>

      <div class="ap-spinner" id="spn"><i class="fa-solid fa-spinner fa-spin"></i> Procesando…</div>
      <div class="ap-muted" id="msg_filters"></div>
    </div>

    <!-- Tabla -->
    <div class="ap-tablewrap">
      <div class="ap-scroll">
        <table class="ap" id="tbl">
          <thead>
            <tr>
              <th>Id</th>
              <th>Empresa</th>
              <th>Codigo</th>
              <th>Descripcion</th>
              <th>HI</th>
              <th>HF</th>
              <th>Min</th>
              <th>HT</th>
              <th>TS</th>
              <th>Visita</th>
              <th>Programado</th>
              <th>DiaO</th>
              <th>RutaId</th>
              <th>Cerrado</th>
              <th>IdV</th>
              <th>Tip</th>
              <th>Lat</th>
              <th>Lng</th>
              <th>Pila</th>
              <th>IdVendedor</th>
              <th>Ayudante1</th>
              <th>Ayudante2</th>
              <th>Vehiculo</th>
            </tr>
          </thead>
          <tbody id="tb"></tbody>
        </table>
      </div>
    </div>

    <!-- Paginador -->
    <div class="ap-pager">
      <div class="info" id="pager_info">—</div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button class="ap-btn" id="btn_prev"><i class="fa-solid fa-chevron-left"></i> Anterior</button>
        <span class="ap-badge" id="pager_page">Página 1</span>
        <button class="ap-btn" id="btn_next">Siguiente <i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>

  </div>
</div>

<script>
/* =================== Endpoints =================== */
const API_BIT = '../api/bitacora_tiempos.php';
const API_EMP = '../api/almacenes.php';        // {ok:true,data:[{Id,clave,nombre}]}
const API_RUT = '../api/catalogo_rutas.php';   // POST almacen=<ID> => rutas por cve_almacenp
const API_USU = '../api/usuarios.php';         // [{id_user,nombre_completo,...}]

let PAGE = 1;
let PAGESIZE = 25;
let TOTAL = 0;

const $ = (id)=>document.getElementById(id);
function sp(show){ $('spn').classList.toggle('show', !!show); }
function setMsg(t){ $('msg_filters').textContent = t || ''; }

function esc(s){
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
}
function todayISO(){
  const d = new Date();
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const dd = String(d.getDate()).padStart(2,'0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}
function dateToStart(dateStr){ return dateStr ? (dateStr + ' 00:00:00') : ''; }
function dateToEnd(dateStr){ return dateStr ? (dateStr + ' 23:59:59') : ''; }

function getEmpresaClave(){
  const sel = $('f_emp');
  const opt = sel.options[sel.selectedIndex];
  return opt ? (opt.getAttribute('data-clave') || '') : '';
}
function getEmpresaId(){
  return $('f_emp').value || '';
}

function qsCommon(action){
  const p = new URLSearchParams();
  p.set('action', action);
  p.set('q', $('f_q').value.trim());

  // ✅ Mandamos ambos: clave + id (para que el API haga match con cualquiera)
  p.set('IdEmpresa', getEmpresaClave());   // varchar (clave)
  p.set('emp_id', getEmpresaId());         // num (id)

  p.set('RutaId', $('f_ruta').value);
  p.set('IdVendedor', $('f_vend').value);

  p.set('desde', dateToStart($('f_desde').value));
  p.set('hasta', dateToEnd($('f_hasta').value));
  return p;
}

/* =================== Cargar filtros =================== */

// Empresa: value=Id (para rutas), data-clave=clave (para bitácora)
async function loadEmpresas(){
  const sel = $('f_emp');
  sel.innerHTML = `<option value="">Todas</option>`;
  try{
    const r = await fetch(API_EMP + '?action=list', {cache:'no-store'});
    const j = await r.json();
    const arr = (j && j.ok && Array.isArray(j.data)) ? j.data : [];

    let opts = `<option value="">Todas</option>`;
    for(const row of arr){
      const id = row.Id ?? row.id ?? row.ID ?? '';
      const clave = row.clave ?? row.Clave ?? row.CLAVE ?? '';
      const nombre = row.nombre ?? row.Nombre ?? row.NOMBRE ?? clave ?? id;
      if(id==='' || id===null) continue;
      opts += `<option value="${esc(id)}" data-clave="${esc(clave)}">${esc(nombre)}</option>`;
    }
    sel.innerHTML = opts;
  }catch(e){
    setMsg('Aviso: no se pudo cargar Empresa (almacenes.php).');
  }
}

// Rutas por cve_almacenp (ID del almacén)
async function loadRutas(idAlmacenp){
  const sel = $('f_ruta');
  sel.innerHTML = `<option value="">Todas</option>`;
  try{
    const fd = new URLSearchParams();
    fd.set('almacen', idAlmacenp || '');

    const r = await fetch(API_RUT, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: fd.toString()
    });
    const j = await r.json();
    const arr = Array.isArray(j) ? j : (Array.isArray(j.data) ? j.data : []);

    let opts = `<option value="">Todas</option>`;
    for(const row of arr){
      const id = row.id ?? row.ID ?? row.RutaId ?? '';
      const nombre = row.nombre ?? row.Nombre ?? row.descripcion ?? id;
      if(id==='' || id===null) continue;
      opts += `<option value="${esc(id)}">${esc(nombre)}</option>`;
    }
    sel.innerHTML = opts;
  }catch(e){
    setMsg('Aviso: no se pudo cargar Rutas (catalogo_rutas.php).');
  }
}

// Vendedores con Nombre Completo
async function loadVendedores(){
  const sel = $('f_vend');
  sel.innerHTML = `<option value="">Todos</option>`;
  try{
    const r = await fetch(API_USU + '?action=list', {cache:'no-store'});
    const j = await r.json();
    const arr = Array.isArray(j) ? j : (Array.isArray(j.data) ? j.data : []);

    let opts = `<option value="">Todos</option>`;
    for(const row of arr){
      const id = row.id_user ?? row.IdUser ?? row.id ?? '';
      const nombre = row.nombre_completo ?? row.NombreCompleto ?? row.nombre ?? id;
      if(id==='' || id===null) continue;
      opts += `<option value="${esc(id)}">${esc(nombre)}</option>`;
    }
    sel.innerHTML = opts;
  }catch(e){
    setMsg('Aviso: no se pudo cargar Vendedores (usuarios.php).');
  }
}

async function initFilters(){
  const t = todayISO();
  $('f_desde').value = t;
  $('f_hasta').value = t;

  await loadEmpresas();
  await loadRutas(''); // todas al inicio
  await loadVendedores();

  // ✅ Dependiente: al cambiar empresa, recarga rutas por ID (cve_almacenp)
  $('f_emp').addEventListener('change', async ()=>{
    const idAlmacenp = getEmpresaId();
    await loadRutas(idAlmacenp);
    $('f_ruta').value = '';
    PAGE = 1;
    refreshAll();
  });
}

/* =================== Render =================== */
function fmt(v){ return (v===null||v===undefined) ? '' : String(v); }
function badge01(v){
  const n = parseInt(v||0,10);
  return n===1 ? '<span class="ap-badge ok">1</span>' : '<span class="ap-badge">0</span>';
}
function badgeCerrado(v){
  const n = parseInt(v||0,10);
  return n===1 ? '<span class="ap-badge bad">CERRADO</span>' : '<span class="ap-badge ok">ABIERTO</span>';
}
function minutesBetween(hi, hf){
  if(!hi || !hf) return '';
  const d1 = new Date(String(hi).replace(' ','T'));
  const d2 = new Date(String(hf).replace(' ','T'));
  if(isNaN(d1.getTime()) || isNaN(d2.getTime())) return '';
  const m = Math.round((d2 - d1)/60000);
  return (m>=0 && isFinite(m)) ? String(m) : '';
}

/* =================== Data =================== */
async function loadStats(){
  const p = qsCommon('stats');
  const r = await fetch(API_BIT + '?' + p.toString(), {cache:'no-store'});
  const j = await r.json();
  if(!j.ok) return;
  const k = j.kpi || {};
  $('k_total').textContent    = fmt(k.total ?? '0');
  $('k_abiertos').textContent = fmt(k.abiertos ?? '0');
  $('k_cerrados').textContent = fmt(k.cerrados ?? '0');
  $('k_curso').textContent    = fmt(k.en_curso ?? '0');
  $('k_visitas').textContent  = fmt(k.visitas ?? '0');
  $('k_prom').textContent     = (k.prom_min===null || k.prom_min===undefined) ? '—' : fmt(k.prom_min);
}

async function loadList(){
  const p = qsCommon('list');
  p.set('page', String(PAGE));
  p.set('pageSize', String(PAGESIZE));

  const r = await fetch(API_BIT + '?' + p.toString(), {cache:'no-store'});
  const j = await r.json();

  TOTAL = j.total || 0;
  const data = j.data || [];

  const tb = $('tb');
  tb.innerHTML = '';

  data.forEach(row=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${fmt(row.Id)}</td>
      <td>${fmt(row.IdEmpresa)}</td>
      <td>${fmt(row.Codigo)}</td>
      <td style="text-align:left;max-width:420px;overflow:hidden;text-overflow:ellipsis">${fmt(row.Descripcion)}</td>
      <td>${fmt(row.HI)}</td>
      <td>${fmt(row.HF)}</td>
      <td>${minutesBetween(row.HI, row.HF)}</td>
      <td>${fmt(row.HT)}</td>
      <td>${fmt(row.TS)}</td>
      <td>${badge01(row.Visita)}</td>
      <td>${badge01(row.Programado)}</td>
      <td>${fmt(row.DiaO)}</td>
      <td>${fmt(row.RutaId)}</td>
      <td>${badgeCerrado(row.Cerrado)}</td>
      <td>${fmt(row.IdV)}</td>
      <td>${fmt(row.Tip)}</td>
      <td>${fmt(row.latitude)}</td>
      <td>${fmt(row.longitude)}</td>
      <td>${fmt(row.pila)}</td>
      <td>${fmt(row.IdVendedor)}</td>
      <td>${fmt(row.Id_Ayudante1)}</td>
      <td>${fmt(row.Id_Ayudante2)}</td>
      <td>${fmt(row.IdVehiculo)}</td>
    `;
    tb.appendChild(tr);
  });

  const totalPages = Math.max(1, Math.ceil(TOTAL / PAGESIZE));
  $('pager_page').textContent = `Página ${PAGE} / ${totalPages}`;
  const ini = TOTAL===0 ? 0 : ((PAGE-1)*PAGESIZE + 1);
  const fin = Math.min(PAGE*PAGESIZE, TOTAL);
  $('pager_info').textContent = `Mostrando ${ini}-${fin} de ${TOTAL} registros · PageSize ${PAGESIZE}`;

  $('btn_prev').disabled = (PAGE<=1);
  $('btn_next').disabled = (PAGE>=totalPages);
}

async function refreshAll(){
  sp(true);
  try{
    await loadStats();
    await loadList();
  }catch(e){
    alert('Error: ' + e.message);
  }finally{
    sp(false);
  }
}

/* =================== Eventos =================== */
$('btn_buscar').addEventListener('click', ()=>{ PAGE=1; refreshAll(); });
$('btn_prev').addEventListener('click', ()=>{ if(PAGE>1){ PAGE--; refreshAll(); }});
$('btn_next').addEventListener('click', ()=>{ PAGE++; refreshAll(); });

$('btn_export_datos').addEventListener('click', ()=>{
  window.location = API_BIT + '?action=export_csv&tipo=datos';
});

/* =================== Init =================== */
(async ()=>{
  await initFilters();
  await refreshAll();
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
