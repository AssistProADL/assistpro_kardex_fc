<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

try {
    $alm = isset($_GET['almacen']) ? (int)$_GET['almacen'] : 0;

    $sql = "SELECT id, Lista, FechaIni, FechaFin, Cve_Almac FROM listap";
    $params = [];

    if ($alm > 0) {
        $sql .= " WHERE (Cve_Almac = ? OR Cve_Almac IS NULL)";
        $params[] = $alm;
    }

    $sql .= " ORDER BY Lista";

    $rows = function_exists('db_all')
        ? db_all($sql, $params)
        : $GLOBALS['pdo']->prepare($sql)->execute($params)->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            "id"        => (string)$r['id'],
            "nombre"    => (string)$r['Lista'],
            "fecha_ini" => $r['FechaIni'],
            "fecha_fin" => $r['FechaFin'],
            "almacen"   => $r['Cve_Almac']
        ];
    }

    echo json_encode(["ok"=>1,"data"=>$out], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(["ok"=>0,"error"=>"Error servidor","detalle"=>$e->getMessage()]);
}
