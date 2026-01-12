<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

echo "<h3>Checking table 'formaspag'</h3>";

try {
    // 1. Count
    $cnt = $pdo->query("SELECT COUNT(*) FROM formaspag")->fetchColumn();
    echo "Total Rows: " . $cnt . "<br>";

    // 2. Show Columns
    $stm = $pdo->query("SHOW COLUMNS FROM formaspag");
    $cols = $stm->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Columns:</h4><pre>";
    print_r($cols);
    echo "</pre>";

    // 3. Show first 3 rows
    if ($cnt > 0) {
        $stm = $pdo->query("SELECT * FROM formaspag LIMIT 3");
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>First 3 rows:</h4><pre>";
        print_r($rows);
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
