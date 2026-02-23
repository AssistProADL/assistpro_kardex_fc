<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../app/db.php';
$pdo = db_pdo();

$input = json_decode(file_get_contents("php://input"), true);

$id    = $input['id'] ?? null;
$campo = $input['campo'] ?? null;
$valor = $input['valor'] ?? null;

if (!$id || !$campo) {
    echo json_encode(["ok"=>false]);
    exit;
}

$permitidos = ['AreaStaging','status'];

if (!in_array($campo,$permitidos)) {
    echo json_encode(["ok"=>false]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE t_ubicacionembarque
        SET $campo = ?
        WHERE ID_Embarque = ?
    ");

    $stmt->execute([$valor,$id]);

    echo json_encode(["ok"=>true]);

} catch (Exception $e) {
    echo json_encode(["ok"=>false]);
}