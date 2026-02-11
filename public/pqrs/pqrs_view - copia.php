<?php
// =====================================================
// PQRS - View (Detalle) - AssistPro Style + Bitácora + Adjuntos
// Archivo: /public/pqrs/pqrs_view.php
// =====================================================
ob_start();
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$id_case = (int)($_GET['id_case'] ?? 0);
if ($id_case <= 0) {
  echo "<div class='container-fluid'><div class='alert alert-danger'>Falta id_case</div></div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

/* ===================== HELPERS ===================== */
function db_table_columns(PDO $pdo, string $table): array {
  try {
    $st = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $st->execute([$table]);
    return $st->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
  } catch(Throwable $e) {
    return [];
  }
}

function pqrs_file_insert(PDO $pdo, int $id_case, string $rel_path, string $orig, string $mime, int $size, ?string $usuario): void {
  $cols = db_table_columns($pdo, 'pqrs_file');
  if (!$cols) return;

  $data = [];
  // mapeos típicos
  if (in_array('id_case', $cols)) $data['id_case'] = $id_case;
  if (in_array('ruta', $cols)) $data['ruta'] = $rel_path;
  if (in_array('path', $cols)) $data['path'] = $rel_path;
  if (in_array('file_path', $cols)) $data['file_path'] = $rel_path;
  if (in_array('archivo', $cols)) $data['archivo'] = $rel_path;
  if (in_array('nombre', $cols)) $data['nombre'] = $orig;
  if (in_array('original_name', $cols)) $data['original_name'] = $orig;
  if (in_array('filename', $cols)) $data['filename'] = basename($rel_path);
  if (in_array('mime', $cols)) $data['mime'] = $mime;
  if (in_array('tipo', $cols)) $data['tipo'] = $mime;
  if (in_array('size', $cols)) $data['size'] = $size;
  if (in_array('tam', $cols)) $data['tam'] = $size;
  if (in_array('usuario', $cols)) $data['usuario'] = $usuario;
  if (in_array('creado_por', $cols)) $data['creado_por'] = $usuario;

  // timestamps
  if (in_array('creado_en', $cols) && !isset($data['creado_en'])) $data['creado_en'] = date('Y-m-d H:i:s');

  if (!$data) return;

  $fields = array_keys($data);
  $ph = implode(',', array_fill(0, count($fields), '?'));
  $sql = "INSERT INTO pqrs_file(" . implode(',', $fields) . ") VALUES ($ph)";
  $pdo->prepare($sql)->execute(array_values($data));
}

/* ===================== CATÁLOGOS ===================== */
$statusRows = [];
try {
  $statusRows = $pdo->query("
    SELECT clave,nombre,orden
    FROM pqrs_cat_status
    WHERE activo=1
    ORDER BY orden
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
  $statusRows = [
    ['clave'=>'NUEVA','nombre'=>'NUEVA','orden'=>10],
    ['clave'=>'EN_ESPERA','nombre'=>'EN_ESPERA','orden'=>20],
    ['clave'=>'EN_PROCESO','nombre'=>'EN_PROCESO','orden'=>30],
    ['clave'=>'CERRADA','nombre'=>'CERRADA','orden'=>90],
  ];
}

$motivosCierre = [];
try {
  $motivosCierre = $pdo->query("
    SELECT id_motivo,nombre,clave
    FROM pqrs_cat_motivo
    WHERE activo=1 AND tipo='CIERRE'
    ORDER BY nombre
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {}

/* ===================== CASO ===================== */
$st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
$st->execute([$id_case]);
$case = $st->fetch(PDO::FETCH_ASSOC);

if (!$case) {
  echo "<div class='container-fluid'><div class='alert alert-warning'>Caso no encontrado</div></div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

$okMsg = '';
$errMsg = '';

/* ===================== POST ===================== */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    $accion  = $_POST['accion'] ?? '';
    $usuario = $_SESSION['usuario'] ?? 'system';

    // ----- ACTUALIZAR (STATUS + COMENTARIO) -----
    if ($accion === 'actualizar') {

      $nuevo = trim($_POST['status_clave'] ?? '');
      $detalle = trim($_POST['detalle'] ?? '');

      if ($nuevo==='') throw new RuntimeException("Selecciona un status.");

      $status_anterior = (string)($case['status_clave'] ?? '');
      $status_nuevo = $nuevo;

      // Si ya está cerrada, no permitir cambios de status (sí permitir nota)
      if ($status_anterior === 'CERRADA' && $status_nuevo !== 'CERRADA') {
        throw new RuntimeException("El caso ya está cerrado. No se puede modificar el status.");
      }

      // Actualiza status del caso (solo si cambia)
      if ($status_nuevo !== $status_anterior) {
        $pdo->prepare("UPDATE pqrs_case SET status_clave=? WHERE id_case=?")->execute([$status_nuevo,$id_case]);
      }

      // Evento: STATUS cuando cambia, NOTA cuando es el mismo
      $evento = ($status_nuevo !== $status_anterior) ? 'STATUS' : 'NOTA';

      if ($detalle==='') {
        $detalle = ($evento==='STATUS')
          ? "Cambio de status a: $status_nuevo"
          : "Actualización registrada";
      }

      // Insert con BEFORE/AFTER
      $pdo->prepare("
        INSERT INTO pqrs_event(id_case,evento,detalle,status_anterior,status_nuevo,usuario)
        VALUES (?,?,?,?,?,?)
      ")->execute([
        $id_case,
        $evento,
        $detalle,
        ($evento==='STATUS' ? $status_anterior : null),
        ($evento==='STATUS' ? $status_nuevo : null),
        $usuario
      ]);

      header("Location: pqrs_view.php?id_case=$id_case&ok=1");
      exit;
    }

    // ----- CIERRE -----
    if ($accion === 'cerrar') {

      if (($case['status_clave'] ?? '') === 'CERRADA') {
        throw new RuntimeException("El caso ya está cerrado.");
      }

      $motivo_id = (int)($_POST['motivo_id'] ?? 0);
      $detalle   = trim($_POST['detalle'] ?? '');

      if ($motivo_id <= 0) throw new RuntimeException("Selecciona un motivo de cierre.");

      $stM = $pdo->prepare("SELECT clave,nombre FROM pqrs_cat_motivo WHERE id_motivo=?");
      $stM->execute([$motivo_id]);
      $mot = $stM->fetch(PDO::FETCH_ASSOC);
      if (!$mot) throw new RuntimeException("Motivo inválido.");

      $procede = (strpos($mot['clave'],'NP_') === 0) ? 0 : 1;

      $pdo->prepare("
        UPDATE pqrs_case
        SET status_clave='CERRADA',
            motivo_cierre_id=?,
            motivo_cierre_txt=?,
            procede=?,
            fecha_cierre=NOW()
        WHERE id_case=?
      ")->execute([$motivo_id,$mot['nombre'],$procede,$id_case]);

      $evento = $procede ? 'CIERRE' : 'NO PROCEDE';
      $txtEvt = "Motivo: ".$mot['nombre'].($detalle ? " | $detalle" : "");

      $pdo->prepare("
        INSERT INTO pqrs_event(id_case,evento,detalle,status_anterior,status_nuevo,usuario)
        VALUES (?,?,?,?,?,?)
      ")->execute([$id_case,$evento,$txtEvt,(string)($case['status_clave'] ?? ''),'CERRADA',$usuario]);

      header("Location: pqrs_view.php?id_case=$id_case&ok=1");
      exit;
    }

    // ----- UPLOAD -----
    if ($accion === 'upload') {
      if (!isset($_FILES['archivo'])) throw new RuntimeException("No se recibió archivo.");

      $uploadDir = __DIR__ . '/uploads_pqrs';
      if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

      $files = $_FILES['archivo'];
      $count = is_array($files['name']) ? count($files['name']) : 1;

      for ($i=0; $i<$count; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $err  = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $size = (int)(is_array($files['size']) ? $files['size'][$i] : $files['size']);
        $mime = (string)(is_array($files['type']) ? $files['type'][$i] : $files['type']);

        if ($err !== UPLOAD_ERR_OK) continue;
        if (!is_uploaded_file($tmp)) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeExt = preg_replace('/[^a-z0-9]+/','',$ext);
        $safeExt = $safeExt ?: 'bin';

        $fn = 'PQ' . $id_case . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
        $dest = $uploadDir . '/' . $fn;

        if (@move_uploaded_file($tmp, $dest)) {
          $rel = 'public/pqrs/uploads_pqrs/' . $fn; // referencia operativa
          // Registrar evento
          $pdo->prepare("
            INSERT INTO pqrs_event(id_case,evento,detalle,usuario)
            VALUES (?,?,?,?)
          ")->execute([$id_case,'ARCHIVO',"Adjunto: $name",$usuario]);

          // Insert en pqrs_file (si aplica)
          pqrs_file_insert($pdo, $id_case, $rel, $name, $mime, $size, $usuario);
        }
      }

      header("Location: pqrs_view.php?id_case=$id_case&ok=1");
      exit;
    }

  } catch(Throwable $e) {
    $errMsg = $e->getMessage();
  }
}

if (isset($_GET['ok'])) $okMsg = "Actualización aplicada correctamente.";

/* ===================== RELOAD DATA ===================== */
$st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
$st->execute([$id_case]);
$case = $st->fetch(PDO::FETCH_ASSOC) ?: $case;

$ev = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY id_event DESC");
$ev->execute([$id_case]);
$events = $ev->fetchAll(PDO::FETCH_ASSOC);

// Archivos (si tabla existe)
$filesRows = [];
try {
  $pdo->query("SELECT 1 FROM pqrs_file LIMIT 1");
  $stF = $pdo->prepare("SELECT * FROM pqrs_file WHERE id_case=? ORDER BY 1 DESC");
  $stF->execute([$id_case]);
  $filesRows = $stF->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $filesRows = []; }
?>

<style>
  :root{ --ap-blue:#0b3a86; --ap-border:#e6ecff; --ap-soft:#f4f7ff; }
  .ap-title{color:var(--ap-blue); font-weight:800;}
  .ap-card{border:1px solid var(--ap-border); border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.04);}
  .ap-card .card-header{background:linear-gradient(180deg,#f7f9ff 0,#ffffff 100%); border-bottom:1px solid var(--ap-border); color:var(--ap-blue); font-weight:800;}
  .ap-badge{font-size:11px; border-radius:999px; padding:.35rem .55rem; font-weight:800; background:#eaf2ff; color:var(--ap-blue);}
  .ap-kpi{background:var(--ap-soft); border:1px solid var(--ap-border); border-radius:12px; padding:10px 12px;}
  .ap-kpi .lbl{font-size:11px; color:#5f6b7a;}
  .ap-kpi .val{font-size:13px; font-weight:800; color:#102a43;}
  .ap-table{font-size:10px;}
  .ap-scroll{max-height:180px; overflow:auto;}
  .ap-help{font-size:11px; color:#6c757d;}
</style>

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
    <div>
      <h3 class="mb-0 ap-title"><i class="fa fa-clipboard-check me-2"></i>Detalle PQRS – <span class="text-primary"><?= h($case['fol_pqrs']) ?></span></h3>
      <div class="ap-help">
        Creado: <?= h($case['creado_en']) ?><?= ($case['creado_por'] ? " | ".h($case['creado_por']) : "") ?>
        <span class="ms-2 ap-badge"><?= h($case['status_clave']) ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="pqrs.php"><i class="fa fa-arrow-left me-1"></i>Volver</a>
      <a class="btn btn-outline-danger btn-sm" target="_blank" href="pqrs_pdf.php?fol=<?= urlencode((string)$case['fol_pqrs']) ?>"><i class="fa fa-file-pdf me-1"></i>PDF</a>
    </div>
  </div>

  <?php if ($okMsg): ?><div class="alert alert-success ap-card py-2"><i class="fa fa-check-circle me-2"></i><?= h($okMsg) ?></div><?php endif; ?>
  <?php if ($errMsg): ?><div class="alert alert-danger ap-card py-2"><i class="fa fa-triangle-exclamation me-2"></i><?= h($errMsg) ?></div><?php endif; ?>

  <div class="row g-3">

    <div class="col-lg-7">
      <div class="card ap-card">
        <div class="card-header"><i class="fa fa-circle-info me-1"></i>Resumen ejecutivo</div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-4"><div class="ap-kpi"><div class="lbl">Cliente</div><div class="val"><?= h($case['cve_clte']) ?></div></div></div>
            <div class="col-md-4"><div class="ap-kpi"><div class="lbl">Almacén</div><div class="val"><?= h($case['cve_almacen']) ?></div></div></div>
            <div class="col-md-4"><div class="ap-kpi"><div class="lbl">Tipo</div><div class="val"><?= h($case['tipo']) ?></div></div></div>

            <div class="col-md-6"><div class="ap-kpi"><div class="lbl">Referencia</div><div class="val"><?= h($case['ref_tipo']) ?> <?= h($case['ref_folio']) ?></div></div></div>
            <div class="col-md-6"><div class="ap-kpi"><div class="lbl">Susceptible a cobro</div><div class="val"><?= ($case['susceptible_cobro']?'Sí':'No') ?></div></div></div>

            <div class="col-md-12"><div class="ap-kpi"><div class="lbl">Motivo de apertura</div><div class="val"><?= h($case['motivo_registro_txt'] ?? '—') ?></div></div></div>
          </div>

          <hr>

          <div class="text-muted mb-1" style="font-size:12px;">Descripción</div>
          <div style="white-space:pre-line; font-size:12px;"><?= h($case['descripcion']) ?></div>

        </div>
      </div>
    </div>

    <div class="col-lg-5">

      <div class="card ap-card mb-3">
        <div class="card-header"><i class="fa fa-pen-to-square me-1"></i>Seguimiento / Actualización</div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="accion" value="actualizar">
            <div class="mb-2">
              <label class="form-label" style="font-size:12px;font-weight:800;">Estatus</label>
              <select class="form-select form-select-sm" name="status_clave" required>
                <?php foreach($statusRows as $r): ?>
                  <option value="<?= h($r['clave']) ?>" <?= ($r['clave']===$case['status_clave']?'selected':'') ?>>
                    <?= h($r['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label" style="font-size:12px;font-weight:800;">Comentario</label>
              <textarea class="form-control form-control-sm" name="detalle" rows="3" placeholder="Describe el avance o cierre del caso"></textarea>
              <div class="ap-help mt-1">Si el estatus no cambia, se registrará como <b>NOTA</b> en eventos.</div>
            </div>
            <div class="d-flex justify-content-end">
              <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-floppy-disk me-1"></i>Registrar actualización</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card ap-card mb-3">
        <div class="card-header"><i class="fa fa-paperclip me-1"></i>Archivos / Evidencias</div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" class="mb-2">
            <input type="hidden" name="accion" value="upload">
            <div class="d-flex gap-2 align-items-center">
              <input class="form-control form-control-sm" type="file" name="archivo[]" multiple>
              <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fa fa-upload me-1"></i>Subir</button>
            </div>
            <div class="ap-help mt-1">Sugerido: fotos, PDFs, layouts y evidencias. Queda trazado en eventos.</div>
          </form>

          <?php if (!$filesRows): ?>
            <div class="text-muted" style="font-size:12px;">Sin archivos registrados.</div>
          <?php else: ?>
            <div class="ap-scroll">
              <table class="table table-sm table-hover ap-table mb-0">
                <thead><tr><th>Archivo</th><th style="width:110px;">Fecha</th></tr></thead>
                <tbody>
                  <?php foreach($filesRows as $f): 
                    $ruta = $f['ruta'] ?? ($f['file_path'] ?? ($f['path'] ?? ($f['archivo'] ?? '')));
                    $nom = $f['nombre'] ?? ($f['original_name'] ?? ($f['filename'] ?? basename((string)$ruta)));
                    $fec = $f['creado_en'] ?? ($f['fecha'] ?? '');
                  ?>
                    <tr>
                      <td>
                        <?php if ($ruta): ?>
                          <a href="/<?= h(ltrim((string)$ruta,'/')) ?>" target="_blank"><?= h($nom) ?></a>
                        <?php else: ?>
                          <?= h($nom) ?>
                        <?php endif; ?>
                      </td>
                      <td><?= h($fec) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card ap-card">
        <div class="card-header"><i class="fa fa-circle-xmark me-1"></i>Cierre</div>
        <div class="card-body">
          <?php if (($case['status_clave'] ?? '') === 'CERRADA'): ?>
            <div class="alert alert-secondary py-2 mb-0">Caso cerrado. Motivo: <b><?= h($case['motivo_cierre_txt'] ?? '—') ?></b></div>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="accion" value="cerrar">
              <div class="mb-2">
                <label class="form-label" style="font-size:12px;font-weight:800;">Motivo</label>
                <select class="form-select form-select-sm" name="motivo_id" required>
                  <option value="">-- Selecciona --</option>
                  <?php foreach($motivosCierre as $m): ?>
                    <option value="<?= (int)$m['id_motivo'] ?>"><?= h($m['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label" style="font-size:12px;font-weight:800;">Detalle (opcional)</label>
                <textarea class="form-control form-control-sm" name="detalle" rows="2"></textarea>
              </div>
              <div class="d-flex justify-content-end">
                <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('¿Confirmas el cierre del caso?');">
                  <i class="fa fa-lock me-1"></i>Cerrar caso
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <div class="col-12">
      <div class="card ap-card">
        <div class="card-header"><i class="fa fa-clock-rotate-left me-1"></i>Bitácora de eventos</div>
        <div class="card-body p-0">
          <div style="max-height:240px; overflow:auto;">
            <table class="table table-sm table-hover ap-table mb-0">
              <thead>
                <tr>
                  <th style="width:140px;">Fecha</th>
                  <th style="width:90px;">Evento</th>
                  <th>Detalle</th>
                  <th style="width:120px;">Antes</th>
                  <th style="width:120px;">Después</th>
                  <th style="width:140px;">Usuario</th>
                </tr>
              </thead>
              <tbody>
              <?php if (!$events): ?>
                <tr><td colspan="6" class="text-muted p-3">Sin eventos.</td></tr>
              <?php else: foreach($events as $e): ?>
                <tr>
                  <td><?= h($e['creado_en'] ?? '') ?></td>
                  <td><?= h($e['evento'] ?? '') ?></td>
                  <td><?= h($e['detalle'] ?? '') ?></td>
                  <td><?= h($e['status_anterior'] ?? '') ?></td>
                  <td><?= h($e['status_nuevo'] ?? '') ?></td>
                  <td><?= h($e['usuario'] ?? '') ?></td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
