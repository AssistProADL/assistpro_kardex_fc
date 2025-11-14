<?php
// public/dashboard/adm_inventarios_det.php
// Detalle de inventario físico por folio, con Conteo 1/2/3 pivotado

require_once __DIR__ . '/../../app/db.php';

$folio = isset($_GET['folio']) ? (int)$_GET['folio'] : 0;
if ($folio <= 0) {
    die('Folio inválido');
}

/* Datos del encabezado para mostrar título */
$enc = db_all("
    SELECT th.ID_Inventario, th.Nombre, th.Fecha, ap.nombre AS almacen, ca.des_almac AS zona
    FROM th_inventario th
    LEFT JOIN c_almacenp ap ON ap.clave = th.cve_almacen
    LEFT JOIN c_almacen  ca ON ca.cve_almac = th.cve_zona
    WHERE th.ID_Inventario = :fol
    LIMIT 1
", [':fol' => $folio]);
$encabezado = $enc ? $enc[0] : null;

/* Pivot de conteos 1/2/3 */
$sql = "
SELECT
  d.idy_ubica,
  d.cve_articulo,
  d.cve_lote,

  SUM(CASE WHEN d.NConteo=1 THEN d.Cantidad           ELSE 0 END) AS c1_cant,
  SUM(CASE WHEN d.NConteo=1 THEN d.ExistenciaTeorica   ELSE 0 END) AS c1_teo,
  MAX(CASE WHEN d.NConteo=1 THEN u.nombre_completo     END)        AS c1_usuario,
  SUM(CASE WHEN d.NConteo=1 THEN (d.Cantidad - d.ExistenciaTeorica) ELSE 0 END) AS c1_dif,

  SUM(CASE WHEN d.NConteo=2 THEN d.Cantidad           ELSE 0 END) AS c2_cant,
  SUM(CASE WHEN d.NConteo=2 THEN d.ExistenciaTeorica   ELSE 0 END) AS c2_teo,
  MAX(CASE WHEN d.NConteo=2 THEN u.nombre_completo     END)        AS c2_usuario,
  SUM(CASE WHEN d.NConteo=2 THEN (d.Cantidad - d.ExistenciaTeorica) ELSE 0 END) AS c2_dif,

  SUM(CASE WHEN d.NConteo=3 THEN d.Cantidad           ELSE 0 END) AS c3_cant,
  SUM(CASE WHEN d.NConteo=3 THEN d.ExistenciaTeorica   ELSE 0 END) AS c3_teo,
  MAX(CASE WHEN d.NConteo=3 THEN u.nombre_completo     END)        AS c3_usuario,
  SUM(CASE WHEN d.NConteo=3 THEN (d.Cantidad - d.ExistenciaTeorica) ELSE 0 END) AS c3_dif

FROM t_invpiezas d
LEFT JOIN c_usuario u ON u.cve_usuario = d.cve_usuario
WHERE d.ID_Inventario = :fol
GROUP BY d.idy_ubica, d.cve_articulo, d.cve_lote
ORDER BY d.idy_ubica, d.cve_articulo, d.cve_lote
LIMIT 5000
";

$rows = db_all($sql, [':fol' => $folio]);

/* KPIs simples para cards */
$lineas = count($rows);
$pzas_cont_tot = 0;
$diferencia_tot = 0;
foreach ($rows as $r) {
    $pzas_cont_tot += (float)$r['c3_cant'] ?: ((float)$r['c2_cant'] ?: (float)$r['c1_cant']);
    $diferencia_tot += (float)$r['c3_dif'] ?: ((float)$r['c2_dif'] ?: (float)$r['c1_dif']);
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3" style="font-size:10px;">
  <div class="row mb-2">
    <div class="col">
      <h5 class="mb-0">
        Detalle Inventario Físico <?= htmlspecialchars($folio) ?>
        <?php if ($encabezado): ?>
          – <?= htmlspecialchars($encabezado['Nombre']) ?> (<?= htmlspecialchars($encabezado['almacen']) ?> / <?= htmlspecialchars($encabezado['zona']) ?>)
        <?php endif; ?>
      </h5>
      <small class="text-muted">Vista pivotada por Conteo 1 / 2 / 3.</small>
    </div>
  </div>

  <!-- Cards -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">Líneas (Ubic-Articulo-Lote)</div>
        <div class="h6 m-0"><?= number_format($lineas) ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">Pzas Contadas (último conteo)</div>
        <div class="h6 m-0"><?= number_format($pzas_cont_tot) ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">Dif. total (último conteo)</div>
        <div class="h6 m-0"><?= number_format($diferencia_tot) ?></div>
      </div></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table id="tblDet" class="table table-sm table-striped table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>Ubicación</th><th>Artículo</th><th>Lote</th>
              <th>C1 Cant</th><th>C1 Teo</th><th>C1 Dif</th><th>C1 Usuario</th>
              <th>C2 Cant</th><th>C2 Teo</th><th>C2 Dif</th><th>C2 Usuario</th>
              <th>C3 Cant</th><th>C3 Teo</th><th>C3 Dif</th><th>C3 Usuario</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['idy_ubica']) ?></td>
              <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
              <td><?= htmlspecialchars($r['cve_lote']) ?></td>

              <td class="text-end"><?= number_format((float)$r['c1_cant']) ?></td>
              <td class="text-end"><?= number_format((float)$r['c1_teo']) ?></td>
              <td class="text-end"><?= number_format((float)$r['c1_dif']) ?></td>
              <td><?= htmlspecialchars($r['c1_usuario']) ?></td>

              <td class="text-end"><?= number_format((float)$r['c2_cant']) ?></td>
              <td class="text-end"><?= number_format((float)$r['c2_teo']) ?></td>
              <td class="text-end"><?= number_format((float)$r['c2_dif']) ?></td>
              <td><?= htmlspecialchars($r['c2_usuario']) ?></td>

              <td class="text-end"><?= number_format((float)$r['c3_cant']) ?></td>
              <td class="text-end"><?= number_format((float)$r['c3_teo']) ?></td>
              <td class="text-end"><?= number_format((float)$r['c3_dif']) ?></td>
              <td><?= htmlspecialchars($r['c3_usuario']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (window.jQuery && $.fn.DataTable) {
    $('#tblDet').DataTable({
      pageLength: 25,
      lengthChange: false,
      ordering: true,
      scrollX: true,
      language: { url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
  }
});
</script>
