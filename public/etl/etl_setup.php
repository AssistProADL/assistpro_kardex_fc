<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/../app/db.php'; // conexión local
$pdoLocal = db();

$message = '';
$bases   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_conn') {
        $alias         = trim($_POST['conn_alias']);
        $friendly_name = trim($_POST['friendly_name'] ?? $alias);
        $db_name       = trim($_POST['db_name']);
        $db_user       = trim($_POST['db_user']);
        $db_pass       = trim($_POST['db_pass']);
        $host          = trim($_POST['db_host'] ?? '127.0.0.1');
        $puerto        = intval($_POST['local_port'] ?? 3307);

        // archivo PHP de conexión
        $connFile = __DIR__ . "/connections/mysql_remote_{$alias}.php";
        $rutaBase = "public/connections/mysql_remote_{$alias}.php";

        $php = "<?php
function db_{$alias}() {
    \$pdo = new PDO('mysql:host=127.0.0.1;port={$puerto};dbname={$db_name};charset=utf8mb4',
        '{$db_user}','{$db_pass}',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    return \$pdo;
}
?>";

        file_put_contents($connFile, $php);

        try {
            $stmt = $pdoLocal->prepare("
                INSERT INTO etl_connections (alias, friendly_name, file_path, db_name)
                VALUES (:a, :f, :p, :d)
                ON DUPLICATE KEY UPDATE
                    friendly_name = VALUES(friendly_name),
                    file_path     = VALUES(file_path),
                    db_name       = VALUES(db_name),
                    created_at    = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                ':a' => $alias,
                ':f' => $friendly_name,
                ':p' => $rutaBase,
                ':d' => $db_name
            ]);
            $message = "<div class='alert alert-success'>Conexión guardada en PHP + SQL.</div>";
        } catch (Throwable $e) {
            $message = "<div class='alert alert-warning'>Conexión PHP guardada pero no pude registrar en etl_connections: {$e->getMessage()}</div>";
        }
    }

    if ($action === 'list_db') {
        try {
            $pdoTest = new PDO(
                "mysql:host=127.0.0.1;port=" . intval($_POST['local_port'] ?? 3307) . ";charset=utf8mb4",
                $_POST['db_user'],
                $_POST['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $q = $pdoTest->query("SHOW DATABASES");
            $bases = $q->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            $message = "<div class='alert alert-danger'>Error de conexión: {$e->getMessage()}</div>";
        }
    }

    if ($action === 'test_conn') {
        try {
            $pdoTest = new PDO(
                "mysql:host=127.0.0.1;port=" . intval($_POST['local_port'] ?? 3307) . ";dbname=" . $_POST['db_name'] . ";charset=utf8mb4",
                $_POST['db_user'],
                $_POST['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $now = $pdoTest->query("SELECT NOW()")->fetchColumn();
            $message = "<div class='alert alert-success'>Conexión exitosa. Hora remota: {$now}</div>";
        } catch (Throwable $e) {
            $message = "<div class='alert alert-danger'>Error de conexión: {$e->getMessage()}</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>ETL Setup</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <h1 class="mb-4">ETL Setup</h1>

  <?= $message ?>

  <form method="post">

    <h5>1) Parámetros de SSH (túnel TCP/IP sobre SSH)</h5>
    <div class="mb-3">
      <label class="form-label">Ruta llave privada</label>
      <input type="text" class="form-control" value="C:/xampp_new/htdocs/assistpro_etl/keys/oracle-private-ssh.pem" readonly>
    </div>
    <div class="mb-3">
      <label class="form-label">Usuario SSH</label>
      <input type="text" class="form-control" value="opc" readonly>
    </div>
    <div class="mb-3">
      <label class="form-label">Bastión (IP/DNS)</label>
      <input type="text" class="form-control" value="129.159.107.144" readonly>
    </div>

    <h5>2) Parámetros MySQL (vía túnel)</h5>
    <div class="row mb-3">
      <div class="col">
        <label class="form-label">Host MySQL visto desde bastión</label>
        <input type="text" class="form-control" value="10.0.0.38" readonly>
      </div>
      <div class="col">
        <label class="form-label">Puerto MySQL remoto</label>
        <input type="text" class="form-control" value="3306" readonly>
      </div>
      <div class="col">
        <label class="form-label">Puerto local túnel</label>
        <input type="number" name="local_port" class="form-control" value="3307">
      </div>
    </div>
    <div class="row mb-3">
      <div class="col">
        <label class="form-label">Usuario MySQL</label>
        <input type="text" name="db_user" class="form-control" required>
      </div>
      <div class="col">
        <label class="form-label">Contraseña</label>
        <input type="password" name="db_pass" class="form-control">
      </div>
      <div class="col">
        <label class="form-label">Base de datos remota</label>
        <input type="text" name="db_name" class="form-control" required>
      </div>
    </div>

    <h5>3) Comando para abrir el túnel (ejecutar en terminal)</h5>
    <pre>ssh -i /xampp_new/htdocs/assistpro_etl/public/connections/oracle-private-ssh.pem -o ServerAliveInterval=60 -o ServerAliveCountMax=3 -L 3307:10.0.0.38:3306 opc@129.159.107.144</pre>

    <h5>4) Guardar conexión</h5>
    <div class="row mb-3">
      <div class="col">
        <label class="form-label">Alias conexión</label>
        <input type="text" name="conn_alias" class="form-control" placeholder="ej: wms162" required>
      </div>
      <div class="col">
        <label class="form-label">Nombre amigable</label>
        <input type="text" name="friendly_name" class="form-control" placeholder="ej: BP Foam Creations">
      </div>
    </div>

    <button type="submit" name="action" value="list_db" class="btn btn-secondary">Listar bases</button>
    <button type="submit" name="action" value="test_conn" class="btn btn-info">Probar conexión</button>
    <button type="submit" name="action" value="save_conn" class="btn btn-primary">Guardar conexión (PHP + SQL)</button>
  </form>

  <?php if ($bases): ?>
  <h5 class="mt-4">Bases de datos detectadas</h5>
  <ul>
    <?php foreach ($bases as $b): ?>
      <li><?= htmlspecialchars($b) ?></li>
    <?php endforeach ?>
  </ul>
  <?php endif; ?>
</body>
</html>
