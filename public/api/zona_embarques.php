<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v)
{
  $v = trim((string) $v);
  return $v === '' ? null : $v;
}
function i0($v)
{
  return ($v === '' || $v === null) ? 0 : (int) $v;
}
function i1($v)
{
  return ($v === '' || $v === null) ? 1 : (int) $v;
}
function fnull($v)
{
  return ($v === '' || $v === null) ? null : (float) $v;
}

function validar_obligatorios($data)
{
  $errs = [];
  $alm = trim((string) ($data['cve_almac'] ?? ''));
  $ub = trim((string) ($data['cve_ubicacion'] ?? ''));
  if ($alm === '')
    $errs[] = 'cve_almac es obligatorio';
  if ($ub === '')
    $errs[] = 'cve_ubicacion es obligatorio';
  return $errs;
}

/* =====================================================
 * EXPORT CSV (layout / datos)
 * ===================================================== */
if ($action === 'export_csv') {
  $tipo = $_GET['tipo'] ?? 'layout';

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=zona_embarques_' . $tipo . '.csv');

  $out = fopen('php://output', 'w');

  $headers = [
    'ID_Embarque',
    'cve_ubicacion',
    'cve_almac',
    'status',
    'Activo',
    'descripcion',
    'AreaStagging',
    'largo',
    'ancho',
    'alto'
  ];
  fputcsv($out, $headers);

  if ($tipo === 'datos') {
    $sql = "SELECT " . implode(',', $headers) . " FROM t_ubicacionembarque WHERE IFNULL(Activo,1)=1 ORDER BY cve_almac, cve_ubicacion LIMIT 5000";
    foreach ($pdo->query($sql) as $row)
      fputcsv($out, $row);
  }

  fclose($out);
  exit;
}

/* =====================================================
 * IMPORT CSV (UPSERT por ID_Embarque si viene; si no, por (cve_almac,cve_ubicacion))
 * ===================================================== */
if ($action === 'import_csv') {

  if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'Archivo no recibido']);
    exit;
  }

  $fh = fopen($_FILES['file']['tmp_name'], 'r');
  if (!$fh) {
    echo json_encode(['error' => 'No se pudo leer el archivo']);
    exit;
  }

  $headers = fgetcsv($fh);

  $esperadas = [
    'ID_Embarque',
    'cve_ubicacion',
    'cve_almac',
    'status',
    'Activo',
    'descripcion',
    'AreaStagging',
    'largo',
    'ancho',
    'alto'
  ];

  if ($headers !== $esperadas) {
    echo json_encode(['error' => 'Layout incorrecto', 'esperado' => $esperadas, 'recibido' => $headers]);
    exit;
  }

  $stFindById = $pdo->prepare("SELECT ID_Embarque FROM t_ubicacionembarque WHERE ID_Embarque=? LIMIT 1");
  $stFindByKey = $pdo->prepare("SELECT ID_Embarque FROM t_ubicacionembarque WHERE cve_almac=? AND cve_ubicacion=? LIMIT 1");

  $stIns = $pdo->prepare("
    INSERT INTO t_ubicacionembarque
    (cve_ubicacion,cve_almac,status,Activo,descripcion,AreaStagging,largo,ancho,alto)
    VALUES (?,?,?,?,?,?,?,?,?)
  ");

  $stUpd = $pdo->prepare("
    UPDATE t_ubicacionembarque SET
      cve_ubicacion=?,cve_almac=?,status=?,Activo=?,descripcion=?,AreaStagging=?,largo=?,ancho=?,alto=?
    WHERE ID_Embarque=?
    LIMIT 1
  ");

  $rows_ok = 0;
  $rows_err = 0;
  $errores = [];
  $pdo->beginTransaction();

  try {
    $linea = 1;
    while (($r = fgetcsv($fh)) !== false) {
      $linea++;
      if (!$r || count($r) < 10) {
        $rows_err++;
        $errores[] = ['fila' => $linea, 'motivo' => 'Fila incompleta', 'data' => $r];
        continue;
      }

      $data = array_combine($esperadas, $r);
      $errs = validar_obligatorios($data);
      if ($errs) {
        $rows_err++;
        $errores[] = ['fila' => $linea, 'motivo' => implode('; ', $errs), 'data' => $r];
        continue;
      }

      $ID_Embarque = trim((string) ($data['ID_Embarque'] ?? ''));
      $cve_ubicacion = trim((string) $data['cve_ubicacion']);
      $cve_almac = (int) $data['cve_almac'];

      $status = s($data['status']);
      $Activo = i1($data['Activo']);
      $descripcion = s($data['descripcion']);
      $AreaStagging = s($data['AreaStagging']); // enum('S','N')
      $largo = fnull($data['largo']);
      $ancho = fnull($data['ancho']);
      $alto = fnull($data['alto']);

      $existeId = null;
      if ($ID_Embarque !== '' && ctype_digit($ID_Embarque)) {
        $stFindById->execute([(int) $ID_Embarque]);
        $existeId = $stFindById->fetchColumn();
      } else {
        $stFindByKey->execute([$cve_almac, $cve_ubicacion]);
        $existeId = $stFindByKey->fetchColumn();
      }

      if ($existeId) {
        $stUpd->execute([
          $cve_ubicacion,
          $cve_almac,
          $status,
          $Activo,
          $descripcion,
          $AreaStagging,
          $largo,
          $ancho,
          $alto,
          (int) $existeId
        ]);
      } else {
        $stIns->execute([$cve_ubicacion, $cve_almac, $status, $Activo, $descripcion, $AreaStagging, $largo, $ancho, $alto]);
      }

      $rows_ok++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'rows_ok' => $rows_ok, 'rows_err' => $rows_err, 'errores' => $errores]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
  }
  exit;
}

/* =====================================================
 * CATALOGOS AUX (almacenes / sugerencias ubicacion)
 * ===================================================== */
if ($action === 'almacenes') {
  $st = $pdo->query("SELECT cve_almac, IFNULL(des_almac, CONCAT('ALM ',cve_almac)) AS des_almac FROM c_almacen WHERE IFNULL(Activo,1)=1 ORDER BY des_almac");
  echo json_encode($st->fetchAll());
  exit;
}

if ($action === 'ubicaciones_suggest') {
  // Sugerencias desde c_ubicacion.CodigoCSD (si existe), limitado para performance
  $q = trim((string) ($_GET['q'] ?? ''));
  $sql = "SELECT DISTINCT CodigoCSD AS cve_ubicacion
          FROM c_ubicacion
          WHERE CodigoCSD IS NOT NULL AND CodigoCSD <> '' ";
  if ($q !== '')
    $sql .= " AND CodigoCSD LIKE :q ";
  $sql .= " ORDER BY CodigoCSD LIMIT 50";
  $st = $pdo->prepare($sql);
  if ($q !== '')
    $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $st->execute();
  echo json_encode($st->fetchAll());
  exit;
}

/* =====================================================
 * LIST + SEARCH (25 rows)
 * ===================================================== */
if ($action === 'list') {
  error_log('=== zona_embarques.php action=list ===');

  $inactivos = (int) ($_GET['inactivos'] ?? 0);
  $alm = (int) ($_GET['cve_almac'] ?? 0);
  $almacenp_id = (int) ($_GET['almacenp_id'] ?? 0); // Nuevo: soporte para c_almacenp.id
  $q = trim((string) ($_GET['q'] ?? ''));

  error_log("Parámetros recibidos: inactivos=$inactivos, cve_almac=$alm, almacenp_id=$almacenp_id, q='$q'");

  // Si viene almacenp_id, convertirlo a cve_almac mediante c_almacen
  if ($almacenp_id > 0 && $alm === 0) {
    // Caso explícito almacenp_id
    $alm = $almacenp_id;
    // (Luego caerá en la lógica mixta abajo)
  }

  $alm_ids = '';

  // NUEVO: Verificar si el $alm recibido (sea por cve_almac o almacenp_id) 
  // es un PADRE que tiene hijos.
  if ($alm > 0) {
    // Intentar buscar hijos asociados a este ID como padre
    $stHijos = $pdo->prepare("SELECT DISTINCT cve_almac FROM c_almacen WHERE cve_almacenp = ?");
    $stHijos->execute([$alm]);
    $hijos = $stHijos->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($hijos)) {
      // Es un padre con hijos
      $alm_ids = implode(',', array_map('intval', $hijos));
      error_log("El ID $alm es un PADRE. Hijos encontrados: $alm_ids");
      // Reseteamos $alm a 0 para que use el IN ($alm_ids)
      $alm = 0;
    } else {
      // No tiene hijos, asumimos que es un almacén directo (hijo) 
      // o un padre sin hijos configurados. Lo tratamos como ID directo.
      error_log("El ID $alm no tiene hijos (o es hijo directo). Buscando directo.");
    }
  }

  $where = "WHERE IFNULL(e.Activo,1)=:activo";

  if ($alm > 0) {
    $where .= " AND e.cve_almac=:alm";
  } elseif (!empty($alm_ids)) {
    // Nueva lógica: soporte para múltiples hijos
    $where .= " AND e.cve_almac IN ($alm_ids)";
  }

  if ($q !== '') {
    $where .= " AND (
      e.cve_ubicacion LIKE :q OR IFNULL(e.descripcion,'') LIKE :q OR IFNULL(e.status,'') LIKE :q OR
      IFNULL(e.AreaStagging,'') LIKE :q OR CAST(e.ID_Embarque AS CHAR) LIKE :q
    )";
  }

  $sql = "
    SELECT DISTINCT
      e.ID_Embarque,
      e.cve_ubicacion,
      e.cve_almac,
      a.des_almac,
      e.status,
      e.Activo,
      e.descripcion,
      e.AreaStagging,
      e.largo, e.ancho, e.alto
    FROM t_ubicacionembarque e
    LEFT JOIN c_almacen a ON a.cve_almac = e.cve_almac
    -- RELAJADO: LEFT JOIN para ver zonas aunque no tengan pedidos asignados
    LEFT JOIN rel_uembarquepedido r ON r.cve_ubicacion = e.cve_ubicacion AND r.Activo = 1
    $where
    ORDER BY e.cve_almac, e.cve_ubicacion
    LIMIT 25
  ";

  error_log("SQL generado: $sql");

  $st = $pdo->prepare($sql);
  $st->bindValue(':activo', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($alm > 0) {
    $st->bindValue(':alm', $alm, PDO::PARAM_INT);
    error_log("Binding :alm = $alm");
  }
  // Nota: si usamos IN ($alm_ids), no bindeamos :alm, ya está inyectado como enteros seguros

  if ($q !== '') {
    $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
    error_log("Binding :q = %$q%");
  }

  $st->execute();
  $result = $st->fetchAll();

  error_log('Registros encontrados: ' . count($result));
  if (count($result) > 0) {
    error_log('Primer registro: ' . print_r($result[0], true));
  } else {
    error_log('⚠️ WARNING: No se encontraron zonas de embarque con los criterios especificados');
  }

  echo json_encode($result);
  error_log('✅ JSON enviado exitosamente');
  exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE
 * ===================================================== */
switch ($action) {

  case 'get': {
    $id = $_GET['ID_Embarque'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'ID_Embarque requerido']);
      exit;
    }
    $st = $pdo->prepare("SELECT * FROM t_ubicacionembarque WHERE ID_Embarque=?");
    $st->execute([(int) $id]);
    echo json_encode($st->fetch());
    break;
  }

  case 'create': {
    $errs = validar_obligatorios($_POST);
    if ($errs) {
      echo json_encode(['error' => 'Validación', 'detalles' => $errs]);
      exit;
    }

    $st = $pdo->prepare("
      INSERT INTO t_ubicacionembarque
      (cve_ubicacion,cve_almac,status,Activo,descripcion,AreaStagging,largo,ancho,alto)
      VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $st->execute([
      trim((string) $_POST['cve_ubicacion']),
      (int) $_POST['cve_almac'],
      s($_POST['status'] ?? null),
      i1($_POST['Activo'] ?? 1),
      s($_POST['descripcion'] ?? null),
      s($_POST['AreaStagging'] ?? null),
      fnull($_POST['largo'] ?? null),
      fnull($_POST['ancho'] ?? null),
      fnull($_POST['alto'] ?? null),
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    break;
  }

  case 'update': {
    $id = $_POST['ID_Embarque'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'ID_Embarque requerido']);
      exit;
    }

    $errs = validar_obligatorios($_POST);
    if ($errs) {
      echo json_encode(['error' => 'Validación', 'detalles' => $errs]);
      exit;
    }

    $st = $pdo->prepare("
      UPDATE t_ubicacionembarque SET
        cve_ubicacion=?,cve_almac=?,status=?,Activo=?,descripcion=?,AreaStagging=?,largo=?,ancho=?,alto=?
      WHERE ID_Embarque=?
      LIMIT 1
    ");

    $st->execute([
      trim((string) $_POST['cve_ubicacion']),
      (int) $_POST['cve_almac'],
      s($_POST['status'] ?? null),
      i1($_POST['Activo'] ?? 1),
      s($_POST['descripcion'] ?? null),
      s($_POST['AreaStagging'] ?? null),
      fnull($_POST['largo'] ?? null),
      fnull($_POST['ancho'] ?? null),
      fnull($_POST['alto'] ?? null),
      (int) $id
    ]);

    echo json_encode(['success' => true]);
    break;
  }

  case 'delete': {
    $id = $_POST['ID_Embarque'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'ID_Embarque requerido']);
      exit;
    }
    $pdo->prepare("UPDATE t_ubicacionembarque SET Activo=0 WHERE ID_Embarque=?")->execute([(int) $id]);
    echo json_encode(['success' => true]);
    break;
  }

  case 'restore': {
    $id = $_POST['ID_Embarque'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'ID_Embarque requerido']);
      exit;
    }
    $pdo->prepare("UPDATE t_ubicacionembarque SET Activo=1 WHERE ID_Embarque=?")->execute([(int) $id]);
    echo json_encode(['success' => true]);
    break;
  }

  default:
    echo json_encode(['error' => 'Acción no válida']);
}
