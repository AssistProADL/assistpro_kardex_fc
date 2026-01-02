<?php
require_once __DIR__ . '/../../app/db.php';

$activeSection = 'procesos';
$activeItem = 'putaway_acomodo';
$pageTitle = 'Put Away · Acomodo / Traslado / XD';
include __DIR__ . '/../bi/_menu_global.php';

$cve_usuario_sesion = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? 'SISTEMA');

$almacen_sel = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$zona_recibo_sel = isset($_GET['zona_recibo']) ? trim($_GET['zona_recibo']) : '';
$bl_origen_sel = isset($_GET['bl_origen']) ? trim($_GET['bl_origen']) : '';
$bl_destino_sel = isset($_GET['bl_destino']) ? trim($_GET['bl_destino']) : '';
$zona_embarque_sel = isset($_GET['zona_embarque']) ? trim($_GET['zona_embarque']) : '';
$folio_sel = isset($_GET['folio_sel']) ? trim($_GET['folio_sel']) : '';

$lp_filtro = isset($_GET['lp']) ? trim($_GET['lp']) : '';
$clave_filtro = isset($_GET['clave']) ? trim($_GET['clave']) : '';
$lote_filtro = isset($_GET['lote_serie']) ? trim($_GET['lote_serie']) : '';

$modos_validos = ['ACOMODO', 'TRASLADO', 'XD'];
$modo = isset($_GET['modo']) ? strtoupper(trim($_GET['modo'])) : 'ACOMODO';
if (!in_array($modo, $modos_validos)) $modo = 'ACOMODO';

/* Catálogos */
$cat_almac = db_all("SELECT id, clave FROM c_almacenp WHERE COALESCE(Activo,1)=1 ORDER BY clave");

$cat_zonas_recibo = db_all("
  SELECT r.cve_ubicacion, r.desc_ubicacion, a.clave AS cve_almac
  FROM tubicacionesretencion r
  LEFT JOIN c_almacenp a ON a.id = r.cve_almacp
  WHERE COALESCE(r.Activo,1)=1
  ORDER BY a.clave, r.cve_ubicacion
");

$cat_ubicaciones = db_all("
  SELECT u.idy_ubica, u.Ubicacion, u.Seccion, u.cve_pasillo, u.cve_rack, a.clave AS cve_almac, u.CodigoCSD
  FROM c_ubicacion u
  JOIN c_almacenp a ON a.id = u.cve_almac
  WHERE COALESCE(u.Activo,1)=1
  ORDER BY a.clave, u.Ubicacion
");

$cat_zonas_embarque = db_all("
  SELECT e.cve_ubicacion, e.descripcion, e.AreaStagging, a.clave AS cve_almac
  FROM t_ubicacionembarque e
  JOIN c_almacenp a ON a.id = e.cve_almac
  WHERE COALESCE(e.Activo,1)=1
  ORDER BY a.clave, e.cve_ubicacion
");

/* Helpers combos */
function render_options_almacen(array $rows, string $selected = ''): string {
  $html = '<option value="">Seleccione</option>';
  foreach ($rows as $r) {
    $clave = trim($r['clave']);
    $sel = ($selected !== '' && $selected === $clave) ? ' selected' : '';
    $html .= '<option value="'.htmlspecialchars($clave).'"'.$sel.'>'.htmlspecialchars($clave).'</option>';
  }
  return $html;
}
function render_options_zonas(array $rows, string $selected = '', string $almacen_sel = ''): string {
  $html = '<option value="">Seleccione</option>';
  $almacen_sel = trim($almacen_sel);
  foreach ($rows as $r) {
    $cve_ubi = trim($r['cve_ubicacion']);
    $desc = trim($r['desc_ubicacion'] ?? '');
    $cve_alm = trim($r['cve_almac'] ?? '');
    if ($almacen_sel !== '' && $cve_alm !== '' && $cve_alm !== $almacen_sel) continue;
    $sel = ($selected !== '' && $selected === $cve_ubi) ? ' selected' : '';
    $text = $cve_ubi . ($desc !== '' ? ' - ' . $desc : '');
    $html .= '<option value="'.htmlspecialchars($cve_ubi).'"'.$sel.'>'.htmlspecialchars($text).'</option>';
  }
  return $html;
}
function render_options_bl(array $rows, string $selected = '', string $almacen_sel = ''): string {
  $html = '<option value="">Seleccione</option>';
  $almacen_sel = trim($almacen_sel);
  foreach ($rows as $r) {
    $ubi = trim($r['Ubicacion'] ?? '');
    $csd = trim($r['CodigoCSD'] ?? ''); // BL real
    $sec = trim($r['Seccion'] ?? '');
    $pas = trim($r['cve_pasillo'] ?? '');
    $rack = trim($r['cve_rack'] ?? '');
    $cve_alm = trim($r['cve_almac'] ?? '');

    if ($almacen_sel !== '' && $cve_alm !== '' && $cve_alm !== $almacen_sel) continue;
    if ($csd === '') continue;

    $label = $csd . ' · ' . $ubi;
    $parts = [];
    if ($sec!=='') $parts[]=$sec;
    if ($pas!=='') $parts[]='P:'.$pas;
    if ($rack!=='') $parts[]='R:'.$rack;
    if (!empty($parts)) $label .= ' ['.implode(' ',$parts).']';

    $sel = ($selected !== '' && $selected === $csd) ? ' selected' : '';
    $html .= '<option value="'.htmlspecialchars($csd).'"'.$sel.'>'.htmlspecialchars($label).'</option>';
  }
  return $html;
}
function render_options_embarque(array $rows, string $selected = '', string $almacen_sel = ''): string {
  $html = '<option value="">Seleccione</option>';
  $almacen_sel = trim($almacen_sel);
  foreach ($rows as $r) {
    $cve_ubi = trim($r['cve_ubicacion']);
    $desc = trim($r['descripcion'] ?? '');
    $cve_alm = trim($r['cve_almac'] ?? '');
    $stag = trim($r['AreaStagging'] ?? '');
    if ($almacen_sel !== '' && $cve_alm !== '' && $cve_alm !== $almacen_sel) continue;
    if ($stag !== 'S') continue;
    $sel = ($selected !== '' && $selected === $cve_ubi) ? ' selected' : '';
    $text = $cve_ubi . ($desc !== '' ? ' - ' . $desc : '') . ' [Stagging]';
    $html .= '<option value="'.htmlspecialchars($cve_ubi).'"'.$sel.'>'.htmlspecialchars($text).'</option>';
  }
  return $html;
}

/* Consulta pendientes (tu lógica) */
$folios_resumen = [];
$lineas_detalle = [];
$total_unidades = 0.0;
$error_msg = '';
$limite_rows = 500;

if (($modo === 'ACOMODO' || $modo === 'XD') && $almacen_sel !== '' && $zona_recibo_sel !== '') {
  try {
    $fromBase = "
      FROM v_pendientesacomodo p
      INNER JOIN td_entalmacen td
         ON TRIM(td.cve_articulo) = TRIM(p.cve_articulo)
        AND IFNULL(TRIM(td.cve_lote),'') = IFNULL(TRIM(p.Cve_Lote),'')
        AND TRIM(td.cve_ubicacion) = TRIM(p.Cve_Ubicacion)
      INNER JOIN th_entalmacen th
         ON th.Fol_Folio  = td.fol_folio
      LEFT JOIN c_articulo a
         ON TRIM(a.cve_articulo) = TRIM(td.cve_articulo)
      LEFT JOIN (
        SELECT fol_folio, cve_articulo, cve_lote, MAX(ClaveEtiqueta) AS ClaveEtiqueta
        FROM td_entalmacenxtarima
        GROUP BY fol_folio, cve_articulo, cve_lote
      ) tx
        ON tx.fol_folio    = td.fol_folio
       AND tx.cve_articulo = td.cve_articulo
       AND IFNULL(tx.cve_lote,'') = IFNULL(td.cve_lote,'')
      LEFT JOIN (
        SELECT Fol_Folio, Cve_Articulo, Cve_Lote, MAX(ClaveEtiqueta) AS ClaveEtiqueta
        FROM td_entalmacencaja
        GROUP BY Fol_Folio, Cve_Articulo, Cve_Lote
      ) cx
        ON cx.Fol_Folio    = td.fol_folio
       AND cx.Cve_Articulo = td.cve_articulo
       AND IFNULL(cx.Cve_Lote,'') = IFNULL(td.cve_lote,'')
    ";

    $whereBase = "
      WHERE TRIM(th.Cve_Almac)    = TRIM(:almac)
        AND TRIM(p.Cve_Ubicacion) = TRIM(:zona)
        AND (td.CantidadUbicada+0) < (td.CantidadRecibida+0)
    ";

    $paramsBase = [':almac'=>$almacen_sel, ':zona'=>$zona_recibo_sel];

    $sqlResumen = "
      SELECT
        th.Fol_Folio AS folio_entrada,
        td.tipo_entrada AS tipo_entrada,
        th.Proyecto AS proyecto,
        SUM((td.CantidadRecibida+0)-(td.CantidadUbicada+0)) AS cant_pendiente,
        COUNT(*) AS num_lineas
      $fromBase
      $whereBase
      GROUP BY th.Fol_Folio, td.tipo_entrada, th.Proyecto
      ORDER BY th.Fol_Folio DESC
      LIMIT {$limite_rows}
    ";
    $folios_resumen = db_all($sqlResumen, $paramsBase);

    if ($folio_sel !== '') {
      $sqlDetalle = "
        SELECT
          th.Cve_Almac,
          th.Fol_Folio AS folio_entrada,
          td.id AS id_det,
          td.tipo_entrada AS tipo_entrada,
          th.Proyecto AS proyecto,
          CASE
            WHEN cx.Fol_Folio IS NOT NULL THEN 'CONTENEDOR'
            WHEN tx.fol_folio IS NOT NULL THEN 'PALLET'
            ELSE 'PIEZA'
          END AS nivel,
          COALESCE(cx.ClaveEtiqueta, tx.ClaveEtiqueta) AS lp,
          td.cve_articulo,
          a.des_articulo,
          td.cve_lote,
          td.cve_ubicacion AS bl_origen,
          (td.CantidadRecibida+0) AS cant_recibida,
          (td.CantidadUbicada+0) AS cant_ubicada,
          (td.CantidadRecibida+0) - (td.CantidadUbicada+0) AS cant_pendiente
        $fromBase
        $whereBase
        AND th.Fol_Folio = :folio
      ";
      $paramsDetalle = $paramsBase;
      $paramsDetalle[':folio'] = $folio_sel;

      if ($clave_filtro !== '') { $sqlDetalle .= " AND TRIM(td.cve_articulo)=TRIM(:cve_art) "; $paramsDetalle[':cve_art']=$clave_filtro; }
      if ($lote_filtro !== '')  { $sqlDetalle .= " AND TRIM(td.cve_lote)=TRIM(:lote) "; $paramsDetalle[':lote']=$lote_filtro; }
      if ($lp_filtro !== '')    { $sqlDetalle .= " AND COALESCE(cx.ClaveEtiqueta, tx.ClaveEtiqueta)=:lp "; $paramsDetalle[':lp']=$lp_filtro; }

      $sqlDetalle .= " ORDER BY td.cve_articulo, td.cve_lote ";
      $lineas_detalle = db_all($sqlDetalle, $paramsDetalle);

      foreach($lineas_detalle as $r){ $total_unidades += (float)$r['cant_pendiente']; }
    }

  } catch(Exception $e){
    $error_msg = $e->getMessage();
    $folios_resumen = [];
    $lineas_detalle = [];
    $total_unidades = 0.0;
  }
}
?>

<style>
  .ap-container{padding:12px;font-size:10px}
  .ap-title{color:#0b5ed7;font-weight:800;margin:6px 0 10px 0}
  table.dataTable tbody td{font-size:10px}
  .btn-xs{padding:2px 8px;font-size:10px}
  #loadingOverlay{position:fixed;z-index:2000;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);display:none;align-items:center;justify-content:center}
</style>

<div id="loadingOverlay">
  <div class="text-center">
    <div class="spinner-border" role="status"></div>
    <div class="mt-2" style="font-size:12px;">Procesando…</div>
  </div>
</div>

<div class="container-fluid ap-container">

  <h2 class="ap-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Put Away · Acomodo / Traslado / XD</h2>

  <?php if($error_msg!==''): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <form method="get" class="row g-2 mb-3" id="frmFiltrosPrincipal"
        onsubmit="document.getElementById('loadingOverlay').style.display='flex';">
    <input type="hidden" name="modo" id="hdnModo" value="<?= htmlspecialchars($modo) ?>">
    <input type="hidden" name="folio_sel" value="<?= htmlspecialchars($folio_sel) ?>">

    <div class="col-md-3">
      <label class="form-label mb-0">Almacén*</label>
      <select name="almacen" class="form-select form-select-sm"
              onchange="document.getElementById('frmFiltrosPrincipal').submit();">
        <?= render_options_almacen($cat_almac, $almacen_sel) ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">Usuario (movimiento)*</label>
      <select id="cmbUsuario" class="form-select form-select-sm"></select>
      <small class="text-muted">Se usa en Kardex (no puede quedar vacío).</small>
    </div>

    <div class="col-md-6">
      <label class="form-label mb-0">Tipo de Movimiento</label>
      <div class="d-flex flex-row flex-wrap mt-1">
        <div class="form-check me-3">
          <input class="form-check-input modo-mov" type="radio" name="modo_radio" value="ACOMODO" <?= $modo==='ACOMODO'?'checked':'' ?>>
          <label class="form-check-label">Acomodo</label>
        </div>
        <div class="form-check me-3">
          <input class="form-check-input modo-mov" type="radio" name="modo_radio" value="TRASLADO" <?= $modo==='TRASLADO'?'checked':'' ?>>
          <label class="form-check-label">Traslado</label>
        </div>
        <div class="form-check me-3">
          <input class="form-check-input modo-mov" type="radio" name="modo_radio" value="XD" <?= $modo==='XD'?'checked':'' ?>>
          <label class="form-check-label">CrossDocking</label>
        </div>
      </div>
    </div>

    <?php
      $cl_zona_origen = ($modo==='ACOMODO' || $modo==='XD') ? '' : 'd-none';
      $cl_bl_origen   = ($modo==='TRASLADO') ? '' : 'd-none';
      $cl_bl_destino  = ($modo==='ACOMODO' || $modo==='TRASLADO') ? '' : 'd-none';
      $cl_zona_emb    = ($modo==='XD') ? '' : 'd-none';
    ?>

    <div class="col-md-3 mt-2 <?= $cl_zona_origen ?>" id="grpZonaOrigen">
      <label class="form-label mb-0">Zona Origen (Recepción)</label>
      <select name="zona_recibo" id="cmbZonaRecibo" class="form-select form-select-sm"
              onchange="document.getElementById('frmFiltrosPrincipal').submit();">
        <?= render_options_zonas($cat_zonas_recibo, $zona_recibo_sel, $almacen_sel) ?>
      </select>
    </div>

    <div class="col-md-3 mt-2 <?= $cl_bl_origen ?>" id="grpBlOrigen">
      <label class="form-label mb-0">BL Origen</label>
      <select name="bl_origen" id="cmbBlOrigen" class="form-select form-select-sm">
        <?= render_options_bl($cat_ubicaciones, $bl_origen_sel, $almacen_sel) ?>
      </select>
    </div>

    <div class="col-md-3 mt-2 <?= $cl_bl_destino ?>" id="grpBlDestino">
      <label class="form-label mb-0">BL Destino</label>
      <select name="bl_destino" id="cmbBlDestino" class="form-select form-select-sm">
        <?= render_options_bl($cat_ubicaciones, $bl_destino_sel, $almacen_sel) ?>
      </select>
      <small class="text-muted">BL = c_ubicacion.CodigoCSD</small>
    </div>

    <div class="col-md-3 mt-2 <?= $cl_zona_emb ?>" id="grpZonaEmbarque">
      <label class="form-label mb-0">Zona Embarque Destino</label>
      <select name="zona_embarque" id="cmbZonaEmbarque" class="form-select form-select-sm">
        <?= render_options_embarque($cat_zonas_embarque, $zona_embarque_sel, $almacen_sel) ?>
      </select>
    </div>
  </form>

  <div class="row mb-2">
    <div class="col-md-4"><div class="card"><div class="card-body py-2 text-center"><b>Modo</b><div><?= htmlspecialchars($modo) ?></div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body py-2 text-center"><b>Folio</b><div><?= $folio_sel!==''?htmlspecialchars($folio_sel):'Ninguno' ?></div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body py-2 text-center"><b>Pendiente</b><div><?= number_format($total_unidades,2) ?></div></div></div></div>
  </div>

  <?php if($modo==='ACOMODO' || $modo==='XD'): ?>
    <div class="row mb-2">
      <div class="col-12">
        <h6>Folios con pendiente</h6>
        <div class="table-responsive">
          <table id="tblFoliosPendientes" class="table table-sm table-bordered table-striped text-center align-middle" style="width:100%;">
            <thead style="background:#eef3ff;">
              <tr>
                <th>Acciones</th>
                <th>Folio</th>
                <th>Tipo</th>
                <th>Proyecto</th>
                <th>Pendiente</th>
                <th>Renglones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($folios_resumen as $row): ?>
                <?php
                  $urlVer = 'putaway_acomodo.php?modo='.urlencode($modo)
                    .'&almacen='.urlencode($almacen_sel)
                    .'&zona_recibo='.urlencode($zona_recibo_sel)
                    .'&folio_sel='.urlencode($row['folio_entrada']);
                ?>
                <tr>
                  <td><a class="btn btn-outline-primary btn-xs" href="<?= htmlspecialchars($urlVer) ?>">Ver</a></td>
                  <td><?= htmlspecialchars($row['folio_entrada']) ?></td>
                  <td><?= htmlspecialchars($row['tipo_entrada']) ?></td>
                  <td><?= htmlspecialchars($row['proyecto']) ?></td>
                  <td class="text-end"><?= number_format((float)$row['cant_pendiente'],2) ?></td>
                  <td class="text-end"><?= (int)$row['num_lineas'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <small class="text-muted">Selecciona un folio para ver el detalle y ejecutar acomodo.</small>
      </div>
    </div>
  <?php endif; ?>

  <?php if($folio_sel!=='' && ($modo==='ACOMODO' || $modo==='XD')): ?>
    <div class="row mb-2">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h6 class="m-0">Detalle folio <?= htmlspecialchars($folio_sel) ?></h6>
        <button class="btn btn-success btn-xs" id="btnAcomodarSeleccion">
          <i class="fa-solid fa-check me-1"></i>Acomodar selección
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table id="tblPendientesAcomodo" class="table table-sm table-bordered table-striped text-center align-middle" style="width:100%;">
        <thead style="background:#eef3ff;">
          <tr>
            <th>Sel</th>
            <th>Folio</th>
            <th>ID Det</th>
            <th>Nivel</th>
            <th>LP</th>
            <th>Clave</th>
            <th>Descripción</th>
            <th>Lote</th>
            <th>BL Origen</th>
            <th>Recibida</th>
            <th>Ubicada</th>
            <th>Pendiente</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($lineas_detalle as $row): ?>
            <tr
              data-folio="<?= htmlspecialchars($row['folio_entrada']) ?>"
              data-id_det="<?= (int)$row['id_det'] ?>"
              data-nivel="<?= htmlspecialchars($row['nivel']) ?>"
              data-lp="<?= htmlspecialchars($row['lp']) ?>"
              data-art="<?= htmlspecialchars($row['cve_articulo']) ?>"
              data-lote="<?= htmlspecialchars($row['cve_lote']) ?>"
              data-pend="<?= htmlspecialchars($row['cant_pendiente']) ?>"
            >
              <td><input type="radio" name="selRow"></td>
              <td><?= htmlspecialchars($row['folio_entrada']) ?></td>
              <td><?= (int)$row['id_det'] ?></td>
              <td><?= htmlspecialchars($row['nivel']) ?></td>
              <td><?= htmlspecialchars($row['lp']) ?></td>
              <td><?= htmlspecialchars($row['cve_articulo']) ?></td>
              <td><?= htmlspecialchars($row['des_articulo']) ?></td>
              <td><?= htmlspecialchars($row['cve_lote']) ?></td>
              <td><?= htmlspecialchars($row['bl_origen']) ?></td>
              <td class="text-end"><?= number_format((float)$row['cant_recibida'],2) ?></td>
              <td class="text-end"><?= number_format((float)$row['cant_ubicada'],2) ?></td>
              <td class="text-end"><span class="badge text-bg-warning"><?= number_format((float)$row['cant_pendiente'],2) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <small class="text-muted">
      Regla: si Nivel es CONTENEDOR o PALLET, el sistema mueve completo (qty = pendiente). No se desarma jerarquía.
    </small>
  <?php endif; ?>

</div>

<!-- MODAL CONFIRMAR ACOMODO -->
<div class="modal fade" id="mdlConfirmAcomodo" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h6 class="modal-title m-0"><i class="fa-solid fa-truck-ramp-box me-2"></i>Confirmar Acomodo</h6>
          <div class="text-muted" style="font-size:12px;">Genera Kardex tipo 2 con usuario y timestamp.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="font-size:12px;">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label mb-0">Folio</label>
            <input id="mFolio" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label mb-0">Nivel</label>
            <input id="mNivel" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label mb-0">LP</label>
            <input id="mLP" class="form-control form-control-sm" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label mb-0">Artículo</label>
            <input id="mArt" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label mb-0">Lote</label>
            <input id="mLote" class="form-control form-control-sm" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label mb-0">Pendiente</label>
            <input id="mPend" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label mb-0">Cantidad a mover</label>
            <input id="mQty" type="number" step="0.0001" class="form-control form-control-sm">
            <small class="text-muted">Para contenedor/pallet se fuerza completo.</small>
          </div>
          <div class="col-md-4">
            <label class="form-label mb-0">BL destino</label>
            <input id="mBL" class="form-control form-control-sm" readonly>
            <small class="text-muted">Se toma del filtro BL Destino.</small>
          </div>

          <div class="col-md-6">
            <label class="form-label mb-0">Usuario</label>
            <input id="mUsr" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label mb-0">ID Detalle</label>
            <input id="mIdDet" class="form-control form-control-sm" readonly>
          </div>

          <div class="col-12">
            <div class="alert alert-info py-2 mt-2 mb-0" style="font-size:12px;">
              Este movimiento preserva trazabilidad por <b>Referencia=Folio</b> en Kardex (OC/Factura/Proyecto/Proveedor vía th_entalmacen).
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success btn-sm" id="btnConfirmarAcomodo">
          <i class="fa-solid fa-check me-1"></i>Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
function applyModoUI(modo){
  const grpZonaOrigen = $('#grpZonaOrigen');
  const grpBlOrigen   = $('#grpBlOrigen');
  const grpBlDestino  = $('#grpBlDestino');
  const grpZonaEmb    = $('#grpZonaEmbarque');

  grpZonaOrigen.addClass('d-none'); grpBlOrigen.addClass('d-none'); grpBlDestino.addClass('d-none'); grpZonaEmb.addClass('d-none');

  if(modo==='ACOMODO'){ grpZonaOrigen.removeClass('d-none'); grpBlDestino.removeClass('d-none'); }
  if(modo==='TRASLADO'){ grpBlOrigen.removeClass('d-none'); grpBlDestino.removeClass('d-none'); }
  if(modo==='XD'){ grpZonaOrigen.removeClass('d-none'); grpZonaEmb.removeClass('d-none'); }
}

async function cargarUsuarios(){
  const sel = document.getElementById('cmbUsuario');
  if(!sel) return;

  const r = await fetch('../api/usuarios.php?action=list');
  const j = await r.json();
  const rows = (j && j.ok) ? (j.data||[]) : (Array.isArray(j) ? j : []);

  sel.innerHTML = '';
  rows.forEach(u=>{
    const opt = document.createElement('option');
    opt.value = (u.cve_usuario||'').trim();
    opt.textContent = `${(u.cve_usuario||'').trim()} - ${(u.nombre_completo||u.nombre||'').trim()}`;
    sel.appendChild(opt);
  });

  const uSesion = "<?= htmlspecialchars($cve_usuario_sesion) ?>".trim();
  if(uSesion){
    const found = [...sel.options].find(o=>o.value===uSesion);
    if(found) sel.value = uSesion;
  }
  if(!sel.value && sel.options.length>0) sel.selectedIndex = 0;
}

function usuarioMovimiento(){
  const sel = document.getElementById('cmbUsuario');
  if(!sel) return "<?= htmlspecialchars($cve_usuario_sesion) ?>";
  return sel.value || (sel.options[0] ? sel.options[0].value : "<?= htmlspecialchars($cve_usuario_sesion) ?>");
}

function getSelectedRow(){
  const r = document.querySelector('#tblPendientesAcomodo input[name="selRow"]:checked');
  if(!r) return null;
  return r.closest('tr');
}

function showOverlay(on){
  document.getElementById('loadingOverlay').style.display = on ? 'flex' : 'none';
}

$(function(){
  cargarUsuarios();

  if($('#tblFoliosPendientes').length){
    $('#tblFoliosPendientes').DataTable({
      pageLength:25,lengthChange:false,scrollX:true,
      language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'}
    });
  }

  if($('#tblPendientesAcomodo').length){
    $('#tblPendientesAcomodo').DataTable({
      pageLength:25,lengthChange:false,scrollX:true,scrollY:'52vh',scrollCollapse:true,
      language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'}
    });
  }

  $('.modo-mov').on('change', function(){
    const modoSel = $('.modo-mov:checked').val() || 'ACOMODO';
    $('#hdnModo').val(modoSel);
    $('input[name="folio_sel"]').val('');
    applyModoUI(modoSel);
    showOverlay(true);
    document.getElementById('frmFiltrosPrincipal').submit();
  });

  applyModoUI($('#hdnModo').val() || 'ACOMODO');

  // Abrir modal
  $('#btnAcomodarSeleccion').on('click', function(){
    const tr = getSelectedRow();
    if(!tr){ alert('Selecciona una línea.'); return; }

    const blDest = ($('#cmbBlDestino').val() || '').trim();
    if(!blDest){ alert('Selecciona BL Destino.'); return; }

    const folio = tr.dataset.folio;
    const id_det = tr.dataset.id_det;
    const nivel = tr.dataset.nivel;
    const lp = tr.dataset.lp || '';
    const art = tr.dataset.art;
    const lote = tr.dataset.lote || '';
    const pend = parseFloat(tr.dataset.pend || '0');

    // regla: contenedor/pallet -> completo
    let qty = pend;
    if(nivel==='PIEZA'){
      qty = pend; // default pendiente, editable
      $('#mQty').prop('readonly', false);
    } else {
      $('#mQty').prop('readonly', true);
    }

    $('#mFolio').val(folio);
    $('#mIdDet').val(id_det);
    $('#mNivel').val(nivel);
    $('#mLP').val(lp);
    $('#mArt').val(art);
    $('#mLote').val(lote);
    $('#mPend').val(pend.toFixed(4));
    $('#mQty').val(qty.toFixed(4));
    $('#mBL').val(blDest);
    $('#mUsr').val(usuarioMovimiento());

    new bootstrap.Modal(document.getElementById('mdlConfirmAcomodo')).show();
  });

  // Confirmar
  $('#btnConfirmarAcomodo').on('click', async function(){
    const folio = $('#mFolio').val();
    const id_det = $('#mIdDet').val();
    const nivel = $('#mNivel').val();
    const lp = $('#mLP').val();
    const art = $('#mArt').val();
    const lote = $('#mLote').val();
    const pend = parseFloat($('#mPend').val() || '0');
    let qty = parseFloat($('#mQty').val() || '0');
    const destino_bl = ($('#mBL').val() || '').trim();
    const usr = usuarioMovimiento();

    if(!destino_bl){ alert('BL destino vacío.'); return; }
    if(!usr){ alert('Usuario vacío.'); return; }
    if(!(qty>0)){ alert('Cantidad inválida.'); return; }
    if(qty > pend + 0.0001){ alert('Cantidad excede pendiente.'); return; }

    if(nivel!=='PIEZA'){
      // fuerza completo
      qty = pend;
    }

    const fd = new FormData();
    fd.append('action','confirm');
    fd.append('folio', folio);
    fd.append('id_det', id_det);
    fd.append('cve_articulo', art);
    fd.append('cve_lote', lote);
    fd.append('qty', qty);
    fd.append('destino_bl', destino_bl);
    fd.append('cve_usuario', usr);

    // LP: dejamos como contenedor_lp o pallet_lp según nivel (para trazabilidad)
    if(nivel==='CONTENEDOR') fd.append('contenedor_lp', lp);
    if(nivel==='PALLET') fd.append('pallet_lp', lp);

    showOverlay(true);
    const r = await fetch('../api/putaway_confirmar.php', { method:'POST', body: fd });
    const j = await r.json();
    showOverlay(false);

    if(!j.ok){
      alert(j.msg || 'Error al confirmar');
      return;
    }

    // cerrar modal y refrescar para que cambie pendiente/avance y RTM se limpie si llega a 100%
    bootstrap.Modal.getInstance(document.getElementById('mdlConfirmAcomodo')).hide();
    alert('Movimiento OK. Kardex ID: ' + (j.data?.kardex_id ?? ''));
    location.reload();
  });

});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
