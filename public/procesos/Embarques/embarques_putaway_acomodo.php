<?php
/* ===========================================================
   public/procesos/putaway_acomodo.php
   Acomodo / Traslado / CrossDocking - Pendientes
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';
session_start();

/* ======================== Frame ========================== */
$activeSection = 'procesos';
$activeItem    = 'putaway_acomodo';
$pageTitle     = 'Put Away · Acomodo / Traslado / XD';
include __DIR__ . '/../bi/_menu_global.php';

/* ================== Sesión / filtros ===================== */
$cve_usuario = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? 'SISTEMA');

$almacen_sel        = isset($_GET['almacen'])      ? trim($_GET['almacen'])      : '';
$zona_recibo_sel    = isset($_GET['zona_recibo'])  ? trim($_GET['zona_recibo'])  : '';
$bl_origen_sel      = isset($_GET['bl_origen'])    ? trim($_GET['bl_origen'])    : '';
$bl_destino_sel     = isset($_GET['bl_destino'])   ? trim($_GET['bl_destino'])   : '';
$zona_embarque_sel  = isset($_GET['zona_embarque'])? trim($_GET['zona_embarque']): '';
$folio_sel          = isset($_GET['folio_sel'])    ? trim($_GET['folio_sel'])    : '';

$lp_filtro       = isset($_GET['lp'])          ? trim($_GET['lp'])          : '';
$clave_filtro    = isset($_GET['clave'])       ? trim($_GET['clave'])       : '';
$lote_filtro     = isset($_GET['lote_serie'])  ? trim($_GET['lote_serie'])  : '';

$modos_validos = ['ACOMODO', 'TRASLADO', 'XD'];
$modo = isset($_GET['modo']) ? strtoupper(trim($_GET['modo'])) : 'ACOMODO';
if (!in_array($modo, $modos_validos)) {
    $modo = 'ACOMODO';
}

/* ================== Catálogos ============================ */

/* Almacenes */
$cat_almac = db_all("
  SELECT id, clave
  FROM c_almacenp
  WHERE COALESCE(Activo,1) = 1
  ORDER BY clave
");

/* Zonas de recepción (zona origen para ACOMODO/XD) */
$cat_zonas_recibo = db_all("
  SELECT r.cve_ubicacion,
         r.desc_ubicacion,
         a.clave AS cve_almac
  FROM tubicacionesretencion r
  LEFT JOIN c_almacenp a ON a.id = r.cve_almacp
  WHERE COALESCE(r.Activo,1) = 1
  ORDER BY a.clave, r.cve_ubicacion
");

/* BLs (ubicaciones de almacenaje) para origen/destino */
$cat_ubicaciones = db_all("
  SELECT u.idy_ubica,
         u.Ubicacion,
         u.Seccion,
         u.cve_pasillo,
         u.cve_rack,
         a.clave AS cve_almac
  FROM c_ubicacion u
  JOIN c_almacenp a ON a.id = u.cve_almac
  WHERE COALESCE(u.Activo,1) = 1
  ORDER BY a.clave, u.Ubicacion
");

/* Zonas de embarque (XD) */
$cat_zonas_embarque = db_all("
  SELECT e.cve_ubicacion,
         e.descripcion,
         e.AreaStagging,
         a.clave AS cve_almac
  FROM t_ubicacionembarque e
  JOIN c_almacenp a ON a.id = e.cve_almac
  WHERE COALESCE(e.Activo,1) = 1
  ORDER BY a.clave, e.cve_ubicacion
");

/* Helpers combos */
function render_options_almacen(array $rows, string $selected = ''): string {
    $html = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $clave = trim($r['clave']);
        $sel   = ($selected !== '' && $selected === $clave) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($clave) . '"' . $sel . '>'
            .   htmlspecialchars($clave)
            . '</option>';
    }
    return $html;
}

function render_options_zonas(array $rows, string $selected = '', string $almacen_sel = ''): string {
    $html = '<option value="">Seleccione</option>';
    $almacen_sel = trim($almacen_sel);

    foreach ($rows as $r) {
        $cve_ubi  = trim($r['cve_ubicacion']);
        $desc     = trim($r['desc_ubicacion'] ?? '');
        $cve_alm  = trim($r['cve_almac'] ?? '');

        if ($almacen_sel !== '' && $cve_alm !== '' && $cve_alm !== $almacen_sel) {
            continue;
        }

        $sel  = ($selected !== '' && $selected === $cve_ubi) ? ' selected' : '';
        $text = $cve_ubi . ($desc !== '' ? ' - ' . $desc : '');
        $html .= '<option value="' . htmlspecialchars($cve_ubi) . '"' . $sel . '>'
            .   htmlspecialchars($text)
            . '</option>';
    }

    return $html;
}

/**
 * BL destino / origen
 *
 * Aquí es donde después filtraremos para ACOMODO:
 *  1) Acomodo mixto
 *  2) Ubicación a piso
 *  3) ABC
 * cuando tengamos claros los nombres de columna en c_ubicacion.
 */
function render_options_bl(array $rows, string $selected = '', string $almacen_sel = ''): string {
    $html = '<option value="">Seleccione</option>';
    $almacen_sel = trim($almacen_sel);

    foreach ($rows as $r) {
        $ubi   = trim($r['Ubicacion'] ?? '');
        $sec   = trim($r['Seccion'] ?? '');
        $pas   = trim($r['cve_pasillo'] ?? '');
        $rack  = trim($r['cve_rack'] ?? '');
        $cve_alm = trim($r['cve_almac'] ?? '');

        if ($almacen_sel !== '' && $cve_alm !== '' && $cve_alm !== $almacen_sel) {
            continue;
        }
        if ($ubi === '') continue;

        $labelParts = [];
        if ($sec !== '') $labelParts[] = $sec;
        if ($pas !== '') $labelParts[] = 'P:' . $pas;
        if ($rack !== '') $labelParts[] = 'R:' . $rack;
        $label = $ubi;
        if (!empty($labelParts)) {
            $label .= ' [' . implode(' ', $labelParts) . ']';
        }

        $sel  = ($selected !== '' && $selected === $ubi) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($ubi) . '"' . $sel . '>'
            .   htmlspecialchars($label)
            . '</option>';
    }

    return $html;
}

/**
 * Zonas de embarque (XD)
 * Solo zonas del mismo almacén con AreaStagging = 'S'
 */
function render_options_embarque(array $rows, string $selected = '', string $almacen_sel = ''): string {
    $html = '<option value="">Seleccione</option>';
    $almacen_sel = trim($almacen_sel);

    foreach ($rows as $r) {
        $cve_ubi  = trim($r['cve_ubicacion']);
        $desc     = trim($r['descripcion'] ?? '');
        $cve_alm  = trim($r['cve_almac'] ?? '');
        $stag     = trim($r['AreaStagging'] ?? '');

        // mismo almacén
        if ($almacen_sel !== '' && $cve_alm !== '' && $cve_alm !== $almacen_sel) {
            continue;
        }

        // Solo stagging
        if ($stag !== 'S') {
            continue;
        }

        $sel  = ($selected !== '' && $selected === $cve_ubi) ? ' selected' : '';
        $text = $cve_ubi . ($desc !== '' ? ' - ' . $desc : '');
        $text .= ' [Stagging]';

        $html .= '<option value="' . htmlspecialchars($cve_ubi) . '"' . $sel . '>'
            .   htmlspecialchars($text)
            . '</option>';
    }

    return $html;
}

/* ================== Consulta pendientes ================== */

$folios_resumen   = [];  // lista de folios con pendiente (solo ACOMODO/XD)
$lineas_detalle   = [];  // detalle del folio seleccionado
$total_unidades   = 0.0;
$error_msg        = '';
$limite_rows      = 500;  // límite de seguridad

// SOLO ACOMODO y XD consultan pendientes de acomodo.
// TRASLADO nunca carga nada de v_pendientesacomodo (es solo acción de mover).
if (($modo === 'ACOMODO' || $modo === 'XD') && $almacen_sel !== '' && $zona_recibo_sel !== '') {

    try {
        // Base de FROM/WHERE compartida para resumen y detalle
        $fromBase = "
          FROM v_pendientesacomodo p
          INNER JOIN td_entalmacen td
             ON TRIM(td.cve_articulo) = TRIM(p.cve_articulo)
            AND IFNULL(TRIM(td.cve_lote),'') = IFNULL(TRIM(p.Cve_Lote),'')
            AND TRIM(td.cve_ubicacion) = TRIM(p.Cve_Ubicacion)
          INNER JOIN th_entalmacen th
             ON th.empresa_id = td.empresa_id
            AND th.Fol_Folio  = td.fol_folio
          LEFT JOIN c_articulo a
             ON TRIM(a.cve_articulo) = TRIM(td.cve_articulo)
        ";

        $whereBase = "
          WHERE TRIM(th.Cve_Almac)    = TRIM(:almac)
            AND TRIM(p.Cve_Ubicacion) = TRIM(:zona)
            AND (td.CantidadUbicada+0) < (td.CantidadRecibida+0)
        ";

        $paramsBase = [
            ':almac' => $almacen_sel,
            ':zona'  => $zona_recibo_sel,
        ];

        // --------- RESUMEN POR FOLIO ----------
        $sqlResumen = "
          SELECT
            th.Fol_Folio                          AS folio_entrada,
            td.tipo_entrada                       AS tipo_entrada,
            th.Proyecto                           AS proyecto,
            SUM( (td.CantidadRecibida+0) - (td.CantidadUbicada+0) ) AS cant_pendiente,
            COUNT(*)                              AS num_lineas
          $fromBase
          $whereBase
          GROUP BY th.Fol_Folio, td.tipo_entrada, th.Proyecto
          ORDER BY th.Fol_Folio DESC
          LIMIT {$limite_rows}
        ";

        $folios_resumen = db_all($sqlResumen, $paramsBase);

        // --------- DETALLE DEL FOLIO SELECCIONADO ----------
        if ($folio_sel !== '') {

            $sqlDetalle = "
              SELECT
                th.Cve_Almac,
                th.Fol_Folio                          AS folio_entrada,
                td.tipo_entrada                       AS tipo_entrada,
                th.Proyecto                           AS proyecto,
                td.cve_articulo,
                a.des_articulo,
                td.cve_lote,
                td.cve_ubicacion                      AS bl_origen,
                (td.CantidadRecibida+0)               AS cant_recibida,
                (td.CantidadUbicada+0)                AS cant_ubicada,
                (td.CantidadRecibida+0) - (td.CantidadUbicada+0) AS cant_pendiente,
                NULL AS lp,
                NULL AS pallet
              $fromBase
              $whereBase
                AND th.Fol_Folio = :folio
            ";

            $paramsDetalle           = $paramsBase;
            $paramsDetalle[':folio'] = $folio_sel;

            if ($clave_filtro !== '') {
                $sqlDetalle .= " AND TRIM(td.cve_articulo) = TRIM(:cve_art) ";
                $paramsDetalle[':cve_art'] = $clave_filtro;
            }
            if ($lote_filtro !== '') {
                $sqlDetalle .= " AND TRIM(td.cve_lote) = TRIM(:lote) ";
                $paramsDetalle[':lote'] = $lote_filtro;
            }
            if ($lp_filtro !== '') {
                $sqlDetalle .= " AND 1 = 0 ";
            }

            $sqlDetalle .= "
              ORDER BY td.cve_articulo, td.cve_lote
            ";

            $lineas_detalle = db_all($sqlDetalle, $paramsDetalle);

            foreach ($lineas_detalle as $r) {
                $total_unidades += (float)$r['cant_pendiente'];
            }
        }

    } catch (Exception $e) {
        $error_msg = 'Ocurrió un problema al consultar los pendientes. '
            . 'Ajusta los filtros (almacén / zona) o vuelve a intentar. '
            . 'Detalle técnico: ' . $e->getMessage();
        $folios_resumen = [];
        $lineas_detalle = [];
        $total_unidades = 0.0;
    }
}

?>
    <style>
        #loadingOverlay {
            position: fixed;
            z-index: 2000;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.7);
            display: none;
            align-items: center;
            justify-content: center;
        }
    </style>

    <div id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border" role="status"></div>
            <div class="mt-2" style="font-size:12px;">Procesando, por favor espera...</div>
        </div>
    </div>

    <div class="container-fluid" style="font-size:10px;">

        <div class="row mb-2">
            <div class="col-12">
                <h5 class="mt-2 mb-1" style="font-weight:600;">Put Away · Acomodo / Traslado / CrossDocking</h5>
            </div>
        </div>

        <?php if ($error_msg !== ''): ?>
            <div class="row mb-2">
                <div class="col-12">
                    <div class="alert alert-warning alert-sm">
                        <?= htmlspecialchars($error_msg) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtros superiores -->
        <form method="get"
              class="row g-2 mb-3"
              id="frmFiltrosPrincipal"
              onsubmit="document.getElementById('loadingOverlay').style.display='flex';">

            <input type="hidden" name="modo" id="hdnModo" value="<?= htmlspecialchars($modo) ?>">
            <input type="hidden" name="folio_sel" value="<?= htmlspecialchars($folio_sel) ?>">

            <div class="col-md-4">
                <label class="form-label mb-0">Almacén*</label>
                <select name="almacen"
                        class="form-select form-select-sm"
                        onchange="document.getElementById('frmFiltrosPrincipal').submit();">
                    <?= render_options_almacen($cat_almac, $almacen_sel) ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label mb-0">Usuario*</label>
                <input type="text"
                       class="form-control form-control-sm"
                       value="<?= htmlspecialchars($cve_usuario) ?>"
                       readonly>
            </div>

            <!-- Radios excluyentes -->
            <div class="col-md-4">
                <label class="form-label mb-0">Tipo de Movimiento</label>
                <div class="d-flex flex-row flex-wrap mt-1">
                    <div class="form-check me-3">
                        <input class="form-check-input modo-mov" type="radio"
                               name="modo_radio" id="rdoAcomodo" value="ACOMODO"
                            <?= $modo === 'ACOMODO' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rdoAcomodo">Acomodo</label>
                    </div>
                    <div class="form-check me-3">
                        <input class="form-check-input modo-mov" type="radio"
                               name="modo_radio" id="rdoTraslado" value="TRASLADO"
                            <?= $modo === 'TRASLADO' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rdoTraslado">Traslado</label>
                    </div>
                    <div class="form-check me-3">
                        <input class="form-check-input modo-mov" type="radio"
                               name="modo_radio" id="rdoXD" value="XD"
                            <?= $modo === 'XD' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rdoXD">CrossDocking</label>
                    </div>
                </div>
                <small class="text-muted">Solo uno puede estar activo</small>
            </div>

            <?php
            // visibilidad server-side
            $cl_zona_origen   = ($modo === 'ACOMODO' || $modo === 'XD')          ? ''      : 'd-none';
            $cl_bl_origen     = ($modo === 'TRASLADO')                           ? ''      : 'd-none';
            // BL Destino se usa en ACOMODO y TRASLADO
            $cl_bl_destino    = ($modo === 'ACOMODO' || $modo === 'TRASLADO')    ? ''      : 'd-none';
            $cl_zona_embarque = ($modo === 'XD')                                 ? ''      : 'd-none';
            ?>

            <!-- Zona Origen (Acomodo / XD) -->
            <div class="col-md-4 mt-2 <?= $cl_zona_origen ?>" id="grpZonaOrigen">
                <label class="form-label mb-0">Zona Origen</label>
                <div class="form-group">
                    <label class="mb-0" style="font-size:9px;">Zona de Recepción</label>
                    <select name="zona_recibo"
                            id="cmbZonaRecibo"
                            class="form-select form-select-sm"
                            onchange="document.getElementById('frmFiltrosPrincipal').submit();">
                        <?= render_options_zonas($cat_zonas_recibo, $zona_recibo_sel, $almacen_sel) ?>
                    </select>
                </div>
            </div>

            <!-- BL Origen (Traslado) -->
            <div class="col-md-4 mt-2 <?= $cl_bl_origen ?>" id="grpBlOrigen">
                <label class="form-label mb-0">BL Origen</label>
                <select name="bl_origen" id="cmbBlOrigen" class="form-select form-select-sm">
                    <?= render_options_bl($cat_ubicaciones, $bl_origen_sel, $almacen_sel) ?>
                </select>
            </div>

            <!-- Zona Destino (BL Destino) - Acomodo / Traslado -->
            <div class="col-md-4 mt-2 <?= $cl_bl_destino ?>" id="grpBlDestino">
                <label class="form-label mb-0">Zona Destino (BL Destino)</label>
                <select name="bl_destino" id="cmbBlDestino" class="form-select form-select-sm">
                    <?= render_options_bl($cat_ubicaciones, $bl_destino_sel, $almacen_sel) ?>
                </select>
                <small class="text-muted">En Acomodo después filtraremos por Mixto / Piso / ABC.</small>
            </div>

            <!-- Zona Embarque Destino (XD) -->
            <div class="col-md-4 mt-2 <?= $cl_zona_embarque ?>" id="grpZonaEmbarque">
                <label class="form-label mb-0">Zona Embarque Destino</label>
                <select name="zona_embarque" id="cmbZonaEmbarque" class="form-select form-select-sm">
                    <?= render_options_embarque($cat_zonas_embarque, $zona_embarque_sel, $almacen_sel) ?>
                </select>
                <small class="text-muted">Solo zonas de embarque (Stagging='S') del mismo almacén.</small>
            </div>

            <!-- Ojo: ya no hay botón "Productos Pendientes": todo se dispara con los cambios -->

        </form>

        <!-- Cards resumen (del folio seleccionado) -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body py-2 text-center" style="background:#f5f5f5;">
                        <strong>Modo seleccionado</strong>
                        <div><?= htmlspecialchars($modo) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body py-2 text-center" style="background:#f5f5f5;">
                        <strong>Folio seleccionado</strong>
                        <div><?= $folio_sel !== '' ? htmlspecialchars($folio_sel) : 'Ninguno' ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body py-2 text-center" style="background:#f5f5f5;">
                        <strong>Pendiente folio | Cantidad</strong>
                        <div><?= number_format($total_unidades, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grilla de FOLIOS con pendiente (solo Acomodo/XD) -->
        <?php if ($modo === 'ACOMODO' || $modo === 'XD'): ?>
            <div class="row mb-2">
                <div class="col-12">
                    <h6>Folios con pendiente de acomodo</h6>
                    <div class="table-responsive">
                        <table id="tblFoliosPendientes" class="table table-striped table-bordered table-sm" style="width:100%;">
                            <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Folio Entrada</th>
                                <th>Tipo</th>
                                <th>Proyecto</th>
                                <th>Cant. Pendiente</th>
                                <th>Renglones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($folios_resumen as $row): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php
                                        $urlVer = 'putaway_acomodo.php'
                                            . '?modo='         . urlencode($modo)
                                            . '&almacen='      . urlencode($almacen_sel)
                                            . '&zona_recibo='  . urlencode($zona_recibo_sel)
                                            . '&folio_sel='    . urlencode($row['folio_entrada']);
                                        ?>
                                        <a href="<?= htmlspecialchars($urlVer) ?>" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($row['folio_entrada']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo_entrada']) ?></td>
                                    <td><?= htmlspecialchars($row['proyecto']) ?></td>
                                    <td class="text-end"><?= number_format($row['cant_pendiente'], 2) ?></td>
                                    <td class="text-end"><?= (int)$row['num_lineas'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">
                        Se listan folios con material pendiente de acomodo en la zona seleccionada.
                    </small>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtros de DETALLE (solo cuando hay folio seleccionado en Acomodo/XD) -->
        <?php if ($folio_sel !== '' && ($modo === 'ACOMODO' || $modo === 'XD')): ?>
            <form method="get"
                  class="row g-2 mb-2"
                  id="frmFiltrosGrilla"
                  onsubmit="document.getElementById('loadingOverlay').style.display='flex';">
                <input type="hidden" name="almacen"       value="<?= htmlspecialchars($almacen_sel) ?>">
                <input type="hidden" name="zona_recibo"   value="<?= htmlspecialchars($zona_recibo_sel) ?>">
                <input type="hidden" name="bl_origen"     value="<?= htmlspecialchars($bl_origen_sel) ?>">
                <input type="hidden" name="bl_destino"    value="<?= htmlspecialchars($bl_destino_sel) ?>">
                <input type="hidden" name="zona_embarque" value="<?= htmlspecialchars($zona_embarque_sel) ?>">
                <input type="hidden" name="modo"          value="<?= htmlspecialchars($modo) ?>">
                <input type="hidden" name="folio_sel"     value="<?= htmlspecialchars($folio_sel) ?>">

                <div class="col-md-3">
                    <label class="form-label mb-0">Buscar detalle:</label>
                    <input type="text" id="buscador_grilla"
                           class="form-control form-control-sm"
                           placeholder="Texto libre (DataTable)">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-0">LP:</label>
                    <input type="text" name="lp" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($lp_filtro) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-0">Clave:</label>
                    <input type="text" name="clave" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($clave_filtro) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-0">Lote | Serie:</label>
                    <input type="text" name="lote_serie" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($lote_filtro) ?>">
                </div>

                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-secondary btn-sm">Aplicar filtros al folio</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Grilla DETALLE del folio -->
        <?php if ($folio_sel !== '' && ($modo === 'ACOMODO' || $modo === 'XD')): ?>
            <div class="row">
                <div class="col-12">
                    <h6>Detalle del folio <?= htmlspecialchars($folio_sel) ?></h6>
                    <div class="table-responsive">
                        <table id="tblPendientesAcomodo" class="table table-striped table-bordered table-sm" style="width:100%;">
                            <thead>
                            <tr>
                                <th>Seleccionar</th>
                                <th>Folio Entrada</th>
                                <th>Tipo</th>
                                <th>Proyecto</th>
                                <th>Pallet / Contenedor</th>
                                <th>License Plate (LP)</th>
                                <th>Clave</th>
                                <th>Descripción</th>
                                <th>Lote</th>
                                <th>BL Origen</th>
                                <th>Cant. Recibida</th>
                                <th>Cant. Ubicada</th>
                                <th>Cant. Pendiente</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($lineas_detalle as $row): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" />
                                    </td>
                                    <td><?= htmlspecialchars($row['folio_entrada']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo_entrada']) ?></td>
                                    <td><?= htmlspecialchars($row['proyecto']) ?></td>
                                    <td><?= htmlspecialchars($row['pallet']) ?></td>
                                    <td><?= htmlspecialchars($row['lp']) ?></td>
                                    <td><?= htmlspecialchars($row['cve_articulo']) ?></td>
                                    <td><?= htmlspecialchars($row['des_articulo']) ?></td>
                                    <td><?= htmlspecialchars($row['cve_lote']) ?></td>
                                    <td><?= htmlspecialchars($row['bl_origen']) ?></td>
                                    <td class="text-end"><?= number_format($row['cant_recibida'], 2) ?></td>
                                    <td class="text-end"><?= number_format($row['cant_ubicada'], 2) ?></td>
                                    <td class="text-end"><?= number_format($row['cant_pendiente'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mt-3 mb-2">
            <div class="col-12 text-end">
                <small>Put Away · Acomodo / Traslado / XD · AssistPro</small>
            </div>
        </div>

    </div>

    <script>
        function applyModoUI(modo) {
            var grpZonaOrigen   = $('#grpZonaOrigen');
            var grpBlOrigen     = $('#grpBlOrigen');
            var grpBlDestino    = $('#grpBlDestino');
            var grpZonaEmbarque = $('#grpZonaEmbarque');

            var cmbZonaRecibo   = $('#cmbZonaRecibo');
            var cmbBlOrigen     = $('#cmbBlOrigen');
            var cmbBlDestino    = $('#cmbBlDestino');
            var cmbZonaEmbarque = $('#cmbZonaEmbarque');

            grpZonaOrigen.addClass('d-none');
            grpBlOrigen.addClass('d-none');
            grpBlDestino.addClass('d-none');
            grpZonaEmbarque.addClass('d-none');

            cmbZonaRecibo.prop('disabled', true);
            cmbBlOrigen.prop('disabled', true);
            cmbBlDestino.prop('disabled', true);
            cmbZonaEmbarque.prop('disabled', true);

            if (modo === 'ACOMODO') {
                // Acomodo: Zona origen + BL Destino
                grpZonaOrigen.removeClass('d-none');
                grpBlDestino.removeClass('d-none');
                cmbZonaRecibo.prop('disabled', false);
                cmbBlDestino.prop('disabled', false);
            } else if (modo === 'TRASLADO') {
                // Traslado: BL Origen + BL Destino (sin consultas de pendientes)
                grpBlOrigen.removeClass('d-none');
                grpBlDestino.removeClass('d-none');
                cmbBlOrigen.prop('disabled', false);
                cmbBlDestino.prop('disabled', false);
            } else if (modo === 'XD') {
                // XD: Zona origen + Zona embarque destino (solo stagging)
                grpZonaOrigen.removeClass('d-none');
                grpZonaEmbarque.removeClass('d-none');
                cmbZonaRecibo.prop('disabled', false);
                cmbZonaEmbarque.prop('disabled', false);
            }
        }

        $(document).ready(function () {
            // Tabla de folios (solo si existe)
            if ($('#tblFoliosPendientes').length) {
                $('#tblFoliosPendientes').DataTable({
                    pageLength: 25,
                    lengthChange: false,
                    ordering: true,
                    searching: true,
                    info: true,
                    scrollX: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
                    }
                });
            }

            // Tabla de detalle (solo si existe)
            if ($('#tblPendientesAcomodo').length) {
                var tablaDetalle = $('#tblPendientesAcomodo').DataTable({
                    pageLength: 25,
                    lengthChange: false,
                    ordering: true,
                    searching: true,
                    info: true,
                    scrollX: true,
                    scrollY: '360px',
                    scrollCollapse: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
                    }
                });

                $('#buscador_grilla').on('keyup change', function () {
                    tablaDetalle.search(this.value).draw();
                });
            }

            // radios
            $('.modo-mov').on('change', function () {
                var modoSel = $('.modo-mov:checked').val() || 'ACOMODO';
                $('#hdnModo').val(modoSel);
                // al cambiar de modo se limpia folio seleccionado
                $('input[name="folio_sel"]').val('');
                applyModoUI(modoSel);
                document.getElementById('loadingOverlay').style.display = 'flex';
                document.getElementById('frmFiltrosPrincipal').submit();
            });

            var modoActual = $('#hdnModo').val() || 'ACOMODO';
            applyModoUI(modoActual);
        });
    </script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
