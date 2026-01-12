<?php
// public/api/sfa/clientes_asignacion_days.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

$out = ['ok'=>0,'data'=>[],'err'=>''];

try{
  $almacen_id = (int)($_GET['almacen_id'] ?? 0);
  $ruta_id    = (int)($_GET['ruta_id'] ?? 0);

  if($almacen_id<=0 || $ruta_id<=0){
    throw new Exception('Parámetros incompletos (almacen_id/ruta_id).');
  }

  $pdo = $GLOBALS['pdo'] ?? ($pdo ?? null);
  if(!$pdo) throw new Exception('Sin conexión PDO ($pdo).');

  $sql = "SELECT Id_Destinatario,
                 Lu,Ma,Mi,Ju,Vi,Sa,Do
          FROM reldaycli
          WHERE Cve_Almac = :a AND Cve_Ruta = :r";

  $st = $pdo->prepare($sql);
  $st->execute([':a'=>$almacen_id, ':r'=>$ruta_id]);

  $map = [];
  while($r = $st->fetch(PDO::FETCH_ASSOC)){
    $d = (int)$r['Id_Destinatario'];
    $map[$d] = [
      'Lu'=>(int)$r['Lu'],'Ma'=>(int)$r['Ma'],'Mi'=>(int)$r['Mi'],
      'Ju'=>(int)$r['Ju'],'Vi'=>(int)$r['Vi'],'Sa'=>(int)$r['Sa'],'Do'=>(int)$r['Do'],
    ];
  }

  $out['ok']=1;
  $out['data']=$map;
  echo json_encode($out);
}catch(Throwable $e){
  $out['ok']=0;
  $out['err']=$e->getMessage();
  echo json_encode($out);
}
