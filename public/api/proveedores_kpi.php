<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if($action!=='kpi'){ echo json_encode(['error'=>'Acción no válida']); exit; }

/*
Cards por Empresa (texto). Si Empresa viene NULL/0/'' se agrupa como SIN EMPRESA.
Inconsistentes = sin cve_proveedor o sin pais o sin Empresa/Nombre
*/
$sql = "
SELECT
  CASE
    WHEN Empresa IS NULL OR Empresa=0 OR Empresa='' THEN 'SIN EMPRESA'
    ELSE CAST(Empresa AS CHAR)
  END AS Empresa,
  COUNT(*) AS total,
  SUM(IFNULL(Activo,1)=1) AS activos,
  SUM(IFNULL(Activo,1)=0) AS inactivos,
  SUM(
    (cve_proveedor IS NULL OR TRIM(cve_proveedor)='')
    OR (pais IS NULL OR TRIM(pais)='')
    OR ( (Empresa IS NULL OR Empresa='' OR Empresa=0) AND (Nombre IS NULL OR TRIM(Nombre)='') )
  ) AS inconsistentes
FROM c_proveedores
GROUP BY 1
ORDER BY (Empresa='SIN EMPRESA') DESC, Empresa
";
echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
