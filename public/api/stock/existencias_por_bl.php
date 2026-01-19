<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../app/db.php';

try {
  $pdo = db();

  $bl = $_GET['bl'] ?? $_GET['CodigoCSD'] ?? null;
  if ($bl === null || trim($bl)==='') {
    echo json_encode(['ok'=>0,'error'=>'Falta parámetro bl (CodigoCSD)']);
    exit;
  }

  // Reusa el core: misma lógica, solo fija el filtro.
  $_GET['bl'] = $bl;

  require __DIR__ . '/existencias_ubicacion_total.php';

} catch (Throwable $e) {
  echo json_encode(['ok'=>0,'error'=>$e->getMessage()]);
}
