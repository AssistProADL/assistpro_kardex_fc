<?php
// =======================================
// Kardex Productividad (iframe-ready)
// =======================================
require_once __DIR__ . '/../../app/db.php';

/* ===== Envolvente (iframe y menú) ===== */
$IFRAME = isset($_GET['iframe']) && ($_GET['iframe']=='1' || strtolower($_GET['iframe'])==='true');

if ($IFRAME) {
  header("Content-Security-Policy: frame-ancestors 'self'");
} else {
  $activeSection = 'dashboard';
  $activeItem    = 'kardex_productividad';
  $pageTitle     = 'Kardex Productividad · AssistPro';
  include __DIR__.'/_menu_global.php';
}

/* ======= TU LÓGICA / CONSULTAS ORIGINALES =======
   Coloca aquí tus consultas PHP existentes de
   kardex_productividad_iframe.php (no las borres).
   Ejemplo:
   $pdo = db();
   // $rows = db_all("SELECT ...");
   ================================================= */
?>

<?php if ($IFRAME): ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= isset($pageTitle)?$pageTitle:'Kardex Productividad' ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- En iframe cargamos dependencias necesarias -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  :root { --b:#ddd; --bg:#f7f7f7; }
  body { background:#fff; font-family: Arial, sans-serif; margin:0; }
  .container-fluid { padding:8px; }
  .ap-card { border:1px solid var(--b); border-radius:12px; background:#fff; padding:10px; }
  .tablewrap { border:1px solid var(--b); border-radius:8px; overflow:auto; height:60vh; }
  table { border-collapse:collapse; width:100%; font-size:10px; min-width:1200px; }
  th, td { border-bottom:1px solid #eee; padding:6px 8px; white-space:nowrap; }
  thead th { position:sticky; top:0; background:var(--bg); z-index:1; }
</style>
</head>
<body>
<?php else: ?>
<!-- Modo layout global: estilos mínimos locales -->
<style>
  :root { --b:#ddd; --bg:#f7f7f7; }
  .container-fluid { padding:12px; }
  .ap-card { border:1px solid var(--b); border-radius:12px; background:#fff; padding:10px; }
  .tablewrap { border:1px solid var(--b); border-radius:8px; overflow:auto; height:60vh; }
  table { border-collapse:collapse; width:100%; font-size:10px; min-width:1200px; }
  th, td { border-bottom:1px solid #eee; padding:6px 8px; white-space:nowrap; }
  thead th { position:sticky; top:0; background:var(--bg); z-index:1; }
</style>
<?php endif; ?>

<div class="container-fluid">
  <div class="ap-card mb-3">
    <h4 style="margin:0;">Kardex Productividad</h4>
  </div>

  <!-- ======= TU HTML / TABLAS ORIGINALES ======= 
       Coloca aquí el contenido existente (grillas, KPIs, etc.)
       Ejemplo básico de tabla (deja tu propia):
  -->
  <div class="ap-card tablewrap">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Módulo</th>
          <th>Operación</th>
          <th>Referencia</th>
          <th>Cantidad</th>
        </tr>
      </thead>
      <tbody>
        <?php /* foreach($rows as $r): */ ?>
        <!-- <tr>
          <td><?= /*htmlspecialchars($r['fecha'])*/ '' ?></td>
          <td><?= /*htmlspecialchars($r['usuario'])*/ '' ?></td>
          <td><?= /*htmlspecialchars($r['modulo'])*/ '' ?></td>
          <td><?= /*htmlspecialchars($r['operacion'])*/ '' ?></td>
          <td><?= /*htmlspecialchars($r['ref'])*/ '' ?></td>
          <td><?= /*number_format($r['qty'],2)*/ '' ?></td>
        </tr> -->
        <?php /* endforeach; */ ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($IFRAME): ?>
<!-- Auto-resize para iframe -->
<script>
(function(){
  function postHeight(){
    var h = document.documentElement.scrollHeight || document.body.scrollHeight;
    try { window.parent.postMessage({ type:'assistpro:resize', feature:'kardex_productividad', height:h }, '*'); } catch(e){}
  }
  var ro = new ResizeObserver(postHeight);
  ro.observe(document.body);
  window.addEventListener('load', postHeight);
})();
</script>
</body>
</html>
<?php else: ?>
<?php include __DIR__.'/_menu_global_end.php'; ?>
<?php endif; ?>
