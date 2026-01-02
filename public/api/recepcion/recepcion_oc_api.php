<?php
// public/ingresos/recepcion_oc_api.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

try {
  $almacen   = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
  $proveedor = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
  $q         = isset($_GET['q']) ? trim($_GET['q']) : '';

  // OCs activas: según tu tabla th_aduana, las OC se distinguen por ID_Protocolo='OCN'
  // status: en tus pantallas se ve K/T. Asumimos K = abierta (ajústalo si aplica)
  $where = ["h.Activo = 1", "h.ID_Protocolo = 'OCN'", "(h.status = 'K' OR h.status IS NULL)"];
  $params = [];

  if ($almacen !== '') {
    $where[] = "(h.Cve_Almac = :alm OR h.Cve_Almac = :alm2)";
    $params['alm'] = $almacen;
    $params['alm2'] = $almacen;
  }

  if ($proveedor !== '') {
    $where[] = "h.ID_Proveedor = :prov";
    $params['prov'] = $proveedor;
  }

  if ($q !== '') {
    // Busca por factura, num_pedimento o id/folio
    $where[] = "(h.Factura LIKE :q OR h.num_pedimento LIKE :q OR CAST(h.ID_Aduana AS CHAR) LIKE :q)";
    $params['q'] = "%$q%";
  }

  $sql = "
    SELECT
      h.ID_Aduana        AS id_oc,
      h.num_pedimento    AS num_oc,
      h.Factura          AS factura,
      h.fech_pedimento   AS fecha_oc,
      h.Cve_Almac        AS almacen,
      h.ID_Proveedor     AS id_proveedor,
      p.Nombre           AS proveedor,
      h.status           AS status
    FROM th_aduana h
    LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
    WHERE " . implode(" AND ", $where) . "
    ORDER BY h.ID_Aduana DESC
    LIMIT 500
  ";

  $rows = db_all($sql, $params);

  echo json_encode([
    'ok' => 1,
    'data' => $rows,
    'total' => count($rows)
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
