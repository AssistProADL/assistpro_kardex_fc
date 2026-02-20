<?php
require_once __DIR__ . '/_qa_bootstrap.php';

$cve_articulo = trim($_GET['cve_articulo'] ?? '');
$cve_lote     = trim($_GET['cve_lote'] ?? ''); // si aplica, la UI lo hará obligatorio
$cve_almac    = trim($_GET['cve_almac'] ?? '');

qa_require_params(['cve_articulo'=>$cve_articulo], ['cve_articulo']);

$params = [$cve_articulo];
$whereLotePZ = "";
$whereLoteCJ = "";
$whereLoteTR = "";
if ($cve_lote !== '') {
    $whereLotePZ = " AND p.cve_lote = ? ";
    $whereLoteCJ = " AND c.cve_lote = ? ";
    $whereLoteTR = " AND t.lote = ? ";
    $params[] = $cve_lote;
}
$alm = "";
if ($cve_almac !== '') {
    $alm = " AND p.cve_almac = ? ";
    $almCJ = " AND c.Cve_Almac = ? ";
    $almTR = " AND t.cve_almac = ? ";
} else {
    $almCJ = "";
    $almTR = "";
}
if ($cve_almac !== '') $params[] = (int)$cve_almac;

$rows = [];

// Piezas
$sqlPZ = "SELECT 'PZ' AS nivel, p.cve_almac, p.idy_ubica, u.CodigoCSD AS bl,
                 p.cve_articulo, p.cve_lote, NULL AS id_contenedor, NULL AS nTarima,
                 p.Existencia AS cantidad, IFNULL(p.Cuarentena,0) AS cuarentena,
                 p.epc, p.code
          FROM ts_existenciapiezas p
          JOIN c_ubicacion u ON u.idy_ubica = p.idy_ubica
          WHERE p.cve_articulo = ? $whereLotePZ $alm
          LIMIT 2000";
$rows = array_merge($rows, qa_query_all($sqlPZ, $params));

// Cajas
$paramsCJ = [$cve_articulo];
if ($cve_lote !== '') $paramsCJ[] = $cve_lote;
if ($cve_almac !== '') $paramsCJ[] = (int)$cve_almac;

$sqlCJ = "SELECT 'CJ' AS nivel, c.Cve_Almac AS cve_almac, c.idy_ubica, u.CodigoCSD AS bl,
                 c.cve_articulo, c.cve_lote, c.Id_Caja AS id_contenedor, c.nTarima,
                 c.PiezasXCaja AS cantidad, IFNULL(c.Cuarentena,0) AS cuarentena,
                 c.epc, c.code
          FROM ts_existenciacajas c
          JOIN c_ubicacion u ON u.idy_ubica = c.idy_ubica
          WHERE c.cve_articulo = ? $whereLoteCJ $almCJ
          LIMIT 2000";
$rows = array_merge($rows, qa_query_all($sqlCJ, $paramsCJ));

// Tarimas
$paramsTR = [$cve_articulo];
if ($cve_lote !== '') $paramsTR[] = $cve_lote;
if ($cve_almac !== '') $paramsTR[] = (int)$cve_almac;

$sqlTR = "SELECT 'TR' AS nivel, t.cve_almac, t.idy_ubica, u.CodigoCSD AS bl,
                 t.cve_articulo, t.lote AS cve_lote, t.ntarima AS id_contenedor, t.ntarima AS nTarima,
                 t.existencia AS cantidad, IFNULL(t.Cuarentena,0) AS cuarentena,
                 t.epc, t.code
          FROM ts_existenciatarima t
          JOIN c_ubicacion u ON u.idy_ubica = t.idy_ubica
          WHERE t.cve_articulo = ? $whereLoteTR $almTR
          LIMIT 2000";
$rows = array_merge($rows, qa_query_all($sqlTR, $paramsTR));

qa_json(true, [
    'cve_articulo' => $cve_articulo,
    'cve_lote' => $cve_lote,
    'rows' => $rows
], "OK");
