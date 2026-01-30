<?php
require_once __DIR__ . '/../../app/db.php';
db_pdo();
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
:root{ --corp:#0b5ed7; }
.title-corp{ color:var(--corp); font-weight:700; }
.card-lite{ border:1px solid rgba(0,0,0,.08); border-radius:12px; }
.card-lite .label{ font-size:12px; color:#6c757d }
.card-lite .value{ font-size:26px; font-weight:800 }
.btn-corp{ background:var(--corp); color:#fff; }
.btn-corp:hover{ background:#0949a8; color:#fff }
</style>

<div class="container-fluid px-4 py-3">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h3 class="title-corp mb-1">
      <i class="fa fa-exchange-alt"></i>
      Importador · Traslado entre Almacenes
    </h3>
    <div class="text-muted">Carga, vista previa, validación y ejecución</div>
  </div>

  <div class="d-flex gap-2">
    <a href="import_runs.php" class="btn btn-outline-secondary">
      <i class="fa fa-cogs"></i> Admin Importaciones
    </a>
    <a href="Layout_Traslado_entre_Almacenes1.csv"
       class="btn btn-outline-primary" download>
      <i class="fa fa-download"></i> Descargar layout
    </a>
  </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card-lite p-3">
      <div class="label">Total líneas</div>
      <div class="value" id="kTotal">-</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-lite p-3">
      <div class="label">Errores</div>
      <div class="value text-danger" id="kErr">-</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-lite p-3">
      <div class="label">Estado</div>
      <div class="value fs-5" id="kStatus">Borrador</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-lite p-3 d-grid">
      <button class="btn btn-corp" id="btnProcesar" disabled>
        <i class="fa fa-bolt"></i> Procesar
      </button>
    </div>
  </div>
</div>

<!-- CARGA -->
<div class="card-lite p-3 mb-4">
  <h5 class="mb-3"><i class="fa fa-file-csv"></i> Archivo CSV</h5>

  <div class="row g-3 align-items-end">
    <div class="col-md-6">
      <label class="form-label">Seleccionar archivo</label>
      <input type="file" id="csvFile" class="form-control" accept=".csv,text/csv">
      <div class="form-text">
        Vista previa NO crea corrida. Guardar sí la fija.
      </div>
    </div>

    <div class="col-md-6 d-grid">
      <div class="btn-group">
        <button class="btn btn-outline-dark" id="btnPreview" disabled>
          <i class="fa fa-search"></i> Vista previa | Validar
        </button>
        <button class="btn btn-outline-primary" id="btnGuardar" disabled>
          <i class="fa fa-save"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- PREVIEW -->
<div class="card-lite p-3">
  <h5 class="mb-2"><i class="fa fa-table"></i> Vista previa</h5>
  <div class="text-muted mb-2" id="previewInfo">
    Selecciona un archivo para visualizar su contenido
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered">
      <thead id="previewHead">
        <tr><th class="text-muted">Sin datos</th></tr>
      </thead>
      <tbody id="previewBody">
        <tr><td class="text-muted">—</td></tr>
      </tbody>
    </table>
  </div>
</div>

</div>

<script>
const API_VALIDATE = '../api/importadores/api_import_run_validate.php';
const API_APPLY    = '../api/importadores/api_import_run_apply.php';
const TIPO_INGRESO = 'TRALM';

let currentRunId = 0;
let lastFile = null;
let lastFingerprint = null;
let lastValidationOK = false;

const csvFile = document.getElementById('csvFile');
const btnPrev = document.getElementById('btnPreview');
const btnSave = document.getElementById('btnGuardar');
const btnProc = document.getElementById('btnProcesar');

const kTotal  = document.getElementById('kTotal');
const kErr    = document.getElementById('kErr');
const kStatus = document.getElementById('kStatus');

const previewInfo = document.getElementById('previewInfo');
const previewHead = document.getElementById('previewHead');
const previewBody = document.getElementById('previewBody');

function fingerprint(f){ return f.name+'|'+f.size+'|'+f.lastModified; }

function detectDelimiter(line){
  const c=[',',';','|','\t']; let best=',',max=-1;
  c.forEach(d=>{ const n=(line.split(d).length-1); if(n>max){max=n;best=d;} });
  return best;
}

function resetState(){
  currentRunId = 0;
  lastValidationOK = false;
  kTotal.innerText='-';
  kErr.innerText='-';
  kStatus.innerText='Borrador';
  btnProc.disabled = true;
}

async function renderPreview(file){
  const text = await file.text();
  const lines = text.split(/\r?\n/).filter(l=>l.trim()!=='');
  if(!lines.length) return;

  const delim = detectDelimiter(lines[0]);
  const headers = lines[0].split(delim);

  previewHead.innerHTML =
    '<tr>'+headers.map(h=>`<th>${h}</th>`).join('')+'</tr>';

  previewBody.innerHTML =
    lines.slice(1,26).map(l=>{
      const c=l.split(delim);
      return '<tr>'+headers.map((_,i)=>`<td>${c[i]||''}</td>`).join('')+'</tr>';
    }).join('');

  previewInfo.innerText =
    `Archivo: ${file.name} · Filas detectadas: ${lines.length-1}`;
}

// Selección de archivo
csvFile.addEventListener('change', async ()=>{
  const f = csvFile.files[0];
  if(!f) return;

  const fp = fingerprint(f);
  if(fp !== lastFingerprint){
    lastFingerprint = fp;
    resetState();
  }

  lastFile = f;
  btnPrev.disabled = false;
  btnSave.disabled = false;

  await renderPreview(f);

  // Permite volver a elegir el mismo archivo sin recargar
  csvFile.value = '';
});

// Vista previa | Validar
btnPrev.addEventListener('click', async ()=>{
  if(!lastFile) return;

  await renderPreview(lastFile);

  if(currentRunId <= 0){
    kStatus.innerText = 'Vista previa';
    return;
  }

  const fd = new FormData();
  fd.append('archivo', lastFile);
  fd.append('tipo_ingreso', TIPO_INGRESO);
  fd.append('run_id', currentRunId);

  const r = await fetch(API_VALIDATE,{method:'POST',body:fd});
  const j = await r.json();
  if(!j.ok) return alert(j.error);

  kTotal.innerText = j.totales.total_lineas;
  kErr.innerText   = j.totales.total_err;
  kStatus.innerText= j.totales.total_err>0?'Validado con errores':'Validado';

  lastValidationOK = (j.totales.total_err===0);
  btnProc.disabled = !lastValidationOK;
});

// Guardar (único punto que crea corrida)
btnSave.addEventListener('click', async ()=>{
  if(!lastFile) return;

  const fd = new FormData();
  fd.append('archivo', lastFile);
  fd.append('tipo_ingreso', TIPO_INGRESO);
  fd.append('run_id', 0);

  const r = await fetch(API_VALIDATE,{method:'POST',body:fd});
  const j = await r.json();
  if(!j.ok) return alert(j.error);

  currentRunId = j.run_id;

  kTotal.innerText = j.totales.total_lineas;
  kErr.innerText   = j.totales.total_err;
  kStatus.innerText= j.totales.total_err>0?'Guardado con errores':'Guardado';

  lastValidationOK = (j.totales.total_err===0);
  btnProc.disabled = !lastValidationOK;
});

// Procesar
btnProc.addEventListener('click', async ()=>{
  if(!lastValidationOK || !currentRunId) return;

  if(!confirm('¿Procesar importación?')) return;

  const r = await fetch(API_APPLY,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ run_id: currentRunId })
  });
  const j = await r.json();
  if(!j.ok) return alert(j.error);

  alert('Importación procesada correctamente');
  kStatus.innerText = 'Procesado';
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
