<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json');

try {

    $idInv = (int)($_POST['id_inventario'] ?? 0);
    if ($idInv <= 0) {
        throw new Exception('Inventario inválido');
    }

    db_begin();

    /* =========================
     * Validar inventario
     * ========================= */
    $inv = db_row("
        SELECT ID_Inventario, Status, Inv_Inicial
        FROM th_inventario
        WHERE ID_Inventario = ?
        FOR UPDATE
    ", [$idInv]);

    if (!$inv || $inv['Status'] !== 'PLANEADO') {
        throw new Exception('Inventario no listo para snapshot');
    }

    /* =========================
     * BL confirmados
     * ========================= */
    $bls = db_all("
        SELECT codigocsd
        FROM t_ubicacionesainventariar
        WHERE ID_Inventario = ?
    ", [$idInv]);

    if (!$bls) {
        throw new Exception('No hay BL asignados');
    }

    /* =========================
     * SNAPSHOT PIEZAS
     * ========================= */
    foreach ($bls as $b) {

        // Piezas con existencia
        dbq("
            INSERT INTO t_invpiezas
            (ID_Inventario, codigocsd, existencia_teorica)
            SELECT ?, codigocsd, Existencia
            FROM ts_existenciapiezas
            WHERE codigocsd = ?
        ", [$idInv, $b['codigocsd']]);

        // BL sin existencia → 0
        dbq("
            INSERT INTO t_invpiezas
            (ID_Inventario, codigocsd, existencia_teorica)
            SELECT ?, ?, 0
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM t_invpiezas
                WHERE ID_Inventario = ?
                  AND codigocsd = ?
            )
        ", [$idInv, $b['codigocsd'], $idInv, $b['codigocsd']]);
    }

    /* =========================
     * SNAPSHOT CONTENEDORES / PALLETS
     * ========================= */
    dbq("
        INSERT INTO t_invtarima
        (ID_Inventario, id_charola, existencia_teorica)
        SELECT ?, c.id_charola, IFNULL(e.existencia,0)
        FROM c_charolas c
        LEFT JOIN ts_existenciatarimas e
            ON e.id_charola = c.id_charola
        WHERE c.codigocsd IN (
            SELECT codigocsd
            FROM t_ubicacionesainventariar
            WHERE ID_Inventario = ?
        )
    ", [$idInv, $idInv]);

    /* =========================
     * Cambiar estado
     * ========================= */
    dbq("
        UPDATE th_inventario
        SET Status = 'SNAPSHOT'
        WHERE ID_Inventario = ?
    ", [$idInv]);

    db_commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Snapshot generado correctamente'
    ]);

} catch (Throwable $e) {

    db_rollback();

    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
