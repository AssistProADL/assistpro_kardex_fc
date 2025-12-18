<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();

    // Parámetros
    $empresa  = isset($_GET['empresa']) ? (int)$_GET['empresa'] : 0;
    $zona     = $_GET['zona'] ?? '';
    $bl       = $_GET['bl'] ?? '';
    $producto = $_GET['producto'] ?? '';
    $lp       = $_GET['lp'] ?? '';
    $limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;

    if ($limit > 500) {
        $limit = 500;
    }

    $sql = "
        SELECT
            empresa_nombre,
            des_almac,
            zona_almacen,
            bl,
            cve_articulo,
            des_articulo,
            cve_lote,
            CveLP,
            existencia,
            disponible,
            es_qa
        FROM v_existencias_por_ubicacion_ao
        WHERE 1=1
    ";

    $params = [];

    // ✅ EMPRESA REAL (c_compania)
    if ($empresa > 0) {
        $sql .= " AND empresa_id = ? ";
        $params[] = $empresa;
    }

    // Zona
    if ($zona !== '') {
        $sql .= " AND cve_almac = ? ";
        $params[] = $zona;
    }

    // BL
    if ($bl !== '') {
        $sql .= " AND bl = ? ";
        $params[] = $bl;
    }

    // Producto
    if ($producto !== '') {
        $sql .= " AND cve_articulo = ? ";
        $params[] = $producto;
    }

    // LP
    if ($lp !== '') {
        $sql .= " AND CveLP = ? ";
        $params[] = $lp;
    }

    $sql .= " ORDER BY bl, cve_articulo LIMIT $limit ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'rows'    => count($rows),
        'data'    => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
