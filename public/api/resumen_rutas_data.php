<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

header('Content-Type: application/json');

$almacen = $_POST['almacen'] ?? '';
if ($almacen === '') {
  echo json_encode(['error'=>'Almacén requerido']);
  exit;
}

/* KPIs */
$kpis = [];

/* Rutas activas */
$kpis['rutas_activas'] = (int)$pdo
  ->query("SELECT COUNT(DISTINCT Cve_Ruta) FROM reldaycli WHERE Cve_Almac = $almacen")
  ->fetchColumn();

/* Clientes asignados */
$kpis['clientes_asignados'] = (int)$pdo
  ->query("SELECT COUNT(DISTINCT Id_Destinatario) FROM reldaycli WHERE Cve_Almac = $almacen")
  ->fetchColumn();

/* Cobertura geo */
$kpis['cobertura_geo'] = (float)$pdo
  ->query("
    SELECT ROUND(
      SUM(CASE WHEN d.latitud IS NOT NULL AND d.longitud IS NOT NULL THEN 1 ELSE 0 END)
      / COUNT(*) * 100, 1
    )
    FROM reldaycli rd
    JOIN c_destinatarios d ON d.id_destinatario = rd.Id_Destinatario
    WHERE rd.Cve_Almac = $almacen
  ")->fetchColumn();

/* Resumen por ruta */
$sql = "
SELECT
  r.cve_ruta,
  COUNT(DISTINCT rd.Id_Destinatario) clientes,
  CONCAT_WS('',
    IF(SUM(rd.Lu)>0,'L',''),
    IF(SUM(rd.Ma)>0,'M',''),
    IF(SUM(rd.Mi)>0,'M',''),
    IF(SUM(rd.Ju)>0,'J',''),
    IF(SUM(rd.Vi)>0,'V',''),
    IF(SUM(rd.Sa)>0,'S','')
  ) dias,
  ROUND(
    SUM(CASE WHEN d.latitud IS NOT NULL AND d.longitud IS NOT NULL THEN 1 ELSE 0 END)
    / COUNT(*) * 100, 1
  ) geo_pct
FROM reldaycli rd
JOIN t_ruta r ON r.ID_Ruta = rd.Cve_Ruta
JOIN c_destinatarios d ON d.id_destinatario = rd.Id_Destinatario
WHERE rd.Cve_Almac = :alm
GROUP BY rd.Cve_Ruta
ORDER BY r.cve_ruta
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':alm'=>$almacen]);
$rutas = [];

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $r['estado'] = ($r['clientes']==0) ? 'rojo' :
                 (($r['geo_pct']<100 || $r['dias']=='') ? 'amarillo' : 'verde');
  $rutas[] = $r;
}

echo json_encode([
  'kpis'  => $kpis,
  'rutas' => $rutas
]);
