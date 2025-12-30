<?php
// public/api/ubicaciones_por_almacen.php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$almacenp_id = $_GET['almacenp_id'] ?? null;
$cve_almac   = $_GET['cve_almac'] ?? null;

if (!$almacenp_id) {
  echo json_encode([]);
  exit;
}

$where = "WHERE a.cve_almacenp = :almacenp_id";
$params = ['almacenp_id' => $almacenp_id];

if ($cve_almac !== null && $cve_almac !== '') {
  $where .= " AND u.cve_almac = :cve_almac";
  $params['cve_almac'] = (int)$cve_almac;
}

$sql = "
  SELECT
    u.cve_almac        AS cve_almac,
    u.CodigoCSD        AS bl,
    u.cve_pasillo      AS pasillo,
    u.cve_rack         AS rack,
    u.cve_nivel        AS nivel,
    u.Seccion          AS seccion,
    u.Ubicacion        AS posicion
  FROM c_ubicacion u
  INNER JOIN c_almacen a
    ON a.cve_almac = u.cve_almac
  $where
  ORDER BY u.CodigoCSD
";

$data = db_all($sql, $params);
echo json_encode($data, JSON_UNESCAPED_UNICODE);
