<?php
require_once '../../../app/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* LISTAR */
if ($action === 'rewards_list') {
    $id_rule = $_GET['id_rule'] ?? null;
    if (!$id_rule) {
        echo json_encode(['ok' => 0, 'msg' => 'id_rule requerido']);
        exit;
    }
    $rows = db_all(
        "SELECT * FROM promo_reward WHERE id_rule = ?",
        [$id_rule]
    );
    echo json_encode(['ok' => 1, 'data' => $rows]);
    exit;
}

/* AGREGAR */
if ($action === 'rewards_add') {
    $d = json_decode(file_get_contents("php://input"), true);

    dbq(
        "INSERT INTO promo_reward
     (id_rule, reward_tipo, valor, cve_articulo, qty, unimed)
     VALUES (?,?,?,?,?,?)",
        [
            $d['id_rule'],
            $d['reward_tipo'],
            ($d['valor'] === '') ? null : $d['valor'],
            $d['cve_articulo'] ?? null,
            ($d['qty'] === '') ? null : $d['qty'],
            $d['unimed'] ?? null
        ]
    );
    echo json_encode(['ok' => 1]);
    exit;
}

/* ELIMINAR */
if ($action === 'rewards_delete') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['ok' => 0, 'msg' => 'id requerido']);
        exit;
    }
    dbq("DELETE FROM promo_reward WHERE id_reward = ?", [$id]);
    echo json_encode(['ok' => 1]);
    exit;
}

echo json_encode(['ok' => 0, 'msg' => 'acción inválida']);
