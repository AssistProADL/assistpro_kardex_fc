<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json');

try {

    $sql = "
        SELECT DISTINCT
            ap.id,
            ap.nombre
        FROM c_almacenp ap
        INNER JOIN c_almacen a ON a.cve_almacenp = ap.id
        INNER JOIN t_ubicacionembarque ue ON ue.cve_almac = a.cve_almac AND ue.Activo = 1
        -- Se cambia a LEFT JOIN para no ocultar almacenes si falla la relación de pedidos
        LEFT JOIN rel_uembarquepedido r ON r.cve_ubicacion = ue.cve_ubicacion AND r.Activo = 1
        WHERE ap.Activo = 1
        -- Agrupamos para evitar duplicados múltiples por el LEFT JOIN
        GROUP BY ap.id, ap.nombre
        ORDER BY ap.nombre
    ";

    $data = db_all($sql);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error al cargar almacenes',
        'msg' => $e->getMessage()
    ]);
}
