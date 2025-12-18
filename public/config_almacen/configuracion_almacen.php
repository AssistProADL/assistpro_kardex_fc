<?php
// configuracion_almacen.php
// Dashboard de configuración de almacén (vista corporativa AssistPro)

require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php'; // Ajusta ruta si es diferente

// =====================================================
// 1. ALMACENES PADRE (c_almacenp) QUE TIENEN UBICACIONES
// =====================================================

$almacenes = db_all("
    SELECT DISTINCT
        ap.id   AS id_almacenp,
        ap.clave,
        ap.nombre
    FROM c_almacenp ap
    JOIN c_almacen a
        ON a.cve_almacenp = ap.id
    JOIN c_ubicacion u
        ON u.cve_almac = CAST(a.cve_almac AS UNSIGNED)
    ORDER BY ap.nombre
");

$almacenPadreSeleccionado = isset($_GET['almacen']) && $_GET['almacen'] !== ''
    ? $_GET['almacen']
    : (isset($almacenes[0]['id_almacenp']) ? $almacenes[0]['id_almacenp'] : null);

// =====================================================
// 2. ZONAS (c_almacen) DEL ALMACÉN SELECCIONADO
// =====================================================

$zonas = [];
if (!empty($almacenPadreSeleccionado)) {
    $zonas = db_all("
        SELECT DISTINCT
            CAST(a.cve_almac AS UNSIGNED) AS cve_almac_int,
            a.des_almac
        FROM c_almacen a
        JOIN c_ubicacion u
            ON u.cve_almac = CAST(a.cve_almac AS UNSIGNED)
        WHERE a.cve_almacenp = :id_almacenp
        ORDER BY a.des_almac
    ", [':id_almacenp' => $almacenPadreSeleccionado]);
}

$zonaSeleccionada = isset($_GET['zona']) && $_GET['zona'] !== '' ? (int)$_GET['zona'] : null;

// =====================================================
// 3. UBICACIONES (c_ubicacion)
// =====================================================

$where = [];
$params = [];

if (!empty($almacenPadreSeleccionado)) {
    $where[] = "ap.id = :id_almacenp";
    $params[':id_almacenp'] = $almacenPadreSeleccionado;
}
if (!empty($zonaSeleccionada)) {
    $where[] = "u.cve_almac = :zona";
    $params[':zona'] = $zonaSeleccionada;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlUbicaciones = "
    SELECT 
        u.*,
        ap.nombre   AS nombre_almacen_padre,
        ap.clave    AS clave_almacen_padre,
        a.des_almac AS nombre_zona,
        CAST(a.cve_almac AS UNSIGNED) AS cve_almac_int
    FROM c_ubicacion u
    LEFT JOIN c_almacen a
        ON u.cve_almac = CAST(a.cve_almac AS UNSIGNED)
    LEFT JOIN c_almacenp ap
        ON a.cve_almacenp = ap.id
    $whereSql
    ORDER BY 
        ap.nombre,
        a.des_almac,
        u.cve_pasillo,
        u.cve_rack,
        u.cve_nivel,
        u.Ubicacion
";

$ubicaciones = db_all($sqlUbicaciones, $params);

// =====================================================
// 4. KPIs BÁSICOS
// =====================================================

$totales = [
    'total'           => 0,
    'activas'         => 0,
    'picking'         => 0,
    'reabasto'        => 0,
    'prod'            => 0,
    'stagging'        => 0,
    'ptl'             => 0,
    'acomodo_mixto'   => 0,
];

foreach ($ubicaciones as $u) {
    $totales['total']++;
    if ((int)$u['Activo'] === 1) $totales['activas']++;
    if (strtoupper($u['picking']) === 'S') $totales['picking']++;
    if (strtoupper($u['Reabasto']) === 'S') $totales['reabasto']++;
    if (strtoupper($u['AreaProduccion']) === 'S') $totales['prod']++;
    if (strtoupper($u['AreaStagging']) === 'S') $totales['stagging']++;
    if (strtoupper($u['Ptl']) === 'S') $totales['ptl']++;
    if (strtoupper($u['AcomodoMixto']) === 'S') $totales['acomodo_mixto']++;
}

$nombreAlmacenActual = 'Todos los almacenes';
foreach ($almacenes as $a) {
    if ($a['id_almacenp'] == $almacenPadreSeleccionado) {
        $nombreAlmacenActual = $a['nombre'];
        break;
    }
}
$nombreZonaActual = '';
foreach ($zonas as $z) {
    if ((int)$z['cve_almac_int'] === $zonaSeleccionada) {
        $nombreZonaActual = $z['cve_almac_int'] . ' - ' . $z['des_almac'];
        break;
    }
}
?>
<div class="container-fluid py-3" style="font-size:10px;">
    <!-- Título + filtros -->
    <div class="row align-items-end mb-3">
        <div class="col-lg-5">
            <div class="fw-bold" style="font-size:16px;color:#0F5AAD;">Configuración de Almacén</div>
            <div class="text-muted" style="font-size:10px;">
                Relación <strong>Almacén → Zonas → Ubicaciones (BL)</strong>.
            </div>
        </div>
        <div class="col-lg-7">
            <form method="get" class="row g-2 justify-content-end">
                <div class="col-md-5">
                    <label class="mb-1" style="font-size:9px;text-transform:uppercase;color:#6c757d;">Almacén</label>
                    <select name="almacen" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= htmlspecialchars($a['id_almacenp'] ?? '') ?>"
                                <?= ($a['id_almacenp'] == $almacenPadreSeleccionado) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['clave'] . ' - ' . $a['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="mb-1" style="font-size:9px;text-transform:uppercase;color:#6c757d;">Zona</label>
                    <select name="zona" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">[Todas]</option>
                        <?php foreach ($zonas as $z): ?>
                            <?php $idZ = (int)$z['cve_almac_int']; ?>
                            <option value="<?= $idZ ?>"
                                <?= (!empty($zonaSeleccionada) && $zonaSeleccionada === $idZ) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($idZ . ' - ' . $z['des_almac'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards resumen (con color) -->
    <div class="row g-3 mb-3">
        <!-- Ubicaciones -->
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm text-white" style="background:linear-gradient(135deg,#0F5AAD,#1b75d1);">
                <div class="card-body p-2 text-center">
                    <div class="fw-semibold text-uppercase" style="font-size:10px;letter-spacing:.05em;">Ubicaciones</div>
                    <div class="fw-bold" style="font-size:18px;"><?= number_format($totales['total']) ?></div>
                    <div class="text-white-50" style="font-size:9px;">
                        <?= htmlspecialchars($nombreAlmacenActual ?? '') ?>
                        <?= $nombreZonaActual ? ' · ' . htmlspecialchars($nombreZonaActual ?? '') : '' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activas -->
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm text-white" style="background:linear-gradient(135deg,#198754,#34c38f);">
                <div class="card-body p-2 text-center">
                    <div class="fw-semibold text-uppercase" style="font-size:10px;letter-spacing:.05em;">Activas</div>
                    <div class="fw-bold" style="font-size:18px;"><?= number_format($totales['activas']) ?></div>
                    <div class="text-white-50" style="font-size:9px;">
                        <?= $totales['total'] > 0 ? round($totales['activas'] * 100 / $totales['total'], 1) : 0 ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Picking / Reabasto -->
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm text-white" style="background:linear-gradient(135deg,#fd7e14,#ff9f43);">
                <div class="card-body p-2 text-center">
                    <div class="fw-semibold text-uppercase" style="font-size:10px;letter-spacing:.05em;">Picking</div>
                    <div class="fw-bold" style="font-size:18px;"><?= number_format($totales['picking']) ?></div>
                    <div class="text-white-50" style="font-size:9px;">
                        Reabasto: <?= number_format($totales['reabasto']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acomodo mixto (gris) -->
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm text-dark" style="background:linear-gradient(135deg,#f8f9fa,#e9ecef);">
                <div class="card-body p-2 text-center">
                    <div class="fw-semibold text-uppercase" style="font-size:10px;letter-spacing:.05em;">Acomodo mixto</div>
                    <div class="fw-bold" style="font-size:18px;"><?= number_format($totales['acomodo_mixto']) ?></div>
                    <div class="text-muted" style="font-size:9px;">
                        Ubicaciones que permiten mezcla
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0" style="font-size:11px;color:#0F5AAD;">Detalle de ubicaciones (BL)</h6>
                <div class="text-muted" style="font-size:9px;">
                    BL = <strong>CodigoCSD</strong>
                </div>
            </div>

            <div style="max-height:60vh; overflow:auto; font-size:10px;">
                <table id="tabla_ubicaciones"
                       class="table table-striped table-hover table-sm align-middle"
                       style="min-width:900px;font-size:10px;">
                    <thead class="table-light">
                    <tr>
                        <th>Almacén</th>
                        <th>Zona</th>
                        <th>ID Zona</th>
                        <th>BL</th>
                        <th>Ubicación</th>
                        <th>Pasillo</th>
                        <th>Rack</th>
                        <th>Nivel</th>
                        <th>Picking</th>
                        <th>Reabasto</th>
                        <th>Tipo</th>
                        <th>Tecnología</th>
                        <th>Activo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ubicaciones as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['clave_almacen_padre'] . ' - ' . $u['nombre_almacen_padre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['nombre_zona'] ?? '') ?></td>
                            <td><?= (int)$u['cve_almac_int'] ?></td>
                            <td><?= htmlspecialchars($u['CodigoCSD'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['Ubicacion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['cve_pasillo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['cve_rack'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['cve_nivel'] ?? '') ?></td>
                            <td><?= strtoupper($u['picking']) === 'S' ? '✔' : '' ?></td>
                            <td><?= strtoupper($u['Reabasto']) === 'S' ? '✔' : '' ?></td>
                            <td><?= htmlspecialchars($u['Tipo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['TECNOLOGIA'] ?? '') ?></td>
                            <td><?= (int)$u['Activo'] === 1 ? '✔' : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- DataTables (si no vienen ya en _menu_global) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
    $('#tabla_ubicaciones').DataTable({
        pageLength: 25,
        lengthMenu: [25, 50, 100],
        autoWidth: false,
        order: [[0,'asc'],[1,'asc'],[3,'asc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-MX.json' },
        dom: '<"row mb-1"<"col-sm-6"l><"col-sm-6"f>>t<"row mt-1"<"col-sm-5"i><"col-sm-7"p>>'
    });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
