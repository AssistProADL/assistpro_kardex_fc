<?php
// public/config_almacen/license_plate_detalle.php
// Fragmento para el modal de detalle de License Plate

require_once __DIR__ . '/../../app/db.php';

$lp = isset($_GET['lp']) ? trim($_GET['lp']) : '';

if ($lp === '') {
    ?>
    <div class="alert alert-warning mb-0" style="font-size:10px;">
        No se recibió el License Plate a consultar.
    </div>
    <?php
    exit;
}

/* ===========================================================
   1) Obtener IDContenedor (ntarima) a partir del CveLP
   =========================================================== */

$sql_lp = "
    SELECT 
        IDContenedor,
        CveLP,
        Clave_Contenedor
    FROM c_charolas
    WHERE CveLP = :lp
    LIMIT 1
";
$info_lp = db_one($sql_lp, ['lp' => $lp]);

if (!$info_lp || empty($info_lp['IDContenedor'])) {
    ?>
    <div class="alert alert-danger mb-0" style="font-size:10px;">
        No se encontró información para el License Plate <strong><?php echo htmlspecialchars($lp); ?></strong>.
    </div>
    <?php
    exit;
}

$ntarima = $info_lp['IDContenedor'];

/* ===========================================================
   2) Detalle de contenido del LP desde ts_existenciatarima
   =========================================================== */

$sql_det = "
    SELECT 
        ap.clave        AS codigo_almacen,
        ap.nombre       AS nombre_almacen,
        t.ntarima       AS id_tarima,
        u.CodigoCSD     AS bl_codigocsd,
        t.cve_articulo,
        a.des_articulo  AS des_articulo,
        t.lote,
        t.existencia,
        t.Fol_Folio,
        t.idy_ubica
    FROM ts_existenciatarima t
    INNER JOIN c_almacenp ap  ON ap.id       = t.cve_almac
    LEFT JOIN  c_ubicacion u  ON u.idy_ubica = t.idy_ubica
    LEFT JOIN  c_articulo a   ON a.cve_articulo = t.cve_articulo
    WHERE t.ntarima   = :ntarima
      AND t.existencia > 0
    ORDER BY ap.clave, a.des_articulo, t.lote
";

$rows = db_all($sql_det, ['ntarima' => $ntarima]);

$total_piezas = 0;
foreach ($rows as $r) {
    $total_piezas += (float)$r['existencia'];
}
?>

<div style="font-size:10px;">
    <div class="mb-2">
        <strong>License Plate:</strong>
        <?php echo htmlspecialchars($info_lp['CveLP']); ?>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Pallet / Contenedor:</strong>
        <?php echo htmlspecialchars($info_lp['Clave_Contenedor']); ?>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>ID Tarima (ntarima):</strong>
        <?php echo htmlspecialchars($ntarima); ?>
    </div>

    <?php if (!$rows): ?>
        <div class="alert alert-info mb-0" style="font-size:10px;">
            El LP <strong><?php echo htmlspecialchars($info_lp['CveLP']); ?></strong> no tiene existencias registradas
            en <strong>ts_existenciatarima</strong>.
        </div>
    <?php else: ?>

        <div class="table-responsive" style="max-height:420px; overflow:auto;">
            <table class="table table-striped table-bordered table-sm align-middle mb-2" style="font-size:10px;">
                <thead class="table-light">
                <tr>
                    <th>Almacén</th>
                    <th>BL (CodigoCSD)</th>
                    <th>Artículo</th>
                    <th>Descripción</th>
                    <th>Lote</th>
                    <th class="text-end">Existencia</th>
                    <th>Folio</th>
                    <th>Ubicación (idy_ubica)</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $almLabel = trim($r['codigo_almacen']) . ' - ' . trim($r['nombre_almacen']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($almLabel); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['bl_codigocsd']); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['cve_articulo']); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['des_articulo']); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['lote']); ?></td>
                        <td class="text-end">
                            <?php echo number_format((float)$r['existencia'], 3, '.', ','); ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)$r['Fol_Folio']); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['idy_ubica']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end">
            <strong>Total piezas en LP:</strong>
            <?php echo number_format($total_piezas, 3, '.', ','); ?>
        </div>
    <?php endif; ?>
</div>
