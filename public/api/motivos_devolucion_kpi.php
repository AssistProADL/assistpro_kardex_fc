<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo=db_pdo();

$sql="
SELECT
  COUNT(*) AS total,
  SUM(IFNULL(Activo,1)=1) AS activos,
  SUM(IFNULL(Activo,1)=0) AS inactivos,
  SUM(
    (Clave_motivo IS NULL OR TRIM(Clave_motivo)='')
    OR (MOT_DESC IS NULL OR TRIM(MOT_DESC)='')
  ) AS inconsistentes
FROM motivos_devolucion
";
echo json_encode($pdo->query($sql)->fetch(PDO::FETCH_ASSOC));
