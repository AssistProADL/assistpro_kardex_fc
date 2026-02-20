<?php
/**
 * plan_inventario_detalle.php — Planeación / Teórico con grilla tipo Excel + export Excel/PDF
 * - Fuente del plan: inventario + th_plan_inventarios (bls_json)
 * - Fuente del teórico: v_inv_existencia_multinivel (no inventario_objeto)
 * - No usa t_ubicacionesinventarias
 */

require_once __DIR__ . '/../../bi/_menu_global.php';

/* -----------------------------
  Cargar DB (db.php) + helpers
------------------------------*/
$dbCandidates = [
  __DIR__ . '/../../app/db.php',
  __DIR__ . '/../app/db.php',
  __DIR__ . '/../../../app/db.php',
];

$dbLoaded = false;
foreach ($dbCandidates as $p) {
  if (file_exists($p)) { require_once $p; $dbLoaded = true; break; }
}
if (!$dbLoaded) { die("No se encontró db.php en rutas candidatas."); }

// Compatibilidad con tus helpers
if (!function_exists('db_one') && isset($pdo) && $pdo instanceof PDO) {
  function db_one($sql,$params=[]){
    global $pdo;
    $st=$pdo->prepare($sql); $st->execute($params);
    $r=$st->fetch(PDO::FETCH_ASSOC); return $r?:null;
  }
  function db_all($sql,$params=[]){
    global $pdo;
    $st=$pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
  function db_val($sql,$params=[]){
    global $pdo;
    $st=$pdo->prepare($sql); $st->execute($params);
    return $st->fetchColumn();
  }
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function is_json($s){ if(!is_string($s)||$s==='') return false; json_decode($s,true); return json_last_error()===JSON_ERROR_NONE; }

/* -----------------------------
  Params
------------------------------*/
$folio = trim($_GET['folio'] ?? '');
if ($folio==='') { die("Falta parámetro folio."); }

$action = trim($_GET['action'] ?? ''); // excel | pdf | print

// filtros rápidos (cliente)
$fSku  = trim($_GET['sku'] ?? '');
$fLp   = trim($_GET['lp'] ?? '');
$fBl   = trim($_GET['bl'] ?? '');
$fLote = trim($_GET['lote'] ?? '');
$fTipo = trim($_GET['tipo'] ?? ''); // TARIMA/CAJA/PIEZA (si aplica)
$max   = max(1, min(50000, (int)($_GET['max'] ?? 2000)));

/* -----------------------------
  Header del inventario + plan
------------------------------*/
$hdr = db_one("
  SELECT
    i.id_inventario,
    i.folio,
    i.tipo_inventario AS tipo,
    i.estado,
    i.fecha_creacion,
    i.cve_almacenp,
    i.observaciones,
    ap.nombre AS almacen_nombre,
    ap.clave  AS almacen_clave,
    p.id_plan,
    p.bls_json,
    p.fecha_programada
  FROM inventario i
  LEFT JOIN th_plan_inventarios p ON p.id_inventario = i.id_inventario
  LEFT JOIN c_almacenp ap ON CAST(ap.id AS UNSIGNED) = i.cve_almacenp
  WHERE i.folio = :f
  LIMIT 1
", [':f'=>$folio]);

if(!$hdr){
  die("No existe folio en inventario: ".h($folio));
}

/* -----------------------------
  Resolver lista de ubicaciones (idy_ubica) desde bls_json
  bls_json puede traer idy_ubica o CodigoCSD; resolvemos ambos.
------------------------------*/
$blsJson = $hdr['bls_json'] ?? '';
$rawList = [];

if (is_json($blsJson)) {
  $tmp = json_decode($blsJson, true);
  if (is_array($tmp)) $rawList = $tmp;
}

$idyList = [];
$csdList = [];

// normaliza lista
foreach($rawList as $x){
  if ($x === null) continue;
  if (is_array($x)) {
    // si viene como objeto: {idy_ubica:..., CodigoCSD:...}
    $idy = $x['idy_ubica'] ?? $x['id'] ?? null;
    $csd = $x['CodigoCSD'] ?? $x['codigocsd'] ?? $x['bl'] ?? null;
    if ($idy !== null && is_numeric($idy)) $idyList[] = (int)$idy;
    if ($csd !== null && trim((string)$csd)!=='') $csdList[] = trim((string)$csd);
  } else {
    $v = trim((string)$x);
    if ($v==='') continue;
    if (ctype_digit($v)) $idyList[] = (int)$v;
    else $csdList[] = $v;
  }
}

$idyList = array_values(array_unique(array_filter($idyList)));
$csdList = array_values(array_unique(array_filter($csdList)));

if ($csdList){
  $in = implode(',', array_fill(0, count($csdList), '?'));
  $rows = db_all("SELECT idy_ubica FROM c_ubicacion WHERE CodigoCSD IN ($in)", $csdList);
  foreach($rows as $r){
    if (isset($r['idy_ubica']) && is_numeric($r['idy_ubica'])) $idyList[] = (int)$r['idy_ubica'];
  }
  $idyList = array_values(array_unique($idyList));
}

/* -----------------------------
  Traer “foto teórica” multinivel por ubicaciones del plan
  Columnas típicas de v_inv_existencia_multinivel:
  cve_almacenp, almacen, CodigoCSD, idy_ubica, CveLP, tipo, cve_articulo, des_articulo, cve_lote, Cantidad, uom (si existe)
------------------------------*/
$detalle = [];
$totUbis = count($idyList);

if ($totUbis > 0){
  $in = implode(',', array_fill(0, count($idyList), '?'));

  // Nota: agregamos join a c_ubicacion para pasillo/rack/nivel (porque la vista puede o no traerlos)
  $sql = "
    SELECT
      v.cve_almacenp,
      v.almacen,
      v.CodigoCSD,
      v.idy_ubica,
      cu.cve_pasillo AS pasillo,
      cu.cve_rack    AS rack,
      cu.cve_nivel   AS nivel,

      v.tipo,
      v.CveLP,
      v.cve_articulo,
      v.des_articulo,
      v.cve_lote,
      v.Cantidad,
      v.uom
    FROM v_inv_existencia_multinivel v
    LEFT JOIN c_ubicacion cu ON cu.idy_ubica = v.idy_ubica
    WHERE v.idy_ubica IN ($in)
  ";

  $params = $idyList;

  // filtros servidor (para export y performance)
  if ($fBl!==''){   $sql .= " AND v.CodigoCSD LIKE ? "; $params[] = "%$fBl%"; }
  if ($fSku!==''){  $sql .= " AND v.cve_articulo LIKE ? "; $params[] = "%$fSku%"; }
  if ($fLp!==''){   $sql .= " AND v.CveLP LIKE ? "; $params[] = "%$fLp%"; }
  if ($fLote!==''){ $sql .= " AND v.cve_lote LIKE ? "; $params[] = "%$fLote%"; }
  if ($fTipo!==''){ $sql .= " AND v.tipo = ? "; $params[] = $fTipo; }

  $sql .= " ORDER BY v.CodigoCSD, v.cve_articulo, v.CveLP LIMIT ".(int)$max;

  $detalle = db_all($sql, $params);
}

/* -----------------------------
  KPIs / agregados
------------------------------*/
$k_planeadas = $totUbis;
$k_renglones = count($detalle);

$sumQty = 0.0;
foreach($detalle as $r){
  $sumQty += (float)($r['Cantidad'] ?? 0);
}
$sumQty = round($sumQty, 4);

/* -----------------------------
  Export Excel (XLSX o CSV)
------------------------------*/
function export_csv($hdr, $detalle){
  $fn = ($hdr['folio'] ?: 'inventario')."_teorico.csv";
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$fn.'"');
  $out = fopen('php://output','w');
  fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM Excel

  fputcsv($out, ['Folio','Tipo','Estado','Almacen','Fecha','Ubis plan','Renglones','Total']);
  fputcsv($out, [
    $hdr['folio'], $hdr['tipo'], $hdr['estado'],
    trim(($hdr['almacen_clave']??'')." ".($hdr['almacen_nombre']??'')),
    $hdr['fecha_creacion'],
    count(json_decode($hdr['bls_json']?:'[]', true) ?: []),
    count($detalle),
    ''
  ]);
  fputcsv($out, []);
  fputcsv($out, ['Almacen','BL','ID Ubica','Pasillo','Rack','Nivel','Tipo','LP','SKU','Descripcion','Lote','UOM','Teorica']);

  foreach($detalle as $r){
    fputcsv($out, [
      $r['almacen'] ?? '',
      $r['CodigoCSD'] ?? '',
      $r['idy_ubica'] ?? '',
      $r['pasillo'] ?? '',
      $r['rack'] ?? '',
      $r['nivel'] ?? '',
      $r['tipo'] ?? '',
      $r['CveLP'] ?? '',
      $r['cve_articulo'] ?? '',
      $r['des_articulo'] ?? '',
      $r['cve_lote'] ?? '',
      $r['uom'] ?? '',
      $r['Cantidad'] ?? 0,
    ]);
  }
  fclose($out);
  exit;
}

function export_xlsx_or_csv($hdr, $detalle){
  // Si está PhpSpreadsheet → XLSX, si no → CSV
  if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Teorico');

    $row = 1;
    $sheet->fromArray(['Folio','Tipo','Estado','Almacen','Fecha','Ubis plan','Renglones','Total'], null, "A{$row}"); $row++;
    $sheet->fromArray([
      $hdr['folio'], $hdr['tipo'], $hdr['estado'],
      trim(($hdr['almacen_clave']??'')." ".($hdr['almacen_nombre']??'')),
      $hdr['fecha_creacion'],
      count(json_decode($hdr['bls_json']?:'[]', true) ?: []),
      count($detalle),
      ''
    ], null, "A{$row}"); $row += 2;

    $sheet->fromArray(['Almacen','BL','ID Ubica','Pasillo','Rack','Nivel','Tipo','LP','SKU','Descripcion','Lote','UOM','Teorica'], null, "A{$row}");
    $row++;

    foreach($detalle as $r){
      $sheet->fromArray([
        $r['almacen'] ?? '',
        $r['CodigoCSD'] ?? '',
        $r['idy_ubica'] ?? '',
        $r['pasillo'] ?? '',
        $r['rack'] ?? '',
        $r['nivel'] ?? '',
        $r['tipo'] ?? '',
        $r['CveLP'] ?? '',
        $r['cve_articulo'] ?? '',
        $r['des_articulo'] ?? '',
        $r['cve_lote'] ?? '',
        $r['uom'] ?? '',
        (float)($r['Cantidad'] ?? 0),
      ], null, "A{$row}");
      $row++;
    }

    // autosize básico
    foreach(range('A','M') as $col){
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $fn = ($hdr['folio'] ?: 'inventario')."_teorico.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$fn.'"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }

  // fallback
  export_csv($hdr, $detalle);
}

/* -----------------------------
  Export PDF (Dompdf si existe)
------------------------------*/
function export_pdf_or_print($hdr, $detalle){
  $title = "Inventario teórico — ".$hdr['folio'];

  // HTML imprimible
  ob_start(); ?>
  <!doctype html>
  <html lang="es">
  <head>
    <meta charset="utf-8">
    <title><?=h($title)?></title>
    <style>
      body{ font-family: Arial, Helvetica, sans-serif; font-size:10px; color:#111; }
      h1{ font-size:14px; margin:0 0 8px; }
      .meta{ margin:0 0 10px; }
      .meta div{ margin:2px 0; }
      table{ width:100%; border-collapse:collapse; }
      th,td{ border:1px solid #ddd; padding:4px 5px; vertical-align:top; }
      th{ background:#f3f6fb; }
      .right{text-align:right;}
      .muted{ color:#666; }
    </style>
  </head>
  <body>
    <h1><?=h($title)?></h1>
    <div class="meta">
      <div><b>Tipo:</b> <?=h($hdr['tipo'])?> &nbsp; <b>Estado:</b> <?=h($hdr['estado'])?></div>
      <div><b>Almacén:</b> <?=h(trim(($hdr['almacen_clave']??'')." ".($hdr['almacen_nombre']??'')))?></div>
      <div><b>Fecha:</b> <?=h($hdr['fecha_creacion'])?></div>
      <div><b>Ubicaciones plan:</b> <?=h(count(json_decode($hdr['bls_json']?:'[]', true) ?: []))?> &nbsp; <b>Renglones:</b> <?=h(count($detalle))?></div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Almacén</th><th>BL</th><th>ID</th><th>Pasillo</th><th>Rack</th><th>Nivel</th>
          <th>Tipo</th><th>LP</th><th>SKU</th><th>Descripción</th><th>Lote</th><th>UOM</th><th class="right">Teórica</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$detalle): ?>
          <tr><td colspan="13" class="muted">Sin información teórica para el alcance.</td></tr>
        <?php else: foreach($detalle as $r): ?>
          <tr>
            <td><?=h($r['almacen'] ?? '')?></td>
            <td><?=h($r['CodigoCSD'] ?? '')?></td>
            <td><?=h($r['idy_ubica'] ?? '')?></td>
            <td><?=h($r['pasillo'] ?? '')?></td>
            <td><?=h($r['rack'] ?? '')?></td>
            <td><?=h($r['nivel'] ?? '')?></td>
            <td><?=h($r['tipo'] ?? '')?></td>
            <td><?=h($r['CveLP'] ?? '')?></td>
            <td><?=h($r['cve_articulo'] ?? '')?></td>
            <td><?=h($r['des_articulo'] ?? '')?></td>
            <td><?=h($r['cve_lote'] ?? '')?></td>
            <td><?=h($r['uom'] ?? '')?></td>
            <td class="right"><?=h($r['Cantidad'] ?? 0)?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </body>
  </html>
  <?php
  $html = ob_get_clean();

  if (class_exists('\Dompdf\Dompdf')) {
    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled'=>true]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $fn = ($hdr['folio'] ?: 'inventario')."_teorico.pdf";
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$fn.'"');
    echo $dompdf->output();
    exit;
  }

  // fallback: abre HTML “print-friendly” para guardar como PDF desde navegador
  header('Content-Type: text/html; charset=utf-8');
  echo $html;
  exit;
}

/* -----------------------------
  Ejecutar exports si aplica
------------------------------*/
if ($action === 'excel') {
  export_xlsx_or_csv($hdr, $detalle);
}
if ($action === 'pdf' || $action === 'print') {
  export_pdf_or_print($hdr, $detalle);
}

/* -----------------------------
  HTML principal (grilla tipo RFID)
------------------------------*/
$baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
$qBase = $_GET;
unset($qBase['action']);

function qlink($extra=[]){
  $q = array_merge($_GET, $extra);
  return '?'.http_build_query($q);
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Detalle Inventario Teórico - <?=h($hdr['folio'])?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  :root{
    --brand:#0b4ea2;
    --brand2:#0d6efd;
    --line:#e5e7eb;
    --muted:#6b7280;
  }
  body{ font-size:12px; background:#fff; }
  .page-h1{ font-weight:800; letter-spacing:.2px; margin:10px 0 2px; }
  .sub{ color:var(--muted); margin-bottom:10px; }
  .kpi-card{ border:1px solid var(--line); border-radius:14px; padding:10px 12px; background:#fff; }
  .kpi-label{ color:var(--muted); font-size:11px; }
  .kpi-val{ font-weight:800; font-size:18px; }
  .chip{ display:inline-block; padding:3px 9px; border-radius:999px; background:#eef4ff; color:var(--brand); font-weight:700; font-size:11px; }
  .grid-frame{ border:1px solid var(--line); border-radius:14px; overflow:hidden; }
  .grid-top{ padding:10px; background:#f8fafc; border-bottom:1px solid var(--line); }
  .scroll-xy{ max-height: 66vh; overflow:auto; background:#fff; }
  table.table{ font-size:11px; min-width: 1850px; margin:0; }
  .table thead th{ position:sticky; top:0; background:#f3f6fb; z-index:2; white-space:nowrap; }
  .table tfoot th{ position:sticky; bottom:0; background:#f8fafc; z-index:1; }
  .filters{ display:flex; gap:8px; flex-wrap:wrap; align-items:end; }
  .filters .field{ display:flex; flex-direction:column; gap:4px; }
  .filters label{ font-size:11px; color:var(--muted); }
  .filters input, .filters select{ height:32px; font-size:12px; border-radius:10px; }
  .btn-brand{ background:var(--brand2); border-color:var(--brand2); }
  .btn-ghost{ background:#fff; border:1px solid var(--line); }
  .muted{ color:var(--muted); }
</style>
</head>

<body>
<div class="container-fluid py-3">

  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
      <div class="page-h1">Detalle de Inventario (Planeación / Teórico)</div>
      <div class="sub">
        Folio: <b><?=h($hdr['folio'])?></b> · ID: <b><?=h($hdr['id_inventario'])?></b> · Tipo: <b><?=h($hdr['tipo'])?></b>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-ghost btn-sm" href="javascript:history.back()">← Regresar</a>
      <a class="btn btn-brand btn-sm text-white" href="<?=h(qlink(['action'=>'excel']))?>">Exportar Excel</a>
      <a class="btn btn-ghost btn-sm" href="<?=h(qlink(['action'=>'pdf']))?>">Exportar PDF</a>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Estado</div>
        <div class="kpi-val"><span class="chip"><?=h($hdr['estado'])?></span></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">BLs planeados</div>
        <div class="kpi-val"><?=h($k_planeadas)?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Renglones teóricos</div>
        <div class="kpi-val"><?=h($k_renglones)?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Total teórico</div>
        <div class="kpi-val"><?=h($sumQty)?></div>
      </div>
    </div>
  </div>

  <div class="grid-frame">
    <div class="grid-top">
      <form class="filters" method="get">
        <input type="hidden" name="folio" value="<?=h($folio)?>">

        <div class="field">
          <label>BL</label>
          <input class="form-control" name="bl" value="<?=h($fBl)?>" placeholder="CodigoCSD">
        </div>
        <div class="field">
          <label>SKU</label>
          <input class="form-control" name="sku" value="<?=h($fSku)?>" placeholder="Artículo">
        </div>
        <div class="field">
          <label>LP</label>
          <input class="form-control" name="lp" value="<?=h($fLp)?>" placeholder="CveLP">
        </div>
        <div class="field">
          <label>Lote</label>
          <input class="form-control" name="lote" value="<?=h($fLote)?>" placeholder="Lote">
        </div>
        <div class="field">
          <label>Tipo</label>
          <select class="form-select" name="tipo">
            <option value="">Todos</option>
            <option value="TARIMA" <?=($fTipo==='TARIMA'?'selected':'')?>>TARIMA</option>
            <option value="CAJA"   <?=($fTipo==='CAJA'?'selected':'')?>>CAJA</option>
            <option value="PIEZA"  <?=($fTipo==='PIEZA'?'selected':'')?>>PIEZA</option>
          </select>
        </div>
        <div class="field">
          <label>Max</label>
          <input class="form-control" type="number" name="max" value="<?=h($max)?>" min="1" max="50000" style="width:110px">
        </div>

        <div class="field" style="min-width:140px">
          <button class="btn btn-brand text-white w-100" type="submit">Aplicar</button>
        </div>
        <div class="field" style="min-width:140px">
          <a class="btn btn-ghost w-100" href="?folio=<?=h(urlencode($folio))?>">Limpiar</a>
        </div>

        <div class="ms-auto muted align-self-center">
          <?=h($k_renglones)?> renglones · vista teórica multinivel
        </div>
      </form>
    </div>

    <div class="scroll-xy">
      <table class="table table-sm table-hover align-middle">
        <thead>
          <tr>
            <th>Almacén</th>
            <th>BL</th>
            <th>ID Ubica</th>
            <th>Pasillo</th>
            <th>Rack</th>
            <th>Nivel</th>
            <th>Tipo</th>
            <th>LP</th>
            <th>SKU</th>
            <th>Descripción</th>
            <th>Lote</th>
            <th>UOM</th>
            <th class="text-end">Teórica</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$detalle): ?>
          <tr><td colspan="13" class="muted p-3">Sin información teórica para el alcance (valida bls_json y existencias multinivel).</td></tr>
        <?php else: foreach($detalle as $r): ?>
          <tr>
            <td><?=h($r['almacen'] ?? '')?></td>
            <td><b><?=h($r['CodigoCSD'] ?? '')?></b></td>
            <td><?=h($r['idy_ubica'] ?? '')?></td>
            <td><?=h($r['pasillo'] ?? '')?></td>
            <td><?=h($r['rack'] ?? '')?></td>
            <td><?=h($r['nivel'] ?? '')?></td>
            <td><?=h($r['tipo'] ?? '')?></td>
            <td><?=h($r['CveLP'] ?? '')?></td>
            <td><?=h($r['cve_articulo'] ?? '')?></td>
            <td><?=h($r['des_articulo'] ?? '')?></td>
            <td><?=h($r['cve_lote'] ?? '')?></td>
            <td><?=h($r['uom'] ?? '')?></td>
            <td class="text-end fw-semibold"><?=h($r['Cantidad'] ?? 0)?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="12" class="text-end">Total</th>
            <th class="text-end"><?=h($sumQty)?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../../bi/_menu_global_end.php'; ?>
</body>
</html>
