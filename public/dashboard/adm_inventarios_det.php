<?php
// adm_inventarios_det.php
// Detalle y diferencias de inventario (físico / cíclico) con filtro por conteo y exportación

require_once __DIR__ . '/../app/db.php';

if (!function_exists('db_all')) {
    function db_all($sql, $params = [])
    {
        global $pdo; // ajusta si tu conexión usa otro nombre
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ---------------------------------------------------------
//  Parámetros
// ---------------------------------------------------------
$tipo   = isset($_GET['tipo'])  ? strtoupper($_GET['tipo']) : 'F';   // F = físico, C = cíclico
$view   = isset($_GET['view'])  ? strtolower($_GET['view']) : 'det'; // det | dif
$folio  = isset($_GET['folio']) ? (int)$_GET['folio'] : 0;
$conteo = isset($_GET['conteo']) ? (int)$_GET['conteo'] : 0;         // 0 = todos

if ($folio <= 0) {
    require_once __DIR__ . '/../bi/_menu_global.php';
    ?>
    <div class="container-fluid py-3" style="font-size:10px;">
        <h5>Detalle de inventario</h5>
        <div class="alert alert-warning">No se recibió un folio válido.</div>
        <a href="adm_inventarios.php" class="btn btn-sm btn-secondary mt-2">Regresar</a>
    </div>
    <?php
    require_once __DIR__ . '/../bi/_menu_global_end.php';
    exit;
}

// ---------------------------------------------------------
//  Helpers de datos (detalle + lista de conteos disponibles)
// ---------------------------------------------------------
function get_conteos_disponibles($tipo, $folio)
{
    if ($tipo === 'F') {
        $rows = db_all(
            "SELECT DISTINCT NConteo 
             FROM t_invpiezas 
             WHERE ID_Inventario = ?
             ORDER BY NConteo",
            [$folio]
        );
    } else {
        $rows = db_all(
            "SELECT DISTINCT NConteo 
             FROM t_invpiezasciclico 
             WHERE ID_PLAN = ?
             ORDER BY NConteo",
            [$folio]
        );
    }
    return array_column($rows, 'NConteo');
}

function get_det_rows($tipo, $view, $folio, $conteo)
{
    $params = [$folio];

    if ($tipo === 'F' && $view === 'det') {
        // Detalle inventario físico
        $sql = "
            SELECT
                v.ID_Inventario          AS folio_inventario,
                v.NConteo,
                v.idy_ubica,
                v.ntarima,
                v.cve_articulo,
                a.des_articulo,
                v.cve_lote,
                v.Cantidad               AS cantidad_conteo,
                v.cve_usuario,
                v.fecha
            FROM v_inventario v
            LEFT JOIN c_articulo a
                   ON a.cve_articulo = v.cve_articulo
            WHERE v.ID_Inventario = ?
        ";
        if ($conteo > 0) {
            $sql .= " AND v.NConteo = ?";
            $params[] = $conteo;
        }
        $sql .= "
            ORDER BY v.NConteo, v.idy_ubica, v.cve_articulo, v.cve_lote
        ";
        return db_all($sql, $params);
    }

    if ($tipo === 'F' && $view === 'dif') {
        // Diferencias inventario físico
        $sql = "
            SELECT
                p.ID_Inventario                      AS folio_inventario,
                p.NConteo,
                p.idy_ubica,
                p.cve_articulo,
                a.des_articulo,
                p.cve_lote,
                p.Cantidad                           AS cantidad_conteo,
                p.ExistenciaTeorica,
                (COALESCE(p.Cantidad,0) -
                 COALESCE(p.ExistenciaTeorica,0))    AS diferencia_piezas,
                COALESCE(a.costoPromedio, a.imp_costo, 0) AS costo_unitario,
                (COALESCE(p.Cantidad,0) -
                 COALESCE(p.ExistenciaTeorica,0)) *
                COALESCE(a.costoPromedio, a.imp_costo, 0) AS diferencia_valor,
                p.ID_Proveedor,
                p.Cuarentena,
                p.ClaveEtiqueta,
                p.cve_usuario,
                p.fecha
            FROM t_invpiezas p
            LEFT JOIN c_articulo a
                   ON a.cve_articulo = p.cve_articulo
            WHERE p.ID_Inventario = ?
              AND ABS(COALESCE(p.Cantidad,0) - COALESCE(p.ExistenciaTeorica,0)) <> 0
        ";
        if ($conteo > 0) {
            $sql .= " AND p.NConteo = ?";
            $params[] = $conteo;
        }
        $sql .= "
            ORDER BY p.NConteo, p.idy_ubica, p.cve_articulo
        ";
        return db_all($sql, $params);
    }

    if ($tipo === 'C' && $view === 'det') {
        // Detalle inventario cíclico
        $sql = "
            SELECT
                c.ID_PLAN               AS folio_plan,
                c.NConteo,
                c.idy_ubica,
                c.cve_articulo,
                a.des_articulo,
                c.cve_lote,
                c.Cantidad              AS cantidad_conteo,
                c.cve_usuario,
                c.fecha
            FROM vd_inventariociclico c
            LEFT JOIN c_articulo a
                   ON a.cve_articulo = c.cve_articulo
            WHERE c.ID_PLAN = ?
        ";
        if ($conteo > 0) {
            $sql .= " AND c.NConteo = ?";
            $params[] = $conteo;
        }
        $sql .= "
            ORDER BY c.NConteo, c.idy_ubica, c.cve_articulo, c.cve_lote
        ";
        return db_all($sql, $params);
    }

    if ($tipo === 'C' && $view === 'dif') {
        // Diferencias inventario cíclico
        $sql = "
            SELECT
                p.ID_PLAN                           AS folio_plan,
                p.NConteo,
                p.idy_ubica,
                p.cve_articulo,
                a.des_articulo,
                p.cve_lote,
                p.Cantidad                          AS cantidad_conteo,
                p.ExistenciaTeorica,
                (COALESCE(p.Cantidad,0) -
                 COALESCE(p.ExistenciaTeorica,0))   AS diferencia_piezas,
                COALESCE(a.costoPromedio, a.imp_costo, 0) AS costo_unitario,
                (COALESCE(p.Cantidad,0) -
                 COALESCE(p.ExistenciaTeorica,0)) *
                COALESCE(a.costoPromedio, a.imp_costo, 0) AS diferencia_valor,
                p.Id_Proveedor,
                p.Cuarentena,
                p.ClaveEtiqueta,
                p.cve_usuario,
                p.fecha
            FROM t_invpiezasciclico p
            LEFT JOIN c_articulo a
                   ON a.cve_articulo = p.cve_articulo
            WHERE p.ID_PLAN = ?
              AND ABS(COALESCE(p.Cantidad,0) - COALESCE(p.ExistenciaTeorica,0)) <> 0
        ";
        if ($conteo > 0) {
            $sql .= " AND p.NConteo = ?";
            $params[] = $conteo;
        }
        $sql .= "
            ORDER BY p.NConteo, p.idy_ubica, p.cve_articulo
        ";
        return db_all($sql, $params);
    }

    return [];
}

// ---------------------------------------------------------
//  Exportación CSV (se hace ANTES de imprimir HTML)
// ---------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = get_det_rows($tipo, $view, $folio, $conteo);

    $nombreTipo = ($tipo === 'F') ? 'fisico' : 'ciclico';
    $nombreView = ($view === 'dif') ? 'dif' : 'det';
    $nombreArchivo = "inv_{$nombreTipo}_{$nombreView}_{$folio}";
    if ($conteo > 0) {
        $nombreArchivo .= "_c{$conteo}";
    }
    $nombreArchivo .= ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

    $out = fopen('php://output', 'w');

    if ($tipo === 'F' && $view === 'det') {
        fputcsv($out, [
            'Folio','Conteo','Ubicacion','Tarima',
            'Articulo','Descripcion','Lote',
            'Cantidad','Usuario','Fecha'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['folio_inventario'],
                $r['NConteo'],
                $r['idy_ubica'],
                $r['ntarima'],
                $r['cve_articulo'],
                $r['des_articulo'],
                $r['cve_lote'],
                $r['cantidad_conteo'],
                $r['cve_usuario'],
                $r['fecha'],
            ]);
        }
    } elseif ($tipo === 'F' && $view === 'dif') {
        fputcsv($out, [
            'Folio','Conteo','Ubicacion','Articulo','Descripcion',
            'Lote','Cantidad','Teorico','Dif_Pzs','Costo','Dif_Valor',
            'Proveedor','Cuarentena','Etiqueta','Usuario','Fecha'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['folio_inventario'],
                $r['NConteo'],
                $r['idy_ubica'],
                $r['cve_articulo'],
                $r['des_articulo'],
                $r['cve_lote'],
                $r['cantidad_conteo'],
                $r['ExistenciaTeorica'],
                $r['diferencia_piezas'],
                $r['costo_unitario'],
                $r['diferencia_valor'],
                $r['ID_Proveedor'],
                $r['Cuarentena'],
                $r['ClaveEtiqueta'],
                $r['cve_usuario'],
                $r['fecha'],
            ]);
        }
    } elseif ($tipo === 'C' && $view === 'det') {
        fputcsv($out, [
            'Plan','Conteo','Ubicacion','Articulo','Descripcion',
            'Lote','Cantidad','Usuario','Fecha'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['folio_plan'],
                $r['NConteo'],
                $r['idy_ubica'],
                $r['cve_articulo'],
                $r['des_articulo'],
                $r['cve_lote'],
                $r['cantidad_conteo'],
                $r['cve_usuario'],
                $r['fecha'],
            ]);
        }
    } else { // Cíclico diferencias
        fputcsv($out, [
            'Plan','Conteo','Ubicacion','Articulo','Descripcion',
            'Lote','Cantidad','Teorico','Dif_Pzs','Costo','Dif_Valor',
            'Proveedor','Cuarentena','Etiqueta','Usuario','Fecha'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['folio_plan'],
                $r['NConteo'],
                $r['idy_ubica'],
                $r['cve_articulo'],
                $r['des_articulo'],
                $r['cve_lote'],
                $r['cantidad_conteo'],
                $r['ExistenciaTeorica'],
                $r['diferencia_piezas'],
                $r['costo_unitario'],
                $r['diferencia_valor'],
                $r['Id_Proveedor'],
                $r['Cuarentena'],
                $r['ClaveEtiqueta'],
                $r['cve_usuario'],
                $r['fecha'],
            ]);
        }
    }

    fclose($out);
    exit;
}

// ---------------------------------------------------------
//  Vista normal (HTML)
// ---------------------------------------------------------
$conteosDisponibles = get_conteos_disponibles($tipo, $folio);
$rows               = get_det_rows($tipo, $view, $folio, $conteo);

$tituloBase = ($tipo === 'F') ? 'Inventario físico' : 'Inventario cíclico';
if ($view === 'dif') {
    $titulo = "Diferencias {$tituloBase} {$folio}";
} else {
    $titulo = "Detalle {$tituloBase} {$folio}";
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3" style="font-size:10px;">
    <div class="row mb-2">
        <div class="col">
            <h5 class="mb-0"><?= htmlspecialchars($titulo) ?></h5>
            <small class="text-muted">
                <a href="adm_inventarios.php" class="text-decoration-none">« Regresar</a>
            </small>
        </div>
        <div class="col-auto text-end">
            <?php
            // URL base para export (conservando filtros)
            $urlExport = 'adm_inventarios_det.php?tipo=' . urlencode($tipo)
                       . '&view=' . urlencode($view)
                       . '&folio=' . urlencode($folio)
                       . '&conteo=' . urlencode($conteo)
                       . '&export=csv';
            ?>
            <a href="<?= $urlExport ?>" class="btn btn-sm btn-success">
                Exportar CSV
            </a>
        </div>
    </div>

    <!-- Filtros locales de la vista -->
    <form method="get" class="card mb-2 shadow-sm border-0">
        <div class="card-body p-2">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
            <input type="hidden" name="folio" value="<?= htmlspecialchars($folio) ?>">

            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Folio</label>
                    <input type="text" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($folio) ?>" disabled>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Conteo</label>
                    <select name="conteo" class="form-select form-select-sm">
                        <option value="0">[Todos]</option>
                        <?php foreach ($conteosDisponibles as $c): ?>
                            <option value="<?= (int)$c ?>" <?= ($conteo == $c ? 'selected' : '') ?>>
                                Conteo <?= (int)$c ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary mt-3">
                        Aplicar filtros
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table id="tblDetInv" class="table table-sm table-striped table-bordered w-100" style="font-size:10px;">
                    <thead class="table-light">
                    <?php if ($tipo === 'F' && $view === 'det'): ?>
                        <tr>
                            <th>Folio</th>
                            <th>Conteo</th>
                            <th>Ubicación</th>
                            <th>Tarima</th>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th>Lote</th>
                            <th>Cant. Conteo</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    <?php elseif ($tipo === 'F' && $view === 'dif'): ?>
                        <tr>
                            <th>Folio</th>
                            <th>Conteo</th>
                            <th>Ubicación</th>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th>Lote</th>
                            <th>Cant. Conteo</th>
                            <th>Teórico</th>
                            <th>Dif. Pzs</th>
                            <th>Costo</th>
                            <th>Dif. Valor</th>
                            <th>Proveedor</th>
                            <th>Cuarentena</th>
                            <th>Etiqueta</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    <?php elseif ($tipo === 'C' && $view === 'det'): ?>
                        <tr>
                            <th>Plan</th>
                            <th>Conteo</th>
                            <th>Ubicación</th>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th>Lote</th>
                            <th>Cant. Conteo</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>Plan</th>
                            <th>Conteo</th>
                            <th>Ubicación</th>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th>Lote</th>
                            <th>Cant. Conteo</th>
                            <th>Teórico</th>
                            <th>Dif. Pzs</th>
                            <th>Costo</th>
                            <th>Dif. Valor</th>
                            <th>Proveedor</th>
                            <th>Cuarentena</th>
                            <th>Etiqueta</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    <?php endif; ?>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="16" class="text-center">Sin datos para los filtros seleccionados</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php if ($tipo === 'F' && $view === 'det'): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                                    <td><?= htmlspecialchars($r['NConteo']) ?></td>
                                    <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                                    <td><?= htmlspecialchars($r['ntarima']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                                    <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                                    <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                                </tr>
                            <?php elseif ($tipo === 'F' && $view === 'dif'): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                                    <td><?= htmlspecialchars($r['NConteo']) ?></td>
                                    <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                                    <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                                    <td class="text-end"><?= number_format($r['ExistenciaTeorica'], 2) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_piezas'], 2) ?></td>
                                    <td class="text-end"><?= number_format($r['costo_unitario'], 4) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_valor'], 2) ?></td>
                                    <td><?= htmlspecialchars($r['ID_Proveedor']) ?></td>
                                    <td><?= (int)$r['Cuarentena'] === 1 ? 'S' : 'N' ?></td>
                                    <td><?= htmlspecialchars($r['ClaveEtiqueta']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                                </tr>
                            <?php elseif ($tipo === 'C' && $view === 'det'): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                                    <td><?= htmlspecialchars($r['NConteo']) ?></td>
                                    <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                                    <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                                    <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                                    <td><?= htmlspecialchars($r['NConteo']) ?></td>
                                    <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                                    <td class="text-end"><?= number_format($r['cantidad_conteo'], 2) ?></td>
                                    <td class="text-end"><?= number_format($r['ExistenciaTeorica'], 2) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_piezas'], 2) ?></td>
                                    <td class="text-end"><?= number_format($r['costo_unitario'], 4) ?></td>
                                    <td class="text-end"><?= number_format($r['diferencia_valor'], 2) ?></td>
                                    <td><?= htmlspecialchars($r['Id_Proveedor']) ?></td>
                                    <td><?= (int)$r['Cuarentena'] === 1 ? 'S' : 'N' ?></td>
                                    <td><?= htmlspecialchars($r['ClaveEtiqueta']) ?></td>
                                    <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $.fn.DataTable) {
        $('#tblDetInv').DataTable({
            pageLength: 50,
            scrollX: true,
            lengthChange: false,
            ordering: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    }
});
</script>
