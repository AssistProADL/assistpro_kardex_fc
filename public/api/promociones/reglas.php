<?php
require_once '../../../app/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* LISTAR */
if ($action === 'reglas_list') {

    $promo_id = $_GET['promo_id'] ?? null;
    if (!$promo_id) {
        echo json_encode(['ok' => 0, 'msg' => 'promo_id requerido']);
        exit;
    }

    $rows = db_all(
        "SELECT * FROM promo_rule WHERE promo_id = ? ORDER BY nivel ASC",
        [$promo_id]
    );

    echo json_encode(['ok' => 1, 'data' => $rows]);
    exit;
}

/* AGREGAR */
if ($action === 'reglas_add') {

    $d = json_decode(file_get_contents('php://input'), true);

    dbq(
        "INSERT INTO promo_rule
     (promo_id,nivel,trigger_tipo,threshold_monto,threshold_qty,acumula,acumula_por)
     VALUES (?,?,?,?,?,?,?)",
        [
            $d['promo_id'],
            $d['nivel'],
            $d['trigger_tipo'],
            $d['threshold_monto'] ?? null,
            $d['threshold_qty'] ?? null,
            $d['acumula'] ?? 'N',
            $d['acumula_por'] ?? 'TICKET'
        ]
    );

    echo json_encode(['ok' => 1]);
    exit;
}

/* ELIMINAR */
if ($action === 'reglas_delete') {

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['ok' => 0, 'msg' => 'id requerido']);
        exit;
    }

    dbq(
        "DELETE FROM promo_rule WHERE id_rule = ?",
        [$id]
    );

    echo json_encode(['ok' => 1]);
    exit;
}

echo json_encode(['ok' => 0, 'msg' => 'Acción inválida reglas']);
