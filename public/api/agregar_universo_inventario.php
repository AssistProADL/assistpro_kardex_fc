<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$folio = trim($input['folio'] ?? '');
$ubicaciones = $input['ubicaciones'] ?? [];

if ($folio === '' || !is_array($ubicaciones) || !count($ubicaciones)) {
  echo json_encode([
    'ok' => false,
    'error' => 'Datos incompletos'
  ]);
  exit;
}

try {
  db_begin();

  // Verificar que el inventario exista y esté en BORRADOR
  $inv = db_row(
    "SELECT estado FROM th_inventario WHERE folio = ? FOR UPDATE",
    [$folio]
  );

  if (!$inv) {
    throw new Exception('Inventario no encontrado');
  }

  if ($inv['estado'] !== 'BORRADOR') {
    throw new Exception('El inventario ya fue planeado');
  }

  // Insertar BL seleccionados
  $sqlInsert = "
    INSERT INTO t_ubicacionesainventariar
      (folio, idy_ubica, estatus, fecha_registro)
    VALUES
      (?, ?, 'PENDIENTE', NOW())
  ";

  foreach ($ubicaciones as $idUbica) {
    dbq($sqlInsert, [$folio, (int)$idUbica]);
  }

  // Cambiar estado del inventario → PLANEADO
  dbq(
    "UPDATE th_inventario
     SET estado = 'PLANEADO'
     WHERE folio = ?",
    [$folio]
  );

  db_commit();

  echo json_encode([
    'ok' => true,
    'mensaje' => 'Universo asignado correctamente'
  ]);

} catch (Throwable $e) {
  db_rollback();
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ]);
}
