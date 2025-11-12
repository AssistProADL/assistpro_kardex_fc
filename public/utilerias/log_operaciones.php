<?php
// /public/dashboard/log_operaciones.php
require_once __DIR__ . '/../../app/db.php';

/* ===== Modo UI =====
   iframe=1  -> se muestra solo el contenido (sin _menu_global.php),
                y se permite que el documento sea embebido (CSP frame-ancestors).
   iframe=0  -> se incluye el layout global (_menu_*).
*/
$IFRAME = isset($_GET['iframe']) && ($_GET['iframe']=='1' || strtolower($_GET['iframe'])==='true');
if ($IFRAME) {
  // Permitir que sea embebido por el mismo origen; ajusta dominios si ocupas cross-origin
  header("Content-Security-Policy: frame-ancestors 'self'");
} else {
  // Variables que usan tus plantillas globales
  $activeSection = 'dashboard';
  $activeItem    = 'log_operaciones';
  $pageTitle     = 'Log de Operaciones · AssistPro';
  // Layout global (como en index.php)
  include __DIR__.'../../bi/_menu_global.php';
}

/* ===== Helpers ===== */
function param($k, $d=null){ return isset($_GET[$k]) && $_GET[$k]!=='' ? trim($_GET[$k]) : $d; }
function iint($v, $d=0){ $v = filter_var($v, FILTER_VALIDATE_INT); return $v===false? $d : $v; }
function qs($overrides=[]){
  $q = array_merge($_GET, $overrides);
  foreach($q as $k=>$v){ if($v===null) unset($q[$k]); }
  return http_build_query($q);
}

/* ===== Filtros ===== */
$empresa_id = param('empresa_id');
$modulo     = param('modulo');
$usuario    = param('usuario');
$operacion  = param('operacion');
$dispositivo= param('dispositivo');
$desde      = param('desde'); // yyyy-mm-dd
$hasta      = param('hasta'); // yyyy-mm-dd
$export     = param('export'); // 'csv'

/* ===== Paginación ===== */
$per_page = max(10, iint(param('per_page', 50), 50));
$page     = max(1,  iint(param('p', 1), 1));
$offset   = ($page - 1) * $per_page;

/* ===== Fuente de datos =====
   Usa la vista normalizada (con fecha_dt) como acordamos.
   Cambia el nombre si tu vista es distinta.
*/
$FROM_SOURCE = "v_log_operaciones";

/* ===== WHERE dinámico ===== */
$where = " WHERE 1=1 ";
$params = [];
if ($empresa_id !== null && $empresa_id !== '') { $where.=" AND empresa_id = :empresa_id";       $params[':empresa_id'] = $empresa_id; }
if ($modulo)      { $where.=" AND modulo LIKE :modulo";           $params[':modulo'] = "%{$modulo}%"; }
if ($usuario)     { $where.=" AND usuario LIKE :usuario";         $params[':usuario'] = "%{$usuario}%"; }
if ($operacion)   { $where.=" AND operacion LIKE :operacion";     $params[':operacion'] = "%{$operacion}%"; }
if ($dispositivo) { $where.=" AND dispositivo LIKE :dispositivo"; $params[':dispositivo'] = "%{$dispositivo}%"; }
if ($desde)       { $where.=" AND fecha_dt >= :desde";            $params[':desde'] = $desde.' 00:00:00'; }
if ($hasta)       { $where.=" AND fecha_dt <= :hasta";            $params[':hasta'] = $hasta.' 23:59:59'; }

/* ===== Totales y data ===== */
$pdo = db();
$sqlCount = "SELECT COUNT(*) FROM {$FROM_SOURCE} {$where}";
$stc = $pdo->prepare($sqlCount); $stc->execute($params);
$total = (int)($stc->fetchColumn() ?: 0);
$pages = max(1, (int)ceil($total / $per_page));

$sqlData = "SELECT empresa_id, modulo, usuario, operacion, dispositivo, observaciones, fecha_dt, fecha_raw
            FROM {$FROM_SOURCE}
            {$where}
            ORDER BY fecha_dt DESC, fecha_raw DESC
            LIMIT :lim OFFSET :off";
$std = $pdo->prepare($sqlData);
foreach($params as $k=>$v){ $std->bindValue($k, $v); }
$std->bindValue(':lim', $per_page, PDO::PARAM_INT);
$std->bindValue(':off', $offset,   PDO::PARAM_INT);
$std->execute();
$rows = $std->fetchAll(PDO::FETCH_ASSOC);

/* ===== Export CSV (con los mismos filtros, sin paginación) ===== */
if ($export === 'csv') {
  $sqlCSV = "SELECT empresa_id, modulo, usuario, operacion, dispositivo, observaciones,
                    COALESCE(DATE_FORMAT(fecha_dt, '%Y-%m-%d %H:%i:%s'), fecha_raw) AS fecha
             FROM {$FROM_SOURCE} {$where} ORDER BY fecha_dt DESC, fecha_raw DESC";
  $stx = $pdo->prepare($sqlCSV);
  $stx->execute($params);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=log_operaciones.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Empresa','Módulo','Usuario','Operación','Dispositivo','Observaciones','Fecha']);
  while ($r = $stx->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [$r['empresa_id'],$r['modulo'],$r['usuario'],$r['operacion'],$r['dispositivo'],$r['observaciones'],$r['fecha']]);
  }
  fclose($out);
  exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= isset($pageTitle)?$pageTitle:'Log de Operaciones' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --b:#ddd; --bg:#f7f7f7; --primary:#0a2a6b; }
    body { font-family: Arial, sans-serif; <?php if($IFRAME): ?>margin:0;background:#fff;<?php endif; ?> }
    .container-fluid { <?php if($IFRAME): ?>padding:8px;<?php else: ?>padding:12px;<?php endif; ?> }
    .ap-card { border:1px solid var(--b); border-radius:12px; background:#fff; }
    .ap-card h4 { margin:0; }
    .filters { display:grid; grid-template-columns: repeat(6, 1fr); gap:8px; align-items:end; }
    .filters label { font-size:12px; display:block; }
    .filters input, .filters select, .filters button { width:100%; padding:6px; font-size:12px; }
    .actions { display:flex; gap:8px; grid-column:1 / -1; }
    .btn { padding:6px 10px; font-size:12px; border:1px solid #ccc; border-radius:6px; background:#fff; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn.primary { background:#1e88e5; color:#fff; border-color:#1e88e5; }
    .tablewrap { margin-top:12px; border:1px solid var(--b); border-radius:8px; overflow:auto; height:60vh; }
    table { border-collapse:collapse; width:100%; font-size:10px; min-width:1200px; } /* min-width -> scroll H */
    th, td { border-bottom:1px solid #eee; padding:6px 8px; white-space:nowrap; }
    thead th { position:sticky; top:0; background:var(--bg); z-index:1; }
    .kpis { display:flex; gap:12px; flex-wrap:wrap; }
    .kpi { border:1px solid var(--b); border-radius:10px; padding:8px 10px; background:#fafafa; font-size:12px; }
    .pager { display:flex; gap:6px; align-items:center; margin-top:8px; font-size:12px; flex-wrap:wrap; }
    .pager a, .pager span { padding:6px 10px; border:1px solid #ccc; border-radius:6px; text-decoration:none; color:#333; }
    .pager .active { background:#333; color:#fff; border-color:#333; }
    .pager .disabled { opacity:.5; pointer-events:none; }
  </style>
</head>
<body>

<div class="container-fluid">
  <!-- Encabezado/KPIs en tarjeta, como en index.php -->
  <div class="ap-card p-3 mb-3">
    <?php if(!$IFRAME): ?><h4 style="color:#0a2a6b;">Log de Operaciones</h4><?php endif; ?>
    <div class="kpis">
      <div class="kpi">Registros (página): <strong><?= number_format(count($rows)) ?></strong></div>
      <div class="kpi">Total filtrado: <strong><?= number_format($total) ?></strong></div>
      <div class="kpi">Páginas: <strong><?= number_format($pages) ?></strong></div>
    </div>
  </div>

  <!-- Filtros -->
  <form method="get" class="ap-card p-3 filters" id="filtros">
    <?php if($IFRAME): ?><input type="hidden" name="iframe" value="1"><?php endif; ?>
    <div>
      <label>Empresa ID</label>
      <input type="number" name="empresa_id" value="<?= htmlspecialchars($empresa_id ?? '') ?>">
    </div>
    <div>
      <label>Módulo</label>
      <input type="text" name="modulo" value="<?= htmlspecialchars($modulo ?? '') ?>" placeholder="kardex, rfid, pedidos...">
    </div>
    <div>
      <label>Usuario</label>
      <input type="text" name="usuario" value="<?= htmlspecialchars($usuario ?? '') ?>">
    </div>
    <div>
      <label>Operación</label>
      <input type="text" name="operacion" value="<?= htmlspecialchars($operacion ?? '') ?>" placeholder="INSERT, UPDATE, LOGIN...">
    </div>
    <div>
      <label>Dispositivo</label>
      <input type="text" name="dispositivo" value="<?= htmlspecialchars($dispositivo ?? '') ?>" placeholder="WEB, HHT, API...">
    </div>
    <div>
      <label>Desde / Hasta</label>
      <div style="display:flex; gap:6px;">
        <input type="date" name="desde" value="<?= htmlspecialchars($desde ?? '') ?>">
        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta ?? '') ?>">
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn primary">Aplicar filtros</button>
      <a class="btn" href="?<?php
        $base = $IFRAME ? 'iframe=1' : '';
        echo $base;
      ?>">Limpiar</a>
      <a class="btn" href="?<?= qs(['export'=>'csv','p'=>null]) ?>">Exportar CSV</a>
      <div style="margin-left:auto; display:flex; gap:6px; align-items:center;">
        <label for="per_page" style="font-size:12px;">Filas/página</label>
        <input type="number" min="10" step="10" id="per_page" name="per_page" value="<?= (int)$per_page ?>" style="width:90px;">
      </div>
    </div>
  </form>

  <!-- Grilla -->
  <div class="tablewrap ap-card">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Empresa</th>
          <th>Módulo</th>
          <th>Usuario</th>
          <th>Operación</th>
          <th>Dispositivo</th>
          <th>Observaciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7">Sin resultados con los filtros actuales.</td></tr>
      <?php else: foreach($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['fecha_dt'] ?: $r['fecha_raw']) ?></td>
          <td><?= htmlspecialchars($r['empresa_id']) ?></td>
          <td><?= htmlspecialchars($r['modulo']) ?></td>
          <td><?= htmlspecialchars($r['usuario']) ?></td>
          <td><?= htmlspecialchars($r['operacion']) ?></td>
          <td><?= htmlspecialchars($r['dispositivo']) ?></td>
          <td><?= htmlspecialchars($r['observaciones']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginación -->
  <div class="pager">
    <?php
      $prev = $page - 1; $next = $page + 1;
      $disablePrev = $page <= 1 ? 'disabled' : '';
      $disableNext = $page >= $pages ? 'disabled' : '';
    ?>
    <a class="<?= $disablePrev ?>" href="?<?= qs(['p'=>1]) ?>">« Primero</a>
    <a class="<?= $disablePrev ?>" href="?<?= qs(['p'=>$prev]) ?>">‹ Anterior</a>
    <span>Página <strong><?= $page ?></strong> de <strong><?= $pages ?></strong></span>
    <a class="<?= $disableNext ?>" href="?<?= qs(['p'=>$next]) ?>">Siguiente ›</a>
    <a class="<?= $disableNext ?>" href="?<?= qs(['p'=>$pages]) ?>">Último »</a>
  </div>
</div>

<?php if (!$IFRAME) { include __DIR__.'/../bi/_menu_global_end.php'; } ?>

<?php if ($IFRAME): ?>
<!-- Auto-resize para el iframe (opcional) -->
<script>
(function(){
  function postHeight(){
    const h = document.documentElement.scrollHeight || document.body.scrollHeight;
    try { window.parent.postMessage({ type:'assistpro:resize', feature:'log_operaciones', height:h }, '*'); } catch(e){}
  }
  const ro = new ResizeObserver(postHeight);
  ro.observe(document.body);
  window.addEventListener('load', postHeight);
  document.getElementById('filtros')?.addEventListener('submit', function(){ setTimeout(postHeight, 400); });
})();
</script>
<?php endif; ?>
</body>
</html>
