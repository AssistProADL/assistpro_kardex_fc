<?php
// rutas_surtido.php
// Administrador de Rutas de Surtido - Versión AssistPro Kardex FC

// Usar exactamente tu app/db.php sin modificarlo
require_once __DIR__ . '/../../app/db.php';

// Aquí inicializamos la conexión correctamente
$pdo = db_pdo();   // <-- ESTA ES LA CLAVE. db_pdo() devuelve el PDO ya conectado.

// Filtros
$almacenSel = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$criterio   = isset($_GET['q']) ? trim($_GET['q']) : '';

// Catálogo de almacenes (c_almacenp)
$sqlAlm = "SELECT clave, nombre FROM c_almacenp ORDER BY clave";
$stmtAlm = $pdo->query($sqlAlm);
$almacenes = $stmtAlm->fetchAll(PDO::FETCH_ASSOC);

// Rutas de surtido
// IMPORTANTE: crea una vista v_rutas_surtido con estos campos mínimos:
// id, nombre, ubicaciones_asignadas, usuarios_asignados, almacen_clave, almacen_nombre
$sql = "
    SELECT
        id,
        nombre,
        ubicaciones_asignadas,
        usuarios_asignados,
        almacen_clave,
        almacen_nombre
    FROM v_rutas_surtido
    WHERE 1=1
";

$params = [];

if ($almacenSel !== '') {
    $sql .= " AND almacen_clave = :almacen ";
    $params[':almacen'] = $almacenSel;
}

if ($criterio !== '') {
    $sql .= " AND ( nombre LIKE :crit
                OR usuarios_asignados LIKE :crit
                OR almacen_nombre LIKE :crit )";
    $params[':crit'] = '%' . $criterio . '%';
}

$sql .= " ORDER BY id ASC";

$stmtRutas = $pdo->prepare($sql);
$stmtRutas->execute($params);
$rutas = $stmtRutas->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid mt-3" style="font-size:10px;">
    <div class="row mb-3">
        <div class="col-12">
            <h5 class="mb-0">
                <i class="fa fa-route me-2"></i>Administrador de Rutas de Surtido
            </h5>
            <small class="text-muted">
                Definición y mantenimiento de rutas de surtido por almacén y surtidor.
            </small>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold" style="font-size:11px;">Total de rutas</div>
                            <div class="h6 mb-0"><?= count($rutas); ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fa fa-project-diagram fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="fw-bold" style="font-size:11px;">Almacén seleccionado</div>
                    <div class="mb-0">
                        <?php
                        if ($almacenSel === '') {
                            echo '<span class="text-muted">Todos</span>';
                        } else {
                            $almNom = array_filter($almacenes, function($a) use ($almacenSel){
                                return $a['clave'] === $almacenSel;
                            });
                            $almNom = $almNom ? reset($almNom) : null;
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

        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="fw-bold" style="font-size:11px;">Ubicaciones asignadas (prom.)</div>
                    <div class="mb-0">
                        <?php
                        $promUbic = 0;
                        if (count($rutas) > 0) {
                            $sum = 0;
                            foreach ($rutas as $r) {
                                $sum += (int)($r['ubicaciones_asignadas'] ?? 0);
                            }
                            $promUbic = $sum / count($rutas);
                        }
                        echo number_format($promUbic, 1);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-0">
                <div class="card-body py-2">
                    <div class="fw-bold" style="font-size:11px;">Usuarios distintos</div>
                    <div class="mb-0">
                        <?php
                        $usuariosSet = [];
                        foreach ($rutas as $r) {
                            if (!empty($r['usuarios_asignados'])) {
                                $list = explode(',', $r['usuarios_asignados']);
                                foreach ($list as $u) {
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
                <input type="text" id="q" name="q" class="form-control"
                       placeholder="Nombre ruta, usuario, almacén..."
                       value="<?= htmlspecialchars($criterio); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 d-flex justify-content-end">
            <div class="text-end">
                <button type="button" class="btn btn-sm btn-success me-2" onclick="nuevaRuta();">
                    <i class="fa fa-plus-circle me-1"></i>Nueva ruta
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarRutas();">
                    <i class="fa fa-file-excel-o me-1"></i>Exportar
                </button>
            </div>
        </div>
    </form>

    <!-- Grilla -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="tablaRutas" class="table table-striped table-bordered table-hover w-100">
                    <thead class="table-light" style="font-size:10px;">
                        <tr>
                            <th style="width:90px;">Acciones</th>
                            <th style="width:50px;">ID</th>
                            <th>Nombre</th>
                            <th style="width:120px;">Ubicaciones asignadas</th>
                            <th>Usuario(s) asignado(s)</th>
                            <th>Almacén</th>
                        </tr>
                    </thead>
                    <tbody style="font-size:10px;">
                        <?php foreach ($rutas as $r): ?>
                            <tr>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-xs btn-outline-info me-1"
                                            title="Detalle"
                                            onclick="verDetalle(<?= (int)$r['id']; ?>);">
                                        <i class="fa fa-search"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-primary me-1"
                                            title="Editar"
                                            onclick="editarRuta(<?= (int)$r['id']; ?>);">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-danger"
                                            title="Eliminar"
                                            onclick="eliminarRuta(<?= (int)$r['id']; ?>);">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                                <td><?= (int)$r['id']; ?></td>
                                <td><?= htmlspecialchars($r['nombre']); ?></td>
                                <td class="text-center">
                                    <?= (int)($r['ubicaciones_asignadas'] ?? 0); ?>
                                </td>
                                <td><?= htmlspecialchars($r['usuarios_asignados'] ?? ''); ?></td>
                                <td>
                                    <?= htmlspecialchars($r['almacen_clave']); ?>
                                    <?php if (!empty($r['almacen_nombre'])): ?>
                                        - <?= htmlspecialchars($r['almacen_nombre']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rutas)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No hay rutas que coincidan con los filtros actuales.
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
    $('#tablaRutas').DataTable({
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

function verDetalle(id) {
    alert('Aquí se mostraría el detalle de la ruta ' + id + ' (pendiente implementar).');
}

function editarRuta(id) {
    alert('Aquí se editaría la ruta ' + id + ' (pendiente implementar).');
}

function eliminarRuta(id) {
    if (!confirm('¿Eliminar la ruta ' + id + '?')) return;
    alert('Endpoint de eliminación pendiente de implementar.');
}

function nuevaRuta() {
    alert('Alta de nueva ruta (pendiente implementar).');
}

function exportarRutas() {
    alert('Exportación de rutas (pendiente implementar).');
}
</script>

<style>
    #tablaRutas tbody td,
    #tablaRutas thead th {
        vertical-align: middle;
    }
</style>
