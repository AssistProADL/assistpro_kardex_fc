<?php
// =====================================================
// PQRS - View (Detalle)
// =====================================================
ob_start();
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id_case = (int)($_GET['id_case'] ?? 0);
if ($id_case <= 0) {
  echo "<div class='container-fluid'><div class='alert alert-danger'>Falta id_case</div></div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

/* ===================== CATÁLOGOS ===================== */
$statusRows = $pdo->query("
  SELECT clave,nombre,orden
  FROM pqrs_cat_status
  WHERE activo=1
  ORDER BY orden
")->fetchAll(PDO::FETCH_ASSOC);

$motivosCierre = $pdo->query("
  SELECT id_motivo,nombre,clave
  FROM pqrs_cat_motivo
  WHERE activo=1 AND tipo='CIERRE'
  ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

/* ===================== CASO ===================== */
$st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
$st->execute([$id_case]);
$case = $st->fetch(PDO::FETCH_ASSOC);

if (!$case) {
  echo "<div class='container-fluid'><div class='alert alert-warning'>Caso no encontrado</div></div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

$ev = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY id_event DESC");
$ev->execute([$id_case]);
$events = $ev->fetchAll(PDO::FETCH_ASSOC);

/* ===================== POST ===================== */
$okMsg = '';
$errMsg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    $accion  = $_POST['accion'] ?? '';
    $usuario = $_SESSION['usuario'] ?? null;

    /* -------- NOTA -------- */
    if ($accion === 'nota') {
      $detalle = trim($_POST['detalle'] ?? '');
      if ($detalle==='') {
        throw new RuntimeException("La nota no puede ir vacía.");
      }

      $pdo->prepare("
        INSERT INTO pqrs_event(id_case,evento,detalle,usuario)
        VALUES (?,?,?,?)
      ")->execute([$id_case,'NOTA',$detalle,$usuario]);

      header("Location: pqrs_view.php?id_case=$id_case&ok=1");
      exit;
    }

    /* -------- STATUS -------- */
    if ($accion === 'status') {

      if ($case['status_clave'] === 'CERRADA') {
        throw new RuntimeException("El caso ya está cerrado. No se puede modificar el status.");
      }

      $nuevo = trim($_POST['status_clave'] ?? '');
      if ($nuevo==='') {
        throw new RuntimeException("Selecciona un status.");
      }

      $pdo->prepare("
        UPDATE pqrs_case
        SET status_clave=?
        WHERE id_case=?
      ")->execute([$nuevo,$id_case]);

      $pdo->prepare("
        INSERT INTO pqrs_event(id_case,evento,detalle,usuario)
        VALUES (?,?,?,?)
      ")->execute([$id_case,'STATUS',"Cambio de status a: $nuevo",$usuario]);

      header("Location: pqrs_view.php?id_case=$id_case&ok=1");
      exit;
    }

    /* -------- CIERRE -------- */
    if ($accion === 'cerrar') {

      if ($case['status_clave'] === 'CERRADA') {
        throw new RuntimeException("El caso ya está cerrado.");
      }

      $motivo_id = (int)($_POST['motivo_id'] ?? 0);
      $detalle   = trim($_POST['detalle'] ?? '');

      if ($motivo_id <= 0) {
        throw new RuntimeException("Selecciona un motivo de cierre.");
      }

      $stM = $pdo->prepare("
        SELECT clave,nombre
        FROM pqrs_cat_motivo
        WHERE id_motivo=?
      ");
      $stM->execute([$motivo_id]);
      $mot = $stM->fetch(PDO::FETCH_ASSOC);

      if (!$mot) {
        throw new RuntimeException("Motivo inválido.");
      }

      $procede = (strpos($mot['clave'],'NP_') === 0) ? 0 : 1;

      $pdo->prepare("
        UPDATE pqrs_case
        SET
          status_clave = 'CERRADA',
          motivo_cierre_id = ?,
          motivo_cierre_txt = ?,
          procede = ?,
          fecha_cierre = NOW()
        WHERE id_case = ?
      ")->execute([
        $motivo_id,
        $mot['nombre'],
        $procede,
        $id_case
      ]);

      $evento = $procede ? 'CIERRE' : 'NO PROCEDE';
      $txtEvt = "Motivo: ".$mot['nombre'].($detalle ? " | $detalle" : "");

      $pdo->prepare("
        INSERT INTO pqrs_event(id_case,evento,detalle,usuario)
        VALUES (?,?,?,?)
      ")->execute([$id_case,$evento,$txtEvt,$usuario]);

      header("Location: pqrs_view.php?id_case=$id_case&ok=1");
      exit;
    }

  } catch(Throwable $e) {
    $errMsg = $e->getMessage();
  }
}

if (isset($_GET['ok'])) {
  $okMsg = "Actualización aplicada correctamente.";
}
?>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">
        Caso PQRS:
        <span class="text-primary"><?= h($case['fol_pqrs']) ?></span>
        <span class="badge badge-info ml-2"><?= h($case['status_clave']) ?></span>
      </h3>
      <div class="text-muted">
        Creado: <?= h($case['creado_en']) ?>
        <?= $case['creado_por'] ? " | ".h($case['creado_por']) : "" ?>
      </div>
    </div>
    <a class="btn btn-outline-secondary" href="pqrs.php">Regresar</a>
  </div>

  <?php if ($okMsg): ?><div class="alert alert-success"><?= h($okMsg) ?></div><?php endif; ?>
  <?php if ($errMsg): ?><div class="alert alert-danger"><?= h($errMsg) ?></div><?php endif; ?>

  <div class="row">

    <!-- ================= RESUMEN ================= -->
    <div class="col-lg-7">
      <div class="card mb-3">
        <div class="card-header"><b>Resumen ejecutivo</b></div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-4"><div class="text-muted">Cliente</div><b><?= h($case['cve_clte']) ?></b></div>
            <div class="col-md-4"><div class="text-muted">Almacén</div><b><?= h($case['cve_almacen']) ?></b></div>
            <div class="col-md-4"><div class="text-muted">Tipo</div><b><?= h($case['tipo']) ?></b></div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-6"><div class="text-muted">Referencia</div><b><?= h($case['ref_tipo']) ?> <?= h($case['ref_folio']) ?></b></div>
            <div class="col-md-6"><div class="text-muted">Susceptible a cobro</div><b><?= $case['susceptible_cobro']?'Sí':'No' ?></b></div>
          </div>

          <hr>

          <!-- 🔹 MOTIVO DE APERTURA -->
          <div class="row">
            <div class="col-md-6">
              <div class="text-muted">Motivo de apertura</div>
              <b><?= h($case['motivo_registro_txt'] ?? '—') ?></b>
            </div>
          </div>

          <hr>

          <div class="text-muted">Descripción</div>
          <div style="white-space:pre-wrap;"><b><?= h($case['descripcion']) ?></b></div>

        </div>
      </div>

      <!-- ================= ACCIONES ================= -->
      <div class="card mb-3">
        <div class="card-header"><b>Acciones operativas</b></div>
        <div class="card-body">

          <div class="row">

            <div class="col-md-6">
              <?php if ($case['status_clave'] !== 'CERRADA'): ?>
                <form method="post">
                  <input type="hidden" name="accion" value="status">
                  <label>Cambiar status</label>
                  <div class="input-group">
                    <select class="form-control" name="status_clave">
                      <?php foreach($statusRows as $s): ?>
                        <option value="<?= h($s['clave']) ?>" <?= $case['status_clave']===$s['clave']?'selected':'' ?>>
                          <?= h($s['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <div class="input-group-append">
                      <button class="btn btn-primary">Actualizar</button>
                    </div>
                  </div>
                </form>
              <?php else: ?>
                <div class="text-muted">El caso está cerrado. El status no puede modificarse.</div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <form method="post">
                <input type="hidden" name="accion" value="nota">
                <label>Agregar nota</label>
                <div class="input-group">
                  <input class="form-control" name="detalle" required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-primary">Guardar</button>
                  </div>
                </div>
              </form>
            </div>

          </div>

          <hr>

          <?php if ($case['status_clave'] !== 'CERRADA'): ?>
            <form method="post" class="border rounded p-3">
              <input type="hidden" name="accion" value="cerrar">
              <div class="font-weight-bold mb-2">Cerrar caso</div>

              <select class="form-control mb-2" name="motivo_id" required>
                <option value="">Motivo de cierre</option>
                <?php foreach($motivosCierre as $m): ?>
                  <option value="<?= (int)$m['id_motivo'] ?>"><?= h($m['nombre']) ?></option>
                <?php endforeach; ?>
              </select>

              <textarea class="form-control mb-2" name="detalle" rows="2"
                placeholder="Detalle / evidencia"></textarea>

              <button class="btn btn-success">Cerrar caso</button>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- ================= TIMELINE ================= -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <b>Timeline / Auditoría</b>
          <span class="text-muted"><?= count($events) ?> eventos</span>
        </div>
        <div class="card-body" style="max-height:720px;overflow:auto;">
          <?php foreach($events as $e): ?>
            <div class="border rounded p-2 mb-2">
              <div class="d-flex justify-content-between">
                <span class="badge badge-light"><?= h($e['evento']) ?></span>
                <span class="text-muted"><?= h($e['creado_en']) ?></span>
              </div>
              <div class="mt-2"><?= h($e['detalle']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
