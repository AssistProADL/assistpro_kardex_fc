<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function out($ok, $extra = []) {
  echo json_encode(array_merge(['ok' => $ok ? 1 : 0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================================================
   LISTADO DE ACTIVOS (con filtros)
   ========================================================= */
if ($action === 'list') {

  $cve_cia     = (int)($_GET['cve_cia'] ?? 0);
  $id_almacenp = (int)($_GET['id_almacenp'] ?? 0);
  $id_estado   = (int)($_GET['id_estado'] ?? 0);
  $id_tipo     = (int)($_GET['id_tipo'] ?? 0);
  $page        = max(1, (int)($_GET['page'] ?? 1));
  $pageSize    = min(100, max(1, (int)($_GET['pageSize'] ?? 25)));
  $offset      = ($page - 1) * $pageSize;

  if ($cve_cia <= 0) {
    out(false, ['error' => 'cve_cia requerido']);
  }

  $where = "a.deleted_at IS NULL AND a.cve_cia = :cve_cia";
  $params = [':cve_cia' => $cve_cia];

  if ($id_estado > 0) {
    $where .= " AND a.id_estado = :id_estado";
    $params[':id_estado'] = $id_estado;
  }
  if ($id_tipo > 0) {
    $where .= " AND a.id_tipo = :id_tipo";
    $params[':id_tipo'] = $id_tipo;
  }
  if ($id_almacenp > 0) {
    $where .= " AND u.id_almacenp = :id_almacenp";
    $params[':id_almacenp'] = $id_almacenp;
  }

  $sql = "
    SELECT
      a.id_activo,
      a.numero_serie,
      a.cve_cia,
      a.id_estado,
      est.nombre  AS estado,
      est.semaforo,
      a.id_tipo,
      t.nombre    AS tipo,
      u.id_almacenp,
      alm.nombre  AS almacen,
      u.latitud,
      u.longitud
    FROM c_activos a
    JOIN c_activo_estado est ON est.id_estado = a.id_estado
    JOIN c_activo_tipo t     ON t.id_tipo = a.id_tipo
    LEFT JOIN t_activo_ubicacion u 
           ON u.id_activo = a.id_activo AND u.vigencia = 1 AND u.deleted_at IS NULL
    LEFT JOIN c_almacenp alm ON alm.id = u.id_almacenp
    WHERE $where
    ORDER BY a.id_activo DESC
    LIMIT $pageSize OFFSET $offset
  ";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Total para paginación
  $stc = $pdo->prepare("
    SELECT COUNT(*)
    FROM c_activos a
    LEFT JOIN t_activo_ubicacion u 
      ON u.id_activo = a.id_activo AND u.vigencia = 1 AND u.deleted_at IS NULL
    WHERE $where
  ");
  $stc->execute($params);
  $total = (int)$stc->fetchColumn();

  out(true, [
    'page' => $page,
    'pageSize' => $pageSize,
    'total' => $total,
    'data' => $rows
  ]);
}

/* =========================================================
   OBTENER ACTIVO POR ID
   ========================================================= */
if ($action === 'get') {

  $id = (int)($_GET['id_activo'] ?? 0);
  if ($id <= 0) out(false, ['error' => 'id_activo requerido']);

  $sql = "
    SELECT
      a.*,
      est.nombre AS estado,
      est.semaforo,
      t.nombre AS tipo
    FROM c_activos a
    JOIN c_activo_estado est ON est.id_estado = a.id_estado
    JOIN c_activo_tipo t ON t.id_tipo = a.id_tipo
    WHERE a.id_activo = :id
      AND a.deleted_at IS NULL
  ";

  $st = $pdo->prepare($sql);
  $st->execute([':id' => $id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) out(false, ['error' => 'Activo no encontrado']);
  out(true, ['data' => $row]);
}

/* =========================================================
   CREAR ACTIVO
   ========================================================= */
if ($action === 'create') {

  $sql = "
    INSERT INTO c_activos
      (cve_cia, numero_serie, id_tipo, id_estado, created_at)
    VALUES
      (:cve_cia, :serie, :id_tipo, :id_estado, NOW())
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':cve_cia'   => (int)$_POST['cve_cia'],
    ':serie'     => trim($_POST['numero_serie']),
    ':id_tipo'   => (int)$_POST['id_tipo'],
    ':id_estado' => (int)$_POST['id_estado']
  ]);

  out(true, ['id_activo' => (int)$pdo->lastInsertId()]);
}

/* =========================================================
   ACTUALIZAR ACTIVO
   ========================================================= */
if ($action === 'update') {

  $id = (int)$_POST['id_activo'];
  if ($id <= 0) out(false, ['error' => 'id_activo requerido']);

  $sql = "
    UPDATE c_activos SET
      id_tipo   = :id_tipo,
      id_estado = :id_estado,
      updated_at = NOW()
    WHERE id_activo = :id
      AND deleted_at IS NULL
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':id'        => $id,
    ':id_tipo'   => (int)$_POST['id_tipo'],
    ':id_estado' => (int)$_POST['id_estado']
  ]);

  out(true);
}

/* =========================================================
   SOFT DELETE
   ========================================================= */
if ($action === 'delete') {

  $id = (int)$_POST['id_activo'];
  if ($id <= 0) out(false, ['error' => 'id_activo requerido']);

  $pdo->prepare("
    UPDATE c_activos
    SET deleted_at = NOW()
    WHERE id_activo = ?
  ")->execute([$id]);

  out(true);
}

out(false, ['error' => 'Acción no válida']);
