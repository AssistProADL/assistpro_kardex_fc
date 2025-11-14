<?php
// public/sfa/lista_precios_editar.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$copiar    = isset($_GET['copiar']) && (int)$_GET['copiar'] === 1;
$errores   = [];
$mensajes  = [];

// ======================================================
// Catálogos
// ======================================================
$almacenes = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    WHERE Activo IS NULL OR Activo = 1
    ORDER BY clave, nombre
");

$monedas = db_all("
    SELECT Id_Moneda, Cve_Moneda, Des_Moneda
    FROM c_monedas
    WHERE Activo = 1
    ORDER BY Cve_Moneda
");

// Catálogo de artículos (puedes luego filtrar por familia/grupo si hace falta)
$articulos = db_all("
    SELECT cve_articulo, des_articulo, imp_costo
    FROM c_articulo
    ORDER BY des_articulo
");

// ======================================================
// Cargar cabecera (si id > 0)
// ======================================================
$cabecera = [
    'id'        => 0,
    'Lista'     => '',
    'Tipo'      => 0,
    'FechaIni'  => null,
    'FechaFin'  => null,
    'Cve_Almac' => null,
    'TipoServ'  => 'P',      // Productos
    'id_moneda' => null
];

if ($id > 0) {
    $cabecera = db_one("
        SELECT *
        FROM listap
        WHERE id = ?
    ", [$id]) ?? $cabecera;
}

// Modo copiar: misma info pero id = 0
if ($copiar && $cabecera['id']) {
    $cabecera['id']    = 0;
    $mensajes[]        = 'Copiando lista. Al guardar se creará un nuevo ID.';
}

// ======================================================
// POST: guardar cabecera o agregar detalle
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Normaliza cabecera desde POST
    if (in_array($accion, ['guardar_cabecera', 'agregar_detalle', 'eliminar_detalle'], true)) {
        $cabecera['id']        = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $cabecera['Lista']     = trim($_POST['Lista'] ?? '');
        $cabecera['Tipo']      = isset($_POST['Tipo']) && $_POST['Tipo'] !== '' ? (int)$_POST['Tipo'] : 0;
        $cabecera['FechaIni']  = $_POST['FechaIni'] !== '' ? $_POST['FechaIni'] : null;
        $cabecera['FechaFin']  = $_POST['FechaFin'] !== '' ? $_POST['FechaFin'] : null;
        $cabecera['id_moneda'] = $_POST['id_moneda'] !== '' ? (int)$_POST['id_moneda'] : null;
        $cabecera['TipoServ']  = isset($_POST['MostrarServicios']) && $_POST['MostrarServicios'] === '1' ? 'S' : 'P';

        $almacen_padre = $_POST['almacen_padre'] !== '' ? (int)$_POST['almacen_padre'] : null;

        // Resolver Cve_Almac usando c_almacenp -> c_almacen
        $cabecera['Cve_Almac'] = null;
        if ($almacen_padre !== null) {
            $cabecera['Cve_Almac'] = db_val(
                'SELECT cve_almac FROM c_almacen WHERE cve_almacenp = ? ORDER BY cve_almac LIMIT 1',
                [$almacen_padre],
                null
            );
            if ($cabecera['Cve_Almac'] === null) {
                $cabecera['Cve_Almac'] = $almacen_padre;
            }
        }

        if ($cabecera['Lista'] === '') {
            $errores[] = 'El nombre de la lista es obligatorio.';
        }

        if ($cabecera['FechaIni'] && $cabecera['FechaFin'] && $cabecera['FechaIni'] > $cabecera['FechaFin']) {
            $errores[] = 'La fecha de inicio no puede ser mayor que la fecha de fin.';
        }
    }

    // Guardar cabecera
    if ($accion === 'guardar_cabecera' && !$errores) {
        if ($cabecera['id'] > 0) {
            dbq("
                UPDATE listap
                SET Lista = ?, Tipo = ?, FechaIni = ?, FechaFin = ?, Cve_Almac = ?, TipoServ = ?, id_moneda = ?
                WHERE id = ?
            ", [
                $cabecera['Lista'],
                $cabecera['Tipo'],
                $cabecera['FechaIni'],
                $cabecera['FechaFin'],
                $cabecera['Cve_Almac'],
                $cabecera['TipoServ'],
                $cabecera['id_moneda'],
                $cabecera['id'],
            ]);
            $mensajes[] = 'Lista actualizada correctamente.';
        } else {
            dbq("
                INSERT INTO listap (Lista, Tipo, FechaIni, FechaFin, Cve_Almac, TipoServ, id_moneda)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [
                $cabecera['Lista'],
                $cabecera['Tipo'],
                $cabecera['FechaIni'],
                $cabecera['FechaFin'],
                $cabecera['Cve_Almac'],
                $cabecera['TipoServ'],
                $cabecera['id_moneda'],
            ]);
            $cabecera['id'] = (int)db_val('SELECT LAST_INSERT_ID()', [], 0);
            $mensajes[] = 'Lista creada correctamente.';
        }
        $id = $cabecera['id'];
    }

    // Agregar detalle
    if ($accion === 'agregar_detalle' && !$errores) {
        if ($cabecera['id'] <= 0) {
            $errores[] = 'Primero guarda la cabecera de la lista antes de agregar productos.';
        } else {
            $cve_articulo = trim($_POST['Cve_Articulo'] ?? '');
            $precio_min   = $_POST['PrecioMin'] !== '' ? (float)$_POST['PrecioMin'] : null;
            $precio_max   = $_POST['PrecioMax'] !== '' ? (float)$_POST['PrecioMax'] : null;
            $com_por      = $_POST['ComisionPor'] !== '' ? (float)$_POST['ComisionPor'] : null;
            $com_mon      = $_POST['ComisionMon'] !== '' ? (float)$_POST['ComisionMon'] : null;

            if ($cve_articulo === '') {
                $errores[] = 'Debes seleccionar un artículo.';
            } else {
                dbq("
                    INSERT INTO detallelp (ListaId, Cve_Articulo, PrecioMin, PrecioMax, Cve_Almac, ComisionPor, ComisionMon)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $cabecera['id'],
                    $cve_articulo,
                    $precio_min,
                    $precio_max,
                    $cabecera['Cve_Almac'],
                    $com_por,
                    $com_mon
                ]);
                $mensajes[] = 'Producto agregado a la lista.';
            }
        }
    }

    // Eliminar detalle
    if ($accion === 'eliminar_detalle' && !$errores) {
        $det_id = isset($_POST['detalle_id']) ? (int)$_POST['detalle_id'] : 0;
        if ($det_id > 0) {
            dbq("DELETE FROM detallelp WHERE id = ?", [$det_id]);
            $mensajes[] = 'Producto eliminado de la lista.';
        }
    }
}

// ======================================================
// Detalle actual (si la lista ya existe)
// ======================================================
$detalles = [];
if ($cabecera['id'] > 0) {
    $detalles = db_all("
        SELECT d.*, a.des_articulo, a.imp_costo
        FROM detallelp d
        LEFT JOIN c_articulo a ON a.cve_articulo = d.Cve_Articulo
        WHERE d.ListaId = ?
        ORDER BY a.des_articulo
    ", [$cabecera['id']]);
}

// Identificar almacén padre seleccionado
$almacen_padre_sel = null;
if ($cabecera['Cve_Almac'] !== null) {
    $almacen_padre_sel = db_val(
        'SELECT cve_almacenp FROM c_almacen WHERE cve_almac = ?',
        [$cabecera['Cve_Almac']],
        null
    );
}
?>
<div class="container-fluid mt-2">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h5 style="color:#0F5AAD;font-weight:bold;">
                    <?= $cabecera['id'] ? 'Editar Lista de Precios' : 'Nueva Lista de Precios' ?>
                </h5>
                <div style="font-size:11px;color:#666;">
                    AssistPro SFA — configuración de lista y productos.
                </div>
            </div>
            <div>
                <a href="lista_precios.php" class="btn btn-sm btn-outline-secondary">
                    &laquo; Volver al listado
                </a>
            </div>
        </div>
    </div>

    <?php if ($errores): ?>
        <div class="alert alert-danger py-1" style="font-size:11px;">
            <ul class="mb-0">
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($mensajes): ?>
        <div class="alert alert-success py-1" style="font-size:11px;">
            <ul class="mb-0">
                <?php foreach ($mensajes as $m): ?>
                    <li><?= htmlspecialchars($m) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- CABECERA -->
    <form method="post" action="lista_precios_editar.php<?= $cabecera['id'] ? '?id=' . (int)$cabecera['id'] : '' ?>">
        <input type="hidden" name="accion" value="guardar_cabecera">
        <input type="hidden" name="id" value="<?= (int)$cabecera['id'] ?>">

        <div class="card mb-2">
            <div class="card-header py-1" style="background:#0F5AAD;color:#fff;">
                <span style="font-size:12px;font-weight:bold;">Datos de la Lista de Precios</span>
            </div>
            <div class="card-body py-2" style="font-size:11px;">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-0">Nombre de la Lista *</label>
                        <input type="text" name="Lista"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($cabecera['Lista'] ?? '') ?>" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label mb-0">Tipo de Lista</label>
                        <div class="border rounded p-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="Tipo" id="tipo_normal"
                                       value="0" <?= ((int)$cabecera['Tipo'] === 0 ? 'checked' : '') ?>>
                                <label class="form-check-label" for="tipo_normal" style="font-size:11px;">
                                    Lista de Precios Normal
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="Tipo" id="tipo_rango"
                                       value="1" <?= ((int)$cabecera['Tipo'] === 1 ? 'checked' : '') ?>>
                                <label class="form-check-label" for="tipo_rango" style="font-size:11px;">
                                    Lista de Precios por Rango de Precios
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label mb-0">Moneda</label>
                        <select name="id_moneda" class="form-select form-select-sm">
                            <option value="">Seleccione</option>
                            <?php foreach ($monedas as $m): ?>
                                <option value="<?= (int)$m['Id_Moneda'] ?>"
                                    <?= ($cabecera['id_moneda'] == $m['Id_Moneda'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars(($m['Cve_Moneda'] ?? '') . ' - ' . ($m['Des_Moneda'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label mb-0">Almacén</label>
                        <select name="almacen_padre" class="form-select form-select-sm">
                            <option value="">Seleccione</option>
                            <?php foreach ($almacenes as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"
                                    <?= ($almacen_padre_sel == $a['id'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars(($a['clave'] ?? '') . ' - ' . ($a['nombre'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-0">Fecha de Inicio</label>
                        <input type="date" name="FechaIni"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($cabecera['FechaIni'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-0">Fecha de Fin</label>
                        <input type="date" name="FechaFin"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($cabecera['FechaFin'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-center">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="chkServicios"
                                   name="MostrarServicios"
                                <?= ($cabecera['TipoServ'] === 'S' ? 'checked' : '') ?>>
                            <label class="form-check-label" for="chkServicios" style="font-size:11px;">
                                Mostrar Servicios
                            </label>
                        </div>
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end justify-content-end">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Guardar Lista
                            </button>
                            <a href="lista_precios.php" class="btn btn-sm btn-outline-secondary">
                                Cerrar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- DETALLE DE PRODUCTOS -->
    <div class="row">
        <div class="col-12 col-lg-5">
            <form method="post" action="lista_precios_editar.php<?= $cabecera['id'] ? '?id=' . (int)$cabecera['id'] : '' ?>">
                <input type="hidden" name="accion" value="agregar_detalle">
                <input type="hidden" name="id" value="<?= (int)$cabecera['id'] ?>">

                <div class="card mb-2">
                    <div class="card-header py-1" style="background:#0F5AAD;color:#fff;">
                        <span style="font-size:12px;font-weight:bold;">Agregar Producto a la Lista</span>
                    </div>
                    <div class="card-body py-2" style="font-size:11px;">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label mb-0">Artículo *</label>
                                <select name="Cve_Articulo" class="form-select form-select-sm" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($articulos as $a): ?>
                                        <option value="<?= htmlspecialchars($a['cve_articulo']) ?>">
                                            <?= htmlspecialchars(($a['cve_articulo'] ?? '') . ' - ' . ($a['des_articulo'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label mb-0">Precio mínimo $</label>
                                <input type="number" step="0.001" name="PrecioMin" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-0">Precio máximo $</label>
                                <input type="number" step="0.001" name="PrecioMax" class="form-control form-control-sm">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label mb-0">Comisión %</label>
                                <input type="number" step="0.001" name="ComisionPor" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-0">Comisión $</label>
                                <input type="number" step="0.001" name="ComisionMon" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="mt-2 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Agregar Producto
                            </button>
                        </div>

                        <div class="mt-2" style="font-size:10px;color:#888;">
                            El costo del artículo se toma del catálogo (c_articulo.imp_costo) y se muestra en el detalle.
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card mb-2">
                <div class="card-header py-1" style="background:#f5f5f5;">
                    <span style="font-size:12px;font-weight:bold;">Detalle de Productos de la Lista</span>
                </div>
                <div class="card-body p-2" style="font-size:11px;">
                    <div class="table-responsive" style="max-height:420px;overflow-y:auto;overflow-x:auto;">
                        <table class="table table-sm table-striped table-hover mb-0" style="font-size:10px;">
                            <thead class="table-light">
                            <tr>
                                <th style="width:70px;">Acciones</th>
                                <th>Clave</th>
                                <th>Nombre</th>
                                <th>Costo $</th>
                                <th>Precio mín. $</th>
                                <th>Precio máx. $</th>
                                <th>Comisión %</th>
                                <th>Comisión $</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$detalles): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No hay productos en la lista.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($detalles as $d): ?>
                                    <tr>
                                        <td>
                                            <form method="post"
                                                  action="lista_precios_editar.php?id=<?= (int)$cabecera['id'] ?>"
                                                  style="display:inline;">
                                                <input type="hidden" name="accion" value="eliminar_detalle">
                                                <input type="hidden" name="id" value="<?= (int)$cabecera['id'] ?>">
                                                <input type="hidden" name="detalle_id" value="<?= (int)$d['id'] ?>">
                                                <button type="submit" class="btn btn-xs btn-link text-danger p-0"
                                                        onclick="return confirm('¿Eliminar este producto de la lista?');">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td><?= htmlspecialchars($d['Cve_Articulo'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($d['des_articulo'] ?? '') ?></td>
                                        <td class="text-end">
                                            <?= number_format((float)($d['imp_costo'] ?? 0), 2) ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format((float)($d['PrecioMin'] ?? 0), 3) ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format((float)($d['PrecioMax'] ?? 0), 3) ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format((float)($d['ComisionPor'] ?? 0), 3) ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format((float)($d['ComisionMon'] ?? 0), 3) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-1" style="font-size:10px;color:#888;">
                        La grilla está limitada visualmente; puedes exportar o extender en futuras versiones.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (file_exists(__DIR__ . '/../bi/_menu_global_end.php')) {
    require_once __DIR__ . '/../bi/_menu_global_end.php';
}
?>
