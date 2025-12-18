<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

try {

    $idInventario = (int)$_POST['id_inventario'];
    $conteoNum    = (int)$_POST['conteo_num'];
    $tipoObjeto   = strtoupper($_POST['tipo_objeto']);
    $idRef        = (int)$_POST['id_referencia'];
    $cantidad     = (float)$_POST['cantidad'];
    $tipoCaptura  = strtoupper($_POST['tipo_captura']);
    $usuario      = (int)$_POST['id_usuario'];

    if (
        !$idInventario || !$conteoNum || !$idRef || !$usuario ||
        !in_array($tipoObjeto, ['PRODUCTO','CHAROLA']) ||
        !in_array($tipoCaptura, ['MANUAL','SKU','EPC'])
    ) {
        throw new Exception('Parámetros inválidos');
    }

    $pdo->beginTransaction();

    // Validar inventario
    $inv = $pdo->prepare("
        SELECT estado
        FROM inventario
        WHERE id_inventario = ?
        FOR UPDATE
    ");
    $inv->execute([$idInventario]);
    $estado = $inv->fetchColumn();

    if ($estado !== 'EN_CONTEO') {
        throw new Exception('Inventario no está en conteo');
    }

    // Validar asignación
    $asg = $pdo->prepare("
        SELECT 1
        FROM inventario_asignacion
        WHERE id_inventario = ?
          AND id_usuario = ?
          AND rol = 'CONTADOR'
          AND conteo_num = ?
    ");
    $asg->execute([$idInventario, $usuario, $conteoNum]);

    if (!$asg->fetch()) {
        throw new Exception('Usuario no autorizado para este conteo');
    }

    // Validar objeto pertenece al inventario
    $obj = $pdo->prepare("
        SELECT 1
        FROM inventario_objeto
        WHERE id_inventario = ?
          AND tipo_objeto = ?
          AND id_referencia = ?
    ");
    $obj->execute([$idInventario, $tipoObjeto, $idRef]);

    if (!$obj->fetch()) {
        throw new Exception('Objeto no pertenece al inventario');
    }

    // Eliminar intento previo del mismo usuario y conteo
    $pdo->prepare("
        DELETE FROM inventario_conteo
        WHERE id_inventario = ?
          AND conteo_num = ?
          AND id_usuario = ?
          AND tipo_objeto = ?
          AND id_referencia = ?
    ")->execute([
        $idInventario,
        $conteoNum,
        $usuario,
        $tipoObjeto,
        $idRef
    ]);

    // Registrar conteo
    $stmt = $pdo->prepare("
        INSERT INTO inventario_conteo
        (id_inventario, conteo_num, tipo_objeto, id_referencia,
         tipo_captura, cantidad, id_usuario)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $idInventario,
        $conteoNum,
        $tipoObjeto,
        $idRef,
        $tipoCaptura,
        $cantidad,
        $usuario
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Conteo registrado correctamente'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
