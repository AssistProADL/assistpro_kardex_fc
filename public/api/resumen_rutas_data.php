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
  $in = $_POST ?: $_GET;
 
  // UI alias: IdEmpresa/IdRuta (y también soporta cve_almac/ruta_id)
  $cve_almac = pick($in, ['cve_almac','IdEmpresa','idEmpresa','empresa_id'], null);
  $ruta_id   = pick($in, ['ruta_id','IdRuta','idRuta'], null);
 
  // Normaliza "Todas"
  $cve_almac = ($cve_almac === null || $cve_almac === '0' || $cve_almac === 'ALL') ? null : (int)$cve_almac;
  $ruta_id   = ($ruta_id   === null || $ruta_id   === '0' || $ruta_id   === 'ALL') ? null : (int)$ruta_id;
 
  $pdo = db_pdo();
 
  // ==========================================================
  // 1) Resumen por ruta (grid principal)
  // ==========================================================
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
      'dias'     => '-', // aquí no; la distribución real va abajo
      'cps'      => $cps,
      'geo_pct'  => $geoPct,
      'estado'   => $estado,
      'almacen'  => $r['almacen'],
      'activo'   => $r['Activo'],
    ];
  }
 
  $geoKpi = $totClientes > 0 ? round(100 * $totGeo / $totClientes, 2) : 0;
 
  // ==========================================================
  // 2) Matriz por ruta (desde reldaycli) + Distribución por día (para UI)
  //    Join: t_ruta.ID_Ruta  <->  CAST(reldaycli.Cve_Ruta AS UNSIGNED)
  // ==========================================================
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
 
  // Matriz por ruta (útil para futuro “Días” por ruta)
  $sqlDiasRoute = "
    SELECT
      r.ID_Ruta,
      r.descripcion AS ruta,
      SUM(CASE WHEN IFNULL(d.Lu,0) > 0 THEN 1 ELSE 0 END) AS LUN,
      SUM(CASE WHEN IFNULL(d.Ma,0) > 0 THEN 1 ELSE 0 END) AS MAR,
      SUM(CASE WHEN IFNULL(d.Mi,0) > 0 THEN 1 ELSE 0 END) AS MIE,
      SUM(CASE WHEN IFNULL(d.Ju,0) > 0 THEN 1 ELSE 0 END) AS JUE,
      SUM(CASE WHEN IFNULL(d.Vi,0) > 0 THEN 1 ELSE 0 END) AS VIE,
      SUM(CASE WHEN IFNULL(d.Sa,0) > 0 THEN 1 ELSE 0 END) AS SAB,
      SUM(CASE WHEN IFNULL(d.Do,0) > 0 THEN 1 ELSE 0 END) AS DOM,
      COUNT(DISTINCT d.Id_Destinatario) AS TOTAL_CLIENTES
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
 
  // ---- Distribución por día en formato que tu UI espera: {dia, rutas, clientes}
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
      if ($v > 0) $rutasConVisita++;     // rutas que sí tienen al menos 1 visita ese día
      $clientesVisita += $v;             // visitas (clientes) asignadas ese día
    }
 
    $dias[] = [
      'dia' => $dinfo['label'],
      'rutas' => $rutasConVisita,
      'clientes' => $clientesVisita,
    ];
  }
 
  // ==========================================================
  // Respuesta estándar (IMPORTANTE: resp.dias es lo que pinta tu tabla)
  // ==========================================================
  echo json_encode([
    'ok' => 1,
    'kpis' => [
      'rutas_activas'       => count($rutas),
      'clientes_asignados'  => $totClientes,
      'clientes_sin_ruta'   => 0,
      'cobertura_geo'       => $geoKpi,
      'documentos'          => 0,
      'total_ventas'        => 0
    ],
    'rutas' => $rutas,
 
    // <-- Esto es lo que TU UI usa en “Distribución por Día”
    'dias' => $dias,
 
    // Extra: matriz por ruta (por si luego quieres poblar la columna “Días” del grid)
    'dias_route' => $dias_route,
 
    'diagnostico' => [
      'input' => $in,
      'params_rutas' => $params,
      'params_dias'  => $paramsD,
      'rows_rutas'   => count($rows),
      'rows_dias_route' => count($dias_route),
      'rows_dias' => count($dias)
    ]
  ]);
 
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ]);
}