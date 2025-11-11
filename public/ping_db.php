<?php
require_once __DIR__ . '/../app/db.php';

try {
    $pdo = db();
    $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
    $tz  = $pdo->query("SELECT @@time_zone")->fetchColumn();
    $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>✅ Conexión exitosa</h3>";
    echo "<p>Servidor: " . DB_HOST . "</p>";
    echo "<p>Versión MySQL: $ver</p>";
    echo "<p>Zona horaria: $tz</p>";
    echo "<p>Bases disponibles:</p><pre>" . implode(PHP_EOL, $dbs) . "</pre>";
} catch (Throwable $e) {
    echo "<h3>❌ Error de conexión</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
