<?php
// /public/api/pick_to_lp/api_pick_to_lp_ejecutar.php
header('Content-Type: application/json; charset=utf-8');

function j_ok($data = []) { echo json_encode(["ok"=>true] + $data, JSON_UNESCAPED_UNICODE); exit; }
function j_err($msg, $code=400, $extra=[]) {
  http_response_code($code);
  echo json_encode(["ok"=>false,"error"=>$msg,"extra"=>$extra], JSON_UNESCAPED_UNICODE);
  exit;
}

function load_pdo() {
  $candidates = [
    __DIR__ . '/../../app/db.php',        // /public/api/pick_to_lp -> /public/app/db.php
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
function map_exist_table(PDO $pdo, $table){
  $C = cols($pdo, $table);
  return [
    "cols"=>$C,
    "bl" => pick_col($C, ['bl','ubicacion','cve_ubicacion','cveubicacion','Cve_Ubicacion']),
    "lp" => pick_col($C, ['cvelp','lp','licenseplate','nTarima','tarima','contenedor']),
    "art"=> pick_col($C, ['cve_articulo','articulo','sku','clave','cveproducto']),
    "qty"=> pick_col($C, ['existencia','total','cantidad','qty']),
    "alm"=> pick_col($C, ['cve_almac','almacen','Cve_Almac']),
  ];
}

function consume(PDO $pdo, $table, $m, $where, $args, $qtyNeed){
  $sqlSel = "SELECT `{$m['qty']}` AS qty FROM `$table` WHERE ".implode(" AND ", $where)." FOR UPDATE";
  $st = $pdo->prepare($sqlSel);
  $st->execute($args);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception("No hay existencia para consumir en $table.");

  $qty = (float)$row['qty'];
  if ($qty < $qtyNeed) throw new Exception("Existencia insuficiente en $table. Disponible: $qty, requerido: $qtyNeed");

  $new = $qty - $qtyNeed;

  if ($new <= 0.0000001) {
    $sqlDel = "DELETE FROM `$table` WHERE ".implode(" AND ",$where);
    $st = $pdo->prepare($sqlDel);
    $st->execute($args);
  } else {
    $sqlUpd = "UPDATE `$table` SET `{$m['qty']}`=? WHERE ".implode(" AND ",$where);
    $st = $pdo->prepare($sqlUpd);
    $st->execute([$new, ...$args]);
  }
  return $new;
}

function upsert_add(PDO $pdo, $table, $m, $keyWhere, $keyArgs, $ins){
  $sqlSel = "SELECT `{$m['qty']}` AS qty FROM `$table` WHERE ".implode(" AND ", $keyWhere)." FOR UPDATE";
  $st = $pdo->prepare($sqlSel);
  $st->execute($keyArgs);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $new = (float)$row['qty'] + (float)$ins['cantidad'];
    $sqlUpd = "UPDATE `$table` SET `{$m['qty']}`=? WHERE ".implode(" AND ",$keyWhere);
    $st = $pdo->prepare($sqlUpd);
    $st->execute([$new, ...$keyArgs]);
    return $new;
  }

  $insCols=[]; $insVals=[];
  if (!empty($ins['bl']) && $m['bl']) { $insCols[]=$m['bl']; $insVals[]=$ins['bl']; }
  if (!empty($ins['lp']) && $m['lp']) { $insCols[]=$m['lp']; $insVals[]=$ins['lp']; }
  if (!empty($ins['art']) && $m['art']) { $insCols[]=$m['art']; $insVals[]=$ins['art']; }
  if (!empty($ins['alm']) && $m['alm']) { $insCols[]=$m['alm']; $insVals[]=$ins['alm']; }
  $insCols[]=$m['qty']; $insVals[]=(float)$ins['cantidad'];

  $place = implode(",", array_fill(0, count($insCols), "?"));
  $sqlIns = "INSERT INTO `$table` (`".implode("`,`",$insCols)."`) VALUES ($place)";
  $st = $pdo->prepare($sqlIns);
  $st->execute($insVals);
  return (float)$ins['cantidad'];
}

/**
 * VALIDACIÓN LP DESTINO (REGLA NUEVA):
 * - existe en c_charolas
 * - activo
 * - Utilizado = 'N'
 */
function lp_destino_disponible(PDO $pdo, $lp, $almacen=''){
  if (!table_has($pdo,'c_charolas')) return ["ok"=>false,"msg"=>"No existe c_charolas"];

  $C = cols($pdo,'c_charolas');
  $colLP   = pick_col($C, ['cvelp','lp','licenseplate','nTarima','tarima','CveLP']);
  $colUtil = pick_col($C, ['utilizado','used','Utilizado']);
  $colAct  = pick_col($C, ['activo','estatus','status','Activo','Estatus']);

  $colAlm  = pick_col($C, ['cve_almac','almacen','Cve_Almac']); // opcional

  if (!$colLP || !$colUtil) return ["ok"=>false,"msg"=>"c_charolas sin columnas LP/Utilizado"];

  $sql = "SELECT `$colUtil` AS u, ".($colAct ? "`$colAct` AS a" : "1 AS a").($colAlm ? ", `$colAlm` AS alm" : "")."
          FROM c_charolas WHERE `$colLP`=? LIMIT 1";
  $st=$pdo->prepare($sql); $st->execute([$lp]);
  $r=$st->fetch(PDO::FETCH_ASSOC);
  if(!$r) return ["ok"=>false,"msg"=>"LP no existe en c_charolas"];

  // activo: 1/0 o S/N
  if ($colAct){
    $a = $r['a'];
    if ($a===0 || $a==='0' || strtoupper((string)$a)==='N') {
      return ["ok"=>false,"msg"=>"LP está inactivo"];
    }
  }

  // si c_charolas tiene almacén, opcionalmente validamos
  if ($colAlm && $almacen!==''){
    $almDb = (string)($r['alm'] ?? '');
    if ($almDb !== '' && strtoupper($almDb) !== strtoupper($almacen)) {
      // no bloqueamos por completo si tu negocio permite LP global; si sí quieres bloquear: return ok false
      // aquí lo dejamos como warning no bloqueante:
      // return ["ok"=>false,"msg"=>"LP pertenece a otro almacén ($almDb)"];
    }
  }

  $u = strtoupper(trim((string)$r['u']));
  if ($u !== 'N') return ["ok"=>false,"msg"=>"LP ya fue utilizado (Utilizado='S')"];

  return ["ok"=>true,"msg"=>"LP disponible"];
}

function marcar_lp_utilizado(PDO $pdo, $lp){
  if (!table_has($pdo,'c_charolas')) return;

  $C = cols($pdo,'c_charolas');
  $colLP   = pick_col($C, ['cvelp','lp','licenseplate','nTarima','tarima','CveLP']);
  $colUtil = pick_col($C, ['utilizado','used','Utilizado']);
  if (!$colLP || !$colUtil) return;

  $st = $pdo->prepare("UPDATE c_charolas SET `$colUtil`='S' WHERE `$colLP`=?");
  $st->execute([$lp]);
}

function inactivate_lp(PDO $pdo, $lp){
  if (!table_has($pdo,'c_charolas')) return;
  $C = cols($pdo,'c_charolas');
  $colLP  = pick_col($C, ['cvelp','lp','licenseplate','nTarima','tarima','CveLP']);
  $colAct = pick_col($C, ['activo','estatus','status','Activo','Estatus']);
  if (!$colLP || !$colAct) return;

  // intentamos 0, si no, N
  $st=$pdo->prepare("UPDATE c_charolas SET `$colAct`=0 WHERE `$colLP`=?");
  $st->execute([$lp]);
  if ($st->rowCount()===0){
    $st=$pdo->prepare("UPDATE c_charolas SET `$colAct`='N' WHERE `$colLP`=?");
    $st->execute([$lp]);
  }
}

/**
 * Kardex: inserta un registro por movimiento.
 * (Se mantiene heurístico por si cambia el nombre de columnas.)
 */
function kardex(PDO $pdo, $tipo_mov, $articulo, $cantidad, $bl_o, $bl_d, $lp_o, $lp_d, $usuario, $almacen){
  if (!table_has($pdo,'t_cardex')) return;

  $C = cols($pdo,'t_cardex');
  $colArt = pick_col($C, ['cve_articulo','articulo','sku','clave','Cve_Articulo']);
  $colFec = pick_col($C, ['fecha','created_at','Fecha']);
  $colOri = pick_col($C, ['origen','Origen']);
  $colDes = pick_col($C, ['destino','Destino']);
  $colCan = pick_col($C, ['cantidad','qty','Cantidad']);
  $colTip = pick_col($C, ['id_tipomovimiento','id_TipoMovimiento','tipo_movimiento','TipoMovimiento']);
  $colUsr = pick_col($C, ['cve_usuario','usuario','Usuario','Cve_Usuario']);
  $colAlm = pick_col($C, ['cve_almac','almacen','Cve_Almac']);
  $colAju = pick_col($C, ['ajuste','Ajuste']);
  $colLot = pick_col($C, ['cve_lote','lote','Lote','Cve_Lote']);

  if (!$colArt || !$colCan) return;

  $origenTxt  = trim($bl_o . ($lp_o ? " | $lp_o" : ""));
  $destinoTxt = trim($bl_d . ($lp_d ? " | $lp_d" : ""));

  $insCols = [$colArt,$colCan];
  $insVals = [$articulo,(float)$cantidad];

  if ($colFec) { $insCols[]=$colFec; $insVals[]=date('Y-m-d H:i:s'); }
  if ($colOri) { $insCols[]=$colOri; $insVals[]=$origenTxt; }
  if ($colDes) { $insCols[]=$colDes; $insVals[]=$destinoTxt; }
  if ($colUsr && $usuario!=='') { $insCols[]=$colUsr; $insVals[]=$usuario; }
  if ($colAlm && $almacen!=='') { $insCols[]=$colAlm; $insVals[]=$almacen; }
  if ($colAju) { $insCols[]=$colAju; $insVals[]=0; }
  if ($colLot) { $insCols[]=$colLot; $insVals[]=''; }

  if ($colTip) {
    // intenta numeric (ej 9001); si falla usa texto
    $tmpCols=$insCols; $tmpVals=$insVals;
    $tmpCols[]=$colTip; $tmpVals[]=9001;
    $place = implode(",", array_fill(0, count($tmpCols), "?"));
    try{
      $st=$pdo->prepare("INSERT INTO t_cardex (`".implode("`,`",$tmpCols)."`) VALUES ($place)");
      $st->execute($tmpVals);
      return;
    }catch(Exception $e){
      $insCols[]=$colTip; $insVals[]=$tipo_mov;
    }
  }

  $place = implode(",", array_fill(0, count($insCols), "?"));
  $st=$pdo->prepare("INSERT INTO t_cardex (`".implode("`,`",$insCols)."`) VALUES ($place)");
  $st->execute($insVals);
}

// ---------------- MAIN ----------------
$pdo = load_pdo();
$in = json_decode(file_get_contents("php://input"), true) ?: [];

$lp_destino = trim($in['lp_destino'] ?? '');
$bl_destino = trim($in['bl_destino'] ?? '');
$movs       = $in['movimientos'] ?? [];

$almacen    = trim($in['almacen'] ?? ($in['Cve_Almac'] ?? ''));
$usuario    = trim($in['usuario'] ?? ($in['cve_usuario'] ?? ''));

if ($lp_destino==='' || $bl_destino==='' || !is_array($movs) || count($movs)===0){
  j_err("Datos incompletos (lp_destino, bl_destino, movimientos)");
}

// Validación LP destino SOLO por Utilizado='N' (y activo)
$val = lp_destino_disponible($pdo, $lp_destino, $almacen);
if (!$val['ok']) j_err("LP destino inválido: ".$val['msg']);

// Map existencias (se usan para afectar inventario)
if (!table_has($pdo,'ts_existenciapiezas')) j_err("Falta tabla ts_existenciapiezas", 500);
if (!table_has($pdo,'ts_existenciacajas'))  j_err("Falta tabla ts_existenciacajas", 500);

$mp = map_exist_table($pdo,'ts_existenciapiezas');
$mc = map_exist_table($pdo,'ts_existenciacajas');

if (!$mp['bl'] || !$mp['art'] || !$mp['qty']) j_err("No pude mapear ts_existenciapiezas", 500, $mp);
if (!$mc['lp'] || !$mc['art'] || !$mc['qty']) j_err("No pude mapear ts_existenciacajas", 500, $mc);

// Transacción
$pdo->beginTransaction();
try{
  $totalMov = 0;
  $totalQty = 0.0;

  foreach($movs as $mv){
    $tipo     = strtoupper(trim($mv['tipo'] ?? 'PIEZA')); // PIEZA | CAJA
    $articulo = trim($mv['articulo'] ?? '');
    $cantidad = (float)($mv['cantidad'] ?? 0);
    $bl_o     = trim($mv['bl_origen'] ?? '');
    $lp_o     = trim($mv['lp_origen'] ?? '');

    if ($articulo==='' || $cantidad<=0) throw new Exception("Movimiento inválido: articulo/cantidad.");
    if ($tipo==='PIEZA' && $bl_o==='') throw new Exception("Movimiento PIEZA requiere bl_origen.");

    if ($tipo === 'PIEZA'){
      // Consume PIEZAS origen
      $where = ["`{$mp['bl']}`=?","`{$mp['art']}`=?"];
      $args  = [$bl_o, $articulo];
      if ($mp['lp'] && $lp_o!==''){ $where[]="`{$mp['lp']}`=?"; $args[]=$lp_o; }
      if ($mp['alm'] && $almacen!==''){ $where[]="`{$mp['alm']}`=?"; $args[]=$almacen; }

      consume($pdo,'ts_existenciapiezas',$mp,$where,$args,$cantidad);

      // Ingresa PIEZAS destino: BL destino + LP destino (LP en columna si existe)
      $keyWhere=["`{$mp['bl']}`=?","`{$mp['art']}`=?"];
      $keyArgs =[$bl_destino,$articulo];
      if ($mp['lp']) { $keyWhere[]="`{$mp['lp']}`=?"; $keyArgs[]=$lp_destino; }
      if ($mp['alm'] && $almacen!==''){ $keyWhere[]="`{$mp['alm']}`=?"; $keyArgs[]=$almacen; }

      upsert_add($pdo,'ts_existenciapiezas',$mp,$keyWhere,$keyArgs,[
        "bl"=>$bl_destino,"lp"=>$lp_destino,"art"=>$articulo,"alm"=>$almacen,"cantidad"=>$cantidad
      ]);

      // Kardex
      kardex($pdo,'PICK_TO_LP',$articulo,$cantidad,$bl_o,$bl_destino,$lp_o,$lp_destino,$usuario,$almacen);

    } else { // CAJA
      if ($lp_o==='') throw new Exception("Movimiento CAJA requiere lp_origen.");

      // Consume CAJAS origen por LP
      $where = ["`{$mc['lp']}`=?","`{$mc['art']}`=?"];
      $args  = [$lp_o,$articulo];
      if ($mc['alm'] && $almacen!==''){ $where[]="`{$mc['alm']}`=?"; $args[]=$almacen; }

      $new = consume($pdo,'ts_existenciacajas',$mc,$where,$args,$cantidad);

      // Ingresa CAJAS destino: LP destino (+ BL si existe)
      $keyWhere=["`{$mc['lp']}`=?","`{$mc['art']}`=?"];
      $keyArgs =[$lp_destino,$articulo];
      if ($mc['bl']) { $keyWhere[]="`{$mc['bl']}`=?"; $keyArgs[]=$bl_destino; }
      if ($mc['alm'] && $almacen!==''){ $keyWhere[]="`{$mc['alm']}`=?"; $keyArgs[]=$almacen; }

      upsert_add($pdo,'ts_existenciacajas',$mc,$keyWhere,$keyArgs,[
        "bl"=>$bl_destino,"lp"=>$lp_destino,"art"=>$articulo,"alm"=>$almacen,"cantidad"=>$cantidad
      ]);

      // Si contenedor quedó vacío -> inactivar LP origen (regla)
      if ($new <= 0.0000001){
        inactivate_lp($pdo, $lp_o);
      }

      // Kardex
      kardex($pdo,'PICK_TO_LP',$articulo,$cantidad,$bl_o,$bl_destino,$lp_o,$lp_destino,$usuario,$almacen);
    }

    $totalMov++;
    $totalQty += $cantidad;
  }

  // Marca LP destino como UTILIZADO = 'S' (regla nueva)
  marcar_lp_utilizado($pdo, $lp_destino);

  $pdo->commit();

  j_ok([
    "mensaje"=>"Pick to LP ejecutado correctamente",
    "lp_destino"=>$lp_destino,
    "bl_destino"=>$bl_destino,
    "movimientos"=>$totalMov,
    "cantidad_total"=>$totalQty
  ]);

} catch(Exception $e){
  $pdo->rollBack();
  j_err("Pick to LP falló: ".$e->getMessage(), 409);
}
