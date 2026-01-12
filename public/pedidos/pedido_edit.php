<?php
// public/pedidos/pedido_edit.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ap_redirect($url){
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    }
    echo "<script>window.location.href=" . json_encode($url) . ";</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=" . h($url) . "'></noscript>";
    echo "<div style='padding:12px;font-family:Arial;font-size:12px;'>
            Guardado OK. Si no redirige, da click:
            <a href='".h($url)."'>Continuar</a>
          </div>";
    exit;
}

$id_pedido = (int)($_GET['id_pedido'] ?? $_POST['id_pedido'] ?? 0);
$tipo = strtoupper(trim((string)($_GET['tipo'] ?? $_POST['TipoPedido'] ?? 'CLIENTE')));
if ($tipo !== 'RUTA') $tipo = 'CLIENTE';

$ok  = (int)($_GET['ok'] ?? 0);
$err = '';

// Empresa (mínimo viable): si te llega por GET/POST lo usamos; si no, 1.
// Esto permite que el API de relvendrutas funcione desde ya.
$id_empresa = (int)($_GET['id_empresa'] ?? $_POST['id_empresa'] ?? 1);

// ==========================
// Catálogos
// ==========================
$almacenes = [];
$rutas = [];
$vendedores = [];
$clientes = [];
$articulos = [];
$mapArt = [];

try {
    $almacenes = $pdo->query("
        SELECT id, clave, nombre
        FROM c_almacenp
        WHERE COALESCE(Activo,1)=1
        ORDER BY nombre
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch(Throwable $e){ $almacenes=[]; }

try {
    // Ruta: intentamos t_ruta con ID_Ruta + Cve_Ruta; si no existe, no truena.
    $rutas = $pdo->query("
        SELECT ID_Ruta, Cve_Ruta, Descripcion
        FROM t_ruta
        WHERE COALESCE(Activo,1)=1
        ORDER BY Cve_Ruta
        LIMIT 5000
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch(Throwable $e){
    $rutas = [];
}

try {
    // Vendedores: si tienes tabla t_vendedor/c_vendedor ajústala; aquí es robusto.
    // Si falla, dejamos el campo libre (input) y solo asignamos el ID.
    $vendedores = $pdo->query("
        SELECT IdVendedor, Cve_Vendedor, Nombre
        FROM t_vendedor
        WHERE COALESCE(Activo,1)=1
        ORDER BY Nombre
        LIMIT 5000
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch(Throwable $e){
    $vendedores = [];
}

try {
    $clientes = $pdo->query("
        SELECT id_cliente, Cve_Clte, RazonSocial, RFC, cve_ruta, IdEmpresa
        FROM c_cliente
        WHERE COALESCE(Activo,1)=1
        ORDER BY RazonSocial
        LIMIT 15000
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch(Throwable $e){ $clientes=[]; }

try {
    $articulos = $pdo->query("
        SELECT cve_articulo, des_articulo, PrecioVenta, unidadMedida
        FROM c_articulo
        WHERE COALESCE(Activo,1)=1
        ORDER BY cve_articulo
        LIMIT 20000
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($articulos as $a) {
        $cve = trim((string)$a['cve_articulo']);
        if ($cve==='') continue;
        $mapArt[$cve] = [
            'des' => (string)($a['des_articulo'] ?? ''),
            'pv'  => (float)($a['PrecioVenta'] ?? 0),
            'um'  => (string)($a['unidadMedida'] ?? ''),
        ];
    }
} catch(Throwable $e){
    $articulos=[]; $mapArt=[];
}

// ==========================
// Cargar pedido (edición)
// ==========================
$H = [
    'id_pedido'     => 0,
    'Fol_folio'     => '',
    'Fec_Pedido'    => date('Y-m-d'),
    'Fec_Entrega'   => date('Y-m-d'),
    'Cve_clte'      => '',
    'TipoPedido'    => $tipo,
    'ruta'          => '', // guardaremos Cve_Ruta (string) para filtrar clientes
    'cve_Vendedor'  => '', // guardaremos IdVendedor (num como string) por practicidad
    'DiaO'          => '',
    'cve_almac'     => '',
    'status'        => 'A',
    'Cve_Usuario'   => 'WEB',
    'Observaciones' => '',
    'tipo_venta'    => 'PREVENTA',
    'Tot_Factura'   => 0,
];

$D = [];

if ($id_pedido > 0) {
    $st = $pdo->prepare("SELECT * FROM th_pedido WHERE id_pedido=? LIMIT 1");
    $st->execute([$id_pedido]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach ($H as $k=>$v) {
            if (array_key_exists($k, $row)) $H[$k] = $row[$k];
        }

        $H['TipoPedido'] = strtoupper(trim((string)($H['TipoPedido'] ?: $tipo)));
        if ($H['TipoPedido'] !== 'RUTA') $H['TipoPedido'] = 'CLIENTE';

        $st2 = $pdo->prepare("
            SELECT d.*, a.des_articulo
            FROM td_pedido d
            LEFT JOIN c_articulo a
              ON a.cve_articulo COLLATE utf8mb4_unicode_ci
               = d.Cve_articulo COLLATE utf8mb4_unicode_ci
            WHERE d.Fol_folio COLLATE utf8mb4_unicode_ci
                = ? COLLATE utf8mb4_unicode_ci
            ORDER BY d.id ASC
        ");
        $st2->execute([$H['Fol_folio']]);
        $D = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

// ==========================
// Guardar (POST)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_empresa = (int)($_POST['id_empresa'] ?? $id_empresa);

        $TipoPedido  = strtoupper(trim((string)($_POST['TipoPedido'] ?? 'CLIENTE')));
        if ($TipoPedido !== 'RUTA') $TipoPedido = 'CLIENTE';

        $Fol_folio   = trim((string)($_POST['Fol_folio'] ?? ''));
        $Fec_Pedido  = trim((string)($_POST['Fec_Pedido'] ?? date('Y-m-d')));
        $Fec_Entrega = trim((string)($_POST['Fec_Entrega'] ?? date('Y-m-d')));
        $Cve_clte    = trim((string)($_POST['Cve_clte'] ?? ''));
        $ruta        = trim((string)($_POST['ruta'] ?? ''));         // Cve_Ruta
        $vendedor    = trim((string)($_POST['cve_Vendedor'] ?? '')); // IdVendedor
        $DiaO        = trim((string)($_POST['DiaO'] ?? ''));
        $cve_almac   = trim((string)($_POST['cve_almac'] ?? ''));
        $status      = trim((string)($_POST['status'] ?? 'A'));
        $Cve_Usuario = trim((string)($_POST['Cve_Usuario'] ?? 'WEB'));
        $Observ      = (string)($_POST['Observaciones'] ?? '');
        $tipo_venta  = trim((string)($_POST['tipo_venta'] ?? 'PREVENTA'));

        if ($cve_almac === '') throw new Exception("Almacén requerido.");

        if ($TipoPedido === 'CLIENTE') {
            if ($Cve_clte === '') throw new Exception("Cliente requerido para Pedido Cliente.");
        } else {
            if ($ruta === '') throw new Exception("Ruta requerida para Pedido Ruta.");
            // vendedor puede venir vacío si no hay asignación en relvendrutas, pero lo ideal es que lo traiga.
        }

        $a_art = $_POST['Cve_articulo'] ?? [];
        $a_qty = $_POST['Num_cantidad'] ?? [];
        $a_pu  = $_POST['Precio_unitario'] ?? [];
        $a_desc= $_POST['Desc_Importe'] ?? [];
        $a_iva = $_POST['IVA'] ?? [];
        $a_proy= $_POST['Proyecto'] ?? [];

        if (!is_array($a_art) || count($a_art) === 0) throw new Exception("Detalle vacío.");

        if ($Fol_folio === '') {
            $Fol_folio = 'PED-' . date('Ymd-His');
        }

        $sub = 0.0; $ivaT = 0.0;
        $lineas_validas = 0;

        for ($i=0; $i<count($a_art); $i++) {
            $cve = trim((string)($a_art[$i] ?? ''));
            $qty = (float)($a_qty[$i] ?? 0);
            if ($cve === '' || $qty <= 0) continue;

            $pu  = (float)($a_pu[$i] ?? 0);
            $des = (float)($a_desc[$i] ?? 0);
            $iv  = (float)($a_iva[$i] ?? 0);

            $importe = ($pu * $qty) - $des;
            if ($importe < 0) $importe = 0;

            $sub += $importe;
            $ivaT += $iv;
            $lineas_validas++;
        }

        if ($lineas_validas === 0) throw new Exception("No hay partidas válidas (artículo y cantidad).");

        $Tot_Factura = $sub + $ivaT;

        $pdo->beginTransaction();

        $id_pedido_post = (int)($_POST['id_pedido'] ?? 0);
        $exist = null;

        if ($id_pedido_post > 0) {
            $st = $pdo->prepare("SELECT id_pedido, Fol_folio FROM th_pedido WHERE id_pedido=? LIMIT 1");
            $st->execute([$id_pedido_post]);
            $exist = $st->fetch(PDO::FETCH_ASSOC);
        } else {
            $st = $pdo->prepare("SELECT id_pedido, Fol_folio FROM th_pedido WHERE Fol_folio=? LIMIT 1");
            $st->execute([$Fol_folio]);
            $exist = $st->fetch(PDO::FETCH_ASSOC);
        }

        if ($exist) {
            $id_pedido_db = (int)$exist['id_pedido'];
            $folio_db = (string)$exist['Fol_folio'];

            $upd = $pdo->prepare("
                UPDATE th_pedido
                SET
                    Fol_folio=?,
                    Fec_Pedido=?,
                    Fec_Entrega=?,
                    Cve_clte=?,
                    TipoPedido=?,
                    ruta=?,
                    cve_Vendedor=?,
                    DiaO=?,
                    cve_almac=?,
                    status=?,
                    Cve_Usuario=?,
                    Observaciones=?,
                    tipo_venta=?,
                    Tot_Factura=?,
                    Activo=1
                WHERE id_pedido=?
            ");
            $upd->execute([
                $Fol_folio, $Fec_Pedido, $Fec_Entrega, $Cve_clte, $TipoPedido,
                $ruta, $vendedor, ($DiaO===''?null:$DiaO), $cve_almac, $status,
                $Cve_Usuario, $Observ, $tipo_venta, $Tot_Factura,
                $id_pedido_db
            ]);

            $del = $pdo->prepare("DELETE FROM td_pedido WHERE Fol_folio=?");
            $del->execute([$folio_db]);

            $id_pedido_final = $id_pedido_db;

        } else {
            $ins = $pdo->prepare("
                INSERT INTO th_pedido
                    (Fol_folio, Fec_Pedido, Cve_clte, status, Fec_Entrega, cve_Vendedor, DiaO, TipoPedido, ruta,
                     cve_almac, Cve_Usuario, Observaciones, tipo_venta, Tot_Factura, Activo)
                VALUES
                    (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)
            ");
            $ins->execute([
                $Fol_folio, $Fec_Pedido, $Cve_clte, $status, $Fec_Entrega, $vendedor,
                ($DiaO===''?null:$DiaO), $TipoPedido, $ruta,
                $cve_almac, $Cve_Usuario, $Observ, $tipo_venta, $Tot_Factura
            ]);
            $id_pedido_final = (int)$pdo->lastInsertId();
        }

        $insD = $pdo->prepare("
            INSERT INTO td_pedido
                (Fol_folio, Cve_articulo, Num_cantidad, status, Precio_unitario, Desc_Importe, IVA, Fec_Entrega, Proyecto, Activo, Num_revisadas)
            VALUES
                (?,?,?,?,?,?,?,?,?,1,0)
        ");

        for ($i=0; $i<count($a_art); $i++) {
            $cve = trim((string)($a_art[$i] ?? ''));
            $qty = (float)($a_qty[$i] ?? 0);
            if ($cve === '' || $qty <= 0) continue;

            $pu  = (float)($a_pu[$i] ?? 0);
            $des = (float)($a_desc[$i] ?? 0);
            $iv  = (float)($a_iva[$i] ?? 0);
            $pry = (string)($a_proy[$i] ?? '');

            $insD->execute([
                $Fol_folio,
                $cve,
                $qty,
                $status,
                $pu,
                $des,
                $iv,
                $Fec_Entrega,
                $pry
            ]);
        }

        $pdo->commit();

        $redir = "pedido_edit.php?id_pedido=" . urlencode((string)$id_pedido_final) . "&ok=1&id_empresa=" . urlencode((string)$id_empresa);
        ap_redirect($redir);

    } catch(Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = $e->getMessage();
    }
}

$H['TipoPedido'] = strtoupper(trim((string)$H['TipoPedido']));
if ($H['TipoPedido'] !== 'RUTA') $H['TipoPedido'] = 'CLIENTE';
?>
<style>
:root{
    --ap-primary:#0F5AAD;
    --ap-border:#e5e7eb;
    --ap-bg:#f5f7fb;
    --ap-muted:#6b7280;
}
body{ background:var(--ap-bg); }

/* 10px en todos los controles del módulo */
select, input, textarea, .form-control, .form-select{
    font-size:10px !important;
}
label{
    font-size:10px;
    font-weight:700;
    color:#374151;
}

/* Card */
.ap-card{ background:#fff; border:1px solid var(--ap-border); border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
.ap-title{ font-weight:900; color:var(--ap-primary); margin:0; font-size:16px; }
.ap-sub{ font-size:11px; color:var(--ap-muted); margin:0; }

/* Botón corporativo */
.btn-ap{ background:var(--ap-primary); border-color:var(--ap-primary); color:#fff; border-radius:999px; font-size:12px; font-weight:900; }
.btn-ap:hover{ background:#0c4a8d; border-color:#0c4a8d; color:#fff; }
.btn-xs{ font-size:10px; padding:2px 10px; border-radius:999px; }

/* Grilla enterprise: 25 filas visibles aprox, una sola línea, scroll */
.ap-table-wrapper{
    max-height:420px; /* ~25 filas */
    overflow-y:auto;
    overflow-x:auto;
    border:1px solid var(--ap-border);
    border-radius:10px;
}
.ap-table{
    table-layout:fixed;
    width:100%;
    margin:0;
}
.ap-table th,.ap-table td{
    font-size:10px !important;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    height:22px;
    line-height:1.1;
    vertical-align:middle;
}
.ap-table thead th{
    position:sticky;
    top:0;
    background:#f3f4f6;
    z-index:2;
}
.small-muted{ font-size:11px; color:var(--ap-muted); }
</style>

<div class="container-fluid mt-3">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="ap-title">Pedido — Edición (Cliente / Ruta)</h5>
            <p class="ap-sub">Operación rápida, consistente con OC, con asignación Ruta→Vendedor y clientes por ruta.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="registro_pedidos.php" class="btn btn-outline-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-success py-2" style="font-size:11px;">Pedido guardado correctamente.</div>
    <?php endif; ?>
    <?php if ($err !== ''): ?>
        <div class="alert alert-danger py-2" style="font-size:11px;">Error: <?php echo h($err); ?></div>
    <?php endif; ?>

    <form method="post" class="ap-card p-3">
        <input type="hidden" name="id_pedido" value="<?php echo (int)$id_pedido; ?>">
        <input type="hidden" name="id_empresa" id="id_empresa" value="<?php echo (int)$id_empresa; ?>">

        <div class="row g-2">
            <div class="col-md-2">
                <label>Tipo Pedido</label>
                <select class="form-select" name="TipoPedido" id="TipoPedido">
                    <option value="CLIENTE" <?php if ($H['TipoPedido']==='CLIENTE') echo 'selected'; ?>>Pedido Cliente</option>
                    <option value="RUTA" <?php if ($H['TipoPedido']==='RUTA') echo 'selected'; ?>>Pedido Ruta</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>Folio</label>
                <input class="form-control" name="Fol_folio" value="<?php echo h($H['Fol_folio']); ?>" placeholder="AUTO si vacío">
            </div>

            <div class="col-md-2">
                <label>Fecha Pedido</label>
                <input type="date" class="form-control" name="Fec_Pedido" value="<?php echo h(substr((string)$H['Fec_Pedido'],0,10)); ?>">
            </div>

            <div class="col-md-2">
                <label>Fecha Entrega</label>
                <input type="date" class="form-control" name="Fec_Entrega" value="<?php echo h(substr((string)$H['Fec_Entrega'],0,10)); ?>">
            </div>

            <div class="col-md-2">
                <label>Almacén</label>
                <select class="form-select" name="cve_almac" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($almacenes as $a): ?>
                        <option value="<?php echo h($a['id']); ?>" <?php if ((string)$a['id'] === (string)$H['cve_almac']) echo 'selected'; ?>>
                            <?php echo h(($a['clave'] ?? '—') . ' - ' . ($a['nombre'] ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label>Status</label>
                <select class="form-select" name="status">
                    <option value="A" <?php if ((string)$H['status']==='A') echo 'selected'; ?>>A</option>
                    <option value="C" <?php if ((string)$H['status']==='C') echo 'selected'; ?>>C</option>
                </select>
            </div>

            <!-- RUTA -->
            <div class="col-md-3" id="boxRuta">
                <label>Ruta</label>
                <select class="form-select" id="id_ruta">
                    <option value="">Seleccione…</option>
                    <?php
                    // Intentamos pre-seleccionar ruta por Cve_Ruta guardada en th_pedido.ruta
                    $ruta_saved = (string)$H['ruta'];
                    foreach ($rutas as $r):
                        $idR = (int)($r['ID_Ruta'] ?? 0);
                        $cve = (string)($r['Cve_Ruta'] ?? '');
                        $txt = trim($cve . ' - ' . (string)($r['Descripcion'] ?? ''));
                        $sel = ($ruta_saved !== '' && $cve === $ruta_saved) ? 'selected' : '';
                    ?>
                        <option value="<?php echo (int)$idR; ?>" data-cve="<?php echo h($cve); ?>" <?php echo $sel; ?>>
                            <?php echo h($txt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="ruta" id="ruta" value="<?php echo h($H['ruta']); ?>">
                <div class="small-muted mt-1">Al seleccionar la ruta se asigna vendedor y se filtran clientes automáticamente.</div>
            </div>

            <div class="col-md-3" id="boxVendedor">
                <label>Vendedor (auto)</label>
                <select class="form-select" id="id_vendedor" name="cve_Vendedor">
                    <option value="">(sin asignación)</option>
                    <?php foreach ($vendedores as $v): ?>
                        <?php
                          $idV = (int)($v['IdVendedor'] ?? 0);
                          $cv  = (string)($v['Cve_Vendedor'] ?? '');
                          $nm  = (string)($v['Nombre'] ?? '');
                          $sel = ((string)$H['cve_Vendedor'] !== '' && (string)$H['cve_Vendedor'] === (string)$idV) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int)$idV; ?>" <?php echo $sel; ?>>
                            <?php echo h(trim($nm . " (" . $cv . ")")); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2" id="boxDiaO">
                <label>Día Operativo</label>
                <input class="form-control" name="DiaO" id="DiaO" value="<?php echo h($H['DiaO']); ?>" placeholder="Ej. 1">
            </div>

            <!-- CLIENTE -->
            <div class="col-md-4" id="boxCliente">
                <label>Cliente</label>
                <select class="form-select" name="Cve_clte" id="Cve_clte">
                    <option value="">Seleccione…</option>
                    <?php foreach ($clientes as $c): ?>
                        <?php $v = (string)$c['Cve_Clte']; ?>
                        <option value="<?php echo h($v); ?>" <?php if ($v === (string)$H['Cve_clte']) echo 'selected'; ?>>
                            <?php echo h($c['RazonSocial'] . " (" . $c['Cve_Clte'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small-muted mt-1">En Pedido Ruta, el listado se reduce a los clientes de esa ruta (para ejecución rápida).</div>
            </div>

            <div class="col-md-2">
                <label>Tipo Venta</label>
                <?php $tv = strtoupper(trim((string)$H['tipo_venta'])); ?>
                <select class="form-select" name="tipo_venta">
                    <option value="VENTA" <?php if ($tv==='VENTA') echo 'selected'; ?>>VENTA</option>
                    <option value="PREVENTA" <?php if ($tv==='PREVENTA') echo 'selected'; ?>>PREVENTA</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>Usuario</label>
                <input class="form-control" name="Cve_Usuario" value="<?php echo h($H['Cve_Usuario']); ?>">
            </div>

            <div class="col-md-8">
                <label>Observaciones</label>
                <input class="form-control" name="Observaciones" value="<?php echo h($H['Observaciones']); ?>">
            </div>
        </div>

        <hr class="my-3">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-weight:900;color:#0F5AAD;font-size:12px;">Detalle</div>
            <button type="button" class="btn btn-outline-primary btn-xs" id="btnAddRow">Agregar partida</button>
        </div>

        <div class="ap-table-wrapper">
            <table class="table table-sm table-hover ap-table" id="tblDet">
                <thead>
                    <tr>
                        <th style="width:44px;">#</th>
                        <th style="width:190px;">Artículo</th>
                        <th style="width:320px;">Descripción</th>
                        <th style="width:100px;" class="text-end">Cantidad</th>
                        <th style="width:110px;" class="text-end">Precio</th>
                        <th style="width:110px;" class="text-end">Descto $</th>
                        <th style="width:110px;" class="text-end">IVA</th>
                        <th style="width:220px;">Proyecto</th>
                        <th style="width:90px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$D): ?>
                    <tr>
                        <td class="text-muted">1</td>
                        <td><input list="dlArt" class="form-control inp-art" name="Cve_articulo[]" value=""></td>
                        <td><input class="form-control inp-des" value="" readonly></td>
                        <td><input type="number" step="0.0001" class="form-control text-end inp-qty" name="Num_cantidad[]" value=""></td>
                        <td><input type="number" step="0.0001" class="form-control text-end inp-pu" name="Precio_unitario[]" value=""></td>
                        <td><input type="number" step="0.0001" class="form-control text-end inp-desc" name="Desc_Importe[]" value="0"></td>
                        <td><input type="number" step="0.0001" class="form-control text-end inp-iva" name="IVA[]" value="0"></td>
                        <td><input class="form-control" name="Proyecto[]" value=""></td>
                        <td><button type="button" class="btn btn-outline-danger btn-xs btnDel">Eliminar</button></td>
                    </tr>
                <?php else: ?>
                    <?php $n=0; foreach ($D as $r): $n++; ?>
                        <tr>
                            <td class="text-muted"><?php echo $n; ?></td>
                            <td><input list="dlArt" class="form-control inp-art" name="Cve_articulo[]" value="<?php echo h($r['Cve_articulo']); ?>"></td>
                            <td><input class="form-control inp-des" value="<?php echo h($r['des_articulo'] ?? ''); ?>" readonly></td>
                            <td><input type="number" step="0.0001" class="form-control text-end inp-qty" name="Num_cantidad[]" value="<?php echo h($r['Num_cantidad']); ?>"></td>
                            <td><input type="number" step="0.0001" class="form-control text-end inp-pu" name="Precio_unitario[]" value="<?php echo h($r['Precio_unitario'] ?? 0); ?>"></td>
                            <td><input type="number" step="0.0001" class="form-control text-end inp-desc" name="Desc_Importe[]" value="<?php echo h($r['Desc_Importe'] ?? 0); ?>"></td>
                            <td><input type="number" step="0.0001" class="form-control text-end inp-iva" name="IVA[]" value="<?php echo h($r['IVA'] ?? 0); ?>"></td>
                            <td><input class="form-control" name="Proyecto[]" value="<?php echo h($r['Proyecto'] ?? ''); ?>"></td>
                            <td><button type="button" class="btn btn-outline-danger btn-xs btnDel">Eliminar</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <datalist id="dlArt">
            <?php foreach ($mapArt as $cve=>$a): ?>
                <option value="<?php echo h($cve); ?>"><?php echo h($a['des']); ?></option>
            <?php endforeach; ?>
        </datalist>

        <div class="mt-3 d-flex justify-content-between align-items-center">
            <div class="small-muted">
                Total: <strong id="lblTotal">0.00</strong>
                <span class="ms-2">| Subtotal: <strong id="lblSub">0.00</strong></span>
                <span class="ms-2">| IVA: <strong id="lblIva">0.00</strong></span>
            </div>
            <button type="submit" class="btn btn-ap">Guardar</button>
        </div>

    </form>
</div>

<script>
const MAP_ART = <?php echo json_encode($mapArt, JSON_UNESCAPED_UNICODE); ?>;

function norm(s){ return (s||'').toString().trim(); }

function recalc(){
    const rows = document.querySelectorAll('#tblDet tbody tr');
    let sub = 0, iva = 0;
    rows.forEach(tr=>{
        const qty = Number(tr.querySelector('.inp-qty')?.value || 0);
        const pu  = Number(tr.querySelector('.inp-pu')?.value || 0);
        const des = Number(tr.querySelector('.inp-desc')?.value || 0);
        const iv  = Number(tr.querySelector('.inp-iva')?.value || 0);
        let imp = (qty * pu) - des;
        if (imp < 0) imp = 0;
        sub += imp;
        iva += iv;
    });
    document.getElementById('lblSub').textContent = sub.toFixed(2);
    document.getElementById('lblIva').textContent = iva.toFixed(2);
    document.getElementById('lblTotal').textContent = (sub + iva).toFixed(2);
}

function resolveArticulo(inp){
    const cve = norm(inp.value);
    const tr = inp.closest('tr');
    const des = tr.querySelector('.inp-des');
    const pu  = tr.querySelector('.inp-pu');
    if (!cve || !MAP_ART[cve]){
        if (des) des.value='';
        return;
    }
    if (des) des.value = MAP_ART[cve].des || '';
    if (pu && (!pu.value || Number(pu.value)===0)) {
        pu.value = (MAP_ART[cve].pv || 0);
    }
    recalc();
}

function renum(){
    document.querySelectorAll('#tblDet tbody tr').forEach((tr,i)=>{
        tr.children[0].textContent = (i+1);
    });
}

function bindRow(tr){
    const art = tr.querySelector('.inp-art');
    if (art){
        art.addEventListener('change', ()=>resolveArticulo(art));
        art.addEventListener('blur', ()=>resolveArticulo(art));
        resolveArticulo(art);
    }
    tr.querySelectorAll('.inp-qty,.inp-pu,.inp-desc,.inp-iva').forEach(el=>{
        el.addEventListener('input', recalc);
    });
    const btn = tr.querySelector('.btnDel');
    if (btn){
        btn.addEventListener('click', ()=>{
            const tbody = tr.parentElement;
            if (tbody.querySelectorAll('tr').length === 1){
                tr.querySelectorAll('input').forEach(i=>i.value='');
                const des = tr.querySelector('.inp-des'); if (des) des.value='';
            } else {
                tr.remove();
                renum();
            }
            recalc();
        });
    }
}

async function setVendedorByRuta(idRuta){
    const idEmpresa = parseInt(document.getElementById('id_empresa').value || '0', 10);
    if (!idRuta || !idEmpresa) return;

    const url = `/public/api/pedidos/pedido_ruta_vendedor.php?id_ruta=${encodeURIComponent(idRuta)}&id_empresa=${encodeURIComponent(idEmpresa)}`;
    const r = await fetch(url);
    const j = await r.json();
    if (j.ok){
        const sel = document.getElementById('id_vendedor');
        sel.value = String(j.id_vendedor || '');
        sel.dispatchEvent(new Event('change'));
    }
}

async function loadClientesByCveRuta(cveRuta){
    const idEmpresa = document.getElementById('id_empresa').value || '';
    if (!cveRuta) return;

    const url = `/public/api/pedidos/pedido_ruta_clientes.php?cve_ruta=${encodeURIComponent(cveRuta)}&id_empresa=${encodeURIComponent(idEmpresa)}`;
    const r = await fetch(url);
    const j = await r.json();

    const selCli = document.getElementById('Cve_clte');
    if (!selCli) return;

    const current = selCli.value;

    // Reconstruimos options
    selCli.innerHTML = `<option value="">Seleccione…</option>`;

    if (j.ok && Array.isArray(j.rows)){
        j.rows.forEach(c=>{
            const opt = document.createElement('option');
            opt.value = c.Cve_Clte || '';
            opt.textContent = `${c.RazonSocial || ''} (${c.Cve_Clte || ''})`;
            selCli.appendChild(opt);
        });
        // Si el cliente actual sigue existiendo, lo conservamos
        selCli.value = current;
    }
}

function toggleTipo(){
    const t = (document.getElementById('TipoPedido').value || 'CLIENTE').toUpperCase();
    const isRuta = (t === 'RUTA');
    document.getElementById('boxRuta').style.display = isRuta ? '' : 'none';
    document.getElementById('boxVendedor').style.display = isRuta ? '' : 'none';
    document.getElementById('boxDiaO').style.display = isRuta ? '' : 'none';
    document.getElementById('boxCliente').style.display = ''; // siempre visible, pero se filtra si es ruta
}

document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('#tblDet tbody tr').forEach(bindRow);
    recalc();
    toggleTipo();

    document.getElementById('TipoPedido').addEventListener('change', ()=>{
        toggleTipo();
        // si cambian a RUTA y ya hay ruta seleccionada, refrescamos
        const selRuta = document.getElementById('id_ruta');
        const opt = selRuta.options[selRuta.selectedIndex];
        const cve = opt ? (opt.getAttribute('data-cve') || '') : '';
        if ((document.getElementById('TipoPedido').value || '').toUpperCase()==='RUTA'){
            if (selRuta.value) {
                document.getElementById('ruta').value = cve;
                setVendedorByRuta(parseInt(selRuta.value||'0',10));
                if (cve) loadClientesByCveRuta(cve);
            }
        }
    });

    document.getElementById('btnAddRow').addEventListener('click', ()=>{
        const tbody = document.querySelector('#tblDet tbody');
        const base = tbody.querySelector('tr:last-child');
        const clone = base.cloneNode(true);

        clone.querySelectorAll('input').forEach(inp=>{
            if (inp.classList.contains('inp-des')) { inp.value=''; return; }
            inp.value = (inp.classList.contains('inp-desc') || inp.classList.contains('inp-iva')) ? '0' : '';
        });

        tbody.appendChild(clone);
        bindRow(clone);
        renum();
        recalc();
    });

    // Ruta -> (guarda Cve_Ruta) + vendedor + clientes
    const selRuta = document.getElementById('id_ruta');
    selRuta.addEventListener('change', async ()=>{
        const idRuta = parseInt(selRuta.value || '0', 10);
        const opt = selRuta.options[selRuta.selectedIndex];
        const cve = opt ? (opt.getAttribute('data-cve') || '') : '';

        document.getElementById('ruta').value = cve;

        const isRuta = (document.getElementById('TipoPedido').value || '').toUpperCase()==='RUTA';
        if (!isRuta) return;

        if (idRuta) await setVendedorByRuta(idRuta);
        if (cve)   await loadClientesByCveRuta(cve);
    });

    // Si el pedido ya venía con ruta guardada, intentamos filtrar clientes al abrir
    const tipo = (document.getElementById('TipoPedido').value || '').toUpperCase();
    if (tipo==='RUTA'){
        const opt = selRuta.options[selRuta.selectedIndex];
        const cve = opt ? (opt.getAttribute('data-cve') || '') : document.getElementById('ruta').value;
        if (selRuta.value) setVendedorByRuta(parseInt(selRuta.value||'0',10));
        if (cve) loadClientesByCveRuta(cve);
    }
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
