<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if($action!=='kpi'){ echo json_encode(['error'=>'Acción no válida']); exit; }

$sql = "
SELECT
  Tipo_Cat,
  COUNT(*) AS total,
  SUM(IFNULL(Activo,1)=1) AS activos,
  SUM(IFNULL(Activo,1)=0) AS inactivos,
  SUM(
    (Tipo_Cat IS NULL OR TRIM(Tipo_Cat)='')
    OR (Des_Motivo IS NULL OR TRIM(Des_Motivo)='')
  ) AS inconsistentes
FROM c_motivo
GROUP BY Tipo_Cat
ORDER BY Tipo_Cat
";
echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
