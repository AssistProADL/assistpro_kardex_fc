<?php
// /public/manufactura/monitor_produccion.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// =======================
// Catálogos (FILTROS)
// =======================

// Empresa = c_proveedores (solo clientes)
$empresas = db_all("
  SELECT ID_Proveedor AS id, Nombre AS nombre
  FROM c_proveedores
  WHERE es_cliente = 1
    AND (Activo = 1 OR Activo IS NULL)
  ORDER BY Nombre
");

// Almacén y Zona: por ahora desde OT (rápido, sin depender de catálogos)
$almacenes = db_all("
  SELECT DISTINCT cve_almac AS cve
  FROM t_ordenprod
  WHERE cve_almac IS NOT NULL AND cve_almac <> ''
  ORDER BY cve_almac
");

$zonas = db_all("
  SELECT DISTINCT id_zona_almac AS id
  FROM t_ordenprod
  WHERE id_zona_almac IS NOT NULL
  ORDER BY id_zona_almac
");

$hoy = date('Y-m-d');
$desde_default = date('Y-m-d', strtotime('-7 days'));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Monitor de Producción | AssistPro</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
  <style>
    body{ background:#f6f8fb; }
    .ap-title{ font-weight:700; letter-spacing:.2px; }
    .ap-subtitle{ font-size:12px; color:#667085; margin-top:-2px; }
    .ap-card{ border:0; border-radius:14px; box-shadow:0 6px 18px rgba(16,24,40,.08); }
    .kpi{ font-size:22px; font-weight:800; }
    .kpi-sub{ font-size:12px; color:#667085; }
    table.dataTable tbody td{ font-size:10px; white-space:nowrap; }
    table.dataTable thead th{ font-size:11px; }
    .btn-xs{ padding:.15rem .35rem; font-size:.75rem; }
    .badge-status{ font-size:11px; padding:.35rem .5rem; border-radius:999px; }
    .nowrap{ white-space:nowrap; }
  </style>
</head>
<body>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="ap-title"><i class="bi bi-broadcast"></i> Monitor de Producción</div>
      <div class="ap-subtitle">Visibilidad multi-almacén en tiempo casi real (solo lectura).</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <button class="btn btn-outline-secondary btn-sm" id="btnRefresh"><i class="bi bi-arrow-clockwise"></i> Refrescar</button>
      <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
        <label class="form-check-label small" for="autoRefresh">Auto (20s)</label>
      </div>
      <button class="btn btn-primary btn-sm" id="btnProducir"><i class="bi bi-play-fill"></i> Iniciar Producción</button>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card ap-card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-0">Empresa</label>
          <select id="fEmpresa" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach($empresas as $e): ?>
              <option value="<?=h($e['id'])?>"><?=h($e['nombre'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Almacén</label>
          <select id="fAlmacen" class="form-select form-select-sm">
            <option value="">Todos</option>
            <?php foreach($almacenes as $a): ?>
              <option value="<?=h($a['cve'])?>"><?=h($a['cve'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Zona</label>
          <select id="fZona" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach($zonas as $z): ?>
              <option value="<?=h($z['id'])?>">Zona <?=h($z['id'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Status</label>
          <select id="fStatus" class="form-select form-select-sm">
            <!-- default Planeada -->
            <option value="P" selected>Planeada</option>
            <option value="E">En proceso</option>
            <option value="T">Terminada</option>
            <option value="C">Cancelada</option>
            <option value="">Todos</option>
          </select>
        </div>

        <div class="col-md-1">
          <label class="form-label mb-0">Desde</label>
          <input type="date" id="fDesde" class="form-control form-control-sm" value="<?=h($desde_default)?>">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Hasta</label>
          <input type="date" id="fHasta" class="form-control form-control-sm" value="<?=h($hoy)?>">
        </div>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-2"><div class="card ap-card"><div class="card-body">
      <div class="kpi" id="kTotal">0</div><div class="kpi-sub">OTs (total)</div>
    </div></div></div>
    <div class="col-md-2"><div class="card ap-card"><div class="card-body">
      <div class="kpi" id="kPlan">0</div><div class="kpi-sub">Planeadas</div>
    </div></div></div>
    <div class="col-md-2"><div class="card ap-card"><div class="card-body">
      <div class="kpi" id="kProc">0</div><div class="kpi-sub">En proceso</div>
    </div></div></div>
    <div class="col-md-2"><div class="card ap-card"><div class="card-body">
      <div class="kpi" id="kTerm">0</div><div class="kpi-sub">Terminadas</div>
    </div></div></div>
    <div class="col-md-2"><div class="card ap-card"><div class="card-body">
      <div class="kpi" id="kCanc">0</div><div class="kpi-sub">Canceladas</div>
    </div></div></div>
    <div class="col-md-2"><div class="card ap-card"><div class="card-body">
      <div class="kpi" id="kUpd">--:--</div><div class="kpi-sub">Última actualización</div>
    </div></div></div>
  </div>

  <!-- Tabla -->
  <div class="card ap-card">
    <div class="card-body">
      <table id="tblMon" class="display compact nowrap" style="width:100%">
        <thead>
          <tr>
            <th class="nowrap">Sel</th>
            <th>Acciones</th>
            <th>Zona</th>
            <th>BL Origen</th>
            <th>Clave</th>
            <th>Descripción</th>
            <th>Lote</th>
            <th>Caducidad</th>
            <th class="text-end">Cantidad</th>
            <th>BL Destino</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Avance</th>
          </tr>
        </thead>
      </table>

      <div class="text-muted mt-2" style="font-size:12px;">
        Nota: el monitor es 100% lectura; no afecta existencias ni procesos de producción.
      </div>
    </div>
  </div>
</div>

<!-- Modal detalle -->
<div class="modal fade" id="mdlDet" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <div class="fw-bold">Detalle OT</div>
          <div class="text-muted" id="detFolio" style="font-size:12px;">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="detLoading" class="text-center py-4">
          <div class="spinner-border" role="status"></div>
          <div class="text-muted mt-2">Cargando detalle...</div>
        </div>

        <div id="detBody" style="display:none;">
          <div class="row g-2 mb-3">
            <div class="col-md-2"><div class="small text-muted">Producto</div><div class="fw-semibold" id="hProd">—</div></div>
            <div class="col-md-2"><div class="small text-muted">Cantidad</div><div class="fw-semibold" id="hCant">—</div></div>
            <div class="col-md-2"><div class="small text-muted">Almacén</div><div class="fw-semibold" id="hAlm">—</div></div>
            <div class="col-md-2"><div class="small text-muted">Zona</div><div class="fw-semibold" id="hZona">—</div></div>
            <div class="col-md-2"><div class="small text-muted">Status</div><div class="fw-semibold" id="hSta">—</div></div>
            <div class="col-md-2"><div class="small text-muted">Fecha</div><div class="fw-semibold" id="hFec">—</div></div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Componente</th>
                  <th class="text-end">Cantidad</th>
                  <th>Referencia</th>
                </tr>
              </thead>
              <tbody id="detLines"></tbody>
            </table>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
const API = "../api/monitor_produccion_api.php";

let tabla = null;
let autoTimer = null;

function filtros(){
  return {
    empresa: $("#fEmpresa").val(),
    almacen: $("#fAlmacen").val(),
    zona: $("#fZona").val(),
    status: ($("#fStatus").val() || "P"), // default Planeada
    desde: $("#fDesde").val(),
    hasta: $("#fHasta").val()
  };
}

function fmtDT(s){
  if(!s) return '';
  const d = new Date(String(s).replace(' ', 'T'));
  if(isNaN(d.getTime())) return String(s);
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yy = d.getFullYear();
  const hh = String(d.getHours()).padStart(2,'0');
  const mi = String(d.getMinutes()).padStart(2,'0');
  const ss = String(d.getSeconds()).padStart(2,'0');
  return `${dd}/${mm}/${yy}, ${hh}:${mi}:${ss}`;
}

function badgeStatus(s){
  const map = {
    'P': ['Planeada','bg-warning text-dark'],
    'E': ['En proceso','bg-info text-dark'],
    'T': ['Terminada','bg-success'],
    'C': ['Cancelada','bg-secondary']
  };
  const x = map[s] || [s||'—','bg-light text-dark'];
  return `<span class="badge badge-status ${x[1]}">${x[0]}</span>`;
}

function barAvance(p){
  const n = Math.max(0, Math.min(100, parseInt(p||0,10)));
  const cls = (n>=100) ? 'bg-success' : (n>0 ? 'bg-info' : 'bg-secondary');
  return `
    <div class="progress" style="height:14px; min-width:120px">
      <div class="progress-bar ${cls}" role="progressbar" style="width:${n}%">
        ${n}%
      </div>
    </div>
  `;
}

function loadKPIs(){
  $.getJSON(API, {...filtros(), action:'stats'})
    .done(r=>{
      if(!r || !r.kpi) return;
      $("#kTotal").text(r.kpi.total ?? 0);
      $("#kPlan").text(r.kpi.planeadas ?? 0);
      $("#kProc").text(r.kpi.en_proceso ?? 0);
      $("#kTerm").text(r.kpi.terminadas ?? 0);
      $("#kCanc").text(r.kpi.canceladas ?? 0);
      const now = new Date();
      const hh = now.getHours()%12 || 12;
      const mm = String(now.getMinutes()).padStart(2,'0');
      $("#kUpd").text(`${hh}:${mm} ${now.getHours()>=12?'p.m.':'a.m.'}`);
    });
}

function refreshAll(){
  if(tabla) tabla.ajax.reload(null,false);
  loadKPIs();
}

function setAuto(on){
  if(autoTimer){ clearInterval(autoTimer); autoTimer=null; }
  if(on){
    autoTimer = setInterval(()=>refreshAll(), 20000);
  }
}

$(function(){

  tabla = $("#tblMon").DataTable({
    processing:true,
    serverSide:true,
    pageLength:25,
    scrollX:true,
    ajax:{
      url: API,
      data: function(d){
        return {...d, ...filtros()};
      }
    },
    order:[[10,'desc']],
    columns:[
      {
        data:null, orderable:false, searchable:false,
        render: row => `<input type="checkbox" class="form-check-input otPick" value="${row.folio}">`
      },
      {
        data:null, orderable:false, searchable:false,
        render: row => `
          <button class="btn btn-outline-primary btn-xs btnVer" data-folio="${row.folio}">
            <i class="bi bi-eye"></i>
          </button>
        `
      },
      {data:'zona'},
      {data:'bl_origen'},
      {data:'clave'},
      {data:'descripcion'},
      {data:'lote'},
      {data:'caducidad'},
      {data:'cantidad', className:'text-end', render:d=> (d==null||d==='')?'' : Number(d).toFixed(4)},
      {data:'bl_destino'},
      {data:'hora_ini', render:s=>fmtDT(s)},
      {data:'hora_fin', render:s=>fmtDT(s)},
      {data:'avance', orderable:false, searchable:false, render:p=>barAvance(p)}
    ]
  });

  $("#fEmpresa,#fAlmacen,#fZona,#fStatus,#fDesde,#fHasta").on("change", function(){
    refreshAll();
  });

  $("#btnRefresh").on("click", ()=>refreshAll());

  setAuto($("#autoRefresh").is(":checked"));
  $("#autoRefresh").on("change", function(){ setAuto(this.checked); });

  // ver detalle
  const mdl = new bootstrap.Modal(document.getElementById('mdlDet'));
  $(document).on("click", ".btnVer", function(){
    const folio = $(this).data("folio");
    $("#detFolio").text(folio);
    $("#detLoading").show();
    $("#detBody").hide();
    $("#detLines").empty();
    mdl.show();

    $.getJSON(API, {action:'detalle', folio})
      .done(r=>{
        if(!r || !r.ok){
          $("#detLoading").hide();
          $("#detBody").show();
          $("#detLines").html(`<tr><td colspan="4" class="text-danger">${(r && r.msg) ? r.msg : 'No se pudo cargar detalle'}</td></tr>`);
          return;
        }

        const h = r.header || {};
        // Header tolerante (el API puede regresar distintos alias)
        const prod = h.producto ?? h.clave ?? h.Cve_Articulo ?? '—';
        const cant = (h.cantidad ?? h.Cantidad ?? '—');
        const alm  = h.bl_origen ?? h.almacen ?? h.BL_Origen ?? '—';
        const zn   = h.zona ?? h.Zona ?? '—';
        const sta  = h.status ?? h.Status ?? '';
        const fec  = h.fecha_reg ?? h.FechaReg ?? h.fecha ?? h.Fecha ?? '';

        $("#hProd").text(prod);
        $("#hCant").text((cant==='—'||cant===null||cant==='') ? '—' : Number(cant).toFixed(4));
        $("#hAlm").text(alm);
        $("#hZona").text(zn);
        $("#hSta").html(badgeStatus(sta));
        $("#hFec").text(fmtDT(fec));

        const lines = r.lines || [];
        if(!lines.length){
          $("#detLines").html(`<tr><td colspan="4" class="text-muted">Sin componentes.</td></tr>`);
        } else {
          let html='';
          lines.forEach((ln,i)=>{
            // Lines tolerantes: soporta salida legacy (Cve_Articulo/Cantidad/Referencia)
            // y salida receta (componente/descripcion/cantidad/referencia)
            const comp = ln.componente ?? ln.Cve_Articulo ?? ln.Componente ?? '';
            const qty  = ln.cantidad ?? ln.Cantidad ?? 0;
            const ref  = ln.referencia ?? ln.Referencia ?? ln.descripcion ?? ln.Descripcion ?? '';
            html += `<tr>
              <td>${i+1}</td>
              <td>${comp}</td>
              <td class="text-end">${(qty===null||qty==='') ? '' : Number(qty).toFixed(4)}</td>
              <td>${ref}</td>
            </tr>`;
          });
          $("#detLines").html(html);
        }

        $("#detLoading").hide();
        $("#detBody").show();
      })
      .fail(()=>{
        $("#detLoading").hide();
        $("#detBody").show();
        $("#detLines").html(`<tr><td colspan="4" class="text-danger">Error consultando detalle.</td></tr>`);
      });
  });

  // iniciar producción (batch por folios)
  $("#btnProducir").on("click", function(){
    const folios = $(".otPick:checked").map((_,e)=>e.value).get();
    if(!folios.length){
      alert("Selecciona al menos una OT para iniciar producción.");
      return;
    }
    window.location.href = "iniciar_produccion.php?folios=" + encodeURIComponent(folios.join(","));
  });

  // init
  loadKPIs();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
