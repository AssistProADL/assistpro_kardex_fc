<?php
// public/api/qa_cuarentena/_qa_bootstrap.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php'; // ✅ al nivel de public
db_pdo();
global $pdo;

if (!isset($pdo) || !($pdo instanceof PDO)) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'msg' => 'No se encontró conexión PDO ($pdo). Verifica db_pdo() en /app/db.php',
    'data' => null
  ]);
  exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function qa_ok($data = null, $msg = 'OK') {
  echo json_encode(['ok' => true, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function qa_fail($msg, $code = 400, $data = null) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function qa_param($key, $default = null) {
  return $_GET[$key] ?? $_POST[$key] ?? $default;
}

function qa_query_all($sql, $params = []) {
  global $pdo;
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

function qa_query_one($sql, $params = []) {
  $rows = qa_query_all($sql, $params);
  return $rows[0] ?? null;
}

function qa_exec($sql, $params = []) {
  global $pdo;
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->rowCount();
}

function qa_require_params($arr) {
  foreach ($arr as $p) {
    if (!isset($_REQUEST[$p]) || $_REQUEST[$p] === '') {
      qa_fail("Parámetro requerido: $p", 400);
    }
  }
}