<?php
// public/catalogos/ticket.php
// Estilo AssistPro: grilla 25, 10px, scroll H/V, título centrado, corporativo
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo - Tickets</title>

  <!-- Bootstrap + DataTables (CDN). Si ya lo tienes local, sustitúyelo -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <style>
    :root{
      --ap-blue:#0b5ed7; /* ajusta al azul corporativo real si tienes uno */
      --ap-bg:#f6f8fb;
      --ap-card:#ffffff;
      --ap-border:#e6e9ef;
    }
    body{ background:var(--ap-bg); }
    .ap-titlebar{
      background: var(--ap-card);
      border:1px solid var(--ap-border);
      border-radius:14px;
      padding:12px 14px;
      margin:14px 14px 10px 14px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
    }
    .ap-title{
      margin:0;
      font-weight:800;
      color:var(--ap-blue);
      letter-spacing:.2px;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .ap-title i{ font-size:20px; }
    .ap-toolbar{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
    .ap-card{
      background:var(--ap-card);
      border:1px solid var(--ap-border);
      border-radius:14px;
      margin:0 14px 14px 14px;
      padding:12px;
    }
    .ap-table-wrap{
      width:100%;
      overflow:auto;
      border-radius:12px;
      border:1px solid var(--ap-border);
    }

    table.dataTable tbody td, table.dataTable thead th{
      font-size:10px !important;
      white-space:nowrap;
      vertical-align:middle;
    }
    .btn-xs{
      padding:2px 6px;
      font-size:10px;
      line-height:1.2;
      border-radius:8px;
    }
    .badge{ font-size:10px; }

    /* Spinner overlay */
    #apSpinner{
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.15);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:9999;
    }
    #apSpinner .box{
      background:#fff;
      border:1px solid var(--ap-border);
      border-radius:14px;
      padding:16px 18px;
      display:flex;
      align-items:center;
      gap:12px;
      box-shadow:0 10px 35px rgba(0,0,0,.12);
    }
  </style>
</head>

<body>

<?php
// Si tienes menú global, descomenta/ajusta rutas:
// @include __DIR__ . '/../_menu_global.php';
?>

<div class="ap-titlebar">
  <h1 class="ap-title">
    <span>🎫</span>
    <span>Catálogo - Tickets</span>
  </h1>

  <div class="ap-toolbar">
    <div class="input-group input-group-sm" style="width:240px;">
      <span class="input-group-text">IdEmpresa</span>
      <input type="text" class="form-control" id="fIdEmpresa" placeholder="Ej: EMP01">
    </div>

    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="fInactivos">
      <label class="form-check-label" for="fInactivos" style="font-size:12px;">Ver inactivos</label>
    </div>

    <button class="btn btn-sm btn-primary" id="btnNuevo">+ Nuevo</button>
    <button class="btn btn-sm btn-outline-primary" id="btnExport">Exportar CSV</button>

    <label class="btn btn-sm btn-outline-primary mb-0">
      Importar CSV <input type="file" id="csvFile" accept=".csv" hidden>
    </label>

    <button class="btn btn-sm btn-outline-secondary" id="btnRefrescar">Refrescar</button>
  </div>
</div>

<div class="ap-card">
  <div class="ap-table-wrap">
    <table id="tblTickets" class="table table-striped table-hover table-sm mb-0" style="width:100%;">
      <thead class="table-light">
        <tr>
          <th style="width:120px;">Acciones</th>
          <th>ID</th>
          <th>Linea1</th>
          <th>Linea2</th>
          <th>Linea3</th>
          <th>Linea4</th>
          <th>Mensaje</th>
          <th>Tdv</th>
          <th>Activo</th>
          <th>IdEmpresa</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Modal CRUD -->
<div class="modal fade" id="mdlTicket" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlTitle">Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="mID">

        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label small">Linea1</label>
            <input type="text" class="form-control form-control-sm" id="mLinea1">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Linea2</label>
            <input type="text" class="form-control form-control-sm" id="mLinea2">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Linea3</label>
            <input type="text" class="form-control form-control-sm" id="mLinea3">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Linea4</label>
            <input type="text" class="form-control form-control-sm" id="mLinea4">
          </div>

          <div class="col-md-12">
            <label class="form-label small">Mensaje</label>
            <input type="text" class="form-control form-control-sm" id="mMensaje">
          </div>

          <div class="col-md-3">
            <label class="form-label small">Tdv</label>
            <input type="number" class="form-control form-control-sm" id="mTdv" value="0">
          </div>

          <div class="col-md-3">
            <label class="form-label small">Activo (MLiq)</label>
            <select class="form-select form-select-sm" id="mMLiq">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small">IdEmpresa</label>
            <input type="text" class="form-control form-control-sm" id="mIdEmpresa">
          </div>

          <div class="col-md-12">
            <label class="form-label small">LOGO (opcional)</label>
            <input type="file" class="form-control form-control-sm" id="mLogoFile" accept="image/*">
            <div class="d-flex align-items-center gap-2 mt-2">
              <img id="mLogoPreview" src="" alt="" style="max-height:50px; display:none; border:1px solid #e6e9ef; border-radius:10px; padding:4px;">
              <button class="btn btn-xs btn-outline-danger" id="btnClearLogo" type="button">Quitar logo</button>
            </div>
            <input type="hidden" id="mLogoBase64" value="__NOCHANGE__">
            <div class="text-muted small mt-1">En CSV se maneja como <b>LOGO_BASE64</b> (puede ir vacío).</div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-sm btn-primary" id="btnGuardar">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Spinner -->
<div id="apSpinner">
  <div class="box">
    <div class="spinner-border" role="status"></div>
    <div style="font-weight:700;">Procesando…</div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
const API = "../api/api_ticket.php";
let tabla = null;
let mdl = null;

function spin(on){ document.getElementById('apSpinner').style.display = on ? 'flex' : 'none'; }

function toast(msg, ok=true){
  const cls = ok ? "alert-success" : "alert-danger";
  const el = document.createElement("div");
  el.className = `alert ${cls} py-2 px-3`;
  el.style.position = "fixed";
  el.style.right = "14px";
  el.style.bottom = "14px";
  el.style.zIndex = 99999;
  el.style.fontSize = "12px";
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(()=>{ el.remove(); }, 2200);
}

function badgeActivo(v){
  v = parseInt(v||0);
  return v===1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
}

function acciones(row){
  const id = row.ID;
  const inactivo = parseInt(row.MLiq||0) === 0;

  const btnEdit = `<button class="btn btn-xs btn-outline-primary me-1" onclick="editRow(${id})">Editar</button>`;
  const btnDel  = `<button class="btn btn-xs btn-outline-danger me-1" onclick="delRow(${id})">Eliminar</button>`;
  const btnInac = !inactivo
    ? `<button class="btn btn-xs btn-outline-warning me-1" onclick="inactRow(${id})">Inactivar</button>`
    : `<button class="btn btn-xs btn-outline-success me-1" onclick="restRow(${id})">Recuperar</button>`;

  return `${btnEdit}${btnInac}${btnDel}`;
}

function dtAjax(data, callback){
  const params = {
    action: "list",
    draw: data.draw,
    start: data.start,
    length: data.length,
    search: { value: data.search.value },
    order: data.order,
    IdEmpresa: $("#fIdEmpresa").val().trim(),
    include_inactive: $("#fInactivos").is(":checked") ? 1 : 0
  };

  $.ajax({
    url: API,
    type: "GET",
    data: params,
    dataType: "json",
    success: function(resp){
      if(!resp.success){
        callback({ draw:data.draw, recordsTotal:0, recordsFiltered:0, data:[] });
        toast(resp.message || "Error al cargar", false);
        return;
      }
      callback(resp);
    },
    error: function(){
      callback({ draw:data.draw, recordsTotal:0, recordsFiltered:0, data:[] });
      toast("Error de comunicación con API", false);
    }
  });
}

function initTabla(){
  tabla = $("#tblTickets").DataTable({
    processing: true,
    serverSide: true,
    pageLength: 25,
    lengthChange: false,
    searching: true,
    scrollX: true,
    scrollY: "55vh",
    scrollCollapse: true,
    ajax: dtAjax,
    order: [[1,'asc']],
    columns: [
      { data: null, orderable:false, render: function(d,t,row){ return acciones(row); } },
      { data: "ID" },
      { data: "Linea1" },
      { data: "Linea2" },
      { data: "Linea3" },
      { data: "Linea4" },
      { data: "Mensaje" },
      { data: "Tdv" },
      { data: "MLiq", render: function(d){ return badgeActivo(d); } },
      { data: "IdEmpresa" }
    ],
    language: {
      processing: "Procesando…",
      search: "Buscar:",
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      zeroRecords: "Sin coincidencias",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });
}

function openModalNuevo(){
  $("#mdlTitle").text("Nuevo Ticket");
  $("#mID").val("");
  $("#mLinea1,#mLinea2,#mLinea3,#mLinea4,#mMensaje,#mIdEmpresa").val("");
  $("#mTdv").val(0);
  $("#mMLiq").val("1");
  $("#mLogoBase64").val("__NOCHANGE__");
  $("#mLogoPreview").hide().attr("src","");
  $("#mLogoFile").val("");
  mdl.show();
}

function fileToBase64(file){
  return new Promise((resolve, reject)=>{
    const reader = new FileReader();
    reader.onload = ()=> resolve(reader.result); // data:image/...;base64,xxx
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

$("#mLogoFile").on("change", async function(){
  const f = this.files && this.files[0] ? this.files[0] : null;
  if(!f) return;
  const b64 = await fileToBase64(f);
  $("#mLogoBase64").val(b64);
  $("#mLogoPreview").attr("src", b64).show();
});

$("#btnClearLogo").on("click", function(){
  $("#mLogoBase64").val(""); // vacío => se guarda NULL
  $("#mLogoPreview").hide().attr("src","");
  $("#mLogoFile").val("");
});

async function editRow(id){
  spin(true);
  $.post(API, { action:"get", ID:id }, function(resp){
    spin(false);
    if(!resp.success){ toast(resp.message||"No se pudo cargar", false); return; }

    const r = resp.row;
    $("#mdlTitle").text("Editar Ticket #" + r.ID);
    $("#mID").val(r.ID);
    $("#mLinea1").val(r.Linea1||"");
    $("#mLinea2").val(r.Linea2||"");
    $("#mLinea3").val(r.Linea3||"");
    $("#mLinea4").val(r.Linea4||"");
    $("#mMensaje").val(r.Mensaje||"");
    $("#mTdv").val(r.Tdv||0);
    $("#mMLiq").val(String(r.MLiq ?? 1));
    $("#mIdEmpresa").val(r.IdEmpresa||"");

    // Logo preview
    if(r.LOGO_BASE64 && r.LOGO_BASE64.length > 0){
      const src = "data:image/*;base64," + r.LOGO_BASE64;
      $("#mLogoPreview").attr("src", src).show();
    } else {
      $("#mLogoPreview").hide().attr("src","");
    }
    $("#mLogoFile").val("");
    $("#mLogoBase64").val("__NOCHANGE__"); // por default no cambia hasta que suban archivo o quiten
    mdl.show();
  }, "json").fail(function(){
    spin(false);
    toast("Error consultando registro", false);
  });
}

function payloadModal(){
  return {
    ID: $("#mID").val(),
    Linea1: $("#mLinea1").val().trim(),
    Linea2: $("#mLinea2").val().trim(),
    Linea3: $("#mLinea3").val().trim(),
    Linea4: $("#mLinea4").val().trim(),
    Mensaje: $("#mMensaje").val().trim(),
    Tdv: $("#mTdv").val(),
    MLiq: $("#mMLiq").val(),
    IdEmpresa: $("#mIdEmpresa").val().trim(),
    LOGO_BASE64: $("#mLogoBase64").val()
  };
}

$("#btnGuardar").on("click", function(){
  const id = $("#mID").val();
  const isNew = !id;

  const p = payloadModal();
  p.action = isNew ? "create" : "update";

  spin(true);
  $.post(API, p, function(resp){
    spin(false);
    if(!resp.success){ toast(resp.message||"Error al guardar", false); return; }
    toast(resp.message||"OK", true);
    mdl.hide();
    tabla.ajax.reload(null,false);
  }, "json").fail(function(){
    spin(false);
    toast("Error guardando", false);
  });
});

function inactRow(id){
  if(!confirm("¿Inactivar ticket #" + id + "?")) return;
  spin(true);
  $.post(API, {action:"inactivate", ID:id}, function(resp){
    spin(false);
    if(!resp.success){ toast(resp.message||"Error", false); return; }
    toast(resp.message||"OK", true);
    tabla.ajax.reload(null,false);
  }, "json");
}

function restRow(id){
  if(!confirm("¿Recuperar ticket #" + id + "?")) return;
  spin(true);
  $.post(API, {action:"restore", ID:id}, function(resp){
    spin(false);
    if(!resp.success){ toast(resp.message||"Error", false); return; }
    toast(resp.message||"OK", true);
    tabla.ajax.reload(null,false);
  }, "json");
}

function delRow(id){
  if(!confirm("¿Eliminar (hard delete) ticket #" + id + "?\nEsta acción es irreversible.")) return;
  spin(true);
  $.post(API, {action:"delete", ID:id}, function(resp){
    spin(false);
    if(!resp.success){ toast(resp.message||"Error", false); return; }
    toast(resp.message||"OK", true);
    tabla.ajax.reload(null,false);
  }, "json");
}

$("#btnNuevo").on("click", openModalNuevo);
$("#btnRefrescar").on("click", ()=> tabla.ajax.reload(null,false));
$("#fIdEmpresa, #fInactivos").on("change keyup", function(){
  // no saturar: refresca al soltar
  clearTimeout(window.__ap_t);
  window.__ap_t = setTimeout(()=> tabla.ajax.reload(), 250);
});

$("#btnExport").on("click", function(){
  const IdEmpresa = $("#fIdEmpresa").val().trim();
  const include_inactive = $("#fInactivos").is(":checked") ? 1 : 0;

  // descarga directa
  const url = `${API}?action=export_csv&IdEmpresa=${encodeURIComponent(IdEmpresa)}&include_inactive=${include_inactive}`;
  window.open(url, "_blank");
});

$("#csvFile").on("change", function(){
  const f = this.files && this.files[0] ? this.files[0] : null;
  if(!f) return;

  const fd = new FormData();
  fd.append("action", "import_csv");
  fd.append("file", f);

  spin(true);
  $.ajax({
    url: API,
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function(resp){
      spin(false);
      if(!resp.success){ toast(resp.message||"Error importando", false); return; }
      toast(`Importación OK: ${resp.total_ok} | Err: ${resp.total_err}`, true);
      tabla.ajax.reload();
      $("#csvFile").val("");
    },
    error: function(){
      spin(false);
      toast("Error importando CSV", false);
      $("#csvFile").val("");
    }
  });
});

$(document).ready(function(){
  mdl = new bootstrap.Modal(document.getElementById('mdlTicket'));
  initTabla();
});
</script>

<!-- FontAwesome opcional (si ya lo tienes en tu layout global, quítalo) -->
<script src="https://kit.fontawesome.com/a2e0e6ad70.js" crossorigin="anonymous"></script>

<?php
// @include __DIR__ . '/../_menu_global_end.php';
?>
</body>
</html>
