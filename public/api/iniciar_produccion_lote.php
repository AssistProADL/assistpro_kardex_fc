<?php
// /public/api/iniciar_produccion_lote.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function out(bool $ok, array $extra=[]): void {
  echo json_encode(array_merge(['ok'=>$ok?1:0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);

$items = $data['items'] ?? null;
if(!is_array($items) || count($items)===0){
  out(false, ['error'=>'Sin items. Formato esperado: {items:[{id,idy_ubica_dest},...] }']);
}

$totalOk = 0;
$totalErr = 0;
$errors = [];

try{
  $pdo->beginTransaction();

  $stOt = $pdo->prepare("SELECT id, Status, cve_almac FROM t_ordenprod WHERE id=? FOR UPDATE");
  $stBl = $pdo->prepare("
    SELECT COUNT(*)
    FROM c_ubicacion
    WHERE idy_ubica = ?
      AND cve_almac = ?
      AND AreaProduccion = 'S'
  ");
  $up = $pdo->prepare("
    UPDATE t_ordenprod
    SET
      Status         = 'E',
      Hora_Ini       = NOW(),
      idy_ubica_dest = :bl
    WHERE id = :id
      AND Status = 'P'
  ");

  foreach($items as $it){
    $id = (int)($it['id'] ?? 0);
    $bl = (int)($it['idy_ubica_dest'] ?? 0);

    if($id<=0 || $bl<=0){
      $totalErr++;
      $errors[] = ['id'=>$id,'error'=>'Parámetros inválidos'];
      continue;
    }

    $stOt->execute([$id]);
    $ot = $stOt->fetch(PDO::FETCH_ASSOC);
    if(!$ot){
      $totalErr++;
      $errors[] = ['id'=>$id,'error'=>'OT no encontrada'];
      continue;
    }

    $status = (string)($ot['Status'] ?? '');
    if($status !== 'P'){
      $totalErr++;
      $errors[] = ['id'=>$id,'error'=>"Status no es P (actual={$status})"];
      continue;
    }

    $alm = (int)($ot['cve_almac'] ?? 0);
    $stBl->execute([$bl,$alm]);
    if((int)$stBl->fetchColumn() <= 0){
      $totalErr++;
      $errors[] = ['id'=>$id,'error'=>'BL inválido para el almacén o no es AreaProduccion=S'];
      continue;
    }

    $up->execute([':bl'=>$bl, ':id'=>$id]);
    if($up->rowCount()<=0){
      $totalErr++;
      $errors[] = ['id'=>$id,'error'=>'No se pudo actualizar (concurrencia)'];
      continue;
    }

    $totalOk++;
  }

  $pdo->commit();
  out(true, [
    'total_ok'=>$totalOk,
    'total_err'=>$totalErr,
    'errors'=>$errors
  ]);

}catch(Throwable $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  out(false, ['error'=>$e->getMessage()]);
}
