<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function out($ok, $extra = []) {
  echo json_encode(array_merge(['ok'=>$ok?1:0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================================================
   MOVER ACTIVO / CAMBIAR UBICACIÓN (TRANSACCIONAL)
   ========================================================= */
if ($action === 'mover') {

  $id_activo      = (int)($_POST['id_activo'] ?? 0);
  $cve_cia        = (int)($_POST['cve_cia'] ?? 0);
  $id_almacenp    = $_POST['id_almacenp'] ?? null; // puede ser null
  $id_cliente     = $_POST['id_cliente'] ?? null;  // puede ser null
  $id_destinatario= $_POST['id_destinatario'] ?? null;
  $latitud        = $_POST['latitud'] ?? null;
  $longitud       = $_POST['longitud'] ?? null;
  $usuario        = trim($_POST['usuario'] ?? 'sistema');

  if ($id_activo<=0 || $cve_cia<=0) {
    out(false, ['error'=>'id_activo y cve_cia son obligatorios']);
  }

  try {
    $pdo->beginTransaction();

    /* 1) Cerrar ubicación vigente */
    $pdo->prepare("
      UPDATE t_activo_ubicacion
         SET vigencia = 0,
             fecha_hasta = NOW(),
             updated_at = NOW()
       WHERE id_activo = ?
         AND vigencia = 1
         AND deleted_at IS NULL
    ")->execute([$id_activo]);

    /* 2) Insertar nueva ubicación */
    $stIns = $pdo->prepare("
      INSERT INTO t_activo_ubicacion
        (id_activo, cve_cia, id_almacenp, id_cliente, id_destinatario,
         latitud, longitud, vigencia, fecha_desde, created_at)
      VALUES
        (:id_activo, :cve_cia, :id_almacenp, :id_cliente, :id_destinatario,
         :latitud, :longitud, 1, NOW(), NOW())
    ");

    $stIns->execute([
      ':id_activo'       => $id_activo,
      ':cve_cia'         => $cve_cia,
      ':id_almacenp'     => $id_almacenp ?: null,
      ':id_cliente'      => $id_cliente ?: null,
      ':id_destinatario' => $id_destinatario ?: null,
      ':latitud'         => $latitud,
      ':longitud'        => $longitud
    ]);

    /* 3) Registrar evento */
    $pdo->prepare("
      INSERT INTO t_activo_evento
        (id_activo, cve_cia, tipo_evento, descripcion,
         latitud, longitud, created_at)
      VALUES
        (?, ?, 'REASIGNACION',
         CONCAT('Cambio de ubicación por ', ?),
         ?, ?, NOW())
    ")->execute([
      $id_activo,
      $cve_cia,
      $usuario,
      $latitud,
      $longitud
    ]);

    $pdo->commit();
    out(true);

  } catch (Throwable $e) {
    $pdo->rollBack();
    out(false, ['error'=>$e->getMessage()]);
  }
}

/* =========================================================
   OBTENER HISTÓRICO DE UBICACIÓN
   ========================================================= */
if ($action === 'historial') {

  $id_activo = (int)($_GET['id_activo'] ?? 0);
  if ($id_activo<=0) out(false, ['error'=>'id_activo requerido']);

  $sql = "
    SELECT
      u.id,
      u.id_almacenp,
      alm.nombre AS almacen,
      u.id_cliente,
      u.id_destinatario,
      u.latitud,
      u.longitud,
      u.fecha_desde,
      u.fecha_hasta,
      u.vigencia
    FROM t_activo_ubicacion u
    LEFT JOIN c_almacenp alm ON alm.id = u.id_almacenp
    WHERE u.id_activo = ?
      AND u.deleted_at IS NULL
    ORDER BY u.fecha_desde DESC
  ";

  $st = $pdo->prepare($sql);
  $st->execute([$id_activo]);
  out(true, ['data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
}

out(false, ['error'=>'Acción no válida']);
