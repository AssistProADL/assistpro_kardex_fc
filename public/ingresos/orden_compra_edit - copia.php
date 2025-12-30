<?php
// public/ingresos/orden_compra_edit.php
// Crear / Editar Orden de Compra (th_aduana / td_aduana)

require_once __DIR__ . '/../../app/db.php';

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die('Error de conexión a BD: ' . e($e->getMessage()));
}

// =========================
// MODO / ID OC
// =========================
$idAduana  = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idAduana = isset($_POST['id_aduana']) ? (int)$_POST['id_aduana'] : 0;
}
$esEdicion = $idAduana > 0;

$encabezado = null;
$detalle    = [];

// =========================
// CARGA DE CATÁLOGOS
// =========================
try {
    $proveedores = $pdo->query("
        SELECT ID_Proveedor, Nombre
        FROM c_proveedores
        WHERE COALESCE(Activo,1) = 1
        ORDER BY Nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $proveedores = [];
}

try {
    $almacenes = $pdo->query("
        SELECT 
            clave,
            nombre,
            cve_cia
        FROM c_almacenp
        ORDER BY clave
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $almacenes = [];
}

try {
    $protocolos = $pdo->query("
        SELECT ID_Protocolo, descripcion
        FROM t_protocolo
        WHERE COALESCE(Activo,1) = 1
        ORDER BY ID_Protocolo
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $protocolos = [];
}

// =========================
// CARGAR ENCABEZADO/DETALLE EN EDICIÓN
// =========================
if ($esEdicion && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $sqlH = "
        SELECT *
        FROM th_aduana
        WHERE ID_Aduana = :id
    ";
    $sth = $pdo->prepare($sqlH);
    $sth->execute([':id' => $idAduana]);
    $encabezado = $sth->fetch(PDO::FETCH_ASSOC);

    if (!$encabezado) {
        die('Orden de compra no encontrada.');
    }

    $sqlD = "
        SELECT 
            d.*,
            a.des_articulo,
            u.des_umed
        FROM td_aduana d
        LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
        LEFT JOIN c_unimed  u ON u.id_umed     = d.Id_UniMed
        WHERE d.ID_Aduana = :id
        ORDER BY d.num_orden, d.Item
    ";
    $std = $pdo->prepare($sqlD);
    $std->execute([':id' => $idAduana]);
    $detalle = $std->fetchAll(PDO::FETCH_ASSOC);
}

// =========================
// GUARDAR (POST)
// =========================
$mensajeError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $usuarioSesion = $_SESSION['username'] ?? 'SYSTEM';

        // Campos de encabezado desde POST (solo para nueva OC)
        $idProveedor = isset($_POST['ID_Proveedor']) ? (int)$_POST['ID_Proveedor'] : 0;
        $cveAlmac    = trim($_POST['Cve_Almac'] ?? '');
        $tipoOc      = trim($_POST['tipo_oc'] ?? 'OCN'); // OCN / OCI
        $idProtocolo = trim($_POST['ID_Protocolo'] ?? '');
        $proyecto    = trim($_POST['Proyecto'] ?? '');
        $moneda      = (int)($_POST['Id_moneda'] ?? 1); // 1=MXN,2=USD
        $tipoCambio  = (float)($_POST['Tipo_Cambio'] ?? 1.0);
        $fechaOc     = trim($_POST['fech_pedimento'] ?? '');
        $folioOc     = trim($_POST['Pedimento'] ?? '');

        if (!$esEdicion) {
            if ($idProveedor <= 0) {
                throw new RuntimeException('Debe seleccionar un proveedor.');
            }

            if ($fechaOc === '') {
                $fechaOc = date('Y-m-d');
            }

            // Aduana en función del tipo OC
            $aduana = ($tipoOc === 'OCI') ? 'IMPORTACION' : 'NACIONAL';

            // Si no se capturó folio, generamos sencillo
            if ($folioOc === '') {
                $folioOc = $tipoOc . '-' . date('Ymd-His');
            }

            $sqlInsH = "
                INSERT INTO th_aduana (
                    num_pedimento,
                    fech_pedimento,
                    aduana,
                    Factura,
                    fech_llegPed,
                    status,
                    ID_Proveedor,
                    ID_Protocolo,
                    cve_usuario,
                    Cve_Almac,
                    Activo,
                    recurso,
                    Proyecto,
                    Tipo_Cambio,
                    Id_moneda,
                    Pedimento
                ) VALUES (
                    NULL,
                    :fech_pedimento,
                    :aduana,
                    :factura,
                    :fech_llegPed,
                    'A',
                    :id_prov,
                    :id_prot,
                    :usuario,
                    :cve_almac,
                    1,
                    :recurso,
                    :proyecto,
                    :tc,
                    :moneda,
                    :pedimento
                )
            ";
            $sth = $pdo->prepare($sqlInsH);
            $sth->execute([
                ':fech_pedimento' => $fechaOc . ' 00:00:00',
                ':aduana'         => $aduana,
                ':factura'        => $folioOc,
                ':fech_llegPed'   => $fechaOc . ' 00:00:00',
                ':id_prov'        => $idProveedor,
                ':id_prot'        => $idProtocolo !== '' ? $idProtocolo : null,
                ':usuario'        => $usuarioSesion,
                ':cve_almac'      => $cveAlmac !== '' ? $cveAlmac : null,
                ':recurso'        => $tipoOc,
                ':proyecto'       => $proyecto !== '' ? $proyecto : null,
                ':tc'             => $tipoCambio ?: 1.0,
                ':moneda'         => $moneda ?: 1,
                ':pedimento'      => $folioOc,
            ]);
            $idAduana  = (int)$pdo->lastInsertId();
            $esEdicion = true;
        } else {
            // En edición NO tocamos encabezado (solo detalle), según instrucción.
            if ($idAduana <= 0) {
                throw new RuntimeException('OC inválida para edición.');
            }
        }

        // =========================
        // GUARDAR DETALLE (REEMPLAZO TOTAL)
        // =========================
        // Borramos todas las partidas actuales
        $pdo->prepare("DELETE FROM td_aduana WHERE ID_Aduana = :id")
            ->execute([':id' => $idAduana]);

        $articulos  = $_POST['cve_articulo'] ?? [];
        $cantidades = $_POST['cantidad']     ?? [];
        $costos     = $_POST['costo']        ?? [];
        $ivas       = $_POST['iva']          ?? [];
        $lotes      = $_POST['Cve_Lote']     ?? [];
        $caducidades= $_POST['caducidad']    ?? [];
        $umedIds    = $_POST['Id_UniMed']    ?? [];

        $sqlInsD = "
            INSERT INTO td_aduana (
                ID_Aduana,
                cve_articulo,
                cantidad,
                Cve_Lote,
                caducidad,
                temperatura,
                num_orden,
                Ingresado,
                Activo,
                costo,
                IVA,
                Item,
                Id_UniMed,
                Fec_Entrega,
                Ref_Docto,
                Peso,
                MarcaNumTotBultos,
                Factura,
                Fec_Factura,
                Contenedores
            ) VALUES (
                :id_aduana,
                :cve_articulo,
                :cantidad,
                :cve_lote,
                :caducidad,
                NULL,
                :num_orden,
                0,
                1,
                :costo,
                :iva,
                :item,
                :id_umed,
                NULL,
                NULL,
                NULL,
                NULL,
                :factura,
                NULL,
                NULL
            )
        ";
        $stmtDet = $pdo->prepare($sqlInsD);

        $linea = 0;
        foreach ($articulos as $idx => $art) {
            $art   = trim((string)$art);
            $cant  = (float)($cantidades[$idx] ?? 0);
            $costo = (float)($costos[$idx] ?? 0);
            $iva   = $ivas[$idx] !== '' ? (float)$ivas[$idx] : 0.0;

            if ($art === '' || $cant <= 0 || $costo <= 0) {
                continue; // línea vacía / inválida
            }

            $linea++;

            $lote   = trim((string)($lotes[$idx] ?? ''));
            $cad    = trim((string)($caducidades[$idx] ?? ''));
            $idUmed = $umedIds[$idx] !== '' ? (int)$umedIds[$idx] : 0;

            // Si no hay UM, buscamos en c_articulo.cve_umed
            if ($idUmed <= 0) {
                $qU = $pdo->prepare("SELECT cve_umed FROM c_articulo WHERE cve_articulo = :art LIMIT 1");
                $qU->execute([':art' => $art]);
                $idUmed = (int)$qU->fetchColumn();
                if ($idUmed <= 0) {
                    $idUmed = null;
                }
            }

            $stmtDet->execute([
                ':id_aduana'   => $idAduana,
                ':cve_articulo'=> $art,
                ':cantidad'    => $cant,
                ':cve_lote'    => $lote !== '' ? $lote : null,
                ':caducidad'   => $cad !== '' ? $cad . ' 00:00:00' : null,
                ':num_orden'   => $linea,
                ':costo'       => $costo,   // PRECIO TOTAL (CON IVA)
                ':iva'         => $iva,     // PORCENTAJE (ej. 16)
                ':item'        => 'ITM-' . str_pad((string)$linea, 3, '0', STR_PAD_LEFT),
                ':id_umed'     => $idUmed,
                ':factura'     => $folioOc !== '' ? $folioOc : null,
            ]);
        }

        if ($linea === 0) {
            throw new RuntimeException('Debe capturar al menos una partida válida.');
        }

        $pdo->commit();

        // Redirige a listado
        header('Location: orden_compra.php');
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $mensajeError = $e->getMessage();

        // Si hubo error, cargamos de nuevo encabezado y detalle para mostrar lo que había
        if ($esEdicion) {
            $sth = $pdo->prepare("SELECT * FROM th_aduana WHERE ID_Aduana = :id");
            $sth->execute([':id' => $idAduana]);
            $encabezado = $sth->fetch(PDO::FETCH_ASSOC);

            $std = $pdo->prepare("
                SELECT d.*, a.des_articulo, u.des_umed
                FROM td_aduana d
                LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
                LEFT JOIN c_unimed  u ON u.id_umed     = d.Id_UniMed
                WHERE d.ID_Aduana = :id
                ORDER BY d.num_orden, d.Item
            ");
            $std->execute([':id' => $idAduana]);
            $detalle = $std->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// =========================
// VALORES POR OMISIÓN ENCABEZADO (para nueva OC)
// =========================
if (!$esEdicion) {
    $encabezado = [
        'ID_Aduana'      => 0,
        'Pedimento'      => '',
        'Factura'        => '',
        'fech_pedimento' => date('Y-m-d') . ' 00:00:00',
        'ID_Proveedor'   => 0,
        'Cve_Almac'      => '',
        'ID_Protocolo'   => '',
        'recurso'        => 'OCN',
        'Proyecto'       => '',
        'Tipo_Cambio'    => 1.0,
        'Id_moneda'      => 1,
        'status'         => 'A',
    ];
}

// =========================
// LAYOUT
// =========================
$TITLE = $esEdicion ? 'Editar Orden de Compra' : 'Nueva Orden de Compra';
require_once __DIR__ . '/../bi/_menu_global.php';

$fechaOcIso = $encabezado['fech_pedimento']
    ? substr($encabezado['fech_pedimento'], 0, 10)
    : date('Y-m-d');

?>
<style>
    body {
        background: #f5f7fb;
        font-size: 11px;
    }
    .ap-card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(15,90,173,.2);
        border: 1px solid #dbe3ef;
        margin-bottom: 10px;
    }
    .ap-card-header {
        background: #0F5AAD;
        color: #fff;
        padding: 8px 14px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ap-title {
        font-size: 14px;
        margin: 0;
        font-weight: 600;
    }
    .ap-subtitle {
        font-size: 11px;
        margin: 0;
        opacity: .85;
    }
    .ap-card-body {
        padding: 10px 14px;
    }
    .ap-label {
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 2px;
        color: #555;
    }
    .form-control, .form-select {
        font-size: 11px;
        height: 28px;
        padding: 3px 6px;
    }
    .btn-ap-primary {
        background:#0F5AAD;
        border-color:#0F5AAD;
        color:#fff;
        border-radius:999px;
        font-size:11px;
        padding:4px 14px;
    }
    .btn-ap-primary:hover {
        background:#0c4a8d;
        border-color:#0c4a8d;
    }
    .table-partidas thead th {
        background:#f1f5f9;
        font-size:10px;
    }
    .table-partidas tbody td {
        font-size:10px;
        padding:2px 4px;
        vertical-align:middle;
    }
    .btn-row {
        font-size:10px;
        padding:2px 6px;
    }
</style>

<div class="container-fluid py-2">
    <div class="ap-card mb-2">
        <div class="ap-card-header">
            <div>
                <div class="ap-title">
                    <?php echo $esEdicion ? 'Editar Orden de Compra' : 'Nueva Orden de Compra'; ?>
                </div>
                <div class="ap-subtitle">
                    th_aduana / td_aduana — OCN / OCI con partidas detalladas.
                </div>
            </div>
            <div>
                <a href="orden_compra.php" class="btn btn-outline-light btn-sm">
                    Regresar
                </a>
            </div>
        </div>
    </div>

    <div class="ap-card">
        <div class="ap-card-body">
            <?php if ($mensajeError !== ''): ?>
                <div class="alert alert-danger py-1" style="font-size:11px;">
                    Error al guardar la OC: <?php echo e($mensajeError); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="formOc">
                <input type="hidden" name="id_aduana" value="<?php echo (int)$encabezado['ID_Aduana']; ?>">

                <!-- ENCABEZADO -->
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="ap-label">Proveedor</label>
                        <select name="ID_Proveedor" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
                            <option value="0">Seleccione...</option>
                            <?php
                            $idProvSel = (int)$encabezado['ID_Proveedor'];
                            foreach ($proveedores as $p):
                            ?>
                                <option value="<?php echo (int)$p['ID_Proveedor']; ?>"
                                    <?php echo ($idProvSel === (int)$p['ID_Proveedor']) ? 'selected' : ''; ?>>
                                    <?php echo e($p['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="ap-label">Almacén</label>
                        <select name="Cve_Almac" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
                            <option value="">Seleccione...</option>
                            <?php
                            $almSel = (string)$encabezado['Cve_Almac'];
                            foreach ($almacenes as $a):
                                $cve = (string)$a['clave'];
                                $nom = (string)$a['nombre'];
                            ?>
                                <option value="<?php echo e($cve); ?>"
                                    <?php echo ($almSel === $cve) ? 'selected' : ''; ?>>
                                    <?php echo e($cve . ' - ' . $nom); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="ap-label">Tipo OC</label>
                        <?php $tipoSel = (string)($encabezado['recurso'] ?? 'OCN'); ?>
                        <select name="tipo_oc" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
                            <option value="OCN" <?php echo ($tipoSel === 'OCN') ? 'selected' : ''; ?>>OCN</option>
                            <option value="OCI" <?php echo ($tipoSel === 'OCI') ? 'selected' : ''; ?>>OCI</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="ap-label">Protocolo</label>
                        <?php $protSel = (string)$encabezado['ID_Protocolo']; ?>
                        <select name="ID_Protocolo" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
                            <option value="">(Ninguno)</option>
                            <?php foreach ($protocolos as $pr): ?>
                                <option value="<?php echo e($pr['ID_Protocolo']); ?>"
                                    <?php echo ($protSel === (string)$pr['ID_Protocolo']) ? 'selected' : ''; ?>>
                                    <?php echo e($pr['descripcion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="ap-label">Folio OC</label>
                        <input type="text" name="Pedimento"
                               class="form-control"
                               value="<?php echo e((string)$encabezado['Pedimento']); ?>"
                               <?php echo $esEdicion ? 'readonly' : ''; ?>>
                    </div>

                    <div class="col-md-1">
                        <label class="ap-label">Moneda</label>
                        <?php $monSel = (int)$encabezado['Id_moneda']; ?>
                        <select name="Id_moneda" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
                            <option value="1" <?php echo ($monSel === 1 ? 'selected' : ''); ?>>MXN</option>
                            <option value="2" <?php echo ($monSel === 2 ? 'selected' : ''); ?>>USD</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="ap-label">Tipo cambio</label>
                        <input type="number" step="0.0001" name="Tipo_Cambio"
                               class="form-control"
                               value="<?php echo e((string)$encabezado['Tipo_Cambio']); ?>"
                               <?php echo $esEdicion ? 'readonly' : ''; ?>>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-2">
                        <label class="ap-label">Fecha OC</label>
                        <input type="date" name="fech_pedimento"
                               class="form-control"
                               value="<?php echo e($fechaOcIso); ?>"
                               <?php echo $esEdicion ? 'readonly' : ''; ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="ap-label">Proyecto</label>
                        <input type="text" name="Proyecto"
                               class="form-control"
                               value="<?php echo e((string)$encabezado['Proyecto']); ?>"
                               <?php echo $esEdicion ? 'readonly' : ''; ?>>
                    </div>
                </div>

                <hr class="my-2">

                <!-- DETALLE -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="ap-label mb-0">Partidas</div>
                    <button type="button" class="btn btn-sm btn-ap-primary" id="btnAddRow">
                        Agregar partida
                    </button>
                </div>

                <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                    <table class="table table-bordered table-hover table-partidas" id="tablaPartidas">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th style="width:120px;">Artículo</th>
                                <th>Descripción</th>
                                <th style="width:70px;">UOM</th>
                                <th style="width:80px;" class="text-end">Cantidad</th>
                                <th style="width:80px;" class="text-end">Costo (c/IVA)</th>
                                <th style="width:60px;" class="text-end">IVA %</th>
                                <th style="width:100px;">Lote</th>
                                <th style="width:90px;">Caducidad</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (!$detalle) {
                            // Fila vacía
                            ?>
                            <tr>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-xs btn-row btn-del">
                                        &times;
                                    </button>
                                </td>
                                <td>
                                    <input type="text" name="cve_articulo[]" class="form-control">
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="" disabled>
                                </td>
                                <td>
                                    <input type="hidden" name="Id_UniMed[]" value="">
                                    <input type="text" class="form-control" value="" disabled>
                                </td>
                                <td>
                                    <input type="number" step="0.0001" name="cantidad[]" class="form-control text-end" value="">
                                </td>
                                <td>
                                    <input type="number" step="0.0001" name="costo[]" class="form-control text-end" value="">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="iva[]" class="form-control text-end" value="16">
                                </td>
                                <td>
                                    <input type="text" name="Cve_Lote[]" class="form-control" value="">
                                </td>
                                <td>
                                    <input type="date" name="caducidad[]" class="form-control" value="">
                                </td>
                            </tr>
                            <?php
                        } else {
                            foreach ($detalle as $row):
                                $cadIso = $row['caducidad'] ? substr($row['caducidad'],0,10) : '';
                            ?>
                            <tr>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-xs btn-row btn-del">
                                        &times;
                                    </button>
                                </td>
                                <td>
                                    <input type="text" name="cve_articulo[]" class="form-control"
                                           value="<?php echo e((string)$row['cve_articulo']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                           value="<?php echo e((string)($row['des_articulo'] ?? '')); ?>" disabled>
                                </td>
                                <td>
                                    <input type="hidden" name="Id_UniMed[]" value="<?php echo e((string)$row['Id_UniMed']); ?>">
                                    <input type="text" class="form-control"
                                           value="<?php echo e((string)($row['des_umed'] ?? '')); ?>" disabled>
                                </td>
                                <td>
                                    <input type="number" step="0.0001" name="cantidad[]" class="form-control text-end"
                                           value="<?php echo e((string)$row['cantidad']); ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.0001" name="costo[]" class="form-control text-end"
                                           value="<?php echo e((string)$row['costo']); ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="iva[]" class="form-control text-end"
                                           value="<?php echo e((string)($row['IVA'] ?? '16')); ?>">
                                </td>
                                <td>
                                    <input type="text" name="Cve_Lote[]" class="form-control"
                                           value="<?php echo e((string)($row['Cve_Lote'] ?? '')); ?>">
                                </td>
                                <td>
                                    <input type="date" name="caducidad[]" class="form-control"
                                           value="<?php echo e($cadIso); ?>">
                                </td>
                            </tr>
                            <?php
                            endforeach;
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <div class="text-muted" style="font-size:10px;">
                        El costo capturado es <strong>precio total con IVA</strong>.<br>
                        El desglose (precio neto, IVA, total) se calcula en reportes dividiendo entre (1 + IVA/100).
                    </div>
                    <div>
                        <button type="submit" class="btn btn-ap-primary">
                            Guardar OC
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaPartidas').querySelector('tbody');
    const btnAdd = document.getElementById('btnAddRow');

    function bindDeleteButtons() {
        tabla.querySelectorAll('.btn-del').forEach(btn => {
            btn.onclick = () => {
                const row = btn.closest('tr');
                if (!row) return;
                if (tabla.rows.length === 1) {
                    // si solo queda una fila, la limpiamos
                    row.querySelectorAll('input').forEach(inp => inp.value = '');
                } else {
                    row.remove();
                }
            };
        });
    }

    btnAdd.addEventListener('click', () => {
        const rows = tabla.querySelectorAll('tr');
        const base = rows[rows.length - 1];
        const clone = base.cloneNode(true);

        // Limpia valores
        clone.querySelectorAll('input').forEach(inp => {
            if (inp.type === 'hidden') {
                inp.value = '';
                return;
            }
            if (inp.type === 'number' || inp.type === 'text' || inp.type === 'date') {
                if (inp.name === 'iva[]') {
                    inp.value = '16';
                } else {
                    inp.value = '';
                }
            }
        });

        tabla.appendChild(clone);
        bindDeleteButtons();
    });

    bindDeleteButtons();
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
