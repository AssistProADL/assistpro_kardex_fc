<?php
/* ===========================================================
   public/dashboard/kardex_productividad.php
   Productividad Operativa – dentro del frame AssistPro ER® BI
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';
//@session_start();

/* ================= Frame (menú global) =================== */
$activeSection = 'operaciones';
$activeItem = 'productividad';
$pageTitle = 'Productividad Operativa · AssistPro ER®';
include __DIR__ . '/../bi/_menu_global.php';

/* ================== Parámetros de filtro ================== */
$hoy = date('Y-m-d');
$desde_def = date('Y-m-d', strtotime('-7 days'));

$desde = $_GET['desde'] ?? $desde_def;
$hasta = $_GET['hasta'] ?? $hoy;
$almacen = $_GET['almacen'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$art = $_GET['art'] ?? '';
$lote = $_GET['lote'] ?? '';
$proveedor = $_GET['proveedor'] ?? '';
$proyecto = $_GET['proyecto'] ?? '';

/* ================== ACL (seguridad por sesión) ================== */
$username = $_SESSION['username'] ?? '';
$almacenACL = $_SESSION['cve_almac'] ?? ''; // almacén activo
$empresasACL = $_SESSION['empresas'] ?? [];  // array de empresa_id permitidos

// Helper para cláusula empresa_id IN (...)
function acl_where_emp_sql($alias = 'v')
{
  $ids = $_SESSION['empresas'] ?? [];
  if (!$ids || !is_array($ids))
    return "1=0";
  $place = implode(',', array_map('intval', $ids));
  return "$alias.empresa_id IN ($place)";
}
function acl_where_alm_sql($alias = 'v')
{
  $alm = $_SESSION['cve_almac'] ?? '';
  return $alm ? "TRIM($alias.cve_almac)=TRIM(" . dbq_quote($alm) . ")" : "1=1";
}
// pequeño helper para comillas seguras (solo aquí, ya se usa PDO abajo)
function dbq_quote($s)
{
  return "'" . str_replace("'", "''", $s) . "'";
}

/* =============== WHERE dinámico (sobre vista v2) =============== */
$where = [
  "DATE(v.fecha_ts) BETWEEN :desde AND :hasta",
  acl_where_emp_sql('v'),
  acl_where_alm_sql('v')
];

$params = [':desde' => $desde, ':hasta' => $hasta];

if ($almacen !== '') {
  $where[] = "TRIM(v.cve_almac)     = TRIM(:almacen)";
  $params[':almacen'] = $almacen;
}
if ($usuario !== '') {
  $where[] = "TRIM(v.id_usuario)    = TRIM(:usuario)";
  $params[':usuario'] = $usuario;
}
if ($tipo !== '') {
  $where[] = "v.tipo_mov_txt        = :tipo";
  $params[':tipo'] = $tipo;
}
if ($art !== '') {
  $where[] = "TRIM(v.cve_articulo)  = TRIM(:art)";
  $params[':art'] = $art;
}
if ($lote !== '') {
  $where[] = "v.lote LIKE :lote";
  $params[':lote'] = "%$lote%";
}
if ($proveedor !== '') {
  $where[] = "TRIM(v.cve_proveedor) = TRIM(:proveedor)";
  $params[':proveedor'] = $proveedor;
}
if ($proyecto !== '') {
  $where[] = "TRIM(v.cve_proyecto)  = TRIM(:proyecto)";
  $params[':proyecto'] = $proyecto;
}

$where_sql = implode(' AND ', $where);

/* ================== Endpoints AJAX (JSON/CSV) ================== */
if (isset($_GET['action'])) {
  $a = $_GET['action'];

  if ($a === 'kpis') {
    $sql = "
      SELECT
        COUNT(*) AS total_mov,
        SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'ENTRADA%' THEN 1 ELSE 0 END) AS entradas,
        SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'SALIDA%'  THEN 1 ELSE 0 END) AS salidas,
        SUM(v.signo * v.cantidad) AS balance_neto
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
    ";
    $k = db_one($sql, $params) ?? ['total_mov' => 0, 'entradas' => 0, 'salidas' => 0, 'balance_neto' => 0];
    header('Content-Type: application/json');
    echo json_encode($k);
    exit;
  }

  if ($a === 'kpis_extra') {
    $base = db_one("
      WITH base AS (
        SELECT v.fecha_ts, v.fecha, v.tipo_mov_txt
        FROM v_kardex_enriquecido_v2 v
        WHERE $where_sql
      )
      SELECT
        GREATEST(TIMESTAMPDIFF(HOUR, MIN(fecha_ts), MAX(fecha_ts)),1) AS horas,
        COUNT(*) AS total_mov,
        SUM(CASE WHEN UPPER(tipo_mov_txt) LIKE 'ENTRADA%' THEN 1 ELSE 0 END) AS entradas,
        SUM(CASE WHEN UPPER(tipo_mov_txt) LIKE 'SALIDA%'  THEN 1 ELSE 0 END) AS salidas,
        COUNT(DISTINCT fecha) AS dias
      FROM base
    ", $params) ?? ['horas' => 1, 'total_mov' => 0, 'entradas' => 0, 'salidas' => 0, 'dias' => 0];

    $top_oper = db_one("
      SELECT operador, COUNT(*) c
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY operador
      ORDER BY c DESC LIMIT 1
    ", $params) ?? ['operador' => '-', 'c' => 0];

    $top_alma = db_one("
      SELECT v.cve_almac a, COUNT(*) c
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY v.cve_almac
      ORDER BY c DESC LIMIT 1
    ", $params) ?? ['a' => '-', 'c' => 0];

    $top_prod = db_one("
      SELECT v.cve_articulo p, COUNT(*) c
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY v.cve_articulo
      ORDER BY c DESC LIMIT 1
    ", $params) ?? ['p' => '-', 'c' => 0];

    $movhora = $base['total_mov'] / max(1, $base['horas']);
    $pct_e = $base['total_mov'] > 0 ? $base['entradas'] * 100 / $base['total_mov'] : 0;
    $pct_s = $base['total_mov'] > 0 ? $base['salidas'] * 100 / $base['total_mov'] : 0;

    $out = [
      'mov_hora' => round($movhora, 2),
      'pct_entradas' => round($pct_e, 1),
      'pct_salidas' => round($pct_s, 1),
      'dias_activos' => (int) $base['dias'],
      'top_operador' => $top_oper['operador'],
      'top_operador_cnt' => (int) $top_oper['c'],
      'top_almacen' => $top_alma['a'],
      'top_almacen_cnt' => (int) $top_alma['c'],
      'top_producto' => $top_prod['p'],
      'top_producto_cnt' => (int) $top_prod['c'],
    ];
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
  }

  if ($a === 'daily') {
    $rows = db_all("
      SELECT v.fecha,
             COUNT(*) AS total_mov,
             SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'ENTRADA%' THEN 1 ELSE 0 END) AS entradas,
             SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'SALIDA%'  THEN 1 ELSE 0 END) AS salidas
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY v.fecha
      ORDER BY v.fecha
    ", $params) ?? [];
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
  }

  if ($a === 'usuarios') {
    $rows = db_all("
      SELECT v.operador,
             COUNT(*) AS total_mov,
             SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'ENTRADA%' THEN 1 ELSE 0 END) AS entradas,
             SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'SALIDA%'  THEN 1 ELSE 0 END) AS salidas
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY v.operador
      ORDER BY total_mov DESC
      LIMIT 20
    ", $params) ?? [];
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
  }

  if ($a === 'almacenes') {
    $rows = db_all("
      SELECT v.cve_almac AS des_almac,
             COUNT(*) AS total_mov,
             SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'ENTRADA%' THEN 1 ELSE 0 END) AS entradas,
             SUM(CASE WHEN UPPER(v.tipo_mov_txt) LIKE 'SALIDA%'  THEN 1 ELSE 0 END) AS salidas
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY v.cve_almac
      ORDER BY total_mov DESC
      LIMIT 20
    ", $params) ?? [];
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
  }

  if ($a === 'topprov') {
    $rows = db_all("
      SELECT COALESCE(v.des_proveedor, v.cve_proveedor) AS des_proveedor,
             COUNT(*) AS total_mov
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY COALESCE(v.des_proveedor, v.cve_proveedor)
      ORDER BY total_mov DESC
      LIMIT 20
    ", $params) ?? [];
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
  }

  if ($a === 'topprod') {
    $rows = db_all("
      SELECT
        TRIM(v.cve_articulo) AS cve_articulo,
        COALESCE(v.desc_articulo,'') AS desc_articulo,
        COUNT(*) AS movimientos,
        SUM(CASE WHEN v.signo =  1 THEN v.cantidad ELSE 0 END) AS total_entrada,
        SUM(CASE WHEN v.signo = -1 THEN v.cantidad ELSE 0 END) AS total_salida
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      GROUP BY TRIM(v.cve_articulo), COALESCE(v.desc_articulo,'')
      HAVING movimientos > 0
      ORDER BY movimientos DESC
      LIMIT 200
    ", $params) ?? [];
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
  }

  if ($a === 'lotes_por_art') {
    if ($art === '') {
      header('Content-Type: application/json');
      echo json_encode([]);
      exit;
    }
    $rows = db_all("
      SELECT DISTINCT v.lote
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
        AND TRIM(v.cve_articulo) = TRIM(:art2)
        AND COALESCE(v.lote,'') <> ''
      ORDER BY v.lote
    ", array_merge($params, [':art2' => $art])) ?? [];
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
  }

  if ($a === 'export_csv') {
    $rows = db_all("
      SELECT v.fecha,
             v.hora,
             v.tipo_mov_txt AS tipo_mov,
             v.cve_articulo,
             v.desc_articulo,
             v.cantidad,
             v.lote,
             v.cve_almac,
             v.operador,
             v.cve_proveedor,
             v.cve_proyecto
      FROM v_kardex_enriquecido_v2 v
      WHERE $where_sql
      ORDER BY v.fecha_ts DESC
    ", $params) ?? [];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kardex_productividad.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['fecha', 'hora', 'tipo_mov', 'cve_articulo', 'desc_articulo', 'cantidad', 'lote', 'cve_almac', 'operador', 'cve_proveedor', 'cve_proyecto']);
    foreach ($rows as $r)
      fputcsv($out, $r);
    fclose($out);
    exit;
  }

  http_response_code(400);
  echo json_encode(['error' => 'Acción no válida']);
  exit;
}

/* ================= Catálogos (con ACL aplicado) ================= */
$cat_almac = db_all("
  SELECT a.clave, a.des_almac
  FROM v_almacen_compat a
  WHERE " . acl_where_alm_sql('a') . "
  ORDER BY a.des_almac
");
$cat_user = db_all("
  SELECT u.cve_usuario, u.nombre
  FROM v_usuario_compat u
  ORDER BY nombre
");
$cat_tipo = db_all("SELECT DISTINCT nombre AS tipo_mov FROM t_tipomovimiento WHERE COALESCE(Activo,'1') IN ('1','S','SI','TRUE') ORDER BY nombre");
$cat_art = db_all("
  SELECT cve_articulo, COALESCE(des_articulo,'') AS desc_articulo
  FROM c_articulo
  ORDER BY desc_articulo
  LIMIT 2000
");
$cat_prov = db_all("
  SELECT DISTINCT v.cve_proveedor, COALESCE(v.des_proveedor, v.cve_proveedor) AS des_proveedor
  FROM v_kardex_enriquecido_v2 v
  WHERE " . acl_where_emp_sql('v') . " AND " . acl_where_alm_sql('v') . "
  ORDER BY des_proveedor
");
$cat_proy = db_all("
  SELECT DISTINCT v.cve_proyecto, COALESCE(v.des_proyecto, v.cve_proyecto) AS des_proyecto
  FROM v_kardex_enriquecido_v2 v
  WHERE " . acl_where_emp_sql('v') . " AND " . acl_where_alm_sql('v') . "
  ORDER BY des_proyecto
");
?>

<div class="container-fluid">

  <!-- Encabezado en frame -->
  <div class="ap-card p-4 mb-3">
    <h4 class="mb-1" style="color:#0a2a6b;">Productividad Operativa</h4>
    <p class="text-secondary m-0">Análisis de movimientos por almacén, usuario, proyecto y proveedor. (Gráficas 250px)
    </p>
  </div>

  <!-- Filtros -->
  <form class="ap-card p-3 mb-3" method="get" id="filtros">
    <div class="row g-2">
      <div class="col-6 col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Almacén</label>
        <select name="almacen" class="form-select" id="sel-almacen">
          <option value="">(Activo: <?= htmlspecialchars($almacenACL ?: '—') ?>)</option>
          <?php foreach ($cat_almac as $c): ?>
            <option value="<?= $c['clave'] ?>" <?= ($almacen === $c['clave'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($c['des_almac']) ?> (<?= $c['clave'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Usuario</label>
        <select name="usuario" class="form-select">
          <option value="">(Todos)</option>
          <?php foreach ($cat_user as $u): ?>
            <option value="<?= $u['cve_usuario'] ?>" <?= ($usuario === $u['cve_usuario'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($u['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label">Proveedor</label>
        <select name="proveedor" class="form-select">
          <option value="">(Todos)</option>
          <?php foreach ($cat_prov as $pv): ?>
            <option value="<?= $pv['cve_proveedor'] ?>" <?= ($proveedor === $pv['cve_proveedor'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($pv['des_proveedor']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label">Proyecto</label>
        <select name="proyecto" class="form-select">
          <option value="">(Todos)</option>
          <?php foreach ($cat_proy as $pr): ?>
            <option value="<?= $pr['cve_proyecto'] ?>" <?= ($proyecto === $pr['cve_proyecto'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($pr['des_proyecto']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Producto</label>
        <select name="art" id="sel-art" class="form-select">
          <option value="">(Todos)</option>
          <?php foreach ($cat_art as $a): ?>
            <option value="<?= $a['cve_articulo'] ?>" <?= ($art === $a['cve_articulo'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($a['desc_articulo'] !== '' ? $a['desc_articulo'] : $a['cve_articulo']) ?>
              (<?= $a['cve_articulo'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Lote</label>
        <select name="lote" id="sel-lote" class="form-select">
          <option value="">(Todos)</option>
          <?php if ($lote !== ''): ?>
            <option selected value="<?= htmlspecialchars($lote) ?>"><?= htmlspecialchars($lote) ?></option><?php endif; ?>
        </select>
      </div>

      <div class="col-12 col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100">Aplicar</button>
      </div>
    </div>
  </form>

  <!-- KPIs fila 1 -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Total Movimientos</div>
        <div class="h4 m-0" id="kpi-total">-</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Entradas</div>
        <div class="h4 m-0" id="kpi-entradas">-</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Salidas</div>
        <div class="h4 m-0" id="kpi-salidas">-</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="ap-card p-3">
        <div class="text-secondary small">Balance Neto</div>
        <div class="h4 m-0" id="kpi-balance">-</div>
      </div>
    </div>
  </div>

  <!-- KPIs fila 2 -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="ap-card p-3">
        <div class="text-secondary small">Mov/Hora</div>
        <div class="h4 m-0" id="kpi-movhora">-</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="ap-card p-3">
        <div class="text-secondary small">% Entradas</div>
        <div class="h4 m-0" id="kpi-pctent">-</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="ap-card p-3">
        <div class="text-secondary small">% Salidas</div>
        <div class="h4 m-0" id="kpi-pctsal">-</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="ap-card p-3">
        <div class="text-secondary small">Días Activos</div>
        <div class="h4 m-0" id="kpi-diasact">-</div>
      </div>
    </div>
    <div class="col-12 col-md-2">
      <div class="ap-card p-3">
        <div class="text-secondary small">Top Operador</div>
        <div class="h6 m-0" id="kpi-topoper">-</div>
        <div class="text-secondary small"><small id="kpi-topoper-cnt"></small></div>
      </div>
    </div>
    <div class="col-12 col-md-2">
      <div class="ap-card p-3">
        <div class="text-secondary small">Top Almacén</div>
        <div class="h6 m-0" id="kpi-topalm">-</div>
        <div class="text-secondary small"><small id="kpi-topalm-cnt"></small></div>
      </div>
    </div>
  </div>

  <!-- Gráficas (250px) -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
      <div class="ap-card p-3">
        <h6 class="mb-2">Tendencia diaria</h6>
        <div style="height:250px"><canvas id="chDaily"></canvas></div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="ap-card p-3">
        <h6 class="mb-2">Top usuarios por movimientos</h6>
        <div style="height:250px"><canvas id="chUsers"></canvas></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="ap-card p-3">
        <h6 class="mb-2">Actividad por almacén (Top)</h6>
        <div style="height:250px"><canvas id="chAlmacenes"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Gráfica Top Proveedores -->
  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="ap-card p-3">
        <h6 class="mb-2">Top proveedores por movimientos</h6>
        <div style="height:250px"><canvas id="chProveedores"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Tabla Top Productos -->
  <div class="ap-card p-3">
    <div class="d-flex align-items-center justify-content-between">
      <h6 class="mb-2">Top productos por rotación (filtrado)</h6>
      <button class="btn btn-outline-secondary btn-sm" id="btn-export">Exportar CSV</button>
    </div>
    <div class="table-responsive">
      <table id="tblTopProd" class="table table-striped table-hover w-100">
        <thead>
          <tr>
            <th>Clave</th>
            <th>Descripción</th>
            <th>Movs</th>
            <th>Total Entrada</th>
            <th>Total Salida</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div> <!-- /container-fluid -->

<!-- Librerías específicas del dashboard -->
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.7/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.7/datatables.min.js"></script>

<script>
  (function () {
    const qs = new URLSearchParams(window.location.search);
    const base = 'kardex_productividad.php?' + qs.toString();
    const $ = (id) => document.getElementById(id);

    // KPIs base
    fetch(base + '&action=kpis').then(r => r.json()).then(d => {
      $('kpi-total').textContent = (d.total_mov ?? 0).toLocaleString();
      $('kpi-entradas').textContent = (d.entradas ?? 0).toLocaleString();
      $('kpi-salidas').textContent = (d.salidas ?? 0).toLocaleString();
      $('kpi-balance').textContent = (d.balance_neto ?? 0).toLocaleString();
    });

    // KPIs extra
    fetch(base + '&action=kpis_extra').then(r => r.json()).then(x => {
      $('kpi-movhora').textContent = (x.mov_hora ?? 0).toLocaleString();
      $('kpi-pctent').textContent = ((x.pct_entradas ?? 0).toFixed(1)) + '%';
      $('kpi-pctsal').textContent = ((x.pct_salidas ?? 0).toFixed(1)) + '%';
      $('kpi-diasact').textContent = (x.dias_activos ?? 0).toLocaleString();
      $('kpi-topoper').textContent = x.top_operador ?? '-';
      $('kpi-topoper-cnt').textContent = (x.top_operador_cnt ?? 0) + ' movs';
      $('kpi-topalm').textContent = x.top_almacen ?? '-';
      $('kpi-topalm-cnt').textContent = (x.top_almacen_cnt ?? 0) + ' movs';
    });

    // Gráfica: tendencia diaria
    fetch(base + '&action=daily').then(r => r.json()).then(rows => {
      const labels = rows.map(x => x.fecha);
      const total = rows.map(x => Number(x.total_mov));
      const ent = rows.map(x => Number(x.entradas));
      const sal = rows.map(x => Number(x.salidas));
      new Chart(document.getElementById('chDaily'), {
        type: 'line',
        data: {
          labels, datasets: [
            { label: 'Total', data: total },
            { label: 'Entradas', data: ent },
            { label: 'Salidas', data: sal }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
    });

    // Gráfica: usuarios
    fetch(base + '&action=usuarios').then(r => r.json()).then(rows => {
      const labels = rows.map(x => x.operador);
      const data = rows.map(x => Number(x.total_mov));
      new Chart(document.getElementById('chUsers'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Movimientos', data }] },
        options: { responsive: true, maintainAspectRatio: false }
      });
    });

    // Gráfica: almacenes (Top)
    fetch(base + '&action=almacenes').then(r => r.json()).then(rows => {
      const labels = rows.map(x => x.des_almac);
      const data = rows.map(x => Number(x.total_mov));
      new Chart(document.getElementById('chAlmacenes'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Movimientos', data }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
      });
    });

    // Gráfica: Top Proveedores
    fetch(base + '&action=topprov').then(r => r.json()).then(rows => {
      const labels = rows.map(x => x.des_proveedor);
      const data = rows.map(x => Number(x.total_mov));
      new Chart(document.getElementById('chProveedores'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Movimientos', data }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
      });
    });

    // Tabla: Top productos (FIX: dataSrc: '')
    new DataTable('#tblTopProd', {
      paging: true, searching: true, info: true, pageLength: 25, order: [[2, 'desc']],
      ajax: { url: base + '&action=topprod', dataSrc: '' },
      columns: [
        { data: 'cve_articulo' },
        { data: 'desc_articulo' },
        { data: 'movimientos', render: (d) => Number(d).toLocaleString() },
        { data: 'total_entrada', render: (d) => Number(d).toLocaleString() },
        { data: 'total_salida', render: (d) => Number(d).toLocaleString() }
      ]
    });

    // Export CSV
    document.getElementById('btn-export')?.addEventListener('click', () => { window.location = base + '&action=export_csv'; });

    // Dependencia Lote ⇢ Producto
    const selArt = document.getElementById('sel-art');
    const selLote = document.getElementById('sel-lote');
    function cargarLotes() {
      const artVal = selArt.value;
      selLote.innerHTML = '<option value="">(Todos)</option>';
      if (!artVal) return;
      const url = new URL(window.location.href);
      url.searchParams.set('action', 'lotes_por_art');
      url.searchParams.set('art', artVal);
      fetch(url.toString()).then(r => r.json()).then(rows => {
        rows.forEach(x => {
          const opt = document.createElement('option');
          opt.value = x.lote;
          opt.textContent = x.lote;
          selLote.appendChild(opt);
        });
      });
    }
    selArt && selArt.addEventListener('change', cargarLotes);
    if (selArt && selArt.value) cargarLotes();
  })();
</script>

<?php
/* ================ Cierre del frame ================ */
include __DIR__ . '/../bi/_menu_global_end.php';
