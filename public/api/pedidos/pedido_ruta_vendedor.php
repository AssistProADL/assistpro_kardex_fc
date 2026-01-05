<?php
require_once __DIR__ . '/../../../app/db.php';
header('Content-Type: application/json');

try {
    $pdo = db_pdo();

    $idRuta    = (int)($_GET['id_ruta'] ?? 0);
    $idEmpresa = (int)($_GET['id_empresa'] ?? 0);

    if ($idRuta <= 0 || $idEmpresa <= 0) {
        echo json_encode(['ok'=>0,'msg'=>'Parámetros inválidos']);
        exit;
    }

    $sql = "
        SELECT IdVendedor
        FROM relvendrutas
        WHERE IdRuta = ?
          AND IdEmpresa = ?
          AND Activo = 1
        ORDER BY Fecha DESC
        LIMIT 1
    ";

    $q = $pdo->prepare($sql);
    $q->execute([$idRuta, $idEmpresa]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok'=>0,'msg'=>'Ruta sin vendedor asignado']);
        exit;
    }

    echo json_encode([
        'ok' => 1,
        'id_vendedor' => (int)$row['IdVendedor']
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'ok'=>0,
        'msg'=>'Error servidor',
        'error'=>$e->getMessage()
    ]);
}
