<?php
// public/procesos/Patios/patios_vincular_oc.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException("Método no permitido");
    }

    $usuario  = $_SESSION['username'] ?? 'SISTEMA';
    $id_visita = isset($_POST['id_visita']) ? (int)$_POST['id_visita'] : 0;
    $oc_ids_raw = $_POST['oc_ids'] ?? '';

    if ($id_visita <= 0) {
        throw new RuntimeException("id_visita inválido");
    }

    if ($oc_ids_raw === '') {
        throw new RuntimeException("Debe indicar al menos una OC");
    }

    // oc_ids puede venir como "1,2,3"
    $oc_ids = array_filter(array_map('intval', explode(',', $oc_ids_raw)));

    if (empty($oc_ids)) {
        throw new RuntimeException("Lista de OC inválida");
    }

    db_tx(function () use ($id_visita, $oc_ids, $usuario) {

        // TODO: Ajustar nombres de tablas / campos de OC
        $sqlOc = "
            SELECT
              oc.id              AS oc_id,
              oc.folio           AS folio_oc,
              oc.proveedor_id,
              oc.monto_total
            FROM th_oc oc
            WHERE oc.id = :oc_id
        ";

        foreach ($oc_ids as $oc_id) {
            $oc = db_one($sqlOc, [':oc_id' => $oc_id]);
            if (!$oc) {
                continue;
            }

            dbq("
                INSERT INTO t_patio_doclink (
                  id_visita, sistema_origen, tipo_doc,
                  tabla_origen, id_origen, folio_origen,
                  proveedor_id, monto_total,
                  estado_sync, usuario_vincula, fecha_vincula
                ) VALUES (
                  :id_visita, 'ER', 'OC',
                  'th_oc', :id_origen, :folio_origen,
                  :proveedor_id, :monto_total,
                  'PENDIENTE', :usuario, NOW()
                )
                ON DUPLICATE KEY UPDATE
                  folio_origen   = VALUES(folio_origen),
                  proveedor_id   = VALUES(proveedor_id),
                  monto_total    = VALUES(monto_total),
                  usuario_vincula= VALUES(usuario_vincula),
                  fecha_vincula  = VALUES(fecha_vincula)
            ", [
                ':id_visita'    => $id_visita,
                ':id_origen'    => $oc['oc_id'],
                ':folio_origen' => $oc['folio_oc'],
                ':proveedor_id' => $oc['proveedor_id'],
                ':monto_total'  => $oc['monto_total'],
                ':usuario'      => $usuario
            ]);
        }
    });

    echo json_encode([
        'ok'   => true,
        'msg'  => 'OCs vinculadas correctamente'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
