<?php
require_once __DIR__ . '/../../app/db.php';
function ymd($s){ return preg_match('/^\d{4}-\d{2}-\d{2}$/',$s) ? $s : null; }

$desde = ymd($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
$hasta = ymd($_GET['hasta'] ?? '') ?: date('Y-m-d');
$maxDays=93;
if ((strtotime($hasta)-strtotime($desde))/86400 > $maxDays) {
  $desde=date('Y-m-d', strtotime("$hasta -$maxDays days")); $corto=true;
}

$fechadt = "COALESCE(
  STR_TO_DATE(k.fecha,'%Y-%m-%d %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%Y-%m-%d'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y')
)";
$params=['desde'=>$desde.' 00:00:00','hasta'=>$hasta.' 23:59:59'];

$sql = "
SELECT
  COALESCE(tm.nombre, CONCAT('Tipo ',k.id_TipoMovimiento)) AS tipo_tx,
  COUNT(*) AS total_movs,
  SUM(CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4))) AS neto
FROM stg_t_cardex k
LEFT JOIN stg_t_tipomovimiento tm
  ON tm.empresa_id = k.empresa_id AND tm.id_TipoMovimiento = k.id_TipoMovimiento
WHERE $fechadt BETWEEN :desde AND :hasta
GROUP BY COALESCE(tm.nombre, CONCAT('Tipo ',k.id_TipoMovimiento))
ORDER BY 1
";
$rows = db_all($sql,$params);
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><title>Movimientos por Tipo</title>
<style>body{font-family:system-ui;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f5f5f5}.note{font-size:12px;opacity:.8;margin:6px 0}</style>
</head><body>
<h2>Movimientos por Tipo</h2>
<form method="get">
  Desde: <input type="date" name="desde" value="<?=htmlspecialchars($desde)?>">
  Hasta: <input type="date" name="hasta" value="<?=htmlspecialchars($hasta)?>">
  <button>Filtrar</button>
</form>
<?php if (!empty($corto)): ?><div class="note">Periodo acotado a <?=$maxDays?> días.</div><?php endif; ?>
<table><thead><tr><th>Tipo</th><th>Total Movs</th><th>Neto</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['tipo_tx'])?></td>
  <td style="text-align:right"><?=number_format((float)$r['total_movs'])?></td>
  <td style="text-align:right"><?=number_format((float)$r['neto'],4)?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<p>Total filas: <?=count($rows)?></p>
</body></html>
