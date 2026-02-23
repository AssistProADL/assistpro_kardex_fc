<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../app/db.php';
$pdo = db_pdo();

$input = json_decode(file_get_contents("php://input"), true);

$id     = $input['id'] ?? null;
$estado = $input['estado'] ?? null;

if (!$id) {
    echo json_encode(["ok"=>false]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE t_ubicacionembarque
        SET Activo = ?
        WHERE ID_Embarque = ?
    ");

    $stmt->execute([$estado,$id]);

    echo json_encode(["ok"=>true]);

} catch (Exception $e) {
    echo json_encode(["ok"=>false]);
}