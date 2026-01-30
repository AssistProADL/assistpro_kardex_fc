<?php
// public/api/qa_cuarentena/qa_buscar_lp.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

function qa_json($ok, $data = null, $msg = 'OK', $http = 200){
  http_response_code($http);
  echo json_encode(['ok'=>(bool)$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}
function qa_require_params(array $keys){
  foreach($keys as $k){
    if(!isset($_GET[$k]) || trim((string)$_GET[$k])===''){
      qa_json(false, null, "Parámetro requerido: {$k}", 400);
    }
  }
}

qa_require_params(['q']);
$q = trim((string)$_GET['q']);

// Heurística EPC vs LP/CODE
$byEpc = (strlen($q) > 40);

// 1) Si es EPC, buscamos directo en existencias (rápido y seguro)
if($byEpc){
  $sqlT = "SELECT
              'TR' AS nivel,
              cve_almac,
              idy_ubica,
              cve_articulo,
              lote AS cve_lote,
              ntarima AS id_contenedor,
              existencia AS cantidad,
              IFNULL(Cuarentena,0) AS cuarentena,
              epc,
              code
           FROM ts_existenciatarima
           WHERE epc = ?
           LIMIT 200";
  $st = $pdo->prepare($sqlT);
  $st->execute([$q]);
  $tar = $st->fetchAll(PDO::FETCH_ASSOC);

  $sqlC = "SELECT
              'CJ' AS nivel,
              Cve_Almac AS cve_almac,
              idy_ubica,
              cve_articulo,
              cve_lote,
              Id_Caja AS id_contenedor,
              PiezasXCaja AS cantidad,
              IFNULL(Cuarentena,0) AS cuarentena,
              epc,
              code,
              nTarima
           FROM ts_existenciacajas
           WHERE epc = ?
           LIMIT 200";
  $st = $pdo->prepare($sqlC);
  $st->execute([$q]);
  $caj = $st->fetchAll(PDO::FETCH_ASSOC);

  $rows = array_merge($tar, $caj);
  if(!$rows) qa_json(false, null, "LP/Contenedor no encontrado", 404);

  $idy = (int)$rows[0]['idy_ubica'];
  $bl = null;
  if($idy>0){
    $st = $pdo->prepare("SELECT CodigoCSD, Ubicacion, Seccion, cve_pasillo, cve_rack, cve_nivel
                         FROM c_ubicacion WHERE idy_ubica=? LIMIT 1");
    $st->execute([$idy]);
    $bl = $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  qa_json(true, ['query'=>$q,'matches'=>$rows,'ubicacion'=>$bl], 'OK');
}

// 2) Si NO es EPC: primero resolvemos por LP en c_charolas (la verdad del negocio)
//    - Esto es lo que tu módulo de consulta ya hace (lookup_lp.php)
$st = $pdo->prepare("
  SELECT CveLP, tipo, cve_almac, Activo, IDContenedor
  FROM c_charolas
  WHERE CveLP = ?
  LIMIT 1
");
$st->execute([$q]);
$lp = $st->fetch(PDO::FETCH_ASSOC);

// fallback por LIKE (por si llega con espacios / parcial)
if(!$lp){
  $st = $pdo->prepare("
    SELECT CveLP, tipo, cve_almac, Activo, IDContenedor
    FROM c_charolas
    WHERE CveLP LIKE ?
    ORDER BY Activo DESC, CveLP
    LIMIT 1
  ");
  $st->execute(["%{$q}%"]);
  $lp = $st->fetch(PDO::FETCH_ASSOC);
}

$rows = [];
$bl = null;

if($lp){
  $tipo = strtoupper((string)$lp['tipo']);
  $idCont = (int)$lp['IDContenedor'];

  if($tipo === 'PALLET'){
    $st = $pdo->prepare("
      SELECT
        'TR' AS nivel,
        cve_almac,
        idy_ubica,
        cve_articulo,
        lote AS cve_lote,
        ntarima AS id_contenedor,
        existencia AS cantidad,
        IFNULL(Cuarentena,0) AS cuarentena,
        epc,
        code
      FROM ts_existenciatarima
      WHERE ntarima = ?
      LIMIT 200
    ");
    $st->execute([$idCont]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  } else {
    // CONTENEDOR / CAJA
    $st = $pdo->prepare("
      SELECT
        'CJ' AS nivel,
        Cve_Almac AS cve_almac,
        idy_ubica,
        cve_articulo,
        cve_lote,
        Id_Caja AS id_contenedor,
        PiezasXCaja AS cantidad,
        IFNULL(Cuarentena,0) AS cuarentena,
        epc,
        code,
        nTarima
      FROM ts_existenciacajas
      WHERE Id_Caja = ?
      LIMIT 200
    ");
    $st->execute([$idCont]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }

  // BL desde idy_ubica (si hay registros)
  if($rows){
    $idy = (int)$rows[0]['idy_ubica'];
    if($idy>0){
      $st = $pdo->prepare("SELECT CodigoCSD, Ubicacion, Seccion, cve_pasillo, cve_rack, cve_nivel
                           FROM c_ubicacion WHERE idy_ubica=? LIMIT 1");
      $st->execute([$idy]);
      $bl = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    qa_json(true, ['query'=>$q,'matches'=>$rows,'ubicacion'=>$bl], 'OK');
  }

  // LP existe pero sin registros en existencias (dato útil)
  qa_json(false, ['query'=>$q,'lp'=>$lp,'matches'=>[],'ubicacion'=>null], 'LP existe pero no tiene existencias ligadas', 404);
}

// 3) Si no está en c_charolas, hacemos fallback por CODE directo en existencias
$st = $pdo->prepare("
  SELECT
    'TR' AS nivel,
    cve_almac,
    idy_ubica,
    cve_articulo,
    lote AS cve_lote,
    ntarima AS id_contenedor,
    existencia AS cantidad,
    IFNULL(Cuarentena,0) AS cuarentena,
    epc,
    code
  FROM ts_existenciatarima
  WHERE code = ?
  LIMIT 200
");
$st->execute([$q]);
$tar = $st->fetchAll(PDO::FETCH_ASSOC);

$st = $pdo->prepare("
  SELECT
    'CJ' AS nivel,
    Cve_Almac AS cve_almac,
    idy_ubica,
    cve_articulo,
    cve_lote,
    Id_Caja AS id_contenedor,
    PiezasXCaja AS cantidad,
    IFNULL(Cuarentena,0) AS cuarentena,
    epc,
    code,
    nTarima
  FROM ts_existenciacajas
  WHERE code = ?
  LIMIT 200
");
$st->execute([$q]);
$caj = $st->fetchAll(PDO::FETCH_ASSOC);

$rows = array_merge($tar, $caj);
if(!$rows) qa_json(false, null, "LP/Contenedor no encontrado", 404);

$idy = (int)$rows[0]['idy_ubica'];
if($idy>0){
  $st = $pdo->prepare("SELECT CodigoCSD, Ubicacion, Seccion, cve_pasillo, cve_rack, cve_nivel
                       FROM c_ubicacion WHERE idy_ubica=? LIMIT 1");
  $st->execute([$idy]);
  $bl = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

qa_json(true, ['query'=>$q,'matches'=>$rows,'ubicacion'=>$bl], 'OK');
