<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if ($action !== 'kpi') {
  echo json_encode(['error' => 'Acción no válida']);
  exit;
}

$sql = "
SELECT
  COUNT(*) AS total,
  SUM(IFNULL(Status,1)=1) AS activos,
  SUM(IFNULL(Status,1)=0) AS inactivos,
  SUM(
    (Clave IS NULL OR TRIM(Clave)='')
    OR (Motivo IS NULL OR TRIM(Motivo)='')
  ) AS inconsistentes
FROM motivosnoventa
";
echo json_encode($pdo->query($sql)->fetch(PDO::FETCH_ASSOC));
