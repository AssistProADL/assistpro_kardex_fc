<?php
// public/api/sfa/comercial_listas_api.php
// Catálogos para asignación comercial (listas de precio / promociones / descuentos)
// GET ?almacen_id=XX

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php';

try {
  $almacen_id = isset($_GET['almacen_id']) ? (int)$_GET['almacen_id'] : 0;

  // Nota: listas pueden ser globales (Cve_Almac=0 o NULL) o por almacén.
  $lp = db_all(
    "SELECT Id, Clave, Nombre, Activo, Cve_Almac
       FROM listap
      WHERE Activo=1 AND (Cve_Almac IS NULL OR Cve_Almac=0 OR Cve_Almac=:a)
      ORDER BY Nombre",
    [':a'=>$almacen_id]
  );

  $ld = db_all(
    "SELECT Id, Clave, Nombre, Activo, Cve_Almac
       FROM listad
      WHERE Activo=1 AND (Cve_Almac IS NULL OR Cve_Almac=0 OR Cve_Almac=:a)
      ORDER BY Nombre",
    [':a'=>$almacen_id]
  );

  $promo = db_all(
    "SELECT Id, Clave, Nombre, Activo, Cve_Almac
       FROM listapromo
      WHERE Activo=1 AND (Cve_Almac IS NULL OR Cve_Almac=0 OR Cve_Almac=:a)
      ORDER BY Nombre",
    [':a'=>$almacen_id]
  );

  echo json_encode([
    'ok' => 1,
    'almacen_id' => $almacen_id,
    'listas_precio' => $lp,
    'listas_descuento' => $ld,
    'listas_promo' => $promo,
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
