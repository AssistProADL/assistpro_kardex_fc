<?php
require_once __DIR__ . '/../../app/db.php';
function ymd($s){ return preg_match('/^\d{4}-\d{2}-\d{2}$/',$s) ? $s : null; }

$almacen = $_GET['almacen'] ?? '';
$desde   = ymd($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
$hasta   = ymd($_GET['hasta'] ?? '') ?: date('Y-m-d');
$limit   = max(1, min(2000, (int)($_GET['limit'] ?? 1000)));

$maxDays = 93;
if ((strtotime($hasta)-strtotime($desde))/86400 > $maxDays) {
  $desde = date('Y-m-d', strtotime("$hasta -$maxDays days")); $corto = true;
}

$fechadt = "COALESCE(
  STR_TO_DATE(k.fecha,'%Y-%m-%d %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%Y-%m-%d'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y')
)";

$params=['desde'=>$desde.' 00:00:00','hasta'=>$hasta.' 23:59:59','lim'=>$limit];
$sql = "
SELECT
  k.empresa_id,
  k.Cve_Almac AS cve_almac,
  al.nombre   AS des_almac,
  k.cve_articulo, a.des_articulo,
  k.cve_lote,
  SUM(CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4))) AS saldo_neto
FROM stg_t_cardex k
LEFT JOIN stg_c_articulo a
  ON a.empresa_id = k.empresa_id AND a.cve_articulo = k.cve_articulo
LEFT JOIN stg_c_almacenp al
  ON al.empresa_id = k.empresa_id AND al.clave = k.Cve_Almac
WHERE $fechadt BETWEEN :desde AND :hasta
";
if ($almacen!==''){ $sql.=" AND k.Cve_Almac=:alm"; $params['alm']=$almacen; }
$sql .= "
GROUP BY k.empresa_id,k.Cve_Almac,al.nombre,k.cve_articulo,a.des_articulo,k.cve_lote
ORDER BY k.empresa_id,k.Cve_Almac,k.cve_articulo,k.cve_lote
LIMIT :lim
";
$rows = dbq($sql,$params)->fetchAll();
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><title>Kardex Resumen (rápido)</title>
<style>body{font-family:system-ui;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f5f5f5}.note{font-size:12px;opacity:.8;margin:6px 0}</style>
</head><body>
<h2>Kardex Resumen – Saldos Neto</h2>
<form method="get">
  Almacén: <input name="almacen" value="<?=htmlspecialchars($almacen)?>">
  Desde: <input type="date" name="desde" value="<?=htmlspecialchars($desde)?>">
  Hasta: <input type="date" name="hasta" value="<?=htmlspecialchars($hasta)?>">
  <input type="number" name="limit" min="1" max="2000" value="<?=htmlspecialchars($limit)?>" style="width:90px"> filas máx
  <button>Filtrar</button>
</form>
<?php if (!empty($corto)): ?><div class="note">Periodo acotado a <?=$maxDays?> días.</div><?php endif; ?>
<table><thead><tr>
<th>Empresa</th><th>Almacén</th><th>Artículo</th><th>Descripción</th><th>Lote</th><th>Saldo Neto</th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['empresa_id'])?></td>
  <td><?=htmlspecialchars(($r['cve_almac'] ?? '').' '.($r['des_almac'] ?? ''))?></td>
  <td><?=htmlspecialchars($r['cve_articulo'])?></td>
  <td><?=htmlspecialchars($r['des_articulo'])?></td>
  <td><?=htmlspecialchars($r['cve_lote'])?></td>
  <td style="text-align:right"><?=number_format((float)$r['saldo_neto'],4)?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<p>Total filas: <?=count($rows)?> (límite <?=number_format($limit)?>)</p>
</body></html>
