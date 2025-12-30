<?php
// public/dashboard/wsadmin_dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

// Evita warnings/HTML colándose antes de headers (menú o includes con BOM/espacios)
if (!ob_get_level()) { ob_start(); }

// Menú global (incluye auth_check.php en tu base)
require_once __DIR__ . '/../bi/_menu_global.php';

// -----------------------------
// Helpers
// -----------------------------
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function dt_to_ui(?string $dt): string {
    if (!$dt) return '';
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
    return $d ? $d->format('d/m/Y H:i') : $dt;
}
function ui_to_dt(string $ui, string $fallback): string {
    $ui = trim($ui);
    if ($ui === '') return $fallback;

    // Acepta: dd/mm/yyyy hh:mm  | dd/mm/yyyy hh:mm:ss | yyyy-mm-dd hh:mm:ss (por si te llega así)
    $formats = ['d/m/Y H:i', 'd/m/Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'];
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $ui);
        if ($d instanceof DateTime) return $d->format('Y-m-d H:i:s');
    }
    return $fallback;
}

// -----------------------------
// Defaults: últimos 7 días
// -----------------------------
$now   = new DateTime('now');
$desde_default = (clone $now)->modify('-7 days')->setTime(0,0,0)->format('Y-m-d H:i:s');
$hasta_default = (clone $now)->setTime(23,59,59)->format('Y-m-d H:i:s');

$desde_ui_in = (string)($_GET['desde'] ?? '');
$hasta_ui_in = (string)($_GET['hasta'] ?? '');

$desde = ui_to_dt($desde_ui_in, $desde_default);
$hasta = ui_to_dt($hasta_ui_in, $hasta_default);

$cliente_id = trim((string)($_GET['cliente_id'] ?? '')); // corresponde a log_ws_ejecucion.conexion_id
$evento     = trim((string)($_GET['evento'] ?? ''));
$resultado  = trim((string)($_GET['resultado'] ?? '')); // TODOS | OK | BLOQUEADO
$q          = trim((string)($_GET['q'] ?? ''));

$page     = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 25;
$offset   = ($page - 1) * $pageSize;

// -----------------------------
// Tabla principal: (ajusta NOMBRE si tu tabla difiere)
// -----------------------------
$TLOG = 'log_ws_ejecucion';
$TCON = 'c_ws_conexion';

// WHERE dinámico
$where = [];
$params = [];

$where[] = "l.fecha_ini BETWEEN :desde AND :hasta";
$params[':desde'] = $desde;
$params[':hasta'] = $hasta;

if ($cliente_id !== '') {
    $where[] = "l.conexion_id = :cid";
    $params[':cid'] = (int)$cliente_id;
}
if ($evento !== '') {
    $where[] = "l.evento = :evt";
    $params[':evt'] = $evento;
}
if ($q !== '') {
    $where[] = "(l.trace_id LIKE :q OR l.referencia LIKE :q OR l.usuario LIKE :q OR l.sistema LIKE :q OR l.dispositivo LIKE :q)";
    $params[':q'] = "%{$q}%";
}

// Resultado derivado (porque no hay columna “status” real)
if ($resultado === 'OK') {
    $where[] = "l.fecha_fin IS NOT NULL";
} elseif ($resultado === 'BLOQUEADO') {
    $where[] = "l.fecha_fin IS NULL";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// -----------------------------
// KPIs (derivados)
// -----------------------------
$sql_kpi = "
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN l.fecha_fin IS NOT NULL THEN 1 ELSE 0 END) AS ok_cnt,
  SUM(CASE WHEN l.fecha_fin IS NULL THEN 1 ELSE 0 END) AS bloqueado_cnt
FROM {$TLOG} l
{$whereSql}
";
$kpi = db_one($sql_kpi, $params) ?: ['total'=>0,'ok_cnt'=>0,'bloqueado_cnt'=>0];

// “ERROR” no existe como columna en tu log; lo dejamos 0 para no inventar.
// Si luego agregas l.estado o l.total_err, lo conectamos.
$kpi_error = 0;

// Última ejecución
$sql_last = "
SELECT l.fecha_ini
FROM {$TLOG} l
{$whereSql}
ORDER BY l.fecha_ini DESC
LIMIT 1
";
$last_dt = db_val($sql_last, $params);

// -----------------------------
// Filtros: clientes/eventos
// -----------------------------
$sql_clientes = "
SELECT DISTINCT l.conexion_id AS id,
       COALESCE(c.nombre_conexion, CONCAT('CONEXION #', l.conexion_id)) AS nombre
FROM {$TLOG} l
LEFT JOIN {$TCON} c ON c.id = l.conexion_id
WHERE l.conexion_id IS NOT NULL
ORDER BY nombre
";
$clientes = db_all($sql_clientes) ?: [];

$sql_eventos = "
SELECT DISTINCT l.evento
FROM {$TLOG} l
WHERE l.evento IS NOT NULL AND l.evento <> ''
ORDER BY l.evento
";
$eventos = db_all($sql_eventos) ?: [];

// -----------------------------
// Conteo/paginación
// -----------------------------
$sql_count = "SELECT COUNT(*) FROM {$TLOG} l {$whereSql}";
$totalRows = (int)db_val($sql_count, $params);
$totalPages = max(1, (int)ceil($totalRows / $pageSize));

// -----------------------------
// Lista
// -----------------------------
$sql_list = "
SELECT
  l.id,
  l.fecha_ini,
  l.fecha_fin,
  l.trace_id,
  l.evento,
  l.referencia,
  l.sistema,
  l.conexion_id,
  l.dispositivo,
  l.usuario,
  l.ip_origen,
  c.nombre_conexion,
  CASE
    WHEN l.fecha_fin IS NULL THEN 'BLOQUEADO'
    ELSE 'OK'
  END AS resultado,
  CASE
    WHEN l.fecha_fin IS NULL THEN NULL
    ELSE ROUND(TIMESTAMPDIFF(MICROSECOND, l.fecha_ini, l.fecha_fin) / 1000)
  END AS duracion_ms
FROM {$TLOG} l
LEFT JOIN {$TCON} c ON c.id = l.conexion_id
{$whereSql}
ORDER BY l.fecha_ini DESC
LIMIT {$pageSize} OFFSET {$offset}
";
$rows = db_all($sql_list, $params) ?: [];

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="wsadmin_log_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Fecha ini','Fecha fin','Cliente','Evento','Resultado','Duración ms','Trace ID','Referencia','Sistema','Usuario','IP','Dispositivo']);

    foreach ($rows as $r) {
        $clienteNombre = $r['nombre_conexion'] ?: ('CONEXION #' . (string)$r['conexion_id']);
        fputcsv($out, [
            dt_to_ui($r['fecha_ini']),
            dt_to_ui($r['fecha_fin']),
            $clienteNombre,
            (string)$r['evento'],
            (string)$r['resultado'],
            (string)($r['duracion_ms'] ?? ''),
            (string)$r['trace_id'],
            (string)($r['referencia'] ?? ''),
            (string)($r['sistema'] ?? ''),
            (string)($r['usuario'] ?? ''),
            (string)($r['ip_origen'] ?? ''),
            (string)($r['dispositivo'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WebServices Admin · Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

  <style>
    :root{
      --ap-blue:#000099;
      --ap-bg:#f5f7fb;
      --ap-card:#ffffff;
      --ap-border:#e9ecef;
      --ap-text:#1f2a37;
      --ap-muted:#6b7280;
      --ap-ok:#198754;
      --ap-err:#dc3545;
      --ap-warn:#fd7e14;
    }
    body{ background:var(--ap-bg); color:var(--ap-text); }
    .ap-title{ font-weight:700; letter-spacing:.2px; }
    .ap-sub{ color:var(--ap-muted); font-size:12px; }
    .kpi-card{ background:var(--ap-card); border:1px solid var(--ap-border); border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(16,24,40,.04); }
    .kpi-label{ font-size:11px; color:var(--ap-muted); text-transform:uppercase; }
    .kpi-val{ font-size:28px; font-weight:800; line-height:1; margin-top:6px;}
    .kpi-bar{ width:4px; border-radius:12px; margin-right:10px;}
    .kpi-row{ display:flex; align-items:center; gap:10px; }
    .filter-card{ background:var(--ap-card); border:1px solid var(--ap-border); border-radius:12px; padding:14px 16px; }
    .table-wrap{ background:var(--ap-card); border:1px solid var(--ap-border); border-radius:12px; padding:14px 16px; }
    table{ font-size:10px; }
    thead th{ background:rgba(0,0,153,.08)!important; }
    .scroll{ overflow:auto; }
    .badge-soft{
      padding:.35rem .55rem; border-radius:999px; font-weight:700; font-size:10px;
      border:1px solid var(--ap-border); background:#fff;
    }
    .b-ok{ border-color:rgba(25,135,84,.25); color:var(--ap-ok); background:rgba(25,135,84,.08); }
    .b-blq{ border-color:rgba(253,126,20,.25); color:var(--ap-warn); background:rgba(253,126,20,.10); }
    .btn-ap{ background:var(--ap-blue); border-color:var(--ap-blue); }
    .btn-ap:hover{ filter:brightness(.92); }
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  </style>
</head>

<body>
<div class="container-fluid py-3">

  <div class="d-flex align-items-start justify-content-between mb-2">
    <div>
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-diagram-project text-primary"></i>
        <div class="ap-title fs-4">WebServices Admin · Dashboard</div>
        <span class="badge text-bg-light border">Solo lectura</span>
      </div>
      <div class="ap-sub">Monitoreo ejecutivo de integraciones · bitácora y KPIs</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?= h($_SERVER['PHP_SELF']) ?>"><i class="fa-solid fa-rotate"></i> Actualizar</a>
      <?php
        $qs = $_GET;
        $qs['export'] = 'csv';
        $csvUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($qs);
      ?>
      <a class="btn btn-ap btn-sm text-white" href="<?= h($csvUrl) ?>"><i class="fa-solid fa-file-csv"></i> Exportar CSV</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-row">
          <div class="kpi-bar" style="background:var(--ap-blue);height:44px;"></div>
          <div>
            <div class="kpi-label">Total ejecuciones</div>
            <div class="kpi-val"><?= (int)$kpi['total'] ?></div>
            <div class="ap-sub">Rango actual</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-row">
          <div class="kpi-bar" style="background:var(--ap-ok);height:44px;"></div>
          <div>
            <div class="kpi-label">OK</div>
            <div class="kpi-val"><?= (int)$kpi['ok_cnt'] ?></div>
            <div class="ap-sub">Procesos finalizados</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-row">
          <div class="kpi-bar" style="background:var(--ap-err);height:44px;"></div>
          <div>
            <div class="kpi-label">Error</div>
            <div class="kpi-val"><?= (int)$kpi_error ?></div>
            <div class="ap-sub">Sin estado real (pendiente integrar)</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card">
        <div class="kpi-row">
          <div class="kpi-bar" style="background:var(--ap-warn);height:44px;"></div>
          <div>
            <div class="kpi-label">Bloqueado</div>
            <div class="kpi-val"><?= (int)$kpi['bloqueado_cnt'] ?></div>
            <div class="ap-sub">Pendiente / no finalizado</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <form class="filter-card mb-3" method="get" action="<?= h($_SERVER['PHP_SELF']) ?>">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-2">
        <label class="form-label mb-1">Desde</label>
        <input type="text" class="form-control form-control-sm flatpickr" name="desde"
               value="<?= h(dt_to_ui($desde)) ?>" placeholder="dd/mm/aaaa hh:mm">
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label mb-1">Hasta</label>
        <input type="text" class="form-control form-control-sm flatpickr" name="hasta"
               value="<?= h(dt_to_ui($hasta)) ?>" placeholder="dd/mm/aaaa hh:mm">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label mb-1">Cliente</label>
        <select class="form-select form-select-sm" name="cliente_id">
          <option value="">Todos</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente_id !== '' && (int)$cliente_id === (int)$c['id']) ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label mb-1">Evento</label>
        <select class="form-select form-select-sm" name="evento">
          <option value="">Todos</option>
          <?php foreach ($eventos as $e): ?>
            <option value="<?= h($e['evento']) ?>" <?= ($evento === (string)$e['evento']) ? 'selected' : '' ?>>
              <?= h($e['evento']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label mb-1">Resultado</label>
        <select class="form-select form-select-sm" name="resultado">
          <option value="" <?= $resultado===''?'selected':'' ?>>Todos</option>
          <option value="OK" <?= $resultado==='OK'?'selected':'' ?>>OK</option>
          <option value="BLOQUEADO" <?= $resultado==='BLOQUEADO'?'selected':'' ?>>Bloqueado</option>
        </select>
      </div>

      <div class="col-12 col-md-8">
        <label class="form-label mb-1">Buscar</label>
        <input type="text" class="form-control form-control-sm" name="q" value="<?= h($q) ?>" placeholder="trace/ref/usuario/sistema">
      </div>
      <div class="col-12 col-md-4 d-flex gap-2 justify-content-end">
        <button class="btn btn-ap btn-sm text-white" type="submit"><i class="fa-solid fa-filter"></i> Aplicar</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?= h($_SERVER['PHP_SELF']) ?>"><i class="fa-solid fa-eraser"></i> Limpiar</a>
      </div>
    </div>

    <div class="mt-2 ap-sub">
      Última ejecución: <?= h(dt_to_ui((string)$last_dt)) ?>
      · Default: últimos 7 días
    </div>
  </form>

  <div class="table-wrap">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="fw-bold"><i class="fa-solid fa-list-check"></i> Bitácora de ejecuciones</div>
      <div class="ap-sub">Página <?= $page ?> de <?= $totalPages ?> · Registros <?= $totalRows ?></div>
    </div>

    <div class="scroll">
      <table class="table table-sm table-hover align-middle mb-2" style="min-width:1200px;">
        <thead>
          <tr>
            <th style="width:70px;">Acción</th>
            <th style="width:140px;">Fecha ini</th>
            <th style="width:220px;">Cliente</th>
            <th style="width:180px;">Evento</th>
            <th style="width:110px;">Resultado</th>
            <th style="width:110px;">Duración ms</th>
            <th style="width:320px;">Trace ID</th>
            <th style="width:200px;">Referencia</th>
            <th style="width:120px;">Sistema</th>
            <th style="width:120px;">Usuario</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="10" class="text-center py-4 text-muted">Sin datos para el rango/filtros seleccionados.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $clienteNombre = $r['nombre_conexion'] ?: ('CONEXION #' . (string)$r['conexion_id']);
              $res = (string)$r['resultado'];
              $badgeClass = ($res === 'OK') ? 'b-ok' : 'b-blq';
              $ref = (string)($r['referencia'] ?? '');
            ?>
            <tr>
              <td>
                <button class="btn btn-outline-primary btn-sm py-0 px-2"
                        onclick="navigator.clipboard.writeText('<?= h($r['trace_id']) ?>')">
                  Copiar
                </button>
              </td>
              <td class="mono"><?= h(dt_to_ui($r['fecha_ini'])) ?></td>
              <td><?= h($clienteNombre) ?></td>
              <td class="mono"><?= h($r['evento']) ?></td>
              <td><span class="badge-soft <?= $badgeClass ?>"><?= h($res) ?></span></td>
              <td class="mono text-end"><?= h($r['duracion_ms']) ?></td>
              <td class="mono"><?= h($r['trace_id']) ?></td>
              <td class="mono"><?= h($ref) ?></td>
              <td><?= h($r['sistema']) ?></td>
              <td><?= h($r['usuario']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
      <div class="ap-sub">PageSize: <?= $pageSize ?></div>
      <div class="d-flex gap-1">
        <?php
          $qsBase = $_GET;
          $qsBase['page'] = max(1, $page - 1);
        ?>
        <a class="btn btn-outline-secondary btn-sm <?= $page<=1?'disabled':'' ?>"
           href="<?= h($_SERVER['PHP_SELF'].'?'.http_build_query($qsBase)) ?>">«</a>

        <?php
          $qsBase['page'] = min($totalPages, $page + 1);
        ?>
        <a class="btn btn-outline-secondary btn-sm <?= $page>=$totalPages?'disabled':'' ?>"
           href="<?= h($_SERVER['PHP_SELF'].'?'.http_build_query($qsBase)) ?>">»</a>
      </div>
    </div>

    <div class="ap-sub mt-2">
      * “Bloqueado” incluye ejecuciones sin fecha_fin (pendiente/no finalizado).  
      * KPI “Error” requiere columna real de estado (ej. l.estado / l.total_err) para contabilizar fallas técnicas/negocio.
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
  flatpickr(".flatpickr", {
    enableTime: true,
    time_24hr: false,
    locale: "es",
    dateFormat: "d/m/Y H:i",
    allowInput: true
  });
</script>
</body>
</html>
