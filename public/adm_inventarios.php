<?php
// adm_inventarios.php
// Dashboard de Administración de Inventarios (Físico y Cíclico)

require_once __DIR__ . '/../app/db.php';

// ---------------------------------------------------------
//  HANDLERS AJAX (detalle / diferencias)
// ---------------------------------------------------------
if (isset($_GET['ajax'])) {
    $ajax = $_GET['ajax'];

    // Usaremos db_all (helper estándar AssistPro). Si no existe en tu app,
    // sustitúyelo por tu método de consulta PDO equivalente.
    if (!function_exists('db_all')) {
        throw new Exception("No existe helper db_all(). Ajusta la conexión según tu app.");
    }

    if ($ajax === 'fisico_det' && !empty($_GET['folio'])) {
        $folio = (int)$_GET['folio'];
        $rows = db_all("
            SELECT
                folio_inventario,
                NConteo,
                idy_ubica,
                ntarima,
                cve_articulo,
                cve_lote,
                tipo_unidad,
                piezas_x_caja,
                cantidad_conteo,
                existencia_teorica,
                diferencia_piezas,
                ID_Proveedor,
                Cuarentena,
                cve_usuario,
                fecha
            FROM v_inv_fisico_detalle
            WHERE folio_inventario = ?
            ORDER BY NConteo, idy_ubica, tipo_unidad, cve_articulo
        ", [$folio]);
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered" style="font-size:10px;">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Conteo</th>
                        <th>Ubicación</th>
                        <th>Tarima</th>
                        <th>Artículo</th>
                        <th>Lote</th>
                        <th>Tipo</th>
                        <th>Pzs x Caja</th>
                        <th>Cant. Conteo</th>
                        <th>Teórico</th>
                        <th>Diferencia</th>
                        <th>Proveedor</th>
                        <th>Cuarentena</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                        <td><?= htmlspecialchars($r['NConteo']) ?></td>
                        <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                        <td><?= htmlspecialchars($r['ntarima']) ?></td>
                        <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                        <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                        <td><?= htmlspecialchars($r['tipo_unidad']) ?></td>
                        <td><?= htmlspecialchars($r['piezas_x_caja']) ?></td>
                        <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['existencia_teorica'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['diferencia_piezas'], 2) ?></td>
                        <td><?= htmlspecialchars($r['ID_Proveedor']) ?></td>
                        <td><?= (int)$r['Cuarentena'] === 1 ? 'S' : 'N' ?></td>
                        <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        exit;
    }

    if ($ajax === 'fisico_dif' && !empty($_GET['folio'])) {
        $folio = (int)$_GET['folio'];
        $rows = db_all("
            SELECT
                folio_inventario,
                NConteo,
                idy_ubica,
                cve_articulo,
                des_articulo,
                cve_lote,
                cantidad_conteo,
                ExistenciaTeorica,
                diferencia_piezas,
                costo_unitario,
                diferencia_valor,
                ID_Proveedor,
                Cuarentena,
                ClaveEtiqueta,
                cve_usuario,
                fecha
            FROM v_inv_fisico_diferencias_det
            WHERE folio_inventario = ?
              AND ABS(diferencia_piezas) > 0
            ORDER BY NConteo, idy_ubica, cve_articulo
        ", [$folio]);
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered" style="font-size:10px;">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Conteo</th>
                        <th>Ubicación</th>
                        <th>Artículo</th>
                        <th>Descripción</th>
                        <th>Lote</th>
                        <th>Cant. Conteo</th>
                        <th>Teórico</th>
                        <th>Dif. Pzs</th>
                        <th>Costo</th>
                        <th>Dif. Valor</th>
                        <th>Proveedor</th>
                        <th>Cuarentena</th>
                        <th>Etiqueta</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                        <td><?= htmlspecialchars($r['NConteo']) ?></td>
                        <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                        <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                        <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                        <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                        <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['ExistenciaTeorica'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['diferencia_piezas'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['costo_unitario'], 4) ?></td>
                        <td class="text-end"><?= number_format($r['diferencia_valor'], 2) ?></td>
                        <td><?= htmlspecialchars($r['ID_Proveedor']) ?></td>
                        <td><?= (int)$r['Cuarentena'] === 1 ? 'S' : 'N' ?></td>
                        <td><?= htmlspecialchars($r['ClaveEtiqueta']) ?></td>
                        <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        exit;
    }

    if ($ajax === 'ciclico_det' && !empty($_GET['plan'])) {
        $plan = (int)$_GET['plan'];
        $rows = db_all("
            SELECT
                folio_plan,
                NConteo,
                idy_ubica,
                cve_articulo,
                cve_lote,
                tipo_unidad,
                piezas_x_caja,
                cantidad_conteo,
                existencia_teorica,
                diferencia_piezas,
                Id_Proveedor,
                Cuarentena,
                cve_usuario,
                fecha
            FROM v_inv_ciclico_detalle
            WHERE folio_plan = ?
            ORDER BY NConteo, idy_ubica, tipo_unidad, cve_articulo
        ", [$plan]);
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered" style="font-size:10px;">
                <thead class="table-light">
                    <tr>
                        <th>Plan</th>
                        <th>Conteo</th>
                        <th>Ubicación</th>
                        <th>Artículo</th>
                        <th>Lote</th>
                        <th>Tipo</th>
                        <th>Pzs x Caja</th>
                        <th>Cant. Conteo</th>
                        <th>Teórico</th>
                        <th>Dif. Pzs</th>
                        <th>Proveedor</th>
                        <th>Cuarentena</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                        <td><?= htmlspecialchars($r['NConteo']) ?></td>
                        <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                        <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                        <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                        <td><?= htmlspecialchars($r['tipo_unidad']) ?></td>
                        <td><?= htmlspecialchars($r['piezas_x_caja']) ?></td>
                        <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['existencia_teorica'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['diferencia_piezas'], 2) ?></td>
                        <td><?= htmlspecialchars($r['Id_Proveedor']) ?></td>
                        <td><?= (int)$r['Cuarentena'] === 1 ? 'S' : 'N' ?></td>
                        <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        exit;
    }

    if ($ajax === 'ciclico_dif' && !empty($_GET['plan'])) {
        $plan = (int)$_GET['plan'];
        $rows = db_all("
            SELECT
                folio_plan,
                NConteo,
                idy_ubica,
                cve_articulo,
                des_articulo,
                cve_lote,
                cantidad_conteo,
                ExistenciaTeorica,
                diferencia_piezas,
                costo_unitario,
                diferencia_valor,
                Id_Proveedor,
                Cuarentena,
                ClaveEtiqueta,
                cve_usuario,
                fecha
            FROM v_inv_ciclico_diferencias_det
            WHERE folio_plan = ?
              AND ABS(diferencia_piezas) > 0
            ORDER BY NConteo, idy_ubica, cve_articulo
        ", [$plan]);
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered" style="font-size:10px;">
                <thead class="table-light">
                    <tr>
                        <th>Plan</th>
                        <th>Conteo</th>
                        <th>Ubicación</th>
                        <th>Artículo</th>
                        <th>Descripción</th>
                        <th>Lote</th>
                        <th>Cant. Conteo</th>
                        <th>Teórico</th>
                        <th>Dif. Pzs</th>
                        <th>Costo</th>
                        <th>Dif. Valor</th>
                        <th>Proveedor</th>
                        <th>Cuarentena</th>
                        <th>Etiqueta</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                        <td><?= htmlspecialchars($r['NConteo']) ?></td>
                        <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                        <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                        <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                        <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                        <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['ExistenciaTeorica'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['diferencia_piezas'], 2) ?></td>
                        <td class="text-end"><?= number_format($r['costo_unitario'], 4) ?></td>
                        <td class="text-end"><?= number_format($r['diferencia_valor'], 2) ?></td>
                        <td><?= htmlspecialchars($r['Id_Proveedor']) ?></td>
                        <td><?= (int)$r['Cuarentena'] === 1 ? 'S' : 'N' ?></td>
                        <td><?= htmlspecialchars($r['ClaveEtiqueta']) ?></td>
                        <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        exit;
    }

    exit;
}

// ---------------------------------------------------------
//  VISTA PRINCIPAL
// ---------------------------------------------------------
require_once __DIR__ . '/../bi/_menu_global.php';

// Filtros
$tipo       = isset($_GET['tipo']) ? $_GET['tipo'] : 'F';
$almacen    = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$folio      = isset($_GET['folio']) ? trim($_GET['folio']) : '';
$f_ini      = isset($_GET['f_ini']) ? trim($_GET['f_ini']) : '';
$f_fin      = isset($_GET['f_fin']) ? trim($_GET['f_fin']) : '';

// Catálogo de almacenes desde inventarios físicos y cíclicos
$almacenes = db_all("
    SELECT DISTINCT almacen FROM v_inv_fisico_resumen
    UNION
    SELECT DISTINCT almacen FROM v_inv_ciclico_resumen
    ORDER BY almacen
");

// Datos resumen físico
$whereFis = [];
$paramsFis = [];

if ($almacen !== '') {
    $whereFis[] = "almacen = ?";
    $paramsFis[] = $almacen;
}
if ($folio !== '' && $tipo === 'F') {
    $whereFis[] = "folio_inventario = ?";
    $paramsFis[] = $folio;
}
if ($f_ini !== '' && $f_fin !== '') {
    $whereFis[] = "fecha_final BETWEEN ? AND ?";
    $paramsFis[] = $f_ini . " 00:00:00";
    $paramsFis[] = $f_fin . " 23:59:59";
}

$sqlFis = "SELECT * FROM v_inv_fisico_resumen";
if ($whereFis) {
    $sqlFis .= " WHERE " . implode(" AND ", $whereFis);
}
$sqlFis .= " ORDER BY fecha_final DESC, folio_inventario DESC";
$fisicos = db_all($sqlFis, $paramsFis);

// Datos resumen cíclico
$whereCic = [];
$paramsCic = [];

if ($almacen !== '') {
    $whereCic[] = "almacen = ?";
    $paramsCic[] = $almacen;
}
if ($folio !== '' && $tipo === 'C') {
    $whereCic[] = "folio_plan = ?";
    $paramsCic[] = $folio;
}
if ($f_ini !== '' && $f_fin !== '') {
    $whereCic[] = "fecha_fin BETWEEN ? AND ?";
    $paramsCic[] = $f_ini . " 00:00:00";
    $paramsCic[] = $f_fin . " 23:59:59";
}

$sqlCic = "SELECT * FROM v_inv_ciclico_resumen";
if ($whereCic) {
    $sqlCic .= " WHERE " . implode(" AND ", $whereCic);
}
$sqlCic .= " ORDER BY fecha_fin DESC, folio_plan DESC";
$ciclicos = db_all($sqlCic, $paramsCic);

// Métricas para cards (físico)
$total_inventarios = count($fisicos);
$valor_total = 0;
$diferencia_total_valor = 0;
foreach ($fisicos as $r) {
    $valor_total += (float)$r['valor_conteo'];
    $diferencia_total_valor += (float)$r['diferencia_valor'];
}

// Métricas para cards (cíclico)
$total_planes = count($ciclicos);
$diferencia_total_ciclico = 0;
foreach ($ciclicos as $r) {
    $diferencia_total_ciclico += (float)$r['diferencia_valor'];
}
?>
<div class="container-fluid py-3" style="font-size:10px;">
    <div class="row mb-2">
        <div class="col">
            <h5 class="mb-0">Administración de Inventarios</h5>
            <small class="text-muted">Resumen de inventarios físicos y cíclicos</small>
        </div>
    </div>

    <!-- Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="fw-bold">Inventarios físicos</div>
                    <div class="h5 mb-0"><?= number_format($total_inventarios) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="fw-bold">Valor inventariado (F)</div>
                    <div class="h6 mb-0">$<?= number_format($valor_total, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="fw-bold">Dif. valor (F)</div>
                    <div class="h6 mb-0 text-danger">$<?= number_format($diferencia_total_valor, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="fw-bold">Planes cíclicos</div>
                    <div class="h5 mb-0"><?= number_format($total_planes) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="card mb-3 shadow-sm border-0">
        <div class="card-body p-2">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="F" <?= $tipo === 'F' ? 'selected' : '' ?>>Físico</option>
                        <option value="C" <?= $tipo === 'C' ? 'selected' : '' ?>>Cíclico</option>
                        <option value="A" <?= $tipo === 'A' ? 'selected' : '' ?>>Ambos</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Almacén</label>
                    <select name="almacen" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= htmlspecialchars($a['almacen']) ?>"
                                <?= $almacen === $a['almacen'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['almacen']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Folio</label>
                    <input type="text" name="folio" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($folio) ?>" placeholder="Folio inv / plan">
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

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-2" role="tablist" style="font-size:10px;">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tipo !== 'C' ? 'active' : '' ?>" id="tab-fisico" data-bs-toggle="tab"
                    data-bs-target="#pane-fisico" type="button" role="tab">
                Inventarios físicos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tipo === 'C' ? 'active' : '' ?>" id="tab-ciclico" data-bs-toggle="tab"
                    data-bs-target="#pane-ciclico" type="button" role="tab">
                Inventarios cíclicos
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB FÍSICO -->
        <div class="tab-pane fade <?= $tipo !== 'C' ? 'show active' : '' ?>" id="pane-fisico" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="tblFisico" class="table table-sm table-striped table-bordered w-100" style="font-size:10px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Almacén</th>
                                    <th>Zona</th>
                                    <th>F. Inicio</th>
                                    <th>F. Fin</th>
                                    <th>Usuario</th>
                                    <th>Status</th>
                                    <th>Ubic.</th>
                                    <th>Tarimas</th>
                                    <th>Cajas</th>
                                    <th>Piezas</th>
                                    <th>Dif. Pzs</th>
                                    <th>Valor</th>
                                    <th>Dif. Valor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($fisicos as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                                    <td><?= htmlspecialchars($r['almacen']) ?></td>
                                    <td><?= htmlspecialchars($r['zona']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_final']) ?></td>
                                    <td><?= htmlspecialchars($r['usuario']) ?></td>
                                    <td><?= htmlspecialchars($r['status']) ?></td>
                                    <td class="text-end"><?= number_format($r['ubicaciones'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['tarimas'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['cajas_contadas'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['piezas_contadas'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_piezas'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['valor_conteo'], 2) ?></td>
                                    <td class="text-end <?= $r['diferencia_valor'] != 0 ? 'text-danger' : '' ?>">
                                        <?= number_format($r['diferencia_valor'], 2) ?>
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-xs btn-outline-primary btn-det-fisico"
                                                data-folio="<?= htmlspecialchars($r['folio_inventario']) ?>">
                                            Detalle
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs btn-outline-danger btn-dif-fisico"
                                                data-folio="<?= htmlspecialchars($r['folio_inventario']) ?>">
                                            Diferencias
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CÍCLICO -->
        <div class="tab-pane fade <?= $tipo === 'C' ? 'show active' : '' ?>" id="pane-ciclico" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="tblCiclico" class="table table-sm table-striped table-bordered w-100" style="font-size:10px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Plan</th>
                                    <th>Almacén</th>
                                    <th>Zona</th>
                                    <th>F. Inicio</th>
                                    <th>F. Fin</th>
                                    <th>Ubic.</th>
                                    <th>Cajas</th>
                                    <th>Piezas</th>
                                    <th>Dif. Pzs</th>
                                    <th>Dif. Valor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ciclicos as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                                    <td><?= htmlspecialchars($r['almacen'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['zona'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_fin']) ?></td>
                                    <td class="text-end"><?= number_format($r['ubicaciones'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['cajas_contadas'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['piezas_contadas'], 0) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_piezas'], 0) ?></td>
                                    <td class="text-end <?= $r['diferencia_valor'] != 0 ? 'text-danger' : '' ?>">
                                        <?= number_format($r['diferencia_valor'], 2) ?>
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-xs btn-outline-primary btn-det-ciclico"
                                                data-plan="<?= htmlspecialchars($r['folio_plan']) ?>">
                                            Detalle
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs btn-outline-danger btn-dif-ciclico"
                                                data-plan="<?= htmlspecialchars($r['folio_plan']) ?>">
                                            Diferencias
                                        </button>
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

    <!-- Modal genérico -->
    <div class="modal fade" id="mdlDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="mdlTitulo">Detalle</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-2" id="mdlBody" style="font-size:10px;">
                    Cargando...
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
// DataTables inicialización básica
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $.fn.DataTable) {
        $('#tblFisico').DataTable({
            pageLength: 25,
            scrollX: true,
            lengthChange: false,
            ordering: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
        $('#tblCiclico').DataTable({
            pageLength: 25,
            scrollX: true,
            lengthChange: false,
            ordering: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    }

    var mdl = document.getElementById('mdlDetalle');
    var bsModal = mdl ? new bootstrap.Modal(mdl) : null;
    var mdlBody = document.getElementById('mdlBody');
    var mdlTitulo = document.getElementById('mdlTitulo');

    function cargarModal(url, titulo) {
        if (!mdlBody || !bsModal) return;
        mdlBody.innerHTML = 'Cargando...';
        mdlTitulo.textContent = titulo || 'Detalle';

        fetch(url)
            .then(r => r.text())
            .then(html => {
                mdlBody.innerHTML = html;
                bsModal.show();
            })
            .catch(err => {
                mdlBody.innerHTML = '<div class="text-danger">Error cargando información</div>';
                console.error(err);
                bsModal.show();
            });
    }

    // Botones físicos
    document.querySelectorAll('.btn-det-fisico').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var folio = this.getAttribute('data-folio');
            var url = 'adm_inventarios.php?ajax=fisico_det&folio=' + encodeURIComponent(folio);
            cargarModal(url, 'Detalle inventario físico ' + folio);
        });
    });
    document.querySelectorAll('.btn-dif-fisico').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var folio = this.getAttribute('data-folio');
            var url = 'adm_inventarios.php?ajax=fisico_dif&folio=' + encodeURIComponent(folio);
            cargarModal(url, 'Diferencias inventario físico ' + folio);
        });
    });

    // Botones cíclicos
    document.querySelectorAll('.btn-det-ciclico').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var plan = this.getAttribute('data-plan');
            var url = 'adm_inventarios.php?ajax=ciclico_det&plan=' + encodeURIComponent(plan);
            cargarModal(url, 'Detalle inventario cíclico ' + plan);
        });
    });
    document.querySelectorAll('.btn-dif-ciclico').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var plan = this.getAttribute('data-plan');
            var url = 'adm_inventarios.php?ajax=ciclico_dif&plan=' + encodeURIComponent(plan);
            cargarModal(url, 'Diferencias inventario cíclico ' + plan);
        });
    });
});
</script>
