<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

/* =========================
   Helpers
========================= */
function s($v){
  $v = trim((string)$v);
  return $v === '' ? null : $v;
}
function i0($v){
  return ($v === '' || $v === null) ? 0 : (int)$v;
}
function clamp_int($v, $min, $max, $def){
  $n = ($v === '' || $v === null) ? $def : (int)$v;
  if($n < $min) $n = $min;
  if($n > $max) $n = $max;
  return $n;
}

/**
 * dt(): normaliza fechas para SQL DATETIME.
 * Acepta:
 * - "YYYY-MM-DD"
 * - "YYYY-MM-DD HH:MM"
 * - "YYYY-MM-DD HH:MM:SS"
 * - "dd/mm/YYYY"
 * - "dd/mm/YYYY HH:MM"
 * - "dd/mm/YYYY HH:MM:SS"
 */
function dt($in){
  $in = trim((string)$in);
  if($in === '') return null;

  // ISO (YYYY-MM-DD ...)
  if(preg_match('/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/', $in)){
    // completar hora
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $in)) return $in . ' 00:00:00';
    if(preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $in)) return $in . ':00';
    return $in;
  }

  // LATAM (dd/mm/YYYY ...)
  if(preg_match('/^\d{2}\/\d{2}\/\d{4}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/', $in)){
    $parts = explode(' ', $in, 2);
    [$dd,$mm,$yy] = explode('/', $parts[0]);
    $time = $parts[1] ?? '00:00:00';
    if(preg_match('/^\d{2}:\d{2}$/', $time)) $time .= ':00';
    return sprintf('%04d-%02d-%02d %s', (int)$yy,(int)$mm,(int)$dd,$time);
  }

  // fallback: devolver tal cual (no revienta; solo puede no filtrar)
  return $in;
}

/**
 * Construye WHERE + params aplicando la lógica tolerante de Empresa:
 * - IdEmpresa (clave) y/o emp_id (id numérico convertido a texto)
 */
function build_filters($req){
  $q       = trim((string)($req['q'] ?? ''));
  $empresa = trim((string)($req['IdEmpresa'] ?? '')); // clave (varchar)
  $emp_id  = trim((string)($req['emp_id'] ?? ''));    // id numérico como string
  $ruta    = trim((string)($req['RutaId'] ?? ''));
  $vend    = trim((string)($req['IdVendedor'] ?? ''));
  $desde   = trim((string)($req['desde'] ?? ''));
  $hasta   = trim((string)($req['hasta'] ?? ''));

  $where = "WHERE 1=1 ";
  $params = [];

  // Empresa tolerante
  if($empresa !== '' && $emp_id !== ''){
    $where .= " AND (IdEmpresa = :emp OR IdEmpresa = :emp_id) ";
    $params[':emp'] = $empresa;
    $params[':emp_id'] = $emp_id;
  }else if($empresa !== ''){
    $where .= " AND IdEmpresa = :emp ";
    $params[':emp'] = $empresa;
  }else if($emp_id !== ''){
    $where .= " AND IdEmpresa = :emp_id ";
    $params[':emp_id'] = $emp_id;
  }

  // Ruta / Vendedor
  if($ruta !== ''){
    $where .= " AND RutaId = :ruta ";
    $params[':ruta'] = (int)$ruta;
  }
  if($vend !== ''){
    $where .= " AND IdVendedor = :vend ";
    $params[':vend'] = (int)$vend;
  }

  // Rango fechas (sobre HI)
  if($desde !== ''){
    $d = dt($desde);
    if($d) { $where .= " AND HI >= :desde "; $params[':desde'] = $d; }
  }
  if($hasta !== ''){
    $h = dt($hasta);
    if($h) { $where .= " AND HI <= :hasta "; $params[':hasta'] = $h; }
  }

  // Búsqueda
  if($q !== ''){
    $where .= " AND (Codigo LIKE :q OR Descripcion LIKE :q OR Tip LIKE :q OR TS LIKE :q OR HT LIKE :q OR CAST(Id AS CHAR) LIKE :q) ";
    $params[':q'] = "%$q%";
  }

  return [$where, $params];
}

/* =====================================================
   EXPORT CSV (filtrado)
===================================================== */
if($action === 'export_csv'){
  $tipo = $_GET['tipo'] ?? 'datos';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=bitacora_tiempos_'.$tipo.'.csv');

  $out = fopen('php://output', 'w');
  $headers = [
    'Id','Codigo','Descripcion','HI','HF','HT','TS','Visita','Programado','DiaO',
    'RutaId','Cerrado','IdV','Tip','latitude','longitude','pila','IdEmpresa',
    'IdVendedor','Id_Ayudante1','Id_Ayudante2','IdVehiculo'
  ];
  fputcsv($out, $headers);

  if($tipo === 'datos'){
    [$where,$params] = build_filters($_GET);

    $sql = "SELECT ".implode(',', $headers)." FROM bitacoratiempos $where ORDER BY HI DESC, Id DESC";
    $st = $pdo->prepare($sql);
    foreach($params as $k=>$v) $st->bindValue($k,$v);
    $st->execute();
    while($row = $st->fetch(PDO::FETCH_ASSOC)){
      // mantener orden de headers
      $line = [];
      foreach($headers as $h) $line[] = $row[$h] ?? null;
      fputcsv($out, $line);
    }
  }
  fclose($out);
  exit;
}

/* =====================================================
   STATS (KPIs)
===================================================== */
if($action === 'stats'){
  [$where,$params] = build_filters($_GET);

  $sql = "
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN COALESCE(CAST(Cerrado AS UNSIGNED),0)=1 THEN 1 ELSE 0 END) AS cerrados,
      SUM(CASE WHEN COALESCE(CAST(Cerrado AS UNSIGNED),0)=0 THEN 1 ELSE 0 END) AS abiertos,
      SUM(CASE WHEN COALESCE(CAST(Visita AS UNSIGNED),0)=1 THEN 1 ELSE 0 END) AS visitas,
      SUM(CASE WHEN COALESCE(CAST(Programado AS UNSIGNED),0)=1 THEN 1 ELSE 0 END) AS programados,
      SUM(CASE WHEN HF IS NULL AND COALESCE(CAST(Cerrado AS UNSIGNED),0)=0 THEN 1 ELSE 0 END) AS en_curso,
      ROUND(AVG(CASE WHEN HF IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, HI, HF) END), 1) AS prom_min,
      ROUND(MAX(CASE WHEN HF IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, HI, HF) END), 1) AS max_min
    FROM bitacoratiempos
    $where
  ";

  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->execute();
  $kpi = $st->fetch(PDO::FETCH_ASSOC) ?: [];

  echo json_encode(['ok'=>true,'kpi'=>$kpi], JSON_UNESCAPED_UNICODE);
  exit;
}

/* =====================================================
   LIST (paginado)
===================================================== */
if($action === 'list'){
  $page = clamp_int($_GET['page'] ?? 1, 1, 999999, 1);
  $pageSize = clamp_int($_GET['pageSize'] ?? 25, 1, 500, 25);
  $offset = ($page - 1) * $pageSize;

  [$where,$params] = build_filters($_GET);

  // Total
  $stc = $pdo->prepare("SELECT COUNT(*) FROM bitacoratiempos $where");
  foreach($params as $k=>$v) $stc->bindValue($k,$v);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  // Data
  $cols = "
    Id,Codigo,Descripcion,HI,HF,HT,TS,
    CAST(Visita AS UNSIGNED) AS Visita,
    CAST(Programado AS UNSIGNED) AS Programado,
    DiaO,RutaId,
    CAST(Cerrado AS UNSIGNED) AS Cerrado,
    IdV,Tip,latitude,longitude,pila,IdEmpresa,IdVendedor,Id_Ayudante1,Id_Ayudante2,IdVehiculo
  ";

  $sql = "SELECT $cols FROM bitacoratiempos $where ORDER BY HI DESC, Id DESC LIMIT :lim OFFSET :off";
  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->bindValue(':lim', (int)$pageSize, PDO::PARAM_INT);
  $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'pagina' => $page,
    'pageSize' => $pageSize,
    'total' => $total,
    'data' => $rows
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* =====================================================
   DEFAULT
===================================================== */
echo json_encode(['error'=>'Acción no válida'], JSON_UNESCAPED_UNICODE);
