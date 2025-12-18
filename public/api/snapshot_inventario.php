<?php
require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json');

try {

    $idInventario = (int)$_POST['id_inventario'];
    $usuario      = (int)$_POST['usuario'];

    if (!$idInventario || !$usuario) {
        throw new Exception('Parámetros inválidos');
    }

    $pdo->beginTransaction();

    // Obtener inventario
    $inv = $pdo->prepare("
        SELECT tipo_inventario, estado, cve_almacenp
        FROM inventario
        WHERE id_inventario = ?
        FOR UPDATE
    ");
    $inv->execute([$idInventario]);
    $inventario = $inv->fetch(PDO::FETCH_ASSOC);

    if (!$inventario) {
        throw new Exception('Inventario no encontrado');
    }

    if (!in_array($inventario['estado'], ['CREADO','ASIGNADO'])) {
        throw new Exception('Snapshot ya ejecutado o estado inválido');
    }

    // Obtener objetos
    $objs = $pdo->prepare("
        SELECT id_inventario_objeto, tipo_objeto, id_referencia
        FROM inventario_objeto
        WHERE id_inventario = ?
    ");
    $objs->execute([$idInventario]);
    $objetos = $objs->fetchAll(PDO::FETCH_ASSOC);

    if (!$objetos) {
        throw new Exception('No hay objetos seleccionados');
    }

    $upd = $pdo->prepare("
        UPDATE inventario_objeto
        SET snapshot_teorico = ?
        WHERE id_inventario_objeto = ?
    ");

    foreach ($objetos as $o) {

        $cantidad = 0;

        switch ($o['tipo_objeto']) {

            case 'BL':
                // piezas
                $q1 = $pdo->prepare("
                    SELECT IFNULL(SUM(Existencia),0)
                    FROM ts_existenciapiezas
                    WHERE idy_ubica = ?
                      AND cve_almac = ?
                ");
                $q1->execute([$o['id_referencia'], $inventario['cve_almacenp']]);
                $cantidad += (float)$q1->fetchColumn();

                // cajas / charolas
                $q2 = $pdo->prepare("
                    SELECT IFNULL(SUM(PiezasXCaja),0)
                    FROM ts_existenciacajas
                    WHERE idy_ubica = ?
                      AND Cve_Almac = ?
                ");
                $q2->execute([$o['id_referencia'], $inventario['cve_almacenp']]);
                $cantidad += (float)$q2->fetchColumn();

                break;

            case 'PRODUCTO':
                $q = $pdo->prepare("
                    SELECT IFNULL(SUM(Existencia),0)
                    FROM ts_existenciapiezas
                    WHERE id_articulo = ?
                      AND cve_almac = ?
                ");
                $q->execute([$o['id_referencia'], $inventario['cve_almacenp']]);
                $cantidad = (float)$q->fetchColumn();
                break;

            case 'CHAROLA':
                // Una charola existente = 1
                $cantidad = 1;
                break;
        }

        $upd->execute([$cantidad, $o['id_inventario_objeto']]);
    }

    // Cambiar estado
    $updInv = $pdo->prepare("
        UPDATE inventario
        SET estado = 'PLANEADO'
        WHERE id_inventario = ?
    ");
    $updInv->execute([$idInventario]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Snapshot teórico generado'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
