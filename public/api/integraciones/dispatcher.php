<?php
// public/api/integraciones/dispatcher.php

header('Content-Type: application/json; charset=utf-8');

function j($v){
  return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function trace_id(){
  // trace simple y efectivo
  return bin2hex(random_bytes(16));
}

// --- Cargar DB (ruta robusta) ---
$root = realpath(__DIR__ . '/../../..'); // /public/api/integraciones -> /public/api -> /public -> /(root)
$dbPath = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'db.php';

if (!file_exists($dbPath)) {
  http_response_code(500);
  echo j([
    'ok' => false,
    'trace_id' => trace_id(),
    'error' => 'No se encontró app/db.php',
    'expected' => $dbPath
  ]);
  exit;
}

require_once $dbPath; // debe exponer $pdo o helpers db_* según tu estándar

// --- Leer payload ---
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = [];

$trace = trace_id();

// Estructura esperada por tu proxy:
// { evento: "...", payload: {...}, contexto: {...} }
$evento   = $payload['evento'] ?? null;
$body     = $payload['payload'] ?? [];
$contexto = $payload['contexto'] ?? [];

if (!$evento) {
  http_response_code(400);
  echo j([
    'ok' => false,
    'trace_id' => $trace,
    'error' => 'Falta campo: evento'
  ]);
  exit;
}

// --- Log en BD (si existe tabla log_ws_ejecucion) ---
$conexionId = $contexto['conexion_id'] ?? 1;
$sistema    = $contexto['sistema'] ?? 'EXTERNO';
$referencia = $contexto['referencia'] ?? null;
$dispositivo= $contexto['dispositivo'] ?? null;
$usuario    = $contexto['usuario'] ?? null;
$ipOrigen   = $_SERVER['REMOTE_ADDR'] ?? null;

$requestJson = [
  'evento'    => $evento,
  'payload'   => $body,
  'contexto'  => $contexto
];

$logId = null;
try {
  // Intenta insertar en tu tabla real (por tus screenshots: assistpro_etl_fc.log_ws_ejecucion)
  // Si tu $pdo está disponible, úsalo directo.
  if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare("
      INSERT INTO log_ws_ejecucion
      (fecha_ini, trace_id, evento, referencia, sistema, conexion_id, dispositivo, usuario, ip_origen, request_json)
      VALUES (NOW(), :trace_id, :evento, :referencia, :sistema, :conexion_id, :dispositivo, :usuario, :ip_origen, :request_json)
    ");
    $stmt->execute([
      ':trace_id'     => $trace,
      ':evento'       => $evento,
      ':referencia'   => $referencia,
      ':sistema'      => $sistema,
      ':conexion_id'  => $conexionId,
      ':dispositivo'  => $dispositivo,
      ':usuario'      => $usuario,
      ':ip_origen'    => $ipOrigen,
      ':request_json' => json_encode($requestJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ]);
    $logId = (int)$pdo->lastInsertId();
  }
} catch (Throwable $e) {
  // No truena el dispatcher por log: solo anota en respuesta
  $logId = null;
}

// --- Aquí va el “switch” por evento (por ahora STUB controlado) ---
/*
  Más adelante:
  - cargar adapter según c_datos_ws
  - validar si evento activo
  - consumir WS externo (SAP B1 / Legacy)
  - persistir respuesta y marcar fecha_fin
*/

case 'CAT_ARTICULOS_SYNC':
    require_once __DIR__ . '/adapters/catalogos/articulos_adapter.php';
    $result = articulos_adapter($payload, $contexto);
    break;



$responseData = [
  'ok' => true,
  'msg' => 'Dispatcher operativo (stub). Siguiente paso: conectar adapter real.',
  'evento' => $evento
];

// --- Cerrar log (fecha_fin) ---
try {
  if ($logId && isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare("
      UPDATE log_ws_ejecucion
      SET fecha_fin = NOW()
      WHERE id = :id
    ");
    $stmt->execute([':id' => $logId]);
  }
} catch (Throwable $e) {
  // ignore
}

echo j([
  'ok' => true,
  'trace_id' => $trace,
  'data' => $responseData,
  'log_id' => $logId
]);
