<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../app/db.php';

try {
  $pdo = db();

  $cvelp = $_GET['CveLP'] ?? $_GET['cvelp'] ?? null;
  if ($cvelp === null || trim($cvelp)==='') {
    echo json_encode(['ok'=>0,'error'=>'Falta parámetro CveLP']);
    exit;
  }

  // Copia mínima del core: aquí sí agregamos filtro por ch.CveLP SIN romper nada.
  $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
  $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

  $sql = "
    SELECT
      v.nivel,
      v.cve_almac,
      v.idy_ubica,
      v.bl,
      v.cve_articulo,
      v.cve_lote,
      v.id_caja,
      v.nTarima,
      ch.CveLP AS CveLP,
      v.cantidad AS existencia_total,
      v.epc,
      v.code,
      v.fuente
    FROM v_inv_existencia_multinivel v
    LEFT JOIN c_charolas ch
      ON ch.IDContenedor = v.nTarima
    WHERE ch.CveLP = :cvelp
      AND v.cantidad > 0
    ORDER BY v.cve_articulo, v.idy_ubica
    LIMIT :limit OFFSET :offset
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':cvelp', $cvelp);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok'=>1,
    'service'=>'existencias_por_lp',
    'filters'=>$_GET,
    'rows'=>$rows
  ]);

} catch (Throwable $e) {
  echo json_encode(['ok'=>0,'error'=>$e->getMessage()]);
}
