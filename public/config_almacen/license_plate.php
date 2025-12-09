<?php
// public/config_almacen/license_plate.php

require_once __DIR__ . '/../../app/db.php';

/* ===========================================================
   LECTURA DE FILTROS
   =========================================================== */

$lp_filtro   = isset($_GET['lp'])       ? trim($_GET['lp'])       : '';
$almacen_f   = isset($_GET['almacen'])  ? trim($_GET['almacen'])  : '';
$zona_f      = isset($_GET['zona'])     ? trim($_GET['zona'])     : '';   // NUEVO: filtro de zona
$tipogen_f   = isset($_GET['tipogen'])  ? trim($_GET['tipogen'])  : '';   // G = Genérico, N = No genérico
$tipo_f      = isset($_GET['tipo'])     ? trim($_GET['tipo'])     : '';
$statuslp_f  = isset($_GET['statuslp']) ? trim($_GET['statuslp']) : '';   // 1 = Permanente, 0 = Temporal
$activo_f    = isset($_GET['activo'])   ? trim($_GET['activo'])   : '';   // 1 = Activo (exist>0), 0 = Inactivo

// Por defecto solo activos por existencia
if ($activo_f === '') {
    $activo_f = '1';
}

/* ===========================================================
   ALMACÉN POR DEFECTO (PRIMER ALMACÉN CON LP ACTIVO SI NO SE FILTRA)
   =========================================================== */

$sql_almacen_def = "
    SELECT 
        ex.cve_almac AS id_almacen,
        ap.clave      AS clave,
        ap.nombre     AS nombre
    FROM c_charolas ch
    LEFT JOIN (
        SELECT 
            ntarima,
            cve_almac,
            SUM(existencia) AS existencia_total
        FROM ts_existenciatarima
        GROUP BY ntarima, cve_almac
    ) ex ON ex.ntarima = ch.IDContenedor
    LEFT JOIN c_almacenp ap ON ap.id = ex.cve_almac
    WHERE COALESCE(ex.existencia_total,0) > 0
      AND ap.id IS NOT NULL
    ORDER BY ap.clave
    LIMIT 1
";

$almacen_def_row = function_exists('db_row') ? db_row($sql_almacen_def) : null;

// Si no viene filtro de almacén se usa el de defecto
if ($almacen_f === '' && $almacen_def_row) {
    $almacen_f          = $almacen_def_row['id_almacen'];
    $_GET['almacen']    = $almacen_f; // para que el combo salga seleccionado
}

/* ===========================================================
   ALMACENES DISPONIBLES (c_almacenp)
   =========================================================== */

$sql_almacenes = "
    SELECT DISTINCT
        ap.id       AS id_almacen,
        ap.clave,
        ap.nombre
    FROM c_almacenp ap
    LEFT JOIN ts_existenciatarima e ON e.cve_almac = ap.id
    ORDER BY ap.clave
";
$almacenes_list = function_exists('db_all') ? db_all($sql_almacenes) : [];

/* ===========================================================
   LISTADO DE TIPOS (Pallet/Contenedor/etc.)
   =========================================================== */

$sql_tipos = "
    SELECT DISTINCT ch.tipo
    FROM c_charolas ch
    WHERE COALESCE(ch.CveLP, '') <> ''
    ORDER BY ch.tipo
";
$tipos_list = function_exists('db_all') ? db_all($sql_tipos) : [];

/* ===========================================================
   WHERE BASE y PARÁMETROS
   =========================================================== */

$whereParts = ["COALESCE(ch.CveLP, '') <> ''"];
$params     = [];

// Filtro LP (CveLP)
if ($lp_filtro !== '') {
    $whereParts[] = "ch.CveLP LIKE :lp";
    $params['lp'] = "%{$lp_filtro}%";
}

// Filtro Almacén (usa c_almacenp.id via ex.cve_almac)
if ($almacen_f !== '') {
    $whereParts[] = "ex.cve_almac = :almacen";
    $params['almacen'] = $almacen_f;
}

// NUEVO: filtro zona por nombre exacto
if ($zona_f !== '') {
    $whereParts[] = "ca.des_almac = :zona";
    $params['zona'] = $zona_f;
}

// Filtro TipoGen (Genérico / No)
if ($tipogen_f === 'G') {
    $whereParts[] = "ch.TipoGen = 1";
} elseif ($tipogen_f === 'N') {
    $whereParts[] = "ch.TipoGen = 0";
}

// Filtro Tipo (Pallet / Contenedor / etc.)
if ($tipo_f !== '') {
    $whereParts[] = "ch.tipo = :tipo_f";
    $params['tipo_f'] = $tipo_f;
}

// Filtro License Plate Status (Permanente / Temporal)
if ($statuslp_f !== '') {
    $whereParts[] = "ch.Permanente = :statuslp";
    $params['statuslp'] = $statuslp_f;
}

// Filtro Activo / Inactivo según existencia
if ($activo_f === '1') {
    $whereParts[] = "COALESCE(ex.existencia_total,0) > 0";
} elseif ($activo_f === '0') {
    $whereParts[] = "COALESCE(ex.existencia_total,0) <= 0";
}

$whereBase = implode(' AND ', $whereParts);

/* ===========================================================
   SUBCONSULTA DE EXISTENCIAS (OPTIMIZADA POR ALMACÉN)
   =========================================================== */

$subWhereExist = '';
$params_base   = [];

if ($almacen_f !== '') {
    $subWhereExist = "WHERE cve_almac = :almacen_exist";
    $params_base['almacen_exist'] = $almacen_f;
}

$baseFrom = "
FROM c_charolas ch
LEFT JOIN (
    SELECT 
        ntarima,
        cve_almac,
        MAX(CASE WHEN existencia > 0 THEN idy_ubica END) AS idy_ubica,
        SUM(existencia) AS existencia_total
    FROM ts_existenciatarima
    {$subWhereExist}
    GROUP BY ntarima, cve_almac
) ex ON ex.ntarima = ch.IDContenedor
LEFT JOIN c_ubicacion u 
       ON u.idy_ubica = ex.idy_ubica
LEFT JOIN c_almacen ca 
       ON ca.cve_almac = u.cve_almac
LEFT JOIN c_almacenp ap 
       ON ap.id = ex.cve_almac
";

/* ===========================================================
   LISTA DE ZONAS (select) – SOLO DEPENDE DE ALMACÉN
   =========================================================== */

$zonas_list = [];
if ($almacen_f !== '') {
    $sql_zonas = "
        SELECT DISTINCT ca.des_almac AS zona
        FROM ts_existenciatarima t
        JOIN c_ubicacion u ON u.idy_ubica = t.idy_ubica
        JOIN c_almacen ca ON ca.cve_almac = u.cve_almac
        WHERE t.cve_almac = :alm_z
        ORDER BY ca.des_almac
    ";
    $zonas_list = db_all($sql_zonas, ['alm_z' => $almacen_f]);
}

/* ===========================================================
   EXPORTAR A CSV (ANTES DEL HTML)
   =========================================================== */

if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $sql_export = "
        SELECT
            ch.IDContenedor,
            ex.cve_almac          AS id_almacen,
            ap.clave              AS clave_almacenp,
            ap.nombre             AS nombre_almacen,
            ca.des_almac          AS zona_almacenaje,
            u.CodigoCSD           AS CodigoCSD,
            ch.Clave_Contenedor,
            ch.descripcion,
            ch.Permanente,
            ch.tipo,
            CASE WHEN COALESCE(ex.existencia_total,0) > 0 THEN 1 ELSE 0 END AS ActivoExistencia,
            ch.CveLP,
            ch.TipoGen
        {$baseFrom}
        WHERE {$whereBase}
        ORDER BY ex.cve_almac, ch.CveLP, ch.Clave_Contenedor
    ";

    $params_export = array_merge($params, $params_base);
    $rows_export   = function_exists('db_all') ? db_all($sql_export, $params_export) : [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="license_plate_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');

    // *** SIN IDs: solo códigos y descripciones ***
    fputcsv($output, [
        'AlmacenClave',
        'AlmacenNombre',
        'ZonaAlmacenaje',
        'BL (CodigoCSD)',
        'PalletContenedor',
        'Descripcion',
        'Permanente',
        'Tipo',
        'ActivoExistencia',
        'CveLP',
        'TipoGen'
    ]);

    foreach ($rows_export as $r) {
        fputcsv($output, [
            $r['clave_almacenp'],
            $r['nombre_almacen'],
            $r['zona_almacenaje'],
            $r['CodigoCSD'],
            $r['Clave_Contenedor'],
            $r['descripcion'],
            $r['Permanente'],
            $r['tipo'],
            $r['ActivoExistencia'],
            $r['CveLP'],
            $r['TipoGen'],
        ]);
    }

    fclose($output);
    exit;
}

/* ===========================================================
   KPIs FILTRADOS
   =========================================================== */

$sql_kpi = "
    SELECT
        COUNT(DISTINCT ch.IDContenedor) AS total_lp,
        SUM(CASE WHEN ch.Permanente = 1 THEN 1 ELSE 0 END) AS total_perm,
        SUM(CASE WHEN ch.Permanente = 0 THEN 1 ELSE 0 END) AS total_temp,
        SUM(CASE WHEN COALESCE(ex.existencia_total,0) > 0 THEN 1 ELSE 0 END) AS total_activo,
        SUM(CASE WHEN COALESCE(ex.existencia_total,0) <= 0 THEN 1 ELSE 0 END) AS total_inactivo,
        COUNT(DISTINCT ex.cve_almac) AS total_almacenes
    {$baseFrom}
    WHERE {$whereBase}
";

$params_kpi = array_merge($params, $params_base);

if (function_exists('db_one')) {
    $kpi = db_one($sql_kpi, $params_kpi);
} else {
    $rows_kpi = function_exists('db_all') ? db_all($sql_kpi, $params_kpi) : [];
    $kpi = $rows_kpi[0] ?? [];
}

$total_lp_filtrado        = (int)($kpi['total_lp'] ?? 0);
$total_perm_filtrado      = (int)($kpi['total_perm'] ?? 0);
$total_temp_filtrado      = (int)($kpi['total_temp'] ?? 0);
$total_activo_filtrado    = (int)($kpi['total_activo'] ?? 0);
$total_inactivo_filtrado  = (int)($kpi['total_inactivo'] ?? 0);
$total_almacenes_filtrado = (int)($kpi['total_almacenes'] ?? 0);

/* ===========================================================
   CONSULTA PRINCIPAL (GRILLA, LIMIT 500)
   =========================================================== */

$sql_rows = "
    SELECT
        ch.IDContenedor,
        ex.cve_almac          AS id_almacen,
        ap.clave              AS clave_almacenp,
        ap.nombre             AS nombre_almacen,
        ca.des_almac          AS zona_almacenaje,
        u.CodigoCSD           AS CodigoCSD,
        ch.Clave_Contenedor,
        ch.descripcion,
        ch.Permanente,
        ch.tipo,
        ch.CveLP,
        ch.TipoGen,
        COALESCE(ex.existencia_total,0) AS existencia_total
    {$baseFrom}
    WHERE {$whereBase}
    ORDER BY ex.cve_almac, ch.CveLP, ch.Clave_Contenedor
    LIMIT 500
";
$params_rows = array_merge($params, $params_base);
$rows        = function_exists('db_all') ? db_all($sql_rows, $params_rows) : [];

$total_en_pantalla = count($rows);

/* ===========================================================
   LISTAS PARA COMBOS (ALMACENES / TIPOS)
   =========================================================== */

// TODOS los almacenes desde c_almacenp (para el combo principal)
$sql_alm = "
    SELECT 
        TRIM(id)     AS id_almacen,
        TRIM(clave)  AS clave,
        TRIM(nombre) AS nombre
    FROM c_almacenp
    WHERE (Activo IS NULL OR TRIM(Activo) <> '0')
    ORDER BY TRIM(clave)
";
$almacenes_list = function_exists('db_all') ? db_all($sql_alm) : [];

// Tipos (Pallet / Contenedor / etc.)
$sql_tipo = "
    SELECT DISTINCT ch.tipo
    FROM c_charolas ch
    WHERE COALESCE(ch.CveLP, '') <> ''
    ORDER BY ch.tipo
";
$tipos_list = function_exists('db_all') ? db_all($sql_tipo) : [];

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>License Plate - Charolas</title>

    <!-- Bootstrap / DataTables / FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { font-size:10px; }
        .table-sm td, .table-sm th { padding: .25rem; }

        /* KPIs más delgados y colores homogéneos */
        .kpi-card {
            font-size: 10px;
            border-radius:10px;
            border:1px solid #e0e5f5;
        }
        .kpi-card .card-body {
            padding:.5rem .75rem;
        }
        .kpi-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color:#6c7aa0;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 700;
        }
        .kpi-sub {
            font-size: 9px;
            color:#6c7aa0;
        }
        .kpi-total { background:#f2f6ff; }
        .kpi-perm  { background:#f5f8ff; }
        .kpi-temp  { background:#f5f8ff; }
        .kpi-act   { background:#f5fdf8; }
        .kpi-inac  { background:#fff5f5; }

        .badge-perm     { background:#0d6efd; }
        .badge-temp     { background:#6c757d; }
        .badge-activo   { background:#198754; }
        .badge-inactivo { background:#dc3545; }
        .table thead th { white-space: nowrap; }
        .sel-info       { font-size: 10px; }
    </style>
</head>
<body>
<div class="container-fluid mt-3">

    <!-- CARDS RESUMEN -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card kpi-card kpi-total">
                <div class="card-body">
                    <div class="kpi-title">License Plates filtrados</div>
                    <div class="kpi-value">
                        <?php echo number_format($total_lp_filtrado); ?>
                    </div>
                    <div class="kpi-sub">En todos los almacenes seleccionados</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3 col-lg-2">
            <div class="card kpi-card kpi-perm">
                <div class="card-body">
                    <div class="kpi-title">LP Permanentes</div>
                    <div class="kpi-value text-primary">
                        <?php echo number_format($total_perm_filtrado); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3 col-lg-2">
            <div class="card kpi-card kpi-temp">
                <div class="card-body">
                    <div class="kpi-title">LP Temporales</div>
                    <div class="kpi-value text-secondary">
                        <?php echo number_format($total_temp_filtrado); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3 col-lg-2">
            <div class="card kpi-card kpi-act">
                <div class="card-body">
                    <div class="kpi-title">LP Activos</div>
                    <div class="kpi-value text-success">
                        <?php echo number_format($total_activo_filtrado); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3 col-lg-3">
            <div class="card kpi-card kpi-inac">
                <div class="card-body">
                    <div class="kpi-title">LP Inactivos</div>
                    <div class="kpi-value text-danger">
                        <?php echo number_format($total_inactivo_filtrado); ?>
                    </div>
                    <div class="kpi-sub">Almacenes: <?php echo number_format($total_almacenes_filtrado); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get">
                <!-- LP -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">License Plate (CveLP)</label>
                    <input type="text"
                           name="lp"
                           class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($lp_filtro); ?>"
                           placeholder="LP parcial">
                </div>

                <!-- Almacén -->
                <div class="col-12 col-sm-3 col-md-3">
                    <label class="form-label mb-1">Almacén</label>
                    <select name="almacen" class="form-select form-select-sm">
                        <option value="">Todos los almacenes</option>
                        <?php foreach ($almacenes_list as $a): ?>
                            <?php
                            $val   = $a['id_almacen'];
                            $nom   = $a['nombre'] ?: $a['clave'];
                            $label = $a['clave'] . ' - ' . $nom;
                            ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"
                                <?php echo ($almacen_f !== '' && $almacen_f == $val) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- NUEVO: Zona de almacenaje -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">Zona de almacenaje</label>
                    <select name="zona" class="form-select form-select-sm" <?php echo $almacen_f === '' ? 'disabled' : ''; ?>>
                        <option value="">Todas</option>
                        <?php foreach ($zonas_list as $z): ?>
                            <?php $zona = $z['zona']; ?>
                            <option value="<?php echo htmlspecialchars($zona); ?>"
                                <?php echo ($zona_f !== '' && $zona_f == $zona) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zona); ?>
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

                <!-- Tipo -->
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

                <!-- Permanente/Temporal -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">License Plate Status</label>
                    <select name="statuslp" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $statuslp_f === '1' ? 'selected' : ''; ?>>Permanente</option>
                        <option value="0" <?php echo $statuslp_f === '0' ? 'selected' : ''; ?>>Temporal</option>
                    </select>
                </div>

                <!-- Activo/Inactivo por existencia -->
                <div class="col-12 col-sm-3 col-md-2">
                    <label class="form-label mb-1">Activo / Inactivo</label>
                    <select name="activo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $activo_f === '1' ? 'selected' : ''; ?>>Activo (exist&gt;0)</option>
                        <option value="0" <?php echo $activo_f === '0' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="col-12 col-sm-8 col-md-5 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        Buscar
                    </button>
                    <a href="license_plate.php" class="btn btn-outline-secondary btn-sm">
                        Limpiar
                    </a>
                    <button type="submit" name="export" value="csv" class="btn btn-success btn-sm mt-1 mt-sm-0">
                        Exportar CSV
                    </button>
                    <!-- Botón para crear nuevos LPs -->
                    <a href="license_plate_new.php" class="btn btn-outline-primary btn-sm mt-1 mt-sm-0">
                        <i class="fa fa-plus"></i> Nuevo LP
                    </a>
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
                Seleccionados: <span id="sel_count">0</span>
            </span>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table id="tblLicensePlate" class="table table-striped table-bordered table-sm align-middle">
                    <thead>
                    <tr>
                        <th style="width:60px;">Acciones</th>
                        <th>Almacén</th>
                        <th>Zona Almacenaje</th>
                        <th>BL (CodigoCSD)</th>
                        <th>Descripción</th>
                        <th>LP Pallet</th>
                        <th>Tipo</th>
                        <th>LP Contenedor</th>
                        <th>Permanente</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $nomAlm      = $row['nombre_almacen'] ?: $row['clave_almacenp'];
                        $labelAl     = $row['clave_almacenp'] . ' - ' . $nomAlm;
                        $activoExist = ($row['existencia_total'] > 0);
                        ?>
                        <tr>
                            <td class="text-center">
                                <!-- Ver (detalle LP) -->
                                <a href="#"
                                   class="btn btn-link btn-sm p-0 me-1 lp-detalle-link"
                                   title="Ver detalle"
                                   data-lp="<?php echo htmlspecialchars((string)$row['CveLP']); ?>">
                                    <i class="fa fa-search"></i>
                                </a>
                                <!-- Imprimir LP (PDF 4x6) -->
                                <a href="license_plate_print.php?lp=<?php echo urlencode($row['CveLP']); ?>&size=4x6"
                                   target="_blank"
                                   class="btn btn-link btn-sm p-0 me-1"
                                   title="Imprimir License Plate 4x6">
                                    <i class="fa fa-barcode"></i>
                                </a>
                                <!-- Imprimir LP (PDF 4x3) -->
                                <a href="license_plate_print.php?lp=<?php echo urlencode($row['CveLP']); ?>&size=4x3"
                                   target="_blank"
                                   class="btn btn-link btn-sm p-0"
                                   title="Imprimir License Plate 4x3">
                                    <i class="fa fa-barcode" style="transform:rotate(90deg);"></i>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars((string)$labelAl); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['zona_almacenaje'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['CodigoCSD'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['Clave_Contenedor']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['tipo']); ?></td>
                            <td></td>
                            <td class="text-center">
                                <?php if ((int)$row['Permanente'] === 1): ?>
                                    <span class="badge badge-perm">Permanente</span>
                                <?php else: ?>
                                    <span class="badge badge-temp">Temporal</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($activoExist): ?>
                                    <span class="badge badge-activo">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="mt-2 text-muted">
                Status <strong>Activo/Inactivo</strong> calculado con base en
                <strong>ts_existenciatarima</strong> (SUM(existencia) por
                <strong>ntarima</strong> y <strong>cve_almac</strong>),  
                BL desde <strong>c_ubicacion.CodigoCSD</strong> y zona desde
                <strong>c_almacen.des_almac</strong>.
            </p>
        </div>
    </div>
</div>

<!-- MODAL DETALLE LP -->
<div class="modal fade" id="modalDetalleLp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">Detalle License Plate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="font-size:10px;">
        Cargando...
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#tblLicensePlate').DataTable({
        pageLength: 25,
        lengthMenu: [[25, 50, 100], [25, 50, 100]],
        scrollX: true,
        scrollY: '420px',
        scrollCollapse: true,
        order: [[1, 'asc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
        },
        dom: 'lrtip'
    });

    // Contar seleccionados (por si luego activamos checkboxes)
    var selCount = 0;
    $('#tblLicensePlate tbody').on('click', 'tr', function () {
        $(this).toggleClass('table-primary');
        selCount = $('#tblLicensePlate tbody tr.table-primary').length;
        $('#sel_count').text(selCount);
    });

    // Detalle LP en modal
    $('.lp-detalle-link').on('click', function (e) {
        e.preventDefault();
        var lp = $(this).data('lp') || '';
        var $modal = $('#modalDetalleLp');

        $modal.find('.modal-title').text('Detalle License Plate: ' + lp);
        $modal.find('.modal-body').html('<p class="text-muted">Cargando detalle...</p>');
        var modal = new bootstrap.Modal($modal[0]);
        modal.show();

        $.get('license_plate_detalle.php', { lp: lp })
            .done(function (html) {
                $modal.find('.modal-body').html(html);
            })
            .fail(function () {
                $modal.find('.modal-body').html(
                    '<div class="alert alert-danger mb-0">No fue posible cargar el detalle del LP.</div>'
                );
            });
    });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
