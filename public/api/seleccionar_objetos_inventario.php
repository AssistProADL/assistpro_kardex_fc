<?php
require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json');

try {

    $idInventario = (int)$_POST['id_inventario'];
    $tipoObjeto   = strtoupper($_POST['tipo_objeto']);
    $ids          = $_POST['ids'];

    if (!$idInventario || !in_array($tipoObjeto, ['BL','PRODUCTO','CHAROLA'])) {
        throw new Exception('Parámetros inválidos');
    }

    if (!is_array($ids) || empty($ids)) {
        throw new Exception('Lista de IDs vacía');
    }

    $pdo->beginTransaction();

    // Validar inventario existe
    $chk = $pdo->prepare("
        SELECT id_inventario 
        FROM inventario 
        WHERE id_inventario = ?
    ");
    $chk->execute([$idInventario]);
    if (!$chk->fetch()) {
        throw new Exception('Inventario no encontrado');
    }

    // Insertar objetos
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO inventario_objeto
        (id_inventario, tipo_objeto, id_referencia)
        VALUES (?,?,?)
    ");

    foreach ($ids as $idRef) {
        $stmt->execute([
            $idInventario,
            $tipoObjeto,
            (int)$idRef
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'insertados' => count($ids)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
