<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
  echo json_encode(['error'=>'JSON inválido']);
  exit;
}

$almacen = (int)$data['almacen'];
$rutaGlobal = (int)$data['ruta_global'];
$diasGlobal = $data['dias_global'] ?? [];
$items = $data['items'] ?? [];

try {
  $pdo->beginTransaction();

  foreach ($items as $i) {

    $dest = (int)$i['id_destinatario'];
    $ruta = (int)($i['ruta'] ?: $rutaGlobal);
    if (!$dest || !$ruta) continue;

    $dias = $i['dias'] ?: $diasGlobal;
    $flags = [];
    foreach (['Lu','Ma','Mi','Ju','Vi','Sa','Do'] as $d) {
      $flags[$d] = in_array($d, $dias) ? 1 : 0;
    }

    $sql = "
    INSERT INTO reldaycli
    (Cve_Almac, Cve_Ruta, Id_Destinatario,
     Lu, Ma, Mi, Ju, Vi, Sa, Do)
    VALUES
    (:alm, :ruta, :dest,
     :Lu, :Ma, :Mi, :Ju, :Vi, :Sa, :Do)
    ON DUPLICATE KEY UPDATE
     Lu=:Lu, Ma=:Ma, Mi=:Mi, Ju=:Ju, Vi=:Vi, Sa=:Sa, Do=:Do
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([
      ':alm'=>$almacen,
      ':ruta'=>$ruta,
      ':dest'=>$dest
    ], $flags));
  }

  $pdo->commit();
  echo json_encode(['ok'=>true,'mensaje'=>'Planeación guardada correctamente']);

} catch (Exception $e) {
  $pdo->rollBack();
  echo json_encode(['error'=>$e->getMessage()]);
}
