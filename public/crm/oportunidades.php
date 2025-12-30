<?php
// public/crm/oportunidades.php

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$db = db_pdo();
$mensaje_ok = '';
$mensaje_error = '';

/**
 * Escape HTML seguro para PHP 8.1+ (evita deprecations por NULL).
 */
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$id_lead_filtro = isset($_GET['lead']) ? (int)$_GET['lead'] : 0;
$lead_actual = null;

// ============================
// Cargar info de lead si viene por GET
// ============================
if ($id_lead_filtro > 0) {
    try {
        $lead_actual = db_row("
            SELECT id_lead, fecha_alta, nombre_contacto, empresa, telefono, correo, origen, etapa
            FROM t_crm_lead
            WHERE id_lead = ?
        ", [$id_lead_filtro]);
    } catch (Throwable $e) {
        $mensaje_error = "Error cargando lead: " . $e->getMessage();
    }
}

// ============================
// Alta de Oportunidad
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_opp') {
    try {
        $id_lead       = isset($_POST['id_lead']) ? (int)$_POST['id_lead'] : null;
        $id_cliente    = isset($_POST['id_cliente']) && $_POST['id_cliente'] !== '' ? (int)$_POST['id_cliente'] : null;
        $titulo        = trim($_POST['titulo'] ?? '');
        $valor         = (float)($_POST['valor_estimado'] ?? 0);
        $probabilidad  = (int)($_POST['probabilidad'] ?? 10);
        $etapa         = trim($_POST['etapa'] ?? 'Prospección');
        $fecha_cierre  = !empty($_POST['fecha_cierre_estimada']) ? $_POST['fecha_cierre_estimada'] : null;
        $usuario_resp  = $_SESSION['usuario'] ?? 'SISTEMA';

        if ($titulo === '') {
            throw new Exception("El título de la oportunidad es obligatorio.");
        }

        dbq("
            INSERT INTO t_crm_oportunidad (
                id_lead,
                id_cliente,
                titulo,
                valor_estimado,
                probabilidad,
                etapa,
                fecha_cierre_estimada,
                usuario_responsable
            ) VALUES (?,?,?,?,?,?,?,?)
        ", [
            $id_lead,
            $id_cliente,
            $titulo,
            $valor,
            $probabilidad,
            $etapa,
            $fecha_cierre,
            $usuario_resp
        ]);

        $mensaje_ok = "Oportunidad registrada correctamente.";
    } catch (Throwable $e) {
        $mensaje_error = "Error al guardar oportunidad: " . $e->getMessage();
    }
}

// ============================
// Cambio de etapa
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_etapa') {
    try {
        $id_opp     = (int)($_POST['id_opp'] ?? 0);
        $etapa_nva  = trim($_POST['etapa_nueva'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');
        $usuario    = $_SESSION['usuario'] ?? 'SISTEMA';

        if ($id_opp <= 0 || $etapa_nva === '') {
            throw new Exception("Datos insuficientes para cambio de etapa.");
        }

        $opp = db_row("
            SELECT etapa
            FROM t_crm_oportunidad
            WHERE id_opp = ?
        ", [$id_opp]);

        if (!$opp) {
            throw new Exception("Oportunidad no encontrada.");
        }

        $etapa_ant = $opp['etapa'];

        $db->beginTransaction();

        dbq("
            UPDATE t_crm_oportunidad
            SET etapa = ?, fecha_modifica = NOW()
            WHERE id_opp = ?
        ", [$etapa_nva, $id_opp]);

        dbq("
            INSERT INTO t_crm_movimientos_etapa (id_opp, etapa_anterior, etapa_nueva, usuario, comentario)
            VALUES (?,?,?,?,?)
        ", [$id_opp, $etapa_ant, $etapa_nva, $usuario, $comentario]);

        $db->commit();

        $mensaje_ok = "Etapa actualizada correctamente ({$etapa_ant} → {$etapa_nva}).";
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $mensaje_error = "Error al cambiar etapa: " . $e->getMessage();
    }
}

// ============================
// Catálogo de clientes
// ============================
$clientes = [];
try {
    $clientes = db_all("
        SELECT id_cliente, Cve_Clte, RazonSocial
        FROM c_cliente
        WHERE Activo = 1
        ORDER BY RazonSocial
    ");
} catch (Throwable $e) {
    // no crítico
}

// ============================
// Cargar oportunidades
// ============================
$opps = [];
try {
    $sql = "
        SELECT 
            o.id_opp,
            o.id_lead,
            o.id_cliente,
            o.titulo,
            o.valor_estimado,
            o.probabilidad,
            o.etapa,
            o.fecha_cierre_estimada,
            o.usuario_responsable,
            o.fecha_crea,
            l.nombre_contacto,
            l.empresa,
            c.RazonSocial AS cliente_nombre
        FROM t_crm_oportunidad o
        LEFT JOIN t_crm_lead l ON l.id_lead = o.id_lead
        LEFT JOIN c_cliente c ON c.id_cliente = o.id_cliente
    ";
    $params = [];

    if ($id_lead_filtro > 0) {
        $sql .= " WHERE o.id_lead = ? ";
        $params[] = $id_lead_filtro;
    }

    $sql .= " ORDER BY o.fecha_crea DESC LIMIT 500";

    $opps = db_all($sql, $params);
} catch (Throwable $e) {
    $mensaje_error = "Error cargando oportunidades: " . $e->getMessage();
}

// Etapas posibles
$etapas_posibles = [
    'Prospección',
    'Propuesta',
    'Cotizado',
    'Negociación',
    'Ganada',
    'Perdida'
];
?>

<div class="container-fluid mt-3" style="font-size:0.82rem;">

    <h4 class="mb-3">
        CRM – Oportunidades
        <?php if ($lead_actual): ?>
            <small class="text-muted">
                &nbsp;|&nbsp; Lead:
                <?= h($lead_actual['nombre_contacto'] ?? '') ?>
                (<?= h($lead_actual['empresa'] ?? '') ?>)
            </small>
        <?php endif; ?>
    </h4>

    <?php if ($mensaje_ok): ?>
        <div class="alert alert-success py-1"><?= h($mensaje_ok) ?></div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger py-1"><?= h($mensaje_error) ?></div>
    <?php endif; ?>

    <!-- Alta rápida de oportunidad -->
    <div class="card mb-3">
        <div class="card-header py-2">
            Nueva oportunidad
        </div>
        <div class="card-body py-2">
            <form method="post" class="row g-2 align-items-end">
                <input type="hidden" name="accion" value="guardar_opp">
                <?php if ($lead_actual): ?>
                    <input type="hidden" name="id_lead" value="<?= (int)($lead_actual['id_lead'] ?? 0) ?>">
                <?php else: ?>
                    <div class="col-md-2">
                        <label class="form-label mb-0">ID Lead (opcional)</label>
                        <input type="number" name="id_lead" class="form-control form-control-sm" placeholder="ID lead">
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label mb-0">Cliente (opcional)</label>
                    <select name="id_cliente" class="form-select form-select-sm">
                        <option value="">-- Sin cliente aún --</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= (int)$cli['id_cliente'] ?>">
                                <?= h(($cli['RazonSocial'] ?? '') . ' [' . ($cli['Cve_Clte'] ?? '') . ']') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-0">Título / Descripción corta *</label>
                    <input type="text" name="titulo" class="form-control form-control-sm" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-0">Valor estimado</label>
                    <input type="number" step="0.01" min="0" name="valor_estimado" class="form-control form-control-sm">
                </div>

                <div class="col-md-1">
                    <label class="form-label mb-0">% Prob.</label>
                    <input type="number" name="probabilidad" min="0" max="100" step="5"
                           class="form-control form-control-sm" value="10">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-0">Etapa</label>
                    <select name="etapa" class="form-select form-select-sm">
                        <?php foreach ($etapas_posibles as $et): ?>
                            <option value="<?= h($et) ?>"><?= h($et) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-0">Fecha cierre est.</label>
                    <input type="date" name="fecha_cierre_estimada" class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        Guardar oportunidad
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listado de oportunidades -->
    <div class="card">
        <div class="card-header py-2">
            Oportunidades <?= $lead_actual ? 'del Lead seleccionado' : ' (últimas 500)' ?>
        </div>
        <div class="card-body p-2">

            <!-- DESKTOP: TABLA -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered" id="tblOpps">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Título</th>
                                <th>Lead / Cliente</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">% Prob</th>
                                <th>Etapa</th>
                                <th>Fecha cierre est.</th>
                                <th>Asesor</th>
                                <th style="width: 180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($opps as $o): ?>
                                <tr>
                                    <td><?= h($o['fecha_crea'] ?? '') ?></td>
                                    <td><?= h($o['titulo'] ?? '') ?></td>
                                    <td>
                                        <?php if (!empty($o['nombre_contacto'])): ?>
                                            <div><strong>Lead: </strong><?= h($o['nombre_contacto']) ?></div>
                                            <?php if (!empty($o['empresa'])): ?>
                                                <div><small><?= h($o['empresa']) ?></small></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($o['cliente_nombre'])): ?>
                                            <div><strong>Cliente: </strong><?= h($o['cliente_nombre']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format((float)($o['valor_estimado'] ?? 0), 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= (int)($o['probabilidad'] ?? 0) ?>%
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= h($o['etapa'] ?? '') ?></span>
                                    </td>

                                    <!-- FIX PRINCIPAL: estos 2 venían NULL y tronaban en PHP 8.1+ -->
                                    <td><?= h($o['fecha_cierre_estimada'] ?? '') ?></td>
                                    <td><?= h($o['usuario_responsable'] ?? '') ?></td>

                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEtapa<?= (int)$o['id_opp'] ?>">
                                                Etapa
                                            </button>
                                            <a class="btn btn-success btn-sm"
                                               href="/assistpro_kardex_fc/public/procesos/cotizaciones.php?id_opp=<?= (int)$o['id_opp'] ?>">
                                                Cotización
                                            </a>
                                            <a class="btn btn-secondary btn-sm"
                                               href="/assistpro_kardex_fc/public/crm/actividades.php?id_opp=<?= (int)$o['id_opp'] ?>">
                                                Actividades
                                            </a>
                                        </div>

                                        <!-- Modal etapa -->
                                        <div class="modal fade" id="modalEtapa<?= (int)$o['id_opp'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form class="modal-content" method="post">
                                                        <input type="hidden" name="accion" value="cambiar_etapa">
                                                        <input type="hidden" name="id_opp" value="<?= (int)$o['id_opp'] ?>">

                                                        <div class="modal-header py-2">
                                                            <h5 class="modal-title">Cambiar etapa – OPP #<?= (int)$o['id_opp'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body" style="font-size:0.85rem;">
                                                            <p><strong><?= h($o['titulo'] ?? '') ?></strong></p>
                                                            <p>
                                                                Etapa actual:
                                                                <span class="badge bg-secondary"><?= h($o['etapa'] ?? '') ?></span>
                                                            </p>

                                                            <div class="mb-2">
                                                                <label class="form-label">Nueva etapa</label>
                                                                <select name="etapa_nueva" class="form-select form-select-sm" required>
                                                                    <option value="">-- Seleccione --</option>
                                                                    <?php foreach ($etapas_posibles as $et): ?>
                                                                        <option value="<?= h($et) ?>"
                                                                            <?= ($et === ($o['etapa'] ?? '')) ? 'selected' : '' ?>>
                                                                            <?= h($et) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="mb-2">
                                                                <label class="form-label">Comentario (opcional)</label>
                                                                <input type="text" name="comentario" class="form-control form-control-sm">
                                                            </div>

                                                        </div>

                                                        <div class="modal-footer py-2">
                                                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- /Modal -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MÓVIL: CARDS -->
            <div class="d-md-none">
                <?php if (empty($opps)): ?>
                    <div class="text-muted">No hay oportunidades registradas.</div>
                <?php endif; ?>

                <div class="row g-2">
                    <?php foreach ($opps as $o): ?>
                        <div class="col-12">
                            <div class="card border-secondary">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div><strong><?= h($o['titulo'] ?? '') ?></strong></div>
                                            <div>
                                                <small class="text-muted">
                                                    <?= h(substr((string)($o['fecha_crea'] ?? ''), 0, 16)) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-secondary"><?= h($o['etapa'] ?? '') ?></span>
                                            <div class="mt-1">
                                                <small><?= (int)($o['probabilidad'] ?? 0) ?>% prob.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($o['nombre_contacto']) || !empty($o['cliente_nombre'])): ?>
                                        <div class="mt-1">
                                            <?php if (!empty($o['nombre_contacto'])): ?>
                                                <div><small>Lead: <?= h($o['nombre_contacto']) ?></small></div>
                                            <?php endif; ?>
                                            <?php if (!empty($o['cliente_nombre'])): ?>
                                                <div><small>Cliente: <?= h($o['cliente_nombre']) ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-1 d-flex justify-content-between">
                                        <div>
                                            <small>Valor: <?= number_format((float)($o['valor_estimado'] ?? 0), 2) ?></small><br>
                                            <?php if (!empty($o['fecha_cierre_estimada'])): ?>
                                                <small>Cierre: <?= h($o['fecha_cierre_estimada']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <small>Asesor:</small><br>
                                            <small><?= h($o['usuario_responsable'] ?? '') ?></small>
                                        </div>
                                    </div>

                                    <div class="mt-2 d-flex justify-content-between">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEtapa<?= (int)$o['id_opp'] ?>">
                                            Etapa
                                        </button>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/assistpro_kardex_fc/public/procesos/cotizaciones.php?id_opp=<?= (int)$o['id_opp'] ?>"
                                               class="btn btn-success">
                                                COT
                                            </a>
                                            <a href="/assistpro_kardex_fc/public/crm/actividades.php?id_opp=<?= (int)$o['id_opp'] ?>"
                                               class="btn btn-secondary">
                                                ACT
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- Reuso del mismo modal de etapa definido arriba -->
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $("#tblOpps").DataTable({
            pageLength: 25,
            scrollY: "400px",
            scrollCollapse: true,
            ordering: true
        });
    }
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
