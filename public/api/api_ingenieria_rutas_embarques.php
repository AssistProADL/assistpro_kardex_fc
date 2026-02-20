<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    /* ==========================================================
       EMPRESAS ACTIVAS
       ========================================================== */
    $sql_empresas = "
        SELECT 
            cve_cia,
            des_cia,
            clave_empresa
        FROM c_compania
        WHERE Activo = 1
        ORDER BY des_cia
    ";

    $empresas = function_exists('db_all')
        ? db_all($sql_empresas)
        : [];

    /* ==========================================================
       ALMACENES CON DEPENDENCIA REAL A EMPRESA
       ========================================================== */
    $sql_almacenes = "
        SELECT 
            ca.id                AS idp,
            cp.cve_cia           AS cve_cia,
            ca.cve_almac         AS cve_almac,
            ca.des_almac         AS nombre
        FROM c_almacen ca
        INNER JOIN c_almacenp cp 
            ON cp.id = ca.cve_almacenp
        WHERE ca.Activo = 1
        ORDER BY cp.cve_cia, ca.des_almac
    ";

    $almacenes = function_exists('db_all')
        ? db_all($sql_almacenes)
        : [];

    echo json_encode([
        'ok'        => true,
        'empresas'  => $empresas,
        'almacenes' => $almacenes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        'ok'      => false,
        'error'   => $e->getMessage()
    ]);
}
