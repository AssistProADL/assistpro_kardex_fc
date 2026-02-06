<?php
// /public/api/unidades_empaque.php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

db_pdo();
global $pdo;

function out($ok, $data = null, $msg = null) {
  echo json_encode([
    'ok'  => (bool)$ok,
    'msg' => $msg,
    'data'=> $data
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

function one($sql, $params){
  global $pdo;
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

try {
  $cve_articulo = trim($_GET['cve_articulo'] ?? '');
  $cve_almac    = trim($_GET['cve_almac'] ?? '');

  if ($cve_articulo === '') out(false, null, 'cve_articulo es obligatorio');

  // Normalizaciones
  $cve_raw  = $cve_articulo;
  $cve_base = preg_replace('~/.*$~', '', $cve_raw); // quita sufijo /9
  $cve_base = trim($cve_base);

  // Query base (misma salida siempre)
  $baseSQL = "
    SELECT
      a.cve_articulo,
      a.cve_almac,
      a.unidadMedida                              AS um_base_id,
      ub.des_umed                                 AS um_base_nombre,
      a.cve_umed                                  AS cve_umed_legacy,
      a.empq_cveumed                              AS um_empaque_id,
      ue.des_umed                                 AS um_empaque_nombre,
      COALESCE(NULLIF(a.num_multiplo,0),1)        AS factor
    FROM c_articulo a
    LEFT JOIN c_unimed ub ON ub.id_umed = a.unidadMedida
    LEFT JOIN c_unimed ue ON ue.id_umed = a.empq_cveumed
    WHERE 1=1
  ";

  $almFilter = "";
  $params = [];

  if ($cve_almac !== '') {
    $almFilter = " AND a.cve_almac = :alm ";
    $params[':alm'] = (int)$cve_almac;
  }

  // 1) Match exacto
  $sql1 = $baseSQL . " AND a.cve_articulo = :cve " . $almFilter . " LIMIT 1";
  $r = one($sql1, array_merge($params, [':cve' => $cve_raw]));

  // 2) Match exacto sin sufijo (/9)
  if (!$r && $cve_base !== '' && $cve_base !== $cve_raw) {
    $sql2 = $baseSQL . " AND a.cve_articulo = :cve " . $almFilter . " LIMIT 1";
    $r = one($sql2, array_merge($params, [':cve' => $cve_base]));
  }

  // 3) Fallback por prefijo (controlado) si aún no aparece
  //    Útil cuando en DB guardan "B6-OUT-STA3-00-608FMX-CHERRY M7" y tú mandas "...M7/9"
  if (!$r && $cve_base !== '') {
    $sql3 = $baseSQL . " AND a.cve_articulo LIKE :like " . $almFilter . " ORDER BY a.cve_articulo ASC LIMIT 1";
    $r = one($sql3, array_merge($params, [':like' => $cve_base . '%']));
  }

  if (!$r) out(false, null, 'articulo no encontrado');

  // Normalización de salida: si no hay um empaque, fuerza factor=1
  if (empty($r['um_empaque_id']) || empty($r['um_empaque_nombre'])) {
    $r['um_empaque_id'] = null;
    $r['um_empaque_nombre'] = null;
    $r['factor'] = 1;
  }

  out(true, $r, null);

} catch (Throwable $e) {
  out(false, ['detail' => $e->getMessage()], 'Error servidor');
}
