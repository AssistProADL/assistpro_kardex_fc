<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$sql = "SELECT id, nombre FROM c_almacenp WHERE Activo = 1 ORDER BY nombre";
echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
