<?php
// public/api/rutas_api.php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jexit(bool $ok, array $payload = [], int $http = 200): void {
  http_response_code($http);
  echo json_encode(array_merge(['success' => $ok], $payload), JSON_UNESCAPED_UNICODE);
  exit;
}

function req_json(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || trim($raw) === '') return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

function norm_int($v, $def = 0): int {
  if ($v === null || $v === '') return (int)$def;
  return (int)$v;
}

function norm_bool01($v, $def = 0): int {
  if ($v === null || $v === '') return (int)$def;
  if (is_bool($v)) return $v ? 1 : 0;
  $s = strtoupper(trim((string)$v));
  if (in_array($s, ['1','SI','S','TRUE','T','Y','YES'], true)) return 1;
  if (in_array($s, ['0','NO','N','FALSE','F'], true)) return 0;
  return (int)$def;
}

function norm_enum_sn($v, $def = 'N'): string {
  $s = strtoupper(trim((string)$v));
  return ($s === 'S') ? 'S' : $def;
}

$action = $_GET['action'] ?? 'list';

try {

  if ($action === 'list') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(200, max(1, (int)($_GET['pageSize'] ?? 25)));
    $offset = ($page - 1) * $pageSize;

    $q = trim((string)($_GET['q'] ?? ''));
    $show_inactivos = norm_bool01($_GET['show_inactivos'] ?? 0, 0);

    $where = [];
    $params = [];

    if (!$show_inactivos) {
      $where[] = 'IFNULL(Activo,1)=1';
    }

    if ($q !== '') {
      $where[] = '(cve_ruta LIKE :q OR descripcion LIKE :q OR CAST(cve_almacenp AS CHAR) LIKE :q OR CAST(ID_Ruta AS CHAR) LIKE :q)';
      $params[':q'] = '%' . $q . '%';
    }

    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $total = (int)db_val('SELECT COUNT(*) FROM t_ruta ' . $where_sql, $params);

    $rows = db_all(
      'SELECT ID_Ruta, cve_ruta, descripcion, status, cve_almacenp, venta_preventa, es_entrega, control_pallets_cont, consig_pallets, consig_cont, ID_Proveedor, Activo
       FROM t_ruta
       ' . $where_sql . '
       ORDER BY ID_Ruta DESC
       LIMIT ' . (int)$pageSize . ' OFFSET ' . (int)$offset,
      $params
    );

    // KPIs globales (sin filtros de búsqueda, pero respetando show_inactivos)
    $kpi_where = [];
    $kpi_params = [];
    if (!$show_inactivos) $kpi_where[] = 'IFNULL(Activo,1)=1';
    $kpi_sql = $kpi_where ? ('WHERE ' . implode(' AND ', $kpi_where)) : '';

    $kpis = db_one(
      'SELECT
         COUNT(*) AS total,
         SUM(IF(IFNULL(Activo,1)=1,1,0)) AS activos,
         SUM(IF(IFNULL(Activo,1)=0,1,0)) AS inactivos,
         SUM(IF(IFNULL(venta_preventa,0)=1,1,0)) AS preventa,
         SUM(IF(IFNULL(es_entrega,0)=1,1,0)) AS entrega
       FROM t_ruta ' . $kpi_sql,
      $kpi_params
    );

    jexit(true, [
      'data' => $rows,
      'total' => $total,
      'page' => $page,
      'pageSize' => $pageSize,
      'kpis' => $kpis
    ]);
  }

  if ($action === 'get') {
    $id = norm_int($_GET['id'] ?? 0, 0);
    if ($id <= 0) jexit(false, ['message' => 'ID inválido'], 400);

    $row = db_one('SELECT ID_Ruta, cve_ruta, descripcion, status, cve_almacenp, venta_preventa, es_entrega, control_pallets_cont, consig_pallets, consig_cont, ID_Proveedor, Activo FROM t_ruta WHERE ID_Ruta=?', [$id]);
    if (!$row) jexit(false, ['message' => 'No encontrado'], 404);

    jexit(true, ['data' => $row]);
  }

  if ($action === 'delete') {
    $id = norm_int($_GET['id'] ?? 0, 0);
    if ($id <= 0) jexit(false, ['message' => 'ID inválido'], 400);

    // Soft delete
    dbq('UPDATE t_ruta SET Activo=0 WHERE ID_Ruta=?', [$id]);
    jexit(true, ['message' => 'OK']);
  }

  if ($action === 'save') {
    $in = req_json();

    $id = norm_int($in['ID_Ruta'] ?? 0, 0);

    $cve_ruta = strtoupper(trim((string)($in['cve_ruta'] ?? '')));
    $descripcion = trim((string)($in['descripcion'] ?? ''));
    $status = strtoupper(trim((string)($in['status'] ?? 'A')));
    if (!in_array($status, ['A','B'], true)) $status = 'A';

    if ($cve_ruta === '' || $descripcion === '') {
      jexit(false, ['message' => 'cve_ruta y descripcion son obligatorios'], 400);
    }

    $cve_almacenp = norm_int($in['cve_almacenp'] ?? 0, 0);
    $venta_preventa = norm_bool01($in['venta_preventa'] ?? 0, 0);
    $es_entrega = norm_bool01($in['es_entrega'] ?? 0, 0);
    $control_pallets_cont = norm_enum_sn($in['control_pallets_cont'] ?? 'N', 'N');
    $consig_pallets = norm_int($in['consig_pallets'] ?? 0, 0);
    $consig_cont = norm_int($in['consig_cont'] ?? 0, 0);
    $ID_Proveedor = ($in['ID_Proveedor'] ?? null);
    $ID_Proveedor = ($ID_Proveedor === '' ? null : ($ID_Proveedor === null ? null : (int)$ID_Proveedor));
    $Activo = norm_bool01($in['Activo'] ?? 1, 1);

    // En alta (ID=0) insertamos y dejamos autoincrement
    if ($id <= 0) {
      // Validar unicidad
      $exists = db_val('SELECT COUNT(*) FROM t_ruta WHERE cve_ruta=?', [$cve_ruta]);
      if ((int)$exists > 0) {
        jexit(false, ['message' => 'La clave de ruta ya existe (cve_ruta debe ser única).'], 409);
      }

      dbq(
        'INSERT INTO t_ruta (cve_ruta, descripcion, status, cve_almacenp, venta_preventa, es_entrega, control_pallets_cont, consig_pallets, consig_cont, ID_Proveedor, Activo)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        [$cve_ruta, $descripcion, $status, $cve_almacenp, $venta_preventa, $es_entrega, $control_pallets_cont, $consig_pallets, $consig_cont, $ID_Proveedor, $Activo]
      );

      $newId = (int)db_val('SELECT LAST_INSERT_ID()');
      jexit(true, ['message' => 'Creado', 'ID_Ruta' => $newId]);
    }

    // Update
    $row = db_one('SELECT ID_Ruta, cve_ruta FROM t_ruta WHERE ID_Ruta=?', [$id]);
    if (!$row) jexit(false, ['message' => 'No encontrado'], 404);

    if (strtoupper((string)$row['cve_ruta']) !== $cve_ruta) {
      $exists = db_val('SELECT COUNT(*) FROM t_ruta WHERE cve_ruta=? AND ID_Ruta<>?', [$cve_ruta, $id]);
      if ((int)$exists > 0) {
        jexit(false, ['message' => 'La clave de ruta ya existe (cve_ruta debe ser única).'], 409);
      }
    }

    dbq(
      'UPDATE t_ruta
       SET cve_ruta=?, descripcion=?, status=?, cve_almacenp=?, venta_preventa=?, es_entrega=?, control_pallets_cont=?, consig_pallets=?, consig_cont=?, ID_Proveedor=?, Activo=?
       WHERE ID_Ruta=?',
      [$cve_ruta, $descripcion, $status, $cve_almacenp, $venta_preventa, $es_entrega, $control_pallets_cont, $consig_pallets, $consig_cont, $ID_Proveedor, $Activo, $id]
    );

    jexit(true, ['message' => 'Actualizado', 'ID_Ruta' => $id]);
  }

  if ($action === 'import_csv') {
    if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
      jexit(false, ['message' => 'Archivo CSV requerido'], 400);
    }

    $tmp = $_FILES['file']['tmp_name'];
    $fh = fopen($tmp, 'r');
    if (!$fh) jexit(false, ['message' => 'No se pudo leer el archivo'], 400);

    $header = fgetcsv($fh);
    if (!$header) jexit(false, ['message' => 'CSV vacío'], 400);

    $cols = array_map(function($c){ return trim((string)$c); }, $header);
    $idx = array_flip($cols);

    // Campos mínimos
    foreach (['cve_ruta','descripcion'] as $need) {
      if (!isset($idx[$need])) {
        fclose($fh);
        jexit(false, ['message' => 'Falta columna obligatoria en CSV: ' . $need], 400);
      }
    }

    $ok = 0; $err = 0; $errs = [];

    // Recomendación: usar upsert por cve_ruta (única)
    $sql = 'INSERT INTO t_ruta (cve_ruta, descripcion, status, cve_almacenp, venta_preventa, es_entrega, control_pallets_cont, consig_pallets, consig_cont, ID_Proveedor, Activo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              descripcion=VALUES(descripcion),
              status=VALUES(status),
              cve_almacenp=VALUES(cve_almacenp),
              venta_preventa=VALUES(venta_preventa),
              es_entrega=VALUES(es_entrega),
              control_pallets_cont=VALUES(control_pallets_cont),
              consig_pallets=VALUES(consig_pallets),
              consig_cont=VALUES(consig_cont),
              ID_Proveedor=VALUES(ID_Proveedor),
              Activo=VALUES(Activo)';

    while(($row = fgetcsv($fh)) !== false) {
      if (count($row) === 1 && trim((string)$row[0]) === '') continue;

      try {
        $cve_ruta = strtoupper(trim((string)($row[$idx['cve_ruta']] ?? '')));
        $descripcion = trim((string)($row[$idx['descripcion']] ?? ''));
        if ($cve_ruta === '' || $descripcion === '') {
          throw new Exception('cve_ruta/descripcion vacíos');
        }

        $status = isset($idx['status']) ? strtoupper(trim((string)$row[$idx['status']])) : 'A';
        if (!in_array($status, ['A','B'], true)) $status = 'A';

        $cve_almacenp = isset($idx['cve_almacenp']) ? norm_int($row[$idx['cve_almacenp']], 0) : 0;
        $venta_preventa = isset($idx['venta_preventa']) ? norm_bool01($row[$idx['venta_preventa']], 0) : 0;
        $es_entrega = isset($idx['es_entrega']) ? norm_bool01($row[$idx['es_entrega']], 0) : 0;
        $control_pallets_cont = isset($idx['control_pallets_cont']) ? norm_enum_sn($row[$idx['control_pallets_cont']], 'N') : 'N';
        $consig_pallets = isset($idx['consig_pallets']) ? norm_int($row[$idx['consig_pallets']], 0) : 0;
        $consig_cont = isset($idx['consig_cont']) ? norm_int($row[$idx['consig_cont']], 0) : 0;
        $ID_Proveedor = isset($idx['ID_Proveedor']) ? ($row[$idx['ID_Proveedor']] === '' ? null : (int)$row[$idx['ID_Proveedor']]) : null;
        $Activo = isset($idx['Activo']) ? norm_bool01($row[$idx['Activo']], 1) : 1;

        dbq($sql, [$cve_ruta, $descripcion, $status, $cve_almacenp, $venta_preventa, $es_entrega, $control_pallets_cont, $consig_pallets, $consig_cont, $ID_Proveedor, $Activo]);
        $ok++;
      } catch(Throwable $e) {
        $err++;
        if (count($errs) < 50) $errs[] = $e->getMessage();
      }
    }

    fclose($fh);
    jexit(true, ['message' => 'Importación finalizada', 'total_ok' => $ok, 'total_err' => $err, 'errors' => $errs]);
  }

  if ($action === 'layout_csv') {
    // Layout de importación
    $layout = [
      'cve_ruta','descripcion','status','cve_almacenp','venta_preventa','es_entrega','control_pallets_cont','consig_pallets','consig_cont','ID_Proveedor','Activo'
    ];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rutas_layout.csv"');
    echo implode(',', $layout) . "\n";
    exit;
  }

  jexit(false, ['message' => 'Acción no soportada'], 400);

} catch (Throwable $e) {
  jexit(false, ['message' => $e->getMessage()], 500);
}
