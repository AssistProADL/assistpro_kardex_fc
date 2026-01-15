<?php
// public/api/lp/lookup_lp.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../app/db.php';

$q = trim($_GET['q'] ?? '');
$minLen = 3;

if ($q === '') {
  echo json_encode(['ok'=>true,'data'=>[],'meta'=>['min_len'=>$minLen,'q'=>$q]], JSON_UNESCAPED_UNICODE);
  exit;
}
if (mb_strlen($q) < $minLen) {
  echo json_encode(['ok'=>true,'data'=>[],'meta'=>['min_len'=>$minLen,'q'=>$q]], JSON_UNESCAPED_UNICODE);
  exit;
}

$needle = $q;

// Derivación robusta:
// - PALLET: ts_existenciatarima (ntarima) -> idy_ubica -> c_ubicacion.CodigoCSD
// - CONTENEDOR: ts_existenciacajas (Id_Caja) -> idy_ubica -> c_ubicacion.CodigoCSD
$sql = "
  SELECT
    ch.CveLP,
    ch.tipo,
    ch.cve_almac,
    ch.Activo,
    ch.IDContenedor,

    CASE
      WHEN UPPER(ch.tipo) = 'PALLET' THEN ut.idy_ubica
      ELSE uc.idy_ubica
    END AS idy_ubica,

    CASE
      WHEN UPPER(ch.tipo) = 'PALLET' THEN u1.CodigoCSD
      ELSE u2.CodigoCSD
    END AS BL

  FROM c_charolas ch

  LEFT JOIN (
    SELECT ntarima, MAX(idy_ubica) AS idy_ubica
    FROM ts_existenciatarima
    WHERE idy_ubica IS NOT NULL
    GROUP BY ntarima
  ) ut ON ut.ntarima = ch.IDContenedor
  LEFT JOIN c_ubicacion u1 ON u1.idy_ubica = ut.idy_ubica

  LEFT JOIN (
    SELECT Id_Caja, MAX(idy_ubica) AS idy_ubica
    FROM ts_existenciacajas
    WHERE idy_ubica IS NOT NULL
    GROUP BY Id_Caja
  ) uc ON uc.Id_Caja = ch.IDContenedor
  LEFT JOIN c_ubicacion u2 ON u2.idy_ubica = uc.idy_ubica

  WHERE ch.CveLP LIKE ?
  ORDER BY ch.Activo DESC, ch.CveLP
  LIMIT 200
";

try {
  $rows = db_all($sql, ["%{$needle}%"]);
  echo json_encode(['ok'=>true,'data'=>$rows,'meta'=>['min_len'=>$minLen,'q'=>$q]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'msg'=>'PHP_EXCEPTION','err'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
