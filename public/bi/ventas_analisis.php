<?php require_once __DIR__ . '/_menu_global.php'; ?>

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
</style>

<div class="ap-container">
  <div class="ap-title">
    <i class="fa-solid fa-chart-line"></i>
    Análisis de Ventas (API)
    <span class="ap-right ap-badge" id="lblRango">Rango: —</span>
  </div>
  <div class="ap-sub">
    Consolida KPIs y transacciones desde <b>Venta</b>. Filtros ejecutivos + exportación CSV.
  </div>

  <!-- KPI Cards -->
  <div class="ap-cards">
    <div class="ap-card">
      <div class="h"><b>Ventas</b><i class="fa-solid fa-receipt"></i></div>
      <div class="ap-kpi" id="k_ventas">0</div>
      <div class="ap-muted">Transacciones filtradas</div>
    </div>
    <div class="ap-card">
      <div class="h"><b>Total</b><i class="fa-solid fa-dollar-sign"></i></div>
      <div class="ap-kpi" id="k_total">$0.00</div>
      <div class="ap-muted">Suma TOTAL</div>
    </div>
    <div class="ap-card">
      <div class="h"><b>Ticket Prom.</b><i class="fa-solid fa-chart-column"></i></div>
      <div class="ap-kpi" id="k_ticket">$0.00</div>
      <div class="ap-muted">AVG(TOTAL)</div>
    </div>
    <div class="ap-card">
      <div class="h"><b>Items</b><i class="fa-solid fa-cubes"></i></div>
      <div class="ap-kpi" id="k_items">0</div>
      <div class="ap-muted">Suma Items</div>
    </div>
  </div>

  <!-- Filters -->
  <div class="ap-filters">
    <div class="row g-2 align-items-end">

      <div class="col-12 col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" class="form-control form-control-sm" id="fecha_ini">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" class="form-control form-control-sm" id="fecha_fin">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">Empresa (IdEmpresa)</label>
        <input type="text" class="form-control form-control-sm" id="IdEmpresa" placeholder="Ej: CLAVE">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">RutaId</label>
        <input type="number" class="form-control form-control-sm" id="RutaId" placeholder="0">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">VendedorId</label>
        <input type="number" class="form-control form-control-sm" id="VendedorId" placeholder="0">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">Cancelada</label>
        <select class="form-select form-select-sm" id="Cancelada">
          <option value="">(Todas)</option>
          <option value="0">No</option>
          <option value="1">Sí</option>
        </select>
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label">Búsqueda</label>
        <input type="text" class="form-control form-control-sm" id="q"
               placeholder="Documento, cliente, doc salida, tipo vta...">
      </div>

      <div class="col-12 col-md-8 d-flex gap-2">
        <button class="ap-btn" id="btnBuscar">
          <i class="fa-solid fa-magnifying-glass"></i> Buscar
        </button>
        <button class="ap-btn2" id="btnLimpiar">
          <i class="fa-solid fa-broom"></i> Limpiar
        </button>

        <div class="ms-auto d-flex gap-2">
          <a class="ap-btn2" id="btnCsvLayout" href="#">
            <i class="fa-solid fa-file-csv"></i> CSV Layout
          </a>
          <a class="ap-btn2" id="btnCsvDatos" href="#">
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
      <span class="ap-muted" id="lblTotal">0 registros</span>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
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
        <tbody id="tb"></tbody>
      </table>
    </div>

    <!-- Pager -->
    <div class="ap-pager">
      <div class="ap-muted">
        Página <b id="p_cur">1</b> de <b id="p_tot">1</b> |
        Mostrando <b id="p_range">0-0</b>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <button class="ap-btn2" id="btnPrev">
          <i class="fa-solid fa-chevron-left"></i> Anterior
        </button>
        <button class="ap-btn2" id="btnNext">
          Siguiente <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * API vive en /public/api/ventas_analisis.php
 * Esta pantalla vive en /public/bi/ventas_analisis.php
 * => ruta relativa correcta:
 */
const API = '../api/ventas_analisis.php';

let page = 1;
const pageSize = 25;
let total = 0;
let pages = 1;

function money(n){
  n = Number(n||0);
  return n.toLocaleString('es-MX', {style:'currency', currency:'MXN'});
}
function fmtDate(dt){
  if(!dt) return '';
  return String(dt).replace('T',' ').substring(0,19);
}

function buildParams(baseAction){
  const p = new URLSearchParams();
  p.set('action', baseAction);

  // filtros
  const fecha_ini = document.getElementById('fecha_ini').value;
  const fecha_fin = document.getElementById('fecha_fin').value;
  const IdEmpresa = document.getElementById('IdEmpresa').value.trim();
  const RutaId = document.getElementById('RutaId').value.trim();
  const VendedorId = document.getElementById('VendedorId').value.trim();
  const Cancelada = document.getElementById('Cancelada').value;
  const q = document.getElementById('q').value.trim();

  if(fecha_ini) p.set('fecha_ini', fecha_ini);
  if(fecha_fin) p.set('fecha_fin', fecha_fin);
  if(IdEmpresa) p.set('IdEmpresa', IdEmpresa);
  if(RutaId) p.set('RutaId', RutaId);
  if(VendedorId) p.set('VendedorId', VendedorId);
  if(Cancelada !== '') p.set('Cancelada', Cancelada);
  if(q) p.set('q', q);

  // etiqueta rango
  const lbl = document.getElementById('lblRango');
  lbl.textContent = (fecha_ini||fecha_fin) ? `Rango: ${fecha_ini||'—'} a ${fecha_fin||'—'}` : 'Rango: —';

  return p;
}

function refreshCsvLinks(){
  // layout fijo
  document.getElementById('btnCsvLayout').href = API + '?action=export_csv&tipo=layout';

  // datos con mismos filtros
  const p = buildParams('export_csv');
  p.set('tipo','datos');
  document.getElementById('btnCsvDatos').href = API + '?' + p.toString();
}

async function loadKPIs(){
  const p = buildParams('kpis');
  const r = await fetch(API + '?' + p.toString());
  const j = await r.json();

  const k = (j && j.kpis) ? j.kpis : {};
  document.getElementById('k_ventas').textContent = (k.ventas||0);
  document.getElementById('k_total').textContent  = money(k.total||0);
  document.getElementById('k_ticket').textContent = money(k.ticket_promedio||0);
  document.getElementById('k_items').textContent  = Number(k.items||0).toLocaleString('es-MX');
}

async function loadGrid(){
  const p = buildParams('list');
  p.set('page', String(page));
  p.set('pageSize', String(pageSize));

  const r = await fetch(API + '?' + p.toString());
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

  (j.rows || []).forEach(x=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${x.Id ?? ''}</td>
      <td style="white-space:nowrap">${fmtDate(x.Fecha)}</td>
      <td>${x.Documento ?? ''}</td>
      <td>${x.CodCliente ?? ''}</td>
      <td>${x.IdEmpresa ?? ''}</td>
      <td>${x.RutaId ?? ''}</td>
      <td>${x.VendedorId ?? ''}</td>
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
}

async function run(){
  refreshCsvLinks();
  await loadKPIs();
  await loadGrid();
}

// UX: Enter en búsqueda
document.getElementById('q').addEventListener('keydown', (e)=>{
  if(e.key==='Enter'){ page=1; run(); }
});

document.getElementById('btnBuscar').addEventListener('click', ()=>{ page=1; run(); });

document.getElementById('btnLimpiar').addEventListener('click', ()=>{
  document.getElementById('fecha_ini').value='';
  document.getElementById('fecha_fin').value='';
  document.getElementById('IdEmpresa').value='';
  document.getElementById('RutaId').value='';
  document.getElementById('VendedorId').value='';
  document.getElementById('Cancelada').value='';
  document.getElementById('q').value='';
  page=1; run();
});

document.getElementById('btnPrev').addEventListener('click', ()=>{ if(page>1){ page--; run(); }});
document.getElementById('btnNext').addEventListener('click', ()=>{ if(page<pages){ page++; run(); }});

run();
</script>

<?php require_once __DIR__ . '/_menu_global_end.php'; ?>
