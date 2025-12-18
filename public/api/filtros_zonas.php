<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json');

$id_almacen = $_GET['id_almacen'] ?? null;

if (!$id_almacen) {
  echo json_encode([]);
  exit;
}

$rows = db_all("
  SELECT DISTINCT cve_zona AS id, cve_zona AS nombre
  FROM c_almacen
  WHERE id_almacen = ?
  ORDER BY cve_zona
", [$id_almacen]);

echo json_encode($rows);
