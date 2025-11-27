<?php
// Seguridad: requiere sesión válida y cve_almac en sesión
require_once __DIR__ . '/../bi/_auth_acl.php';
if (!isset($_SESSION['cve_almac']) || trim($_SESSION['cve_almac'])==='') {
  header('Location: /assistpro_kardex_fc/public/login.php?err=Seleccione%20almac%C3%A9n');
  exit;
}

// Fijamos el almacén para el reporte original SIN modificar su código.
// Muchos reportes leen $_GET/$_REQUEST, por eso seteamos ambas llaves:
$_GET['alm'] = $_REQUEST['alm'] = $_SESSION['cve_almac'];

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Productividad · BI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Fondo blanco garantizado */
    body{ background:#fff; }
    .ap-overlay{ display:none !important; }

    /* Opcional: ocultar cualquier control de selección de almacén
       común en reportes legacy (no alteramos el PHP original). */
    .hide-warehouse{ display:none !important; }
  </style>
</head>
<body>
<?php
// Frame blanco con título centrado
include __DIR__ . '/../bi/_menu_global.php';
?>

  <div class="container-fluid py-0">
    <?php
      // Inyectamos una pequeña salida para ocultar selectores de almacén típicos
      // (select con id/name que contengan 'alm' o label 'Almacén'), pero sin romper layouts.
      echo '<style>
        select[id*="alm"], select[name*="alm"], 
        .form-group:has(> label:contains("Almacén")), 
        label:contains("Almacén") { display:none !important; }
      </style>';
      // Incluimos el REPORTE ORIGINAL SIN CAMBIOS:
      require __DIR__ . '/kardex_productividad.php';
    ?>
  </div>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
