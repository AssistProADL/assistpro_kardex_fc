<?php
// public/catalogos/formas_pago.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssistPro | Formas de Pago</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <!-- DataTables Buttons (Export CSV) -->
  <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

  <!-- FontAwesome (icono) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --ap-blue:#0b3a66;
      --ap-blue2:#0e4a80;
      --ap-gray:#f4f6f9;
      --ap-white:#ffffff;
    }

    body{ background: var(--ap-gray); }

    /* Header corporativo (no pegado) */
    .ap-header{
      background: linear-gradient(90deg, var(--ap-blue), var(--ap-blue2));
      color:#fff;
      border-radius: 16px;
      padding: 18px 18px; /* más aire */
      box-shadow: 0 6px 18px rgba(0,0,0,.12);
      margin-bottom: 12px;
    }
    .ap-title{
      display:flex; gap:12px; align-items:center;
    }
    .ap-title .ap-ico{
      width:42px; height:42px;
      border-radius: 12px;
      background: rgba(255,255,255,.14);
      display:flex; align-items:center; justify-content:center;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
    }
    .ap-title h1{
      font-size: 16px; margin:0; font-weight:800; letter-spacing:.2px;
    }
    .ap-title .sub{
      font-size: 12px; margin:0; opacity:.95; font-weight:600;
    }

    .ap-card{
      border:0;
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0,0,0,.08);
    }

    /* Tipografía compacta AssistPro */
    .table, .dataTables_wrapper, .modal, .form-control, .form-select, .btn{
      font-size: 10px !important;
    }

    /* Una fila por renglón, sin doble espacio */
    table.dataTable thead th{ padding: 6px 8px !important; }
    table.dataTable tbody td{
      padding: 4px 8px !important;
      line-height: 1.05 !important;
      vertical-align: middle !important;
      white-space: nowrap;
    }

    /* Filtros en una sola fila */
    .filters-row .form-select, .filters-row .form-control{
      font-size: 10px !important;
      padding: .20rem .45rem !important;
      height: 28px !important;
    }

    /* Botón mini */
    .btn-xs{ padding: .15rem .35rem !important; font-size: 10px !important; }

    .badge{ font-size:10px !important; }

    /* Ajustes DataTables */
    .dataTables_wrapper .dataTables_filter input{
      height: 28px !important;
      padding: .20rem .45rem !important;
      font-size: 10px !important;
    }
    .dataTables_wrapper .dataTables_length{ display:none; } /* max 25, sin selector */
    .dt-buttons .btn{ margin-right:6px; }

    /* Overlay / Spinner corporativo */
    .ap-overlay{
      position: fixed;
      inset: 0;
      background: rgba(255,255,255,.65);
      display:none;
      align-items:center;
      justify-content:center;
      z-index: 9999;
    }
    .ap-overlay .box{
      background: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,.12);
      display:flex;
      align-items:center;
      gap:10px;
      border: 1px solid rgba(11,58,102,.12);
    }
    .ap-overlay .spinner-border{
      width: 1.15rem;
      height: 1.15rem;
    }
  </style>
</head>

<body>

<div class="ap-overlay" id="apOverlay">
  <div class="box">
    <div class="spinner-border text-primary" role="status"></div>
    <div style="font-weight:700;color:var(--ap-blue);">Procesando...</div>
  </div>
</div>

<div class="container-fluid py-3">

  <div class="ap-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="ap-title">
      <div class="ap-ico">
        <i class="fa-solid fa-credit-card" style="font-size:18px;"></i>
      </div>
      <div>
        <h1>Catálogo | Formas de Pago</h1>
        <p class="sub">Gobierno comercial y control financiero (Activos / Inactivos)</p>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-light btn-sm" id="btnNuevo"><i class="fa-solid fa-plus me-1"></i>Nuevo</button>
      <button class="btn btn-outline-light btn-sm" id="btnImportar"><i class="fa-solid fa-file-arrow-up me-1"></i>Importar CSV</button>
    </div>
  </div>

  <div class="card ap-card">
    <div class="card-body">

      <!-- Filtros (misma fila) -->
      <div class="row g-2 align-items-end filters-row mb-2">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Empresa</label>
          <select class="form-select" id="fEmpresa">
            <option value="">Todas</option>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label mb-1">Estatus</label>
          <select class="form-select" id="fStatus">
            <option value="">Todos</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        <div class="col-12 col-md-7 d-flex justify-content-end gap-2">
          <button class="btn btn-outline-primary btn-sm" id="btnRefrescar">
            <i class="fa-solid fa-rotate me-1"></i>Refrescar
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table id="tbl" class="table table-sm table-striped table-hover w-100">
          <thead class="table-dark">
            <tr>
              <th style="width:170px;">Opciones</th>
              <th style="width:70px;" class="text-center">ID</th>
              <th>Forma</th>
              <th>Clave</th>
              <th style="width:140px;" class="text-center">Empresa</th>
              <th style="width:95px;" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<!-- Modal Alta/Edición -->
<div class="modal fade" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header" style="background:var(--ap-blue);color:#fff;">
        <h6 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Forma de Pago</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="frm">
        <div class="modal-body">
          <input type="hidden" name="IdFpag" id="IdFpag" value="0">

          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Forma</label>
              <input class="form-control form-control-sm" name="Forma" id="Forma" maxlength="100" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Clave</label>
              <input class="form-control form-control-sm" name="Clave" id="Clave" maxlength="100" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Empresa (IdEmpresa)</label>
              <input class="form-control form-control-sm" name="IdEmpresa" id="IdEmpresa" maxlength="50">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Status</label>
              <select class="form-select form-select-sm" name="Status" id="Status">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Importar CSV -->
<div class="modal fade" id="mdlCsv" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header" style="background:var(--ap-blue);color:#fff;">
        <h6 class="modal-title"><i class="fa-solid fa-file-csv me-2"></i>Importar CSV</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info py-2 mb-2" style="font-size:10px;">
          Layout CSV esperado: <b>Clave, Forma, IdEmpresa, Status</b> (Status: 1 activo, 0 inactivo)
        </div>

        <input type="file" id="csvFile" class="form-control form-control-sm" accept=".csv">
        <div class="mt-2 d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" id="btnDescargaLayout">
            <i class="fa-solid fa-download me-1"></i>Layout CSV
          </button>
          <button class="btn btn-primary btn-sm" id="btnSubirCsv">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i>Importar
          </button>
        </div>

        <div id="csvResult" class="mt-2" style="font-size:10px;"></div>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
  const API = "../api/formaspag_api.php";
  const mdl = new bootstrap.Modal(document.getElementById('mdl'));
  const mdlCsv = new bootstrap.Modal(document.getElementById('mdlCsv'));

  let dt = null;

  function overlay(show){
    const el = document.getElementById('apOverlay');
    el.style.display = show ? 'flex' : 'none';
  }

  function badgeStatus(v){
    const n = parseInt(v,10);
    return n === 1
      ? '<span class="badge text-bg-success">Activo</span>'
      : '<span class="badge text-bg-secondary">Inactivo</span>';
  }

  function renderOps(row){
    const id = row.IdFpag;
    const st = parseInt(row.Status,10);

    const btnEdit = `<button class="btn btn-outline-primary btn-xs me-1" data-op="edit" data-id="${id}">
      <i class="fa-solid fa-pen me-1"></i>Editar</button>`;

    const btnToggle = st === 1
      ? `<button class="btn btn-outline-warning btn-xs me-1" data-op="toggle" data-id="${id}">
          <i class="fa-solid fa-ban me-1"></i>Inactivar</button>`
      : `<button class="btn btn-outline-success btn-xs me-1" data-op="toggle" data-id="${id}">
          <i class="fa-solid fa-rotate-left me-1"></i>Recuperar</button>`;

    const btnDel = `<button class="btn btn-outline-danger btn-xs" data-op="del" data-id="${id}">
      <i class="fa-solid fa-trash me-1"></i>Eliminar</button>`;

    return btnEdit + btnToggle + btnDel;
  }

  function loadEmpresas(){
    return $.getJSON(API, {action:'empresas'}).then(resp=>{
      if(!resp.ok) return;
      const $s = $("#fEmpresa");
      $s.find('option:not(:first)').remove();
      (resp.data || []).forEach(r=>{
        const v = (r.IdEmpresa ?? '').toString().trim();
        if(v !== '') $s.append(`<option value="${v}">${v}</option>`);
      });
    });
  }

  function initDT(){
    dt = $("#tbl").DataTable({
      processing: true,
      serverSide: true,
      pageLength: 25,
      scrollX: true,
      scrollY: "58vh",
      scrollCollapse: true,
      searching: true,
      dom: "<'row g-2 align-items-center'<'col-12 col-md-6'B><'col-12 col-md-6'f>>" +
           "<'row'<'col-12'tr>>" +
           "<'row g-2 align-items-center'<'col-12 col-md-6'i><'col-12 col-md-6'p>>",
      buttons: [
        {
          extend: 'csvHtml5',
          text: '<i class="fa-solid fa-file-csv me-1"></i>Exportar CSV',
          className: 'btn btn-outline-primary btn-sm',
          filename: 'catalogo_formas_pago',
          exportOptions: {
            // Exporta columnas visibles (sin Opciones)
            columns: [1,2,3,4,5]
          }
        }
      ],
      ajax: {
        url: API,
        type: "GET",
        data: function(d){
          d.action   = "list";
          d.fEmpresa = $("#fEmpresa").val();
          d.fStatus  = $("#fStatus").val();
          d.length   = 25; // asegurar max 25
        },
        beforeSend: ()=> overlay(true),
        complete: ()=> overlay(false),
        error: ()=> overlay(false)
      },
      columns: [
        { data: null, orderable:false, searchable:false, render: (d,t,row)=> renderOps(row) },
        { data: "IdFpag", className: "text-center" },
        { data: "Forma" },
        { data: "Clave" },
        { data: "IdEmpresa", className: "text-center" },
        { data: "Status", className: "text-center", render: (d)=> badgeStatus(d) }
      ],
      order: [[1,'desc']],
      language: { url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json" }
    });

    // Hook extra: overlay cuando DataTables procesa demasiado
    dt.on('processing.dt', function(e, settings, processing) {
      overlay(!!processing);
    });
  }

  function openNuevo(){
    $("#IdFpag").val(0);
    $("#Forma").val('');
    $("#Clave").val('');
    $("#IdEmpresa").val($("#fEmpresa").val() || '');
    $("#Status").val(1);
    mdl.show();
  }

  function openEdit(id){
    overlay(true);
    $.getJSON(API, {action:'get', id}).then(resp=>{
      overlay(false);
      if(!resp.ok){ alert(resp.msg || 'Error'); return; }
      const r = resp.data;
      $("#IdFpag").val(r.IdFpag);
      $("#Forma").val(r.Forma);
      $("#Clave").val(r.Clave);
      $("#IdEmpresa").val(r.IdEmpresa);
      $("#Status").val(r.Status);
      mdl.show();
    }).catch(()=> overlay(false));
  }

  function toggleStatus(id){
    if(!confirm("¿Confirmas el cambio de estatus (Inactivar/Recuperar)?")) return;
    overlay(true);
    $.post(API, {action:'toggle', id}, null, 'json').done(resp=>{
      overlay(false);
      if(!resp.ok){ alert(resp.msg||'Error'); return; }
      dt.ajax.reload(null,false);
    }).fail(()=> overlay(false));
  }

  function hardDelete(id){
    if(!confirm("¿Confirmas ELIMINAR (Hard Delete) este registro?")) return;
    overlay(true);
    $.post(API, {action:'delete', id}, null, 'json').done(resp=>{
      overlay(false);
      if(!resp.ok){ alert(resp.msg||'Error'); return; }
      dt.ajax.reload(null,false);
      loadEmpresas();
    }).fail(()=> overlay(false));
  }

  function downloadLayout(){
    const csv = "Clave,Forma,IdEmpresa,Status\n" +
                "EFECTIVO,Pago en Efectivo,EMP01,1\n" +
                "TRANSFER,Transferencia Bancaria,EMP01,1\n";
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'layout_formas_pago.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  function importCsv(){
    const f = document.getElementById('csvFile').files[0];
    if(!f){ alert("Selecciona un archivo CSV."); return; }

    const fd = new FormData();
    fd.append('action','import_csv');
    fd.append('file', f);

    $("#csvResult").html('');
    overlay(true);

    $.ajax({
      url: API,
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json'
    }).done(resp=>{
      overlay(false);
      if(!resp.ok){
        let html = `<div class="alert alert-danger py-2 mb-0"><b>${resp.msg || 'Error'}</b>`;
        if(resp.errors && resp.errors.length){
          html += `<div class="mt-1">Detalles (top 25):<br>${resp.errors.map(x=>`• ${x}`).join('<br>')}</div>`;
        }
        html += `</div>`;
        $("#csvResult").html(html);
        return;
      }
      $("#csvResult").html(`<div class="alert alert-success py-2 mb-0"><b>${resp.msg}</b></div>`);
      loadEmpresas();
      dt.ajax.reload(null,false);
    }).fail(()=>{
      overlay(false);
      $("#csvResult").html(`<div class="alert alert-danger py-2 mb-0"><b>Error de comunicación con API</b></div>`);
    });
  }

  $(function(){
    overlay(true);
    loadEmpresas().then(()=>{
      initDT();
      overlay(false);
    }).catch(()=> overlay(false));

    $("#btnNuevo").on('click', openNuevo);
    $("#btnRefrescar").on('click', ()=> dt.ajax.reload());
    $("#fEmpresa,#fStatus").on('change', ()=> dt.ajax.reload());

    $("#tbl").on('click','button[data-op]', function(){
      const op = $(this).data('op');
      const id = $(this).data('id');
      if(op === 'edit') openEdit(id);
      if(op === 'toggle') toggleStatus(id);
      if(op === 'del') hardDelete(id);
    });

    $("#frm").on('submit', function(e){
      e.preventDefault();
      overlay(true);
      const data = $(this).serialize() + "&action=save";
      $.post(API, data, null, 'json').done(resp=>{
        overlay(false);
        if(!resp.ok){ alert(resp.msg||'Error'); return; }
        mdl.hide();
        dt.ajax.reload(null,false);
        loadEmpresas();
      }).fail(()=> overlay(false));
    });

    $("#btnImportar").on('click', ()=>{
      $("#csvFile").val('');
      $("#csvResult").html('');
      mdlCsv.show();
    });
    $("#btnDescargaLayout").on('click', downloadLayout);
    $("#btnSubirCsv").on('click', importCsv);
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
