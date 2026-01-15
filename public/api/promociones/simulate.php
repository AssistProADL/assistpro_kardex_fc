<?php
require_once '../../../app/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['promo_id']) || !isset($data['monto_simulado'])) {
    echo json_encode(['ok' => 0, 'msg' => 'Datos insuficientes (promo_id, monto_simulado)']);
    exit;
}

$promo = db_row("
SELECT r.*, rw.reward_tipo, rw.valor, rw.cve_articulo, rw.qty
FROM promo_rule r
JOIN promo_reward rw ON rw.id_rule = r.id_rule
WHERE r.promo_id = ?
AND r.threshold_monto <= ?
ORDER BY r.nivel DESC
LIMIT 1
", [$data['promo_id'], $data['monto_simulado']]);

if (!$promo) {
    echo json_encode([
        'ok' => 1,
        'msg' => 'No se cumplió ninguna regla para el monto proporcionado.',
        'beneficio' => null
    ]);
    exit;
}

echo json_encode([
    'ok' => 1,
    'nivel' => $promo['nivel'],
    'beneficio' => [
        'tipo' => $promo['reward_tipo'],
        'valor' => $promo['valor'],
        'articulo' => $promo['cve_articulo'],
        'qty' => $promo['qty']
    ]
]);
