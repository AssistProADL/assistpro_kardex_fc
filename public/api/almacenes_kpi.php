<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

if(($_GET['action'] ?? '')!=='kpi'){
  echo json_encode(['error'=>'Acción no soportada'], JSON_UNESCAPED_UNICODE);
  exit;
}

$total=(int)db_val("SELECT COUNT(*) FROM c_almacenp");
$activos=(int)db_val("SELECT COUNT(*) FROM c_almacenp WHERE IFNULL(Activo,'1')='1'");
$inactivos=(int)db_val("SELECT COUNT(*) FROM c_almacenp WHERE IFNULL(Activo,'1')='0'");

echo json_encode(['total'=>$total,'activos'=>$activos,'inactivos'=>$inactivos], JSON_UNESCAPED_UNICODE);
