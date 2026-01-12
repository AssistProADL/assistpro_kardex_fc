<?php
// /public/api/sfa/reldaycli_get.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

$resp = ['ok'=>0,'data'=>[],'error'=>'','detalle'=>''];

try {
    // Resolver PDO robusto
    $pdo = null;
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) $pdo = $GLOBALS['pdo'];
    if (!$pdo && function_exists('db')) $pdo = db();
    if (!$pdo) throw new Exception('No hay conexión PDO disponible (revisa app/db.php).');

    $almacen = (int)($_GET['almacen'] ?? 0);
    $ruta    = (int)($_GET['ruta'] ?? 0);

    if ($almacen <= 0 || $ruta <= 0) {
        $resp['error'] = 'Parámetros incompletos (almacen/ruta).';
        echo json_encode($resp);
        exit;
    }

    $sql = "
        SELECT 
          Id_Destinatario AS id_destinatario,
          Cve_Cliente     AS cve_cliente,
          Cve_Vendedor    AS cve_vendedor,
          Lu, Ma, Mi, Ju, Vi, Sa, Do
        FROM reldaycli
        WHERE Cve_Almac = :alm AND Cve_Ruta = :ruta
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':alm'=>$almacen, ':ruta'=>$ruta]);

    $map = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$r['id_destinatario'];
        $map[$id] = [
            'id_destinatario' => $id,
            'cve_cliente'     => $r['cve_cliente'],
            'cve_vendedor'    => $r['cve_vendedor'],
            'Lu' => (int)$r['Lu'],
            'Ma' => (int)$r['Ma'],
            'Mi' => (int)$r['Mi'],
            'Ju' => (int)$r['Ju'],
            'Vi' => (int)$r['Vi'],
            'Sa' => (int)$r['Sa'],
            'Do' => (int)$r['Do'],
        ];
    }

    $resp['ok'] = 1;
    $resp['data'] = $map;
    echo json_encode($resp);

} catch (Throwable $e) {
    $resp['ok'] = 0;
    $resp['error'] = 'Error consultando reldaycli';
    $resp['detalle'] = $e->getMessage();
    echo json_encode($resp);
}
