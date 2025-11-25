<?php
// public/pedidos/pedido_detalle_api.php
// API para devolver encabezado + detalle de un pedido en JSON (para el modal)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No existe la conexión PDO disponible: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_pedido = isset($_GET['id_pedido']) ? (int)$_GET['id_pedido'] : 0;
$folio     = $_GET['folio'] ?? null;

if ($id_pedido <= 0 && !$folio) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Parámetros insuficientes. Enviar id_pedido o folio.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ================== ENCABEZADO ==================
    if ($folio) {
        $pedido = db_one("
            SELECT
                h.id_pedido,
                h.Fol_folio,
                h.Fec_Pedido,
                h.Cve_clte,
                h.status,
                h.Fec_Entrega,
                h.cve_Vendedor,
                h.fuente_id,
                h.fuente_detalle,
                h.Observaciones,
                h.ID_Tipoprioridad,
                h.TipoPedido,
                h.ruta,
                h.cve_almac,
                a.clave AS clave_almacen,
                h.Cve_Usuario
            FROM th_pedido h
            LEFT JOIN c_almacenp a 
                ON (a.clave = h.cve_almac OR a.id = h.cve_almac)
            WHERE h.Fol_folio = ?
            ORDER BY h.id_pedido DESC
            LIMIT 1
        ", [$folio]);
    } else {
        $pedido = db_one("
            SELECT
                h.id_pedido,
                h.Fol_folio,
                h.Fec_Pedido,
                h.Cve_clte,
                h.status,
                h.Fec_Entrega,
                h.cve_Vendedor,
                h.fuente_id,
                h.fuente_detalle,
                h.Observaciones,
                h.ID_Tipoprioridad,
                h.TipoPedido,
                h.ruta,
                h.cve_almac,
                a.clave AS clave_almacen,
                h.Cve_Usuario
            FROM th_pedido h
            LEFT JOIN c_almacenp a 
                ON (a.clave = h.cve_almac OR a.id = h.cve_almac)
            WHERE h.id_pedido = ?
            LIMIT 1
        ", [$id_pedido]);
    }

    if (!$pedido) {
        echo json_encode([
            'ok'    => false,
            'error' => 'No se encontró el pedido solicitado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $folioReal = $pedido['Fol_folio'];

    // ================== DETALLE ==================
    $detalle = db_all("
        SELECT
            d.id,
            d.Fol_folio,
            d.Cve_articulo,
            d.Num_cantidad,
            d.id_unimed,
            d.SurtidoXCajas,
            d.SurtidoXPiezas,
            d.Num_revisadas,
            d.Num_Empacados,
            d.cve_lote,
            d.status,
            d.itemPos
        FROM td_pedido d
        WHERE d.Fol_folio = ?
        ORDER BY d.itemPos, d.id
        LIMIT 500
    ", [$folioReal]);

    echo json_encode([
        'ok'      => true,
        'pedido'  => $pedido,
        'detalle' => $detalle
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Error general al obtener el detalle del pedido: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
