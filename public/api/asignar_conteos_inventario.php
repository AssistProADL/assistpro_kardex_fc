<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

try {

    $idInventario = (int)$_POST['id_inventario'];
    $asignaciones = $_POST['asignaciones'];

    if (!$idInventario || !is_array($asignaciones)) {
        throw new Exception('Parámetros inválidos');
    }

    $pdo->beginTransaction();

    // Validar estado del inventario
    $inv = $pdo->prepare("
        SELECT estado
        FROM inventario
        WHERE id_inventario = ?
        FOR UPDATE
    ");
    $inv->execute([$idInventario]);
    $estado = $inv->fetchColumn();

    if ($estado !== 'PLANEADO') {
        throw new Exception('Inventario no está en estado PLANEADO');
    }

    // Limpiar asignaciones previas
    $pdo->prepare("
        DELETE FROM inventario_asignacion
        WHERE id_inventario = ?
    ")->execute([$idInventario]);

    $usuariosConteo = [];

    $stmt = $pdo->prepare("
        INSERT INTO inventario_asignacion
        (id_inventario, id_usuario, rol, conteo_num)
        VALUES (?,?,?,?)
    ");

    foreach ($asignaciones as $a) {

        $usuario = (int)$a['id_usuario'];
        $rol     = strtoupper($a['rol']);
        $conteo  = $a['conteo_num'] ?? null;

        if (!in_array($rol, ['CONTADOR','VALIDADOR','SUPERVISOR'])) {
            throw new Exception('Rol inválido');
        }

        if ($rol === 'CONTADOR') {
            if (!$conteo || $conteo < 1) {
                throw new Exception('Conteo inválido');
            }
            if (isset($usuariosConteo[$usuario])) {
                throw new Exception('Usuario asignado a más de un conteo');
            }
            $usuariosConteo[$usuario] = true;
        }

        $stmt->execute([
            $idInventario,
            $usuario,
            $rol,
            $conteo
        ]);
    }

    // Cambiar estado
    $pdo->prepare("
        UPDATE inventario
        SET estado = 'EN_CONTEO'
        WHERE id_inventario = ?
    ")->execute([$idInventario]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Conteos asignados correctamente'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
