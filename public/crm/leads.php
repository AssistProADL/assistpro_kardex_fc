<?php
// public/crm/leads.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$db = db_pdo();
$mensaje_ok = '';
$mensaje_error = '';

// ============================
// Alta de Lead
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_lead') {
    try {
        $nombre = trim($_POST['nombre']);
        $empresa = trim($_POST['empresa']);
        $telefono = trim($_POST['telefono']);
        $correo = trim($_POST['correo']);
        $origen = trim($_POST['origen']);
        $usuario = $_SESSION['usuario'] ?? 'SISTEMA';

        if ($nombre === '') {
            throw new Exception("El nombre del contacto es obligatorio.");
        }

        dbq("
            INSERT INTO t_crm_lead (nombre_contacto, empresa, telefono, correo, origen, usuario_asignado)
            VALUES (?,?,?,?,?,?)
        ", [
            $nombre,
            $empresa,
            $telefono,
            $correo,
            $origen,
            $usuario
        ]);

        $mensaje_ok = "Lead registrado correctamente.";
    } catch (Throwable $e) {
        $mensaje_error = "Error al guardar lead: " . $e->getMessage();
    }
}

// ============================
// Carga de leads existentes
// ============================
$leads = [];
try {
    $leads = db_all("
        SELECT 
            id_lead,
            fecha_alta,
            nombre_contacto,
            empresa,
            telefono,
            correo,
            origen,
            etapa,
            prioridad,
            usuario_asignado,
            estatus
        FROM t_crm_lead
        WHERE estatus = 'A'
        ORDER BY fecha_alta DESC
        LIMIT 500
    ");
} catch (Throwable $e) {
    $mensaje_error = "Error cargando leads: " . $e->getMessage();
}
?>

<div class="container-fluid mt-3" style="font-size:0.82rem;">

    <h4 class="mb-3 d-flex justify-content-between align-items-center">
        <span>CRM – Leads</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalLead">
            + Nuevo Lead
        </button>
    </h4>

    <?php if ($mensaje_ok): ?>
        <div class="alert alert-success py-1"><?= htmlspecialchars($mensaje_ok) ?></div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($mensaje_error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header py-2">Leads registrados</div>
        <div class="card-body p-2">

            <!-- VISTA DESKTOP: TABLA -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered" id="tblLeads">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Contacto</th>
                                <th>Empresa</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Origen</th>
                                <th>Etapa</th>
                                <th>Asesor</th>
                                <th style="width:80px;">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['fecha_alta']) ?></td>
                                <td><?= htmlspecialchars($l['nombre_contacto']) ?></td>
                                <td><?= htmlspecialchars($l['empresa']) ?></td>
                                <td><?= htmlspecialchars($l['telefono']) ?></td>
                                <td><?= htmlspecialchars($l['correo']) ?></td>
                                <td><?= htmlspecialchars($l['origen']) ?></td>
                                <td><?= htmlspecialchars($l['etapa']) ?></td>
                                <td><?= htmlspecialchars($l['usuario_asignado']) ?></td>
                                <td class="text-center">
                                    <a href="oportunidades.php?lead=<?= (int)$l['id_lead'] ?>" 
                                       class="btn btn-sm btn-success mb-1">
                                        OPP
                                    </a>
                                    <button class="btn btn-sm btn-secondary" disabled>Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VISTA MÓVIL: CARDS -->
            <div class="d-md-none">
                <?php if (empty($leads)): ?>
                    <div class="text-muted">No hay leads registrados.</div>
                <?php endif; ?>

                <div class="row g-2">
                    <?php foreach ($leads as $l): ?>
                        <div class="col-12">
                            <div class="card border-secondary">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div><strong><?= htmlspecialchars($l['nombre_contacto']) ?></strong></div>
                                            <?php if (!empty($l['empresa'])): ?>
                                                <div><small><?= htmlspecialchars($l['empresa']) ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($l['etapa']) ?>
                                            </span>
                                            <div><small class="text-muted">
                                                <?= htmlspecialchars(substr($l['fecha_alta'],0,16)) ?>
                                            </small></div>
                                        </div>
                                    </div>

                                    <?php if (!empty($l['telefono']) || !empty($l['correo'])): ?>
                                        <div class="mt-1">
                                            <?php if (!empty($l['telefono'])): ?>
                                                <div><small>📞 <?= htmlspecialchars($l['telefono']) ?></small></div>
                                            <?php endif; ?>
                                            <?php if (!empty($l['correo'])): ?>
                                                <div><small>✉ <?= htmlspecialchars($l['correo']) ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($l['origen']) ?>
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <a href="oportunidades.php?lead=<?= (int)$l['id_lead'] ?>" 
                                               class="btn btn-success">
                                                OPP
                                            </a>
                                            <button class="btn btn-secondary" disabled>Editar</button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>


<!-- ============================
     Modal Nuevo Lead
============================= -->
<div class="modal fade" id="modalLead" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post">
            <input type="hidden" name="accion" value="guardar_lead">

            <div class="modal-header py-2">
                <h5 class="modal-title">Nuevo Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="font-size: 0.85rem;">
                
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del contacto *</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <input type="text" name="empresa" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Correo</label>
                        <input type="email" name="correo" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Origen</label>
                        <select name="origen" class="form-select form-select-sm">
                            <option value="Web">Web</option>
                            <option value="Llamada">Llamada</option>
                            <option value="Referencia">Referencia</option>
                            <option value="Redes Sociales">Redes Sociales</option>
                            <option value="Amazon">Amazon</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="modal-footer py-2">
                <button type="submit" class="btn btn-success btn-sm">Guardar Lead</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            </div>

        </form>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function(){
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $("#tblLeads").DataTable({
            pageLength: 25,
            scrollY: "400px",
            scrollCollapse: true,
            ordering: true
        });
    }
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
