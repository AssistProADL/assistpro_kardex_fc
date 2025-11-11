<?php
/* kardex.php (fast v3)
 * - Carga inicial sin consulta; ejecuta al presionar Filtrar (run=1)
 * - Dropdown de Almacén (c_almacenp), filtrable por empresa_id si se ingresa
 * - Filtro por alm_clave exacto (valor del select)
 * - KPI cards: movimientos, entradas, salidas, neto, ajustes, transferencias
 * - Paginación sin COUNT(*) (LIMIT+1)
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
$alm_codigo   = $_GET['alm_codigo']   ?? ''; // texto libre zona
$alm_clave    = $_GET['alm_clave']    ?? ''; // select de almacén padre (clave)
$proyecto     = $_GET['proyecto']     ?? '';
$fini         = $_GET['fini']         ?? '';
$ffin         = $_GET['ffin']         ?? '';
$debug        = isset($_GET['debug']) && $_GET['debug']=='1';

$limit  = (isset($_GET['limit']) && ctype_digit((string)$_GET['limit'])) ? (int)$_GET['limit'] : 100;
$page   = (isset($_GET['page'])  && ctype_digit((string)$_GET['page']))  ? (int)$_GET['page']  : 1;
$offset = max(0, ($page-1) * $limit);

/* Solo ejecutar consultas si el usuario dio click a Filtrar (run=1) */
$run = isset($_GET['run']) && $_GET['run'] == '1';

/* ===== Catálogo de almacenes (c_almacenp) para el select ===== */
try {
  if ($empresa_id !== '') {
    $stAlm = $pdo->prepare("SELECT clave, nombre FROM c_almacenp WHERE empresa_id = :emp ORDER BY nombre LIMIT 1000");
    $stAlm->execute([':emp'=>$empresa_id]);
  } else {
    $stAlm = $pdo->query("SELECT clave, nombre FROM c_almacenp ORDER BY nombre LIMIT 500");
  }
  $alm_opts = $stAlm->fetchAll();
} catch (Throwable $e) {
  $alm_opts = [];
}

/* ===== WHERE ===== */
$where = []; $p = []; $notes = [];

if ($empresa_id !== '') { $where[] = 'empresa_id = :emp'; $p[':emp'] = $empresa_id; }
if ($producto_id !== ''){ $where[] = 'producto_id LIKE :prod'; $p[':prod'] = '%'.$producto_id.'%'; }
if ($lote !== '')       { $where[] = 'lote LIKE :lote'; $p[':lote'] = '%'.$lote.'%'; }
if ($alm_codigo !== '') { $where[] = 'alm_codigo LIKE :alm'; $p[':alm'] = '%'.$alm_codigo.'%'; }
if ($alm_clave  !== '') { $where[] = 'alm_clave = :almc'; $p[':almc'] = $alm_clave; }
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

/* ===== Datos (sin COUNT) y KPIs ===== */
$rows = []; $has_prev = false; $has_next = false; $error_msg = '';
$kpi = ['movs'=>0,'entradas'=>0,'salidas'=>0,'neto'=>0,'ajustes'=>0,'traslados'=>0];
if ($run) {
  try {
    // KPIs
    $stK = $pdo->prepare("
      SELECT
        COUNT(*)                           AS movs,
        SUM(mov_dst)                       AS entradas,
        SUM(mov_ori)                       AS salidas,
        SUM(mov_dst - mov_ori)             AS neto,
        SUM(CASE WHEN UPPER(tipo_tx) LIKE 'AJUSTE%%' THEN 1 ELSE 0 END) AS ajustes,
        SUM(CASE WHEN UPPER(tipo_tx) = 'TRANSFERENCIA' THEN 1 ELSE 0 END) AS traslados
      FROM v_kardex_doble_partida
      $wsql
    ");
    $stK->execute($p);
    $kpi_row = $stK->fetch();
    if ($kpi_row) {
      foreach ($kpi_row as $k=>$v) { $kpi[$k] = $v ?? 0; }
    }

    // Datos (limit+1)
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
<title>Kardex ETL (rápido v3)</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Helvetica,Arial,sans-serif;margin:0;background:#f7f7f9}
.container{max-width:1450px;margin:24px auto;padding:16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px}
h1{margin:0 0 16px 0;font-size:20px}
.grid{display:grid;grid-template-columns:repeat(8,minmax(120px,1fr));gap:8px}
form.filters{margin-bottom:12px}
input,select,button{padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
.actions{display:flex;gap:8px;align-items:center;margin:8px 0 12px 0;flex-wrap:wrap}
.badge{display:inline-block;margin-right:8px;padding:4px 8px;border-radius:999px;background:#eef;border:1px solid #99f;color:#003}
.table-wrap{overflow:auto;max-height:60vh;border:1px solid #e5e7eb;border-radius:8px}
table{border-collapse:collapse;width:100%;font-size:12px}
th,td{border-bottom:1px solid #eee;padding:6px 8px;text-align:left;white-space:nowrap}
th{background:#fafafa;position:sticky;top:0}
.pager{display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap}
.pager a,.pager span{padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;text-decoration:none;color:#111}
.pager .active{background:#eef}
.muted{color:#6b7280}
.debug{background:#fef3c7;border:1px dashed #f59e0b;border-radius:8px;padding:8px;margin-bottom:8px;font-size:12px}
.err{background:#fee2e2;border:1px solid #ef4444;border-radius:8px;padding:8px;margin-bottom:8px}
.note{background:#ecfeff;border:1px solid #06b6d4;border-radius:8px;padding:8px;margin-bottom:8px}
.cards{display:grid;grid-template-columns:repeat(6,minmax(140px,1fr));gap:8px;margin:8px 0 12px}
.card{border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fafafa}
.card .label{font-size:12px;color:#6b7280}
.card .value{font-size:18px;font-weight:700}
</style>
</head>
<body>
<div class="container">
  <h1>Kardex Bidireccional — ETL (carga rápida v3)</h1>

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
      <span class="muted">Carga inicial ligera. Selecciona <b>Almacén</b> y/o fechas y presiona <b>Filtrar</b>.</span>
    <?php else: ?>
      <a href="?<?php $q=$_GET; $q['export']='csv'; echo htmlspecialchars(http_build_query($q)); ?>" class="pager">Exportar CSV</a>
    <?php endif; ?>
  </div>

  <form class="filters" method="get">
    <input type="hidden" name="run" value="1">
    <div class="grid">
      <input type="text" name="empresa_id"  placeholder="Empresa" value="<?php echo htmlspecialchars($empresa_id); ?>">
      <input type="text" name="producto_id" placeholder="Producto" value="<?php echo htmlspecialchars($producto_id); ?>">
      <input type="text" name="lote"        placeholder="Lote/Serie" value="<?php echo htmlspecialchars($lote); ?>">
      <select name="tipo_tx">
        <option value="">Tipo (todos)</option>
        <?php foreach (['ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE'] as $t) {
          $sel = ($tipo_tx === $t) ? ' selected' : '';
          echo '<option value="'.$t.'"'.$sel.'>'.$t.'</option>';
        } ?>
      </select>
      <input type="text" name="alm_codigo" placeholder="Zona (c_almacen.cve_almac)" value="<?php echo htmlspecialchars($alm_codigo); ?>">

      <select name="alm_clave">
        <option value="">Almacén (todos)</option>
        <?php foreach ($alm_opts as $opt) {
          $val = $opt['clave']; $text = $opt['clave'].' — '.$opt['nombre'];
          $sel = ($alm_clave === $val) ? ' selected' : '';
          echo '<option value="'.htmlspecialchars($val).'"'.$sel.'>'.htmlspecialchars($text).'</option>';
        } ?>
      </select>

      <input type="date" name="fini" value="<?php echo htmlspecialchars($fini); ?>">
      <input type="date" name="ffin" value="<?php echo htmlspecialchars($ffin); ?>">
    </div>
    <div style="margin-top:8px"><button type="submit">Filtrar</button></div>
  </form>

  <?php if($run): ?>
    <div class="cards">
      <div class="card"><div class="label">Movimientos</div><div class="value"><?=number_format((float)$kpi['movs'])?></div></div>
      <div class="card"><div class="label">Entradas</div><div class="value"><?=number_format((float)$kpi['entradas'],4)?></div></div>
      <div class="card"><div class="label">Salidas</div><div class="value"><?=number_format((float)$kpi['salidas'],4)?></div></div>
      <div class="card"><div class="label">Neto</div><div class="value"><?=number_format((float)$kpi['neto'],4)?></div></div>
      <div class="card"><div class="label">Ajustes</div><div class="value"><?=number_format((float)$kpi['ajustes'])?></div></div>
      <div class="card"><div class="label">Traslados</div><div class="value"><?=number_format((float)$kpi['traslados'])?></div></div>
    </div>
  <?php endif; ?>

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
