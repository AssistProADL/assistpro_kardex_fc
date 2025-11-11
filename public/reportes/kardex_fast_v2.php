<?php
/* kardex.php (fast v2)
 * - No consulta hasta presionar Filtrar (run=1)
 * - Si no hay fechas, impone últimos 7 días por defecto (para acotar)
 * - Usa LIMIT+1 para paginación (sin COUNT(*))
 * - ?debug=1 muestra el WHERE y parámetros
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','1');

try {
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
    'root', '',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]
  );
} catch (Throwable $e) {
  die('Error de conexión a assistpro_etl_fc: ' . htmlspecialchars($e->getMessage()));
}

/* ===== Captura filtros ===== */
$empresa_id   = $_GET['empresa_id']   ?? '';
$producto_id  = $_GET['producto_id']  ?? '';
$lote         = $_GET['lote']         ?? '';
$tipo_tx      = $_GET['tipo_tx']      ?? '';
$alm_codigo   = $_GET['alm_codigo']   ?? '';
$proyecto     = $_GET['proyecto']     ?? '';
$fini         = $_GET['fini']         ?? '';
$ffin         = $_GET['ffin']         ?? '';
$debug        = isset($_GET['debug']) && $_GET['debug']=='1';

$limit  = (isset($_GET['limit']) && ctype_digit((string)$_GET['limit'])) ? (int)$_GET['limit'] : 100;
$page   = (isset($_GET['page'])  && ctype_digit((string)$_GET['page']))  ? (int)$_GET['page']  : 1;
$offset = max(0, ($page-1) * $limit);

/* Solo ejecutar consultas si el usuario dio click a Filtrar (run=1) */
$run = isset($_GET['run']) && $_GET['run'] == '1';

/* ===== WHERE ===== */
$where = []; $p = []; $notes = [];

if ($empresa_id !== '') { $where[] = 'empresa_id = :emp'; $p[':emp'] = $empresa_id; }
if ($producto_id !== ''){ $where[] = 'producto_id LIKE :prod'; $p[':prod'] = '%'.$producto_id.'%'; }
if ($lote !== '')       { $where[] = 'lote LIKE :lote'; $p[':lote'] = '%'.$lote.'%'; }
if ($alm_codigo !== '') { $where[] = 'alm_codigo LIKE :alm'; $p[':alm'] = '%'.$alm_codigo.'%'; }
if ($tipo_tx !== '')    { $where[] = 'UPPER(tipo_tx) = :ttx'; $p[':ttx'] = strtoupper($tipo_tx); }
if ($proyecto !== '')   {
  $where[] = '(CAST(proyecto_id AS CHAR) = :prjid OR proyecto_clave LIKE :prj OR proyecto_nombre LIKE :prj)';
  $p[':prjid'] = $proyecto;
  $p[':prj']   = '%'.$proyecto.'%';
}

/* Fecha por defecto: últimos 7 días si no se indicó nada */
if ($run && $fini === '' && $ffin === '') {
  $fini = date('Y-m-d', strtotime('-7 days'));
  $ffin = date('Y-m-d');
  $notes[] = "No indicaste fechas: se aplicó rango por defecto (últimos 7 días).";
}
if ($fini !== '') { $where[] = 'fecha_hora >= :fini'; $p[':fini'] = $fini.' 00:00:00'; }
if ($ffin !== '') { $where[] = 'fecha_hora <= :ffin'; $p[':ffin'] = $ffin.' 23:59:59'; }

$wsql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ===== CSV ===== */
if ($run && isset($_GET['export']) && strtolower($_GET['export'])==='csv') {
  $fname = 'kardex_'.date('Ymd_His').'.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  $out = fopen('php://output','w');
  fputcsv($out, ['tx_id','fecha_hora','tipo_tx','producto_id','producto_nombre','lote','mov_ori','mov_dst','alm_codigo','alm_nombre','zona_nombre','empresa_id','proyecto_id','proyecto_clave','proyecto_nombre','Referencia']);
  $st = $pdo->prepare("
    SELECT tx_id,fecha_hora,tipo_tx,producto_id,producto_nombre,lote,mov_ori,mov_dst,alm_codigo,alm_nombre,zona_nombre,empresa_id,proyecto_id,proyecto_clave,proyecto_nombre,Referencia
    FROM v_kardex_doble_partida $wsql
    ORDER BY fecha_hora DESC, tx_id DESC
  ");
  $st->execute($p);
  while ($r = $st->fetch(PDO::FETCH_NUM)) fputcsv($out, $r);
  fclose($out); exit;
}

/* ===== Datos (sin COUNT): limit+1 para saber si hay más ===== */
$rows = []; $has_prev = false; $has_next = false; $error_msg = '';
if ($run) {
  try {
    $st = $pdo->prepare("
      SELECT tx_id, fecha_hora, tipo_tx, producto_id, producto_nombre, lote,
             mov_ori, mov_dst, alm_codigo, alm_nombre, zona_nombre,
             empresa_id, proyecto_id, proyecto_clave, proyecto_nombre, Referencia
      FROM v_kardex_doble_partida
      $wsql
      ORDER BY fecha_hora DESC, tx_id DESC
      LIMIT :lim OFFSET :off
    ");
    foreach ($p as $k=>$v) { $st->bindValue($k,$v); }
    $st->bindValue(':lim', (int)($limit+1), PDO::PARAM_INT);
    $st->bindValue(':off', (int)$offset,   PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll();
    $has_next = count($rows) > $limit;
    if ($has_next) array_pop($rows);
    $has_prev = $page > 1;
  } catch (Throwable $e) {
    $error_msg = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kardex ETL (rápido v2)</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Helvetica,Arial,sans-serif;margin:0;background:#f7f7f9}
.container{max-width:1400px;margin:24px auto;padding:16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px}
h1{margin:0 0 16px 0;font-size:20px}
form.filters{display:grid;grid-template-columns:repeat(8,minmax(120px,1fr));gap:8px;margin-bottom:12px}
form.filters input,form.filters select,form.filters button{padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
.badge{display:inline-block;margin-right:8px;padding:4px 8px;border-radius:999px;background:#eef;border:1px solid #99f;color:#003}
.table-wrap{overflow:auto;max-height:65vh;border:1px solid #e5e7eb;border-radius:8px}
table{border-collapse:collapse;width:100%;font-size:12px}
th,td{border-bottom:1px solid #eee;padding:6px 8px;text-align:left;white-space:nowrap}
th{background:#fafafa;position:sticky;top:0}
.pager{display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap}
.pager a,.pager span{padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;text-decoration:none;color:#111}
.pager .active{background:#eef}
.actions{display:flex;gap:8px;align-items:center;margin:8px 0 12px 0;flex-wrap:wrap}
.muted{color:#6b7280}
.debug{background:#fef3c7;border:1px dashed #f59e0b;border-radius:8px;padding:8px;margin-bottom:8px;font-size:12px}
.err{background:#fee2e2;border:1px solid #ef4444;border-radius:8px;padding:8px;margin-bottom:8px}
.note{background:#ecfeff;border:1px solid #06b6d4;border-radius:8px;padding:8px;margin-bottom:8px}
</style>
</head>
<body>
<div class="container">
  <h1>Kardex Bidireccional — ETL (carga rápida v2)</h1>

  <?php if($debug): ?>
    <div class="debug">
      <div><b>WHERE:</b> <?=htmlspecialchars($wsql ?: '(none)')?></div>
      <div><b>Params:</b> <?=htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE))?></div>
    </div>
  <?php endif; ?>

  <?php foreach ($notes as $n): ?>
    <div class="note"><?=htmlspecialchars($n)?></div>
  <?php endforeach; ?>

  <?php if($error_msg): ?>
    <div class="err"><b>Error SQL:</b> <?=htmlspecialchars($error_msg)?></div>
  <?php endif; ?>

  <div class="actions">
    <span class="badge">DB: assistpro_etl_fc</span>
    <?php if(!$run): ?>
      <span class="muted">Carga inicial ligera. Ajusta filtros y presiona <b>Filtrar</b>.</span>
    <?php else: ?>
      <a href="?<?php $q=$_GET; $q['export']='csv'; echo htmlspecialchars(http_build_query($q)); ?>" class="pager">Exportar CSV</a>
    <?php endif; ?>
  </div>

  <form class="filters" method="get">
    <input type="hidden" name="run" value="1">
    <input type="text" name="empresa_id"  placeholder="Empresa" value="<?php echo htmlspecialchars($empresa_id); ?>">
    <input type="text" name="producto_id" placeholder="Producto" value="<?php echo htmlspecialchars($producto_id); ?>">
    <input type="text" name="lote"        placeholder="Lote/Serie" value="<?php echo htmlspecialchars($lote); ?>">
    <select name="tipo_tx">
      <option value="">Tipo (todos)</option>
      <?php foreach (['ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE'] as $t) {
        $sel = ($tipo_tx === $t) ? ' selected' : '';
        echo '<option value="'.$t.'"' . $sel . '>'.$t.'</option>';
      } ?>
    </select>
    <input type="text" name="alm_codigo" placeholder="Almacén/Zona" value="<?php echo htmlspecialchars($alm_codigo); ?>">
    <input type="text" name="proyecto"   placeholder="Proyecto (id/clave/nombre)" value="<?php echo htmlspecialchars($proyecto); ?>">
    <input type="date" name="fini" value="<?php echo htmlspecialchars($fini); ?>">
    <input type="date" name="ffin" value="<?php echo htmlspecialchars($ffin); ?>">
    <button type="submit">Filtrar</button>
  </form>

  <?php if(!$run): ?>
    <div class="muted">Sin consulta ejecutada. Esta vista no carga datos hasta que apliques filtros.</div>
  <?php else: ?>
    <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>tx_id</th><th>fecha_hora</th><th>tipo_tx</th>
          <th>producto_id</th><th>producto_nombre</th><th>lote</th>
          <th>mov_ori</th><th>mov_dst</th>
          <th>alm_codigo</th><th>alm_nombre</th><th>zona_nombre</th>
          <th>empresa_id</th><th>proyecto_id</th><th>proyecto_clave</th><th>proyecto_nombre</th>
          <th>Referencia</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="16">Sin resultados para el filtro actual.</td></tr>
        <?php else: foreach($rows as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['tx_id'])?></td>
            <td><?=htmlspecialchars($r['fecha_hora'])?></td>
            <td><?=htmlspecialchars($r['tipo_tx'])?></td>
            <td><?=htmlspecialchars($r['producto_id'])?></td>
            <td><?=htmlspecialchars($r['producto_nombre'])?></td>
            <td><?=htmlspecialchars($r['lote'])?></td>
            <td><?=htmlspecialchars($r['mov_ori'])?></td>
            <td><?=htmlspecialchars($r['mov_dst'])?></td>
            <td><?=htmlspecialchars($r['alm_codigo'])?></td>
            <td><?=htmlspecialchars($r['alm_nombre'])?></td>
            <td><?=htmlspecialchars($r['zona_nombre'])?></td>
            <td><?=htmlspecialchars($r['empresa_id'])?></td>
            <td><?=htmlspecialchars($r['proyecto_id'])?></td>
            <td><?=htmlspecialchars($r['proyecto_clave'])?></td>
            <td><?=htmlspecialchars($r['proyecto_nombre'])?></td>
            <td><?=htmlspecialchars($r['Referencia'])?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>

    <div class="pager">
      <?php
        $q=$_GET;
        if ($has_prev) { $q['page']=$page-1; echo '<a href="?'.htmlspecialchars(http_build_query($q)).'">&laquo; Prev</a>'; }
        echo '<span>Página '.$page.'</span>';
        if ($has_next) { $q['page']=$page+1; echo '<a href="?'.htmlspecialchars(http_build_query($q)).'">Next &raquo;</a>'; }
      ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
