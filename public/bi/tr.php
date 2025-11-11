<?php
// =======================================
//  Repremundo | Análisis Inicial – Dashboard Pro
//  Fuente: assistpro_etl_fc
//  Tablas: c_almacenp, c_usuario, t_roles, trel_us_alm, c_articulo, c_proveedores
// =======================================
require_once __DIR__ . '/../../app/db.php';

session_start();

/* ===========================
   Envolvente (iframe y menú)
   =========================== */
$IFRAME = isset($_GET['iframe']) && ($_GET['iframe']=='1' || strtolower($_GET['iframe'])==='true');
if ($IFRAME) {
  header("Content-Security-Policy: frame-ancestors 'self'");
} else {
  $activeSection = 'dashboard';
  $activeItem    = 'tr';
  $pageTitle     = 'Repremundo | Análisis Inicial';
  include __DIR__.'/_menu_global.php';
}

/* ===========================
   Utilidades y Config
   =========================== */
function gp($k,$d=null){ return isset($_GET[$k]) && $_GET[$k]!=='' ? trim($_GET[$k]) : $d; }
function qs($over=[]){ $q=array_merge($_GET,$over); foreach($q as $k=>$v){ if($v===null) unset($q[$k]); } return http_build_query($q); }

$COLLATE = 'utf8mb4_unicode_ci'; // usa la colación global objetivo

// Helper para forzar colación en comparaciones LIKE/=
function COL($expr){ global $COLLATE; return "CONVERT($expr USING utf8mb4) COLLATE {$COLLATE}"; }

// Almacén en sesión o por GET (fallback)
$alm_sesion = $_SESSION['cve_almacen'] ?? gp('cve_almac');

/* ===========================
   Parámetros
   =========================== */
$dataset    = strtolower(gp('dataset','articulos'));        // almacenes | usuarios | articulos | proveedores
$empresa_id = gp('empresa_id');
$activo     = gp('activo');                                 // 1 / 0
$q          = gp('q');
$rol        = gp('rol');                                    // para usuarios (t_roles.rol / c_usuario.perfil)
$per_page   = max(10, (int)gp('per_page', 25));
$page       = max(1,  (int)gp('p', 1));
$offset     = ($page-1) * $per_page;
$export     = gp('export');                                 // 'csv'

$valid = ['almacenes','usuarios','articulos','proveedores'];
if (!in_array($dataset,$valid)) { $dataset='articulos'; }

/* ===========================
   Totales (cards)
   =========================== */
$total_almac    = db_val("SELECT COUNT(*) FROM c_almacenp");
$total_usuarios = db_val("SELECT COUNT(*) FROM c_usuario");
$total_art      = db_val("SELECT COUNT(*) FROM c_articulo");
$total_cli      = db_val("SELECT COUNT(*) FROM c_proveedores WHERE ".COL('es_cliente')." IN ('1','S','SI','Y','YES')");
$total_prov     = $total_cli;

/* ===========================
   Catálogos (filtros)
   =========================== */
$roles = db_all("SELECT DISTINCT rol FROM t_roles WHERE COALESCE(rol,'')<>'' ORDER BY rol");

$empresas_ds = [];
switch ($dataset) {
  case 'almacenes':
    $empresas_ds = db_all("SELECT DISTINCT empresa_id AS empresa FROM c_almacenp WHERE empresa_id IS NOT NULL AND empresa_id<>'' ORDER BY empresa_id");
    break;
  case 'usuarios':
    $empresas_ds = db_all("SELECT DISTINCT empresa_id AS empresa FROM c_usuario WHERE empresa_id IS NOT NULL AND empresa_id<>'' ORDER BY empresa_id");
    break;
  case 'proveedores':
    $hasEmpresaProv = db_all("SHOW COLUMNS FROM c_proveedores LIKE 'empresa_id'");
    if ($hasEmpresaProv) $empresas_ds = db_all("SELECT DISTINCT empresa_id AS empresa FROM c_proveedores WHERE empresa_id IS NOT NULL AND empresa_id<>'' ORDER BY empresa_id");
    break;
  default:
    $empresas_ds = [];
}

/* ===========================
   Columnas fijas por dataset
   (para evitar mismatches con DataTables)
   =========================== */
if     ($dataset==='almacenes')   { $cols = ['Empresa','Clave','Nombre','Direccion','CP','Telefono','Contacto','Correo','Activo']; }
elseif ($dataset==='usuarios')    { $cols = ['Empresa','Clave','Nombre','Correo','Celular','Rol','Estatus','Activo']; }
elseif ($dataset==='proveedores') { $cols = ['Clave','Nombre','Pais','Estado','Ciudad','Telefono','Cliente','Activo']; } // SOLO clientes
else                              { $cols = ['Clave','Descripcion','Grupo','Clasificacion','Tipo','Activo']; }

/* ===========================
   WHERE & SQL por dataset
   =========================== */
$where  = " WHERE 1=1 ";
$params = [];
$title  = '';

if ($dataset==='almacenes') {
  $title = 'Almacenes';
  if ($empresa_id!=='') { $where.=" AND empresa_id = :emp"; $params[':emp']=$empresa_id; }
  if ($activo==='1') { $where.=" AND ((COALESCE(Activo,'')+0)=1 OR ".COL('Activo')." IN ('S','SI','Y','YES'))"; }
  if ($activo==='0') { $where.=" AND NOT ((COALESCE(Activo,'')+0)=1 OR ".COL('Activo')." IN ('S','SI','Y','YES'))"; }
  if ($q) {
    $where.=" AND (".COL('clave')." LIKE :q OR ".COL('nombre')." LIKE :q OR ".COL('direccion')." LIKE :q OR ".COL('correo')." LIKE :q OR ".COL('contacto')." LIKE :q OR ".COL('telefono')." LIKE :q)";
    $params[':q'] = "%{$q}%";
  }

  $countSql = "SELECT COUNT(*) FROM c_almacenp {$where}";
  $dataSql  = "SELECT
                COALESCE(empresa_id,'')   AS Empresa,
                COALESCE(clave,'')        AS Clave,
                COALESCE(nombre,'')       AS Nombre,
                COALESCE(direccion,'')    AS Direccion,
                COALESCE(codigopostal,'') AS CP,
                COALESCE(telefono,'')     AS Telefono,
                COALESCE(contacto,'')     AS Contacto,
                COALESCE(correo,'')       AS Correo,
                COALESCE(Activo,'')       AS Activo
              FROM c_almacenp
              {$where}
              ORDER BY nombre
              LIMIT :lim OFFSET :off";

} elseif ($dataset==='usuarios') {
  $title = 'Usuarios (con Rol)';

  // JOIN roles con CAST + COLLATE
  $joinRoles = "LEFT JOIN t_roles tr
                  ON tr.empresa_id = cu.empresa_id
                 AND (CAST(tr.id_role AS CHAR) COLLATE {$COLLATE}) = (CAST(cu.perfil AS CHAR) COLLATE {$COLLATE})";

  // Relación usuarios↔almacén para filtrar por almacén de sesión
  $joinRel = "LEFT JOIN trel_us_alm tua
                ON tua.empresa_id = cu.empresa_id
               AND ".COL('tua.cve_usuario')." = ".COL('cu.cve_usuario');

  // WHERE base
  $where = " WHERE 1=1 ";
  if ($empresa_id!=='') { $where.=" AND cu.empresa_id = :emp"; $params[':emp']=$empresa_id; }
  if ($activo==='1') { $where.=" AND ((COALESCE(cu.Activo,'')+0)=1 OR ".COL('cu.Activo')." IN ('S','SI','Y','YES'))"; }
  if ($activo==='0') { $where.=" AND NOT ((COALESCE(cu.Activo,'')+0)=1 OR ".COL('cu.Activo')." IN ('S','SI','Y','YES'))"; }
  if ($rol!=='')     { $where.=" AND (UPPER(COALESCE(tr.rol, cu.perfil)) COLLATE {$COLLATE}) = (UPPER(CAST(:rol AS CHAR)) COLLATE {$COLLATE})"; $params[':rol']=$rol; }
  if ($q) {
    $where.=" AND (".COL('cu.cve_usuario')." LIKE :q OR ".COL('cu.nombre_completo')." LIKE :q OR ".COL('cu.email')." LIKE :q OR ".COL('cu.celular')." LIKE :q)";
    $params[':q'] = "%{$q}%";
  }
  if ($alm_sesion!=='') { $where.=" AND ".COL('tua.cve_almac')." = ".COL(':alm'); $params[':alm']=$alm_sesion; }

  $countSql = "SELECT COUNT(*)
               FROM c_usuario cu
               {$joinRoles}
               {$joinRel}
               {$where}";

  $dataSql  = "SELECT
                cu.empresa_id                 AS Empresa,
                COALESCE(cu.cve_usuario,'')   AS Clave,
                COALESCE(cu.nombre_completo,'') AS Nombre,
                COALESCE(cu.email,'')         AS Correo,
                COALESCE(cu.celular,'')       AS Celular,
                COALESCE(tr.rol, cu.perfil)   AS Rol,
                COALESCE(cu.status,'')        AS Estatus,
                COALESCE(cu.Activo,'')        AS Activo
              FROM c_usuario cu
              {$joinRoles}
              {$joinRel}
              {$where}
              ORDER BY cu.nombre_completo
              LIMIT :lim OFFSET :off";

} elseif ($dataset==='proveedores') {
  $title = 'Clientes (desde c_proveedores)'; // SOLO clientes
  // WHERE base: solo clientes
  $where = " WHERE ".COL('es_cliente')." IN ('1','S','SI','Y','YES') ";
  $hasEmpresaProv = db_all("SHOW COLUMNS FROM c_proveedores LIKE 'empresa_id'");
  if ($empresa_id!=='' && $hasEmpresaProv) { $where.=" AND empresa_id = :emp"; $params[':emp']=$empresa_id; }
  if ($activo==='1') { $where.=" AND ((COALESCE(Activo,'')+0)=1 OR ".COL('Activo')." IN ('S','SI','Y','YES'))"; }
  if ($activo==='0') { $where.=" AND NOT ((COALESCE(Activo,'')+0)=1 OR ".COL('Activo')." IN ('S','SI','Y','YES'))"; }
  if ($q) {
    $where.=" AND (".COL('cve_proveedor')." LIKE :q OR ".COL('Nombre')." LIKE :q OR ".COL('ciudad')." LIKE :q OR ".COL('estado')." LIKE :q OR ".COL('pais')." LIKE :q OR ".COL('telefono1')." LIKE :q)";
    $params[':q'] = "%{$q}%";
  }

  $countSql = "SELECT COUNT(*) FROM c_proveedores {$where}";
  $dataSql  = "SELECT
                COALESCE(cve_proveedor,'') AS Clave,
                COALESCE(Nombre,'')        AS Nombre,
                COALESCE(pais,'')          AS Pais,
                COALESCE(estado,'')        AS Estado,
                COALESCE(ciudad,'')        AS Ciudad,
                COALESCE(telefono1,'')     AS Telefono,
                'Sí'                        AS Cliente,
                COALESCE(Activo,'')        AS Activo
              FROM c_proveedores
              {$where}
              ORDER BY Nombre
              LIMIT :lim OFFSET :off";

} else { // articulos
  $title = 'Artículos';
  if ($activo==='1') { $where.=" AND ((COALESCE(Activo,'')+0)=1 OR ".COL('Activo')." IN ('S','SI','Y','YES'))"; }
  if ($activo==='0') { $where.=" AND NOT ((COALESCE(Activo,'')+0)=1 OR ".COL('Activo')." IN ('S','SI','Y','YES'))"; }
  if ($q) {
    $where.=" AND (".COL('cve_articulo')." LIKE :q OR ".COL('des_articulo')." LIKE :q OR ".COL('grupo')." LIKE :q OR ".COL('clasificacion')." LIKE :q OR ".COL('tipo')." LIKE :q)";
    $params[':q']="%{$q}%";
  }

  $countSql = "SELECT COUNT(*) FROM c_articulo {$where}";
  $dataSql  = "SELECT
                COALESCE(cve_articulo,'')  AS Clave,
                COALESCE(des_articulo,'')  AS Descripcion,
                COALESCE(grupo,'')         AS Grupo,
                COALESCE(clasificacion,'') AS Clasificacion,
                COALESCE(tipo,'')          AS Tipo,
                COALESCE(Activo,'')        AS Activo
              FROM c_articulo
              {$where}
              ORDER BY des_articulo
              LIMIT :lim OFFSET :off";
}

/* ===========================
   Ejecutar consultas
   =========================== */
$pdo = db();

// total
$stc = $pdo->prepare($countSql);
$stc->execute($params);
$total = (int)$stc->fetchColumn();
$pages = max(1,(int)ceil($total/$per_page));

// data
$std = $pdo->prepare($dataSql);
foreach ($params as $k=>$v) $std->bindValue($k,$v);
$std->bindValue(':lim',$per_page,PDO::PARAM_INT);
$std->bindValue(':off',$offset,PDO::PARAM_INT);
$std->execute();
$rows = $std->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   CSV export (mismos filtros)
   =========================== */
if ($export==='csv') {
  $sqlCSV = preg_replace('/LIMIT\\s*:\\w+\\s*OFFSET\\s*:\\w+/i','',$dataSql);
  $stx = $pdo->prepare($sqlCSV);
  foreach ($params as $k=>$v) $stx->bindValue($k,$v);
  $stx->execute();

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=repremundo_'.$dataset.'.csv');
  $out = fopen('php://output','w');
  fputcsv($out, $cols);
  while ($r = $stx->fetch(PDO::FETCH_ASSOC)) {
    if (isset($r['Activo'])) {
      $a = strtoupper((string)$r['Activo']);
      $r['Activo'] = in_array($a,['1','S','SI','Y','YES']) ? '✔' : '✘';
    }
    $line = [];
    foreach ($cols as $c) { $line[] = $r[$c] ?? ''; }
    fputcsv($out, $line);
  }
  fclose($out);
  exit;
}
?>
<?php if ($IFRAME): ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($pageTitle ?? 'Repremundo | Análisis Inicial') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
  :root { --b:#e5e7eb; --bg:#f8f9fa; --brand:#0F5AAD; }
  body { background:#fff; font-family: Arial, sans-serif; margin:0; font-size:13px; }
  .container-fluid { padding:12px; }
<?php else: ?>
<style>
  :root { --b:#e5e7eb; --bg:#f8f9fa; --brand:#0F5AAD; }
  .container-fluid { padding:12px; }
<?php endif; ?>
  /* Cards con gradientes */
  .card-summary { border:0; color:#fff; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,.07); }
  .card-azul   { background: linear-gradient(135deg, #0F5AAD, #2A76D2); }
  .card-verde  { background: linear-gradient(135deg, #2e7d32, #43a047); }
  .card-morado { background: linear-gradient(135deg, #6a1b9a, #8e24aa); }
  .card-ambar  { background: linear-gradient(135deg, #ef6c00, #fb8c00); }
  .card-title  { font-size:12px; color:rgba(255,255,255,.85); margin-bottom:6px; }
  .card-value  { font-size:26px; font-weight:800; color:#fff; text-shadow:0 1px 1px rgba(0,0,0,.2); }
  .badge-soft { background: rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); font-weight:600; }
  .ap-card { border:1px solid var(--b); border-radius:12px; background:#fff; padding:12px; }
  .filters-grid { display:grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap:10px; }
  .tablewrap { border:1px solid var(--b); border-radius:8px; overflow:auto; height:55vh; }
  table { border-collapse:collapse; width:100%; font-size:12px; min-width:1100px; }
  th, td { border-bottom:1px solid #eee; padding:6px 8px; white-space:nowrap; }
  thead th { position:sticky; top:0; background:#f7f7f7; z-index:1; }
</style>
<?php if ($IFRAME): ?>
</head><body>
<?php endif; ?>

<div class="container-fluid">

  <!-- Header -->
  <div class="row g-3 mb-3">
    <div class="col">
      <h4 class="m-0 text-primary">Repremundo | Análisis Inicial</h4>
    </div>
  </div>

  <!-- Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <a class="text-decoration-none" href="?<?= qs(['dataset'=>'almacenes','p'=>1]) ?>#grid">
        <div class="card card-summary card-azul text-center">
          <div class="card-body">
            <div class="card-title">Total de Almacenes</div>
            <div class="card-value"><?= number_format($total_almac) ?></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a class="text-decoration-none" href="?<?= qs(['dataset'=>'usuarios','p'=>1]) ?>#grid">
        <div class="card card-summary card-verde text-center">
          <div class="card-body">
            <div class="card-title">Total de Usuarios</div>
            <div class="card-value"><?= number_format($total_usuarios) ?></div>
            <div class="mt-1"><span class="badge badge-soft">con Rol</span></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a class="text-decoration-none" href="?<?= qs(['dataset'=>'articulos','p'=>1]) ?>#grid">
        <div class="card card-summary card-morado text-center">
          <div class="card-body">
            <div class="card-title">Total de Artículos</div>
            <div class="card-value"><?= number_format($total_art) ?></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a class="text-decoration-none" href="?<?= qs(['dataset'=>'proveedores','p'=>1]) ?>#grid">
        <div class="card card-summary card-ambar text-center">
          <div class="card-body">
            <div class="card-title">Clientes (c_proveedores)</div>
            <div class="card-value"><?= number_format($total_prov) ?></div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Filtros -->
  <form class="ap-card mb-3" method="get" id="formFiltros">
    <input type="hidden" name="dataset" value="<?= htmlspecialchars($dataset) ?>">
    <?php if ($IFRAME): ?><input type="hidden" name="iframe" value="1"><?php endif; ?>
    <div class="filters-grid">
      <div>
        <label class="form-label mb-1">Empresa</label>
        <select name="empresa_id" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach ($empresas_ds as $e): $v=$e['empresa']??''; ?>
            <option value="<?= htmlspecialchars($v) ?>" <?= ($empresa_id!=='' && (string)$empresa_id===(string)$v)?'selected':'' ?>>
              <?= htmlspecialchars($v) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label mb-1">Activo</label>
        <select name="activo" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="1" <?= $activo==='1'?'selected':'' ?>>Sí</option>
          <option value="0" <?= $activo==='0'?'selected':'' ?>>No</option>
        </select>
      </div>

      <div data-show="usuarios">
        <label class="form-label mb-1">Rol</label>
        <select name="rol" class="form-select form-select-sm">
          <option value="">(Todos)</option>
          <?php foreach ($roles as $r): $rv=$r['rol']; ?>
            <option value="<?= htmlspecialchars($rv) ?>" <?= ($rol!=='' && strcasecmp($rol,$rv)===0)?'selected':'' ?>>
              <?= htmlspecialchars($rv) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label mb-1">Buscar</label>
        <input type="text" class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q??'') ?>" placeholder="Clave, nombre, correo, ciudad...">
      </div>

      <div style="display:flex; gap:8px; align-items:end;">
        <div>
          <label class="form-label mb-1">Filas/pág.</label>
          <input type="number" class="form-control form-control-sm" name="per_page" value="<?= (int)$per_page ?>" min="10" step="5">
        </div>
        <div style="display:flex; gap:8px; margin-bottom:2px;">
          <button class="btn btn-sm btn-primary" type="submit">Aplicar</button>
          <a class="btn btn-sm btn-outline-secondary" href="?<?= $IFRAME?'iframe=1&':'' ?>dataset=<?= urlencode($dataset) ?>">Limpiar</a>
          <a class="btn btn-sm btn-success" href="?<?= qs(['export'=>'csv']) ?>">Exportar CSV</a>
        </div>
      </div>
    </div>
  </form>

  <!-- Grilla -->
  <div id="grid" class="ap-card tablewrap">
    <table id="tablaDatos" class="table table-sm table-bordered table-striped w-100">
      <thead class="table-light">
        <tr>
          <?php foreach ($cols as $c): ?><th><?= htmlspecialchars($c) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="<?= count($cols) ?>">Sin resultados.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <?php foreach ($cols as $c):
              $val = array_key_exists($c,$r) ? $r[$c] : '';
              if ($c==='Activo') {
                $val = in_array(strtoupper((string)$val),['1','S','SI','Y','YES']) ? '✔️' : '❌';
              }
            ?>
              <td><?= htmlspecialchars((string)$val) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginación -->
  <?php $prev=$page-1; $next=$page+1; $disablePrev=$page<=1; $disableNext=$page>=$pages; ?>
  <div class="d-flex gap-2 align-items-center mt-2 flex-wrap">
    <a class="btn btn-sm btn-outline-secondary <?= $disablePrev?'disabled':'' ?>" href="?<?= qs(['p'=>1]) ?>">« Primero</a>
    <a class="btn btn-sm btn-outline-secondary <?= $disablePrev?'disabled':'' ?>" href="?<?= qs(['p'=>$prev]) ?>">‹ Anterior</a>
    <span class="text-muted">Página <strong><?= $page ?></strong> de <strong><?= $pages ?></strong> — Registros: <strong><?= number_format($total) ?></strong></span>
    <a class="btn btn-sm btn-outline-secondary <?= $disableNext?'disabled':'' ?>" href="?<?= qs(['p'=>$next]) ?>">Siguiente ›</a>
    <a class="btn btn-sm btn-outline-secondary <?= $disableNext?'disabled':'' ?>" href="?<?= qs(['p'=>$pages]) ?>">Último »</a>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  // DataTables solo para scroll/ordenamiento local; paginación real es del servidor
  $('#tablaDatos').DataTable({
    paging: false, searching: false, info: false, ordering: true,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
    scrollX: true, scrollY: '45vh', scrollCollapse: true, autoWidth: true, deferRender: true
  });

  // Mostrar/ocultar filtros por dataset
  const ds = '<?= $dataset ?>';
  document.querySelectorAll('[data-show]').forEach(el=>{
    el.style.display = (el.getAttribute('data-show')===ds) ? '' : 'none';
  });

  // Anclar a la grilla si viene con #grid
  if (window.location.hash==='#grid') { document.getElementById('grid')?.scrollIntoView({behavior:'smooth'}); }
});
</script>

<?php if ($IFRAME): ?>
<script>
(function(){ // auto-resize iframe
  function postHeight(){
    var h = document.documentElement.scrollHeight || document.body.scrollHeight;
    try { window.parent.postMessage({ type:'assistpro:resize', feature:'tr', height:h }, '*'); } catch(e){}
  }
  var ro = new ResizeObserver(postHeight);
  ro.observe(document.body);
  window.addEventListener('load', postHeight);
  document.getElementById('formFiltros')?.addEventListener('submit', function(){ setTimeout(postHeight, 400); });
})();
</script>
</body>
</html>
<?php else: ?>
<?php include __DIR__.'/_menu_global_end.php'; ?>
<?php endif; ?>
