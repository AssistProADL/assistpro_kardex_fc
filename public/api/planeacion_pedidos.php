<?php
/**
 * API: Planeación de Embarques - Pedidos
 * Usa:
 *  - th_pedido (cabecera)
 *  - td_pedido (detalle)
 */

require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json');

try {

    $pdo = db_pdo();

    /* ================== PARÁMETROS ================== */
    $idAlmacen = $_GET['almacen'] ?? null;

    if (!$idAlmacen) {
        echo json_encode([
            'success' => false,
            'error'   => 'Parámetro almacén requerido'
        ]);
        exit;
    }

    /* ================== QUERY ================== */
    $sql = "
        SELECT
            h.id_pedido,
            h.Fol_folio                    AS folio,
            h.Fec_Pedido                   AS fecha_pedido,
            h.Fec_Entrega                  AS fecha_entrega,
            h.rango_hora                   AS horario_planeado,
            h.ruta,
            h.Cve_clte                     AS cliente,
            h.destinatario,
            h.cve_almac                    AS almacen,

            COUNT(DISTINCT d.Cve_articulo) AS total_articulos,
            SUM(d.Num_cantidad)            AS total_piezas,
            SUM(d.Num_Empacados)           AS total_cajas,

            SUM(
                IFNULL(d.Valor_Comercial_MN,0)
            )                              AS valor_comercial,

            COUNT(DISTINCT d.id)           AS lineas

        FROM th_pedido h
        INNER JOIN td_pedido d
            ON d.Fol_folio = h.Fol_folio

        WHERE h.Activo = 1
          AND d.Activo = 1
          AND h.cve_almac = :almacen

        GROUP BY h.id_pedido
        ORDER BY h.Fec_Pedido DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':almacen', $idAlmacen);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ================== RESPUESTA ================== */
    echo json_encode([
        'success' => true,
        'total'   => count($data),
        'data'    => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error'   => 'Error al cargar pedidos de planeación',
        'msg'     => $e->getMessage()
    ]);
}
