<?php
// public/ingresos/orden_compra.php

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die('Error de conexión a base de datos: ' . htmlspecialchars($e->getMessage()));
}

/**
 * Carga almacenes directamente desde th_aduana (almacenes que ya tienen OCs),
 * para no depender del API ni de otras tablas por ahora.
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
            $out[] = [
                'Clave_Almacen' => $r['Clave_Almacen'],
                'Descripcion'   => $r['Clave_Almacen'],
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Carga protocolos disponibles en t_protocolo para filtro.
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

// ================== LECTURA DE FILTROS ==================
$almacen   = $_GET['almacen']  ?? '';
$desde     = $_GET['desde']    ?? '';
$hasta     = $_GET['hasta']    ?? '';
$status    = $_GET['status']   ?? '';
$protocolo = $_GET['protocolo']?? '';
$tipoOc    = $_GET['tipooc']   ?? ''; // OCI / OCN

$almacenes = cargarAlmacenes($pdo);
$protRows  = cargarProtocolos($pdo);

// ================== WHERE de la consulta ==================
$where  = [];
$params = [];

if ($almacen !== '') {
    $where[]            = 'h.Cve_Almac = :almacen';
    $params[':almacen'] = $almacen;
}
if ($desde !== '') {
    $where[]           = 'DATE(h.fech_pedimento) >= :desde';
    $params[':desde']  = $desde;
}
if ($hasta !== '') {
    $where[]           = 'DATE(h.fech_pedimento) <= :hasta';
    $params[':hasta']  = $hasta;
}
if ($status !== '') {
    $where[]           = 'h.status = :status';
    $params[':status'] = $status;
}
if ($protocolo !== '') {
    $where[]                = 'h.ID_Protocolo = :protocolo';
    $params[':protocolo']   = $protocolo;
}
if ($tipoOc !== '') {
    // Se usa campo recurso = 'OCN' / 'OCI'
    $where[]               = 'h.recurso = :tipooc';
    $params[':tipooc']     = $tipoOc;
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
            h.recurso AS tipo_oc,
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
        LIMIT 500
    ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $ocs = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errorConsulta = $e->getMessage();
    $ocs           = [];
}

// ================== LAYOUT CORPORATIVO ==================
$TITLE = 'Órdenes de Compra';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
    body {
        background: #f5f7fb;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: 10px;
    }
    .ap-card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(15, 90, 173, 0.20);
        border: 1px solid #dbe3ef;
        margin-bottom: 10px;
    }
    .ap-card-header {
        background: #0F5AAD;
        color: #ffffff;
        padding: 8px 14px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ap-title {
        font-size: 15px;
        font-weight: 600;
    }
    .ap-subtitle {
        font-size: 11px;
        margin: 0;
        opacity: .85;
    }
    .ap-card-body {
        padding: 10px 14px 8px;
    }
    .ap-label {
        font-size: 11px;
        font-weight: 600;
        color: #555;
        margin-bottom: 2px;
    }
    .form-control,
    .form-select {
        font-size: 12px;
        height: 30px;
        padding: 3px 8px;
    }
    .btn-ap-primary {
        background-color: #0F5AAD;
        border-color: #0F5AAD;
        color: #fff;
        border-radius: 999px;
        padding-inline: 14px;
        padding-block: 3px;
        font-size: 11px;
        box-shadow: 0 4px 10px rgba(15, 90, 173, 0.4);
    }
    .btn-ap-primary:hover {
        background-color: #0c4a8d;
        border-color: #0c4a8d;
    }
    #tablaWrapper {
        max-height: 520px;
        overflow-y: auto;
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    table.table-sm th,
    table.table-sm td {
        font-size: 11px;
        white-space: nowrap;
    }
    table.table-sm th {
        background: #f1f5f9;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .badge-status {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 999px;
    }
    .btn-group-sm>.btn {
        font-size: 10px;
        padding: 2px 6px;
    }
</style>

<div class="container-fluid py-2">

    <!-- Encabezado -->
    <div class="ap-card mb-2">
        <div class="ap-card-header">
            <div>
                <div class="ap-title mb-0">AssistPro SFA — Órdenes de Compra</div>
                <p class="ap-subtitle">Consulta y administración de Órdenes de Compra (th_aduana / td_aduana).</p>
            </div>
            <div>
                <a href="orden_compra_edit.php" class="btn btn-ap-primary btn-sm">
                    Nueva OC
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="ap-card mb-2">
        <div class="ap-card-body">
            <form class="row g-2 align-items-end" method="get">
                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Almacén</label>
                    <select class="form-select" name="almacen">
                        <option value="">Todos</option>
                        <?php if (!$almacenes): ?>
                            <option value="">(Sin almacenes en th_aduana)</option>
                        <?php else: ?>
                            <?php foreach ($almacenes as $a): ?>
                                <option value="<?php echo htmlspecialchars($a['Clave_Almacen']); ?>"
                                    <?php if ($almacen === $a['Clave_Almacen']) echo ' selected'; ?>>
                                    <?php echo htmlspecialchars($a['Clave_Almacen'] . ' — ' . $a['Descripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Fecha desde</label>
                    <input type="date" class="form-control" name="desde"
                           value="<?php echo htmlspecialchars($desde ?? ''); ?>">
                </div>
                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Fecha hasta</label>
                    <input type="date" class="form-control" name="hasta"
                           value="<?php echo htmlspecialchars($hasta ?? ''); ?>">
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="A" <?php echo ($status === 'A') ? 'selected' : ''; ?>>Abierta</option>
                        <option value="C" <?php echo ($status === 'C') ? 'selected' : ''; ?>>Cerrada</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Protocolo</label>
                    <select class="form-select" name="protocolo">
                        <option value="">Todos</option>
                        <?php foreach ($protRows as $pr): ?>
                            <option value="<?php echo (int)$pr['ID_Protocolo']; ?>"
                                <?php echo ($protocolo == $pr['ID_Protocolo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pr['Descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">Tipo OC</label>
                    <select class="form-select" name="tipooc">
                        <option value="">Todos</option>
                        <option value="OCN" <?php echo ($tipoOc === 'OCN') ? 'selected' : ''; ?>>OCN</option>
                        <option value="OCI" <?php echo ($tipoOc === 'OCI') ? 'selected' : ''; ?>>OCI</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="ap-label">&nbsp;</label>
                    <button type="submit" class="btn btn-ap-primary w-100">
                        Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="ap-card">
        <div class="ap-card-body">
            <?php if (isset($errorConsulta)): ?>
                <div class="alert alert-danger py-1 mb-2" style="font-size:11px;">
                    Error al consultar órdenes de compra:
                    <?php echo htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div id="tablaWrapper" class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" id="tablaOC">
                    <thead>
                        <tr>
                            <th style="width:170px;">Acciones</th>
                            <th>ID Aduana</th>
                            <th>Pedimento (folio OC)</th>
                            <th>Fecha OC</th>
                            <th>Proveedor</th>
                            <th>Tipo OC</th>
                            <th>Almacén</th>
                            <th>Moneda</th>
                            <th>Tipo Cambio</th>
                            <th class="text-end" style="width:80px;">Partidas (td_aduana)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$ocs): ?>
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
                                } elseif ((int)$r['Id_moneda'] === 2) {
                                    $monedaTxt = 'USD';
                                }
                                $statusBadge = ($r['status'] === 'A') ? 'success' : 'secondary';
                                $statusText  = ($r['status'] === 'A') ? 'Abierta' : 'Cerrada';
                            ?>
                            <tr>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-ver"
                                                data-id="<?php echo (int)$r['ID_Aduana']; ?>">
                                            Ver
                                        </button>
                                        <a href="orden_compra_edit.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                           class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                        <a href="orden_compra_pdf.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                           class="btn btn-outline-secondary" target="_blank">
                                            PDF OC
                                        </a>
                                        <a href="orden_compra_pdf_sin_costos.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                           class="btn btn-outline-secondary" target="_blank">
                                            PDF recepción
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo (int)$r['ID_Aduana']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($r['Pedimento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?php
                                        echo htmlspecialchars(
                                            $r['fech_pedimento'] ? substr($r['fech_pedimento'], 0, 10) : '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                    ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['proveedor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['tipo_oc'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['Cve_Almac'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($monedaTxt, ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format((float)$r['Tipo_Cambio'], 4); ?>
                                </td>
                                <td class="text-end">
                                    <?php echo (int)$r['partidas']; ?>
                                </td>
                                <td>
                                    <span class="badge badge-status bg-<?php echo $statusBadge; ?>">
                                        <?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-muted mt-1" style="font-size:9px;">
                Consulta limitada a 500 registros.
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver OC -->
<div class="modal fade" id="modalVerOc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="font-size:10px;">
      <div class="modal-header">
        <h5 class="modal-title">Orden de Compra — Detalle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalVerOcBody">
        Cargando...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Botones Ver -> modal con resumen sin costos
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-ver').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            const modalBody = document.getElementById('modalVerOcBody');
            modalBody.textContent = 'Cargando...';
            try {
                const res = await fetch('orden_compra_ver.php?id_aduana=' + encodeURIComponent(id));
                const html = await res.text();
                modalBody.innerHTML = html;
                const modal = new bootstrap.Modal(document.getElementById('modalVerOc'));
                modal.show();
            } catch (e) {
                modalBody.textContent = 'Error al cargar el detalle de la OC.';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
