<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

echo "Database: " . db_val("SELECT DATABASE()") . "\n";
echo "Host info: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";

echo "Searching tables like '%motiv%':\n";
$stmt = $pdo->query("SHOW TABLES LIKE '%motiv%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    echo "No tables found matching 'motiv'.\n";
} else {
    foreach ($tables as $t) {
        echo "- $t\n";
    }
}

// Check exactly 'MotivosNoVenta'
$exists = $pdo->query("SHOW TABLES LIKE 'MotivosNoVenta'")->rowCount();
echo "Exact match 'MotivosNoVenta': " . ($exists ? "YES" : "NO") . "\n";

// Check lowercase
$existsLower = $pdo->query("SHOW TABLES LIKE 'motivosnoventa'")->rowCount();
echo "Exact match 'motivosnoventa': " . ($existsLower ? "YES" : "NO") . "\n";

// Check snake_case
$existsSnake = $pdo->query("SHOW TABLES LIKE 'motivos_no_venta'")->rowCount();
echo "Exact match 'motivos_no_venta': " . ($existsSnake ? "YES" : "NO") . "\n";
