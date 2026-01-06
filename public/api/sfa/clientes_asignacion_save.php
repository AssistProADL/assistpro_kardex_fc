<?php
// public/api/sfa/clientes_asignacion_save.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ---- Helpers de respuesta
function jexit(array $arr): void {
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

// ---- Cargar conexión PDO (ajusta rutas si tu proyecto difiere)
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

// Intentar detectar variable PDO típica
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
  jexit(['ok'=>0,'error'=>'No se pudo inicializar PDO (db.php).']);
}

// ---- Leer payload (JSON o POST)
$raw = file_get_contents('php://input');
$payload = null;

if ($raw && strlen(trim($raw)) > 0) {
  $payload = json_decode($raw, true);
  if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
    jexit(['ok'=>0,'error'=>'JSON inválido en body','detalle'=>json_last_error_msg()]);
  }
} else {
  $payload = $_POST ?: [];
}

// Normalizar params
$almacen_id = (int)($payload['almacen_id'] ?? $payload['almacen'] ?? $payload['id_almacen'] ?? 0);
$ruta_id    = (int)($payload['ruta_id']    ?? $payload['ruta']    ?? $payload['id_ruta']    ?? 0);

// items puede venir como array ya decodificado o como string JSON en POST
$items = $payload['items'] ?? $payload['data'] ?? [];
if (is_string($items)) {
  $tmp = json_decode($items, true);
  if (is_array($tmp)) $items = $tmp;
}

if ($almacen_id <= 0 || $ruta_id <= 0 || !is_array($items) || count($items) === 0) {
  jexit([
    'ok'=>0,
    'error'=>'Parámetros incompletos (almacen_id/ruta_id/items).',
    'debug'=>[
      'almacen_id'=>$almacen_id,
      'ruta_id'=>$ruta_id,
      'items_type'=>gettype($items),
      'items_count'=>is_array($items)?count($items):0
    ]
  ]);
}

// ---- Funciones para días
function daysFromItem(array $it): array {
  // Prioridad:
  // 1) flags Lu..Do explícitos
  // 2) dias_bits (ej "1010100")
  // 3) days_bits
  // 4) dias (array)
  $Lu=$Ma=$Mi=$Ju=$Vi=$Sa=$Do=0;

  $hasExplicit = false;
  foreach (['Lu','Ma','Mi','Ju','Vi','Sa','Do'] as $k) {
    if (array_key_exists($k, $it)) { $hasExplicit = true; }
  }

  if ($hasExplicit) {
    $Lu = !empty($it['Lu']) ? 1 : 0;
    $Ma = !empty($it['Ma']) ? 1 : 0;
    $Mi = !empty($it['Mi']) ? 1 : 0;
    $Ju = !empty($it['Ju']) ? 1 : 0;
    $Vi = !empty($it['Vi']) ? 1 : 0;
    $Sa = !empty($it['Sa']) ? 1 : 0;
    $Do = !empty($it['Do']) ? 1 : 0;
    return compact('Lu','Ma','Mi','Ju','Vi','Sa','Do');
  }

  $bits = $it['dias_bits'] ?? $it['days_bits'] ?? '';
  if (is_string($bits) && strlen($bits) >= 7) {
    $bits = substr($bits, 0, 7);
    $Lu = ($bits[0] === '1') ? 1 : 0;
    $Ma = ($bits[1] === '1') ? 1 : 0;
    $Mi = ($bits[2] === '1') ? 1 : 0;
    $Ju = ($bits[3] === '1') ? 1 : 0;
    $Vi = ($bits[4] === '1') ? 1 : 0;
    $Sa = ($bits[5] === '1') ? 1 : 0;
    $Do = ($bits[6] === '1') ? 1 : 0;
    return compact('Lu','Ma','Mi','Ju','Vi','Sa','Do');
  }

  if (!empty($it['dias']) && is_array($it['dias'])) {
    $d = $it['dias'];
    $Lu = !empty($d['Lu'] ?? $d['lu'] ?? 0) ? 1 : 0;
    $Ma = !empty($d['Ma'] ?? $d['ma'] ?? 0) ? 1 : 0;
    $Mi = !empty($d['Mi'] ?? $d['mi'] ?? 0) ? 1 : 0;
    $Ju = !empty($d['Ju'] ?? $d['ju'] ?? 0) ? 1 : 0;
    $Vi = !empty($d['Vi'] ?? $d['vi'] ?? 0) ? 1 : 0;
    $Sa = !empty($d['Sa'] ?? $d['sa'] ?? 0) ? 1 : 0;
    $Do = !empty($d['Do'] ?? $d['do'] ?? 0) ? 1 : 0;
    return compact('Lu','Ma','Mi','Ju','Vi','Sa','Do');
  }

  return compact('Lu','Ma','Mi','Ju','Vi','Sa','Do');
}

// ---- Guardado transaccional, anti-duplicados
$okCount = 0;
$errCount = 0;
$errors = [];

try {
  $pdo->beginTransaction();

  // Statements preparados
  $delDup = $pdo->prepare("
    DELETE FROM reldaycli
    WHERE Cve_Almac = ? AND Cve_Ruta = ? AND Id_Destinatario = ?
  ");

  $ins = $pdo->prepare("
    INSERT INTO reldaycli
      (Cve_Almac, Cve_Ruta, Cve_Cliente, Id_Destinatario, Cve_Vendedor, Lu, Ma, Mi, Ju, Vi, Sa, Do)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  foreach ($items as $idx => $it) {
    if (!is_array($it)) { $errCount++; $errors[]="Item#$idx no es objeto"; continue; }

    $id_dest = (int)($it['id_destinatario'] ?? $it['Id_Destinatario'] ?? $it['destinatario_id'] ?? 0);
    $cve_cte = (string)($it['Cve_Cte'] ?? $it['Cve_Cliente'] ?? $it['cve_cliente'] ?? $it['cliente'] ?? '');
    $cve_vend = (string)($it['Cve_Vendedor'] ?? $it['cve_vendedor'] ?? '');

    if ($id_dest <= 0) { $errCount++; $errors[]="Item#$idx sin id_destinatario"; continue; }
    if ($cve_cte === '') { $cve_cte = '0'; } // fallback seguro si no viene

    $days = daysFromItem($it);

    // 1) eliminar cualquier duplicado previo para esta combinación
    $delDup->execute([$almacen_id, $ruta_id, $id_dest]);

    // 2) insertar 1 sola fila con los días correctos
    $ins->execute([
      $almacen_id,
      $ruta_id,
      $cve_cte,
      $id_dest,
      $cve_vend,
      $days['Lu'], $days['Ma'], $days['Mi'], $days['Ju'], $days['Vi'], $days['Sa'], $days['Do']
    ]);

    $okCount++;
  }

  if ($errCount > 0 && $okCount === 0) {
    $pdo->rollBack();
    jexit(['ok'=>0,'error'=>'No se pudo guardar ningún registro','detalle'=>$errors]);
  }

  $pdo->commit();

  jexit([
    'ok'=>1,
    'almacen_id'=>$almacen_id,
    'ruta_id'=>$ruta_id,
    'total_ok'=>$okCount,
    'total_err'=>$errCount,
    'errors'=>$errors
  ]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit([
    'ok'=>0,
    'error'=>'Error guardando asignación',
    'detalle'=>$e->getMessage()
  ]);
}
