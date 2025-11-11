<?php
require_once __DIR__ . '/../../app/db.php';
function ymd($s){ return preg_match('/^\d{4}-\d{2}-\d{2}$/',$s) ? $s : null; }

$articulo = $_GET['articulo'] ?? '';
$lote     = $_GET['lote'] ?? '';
$desde    = ymd($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
$hasta    = ymd($_GET['hasta'] ?? '') ?: date('Y-m-d');
$limit    = max(1, min(2000, (int)($_GET['limit'] ?? 500)));

$maxDays = 93;
if ((strtotime($hasta) - strtotime($desde))/86400 > $maxDays) {
  $desde = date('Y-m-d', strtotime("$hasta -$maxDays days"));
  $corto = true;
}

$fechadt = "COALESCE(
  STR_TO_DATE(k.fecha,'%Y-%m-%d %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%Y-%m-%d'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y')
)";

$params = ['desde'=>$desde.' 00:00:00','hasta'=>$hasta.' 23:59:59','lim'=>$limit];
$sql = "
SELECT
  $fechadt AS fecha_dt,
  k.cve_articulo, a.des_articulo,
  k.cve_lote,
  k.Cve_Almac AS cve_almac,
  al.nombre   AS des_almac,
  k.origen, k.destino,
  COALESCE(tm.nombre, CONCAT('Tipo ',k.id_TipoMovimiento)) AS tipo_tx,
  k.Referencia AS ref_ext,
  CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4)) AS cantidad
FROM stg_t_cardex k
LEFT JOIN stg_c_articulo a
  ON a.empresa_id=k.empresa_id AND a.cve_articulo=k.cve_articulo
LEFT JOIN stg_c_almacenp al
  ON al.empresa_id=k.empresa_id AND al.clave=k.Cve_Almac
LEFT JOIN stg_t_tipomovimiento tm
  ON tm.empresa_id=k.empresa_id AND tm.id_TipoMovimiento=k.id_TipoMovimiento
WHERE 1=1
  AND $fechadt BETWEEN :desde AND :hasta
";
if ($articulo!==''){ $sql.=" AND k.cve_articulo=:art"; $params['art']=$articulo; }
if ($lote!==''){     $sql.=" AND k.cve_lote=:lot";     $params['lot']=$lote; }
$sql.=" ORDER BY fecha_dt ASC LIMIT :lim";

$rows = dbq($sql,$params)->fetchAll();
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><title>Trazabilidad por Lote</title>
<style>body{font-family:system-ui;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f5f5f5}.note{font-size:12px;opacity:.8;margin:6px 0}</style>
</head><body>
<h2>Trazabilidad – Artículo/Lote</h2>
<form method="get">
  Artículo: <input name="articulo" value="<?=htmlspecialchars($articulo)?>">
  Lote: <input name="lote" value="<?=htmlspecialchars($lote)?>">
  Desde: <input type="date" name="desde" value="<?=htmlspecialchars($desde)?>">
  Hasta: <input type="date" name="hasta" value="<?=htmlspecialchars($hasta)?>">
  <input type="number" name="limit" min="1" max="2000" value="<?=htmlspecialchars($limit)?>" style="width:90px"> filas máx
  <button>Filtrar</button>
</form>
<?php if (!empty($corto)): ?><div class="note">Periodo acotado a <?=$maxDays?> días.</div><?php endif; ?>
<table><thead><tr>
  <th>Fecha</th><th>Almacén</th><th>Origen</th><th>Destino</th><th>Tipo</th><th>Referencia</th><th>Cantidad</th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['fecha_dt'])?></td>
  <td><?=htmlspecialchars(($r['cve_almac']??'').' '.($r['des_almac']??''))?></td>
  <td><?=htmlspecialchars($r['origen'])?></td>
  <td><?=htmlspecialchars($r['destino'])?></td>
  <td><?=htmlspecialchars($r['tipo_tx'])?></td>
  <td><?=htmlspecialchars($r['ref_ext'])?></td>
  <td style="text-align:right"><?=number_format((float)$r['cantidad'],4)?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<p>Total filas: <?=count($rows)?></p>
</body></html>
