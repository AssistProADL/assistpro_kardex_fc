<?php
header('Content-Type: application/json; charset=utf-8');

function j_ok($data = []) { echo json_encode(["ok"=>true,"data"=>$data], JSON_UNESCAPED_UNICODE); exit; }
function j_err($msg, $code=400, $extra=[]) { http_response_code($code); echo json_encode(["ok"=>false,"error"=>$msg,"extra"=>$extra], JSON_UNESCAPED_UNICODE); exit; }

function load_pdo() {
  $candidates = [__DIR__ . '/../../app/db.php', __DIR__ . '/../../../app/db.php', __DIR__ . '/../../../../app/db.php'];
  foreach ($candidates as $p) {
    if (file_exists($p)) {
      require_once $p;
      if (isset($pdo) && $pdo instanceof PDO) return $pdo;
      if (function_exists('db') ) { $x = db(); if ($x instanceof PDO) return $x; }
      if (function_exists('getPDO')) { $x = getPDO(); if ($x instanceof PDO) return $x; }
    }
  }
  j_err("No se pudo cargar conexión DB (pdo) desde app/db.php", 500, ["searched"=>$candidates]);
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
$in = json_decode(file_get_contents("php://input"), true) ?: [];

$lp     = trim($in['lp'] ?? ($in['CveLP'] ?? ''));
$motivo = trim($in['motivo'] ?? 'VACIO');

if ($lp === '') j_err("LP requerido");

$table = 'c_charolas';
$C = cols($pdo, $table);

$colLP    = pick_col($C, ['cvelp','CveLP','lp','LP','licenseplate','LicensePlate','nTarima','Tarima']);
$colAct   = pick_col($C, ['activo','Activo','estatus','Estatus','status','Status']);
$colUtil  = pick_col($C, ['utilizado','Utilizado','used','Used']);

if (!$colLP) j_err("No pude mapear columna LP en c_charolas", 500, ["cols"=>$C]);

$pdo->beginTransaction();
try{
  // Preferencia: si existe columna "activo" la ponemos a 0 o 'N'
  if ($colAct) {
    // Detecta tipo por contenido: intentamos 0 primero; si falla, 'N'
    $sql = "UPDATE `$table` SET `$colAct`=0 WHERE `$colLP`=?";
    $st = $pdo->prepare($sql);
    $st->execute([$lp]);
    if ($st->rowCount()===0) {
      $sql = "UPDATE `$table` SET `$colAct`='N' WHERE `$colLP`=?";
      $st = $pdo->prepare($sql);
      $st->execute([$lp]);
    }
  }
  // Marcamos utilizado si existe
  if ($colUtil) {
    $sql = "UPDATE `$table` SET `$colUtil`='S' WHERE `$colLP`=?";
    $st = $pdo->prepare($sql);
    $st->execute([$lp]);
  }

  $pdo->commit();
  j_ok(["lp"=>$lp,"motivo"=>$motivo]);
} catch(Exception $e){
  $pdo->rollBack();
  j_err($e->getMessage(), 409);
}
