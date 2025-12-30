<?php
// public/api/monitor_produccion_api.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

function jexit(array $payload): void {
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function param_str(string $k, string $def = ''): string {
  return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $def;
}
function param_int(string $k, int $def = 0): int {
  return isset($_GET[$k]) ? (int)$_GET[$k] : $def;
}

$action = param_str('action', 'list');

try {

  if ($action === 'detail') {
    // ============================================================
    // DETALLE OT (Modal)
    // ============================================================
    $folio = param_str('folio', '');
    if ($folio === '') {
      jexit(['ok' => false, 'error' => 'Folio requerido']);
    }

    // Encabezado
    $h = db_one("
      SELECT
        h.id,
        h.Folio_Pro,
        h.ID_Proveedor,
        h.cve_almac,
        h.id_zona_almac,
        h.Cve_Articulo,
        h.Cantidad,
        h.Cant_Prod,
        h.Status,
        h.Cve_Usuario,
        h.Usr_Armo,
        h.Fecha,
        h.FechaReg,
        h.Hora_Ini,
        h.Hora_Fin,
        CONCAT('(', COALESCE(a.clave_almacen,''), ') ', COALESCE(a.des_almac,'')) AS almacen_txt,
        CONCAT('(', COALESCE(z.clave_almacen,''), ') ', COALESCE(z.des_almac,'')) AS zona_txt
      FROM t_ordenprod h
      LEFT JOIN c_almacenp a ON a.id = h.cve_almac
      LEFT JOIN c_almacenp z ON z.id = h.id_zona_almac
      WHERE h.Folio_Pro = ?
      LIMIT 1
    ", [$folio]);

    if (!$h) {
      jexit(['ok' => false, 'error' => 'OT no encontrada']);
    }

    // Detalle componentes
    $rows = db_all("
      SELECT
        d.id_ord,
        d.Folio_Pro,
        d.Cve_Articulo AS componente,
        COALESCE(art.descripcion, '') AS descripcion,
        COALESCE(um.descripcion, '') AS umed,
        d.Cantidad,
        d.Fecha_Prod,
        d.Cve_Lote,
        d.Referencia,
        d.Cve_Almac_Ori,
        d.Activo
      FROM td_ordenprod d
      LEFT JOIN c_articulo art ON art.Cve_Articulo = d.Cve_Articulo
      LEFT JOIN c_unimed um ON um.cve_umed = art.unidadMedida
      WHERE d.Folio_Pro = ?
      ORDER BY d.id_ord ASC
    ", [$folio]);

    jexit([
      'ok' => true,
      'header' => $h,
      'items'  => $rows
    ]);
  }

  // ============================================================
  // LISTADO (DataTables) + KPIs
  // ============================================================

  // DataTables params
  $draw   = param_int('draw', 1);
  $start  = param_int('start', 0);
  $length = param_int('length', 25);
  if ($length <= 0) $length = 25;

  $search = '';
  if (isset($_GET['search']) && is_array($_GET['search'])) {
    $search = trim((string)($_GET['search']['value'] ?? ''));
  }

  // Filtros (UI)
  $empresa = param_int('empresa', 0);     // ID_Proveedor
  $almacen = param_int('almacen', 0);     // cve_almac
  $zona    = param_int('zona', 0);        // id_zona_almac
  $status  = param_str('status', '');     // Status
  $desde   = param_str('desde', '');
  $hasta   = param_str('hasta', '');

  // Normaliza fechas (si vienen dd/mm/aaaa, opcional)
  $normDate = function(string $d): string {
    $d = trim($d);
    if ($d === '') return '';
    // yyyy-mm-dd
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    // dd/mm/yyyy
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $d)) {
      [$dd,$mm,$yy] = explode('/', $d);
      return $yy.'-'.$mm.'-'.$dd;
    }
    return $d;
  };
  $desde = $normDate($desde);
  $hasta = $normDate($hasta);

  $where = [];
  $params = [];

  if ($empresa > 0) { $where[] = "h.ID_Proveedor = ?"; $params[] = $empresa; }
  if ($almacen > 0) { $where[] = "h.cve_almac = ?"; $params[] = $almacen; }
  if ($zona > 0)    { $where[] = "h.id_zona_almac = ?"; $params[] = $zona; }
  if ($status !== '' && strtolower($status) !== 'todos') { $where[] = "h.Status = ?"; $params[] = $status; }

  if ($desde !== '') { $where[] = "DATE(h.FechaReg) >= ?"; $params[] = $desde; }
  if ($hasta !== '') { $where[] = "DATE(h.FechaReg) <= ?"; $params[] = $hasta; }

  if ($search !== '') {
    $where[] = "(h.Folio_Pro LIKE ? OR h.Cve_Articulo LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
  }

  $wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // Total sin filtros (para DataTables)
  $recordsTotal = (int)db_val("SELECT COUNT(*) FROM t_ordenprod");

  // Total con filtros
  $recordsFiltered = (int)db_val("SELECT COUNT(*) FROM t_ordenprod h $wsql", $params);

  // KPIs con filtros
  $kpi = db_one("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN h.Status = 'P' THEN 1 ELSE 0 END) AS planeadas,
      SUM(CASE WHEN h.Status = 'E' THEN 1 ELSE 0 END) AS proceso,
      SUM(CASE WHEN h.Status = 'T' THEN 1 ELSE 0 END) AS terminadas,
      SUM(CASE WHEN h.Status = 'C' THEN 1 ELSE 0 END) AS canceladas
    FROM t_ordenprod h
    $wsql
  ", $params) ?: ['total'=>0,'planeadas'=>0,'proceso'=>0,'terminadas'=>0,'canceladas'=>0];

  // Data (listado)
  $sql = "
    SELECT
      h.id,
      h.Folio_Pro,
      h.Cve_Articulo,
      h.Cantidad,
      h.cve_almac,
      h.id_zona_almac,
      h.Status,
      h.Cve_Usuario,
      h.Usr_Armo,
      h.FechaReg,
      CONCAT('(', COALESCE(a.clave_almacen,''), ') ', COALESCE(a.des_almac,'')) AS almacen_txt,
      CONCAT('(', COALESCE(z.clave_almacen,''), ') ', COALESCE(z.des_almac,'')) AS zona_txt,
      COUNT(d.id_ord) AS comp,
      COALESCE(SUM(d.Cantidad),0) AS cant_comp
    FROM t_ordenprod h
    LEFT JOIN td_ordenprod d ON d.Folio_Pro = h.Folio_Pro
    LEFT JOIN c_almacenp a ON a.id = h.cve_almac
    LEFT JOIN c_almacenp z ON z.id = h.id_zona_almac
    $wsql
    GROUP BY h.id
    ORDER BY h.FechaReg DESC
    LIMIT $start, $length
  ";

  $data = db_all($sql, $params);

  // Formatea salida
  $out = [];
  foreach ($data as $r) {
    $out[] = [
      'id'        => (int)$r['id'],
      'folio'     => (string)$r['Folio_Pro'],
      'producto'  => (string)$r['Cve_Articulo'],
      'cant'      => (float)$r['Cantidad'],
      'almacen'   => (string)$r['almacen_txt'],
      'zona'      => (string)$r['zona_txt'],
      'status'    => (string)$r['Status'],
      'usr_reg'   => (string)($r['Cve_Usuario'] ?? ''),
      'usr_armo'  => (string)($r['Usr_Armo'] ?? ''),
      'fecha_reg' => (string)($r['FechaReg'] ?? ''),
      'comp'      => (int)($r['comp'] ?? 0),
      'cant_comp' => (float)($r['cant_comp'] ?? 0),
    ];
  }

  jexit([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'kpi' => [
      'total'     => (int)($kpi['total'] ?? 0),
      'planeadas' => (int)($kpi['planeadas'] ?? 0),
      'proceso'   => (int)($kpi['proceso'] ?? 0),
      'terminadas'=> (int)($kpi['terminadas'] ?? 0),
      'canceladas'=> (int)($kpi['canceladas'] ?? 0),
    ],
    'data' => $out
  ]);

} catch (Throwable $e) {
  jexit(['ok' => false, 'error' => $e->getMessage()]);
}
