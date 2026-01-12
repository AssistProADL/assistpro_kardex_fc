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

function validar($data)
{
  $errs = [];
  $clave = trim((string) ($data['Clave'] ?? ''));
  $motivo = trim((string) ($data['Motivo'] ?? ''));
  if ($clave === '')
    $errs[] = 'Clave es obligatoria';
  if ($motivo === '')
    $errs[] = 'Motivo es obligatorio';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if ($action === 'export_csv') {
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=motivosnoventa_' . $tipo . '.csv');
  $out = fopen('php://output', 'w');

  $headers = ['IdMot', 'Clave', 'Motivo', 'Status'];
  fputcsv($out, $headers);

  if ($tipo === 'datos') {
    $sql = "SELECT " . implode(',', $headers) . " FROM motivosnoventa ORDER BY IdMot";
    foreach ($pdo->query($sql) as $row)
      fputcsv($out, $row);
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
  $esperadas = ['IdMot', 'Clave', 'Motivo', 'Status'];
  if ($headers !== $esperadas) {
    echo json_encode(['error' => 'Layout incorrecto', 'esperado' => $esperadas, 'recibido' => $headers]);
    exit;
  }

  $stFind = $pdo->prepare("SELECT IdMot FROM motivosnoventa WHERE Clave=? LIMIT 1");
  $stIns = $pdo->prepare("INSERT INTO motivosnoventa (Clave,Motivo,Status) VALUES (?,?,?)");
  $stUpd = $pdo->prepare("UPDATE motivosnoventa SET Motivo=?, Status=? WHERE IdMot=? LIMIT 1");

  $rows_ok = 0;
  $rows_err = 0;
  $errores = [];
  $pdo->beginTransaction();
  try {
    $linea = 1;
    while (($r = fgetcsv($fh)) !== false) {
      $linea++;
      if (!$r || count($r) < count($esperadas)) {
        $rows_err++;
        $errores[] = ['fila' => $linea, 'motivo' => 'Fila incompleta'];
        continue;
      }
      $data = array_combine($esperadas, $r);

      $errs = validar($data);
      if ($errs) {
        $rows_err++;
        $errores[] = ['fila' => $linea, 'motivo' => implode('; ', $errs)];
        continue;
      }

      $clave = trim((string) $data['Clave']);
      $motivo = trim((string) $data['Motivo']);
      $status = ($data['Status'] === '' || $data['Status'] === null) ? 1 : (int) $data['Status'];

      $stFind->execute([$clave]);
      $id = (int) ($stFind->fetchColumn() ?: 0);

      if ($id > 0) {
        $stUpd->execute([$motivo, $status, $id]);
      } else {
        $stIns->execute([$clave, $motivo, $status]);
      }

      $rows_ok++;
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'rows_ok' => $rows_ok, 'rows_err' => $rows_err, 'errores' => $errores]);
    exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
    exit;
  }
}

/* =========================
   LIST (paginado server-side)
========================= */
if ($action === 'list') {
  $inactivos = (int) ($_GET['inactivos'] ?? 0);
  $q = trim((string) ($_GET['q'] ?? ''));
  $limit = max(1, min(200, (int) ($_GET['limit'] ?? 25)));
  $offset = max(0, (int) ($_GET['offset'] ?? 0));

  $where = " WHERE IFNULL(Status,1)=:status ";
  if ($q !== '') {
    $where .= " AND (Clave LIKE :q OR Motivo LIKE :q) ";
  }

  $sqlCount = "SELECT COUNT(*) FROM motivosnoventa $where";
  $stc = $pdo->prepare($sqlCount);
  $stc->bindValue(':status', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($q !== '')
    $stc->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $stc->execute();
  $total = (int) $stc->fetchColumn();

  $sql = "SELECT IdMot,Clave,Motivo,IFNULL(Status,1) AS Status
          FROM motivosnoventa
          $where
          ORDER BY IdMot DESC
          LIMIT $limit OFFSET $offset";
  $st = $pdo->prepare($sql);
  $st->bindValue(':status', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($q !== '')
    $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $st->execute();

  echo json_encode(['rows' => $st->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
  exit;
}

/* =========================
   CRUD
========================= */
switch ($action) {
  case 'get': {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'id requerido']);
      exit;
    }
    $st = $pdo->prepare("SELECT IdMot,Clave,Motivo,IFNULL(Status,1) AS Status FROM motivosnoventa WHERE IdMot=? LIMIT 1");
    $st->execute([$id]);
    echo json_encode($st->fetch(PDO::FETCH_ASSOC));
    exit;
  }
  case 'create': {
    $errs = validar($_POST);
    if ($errs) {
      echo json_encode(['error' => 'Validación', 'detalles' => $errs]);
      exit;
    }

    $st = $pdo->prepare("INSERT INTO motivos_no_venta (Clave,Motivo,Status) VALUES (?,?,?)");
    $st->execute([
      trim((string) $_POST['Clave']),
      trim((string) $_POST['Motivo']),
      i1($_POST['Status'] ?? 1)
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
  }
  case 'update': {
    $id = (int) ($_POST['IdMot'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'IdMot requerido']);
      exit;
    }
    $errs = validar($_POST);
    if ($errs) {
      echo json_encode(['error' => 'Validación', 'detalles' => $errs]);
      exit;
    }

    $st = $pdo->prepare("UPDATE motivos_no_venta SET Clave=?, Motivo=?, Status=? WHERE IdMot=? LIMIT 1");
    $st->execute([
      trim((string) $_POST['Clave']),
      trim((string) $_POST['Motivo']),
      i1($_POST['Status'] ?? 1),
      $id
    ]);
    echo json_encode(['success' => true]);
    exit;
  }
  case 'delete': {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'id requerido']);
      exit;
    }
    $pdo->prepare("UPDATE motivos_no_venta SET Status=0 WHERE IdMot=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
  }
  case 'restore': {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'id requerido']);
      exit;
    }
    $pdo->prepare("UPDATE motivos_no_venta SET Status=1 WHERE IdMot=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
  }
  default:
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}
