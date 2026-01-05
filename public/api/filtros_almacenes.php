<?php
// public/api/filtros_almacenes.php  (v2)
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jexit($ok, $msg='', $data=[]){
  echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $action = $_GET['action'] ?? 'almacenes';

  // Filtros
  $cve_cia     = (int)($_GET['cve_cia'] ?? 0);               // c_compania.cve_cia
  $almacenp_id = trim((string)($_GET['almacenp_id'] ?? '')); // c_almacenp.id (TEXT)

  if ($action === 'almacenes') {

    $where = ["COALESCE(ap.Activo,1)=1"];
    $params = [];

    // c_almacenp trae cve_cia como TEXT; blindaje collation para evitar 1267
    if ($cve_cia > 0) {
      $where[] = "TRIM(IFNULL(ap.cve_cia,'')) COLLATE utf8mb4_unicode_ci
                  = CONVERT(:cia USING utf8mb4) COLLATE utf8mb4_unicode_ci";
      $params[':cia'] = (string)$cve_cia;
    }

    // JOIN correcto: a.cve_almacenp (INT) = CAST(ap.id AS UNSIGNED)
    $sql = "
      SELECT DISTINCT
        ap.id    AS almacenp_id,
        ap.clave AS clave,
        ap.nombre AS nombre
      FROM c_almacenp ap
      INNER JOIN c_almacen a
        ON a.cve_almacenp = CAST(ap.id AS UNSIGNED)
      WHERE " . implode(" AND ", $where) . "
      ORDER BY ap.nombre COLLATE utf8mb4_unicode_ci
    ";

    $rows = db_all($sql, $params);
    jexit(true, '', $rows);
  }

  if ($action === 'zonas') {
    if ($almacenp_id === '') jexit(true, '', []);

    $sql = "
      SELECT
        a.cve_almac,
        a.des_almac
      FROM c_almacen a
      WHERE a.cve_almacenp = CAST(:id AS UNSIGNED)
        AND COALESCE(a.Activo,1)=1
      ORDER BY a.des_almac COLLATE utf8mb4_unicode_ci
    ";
    $rows = db_all($sql, [':id' => $almacenp_id]);
    jexit(true, '', $rows);
  }

  jexit(false, 'Acción no soportada: '.$action, []);

} catch (Throwable $e) {
  jexit(false, $e->getMessage(), []);
}
