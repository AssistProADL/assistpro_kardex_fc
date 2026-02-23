<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode(['ok'=>false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id     = $data['id'] ?? null;
$estado = $data['estado'] ?? null;

if (!$id || !isset($estado)) {
    echo json_encode(['ok'=>false]);
    exit;
}

try {

    $sql = "
        UPDATE tubicacionesretencion
        SET Activo = :estado
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'estado' => (int)$estado,
        'id' => $id
    ]);

    echo json_encode(['ok'=>true]);

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}