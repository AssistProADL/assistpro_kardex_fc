<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

try {

    $empresa_id = $_GET['empresa_id'] ?? null;
    $debug = $_GET['debug'] ?? 0;

    $where = "WHERE u.Activo = 1";
    $params = [];

    if ($empresa_id) {
        $where .= " AND cia.cve_cia = :empresa_id";
        $params[':empresa_id'] = $empresa_id;
    }

    $sql = "
        SELECT
            cia.cve_cia             AS empresa_id,
            cia.des_cia             AS empresa,

            ap.id                   AS almacen_id,
            ap.clave                AS almacen_clave,
            ap.nombre               AS almacen_nombre,

            a.cve_almac             AS zona_id,
            a.clave_almacen         AS zona_clave,
            a.des_almac             AS zona_descripcion,

            u.idy_ubica             AS ubicacion_id,
            u.CodigoCSD             AS bl,
            u.cve_nivel,

            CASE 
                WHEN u.cve_nivel = 0 THEN 'PISO'
                ELSE 'RACK'
            END                     AS tipo_ubicacion,

            COUNT(ch.IDContenedor)  AS total_unidades,
            SUM(CASE WHEN ch.tipo = 'Pallet' THEN 1 ELSE 0 END) AS pallets,
            SUM(CASE WHEN ch.tipo <> 'Pallet' THEN 1 ELSE 0 END) AS contenedores

        FROM c_ubicacion u
        LEFT JOIN c_charolas ch
            ON ch.cve_almac = u.cve_almac
           AND ch.Activo = 1

        INNER JOIN c_almacen a
            ON a.cve_almac = u.cve_almac
        INNER JOIN c_almacenp ap
            ON ap.id = a.cve_almacenp
        INNER JOIN c_compania cia
            ON cia.cve_cia = ap.cve_cia

        $where

        GROUP BY
            cia.cve_cia, cia.des_cia,
            ap.id, ap.clave, ap.nombre,
            a.cve_almac, a.clave_almacen, a.des_almac,
            u.idy_ubica, u.CodigoCSD, u.cve_nivel

        ORDER BY
            ap.nombre,
            a.des_almac,
            u.CodigoCSD
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "total" => count($rows),
        "data" => $rows,
        "debug" => $debug ? ["sql" => $sql, "params" => $params] : null
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
