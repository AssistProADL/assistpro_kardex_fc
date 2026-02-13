<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

db_pdo();
global $pdo;

function jexit($arr)
{
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function ok($data = [])
{
  jexit(array_merge(['success' => true], $data));
}

function err($msg)
{
  jexit(['success' => false, 'message' => $msg]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* =====================================================
   LIST
===================================================== */
if ($action === 'list') {

  $q = trim($_GET['q'] ?? '');

  $where = "1=1";
  $params = [];

  if ($q !== '') {
    $where .= " AND (Nombre LIKE ?)";
    $params[] = "%$q%";
  }

  $sql = "SELECT * FROM t_vendedores
          WHERE $where
          ORDER BY Id_Vendedor DESC";

  try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    err("Error LIST: " . $e->getMessage());
  }

  $kpi = $pdo->query("
    SELECT 
      COUNT(*) total,
      SUM(CASE WHEN Activo=1 THEN 1 ELSE 0 END) activos,
      SUM(CASE WHEN Activo=0 THEN 1 ELSE 0 END) inactivos
    FROM t_vendedores
  ")->fetch(PDO::FETCH_ASSOC);

  ok([
    'data' => $rows,
    'kpis' => $kpi
  ]);
}

/* =====================================================
   GET
===================================================== */
if ($action === 'get') {

  $id = intval($_GET['id'] ?? 0);
  if (!$id) err('ID inválido');

  $st = $pdo->prepare("SELECT * FROM t_vendedores WHERE Id_Vendedor=?");
  $st->execute([$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) err('No encontrado');

  ok(['data' => $row]);
}

/* =====================================================
   SAVE (INSERT / UPDATE)
===================================================== */
if ($action === 'save') {

  $data = json_decode(file_get_contents("php://input"), true);

  $id = intval($data['Id_Vendedor'] ?? 0);
  $Nombre = trim($data['Nombre'] ?? '');

  if ($Nombre === '') err('Nombre requerido');

  $fields = [
    trim($data['Cve_Vendedor'] ?? ''),
    intval($data['Ban_Ayudante'] ?? 0),
    $Nombre,
    !empty($data['Id_Supervisor']) ? intval($data['Id_Supervisor']) : null,
    $data['CalleNumero'] ?? null,
    $data['Colonia'] ?? null,
    $data['Ciudad'] ?? null,
    $data['Estado'] ?? null,
    $data['Pais'] ?? null,
    $data['CodigoPostal'] ?? null,
    intval($data['Activo'] ?? 1)
  ];


  if ($id > 0) {

    $sql = "UPDATE t_vendedores SET
  Cve_Vendedor=?,
  Ban_Ayudante=?,
  Nombre=?,
  Id_Supervisor=?,
  CalleNumero=?,
  Colonia=?,
  Ciudad=?,
  Estado=?,
  Pais=?,
  CodigoPostal=?,
  Activo=?
  WHERE Id_Vendedor=?";


    $fields[] = $id;

    try {
      $st = $pdo->prepare($sql);
      $st->execute($fields);
    } catch (Exception $e) {
      err("Error UPDATE: " . $e->getMessage());
    }
  } else {

    $sql = "INSERT INTO t_vendedores
  (Cve_Vendedor,Ban_Ayudante,Nombre,Id_Supervisor,
   CalleNumero,Colonia,Ciudad,Estado,Pais,CodigoPostal,Activo)
  VALUES (?,?,?,?,?,?,?,?,?,?,?)";


    try {
      $st = $pdo->prepare($sql);
      $st->execute($fields);
      $id = $pdo->lastInsertId();
    } catch (Exception $e) {
      err("Error INSERT: " . $e->getMessage());
    }
  }

  ok(['Id_Vendedor' => $id]);
}

/* =====================================================
   DELETE LOGICO
===================================================== */
if ($action === 'delete') {

  $id = intval($_GET['id'] ?? 0);
  if (!$id) err('ID inválido');

  $st = $pdo->prepare("UPDATE t_vendedores SET Activo=0 WHERE Id_Vendedor=?");
  $st->execute([$id]);

  ok();
}

/* =====================================================
   RECOVER
===================================================== */
if ($action === 'recover') {

  $id = intval($_GET['id'] ?? 0);
  if (!$id) err('ID inválido');

  $st = $pdo->prepare("UPDATE t_vendedores SET Activo=1 WHERE Id_Vendedor=?");
  $st->execute([$id]);

  ok();
}

err('Acción inválida');

/* =====================================================
   LISTA SUPERVISORES
===================================================== */
if ($action === 'supervisores') {

  $st = $pdo->query("
    SELECT Id_Supervisor, Nombre
    FROM t_supervisores
    WHERE Activo = 1
    ORDER BY Nombre
  ");

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  ok(['data' => $rows]);
}
