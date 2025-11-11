<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$CAT_TABLE   = 'c_lotes';
$CAT_PK      = 'id';
$CAT_TITLE   = 'Lotes';
$CAT_COLUMNS = array (
  'id' => 
  array (
    'label' => 'Id',
    'in_list' => false,
    'in_form' => true,
    'nullable' => false,
    'pk' => false,
    'data_type' => 'int',
    'column_type' => 'int(10) unsigned',
    'type' => 'num',
    'input' => 'number',
  ),
  'cve_articulo' => 
  array (
    'label' => 'Clave Articulo',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
    'data_type' => 'varchar',
    'column_type' => 'varchar(50)',
    'type' => 'text',
    'input' => 'text',
  ),
  'Lote' => 
  array (
    'label' => 'Lote',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
    'data_type' => 'varchar',
    'column_type' => 'varchar(50)',
    'type' => 'text',
    'input' => 'text',
  ),
  'Caducidad' => 
  array (
    'label' => 'Caducidad',
    'in_list' => true,
    'in_form' => true,
    'nullable' => false,
    'pk' => false,
    'data_type' => 'date',
    'column_type' => 'date',
    'type' => 'date',
    'input' => 'date',
  ),
  'Activo' => 
  array (
    'label' => 'Activo',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
    'data_type' => 'int',
    'column_type' => 'int(11)',
    'type' => 'num',
    'input' => 'number',
  ),
  'Fec_Prod' => 
  array (
    'label' => 'Fecha Producción',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
    'data_type' => 'date',
    'column_type' => 'date',
    'type' => 'date',
    'input' => 'date',
  ),
  'Lote_Alterno' => 
  array (
    'label' => 'Lote Alterno',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
    'data_type' => 'varchar',
    'column_type' => 'varchar(50)',
    'type' => 'text',
    'input' => 'text',
  ),
);

$pdo = db_pdo();

/* ---- columnas para grilla y formulario ---- */
$list_cols = [];
$form_cols = [];
foreach ($CAT_COLUMNS as $name => $cfg) {
    if (!empty($cfg['in_list'])) {
        $list_cols[] = $name;
    }
    if (!empty($cfg['in_form']) && empty($cfg['pk'])) {
        $form_cols[] = $name;
    }
}

/* ========= IMPORT / EXPORT CSV ========= */
$import_msg = '';
$errors     = [];
$editing    = null;

/* EXPORT */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $search = trim((string)($_GET['q'] ?? ''));
    $where  = '';
    $params = [];

    if ($search !== '') {
        $q = '%'.$search.'%';
        $cols  = $list_cols ?: array_keys($CAT_COLUMNS);
        $parts = [];
        foreach ($cols as $c) {
            $parts[]  = "$c LIKE ?";
            $params[] = $q;
        }
        if ($parts) {
            $where = ' WHERE ' . implode(' OR ', $parts);
        }
    }

    $sql = "SELECT * FROM $CAT_TABLE$where ORDER BY $CAT_PK DESC";
    $st  = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.preg_replace('/\s+/', '_', $CAT_TITLE).'_'.date('Ymd_His').'.csv"');

    $out = fopen('php://output', 'w');

    $headers = $list_cols ?: array_keys($CAT_COLUMNS);
    fputcsv($out, $headers);

    foreach ($rows as $r) {
        $row = [];
        foreach ($headers as $c) {
            $row[] = $r[$c] ?? '';
        }
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

/* IMPORT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action'] ?? '') === 'import') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $import_msg = 'Error al subir el archivo CSV.';
    } else {
        $filename = $_FILES['csv_file']['tmp_name'];
        $fh = fopen($filename, 'r');
        if (!$fh) {
            $import_msg = 'No se pudo leer el archivo CSV.';
        } else {
            $pdo->beginTransaction();
            try {
                $header = fgetcsv($fh);
                if (!$header) {
                    throw new RuntimeException('El CSV no tiene encabezados.');
                }
                $header = array_map('trim', $header);

                $valid_cols = array_keys($CAT_COLUMNS);
                $map_index  = [];

                foreach ($header as $idx => $colName) {
                    if (in_array($colName, $valid_cols, true)) {
                        $map_index[$idx] = $colName;
                    }
                }
                if (!$map_index) {
                    throw new RuntimeException('Ninguna columna del CSV coincide con las columnas del catálogo.');
                }

                $inserted = 0;
                while (($row = fgetcsv($fh)) !== false) {
                    $data = [];
                    foreach ($map_index as $idx => $colName) {
                        $val = $row[$idx] ?? null;
                        $cfg = $CAT_COLUMNS[$colName];

                        if ($val === '' && !empty($cfg['nullable'])) {
                            $val = null;
                        }
                        if (($cfg['input'] ?? '') === 'checkbox') {
                            if (is_string($val)) {
                                $v = strtolower(trim($val));
                                $val = in_array($v, ['1','true','t','s','y','si','sí'], true) ? 1 : 0;
                            } else {
                                $val = (int)$val ? 1 : 0;
                            }
                        }
                        $data[$colName] = $val;
                    }

                    if ($data) {
                        $cols   = array_keys($data);
                        $place  = implode(',', array_fill(0, count($cols), '?'));
                        $sqlIns = "INSERT INTO $CAT_TABLE (".implode(',', $cols).") VALUES ($place)";
                        $stIns  = $pdo->prepare($sqlIns);
                        $stIns->execute(array_values($data));
                        $inserted++;
                    }
                }

                $pdo->commit();
                $import_msg = "Importación completada. Registros insertados: $inserted.";
            } catch (Throwable $e) {
                $pdo->rollBack();
                $import_msg = 'Error en importación: '.$e->getMessage();
            }
            fclose($fh);
        }
    }
}

/* ========= ALTA SIMPLE (AGREGAR) ========= */

$is_new_view = isset($_GET['nuevo']) && $_GET['nuevo'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action'] ?? '') === 'save') {
    $data = [];
    foreach ($CAT_COLUMNS as $name => $cfg) {
        if (empty($cfg['in_form']) || !empty($cfg['pk'])) continue;

        if (($cfg['input'] ?? '') === 'checkbox') {
            $data[$name] = isset($_POST[$name]) ? 1 : 0;
        } else {
            $v = $_POST[$name] ?? null;
            if ($v === '' && !empty($cfg['nullable'])) {
                $v = null;
            }
            $data[$name] = $v;
        }
    }

    try {
        if ($data) {
            $cols  = array_keys($data);
            $place = implode(',', array_fill(0, count($cols), '?'));
            $sql   = "INSERT INTO $CAT_TABLE (".implode(',', $cols).") VALUES ($place)";
            $st    = $pdo->prepare($sql);
            $st->execute(array_values($data));
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
        $editing  = $_POST;
        $is_new_view = true;
    }
}

/* ========= LISTADO (grilla) ========= */

$per_page = 25;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$search = trim((string)($_GET['q'] ?? ''));
$where  = '';
$params = [];

if ($search !== '') {
    $q = '%'.$search.'%';
    $cols  = $list_cols ?: array_keys($CAT_COLUMNS);
    $parts = [];
    foreach ($cols as $c) {
        $parts[]  = "$c LIKE ?";
        $params[] = $q;
    }
    if ($parts) {
        $where = ' WHERE ' . implode(' OR ', $parts);
    }
}

$total  = 0;
$errMsg = '';
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM $CAT_TABLE$where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
} catch (Throwable $e) {
    $errMsg = $e->getMessage();
}

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

$layout_cols   = $form_cols ?: $list_cols ?: array_keys($CAT_COLUMNS);
$layout_header = implode(',', $layout_cols);

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">

  <style>
    .cat-grid-wrapper {
      max-height: 60vh;
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

  <?php if ($errors): ?>
    <div class="alert alert-danger small py-1">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?php echo h($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($errMsg)): ?>
    <div class="alert alert-danger small py-1"><?php echo h($errMsg); ?></div>
  <?php endif; ?>

  <?php if ($import_msg): ?>
    <div class="alert alert-info small py-1"><?php echo h($import_msg); ?></div>
  <?php endif; ?>

  <?php if ($is_new_view): ?>

    <!-- ====== VISTA NUEVO REGISTRO ====== -->
    <div class="row">
      <div class="col-md-8 col-lg-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Nuevo <?php echo h($CAT_TITLE); ?></span>
            <a href="<?php echo h($_SERVER['PHP_SELF']); ?>"
               class="btn btn-sm btn-outline-secondary">Volver</a>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo h($_SERVER['PHP_SELF'].'?nuevo=1'); ?>">
              <input type="hidden" name="__action" value="save">

              <?php foreach ($form_cols as $c): ?>
                <?php
                $cfg = $CAT_COLUMNS[$c];
                $val = $editing[$c] ?? '';
                $req = empty($cfg['nullable']);
                ?>
                <div class="mb-2">
                  <label class="form-label">
                    <?php echo h($cfg['label']); ?>
                    <?php if ($req): ?><span class="text-danger">*</span><?php endif; ?>
                  </label>
                  <?php if (($cfg['input'] ?? '') === 'textarea'): ?>
                    <textarea name="<?php echo h($c); ?>" rows="3"
                              class="form-control form-control-sm"><?php echo h($val); ?></textarea>
                  <?php elseif (($cfg['input'] ?? '') === 'checkbox'): ?>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox"
                             name="<?php echo h($c); ?>" value="1"
                             <?php echo $val ? 'checked' : ''; ?>>
                      <label class="form-check-label">Activo</label>
                    </div>
                  <?php else: ?>
                    <input type="<?php echo h($cfg['input'] ?? 'text'); ?>"
                           name="<?php echo h($c); ?>"
                           value="<?php echo h($val); ?>"
                           class="form-control form-control-sm"
                           <?php echo $req ? 'required' : ''; ?>>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>

              <div class="mt-3">
                <button class="btn btn-primary btn-sm">Guardar</button>
                <a href="<?php echo h($_SERVER['PHP_SELF']); ?>"
                   class="btn btn-secondary btn-sm">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>

    <!-- ====== VISTA LISTA ====== -->

    <div class="d-flex justify-content-between small mb-2">
      <div>
        <b>Total registros:</b> <?php echo (int)$total; ?>
        — Página <?php echo $page; ?> de <?php echo $pages; ?>
      </div>
      <div>
        <a href="<?php echo h($_SERVER['PHP_SELF'].'?nuevo=1'); ?>"
           class="btn btn-success btn-sm">Nuevo</a>
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
      <div class="col-sm-2">
        <?php
        $exportParams = $_GET;
        $exportParams['export'] = 'csv';
        $exportUrl = $_SERVER['PHP_SELF'].'?'.http_build_query($exportParams);
        ?>
        <a href="<?php echo h($exportUrl); ?>"
           class="btn btn-success btn-sm w-100">Exportar CSV</a>
      </div>
    </form>

    <div class="mb-2 small">
      <label class="form-label mb-1">Layout de importación (previsualización)</label>
      <textarea class="form-control form-control-sm" rows="1" readonly><?php echo h($layout_header); ?></textarea>
      <div class="form-text">Columnas esperadas en el archivo de importación (CSV).</div>
    </div>

    <div class="mb-2">
      <form method="post" enctype="multipart/form-data" class="row g-1 align-items-center">
        <input type="hidden" name="__action" value="import">
        <div class="col-auto">
          <input type="file" name="csv_file" accept=".csv"
                 class="form-control form-control-sm">
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-secondary btn-sm">Importar CSV</button>
        </div>
      </form>
    </div>

    <div class="cat-grid-wrapper">
      <table class="table table-sm table-striped table-bordered align-middle cat-grid-table">
        <thead>
        <tr>
          <th>#</th>
          <?php foreach ($list_cols as $c): ?>
            <th><?php echo h($CAT_COLUMNS[$c]['label']); ?></th>
          <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows): ?>
          <?php $i = 1 + $offset; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <?php foreach ($list_cols as $c): ?>
                <td><?php echo h((string)($r[$c] ?? '')); ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="<?php echo 1 + count($list_cols); ?>"
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
          for ($p = 1; $p <= $pages; $p++) {
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

  <?php endif; ?>

</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>