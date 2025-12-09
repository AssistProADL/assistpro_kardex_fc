<?php
// public/sfa/lista_precios.php

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db();

/* ===========================================================
   Lectura de filtros
   =========================================================== */

$f_almacen = isset($_GET['almacen']) ? trim($_GET['almacen']) : 'Todos';
$f_moneda  = isset($_GET['moneda'])  ? trim($_GET['moneda'])  : 'Todas';
$f_vig     = isset($_GET['vigencia'])? trim($_GET['vigencia']): 'Vigentes';
$f_buscar  = isset($_GET['buscar'])  ? trim($_GET['buscar'])  : '';

$vigencias_validas = ['Vigentes','Futuras','Vencidas','Todas'];
if (!in_array($f_vig, $vigencias_validas, true)) {
    $f_vig = 'Vigentes';
}

/* ===========================================================
   Catálogos
   =========================================================== */

/*
   c_almacenp según tu script:
   - empresa_id int
   - id text
   - clave text
   - nombre text
   ...
*/
$almacenes = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    WHERE IFNULL(Activo,'1') <> '0'
    ORDER BY nombre
");

$monedas = db_all("
    SELECT Id_Moneda, Cve_Moneda, Des_Moneda
    FROM c_monedas
    WHERE IFNULL(Activo,1) = 1
    ORDER BY Des_Moneda
");

/* ===========================================================
   WHERE para la grilla
   =========================================================== */

$where  = [];
$params = [];
$where[] = "1=1";

if ($f_almacen !== '' && $f_almacen !== 'Todos') {
    $where[] = "lp.Cve_Almac = :cve_almac";
    $params[':cve_almac'] = (int)$f_almacen;
}

if ($f_moneda !== '' && $f_moneda !== 'Todas') {
    $where[] = "lp.id_moneda = :id_moneda";
    $params[':id_moneda'] = (int)$f_moneda;
}

if ($f_buscar !== '') {
    $where[] = "lp.Lista LIKE :buscar";
    $params[':buscar'] = '%' . $f_buscar . '%';
}

$hoy = date('Y-m-d');
switch ($f_vig) {
    case 'Vigentes':
        $where[] = "(
            (lp.FechaIni IS NULL OR lp.FechaIni = '0000-00-00' OR lp.FechaIni <= :hoy_vig1)
            AND
            (lp.FechaFin IS NULL OR lp.FechaFin = '0000-00-00' OR lp.FechaFin >= :hoy_vig2)
        )";
        $params[':hoy_vig1'] = $hoy;
        $params[':hoy_vig2'] = $hoy;
        break;
    case 'Futuras':
        $where[] = "(lp.FechaIni IS NOT NULL AND lp.FechaIni <> '0000-00-00' AND lp.FechaIni > :hoy_fut)";
        $params[':hoy_fut'] = $hoy;
        break;
    case 'Vencidas':
        $where[] = "(lp.FechaFin IS NOT NULL AND lp.FechaFin <> '0000-00-00' AND lp.FechaFin < :hoy_ven)";
        $params[':hoy_ven'] = $hoy;
        break;
    case 'Todas':
    default:
        break;
}

$whereSql = implode(' AND ', $where);

/* ===========================================================
   Consulta principal de listas
   =========================================================== */

$sqlListas = "
    SELECT
        lp.id,
        lp.Lista,
        lp.Tipo,
        lp.FechaIni,
        lp.FechaFin,
        lp.Cve_Almac,
        lp.TipoServ,
        lp.id_moneda,
        ap.nombre AS Des_Almac,
        m.Des_Moneda,
        m.Cve_Moneda,
        CASE
            WHEN ( (lp.FechaIni IS NULL OR lp.FechaIni = '0000-00-00' OR lp.FechaIni <= :hoy1)
                   AND (lp.FechaFin IS NULL OR lp.FechaFin = '0000-00-00' OR lp.FechaFin >= :hoy2) )
                THEN 'VIGENTE'
            WHEN (lp.FechaIni IS NOT NULL AND lp.FechaIni <> '0000-00-00' AND lp.FechaIni > :hoy3)
                THEN 'FUTURA'
            WHEN (lp.FechaFin IS NOT NULL AND lp.FechaFin <> '0000-00-00' AND lp.FechaFin < :hoy4)
                THEN 'VENCIDA'
            ELSE 'SIN FECHA'
        END AS StatusVigencia
    FROM listap lp
    LEFT JOIN c_almacenp ap
           ON CAST(ap.id AS SIGNED) = lp.Cve_Almac
    LEFT JOIN c_monedas  m
           ON m.Id_Moneda = lp.id_moneda
    WHERE $whereSql
    ORDER BY lp.id DESC
";

$paramsListas = $params;
$paramsListas[':hoy1'] = $hoy;
$paramsListas[':hoy2'] = $hoy;
$paramsListas[':hoy3'] = $hoy;
$paramsListas[':hoy4'] = $hoy;

$listas = db_all($sqlListas, $paramsListas);
$total_listas = count($listas);

/* ===========================================================
   Contadores por vigencia (sobre las listas cargadas)
   =========================================================== */

$cnt_vigentes = 0;
$cnt_futuras  = 0;
$cnt_vencidas = 0;

foreach ($listas as $row) {
    switch ($row['StatusVigencia']) {
        case 'VIGENTE': $cnt_vigentes++; break;
        case 'FUTURA':  $cnt_futuras++;  break;
        case 'VENCIDA': $cnt_vencidas++; break;
    }
}
?>
<div class="container-fluid px-3" style="font-size:10px;">
    <div class="row mb-2">
        <div class="col-6">
            <h5 class="mb-0" style="color:#0F5AAD;">Listas de precios (SFA)</h5>
            <small class="text-muted">Origen: listap / detallelp (almacenes de c_almacenp)</small>
        </div>
        <div class="col-6 text-end">
            <a href="lista_precios_editar.php" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> Nueva lista
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Filtros + grilla -->
        <div class="col-md-8">
            <div class="card mb-2">
                <div class="card-header py-1" style="font-size:10px;">
                    Filtros
                </div>
                <div class="card-body py-2">
                    <form method="get" class="row g-2 align-items-end" style="font-size:10px;">
                        <div class="col-md-3">
                            <label class="form-label mb-0">Almacén</label>
                            <select name="almacen" class="form-select form-select-sm">
                                <option value="Todos" <?= $f_almacen === 'Todos' ? 'selected' : '' ?>>Todos</option>
                                <?php foreach ($almacenes as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>"
                                        <?= ($f_almacen !== 'Todos' && (int)$f_almacen === (int)$a['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-0">Moneda</label>
                            <select name="moneda" class="form-select form-select-sm">
                                <option value="Todas" <?= $f_moneda === 'Todas' ? 'selected' : '' ?>>Todas</option>
                                <?php foreach ($monedas as $m): ?>
                                    <option value="<?= (int)$m['Id_Moneda'] ?>"
                                        <?= ($f_moneda !== 'Todas' && (int)$f_moneda === (int)$m['Id_Moneda']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['Des_Moneda'] . ' (' . $m['Cve_Moneda'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-0">Vigencia</label>
                            <select name="vigencia" class="form-select form-select-sm">
                                <option value="Vigentes" <?= $f_vig === 'Vigentes' ? 'selected' : '' ?>>Vigentes</option>
                                <option value="Futuras"  <?= $f_vig === 'Futuras'  ? 'selected' : '' ?>>Futuras</option>
                                <option value="Vencidas" <?= $f_vig === 'Vencidas' ? 'selected' : '' ?>>Vencidas</option>
                                <option value="Todas"    <?= $f_vig === 'Todas'    ? 'selected' : '' ?>>Todas</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label mb-0">Buscar</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="buscar" class="form-control"
                                       placeholder="Nombre de lista..."
                                       value="<?= htmlspecialchars($f_buscar) ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                                <a href="lista_precios.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header py-1" style="font-size:10px;">
                    Listas de precios
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:420px;overflow:auto;">
                        <table class="table table-striped table-hover table-sm mb-0" style="font-size:10px;">
                            <thead class="table-light">
                            <tr>
                                <th style="width:70px;">Acciones</th>
                                <th>ID</th>
                                <th>Lista</th>
                                <th>Almacén</th>
                                <th>Moneda</th>
                                <th>Tipo</th>
                                <th>Tipo Serv.</th>
                                <th>Fecha Ini</th>
                                <th>Fecha Fin</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($listas)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-3">
                                        Sin listas con los filtros actuales.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listas as $row): ?>
                                    <?php
                                    $tipo_txt      = ((int)$row['Tipo'] === 0) ? 'Normal' : 'Otro';
                                    $tipo_serv_txt = ($row['TipoServ'] === 'S') ? 'Servicios' : 'Productos';

                                    $fini = $row['FechaIni'] && $row['FechaIni'] !== '0000-00-00'
                                        ? date('d/m/Y', strtotime($row['FechaIni']))
                                        : '';
                                    $ffin = $row['FechaFin'] && $row['FechaFin'] !== '0000-00-00'
                                        ? date('d/m/Y', strtotime($row['FechaFin']))
                                        : '';

                                    $status = $row['StatusVigencia'];
                                    $status_class = 'badge bg-success';
                                    if ($status === 'FUTURA')  $status_class = 'badge bg-info';
                                    if ($status === 'VENCIDA') $status_class = 'badge bg-danger';
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="lista_precios_editar.php?id=<?= (int)$row['id'] ?>"
                                               class="btn btn-outline-primary btn-xs btn-sm"
                                               title="Editar lista">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        </td>
                                        <td><?= (int)$row['id'] ?></td>
                                        <td><?= htmlspecialchars($row['Lista']) ?></td>
                                        <td><?= htmlspecialchars($row['Des_Almac'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(($row['Des_Moneda'] ?? '') . (isset($row['Cve_Moneda']) ? ' (' . $row['Cve_Moneda'] . ')' : '')) ?></td>
                                        <td><?= htmlspecialchars($tipo_txt) ?></td>
                                        <td><?= htmlspecialchars($tipo_serv_txt) ?></td>
                                        <td><?= htmlspecialchars($fini) ?></td>
                                        <td><?= htmlspecialchars($ffin) ?></td>
                                        <td><span class="<?= $status_class ?>"><?= htmlspecialchars($status) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs derecha -->
        <div class="col-md-4">
            <div class="card mb-2">
                <div class="card-header py-1" style="font-size:10px;">
                    Resumen listas
                </div>
                <div class="card-body" style="font-size:10px;">
                    <div class="mb-1">
                        <strong>Total:</strong> <?= $total_listas ?> listas
                    </div>
                    <div class="mb-1">
                        <span class="badge rounded-pill bg-success">Vigentes: <?= $cnt_vigentes ?></span>
                    </div>
                    <div class="mb-1">
                        <span class="badge rounded-pill bg-info">Futuras: <?= $cnt_futuras ?></span>
                    </div>
                    <div class="mb-1">
                        <span class="badge rounded-pill bg-danger">Vencidas: <?= $cnt_vencidas ?></span>
                    </div>
                    <hr class="my-2">
                    <small class="text-muted">Fecha de corte: <?= $hoy ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
