<?php
// public/procesos/servicio_depot/reporte_diagnostico.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../bi/_menu_global.php';

$TITLE = 'Servicio – Reporte de Diagnóstico';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$usuarioActual = $_SESSION['usuario'] ?? 'SYSTEM';

$mensajeOk  = null;
$mensajeErr = null;

// =======================
// Utilidades
// =======================

function get_servicio_by_id(PDO $pdo, int $id): ?array {
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

function get_diagnostico(PDO $pdo, int $servicioId): ?array {
    $sql = "SELECT * FROM t_servicio_diagnostico WHERE servicio_id = :id";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $servicioId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// convertir dd/mm/yyyy → yyyy-mm-dd
function parse_fecha_dmy(?string $d): ?string {
    $d = trim((string)$d);
    if ($d === '') return null;
    if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $d, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $d)) {
        return $d;
    }
    return null;
}

function fecha_a_dmy(?string $d): string {
    if (!$d) return '';
    if (preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $d, $m)) {
        return "{$m[3]}/{$m[2]}/{$m[1]}";
    }
    return $d;
}

// =======================
// Parámetro principal
// =======================

$servicioIdSel = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// =======================
// POST acciones
// =======================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'guardar_diag') {
        $servicioId   = (int)($_POST['servicio_id'] ?? 0);
        $diagTxt      = trim($_POST['diagnostico'] ?? '');
        $causaTxt     = trim($_POST['causa_raiz'] ?? '');
        $tiempoEst    = (float)($_POST['tiempo_estimado_horas'] ?? 0);
        $tiempoReal   = (float)($_POST['tiempo_real_horas'] ?? 0);
        $fechaEstStr  = $_POST['fecha_estim_entrega'] ?? '';
        $fechaRealStr = $_POST['fecha_real_entrega'] ?? '';
        $costoMO      = (float)($_POST['costo_mano_obra'] ?? 0);
        $costoMat     = (float)($_POST['costo_materiales'] ?? 0);
        $costoTot     = $costoMO + $costoMat;

        if ($servicioId <= 0) {
            $mensajeErr = 'ID de servicio inválido.';
        } else {
            try {
                $fechaEst  = parse_fecha_dmy($fechaEstStr);
                $fechaReal = parse_fecha_dmy($fechaRealStr);

                $pdo->beginTransaction();

                $exist = get_diagnostico($pdo, $servicioId);
                if ($exist) {
                    $sqlUpd = "UPDATE t_servicio_diagnostico
                               SET diagnostico = :diag,
                                   causa_raiz = :causa,
                                   tiempo_estimado_horas = :test,
                                   tiempo_real_horas     = :treal,
                                   fecha_estim_entrega   = :fest,
                                   fecha_real_entrega    = :freal,
                                   costo_mano_obra       = :cmo,
                                   costo_materiales      = :cmat,
                                   costo_total           = :ctot,
                                   updated_at            = NOW(),
                                   updated_by            = :updby
                               WHERE id = :id";
                    $stUpd = $pdo->prepare($sqlUpd);
                    $stUpd->execute([
                        ':diag'   => $diagTxt ?: null,
                        ':causa'  => $causaTxt ?: null,
                        ':test'   => $tiempoEst ?: null,
                        ':treal'  => $tiempoReal ?: null,
                        ':fest'   => $fechaEst,
                        ':freal'  => $fechaReal,
                        ':cmo'    => $costoMO,
                        ':cmat'   => $costoMat,
                        ':ctot'   => $costoTot,
                        ':updby'  => $usuarioActual,
                        ':id'     => (int)$exist['id'],
                    ]);
                } else {
                    $sqlIns = "INSERT INTO t_servicio_diagnostico (
                                    servicio_id,
                                    diagnostico, causa_raiz,
                                    tiempo_estimado_horas, tiempo_real_horas,
                                    fecha_estim_entrega, fecha_real_entrega,
                                    costo_mano_obra, costo_materiales, costo_total,
                                    created_at, created_by
                               ) VALUES (
                                    :servicio_id,
                                    :diag, :causa,
                                    :test, :treal,
                                    :fest, :freal,
                                    :cmo, :cmat, :ctot,
                                    NOW(), :created_by
                               )";
                    $stIns = $pdo->prepare($sqlIns);
                    $stIns->execute([
                        ':servicio_id' => $servicioId,
                        ':diag'        => $diagTxt ?: null,
                        ':causa'       => $causaTxt ?: null,
                        ':test'        => $tiempoEst ?: null,
                        ':treal'       => $tiempoReal ?: null,
                        ':fest'        => $fechaEst,
                        ':freal'       => $fechaReal,
                        ':cmo'         => $costoMO,
                        ':cmat'        => $costoMat,
                        ':ctot'        => $costoTot,
                        ':created_by'  => $usuarioActual,
                    ]);
                }

                $pdo->commit();
                $mensajeOk = 'Reporte de diagnóstico guardado correctamente.';
                $servicioIdSel = $servicioId;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensajeErr = 'Error al guardar diagnóstico: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'agregar_mat') {
        $servicioId  = (int)($_POST['servicio_id'] ?? 0);
        $diagId      = (int)($_POST['diagnostico_id'] ?? 0);
        $cveArt      = trim($_POST['cve_articulo'] ?? '');
        $cant        = (float)($_POST['cantidad'] ?? 0);
        $costoUnit   = (float)($_POST['costo_unitario'] ?? 0);
        $costoTot    = $cant * $costoUnit;

        if ($servicioId <= 0 || $diagId <= 0) {
            $mensajeErr = 'Servicio o diagnóstico inválido.';
        } elseif ($cveArt === '' || $cant <= 0) {
            $mensajeErr = 'Debes indicar artículo y cantidad.';
        } else {
            try {
                $sql = "INSERT INTO t_servicio_diagnostico_material
                        (diagnostico_id, servicio_id, cve_articulo, cantidad,
                         costo_unitario, costo_total, created_at, created_by)
                        VALUES
                        (:diag_id, :serv_id, :art, :cant,
                         :cu, :ctot, NOW(), :cb)";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':diag_id' => $diagId,
                    ':serv_id' => $servicioId,
                    ':art'     => $cveArt,
                    ':cant'    => $cant,
                    ':cu'      => $costoUnit,
                    ':ctot'    => $costoTot,
                    ':cb'      => $usuarioActual,
                ]);
                $mensajeOk = 'Material agregado al diagnóstico.';
                $servicioIdSel = $servicioId;
            } catch (Throwable $e) {
                $mensajeErr = 'Error al agregar material: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'agregar_mo') {
        $servicioId = (int)($_POST['servicio_id'] ?? 0);
        $diagId     = (int)($_POST['diagnostico_id'] ?? 0);
        $tecnico    = trim($_POST['tecnico'] ?? '');
        $horas      = (float)($_POST['horas'] ?? 0);
        $costoHora  = (float)($_POST['costo_hora'] ?? 0);
        $costoTot   = $horas * $costoHora;

        if ($servicioId <= 0 || $diagId <= 0) {
            $mensajeErr = 'Servicio o diagnóstico inválido.';
        } elseif ($tecnico === '' || $horas <= 0) {
            $mensajeErr = 'Debes indicar técnico y horas.';
        } else {
            try {
                $sql = "INSERT INTO t_servicio_diagnostico_manoobra
                        (diagnostico_id, servicio_id, tecnico, horas,
                         costo_hora, costo_total, created_at, created_by)
                        VALUES
                        (:diag_id, :serv_id, :tec, :hrs,
                         :ch, :ctot, NOW(), :cb)";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':diag_id' => $diagId,
                    ':serv_id' => $servicioId,
                    ':tec'     => $tecnico,
                    ':hrs'     => $horas,
                    ':ch'      => $costoHora,
                    ':ctot'    => $costoTot,
                    ':cb'      => $usuarioActual,
                ]);
                $mensajeOk = 'Mano de obra agregada al diagnóstico.';
                $servicioIdSel = $servicioId;
            } catch (Throwable $e) {
                $mensajeErr = 'Error al agregar mano de obra: ' . $e->getMessage();
            }
        }
    }
}

// =======================
// Lista de casos (pendientes / con diagnóstico)
// =======================

$sqlLista = "SELECT s.id, s.folio, s.fecha_alta, s.articulo, s.serie,
                    s.motivo, s.es_garantia, s.status,
                    a.des_almac,
                    c.RazonSocial AS cliente_nombre,
                    c.Cve_Clte    AS cliente_clave
             FROM th_servicio_caso s
             LEFT JOIN c_almacen a ON a.cve_almac = s.origen_almacen_id
             LEFT JOIN c_cliente c ON c.id_cliente = s.cliente_id
             WHERE s.status IN ('EN_DIAGNOSTICO','EN_REPARACION','EN_PRUEBAS','EN_ESPERA_AUTORIZACION','LISTO_ENTREGA')
             ORDER BY s.fecha_alta DESC
             LIMIT 200";
$casos = $pdo->query($sqlLista)->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Detalle del caso y diagnóstico seleccionado
// =======================

$servicioSel = null;
$diag        = null;
$materiales  = [];
$manoObra    = [];

if ($servicioIdSel > 0) {
    $servicioSel = get_servicio_by_id($pdo, $servicioIdSel);
    if ($servicioSel) {
        $diag = get_diagnostico($pdo, $servicioIdSel);

        if ($diag) {
            $sqlMat = "SELECT * FROM t_servicio_diagnostico_material
                       WHERE diagnostico_id = :id
                       ORDER BY created_at ASC, id ASC";
            $stM = $pdo->prepare($sqlMat);
            $stM->execute([':id' => (int)$diag['id']]);
            $materiales = $stM->fetchAll(PDO::FETCH_ASSOC);

            $sqlMO = "SELECT * FROM t_servicio_diagnostico_manoobra
                      WHERE diagnostico_id = :id
                      ORDER BY created_at ASC, id ASC";
            $stMO = $pdo->prepare($sqlMO);
            $stMO->execute([':id' => (int)$diag['id']]);
            $manoObra = $stMO->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

?>
<div class="container-fluid mt-3">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-end">
            <div>
                <h4 class="mb-0">Servicio – Reporte de Diagnóstico</h4>
                <small class="text-muted">
                    Resumen técnico, materiales, mano de obra y tiempo de devolución.
                </small>
            </div>
            <div class="text-end" style="font-size:0.8rem;">
                <a href="laboratorio_servicio.php" class="btn btn-outline-secondary btn-sm">
                    &laquo; Laboratorio
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
                    <strong>Casos para diagnóstico</strong>
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
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($casos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            No hay casos en diagnóstico / reparación.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($casos as $c): ?>
                                        <tr <?= ($servicioIdSel === (int)$c['id']) ? 'class="table-primary"' : '' ?>>
                                            <td><?= htmlspecialchars($c['folio']) ?></td>
                                            <td><?= htmlspecialchars($c['fecha_alta']) ?></td>
                                            <td>
                                                <?= '[' . htmlspecialchars($c['cliente_clave'] ?? '') . '] ' .
                                                     htmlspecialchars($c['cliente_nombre'] ?? '') ?>
                                            </td>
                                            <td><?= htmlspecialchars($c['articulo']) ?></td>
                                            <td><?= htmlspecialchars($c['serie']) ?></td>
                                            <td>
                                                <a href="reporte_diagnostico.php?id=<?= (int)$c['id'] ?>"
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
                        * Puedes filtrar en siguientes fases por laboratorio, fechas o status.
                    </small>
                </div>
            </div>
        </div>

        <!-- Detalle diagnóstico -->
        <div class="col-lg-8">
            <?php if (!$servicioSel): ?>
                <div class="card shadow-sm">
                    <div class="card-body" style="font-size:0.85rem;">
                        Selecciona un caso en la lista de la izquierda para capturar o revisar su reporte de diagnóstico.
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Diagnóstico del caso</strong><br>
                            <small class="text-muted">
                                Folio <?= htmlspecialchars($servicioSel['folio']) ?> ·
                                Status actual: <strong><?= htmlspecialchars($servicioSel['status']) ?></strong>
                            </small>
                        </div>
                        <div>
                            <?php if (isset($servicioSel['motivo']) && strtoupper($servicioSel['motivo']) === 'SERVICIO'): ?>
                                <a href="servicio_generar_cotizacion.php?id=<?= (int)$servicioSel['id'] ?>"
                                   class="btn btn-warning btn-sm"
                                   style="font-size:0.75rem;">
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

                        <hr class="my-2">

                        <!-- Form principal de diagnóstico -->
                        <form method="post" class="mb-3">
                            <input type="hidden" name="action" value="guardar_diag">
                            <input type="hidden" name="servicio_id" value="<?= (int)$servicioSel['id'] ?>">

                            <div class="mb-2">
                                <label class="form-label mb-1">Diagnóstico técnico</label>
                                <textarea name="diagnostico" rows="3"
                                          class="form-control form-control-sm"
                                          placeholder="Descripción técnica de la falla, hallazgos, condiciones de prueba..."><?= htmlspecialchars($diag['diagnostico'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label mb-1">Causa raíz</label>
                                <textarea name="causa_raiz" rows="2"
                                          class="form-control form-control-sm"
                                          placeholder="Causa raíz identificada (componente defectuoso, condiciones de uso, etc.)"><?= htmlspecialchars($diag['causa_raiz'] ?? '') ?></textarea>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label class="form-label mb-1">Tiempo estimado (hrs)</label>
                                    <input type="number" step="0.1" min="0"
                                           name="tiempo_estimado_horas"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars((string)($diag['tiempo_estimado_horas'] ?? '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1">Tiempo real (hrs)</label>
                                    <input type="number" step="0.1" min="0"
                                           name="tiempo_real_horas"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars((string)($diag['tiempo_real_horas'] ?? '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1">Fecha estimada devolución</label>
                                    <input type="text" name="fecha_estim_entrega"
                                           class="form-control form-control-sm"
                                           placeholder="dd/mm/aaaa"
                                           value="<?= htmlspecialchars(fecha_a_dmy($diag['fecha_estim_entrega'] ?? null)) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1">Fecha real devolución</label>
                                    <input type="text" name="fecha_real_entrega"
                                           class="form-control form-control-sm"
                                           placeholder="dd/mm/aaaa"
                                           value="<?= htmlspecialchars(fecha_a_dmy($diag['fecha_real_entrega'] ?? null)) ?>">
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Costo mano de obra</label>
                                    <input type="number" step="0.01" min="0"
                                           name="costo_mano_obra"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars((string)($diag['costo_mano_obra'] ?? '0')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Costo materiales</label>
                                    <input type="number" step="0.01" min="0"
                                           name="costo_materiales"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars((string)($diag['costo_materiales'] ?? '0')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Costo total</label>
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars(number_format((float)($diag['costo_total'] ?? 0), 2)) ?>"
                                           readonly>
                                </div>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Guardar reporte de diagnóstico
                                </button>
                            </div>
                        </form>

                        <?php if ($diag): ?>
                            <hr class="my-2">

                            <div class="row">
                                <!-- Materiales -->
                                <div class="col-md-6 mb-2">
                                    <h6 class="mb-1">Materiales del diagnóstico</h6>
                                    <form method="post" class="border rounded p-2 bg-light mb-2">
                                        <input type="hidden" name="action" value="agregar_mat">
                                        <input type="hidden" name="servicio_id" value="<?= (int)$servicioSel['id'] ?>">
                                        <input type="hidden" name="diagnostico_id" value="<?= (int)$diag['id'] ?>">

                                        <div class="mb-2">
                                            <label class="form-label mb-1">Artículo</label>
                                            <input type="text" name="cve_articulo"
                                                   class="form-control form-control-sm"
                                                   placeholder="Clave de artículo (o integrarlo a combo en siguiente fase)" required>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label mb-1">Cantidad</label>
                                            <input type="number" step="0.01" min="0"
                                                   name="cantidad"
                                                   class="form-control form-control-sm" required>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label mb-1">Costo unitario</label>
                                            <input type="number" step="0.01" min="0"
                                                   name="costo_unitario"
                                                   class="form-control form-control-sm">
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                Agregar material
                                            </button>
                                        </div>
                                    </form>

                                    <div class="table-responsive" style="max-height:220px; overflow:auto;">
                                        <table class="table table-sm table-striped align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Artículo</th>
                                                    <th>Cant.</th>
                                                    <th>C.Unit</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($materiales)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">
                                                            Sin materiales registrados.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($materiales as $m): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($m['created_at']) ?></td>
                                                            <td><?= htmlspecialchars($m['cve_articulo']) ?></td>
                                                            <td><?= htmlspecialchars($m['cantidad']) ?></td>
                                                            <td><?= htmlspecialchars(number_format((float)$m['costo_unitario'], 2)) ?></td>
                                                            <td><?= htmlspecialchars(number_format((float)$m['costo_total'], 2)) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Mano de obra -->
                                <div class="col-md-6 mb-2">
                                    <h6 class="mb-1">Mano de obra</h6>
                                    <form method="post" class="border rounded p-2 bg-light mb-2">
                                        <input type="hidden" name="action" value="agregar_mo">
                                        <input type="hidden" name="servicio_id" value="<?= (int)$servicioSel['id'] ?>">
                                        <input type="hidden" name="diagnostico_id" value="<?= (int)$diag['id'] ?>">

                                        <div class="mb-2">
                                            <label class="form-label mb-1">Técnico</label>
                                            <input type="text" name="tecnico"
                                                   class="form-control form-control-sm" required>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label mb-1">Horas</label>
                                            <input type="number" step="0.1" min="0"
                                                   name="horas"
                                                   class="form-control form-control-sm" required>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label mb-1">Costo por hora</label>
                                            <input type="number" step="0.01" min="0"
                                                   name="costo_hora"
                                                   class="form-control form-control-sm">
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                Agregar mano de obra
                                            </button>
                                        </div>
                                    </form>

                                    <div class="table-responsive" style="max-height:220px; overflow:auto;">
                                        <table class="table table-sm table-striped align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Técnico</th>
                                                    <th>Horas</th>
                                                    <th>C.Hora</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($manoObra)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">
                                                            Sin mano de obra registrada.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($manoObra as $mo): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($mo['created_at']) ?></td>
                                                            <td><?= htmlspecialchars($mo['tecnico']) ?></td>
                                                            <td><?= htmlspecialchars($mo['horas']) ?></td>
                                                            <td><?= htmlspecialchars(number_format((float)$mo['costo_hora'], 2)) ?></td>
                                                            <td><?= htmlspecialchars(number_format((float)$mo['costo_total'], 2)) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-2" style="font-size:0.8rem;">
                                Guarda primero el diagnóstico general para habilitar el detalle de materiales y mano de obra.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
