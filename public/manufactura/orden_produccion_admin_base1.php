<?php
// public/manufactura/orden_produccion_admin_base.php
// Admin OTs Manufactura - AssistPro (Adventech)
// Requiere: _menu_global.php y _menu_global_end.php (si existen). Igual se blinda con includes propios.

if (file_exists(__DIR__ . '/../bi/_menu_global.php')) {
  include __DIR__ . '/../bi/_menu_global.php';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administración de Manufactura</title>

  <!-- Bootstrap (si tu _menu_global.php no lo incluye, esto lo blinda) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

  <style>
    :root{ --ap-font:10px; }
    body, .form-control, .form-select, .btn, table { font-size: var(--ap-font) !important; }
    .ap-card { border:1px solid #e6e6e6; border-radius:10px; padding:10px; background:#fff; }
    .ap-kpi { min-width: 160px; }
    .ap-kpi .label { color:#6c757d; font-weight:600; }
    .ap-kpi .value { font-size:14px !important; font-weight:800; }
    .dt-wrap { border:1px solid #e6e6e6; border-radius:10px; overflow:hidden; background:#fff; }
    table.dataTable thead th { white-space:nowrap; }
    .text-num { text-align:right !important; }
    .btn-icon { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; }
    .modal-xl { --bs-modal-width: 1100px; }
  </style>
</head>

<body class="bg-light">

<div class="container-fluid p-3">

  <div class="d-flex align-items-center gap-2 mb-2">
    <h5 class="m-0 text-primary fw-bold">📁 Administración de Manufactura</h5>
  </div>

  <!-- KPIs -->
  <div class="d-flex flex-wrap gap-2 mb-2">
    <div class="ap-card ap-kpi">
      <div class="label">Pendientes (P)</div>
      <div class="value" id="kpiP">0</div>
    </div>
    <div class="ap-card ap-kpi">
      <div class="label">Terminadas (T)</div>
      <div class="value" id="kpiT">0</div>
    </div>
    <div class="ap-card ap-kpi">
      <div class="label">Iniciadas (I)</div>
      <div class="value" id="kpiI">0</div>
    </div>
    <div class="ap-card ap-kpi">
      <div class="label">Borrador (B)</div>
      <div class="value" id="kpiB">0</div>
    </div>
    <div class="ap-card ap-kpi">
      <div class="label">Error (E)</div>
      <div class="value" id="kpiE">0</div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="ap-card mb-2">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label mb-1">Empresa</label>
        <select id="fEmpresa" class="form-select">
          <option value="">Todas</option>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label mb-1">Almacén</label>
        <select id="fAlmacen" class="form-select">
          <option value="">Todos</option>
        </select>
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label mb-1">Status OT</label>
        <select id="fStatus" class="form-select">
          <!-- DEFAULT: Pendiente -->
          <option value="P" selected>Pendiente</option>
          <option value="">Todos</option>
          <option value="I">Iniciada</option>
          <option value="T">Terminada</option>
          <!-- Compatibilidad legacy -->
          <option value="B">Borrador</option>
          <option value="E">Error</option>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label mb-1">Fecha inicio</label>
        <input type="date" id="fIni" class="form-control">
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label mb-1">Fecha fin</label>
        <input type="date" id="fFin" class="form-control">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label mb-1">Buscar</label>
        <input type="text" id="fQ" class="form-control" placeholder="Folio / Artículo">
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label mb-1">Buscar LP</label>
        <input type="text" id="fLP" class="form-control" placeholder="LP / Contenedor">
      </div>

      <div class="col-12 col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100" id="btnAplicar">Aplicar</button>
        <button class="btn btn-outline-secondary w-100" id="btnLimpiar">Limpiar</button>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="dt-wrap p-2">
    <table id="tblOT" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Folio</th>
          <th>Artículo</th>
          <th>Lote</th>
          <th class="text-num">Cantidad</th>
          <th class="text-num">Prod</th>
          <th>Usuario</th>
          <th>Fecha</th>
          <th>Status</th>
        </tr>
      </thead>
    </table>
  </div>

</div>

<!-- Modal Detalle -->
<div class="modal fade" id="mdlDetalle" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">Detalle Orden Producción</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-wrap gap-3 mb-2">
          <div><b>Folio:</b> <span id="dFolio"></span></div>
          <div><b>Artículo:</b> <span id="dArticulo"></span></div>
          <div><b>Lote:</b> <span id="dLote"></span></div>
          <div><b>Cantidad:</b> <span id="dCantidad"></span></div>
          <div><b>Status:</b> <span id="dStatus"></span></div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead>
              <tr>
                <th>Artículo</th>
                <th>Lote</th>
                <th class="text-end">Cantidad</th>
                <th>Fecha</th>
                <th>Usuario</th>
              </tr>
            </thead>
            <tbody id="dBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (local primero, fallback CDN) -->
<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script>
if (typeof window.jQuery === "undefined") {
  document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
}
</script>

<!-- DataTables (local no asumimos; CDN) -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
const API_OT = "../api/orden_produccion_admin_data.php";

function ymd(d){
  const z = (n)=>String(n).padStart(2,'0');
  return d.getFullYear()+"-"+z(d.getMonth()+1)+"-"+z(d.getDate());
}

function fmt4(x){
  // 4 decimales máximo, siempre numérico
  const n = Number(x || 0);
  return n.toFixed(4).replace(/\.0000$/,'').replace(/(\.\d*[1-9])0+$/,'$1');
}

function refreshKPIs(stats){
  // stats: {P:..., I:..., T:..., B:..., E:...}
  $("#kpiP").text(stats?.P ?? 0);
  $("#kpiI").text(stats?.I ?? 0);
  $("#kpiT").text(stats?.T ?? 0);
  $("#kpiB").text(stats?.B ?? 0);
  $("#kpiE").text(stats?.E ?? 0);
}

function currentFilters(includeStatus=true){
  return {
    empresa: $("#fEmpresa").val() || "",
    almacen: $("#fAlmacen").val() || "",
    status: includeStatus ? ($("#fStatus").val() || "") : "",
    ini: $("#fIni").val() || "",
    fin: $("#fFin").val() || "",
    q: ($("#fQ").val() || "").trim(),
    lp: ($("#fLP").val() || "").trim()
  };
}

async function loadEmpresas(){
  // Usa c_compania
  // Endpoint esperado: ../api/empresas_api.php  (tu lo mencionaste)
  try{
    const r = await fetch("../api/empresas_api.php");
    const j = await r.json();
    const sel = $("#fEmpresa");
    sel.empty().append(`<option value="">Todas</option>`);
    if (Array.isArray(j?.data)){
      j.data.forEach(it=>{
        sel.append(`<option value="${it.cve_cia}">${(it.des_cia||it.clave_empresa||it.cve_cia)}</option>`);
      });
    }
  }catch(e){
    // Si falla, dejamos "Todas" y no tronamos la UI
    console.warn("No pude cargar empresas_api.php", e);
  }
}

async function loadAlmacenesByEmpresa(){
  // Si hay empresa -> filtramos almacenes por cve_cia
  // Endpoint opcional: si no existe, el API de OTs igual filtra por almacen id.
  try{
    const empresa = $("#fEmpresa").val() || "";
    const url = empresa
      ? `../api/catalogo_almacenes.php?cve_cia=${encodeURIComponent(empresa)}`
      : `../api/catalogo_almacenes.php`;
    const r = await fetch(url);
    const j = await r.json();

    const sel = $("#fAlmacen");
    sel.empty().append(`<option value="">Todos</option>`);

    const arr = Array.isArray(j?.data) ? j.data : (Array.isArray(j) ? j : []);
    arr.forEach(it=>{
      const id = it.id ?? it.cve_almac ?? it.cve_alma ?? it.ID ?? "";
      const txt = it.nombre ?? it.des_almac ?? it.clave ?? it.rut ?? id;
      if (id !== "") sel.append(`<option value="${id}">${txt}</option>`);
    });
  }catch(e){
    console.warn("No pude cargar catalogo_almacenes.php", e);
  }
}

function setDefaultDates(){
  // Por default: últimos 7 días
  const fin = new Date();
  const ini = new Date(); ini.setDate(ini.getDate()-7);
  $("#fIni").val(ymd(ini));
  $("#fFin").val(ymd(fin));
}

let dt = null;

function initTable(){
  dt = $("#tblOT").DataTable({
    processing: true,
    serverSide: true,
    pageLength: 25,
    lengthMenu: [[25,50,100],[25,50,100]],
    scrollX: true,
    scrollY: "55vh",
    scrollCollapse: true,
    order: [[7, "desc"]],
    ajax: {
      url: API_OT,
      type: "GET",
      data: function(d){
        // DataTables -> API + filtros
        const f = currentFilters(true);
        d.empresa = f.empresa;
        d.almacen = f.almacen;
        d.status  = f.status;
        d.ini     = f.ini;
        d.fin     = f.fin;
        d.q       = f.q;
        d.lp      = f.lp;
        d.with_stats = 1; // KPIs
      },
      dataSrc: function(json){
        // KPIs (total real filtrado, no page)
        if (json && json.stats) refreshKPIs(json.stats);
        return json.data || [];
      },
      error: function(xhr){
        console.error("API error:", xhr.responseText);
      }
    },
    columns: [
      { data: "acciones", orderable:false, searchable:false },
      { data: "Folio_Pro" },
      { data: "Cve_Articulo" },
      { data: "Cve_Lote" },
      { data: "Cantidad", className:"text-num",
        render: (d)=>fmt4(d)
      },
      { data: "Cant_Prod", className:"text-num",
        render: (d)=>fmt4(d)
      },
      { data: "Cve_Usuario" },
      { data: "Fecha" },
      { data: "Status" }
    ],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
    }
  });
}

function reloadTable(){
  if (!dt) return;
  dt.ajax.reload(null, true);
}

async function verDetalle(folio){
  try{
    const url = `${API_OT}?action=detalle&folio=${encodeURIComponent(folio)}`;
    const r = await fetch(url);
    const j = await r.json();
    if (!j.ok) throw new Error(j.error || "Error detalle");

    $("#dFolio").text(j.head?.Folio_Pro || folio);
    $("#dArticulo").text(j.head?.Cve_Articulo || "");
    $("#dLote").text(j.head?.Cve_Lote || "");
    $("#dCantidad").text(fmt4(j.head?.Cantidad || 0));
    $("#dStatus").text(j.head?.Status || "");

    const tb = $("#dBody");
    tb.empty();
    (j.rows||[]).forEach(it=>{
      tb.append(`
        <tr>
          <td>${it.Cve_Articulo ?? ""}</td>
          <td>${it.Cve_Lote ?? ""}</td>
          <td class="text-end">${fmt4(it.Cantidad)}</td>
          <td>${it.Fecha_Prod ?? it.Fecha ?? ""}</td>
          <td>${it.Usr_Armo ?? ""}</td>
        </tr>
      `);
    });

    const modal = new bootstrap.Modal(document.getElementById("mdlDetalle"));
    modal.show();
  }catch(e){
    alert("No se pudo abrir detalle: " + e.message);
  }
}

// Exponer para botones
window.__verDetalle = verDetalle;

$(async function(){

  setDefaultDates();
  await loadEmpresas();
  await loadAlmacenesByEmpresa();

  // Cambiar empresa -> recargar almacenes
  $("#fEmpresa").on("change", async ()=>{
    await loadAlmacenesByEmpresa();
  });

  initTable();

  $("#btnAplicar").on("click", function(){
    reloadTable();
  });

  $("#btnLimpiar").on("click", async function(){
    $("#fEmpresa").val("");
    $("#fAlmacen").val("");
    $("#fStatus").val("P"); // default Pendiente
    $("#fQ").val("");
    $("#fLP").val("");
    setDefaultDates();
    await loadAlmacenesByEmpresa();
    reloadTable();
  });

});
</script>

<?php
if (file_exists(__DIR__ . '/../bi/_menu_global_end.php')) {
  include __DIR__ . '/../bi/_menu_global_end.php';
}
?>
</body>
</html>
