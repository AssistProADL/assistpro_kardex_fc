<?php
/**
 * assistpro_etl_fc / app/db.php (versión mínima-estable)
 * - Sin SET time_zone
 * - Timeout corto (3s)
 * - Sin reintentos ni comandos extra
 * - Helpers: db, dbq, db_all, db_one, db_val, db_tx
 */

/* ====== EDITA ESTOS DATOS SI ES NECESARIO ====== */
$AP_CFG = [
  'db' => [
    'host'   => '127.0.0.1',       // o IP/host del servidor
    'port'   => 3306,              // puerto MySQL/MariaDB
    'name'   => 'assistpro_etl_fc',
    'user'   => 'root',
    'pass'   => '',
    'charset'=> 'utf8mb4',
    'timeout'=> 3,                 // segundos
  ],
];
/* =============================================== */

if (!function_exists('cfg')) {
  function cfg(string $key, $default = null) {
    static $C = null; global $AP_CFG;
    if ($C === null) $C = $AP_CFG;
    $envKey = strtoupper(str_replace('.', '_', $key));
    $envVal = getenv($envKey);
    if ($envVal !== false) {
      if (is_numeric($envVal) && (string)(int)$envVal === $envVal) $envVal = (int)$envVal;
      return $envVal;
    }
    $parts = explode('.', $key); $ref = $C;
    foreach ($parts as $p) { if (!is_array($ref) || !array_key_exists($p,$ref)) return $default; $ref = $ref[$p]; }
    return $ref;
  }
}

if (!function_exists('db')) {
  function db(): PDO {
    static $pdo = null; if ($pdo instanceof PDO) return $pdo;

    $host = cfg('db.host');   $port = (int)cfg('db.port',3306);
    $name = cfg('db.name');   $user = cfg('db.user');
    $pass = cfg('db.pass');   $charset = cfg('db.charset','utf8mb4');
    $timeout = (int)cfg('db.timeout',3);

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      PDO::ATTR_PERSISTENT         => false,
      PDO::ATTR_TIMEOUT            => $timeout,          // falla rápido si no conecta
      // NOTA: No usamos MYSQL_ATTR_INIT_COMMAND para evitar bloqueos
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
  }
}

if (!function_exists('dbq')) {
  function dbq(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) {
      $type = PDO::PARAM_STR;
      if (is_int($v))   $type = PDO::PARAM_INT;
      if (is_bool($v))  $type = PDO::PARAM_BOOL;
      if (is_null($v))  $type = PDO::PARAM_NULL;
      if (is_string($k) && $k !== '' && $k[0] !== ':') {
        $stmt->bindValue(':'.$k, $v, $type);
      } else {
        $stmt->bindValue(is_int($k) ? $k+1 : $k, $v, $type);
      }
    }
    $stmt->execute();
    return $stmt;
  }
}

if (!function_exists('db_all')) {
  function db_all(string $sql, array $params = []): array {
    return dbq($sql, $params)->fetchAll();
  }
}

if (!function_exists('db_one')) {
  function db_one(string $sql, array $params = []): ?array {
    $row = dbq($sql, $params)->fetch();
    return $row === false ? null : $row;
  }
}

if (!function_exists('db_val')) {
  function db_val(string $sql, array $params = []) {
    $val = dbq($sql, $params)->fetchColumn();
    return $val === false ? null : $val;
  }
}

if (!function_exists('db_tx')) {
  function db_tx(callable $fn) {
    $pdo = db(); $inTx = $pdo->inTransaction();
    if (!$inTx) $pdo->beginTransaction();
    try {
      $ret = $fn();
      if (!$inTx) $pdo->commit();
      return $ret;
    } catch (Throwable $e) {
      if (!$inTx && $pdo->inTransaction()) $pdo->rollBack();
      throw $e;
    }
  }
}
