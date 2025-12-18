<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

try {

    $idInventario = (int)$_POST['id_inventario'];
    $usuarioAuth  = (int)$_POST['usuario_autoriza'];
    $motivo       = trim($_POST['motivo'] ?? '');

    if (!$idInventario || !$usuarioAuth || $motivo === '') {
        throw new Exception('Parámetros inválidos');
    }

    $pdo->beginTransaction();

    // Bloquear inventario
    $inv = $pdo->prepare("
        SELECT estado
        FROM inventario
        WHERE id_inventario = ?
        FOR UPDATE
    ");
    $inv->execute([$idInventario]);
    $estado = $inv->fetchColumn();

    if ($estado !== 'CERRADO') {
        throw new Exception('Inventario no está cerrado');
    }

    // Obtener resultados con diferencia
    $res = $pdo->prepare("
        SELECT
            tipo_objeto,
            id_referencia,
            cantidad_teorica,
            cantidad_contada,
            diferencia
        FROM inventario_resultado
        WHERE id_inventario = ?
          AND estatus = 'DIFERENCIA'
    ");
    $res->execute([$idInventario]);
    $ajustes = $res->fetchAll(PDO::FETCH_ASSOC);

    if (!$ajustes) {
        throw new Exception('No hay diferencias para ajustar');
    }

    // Preparar inserción de auditoría
    $insAud = $pdo->prepare("
        INSERT INTO inventario_ajuste
        (id_inventario, tipo_objeto, id_referencia,
         cantidad_antes, cantidad_ajuste, cantidad_despues,
         motivo, usuario_autoriza, fecha_autorizacion, aplicado)
        VALUES (?,?,?,?,?,?,?,?,NOW(),1)
    ");

    foreach ($ajustes as $a) {

        $antes  = (float)$a['cantidad_teorica'];
        $ajuste = (float)$a['diferencia'];
        $despues = $antes + $ajuste;

        /*
          ⚠️ AQUÍ NO TOCAMOS LEGACY DIRECTO
          La actualización real de existencias debe:
          - llamar a procedimiento de kardex
          - o cola de movimientos
          - o API de inventarios base
        */

        // Registrar auditoría
        $insAud->execute([
            $idInventario,
            $a['tipo_objeto'],
            $a['id_referencia'],
            $antes,
            $ajuste,
            $despues,
            $motivo,
            $usuarioAuth
        ]);
    }

    // Marcar inventario como ajustado
    $pdo->prepare("
        UPDATE inventario
        SET estado = 'AJUSTADO'
        WHERE id_inventario = ?
    ")->execute([$idInventario]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Ajustes aplicados correctamente',
        'ajustes_aplicados' => count($ajustes)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
