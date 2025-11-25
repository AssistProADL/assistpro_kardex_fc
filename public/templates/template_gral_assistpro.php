<?php
// public/templates/template_gral_assistpro.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

// menú global corporativo
$menu_path = __DIR__ . '/../bi/_menu_global.php';
if (file_exists($menu_path)) {
    require_once $menu_path;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Template Gral AssistPro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            font-size: 12px;
        }
        .ap-main {
            padding: 8px 10px;
        }
        .ap-panel {
            background: #ffffff;
            border-radius: .5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            padding: 8px;
        }
        .ap-panel-header {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .ap-grid-placeholder {
            border: 1px dashed #ccc;
            min-height: 200px;
            padding: 6px;
            font-size: 11px;
            color: #777;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid ap-main">

    <!-- Filtros generales (nuevo partial) -->
    <?php
    require_once __DIR__ . '/../partials/filtros_assistpro.php';
    ?>

    <!-- Panel central de trabajo (cada página reutiliza este frame) -->
    <div class="ap-panel mt-2">
        <div class="ap-panel-header">
            Vista / reporte asociado al template general
        </div>

        <div class="ap-grid-placeholder">
            Aquí va la grilla / contenido específico de cada proceso
            (existencias por ubicación, OC, alta pedidos, reabasto, etc.)
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// cierre de menú global, si existe
$menu_end = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menu_end)) {
    require_once $menu_end;
}
?>
