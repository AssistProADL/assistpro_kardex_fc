<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$IdEmpresa = $_POST['IdEmpresa'] ?? $_POST['almacen'] ?? $_GET['IdEmpresa'] ?? $_GET['almacen'] ?? '';
$IdEmpresa = trim((string)$IdEmpresa);

if ($IdEmpresa==='') {
  echo json_encode(['error'=>'Almacén (IdEmpresa) requerido']);
  exit;
}

/* KPIs */
$kpis = [];

/* Rutas activas (con asignación) */
$st = $pdo->prepare("SELECT COUNT(DISTINCT IdRuta) FROM relclirutas WHERE IdEmpresa = :emp");
$st->execute([':emp'=>$IdEmpresa]);
$kpis['rutas_activas'] = (int)$st->fetchColumn();

/* Clientes asignados */
$st = $pdo->prepare("SELECT COUNT(DISTINCT IdCliente) FROM relclirutas WHERE IdEmpresa = :emp");
$st->execute([':emp'=>$IdEmpresa]);
$kpis['clientes_asignados'] = (int)$st->fetchColumn();

/* Clientes sin ruta (basado en c_cliente) */
$st = $pdo->prepare("
  SELECT COUNT(*)
  FROM c_cliente c
  LEFT JOIN relclirutas rc
    ON rc.IdEmpresa = c.IdEmpresa
   AND rc.IdCliente = CAST(c.id_cliente AS CHAR)
  WHERE c.IdEmpresa = :emp
    AND rc.IdCliente IS NULL
");
$st->execute([':emp'=>$IdEmpresa]);
$kpis['clientes_sin_ruta'] = (int)$st->fetchColumn();

/* Cobertura geo (asignados con GPS) */
$st = $pdo->prepare("
  SELECT ROUND(
    SUM(CASE WHEN c.latitud IS NOT NULL AND c.longitud IS NOT NULL AND c.latitud<>'' AND c.longitud<>'' THEN 1 ELSE 0 END)
    / NULLIF(COUNT(*),0) * 100, 1
  )
  FROM c_cliente c
  JOIN relclirutas rc
    ON rc.IdEmpresa = c.IdEmpresa
   AND rc.IdCliente = CAST(c.id_cliente AS CHAR)
  WHERE c.IdEmpresa = :emp
");
$st->execute([':emp'=>$IdEmpresa]);
$kpis['cobertura_geo'] = (float)($st->fetchColumn() ?: 0);

/* Resumen por ruta */
$sql = "
SELECT
  r.ID_Ruta,
  r.descripcion AS ruta,
  COUNT(DISTINCT rc.IdCliente) clientes,
  COALESCE((
    SELECT COUNT(DISTINCT c2.CodigoPostal)
    FROM c_cliente c2
    JOIN relclirutas rc2
      ON rc2.IdEmpresa = c2.IdEmpresa
     AND rc2.IdCliente = CAST(c2.id_cliente AS CHAR)
    WHERE rc2.IdEmpresa = :emp
      AND rc2.IdRuta = r.ID_Ruta
  ),0) AS cps,
  ROUND((
    SELECT SUM(CASE WHEN c3.latitud IS NOT NULL AND c3.longitud IS NOT NULL AND c3.latitud<>'' AND c3.longitud<>'' THEN 1 ELSE 0 END)
           / NULLIF(COUNT(*),0) * 100
    FROM c_cliente c3
    JOIN relclirutas rc3
      ON rc3.IdEmpresa = c3.IdEmpresa
     AND rc3.IdCliente = CAST(c3.id_cliente AS CHAR)
    WHERE rc3.IdEmpresa = :emp
      AND rc3.IdRuta = r.ID_Ruta
  ),1) geo_pct
FROM t_ruta r
LEFT JOIN relclirutas rc
  ON rc.IdRuta = r.ID_Ruta
 AND rc.IdEmpresa = :emp
GROUP BY r.ID_Ruta, r.descripcion
ORDER BY r.descripcion
";
$st = $pdo->prepare($sql);
$st->execute([':emp'=>$IdEmpresa]);
$rutas = [];
while($r = $st->fetch(PDO::FETCH_ASSOC)){
  $estado = 'rojo';
  if ((int)$r['clientes'] > 0) $estado = ((float)$r['geo_pct'] < 100 ? 'amarillo' : 'verde');
  $r['estado'] = $estado;
  $rutas[] = $r;
}

/* Distribución por día (desde reldaycli, si existe) */
$dias = [];
try {
  $st = $pdo->prepare("
    SELECT
      'Lu' dia, SUM(CASE WHEN Lu=1 THEN 1 ELSE 0 END) clientes, COUNT(DISTINCT CASE WHEN Lu=1 THEN Cve_Ruta END) rutas
    FROM reldaycli WHERE Cve_Almac = :emp
    UNION ALL
    SELECT 'Ma', SUM(CASE WHEN Ma=1 THEN 1 ELSE 0 END), COUNT(DISTINCT CASE WHEN Ma=1 THEN Cve_Ruta END) FROM reldaycli WHERE Cve_Almac = :emp
    UNION ALL
    SELECT 'Mi', SUM(CASE WHEN Mi=1 THEN 1 ELSE 0 END), COUNT(DISTINCT CASE WHEN Mi=1 THEN Cve_Ruta END) FROM reldaycli WHERE Cve_Almac = :emp
    UNION ALL
    SELECT 'Ju', SUM(CASE WHEN Ju=1 THEN 1 ELSE 0 END), COUNT(DISTINCT CASE WHEN Ju=1 THEN Cve_Ruta END) FROM reldaycli WHERE Cve_Almac = :emp
    UNION ALL
    SELECT 'Vi', SUM(CASE WHEN Vi=1 THEN 1 ELSE 0 END), COUNT(DISTINCT CASE WHEN Vi=1 THEN Cve_Ruta END) FROM reldaycli WHERE Cve_Almac = :emp
    UNION ALL
    SELECT 'Sa', SUM(CASE WHEN Sa=1 THEN 1 ELSE 0 END), COUNT(DISTINCT CASE WHEN Sa=1 THEN Cve_Ruta END) FROM reldaycli WHERE Cve_Almac = :emp
    UNION ALL
    SELECT 'Do', SUM(CASE WHEN Do=1 THEN 1 ELSE 0 END), COUNT(DISTINCT CASE WHEN Do=1 THEN Cve_Ruta END) FROM reldaycli WHERE Cve_Almac = :emp
  
  ");
  $st->execute([':emp'=>$IdEmpresa]);
  $dias = $st->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){
  $dias = [];
}

echo json_encode([
  'kpis'=>$kpis,
  'rutas'=>$rutas,
  'dias'=>$dias
]);
