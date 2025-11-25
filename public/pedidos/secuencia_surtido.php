<?php
// public/pedidos/secuencia_surtido.php
// Administrador de Secuencias de Surtido - AssistPro Kardex FC

require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ================== Filtros base ================== */
$almacenSel = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$criterio   = isset($_GET['q']) ? trim($_GET['q']) : ''; // reservado por si después se usa

/* ===========================================================
   Descarga de layout CSV (plantilla de importación)
   =========================================================== */
if (isset($_GET['accion']) && $_GET['accion'] === 'layout') {

    $filename = 'layout_secuencia_surtido.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $out = fopen('php://output', 'w');

    // Encabezados del layout
    fputcsv($out, [
        'ALMACEN_CLAVE',     // c_almacenp.clave
        'CLAVE_SECUENCIA',   // c_secuencia_surtido.clave_sec
        'NOMBRE_SECUENCIA',  // c_secuencia_surtido.nombre
        'TIPO_SECUENCIA',    // PICKING / REABASTO / GLOBAL
        'PROCESO',           // VENTA / REABASTO / SURTIDO_INTERNO
        'BL',                // v_secuencia_surtido_detalle.bl (CodigoCSD)
        'ORDEN'              // v_secuencia_surtido_detalle.orden
    ]);

    fclose($out);
    exit;
}

/* ===========================================================
   Exportación CSV con mismo layout que import
   =========================================================== */
if (isset($_GET['accion']) && $_GET['accion'] === 'export') {

    $filename = 'secuencia_surtido_export.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $out = fopen('php://output', 'w');

    // Encabezados (mismo layout que import)
    fputcsv($out, [
        'ALMACEN_CLAVE',
        'CLAVE_SECUENCIA',
        'NOMBRE_SECUENCIA',
        'TIPO_SECUENCIA',
        'PROCESO',
        'BL',
        'ORDEN'
    ]);

    // Datos: c_secuencia_surtido + c_almacenp + v_secuencia_surtido_detalle
    $sqlExp = "
        SELECT
            ap.clave                      AS ALMACEN_CLAVE,
            s.clave_sec                   AS CLAVE_SECUENCIA,
            s.nombre                      AS NOMBRE_SECUENCIA,
            s.tipo_sec                    AS TIPO_SECUENCIA,
            s.proceso                     AS PROCESO,
            d.bl                          AS BL,
            d.orden                       AS ORDEN
        FROM c_secuencia_surtido s
        JOIN c_almacenp ap
              ON ap.id = s.almacen_id
        LEFT JOIN v_secuencia_surtido_detalle d
              ON d.sec_id = s.id
        WHERE s.activo = 1
    ";

    $paramsExp = [];
    if ($almacenSel !== '') {
        $sqlExp .= " AND ap.clave = :almacen_clave ";
        $paramsExp[':almacen_clave'] = $almacenSel;
    }
    if ($criterio !== '') {
        $sqlExp .= " AND (
            s.clave_sec LIKE :crit
            OR s.nombre LIKE :crit
            OR s.tipo_sec LIKE :crit
            OR s.proceso LIKE :crit
        )";
        $paramsExp[':crit'] = '%'.$criterio.'%';
    }

    $stmtExp = $pdo->prepare($sqlExp);
    $stmtExp->execute($paramsExp);

    while ($row = $stmtExp->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['ALMACEN_CLAVE'],
            $row['CLAVE_SECUENCIA'],
            $row['NOMBRE_SECUENCIA'],
            $row['TIPO_SECUENCIA'],
            $row['PROCESO'],
            $row['BL'],
            $row['ORDEN']
        ]);
    }

    fclose($out);
    exit;
}

/* ================== Catálogo de almacenes ================== */
$sqlAlm = "SELECT id, clave, nombre FROM c_almacenp ORDER BY clave";
$stmtAlm = $pdo->query($sqlAlm);
$almacenes = $stmtAlm->fetchAll(PDO::FETCH_ASSOC);

$almacenSelId = null;
foreach ($almacenes as $a) {
    if ($a['clave'] === $almacenSel) {
        $almacenSelId = (int)$a['id'];
        break;
    }
}

/* ================== Secuencias (vista) ================== */
$sql = "
    SELECT
        id,
        clave_sec,
        nombre,
        tipo_sec,
        proceso,
        almacen_id,
        almacen_clave,
        almacen_nombre,
        ubicaciones_asignadas,
        usuarios_asignados
    FROM v_secuencia_surtido_admin
    WHERE 1 = 1
";
$params = [];

if ($almacenSel !== '') {
    $sql .= " AND almacen_clave = :almacen_clave ";
    $params[':almacen_clave'] = $almacenSel;
}

if ($criterio !== '') {
    $sql .= " AND (
        clave_sec LIKE :crit
        OR nombre LIKE :crit
        OR tipo_sec LIKE :crit
        OR proceso LIKE :crit
        OR usuarios_asignados LIKE :crit
        OR almacen_nombre LIKE :crit
    )";
    $params[':crit'] = '%'.$criterio.'%';
}

$sql .= " ORDER BY id ASC";

$stmtSec = $pdo->prepare($sql);
$stmtSec->execute($params);
$secuencias = $stmtSec->fetchAll(PDO::FETCH_ASSOC);
$totalSecuencias = count($secuencias);

/* ================== Métricas de ubicaciones ================== */
$sqlTotalUb = "
    SELECT COUNT(*)
    FROM c_ubicacion u
    JOIN c_almacen a ON a.cve_almac = u.cve_almac
    JOIN c_almacenp ap ON ap.id = a.cve_almacenp
    WHERE 1 = 1
";
$paramsUb = [];
if ($almacenSel !== '') {
    $sqlTotalUb .= " AND ap.clave = :almacen_clave ";
    $paramsUb[':almacen_clave'] = $almacenSel;
}
$stmtTotalUb = $pdo->prepare($sqlTotalUb);
$stmtTotalUb->execute($paramsUb);
$totalUbicacionesPicking = (int)$stmtTotalUb->fetchColumn();

$sqlUbAsignadas = "
    SELECT COUNT(DISTINCT d.ubicacion_id)
    FROM c_secuencia_surtido_det d
    JOIN c_secuencia_surtido s ON s.id = d.sec_id
    JOIN c_almacenp ap ON ap.id = s.almacen_id
    WHERE d.activo = 1
      AND s.activo = 1
";
$paramsUbAsig = [];
if ($almacenSel !== '') {
    $sqlUbAsignadas .= " AND ap.clave = :almacen_clave ";
    $paramsUbAsig[':almacen_clave'] = $almacenSel;
}
$stmtUbAsig = $pdo->prepare($sqlUbAsignadas);
$stmtUbAsig->execute($paramsUbAsig);
$totalUbicacionesAsignadas = (int)$stmtUbAsig->fetchColumn();

$ubicacionesDisponibles = max(0, $totalUbicacionesPicking - $totalUbicacionesAsignadas);

/* ================== Usuarios distintos ================== */
$usuariosSet = [];
foreach ($secuencias as $s) {
    if (!empty($s['usuarios_asignados'])) {
        $lista = explode(',', $s['usuarios_asignados']);
        foreach ($lista as $u) {
            $u = trim($u);
            if ($u !== '') {
                $usuariosSet[$u] = true;
            }
        }
    }
}
$totalUsuariosDistintos = count($usuariosSet);

/* ================== Datos para modal de detalle ================== */
$sqlUbList = "
    SELECT
        u.idy_ubica,
        u.CodigoCSD,
        u.cve_pasillo,
        u.cve_rack,
        u.cve_nivel,
        u.Seccion,
        u.Ubicacion
    FROM c_ubicacion u
    JOIN c_almacen a ON a.cve_almac = u.cve_almac
    JOIN c_almacenp ap ON ap.id = a.cve_almacenp
    WHERE 1 = 1
";
$paramsUbList = [];
if ($almacenSel !== '') {
    $sqlUbList .= " AND ap.clave = :almacen_clave ";
    $paramsUbList[':almacen_clave'] = $almacenSel;
}
$sqlUbList .= " ORDER BY u.cve_pasillo, u.cve_rack, u.cve_nivel, u.Seccion, u.Ubicacion";
$stmtUb = $pdo->prepare($sqlUbList);
$stmtUb->execute($paramsUbList);
$ubicacionesPicking = $stmtUb->fetchAll(PDO::FETCH_ASSOC);

$sqlDet = "
    SELECT
        id,
        sec_id,
        ubicacion_id,
        orden,
        bl,
        pasillo,
        rack,
        nivel,
        seccion,
        posicion
    FROM v_secuencia_surtido_detalle
    ORDER BY sec_id, orden
";
$stmtDet = $pdo->query($sqlDet);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

$detPorSecuencia = [];
foreach ($detalles as $d) {
    $secId = (int)$d['sec_id'];
    if (!isset($detPorSecuencia[$secId])) {
        $detPorSecuencia[$secId] = [];
    }
    $detPorSecuencia[$secId][] = $d;
}
?>
<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid mt-3" style="font-size:10px;">
    <div class="row mb-3">
        <div class="col-12">
            <h5 class="mb-0 page-title-ss">
                <i class="fa fa-route me-2"></i>Secuencia de Surtido
            </h5>
            <small class="text-muted">
                Definición de secuencias internas de surtido por almacén, tipo y proceso.
            </small>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card card-summary shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="title">Secuencias de Surtido</div>
                    <div class="value"><?= $totalSecuencias; ?></div>
                    <div class="subtitle">
                        Configuradas <?= $almacenSel ? 'en el almacén seleccionado' : 'en todos los almacenes'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card card-summary shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="title">Ubicaciones Picking</div>
                    <div class="value"><?= $totalUbicacionesPicking; ?></div>
                    <div class="subtitle">
                        c_ubicacion<?= $almacenSel ? ' filtradas por almacén' : ''; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card card-summary shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="title">Ubicaciones disponibles</div>
                    <div class="value"><?= $ubicacionesDisponibles; ?></div>
                    <div class="subtitle">
                        Sin secuencia asignada (debería tender a 0)
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card card-summary shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="title">Usuarios distintos</div>
                    <div class="value"><?= $totalUsuariosDistintos; ?></div>
                    <div class="subtitle">
                        Con alguna secuencia asignada
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-6 col-sm-7">
            <label for="almacen" class="form-label mb-1">Almacén</label>
            <select name="almacen"
                    id="almacen"
                    class="form-select form-select-sm"
                    onchange="this.form.submit();">
                <option value="">[Todos]</option>
                <?php foreach ($almacenes as $a): ?>
                    <option value="<?= htmlspecialchars($a['clave']); ?>"
                        <?= $almacenSel === $a['clave'] ? 'selected' : ''; ?>>
                        (<?= htmlspecialchars($a['clave']); ?>) - <?= htmlspecialchars($a['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6 col-sm-5 d-flex justify-content-end">
            <div class="btn-toolbar mb-1" role="toolbar">
                <div class="btn-group btn-group-sm me-2" role="group">
                    <button type="button" class="btn btn-success" onclick="nuevaSecuencia();">
                        <i class="fa fa-plus-circle me-1"></i>Nueva secuencia
                    </button>
                </div>
                <div class="btn-group btn-group-sm me-2" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="abrirImportarSecuencia();">
                        <i class="fa fa-upload me-1"></i>Importar
                    </button>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" onclick="exportarSecuencias();">
                        <i class="fa fa-download me-1"></i>Exportar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Grilla -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="tablaSecuencias" class="table table-striped table-bordered table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width:130px;">Acciones</th>
                            <th style="width:50px;">ID</th>
                            <th style="width:90px;">Clave</th>
                            <th>Nombre</th>
                            <th style="width:90px;">Tipo</th>
                            <th style="width:110px;">Proceso</th>
                            <th style="width:90px;">Ubic. asignadas</th>
                            <th style="width:180px;">Usuarios asignados</th>
                            <th>Almacén</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($secuencias as $s): ?>
                            <tr
                                data-sec-id="<?= (int)$s['id']; ?>"
                                data-sec-clave="<?= htmlspecialchars($s['clave_sec']); ?>"
                                data-sec-nombre="<?= htmlspecialchars($s['nombre']); ?>"
                                data-sec-tipo="<?= htmlspecialchars($s['tipo_sec']); ?>"
                                data-sec-proceso="<?= htmlspecialchars($s['proceso']); ?>"
                                data-sec-alm="<?= htmlspecialchars($s['almacen_clave']); ?>"
                                data-sec-alm-nombre="<?= htmlspecialchars($s['almacen_nombre']); ?>"
                            >
                                <td class="text-center">
                                    <!-- Ver / previsualizar -->
                                    <button type="button"
                                            class="btn btn-xs btn-outline-info me-1"
                                            title="Visualizar secuencia"
                                            onclick="verDetalleSecuencia(this, false);">
                                        <i class="fa fa-search"></i>
                                    </button>
                                    <!-- Editar secuencia (detalle) -->
                                    <button type="button"
                                            class="btn btn-xs btn-outline-primary me-1"
                                            title="Editar secuencia (orden / ubicaciones)"
                                            onclick="verDetalleSecuencia(this, true);">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <!-- Asignar usuarios -->
                                    <button type="button"
                                            class="btn btn-xs btn-outline-warning me-1"
                                            title="Asignar usuarios"
                                            onclick="asignarUsuarios(this);">
                                        <i class="fa fa-user"></i>
                                    </button>
                                    <!-- Eliminar -->
                                    <button type="button"
                                            class="btn btn-xs btn-outline-danger"
                                            title="Eliminar (soft delete)"
                                            onclick="eliminarSecuencia(this);">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                                <td><?= (int)$s['id']; ?></td>
                                <td><?= htmlspecialchars($s['clave_sec']); ?></td>
                                <td><?= htmlspecialchars($s['nombre']); ?></td>
                                <td><?= htmlspecialchars($s['tipo_sec']); ?></td>
                                <td><?= htmlspecialchars($s['proceso']); ?></td>
                                <td class="text-center">
                                    <?= (int)($s['ubicaciones_asignadas'] ?? 0); ?>
                                </td>
                                <td class="text-truncate" style="max-width:220px;">
                                    <?= htmlspecialchars($s['usuarios_asignados'] ?? ''); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['almacen_clave']); ?>
                                    <?php if (!empty($s['almacen_nombre'])): ?>
                                        - <?= htmlspecialchars($s['almacen_nombre']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($secuencias)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    No hay secuencias que coincidan con los filtros actuales.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted">
                *La tabla se limita visualmente a 25 registros por página con scroll horizontal y vertical.
            </small>
        </div>
    </div>
</div>

<!-- Modal Detalle / Edición de Secuencia -->
<div class="modal fade" id="modalSecuencia" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="font-size:10px;">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="tituloModalDetalle">
            <i class="fa fa-stream me-2"></i>
            Secuencia de Surtido - Detalle
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="card mb-2 border-0 shadow-sm">
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="fw-bold">ID Secuencia</div>
                        <div id="sec_det_id"></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fw-bold">Clave</div>
                        <div id="sec_det_clave"></div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="fw-bold">Nombre</div>
                        <div id="sec_det_nombre"></div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-md-3 col-sm-6">
                        <div class="fw-bold">Tipo</div>
                        <div id="sec_det_tipo"></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fw-bold">Proceso</div>
                        <div id="sec_det_proceso"></div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="fw-bold">Almacén</div>
                        <div id="sec_det_almacen"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel doble -->
        <div class="row">
            <div class="col-md-6 mb-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Ubicaciones disponibles (picking)</span>
                            <div>
                                <button type="button"
                                        id="btnAddToSeq"
                                        class="btn btn-xs btn-outline-primary"
                                        onclick="agregarSeleccionadosASecuencia();">
                                    <i class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">
                            * Basado en c_ubicacion del almacén seleccionado.
                        </small>
                    </div>
                    <div class="card-body p-1">
                        <div class="table-responsive" style="max-height:45vh; overflow:auto;">
                            <table id="tblDisponibles" class="table table-sm table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:25px;"></th>
                                        <th>BL</th>
                                        <th>Pasillo</th>
                                        <th>Rack</th>
                                        <th>Nivel</th>
                                        <th>Sección</th>
                                        <th>Posición</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Secuencia actual (orden de visita)</span>
                            <div>
                                <button type="button"
                                        id="btnUp"
                                        class="btn btn-xs btn-outline-secondary"
                                        onclick="reordenarSecuenciaSeleccion('up');">
                                    <i class="fa fa-arrow-up"></i>
                                </button>
                                <button type="button"
                                        id="btnDown"
                                        class="btn btn-xs btn-outline-secondary"
                                        onclick="reordenarSecuenciaSeleccion('down');">
                                    <i class="fa fa-arrow-down"></i>
                                </button>
                                <button type="button"
                                        id="btnRemove"
                                        class="btn btn-xs btn-outline-danger"
                                        onclick="quitarDeSecuenciaSeleccion();">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">
                            * Secuencia final que seguirá el surtidor.
                        </small>
                    </div>
                    <div class="card-body p-1">
                        <div class="table-responsive" style="max-height:45vh; overflow:auto;">
                            <table id="tblSecuencia" class="table table-sm table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:25px;"></th>
                                        <th style="width:50px;">Orden</th>
                                        <th>BL</th>
                                        <th>Pasillo</th>
                                        <th>Rack</th>
                                        <th>Nivel</th>
                                        <th>Sección</th>
                                        <th>Posición</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" id="btnGuardarSec" class="btn btn-sm btn-primary" onclick="guardarOrdenSecuencia();">
            <i class="fa fa-save me-1"></i>Guardar secuencia
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nueva Secuencia -->
<div class="modal fade" id="modalNuevaSecuencia" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="font-size:10px;">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="tituloModalSecuencia">
          <i class="fa fa-plus-circle me-2"></i>Nueva Secuencia de Surtido
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevaSecuencia">
          <div class="row mb-2">
            <div class="col-md-4">
              <label class="form-label mb-1">Almacén</label>
              <input type="text"
                     class="form-control form-control-sm"
                     id="ns_almacen"
                     readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1">Clave de secuencia</label>
              <input type="text"
                     class="form-control form-control-sm"
                     id="ns_clave"
                     maxlength="50">
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1">Nombre</label>
              <input type="text"
                     class="form-control form-control-sm"
                     id="ns_nombre"
                     maxlength="150">
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-4">
              <label class="form-label mb-1">Tipo de secuencia</label>
              <select class="form-select form-select-sm" id="ns_tipo">
                <option value="">[Seleccione]</option>
                <option value="PICKING">PICKING</option>
                <option value="REABASTO">REABASTO</option>
                <option value="GLOBAL">GLOBAL</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1">Proceso</label>
              <select class="form-select form-select-sm" id="ns_proceso">
                <option value="">[Seleccione]</option>
                <option value="VENTA">VENTA</option>
                <option value="REABASTO">REABASTO</option>
                <option value="SURTIDO_INTERNO">SURTIDO INTERNO</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1">Comentarios (opcional)</label>
              <input type="text"
                     class="form-control form-control-sm"
                     id="ns_comentarios"
                     maxlength="255">
            </div>
          </div>

          <small class="text-muted">
            * Se guardará en <code>c_secuencia_surtido</code>; más adelante ligamos importadores masivos.
          </small>
        </form>
      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="guardarSecuenciaEncabezado();">
          <i class="fa fa-save me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Asignar Usuarios -->
<div class="modal fade" id="modalUsuarios" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="font-size:10px;">
      <div class="modal-header py-2">
        <h6 class="modal-title">
          <i class="fa fa-user me-2"></i>Usuarios asignados a secuencia
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="us_sec_id" value="">
        <div class="mb-2">
            <strong id="us_sec_info"></strong>
        </div>
        <div class="table-responsive" style="max-height:55vh; overflow:auto;">
            <table class="table table-sm table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px;" class="text-center">
                            <input type="checkbox" id="us_sel_all" onclick="toggleUsuariosTodos(this);">
                        </th>
                        <th style="width:80px;">Usuario</th>
                        <th>Nombre completo</th>
                    </tr>
                </thead>
                <tbody id="tblUsuarios"></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="guardarUsuariosSecuencia();">
          <i class="fa fa-save me-1"></i>Guardar usuarios
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Importar Secuencia -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="font-size:10px;">
      <div class="modal-header py-2">
        <h6 class="modal-title">
          <i class="fa fa-upload me-2"></i>Importar Secuencias desde CSV
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">
          1. Descarga el layout CSV, llena los datos y selecciona el archivo.
        </p>
        <p class="mb-2">
          2. Previsualiza el contenido y posteriormente registra la importación.
        </p>

        <div class="mb-2 d-flex align-items-center">
            <a href="?accion=layout" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fa fa-download me-1"></i>Descargar layout CSV
            </a>
            <input type="file" id="fileImport" class="form-control form-control-sm" accept=".csv">
        </div>

        <div class="mb-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="previsualizarImportacion();">
                <i class="fa fa-eye me-1"></i>Previsualizar
            </button>
        </div>

        <div class="table-responsive" style="max-height:40vh; overflow:auto;">
            <table class="table table-sm table-striped table-hover" id="tblPreviewImport">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Almacén</th>
                        <th>Clave</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Proceso</th>
                        <th>BL</th>
                        <th>Orden</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila de ejemplo se genera al abrir el modal -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="registrarImportacion();">
          <i class="fa fa-save me-1"></i>Registrar
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$jsUbicaciones = json_encode($ubicacionesPicking, JSON_UNESCAPED_UNICODE);
$jsDetPorSec   = json_encode($detPorSecuencia, JSON_UNESCAPED_UNICODE);
$jsAlmSel      = json_encode($almacenSel, JSON_UNESCAPED_UNICODE);
?>
<script>
const UBICACIONES_PICKING   = <?= $jsUbicaciones ?>;
const SEC_DETALLE           = <?= $jsDetPorSec ?>;
const ALMACEN_SELECCIONADO  = <?= $jsAlmSel ?>;
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#tablaSecuencias').DataTable({
        pageLength: 25,
        lengthChange: false,
        ordering: true,
        scrollX: true,
        scrollY: '50vh',
        scrollCollapse: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
});

let modalNuevaSecuencia = null;
let modalSecuencia = null;
let modalUsuarios = null;
let modalImportar = null;

/* =========================== Top buttons ============================ */
function nuevaSecuencia() {
    if (!ALMACEN_SELECCIONADO) {
        alert('Selecciona primero un almacén para crear una nueva Secuencia de Surtido.');
        return;
    }
    document.getElementById('tituloModalSecuencia').innerHTML =
        '<i class="fa fa-plus-circle me-2"></i>Nueva Secuencia de Surtido';
    document.getElementById('ns_almacen').value = ALMACEN_SELECCIONADO;
    document.getElementById('ns_clave').value = '';
    document.getElementById('ns_nombre').value = '';
    document.getElementById('ns_tipo').value = '';
    document.getElementById('ns_proceso').value = '';
    document.getElementById('ns_comentarios').value = '';

    if (!modalNuevaSecuencia) {
        modalNuevaSecuencia = new bootstrap.Modal(document.getElementById('modalNuevaSecuencia'));
    }
    modalNuevaSecuencia.show();
}

function exportarSecuencias() {
    const params = new URLSearchParams(window.location.search);
    params.set('accion', 'export');
    window.location.href = '?' + params.toString();
}

function abrirImportarSecuencia() {
    if (!modalImportar) {
        modalImportar = new bootstrap.Modal(document.getElementById('modalImportar'));
    }
    document.getElementById('fileImport').value = '';
    const tbody = document.querySelector('#tblPreviewImport tbody');
    tbody.innerHTML = `
        <tr>
            <td>1</td>
            <td>WH1</td>
            <td>WH1_SS1</td>
            <td>Secuencia Surtido WH1</td>
            <td>PICKING</td>
            <td>SURTIDO_INTERNO</td>
            <td>WH1-A-01-01</td>
            <td>1</td>
            <td>Fila de ejemplo (reemplazada al previsualizar)</td>
        </tr>
    `;
    modalImportar.show();
}

/* =================== Importar CSV: preview ==================== */
function previsualizarImportacion() {
    const fileInput = document.getElementById('fileImport');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Selecciona un archivo CSV para previsualizar.');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    fetch('../api/secuencia_surtido.php?action=preview_import', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) {
            let msg = 'Error en previsualización: ' + (resp.error || 'desconocido');
            if (resp.detalle && Array.isArray(resp.detalle)) {
                msg += '\n\nDetalle:\n- ' + resp.detalle.join('\n- ');
            }
            alert(msg);
            return;
        }

        const tbody = document.querySelector('#tblPreviewImport tbody');
        tbody.innerHTML = '';

        (resp.rows || []).forEach((row, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td>${row.almacen_clave}</td>
                <td>${row.clave_sec}</td>
                <td>${row.nombre}</td>
                <td>${row.tipo_sec}</td>
                <td>${row.proceso}</td>
                <td>${row.bl}</td>
                <td>${row.orden}</td>
                <td>${row.observacion}</td>
            `;
            if (row.observacion && row.observacion !== 'OK') {
                tr.classList.add('table-warning');
            }
            tbody.appendChild(tr);
        });

        if (!resp.rows || resp.rows.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td colspan="9" class="text-center text-muted">
                    El archivo no contiene filas de datos.
                </td>
            `;
            tbody.appendChild(tr);
        }
    })
    .catch(err => {
        alert('Error en previsualización: ' + err);
    });
}

/* =================== Importar CSV: registrar ==================== */
function registrarImportacion() {
    const fileInput = document.getElementById('fileImport');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Selecciona un archivo CSV para registrar la importación.');
        return;
    }

    if (!confirm('¿Deseas registrar la importación de secuencias de surtido con el archivo seleccionado?')) {
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    fetch('../api/secuencia_surtido.php?action=registrar_import', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) {
            let msg = 'Error al registrar importación: ' + (resp.error || 'desconocido');
            if (resp.detalle && Array.isArray(resp.detalle)) {
                msg += '\n\nDetalle:\n- ' + resp.detalle.join('\n- ');
            }
            alert(msg);
            return;
        }

        alert((resp.mensaje || 'Importación realizada.') +
              (resp.secuencias ? '\nSecuencias procesadas: ' + resp.secuencias : ''));
        if (modalImportar) modalImportar.hide();
        location.reload();
    })
    .catch(err => {
        alert('Error al registrar importación: ' + err);
    });
}

/* ====================== Modal Detalle / Edición ===================== */
function verDetalleSecuencia(btn, editable) {
    const tr = btn.closest('tr');
    const id      = parseInt(tr.dataset.secId || '0', 10);
    const clave   = tr.dataset.secClave || '';
    const nombre  = tr.dataset.secNombre || '';
    const tipo    = tr.dataset.secTipo || '';
    const proceso = tr.dataset.secProceso || '';
    const alm     = tr.dataset.secAlm || '';
    const almNom  = tr.dataset.secAlmNombre || '';

    document.getElementById('sec_det_id').textContent       = id;
    document.getElementById('sec_det_clave').textContent    = clave;
    document.getElementById('sec_det_nombre').textContent   = nombre;
    document.getElementById('sec_det_tipo').textContent     = tipo;
    document.getElementById('sec_det_proceso').textContent  = proceso;
    document.getElementById('sec_det_almacen').textContent  = alm + (almNom ? (' - ' + almNom) : '');

    // título según modo
    document.getElementById('tituloModalDetalle').innerHTML =
        editable
            ? '<i class="fa fa-edit me-2"></i>Editar Secuencia de Surtido'
            : '<i class="fa fa-search me-2"></i>Visualizar Secuencia de Surtido';

    // habilitar / deshabilitar botones al modo lectura
    document.getElementById('btnAddToSeq').disabled   = !editable;
    document.getElementById('btnUp').disabled         = !editable;
    document.getElementById('btnDown').disabled       = !editable;
    document.getElementById('btnRemove').disabled     = !editable;
    document.getElementById('btnGuardarSec').disabled = !editable;

    const tbDisp = document.querySelector('#tblDisponibles tbody');
    const tbSec  = document.querySelector('#tblSecuencia tbody');
    tbDisp.innerHTML = '';
    tbSec.innerHTML  = '';

    const detalleSec = SEC_DETALLE[id] || [];
    const usados = new Set();
    detalleSec.forEach(d => usados.add(String(d.ubicacion_id)));

    UBICACIONES_PICKING.forEach(u => {
        const uid = String(u.idy_ubica);
        if (!usados.has(uid)) {
            const trD = document.createElement('tr');
            trD.dataset.ubicacionId = uid;
            trD.innerHTML = `
                <td class="text-center">
                    <input type="checkbox" class="chk-disp" ${editable ? '' : 'disabled'}>
                </td>
                <td>${u.CodigoCSD}</td>
                <td>${u.cve_pasillo}</td>
                <td>${u.cve_rack}</td>
                <td>${u.cve_nivel}</td>
                <td>${u.Seccion}</td>
                <td>${u.Ubicacion}</td>
            `;
            tbDisp.appendChild(trD);
        }
    });

    detalleSec.forEach(d => {
        const trS = document.createElement('tr');
        trS.dataset.ubicacionId = String(d.ubicacion_id);
        trS.innerHTML = `
            <td class="text-center">
                <input type="radio" name="sec_sel" class="chk-sec" ${editable ? '' : 'disabled'}>
            </td>
            <td class="orden-col">${d.orden}</td>
            <td>${d.bl}</td>
            <td>${d.pasillo}</td>
            <td>${d.rack}</td>
            <td>${d.nivel}</td>
            <td>${d.seccion}</td>
            <td>${d.posicion}</td>
        `;
        tbSec.appendChild(trS);
    });

    recalcularOrdenSecuencia();

    if (!modalSecuencia) {
        modalSecuencia = new bootstrap.Modal(document.getElementById('modalSecuencia'));
    }
    modalSecuencia.show();
}

function agregarSeleccionadosASecuencia() {
    const tbDisp = document.querySelector('#tblDisponibles tbody');
    const tbSec  = document.querySelector('#tblSecuencia tbody');
    const rows = Array.from(tbDisp.querySelectorAll('tr'));

    rows.forEach(row => {
        const chk = row.querySelector('.chk-disp');
        if (chk && chk.checked) {
            chk.checked = false;
            const uid   = row.dataset.ubicacionId;
            const cells = row.querySelectorAll('td');
            const bl   = cells[1].textContent;
            const pas  = cells[2].textContent;
            const rack = cells[3].textContent;
            const niv  = cells[4].textContent;
            const sec  = cells[5].textContent;
            const pos  = cells[6].textContent;

            const newRow = document.createElement('tr');
            newRow.dataset.ubicacionId = uid;
            newRow.innerHTML = `
                <td class="text-center">
                    <input type="radio" name="sec_sel" class="chk-sec">
                </td>
                <td class="orden-col"></td>
                <td>${bl}</td>
                <td>${pas}</td>
                <td>${rack}</td>
                <td>${niv}</td>
                <td>${sec}</td>
                <td>${pos}</td>
            `;
            tbSec.appendChild(newRow);
            tbDisp.removeChild(row);
        }
    });
    recalcularOrdenSecuencia();
}

function quitarDeSecuenciaSeleccion() {
    const tbSec = document.querySelector('#tblSecuencia tbody');
    const rows  = Array.from(tbSec.querySelectorAll('tr'));
    let removed = false;
    rows.forEach(row => {
        const chk = row.querySelector('.chk-sec');
        if (chk && chk.checked) {
            tbSec.removeChild(row);
            removed = true;
        }
    });
    if (removed) {
        recalcularOrdenSecuencia();
    }
}

function reordenarSecuenciaSeleccion(direccion) {
    const tbSec = document.querySelector('#tblSecuencia tbody');
    const rows  = Array.from(tbSec.querySelectorAll('tr'));
    let idxSel  = -1;

    rows.forEach((row, idx) => {
        const chk = row.querySelector('.chk-sec');
        if (chk && chk.checked) idxSel = idx;
    });

    if (idxSel === -1) {
        alert('Selecciona una fila de la secuencia.');
        return;
    }

    if (direccion === 'up' && idxSel > 0) {
        tbSec.insertBefore(rows[idxSel], rows[idxSel - 1]);
    } else if (direccion === 'down' && idxSel < rows.length - 1) {
        tbSec.insertBefore(rows[idxSel + 1], rows[idxSel]);
    }

    recalcularOrdenSecuencia();
}

function recalcularOrdenSecuencia() {
    const tbSec = document.querySelector('#tblSecuencia tbody');
    const rows  = Array.from(tbSec.querySelectorAll('tr'));
    rows.forEach((row, index) => {
        const ordenCell = row.querySelector('.orden-col');
        if (ordenCell) {
            ordenCell.textContent = (index + 1);
        }
    });
}

function guardarOrdenSecuencia() {
    const idSec = document.getElementById('sec_det_id').textContent || '';
    const tbSec = document.querySelector('#tblSecuencia tbody');
    const rows  = Array.from(tbSec.querySelectorAll('tr'));

    const detalle = rows.map(row => {
        const cols = row.querySelectorAll('td');
        return {
            ubicacion_id: row.dataset.ubicacionId || null,
            orden: parseInt(cols[1].textContent || '0', 10)
        };
    });

    const payload = { sec_id: idSec, detalle: detalle };

    fetch('../api/secuencia_surtido.php?action=guardar_detalle', {
        method: 'POST',
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) {
            alert('Error: ' + resp.error);
            return;
        }
        alert('Secuencia guardada correctamente.');
        location.reload();
    })
    .catch(err => alert('Error en API: ' + err));
}

/* ======================= Guardar encabezado ==================== */
function guardarSecuenciaEncabezado() {
    const clave   = document.getElementById('ns_clave').value.trim();
    const nombre  = document.getElementById('ns_nombre').value.trim();
    const tipo    = document.getElementById('ns_tipo').value;
    const proceso = document.getElementById('ns_proceso').value;
    const comentario = document.getElementById('ns_comentarios').value.trim();

    if (!ALMACEN_SELECCIONADO) {
        alert('Falta seleccionar el almacén.');
        return;
    }
    if (!clave || !nombre || !tipo || !proceso) {
        alert('Completa todos los datos obligatorios.');
        return;
    }

    const payload = {
        almacen_clave: ALMACEN_SELECCIONADO,
        clave_sec: clave,
        nombre: nombre,
        tipo_sec: tipo,
        proceso: proceso,
        comentarios: comentario
    };

    fetch('../api/secuencia_surtido.php?action=crear', {
        method: 'POST',
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) {
            alert('Error: ' + resp.error);
            return;
        }
        alert(resp.mensaje || 'Secuencia creada correctamente.');
        if (modalNuevaSecuencia) modalNuevaSecuencia.hide();
        location.reload();
    })
    .catch(err => alert('Error en API: ' + err));
}

/* ======================= Usuarios por secuencia ==================== */
function asignarUsuarios(btn) {
    const tr = btn.closest('tr');
    const id      = parseInt(tr.dataset.secId || '0', 10);
    const clave   = tr.dataset.secClave || '';
    const nombre  = tr.dataset.secNombre || '';

    document.getElementById('us_sec_id').value = id;
    document.getElementById('us_sec_info').textContent =
        'Secuencia ' + id + ' - ' + clave + ' : ' + nombre;

    fetch('../api/secuencia_surtido.php?action=usuarios_data&sec_id=' + id)
        .then(r => r.json())
        .then(resp => {
            if (!resp.ok) {
                alert('Error: ' + resp.error);
                return;
            }
            const tbody = document.getElementById('tblUsuarios');
            tbody.innerHTML = '';

            const asignados = new Set((resp.asignados || []).map(x => String(x)));

            (resp.usuarios || []).forEach(u => {
                const tr = document.createElement('tr');
                const uid = String(u.id_user);
                const checked = asignados.has(uid) ? 'checked' : '';
                tr.innerHTML = `
                    <td class="text-center">
                        <input type="checkbox" class="chk-us" value="${uid}" ${checked}>
                    </td>
                    <td>${u.cve_usuario}</td>
                    <td>${u.nombre_completo}</td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('us_sel_all').checked = false;

            if (!modalUsuarios) {
                modalUsuarios = new bootstrap.Modal(document.getElementById('modalUsuarios'));
            }
            modalUsuarios.show();
        })
        .catch(err => alert('Error en API: ' + err));
}

function toggleUsuariosTodos(chk) {
    const marca = chk.checked;
    document.querySelectorAll('#tblUsuarios .chk-us').forEach(c => {
        c.checked = marca;
    });
}

function guardarUsuariosSecuencia() {
    const secId = document.getElementById('us_sec_id').value;
    const usuarios = [];
    document.querySelectorAll('#tblUsuarios .chk-us').forEach(c => {
        if (c.checked) usuarios.push(c.value);
    });

    const payload = { sec_id: secId, usuarios: usuarios };

    fetch('../api/secuencia_surtido.php?action=guardar_usuarios', {
        method: 'POST',
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) {
            alert('Error: ' + resp.error);
            return;
        }
        alert(resp.mensaje || 'Usuarios guardados.');
        if (modalUsuarios) modalUsuarios.hide();
        location.reload();
    })
    .catch(err => alert('Error en API: ' + err));
}

/* ======================= Eliminar (soft delete) ==================== */
function eliminarSecuencia(btn) {
    const tr = btn.closest('tr');
    const id = tr.dataset.secId;
    alert('Soft delete de la secuencia ' + id + ' pendiente de implementar (UPDATE activo=0).');
}
</script>

<style>
    /* Título principal en azul corporativo */
    .page-title-ss {
        color: #0F5AAD;                 /* Azul Adventech */
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .page-title-ss i {
        color: #00A3E0;                 /* Azul secundario */
        font-size: 16px;
    }

    /* Una sola línea, compacto en la grilla */
    #tablaSecuencias th,
    #tablaSecuencias td {
        padding: 4px 6px !important;
        white-space: nowrap;
        vertical-align: middle;
        font-size: 10px;
    }

    #tablaSecuencias tbody td.text-truncate {
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Cards resumen con estilo corporativo */
    .card-summary {
        border-radius: 0.9rem;
        border: 1px solid #E0E6F2;
        box-shadow: 0 2px 4px rgba(15, 90, 173, 0.08);
        position: relative;
        overflow: hidden;
    }
    .card-summary::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #0F5AAD, #00A3E0);
    }
    .card-summary .title {
        font-size: 11px;
        font-weight: 600;
        color: #0F5AAD;
        margin-bottom: 2px;
    }
    .card-summary .value {
        font-size: 18px;
        font-weight: 600;
        line-height: 1.1;
        color: #051B3A;
    }
    .card-summary .subtitle {
        font-size: 10px;
        color: #6c757d;
    }

    /* Tablas internas (modales) compactas */
    #tblDisponibles th,
    #tblDisponibles td,
    #tblSecuencia th,
    #tblSecuencia td,
    #tblUsuarios th,
    #tblUsuarios td {
        padding: 3px 4px !important;
        font-size: 10px;
        white-space: nowrap;
    }

    #tblDisponibles tbody tr,
    #tblSecuencia tbody tr {
        cursor: pointer;
    }

    /* Encabezados de cards en modales */
    #modalSecuencia .card-header {
        background: linear-gradient(90deg, #0F5AAD 0%, #00A3E0 100%);
        color: #fff;
        border-bottom: none;
    }
    #modalSecuencia .card-header .fw-bold {
        color: #fff;
    }

    /* Botones pequeños consistentes */
    .btn-xs {
        padding: 1px 6px;
        font-size: 10px;
        line-height: 1.3;
        border-radius: 4px;
    }
</style>
