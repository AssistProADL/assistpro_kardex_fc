<?php
// =====================================================
// PQRS - View (Detalle)
// Ruta: /public/pqrs/pqrs_view.php?id_case=123
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

// catálogos
$statusRows = [];
$motivosCierre = [];
$motivosNoProcede = [];
try{
  $statusRows = $pdo->query("SELECT clave,nombre,es_final,orden FROM pqrs_cat_status WHERE activo=1 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
  $stM = $pdo->prepare("SELECT id_motivo, tipo, nombre FROM pqrs_cat_motivo WHERE activo=1 AND tipo IN ('CIERRE','NO_PROCEDE') ORDER BY tipo, nombre");
  $stM->execute();
  $m = $stM->fetchAll(PDO::FETCH_ASSOC);
  foreach($m as $r){
    if ($r['tipo']==='CIERRE') $motivosCierre[] = $r;
    if ($r['tipo']==='NO_PROCEDE') $motivosNoProcede[] = $r;
  }
}catch(Throwable $e){}

// cargar caso
$case = null;
$events = [];
try{
  $st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
  $st->execute([$id_case]);
  $case = $st->fetch(PDO::FETCH_ASSOC);

  if ($case) {
    $ev = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY id_event DESC");
    $ev->execute([$id_case]);
    $events = $ev->fetchAll(PDO::FETCH_ASSOC);
  }
}catch(Throwable $e){}

if (!$case) {
  echo "<div class='container-fluid'><div class='alert alert-warning'>Caso no encontrado</div></div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

// Helpers UI
function tipo_label($t){
  return ['P'=>'Petición','Q'=>'Queja','R'=>'Reclamo','S'=>'Sugerencia'][$t] ?? $t;
}
function status_name($statusRows, $clave){
  foreach($statusRows as $s) if ($s['clave']===$clave) return $s['nombre'];
  return $clave;
}
function badge_class($clave){
  $map = [
    'NUEVA'=>'badge-primary',
    'EN_PROCESO'=>'badge-info',
    'EN_ESPERA'=>'badge-warning',
    'CERRADA'=>'badge-success',
    'NO_PROCEDE'=>'badge-secondary',
  ];
  return $map[$clave] ?? 'badge-dark';
}

$okMsg = '';
$errMsg = '';

// =============================
// POST acciones (nota, status, cierre, no procede)
// =============================
if ($_SERVER['REQUEST_METHOD']==='POST') {
  try{
    $accion = trim((string)($_POST['accion'] ?? ''));
    $usuario = (string)($_SESSION['usuario'] ?? null);

    if ($accion === 'nota') {
      $detalle = trim((string)($_POST['detalle'] ?? ''));
      if ($detalle==='') throw new RuntimeException("La nota no puede ir vacía.");

      $st = $pdo->prepare("INSERT INTO pqrs_event(id_case, evento, detalle, usuario) VALUES(?,?,?,?)");
      $st->execute([$id_case, 'NOTA', $detalle, ($usuario!==''?$usuario:null)]);
      header("Location: pqrs_view.php?id_case=".$id_case."&ok=1");
      exit;
    }

    if ($accion === 'status') {
      $nuevo = trim((string)($_POST['status_clave'] ?? ''));
      if ($nuevo==='') throw new RuntimeException("Selecciona un status.");

      $pdo->beginTransaction();
      $st = $pdo->prepare("UPDATE pqrs_case SET status_clave=?, actualizado_en=NOW(), actualizado_por=? WHERE id_case=?");
      $st->execute([$nuevo, ($usuario!==''?$usuario:null), $id_case]);

      $stE = $pdo->prepare("INSERT INTO pqrs_event(id_case, evento, detalle, usuario) VALUES(?,?,?,?)");
      $stE->execute([$id_case, 'STATUS', "Cambio de status a: ".$nuevo, ($usuario!==''?$usuario:null)]);

      $pdo->commit();
      header("Location: pqrs_view.php?id_case=".$id_case."&ok=1");
      exit;
    }

    if ($accion === 'cerrar' || $accion === 'no_procede') {
      $motivo_id = (int)($_POST['motivo_id'] ?? 0);
      $motivo_txt = trim((string)($_POST['motivo_txt'] ?? ''));
      $detalle = trim((string)($_POST['detalle'] ?? ''));

      if ($motivo_id<=0 && $motivo_txt==='') throw new RuntimeException("Indica motivo (catálogo o texto).");

      $nuevoStatus = ($accion==='cerrar') ? 'CERRADA' : 'NO_PROCEDE';
      $evento = ($accion==='cerrar') ? 'CIERRE' : 'NO_PROCEDE';

      $pdo->beginTransaction();

      // guardar motivo en campos existentes (pqrs_case tiene motivo_registro_*; para cierre/NP puedes tener columnas nuevas.
      // Si tu tabla ya trae columnas motivo_cierre_id/motivo_cierre_txt, cámbialo aquí.
      // De momento lo dejamos en un evento + status.
      $st = $pdo->prepare("UPDATE pqrs_case SET status_clave=?, actualizado_en=NOW(), actualizado_por=? WHERE id_case=?");
      $st->execute([$nuevoStatus, ($usuario!==''?$usuario:null), $id_case]);

      $mot = ($motivo_id>0 ? "MotivoID=$motivo_id" : "Motivo=".$motivo_txt);
      $det = trim(($mot." | ".$detalle));

      $stE = $pdo->prepare("INSERT INTO pqrs_event(id_case, evento, detalle, usuario) VALUES(?,?,?,?)");
      $stE->execute([$id_case, $evento, $det, ($usuario!==''?$usuario:null)]);

      $pdo->commit();
      header("Location: pqrs_view.php?id_case=".$id_case."&ok=1");
      exit;
    }

    throw new RuntimeException("Acción no soportada.");
  } catch(Throwable $e){
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    $errMsg = $e->getMessage();
  }
}

if (isset($_GET['ok']) && $_GET['ok']=='1') $okMsg = "Actualización aplicada correctamente.";

// reload rápido tras post
try{
  $st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
  $st->execute([$id_case]);
  $case = $st->fetch(PDO::FETCH_ASSOC);

  $ev = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY id_event DESC");
  $ev->execute([$id_case]);
  $events = $ev->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){}

?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">
        Caso PQRS: <span class="text-primary"><?= h($case['fol_pqrs']) ?></span>
        <span class="badge <?= h(badge_class($case['status_clave'])) ?> ml-2"><?= h(status_name($statusRows,$case['status_clave'])) ?></span>
      </h3>
      <div class="text-muted">
        Creado: <?= h($case['creado_en']) ?><?= $case['creado_por'] ? " | Por: ".h($case['creado_por']) : "" ?>
      </div>
    </div>
    <div>
      <a class="btn btn-outline-secondary" href="pqrs.php"><i class="fas fa-arrow-left"></i> Regresar</a>
      <a class="btn btn-success ml-2" href="pqrs_new.php"><i class="fas fa-plus"></i> Nueva</a>
    </div>
  </div>

  <?php if ($okMsg): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($okMsg) ?></div>
  <?php endif; ?>
  <?php if ($errMsg): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= h($errMsg) ?></div>
  <?php endif; ?>

  <div class="row">
    <!-- Col izquierda: Resumen -->
    <div class="col-lg-7">

      <div class="card mb-3">
        <div class="card-header"><b>Resumen ejecutivo</b></div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-4">
              <div class="text-muted">Cliente</div>
              <div style="font-weight:700;"><?= h($case['cve_clte']) ?></div>
            </div>
            <div class="col-md-4">
              <div class="text-muted">Almacén / CEDIS</div>
              <div style="font-weight:700;"><?= h($case['cve_almacen']) ?></div>
            </div>
            <div class="col-md-4">
              <div class="text-muted">Tipo</div>
              <div style="font-weight:700;"><?= h(tipo_label($case['tipo'])) ?></div>
            </div>
          </div>

          <hr/>

          <div class="row">
            <div class="col-md-6">
              <div class="text-muted">Referencia</div>
              <div style="font-weight:700;"><?= h($case['ref_tipo']) ?>: <?= h($case['ref_folio']) ?></div>
            </div>
            <div class="col-md-6">
              <div class="text-muted">Susceptible a cobro</div>
              <div style="font-weight:700;">
                <?= ((int)$case['susceptible_cobro']===1 ? "Sí" : "No") ?>
                <?= ($case['monto_estimado']!==null ? " | Monto: ".h($case['monto_estimado']) : "") ?>
              </div>
            </div>
          </div>

          <hr/>

          <div class="row">
            <div class="col-md-6">
              <div class="text-muted">Quién reporta</div>
              <div style="font-weight:700;">
                <?= h($case['reporta_nombre']) ?>
                <?= $case['reporta_contacto'] ? " | ".h($case['reporta_contacto']) : "" ?>
                <?= $case['reporta_cargo'] ? " | ".h($case['reporta_cargo']) : "" ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="text-muted">Responsables</div>
              <div style="font-weight:700;">
                Recibe: <?= h($case['responsable_recibo']) ?>
                <?= $case['responsable_accion'] ? "<br>Atiende: ".h($case['responsable_accion']) : "" ?>
              </div>
            </div>
          </div>

          <hr/>

          <div class="mb-2 text-muted">Asunto</div>
          <div style="font-weight:700;"><?= h($case['asunto'] ?? '') ?></div>

          <div class="mt-3 mb-2 text-muted">Descripción</div>
          <div style="white-space:pre-wrap;"><?= h($case['descripcion']) ?></div>

          <?php if ($case['motivo_registro_id'] || $case['motivo_registro_txt']): ?>
            <hr/>
            <div class="text-muted">Motivo registro</div>
            <div style="font-weight:700;">
              <?= $case['motivo_registro_id'] ? "ID: ".(int)$case['motivo_registro_id']." " : "" ?>
              <?= h($case['motivo_registro_txt'] ?? '') ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <!-- Acciones -->
      <div class="card mb-3">
        <div class="card-header"><b>Acciones operativas</b></div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-6">
              <form method="post">
                <input type="hidden" name="accion" value="status">
                <label class="font-weight-bold">Cambiar status</label>
                <div class="input-group">
                  <select class="form-control" name="status_clave" required>
                    <?php foreach($statusRows as $s): ?>
                      <option value="<?= h($s['clave']) ?>" <?= ($case['status_clave']===$s['clave']?'selected':'') ?>>
                        <?= h($s['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-sync"></i></button>
                  </div>
                </div>
                <small class="text-muted">Esto deja evidencia en el timeline.</small>
              </form>
            </div>

            <div class="col-md-6">
              <form method="post">
                <input type="hidden" name="accion" value="nota">
                <label class="font-weight-bold">Agregar nota</label>
                <div class="input-group">
                  <input class="form-control" name="detalle" placeholder="Avance, evidencia, acuerdos..." required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-comment"></i></button>
                  </div>
                </div>
                <small class="text-muted">Recomendado para construir trazabilidad.</small>
              </form>
            </div>
          </div>

          <hr/>

          <div class="row">
            <div class="col-md-6">
              <form method="post" class="border rounded p-3">
                <input type="hidden" name="accion" value="cerrar">
                <div class="font-weight-bold mb-2">Cerrar caso</div>
                <select class="form-control mb-2" name="motivo_id">
                  <option value="">Motivo (catálogo)</option>
                  <?php foreach($motivosCierre as $m): ?>
                    <option value="<?= (int)$m['id_motivo'] ?>"><?= h($m['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <input class="form-control mb-2" name="motivo_txt" placeholder="Motivo texto (si no existe en catálogo)">
                <textarea class="form-control mb-2" name="detalle" rows="2" placeholder="Detalle de cierre / evidencia"></textarea>
                <button class="btn btn-success btn-block" type="submit"><i class="fas fa-check"></i> Cerrar</button>
              </form>
            </div>

            <div class="col-md-6">
              <form method="post" class="border rounded p-3">
                <input type="hidden" name="accion" value="no_procede">
                <div class="font-weight-bold mb-2">Marcar como “No procede”</div>
                <select class="form-control mb-2" name="motivo_id">
                  <option value="">Motivo (catálogo)</option>
                  <?php foreach($motivosNoProcede as $m): ?>
                    <option value="<?= (int)$m['id_motivo'] ?>"><?= h($m['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <input class="form-control mb-2" name="motivo_txt" placeholder="Motivo texto (si no existe en catálogo)">
                <textarea class="form-control mb-2" name="detalle" rows="2" placeholder="Detalle / sustento"></textarea>
                <button class="btn btn-secondary btn-block" type="submit"><i class="fas fa-ban"></i> No procede</button>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Col derecha: Timeline -->
    <div class="col-lg-5">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <b>Timeline / Auditoría</b>
          <span class="text-muted"><?= count($events) ?> eventos</span>
        </div>
        <div class="card-body" style="max-height: 720px; overflow:auto;">
          <?php if(!$events): ?>
            <div class="text-muted">Sin eventos.</div>
          <?php else: ?>
            <?php foreach($events as $e): ?>
              <div class="mb-3 p-2 border rounded">
                <div class="d-flex justify-content-between">
                  <div>
                    <span class="badge badge-light"><?= h($e['evento']) ?></span>
                    <span class="ml-2 text-muted"><?= h($e['creado_en']) ?></span>
                  </div>
                  <div class="text-muted"><?= h($e['usuario'] ?? '') ?></div>
                </div>
                <div class="mt-2" style="white-space:pre-wrap;"><?= h($e['detalle'] ?? '') ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="alert alert-info">
        <b>Tip operativo:</b> usa el timeline para evidencias y acuerdos. Cuando haya disputa/cobro, esto es tu “bitácora legal”.
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
