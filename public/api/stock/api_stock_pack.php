<?php
require_once __DIR__ . '/api_stock.php';

$input = json_decode(file_get_contents('php://input'), true);

/*
$input = [
    'articulo'   => 'PT-001',
    'piezas'     => 120,
    'cajas'      => 10,
    'piezas_x_caja' => 12,
    'zona'       => 'PROD',
    'bl'         => 'BL-PT',
    'lp_caja'    => 'LP-CJ-001',
    'lp_pallet'  => 'LP-PL-001',
    'lote'       => 'L-001',
    'caducidad'  => '2026-12-31',
    'usuario'    => 1
];
*/

$pdo->beginTransaction();

try {

    /** 1️⃣ EGRESO PIEZAS **/
    api_stock($pdo, [
        'tipo'       => 'PIEZA',
        'articulo'   => $input['articulo'],
        'cantidad'   => -$input['piezas'],
        'zona'       => $input['zona'],
        'bl'         => $input['bl'],
        'lp'         => null,
        'lote'       => $input['lote'],
        'caducidad'  => $input['caducidad'],
        'movimiento' => 'PACK',
        'referencia' => 'PACK',
        'usuario'    => $input['usuario']
    ]);

    /** 2️⃣ INGRESO CAJAS **/
    api_stock($pdo, [
        'tipo'       => 'CAJA',
        'articulo'   => $input['articulo'],
        'cantidad'   => $input['cajas'],
        'zona'       => $input['zona'],
        'bl'         => $input['bl'],
        'lp'         => $input['lp_caja'],
        'lote'       => $input['lote'],
        'caducidad'  => $input['caducidad'],
        'movimiento' => 'PACK',
        'referencia' => 'PACK',
        'usuario'    => $input['usuario']
    ]);

    /** 3️⃣ Movimiento LP **/
    $sql = "
        INSERT INTO t_movcharolas (lp_origen, lp_destino, movimiento, referencia)
        VALUES (NULL, :lp, 'PACK', 'PACK')
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lp' => $input['lp_caja']]);

    $pdo->commit();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
