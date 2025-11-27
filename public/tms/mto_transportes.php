<?php
// public/tms/mto_transportes.php

declare(strict_types=1);

$TITLE = 'Mantenimiento de Transportes';

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$mensajeError = null;
$transportes  = [];

try {
    $pdo = db_pdo();

    // Traemos transportes + descripción de tipo de transporte
    $sql = "
        SELECT 
            t.*,
            tt.desc_ttransporte,
            tt.capacidad_carga
        FROM t_transporte t
        LEFT JOIN tipo_transporte tt 
               ON tt.clave_ttransporte = t.tipo_transporte
        ORDER BY t.cve_cia, t.ID_Transporte
    ";
    $transportes = db_all($sql);
} catch (Throwable $e) {
    $mensajeError = 'Error al cargar información de transportes: ' . $e->getMessage();
}
?>

<div class="container-fluid py-3">

    <!-- Encabezado -->
    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-truck-moving me-2"></i>
                    Mantenimiento de Transportes
                </h4>
                <small class="text-muted">
                    Gestión de vehículos, estatus y accesos a mantenimiento preventivo/correctivo.
                </small>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-primary btn-sm me-2">
                    <i class="fa fa-plus-circle me-1"></i> Nueva Orden de Mantenimiento
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fa fa-calendar-alt me-1"></i> Programación Preventiva
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-clipboard-list me-1"></i> Historial OT
                </button>
            </div>
        </div>
    </div>

    <!-- Cards de resumen -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Transportes</div>
                            <div class="fs-5 fw-semibold">
                                <?php echo number_format(count($transportes)); ?>
                            </div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fa fa-truck text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estos valores son estáticos por ahora, se pueden parametrizar después -->
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Activos</div>
                            <div class="fs-5 fw-semibold">
                                <!-- Placeholder, se puede calcular filtrando Activo = 1 -->
                                -
                            </div>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fa fa-check-circle text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">En Taller</div>
                            <div class="fs-5 fw-semibold">
                                <!-- Cuando tengamos estatus_uso se puede contear -->
                                -
                            </div>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fa fa-tools text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Fuera de Servicio</div>
                            <div class="fs-5 fw-semibold">
                                <!-- Placeholder -->
                                -
                            </div>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="fa fa-exclamation-triangle text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <?php if ($mensajeError): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fa fa-exclamation-circle me-1"></i>
            <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" id="form-filtros-mto-transportes" onsubmit="return false;">
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1">Compañía</label>
                    <input type="text" class="form-control form-control-sm" placeholder="cve_cia / nombre (placeholder)">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1">Almacén base</label>
                    <select class="form-select form-select-sm">
                        <option value="">(Todos)</option>
                        <!-- Luego se llena con c_almacen / c_almacenp -->
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select class="form-select form-select-sm">
                        <option value="">(Todos)</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="EN_TALLER">En Taller</option>
                        <option value="FUERA_SERVICIO">Fuera de Servicio</option>
                        <option value="BAJA">Baja</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm mb-1">Tipo unidad</label>
                    <select class="form-select form-select-sm">
                        <option value="">(Todos)</option>
                        <!-- Se puede llenar con tipo_transporte/desc_ttransporte -->
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label form-label-sm mb-1">Buscar</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm" placeholder="Transporte / Placas / VIN">
                        <button class="btn btn-outline-primary btn-sm" type="button">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Grilla de transportes -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table id="grid_transportes" class="table table-sm table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">Cía</th>
                            <th style="width: 110px;">ID Transporte</th>
                            <th>Descripción</th>
                            <th style="width: 110px;">Placas</th>
                            <th style="width: 130px;">Tipo Transporte</th>
                            <th style="width: 110px;">Cap. Carga</th>
                            <th style="width: 80px;">Almacén</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 80px;">Activo</th>
                            <th style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transportes as $t): ?>
                        <?php
                        $cveCia          = $t['cve_cia'] ?? '';
                        $idTransporte    = $t['ID_Transporte'] ?? '';
                        $nombre          = $t['Nombre'] ?? '';
                        $placas          = $t['Placas'] ?? '';
                        $tipoTransporte  = $t['tipo_transporte'] ?? '';
                        $descTipo        = $t['desc_ttransporte'] ?? '';
                        $capCarga        = $t['capacidad_carga'] ?? null;
                        $idAlmac         = $t['id_almac'] ?? '';
                        $activo          = (int)($t['Activo'] ?? 0);
                        // Placeholder para estatus_uso: si no existe la columna, mostramos '-'
                        $estatusUso      = $t['estatus_uso'] ?? '-';

                        $badgeActivo = $activo === 1
                            ? '<span class="badge bg-success">Sí</span>'
                            : '<span class="badge bg-secondary">No</span>';

                        $badgeStatus = '-';
                        if ($estatusUso === 'ACTIVO') {
                            $badgeStatus = '<span class="badge bg-success">ACTIVO</span>';
                        } elseif ($estatusUso === 'EN_TALLER') {
                            $badgeStatus = '<span class="badge bg-warning text-dark">EN TALLER</span>';
                        } elseif ($estatusUso === 'FUERA_SERVICIO') {
                            $badgeStatus = '<span class="badge bg-danger">FUERA SERVICIO</span>';
                        } elseif ($estatusUso === 'BAJA') {
                            $badgeStatus = '<span class="badge bg-dark">BAJA</span>';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$cveCia, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$idTransporte, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$nombre, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$placas, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php
                                $labelTipo = trim((string)$tipoTransporte . ' ' . (string)$descTipo);
                                echo htmlspecialchars($labelTipo, ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td class="text-end">
                                <?php
                                echo $capCarga !== null
                                    ? number_format((float)$capCarga, 0) . ' kg'
                                    : '-';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php echo htmlspecialchars((string)$idAlmac, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-center">
                                <?php echo $badgeStatus ?: '-'; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $badgeActivo; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" title="Órdenes de mantenimiento">
                                        <i class="fa fa-wrench"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" title="Programación preventiva">
                                        <i class="fa fa-calendar-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" title="Historial">
                                        <i class="fa fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($transportes) && !$mensajeError): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted small">
                                No se encontraron transportes registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-1">
                * La integración con órdenes de mantenimiento, programación preventiva y historial se realizará en las siguientes etapas.
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#grid_transportes').DataTable({
            paging: true,
            pageLength: 25,
            lengthChange: false,
            ordering: true,
            info: true,
            searching: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            columnDefs: [
                { targets: [0, 3, 6, 7, 8, 9], orderable: false }
            ]
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
