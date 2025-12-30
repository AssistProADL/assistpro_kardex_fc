<?php
// public/api/empresas_api.php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

function jexit($ok, $msg='', $data=[]){
  echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if ($action === 'list') {
    $q = trim((string)($_GET['q'] ?? ''));
    $soloActivas = (int)($_GET['solo_activas'] ?? 1);

    $where = [];
    $params = [];

    if ($soloActivas === 1) {
      $where[] = "IFNULL(Activo,1)=1";
    }

    if ($q !== '') {
      $where[] = "(clave_empresa LIKE :q OR des_cia LIKE :q OR des_rfc LIKE :q OR distrito LIKE :q)";
      $params[':q'] = "%$q%";
    }

    $sql = "SELECT cve_cia, clave_empresa, des_cia, Activo
            FROM c_compania";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY IFNULL(Activo,1) DESC, des_cia ASC LIMIT 2000";

    $rows = db_all($sql, $params);
    jexit(true, '', $rows);
  }

  if ($action === 'get') {
    $id = (int)($_GET['cve_cia'] ?? 0);
    if ($id <= 0) jexit(false, 'cve_cia inválido', null);
    $row = db_one("SELECT * FROM c_compania WHERE cve_cia=:id LIMIT 1", [':id'=>$id]);
    jexit(true, '', $row ?: null);
  }

  jexit(false, 'Acción no soportada: '.$action, []);

} catch (Throwable $e) {
  jexit(false, $e->getMessage(), []);
}
