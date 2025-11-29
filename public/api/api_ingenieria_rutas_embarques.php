<?php
// public/api/ingenieria_rutas_embarques.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No existe la conexión PDO disponible ($pdo) en db.php: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'init';

try {
    switch ($action) {
        case 'init':
            init_datos($pdo);
            break;

        case 'listar':
            listar_embarques($pdo);
            break;

        case 'detalle':
            detalle_pedido($pdo);
            break;

        case 'asignar_transporte':
            asignar_transporte($pdo);
            break;

        case 'actualizar_status':
            actualizar_status($pdo);
            break;

        default:
            echo json_encode([
                'ok'    => false,
                'error' => 'Acción no soportada en ingenieria_rutas_embarques.php: ' . $action
            ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Error general en ingenieria_rutas_embarques.php: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Carga inicial de combos: almacenes, rutas, transportes, status.
 */
function init_datos(PDO $pdo): void
{
    // Almacenes principales (c_almacenp)
    // Usamos id como clave de almacén (en tu estructura real NO existe empresa_id)
    $sqlAlm = "
        SELECT 
            id,
            clave,
            nombre,
            IFNULL(Activo,'1') AS Activo
        FROM c_almacenp
        WHERE IFNULL(Activo,'1') IN ('1', 1)
        ORDER BY nombre
    ";
    $almacenes = $pdo->query($sqlAlm)->fetchAll(PDO::FETCH_ASSOC);

    // Rutas (t_ruta)
    $sqlRuta = "
        SELECT
            ID_Ruta,
            cve_ruta,
            descripcion,
            cve_almacenp,
            status,
            Activo
        FROM t_ruta
        WHERE IFNULL(Activo,1) = 1
        ORDER BY descripcion
    ";
    $rutas = $pdo->query($sqlRuta)->fetchAll(PDO::FETCH_ASSOC);

    // Transportes (t_transporte)
    $sqlTrans = "
        SELECT
            id,
            ID_Transporte,
            Nombre,
            Placas,
            tipo_transporte,
            num_ec,
            transporte_externo,
            IFNULL(Activo,1) AS Activo
        FROM t_transporte
        WHERE IFNULL(Activo,1) = 1
        ORDER BY Nombre
    ";
    $transportes = $pdo->query($sqlTrans)->fetchAll(PDO::FETCH_ASSOC);

    // Catálogo estático de status de embarque
    $status = [
        ['id' => '',  'texto' => 'Todos'],
        ['id' => 'T', 'texto' => 'En Ruta (T)'],
        ['id' => 'F', 'texto' => 'Entregado (F)'],
    ];

    echo json_encode([
        'ok'           => true,
        'almacenes'    => $almacenes,
        'rutas'        => $rutas,
        'transportes'  => $transportes,
        'status_list'  => $status,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Lista embarques / pedidos para la grilla principal.
 * Filtra por: almacén, ruta, fechas, status, búsqueda libre.
 */
function listar_embarques(PDO $pdo): void
{
    // Parámetros de filtro
    $almacen   = trim($_POST['almacen'] ?? $_GET['almacen'] ?? '');
    $ruta      = trim($_POST['ruta'] ?? $_GET['ruta'] ?? '');
    $status    = trim($_POST['status'] ?? $_GET['status'] ?? '');
    $fechaIni  = trim($_POST['fecha_ini'] ?? $_GET['fecha_ini'] ?? '');
    $fechaFin  = trim($_POST['fecha_fin'] ?? $_GET['fecha_fin'] ?? '');
    $busqueda  = trim($_POST['buscar'] ?? $_GET['buscar'] ?? '');

    $params = [];
    $filtros = [];

    // Filtro por fechas sobre fecha de embarque (th_ordenembarque.fecha)
    if ($fechaIni !== '' && $fechaFin !== '') {
        $filtros[] = "oe.fecha BETWEEN :fini AND :ffin";
        $params[':fini'] = $fechaIni . ' 00:00:00';
        $params[':ffin'] = $fechaFin . ' 23:59:59';
    } elseif ($fechaIni !== '') {
        $filtros[] = "oe.fecha >= :fini";
        $params[':fini'] = $fechaIni . ' 00:00:00';
    } elseif ($fechaFin !== '') {
        $filtros[] = "oe.fecha <= :ffin";
        $params[':ffin'] = $fechaFin . ' 23:59:59';
    }

    // Filtro por status de embarque
    if ($status !== '') {
        $filtros[] = "oe.status = :status";
        $params[':status'] = $status;
    }

    // Filtro por almacén: ahora usamos c_almacenp.id
    // th_pedido.cve_almac -> c_almacen.clave_almacen
    // c_almacen.cve_almacenp -> c_almacenp.id
    if ($almacen !== '') {
        $filtros[] = "ap.id = :almacen";
        $params[':almacen'] = $almacen;
    }

    // Filtro por ruta
    if ($ruta !== '') {
        $filtros[] = "oe.Id_Ruta = :idruta";
        $params[':idruta'] = (int)$ruta;
    }

    // Búsqueda libre por folio o cliente
    if ($busqueda !== '') {
        $filtros[] = "(p.Fol_folio LIKE :q OR cli.RazonSocial LIKE :q)";
        $params[':q'] = '%' . $busqueda . '%';
    }

    $where = '';
    if ($filtros) {
        $where = 'WHERE ' . implode(' AND ', $filtros);
    }

    // Consulta principal
    $sql = "
        SELECT
            oe.ID_OEmbarque,
            oe.Cve_Almac,
            oe.Id_Ruta,
            r.cve_ruta,
            r.descripcion          AS ruta_desc,
            oe.ID_Transporte,
            tr.Nombre              AS transporte_nombre,
            tr.Placas              AS transporte_placas,
            oe.fecha               AS fecha_embarque,
            oe.FechaEnvio,
            oe.status              AS status_embarque,
            oe.Num_Guia,
            oe.Tipo_Entrega,
            oe.embarcador,
            oe.chofer,
            p.Fol_folio,
            p.Fec_Pedido,
            p.Fec_Entrega,
            p.rango_hora,
            p.TipoPedido,
            p.TipoDoc,
            p.cve_almac           AS cve_almac_pedido,
            cli.id_cliente,
            cli.Cve_Clte,
            cli.RazonSocial,
            cli.CalleNumero,
            cli.Colonia,
            cli.CodigoPostal,
            cli.Ciudad,
            cli.Estado,
            cli.latitud,
            cli.longitud,
            SUM(IFNULL(dp.Num_cantidad,0)) AS total_piezas
        FROM th_ordenembarque oe
        INNER JOIN td_ordenembarque de
            ON de.ID_OEmbarque = oe.ID_OEmbarque
        INNER JOIN th_pedido p
            ON p.Fol_folio = de.Fol_folio
        LEFT JOIN td_pedido dp
            ON dp.Fol_folio = p.Fol_folio
        LEFT JOIN c_cliente cli
            ON cli.Cve_Clte = p.Cve_clte
        LEFT JOIN t_ruta r
            ON r.ID_Ruta = oe.Id_Ruta
        LEFT JOIN c_almacen ca
            ON ca.clave_almacen = p.cve_almac
        LEFT JOIN c_almacenp ap
            ON ap.id = ca.cve_almacenp
        LEFT JOIN t_transporte tr
            ON tr.ID_Transporte = oe.ID_Transporte
        $where
        GROUP BY
            oe.ID_OEmbarque,
            de.Fol_folio
        ORDER BY
            oe.fecha DESC,
            oe.ID_OEmbarque DESC,
            p.Fol_folio
        LIMIT 0, 500
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok'   => true,
        'rows' => $rows,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Detalle de un pedido dentro de un embarque.
 * Parámetros: id_oembarque, folio
 */
function detalle_pedido(PDO $pdo): void
{
    $idOe = (int)($_POST['id_oembarque'] ?? $_GET['id_oembarque'] ?? 0);
    $folio = trim($_POST['folio'] ?? $_GET['folio'] ?? '');

    if ($idOe <= 0 || $folio === '') {
        echo json_encode([
            'ok'    => false,
            'error' => 'Parámetros insuficientes para detalle (id_oembarque / folio).'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $sqlHead = "
        SELECT
            oe.ID_OEmbarque,
            oe.Cve_Almac,
            oe.Id_Ruta,
            r.cve_ruta,
            r.descripcion       AS ruta_desc,
            oe.ID_Transporte,
            tr.Nombre           AS transporte_nombre,
            tr.Placas           AS transporte_placas,
            oe.fecha            AS fecha_embarque,
            oe.FechaEnvio,
            oe.status           AS status_embarque,
            oe.Num_Guia,
            oe.Tipo_Entrega,
            oe.embarcador,
            oe.chofer,
            oe.comentarios,
            p.Fol_folio,
            p.Fec_Pedido,
            p.Fec_Entrega,
            p.rango_hora,
            p.TipoPedido,
            p.TipoDoc,
            p.cve_almac        AS cve_almac_pedido,
            cli.id_cliente,
            cli.Cve_Clte,
            cli.RazonSocial,
            cli.CalleNumero,
            cli.Colonia,
            cli.CodigoPostal,
            cli.Ciudad,
            cli.Estado,
            cli.latitud,
            cli.longitud
        FROM th_ordenembarque oe
        INNER JOIN td_ordenembarque de
            ON de.ID_OEmbarque = oe.ID_OEmbarque
        INNER JOIN th_pedido p
            ON p.Fol_folio = de.Fol_folio
        LEFT JOIN c_cliente cli
            ON cli.Cve_Clte = p.Cve_clte
        LEFT JOIN t_ruta r
            ON r.ID_Ruta = oe.Id_Ruta
        LEFT JOIN t_transporte tr
            ON tr.ID_Transporte = oe.ID_Transporte
        WHERE
            oe.ID_OEmbarque = :id_oe
            AND p.Fol_folio = :folio
        LIMIT 1
    ";
    $stHead = $pdo->prepare($sqlHead);
    $stHead->execute([
        ':id_oe' => $idOe,
        ':folio' => $folio,
    ]);
    $header = $stHead->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        echo json_encode([
            'ok'    => false,
            'error' => 'No se encontró información para el embarque/pedido solicitado.'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $sqlDet = "
        SELECT
            d.Cve_articulo,
            d.Num_cantidad,
            d.SurtidoXCajas,
            d.SurtidoXPiezas,
            d.Precio_unitario,
            d.Desc_Importe,
            d.IVA,
            d.Num_Empacados,
            d.cve_lote
        FROM td_pedido d
        WHERE d.Fol_folio = :folio
        ORDER BY d.itemPos
    ";
    $stDet = $pdo->prepare($sqlDet);
    $stDet->execute([':folio' => $folio]);
    $detalles = $stDet->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok'      => true,
        'header'  => $header,
        'detalle' => $detalles,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Asigna / actualiza transporte del embarque.
 * Parámetros: id_oembarque, id_transporte (ID_Transporte, no el id autoincremental)
 */
function asignar_transporte(PDO $pdo): void
{
    $idOe = (int)($_POST['id_oembarque'] ?? 0);
    $idTransporte = trim($_POST['id_transporte'] ?? '');

    if ($idOe <= 0 || $idTransporte === '') {
        echo json_encode([
            'ok'    => false,
            'error' => 'Parámetros insuficientes para asignar transporte.'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $sql = "
        UPDATE th_ordenembarque
        SET ID_Transporte = :id_transporte
        WHERE ID_OEmbarque = :id_oe
        LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':id_transporte' => $idTransporte,
        ':id_oe'         => $idOe,
    ]);

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Transporte actualizado correctamente.'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Actualiza el status del embarque (T = En Ruta, F = Entregado).
 * Parámetros: id_oembarque, status
 */
function actualizar_status(PDO $pdo): void
{
    $idOe = (int)($_POST['id_oembarque'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($idOe <= 0 || !in_array($status, ['T', 'F'], true)) {
        echo json_encode([
            'ok'    => false,
            'error' => 'Parámetros inválidos para actualizar status (use T o F).'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $sql = "
        UPDATE th_ordenembarque
        SET status = :status
        WHERE ID_OEmbarque = :id_oe
        LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':status' => $status,
        ':id_oe'  => $idOe,
    ]);

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Status de embarque actualizado correctamente.'
    ], JSON_UNESCAPED_UNICODE);
}
