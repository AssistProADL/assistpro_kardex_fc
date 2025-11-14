<?php
// public/crm/cotizaciones.php
// Módulo CRM – Cotizaciones leyendo productos, precios y stock reales de AssistPro

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$mensaje = '';
$error   = '';

try {
    // Obtener PDO desde db.php y asegurar excepciones
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================
    // 1. Guardar cotización
    // =========================
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['accion'])
        && $_POST['accion'] === 'guardar'
    ) {
        // Datos encabezado
        $id_cliente     = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
        $cve_clte       = !empty($_POST['cve_clte']) ? trim((string)$_POST['cve_clte']) : null;
        $fuente_id      = !empty($_POST['fuente_id']) ? (int)$_POST['fuente_id'] : null;
        $fuente_detalle = !empty($_POST['fuente_detalle']) ? trim((string)$_POST['fuente_detalle']) : null;

        $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

        // Validación mínima: al menos un renglón con artículo + cantidad
        $tiene_renglones = false;
        foreach ($items as $row) {
            $cve_articulo = isset($row['cve_articulo']) ? trim((string)$row['cve_articulo']) : '';
            $cantidad     = isset($row['cantidad']) ? (float)$row['cantidad'] : 0.0;

            if ($cve_articulo !== '' && $cantidad > 0) {
                $tiene_renglones = true;
                break;
            }
        }

        if (!$tiene_renglones) {
            throw new Exception('Debes capturar al menos un renglón con artículo y cantidad.');
        }

        // Generar folio simple de cotización (puede reemplazarse por SP de folios)
        $folio_cotizacion = 'COT-' . date('Ymd-His');

        // Calcular total
        $total = 0.0;
        foreach ($items as $row) {
            $cant   = isset($row['cantidad']) ? (float)$row['cantidad'] : 0.0;
            $precio = isset($row['precio_unitario']) ? (float)$row['precio_unitario'] : 0.0;
            if ($cant > 0 && $precio >= 0) {
                $total += $cant * $precio;
            }
        }

        // Transacción para guardar encabezado + detalle
        $pdo->beginTransaction();

        // Encabezado de cotización
        $sqlInsHead = "
            INSERT INTO crm_cotizacion (
                folio_cotizacion,
                fecha,
                id_cliente,
                cve_clte,
                fuente_id,
                fuente_detalle,
                total,
                estado
            ) VALUES (
                :folio,
                NOW(),
                :id_cliente,
                :cve_clte,
                :fuente_id,
                :fuente_detalle,
                :total,
                'BORRADOR'
            )
        ";
        $stmtHead = $pdo->prepare($sqlInsHead);
        $stmtHead->execute([
            ':folio'          => $folio_cotizacion,
            ':id_cliente'     => $id_cliente ?: null,
            ':cve_clte'       => $cve_clte ?: null,
            ':fuente_id'      => $fuente_id ?: null,
            ':fuente_detalle' => $fuente_detalle ?: null,
            ':total'          => $total,
        ]);

        $cotizacion_id = (int)$pdo->lastInsertId();

        // Detalle de cotización
        $sqlInsDet = "
            INSERT INTO crm_cotizacion_det (
                cotizacion_id,
                cve_articulo,
                descripcion,
                cantidad,
                precio_unitario,
                subtotal,
                existencia
            ) VALUES (
                :cotizacion_id,
                :cve_articulo,
                :descripcion,
                :cantidad,
                :precio_unitario,
                :subtotal,
                :existencia
            )
        ";
        $stmtDet = $pdo->prepare($sqlInsDet);

        foreach ($items as $row) {
            $cve_articulo = isset($row['cve_articulo']) ? trim((string)$row['cve_articulo']) : '';
            $descripcion  = isset($row['descripcion']) ? trim((string)$row['descripcion']) : '';
            $cantidad     = isset($row['cantidad']) ? (float)$row['cantidad'] : 0.0;
            $precio       = isset($row['precio_unitario']) ? (float)$row['precio_unitario'] : 0.0;
            $existencia   = isset($row['existencia']) && $row['existencia'] !== ''
                            ? (float)$row['existencia']
                            : null;

            if ($cve_articulo === '' || $cantidad <= 0) {
                continue;
            }

            $subtotal = $cantidad * $precio;

            $stmtDet->execute([
                ':cotizacion_id'  => $cotizacion_id,
                ':cve_articulo'   => $cve_articulo,
                ':descripcion'    => $descripcion,
                ':cantidad'       => $cantidad,
                ':precio_unitario'=> $precio,
                ':subtotal'       => $subtotal,
                ':existencia'     => $existencia,
            ]);
        }

        $pdo->commit();
        $mensaje = "Cotización {$folio_cotizacion} guardada correctamente.";
    }

    // =========================
    // 2. Catálogos base (BD actual)
    // =========================

    // Clientes desde c_cliente
    $sqlClientes = "
        SELECT id_cliente, Cve_Clte, RazonSocial
        FROM c_cliente
        ORDER BY RazonSocial
    ";
    $clientes = db_all($sqlClientes);

    // Fuentes desde c_pedido_fuente
    $sqlFuentes = "
        SELECT id, clave, descripcion
        FROM c_pedido_fuente
        WHERE activo = 1
        ORDER BY descripcion
    ";
    $fuentes = db_all($sqlFuentes);

    // Productos + precio + existencia desde c_articulo + mv_existencia_gral
    $sqlProductos = "
        SELECT
            a.cve_articulo,
            a.des_articulo,
            a.PrecioVenta,
            IFNULL(eg.ExistenciaTotal, 0) AS existencia
        FROM c_articulo a
        LEFT JOIN (
            SELECT cve_articulo, SUM(Existencia) AS ExistenciaTotal
            FROM mv_existencia_gral
            WHERE IFNULL(Cuarentena, 0) = 0
            GROUP BY cve_articulo
        ) eg ON eg.cve_articulo = a.cve_articulo
        WHERE a.Activo = 1
        ORDER BY a.des_articulo
    ";
    $productos = db_all($sqlProductos);

    // Cotizaciones recientes
    $sqlCotizaciones = "
        SELECT
            c.id,
            c.folio_cotizacion,
            c.fecha,
            c.total,
            c.estado,
            cli.RazonSocial AS cliente,
            f.descripcion   AS fuente
        FROM crm_cotizacion c
        LEFT JOIN c_cliente       cli ON cli.id_cliente = c.id_cliente
        LEFT JOIN c_pedido_fuente f   ON f.id = c.fuente_id
        ORDER BY c.fecha DESC
        LIMIT 50
    ";
    $cotizaciones = db_all($sqlCotizaciones);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotizaciones CRM</title>
    <link rel="stylesheet" href="/assistpro_kardex_fc/public/assets/bootstrap.min.css">
    <link rel="stylesheet" href="/assistpro_kardex_fc/public/assets/fontawesome.min.css">
    <style>
        body { font-size: 10px; }
        .card { margin-bottom: 10px; }
        .table-sm td, .table-sm th { padding: 0.25rem; }
        .form-control, .form-select { font-size: 10px; }
    </style>
</head>
<body>

<div class="container-fluid mt-2">

    <h5>Cotizaciones (CRM)</h5>

    <?php if ($mensaje): ?>
        <div class="alert alert-success py-1"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger py-1"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Formulario de nueva cotización -->
    <div class="card">
        <div class="card-header py-1">
            Nueva cotización
        </div>
        <div class="card-body py-2">
            <form method="post">
                <input type="hidden" name="accion" value="guardar">

                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label mb-0">Cliente</label>
                        <select name="id_cliente" id="id_cliente" class="form-select form-select-sm">
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?php echo (int)$cli['id_cliente']; ?>">
                                    <?php echo htmlspecialchars($cli['RazonSocial'] . ' [' . $cli['Cve_Clte'] . ']', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Catálogo: c_cliente</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-0">Clave cliente (opcional)</label>
                        <input type="text" name="cve_clte" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Fuente</label>
                        <select name="fuente_id" class="form-select form-select-sm">
                            <option value="">-- Sin especificar --</option>
                            <?php foreach ($fuentes as $f): ?>
                                <option value="<?php echo (int)$f['id']; ?>">
                                    <?php echo htmlspecialchars($f['descripcion'] . ' [' . $f['clave'] . ']', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Catálogo: c_pedido_fuente</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-0">Detalle de fuente</label>
                        <input type="text" name="fuente_detalle" class="form-control form-control-sm"
                               placeholder="Ej. Amazon MX FBA, ML Tienda oficial, Campaña X...">
                    </div>
                </div>

                <!-- Renglones de detalle -->
                <div class="table-responsive" style="max-height: 320px; overflow: auto;">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 30%;">Artículo</th>
                            <th style="width: 25%;">Descripción</th>
                            <th style="width: 10%;">Existencia</th>
                            <th style="width: 10%;">Cantidad</th>
                            <th style="width: 10%;">Precio</th>
                            <th style="width: 15%;">Subtotal</th>
                        </tr>
                        </thead>
                        <tbody id="detalle-rows">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <tr>
                                <td>
                                    <select name="items[<?php echo $i; ?>][cve_articulo]"
                                            class="form-select form-select-sm articulo-select">
                                        <option value="">-- Seleccione --</option>
                                        <?php foreach ($productos as $p): ?>
                                            <option value="<?php echo htmlspecialchars($p['cve_articulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-des="<?php echo htmlspecialchars($p['des_articulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-precio="<?php echo htmlspecialchars((string)$p['PrecioVenta'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-existencia="<?php echo htmlspecialchars((string)$p['existencia'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($p['des_articulo'] . ' [' . $p['cve_articulo'] . ']', ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text"
                                           name="items[<?php echo $i; ?>][descripcion]"
                                           class="form-control form-control-sm descripcion">
                                </td>
                                <td>
                                    <input type="text"
                                           name="items[<?php echo $i; ?>][existencia]"
                                           class="form-control form-control-sm existencia"
                                           readonly>
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0"
                                           name="items[<?php echo $i; ?>][cantidad]"
                                           class="form-control form-control-sm cantidad">
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0"
                                           name="items[<?php echo $i; ?>][precio_unitario]"
                                           class="form-control form-control-sm precio">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm subtotal" readonly>
                                </td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2">
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Guardar cotización
                        </button>
                    </div>
                    <div class="col-md-4 offset-md-4 text-end">
                        <label class="form-label mb-0 fw-bold">Total estimado:</label>
                        <span id="total-estimado" class="fw-bold">$0.00</span>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Listado de últimas cotizaciones -->
    <div class="card">
        <div class="card-header py-1">
            Últimas cotizaciones
        </div>
        <div class="card-body py-2">
            <div class="table-responsive" style="max-height: 260px; overflow: auto;">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Fuente</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($cotizaciones)): ?>
                        <?php foreach ($cotizaciones as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['folio_cotizacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($c['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$c['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$c['fuente'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?php echo number_format((float)$c['total'], 2); ?></td>
                                <td><?php echo htmlspecialchars($c['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">Sin cotizaciones capturadas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
// JS para rellenar descripción, existencia y subtotal
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('#detalle-rows tr');

    function recalcTotals() {
        let total = 0;
        rows.forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad')?.value || '0');
            const precio   = parseFloat(row.querySelector('.precio')?.value || '0');
            const subtotal = cantidad * precio;
            if (!isNaN(subtotal)) {
                const subInput = row.querySelector('.subtotal');
                if (subInput) subInput.value = subtotal.toFixed(2);
                total += subtotal;
            }
        });
        const totalSpan = document.getElementById('total-estimado');
        if (totalSpan) {
            totalSpan.textContent = '$' + total.toFixed(2);
        }
    }

    rows.forEach(row => {
        const selArt    = row.querySelector('.articulo-select');
        const descInput = row.querySelector('.descripcion');
        const exiInput  = row.querySelector('.existencia');
        const precio    = row.querySelector('.precio');
        const cantidad  = row.querySelector('.cantidad');

        if (selArt) {
            selArt.addEventListener('change', function () {
                const opt = selArt.selectedOptions[0];
                if (!opt) return;
                const des  = opt.getAttribute('data-des') || '';
                const prec = opt.getAttribute('data-precio') || '0';
                const exi  = opt.getAttribute('data-existencia') || '0';

                if (descInput) descInput.value = des;
                if (exiInput) exiInput.value = exi;
                if (precio && !precio.value) precio.value = prec;

                recalcTotals();
            });
        }

        if (precio)   precio.addEventListener('input', recalcTotals);
        if (cantidad) cantidad.addEventListener('input', recalcTotals);
    });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
