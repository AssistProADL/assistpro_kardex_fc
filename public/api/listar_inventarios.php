<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

$where = [];
$params = [];

if (!empty($_GET['estado'])) {
    $where[] = 'i.estado = ?';
    $params[] = $_GET['estado'];
}
if (!empty($_GET['tipo'])) {
    $where[] = 'i.tipo_inventario = ?';
    $params[] = $_GET['tipo'];
}
if (!empty($_GET['almacenp'])) {
    $where[] = 'i.cve_almacenp = ?';
    $params[] = (int)$_GET['almacenp'];
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    i.id_inventario,
    i.folio,
    i.tipo_inventario,
    i.estado,
    i.fecha_creacion,

    -- total de objetos
    COUNT(DISTINCT io.id_inventario_objeto) AS total_objetos,

    -- objetos con al menos un conteo
    COUNT(DISTINCT ic.id_referencia) AS objetos_contados,

    -- diferencias (en tiempo real)
    SUM(
        CASE
            WHEN io.snapshot_teorico IS NOT NULL
             AND IFNULL(SUM(ic.cantidad),0) <> io.snapshot_teorico
            THEN 1 ELSE 0
        END
    ) AS diferencias

FROM inventario i
LEFT JOIN inventario_objeto io
       ON io.id_inventario = i.id_inventario
LEFT JOIN inventario_conteo ic
       ON ic.id_inventario = i.id_inventario
      AND ic.tipo_objeto = io.tipo_objeto
      AND ic.id_referencia = io.id_referencia

$whereSql
GROUP BY i.id_inventario
ORDER BY i.fecha_creacion DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$result = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $total = (int)$row['total_objetos'];
    $contados = (int)$row['objetos_contados'];

    $avance = ($total > 0)
        ? round(($contados / $total) * 100, 2)
        : 0;

    $result[] = [
        'id' => $row['id_inventario'],
        'folio' => $row['folio'],
        'tipo' => $row['tipo_inventario'],
        'estado' => $row['estado'],
        'fecha' => $row['fecha_creacion'],
        'avance' => $avance,
        'diferencias' => (int)$row['diferencias']
    ];
}

echo json_encode($result);
