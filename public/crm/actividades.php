<?php
// public/crm/actividades.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$db = db_pdo();
$mensaje_ok = '';
$mensaje_error = '';

$id_opp = isset($_GET['id_opp']) ? (int)$_GET['id_opp'] : 0;
$id_lead = isset($_GET['id_lead']) ? (int)$_GET['id_lead'] : 0;

$opp_actual = null;
$lead_actual = null;

// ============================
// Cargar info relacionada
// ============================
try {
    if ($id_opp > 0) {
        $opp_actual = db_row("
            SELECT o.*, l.nombre_contacto, l.empresa
            FROM t_crm_oportunidad o
            LEFT JOIN t_crm_lead l ON l.id_lead = o.id_lead
            WHERE o.id_opp = ?
        ", [$id_opp]);
    }

    if ($id_lead > 0) {
        $lead_actual = db_row("
            SELECT *
            FROM t_crm_lead
            WHERE id_lead = ?
        ", [$id_lead]);
    }
} catch (Throwable $e) {
    $mensaje_error = "Error cargando datos relacionados: " . $e->getMessage();
}

// ============================
// Registrar actividad
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_actividad') {
    try {
        $tipo = trim($_POST['tipo']);
        $descripcion = trim($_POST['descripcion']);
        $fecha_programada = $_POST['fecha_programada'];
        $usuario = $_SESSION['usuario'] ?? 'SISTEMA';
        $id_opp_post = $_POST['id_opp'] !== '' ? (int)$_POST['id_opp'] : null;
        $id_lead_post = $_POST['id_lead'] !== '' ? (int)$_POST['id_lead'] : null;

        if ($tipo === '' || $fecha_programada === '') {
            throw new Exception("Tipo y fecha programada son obligatorios.");
        }

        dbq("
            INSERT INTO t_crm_actividad (
                id_lead, id_opp, tipo, descripcion, fecha_programada, usuario
            ) VALUES (?,?,?,?,?,?)
        ", [
            $id_lead_post,
            $id_opp_post,
            $tipo,
            $descripcion,
            $fecha_programada,
            $usuario
        ]);

        $mensaje_ok = "Actividad registrada correctamente.";
    } catch (Throwable $e) {
        $mensaje_error = "Error al guardar la actividad: " . $e->getMessage();
    }
}

// ============================
// Cambiar estatus de actividad
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estatus') {
    try {
        $id_act = (int)$_POST['id_act'];
        $estatus = trim($_POST['estatus']);

        if ($estatus === 'Realizada') {
            $fecha_realizada = date('Y-m-d H:i:s');
            dbq("
                UPDATE t_crm_actividad
                SET estatus = ?, fecha_realizada = ?
                WHERE id_act = ?
            ", [$estatus, $fecha_realizada, $id_act]);
        } else {
            dbq("
                UPDATE t_crm_actividad
                SET estatus = ?
                WHERE id_act = ?
            ", [$estatus, $id_act]);
        }

        $mensaje_ok = "Estatus actualizado correctamente.";
    } catch (Throwable $e) {
        $mensaje_error = "Error al actualizar actividad: " . $e->getMessage();
    }
}

// ============================
// Listado de actividades
// ============================
$actividades = [];
try {
    $sql = "
        SELECT 
            a.*,
            l.nombre_contacto,
            l.empresa,
            o.titulo AS opp_titulo
        FROM t_crm_actividad a
        LEFT JOIN t_crm_lead l ON l.id_lead = a.id_lead
        LEFT JOIN t_crm_oportunidad o ON o.id_opp = a.id_opp
    ";
    $params = [];

    if ($id_opp > 0) {
        $sql .= " WHERE a.id_opp = ? ";
        $params[] = $id_opp;
    } elseif ($id_lead > 0) {
        $sql .= " WHERE a.id_lead = ? ";
        $params[] = $id_lead;
    }

    $sql .= " ORDER BY a.fecha_programada ASC ";

    $actividades = db_all($sql, $params);
} catch (Throwable $e) {
    $mensaje_error = "Error cargando actividades: " . $e->getMessage();
}

$t_tipos = [
    'Llamada',
    'Correo',
    'Visita',
    'Tarea',
    'Seguimiento'
];
$t_estatus = ['Programada', 'Realizada', 'Vencida', 'Cancelada'];
?>

<div class="container-fluid mt-3" style="font-size:0.82rem;">

    <h4 class="mb-3">
        CRM – Actividades
        <?php if ($opp_actual): ?>
            <small class="text-muted">| OPP #<?= (int)$opp_actual['id_opp'] ?> – <?= htmlspecialchars($opp_actual['titulo']) ?></small>
        <?php endif; ?>
        <?php if ($lead_actual): ?>
            <small class="text-muted">| Lead: <?= htmlspecialchars($lead_actual['nombre_contacto']) ?></small>
        <?php endif; ?>
    </h4>

    <?php if ($mensaje_ok): ?>
        <div class="alert alert-success py-1"><?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($mensaje_error) ?></div>
    <?php endif; ?>

    <!-- FORMULARIO DE NUEVA ACTIVIDAD -->
    <div class="card mb-3">
        <div class="card-header py-2">Nueva actividad</div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <input type="hidden" name="accion" value="guardar_actividad">
                <input type="hidden" name="id_opp" value="<?= $id_opp ?: '' ?>">
                <input type="hidden" name="id_lead" value="<?= $id_lead ?: '' ?>">

                <div class="col-md-3">
                    <label class="form-label mb-0">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm" required>
                        <option value="">--</option>
                        <?php foreach ($t_tipos as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-0">Fecha programada *</label>
                    <input type="datetime-local" name="fecha_programada" class="form-control form-control-sm" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label mb-0">Descripción</label>
                    <input type="text" name="descripcion" class="form-control form-control-sm">
                </div>

                <div class="col-md-3 mt-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        Guardar actividad
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTADO DE ACTIVIDADES -->
    <div class="card mb-3">
        <div class="card-header py-2">Actividades registradas</div>
        <div class="card-body p-2">

            <!-- DESKTOP: TABLA -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped" id="tblActs">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Lead / OPP</th>
                                <th>Estatus</th>
                                <th style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($a['fecha_programada'],0,16)) ?></td>
                                    <td><?= htmlspecialchars($a['tipo']) ?></td>
                                    <td><?= htmlspecialchars($a['descripcion']) ?></td>
                                    <td>
                                        <?php if ($a['nombre_contacto']): ?>
                                            <div><strong><?= htmlspecialchars($a['nombre_contacto']) ?></strong></div>
                                            <div><small><?= htmlspecialchars($a['empresa']) ?></small></div>
                                        <?php endif; ?>
                                        <?php if ($a['opp_titulo']): ?>
                                            <div><small>OPP: <?= htmlspecialchars($a['opp_titulo']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['estatus']) ?></td>
                                    <td>
                                        <form method="post" class="d-flex flex-column gap-1">
                                            <input type="hidden" name="accion" value="cambiar_estatus">
                                            <input type="hidden" name="id_act" value="<?= (int)$a['id_act'] ?>">

                                            <select name="estatus" class="form-select form-select-sm">
                                                <?php foreach ($t_estatus as $e): ?>
                                                    <option value="<?= htmlspecialchars($e) ?>" 
                                                        <?= $a['estatus']===$e ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($e) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="btn btn-sm btn-primary">Guardar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MÓVIL: CARDS -->
            <div class="d-md-none">

                <?php if (empty($actividades)): ?>
                    <div class="text-muted">No hay actividades registradas.</div>
                <?php endif; ?>

                <div class="row g-2">
                    <?php foreach ($actividades as $a): ?>
                        <div class="col-12">
                            <div class="card border-secondary">
                                <div class="card-body p-2">

                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?= htmlspecialchars($a['tipo']) ?></strong>
                                            <div><small><?= htmlspecialchars(substr($a['fecha_programada'],0,16)) ?></small></div>
                                        </div>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($a['estatus']) ?></span>
                                    </div>

                                    <?php if ($a['descripcion']): ?>
                                        <div class="mt-1"><?= htmlspecialchars($a['descripcion']) ?></div>
                                    <?php endif; ?>

                                    <?php if ($a['nombre_contacto'] || $a['opp_titulo']): ?>
                                        <div class="mt-1">
                                            <?php if ($a['nombre_contacto']): ?>
                                                <div><small><strong>Lead:</strong> <?= htmlspecialchars($a['nombre_contacto']) ?></small></div>
                                            <?php endif; ?>
                                            <?php if ($a['opp_titulo']): ?>
                                                <div><small><strong>OPP:</strong> <?= htmlspecialchars($a['opp_titulo']) ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="accion" value="cambiar_estatus">
                                        <input type="hidden" name="id_act" value="<?= (int)$a['id_act'] ?>">

                                        <select name="estatus" class="form-select form-select-sm mb-1">
                                            <?php foreach ($t_estatus as $e): ?>
                                                <option value="<?= htmlspecialchars($e) ?>" 
                                                    <?= $a['estatus']===$e ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($e) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button class="btn btn-sm btn-primary w-100">Guardar</button>
                                    </form>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $("#tblActs").DataTable({
            pageLength: 25,
            scrollY: "400px",
            scrollCollapse: true,
            ordering: true
        });
    }
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
