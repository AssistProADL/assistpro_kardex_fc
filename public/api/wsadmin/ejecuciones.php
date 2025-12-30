<?php
require_once __DIR__ . '/../../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$rows = db_all("
  SELECT fecha_ini, cliente, evento, resultado, trace_id
  FROM log_ws_ejecucion
  ORDER BY fecha_ini DESC
  LIMIT 25
");

echo json_encode([
  'ok' => true,
  'data' => $rows
]);
exit;
