<?php
/* kardex_etl_zero.php
 * Kardex Bidireccional (ETL) — desde cero, directo a assistpro_etl_fc
 * - PDO seguro
 * - Filtros básicos
 * - Paginación
 * - Exportar CSV con mismos filtros
 * - Sin frameworks, sin dependencias externas
 */

// --- Config/errores
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','1');

// --- Conexión PDO (directo al ETL)
try {
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
    'root', '',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  die('Error de conexión a assistpro_etl_fc: ' . htmlspecialchars($e->getMessage()));
}

// --- Asegurar vista mínima si no existe (no altera tablas)
try {
  $exists = $pdo->prepare("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'v_kardex_doble_partida'"); 
  $exists->execute();
  if ((int)$exists->fetchColumn() === 0) {
    $pdo->exec("
      CREATE OR REPLACE VIEW v_kardex_doble_partida AS
      SELECT
        CAST(tc.id AS UNSIGNED)                    AS tx_id,
        STR_TO_DATE(tc.fecha, '%Y-%m-%d %H:%i:%s') AS fecha_hora,
        CASE
          WHEN UPPER(tm.nombre) IN ('ENTRADA')                 THEN 'ENTRADA'
          WHEN UPPER(tm.nombre) IN ('BAJA','SALIDA')           THEN 'SALIDA'
          WHEN UPPER(tm.nombre) IN ('ACOMODO','TRANSFERENCIA') THEN 'TRANSFERENCIA'
          WHEN UPPER(tm.nombre) LIKE 'AJUSTE%'                 THEN 'AJUSTE'
          ELSE UPPER(COALESCE(tm.nombre,'DESCONOCIDO'))
        END AS tipo_tx,
        NULL AS proyecto_id,
        tc.cve_articulo              AS producto_id,
        NULL AS uom,
        NULLIF(tc.cve_lote,'')       AS lote,
        NULL AS serie,
        NULL AS ajuste_id,
        NULL AS ajuste_motivo,
        NULL AS alm_ori_id, NULL AS ubi_ori_id,
        NULL AS alm_dst_id, NULL AS ubi_dst_id,
        CASE WHEN UPPER(tm.nombre) IN ('SALIDA','BAJA') THEN ABS(COALESCE(tc.cantidad,0))
             WHEN UPPER(tm.nombre) LIKE 'AJUSTE%' AND COALESCE(tc.cantidad,0) < 0 THEN ABS(tc.cantidad)
             WHEN UPPER(tm.nombre) IN ('TRANSFERENCIA','ACOMODO') THEN ABS(COALESCE(tc.cantidad,0))
             ELSE 0 END AS mov_ori,
        CASE WHEN UPPER(tm.nombre) IN ('ENTRADA') THEN ABS(COALESCE(tc.cantidad,0))
             WHEN UPPER(tm.nombre) LIKE 'AJUSTE%' AND COALESCE(tc.cantidad,0) > 0 THEN tc.cantidad
             WHEN UPPER(tm.nombre) IN ('TRANSFERENCIA','ACOMODO') THEN ABS(COALESCE(tc.cantidad,0))
             ELSE 0 END AS mov_dst,
        0 AS stock_ini_ori, 0 AS stock_fin_ori,
        0 AS stock_ini_dst, 0 AS stock_fin_dst,
        NULL AS referencia, NULL AS notas, NULL AS usuario_id,
        tc.empresa_id
      FROM stg_t_cardex tc
      LEFT JOIN stg_t_tipomovimiento tm
        ON tm.empresa_id = tc.empresa_id
       AND tm.id_TipoMovimiento = tc.id_TipoMovimiento
    ");
  }
} catch (Throwable $e) { /* no interrumpir UI */ }

// --- Captura de filtros
$empresa_id  = isset($_GET['empresa_id'])  && $_GET['empresa_id'] !== '' ? trim($_GET['empresa_id']) : null;
$producto_id = isset($_GET['producto_id']) && $_GET['producto_id'] !== '' ? trim($_GET['producto_id']) : null;
$lote        = isset($_GET['lote'])        && $_GET['lote'] !== '' ? trim($_GET['lote']) : null;
$tipo_tx     = isset($_GET['tipo_tx'])     && $_GET['tipo_tx'] !== '' ? (array)$_GET['tipo_tx'] : [];
$fini        = isset($_GET['fini'])        && $_GET['fini'] !== '' ? trim($_GET['fini']) : null;
$ffin        = isset($_GET['ffin'])        && $_GET['ffin'] !== '' ? trim($_GET['ffin']) : null;

$limit  = isset($_GET['limit'])  && ctype_digit((string)$_GET['limit'])  ? (int)$_GET['limit']  : 200;
$page   = isset($_GET['page'])   && ctype_digit((string)$_GET['page'])   ? (int)$_GET['page']   : 1;
$offset = max(0, ($page - 1) * $limit);

// --- WHERE dinámico
$where = []; $p = [];
if ($empresa_id !== null) { $where[] = 'empresa_id = :emp'; $p[':emp'] = $empresa_id; }
if ($producto_id !== null){ $where[] = 'producto_id LIKE :prod'; $p[':prod'] = '%' . $producto_id . '%'; }
if ($lote !== null)       { $where[] = 'lote LIKE :lote'; $p[':lote'] = '%' . $lote . '%'; }
if ($fini !== null)       { $where[] = 'fecha_hora >= :fini'; $p[':fini'] = $fini . ' 00:00:00'; }
if ($ffin !== null)       { $where[] = 'fecha_hora <= :ffin'; $p[':ffin'] = $ffin . ' 23:59:59'; }

if (!empty($tipo_tx)) {
  $ph = []; $i=0;
  foreach ($tipo_tx as $t){ $k=':t'.$i++; $ph[]=$k; $p[$k]=strtoupper(trim($t)); }
  $where[] = 'UPPER(tipo_tx) IN ('.implode(',', $ph).')';
}
$wsql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

// --- Export CSV
if (isset($_GET['export']) && strtolower($_GET['export'])==='csv') {
  $fname = 'kardex_'.date('Ymd_His').'.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  $out = fopen('php://output','w');
  fputcsv($out, ['tx_id','fecha_hora','tipo_tx','producto_id','lote','mov_ori','mov_dst','empresa_id']);
  $st = $pdo->prepare("SELECT tx_id,fecha_hora,tipo_tx,producto_id,lote,mov_ori,mov_dst,empresa_id
                       FROM v_kardex_doble_partida $wsql ORDER BY fecha_hora DESC, tx_id DESC");
  $st->execute($p);
  while ($r = $st->fetch(PDO::FETCH_NUM)) { fputcsv($out, $r); }
  fclose($out); exit;
}

// --- Totales y datos
$total = (int)($pdo->prepare("SELECT COUNT(*) FROM v_kardex_doble_partida $wsql")->execute($p) ? $pdo->query("SELECT COUNT(*) FROM v_kardex_doble_partida $wsql")->fetchColumn() : 0);
// PDO::prepare twice for safety with COUNT; alternate robust way:
$stCount = $pdo->prepare("SELECT COUNT(*) AS c FROM v_kardex_doble_partida $wsql");
$stCount->execute($p);
$total = (int)$stCount->fetchColumn();

$st = $pdo->prepare("
  SELECT tx_id, fecha_hora, tipo_tx, producto_id, lote, mov_ori, mov_dst, empresa_id
  FROM v_kardex_doble_partida
  $wsql
  ORDER BY fecha_hora DESC, tx_id DESC
  LIMIT :lim OFFSET :off
");
foreach ($p as $k=>$v){ $st->bindValue($k, $v); }
$st->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
$st->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$total_pages = $total > 0 ? (int)ceil($total / $limit) : 0;

// --- HTML simple
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kardex ETL (desde cero)</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Helvetica,Arial,sans-serif;margin:0;background:#f7f7f9}
.container{max-width:1200px;margin:24px auto;padding:16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px}
h1{margin:0 0 16px 0;font-size:20px}
form.filters{display:grid;grid-template-columns:repeat(6,minmax(120px,1fr));gap:8px;margin-bottom:12px}
form.filters input,form.filters select,form.filters button{padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
.badge{display:inline-block;margin-right:8px;padding:4px 8px;border-radius:999px;background:#eef;border:1px solid #99f;color:#003}
.table-wrap{overflow:auto;max-height:65vh;border:1px solid #e5e7eb;border-radius:8px}
table{border-collapse:collapse;width:100%;font-size:12px}
th,td{border-bottom:1px solid #eee;padding:6px 8px;text-align:left}
th{background:#fafafa;position:sticky;top:0}
.pager{display:flex;gap:8px;align-items:center;margin-top:10px}
.pager a,.pager span{padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;text-decoration:none;color:#111}
.pager .active{background:#eef}
.actions{display:flex;gap:8px;align-items:center;margin:8px 0 12px 0}
</style>
</head>
<body>
<div class="container">
  <h1>Kardex Bidireccional — ETL</h1>

  <div class="actions">
    <span class="badge">DB: assistpro_etl_fc</span>
    <span class="badge">Total: <?php echo (int)$total; ?></span>
    <span class="badge">Páginas: <?php echo (int)$total_pages; ?></span>
  </div>

  <form class="filters" method="get">
    <input type="text" name="empresa_id"  placeholder="Empresa" value="<?php echo htmlspecialchars($empresa_id ?? ''); ?>">
    <input type="text" name="producto_id" placeholder="Producto" value="<?php echo htmlspecialchars($producto_id ?? ''); ?>">
    <input type="text" name="lote"        placeholder="Lote/Serie" value="<?php echo htmlspecialchars($lote ?? ''); ?>">
    <select name="tipo_tx">
      <option value="">Tipo (todos)</option>
      <?php
        $tipos = ['ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE'];
        foreach ($tipos as $t) {
          $sel = (in_array($t, (array)$tipo_tx) ? ' selected' : '');
          echo '<option value="'.$t.'"'.$sel.'>'.$t.'</option>';
        }
      ?>
    </select>
    <input type="date" name="fini" value="<?php echo htmlspecialchars($fini ?? ''); ?>">
    <input type="date" name="ffin" value="<?php echo htmlspecialchars($ffin ?? ''); ?>">
    <button type="submit">Filtrar</button>
  </form>

  <div class="actions">
    <a href="?<?php
      $q = $_GET; $q['export']='csv'; echo htmlspecialchars(http_build_query($q));
    ?>" class="pager">Exportar CSV</a>
  </div>

  <div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>tx_id</th>
        <th>fecha_hora</th>
        <th>tipo_tx</th>
        <th>producto_id</th>
        <th>lote</th>
        <th>mov_ori</th>
        <th>mov_dst</th>
        <th>empresa_id</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8">Sin resultados para el filtro actual.</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['tx_id']); ?></td>
        <td><?php echo htmlspecialchars($r['fecha_hora']); ?></td>
        <td><?php echo htmlspecialchars($r['tipo_tx']); ?></td>
        <td><?php echo htmlspecialchars($r['producto_id']); ?></td>
        <td><?php echo htmlspecialchars($r['lote']); ?></td>
        <td><?php echo htmlspecialchars($r['mov_ori']); ?></td>
        <td><?php echo htmlspecialchars($r['mov_dst']); ?></td>
        <td><?php echo htmlspecialchars($r['empresa_id']); ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>

  <div class="pager">
    <?php
      $q = $_GET;
      for ($p=1; $p <= max(1,$total_pages); $p++) {
        $q['page'] = $p;
        $cls = ($p == $page) ? 'active' : '';
        echo '<a class="'.$cls.'" href="?'.htmlspecialchars(http_build_query($q)).'">'.$p.'</a>';
        if ($p>=20) { echo '<span>…</span>'; break; } // no saturar
      }
    ?>
  </div>
</div>
</body>
</html>
