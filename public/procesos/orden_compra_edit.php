<?php
// Versión de solo diseño de Orden de Compra (sin consultas, sin BD)
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AssistPro SFA — Editar | Crear Orden de Compra (Diseño)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Estilos base (Bootstrap 5 desde CDN o tu propio bundle) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
        }
        .ap-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 90, 173, 0.08);
            border: 1px solid #eef1f7;
            padding: 18px 20px;
            margin-bottom: 18px;
        }
        .ap-title {
            font-weight: 700;
            color: #0F5AAD;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .ap-subtitle {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 0;
        }
        .ap-label {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
        }
        .form-control, .form-select {
            border-radius: 10px;
            font-size: 13px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0F5AAD;
            box-shadow: 0 0 0 0.15rem rgba(15, 90, 173, .25);
        }
        .ap-section-title {
            font-weight: 700;
            font-size: 15px;
            color: #0F5AAD;
            margin-bottom: 8px;
        }
        table.table-sm th, table.table-sm td {
            font-size: 12px;
            vertical-align: middle;
        }
        .btn-ap-primary {
            background-color: #0F5AAD;
            border-color: #0F5AAD;
            color: #fff;
            border-radius: 999px;
            font-size: 13px;
            padding-inline: 18px;
        }
        .btn-ap-primary:hover {
            background-color: #0c4a8d;
            border-color: #0c4a8d;
        }
        .btn-ap-link {
            font-size: 13px;
            border-radius: 999px;
        }
        .badge-status {
            font-size: 11px;
            border-radius: 999px;
            padding: 4px 10px;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid py-3">
    <!-- Encabezado de la pantalla -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="ap-title mb-0">
                AssistPro SFA — <span style="font-weight:600;">Editar | Crear Orden de Compra</span>
            </div>
            <p class="ap-subtitle">Diseño estático de encabezado y detalle de la Orden de Compra (sin conexión a BD).</p>
        </div>
        <div>
            <a href="orden_compra.php" class="btn btn-outline-secondary btn-sm btn-ap-link">
                <span class="me-1">↩</span> Volver a la lista
            </a>
        </div>
    </div>

    <!-- Encabezado de la OC (solo UI) -->
    <form method="post" class="ap-card mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="ap-section-title mb-1">Encabezado</div>
                <small class="text-muted">Maqueta del formulario de encabezado para la Orden de Compra.</small>
            </div>
            <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle badge-status">
                    ABIERTA
                </span>
            </div>
        </div>

        <div class="row g-3">
            <!-- Fila 1 -->
            <div class="col-md-2">
                <label class="ap-label">Folio</label>
                <input type="text" class="form-control" placeholder="OC000123">
            </div>
            <div class="col-md-2">
                <label class="ap-label">Fecha OC</label>
                <input type="text" class="form-control" placeholder="dd/mm/aaaa">
            </div>
            <div class="col-md-3">
                <label class="ap-label">Empresa</label>
                <select class="form-select">
                    <option value="">Seleccione...</option>
                    <option>Empresa Demo 1</option>
                    <option>Empresa Demo 2</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="ap-label">Almacén</label>
                <select class="form-select">
                    <option value="">Seleccione...</option>
                    <option>Almacén Principal</option>
                    <option>Almacén Foráneo</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ap-label">Proveedor</label>
                <select class="form-select">
                    <option value="">Seleccione...</option>
                    <option>Proveedor Demo 1</option>
                    <option>Proveedor Demo 2</option>
                </select>
            </div>

            <!-- Fila 2 -->
            <div class="col-md-3">
                <label class="ap-label">Tipo OC</label>
                <select class="form-select">
                    <option value="">Seleccione...</option>
                    <option>COMPRA NACIONAL</option>
                    <option>IMPORTACIÓN</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="ap-label">Moneda</label>
                <select class="form-select">
                    <option value="">Seleccione...</option>
                    <option>MXN - Pesos Mexicanos</option>
                    <option>USD - Dólares</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="ap-label">Tipo de cambio</label>
                <input type="number" step="0.0001" class="form-control" value="1.0000">
            </div>
            <div class="col-md-2">
                <label class="ap-label">Folio ERP</label>
                <input type="text" class="form-control" placeholder="Ej. OC-ERP-001">
            </div>
            <div class="col-md-2">
                <label class="ap-label">Status</label>
                <select class="form-select">
                    <option>ABIERTA</option>
                    <option>CERRADA</option>
                    <option>CANCELADA</option>
                </select>
            </div>

            <!-- Fila 3 -->
            <div class="col-md-3">
                <label class="ap-label">ID Pedido relacionado (opcional)</label>
                <input type="number" class="form-control" placeholder="vincula materiales">
            </div>
            <div class="col-md-3">
                <label class="ap-label">Fecha Compromiso</label>
                <input type="text" class="form-control" placeholder="dd/mm/aaaa">
            </div>
            <div class="col-md-3">
                <label class="ap-label">Recepción Prevista</label>
                <input type="text" class="form-control" placeholder="dd/mm/aaaa">
            </div>
            <div class="col-md-3">
                <label class="ap-label">Comentarios</label>
                <input type="text" class="form-control" placeholder="Comentarios generales de la OC">
            </div>
        </div>

        <div class="mt-3 text-end">
            <button type="button" class="btn btn-ap-primary">
                Guardar encabezado (demo)
            </button>
            <a href="orden_compra.php" class="btn btn-outline-secondary btn-sm btn-ap-link ms-2">
                Volver a la lista
            </a>
        </div>
    </form>

    <!-- Detalle de la OC (productos) - solo diseño -->
    <div class="ap-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="ap-section-title mb-1">Detalle (productos)</div>
                <small class="text-muted">Maqueta de captura de productos, cantidades y precios.</small>
            </div>
        </div>

        <!-- Buscador / alta rápida de producto (solo UI) -->
        <form class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="ap-label">Buscar producto (clave / nombre)</label>
                <input type="text" class="form-control" placeholder="Teclea para buscar...">
            </div>
            <div class="col-md-1">
                <label class="ap-label">Clave</label>
                <input type="text" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="ap-label">Producto</label>
                <input type="text" class="form-control">
            </div>
            <div class="col-md-1">
                <label class="ap-label">UOM</label>
                <input type="text" class="form-control" placeholder="PZA">
            </div>
            <div class="col-md-1">
                <label class="ap-label">Cantidad</label>
                <input type="number" step="0.0001" class="form-control" value="0">
            </div>
            <div class="col-md-1">
                <label class="ap-label">Precio (NETO)</label>
                <input type="number" step="0.0001" class="form-control" value="0">
            </div>
            <div class="col-md-1">
                <label class="ap-label">IVA (%)</label>
                <input type="number" step="0.01" class="form-control" value="16">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-ap-primary w-100">
                    Agregar
                </button>
            </div>
        </form>

        <!-- Tabla de detalle (sin datos reales) -->
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">Acciones</th>
                        <th>Clave</th>
                        <th>Producto</th>
                        <th>UOM</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            (Diseño) No hay productos capturados. Aquí se mostrarían las partidas de la OC.
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="7" class="text-end">Subtotal</th>
                        <th class="text-end">0.00</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="7" class="text-end">IVA</th>
                        <th class="text-end">0.00</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="7" class="text-end">Total</th>
                        <th class="text-end">0.00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
