<?php
declare(strict_types=1);

/**
 * /public/api/monitor_produccion_api.php
 * API Monitor Producción (DataTables serverSide)
 *
 * Devuelve (por fila):
 * folio, zona, bl_origen, clave, descripcion, lote, caducidad, cantidad,
 * bl_destino, hora_ini, hora_fin, avance
 *
 * Soporta:
 * action=stats   -> KPIs
 * action=detalle -> header + lines (si existe tabla detalle)
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// limpia cualquier contaminación previa
while (ob_get_level() > 0) { ob_end_clean(); }
ob_start();

// ===== localizar app/db.php (sin depender de ../../..) =====
function findDbFile(string $startDir): string {
  $dir = $startDir;
  for ($i=0; $i<10; $i++) {
    $candidate = $dir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'db.php';
    if (is_file($candidate)) return $candidate;
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
  }
  throw new RuntimeException("No se encontró app/db.php desde: $startDir");
}

require_once findDbFile(__DIR__);

if (!function_exists('db_pdo')) {
  ob_clean();
  echo json_encode(['error'=>true,'message'=>'db_pdo() no existe en app/db.php','data'=>[]], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ===== helpers esquema =====
function colExists(PDO $pdo, string $table, string $col): bool {
  $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c";
  $st = $pdo->prepare($sql);
  $st->execute([':t'=>$table, ':c'=>$col]);
  return (int)$st->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool {
  $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t";
  $st = $pdo->prepare($sql);
  $st->execute([':t'=>$table]);
  return (int)$st->fetchColumn() > 0;
}

function req(string $k, $default=null) {
  return $_REQUEST[$k] ?? $default;
}

// ===== filtros =====
$empresa = trim((string)req('empresa',''));
$almacen = trim((string)req('almacen',''));
$zona    = trim((string)req('zona',''));
$status  = trim((string)req('status','P'));
$desde   = trim((string)req('desde',''));
$hasta   = trim((string)req('hasta',''));

$action  = trim((string)req('action',''));

// Defaults fechas (últimos 7 días si no llegan)
if ($desde === '') $desde = date('Y-m-d', strtotime('-7 days'));
if ($hasta === '') $hasta = date('Y-m-d');

// ===== Tabla principal OT =====
$table = 't_ordenprod';

// ===== detecta columnas reales en t_ordenprod =====
$COL_FOLIO = colExists($pdo,$table,'Folio_Pro') ? 'Folio_Pro' : (colExists($pdo,$table,'Folio') ? 'Folio' : null);
$COL_ART   = colExists($pdo,$table,'Cve_Articulo') ? 'Cve_Articulo' : (colExists($pdo,$table,'Articulo') ? 'Articulo' : null);
$COL_CANT  = colExists($pdo,$table,'Cantidad') ? 'Cantidad' : (colExists($pdo,$table,'Cant') ? 'Cant' : null);

$COL_ALM   = colExists($pdo,$table,'cve_almac') ? 'cve_almac' : null;
$COL_ZONA  = colExists($pdo,$table,'id_zona_almac') ? 'id_zona_almac' : null;
$COL_PROV  = colExists($pdo,$table,'ID_Proveedor') ? 'ID_Proveedor' : (colExists($pdo,$table,'id_prov') ? 'id_prov' : null);

$COL_BL_ORI = colExists($pdo,$table,'idy_ubica') ? 'idy_ubica' : (colExists($pdo,$table,'idy_ubica_ori') ? 'idy_ubica_ori' : null);
$COL_BL_DES = colExists($pdo,$table,'idy_ubica_dest') ? 'idy_ubica_dest' : null;

$COL_LOTE = colExists($pdo,$table,'Lote') ? 'Lote' : (colExists($pdo,$table,'lote') ? 'lote' : null);
$COL_CAD  = colExists($pdo,$table,'Fecha_Caducidad') ? 'Fecha_Caducidad' : (colExists($pdo,$table,'Caducidad') ? 'Caducidad' : null);

$COL_STA = colExists($pdo,$table,'Status') ? 'Status' : null;

$COL_HINI = colExists($pdo,$table,'Hora_Ini') ? 'Hora_Ini' : (colExists($pdo,$table,'FechaHoraIni') ? 'FechaHoraIni' : null);
$COL_HFIN = colExists($pdo,$table,'Hora_Fin') ? 'Hora_Fin' : (colExists($pdo,$table,'FechaHoraFin') ? 'FechaHoraFin' : null);
$COL_FREG = colExists($pdo,$table,'FechaReg') ? 'FechaReg' : (colExists($pdo,$table,'Fecha') ? 'Fecha' : null);

// ===== validación mínima =====
if ($COL_FOLIO === null || $COL_ART === null || $COL_CANT === null) {
  ob_clean();
  echo json_encode([
    'draw' => (int)req('draw',1),
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => [],
    'error' => true,
    'message' => 'Faltan columnas críticas en t_ordenprod (folio/artículo/cantidad).'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

// ===== Tabla artículos confirmada por tu captura =====
$ART_TABLE    = 'c_articulo';
$ART_COL_KEY  = 'cve_articulo';
$ART_COL_DESC = 'des_articulo';

// ===== action=stats (KPIs) =====
if ($action === 'stats') {
  try {
    $where = [];
    $bind  = [];

    $dateCol = $COL_FREG ?? $COL_HINI ?? null;
    if ($dateCol) {
      $where[] = "DATE(t.$dateCol) BETWEEN :desde AND :hasta";
      $bind[':desde'] = $desde;
      $bind[':hasta'] = $hasta;
    }

    if ($COL_ALM && $almacen !== '') { $where[] = "t.$COL_ALM = :alm"; $bind[':alm'] = $almacen; }
    if ($COL_ZONA && $zona !== '')   { $where[] = "t.$COL_ZONA = :zona"; $bind[':zona'] = $zona; }
    if ($COL_PROV && $empresa !== ''){ $where[] = "t.$COL_PROV = :prov"; $bind[':prov'] = $empresa; }

    $w = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    $sql = "
      SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN ".($COL_STA ? "t.$COL_STA='P'" : "0")." THEN 1 ELSE 0 END) AS planeadas,
        SUM(CASE WHEN ".($COL_STA ? "t.$COL_STA='E'" : "0")." THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN ".($COL_STA ? "t.$COL_STA='T'" : "0")." THEN 1 ELSE 0 END) AS terminadas,
        SUM(CASE WHEN ".($COL_STA ? "t.$COL_STA='C'" : "0")." THEN 1 ELSE 0 END) AS canceladas
      FROM $table t
      $w
    ";

    $st = $pdo->prepare($sql);
    $st->execute($bind);
    $kpi = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    ob_clean();
    echo json_encode(['kpi'=>$kpi], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    ob_clean();
    echo json_encode(['kpi'=>[], 'error'=>true, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// ===== action=detalle (header + lines) =====
if ($action === 'detalle') {
  $folio = trim((string)req('folio',''));
  if ($folio === '') {
    ob_clean();
    echo json_encode(['ok'=>false,'msg'=>'Folio requerido'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  try {
    $sqlH = "
      SELECT
        t.$COL_FOLIO AS folio,
        t.$COL_ART AS producto,
        t.$COL_CANT AS cantidad,
        ".($COL_ALM ? "t.$COL_ALM" : "NULL")." AS almacen,
        ".($COL_ZONA ? "t.$COL_ZONA" : "NULL")." AS zona,
        ".($COL_STA ? "t.$COL_STA" : "NULL")." AS status,
        ".($COL_FREG ? "t.$COL_FREG" : ($COL_HINI ? "t.$COL_HINI" : "NULL"))." AS fecha_reg
      FROM $table t
      WHERE t.$COL_FOLIO = :folio
      LIMIT 1
    ";
    $stH = $pdo->prepare($sqlH);
    $stH->execute([':folio'=>$folio]);
    $header = $stH->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
      ob_clean();
      echo json_encode(['ok'=>false,'msg'=>'OT no encontrada'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    // detalle componentes si existe tabla
    $lines = [];
    $detTables = ['t_ordenprod_det','t_ordenprod_detalle','t_ordenprod_d'];
    $detTable = null;
    foreach ($detTables as $tt) {
      if (tableExists($pdo,$tt)) { $detTable = $tt; break; }
    }

    if ($detTable) {
      $cArt = colExists($pdo,$detTable,'Cve_Articulo') ? 'Cve_Articulo' : (colExists($pdo,$detTable,'Articulo') ? 'Articulo' : null);
      $cQty = colExists($pdo,$detTable,'Cantidad') ? 'Cantidad' : (colExists($pdo,$detTable,'Cant') ? 'Cant' : null);
      $cRef = colExists($pdo,$detTable,'Referencia') ? 'Referencia' : null;
      $cFol = colExists($pdo,$detTable,'Folio_Pro') ? 'Folio_Pro' : (colExists($pdo,$detTable,'Folio') ? 'Folio' : null);

      if ($cArt && $cQty && $cFol) {
        $sqlL = "
          SELECT
            d.$cArt AS Cve_Articulo,
            d.$cQty AS Cantidad,
            ".($cRef ? "d.$cRef" : "NULL")." AS Referencia
          FROM $detTable d
          WHERE d.$cFol = :folio
          ORDER BY 1
          LIMIT 500
        ";
        $stL = $pdo->prepare($sqlL);
        $stL->execute([':folio'=>$folio]);
        $lines = $stL->fetchAll(PDO::FETCH_ASSOC) ?: [];
      }
    }

    ob_clean();
    echo json_encode(['ok'=>true,'header'=>$header,'lines'=>$lines], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    ob_clean();
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// ===== DataTables serverSide =====
try {
  $draw   = (int)req('draw', 1);
  $start  = (int)req('start', 0);
  $length = (int)req('length', 25);
  if ($length <= 0) $length = 25;
  if ($length > 200) $length = 200;

  $search = '';
  if (isset($_REQUEST['search']['value'])) {
    $search = trim((string)$_REQUEST['search']['value']);
  }

  // WHERE dinámico
  $where = [];
  $bind  = [];

  $dateCol = $COL_FREG ?? $COL_HINI ?? null;
  if ($dateCol) {
    $where[] = "DATE(t.$dateCol) BETWEEN :desde AND :hasta";
    $bind[':desde'] = $desde;
    $bind[':hasta'] = $hasta;
  }

  if ($COL_ALM && $almacen !== '') { $where[] = "t.$COL_ALM = :alm"; $bind[':alm'] = $almacen; }
  if ($COL_ZONA && $zona !== '')   { $where[] = "t.$COL_ZONA = :zona"; $bind[':zona'] = $zona; }
  if ($COL_PROV && $empresa !== ''){ $where[] = "t.$COL_PROV = :prov"; $bind[':prov'] = $empresa; }
  if ($COL_STA && $status !== '')  { $where[] = "t.$COL_STA = :sta";  $bind[':sta']  = $status; }

  // búsqueda global
  if ($search !== '') {
    $where[] = "("
      . "t.$COL_FOLIO LIKE :q "
      . "OR t.$COL_ART LIKE :q "
      . "OR a.$ART_COL_DESC LIKE :q "
      . ($COL_LOTE ? "OR t.$COL_LOTE LIKE :q " : "")
      . ")";
    $bind[':q'] = "%$search%";
  }

  $w = $where ? ('WHERE '.implode(' AND ', $where)) : '';

  // ORDER BY (mapeo a columnas visibles del DataTable)
  $orderColIdx = 0;
  $orderDir = 'DESC';
  if (isset($_REQUEST['order'][0]['column'])) $orderColIdx = (int)$_REQUEST['order'][0]['column'];
  if (isset($_REQUEST['order'][0]['dir'])) $orderDir = strtoupper((string)$_REQUEST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

  $orderMap = [
    2  => ($COL_ZONA ? "t.$COL_ZONA" : "t.$COL_FOLIO"),
    3  => ($COL_BL_ORI ? "uo.CodigoCSD" : "t.$COL_FOLIO"),
    4  => "t.$COL_ART",
    5  => "a.$ART_COL_DESC",
    6  => ($COL_LOTE ? "t.$COL_LOTE" : "t.$COL_FOLIO"),
    7  => ($COL_CAD ? "t.$COL_CAD" : "t.$COL_FOLIO"),
    8  => "t.$COL_CANT",
    9  => ($COL_BL_DES ? "ud.CodigoCSD" : "t.$COL_FOLIO"),
    10 => ($COL_HINI ? "t.$COL_HINI" : ($COL_FREG ? "t.$COL_FREG" : "t.$COL_FOLIO")),
    11 => ($COL_HFIN ? "t.$COL_HFIN" : "t.$COL_FOLIO"),
  ];

  $orderBy = $orderMap[$orderColIdx] ?? ($COL_HINI ? "t.$COL_HINI" : "t.$COL_FOLIO");
  $orderSql = "ORDER BY $orderBy $orderDir";

  // JOINs
  $joinArt = "LEFT JOIN $ART_TABLE a ON a.$ART_COL_KEY = t.$COL_ART";
  $joinUO  = $COL_BL_ORI ? "LEFT JOIN c_ubicacion uo ON uo.idy_ubica = t.$COL_BL_ORI" : "";
  $joinUD  = $COL_BL_DES ? "LEFT JOIN c_ubicacion ud ON ud.idy_ubica = t.$COL_BL_DES" : "";

  // SELECT garantizando nombres esperados por UI
  $sel = "
    SELECT
      t.$COL_FOLIO AS folio,
      ".($COL_ZONA ? "t.$COL_ZONA" : "NULL")." AS zona,
      ".($COL_BL_ORI ? "uo.CodigoCSD" : "NULL")." AS bl_origen,
      t.$COL_ART AS clave,
      a.$ART_COL_DESC AS descripcion,
      ".($COL_LOTE ? "t.$COL_LOTE" : "NULL")." AS lote,
      ".($COL_CAD ? "DATE_FORMAT(t.$COL_CAD,'%Y-%m-%d')" : "NULL")." AS caducidad,
      t.$COL_CANT AS cantidad,
      ".($COL_BL_DES ? "ud.CodigoCSD" : "NULL")." AS bl_destino,
      ".($COL_HINI ? "DATE_FORMAT(t.$COL_HINI,'%Y-%m-%d %H:%i:%s')" : "NULL")." AS hora_ini,
      ".($COL_HFIN ? "DATE_FORMAT(t.$COL_HFIN,'%Y-%m-%d %H:%i:%s')" : "NULL")." AS hora_fin,
      CASE
        WHEN ".($COL_STA ? "t.$COL_STA='T'" : "0")." THEN 100
        WHEN ".($COL_STA ? "t.$COL_STA='E'" : "0")." THEN 90
        ELSE 0
      END AS avance
    FROM $table t
    $joinArt
    $joinUO
    $joinUD
    $w
    $orderSql
    LIMIT :start, :len
  ";

  // recordsTotal
  $sqlTotal = "SELECT COUNT(*) FROM $table t";

  // recordsFiltered
  $sqlFilt  = "
    SELECT COUNT(*)
    FROM $table t
    $joinArt
    $joinUO
    $joinUD
    $w
  ";

  $total = (int)$pdo->query($sqlTotal)->fetchColumn();

  $stF = $pdo->prepare($sqlFilt);
  $stF->execute($bind);
  $filtered = (int)$stF->fetchColumn();

  $st = $pdo->prepare($sel);
  foreach ($bind as $k=>$v) $st->bindValue($k, $v);
  $st->bindValue(':start', $start, PDO::PARAM_INT);
  $st->bindValue(':len', $length, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

  ob_clean();
  echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $rows
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  ob_clean();
  echo json_encode([
    'draw' => (int)req('draw',1),
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => [],
    'error' => true,
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
