<?php
// /public/api/monitor_produccion_api.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }

$pdo = db_pdo();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {

  // =========================
  // Filtros (señales de negocio)
  // =========================
  $empresa = s($_GET['empresa'] ?? $_POST['empresa'] ?? null);   // ID_Proveedor
  $almacen = s($_GET['almacen'] ?? $_POST['almacen'] ?? null);   // cve_almac
  $zona    = s($_GET['zona']    ?? $_POST['zona']    ?? null);   // id_zona_almac
  $status  = s($_GET['status']  ?? $_POST['status']  ?? null);   // Status
  $desde   = s($_GET['desde']   ?? $_POST['desde']   ?? null);   // YYYY-MM-DD
  $hasta   = s($_GET['hasta']   ?? $_POST['hasta']   ?? null);   // YYYY-MM-DD

  $where = [];
  $bind  = [];

  if($empresa !== null){ $where[] = "o.ID_Proveedor = :empresa"; $bind[':empresa'] = (int)$empresa; }
  if($almacen !== null){ $where[] = "o.cve_almac = :almacen";    $bind[':almacen'] = $almacen; }
  if($zona    !== null){ $where[] = "o.id_zona_almac = :zona";   $bind[':zona'] = (int)$zona; }
  if($status  !== null){ $where[] = "o.Status = :status";        $bind[':status'] = $status; }

  // FechaReg preferente, si viene NULL cae a Fecha
  if($desde !== null){
    $where[] = "COALESCE(o.FechaReg,o.Fecha) >= :desde";
    $bind[':desde'] = $desde . " 00:00:00";
  }
  if($hasta !== null){
    $dt = new DateTime($hasta);
    $dt->modify('+1 day');
    $where[] = "COALESCE(o.FechaReg,o.Fecha) < :hasta";
    $bind[':hasta'] = $dt->format('Y-m-d') . " 00:00:00";
  }

  $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

  // =========================
  // Acción: KPIs
  // =========================
  if($action === 'stats'){
    $sql = "
      SELECT
        COUNT(*) AS total,
        SUM(o.Status='P') AS planeadas,
        SUM(o.Status='E') AS en_proceso,
        SUM(o.Status='T') AS terminadas,
        SUM(o.Status='C') AS canceladas
      FROM t_ordenprod o
      $whereSql
    ";
    $st = $pdo->prepare($sql);
    foreach($bind as $k=>$v){ $st->bindValue($k, $v); }
    $st->execute();
    $kpi = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    jexit(['ok'=>1,'kpi'=>[
      'total'=>(int)($kpi['total'] ?? 0),
      'planeadas'=>(int)($kpi['planeadas'] ?? 0),
      'en_proceso'=>(int)($kpi['en_proceso'] ?? 0),
      'terminadas'=>(int)($kpi['terminadas'] ?? 0),
      'canceladas'=>(int)($kpi['canceladas'] ?? 0),
    ]]);
  }

  // =========================
  // Acción: Detalle OT (modal)
  // =========================
  if($action === 'detalle'){
    $folio = s($_GET['folio'] ?? $_POST['folio'] ?? null);
    if(!$folio) jexit(['ok'=>0,'msg'=>'Folio requerido']);

    $sqlH = "
      SELECT
        o.id,
        o.Folio_Pro AS folio,
        o.Cve_Articulo AS producto,
        o.Cantidad AS cantidad,
        o.cve_almac AS almacen,
        o.id_zona_almac AS zona,
        o.Status AS status,
        o.Cve_Usuario AS usr_reg,
        o.Usr_Armo AS usr_armo,
        COALESCE(o.FechaReg,o.Fecha) AS fecha_reg,
        o.Hora_Ini AS hora_ini,
        o.Hora_Fin AS hora_fin,
        o.Referencia AS referencia
      FROM t_ordenprod o
      WHERE o.Folio_Pro = :folio
      ORDER BY COALESCE(o.FechaReg,o.Fecha) DESC, o.id DESC
      LIMIT 1
    ";
    $st = $pdo->prepare($sqlH);
    $st->execute([':folio'=>$folio]);
    $header = $st->fetch(PDO::FETCH_ASSOC);
    if(!$header) jexit(['ok'=>0,'msg'=>'OT no encontrada']);

    $sqlL = "
      SELECT
        d.id_ord,
        d.Cve_Articulo,
        d.Cantidad,
        d.Referencia
      FROM td_ordenprod d
      WHERE d.Folio_Pro = :folio
        AND (d.Activo IS NULL OR d.Activo = 1)
      ORDER BY d.id_ord ASC
    ";
    $st = $pdo->prepare($sqlL);
    $st->execute([':folio'=>$folio]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC);

    jexit(['ok'=>1,'header'=>$header,'lines'=>$lines]);
  }

  // =========================
  // Acción: List (DataTables server-side)
  // =========================
  $draw   = (int)($_GET['draw'] ?? 1);
  $start  = (int)($_GET['start'] ?? 0);
  $length = (int)($_GET['length'] ?? 25);
  if($length <= 0) $length = 25;

  $search = s($_GET['search']['value'] ?? null);

  // Orden DataTables
  $orderCol = (int)($_GET['order'][0]['column'] ?? 9);
  $orderDir = strtolower((string)($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

  // Mapeo columnas (no ordenamos por acciones)
  $colMap = [
    1 => "o.Folio_Pro",
    2 => "o.Cve_Articulo",
    3 => "o.Cantidad",
    4 => "o.cve_almac",
    5 => "o.id_zona_almac",
    6 => "o.Status",
    7 => "o.Cve_Usuario",
    8 => "o.Usr_Armo",
    9 => "COALESCE(o.FechaReg,o.Fecha)"
  ];
  $orderBy = $colMap[$orderCol] ?? "COALESCE(o.FechaReg,o.Fecha)";

  // WHERE + búsqueda
  $where2 = $where;
  $bind2  = $bind;

  if($search !== null){
    $where2[] = "(o.Folio_Pro LIKE :q OR o.Cve_Articulo LIKE :q OR o.Cve_Usuario LIKE :q OR o.Usr_Armo LIKE :q OR o.Referencia LIKE :q)";
    $bind2[':q'] = "%{$search}%";
  }
  $whereSql2 = $where2 ? ("WHERE " . implode(" AND ", $where2)) : "";

  // Totales (DataTables)
  $sqlTotal = "SELECT COUNT(*) FROM t_ordenprod o $whereSql";
  $st = $pdo->prepare($sqlTotal);
  foreach($bind as $k=>$v){ $st->bindValue($k,$v); }
  $st->execute();
  $recordsTotal = (int)$st->fetchColumn();

  $sqlFiltered = "SELECT COUNT(*) FROM t_ordenprod o $whereSql2";
  $st = $pdo->prepare($sqlFiltered);
  foreach($bind2 as $k=>$v){ $st->bindValue($k,$v); }
  $st->execute();
  $recordsFiltered = (int)$st->fetchColumn();

  // LIMIT/OFFSET inline sanitizado (evita PDO issues y mejora estabilidad)
  $lim = max(1, (int)$length);
  $off = max(0, (int)$start);

  // 1) Trae SOLO la página actual de OTs (sin joins pesados)
  $sqlPage = "
    SELECT
      o.Folio_Pro AS folio,
      o.Cve_Articulo AS producto,
      o.Cantidad AS cantidad,
      o.cve_almac AS almacen,
      o.id_zona_almac AS zona,
      o.Status AS status,
      o.Cve_Usuario AS usr_reg,
      o.Usr_Armo AS usr_armo,
      COALESCE(o.FechaReg,o.Fecha) AS fecha_reg
    FROM t_ordenprod o
    $whereSql2
    ORDER BY $orderBy $orderDir
    LIMIT $lim OFFSET $off
  ";
  $st = $pdo->prepare($sqlPage);
  foreach($bind2 as $k=>$v){ $st->bindValue($k,$v); }
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Si no hay filas, salida rápida
  if(!$rows){
    jexit([
      'draw' => $draw,
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
      'data' => []
    ]);
  }

  // 2) Calcula componentes SOLO para los folios de esta página (IN-list)
  $folios = array_values(array_unique(array_map(fn($r)=> (string)$r['folio'], $rows)));
  $ph = [];
  $inBind = [];
  foreach($folios as $i=>$f){
    $k = ":f{$i}";
    $ph[] = $k;
    $inBind[$k] = $f;
  }

  $sqlComp = "
    SELECT
      d.Folio_Pro,
      COUNT(*) AS componentes,
      SUM(COALESCE(d.Cantidad,0)) AS cant_componentes
    FROM td_ordenprod d
    WHERE d.Folio_Pro IN (" . implode(',', $ph) . ")
      AND (d.Activo IS NULL OR d.Activo = 1)
    GROUP BY d.Folio_Pro
  ";
  $st = $pdo->prepare($sqlComp);
  foreach($inBind as $k=>$v){ $st->bindValue($k,$v); }
  $st->execute();
  $compRows = $st->fetchAll(PDO::FETCH_ASSOC);

  $compMap = [];
  foreach($compRows as $cr){
    $compMap[(string)$cr['Folio_Pro']] = [
      'componentes' => (int)($cr['componentes'] ?? 0),
      'cant_componentes' => (float)($cr['cant_componentes'] ?? 0)
    ];
  }

  // 3) Merge final
  foreach($rows as &$r){
    $f = (string)$r['folio'];
    $r['componentes'] = $compMap[$f]['componentes'] ?? 0;
    $r['cant_componentes'] = $compMap[$f]['cant_componentes'] ?? 0;
  }
  unset($r);

  jexit([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $rows
  ]);

} catch(Throwable $e){
  // Para que DataTables no se quede "pensando"
  echo json_encode([
    'draw' => (int)($_GET['draw'] ?? 1),
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => [],
    'error' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
