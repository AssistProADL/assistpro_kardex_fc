<?php
// public/api/stock/lookup_lp_origen.php
// Devuelve: LP maestro + BL actual (si es único) + detalle SKU/Lote/Cantidad (18,4)

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function respond($payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $cvelp = trim((string)($_GET['CveLP'] ?? $_GET['cvelp'] ?? ''));
  if ($cvelp === '') respond(['ok'=>0,'error'=>'Falta parámetro CveLP.'], 400);

  // Maestro LP
  $st = $pdo->prepare("SELECT IDContenedor, cve_almac, tipo, Activo, Clave_Contenedor, CveLP FROM c_charolas WHERE CveLP = :lp LIMIT 1");
  $st->execute([':lp'=>$cvelp]);
  $lp = $st->fetch(PDO::FETCH_ASSOC);
  if (!$lp) respond(['ok'=>0,'error'=>'CveLP no existe en c_charolas.'], 404);

  $idCont = (int)$lp['IDContenedor'];

  // Detalle desde vista (inventario real derivado de kardex)
  $sql = "
    SELECT v.bl, v.cve_articulo, v.cve_lote, SUM(v.cantidad) AS cantidad
    FROM v_inv_existencia_multinivel v
    WHERE v.nTarima = :idCont AND v.cantidad > 0
    GROUP BY v.bl, v.cve_articulo, v.cve_lote
    ORDER BY v.bl, v.cve_articulo, v.cve_lote
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':idCont'=>$idCont]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  if (!$rows) respond(['ok'=>0,'error'=>'LP sin existencia positiva.'], 400);

  $bls = [];
  $total = 0.0;
  foreach ($rows as $r) {
    $bls[$r['bl']] = 1;
    $total += (float)$r['cantidad'];
  }
  $bl_list = array_keys($bls);
  $bl_actual = (count($bl_list) === 1) ? $bl_list[0] : null;

  respond([
    'ok'=>1,
    'service'=>'lookup_lp_origen',
    'lp'=>$lp,
    'bl_actual'=>$bl_actual,
    'bls'=>$bl_list,
    'total'=>$total,
    'rows'=>$rows
  ]);

} catch (Throwable $e) {
  respond(['ok'=>0,'error'=>$e->getMessage()], 500);
}
