<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$CAT_TABLE   = 'tubicacionesretencion';
$CAT_PK      = 'id';
$CAT_TITLE   = 'Tubicacionesretencion';
$CAT_COLUMNS = array (
  'id' => 
  array (
    'label' => 'Id',
    'nullable' => false,
    'pk' => false,
  ),
  'cve_ubicacion' => 
  array (
    'label' => 'Cve Ubicacion',
    'nullable' => false,
    'pk' => false,
  ),
  'cve_almacp' => 
  array (
    'label' => 'Cve Almacp',
    'nullable' => true,
    'pk' => false,
  ),
  'Activo' => 
  array (
    'label' => 'Activo',
    'nullable' => true,
    'pk' => false,
  ),
  'desc_ubicacion' => 
  array (
    'label' => 'Desc Ubicacion',
    'nullable' => true,
    'pk' => false,
  ),
  'B_Devolucion' => 
  array (
    'label' => 'B Devolucion',
    'nullable' => true,
    'pk' => false,
  ),
  'AreaStagging' => 
  array (
    'label' => 'AreaStagging',
    'nullable' => true,
    'pk' => false,
  ),
);

$pdo = db_pdo();

/* ---- Parámetros de grilla ---- */
$per_page = 25;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$search = trim((string)($_GET['q'] ?? ''));
$where  = '';
$params = [];

if ($search !== '') {
    $q = '%'.$search.'%';
    $cols  = array_keys($CAT_COLUMNS); // se buscan sólo las columnas de la grilla
    $parts = [];
    foreach ($cols as $c) {
        $parts[]  = "$c LIKE ?";
        $params[] = $q;
    }
    if ($parts) {
        $where = ' WHERE ' . implode(' OR ', $parts);
    }
}

/* ---- Total de registros ---- */
$total  = 0;
$errMsg = '';
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM $CAT_TABLE$where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
} catch (Throwable $e) {
    $errMsg = $e->getMessage();
}

/* ---- Filas ---- */
$rows = [];
try {
    $sql = "SELECT * FROM $CAT_TABLE$where ORDER BY $CAT_PK DESC LIMIT $offset,$per_page";
    $st  = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errMsg = $e->getMessage();
}

$pages = max(1, (int)ceil($total / $per_page));

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">

  <style>
    /* una fila = un registro, con scroll horizontal */
    .cat-grid-wrapper {
      max-height: 70vh;
      overflow-x: auto;
      overflow-y: auto;
    }
    .cat-grid-table th,
    .cat-grid-table td {
      white-space: nowrap;
      padding: 2px 4px;
      font-size: 10px;
    }
  </style>

  <h5 class="mb-2"><?php echo h($CAT_TITLE); ?></h5>

  <div class="d-flex justify-content-between small mb-2">
    <div>
      <b>Total:</b> <?php echo (int)$total; ?>
      — Página <?php echo $page; ?> de <?php echo $pages; ?>
    </div>
  </div>

  <form class="row g-2 mb-2" method="get">
    <div class="col-sm-4">
      <input name="q" value="<?php echo h($search); ?>"
             class="form-control form-control-sm" placeholder="Buscar...">
    </div>
    <div class="col-sm-2">
      <button class="btn btn-primary btn-sm w-100">Filtrar</button>
    </div>
    <div class="col-sm-2">
      <a href="<?php echo h($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary btn-sm w-100">
        Limpiar
      </a>
    </div>
  </form>

  <?php if (!empty($errMsg)): ?>
    <div class="alert alert-danger small py-1"><?php echo h($errMsg); ?></div>
  <?php endif; ?>

  <div class="cat-grid-wrapper">
    <table class="table table-sm table-striped table-bordered align-middle cat-grid-table">
      <thead>
      <tr>
        <th>#</th>
        <?php foreach ($CAT_COLUMNS as $name => $cfg): ?>
          <th><?php echo h($cfg['label']); ?></th>
        <?php endforeach; ?>
      </tr>
      </thead>
      <tbody>
      <?php if ($rows): ?>
        <?php $i = 1 + $offset; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <?php foreach ($CAT_COLUMNS as $name => $cfg): ?>
              <td><?php echo h((string)($r[$name] ?? '')); ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="<?php echo 1 + count($CAT_COLUMNS); ?>"
              class="text-center text-muted">
            Sin registros
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="small mt-1">
      <ul class="pagination pagination-sm mb-0">
        <?php
        $base = $_GET;
        foreach (range(1, $pages) as $p) {
            $base['page'] = $p;
            $url = $_SERVER['PHP_SELF'].'?'.http_build_query($base);
            $active = $p === $page ? 'active' : '';
            echo '<li class="page-item '.$active.'"><a class="page-link" href="'.h($url).'">'.$p.'</a></li>';
        }
        ?>
      </ul>
    </nav>
  <?php endif; ?>

  <div class="small text-muted mt-1">
    Filas en esta página: <?php echo count($rows); ?>
  </div>
</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>