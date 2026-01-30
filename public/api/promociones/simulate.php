<?php
require_once '../../../app/db.php';
header('Content-Type: application/json');

/**
 * SIMULADOR DE PROMOCIONES (por ticket, NO acumulable)
 * - Selecciona la regla de mayor nivel que cumpla.
 * - trigger_tipo: UNIDADES | MONTO | MIXTO
 * - tope_valor en promo_reward se interpreta como TOPE DE UNIDADES DE REGALO (solo BONIF_PRODUCTO).
 * - No acumula: siempre aplica máximo 1 vez por ticket (si cumple).
 */

function read_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) return $json;
    if (!empty($_POST)) return $_POST; // fallback form-data
    return [];
}

$in = read_input();

$promo_id        = $in['promo_id'] ?? null;
$cliente         = $in['cliente'] ?? null; // reservado para scope futuro
$monto_simulado  = isset($in['monto_simulado']) ? floatval($in['monto_simulado']) : 0.0;
$qty_simulada    = isset($in['qty_simulada']) ? floatval($in['qty_simulada']) : 0.0;
$unimed_simulada = trim((string)($in['unimed_simulada'] ?? ''));

if (!$promo_id) {
    echo json_encode(['ok' => 0, 'msg' => 'promo_id requerido']);
    exit;
}

/**
 * 1) Reglas activas, mayor nivel primero
 */
$rules = db_all(
    "SELECT * FROM promo_rule
     WHERE promo_id = ? AND activo = 1
     ORDER BY nivel DESC, id_rule DESC",
    [$promo_id]
);

if (!$rules) {
    echo json_encode([
        'ok' => 1,
        'aplica' => 0,
        'msg' => 'Sin reglas activas',
        'data' => ['promo_id' => $promo_id]
    ]);
    exit;
}

/**
 * 2) Elegir la regla ganadora (primera que cumpla)
 * Política: por ticket, NO acumulable -> aplica 1 vez.
 */
$winner = null;
$winner_eval = null;

foreach ($rules as $r) {

    $trigger  = strtoupper(trim($r['trigger_tipo'] ?? ''));
    $th_monto = isset($r['threshold_monto']) ? floatval($r['threshold_monto']) : null;
    $th_qty   = isset($r['threshold_qty']) ? floatval($r['threshold_qty']) : null;

    $cumple = false;
    $motivo = '';

    if ($trigger === 'UNIDADES') {
        if ($th_qty === null || $th_qty <= 0) {
            $cumple = false;
            $motivo = 'Regla UNIDADES sin threshold_qty válido';
        } else {
            $cumple = ($qty_simulada >= $th_qty);
            $motivo = $cumple ? "Cumple UNIDADES: {$qty_simulada} >= {$th_qty}" : "No cumple UNIDADES: {$qty_simulada} < {$th_qty}";
        }

    } elseif ($trigger === 'MONTO') {
        if ($th_monto === null || $th_monto <= 0) {
            $cumple = false;
            $motivo = 'Regla MONTO sin threshold_monto válido';
        } else {
            $cumple = ($monto_simulado >= $th_monto);
            $motivo = $cumple ? "Cumple MONTO: {$monto_simulado} >= {$th_monto}" : "No cumple MONTO: {$monto_simulado} < {$th_monto}";
        }

    } elseif ($trigger === 'MIXTO') {
        $ok_qty   = ($th_qty !== null && $th_qty > 0) ? ($qty_simulada >= $th_qty) : false;
        $ok_monto = ($th_monto !== null && $th_monto > 0) ? ($monto_simulado >= $th_monto) : false;

        $cumple = ($ok_qty && $ok_monto);
        $motivo = $cumple
            ? "Cumple MIXTO: qty {$qty_simulada}>= {$th_qty} y monto {$monto_simulado}>= {$th_monto}"
            : "No cumple MIXTO: qty_ok=" . ($ok_qty ? '1' : '0') . " monto_ok=" . ($ok_monto ? '1' : '0');

    } else {
        $cumple = false;
        $motivo = "trigger_tipo inválido: {$trigger}";
    }

    if ($cumple) {
        $winner = $r;
        $winner_eval = [
            'motivo' => $motivo,
            'trigger' => $trigger,
            'monto_simulado' => $monto_simulado,
            'qty_simulada' => $qty_simulada,
            'unimed_simulada' => $unimed_simulada,
            'policy' => 'POR_TICKET_NO_ACUMULA'
        ];
        break;
    }
}

if (!$winner) {
    echo json_encode([
        'ok' => 1,
        'aplica' => 0,
        'msg' => 'Ninguna regla cumple para este ticket',
        'data' => [
            'promo_id' => $promo_id,
            'monto_simulado' => $monto_simulado,
            'qty_simulada' => $qty_simulada,
            'unimed_simulada' => $unimed_simulada
        ]
    ]);
    exit;
}

/**
 * 3) Rewards activos de la regla ganadora
 * Por ticket: si hay varios rewards, se devuelven todos (cada uno aplica 1 vez).
 */
$rewards = db_all(
    "SELECT * FROM promo_reward
     WHERE id_rule = ? AND activo = 1
     ORDER BY id_reward ASC",
    [$winner['id_rule']]
);

if (!$rewards) {
    echo json_encode([
        'ok' => 1,
        'aplica' => 0,
        'msg' => 'Regla cumple pero no tiene rewards activos',
        'data' => [
            'rule' => $winner,
            'eval' => $winner_eval
        ]
    ]);
    exit;
}

/**
 * 4) Aplicación (por ticket, sin acumulación)
 * - BONIF_PRODUCTO: qty_final = qty_config * 1
 *   - tope_valor -> min(qty_final, tope_valor)
 */
$applied = [];
foreach ($rewards as $rw) {
    $reward_tipo = strtoupper(trim($rw['reward_tipo'] ?? ''));
    $qty_rw = isset($rw['qty']) ? floatval($rw['qty']) : 0.0;
    $tope = isset($rw['tope_valor']) && $rw['tope_valor'] !== null ? floatval($rw['tope_valor']) : null;

    $veces = 1; // POR TICKET
    $qty_final = $qty_rw * $veces;

    if ($reward_tipo === 'BONIF_PRODUCTO') {
        if ($tope !== null && $tope > 0) {
            $qty_final = min($qty_final, $tope);
        }
    }

    $applied[] = [
        'id_reward' => $rw['id_reward'],
        'reward_tipo' => $rw['reward_tipo'],
        'cve_articulo' => $rw['cve_articulo'],
        'unimed' => $rw['unimed'],
        'qty_config' => $qty_rw,
        'veces_aplica' => $veces,
        'tope_unidades' => $tope,
        'qty_final' => $qty_final,
        'aplica_sobre' => $rw['aplica_sobre'] ?? 'TOTAL',
        'observaciones' => $rw['observaciones'] ?? null
    ];
}

echo json_encode([
    'ok' => 1,
    'aplica' => 1,
    'msg' => 'Promoción aplicada (por ticket, sin acumulación)',
    'data' => [
        'promo_id' => $promo_id,
        'rule' => [
            'id_rule' => $winner['id_rule'],
            'nivel' => $winner['nivel'],
            'trigger_tipo' => $winner['trigger_tipo'],
            'threshold_monto' => $winner['threshold_monto'],
            'threshold_qty' => $winner['threshold_qty'],
            'acumula' => $winner['acumula'],
            'acumula_por' => $winner['acumula_por']
        ],
        'eval' => $winner_eval,
        'rewards' => $applied
    ]
]);
