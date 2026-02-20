<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

function jexit(array $arr): void {
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================
   CONEXIÓN PDO
   ========================= */
$pdo = null;
$tryPaths = [
  __DIR__ . '/../../../app/db.php',
  __DIR__ . '/../../app/db.php',
  __DIR__ . '/../../../includes/db.php',
  __DIR__ . '/../../includes/db.php',
];

foreach ($tryPaths as $p) {
  if (file_exists($p)) { require_once $p; break; }
}

if (isset($pdo) && $pdo instanceof PDO) {
  // ok
} elseif (isset($db) && $db instanceof PDO) {
  $pdo = $db;
} elseif (function_exists('db')) {
  $pdo = db();
} elseif (function_exists('db_conn')) {
  $pdo = db_conn();
}

if (!$pdo || !($pdo instanceof PDO)) {
  jexit(['ok'=>0,'error'=>'No se pudo inicializar PDO']);
}

/* =========================
   LECTURA PAYLOAD
   ========================= */
$raw = file_get_contents('php://input');
$payload = $raw ? json_decode($raw, true) : $_POST;

if (!$payload) {
  jexit(['ok'=>0,'error'=>'Payload vacío']);
}

$almacen = trim((string)($payload['almacen'] ?? ''));
$ruta    = trim((string)($payload['ruta'] ?? ''));
$items   = $payload['items'] ?? [];

if ($almacen === '' || $ruta === '' || !is_array($items)) {
  jexit(['ok'=>0,'error'=>'Parámetros incompletos']);
}

/* =========================
   HELPERS
   ========================= */
function daysFromItem(array $it): array {
  return [
    'Lu'=>!empty($it['Lu']) ? 1 : 0,
    'Ma'=>!empty($it['Ma']) ? 1 : 0,
    'Mi'=>!empty($it['Mi']) ? 1 : 0,
    'Ju'=>!empty($it['Ju']) ? 1 : 0,
    'Vi'=>!empty($it['Vi']) ? 1 : 0,
    'Sa'=>!empty($it['Sa']) ? 1 : 0,
    'Do'=>!empty($it['Do']) ? 1 : 0,
  ];
}

function isTruthy($v): bool {
  if (is_bool($v)) return $v;
  if (is_numeric($v)) return ((int)$v) !== 0;
  return in_array(strtolower((string)$v), ['1','true','si','sí','on','x'], true);
}

/* =========================
   TRANSACCIÓN
   ========================= */
try {

  $pdo->beginTransaction();

  $delDay = $pdo->prepare("
    DELETE FROM reldaycli
     WHERE Cve_Almac = ?
       AND Cve_Ruta  = ?
       AND Id_Destinatario = ?
  ");

  $insDay = $pdo->prepare("
    INSERT INTO reldaycli
      (Cve_Almac, Cve_Ruta, Cve_Cliente, Id_Destinatario, Cve_Vendedor,
       Lu, Ma, Mi, Ju, Vi, Sa, Do)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $selRel = $pdo->prepare("
    SELECT 1 FROM relclirutas
     WHERE IdEmpresa = ?
       AND IdRuta    = ?
       AND IdCliente = ?
     LIMIT 1
  ");

  $insRel = $pdo->prepare("
    INSERT INTO relclirutas
      (IdCliente, IdRuta, IdEmpresa, Fecha)
    VALUES (?, ?, ?, CURDATE())
  ");

  $delRel = $pdo->prepare("
    DELETE FROM relclirutas
     WHERE IdEmpresa = ?
       AND IdRuta    = ?
       AND IdCliente = ?
  ");

  $items_procesados = 0;
  $day_guardados = 0;
  $day_borrados = 0;
  $rel_agregados = 0;
  $rel_borrados = 0;

  foreach ($items as $it) {

    if (!is_array($it)) continue;

    $idDest = (int)($it['id_destinatario'] ?? 0);
    if ($idDest <= 0) continue;

    $cveCliente  = trim((string)($it['cve_cliente'] ?? ''));
    $cveVendedor = trim((string)($it['cve_vendedor'] ?? ''));

    $idClienteRel = $cveCliente !== '' ? $cveCliente : ('DEST_' . $idDest);

    $unassign = false;
    if (array_key_exists('unassign', $it)) {
      $unassign = isTruthy($it['unassign']);
    }

    if ($unassign) {

      $delRel->execute([$almacen, (int)$ruta, $idClienteRel]);
      $rel_borrados += $delRel->rowCount();

      $delDay->execute([$almacen, $ruta, $idDest]);
      $day_borrados += $delDay->rowCount();

      $items_procesados++;
      continue;
    }

    /* ---------- relclirutas ---------- */
    $selRel->execute([$almacen, (int)$ruta, $idClienteRel]);
    if (!$selRel->fetchColumn()) {
      $insRel->execute([$idClienteRel, (int)$ruta, $almacen]);
      $rel_agregados++;
    }

    /* ---------- reldaycli ---------- */
    $days = daysFromItem($it);

    $delDay->execute([$almacen, $ruta, $idDest]);
    $day_borrados += $delDay->rowCount();

    $insDay->execute([
      $almacen,
      $ruta,
      $cveCliente,
      $idDest,
      $cveVendedor,
      $days['Lu'], $days['Ma'], $days['Mi'],
      $days['Ju'], $days['Vi'], $days['Sa'], $days['Do']
    ]);

    $day_guardados++;
    $items_procesados++;
  }

  $pdo->commit();

  $total_cambios =
    $day_guardados +
    $day_borrados +
    $rel_agregados +
    $rel_borrados;

  jexit([
    'ok' => 1,
    'almacen' => $almacen,
    'ruta' => $ruta,
    'items_procesados' => $items_procesados,
    'reldaycli_guardados' => $day_guardados,
    'reldaycli_borrados' => $day_borrados,
    'relclirutas_agregados' => $rel_agregados,
    'relclirutas_borrados' => $rel_borrados,
    'total_cambios' => $total_cambios,
    'hubo_cambios' => $total_cambios > 0
  ]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit([
    'ok'=>0,
    'error'=>'Error al guardar',
    'detalle'=>$e->getMessage()
  ]);
}
