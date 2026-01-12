<?php
// public/api/sfa/catalogo_rutas.php
// - GET ?mode=almacenes  => almacenes que tienen rutas
// - GET ?almacen_id=XX   => rutas del almacén

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

try {
  $mode = $_GET['mode'] ?? '';
  $almacen_id = isset($_GET['almacen_id']) ? (int)$_GET['almacen_id'] : 0;

  if ($mode === 'almacenes') {
    $rows = db_all("\n      SELECT DISTINCT a.id, CONCAT('Almacén ', a.id, ' - ', a.nombre) AS nombre\n      FROM c_almacenp a\n      INNER JOIN t_ruta r ON r.cve_almacenp = a.id\n      WHERE IFNULL(r.status,'A') <> 'B'\n      ORDER BY a.nombre\n    ");
    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($almacen_id > 0) {
    $rows = db_all("\n      SELECT r.ID_Ruta AS id_ruta, r.cve_ruta, r.descripcion, r.venta_preventa, r.es_entrega\n      FROM t_ruta r\n      WHERE r.cve_almacenp = ? AND IFNULL(r.status,'A') <> 'B'\n      ORDER BY r.cve_ruta\n    ", [$almacen_id]);
    echo json_encode(['success' => true, 'almacen_id' => $almacen_id, 'data' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(['success' => false, 'error' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Error servidor', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
