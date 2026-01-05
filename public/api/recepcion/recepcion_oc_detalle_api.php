<?php
// public/ingresos/recepcion_oc_detalle_api.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

try {
  $id_oc = isset($_GET['id_oc']) ? (int)$_GET['id_oc'] : 0;
  if ($id_oc <= 0) {
    echo json_encode(['ok'=>0,'error'=>'Parámetro id_oc requerido'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Líneas de OC: td_aduana
  $rows = db_all("
    SELECT
      d.Id_DetAduana AS id_det,
      d.ID_Aduana    AS id_oc,
      d.cve_articulo AS cve_articulo,
      d.cantidad     AS cantidad,
      d.Cve_Lote     AS cve_lote,
      d.caducidad    AS caducidad,
      d.num_orden    AS num_orden,
      d.Ingresado    AS ingresado,
      d.Activo       AS activo
    FROM td_aduana d
    WHERE d.ID_Aduana = :id
    ORDER BY d.Id_DetAduana
  ", ['id' => $id_oc]);

  echo json_encode(['ok'=>1,'data'=>$rows,'total'=>count($rows)], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
