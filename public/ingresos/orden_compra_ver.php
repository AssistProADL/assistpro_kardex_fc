<?php
// public/ingresos/orden_compra_ver.php
// Devuelve SOLO HTML para el modal (sin <html> / <body>)

require_once __DIR__ . '/../../app/db.php';

$id = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;
if ($id <= 0) {
    echo '<div class="text-danger">ID de OC inválido.</div>';
    exit;
}

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Encabezado
    $stH = $pdo->prepare("
        SELECT h.*,
               p.Nombre AS proveedor,
               pr.descripcion AS tipo_oc,
               pr.ID_Protocolo
        FROM th_aduana h
        LEFT JOIN c_proveedores p
            ON p.ID_Proveedor = h.ID_Proveedor
        LEFT JOIN t_protocolo pr
            ON pr.ID_Protocolo = h.ID_Protocolo
        WHERE h.ID_Aduana = :id
        LIMIT 1
    ");
    $stH->execute([':id' => $id]);
    $h = $stH->fetch(PDO::FETCH_ASSOC);

    if (!$h) {
        echo '<div class="text-danger">No se encontró la orden de compra.</div>';
        exit;
    }

    // Empresa (simple: primera de c_compania)
    $empresaNombre = '';
    $empresaDir    = '';
    try {
        $rowEmp = $pdo->query("
            SELECT des_cia, des_direcc, des_cp
            FROM c_compania
            ORDER BY empresa_id
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        if ($rowEmp) {
            $empresaNombre = $rowEmp['des_cia'] ?? '';
            $empresaDir    = trim(($rowEmp['des_direcc'] ?? '') . ' CP ' . ($rowEmp['des_cp'] ?? ''));
        }
    } catch (Throwable $e) { }

    // Detalle
    $stD = $pdo->prepare("
        SELECT d.*, a.des_articulo, a.cve_umed
        FROM td_aduana d
        LEFT JOIN c_articulo a
            ON a.cve_articulo = d.cve_articulo
        WHERE d.ID_Aduana = :id
        ORDER BY d.Item
    ");
    $stD->execute([':id' => $id]);
    $det = $stD->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    echo '<div class="text-danger">Error al consultar la OC: ' .
         htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    exit;
}
?>

<div>
    <div class="mb-2">
        <strong><?php echo htmlspecialchars($empresaNombre ?: 'Empresa', ENT_QUOTES, 'UTF-8'); ?></strong><br>
        <span><?php echo htmlspecialchars($empresaDir, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <div class="row mb-2">
        <div class="col-6">
            <div><strong>ID Aduana:</strong> <?php echo (int)$h['ID_Aduana']; ?></div>
            <div><strong>Pedimento / Folio OC:</strong> <?php echo htmlspecialchars($h['Pedimento'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Proveedor:</strong> <?php echo htmlspecialchars($h['proveedor'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="col-6">
            <div><strong>Tipo OC:</strong>
                <?php echo htmlspecialchars(trim(($h['ID_Protocolo'] ?? '') . ' ' . ($h['tipo_oc'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div><strong>Almacén:</strong> <?php echo htmlspecialchars($h['Cve_Almac'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Fecha OC:</strong>
                <?php echo htmlspecialchars(substr((string)$h['fech_pedimento'], 0, 10), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>

    <h6>Partidas (sin costos)</h6>
    <div class="table-responsive" style="max-height:260px; overflow:auto;">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Clave</th>
                    <th>Descripción</th>
                    <th>UOM</th>
                    <th class="text-end">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$det): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Sin partidas registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $i = 0;
                    $totalCant = 0;
                    foreach ($det as $r):
                        $i++;
                        $cant = (float)$r['cantidad'];
                        $totalCant += $cant;
                    ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo htmlspecialchars($r['cve_articulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['des_articulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['cve_umed'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end"><?php echo number_format($cant, 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light">
                        <td colspan="4" class="text-end"><strong>Total cantidad</strong></td>
                        <td class="text-end"><strong><?php echo number_format($totalCant, 4); ?></strong></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
