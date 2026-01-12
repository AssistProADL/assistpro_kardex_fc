<?php
// public/api/orden_produccion_admin_data.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php'; // ajusta si tu ruta es distinta

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$action = $_GET['action'] ?? '';

/**
 * Detalle para modal
 */
if ($action === 'detalle') {
  $folio = trim($_GET['folio'] ?? '');
  if ($folio === '') jexit(['ok'=>0,'error'=>'Folio requerido']);

  try {
    $head = db_one("
      SELECT
        t.Folio_Pro, t.Cve_Articulo, t.Cve_Lote,
        IFNULL(t.Cantidad,0) AS Cantidad,
        IFNULL(t.Cant_Prod,0) AS Cant_Prod,
        IFNULL(t.Cve_Usuario,'') AS Cve_Usuario,
        DATE_FORMAT(t.Fecha,'%d/%m/%Y') AS Fecha,
        IFNULL(t.Status,'') AS Status
      FROM t_ordenprod t
      WHERE t.Folio_Pro = ?
      LIMIT 1
    ", [$folio]);

    $rows = db_all("
      SELECT
        d.Cve_Articulo,
        d.Cve_Lote,
        IFNULL(d.Cantidad,0) AS Cantidad,
        DATE_FORMAT(d.Fecha_Prod,'%d/%m/%Y') AS Fecha_Prod,
        IFNULL(d.Usr_Armo,'') AS Usr_Armo
      FROM td_ordenprod d
      WHERE d.Folio_Pro = ?
      ORDER BY d.Cve_Articulo ASC
    ", [$folio]);

    jexit(['ok'=>1,'head'=>$head,'rows'=>$rows]);
  } catch (Throwable $e) {
    jexit(['ok'=>0,'error'=>'Error detalle','detalle'=>$e->getMessage()]);
  }
}

/**
 * DataTables server-side
 */
try {
  $draw   = intval($_GET['draw'] ?? 1);
  $start  = intval($_GET['start'] ?? 0);
  $length = intval($_GET['length'] ?? 25);
  if ($length <= 0) $length = 25;

  // Filtros negocio
  $empresa = trim($_GET['empresa'] ?? '');
  $almacen = trim($_GET['almacen'] ?? '');
  $status  = trim($_GET['status'] ?? ''); // default lo controla la UI (P)
  $ini     = trim($_GET['ini'] ?? '');
  $fin     = trim($_GET['fin'] ?? '');
  $q       = trim($_GET['q'] ?? '');
  $lp      = trim($_GET['lp'] ?? '');
  $withStats = intval($_GET['with_stats'] ?? 0);

  // Search DataTables (si usan search global)
  $dtSearch = trim($_GET['search']['value'] ?? '');
  if ($dtSearch !== '' && $q === '') $q = $dtSearch;

  // Orden DataTables
  $orderColIdx = intval($_GET['order'][0]['column'] ?? 7);
  $orderDir    = strtoupper($_GET['order'][0]['dir'] ?? 'DESC');
  if (!in_array($orderDir, ['ASC','DESC'], true)) $orderDir = 'DESC';

  $cols = [
    0 => 't.Folio_Pro',       // acciones (no aplica) pero caemos a Folio
    1 => 't.Folio_Pro',
    2 => 't.Cve_Articulo',
    3 => 't.Cve_Lote',
    4 => 't.Cantidad',
    5 => 't.Cant_Prod',
    6 => 't.Cve_Usuario',
    7 => 't.Fecha',
    8 => 't.Status',
  ];
  $orderBy = $cols[$orderColIdx] ?? 't.Fecha';

  // WHERE dinámico (base filters)
  $where = [];
  $params = [];

  // Join a almacenes/empresa
  $joins = "
    LEFT JOIN c_almacenp a ON a.id = t.cve_almac
    LEFT JOIN c_compania c ON c.cve_cia = a.cve_cia
  ";

  if ($empresa !== '') { $where[] = "a.cve_cia = ?"; $params[] = $empresa; }
  if ($almacen !== '') { $where[] = "t.cve_almac = ?"; $params[] = $almacen; }

  // Fecha (t.Fecha es datetime)
  if ($ini !== '') { $where[] = "DATE(t.Fecha) >= ?"; $params[] = $ini; }
  if ($fin !== '') { $where[] = "DATE(t.Fecha) <= ?"; $params[] = $fin; }

  // Buscar folio/artículo
  if ($q !== '') {
    $where[] = "(t.Folio_Pro LIKE ? OR t.Cve_Articulo LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
  }

  // Buscar LP / Contenedor (c_charolas) => EXISTS por Pedido(Folio) o Clave_Contenedor
  if ($lp !== '') {
    $where[] = "EXISTS (
      SELECT 1
      FROM c_charolas ch
      WHERE (ch.Pedido = t.Folio_Pro OR ch.Clave_Contenedor LIKE ?)
      LIMIT 1
    )";
    $params[] = "%$lp%";
  }

  // WHERE string
  $whereSqlBase = (count($where) ? ("WHERE " . implode(" AND ", $where)) : "");

  // recordsTotal (sin filtros)
  $recordsTotal = db_val("SELECT COUNT(*) FROM t_ordenprod t", []);

  // recordsFiltered (con filtros + status si aplica)
  $whereSql = $whereSqlBase;
  $paramsFiltered = $params;

  if ($status !== '') {
    $whereSql .= ($whereSql === '' ? "WHERE " : " AND ") . "t.Status = ?";
    $paramsFiltered[] = $status;
  }

  $recordsFiltered = db_val("
    SELECT COUNT(*)
    FROM t_ordenprod t
    $joins
    $whereSql
  ", $paramsFiltered);

  // Data
  $rows = db_all("
    SELECT
      t.id,
      t.Folio_Pro,
      IFNULL(t.Cve_Articulo,'') AS Cve_Articulo,
      IFNULL(t.Cve_Lote,'') AS Cve_Lote,
      IFNULL(t.Cantidad,0) AS Cantidad,
      IFNULL(t.Cant_Prod,0) AS Cant_Prod,
      IFNULL(t.Cve_Usuario,'') AS Cve_Usuario,
      DATE_FORMAT(t.Fecha,'%d/%m/%Y') AS Fecha,
      IFNULL(t.Status,'') AS Status
    FROM t_ordenprod t
    $joins
    $whereSql
    ORDER BY $orderBy $orderDir
    LIMIT $start, $length
  ", $paramsFiltered);

  // Acciones (ver + iniciar)
  foreach ($rows as &$r) {
    $folio = htmlspecialchars($r['Folio_Pro'] ?? '', ENT_QUOTES, 'UTF-8');
    $r['acciones'] = '
      <div class="d-flex gap-1">
        <button class="btn btn-primary btn-sm btn-icon" title="Ver detalle" onclick="__verDetalle(\''.$folio.'\')">🔍</button>
        <button class="btn btn-success btn-sm btn-icon" title="Iniciar (siguiente paso)">▶</button>
      </div>
    ';
  }
  unset($r);

  // KPIs por status (con filtros BASE, sin limitar por página)
  // Nota: NO amarramos al status dropdown para que el tablero refleje el universo filtrado por empresa/almacen/fecha/q/lp.
  $stats = null;
  if ($withStats) {
    $statsRows = db_all("
      SELECT IFNULL(t.Status,'') AS Status, COUNT(*) AS n
      FROM t_ordenprod t
      $joins
      $whereSqlBase
      GROUP BY IFNULL(t.Status,'')
    ", $params);

    $stats = ['P'=>0,'I'=>0,'T'=>0,'B'=>0,'E'=>0];
    foreach ($statsRows as $sr) {
      $k = strtoupper(trim($sr['Status'] ?? ''));
      $n = intval($sr['n'] ?? 0);
      if ($k === '') continue;
      if (!isset($stats[$k])) continue;
      $stats[$k] = $n;
    }
  }

  jexit([
    'draw' => $draw,
    'recordsTotal' => intval($recordsTotal),
    'recordsFiltered' => intval($recordsFiltered),
    'data' => $rows,
    'stats' => $stats
  ]);

} catch (Throwable $e) {
  jexit([
    'draw' => intval($_GET['draw'] ?? 1),
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => [],
    'stats' => ['P'=>0,'I'=>0,'T'=>0,'B'=>0,'E'=>0],
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ]);
}
