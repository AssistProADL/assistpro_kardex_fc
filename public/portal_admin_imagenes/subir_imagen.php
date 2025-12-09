<?php
// /public/portal_admin_imagenes/subir_imagen.php
require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro    = $_GET['filtro'] ?? 'SIN'; // SIN = solo sin imagen, TODOS = todos
$articulo  = null;
$mensaje_ok  = '';
$mensaje_err = '';

function normalizarGaleria($cadena) {
    if (!$cadena) return [];
    $parts = explode(',', $cadena);
    $out   = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = $p;
    }
    return $out;
}

// Cargar artículo si hay ID para edición
if ($id > 0) {
    $articulo = db_one("
        SELECT id, cve_articulo, des_articulo,
               ecommerce_img_principal,
               ecommerce_img_galeria
          FROM c_articulo
         WHERE id = :id
         LIMIT 1
    ", [':id' => $id]);
}

// Procesar subida solo si hay artículo válido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $articulo) {

    $accion = $_POST['accion'] ?? '';

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $extPermitidas = ['jpg','jpeg','png','webp'];

    try {

        // Imagen principal
        if ($accion === 'principal') {

            if (!isset($_FILES['imagen_principal']) || $_FILES['imagen_principal']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió archivo o hubo un error al subir la imagen principal.');
            }

            $file = $_FILES['imagen_principal'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $extPermitidas)) {
                throw new Exception('Formato no permitido. Use JPG, PNG o WEBP.');
            }

            $nombreFinal = 'prod_'.$id.'_main_'.time().'.'.$ext;
            $destinoF    = $uploadDir . $nombreFinal;

            if (!move_uploaded_file($file['tmp_name'], $destinoF)) {
                throw new Exception('No se pudo guardar la imagen en el servidor.');
            }

            $rutaRel = 'portal_admin_imagenes/uploads/' . $nombreFinal;

            dbq("
                UPDATE c_articulo
                   SET ecommerce_img_principal = :img
                 WHERE id = :id
            ", [
                ':img' => $rutaRel,
                ':id'  => $id
            ]);

            $articulo['ecommerce_img_principal'] = $rutaRel;
            $mensaje_ok = 'Imagen principal actualizada correctamente.';
        }

        // Galería
        if ($accion === 'galeria') {

            if (!isset($_FILES['galeria']) || !is_array($_FILES['galeria']['name'])) {
                throw new Exception('No se recibieron archivos para la galería.');
            }

            $n = count($_FILES['galeria']['name']);
            if ($n === 0) {
                throw new Exception('No se seleccionó ningún archivo de galería.');
            }

            $existentes = normalizarGaleria($articulo['ecommerce_img_galeria'] ?? '');
            $nuevas     = [];

            for ($i = 0; $i < $n; $i++) {
                if ($_FILES['galeria']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $nombre = $_FILES['galeria']['name'][$i];
                $tmp    = $_FILES['galeria']['tmp_name'][$i];
                $ext    = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

                if (!in_array($ext, $extPermitidas)) {
                    continue;
                }

                $nombreFinal = 'prod_'.$id.'_gal_'.time().'_'.$i.'.'.$ext;
                $destinoF    = $uploadDir . $nombreFinal;

                if (!move_uploaded_file($tmp, $destinoF)) {
                    continue;
                }

                $rutaRel = 'portal_admin_imagenes/uploads/' . $nombreFinal;
                $nuevas[] = $rutaRel;
            }

            if (!count($nuevas)) {
                throw new Exception('No se pudo guardar ninguna imagen de galería.');
            }

            $total  = array_merge($existentes, $nuevas);
            $cadena = implode(',', $total);

            dbq("
                UPDATE c_articulo
                   SET ecommerce_img_galeria = :gal
                 WHERE id = :id
            ", [
                ':gal' => $cadena,
                ':id'  => $id
            ]);

            $articulo['ecommerce_img_galeria'] = $cadena;
            $mensaje_ok = 'Imágenes de galería actualizadas correctamente.';
        }

    } catch (Exception $e) {
        $mensaje_err = $e->getMessage();
    }
}

$galeria = $articulo ? normalizarGaleria($articulo['ecommerce_img_galeria'] ?? '') : [];

// Listado de artículos e-commerce para mantenimiento
$where = "IFNULL(Activo,0)=1 AND IFNULL(ecommerce_activo,0)=1";
$params = [];
if ($filtro === 'SIN') {
    $where .= " AND (ecommerce_img_principal IS NULL OR ecommerce_img_principal = '')";
}
$lista = db_all("
    SELECT id, cve_articulo, des_articulo,
           ecommerce_categoria,
           ecommerce_img_principal
      FROM c_articulo
     WHERE $where
     ORDER BY ecommerce_categoria, des_articulo
     LIMIT 300
", $params);

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Imágenes de catálogo e-commerce</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { font-family: Arial, sans-serif; font-size: 10px; }
  .wrap { padding: 14px 18px; }
  h1 { font-size: 16px; margin: 0 0 10px; color:#0F5AAD; }
  .card-box {
    background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.12);
    padding:12px 16px; margin-bottom:14px;
  }
  .subtitulo { font-size:12px; font-weight:bold; margin-bottom:8px; }
  .detalle-art { margin-bottom:10px; font-size:11px; }
  .detalle-art span.label { font-weight:bold; color:#555; }
  .mensaje-ok {
    background:#e6ffed; border:1px solid #2ecc71; color:#1d7a3c;
    padding:6px 10px; border-radius:6px; margin-bottom:10px; font-size:10px;
  }
  .mensaje-err {
    background:#ffe6e6; border:1px solid #e74c3c; color:#b1271b;
    padding:6px 10px; border-radius:6px; margin-bottom:10px; font-size:10px;
  }
  .btn {
    display:inline-block; font-size:10px; padding:4px 8px; border-radius:6px;
    border:1px solid #0F5AAD; background:#0F5AAD; color:#fff; cursor:pointer;
  }
  .btn-light {
    background:#fff; color:#0F5AAD;
  }
  .img-preview { max-width:160px; max-height:120px; border-radius:8px; border:1px solid #ddd; background:#fafafa; }
  .galeria-grid { display:flex; flex-wrap:wrap; gap:10px; margin-top:6px; }

  table { width:100%; border-collapse:collapse; font-size:10px; }
  th, td { padding:4px 6px; border-bottom:1px solid #eee; text-align:left; }
  th { background:#f5f7fb; font-weight:bold; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Imágenes de catálogo e-commerce</h1>

  <?php if ($articulo): ?>
    <div class="card-box">
      <div class="subtitulo">Mantenimiento de imágenes para artículo</div>

      <div class="detalle-art">
        <div><span class="label">ID:</span> <?php echo (int)$articulo['id']; ?></div>
        <div><span class="label">Clave:</span> <?php echo htmlspecialchars($articulo['cve_articulo'] ?? ''); ?></div>
        <div><span class="label">Descripción:</span> <?php echo htmlspecialchars($articulo['des_articulo'] ?? ''); ?></div>
      </div>

      <?php if ($mensaje_ok): ?>
        <div class="mensaje-ok"><?php echo htmlspecialchars($mensaje_ok); ?></div>
      <?php endif; ?>
      <?php if ($mensaje_err): ?>
        <div class="mensaje-err"><?php echo htmlspecialchars($mensaje_err); ?></div>
      <?php endif; ?>

      <!-- Imagen principal + galería -->
      <div style="display:flex; gap:24px; flex-wrap:wrap;">
        <div>
          <div class="subtitulo" style="margin-bottom:4px;">Imagen principal (640×480)</div>
          <div style="margin-bottom:8px;">
            <strong>Actual:</strong><br>
            <?php if (!empty($articulo['ecommerce_img_principal'])): ?>
              <img src="../<?php echo htmlspecialchars($articulo['ecommerce_img_principal']); ?>" class="img-preview">
            <?php else: ?>
              <span style="font-size:10px;color:#888;">Sin imagen principal</span>
            <?php endif; ?>
          </div>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="principal">
            <input type="file" name="imagen_principal" accept="image/*" required>
            <br><br>
            <button type="submit" class="btn">Subir imagen principal</button>
          </form>
        </div>

        <div>
          <div class="subtitulo" style="margin-bottom:4px;">Galería (opcional)</div>
          <?php if ($galeria): ?>
            <div class="galeria-grid">
              <?php foreach ($galeria as $g): ?>
                <div>
                  <img src="../<?php echo htmlspecialchars($g); ?>" class="img-preview">
                </div>
              <?php endforeach; ?>
            </div>
            <br>
          <?php else: ?>
            <div style="font-size:10px;color:#888;">Sin imágenes en galería.</div><br>
          <?php endif; ?>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="galeria">
            <input type="file" name="galeria[]" accept="image/*" multiple required>
            <br><br>
            <button type="submit" class="btn">Subir imágenes de galería</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Listado de artículos -->
  <div class="card-box">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
      <div class="subtitulo" style="margin-bottom:0;">Artículos e-commerce</div>
      <div>
        <form method="get" style="display:inline;">
          <input type="hidden" name="filtro" value="SIN">
          <button class="btn btn-light" type="submit" <?php echo ($filtro==='SIN'?'style="font-weight:bold;"':''); ?>>
            Solo sin imagen
          </button>
        </form>
        <form method="get" style="display:inline; margin-left:4px;">
          <input type="hidden" name="filtro" value="TODOS">
          <button class="btn btn-light" type="submit" <?php echo ($filtro==='TODOS'?'style="font-weight:bold;"':''); ?>>
            Todos
          </button>
        </form>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Clave</th>
          <th>Descripción</th>
          <th>Categoría</th>
          <th>Imagen</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$lista): ?>
          <tr><td colspan="6" style="font-size:10px; color:#888;">Sin registros.</td></tr>
        <?php else: ?>
          <?php foreach ($lista as $row): ?>
            <tr>
              <td><?php echo (int)$row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['cve_articulo'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row['des_articulo'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row['ecommerce_categoria'] ?? ''); ?></td>
              <td>
                <?php if (!empty($row['ecommerce_img_principal'])): ?>
                  ✔
                <?php else: ?>
                  ✖
                <?php endif; ?>
              </td>
              <td>
                <a class="btn btn-light"
                   href="subir_imagen.php?id=<?php echo (int)$row['id']; ?>&amp;filtro=<?php echo urlencode($filtro); ?>">
                  Administrar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
