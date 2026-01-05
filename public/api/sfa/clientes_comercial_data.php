<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

/**
 * POST JSON:
 * { "ids": ["443","447"] }
 */
try {
    $raw = file_get_contents("php://input");
    $j = json_decode($raw, true);

    $ids = $j['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(["ok"=>1,"data"=>[]]);
        exit;
    }

    $idsInt = [];
    foreach ($ids as $v) {
        $v = (int)$v;
        if ($v > 0) $idsInt[] = $v;
    }
    $idsInt = array_unique($idsInt);
    if (empty($idsInt)) {
        echo json_encode(["ok"=>1,"data"=>[]]);
        exit;
    }

    $ph = implode(',', array_fill(0, count($idsInt), '?'));

    $sql = "
        SELECT
            r.Id_Destinatario,
            r.ListaP,
            r.ListaPromo,
            r.ListaD,
            lp.Lista AS lp_nombre,
            pr.Lista AS promo_nombre,
            ld.Lista AS desc_nombre
        FROM relclilis r
        LEFT JOIN listap lp ON lp.id = r.ListaP
        LEFT JOIN listap pr ON pr.id = r.ListaPromo
        LEFT JOIN listap ld ON ld.id = r.ListaD
        WHERE r.Id_Destinatario IN ($ph)
    ";

    $rows = function_exists('db_all')
        ? db_all($sql, $idsInt)
        : $GLOBALS['pdo']->prepare($sql)->execute($idsInt)->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $id = (string)$r['Id_Destinatario'];
        $out[$id] = [
            "lp"    => ["id"=>$r['ListaP'] ?: "",     "nombre"=>$r['lp_nombre'] ?? ""],
            "promo" => ["id"=>$r['ListaPromo'] ?: "", "nombre"=>$r['promo_nombre'] ?? ""],
            "desc"  => ["id"=>$r['ListaD'] ?: "",     "nombre"=>$r['desc_nombre'] ?? ""],
        ];
    }

    echo json_encode(["ok"=>1,"data"=>$out], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(["ok"=>0,"error"=>"Error servidor","detalle"=>$e->getMessage()]);
}
