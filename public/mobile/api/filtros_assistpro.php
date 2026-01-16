<?php
// /public/mobile/api/filtros_assistpro.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$core = __DIR__ . '/../../api/filtros_assistpro.php';

if (!file_exists($core)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Core no encontrado: /public/api/filtros_assistpro.php'
    ]);
    exit;
}

ob_start();
include $core;               // el core puede hacer echo JSON o imprimir HTML si algo falla
$out = trim(ob_get_clean());

// Si el core ya devolvió JSON, lo respetamos
$decoded = json_decode($out, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    // Normalizamos a {ok:true,data:...} para mobile
    echo json_encode([
        'ok' => true,
        'data' => $decoded
    ]);
    exit;
}

// Si NO es JSON, devolvemos error controlado (para que no truene el login)
http_response_code(200);
echo json_encode([
    'ok' => false,
    'error' => 'Respuesta no JSON desde core',
    'raw' => substr(strip_tags($out), 0, 250)
]);
