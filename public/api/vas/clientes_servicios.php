<?php
// public/api/vas/clientes_servicios.php
require_once __DIR__ . '/../../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok($data = [], $msg = '') {
  echo json_encode(['ok' => true, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}
function json_fail($msg = 'Error', $details = null, $code = 400) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'msg' => $msg, 'details' => $details], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $method = $_SERVER['REQUEST_METHOD'];

  // ==========================
  // GET
  // ==========================
  if ($method === 'GET') {

    // NUEVO: GRID para vw_vas_servicios_cliente
    if (isset($_GET['action']) && $_GET['action'] === 'grid') {
      $IdEmpresa = isset($_GET['IdEmpresa']) ? (int)$_GET['IdEmpresa'] : 0;
      $cve_almac = isset($_GET['cve_almac']) ? trim($_GET['cve_almac']) : '';
      $q = isset($_GET['q']) ? trim($_GET['q']) : '';

      if ($IdEmpresa <= 0) json_fail('Falta IdEmpresa');

      $where = " WHERE IdEmpresa = ? ";
      $params = [$IdEmpresa];

      if ($cve_almac !== '' && strtolower($cve_almac) !== 'todos') {
        $where .= " AND (cve_almac = ? OR cve_almac IS NULL) ";
        $params[] = $cve_almac;
      }

      if ($q !== '') {
        $where .= " AND (cliente LIKE ? OR servicio LIKE ? OR clave_servicio LIKE ? OR Cve_Clte LIKE ?) ";
        $like = "%$q%";
        array_push($params, $like, $like, $like, $like);
      }

      // vw_vas_servicios_cliente ya trae todo lo necesario
      $sql = "SELECT 
                IdEmpresa, cve_almac, id_cliente, Cve_Clte, RazonSocial, RazonComercial,
                id_servicio, clave_servicio, servicio, tipo_cobro,
                precio_final, precio_cliente, precio_base, moneda, Activo
              FROM vw_vas_servicios_cliente
              $where
              ORDER BY RazonSocial, servicio";

      $rows = db_all($sql, $params);

      // KPI rápidos
      $total = count($rows);
      $activos = 0;
      foreach ($rows as $r) { if ((int)($r['Activo'] ?? 0) === 1) $activos++; }

      json_ok([
        'rows' => $rows,
        'kpi'  => ['total' => $total, 'activos' => $activos]
      ]);
    }

    // ==========================
    // (LO EXISTENTE) Lista por cliente
    // ==========================
    $IdEmpresa = isset($_GET['IdEmpresa']) ? (int)$_GET['IdEmpresa'] : 0;
    if ($IdEmpresa <= 0) json_fail('Falta IdEmpresa');

    $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
    $owner_id   = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : 0;
    $cve_almac  = isset($_GET['cve_almac']) ? trim($_GET['cve_almac']) : '';

    $idFinal = $id_cliente > 0 ? $id_cliente : $owner_id;
    if ($idFinal <= 0) json_fail('Falta id_cliente u owner_id');

    $params = [$IdEmpresa, $idFinal];
    $whereAlmac = "";
    if ($cve_almac !== '' && strtolower($cve_almac) !== 'todos') {
      $whereAlmac = " AND (cve_almac = ? OR cve_almac IS NULL) ";
      $params[] = $cve_almac;
    }

    // Matriz de servicios para ese cliente (base servicios + override cliente)
    $rows = db_all("
      SELECT 
        s.id_servicio,
        s.clave_servicio,
        s.nombre AS servicio,
        s.tipo_cobro,
        s.precio_base,
        s.moneda,
        COALESCE(cs.precio_cliente, NULL) AS precio_cliente,
        COALESCE(cs.Activo, 1) AS habilitado,
        CASE 
          WHEN cs.precio_cliente IS NOT NULL THEN cs.precio_cliente
          ELSE s.precio_base
        END AS precio_final
      FROM vas_servicio s
      LEFT JOIN vas_cliente_servicio cs
        ON cs.id_servicio = s.id_servicio
       AND cs.id_cliente = ?
       AND cs.IdEmpresa = ?
       $whereAlmac
      WHERE s.IdEmpresa = ?
        AND s.Activo = 1
      ORDER BY s.nombre
    ", array_merge([$idFinal, $IdEmpresa], $params, [$IdEmpresa]));

    json_ok($rows);
  }

  // ==========================
  // POST (create/update)
  // ==========================
  if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) json_fail('JSON inválido');

    $IdEmpresa = (int)($payload['IdEmpresa'] ?? 0);
    $id_cliente = (int)($payload['id_cliente'] ?? 0);
    $id_servicio = (int)($payload['id_servicio'] ?? 0);
    $cve_almac = trim((string)($payload['cve_almac'] ?? ''));
    $precio_cliente = $payload['precio_cliente'] ?? null;
    $Activo = isset($payload['Activo']) ? (int)$payload['Activo'] : 1;
    $usuario = trim((string)($payload['usuario'] ?? 'API'));

    if ($IdEmpresa <= 0) json_fail('Falta IdEmpresa');
    if ($id_cliente <= 0) json_fail('Falta id_cliente');
    if ($id_servicio <= 0) json_fail('Falta id_servicio');

    if ($precio_cliente === '' || $precio_cliente === null) $precio_cliente = null;

    // Upsert
    $exists = db_one("SELECT id FROM vas_cliente_servicio WHERE IdEmpresa=? AND id_cliente=? AND id_servicio=? AND (cve_almac <=> ?)",
      [$IdEmpresa, $id_cliente, $id_servicio, ($cve_almac === '' ? null : $cve_almac)]
    );

    if ($exists && isset($exists['id'])) {
      db_exec("UPDATE vas_cliente_servicio
                 SET precio_cliente = ?, Activo = ?, updated_at = NOW(), updated_by = ?
               WHERE id = ?",
        [$precio_cliente, $Activo, $usuario, (int)$exists['id']]
      );
      json_ok(['id' => (int)$exists['id']], 'Actualizado');
    } else {
      db_exec("INSERT INTO vas_cliente_servicio
               (IdEmpresa, cve_almac, id_cliente, id_servicio, tipo_cobro, precio_cliente, Activo, created_at, created_by)
               VALUES (?, ?, ?, ?, NULL, ?, ?, NOW(), ?)",
        [$IdEmpresa, ($cve_almac === '' ? null : $cve_almac), $id_cliente, $id_servicio, $precio_cliente, $Activo, $usuario]
      );
      $newId = db_last_id();
      json_ok(['id' => (int)$newId], 'Creado');
    }
  }

  // ==========================
  // DELETE
  // ==========================
  if ($method === 'DELETE') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) json_fail('JSON inválido');

    $id = (int)($payload['id'] ?? 0);
    if ($id <= 0) json_fail('Falta id');

    db_exec("DELETE FROM vas_cliente_servicio WHERE id = ?", [$id]);
    json_ok([], 'Eliminado');
  }

  json_fail('Método no soportado', ['method' => $method], 405);

} catch (Throwable $e) {
  json_fail('Error interno', $e->getMessage(), 500);
}
