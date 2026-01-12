<?php
require_once __DIR__ . '/../../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db_pdo();

    $cveRuta   = trim((string)($_GET['cve_ruta'] ?? ''));
    $idEmpresa = trim((string)($_GET['id_empresa'] ?? ''));

    if ($cveRuta === '') {
        echo json_encode(['ok'=>0,'rows'=>[], 'msg'=>'cve_ruta requerida']);
        exit;
    }

    // Nota: evitamos collation mix forzando COLLATE en comparaciones.
    $sql = "
        SELECT
            id_cliente,
            Cve_Clte,
            RazonSocial,
            RFC
        FROM c_cliente
        WHERE COALESCE(Activo,1)=1
          AND cve_ruta COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci
    ";

    $params = [$cveRuta];

    // Si IdEmpresa existe en tu data, filtramos. Si no, no rompe.
    if ($idEmpresa !== '') {
        $sql .= " AND COALESCE(IdEmpresa,'') COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci ";
        $params[] = $idEmpresa;
    }

    $sql .= " ORDER BY RazonSocial LIMIT 5000 ";

    $q = $pdo->prepare($sql);
    $q->execute($params);

    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['ok'=>1,'rows'=>$rows]);

} catch (Throwable $e) {
    echo json_encode(['ok'=>0,'rows'=>[], 'msg'=>'Error servidor','error'=>$e->getMessage()]);
}
