<?php
// public/portal_admin_imagenes/banners.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

// Carpeta destino
$dirBanners = __DIR__ . '/banners/';
if (!is_dir($dirBanners)) {
    mkdir($dirBanners, 0777, true);
}

// Procesar eliminación
if (isset($_GET['del'])) {
    $file = basename($_GET['del']);
    $path = $dirBanners . $file;

    if (file_exists($path)) {
        unlink($path);
    }

    header("Location: banners.php");
    exit;
}

// Procesar subida
$msg = "";
if (!empty($_FILES['banner']['name'])) {

    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $msg = "Formato NO permitido. Solo JPG, PNG o WEBP.";
    } else {

        $newName = "banner-" . time() . "." . $ext;
        $destino = $dirBanners . $newName;

        if (move_uploaded_file($_FILES['banner']['tmp_name'], $destino)) {
            $msg = "Banner subido correctamente.";
        } else {
            $msg = "Error al subir el banner.";
        }

    }
}

// Obtener lista de banners
$banners = array_values(array_filter(scandir($dirBanners), function($f) {
    return !in_array($f, ['.', '..']);
}));

?>
<style>
.banner-prev {
    width: 260px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-right: 15px;
}
.banner-card {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}
</style>

<div class="container mt-4">

    <h3>Administrar Banners del Catálogo</h3>

    <?php if ($msg): ?>
        <div class="alert alert-info mt-3"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">

                <label class="fw-bold">Subir banner (JPG/PNG/WEBP)</label>

                <p class="text-muted" style="font-size:12px;">
                    Tamaño recomendado: <strong>1200 × 280 px</strong>  
                    &nbsp;•&nbsp;  
                    Peso máximo sugerido: <strong>500 KB</strong>  
                    &nbsp;•&nbsp;  
                    Formatos permitidos: <strong>JPG, PNG, WEBP</strong>
                </p>

                <div class="input-group mb-3">
                    <input type="file" name="banner" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    <button class="btn btn-primary" type="submit">Subir</button>
                </div>

            </form>

        </div>
    </div>

    <h5 class="mt-4">Banners actuales</h5>

    <div class="card mt-2">
        <div class="card-body">

            <?php if (empty($banners)): ?>
                <p class="text-muted">No hay banners cargados.</p>
            <?php else: ?>

                <?php foreach ($banners as $b): ?>
                    <div class="banner-card">
                        <img src="banners/<?= $b ?>" class="banner-prev">
                        <div>
                            <strong><?= $b ?></strong><br>
                            <a href="?del=<?= urlencode($b) ?>" onclick="return confirm('¿Eliminar este banner?');" class="btn btn-danger btn-sm mt-2">Eliminar</a>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
