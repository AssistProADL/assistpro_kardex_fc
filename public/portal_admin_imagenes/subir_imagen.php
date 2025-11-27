<?php
// /public/portal_admin_imagenes/subir_imagen.php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

$id    = intval($_GET['id'] ?? 0);
$q     = trim($_GET['q'] ?? '');
$page  = max(1, intval($_GET['page'] ?? 1));
$perPage = 25; // registros por página
$art   = null;

if ($id > 0) {
    $art = db_one("SELECT * FROM c_articulo WHERE id = :id", [":id" => $id]);
}

// Ruta base para uploads
$base_upload_dir = __DIR__ . "/../../uploads/articulos/";
if (!is_dir($base_upload_dir)) {
    mkdir($base_upload_dir, 0777, true);
}

// -------- función para redimensionar --------
function guardar_redimensionada($file_tmp, $destino, $w = 640, $h = 480) {
    list($ancho, $alto) = getimagesize($file_tmp);
    $origen = imagecreatefromstring(file_get_contents($file_tmp));
    $lienzo = imagecreatetruecolor($w, $h);
    imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $w, $h, $ancho, $alto);
    imagejpeg($lienzo, $destino, 90);
    imagedestroy($origen);
    imagedestroy($lienzo);
}

$msg = '';

// -------- procesar subida si hay artículo válido --------
if ($art && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $upload_dir = $base_upload_dir . $art['id'] . "/";
    $url_base   = "/uploads/articulos/" . $art['id'] . "/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Imagen principal
    if (!empty($_FILES['img_principal']['tmp_name'])) {
        $destino = $upload_dir . "img_principal.jpg";
        guardar_redimensionada($_FILES['img_principal']['tmp_name'], $destino);
        db_q(
            "UPDATE c_articulo SET ecommerce_img_principal = :u WHERE id = :id",
            [':u' => $url_base . "img_principal.jpg", ':id' => $art['id']]
        );
        $msg .= "Imagen principal actualizada. ";
    }

    // Galería
    if (!empty($_FILES['galeria']['tmp_name'][0])) {
        $gal = [];
        foreach ($_FILES['galeria']['tmp_name'] as $idx => $tmp) {
            if (!$tmp) continue;
            $destino = $upload_dir . "galeria_" . ($idx + 1) . ".jpg";
            guardar_redimensionada($tmp, $destino);
            $gal[] = $url_base . "galeria_" . ($idx + 1) . ".jpg";
        }
        if ($gal) {
            db_q(
                "UPDATE c_articulo SET ecommerce_img_galeria = :g WHERE id = :id",
                [':g' => json_encode($gal), ':id' => $art['id']]
            );
            $msg .= "Galería actualizada.";
        }
    }

    // refrescar artículo
    $art = db_one("SELECT * FROM c_articulo WHERE id = :id", [":id" => $art['id']]);
}

/* ============================================================
 *  LISTA DE ARTÍCULOS (cuando NO hay id seleccionado)
 *  Solo ecommerce_activo = 1, con filtro, paginador y totales
 * ============================================================ */
if (!$art) {

    $where  = ["IFNULL(ecommerce_activo,0) = 1"];
    $params = [];

    if ($q !== '') {
        $where[]        = "(cve_articulo LIKE :q OR des_articulo LIKE :q)";
        $params[':q']   = "%{$q}%";
    }

    $whereSql = implode(' AND ', $where);

    // total de registros
    $sqlCount = "SELECT COUNT(*) AS total FROM c_articulo WHERE $whereSql";
    $rowCount = db_one($sqlCount, $params);
    $totalReg = intval($rowCount['total'] ?? 0);

    $totalPages = max(1, (int)ceil($totalReg / $perPage));
    if ($page > $totalPages) $page = $totalPages;

    $offset = ($page - 1) * $perPage;

    // datos de página
    $sql = "
        SELECT id, cve_articulo, des_articulo, ecommerce_categoria
        FROM c_articulo
        WHERE $whereSql
        ORDER BY des_articulo
        LIMIT $offset, $perPage;
    ";
    $articulos = db_all($sql, $params);

    // resumen por categoría (grupos)
    $sqlCat = "
        SELECT IFNULL(ecommerce_categoria,'(SIN CATEGORÍA)') AS categoria,
               COUNT(*) AS total
        FROM c_articulo
        WHERE $whereSql
        GROUP BY IFNULL(ecommerce_categoria,'(SIN CATEGORÍA)')
        ORDER BY categoria;
    ";
    $resumenCat = db_all($sqlCat, $params);

    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Seleccionar artículo para administrar imágenes</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            .wrap     { padding: 14px 18px; }
            .card     { background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.08); padding:12px 14px; margin-bottom:10px; }
            .titulo   { font-size:14px; color:#0F5AAD; margin:0 0 4px; }
            .sub      { font-size:10px; color:#555; margin:0 0 8px; }
            .filtros  { display:flex; gap:8px; align-items:center; margin-bottom:10px; flex-wrap:wrap; }
            .filtros input { font-size:10px; padding:4px 6px; width:220px; }
            .btn      { background:#0F5AAD; color:#fff; border:0; border-radius:6px; padding:4px 10px; font-size:10px; cursor:pointer; }
            .btn.sec  { background:#fff; color:#0F5AAD; border:1px solid #0F5AAD; }
            .btn.sec:hover { background:#0F5AAD; color:#fff; }
            table     { border-collapse: collapse; width:100%; background:#fff; }
            th, td    { border:1px solid #ddd; padding:6px 8px; font-size:10px; }
            th        { background:#f5f7fb; text-align:left; }
            a.btn-link{ padding:4px 8px; background:#0F5AAD; color:#fff; border-radius:6px; text-decoration:none; font-size:10px; display:inline-block; text-align:center; }
            a.btn-link:hover { background:#0b4687; }
            .no-reg   { padding:8px; background:#fff3cd; border:1px solid #ffeeba; border-radius:6px; font-size:10px; margin-top:6px; }
            .resumen  { font-size:10px; margin-bottom:6px; }
            .badge    { display:inline-block; background:#e8f0fe; color:#0F5AAD; border-radius:8px; padding:2px 6px; margin:1px 4px 1px 0; }
            .paginador{ margin-top:8px; display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
            .page-link{ padding:3px 7px; border-radius:6px; border:1px solid #ccc; font-size:10px; text-decoration:none; color:#333; }
            .page-link.act { background:#0F5AAD; color:#fff; border-color:#0F5AAD; }
        </style>
    </head>
    <body>
    <div class="wrap">
        <div class="card">
            <h2 class="titulo">Seleccionar artículo para administrar imágenes</h2>
            <p class="sub">
                Solo se muestran artículos marcados como <b>E-Commerce</b> (<code>ecommerce_activo = 1</code>).<br>
                Total catálogo e-commerce: <b><?= $totalReg ?></b> artículo(s).
            </p>

            <form method="get" class="filtros">
                <input type="text" name="q" placeholder="Buscar por clave o descripción" value="<?= htmlspecialchars($q) ?>">
                <button class="btn" type="submit">Buscar</button>
                <button class="btn sec" type="button" onclick="window.location.href='subir_imagen.php'">Limpiar</button>
            </form>

            <?php if ($resumenCat): ?>
                <div class="resumen">
                    <b>Grupos / Categorías:</b>
                    <?php foreach ($resumenCat as $rc): ?>
                        <span class="badge">
                            <?= htmlspecialchars($rc['categoria']) ?>: <?= (int)$rc['total'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$articulos): ?>
                <div class="no-reg">
                    No se encontraron artículos con los criterios actuales.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:160px;">Clave / Artículo</th>
                        <th>Descripción</th>
                        <th style="width:140px;">Categoría E-Commerce</th>
                        <th style="width:90px;">Acción</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($articulos as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['id']) ?></td>
                            <td><?= htmlspecialchars($a['cve_articulo']) ?></td>
                            <td><?= htmlspecialchars($a['des_articulo']) ?></td>
                            <td><?= htmlspecialchars($a['ecommerce_categoria'] ?? '') ?></td>
                            <td>
                                <a class="btn-link" href="subir_imagen.php?id=<?= urlencode($a['id']) ?>">Imágenes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="paginador">
                    <span>Mostrando <?= count($articulos) ?> de <?= $totalReg ?> &nbsp;|&nbsp; Página <?= $page ?> de <?= $totalPages ?></span>
                    <?php
                    // links de paginador simples
                    for ($p = 1; $p <= $totalPages; $p++) {
                        $paramsUrl = ['page' => $p];
                        if ($q !== '') $paramsUrl['q'] = $q;
                        $url = 'subir_imagen.php?' . http_build_query($paramsUrl);
                        $cls = 'page-link' . ($p == $page ? ' act' : '');
                        echo '<a class="'.$cls.'" href="'.$url.'">'.$p.'</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

/* ============================================================
 *  VISTA DETALLE (cuando sí hay artículo seleccionado)
 * ============================================================ */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Subir imágenes artículo</title>
<style>
body { font-family: Arial, sans-serif; font-size: 10px; }
.wrap { padding:14px 18px; }
.card { background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.08); padding:14px 16px; margin-bottom:16px; }
.titulo { font-size:14px; color:#0F5AAD; margin:0 0 6px; }
h3 { margin:0 0 8px; font-size:12px; color:#0F5AAD; }
.info { margin-bottom:8px; color:#555; }
.btn  { background:#0F5AAD; padding:6px 10px; color:#fff; border:0; border-radius:6px; cursor:pointer; font-size:10px; }
.btn:hover { background:#0b4687; }
img.preview { max-width:260px; max-height:200px; border-radius:8px; display:block; margin-top:8px; }
.msg { background:#e6f4ff; border-left:4px solid #0F5AAD; padding:6px 8px; margin-bottom:12px; font-size:10px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h2 class="titulo">Imágenes para artículo</h2>
        <div class="info">
            <b>ID:</b> <?= htmlspecialchars($art['id']) ?> &nbsp; | &nbsp;
            <b>Clave:</b> <?= htmlspecialchars($art['cve_articulo']) ?> <br>
            <b>Descripción:</b> <?= htmlspecialchars($art['des_articulo']) ?>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Imagen principal (640×480)</h3>
            <?php if (!empty($art['ecommerce_img_principal'])): ?>
                <div>Actual:</div>
                <img class="preview" src="<?= htmlspecialchars($art['ecommerce_img_principal']) ?>" alt="Principal">
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="img_principal" accept="image/*" required>
                <br><br>
                <button class="btn">Subir imagen principal</button>
            </form>
        </div>

        <div class="card">
            <h3>Galería (varias imágenes, se redimensionan a 640×480)</h3>
            <?php
            $gal_actual = [];
            if (!empty($art['ecommerce_img_galeria'])) {
                $str = $art['ecommerce_img_galeria'];
                if (trim($str) !== '') {
                    if (trim($str)[0] == '[') {
                        $gal_actual = json_decode($str, true) ?: [];
                    } else {
                        $gal_actual = array_filter(array_map('trim', explode(',', $str)));
                    }
                }
            }
            ?>
            <?php if ($gal_actual): ?>
                <div>Galería actual:</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
                    <?php foreach ($gal_actual as $u): ?>
                        <img class="preview" src="<?= htmlspecialchars($u) ?>" style="width:110px;height:90px;object-fit:cover;">
                    <?php endforeach; ?>
                </div>
                <br>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="galeria[]" accept="image/*" multiple required>
                <br><br>
                <button class="btn">Subir imágenes de galería</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
