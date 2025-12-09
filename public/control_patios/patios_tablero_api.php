<?php
// public/control_patios/patios_tablero_api.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // empresa_id = cve_cia, almacenp_id = c_almacenp.id
    $empresa_id  = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : 0;
    $almacenp_id = isset($_GET['almacenp_id']) ? trim((string)$_GET['almacenp_id']) : '';

    $sql = "SELECT vpt.*
            FROM v_patio_tablero vpt
            WHERE 1=1";
    $params = [];

    if ($empresa_id > 0) {
        $sql .= " AND EXISTS (
                    SELECT 1
                    FROM t_patio_visita vv
                    WHERE vv.id_visita = vpt.id_visita
                      AND vv.empresa_id = :empresa_id
                  )";
        $params[':empresa_id'] = $empresa_id;
    }

    if ($almacenp_id !== '') {
        $sql .= " AND EXISTS (
                    SELECT 1
                    FROM t_patio_visita vv2
                    WHERE vv2.id_visita = vpt.id_visita
                      AND vv2.almacenp_id = :almacenp_id
                  )";
        $params[':almacenp_id'] = $almacenp_id;
    }

    $rows = db_all($sql, $params);

    echo json_encode([
        'ok'   => true,
        'data' => $rows
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
