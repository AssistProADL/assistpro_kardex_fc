<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db_pdo();

  $idEmpresa = trim($_POST['IdEmpresa'] ?? $_POST['idEmpresa'] ?? '');
  $rutaNueva = (int)($_POST['ruta_nueva'] ?? 0);
  $idsRaw    = trim($_POST['ids_destinatario'] ?? '');

  if ($idEmpresa === '' || $rutaNueva <= 0 || $idsRaw === '') {
    echo json_encode(['error' => 'Parámetros requeridos: IdEmpresa, ruta_nueva, ids_destinatario']);
    exit;
  }

  $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw))));
  if (!$ids) {
    echo json_encode(['error' => 'Lista de IDs vacía']);
    exit;
  }

  // Días (global) opcional: Lu,Ma,Mi,Ju,Vi,Sa,Do
  $dias = [
    'Lu' => (int)($_POST['Lu'] ?? 0),
    'Ma' => (int)($_POST['Ma'] ?? 0),
    'Mi' => (int)($_POST['Mi'] ?? 0),
    'Ju' => (int)($_POST['Ju'] ?? 0),
    'Vi' => (int)($_POST['Vi'] ?? 0),
    'Sa' => (int)($_POST['Sa'] ?? 0),
    'Do' => (int)($_POST['Do'] ?? 0),
  ];

  $pdo->beginTransaction();

  // 1) relclirutas: mover a rutaNueva
  $in = implode(',', array_fill(0, count($ids), '?'));
  $sqlRel = "UPDATE relclirutas
             SET IdRuta = ?, Fecha = CURDATE()
             WHERE IdEmpresa = ? AND IdCliente IN ($in)";
  $paramsRel = array_merge([$rutaNueva, $idEmpresa], $ids);
  $st = $pdo->prepare($sqlRel);
  $st->execute($paramsRel);
  $affRel = $st->rowCount();

  // 2) reldaycli: heredar nueva ruta + (opcional) días globales
  // Si reldaycli tiene el destinatario en Id_Destinatario y almacén en Cve_Almac:
  $setDias = "";
  $paramsDay = [$rutaNueva];

  if (array_sum($dias) > 0) {
    $setDias = ", Lu=?, Ma=?, Mi=?, Ju=?, Vi=?, Sa=?, Do=?";
    $paramsDay = array_merge($paramsDay, [$dias['Lu'],$dias['Ma'],$dias['Mi'],$dias['Ju'],$dias['Vi'],$dias['Sa'],$dias['Do']]);
  }

  $sqlDay = "UPDATE reldaycli
             SET Cve_Ruta = ? $setDias
             WHERE Cve_Almac = ? AND Id_Destinatario IN ($in)";

  $paramsDay = array_merge($paramsDay, [$idEmpresa], $ids);

  $st2 = $pdo->prepare($sqlDay);
  $st2->execute($paramsDay);
  $affDay = $st2->rowCount();

  $pdo->commit();

  echo json_encode([
    'ok' => true,
    'movidos_relclirutas' => $affRel,
    'actualizados_reldaycli' => $affDay
  ]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  echo json_encode([
    'error' => 'Error aplicando reasignación',
    'detalle' => $e->getMessage()
  ]);
}
