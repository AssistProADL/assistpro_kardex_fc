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
function i1($v)
{
  return ($v === '' || $v === null) ? 1 : (int) $v;
}

function validar($data)
{
  $errs = [];
  if (trim((string) ($data['Clave_motivo'] ?? '')) === '')
    $errs[] = 'Clave_motivo es obligatorio';
  if (trim((string) ($data['MOT_DESC'] ?? '')) === '')
    $errs[] = 'MOT_DESC es obligatorio';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if ($action === 'export_csv') {
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=motivos_devolucion_' . $tipo . '.csv');
  $out = fopen('php://output', 'w');
  fputs($out, "\xEF\xBB\xBF"); // BOM

  // User-friendly headers for CSV
  $csvHeaders = ['ID', 'Clave', 'Descripción', 'Almacén', 'Activo'];
  fputcsv($out, $csvHeaders);

  if ($tipo === 'datos') {
    // DB columns to select
    $dbCols = ['MOT_ID', 'Clave_motivo', 'MOT_DESC', 'id_almacen', 'Activo'];
    $sql = "SELECT " . implode(',', $dbCols) . " FROM motivos_devolucion ORDER BY MOT_ID";
    foreach ($pdo->query($sql) as $r)
      fputcsv($out, $r);
  }
  fclose($out);
  exit;
}

/* =========================
   IMPORT CSV (UPSERT por Clave)
========================= */
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
  $esperadas = ['ID', 'Clave', 'Descripción', 'Almacén', 'Activo'];

  if ($headers !== $esperadas) {
    echo json_encode(['error' => 'Layout incorrecto', 'esperado' => $esperadas, 'recibido' => $headers]);
    exit;
  }

  // Map friendly headers to DB columns
  $headerMap = [
    'ID' => 'MOT_ID',
    'Clave' => 'Clave_motivo',
    'Descripción' => 'MOT_DESC',
    'Almacén' => 'id_almacen',
    'Activo' => 'Activo'
  ];

  $stFind = $pdo->prepare("SELECT MOT_ID FROM motivos_devolucion WHERE Clave_motivo=? LIMIT 1");
  $stIns = $pdo->prepare("
    INSERT INTO motivos_devolucion (Clave_motivo,MOT_DESC,id_almacen,Activo)
    VALUES (?,?,?,?)
  ");
  $stUpd = $pdo->prepare("
    UPDATE motivos_devolucion
    SET MOT_DESC=?, id_almacen=?, Activo=?
    WHERE MOT_ID=? LIMIT 1
  ");

  $ok = 0;
  $err = 0;
  $errs = [];
  $pdo->beginTransaction();
  try {
    $ln = 1;
    while (($r = fgetcsv($fh)) !== false) {
      $ln++;
      if (count($r) < count($esperadas)) {
        $err++;
        $errs[] = ['fila' => $ln, 'motivo' => 'Fila incompleta'];
        continue;
      }

      // Combine with friendly headers, then map to DB columns
      $rowData = array_combine($esperadas, $r);
      $d = [];
      foreach ($rowData as $friendlyKey => $value) {
        $dbKey = $headerMap[$friendlyKey];
        $d[$dbKey] = $value;
      }

      $val = validar($d);
      if ($val) {
        $err++;
        $errs[] = ['fila' => $ln, 'motivo' => implode('; ', $val)];
        continue;
      }

      $clave = trim($d['Clave_motivo']);
      $stFind->execute([$clave]);
      $id = $stFind->fetchColumn();

      if ($id) {
        $stUpd->execute([
          s($d['MOT_DESC']),
          s($d['id_almacen']),
          i1($d['Activo']),
          $id
        ]);
      } else {
        $stIns->execute([
          $clave,
          s($d['MOT_DESC']),
          s($d['id_almacen']),
          i1($d['Activo'])
        ]);
      }
      $ok++;
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'rows_ok' => $ok, 'rows_err' => $err, 'errores' => $errs]);
    exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
    exit;
  }
}

/* =========================
   LIST (server side)
========================= */
if ($action === 'list') {
  $inactivos = (int) ($_GET['inactivos'] ?? 0);
  $q = trim($_GET['q'] ?? '');
  $page = max(1, (int) ($_GET['page'] ?? 1)); // Standard logic often sends page/limit
  $limit = max(1, min(200, (int) ($_GET['limit'] ?? 25)));
  $offset = ($page - 1) * $limit; // Calculate offset from page if sent, or use GET offset if preferred. But standard often uses page.
  // Frontend sends limit & offset directly in current code: '&limit='... '&offset='.
  // Let's support both or rely on what frontend sends.
  // Frontend code: url = API+'?action=list' ... '&limit='+perPage+'&offset='+offset;
  $offset = max(0, (int) ($_GET['offset'] ?? 0));

  $where = " WHERE IFNULL(Activo,1)=:a ";
  if ($q !== '') {
    $where .= " AND (Clave_motivo LIKE :q OR MOT_DESC LIKE :q)";
  }

  $stc = $pdo->prepare("SELECT COUNT(*) FROM motivos_devolucion $where");
  $stc->bindValue(':a', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($q !== '')
    $stc->bindValue(':q', "%$q%");
  $stc->execute();
  $total = (int) $stc->fetchColumn();
  $pages = ceil($total / $limit);

  // Check for ANY inactives in the whole table to decide if we show the toggle
  $sth = $pdo->query("SELECT 1 FROM motivos_devolucion WHERE IFNULL(Activo,1)=0 LIMIT 1");
  $hasInactives = (bool) $sth->fetchColumn();

  $st = $pdo->prepare("
    SELECT MOT_ID,Clave_motivo,MOT_DESC,id_almacen,Activo
    FROM motivos_devolucion
    $where
    ORDER BY MOT_ID DESC
    LIMIT $limit OFFSET $offset
  ");
  $st->bindValue(':a', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($q !== '')
    $st->bindValue(':q', "%$q%");
  $st->execute();

  echo json_encode([
    'success' => true,
    'rows' => $st->fetchAll(PDO::FETCH_ASSOC),
    'total' => $total,
    'pages' => $pages,
    'page' => $page,
    'hasInactives' => $hasInactives
  ]);
  exit;
}

/* =========================
   CRUD
========================= */
switch ($action) {
  case 'get':
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'id requerido']);
      exit;
    }
    $st = $pdo->prepare("SELECT * FROM motivos_devolucion WHERE MOT_ID=? LIMIT 1");
    $st->execute([$id]);
    echo json_encode($st->fetch(PDO::FETCH_ASSOC));
    exit;

  case 'create':
    $e = validar($_POST);
    if ($e) {
      echo json_encode(['error' => 'Validación', 'detalles' => $e]);
      exit;
    }
    $pdo->prepare("
      INSERT INTO motivos_devolucion (Clave_motivo,MOT_DESC,id_almacen,Activo)
      VALUES (?,?,?,?)
    ")->execute([
          trim($_POST['Clave_motivo']),
          trim($_POST['MOT_DESC']),
          s($_POST['id_almacen']),
          i1($_POST['Activo'])
        ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;

  case 'update':
    $id = (int) ($_POST['MOT_ID'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'MOT_ID requerido']);
      exit;
    }
    $e = validar($_POST);
    if ($e) {
      echo json_encode(['error' => 'Validación', 'detalles' => $e]);
      exit;
    }
    $pdo->prepare("
      UPDATE motivos_devolucion
      SET Clave_motivo=?, MOT_DESC=?, id_almacen=?, Activo=?
      WHERE MOT_ID=? LIMIT 1
    ")->execute([
          trim($_POST['Clave_motivo']),
          trim($_POST['MOT_DESC']),
          s($_POST['id_almacen']),
          i1($_POST['Activo']),
          $id
        ]);
    echo json_encode(['success' => true]);
    exit;

  case 'delete':
    $pdo->prepare("UPDATE motivos_devolucion SET Activo=0 WHERE MOT_ID=?")
      ->execute([(int) $_POST['id']]);
    echo json_encode(['success' => true]);
    exit;

  case 'restore':
    $pdo->prepare("UPDATE motivos_devolucion SET Activo=1 WHERE MOT_ID=?")
      ->execute([(int) $_POST['id']]);
    echo json_encode(['success' => true]);
    exit;

  default:
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}
