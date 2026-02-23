<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

$almacen = $_GET['almacen'] ?? null;
$estado  = $_GET['estado'] ?? 1;

try {

    if (!$almacen) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            ID_Embarque AS id,
            cve_ubicacion,
            descripcion,
            AreaStagging,
            status,
            Activo
        FROM t_ubicacionembarque
        WHERE cve_almac = ?
          AND Activo = ?
        ORDER BY ID_Embarque DESC
    ");

    $stmt->execute([$almacen, $estado]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}