<?php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function table_exists($t){
  return (int)db_val(
    "SELECT COUNT(*)
     FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = :t",
    [':t'=>$t]
  ) > 0;
}

function column_exists($table, $col){
  return (int)db_val(
    "SELECT COUNT(*)
     FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = :t AND column_name = :c",
    [':t'=>$table, ':c'=>$col]
  ) > 0;
}

function detect_column($table, $candidates){
  foreach($candidates as $c){
    if(column_exists($table, $c)) return $c;
  }
  return null;
}

function fmt_ddmmyyyy($dt){
  if(!$dt) return '';
  $ts = strtotime($dt);
  if(!$ts) return (string)$dt;
  return date('d/m/Y', $ts);
}

// ==================== Filtros ====================
$q        = trim((string)($_GET['q'] ?? ''));
$estado   = trim((string)($_GET['estado'] ?? ''));
$almacenp = (int)($_GET['cve_almacenp'] ?? 0);
$desde    = trim((string)($_GET['desde'] ?? ''));
$hasta    = trim((string)($_GET['hasta'] ?? ''));

// Default fechas: última semana (si el usuario no envía rango)
if($desde === '' && $hasta === ''){
  $hasta = date('Y-m-d');
  $desde = date('Y-m-d', strtotime('-7 days'));
}

$where = [];
$params = [];

if($q !== ''){
  $where[] = "(i.folio LIKE :q OR i.observaciones LIKE :q)";
  $params[':q'] = "%$q%";
}
if($estado !== '' && $estado !== 'ALL'){
  $where[] = "i.estado = :estado";
  $params[':estado'] = $estado;
}
if($almacenp > 0){
  $where[] = "i.cve_almacenp = :alm";
  $params[':alm'] = $almacenp;
}
if($desde !== ''){
  $where[] = "DATE(i.fecha_creacion) >= :desde";
  $params[':desde'] = $desde;
}
if($hasta !== ''){
  $where[] = "DATE(i.fecha_creacion) <= :hasta";
  $params[':hasta'] = $hasta;
}

$wsql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

// ==================== Catálogos para filtros ====================
$almacenes = db_all("
  SELECT CAST(id AS UNSIGNED) AS cve_almacenp,
         TRIM(COALESCE(clave,'')) AS clave,
         TRIM(COALESCE(nombre,'')) AS nombre
  FROM c_almacenp
  ORDER BY nombre
");

$estados = ['CREADO','EN_CONTEO','CERRADO','CANCELADO'];

// ==================== Resolver tabla detalle BLs ====================
$detail_candidates = ['det_planifica_inventario','t_ubicacioninventario'];
$detail_table = null;
foreach($detail_candidates as $t){
  if(table_exists($t)){ $detail_table = $t; break; }
}

// detectar columnas: FK y BL
$fk_col = null;
$bl_col = null;

if($detail_table){
  $fk_col = detect_column($detail_table, ['id_inventario','inventario_id','cve_inventario']);
  // IMPORTANTE: BL real = codigocsd (prioritario)
  $bl_col = detect_column($detail_table, ['codigocsd','bl','codigo','ubicacion','cod_bl','bin_location']);
}

// subquery para contar BLs (evita JOIN y evita errores por columnas)
$bls_expr = "0";
if($detail_table && $fk_col && $bl_col){
  // count distinct del BL (codigocsd)
  $bls_expr = "(SELECT COUNT(DISTINCT d.`$bl_col`) 
                FROM `$detail_table` d 
                WHERE d.`$fk_col` = i.id_inventario)";
}

// ==================== Query principal ====================
$sql = "
SELECT
  i.id_inventario,
  i.folio,
  i.tipo_inventario,
  i.estado,
  i.cve_cia,
  i.cve_almacenp,
  i.fecha_creacion,
  i.usuario_creo,
  i.observaciones,
  TRIM(COALESCE(ap.clave,''))  AS almacen_clave,
  TRIM(COALESCE(ap.nombre,'')) AS almacen_nombre,
  TRIM(COALESCE(u.nombre_completo,'')) AS usuario_nombre,
  $bls_expr AS bls_cnt
FROM inventario i
LEFT JOIN c_almacenp ap
  ON CAST(ap.id AS UNSIGNED) = i.cve_almacenp
LEFT JOIN c_usuario u
  ON u.id_user = i.usuario_creo
$wsql
ORDER BY i.id_inventario DESC
LIMIT 2000
";

$rows = db_all($sql, $params);

// ==================== KPIs ====================
$total = count($rows);
$k_creado = 0; $k_en = 0; $k_cerr = 0; $k_hoy = 0;
$hoy = date('Y-m-d');

foreach($rows as $r){
  $st = strtoupper(trim((string)$r['estado']));
  if($st === 'CREADO') $k_creado++;
  if($st === 'EN_CONTEO') $k_en++;
  if($st === 'CERRADO') $k_cerr++;
  $f = substr((string)$r['fecha_creacion'],0,10);
  if($f === $hoy) $k_hoy++;
}
?>
<style>
  .ap-page-title{ font-weight:700; letter-spacing:.2px; }
  .ap-kpi{ border-radius:14px; box-shadow:0 6px 18px rgba(16,24,40,.06); border:1px solid #e8eef9; }
  .ap-kpi .label{ font-size:12px; color:#6b7280; }
  .ap-kpi .value{ font-size:26px; font-weight:800; color:#0b3a88; }
  .ap-card{ border-radius:14px; border:1px solid #e8eef9; box-shadow:0 6px 18px rgba(16,24,40,.06); }
  .ap-table thead th{ font-size:12px; text-transform:uppercase; letter-spacing:.6px; color:#6b7280; }
  .ap-table td{ font-size:12.5px; vertical-align:middle; }
  .badge-soft{ background:#eef5ff; color:#0b3a88; border:1px solid #d9e8ff; }
  .badge-ok{ background:#ecfdf5; color:#027a48; border:1px solid #abefc6; }
  .badge-warn{ background:#fffbeb; color:#b54708; border:1px solid #fedf89; }
  .badge-dead{ background:#fef2f2; color:#b42318; border:1px solid #fecaca; }
</style>

<div class="container-fluid mt-3">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="ap-page-title mb-0">📊 Administración de Inventarios</h3>
      <div class="text-muted" style="font-size:12px;">
        Fuente: inventario <?= $detail_table ? " · detalle: ".$detail_table." (BL=".$bl_col.")" : " · detalle: (no detectado)" ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="/public/inventarios/planeacion/planificar_inventarios.php" class="btn btn-primary btn-sm">
        + Nuevo plan
      </a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="ap-kpi p-3"><div class="label">Total (filtro aplicado)</div><div class="value"><?= (int)$total ?></div></div></div>
    <div class="col-md-3"><div class="ap-kpi p-3"><div class="label">Creados</div><div class="value"><?= (int)$k_creado ?></div></div></div>
    <div class="col-md-3"><div class="ap-kpi p-3"><div class="label">En conteo</div><div class="value"><?= (int)$k_en ?></div></div></div>
    <div class="col-md-3"><div class="ap-kpi p-3"><div class="label">Creados hoy</div><div class="value"><?= (int)$k_hoy ?></div></div></div>
  </div>

  <div class="ap-card p-3 mb-3">
    <form class="row g-2" method="GET">
      <div class="col-md-3">
        <label class="form-label" style="font-size:12px;">Buscar</label>
        <input type="text" class="form-control form-control-sm" name="q" value="<?= h($q) ?>" placeholder="Folio / Observaciones">
      </div>
      <div class="col-md-2">
        <label class="form-label" style="font-size:12px;">Estado</label>
        <select class="form-select form-select-sm" name="estado">
          <option value="ALL">Todos</option>
          <?php foreach($estados as $e): ?>
            <option value="<?= h($e) ?>" <?= ($estado===$e?'selected':'') ?>><?= h($e) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" style="font-size:12px;">Almacén</label>
        <select class="form-select form-select-sm" name="cve_almacenp">
          <option value="0">Todos</option>
          <?php foreach($almacenes as $a): ?>
            <option value="<?= (int)$a['cve_almacenp'] ?>" <?= ($almacenp==(int)$a['cve_almacenp']?'selected':'') ?>>
              <?= h(($a['clave'] ?: 'SINCLAVE').' · '.$a['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label" style="font-size:12px;">Desde</label>
        <input type="date" class="form-control form-control-sm" name="desde" value="<?= h($desde) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label" style="font-size:12px;">Hasta</label>
        <input type="date" class="form-control form-control-sm" name="hasta" value="<?= h($hasta) ?>">
      </div>
      <div class="col-12 d-flex gap-2 mt-2">
        <button class="btn btn-primary btn-sm">Aplicar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/public/inventarios/administracion/admin_inventarios.php">Limpiar</a>
        <div class="ms-auto text-muted" style="font-size:12px; padding-top:6px;">
          Máx. 2000 registros
        </div>
      </div>
    </form>
  </div>

  <div class="ap-card p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm ap-table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px;">ID</th>
            <th style="width:170px;">Folio</th>
            <th style="width:90px;">Tipo</th>
            <th style="width:110px;">Estado</th>
            <th>Almacén</th>
            <th style="width:220px;">Usuario</th>
            <th style="width:140px;">Fecha</th>
            <th style="width:120px;">Ubicaciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted p-4">Sin resultados.</td></tr>
        <?php else: foreach($rows as $r):
          $st = strtoupper(trim((string)$r['estado']));
          $badge = 'badge-soft';
          if($st==='CERRADO') $badge='badge-ok';
          else if($st==='EN_CONTEO') $badge='badge-warn';
          else if($st==='CANCELADO') $badge='badge-dead';

          $almTxt = trim(($r['almacen_clave'] ?? '').' '.$r['almacen_nombre']);
          if($almTxt==='') $almTxt = '—';

          $usrTxt = trim((string)($r['usuario_nombre'] ?? ''));
          if($usrTxt==='') $usrTxt = '—';

          $bls = (int)($r['bls_cnt'] ?? 0);
        ?>
          <tr>
            <td><?= (int)$r['id_inventario'] ?></td>
            <td>
              <div class="fw-bold"><?= h($r['folio']) ?></div>
              <div class="text-muted" style="font-size:11px;"><?= h($r['observaciones'] ?? '') ?></div>
            </td>
            <td><span class="badge badge-soft"><?= h($r['tipo_inventario']) ?></span></td>
            <td><span class="badge <?= $badge ?>"><?= h($st) ?></span></td>
            <td><?= h($almTxt) ?></td>
            <td><?= h($usrTxt) ?></td>
            <td><?= h(fmt_ddmmyyyy($r['fecha_creacion'])) ?></td>
            <td class="text-center"><span class="badge badge-soft"><?= $bls ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../../bi/_menu_global_end.php'; ?>
