<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$sql = "
SELECT cve_cia, des_cia
FROM c_compania
WHERE Activo = 1
ORDER BY des_cia
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
