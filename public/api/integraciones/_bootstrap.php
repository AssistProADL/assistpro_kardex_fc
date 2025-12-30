<?php
// public/api/integraciones/_bootstrap.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Base del proyecto: .../assistpro_kardex_fc
$PROJECT_ROOT = realpath(__DIR__ . '/../../..'); // desde /public/api/integraciones -> / (raíz proyecto)
if (!$PROJECT_ROOT) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'No se pudo resolver PROJECT_ROOT']);
    exit;
}

// Paths candidatos del config (NO lo muevas; lo encontramos)
$CONFIG_CANDIDATES = [
    $PROJECT_ROOT . '/public/conexion_ws/config_conexion_ws.php',
    $PROJECT_ROOT . '/public/conexion_ws/config_conexion_ws.inc.php',
    $PROJECT_ROOT . '/public/conexion_ws/config.php',
];

$GLOBALS['AP_PROJECT_ROOT'] = $PROJECT_ROOT;
$GLOBALS['AP_WS_CONFIG_PATH'] = null;

foreach ($CONFIG_CANDIDATES as $p) {
    if (is_file($p)) { $GLOBALS['AP_WS_CONFIG_PATH'] = $p; break; }
}

function ap_fail(int $code, string $msg, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['ok'=>false,'error'=>$msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function ap_read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw)==='') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

/**
 * POST JSON vía cURL si existe, fallback a streams si no.
 */
function ap_http_post_json(string $url, array $payload, array $headers = [], int $timeout = 30): array {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $baseHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    foreach ($headers as $h) $baseHeaders[] = $h;

    // 1) cURL si está disponible
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $baseHeaders,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            return ['ok'=>false,'http'=>0,'error'=>'cURL error: '.$err,'raw'=>null];
        }
        return ['ok'=>true,'http'=>$http,'raw'=>$resp];
    }

    // 2) Fallback streams (cuando Apache no cargó curl)
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $baseHeaders),
            'content' => $body,
            'timeout' => $timeout,
        ]
    ]);

    $resp = @file_get_contents($url, false, $context);
    $http = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $line, $m)) { $http = (int)$m[1]; break; }
        }
    }
    if ($resp === false) {
        return ['ok'=>false,'http'=>$http,'error'=>'HTTP stream error','raw'=>null];
    }
    return ['ok'=>true,'http'=>$http,'raw'=>$resp];
}
