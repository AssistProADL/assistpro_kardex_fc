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
function dnull($v)
{
  return ($v === '' || $v === null) ? null : (float) $v;
}

function validar_obligatorios($data)
{
  $errs = [];
  $alm = trim((string) ($data['cve_almac'] ?? ''));
  $clv = trim((string) ($data['Clave_Contenedor'] ?? ''));
  if ($alm === '')
    $errs[] = 'cve_almac es obligatorio';
  if ($clv === '')
    $errs[] = 'Clave_Contenedor es obligatorio';
  return $errs;
}

function table_exists($pdo, $name)
{
  $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
  $st->execute([$name]);
  return (int) $st->fetchColumn() > 0;
}

function almac_id_from_clave($pdo, $almac_clave)
{
  $almac_clave = trim((string) $almac_clave);
  if ($almac_clave === '')
    return 0;
  $st = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave=? LIMIT 1");
  $st->execute([$almac_clave]);
  return (int) ($st->fetchColumn() ?: 0);
}

/* =====================================================
 * EXPORT CSV (layout / datos)
 * ===================================================== */
if ($action === 'export_csv') {
  $tipo = $_GET['tipo'] ?? 'layout';

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=pallets_contenedores_' . $tipo . '.csv');

  $out = fopen('php://output', 'w');

  $headers = [
    'cve_almac',
    'Clave_Contenedor',
    'descripcion',
    'Permanente',
    'Pedido',
    'sufijo',
    'tipo',
    'Activo',
    'alto',
    'ancho',
    'fondo',
    'peso',
    'pesomax',
    'capavol',
    'Costo',
    'CveLP',
    'TipoGen'
  ];
  fputcsv($out, $headers);

  if ($tipo === 'datos') {
    $sql = "SELECT " . implode(',', $headers) . " FROM c_charolas WHERE IFNULL(Activo,1)=1 ORDER BY cve_almac, Clave_Contenedor";
    foreach ($pdo->query($sql) as $row)
      fputcsv($out, $row);
  }

  fclose($out);
  exit;
}

/* =====================================================
 * IMPORT CSV (UPSERT por Clave_Contenedor)
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
    'cve_almac',
    'Clave_Contenedor',
    'descripcion',
    'Permanente',
    'Pedido',
    'sufijo',
    'tipo',
    'Activo',
    'alto',
    'ancho',
    'fondo',
    'peso',
    'pesomax',
    'capavol',
    'Costo',
    'CveLP',
    'TipoGen'
  ];

  if ($headers !== $esperadas) {
    echo json_encode(['error' => 'Layout incorrecto', 'esperado' => $esperadas, 'recibido' => $headers]);
    exit;
  }

  $stFind = $pdo->prepare("SELECT IDContenedor FROM c_charolas WHERE Clave_Contenedor=? LIMIT 1");

  $stIns = $pdo->prepare("
    INSERT INTO c_charolas
    (cve_almac,Clave_Contenedor,descripcion,Permanente,Pedido,sufijo,tipo,Activo,alto,ancho,fondo,peso,pesomax,capavol,Costo,CveLP,TipoGen)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");

  $stUpd = $pdo->prepare("
    UPDATE c_charolas SET
      cve_almac=?,descripcion=?,Permanente=?,Pedido=?,sufijo=?,tipo=?,Activo=?,
      alto=?,ancho=?,fondo=?,peso=?,pesomax=?,capavol=?,Costo=?,CveLP=?,TipoGen=?
    WHERE Clave_Contenedor=?
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
      if (!$r || count($r) < 17) {
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

      $cve_almac = (int) $data['cve_almac'];
      $Clave_Contenedor = trim((string) $data['Clave_Contenedor']);

      $descripcion = s($data['descripcion']);
      $Permanente = i0($data['Permanente']);
      $Pedido = s($data['Pedido']);
      $sufijo = ($data['sufijo'] === '' ? null : (int) $data['sufijo']);
      $tipo = s($data['tipo']);
      $Activo = i1($data['Activo']);

      $alto = ($data['alto'] === '' ? null : (int) $data['alto']);
      $ancho = ($data['ancho'] === '' ? null : (int) $data['ancho']);
      $fondo = ($data['fondo'] === '' ? null : (int) $data['fondo']);

      $peso = dnull($data['peso']);
      $pesomax = dnull($data['pesomax']);
      $capavol = dnull($data['capavol']);
      $Costo = dnull($data['Costo']);

      $CveLP = s($data['CveLP']);
      $TipoGen = ($data['TipoGen'] === '' ? null : (int) $data['TipoGen']);

      $stFind->execute([$Clave_Contenedor]);
      $existe = $stFind->fetchColumn();

      if ($existe) {
        $stUpd->execute([
          $cve_almac,
          $descripcion,
          $Permanente,
          $Pedido,
          $sufijo,
          $tipo,
          $Activo,
          $alto,
          $ancho,
          $fondo,
          $peso,
          $pesomax,
          $capavol,
          $Costo,
          $CveLP,
          $TipoGen,
          $Clave_Contenedor
        ]);
      } else {
        $stIns->execute([
          $cve_almac,
          $Clave_Contenedor,
          $descripcion,
          $Permanente,
          $Pedido,
          $sufijo,
          $tipo,
          $Activo,
          $alto,
          $ancho,
          $fondo,
          $peso,
          $pesomax,
          $capavol,
          $Costo,
          $CveLP,
          $TipoGen
        ]);
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
 * LIST + SEARCH + PAGINACIÓN
 * Params:
 *   almac_clave=WH1
 *   inactivos=0/1
 *   q=...
 *   page=1..n
 *   per_page=25 (default)
 * ===================================================== */
if ($action === 'list') {
  $inactivos = (int) ($_GET['inactivos'] ?? 0);

  $almac_clave = trim((string) ($_GET['almac_clave'] ?? ''));
  $alm_id = $almac_clave !== '' ? almac_id_from_clave($pdo, $almac_clave) : 0;

  $q = trim((string) ($_GET['q'] ?? ''));

  $page = max(1, (int) ($_GET['page'] ?? 1));
  $per_page = (int) ($_GET['per_page'] ?? 25);
  if ($per_page <= 0)
    $per_page = 25;
  if ($per_page > 200)
    $per_page = 200;

  $offset = ($page - 1) * $per_page;

  $where = "WHERE IFNULL(ch.Activo,1)=:activo";
  if ($alm_id > 0)
    $where .= " AND ch.cve_almac=:alm_id";
  if ($q !== '') {
    $where .= " AND (
      ch.Clave_Contenedor LIKE :q1 OR ch.descripcion LIKE :q2 OR ch.Pedido LIKE :q3 OR ch.tipo LIKE :q4 OR
      ch.CveLP LIKE :q5 OR CAST(ch.sufijo AS CHAR) LIKE :q6
    )";
  }

  // Total
  $sqlCount = "
    SELECT COUNT(*)
    FROM c_charolas ch
    LEFT JOIN c_almacenp ap ON ap.id = ch.cve_almac
    $where
  ";
  $stC = $pdo->prepare($sqlCount);
  $stC->bindValue(':activo', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($alm_id > 0)
    $stC->bindValue(':alm_id', $alm_id, PDO::PARAM_INT);
  if ($q !== '') {
    $qv = "%$q%";
    for ($i = 1; $i <= 6; $i++)
      $stC->bindValue(":q$i", $qv, PDO::PARAM_STR);
  }
  $stC->execute();
  $total = (int) $stC->fetchColumn();

  $total_paginas = $total > 0 ? (int) ceil($total / $per_page) : 1;
  if ($page > $total_paginas)
    $page = $total_paginas;
  $offset = ($page - 1) * $per_page;

  // Datos (incluye clave/nombre almacén)
  $sql = "
    SELECT
      ch.IDContenedor,ch.cve_almac,
      ap.clave AS almac_clave,
      ap.nombre AS almac_nombre,
      ch.Clave_Contenedor,ch.descripcion,ch.Permanente,ch.Pedido,ch.sufijo,ch.tipo,ch.Activo,
      ch.alto,ch.ancho,ch.fondo,ch.peso,ch.pesomax,ch.capavol,ch.Costo,ch.CveLP,ch.TipoGen
    FROM c_charolas ch
    LEFT JOIN c_almacenp ap ON ap.id = ch.cve_almac
    $where
    ORDER BY ap.clave, ch.Clave_Contenedor
    LIMIT $per_page OFFSET $offset
  ";

  $st = $pdo->prepare($sql);
  $st->bindValue(':activo', $inactivos ? 0 : 1, PDO::PARAM_INT);
  if ($alm_id > 0)
    $st->bindValue(':alm_id', $alm_id, PDO::PARAM_INT);
  if ($q !== '') {
    $qv = "%$q%";
    for ($i = 1; $i <= 6; $i++)
      $st->bindValue(":q$i", $qv, PDO::PARAM_STR);
  }
  $st->execute();

  echo json_encode([
    'almac_clave' => $almac_clave,
    'alm_id' => $alm_id,
    'q' => $q,
    'pagina' => $page,
    'por_pagina' => $per_page,
    'total' => $total,
    'total_paginas' => $total_paginas,
    'rows' => $st->fetchAll()
  ]);
  exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE
 * ===================================================== */
switch ($action) {

  case 'get': {
    $id = $_GET['IDContenedor'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'IDContenedor requerido']);
      exit;
    }
    $st = $pdo->prepare("SELECT * FROM c_charolas WHERE IDContenedor=?");
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
      INSERT INTO c_charolas
      (cve_almac,Clave_Contenedor,descripcion,Permanente,Pedido,sufijo,tipo,Activo,
       alto,ancho,fondo,peso,pesomax,capavol,Costo,CveLP,TipoGen)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $st->execute([
      (int) $_POST['cve_almac'],
      trim((string) $_POST['Clave_Contenedor']),
      s($_POST['descripcion'] ?? null),
      i0($_POST['Permanente'] ?? 0),
      s($_POST['Pedido'] ?? null),
      ($_POST['sufijo'] ?? '') === '' ? null : (int) $_POST['sufijo'],
      s($_POST['tipo'] ?? null),
      i1($_POST['Activo'] ?? 1),
      ($_POST['alto'] ?? '') === '' ? null : (int) $_POST['alto'],
      ($_POST['ancho'] ?? '') === '' ? null : (int) $_POST['ancho'],
      ($_POST['fondo'] ?? '') === '' ? null : (int) $_POST['fondo'],
      dnull($_POST['peso'] ?? null),
      dnull($_POST['pesomax'] ?? null),
      dnull($_POST['capavol'] ?? null),
      dnull($_POST['Costo'] ?? null),
      s($_POST['CveLP'] ?? null),
      ($_POST['TipoGen'] ?? '') === '' ? null : (int) $_POST['TipoGen']
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    break;
  }

  case 'update': {
    $id = $_POST['IDContenedor'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'IDContenedor requerido']);
      exit;
    }

    $errs = validar_obligatorios($_POST);
    if ($errs) {
      echo json_encode(['error' => 'Validación', 'detalles' => $errs]);
      exit;
    }

    $st = $pdo->prepare("
      UPDATE c_charolas SET
        cve_almac=?,Clave_Contenedor=?,descripcion=?,Permanente=?,Pedido=?,sufijo=?,tipo=?,Activo=?,
        alto=?,ancho=?,fondo=?,peso=?,pesomax=?,capavol=?,Costo=?,CveLP=?,TipoGen=?
      WHERE IDContenedor=?
      LIMIT 1
    ");

    $st->execute([
      (int) $_POST['cve_almac'],
      trim((string) $_POST['Clave_Contenedor']),
      s($_POST['descripcion'] ?? null),
      i0($_POST['Permanente'] ?? 0),
      s($_POST['Pedido'] ?? null),
      ($_POST['sufijo'] ?? '') === '' ? null : (int) $_POST['sufijo'],
      s($_POST['tipo'] ?? null),
      i1($_POST['Activo'] ?? 1),
      ($_POST['alto'] ?? '') === '' ? null : (int) $_POST['alto'],
      ($_POST['ancho'] ?? '') === '' ? null : (int) $_POST['ancho'],
      ($_POST['fondo'] ?? '') === '' ? null : (int) $_POST['fondo'],
      dnull($_POST['peso'] ?? null),
      dnull($_POST['pesomax'] ?? null),
      dnull($_POST['capavol'] ?? null),
      dnull($_POST['Costo'] ?? null),
      s($_POST['CveLP'] ?? null),
      ($_POST['TipoGen'] ?? '') === '' ? null : (int) $_POST['TipoGen'],
      (int) $id
    ]);

    echo json_encode(['success' => true]);
    break;
  }

  case 'delete': {
    $id = $_POST['IDContenedor'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'IDContenedor requerido']);
      exit;
    }
    $pdo->prepare("UPDATE c_charolas SET Activo=0 WHERE IDContenedor=?")->execute([(int) $id]);
    echo json_encode(['success' => true]);
    break;
  }

  case 'restore': {
    $id = $_POST['IDContenedor'] ?? null;
    if (!$id) {
      echo json_encode(['error' => 'IDContenedor requerido']);
      exit;
    }
    $pdo->prepare("UPDATE c_charolas SET Activo=1 WHERE IDContenedor=?")->execute([(int) $id]);
    echo json_encode(['success' => true]);
    break;
  }

  default:
    echo json_encode(['error' => 'Acción no válida']);
}
