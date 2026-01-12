<?php
declare(strict_types=1);

/**
 * app/db.php
 * Conexión centralizada PDO + helpers + compatibilidad legacy.
 *
 * Uso moderno:
 * $rows = db_all("SELECT * FROM tabla WHERE id=?", [$id]);
 * $row  = db_row("SELECT * FROM tabla WHERE id=?", [$id]);
 * $val  = db_val("SELECT COUNT(*) FROM tabla");
 * dbq("UPDATE tabla SET x=? WHERE id=?", [$x, $id]);
 * $pdo  = db_pdo(); // o usar $GLOBALS['pdo']
 *
 * Legacy cubierto:
 * db(), db_one(), db_first(), db_scalar(), db_conn()
 */

/* =========================
 * Configuración por defecto
 * ========================= */
const DB_DEFAULT = [
    'host' => '89.117.146.27',
    'name' => 'assistpro_etl_fc_dev',
    'user' => 'root2',
    'pass' => 'AdvLogMysql21#',
    'port' => 3306,
    'charset' => 'utf8mb4',
    'timezone' => '-06:00', // CDMX
];

/**
 * Lee configuración desde:
 * - app/db.local.ini (no versionado)
 * - variables de entorno
 */
function db_config(): array
{
    $cfg = DB_DEFAULT;

    $ini = __DIR__ . '/db.local.ini';
    if (is_file($ini)) {
        $loc = parse_ini_file($ini);
        if (is_array($loc)) {
            $cfg = array_merge($cfg, array_change_key_case($loc, CASE_LOWER));
        }
    }

    $env = [
        'DB_HOST' => 'host',
        'DB_NAME' => 'name',
        'DB_USER' => 'user',
        'DB_PASS' => 'pass',
        'DB_PORT' => 'port',
        'DB_CHARSET' => 'charset',
        'DB_TIMEZONE' => 'timezone',
    ];

    foreach ($env as $E => $k) {
        $v = getenv($E);
        if ($v !== false && $v !== '') {
            $cfg[$k] = $v;
        }
    }

    return $cfg;
}

/* =========================
 * Conexión PDO (singleton)
 * ========================= */
function db_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_config();

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        (int) $cfg['port'],
        $cfg['name'],
        $cfg['charset']
    );

    $pdo = new PDO(
        $dsn,
        (string) $cfg['user'],
        (string) $cfg['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Ajustes de sesión MySQL
    $pdo->exec("SET NAMES {$cfg['charset']}");
    $tz = $cfg['timezone'] ?: '+00:00';
    $pdo->exec("SET time_zone = '{$tz}'");
    $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");

    // 🔴 CLAVE: exponer $pdo GLOBAL para APIs legacy
    $GLOBALS['pdo'] = $pdo;

    return $pdo;
}

/* =========================
 * Helpers de consulta
 * ========================= */
function db_all(string $sql, array $params = []): array
{
    $st = db_pdo()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_row(string $sql, array $params = []): ?array
{
    $st = db_pdo()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

function db_val(string $sql, array $params = [], $default = null)
{
    $st = db_pdo()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function dbq(string $sql, array $params = []): int
{
    $st = db_pdo()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/* =========================
 * Transacciones
 * ========================= */
function db_begin(): void
{
    db_pdo()->beginTransaction();
}

function db_commit(): void
{
    $pdo = db_pdo();
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
}

function db_rollback(): void
{
    $pdo = db_pdo();
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

function db_tx(callable $fn)
{
    $pdo = db_pdo();
    $pdo->beginTransaction();
    try {
        $res = $fn($pdo);
        $pdo->commit();
        return $res;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/* =========================
 * Utilidades
 * ========================= */
function db_table_exists(string $table): bool
{
    $db = db_val('SELECT DATABASE()');
    $sql = 'SELECT 1 FROM information_schema.tables WHERE table_schema=? AND table_name=?';
    return (bool) db_val($sql, [$db, $table], false);
}

/* ============================================================
 * Compatibilidad legacy (NO romper vistas existentes)
 * ============================================================ */

if (!function_exists('db')) {
    function db(): PDO
    {
        return db_pdo();
    }
}

if (!function_exists('db_one')) {
    function db_one(string $sql, array $params = []): ?array
    {
        return db_row($sql, $params);
    }
}

if (!function_exists('db_first')) {
    function db_first(string $sql, array $params = []): ?array
    {
        return db_row($sql, $params);
    }
}

if (!function_exists('db_scalar')) {
    function db_scalar(string $sql, array $params = [], $default = null)
    {
        return db_val($sql, $params, $default);
    }
}

if (!function_exists('db_conn')) {
    function db_conn(): PDO
    {
        return db_pdo();
    }
}
