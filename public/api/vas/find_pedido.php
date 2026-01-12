<?php
// public/api/vas/find_pedido.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function jerr($msg, $type='VALIDATION', $detalle=[]) {
  echo json_encode(["ok"=>0,"error"=>$type,"msg"=>$msg,"detalle"=>$detalle], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($data=null, $msg="OK") {
  $out=["ok"=>1,"msg"=>$msg];
  if ($data!==null) $out["data"]=$data;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

$folio = trim($_GET['Fol_folio'] ?? '');
if($folio==='') jerr("Falta Fol_folio");

$row = db_one("SELECT id_pedido, Fol_folio, Fec_Pedido, Cve_clte, cve_almac, Id_Proveedor
               FROM th_pedido
               WHERE Fol_folio=:f
               LIMIT 1", ["f"=>$folio]);

if(!$row) jerr("Pedido no encontrado", "NOT_FOUND");
jok($row);
