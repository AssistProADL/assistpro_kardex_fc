<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . '/../app/auth_check.php';
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$user = trim($_GET['user'] ?? '');
$alm = trim($_GET['alm'] ?? '');
if ($user === '' || $alm === '') {
  echo json_encode([]);
  exit;
}

/*
  Empresas / Proveedores con Cliente = 1, filtradas por almacén seleccionado.
  Catálogo tomado de c_proveedores. Si no existe relación directa por almacén en esa tabla,
  puedes ampliar aquí el filtro cruzando con tus tablas de entradas/saldos para “empresas que operan en ese almacén”.
*/
$sql = "
SELECT DISTINCT
  TRIM(Cve_Proveedor) AS cve_proveedor,
  Des_Proveedor       AS des_proveedor
FROM c_proveedores
WHERE COALESCE(Cliente,0) = 1
ORDER BY Des_Proveedor;
";

try {
  $rows = db_all($sql) ?? [];
  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
