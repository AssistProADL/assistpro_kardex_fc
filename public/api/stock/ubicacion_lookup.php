<?php
// public/api/stock/ubicacion_lookup.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function respond($payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $bl = trim((string)($_GET['bl'] ?? $_GET['CodigoCSD'] ?? ''));
  if ($bl === '') respond(['ok'=>0,'error'=>'Falta parámetro bl.'], 400);

  $st = $pdo->prepare("
    SELECT
      CodigoCSD,
      idy_ubica,
      cve_almac,
      Descripcion,
      AcomodoMixto,
      Activo
    FROM c_ubicacion
    WHERE CodigoCSD = :bl
    LIMIT 1
  ");
  $st->execute([':bl'=>$bl]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) respond(['ok'=>0,'error'=>'BL no existe.'], 404);

  $row['AcomodoMixto'] = (string)($row['AcomodoMixto'] ?? '');
  $row['Activo'] = (int)($row['Activo'] ?? 0);

  $row['ok_destino'] =
    ($row['Activo'] === 1) && (strtoupper($row['AcomodoMixto']) === 'S');

  respond([
    'ok'=>1,
    'service'=>'ubicacion_lookup',
    'row'=>$row
  ]);

} catch (Throwable $e) {
  respond(['ok'=>0,'error'=>$e->getMessage()], 500);
}
