<?php
require_once __DIR__ . '/../../app/db.php';

$activeSection = 'procesos';
$activeItem = 'rtm';
$pageTitle = 'RTM · Ready To Move';
include __DIR__ . '/../bi/_menu_global.php';

$almacen_sel = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';

$cat_almac = db_all("
  SELECT id, clave
  FROM c_almacenp
  WHERE COALESCE(Activo,1)=1
  ORDER BY clave
");

function render_options_almacen(array $rows, string $selected = ''): string {
  $html = '<option value="">Seleccione</option>';
  foreach ($rows as $r) {
    $clave = trim($r['clave']);
    $sel = ($selected !== '' && $selected === $clave) ? ' selected' : '';
    $html .= '<option value="'.htmlspecialchars($clave).'"'.$sel.'>'.htmlspecialchars($clave).'</option>';
  }
  return $html;
}

// RTM: folios con pendiente (CantidadRecibida - CantidadUbicada > 0)
$rows = [];
$error_msg = '';

if ($almacen_sel !== '') {
  try {
    $rows = db_all("
      SELECT
        th.Fol_Folio AS folio,
        th.tipo      AS tipo_doc,
        th.Fol_OEP   AS oc,
        th.Fact_Prov AS factura,
        th.Proveedor AS proveedor,
        th.Proyecto  AS proyecto,
        th.ID_Protocolo,
        th.Consec_protocolo,
        th.Fec_Entrada AS fecha_entrada,
        COUNT(td.id) AS partidas,
        SUM(COALESCE(td.CantidadRecibida,0)) AS total_recibido,
        SUM(COALESCE(td.CantidadUbicada,0))  AS total_acomodado,
        SUM(GREATEST(COALESCE(td.CantidadRecibida,0) - COALESCE(td.CantidadUbicada,0),0)) AS pendiente,
        ROUND(
          (SUM(COALESCE(td.CantidadUbicada,0)) / NULLIF(SUM(COALESCE(td.CantidadRecibida,0)),0))*100
        ,2) AS avance
      FROM th_entalmacen th
      INNER JOIN td_entalmacen td
        ON td.fol_folio = th.Fol_Folio
      WHERE TRIM(th.Cve_Almac)=TRIM(:alm)
      GROUP BY
        th.Fol_Folio, th.tipo, th.Fol_OEP, th.Fact_Prov, th.Proveedor, th.Proyecto,
        th.ID_Protocolo, th.Consec_protocolo, th.Fec_Entrada
      HAVING pendiente > 0
      ORDER BY th.Fol_Folio DESC
      LIMIT 500
    ", [':alm'=>$almacen_sel]);

  } catch(Exception $e) {
    $error_msg = $e->getMessage();
    $rows = [];
  }
}
?>

<style>
  .ap-page{font-size:10px;padding:12px}
  .ap-title{color:#0b5ed7;font-weight:800;margin:6px 0 10px 0}
  .ap-card{background:#fff;border:1px solid #d0d7e2;border-radius:12px;padding:10px;margin-bottom:10px}
  table.dataTable tbody td{font-size:10px}
  .btn-xs{padding:2px 8px;font-size:10px}
</style>

<div class="container-fluid ap-page">

  <h2 class="ap-title"><i class="fa-solid fa-dolly me-2"></i>RTM · Ready To Move</h2>

  <?php if($error_msg!==''): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <div class="ap-card">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label mb-0">Almacén</label>
        <select name="almacen" class="form-select form-select-sm" onchange="this.form.submit()">
          <?= render_options_almacen($cat_almac, $almacen_sel) ?>
        </select>
      </div>
      <div class="col-md-8 text-end">
        <small class="text-muted">RTM muestra ingresos con pendiente de acomodo; al 100% desaparece.</small>
      </div>
    </form>
  </div>

  <div class="ap-card">
    <div class="table-responsive">
      <table id="tblRTM" class="table table-sm table-bordered table-striped text-center align-middle" style="width:100%;">
        <thead style="background:#eef3ff;">
          <tr>
            <th style="min-width:150px;">Acciones</th>
            <th>Folio</th>
            <th>Tipo</th>
            <th>OC</th>
            <th>Factura</th>
            <th>Proveedor</th>
            <th>Proyecto</th>
            <th>Protocolo</th>
            <th>Partidas</th>
            <th>Recibido</th>
            <th>Acomodado</th>
            <th>Pendiente</th>
            <th>Avance %</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <?php
              // Enviamos a putaway_acomodo con almacén + folio; la zona se selecciona allá
              $urlAcom = '../putaway/putaway_acomodo.php?modo=ACOMODO'
                       . '&almacen='.urlencode($almacen_sel)
                       . '&folio_sel='.urlencode($r['folio']);
            ?>
            <tr>
              <td class="text-center">
                <a class="btn btn-outline-primary btn-xs" href="<?= htmlspecialchars($urlAcom) ?>">
                  <i class="fa-solid fa-boxes-stacked me-1"></i>Acomodar
                </a>
              </td>
              <td><span class="badge text-bg-primary"><?= (int)$r['folio'] ?></span></td>
              <td><?= htmlspecialchars($r['tipo_doc'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['oc'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['factura'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['proveedor'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['proyecto'] ?? '') ?></td>
              <td><?= htmlspecialchars(($r['ID_Protocolo'] ?? '').(($r['Consec_protocolo']??'')!==''?('-'.$r['Consec_protocolo']):'')) ?></td>
              <td class="text-end"><?= (int)$r['partidas'] ?></td>
              <td class="text-end"><?= number_format((float)$r['total_recibido'],2) ?></td>
              <td class="text-end"><?= number_format((float)$r['total_acomodado'],2) ?></td>
              <td class="text-end"><span class="badge text-bg-warning"><?= number_format((float)$r['pendiente'],2) ?></span></td>
              <td class="text-end"><?= number_format((float)$r['avance'],2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  $('#tblRTM').DataTable({
    pageLength: 25,
    lengthChange:false,
    searching:true,
    ordering:true,
    info:true,
    scrollX:true,
    scrollY:'58vh',
    scrollCollapse:true,
    language:{ url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
  });
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
