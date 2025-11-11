 <?php
// license_plate.php

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

/* =======================
   LECTURA DE FILTROS
   ======================= */

$lp_filtro   = isset($_GET['lp'])       ? trim($_GET['lp'])       : '';
$almacen_f   = isset($_GET['almacen'])  ? trim($_GET['almacen'])  : '';
$tipogen_f   = isset($_GET['tipogen'])  ? trim($_GET['tipogen'])  : ''; // G = Genérico, N = No genérico
$tipo_f      = isset($_GET['tipo'])     ? trim($_GET['tipo'])     : '';
$statuslp_f  = isset($_GET['statuslp']) ? trim($_GET['statuslp']) : ''; // 1 = Permanente, 0 = Temporal
$activo_f    = isset($_GET['activo'])   ? trim($_GET['activo'])   : ''; // 1 = Activo, 0 = Inactivo

$where  = "COALESCE(CveLP, '') <> ''";
$params = [];

// Filtro LP
if ($lp_filtro !== '') {
    $where .= " AND CveLP LIKE :lp";
    $params['lp'] = "%{$lp_filtro}%";
}

// Filtro Almacén
if ($almacen_f !== '') {
    $where .= " AND cve_almac = :almacen";
    $params['almacen'] = $almacen_f;
}

// Filtro TipoGen (Genérico / No)
if ($tipogen_f === 'G') {
    $where .= " AND TipoGen = 1";
} elseif ($tipogen_f === 'N') {
    $where .= " AND TipoGen = 0";
}

// Filtro Tipo (Pallet / Contenedor / etc.)
if ($tipo_f !== '') {
    $where .= " AND tipo = :tipo_f";
    $params['tipo_f'] = $tipo_f;
}

// Filtro License Plate Status (Permanente / Temporal)
if ($statuslp_f !== '') {
    $where .= " AND Permanente = :statuslp";
    $params['statuslp'] = $statuslp_f;
}

// Filtro Activo / Inactivo
if ($activo_f !== '') {
    $where .= " AND Activo = :activo_f";
    $params['activo_f'] = $activo_f;
}

/* =======================
   EXPORTAR A EXCEL (filtrado, sin límite)
   ======================= */

if (isset($_GET['export']) && $_GET['export'] === 'xls') {

    $sql_export = "
        SELECT
            IDContenedor,
            cve_almac,
            Clave_Contenedor,
            descripcion,
            Permanente,
            Pedido,
            sufijo,
            tipo,
            Activo,
            alto,
            ancho,
            fondo,
            peso,
            pesomax,
            capavol,
            Costo,
            CveLP,
            TipoGen
        FROM c_charolas
        WHERE {$where}
        ORDER BY cve_almac, CveLP, Clave_Contenedor
    ";

    if (function_exists('db_all')) {
        $rows_export = db_all($sql_export, $params);
    } else {
        if (!isset($pdo)) {
            die('No se encontró conexión a base de datos. Verifica app/db.php.');
        }
        $stmt = $pdo->prepare($sql_export);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        $rows_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="license_plate_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'IDContenedor','cve_almac','Clave_Contenedor','descripcion',
        'Permanente','Pedido','sufijo','tipo','Activo',
        'alto','ancho','fondo','peso','pesomax','capavol','Costo',
        'CveLP','TipoGen'
    ]);

    foreach ($rows_export as $r) {
        fputcsv($output, [
            $r['IDContenedor'],
            $r['cve_almac'],
            $r['Clave_Contenedor'],
            $r['descripcion'],
            $r['Permanente'],
            $r['Pedido'],
            $r['sufijo'],
            $r['tipo'],
            $r['Activo'],
            $r['alto'],
            $r['ancho'],
            $r['fondo'],
            $r['peso'],
            $r['pesomax'],
            $r['capavol'],
            $r['Costo'],
            $r['CveLP'],
            $r['TipoGen'],
        ]);
    }

    fclose($output);
    exit;
}

/* =======================
   KPIs FILTRADOS (mismo WHERE)
   ======================= */

$sql_kpi = "
    SELECT
        COUNT(*) AS total_lp,
        SUM(CASE WHEN Permanente = 1 THEN 1 ELSE 0 END) AS total_perm,
        SUM(CASE WHEN Permanente = 1 THEN 0 ELSE 1 END) AS total_temp,
        SUM(CASE WHEN Activo = 1 THEN 1 ELSE 0 END)      AS total_activo,
        SUM(CASE WHEN Activo = 1 THEN 0 ELSE 1 END)      AS total_inactivo,
        COUNT(DISTINCT cve_almac)                        AS total_almacenes
    FROM c_charolas
    WHERE {$where}
";

if (function_exists('db_one')) {
    $kpi = db_one($sql_kpi, $params);
} else {
    $stmtK = $pdo->prepare($sql_kpi);
    foreach ($params as $k => $v) {
        $stmtK->bindValue(':' . $k, $v);
    }
    $stmtK->execute();
    $kpi = $stmtK->fetch(PDO::FETCH_ASSOC);
}

$total_lp_filtrado        = (int)($kpi['total_lp'] ?? 0);
$total_perm_filtrado      = (int)($kpi['total_perm'] ?? 0);
$total_temp_filtrado      = (int)($kpi['total_temp'] ?? 0);
$total_activo_filtrado    = (int)($kpi['total_activo'] ?? 0);
$total_inactivo_filtrado  = (int)($kpi['total_inactivo'] ?? 0);
$total_almacenes_filtrado = (int)($kpi['total_almacenes'] ?? 0);

/* =======================
   LISTAS PARA COMBOS
   ======================= */

// Almacenes (sin filtrar, catálogo)
$sql_alm = "
    SELECT DISTINCT cve_almac
    FROM c_charolas
    WHERE COALESCE(CveLP, '') <> ''
    ORDER BY cve_almac
";
if (function_exists('db_all')) {
    $almacenes_list = db_all($sql_alm);
} else {
    $almacenes_list = $pdo->query($sql_alm)->fetchAll(PDO::FETCH_ASSOC);
}

// Tipos (Pallet / Contenedor / etc.)
$sql_tipo = "
    SELECT DISTINCT tipo
    FROM c_charolas
    WHERE COALESCE(CveLP, '') <> ''
    ORDER BY tipo
";
if (function_exists('db_all')) {
    $tipos_list = db_all($sql_tipo);
} else {
    $tipos_list = $pdo->query($sql_tipo)->fetchAll(PDO::FETCH_ASSOC);
}

/* =======================
   CONSULTA PRINCIPAL (filtrada, máx 500)
   ======================= */

$sql = "
    SELECT
        IDContenedor,
        cve_almac,
        Clave_Contenedor,
        descripcion,
        Permanente,
        Pedido,
        sufijo,
        tipo,
        Activo,
        alto,
        ancho,
        fondo,
        peso,
        pesomax,
        capavol,
        Costo,
        CveLP,
        TipoGen
    FROM c_charolas
    WHERE {$where}
    ORDER BY cve_almac, CveLP, Clave_Contenedor
    LIMIT 500
";

if (function_exists('db_all')) {
    $rows = db_all($sql, $params);
} else {
    if (!isset($pdo)) {
        die('No se encontró conexión a base de datos. Verifica app/db.php.');
    }
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_en_pantalla = count($rows);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>License Plate - Charolas</title>

    <!-- Bootstrap / DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-size: 10px;
        }
        .card-header {
            background:#0F5AAD;
            color:#fff;
            font-weight:600;
        }
        .kpi-card {
            border-radius: .75rem;
            box-shadow: 0 4px 10px rgba(0,0,0,.08);
        }
        .kpi-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color:#a7b3d6;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: 700;
        }
        .kpi-sub {
            font-size: 10px;
            color:#e8eefb;
        }
        .kpi-total { background: linear-gradient(135deg,#0F5AAD,#00A3E0); color:#fff; }
        .kpi-perm  { background:#0b8955; color:#fff; }
        .kpi-temp  { background:#6c757d; color:#fff; }
        .kpi-alm   { background:#111827; color:#fff; }
        .badge-perm      { background:#198754; }
        .badge-temp      { background:#6c757d; }
        .badge-activo    { background:#198754; }
        .badge-inactivo  { background:#dc3545; }
        .table thead th  { white-space: nowrap; }
        .sel-info        { font-size: 10px; }
    </style>
</head>
<body>
<div class="container-fluid mt-3">

    <!-- CARDS RESUMEN (totales filtrados) -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card kpi-card kpi-total">
                <div class="card-body">
                    <div class="kpi-title">License Plates filtrados</div>
                    <div class="kpi-value"><?php echo number_format($total_lp_filtrado); ?></div>
                    <div class="kpi-sub">Coinciden con los filtros actuales</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card kpi-card kpi-perm">
                <div class="card-body">
                    <div class="kpi-title">Permanentes</div>
                    <div class="kpi-value"><?php echo number_format($total_perm_filtrado); ?></div>
                    <div class="kpi-sub">Dentro del conjunto filtrado</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card kpi-card kpi-temp">
                <div class="card-body">
                    <div class="kpi-title">Activos</div>
                    <div class="kpi-value"><?php echo number_format($total_activo_filtrado); ?></div>
                    <div class="kpi-sub">LP activos filtrados</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card kpi-card kpi-alm">
                <div class="card-body">
                    <div class="kpi-title">Almacenes filtrados</div>
                    <div class="kpi-value"><?php echo number_format($total_almacenes_filtrado); ?></div>
                    <div class="kpi-sub">Almacenes con LP coincidentes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS + EXPORT -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get">
                <!-- LP -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">License Plate</label>
                    <input type="text"
                           name="lp"
                           class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($lp_filtro); ?>"
                           placeholder="LP parcial">
                </div>

                <!-- Almacén -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">Almacén</label>
                    <select name="almacen" class="form-select form-select-sm">
                        <option value="">Todos los almacenes</option>
                        <?php foreach ($almacenes_list as $a): ?>
                            <?php $val = $a['cve_almac']; ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"
                                <?php echo ($almacen_f !== '' && $almacen_f == $val) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($val); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- TipoGen -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">Tipo (Genérico/No)</label>
                    <select name="tipogen" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="G" <?php echo $tipogen_f === 'G' ? 'selected' : ''; ?>>Genérico</option>
                        <option value="N" <?php echo $tipogen_f === 'N' ? 'selected' : ''; ?>>No genérico</option>
                    </select>
                </div>

                <!-- Tipo contenedor -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">Tipo (Pallet/Contenedor)</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tipos_list as $t): ?>
                            <?php $val = $t['tipo']; ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"
                                <?php echo ($tipo_f !== '' && $tipo_f == $val) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($val); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- License Plate Status (Permanente/Temporal) -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">License Plate Status</label>
                    <select name="statuslp" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $statuslp_f === '1' ? 'selected' : ''; ?>>Permanente</option>
                        <option value="0" <?php echo $statuslp_f === '0' ? 'selected' : ''; ?>>Temporal</option>
                    </select>
                </div>

                <!-- Activo / Inactivo -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">Activo / Inactivo</label>
                    <select name="activo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $activo_f === '1' ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo $activo_f === '0' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="col-12 col-sm-4 col-md-3 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        Buscar
                    </button>
                    <a href="license_plate.php" class="btn btn-outline-secondary btn-sm">
                        Limpiar
                    </a>
                    <button type="submit" name="export" value="xls" class="btn btn-success btn-sm mt-1 mt-sm-0">
                        Exportar a Excel
                    </button>
                </div>

                <div class="col-12 col-md-3 text-md-end mt-2">
                    <small class="text-muted">
                        En pantalla: <strong><?php echo $total_en_pantalla; ?></strong> de
                        <strong><?php echo $total_lp_filtrado; ?></strong> LP filtrados
                        (máx. 500 por consulta).
                    </small>
                </div>
            </form>
        </div>
    </div>

    <!-- GRILLA PRINCIPAL -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>License Plate (c_charolas)</span>
            <span class="small sel-info">
                <input type="checkbox" id="chk_all" />
                <label for="chk_all" class="ms-1 mb-0">Seleccionar todos</label>
                &nbsp;|&nbsp; Seleccionados: <span id="sel_count">0</span>
            </span>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table id="tblLicensePlate" class="table table-striped table-bordered table-sm align-middle">
                    <thead>
                    <tr>
                        <th style="width:20px;"></th>
                        <th>LP</th>
                        <th>Clave Contenedor</th>
                        <th>Descripción</th>
                        <th>Almacén</th>
                        <th>Tipo</th>
                        <th>Permanente</th>
                        <th>Activo</th>
                        <th>Dimensiones (Al x An x Fo)</th>
                        <th>Peso (kg)</th>
                        <th>Peso Máx (kg)</th>
                        <th>Cap. Vol.</th>
                        <th>Pedido</th>
                        <th>Sufijo</th>
                        <th>Costo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox"
                                       class="chk_row"
                                       value="<?php echo htmlspecialchars($row['CveLP']); ?>">
                            </td>
                            <td>
                                <a href="license_plate_detalle.php?lp=<?php echo urlencode($row['CveLP']); ?>">
                                    <?php echo htmlspecialchars($row['CveLP']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($row['Clave_Contenedor']); ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                            <td><?php echo (int)$row['cve_almac']; ?></td>
                            <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                            <td class="text-center">
                                <?php if ((int)$row['Permanente'] === 1): ?>
                                    <span class="badge badge-perm">Permanente</span>
                                <?php else: ?>
                                    <span class="badge badge-temp">Temporal</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$row['Activo'] === 1): ?>
                                    <span class="badge badge-activo">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                echo (int)$row['alto'] . ' x ' .
                                     (int)$row['ancho'] . ' x ' .
                                     (int)$row['fondo'];
                                ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['peso'], 3); ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['pesomax'], 3); ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['capavol'], 3); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['Pedido']); ?></td>
                            <td><?php echo htmlspecialchars($row['sufijo']); ?></td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['Costo'], 3); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="mt-2 text-muted">
                Mostrando charolas con License Plate asignado (campo <strong>CveLP</strong> de <strong>c_charolas</strong>).
            </p>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(function () {
        const table = $('#tblLicensePlate').DataTable({
            pageLength: 25,
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            scrollX: true,
            scrollY: '420px',
            scrollCollapse: true,
            order: [[1, 'asc']], // por LP
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            },
            dom: 'lrtip'
        });

        function updateSelCount() {
            $('#sel_count').text($('.chk_row:checked').length);
        }

        $('#chk_all').on('change', function () {
            const checked = this.checked;
            $('.chk_row').prop('checked', checked);
            updateSelCount();
        });

        $(document).on('change', '.chk_row', function () {
            const totalRows    = $('.chk_row').length;
            const totalChecked = $('.chk_row:checked').length;
            $('#chk_all').prop('checked', totalRows > 0 && totalRows === totalChecked);
            updateSelCount();
        });

        updateSelCount();
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>

