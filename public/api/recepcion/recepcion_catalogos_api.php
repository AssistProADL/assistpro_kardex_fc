<?php
// public/ingresos/recepcion_catalogos_api.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

try {
  $almacen = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';

  // Almacenes (catálogo real que ya usas en AssistPro)
  $almacenes = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    WHERE Activo = 1
    ORDER BY nombre
  ");

  // Proveedores (según diagrama: c_proveedor)
  $proveedores = db_all("
    SELECT ID_Proveedor AS id, cve_proveedor AS clave, Nombre AS nombre
    FROM c_proveedores
    WHERE Activo = 1
    ORDER BY Nombre
  ");

  // Zonas de recepción DEPENDIENTES del almacén
  // En tu diagrama aparece: tubicacionesretencion (zona de recepción / retención)
  // Si tu campo del almacén es cve_almacp o Cve_Almac, aquí lo tratamos como "clave"
  $zonas_recepcion = [];
  if ($almacen !== '') {
    $zonas_recepcion = db_all("
      SELECT id, cve_ubicacion AS clave, desc_ubicacion AS nombre
      FROM tubicacionesretencion
      WHERE Activo = 1
        AND (cve_almacp = :a OR cve_almacp = :a2)
      ORDER BY desc_ubicacion
    ", ['a' => $almacen, 'a2' => $almacen]);
  }

  echo json_encode([
    'ok' => 1,
    'almacenes' => $almacenes,
    'proveedores' => $proveedores,
    'zonas_recepcion' => $zonas_recepcion,
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
