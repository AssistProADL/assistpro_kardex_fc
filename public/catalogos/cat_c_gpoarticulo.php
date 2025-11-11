<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$CAT_TABLE   = 'c_gpoarticulo';
$CAT_PK      = 'id';
$CAT_TITLE   = 'Gpoarticulo';
$CAT_COLUMNS = array (
  'id' => 
  array (
    'label' => 'Id',
    'type' => 'num',
    'input' => 'number',
    'in_list' => true,
    'in_form' => true,
    'nullable' => false,
    'pk' => false,
  ),
  'cve_gpoart' => 
  array (
    'label' => 'Cve Gpoart',
    'type' => 'num',
    'input' => 'number',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
  ),
  'des_gpoart' => 
  array (
    'label' => 'Des Gpoart',
    'type' => 'text',
    'input' => 'text',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
  ),
  'por_depcont' => 
  array (
    'label' => 'Por Depcont',
    'type' => 'text',
    'input' => 'text',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
  ),
  'por_depfical' => 
  array (
    'label' => 'Por Depfical',
    'type' => 'text',
    'input' => 'text',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
  ),
  'Activo' => 
  array (
    'label' => 'Activo',
    'type' => 'bool',
    'input' => 'checkbox',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
  ),
  'id_almacen' => 
  array (
    'label' => 'Id Almacen',
    'type' => 'num',
    'input' => 'number',
    'in_list' => true,
    'in_form' => true,
    'nullable' => true,
    'pk' => false,
  ),
);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function cat_db(): PDO { return db_pdo(); }

/* ==== Listado ==== */
function cat_list(string $table, string $pk, int $limit, int $offset, int &$total): array {
    $pdo = cat_db();
    $where  = '';
    $params = [];

    if (!empty($_GET['q'])) {
        $q = '%' . trim((string)$_GET['q']) . '%';
        $cols = [];
        foreach ($GLOBALS['CAT_COLUMNS'] as $name => $cfg) {
            if (in_array($cfg['type'], ['text','num','date'], true)) {
                $cols[] = $name;
            }
        }
        if (!$cols) {
            $cols = [$pk];
        }
        $parts = [];
        foreach ($cols as $c) {
            $parts[] = "$c LIKE ?";
            $params[] = $q;
        }
        if ($parts) {
            $where = ' WHERE ' . implode(' OR ', $parts);
        }
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM $table$where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();

    $limit  = max(1, $limit);
    $offset = max(0, $offset);

    $st = $pdo->prepare("SELECT * FROM $table$where ORDER BY $pk DESC LIMIT $offset,$limit");
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/* ==== CRUD ==== */
function cat_find($id){
    global $CAT_TABLE, $CAT_PK;
    $st = cat_db()->prepare("SELECT * FROM $CAT_TABLE WHERE $CAT_PK = ?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}
function cat_insert(array $data){
    global $CAT_TABLE;
    if (!$data) return null;
    $pdo = cat_db();
    $cols = array_keys($data);
    $place = implode(',', array_fill(0, count($cols), '?'));
    $sql = "INSERT INTO $CAT_TABLE (".implode(',',$cols).") VALUES ($place)";
    $st = $pdo->prepare($sql);
    $st->execute(array_values($data));
    return $pdo->lastInsertId();
}
function cat_update($id, array $data){
    global $CAT_TABLE, $CAT_PK;
    if (!$data) return;
    $pdo = cat_db();
    $sets = [];
    $vals = [];
    foreach ($data as $k => $v) {
        $sets[] = "$k = ?";
        $vals[] = $v;
    }
    $vals[] = $id;
    $sql = "UPDATE $CAT_TABLE SET ".implode(',',$sets)." WHERE $CAT_PK = ?";
    $st = $pdo->prepare($sql);
    $st->execute($vals);
}
function cat_delete($id){
    global $CAT_TABLE, $CAT_PK, $CAT_COLUMNS;
    $pdo = cat_db();
    if (array_key_exists('activo', $CAT_COLUMNS)) {
        $sql = "UPDATE $CAT_TABLE SET activo = 0 WHERE $CAT_PK = ?";
    } elseif (array_key_exists('estatus', $CAT_COLUMNS)) {
        $sql = "UPDATE $CAT_TABLE SET estatus = 0 WHERE $CAT_PK = ?";
    } else {
        $sql = "DELETE FROM $CAT_TABLE WHERE $CAT_PK = ?";
    }
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
}
function cat_restore($id){
    global $CAT_TABLE, $CAT_PK, $CAT_COLUMNS;
    $pdo = cat_db();
    if (array_key_exists('activo', $CAT_COLUMNS)) {
        $sql = "UPDATE $CAT_TABLE SET activo = 1 WHERE $CAT_PK = ?";
    } elseif (array_key_exists('estatus', $CAT_COLUMNS)) {
        $sql = "UPDATE $CAT_TABLE SET estatus = 1 WHERE $CAT_PK = ?";
    } else {
        return;
    }
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
}

/* ==== POST ==== */
$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['__action'] ?? '';
    if ($action === 'save') {
        $id = $_POST[$CAT_PK] ?? null;
        $data = [];
        foreach ($CAT_COLUMNS as $name => $cfg) {
            if (!$cfg['in_form'] || $cfg['pk']) continue;
            if ($cfg['input'] === 'checkbox') {
                $data[$name] = isset($_POST[$name]) ? 1 : 0;
            } else {
                $v = $_POST[$name] ?? null;
                if ($v === '' && $cfg['nullable']) {
                    $v = null;
                }
                $data[$name] = $v;
            }
        }
        try {
            if ($id) cat_update($id, $data);
            else     cat_insert($data);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch(Throwable $e) {
            $errors[] = $e->getMessage();
            $editing  = $_POST;
        }
    } elseif ($action === 'del' && !empty($_POST['id'])) {
        cat_delete($_POST['id']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'restore' && !empty($_POST['id'])) {
        cat_restore($_POST['id']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_GET['edit'])) {
    $editing = cat_find($_GET['edit']);
}

/* ==== Paginación ==== */
$per_page = 25;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$total  = 0;
$rows   = cat_list($CAT_TABLE, $CAT_PK, $per_page, $offset, $total);
$pages  = max(1, (int)ceil($total / $per_page));

$list_cols = [];
$form_cols = [];
foreach ($CAT_COLUMNS as $name => $cfg) {
    if ($cfg['in_list']) $list_cols[] = $name;
    if ($cfg['in_form'] && !$cfg['pk']) $form_cols[] = $name;
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">
  <h5 class="mb-2"><?php echo h($CAT_TITLE); ?></h5>

  <div class="d-flex justify-content-between small mb-2">
    <div>
      <b>Total:</b> <?php echo (int)$total; ?>
      — Página <?php echo $page; ?> de <?php echo $pages; ?>
    </div>
  </div>

  <form class="row g-2 mb-2" method="get">
    <div class="col-sm-4">
      <input name="q" value="<?php echo h($_GET['q'] ?? ''); ?>"
             class="form-control form-control-sm" placeholder="Buscar...">
    </div>
    <div class="col-sm-2">
      <button class="btn btn-primary btn-sm w-100">Filtrar</button>
    </div>
    <div class="col-sm-2">
      <a href="<?php echo h($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary btn-sm w-100">Limpiar</a>
    </div>
  </form>

  <?php if ($errors): ?>
    <div class="alert alert-danger small py-1">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?php echo h($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-7">
      <div class="table-responsive" style="max-height:65vh;overflow:auto;">
        <table class="table table-sm table-striped table-bordered align-middle">
          <thead>
          <tr>
            <th>Acciones</th>
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
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="?edit=<?php echo urlencode($r[$CAT_PK]); ?>" class="btn btn-outline-primary">Editar</a>
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('¿Borrar / enviar a inactivos?');">
                      <input type="hidden" name="__action" value="del">
                      <input type="hidden" name="id" value="<?php echo h($r[$CAT_PK]); ?>">
                      <button class="btn btn-outline-danger">Borrar</button>
                    </form>
                    <?php
                    $canRestore = false;
                    if (array_key_exists('activo', $CAT_COLUMNS)) {
                        $canRestore = isset($r['activo']) && (int)$r['activo'] === 0;
                    } elseif (array_key_exists('estatus', $CAT_COLUMNS)) {
                        $canRestore = isset($r['estatus']) && (int)$r['estatus'] === 0;
                    }
                    ?>
                    <?php if ($canRestore): ?>
                      <form method="post" class="d-inline"
                            onsubmit="return confirm('¿Recuperar registro?');">
                        <input type="hidden" name="__action" value="restore">
                        <input type="hidden" name="id" value="<?php echo h($r[$CAT_PK]); ?>">
                        <button class="btn btn-outline-warning">Recuperar</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?php echo $i++; ?></td>
                <?php foreach ($list_cols as $c): ?>
                  <td><?php echo h((string)($r[$c] ?? '')); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="<?php echo 2 + count($list_cols); ?>" class="text-center text-muted">
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

    <div class="col-md-5">
      <div class="card">
        <div class="card-header">
          <?php echo $editing ? 'Editar' : 'Nuevo'; ?> registro
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="__action" value="save">
            <?php if ($editing && isset($editing[$CAT_PK])): ?>
              <input type="hidden" name="<?php echo h($CAT_PK); ?>" value="<?php echo h($editing[$CAT_PK]); ?>">
            <?php endif; ?>

            <?php foreach ($form_cols as $c): ?>
              <?php $cfg = $CAT_COLUMNS[$c]; $val = $editing[$c] ?? ''; $req = !$cfg['nullable']; ?>
              <div class="mb-2">
                <label class="form-label">
                  <?php echo h($cfg['label']); ?>
                  <?php if ($req): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <?php if ($cfg['input'] === 'textarea'): ?>
                  <textarea name="<?php echo h($c); ?>" class="form-control form-control-sm"
                            rows="2"><?php echo h($val); ?></textarea>
                <?php elseif ($cfg['input'] === 'checkbox'): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="<?php echo h($c); ?>" value="1"
                           <?php echo $val ? 'checked' : ''; ?>>
                    <label class="form-check-label">Activo</label>
                  </div>
                <?php else: ?>
                  <input type="<?php echo h($cfg['input']); ?>"
                         name="<?php echo h($c); ?>"
                         value="<?php echo h($val); ?>"
                         class="form-control form-control-sm"
                         <?php echo $req ? 'required' : ''; ?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <div class="mt-2">
              <button class="btn btn-primary btn-sm">Guardar</button>
              <a href="<?php echo h($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>