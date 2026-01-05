<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if($action!=='kpi'){ echo json_encode(['error'=>'Acción no válida']); exit; }

/*
KPI por "scope" de negocio (DP/CI) filtrando Tipo_Cat='A'
- DP = dev_proveedor=1
- CI = dev_proveedor=0
*/
$sql = "
SELECT
  CASE WHEN IFNULL(dev_proveedor,0)=1 THEN 'DP' ELSE 'CI' END AS scope,
  COUNT(*) AS total,
  SUM(IFNULL(Activo,1)=1) AS activos,
  SUM(IFNULL(Activo,1)=0) AS inactivos,
  SUM(
    (Des_Motivo IS NULL OR TRIM(Des_Motivo)='')
  ) AS inconsistentes
FROM c_motivo
WHERE IFNULL(Tipo_Cat,'A')='A'
GROUP BY 1
ORDER BY scope
";
echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
