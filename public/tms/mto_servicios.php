<?php
// public/procesos/mto_servicios.php
declare(strict_types=1);

$TITLE = 'Catálogo de Servicios de Mantenimiento';

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$mensajeError = null;
$servicios    = [];

// Filtros simples por GET
$cveCia = $_GET['cve_cia'] ?? '';
$clase  = $_GET['clase']   ?? '';
$q      = $_GET['q']       ?? '';

try {
    $pdo = db_pdo();

    $where = [];
    $params = [];

    if ($cveCia !== '') {
        $where[] = 'a.cve_cia = :cve_cia';
        $params[':cve_cia'] = (int)$cveCia;
    }

    if ($clase !== '') {
        $where[] = 't.clase = :clase';
        $params[':clase'] = $clase;
    }

    if ($q !== '') {
        $where[] = '(a.CVE_ACT LIKE :q OR a.descripcion LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    $sql = "
        SELECT 
            a.id,
            a.cve_cia,
            a.CVE_ACT,
            a.descripcion,
            a.km_frecuencia,
            a.dias_frecuencia,
            a.horas_frecuencia,
            a.tiempo_estimado_min,
            a.tarifa_mano_obra,
            a.tarifa_fija,
            a.activo,
            t.descripcion AS tipo_desc,
            t.clase,
            f.descripcion AS familia_desc
        FROM c_mto_actividad a
        LEFT JOIN c_mto_tipo t 
               ON t.id = a.tipo_id
        LEFT JOIN c_mto_familia_servicio f
               ON f.id = a.familia_id
        $whereSql
        ORDER BY a.cve_cia, a.CVE_ACT
        LIMIT 500
    ";

    $servicios = db_all($sql, $params);

} catch (Throwable $e) {
    $mensajeError = 'Error al cargar el catálogo de servicios: ' . $e->getMessage();
}

?>
<div class="container-fluid py-3">

    <!-- Encabezado -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-cogs me-2"></i>
                    Catálogo de Servicios de Mantenimiento
                </h4>
                <small class="text-muted">
                    Definición de servicios/actividades base para mantenimiento preventivo y correctivo.
                </small>
            </div>
            <div>
                <a href="mto_servicio_editar.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus-circle me-1"></i> Nuevo Servicio
                </a>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($mensajeError): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fa fa-exclamation-circle me-1"></i>
            <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Cards de resumen (placeholder simple) -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Total Servicios</div>
                            <div class="fs-5 fw-semibold">
                                <?php echo number_format(count($servicios)); ?>
                            </div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fa fa-cog text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Los siguientes los podemos afinar después calculando clasificaciones -->
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Preventivos</div>
                            <div class="fs-5 fw-semibold">-</div>
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
                            <div class="small text-muted">Correctivos</div>
                            <div class="fs-5 fw-semibold">-</div>
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
                            <div class="small text-muted">Inspecciones</div>
                            <div class="fs-5 fw-semibold">-</div>
                        </div>
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fa fa-search text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" method="get">
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm mb-1">Compañía</label>
                    <input type="text"
                           name="cve_cia"
                           class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($cveCia, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="cve_cia">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm mb-1">Clase</label>
                    <select name="clase" class="form-select form-select-sm">
                        <option value="">(Todas)</option>
                        <option value="PREVENTIVO" <?php echo $clase === 'PREVENTIVO' ? 'selected' : ''; ?>>Preventivo</option>
                        <option value="CORRECTIVO" <?php echo $clase === 'CORRECTIVO' ? 'selected' : ''; ?>>Correctivo</option>
                        <option value="INSPECCION" <?php echo $clase === 'INSPECCION' ? 'selected' : ''; ?>>Inspección</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm mb-1">Buscar</label>
                    <div class="input-group input-group-sm">
                        <input type="text"
                               name="q"
                               class="form-control form-control-sm"
                               value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="Código o descripción">
                        <button class="btn btn-outline-primary btn-sm" type="submit">
                            <i class="fa fa-search"></i>
                        </button>
                        <a href="mto_servicios.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-eraser"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Grilla -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table id="grid_servicios" class="table table-sm table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px;">Cía</th>
                            <th style="width:110px;">Código</th>
                            <th>Descripción</th>
                            <th style="width:150px;">Tipo / Clase</th>
                            <th style="width:150px;">Familia</th>
                            <th style="width:140px;">Frecuencia</th>
                            <th style="width:120px;">Tarifa MO</th>
                            <th style="width:120px;">Tarifa Fija</th>
                            <th style="width:70px;">Activo</th>
                            <th style="width:120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($servicios as $s): ?>
                        <?php
                        $badgeActivo = ((int)$s['activo'] === 1)
                            ? '<span class="badge bg-success">Sí</span>'
                            : '<span class="badge bg-secondary">No</span>';

                        $textoClase = $s['clase'] ?? '';
                        $badgeClase = '';
                        if ($textoClase === 'PREVENTIVO') {
                            $badgeClase = '<span class="badge bg-success">PREVENTIVO</span>';
                        } elseif ($textoClase === 'CORRECTIVO') {
                            $badgeClase = '<span class="badge bg-warning text-dark">CORRECTIVO</span>';
                        } elseif ($textoClase === 'INSPECCION') {
                            $badgeClase = '<span class="badge bg-info text-dark">INSPECCIÓN</span>';
                        }

                        $freq = [];
                        if (!empty($s['km_frecuencia'])) {
                            $freq[] = (int)$s['km_frecuencia'] . ' km';
                        }
                        if (!empty($s['dias_frecuencia'])) {
                            $freq[] = (int)$s['dias_frecuencia'] . ' días';
                        }
                        if (!empty($s['horas_frecuencia'])) {
                            $freq[] = (int)$s['horas_frecuencia'] . ' hrs';
                        }
                        $freqText = !empty($freq) ? implode(' / ', $freq) : '-';
                        ?>
                        <tr>
                            <td><?php echo (int)$s['cve_cia']; ?></td>
                            <td><?php echo htmlspecialchars($s['CVE_ACT'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($s['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($s['tipo_desc'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small mt-1"><?php echo $badgeClase; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($s['familia_desc'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($freqText, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end">
                                <?php
                                echo $s['tarifa_mano_obra'] !== null
                                    ? number_format((float)$s['tarifa_mano_obra'], 2)
                                    : '-';
                                ?>
                            </td>
                            <td class="text-end">
                                <?php
                                echo $s['tarifa_fija'] !== null
                                    ? number_format((float)$s['tarifa_fija'], 2)
                                    : '-';
                                ?>
                            </td>
                            <td class="text-center"><?php echo $badgeActivo; ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="mto_servicio_editar.php?id=<?php echo (int)$s['id']; ?>"
                                       class="btn btn-outline-primary btn-sm" title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            title="Usar en programación preventiva">
                                        <i class="fa fa-calendar-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($servicios) && !$mensajeError): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted small">
                                No se encontraron servicios con los criterios indicados.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-1">
                * En siguientes etapas se conectará con programación preventiva y detalle de OT.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn.DataTable) {
        $('#grid_servicios').DataTable({
            paging: true,
            pageLength: 25,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            columnDefs: [
                { targets: [0, 8, 9], orderable: false }
            ]
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
