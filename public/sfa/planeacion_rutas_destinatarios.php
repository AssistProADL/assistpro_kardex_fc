<?php
// Ruta esperada: /public/sfa/planeacion_rutas_destinatarios.php
require_once __DIR__ . '/../bi/_menu_global.php';

// Sin sesión por ahora.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Planeación de Rutas | Asignación de Clientes</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --ap-font:10px; }
    body, .form-control, .form-select, .btn, .table { font-size: var(--ap-font) !important; }
    .ap-title{ font-weight:700; font-size:18px !important; }
    .ap-sub{ color:#6c757d; }
    .kpi-pill{ display:inline-flex; gap:6px; align-items:center; padding:3px 10px; border-radius:999px; background:#f1f3f5; border:1px solid #e9ecef; }
    .kpi-pill b{ font-size:11px !important; }
    .grid-wrap{
      border:1px solid #e9ecef; border-radius:10px; overflow:hidden;
      background:#fff;
    }
    .grid-scroll{
      max-height: 62vh;
      overflow:auto;
    }
    table{ white-space:nowrap; }
    th{ position:sticky; top:0; z-index:3; background:#f8f9fa; }
    .w-ctrl{ min-width:34px; text-align:center; }
    .w-small{ min-width:60px; }
    .w-med{ min-width:160px; }
    .w-big{ min-width:260px; }
    .badge-yes{ background:#198754; }
    .badge-no{ background:#6c757d; }
    .muted{ color:#6c757d; }
  </style>
</head>
<body class="bg-light">

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="ap-title"><i class="fa-solid fa-route me-2"></i>Planeación de Rutas | Asignación de Clientes</div>
      <div class="ap-sub">Almacén → Rutas (dependientes) → Clientes. Guardado de días de visita en <b>RelDayCli</b>.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnRefrescar"><i class="fa-solid fa-rotate me-1"></i>Refrescar</button>
      <button class="btn btn-success btn-sm" id="btnGuardarTop"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar planeación</button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">Almacén</label>
          <select class="form-select form-select-sm" id="selAlmacen"></select>
          <div class="small muted mt-1">Fuente: <span class="text-danger">../api/sfa/catalogo_rutas.php?mode=almacenes</span></div>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label mb-1">Ruta destino (global)</label>
          <select class="form-select form-select-sm" id="selRuta" disabled>
            <option value="">Seleccione Ruta</option>
          </select>
          <div class="small muted mt-1">Fuente: <span class="text-danger">../api/sfa/catalogo_rutas.php?almacen_id=...</span></div>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label mb-1">Buscar</label>
          <div class="input-group input-group-sm">
            <input type="text" id="txtBuscar" class="form-control" placeholder="Cliente / Destinatario / Colonia / CP">
            <button class="btn btn-primary" id="btnBuscar"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button>
            <button class="btn btn-outline-secondary" id="btnLimpiar"><i class="fa-solid fa-eraser me-1"></i>Limpiar</button>
          </div>
          <div class="small muted mt-1">Tip: Enter ejecuta búsqueda. La grilla se alimenta por ruta seleccionada.</div>
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="kpi-pill"><b id="kpiClientes">0</b> clientes</span>
          <span class="kpi-pill"><b id="kpiSel">0</b> seleccionados</span>
          <span class="kpi-pill"><b id="kpiAsign">0</b> asignados a ruta</span>
          <span class="kpi-pill" id="pillStatus"><b id="kpiStatus">OK</b></span>
          <span class="kpi-pill"><span class="muted">Ruta cargada:</span>&nbsp;<b id="kpiRuta">—</b></span>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
          <div class="me-2"><b>Días visita (global)</b> <span class="muted">(si una fila no trae días, puedes aplicar estos a los seleccionados)</span></div>
          <?php
            $dias = ['Lu','Ma','Mi','Ju','Vi','Sa','Do'];
            foreach($dias as $d){
              echo '<label class="form-check form-check-inline mb-0">
                      <input class="form-check-input day-global" type="checkbox" value="'.$d.'">
                      <span class="form-check-label">'.$d.'</span>
                    </label>';
            }
          ?>
          <button class="btn btn-outline-primary btn-sm" id="btnSelTodo"><i class="fa-solid fa-check-double me-1"></i>Seleccionar todo</button>
          <button class="btn btn-outline-secondary btn-sm" id="btnClearSel"><i class="fa-solid fa-ban me-1"></i>Limpiar selección</button>
          <button class="btn btn-success btn-sm" id="btnGuardar"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
        </div>
      </div>

      <div class="grid-wrap mt-3">
        <div class="grid-scroll">
          <table class="table table-sm table-hover mb-0" id="tbl">
            <thead>
              <tr>
                <th class="w-ctrl"><input type="checkbox" id="chkAll"></th>
                <th class="w-big">Cliente</th>
                <th class="w-big">Destinatario</th>
                <th class="w-ctrl">Lu</th>
                <th class="w-ctrl">Ma</th>
                <th class="w-ctrl">Mi</th>
                <th class="w-ctrl">Ju</th>
                <th class="w-ctrl">Vi</th>
                <th class="w-ctrl">Sa</th>
                <th class="w-ctrl">Do</th>
                <th class="w-small">Asignado</th>
                <th class="w-med">Ruta New</th>
                <th class="w-small">Sec</th>
                <th class="w-big">Dirección</th>
                <th class="w-med">Colonia</th>
                <th class="w-small">CP</th>
                <th class="w-med">Ciudad</th>
                <th class="w-med">Estado</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="18" class="text-center muted p-4">Seleccione un almacén y una ruta y luego Buscar/Refrescar.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="small muted mt-2">
        API clientes: <span class="text-danger">../api/sfa/clientes_asignacion_data.php</span> |
        API guardado: <span class="text-danger">../api/sfa/clientes_asignacion_save.php</span>
      </div>
    </div>
  </div>
</div>

<script>
const $ = (id)=>document.getElementById(id);

const selAlmacen = $('selAlmacen');
const selRuta    = $('selRuta');
const txtBuscar  = $('txtBuscar');
const tbody      = $('tbody');

const kpiClientes = $('kpiClientes');
const kpiSel      = $('kpiSel');
const kpiAsign    = $('kpiAsign');
const kpiStatus   = $('kpiStatus');
const pillStatus  = $('pillStatus');
const kpiRuta     = $('kpiRuta');

function setStatus(ok, msg){
  kpiStatus.textContent = msg || (ok ? 'OK' : 'Error API');
  pillStatus.style.background = ok ? '#e8fff3' : '#fff1f1';
  pillStatus.style.border = ok ? '1px solid #b7f0cf' : '1px solid #f3b7b7';
}

function getGlobalDays(){
  const checked = Array.from(document.querySelectorAll('.day-global:checked')).map(x=>x.value);
  const map = {Lu:0,Ma:0,Mi:0,Ju:0,Vi:0,Sa:0,Do:0};
  checked.forEach(d=>map[d]=1);
  return map;
}

function updateKpis(){
  const rows = tbody.querySelectorAll('tr[data-dest]');
  kpiClientes.textContent = rows.length;

  let sel=0, asg=0;
  rows.forEach(tr=>{
    const cb = tr.querySelector('.row-sel');
    if(cb && cb.checked) sel++;
    const asign = tr.getAttribute('data-asignado') || '0';
    if(asign==='1') asg++;
  });
  kpiSel.textContent = sel;
  kpiAsign.textContent = asg;
}

async function fetchJson(url, opts){
  const res = await fetch(url, opts || {});
  const txt = await res.text();
  try {
    return JSON.parse(txt);
  } catch(e){
    return {ok:0,error:'Respuesta no JSON', detalle: txt.substring(0,500)};
  }
}

async function cargarAlmacenes(){
  setStatus(true,'OK');
  selAlmacen.innerHTML = '<option value="">Cargando...</option>';
  selRuta.innerHTML = '<option value="">Seleccione Ruta</option>';
  selRuta.disabled = true;
  tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">Cargando almacenes...</td></tr>';

  const url = '../api/sfa/catalogo_rutas.php?mode=almacenes';
  const js = await fetchJson(url);
  if(!js.success){
    setStatus(false,'Error API');
    tbody.innerHTML = '<tr><td colspan="18" class="text-danger p-3">Error cargando almacenes: '+(js.error||'')+'</td></tr>';
    return;
  }

  const data = js.data || [];
  if(!data.length){
    selAlmacen.innerHTML = '<option value="">Sin almacenes con rutas</option>';
    tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">No hay almacenes con rutas.</td></tr>';
    return;
  }

  selAlmacen.innerHTML = '<option value="">Seleccione</option>' + data.map(a=>(
    `<option value="${a.id}">${a.nombre}</option>`
  )).join('');

  tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">Seleccione un almacén y una ruta y luego Buscar/Refrescar.</td></tr>';
  updateKpis();
}

async function cargarRutas(){
  const alm = selAlmacen.value;
  selRuta.innerHTML = '<option value="">Cargando...</option>';
  selRuta.disabled = true;
  tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">Seleccione una ruta y luego Buscar/Refrescar.</td></tr>';

  if(!alm){
    selRuta.innerHTML = '<option value="">Seleccione Ruta</option>';
    return;
  }

  const url = '../api/sfa/catalogo_rutas.php?almacen_id=' + encodeURIComponent(alm);
  const js = await fetchJson(url);

  if(!js.success){
    setStatus(false,'Error API');
    selRuta.innerHTML = '<option value="">Sin rutas</option>';
    return;
  }

  const rutas = js.data || [];
  if(!rutas.length){
    selRuta.innerHTML = '<option value="">Sin rutas</option>';
    selRuta.disabled = true;
    return;
  }

  selRuta.innerHTML = '<option value="">Seleccione Ruta</option>' + rutas.map(r=>(
    `<option value="${r.id}">${r.nombre}</option>`
  )).join('');
  selRuta.disabled = false;
  setStatus(true,'OK');
}

async function cargarClientes(){
  const alm = selAlmacen.value;
  const ruta = selRuta.value;
  const q = txtBuscar.value.trim();

  if(!alm || !ruta){
    setStatus(false,'Seleccione almacén y ruta');
    tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">Seleccione un almacén y una ruta y luego Buscar/Refrescar.</td></tr>';
    updateKpis();
    return;
  }

  setStatus(true,'OK');
  kpiRuta.textContent = ruta;
  tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">Cargando clientes...</td></tr>';

  const url = `../api/sfa/clientes_asignacion_data.php?almacen_id=${encodeURIComponent(alm)}&ruta_id=${encodeURIComponent(ruta)}&q=${encodeURIComponent(q)}`;
  const js = await fetchJson(url);

  if(js.ok !== 1){
    setStatus(false,'Error API');
    tbody.innerHTML = `<tr><td colspan="18" class="text-danger p-3">Error consultando clientes. ${js.error||''}<br><span class="small muted">${(js.detalle||'')}</span></td></tr>`;
    updateKpis();
    return;
  }

  const items = js.items || [];
  if(!items.length){
    tbody.innerHTML = '<tr><td colspan="18" class="text-center muted p-4">Sin resultados</td></tr>';
    updateKpis();
    return;
  }

  tbody.innerHTML = items.map((it,idx)=>{
    const cli = it.Cve_Clte ? `[${it.Cve_Clte}] ${it.Cliente||''}` : (it.Cliente||'');
    const dest = it.Id_Destinatario ? `[${it.Id_Destinatario}] ${it.Destinatario||''}` : (it.Destinatario||'');
    const asignado = (parseInt(it.Asignado||0)===1);
    return `
      <tr data-dest="${it.Id_Destinatario||''}" data-cli="${it.Cve_Cliente||''}" data-asignado="${asignado?1:0}">
        <td class="w-ctrl"><input type="checkbox" class="row-sel"></td>
        <td class="w-big">${cli}</td>
        <td class="w-big">${dest}</td>
        ${['Lu','Ma','Mi','Ju','Vi','Sa','Do'].map(d=>(
          `<td class="w-ctrl"><input type="checkbox" class="day" data-day="${d}" ${parseInt(it[d]||0)===1?'checked':''}></td>`
        )).join('')}
        <td class="w-small">${asignado?'<span class="badge badge-yes">Sí</span>':'<span class="badge badge-no">No</span>'}</td>
        <td class="w-med"><span class="muted">(global)</span></td>
        <td class="w-small"><input type="text" class="form-control form-control-sm sec" value="${it.Sec||''}"></td>
        <td class="w-big">${it.Direccion||''}</td>
        <td class="w-med">${it.Colonia||''}</td>
        <td class="w-small">${it.CP||''}</td>
        <td class="w-med">${it.Ciudad||''}</td>
        <td class="w-med">${it.Estado||''}</td>
      </tr>
    `;
  }).join('');

  setStatus(true,'OK');
  updateKpis();
}

function aplicarDiasGlobalASeleccion(){
  const g = getGlobalDays();
  const rows = tbody.querySelectorAll('tr[data-dest]');
  rows.forEach(tr=>{
    const cb = tr.querySelector('.row-sel');
    if(!cb || !cb.checked) return;
    tr.querySelectorAll('input.day').forEach(chk=>{
      const d = chk.getAttribute('data-day');
      if(g[d] === 1) chk.checked = true;
    });
  });
}

async function guardarPlaneacion(){
  const alm = selAlmacen.value;
  const ruta = selRuta.value;

  if(!alm || !ruta){
    alert('Seleccione almacén y ruta.');
    return;
  }

  // Aplica días globales a seleccionados (solo si el usuario marcó globales)
  aplicarDiasGlobalASeleccion();

  const payload = {
    almacen_id: parseInt(alm),
    ruta_id: parseInt(ruta),
    items: []
  };

  const rows = tbody.querySelectorAll('tr[data-dest]');
  rows.forEach(tr=>{
    const cb = tr.querySelector('.row-sel');
    if(!cb || !cb.checked) return;

    const dest = parseInt(tr.getAttribute('data-dest')||0);
    const cli  = parseInt(tr.getAttribute('data-cli')||0);
    const sec  = (tr.querySelector('input.sec')?.value || '').trim();

    const days = {Lu:0,Ma:0,Mi:0,Ju:0,Vi:0,Sa:0,Do:0};
    tr.querySelectorAll('input.day').forEach(chk=>{
      const d = chk.getAttribute('data-day');
      days[d] = chk.checked ? 1 : 0;
    });

    payload.items.push({
      Id_Destinatario: dest,
      Cve_Cliente: cli,
      Sec: sec,
      ...days
    });
  });

  if(payload.items.length===0){
    alert('No hay filas seleccionadas para guardar.');
    return;
  }

  const js = await fetchJson('../api/sfa/clientes_asignacion_save.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });

  if(js.ok !== 1){
    alert((js.error||'Error guardando') + (js.detalle?('\n'+js.detalle):''));
    setStatus(false,'Error API');
    return;
  }

  alert(js.message || 'Planeación guardada.');
  // Refresca desde BD para validar que lo guardado se refleje
  await cargarClientes();
}

$('btnRefrescar').addEventListener('click', cargarClientes);
$('btnBuscar').addEventListener('click', cargarClientes);
$('btnLimpiar').addEventListener('click', ()=>{ txtBuscar.value=''; cargarClientes(); });
$('btnSelTodo').addEventListener('click', ()=>{
  tbody.querySelectorAll('.row-sel').forEach(cb=>cb.checked=true);
  $('chkAll').checked=true;
  updateKpis();
});
$('btnClearSel').addEventListener('click', ()=>{
  tbody.querySelectorAll('.row-sel').forEach(cb=>cb.checked=false);
  $('chkAll').checked=false;
  updateKpis();
});
$('btnGuardar').addEventListener('click', guardarPlaneacion);
$('btnGuardarTop').addEventListener('click', guardarPlaneacion);

selAlmacen.addEventListener('change', async ()=>{
  await cargarRutas();
  kpiRuta.textContent = '—';
  setStatus(true,'OK');
});

selRuta.addEventListener('change', ()=>{
  kpiRuta.textContent = selRuta.value || '—';
  setStatus(true,'OK');
});

$('chkAll').addEventListener('change', (e)=>{
  tbody.querySelectorAll('.row-sel').forEach(cb=>cb.checked=e.target.checked);
  updateKpis();
});

txtBuscar.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); cargarClientes(); } });

tbody.addEventListener('change', (e)=>{
  if(e.target.classList.contains('row-sel')) updateKpis();
});

// Init
(async function init(){
  await cargarAlmacenes();
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
