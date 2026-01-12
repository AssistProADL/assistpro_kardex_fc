<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

echo "<h3>Checking table 'ctiket'</h3>";

try {
    // 1. Check if table exists and get correct name
    $stm = $pdo->query("SHOW TABLES LIKE '%tiket%'");
    $tables = $stm->fetchAll(PDO::FETCH_COLUMN);
    echo "<h4>Tables found similar to 'tiket':</h4><pre>";
    print_r($tables);
    echo "</pre>";

    if (empty($tables)) {
        echo "No tables found matching 'tiket'.";
    } else {
        $tb = $tables[0];

        // 2. Count
        $cnt = $pdo->query("SELECT COUNT(*) FROM $tb")->fetchColumn();
        echo "Total Rows in '$tb': " . $cnt . "<br>";

        // 3. Show Columns
        $stm = $pdo->query("SHOW COLUMNS FROM $tb");
        $cols = $stm->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Columns:</h4><pre>";
        print_r($cols);
        echo "</pre>";

        // 4. Show first 3 rows
        if ($cnt > 0) {
            $stm = $pdo->query("SELECT * FROM $tb LIMIT 3");
            $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>First 3 rows:</h4><pre>";
            print_r($rows);
            echo "</pre>";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
