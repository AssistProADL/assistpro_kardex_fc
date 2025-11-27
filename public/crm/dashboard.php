<?php
// public/crm/dashboard.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$db = db_pdo();
$mensaje_error = '';

// =======================
// Filtros del dashboard
// =======================
$fil_fecha_ini = $_GET['f_ini'] ?? date('Y-m-01');
$fil_fecha_fin = $_GET['f_fin'] ?? date('Y-m-t');
$fil_asesor    = $_GET['asesor'] ?? '';
$fil_etapa     = $_GET['etapa'] ?? '';
$fil_cliente   = $_GET['cliente'] ?? '';
$fil_canal     = $_GET['canal'] ?? ''; // reservado para futuro (ej. Amazon vs Directo)

// =======================
// Filtros SQL para oportunidades
// =======================
$where_opp = " WHERE 1=1 ";
$params_opp = [];

if ($fil_asesor !== '') {
    $where_opp .= " AND o.usuario_responsable = ? ";
    $params_opp[] = $fil_asesor;
}
if ($fil_etapa !== '') {
    $where_opp .= " AND o.etapa = ? ";
    $params_opp[] = $fil_etapa;
}
if ($fil_cliente !== '') {
    $where_opp .= " AND (c.RazonSocial LIKE ? OR c.Cve_Clte LIKE ?) ";
    $params_opp[] = "%$fil_cliente%";
    $params_opp[] = "%$fil_cliente%";
}
// rango de fechas sobre fecha_crea de la oportunidad
$where_opp .= " AND DATE(o.fecha_crea) BETWEEN ? AND ? ";
$params_opp[] = $fil_fecha_ini;
$params_opp[] = $fil_fecha_fin;

// =======================
// Catálogo de asesores para combo
// =======================
$asesores_combo = [];
try {
    $asesores_combo = db_all("
        SELECT DISTINCT usuario_responsable
        FROM t_crm_oportunidad
        WHERE usuario_responsable IS NOT NULL AND usuario_responsable <> ''
        ORDER BY usuario_responsable
    ");
} catch (Throwable $e) {
    // no es crítico
}

// =======================
// Variables de KPIs y datasets
// =======================
$kpi_leads_30      = 0;
$kpi_opp_abiertas  = 0;
$kpi_opp_ganadas   = 0;
$kpi_conv_pct      = 0;
$kpi_act_hoy       = 0;

$funnel = [];
$opp_por_asesor = [];
$acts_pendientes = [];
$top_opp = [];
$kpi_amz_ventas_30 = null;
$kpi_amz_unidades_30 = null;

// ===============================
// KPIs principales (usando filtros)
// ===============================
try {
    // Leads en rango de fechas
    $row = db_row("
        SELECT COUNT(*) AS total
        FROM t_crm_lead
        WHERE DATE(fecha_alta) BETWEEN ? AND ?
    ", [$fil_fecha_ini, $fil_fecha_fin]);
    $kpi_leads_30 = (int)($row['total'] ?? 0);

    // Oportunidades abiertas (filtradas)
    $row = db_row("
        SELECT COUNT(*) AS total
        FROM t_crm_oportunidad o
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
        $where_opp
        AND o.etapa NOT IN ('Ganada','Perdida')
    ", $params_opp);
    $kpi_opp_abiertas = (int)($row['total'] ?? 0);

    // Oportunidades ganadas (filtradas + etapa ganada)
    $row = db_row("
        SELECT COUNT(*) AS total
        FROM t_crm_oportunidad o
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
        $where_opp
        AND o.etapa = 'Ganada'
    ", $params_opp);
    $kpi_opp_ganadas = (int)($row['total'] ?? 0);

    // Tasa de conversión (ganada vs perdida, filtradas)
    $row = db_row("
        SELECT
            SUM(o.etapa='Ganada') AS ganadas,
            SUM(o.etapa='Perdida') AS perdidas,
            COUNT(*) AS total
        FROM t_crm_oportunidad o
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
        $where_opp
    ", $params_opp);
    $gan = (int)($row['ganadas'] ?? 0);
    $per = (int)($row['perdidas'] ?? 0);
    if ($gan + $per > 0) {
        $kpi_conv_pct = round(($gan / ($gan + $per)) * 100, 1);
    } else {
        $kpi_conv_pct = 0;
    }

    // Actividades programadas para hoy (independiente del filtro de fecha de opp)
    $row = db_row("
        SELECT COUNT(*) AS total
        FROM t_crm_actividad
        WHERE estatus = 'Programada'
          AND DATE(fecha_programada) = CURDATE()
    ");
    $kpi_act_hoy = (int)($row['total'] ?? 0);

} catch (Throwable $e) {
    $mensaje_error = 'Error cargando KPIs: ' . $e->getMessage();
}

// ===============================
// Funil por etapa (filtrado)
// ===============================
try {
    $funnel = db_all("
        SELECT 
            o.etapa,
            COUNT(*) AS total,
            SUM(o.valor_estimado) AS valor,
            SUM(o.valor_estimado * (o.probabilidad/100)) AS forecast
        FROM t_crm_oportunidad o
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
        $where_opp
        GROUP BY o.etapa
        ORDER BY FIELD(o.etapa,'Prospección','Propuesta','Cotizado','Negociación','Ganada','Perdida'), o.etapa
    ", $params_opp);
} catch (Throwable $e) {
    // no rompemos la vista
}

// ===============================
// Oportunidades por asesor (filtrado)
// ===============================
try {
    $opp_por_asesor = db_all("
        SELECT 
            o.usuario_responsable,
            COUNT(*) AS total,
            SUM(o.valor_estimado) AS valor
        FROM t_crm_oportunidad o
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
        $where_opp
        GROUP BY o.usuario_responsable
        ORDER BY total DESC
        LIMIT 10
    ", $params_opp);
} catch (Throwable $e) {
    // ignorar
}

// ===============================
// Actividades pendientes próximas (sin filtrar por opp, ya que es agenda general)
// ===============================
try {
    $acts_pendientes = db_all("
        SELECT 
            a.*,
            l.nombre_contacto,
            o.titulo AS opp_titulo
        FROM t_crm_actividad a
        LEFT JOIN t_crm_lead l ON l.id_lead = a.id_lead
        LEFT JOIN t_crm_oportunidad o ON o.id_opp = a.id_opp
        WHERE a.estatus = 'Programada'
        ORDER BY a.fecha_programada ASC
        LIMIT 20
    ");
} catch (Throwable $e) {
    // ignorar
}

// ===============================
// Top oportunidades abiertas (filtrado)
// ===============================
try {
    $top_opp = db_all("
        SELECT 
            o.id_opp,
            o.titulo,
            o.valor_estimado,
            o.probabilidad,
            o.etapa,
            o.fecha_cierre_estimada,
            o.usuario_responsable,
            l.nombre_contacto,
            c.RazonSocial AS cliente_nombre
        FROM t_crm_oportunidad o
        LEFT JOIN t_crm_lead l ON l.id_lead = o.id_lead
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
        $where_opp
        AND o.etapa NOT IN ('Ganada','Perdida')
        ORDER BY o.valor_estimado DESC
        LIMIT 20
    ", $params_opp);
} catch (Throwable $e) {
    // ignorar
}

// ===============================
// Ventas Amazon (opcional, si existe fact_ventas_comercial)
// usando tambien el rango de fechas
// ===============================
try {
    $row = db_row("
        SELECT 
            SUM(venta_neta) AS venta_30,
            SUM(unidades)   AS unidades_30
        FROM fact_ventas_comercial
        WHERE canal = 'AMAZON'
          AND fecha BETWEEN ? AND ?
    ", [$fil_fecha_ini, $fil_fecha_fin]);
    if ($row && (!is_null($row['venta_30']) || !is_null($row['unidades_30']))) {
        $kpi_amz_ventas_30   = (float)$row['venta_30'];
        $kpi_amz_unidades_30 = (float)$row['unidades_30'];
    }
} catch (Throwable $e) {
    // si no existe, no mostramos
}

// calcular max para barras del funil
$max_funnel_val = 0;
foreach ($funnel as $f) {
    $max_funnel_val = max($max_funnel_val, (float)$f['forecast'], (float)$f['valor']);
}
?>
<div class="container-fluid mt-3" style="font-size:0.82rem;">

    <h4 class="mb-2">CRM – Dashboard Comercial</h4>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($mensaje_error) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="get" class="card p-2 mb-3" style="font-size:0.80rem;">
        <div class="row g-2 align-items-end">

            <div class="col-md-2 col-6">
                <label class="form-label mb-0">Fecha inicio</label>
                <input type="date" name="f_ini" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($fil_fecha_ini) ?>">
            </div>

            <div class="col-md-2 col-6">
                <label class="form-label mb-0">Fecha fin</label>
                <input type="date" name="f_fin" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($fil_fecha_fin) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-0">Asesor</label>
                <select name="asesor" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($asesores_combo as $a): ?>
                        <option value="<?= htmlspecialchars($a['usuario_responsable']) ?>"
                            <?= $fil_asesor === $a['usuario_responsable'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['usuario_responsable']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label mb-0">Etapa</label>
                <select name="etapa" class="form-select form-select-sm">
                    <option value="">-- Todas --</option>
                    <?php
                    $etapas_combo = ['Prospección','Propuesta','Cotizado','Negociación','Ganada','Perdida'];
                    foreach ($etapas_combo as $e):
                    ?>
                        <option value="<?= $e ?>" <?= $fil_etapa === $e ? 'selected' : '' ?>>
                            <?= $e ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-0">Cliente</label>
                <input type="text" name="cliente" class="form-control form-control-sm"
                       placeholder="Nombre o clave"
                       value="<?= htmlspecialchars($fil_cliente) ?>">
            </div>

            <div class="col-md-1 col-6">
                <button class="btn btn-primary btn-sm w-100">Aplicar</button>
            </div>

        </div>
    </form>

    <!-- KPIs principales -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <div class="card text-bg-light h-100">
                <div class="card-body p-2">
                    <div class="text-muted" style="font-size:0.75rem;">Leads periodo</div>
                    <div style="font-size:1.3rem; font-weight:bold;"><?= $kpi_leads_30 ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-bg-light h-100">
                <div class="card-body p-2">
                    <div class="text-muted" style="font-size:0.75rem;">OPP abiertas</div>
                    <div style="font-size:1.3rem; font-weight:bold;"><?= $kpi_opp_abiertas ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-bg-light h-100">
                <div class="card-body p-2">
                    <div class="text-muted" style="font-size:0.75rem;">OPP ganadas</div>
                    <div style="font-size:1.3rem; font-weight:bold;"><?= $kpi_opp_ganadas ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-bg-light h-100">
                <div class="card-body p-2">
                    <div class="text-muted" style="font-size:0.75rem;">Conversión</div>
                    <div style="font-size:1.3rem; font-weight:bold;"><?= number_format($kpi_conv_pct, 1) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-bg-light h-100">
                <div class="card-body p-2">
                    <div class="text-muted" style="font-size:0.75rem;">Actividades hoy</div>
                    <div style="font-size:1.3rem; font-weight:bold;"><?= $kpi_act_hoy ?></div>
                </div>
            </div>
        </div>
        <?php if (!is_null($kpi_amz_ventas_30)): ?>
        <div class="col-6 col-md-2">
            <div class="card text-bg-light h-100">
                <div class="card-body p-2">
                    <div class="text-muted" style="font-size:0.75rem;">Ventas Amazon</div>
                    <div style="font-size:0.9rem; font-weight:bold;">
                        $<?= number_format($kpi_amz_ventas_30, 2) ?><br>
                        <small><?= number_format($kpi_amz_unidades_30, 0) ?> uds.</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-3">
        <!-- Funil por etapa -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2">
                    Funil por etapa
                </div>
                <div class="card-body p-2">
                    <?php if (empty($funnel)): ?>
                        <div class="text-muted">No hay oportunidades en el periodo/filtros.</div>
                    <?php else: ?>
                        <?php foreach ($funnel as $f): 
                            $val_total = (float)($f['valor'] ?? 0);
                            $val_fc    = (float)($f['forecast'] ?? 0);
                            $pct_bar   = ($max_funnel_val > 0) ? round(($val_fc / $max_funnel_val) * 100) : 0;
                            if ($pct_bar < 5 && $val_fc > 0) $pct_bar = 5;
                        ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <strong><?= htmlspecialchars($f['etapa']) ?></strong>
                                <small>
                                    <?= (int)$f['total'] ?> opps ·
                                    $<?= number_format($val_fc, 0) ?> forecast
                                </small>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: <?= $pct_bar ?>%;"
                                     aria-valuenow="<?= $pct_bar ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Oportunidades por asesor -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2">
                    Oportunidades por asesor
                </div>
                <div class="card-body p-2">
                    <?php if (empty($opp_por_asesor)): ?>
                        <div class="text-muted">No hay datos de asesores con los filtros actuales.</div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asesor</th>
                                        <th class="text-end">Oportunidades</th>
                                        <th class="text-end">Valor total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($opp_por_asesor as $r): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['usuario_responsable']) ?></td>
                                            <td class="text-end"><?= (int)$r['total'] ?></td>
                                            <td class="text-end">$<?= number_format($r['valor'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Cards en móvil -->
                        <div class="d-md-none">
                            <div class="row g-2">
                                <?php foreach ($opp_por_asesor as $r): ?>
                                    <div class="col-12">
                                        <div class="card border-secondary">
                                            <div class="card-body p-2">
                                                <strong><?= htmlspecialchars($r['usuario_responsable']) ?></strong>
                                                <div><small>Oportunidades: <?= (int)$r['total'] ?></small></div>
                                                <div><small>Valor total: $<?= number_format($r['valor'],2) ?></small></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Actividades próximas -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2">
                    Actividades próximas
                </div>
                <div class="card-body p-2">
                    <?php if (empty($acts_pendientes)): ?>
                        <div class="text-muted">No hay actividades programadas.</div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm table-bordered mb-0" id="tblActsDash">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Lead / OPP</th>
                                        <th>Descripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acts_pendientes as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($a['fecha_programada'],0,16)) ?></td>
                                            <td><?= htmlspecialchars($a['tipo']) ?></td>
                                            <td>
                                                <?php if ($a['nombre_contacto']): ?>
                                                    <div><strong><?= htmlspecialchars($a['nombre_contacto']) ?></strong></div>
                                                <?php endif; ?>
                                                <?php if ($a['opp_titulo']): ?>
                                                    <div><small>OPP: <?= htmlspecialchars($a['opp_titulo']) ?></small></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($a['descripcion']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-md-none">
                            <div class="row g-2">
                                <?php foreach ($acts_pendientes as $a): ?>
                                    <div class="col-12">
                                        <div class="card border-secondary">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between">
                                                    <strong><?= htmlspecialchars($a['tipo']) ?></strong>
                                                    <small><?= htmlspecialchars(substr($a['fecha_programada'],0,16)) ?></small>
                                                </div>
                                                <?php if ($a['descripcion']): ?>
                                                    <div class="mt-1"><?= htmlspecialchars($a['descripcion']) ?></div>
                                                <?php endif; ?>
                                                <?php if ($a['nombre_contacto'] || $a['opp_titulo']): ?>
                                                    <div class="mt-1">
                                                        <?php if ($a['nombre_contacto']): ?>
                                                            <div><small>Lead: <?= htmlspecialchars($a['nombre_contacto']) ?></small></div>
                                                        <?php endif; ?>
                                                        <?php if ($a['opp_titulo']): ?>
                                                            <div><small>OPP: <?= htmlspecialchars($a['opp_titulo']) ?></small></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top oportunidades abiertas -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2">
                    Top oportunidades abiertas
                </div>
                <div class="card-body p-2">
                    <?php if (empty($top_opp)): ?>
                        <div class="text-muted">No hay oportunidades abiertas con los filtros actuales.</div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm table-bordered mb-0" id="tblTopOpp">
                                <thead class="table-light">
                                    <tr>
                                        <th>Folio</th>
                                        <th>Título</th>
                                        <th>Lead / Cliente</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-center">% Prob</th>
                                        <th>Etapa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_opp as $o): ?>
                                        <tr>
                                            <td>#<?= (int)$o['id_opp'] ?></td>
                                            <td><?= htmlspecialchars($o['titulo']) ?></td>
                                            <td>
                                                <?php if ($o['nombre_contacto']): ?>
                                                    <div><strong><?= htmlspecialchars($o['nombre_contacto']) ?></strong></div>
                                                <?php endif; ?>
                                                <?php if ($o['cliente_nombre']): ?>
                                                    <div><small><?= htmlspecialchars($o['cliente_nombre']) ?></small></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">$<?= number_format($o['valor_estimado'], 2) ?></td>
                                            <td class="text-center"><?= (int)$o['probabilidad'] ?>%</td>
                                            <td><?= htmlspecialchars($o['etapa']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-md-none">
                            <div class="row g-2">
                                <?php foreach ($top_opp as $o): ?>
                                    <div class="col-12">
                                        <div class="card border-secondary">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between">
                                                    <strong>#<?= (int)$o['id_opp'] ?> – <?= htmlspecialchars($o['titulo']) ?></strong>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($o['etapa']) ?></span>
                                                </div>
                                                <div class="mt-1">
                                                    <small>Valor: $<?= number_format($o['valor_estimado'],2) ?> · <?= (int)$o['probabilidad'] ?>%</small>
                                                </div>
                                                <?php if ($o['nombre_contacto'] || $o['cliente_nombre']): ?>
                                                    <div class="mt-1">
                                                        <?php if ($o['nombre_contacto']): ?>
                                                            <div><small>Lead: <?= htmlspecialchars($o['nombre_contacto']) ?></small></div>
                                                        <?php endif; ?>
                                                        <?php if ($o['cliente_nombre']): ?>
                                                            <div><small>Cliente: <?= htmlspecialchars($o['cliente_nombre']) ?></small></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $("#tblActsDash").DataTable({
            pageLength: 10,
            scrollY: "200px",
            scrollCollapse: true,
            ordering: false,
            searching: false,
            info: false
        });
        $("#tblTopOpp").DataTable({
            pageLength: 10,
            scrollY: "200px",
            scrollCollapse: true,
            ordering: true,
            searching: false,
            info: false
        });
    }
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
