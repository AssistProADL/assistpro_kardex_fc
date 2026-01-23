<?php
// public/api/vas/catalogos_owners.php
// Acciones:
// - action=empresas : catálogo de compañías (IdEmpresa = cve_cia)
// - action=owners   : catálogo de owners/clientes con movimiento VAS (desde vw_vas_pendiente_cobro)
// Compat: si no viene action, responde lista simple (owners) para no romper usos anteriores.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function jerr($msg, $detalle=null, $http=200) {
  http_response_code($http);
  echo json_encode(["ok"=>0,"msg"=>$msg,"detalle"=>$detalle], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($data=null, $msg="OK") {
  $out=["ok"=>1,"msg"=>$msg];
  if ($data !== null) $out["data"] = $data;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

$action = trim($_GET['action'] ?? '');
$search = trim($_GET['search'] ?? '');
$Activo = isset($_GET['Activo']) ? intval($_GET['Activo']) : 1;

try {
  if ($action === 'empresas') {
    // c_compania: cve_cia, clave_empresa, des_cia, activo (en tu UI el id 1 existe)
    $sql = "SELECT cve_cia AS IdEmpresa,
                   IFNULL(NULLIF(des_cia,''), IFNULL(NULLIF(clave_empresa,''), cve_cia)) AS nombre,
                   clave_empresa,
                   des_cia,
                   activo
            FROM c_compania
            WHERE 1=1";
    $p = [];
    if ($Activo === 0 || $Activo === 1) {
      $sql .= " AND activo = :a";
      $p["a"] = $Activo;
    }
    if ($search !== '') {
      $sql .= " AND (des_cia LIKE :q OR clave_empresa LIKE :q OR cve_cia LIKE :q)";
      $p["q"] = "%$search%";
    }
    $sql .= " ORDER BY des_cia ASC LIMIT 500";
    $rows = db_all($sql, $p);
    jok($rows);
  }

  // owners: DISTINCT desde vista operativa (evita dependencias de columnas en catálogos legacy)
  if ($action === 'owners' || $action === '') {
    $IdEmpresa = $_GET['IdEmpresa'] ?? $_GET['idEmpresa'] ?? null;
    if ($IdEmpresa === null || $IdEmpresa === '') {
      jerr("Falta IdEmpresa/idEmpresa");
    }

    $sql = "SELECT DISTINCT id_cliente AS owner_id,
                           cliente   AS owner_nombre
            FROM vw_vas_pendiente_cobro
            WHERE IdEmpresa = :e";
    $p = ["e" => $IdEmpresa];

    if ($search !== '') {
      $sql .= " AND (cliente LIKE :q OR id_cliente LIKE :q)";
      $p["q"] = "%$search%";
    }
    $sql .= " ORDER BY owner_nombre ASC LIMIT 500";
    $rows = db_all($sql, $p);
    jok($rows);
  }

  jerr("Acción no soportada: $action");
} catch (Throwable $ex) {
  jerr("Error en API", $ex->getMessage(), 200);
}
