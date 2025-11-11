<?php
/* ===========================================================
   public/procesos/acomodo.php
   Acomodo desde zonas de recepción (ts_existenciatarima / ts_existenciapiezas)
   Estructura estándar AssistPro Kardex (db.php + _menu_global)
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';
session_start();

/* ================= Frame (menú global) =================== */
$activeSection = 'operaciones';
$activeItem    = 'acomodo';
$pageTitle     = 'Acomodo desde zonas de recepción · AssistPro ER®';
include __DIR__ . '/../bi/_menu_global.php';

/* ================== Sesión / filtros ================== */
// El kardex usa cve_usuario (texto); si no está en sesión, usamos el username o SISTEMA.
$cve_usuario = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? 'SISTEMA');

$almacen_sel       = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$zona_recibo_sel   = isset($_GET['zona_recibo']) ? trim($_GET['zona_recibo']) : '';
$ubic_destino_pref = isset($_GET['ubic_destino_pref']) ? trim($_GET['ubic_destino_pref']) : '';

/* ================== Catálogos ================== */

/*
 * Almacenes: c_almacenp
 *   - clave = cve_almac
 *   - Activo
 */
$cat_almac = db_all("
  SELECT clave
  FROM c_almacenp
  WHERE COALESCE(Activo,1) = 1
  ORDER BY clave
");

/*
 * Zonas de recepción: tubicacionesretencion
 *   - cve_ubicacion      (R01, 001, 01, 02, A01, ASTG, etc.)
 *   - desc_ubicacion     (Cortina1, Cortina2, ANDEN 1, Area Stagging, ...)
 *   - Activo
 *   NOTA: aquí NO filtramos por almacén, igual que en el acomodo original.
 */
$cat_zonas_recibo = db_all("
  SELECT cve_ubicacion,
         desc_ubicacion
  FROM tubicacionesretencion
  WHERE COALESCE(Activo,1) = 1
  ORDER BY cve_ubicacion
");

/*
 * Ubicaciones de almacenaje: c_ubicacion
 *   - idy_ubica
 *   - CodigoCSD          (BL de almacenaje)
 *   - Activo
 */
$cat_ubic_almacen = db_all("
  SELECT idy_ubica, CodigoCSD
  FROM c_ubicacion
  WHERE COALESCE(Activo,1) = 1
  ORDER BY CodigoCSD
");

/* Helpers para combos */
function render_options_almacen(array $rows, string $selected = ''): string {
    $html = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $clave = trim($r['clave']);
        $sel   = ($selected !== '' && $selected === $clave) ? ' selected' : '';
        $html .= '<option value="'.htmlspecialchars($clave).'"'.$sel.'>'.htmlspecialchars($clave).'</option>';
    }
    return $html;
}

function render_options_zonas(array $rows, string $selected = ''): string {
    $html = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $cve  = trim($r['cve_ubicacion']);
        $desc = trim($r['desc_ubicacion'] ?? '');
        $sel  = ($selected !== '' && $selected === $cve) ? ' selected' : '';
        $text = $cve . ($desc !== '' ? ' - '.$desc : '');
        $html .= '<option value="'.htmlspecialchars($cve).'"'.$sel.'>'.htmlspecialchars($text).'</option>';
    }
    return $html;
}

function render_options_ubic_destino(array $rows, string $selected = ''): string {
    $html = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $val = trim($r['CodigoCSD']);
        $sel = ($selected !== '' && $selected === $val) ? ' selected' : '';
        $html .= '<option value="'.htmlspecialchars($val).'"'.$sel.'>'.htmlspecialchars($val).'</option>';
    }
    return $html;
}

/* ================== POST: registrar movimiento de acomodo ================== */

$mensaje_ok  = null;
$mensaje_err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'acomodar') {

    $cve_almac      = trim($_POST['cve_almac'] ?? '');
    $cve_articulo   = trim($_POST['cve_articulo'] ?? '');
    $cve_lote       = trim($_POST['cve_lote'] ?? '');
    $zona_origen    = trim($_POST['zona_origen'] ?? '');
    $ubi_destino    = trim($_POST['ubi_destino'] ?? '');
    $existencia_row = (float)($_POST['existencia'] ?? 0);
    $cantidad       = (float)($_POST['cantidad_mover'] ?? 0);
    $tipo_envio     = $_POST['tipo_existencia'] ?? ''; // TARIMA / PIEZA
    $nTarima        = isset($_POST['nTarima']) ? trim($_POST['nTarima']) : null;

    if ($cve_almac === '' || $cve_articulo === '' || $zona_origen === '' || $ubi_destino === '' || $cantidad <= 0) {
        $mensaje_err = 'Datos incompletos para registrar el acomodo.';
    } elseif ($cantidad > $existencia_row) {
        $mensaje_err = 'La cantidad a acomodar no puede ser mayor a la existencia mostrada.';
    } else {

        try {
            db_tx(function() use ($cve_almac, $cve_articulo, $cve_lote, $zona_origen, $ubi_destino,
                                  $existencia_row, $cantidad, $cve_usuario, $tipo_envio, $nTarima, &$mensaje_ok) {

                // Según Excel Acomodo: Id_TipoMovimiento = 2 --> Acomodo
                $id_tipo = 2;

                // 1) Insertamos en t_cardex (trazabilidad principal)
                dbq("
                    INSERT INTO t_cardex
                    (cve_articulo, cve_lote, fecha, origen, destino,
                     stockinicial, cantidad, ajuste, id_TipoMovimiento,
                     cve_usuario, cve_almac, Activo)
                    VALUES
                    (:cve_articulo, :cve_lote, NOW(), :origen, :destino,
                     :stockinicial, :cantidad, 0, :id_tipo,
                     :cve_usuario, :cve_almac, 1)
                ", [
                    ':cve_articulo' => $cve_articulo,
                    ':cve_lote'     => ($cve_lote === '' ? null : $cve_lote),
                    ':origen'       => $zona_origen,
                    ':destino'      => $ubi_destino,
                    ':stockinicial' => $existencia_row,
                    ':cantidad'     => $cantidad,
                    ':id_tipo'      => $id_tipo,
                    ':cve_usuario'  => $cve_usuario,
                    ':cve_almac'    => $cve_almac,
                ]);

                $id_kardex = db_last_id();

                // 2) (Pendiente) Insert en t_MovCharolas para trazar por tarima / contenedor
                //    Lo dejo comentado a falta del CREATE TABLE real, pero respetando lo del Excel.
                /*
                if ($tipo_envio === 'TARIMA' && $nTarima !== null && $nTarima !== '') {
                    dbq("
                        INSERT INTO t_MovCharolas
                        (Cve_Almac, ID_Contenedor, Fecha, Origen, Destino,
                         Id_TipoMovimiento, Cve_Usuario, Status, EsCaja)
                        VALUES
                        (:cve_almac, :id_contenedor, NOW(), :origen, :destino,
                         :id_tipo, :cve_usuario, 'I', 'N')
                    ", [
                        ':cve_almac'     => $cve_almac,
                        ':id_contenedor' => $nTarima,
                        ':origen'        => $zona_origen,
                        ':destino'       => $ubi_destino,
                        ':id_tipo'       => $id_tipo,
                        ':cve_usuario'   => $cve_usuario,
                    ]);
                }
                */

                $mensaje_ok = 'Acomodo registrado correctamente en Kardex'
                              . ($tipo_envio === 'TARIMA' && $nTarima ? ' (tarima: '.$nTarima.')' : '') . '.';
            });

        } catch (Exception $e) {
            $mensaje_err = $e->getMessage();
        }
    }

    // Post/Redirect/Get
    $params = $_GET;
    if ($mensaje_ok)  { $params['msg_ok']  = urlencode($mensaje_ok); }
    if ($mensaje_err) { $params['msg_err'] = urlencode($mensaje_err); }
    $qs = http_build_query($params);
    header('Location: acomodo.php'.($qs ? ('?'.$qs) : ''));
    exit;
}

// Mensajes después del redirect
if (isset($_GET['msg_ok']))  { $mensaje_ok  = $_GET['msg_ok']; }
if (isset($_GET['msg_err'])) { $mensaje_err = $_GET['msg_err']; }

/* ================== Consulta de existencias en zona de recepción ================== */

$lineas = [];
if ($almacen_sel !== '' && $zona_recibo_sel !== '') {

    $sql = "
        SELECT
            src.tipo_existencia,
            src.cve_almac,
            src.Idy_Ubica,
            u.CodigoCSD      AS bl_origen,
            src.cve_articulo,
            a.des_articulo,
            src.cve_lote,
            src.Existencia,
            src.nTarima
        FROM (
            SELECT
                'TARIMA' AS tipo_existencia,
                t.cve_almac,
                t.Idy_Ubica,
                t.cve_articulo,
                t.cve_lote,
                t.Existencia,
                t.nTarima
            FROM ts_existenciatarima t

            UNION ALL

            SELECT
                'PIEZA' AS tipo_existencia,
                p.cve_almac,
                p.Idy_ubica AS Idy_Ubica,
                p.cve_articulo,
                p.cve_lote,
                p.Existencia,
                NULL       AS nTarima
            FROM ts_existenciapiezas p
        ) AS src
        INNER JOIN c_ubicacion u
            ON u.idy_ubica = src.Idy_Ubica
        INNER JOIN c_articulo a
            ON TRIM(a.cve_articulo) = TRIM(src.cve_articulo)
        WHERE TRIM(src.cve_almac) = TRIM(:almac)
          AND TRIM(u.CodigoCSD)   = TRIM(:zona)
          AND src.Existencia > 0
        ORDER BY a.des_articulo, src.tipo_existencia, src.nTarima
    ";

    $lineas = db_all($sql, [
        ':almac' => $almacen_sel,
        ':zona'  => $zona_recibo_sel,
    ]);
}

$total_lineas   = count($lineas);
$total_unidades = 0;
foreach ($lineas as $l) {
    $total_unidades += (float)$l['Existencia'];
}
?>

<div class="container-fluid" style="font-size:10px;">
  <div class="row mb-2">
    <div class="col-12">
      <h5 class="mt-2 mb-1" style="font-weight:600;">
        Acomodo desde zonas de recepción
      </h5>
      <p class="text-muted mb-2" style="font-size:9px;">
        Mueve existencias desde <strong>zonas de recepción</strong> (ts_existenciatarima / ts_existenciapiezas,
        vinculadas a <code>tubicacionesretencion</code>) hacia ubicaciones de almacenaje (<code>c_ubicacion</code>),
        registrando el movimiento en <code>t_cardex</code> con Id_TipoMovimiento = 2 (ACOMODO).
      </p>
    </div>
  </div>

  <!-- Filtros -->
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
      <label class="form-label mb-0">Almacén (cve_almac)</label>
      <select name="almacen" class="form-select form-select-sm">
        <?= render_options_almacen($cat_almac, $almacen_sel) ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">Zona Origen</label>
      <div class="form-group">
        <label class="mb-0" style="font-size:9px;">Zona de Recepción</label>
        <select name="zona_recibo" id="zona_recibo" class="form-select form-select-sm">
          <?= render_options_zonas($cat_zonas_recibo, $zona_recibo_sel) ?>
        </select>
      </div>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">Ubicación destino sugerida (c_ubicacion.CodigoCSD)</label>
      <select name="ubic_destino_pref" class="form-select form-select-sm">
        <?= render_options_ubic_destino($cat_ubic_almacen, $ubic_destino_pref) ?>
      </select>
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary btn-sm w-100">Aplicar filtros</button>
    </div>
  </form>

  <!-- Mensajes -->
  <?php if ($mensaje_ok): ?>
    <div class="alert alert-success py-1"><?= htmlspecialchars($mensaje_ok) ?></div>
  <?php endif; ?>
  <?php if ($mensaje_err): ?>
    <div class="alert alert-danger py-1"><?= htmlspecialchars($mensaje_err) ?></div>
  <?php endif; ?>

  <!-- Cards resumen -->
  <div class="row mb-3">
    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #0F5AAD;">
        <div class="card-body py-2">
          <div class="fw-semibold">Tipo de movimiento</div>
          <div class="fs-6">ACOMODO</div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #00A3E0;">
        <div class="card-body py-2">
          <div class="fw-semibold">Líneas en zona de recepción</div>
          <div class="fs-6"><?= $total_lineas ?></div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #22c55e;">
        <div class="card-body py-2">
          <div class="fw-semibold">Unidades en zona de recepción</div>
          <div class="fs-6"><?= number_format($total_unidades, 2) ?></div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #f59e0b;">
        <div class="card-body py-2">
          <div class="fw-semibold">Zona de recepción</div>
          <div class="fs-6"><?= htmlspecialchars($zona_recibo_sel ?: 'N/D') ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grilla principal: productos en zona de recepción -->
  <div class="row">
    <div class="col-12">
      <div class="table-responsive">
        <table id="tblAcomodo" class="table table-striped table-bordered table-sm" style="width:100%;">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Tarima / Contenedor</th>
              <th>Clave artículo</th>
              <th>Descripción</th>
              <th>Lote</th>
              <th>BL origen</th>
              <th>Existencia</th>
              <th>Cant. a acomodar</th>
              <th>Ubicación destino</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($lineas as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['tipo_existencia']) ?></td>
              <td><?= htmlspecialchars($row['nTarima']) ?></td>
              <td><?= htmlspecialchars($row['cve_articulo']) ?></td>
              <td><?= htmlspecialchars($row['des_articulo']) ?></td>
              <td><?= htmlspecialchars($row['cve_lote']) ?></td>
              <td><?= htmlspecialchars($row['bl_origen']) ?></td>
              <td class="text-end"><?= number_format($row['Existencia'], 2) ?></td>

              <td style="min-width:110px;">
                <form method="post" class="d-flex align-items-center gap-1">
                  <input type="hidden" name="accion"           value="acomodar">
                  <input type="hidden" name="cve_almac"        value="<?= htmlspecialchars($row['cve_almac']) ?>">
                  <input type="hidden" name="cve_articulo"     value="<?= htmlspecialchars($row['cve_articulo']) ?>">
                  <input type="hidden" name="cve_lote"         value="<?= htmlspecialchars($row['cve_lote']) ?>">
                  <input type="hidden" name="zona_origen"      value="<?= htmlspecialchars($row['bl_origen']) ?>">
                  <input type="hidden" name="existencia"       value="<?= htmlspecialchars($row['Existencia']) ?>">
                  <input type="hidden" name="tipo_existencia"  value="<?= htmlspecialchars($row['tipo_existencia']) ?>">
                  <input type="hidden" name="nTarima"          value="<?= htmlspecialchars($row['nTarima']) ?>">

                  <input type="number"
                         name="cantidad_mover"
                         class="form-control form-control-sm text-end"
                         step="0.0001"
                         min="0.0001"
                         max="<?= htmlspecialchars($row['Existencia']) ?>"
                         value="<?= htmlspecialchars($row['Existencia']) ?>">
              </td>

              <td style="min-width:170px;">
                  <select name="ubi_destino" class="form-select form-select-sm">
                    <?= render_options_ubic_destino($cat_ubic_almacen, $ubic_destino_pref) ?>
                  </select>
              </td>

              <td>
                  <button type="submit" class="btn btn-success btn-sm">
                    Acomodar
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row mt-3 mb-2">
    <div class="col-12 text-end">
      <small>Powered by Adventech Logística 2025</small>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    $('#tblAcomodo').DataTable({
      pageLength: 25,
      lengthChange: false,
      searching: true,
      ordering: true,
      info: true,
      scrollX: true,
      scrollY: '420px',
      scrollCollapse: true,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
      }
    });
  });
</script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
