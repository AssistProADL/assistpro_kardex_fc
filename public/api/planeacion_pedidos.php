<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json');

try {

    $pdo = db_pdo();

    /*
     * =================================================
     *  PLANEACIÓN BASE (SIN FILTRO DE ALMACÉN)
     *  th_pedido + td_pedido
     * =================================================
     */
    $sql = "
        SELECT
            h.Fol_folio                 AS folio,
            h.Fec_Pedido                AS fecha_pedido,
            h.Fec_Entrega               AS fecha_entrega,
            h.status                    AS status,
            h.ruta                      AS ruta,
            h.Cve_clte                  AS cliente,
            h.destinatario              AS destinatario,

            COUNT(DISTINCT d.id)        AS total_cajas,
            COALESCE(SUM(d.Num_cantidad),0) AS total_piezas,
            COALESCE(SUM(d.Num_cantidad),0) AS valor_comercial

        FROM th_pedido h

        LEFT JOIN td_pedido d
            ON d.Fol_folio = h.Fol_folio
           AND d.Activo = 1

        WHERE h.Activo = 1

        GROUP BY h.Fol_folio
        ORDER BY h.Fec_Entrega DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total'   => count($data),
        'data'    => $data
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'error'   => 'Error al cargar planeación',
        'msg'     => $e->getMessage()
    ]);
}
