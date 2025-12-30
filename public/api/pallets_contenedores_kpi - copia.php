<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_GET['action'] ?? 'kpi';

if($action==='kpi'){
  $sql = "
    SELECT
      ap.clave          AS almac_clave,
      ap.nombre         AS almac_nombre,
      MIN(ap.id)        AS almac_id,
      COUNT(ch.IDContenedor) AS total,
      SUM(IFNULL(ch.Activo,1)=1) AS activas,
      SUM(IFNULL(ch.Activo,1)=0) AS inactivas,
      SUM(IFNULL(ch.Permanente,0)=1) AS permanentes,
      SUM(IFNULL(ch.Permanente,0)=0) AS no_permanentes,
      SUM(ch.CveLP IS NOT NULL AND ch.CveLP <> '') AS con_lp,
      SUM(ch.CveLP IS NULL OR ch.CveLP = '') AS libres
    FROM c_almacenp ap
    LEFT JOIN c_charolas ch ON ch.cve_almac = ap.id
    GROUP BY ap.clave, ap.nombre
    ORDER BY ap.clave
  ";
  echo json_encode($pdo->query($sql)->fetchAll());
  exit;
}

echo json_encode(['error'=>'Acción no válida']);
