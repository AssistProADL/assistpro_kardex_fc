<?php
// public/portal_admin_imagenes/editar_ficha.php

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';


$usuario = $_SESSION['username'] ?? 'ADMIN';

// -------------------------------------------------
// LECTURA DE PARÁMETROS
// -------------------------------------------------
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro = isset($_GET['filtro']) ? strtoupper(trim($_GET['filtro'])) : 'SIN';
if (!in_array($filtro, ['SIN', 'TODOS'])) {
    $filtro = 'SIN';
}

$mensaje_ok  = '';
$mensaje_err = '';
$articulo    = null;

// -------------------------------------------------
// GUARDAR FICHA TÉCNICA
// -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accion'])
    && $_POST['accion'] === 'guardar') {

    $id_post = (int)($_POST['id_articulo'] ?? 0);
    $ficha   = trim($_POST['des_detallada'] ?? '');
    $tags    = trim($_POST['ecommerce_tags'] ?? '');

    if ($id_post <= 0) {
        $mensaje_err = 'ID de artículo inválido.';
    } else {
        try {
            dbq("
                UPDATE c_articulo
                   SET des_detallada = :ficha,
                       ecommerce_tags = :tags
                 WHERE id = :id
            ", [
                ':ficha' => $ficha !== '' ? $ficha : null,
                ':tags'  => $tags  !== '' ? $tags  : null,
                ':id'    => $id_post
            ]);

            $mensaje_ok = 'Ficha técnica actualizada correctamente.';
            $id = $id_post; // recargar el mismo artículo después de guardar
        } catch (Throwable $e) {
            $mensaje_err = 'Error al guardar ficha técnica: ' . $e->getMessage();
        }
    }
}

// -------------------------------------------------
// CARGAR ARTÍCULO SELECCIONADO (SI HAY ID)
// -------------------------------------------------
if ($id > 0) {
    $articulo = db_one("
        SELECT id, cve_articulo, des_articulo,
               des_detallada, ecommerce_tags,
               ecommerce_categoria, ecommerce_img_principal
          FROM c_articulo
         WHERE id = :id
    ", [':id' => $id]);
}

// -------------------------------------------------
// LISTADO DE ARTÍCULOS E-COMMERCE
// -------------------------------------------------
$params = [];
$sql    = "
    SELECT
        id,
        cve_articulo,
        des_articulo,
        ecommerce_categoria,
        des_detallada,
        ecommerce_tags
    FROM c_articulo
    WHERE IFNULL(Activo,0) = 1
      AND IFNULL(ecommerce_activo,0) = 1
";

if ($filtro === 'SIN') {
    $sql .= " AND (des_detallada IS NULL OR des_detallada = '') ";
}

$sql .= " ORDER BY des_articulo";

$articulos = db_all($sql, $params);

// helper para imagen
function imgUrlFicha($path) {
    if (!$path) {
        return 'https://via.placeholder.com/160x120?text=Producto';
    }
    if (preg_match('~^https?://~i', $path) || preg_match('~^//~', $path)) {
        return $path;
    }
    if (preg_match('~^\d+x\d+\?text=~i', $path)) {
        return 'https://via.placeholder.com/' . $path;
    }
    return '../' . ltrim($path, '/');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Editar ficha técnica</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{
    font-family:Arial,sans-serif;
    font-size:10px;
    background:#f2f4f8;
    margin:0;
}
.wrap{
    padding:14px 18px;
    margin-left:260px;   /* respeta menú */
    margin-right:18px;   /* usar casi todo el frame */
}
h1{
    font-size:16px;
    margin:0 0 10px;
    color:#0F5AAD;
}
.card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 1px 4px rgba(0,0,0,.12);
    padding:12px 16px;
    margin-bottom:12px;
}
label{
    font-weight:bold;
    color:#555;
    font-size:10px;
}
input[type=text],textarea{
    width:100%;
    font-size:10px;
    padding:6px;
    border:1px solid #ccc;
    border-radius:6px;
    box-sizing:border-box;
}
textarea{min-height:160px}
.btn{
    font-size:10px;
    padding:5px 10px;
    border-radius:6px;
    border:1px solid #0F5AAD;
    background:#0F5AAD;
    color:#fff;
    cursor:pointer;
}
.btn.btn-light{
    background:#fff;
    color:#0F5AAD;
}
.btn.btn-small{
    font-size:9px;
    padding:3px 8px;
}
.msg-ok{
    margin-bottom:8px;
    padding:6px 8px;
    border-radius:6px;
    background:#e9f8ef;
    border:1px solid #2ecc71;
    color:#1e7c3c;
}
.msg-err{
    margin-bottom:8px;
    padding:6px 8px;
    border-radius:6px;
    background:#fdecea;
    border:1px solid #e74c3c;
    color:#b1271b;
}
table{
    width:100%;
    border-collapse:collapse;
    font-size:10px;
}
th,td{
    padding:5px 6px;
    border-bottom:1px solid #eee;
    text-align:left;
}
th{background:#f5f7fb}
.badge{
    display:inline-block;
    padding:2px 6px;
    border-radius:6px;
    font-size:9px;
}
.badge.si{background:#e9f8ef;color:#1e7c3c}
.badge.no{background:#fdecea;color:#b1271b}
.filtros-lista{
    display:flex;
    justify-content:flex-end;
    gap:6px;
    margin-bottom:6px;
}

/* Layout tipo grilla en el panel superior */
.top-grid{
    display:grid;
    grid-template-columns: 1.6fr 1.4fr;
    gap:12px;
}
.top-info{
    font-size:11px;
}
.top-info strong{font-weight:bold}
.top-img{
    text-align:right;
}
.top-img img{
    width:160px;
    height:120px;
    object-fit:contain;
    border-radius:8px;
    background:#f5f5f5;
    border:1px solid #eee;
}

/* ayuda para plantilla de ficha tipo grilla */
.help-text{
    font-size:9px;
    color:#777;
    margin-top:4px;
}
.help-text code{
    background:#f4f4f4;
    padding:1px 4px;
    border-radius:4px;
}
</style>
</head>
<body>
<div class="wrap">
    <h1>Editar ficha técnica</h1>

    <?php if ($mensaje_ok): ?>
        <div class="msg-ok"><?php echo htmlspecialchars($mensaje_ok); ?></div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="msg-err"><?php echo htmlspecialchars($mensaje_err); ?></div>
    <?php endif; ?>

    <!-- PANEL SUPERIOR: EDICIÓN DE ARTÍCULO -->
    <div class="card">
        <?php if ($articulo): ?>
            <form method="post" id="fichaForm">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id_articulo" value="<?php echo (int)$articulo['id']; ?>">

                <div class="top-grid">
                    <div>
                        <div class="top-info">
                            <strong>ID:</strong> <?php echo (int)$articulo['id']; ?> &nbsp;|&nbsp;
                            <strong>Clave:</strong> <?php echo htmlspecialchars($articulo['cve_articulo']); ?> <br>
                            <strong>Descripción:</strong> <?php echo htmlspecialchars($articulo['des_articulo']); ?><br>
                            <?php if (!empty($articulo['ecommerce_categoria'])): ?>
                                <strong>Categoría:</strong> <?php echo htmlspecialchars($articulo['ecommerce_categoria']); ?>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top:8px;">
                            <label>Ficha técnica (HTML permitido)</label>
                            <textarea name="des_detallada" id="des_detallada"
                                      placeholder="<table class=&quot;ft-grid&quot;>..."><?php
                                echo htmlspecialchars($articulo['des_detallada'] ?? '');
                            ?></textarea>
                            <div class="help-text">
                                Puedes usar una tabla tipo grilla, por ejemplo:<br>
                                <code>&lt;table class="ft-grid"&gt;&lt;tr&gt;&lt;th&gt;Propiedad&lt;/th&gt;&lt;th&gt;Valor&lt;/th&gt;&lt;/tr&gt;...&lt;/table&gt;</code>
                            </div>
                        </div>

                        <div style="margin-top:8px;">
                            <label>Tags (separados por coma)</label>
                            <input type="text" name="ecommerce_tags"
                                   value="<?php echo htmlspecialchars($articulo['ecommerce_tags'] ?? ''); ?>"
                                   placeholder="ALMOHADA, SERIE:A, CONFORT">
                        </div>
                    </div>

                    <div class="top-img">
                        <div style="font-weight:bold;margin-bottom:4px;">Vista previa imagen</div>
                        <img src="<?php echo htmlspecialchars(imgUrlFicha($articulo['ecommerce_img_principal'] ?? '')); ?>"
                             alt="Imagen artículo">
                    </div>
                </div>

                <div style="margin-top:10px;text-align:right">
                    <button type="button" class="btn btn-light"
                            onclick="insertarPlantilla()">Insertar plantilla grilla</button>
                    <button type="submit" class="btn">Guardar ficha técnica</button>
                    <a href="editar_ficha.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn-light">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <div style="font-size:11px;color:#666;">
                Seleccione un artículo de la lista inferior para editar su ficha técnica.
            </div>
        <?php endif; ?>
    </div>

    <!-- LISTA DE ARTÍCULOS E-COMMERCE -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <div style="font-weight:bold;font-size:11px;">Artículos e-commerce</div>
            <div class="filtros-lista">
                <a href="editar_ficha.php?filtro=SIN" class="btn btn-light btn-small"
                   style="<?php echo $filtro==='SIN'?'background:#0F5AAD;color:#fff;':''; ?>">
                    Solo sin ficha
                </a>
                <a href="editar_ficha.php?filtro=TODOS" class="btn btn-light btn-small"
                   style="<?php echo $filtro==='TODOS'?'background:#0F5AAD;color:#fff;':''; ?>">
                    Todos
                </a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:110px;">Clave</th>
                    <th>Descripción</th>
                    <th style="width:120px;">Categoría</th>
                    <th style="width:60px;">Ficha</th>
                    <th style="width:90px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$articulos): ?>
                <tr>
                    <td colspan="6" style="color:#888;">No hay artículos que cumplan el filtro.</td>
                </tr>
            <?php else: foreach ($articulos as $row):
                $tiene_ficha = !empty($row['des_detallada']);
            ?>
                <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['cve_articulo'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['des_articulo'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['ecommerce_categoria'] ?? ''); ?></td>
                    <td>
                        <?php if ($tiene_ficha): ?>
                            <span class="badge si">✓</span>
                        <?php else: ?>
                            <span class="badge no">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="editar_ficha.php?id=<?php echo (int)$row['id']; ?>&filtro=<?php echo urlencode($filtro); ?>"
                           class="btn btn-light btn-small">
                            Administrar
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Inserta rápidamente una plantilla de tabla tipo grilla
function insertarPlantilla() {
    var ta = document.getElementById('des_detallada');
    if (!ta) return;
    if (ta.value.trim() !== '') {
        if (!confirm('La ficha ya tiene contenido.\n¿Deseas reemplazarla con la plantilla de grilla?')) {
            return;
        }
    }
    var tpl =
'<table class="ft-grid">\n' +
'  <tr><th>Propiedad</th><th>Valor</th></tr>\n' +
'  <tr><td>Material</td><td>Ejemplo</td></tr>\n' +
'  <tr><td>Color</td><td>Ejemplo</td></tr>\n' +
'  <tr><td>Medidas</td><td>Ejemplo</td></tr>\n' +
'</table>';
    ta.value = tpl;
    ta.focus();
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
