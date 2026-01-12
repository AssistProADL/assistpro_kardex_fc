<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if ($action !== 'kpi') {
  echo json_encode(['error' => 'Acción no válida']);
  exit;
}

/*
Cards por Almacén (se trae nombre desde c_almacen si existe; si no, queda con Id)
- SI NO existe c_almacen en esta BD, igual funciona (etiqueta: ALMACEN #id)
*/
$sql = "
SELECT
  p.id_almacen AS IdAlmacen,
  CONCAT('ALMACEN #', p.id_almacen) AS Almacen,
  COUNT(*) AS total,
  SUM( (p.Cve_Proyecto IS NULL OR TRIM(p.Cve_Proyecto)='') OR (p.Des_Proyecto IS NULL OR TRIM(p.Des_Proyecto)='') ) AS inconsistentes
FROM c_proyecto p
GROUP BY p.id_almacen
ORDER BY (p.id_almacen=0) DESC, p.id_almacen
";

echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
