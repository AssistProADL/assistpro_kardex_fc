<?php
// public/reportes/existencias_ubicacion.php
// Reporte de existencias por ubicación usando v_existencias_por_ubicacion_ao
// Plantilla de filtros FIJA: 'existencias_ubicacion' (no modificable por el usuario)

declare(strict_types=1);
set_time_limit(300);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

// ------------------------------
// 1. Configuración de módulo / plantilla fija
// ------------------------------
$moduloPlantillas   = 'existencias_ubicacion';
$nombrePlantillaFija = 'existencias_ubicacion'; // nombre en ap_plantillas_filtros

// Campos de filtros que nos interesan
$filterNames = [
    'empresa','almacen','zona','bl',
    'lp','producto','lote',
    'ruta','cliente','proveedor',
    'vendedor','usuario',
    'zona_recep','zona_qa','zona_emb',
    'proyecto','ubica_mfg',
    'use_empresa','use_almacen','use_zona','use_bl',
    'use_lp','use_producto','use_lote',
    'use_ruta','use_cliente','use_proveedor',
    'use_vendedor','use_usuario',
    'use_zona_recep','use_zona_qa','use_zona_emb',
    'use_proyecto','use_ubica_mfg',
];

$hayFiltrosEnGet = false;
foreach ($filterNames as $fn) {
    if (array_key_exists($fn, $_GET)) {
        $hayFiltrosEnGet = true;
        break;
    }
}

// ------------------------------
// 2. Aplicar plantilla FIJA y defaults de empresa/almacén/zona
// ------------------------------

// 2.1 Aplicar plantilla fija solo si NO hay filtros en GET
if (!$hayFiltrosEnGet) {
    $tplAplicada = null;

    // a) Intentar plantilla por nombre exacto
    $tplAplicada = db_one("
        SELECT *
        FROM ap_plantillas_filtros
        WHERE modulo = ?
          AND activo = 1
          AND nombre = ?
        ORDER BY id DESC
        LIMIT 1
    ", [$moduloPlantillas, $nombrePlantillaFija]);

    // b) Si no existe, intentar default
    if (!$tplAplicada) {
        $tplAplicada = db_one("
            SELECT *
            FROM ap_plantillas_filtros
            WHERE modulo = ?
              AND activo = 1
              AND es_default = 1
            ORDER BY id DESC
            LIMIT 1
        ", [$moduloPlantillas]);
    }

    // c) Si tampoco hay default, tomar la primera activa
    if (!$tplAplicada) {
        $tplAplicada = db_one("
            SELECT *
            FROM ap_plantillas_filtros
            WHERE modulo = ?
              AND activo = 1
            ORDER BY id
            LIMIT 1
        ", [$moduloPlantillas]);
    }

    // d) Hidratar filtros desde la plantilla elegida (si existe)
    if ($tplAplicada) {
        $cfg = json_decode($tplAplicada['filtros_json'] ?? '', true);
        if (is_array($cfg)) {
            if (!empty($cfg['use']) && is_array($cfg['use'])) {
                foreach ($cfg['use'] as $k => $v) {
                    $_GET['use_'.$k] = $v ? '1' : '0';
                }
            }
            if (!empty($cfg['defaults']) && is_array($cfg['defaults'])) {
                foreach ($cfg['defaults'] as $k => $v) {
                    if (!isset($_GET[$k]) || $_GET[$k] === '') {
                        $_GET[$k] = (string)$v;
                    }
                }
            }
        }
    }
}

// 2.2 Defaults de empresa / almacén / zona si siguen vacíos
$empresaSelDefault = $_GET['empresa'] ?? '';
$almacenSelDefault = $_GET['almacen'] ?? '';
$zonaSelDefault    = $_GET['zona']    ?? '';

// 2.2.1 Empresa default (c_compania)
if ($empresaSelDefault === '') {
    $ciaDefault = db_one("
        SELECT cve_cia
        FROM c_compania
        WHERE COALESCE(Activo,1) = 1
        ORDER BY cve_cia
        LIMIT 1
    ");
    if ($ciaDefault && isset($ciaDefault['cve_cia'])) {
        $_GET['empresa']   = (string)$ciaDefault['cve_cia'];
        $empresaSelDefault = $_GET['empresa'];
    }
}

// 2.2.2 Almacén padre default (c_almacenp, ligado a empresa por cve_cia)
if ($almacenSelDefault === '') {
    if ($empresaSelDefault !== '') {
        $almacDefault = db_one("
            SELECT id
            FROM c_almacenp
            WHERE COALESCE(Activo,'S') <> 'N'
              AND cve_cia = ?
            ORDER BY id
            LIMIT 1
        ", [$empresaSelDefault]);
    } else {
        $almacDefault = db_one("
            SELECT id
            FROM c_almacenp
            WHERE COALESCE(Activo,'S') <> 'N'
            ORDER BY id
            LIMIT 1
        ");
    }
    if ($almacDefault && isset($almacDefault['id'])) {
        $_GET['almacen']      = (string)$almacDefault['id'];
        $almacenSelDefault    = $_GET['almacen'];
    }
}

// 2.2.3 Zona (c_almacen) default según almacén padre
if ($zonaSelDefault === '' && $almacenSelDefault !== '') {
    $zonaDefault = db_one("
        SELECT cve_almac
        FROM c_almacen
        WHERE COALESCE(Activo,'S') <> 'N'
          AND cve_almacenp = ?
        ORDER BY cve_almac
        LIMIT 1
    ", [$almacenSelDefault]);
    if ($zonaDefault && isset($zonaDefault['cve_almac'])) {
        $_GET['zona']   = (string)$zonaDefault['cve_almac'];
        $zonaSelDefault = $_GET['zona'];
    }
}

// ------------------------------
// 3. Leer valores de filtros desde $_GET
// ------------------------------
$empresaSel    = $_GET['empresa']    ?? '';
$almacenSel    = $_GET['almacen']    ?? '';
$zonaSel       = $_GET['zona']       ?? '';
$blSel         = $_GET['bl']         ?? '';
$lpSel         = $_GET['lp']         ?? '';
$prodSel       = $_GET['producto']   ?? '';
$loteSel       = $_GET['lote']       ?? '';
$rutaSel       = $_GET['ruta']       ?? '';
$clienteSel    = $_GET['cliente']    ?? '';
$proveedorSel  = $_GET['proveedor']  ?? '';
$vendedorSel   = $_GET['vendedor']   ?? '';
$usuarioSel    = $_GET['usuario']    ?? '';
$zonaRecepSel  = $_GET['zona_recep'] ?? '';
$zonaQASel     = $_GET['zona_qa']    ?? '';
$zonaEmbSel    = $_GET['zona_emb']   ?? '';
$proyectoSel   = $_GET['proyecto']   ?? '';
$ubicaMfgSel   = $_GET['ubica_mfg']  ?? '';

$useEmpresa    = ($_GET['use_empresa']    ?? '1') === '1';
$useAlmacen    = ($_GET['use_almacen']    ?? '1') === '1';
$useZona       = ($_GET['use_zona']       ?? '1') === '1';
$useBL         = ($_GET['use_bl']         ?? '1') === '1';
$useLP         = ($_GET['use_lp']         ?? '1') === '1';
$useProd       = ($_GET['use_producto']   ?? '1') === '1';
$useLote       = ($_GET['use_lote']       ?? '1') === '1';
$useRuta       = ($_GET['use_ruta']       ?? '1') === '1';
$useCliente    = ($_GET['use_cliente']    ?? '1') === '1';
$useProv       = ($_GET['use_proveedor']  ?? '1') === '1';
$useVend       = ($_GET['use_vendedor']   ?? '1') === '1';
$useUsuario    = ($_GET['use_usuario']    ?? '1') === '1';
$useZonaRecep  = ($_GET['use_zona_recep'] ?? '1') === '1';
$useZonaQA     = ($_GET['use_zona_qa']    ?? '1') === '1';
$useZonaEmb    = ($_GET['use_zona_emb']   ?? '1') === '1';
$useProyecto   = ($_GET['use_proyecto']   ?? '1') === '1';
$useUbicaMfg   = ($_GET['use_ubica_mfg']  ?? '1') === '1';

// ------------------------------
// 4. Helper para detectar columnas de la vista dinámicamente
// ------------------------------
function ap_pick_col(array $fields, array $exact = [], array $contains = []): ?string {
    foreach ($exact as $ex) {
        foreach ($fields as $f) {
            if (strcasecmp($f, $ex) === 0) {
                return $f;
            }
        }
    }
    foreach ($contains as $pat) {
        foreach ($fields as $f) {
            if (stripos($f, $pat) !== false) {
                return $f;
            }
        }
    }
    return null;
}

// ------------------------------
// 5. Construir consulta a v_existencias_por_ubicacion_ao
// ------------------------------
$rows      = [];
$kpis      = [
    'total_registros' => 0,
    'total_lp'        => 0,
    'total_ubic'      => 0,
    'total_prod'      => 0,
    'total_exist'     => 0.0,
];
$errorMsg  = '';
$campos    = [];
$colsVista = [];

try {
    // 5.1. Estructura de la vista
    $colsVista = db_all("DESCRIBE v_existencias_por_ubicacion_ao");
    $campos    = array_map(static fn($r) => (string)$r['Field'], $colsVista);

    // 5.2. Mapear columnas
    $colEmpresa  = ap_pick_col($campos, ['empresa_id','cve_cia','empresa_clave'], ['empresa']);
    $colZona     = ap_pick_col($campos, ['cve_almac','id_almacen'], ['almac']);
    $colBL       = ap_pick_col($campos, ['bl','CodigoCSD','codigocsd'], ['bin','ubic','csd']);
    $colLP       = ap_pick_col($campos, ['lp','CveLP','license_plate'], ['lp']);
    $colProd     = ap_pick_col($campos, ['cve_articulo','sku'], ['cve_art','producto','sku']);
    $colLote     = ap_pick_col($campos, ['lote','cve_lote'], ['lote','serie']);
    $colProyecto = ap_pick_col($campos, ['id_proyecto','proyecto_id','Cve_Proyecto'], ['proy']);
    $colExist    = ap_pick_col($campos, ['existencia','existencia_total','cantidad'], ['exist','cant']);

    // 5.3. WHERE dinámico
    $where  = " WHERE 1=1 ";
    $params = [];

    // Filtro EMPRESA (c_compania / cve_cia / empresa_id)
    if ($useEmpresa && $empresaSel !== '' && $colEmpresa !== null) {
        $where   .= " AND `$colEmpresa` = ? ";
        $params[] = $empresaSel;
    }

    // Filtro ZONA (c_almacen / cve_almac)
    if ($useZona && $zonaSel !== '' && $colZona !== null) {
        $where   .= " AND `$colZona` = ? ";
        $params[] = $zonaSel;
    }

    // BL
    if ($useBL && $blSel !== '' && $colBL !== null) {
        $where   .= " AND `$colBL` = ? ";
        $params[] = $blSel;
    }

    // LP
    if ($useLP && $lpSel !== '' && $colLP !== null) {
        $where   .= " AND `$colLP` = ? ";
        $params[] = $lpSel;
    }

    // Producto
    if ($useProd && $prodSel !== '' && $colProd !== null) {
        $where   .= " AND `$colProd` = ? ";
        $params[] = $prodSel;
    }

    // Lote / Serie
    if ($useLote && $loteSel !== '' && $colLote !== null) {
        $where   .= " AND `$colLote` = ? ";
        $params[] = $loteSel;
    }

    // Proyecto (si en algún momento se agrega)
    if ($useProyecto && $proyectoSel !== '' && $colProyecto !== null) {
        $where   .= " AND `$colProyecto` = ? ";
        $params[] = $proyectoSel;
    }

    // 5.4. Ejecutar consulta (máx 100 filas)
    $sql = "
        SELECT *
        FROM v_existencias_por_ubicacion_ao
        $where
        LIMIT 100
    ";

    $rows = db_all($sql, $params);

    // 5.5. KPIs
    $kpis['total_registros'] = count($rows);

    $setLP   = [];
    $setBL   = [];
    $setProd = [];

    foreach ($rows as $r) {
        if ($colLP !== null && isset($r[$colLP]) && $r[$colLP] !== '') {
            $setLP[(string)$r[$colLP]] = true;
        }
        if ($colBL !== null && isset($r[$colBL]) && $r[$colBL] !== '') {
            $setBL[(string)$r[$colBL]] = true;
        }
        if ($colProd !== null && isset($r[$colProd]) && $r[$colProd] !== '') {
            $setProd[(string)$r[$colProd]] = true;
        }
        if ($colExist !== null && isset($r[$colExist]) && is_numeric($r[$colExist])) {
            $kpis['total_exist'] += (float)$r[$colExist];
        }
    }

    $kpis['total_lp']   = count($setLP);
    $kpis['total_ubic'] = count($setBL);
    $kpis['total_prod'] = count($setProd);

} catch (Throwable $e) {
    $errorMsg = 'Error consultando v_existencias_por_ubicacion_ao: '.$e->getMessage();
}

// ------------------------------
// 6. Render UI
// ------------------------------
?>
<div class="container-fluid" style="font-size:11px;">

    <!-- Título -->
    <div class="row mt-2">
        <div class="col-12">
            <h5>Existencias por ubicación</h5>
            <p class="text-muted mb-1">
                Vista base: <code>v_existencias_por_ubicacion_ao</code><br>
                Plantilla aplicada: <code><?= htmlspecialchars($nombrePlantillaFija, ENT_QUOTES, 'UTF-8') ?></code> (fija)
            </p>
        </div>
    </div>

    <!-- Panel maestro de filtros -->
    <div class="row mb-2">
        <div class="col-12">
            <?php
            require_once __DIR__ . '/./partials/filtros_assistpro.php';
            ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-2">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm" style="font-size:11px;">
                <div class="card-body py-2">
                    <div class="fw-bold">Registros consultados</div>
                    <div style="font-size:18px;">
                        <?= number_format($kpis['total_registros']) ?>
                    </div>
                    <small class="text-muted">Máximo 100 filas por consulta</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm" style="font-size:11px;">
                <div class="card-body py-2">
                    <div class="fw-bold">License Plates distintos</div>
                    <div style="font-size:18px;">
                        <?= number_format($kpis['total_lp']) ?>
                    </div>
                    <small class="text-muted">Detectados en el resultado</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm" style="font-size:11px;">
                <div class="card-body py-2">
                    <div class="fw-bold">Ubicaciones (BL) distintas</div>
                    <div style="font-size:18px;">
                        <?= number_format($kpis['total_ubic']) ?>
                    </div>
                    <small class="text-muted">BL de la vista</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm" style="font-size:11px;">
                <div class="card-body py-2">
                    <div class="fw-bold">Productos distintos</div>
                    <div style="font-size:18px;">
                        <?= number_format($kpis['total_prod']) ?>
                    </div>
                    <small class="text-muted">Por clave de artículo</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Error -->
    <?php if ($errorMsg !== ''): ?>
        <div class="row mb-2">
            <div class="col-12">
                <div class="alert alert-danger py-1" style="font-size:11px;">
                    <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Grilla -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-1" style="font-size:11px;">
                    <strong>Detalle de existencias por ubicación</strong>
                    <span class="text-muted">
                        (se muestran hasta 25 filas de un máximo de 100 consultadas)
                    </span>
                </div>
                <div class="card-body p-1" style="font-size:10px;">
                    <div style="max-height:400px; overflow:auto;">
                        <?php if (empty($rows)): ?>
                            <span class="text-muted">No hay registros para los filtros seleccionados.</span>
                        <?php else: ?>
                            <table class="table table-sm table-striped table-hover mb-0" style="font-size:10px;">
                                <thead>
                                <tr>
                                    <?php foreach ($campos as $c): ?>
                                        <th><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $maxMostrar = 25;
                                $i = 0;
                                foreach ($rows as $r):
                                    if ($i >= $maxMostrar) {
                                        break;
                                    }
                                    $i++;
                                ?>
                                    <tr>
                                        <?php foreach ($campos as $c): ?>
                                            <td><?= htmlspecialchars((string)($r[$c] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($rows) && count($rows) > $maxMostrar): ?>
                        <small class="text-muted">
                            Se consultaron <?= number_format(count($rows)) ?> filas; se muestran solo las primeras <?= $maxMostrar ?>.
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
