<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$almacen = $_POST['almacen'] ?? '';

$sql = "SELECT ID_Ruta AS id, cve_ruta AS nombre
        FROM t_ruta
        WHERE Activo = 1";

$params = [];
if ($almacen !== '') {
  $sql .= " AND cve_almacenp = :almacen";
  $params[':almacen'] = $almacen;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
