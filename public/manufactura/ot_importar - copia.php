<?php
// /public/manufactura/ot_importar.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

function jexit($arr, int $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function csv_row(array $a): string {
  $out = [];
  foreach($a as $v){
    $v = (string)$v;
    if (strpbrk($v, ",\"\n\r") !== false) {
      $v = '"' . str_replace('"','""',$v) . '"';
    }
    $out[] = $v;
  }
  return implode(",", $out) . "\r\n";
}

$op = $_GET['op'] ?? '';

/* ============================================================
   ENDPOINT: descargar layout CSV
============================================================ */
if ($op === 'layout') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="layout_importacion_ots.csv"');

  // Requeridas (mínimas)
  $cols_req = [
    'OT_CLIENTE',        // folio/OT de referencia (puede ser folio final si así lo usas)
    'CVE_ARTICULO',      // producto compuesto
    'CANTIDAD',          // cantidad a producir
  ];

  // Opcionales (para tu proceso futuro de contenedores/pallets)
  $cols_opt = [
    'OT_ERP',
    'FECHA_OT',          // YYYY-MM-DD
    'FECHA_COMPROMISO',  // YYYY-MM-DD
    'CVE_ALMACENP',      // id de c_almacenp (opcional si se usa selección en modal)
    'ZONA',              // cve_almac o zona (opcional si se usa selección en modal)
    'BL_ORIGEN',         // código BL (ubicacion.CodigoCSD)
    'BL_DESTINO',        // código BL destino (ubicacion.CodigoCSD)
    'CVE_LOTE',
    'LP_CONTENEDOR',
    'LP_PALLET',
    'REFERENCIA'
  ];

  echo csv_row(array_merge($cols_req, $cols_opt));

  // Fila ejemplo
  echo csv_row([
    'OT20251225-00001',
    'A6-43019-001B2-109',
    '90',
    'ERP-REF-001',
    date('Y-m-d'),
    date('Y-m-d', strtotime('+7 days')),
    '', '', '',
    'E301010101', 'E301010101',
    '',
    'CT251225-1',
    'LP251225-1',
    'OBS opcional'
  ]);
  exit;
}

/* ============================================================
   ENDPOINTS: preview / importar
============================================================ */
if ($op === 'preview' || $op === 'import') {
  try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      jexit(['ok'=>false,'msg'=>'No se recibió archivo CSV válido.'], 400);
    }

    $almacenp = trim((string)($_POST['almacenp'] ?? ''));
    $zona     = trim((string)($_POST['zona'] ?? ''));
    $bl_def   = trim((string)($_POST['bl_default'] ?? ''));
    $user     = trim((string)($_POST['cve_usuario'] ?? ''));

    if ($op === 'import') {
      // Para importar, pedimos mínimos de contexto (si CSV no lo trae)
      if ($zona === '' && $almacenp === '') {
        jexit(['ok'=>false,'msg'=>'Seleccione almacén/empresa y zona (o envíelos en el CSV).'], 400);
      }
      if ($user === '') {
        jexit(['ok'=>false,'msg'=>'Seleccione Usuario (registra).'], 400);
      }
    }

    $tmp = $_FILES['file']['tmp_name'];
    $fh  = fopen($tmp, 'r');
    if (!$fh) jexit(['ok'=>false,'msg'=>'No se pudo leer el archivo.'], 400);

    // Detectar encabezados
    $header = fgetcsv($fh);
    if (!$header) jexit(['ok'=>false,'msg'=>'CSV sin encabezados.'], 400);

    $header = array_map(fn($x)=>trim((string)$x), $header);
    $map = [];
    foreach($header as $i=>$h){
      $map[strtoupper($h)] = $i;
    }

    $need = ['OT_CLIENTE','CVE_ARTICULO','CANTIDAD'];
    foreach($need as $n){
      if (!array_key_exists($n, $map)) {
        jexit(['ok'=>false,'msg'=>"Falta columna requerida: $n"], 400);
      }
    }

    // helpers
    $resolveBl = function(string $blCode){
      if ($blCode === '') return null;
      // prioridad: ubicacion.CodigoCSD (tal como definiste en proyecto)
      return db_val("SELECT idy_ubica FROM c_ubicacion WHERE CodigoCSD = ? LIMIT 1", [$blCode]);
    };

    $rows = [];
    $errores = [];
    $maxPreview = 200;
    $line = 1;

    // Para IMPORT: transacción (1 corrida)
    if ($op === 'import') dbq("START TRANSACTION");

    $now = date('Y-m-d H:i:s');
    $totalOT = 0;

    while(($r = fgetcsv($fh)) !== false){
      $line++;

      $otCli = trim((string)($r[$map['OT_CLIENTE']] ?? ''));
      $art   = trim((string)($r[$map['CVE_ARTICULO']] ?? ''));
      $cant  = trim((string)($r[$map['CANTIDAD']] ?? ''));

      $otErp = trim((string)($r[$map['OT_ERP']] ?? ''));
      $fechaOT = trim((string)($r[$map['FECHA_OT']] ?? ''));
      $fechaComp = trim((string)($r[$map['FECHA_COMPROMISO']] ?? ''));

      $csvAlmacenp = trim((string)($r[$map['CVE_ALMACENP']] ?? ''));
      $csvZona     = trim((string)($r[$map['ZONA']] ?? ''));

      $blOrigenCode  = trim((string)($r[$map['BL_ORIGEN']] ?? ''));
      $blDestinoCode = trim((string)($r[$map['BL_DESTINO']] ?? ''));

      $lote   = trim((string)($r[$map['CVE_LOTE']] ?? ''));
      $lpCont = trim((string)($r[$map['LP_CONTENEDOR']] ?? ''));
      $lpPal  = trim((string)($r[$map['LP_PALLET']] ?? ''));
      $refCsv = trim((string)($r[$map['REFERENCIA']] ?? ''));

      // Validación mínima
      $err = [];
      if ($otCli === '') $err[] = 'OT_CLIENTE vacío';
      if ($art === '')   $err[] = 'CVE_ARTICULO vacío';
      if ($cant === '' || !is_numeric($cant) || (float)$cant <= 0) $err[] = 'CANTIDAD inválida';

      $zonaEff = $csvZona !== '' ? $csvZona : $zona;
      $almEff  = $csvAlmacenp !== '' ? $csvAlmacenp : $almacenp;

      // BL default desde modal
      $idyUbicaDefault = $bl_def !== '' ? $resolveBl($bl_def) : null;

      if ($op === 'import') {
        if ($zonaEff === '') $err[] = 'Zona no definida (ZONA o selección modal)';
        if ($user === '')    $err[] = 'Usuario no definido';
      }

      $ok = count($err) === 0;

      if (!$ok) {
        $errores[] = ['linea'=>$line, 'ot'=>$otCli, 'errores'=>$err];
      }

      // Preview: solo acumulamos
      if ($op === 'preview') {
        if (count($rows) < $maxPreview) {
          $rows[] = [
            'OT_CLIENTE'=>$otCli,
            'OT_ERP'=>$otErp,
            'CVE_ARTICULO'=>$art,
            'CANTIDAD'=>$cant,
            'FECHA_OT'=>$fechaOT,
            'FECHA_COMPROMISO'=>$fechaComp,
            'ZONA'=>$zonaEff,
            'BL_ORIGEN'=>$blOrigenCode ?: $bl_def,
            'BL_DESTINO'=>$blDestinoCode,
            'LP_CONTENEDOR'=>$lpCont,
            'LP_PALLET'=>$lpPal,
            'OK'=>$ok ? 'OK' : 'ERROR'
          ];
        }
        continue;
      }

      // IMPORT: si hay error, saltamos fila (y dejamos reporte)
      if (!$ok) continue;

      // Folio: aquí usamos OT_CLIENTE como Folio_Pro (idempotencia)
      $folioPro = $otCli;

      // Resolver BLs (si no viene, usar default)
      $idyUbicaOri  = $blOrigenCode !== '' ? $resolveBl($blOrigenCode) : $idyUbicaDefault;
      $idyUbicaDest = $blDestinoCode !== '' ? $resolveBl($blDestinoCode) : $idyUbicaDefault;

      if (!$idyUbicaOri)  $idyUbicaOri  = $idyUbicaDefault;
      if (!$idyUbicaDest) $idyUbicaDest = $idyUbicaDefault;

      // Referencia enriquecida opcional (sin romper proceso)
      $refExtra = $refCsv;
      if ($lpCont !== '')   $refExtra .= ($refExtra ? ' | ' : '') . 'LP_CONT=' . $lpCont;
      if ($lpPal !== '')    $refExtra .= ($refExtra ? ' | ' : '') . 'LP_PAL=' . $lpPal;
      if ($blDestinoCode !== '') $refExtra .= ($refExtra ? ' | ' : '') . 'BL_DEST=' . $blDestinoCode;

      // Idempotencia por folio
      $ex = db_val("SELECT COUNT(*) FROM t_ordenprod WHERE Folio_Pro = ?", [$folioPro]);
      if ((int)$ex > 0) continue;

      // Fecha OT: si viene YYYY-MM-DD, la guardamos como DATETIME 00:00:00
      $fechaOTdb = ($fechaOT !== '' ? ($fechaOT . ' 00:00:00') : date('Y-m-d 00:00:00'));
      $fechaCompdb = ($fechaComp !== '' ? ($fechaComp . ' 00:00:00') : null);

      dbq("
        INSERT INTO t_ordenprod
          (Folio_Pro, cve_almac, Cve_Articulo, Cve_Lote, Cantidad, Cant_Prod,
           Cve_Usuario, Fecha, FechaReg, Status, Referencia,
           id_zona_almac, idy_ubica, idy_ubica_dest)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
      ", [
        $folioPro,
        $zonaEff,
        $art,
        $lote,
        $cant,
        $cant,
        $user,
        $fechaOTdb,
        $now,
        'P',
        $refExtra,
        $zonaEff,
        $idyUbicaOri,
        $idyUbicaDest
      ]);

      // Si quisieras guardar Fecha compromiso en otra tabla/columna: lo dejamos listo a futuro
      // (no rompemos esquema actual)
      $totalOT++;
    }

    fclose($fh);

    if ($op === 'import') {
      dbq("COMMIT");
      jexit([
        'ok'=>true,
        'msg'=> "{$totalOT} OT(s) importadas.",
        'total'=>$totalOT,
        'errores'=>$errores
      ]);
    }

    // preview
    jexit([
      'ok'=>true,
      'rows'=>$rows,
      'errores'=>$errores,
      'msg'=>"Previsualización lista: ".count($rows)." fila(s) mostradas."
    ]);

  } catch (Throwable $e) {
    // rollback si aplica
    try { dbq("ROLLBACK"); } catch(Throwable $x){}
    jexit(['ok'=>false,'msg'=>'Error: '.$e->getMessage()], 500);
  }
}

/* ============================================================
   VISTA HTML + MODAL (estilo screenshot)
============================================================ */

// AlmacenesP desde c_almacenp (sin columna inexistente)
$almacenesP = [];
try {
  $almacenesP = db_all("
    SELECT ap.id AS id_almacenp, ap.clave, ap.nombre
    FROM c_almacenp ap
    ORDER BY ap.nombre
  ");
} catch(Throwable $e){ $almacenesP=[]; }

// Usuarios (según tu tabla c_usuario, cve_cia = 1 en tu evidencia)
$usuarios = [];
try {
  $usuarios = db_all("
    SELECT cve_usuario, nombre_completo
    FROM c_usuario
    WHERE Activo = 1
    ORDER BY nombre_completo
  ");
} catch(Throwable $e){ $usuarios=[]; }

$TITLE = 'Importador Masivo de OT';
include __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Importador Masivo de OT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .ap-title{font-weight:700}
    .ap-note{font-size:12px;color:#5b6b7a}
    .table-wrap{max-height:45vh;overflow:auto;border:1px solid #e7edf4;border-radius:10px}
    .table thead th{position:sticky;top:0;background:#f8fafc;z-index:2}
  </style>
</head>
<body>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="ap-title">Importador Masivo de Órdenes de Producción</div>
      <div class="ap-note">Carga CSV, previsualiza y ejecuta importación idempotente por Folio_Pro.</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mdlImport">
      Importar
    </button>
  </div>
</div>

<!-- MODAL estilo screenshot -->
<div class="modal fade" id="mdlImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Importar pallets / contenedores</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-primary py-2 mb-3" style="font-size:12px">
          Layout FULL con UPSERT por Folio_Pro. Previsualiza antes de importar. Columnas extra son opcionales para flujo de contenedores/pallets.
        </div>

        <div class="row g-2 mb-2">
          <div class="col-md-3">
            <label class="form-label">Almacén (empresa)</label>
            <select id="almacenp" class="form-select">
              <option value="">Seleccione</option>
              <?php foreach($almacenesP as $a): ?>
                <option value="<?= htmlspecialchars((string)$a['id_almacenp']) ?>">
                  (<?= htmlspecialchars((string)$a['clave']) ?>) <?= htmlspecialchars((string)$a['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Zona (cve_almac)</label>
            <input id="zona" class="form-control" placeholder="Ej: 35">
          </div>

          <div class="col-md-3">
            <label class="form-label">BL default (CodigoCSD)</label>
            <input id="bl_default" class="form-control" placeholder="Ej: E301010101">
          </div>

          <div class="col-md-3">
            <label class="form-label">Usuario (registra)</label>
            <select id="cve_usuario" class="form-select">
              <option value="">Seleccione</option>
              <?php foreach($usuarios as $u): ?>
                <option value="<?= htmlspecialchars((string)$u['cve_usuario']) ?>">
                  <?= htmlspecialchars((string)$u['nombre_completo']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 mb-3">
          <input type="file" id="file" class="form-control" accept=".csv" style="max-width:520px">
          <button class="btn btn-outline-primary" id="btnLayout">Descargar layout</button>
          <button class="btn btn-success" id="btnPreview">Previsualizar</button>
          <button class="btn btn-primary" id="btnImport" disabled>Importar</button>
          <div id="busy" class="ms-auto" style="display:none">
            <span class="spinner-border spinner-border-sm"></span>
            <span style="font-size:12px">Procesando…</span>
          </div>
        </div>

        <div id="msg" class="small mb-2"></div>

        <div class="table-wrap">
          <table class="table table-sm table-striped mb-0" id="tblPrev">
            <thead>
              <tr>
                <th>OT_CLIENTE</th>
                <th>OT_ERP</th>
                <th>CVE_ARTICULO</th>
                <th>CANTIDAD</th>
                <th>FECHA_OT</th>
                <th>FECHA_COMPROMISO</th>
                <th>ZONA</th>
                <th>BL_ORIGEN</th>
                <th>BL_DESTINO</th>
                <th>LP_CONT</th>
                <th>LP_PAL</th>
                <th>OK</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <div class="mt-2">
          <div class="small text-muted" id="errBox"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const msg  = (t, ok=true) => {
  const el = document.getElementById('msg');
  el.className = ok ? 'alert alert-success py-1' : 'alert alert-danger py-1';
  el.textContent = t;
};
const setBusy = (b) => {
  document.getElementById('busy').style.display = b ? 'block' : 'none';
  document.getElementById('btnPreview').disabled = b;
  document.getElementById('btnImport').disabled = b || !window.__previewOk;
};

document.getElementById('btnLayout').addEventListener('click', ()=>{
  window.location = 'ot_importar.php?op=layout';
});

function formDataBase(){
  const fd = new FormData();
  const f  = document.getElementById('file').files[0];
  if (f) fd.append('file', f);
  fd.append('almacenp', document.getElementById('almacenp').value);
  fd.append('zona', document.getElementById('zona').value);
  fd.append('bl_default', document.getElementById('bl_default').value);
  fd.append('cve_usuario', document.getElementById('cve_usuario').value);
  return fd;
}

function renderPreview(rows){
  const tb = document.querySelector('#tblPrev tbody');
  tb.innerHTML = '';
  rows.forEach(r=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${r.OT_CLIENTE||''}</td>
      <td>${r.OT_ERP||''}</td>
      <td>${r.CVE_ARTICULO||''}</td>
      <td>${r.CANTIDAD||''}</td>
      <td>${r.FECHA_OT||''}</td>
      <td>${r.FECHA_COMPROMISO||''}</td>
      <td>${r.ZONA||''}</td>
      <td>${r.BL_ORIGEN||''}</td>
      <td>${r.BL_DESTINO||''}</td>
      <td>${r.LP_CONTENEDOR||''}</td>
      <td>${r.LP_PALLET||''}</td>
      <td><span class="badge ${r.OK==='OK'?'text-bg-success':'text-bg-danger'}">${r.OK}</span></td>
    `;
    tb.appendChild(tr);
  });
}

function renderErrors(errs){
  const el = document.getElementById('errBox');
  if (!errs || !errs.length){ el.textContent=''; return; }
  el.textContent = `Errores detectados: ${errs.length}. (Se importarán solo filas válidas).`;
}

document.getElementById('btnPreview').addEventListener('click', async ()=>{
  window.__previewOk = false;
  document.getElementById('btnImport').disabled = true;

  const f = document.getElementById('file').files[0];
  if (!f) return msg('Seleccione un CSV.', false);

  setBusy(true);
  try{
    const res = await fetch('ot_importar.php?op=preview', {method:'POST', body: formDataBase()});
    const j = await res.json();
    if (!j.ok) throw new Error(j.msg || 'Error en preview');
    renderPreview(j.rows||[]);
    renderErrors(j.errores||[]);
    window.__previewOk = true;
    msg(j.msg || 'Previsualización lista.');
    document.getElementById('btnImport').disabled = false;
  }catch(e){
    msg(e.message, false);
  }finally{
    setBusy(false);
  }
});

document.getElementById('btnImport').addEventListener('click', async ()=>{
  if (!window.__previewOk) return;

  setBusy(true);
  try{
    const res = await fetch('ot_importar.php?op=import', {method:'POST', body: formDataBase()});
    const j = await res.json();
    if (!j.ok) throw new Error(j.msg || 'Error al importar');
    renderErrors(j.errores||[]);
    msg(j.msg || 'Importación completada.');
  }catch(e){
    msg(e.message, false);
  }finally{
    setBusy(false);
  }
});
</script>
</body>
</html>
