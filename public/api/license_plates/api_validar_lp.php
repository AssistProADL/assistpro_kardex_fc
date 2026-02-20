<?php
// /public/api/license_plates/api_validar_lp.php
header('Content-Type: application/json; charset=utf-8');

function j_ok($data = []) { echo json_encode(["ok"=>true] + $data, JSON_UNESCAPED_UNICODE); exit; }
function j_err($msg, $code=400, $extra=[]) {
  http_response_code($code);
  echo json_encode(["ok"=>false,"error"=>$msg,"extra"=>$extra], JSON_UNESCAPED_UNICODE);
  exit;
}

function load_pdo() {
  $candidates = [
    __DIR__ . '/../../app/db.php',        // /public/api/license_plates -> /public/app/db.php
    __DIR__ . '/../../../app/db.php',
    __DIR__ . '/../../../../app/db.php',
  ];
  foreach ($candidates as $p) {
    if (file_exists($p)) {
      require_once $p;
      if (isset($pdo) && $pdo instanceof PDO) return $pdo;
      if (function_exists('db')) { $x = db(); if ($x instanceof PDO) return $x; }
      if (function_exists('getPDO')) { $x = getPDO(); if ($x instanceof PDO) return $x; }
    }
  }
  j_err("No se pudo cargar conexión DB (pdo) desde app/db.php", 500, ["searched"=>$candidates]);
}
function table_has(PDO $pdo, $table){
  try { $pdo->query("SELECT 1 FROM `$table` LIMIT 1"); return true; }
  catch(Exception $e){ return false; }
}
function cols(PDO $pdo, $table){
  $st = $pdo->prepare("SHOW COLUMNS FROM `$table`");
  $st->execute();
  return array_map(fn($r)=>$r['Field'], $st->fetchAll(PDO::FETCH_ASSOC));
}
function pick_col($columns, $cands){
  $lc = array_map('strtolower',$columns);
  foreach ($cands as $c){
    $idx = array_search(strtolower($c), $lc);
    if ($idx !== false) return $columns[$idx];
  }
  return null;
}

$pdo = load_pdo();

$lp = trim($_GET['lp'] ?? ($_GET['CveLP'] ?? ''));
$almacen = trim($_GET['almacen'] ?? ($_GET['Cve_Almac'] ?? ''));

if ($lp==='') j_err("Parámetro requerido: lp");

if (!table_has($pdo,'c_charolas')) j_err("No existe tabla c_charolas", 500);

$C = cols($pdo,'c_charolas');
$colLP   = pick_col($C, ['cvelp','lp','licenseplate','nTarima','tarima','CveLP']);
$colUtil = pick_col($C, ['utilizado','used','Utilizado']);
$colAct  = pick_col($C, ['activo','estatus','status','Activo','Estatus']);
$colTipo = pick_col($C, ['tipo','Tipo']);                 // opcional
$colAlm  = pick_col($C, ['cve_almac','almacen','Cve_Almac']); // opcional

if (!$colLP || !$colUtil) j_err("c_charolas no tiene columnas LP/Utilizado", 500, ["cols"=>$C]);

$sql = "SELECT
          `$colLP` AS lp,
          `$colUtil` AS utilizado,
          ".($colAct ? "`$colAct` AS activo" : "1 AS activo")."
          ".($colTipo ? ", `$colTipo` AS tipo" : "")."
          ".($colAlm ? ", `$colAlm` AS almacen" : "")."
        FROM c_charolas
        WHERE `$colLP`=? LIMIT 1";
$st = $pdo->prepare($sql);
$st->execute([$lp]);
$r = $st->fetch(PDO::FETCH_ASSOC);

if (!$r) j_err("LP no existe en c_charolas", 404);

$activo = true;
if ($colAct){
  $a = $r['activo'];
  if ($a===0 || $a==='0' || strtoupper((string)$a)==='N') $activo = false;
}
$util = strtoupper(trim((string)$r['utilizado']));
$disponible = ($activo && $util==='N');

j_ok([
  "lp" => $r['lp'],
  "activo" => $activo,
  "utilizado" => $util,
  "disponible" => $disponible,
  "tipo" => $r['tipo'] ?? null,
  "almacen" => $r['almacen'] ?? null,
  "mensaje" => $disponible ? "LP disponible (Utilizado='N')" : ($activo ? "LP NO disponible (Utilizado='S')" : "LP inactivo")
]);
