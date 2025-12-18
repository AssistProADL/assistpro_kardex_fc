<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

$idInventario = (int)($_GET['id_inventario'] ?? 0);

if (!$idInventario) {
    http_response_code(400);
    echo json_encode(['error' => 'id_inventario requerido']);
    exit;
}

/*
 Consolidamos así:
 - cantidad_contada = promedio de conteos válidos
   (esto se puede cambiar a regla distinta después)
*/

$sql = "
SELECT
    io.id_inventario_objeto,
    io.tipo_objeto,
    io.id_referencia,

    io.snapshot_teorico,

    COUNT(ic.id_conteo) AS total_conteos,
    IFNULL(AVG(ic.cantidad),0) AS cantidad_contada,

    (IFNULL(AVG(ic.cantidad),0) - io.snapshot_teorico) AS diferencia

FROM inventario_objeto io
LEFT JOIN inventario_conteo ic
       ON ic.id_inventario = io.id_inventario
      AND ic.tipo_objeto = io.tipo_objeto
      AND ic.id_referencia = io.id_referencia

WHERE io.id_inventario = ?

GROUP BY
    io.id_inventario_objeto,
    io.tipo_objeto,
    io.id_referencia,
    io.snapshot_teorico

ORDER BY io.tipo_objeto, io.id_referencia
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$idInventario]);

$data = [];

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $estatus = 'PENDIENTE';

    if ($r['total_conteos'] > 0) {
        if ((float)$r['diferencia'] == 0.0) {
            $estatus = 'OK';
        } else {
            $estatus = 'DIFERENCIA';
        }
    }

    $data[] = [
        'tipo_objeto' => $r['tipo_objeto'],
        'id_referencia' => $r['id_referencia'],
        'snapshot_teorico' => (float)$r['snapshot_teorico'],
        'cantidad_contada' => round((float)$r['cantidad_contada'], 4),
        'diferencia' => round((float)$r['diferencia'], 4),
        'conteos' => (int)$r['total_conteos'],
        'estatus' => $estatus
    ];
}

echo json_encode($data);
