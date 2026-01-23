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
function table_has(PDO $pdo, $table){
  try { $pdo->query("SELECT 1 FROM `$table` LIMIT 1"); return true; }
  catch(Exception $e){ return false; }
}

$pdo = load_pdo();
$in = json_decode(file_get_contents("php://input"), true) ?: [];

$bl_destino = trim($in['bl_destino'] ?? '');
$lp_destino = trim($in['lp_destino'] ?? '');
$articulo   = trim($in['articulo'] ?? '');
$cantidad   = (float)($in['cantidad'] ?? 0);
$almacen    = trim($in['almacen'] ?? ($in['Cve_Almac'] ?? ''));
$referencia = trim($in['referencia'] ?? '');

if ($bl_destino === '' || $articulo === '' || $cantidad <= 0) j_err("Datos incompletos (bl_destino, articulo, cantidad)");

$table = 'ts_existenciapiezas';
if (!table_has($pdo, $table)) j_err("No existe tabla $table", 500);

$C = cols($pdo, $table);
$colBL   = pick_col($C, ['bl','BL','ubicacion','Cve_Ubicacion','cve_ubicacion','Destino','destino']);
$colLP   = pick_col($C, ['cvelp','CveLP','lp','LP','licenseplate','LicensePlate','nTarima','Tarima']);
$colArt  = pick_col($C, ['cve_articulo','Cve_Articulo','articulo','Articulo','clave','Clave','sku','SKU']);
$colQty  = pick_col($C, ['existencia','Existencia','total','Total','cantidad','Cantidad','qty','Qty']);
$colAlm  = pick_col($C, ['cve_almac','Cve_Almac','almacen','Almacen']);

if (!$colBL || !$colArt || !$colQty) j_err("No pude mapear columnas en $table", 500, ["cols"=>$C]);

$where = ["`$colBL`=?","`$colArt`=?"];
$args  = [$bl_destino, $articulo];

if ($colLP && $lp_destino!=='') { $where[]="`$colLP`=?"; $args[]=$lp_destino; }
if ($colAlm && $almacen!=='') { $where[]="`$colAlm`=?"; $args[]=$almacen; }

$pdo->beginTransaction();
try {
  $sqlSel = "SELECT `$colQty` AS qty FROM `$table` WHERE ".implode(" AND ",$where)." FOR UPDATE";
  $st = $pdo->prepare($sqlSel);
  $st->execute($args);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $new = (float)$row['qty'] + $cantidad;
    $sqlUpd = "UPDATE `$table` SET `$colQty`=? WHERE ".implode(" AND ",$where);
    $st = $pdo->prepare($sqlUpd);
    $st->execute([$new, ...$args]);
  } else {
    // Insert mínimo con columnas disponibles
    $insCols = [$colBL, $colArt, $colQty];
    $insVals = [$bl_destino, $articulo, $cantidad];

    if ($colLP && $lp_destino!=='') { $insCols[]=$colLP; $insVals[]=$lp_destino; }
    if ($colAlm && $almacen!=='') { $insCols[]=$colAlm; $insVals[]=$almacen; }

    $place = implode(",", array_fill(0, count($insCols), "?"));
    $sqlIns = "INSERT INTO `$table` (`".implode("`,`",$insCols)."`) VALUES ($place)";
    $st = $pdo->prepare($sqlIns);
    $st->execute($insVals);
    $new = $cantidad;
  }

  $pdo->commit();
  j_ok(["existencia_final"=>$new, "referencia"=>$referencia]);
} catch(Exception $e){
  $pdo->rollBack();
  j_err($e->getMessage(), 409);
}
