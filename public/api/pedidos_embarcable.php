<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json');

try {

    $pdo = db_pdo();

    /* ================== PARÁMETROS ================== */
    $almacen = $_GET['almacen'] ?? null;
    $almacenp_id = $_GET['almacenp_id'] ?? null; // c_almacenp.id
    $ubicacion = $_GET['ubicacion'] ?? null; // área de embarque
    $ruta = $_GET['ruta'] ?? null;
    $cliente = $_GET['cliente'] ?? null;

    // Usar almacenp_id directamente (th_pedido.cve_almac = c_almacenp.id)
    if ($almacenp_id && !$almacen) {
        $almacen = $almacenp_id;
    }

    if (!$almacen || !$ubicacion) {
        throw new Exception('Parámetros almacén y área de embarque requeridos');
    }

    /* ================== SQL BASE ================== */
    $sql = "
        SELECT
            p.Fol_Folio,
            sp.Sufijo,
            p.cve_almac,
            p.Cve_clte,
            p.ruta,
            ue.cve_ubicacion,
            ue.AreaStagging,

            /* Fechas */
            p.Fec_Pedido AS fecha_pedido,
            p.Fec_Entrega AS fecha_entrega,

            /* Datos de Cliente/Destino */
            cl.RazonSocial AS cliente_nombre,
            cl.CalleNumero AS direccion,
            cl.Colonia AS colonia,
            cl.CodigoPostal AS cp,
            cl.latitud AS latitud,
            cl.longitud AS longitud,

            /* Totales desde detalle */
            SUM(d.Num_cantidad)                         AS total_piezas,
            SUM(d.Valor_Comercial_MN)                   AS valor_comercial,
            /* Asumiendo que existen pesos y volumen en detalle o cabecera, si no, poner 0 por ahora */
            0 AS peso_total, 
            0 AS volumen_total,
            0 AS total_cajas,

            p.status AS status_pedido,
            sp.status AS status_subpedido

        FROM th_pedido p

        /* Cliente */
        LEFT JOIN c_cliente cl ON cl.Cve_Clte = p.Cve_clte

        /* Subpedidos */
        INNER JOIN th_subpedido sp
            ON sp.Fol_Folio = p.Fol_Folio
           AND sp.cve_almac = p.cve_almac

        /* Relación ubicación de embarque */
        INNER JOIN rel_uembarquepedido re
            ON re.Fol_Folio = p.Fol_Folio
           AND re.Sufijo    = sp.Sufijo
           AND re.Activo   = 1

        /* Ubicaciones de embarque */
        INNER JOIN t_ubicacionembarque ue
            ON ue.cve_ubicacion COLLATE utf8mb4_unicode_ci = re.cve_ubicacion COLLATE utf8mb4_unicode_ci
           AND ue.Activo        = 1

        /* Detalle del pedido */
        INNER JOIN td_pedido d
            ON d.Fol_Folio = p.Fol_Folio
           AND d.Activo    = 1

        WHERE
            p.Activo = 1
            AND p.status = 'C'
            AND sp.status = 'C'
            AND p.cve_almac = :almacen
            AND ue.cve_ubicacion = :ubicacion
    ";

    /* ================== FILTROS OPCIONALES ================== */
    $params = [
        ':almacen' => $almacen,
        ':ubicacion' => $ubicacion
    ];

    if ($ruta) {
        $sql .= " AND p.ruta = :ruta ";
        $params[':ruta'] = $ruta;
    }

    if ($cliente) {
        $sql .= " AND p.Cve_clte = :cliente ";
        $params[':cliente'] = $cliente;
    }

    /* ================== VALIDACIÓN DE SUBPEDIDOS ================== */
    $sql .= "
        AND NOT EXISTS (
            SELECT 1
            FROM th_subpedido sp2
            WHERE sp2.Fol_Folio = p.Fol_Folio
              AND sp2.cve_almac = p.cve_almac
              AND sp2.status <> 'C'
        )
    ";

    $sql .= "
        GROUP BY
            p.Fol_Folio,
            sp.Sufijo,
            ue.cve_ubicacion,
            ue.AreaStagging,
            p.cve_almac,
            p.Cve_clte,
            p.ruta,
            p.status,
            sp.status
        ORDER BY
            p.Fol_Folio,
            sp.Sufijo
    ";

    /* ================== EJECUCIÓN ================== */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total' => count($data),
        'data' => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
