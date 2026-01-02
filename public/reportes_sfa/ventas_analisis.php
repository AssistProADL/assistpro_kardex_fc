<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
 
<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.ap-sub{color:#6c757d;margin-bottom:10px}
.ap-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ap-card{width:260px;background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ap-card .h{display:flex;justify-content:space-between;align-items:center}
.ap-card .h b{font-size:13px}
.ap-kpi{font-size:20px;font-weight:700;margin-top:6px}
.ap-muted{color:#6c757d;font-size:11px}
.ap-filters{background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px;margin-bottom:10px}
.ap-filters .row{--bs-gutter-x:8px}
.ap-table{background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px}
.table{font-size:11px}
.ap-pager{display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:10px;flex-wrap:wrap}
.ap-btn{border:1px solid #0b5ed7;background:#0b5ed7;color:#fff;border-radius:8px;padding:6px 10px;font-size:12px}
.ap-btn2{border:1px solid #d0d7e2;background:#fff;color:#0b5ed7;border-radius:8px;padding:6px 10px;font-size:12px}
.ap-btn:disabled,.ap-btn2:disabled{opacity:.5;cursor:not-allowed}
.ap-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;border:1px solid #d0d7e2}
.ap-right{margin-left:auto}
.ap-disabled{opacity:.65;pointer-events:none}
.ap-help{font-size:11px;color:#6c757d}
.ap-wait{display:none;gap:8px;align-items:center}
.ap-wait.show{display:flex}
</style>

<div class="ap-container">
  <div class="ap-title">
    <i class="fa-solid fa-chart-line"></i>
    Análisis de Ventas (API)
    <span class="ap-right ap-badge" id="lblRango">Rango: —</span>
  </div>
  <div class="ap-sub">
    Consolida KPIs y transacciones desde <b>Venta</b>. Filtros ejecutivos + exportación CSV.
    <span class="ap-help ms-2">Tip: selecciona Empresa / Ruta / Vendedor y presiona Buscar.</span>
  </div>

  <!-- KPI Cards -->
  <div class="ap-cards">
    <div class="ap-card">
      <div class="h"><b>Ventas</b><i class="fa-solid fa-receipt"></i></div>
      <div class="ap-kpi" id="k_ventas">—</div>
      <div class="ap-muted">Transacciones filtradas</div>
    </div>

    <div class="ap-card">
      <div class="h"><b>Total</b><i class="fa-solid fa-dollar-sign"></i></div>
      <div class="ap-kpi" id="k_total">—</div>
      <div class="ap-muted">Suma TOTAL</div>
    </div>

    <div class="ap-card">
      <div class="h"><b>Ticket Prom.</b><i class="fa-solid fa-chart-column"></i></div>
      <div class="ap-kpi" id="k_ticket">—</div>
      <div class="ap-muted">AVG(TOTAL)</div>
    </div>

    <div class="ap-card">
      <div class="h"><b>Items</b><i class="fa-solid fa-cubes"></i></div>
      <div class="ap-kpi" id="k_items">—</div>
      <div class="ap-muted">Suma Items</div>
    </div>

    <!-- NUEVAS: Crédito / Contado -->
    <div class="ap-card">
      <div class="h"><b>Crédito</b><i class="fa-solid fa-hand-holding-dollar"></i></div>
      <div class="ap-kpi" id="k_credito">—</div>
      <div class="ap-muted">Monto TipoVta = Crédito</div>
    </div>

    <div class="ap-card">
      <div class="h"><b>Contado</b><i class="fa-solid fa-cash-register"></i></div>
      <div class="ap-kpi" id="k_contado">—</div>
      <div class="ap-muted">Monto TipoVta = Contado</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="ap-filters">
    <div class="row g-2 align-items-end">

      <!-- Empresa: selector -->
      <div class="col-12 col-md-3">
        <label class="form-label">Empresa (Almacén)</label>
        <select class="form-select form-select-sm" id="IdEmpresa">
          <option value="">(Seleccione)</option>
        </select>
      </div>

      <!-- Ruta: selector -->
      <div class="col-12 col-md-3">
        <label class="form-label">Ruta</label>
        <select class="form-select form-select-sm" id="RutaId" disabled>
          <option value="">(Seleccione)</option>
        </select>
      </div>

      <!-- Vendedor: selector -->
      <div class="col-12 col-md-3">
        <label class="form-label">Vendedor</label>
        <select class="form-select form-select-sm" id="VendedorId">
          <option value="">(Seleccione)</option>
        </select>
      </div>

      <!-- Fechas DESPUÉS de vendedor -->
      <div class="col-12 col-md-1">
        <label class="form-label">Desde</label>
        <input type="date" class="form-control form-control-sm" id="fecha_ini">
      </div>

      <div class="col-12 col-md-1">
        <label class="form-label">Hasta</label>
        <input type="date" class="form-control form-control-sm" id="fecha_fin">
      </div>

      <div class="col-12 col-md-1">
        <label class="form-label">Cancelada</label>
        <select class="form-select form-select-sm" id="Cancelada">
          <option value="">(Todas)</option>
          <option value="0">No</option>
          <option value="1">Sí</option>
        </select>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Búsqueda</label>
        <input type="text" class="form-control form-control-sm" id="q"
               placeholder="Documento, doc salida, tipo vta...">
      </div>

      <div class="col-12 col-md-6 d-flex gap-2 align-items-center">
        <button class="ap-btn" id="btnBuscar">
          <i class="fa-solid fa-magnifying-glass"></i> Buscar
        </button>
        <button class="ap-btn2" id="btnLimpiar">
          <i class="fa-solid fa-broom"></i> Limpiar
        </button>

        <div class="ap-wait ms-2" id="wait">
          <div class="spinner-border spinner-border-sm" role="status"></div>
          <span class="ap-muted">Consultando…</span>
        </div>

        <div class="ms-auto d-flex gap-2">
          <a class="ap-btn2 ap-disabled" id="btnCsvLayout" href="#">
            <i class="fa-solid fa-file-csv"></i> CSV Layout
          </a>
          <a class="ap-btn2 ap-disabled" id="btnCsvDatos" href="#">
            <i class="fa-solid fa-download"></i> CSV Datos
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Grid -->
  <div class="ap-table">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <b>Detalle de Ventas</b>
      <span class="ap-muted" id="lblTotal">Sin consulta</span>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="white-space:nowrap">Acciones</th>
            <th style="white-space:nowrap">Id</th>
            <th style="white-space:nowrap">Fecha</th>
            <th style="white-space:nowrap">Documento</th>
            <th style="white-space:nowrap">Cliente</th>
            <th style="white-space:nowrap">Empresa</th>
            <th style="white-space:nowrap">Ruta</th>
            <th style="white-space:nowrap">Vendedor</th>
            <th style="white-space:nowrap">Tipo</th>
            <th style="text-align:right;white-space:nowrap">Subtotal</th>
            <th style="text-align:right;white-space:nowrap">IVA</th>
            <th style="text-align:right;white-space:nowrap">IEPS</th>
            <th style="text-align:right;white-space:nowrap">Total</th>
            <th style="text-align:right;white-space:nowrap">Items</th>
            <th style="white-space:nowrap">Cancelada</th>
          </tr>
        </thead>
        <tbody id="tb">
          <tr><td colspan="15" class="ap-muted">Seleccione filtros y presione <b>Buscar</b>.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pager -->
    <div class="ap-pager">
      <div class="ap-muted">
        Página <b id="p_cur">—</b> de <b id="p_tot">—</b> |
        Mostrando <b id="p_range">—</b>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <button class="ap-btn2" id="btnPrev" disabled>
          <i class="fa-solid fa-chevron-left"></i> Anterior
        </button>
        <button class="ap-btn2" id="btnNext" disabled>
          Siguiente <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="mdlDetalle" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-file-invoice"></i> Detalle de Venta
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 mb-2">
          <span class="ap-badge" id="d_doc">Documento: —</span>
          <span class="ap-badge" id="d_cli">Cliente: —</span>
          <span class="ap-badge" id="d_emp">Empresa: —</span>
          <span class="ap-badge" id="d_ruta">Ruta: —</span>
          <span class="ap-badge" id="d_ven">Vendedor: —</span>
          <span class="ap-badge" id="d_fecha">Fecha: —</span>
          <span class="ap-badge" id="d_total">Total: —</span>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Artículo</th>
                <th>Descripción</th>
                <th style="text-align:right">Precio</th>
                <th style="text-align:right">Pza</th>
                <th style="text-align:right">Kg</th>
                <th style="text-align:right">Desc</th>
                <th style="text-align:right">Importe</th>
                <th style="text-align:right">IVA</th>
                <th style="text-align:right">IEPS</th>
                <th style="text-align:right">Utilidad</th>
              </tr>
            </thead>
            <tbody id="dtb"></tbody>
          </table>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-2">
          <span class="ap-badge" id="d_partidas">Partidas: 0</span>
          <span class="ap-badge" id="d_total_calc">Total calculado: $0.00</span>
        </div>
      </div>

      <div class="modal-footer">
        <button class="ap-btn2" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ========= Rutas APIs ========= */
const API_VENTAS = '../api/ventas_analisis.php';

// APIs de filtros
const API_ALMACENES = '../api/almacenes.php';
const API_RUTAS     = '../api/catalogo_rutas.php';
const API_USUARIOS  = '../api/usuarios.php';

let page = 1;
const pageSize = 25;
let total = 0;
let pages = 1;

// Cache de catálogos para mostrar nombres en la grilla / modal
let MAP_EMP = new Map();   // idEmpresa -> nombre
let MAP_RUTA = new Map();  // RutaId -> nombre
let MAP_VEN = new Map();   // VendedorId -> nombre_completo

function money(n){
  n = Number(n||0);
  return n.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
}
function fmtDate(dt){
  if(!dt) return '';
  return String(dt).replace('T',' ').substring(0,19);
}
function showWait(on){
  const w = document.getElementById('wait');
  if(on) w.classList.add('show');
  else w.classList.remove('show');
}
function setKpiEmpty(){
  document.getElementById('k_ventas').textContent = '—';
  document.getElementById('k_total').textContent  = '—';
  document.getElementById('k_ticket').textContent = '—';
  document.getElementById('k_items').textContent  = '—';
  document.getElementById('k_credito').textContent = '—';
  document.getElementById('k_contado').textContent = '—';
}
function disableCsv(disable){
  const a1 = document.getElementById('btnCsvLayout');
  const a2 = document.getElementById('btnCsvDatos');
  if(disable){
    a1.classList.add('ap-disabled'); a2.classList.add('ap-disabled');
  }else{
    a1.classList.remove('ap-disabled'); a2.classList.remove('ap-disabled');
  }
}
function setGridEmpty(msg){
  const tb = document.getElementById('tb');
  tb.innerHTML = `<tr><td colspan="15" class="ap-muted">${msg}</td></tr>`;
  document.getElementById('lblTotal').textContent = 'Sin consulta';
  document.getElementById('p_cur').textContent = '—';
  document.getElementById('p_tot').textContent = '—';
  document.getElementById('p_range').textContent = '—';
  document.getElementById('btnPrev').disabled = true;
  document.getElementById('btnNext').disabled = true;
}

function buildParams(baseAction){
  const p = new URLSearchParams();
  p.set('action', baseAction);

  const IdEmpresa = document.getElementById('IdEmpresa').value;
  const RutaId = document.getElementById('RutaId').value;
  const VendedorId = document.getElementById('VendedorId').value;
  const fecha_ini = document.getElementById('fecha_ini').value;
  const fecha_fin = document.getElementById('fecha_fin').value;
  const Cancelada = document.getElementById('Cancelada').value;
  const q = document.getElementById('q').value.trim();

  if(IdEmpresa) p.set('IdEmpresa', IdEmpresa);
  if(RutaId) p.set('RutaId', RutaId);
  if(VendedorId) p.set('VendedorId', VendedorId);
  if(fecha_ini) p.set('fecha_ini', fecha_ini);
  if(fecha_fin) p.set('fecha_fin', fecha_fin);
  if(Cancelada !== '') p.set('Cancelada', Cancelada);
  if(q) p.set('q', q);

  const lbl = document.getElementById('lblRango');
  lbl.textContent = (fecha_ini||fecha_fin) ? `Rango: ${fecha_ini||'—'} a ${fecha_fin||'—'}` : 'Rango: —';

  return p;
}

/* ========= Carga de filtros ========= */
function pick(o, keys){
  for(const k of keys){
    if(o && o[k] !== undefined && o[k] !== null && String(o[k]).trim() !== '') return o[k];
  }
  return null;
}

async function loadEmpresas(){
  const sel = document.getElementById('IdEmpresa');
  sel.innerHTML = `<option value="">(Seleccione)</option>`;
  MAP_EMP.clear();

  try{
    let url = API_ALMACENES;
    const r = await fetch(url);
    const j = await r.json();
    const rows = Array.isArray(j) ? j : (j.rows || j.data || j.almacenes || []);

    rows.forEach(x=>{
      const id = pick(x, ['clave','Clave','IdEmpresa','id','ID','cve_almacenp','cve_almacen','Id']) ?? '';
      const nombre = pick(x, ['nombre','Nombre','descripcion','Descripcion','razonsocial','RazonSocial','nom_almacen','almacen']) ?? id;
      if(String(id).trim()==='') return;
      MAP_EMP.set(String(id), String(nombre));
      const opt = document.createElement('option');
      opt.value = String(id);
      opt.textContent = String(nombre);
      sel.appendChild(opt);
    });
  }catch(e){
    console.error('loadEmpresas', e);
  }
}

async function loadVendedores(){
  const sel = document.getElementById('VendedorId');
  sel.innerHTML = `<option value="">(Seleccione)</option>`;
  MAP_VEN.clear();

  try{
    let url = API_USUARIOS;
    const r = await fetch(url);
    const j = await r.json();
    const rows = Array.isArray(j) ? j : (j.rows || j.data || j.usuarios || []);

    rows.forEach(x=>{
      const id = pick(x, ['Id_Vendedor','VendedorId','id','ID','cve_usuario','Cve_Usuario','IdUsuario','id_usuario']) ?? '';
      const nombre = pick(x, ['nombre_completo','NombreCompleto','nombre','Nombre','full_name','usuario']) ?? id;
      if(String(id).trim()==='') return;
      MAP_VEN.set(String(id), String(nombre));
      const opt = document.createElement('option');
      opt.value = String(id);
      opt.textContent = String(nombre);
      sel.appendChild(opt);
    });
  }catch(e){
    console.error('loadVendedores', e);
  }
}

async function loadRutasByEmpresa(){
  const emp = document.getElementById('IdEmpresa').value;

  const sel = document.getElementById('RutaId');
  sel.innerHTML = `<option value="">(Seleccione)</option>`;
  MAP_RUTA.clear();

  if(!emp){
    sel.disabled = true;
    return;
  }
  sel.disabled = false;

  try{
    const qs = new URLSearchParams();
    qs.set('IdEmpresa', emp);
    const url = API_RUTAS + '?' + qs.toString();

    const r = await fetch(url);
    const j = await r.json();
    const rows = Array.isArray(j) ? j : (j.rows || j.data || j.rutas || []);

    rows.forEach(x=>{
      const id = pick(x, ['ID_Ruta','RutaId','id_ruta','IdRuta','id','ID']) ?? '';
      const nombre = pick(x, ['Nombre','nombre','descripcion','Descripcion','ruta','nom_ruta']) ?? id;

      const empRow = pick(x, ['IdEmpresa','cve_almacenp','clave','cve_almac']) ?? null;
      if(empRow !== null && String(empRow) !== String(emp)) return;

      if(String(id).trim()==='') return;
      MAP_RUTA.set(String(id), String(nombre));
      const opt = document.createElement('option');
      opt.value = String(id);
      opt.textContent = String(nombre);
      sel.appendChild(opt);
    });
  }catch(e){
    console.error('loadRutasByEmpresa', e);
  }
}

/* ========= Consultas ========= */
function canSearch(){
  const emp = document.getElementById('IdEmpresa').value;
  const ruta = document.getElementById('RutaId').value;
  const ven = document.getElementById('VendedorId').value;
  const fi = document.getElementById('fecha_ini').value;
  const ff = document.getElementById('fecha_fin').value;
  const q = document.getElementById('q').value.trim();

  if(!emp) return false; // obligatoria
  if(ruta || ven || fi || ff || q) return true;
  return false;
}

function refreshCsvLinks(){
  document.getElementById('btnCsvLayout').href = API_VENTAS + '?action=export_csv&tipo=layout';
  const p = buildParams('export_csv');
  p.set('tipo','datos');
  document.getElementById('btnCsvDatos').href = API_VENTAS + '?' + p.toString();
}

async function loadKPIs(){
  const p = buildParams('kpis');
  const r = await fetch(API_VENTAS + '?' + p.toString());
  const j = await r.json();

  const k = (j && j.kpis) ? j.kpis : {};
  document.getElementById('k_ventas').textContent = (k.ventas||0).toLocaleString('es-MX');
  document.getElementById('k_total').textContent  = money(k.total||0);
  document.getElementById('k_ticket').textContent = money(k.ticket_promedio||0);
  document.getElementById('k_items').textContent  = Number(k.items||0).toLocaleString('es-MX');

  // NUEVOS
  document.getElementById('k_credito').textContent = money(k.total_credito||0);
  document.getElementById('k_contado').textContent = money(k.total_contado||0);
}

async function loadGrid(){
  const p = buildParams('list');
  p.set('page', String(page));
  p.set('pageSize', String(pageSize));

  const r = await fetch(API_VENTAS + '?' + p.toString());
  const j = await r.json();

  total = j.total || 0;
  pages = j.pages || 1;

  document.getElementById('p_cur').textContent = j.page || 1;
  document.getElementById('p_tot').textContent = pages;

  const start = total ? ((page-1)*pageSize + 1) : 0;
  const end = Math.min(page*pageSize, total);
  document.getElementById('p_range').textContent = `${start}-${end}`;
  document.getElementById('lblTotal').textContent = `${total.toLocaleString('es-MX')} registros`;

  document.getElementById('btnPrev').disabled = page <= 1;
  document.getElementById('btnNext').disabled = page >= pages;

  const tb = document.getElementById('tb');
  tb.innerHTML = '';

  const empSel = document.getElementById('IdEmpresa').value;
  const empName = MAP_EMP.get(String(empSel)) || empSel;

  (j.rows || []).forEach(x=>{
    const tr = document.createElement('tr');
    const rutaName = MAP_RUTA.get(String(x.RutaId ?? '')) || (x.RutaId ?? '');
    const venName = MAP_VEN.get(String(x.VendedorId ?? '')) || (x.VendedorId ?? '');

    tr.innerHTML = `
      <td style="white-space:nowrap">
        <button class="ap-btn2" title="Ver detalle" onclick="verDetalle(${x.Id})">
          <i class="fa-solid fa-eye"></i>
        </button>
      </td>
      <td>${x.Id ?? ''}</td>
      <td style="white-space:nowrap">${fmtDate(x.Fecha)}</td>
      <td>${x.Documento ?? ''}</td>
      <td>${(x.ClienteNombre ?? x.CodCliente ?? '')}</td>
      <td>${empName}</td>
      <td>${rutaName}</td>
      <td>${venName}</td>
      <td>${x.TipoVta ?? ''}</td>
      <td style="text-align:right">${money(x.SubTotal)}</td>
      <td style="text-align:right">${money(x.IVA)}</td>
      <td style="text-align:right">${money(x.IEPS)}</td>
      <td style="text-align:right"><b>${money(x.TOTAL)}</b></td>
      <td style="text-align:right">${Number(x.Items||0).toLocaleString('es-MX')}</td>
      <td>${(Number(x.Cancelada||0)===1) ? '<span class="ap-badge">Sí</span>' : '<span class="ap-badge">No</span>'}</td>
    `;
    tb.appendChild(tr);
  });

  if((j.rows || []).length === 0){
    setGridEmpty('Sin resultados con los filtros seleccionados.');
  }
}

async function verDetalle(id){
  const r = await fetch(API_VENTAS + '?action=detalle&Id=' + encodeURIComponent(id));
  const j = await r.json();
  if(j.error){ alert(j.error); return; }

  const h = j.head || {};
  const det = j.detail || [];
  const lt = j.line_totals || {};

  const empName = MAP_EMP.get(String(h.IdEmpresa ?? '')) || (h.IdEmpresa ?? '—');
  const rutaName = MAP_RUTA.get(String(h.RutaId ?? '')) || (h.RutaId ?? '—');
  const venName = MAP_VEN.get(String(h.VendedorId ?? '')) || (h.VendedorId ?? '—');

  document.getElementById('d_doc').textContent   = 'Documento: ' + (h.Documento || '—');
  document.getElementById('d_cli').textContent   = 'Cliente: ' + (h.ClienteNombre || h.CodCliente || '—');
  document.getElementById('d_emp').textContent   = 'Empresa: ' + empName;
  document.getElementById('d_ruta').textContent  = 'Ruta: ' + rutaName;
  document.getElementById('d_ven').textContent   = 'Vendedor: ' + venName;
  document.getElementById('d_fecha').textContent = 'Fecha: ' + (h.Fecha ? fmtDate(h.Fecha) : '—');
  document.getElementById('d_total').textContent = 'Total: ' + money(h.TOTAL || 0);

  document.getElementById('d_partidas').textContent   = 'Partidas: ' + (lt.partidas || det.length || 0);
  document.getElementById('d_total_calc').textContent = 'Total calculado: ' + money(lt.total_calculado || 0);

  const tb = document.getElementById('dtb');
  tb.innerHTML = '';

  det.forEach(x=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${x.Articulo ?? ''}</td>
      <td>${x.Descripcion ?? ''}</td>
      <td style="text-align:right">${money(x.Precio)}</td>
      <td style="text-align:right">${Number(x.Pza||0).toLocaleString('es-MX')}</td>
      <td style="text-align:right">${Number(x.Kg||0).toLocaleString('es-MX')}</td>
      <td style="text-align:right">${money(x.DescMon)}</td>
      <td style="text-align:right">${money(x.Importe)}</td>
      <td style="text-align:right">${money(x.IVA)}</td>
      <td style="text-align:right">${money(x.IEPS)}</td>
      <td style="text-align:right">${money(x.Utilidad)}</td>
    `;
    tb.appendChild(tr);
  });

  const mdl = new bootstrap.Modal(document.getElementById('mdlDetalle'));
  mdl.show();
}

async function run(){
  if(!canSearch()){
    setKpiEmpty();
    disableCsv(true);
    setGridEmpty('Seleccione al menos <b>Empresa</b> y otro filtro (Ruta/Vendedor/Fechas/Búsqueda) y presione <b>Buscar</b>.');
    return;
  }

  disableCsv(false);
  refreshCsvLinks();

  showWait(true);
  try{
    await loadKPIs();
    await loadGrid();
  }finally{
    showWait(false);
  }
}

/* ========= Eventos ========= */
document.getElementById('btnBuscar').addEventListener('click', ()=>{ page=1; run(); });

document.getElementById('btnLimpiar').addEventListener('click', ()=>{
  document.getElementById('IdEmpresa').value='';
  document.getElementById('RutaId').innerHTML = `<option value="">(Seleccione)</option>`;
  document.getElementById('RutaId').disabled = true;
  document.getElementById('VendedorId').value='';
  document.getElementById('fecha_ini').value='';
  document.getElementById('fecha_fin').value='';
  document.getElementById('Cancelada').value='';
  document.getElementById('q').value='';
  page=1;
  run();
});

document.getElementById('btnPrev').addEventListener('click', ()=>{ if(page>1){ page--; run(); }});
document.getElementById('btnNext').addEventListener('click', ()=>{ if(page<pages){ page++; run(); }});

document.getElementById('q').addEventListener('keydown', (e)=>{
  if(e.key==='Enter'){ page=1; run(); }
});

// Cascada: Empresa -> Rutas
document.getElementById('IdEmpresa').addEventListener('change', async ()=>{
  await loadRutasByEmpresa();
  run(); // mantiene política: sin filtros suficientes no consulta
});

/* ========= Init ========= */
(async function init(){
  setKpiEmpty();
  disableCsv(true);
  setGridEmpty('Seleccione filtros y presione <b>Buscar</b>.');

  await loadEmpresas();
  await loadVendedores();
  // rutas se cargan cuando se seleccione empresa
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
