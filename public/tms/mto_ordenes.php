<?php
// public/procesos/mto_ordenes.php

declare(strict_types=1);

$TITLE = 'Órdenes de Mantenimiento';

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$mensajeError = null;
$ordenes = [];

try {
    $pdo = db_pdo();

    $sql = "
        SELECT 
            o.id,
            o.cve_cia,
            o.folio,
            o.transporte_id,
            o.tipo_id,
            o.taller_id,
            o.estatus,
            o.fecha_programada,
            o.fecha_crea,
            o.fecha_cierre,
            t.ID_Transporte,
            t.Nombre AS nombre_transporte,
            tt.desc_ttransporte,
            mt.descripcion AS tipo_mto,
            tl.nombre AS taller
        FROM th_mto_orden o
        LEFT JOIN t_transporte t ON t.id = o.transporte_id
        LEFT JOIN tipo_transporte tt ON tt.clave_ttransporte = t.tipo_transporte
        LEFT JOIN c_mto_tipo mt ON mt.id = o.tipo_id
        LEFT JOIN c_mto_taller tl ON tl.id = o.taller_id
        ORDER BY o.fecha_crea DESC
        LIMIT 200
    ";
    $ordenes = db_all($sql);
} catch (Throwable $e) {
    $mensajeError = 'Error al cargar órdenes de mantenimiento: ' . $e->getMessage();
}
?>

<div class="container-fluid py-3">

    <!-- ENCABEZADO -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-wrench me-2"></i> Órdenes de Mantenimiento
                </h4>
                <small class="text-muted">
                    Administración de OT, estatus, talleres y programación de servicios.
                </small>
            </div>
            <div>
                <a href="mto_orden_nueva.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus-circle me-1"></i> Generar Orden
                </a>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" onsubmit="return false;">
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Compañía</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Cía">
                </div>

                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Transporte</label>
                    <input type="text" class="form-control form-control-sm" placeholder="ID Transporte / Placas">
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Tipo Mto.</label>
                    <select class="form-select form-select-sm">
                        <option value="">(Todos)</option>
                        <option value="PREVENTIVO">Preventivo</option>
                        <option value="CORRECTIVO">Correctivo</option>
                        <option value="INSPECCION">Inspección</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Taller</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Taller">
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Estatus</label>
                    <select class="form-select form-select-sm">
                        <option value="">(Todos)</option>
                        <option value="ABIERTA">Abierta</option>
                        <option value="EN_PROCESO">En Proceso</option>
                        <option value="CERRADA">Cerrada</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ALERTAS -->
    <?php if ($mensajeError): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fa fa-exclamation-circle me-1"></i><?= $mensajeError ?>
        </div>
    <?php endif; ?>

    <!-- GRID PRINCIPAL -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">

            <div class="table-responsive" style="max-height: 470px; overflow-y: auto;">
                <table id="grid_ordenes" class="table table-sm table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Transporte</th>
                            <th>Tipo</th>
                            <th>Taller</th>
                            <th>Estatus</th>
                            <th style="width:120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ordenes as $o): ?>
                        <?php
                        $badge = '<span class="badge bg-secondary">-</span>';
                        switch ($o['estatus']) {
                            case 'ABIERTA':
                                $badge = '<span class="badge bg-info text-dark">Abierta</span>';
                                break;
                            case 'EN_PROCESO':
                                $badge = '<span class="badge bg-warning text-dark">En Proceso</span>';
                                break;
                            case 'CERRADA':
                                $badge = '<span class="badge bg-success">Cerrada</span>';
                                break;
                            case 'CANCELADA':
                                $badge = '<span class="badge bg-danger">Cancelada</span>';
                                break;
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($o['folio']) ?></td>
                            <td><?= htmlspecialchars($o['fecha_crea']) ?></td>
                            <td>
                                <?= htmlspecialchars($o['ID_Transporte']) ?>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($o['nombre_transporte']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($o['tipo_mto'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($o['taller'] ?? '-') ?></td>
                            <td><?= $badge ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" title="Ver / Editar">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" title="Historial">
                                        <i class="fa fa-history"></i>
                                    </button>
                                    <button class="btn btn-outline-dark" title="Imprimir">
                                        <i class="fa fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($ordenes)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted small">
                                No hay órdenes registradas.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn.DataTable) {
        $('#grid_ordenes').DataTable({
            paging: true,
            lengthChange: false,
            pageLength: 25,
            searching: false,
            ordering: true,
            info: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
