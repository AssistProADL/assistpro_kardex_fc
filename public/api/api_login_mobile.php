<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$pwd     = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
$almacen = isset($_POST['almacen']) ? trim($_POST['almacen']) : '';

if ($usuario === '' || $pwd === '' || $almacen === '') {
  echo json_encode(["ok"=>false,"msg"=>"Faltan parámetros (usuario/pwd/almacen)."]);
  exit;
}

// Conexión: intentamos api_base.php (estándar del proyecto)
$mysqli = null;

$apiBase = __DIR__ . '/../../app/api_base.php';
if (file_exists($apiBase)) {
  require_once $apiBase;

  // Soporte flexible: si api_base define $mysqli, úsalo.
  if (isset($mysqli) && $mysqli instanceof mysqli) {
    // ok
  } else {
    // fallback: si api_base expone function db() o similar, intenta
    if (function_exists('db')) {
      $mysqli = db();
    }
  }
}

if (!($mysqli instanceof mysqli)) {
  echo json_encode(["ok"=>false,"msg"=>"No hay conexión a BD (api_base.php no disponible o no expone mysqli)."]);
  exit;
}

$usuarioEsc = $mysqli->real_escape_string($usuario);
$pwdEsc     = $mysqli->real_escape_string($pwd);
$almEsc     = $mysqli->real_escape_string($almacen);

// 1) Validar usuario
$sqlU = "
  SELECT
    cve_usuario,
    nombre_completo,
    pwd_usuario,
    IFNULL(ban_usuario,0) as ban_usuario,
    IFNULL(Activo,1) as Activo
  FROM c_usuario
  WHERE cve_usuario = '$usuarioEsc'
  LIMIT 1
";
$rsU = $mysqli->query($sqlU);

if (!$rsU || $rsU->num_rows === 0) {
  echo json_encode(["ok"=>false,"msg"=>"Usuario o password incorrecto."]);
  exit;
}
$u = $rsU->fetch_assoc();

if ((int)$u['ban_usuario'] === 1 || (int)$u['Activo'] === 0) {
  echo json_encode(["ok"=>false,"msg"=>"Usuario bloqueado"]);
  exit;
}

// 2) Password (en muchos legacy viene en texto plano; si luego migras a hash, aquí se adapta)
if ((string)$u['pwd_usuario'] !== (string)$pwd) {
  echo json_encode(["ok"=>false,"msg"=>"Usuario o password incorrecto."]);
  exit;
}

// 3) Validar que el usuario tenga el almacén asignado (trel_us_alm). Si no existe la tabla, no bloqueamos.
$hasRel = $mysqli->query("SHOW TABLES LIKE 'trel_us_alm'");
if ($hasRel && $hasRel->num_rows > 0) {
  $sqlA = "
    SELECT 1
    FROM trel_us_alm
    WHERE cve_usuario='$usuarioEsc' AND cve_almac='$almEsc' AND IFNULL(Activo,1)=1
    LIMIT 1
  ";
  $rsA = $mysqli->query($sqlA);
  if (!$rsA || $rsA->num_rows === 0) {
    echo json_encode(["ok"=>false,"msg"=>"El usuario no tiene asignado el almacén seleccionado."]);
    exit;
  }
}

echo json_encode([
  "ok" => true,
  "msg" => "OK",
  "usuario" => $u['cve_usuario'],
  "nombre" => $u['nombre_completo'] ?: $u['cve_usuario'],
  "almacen" => $almacen
]);
