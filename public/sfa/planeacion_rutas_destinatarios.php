<?php
// /public/sfa/planeacion_rutas_destinatarios.php
// UI: Planeación de rutas (Asignación de clientes/destinatarios)
// NOTA: NO modifica APIs. Solo arma correctamente los parámetros (almacen_id / ruta_id).

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Planeación de Rutas | Asignación de Clientes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap (usa el que ya tengas en tu proyecto; si falla, al menos no rompe JS) -->
  <style>
    body { font-size: 10px; }
    .ap-title { font-size: 18px; font-weight: 700; }
    .ap-sub  { font-size: 11px; color:#6b7280; }
    .kpi-pill { display:inline-block; padding:4px 10px; border-radius:999px; background:#f3f4f6; margin-right:6px; }
    .table thead th { position: sticky; top: 0; background: #fff; z-index: 2; }
    .table-responsive { max-height: 62vh; overflow:auto; }
    .daybox { transform: scale(1.05); }
    .muted { color:#9ca3af; }
    .tiny { font-size: 10px; }
    .badge-ok { background:#16a34a; }
    .badge-no { background:#6b7280; }

    .row-dirty { background:#fef3c7 !important; }
    #toastBox{
      position:fixed;
      bottom:20px;
      right:20px;
      min-width:320px;
      max-width:460px;
      padding:14px 16px;
      border-radius:10px;
      box-shadow:0 15px 35px rgba(0,0,0,.25);
      display:none;
      font-size:12px;
      white-space:pre-line;
      z-index:9999;
      transition: all .25s ease;
      color:#fff;
    }
  </style>
</head>

<body>
<div class="container-fluid py-2">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="ap-title">📍 Planeación de Rutas | Asignación de Clientes</div>
      <div class="ap-sub">Almacén → Rutas (dependientes) → Clientes. Guardado de días de visita en <b>RelDayCli</b>.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnRefrescar">⟳ Refrescar</button>
      <button class="btn btn-success btn-sm" id="btnGuardarTop" disabled>💾 Guardar planeación</button>
      <button class="btn btn-outline-danger btn-sm" id="btnUndoTop" disabled>↩ Deshacer</button>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-body">

      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-1"><b>Almacén</b></label>
          <select class="form-select form-select-sm" id="selAlmacen">
            <option value="">Cargando...</option>
          </select>
          <div class="tiny mt-1 muted">
            Fuente: <span class="text-danger">../api/catalogo_almacenes.php</span> + filtro rutas: <span class="text-danger">../api/sfa/catalogo_rutas.php?almacen_id=...</span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1"><b>Ruta destino (global)</b></label>
          <select class="form-select form-select-sm" id="selRuta" disabled>
            <option value="">Seleccione almacén</option>
          </select>
          <div class="tiny mt-1 muted">
            Fuente: <span class="text-danger">../api/sfa/catalogo_rutas.php?almacen_id=...</span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1"><b>Buscar</b></label>
          <div class="input-group input-group-sm">
            <input type="text" class="form-control" id="txtBuscar" placeholder="Cliente / Destinatario / Colonia / CP">
            <button class="btn btn-primary" id="btnBuscar">🔎 Buscar</button>
            <button class="btn btn-outline-secondary" id="btnLimpiar">Limpiar</button>
          </div>
          <div class="tiny mt-1 muted">Tip: Enter ejecuta búsqueda. La grilla se alimenta por la ruta seleccionada.</div>
        </div>
      </div>

      <hr class="my-2">

      <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="kpi-pill"><span id="kpiTotal">0</span> clientes</span>
        <span class="kpi-pill"><span id="kpiSel">0</span> seleccionados</span>
        <span class="kpi-pill"><span id="kpiAsig">0</span> asignados a ruta</span>
        <span class="kpi-pill"><span class="badge badge-ok text-white" id="badgeEstado">OK</span></span>
        <span class="kpi-pill">Ruta cargada: <b id="lblRutaCargada">—</b></span>
      </div>

      <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="tiny"><b>Días visita (global)</b> <span class="muted">(si una fila no trae días, puedes aplicar estos a los seleccionados)</span></div>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gLu"> Lu</label>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gMa"> Ma</label>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gMi"> Mi</label>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gJu"> Ju</label>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gVi"> Vi</label>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gSa"> Sa</label>
          <label class="form-check form-check-inline tiny mb-0"><input class="form-check-input daybox" type="checkbox" id="gDo"> Do</label>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-primary btn-sm" id="btnSelTodo">✓ Seleccionar todo</button>
          <button class="btn btn-outline-secondary btn-sm" id="btnClearSel">Limpiar selección</button>
          <button class="btn btn-success btn-sm" id="btnGuardar" disabled>💾 Guardar</button>
          <button class="btn btn-outline-danger btn-sm" id="btnUndo" disabled>↩ Deshacer</button>
        </div>
      </div>

    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead>
            <tr class="tiny">
              <th style="width:24px;"><input type="checkbox" id="chkAll"></th>
              <th style="min-width:220px;">Cliente</th>
              <th style="min-width:260px;">Destinatario</th>
              <th style="width:38px;">Lu</th>
              <th style="width:38px;">Ma</th>
              <th style="width:38px;">Mi</th>
              <th style="width:38px;">Ju</th>
              <th style="width:38px;">Vi</th>
              <th style="width:38px;">Sa</th>
              <th style="width:38px;">Do</th>
              <th style="width:80px;">Asignado</th>
              <th style="min-width:260px;">Rutas actuales</th>
              <th style="min-width:180px;">Dirección</th>
              <th style="min-width:160px;">Colonia</th>
              <th style="width:90px;">CP</th>
              <th style="min-width:140px;">Ciudad</th>
              <th style="min-width:120px;">Estado</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="17" class="text-center tiny muted py-3">Seleccione un almacén y una ruta y luego Buscar/Refrescar.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="tiny p-2">
        API clientes: <span class="text-danger">../api/sfa/clientes_asignacion_data.php</span> |
        API guardado: <span class="text-danger">../api/sfa/clientes_asignacion_save.php</span>
      </div>
    </div>
  </div>

</div>

<div id="toastBox"></div>

<script>
(function(){
  const el = id => document.getElementById(id);

  // ========= Enterprise UX (toast + dirty tracking) =========
  let cambiosPendientes = new Set();

  function showToast(msg, type="success", ms=4500){
    const box = document.getElementById('toastBox');
    if(!box){ alert(msg); return; }
    const colors = {
      success: "#065f46",
      warning: "#92400e",
      error:   "#7f1d1d",
      info:    "#1e3a8a"
    };
    box.style.background = colors[type] || colors.success;
    box.textContent = msg;
    box.style.display = "block";
    box.style.opacity = "1";
    window.clearTimeout(box._t1); window.clearTimeout(box._t2);
    box._t1 = window.setTimeout(()=>{
      box.style.opacity = "0";
      box._t2 = window.setTimeout(()=>{ box.style.display="none"; }, 250);
    }, ms);
  }

  function updateSaveButtons(){
    const dirty = cambiosPendientes.size > 0;
    const b1 = el('btnGuardar');
    const b2 = el('btnGuardarTop');
    const u1 = el('btnUndo');
    const u2 = el('btnUndoTop');
    [b1,b2,u1,u2].forEach(x=>{ if(x) x.disabled = !dirty; });

    if(b1){
      b1.classList.toggle('btn-warning', dirty);
      b1.classList.toggle('btn-success', !dirty);
    }
    if(b2){
      b2.classList.toggle('btn-warning', dirty);
      b2.classList.toggle('btn-success', !dirty);
    }
  }

  function markDirty(tr){
    if(!tr) return;
    const id = tr.dataset.idDest;
    if(!id) return;
    cambiosPendientes.add(id);
    tr.classList.add('row-dirty');
    updateSaveButtons();
  }

  function clearDirtyState(){
    cambiosPendientes.clear();
    tbody.querySelectorAll('tr.row-dirty').forEach(tr=>tr.classList.remove('row-dirty'));
    updateSaveButtons();
  }

  const selAlmacen = el('selAlmacen');
  const selRuta    = el('selRuta');
  const tbody      = el('tbody');

  const badgeEstado = el('badgeEstado');
  const lblRutaCargada = el('lblRutaCargada');

  const kpiTotal = el('kpiTotal');
  const kpiSel   = el('kpiSel');
  const kpiAsig  = el('kpiAsig');

  const setEstado = (txt, ok=true) => {
    badgeEstado.textContent = txt;
    badgeEstado.className = ok ? 'badge badge-ok text-white' : 'badge bg-danger text-white';
  };

  const escapeHtml = (s) => (s??'').toString()
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;");

  function getEmpresaFromAlmacenOption(){
    const opt = selAlmacen.options[selAlmacen.selectedIndex];
    if(!opt) return '';
    return opt.dataset.empresa || opt.dataset.idempresa || opt.dataset.idEmpresa || '';
  }

  function getGlobalDays(){
    return {
      Lu: el('gLu').checked ? 1:0,
      Ma: el('gMa').checked ? 1:0,
      Mi: el('gMi').checked ? 1:0,
      Ju: el('gJu').checked ? 1:0,
      Vi: el('gVi').checked ? 1:0,
      Sa: el('gSa').checked ? 1:0,
      Do: el('gDo').checked ? 1:0
    };
  }

  function parseDias(row){
    const d = {Lu:0,Ma:0,Mi:0,Ju:0,Vi:0,Sa:0,Do:0};

    const hasDirect = ['Lu','Ma','Mi','Ju','Vi','Sa','Do'].some(k => row[k] !== undefined);
    if(hasDirect){
      d.Lu = row.Lu ? 1:0; d.Ma = row.Ma ? 1:0; d.Mi = row.Mi ? 1:0;
      d.Ju = row.Ju ? 1:0; d.Vi = row.Vi ? 1:0; d.Sa = row.Sa ? 1:0; d.Do = row.Do ? 1:0;
      return d;
    }

    const bits = (row.dias_bits ?? row.diasBits ?? '').toString();
    if(bits.length >= 7){
      d.Lu = bits[0]==='1'?1:0;
      d.Ma = bits[1]==='1'?1:0;
      d.Mi = bits[2]==='1'?1:0;
      d.Ju = bits[3]==='1'?1:0;
      d.Vi = bits[4]==='1'?1:0;
      d.Sa = bits[5]==='1'?1:0;
      d.Do = bits[6]==='1'?1:0;
    }
    return d;
  }

  function rowTemplate(r){
    const idDest = r.id_destinatario ?? r.Id_Destinatario ?? r.idDestinatario ?? 0;
    const cveClte = r.Cve_Clte ?? r.cve_clte ?? r.cve_cliente ?? r.Cve_Cliente ?? '';
    const cveVend = r.Cve_Vendedor ?? r.cve_vendedor ?? 0;

    const clienteTxt = (cveClte ? `[${escapeHtml(cveClte)}] ` : '') + escapeHtml(r.cliente ?? r.Cliente ?? '');
    const destTxt = escapeHtml(r.razonsocial ?? r.Destinatario ?? r.destinatario ?? '');

    const dir = escapeHtml(r.direccion ?? '');
    const col = escapeHtml(r.colonia ?? '');
    const cp  = escapeHtml(r.cp ?? '');
    const cd  = escapeHtml(r.ciudad ?? '');
    const edo = escapeHtml(r.estado ?? '');

    const rutasAct = escapeHtml(r.rutas_actuales ?? r.rutas ?? '');

    const asig = (r.asignado_esta_ruta ?? r.asignado ?? r.asig ?? 0) ? 1 : 0;
    const badge = asig
      ? `<span class="badge badge-ok text-white">Sí</span>`
      : `<span class="badge badge-no text-white">No</span>`;

    const dias = parseDias(r);

    const mkDay = (k, v) => `<input type="checkbox" class="form-check-input daybox d-${k}" ${v? 'checked':''}>`;

    return `
      <tr class="tiny" data-id-dest="${idDest}" data-cve-clte="${escapeHtml(cveClte)}" data-cve-vend="${cveVend}">
        <td class="text-center"><input type="checkbox" class="form-check-input rowSel"></td>
        <td>${clienteTxt}</td>
        <td>${destTxt}</td>
        <td class="text-center">${mkDay('Lu',dias.Lu)}</td>
        <td class="text-center">${mkDay('Ma',dias.Ma)}</td>
        <td class="text-center">${mkDay('Mi',dias.Mi)}</td>
        <td class="text-center">${mkDay('Ju',dias.Ju)}</td>
        <td class="text-center">${mkDay('Vi',dias.Vi)}</td>
        <td class="text-center">${mkDay('Sa',dias.Sa)}</td>
        <td class="text-center">${mkDay('Do',dias.Do)}</td>
        <td class="text-center">${badge}</td>
        <td>${rutasAct}</td>
        <td>${dir}</td>
        <td>${col}</td>
        <td>${cp}</td>
        <td>${cd}</td>
        <td>${edo}</td>
      </tr>
    `;
  }

  function updateKPIs(){
    const rows = tbody.querySelectorAll('tr[data-id-dest]');
    const sel  = tbody.querySelectorAll('tr[data-id-dest] .rowSel:checked');
    let asig = 0;
    rows.forEach(tr=>{
      const badge = tr.querySelector('td:nth-child(11) .badge');
      if(badge && badge.textContent.trim().toLowerCase()==='sí') asig++;
    });

    kpiTotal.textContent = rows.length;
    kpiSel.textContent   = sel.length;
    kpiAsig.textContent  = asig;
  }

  async function cargarAlmacenes(){
    try{
      setEstado('Cargando...', true);

      let idsConRutas = new Set();
      try{
        const resR = await fetch('../api/sfa/catalogo_rutas.php?mode=almacenes', {cache:'no-store'});
        const jsR  = await resR.json();
        const arrR = Array.isArray(jsR) ? jsR : (jsR.data ?? jsR.almacenes ?? jsR.rows ?? []);
        arrR.forEach(a=>{
          const id = a.id ?? a.IdEmpresa ?? a.idempresa ?? a.almacen_id ?? a.id_almacen ?? a.IdAlmacen ?? '';
          const n  = parseInt(id||'0',10);
          if(n) idsConRutas.add(n);
        });
      }catch(_e){}

      const res = await fetch('../api/catalogo_almacenes.php', {cache:'no-store'});
      const js = await res.json();

      let data = Array.isArray(js) ? js : (js.data ?? js.almacenes ?? []);
      if(idsConRutas.size){
        data = data.filter(a=>{
          const id = a.id ?? a.Id ?? a.almacen_id ?? a.id_almacen ?? a.IdAlmacen ?? '';
          const n  = parseInt(id||'0',10);
          return n && idsConRutas.has(n);
        });
      }

      selAlmacen.innerHTML = `<option value="">Seleccione almacén</option>`;

      data.forEach(a=>{
        const id = a.id ?? a.Id ?? a.almacen_id ?? a.id_almacen ?? a.IdAlmacen ?? '';
        const nombre = a.nombre ?? a.Nombre ?? a.descripcion ?? a.Descripcion ?? '';
        const clave = a.clave ?? a.Clave ?? '';
        const idempresa = a.idempresa ?? a.IdEmpresa ?? a.id_empresa ?? '';

        if(!id) return;
        const label = (clave ? `${clave} - ` : '') + (nombre || `Almacén ${id}`);
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = label;
        if(idempresa) opt.dataset.empresa = idempresa;
        selAlmacen.appendChild(opt);
      });

      setEstado('OK', true);
    }catch(e){
      console.error(e);
      setEstado('Error', false);
      selAlmacen.innerHTML = `<option value="">Error cargando almacenes</option>`;
    }
  }

  async function cargarRutasPorAlmacen(){
    const almacenId = parseInt(selAlmacen.value||'0',10);
    selRuta.innerHTML = `<option value="">Seleccione Ruta</option>`;
    selRuta.disabled = true;
    lblRutaCargada.textContent = '—';

    tbody.innerHTML = `<tr><td colspan="17" class="text-center tiny muted py-3">Seleccione un almacén y una ruta y luego Buscar/Refrescar.</td></tr>`;
    clearDirtyState();
    updateKPIs();

    if(!almacenId){ return; }

    try{
      setEstado('Cargando rutas...', true);
      const url = `../api/sfa/catalogo_rutas.php?almacen_id=${encodeURIComponent(almacenId)}`;
      const res = await fetch(url, {cache:'no-store'});
      const js = await res.json();

      const rutas = js.data ?? js.rutas ?? (Array.isArray(js)? js : []);
      rutas.forEach(r=>{
        const id = r.id_ruta ?? r.id ?? r.ID_Ruta ?? r.IdRuta ?? '';
        const desc = r.descripcion ?? r.Descripcion ?? r.nombre ?? r.Nombre ?? '';
        const cve  = r.cve_ruta ?? r.Cve_Ruta ?? r.clave ?? r.Clave ?? '';
        if(!id) return;

        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = desc ? desc : (cve ? cve : `Ruta ${id}`);
        if(cve) opt.dataset.cve = cve;
        selRuta.appendChild(opt);
      });

      selRuta.disabled = false;
      setEstado('OK', true);
    }catch(e){
      console.error(e);
      setEstado('Error rutas', false);
      selRuta.innerHTML = `<option value="">Error cargando rutas</option>`;
      selRuta.disabled = true;
    }
  }

  async function cargarDestinatarios(){
    const almacenId = parseInt(selAlmacen.value||'0',10);
    const rutaId = parseInt(selRuta.value||'0',10);
    const q = (el('txtBuscar').value||'').trim();

    if(!almacenId || !rutaId){
      tbody.innerHTML = `<tr><td colspan="17" class="text-center tiny muted py-3">Seleccione un almacén y una ruta y luego Buscar/Refrescar.</td></tr>`;
      clearDirtyState();
      updateKPIs();
      return;
    }

    try{
      setEstado('Cargando clientes...', true);

      const empresa = getEmpresaFromAlmacenOption();

      const url =
        `../api/sfa/clientes_asignacion_data.php?` +
        `almacen_id=${encodeURIComponent(almacenId)}` +
        `&ruta_id=${encodeURIComponent(rutaId)}` +
        `&almacen=${encodeURIComponent(almacenId)}` +
        `&ruta=${encodeURIComponent(rutaId)}` +
        `&empresa=${encodeURIComponent(empresa)}` +
        `&q=${encodeURIComponent(q)}`;

      const res = await fetch(url, {cache:'no-store'});
      const js = await res.json();

      const ok = (js.ok==1) || (js.success===true) || (js.ok===true);
      if(!ok){
        const msg = js.error || js.msg || 'Error consultando clientes';
        tbody.innerHTML = `<tr><td colspan="17" class="text-danger tiny p-2">${escapeHtml(msg)}</td></tr>`;
        setEstado('Error API', false);
        updateKPIs();
        return;
      }

      const data = js.data ?? [];
      lblRutaCargada.textContent = selRuta.options[selRuta.selectedIndex]?.textContent || rutaId;

      if(!Array.isArray(data) || data.length===0){
        tbody.innerHTML = `<tr><td colspan="17" class="text-center tiny muted py-3">Sin resultados.</td></tr>`;
        setEstado('OK', true);
        clearDirtyState();
        updateKPIs();
        return;
      }

      tbody.innerHTML = data.map(rowTemplate).join('');
      setEstado('OK', true);
      clearDirtyState();
      updateKPIs();

    }catch(e){
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="17" class="text-danger tiny p-2">Error consultando clientes: ${escapeHtml(e.message||e)}</td></tr>`;
      setEstado('Error', false);
      updateKPIs();
    }
  }

  function seleccionarTodo(flag){
    tbody.querySelectorAll('.rowSel').forEach(chk => chk.checked = flag);
    updateKPIs();
  }

  function aplicarDiasGlobalASel(){
    const g = getGlobalDays();
    const selected = tbody.querySelectorAll('tr[data-id-dest] .rowSel:checked');
    selected.forEach(chk=>{
      const tr = chk.closest('tr');
      if(!tr) return;
      tr.querySelector('.d-Lu').checked = !!g.Lu;
      tr.querySelector('.d-Ma').checked = !!g.Ma;
      tr.querySelector('.d-Mi').checked = !!g.Mi;
      tr.querySelector('.d-Ju').checked = !!g.Ju;
      tr.querySelector('.d-Vi').checked = !!g.Vi;
      tr.querySelector('.d-Sa').checked = !!g.Sa;
      tr.querySelector('.d-Do').checked = !!g.Do;
      markDirty(tr);
    });
  }

  async function guardar(){
    const almacenId = parseInt(selAlmacen.value||'0',10);
    const rutaId = parseInt(selRuta.value||'0',10);
    if(!almacenId || !rutaId){
      showToast('Seleccione almacén y ruta.', 'warning');
      return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr[data-id-dest]'));
    const selectedRows = rows.filter(tr => tr.querySelector('.rowSel')?.checked);
    const candidate = selectedRows.length ? selectedRows : rows.filter(tr=>{
      return ['Lu','Ma','Mi','Ju','Vi','Sa','Do'].some(k => tr.querySelector('.d-'+k)?.checked);
    });

    if(candidate.length===0){
      showToast('No hay filas para guardar (seleccione filas o marque días).', 'warning');
      return;
    }

    candidate.forEach(tr=>{
      const any = ['Lu','Ma','Mi','Ju','Vi','Sa','Do'].some(k => tr.querySelector('.d-'+k)?.checked);
      if(!any){
        const g = getGlobalDays();
        tr.querySelector('.d-Lu').checked = !!g.Lu;
        tr.querySelector('.d-Ma').checked = !!g.Ma;
        tr.querySelector('.d-Mi').checked = !!g.Mi;
        tr.querySelector('.d-Ju').checked = !!g.Ju;
        tr.querySelector('.d-Vi').checked = !!g.Vi;
        tr.querySelector('.d-Sa').checked = !!g.Sa;
        tr.querySelector('.d-Do').checked = !!g.Do;
      }
    });

    const items = candidate.map(tr=>{
      return {
        id_destinatario: parseInt(tr.dataset.idDest||'0',10),
        cve_cliente: tr.dataset.cveClte || '',
        cve_vendedor: parseInt(tr.dataset.cveVend||'0',10),
        Lu: tr.querySelector('.d-Lu').checked ? 1:0,
        Ma: tr.querySelector('.d-Ma').checked ? 1:0,
        Mi: tr.querySelector('.d-Mi').checked ? 1:0,
        Ju: tr.querySelector('.d-Ju').checked ? 1:0,
        Vi: tr.querySelector('.d-Vi').checked ? 1:0,
        Sa: tr.querySelector('.d-Sa').checked ? 1:0,
        Do: tr.querySelector('.d-Do').checked ? 1:0
      };
    }).filter(x=>x.id_destinatario>0);

    // Si hay filas seleccionadas que quedaron SIN días, ofrecer desasignar (API soporta unassign)
    const sinDias = items.filter(it => !it.Lu && !it.Ma && !it.Mi && !it.Ju && !it.Vi && !it.Sa && !it.Do);
    if(sinDias.length){
      const ok = confirm(`Hay ${sinDias.length} destinatario(s) sin días marcados.\n¿Desea DESASIGNARLOS de la ruta?`);
      if(!ok){
        showToast('Operación cancelada.', 'info');
        return;
      }
      sinDias.forEach(it => it.unassign = 1);
    }

    try{
      setEstado('Guardando...', true);

      const payload = { almacen: almacenId, ruta: rutaId, items: items };
      const res = await fetch('../api/sfa/clientes_asignacion_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const js = await res.json();

      if(!(js.ok==1 || js.ok===true)){
        setEstado('Error guardando', false);
        showToast((js.error||'Error guardando') + (js.detalle?('\n'+js.detalle):''), 'error', 6500);
        return;
      }

      setEstado('OK', true);

      const totalCambios = (js.total_cambios ?? ((js.reldaycli_guardados??0)+(js.reldaycli_borrados??0)+(js.relclirutas_agregados??0)+(js.relclirutas_borrados??0)));

      if(totalCambios > 0){
        await cargarDestinatarios();
      } else {
        clearDirtyState();
      }

      const titulo = totalCambios>0 ? 'Planeación guardada ✅' : 'Sin cambios para guardar';
      const tipo   = totalCambios>0 ? 'success' : 'info';

      const resumen =
`${titulo}

Items procesados: ${js.items_procesados ?? 0}

Visitas (reldaycli):
  • Guardados: ${js.reldaycli_guardados ?? 0}
  • Eliminados: ${js.reldaycli_borrados ?? 0}

Asignaciones (relclirutas):
  • Nuevas: ${js.relclirutas_agregados ?? 0}
  • Eliminadas: ${js.relclirutas_borrados ?? 0}`;

      showToast(resumen, tipo, 6500);

    }catch(e){
      console.error(e);
      setEstado('Error guardando', false);
      showToast('Error guardando: ' + (e.message||e), 'error', 6500);
    }
  }

  // Marcar cambios cuando se tocan días / checkboxes
  tbody.addEventListener('change', (e)=>{
    const trg = e.target;
    const tr = trg.closest('tr[data-id-dest]');
    if(!tr) return;

    if(trg.classList && trg.classList.contains('daybox')){
      markDirty(tr);
    }
    updateKPIs();
  });

  // Eventos
  selAlmacen.addEventListener('change', async ()=>{
    await cargarRutasPorAlmacen();
  });

  selRuta.addEventListener('change', async ()=>{
    const txt = selRuta.options[selRuta.selectedIndex]?.textContent || '—';
    lblRutaCargada.textContent = txt;
    await cargarDestinatarios();
  });

  el('btnBuscar').addEventListener('click', cargarDestinatarios);
  el('btnLimpiar').addEventListener('click', ()=>{ el('txtBuscar').value=''; cargarDestinatarios(); });
  el('txtBuscar').addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); cargarDestinatarios(); } });

  el('btnRefrescar').addEventListener('click', cargarDestinatarios);
  el('btnGuardarTop').addEventListener('click', guardar);
  el('btnGuardar').addEventListener('click', guardar);

  el('btnSelTodo').addEventListener('click', ()=>seleccionarTodo(true));
  el('btnClearSel').addEventListener('click', ()=>seleccionarTodo(false));
  el('chkAll').addEventListener('change', (e)=>seleccionarTodo(e.target.checked));

  ['gLu','gMa','gMi','gJu','gVi','gSa','gDo'].forEach(id=>{
    el(id).addEventListener('change', ()=>aplicarDiasGlobalASel());
  });

  // Undo (recarga lo último guardado desde API)
  function doUndo(){
    if(!cambiosPendientes.size) return;
    const ok = confirm('¿Descartar cambios pendientes?');
    if(!ok) return;
    clearDirtyState();
    cargarDestinatarios();
    showToast('Cambios descartados.', 'info');
  }

  el('btnUndoTop')?.addEventListener('click', doUndo);
  el('btnUndo')?.addEventListener('click', doUndo);

  // Aviso al salir si hay cambios sin guardar
  window.addEventListener('beforeunload', function (e) {
    if (cambiosPendientes.size > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // Boot
  (async function init(){
    await cargarAlmacenes();
    clearDirtyState();
    setEstado('OK', true);
  })();

})();
</script>

</body>
</html>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
