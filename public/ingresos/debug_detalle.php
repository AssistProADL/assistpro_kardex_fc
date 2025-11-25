<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$idAduana = 10173;

echo "<h2>Debug: Verificando datos para ID_Aduana = $idAduana</h2>";

// 1. Verificar encabezado
echo "<h3>1. Encabezado (th_aduana)</h3>";
$stH = $pdo->prepare("SELECT * FROM th_aduana WHERE ID_Aduana = :id LIMIT 1");
$stH->execute([':id' => $idAduana]);
$rowH = $stH->fetch(PDO::FETCH_ASSOC);

if ($rowH) {
    echo "<pre>";
    print_r($rowH);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>NO SE ENCONTRÓ ENCABEZADO para ID_Aduana = $idAduana</p>";
}

// 2. Verificar detalle
echo "<h3>2. Detalle (td_aduana)</h3>";
$stD = $pdo->prepare("
    SELECT d.*, a.des_articulo, a.cve_umed
    FROM td_aduana d
    LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
    WHERE d.ID_Aduana = :id
    ORDER BY d.Item ASC
");
$stD->execute([':id' => $idAduana]);
$rowsD = $stD->fetchAll(PDO::FETCH_ASSOC);

if (count($rowsD) > 0) {
    echo "<p>Se encontraron " . count($rowsD) . " registros de detalle:</p>";
    echo "<pre>";
    print_r($rowsD);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>NO SE ENCONTRARON DETALLES para ID_Aduana = $idAduana</p>";
}

// 3. Verificar si hay registros en td_aduana en general
echo "<h3>3. Verificar registros en td_aduana (últimos 10)</h3>";
$stAll = $pdo->query("SELECT ID_Aduana, COUNT(*) as total FROM td_aduana GROUP BY ID_Aduana ORDER BY ID_Aduana DESC LIMIT 10");
$allRecords = $stAll->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($allRecords);
echo "</pre>";
