<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

/* =========================
   Parámetros
   ========================= */
$almacenp_id = isset($_GET['almacenp_id']) ? intval($_GET['almacenp_id']) : 0;

error_log("=== API rutas.php ===");
error_log("Parámetro almacenp_id: " . $almacenp_id);

try {
    /* =========================
       Consulta de rutas
       ========================= */
    $sql = "
        SELECT
            ID_Ruta,
            cve_ruta,
            descripcion,
            status,
            cve_almacenp,
            venta_preventa,
            control_pallets_cont
        FROM t_ruta
        WHERE Activo = 1
          AND status = 'A'
    ";

    $params = [];

    // Si se especifica almacén, filtrar por él
    if ($almacenp_id > 0) {
        $sql .= " AND cve_almacenp = :almacenp_id";
        $params['almacenp_id'] = $almacenp_id;
        error_log("Filtrando por almacén: " . $almacenp_id);
    } else {
        error_log("Sin filtro de almacén - mostrando todas las rutas activas");
    }

    $sql .= " ORDER BY cve_ruta";

    error_log("SQL: " . $sql);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Rutas encontradas: " . count($rutas));

    // Devolver formato estándar (consistente con otras APIs)
    echo json_encode([
        'success' => true,
        'total' => count($rutas),
        'data' => $rutas
    ]);

} catch (Exception $e) {
    error_log("Error en rutas.php: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
