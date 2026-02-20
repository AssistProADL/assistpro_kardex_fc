<?php
// public/api/sfa/clientes_asignacion_save.php
// reldaycli: se mantiene el comportamiento probado (delete+insert por destinatario recibido)
// relclirutas: NO se resetea; se mantiene histórico y solo se agrega lo nuevo.
//             Solo se elimina si llega bandera explícita de desasignación (unassign=1 o asignado=0)

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

function jexit(array $arr): void {
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================
   CONEXIÓN PDO (estándar)
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
   LECTURA DE PAYLOAD
   ========================= */
$raw = file_get_contents('php://input');
$payload = null;

if ($raw && strlen(trim($raw)) > 0) {
  $payload = json_decode($raw, true);
  if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
    jexit(['ok'=>0,'error'=>'JSON inválido','detalle'=>json_last_error_msg()]);
  }
} else {
  $payload = $_POST ?: [];
}

$almacen = trim((string)($payload['almacen'] ?? $payload['almacen_id'] ?? ''));
$ruta    = trim((string)($payload['ruta']    ?? $payload['ruta_id']    ?? ''));
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
  $s = strtolower(trim((string)$v));
  return in_array($s, ['1','si','sí','true','t','x','on'], true);
}

/* =========================
   TRANSACCIÓN
   ========================= */
try {
  $pdo->beginTransaction();

  /* ---- reldaycli (MISMA LÓGICA QUE YA FUNCIONABA) ---- */
  $delDayByDest = $pdo->prepare("
    DELETE FROM reldaycli
     WHERE Cve_Almac = ?
       AND Cve_Ruta  = ?
       AND Id_Destinatario = ?
  ");

  $insDay = $pdo->prepare("
    INSERT INTO reldaycli
      (Cve_Almac, Cve_Ruta, Cve_Cliente, Id_Destinatario, Cve_Vendedor,
       Lu, Ma, Mi, Ju, Vi, Sa, Do)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  /* ---- relclirutas (MANTENER + AGREGAR; BORRAR SOLO EXPLÍCITO) ---- */
  // Estructura confirmada:
  // IdCliente (varchar), IdRuta (int), IdEmpresa (varchar), Fecha (date)
  $selRelExists = $pdo->prepare("
    SELECT 1
      FROM relclirutas
     WHERE IdEmpresa = ?
       AND IdRuta    = ?
       AND IdCliente = ?
     LIMIT 1
  ");

  $insRel = $pdo->prepare("
    INSERT INTO relclirutas
      (IdCliente, IdRuta, IdEmpresa, Fecha)
    VALUES
      (?, ?, ?, CURDATE())
  ");

  $delRelOne = $pdo->prepare("
    DELETE FROM relclirutas
     WHERE IdEmpresa = ?
       AND IdRuta    = ?
       AND IdCliente = ?
  ");

  // Cuando se desasigna, también se eliminan sus visitas (si existen)
  $delDayByDestOnly = $pdo->prepare("
    DELETE FROM reldaycli
     WHERE Cve_Almac = ?
       AND Cve_Ruta  = ?
       AND Id_Destinatario = ?
  ");

  $ok = 0;
  $rel_added = 0;
  $rel_deleted = 0;
  $day_saved = 0;
  $day_deleted = 0;

  foreach ($items as $it) {
    if (!is_array($it)) continue;

    $idDest = (int)($it['id_destinatario'] ?? $it['Id_Destinatario'] ?? 0);
    if ($idDest <= 0) continue;

    $cveCliente  = trim((string)($it['cve_cliente'] ?? $it['Cve_Cliente'] ?? ''));
    $cveVendedor = trim((string)($it['cve_vendedor'] ?? $it['Cve_Vendedor'] ?? ''));

    // Fallback estable para no perder asignación si cve_cliente viene vacío
    $idClienteRel = ($cveCliente !== '') ? $cveCliente : ('DEST_' . (string)$idDest);

    // Bandera de desasignación (para cuando implementes click "Asignado")
    $unassign = false;
    if (array_key_exists('unassign', $it)) $unassign = isTruthy($it['unassign']);
    if (array_key_exists('asignado', $it)) $unassign = $unassign || (!isTruthy($it['asignado']));

    if ($unassign) {
      // 1) Quitar asignación
      $delRelOne->execute([$almacen, (int)$ruta, $idClienteRel]);
      $rel_deleted += $delRelOne->rowCount() > 0 ? 1 : 0;

      // 2) Quitar días (si existen)
      $delDayByDestOnly->execute([$almacen, $ruta, $idDest]);
      $day_deleted += $delDayByDestOnly->rowCount() > 0 ? 1 : 0;

      $ok++;
      continue;
    }

    // 1) Mantener/agregar asignación en relclirutas (SIN borrar lo existente)
    $selRelExists->execute([$almacen, (int)$ruta, $idClienteRel]);
    $exists = $selRelExists->fetchColumn();

    if (!$exists) {
      $insRel->execute([$idClienteRel, (int)$ruta, $almacen]);
      $rel_added++;
    }

    // 2) Guardar días en reldaycli solo para este destinatario (mantiene comportamiento probado)
    $days = daysFromItem($it);
    $delDayByDest->execute([$almacen, $ruta, $idDest]);
    $insDay->execute([
      $almacen,
      $ruta,
      $cveCliente,
      $idDest,
      $cveVendedor,
      $days['Lu'], $days['Ma'], $days['Mi'],
      $days['Ju'], $days['Vi'], $days['Sa'], $days['Do']
    ]);
    $day_saved++;

    $ok++;
  }

  $pdo->commit();

  jexit([
    'ok'=>1,
    'almacen'=>$almacen,
    'ruta'=>$ruta,
    'items_procesados'=>$ok,
    'reldaycli_guardados'=>$day_saved,
    'reldaycli_borrados'=>$day_deleted,
    'relclirutas_agregados'=>$rel_added,
    'relclirutas_borrados'=>$rel_deleted
  ]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit(['ok'=>0,'error'=>'Error al guardar','detalle'=>$e->getMessage()]);
}
