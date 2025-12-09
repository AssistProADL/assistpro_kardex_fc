<?php
// public/ingresos/orden_compra.php

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die('Error de conexión a base de datos: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/**
 * Carga almacenes directamente desde th_aduana (almacenes que ya tienen OCs)
 */
function cargarAlmacenes(PDO $pdo): array
{
    try {
        $rows = $pdo->query("
            SELECT DISTINCT Cve_Almac AS Clave_Almacen
            FROM th_aduana
            WHERE Cve_Almac IS NOT NULL AND Cve_Almac <> ''
            ORDER BY Cve_Almac
        ")->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $clave = trim((string)$r['Clave_Almacen']);
            if ($clave === '') {
                continue;
            }

            $out[] = [
                'Clave'       => $clave,
                'Descripcion' => $clave,
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Carga monedas desde th_aduana (distintas Id_moneda existentes).
 */
function cargarMonedas(PDO $pdo): array
{
    try {
        $rows = $pdo->query("
            SELECT DISTINCT Id_moneda
            FROM th_aduana
            WHERE Id_moneda IS NOT NULL
            ORDER BY Id_moneda
        ")->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r['Id_moneda'];
            if ($id <= 0) {
                continue;
            }
            $out[] = [
                'Id_moneda' => $id,
                'Nombre'    => $id == 1 ? 'MXN' : 'USD',
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Carga protocolos desde t_protocolo para el combo de filtro.
 */
function cargarProtocolos(PDO $pdo): array
{
    try {
        $rows = $pdo->query("
            SELECT ID_Protocolo, descripcion
            FROM t_protocolo
            WHERE Activo = 1
            ORDER BY ID_Protocolo
        ")->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'ID_Protocolo' => $r['ID_Protocolo'],
                'Descripcion'  => $r['descripcion'],
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

// ================== LECTURA DE FILTROS =================

$isFirstLoad = empty($_GET); // primera vez que entras a la vista

$almacen    = isset($_GET['almacen'])      ? trim($_GET['almacen'])      : '';
$fechaDesde = isset($_GET['fecha_desde'])  ? trim($_GET['fecha_desde'])  : '';
$fechaHasta = isset($_GET['fecha_hasta'])  ? trim($_GET['fecha_hasta'])  : '';
$status     = isset($_GET['status'])       ? trim($_GET['status'])       : '';
$moneda     = isset($_GET['moneda'])       ? trim($_GET['moneda'])       : '';
$protocolo  = isset($_GET['protocolo'])    ? trim($_GET['protocolo'])    : '';

// Fechas por default: últimos 7 días (solo en primera carga)
if ($isFirstLoad && $fechaDesde === '' && $fechaHasta === '') {
    $fechaDesde = date('d/m/Y', strtotime('-7 days'));
    $fechaHasta = date('d/m/Y');
}

// combos
$almacenes = cargarAlmacenes($pdo);
$monedas   = cargarMonedas($pdo);
$protRows  = cargarProtocolos($pdo);

$where  = [];
$params = [];

// Almacén
if ($almacen !== '') {
    $where[]            = 'h.Cve_Almac = :almacen';
    $params[':almacen'] = $almacen;
}

// Rango de fechas (Fecha OC = fech_pedimento)
if ($fechaDesde !== '') {
    $where[]               = 'DATE(h.fech_pedimento) >= STR_TO_DATE(:fdesde, "%d/%m/%Y")';
    $params[':fdesde']     = $fechaDesde;
}
if ($fechaHasta !== '') {
    $where[]               = 'DATE(h.fech_pedimento) <= STR_TO_DATE(:fhasta, "%d/%m/%Y")';
    $params[':fhasta']     = $fechaHasta;
}

// Status
if ($status !== '') {
    $where[]            = 'h.status = :status';
    $params[':status']  = $status;
}

// Moneda
if ($moneda !== '') {
    $where[]            = 'h.Id_moneda = :moneda';
    $params[':moneda']  = $moneda;
}

// Protocolo (vía ID_Protocolo)
if ($protocolo !== '') {
    $where[]               = 'h.ID_Protocolo = :protocolo';
    $params[':protocolo']  = $protocolo;
}

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $sql = "
        SELECT
            h.ID_Aduana,
            h.Pedimento,
            h.num_pedimento,
            h.fech_pedimento,
            h.fech_llegPed,
            h.status,
            h.ID_Proveedor,
            h.ID_Protocolo,
            h.Cve_Almac,
            h.Tipo_Cambio,
            h.Id_moneda,
            p.Nombre        AS proveedor,
            pr.descripcion  AS protocolo_desc,
            pr.ID_Protocolo AS protocolo_clave,
            COALESCE(d.partidas, 0) AS partidas
        FROM th_aduana h
        LEFT JOIN c_proveedores p
            ON p.ID_Proveedor = h.ID_Proveedor
        LEFT JOIN t_protocolo pr
            ON pr.ID_Protocolo = h.ID_Protocolo
        LEFT JOIN (
            SELECT ID_Aduana, COUNT(*) AS partidas
            FROM td_aduana
            GROUP BY ID_Aduana
        ) d
            ON d.ID_Aduana = h.ID_Aduana
        $sqlWhere
        ORDER BY h.ID_Aduana DESC
        LIMIT 25
    ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $ocs = $st->fetchAll(PDO::FETCH_ASSOC);
    $errorConsulta = '';
} catch (Throwable $e) {
    $errorConsulta = $e->getMessage();
    $ocs           = [];
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- LineAwesome (si ya lo cargas globalmente, puedes quitar este link) -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css"/>

<style>
    /* Estilo AssistPro para grillas: 10px, una línea, scroll H/V */
    .ap-oc-table-wrapper {
        max-height: 420px;
        overflow: auto; /* scroll horizontal y vertical */
    }
    .ap-oc-table {
        font-size: 10px;
        margin-bottom: 0;
    }
    .ap-oc-table th,
    .ap-oc-table td {
        white-space: nowrap; /* una sola línea por registro */
        padding: 4px 6px;
        vertical-align: middle;
    }
    .ap-oc-table thead th {
        background-color: #f4f6fb;
        font-weight: 600;
    }
    .ap-oc-table tbody tr:hover {
        background-color: #f7faff;
    }
    .ap-oc-actions .btn {
        padding: 2px 4px;
        font-size: 10px;
        line-height: 1.2;
    }
    .ap-oc-header-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .ap-oc-header-subtitle {
        font-size: 11px;
        color: #666;
        margin-bottom: 0;
    }
    .ap-label {
        font-size: 10px;
        font-weight: 600;
    }
</style>

<div class="ap-wrapper">
    <div class="ap-header mb-3">
        <h1 class="ap-oc-header-title">AssistPro SFA — Órdenes de Compra</h1>
        <p class="ap-oc-header-subtitle">
            Módulo AssistPro SFA &amp; Ingresos — Administración de Órdenes de Compra (th_aduana / td_aduana).
        </p>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Almacén</label>
                    <select class="form-select form-select-sm" name="almacen">
                        <option value="">Todos</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?php echo htmlspecialchars((string)$a['Clave'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo ($almacen === $a['Clave']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$a['Clave'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Fecha desde</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           name="fecha_desde"
                           placeholder="dd/mm/aaaa"
                           value="<?php echo htmlspecialchars((string)$fechaDesde, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Fecha hasta</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           name="fecha_hasta"
                           placeholder="dd/mm/aaaa"
                           value="<?php echo htmlspecialchars((string)$fechaHasta, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Todos</option>
                        <option value="A" <?php echo ($status === 'A') ? 'selected' : ''; ?>>Activos</option>
                        <option value="C" <?php echo ($status === 'C') ? 'selected' : ''; ?>>Cancelados</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Moneda</label>
                    <select class="form-select form-select-sm" name="moneda">
                        <option value="">Todas</option>
                        <?php foreach ($monedas as $m): ?>
                            <option value="<?php echo (int)$m['Id_moneda']; ?>"
                                <?php echo ($moneda == $m['Id_moneda']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$m['Nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Protocolo</label>
                    <select class="form-select form-select-sm" name="protocolo">
                        <option value="">Todos</option>
                        <?php foreach ($protRows as $pr): ?>
                            <option value="<?php echo (int)$pr['ID_Protocolo']; ?>"
                                <?php echo ($protocolo == $pr['ID_Protocolo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$pr['Descripcion'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="la la-search"></i> Aplicar
                    </button>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">&nbsp;</label>
                    <a href="orden_compra.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="la la-eraser"></i> Limpiar
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- Lista de OCs -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h5 class="mb-0" style="font-size:13px;font-weight:600;">Órdenes de Compra — detalle</h5>
                </div>
                <div>
                    <a href="orden_compra_edit.php" class="btn btn-sm btn-primary">
                        <i class="la la-plus-circle"></i> Nueva OC
                    </a>
                </div>
            </div>

            <div class="ap-oc-table-wrapper">
                <table class="table table-striped table-hover table-sm ap-oc-table">
                    <thead>
                    <tr>
                        <th class="text-center" style="width:80px;">Acciones</th>
                        <th>ID Aduana</th>
                        <th>Pedimento (folio OC)</th>
                        <th>Fecha OC</th>
                        <th>Proveedor</th>
                        <th>Protocolo</th>
                        <th>Almacén</th>
                        <th>Moneda</th>
                        <th>Tipo Cambio</th>
                        <th class="text-end">Partidas</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($errorConsulta)): ?>
                        <tr>
                            <td colspan="11" class="text-center text-danger">
                                Error al consultar OCs:
                                <?php echo htmlspecialchars((string)$errorConsulta, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php elseif (!$ocs): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted">
                                No hay órdenes de compra para los filtros seleccionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ocs as $r): ?>
                            <?php
                            $monedaTxt = '';
                            if ((int)$r['Id_moneda'] === 1) {
                                $monedaTxt = 'MXN';
                            } elseif ((int)$r['Id_moneda'] > 1) {
                                $monedaTxt = 'USD';
                            }
                            ?>
                            <tr>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm ap-oc-actions">
                                        <a href="orden_compra_ver.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                           class="btn btn-outline-primary"
                                           title="Ver OC">
                                            <i class="la la-eye"></i>
                                        </a>
                                        <a href="orden_compra_edit.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                           class="btn btn-outline-secondary"
                                           title="Editar OC">
                                            <i class="la la-edit"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo (int)$r['ID_Aduana']; ?></td>
                                <td><?php echo htmlspecialchars((string)($r['Pedimento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php
                                    if (!empty($r['fech_pedimento'])) {
                                        $dt = new DateTime($r['fech_pedimento']);
                                        echo $dt->format('d/m/Y');
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)($r['proveedor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($r['protocolo_clave'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($r['Cve_Almac'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$monedaTxt, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($r['Tipo_Cambio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?php echo (int)$r['partidas']; ?></td>
                                <td><?php echo htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
