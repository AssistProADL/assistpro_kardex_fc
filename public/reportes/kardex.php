<?php
/* kardex_etl_zero_v2.php
 * Kardex Bidireccional (ETL) — v2 con Proyecto, Almacén y Empresa
 * - PDO seguro a assistpro_etl_fc
 * - Filtros: empresa_id, producto_id, lote, tipo_tx, fechas, alm_codigo, proyecto (id/clave/nombre)
 * - Paginación y Export CSV
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

/* ========= Filtros ========= */
$empresa_id   = isset($_GET['empresa_id'])   && $_GET['empresa_id']   !== '' ? trim($_GET['empresa_id'])   : null;
$producto_id  = isset($_GET['producto_id'])  && $_GET['producto_id']  !== '' ? trim($_GET['producto_id'])  : null;
$lote         = isset($_GET['lote'])         && $_GET['lote']         !== '' ? trim($_GET['lote'])         : null;
$tipo_tx      = isset($_GET['tipo_tx'])      && $_GET['tipo_tx']      !== '' ? (array)$_GET['tipo_tx']     : [];
$fini         = isset($_GET['fini'])         && $_GET['fini']         !== '' ? trim($_GET['fini'])         : null;
$ffin         = isset($_GET['ffin'])         && $_GET['ffin']         !== '' ? trim($_GET['ffin'])         : null;
$alm_codigo   = isset($_GET['alm_codigo'])   && $_GET['alm_codigo']   !== '' ? trim($_GET['alm_codigo'])   : null;
$prj          = isset($_GET['proyecto'])     && $_GET['proyecto']     !== '' ? trim($_GET['proyecto'])     : null; // acepta id/clave/nombre

/* rango por defecto: últimos 30 días si no se indicó ninguno */
if ($fini === null && $ffin === null) {
  $fini = date('Y-m-d', strtotime('-30 days'));
  $ffin = date('Y-m-d');
}

$limit  = isset($_GET['limit'])  && ctype_digit((string)$_GET['limit'])  ? (int)$_GET['limit']  : 200;
$page   = isset($_GET['page'])   && ctype_digit((string)$_GET['page'])   ? (int)$_GET['page']   : 1;
$offset = max(0, ($page - 1) * $limit);

/* ========= WHERE ========= */
$where = []; $p = [];
if ($empresa_id !== null) { $where[] = 'empresa_id = :emp'; $p[':emp'] = $empresa_id; }
if ($producto_id !== null){ $where[] = 'producto_id LIKE :prod'; $p[':prod'] = '%' . $producto_id . '%'; }
if ($lote !== null)       { $where[] = 'lote LIKE :lote'; $p[':lote'] = '%' . $lote . '%'; }
if ($alm_codigo !== null) { $where[] = 'alm_codigo LIKE :alm'; $p[':alm'] = '%' . $alm_codigo . '%'; }
if ($fini !== null)       { $where[] = 'fecha_hora >= :fini'; $p[':fini'] = $fini . ' 00:00:00'; }
if ($ffin !== null)       { $where[] = 'fecha_hora <= :ffin'; $p[':ffin'] = $ffin . ' 23:59:59'; }

if (!empty($tipo_tx)) {
  $ph = []; $i=0;
  foreach ($tipo_tx as $t){ $k=':t'.$i++; $ph[]=$k; $p[$k]=strtoupper(trim($t)); }
  $where[] = 'UPPER(tipo_tx) IN ('.implode(',', $ph).')';
}

if ($prj !== null) {
  // busca por id exacto o por like en clave/nombre
  $where[] = '(CAST(proyecto_id AS CHAR) = :prjid OR proyecto_clave LIKE :prj OR proyecto_nombre LIKE :prj)';
  $p[':prjid'] = $prj;
  $p[':prj']   = '%'.$prj.'%';
}

$wsql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ========= Export CSV ========= */
if (isset($_GET['export']) && strtolower($_GET['export'])==='csv') {
  $fname = 'kardex_'.date('Ymd_His').'.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  $out = fopen('php://output','w');
  fputcsv($out, ['tx_id','fecha_hora','tipo_tx','producto_id','producto_nombre','lote','mov_ori','mov_dst','alm_codigo','alm_nombre','zona_nombre','empresa_id','proyecto_id','proyecto_clave','proyecto_nombre','Referencia']);
  $st = $pdo->prepare("SELECT tx_id,fecha_hora,tipo_tx,producto_id,producto_nombre,lote,mov_ori,mov_dst,alm_codigo,alm_nombre,zona_nombre,empresa_id,proyecto_id,proyecto_clave,proyecto_nombre,Referencia
                       FROM v_kardex_doble_partida $wsql
                       ORDER BY fecha_hora DESC, tx_id DESC");
  $st->execute($p);
  while ($r = $st->fetch(PDO::FETCH_NUM)) { fputcsv($out, $r); }
  fclose($out); exit;
}

/* ========= Conteo y datos ========= */
$stCount = $pdo->prepare("SELECT COUNT(*) AS c FROM v_kardex_doble_partida $wsql");
$stCount->execute($p);
$total = (int)$stCount->fetchColumn();

$st = $pdo->prepare("
  SELECT tx_id, fecha_hora, tipo_tx, producto_id, producto_nombre, lote,
         mov_ori, mov_dst, alm_codigo, alm_nombre, zona_nombre,
         empresa_id, proyecto_id, proyecto_clave, proyecto_nombre, Referencia
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
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kardex ETL (v2)</title>
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
</style>
</head>
<body>
<div class="container">
  <h1>Kardex Bidireccional — ETL (v2)</h1>

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
    <input type="text" name="alm_codigo" placeholder="Almacén/Zona" value="<?php echo htmlspecialchars($alm_codigo ?? ''); ?>">
    <input type="text" name="proyecto"   placeholder="Proyecto (id/clave/nombre)" value="<?php echo htmlspecialchars($prj ?? ''); ?>">
    <input type="date" name="fini" value="<?php echo htmlspecialchars($fini ?? ''); ?>">
    <input type="date" name="ffin" value="<?php echo htmlspecialchars($ffin ?? ''); ?>">
    <button type="submit">Filtrar</button>
  </form>

  <div class="actions">
    <a href="?<?php $q = $_GET; $q['export']='csv'; echo htmlspecialchars(http_build_query($q)); ?>" class="pager">Exportar CSV</a>
  </div>

  <div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>tx_id</th>
        <th>fecha_hora</th>
        <th>tipo_tx</th>
        <th>producto_id</th>
        <th>producto_nombre</th>
        <th>lote</th>
        <th>mov_ori</th>
        <th>mov_dst</th>
        <th>alm_codigo</th>
        <th>alm_nombre</th>
        <th>zona_nombre</th>
        <th>empresa_id</th>
        <th>proyecto_id</th>
        <th>proyecto_clave</th>
        <th>proyecto_nombre</th>
        <th>Referencia</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="16">Sin resultados para el filtro actual.</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['tx_id']); ?></td>
        <td><?php echo htmlspecialchars($r['fecha_hora']); ?></td>
        <td><?php echo htmlspecialchars($r['tipo_tx']); ?></td>
        <td><?php echo htmlspecialchars($r['producto_id']); ?></td>
        <td><?php echo htmlspecialchars($r['producto_nombre']); ?></td>
        <td><?php echo htmlspecialchars($r['lote']); ?></td>
        <td><?php echo htmlspecialchars($r['mov_ori']); ?></td>
        <td><?php echo htmlspecialchars($r['mov_dst']); ?></td>
        <td><?php echo htmlspecialchars($r['alm_codigo']); ?></td>
        <td><?php echo htmlspecialchars($r['alm_nombre']); ?></td>
        <td><?php echo htmlspecialchars($r['zona_nombre']); ?></td>
        <td><?php echo htmlspecialchars($r['empresa_id']); ?></td>
        <td><?php echo htmlspecialchars($r['proyecto_id']); ?></td>
        <td><?php echo htmlspecialchars($r['proyecto_clave']); ?></td>
        <td><?php echo htmlspecialchars($r['proyecto_nombre']); ?></td>
        <td><?php echo htmlspecialchars($r['Referencia']); ?></td>
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
        if ($p>=20) { echo '<span>…</span>'; break; }
      }
    ?>
  </div>
</div>
</body>
</html>
