<?php
// public/api/vas/catalogos_owners.php
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

$search = trim($_GET['search'] ?? '');
$Activo = isset($_GET['Activo']) ? intval($_GET['Activo']) : 1;

$sql = "SELECT ID_Proveedor, cve_proveedor, Empresa, Nombre, Activo
        FROM c_proveedores
        WHERE es_cliente = 1 ";
$p = [];

if ($Activo === 0 || $Activo === 1) {
  $sql .= " AND Activo = :a ";
  $p["a"] = $Activo;
}
if ($search !== '') {
  $sql .= " AND (Nombre LIKE :q OR Empresa LIKE :q OR cve_proveedor LIKE :q) ";
  $p["q"] = "%$search%";
}
$sql .= " ORDER BY Nombre ASC LIMIT 500";

$rows = db_all($sql, $p);
jok($rows);
