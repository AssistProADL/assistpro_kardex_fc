<?php
// AssistPro ETL Browser - versión con soporte de grupos funcionales

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/../app/db.php';
$pdoLocal = db();

function qAll($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function qOne($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch(PDO::FETCH_ASSOC);
}

// === Tablas de procesos funcionales ===
try {
    $pdoLocal->exec("
        CREATE TABLE IF NOT EXISTS etl_processes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(191) NOT NULL,
          description TEXT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");
    // NUEVO: agregar columna group_name si no existe
    $pdoLocal->exec("ALTER TABLE etl_processes ADD COLUMN IF NOT EXISTS group_name VARCHAR(191) NULL");

    $pdoLocal->exec("
        CREATE TABLE IF NOT EXISTS etl_process_objects (
          id INT AUTO_INCREMENT PRIMARY KEY,
          process_id INT NOT NULL,
          alias VARCHAR(100) NOT NULL,
          remote_db VARCHAR(191) NOT NULL,
          object_name VARCHAR(191) NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_proc_obj (process_id, alias, remote_db, object_name),
          CONSTRAINT fk_proc_obj_process
            FOREIGN KEY (process_id) REFERENCES etl_processes(id)
            ON DELETE CASCADE
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");
} catch (Throwable $e) {
    die("Error creando estructuras locales: " . $e->getMessage());
}

// === Conexión a fuentes ===
$connectionsDir = __DIR__ . '/connections';
$remoteConns = [];
if (is_dir($connectionsDir)) {
    foreach (glob($connectionsDir . '/mysql_remote_*.php') as $file) {
        $base  = basename($file, '.php');
        $alias = substr($base, strlen('mysql_remote_'));
        $remoteConns[] = [
            'alias' => $alias,
            'file'  => $file,
        ];
    }
}

$remoteAlias = $_GET['alias'] ?? ($remoteConns[0]['alias'] ?? null);
$pdoRemote   = null;
$remoteError = null;
$remoteDbs   = [];
$remoteDb    = $_GET['db'] ?? '';

if ($remoteAlias) {
    $connFile = $connectionsDir . '/mysql_remote_' . $remoteAlias . '.php';
    if (is_file($connFile)) {
        try {
            require $connFile;
            if (!($pdoRemote instanceof PDO)) {
                $remoteError = "Conexión no válida en archivo {$connFile}";
            } else {
                $remoteDbs = qAll($pdoRemote, "SHOW DATABASES");
            }
        } catch (Throwable $e) {
            $remoteError = $e->getMessage();
        }
    } else {
        $remoteError = "Falta archivo de conexión: mysql_remote_{$remoteAlias}.php";
    }
}

if ($remoteDbs && $remoteDb === '') {
    $remoteDb = $remoteDbs[0]['Database'];
}

// === Variables de control UI ===
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 25;
$search = trim($_GET['q'] ?? '');
$mode = $_GET['mode'] ?? 'tables';

$msg = $err = null;

// === Crear proceso funcional ===
$processAction     = $_POST['process_action']  ?? '';
$processName       = trim($_POST['process_name'] ?? '');
$processDesc       = trim($_POST['process_desc'] ?? '');
$processGroup      = trim($_POST['process_group'] ?? ''); // NUEVO
$processObjects    = isset($_POST['process_objects'])
    ? array_filter(array_map('trim', explode(',', $_POST['process_objects'])))
    : [];
$processExportTxt  = isset($_POST['process_export_txt']) ? (int)$_POST['process_export_txt'] : 0;

if ($processAction === 'create_process' && $processName !== '' && $processObjects) {
    try {
        $st = $pdoLocal->prepare("
            INSERT INTO etl_processes (name, description, group_name, created_at)
            VALUES (:name, :description, :group_name, NOW())
        ");
        $st->execute([
            ':name' => $processName,
            ':description' => $processDesc ?: null,
            ':group_name' => $processGroup ?: null,
        ]);
        $pid = $pdoLocal->lastInsertId();

        $insObj = $pdoLocal->prepare("
            INSERT IGNORE INTO etl_process_objects (process_id, alias, remote_db, object_name, created_at)
            VALUES (:pid, :alias, :db, :obj, NOW())
        ");
        foreach ($processObjects as $key) {
            [$al, $dbn, $obj] = explode('|', $key);
            $insObj->execute([
                ':pid' => $pid,
                ':alias' => $al,
                ':db' => $dbn,
                ':obj' => $obj,
            ]);
        }

        $msg = "Proceso funcional creado correctamente.";

        // Exportar TXT si se marcó
        if ($processExportTxt) {
            $txt  = "AssistPro ETL - Documento de proceso\n";
            $txt .= "=======================================\n\n";
            $txt .= "Nombre: {$processName}\n";
            $txt .= "Grupo: " . ($processGroup ?: "(sin grupo)") . "\n";
            $txt .= "Descripción: " . ($processDesc ?: "(sin descripción)") . "\n";
            $txt .= "Creado: " . date('Y-m-d H:i:s') . "\n\n";
            $txt .= "Objetos asociados:\n";
            foreach ($processObjects as $k) {
                [$al, $dbn, $obj] = explode('|', $k);
                $txt .= "- {$obj} (Alias={$al}, BD={$dbn})\n";
            }
            $dir = __DIR__ . '/etl';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $filename = 'proceso_' . preg_replace('/\W+/', '_', strtolower($processName)) . '_' . date('Ymd_His') . '.txt';
            file_put_contents($dir . '/' . $filename, $txt);
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $txt;
            exit;
        }

    } catch (Throwable $e) {
        $err = "Error creando proceso: " . $e->getMessage();
    }
}

// === Carga de objetos remotos ===
$objects = [];
$totalObjects = 0;

if ($pdoRemote && !$remoteError && $remoteDb !== '') {
    $pdoRemote->exec("USE `{$remoteDb}`");
    if ($mode === 'tables') {
        $sql = "SELECT TABLE_NAME AS object_name, TABLE_TYPE AS type
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = :db AND TABLE_TYPE = 'BASE TABLE'";
    } elseif ($mode === 'views') {
        $sql = "SELECT TABLE_NAME AS object_name, 'VIEW' AS type
                FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = :db";
    } else {
        $sql = "SELECT ROUTINE_NAME AS object_name, ROUTINE_TYPE AS type
                FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = :db";
    }

    $params = [':db' => $remoteDb];
    $all = qAll($pdoRemote, $sql, $params);
    if ($search !== '') {
        $all = array_values(array_filter($all, fn($r) => stripos($r['object_name'], $search) !== false));
    }
    $totalObjects = count($all);
    $offset = ($page - 1) * $pageSize;
    $objects = array_slice($all, $offset, $pageSize);
}

$totalPages = max(1, ceil($totalObjects / $pageSize));

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro ETL Browser</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body{background:#f4f6fa;font-size:13px}
.table-grid-wrapper{max-height:460px;overflow:auto}
</style>
</head>
<body>
<div class="container my-3">
  <h4 class="mb-3"><i class="bi bi-diagram-3"></i> AssistPro ETL Browser</h4>
  <?php if($msg):?><div class="alert alert-success"><?=$msg?></div><?php endif;?>
  <?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
  <?php if($remoteError):?><div class="alert alert-warning"><?=$remoteError?></div><?php endif;?>

  <form class="row g-2 mb-3" method="get">
    <div class="col-auto">
      <select name="alias" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach($remoteConns as $c): ?>
          <option value="<?=$c['alias']?>" <?=$c['alias']===$remoteAlias?'selected':''?>><?=$c['alias']?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="db" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach($remoteDbs as $dbRow): ?>
          <option value="<?=$dbRow['Database']?>" <?=$dbRow['Database']===$remoteDb?'selected':''?>><?=$dbRow['Database']?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <input type="text" name="q" value="<?=htmlspecialchars($search)?>" class="form-control form-control-sm" placeholder="Buscar...">
    </div>
    <div class="col-auto">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-2">
      <form method="post">
        <input type="hidden" name="process_action" value="">
        <div class="table-grid-wrapper">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th><input type="checkbox" onclick="toggleAll(this)"></th>
                <th>Objeto</th><th>Tipo</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($objects as $obj): ?>
              <tr>
                <td><input type="checkbox" class="obj-check" value="<?=$remoteAlias?>|<?=$remoteDb?>|<?=$obj['object_name']?>"></td>
                <td><?=$obj['object_name']?></td>
                <td><?=$obj['type']?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openProcessModal()">
            <i class="bi bi-diagram-3"></i> Crear proceso funcional
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal nuevo proceso -->
<div class="modal fade" id="processModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="post" class="modal-content">
      <input type="hidden" name="process_action" value="create_process">
      <input type="hidden" name="process_objects" id="process_objects">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo proceso funcional</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Nombre</label>
          <input type="text" name="process_name" class="form-control form-control-sm" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Grupo</label>
          <input type="text" name="process_group" class="form-control form-control-sm" placeholder="Ej. Dashboard Procesos / Entradas, Manufactura, Embarque">
        </div>
        <div class="mb-2">
          <label class="form-label">Descripción</label>
          <textarea name="process_desc" class="form-control form-control-sm"></textarea>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="export_txt" name="process_export_txt" value="1">
          <label class="form-check-label small" for="export_txt">Generar documento TXT en <code>/public/etl/</code></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAll(master){
  document.querySelectorAll('.obj-check').forEach(ch=>ch.checked=master.checked);
}
function openProcessModal(){
  const selected = Array.from(document.querySelectorAll('.obj-check:checked')).map(ch=>ch.value);
  if(!selected.length){alert("Selecciona al menos un objeto.");return;}
  document.getElementById('process_objects').value = selected.join(',');
  new bootstrap.Modal(document.getElementById('processModal')).show();
}
</script>
</body>
</html>
