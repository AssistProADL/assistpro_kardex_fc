<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$preCveClte = trim((string)($_GET['Cve_Clte'] ?? '')); // precarga desde clientes
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo de Destinatarios</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css" rel="stylesheet">

  <style>
    .ap-title{ font-weight:700; letter-spacing:.2px; }
    .ap-sub{ color:#6c757d; font-size:12px; }
    .ap-card{ border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
    table.dataTable thead th, table.dataTable tbody td { font-size: 10px; white-space: nowrap; }
    .dt-wrap { overflow:auto; }
    .btn-xs{ padding:.25rem .45rem; font-size: .75rem; }
    .form-label{ font-size:12px; margin-bottom:.25rem; }
    .form-control, .form-select{ font-size:12px; }
    .kpi{ min-height:72px; }
    .kpi .n{ font-size:20px; font-weight:800; }
  </style>
</head>
<body>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="ap-title fs-5">📍 Catálogo de Destinatarios</div>
      <div class="ap-sub">Direcciones de entrega / contactos asociados a clientes (25 por página con paginación)</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="../api/destinatarios.php?action=export_csv&tipo=layout">Descargar layout</a>
      <a class="btn btn-outline-secondary btn-sm" href="../api/destinatarios.php?action=export_csv&tipo=datos">Exportar datos</a>
      <button class="btn btn-outline-primary btn-sm" id="btnImport">Importar CSV</button>
      <button class="btn btn-primary btn-sm" id="btnNuevo">Nuevo destinatario</button>
    </div>
  </div>

  <!-- KPI -->
  <div class="row g-2 mb-2">
    <div class="col-md-3">
      <div class="card ap-card kpi"><div class="card-body">
        <div class="ap-sub">Total</div><div class="n" id="kTotal">0</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card ap-card kpi"><div class="card-body">
        <div class="ap-sub">Activos</div><div class="n" id="kActivos">0</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card ap-card kpi"><div class="card-body">
        <div class="ap-sub">Inactivos</div><div class="n" id="kInactivos">0</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card ap-card kpi"><div class="card-body">
        <div class="ap-sub">Principales</div><div class="n" id="kPrincipales">0</div>
      </div></div>
    </div>
  </div>

  <div class="card ap-card">
    <div class="card-body">

      <div class="row g-2 align-items-end mb-2">
        <div class="col-md-3">
          <label class="form-label">Cve Cliente (obligatorio para alta)</label>
          <input type="text" id="fCveClte" class="form-control" placeholder="Ej: CLI0001" value="<?= htmlspecialchars($preCveClte) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ver inactivos</label>
          <select id="fInactivos" class="form-select">
            <option value="0" selected>No</option>
            <option value="1">Sí</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Buscar</label>
          <input type="text" id="fQ" class="form-control" placeholder="Razón social, dirección, contacto, etc.">
        </div>
        <div class="col-md-1">
          <button class="btn btn-outline-dark btn-sm w-100" id="btnFiltrar">OK</button>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary btn-sm w-100" id="btnReset">Limpiar</button>
        </div>
      </div>

      <div class="dt-wrap">
        <table id="tblDest" class="display stripe" style="width:100%">
          <thead>
            <tr>
              <th>Acciones</th>
              <th>id_destinatario</th>
              <th>Cve_Clte</th>
              <th>clave_destinatario</th>
              <th>razonsocial</th>
              <th>direccion</th>
              <th>colonia</th>
              <th>postal</th>
              <th>ciudad</th>
              <th>estado</th>
              <th>contacto</th>
              <th>telefono</th>
              <th>email</th>
              <th>cve_vendedor</th>
              <th>dir_principal</th>
              <th>Activo</th>
            </tr>
          </thead>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- Modal Destinatario -->
<div class="modal fade" id="mdlDest" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlDestTitle">Destinatario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="m_id_destinatario">

        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label">Cve_Clte *</label>
            <input class="form-control" id="m_Cve_Clte">
          </div>
          <div class="col-md-3">
            <label class="form-label">clave_destinatario *</label>
            <input class="form-control" id="m_clave_destinatario" placeholder="Ej: CLI0001-PRINC">
          </div>
          <div class="col-md-4">
            <label class="form-label">razonsocial</label>
            <input class="form-control" id="m_razonsocial">
          </div>
          <div class="col-md-2">
            <label class="form-label">Activo</label>
            <select class="form-select" id="m_Activo">
              <option value="1" selected>Sí</option>
              <option value="0">No</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">direccion</label>
            <input class="form-control" id="m_direccion">
          </div>
          <div class="col-md-3">
            <label class="form-label">colonia</label>
            <input class="form-control" id="m_colonia">
          </div>
          <div class="col-md-3">
            <label class="form-label">postal</label>
            <input class="form-control" id="m_postal">
          </div>

          <div class="col-md-3">
            <label class="form-label">ciudad</label>
            <input class="form-control" id="m_ciudad">
          </div>
          <div class="col-md-3">
            <label class="form-label">estado</label>
            <input class="form-control" id="m_estado">
          </div>
          <div class="col-md-3">
            <label class="form-label">contacto</label>
            <input class="form-control" id="m_contacto">
          </div>
          <div class="col-md-3">
            <label class="form-label">telefono</label>
            <input class="form-control" id="m_telefono">
          </div>

          <div class="col-md-4">
            <label class="form-label">email_destinatario</label>
            <input class="form-control" id="m_email_destinatario">
          </div>
          <div class="col-md-4">
            <label class="form-label">cve_vendedor</label>
            <input class="form-control" id="m_cve_vendedor">
          </div>
          <div class="col-md-2">
            <label class="form-label">dir_principal</label>
            <input class="form-control" id="m_dir_principal" placeholder="1/0">
          </div>
          <div class="col-md-1">
            <label class="form-label">latitud</label>
            <input class="form-control" id="m_latitud">
          </div>
          <div class="col-md-1">
            <label class="form-label">longitud</label>
            <input class="form-control" id="m_longitud">
          </div>
        </div>

        <div class="alert alert-info mt-3 mb-0" style="font-size:12px;">
          * Obligatorios: <b>Cve_Clte</b> y <b>clave_destinatario</b>.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" id="btnGuardarDest">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="mdlImport" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Importar Destinatarios (CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="file" id="csvFile" class="form-control" accept=".csv">
        <div class="ap-sub mt-2">Descarga layout, llena datos y reimporta (UPSERT por Cve_Clte + clave_destinatario).</div>
        <div id="importResult" class="mt-2" style="font-size:12px;"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" id="btnDoImport">Importar</button>
      </div>
    </div>
  </div>
</div>

<script>
  const API = "../api/destinatarios.php";
  const PRE_CVE = <?= json_encode($preCveClte) ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

<script>
let dtDest;

function postForm(action, payload){
  const fd = new FormData();
  fd.append('action', action);
  Object.keys(payload).forEach(k => fd.append(k, payload[k]));
  return fetch(API, { method:'POST', body: fd }).then(r => r.json());
}

function refreshKpis(){
  // usamos dt_dest para calcular KPIs: total/activos/inactivos y principales
  const base = new URLSearchParams();
  base.set('action','dt_dest');
  base.set('draw','1');
  base.set('start','0');
  base.set('length','1');
  base.set('Cve_Clte', $('#fCveClte').val().trim());
  base.set('q', $('#fQ').val().trim());
  base.set('inactivos','1'); // para contar total real
  fetch(API + "?" + base.toString()).then(r=>r.json()).then(j=>{
    $('#kTotal').text(j.recordsFiltered ?? 0);
  });

  const act = new URLSearchParams(base);
  act.set('inactivos','0');
  fetch(API + "?" + act.toString()).then(r=>r.json()).then(j=>{
    $('#kActivos').text(j.recordsFiltered ?? 0);
    const total = parseInt($('#kTotal').text()||'0',10);
    const activos = j.recordsFiltered ?? 0;
    $('#kInactivos').text(Math.max(0, total - activos));
  });

  // principales (dir_principal=1) simple query con list (rápido)
  const p = new URLSearchParams();
  p.set('action','list');
  p.set('inactivos','1');
  p.set('Cve_Clte', $('#fCveClte').val().trim());
  p.set('q','');
  fetch(API + "?" + p.toString()).then(r=>r.json()).then(rows=>{
    const n = Array.isArray(rows) ? rows.filter(x => parseInt(x.dir_principal||0,10)===1).length : 0;
    $('#kPrincipales').text(n);
  });
}

$(function(){

  // precarga desde clientes: bloquea campo filtro si llega por GET
  if(PRE_CVE){
    $('#fCveClte').prop('readonly', true);
  }

  dtDest = $("#tblDest").DataTable({
    processing: true,
    serverSide: true,
    pageLength: 25,
    lengthMenu: [25,50,100],
    scrollX: true,
    scrollY: "60vh",
    ajax: function(data, cb){
      const params = new URLSearchParams();
      params.set('action','dt_dest');
      params.set('draw', data.draw);
      params.set('start', data.start);
      params.set('length', data.length);
      params.set('inactivos', $("#fInactivos").val());
      params.set('Cve_Clte', $("#fCveClte").val().trim());  // <-- Cve_Clte
      params.set('q', $("#fQ").val().trim());
      fetch(API + "?" + params.toString()).then(r=>r.json()).then(j=>{
        cb(j);
        refreshKpis();
      });
    },
    columns: [
      { data: null, orderable:false, render: function(row){
          const id = row.id_destinatario;
          const act = (row.Activo==='1');
          return `
            <div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-xs" onclick="editDest(${id})">Editar</button>
              ${act
                ? `<button class="btn btn-outline-danger btn-xs" onclick="delDest(${id})">Baja</button>`
                : `<button class="btn btn-outline-success btn-xs" onclick="resDest(${id})">Restaurar</button>`}
            </div>`;
        }
      },
      { data:'id_destinatario' },
      { data:'Cve_Clte' },
      { data:'clave_destinatario' },
      { data:'razonsocial' },
      { data:'direccion' },
      { data:'colonia' },
      { data:'postal' },
      { data:'ciudad' },
      { data:'estado' },
      { data:'contacto' },
      { data:'telefono' },
      { data:'email_destinatario' },
      { data:'cve_vendedor' },
      { data:'dir_principal' },
      { data:'Activo' }
    ],
    fixedHeader: true
  });

  const mdlDest = new bootstrap.Modal(document.getElementById('mdlDest'));
  const mdlImport = new bootstrap.Modal(document.getElementById('mdlImport'));

  $("#btnFiltrar").on('click', ()=> dtDest.ajax.reload());
  $("#btnReset").on('click', ()=>{
    if(!PRE_CVE) $('#fCveClte').val('');
    $('#fQ').val('');
    $('#fInactivos').val('0');
    dtDest.ajax.reload();
  });

  $("#btnNuevo").on('click', ()=>{
    $("#mdlDestTitle").text("Nuevo destinatario");
    $("#mdlDest input, #mdlDest select").val('');
    $("#m_Activo").val('1');
    // Si vienes desde clientes, precarga y bloquea en modal
    const c = $('#fCveClte').val().trim();
    if(c){
      $('#m_Cve_Clte').val(c).prop('readonly', true);
      $('#m_clave_destinatario').val(c + '-PRINC');
      $('#m_dir_principal').val('1');
    }else{
      $('#m_Cve_Clte').prop('readonly', false);
    }
    mdlDest.show();
  });

  $("#btnImport").on('click', ()=>{ $("#csvFile").val(''); $("#importResult").html(''); mdlImport.show(); });

  $("#btnDoImport").on('click', async ()=>{
    const f = $("#csvFile")[0].files[0];
    if(!f){ alert("Selecciona un CSV"); return; }
    const fd = new FormData();
    fd.append('action','import_csv');
    fd.append('file', f);
    const r = await fetch(API, { method:'POST', body: fd }).then(x=>x.json());
    if(r.ok || r.success){
      $("#importResult").html(`<div class="text-success">OK: ${r.rows_ok} | Errores: ${r.rows_err}</div>`);
      dtDest.ajax.reload();
    }else{
      $("#importResult").html(`<div class="text-danger">${r.msg || r.error || 'Error'}</div>`);
    }
  });

  $("#btnGuardarDest").on('click', async ()=>{
    const id = $("#m_id_destinatario").val().trim();
    const payload = {
      id_destinatario: id,
      Cve_Clte: $("#m_Cve_Clte").val(),                 // <-- Cve_Clte
      clave_destinatario: $("#m_clave_destinatario").val(),
      razonsocial: $("#m_razonsocial").val(),
      direccion: $("#m_direccion").val(),
      colonia: $("#m_colonia").val(),
      postal: $("#m_postal").val(),
      ciudad: $("#m_ciudad").val(),
      estado: $("#m_estado").val(),
      contacto: $("#m_contacto").val(),
      telefono: $("#m_telefono").val(),
      email_destinatario: $("#m_email_destinatario").val(),
      cve_vendedor: $("#m_cve_vendedor").val(),
      latitud: $("#m_latitud").val(),
      longitud: $("#m_longitud").val(),
      dir_principal: $("#m_dir_principal").val(),
      Activo: $("#m_Activo").val()
    };
    if(!payload.Cve_Clte || !payload.clave_destinatario){
      alert("Cve_Clte y clave_destinatario son obligatorios"); return;
    }
    const action = id ? 'update' : 'create';
    const r = await postForm(action, payload);
    if(r.ok || r.success){
      mdlDest.hide();
      dtDest.ajax.reload();
    }else{
      alert((r.msg || r.error || 'Error') + (r.detalles ? ("\n" + r.detalles.join("\n")) : ''));
    }
  });

  // exponer modal
  window.__mdlDest = mdlDest;
});

async function editDest(id){
  const r = await fetch(API + "?action=get&id_destinatario=" + id).then(x=>x.json());
  const d = r.data || r;
  if(d && !d.error){
    $("#mdlDestTitle").text("Editar destinatario #" + id);
    $("#m_id_destinatario").val(d.id_destinatario || '');
    $("#m_Cve_Clte").val(d.Cve_Clte || '').prop('readonly', !!PRE_CVE);
    $("#m_clave_destinatario").val(d.clave_destinatario || '');
    $("#m_razonsocial").val(d.razonsocial || '');
    $("#m_direccion").val(d.direccion || '');
    $("#m_colonia").val(d.colonia || '');
    $("#m_postal").val(d.postal || '');
    $("#m_ciudad").val(d.ciudad || '');
    $("#m_estado").val(d.estado || '');
    $("#m_contacto").val(d.contacto || '');
    $("#m_telefono").val(d.telefono || '');
    $("#m_email_destinatario").val(d.email_destinatario || d.email_destinatario || '');
    $("#m_cve_vendedor").val(d.cve_vendedor || '');
    $("#m_latitud").val(d.latitud || '');
    $("#m_longitud").val(d.longitud || '');
    $("#m_dir_principal").val(d.dir_principal ?? '');
    $("#m_Activo").val((d.Activo==='0')?'0':'1');
    window.__mdlDest.show();
  }else{
    alert(d.error || "No se pudo cargar");
  }
}

async function delDest(id){
  if(!confirm("Dar de baja destinatario #" + id + "?")) return;
  const r = await postForm('delete', { id_destinatario: id });
  if(r.ok || r.success){ $("#tblDest").DataTable().ajax.reload(); }
  else alert(r.msg || r.error || "Error");
}
async function resDest(id){
  if(!confirm("Restaurar destinatario #" + id + "?")) return;
  const r = await postForm('restore', { id_destinatario: id });
  if(r.ok || r.success){ $("#tblDest").DataTable().ajax.reload(); }
  else alert(r.msg || r.error || "Error");
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
