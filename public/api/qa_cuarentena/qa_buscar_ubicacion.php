<?php
require_once __DIR__ . '/_qa_bootstrap.php';

$idy_ubica = trim($_GET['idy_ubica'] ?? '');
$bl = trim($_GET['bl'] ?? '');
$cve_almac = trim($_GET['cve_almac'] ?? '');

if ($idy_ubica === '' && $bl === '') qa_json(false, null, "Proporcione idy_ubica o bl", 400);

if ($idy_ubica === '' && $bl !== '') {
    $u = qa_query_all("SELECT idy_ubica FROM c_ubicacion WHERE CodigoCSD = ? OR Ubicacion = ? LIMIT 1", [$bl, $bl]);
    if (!$u) qa_json(false, null, "Ubicación no encontrada", 404);
    $idy_ubica = $u[0]['idy_ubica'];
}
$idy_ubica = (int)$idy_ubica;

$uinfo = qa_query_all("SELECT idy_ubica, cve_almac, CodigoCSD, Ubicacion, Seccion, cve_pasillo, cve_rack, cve_nivel, num_volumenDisp, PesoMaximo, PesoOcupado 
                       FROM c_ubicacion WHERE idy_ubica = ? LIMIT 1", [$idy_ubica]);
$uinfo = $uinfo ? $uinfo[0] : null;

$params = [$idy_ubica];
$almF = "";
if ($cve_almac !== '') { $almF = " AND cve_almac = ? "; $params[] = (int)$cve_almac; }
$rows = [];

// Piezas
$rows = array_merge($rows, qa_query_all("SELECT 'PZ' AS nivel, cve_almac, idy_ubica, cve_articulo, cve_lote, Existencia AS cantidad, IFNULL(Cuarentena,0) AS cuarentena, epc, code
                                        FROM ts_existenciapiezas WHERE idy_ubica = ? $almF LIMIT 5000", $params));

// Cajas
$paramsCJ = [$idy_ubica];
$almCJ = "";
if ($cve_almac !== '') { $almCJ = " AND Cve_Almac = ? "; $paramsCJ[] = (int)$cve_almac; }
$rows = array_merge($rows, qa_query_all("SELECT 'CJ' AS nivel, Cve_Almac AS cve_almac, idy_ubica, cve_articulo, cve_lote, Id_Caja AS id_contenedor, nTarima,
                                               PiezasXCaja AS cantidad, IFNULL(Cuarentena,0) AS cuarentena, epc, code
                                        FROM ts_existenciacajas WHERE idy_ubica = ? $almCJ LIMIT 5000", $paramsCJ));

// Tarimas
$paramsTR = [$idy_ubica];
$almTR = "";
if ($cve_almac !== '') { $almTR = " AND cve_almac = ? "; $paramsTR[] = (int)$cve_almac; }
$rows = array_merge($rows, qa_query_all("SELECT 'TR' AS nivel, cve_almac, idy_ubica, cve_articulo, lote AS cve_lote, ntarima AS id_contenedor,
                                               existencia AS cantidad, IFNULL(Cuarentena,0) AS cuarentena, epc, code
                                        FROM ts_existenciatarima WHERE idy_ubica = ? $almTR LIMIT 5000", $paramsTR));

qa_json(true, [
    'ubicacion' => $uinfo,
    'rows' => $rows
], "OK");
