<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

try {
    echo "<h3>Fixing 'ctiket' Schema</h3>";

    // 1. Make ID Primary Key and Auto Increment
    // We assume ID is int(11) based on previous debug output
    $sqlAlter = "ALTER TABLE ctiket MODIFY COLUMN ID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    $pdo->query($sqlAlter);
    echo "✅ Success: ID is now PRIMARY KEY and AUTO_INCREMENT.<br>";

    // 2. Insert Test Record
    $check = $pdo->query("SELECT COUNT(*) FROM ctiket")->fetchColumn();
    if ($check == 0) {
        $sqlInsert = "INSERT INTO ctiket (Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, MLiq, IdEmpresa) 
                      VALUES ('TICKET PRUEBA', 'Calle Falsa 123', 'Col. Centro', 'CDMX', 'Gracias por su compra', 30, 1, 'EMP01')";
        $pdo->query($sqlInsert);
        echo "✅ Success: Inserted sample record.<br>";
    } else {
        echo "ℹ️ Table already has data, skipped insert.<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
