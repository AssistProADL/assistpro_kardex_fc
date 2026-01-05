<?php
require_once __DIR__ . '/../../app/db.php';

// --- Helper HTML seguro (evita Deprecated por null en htmlspecialchars, PHP 8.2/8.3) ---
if (!function_exists('h')) {
  function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
  }
}

// ----------------- CONFIG UI -----------------
$activeSection = 'procesos';
$activeItem    = 'putaway';
$pageTitle     = 'Put Away · Acomodo / Traslado / XD';
include __DIR__ . '/../bi/_menu_global.php';

// ----------------- INPUTS -----------------
$modo = isset($_GET['modo']) ? strtoupper(trim($_GET['modo'])) : 'ACOMODO';
if(!in_array($modo, ['ACOMODO','TRASLADO','XD'], true)) $modo = 'ACOMODO';

$almacen_sel       = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$zona_recibo_sel   = isset($_GET['zona_recibo']) ? trim($_GET['zona_recibo']) : '';
$folio_sel         = isset($_GET['folio_sel']) ? trim($_GET['folio_sel']) : '';
$bl_origen_sel     = isset($_GET['bl_origen']) ? trim($_GET['bl_origen']) : '';
$bl_destino_sel    = isset($_GET['bl_destino']) ? trim($_GET['bl_destino']) : '';
$zona_embarque_sel = isset($_GET['zona_embarque']) ? trim($_GET['zona_embarque']) : '';

$modo_radio = isset($_GET['modo_radio']) ? strtoupper(trim($_GET['modo_radio'])) : $modo;
if(!in_array($modo_radio, ['ACOMODO','TRASLADO','XD'], true)) $modo_radio = $modo;

$error_msg = '';

// ----------------- CATÁLOGOS -----------------
$cat_almacenes = [];
$cat_ubicaciones = [];
$cat_zonas_recibo = [];
$cat_zonas_embarque = [];

try{
  // Almacenes (c_almacenp)
  $cat_almacenes = db_all("
    SELECT id, clave, nombre, cve_cia
    FROM c_almacenp
    WHERE COALESCE(Activo,1)=1
    ORDER BY clave
  ");
}catch(Throwable $e){
  $error_msg = 'Error cargando almacenes: '.$e->getMessage();
}

// Helper renders
function render_options_almacen(array $rows, string $selected = ''): string {
  $html = '<option value="">Seleccione...</option>';
  foreach ($rows as $r) {
    $clave = trim((string)($r['clave'] ?? ''));
    $nom   = trim((string)($r['nombre'] ?? ''));
    if($clave==='') continue;
    $sel = ($selected !== '' && $selected === $clave) ? ' selected' : '';
    $lbl = $nom!=='' ? ($clave.' - '.$nom) : $clave;
    $html .= '<option value="'.h($clave).'"'.$sel.'>'.h($lbl).'</option>';
  }
  return $html;
}

function render_options_zonas(array $rows, string $selected = '', string $almacen_sel=''): string {
  $html = '<option value="">Seleccione...</option>';
  foreach($rows as $r){
    $cve_ubi = trim((string)($r['cve_ubicacion'] ?? ($r['codigo'] ?? '')));
    $desc    = trim((string)($r['desc_ubicacion'] ?? ($r['nombre'] ?? '')));
    if($cve_ubi==='') continue;
    $text = $desc!=='' ? ($cve_ubi.' - '.$desc) : $cve_ubi;
    $sel = ($selected !== '' && $selected === $cve_ubi) ? ' selected' : '';
    $html .= '<option value="'.h($cve_ubi).'"'.$sel.'>'.h($text).'</option>';
  }
  return $html;
}

function render_options_bl(array $rows, string $selected = '', string $almacen_sel=''): string {
  $html = '<option value="">Seleccione...</option>';
  foreach($rows as $r){
    // BL = c_ubicacion.CodigoCSD (prioritario por convención)
    $csd = trim((string)($r['CodigoCSD'] ?? $r['codigocsd'] ?? ''));
    if($csd==='') continue;
    $label = $csd;
    $sel = ($selected !== '' && $selected === $csd) ? ' selected' : '';
    $html .= '<option value="'.h($csd).'"'.$sel.'>'.h($label).'</option>';
  }
  return $html;
}

function render_options_embarque(array $rows, string $selected = '', string $almacen_sel=''): string {
  $html = '<option value="">Seleccione...</option>';
  foreach($rows as $r){
    $cve_ubi = trim((string)($r['cve_ubicacion'] ?? ($r['codigo'] ?? '')));
    $desc    = trim((string)($r['desc_ubicacion'] ?? ($r['nombre'] ?? '')));
    if($cve_ubi==='') continue;
    $text = $desc!=='' ? ($cve_ubi.' - '.$desc) : $cve_ubi;
    $sel = ($selected !== '' && $selected === $cve_ubi) ? ' selected' : '';
    $html .= '<option value="'.h($cve_ubi).'"'.$sel.'>'.h($text).'</option>';
  }
  return $html;
}

// Cargar ubicaciones + zonas (solo si hay almacén)
if($almacen_sel!==''){
  try{
    // Ubicaciones para BL (c_ubicacion)
    // Nota: el filtro por almacén puede variar según tu modelo; aquí se usa cve_almac desde c_ubicacion.
    // Si tu almacén es clave y no id, se ajusta posteriormente.
    $cat_ubicaciones = db_all("
      SELECT CodigoCSD
      FROM c_ubicacion
      WHERE COALESCE(Activo,1)=1
      ORDER BY CodigoCSD
      LIMIT 5000
    ");

    // Zonas de recibo (tubicacionesretencion) por c_almacenp.id (cve_almacp)
    $alm_idp = db_val("
      SELECT id
      FROM c_almacenp
      WHERE TRIM(clave)=TRIM(:alm)
      LIMIT 1
    ", [':alm'=>$almacen_sel]);

    if($alm_idp){
      $cat_zonas_recibo = db_all("
        SELECT cve_ubicacion, desc_ubicacion, AreaStagging, B_Devolucion
        FROM tubicacionesretencion
        WHERE COALESCE(Activo,1)=1
          AND cve_almacp = :idp
        ORDER BY cve_ubicacion
      ", [':idp'=>$alm_idp]);
    }else{
      $cat_zonas_recibo = [];
    }

    // Zonas embarque (placeholder: si tienes tabla/vista específica, aquí conectamos)
    $cat_zonas_embarque = [];

  }catch(Throwable $e){
    $error_msg = 'Error cargando catálogos: '.$e->getMessage();
  }
}

// ----------------- DATA OPERATIVA -----------------
$folios_pendientes = [];
$lineas_detalle = [];
$total_unidades = 0.0;

try{
  if($modo==='ACOMODO' || $modo==='XD'){
    // Folios con pendiente (th_entalmacen/td_entalmacen)
    if($almacen_sel!==''){
      $sqlFol = "
        SELECT
          th.Fol_Folio AS folio,
          th.tipo      AS tipo_doc,
          th.Fol_OEP   AS oc,
          th.Fact_Prov AS factura,
          th.Proveedor AS proveedor,
          th.Proyecto  AS proyecto,
          th.ID_Protocolo,
          th.Consec_protocolo,
          COUNT(td.id) AS partidas,
          SUM(COALESCE(td.CantidadRecibida,0)) AS total_recibido,
          SUM(COALESCE(td.CantidadUbicada,0))  AS total_acomodado,
          SUM(GREATEST(COALESCE(td.CantidadRecibida,0) - COALESCE(td.CantidadUbicada,0),0)) AS pendiente,
          ROUND(
            (SUM(COALESCE(td.CantidadUbicada,0)) / NULLIF(SUM(COALESCE(td.CantidadRecibida,0)),0))*100
          ,2) AS avance
        FROM th_entalmacen th
        INNER JOIN td_entalmacen td ON td.fol_folio = th.Fol_Folio
        WHERE TRIM(th.Cve_Almac)=TRIM(:alm)
      ";
      $params = [':alm'=>$almacen_sel];

      if($zona_recibo_sel!==''){
        $sqlFol .= " AND TRIM(IFNULL(td.cve_ubicacion, th.cve_ubicacion))=TRIM(:zona) ";
        $params[':zona'] = $zona_recibo_sel;
      }

      $sqlFol .= "
        GROUP BY th.Fol_Folio, th.tipo, th.Fol_OEP, th.Fact_Prov, th.Proveedor, th.Proyecto, th.ID_Protocolo, th.Consec_protocolo
        HAVING pendiente > 0
        ORDER BY th.Fol_Folio DESC
        LIMIT 500
      ";

      $folios_pendientes = db_all($sqlFol, $params);

      if($folio_sel===''){
        // Si viene vacío, dejamos que el usuario seleccione. No forzamos el primero.
      }

      // Detalle del folio seleccionado
      if($folio_sel!==''){
        $sqlDet = "
          SELECT
            th.Fol_Folio AS folio_entrada,
            td.id        AS id_det,
            COALESCE(td.Nivel,'PIEZA') AS nivel,
            COALESCE(td.LP,'') AS lp,
            td.cve_articulo,
            COALESCE(a.des_articulo,'') AS des_articulo,
            COALESCE(td.cve_lote,'') AS cve_lote,
            COALESCE(td.bl_origen,'') AS bl_origen,
            COALESCE(td.CantidadRecibida,0) AS cant_recibida,
            COALESCE(td.CantidadUbicada,0)  AS cant_ubicada,
            GREATEST(COALESCE(td.CantidadRecibida,0) - COALESCE(td.CantidadUbicada,0),0) AS cant_pendiente
          FROM th_entalmacen th
          INNER JOIN td_entalmacen td ON td.fol_folio = th.Fol_Folio
          LEFT JOIN c_articulo a ON a.cve_articulo = td.cve_articulo
          WHERE TRIM(th.Cve_Almac)=TRIM(:alm)
            AND th.Fol_Folio = :folio
        ";
        $params = [':alm'=>$almacen_sel, ':folio'=>$folio_sel];

        if($zona_recibo_sel!==''){
          $sqlDet .= " AND TRIM(IFNULL(td.cve_ubicacion, th.cve_ubicacion))=TRIM(:zona) ";
          $params[':zona'] = $zona_recibo_sel;
        }

        $sqlDet .= " HAVING cant_pendiente > 0 ORDER BY td.id LIMIT 3000";

        $lineas_detalle = db_all($sqlDet, $params);

        $total_unidades = 0.0;
        foreach($lineas_detalle as $r){
          $total_unidades += (float)($r['cant_pendiente'] ?? 0);
        }
      }
    }

  } elseif($modo==='TRASLADO'){
    // Traslado: aquí tu lógica (origen/destino BL). Se deja UI, sin romper.
    $folios_pendientes = [];
    $lineas_detalle = [];
    $total_unidades = 0.0;
  }

}catch(Throwable $e){
  $error_msg = 'Error cargando operación: '.$e->getMessage();
}

?>
<style>
  .ap-page{font-size:10px;padding:12px}
  .ap-title{color:#0b5ed7;font-weight:800;margin:6px 0 10px 0}
  .ap-card{background:#fff;border:1px solid #d0d7e2;border-radius:12px;padding:10px;margin-bottom:10px}
  .ap-sub{color:#5b6777;font-size:11px;margin-top:-6px}
  .btn-xs{padding:2px 8px;font-size:10px;border-radius:6px}
  .table thead th{white-space:nowrap}
</style>

<div class="ap-page">

  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="ap-title"><i class="fa-solid fa-warehouse me-2"></i>Put Away · Acomodo / Traslado / XD</div>
      <div class="ap-sub">Consola operativa con RTM + confirmación a Kardex. (Modo ACOMODO/TRASLADO/XD)</div>
    </div>
    <div>
      <a class="btn btn-outline-primary btn-xs" href="./rtm_general.php">RTM General</a>
    </div>
  </div>

  <div class="ap-card">
    <?php if($error_msg!==''): ?>
      <div class="alert alert-warning"><?= h($error_msg) ?></div>
    <?php endif; ?>

    <form method="get" id="frmFiltrosPrincipal" class="row g-2 align-items-end">
      <input type="hidden" name="modo" id="hdnModo" value="<?= h($modo) ?>">
      <input type="hidden" name="folio_sel" value="<?= h($folio_sel) ?>">

      <div class="col-md-3">
        <label class="form-label mb-0">Almacén*</label>
        <select name="almacen" id="cmbAlmacen" class="form-select form-select-sm"
                onchange="document.getElementById('frmFiltrosPrincipal').submit();">
          <?= render_options_almacen($cat_almacenes, $almacen_sel) ?>
        </select>
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

      <div class="col-md-3">
        <label class="form-label mb-0">Folio (RTM)</label>
        <select name="folio_sel" id="cmbFolio" class="form-select form-select-sm">
          <option value="">Seleccione...</option>
          <?php foreach($folios_pendientes as $f): ?>
            <?php $fv = (string)($f['folio'] ?? ''); ?>
            <option value="<?= h($fv) ?>" <?= ($folio_sel!=='' && $folio_sel===$fv)?'selected':'' ?>>
              <?= h($fv) ?> · Pend <?= number_format((float)($f['pendiente'] ?? 0),2) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label mb-0">Modo</label>
        <div class="d-flex gap-2">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="modo_radio" id="rA" value="ACOMODO" <?= $modo_radio==='ACOMODO'?'checked':'' ?>>
            <label class="form-check-label" for="rA">Acomodo</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="modo_radio" id="rT" value="TRASLADO" <?= $modo_radio==='TRASLADO'?'checked':'' ?>>
            <label class="form-check-label" for="rT">Traslado</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="modo_radio" id="rX" value="XD" <?= $modo_radio==='XD'?'checked':'' ?>>
            <label class="form-check-label" for="rX">CrossDocking</label>
          </div>
        </div>
      </div>

      <div class="col-md-2 d-flex gap-2 justify-content-end">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search me-1"></i>Aplicar</button>
        <a href="./putaway_acomodo.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
      </div>
    </form>

    <div class="row mb-2 mt-2">
      <div class="col-md-4"><div class="card"><div class="card-body py-2 text-center"><b>Modo</b><div><?= h($modo) ?></div></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body py-2 text-center"><b>Folio</b><div><?= $folio_sel!==''?h($folio_sel):'Ninguno' ?></div></div></div></div>
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
                  <th>Acción</th>
                  <th>Folio</th>
                  <th>Tipo</th>
                  <th>OC</th>
                  <th>Factura</th>
                  <th>Proveedor</th>
                  <th>Proyecto</th>
                  <th>Protocolo</th>
                  <th>Partidas</th>
                  <th>Recibido</th>
                  <th>Ubicado</th>
                  <th>Pendiente</th>
                  <th>Avance %</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($folios_pendientes as $r): ?>
                  <?php
                    $fol = (string)($r['folio'] ?? '');
                    $urlVer = './putaway_acomodo.php?modo='.urlencode($modo)
                            .'&almacen='.urlencode($almacen_sel)
                            .'&zona_recibo='.urlencode($zona_recibo_sel)
                            .'&folio_sel='.urlencode($fol)
                            .'&modo_radio='.urlencode($modo_radio);
                  ?>
                  <tr>
                    <td><a class="btn btn-outline-primary btn-xs" href="<?= h($urlVer) ?>">Ver</a></td>
                    <td><span class="badge text-bg-primary"><?= (int)$fol ?></span></td>
                    <td><?= h($r['tipo_doc'] ?? '') ?></td>
                    <td><?= h($r['oc'] ?? '') ?></td>
                    <td><?= h($r['factura'] ?? '') ?></td>
                    <td><?= h($r['proveedor'] ?? '') ?></td>
                    <td><?= h($r['proyecto'] ?? '') ?></td>
                    <td><?= h(($r['ID_Protocolo'] ?? '').(($r['Consec_protocolo']??'')!==''?('-'.$r['Consec_protocolo']):'')) ?></td>
                    <td class="text-end"><?= (int)($r['partidas'] ?? 0) ?></td>
                    <td class="text-end"><?= number_format((float)($r['total_recibido'] ?? 0),2) ?></td>
                    <td class="text-end"><?= number_format((float)($r['total_acomodado'] ?? 0),2) ?></td>
                    <td class="text-end"><span class="badge text-bg-warning"><?= number_format((float)($r['pendiente'] ?? 0),2) ?></span></td>
                    <td class="text-end"><?= number_format((float)($r['avance'] ?? 0),2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <small class="text-muted">Tip: selecciona un folio para ver sus líneas con pendiente.</small>
        </div>
      </div>

      <div class="row mb-2">
        <div class="col-12">
          <h6>Detalle del folio</h6>
          <div class="table-responsive">
            <table id="tblLineasDetalle" class="table table-sm table-bordered table-striped text-center align-middle" style="width:100%;">
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
                    data-folio="<?= h($row['folio_entrada'] ?? '') ?>"
                    data-id_det="<?= (int)($row['id_det'] ?? 0) ?>"
                    data-nivel="<?= h($row['nivel'] ?? '') ?>"
                    data-lp="<?= h($row['lp'] ?? '') ?>"
                    data-art="<?= h($row['cve_articulo'] ?? '') ?>"
                    data-lote="<?= h($row['cve_lote'] ?? '') ?>"
                    data-pend="<?= h($row['cant_pendiente'] ?? 0) ?>"
                  >
                    <td><input type="radio" name="selRow"></td>
                    <td><?= h($row['folio_entrada'] ?? '') ?></td>
                    <td><?= (int)($row['id_det'] ?? 0) ?></td>
                    <td><?= h($row['nivel'] ?? '') ?></td>
                    <td><?= h($row['lp'] ?? '') ?></td>
                    <td><?= h($row['cve_articulo'] ?? '') ?></td>
                    <td><?= h($row['des_articulo'] ?? '') ?></td>
                    <td><?= h($row['cve_lote'] ?? '') ?></td>
                    <td><?= h($row['bl_origen'] ?? '') ?></td>
                    <td class="text-end"><?= number_format((float)($row['cant_recibida'] ?? 0),2) ?></td>
                    <td class="text-end"><?= number_format((float)($row['cant_ubicada'] ?? 0),2) ?></td>
                    <td class="text-end">
                      <span class="badge text-bg-warning"><?= number_format((float)($row['cant_pendiente'] ?? 0),2) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <small class="text-muted">
            Regla: si Nivel es CONTENEDOR o PALLET, el sistema mueve completo (qty = pendiente). No se desarma jerarquía.
          </small>
        </div>
      </div>
    <?php endif; ?>

  </div>
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

          <div class="col-md-4">
            <label class="form-label mb-0">Artículo</label>
            <input id="mArt" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-4">
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

<div id="overlayWait" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9999;">
  <div style="position:absolute;top:45%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:18px 22px;border-radius:12px;">
    <i class="fa-solid fa-circle-notch fa-spin me-2"></i>Procesando movimiento...
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
function showOverlay(on){ $('#overlayWait').css('display', on?'block':'none'); }

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

function usuarioMovimiento(){
  // Si tu plataforma maneja sesión, aquí toma el usuario. Si no, déjalo en prompt.
  let u = '';
  try{
    u = (window.ASSISTPRO_USER || '').trim();
  }catch(e){}
  if(!u){
    // fallback (no cambia el diseño, sólo asegura dato)
    u = prompt('Usuario (cve_usuario):','');
    u = (u||'').trim();
  }
  return u;
}

$(function(){
  // DataTables
  if($.fn.DataTable){
    $('#tblFoliosPendientes').DataTable({
      pageLength: 10,
      lengthChange:false,
      searching:true,
      ordering:true,
      info:true,
      scrollX:true,
      scrollY:'28vh',
      scrollCollapse:true,
      language:{ url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
    });

    $('#tblLineasDetalle').DataTable({
      pageLength: 25,
      lengthChange:false,
      searching:true,
      ordering:true,
      info:true,
      scrollX:true,
      scrollY:'45vh',
      scrollCollapse:true,
      language:{ url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
    });
  }

  // Cambiar modo por radio y reflejarlo al hidden modo (mantiene tu flujo)
  $('input[name="modo_radio"]').on('change', function(){
    const m = $(this).val();
    $('#hdnModo').val(m);
    applyModoUI(m);
  });
  applyModoUI($('#hdnModo').val());

  // Selección de fila detalle -> abre modal
  $('#tblLineasDetalle tbody').on('click', 'tr', function(){
    const $tr = $(this);
    $tr.find('input[type=radio]').prop('checked', true);

    const folio = $tr.data('folio') || '';
    const id_det = $tr.data('id_det') || '';
    const nivel = ($tr.data('nivel') || '').toString();
    const lp = ($tr.data('lp') || '').toString();
    const art = ($tr.data('art') || '').toString();
    const lote = ($tr.data('lote') || '').toString();
    const pend = parseFloat($tr.data('pend') || '0');

    const destino = ($('#cmbBlDestino').val() || '').toString();

    $('#mFolio').val(folio);
    $('#mIdDet').val(id_det);
    $('#mNivel').val(nivel);
    $('#mLP').val(lp);
    $('#mArt').val(art);
    $('#mLote').val(lote);
    $('#mPend').val(pend.toFixed(4));
    $('#mBL').val(destino);
    $('#mQty').val(pend.toFixed(4));
    $('#mUsr').val(usuarioMovimiento());

    new bootstrap.Modal(document.getElementById('mdlConfirmAcomodo')).show();
  });

  // Confirmar movimiento
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
    const r = await fetch('./api/putaway_confirmar.php', { method:'POST', body: fd });
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
