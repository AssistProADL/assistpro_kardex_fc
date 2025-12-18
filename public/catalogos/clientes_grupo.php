<?php
// public/catalogos/clientes_grupo.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>AssistPro | Grupo de Clientes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --ap-blue:#0B5ED7;
      --ap-blue-2:#0A58CA;
      --ap-bg:#F6F8FB;
      --ap-card:#FFFFFF;
      --ap-border:#E6EAF2;
      --ap-text:#1F2A37;
    }
    body{ background:var(--ap-bg); color:var(--ap-text); }
    .ap-titlebar{
      background: var(--ap-card);
      border:1px solid var(--ap-border);
      border-radius:14px;
      padding:14px 16px;
      margin:14px 14px 10px 14px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
    }
    .ap-title{
      display:flex; align-items:center; gap:10px;
      font-weight:800; color:var(--ap-blue);
      margin:0; font-size:18px;
    }
    .ap-title i{ font-size:18px; }
    .ap-actions{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
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
    table.ap-grid{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      font-size:10px;
      white-space:nowrap;
    }
    table.ap-grid thead th{
      position:sticky; top:0;
      background:#EEF4FF;
      color:#0B2F6A;
      z-index:2;
      border-bottom:1px solid var(--ap-border);
      padding:8px 8px;
      text-align:center;
    }
    table.ap-grid td{
      border-bottom:1px solid #F0F3FA;
      padding:6px 8px;
      text-align:center;
      background:#fff;
    }
    table.ap-grid tbody tr:hover td{ background:#F7FAFF; }
    .ap-col-actions{ position:sticky; left:0; z-index:3; }
    table.ap-grid thead .ap-col-actions{ background:#E3EEFF; z-index:4; }
    table.ap-grid tbody .ap-col-actions{ background:#fff; }
    .ap-btn{
      border:1px solid var(--ap-border);
      background:#fff;
      padding:6px 10px;
      border-radius:10px;
      font-size:12px;
    }
    .ap-btn-primary{
      background:var(--ap-blue);
      color:#fff;
      border-color:var(--ap-blue);
    }
    .ap-btn-primary:hover{ background:var(--ap-blue-2); }
    .ap-badge-inac{
      background:#FFE7E7; color:#9B1C1C; border:1px solid #FFD1D1;
      padding:2px 8px; border-radius:999px; font-size:10px;
    }
    .ap-badge-act{
      background:#E9FCEB; color:#166534; border:1px solid #C7F9CC;
      padding:2px 8px; border-radius:999px; font-size:10px;
    }
    .ap-spinner{
      display:none;
      position:fixed;
      inset:0;
      background:rgba(15,23,42,.35);
      z-index:9999;
      align-items:center;
      justify-content:center;
    }
    .ap-spinner .box{
      background:#fff;
      border:1px solid var(--ap-border);
      border-radius:16px;
      padding:18px 22px;
      display:flex;
      align-items:center;
      gap:12px;
      font-weight:700;
    }
    .ap-modal .form-label{ font-weight:700; font-size:12px; }
    .ap-modal .form-control, .ap-modal .form-select{ font-size:12px; }
  </style>
</head>

<body>

<div class="ap-spinner" id="apSpinner">
  <div class="box">
    <div class="spinner-border" role="status" aria-hidden="true"></div>
    <div>Procesando información…</div>
  </div>
</div>

<div class="ap-titlebar">
  <h1 class="ap-title">
    <i class="fa-solid fa-layer-group"></i>
    Catálogo | Grupo de Clientes
  </h1>

  <div class="ap-actions">
    <input id="txtQ" class="form-control form-control-sm" style="width:240px; font-size:12px;" placeholder="Buscar (clave o descripción)…">
    <select id="selActivo" class="form-select form-select-sm" style="width:170px; font-size:12px;">
      <option value="">Estatus: Todos</option>
      <option value="1" selected>Activos</option>
      <option value="0">Inactivos</option>
    </select>

    <button class="ap-btn ap-btn-primary" id="btnNuevo"><i class="fa-solid fa-plus"></i> Nuevo</button>
    <button class="ap-btn" id="btnExport"><i class="fa-solid fa-file-export"></i> Exportar CSV</button>

    <label class="ap-btn mb-0" style="cursor:pointer;">
      <i class="fa-solid fa-file-import"></i> Importar CSV
      <input type="file" id="fileCsv" accept=".csv,text/csv" hidden>
    </label>
  </div>
</div>

<div class="ap-card">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
      <span class="badge text-bg-primary" id="lblTotal">0</span>
      <span>Total registros</span>
    </div>
    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
      <button class="ap-btn" id="btnPrev"><i class="fa-solid fa-chevron-left"></i></button>
      <span>Página <b id="lblPage">1</b></span>
      <button class="ap-btn" id="btnNext"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
  </div>

  <div class="ap-table-wrap" style="max-height: 62vh;">
    <table class="ap-grid" id="tbl">
      <thead>
        <tr>
          <th class="ap-col-actions">Acciones</th>
          <th>id</th>
          <th>cve_grupo</th>
          <th>des_grupo</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tbody"></tbody>
    </table>
  </div>
</div>

<!-- Modal Alta/Edición -->
<div class="modal fade ap-modal" id="mdl" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlTitle" style="color:var(--ap-blue); font-weight:800;">
          <i class="fa-solid fa-pen-to-square"></i> Registro
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="frm" class="row g-2">
          <input type="hidden" name="id" id="f_id">

          <div class="col-md-4">
            <label class="form-label">cve_grupo</label>
            <input class="form-control" name="cve_grupo" id="f_cve" maxlength="50" autocomplete="off">
          </div>

          <div class="col-md-8">
            <label class="form-label">des_grupo</label>
            <input class="form-control" name="des_grupo" id="f_des" maxlength="200" autocomplete="off">
          </div>

          <div class="col-md-4">
            <label class="form-label">Activo</label>
            <select class="form-select" name="Activo" id="f_activo">
              <option value="1">1</option>
              <option value="0">0</option>
            </select>
          </div>
        </form>
        <div class="small text-muted mt-2">
          Nota: <b>cve_grupo</b> se guarda en MAYÚSCULAS para estandarizar segmentación.
        </div>
      </div>
      <div class="modal-footer">
        <button class="ap-btn" data-bs-dismiss="modal"><i class="fa-regular fa-circle-xmark"></i> Cancelar</button>
        <button class="ap-btn ap-btn-primary" id="btnGuardar"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const API = "../api/clientes_grupo_api.php";
  let PAGE = 1;
  const LIMIT = 25;
  let CURRENT = [];

  const el = (id)=>document.getElementById(id);
  const sp = (on=true)=>el('apSpinner').style.display = on?'flex':'none';
  const safe = (s)=>String(s ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');

  function badgeActivo(v){
    const n = parseInt(v ?? 1, 10);
    return n===1 ? `<span class="ap-badge-act">ACTIVO</span>` : `<span class="ap-badge-inac">INACTIVO</span>`;
  }

  async function apiGet(params){
    const url = API + "?" + new URLSearchParams(params).toString();
    const r = await fetch(url, {cache:'no-store'});
    return await r.json();
  }
  async function apiPost(action, payload, isForm=false){
    let opt = { method:'POST', cache:'no-store' };
    if (isForm){
      opt.body = payload;
    } else {
      opt.headers = {'Content-Type':'application/json; charset=utf-8'};
      opt.body = JSON.stringify(payload);
    }
    const r = await fetch(API + "?action=" + encodeURIComponent(action), opt);
    return await r.json();
  }

  function render(rows){
    const tb = [];
    rows.forEach(r=>{
      tb.push(`<tr>`);
      tb.push(`<td class="ap-col-actions">
        <div class="d-flex gap-1 justify-content-center">
          <button class="btn btn-sm btn-outline-primary" style="font-size:10px; padding:2px 6px;" onclick="editRow(${r.id})" title="Editar">
            <i class="fa-solid fa-pen"></i>
          </button>
          ${
            parseInt(r.Activo,10)===1
            ? `<button class="btn btn-sm btn-outline-warning" style="font-size:10px; padding:2px 6px;" onclick="toggleActivo(${r.id},0)" title="Inactivar">
                <i class="fa-solid fa-lock"></i>
              </button>`
            : `<button class="btn btn-sm btn-outline-success" style="font-size:10px; padding:2px 6px;" onclick="toggleActivo(${r.id},1)" title="Recuperar">
                <i class="fa-solid fa-rotate-left"></i>
              </button>`
          }
          <button class="btn btn-sm btn-outline-danger" style="font-size:10px; padding:2px 6px;" onclick="hardDelete(${r.id})" title="Eliminar">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </td>`);

      tb.push(`<td>${safe(r.id)}</td>`);
      tb.push(`<td>${safe(r.cve_grupo)}</td>`);
      tb.push(`<td>${safe(r.des_grupo)}</td>`);
      tb.push(`<td>${badgeActivo(r.Activo)}</td>`);
      tb.push(`</tr>`);
    });
    el('tbody').innerHTML = tb.join('') || `<tr><td colspan="5" style="padding:18px; text-align:center;">Sin información</td></tr>`;
  }

  async function load(){
    sp(true);
    try{
      const q = el('txtQ').value.trim();
      const activo = el('selActivo').value;

      const resp = await apiGet({action:'list', page: PAGE, limit: LIMIT, q, activo});
      if(!resp.ok) throw new Error(resp.msg || 'Error consultando datos.');

      CURRENT = resp.data || [];
      el('lblTotal').textContent = resp.total ?? 0;
      el('lblPage').textContent = PAGE;
      render(CURRENT);
    }catch(e){
      el('tbody').innerHTML = `<tr><td colspan="5" style="padding:18px; text-align:center;"><b>Error:</b> ${safe(e.message)}</td></tr>`;
    }finally{
      sp(false);
    }
  }

  function openModal(title){
    el('mdlTitle').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> ${safe(title)}`;
    new bootstrap.Modal(el('mdl')).show();
  }

  function clearForm(){
    el('f_id').value = '';
    el('f_cve').value = '';
    el('f_des').value = '';
    el('f_activo').value = '1';
  }

  window.editRow = function(id){
    const r = CURRENT.find(x=>String(x.id)===String(id));
    if(!r) return;

    el('f_id').value = r.id;
    el('f_cve').value = r.cve_grupo ?? '';
    el('f_des').value = r.des_grupo ?? '';
    el('f_activo').value = String(r.Activo ?? '1');

    openModal('Editar Grupo de Cliente');
  }

  el('btnNuevo').addEventListener('click', ()=>{
    clearForm();
    openModal('Nuevo Grupo de Cliente');
  });

  el('btnGuardar').addEventListener('click', async ()=>{
    const data = {
      id: el('f_id').value,
      cve_grupo: (el('f_cve').value || '').toUpperCase().trim(),
      des_grupo: (el('f_des').value || '').trim(),
      Activo: el('f_activo').value
    };

    sp(true);
    try{
      const resp = await apiPost('save', {data});
      if(!resp.ok) throw new Error(resp.msg || 'No se pudo guardar.');
      await load();
      bootstrap.Modal.getInstance(el('mdl')).hide();
      alert(resp.msg);
    }catch(e){
      alert("Error: " + e.message);
    }finally{
      sp(false);
    }
  });

  window.toggleActivo = async function(id, val){
    if(!confirm(val===1 ? "¿Recuperar (Activo=1) este grupo?" : "¿Inactivar (Activo=0) este grupo?")) return;
    sp(true);
    try{
      const resp = await apiPost('toggle', {id, Activo: val});
      if(!resp.ok) throw new Error(resp.msg || 'No se pudo actualizar Activo.');
      await load();
      alert(resp.msg);
    }catch(e){
      alert("Error: " + e.message);
    }finally{
      sp(false);
    }
  }

  window.hardDelete = async function(id){
    if(!confirm("¿Eliminar (Hard Delete) este registro? No se puede revertir.")) return;
    sp(true);
    try{
      const resp = await apiPost('delete', {id});
      if(!resp.ok) throw new Error(resp.msg || 'No se pudo eliminar.');
      await load();
      alert(resp.msg);
    }catch(e){
      alert("Error: " + e.message);
    }finally{
      sp(false);
    }
  }

  el('btnExport').addEventListener('click', async ()=>{
    sp(true);
    try{
      const activo = el('selActivo').value;
      const resp = await apiGet({action:'export', activo});
      if(!resp.ok) throw new Error(resp.msg || 'No se pudo exportar.');

      const blob = new Blob([resp.csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = resp.filename || 'c_gpoclientes.csv';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }catch(e){
      alert("Error: " + e.message);
    }finally{
      sp(false);
    }
  });

  el('fileCsv').addEventListener('change', async (ev)=>{
    const f = ev.target.files?.[0];
    if(!f) return;
    if(!confirm("¿Importar CSV? Layout: id,cve_grupo,des_grupo,Activo")) { ev.target.value=''; return; }

    const form = new FormData();
    form.append('file', f);

    sp(true);
    try{
      const r = await fetch(API + "?action=import", { method:'POST', body: form });
      const j = await r.json();
      if(!j.ok) throw new Error(j.msg || 'Error importando.');
      await load();
      alert(j.msg);
    }catch(e){
      alert("Error: " + e.message);
    }finally{
      sp(false);
      ev.target.value='';
    }
  });

  el('btnPrev').addEventListener('click', async ()=>{
    if(PAGE<=1) return;
    PAGE--; await load();
  });
  el('btnNext').addEventListener('click', async ()=>{
    PAGE++; await load();
  });

  let t=null;
  el('txtQ').addEventListener('input', ()=>{
    clearTimeout(t);
    t=setTimeout(()=>{ PAGE=1; load(); }, 350);
  });
  el('selActivo').addEventListener('change', ()=>{
    PAGE=1; load();
  });

  (async ()=>{ await load(); })();
</script>

</body>
</html>
