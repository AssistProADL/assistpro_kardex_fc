<?php
// public/api/integraciones/contratos/v1/catalogos/articulos.php

header('Content-Type: application/json; charset=utf-8');

function j($v){
  return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = [];

$req = [
  'evento'  => 'CAT_ARTICULOS_SYNC',
  'payload' => $payload,
  'contexto' => [
    'sistema'      => $payload['sistema'] ?? 'EXTERNO',
    'referencia'   => $payload['referencia'] ?? ($payload['folio'] ?? null),
    'usuario'      => $payload['usuario'] ?? ($payload['cve_usuario'] ?? null),
    'dispositivo'  => $payload['dispositivo'] ?? null,
    'empresa_id'   => $payload['empresa_id'] ?? null,
    'cve_almacenp' => $payload['cve_almacenp'] ?? null,
    'cve_almac'    => $payload['cve_almac'] ?? null,
    'cliente_id'   => $payload['cliente_id'] ?? null,
    // opcional si luego lo usas para decidir conexión:
    'conexion_id'  => $payload['conexion_id'] ?? 1,
  ]
];

// --- Construir URL del dispatcher de forma robusta ---
// SCRIPT_NAME típico:
// /assistpro_kardex_fc/public/api/integraciones/contratos/v1/catalogos/articulos.php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '';

$root = preg_replace(
  '#/api/integraciones/contratos/v1/catalogos/articulos\.php$#',
  '',
  $script
);

if (!$root || $root === $script) {
  // fallback: recorta hasta /public
  $pos = strpos($script, '/public/');
  $root = ($pos !== false) ? substr($script, 0, $pos + 7) : '/assistpro_kardex_fc/public';
}

$dispatcherUrl = $scheme . '://' . $host . $root . '/api/integraciones/dispatcher.php';

$opts = [
  'http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/json; charset=utf-8\r\n",
    'content' => j($req),
    'timeout' => 30,
    'ignore_errors' => true
  ]
];

$ctx = stream_context_create($opts);
$out = @file_get_contents($dispatcherUrl, false, $ctx);

if ($out === false) {
  http_response_code(500);
  echo j([
    'ok' => false,
    'error' => 'No se pudo conectar al dispatcher',
    'dispatcher' => $dispatcherUrl
  ]);
  exit;
}

// Si por alguna razón el dispatcher devolvió HTML (login, etc), regresa error JSON controlado
if (stripos($out, '<html') !== false || stripos($out, '<!DOCTYPE') !== false) {
  http_response_code(502);
  echo j([
    'ok' => false,
    'error' => 'El dispatcher devolvió HTML (posible login/router). Revisa dispatcher.php y .htaccess.',
    'dispatcher' => $dispatcherUrl,
    'sample' => substr($out, 0, 300)
  ]);
  exit;
}

$code = 200;
if (isset($http_response_header) && is_array($http_response_header)) {
  foreach ($http_response_header as $h) {
    if (preg_match('#HTTP/\S+\s+(\d{3})#', $h, $m)) { $code = (int)$m[1]; break; }
  }
}

http_response_code($code);
echo $out;
