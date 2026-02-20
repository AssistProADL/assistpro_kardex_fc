<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../../app/auth_check.php';
require_once __DIR__ . '/../../../app/db.php';

db_pdo();
global $pdo;

function jexit(bool $ok, string $msg = '', array $data = [], int $http = 200): void {
  http_response_code($http);
  echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $data), JSON_UNESCAPED_UNICODE);
  exit;
}

function p(string $k, $d = null) {
  return $_POST[$k] ?? $_GET[$k] ?? $d;
}
function up($v): string {
  return strtoupper(trim((string)$v));
}
function int01($v, int $default = 0): int {
  if ($v === null || $v === '') return $default;
  return ((string)$v === '1' || $v === 1 || $v === true) ? 1 : 0;
}

/**
 * Convierte una "ruta guardada" tipo "public/api/importadores/imp_tralm.php"
 * en una ruta llamable desde este API: "../importadores/imp_tralm.php"
 * Nota: Esto asume que estás en /public/api/utilerias/ actualmente.
 */
function rutaApiToCallable(string $rutaApi): string {
  $rutaApi = trim($rutaApi);
  if ($rutaApi === '') return '';

  // Si viene como "public/..." lo convertimos a relativo desde /public/api/utilerias/
  if (preg_match('#^public/#i', $rutaApi)) {
    $rutaApi = preg_replace('#^public/#i', '../', $rutaApi); // => "../api/importadores/imp_tralm.php" (si guardan "public/api/..")
    // Si quedó "../api/..." desde /public/api/utilerias/ debemos subir un nivel más:
    // /public/api/utilerias/ + ../api/... => /public/api/api/... (incorrecto).
    // Entonces normalizamos: si empieza con "../api/" lo convertimos a "../importadores/" (porque ya estamos dentro de /api/)
    $rutaApi = preg_replace('#^\.\./api/#i', '../', $rutaApi); // => "../importadores/imp_tralm.php" si guardaron "public/api/importadores/..."
  }

  // Si guardan directo "api/importadores/..." o "/api/importadores/..." lo normalizamos
  $rutaApi = preg_replace('#^/?api/#i', '..', $rutaApi); // => "../importadores/..."
  // Si guardan "importadores/..." lo dejamos relativo a /public/api/utilerias/ => "../importadores/..."
  if (preg_match('#^importadores/#i', $rutaApi)) $rutaApi = '../' . $rutaApi;

  return $rutaApi;
}

$action = p('action', 'list');

try {

  /* ===============================
   * LIST
   * =============================== */
  if ($action === 'list') {
    $tipo   = up(p('tipo', ''));
    $activo = p('activo', '');
    $search = up(p('search', ''));

    $sql = "SELECT * FROM c_importador WHERE 1=1 ";
    $prm = [];

    if ($tipo !== '' && $tipo !== 'TODOS') {
      $sql .= " AND tipo = :tipo ";
      $prm[':tipo'] = $tipo;
    }
    if ($activo !== '' && $activo !== 'TODOS') {
      $sql .= " AND activo = :activo ";
      $prm[':activo'] = (int)$activo;
    }
    if ($search !== '') {
      $sql .= " AND (UPPER(clave) LIKE :s OR UPPER(descripcion) LIKE :s) ";
      $prm[':s'] = "%$search%";
    }

    $sql .= " ORDER BY tipo, clave";

    $st = $pdo->prepare($sql);
    $st->execute($prm);

    jexit(true, '', ['rows' => $st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  /* ===============================
   * GET
   * =============================== */
  if ($action === 'get') {
    $id = (int)p('id_importador', 0);
    $st = $pdo->prepare("SELECT * FROM c_importador WHERE id_importador=?");
    $st->execute([$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) jexit(false, 'No encontrado', [], 404);
    jexit(true, '', ['row' => $r]);
  }

  /* ===============================
   * CREATE
   * =============================== */
  if ($action === 'create') {
    $clave = up(p('clave', ''));
    $tipo  = up(p('tipo', ''));
    $desc  = trim((string)p('descripcion', ''));

    if ($clave === '') jexit(false, 'Clave obligatoria');
    if ($tipo === '')  jexit(false, 'Tipo obligatorio');
    if ($desc === '')  jexit(false, 'Descripción obligatoria');

    $st = $pdo->prepare("SELECT 1 FROM c_importador WHERE clave=?");
    $st->execute([$clave]);
    if ($st->fetch()) jexit(false, 'La clave ya existe');

    $sql = "INSERT INTO c_importador
      (clave, descripcion, tipo, activo,
       permite_rollback, impacta_kardex_default, requiere_layout,
       requiere_bl_origen, requiere_lote_si_aplica, requiere_serie_si_aplica,
       destino_retencion_obligatorio, ruta_api, version)
      VALUES
      (:clave,:des,:tipo,:activo,:rb,:kdx,:lay,:bl,:lote,:serie,:ret,:api,:ver)";

    $pdo->prepare($sql)->execute([
      ':clave' => $clave,
      ':des'   => $desc,
      ':tipo'  => $tipo,
      ':activo'=> int01(p('activo', 1), 1),

      ':rb'    => int01(p('permite_rollback', 1), 1),
      ':kdx'   => int01(p('impacta_kardex_default', 1), 1),
      ':lay'   => int01(p('requiere_layout', 1), 1),

      ':bl'    => int01(p('requiere_bl_origen', 0), 0),
      ':lote'  => int01(p('requiere_lote_si_aplica', 1), 1),
      ':serie' => int01(p('requiere_serie_si_aplica', 1), 1),
      ':ret'   => int01(p('destino_retencion_obligatorio', 0), 0),

      ':api'   => trim((string)p('ruta_api', '')),
      ':ver'   => (int)p('version', 1),
    ]);

    jexit(true, 'Importador creado');
  }

  /* ===============================
   * UPDATE
   * =============================== */
  if ($action === 'update') {
    $id = (int)p('id_importador', 0);
    if (!$id) jexit(false, 'ID inválido');

    $tipo = up(p('tipo', ''));
    $desc = trim((string)p('descripcion', ''));

    if ($tipo === '') jexit(false, 'Tipo obligatorio');
    if ($desc === '') jexit(false, 'Descripción obligatoria');

    $sql = "UPDATE c_importador SET
      descripcion=:des,
      tipo=:tipo,
      activo=:activo,
      permite_rollback=:rb,
      impacta_kardex_default=:kdx,
      requiere_layout=:lay,
      requiere_bl_origen=:bl,
      requiere_lote_si_aplica=:lote,
      requiere_serie_si_aplica=:serie,
      destino_retencion_obligatorio=:ret,
      ruta_api=:api,
      version=:ver
    WHERE id_importador=:id";

    $pdo->prepare($sql)->execute([
      ':des'   => $desc,
      ':tipo'  => $tipo,
      ':activo'=> int01(p('activo', 1), 1),

      ':rb'    => int01(p('permite_rollback', 1), 1),
      ':kdx'   => int01(p('impacta_kardex_default', 1), 1),
      ':lay'   => int01(p('requiere_layout', 1), 1),

      ':bl'    => int01(p('requiere_bl_origen', 0), 0),
      ':lote'  => int01(p('requiere_lote_si_aplica', 1), 1),
      ':serie' => int01(p('requiere_serie_si_aplica', 1), 1),
      ':ret'   => int01(p('destino_retencion_obligatorio', 0), 0),

      ':api'   => trim((string)p('ruta_api', '')),
      ':ver'   => (int)p('version', 1),
      ':id'    => $id
    ]);

    jexit(true, 'Importador actualizado');
  }

  /* ===============================
   * TOGGLE ACTIVO
   * =============================== */
  if ($action === 'toggle_activo') {
    $id = (int)p('id_importador', 0);
    if (!$id) jexit(false, 'ID inválido');
    $pdo->prepare("UPDATE c_importador SET activo = IF(activo=1,0,1) WHERE id_importador=?")
        ->execute([$id]);
    jexit(true, 'Estado actualizado');
  }

  /* ===============================
   * CLONE
   * =============================== */
  if ($action === 'clone') {
    $id = (int)p('id_importador', 0);
    $nueva = up(p('nueva_clave', ''));
    if (!$id) jexit(false, 'ID inválido');
    if ($nueva === '') jexit(false, 'Nueva clave obligatoria');

    $st = $pdo->prepare("SELECT 1 FROM c_importador WHERE clave=?");
    $st->execute([$nueva]);
    if ($st->fetch()) jexit(false, 'La nueva clave ya existe');

    $st = $pdo->prepare("SELECT * FROM c_importador WHERE id_importador=?");
    $st->execute([$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) jexit(false, 'Registro origen no encontrado', [], 404);

    $sql = "INSERT INTO c_importador
      (clave, descripcion, tipo, activo,
       permite_rollback, impacta_kardex_default, requiere_layout,
       requiere_bl_origen, requiere_lote_si_aplica, requiere_serie_si_aplica,
       destino_retencion_obligatorio, ruta_api, version)
      VALUES
      (:clave,:des,:tipo,1,:rb,:kdx,:lay,:bl,:lote,:serie,:ret,:api,:ver)";

    $pdo->prepare($sql)->execute([
      ':clave' => $nueva,
      ':des'   => trim((string)$r['descripcion']) . ' (CLON)',
      ':tipo'  => up($r['tipo'] ?? ''),
      ':rb'    => (int)($r['permite_rollback'] ?? 1),
      ':kdx'   => (int)($r['impacta_kardex_default'] ?? 1),
      ':lay'   => (int)($r['requiere_layout'] ?? 1),
      ':bl'    => (int)($r['requiere_bl_origen'] ?? 0),
      ':lote'  => (int)($r['requiere_lote_si_aplica'] ?? 1),
      ':serie' => (int)($r['requiere_serie_si_aplica'] ?? 1),
      ':ret'   => (int)($r['destino_retencion_obligatorio'] ?? 0),
      ':api'   => (string)($r['ruta_api'] ?? ''),
      ':ver'   => (int)($r['version'] ?? 1),
    ]);

    jexit(true, 'Clonado OK');
  }

  /* ===============================
   * PING (un importador)
   * =============================== */
  if ($action === 'ping') {
    $id = (int)p('id_importador', 0);
    if (!$id) jexit(false, 'ID inválido');

    $st = $pdo->prepare("SELECT clave, ruta_api FROM c_importador WHERE id_importador=?");
    $st->execute([$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) jexit(false, 'No encontrado', [], 404);

    $ruta = trim((string)($r['ruta_api'] ?? ''));
    if ($ruta === '') jexit(false, 'Ruta API vacía');

    $callable = rutaApiToCallable($ruta);
    if ($callable === '') jexit(false, 'Ruta API inválida');

    // Se intenta llamar agregando action=ping (si el endpoint no soporta ping, igual queremos validar que responda JSON)
    $testUrl = $callable . (strpos($callable, '?') === false ? '?' : '&') . 'action=ping';

    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $raw = @file_get_contents($testUrl, false, $ctx);

    if ($raw === false) {
      jexit(false, 'Ping falló: no se pudo abrir endpoint (' . up($r['clave']) . ')');
    }

    $js = json_decode($raw, true);
    if (!is_array($js)) {
      jexit(false, 'Ping falló: respuesta no es JSON (' . up($r['clave']) . ')');
    }

    jexit(true, 'Ping OK: ' . up($r['clave']) . ' responde JSON');
  }

  /* ===============================
   * PING ALL (solo activos)
   * =============================== */
  if ($action === 'ping_all') {
    $st = $pdo->query("SELECT id_importador, clave, ruta_api FROM c_importador WHERE activo=1 ORDER BY tipo, clave");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $ok = 0; $err = 0; $det = [];

    foreach ($rows as $r) {
      $clave = up($r['clave'] ?? '');
      $ruta  = trim((string)($r['ruta_api'] ?? ''));

      if ($ruta === '') {
        $err++;
        $det[] = ['clave' => $clave, 'ok' => false, 'msg' => 'Ruta API vacía'];
        continue;
      }

      $callable = rutaApiToCallable($ruta);
      if ($callable === '') {
        $err++;
        $det[] = ['clave' => $clave, 'ok' => false, 'msg' => 'Ruta API inválida'];
        continue;
      }

      $testUrl = $callable . (strpos($callable, '?') === false ? '?' : '&') . 'action=ping';

      $ctx = stream_context_create(['http' => ['timeout' => 3]]);
      $raw = @file_get_contents($testUrl, false, $ctx);

      if ($raw === false) {
        $err++;
        $det[] = ['clave' => $clave, 'ok' => false, 'msg' => 'No se pudo abrir endpoint'];
        continue;
      }

      $js = json_decode($raw, true);
      if (!is_array($js)) {
        $err++;
        $det[] = ['clave' => $clave, 'ok' => false, 'msg' => 'Respuesta no JSON'];
        continue;
      }

      $ok++;
      $det[] = ['clave' => $clave, 'ok' => true, 'msg' => 'OK'];
    }

    jexit(true, 'Ping masivo terminado', [
      'ok_count' => $ok,
      'err_count'=> $err,
      'detail'   => $det
    ]);
  }

  jexit(false, 'Acción no soportada', [], 400);

} catch (Throwable $e) {
  jexit(false, $e->getMessage(), [], 500);
}
