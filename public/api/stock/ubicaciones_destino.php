<?php
// public/api/stock/ubicaciones_destino.php
// Destinos válidos para Traslado LP: c_ubicacion.Activo=1, CodigoCSD no nulo, AcomodoMixto='S'

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function respond($payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $almacen = isset($_GET['almacen']) ? trim((string)$_GET['almacen']) : '';
  $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
  $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;

  $sql = "
    SELECT
      idy_ubica, cve_almac, CodigoCSD, AcomodoMixto, Activo
    FROM c_ubicacion
    WHERE Activo = 1
      AND CodigoCSD IS NOT NULL
      AND CodigoCSD <> ''
      AND AcomodoMixto = 'S'
      AND (:almacen = '' OR cve_almac = :almacen)
      AND (:q = '' OR CodigoCSD LIKE CONCAT('%', :q, '%'))
    ORDER BY CodigoCSD
    LIMIT :limit
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':almacen', $almacen);
  $stmt->bindValue(':q', $q);
  $stmt->bindValue(':limit', $limit, 2);  // PDO::PARAM_INT
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  respond([
    'ok' => 1,
    'service' => 'ubicaciones_destino',
    'filters' => ['almacen'=>$almacen,'q'=>$q,'limit'=>$limit],
    'rows' => $rows,
  ]);

} catch (Throwable $e) {
  respond(['ok'=>0,'error'=>$e->getMessage()], 500);
}
