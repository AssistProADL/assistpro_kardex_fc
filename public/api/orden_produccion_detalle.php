<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$id = $_POST['id'] ?? 0;

/* =============================
   ENCABEZADO DE LA OT
   ============================= */
$sqlOT = "
SELECT
    Folio_Pro,
    Cve_Articulo,
    Cve_Lote,
    Cantidad,
    Status
FROM t_ordenprod
WHERE id = :id
";
$stmt = $pdo->prepare($sqlOT);
$stmt->execute([':id' => $id]);
$ot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ot) {
    echo json_encode(['error' => 'OT no encontrada']);
    exit;
}

/* =============================
   BOM / COMPONENTES
   ============================= */
$sqlDet = "
SELECT
    Cve_Articulo,
    Cve_Lote,
    Cantidad,
    DATE_FORMAT(Fecha_Prod,'%d/%m/%Y') AS Fecha_Prod,
    Usr_Armo,
    Referencia,
    Cve_Almac_Ori
FROM td_ordenprod
WHERE Folio_Pro = :folio
  AND Activo = 1
ORDER BY Cve_Articulo
";

$stmt2 = $pdo->prepare($sqlDet);
$stmt2->execute([':folio' => $ot['Folio_Pro']]);
$detalle = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'ot'      => $ot,
    'detalle' => $detalle
]);
