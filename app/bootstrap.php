<?php
// app/bootstrap.php
//
// Punto de entrada común para AssistPro Kardex FC:
// - Carga Composer
// - Lee .env
// - Inicializa PDO ($pdo)
// - Inicializa logger Monolog
// - Expone helpers db_* y app_logger()

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// ---------- 1) Composer autoload ----------
require_once __DIR__ . '/../vendor/autoload.php';

// ---------- 2) Cargar .env ----------
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// ---------- 3) Helper env() ----------
if (!function_exists('env')) {
    /**
     * Obtiene variable de entorno con valor por defecto.
     */
    function env(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        return $default;
    }
}

// ---------- 4) Configuración de errores ----------
$appEnv   = env('APP_ENV', 'local');
$appDebug = filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN);

if ($appEnv === 'local' && $appDebug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

// ---------- 5) Conexión PDO global ----------
/** @var PDO $pdo */
global $pdo;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $dbHost = env('DB_HOST', '127.0.0.1');
    $dbPort = env('DB_PORT', '3306');
    $dbName = env('DB_NAME', 'assistpro_etl_fc');
    $dbUser = env('DB_USER', 'root');
    $dbPass = env('DB_PASS', '');

    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Si no hay logger aún, mostramos error simple
        http_response_code(500);
        echo "Error de conexión a base de datos. Verifique configuración.";
        if ($appDebug) {
            echo "<br><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
        exit;
    }
}

/**
 * Devuelve la instancia global de PDO.
 */
if (!function_exists('db')) {
    function db(): PDO
    {
        global $pdo;
        return $pdo;
    }
}

// ---------- 6) Helpers de BD (compatibles con tu estilo) ----------

if (!function_exists('db_all')) {
    function db_all(string $sql, array $params = []): array
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('db_one')) {
    function db_one(string $sql, array $params = []): ?array
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}

if (!function_exists('db_val')) {
    /**
     * Devuelve el primer valor de la primera fila
     */
    function db_val(string $sql, array $params = [])
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row === false ? null : $row[0];
    }
}

if (!function_exists('db_exec')) {
    /**
     * Ejecuta INSERT/UPDATE/DELETE y devuelve filas afectadas.
     */
    function db_exec(string $sql, array $params = []): int
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}

// ---------- 7) Logger Monolog compartido ----------

if (!function_exists('app_logger')) {
    /**
     * Logger global para el proyecto.
     */
    function app_logger(): Logger
    {
        static $logger = null;
        if ($logger instanceof Logger) {
            return $logger;
        }

        $channel = env('APP_LOG_CHANNEL', 'assistpro');
        $logger  = new Logger($channel);

        $logDir  = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/app.log';

        // Formato: [fecha] nivel: mensaje {contexto}
        $output  = "[%datetime%] %level_name%: %message% %context%\n";
        $formatter = new LineFormatter($output, 'Y-m-d H:i:s', true, true);

        $handler = new StreamHandler($logFile, Logger::DEBUG);
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return $logger;
    }
}

// ---------- 8) Log básico de arranque (opcional) ----------
if ($appDebug) {
    app_logger()->debug('bootstrap cargado', [
        'env'   => $appEnv,
        'db'    => env('DB_NAME', 'assistpro_etl_fc'),
        'host'  => env('DB_HOST', '127.0.0.1'),
    ]);
}
