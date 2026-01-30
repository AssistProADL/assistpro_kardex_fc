<?php
/* =========================================================
   API - APLICAR CORRIDA DE IMPORTACIÓN (GOBIERNO)
   Ruta: /public/api/importadores/api_import_run_apply.php
   Entrada JSON: { "run_id": 123 }
   Salida JSON:  { ok: true|false, ... }
   ========================================================= */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

/**
 * Convertir errores PHP a exceptions para que no impriman <br /> en la salida
 */
set_error_handler(function ($severity, $message, $file, $line) {
  if (!(error_reporting() & $severity)) return false;
  throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Capturar fatals y devolver JSON
 */
register_shutdown_function(function () {
  $err = error_get_last();
  if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    http_response_code(500);
    echo json_encode([
      'ok' => false,
      'error' => 'Fatal: ' . $err['message'],
      'file' => $err['file'],
      'line' => $err['line'],
    ], JSON_UNESCAPED_UNICODE);
  }
});

function jexit($arr, $code = 200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json_body(){
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $j = json_decode($raw, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    return ['__json_error__' => json_last_error_msg(), '__raw__' => $raw];
  }
  return $j ?: [];
}

function http_post_json($url, $payload, $timeout = 180){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json; charset=utf-8',
      'Accept: application/json',
      'X-Import-Internal: 1'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => $timeout,
  ]);
  $body = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $cerr = curl_error($ch);
  curl_close($ch);

  return [$http, $body, $cerr];
}

try {
  require_once __DIR__ . '/../../../app/db.php';
  $pdo = db_pdo();
  if (!$pdo) jexit(['ok'=>false,'error'=>'Sin conexión BD'], 500);

  $in = read_json_body();
  if (isset($in['__json_error__'])) {
    jexit(['ok'=>false,'error'=>'JSON inválido: '.$in['__json_error__']], 400);
  }

  $run_id = isset($in['run_id']) ? (int)$in['run_id'] : 0;
  if ($run_id <= 0) jexit(['ok'=>false,'error'=>'run_id requerido'], 400);

  // Leer corrida + configuración del importador
  $sql = "
    SELECT
      r.id, r.tipo_ingreso, r.status, r.folio_importacion,
      r.total_lineas, r.total_err,
      i.ruta_api, i.descripcion
    FROM ap_import_runs r
    LEFT JOIN c_importador i ON i.clave = r.tipo_ingreso
    WHERE r.id = ?
    LIMIT 1
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$run_id]);
  $run = $st->fetch(PDO::FETCH_ASSOC);

  if (!$run) jexit(['ok'=>false,'error'=>"Corrida no existe: $run_id"], 404);

  $status = strtoupper((string)$run['status']);
  if ($status === 'APLICADO') {
    jexit([
      'ok'=>true,
      'msg'=>'Corrida ya aplicada (idempotente)',
      'run_id'=>$run_id,
      'folio'=>$run['folio_importacion'] ?? null
    ]);
  }
  if ($status !== 'VALIDADO') {
    jexit([
      'ok'=>false,
      'error'=>"Estatus inválido para aplicar: {$run['status']} (se requiere VALIDADO)",
      'run_id'=>$run_id,
      'folio'=>$run['folio_importacion'] ?? null
    ], 409);
  }
  if ((int)$run['total_err'] > 0) {
    jexit([
      'ok'=>false,
      'error'=>"Corrida con errores ({$run['total_err']}). No se puede aplicar.",
      'run_id'=>$run_id,
      'folio'=>$run['folio_importacion'] ?? null
    ], 409);
  }

  // ✅ VALIDACIÓN: el detalle está en ap_import_run_rows (NO existe ap_import_run_detalle)
  $st2 = $pdo->prepare("SELECT COUNT(*) c FROM ap_import_run_rows WHERE run_id=?");
  $st2->execute([$run_id]);
  $c = (int)$st2->fetchColumn();
  if ($c <= 0) {
    jexit(['ok'=>false,'error'=>'La corrida no tiene detalle (ap_import_run_rows vacío).'], 409);
  }

  // Ruta API del importador
  $ruta_api = trim((string)$run['ruta_api']);
  if ($ruta_api === '') {
    jexit(['ok'=>false,'error'=>'Importador sin ruta_api en c_importador.'], 500);
  }

  // Construir URL absoluta al importador
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

  // Si ruta_api es "/public/....php", quitar "/public" porque ya estamos en el docroot público.
  $path = $ruta_api;
  if (stripos($path, '/public/') === 0) {
    $path = substr($path, strlen('/public'));
    if ($path === '') $path = '/';
  }
  if ($path[0] !== '/') $path = '/'.$path;

  $url = $scheme.'://'.$host.$path;

  // Transacción de gobierno: aplicamos y si el importador falla, rollback y status=ERROR
  $pdo->beginTransaction();

  // Llamada al importador específico (reglas RTM/BL + TH/TD pedido + TH/TD aduana viven ahí)
  [$http, $body, $cerr] = http_post_json($url, [
    'accion' => 'apply',
    'run_id' => $run_id
  ], 180);

  if ($cerr) {
    $pdo->rollBack();
    $pdo->prepare("UPDATE ap_import_runs SET status='ERROR' WHERE id=?")->execute([$run_id]);
    jexit([
      'ok'=>false,
      'error'=>'Falla al invocar importador (cURL): '.$cerr,
      'importador_url'=>$url
    ], 500);
  }

  if ($http < 200 || $http >= 300) {
    $pdo->rollBack();
    $pdo->prepare("UPDATE ap_import_runs SET status='ERROR' WHERE id=?")->execute([$run_id]);
    jexit([
      'ok'=>false,
      'error'=>"Importador respondió HTTP $http",
      'importador_url'=>$url,
      'respuesta'=>mb_substr((string)$body, 0, 2000)
    ], 500);
  }

  // El importador debe regresar JSON {ok:true|false, ...}
  $j = json_decode((string)$body, true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    $pdo->rollBack();
    $pdo->prepare("UPDATE ap_import_runs SET status='ERROR' WHERE id=?")->execute([$run_id]);
    jexit([
      'ok'=>false,
      'error'=>'Importador no devolvió JSON (se recibió HTML/texto).',
      'importador_url'=>$url,
      'sample'=>mb_substr(strip_tags((string)$body), 0, 2000)
    ], 500);
  }

  if (empty($j['ok'])) {
    $pdo->rollBack();
    $pdo->prepare("UPDATE ap_import_runs SET status='ERROR' WHERE id=?")->execute([$run_id]);
    jexit([
      'ok'=>false,
      'error'=>$j['error'] ?? 'Importador reportó error',
      'importador'=>$run['tipo_ingreso'],
      'detalle'=>$j
    ], 500);
  }

  // OK: marcar corrida aplicada
  $pdo->prepare("UPDATE ap_import_runs SET status='APLICADO' WHERE id=?")->execute([$run_id]);
  $pdo->commit();

  jexit([
    'ok'=>true,
    'msg'=>'Aplicación exitosa',
    'run_id'=>$run_id,
    'folio'=>$run['folio_importacion'] ?? null,
    'importador'=>$run['tipo_ingreso'],
    'resultado_importador'=>$j
  ]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  try {
    if (isset($pdo) && $pdo instanceof PDO && isset($run_id) && $run_id > 0) {
      $pdo->prepare("UPDATE ap_import_runs SET status='ERROR' WHERE id=?")->execute([$run_id]);
    }
  } catch (Throwable $e2) {}

  jexit([
    'ok'=>false,
    'error'=>$e->getMessage(),
    'where'=>[
      'file'=>$e->getFile(),
      'line'=>$e->getLine()
    ]
  ], 500);
}
