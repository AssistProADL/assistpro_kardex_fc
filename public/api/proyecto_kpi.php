<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if($action!=='kpi'){ echo json_encode(['error'=>'Acción no válida']); exit; }

/*
Cards por Almacén (se trae nombre desde c_almacen si existe; si no, queda con Id)
- SI NO existe c_almacen en esta BD, igual funciona (etiqueta: ALMACEN #id)
*/
$sql = "
SELECT
  p.id_almacen AS IdAlmacen,
  COALESCE(NULLIF(a.des_almac,''), CONCAT('ALMACEN #', p.id_almacen)) AS Almacen,
  COUNT(*) AS total,
  SUM( (p.Cve_Proyecto IS NULL OR TRIM(p.Cve_Proyecto)='') OR (p.Des_Proyecto IS NULL OR TRIM(p.Des_Proyecto)='') ) AS inconsistentes
FROM c_proyecto p
LEFT JOIN c_almacen a ON a.Id = p.id_almacen
GROUP BY p.id_almacen, Almacen
ORDER BY (p.id_almacen=0) DESC, Almacen
";

echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
