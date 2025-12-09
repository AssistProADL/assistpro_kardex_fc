<?php
// public/sfa/lista_precios_editar.php

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

/* ===========================================================
   Helpers
   =========================================================== */

function fecha_ui_a_db(?string $f): ?string {
    $f = trim((string)$f);
    if ($f === '') return null;
    $parts = explode('/', $f);
    if (count($parts) === 3) {
        return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
    }
    return $f;
}

function fecha_db_a_ui(?string $f): string {
    $f = trim((string)$f);
    if ($f === '' || $f === '0000-00-00') return '';
    $parts = explode('-', $f);
    if (count($parts) === 3) {
        return sprintf('%02d/%02d/%04d', $parts[2], $parts[1], $parts[0]);
    }
    return $f;
}

/* ===========================================================
   Carga inicial / variables
   =========================================================== */

$pdo   = db();
$id    = isset($_GET['id']) ? trim($_GET['id']) : (isset($_POST['id']) ? trim($_POST['id']) : '');
$isNew = ($id === '' || $id === null);

$errores    = [];
$mensaje_ok = '';

$lista      = '';
$tipo       = 0;
$fecha_ini  = '';
$fecha_fin  = '';
$cve_almac  = '';
$tipo_serv  = 'P';
$id_moneda  = '';
$detalle    = [];

/* ===========================================================
   Catálogos
   =========================================================== */

// Almacenes físicos desde c_almacenp (id, nombre)
$almacenes = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    WHERE IFNULL(Activo,'1') <> '0'
    ORDER BY nombre
");

// Monedas
$monedas = db_all("
    SELECT Id_Moneda, Cve_Moneda, Des_Moneda
    FROM c_monedas
    WHERE IFNULL(Activo,1) = 1
    ORDER BY Des_Moneda
");

// Productos para datalist
$productos = db_all("
    SELECT cve_articulo, des_articulo
    FROM c_articulo
    WHERE IFNULL(Activo,1) = 1
    ORDER BY des_articulo
    LIMIT 5000
");

/* ===========================================================
   POST: guardar
   =========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lista      = trim($_POST['Lista']      ?? '');
    $tipo       = (int)($_POST['Tipo']      ?? 0);
    $fecha_ini  = trim($_POST['FechaIni']   ?? '');
    $fecha_fin  = trim($_POST['FechaFin']   ?? '');
    $cve_almac  = trim($_POST['Cve_Almac']  ?? '');
    $tipo_serv  = trim($_POST['TipoServ']   ?? 'P');
    $id_moneda  = trim($_POST['id_moneda']  ?? '');

    $det_cve  = $_POST['det_cve_articulo'] ?? [];
    $det_pmin = $_POST['det_precio_min']   ?? [];
    $det_pmax = $_POST['det_precio_max']   ?? [];

    if ($lista === '')        $errores[] = 'El nombre de la lista es obligatorio.';
    if ($cve_almac === '')    $errores[] = 'Debe seleccionar un almacén.';
    if ($id_moneda === '')    $errores[] = 'Debe seleccionar una moneda.';

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            if ($isNew) {
                $id = db_val("SELECT IFNULL(MAX(id), -1) + 1 FROM listap");
                if ($id === null) $id = 0;

                $sql_ins = "
                    INSERT INTO listap (id, Lista, Tipo, FechaIni, FechaFin, Cve_Almac, TipoServ, id_moneda)
                    VALUES (:id, :Lista, :Tipo, :FechaIni, :FechaFin, :Cve_Almac, :TipoServ, :id_moneda)
                ";
                $stmt = $pdo->prepare($sql_ins);
                $stmt->execute([
                    ':id'        => (int)$id,
                    ':Lista'     => $lista,
                    ':Tipo'      => $tipo,
                    ':FechaIni'  => fecha_ui_a_db($fecha_ini),
                    ':FechaFin'  => fecha_ui_a_db($fecha_fin),
                    ':Cve_Almac' => $cve_almac !== '' ? (int)$cve_almac : null,
                    ':TipoServ'  => $tipo_serv,
                    ':id_moneda' => $id_moneda !== '' ? (int)$id_moneda : null,
                ]);
                $isNew = false;
            } else {
                $sql_upd = "
                    UPDATE listap
                    SET Lista = :Lista,
                        Tipo = :Tipo,
                        FechaIni = :FechaIni,
                        FechaFin = :FechaFin,
                        Cve_Almac = :Cve_Almac,
                        TipoServ = :TipoServ,
                        id_moneda = :id_moneda
                    WHERE id = :id
                ";
                $stmt = $pdo->prepare($sql_upd);
                $stmt->execute([
                    ':Lista'     => $lista,
                    ':Tipo'      => $tipo,
                    ':FechaIni'  => fecha_ui_a_db($fecha_ini),
                    ':FechaFin'  => fecha_ui_a_db($fecha_fin),
                    ':Cve_Almac' => $cve_almac !== '' ? (int)$cve_almac : null,
                    ':TipoServ'  => $tipo_serv,
                    ':id_moneda' => $id_moneda !== '' ? (int)$id_moneda : null,
                    ':id'        => (int)$id,
                ]);
            }

            // Detalle
            $stmtDel = $pdo->prepare("DELETE FROM detallelp WHERE ListaId = :lista");
            $stmtDel->execute([':lista' => (int)$id]);

            $stmtDet = $pdo->prepare("
                INSERT INTO detallelp (ListaId, Cve_Articulo, PrecioMin, PrecioMax, Cve_Almac, ComisionPor, ComisionMon)
                VALUES (:ListaId, :Cve_Articulo, :PrecioMin, :PrecioMax, :Cve_Almac, 0, 0)
            ");

            $detalle = [];
            $rows = max(count($det_cve), count($det_pmin), count($det_pmax));

            for ($i = 0; $i < $rows; $i++) {
                $cve  = isset($det_cve[$i])  ? trim($det_cve[$i])  : '';
                $pmin = isset($det_pmin[$i]) ? trim($det_pmin[$i]) : '';
                $pmax = isset($det_pmax[$i]) ? trim($det_pmax[$i]) : '';

                if ($cve === '' || $pmin === '') continue;
                if ($pmax === '') $pmax = $pmin;

                $stmtDet->execute([
                    ':ListaId'      => (int)$id,
                    ':Cve_Articulo' => $cve,
                    ':PrecioMin'    => (float)$pmin,
                    ':PrecioMax'    => (float)$pmax,
                    ':Cve_Almac'    => $cve_almac !== '' ? (int)$cve_almac : null,
                ]);

                $detalle[] = [
                    'Cve_Articulo' => $cve,
                    'PrecioMin'    => $pmin,
                    'PrecioMax'    => $pmax,
                ];
            }

            $pdo->commit();
            $mensaje_ok = 'Lista guardada correctamente.';

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errores[] = 'Error al guardar la lista: ' . $e->getMessage();
        }
    }
}

/* ===========================================================
   GET: cargar info existente
   =========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$isNew) {
    $row = db_one("SELECT * FROM listap WHERE id = :id", [':id' => $id]);
    if ($row) {
        $lista      = $row['Lista'] ?? '';
        $tipo       = (int)($row['Tipo'] ?? 0);
        $fecha_ini  = fecha_db_a_ui($row['FechaIni'] ?? '');
        $fecha_fin  = fecha_db_a_ui($row['FechaFin'] ?? '');
        $cve_almac  = (string)($row['Cve_Almac'] ?? '');
        $tipo_serv  = $row['TipoServ'] ?? 'P';
        $id_moneda  = (string)($row['id_moneda'] ?? '');
    }

    $detalle = db_all("
        SELECT Cve_Articulo, PrecioMin, PrecioMax
        FROM detallelp
        WHERE ListaId = :lista
        ORDER BY Cve_Articulo
    ", [':lista' => $id]);
}

if (empty($detalle)) {
    $detalle = [];
    for ($i = 0; $i < 5; $i++) {
        $detalle[] = ['Cve_Articulo' => '', 'PrecioMin' => '', 'PrecioMax' => ''];
    }
}
?>
<div class="container-fluid px-3" style="font-size:10px;">
    <div class="row mb-2">
        <div class="col-6">
            <h5 class="mb-0" style="color:#0F5AAD;">
                <?= $isNew ? 'Nueva lista de precios' : 'Editar lista de precios' ?>
            </h5>
            <small class="text-muted">Origen: listap / detallelp</small>
        </div>
        <div class="col-6 text-end">
            <a href="lista_precios.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Regresar
            </a>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger py-1" style="font-size:10px;">
            <ul class="mb-0">
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($mensaje_ok !== ''): ?>
        <div class="alert alert-success py-1" style="font-size:10px;">
            <?= htmlspecialchars($mensaje_ok) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">

        <!-- Encabezado -->
        <div class="card mb-2" style="font-size:10px;">
            <div class="card-header py-1">
                Datos de la lista
            </div>
            <div class="card-body py-2">
                <div class="row mb-1">
                    <div class="col-md-6">
                        <label class="form-label mb-0">Nombre de la lista *</label>
                        <input type="text" name="Lista" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($lista) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Tipo</label>
                        <select name="Tipo" class="form-select form-select-sm">
                            <option value="0" <?= $tipo == 0 ? 'selected' : '' ?>>Normal</option>
                            <option value="1" <?= $tipo == 1 ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Tipo servicio</label>
                        <select name="TipoServ" class="form-select form-select-sm">
                            <option value="P" <?= $tipo_serv === 'P' ? 'selected' : '' ?>>Productos</option>
                            <option value="S" <?= $tipo_serv === 'S' ? 'selected' : '' ?>>Servicios</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-1">
                    <div class="col-md-3">
                        <label class="form-label mb-0">Fecha inicio</label>
                        <input type="text" name="FechaIni" class="form-control form-control-sm"
                               placeholder="dd/mm/aaaa"
                               value="<?= htmlspecialchars($fecha_ini) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Fecha fin</label>
                        <input type="text" name="FechaFin" class="form-control form-control-sm"
                               placeholder="dd/mm/aaaa"
                               value="<?= htmlspecialchars($fecha_fin) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Moneda *</label>
                        <select name="id_moneda" class="form-select form-select-sm" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($monedas as $m): ?>
                                <option value="<?= (int)$m['Id_Moneda'] ?>"
                                    <?= ($id_moneda !== '' && (int)$id_moneda === (int)$m['Id_Moneda']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['Des_Moneda'] . ' (' . $m['Cve_Moneda'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Almacén *</label>
                        <select name="Cve_Almac" class="form-select form-select-sm" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($almacenes as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"
                                    <?= ($cve_almac !== '' && (int)$cve_almac === (int)$a['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle -->
        <div class="card mb-2" style="font-size:10px;">
            <div class="card-header py-1 d-flex justify-content-between align-items-center">
                <span>Detalle de productos (N renglones)</span>
                <button type="button" class="btn btn-primary btn-xs btn-sm" onclick="agregarFila();">
                    <i class="fa fa-plus"></i> Agregar renglón
                </button>
            </div>
            <div class="card-body p-0">
                <!-- datalist de artículos -->
                <datalist id="articulosList">
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= htmlspecialchars($p['cve_articulo']) ?>">
                            <?= htmlspecialchars($p['cve_articulo'] . ' - ' . $p['des_articulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>

                <div class="table-responsive" style="max-height:420px;overflow:auto;">
                    <table id="tbl-detalle" class="table table-bordered table-sm mb-0 align-middle" style="font-size:10px;">
                        <thead class="table-light">
                        <tr>
                            <th style="width:35%;">Clave artículo (SKU)</th>
                            <th style="width:25%;">Precio mínimo</th>
                            <th style="width:25%;">Precio máximo</th>
                            <th style="width:10%;">&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($detalle as $row): ?>
                            <tr>
                                <td>
                                    <input type="text" name="det_cve_articulo[]" class="form-control form-control-sm"
                                           list="articulosList"
                                           value="<?= htmlspecialchars($row['Cve_Articulo'] ?? '') ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="det_precio_min[]"
                                           class="form-control form-control-sm text-end"
                                           value="<?= htmlspecialchars($row['PrecioMin'] ?? '') ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="det_precio_max[]"
                                           class="form-control form-control-sm text-end"
                                           value="<?= htmlspecialchars($row['PrecioMax'] ?? '') ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-xs btn-sm"
                                            onclick="eliminarFila(this);">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-2 py-1 text-muted" style="font-size:9px;">
                    Tip: escribe la clave y usa la lista desplegable para ver productos del catálogo.
                </div>
            </div>
        </div>

        <div class="text-end mb-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa fa-save"></i> Guardar lista
            </button>
            <a href="lista_precios.php" class="btn btn-outline-secondary btn-sm">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function agregarFila() {
    const tbody = document.querySelector('#tbl-detalle tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" name="det_cve_articulo[]" class="form-control form-control-sm"
                   list="articulosList">
        </td>
        <td>
            <input type="number" step="0.01" name="det_precio_min[]"
                   class="form-control form-control-sm text-end">
        </td>
        <td>
            <input type="number" step="0.01" name="det_precio_max[]"
                   class="form-control form-control-sm text-end">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-xs btn-sm"
                    onclick="eliminarFila(this);">&times;</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function eliminarFila(btn) {
    const tr = btn.closest('tr');
    const tbody = tr.parentNode;
    if (tbody.rows.length > 1) {
        tbody.removeChild(tr);
    } else {
        tr.querySelectorAll('input').forEach(i => i.value = '');
    }
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
