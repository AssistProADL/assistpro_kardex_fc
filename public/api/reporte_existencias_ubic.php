<?php
// /public/api/reporte_existencias_ubic.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php'; // Debe exponer $pdo (PDO)

function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try {
  // Lee filtros (POST JSON o x-www-form-urlencoded)
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true);
  if (!is_array($in)) $in = $_POST;

  // Paginación básica
  $limit  = isset($in['limit'])  ? max(1, min(500, (int)$in['limit'])) : 25;
  $offset = isset($in['offset']) ? max(0, (int)$in['offset']) : 0;

  // Filtros
  $f = [
    'empresa'     => trim($in['empresa'] ?? ''),
    'almacen'     => trim($in['almacen'] ?? ''),
    'zona'        => trim($in['zona'] ?? ''),
    'bl'          => trim($in['bl'] ?? ''),
    'tipo_bl'     => trim($in['tipo_bl'] ?? ''),
    'status'      => trim($in['status'] ?? ''),
    'pasillo'     => trim($in['pasillo'] ?? ''),
    'rack'        => trim($in['rack'] ?? ''),
    'nivel'       => trim($in['nivel'] ?? ''),
    'producto'    => trim($in['producto'] ?? ''),
    'lp'          => trim($in['lp'] ?? ''),
    'lp_tipo'     => trim($in['lp_tipo'] ?? ''), // Pallet/Contenedor (si aplica)
    'lote'        => trim($in['lote'] ?? ''),
    'serie'       => trim($in['serie'] ?? ''),
    'fecha_ini'   => trim($in['fecha_ini'] ?? ''),
    'fecha_fin'   => trim($in['fecha_fin'] ?? ''),
  ];

  // WHERE dinámico (ajusta nombres de tablas/columnas si difieren)
  // Supuestos:
  // - stock_ubic su(ubicacion_id, producto_id, existencia, lp, lote, serie, updated_at)
  // - ubicaciones u(id, empresa_id, almacen_id, zona_id, codigocsd, tipo_bl, status_bl, pasillo, rack, nivel)
  // - productos p(id, sku/codigo, nombre/descripcion, um)
  // - empresas e(id,nombre), almacenes a(id,nombre,empresa_id), c_almacen z(id, des_almac, empresa_id, almacen_id)

  $where = ["su.existencia IS NOT NULL", "su.existencia <> 0"];
  $params = [];

  if ($f['empresa'] !== '') { $where[] = "u.empresa_id = :empresa"; $params[':empresa'] = $f['empresa']; }
  if ($f['almacen'] !== '') { $where[] = "u.almacen_id = :almacen"; $params[':almacen'] = $f['almacen']; }
  if ($f['zona']    !== '') { $where[] = "u.zona_id    = :zona";    $params[':zona']    = $f['zona']; }
  if ($f['bl']      !== '') { $where[] = "u.codigocsd  = :bl";      $params[':bl']      = $f['bl']; }

  if ($f['tipo_bl'] !== '') { $where[] = "COALESCE(u.tipo_bl,'')   = :tipo_bl";  $params[':tipo_bl'] = $f['tipo_bl']; }
  if ($f['status']  !== '') { $where[] = "COALESCE(u.status_bl,'') = :status";   $params[':status']  = $f['status']; }
  if ($f['pasillo'] !== '') { $where[] = "COALESCE(u.pasillo,'')   = :pasillo";  $params[':pasillo'] = $f['pasillo']; }
  if ($f['rack']    !== '') { $where[] = "COALESCE(u.rack,'')      = :rack";     $params[':rack']    = $f['rack']; }
  if ($f['nivel']   !== '') { $where[] = "COALESCE(u.nivel,'')     = :nivel";    $params[':nivel']   = $f['nivel']; }

  if ($f['producto'] !== '') { $where[] = "p.id = :producto";      $params[':producto'] = $f['producto']; }
  if ($f['lp']       !== '') { $where[] = "su.lp = :lp";            $params[':lp']       = $f['lp']; }
  if ($f['lp_tipo']  !== '') { $where[] = "COALESCE(su.lp_tipo,'') = :lp_tipo";  $params[':lp_tipo']  = $f['lp_tipo']; }
  if ($f['lote']     !== '') { $where[] = "su.lote = :lote";        $params[':lote']     = $f['lote']; }
  if ($f['serie']    !== '') { $where[] = "su.serie = :serie";      $params[':serie']    = $f['serie']; }

  if ($f['fecha_ini'] !== '' && $f['fecha_fin'] !== '') {
    // usa updated_at si existe; si no, elimina este bloque en tu BD
    $where[] = "DATE(su.updated_at) BETWEEN :fi AND :ff";
    $params[':fi'] = $f['fecha_ini'];
    $params[':ff'] = $f['fecha_fin'];
  }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // Consulta principal
  $sql = "
    SELECT
      e.nombre AS empresa,
      a.nombre AS almacen,
      z.des_almac AS zona,
      u.codigocsd AS bl,
      COALESCE(u.tipo_bl,'')   AS tipo_bl,
      COALESCE(u.status_bl,'') AS status_bl,
      COALESCE(u.pasillo,'')   AS pasillo,
      COALESCE(u.rack,'')      AS rack,
      COALESCE(u.nivel,'')     AS nivel,

      p.id AS producto_id,
      COALESCE(p.sku, p.codigo) AS sku,
      COALESCE(p.nombre, p.descripcion) AS producto,
      COALESCE(p.um,'') AS um,

      su.lp,
      COALESCE(su.lp_tipo,'') AS lp_tipo,
      su.lote,
      su.serie,
      su.existencia,
      su.updated_at

    FROM stock_ubic su
    JOIN ubicaciones u ON u.id = su.ubicacion_id
    LEFT JOIN productos p ON p.id = su.producto_id
    LEFT JOIN empresas  e ON e.id = u.empresa_id
    LEFT JOIN almacenes a ON a.id = u.almacen_id
    LEFT JOIN c_almacen z ON z.id = u.zona_id
    $whereSql
    ORDER BY e.nombre, a.nombre, z.des_almac, u.codigocsd, producto
    LIMIT :limit OFFSET :offset
  ";

  $stmt = $pdo->prepare($sql);
  foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
  $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // KPIs (totales) — mismo WHERE pero con agregados
  $sqlKpi = "
    SELECT
      COUNT(*)                        AS total_filas,
      COUNT(DISTINCT su.lp)           AS lp_unicos,
      SUM(COALESCE(su.existencia,0))  AS total_existencia
    FROM stock_ubic su
    JOIN ubicaciones u ON u.id = su.ubicacion_id
    LEFT JOIN productos p ON p.id = su.producto_id
    $whereSql
  ";
  $stmtK = $pdo->prepare($sqlKpi);
  foreach ($params as $k=>$v) $stmtK->bindValue($k, $v);
  $stmtK->execute();
  $kpi = $stmtK->fetch(PDO::FETCH_ASSOC);

  out([
    'ok' => true,
    'kpi' => [
      'total_filas'     => (int)($kpi['total_filas'] ?? 0),
      'lp_unicos'       => (int)($kpi['lp_unicos'] ?? 0),
      'total_existencia'=> (float)($kpi['total_existencia'] ?? 0),
    ],
    'limit'  => $limit,
    'offset' => $offset,
    'rows'   => $rows
  ]);

} catch (Exception $ex) {
  http_response_code(500);
  out(['ok'=>false, 'error'=>$ex->getMessage()]);
}
