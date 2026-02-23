<?php

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

try {

    $almacen = $_GET['almacen'] ?? null;

    if (!$almacen) {
        echo json_encode([
            "success" => false,
            "error" => "Almacén requerido"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            cve_almac AS zona_id,
            clave_almacen AS zona_clave,
            des_almac AS zona,
            cve_almacenp AS almacen_id
        FROM c_almacen
        WHERE cve_almacenp = ?
        ORDER BY des_almac
    ");

    $stmt->execute([$almacen]);

    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "zonas" => $zonas
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}