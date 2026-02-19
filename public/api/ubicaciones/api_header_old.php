<?php

require_once __DIR__ . '/../../../app/db.php';

db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

try {

    // 🔹 EMPRESAS
    $empresas = $pdo->query("
        SELECT cve_cia, clave_empresa, des_cia
        FROM c_compania
        WHERE Activo = 1
        ORDER BY clave_empresa
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 ALMACENES (c_almacenp)
    $almacenes = $pdo->query("
        SELECT id, clave, nombre, cve_cia
        FROM c_almacenp
        ORDER BY clave
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 ZONAS (c_almacen)
    $zonas = $pdo->query("
        SELECT cve_almac, clave_almacen, cve_almacenp, des_almac
        FROM c_almacen
        ORDER BY clave_almacen
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
