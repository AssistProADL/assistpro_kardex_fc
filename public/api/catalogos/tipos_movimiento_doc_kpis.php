<?php
require_once __DIR__ . '/../_base.php';

$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$modulo     = trim($_GET['modulo'] ?? '');

if ($empresa_id <= 0 || $modulo === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

$sql = "
SELECT
    COUNT(*)                                   AS total,
    SUM(activo = 1)                            AS activos,
    SUM(activo = 0)                            AS inactivos,
    SUM(requiere_folio = 1 AND activo = 1)    AS requieren_folio
FROM c_tipo_movimiento_doc
WHERE empresa_id = :empresa_id
  AND modulo = :modulo
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':empresa_id' => $empresa_id,
    ':modulo'     => $modulo
]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
