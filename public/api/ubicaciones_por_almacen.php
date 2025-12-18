<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json');

$almacenp_id = $_GET['almacenp_id'] ?? null;

if (!$almacenp_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT
        u.CodigoCSD      AS bl,
        u.cve_pasillo    AS pasillo,
        u.cve_rack       AS rack,
        u.cve_nivel      AS nivel,
        u.Seccion        AS seccion,
        u.Ubicacion      AS posicion
    FROM c_ubicacion u
    INNER JOIN c_almacen a
        ON a.cve_almac = u.cve_almac
    WHERE a.cve_almacenp = :almacenp_id
    ORDER BY u.CodigoCSD
";

$data = db_all($sql, [
    'almacenp_id' => $almacenp_id
]);

echo json_encode($data);
