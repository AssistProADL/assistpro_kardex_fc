<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

/* ==========================
   CONFIG & DEBUG
========================== */
$DEBUG = isset($_GET['debug']) && $_GET['debug'] == 1;

function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/* ==========================
   QUERY PRINCIPAL
========================== */
$sql = "
    SELECT 
        ID_Inventario,
        Nombre AS folio,
        Fecha,
        Status,
        Inv_Inicial,
        Activo,
        cve_almacen,
        cve_zona
    FROM th_inventario
    ORDER BY Fecha DESC
    LIMIT 300
";

$inventarios = db_all($sql);

/* ==========================
   KPIs
========================== */
$total = count($inventarios);
$abiertos = 0;
$iniciales = 0;
$fisicos = 0;

foreach ($inventarios as $i) {
    if ($i['Status'] === 'A') $abiertos++;
    if ((int)$i['Inv_Inicial'] === 1) $iniciales++;
    else $fisicos++;
}
?>

<style>
.ap-card {
    background: linear-gradient(135deg,#0f5aad,#1e88e5);
    color:#fff;
    border-radius:12px;
    padding:18px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
.ap-card small {
    opacity:.85;
}
.ap-card h2 {
    margin:0;
    font-size:26px;
}
.ap-table th {
    background:#0f5aad;
    color:#fff;
    font-size:11px;
}
.ap-table td {
    font-size:11px;
    vertical-align:middle;
}
.badge-ap {
    padding:4px 8px;
    border-radius:6px;
    font-size:10px;
    font-weight:600;
}
.badge-open { background:#4caf50; color:#fff; }
.badge-close { background:#607d8b; color:#fff; }
.badge-cancel { background:#f44336; color:#fff; }
</style>

<div class="container-fluid mt-4">

    <h4 class="mb-3">
        📦 Administración de Inventarios
    </h4>

    <!-- ================= KPI CARDS ================= -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="ap-card">
                <small>Total inventarios</small>
                <h2><?= $total ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ap-card">
                <small>Inventarios abiertos</small>
                <h2><?= $abiertos ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ap-card">
                <small>Físicos</small>
                <h2><?= $fisicos ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ap-card">
                <small>Iniciales</small>
                <h2><?= $iniciales ?></h2>
            </div>
        </div>
    </div>

    <!-- ================= DEBUG ================= -->
    <?php if ($DEBUG): ?>
        <div class="alert alert-dark">
            <pre><?= print_r($inventarios, true) ?></pre>
        </div>
    <?php endif; ?>

    <!-- ================= GRID ================= -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            Últimos inventarios (máx 300)
        </div>
        <div class="card-body p-0">
            <div style="max-height:520px;overflow:auto">
                <table class="table table-striped table-bordered ap-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Fecha</th>
                            <th>Almacén</th>
                            <th>Zona</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inventarios as $row): 
                        $tipo = ((int)$row['Inv_Inicial'] === 1) ? 'INICIAL' : 'FÍSICO';

                        $statusTxt = $row['Status'];
                        $badge = 'badge-close';

                        if ($row['Status'] === 'A') {
                            $statusTxt = 'Abierto';
                            $badge = 'badge-open';
                        } elseif ($row['Status'] === 'T') {
                            $statusTxt = 'Cerrado';
                        } elseif ($row['Status'] === 'K') {
                            $statusTxt = 'Cancelado';
                            $badge = 'badge-cancel';
                        }
                    ?>
                        <tr>
                            <td><?= esc($row['ID_Inventario']) ?></td>
                            <td><?= esc($row['folio'] ?? '—') ?></td>
                            <td><?= $tipo ?></td>
                            <td>
                                <span class="badge-ap <?= $badge ?>">
                                    <?= $statusTxt ?>
                                </span>
                            </td>
                            <td><?= esc($row['Fecha']) ?></td>
                            <td><?= esc($row['cve_almacen'] ?? '—') ?></td>
                            <td><?= esc($row['cve_zona'] ?? '—') ?></td>
                            <td>
                                <a href="../planeacion/planificar_inventario.php?id=<?= (int)$row['ID_Inventario'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
