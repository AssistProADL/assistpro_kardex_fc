<?php
// public/api/vas/servicios.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function jerr($msg, $type='VALIDATION', $detalle=[]) {
  echo json_encode(["ok"=>0,"error"=>$type,"msg"=>$msg,"detalle"=>$detalle], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($data=null, $msg="OK") {
  $out=["ok"=>1,"msg"=>$msg];
  if ($data!==null) $out["data"]=$data;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// parse JSON body for POST/PUT/DELETE
$raw = file_get_contents('php://input');
$body = [];
if ($raw) {
  $tmp = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) $body = $tmp;
}

$IdEmpresa = $_GET['IdEmpresa'] ?? ($body['IdEmpresa'] ?? null);
if (!$IdEmpresa) jerr("Falta IdEmpresa");

if ($method === 'GET') {
  $activo = isset($_GET['Activo']) ? intval($_GET['Activo']) : 1;
  $search = trim($_GET['search'] ?? '');

  $sql = "SELECT id_servicio, IdEmpresa, clave_servicio, nombre, descripcion, tipo_cobro, precio_base, moneda, Activo
          FROM vas_servicio
          WHERE IdEmpresa = :IdEmpresa ";
  $p = ["IdEmpresa"=>$IdEmpresa];

  if ($activo === 0 || $activo === 1) {
    $sql .= " AND Activo = :Activo ";
    $p["Activo"] = $activo;
  }
  if ($search !== '') {
    $sql .= " AND (clave_servicio LIKE :q OR nombre LIKE :q) ";
    $p["q"] = "%$search%";
  }
  $sql .= " ORDER BY nombre ASC";

  $rows = db_all($sql, $p);
  jok($rows);
}

// CREATE
if ($method === 'POST') {
  $clave = strtoupper(trim($body['clave_servicio'] ?? $body['clave'] ?? ''));
  $nombre = trim($body['nombre'] ?? '');
  $desc = trim($body['descripcion'] ?? '');
  $tipo = $body['tipo_cobro'] ?? 'fijo';
  $precio = isset($body['precio_base']) ? floatval($body['precio_base']) : 0;
  $moneda = strtoupper(trim($body['moneda'] ?? 'MXN'));
  $Activo = isset($body['Activo']) ? intval($body['Activo']) : 1;

  if ($clave === '' || $nombre === '') jerr("Clave y nombre son requeridos");
  $validTipos = ['fijo','por_pieza','por_pedido','por_hora'];
  if (!in_array($tipo, $validTipos, true)) jerr("tipo_cobro inválido", "VALIDATION", ["valid"=>$validTipos]);

  // unique check
  $exists = db_val("SELECT COUNT(*) FROM vas_servicio WHERE IdEmpresa=:IdEmpresa AND clave_servicio=:c",
                   ["IdEmpresa"=>$IdEmpresa,"c"=>$clave]);
  if (intval($exists) > 0) jerr("Ya existe la clave_servicio en esta empresa", "CONFLICT");

  $sql = "INSERT INTO vas_servicio (IdEmpresa, clave_servicio, nombre, descripcion, tipo_cobro, precio_base, moneda, Activo, created_by)
          VALUES (:IdEmpresa, :c, :n, :d, :t, :p, :m, :a, :u)";
  dbq($sql, [
    "IdEmpresa"=>$IdEmpresa,
    "c"=>$clave, "n"=>$nombre, "d"=>$desc,
    "t"=>$tipo, "p"=>$precio, "m"=>$moneda,
    "a"=>$Activo,
    "u"=>($_SESSION['usuario'] ?? 'API')
  ]);
  $newId = db_val("SELECT LAST_INSERT_ID()");
  jok(["id_servicio"=>intval($newId)], "Creado");
}

// UPDATE
if ($method === 'PUT') {
  if ($id <= 0) jerr("Falta id (querystring ?id=)", "VALIDATION");

  $row = db_one("SELECT id_servicio FROM vas_servicio WHERE id_servicio=:id AND IdEmpresa=:IdEmpresa",
                ["id"=>$id,"IdEmpresa"=>$IdEmpresa]);
  if (!$row) jerr("Servicio no encontrado", "NOT_FOUND");

  $sets = [];
  $p = ["id"=>$id,"IdEmpresa"=>$IdEmpresa, "u"=>($_SESSION['usuario'] ?? 'API')];

  $map = [
    "clave_servicio"=>"clave_servicio",
    "nombre"=>"nombre",
    "descripcion"=>"descripcion",
    "tipo_cobro"=>"tipo_cobro",
    "precio_base"=>"precio_base",
    "moneda"=>"moneda",
    "Activo"=>"Activo"
  ];

  foreach ($map as $k=>$col) {
    if (array_key_exists($k, $body)) {
      $val = $body[$k];
      if ($k === 'clave_servicio') $val = strtoupper(trim($val));
      if ($k === 'moneda') $val = strtoupper(trim($val));
      if ($k === 'precio_base') $val = floatval($val);
      if ($k === 'Activo') $val = intval($val);
      if ($k === 'tipo_cobro') {
        $validTipos = ['fijo','por_pieza','por_pedido','por_hora'];
        if (!in_array($val, $validTipos, true)) jerr("tipo_cobro inválido", "VALIDATION", ["valid"=>$validTipos]);
      }
      $sets[] = "$col = :$k";
      $p[$k] = $val;
    }
  }

  if (!$sets) jerr("Nada para actualizar", "VALIDATION");

  // unique change check if clave_servicio provided
  if (isset($p['clave_servicio'])) {
    $exists = db_val("SELECT COUNT(*) FROM vas_servicio WHERE IdEmpresa=:IdEmpresa AND clave_servicio=:c AND id_servicio<>:id",
                     ["IdEmpresa"=>$IdEmpresa,"c"=>$p['clave_servicio'],"id"=>$id]);
    if (intval($exists) > 0) jerr("La clave_servicio ya existe", "CONFLICT");
  }

  $sql = "UPDATE vas_servicio SET ".implode(", ", $sets).", updated_at=NOW(), updated_by=:u
          WHERE id_servicio=:id AND IdEmpresa=:IdEmpresa";
  dbq($sql, $p);
  jok(null, "Actualizado");
}

// DELETE (soft)
if ($method === 'DELETE') {
  if ($id <= 0) jerr("Falta id (querystring ?id=)", "VALIDATION");

  $ok = dbq("UPDATE vas_servicio SET Activo=0, updated_at=NOW(), updated_by=:u
             WHERE id_servicio=:id AND IdEmpresa=:IdEmpresa",
            ["id"=>$id,"IdEmpresa"=>$IdEmpresa, "u"=>($_SESSION['usuario'] ?? 'API')]);
  jok(null, "Desactivado");
}

jerr("Método no soportado", "VALIDATION", ["method"=>$method]);
