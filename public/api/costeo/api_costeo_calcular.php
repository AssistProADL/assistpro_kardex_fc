<?php
/**
 * API de Costeo / Totalización
 * --------------------------------
 * - Calcula subtotal, IVA y total
 * - Regla fiscal:
 *      * Moneda USD => IVA = 0
 *      * Moneda MXN => IVA según iva_pct
 * - Soporta una o múltiples líneas
 * - PHP puro, sin framework
 */

header('Content-Type: application/json');

// ==============================
// Utilidades
// ==============================
function num($v) {
    if ($v === null || $v === '') return 0;
    return round((float)$v, 4);
}

function money($v) {
    return round($v, 2);
}

// ==============================
// Leer input JSON
// ==============================
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'error' => 'JSON inválido'
    ]);
    exit;
}

// ==============================
// Normalizar entrada
// ==============================
$moneda  = strtoupper($data['moneda'] ?? 'MXN');
$iva_pct = num($data['iva_pct'] ?? 16);
$lines   = $data['lines'] ?? [];

// Permitir una sola línea sin array
if (isset($data['cantidad']) && isset($data['costo_unitario'])) {
    $lines = [$data];
}

// ==============================
// Procesar líneas
// ==============================
$result_lines = [];
$summary = [
    'subtotal' => 0,
    'iva'      => 0,
    'total'    => 0
];

foreach ($lines as $idx => $l) {

    $qty   = num($l['cantidad'] ?? $l['qty'] ?? 0);
    $cost  = num($l['costo_unitario'] ?? $l['unit_cost'] ?? 0);

    $subtotal = money($qty * $cost);

    // Regla fiscal clave
    if ($moneda === 'USD') {
        $iva = 0;
        $iva_aplicado = 0;
    } else {
        $iva = money($subtotal * ($iva_pct / 100));
        $iva_aplicado = $iva_pct;
    }

    $total = money($subtotal + $iva);

    $result_lines[] = [
        'line'        => $idx + 1,
        'cantidad'    => $qty,
        'costo_unit'  => $cost,
        'subtotal'    => $subtotal,
        'iva'         => $iva,
        'total'       => $total
    ];

    $summary['subtotal'] += $subtotal;
    $summary['iva']      += $iva;
    $summary['total']    += $total;
}

// Redondeo final
$summary = [
    'subtotal' => money($summary['subtotal']),
    'iva'      => money($summary['iva']),
    'total'    => money($summary['total'])
];

// ==============================
// Respuesta
// ==============================
echo json_encode([
    'moneda'             => $moneda,
    'iva_pct_aplicado'   => $moneda === 'USD' ? 0 : $iva_pct,
    'lines'              => $result_lines,
    'summary'            => $summary
], JSON_PRETTY_PRINT);
