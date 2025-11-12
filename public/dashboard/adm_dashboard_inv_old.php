<?php
// adm_dashboard_inv.php
// Dashboard de estatus de inventarios físicos y cíclicos (planeados / en ejecución / cerrados)

require_once __DIR__ . '/../../app/db.php';

if (!function_exists('db_all')) {
    function db_all($sql, $params = [])
    {
        global $pdo; // ajusta si tu conexión se llama diferente
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ---------------------------------------------------------------------
// Filtros
// ---------------------------------------------------------------------
$tipo      = isset($_GET['tipo']) ? strtoupper($_GET['tipo']) : 'A'; // A = ambos, F = físico, C = cíclico
$almacen   = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$estado    = isset($_GET['estado']) ? trim($_GET['estado']) : '';    // Planeado / En ejecución / Completado / Cerrado
$f_ini     = isset($_GET['f_ini']) ? trim($_GET['f_ini']) : '';
$f_fin     = isset($_GET['f_fin']) ? trim($_GET['f_fin']) : '';

// Si manejas almacén en sesión, lo puedes usar como default:
if ($almacen === '' && !empty($_SESSION['cve_almacen'])) {
    $almacen = $_SESSION['cve_almacen'];
}

// Catálogo de almacenes (de ambas vistas)
$almacenes = db_all("
    SELECT DISTINCT cve_almacen, almacen
    FROM (
        SELECT cve_almacen, almacen
        FROM v_dashboard_inv_fisico_ao
        UNION
        SELECT cve_almacen, des_almacen AS almacen
        FROM v_dashboard_inv_ciclico_ao
    ) x
    WHERE cve_almacen IS NOT NULL AND cve_almacen <> ''
    ORDER BY almacen
");

// ---------------------------------------------------------------------
// Datos inventario FÍSICO
// ---------------------------------------------------------------------
$fisicos = [];
if ($tipo === 'A' || $tipo === 'F') {
    $whereFis  = [];
    $paramsFis = [];

    if ($almacen !== '') {
        $whereFis[]  = "cve_almacen = ?";
        $paramsFis[] = $almacen;
    }
    if ($estado !== '') {
        $whereFis[]  = "estado_proceso = ?";
        $paramsFis[] = $estado;
    }
    if ($f_ini !== '' && $f_fin !== '') {
        $whereFis[]  = "DATE(fecha_creacion) BETWEEN ? AND ?";
        $paramsFis[] = $f_ini;
        $paramsFis[] = $f_fin;
    }

    $sqlFis = "SELECT * FROM v_dashboard_inv_fisico_ao";
    if ($whereFis) {
        $sqlFis .= " WHERE " . implode(" AND ", $whereFis);
    }
    $sqlFis .= " ORDER BY fecha_creacion DESC, folio_inventario DESC";

    $fisicos = db_all($sqlFis, $paramsFis);
}

// KPIs físicos
$fis_planeados = $fis_ejec = $fis_comp = $fis_cerrados = 0;
$fis_ub_plan = $fis_ub_cont = $fis_pzas = $fis_dif_pzas = 0;
$fis_avance_prom = 0;

if ($fisicos) {
    foreach ($fisicos as $r) {
        $estado_p = $r['estado_proceso'];
        if ($estado_p === 'Planeado')     $fis_planeados++;
        elseif ($estado_p === 'En ejecución') $fis_ejec++;
        elseif ($estado_p === 'Completado')   $fis_comp++;
        elseif ($estado_p === 'Cerrado')      $fis_cerrados++;

        $fis_ub_plan   += (int)($r['ubicaciones_planeadas'] ?? 0);
        $fis_ub_cont   += (int)($r['ubicaciones_contadas'] ?? 0);
        $fis_pzas      += (float)($r['piezas_contadas'] ?? 0);
        $fis_dif_pzas  += (float)($r['diferencia_piezas'] ?? 0);
        $fis_avance_prom += (float)($r['avance_porcentual'] ?? 0);
    }
    $fis_avance_prom = $fis_avance_prom / max(count($fisicos), 1);
}

// ---------------------------------------------------------------------
// Datos inventario CÍCLICO
// ---------------------------------------------------------------------
$ciclicos = [];
if ($tipo === 'A' || $tipo === 'C') {
    $whereC  = [];
    $paramsC = [];

    if ($almacen !== '') {
        // en la vista cíclica el campo nombre del almacén es des_almacen y la clave es cve_almacen
        $whereC[]  = "cve_almacen = ?";
        $paramsC[] = $almacen;
    }
    if ($estado !== '') {
        $whereC[]  = "estado_proceso = ?";
        $paramsC[] = $estado;
    }
    if ($f_ini !== '' && $f_fin !== '') {
        $whereC[]  = "DATE(fecha_inicio) BETWEEN ? AND ?";
        $paramsC[] = $f_ini;
        $paramsC[] = $f_fin;
    }

    $sqlC = "SELECT * FROM v_dashboard_inv_ciclico_ao";
    if ($whereC) {
        $sqlC .= " WHERE " . implode(" AND ", $whereC);
    }
    $sqlC .= " ORDER BY fecha_inicio DESC, folio_plan DESC";

    $ciclicos = db_all($sqlC, $paramsC);
}

// KPIs cíclicos
$cic_planeados = $cic_ejec = $cic_comp = $cic_cerrados = 0;
$cic_ub_plan = $cic_ub_cont = $cic_pzas = $cic_dif_pzas = 0;
$cic_avance_prom = 0;

if ($ciclicos) {
    foreach ($ciclicos as $r) {
        $estado_p = $r['estado_proceso'];
        if ($estado_p === 'Planeado')     $cic_planeados++;
        elseif ($estado_p === 'En ejecución') $cic_ejec++;
        elseif ($estado_p === 'Completado')   $cic_comp++;
        elseif ($estado_p === 'Cerrado')      $cic_cerrados++;

        $cic_ub_plan   += (int)($r['ubicaciones_planeadas'] ?? 0);
        $cic_ub_cont   += (int)($r['ubicaciones_contadas'] ?? 0);
        $cic_pzas      += (float)($r['piezas_contadas'] ?? 0);
        $cic_dif_pzas  += (float)($r['diferencia_piezas'] ?? 0);
        $cic_avance_prom += (float)($r['avance_porcentual'] ?? 0);
    }
    $cic_avance_prom = $cic_avance_prom / max(count($ciclicos), 1);
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3" style="font-size:10px;">
    <div class="row mb-2">
        <div class="col">
            <h5 class="mb-0">Estatus de Inventarios</h5>
            <small class="text-muted">Inventarios físicos y cíclicos planeados, en ejecución y cerrados</small>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="card mb-3 shadow-sm border-0">
        <div class="card-body p-2">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="A" <?= $tipo === 'A' ? 'selected' : '' ?>>Ambos</option>
                        <option value="F" <?= $tipo === 'F' ? 'selected' : '' ?>>Físico</option>
                        <option value="C" <?= $tipo === 'C' ? 'selected' : '' ?>>Cíclico</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Almacén</label>
                    <select name="almacen" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= htmlspecialchars($a['cve_almacen']) ?>"
                                <?= $almacen === $a['cve_almacen'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['cve_almacen'] . ' - ' . $a['almacen']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                        <?php
                        $estados = ['Planeado','En ejecución','Completado','Cerrado'];
                        foreach ($estados as $e): ?>
                            <option value="<?= $e ?>" <?= $estado === $e ? 'selected' : '' ?>>
                                <?= $e ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Fecha inicio</label>
                    <input type="date" name="f_ini" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($f_ini) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Fecha fin</label>
                    <input type="date" name="f_fin" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($f_fin) ?>">
                </div>
                <div class="col-6 col-md-1 text-end">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- KPIs -->
    <div class="row g-2 mb-3">
        <?php if ($tipo === 'A' || $tipo === 'F'): ?>
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-2">
                    <div class="fw-bold mb-1">Inventarios físicos</div>
                    <div class="row g-2">
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">Planeados</div>
                            <div class="h6 mb-0"><?= number_format($fis_planeados) ?></div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">En ejecución</div>
                            <div class="h6 mb-0"><?= number_format($fis_ejec) ?></div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">Completados</div>
                            <div class="h6 mb-0"><?= number_format($fis_comp) ?></div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">Cerrados</div>
                            <div class="h6 mb-0"><?= number_format($fis_cerrados) ?></div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="small text-muted">Ubic. plan.</div>
                            <div class="h6 mb-0"><?= number_format($fis_ub_plan) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Ubic. contadas</div>
                            <div class="h6 mb-0"><?= number_format($fis_ub_cont) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">% avance prom.</div>
                            <div class="h6 mb-0"><?= number_format($fis_avance_prom, 1) ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tipo === 'A' || $tipo === 'C'): ?>
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-2">
                    <div class="fw-bold mb-1">Inventarios cíclicos</div>
                    <div class="row g-2">
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">Planeados</div>
                            <div class="h6 mb-0"><?= number_format($cic_planeados) ?></div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">En ejecución</div>
                            <div class="h6 mb-0"><?= number_format($cic_ejec) ?></div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">Completados</div>
                            <div class="h6 mb-0"><?= number_format($cic_comp) ?></div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="small text-muted">Cerrados</div>
                            <div class="h6 mb-0"><?= number_format($cic_cerrados) ?></div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="small text-muted">Ubic. plan.</div>
                            <div class="h6 mb-0"><?= number_format($cic_ub_plan) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Ubic. contadas</div>
                            <div class="h6 mb-0"><?= number_format($cic_ub_cont) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">% avance prom.</div>
                            <div class="h6 mb-0"><?= number_format($cic_avance_prom, 1) ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabs tablas detalle -->
    <ul class="nav nav-tabs mb-2" role="tablist" style="font-size:10px;">
        <?php if ($tipo === 'A' || $tipo === 'F'): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tipo !== 'C' ? 'active' : '' ?>" id="tab-fis" data-bs-toggle="tab"
                    data-bs-target="#pane-fis" type="button" role="tab">
                Inventarios físicos
            </button>
        </li>
        <?php endif; ?>
        <?php if ($tipo === 'A' || $tipo === 'C'): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tipo === 'C' ? 'active' : '' ?>" id="tab-cic" data-bs-toggle="tab"
                    data-bs-target="#pane-cic" type="button" role="tab">
                Inventarios cíclicos
            </button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">
        <?php if ($tipo === 'A' || $tipo === 'F'): ?>
        <div class="tab-pane fade <?= $tipo !== 'C' ? 'show active' : '' ?>" id="pane-fis" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="tblFisDash" class="table table-sm table-striped table-bordered w-100" style="font-size:10px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Almacén</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                    <th>Status inv.</th>
                                    <th>Estado proceso</th>
                                    <th>Ubic. plan.</th>
                                    <th>Ubic. cont.</th>
                                    <th>% avance</th>
                                    <th>Pzas cont.</th>
                                    <th>Dif. pzs</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($fisicos as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_almacen'] . ' - ' . $r['almacen']) ?></td>
                                    <td><?= htmlspecialchars($r['nombre_inventario']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_creacion']) ?></td>
                                    <td><?= htmlspecialchars($r['status_inventario']) ?></td>
                                    <td><?= htmlspecialchars($r['estado_proceso']) ?></td>
                                    <td class="text-end"><?= number_format($r['ubicaciones_planeadas'] ?? 0, 0) ?></td>
                                    <td class="text-end"><?= number_format($r['ubicaciones_contadas'] ?? 0, 0) ?></td>
                                    <td class="text-end"><?= number_format($r['avance_porcentual'] ?? 0, 1) ?>%</td>
                                    <td class="text-end"><?= number_format($r['piezas_contadas'] ?? 0, 0) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_piezas'] ?? 0, 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($fisicos)): ?>
                                <tr><td colspan="11" class="text-center">Sin inventarios físicos para los filtros seleccionados</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tipo === 'A' || $tipo === 'C'): ?>
        <div class="tab-pane fade <?= $tipo === 'C' ? 'show active' : '' ?>" id="pane-cic" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="tblCicDash" class="table table-sm table-striped table-bordered w-100" style="font-size:10px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Plan</th>
                                    <th>Almacén</th>
                                    <th>F. inicio</th>
                                    <th>F. fin</th>
                                    <th>Estado proceso</th>
                                    <th>Ubic. plan.</th>
                                    <th>Ubic. cont.</th>
                                    <th>% avance</th>
                                    <th>Pzas cont.</th>
                                    <th>Dif. pzs</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ciclicos as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_almacen'] . ' - ' . $r['des_almacen']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_fin']) ?></td>
                                    <td><?= htmlspecialchars($r['estado_proceso']) ?></td>
                                    <td class="text-end"><?= number_format($r['ubicaciones_planeadas'] ?? 0, 0) ?></td>
                                    <td class="text-end"><?= number_format($r['ubicaciones_contadas'] ?? 0, 0) ?></td>
                                    <td class="text-end"><?= number_format($r['avance_porcentual'] ?? 0, 1) ?>%</td>
                                    <td class="text-end"><?= number_format($r['piezas_contadas'] ?? 0, 0) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_piezas'] ?? 0, 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ciclicos)): ?>
                                <tr><td colspan="10" class="text-center">Sin inventarios cíclicos para los filtros seleccionados</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $.fn.DataTable) {
        if (document.getElementById('tblFisDash')) {
            $('#tblFisDash').DataTable({
                pageLength: 20,
                scrollX: true,
                lengthChange: false,
                ordering: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
            });
        }
        if (document.getElementById('tblCicDash')) {
            $('#tblCicDash').DataTable({
                pageLength: 20,
                scrollX: true,
                lengthChange: false,
                ordering: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
            });
        }
    }
});
</script>
