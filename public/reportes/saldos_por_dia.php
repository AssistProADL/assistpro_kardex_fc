<?php
require_once __DIR__ . '/../../app/db.php';

$almacen = $_GET['almacen'] ?? '';
$params = [];

$sql = "
SELECT
  k.empresa_id,
  k.Cve_Almac AS cve_almac,
  al.des_almac,
  k.cve_articulo,
  a.des_articulo,
  k.cve_lote,
  SUM(CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4))) AS saldo_neto
FROM stg_t_cardex k
LEFT JOIN stg_c_articulo a
  ON a.empresa_id = k.empresa_id AND a.cve_articulo = k.cve_articulo
LEFT JOIN stg_c_almacen al
  ON al.empresa_id = k.empresa_id AND al.cve_almac = k.Cve_Almac
WHERE 1=1
";
if ($almacen !== '') { $sql .= " AND k.Cve_Almac = :almacen"; $params['almacen'] = $almacen; }

$sql .= "
GROUP BY k.empresa_id,k.Cve_Almac,al.des_almac,k.cve_articulo,a.des_articulo,k.cve_lote
ORDER BY k.empresa_id,k.Cve_Almac,k.cve_articulo,k.cve_lote
";

$rows = db_all($sql,$params);
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><title>Kardex Resumen (Saldos)</title>
<style>body{font-family:system-ui;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f5f5f5}</style>
</head><body>
<h2>Kardex Resumen – Saldos Neto</h2>
<form method="get">Almacén: <input name="almacen" value="<?=htmlspecialchars($almacen)?>"><button>Filtrar</button></form>
<table><thead><tr>
<th>Empresa</th><th>Almacén</th><th>Artículo</th><th>Descripción</th><th>Lote</th><th>Saldo Neto</th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['empresa_id'])?></td>
  <td><?=htmlspecialchars(($r['cve_almac'] ?? '').' '.$r['des_almac'])?></td>
  <td><?=htmlspecialchars($r['cve_articulo'])?></td>
  <td><?=htmlspecialchars($r['des_articulo'])?></td>
  <td><?=htmlspecialchars($r['cve_lote'])?></td>
  <td style="text-align:right"><?=number_format((float)$r['saldo_neto'],4)?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<p>Total filas: <?=count($rows)?></p>
</body></html>
