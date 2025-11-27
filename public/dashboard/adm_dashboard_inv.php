<?php
// public/dashboard/adm_dashboard_inv.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$errorMsg = '';

// Helper seguro para imprimir (evita TypeError en PHP 8)
function h($v): string {
    if ($v === null) return '';
    if (is_int($v) || is_float($v)) {
        $v = (string)$v;
    } elseif (!is_string($v)) {
        $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// -------------------------
// Filtros
// -------------------------
$tipo    = strtoupper($_GET['tipo'] ?? 'A');      // F = Físico, C = Cíclico, A = Ambos
$almacen = trim($_GET['almacen'] ?? '');
$f_ini   = trim($_GET['f_ini'] ?? '');
$f_fin   = trim($_GET['f_fin'] ?? '');

// -------------------------
// Catálogo de almacenes (ligero)
// -------------------------
try {
    $almacenes = db_all("
        SELECT cve_almac AS clave, des_almac AS nombre
        FROM c_almacen
        WHERE (Activo IS NULL OR Activo <> 0)
        ORDER BY des_almac
    ");
} catch (Throwable $e) {
    $almacenes = [];
    $errorMsg  = $errorMsg ?: $e->getMessage();
}

// -------------------------
// Carga de datos desde vistas (limit 25)
// -------------------------
function load_fisico(string $almacen, string $f_ini, string $f_fin, int $maxRows = 25): array {
    $where = [];
    $par   = [];

    if ($almacen !== '') {
        $where[] = "cve_almacen = ?";
        $par[]   = $almacen;
    }
    if ($f_ini !== '' && $f_fin !== '') {
        $where[] = "fecha_creacion BETWEEN ? AND ?";
        $par[]   = $f_ini . " 00:00:00";
        $par[]   = $f_fin . " 23:59:59";
    }

    $sql = "SELECT folio_inventario, fecha_creacion, nombre_inventario, status_inventario,
                   cve_almacen, almacen, avance_porcentual, estado_proceso,
                   ubicaciones_planeadas, ubicaciones_contadas,
                   piezas_contadas, piezas_teoricas, diferencia_piezas
            FROM v_dashboard_inv_fisico_ao";

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY fecha_creacion DESC LIMIT " . (int)$maxRows;

    return db_all($sql, $par);
}

function load_ciclico(string $almacen, string $f_ini, string $f_fin, int $maxRows = 25): array {
    $where = [];
    $par   = [];

    if ($almacen !== '') {
        $where[] = "cve_almacen = ?";
        $par[]   = $almacen;
    }
    if ($f_ini !== '' && $f_fin !== '') {
        $where[] = "fecha_inicio BETWEEN ? AND ?";
        $par[]   = $f_ini . " 00:00:00";
        $par[]   = $f_fin . " 23:59:59";
    }

    $sql = "SELECT folio_plan, fecha_inicio, fecha_fin,
                   cve_almacen, des_almacen AS almacen,
                   avance_porcentual, estado_proceso,
                   ubicaciones_planeadas, ubicaciones_contadas,
                   piezas_contadas, piezas_teoricas, diferencia_piezas
            FROM v_dashboard_inv_ciclico_ao";

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY fecha_inicio DESC LIMIT " . (int)$maxRows;

    return db_all($sql, $par);
}

$fisico  = [];
$ciclico = [];

try {
    if ($tipo === 'F' || $tipo === 'A') {
        $fisico = load_fisico($almacen, $f_ini, $f_fin);
    }
    if ($tipo === 'C' || $tipo === 'A') {
        $ciclico = load_ciclico($almacen, $f_ini, $f_fin);
    }
} catch (Throwable $e) {
    $fisico = $ciclico = [];
    $errorMsg = $errorMsg ?: $e->getMessage();
}

// -------------------------
// KPIs y datos para gráficas
// -------------------------
function agg_estado(array $rows): array {
    $est = ['Planeado'=>0,'En ejecución'=>0,'Completado'=>0,'Cerrado'=>0];
    foreach ($rows as $r) {
        $e = $r['estado_proceso'] ?? '';
        if (!isset($est[$e])) {
            $est[$e] = 0;
        }
        $est[$e]++;
    }
    return $est;
}

function labels_values_avance(array $rows, string $campoFolio): array {
    $labels = [];
    $values = [];
    foreach ($rows as $r) {
        $labels[] = (string)($r[$campoFolio] ?? '');
        $values[] = (float)($r['avance_porcentual'] ?? 0);
    }
    return [$labels, $values];
}

$estFis = agg_estado($fisico);
$estCic = agg_estado($ciclico);

list($labFis, $valFis) = labels_values_avance($fisico, 'folio_inventario');
list($labCic, $valCic) = labels_values_avance($ciclico, 'folio_plan');

$tot_fis = count($fisico);
$tot_cic = count($ciclico);

$sum_pzas_f = array_sum(array_map(fn($r) => (float)($r['piezas_contadas']   ?? 0), $fisico));
$sum_difp_f = array_sum(array_map(fn($r) => (float)($r['diferencia_piezas'] ?? 0), $fisico));
$sum_pzas_c = array_sum(array_map(fn($r) => (float)($r['piezas_contadas']   ?? 0), $ciclico));
$sum_difp_c = array_sum(array_map(fn($r) => (float)($r['diferencia_piezas'] ?? 0), $ciclico));
?>
<div class="container-fluid py-3" style="font-size:10px;">

    <div class="row mb-2">
        <div class="col">
            <h5 class="mb-0">Dashboard de Inventarios (Gráfico)</h5>
            <small class="text-muted">Inventario Físico y Cíclico &mdash; Estado y avance</small>
        </div>
    </div>

    <?php if ($errorMsg): ?>
        <div class="alert alert-warning py-2" style="font-size:11px;">
            <strong>Nota:</strong> <?= h($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="get" class="card mb-3 shadow-sm border-0">
        <div class="card-body p-2">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="F" <?= $tipo==='F'?'selected':''; ?>>Físico</option>
                        <option value="C" <?= $tipo==='C'?'selected':''; ?>>Cíclico</option>
                        <option value="A" <?= $tipo==='A'?'selected':''; ?>>Ambos</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Almacén</label>
                    <select name="almacen" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= h($a['clave']) ?>" <?= $almacen===$a['clave']?'selected':''; ?>>
                                <?= h($a['nombre']) ?> (<?= h($a['clave']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Fecha inicio</label>
                    <input type="date" name="f_ini" class="form-control form-control-sm" value="<?= h($f_ini) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Fecha fin</label>
                    <input type="date" name="f_fin" class="form-control form-control-sm" value="<?= h($f_fin) ?>">
                </div>
                <div class="col-6 col-md-1 text-end">
                    <button class="btn btn-sm btn-primary px-3">Aplicar</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div class="fw-bold">Inventarios físicos</div>
                    <div class="h5 mb-0"><?= number_format($tot_fis) ?></div>
                    <small class="text-muted">
                        Pzas: <?= number_format($sum_pzas_f) ?> · Dif: <?= number_format($sum_difp_f) ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div class="fw-bold">Inventarios cíclicos</div>
                    <div class="h5 mb-0"><?= number_format($tot_cic) ?></div>
                    <small class="text-muted">
                        Pzas: <?= number_format($sum_pzas_c) ?> · Dif: <?= number_format($sum_difp_c) ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div class="fw-bold">Físico · En ejecución</div>
                    <div class="h6 mb-0"><?= number_format($estFis['En ejecución'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div class="fw-bold">Cíclico · En ejecución</div>
                    <div class="h6 mb-0"><?= number_format($estCic['En ejecución'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div class="fw-bold mb-2">Distribución por estado</div>
                    <canvas id="chartEstados" height="160"></canvas>
                    <small class="text-muted">Planeado / En ejecución / Completado / Cerrado</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div class="fw-bold mb-2">Avance por folio</div>
                    <canvas id="chartAvance" height="160"></canvas>
                    <small class="text-muted">% avance (ubicaciones / piezas)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla resumen (máx 25 por tipo) -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-2">
            <div class="table-responsive" style="max-height:350px; overflow-y:auto; overflow-x:auto;">
                <table id="tblResumen" class="table table-sm table-striped table-bordered w-100" style="font-size:10px;">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Folio</th>
                            <th>Almacén</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>% Avance</th>
                            <th>Ubic. Plan</th>
                            <th>Ubic. Cont</th>
                            <th>Pzas Cont</th>
                            <th>Dif Pzas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tipo==='F' || $tipo==='A'): ?>
                            <?php foreach ($fisico as $r): ?>
                                <tr>
                                    <td>Físico</td>
                                    <td><?= h($r['folio_inventario'] ?? '') ?></td>
                                    <td><?= h($r['almacen'] ?? '') ?> (<?= h($r['cve_almacen'] ?? '') ?>)</td>
                                    <td><?= h($r['fecha_creacion'] ?? '') ?></td>
                                    <td><?= h($r['estado_proceso'] ?? '') ?></td>
                                    <td class="text-end"><?= number_format((float)($r['avance_porcentual'] ?? 0), 2) ?>%</td>
                                    <td class="text-end"><?= number_format((float)($r['ubicaciones_planeadas'] ?? 0)) ?></td>
                                    <td class="text-end"><?= number_format((float)($r['ubicaciones_contadas'] ?? 0)) ?></td>
                                    <td class="text-end"><?= number_format((float)($r['piezas_contadas'] ?? 0)) ?></td>
                                    <td class="text-end"><?= number_format((float)($r['diferencia_piezas'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($tipo==='C' || $tipo==='A'): ?>
                            <?php foreach ($ciclico as $r): ?>
                                <tr>
                                    <td>Cíclico</td>
                                    <td><?= h($r['folio_plan'] ?? '') ?></td>
                                    <td><?= h($r['almacen'] ?? '') ?> (<?= h($r['cve_almacen'] ?? '') ?>)</td>
                                    <td><?= h($r['fecha_inicio'] ?? '') ?></td>
                                    <td><?= h($r['estado_proceso'] ?? '') ?></td>
                                    <td class="text-end"><?= number_format((float)($r['avance_porcentual'] ?? 0), 2) ?>%</td>
                                    <td class="text-end"><?= number_format((float)($r['ubicaciones_planeadas'] ?? 0)) ?></td>
                                    <td class="text-end"><?= number_format((float)($r['ubicaciones_contadas'] ?? 0)) ?></td>
                                    <td class="text-end"><?= number_format((float)($r['piezas_contadas'] ?? 0)) ?></td>
                                    <td class="text-end"><?= number_format((float)($r['diferencia_piezas'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const tipo  = <?= json_encode($tipo) ?>;
    const estFis = <?= json_encode($estFis) ?>;
    const estCic = <?= json_encode($estCic) ?>;
    const labelsEstados = ['Planeado','En ejecución','Completado','Cerrado'];

    let dataEstados = [0,0,0,0];
    if (tipo === 'F') {
        dataEstados = labelsEstados.map(l => (estFis[l] || 0));
    } else if (tipo === 'C') {
        dataEstados = labelsEstados.map(l => (estCic[l] || 0));
    } else {
        dataEstados = labelsEstados.map(l => (estFis[l] || 0) + (estCic[l] || 0));
    }

    const labFis = <?= json_encode($labFis) ?>;
    const valFis = <?= json_encode($valFis) ?>;
    const labCic = <?= json_encode($labCic) ?>;
    const valCic = <?= json_encode($valCic) ?>;

    let labelsAv = [], valuesAv = [];
    if (tipo === 'F') {
        labelsAv = labFis;
        valuesAv = valFis;
    } else if (tipo === 'C') {
        labelsAv = labCic;
        valuesAv = valCic;
    } else {
        labelsAv = labFis.concat(labCic.map(f => 'P' + f)); // prefijo para diferenciar
        valuesAv = valFis.concat(valCic);
    }

    const ctx1 = document.getElementById('chartEstados');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'doughnut',
            data: { labels: labelsEstados, datasets: [{ data: dataEstados }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    const ctx2 = document.getElementById('chartAvance');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: { labels: labelsAv, datasets: [{ label: '% Avance', data: valuesAv }] },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, max: 100 } },
                plugins: { legend: { display: false } }
            }
        });
    }

    if (window.jQuery && $.fn.DataTable) {
        $('#tblResumen').DataTable({
            pageLength: 25,
            lengthChange: false,
            scrollX: true,
            searching: false,
            info: false,
            paging: false,
            order: [[3,'desc']],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
        });
    }
})();
</script>
