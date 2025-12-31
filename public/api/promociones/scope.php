<?php
require_once '../../../app/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* ===== LISTAR ===== */
if ($action === 'scope_list') {

    $promo_id = $_GET['promo_id'] ?? null;
    if (!$promo_id) {
        echo json_encode(['ok' => 0, 'msg' => 'promo_id requerido']);
        exit;
    }

    $rows = db_all(
        "SELECT * FROM promo_scope
     WHERE promo_id = ?
     ORDER BY scope_tipo, scope_id",
        [$promo_id]
    );

    echo json_encode(['ok' => 1, 'data' => $rows]);
    exit;
}

/* ===== AGREGAR ===== */
if ($action === 'scope_add') {

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['scope_ids'])) {
        echo json_encode(['ok' => 0, 'msg' => 'scope_ids requerido']);
        exit;
    }

    foreach ($data['scope_ids'] as $id) {
        dbq(
            "INSERT INTO promo_scope
       (promo_id, scope_tipo, scope_id)
       VALUES (?,?,?)",
            [$data['promo_id'], $data['scope_tipo'], $id]
        );
    }

    echo json_encode(['ok' => 1]);
    exit;
}

/* ===== ELIMINAR ===== */
if ($action === 'scope_delete') {

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['ok' => 0, 'msg' => 'id requerido']);
        exit;
    }

    dbq(
        "DELETE FROM promo_scope WHERE id_scope = ?",
        [$id]
    );

    echo json_encode(['ok' => 1]);
    exit;
}

echo json_encode(['ok' => 0, 'msg' => 'acción inválida scope']);
