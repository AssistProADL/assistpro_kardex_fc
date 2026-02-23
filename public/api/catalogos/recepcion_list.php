<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/db.php';


$pdo = db_pdo();

try {

    $empresa = $_GET['empresa'] ?? null;
    $almacen = $_GET['almacen'] ?? null;
    $estado  = $_GET['estado'] ?? 1;

    if (!$empresa || !$almacen) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT 
                id,
                cve_ubicacion,
                desc_ubicacion,
                Activo,
                B_Devolucion,
                AreaStagging
            FROM tubicacionesretencion
            WHERE cve_almacp = :almacen
              AND Activo = :estado
            ORDER BY cve_ubicacion";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':almacen' => $almacen,
        ':estado'  => $estado
    ]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode([]);
        exit;
    }

    echo json_encode($data);

} catch (Throwable $e) {

    echo json_encode([
        'error' => $e->getMessage()
    ]);
}