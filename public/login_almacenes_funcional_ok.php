<?php
if (session_status() === PHP_SESSION_NONE) //@session_start();
  require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$user = trim($_GET['user'] ?? '');
if ($user === '') {
  echo json_encode([]);
  exit;
}

try {
  $rows = db_all("
    SELECT s.cve_almac,
           COALESCE(CONVERT(a.des_almac USING utf8mb4), s.cve_almac) AS des_almac
    FROM (
      SELECT DISTINCT TRIM(t.cve_almac) AS cve_almac
      FROM trel_us_alm t
      WHERE CONVERT(TRIM(t.cve_usuario) USING utf8mb4) = CONVERT(TRIM(:u1) USING utf8mb4)
        AND COALESCE(t.Activo,'1') IN ('1','S','SI','TRUE')
      UNION
      SELECT DISTINCT TRIM(p.cve_almac) AS cve_almac
      FROM t_usu_alm_pre p
      WHERE CONVERT(TRIM(p.id_user) USING utf8mb4) = CONVERT(TRIM(:u2) USING utf8mb4)
    ) s
    LEFT JOIN v_almacen_compat a 
      ON CONVERT(TRIM(a.clave) USING utf8mb4) = CONVERT(s.cve_almac USING utf8mb4)
    ORDER BY des_almac
  ", [':u1' => $user, ':u2' => $user]);

  if (!$rows || !is_array($rows))
    $rows = [];
  echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
