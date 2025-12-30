<?php
header('Content-Type: application/json');
include_once '../config/db.php';

try {

    $sql = "
        SELECT
            u.idy_ubica,
            u.cve_almac,
            ap.nombre        AS almacen_principal,
            a.des_almac      AS zona,
            u.Seccion,
            u.cve_pasillo,
            u.cve_rack,
            u.cve_nivel,
            u.Ubicacion,
            u.CodigoCSD,
            u.picking,
            u.Status,
            u.Activo,
            u.AreaStagging,
            u.AcomodoMixto,
            u.Reabasto,
            u.Maximo,
            u.Minimo,
            u.PesoMaximo,
            u.PesoOcupado
        FROM c_ubicacion u
        INNER JOIN c_almacen a   ON a.cve_almac = u.cve_almac
        INNER JOIN c_almacenp ap ON ap.id = a.cve_almacenp
        WHERE 1=1
    ";

    $params = [];

    if (!empty($_GET['cve_almac'])) {
        $sql .= " AND u.cve_almac = :cve_almac";
        $params[':cve_almac'] = $_GET['cve_almac'];
    }

    if (!empty($_GET['seccion'])) {
        $sql .= " AND u.Seccion = :seccion";
        $params[':seccion'] = $_GET['seccion'];
    }

    if (!empty($_GET['pasillo'])) {
        $sql .= " AND u.cve_pasillo = :pasillo";
        $params[':pasillo'] = $_GET['pasillo'];
    }

    if (!empty($_GET['rack'])) {
        $sql .= " AND u.cve_rack = :rack";
        $params[':rack'] = $_GET['rack'];
    }

    if (!empty($_GET['nivel'])) {
        $sql .= " AND u.cve_nivel = :nivel";
        $params[':nivel'] = $_GET['nivel'];
    }

    if (!empty($_GET['picking'])) {
        $sql .= " AND u.picking = :picking";
        $params[':picking'] = $_GET['picking'];
    }

    if (!empty($_GET['activo'])) {
        $sql .= " AND u.Activo = :activo";
        $params[':activo'] = $_GET['activo'];
    }

    $sql .= " ORDER BY ap.nombre, a.des_almac, u.cve_pasillo, u.cve_rack, u.cve_nivel";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'ok'   => 1,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok'      => 0,
        'error'   => 'Error servidor',
        'detalle' => $e->getMessage()
    ]);
}
