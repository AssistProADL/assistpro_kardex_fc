<?php
// public/portal_clientes/catalogo.php

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth_check.php'; // <<< IMPORTANTE: antes de cualquier HTML

// ============================================================================
// Datos de usuario / cliente (si aplica)
// ============================================================================
$usuario   = $_SESSION['username'] ?? '';
$clienteId = $_SESSION['cve_cliente'] ?? null; // ajusta al nombre real del campo si es distinto

// ============================================================================
// Banners de e-commerce (opcional)
// ============================================================================
$banners = [];
try {
    $banners = db_all("
        SELECT id, titulo, imagen_url, enlace_url
        FROM t_ecommerce_banners
        WHERE IFNULL(activo,1) = 1
        ORDER BY orden, id
    ");
} catch (Throwable $e) {
    $banners = [];
}

// ============================================================================
// Estadísticas rápidas (totales, genérica / sin categoría)
// ============================================================================
$stats = [
    'total'     => 0,
    'generica'  => 0,
    'sin_cat'   => 0,
];

try {
    $row = db_one("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN IFNULL(ecommerce_categoria,'') <> '' THEN 1 ELSE 0 END) AS generica,
            SUM(CASE WHEN IFNULL(ecommerce_categoria,'')  = '' THEN 1 ELSE 0 END) AS sin_cat
        FROM v_ecommerce_articulos
    ");

    if ($row) {
        $stats['total']    = (int)$row['total'];
        $stats['generica'] = (int)$row['generica'];
        $stats['sin_cat']  = (int)$row['sin_cat'];
    }
} catch (Throwable $e) {
    // Si falla la vista no rompemos la página
}

// ============================================================================
// HTML
// ============================================================================
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Catálogo E-Commerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap / estilos corporativos ya vienen en _menu_global -->
    <link rel="stylesheet" href="../assets/css/assistpro.css?v=1">
    <style>
        .ec-banner-wrapper {
            margin-bottom: 15px;
        }
        .ec-banner {
            border-radius: 10px;
            overflow: hidden;
            max-height: 220px;
        }
        .ec-banner img {
            width: 100%;
            object-fit: cover;
        }
        .ec-catalog-summary {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        .ec-card {
            border-radius: 10px;
            border: 1px solid #e3e6f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: box-shadow 0.2s ease, transform 0.1s ease;
        }
        .ec-card:hover {
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .ec-card-img {
            height: 180px;
            background-color: #f5f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #eee;
            overflow: hidden;
        }
        .ec-card-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .ec-card-body {
            padding: 10px 12px 12px 12px;
            font-size: 11px;
        }
        .ec-card-title {
            font-size: 12px;
            font-weight: 600;
            min-height: 32px;
            margin-bottom: 4px;
        }
        .ec-card-sku {
            font-size: 10px;
            color: #999;
        }
        .ec-card-price {
            font-size: 14px;
            font-weight: 700;
            color: #007bff;
            margin-top: 6px;
        }
        .ec-card-footer {
            padding: 8px 12px 10px 12px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ec-card-footer .btn {
            font-size: 10px;
            padding: 2px 8px;
        }
        .ec-search-row {
            margin-bottom: 10px;
        }
        .ec-search-row input,
        .ec-search-row select {
            font-size: 11px;
            height: 30px;
            padding: 4px 8px;
        }
        .ec-search-row .btn {
            font-size: 11px;
            padding: 4px 10px;
        }
        .ec-cart-widget {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 220px;
            z-index: 1050;
        }
        .ec-cart-widget .card {
            font-size: 11px;
        }
        .ec-cart-widget .card-body {
            padding: 8px 10px;
        }
        .ec-cart-widget .btn {
            font-size: 10px;
            padding: 4px 8px;
        }
        .badge-ecategoria {
            font-size: 9px;
            background-color: #e9f2ff;
            color: #0056b3;
            border-radius: 20px;
            padding: 2px 7px;
        }
        .modal-ec .modal-header {
            padding: 10px 15px;
        }
        .modal-ec .modal-body {
            font-size: 11px;
        }
        .modal-ec h5 {
            font-size: 13px;
            font-weight: 600;
        }
        .ft-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .ft-grid th,
        .ft-grid td {
            border: 1px solid #ddd;
            padding: 4px 6px;
        }
        .ft-grid th {
            background-color: #f7f7f7;
            font-weight: 600;
        }
        .ec-compare {
            font-size: 10px;
        }
        .ec-compare input {
            margin-right: 2px;
        }
    </style>

    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="mt-3 mb-1">Catálogo E-Commerce</h4>

            <div class="ec-catalog-summary">
                Total: <strong id="lblTotalArt"><?php echo number_format($stats['total']); ?></strong>
                &nbsp;|&nbsp;
                Genérica: <strong><?php echo number_format($stats['generica']); ?></strong>
                &nbsp;|&nbsp;
                Sin categoría: <strong><?php echo number_format($stats['sin_cat']); ?></strong>
            </div>

            <?php if (!empty($banners)) : ?>
                <div id="ecBannerCarousel" class="carousel slide ec-banner-wrapper" data-bs-ride="carousel">
                    <div class="carousel-inner ec-banner">
                        <?php foreach ($banners as $i => $b) : ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <?php if (!empty($b['enlace_url'])) : ?>
                                    <a href="<?php echo htmlspecialchars($b['enlace_url']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($b['imagen_url']); ?>" alt="<?php echo htmlspecialchars($b['titulo']); ?>">
                                    </a>
                                <?php else : ?>
                                    <img src="<?php echo htmlspecialchars($b['imagen_url']); ?>" alt="<?php echo htmlspecialchars($b['titulo']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($banners) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#ecBannerCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#ecBannerCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-2">
                <div class="card-body py-2">
                    <div class="row ec-search-row align-items-center">
                        <div class="col-md-4 col-sm-6 mb-1">
                            <label class="form-label mb-1" style="font-size:10px;">Buscar (SKU, descripción, t.)</label>
                            <input type="text" id="txtBuscar" class="form-control" placeholder="Escriba para buscar...">
                        </div>
                        <div class="col-md-3 col-sm-6 mb-1">
                            <label class="form-label mb-1" style="font-size:10px;">Categoría</label>
                            <select id="cmbCategoria" class="form-select">
                                <option value="">Todas las categorías</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 mb-1 d-flex align-items-end">
                            <button id="btnBuscar" class="btn btn-primary me-1">Buscar</button>
                            <button id="btnLimpiar" class="btn btn-outline-secondary">Limpiar</button>
                        </div>
                        <div class="col-md-3 text-end d-none d-md-block">
                            <small style="font-size:10px;color:#888;">
                                Visitante: <strong><?php echo $clienteId ? 'Cliente ' . htmlspecialchars((string)$clienteId) : 'Genérico'; ?></strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div id="ecCatalogo" class="row">
                <!-- Tarjetas aquí -->
            </div>

            <div class="text-end mt-1 mb-3" style="font-size:11px;">
                Total productos mostrados: <span id="lblTotalMostrados">0</span>
            </div>
        </div>
    </div>
</div>

<!-- Widget carrito -->
<div class="ec-cart-widget">
    <div class="card shadow">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong>Carrito</strong>
                <span style="font-size:10px;color:#999;">e-commerce</span>
            </div>
            <div style="font-size:11px;">
                Artículos: <span id="cartItemsCount">0</span><br>
                Total: <span id="cartTotal">$0.00</span>
            </div>
            <div class="mt-2 d-grid gap-1">
                <button id="btnVerCarrito" class="btn btn-outline-primary btn-sm">Ver detalle</button>
                <button id="btnIrPedidos" class="btn btn-primary btn-sm">Mis pedidos</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle (producto / ficha técnica / carrito) -->
<div class="modal fade modal-ec" id="ecDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del carrito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-2">
                    <table class="table table-sm table-striped" style="font-size:11px;">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Descripción</th>
                                <th style="width:80px;text-align:right;">Precio</th>
                                <th style="width:70px;text-align:center;">Cant.</th>
                                <th style="width:90px;text-align:right;">Subtotal</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tblCarritoBody">
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th class="text-end" id="tblCarritoTotal">$0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" id="btnConfirmarPedido" class="btn btn-primary btn-sm">Confirmar pedido</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
    // ============================================================
    // Utilidades
    // ============================================================
    function formatoMoneda(valor) {
        valor = parseFloat(valor || 0);
        return '$' + valor.toFixed(2);
    }

    // Carrito en memoria
    let carrito = [];

    function actualizarResumenCarrito() {
        let totalItems = 0;
        let totalMonto = 0;
        carrito.forEach(item => {
            totalItems += item.cantidad;
            totalMonto += item.cantidad * item.precio;
        });
        $('#cartItemsCount').text(totalItems);
        $('#cartTotal').text(formatoMoneda(totalMonto));
        $('#tblCarritoTotal').text(formatoMoneda(totalMonto));
    }

    function renderizarCarritoTabla() {
        const $tbody = $('#tblCarritoBody');
        $tbody.empty();

        carrito.forEach((item, idx) => {
            const $tr = $('<tr></tr>');
            $tr.append($('<td></td>').text(item.cve_articulo));
            $tr.append($('<td></td>').text(item.des_articulo));
            $tr.append($('<td class="text-end"></td>').text(formatoMoneda(item.precio)));

            const $inpCant = $('<input type="number" min="1" class="form-control form-control-sm" style="font-size:10px;text-align:center;">')
                .val(item.cantidad)
                .on('change', function () {
                    let val = parseInt($(this).val(), 10);
                    if (isNaN(val) || val <= 0) val = 1;
                    carrito[idx].cantidad = val;
                    renderizarCarritoTabla();
                    actualizarResumenCarrito();
                });

            $tr.append($('<td class="text-center"></td>').append($inpCant));

            const subtotal = item.cantidad * item.precio;
            $tr.append($('<td class="text-end"></td>').text(formatoMoneda(subtotal)));

            const $btnDel = $('<button type="button" class="btn btn-sm btn-link text-danger" style="font-size:11px;">X</button>')
                .on('click', function () {
                    carrito.splice(idx, 1);
                    renderizarCarritoTabla();
                    actualizarResumenCarrito();
                });

            $tr.append($('<td class="text-center"></td>').append($btnDel));

            $tbody.append($tr);
        });
    }

    function agregarAlCarrito(prod) {
        const idx = carrito.findIndex(x => x.id === prod.id);
        if (idx >= 0) {
            carrito[idx].cantidad += 1;
        } else {
            carrito.push({
                id: prod.id,
                cve_articulo: prod.cve_articulo,
                des_articulo: prod.des_articulo,
                precio: parseFloat(prod.PrecioVenta || 0),
                cantidad: 1
            });
        }
        actualizarResumenCarrito();
    }

    // ============================================================
    // Carga catálogo
    // ============================================================
    function cargarCatalogo() {
        const q = $('#txtBuscar').val();
        const categoria = $('#cmbCategoria').val();

        $('#ecCatalogo').html('<div class="col-12 text-center py-3" style="font-size:11px;">Cargando catálogo...</div>');

        $.getJSON('../api/ecommerce_articulos.php', {
            q: q,
            categoria: categoria
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                alert(resp && resp.error ? resp.error : 'Error al cargar catálogo.');
                $('#ecCatalogo').empty();
                $('#lblTotalMostrados').text('0');
                return;
            }

            const data = resp.data || [];
            renderTarjetas(data);
        }).fail(function () {
            alert('Error de comunicación con el servidor (catálogo).');
            $('#ecCatalogo').empty();
            $('#lblTotalMostrados').text('0');
        });
    }

    function renderTarjetas(data) {
        const $grid = $('#ecCatalogo');
        $grid.empty();

        const categorias = new Set();

        data.forEach(prod => {
            if (prod.ecommerce_categoria) {
                categorias.add(prod.ecommerce_categoria);
            }

            const imgUrl = prod.ecommerce_img_principal && prod.ecommerce_img_principal !== ''
                ? prod.ecommerce_img_principal
                : '../../public/img/no-image-640x480.png';

            const precio = parseFloat(prod.PrecioVenta || 0);

            const $col = $('<div class="col-xl-3 col-lg-4 col-md-4 col-sm-6"></div>');
            const $card = $('<div class="ec-card"></div>');

            const $imgDiv = $('<div class="ec-card-img"></div>');
            const $img = $('<img alt="Producto">').attr('src', imgUrl);
            $imgDiv.append($img);

            const $body = $('<div class="ec-card-body"></div>');
            $body.append($('<div class="ec-card-title"></div>').text(prod.des_articulo || ''));
            $body.append($('<div class="ec-card-sku"></div>').text(prod.cve_articulo || ''));

            if (prod.ecommerce_categoria) {
                const $cat = $('<div class="mt-1"></div>').append(
                    $('<span class="badge-ecategoria"></span>').text(prod.ecommerce_categoria)
                );
                $body.append($cat);
            }

            const $price = $('<div class="ec-card-price"></div>').text(formatoMoneda(precio));
            $body.append($price);

            const $footer = $('<div class="ec-card-footer"></div>');

            const $btnDetalle = $('<button class="btn btn-outline-primary btn-sm">Ver detalle</button>')
                .on('click', function () {
                    mostrarDetalleProducto(prod);
                });

            const $btnAgregar = $('<button class="btn btn-primary btn-sm">Agregar</button>')
                .on('click', function () {
                    agregarAlCarrito(prod);
                });

            const $compare = $('<div class="ec-compare"></div>');
            const $chk = $('<input type="checkbox" class="form-check-input">');
            $compare.append($chk).append(' Comparar');

            $footer.append($btnDetalle);
            $footer.append($btnAgregar);

            $card.append($imgDiv);
            $card.append($body);
            $card.append($footer);

            $col.append($card);
            $grid.append($col);
        });

        // Llenar combo de categorías
        const $cmb = $('#cmbCategoria');
        const selectedCat = $cmb.val();
        $cmb.empty();
        $cmb.append('<option value="">Todas las categorías</option>');
        Array.from(categorias).sort().forEach(c => {
            const $opt = $('<option></option>').val(c).text(c);
            $cmb.append($opt);
        });
        if (selectedCat) {
            $cmb.val(selectedCat);
        }

        $('#lblTotalMostrados').text(data.length);
    }

    // ============================================================
    // Detalle de producto (reusa modal de carrito, solo informativo)
    // ============================================================
    function mostrarDetalleProducto(prod) {
        // Por ahora mostramos sólo carrito. Si quieres detalle/ficha aparte,
        // aquí podríamos abrir otro modal o cargar ficha desde API.
        if (carrito.length === 0) {
            agregarAlCarrito(prod);
        }
        renderizarCarritoTabla();
        actualizarResumenCarrito();
        $('#ecDetalleModal .modal-title').text('Detalle del carrito');
        const modal = new bootstrap.Modal(document.getElementById('ecDetalleModal'));
        modal.show();
    }

    // ============================================================
    // Confirmar pedido
    // ============================================================
    function enviarPedido() {
        if (carrito.length === 0) {
            alert('El carrito está vacío.');
            return;
        }

        const payload = {
            items: carrito
        };

        $.ajax({
            url: '../api/ecommerce_pedidos.php',
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json'
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                alert(resp && resp.error ? resp.error : 'Error al registrar el pedido.');
                return;
            }

            alert('Pedido registrado correctamente con folio: ' + resp.folio);
            carrito = [];
            renderizarCarritoTabla();
            actualizarResumenCarrito();
            const modalEl = document.getElementById('ecDetalleModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        }).fail(function () {
            alert('Error de comunicación al guardar el pedido.');
        });
    }

    // ============================================================
    // Inicialización
    // ============================================================
    $(function () {
        // Buscar
        $('#btnBuscar').on('click', function () {
            cargarCatalogo();
        });

        $('#txtBuscar').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                cargarCatalogo();
            }
        });

        // Limpiar
        $('#btnLimpiar').on('click', function () {
            $('#txtBuscar').val('');
            $('#cmbCategoria').val('');
            cargarCatalogo();
        });

        // Ver carrito
        $('#btnVerCarrito').on('click', function () {
            renderizarCarritoTabla();
            const modal = new bootstrap.Modal(document.getElementById('ecDetalleModal'));
            modal.show();
        });

        // Confirmar pedido
        $('#btnConfirmarPedido').on('click', function () {
            enviarPedido();
        });

        // Ir a "Mis pedidos" (puedes cambiar la ruta cuando exista)
        $('#btnIrPedidos').on('click', function () {
            window.location.href = 'mis_pedidos.php';
        });

        // Carga inicial
        cargarCatalogo();
    });
</script>

</body>
</html>
