<?php
// public/procesos/cotizaciones.php

require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo(); // OBLIGATORIO con tu nuevo db.php (no se modifica db.php)

require_once __DIR__ . '/../bi/_menu_global.php';
// Si quieres usar el template de filtros/encabezados estándar, descomenta y ajusta ruta:
// require_once __DIR__ . '/../reportes/filtros_assistpro.php';

$mensaje = '';
$mensaje_tipo = 'success';

try {
    // -----------------------------------------------------------------
    // Catálogos básicos (pueden venir después desde filtros_assistpro)
    // -----------------------------------------------------------------
    $empresa_id = 1; // luego lo puedes tomar de sesión

    // Almacenes
    $stmt = $pdo->query("SELECT id, clave, nombre FROM c_almacenp ORDER BY nombre");
    $almacenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clientes
    $stmt = $pdo->query("SELECT id, razon_social FROM c_cliente ORDER BY razon_social");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Productos para datalist
    $stmt = $pdo->query("SELECT id, clave, descripcion FROM c_producto ORDER BY descripcion LIMIT 500");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -----------------------------------------------------------------
    // Guardar cotización
    // -----------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1) Encabezado
        $almacen_id      = isset($_POST['almacen_id']) ? (int)$_POST['almacen_id'] : 0;
        $cliente_id      = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
        $fecha_vigencia  = !empty($_POST['fecha_vigencia']) ? $_POST['fecha_vigencia'] : null;
        $moneda          = !empty($_POST['moneda']) ? $_POST['moneda'] : 'MXN';
        $tipo_cambio     = isset($_POST['tipo_cambio']) ? (float)$_POST['tipo_cambio'] : 1.0;
        $observaciones   = !empty($_POST['observaciones']) ? $_POST['observaciones'] : null;
        $usuario_crea    = 'SYSTEM'; // luego: usuario de sesión

        // 2) Detalle
        $prod_ids        = $_POST['prod_id']         ?? [];
        $descripciones   = $_POST['descripcion']     ?? [];
        $cantidades      = $_POST['cantidad']        ?? [];
        $precios         = $_POST['precio_unitario'] ?? [];
        $descuentos_pct  = $_POST['descuento_pct']   ?? [];
        $impuestos_pct   = $_POST['impuesto_pct']    ?? [];

        if ($almacen_id <= 0 || $cliente_id <= 0) {
            throw new Exception('Debe seleccionar Almacén y Cliente.');
        }

        if (empty($prod_ids)) {
            throw new Exception('Debe capturar al menos una línea de detalle.');
        }

        $tiene_lineas_validas = false;
        foreach ($prod_ids as $k => $pid) {
            $cant = isset($cantidades[$k]) ? (float)$cantidades[$k] : 0;
            if ($pid && $cant > 0) {
                $tiene_lineas_validas = true;
                break;
            }
        }
        if (!$tiene_lineas_validas) {
            throw new Exception('Todas las líneas tienen cantidad 0 o sin producto.');
        }

        // 3) Transacción + folio
        $pdo->beginTransaction();

        $modulo = 'COTIZACION';
        $serie  = 'A';

        $stmt = $pdo->prepare("CALL sp_next_folio(?, ?, ?, @folio_str, @folio_num)");
        $stmt->execute([$empresa_id, $modulo, $serie]);

        $rowFolio = $pdo->query("SELECT @folio_str AS folio_str, @folio_num AS folio_num")->fetch(PDO::FETCH_ASSOC);
        if (!$rowFolio || !$rowFolio['folio_str']) {
            throw new Exception('No se pudo generar el folio de cotización.');
        }
        $folio_str = $rowFolio['folio_str'];

        // 4) Insertar encabezado
        $sql_th = "INSERT INTO th_cotizacion (
                        empresa_id,
                        almacen_id,
                        folio,
                        serie,
                        cliente_id,
                        fecha_cotizacion,
                        fecha_vigencia,
                        moneda,
                        tipo_cambio,
                        subtotal,
                        descuento_total,
                        impuestos_total,
                        total,
                        estatus,
                        observaciones,
                        usuario_crea
                    ) VALUES (
                        :empresa_id,
                        :almacen_id,
                        :folio,
                        :serie,
                        :cliente_id,
                        NOW(),
                        :fecha_vigencia,
                        :moneda,
                        :tipo_cambio,
                        0, 0, 0, 0,
                        'BORRADOR',
                        :observaciones,
                        :usuario_crea
                    )";

        $stmt_th = $pdo->prepare($sql_th);
        $stmt_th->execute([
            ':empresa_id'    => $empresa_id,
            ':almacen_id'    => $almacen_id,
            ':folio'         => $folio_str,
            ':serie'         => $serie,
            ':cliente_id'    => $cliente_id,
            ':fecha_vigencia'=> $fecha_vigencia,
            ':moneda'        => $moneda,
            ':tipo_cambio'   => $tipo_cambio,
            ':observaciones' => $observaciones,
            ':usuario_crea'  => $usuario_crea
        ]);

        $th_id = $pdo->lastInsertId();

        // 5) Insertar detalle
        $sql_td = "INSERT INTO td_cotizacion (
                        th_id,
                        renglon,
                        producto_id,
                        descripcion,
                        unidad_id,
                        unidad_clave,
                        cantidad,
                        precio_unitario,
                        descuento_pct,
                        descuento_imp,
                        subtotal,
                        impuesto_pct,
                        impuesto_imp,
                        total_linea,
                        almacen_id,
                        fecha_promesa,
                        estatus_linea,
                        stock_disponible,
                        stock_comprometido
                    ) VALUES (
                        :th_id,
                        :renglon,
                        :producto_id,
                        :descripcion,
                        NULL,
                        NULL,
                        :cantidad,
                        :precio_unitario,
                        :descuento_pct,
                        :descuento_imp,
                        :subtotal,
                        :impuesto_pct,
                        :impuesto_imp,
                        :total_linea,
                        :almacen_id,
                        :fecha_promesa,
                        'ABIERTA',
                        NULL,
                        NULL
                    )";

        $stmt_td = $pdo->prepare($sql_td);

        $renglon = 0;
        $subtotal_global        = 0;
        $descuento_total_global = 0;
        $impuestos_total_global = 0;
        $total_global           = 0;

        foreach ($prod_ids as $k => $pid) {
            $pid  = (int)$pid;
            $cant = isset($cantidades[$k]) ? (float)$cantidades[$k] : 0;
            if ($pid <= 0 || $cant <= 0) {
                continue;
            }

            $renglon++;

            $desc_txt = isset($descripciones[$k]) ? trim($descripciones[$k]) : '';
            $precio   = isset($precios[$k])        ? (float)$precios[$k]       : 0;
            $desc_pct = isset($descuentos_pct[$k]) ? (float)$descuentos_pct[$k]: 0;
            $imp_pct  = isset($impuestos_pct[$k])  ? (float)$impuestos_pct[$k] : 0;

            $importe_bruto = $cant * $precio;
            $importe_desc  = ($desc_pct > 0) ? ($importe_bruto * ($desc_pct / 100)) : 0;
            $importe_sub   = $importe_bruto - $importe_desc;
            $importe_imp   = ($imp_pct > 0) ? ($importe_sub * ($imp_pct / 100)) : 0;
            $importe_tot   = $importe_sub + $importe_imp;

            $subtotal_global        += $importe_sub;
            $descuento_total_global += $importe_desc;
            $impuestos_total_global += $importe_imp;
            $total_global           += $importe_tot;

            $fecha_promesa = null; // luego la calculamos contra existencias reales

            $stmt_td->execute([
                ':th_id'          => $th_id,
                ':renglon'        => $renglon,
                ':producto_id'    => $pid,
                ':descripcion'    => $desc_txt,
                ':cantidad'       => $cant,
                ':precio_unitario'=> $precio,
                ':descuento_pct'  => $desc_pct,
                ':descuento_imp'  => $importe_desc,
                ':subtotal'       => $importe_sub,
                ':impuesto_pct'   => $imp_pct,
                ':impuesto_imp'   => $importe_imp,
                ':total_linea'    => $importe_tot,
                ':almacen_id'     => $almacen_id,
                ':fecha_promesa'  => $fecha_promesa
            ]);
        }

        if ($renglon === 0) {
            throw new Exception('No se insertó ninguna línea válida de detalle.');
        }

        // 6) Actualizar totales en encabezado
        $sql_upd = "UPDATE th_cotizacion
                    SET subtotal = :subtotal,
                        descuento_total = :descuento_total,
                        impuestos_total = :impuestos_total,
                        total = :total,
                        estatus = 'ABIERTA'
                    WHERE id = :id";
        $stmt_upd = $pdo->prepare($sql_upd);
        $stmt_upd->execute([
            ':subtotal'        => $subtotal_global,
            ':descuento_total' => $descuento_total_global,
            ':impuestos_total' => $impuestos_total_global,
            ':total'           => $total_global,
            ':id'              => $th_id
        ]);

        $pdo->commit();

        $mensaje = "Cotización guardada correctamente. Folio: {$folio_str}";
        $mensaje_tipo = 'success';
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensaje = 'Error: ' . $e->getMessage();
    $mensaje_tipo = 'danger';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizaciones – AssistPro</title>
    <link href="/assistpro_kardex_fc/public/assets/bootstrap.min.css" rel="stylesheet"><!-- ajusta si es necesario -->
    <style>
        body { font-size: 10px; }
        .table td, .table th { padding: 4px; }
        .form-control, .form-select { font-size: 10px; padding: 2px 4px; }
    </style>
</head>
<body>
<div class="container-fluid mt-3">
    <h5>Cotizaciones</h5>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($mensaje_tipo); ?> py-1">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="post" id="form-cotizacion">
        <input type="hidden" name="empresa_id" value="<?php echo (int)$empresa_id; ?>">

        <!-- Encabezado (luego podemos migrarlo a filtros_assistpro.php) -->
        <div class="card mb-3">
            <div class="card-header py-1">
                Datos de la cotización
            </div>
            <div class="card-body py-2">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label for="almacen_id" class="form-label">Almacén</label>
                        <select name="almacen_id" id="almacen_id" class="form-select form-select-sm" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($almacenes as $a): ?>
                                <option value="<?php echo (int)$a['id']; ?>">
                                    <?php echo htmlspecialchars($a['clave'] . ' - ' . $a['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-select form-select-sm" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>">
                                    <?php echo htmlspecialchars($c['razon_social']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_vigencia" class="form-label">Vigencia</label>
                        <input type="date" name="fecha_vigencia" id="fecha_vigencia"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label for="moneda" class="form-label">Moneda</label>
                        <select name="moneda" id="moneda" class="form-select form-select-sm">
                            <option value="MXN">MXN</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2">
                        <label for="tipo_cambio" class="form-label">Tipo cambio</label>
                        <input type="number" step="0.000001" name="tipo_cambio" id="tipo_cambio"
                               class="form-control form-control-sm" value="1.000000">
                    </div>
                    <div class="col-md-10">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <input type="text" name="observaciones" id="observaciones"
                               class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle -->
        <div class="card mb-3">
            <div class="card-header py-1 d-flex justify-content-between align-items-center">
                <span>Detalle de productos</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="agregarLinea()">Agregar línea</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0" id="tabla-detalle">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 18%;">Producto (ID)</th>
                            <th style="width: 30%;">Descripción</th>
                            <th style="width: 10%;">Cantidad</th>
                            <th style="width: 10%;">Precio</th>
                            <th style="width: 10%;">Desc. %</th>
                            <th style="width: 10%;">Imp. %</th>
                            <th style="width: 12%;">Total línea</th>
                            <th style="width: 5%;">X</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- filas dinámicas JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Totales visuales -->
        <div class="row mb-3">
            <div class="col-md-3 ms-auto">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-end">Subtotal:</td>
                        <td class="text-end"><span id="lbl-subtotal">0.00</span></td>
                    </tr>
                    <tr>
                        <td class="text-end">Descuento:</td>
                        <td class="text-end"><span id="lbl-descuento">0.00</span></td>
                    </tr>
                    <tr>
                        <td class="text-end">Impuestos:</td>
                        <td class="text-end"><span id="lbl-impuestos">0.00</span></td>
                    </tr>
                    <tr class="fw-bold">
                        <td class="text-end">Total:</td>
                        <td class="text-end"><span id="lbl-total">0.00</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-success btn-sm">Guardar cotización</button>
        </div>
    </form>
</div>

<!-- Datalist de productos -->
<datalist id="dl_productos">
    <?php foreach ($productos as $p): ?>
        <option value="<?php echo (int)$p['id']; ?>">
            <?php echo htmlspecialchars($p['clave'] . ' - ' . $p['descripcion']); ?>
        </option>
    <?php endforeach; ?>
</datalist>

<script>
    let contadorLineas = 0;

    function agregarLinea() {
        const tbody = document.querySelector('#tabla-detalle tbody');
        contadorLineas++;
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td class="text-center">${contadorLineas}</td>
            <td>
                <input list="dl_productos" name="prod_id[]" class="form-control form-control-sm">
            </td>
            <td>
                <input type="text" name="descripcion[]" class="form-control form-control-sm">
            </td>
            <td>
                <input type="number" step="0.0001" name="cantidad[]" class="form-control form-control-sm" value="0" oninput="recalcularTotales()">
            </td>
            <td>
                <input type="number" step="0.000001" name="precio_unitario[]" class="form-control form-control-sm" value="0" oninput="recalcularTotales()">
            </td>
            <td>
                <input type="number" step="0.01" name="descuento_pct[]" class="form-control form-control-sm" value="0" oninput="recalcularTotales()">
            </td>
            <td>
                <input type="number" step="0.01" name="impuesto_pct[]" class="form-control form-control-sm" value="0" oninput="recalcularTotales()">
            </td>
            <td class="text-end">
                <span class="total-linea">0.00</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarLinea(this)">X</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function eliminarLinea(btn) {
        const tr = btn.closest('tr');
        tr.remove();
        recalcularTotales();
    }

    function recalcularTotales() {
        let subtotal = 0, descTotal = 0, impTotal = 0, total = 0;

        const filas = document.querySelectorAll('#tabla-detalle tbody tr');
        filas.forEach(tr => {
            const cant = parseFloat(tr.querySelector('input[name="cantidad[]"]').value) || 0;
            const precio = parseFloat(tr.querySelector('input[name="precio_unitario[]"]').value) || 0;
            const descPct = parseFloat(tr.querySelector('input[name="descuento_pct[]"]').value) || 0;
            const impPct  = parseFloat(tr.querySelector('input[name="impuesto_pct[]"]').value)  || 0;

            const bruto = cant * precio;
            const desc = bruto * (descPct / 100);
            const sub  = bruto - desc;
            const imp  = sub * (impPct / 100);
            const tot  = sub + imp;

            subtotal += sub;
            descTotal += desc;
            impTotal += imp;
            total += tot;

            tr.querySelector('.total-linea').textContent = tot.toFixed(2);
        });

        document.getElementById('lbl-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('lbl-descuento').textContent = descTotal.toFixed(2);
        document.getElementById('lbl-impuestos').textContent = impTotal.toFixed(2);
        document.getElementById('lbl-total').textContent = total.toFixed(2);
    }

    // Línea inicial
    agregarLinea();
</script>

</body>
</html>
