<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Limpia cualquier salida previa (echo, BOM, true, etc.)
while (ob_get_level() > 0) { ob_end_clean(); }
ob_start();

/* ===== Encuentra app/db.php sin depender de ../ ===== */
function findDb(string $dir): string {
  for ($i=0; $i<8; $i++) {
    $f = $dir . '/app/db.php';
    if (is_file($f)) return $f;
    $dir = dirname($dir);
  }
  throw new RuntimeException('app/db.php no encontrado');
}

try {

  require_once findDb(__DIR__);

  if (!function_exists('db_pdo')) {
    throw new RuntimeException('db_pdo() no existe');
  }

  $pdo = db_pdo();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $sql = "
    SELECT
      t.id_zona_almac                                  AS zona,
      uo.CodigoCSD                                    AS bl_origen,
      t.Cve_Articulo                                  AS clave,
      a.Descripcion                                   AS descripcion,
      t.Lote                                          AS lote,
      DATE_FORMAT(t.Fecha_Caducidad,'%Y-%m-%d')       AS caducidad,
      t.Cantidad                                      AS cantidad,
      ud.CodigoCSD                                    AS bl_destino,
      DATE_FORMAT(t.Hora_Ini,'%Y-%m-%d %H:%i:%s')     AS inicio,
      DATE_FORMAT(t.Hora_Fin,'%Y-%m-%d %H:%i:%s')     AS fin,
      CASE
        WHEN t.Status='P' THEN 0
        WHEN t.Status='E' THEN 90
        WHEN t.Status='T' THEN 100
        ELSE 0
      END                                             AS avance
    FROM t_ordenprod t
    LEFT JOIN c_articulo  a  ON a.Cve_Articulo = t.Cve_Articulo
    LEFT JOIN c_ubicacion uo ON uo.idy_ubica  = t.idy_ubica
    LEFT JOIN c_ubicacion ud ON ud.idy_ubica  = t.idy_ubica_dest
    ORDER BY COALESCE(t.Hora_Ini, t.FechaReg) DESC
    LIMIT 500
  ";

  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  ob_clean();
  echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

  ob_clean();
  echo json_encode([
    'data' => [],
    'error' => true,
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}

exit;
