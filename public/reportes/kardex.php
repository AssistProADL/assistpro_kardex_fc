<?php
// ===============================================================
//  KARDEX - VISTA DIRECTA (paginado propio, orden bidireccional)
//  Base: assistpro_etl_fc
// ===============================================================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

$TITLE = 'Kardex - Vista Directa';

// ------------------ Filtros ------------------
$f_empresa  = $_GET['empresa']  ?? '';
$f_almacen  = $_GET['almacen']  ?? '';
$f_articulo = $_GET['articulo'] ?? '';
$f_lote     = $_GET['lote']     ?? '';
$f_fini     = $_GET['fini']     ?? '';
$f_ffin     = $_GET['ffin']     ?? '';

// ------------------ Paginación ------------------
$perPage = 25;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

// ============================================================
//  Catálogo de empresas (c_compania)
// ============================================================
$empresas = [];
try {
    $empresas = db_all("
        SELECT cve_cia, clave_empresa, des_cia
        FROM c_compania
        ORDER BY clave_empresa
    ");
} catch (Throwable $e) {
    $empresas = [];
}

// ============================================================
//  Catálogo de almacenes (c_almacenp) – SIEMPRE
// ============================================================
$almacenes = [];
try {
    $almacenes = db_all("
        SELECT id, clave, nombre, cve_cia
        FROM c_almacenp
        ORDER BY clave
    ");
} catch (Throwable $e) {
    $almacenes = [];
}

// ============================================================
//  Catálogo de artículos (combo)
// ============================================================
$articulos = [];
try {
    $articulos = db_all("
        SELECT cve_articulo, des_articulo
        FROM c_articulo
        ORDER BY cve_articulo
        LIMIT 500
    ");
} catch (Throwable $e) {
    $articulos = [];
}

// ============================================================
//  Catálogo de lotes (solo si hay artículo)
// ============================================================
$lotes = [];
if ($f_articulo !== '') {
    try {
        $lotes = db_all("
            SELECT DISTINCT cve_lote
            FROM t_cardex
            WHERE cve_articulo = :art
              AND cve_lote <> ''
            ORDER BY cve_lote
        ", [':art' => $f_articulo]);
    } catch (Throwable $e) {
        $lotes = [];
    }
}

// ============================================================
//  WHERE dinámico
// ============================================================
$where  = [];
$params = [];

if ($f_empresa !== '') {
    $where[] = "com.cve_cia = :cia";
    $params[':cia'] = $f_empresa;
}
if ($f_almacen !== '') {
    $where[] = "cap.id = :almac";
    $params[':almac'] = $f_almacen;
}
if ($f_articulo !== '') {
    $where[] = "tc.cve_articulo = :articulo";
    $params[':articulo'] = $f_articulo;
}
if ($f_lote !== '') {
    $where[] = "tc.cve_lote = :lote";
    $params[':lote'] = $f_lote;
}
if ($f_fini !== '' && $f_ffin !== '') {
    $where[] = "tc.fecha BETWEEN :fini AND :ffin";
    $params[':fini'] = $f_fini . " 00:00:00";
    $params[':ffin'] = $f_ffin . " 23:59:59";
}

$whereSQL = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// ============================================================
//  Total de registros para paginación
// ============================================================
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM t_cardex tc
    LEFT JOIN c_almacen ca
        ON ca.cve_almac = tc.Cve_Almac
    LEFT JOIN c_almacenp cap
        ON cap.id = ca.cve_almacenp
    LEFT JOIN c_compania com
        ON com.cve_cia = cap.cve_cia
    $whereSQL
";
$totalRegs = 0;
try {
    $totalRegs = (int)db_val($sqlCount, $params);
} catch (Throwable $e) {
    $totalRegs = 0;
}
$totalPages = max(1, (int)ceil($totalRegs / $perPage));

// ============================================================
//  Consulta principal (solo 25 por página)
// ============================================================
$sql = "
    SELECT
        tc.id,
        tc.fecha,
        tc.id_TipoMovimiento,
        tc.cve_articulo,
        tc.cve_lote,
        tc.origen,
        tc.destino,
        tc.stockinicial,
        tc.cantidad,
        tc.ajuste,
        tc.cve_usuario,
        tc.Referencia,
        ca.clave_almacen      AS zona_clave,
        ca.des_almac          AS zona_nombre,
        cap.clave             AS almac_clave,
        cap.nombre            AS almac_nombre,
        com.clave_empresa     AS empresa_clave,
        com.des_cia           AS empresa_nombre
    FROM t_cardex tc
    LEFT JOIN c_almacen ca
        ON ca.cve_almac = tc.Cve_Almac
    LEFT JOIN c_almacenp cap
        ON cap.id = ca.cve_almacenp
    LEFT JOIN c_compania com
        ON com.cve_cia = cap.cve_cia
    $whereSQL
    ORDER BY tc.id DESC
    LIMIT $offset, $perPage
";

$rows = [];
$error_sql = '';
try {
    $rows = db_all($sql, $params);
} catch (Throwable $e) {
    $error_sql = $e->getMessage();
}

// ============================================================
//  Mapas en memoria: artículos, movimientos, BL
// ============================================================

// Artículos
$mapArt = [];
$arts = array_unique(array_column($rows, 'cve_articulo'));
if ($arts) {
    $ph = [];
    $pa = [];
    foreach ($arts as $i => $a) {
        if ($a === null || $a === '') continue;
        $k = ":a$i";
        $ph[] = $k;
        $pa[$k] = $a;
    }
    if ($ph) {
        $res = db_all("
            SELECT cve_articulo, des_articulo
            FROM c_articulo
            WHERE cve_articulo IN (".implode(",", $ph).")
        ", $pa);
        foreach ($res as $r) {
            $mapArt[$r['cve_articulo']] = $r['des_articulo'];
        }
    }
}

// Movimientos
$mapMov = [];
$mids = array_unique(array_column($rows, 'id_TipoMovimiento'));
if ($mids) {
    $ph = [];
    $pm = [];
    foreach ($mids as $i => $m) {
        if ($m === null || $m === '') continue;
        $k = ":m$i";
        $ph[] = $k;
        $pm[$k] = $m;
    }
    if ($ph) {
        $res = db_all("
            SELECT id_TipoMovimiento, nombre
            FROM t_tipomovimiento
            WHERE id_TipoMovimiento IN (".implode(",", $ph).")
        ", $pm);
        foreach ($res as $r) {
            $mapMov[$r['id_TipoMovimiento']] = $r['nombre'];
        }
    }
}

// Ubicaciones (BL)
$mapUb = [];
$ubIds = [];
foreach ($rows as $r) {
    if (ctype_digit((string)$r['origen']))  $ubIds[(int)$r['origen']]  = true;
    if (ctype_digit((string)$r['destino'])) $ubIds[(int)$r['destino']] = true;
}
if ($ubIds) {
    $ph = [];
    $pu = [];
    foreach (array_keys($ubIds) as $i => $u) {
        $k = ":u$i";
        $ph[] = $k;
        $pu[$k] = $u;
    }
    $res = db_all("
        SELECT idy_ubica, CodigoCSD
        FROM c_ubicacion
        WHERE idy_ubica IN (".implode(",", $ph).")
    ", $pu);
    foreach ($res as $r) {
        $mapUb[(string)$r['idy_ubica']] = $r['CodigoCSD'];
    }
}

// ============================================================
//  Helper: querystring para paginación
// ============================================================
function build_query_without_page(array $extra = []) {
    $q = $_GET;
    unset($q['page']);
    $q = array_merge($q, $extra);
    return http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($TITLE) ?></title>

<link rel="stylesheet" href="/assistpro_kardex_fc/assets/bootstrap.min.css">

<style>
body{
    background:#f7f9fc;
    font-size:10px;
}
.container-ap{
    max-width:96%;
    margin:15px auto;
}
h2{
    color:#0F5AAD;
    font-size:18px;
    margin:10px 0;
}
.ap-filtros{
    background:#fff;
    border:1px solid #e1e5eb;
    border-radius:8px;
    padding:8px 12px;
    margin-bottom:10px;
}
label{
    font-weight:600;
    font-size:10px;
}
.tablewrap{
    background:#fff;
    border:1px solid #e1e5eb;
    border-radius:8px;
    padding:10px;
}
.table-responsive{
    overflow-x:auto;
}
table.kdx{
    width:100%;
    border-collapse:collapse;
}
table.kdx thead th{
    background:#0F5AAD;
    color:#fff;
    font-weight:600;
    font-size:10px;
    padding:4px 6px;
    border:1px solid #d0d5dd;
}
table.kdx tbody td{
    font-size:10px;
    padding:3px 6px;
    border:1px solid #e1e5eb;
}
/* 1 renglón por registro */
table.kdx thead th,
table.kdx tbody td{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
td.text-end{
    text-align:right;
}
.kdx-pager{
    margin-top:6px;
    font-size:10px;
}
.kdx-pager a{
    padding:2px 8px;
    border:1px solid #d0d5dd;
    border-radius:4px;
    margin-right:4px;
    text-decoration:none;
    color:#0F5AAD;
}
.kdx-pager span.current{
    padding:2px 8px;
    border-radius:4px;
    background:#0F5AAD;
    color:#fff;
    margin-right:4px;
}
</style>
</head>
<body>
<div class="container-ap">

    <h2><?= htmlspecialchars($TITLE) ?></h2>

    <!-- Filtros -->
    <form method="get" class="ap-filtros">
        <div class="row align-items-end">
            <div class="col-md-2">
                <label>Empresa:</label>
                <select name="empresa" class="form-select form-select-sm">
                    <option value="">-- Todas --</option>
                    <?php foreach ($empresas as $e): ?>
                        <option value="<?= htmlspecialchars($e['cve_cia']) ?>"
                            <?= $f_empresa == $e['cve_cia'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['clave_empresa'].' - '.$e['des_cia']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label>Almacén:</label>
                <select name="almacen" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($almacenes as $a): ?>
                        <?php $txtAlm = $a['clave'].' - '.$a['nombre']; ?>
                        <option value="<?= htmlspecialchars($a['id']) ?>"
                            <?= $f_almacen == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($txtAlm) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label>Artículo:</label>
                <select name="articulo" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($articulos as $a): ?>
                        <?php $txt = $a['cve_articulo'].' | '.$a['des_articulo']; ?>
                        <option value="<?= htmlspecialchars($a['cve_articulo']) ?>"
                            <?= $f_articulo == $a['cve_articulo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($txt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label>Lote:</label>
                <select name="lote" class="form-select form-select-sm" <?= $f_articulo==''?'disabled':'' ?>>
                    <option value="">-- Todos --</option>
                    <?php foreach ($lotes as $l): ?>
                        <option value="<?= htmlspecialchars($l['cve_lote']) ?>"
                            <?= $f_lote == $l['cve_lote'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['cve_lote']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1">
                <label>F. inicio:</label>
                <input type="date" name="fini" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($f_fini) ?>">
            </div>

            <div class="col-md-1">
                <label>F. fin:</label>
                <input type="date" name="ffin" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($f_ffin) ?>">
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-12">
                <button class="btn btn-primary btn-sm" type="submit">Aplicar</button>
                <a href="kardex.php" class="btn btn-secondary btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <?php if ($error_sql): ?>
        <div class="alert alert-danger py-1 my-2">
            Error al consultar Kardex: <?= htmlspecialchars($error_sql) ?></div>
    <?php endif; ?>

    <!-- Tabla -->
    <div class="tablewrap">
        <div class="table-responsive">
            <table class="kdx">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Proyecto</th>
                        <th>Producto</th>
                        <th>UOM</th>
                        <th>Lote</th>
                        <th>Serie</th>
                        <th>Aj Id</th>
                        <th>Ajuste</th>
                        <th>Mov Firma</th>
                        <th>Referencia</th>
                        <th>Notas</th>
                        <th>Alm Ori</th>
                        <th>BL Ori</th>
                        <th>Zona Ori</th>
                        <th>Stock Ini Ori</th>
                        <th>Mov Ori</th>
                        <th>Stock Fin Ori</th>
                        <th>Alm Dst</th>
                        <th>BL Dst</th>
                        <th>Zona Dst</th>
                        <th>Stock Ini Dst</th>
                        <th>Mov Dst</th>
                        <th>Stock Fin Dst</th>
                        <th>Tx Id</th>
                        <th>Usuario</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            // Descripción de artículo
                            $desc = $mapArt[$r['cve_articulo']] ?? '';

                            // BL origen / destino
                            $oriBL = $mapUb[$r['origen']]  ?? $r['origen'];
                            $desBL = $mapUb[$r['destino']] ?? $r['destino'];

                            // Saldos
                            $stockIni = (float)$r['stockinicial'];
                            $cant     = (float)$r['cantidad'];
                            $ajuste   = (float)$r['ajuste'];
                            $entrada  = $cant >= 0 ? $cant : 0;
                            $salida   = $cant < 0 ? -$cant : 0;
                            $stockFin = $stockIni + $cant + $ajuste;

                            // Tipo (E/S/A) aproximado
                            if ($cant > 0 && $ajuste == 0) {
                                $tipo = 'E';
                            } elseif ($cant < 0 && $ajuste == 0) {
                                $tipo = 'S';
                            } elseif ($ajuste != 0) {
                                $tipo = 'A';
                            } else {
                                $tipo = '';
                            }

                            // Movimiento firmado (como en kardex_test: movimiento con signo)
                            $movFirma = $cant + $ajuste;

                            // Fecha formateada
                            $fechaTxt = '';
                            if (!empty($r['fecha'])) {
                                try {
                                    $dt = new DateTime($r['fecha']);
                                    $fechaTxt = $dt->format('d/m/Y H:i:s');
                                } catch (Throwable $e) {
                                    $fechaTxt = $r['fecha'];
                                }
                            }

                            // Campos que aún no tenemos en legacy:
                            $proyecto = ''; // pendiente mapear
                            $uom      = ''; // pendiente mapear
                            $serie    = ''; // pendiente mapear
                            $ajId     = ''; // pendiente mapear
                            $notas    = ''; // pendiente mapear

                            // ORIGEN: usamos almacén/zona actual y saldos globales
                            $almOri   = $r['almac_clave'] ?? '';
                            $zonaOri  = $r['zona_clave']  ?? '';
                            $stockIniOri = $stockIni;
                            $movOri       = $movFirma;
                            $stockFinOri  = $stockFin;

                            // DESTINO: por ahora vacíos hasta integrar bidireccional real
                            $almDst      = '';
                            $zonaDst     = '';
                            $stockIniDst = '';
                            $movDst      = '';
                            $stockFinDst = '';

                            $txId    = $r['id'];
                            $usuario = $r['cve_usuario'];
                            $ref     = $r['Referencia'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($fechaTxt) ?></td>
                            <td><?= htmlspecialchars($tipo) ?></td>
                            <td><?= htmlspecialchars($proyecto) ?></td>
                            <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                            <td><?= htmlspecialchars($uom) ?></td>
                            <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                            <td><?= htmlspecialchars($serie) ?></td>
                            <td><?= htmlspecialchars($ajId) ?></td>
                            <td class="text-end"><?= number_format($ajuste, 4) ?></td>
                            <td class="text-end"><?= number_format($movFirma, 4) ?></td>
                            <td><?= htmlspecialchars($ref) ?></td>
                            <td><?= htmlspecialchars($notas) ?></td>
                            <td><?= htmlspecialchars($almOri) ?></td>
                            <td><?= htmlspecialchars($oriBL) ?></td>
                            <td><?= htmlspecialchars($zonaOri) ?></td>
                            <td class="text-end"><?= number_format($stockIniOri, 4) ?></td>
                            <td class="text-end"><?= number_format($movOri, 4) ?></td>
                            <td class="text-end"><?= number_format($stockFinOri, 4) ?></td>
                            <td><?= htmlspecialchars($almDst) ?></td>
                            <td><?= htmlspecialchars($desBL) ?></td>
                            <td><?= htmlspecialchars($zonaDst) ?></td>
                            <td><?= htmlspecialchars($stockIniDst) ?></td>
                            <td><?= htmlspecialchars($movDst) ?></td>
                            <td><?= htmlspecialchars($stockFinDst) ?></td>
                            <td><?= htmlspecialchars($txId) ?></td>
                            <td><?= htmlspecialchars($usuario) ?></td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginador -->
        <div class="kdx-pager">
            <?php
            $queryPrev = build_query_without_page(['page' => max(1, $page-1)]);
            $queryNext = build_query_without_page(['page' => min($totalPages, $page+1)]);
            ?>
            <?php if ($page > 1): ?>
                <a href="kardex.php?<?= htmlspecialchars($queryPrev) ?>">&laquo; Anterior</a>
            <?php endif; ?>
            <span class="current">Página <?= $page ?> de <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="kardex.php?<?= htmlspecialchars($queryNext) ?>">Siguiente &raquo;</a>
            <?php endif; ?>
            <span style="margin-left:8px;">Registros: <?= $totalRegs ?></span>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
