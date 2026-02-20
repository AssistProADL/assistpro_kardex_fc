<?php
// AssistPro WMS - PQRS - Detalle / Seguimiento
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

// if (session_status() === PHP_SESSION_NONE) { @session_start(); }

function h($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function fmt_date(?string $dt): string {
  if (!$dt) return '';
  try { $d = new DateTime($dt); return $d->format('d/m/Y'); } catch(Throwable $e) { return (string)$dt; }
}
function fmt_dt(?string $dt): string {
  if (!$dt) return '';
  try { $d = new DateTime($dt); return $d->format('d/m/Y H:i'); } catch(Throwable $e) { return (string)$dt; }
}
function badge_class(string $status): string {
  $s = strtoupper(trim($status));
  return match($s) {
    'NUEVA' => 'bg-secondary',
    'EN_PROCESO' => 'bg-warning text-dark',
    'EN_ESPERA' => 'bg-info text-dark',
    'CERRADA', 'CERRADO' => 'bg-success',
    'CANCELADA', 'CANCELADO' => 'bg-danger',
    default => 'bg-primary'
  };
}
function current_user(): string {
  return (string)($_SESSION['usuario'] ?? $_SESSION['user'] ?? $_SESSION['login'] ?? $_SESSION['username'] ?? 'system');
}

// ---------- Input ----------
$id_case = (int)($_GET['id_case'] ?? 0);
if ($id_case <= 0) { http_response_code(400); echo "Falta id_case"; exit; }

$okMsg = '';
$errMsg = '';

// ---------- Helpers: DB ----------
function load_case(PDO $pdo, int $id_case): array {
  $st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
  $st->execute([$id_case]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
function load_statuses(PDO $pdo): array {
  // pqrs_cat_status: clave, nombre, orden, activo
  try {
    $q = $pdo->query("SELECT clave,nombre FROM pqrs_cat_status WHERE activo=1 ORDER BY COALESCE(orden,999), nombre");
    return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch(Throwable $e) { return []; }
}
function load_motivos(PDO $pdo): array {
  // pqrs_cat_motivo: id_motivo, clave, nombre, activo
  try {
    $q = $pdo->query("SELECT id_motivo, clave, nombre FROM pqrs_cat_motivo WHERE activo=1 ORDER BY nombre");
    return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch(Throwable $e) { return []; }
}
function load_events(PDO $pdo, int $id_case): array {
  $st = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY id_event DESC");
  $st->execute([$id_case]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
function load_files(PDO $pdo, int $id_case): array {
  try {
    $st = $pdo->prepare("SELECT * FROM pqrs_file WHERE id_case=? ORDER BY id_file DESC");
    $st->execute([$id_case]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch(Throwable $e) { return []; }
}
function ensure_upload_dir(): string {
  $dir = __DIR__ . '/uploads_pqrs';
  if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
  return $dir;
}
function db_has_file_named(PDO $pdo, int $id_case, string $nombre_original): bool {
  $st = $pdo->prepare("SELECT 1 FROM pqrs_file WHERE id_case=? AND nombre_original=? LIMIT 1");
  $st->execute([$id_case, $nombre_original]);
  return (bool)$st->fetchColumn();
}
function insert_file(PDO $pdo, int $id_case, string $nombre_original, string $ruta, string $mime, int $size_bytes, string $usuario): void {
  $st = $pdo->prepare("
    INSERT INTO pqrs_file(id_case, nombre_original, ruta, mime, size_bytes, subido_por)
    VALUES (?,?,?,?,?,?)
  ");
  $st->execute([$id_case, $nombre_original, $ruta, ($mime ?: null), ($size_bytes ?: null), ($usuario ?: null)]);
}
function insert_event(PDO $pdo, int $id_case, string $evento, string $detalle, ?string $before, ?string $after, string $usuario): void {
  $st = $pdo->prepare("
    INSERT INTO pqrs_event(id_case, evento, detalle, status_anterior, status_nuevo, usuario)
    VALUES (?,?,?,?,?,?)
  ");
  $st->execute([$id_case, $evento, $detalle, $before, $after, ($usuario ?: null)]);
}

// ---------- PRELOAD (before POST actions we need current status) ----------
$case = load_case($pdo, $id_case);
if (!$case) { http_response_code(404); echo "Caso no encontrado"; exit; }

$usuario = current_user();

// ---------- ACTIONS (POST/GET) - must run BEFORE menu output ----------
try {
  // Delete file
  if (isset($_GET['del_file'])) {
    $id_file = (int)$_GET['del_file'];
    if ($id_file > 0) {
      $st = $pdo->prepare("SELECT * FROM pqrs_file WHERE id_file=? AND id_case=? LIMIT 1");
      $st->execute([$id_file, $id_case]);
      $f = $st->fetch(PDO::FETCH_ASSOC);
      if ($f) {
        $ruta = (string)($f['ruta'] ?? '');
        // unlink (best effort)
        $fs = '';
        if ($ruta) {
          if (str_starts_with($ruta, 'uploads_pqrs/')) $fs = __DIR__ . '/' . $ruta;
          else if (basename($ruta) === $ruta) $fs = __DIR__ . '/uploads_pqrs/' . $ruta;
          else if (strpos($ruta, '/uploads_pqrs/') !== false) $fs = __DIR__ . '/uploads_pqrs/' . basename($ruta);
        }
        if ($fs && is_file($fs)) { @unlink($fs); }
        $pdo->prepare("DELETE FROM pqrs_file WHERE id_file=? LIMIT 1")->execute([$id_file]);

        insert_event($pdo, $id_case, 'ARCHIVO', 'Archivo eliminado: ' . (string)($f['nombre_original'] ?? ''), null, null, $usuario);

        header("Location: pqrs_view.php?id_case={$id_case}&ok=1");
        exit;
      }
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'update') {
      $nuevo = trim((string)($_POST['status_clave'] ?? ''));
      $coment = trim((string)($_POST['comentario'] ?? ''));

      if ($coment === '' && $nuevo === (string)($case['status_clave'] ?? '')) {
        throw new RuntimeException("No hay cambios para registrar.");
      }

      $before = (string)($case['status_clave'] ?? '');
      $after  = $nuevo !== '' ? $nuevo : $before;

      if ($nuevo !== '' && $nuevo !== $before) {
        $pdo->prepare("UPDATE pqrs_case SET status_clave=?, actualizado_en=NOW() WHERE id_case=?")->execute([$nuevo, $id_case]);
        $detalleEvt = $coment !== '' ? $coment : ("Cambio de status a: {$after}");
        insert_event($pdo, $id_case, 'STATUS', $detalleEvt, $before, $after, $usuario);
      } else {
        // Nota sin cambio de status
        if ($coment === '') throw new RuntimeException("Captura un comentario.");
        insert_event($pdo, $id_case, 'NOTA', $coment, null, null, $usuario);
      }

      header("Location: pqrs_view.php?id_case={$id_case}&ok=1");
      exit;
    }

    if ($accion === 'cerrar') {
      $motivo_id = (int)($_POST['motivo_id'] ?? 0);
      $detalle   = trim((string)($_POST['detalle'] ?? ''));

      if ($motivo_id <= 0) throw new RuntimeException("Selecciona un motivo de cierre.");

      $stM = $pdo->prepare("SELECT clave,nombre FROM pqrs_cat_motivo WHERE id_motivo=? LIMIT 1");
      $stM->execute([$motivo_id]);
      $mot = $stM->fetch(PDO::FETCH_ASSOC);
      if (!$mot) throw new RuntimeException("Motivo inválido.");

      $procede = (strpos((string)$mot['clave'], 'NP_') === 0) ? 0 : 1;
      $before = (string)($case['status_clave'] ?? '');
      $after  = 'CERRADA';

      $pdo->prepare("
        UPDATE pqrs_case
        SET status_clave='CERRADA',
            motivo_cierre_id=?,
            motivo_cierre_txt=?,
            procede=?,
            fecha_cierre=NOW(),
            actualizado_en=NOW()
        WHERE id_case=?
      ")->execute([$motivo_id, (string)$mot['nombre'], $procede, $id_case]);

      $evento = $procede ? 'CIERRE' : 'NO_PROCEDE';
      $txtEvt = "Motivo: " . (string)$mot['nombre'] . ($detalle ? " | {$detalle}" : "");
      insert_event($pdo, $id_case, $evento, $txtEvt, $before, $after, $usuario);

      header("Location: pqrs_view.php?id_case={$id_case}&ok=1");
      exit;
    }

    if ($accion === 'upload') {
      if (!isset($_FILES['archivo'])) throw new RuntimeException("No se recibió archivo.");

      $up = $_FILES['archivo'];
      $count = is_array($up['name']) ? count($up['name']) : 1;

      $uploadDir = ensure_upload_dir();

      for ($i=0; $i<$count; $i++) {
        $name = (string)(is_array($up['name']) ? $up['name'][$i] : $up['name']);
        $tmp  = (string)(is_array($up['tmp_name']) ? $up['tmp_name'][$i] : $up['tmp_name']);
        $err  = (int)(is_array($up['error']) ? $up['error'][$i] : $up['error']);
        $size = (int)(is_array($up['size']) ? $up['size'][$i] : $up['size']);
        $mime = (string)(is_array($up['type']) ? $up['type'][$i] : $up['type']);

        $name = trim($name);
        if ($name === '') continue;
        if ($err !== UPLOAD_ERR_OK) continue;
        if (!is_uploaded_file($tmp)) continue;

        // No duplicados por nombre original por caso
        if (db_has_file_named($pdo, $id_case, $name)) {
          // Se omite silencioso para evitar ruido (o podrías lanzar error)
          continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeExt = preg_replace('/[^a-z0-9]+/', '', $ext);
        $safeExt = $safeExt ?: 'bin';

        $fn = 'PQ' . $id_case . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
        $dest = $uploadDir . '/' . $fn;

        if (@move_uploaded_file($tmp, $dest)) {
          $rutaRel = 'uploads_pqrs/' . $fn;

          insert_file($pdo, $id_case, $name, $rutaRel, $mime, $size, $usuario);
          insert_event($pdo, $id_case, 'ARCHIVO', "Archivo adjunto: {$name}", null, null, $usuario);
        }
      }

      header("Location: pqrs_view.php?id_case={$id_case}&ok=1");
      exit;
    }
  }
} catch(Throwable $e) {
  $errMsg = $e->getMessage();
}

if (isset($_GET['ok'])) $okMsg = "Actualización aplicada correctamente.";

// ---------- Reload data after actions ----------
$case = load_case($pdo, $id_case);
$statuses = load_statuses($pdo);
$motivos  = load_motivos($pdo);
$events   = load_events($pdo, $id_case);
$files    = load_files($pdo, $id_case);

// ---------- Menu (output starts here) ----------
require_once __DIR__ . '/../bi/_menu_global.php';

// Base url for uploads
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$uploadBase = $scriptDir . '/uploads_pqrs';
?>
<style>
  /* AssistPro visual baseline */
  .ap-title { font-weight: 800; letter-spacing: .2px; color:#0b2e6b; }
  .ap-subtle { color:#6c757d; }
  .ap-card { border:1px solid #e6eef8; border-radius: 14px; box-shadow: 0 6px 18px rgba(12, 32, 74, .05); }
  .ap-card .card-header { background:#f6f9ff; border-bottom:1px solid #e6eef8; font-weight:700; color:#0b2e6b; }
  .ap-chip { display:inline-block; padding:.25rem .6rem; border-radius: 999px; font-size:.78rem; font-weight:700; }
  .ap-chip i { margin-right:.35rem; }
  .ap-kv .label { font-size:.72rem; color:#6c757d; margin-bottom:.15rem; }
  .ap-kv .value { font-weight:700; }
  .ap-table { font-size:10px; }
  .ap-table th { position: sticky; top: 0; background: #f6f9ff; z-index: 2; }
  .ap-table-wrap { max-height: 420px; overflow: auto; border:1px solid #e6eef8; border-radius: 12px; }
  .ap-help { font-size:.72rem; color:#6c757d; }
  .btn-ap { border-radius: 10px; font-weight:700; }
</style>

<div class="container-fluid px-3 px-md-4">
  <div class="d-flex align-items-start align-items-md-center justify-content-between flex-wrap gap-2 mt-3">
    <div>
      <div class="d-flex align-items-center gap-2">
        <span class="ap-chip bg-primary text-white"><i class="fa fa-ticket"></i>PQRS</span>
        <h3 class="m-0 ap-title">
          Detalle PQRS – <?= h($case['fol_pqrs'] ?? ('#'.$id_case)) ?>
        </h3>
      </div>
      <div class="ap-subtle mt-1">
        Creado: <?= h(fmt_dt($case['creado_en'] ?? null)) ?>
        &nbsp;&nbsp;•&nbsp;&nbsp;
        Estatus: <span class="ap-chip <?= h(badge_class((string)($case['status_clave'] ?? ''))) ?>"><?= h((string)($case['status_clave'] ?? '')) ?></span>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-ap" href="pqrs_list.php"><i class="fa fa-arrow-left"></i> Volver</a>
      <a class="btn btn-danger btn-ap" target="_blank" href="pqrs_pdf.php?id_case=<?= (int)$id_case ?>"><i class="fa fa-file-pdf"></i> PDF</a>
    </div>
  </div>

  <?php if ($okMsg): ?>
    <div class="alert alert-success ap-card mt-3 mb-2"><i class="fa fa-check-circle"></i> <?= h($okMsg) ?></div>
  <?php endif; ?>
  <?php if ($errMsg): ?>
    <div class="alert alert-danger ap-card mt-3 mb-2"><i class="fa fa-exclamation-triangle"></i> <?= h($errMsg) ?></div>
  <?php endif; ?>

  <div class="row g-3 mt-1">
    <div class="col-12 col-lg-7">
      <div class="card ap-card">
        <div class="card-header"><i class="fa fa-chart-line"></i> Resumen ejecutivo</div>
        <div class="card-body">
          <div class="row g-2 ap-kv">
            <div class="col-6 col-md-4">
              <div class="p-2 rounded bg-light border">
                <div class="label">Cliente</div>
                <div class="value"><?= h($case['cve_clte'] ?? '') ?></div>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="p-2 rounded bg-light border">
                <div class="label">Almacén</div>
                <div class="value"><?= h($case['cve_almacen'] ?? '') ?></div>
              </div>
            </div>
            <div class="col-6 col-md-4">
              <div class="p-2 rounded bg-light border">
                <div class="label">Tipo</div>
                <div class="value"><?= h($case['tipo'] ?? '') ?></div>
              </div>
            </div>
            <div class="col-12 col-md-8">
              <div class="p-2 rounded bg-light border">
                <div class="label">Referencia</div>
                <div class="value">
                  <?= h(trim((string)($case['ref_tipo'] ?? '') . ' ' . (string)($case['ref_folio'] ?? ''))) ?>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="p-2 rounded bg-light border">
                <div class="label">Susceptible a cobro</div>
                <div class="value"><?= ((int)($case['susceptible_cobro'] ?? 0) === 1) ? 'Sí' : 'No' ?></div>
              </div>
            </div>
            <div class="col-12">
              <div class="p-2 rounded bg-light border">
                <div class="label">Motivo de apertura</div>
                <div class="value"><?= h($case['motivo_registro_txt'] ?? '') ?></div>
              </div>
            </div>
          </div>

          <hr class="my-3">

          <div class="mb-0">
            <div class="fw-bold text-primary mb-1"><i class="fa fa-align-left"></i> Descripción</div>
            <div class="p-2 rounded bg-white border"><?= nl2br(h($case['descripcion'] ?? '')) ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="card ap-card">
        <div class="card-header"><i class="fa fa-pen-to-square"></i> Seguimiento / Actualización</div>
        <div class="card-body">
          <form method="post" class="row g-2">
            <input type="hidden" name="accion" value="update">
            <div class="col-12">
              <label class="form-label fw-bold mb-1">Estatus</label>
              <select class="form-select" name="status_clave">
                <?php if (!$statuses): ?>
                  <option value="<?= h((string)($case['status_clave'] ?? '')) ?>"><?= h((string)($case['status_clave'] ?? '')) ?></option>
                <?php else: ?>
                  <?php foreach($statuses as $s): ?>
                    <?php $clave = (string)($s['clave'] ?? ''); ?>
                    <option value="<?= h($clave) ?>" <?= ($clave === (string)($case['status_clave'] ?? '')) ? 'selected' : '' ?>>
                      <?= h((string)($s['nombre'] ?? $clave)) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <div class="ap-help mt-1">Si el estatus no cambia, se registrará como <b>NOTA</b> en eventos.</div>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold mb-1">Comentario</label>
              <textarea class="form-control" name="comentario" rows="3" placeholder="Describe el avance o cierre del caso"></textarea>
            </div>

            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-primary btn-ap" type="submit"><i class="fa fa-save"></i> Registrar actualización</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card ap-card mt-3">
        <div class="card-header"><i class="fa fa-paperclip"></i> Archivos / Evidencias</div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" class="row g-2">
            <input type="hidden" name="accion" value="upload">
            <div class="col-12">
              <input class="form-control" type="file" name="archivo[]" multiple>
              <div class="ap-help mt-1">Sugerido: fotos, PDFs, layouts y evidencias. No se permiten duplicados por nombre dentro del caso.</div>
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-outline-primary btn-ap" type="submit"><i class="fa fa-upload"></i> Subir</button>
            </div>
          </form>

          <hr class="my-3">

          <?php if (!$files): ?>
            <div class="ap-subtle">Sin archivos registrados.</div>
          <?php else: ?>
            <ul class="list-unstyled mb-0">
              <?php foreach($files as $f): ?>
                <?php
                  $fname = (string)($f['nombre_original'] ?? '');
                  $ruta  = (string)($f['ruta'] ?? '');
                  $link  = $ruta ? ($scriptDir . '/' . ltrim($ruta,'/')) : '#';
                  $when  = fmt_dt($f['subido_en'] ?? null);
                  $id_file = (int)($f['id_file'] ?? 0);
                ?>
                <li class="d-flex align-items-center justify-content-between py-1 border-bottom">
                  <div class="text-truncate" style="max-width: 74%;">
                    <i class="fa fa-file"></i>
                    <a href="<?= h($link) ?>" target="_blank"><?= h($fname ?: $ruta) ?></a>
                    <span class="ap-subtle"> (<?= h($when) ?>)</span>
                  </div>
                  <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-danger btn-ap"
                       href="pqrs_view.php?id_case=<?= (int)$id_case ?>&del_file=<?= $id_file ?>"
                       onclick="return confirm('¿Eliminar el archivo?');">
                      <i class="fa fa-trash"></i>
                    </a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="card ap-card mt-3">
        <div class="card-header"><i class="fa fa-flag-checkered"></i> Cierre</div>
        <div class="card-body">
          <form method="post" class="row g-2">
            <input type="hidden" name="accion" value="cerrar">
            <div class="col-12">
              <label class="form-label fw-bold mb-1">Motivo</label>
              <select class="form-select" name="motivo_id">
                <option value="0">-- Selecciona --</option>
                <?php foreach($motivos as $m): ?>
                  <option value="<?= (int)($m['id_motivo'] ?? 0) ?>">
                    <?= h((string)($m['nombre'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold mb-1">Detalle / Comentario</label>
              <textarea class="form-control" name="detalle" rows="2" placeholder="Opcional"></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-dark btn-ap" type="submit" <?= ((string)($case['status_clave'] ?? '') === 'CERRADA') ? 'disabled' : '' ?>>
                <i class="fa fa-lock"></i> Cerrar caso
              </button>
            </div>
          </form>
          <?php if ((string)($case['status_clave'] ?? '') === 'CERRADA'): ?>
            <div class="ap-help mt-2"><i class="fa fa-info-circle"></i> El caso ya está cerrado.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card ap-card">
        <div class="card-header"><i class="fa fa-list"></i> Bitácora de eventos</div>
        <div class="card-body">
          <div class="ap-table-wrap">
            <table class="table table-sm table-striped table-hover mb-0 ap-table">
              <thead>
                <tr>
                  <th style="min-width:120px;">Fecha</th>
                  <th style="min-width:90px;">Evento</th>
                  <th style="min-width:320px;">Detalle</th>
                  <th style="min-width:120px;">Antes</th>
                  <th style="min-width:120px;">Después</th>
                  <th style="min-width:140px;">Usuario</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$events): ?>
                  <tr><td colspan="6" class="text-center ap-subtle py-4">Sin eventos</td></tr>
                <?php else: ?>
                  <?php foreach($events as $e): ?>
                    <tr>
                      <td><?= h(fmt_dt($e['creado_en'] ?? null)) ?></td>
                      <td><span class="ap-chip bg-light border text-dark"><?= h($e['evento'] ?? '') ?></span></td>
                      <td><?= nl2br(h($e['detalle'] ?? '')) ?></td>
                      <td><?= h($e['status_anterior'] ?? '') ?></td>
                      <td><?= h($e['status_nuevo'] ?? '') ?></td>
                      <td><?= h($e['usuario'] ?? '') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="ap-help mt-2">Tabla preparada para 25+ registros con scroll horizontal y vertical.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
