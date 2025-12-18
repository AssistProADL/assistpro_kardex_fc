<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_GET['action'] ?? 'kpi';

if($action==='kpi'){
  $sql = "
    SELECT
      cve_almac,
      COUNT(*) AS total,
      SUM(IFNULL(Activo,1)=1) AS activas,
      SUM(IFNULL(Activo,1)=0) AS inactivas,
      SUM(IFNULL(Permanente,0)=1) AS permanentes,
      SUM(IFNULL(Permanente,0)=0) AS no_permanentes,
      SUM(CveLP IS NOT NULL AND CveLP <> '') AS con_lp,
      SUM(CveLP IS NULL OR CveLP = '') AS libres
    FROM c_charolas
    GROUP BY cve_almac
    ORDER BY cve_almac
  ";
  echo json_encode($pdo->query($sql)->fetchAll());
  exit;
}

echo json_encode(['error'=>'Acción no válida']);
