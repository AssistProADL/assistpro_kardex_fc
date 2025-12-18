<?php
// public/catalogos/contactos.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo - Contactos | AssistPro</title>

  <!-- Bootstrap / FontAwesome (si tu proyecto ya los incluye global, puedes omitir) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/datatables.net-scroller-bs5@2.4.0/css/scroller.bootstrap5.min.css" rel="stylesheet">

  <style>
    :root{
      --ap-blue:#0b4aa2;      /* azul corporativo */
      --ap-blue-2:#0d6efd;
      --ap-bg:#f4f7fb;
      --ap-card:#ffffff;
      --ap-border:#dfe6ef;
    }
    body{ background:var(--ap-bg); }
    .ap-titlebar{
      background:var(--ap-card);
      border:1px solid var(--ap-border);
      border-radius:14px;
      padding:14px 16px;
      margin:14px 14px 10px 14px;
      display:flex; align-items:center; justify-content:space-between;
      box-shadow:0 6px 18px rgba(16,24,40,.06);
    }
    .ap-title{
      display:flex; gap:10px; align-items:center;
      color:var(--ap-blue);
      font-weight:800;
      font-size:18px;
      margin:0;
    }
    .ap-title i{ font-size:18px; }
    .ap-card{
      background:var(--ap-card);
      border:1px solid var(--ap-border);
      border-radius:14px;
      margin:0 14px 14px 14px;
      box-shadow:0 6px 18px rgba(16,24,40,.06);
    }
    .ap-card .ap-card-h{
      padding:10px 12px;
      border-bottom:1px solid var(--ap-border);
      display:flex; align-items:center; justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
    }
    .ap-btn{
      border-radius:10px;
      font-weight:700;
    }
    .ap-table-wrap{
      padding:10px 12px 14px 12px;
    }
    table.dataTable{
      font-size:10px !important;
      width:100% !important;
    }
    table.dataTable thead th,
    table.dataTable tbody td{
      white-space:nowrap;
      text-align:center;
      vertical-align:middle;
    }
    .dt-scroll-body{
      max-height:62vh !important;
    }

    /* Spinner overlay */
    .ap-overlay{
      position:fixed; inset:0;
      background:rgba(255,255,255,.65);
      display:none;
      align-items:center; justify-content:center;
      z-index:9999;
    }
    .ap-overlay .box{
      background:#fff;
      border:1px solid var(--ap-border);
      border-radius:14px;
      padding:18px 22px;
      box-shadow:0 10px 30px rgba(0,0,0,.12);
      display:flex; gap:12px; align-items:center;
      font-weight:800;
      color:var(--ap-blue);
    }
    .ap-form-label{ font-weight:800; color:#1b2b41; }
    .modal-title{ color:var(--ap-blue); font-weight:900; }
    .ap-help{ font-size:12px; color:#5b6b7f; }
  </style>
</head>

<body>

<div class="ap-overlay" id="apOverlay">
  <div class="box">
    <div class="spinner-border" role="status" aria-hidden="true"></div>
    <div>Procesando…</div>
  </div>
</div>

<div class="ap-titlebar">
  <h1 class="ap-title">
    <i class="fa-solid fa-address-book"></i>
    Catálogo - Contactos
  </h1>

  <div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-primary ap-btn" id="btnNuevo">
      <i class="fa-solid fa-plus"></i> Nuevo
    </button>

    <a class="btn btn-outline-primary ap-btn" href="../api/api_contactos.php?action=export_csv" target="_blank">
      <i class="fa-solid fa-file-csv"></i> Exportar CSV
    </a>

    <button class="btn btn-outline-primary ap-btn" id="btnImportar">
      <i class="fa-solid fa-file-import"></i> Importar CSV
    </button>
  </div>
</div>

<div class="ap-card">
  <div class="ap-card-h">
    <div class="d-flex gap-2 align-items-center">
      <i class="fa-solid fa-filter text-primary"></i>
      <div class="fw-bold" style="color:#1b2b41;">Búsqueda</div>
      <div class="ap-help">Escribe para filtrar por clave, nombre, correo, teléfonos o ubicación.</div>
    </div>
  </div>

  <div class="ap-table-wrap">
    <div class="table-responsive">
      <table id="tblContactos" class="table table-striped table-bordered table-hover w-100">
        <thead>
          <tr>
            <th style="min-width:140px;">Acciones</th>
            <th style="min-width:90px;">ID</th>
            <th style="min-width:120px;">Clave</th>
            <th style="min-width:160px;">Nombre</th>
            <th style="min-width:160px;">Apellido</th>
            <th style="min-width:200px;">Correo</th>
            <th style="min-width:140px;">Teléfono 1</th>
            <th style="min-width:140px;">Teléfono 2</th>
            <th style="min-width:140px;">País</th>
            <th style="min-width:140px;">Estado</th>
            <th style="min-width:140px;">Ciudad</th>
            <th style="min-width:260px;">Dirección</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal CRUD -->
<div class="modal fade" id="mdlContacto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-address-book"></i> <span id="mdlTitulo">Contacto</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="id">

        <div class="row g-3">
          <div class="col-md-3">
            <label class="ap-form-label form-label">Clave *</label>
            <input type="text" class="form-control" id="clave" maxlength="50" autocomplete="off">
          </div>

          <div class="col-md-4">
            <label class="ap-form-label form-label">Nombre *</label>
            <input type="text" class="form-control" id="nombre" maxlength="100" autocomplete="off">
          </div>

          <div class="col-md-5">
            <label class="ap-form-label form-label">Apellido</label>
            <input type="text" class="form-control" id="apellido" maxlength="100" autocomplete="off">
          </div>

          <div class="col-md-5">
            <label class="ap-form-label form-label">Correo</label>
            <input type="email" class="form-control" id="correo" maxlength="100" autocomplete="off">
          </div>

          <div class="col-md-3">
            <label class="ap-form-label form-label">Teléfono 1</label>
            <input type="text" class="form-control" id="telefono1" maxlength="50" autocomplete="off">
          </div>

          <div class="col-md-4">
            <label class="ap-form-label form-label">Teléfono 2</label>
            <input type="text" class="form-control" id="telefono2" maxlength="50" autocomplete="off">
          </div>

          <div class="col-md-3">
            <label class="ap-form-label form-label">País</label>
            <input type="text" class="form-control" id="pais" maxlength="100" autocomplete="off">
          </div>

          <div class="col-md-3">
            <label class="ap-form-label form-label">Estado</label>
            <input type="text" class="form-control" id="estado" maxlength="100" autocomplete="off">
          </div>

          <div class="col-md-3">
            <label class="ap-form-label form-label">Ciudad</label>
            <input type="text" class="form-control" id="ciudad" maxlength="100" autocomplete="off">
          </div>

          <div class="col-md-12">
            <label class="ap-form-label form-label">Dirección</label>
            <input type="text" class="form-control" id="direccion" maxlength="200" autocomplete="off">
          </div>
        </div>

        <div class="mt-3 ap-help">
          * Campos obligatorios: <b>Clave</b>, <b>Nombre</b>.
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary ap-btn" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark"></i> Cancelar
        </button>
        <button class="btn btn-primary ap-btn" id="btnGuardar">
          <i class="fa-solid fa-floppy-disk"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import CSV -->
<div class="modal fade" id="mdlImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-file-import"></i> Importar Contactos (CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="ap-help mb-2">
          Layout requerido (encabezados exactos): <b>clave,nombre,apellido,correo,telefono1,telefono2,pais,estado,ciudad,direccion</b>
          <br>Regla: UPSERT por <b>clave</b> (si existe actualiza, si no existe inserta).
        </div>

        <input type="file" id="csvFile" class="form-control" accept=".csv,text/csv">
        <div class="mt-2 ap-help">Tip: exporta primero para obtener el layout exacto.</div>

        <div class="alert alert-danger mt-3 d-none" id="impErr"></div>
        <div class="alert alert-success mt-3 d-none" id="impOk"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary ap-btn" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary ap-btn" id="btnProcesarImport">
          <i class="fa-solid fa-upload"></i> Procesar Importación
        </button>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-scroller@2.4.0/js/dataTables.scroller.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-scroller-bs5@2.4.0/js/scroller.bootstrap5.min.js"></script>

<script>
  const API = "../api/api_contactos.php";

  const overlay = document.getElementById('apOverlay');
  const showOverlay = (v=true) => overlay.style.display = v ? 'flex' : 'none';

  const mdlContacto = new bootstrap.Modal(document.getElementById('mdlContacto'));
  const mdlImport   = new bootstrap.Modal(document.getElementById('mdlImport'));

  let tabla = null;
  let modo  = "create"; // create | update

  function toast(msg, type="success"){
    // Minimalista: alert bootstrap
    const div = document.createElement('div');
    div.className = `alert alert-${type} position-fixed top-0 end-0 m-3 shadow`;
    div.style.zIndex = 99999;
    div.innerHTML = msg;
    document.body.appendChild(div);
    setTimeout(()=>div.remove(), 2400);
  }

  function limpiarForm(){
    document.getElementById('id').value = "";
    ['clave','nombre','apellido','correo','telefono1','telefono2','pais','estado','ciudad','direccion'].forEach(id=>{
      document.getElementById(id).value = "";
    });
  }

  async function apiPost(action, data){
    const form = new FormData();
    form.append('action', action);
    Object.keys(data||{}).forEach(k => form.append(k, data[k] ?? ''));

    const resp = await fetch(API, { method:'POST', body: form });
    const json = await resp.json().catch(()=>null);
    if(!json || json.ok !== true){
      throw new Error((json && (json.msg || json.error)) ? (json.msg || json.error) : 'Error desconocido');
    }
    return json;
  }

  async function apiGet(params){
    const url = new URL(API, window.location.origin);
    Object.keys(params||{}).forEach(k => url.searchParams.set(k, params[k]));
    const resp = await fetch(url.toString(), { method:'GET' });
    return await resp.json();
  }

  function renderAcciones(row){
    return `
      <div class="d-flex gap-1 justify-content-center">
        <button class="btn btn-sm btn-outline-primary ap-btn" title="Editar" onclick="editar(${row.id})">
          <i class="fa-solid fa-pen"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger ap-btn" title="Eliminar" onclick="eliminar(${row.id})">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    `;
  }

  window.editar = async function(id){
    try{
      showOverlay(true);
      const r = await apiGet({ action:'get', id });
      if(!r.ok) throw new Error(r.msg || 'No se pudo obtener el registro');

      limpiarForm();
      modo = "update";
      document.getElementById('mdlTitulo').innerText = "Editar Contacto";
      const d = r.data;

      document.getElementById('id').value        = d.id ?? '';
      document.getElementById('clave').value     = d.clave ?? '';
      document.getElementById('nombre').value    = d.nombre ?? '';
      document.getElementById('apellido').value  = d.apellido ?? '';
      document.getElementById('correo').value    = d.correo ?? '';
      document.getElementById('telefono1').value = d.telefono1 ?? '';
      document.getElementById('telefono2').value = d.telefono2 ?? '';
      document.getElementById('pais').value      = d.pais ?? '';
      document.getElementById('estado').value    = d.estado ?? '';
      document.getElementById('ciudad').value    = d.ciudad ?? '';
      document.getElementById('direccion').value = d.direccion ?? '';

      mdlContacto.show();
    }catch(e){
      toast(e.message, "danger");
    }finally{
      showOverlay(false);
    }
  }

  window.eliminar = async function(id){
    if(!confirm("¿Eliminar contacto (Hard Delete)?")) return;
    try{
      showOverlay(true);
      await apiPost('delete', { id });
      toast("Contacto eliminado", "success");
      tabla.ajax.reload(null, false);
    }catch(e){
      toast(e.message, "danger");
    }finally{
      showOverlay(false);
    }
  }

  document.getElementById('btnNuevo').addEventListener('click', ()=>{
    modo = "create";
    limpiarForm();
    document.getElementById('mdlTitulo').innerText = "Nuevo Contacto";
    mdlContacto.show();
  });

  document.getElementById('btnGuardar').addEventListener('click', async ()=>{
    const id = document.getElementById('id').value;

    const data = {
      id,
      clave: document.getElementById('clave').value.trim(),
      nombre: document.getElementById('nombre').value.trim(),
      apellido: document.getElementById('apellido').value.trim(),
      correo: document.getElementById('correo').value.trim(),
      telefono1: document.getElementById('telefono1').value.trim(),
      telefono2: document.getElementById('telefono2').value.trim(),
      pais: document.getElementById('pais').value.trim(),
      estado: document.getElementById('estado').value.trim(),
      ciudad: document.getElementById('ciudad').value.trim(),
      direccion: document.getElementById('direccion').value.trim()
    };

    if(!data.clave || !data.nombre){
      toast("Campos obligatorios: clave y nombre", "danger");
      return;
    }

    try{
      showOverlay(true);
      if(modo === "create"){
        await apiPost('create', data);
        toast("Contacto creado", "success");
      }else{
        await apiPost('update', data);
        toast("Contacto actualizado", "success");
      }
      mdlContacto.hide();
      tabla.ajax.reload(null, false);
    }catch(e){
      toast(e.message, "danger");
    }finally{
      showOverlay(false);
    }
  });

  document.getElementById('btnImportar').addEventListener('click', ()=>{
    document.getElementById('csvFile').value = "";
    document.getElementById('impErr').classList.add('d-none');
    document.getElementById('impOk').classList.add('d-none');
    mdlImport.show();
  });

  document.getElementById('btnProcesarImport').addEventListener('click', async ()=>{
    const f = document.getElementById('csvFile').files[0];
    const boxErr = document.getElementById('impErr');
    const boxOk  = document.getElementById('impOk');
    boxErr.classList.add('d-none'); boxOk.classList.add('d-none');

    if(!f){
      boxErr.innerText = "Selecciona un archivo CSV.";
      boxErr.classList.remove('d-none');
      return;
    }

    try{
      showOverlay(true);
      const form = new FormData();
      form.append('action', 'import_csv');
      form.append('archivo', f);

      const resp = await fetch(API, { method:'POST', body: form });
      const json = await resp.json().catch(()=>null);

      if(!json || json.ok !== true){
        const msg = (json && json.msg) ? json.msg : "Error en importación";
        const det = (json && Array.isArray(json.errores)) ? ("\n" + json.errores.slice(0,10).join("\n")) : "";
        boxErr.innerText = msg + det;
        boxErr.classList.remove('d-none');
        return;
      }

      boxOk.innerText = `Importación exitosa. OK: ${json.total_ok ?? 0}`;
      boxOk.classList.remove('d-none');

      tabla.ajax.reload(null, false);
    }catch(e){
      boxErr.innerText = e.message;
      boxErr.classList.remove('d-none');
    }finally{
      showOverlay(false);
    }
  });

  $(document).ready(function(){
    tabla = $('#tblContactos').DataTable({
      processing: true,
      serverSide: true,
      pageLength: 25,
      lengthChange: false,
      searching: true,
      scrollX: true,
      scrollY: "62vh",
      scroller: true,
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
      },
      ajax: {
        url: API,
        type: 'GET',
        data: function(d){
          d.action = 'list';
        },
        beforeSend: function(){ showOverlay(true); },
        complete: function(){ showOverlay(false); }
      },
      columns: [
        { data: null, orderable:false, render: function(_,__,row){ return renderAcciones(row); } },
        { data: 'id' },
        { data: 'clave' },
        { data: 'nombre' },
        { data: 'apellido' },
        { data: 'correo' },
        { data: 'telefono1' },
        { data: 'telefono2' },
        { data: 'pais' },
        { data: 'estado' },
        { data: 'ciudad' },
        { data: 'direccion' }
      ],
      order: [[1,'desc']]
    });
  });
</script>

</body>
</html>
