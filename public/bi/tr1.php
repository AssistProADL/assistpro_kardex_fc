
<?php
// =======================================
//  Test_Repremundo Dashboard (FIX)
//  Fuente: assistpro_etl_fc
//  Tablas: c_almacenp, c_usuario, c_articulo
// =======================================
include_once(__DIR__ . '/../../app/db.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Test Repremundo – AssistPro ETL FC</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
  body { background-color:#f8f9fa; font-size: 13px; }
  .card-summary { border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
  .card-title { font-size: 13px; color: #666; margin-bottom: 0; }
  .card-value { font-size: 22px; font-weight: bold; color: #0F5AAD; }
  .datatable-container { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style>
</head>
<body class="p-3">

<?php
// ===== CONSULTAS DE TOTALES =====
$total_almac    = db_val("SELECT COUNT(*) FROM c_almacenp");
$total_usuarios = db_val("SELECT COUNT(*) FROM c_usuario");
$total_art      = db_val("SELECT COUNT(*) FROM c_articulo"); // <-- antes c_producto
?>

<div class="container-fluid">
  <h4 class="mb-4 text-primary">📊 Test Repremundo – Resumen General</h4>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card card-summary text-center">
        <div class="card-body">
          <div class="card-title">Total de Almacenes</div>
          <div class="card-value"><?= number_format($total_almac) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-summary text-center">
        <div class="card-body">
          <div class="card-title">Total de Usuarios</div>
          <div class="card-value"><?= number_format($total_usuarios) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-summary text-center">
        <div class="card-body">
          <div class="card-title">Total de Artículos</div>
          <div class="card-value"><?= number_format($total_art) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="datatable-container">
    <h6 class="text-secondary mb-3">Detalle de Artículos</h6>
    <table id="tablaDatos" class="table table-sm table-bordered table-striped w-100">
      <thead class="table-light">
        <tr>
          <th>Clave</th>
          <th>Descripción</th>
          <th>Grupo</th>
          <th>Clasificación</th>
          <th>Tipo</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Campos existentes en c_articulo: cve_articulo, des_articulo, grupo, clasificacion, tipo, Activo
        // (según tu dump).  :contentReference[oaicite:1]{index=1}
        $articulos = db_all("
          SELECT 
            cve_articulo,
            des_articulo,
            COALESCE(grupo,'')          AS grupo,
            COALESCE(clasificacion,'')  AS clasificacion,
            COALESCE(tipo,'')           AS tipo,
            COALESCE(Activo,'')         AS Activo
          FROM c_articulo
          ORDER BY des_articulo
          LIMIT 200
        ");
        foreach ($articulos as $a) {
          echo '<tr>';
          echo '<td>'.htmlspecialchars($a['cve_articulo']).'</td>';
          echo '<td>'.htmlspecialchars($a['des_articulo']).'</td>';
          echo '<td>'.htmlspecialchars($a['grupo']).'</td>';
          echo '<td>'.htmlspecialchars($a['clasificacion']).'</td>';
          echo '<td>'.htmlspecialchars($a['tipo']).'</td>';
          echo '<td>'.(($a['Activo']==='1' || strtoupper($a['Activo'])==='S') ? '✔️' : '❌').'</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  $('#tablaDatos').DataTable({
    pageLength: 10,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
  });
});
</script>

</body>
</html>
