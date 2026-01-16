<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../api/filtros_assistpro.php';

/*
|--------------------------------------------------------------------------
| Wrapper Mobile – AssistPro ER
|--------------------------------------------------------------------------
| • No sesiones
| • Respuesta SIEMPRE JSON
| • Captura errores HTML
*/

ob_start();
$resultado = filtros_assistpro(); // función existente
$salida = ob_get_clean();

if (!is_array($resultado)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Respuesta inválida del backend',
        'raw' => substr(strip_tags($salida), 0, 300)
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => $resultado
]);
