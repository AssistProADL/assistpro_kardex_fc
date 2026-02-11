<?php
// /public/api/recepcion/recepcion_oc_api.php
// Lista OCs OCN para Recepción Mobile
// - Filtros: almacen, proveedor
// - Búsqueda: q (alfanumérica) contra num_pedimento/Factura/ID_Aduana/Consec_protocolo/Proveedor
// - FIX PDO: NO reusar mismo placeholder (:q) en múltiples condiciones (provoca HY093)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

// Detecta columna vigente para folio (migración num_pedimento -> folio_mov)
function first_col($table, $cands) {
    foreach ($cands as $c) {
        $x = db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $c]);
        if ((int)$x > 0) return $c;
    }
    return null;
}

function out($arr, $code = 200){
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Asegura conexión PDO (según tu db.php)
    if (function_exists('db_pdo')) {
        db_pdo();
    }

    $almacen   = isset($_GET['almacen']) ? trim((string)$_GET['almacen']) : '';
    $proveedor = isset($_GET['proveedor']) ? trim((string)$_GET['proveedor']) : '';
    $q         = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

    // Hard limits defensivos
    if (strlen($almacen) > 20)   $almacen = substr($almacen, 0, 20);
    if (strlen($proveedor) > 20) $proveedor = substr($proveedor, 0, 20);
    if (strlen($q) > 60)         $q = substr($q, 0, 60);

    $where  = [];
    $params = [];

    // Base: OCN
    $where[] = "h.Activo = 1";
    $where[] = "h.ID_Protocolo = 'OCN'";

    // Columna folio vigente en th_aduana
    $colFolio = first_col('th_aduana', ['folio_mov','num_pedimento']) ?: 'num_pedimento';

    // Almacén (tu data usa WH8 alfanumérico; respetamos tal cual)
    if ($almacen !== '') {
        $where[] = "h.Cve_Almac = :alm";
        $params['alm'] = $almacen;
    }

    // Proveedor
    if ($proveedor !== '') {
        $where[] = "h.ID_Proveedor = :prov";
        $params['prov'] = $proveedor;
    }

    // q alfanumérico (NO asumimos numérico)
    // FIX: placeholders únicos (q1..q5) para evitar HY093 en PDO
    if ($q !== '') {
        $where[] = "("
            . "CAST(h.$colFolio AS CHAR) LIKE :q1 "
            . "OR h.Factura LIKE :q2 "
            . "OR CAST(h.ID_Aduana AS CHAR) LIKE :q3 "
            . "OR CAST(h.Consec_protocolo AS CHAR) LIKE :q4 "
            . "OR p.Nombre LIKE :q5"
            . ")";

        $like = "%{$q}%";
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
        $params['q5'] = $like;
    }

    // Importante: la UI legacy espera num_pedimento para pintar el combo.
    // Con el cambio a folio_mov, devolvemos alias compatibles SIN tocar diseño.
    $sql = "
        SELECT
          h.*,
          CAST(h.$colFolio AS CHAR) AS num_pedimento,
          CAST(h.$colFolio AS CHAR) AS folio_oc,
          CAST(h.$colFolio AS CHAR) AS folio_mov
        FROM th_aduana h
        LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
        WHERE " . implode(" AND ", $where) . "
        ORDER BY h.ID_Aduana DESC
        LIMIT 500
    ";

    // db_all viene de tu stack (según tus otros módulos)
    $rows = db_all($sql, $params);

    out([
        'ok'    => 1,
        'data'  => $rows,
        'total' => is_array($rows) ? count($rows) : 0,
    ]);

} catch (Throwable $e) {
    out([
        'ok'      => 0,
        'error'   => 'Error servidor',
        'detalle' => $e->getMessage(),
    ], 500);
}
