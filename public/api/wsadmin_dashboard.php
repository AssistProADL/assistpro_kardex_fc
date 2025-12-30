<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

// Si tu auth_check redirige a login, cámbialo para APIs:
// - o detecta "es API" y en lugar de redirect regresa 401 JSON.
// Ejemplo rápido:
if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'NO_AUTH']);
  exit;
}

$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;
$cliente = $_GET['cliente'] ?? 'ALL';
$evento = $_GET['evento'] ?? 'ALL';
$resultado = $_GET['resultado'] ?? 'ALL';
$q = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = min(100, max(10, (int)($_GET['pageSize'] ?? 25)));
$offset = ($page-1) * $pageSize;

// OJO: ajusta nombres según tu BD real (en tu captura existe log_ws_ejecucion)
$where = " WHERE 1=1 ";
$params = [];

if ($desde) { $where .= " AND fecha_ini >= :desde "; $params[':desde'] = $desde; }
if ($hasta) { $where .= " AND fecha_ini <= :hasta "; $params[':hasta'] = $hasta; }
if ($cliente !== 'ALL') { $where .= " AND cliente = :cliente "; $params[':cliente'] = $cliente; }
if ($evento !== 'ALL') { $where .= " AND evento = :evento "; $params[':evento'] = $evento; }
if ($resultado !== 'ALL') { $where .= " AND resultado = :resultado "; $params[':resultado'] = $resultado; }
if ($q !== '') { $where .= " AND (trace_id LIKE :q OR referencia LIKE :q) "; $params[':q'] = "%$q%"; }

// KPIs
$sqlKpi = "SELECT
  COUNT(*) total,
  SUM(CASE WHEN resultado='OK' THEN 1 ELSE 0 END) ok,
  SUM(CASE WHEN resultado='ERROR' THEN 1 ELSE 0 END) err,
  SUM(CASE WHEN resultado='BLOQUEADO' THEN 1 ELSE 0 END) bloqueado
FROM log_ws_ejecucion
$where";
$kpi = db_one($sqlKpi, $params) ?: ['total'=>0,'ok'=>0,'err'=>0,'bloqueado'=>0];

// Total rows (paginación)
$total = (int)db_val("SELECT COUNT(*) FROM log_ws_ejecucion $where", $params);

// Lista
$sql = "SELECT id, fecha_ini, fecha_fin, trace_id, evento, referencia, sistema, dispositivo, usuario, ip_origen, conexion_id, resultado,
TIMESTAMPDIFF(MICROSECOND, fecha_ini, IFNULL(fecha_fin, NOW()))/1000 duracion_ms
FROM log_ws_ejecucion
$where
ORDER BY id DESC
LIMIT $pageSize OFFSET $offset";
$rows = db_all($sql, $params);

echo json_encode([
  'ok' => true,
  'kpi' => $kpi,
  'page' => $page,
  'pageSize' => $pageSize,
  'total' => $total,
  'rows' => $rows
]);
