<?php
// public/dashboard/ocupacion_almacen.php

session_start();
require_once __DIR__ . '/../../app/db.php';

// Forzar mostrar errores (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errorGlobal = '';

// ================== FILTROS ==================
$almacen = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';

// ========== CONSULTA DE DATOS CON TRY/CATCH ==========

// Catálogo de almacenes
try {
    $sqlAlm = "SELECT cve_almac, des_almac 
               FROM c_almacen 
               WHERE Activo = 1 
               ORDER BY des_almac";
    $almacenes = db_all($sqlAlm);
} catch (Throwable $e) {
    $almacenes = [];
    $errorGlobal .= "Error catálogo almacenes: " . $e->getMessage() . "<br>";
}

// Resumen ocupación
$totalUbic = 0;
$totalUsed = 0;
$totalFree = 0;
$porcOcup  = 0.0;
$resumen   = [];

try {
    $paramsRes = [];
    $sqlRes = "SELECT * 
               FROM v_ocupacion_almacen_resumen
               WHERE 1=1";

    if ($almacen !== '') {
        $sqlRes .= " AND cve_almac = ?";
        $paramsRes[] = $almacen;
    }
    $sqlRes .= " ORDER BY des_almac";

    $resumen = db_all($sqlRes, $paramsRes);

    foreach ($resumen as $r) {
        $totalUbic += (int)$r['total_ubicaciones'];
        $totalUsed += (int)$r['ubicaciones_utilizadas'];
        $totalFree += (int)$r['ubicaciones_libres'];
    }

    if ($totalUbic > 0) {
        $porcOcup = round($totalUsed / $totalUbic * 100, 2);
    }
} catch (Throwable $e) {
    $resumen = [];
    $errorGlobal .= "Error resumen ocupación: " . $e->getMessage() . "<br>";
}

// Tipos de ubicación
$tipos = [];
try {
    $paramsTipos = [];
    $sqlTipos = "SELECT * 
                 FROM v_ocupacion_almacen_tipos
                 WHERE 1=1";

    if ($almacen !== '') {
        $sqlTipos .= " AND cve_almac = ?";
        $paramsTipos[] = $almacen;
    }
    $sqlTipos .= " ORDER BY des_almac";

    $tipos = db_all($sqlTipos, $paramsTipos);
} catch (Throwable $e) {
    $tipos = [];
    $errorGlobal .= "Error tipos de ubicación: " . $e->getMessage() . "<br>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dashboard Ocupación de Almacén</title>

    <!-- Bootstrap / jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        body {
            font-size: 0.80rem;
        }
        .card-kpi {
            border-radius: 0.75rem;
        }
        .kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .kpi-label {
            font-size: 0.80rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .chart-box {
            min-height: 260px;
        }
    </style>
</head>
<body>
<?php
// menú global (ajusta ruta si tu _menu_global.php está en otra carpeta)
$menuPath = __DIR__ . '/../bi/_menu_global.php';
if (file_exists($menuPath)) {
    require_once $menuPath;
}
?>

<div class="container-fluid mt-3 mb-4">

    <?php if ($errorGlobal !== ''): ?>
        <div class="alert alert-danger py-1">
            <strong>Errores detectados:</strong><br>
            <?= $errorGlobal ?>
        </div>
    <?php endif; ?>

    <div class="row mb-2">
        <div class="col-12">
            <h5 class="mb-0">Dashboard Ocupación de Almacén</h5>
            <small class="text-muted">Ocupación de ubicaciones y tipos de zona por almacén.</small>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-3 col-sm-6">
            <label for="almacen" class="form-label mb-1">Almacén</label>
            <select name="almacen" id="almacen" class="form-select form-select-sm">
                <option value="">[Todos]</option>
                <?php foreach ($almacenes as $a): ?>
                    <option value="<?= htmlspecialchars($a['cve_almac']) ?>"
                        <?= ($almacen !== '' && $almacen == $a['cve_almac']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['cve_almac'] . ' - ' . $a['des_almac']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6">
            <button type="submit" class="btn btn-primary btn-sm">
                Aplicar filtros
            </button>
        </div>
    </form>

    <!-- KPIs superiores -->
    <div class="row g-2 mb-3">
        <div class="col-md-4 col-sm-6">
            <div class="card card-kpi shadow-sm">
                <div class="card-body py-2">
                    <div class="kpi-label text-muted">Ocupación promedio</div>
                    <div class="kpi-value"><?= number_format($porcOcup, 2) ?>%</div>
                    <div class="text-muted">Ubic usadas: <?= number_format($totalUsed) ?> / <?= number_format($totalUbic) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card card-kpi shadow-sm">
                <div class="card-body py-2">
                    <div class="kpi-label text-muted">Ubicaciones libres</div>
                    <div class="kpi-value"><?= number_format($totalFree) ?></div>
                    <div class="text-muted">Disponibles para acomodo</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card card-kpi shadow-sm">
                <div class="card-body py-2">
                    <div class="kpi-label text-muted">Almacenes en reporte</div>
                    <div class="kpi-value"><?= count($resumen) ?></div>
                    <div class="text-muted">Aplicando filtros actuales</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-2 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header py-1">
                    <small class="fw-semibold">Ocupación por almacén</small>
                </div>
                <div class="card-body chart-box">
                    <div id="chartOcupacion"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header py-1">
                    <small class="fw-semibold">Distribución de tipos de ubicación (total)</small>
                </div>
                <div class="card-body chart-box">
                    <div id="chartTipos"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$menuEndPath = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menuEndPath)) {
    require_once $menuEndPath;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function () {

    // --------- Ocupación por almacén ----------
    var dataOcup = <?php
        $labels = [];
        $values = [];
        foreach ($resumen as $r) {
            $labels[] = $r['des_almac'];
            $values[] = (float)$r['porcentaje_ocupacion'];
        }
        echo json_encode(['labels' => $labels, 'values' => $values]);
    ?>;

    if (dataOcup.labels.length > 0) {
        var optionsOcup = {
            series: [{
                name: '% Ocupación',
                data: dataOcup.values
            }],
            chart: {
                type: 'bar',
                height: 240
            },
            plotOptions: {
                bar: {
                    horizontal: true
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) { return val.toFixed(1) + '%'; }
            },
            xaxis: {
                categories: dataOcup.labels,
                labels: { formatter: function (val) { return val + '%'; } }
            },
            tooltip: {
                y: { formatter: function (val) { return val.toFixed(2) + '%'; } }
            }
        };
        var chartOcup = new ApexCharts(document.querySelector("#chartOcupacion"), optionsOcup);
        chartOcup.render();
    } else {
        document.querySelector("#chartOcupacion").innerHTML = '<span class="text-muted">Sin datos para el filtro actual.</span>';
    }

    // --------- Tipos de ubicación (sumados) ----------
    var dataTipos = <?php
        $sumaMixto = $sumaReabasto = $sumaPicking = $sumaMfg = $sumaStg = $sumaPtl = 0;
        foreach ($tipos as $t) {
            $sumaMixto    += (int)$t['ubic_acomodo_mixto'];
            $sumaReabasto += (int)$t['ubic_reabasto'];
            $sumaPicking  += (int)$t['ubic_picking'];
            $sumaMfg      += (int)$t['ubic_manufactura'];
            $sumaStg      += (int)$t['ubic_staging'];
            $sumaPtl      += (int)$t['ubic_ptl'];
        }
        echo json_encode([
            'labels' => ['Mixto','Reabasto','Picking','Manufactura','Staging','PTL'],
            'values' => [
                $sumaMixto, $sumaReabasto, $sumaPicking,
                $sumaMfg, $sumaStg, $sumaPtl
            ]
        ]);
    ?>;

    var totalTipos = dataTipos.values.reduce(function (a, b) { return a + b; }, 0);

    if (totalTipos > 0) {
        var optionsTipos = {
            series: dataTipos.values,
            labels: dataTipos.labels,
            chart: {
                type: 'donut',
                height: 240
            },
            dataLabels: { enabled: true },
            legend: { position: 'bottom' }
        };
        var chartTipos = new ApexCharts(document.querySelector("#chartTipos"), optionsTipos);
        chartTipos.render();
    } else {
        document.querySelector("#chartTipos").innerHTML = '<span class="text-muted">Sin datos de tipos para el filtro actual.</span>';
    }
});
</script>
</body>
</html>
