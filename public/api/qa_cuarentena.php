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
function i0($v)
{
  $v = trim((string) $v);
  return $v === '' ? 0 : (int) $v;
}

function tipo_guardar($v)
{
  $v = trim((string) $v);
  // UI manda "E" o "S" (Entrada/Salida) o ya puede venir "Q"/"S"
  if ($v === 'E')
    return 'Q';
  if ($v === 'Q')
    return 'Q';
  if ($v === 'S')
    return 'S';
  return null;
}

function validar($data)
{
  $errs = [];
  $tipo = tipo_guardar($data['Tipo_Cat'] ?? '');
  $mot = trim((string) ($data['Des_Motivo'] ?? ''));
  if (!$tipo)
    $errs[] = 'Tipo_Cat es obligatorio (Entrada/Salida)';
  if ($mot === '')
    $errs[] = 'Des_Motivo es obligatorio';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
/* =========================
   EXPORT CSV
========================= */
if ($action === 'export_csv') {
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=qa_cuarentena_' . $tipo . '.csv');
  $out = fopen('php://output', 'w');

  // Human Readable Headers
  $headers = ['ID', 'Tipo', 'Motivo', 'Dev. Proveedor', 'Activo'];
  fputcsv($out, $headers);

  if ($tipo === 'datos') {
    $sql = "SELECT id,
            CASE WHEN Tipo_Cat='Q' THEN 'Entrada' WHEN Tipo_Cat='S' THEN 'Salida' ELSE Tipo_Cat END as Tipo,
            Des_Motivo, 
            dev_proveedor, 
            Activo 
            FROM c_motivo ORDER BY id";
    foreach ($pdo->query($sql) as $row)
      fputcsv($out, $row);
  }
  fclose($out);
  exit;
}

/* =========================
   IMPORT CSV (UPSERT por (Tipo + Motivo))
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
  // Remove BOM if present
  if ($headers && isset($headers[0])) {
    $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
  }

  $esperadas = ['ID', 'Tipo', 'Motivo', 'Dev. Proveedor', 'Activo'];
  // Loose check or strict? Let's check overlap or strict. Strict is safest for now.
  if ($headers !== $esperadas) {
    // Try to be lenient if order differs or casing? No, let's enforce layout.
    // Actually, sometimes BOM makes first char weird.
    // Let's just mapping.
  }

  // Map Human -> DB
  $map = [
    'ID' => 'id',
    'Tipo' => 'Tipo_Cat',
    'Motivo' => 'Des_Motivo',
    'Dev. Proveedor' => 'dev_proveedor',
    'Activo' => 'Activo'
  ];

  $stFind = $pdo->prepare("SELECT id FROM c_motivo WHERE Tipo_Cat=? AND Des_Motivo=? LIMIT 1");
  $stIns = $pdo->prepare("INSERT INTO c_motivo (Tipo_Cat, Des_Motivo, dev_proveedor, Activo) VALUES (?,?,?,?)");
  $stUpd = $pdo->prepare("UPDATE c_motivo SET Tipo_Cat=?, Des_Motivo=?, dev_proveedor=?, Activo=? WHERE id=? LIMIT 1");

  $rows_ok = 0;
  $rows_err = 0;
  $errores = [];
  $pdo->beginTransaction();
  try {
    $linea = 1;
    while (($r = fgetcsv($fh)) !== false) {
      $linea++;
      if (!$r || count($r) < 2) { // Require at least Tipo & Motivo essentially
        continue;
      }

      // Associative array based on headers (assuming user follows layout)
      // If headers matched $esperadas, we can combine.
      $rowMap = [];
      if (count($r) === count($headers)) {
        $rowMap = array_combine($headers, $r);
      } else {
        // Fallback positional if completely broken? No, just skip.
        $rows_err++;
        $errores[] = ['fila' => $linea, 'motivo' => 'Columnas no coinciden con cabecera'];
        continue;
      }

      // Convert Human Values to DB
      $tipoStr = trim($rowMap['Tipo'] ?? '');
      // Map Entrada/Salida -> Q/S
      $tipoCat = null;
      if (stripos($tipoStr, 'Entrada') !== false)
        $tipoCat = 'Q';
      else if (stripos($tipoStr, 'Salida') !== false)
        $tipoCat = 'S';
      else if (in_array($tipoStr, ['Q', 'S', 'E']))
        $tipoCat = tipo_guardar($tipoStr);

      $motivo = trim($rowMap['Motivo'] ?? '');
      $devProv = isset($rowMap['Dev. Proveedor']) ? i0($rowMap['Dev. Proveedor']) : 0;
      $activo = isset($rowMap['Activo']) ? i1($rowMap['Activo']) : 1;

      if (!$tipoCat || $motivo === '') {
        $rows_err++;
        $errores[] = ['fila' => $linea, 'motivo' => 'Falta Tipo o Motivo'];
        continue;
      }

      $stFind->execute([$tipoCat, $motivo]);
      $id = (int) ($stFind->fetchColumn() ?: 0);

      if ($id > 0) {
        $stUpd->execute([$tipoCat, $motivo, $devProv, $activo, $id]);
      } else {
        $stIns->execute([$tipoCat, $motivo, $devProv, $activo]);
      }

      $rows_ok++;
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'rows_ok' => $rows_ok, 'rows_err' => $rows_err, 'errores' => $errores]);
    exit;
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
  $tipo = trim((string) ($_GET['tipo'] ?? '')); // 'Q' o 'S' o ''
  $limit = max(1, min(200, (int) ($_GET['limit'] ?? 25)));
  $offset = max(0, (int) ($_GET['offset'] ?? 0));

  $where = " WHERE IFNULL(Activo,1)=:activo ";
  if ($tipo === 'Q' || $tipo === 'S') {
    $where .= " AND Tipo_Cat = :tipo ";
  }
  if ($q !== '') {
    $where .= " AND (Des_Motivo LIKE :q OR CAST(dev_proveedor AS CHAR) LIKE :q OR Tipo_Cat LIKE :q) ";
  }

  $sqlCount = "SELECT COUNT(*) FROM c_motivo $where";
  $stc = $pdo->prepare($sqlCount);
  $stc->bindValue(':activo', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($tipo === 'Q' || $tipo === 'S')
    $stc->bindValue(':tipo', $tipo, PDO::PARAM_STR);
  if ($q !== '')
    $stc->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $stc->execute();
  $total = (int) $stc->fetchColumn();

  $sql = "SELECT id, Tipo_Cat, Des_Motivo, dev_proveedor, Activo
          FROM c_motivo
          $where
          ORDER BY id DESC
          LIMIT $limit OFFSET $offset";
  $st = $pdo->prepare($sql);
  $st->bindValue(':activo', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($tipo === 'Q' || $tipo === 'S')
    $st->bindValue(':tipo', $tipo, PDO::PARAM_STR);
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
    $st = $pdo->prepare("SELECT * FROM c_motivo WHERE id=? LIMIT 1");
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

    $tipoCat = tipo_guardar($_POST['Tipo_Cat'] ?? '');
    $st = $pdo->prepare("
      INSERT INTO c_motivo (Tipo_Cat, Des_Motivo, dev_proveedor, Activo)
      VALUES (?,?,?,?)
    ");
    $st->execute([
      $tipoCat,
      s($_POST['Des_Motivo'] ?? null),
      i0($_POST['dev_proveedor'] ?? 0),
      i1($_POST['Activo'] ?? 1),
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
  }
  case 'update': {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'id requerido']);
      exit;
    }

    $errs = validar($_POST);
    if ($errs) {
      echo json_encode(['error' => 'Validación', 'detalles' => $errs]);
      exit;
    }

    $tipoCat = tipo_guardar($_POST['Tipo_Cat'] ?? '');
    $st = $pdo->prepare("
      UPDATE c_motivo SET
        Tipo_Cat=?, Des_Motivo=?, dev_proveedor=?, Activo=?
      WHERE id=? LIMIT 1
    ");
    $st->execute([
      $tipoCat,
      s($_POST['Des_Motivo'] ?? null),
      i0($_POST['dev_proveedor'] ?? 0),
      i1($_POST['Activo'] ?? 1),
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
    $pdo->prepare("UPDATE c_motivo SET Activo=0 WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
  }
  case 'restore': {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['error' => 'id requerido']);
      exit;
    }
    $pdo->prepare("UPDATE c_motivo SET Activo=1 WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
  }
  default:
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}
