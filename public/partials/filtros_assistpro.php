<?php
// public/partials/filtros_assistpro.php
// Filtros generales — AssistPro (constructor de templates + filtros funcionales)

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';


$pdo = db_pdo();

// =========================
// 1. Catálogos base
// =========================

// EMPRESAS (c_compania)
$empresas = [];
try {
    $empresas = db_all("
        SELECT cve_cia, des_cia
        FROM c_compania
        WHERE COALESCE(Activo,1) = 1
        ORDER BY des_cia
    ");
} catch (Throwable $e) {
    $empresas = [];
}

// ALMACENES (macro-almacén: c_almacenp)
$almacenes = [];
try {
    $almacenes = db_all("
        SELECT id, clave, nombre
        FROM c_almacenp
        WHERE COALESCE(Activo,'S') <> 'N'
        ORDER BY clave, nombre
    ");
} catch (Throwable $e) {
    $almacenes = [];
}

// =========================
// 2. Valores seleccionados
// =========================

$empresaSel    = $_GET['empresa']    ?? '';
$almacenSel    = $_GET['almacen']    ?? '';
$zonaSel       = $_GET['zona']       ?? '';
$blSel         = $_GET['bl']         ?? '';
$rutaSel       = $_GET['ruta']       ?? '';
$clienteSel    = $_GET['cliente']    ?? '';
$lpSel         = $_GET['lp']         ?? '';
$prodSel       = $_GET['producto']   ?? '';
$loteSel       = $_GET['lote']       ?? '';
$proveedorSel  = $_GET['proveedor']  ?? '';
$vendedorSel   = $_GET['vendedor']   ?? '';
$usuarioSel    = $_GET['usuario']    ?? '';
$zonaRecepSel  = $_GET['zona_recep'] ?? '';
$zonaQASel     = $_GET['zona_qa']    ?? '';
$zonaEmbSel    = $_GET['zona_emb']   ?? '';
$proyectoSel   = $_GET['proyecto']   ?? '';

// checks de “usar en template”
$useEmpresa    = isset($_GET['use_empresa'])    ? $_GET['use_empresa']    === '1' : true;
$useAlmacen    = isset($_GET['use_almacen'])    ? $_GET['use_almacen']    === '1' : true;
$useZona       = isset($_GET['use_zona'])       ? $_GET['use_zona']       === '1' : true;
$useBL         = isset($_GET['use_bl'])         ? $_GET['use_bl']         === '1' : true;
$useLP         = isset($_GET['use_lp'])         ? $_GET['use_lp']         === '1' : true;
$useProd       = isset($_GET['use_producto'])   ? $_GET['use_producto']   === '1' : true;
$useLote       = isset($_GET['use_lote'])       ? $_GET['use_lote']       === '1' : true;
$useRuta       = isset($_GET['use_ruta'])       ? $_GET['use_ruta']       === '1' : true;
$useCliente    = isset($_GET['use_cliente'])    ? $_GET['use_cliente']    === '1' : true;
$useProv       = isset($_GET['use_proveedor'])  ? $_GET['use_proveedor']  === '1' : true;
$useVend       = isset($_GET['use_vendedor'])   ? $_GET['use_vendedor']   === '1' : true;
$useUsuario    = isset($_GET['use_usuario'])    ? $_GET['use_usuario']    === '1' : true;
$useZonaRecep  = isset($_GET['use_zona_recep']) ? $_GET['use_zona_recep'] === '1' : true;
$useZonaQA     = isset($_GET['use_zona_qa'])    ? $_GET['use_zona_qa']    === '1' : true;
$useZonaEmb    = isset($_GET['use_zona_emb'])   ? $_GET['use_zona_emb']   === '1' : true;
$useProyecto   = isset($_GET['use_proyecto'])   ? $_GET['use_proyecto']   === '1' : true;

// =========================
// 3. Catálogos dependientes
// =========================

// ZONAS DE ALMACENAJE (c_almacen) – dependen de c_almacenp.id
$zonas = [];
if ($almacenSel !== '') {
    try {
        $zonas = db_all("
            SELECT cve_almac, des_almac
            FROM c_almacen
            WHERE COALESCE(Activo,'S') <> 'N'
              AND cve_almacenp = ?
            ORDER BY des_almac
        ", [$almacenSel]);
    } catch (Throwable $e) {
        $zonas = [];
    }
}

// BL (Bin Location) – c_ubicacion.CodigoCSD por zona
$bls = [];
if ($zonaSel !== '') {
    try {
        $bls = db_all("
            SELECT DISTINCT CodigoCSD
            FROM c_ubicacion
            WHERE COALESCE(Activo,'S') <> 'N'
              AND cve_almac = ?
              AND NULLIF(CodigoCSD,'') IS NOT NULL
            ORDER BY CodigoCSD
            LIMIT 500
        ", [$zonaSel]);
    } catch (Throwable $e) {
        $bls = [];
    }
}

// RUTAS (t_ruta)
$rutas = [];
try {
    $rutas = db_all("
        SELECT ID_Ruta, cve_ruta, descripcion
        FROM t_ruta
        WHERE COALESCE(Activo,1) = 1
        ORDER BY cve_ruta
    ");
} catch (Throwable $e) {
    $rutas = [];
}

// CLIENTES (c_cliente) – top 500
$clientes = [];
try {
    $clientes = db_all("
        SELECT id_cliente, Cve_Clte, RazonSocial
        FROM c_cliente
        ORDER BY RazonSocial
        LIMIT 500
    ");
} catch (Throwable $e) {
    $clientes = [];
}

// LICENSE PLATE (LP) – c_charolas
$lps = [];
try {
    $lps = db_all("
        SELECT DISTINCT CveLP
        FROM c_charolas
        WHERE COALESCE(Activo,1) = 1
          AND NULLIF(CveLP,'') IS NOT NULL
        ORDER BY CveLP
        LIMIT 500
    ");
} catch (Throwable $e) {
    $lps = [];
}

// PRODUCTOS – c_articulo
$productos = [];
try {
    $productos = db_all("
        SELECT cve_articulo, des_articulo
        FROM c_articulo
        WHERE COALESCE(Activo,'S') <> 'N'
        ORDER BY des_articulo
        LIMIT 500
    ");
} catch (Throwable $e) {
    $productos = [];
}

// PROVEEDORES – c_proveedores
$proveedores = [];
try {
    $proveedores = db_all("
        SELECT ID_Proveedor, cve_proveedor, Nombre
        FROM c_proveedores
        WHERE COALESCE(Activo,1) = 1
        ORDER BY Nombre
        LIMIT 500
    ");
} catch (Throwable $e) {
    $proveedores = [];
}

// VENDEDORES – t_vendedores (ajusta si el naming es distinto)
$vendedores = [];
try {
    $vendedores = db_all("
        SELECT id_vendedor, cve_vendedor, nombre
        FROM t_vendedores
        ORDER BY nombre
        LIMIT 500
    ");
} catch (Throwable $e) {
    $vendedores = [];
}

// USUARIOS – c_usuarios (ajusta columnas si difieren)
$usuarios = [];
try {
    $usuarios = db_all("
        SELECT id_usuario, usuario, nombre
        FROM c_usuarios
        ORDER BY nombre
        LIMIT 500
    ");
} catch (Throwable $e) {
    $usuarios = [];
}

// Zonas de recepción / retención – tubicacionesretencion
$zonasRecep = [];
try {
    $zonasRecep = db_all("
        SELECT id, cve_ubicacion, desc_ubicacion
        FROM tubicacionesretencion
        WHERE COALESCE(Activo,1) = 1
        ORDER BY cve_ubicacion, desc_ubicacion
    ");
} catch (Throwable $e) {
    $zonasRecep = [];
}

// Zonas de QA / revisión – t_ubicaciones_revision
$zonasQA = [];
try {
    $zonasQA = db_all("
        SELECT ID_URevision, cve_ubicacion, descripcion
        FROM t_ubicaciones_revision
        WHERE COALESCE(Activo,1) = 1
        ORDER BY cve_ubicacion, descripcion
    ");
} catch (Throwable $e) {
    $zonasQA = [];
}

// Zonas de embarque – t_ubicacionembarque
$zonasEmb = [];
try {
    $zonasEmb = db_all("
        SELECT ID_Embarque, cve_ubicacion, descripcion
        FROM t_ubicacionembarque
        WHERE COALESCE(Activo,1) = 1
        ORDER BY cve_ubicacion, descripcion
    ");
} catch (Throwable $e) {
    $zonasEmb = [];
}

// Proyectos – c_proyecto (si hay almacén, filtramos por id_almacen)
$proyectos = [];
try {
    if ($almacenSel !== '') {
        $proyectos = db_all("
            SELECT Id, Cve_Proyecto, Des_Proyecto
            FROM c_proyecto
            WHERE id_almacen = ?
            ORDER BY Des_Proyecto
        ", [$almacenSel]);
    } else {
        $proyectos = db_all("
            SELECT Id, Cve_Proyecto, Des_Proyecto
            FROM c_proyecto
            ORDER BY Des_Proyecto
        ");
    }
} catch (Throwable $e) {
    $proyectos = [];
}

// Helper para options
function ap_opt(string $value, string $label, string $selected): string {
    $sel = ($value === $selected) ? ' selected' : '';
    return '<option value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'.
           htmlspecialchars($label, ENT_QUOTES, 'UTF-8').
           '</option>';
}

?>
<style>
    .ap-filtros-bar {
        background:#0F5AAD;
        color:#fff;
        font-size:11px;
        padding:6px 10px;
        border-radius:4px 4px 0 0;
        margin-bottom:0;
    }
    .ap-filtros-body {
        background:#fff;
        border:1px solid #d0d0d0;
        border-top:none;
        border-radius:0 0 4px 4px;
        padding:8px 10px 6px 10px;
        font-size:11px;
    }
    .ap-row {
        display:flex;
        flex-wrap:wrap;
        gap:8px 16px;
        margin-bottom:4px;
    }
    .ap-col {
        min-width:230px;
    }
    .ap-label {
        font-weight:600;
        margin-bottom:2px;
    }
    .ap-select,
    .ap-input {
        font-size:11px;
        height:24px;
        padding:2px 4px;
    }
    .ap-btn {
        font-size:11px;
        height:24px;
        padding:2px 8px;
    }
    .ap-check-label {
        font-weight:400;
        margin-left:2px;
    }
</style>

<div class="ap-filtros-bar d-flex justify-content-between align-items-center">
    <div><strong>Filtros generales — AssistPro</strong></div>
    <div>
        <button type="button" class="btn btn-light ap-btn" onclick="apFiltrosLimpiar()">Limpiar</button>
        <button type="submit" form="formFiltrosAssistpro" class="btn btn-warning ap-btn">Aplicar</button>
    </div>
</div>

<div class="ap-filtros-body">
    <form id="formFiltrosAssistpro" method="get">

        <!-- Fila 1: Empresa / Almacén / Zona / BL -->
        <div class="ap-row">

            <!-- Empresa -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_empresa" value="1" <?= $useEmpresa?'checked':''; ?>>
                    <span class="ap-check-label">Empresa</span>
                </div>
                <select name="empresa" class="form-select ap-select">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $e): ?>
                        <?= ap_opt((string)$e['cve_cia'], $e['des_cia'], (string)$empresaSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Almacén (macro) -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_almacen" value="1" <?= $useAlmacen?'checked':''; ?>>
                    <span class="ap-check-label">Almacén</span>
                </div>
                <select name="almacen" class="form-select ap-select" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($almacenes as $a):
                        $txt = trim(($a['clave'] ?? '').' - '.($a['nombre'] ?? ''));
                    ?>
                        <?= ap_opt((string)$a['id'], $txt, (string)$almacenSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Zona de almacenaje -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_zona" value="1" <?= $useZona?'checked':''; ?>>
                    <span class="ap-check-label">Zona de almacenaje</span>
                </div>
                <select name="zona" class="form-select ap-select" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($zonas as $z):
                        $txt = trim($z['cve_almac'].' - '.$z['des_almac']);
                    ?>
                        <?= ap_opt((string)$z['cve_almac'], $txt, (string)$zonaSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- BL (Bin Location) -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_bl" value="1" <?= $useBL?'checked':''; ?>>
                    <span class="ap-check-label">BL (Bin Location)</span>
                </div>
                <select name="bl" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($bls as $b): ?>
                        <?= ap_opt((string)$b['CodigoCSD'], (string)$b['CodigoCSD'], (string)$blSel); ?>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Fuente: c_ubicacion.CodigoCSD</small>
            </div>

        </div>

        <!-- Fila 2: LP / Producto / Lote -->
        <div class="ap-row">

            <!-- License Plate -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_lp" value="1" <?= $useLP?'checked':''; ?>>
                    <span class="ap-check-label">License Plate (LP)</span>
                </div>
                <select name="lp" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($lps as $lp): ?>
                        <?= ap_opt((string)$lp['CveLP'], (string)$lp['CveLP'], (string)$lpSel); ?>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Fuente: c_charolas.CveLP</small>
            </div>

            <!-- Producto -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_producto" value="1" <?= $useProd?'checked':''; ?>>
                    <span class="ap-check-label">Producto</span>
                </div>
                <select name="producto" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($productos as $p):
                        $txt = trim($p['cve_articulo'].' - '.$p['des_articulo']);
                    ?>
                        <?= ap_opt((string)$p['cve_articulo'], $txt, (string)$prodSel); ?>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Fuente: c_articulo</small>
            </div>

            <!-- Lote / Serie -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_lote" value="1" <?= $useLote?'checked':''; ?>>
                    <span class="ap-check-label">Lote / Serie</span>
                </div>
                <input type="text"
                       name="lote"
                       value="<?= htmlspecialchars($loteSel, ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-control ap-input"
                       placeholder="Lote o serie">
                <small class="text-muted">Serie = S en c_lotes</small>
            </div>

        </div>

        <!-- Fila 3: Ruta / Cliente / Proveedor -->
        <div class="ap-row">

            <!-- Ruta -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_ruta" value="1" <?= $useRuta?'checked':''; ?>>
                    <span class="ap-check-label">Ruta</span>
                </div>
                <select name="ruta" class="form-select ap-select">
                    <option value="">Todas</option>
                    <?php foreach ($rutas as $r):
                        $txt = trim($r['cve_ruta'].' - '.$r['descripcion']);
                    ?>
                        <?= ap_opt((string)$r['ID_Ruta'], $txt, (string)$rutaSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cliente -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_cliente" value="1" <?= $useCliente?'checked':''; ?>>
                    <span class="ap-check-label">Cliente</span>
                </div>
                <select name="cliente" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($clientes as $c):
                        $rs  = html_entity_decode((string)$c['RazonSocial'], ENT_QUOTES, 'UTF-8');
                        $txt = trim(($c['Cve_Clte'] ?? '').' - '.$rs);
                    ?>
                        <?= ap_opt((string)$c['id_cliente'], $txt, (string)$clienteSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Proveedor -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_proveedor" value="1" <?= $useProv?'checked':''; ?>>
                    <span class="ap-check-label">Proveedor</span>
                </div>
                <select name="proveedor" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($proveedores as $p):
                        $txt = trim(($p['cve_proveedor'] ?? '').' - '.($p['Nombre'] ?? ''));
                    ?>
                        <?= ap_opt((string)$p['ID_Proveedor'], $txt, (string)$proveedorSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <!-- Fila 4: Vendedor / Usuario / Proyecto -->
        <div class="ap-row">

            <!-- Vendedor -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_vendedor" value="1" <?= $useVend?'checked':''; ?>>
                    <span class="ap-check-label">Vendedor</span>
                </div>
                <select name="vendedor" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($vendedores as $v):
                        $txt = trim(($v['cve_vendedor'] ?? '').' - '.($v['nombre'] ?? ''));
                    ?>
                        <?= ap_opt((string)$v['id_vendedor'], $txt, (string)$vendedorSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Usuario -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_usuario" value="1" <?= $useUsuario?'checked':''; ?>>
                    <span class="ap-check-label">Usuario</span>
                </div>
                <select name="usuario" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u):
                        $txt = trim(($u['usuario'] ?? '').' - '.($u['nombre'] ?? ''));
                    ?>
                        <?= ap_opt((string)$u['id_usuario'], $txt, (string)$usuarioSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Proyecto -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_proyecto" value="1" <?= $useProyecto?'checked':''; ?>>
                    <span class="ap-check-label">Proyecto</span>
                </div>
                <select name="proyecto" class="form-select ap-select">
                    <option value="">Todos</option>
                    <?php foreach ($proyectos as $pr):
                        $txt = trim(($pr['Cve_Proyecto'] ?? '').' - '.($pr['Des_Proyecto'] ?? ''));
                    ?>
                        <?= ap_opt((string)$pr['Id'], $txt, (string)$proyectoSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <!-- Fila 5: Zonas recepción / QA / embarques -->
        <div class="ap-row">

            <!-- Zona recepción / retención -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_zona_recep" value="1" <?= $useZonaRecep?'checked':''; ?>>
                    <span class="ap-check-label">Zona recepción / retención</span>
                </div>
                <select name="zona_recep" class="form-select ap-select">
                    <option value="">Todas</option>
                    <?php foreach ($zonasRecep as $zr):
                        $txt = trim(($zr['cve_ubicacion'] ?? '').' - '.($zr['desc_ubicacion'] ?? ''));
                    ?>
                        <?= ap_opt((string)$zr['id'], $txt, (string)$zonaRecepSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Zona QA / revisión -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_zona_qa" value="1" <?= $useZonaQA?'checked':''; ?>>
                    <span class="ap-check-label">Zona QA / revisión</span>
                </div>
                <select name="zona_qa" class="form-select ap-select">
                    <option value="">Todas</option>
                    <?php foreach ($zonasQA as $zq):
                        $txt = trim(($zq['cve_ubicacion'] ?? '').' - '.($zq['descripcion'] ?? ''));
                    ?>
                        <?= ap_opt((string)$zq['ID_URevision'], $txt, (string)$zonaQASel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Zona embarques -->
            <div class="ap-col">
                <div class="ap-label">
                    <input type="checkbox" name="use_zona_emb" value="1" <?= $useZonaEmb?'checked':''; ?>>
                    <span class="ap-check-label">Zona embarques</span>
                </div>
                <select name="zona_emb" class="form-select ap-select">
                    <option value="">Todas</option>
                    <?php foreach ($zonasEmb as $ze):
                        $txt = trim(($ze['cve_ubicacion'] ?? '').' - '.($ze['descripcion'] ?? ''));
                    ?>
                        <?= ap_opt((string)$ze['ID_Embarque'], $txt, (string)$zonaEmbSel); ?>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

    </form>
</div>

<script>
function apFiltrosLimpiar() {
    const form = document.getElementById('formFiltrosAssistpro');
    if (!form) return;

    for (const el of form.elements) {
        if (!el.name) continue;
        if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        } else if (el.type === 'text') {
            el.value = '';
        } else if (el.type === 'checkbox') {
            el.checked = true; // por defecto, todos los filtros activos en plantilla
        }
    }
    form.submit();
}
require_once __DIR__ . '/../bi/_menu_global_end.php';

</script>
