<?php
// /public/sfa/api/resumen_rutas_data.php

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function pick($arr, $keys, $default=null){
  foreach($keys as $k){
    if(isset($arr[$k]) && $arr[$k] !== '') return $arr[$k];
  }
  return $default;
}

try {

  $__start = microtime(true);

  $in = $_POST ?: $_GET;

  $cve_almac = pick($in, ['cve_almac','IdEmpresa','idEmpresa','empresa_id'], null);
  $ruta_id   = pick($in, ['ruta_id','IdRuta','idRuta'], null);

  $cve_almac = ($cve_almac === null || $cve_almac === '0' || $cve_almac === 'ALL') ? null : (int)$cve_almac;
  $ruta_id   = ($ruta_id   === null || $ruta_id   === '0' || $ruta_id   === 'ALL') ? null : (int)$ruta_id;

  $pdo = db_pdo();

  /* ==========================================================
     1) RESUMEN POR RUTA
  ========================================================== */

  $where = [];
  $params = [];

  if ($cve_almac !== null) {
    $where[] = "r.cve_almacenp = :cve_almac";
    $params[':cve_almac'] = $cve_almac;
  }
  if ($ruta_id !== null) {
    $where[] = "r.ID_Ruta = :ruta_id";
    $params[':ruta_id'] = $ruta_id;
  }

  $sql = "
    SELECT
      r.ID_Ruta,
      r.cve_ruta,
      r.descripcion,
      r.Activo,
      r.cve_almacenp,
      a.des_almac AS almacen,
      COUNT(DISTINCT rc.IdCliente) AS clientes_asignados,
      COUNT(DISTINCT NULLIF(TRIM(c.postal),'') ) AS cps,
      SUM(
        CASE
          WHEN c.latitud  IS NOT NULL AND TRIM(c.latitud)  <> ''
           AND c.longitud IS NOT NULL AND TRIM(c.longitud) <> ''
          THEN 1 ELSE 0
        END
      ) AS clientes_geo
    FROM t_ruta r
    LEFT JOIN relclirutas rc     ON rc.IdRuta = r.ID_Ruta
    LEFT JOIN c_destinatarios c  ON c.id_destinatario = rc.IdCliente
    LEFT JOIN c_almacen a        ON a.cve_almac = r.cve_almacenp
    " . (count($where) ? " WHERE " . implode(" AND ", $where) : "") . "
    GROUP BY
      r.ID_Ruta, r.cve_ruta, r.descripcion, r.Activo, r.cve_almacenp, a.des_almac
    ORDER BY r.descripcion
  ";

  $st = $pdo->prepare($sql);
  foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $rutas = [];
  $totClientes = 0;
  $totGeo = 0;

  foreach ($rows as $r) {

    $clientes = (int)$r['clientes_asignados'];
    $geo      = (int)$r['clientes_geo'];
    $cps      = (int)$r['cps'];

    $geoPct = $clientes > 0 ? round(100 * $geo / $clientes, 2) : 0;

    $estado = 'rojo';
    if ($geoPct >= 80) $estado = 'verde';
    else if ($geoPct >= 40) $estado = 'amarillo';

    $totClientes += $clientes;
    $totGeo      += $geo;

    $rutas[] = [
      'ID_Ruta'  => (int)$r['ID_Ruta'],
      'cve_ruta' => $r['cve_ruta'],
      'ruta'     => $r['descripcion'],
      'clientes' => $clientes,
      'dias'     => '-',
      'cps'      => $cps,
      'geo_pct'  => $geoPct,
      'estado'   => $estado,
      'almacen'  => $r['almacen'],
      'activo'   => $r['Activo'],
    ];
  }

  $geoKpi = $totClientes > 0 ? round(100 * $totGeo / $totClientes, 2) : 0;

  /* ==========================================================
     2) DISTRIBUCIÓN POR DÍA
  ========================================================== */

  $whereD = [];
  $paramsD = [];

  if ($cve_almac !== null) {
    $whereD[] = "CAST(NULLIF(TRIM(d.Cve_Almac),'') AS UNSIGNED) = :cve_almac";
    $paramsD[':cve_almac'] = $cve_almac;
  }
  if ($ruta_id !== null) {
    $whereD[] = "r.ID_Ruta = :ruta_id";
    $paramsD[':ruta_id'] = $ruta_id;
  }

  $sqlDiasRoute = "
    SELECT
      r.ID_Ruta,
      r.descripcion AS ruta,
      SUM(IFNULL(d.Lu,0)) AS LUN,
      SUM(IFNULL(d.Ma,0)) AS MAR,
      SUM(IFNULL(d.Mi,0)) AS MIE,
      SUM(IFNULL(d.Ju,0)) AS JUE,
      SUM(IFNULL(d.Vi,0)) AS VIE,
      SUM(IFNULL(d.Sa,0)) AS SAB,
      SUM(IFNULL(d.Do,0)) AS DOM
    FROM reldaycli d
    INNER JOIN t_ruta r
      ON r.ID_Ruta = CAST(NULLIF(TRIM(d.Cve_Ruta),'') AS UNSIGNED)
    " . (count($whereD) ? " WHERE " . implode(" AND ", $whereD) : "") . "
    GROUP BY r.ID_Ruta, r.descripcion
    ORDER BY r.descripcion
  ";

  $stD = $pdo->prepare($sqlDiasRoute);
  foreach ($paramsD as $k => $v) $stD->bindValue($k, $v, PDO::PARAM_INT);
  $stD->execute();
  $dias_route = $stD->fetchAll(PDO::FETCH_ASSOC);

  $dayMap = [
    ['key'=>'LUN', 'label'=>'Lunes'],
    ['key'=>'MAR', 'label'=>'Martes'],
    ['key'=>'MIE', 'label'=>'Miércoles'],
    ['key'=>'JUE', 'label'=>'Jueves'],
    ['key'=>'VIE', 'label'=>'Viernes'],
    ['key'=>'SAB', 'label'=>'Sábado'],
    ['key'=>'DOM', 'label'=>'Domingo'],
  ];

  $dias = [];

  foreach ($dayMap as $dinfo) {
    $k = $dinfo['key'];
    $rutasConVisita = 0;
    $clientesVisita = 0;

    foreach ($dias_route as $rr) {
      $v = (int)($rr[$k] ?? 0);
      if ($v > 0) $rutasConVisita++;
      $clientesVisita += $v;
    }

    $dias[] = [
      'dia' => $dinfo['label'],
      'rutas' => $rutasConVisita,
      'clientes' => $clientesVisita,
    ];
  }

  /* ==========================================================
     INTELIGENCIA SISTEMA
  ========================================================== */

  $clientesSinRuta = 0;

  $estadoGlobal = 'ESTABLE';
  $colorGlobal  = 'verde';
  $nivel        = 1;

  if ($geoKpi < 60) {
    $estadoGlobal = 'ALERTA';
    $colorGlobal  = 'amarillo';
    $nivel        = 2;
  }

  if ($geoKpi < 35) {
    $estadoGlobal = 'CRITICO';
    $colorGlobal  = 'rojo';
    $nivel        = 3;
  }

  $alertas = [];

  if ($geoKpi < 50) {
    $alertas[] = [
      'tipo' => 'cobertura',
      'nivel' => 'medio',
      'mensaje' => 'Cobertura geográfica baja'
    ];
  }

  $__response_ms = round((microtime(true) - $__start) * 1000, 2);

  echo json_encode([
    'ok' => 1,
    'meta' => [
      'empresa' => $cve_almac,
      'ruta' => $ruta_id,
      'timestamp' => date('Y-m-d H:i:s'),
      'response_ms' => $__response_ms,
      'version' => '2.3.0'
    ],
    'kpis' => [
      'rutas_activas'       => count($rutas),
      'clientes_asignados'  => $totClientes,
      'clientes_sin_ruta'   => $clientesSinRuta,
      'cobertura_geo'       => $geoKpi,
      'documentos'          => 0,
      'total_ventas'        => 0
    ],
    'salud' => [
      'estado' => $estadoGlobal,
      'color'  => $colorGlobal,
      'nivel'  => $nivel
    ],
    'rutas' => $rutas,
    'dias' => $dias,
    'dias_route' => $dias_route,
    'alertas' => $alertas
  ]);

} catch (Throwable $e) {

  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ]);
}
