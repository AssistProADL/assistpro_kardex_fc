<?php
require_once __DIR__ . '/../../app/db.php';

function ymd($s){ return preg_match('/^\d{4}-\d{2}-\d{2}$/',$s) ? $s : null; }

$articulo = $_GET['articulo'] ?? '';
$lote     = $_GET['lote'] ?? '';
$almacen  = $_GET['almacen'] ?? '';
$desde    = ymd($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
$hasta    = ymd($_GET['hasta'] ?? '') ?: date('Y-m-d');
$perPage  = max(1, min(1000, (int)($_GET['per_page'] ?? 200)));
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page-1)*$perPage;

/* Hard-cap de periodo: 93 días para evitar escaneos enormes */
$maxDays = 93;
if ((strtotime($hasta) - strtotime($desde))/86400 > $maxDays) {
  $desde = date('Y-m-d', strtotime("$hasta -$maxDays days"));
  $corto = true;
}

/* Normalización de fecha sin tocar la BD */
$fechadt = "COALESCE(
  STR_TO_DATE(k.fecha,'%Y-%m-%d %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%Y-%m-%d'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y %H:%i:%s'),
  STR_TO_DATE(k.fecha,'%d/%m/%Y')
)";

$params = [
  'desde' => $desde.' 00:00:00',
  'hasta' => $hasta.' 23:59:59',
  'limit' => (int)$perPage,
  'off'   => (int)$offset,
];

$sql = "
SELECT
  $fechadt AS fecha_dt,
  COALESCE(tm.nombre, CONCAT('Tipo ',k.id_TipoMovimiento)) AS tipo_tx,
  k.Referencia AS ref_ext,
  k.Cve_Almac  AS cve_almac,
  al.nombre    AS des_almac,
  k.cve_articulo, a.des_articulo,
  k.cve_lote, l.Caducidad AS caducidad,
  k.origen, k.destino,
  CASE WHEN CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4)) >= 0
       THEN CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4)) ELSE 0 END AS entrada,
  CASE WHEN CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4)) < 0
       THEN ABS(CAST(NULLIF(k.cantidad,'') AS DECIMAL(18,4))) ELSE 0 END AS salida,
  CAST(NULLIF(k.ajuste,'0') AS DECIMAL(18,4)) AS ajuste,
  k.cve_usuario
FROM t_cardex k
LEFT JOIN c_articulo a
  ON a.empresa_id = k.empresa_id AND a.cve_articulo = k.cve_articulo
LEFT JOIN c_lotes l
  ON l.empresa_id = k.empresa_id AND l.cve_articulo = k.cve_articulo AND l.Lote = k.cve_lote
LEFT JOIN c_almacenp al
  ON al.empresa_id = k.empresa_id AND al.clave = k.Cve_Almac
LEFT JOIN t_tipomovimiento tm
  ON tm.empresa_id = k.empresa_id AND tm.id_TipoMovimiento = k.id_TipoMovimiento
WHERE 1=1
  AND $fechadt BETWEEN :desde AND :hasta
";
if ($articulo!==''){ $sql.=" AND k.cve_articulo = :articulo"; $params['articulo']=$articulo; }
if ($lote!==''){     $sql.=" AND k.cve_lote     = :lote";     $params['lote']=$lote; }
if ($almacen!==''){  $sql.=" AND k.Cve_Almac    = :almacen";  $params['almacen']=$almacen; }

$sql .= " ORDER BY fecha_dt ASC LIMIT :limit OFFSET :off";
$rows = dbq($sql,$params)->fetchAll();
$hasMore = count($rows)===$perPage;
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><title>Kardex Detalle (rápido)</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:system-ui;margin:16px}table{border-collapse:collapse;width:100%}
th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f5f5f5}
.filters{margin-bottom:10px}input,select{padding:6px;font-size:12px}
.note{font-size:12px;opacity:.8;margin:6px 0}.pager a{margin-right:8px}
</style></head><body>
<h2>Kardex Detalle – Foam Creations</h2>
<form class="filters" method="get">
  Artículo: <input name="articulo" value="<?=htmlspecialchars($articulo)?>">
  Lote/Serie: <input name="lote" value="<?=htmlspecialchars($lote)?>">
  Almacén: <input name="almacen" value="<?=htmlspecialchars($almacen)?>">
  Desde: <input type="date" name="desde" value="<?=htmlspecialchars($desde)?>">
  Hasta: <input type="date" name="hasta" value="<?=htmlspecialchars($hasta)?>">
  <input type="number" name="per_page" min="1" max="1000" value="<?=htmlspecialchars($perPage)?>" style="width:90px"> filas/pág
  <button>Filtrar</button>
</form>
<?php if (!empty($corto)): ?><div class="note">Periodo acotado a <?=$maxDays?> días.</div><?php endif; ?>
<table><thead><tr>
  <th>Fecha/Hora</th><th>Tipo</th><th>Ref</th><th>Almacén</th><th>Artículo</th><th>Descripción</th>
  <th>Lote</th><th>Caducidad</th><th>Origen</th><th>Destino</th><th>Entrada</th><th>Salida</th><th>Ajuste</th><th>Usuario</th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['fecha_dt'])?></td>
  <td><?=htmlspecialchars($r['tipo_tx'])?></td>
  <td><?=htmlspecialchars($r['ref_ext'])?></td>
  <td><?=htmlspecialchars(($r['cve_almac']??'').' '.($r['des_almac']??''))?></td>
  <td><?=htmlspecialchars($r['cve_articulo'])?></td>
  <td><?=htmlspecialchars($r['des_articulo'])?></td>
  <td><?=htmlspecialchars($r['cve_lote'])?></td>
  <td><?=htmlspecialchars($r['caducidad'])?></td>
  <td><?=htmlspecialchars($r['origen'])?></td>
  <td><?=htmlspecialchars($r['destino'])?></td>
  <td style="text-align:right"><?=number_format((float)$r['entrada'],4)?></td>
  <td style="text-align:right"><?=number_format((float)$r['salida'],4)?></td>
  <td style="text-align:right"><?=number_format((float)($r['ajuste']??0),4)?></td>
  <td><?=htmlspecialchars($r['cve_usuario'])?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<div class="note">Página <?=number_format($page)?><?= $hasMore ? '' : ' (última)' ?> · Máx <?=$perPage?> filas.</div>
<div class="pager">
  <?php if ($page>1): ?>
    <a href="?<?=http_build_query(array_merge($_GET,['page'=>$page-1]))?>">« Anterior</a>
  <?php endif; ?>
  <?php if ($hasMore): ?>
    <a href="?<?=http_build_query(array_merge($_GET,['page'=>$page+1]))?>">Siguiente »</a>
  <?php endif; ?>
</div>
</body></html>
