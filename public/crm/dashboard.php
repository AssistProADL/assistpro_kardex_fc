<?php
// /public/crm/dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
$db = db_pdo();
// --- Helpers ---
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function dt_today(): string { return date('Y-m-d'); }
function month_start(): string { return date('Y-m-01'); }
function month_end(): string { return date('Y-m-t'); }

// --- PDO (tu db.php es PDO directo; normalmente expone $pdo) ---
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Si tu db.php usa otro nombre, ajusta aquí (ej. $conn)
    if (isset($conn) && $conn instanceof PDO) $pdo = $conn;
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo "<div style='padding:12px;font-family:Arial'>
            <b>Error:</b> No se encontró instancia PDO (\$pdo). Revisa app/db.php.
          </div>";
    exit;
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Filtros ---
$fi = $_GET['fi'] ?? month_start();
$ff = $_GET['ff'] ?? month_end();
$asesor = trim((string)($_GET['asesor'] ?? ''));
$etapa  = trim((string)($_GET['etapa'] ?? ''));
$cliente_q = trim((string)($_GET['cliente'] ?? ''));

// Normaliza fechas (fallback)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fi)) $fi = month_start();
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ff)) $ff = month_end();

// --- Combos (asesores / etapas) ---
$asesores = [];
$etapas = [];

try {
    $stmt = $pdo->query("SELECT DISTINCT usuario_responsable AS asesor
                         FROM t_crm_oportunidad
                         WHERE usuario_responsable IS NOT NULL AND usuario_responsable <> ''
                         ORDER BY usuario_responsable");
    $asesores = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT etapa
                         FROM t_crm_oportunidad
                         WHERE etapa IS NOT NULL AND etapa <> ''
                         ORDER BY etapa");
    $etapas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    // no bloquea UI
}

// --- WHERE dinámico (periodo sobre fecha_crea) ---
$where = " WHERE o.fecha_crea >= :fi AND o.fecha_crea < DATE_ADD(:ff, INTERVAL 1 DAY) ";
$params = [':fi' => $fi, ':ff' => $ff];

if ($asesor !== '') { $where .= " AND o.usuario_responsable = :asesor "; $params[':asesor'] = $asesor; }
if ($etapa !== '')  { $where .= " AND o.etapa = :etapa "; $params[':etapa'] = $etapa; }

if ($cliente_q !== '') {
    // cliente por id_cliente o por texto en lead/título
    $where .= " AND (
                    CAST(o.id_cliente AS CHAR) LIKE :cq
                    OR o.titulo LIKE :cq
                    OR EXISTS (
                        SELECT 1 FROM t_crm_lead l
                        WHERE l.id_lead = o.id_lead
                          AND (l.nombre LIKE :cq OR l.empresa LIKE :cq OR l.email LIKE :cq)
                    )
                ) ";
    $params[':cq'] = "%{$cliente_q}%";
}

// --- KPIs ---
$kpi = [
    'leads' => 0,
    'opp_abiertas' => 0,
    'opp_ganadas' => 0,
    'conversion' => 0.0,
    'act_hoy' => 0
];

try {
    // Leads en periodo (si tienes t_crm_lead con fecha_crea)
    $sql = "SELECT COUNT(*) FROM t_crm_lead
            WHERE fecha_crea >= :fi AND fecha_crea < DATE_ADD(:ff, INTERVAL 1 DAY)";
    $st = $pdo->prepare($sql);
    $st->execute([':fi'=>$fi, ':ff'=>$ff]);
    $kpi['leads'] = (int)$st->fetchColumn();

    // OPP abiertas / ganadas en periodo con filtros
    $sql = "SELECT
              SUM(CASE WHEN o.estatus='Abierta' THEN 1 ELSE 0 END) abiertas,
              SUM(CASE WHEN o.estatus='Ganada' THEN 1 ELSE 0 END) ganadas,
              COUNT(*) total
            FROM t_crm_oportunidad o {$where}";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    $kpi['opp_abiertas'] = (int)($row['abiertas'] ?? 0);
    $kpi['opp_ganadas']  = (int)($row['ganadas'] ?? 0);
    $total = (int)($row['total'] ?? 0);
    $kpi['conversion'] = $total > 0 ? round(($kpi['opp_ganadas'] / $total) * 100, 1) : 0.0;

    // Actividades hoy (si tienes t_crm_actividad)
    $sql = "SELECT COUNT(*)
            FROM t_crm_actividad a
            WHERE DATE(a.fecha_programada) = CURDATE()";
    $st = $pdo->query($sql);
    $kpi['act_hoy'] = (int)$st->fetchColumn();

} catch (Throwable $e) {
    // no bloquea UI
}

// --- Funnel por etapa (abiertas) ---
$funnel = [];
try {
    $sql = "SELECT o.etapa,
                   COUNT(*) oportunidades,
                   SUM(COALESCE(o.valor_estimado,0)) monto
            FROM t_crm_oportunidad o
            {$where} AND o.estatus='Abierta'
            GROUP BY o.etapa
            ORDER BY oportunidades DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $funnel = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// --- Oportunidades por asesor (abiertas) ---
$porAsesor = [];
try {
    $sql = "SELECT o.usuario_responsable AS asesor,
                   COUNT(*) oportunidades,
                   SUM(COALESCE(o.valor_estimado,0)) monto
            FROM t_crm_oportunidad o
            {$where} AND o.estatus='Abierta'
            GROUP BY o.usuario_responsable
            ORDER BY oportunidades DESC, monto DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $porAsesor = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// --- Actividades próximas (programadas en rango) ---
$actividades = [];
try {
    $sql = "SELECT a.fecha_programada, a.tipo, a.descripcion, a.id_lead, a.id_opp, a.usuario, a.estatus
            FROM t_crm_actividad a
            WHERE a.fecha_programada >= :fi AND a.fecha_programada < DATE_ADD(:ff, INTERVAL 1 DAY)
            ORDER BY a.fecha_programada ASC
            LIMIT 25";
    $st = $pdo->prepare($sql);
    $st->execute([':fi'=>$fi, ':ff'=>$ff]);
    $actividades = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// --- Top oportunidades abiertas ---
$topOpp = [];
try {
    $sql = "SELECT o.id_opp, o.titulo, o.valor_estimado, o.probabilidad, o.etapa, o.fecha_crea, o.id_lead, o.id_cliente
            FROM t_crm_oportunidad o
            {$where} AND o.estatus='Abierta'
            ORDER BY COALESCE(o.valor_estimado,0) DESC, o.probabilidad DESC, o.id_opp DESC
            LIMIT 10";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $topOpp = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

?>
<style>
/* --- Corporate / elegante --- */
.ap-wrap{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:700;color:#0b5ed7;margin:4px 0 10px 0;display:flex;align-items:center;gap:10px}
.ap-sub{color:#5b6b7c;font-size:12px;margin-top:-6px;margin-bottom:10px}

.ap-filters{background:#fff;border:1px solid #dbe5f1;border-radius:12px;padding:10px;box-shadow:0 2px 10px rgba(10,36,84,.06);margin-bottom:10px}
.ap-filters .row{--bs-gutter-x:10px}
.ap-filters label{font-size:11px;color:#5b6b7c;margin-bottom:3px}
.ap-filters .form-control,.ap-filters .form-select{font-size:12px;border-radius:10px}
.ap-btn{border-radius:10px;font-weight:600}

.ap-kpis{display:grid;grid-template-columns:repeat(5,minmax(160px,1fr));gap:10px;margin-bottom:10px}
@media(max-width:1200px){.ap-kpis{grid-template-columns:repeat(2,minmax(160px,1fr));}}
.ap-card{background:#fff;border:1px solid #dbe5f1;border-radius:14px;padding:12px;box-shadow:0 2px 12px rgba(10,36,84,.06)}
.ap-kpi-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.ap-kpi-title{font-size:11px;color:#6c7b8a;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
.ap-kpi-val{font-size:22px;font-weight:800;color:#0b1f44;line-height:1}
.ap-kpi-badge{font-size:11px;font-weight:700;border-radius:999px;padding:4px 10px;background:#eef5ff;color:#0b5ed7;border:1px solid #dbe5f1}
.ap-kpi-foot{font-size:11px;color:#6c7b8a}

.ap-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:10px}
@media(max-width:1200px){.ap-grid{grid-template-columns:1fr;}}
.ap-card h6{font-size:12px;font-weight:800;color:#0b1f44;margin:0 0 8px 0}
.ap-table{width:100%;border-collapse:collapse;font-size:10px}
.ap-table th{background:#f6f9ff;color:#0b1f44;border:1px solid #e6eefb;padding:6px 6px;text-align:left}
.ap-table td{border:1px solid #eef2f7;padding:6px 6px;vertical-align:top}
.ap-table td.num, .ap-table th.num{text-align:right}
.ap-muted{color:#6c7b8a}
.ap-chip{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef5ff;border:1px solid #dbe5f1;color:#0b5ed7;font-size:10px;font-weight:700}
.ap-bar{height:10px;border-radius:999px;background:#eef2f7;overflow:hidden;border:1px solid #e6eefb}
.ap-bar > div{height:100%;background:#0b5ed7}
.ap-scroll{max-height:260px;overflow:auto}
</style>

<div class="ap-wrap">
  <div class="ap-title">
    <i class="fa-solid fa-chart-line"></i>
    CRM – Dashboard Comercial
  </div>
  <div class="ap-sub">Visión ejecutiva del pipeline y productividad (filtros por periodo, asesor, etapa y cliente).</div>

  <form class="ap-filters" method="GET" action="">
    <div class="row align-items-end">
      <div class="col-md-2">
        <label>Fecha inicio</label>
        <input type="date" class="form-control" name="fi" value="<?=h($fi)?>">
      </div>
      <div class="col-md-2">
        <label>Fecha fin</label>
        <input type="date" class="form-control" name="ff" value="<?=h($ff)?>">
      </div>
      <div class="col-md-2">
        <label>Asesor</label>
        <select class="form-select" name="asesor">
          <option value="">— Todos —</option>
          <?php foreach ($asesores as $a): ?>
            <option value="<?=h($a)?>" <?=($a===$asesor?'selected':'')?>><?=h($a)?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label>Etapa</label>
        <select class="form-select" name="etapa">
          <option value="">— Todas —</option>
          <?php foreach ($etapas as $e): ?>
            <option value="<?=h($e)?>" <?=($e===$etapa?'selected':'')?>><?=h($e)?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label>Cliente</label>
        <input type="text" class="form-control" name="cliente" placeholder="Nombre o clave" value="<?=h($cliente_q)?>">
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary ap-btn" type="submit">Aplicar</button>
      </div>
    </div>
  </form>

  <div class="ap-kpis">
    <div class="ap-card">
      <div class="ap-kpi-top">
        <div class="ap-kpi-title">Leads periodo</div>
        <div class="ap-kpi-badge"><i class="fa-solid fa-user-plus"></i></div>
      </div>
      <div class="ap-kpi-val"><?=number_format((int)$kpi['leads'])?></div>
      <div class="ap-kpi-foot">Base de prospección en el rango.</div>
    </div>

    <div class="ap-card">
      <div class="ap-kpi-top">
        <div class="ap-kpi-title">OPP abiertas</div>
        <div class="ap-kpi-badge"><i class="fa-solid fa-folder-open"></i></div>
      </div>
      <div class="ap-kpi-val"><?=number_format((int)$kpi['opp_abiertas'])?></div>
      <div class="ap-kpi-foot">Pipeline activo (Abierta).</div>
    </div>

    <div class="ap-card">
      <div class="ap-kpi-top">
        <div class="ap-kpi-title">OPP ganadas</div>
        <div class="ap-kpi-badge"><i class="fa-solid fa-trophy"></i></div>
      </div>
      <div class="ap-kpi-val"><?=number_format((int)$kpi['opp_ganadas'])?></div>
      <div class="ap-kpi-foot">Cierres exitosos en el rango.</div>
    </div>

    <div class="ap-card">
      <div class="ap-kpi-top">
        <div class="ap-kpi-title">Conversión</div>
        <div class="ap-kpi-badge"><i class="fa-solid fa-percent"></i></div>
      </div>
      <div class="ap-kpi-val"><?=h((string)$kpi['conversion'])?>%</div>
      <div class="ap-kpi-foot">Ganadas / Total oportunidades.</div>
    </div>

    <div class="ap-card">
      <div class="ap-kpi-top">
        <div class="ap-kpi-title">Actividades hoy</div>
        <div class="ap-kpi-badge"><i class="fa-solid fa-calendar-check"></i></div>
      </div>
      <div class="ap-kpi-val"><?=number_format((int)$kpi['act_hoy'])?></div>
      <div class="ap-kpi-foot">Agenda operativa del día.</div>
    </div>
  </div>

  <div class="ap-grid">
    <div class="ap-card">
      <h6><i class="fa-solid fa-filter-circle-dollar"></i> Funil por etapa</h6>
      <div class="ap-scroll">
        <table class="ap-table">
          <thead>
            <tr>
              <th>Etapa</th>
              <th class="num">Oportunidades</th>
              <th class="num">Monto</th>
              <th style="width:180px">Participación</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $totalOpp = 0;
            foreach ($funnel as $r) $totalOpp += (int)($r['oportunidades'] ?? 0);
            foreach ($funnel as $r):
              $cnt = (int)($r['oportunidades'] ?? 0);
              $monto = (float)($r['monto'] ?? 0);
              $pct = ($totalOpp>0) ? round(($cnt/$totalOpp)*100,1) : 0;
          ?>
            <tr>
              <td><span class="ap-chip"><?=h($r['etapa'] ?? '')?></span></td>
              <td class="num"><?=number_format($cnt)?></td>
              <td class="num">$<?=number_format($monto,2)?></td>
              <td>
                <div class="ap-bar" title="<?=h((string)$pct)?>%">
                  <div style="width: <?=$pct?>%"></div>
                </div>
                <div class="ap-muted" style="font-size:10px;margin-top:2px"><?=h((string)$pct)?>%</div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$funnel): ?>
            <tr><td colspan="4" class="ap-muted">Sin datos para los filtros seleccionados.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="ap-card">
      <h6><i class="fa-solid fa-user-tie"></i> Oportunidades por asesor</h6>
      <div class="ap-scroll">
        <table class="ap-table">
          <thead>
            <tr>
              <th>Asesor</th>
              <th class="num">Oportunidades</th>
              <th class="num">Valor total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($porAsesor as $r): ?>
              <tr>
                <td><?=h($r['asesor'] ?? '')?></td>
                <td class="num"><?=number_format((int)($r['oportunidades'] ?? 0))?></td>
                <td class="num">$<?=number_format((float)($r['monto'] ?? 0),2)?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$porAsesor): ?>
              <tr><td colspan="3" class="ap-muted">Sin datos para los filtros seleccionados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="ap-card">
      <h6><i class="fa-solid fa-list-check"></i> Actividades próximas</h6>
      <div class="ap-scroll">
        <table class="ap-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Lead / OPP</th>
              <th>Descripción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($actividades as $a): ?>
              <tr>
                <td><?=h($a['fecha_programada'] ?? '')?></td>
                <td><?=h($a['tipo'] ?? '')?></td>
                <td>
                  <span class="ap-muted">Lead:</span> <?=h((string)($a['id_lead'] ?? ''))?>
                  &nbsp; <span class="ap-muted">OPP:</span> <?=h((string)($a['id_opp'] ?? ''))?>
                </td>
                <td><?=h($a['descripcion'] ?? '')?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$actividades): ?>
              <tr><td colspan="4" class="ap-muted">Sin actividades en el rango.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="ap-card">
      <h6><i class="fa-solid fa-star"></i> Top oportunidades abiertas</h6>
      <div class="ap-scroll">
        <table class="ap-table">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Título</th>
              <th>Lead / Cliente</th>
              <th class="num">Valor</th>
              <th class="num">% Prob</th>
              <th>Etapa</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topOpp as $o): ?>
              <tr>
                <td>#<?=h((string)($o['id_opp'] ?? ''))?></td>
                <td><?=h($o['titulo'] ?? '')?></td>
                <td>
                  <b><?=h((string)($o['id_lead'] ?? ''))?></b>
                  <span class="ap-muted">/</span>
                  <?=h((string)($o['id_cliente'] ?? ''))?>
                </td>
                <td class="num">$<?=number_format((float)($o['valor_estimado'] ?? 0),2)?></td>
                <td class="num"><?=h((string)($o['probabilidad'] ?? 0))?>%</td>
                <td><?=h($o['etapa'] ?? '')?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$topOpp): ?>
              <tr><td colspan="6" class="ap-muted">Sin oportunidades abiertas en el rango.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php
// Cierre de layout global si aplica en tu proyecto
if (file_exists(__DIR__ . '/../bi/_menu_global_end.php')) {
    require_once __DIR__ . '/../bi/_menu_global_end.php';
}
?>
