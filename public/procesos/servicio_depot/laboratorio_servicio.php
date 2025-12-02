<?php
// public/procesos/servicio_depot/laboratorio_servicio.php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/db.php';

if (session_status() === PHP_SESSION_NONE) {
    //@session_start();
}
require_once __DIR__ . '/../../bi/_menu_global.php';

$TITLE = 'Servicio – Laboratorio';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$usuarioActual = $_SESSION['usuario'] ?? 'SYSTEM';

$mensajeOk = null;
$mensajeErr = null;

// =======================
// Utilidades
// =======================

function get_servicio_by_id(PDO $pdo, int $id): ?array
{
    $sql = "SELECT s.*, a.des_almac,
                   c.RazonSocial AS cliente_nombre,
                   c.Cve_Clte    AS cliente_clave
            FROM th_servicio_caso s
            LEFT JOIN c_almacen a ON a.cve_almac = s.origen_almacen_id
            LEFT JOIN c_cliente c ON c.id_cliente = s.cliente_id
            WHERE s.id = :id";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function get_log_last_detail(PDO $pdo, int $servicioId, string $evento): ?string
{
    $sql = "SELECT detalle
            FROM td_servicio_caso_log
            WHERE servicio_id = :id AND evento = :evento
            ORDER BY fecha DESC, id DESC
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':id' => $servicioId,
        ':evento' => $evento,
    ]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (string) $row['detalle'] : null;
}

// =======================
// Parámetros
// =======================

$servicioIdSel = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// =======================
// POST: Acciones de laboratorio
// =======================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'actualizar_laboratorio') {
        $servicioId = (int) ($_POST['servicio_id'] ?? 0);
        $statusNuevo = trim($_POST['status_lab'] ?? '');
        $diagTexto = trim($_POST['diagnostico'] ?? '');
        $trabajoTexto = trim($_POST['trabajo_realizado'] ?? '');

        if ($servicioId <= 0) {
            $mensajeErr = 'ID de servicio inválido.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1) Actualizar status (si viene)
                if ($statusNuevo !== '') {
                    $sqlUpd = "UPDATE th_servicio_caso
                               SET status = :status
                               WHERE id = :id";
                    $stUpd = $pdo->prepare($sqlUpd);
                    $stUpd->execute([
                        ':status' => $statusNuevo,
                        ':id' => $servicioId,
                    ]);

                    // Log cambio de status
                    $sqlLog = "INSERT INTO td_servicio_caso_log
                               (servicio_id, fecha, usuario, evento, detalle, created_at, created_by)
                               VALUES
                               (:servicio_id, NOW(), :usuario, 'LAB_STATUS',
                                :detalle, NOW(), :created_by)";
                    $stLog = $pdo->prepare($sqlLog);
                    $stLog->execute([
                        ':servicio_id' => $servicioId,
                        ':usuario' => $usuarioActual,
                        ':detalle' => 'Cambio de status en laboratorio a: ' . $statusNuevo,
                        ':created_by' => $usuarioActual,
                    ]);
                }

                // 2) Diagnóstico
                if ($diagTexto !== '') {
                    $sqlLog = "INSERT INTO td_servicio_caso_log
                               (servicio_id, fecha, usuario, evento, detalle, created_at, created_by)
                               VALUES
                               (:servicio_id, NOW(), :usuario, 'LAB_DIAGNOSTICO',
                                :detalle, NOW(), :created_by)";
                    $stLog = $pdo->prepare($sqlLog);
                    $stLog->execute([
                        ':servicio_id' => $servicioId,
                        ':usuario' => $usuarioActual,
                        ':detalle' => $diagTexto,
                        ':created_by' => $usuarioActual,
                    ]);
                }

                // 3) Trabajo realizado
                if ($trabajoTexto !== '') {
                    $sqlLog = "INSERT INTO td_servicio_caso_log
                               (servicio_id, fecha, usuario, evento, detalle, created_at, created_by)
                               VALUES
                               (:servicio_id, NOW(), :usuario, 'LAB_TRABAJO',
                                :detalle, NOW(), :created_by)";
                    $stLog = $pdo->prepare($sqlLog);
                    $stLog->execute([
                        ':servicio_id' => $servicioId,
                        ':usuario' => $usuarioActual,
                        ':detalle' => $trabajoTexto,
                        ':created_by' => $usuarioActual,
                    ]);
                }

                $pdo->commit();
                $mensajeOk = 'Información de laboratorio actualizada correctamente.';
                $servicioIdSel = $servicioId;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensajeErr = 'Error al actualizar datos de laboratorio: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'agregar_parte') {
        $servicioId = (int) ($_POST['servicio_id'] ?? 0);
        $cveArticulo = trim($_POST['parte_articulo'] ?? '');
        $cantidad = (float) ($_POST['parte_cantidad'] ?? 0);
        $almacenOri = trim($_POST['parte_almacen'] ?? '');
        $nota = trim($_POST['parte_nota'] ?? '');

        if ($servicioId <= 0) {
            $mensajeErr = 'ID de servicio inválido.';
        } elseif ($cveArticulo === '' || $cantidad <= 0) {
            $mensajeErr = 'Debes indicar artículo y cantidad mayor a cero.';
        } else {
            try {
                $pdo->beginTransaction();

                $sqlIns = "INSERT INTO t_servicio_parte
                           (servicio_id, cve_articulo, cantidad, tipo_mov,
                            almacen_origen, status_surtido, nota,
                            created_at, created_by)
                           VALUES
                           (:servicio_id, :articulo, :cantidad, 'REQUERIDA',
                            :almacen, 'SOLICITADA', :nota,
                            NOW(), :created_by)";
                $stIns = $pdo->prepare($sqlIns);
                $stIns->execute([
                    ':servicio_id' => $servicioId,
                    ':articulo' => $cveArticulo,
                    ':cantidad' => $cantidad,
                    ':almacen' => $almacenOri ?: null,
                    ':nota' => $nota ?: null,
                    ':created_by' => $usuarioActual,
                ]);

                // Log
                $sqlLog = "INSERT INTO td_servicio_caso_log
                           (servicio_id, fecha, usuario, evento, detalle, created_at, created_by)
                           VALUES
                           (:servicio_id, NOW(), :usuario, 'LAB_PARTE_REQ',
                            :detalle, NOW(), :created_by)";
                $stLog = $pdo->prepare($sqlLog);
                $detalle = "Parte requerida: {$cveArticulo} x {$cantidad}" .
                    ($almacenOri ? " desde almacén {$almacenOri}" : '');
                $stLog->execute([
                    ':servicio_id' => $servicioId,
                    ':usuario' => $usuarioActual,
                    ':detalle' => $detalle,
                    ':created_by' => $usuarioActual,
                ]);

                $pdo->commit();
                $mensajeOk = 'Parte registrada como solicitada al almacén.';
                $servicioIdSel = $servicioId;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensajeErr = 'Error al registrar parte: ' . $e->getMessage();
            }
        }
    }
}

// =======================
// Lista de casos para laboratorio
// =======================

$sqlLista = "SELECT s.id, s.folio, s.fecha_alta, s.articulo, s.serie,
                    s.motivo, s.es_garantia, s.status,
                    a.des_almac,
                    c.RazonSocial AS cliente_nombre,
                    c.Cve_Clte    AS cliente_clave
             FROM th_servicio_caso s
             LEFT JOIN c_almacen a ON a.cve_almac = s.origen_almacen_id
             LEFT JOIN c_cliente c ON c.id_cliente = s.cliente_id
             WHERE s.status IN ('RECIBIDO_DEPOT','EN_LAB','EN_DIAGNOSTICO','EN_REPARACION','EN_PRUEBAS')
             ORDER BY s.fecha_alta DESC
             LIMIT 200";
$casosLab = $pdo->query($sqlLista)->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Detalle de servicio seleccionado
// =======================

$servicioSel = null;
$diagActual = null;
$trabActual = null;
$partes = [];

if ($servicioIdSel > 0) {
    $servicioSel = get_servicio_by_id($pdo, $servicioIdSel);
    if ($servicioSel) {
        $diagActual = get_log_last_detail($pdo, $servicioIdSel, 'LAB_DIAGNOSTICO');
        $trabActual = get_log_last_detail($pdo, $servicioIdSel, 'LAB_TRABAJO');

        $sqlPartes = "SELECT *
                      FROM t_servicio_parte
                      WHERE servicio_id = :id
                      ORDER BY created_at ASC, id ASC";
        $stP = $pdo->prepare($sqlPartes);
        $stP->execute([':id' => $servicioIdSel]);
        $partes = $stP->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
<div class="container-fluid mt-3">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-end">
            <div>
                <h4 class="mb-0">Servicio – Laboratorio</h4>
                <small class="text-muted">
                    Diagnóstico, reparación y control de refacciones por caso.
                </small>
            </div>
            <div class="text-end" style="font-size:0.8rem;">
                <a href="recepcion.php" class="btn btn-outline-secondary btn-sm">
                    &laquo; Recepción Depot
                </a>
                <a href="admin_ingenieria_servicio.php" class="btn btn-outline-primary btn-sm">
                    Panel de ingeniería
                </a>
            </div>
        </div>
    </div>

    <?php if ($mensajeOk): ?>
        <div class="alert alert-success alert-sm py-2"><?= $mensajeOk ?></div>
    <?php endif; ?>
    <?php if ($mensajeErr): ?>
        <div class="alert alert-danger alert-sm py-2"><?= $mensajeErr ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Lista de casos -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                    <strong>Casos pendientes / en laboratorio</strong>
                </div>
                <div class="card-body p-2" style="font-size:0.8rem;">
                    <div class="table-responsive" style="max-height:480px; overflow:auto;">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Artículo</th>
                                    <th>Serie</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($casosLab)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            No hay casos pendientes para laboratorio.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($casosLab as $c): ?>
                                        <tr <?= ($servicioIdSel === (int) $c['id']) ? 'class="table-primary"' : '' ?>>
                                            <td><?= htmlspecialchars($c['folio']) ?></td>
                                            <td><?= htmlspecialchars($c['fecha_alta']) ?></td>
                                            <td>
                                                <?= '[' . htmlspecialchars($c['cliente_clave'] ?? '') . '] ' .
                                                    htmlspecialchars($c['cliente_nombre'] ?? '') ?>
                                            </td>
                                            <td><?= htmlspecialchars($c['articulo']) ?></td>
                                            <td><?= htmlspecialchars($c['serie']) ?></td>
                                            <td><?= htmlspecialchars($c['status']) ?></td>
                                            <td>
                                                <a href="laboratorio_servicio.php?id=<?= (int) $c['id'] ?>"
                                                    class="btn btn-outline-primary btn-sm btn-icon">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-1">
                        * Se muestran hasta 200 casos. En siguiente fase agregamos filtros por laboratorio, rango de
                        fechas y usuario.
                    </small>
                </div>
            </div>
        </div>

        <!-- Detalle laboratorio -->
        <div class="col-lg-8">
            <?php if (!$servicioSel): ?>
                <div class="card shadow-sm">
                    <div class="card-body" style="font-size:0.85rem;">
                        Selecciona un caso en la lista de la izquierda para capturar
                        diagnóstico, trabajo realizado y refacciones.
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Detalle del caso</strong><br>
                            <small class="text-muted">
                                Folio <?= htmlspecialchars($servicioSel['folio']) ?> ·
                                Status actual: <strong><?= htmlspecialchars($servicioSel['status']) ?></strong>
                            </small>
                        </div>
                        <div>
                            <?php
                            // botón de cotización solo para SERVICIO (no garantía)
                            if (isset($servicioSel['motivo']) && strtoupper($servicioSel['motivo']) === 'SERVICIO'):
                                ?>
                                <a href="servicio_generar_cotizacion.php?id=<?= (int) $servicioSel['id'] ?>"
                                    class="btn btn-warning btn-sm" style="font-size:0.75rem;">
                                    Generar Cotización CRM
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body" style="font-size:0.8rem;">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div><strong>Cliente:</strong></div>
                                <div>
                                    <?= '[' . htmlspecialchars($servicioSel['cliente_clave'] ?? '') . '] ' .
                                        htmlspecialchars($servicioSel['cliente_nombre'] ?? '') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>Almacén origen (Depot):</strong></div>
                                <div><?= htmlspecialchars($servicioSel['des_almac'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div><strong>Artículo:</strong></div>
                                <div><?= htmlspecialchars($servicioSel['articulo']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>Serie:</strong></div>
                                <div><?= htmlspecialchars($servicioSel['serie']) ?></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <div><strong>Motivo:</strong></div>
                                <div><?= htmlspecialchars($servicioSel['motivo']) ?></div>
                            </div>
                            <div class="col-md-4">
                                <div><strong>Garantía:</strong></div>
                                <div>
                                    <?php if ((int) $servicioSel['es_garantia'] === 1): ?>
                                        <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div><strong>Fecha alta:</strong></div>
                                <div><?= htmlspecialchars($servicioSel['fecha_alta']) ?></div>
                            </div>
                        </div>

                        <hr class="my-2">

                        <!-- Form de laboratorio -->
                        <form method="post" class="mb-3">
                            <input type="hidden" name="action" value="actualizar_laboratorio">
                            <input type="hidden" name="servicio_id" value="<?= (int) $servicioSel['id'] ?>">

                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label mb-1">Status en laboratorio</label>
                                    <select name="status_lab" class="form-select form-select-sm">
                                        <option value="">[Sin cambio]</option>
                                        <?php
                                        $statusOpc = [
                                            'EN_LAB' => 'En laboratorio',
                                            'EN_DIAGNOSTICO' => 'En diagnóstico',
                                            'EN_REPARACION' => 'En reparación',
                                            'EN_PRUEBAS' => 'En pruebas',
                                            'LISTO_ENTREGA' => 'Listo para entrega / envío',
                                        ];
                                        foreach ($statusOpc as $val => $txt):
                                            ?>
                                            <option value="<?= $val ?>" <?= ($servicioSel['status'] === $val ? 'selected' : '') ?>>
                                                <?= $txt ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label mb-1">Diagnóstico de laboratorio</label>
                                <textarea name="diagnostico" rows="3" class="form-control form-control-sm"
                                    placeholder="Descripción técnica de la falla detectada..."><?= htmlspecialchars($diagActual ?? '') ?></textarea>
                                <small class="text-muted">
                                    Se guarda en la bitácora como evento <code>LAB_DIAGNOSTICO</code>.
                                </small>
                            </div>

                            <div class="mb-2">
                                <label class="form-label mb-1">Trabajo realizado / acciones correctivas</label>
                                <textarea name="trabajo_realizado" rows="3" class="form-control form-control-sm"
                                    placeholder="Componentes reemplazados, procedimientos realizados, ajustes, etc."><?= htmlspecialchars($trabActual ?? '') ?></textarea>
                                <small class="text-muted">
                                    Se guarda en la bitácora como evento <code>LAB_TRABAJO</code>.
                                </small>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Guardar información de laboratorio
                                </button>
                            </div>
                        </form>

                        <hr class="my-2">

                        <!-- Partes / refacciones -->
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <h6 class="mb-1">Partes / refacciones requeridas</h6>
                                <form method="post" class="border rounded p-2 bg-light">
                                    <input type="hidden" name="action" value="agregar_parte">
                                    <input type="hidden" name="servicio_id" value="<?= (int) $servicioSel['id'] ?>">

                                    <div class="mb-2">
                                        <label class="form-label mb-1">Almacén origen</label>
                                        <select id="selAlmacenLab" name="parte_almacen" class="form-select form-select-sm">
                                            <option value="">[Selecciona almacén]</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label mb-1">Artículo</label>
                                        <select id="selArticuloLab" name="parte_articulo" class="form-select form-select-sm"
                                            required>
                                            <option value="">[Selecciona producto]</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label mb-1">Cantidad</label>
                                        <input type="number" name="parte_cantidad" step="0.01" min="0"
                                            class="form-control form-control-sm" required>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label mb-1">Nota (opcional)</label>
                                        <input type="text" name="parte_nota" class="form-control form-control-sm">
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            Registrar parte requerida
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        En esta fase solo registramos la solicitud. En la siguiente
                                        integraremos el enlace automático con el módulo de picking / traslados.
                                    </small>
                                </form>
                            </div>
                            <div class="col-md-6 mb-2">
                                <h6 class="mb-1">Histórico de partes del caso</h6>
                                <div class="table-responsive" style="max-height:240px; overflow:auto;">
                                    <table class="table table-sm table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Artículo</th>
                                                <th>Cant.</th>
                                                <th>Almacén</th>
                                                <th>Estatus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($partes)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        Sin partes registradas para este caso.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($partes as $p): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($p['created_at']) ?></td>
                                                        <td><?= htmlspecialchars($p['cve_articulo']) ?></td>
                                                        <td><?= htmlspecialchars($p['cantidad']) ?></td>
                                                        <td><?= htmlspecialchars($p['almacen_origen'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($p['status_surtido']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Más adelante podremos marcar aquí cuando el almacén surta o consuma la parte
                                    (ligado a la orden de surtido / picking).
                                </small>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Cargar almacenes y productos desde public/api/filtros_assistpro.php
    document.addEventListener('DOMContentLoaded', function () {
        const apiUrl = '../../api/filtros_assistpro.php?action=init';

        fetch(apiUrl, { method: 'GET' })
            .then(resp => resp.json())
            .then(data => {
                if (!data || data.ok === false) {
                    console.error('Error en filtros_assistpro:', data && data.error);
                    return;
                }

                // Almacenes para laboratorio
                const selAlm = document.getElementById('selAlmacenLab');
                if (selAlm) {
                    if (!selAlm.options.length) {
                        selAlm.innerHTML = '<option value="">[Selecciona almacén]</option>';
                    }
                    if (Array.isArray(data.almacenes)) {
                        data.almacenes.forEach(a => {
                            const opt = document.createElement('option');
                            opt.value = a.cve_almac;
                            opt.textContent = a.des_almac || a.clave_almacen || a.cve_almac;
                            selAlm.appendChild(opt);
                        });
                    }
                }

                // Productos para partes
                const selArt = document.getElementById('selArticuloLab');
                if (selArt) {
                    selArt.innerHTML = '<option value="">[Selecciona producto]</option>';
                    if (Array.isArray(data.productos)) {
                        data.productos.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.cve_articulo;
                            opt.textContent = '[' + p.cve_articulo + '] ' + p.des_articulo;
                            selArt.appendChild(opt);
                        });
                    }
                }
            })
            .catch(err => {
                console.error('Error cargando filtros_assistpro:', err);
            });
    });
</script>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
