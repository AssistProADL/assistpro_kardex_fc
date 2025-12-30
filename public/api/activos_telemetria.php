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
   INGESTA DE TELEMETRÍA
   ========================================================= */
if ($action === 'ingest') {

  $id_activo    = (int)($_POST['id_activo'] ?? 0);
  $cve_cia      = (int)($_POST['cve_cia'] ?? 0);
  $latitud      = $_POST['latitud'] ?? null;
  $longitud     = $_POST['longitud'] ?? null;
  $temperatura  = $_POST['temperatura'] ?? null;
  $bateria      = $_POST['bateria'] ?? null;
  $fuente       = trim($_POST['fuente'] ?? 'SENSOR');
  $payload_json = $_POST['payload_json'] ?? null;

  if ($id_activo<=0 || $cve_cia<=0) {
    out(false, ['error'=>'id_activo y cve_cia requeridos']);
  }

  try {
    $pdo->beginTransaction();

    /* 1) Guardar telemetría */
    $st = $pdo->prepare("
      INSERT INTO t_activo_telemetria
        (id_activo, cve_cia, ts_evento,
         latitud, longitud, temperatura, bateria,
         fuente, payload_json, created_at)
      VALUES
        (?, ?, NOW(),
         ?, ?, ?, ?,
         ?, ?, NOW())
    ");
    $st->execute([
      $id_activo,
      $cve_cia,
      $latitud,
      $longitud,
      $temperatura,
      $bateria,
      $fuente,
      $payload_json
    ]);

    /* 2) Evaluar alertas simples */
    if ($temperatura !== null) {
      // ejemplo: fuera de rango 2–8 °C
      if ($temperatura < 2 || $temperatura > 8) {
        $pdo->prepare("
          INSERT INTO t_activo_evento
            (id_activo, cve_cia, tipo_evento, descripcion, created_at)
          VALUES
            (?, ?, 'TEMP_ALERTA',
             CONCAT('Temperatura fuera de rango: ', ?, ' °C'),
             NOW())
        ")->execute([
          $id_activo,
          $cve_cia,
          $temperatura
        ]);
      }
    }

    $pdo->commit();
    out(true);

  } catch (Throwable $e) {
    $pdo->rollBack();
    out(false, ['error'=>$e->getMessage()]);
  }
}

/* =========================================================
   ÚLTIMA LECTURA POR ACTIVO
   ========================================================= */
if ($action === 'last') {

  $id_activo = (int)($_GET['id_activo'] ?? 0);
  if ($id_activo<=0) out(false, ['error'=>'id_activo requerido']);

  $st = $pdo->prepare("
    SELECT
      ts_evento,
      latitud,
      longitud,
      temperatura,
      bateria,
      fuente
    FROM t_activo_telemetria
    WHERE id_activo = ?
    ORDER BY ts_evento DESC
    LIMIT 1
  ");
  $st->execute([$id_activo]);

  out(true, ['data'=>$st->fetch(PDO::FETCH_ASSOC)]);
}

/* =========================================================
   HISTÓRICO DE TELEMETRÍA (PAGINADO)
   ========================================================= */
if ($action === 'history') {

  $id_activo = (int)($_GET['id_activo'] ?? 0);
  $limit     = min(500, max(10, (int)($_GET['limit'] ?? 100)));

  if ($id_activo<=0) out(false, ['error'=>'id_activo requerido']);

  $st = $pdo->prepare("
    SELECT
      ts_evento,
      latitud,
      longitud,
      temperatura,
      bateria,
      fuente
    FROM t_activo_telemetria
    WHERE id_activo = ?
    ORDER BY ts_evento DESC
    LIMIT ?
  ");
  $st->execute([$id_activo, $limit]);

  out(true, ['data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
}

out(false, ['error'=>'Acción no válida']);
