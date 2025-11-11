<?php
require_once __DIR__ . '/../../app/db.php';

$almacen = $_GET['almacen'] ?? '';
$params = [];

$hasView = db_val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'stg_vs_existencia'") > 0;

if ($hasView) {
  $sql = "
  SELECT
    e.empresa_id,
    u.cve_almac,
    al.nombre   AS des_almac,
    u.CodigoCSD AS bl_code,
    e.cve_articulo,
    a.des_articulo,
    e.cve_lote,
    CAST(NULLIF(e.Existencia,'') AS DECIMAL(18,4)) AS cantidad
  FROM stg_vs_existencia e
  LEFT JOIN stg_c_ubicacion u
    ON u.empresa_id=e.empresa_id AND u.idy_ubica=e.idy_ubica
  LEFT JOIN stg_c_almacenp al
    ON al.empresa_id=e.empresa_id AND al.clave=u.cve_almac
  LEFT JOIN stg_c_articulo a
    ON a.empresa_id=e.empresa_id AND a.cve_articulo=e.cve_articulo
  WHERE 1=1
  ";
  if ($almacen!==''){ $sql.=" AND u.cve_almac=:almacen"; $params['almacen']=$almacen; }
  $sql.=" ORDER BY u.cve_almac, bl_code, e.cve_articulo, e.cve_lote";
} else {
  $sql = "
  WITH ult AS (
    SELECT
      p.empresa_id,
      p.idy_ubica,
      u.CodigoCSD AS bl_code,
      u.cve_almac,
      p.cve_articulo,
      p.cve_lote,
      CAST(NULLIF(p.Cantidad,'') AS DECIMAL(18,4)) AS cantidad,
      COALESCE(
        STR_TO_DATE(p.fecha,'%Y-%m-%d %H:%i:%s'),
        STR_TO_DATE(p.fecha,'%Y-%m-%d'),
        STR_TO_DATE(p.fecha,'%d/%m/%Y %H:%i:%s'),
        STR_TO_DATE(p.fecha,'%d/%m/%Y')
      ) AS fecha_dt,
      ROW_NUMBER() OVER (PARTITION BY p.empresa_id,p.idy_ubica,p.cve_articulo,p.cve_lote ORDER BY
        COALESCE(
          STR_TO_DATE(p.fecha,'%Y-%m-%d %H:%i:%s'),
          STR_TO_DATE(p.fecha,'%Y-%m-%d'),
          STR_TO_DATE(p.fecha,'%d/%m/%Y %H:%i:%s'),
          STR_TO_DATE(p.fecha,'%d/%m/%Y')
        ) DESC
      ) AS rn
    FROM stg_t_invpiezas p
    LEFT JOIN stg_c_ubicacion u
      ON u.empresa_id=p.empresa_id AND u.idy_ubica=p.idy_ubica
  )
  SELECT
    x.empresa_id,
    x.cve_almac,
    al.nombre   AS des_almac,
    x.bl_code,
    x.cve_articulo,
    a.des_articulo,
    x.cve_lote,
    SUM(x.cantidad) AS cantidad
  FROM ult x
  LEFT JOIN stg_c_articulo a
    ON a.empresa_id=x.empresa_id AND a.cve_articulo=x.cve_articulo
  LEFT JOIN stg_c_almacenp al
    ON al.empresa_id=x.empresa_id AND al.clave=x.cve_almac
  WHERE x.rn=1
  ";
  if ($almacen!==''){ $sql.=" AND x.cve_almac=:almacen"; $params['almacen']=$almacen; }
  $sql.=" GROUP BY x.empresa_id,x.cve_almac,al.nombre,x.bl_code,x.cve_articulo,a.des_articulo,x.cve_lote
          ORDER BY x.cve_almac,x.bl_code,x.cve_articulo,x.cve_lote";
}

$rows = db_all($sql,$params);
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><title>Existencias por Ubicación</title>
<style>body{font-family:system-ui;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;font-size:12px}th{background:#f5f5f5}</style>
</head><body>
<h2>Existencias por Ubicación</h2>
<form method="get">
  Almacén: <input name="almacen" value="<?=htmlspecialchars($almacen)?>">
  <button>Filtrar</button>
</form>
<table><thead><tr>
  <th>Empresa</th><th>Almacén</th><th>Ubicación (BL)</th><th>Artículo</th><th>Descripción</th><th>Lote</th><th>Cantidad</th>
</tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['empresa_id'])?></td>
  <td><?=htmlspecialchars(($r['cve_almac']??'').' '.($r['des_almac']??''))?></td>
  <td><?=htmlspecialchars($r['bl_code'])?></td>
  <td><?=htmlspecialchars($r['cve_articulo'])?></td>
  <td><?=htmlspecialchars($r['des_articulo'])?></td>
  <td><?=htmlspecialchars($r['cve_lote'])?></td>
  <td style="text-align:right"><?=number_format((float)($r['cantidad']??0),4)?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<p>Total filas: <?=count($rows)?></p>
</body></html>
