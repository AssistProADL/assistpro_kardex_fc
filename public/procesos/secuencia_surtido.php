<?php
// secuencia_surtido.php
// Administrador de Secuencias de Surtido - AssistPro Kardex FC

require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

// Filtros
$almacenSel = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$criterio   = isset($_GET['q']) ? trim($_GET['q']) : '';

// Catálogo de almacenes (c_almacenp)
$sqlAlm = "SELECT id, clave, nombre FROM c_almacenp ORDER BY clave";
$stmtAlm = $pdo->query($sqlAlm);
$almacenes = $stmtAlm->fetchAll(PDO::FETCH_ASSOC);

// Secuencias de surtido (vista v_secuencia_surtido_admin)
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
    $params[':crit'] = '%' . $criterio . '%';
}

$sql .= " ORDER BY id ASC";

$stmtSec = $pdo->prepare($sql);
$stmtSec->execute($params);
$secuencias = $stmtSec->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid mt-3" style="font-size:10px;">
    <div class="row mb-3">
        <div class="col-12">
            <h5 class="mb-0">
                <i class="fa fa-route me-2"></i>Secuencia de Surtido
            </h5>
            <small class="text-muted">
                Definición de secuencias internas de surtido por almacén, tipo y proceso.
            </small>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row mb-3">
        <!-- Total secuencias -->
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold" style="font-size:11px;">Total de secuencias</div>
                            <div class="h6 mb-0"><?= count($secuencias); ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fa fa-project-diagram fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Almacén seleccionado -->
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="fw-bold" style="font-size:11px;">Almacén seleccionado</div>
                    <div class="mb-0">
                        <?php
                        if ($almacenSel === '') {
                            echo '<span class="text-muted">Todos</span>';
                        } else {
                            $almNom = null;
                            foreach ($almacenes as $a) {
                                if ($a['clave'] === $almacenSel) {
                                    $almNom = $a;
                                    break;
                                }
                            }
                            echo '<span class="fw-semibold">' . htmlspecialchars($almacenSel) . '</span>';
                            if ($almNom) {
                                echo ' - ' . htmlspecialchars($almNom['nombre']);
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ubicaciones promedio por secuencia -->
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="fw-bold" style="font-size:11px;">Ubicaciones por secuencia (prom.)</div>
                    <div class="mb-0">
                        <?php
                        $promUbic = 0;
                        if (count($secuencias) > 0) {
                            $sum = 0;
                            foreach ($secuencias as $s) {
                                $sum += (int)($s['ubicaciones_asignadas'] ?? 0);
                            }
                            $promUbic = $sum / count($secuencias);
                        }
                        echo number_format($promUbic, 1);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuarios distintos -->
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="fw-bold" style="font-size:11px;">Usuarios distintos</div>
                    <div class="mb-0">
                        <?php
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
                        echo count($usuariosSet);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-4 col-sm-6">
            <label for="almacen" class="form-label mb-1">Almacén</label>
            <select name="almacen" id="almacen" class="form-select form-select-sm">
                <option value="">[Todos]</option>
                <?php foreach ($almacenes as $a): ?>
                    <option value="<?= htmlspecialchars($a['clave']); ?>"
                        <?= $almacenSel === $a['clave'] ? 'selected' : ''; ?>>
                        (<?= htmlspecialchars($a['clave']); ?>) - <?= htmlspecialchars($a['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4 col-sm-6">
            <label for="q" class="form-label mb-1">Buscar</label>
            <div class="input-group input-group-sm">
                <input type="text"
                       id="q"
                       name="q"
                       class="form-control"
                       placeholder="Clave, nombre, tipo, proceso, usuario..."
                       value="<?= htmlspecialchars($criterio); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 d-flex justify-content-end">
            <div class="text-end">
                <button type="button" class="btn btn-sm btn-success me-2" onclick="nuevaSecuencia();">
                    <i class="fa fa-plus-circle me-1"></i>Nueva secuencia
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarSecuencias();">
                    <i class="fa fa-file-excel-o me-1"></i>Exportar
                </button>
            </div>
        </div>
    </form>

    <!-- Grilla -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="tablaSecuencias" class="table table-striped table-bordered table-hover w-100">
                    <thead class="table-light" style="font-size:10px;">
                        <tr>
                            <th style="width:110px;">Acciones</th>
                            <th style="width:50px;">ID</th>
                            <th style="width:90px;">Clave</th>
                            <th>Nombre</th>
                            <th style="width:80px;">Tipo</th>
                            <th style="width:90px;">Proceso</th>
                            <th style="width:90px;">Ubic. asignadas</th>
                            <th>Usuarios asignados</th>
                            <th>Almacén</th>
                        </tr>
                    </thead>
                    <tbody style="font-size:10px;">
                        <?php foreach ($secuencias as $s): ?>
                            <tr>
                                <td class="text-center">
                                    <!-- Todos los botones visibles (acciones futuras) -->
                                    <button type="button"
                                            class="btn btn-xs btn-outline-info me-1"
                                            title="Detalle / Secuencia"
                                            onclick="verDetalleSecuencia(<?= (int)$s['id']; ?>);">
                                        <i class="fa fa-search"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-primary me-1"
                                            title="Editar encabezado"
                                            onclick="editarSecuencia(<?= (int)$s['id']; ?>);">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-warning me-1"
                                            title="Asignar usuarios"
                                            onclick="asignarUsuarios(<?= (int)$s['id']; ?>);">
                                        <i class="fa fa-user"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-danger"
                                            title="Eliminar (soft delete)"
                                            onclick="eliminarSecuencia(<?= (int)$s['id']; ?>);">
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
                                <td><?= htmlspecialchars($s['usuarios_asignados'] ?? ''); ?></td>
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

// Stubs de acciones (para conectar después a modales / endpoints)
function nuevaSecuencia() {
    alert('Alta de nueva Secuencia de Surtido (pendiente implementar: wizard).');
}

function exportarSecuencias() {
    alert('Exportación de Secuencias de Surtido (pendiente implementar).');
}

function verDetalleSecuencia(id) {
    console.log('Detalle secuencia', id);
    alert('Aquí se mostraría el detalle de la secuencia ' + id +
          ' (orden de BL, drag & drop, etc. pendiente implementar).');
}

function editarSecuencia(id) {
    console.log('Editar secuencia', id);
    alert('Aquí se editarían los datos generales de la secuencia ' + id +
          ' (encabezado).');
}

function asignarUsuarios(id) {
    console.log('Asignar usuarios secuencia', id);
    alert('Aquí se administran los usuarios asignados a la secuencia ' + id + '.');
}

function eliminarSecuencia(id) {
    if (!confirm('¿Eliminar (desactivar) la secuencia ' + id + '?')) {
        return;
    }
    console.log('Eliminar secuencia', id);
    alert('Soft delete de la secuencia ' + id + ' pendiente de implementar (update activo=0).');
}
</script>

<style>
    #tablaSecuencias tbody td,
    #tablaSecuencias thead th {
        vertical-align: middle;
    }
</style>
