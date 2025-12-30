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
   CREAR MANTENIMIENTO
   ========================================================= */
if ($action === 'create') {

  $id_activo   = (int)($_POST['id_activo'] ?? 0);
  $cve_cia     = (int)($_POST['cve_cia'] ?? 0);
  $tipo        = $_POST['tipo'] ?? 'PREVENTIVO'; // PREVENTIVO | CORRECTIVO
  $proveedor   = trim($_POST['proveedor'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $usuario     = trim($_POST['usuario'] ?? 'sistema');

  if ($id_activo<=0 || $cve_cia<=0) {
    out(false, ['error'=>'id_activo y cve_cia requeridos']);
  }

  try {
    $pdo->beginTransaction();

    // 1) Insertar mantenimiento
    $st = $pdo->prepare("
      INSERT INTO t_activo_mantenimiento
        (id_activo, cve_cia, tipo, estatus, proveedor,
         descripcion, fecha_programada, created_at)
      VALUES
        (?, ?, ?, 'EN_PROCESO', ?, ?, NOW(), NOW())
    ");
    $st->execute([
      $id_activo,
      $cve_cia,
      strtoupper($tipo),
      $proveedor,
      $descripcion
    ]);

    // 2) Cambiar estado del activo a EN MANTENIMIENTO
    $pdo->prepare("
      UPDATE c_activos
         SET id_estado = (
           SELECT id_estado FROM c_activo_estado
            WHERE nombre = 'EN MANTENIMIENTO' LIMIT 1
         ),
         updated_at = NOW()
       WHERE id_activo = ?
    ")->execute([$id_activo]);

    // 3) Registrar evento
    $pdo->prepare("
      INSERT INTO t_activo_evento
        (id_activo, cve_cia, tipo_evento, descripcion, created_at)
      VALUES
        (?, ?, 'MANTENIMIENTO_IN',
         CONCAT('Mantenimiento ', ?, ' iniciado por ', ?),
         NOW())
    ")->execute([
      $id_activo,
      $cve_cia,
      strtoupper($tipo),
      $usuario
    ]);

    $pdo->commit();
    out(true);

  } catch (Throwable $e) {
    $pdo->rollBack();
    out(false, ['error'=>$e->getMessage()]);
  }
}

/* =========================================================
   CERRAR MANTENIMIENTO
   ========================================================= */
if ($action === 'close') {

  $id_mant     = (int)($_POST['id_mant'] ?? 0);
  $id_activo   = (int)($_POST['id_activo'] ?? 0);
  $cve_cia     = (int)($_POST['cve_cia'] ?? 0);
  $resultado   = trim($_POST['resultado'] ?? '');
  $costo       = $_POST['costo'] ?? null;
  $usuario     = trim($_POST['usuario'] ?? 'sistema');

  if ($id_mant<=0 || $id_activo<=0 || $cve_cia<=0) {
    out(false, ['error'=>'Parámetros requeridos incompletos']);
  }

  try {
    $pdo->beginTransaction();

    // 1) Cerrar mantenimiento
    $pdo->prepare("
      UPDATE t_activo_mantenimiento
         SET estatus = 'CERRADO',
             fecha_fin = NOW(),
             costo = ?,
             resultado = ?,
             updated_at = NOW()
       WHERE id_mtto = ?
    ")->execute([$costo, $resultado, $id_mant]);

    // 2) Regresar activo a DISPONIBLE
    $pdo->prepare("
      UPDATE c_activos
         SET id_estado = (
           SELECT id_estado FROM c_activo_estado
            WHERE nombre = 'DISPONIBLE' LIMIT 1
         ),
         updated_at = NOW()
       WHERE id_activo = ?
    ")->execute([$id_activo]);

    // 3) Evento
    $pdo->prepare("
      INSERT INTO t_activo_evento
        (id_activo, cve_cia, tipo_evento, descripcion, created_at)
      VALUES
        (?, ?, 'MANTENIMIENTO_OUT',
         CONCAT('Mantenimiento cerrado por ', ?),
         NOW())
    ")->execute([
      $id_activo,
      $cve_cia,
      $usuario
    ]);

    $pdo->commit();
    out(true);

  } catch (Throwable $e) {
    $pdo->rollBack();
    out(false, ['error'=>$e->getMessage()]);
  }
}

/* =========================================================
   LISTAR MANTENIMIENTOS POR ACTIVO
   ========================================================= */
if ($action === 'list') {

  $id_activo = (int)($_GET['id_activo'] ?? 0);
  if ($id_activo<=0) out(false, ['error'=>'id_activo requerido']);

  $st = $pdo->prepare("
    SELECT
      id_mtto,
      tipo,
      estatus,
      proveedor,
      costo,
      descripcion,
      resultado,
      fecha_programada,
      fecha_inicio,
      fecha_fin
    FROM t_activo_mantenimiento
    WHERE id_activo = ?
      AND deleted_at IS NULL
    ORDER BY created_at DESC
  ");
  $st->execute([$id_activo]);

  out(true, ['data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
}

out(false, ['error'=>'Acción no válida']);
