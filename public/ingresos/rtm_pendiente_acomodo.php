<?php
// public/procesos/rtm_pendiente_acomodo.php
// RTM: Producto pendiente de acomodo (vista administrativa)

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

//@session_start();

// --------- CATALOGOS PARA FILTROS ---------

// Proveedores desde la tabla de pendientes
$proveedores = db_all("
    SELECT DISTINCT 
        p.ID_Proveedor,
        prov.cve_proveedor,
        prov.Nombre AS proveedor
    FROM v_pendientesacomodo p
    LEFT JOIN c_proveedores prov
        ON prov.ID_Proveedor = p.ID_Proveedor
    ORDER BY proveedor
");

// --------- FILTROS ---------
$f_proveedor = $_GET['proveedor'] ?? '';
$f_articulo = $_GET['articulo'] ?? '';
$f_lote = $_GET['lote'] ?? '';
$f_ubica = $_GET['ubica'] ?? '';

$where = "1 = 1";
$params = [];

// proveedor
if ($f_proveedor !== '') {
    $where .= " AND p.ID_Proveedor = :proveedor";
    $params['proveedor'] = $f_proveedor;
}

// artículo (por clave o descripción)
if ($f_articulo !== '') {
    $where .= " AND (p.cve_articulo LIKE :articulo OR a.des_articulo LIKE :articulo)";
    $params['articulo'] = '%' . $f_articulo . '%';
}

// lote
if ($f_lote !== '') {
    $where .= " AND p.cve_lote LIKE :lote";
    $params['lote'] = '%' . $f_lote . '%';
}

// ubicación
if ($f_ubica !== '') {
    $where .= " AND p.cve_ubicacion LIKE :ubica";
    $params['ubica'] = '%' . $f_ubica . '%';
}

// --------- CONSULTA PRINCIPAL (SOLO LECTURA) ---------
$sql = "
    SELECT
        -- ID sintético solo para la grilla
        CONCAT_WS('::',
            p.cve_articulo,
            p.cve_lote,
            IFNULL(p.cve_ubicacion,''),
            IFNULL(p.ID_Proveedor,'')
        ) AS id,

        p.cve_articulo,
        a.des_articulo,
        p.cve_lote,
        l.Caducidad          AS caducidad,
        p.Cantidad           AS CantPendiente,
        p.cve_ubicacion,
        p.ID_Proveedor,
        prov.cve_proveedor,
        prov.Nombre          AS proveedor
    FROM v_pendientesacomodo p
    LEFT JOIN c_articulo a
           ON a.cve_articulo = p.cve_articulo
    LEFT JOIN c_lotes l
           ON l.cve_articulo = p.cve_articulo
          AND l.Lote         = p.cve_lote
    LEFT JOIN c_proveedores prov
           ON prov.ID_Proveedor = p.ID_Proveedor
    WHERE $where
    ORDER BY proveedor, p.cve_articulo, p.cve_lote
";

$rows = db_all($sql, $params);

// --------- KPI ---------
$total_lineas = count($rows);
$total_pendiente = 0.0;

foreach ($rows as $r) {
    $total_pendiente += (float) $r['CantPendiente'];
}
?>
<style>
    #rtm-table {
        font-size: 10px;
    }

    .kpi-card {
        border-radius: 6px;
        padding: 8px 12px;
        margin-bottom: 8px;
        background: #f8fafc;
        border-left: 4px solid #0F5AAD;
    }

    .kpi-title {
        font-size: 10px;
        text-transform: uppercase;
        color: #666;
        margin-bottom: 2px;
    }

    .kpi-value {
        font-size: 18px;
        font-weight: 600;
        color: #0F5AAD;
        line-height: 1.1;
    }

    .kpi-sub {
        font-size: 9px;
        color: #999;
    }
</style>

<div class="wrapper wrapper-content animated fadeIn">

    <div class="row">
        <div class="col-lg-12">
            <h3>RTM – Producto pendiente de acomodo</h3>
            <small>
                Vista administrativa basada en <strong>v_pendientesacomodo</strong>.
                Sólo muestra producto pendiente en zona de recibo / staging. No realiza movimientos.
            </small>
            <hr />
        </div>
    </div>

    <!-- KPI -->
    <div class="row">
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Líneas pendientes</div>
                <div class="kpi-value"><?= number_format($total_lineas) ?></div>
                <div class="kpi-sub">Registros en RTM</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Cantidad total pendiente</div>
                <div class="kpi-value"><?= number_format($total_pendiente, 3) ?></div>
                <div class="kpi-sub">Unidades sin acomodo</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row m-b-sm">
        <div class="col-md-12">
            <div class="ibox">
                <div class="ibox-title">
                    <h5>Filtros</h5>
                </div>
                <div class="ibox-content">
                    <form method="get" class="form-inline">
                        <div class="form-group m-r-sm">
                            <label for="proveedor">Proveedor:&nbsp;</label>
                            <select name="proveedor" id="proveedor" class="form-control input-sm">
                                <option value="">[Todos]</option>
                                <?php foreach ($proveedores as $p): ?>
                                    <option value="<?= htmlspecialchars($p['ID_Proveedor']) ?>"
                                        <?= $f_proveedor === (string) $p['ID_Proveedor'] ? 'selected' : '' ?>>
                                        (<?= htmlspecialchars($p['cve_proveedor']) ?>)
                                        <?= htmlspecialchars($p['proveedor']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group m-r-sm">
                            <label for="articulo">Artículo:&nbsp;</label>
                            <input type="text" name="articulo" id="articulo"
                                value="<?= htmlspecialchars($f_articulo) ?>" class="form-control input-sm"
                                placeholder="Clave o descripción">
                        </div>

                        <div class="form-group m-r-sm">
                            <label for="lote">Lote:&nbsp;</label>
                            <input type="text" name="lote" id="lote" value="<?= htmlspecialchars($f_lote) ?>"
                                class="form-control input-sm" placeholder="Lote">
                        </div>

                        <div class="form-group m-r-sm">
                            <label for="ubica">Ubicación/Zona:&nbsp;</label>
                            <input type="text" name="ubica" id="ubica" value="<?= htmlspecialchars($f_ubica) ?>"
                                class="form-control input-sm" placeholder="Zona recibo / staging">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm m-l-sm">
                            <i class="fa fa-search"></i> Aplicar filtros
                        </button>
                        <a href="rtm_pendiente_acomodo.php" class="btn btn-default btn-sm m-l-sm">
                            <i class="fa fa-eraser"></i> Limpiar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Grilla RTM -->
    <div class="row">
        <div class="col-md-12">
            <div class="ibox">
                <div class="ibox-title">
                    <h5>Detalle de producto pendiente de acomodo</h5>
                </div>
                <div class="ibox-content">
                    <div class="table-responsive">
                        <table id="rtm-table" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Proveedor</th>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th>Lote</th>
                                    <th>Caducidad</th>
                                    <th>Ubicación / Zona</th>
                                    <th>Cantidad pendiente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($r['cve_proveedor']) ?>
                                            -
                                            <?= htmlspecialchars($r['proveedor']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
                                        <td><?= htmlspecialchars($r['des_articulo']) ?></td>
                                        <td><?= htmlspecialchars($r['cve_lote']) ?></td>
                                        <td><?= htmlspecialchars($r['caducidad']) ?></td>
                                        <td><?= htmlspecialchars($r['cve_ubicacion']) ?></td>
                                        <td style="text-align:right;"><?= number_format($r['CantPendiente'], 3) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <small>* Fuente: v_pendientesacomodo. Solo lectura, sin acomodo ni traslado.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    $(function () {
        if ($.fn.DataTable) {
            $('#rtm-table').DataTable({
                pageLength: 25,
                order: [[0, 'asc'], [1, 'asc']],
                scrollX: true,
                scrollY: '50vh',
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'excel', text: 'Excel' },
                    { extend: 'pdf', text: 'PDF' },
                    { extend: 'print', text: 'Imprimir' }
                ]
            });

            // Ajuste de clases a estilo AssistPro
            $('.dt-button').addClass('btn btn-sm').removeClass('dt-button');
        }
    });
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
