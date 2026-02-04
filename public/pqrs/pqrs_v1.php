<?php
// =====================================================
// PQRS - Listado (v2 mejorado + filtros horizontales)
// Ruta: /public/pqrs/pqrs.php
// =====================================================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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

// filtros
$f_status = trim((string)($_GET['status'] ?? ''));
$f_clte   = trim((string)($_GET['cve_clte'] ?? ''));
$f_ref    = trim((string)($_GET['ref_folio'] ?? ''));
$f_desde  = trim((string)($_GET['desde'] ?? ''));
$f_hasta  = trim((string)($_GET['hasta'] ?? ''));
$f_q      = trim((string)($_GET['q'] ?? ''));

// status catálogo
$statusRows = [];
try {
  $statusRows = $pdo->query("SELECT clave,nombre,orden FROM pqrs_cat_status WHERE activo=1 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){
  $statusRows = [
    ['clave'=>'NUEVA','nombre'=>'Nueva','orden'=>10],
    ['clave'=>'EN_PROCESO','nombre'=>'En proceso','orden'=>20],
    ['clave'=>'EN_ESPERA','nombre'=>'En espera','orden'=>30],
    ['clave'=>'CERRADA','nombre'=>'Cerrada','orden'=>90],
    ['clave'=>'NO_PROCEDE','nombre'=>'No procede','orden'=>95],
  ];
}

// KPIs
$kpi = [];
try {
  $st = $pdo->query("SELECT status_clave, COUNT(*) total FROM pqrs_case GROUP BY status_clave");
  foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){
    $kpi[$r['status_clave']] = (int)$r['total'];
  }
} catch(Throwable $e){}

// listado
$sql = "SELECT id_case, fol_pqrs, cve_clte, cve_almacen, tipo, ref_tipo, ref_folio, status_clave, creado_en
        FROM pqrs_case WHERE 1=1 ";
$p = [];

if ($f_status !== '') { $sql .= " AND status_clave=? "; $p[] = $f_status; }
if ($f_clte !== '')   { $sql .= " AND cve_clte=? "; $p[] = $f_clte; }
if ($f_ref !== '')    { $sql .= " AND ref_folio LIKE ? "; $p[] = "%$f_ref%"; }
if ($f_desde !== '')  { $sql .= " AND DATE(creado_en) >= ? "; $p[] = $f_desde; }
if ($f_hasta !== '')  { $sql .= " AND DATE(creado_en) <= ? "; $p[] = $f_hasta; }

if ($f_q !== '') {
  $sql .= " AND (fol_pqrs LIKE ? OR ref_folio LIKE ? OR cve_clte LIKE ?) ";
  $p[] = "%$f_q%"; $p[] = "%$f_q%"; $p[] = "%$f_q%";
}

$sql .= " ORDER BY id_case DESC LIMIT 500";

$rows = [];
try {
  $st = $pdo->prepare($sql);
  $st->execute($p);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $rows=[]; }

?>

<style>
/* Compacta la franja de filtros y evita que empuje la tabla hacia abajo */
.pqrs-filter-bar .form-group{ margin-bottom: .25rem; }
.pqrs-filter-bar label{ font-size: .82rem; color:#6c757d; margin-bottom:.15rem; }
.pqrs-filter-bar .form-control{ height: 36px; }
.pqrs-filter-actions{ display:flex; gap:.5rem; align-items:flex-end; justify-content:flex-end; }
@media (max-width: 992px){
  .pqrs-filter-actions{ justify-content:flex-start; }
}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Control de Incidencias (PQRS v2)</h3>
      <div class="text-muted">Gestión por status, referencia y trazabilidad operacional.</div>
    </div>
    <a href="pqrs_new.php" class="btn btn-success">
      <i class="fas fa-plus"></i> Nueva Incidencia
    </a>
  </div>

  <!-- KPIs -->
  <div class="row">
    <?php foreach($statusRows as $s):
      $clave = $s['clave'];
      $total = (int)($kpi[$clave] ?? 0);
      $active = ($f_status === $clave);
      $href = "pqrs.php?status=" . urlencode($clave);
    ?>
      <div class="col-md-3 mb-3">
        <a href="<?= h($href) ?>" style="text-decoration:none;color:inherit;">
          <div class="card" style="border-left:6px solid #0d6efd; <?= $active ? 'box-shadow:0 0 0 2px rgba(13,110,253,.25);' : '' ?>">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted"><?= h($s['nombre']) ?></div>
                <div style="font-size:30px;font-weight:800;"><?= $total ?></div>
              </div>
              <div class="text-right">
                <span class="badge <?= h(badge_class($clave)) ?>"><?= h($clave) ?></span>
                <div class="mt-2">
                  <span class="btn btn-sm btn-outline-primary">Ver</span>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Filtros HORIZONTALES -->
  <div class="card mb-3">
    <div class="card-body pqrs-filter-bar">
      <form class="row">

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
          <div class="form-group">
            <label>Status</label>
            <select class="form-control" name="status">
              <option value="">Todos</option>
              <?php foreach($statusRows as $s): ?>
                <option value="<?= h($s['clave']) ?>" <?= ($f_status===$s['clave']?'selected':'') ?>><?= h($s['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
          <div class="form-group">
            <label>Cliente</label>
            <input class="form-control" name="cve_clte" value="<?= h($f_clte) ?>" placeholder="DEMO104">
          </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
          <div class="form-group">
            <label>Referencia</label>
            <input class="form-control" name="ref_folio" value="<?= h($f_ref) ?>" placeholder="PED-... / OC-...">
          </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
          <div class="form-group">
            <label>Búsqueda rápida</label>
            <input class="form-control" name="q" value="<?= h($f_q) ?>" placeholder="PQ / PED / cliente">
          </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
          <div class="form-group">
            <label>Desde</label>
            <input type="date" class="form-control" name="desde" value="<?= h($f_desde) ?>">
          </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
          <div class="form-group">
            <label>Hasta</label>
            <input type="date" class="form-control" name="hasta" value="<?= h($f_hasta) ?>">
          </div>
        </div>

        <div class="col-12 mt-2 pqrs-filter-actions">
          <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
          <a class="btn btn-light" href="pqrs.php">Limpiar</a>
        </div>

      </form>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted"><?= count($rows) ?> resultados</div>
        <?php if ($f_status || $f_clte || $f_ref || $f_desde || $f_hasta || $f_q): ?>
          <div class="text-muted"><i class="fas fa-bolt"></i> Filtro activo</div>
        <?php endif; ?>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-sm">
          <thead>
            <tr>
              <th>Folio PQRS</th>
              <th>Cliente</th>
              <th>Referencia</th>
              <th>Almacén</th>
              <th>Tipo</th>
              <th>Status</th>
              <th>Creado</th>
              <th>Edad</th>
              <th style="width:90px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows): ?>
              <tr><td colspan="9" class="text-muted">Sin registros.</td></tr>
            <?php else: foreach($rows as $r):
              $creado = strtotime($r['creado_en']);
              $edad = $creado ? floor((time()-$creado)/86400) : '';
            ?>
              <tr>
                <td><b><?= h($r['fol_pqrs']) ?></b></td>
                <td><?= h($r['cve_clte']) ?></td>
                <td><?= h($r['ref_tipo']) ?>: <?= h($r['ref_folio']) ?></td>
                <td><?= h($r['cve_almacen']) ?></td>
                <td><?= h($r['tipo']) ?></td>
                <td><span class="badge <?= h(badge_class($r['status_clave'])) ?>"><?= h($r['status_clave']) ?></span></td>
                <td><?= h($r['creado_en']) ?></td>
                <td><?= ($edad!=='' ? $edad.'d' : '') ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" title="Ver" href="pqrs_view.php?id_case=<?= (int)$r['id_case'] ?>">
                    <i class="fas fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
