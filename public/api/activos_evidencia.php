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
   SUBIR EVIDENCIA (FOTO / DOCUMENTO)
   ========================================================= */
if ($action === 'upload') {

  $id_activo = (int)($_POST['id_activo'] ?? 0);
  $cve_cia   = (int)($_POST['cve_cia'] ?? 0);
  $tipo      = strtoupper(trim($_POST['tipo'] ?? 'GENERAL')); // ENTREGA | MANTENIMIENTO | DANIO | CONTRATO | GENERAL
  $usuario   = trim($_POST['usuario'] ?? 'sistema');

  if ($id_activo<=0 || $cve_cia<=0) {
    out(false, ['error'=>'id_activo y cve_cia requeridos']);
  }
  if (!isset($_FILES['archivo'])) {
    out(false, ['error'=>'Archivo no recibido']);
  }

  $file = $_FILES['archivo'];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    out(false, ['error'=>'Error al subir archivo']);
  }

  $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $mime = mime_content_type($file['tmp_name']);
  $hash = sha1_file($file['tmp_name']);

  // Directorio destino
  $baseDir = __DIR__ . '/../uploads/activos';
  if (!is_dir($baseDir)) mkdir($baseDir, 0775, true);

  $destDir = $baseDir . '/' . $cve_cia . '/' . $id_activo;
  if (!is_dir($destDir)) mkdir($destDir, 0775, true);

  $fname = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest  = $destDir . '/' . $fname;

  if (!move_uploaded_file($file['tmp_name'], $dest)) {
    out(false, ['error'=>'No se pudo guardar el archivo']);
  }

  // Guardar referencia en BD
  $st = $pdo->prepare("
    INSERT INTO t_activo_archivo
      (id_activo, cve_cia, tipo, path, mime, hash_sha1, usuario, created_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  $st->execute([
    $id_activo,
    $cve_cia,
    $tipo,
    $dest,
    $mime,
    $hash,
    $usuario
  ]);

  out(true, ['archivo'=>$fname]);
}

/* =========================================================
   LISTAR EVIDENCIAS POR ACTIVO
   ========================================================= */
if ($action === 'list') {

  $id_activo = (int)($_GET['id_activo'] ?? 0);
  if ($id_activo<=0) out(false, ['error'=>'id_activo requerido']);

  $st = $pdo->prepare("
    SELECT
      id_archivo,
      tipo,
      path,
      mime,
      hash_sha1,
      usuario,
      created_at
    FROM t_activo_archivo
    WHERE id_activo = ?
      AND deleted_at IS NULL
    ORDER BY created_at DESC
  ");
  $st->execute([$id_activo]);

  out(true, ['data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
}

/* =========================================================
   ELIMINAR EVIDENCIA (SOFT DELETE)
   ========================================================= */
if ($action === 'delete') {

  $id_archivo = (int)($_POST['id_archivo'] ?? 0);
  if ($id_archivo<=0) out(false, ['error'=>'id_archivo requerido']);

  $pdo->prepare("
    UPDATE t_activo_archivo
       SET deleted_at = NOW()
     WHERE id_archivo = ?
  ")->execute([$id_archivo]);

  out(true);
}

out(false, ['error'=>'Acción no válida']);
