<?php
// /public/sfa/planeacion_rutas_destinatarios.php
// UI: planeación rutas -> asignación clientes -> guardado días en reldaycli

$baseApi = '../api';
$apiAlmacenes = $baseApi . '/catalogo_almacenes.php';
$apiRutas     = $baseApi . '/sfa/catalogo_rutas.php';
$apiClientes  = $baseApi . '/sfa/clientes_asignacion_data.php';
$apiSave      = $baseApi . '/sfa/clientes_asignacion_save.php';

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Planeación de Rutas | Asignación de Clientes</title>

  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/fontawesome/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --ap-font:10px; }
    body{ font-size:var(--ap-font); }
    .ap-title{ font-size:18px; font-weight:800; }
    .ap-sub{ font-size:12px; color:#6b7280; }
    .ap-chip{ display:inline-block; padding:2px 8px; border-radius:999px; background:#f3f4f6; margin-right:6px; }
    .ap-chip.ok{ background:#e8fff1; border:1px solid #b6f2c6; }
    .ap-chip.err{ background:#ffecec; border:1px solid #ffbcbc; }
    .ap-table-wrap{
      border:1px solid #e5e7eb; border-radius:10px;
      overflow:auto;            /* scroll H y V */
      max-height: 58vh;         /* V */
      background:#fff;
    }
    table{ font-size:var(--ap-font); min-width:1400px; } /* H */
    thead th{ position:sticky; top:0; background:#fff; z-index:2; }
    th,td{ vertical-align:middle; white-space:nowrap; }
    .day-cell{ text-align:center; }
    .day-cell input{ transform:scale(.95); }
    .btn{ font-size:var(--ap-font); }
    .form-select,.form-control{ font-size:var(--ap-font); }
  </style>
</head>

<body>
<?php
// menú global si existe
$menu = __DIR__ . '/../bi/_menu_global.php';
if (file_exists($menu)) include $menu;
?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="ap-title"><i class="fa-solid fa-route me-2"></i>Planeación de Rutas | Asignación de Clientes</div>
      <div class="ap-sub">Almacén → Rutas (dependientes) → Clientes. Guardado de días de visita en <b>RelDayCli</b>.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary" id="btnRefrescarTop"><i class="fa-solid fa-rotate"></i> Refrescar</button>
      <button class="btn btn-success" id="btnGuardarTop"><i class="fa-solid fa-floppy-disk"></i> Guardar planeación</button>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label mb-1"><b>Almacén (IdEmpresa)</b></label>
          <select class="form-select" id="selAlmacen">
            <option value="">Cargando...</option>
          </select>
          <div class="small text-muted mt-1">Fuente: <span class="text-danger"><?=htmlspecialchars($apiAlmacenes)?></span> + filtro rutas: <span class="text-danger"><?=htmlspecialchars($apiRutas)?>?mode=almacenes</span></div>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1"><b>Ruta destino (global)</b></label>
          <select class="form-select" id="selRuta" disabled>
            <option value="">Seleccione almacén</option>
          </select>
          <div class="small text-muted mt-1">Fuente: <span class="text-danger"><?=htmlspecialchars($apiRutas)?>?almacen_id=...</span></div>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1"><b>Buscar</b></label>
          <div class="input-group">
            <input class="form-control" id="txtBuscar" placeholder="Cliente / Destinatario / Colonia / CP">
            <button class="btn btn-primary" id="btnBuscar"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            <button class="btn btn-outline-secondary" id="btnLimpiar"><i class="fa-solid fa-eraser"></i> Limpiar</button>
          </div>
          <div class="small text-muted mt-1">Tip: Enter ejecuta búsqueda. La grilla NO se filtra por ruta; ruta aplica a guardado.</div>
        </div>
      </div>

      <hr class="my-3">

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="ap-chip" id="kpiClientes">0 clientes</span>
          <span class="ap-chip" id="kpiSel">0 seleccionados</span>
          <span class="ap-chip" id="kpiAsig">0 asignados a ruta</span>
          <span class="ap-chip ok" id="kpiEstado">OK</span>
          <span class="small text-muted" id="lblRutaCargada"></span>
        </div>

        <div class="d-flex align-items-center gap-2">
          <div class="small"><b>Días de visita (global)</b> <span class="text-muted">(aplica al Guardado para los seleccionados)</span></div>
          <label class="ms-2"><input type="checkbox" id="gLu"> Lu</label>
          <label><input type="checkbox" id="gMa"> Ma</label>
          <label><input type="checkbox" id="gMi"> Mi</label>
          <label><input type="checkbox" id="gJu"> Ju</label>
          <label><input type="checkbox" id="gVi"> Vi</label>
          <label><input type="checkbox" id="gSa"> Sa</label>
          <label><input type="checkbox" id="gDo"> Do</label>

          <button class="btn btn-outline-primary ms-2" id="btnSelTodo"><i class="fa-regular fa-square-check"></i> Seleccionar todo</button>
          <button class="btn btn-outline-secondary" id="btnSelNada"><i class="fa-regular fa-square"></i> Limpiar selección</button>
          <button class="btn btn-success" id="btnGuardar"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="ap-table-wrap">
    <table class="table table-hover table-sm mb-0" id="tbl">
      <thead>
        <tr>
          <th style="width:28px;"><input type="checkbox" id="chkAll"></th>
          <th>Cliente</th>
          <th>Destinatario</th>

          <!-- Días después de destinatario -->
          <th class="day-cell">Lu</th>
          <th class="day-cell">Ma</th>
          <th class="day-cell">Mi</th>
          <th class="day-cell">Ju</th>
          <th class="day-cell">Vi</th>
          <th class="day-cell">Sa</th>
          <th class="day-cell">Do</th>

          <th>Asignado</th>
          <th>Ruta New</th>
          <th>Sec</th>

          <!-- Dirección al final -->
          <th>Dirección</th>
          <th>Colonia</th>
          <th>CP</th>
          <th>Ciudad</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div class="small text-muted mt-2" id="diag"></div>
</div>

<script>
const API_ALM = <?=json_encode($apiAlmacenes)?>;
const API_RUT = <?=json_encode($apiRutas)?>;
const API_CLI = <?=json_encode($apiClientes)?>;
const API_SAV = <?=json_encode($apiSave)?>;

const selAlm  = document.getElementById('selAlmacen');
const selRut  = document.getElementById('selRuta');
const tb      = document.getElementById('tb');
const txtBuscar = document.getElementById('txtBuscar');

const kpiClientes = document.getElementById('kpiClientes');
const kpiSel = document.getElementById('kpiSel');
const kpiAsig = document.getElementById('kpiAsig');
const kpiEstado = document.getElementById('kpiEstado');
const lblRutaCargada = document.getElementById('lblRutaCargada');
const diag = document.getElementById('diag');

const gDays = {
  Lu: document.getElementById('gLu'),
  Ma: document.getElementById('gMa'),
  Mi: document.getElementById('gMi'),
  Ju: document.getElementById('gJu'),
  Vi: document.getElementById('gVi'),
  Sa: document.getElementById('gSa'),
  Do: document.getElementById('gDo'),
};

let currentRows = []; // datos de clientes en memoria

function setEstado(ok, msg='OK'){
  kpiEstado.textContent = msg;
  kpiEstado.classList.remove('ok','err');
  kpiEstado.classList.add(ok ? 'ok':'err');
}

function esc(s){ return (s??'').toString().replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;'); }

async function jget(url){
  const r = await fetch(url, {cache:'no-store'});
  return await r.json();
}

async function cargarAlmacenes(){
  setEstado(true,'OK');
  selAlm.innerHTML = `<option value="">Cargando...</option>`;
  try{
    const data = await jget(API_ALM);
    // soporta formatos: {ok:1,data:[...]} o array directo
    const rows = Array.isArray(data) ? data : (data.data ?? []);
    selAlm.innerHTML = `<option value="">Seleccione</option>` + rows.map(x=>{
      const id = x.id ?? x.Id ?? x.ID ?? x.id_almacen ?? '';
      const nom = x.nombre ?? x.Nombre ?? x.name ?? '';
      return `<option value="${esc(id)}">${esc(nom)}</option>`;
    }).join('');
  }catch(e){
    setEstado(false,'Error almacenes');
    selAlm.innerHTML = `<option value="">Error cargando</option>`;
    diag.textContent = e.message;
  }
}

async function cargarRutasPorAlmacen(almacenId){
  selRut.disabled = true;
  selRut.innerHTML = `<option value="">Cargando rutas...</option>`;
  try{
    const data = await jget(`${API_RUT}?almacen_id=${encodeURIComponent(almacenId)}`);
    const rows = data.data ?? data.rows ?? data ?? [];
    selRut.innerHTML = `<option value="">Seleccione Ruta</option>` + rows.map(x=>{
      const id = x.id ?? x.ID_Ruta ?? x.id_ruta ?? x.IdRuta ?? x.Cve_Ruta ?? '';
      const nom = x.nombre ?? x.Nombre ?? x.descripcion ?? x.Ruta ?? x.Cve ?? '';
      return `<option value="${esc(id)}">${esc(nom)}</option>`;
    }).join('');
    selRut.disabled = false;
  }catch(e){
    setEstado(false,'Error rutas');
    selRut.innerHTML = `<option value="">Sin rutas</option>`;
    diag.textContent = e.message;
  }
}

function getSelectedIds(){
  return [...document.querySelectorAll('.rowchk:checked')].map(x=>x.getAttribute('data-id')).filter(Boolean);
}

function updateKpis(){
  kpiClientes.textContent = `${currentRows.length} clientes`;
  const sel = getSelectedIds().length;
  kpiSel.textContent = `${sel} seleccionados`;
  const asig = currentRows.filter(r=> (r.asignado_ruta??0)==1).length;
  kpiAsig.textContent = `${asig} asignados a ruta`;
}

function renderRows(rows){
  currentRows = rows;
  tb.innerHTML = rows.map(r=>{
    const idDest = r.id_destinatario ?? r.Id_Destinatario ?? r.idDestinatario ?? '';
    const cte = r.Cve_Clte ?? r.cve_clte ?? r.Cve_Cliente ?? r.cve_cliente ?? '';
    const cliente = r.cliente ?? r.RazonSocial ?? r.razonsocial ?? '';
    const destinatario = r.destinatario ?? r.razonsocial_dest ?? r.razonsocial ?? '';
    const dir = r.direccion ?? r.CalleNumero ?? '';
    const col = r.colonia ?? r.Colonia ?? '';
    const cp  = r.cp ?? r.CodigoPostal ?? r.postal ?? '';
    const cd  = r.ciudad ?? r.Ciudad ?? '';
    const edo = r.estado ?? r.Estado ?? '';
    const asignado = (r.asignado_ruta??0)==1 ? 'Sí':'No';

    // Días por fila (si API los diera); si no, arrancan en 0
    const Lu = r.Lu??0, Ma=r.Ma??0, Mi=r.Mi??0, Ju=r.Ju??0, Vi=r.Vi??0, Sa=r.Sa??0, Do=r.Do??0;

    return `
      <tr data-row="${esc(idDest)}">
        <td><input class="rowchk" type="checkbox" data-id="${esc(idDest)}"></td>
        <td>[${esc(cte)}] ${esc(cliente)}</td>
        <td>[${esc(idDest)}] ${esc(destinatario)}</td>

        <td class="day-cell"><input type="checkbox" class="dLu" ${Lu? 'checked':''}></td>
        <td class="day-cell"><input type="checkbox" class="dMa" ${Ma? 'checked':''}></td>
        <td class="day-cell"><input type="checkbox" class="dMi" ${Mi? 'checked':''}></td>
        <td class="day-cell"><input type="checkbox" class="dJu" ${Ju? 'checked':''}></td>
        <td class="day-cell"><input type="checkbox" class="dVi" ${Vi? 'checked':''}></td>
        <td class="day-cell"><input type="checkbox" class="dSa" ${Sa? 'checked':''}></td>
        <td class="day-cell"><input type="checkbox" class="dDo" ${Do? 'checked':''}></td>

        <td><span class="badge ${asignado==='Sí'?'text-bg-success':'text-bg-secondary'}">${asignado}</span></td>
        <td>
          <select class="form-select form-select-sm rutaNew">
            <option value="global">(global)</option>
          </select>
        </td>
        <td><input class="form-control form-control-sm sec" style="width:70px" value="${esc(r.sec ?? '')}"></td>

        <td>${esc(dir)}</td>
        <td>${esc(col)}</td>
        <td>${esc(cp)}</td>
        <td>${esc(cd)}</td>
        <td>${esc(edo)}</td>
      </tr>
    `;
  }).join('');

  // eventos para KPIs
  document.querySelectorAll('.rowchk').forEach(x=>x.addEventListener('change', updateKpis));
  updateKpis();
}

async function cargarClientes(){
  const alm = selAlm.value;
  const rut = selRut.value; // puede existir o no; el listado no depende, pero lo mandas si tu API lo requiere
  const q = txtBuscar.value.trim();

  if(!alm){
    renderRows([]);
    return;
  }

  setEstado(true,'OK');
  try{
    // Tu API requiere empresa/almacen/ruta en algunos casos; aquí mandamos empresa=1 fijo si lo pide.
    // Ajusta empresa si en tu implementación es otro valor.
    const url = `${API_CLI}?empresa=1&almacen=${encodeURIComponent(alm)}&ruta=${encodeURIComponent(rut||1)}&q=${encodeURIComponent(q)}`;
    const data = await jget(url);

    if(!data.ok){
      setEstado(false,'Error API');
      diag.textContent = data.detalle || data.error || 'Error consultando clientes';
      renderRows([]);
      return;
    }

    lblRutaCargada.textContent = `Ruta cargada: ${selRut.value || '(sin selección)'}`;
    renderRows(data.data || []);
  }catch(e){
    setEstado(false,'Error API');
    diag.textContent = e.message;
    renderRows([]);
  }
}

function applyGlobalDaysToSelected(){
  const selIds = new Set(getSelectedIds());
  if(selIds.size===0) return;

  currentRows.forEach(r=>{
    const idDest = (r.id_destinatario ?? r.Id_Destinatario ?? '').toString();
    if(!selIds.has(idDest)) return;

    const tr = document.querySelector(`tr[data-row="${CSS.escape(idDest)}"]`);
    if(!tr) return;

    tr.querySelector('.dLu').checked = gDays.Lu.checked;
    tr.querySelector('.dMa').checked = gDays.Ma.checked;
    tr.querySelector('.dMi').checked = gDays.Mi.checked;
    tr.querySelector('.dJu').checked = gDays.Ju.checked;
    tr.querySelector('.dVi').checked = gDays.Vi.checked;
    tr.querySelector('.dSa').checked = gDays.Sa.checked;
    tr.querySelector('.dDo').checked = gDays.Do.checked;
  });
}

async function guardar(){
  const almacen = parseInt(selAlm.value||'0',10);
  const ruta = parseInt(selRut.value||'0',10);
  const selIds = getSelectedIds();

  if(!almacen || !ruta || selIds.length===0){
    alert('Parámetros incompletos (almacen/ruta/items).');
    return;
  }

  // construir items desde DOM (para garantizar que mandamos días correctos)
  const items = selIds.map(idDest=>{
    const tr = document.querySelector(`tr[data-row="${CSS.escape(idDest)}"]`);
    if(!tr) return null;

    return {
      id_destinatario: parseInt(idDest,10),
      // opcional si lo tienes; tu save hace fallback si no viene
      cve_cliente: (currentRows.find(x => (x.id_destinatario??x.Id_Destinatario??'').toString()===idDest)?.Cve_Cliente)
                  || (currentRows.find(x => (x.id_destinatario??x.Id_Destinatario??'').toString()===idDest)?.cve_cliente)
                  || (currentRows.find(x => (x.id_destinatario??x.Id_Destinatario??'').toString()===idDest)?.id_cliente)
                  || 0,
      cve_vendedor: (currentRows.find(x => (x.id_destinatario??x.Id_Destinatario??'').toString()===idDest)?.cve_vendedor) || 0,

      Lu: tr.querySelector('.dLu').checked ? 1:0,
      Ma: tr.querySelector('.dMa').checked ? 1:0,
      Mi: tr.querySelector('.dMi').checked ? 1:0,
      Ju: tr.querySelector('.dJu').checked ? 1:0,
      Vi: tr.querySelector('.dVi').checked ? 1:0,
      Sa: tr.querySelector('.dSa').checked ? 1:0,
      Do: tr.querySelector('.dDo').checked ? 1:0,
    };
  }).filter(Boolean);

  const payload = { almacen, ruta, items };

  try{
    setEstado(true,'Guardando...');
    const r = await fetch(API_SAV, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await r.json();

    if(!data.ok){
      setEstado(false,'Error guardado');
      alert((data.error||'Error') + (data.detalle?("\n"+data.detalle):''));
      diag.textContent = JSON.stringify(data);
      return;
    }

    setEstado(true,'OK');
    alert('Planeación guardada.');
  }catch(e){
    setEstado(false,'Error guardado');
    alert(e.message);
  }
}

document.getElementById('btnBuscar').addEventListener('click', cargarClientes);
document.getElementById('btnLimpiar').addEventListener('click', ()=>{ txtBuscar.value=''; cargarClientes(); });

document.getElementById('btnSelTodo').addEventListener('click', ()=>{
  document.querySelectorAll('.rowchk').forEach(x=>x.checked=true);
  updateKpis();
});
document.getElementById('btnSelNada').addEventListener('click', ()=>{
  document.querySelectorAll('.rowchk').forEach(x=>x.checked=false);
  updateKpis();
});

document.getElementById('chkAll').addEventListener('change', (e)=>{
  const v = e.target.checked;
  document.querySelectorAll('.rowchk').forEach(x=>x.checked=v);
  updateKpis();
});

Object.values(gDays).forEach(chk=>{
  chk.addEventListener('change', applyGlobalDaysToSelected);
});

document.getElementById('btnGuardar').addEventListener('click', guardar);
document.getElementById('btnGuardarTop').addEventListener('click', guardar);
document.getElementById('btnRefrescarTop').addEventListener('click', ()=>{
  cargarRutasPorAlmacen(selAlm.value);
  cargarClientes();
});

txtBuscar.addEventListener('keydown', (e)=>{
  if(e.key==='Enter'){ e.preventDefault(); cargarClientes(); }
});

selAlm.addEventListener('change', async ()=>{
  const alm = selAlm.value;
  await cargarRutasPorAlmacen(alm);
  await cargarClientes();
});

selRut.addEventListener('change', ()=>{
  lblRutaCargada.textContent = `Ruta cargada: ${selRut.value || '(sin selección)'}`;
});

// init
(async function(){
  await cargarAlmacenes();
  renderRows([]);
})();
</script>

<?php
$menuEnd = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menuEnd)) include $menuEnd;
?>
</body>
</html>
