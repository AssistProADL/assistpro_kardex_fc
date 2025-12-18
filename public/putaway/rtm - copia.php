<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>RTM - Ready To Move</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root{ --ap-blue:#0b5ed7; --ap-blue2:#084298; }
    body{ background:#f6f8fb; }
    .ap-title{ color:var(--ap-blue2); font-weight:800; letter-spacing:.2px; }
    .ap-sub{ color:#5b6b7a; font-size:12px; }
    .ap-card{ border:1px solid #e6eef7; border-radius:14px; box-shadow:0 6px 18px rgba(18,38,63,.06); }
    .ap-table-wrap{ max-height: 62vh; overflow:auto; }
    table.ap-table{ font-size:10px; white-space:nowrap; }
    table.ap-table th{ position:sticky; top:0; background:#f1f5ff; z-index:2; }
    .ap-actions .btn{ padding:2px 8px; font-size:10px; }
    .ap-badge{ font-size:10px; }
    .ap-progress{ height:14px; }
    .ap-progress .progress-bar{ font-size:10px; }
    .ap-spinner{ display:none; }
    .ap-muted{ color:#6c7a89; }
  </style>
</head>
<body class="p-3">

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h2 class="ap-title m-0"><i class="fa-solid fa-dolly me-2"></i>RTM - Ready To Move</h2>
      <div class="ap-sub">Dashboard operativo de ingresos pendientes de acomodo (avance por folio).</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <div class="ap-spinner" id="spinner">
        <span class="spinner-border spinner-border-sm text-primary"></span>
        <span class="ap-muted">Cargando…</span>
      </div>
      <button class="btn btn-primary btn-sm" id="btnRefresh"><i class="fa-solid fa-rotate"></i> Actualizar</button>
    </div>
  </div>

  <div class="ap-card p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label mb-1">Almacén</label>
        <select class="form-select form-select-sm" id="selAlmac"></select>
      </div>
      <div class="col-md-4">
        <label class="form-label mb-1">Buscar</label>
        <input class="form-control form-control-sm" id="txtSearch" placeholder="Folio / Proveedor / Protocolo / Tipo">
      </div>
      <div class="col-md-4 text-end">
        <div class="small ap-muted">Registros: <span id="lblTotal">0</span></div>
        <div class="small ap-muted">Página: <span id="lblPage">1</span></div>
      </div>
    </div>
  </div>

  <div class="ap-card p-2">
    <div class="ap-table-wrap">
      <table class="table table-sm table-bordered table-hover align-middle text-center ap-table mb-0" id="tbl">
        <thead>
          <tr>
            <th style="min-width:90px;">Acciones</th>
            <th>Folio</th>
            <th>Tipo</th>
            <th>Almacén</th>
            <th>Fecha Entrada</th>
            <th>Proveedor</th>
            <th>Protocolo</th>
            <th>Partidas</th>
            <th>Recibido</th>
            <th>Acomodado</th>
            <th>Pendiente</th>
            <th style="min-width:160px;">Avance</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center p-2">
      <div class="small ap-muted">Máx. 25 renglones por página.</div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btnPrev"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="btn btn-outline-secondary btn-sm" id="btnNext"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Modal detalle -->
<div class="modal fade" id="mdlDetail" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h6 class="modal-title m-0"><i class="fa-solid fa-list-check me-2"></i>Detalle de folio <span id="dFolio"></span></h6>
          <div class="small ap-muted">Partidas con pendiente y trazabilidad de ubicación.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-hover align-middle text-center ap-table" id="tblDetail">
            <thead>
              <tr>
                <th>ID</th>
                <th>Artículo</th>
                <th>Lote</th>
                <th>Pedida</th>
                <th>Recibida</th>
                <th>Ubicada</th>
                <th>Pendiente</th>
                <th>Ubicación</th>
                <th>Inicio</th>
                <th>Fin</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API = '../api/rtm.php';
let page = 1, limit = 25, total = 0;

const $ = (id)=>document.getElementById(id);
const spinner = (on)=>{ $('spinner').style.display = on ? 'inline-flex' : 'none'; };

function fmt(n){
  if(n===null||n===undefined) return '';
  const x = Number(n);
  if (Number.isNaN(x)) return n;
  return x.toLocaleString('es-MX',{minimumFractionDigits:0, maximumFractionDigits:4});
}

function progressBar(p){
  const val = Math.max(0, Math.min(100, Number(p||0)));
  const cls = val>=90?'bg-success':(val>=60?'bg-primary':(val>=30?'bg-warning':'bg-danger'));
  return `
    <div class="progress ap-progress">
      <div class="progress-bar ${cls}" role="progressbar" style="width:${val}%">${val.toFixed(2)}%</div>
    </div>`;
}

async function loadAlmacenes(){
  spinner(true);
  const r = await fetch(API+'?action=almacenes');
  const j = await r.json();
  const sel = $('selAlmac');
  sel.innerHTML = '';
  (j.data||[]).forEach(a=>{
    const opt = document.createElement('option');
    opt.value = a.clave;
    opt.textContent = `${a.clave} - ${a.nombre}`;
    sel.appendChild(opt);
  });
  spinner(false);
}

async function loadRTM(){
  const almac = $('selAlmac').value || '';
  if(!almac) return;

  spinner(true);
  const url = `${API}?action=list&almac=${encodeURIComponent(almac)}&page=${page}&limit=${limit}`;
  const r = await fetch(url);
  const j = await r.json();
  spinner(false);

  if(!j.ok){
    alert(j.msg || 'Error');
    return;
  }
  total = j.total || 0;
  $('lblTotal').textContent = total;
  $('lblPage').textContent = page;

  const q = ($('txtSearch').value || '').toLowerCase().trim();
  const rows = (j.data||[]).filter(x=>{
    if(!q) return true;
    return (String(x.folio||'')+' '+String(x.proveedor||'')+' '+String(x.protocolo||'')+' '+String(x.tipo||'')).toLowerCase().includes(q);
  });

  const tb = $('tbl').querySelector('tbody');
  tb.innerHTML = rows.map(x=>`
    <tr>
      <td class="ap-actions">
        <button class="btn btn-outline-primary btn-sm" onclick="openDetail(${x.folio})">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </td>
      <td><span class="badge text-bg-primary ap-badge">${x.folio}</span></td>
      <td>${x.tipo||''}</td>
      <td>${x.cve_almac||''}</td>
      <td>${x.fecha_entrada||''}</td>
      <td>${x.proveedor||''}</td>
      <td>${(x.protocolo||'')}${x.consec_protocolo?('-'+x.consec_protocolo):''}</td>
      <td>${fmt(x.partidas)}</td>
      <td>${fmt(x.total_recibido)}</td>
      <td>${fmt(x.total_acomodado)}</td>
      <td><span class="badge text-bg-warning ap-badge">${fmt(x.pendiente)}</span></td>
      <td>${progressBar(x.avance)}</td>
    </tr>
  `).join('');
}

async function openDetail(folio){
  $('dFolio').textContent = folio;
  const r = await fetch(`${API}?action=detail&folio=${folio}`);
  const j = await r.json();
  if(!j.ok){ alert(j.msg||'Error'); return; }

  const tb = $('tblDetail').querySelector('tbody');
  tb.innerHTML = (j.data||[]).map(x=>`
    <tr>
      <td>${x.id}</td>
      <td>${x.cve_articulo||''}</td>
      <td>${x.cve_lote||''}</td>
      <td>${fmt(x.CantidadPedida)}</td>
      <td>${fmt(x.CantidadRecibida)}</td>
      <td>${fmt(x.CantidadUbicada)}</td>
      <td><span class="badge text-bg-warning ap-badge">${fmt(x.pendiente)}</span></td>
      <td>${x.cve_ubicacion||''}</td>
      <td>${x.fecha_inicio||''}</td>
      <td>${x.fecha_fin||''}</td>
    </tr>
  `).join('');

  new bootstrap.Modal(document.getElementById('mdlDetail')).show();
}

$('btnRefresh').addEventListener('click', ()=>{ page=1; loadRTM(); });
$('txtSearch').addEventListener('input', ()=>loadRTM());
$('selAlmac').addEventListener('change', ()=>{ page=1; loadRTM(); });
$('btnPrev').addEventListener('click', ()=>{ if(page>1){ page--; loadRTM(); }});
$('btnNext').addEventListener('click', ()=>{ if((page*limit)<total){ page++; loadRTM(); }});

(async function init(){
  await loadAlmacenes();
  loadRTM();
})();
</script>

</body>
</html>
