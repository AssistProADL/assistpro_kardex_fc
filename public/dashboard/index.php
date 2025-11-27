<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$errorMsg = '';

// Helper seguro para imprimir en HTML (evita TypeError en PHP 8)
function h($v): string {
    if ($v === null) return '';
    if (is_int($v) || is_float($v)) {
        $v = (string)$v;
    } elseif (!is_string($v)) {
        $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// ===============================
// KPIs globales (4 tarjetas)
// ===============================
try {
    $totAlmacenes = (int) db_val("
        SELECT COUNT(*)
        FROM c_almacen
        WHERE Activo IS NULL OR Activo <> 0
    ");

    $totArticulos = (int) db_val("
        SELECT COUNT(*)
        FROM c_articulo
    ");

    // Para no matar la BD, contamos solo últimos 30 días
    $totEntradas = (int) db_val("
        SELECT COUNT(*)
        FROM th_entalmacen
        WHERE Fec_Entrada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");

    $totSalidas = (int) db_val("
        SELECT COUNT(*)
        FROM th_salalmacen
        WHERE fec_salida >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
} catch (Throwable $e) {
    $totAlmacenes = $totArticulos = $totEntradas = $totSalidas = 0;
    $errorMsg = $e->getMessage();
}

// ===============================
// Movimientos clásicos (máx 25)
// ===============================
$movimientos = [];

try {
    $entradas = db_all("
        SELECT
            'ENTRADA' AS tipo_mov,
            Fol_Folio AS folio,
            Cve_Almac AS almac,
            Fec_Entrada AS fecha,
            Cve_Usuario AS usuario,
            Fol_OEP AS doc_ref
        FROM th_entalmacen
        WHERE Fec_Entrada IS NOT NULL
        ORDER BY Fec_Entrada DESC
        LIMIT 25
    ");

    $salidas = db_all("
        SELECT
            'SALIDA' AS tipo_mov,
            fol_folio AS folio,
            Cve_Almac AS almac,
            DATE_FORMAT(fec_salida, '%Y-%m-%d %H:%i:%s') AS fecha,
            cve_usuario AS usuario,
            RefFolio AS doc_ref
        FROM th_salalmacen
        WHERE fec_salida IS NOT NULL
        ORDER BY fec_salida DESC
        LIMIT 25
    ");

    $movimientos = array_merge($entradas, $salidas);

    // Ordenar en PHP por fecha DESC
    usort($movimientos, function ($a, $b) {
        return strcmp((string)($b['fecha'] ?? ''), (string)($a['fecha'] ?? ''));
    });

    // Solo 25 para grilla y gráfica
    $movimientos = array_slice($movimientos, 0, 25);

} catch (Throwable $e) {
    $movimientos = [];
    $errorMsg = $errorMsg ?: $e->getMessage();
}

// ===============================
// Existencias por tarima / artículo (máx 25, sin ORDER BY pesado)
// ===============================
$existencias = [];

try {
    $existencias = db_all("
        SELECT
            t.cve_almac,
            t.cve_articulo,
            a.des_articulo,
            t.lote,
            t.existencia
        FROM ts_existenciatarima t
        LEFT JOIN c_articulo a
               ON a.cve_articulo = t.cve_articulo
        WHERE t.existencia IS NOT NULL
        LIMIT 25
    ");
} catch (Throwable $e) {
    $existencias = [];
    $errorMsg = $errorMsg ?: $e->getMessage();
}

// ===============================
// Datos para gráficas (sin consultas extra)
// ===============================

// Entradas vs Salidas por día
$contEnt = [];
$contSal = [];

foreach ($movimientos as $m) {
    $fecha = substr((string)($m['fecha'] ?? ''), 0, 10); // YYYY-MM-DD
    if ($fecha === '') continue;

    if (($m['tipo_mov'] ?? '') === 'ENTRADA') {
        $contEnt[$fecha] = ($contEnt[$fecha] ?? 0) + 1;
    } else {
        $contSal[$fecha] = ($contSal[$fecha] ?? 0) + 1;
    }
}

$fechas = array_unique(array_merge(array_keys($contEnt), array_keys($contSal)));
sort($fechas);

$dataEnt = [];
$dataSal = [];
foreach ($fechas as $f) {
    $dataEnt[] = $contEnt[$f] ?? 0;
    $dataSal[] = $contSal[$f] ?? 0;
}

// Top 10 artículos por existencia (acumulado de esas 25 filas)
$acumArt = [];
$descArt = [];

foreach ($existencias as $e) {
    $art = (string)($e['cve_articulo'] ?? '');
    if ($art === '') continue;
    $acumArt[$art] = ($acumArt[$art] ?? 0) + (float)($e['existencia'] ?? 0);
    if (!isset($descArt[$art])) {
        $descArt[$art] = (string)($e['des_articulo'] ?? '');
    }
}

arsort($acumArt);
$acumArt = array_slice($acumArt, 0, 10, true);

$topLabels = [];
$topValues = [];
foreach ($acumArt as $art => $exist) {
    $topLabels[] = trim($art . ' - ' . ($descArt[$art] ?? ''));
    $topValues[] = $exist;
}
?>
<div class="container-fluid mt-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">AssistPro WMS &mdash; Dashboard Inicial</h4>
            <small class="text-muted">
                Movimientos clásicos generales y datos globales iniciales
            </small>
        </div>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-warning py-2" style="font-size:11px;">
            <strong>Nota:</strong> <?= h($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- KPIs: 4 tarjetas -->
    <div class="row g-3">
        <!-- Almacenes -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column py-2">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <div class="text-muted" style="font-size:10px;">ALMACENES ACTIVOS</div>
                            <div class="fw-bold" style="font-size:22px;"><?= number_format($totAlmacenes) ?></div>
                        </div>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                             style="width:40px;height:40px;">
                            <i class="fa fa-warehouse"></i>
                        </div>
                    </div>
                    <small class="text-muted mt-auto" style="font-size:10px;">
                        Catálogo de almacenes (c_almacen).
                    </small>
                </div>
            </div>
        </div>

        <!-- Artículos -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column py-2">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <div class="text-muted" style="font-size:10px;">ARTÍCULOS</div>
                            <div class="fw-bold" style="font-size:22px;"><?= number_format($totArticulos) ?></div>
                        </div>
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                             style="width:40px;height:40px;">
                            <i class="fa fa-box"></i>
                        </div>
                    </div>
                    <small class="text-muted mt-auto" style="font-size:10px;">
                        Catálogo maestro de artículos (c_articulo).
                    </small>
                </div>
            </div>
        </div>

        <!-- Entradas -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column py-2">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <div class="text-muted" style="font-size:10px;">ENTRADAS (últimos 30 días)</div>
                            <div class="fw-bold" style="font-size:22px;"><?= number_format($totEntradas) ?></div>
                        </div>
                        <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center"
                             style="width:40px;height:40px;">
                            <i class="fa fa-arrow-down"></i>
                        </div>
                    </div>
                    <small class="text-muted mt-auto" style="font-size:10px;">
                        Registros en th_entalmacen (30 días).
                    </small>
                </div>
            </div>
        </div>

        <!-- Salidas -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column py-2">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <div class="text-muted" style="font-size:10px;">SALIDAS (últimos 30 días)</div>
                            <div class="fw-bold" style="font-size:22px;"><?= number_format($totSalidas) ?></div>
                        </div>
                        <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center"
                             style="width:40px;height:40px;">
                            <i class="fa fa-arrow-up"></i>
                        </div>
                    </div>
                    <small class="text-muted mt-auto" style="font-size:10px;">
                        Registros en th_salalmacen (30 días).
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row mt-4 g-3">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="mb-1">Entradas vs Salidas (últimos movimientos)</h6>
                    <small class="text-muted" style="font-size:10px;">
                        Conteo por día basado en los últimos 25 movimientos.
                    </small>
                    <div style="height:280px;">
                        <canvas id="chartEntradasSalidas"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="mb-1">Top 10 artículos por existencia</h6>
                    <small class="text-muted" style="font-size:10px;">
                        Basado en las 25 filas de mayor prioridad consultadas.
                    </small>
                    <div style="height:280px;">
                        <canvas id="chartTopArticulos"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grillas -->
    <div class="row mt-4 g-3">

        <!-- Movimientos clásicos -->
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="mb-1">Movimientos clásicos generales</h6>
                    <small class="text-muted" style="font-size:10px;">
                        Últimos 25 movimientos (entradas y salidas).
                    </small>
                    <div class="table-responsive mt-2" style="max-height:300px; overflow-y:auto; overflow-x:auto;">
                        <table id="tabla-movimientos" class="table table-striped table-bordered table-sm"
                               style="width:100%;font-size:10px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Folio</th>
                                    <th>Almacén</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Documento ref.</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td><?= h($m['tipo_mov'] ?? '') ?></td>
                                    <td><?= h($m['folio'] ?? '') ?></td>
                                    <td><?= h($m['almac'] ?? '') ?></td>
                                    <td><?= h($m['fecha'] ?? '') ?></td>
                                    <td><?= h($m['usuario'] ?? '') ?></td>
                                    <td><?= h($m['doc_ref'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existencias -->
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="mb-1">Top existencias por tarima / artículo</h6>
                    <small class="text-muted" style="font-size:10px;">
                        25 registros consultados de ts_existenciatarima.
                    </small>
                    <div class="table-responsive mt-2" style="max-height:300px; overflow-y:auto; overflow-x:auto;">
                        <table id="tabla-existencias" class="table table-striped table-bordered table-sm"
                               style="width:100%;font-size:10px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Almacén</th>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th>Lote</th>
                                    <th class="text-end">Existencia</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($existencias as $e): ?>
                                <tr>
                                    <td><?= h($e['cve_almac'] ?? '') ?></td>
                                    <td><?= h($e['cve_articulo'] ?? '') ?></td>
                                    <td><?= h($e['des_articulo'] ?? '') ?></td>
                                    <td><?= h($e['lote'] ?? '') ?></td>
                                    <td class="text-end">
                                        <?= number_format((float)($e['existencia'] ?? 0), 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#tabla-movimientos').DataTable({
            pageLength: 25,
            lengthChange: false,
            scrollX: true,
            searching: false,
            info: false,
            paging: false,
            order: [[3, 'desc']],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
        });

        $('#tabla-existencias').DataTable({
            pageLength: 25,
            lengthChange: false,
            scrollX: true,
            searching: false,
            info: false,
            paging: false,
            order: [[4, 'desc']],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
        });
    }

    const ctxES = document.getElementById('chartEntradasSalidas');
    if (ctxES) {
        new Chart(ctxES, {
            type: 'line',
            data: {
                labels: <?= json_encode($fechas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                datasets: [
                    {
                        label: 'Entradas',
                        data: <?= json_encode($dataEnt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: 'Salidas',
                        data: <?= json_encode($dataSal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        borderWidth: 2,
                        fill: false
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    const ctxTop = document.getElementById('chartTopArticulos');
    if (ctxTop) {
        new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: <?= json_encode($topLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                datasets: [{
                    label: 'Existencia total',
                    data: <?= json_encode($topValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
