<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function out(bool $ok, array $extra = []): void {
  echo json_encode(array_merge(['ok' => $ok ? 1 : 0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

function s($v): ?string {
  $v = trim((string)$v);
  return $v === '' ? null : $v;
}
function i0($v): int {
  return ($v === '' || $v === null) ? 0 : (int)$v;
}
function tbool($v): int {
  if ($v === null) return 0;
  $v = strtoupper(trim((string)$v));
  return ($v === '1' || $v === 'SI' || $v === 'S' || $v === 'TRUE' || $v === 'ON') ? 1 : 0;
}

function col_exists(PDO $pdo, string $table, string $col): bool {
  $db = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
  $st = $pdo->prepare("
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c
  ");
  $st->execute([':db'=>$db, ':t'=>$table, ':c'=>$col]);
  return ((int)$st->fetchColumn()) > 0;
}

function table_exists(PDO $pdo, string $table): bool {
  $db = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
  $st = $pdo->prepare("
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t
  ");
  $st->execute([':db'=>$db, ':t'=>$table]);
  return ((int)$st->fetchColumn()) > 0;
}

/* =========================================================
   META: combos para modal (compañías / almacenes / proveedores)
   ========================================================= */
if ($action === 'meta') {

  // c_compania (legacy suele traer Nombre en mayúscula)
  $comp = [];
  if (table_exists($pdo, 'c_compania')) {
    $compNameCol = col_exists($pdo, 'c_compania', 'Nombre') ? 'Nombre' : (col_exists($pdo, 'c_compania', 'nombre') ? 'nombre' : null);
    $compIdCol   = col_exists($pdo, 'c_compania', 'id_compania') ? 'id_compania' : (col_exists($pdo, 'c_compania', 'ID_Compania') ? 'ID_Compania' : 'id');

    if ($compNameCol) {
      $sql = "SELECT {$compIdCol} AS id, {$compNameCol} AS nombre FROM c_compania ORDER BY {$compNameCol}";
      $comp = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
  }

  // c_almacenp (ya vimos que tiene id/clave/nombre)
  $alm = [];
  if (table_exists($pdo, 'c_almacenp')) {
    $alm = $pdo->query("SELECT id, clave, nombre FROM c_almacenp ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
  }

  // c_proveedores (legacy: ID_Proveedor, Nombre, cve_proveedor)
  $prov = [];
  if (table_exists($pdo, 'c_proveedores')) {
    $provIdCol = col_exists($pdo, 'c_proveedores', 'ID_Proveedor') ? 'ID_Proveedor' : (col_exists($pdo, 'c_proveedores', 'id_proveedor') ? 'id_proveedor' : 'id');
    $provNmCol = col_exists($pdo, 'c_proveedores', 'Nombre') ? 'Nombre' : (col_exists($pdo, 'c_proveedores', 'nombre') ? 'nombre' : null);
    $provCvCol = col_exists($pdo, 'c_proveedores', 'cve_proveedor') ? 'cve_proveedor' : (col_exists($pdo, 'c_proveedores', 'clave') ? 'clave' : null);

    if ($provNmCol) {
      $selClave = $provCvCol ? ", {$provCvCol} AS clave" : ", NULL AS clave";
      $sql = "SELECT {$provIdCol} AS id, {$provNmCol} AS nombre {$selClave} FROM c_proveedores ORDER BY {$provNmCol}";
      $prov = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
  }

  out(true, [
    'companias'  => $comp,
    'almacenes'  => $alm,
    'proveedores'=> $prov
  ]);
}

/* =========================================================
   LISTADO
   ========================================================= */
if ($action === 'list') {

  $solo_activos = tbool($_GET['solo_activos'] ?? '1');
  $q            = s($_GET['q'] ?? null);
  $page         = max(1, i0($_GET['page'] ?? 1));
  $pageSize     = min(200, max(1, i0($_GET['pageSize'] ?? 25)));
  $offset       = ($page - 1) * $pageSize;

  $where = "a.deleted_at IS NULL";
  $params = [];

  if ($solo_activos === 1) {
    $where .= " AND a.activo = 1";
  }

  if ($q) {
    $where .= " AND (
      a.clave LIKE :q OR a.num_serie LIKE :q OR a.marca LIKE :q OR a.modelo LIKE :q OR a.descripcion LIKE :q
    )";
    $params[':q'] = "%{$q}%";
  }

  $sql = "
    SELECT
      a.id_activo,
      a.clave,
      a.id_compania,
      a.id_almacen,
      alm.clave  AS almacen_clave,
      alm.nombre AS almacen_nombre,
      a.tipo_activo,
      a.num_serie,
      a.marca,
      a.modelo,
      a.descripcion,
      a.estatus,
      a.latitud,
      a.longitud,
      a.activo,
      a.proveedor
    FROM c_activos a
    LEFT JOIN c_almacenp alm ON alm.id = a.id_almacen
    WHERE {$where}
    ORDER BY a.id_activo DESC
    LIMIT {$pageSize} OFFSET {$offset}
  ";

  try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $stc = $pdo->prepare("SELECT COUNT(*) FROM c_activos a WHERE {$where}");
    $stc->execute($params);
    $total = (int)$stc->fetchColumn();

    out(true, ['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'pageSize'=>$pageSize]);
  } catch (Throwable $e) {
    out(false, ['error'=>'Error servidor', 'detalle'=>$e->getMessage()]);
  }
}

/* =========================================================
   GET
   ========================================================= */
if ($action === 'get') {
  $id = i0($_GET['id_activo'] ?? 0);
  if ($id <= 0) out(false, ['error'=>'id_activo requerido']);

  $sql = "
    SELECT
      a.*,
      alm.clave  AS almacen_clave,
      alm.nombre AS almacen_nombre
    FROM c_activos a
    LEFT JOIN c_almacenp alm ON alm.id = a.id_almacen
    WHERE a.id_activo = :id AND a.deleted_at IS NULL
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':id'=>$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) out(false, ['error'=>'Activo no encontrado']);
  out(true, ['row'=>$row]);
}

/* =========================================================
   CREATE / UPDATE
   ========================================================= */
if ($action === 'create' || $action === 'update') {

  $id_activo    = i0($_POST['id_activo'] ?? 0);
  $clave        = s($_POST['clave'] ?? '');
  $id_compania  = i0($_POST['id_compania'] ?? 0);
  $id_almacen   = i0($_POST['id_almacen'] ?? 0);
  $tipo_activo  = s($_POST['tipo_activo'] ?? 'OTRO') ?? 'OTRO';
  $marca        = s($_POST['marca'] ?? null);
  $modelo       = s($_POST['modelo'] ?? null);
  $num_serie    = s($_POST['num_serie'] ?? null);
  $descripcion  = s($_POST['descripcion'] ?? null);
  $fecha_compra = s($_POST['fecha_compra'] ?? null);
  $proveedor    = s($_POST['proveedor'] ?? null);
  $factura      = s($_POST['factura'] ?? null);
  $latitud      = s($_POST['latitud'] ?? null);
  $longitud     = s($_POST['longitud'] ?? null);
  $ventas_obj   = s($_POST['ventas_objetivo_mensual'] ?? null);
  $notas_cond   = s($_POST['notas_condicion'] ?? null);
  $estatus      = s($_POST['estatus'] ?? 'ACTIVO') ?? 'ACTIVO';
  $activo       = tbool($_POST['activo'] ?? '1');

  if (!$clave) out(false, ['error'=>'clave requerida']);
  if ($id_compania <= 0) out(false, ['error'=>'id_compania requerido']);
  if ($id_almacen <= 0) out(false, ['error'=>'id_almacen requerido']);
  if (!$num_serie) out(false, ['error'=>'num_serie requerido']);

  try {
    if ($action === 'create') {

      $sql = "
        INSERT INTO c_activos
        (clave, id_compania, id_almacen, tipo_activo, marca, modelo, num_serie, descripcion,
         fecha_compra, proveedor, factura, latitud, longitud, ventas_objetivo_mensual, notas_condicion,
         estatus, activo, created_at)
        VALUES
        (:clave, :id_compania, :id_almacen, :tipo_activo, :marca, :modelo, :num_serie, :descripcion,
         :fecha_compra, :proveedor, :factura, :latitud, :longitud, :ventas_obj, :notas_cond,
         :estatus, :activo, NOW())
      ";

      $st = $pdo->prepare($sql);
      $st->execute([
        ':clave'=>$clave,
        ':id_compania'=>$id_compania,
        ':id_almacen'=>$id_almacen,
        ':tipo_activo'=>$tipo_activo,
        ':marca'=>$marca,
        ':modelo'=>$modelo,
        ':num_serie'=>$num_serie,
        ':descripcion'=>$descripcion,
        ':fecha_compra'=>$fecha_compra,
        ':proveedor'=>$proveedor,
        ':factura'=>$factura,
        ':latitud'=>$latitud,
        ':longitud'=>$longitud,
        ':ventas_obj'=>$ventas_obj,
        ':notas_cond'=>$notas_cond,
        ':estatus'=>$estatus,
        ':activo'=>$activo
      ]);

      out(true, ['id_activo'=>(int)$pdo->lastInsertId()]);
    }

    // update
    if ($id_activo <= 0) out(false, ['error'=>'id_activo requerido']);

    $sql = "
      UPDATE c_activos SET
        clave = :clave,
        id_compania = :id_compania,
        id_almacen = :id_almacen,
        tipo_activo = :tipo_activo,
        marca = :marca,
        modelo = :modelo,
        num_serie = :num_serie,
        descripcion = :descripcion,
        fecha_compra = :fecha_compra,
        proveedor = :proveedor,
        factura = :factura,
        latitud = :latitud,
        longitud = :longitud,
        ventas_objetivo_mensual = :ventas_obj,
        notas_condicion = :notas_cond,
        estatus = :estatus,
        activo = :activo,
        updated_at = NOW()
      WHERE id_activo = :id_activo AND deleted_at IS NULL
    ";
    $st = $pdo->prepare($sql);
    $st->execute([
      ':id_activo'=>$id_activo,
      ':clave'=>$clave,
      ':id_compania'=>$id_compania,
      ':id_almacen'=>$id_almacen,
      ':tipo_activo'=>$tipo_activo,
      ':marca'=>$marca,
      ':modelo'=>$modelo,
      ':num_serie'=>$num_serie,
      ':descripcion'=>$descripcion,
      ':fecha_compra'=>$fecha_compra,
      ':proveedor'=>$proveedor,
      ':factura'=>$factura,
      ':latitud'=>$latitud,
      ':longitud'=>$longitud,
      ':ventas_obj'=>$ventas_obj,
      ':notas_cond'=>$notas_cond,
      ':estatus'=>$estatus,
      ':activo'=>$activo
    ]);

    out(true);

  } catch (Throwable $e) {
    out(false, ['error'=>'Error servidor', 'detalle'=>$e->getMessage()]);
  }
}

/* =========================================================
   DELETE (soft)
   ========================================================= */
if ($action === 'delete') {
  $id = i0($_POST['id_activo'] ?? 0);
  if ($id <= 0) out(false, ['error'=>'id_activo requerido']);

  try {
    $st = $pdo->prepare("UPDATE c_activos SET deleted_at = NOW() WHERE id_activo = :id");
    $st->execute([':id'=>$id]);
    out(true);
  } catch (Throwable $e) {
    out(false, ['error'=>'Error servidor', 'detalle'=>$e->getMessage()]);
  }
}

out(false, ['error'=>'Acción no válida']);
