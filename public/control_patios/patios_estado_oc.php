<?php
// public/procesos/Patios/patios_estado_oc.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $id_visita = isset($_GET['id_visita']) ? (int)$_GET['id_visita'] : 0;
    if ($id_visita <= 0) {
        throw new RuntimeException("id_visita inválido");
    }

    $links = db_all("
        SELECT
          dl.id_doclink,
          dl.tipo_doc,
          dl.tabla_origen,
          dl.id_origen,
          dl.folio_origen,
          dl.proveedor_id,
          dl.monto_total,
          dl.estado_sync
        FROM t_patio_doclink dl
        WHERE dl.id_visita = :id_visita
    ", [':id_visita' => $id_visita]);

    // TODO: Si quieres, aquí puedes recalcular pendiente de cada OC
    // leyendo tus tablas reales de OC y añadir campos: cant_pendiente, porcentaje, etc.

    echo json_encode([
        'ok'   => true,
        'data' => $links
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
