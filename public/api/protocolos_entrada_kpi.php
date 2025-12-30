<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if($action!=='kpi'){ echo json_encode(['error'=>'Acción no válida']); exit; }

$sql = "
SELECT
  COUNT(*) AS total,
  SUM(IFNULL(Activo,1)=1) AS activos,
  SUM(IFNULL(Activo,1)=0) AS inactivos,
  SUM(
    (ID_Protocolo IS NULL OR TRIM(ID_Protocolo)='')
    OR (descripcion IS NULL OR TRIM(descripcion)='')
  ) AS inconsistentes
FROM t_protocolo
";
echo json_encode($pdo->query($sql)->fetch(PDO::FETCH_ASSOC));
