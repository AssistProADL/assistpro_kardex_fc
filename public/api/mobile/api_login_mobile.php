<?php
/**
 * AssistPro - Login Mobile
 * Ruta correcta y estable
 */

header('Content-Type: application/json; charset=utf-8');

// === RUTAS REALES DEL PROYECTO ===
$API_BASE = dirname(__DIR__, 3) . '/app/api_base.php';
$DB_BASE  = dirname(__DIR__, 3) . '/app/db.php';

// Validación dura (para evitar HTML en respuestas)
if (!file_exists($API_BASE) || !file_exists($DB_BASE)) {
    echo json_encode([
        "ok" => 0,
        "msg" => "Infraestructura no localizada (api_base/db)"
    ]);
    exit;
}

require_once $API_BASE;
require_once $DB_BASE;

// ===============================
// INPUT
// ===============================
$usuario = trim($_POST['usuario'] ?? '');
$pwd     = trim($_POST['password'] ?? '');
$almacen = trim($_POST['almacen'] ?? '');

if ($usuario === '' || $pwd === '' || $almacen === '') {
    echo json_encode(["ok"=>0,"msg"=>"Faltan datos obligatorios"]);
    exit;
}

// ===============================
// VALIDAR USUARIO
// ===============================
$u = db_row("
    SELECT
        id_user,
        cve_usuario,
        nombre_completo,
        perfil,
        pwd_usuario,
        ban_usuario,
        status,
        Activo
    FROM c_usuario
    WHERE cve_usuario = ?
    LIMIT 1
", [$usuario]);

if (!$u) {
    echo json_encode(["ok"=>0,"msg"=>"Usuario no existe"]);
    exit;
}

if ((int)$u['ban_usuario'] === 1) {
    echo json_encode(["ok"=>0,"msg"=>"Usuario bloqueado"]);
    exit;
}

if ((int)$u['Activo'] === 0) {
    echo json_encode(["ok"=>0,"msg"=>"Usuario inactivo"]);
    exit;
}

if ($u['status'] && strtoupper($u['status']) !== 'A') {
    echo json_encode(["ok"=>0,"msg"=>"Usuario no autorizado"]);
    exit;
}

// Password plano (según tu modelo actual)
if ($u['pwd_usuario'] !== $pwd) {
    echo json_encode(["ok"=>0,"msg"=>"Password incorrecto"]);
    exit;
}

// ===============================
// VALIDAR ALMACÉN ASIGNADO
// ===============================
$perm = db_row("
    SELECT 1
    FROM trel_us_alm
    WHERE cve_usuario = ?
      AND cve_almac   = ?
      AND (Activo IS NULL OR Activo = 1)
    LIMIT 1
", [$usuario, $almacen]);

if (!$perm) {
    echo json_encode([
        "ok" => 0,
        "msg" => "Usuario sin acceso al almacén seleccionado"
    ]);
    exit;
}

// ===============================
// LOGIN OK
// ===============================
$token = bin2hex(random_bytes(16));

echo json_encode([
    "ok" => 1,
    "msg" => "OK",
    "token" => $token,
    "usuario" => [
        "cve_usuario" => $u['cve_usuario'],
        "nombre" => $u['nombre_completo'] ?: $u['cve_usuario'],
        "perfil" => $u['perfil'] ?: 'OPERADOR'
    ],
    "almacen" => $almacen
]);
