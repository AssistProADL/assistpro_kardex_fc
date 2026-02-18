<?php
// public/api/filtros_assistpro.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error conexión DB: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'init';

try {
    if ($action === 'init') {
        init_filtros($pdo);
    } else {
        echo json_encode([
            'ok' => false,
            'error' => 'Acción no soportada: ' . $action
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function init_filtros(PDO $pdo): void
{
    $empresa = trim($_GET['empresa'] ?? $_POST['empresa'] ?? '');

    $data = [
        'ok' => true,
        'empresas' => [],
        'almacenes' => []
    ];

    /* ==============================
       EMPRESAS
    ============================== */
    $data['empresas'] = db_all("
        SELECT 
            cve_cia,
            clave_empresa,
            des_cia
        FROM c_compania
        WHERE IFNULL(Activo,1) = 1
        ORDER BY des_cia
    ");

    /* ==============================
       ALMACENES (DEPENDIENTES)
       IMPORTANTE: REGRESAR cve_cia
    ============================== */
    $params = [];
    $where = ["IFNULL(a.Activo,1) = 1"];

    if ($empresa !== '') {
        $where[] = "a.cve_cia = :empresa";
        $params['empresa'] = $empresa;
    }

    $sqlAlm = "
        SELECT 
            a.id       AS idp,
            a.clave    AS cve_almac,
            a.nombre,
            a.cve_cia
        FROM c_almacenp a
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.clave
    ";

    $data['almacenes'] = db_all($sqlAlm, $params);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
