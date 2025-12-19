<?php
// public/api/api_manufactura.php
declare(strict_types=1);

// Limpieza de buffer
ob_start();
require_once __DIR__ . '/../../app/db.php';
if (ob_get_length()) ob_clean();

header('Content-Type: application/json; charset=utf-8');

session_start();
session_write_close();

function jexit(array $payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // === EL CHISMOSO ===
    // 1. ¿A qué base de datos estamos conectados?
    $dbInfo = db_all("SELECT DATABASE() as nombre_db");
    $nombreDB = $dbInfo[0]['nombre_db'] ?? 'DESCONOCIDA';

    // 2. ¿Cuántos registros tiene la tabla realmente?
    $conteoCrudo = db_all("SELECT COUNT(*) as total FROM t_ordenprod");
    $totalRealEnTabla = (int)($conteoCrudo[0]['total'] ?? 0);
    // ===================

    $method = $_SERVER['REQUEST_METHOD'];
    $req    = ($method === 'POST') ? $_POST : $_GET;

    // Paginación
    $start  = isset($req['start'])  ? (int)$req['start']  : 0;
    $length = isset($req['length']) ? (int)$req['length'] : 25;
    if ($length <= 0) $length = 25;

    // Helper de limpieza
    $cleanParam = function($val) {
        $v = trim((string)($val ?? ''));
        return ($v === 'null' || $v === 'undefined') ? '' : $v;
    };

    // Filtros
    $empresa     = $cleanParam($req['empresa']     ?? '');
    $almacen     = $cleanParam($req['almacen']     ?? '');
    $proveedorId = $cleanParam($req['Proveedor']   ?? $req['proveedor'] ?? '');
    $statusOT    = $cleanParam($req['statusOT']    ?? '');
    $criterio    = $cleanParam($req['criterio']    ?? '');
    $fechai      = $cleanParam($req['fechaInicio'] ?? '');
    $fechaf      = $cleanParam($req['fechaFin']    ?? '');

    $params = [];
    $where  = [];

    if ($empresa !== '') { $where[] = "al.cve_cia = :empresa"; $params['empresa'] = $empresa; }
    if ($almacen !== '') { $where[] = "op.cve_almac = :almacen"; $params['almacen'] = $almacen; }
    if ($proveedorId !== '') { $where[] = "op.ID_Proveedor = :prov"; $params['prov'] = $proveedorId; }
    if ($statusOT !== '') { $where[] = "op.Status = :st"; $params['st'] = $statusOT; }

    if ($criterio !== '') {
        $where[] = "(op.Folio_Pro LIKE :crit OR op.Cve_Articulo LIKE :crit OR op.Cve_Lote LIKE :crit)";
        $params['crit'] = "%$criterio%";
    }

    if ($fechai !== '') { $where[] = "DATE(op.Fecha) >= :fi"; $params['fi'] = $fechai; }
    if ($fechaf !== '') { $where[] = "DATE(op.Fecha) <= :ff"; $params['ff'] = $fechaf; }

    $joins = "
        FROM t_ordenprod op
        LEFT JOIN c_almacenp    al ON al.clave        = op.cve_almac
        LEFT JOIN c_articulo    a  ON a.cve_articulo  = op.Cve_Articulo
        LEFT JOIN c_proveedores pr ON pr.ID_Proveedor = op.ID_Proveedor
        LEFT JOIN th_pedido     tp ON tp.Ship_Num     = op.Folio_Pro
    ";

    $whereSql = count($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    // Total Filtrado
    $sqlCount = "SELECT COUNT(*) as total " . $joins . $whereSql;
    $rowsCnt  = db_all($sqlCount, $params);
    $recordsFiltered = (int)($rowsCnt[0]['total'] ?? 0);

    // Consulta de Datos
    $sqlData = "
        SELECT
            op.Folio_Pro                      AS folioOt,
            IFNULL(DATE_FORMAT(op.Fecha, '%Y-%m-%d'), '') AS fechaOt,
            IFNULL(DATE_FORMAT(op.Fecha, '%H:%i:%s'), '') AS horaOt,
            IFNULL(tp.Fol_folio, '')          AS pedidoFolio,
            op.Cve_Articulo                   AS articuloClave,
            IFNULL(a.des_articulo, 'N/D')     AS articuloDescripcion,
            IFNULL(op.Cve_Lote, '')           AS lote,
            ''                                AS caducidad,
            op.Cantidad                       AS cantidad,
            op.Cant_Prod                      AS cantidadProducida,
            op.Cve_Usuario                    AS usuario,
            IFNULL(tp.Fec_Entrega, '')        AS fechaCompromiso,
            op.Status                         AS status,
            IFNULL(op.Fecha, '')              AS fechaInicio,
            IFNULL(op.Hora_Ini, '')           AS horaInicio,
            IFNULL(op.Fecha, '')              AS fechaFin,
            IFNULL(op.Hora_Fin, '')           AS horaFin,
            IFNULL(pr.Nombre, 'Sin Proveedor')   AS proveedorNombre,
            op.Status                         AS statusOt,
            op.cve_almac                      AS almacenClave,
            op.id_zona_almac                  AS zonaAlmacen,
            op.Tipo                           AS tipoOt
        " . $joins . $whereSql . "
        ORDER BY op.Folio_Pro DESC
        LIMIT $start, $length
    ";

    $rows = db_all($sqlData, $params);

    jexit([
        'ok'              => true,
        'draw'            => isset($req['draw']) ? (int)$req['draw'] : 1,
        'recordsTotal'    => $totalRealEnTabla, // Usamos el total real para ver si la tabla tiene algo
        'recordsFiltered' => $recordsFiltered,
        'data'            => $rows,
        // ESTOS DOS DATOS SON LA CLAVE:
        'debug_db_conectada' => $nombreDB,
        'debug_total_real_tabla' => $totalRealEnTabla
    ]);

} catch (Throwable $e) {
    jexit(['ok' => false, 'error' => $e->getMessage()]);
}
?>