<?php

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

try {

    // EMPRESAS
    $empresas = $pdo->query("
        SELECT 
            cve_cia AS empresa_id,
            clave_empresa AS empresa_clave,
            des_cia AS empresa
        FROM c_compania
        WHERE Activo = 1
        ORDER BY clave_empresa
    ")->fetchAll(PDO::FETCH_ASSOC);


    // ALMACENES
    $almacenes = $pdo->query("
        SELECT 
            id AS almacen_id,
            clave AS almacen_clave,
            nombre AS almacen,
            cve_cia AS empresa_id
        FROM c_almacenp
        WHERE Activo = 1
        ORDER BY clave
    ")->fetchAll(PDO::FETCH_ASSOC);


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
        "empresas" => $empresas,
        "almacenes" => $almacenes,
        "zonas" => $zonas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
