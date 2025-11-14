<?php
// public/templates/template_gral_assistpro.php

// Conexión (por si se requiere algo en el layout)
require_once __DIR__ . '/../../app/db.php';

// Variables que pueden venir del wrapper
if (!isset($vista_id)) {
    $vista_id = isset($_GET['vista']) ? $_GET['vista'] : '';
}
if (!isset($vista_titulo)) {
    $vista_titulo = 'Template Gral AssistPro';
}
if (!isset($titulo_pagina)) {
    $titulo_pagina = $vista_titulo;
}

// Menú global corporativo (inicio)
$menu_path = __DIR__ . '/../bi/_menu_global.php';
if (file_exists($menu_path)) {
    require_once $menu_path;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($titulo_pagina); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            font-size: 12px;
            background-color: #f4f6f9;
        }
        .assistpro-header {
            background: #0F5AAD;
            color: #fff;
            padding: .5rem 1rem;
            margin-bottom: .75rem;
        }
        .assistpro-header h5 {
            margin: 0;
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="container-fluid py-2">
    <div class="assistpro-header d-flex justify-content-between align-items-center">
        <h5><?php echo htmlspecialchars($vista_titulo); ?></h5>
        <span style="font-size:11px;">Template Gral — AssistPro</span>
    </div>

    <?php
    // Pasamos la receta seleccionada al partial
    $vista_id_default = $vista_id;
    include __DIR__ . '/../partials/filtros_assistpro.php';
    ?>
</div>

<!-- Bootstrap JS (opcional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
// Menú global (cierre) si existe archivo de cierre
$menu_end = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menu_end)) {
    require_once $menu_end;
}
