<?php
// public/api/zonas_api.php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$almacenp_id = $_GET['almacenp_id'] ?? null;
$solo_activas = (int)($_GET['solo_activas'] ?? 1);

if (!$almacenp_id) {
  echo json_encode(['ok'=>true,'data'=>[]], JSON_UNESCAPED_UNICODE);
  exit;
}

$where = "WHERE a.cve_almacenp = :ap";
$params = [':ap' => $almacenp_id];

if ($solo_activas === 1) {
  $where .= " AND COALESCE(a.Activo,1)=1";
}

$sql = "
  SELECT
    a.cve_almac,
    a.clave_almacen,
    a.des_almac,
    a.cve_almacenp,
    COALESCE(a.Activo,1) AS Activo
  FROM c_almacen a
  $where
  ORDER BY a.des_almac
";

$rows = db_all($sql, $params);
echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
