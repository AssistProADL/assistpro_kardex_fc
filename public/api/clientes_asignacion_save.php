<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function jerr($msg, $extra=[]){ echo json_encode(array_merge(['error'=>$msg], $extra)); exit; }
function col_exists(PDO $pdo, string $table, string $col): bool {
  $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
  $st->execute([$table,$col]);
  return (int)$st->fetchColumn() > 0;
}

$raw = file_get_contents('php://input');
if(!$raw) jerr('Body JSON requerido');
$req = json_decode($raw, true);
if(!is_array($req)) jerr('JSON inválido');

$alm = trim((string)($req['almacen'] ?? ($req['IdEmpresa'] ?? '')));
if($alm==='') jerr('Almacén (IdEmpresa) requerido');

$ruta_global = trim((string)($req['ruta_global'] ?? '')); // puede venir vacío si cada fila trae ruta
$dias_global = $req['dias_global'] ?? [];
if(!is_array($dias_global)) $dias_global = [];

$items = $req['items'] ?? [];
if(!is_array($items) || count($items)===0) jerr('items requerido');

$hasDo = col_exists($pdo, 'reldaycli', 'Do');   // domingo
$hasLu = col_exists($pdo, 'reldaycli', 'Lu');
$hasMa = col_exists($pdo, 'reldaycli', 'Ma');
$hasMi = col_exists($pdo, 'reldaycli', 'Mi');
$hasJu = col_exists($pdo, 'reldaycli', 'Ju');
$hasVi = col_exists($pdo, 'reldaycli', 'Vi');
$hasSa = col_exists($pdo, 'reldaycli', 'Sa');

// columnas mínimas esperadas
$hasIdDest = col_exists($pdo, 'reldaycli', 'Id_Destinatario');
$hasCveRuta = col_exists($pdo, 'reldaycli', 'Cve_Ruta');
$hasCveAlm  = col_exists($pdo, 'reldaycli', 'Cve_Almac');

if(!$hasIdDest || !$hasCveRuta || !$hasCveAlm){
  // no matamos la corrida, solo avisamos que no heredamos a reldaycli
  $noDaycli = true;
}else{
  $noDaycli = false;
}

try{
  $pdo->beginTransaction();

  // UPSERT relclirutas: (IdCliente, IdEmpresa) -> IdRuta
  $stFindRel = $pdo->prepare("SELECT Id FROM relclirutas WHERE IdCliente=? AND IdEmpresa=? LIMIT 1");
  $stUpdRel  = $pdo->prepare("UPDATE relclirutas SET IdRuta=?, Fecha=CURDATE() WHERE Id=? LIMIT 1");
  $stInsRel  = $pdo->prepare("INSERT INTO relclirutas (IdCliente, IdRuta, IdEmpresa, Fecha) VALUES (?,?,?,CURDATE())");

  // UPSERT reldaycli: (Id_Destinatario, Cve_Almac) -> Cve_Ruta + días
  $stFindDay = $noDaycli ? null : $pdo->prepare("SELECT 1 FROM reldaycli WHERE Id_Destinatario=? AND Cve_Almac=? LIMIT 1");

  // armamos dinámico SET días existentes (incluye Do si existe)
  $dayCols = [];
  if($hasLu) $dayCols[]='Lu';
  if($hasMa) $dayCols[]='Ma';
  if($hasMi) $dayCols[]='Mi';
  if($hasJu) $dayCols[]='Ju';
  if($hasVi) $dayCols[]='Vi';
  if($hasSa) $dayCols[]='Sa';
  if($hasDo) $dayCols[]='Do';

  // UPDATE reldaycli
  $setDays = [];
  foreach($dayCols as $c) $setDays[] = "$c=:$c";
  $sqlUpdDay = $noDaycli ? null : ("UPDATE reldaycli SET Cve_Ruta=:ruta,".implode(',', $setDays)." WHERE Id_Destinatario=:id AND Cve_Almac=:alm LIMIT 1");

  // INSERT reldaycli
  $colsIns = array_merge(['Id_Destinatario','Cve_Ruta','Cve_Almac'], $dayCols);
  $valsIns = array_merge([':id',':ruta',':alm'], array_map(fn($c)=>":$c", $dayCols));
  $sqlInsDay = $noDaycli ? null : ("INSERT INTO reldaycli (".implode(',',$colsIns).") VALUES (".implode(',',$valsIns).")");

  $stUpdDay = $noDaycli ? null : $pdo->prepare($sqlUpdDay);
  $stInsDay = $noDaycli ? null : $pdo->prepare($sqlInsDay);

  $ok=0; $err=0; $errs=[];

  foreach($items as $it){
    $id = (int)($it['id_destinatario'] ?? 0);
    if($id<=0){ $err++; $errs[]=['id'=>0,'motivo'=>'id_destinatario inválido']; continue; }

    $rutaFila = trim((string)($it['ruta'] ?? ''));
    $ruta = $rutaFila!=='' ? $rutaFila : $ruta_global;
    if($ruta===''){ $err++; $errs[]=['id'=>$id,'motivo'=>'Ruta requerida']; continue; }

    $diasFila = $it['dias'] ?? [];
    if(!is_array($diasFila)) $diasFila = [];
    $dias = count($diasFila)>0 ? $diasFila : $dias_global;

    // normaliza flags días -> 0/1
    $flags = [
      'Lu'=>0,'Ma'=>0,'Mi'=>0,'Ju'=>0,'Vi'=>0,'Sa'=>0,'Do'=>0
    ];
    foreach($dias as $d){
      $d = trim((string)$d);
      if(isset($flags[$d])) $flags[$d]=1;
    }

    // 1) relclirutas
    $stFindRel->execute([$id, $alm]);
    $relId = $stFindRel->fetchColumn();
    if($relId){
      $stUpdRel->execute([(int)$ruta, (int)$relId]);
    }else{
      $stInsRel->execute([$id, (int)$ruta, $alm]);
    }

    // 2) reldaycli (herencia)
    if(!$noDaycli){
      $stFindDay->execute([$id, $alm]);
      $exists = $stFindDay->fetchColumn();

      $bind = [':id'=>$id, ':ruta'=>(int)$ruta, ':alm'=>$alm];
      foreach($dayCols as $c){
        // si no existe columna Do en tabla, jamás entrará aquí
        $bind[":$c"] = (int)$flags[$c];
      }

      if($exists){
        $stUpdDay->execute($bind);
      }else{
        $stInsDay->execute($bind);
      }
    }

    $ok++;
  }

  $pdo->commit();

  echo json_encode([
    'success'=>true,
    'mensaje'=>"Planeación guardada. OK=$ok, ERR=$err".($noDaycli ? " (reldaycli no actualizado: columnas no compatibles)" : ""),
    'ok'=>$ok,
    'err'=>$err,
    'errores'=>$errs
  ]);

}catch(Throwable $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  jerr('Error guardando planeación', ['detalle'=>$e->getMessage()]);
}
