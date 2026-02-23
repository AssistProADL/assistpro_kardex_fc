<?php

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

try {

 

    // ZONAS
    $zonas = $pdo->query("
        SELECT 
            cve_almac AS zona_id,
            clave_almacen AS zona_clave,
            des_almac AS zona,
            cve_almacenp AS almacen_id
        FROM c_almacen
        ORDER BY des_almac
    ")->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
           "zonas" => $zonas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
