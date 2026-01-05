<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_GET['action'] ?? 'kpi';
if($action!=='kpi'){ echo json_encode(['error'=>'Acción no válida']); exit; }

$sql = "
SELECT
  c.IdEmpresa,
  ap.nombre AS Empresa,

  COUNT(*) AS docs_pendientes,
  SUM(CASE WHEN c.FechaVence IS NOT NULL AND DATE(c.FechaVence) < CURDATE() THEN 1 ELSE 0 END) AS docs_vencidos,

  ROUND(SUM(IFNULL(c.Saldo,0)),2) AS saldo_total,
  ROUND(SUM(IFNULL(ab.Abonos,0)),2) AS abonos_total,
  ROUND(SUM(IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0)),2) AS saldo_neto,

  ROUND(SUM(CASE
    WHEN c.FechaVence IS NULL THEN 0
    WHEN DATEDIFF(CURDATE(), DATE(c.FechaVence)) <= 30 THEN (IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0))
    ELSE 0
  END),2) AS aging_0_30,

  ROUND(SUM(CASE
    WHEN c.FechaVence IS NULL THEN 0
    WHEN DATEDIFF(CURDATE(), DATE(c.FechaVence)) BETWEEN 31 AND 60 THEN (IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0))
    ELSE 0
  END),2) AS aging_31_60,

  ROUND(SUM(CASE
    WHEN c.FechaVence IS NULL THEN 0
    WHEN DATEDIFF(CURDATE(), DATE(c.FechaVence)) BETWEEN 61 AND 90 THEN (IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0))
    ELSE 0
  END),2) AS aging_61_90,

  ROUND(SUM(CASE
    WHEN c.FechaVence IS NULL THEN 0
    WHEN DATEDIFF(CURDATE(), DATE(c.FechaVence)) > 90 THEN (IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0))
    ELSE 0
  END),2) AS aging_90p

FROM Cobranza c
LEFT JOIN c_almacenp ap ON ap.clave = c.IdEmpresa
LEFT JOIN (
  SELECT
    Documento,
    IdEmpresa,
    SUM(CASE WHEN IFNULL(Cancelada,0)=0 THEN IFNULL(Abono,0) ELSE 0 END) AS Abonos
  FROM DetalleCob
  GROUP BY Documento, IdEmpresa
) ab ON ab.Documento = c.Documento AND ab.IdEmpresa = c.IdEmpresa

WHERE IFNULL(c.Status,0) = 1
  AND IFNULL(c.Saldo,0) > 0

GROUP BY c.IdEmpresa, ap.nombre
ORDER BY saldo_neto DESC
";

echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
