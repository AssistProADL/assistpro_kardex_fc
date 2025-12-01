<?php
// public/ingresos/orden_compra_ver.php
// Contenido HTML para el modal "Ver" (solo detalle, sin marco global)

require_once __DIR__ . '/../../app/db.php';

header('Content-Type: text/html; charset=utf-8');

$id = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;
if (!$id) {
    echo '<div class="text-danger">OC no encontrada.</div>';
    exit;
}

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    echo '<div class="text-danger">Error de conexión: ' .
         htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
         '</div>';
    exit;
}

/*
 * ENCABEZADO
 *  th_aduana + c_proveedores + c_almacenp + c_compania
 */
$sqlH = "
    SELECT
        h.ID_Aduana,
        h.Pedimento,
        h.Factura,
        h.fech_pedimento,
        h.fech_llegPed,
        h.Cve_Almac,
        h.Id_moneda,
        h.Tipo_Cambio,
        h.recurso,
        h.status,
        h.Proyecto,
        h.ID_Proveedor,
        p.Nombre              AS proveedor,
        a.nombre              AS nombre_almacen,
        a.cve_cia,
        cia.des_cia           AS nombre_compania,
        CASE 
            WHEN h.Id_moneda = 2 THEN 'USD'
            WHEN h.Id_moneda = 1 THEN 'MXN'
            ELSE ''
        END AS moneda_desc
    FROM th_aduana h
    LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
    LEFT JOIN c_almacenp    a ON a.clave       = h.Cve_Almac
    LEFT JOIN c_compania    cia ON cia.cve_cia = a.cve_cia
    WHERE h.ID_Aduana = :id
";

$st = $pdo->prepare($sqlH);
$st->execute([':id' => $id]);
$h = $st->fetch(PDO::FETCH_ASSOC);

if (!$h) {
    echo '<div class="text-danger">No se encontró la OC.</div>';
    exit;
}

/*
 * DETALLE
 *  td_aduana + c_articulo + c_unimed
 */
$sqlD = "
    SELECT
        d.cve_articulo,
        d.cantidad,
        d.costo,
        d.IVA,
        d.Cve_Lote,
        d.caducidad,
        d.Item,
        d.Id_UniMed,
        a.des_articulo,
        u.des_umed,
        (d.costo * d.cantidad)                                       AS subtotal,
        (d.costo * d.cantidad * (IFNULL(d.IVA,0)/100))               AS iva_monto,
        (d.costo * d.cantidad * (1 + (IFNULL(d.IVA,0)/100)))         AS total
    FROM td_aduana d
    LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
    LEFT JOIN c_unimed  u ON u.cve_umed     = a.cve_umed
    WHERE d.ID_Aduana = :id
    ORDER BY d.num_orden, d.Item
";

$st = $pdo->prepare($sqlD);
$st->execute([':id' => $id]);
$det = $st->fetchAll(PDO::FETCH_ASSOC);

// Totales
$subTotal = 0.0;
$ivaTotal = 0.0;
$total    = 0.0;
foreach ($det as $r) {
    $subTotal += (float)$r['subtotal'];
    $ivaTotal += (float)$r['iva_monto'];
    $total    += (float)$r['total'];
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<div class="container-fluid" style="font-size:11px;">
    <div class="row mb-2">
        <div class="col-8">
            <div><strong>Compañía:</strong> <?php echo e($h['nombre_compania'] ?? ''); ?></div>
            <div><strong>Proveedor:</strong> <?php echo e($h['proveedor'] ?? ''); ?></div>
            <div><strong>Proyecto:</strong> <?php echo e($h['Proyecto'] ?? ''); ?></div>
            <div><strong>Almacén:</strong>
                <?php echo e(($h['Cve_Almac'] ?? '') . ' - ' . ($h['nombre_almacen'] ?? '')); ?>
            </div>
        </div>
        <div class="col-4 text-end">
            <div><strong>Folio OC:</strong> <?php echo e($h['Pedimento'] ?: $h['Factura']); ?></div>
            <div><strong>ID Aduana:</strong> <?php echo (int)$h['ID_Aduana']; ?></div>
            <div><strong>Fecha OC:</strong>
                <?php echo $h['fech_pedimento'] ? e(substr($h['fech_pedimento'],0,10)) : ''; ?>
            </div>
            <div><strong>Tipo OC:</strong> <?php echo e($h['recurso'] ?? ''); ?></div>
            <div>
                <strong>Moneda:</strong> <?php echo e($h['moneda_desc']); ?>
                &nbsp; <strong>TC:</strong> <?php echo number_format((float)$h['Tipo_Cambio'], 4); ?>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped table-bordered align-middle mb-1">
            <thead class="table-light">
                <tr>
                    <th style="width:30px;">#</th>
                    <th style="width:90px;">Clave</th>
                    <th>Descripción</th>
                    <th style="width:80px;">UOM</th>
                    <th style="width:80px;" class="text-end">Cantidad</th>
                    <th style="width:80px;" class="text-end">Precio neto</th>
                    <th style="width:60px;" class="text-end">IVA %</th>
                    <th style="width:90px;" class="text-end">Subtotal</th>
                    <th style="width:90px;" class="text-end">IVA</th>
                    <th style="width:90px;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$det): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted">Sin partidas.</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($det as $r): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo e($r['cve_articulo']); ?></td>
                        <td><?php echo e($r['des_articulo']); ?></td>
                        <td><?php echo e($r['des_umed'] ?: $r['Id_UniMed']); ?></td>
                        <td class="text-end"><?php echo number_format((float)$r['cantidad'], 4); ?></td>
                        <td class="text-end"><?php echo number_format((float)$r['costo'], 4); ?></td>
                        <td class="text-end"><?php echo number_format((float)$r['IVA'], 2); ?></td>
                        <td class="text-end"><?php echo number_format((float)$r['subtotal'], 2); ?></td>
                        <td class="text-end"><?php echo number_format((float)$r['iva_monto'], 2); ?></td>
                        <td class="text-end"><?php echo number_format((float)$r['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="7" class="text-end">Subtotal</th>
                    <th class="text-end"><?php echo number_format($subTotal, 2); ?></th>
                    <th class="text-end"><?php echo number_format($ivaTotal, 2); ?></th>
                    <th class="text-end"><?php echo number_format($total, 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
