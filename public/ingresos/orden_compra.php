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
 *
 * Devuelve:
 *   [
 *     ['Clave_Almacen' => 'WH1', 'Descripcion' => 'WH1'],
 *     ...
 *   ]
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

        $almacenes = [];
        foreach ($rows as $r) {
            $almacenes[] = [
                'Clave_Almacen' => $r['Clave_Almacen'],
                'Descripcion'   => $r['Clave_Almacen'],
            ];
        }
        return $almacenes;
    } catch (Throwable $e) {
        return [];
    }
}

// ================== Catálogo de almacenes ==================
$almacenes = cargarAlmacenes($pdo);
$primerAlmacen = $almacenes[0]['Clave_Almacen'] ?? '';

// ================== Parámetros de filtro con defaults ==================
$hoy          = date('Y-m-d');
$desdeDefault = date('Y-m-d', strtotime('-7 days'));

$almacen   = $_GET['almacen']   ?? $primerAlmacen;
$desde     = $_GET['desde']     ?? $desdeDefault;
$hasta     = $_GET['hasta']     ?? $hoy;
$status    = $_GET['status']    ?? 'A';     // Abierta por default
$protocolo = $_GET['protocolo'] ?? 'OCN';  // Orden de Compra Nacional

// Si usuario selecciona "Todos" en status, el value llega vacío => sin filtro
if ($status === 'TODOS') {
    $status = '';
}

// ================== Catálogo de protocolos (solo OCN / OCI) ==================
$protRows = [];
try {
    $protRows = $pdo->query("
        SELECT ID_Protocolo, descripcion
        FROM t_protocolo
        WHERE Activo = 1
          AND ID_Protocolo IN ('OCN','OCI')
        ORDER BY ID_Protocolo
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $protRows = [];
}

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
    $where[]              = 'h.ID_Protocolo = :protocolo';
    $params[':protocolo'] = $protocolo;
}

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$ocs           = [];
$errorConsulta = '';

if ($almacen !== '') {
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
            h.recurso AS empresa_recurso,
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

    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $ocs = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $errorConsulta = $e->getMessage();
        $ocs = [];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AssistPro SFA — Órdenes de Compra</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 10px;
        }
        .ap-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 90, 173, 0.08);
            border: 1px solid #eef1f7;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .ap-title,
        .ap-subtitle,
        .ap-label,
        .form-control,
        .form-select,
        .btn,
        table,
        table th,
        table td {
            font-size: 10px !important;
        }
        .ap-title {
            font-weight: 700;
            color: #0F5AAD;
        }
        .ap-subtitle {
            color: #6c757d;
            margin-bottom: 0;
        }
        .ap-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 2px;
        }
        .form-control,
        .form-select {
            border-radius: 8px;
            padding-top: 1px;
            padding-bottom: 1px;
            line-height: 1.2;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #0F5AAD;
            box-shadow: 0 0 0 0.12rem rgba(15, 90, 173, .25);
        }
        .btn-ap-primary {
            background-color: #0F5AAD;
            border-color: #0F5AAD;
            color: #fff;
            border-radius: 999px;
            padding-inline: 14px;
            padding-block: 3px;
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
            white-space: nowrap;
            vertical-align: middle;
        }
        thead.sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
        }
        .badge-status {
            border-radius: 999px;
            padding: 2px 8px;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="ap-title mb-0">AssistPro SFA — Órdenes de Compra</div>
            <p class="ap-subtitle">Consulta y administración de Órenes de Compra (th_aduana / td_aduana).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="orden_compra_edit.php" class="btn btn-ap-primary btn-sm">
                Nueva OC
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="ap-card mb-2">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Almacén</label>
                <select class="form-select" name="almacen">
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
                <input type="date" class="form-control" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Fecha hasta</label>
                <input type="date" class="form-control" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="A"<?php if ($status==='A') echo ' selected'; ?>>Abierta (A)</option>
                    <option value="C"<?php if ($status==='C') echo ' selected'; ?>>Cerrada (C)</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Tipo OC</label>
                <select class="form-select" name="protocolo">
                    <?php if (!$protRows): ?>
                        <option value="">Sin tipos</option>
                    <?php else: ?>
                        <?php foreach ($protRows as $pr): ?>
                            <option value="<?php echo htmlspecialchars($pr['ID_Protocolo']); ?>"
                                <?php if ($protocolo === $pr['ID_Protocolo']) echo ' selected'; ?>>
                                <?php echo htmlspecialchars($pr['ID_Protocolo'] . ' - ' . $pr['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-1 col-sm-6 text-end">
                <button type="submit" class="btn btn-ap-primary btn-sm w-100">
                    Buscar
                </button>
            </div>
        </form>
        <?php if (!empty($errorConsulta)): ?>
            <div class="text-danger mt-1" style="font-size:9px;">
                Error en la consulta: <?php echo htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Grilla -->
    <div class="ap-card">
        <div id="tablaWrapper" class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="tablaOC">
                <thead class="sticky-header">
                    <tr>
                        <th style="width:160px;">Acciones</th>
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
                <?php if ($almacen === '' && !$almacenes): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">
                            No hay almacenes disponibles en th_aduana.
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
                                <?php
                                    $protTxt = trim(($r['protocolo_clave'] ?? '') . ' ' . ($r['protocolo_desc'] ?? ''));
                                    echo htmlspecialchars($protTxt, ENT_QUOTES, 'UTF-8');
                                ?>
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
                                    <?php echo $statusText; ?>
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

<!-- Modal Ver OC (solo resumen sin costos) -->
<div class="modal fade" id="modalVerOc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="font-size:10px;">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Orden de Compra</h5>
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

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
            } catch (e) {
                modalBody.textContent = 'Error al cargar el detalle.';
            }

            const modalEl = document.getElementById('modalVerOc');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    });
});
</script>
</body>
</html>
