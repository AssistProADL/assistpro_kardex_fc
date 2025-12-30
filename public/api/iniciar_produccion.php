<?php
// /public/api/iniciar_produccion.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function out(bool $ok, array $extra=[]): void {
  echo json_encode(array_merge(['ok'=>$ok?1:0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$bl = (int)($_POST['idy_ubica_dest'] ?? 0);

if($id <= 0) out(false, ['error'=>'OT inválida']);
if($bl <= 0) out(false, ['error'=>'BL de producción inválido']);

$usr = 'DEMO'; // sin sesión por ahora

try{
  $pdo->beginTransaction();

  // Lock OT
  $st = $pdo->prepare("SELECT id, Status, cve_almac FROM t_ordenprod WHERE id=? FOR UPDATE");
  $st->execute([$id]);
  $ot = $st->fetch(PDO::FETCH_ASSOC);

  if(!$ot){
    $pdo->rollBack();
    out(false, ['error'=>'OT no encontrada']);
  }

  $status = (string)($ot['Status'] ?? '');
  if($status !== 'P'){
    $pdo->rollBack();
    out(false, ['error'=>"La OT no está en status Planeada (P). Status actual: {$status}"]);
  }

  $alm = (int)($ot['cve_almac'] ?? 0);

  // Validar BL por almacén y AreaProduccion='S'
  $vb = $pdo->prepare("
    SELECT COUNT(*)
    FROM c_ubicacion
    WHERE idy_ubica = ?
      AND cve_almac = ?
      AND AreaProduccion = 'S'
  ");
  $vb->execute([$bl, $alm]);
  if((int)$vb->fetchColumn() <= 0){
    $pdo->rollBack();
    out(false, ['error'=>'El BL seleccionado no pertenece al almacén o no es AreaProduccion=S']);
  }

  // Arranque controlado: P -> E
  // En tu esquema real: Usr_Armo existe, no Usr_Cambio
  $up = $pdo->prepare("
    UPDATE t_ordenprod
    SET
      Status         = 'E',
      Hora_Ini       = NOW(),
      idy_ubica_dest = :bl,
      Usr_Armo       = :usr
    WHERE id = :id
      AND Status = 'P'
  ");
  $up->execute([':bl'=>$bl, ':usr'=>$usr, ':id'=>$id]);

  if($up->rowCount() <= 0){
    $pdo->rollBack();
    out(false, ['error'=>'No se pudo iniciar (concurrencia o status cambió).']);
  }

  $pdo->commit();
  out(true, ['id'=>$id, 'idy_ubica_dest'=>$bl, 'status'=>'E']);

}catch(Throwable $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  out(false, ['error'=>$e->getMessage()]);
}
