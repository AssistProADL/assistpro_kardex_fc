<?php
// public/procesos/Patios/patios_oc_pendientes.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $almacenp_id = isset($_GET['almacenp_id']) ? trim((string)$_GET['almacenp_id']) : '';
    $proveedor_id = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;

    if ($almacenp_id === '') {
        throw new RuntimeException("Falta almacenp_id");
    }

    // TODO: AJUSTAR ESTA CONSULTA A TU MODELO REAL DE OC
    $sql = "
        SELECT
          oc.id              AS oc_id,
          oc.folio           AS folio_oc,
          oc.proveedor_id,
          DATE(oc.fecha)     AS fecha_oc,
          SUM(det.cantidad)  AS cant_oc,
          SUM(det.recibida)  AS cant_recibida,
          (SUM(det.cantidad) - SUM(det.recibida)) AS cant_pendiente
        FROM th_oc oc
        JOIN td_oc det ON det.oc_id = oc.id
        WHERE oc.almacenp_id = :almacenp_id
          AND oc.estatus IN ('ABIERTA','PARCIAL')
    ";
    $params = [':almacenp_id' => $almacenp_id];

    if ($proveedor_id > 0) {
        $sql .= " AND oc.proveedor_id = :proveedor_id";
        $params[':proveedor_id'] = $proveedor_id;
    }

    $sql .= "
        GROUP BY oc.id, oc.folio, oc.proveedor_id, oc.fecha
        HAVING cant_pendiente > 0
        ORDER BY oc.fecha DESC, oc.folio
    ";

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
