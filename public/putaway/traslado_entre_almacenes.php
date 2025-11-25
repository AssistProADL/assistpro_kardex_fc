<?php
/* ===========================================================
   public/procesos/traslado_entre_almacenes.php
   PutAway – Traslado entre almacenes:
   BL origen (almacén origen) -> Zona de recepción (almacén destino)
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';
session_start();

/* ================= Frame (menú global) =================== */
$activeSection = 'operaciones';
$activeItem    = 'putaway_traslado_almacenes'; // ajusta a tu menú
$pageTitle     = 'PutAway · Traslado entre almacenes';
include __DIR__ . '/../bi/_menu_global.php';

/* ================= Parámetros =================== */
$cve_usuario        = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? 'SISTEMA');

$almacen_origen_sel = isset($_GET['almacen_origen']) ? trim($_GET['almacen_origen']) : '';
$bl_origen_sel      = isset($_GET['bl_origen'])       ? trim($_GET['bl_origen'])       : '';
$almacen_dest_sel   = isset($_GET['almacen_dest'])    ? trim($_GET['almacen_dest'])    : '';
$zona_dest_sel      = isset($_GET['zona_dest'])       ? trim($_GET['zona_dest'])       : '';

$api_debug = '';

/* ===========================================================
   FUNCIÓN: consumir filtros_assistpro.php (lado servidor)
   =========================================================== */
function api_filtros_init_traslado_alm(?string $almacen, ?string &$error = null): ?array
{
    $almacen = $almacen ?? '';

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    $pos = strpos($script, '/public/');
    if ($pos === false) {
        $error = 'No se detectó /public/ en SCRIPT_NAME para armar URL del API.';
        return null;
    }

    $publicRoot = substr($script, 0, $pos + 8);
    $apiPath    = $publicRoot . 'api/filtros_assistpro.php';

    $url = $scheme . '://' . $host . $apiPath
         . '?action=init&almacen=' . urlencode($almacen);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 5,
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        $error = "No se pudo llamar al API filtros_assistpro ($url)";
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        $error = 'Respuesta JSON inválida desde filtros_assistpro.';
        return null;
    }

    if (isset($data['ok']) && $data['ok'] === false) {
        $error = 'API devolvió error: ' . ($data['error'] ?? 'Error desconocido');
        return null;
    }

    return $data;
}

/* ===========================================================
   CATÁLOGOS (API + FALLBACK)
   =========================================================== */

$cat_almac   = [];
$cat_bls     = [];
$cat_zonas   = [];

$api_error = null;
$api_data  = api_filtros_init_traslado_alm($almacen_origen_sel, $api_error);

if ($api_data !== null) {
    $api_debug = 'Catálogos desde filtros_assistpro.php';

    foreach ($api_data['almacenes'] ?? [] as $a) {
        $cat_almac[] = [
            'clave'  => trim($a['cve_almac'] ?? ''),
            'nombre' => trim($a['des_almac'] ?? ''),
        ];
    }

    foreach ($api_data['bls'] ?? [] as $b) {
        $bl = trim($b['bl'] ?? '');
        if ($bl !== '') {
            $cat_bls[] = ['CodigoCSD' => $bl];
        }
    }

    // Zonas de recepción: podemos filtrar por almacen_dest si viene cve_almacp
    foreach ($api_data['zonas_recep'] ?? [] as $z) {
        $row = [
            'cve_ubicacion'  => trim($z['cve_ubicacion'] ?? ''),
            'desc_ubicacion' => trim($z['desc_ubicacion'] ?? ''),
            'cve_almacp'     => trim($z['cve_almacp'] ?? ''),
        ];
        if ($almacen_dest_sel !== '' && $row['cve_almacp'] !== '' &&
            $row['cve_almacp'] !== $almacen_dest_sel) {
            continue;
        }
        $cat_zonas[] = $row;
    }

} else {
    $api_debug = $api_error ?: 'Error al llamar API; usando consultas locales.';

    $cat_almac = db_all("
        SELECT clave AS clave, clave AS nombre
        FROM c_almacenp
        WHERE COALESCE(Activo,1)=1
        ORDER BY clave
    ");

    $paramsBL = [];
    $whereBL  = ["COALESCE(Activo,1)=1", "IFNULL(CodigoCSD,'')<>''"];
    if ($almacen_origen_sel !== '') {
        $whereBL[]              = 'cve_almac = :alm';
        $paramsBL['alm']        = $almacen_origen_sel;
    }
    $sqlBL = "
        SELECT CodigoCSD
        FROM c_ubicacion
        WHERE " . implode(' AND ', $whereBL) . "
        ORDER BY CodigoCSD
    ";
    $cat_bls = db_all($sqlBL, $paramsBL);

    $cat_zonas = db_all("
        SELECT
            cve_ubicacion,
            desc_ubicacion,
            cve_almacp
        FROM tubicacionesretencion
        WHERE COALESCE(Activo,1)=1
        ORDER BY desc_ubicacion
    ");
}

/* ===========================================================
   HELPERS COMBOS
   =========================================================== */
function optAlmacenTA(array $rows, string $sel): string {
    $h = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $v = trim($r['clave'] ?? '');
        if ($v === '') continue;
        $t = trim($r['nombre'] ?? $v);
        $s = ($v === $sel) ? ' selected' : '';
        $h .= '<option value="'.htmlspecialchars($v).'"'.$s.'>'
            . htmlspecialchars($v.' - '.$t).'</option>';
    }
    return $h;
}

function optBLTA(array $rows, string $sel): string {
    $h = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $v = trim($r['CodigoCSD'] ?? '');
        if ($v === '') continue;
        $s = ($v === $sel) ? ' selected' : '';
        $h .= '<option value="'.htmlspecialchars($v).'"'.$s.'>'
            . htmlspecialchars($v).'</option>';
    }
    return $h;
}

function optZonaTA(array $rows, string $sel): string {
    $h = '<option value="">Seleccione</option>';
    foreach ($rows as $r) {
        $v = trim($r['cve_ubicacion'] ?? '');
        if ($v === '') continue;
        $d = trim($r['desc_ubicacion'] ?? '');
        $s = ($v === $sel) ? ' selected' : '';
        $label = $v . ($d ? ' - ' . $d : '');
        $h .= '<option value="'.htmlspecialchars($v).'"'.$s.'>'
            . htmlspecialchars($label).'</option>';
    }
    return $h;
}

/* ===========================================================
   POST: REGISTRO DE TRASLADO ENTRE ALMACENES
   =========================================================== */
$mensaje_ok  = '';
$mensaje_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'trasladar_alm') {

    $alm_origen   = trim($_POST['almacen_origen'] ?? '');
    $alm_dest     = trim($_POST['almacen_dest'] ?? '');
    $bl_origen    = trim($_POST['bl_origen'] ?? '');
    $zona_dest    = trim($_POST['zona_dest'] ?? '');
    $cve_articulo = trim($_POST['cve_articulo'] ?? '');
    $cve_lote     = trim($_POST['cve_lote'] ?? '');
    $existencia   = (float)($_POST['existencia'] ?? 0);
    $cantidad     = (float)($_POST['cantidad_mover'] ?? 0);

    if ($alm_origen === '' || $alm_dest === '' || $bl_origen === '' || $zona_dest === '' || $cve_articulo === '' || $cantidad <= 0) {
        $mensaje_err = 'Datos incompletos para registrar el traslado entre almacenes.';
    } elseif ($cantidad > $existencia) {
        $mensaje_err = 'La cantidad a trasladar no puede ser mayor a la existencia.';
    } else {
        try {
            db_tx(function() use ($alm_origen,$alm_dest,$bl_origen,$zona_dest,
                                  $cve_articulo,$cve_lote,
                                  $existencia,$cantidad,
                                  $cve_usuario,&$mensaje_ok) {

                // Id_TipoMovimiento para TRASLADO ENTRE ALMACENES (ajusta al id real)
                $id_tipo = 4;

                $origen_txt  = "ALM_ORIG: {$alm_origen} / BL: {$bl_origen}";
                $destino_txt = "ALM_DEST: {$alm_dest} / ZONA: {$zona_dest}";

                dbq("
                    INSERT INTO t_cardex
                    (cve_articulo,cve_lote,fecha,origen,destino,
                     stockinicial,cantidad,ajuste,id_TipoMovimiento,
                     cve_usuario,cve_almac,Activo)
                    VALUES
                    (:a,:l,NOW(),:o,:d,
                     :s,:c,0,:t,
                     :u,:alm_dest,1)
                ", [
                    ':a'        => $cve_articulo,
                    ':l'        => ($cve_lote === '' ? null : $cve_lote),
                    ':o'        => $origen_txt,
                    ':d'        => $destino_txt,
                    ':s'        => $existencia,
                    ':c'        => $cantidad,
                    ':t'        => $id_tipo,
                    ':u'        => $cve_usuario,
                    ':alm_dest' => $alm_dest,
                ]);

                // Aquí luego podrás implementar la lógica de mover stock
                // entre ts_existenciatarima/ts_existenciapiezas de un almacén a otro.

                $mensaje_ok = 'Traslado entre almacenes registrado correctamente en Kardex.';
            });

        } catch (Throwable $e) {
            $mensaje_err = 'Error al registrar traslado entre almacenes: ' . $e->getMessage();
        }
    }

    $params = $_GET;
    if ($mensaje_ok)  $params['msg_ok']  = urlencode($mensaje_ok);
    if ($mensaje_err) $params['msg_err'] = urlencode($mensaje_err);
    header('Location: traslado_entre_almacenes.php?' . http_build_query($params));
    exit;
}

$mensaje_ok  = $_GET['msg_ok']  ?? $mensaje_ok;
$mensaje_err = $_GET['msg_err'] ?? $mensaje_err;

/* ===========================================================
   CONSULTA DE EXISTENCIAS EN BL ORIGEN (ALMACÉN ORIGEN)
   =========================================================== */
$lineas         = [];
$total_lineas   = 0;
$total_unidades = 0.0;

if ($almacen_origen_sel !== '' && $bl_origen_sel !== '') {

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
          AND TRIM(u.CodigoCSD)   = TRIM(:bl)
          AND src.Existencia > 0
        ORDER BY a.des_articulo, src.tipo_existencia, src.nTarima
    ";

    $lineas = db_all($sql, [
        ':almac' => $almacen_origen_sel,
        ':bl'    => $bl_origen_sel,
    ]);

    $total_lineas = count($lineas);
    foreach ($lineas as $r) {
        $total_unidades += (float)$r['Existencia'];
    }
}
?>
<div class="container-fluid" style="font-size:10px;">

  <h5 class="mt-2 mb-1">PutAway · Traslado entre almacenes</h5>
  <p class="text-muted mb-1" style="font-size:9px;">
    Mueve existencias desde un <strong>BL origen</strong> en el
    <strong>almacén origen</strong> hacia una <strong>zona de recepción</strong>
    en el <strong>almacén destino</strong>.
  </p>

  <?php if ($api_debug): ?>
    <p class="text-muted mb-2" style="font-size:9px;">
      <strong>Debug API:</strong> <?= htmlspecialchars($api_debug) ?>
    </p>
  <?php endif; ?>

  <?php if ($mensaje_ok): ?>
    <div class="alert alert-success py-1"><?= htmlspecialchars($mensaje_ok) ?></div>
  <?php endif; ?>
  <?php if ($mensaje_err): ?>
    <div class="alert alert-danger py-1"><?= htmlspecialchars($mensaje_err) ?></div>
  <?php endif; ?>

  <!-- Filtros -->
  <form method="get" class="row g-2 mb-3">

    <div class="col-md-3">
      <label class="form-label mb-0">Almacén origen</label>
      <select name="almacen_origen" class="form-select form-select-sm">
        <?= optAlmacenTA($cat_almac, $almacen_origen_sel) ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">BL origen</label>
      <select name="bl_origen" class="form-select form-select-sm">
        <?= optBLTA($cat_bls, $bl_origen_sel) ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">Almacén destino</label>
      <select name="almacen_dest" class="form-select form-select-sm">
        <?= optAlmacenTA($cat_almac, $almacen_dest_sel) ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">Zona de recepción destino</label>
      <select name="zona_dest" class="form-select form-select-sm">
        <?= optZonaTA($cat_zonas, $zona_dest_sel) ?>
      </select>
    </div>

    <div class="col-md-2 d-flex align-items-end mt-2">
      <button type="submit" class="btn btn-primary btn-sm w-100">
        Aplicar filtros
      </button>
    </div>
  </form>

  <!-- Resumen -->
  <div class="row mb-3">
    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #0F5AAD;">
        <div class="card-body py-2">
          <div class="fw-semibold">Movimiento</div>
          <div class="fs-6">Traslado entre almacenes</div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #00A3E0;">
        <div class="card-body py-2">
          <div class="fw-semibold">Líneas en BL origen</div>
          <div class="fs-6"><?= $total_lineas ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #22c55e;">
        <div class="card-body py-2">
          <div class="fw-semibold">Unidades en BL origen</div>
          <div class="fs-6"><?= number_format($total_unidades, 2) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grilla -->
  <div class="table-responsive">
    <table id="tblTrasladoAlm" class="table table-striped table-bordered table-sm" style="width:100%;">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Tarima / Contenedor</th>
          <th>Clave artículo</th>
          <th>Descripción</th>
          <th>Lote</th>
          <th>BL origen</th>
          <th>Existencia</th>
          <th>Cant. a trasladar</th>
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

          <td style="min-width:120px;">
            <form method="post" class="d-flex align-items-center gap-1">
              <input type="hidden" name="accion"          value="trasladar_alm">
              <input type="hidden" name="almacen_origen"  value="<?= htmlspecialchars($almacen_origen_sel) ?>">
              <input type="hidden" name="almacen_dest"    value="<?= htmlspecialchars($almacen_dest_sel) ?>">
              <input type="hidden" name="bl_origen"       value="<?= htmlspecialchars($row['bl_origen']) ?>">
              <input type="hidden" name="zona_dest"       value="<?= htmlspecialchars($zona_dest_sel) ?>">
              <input type="hidden" name="cve_articulo"    value="<?= htmlspecialchars($row['cve_articulo']) ?>">
              <input type="hidden" name="cve_lote"        value="<?= htmlspecialchars($row['cve_lote']) ?>">
              <input type="hidden" name="existencia"      value="<?= htmlspecialchars($row['Existencia']) ?>">

              <input type="number"
                     name="cantidad_mover"
                     class="form-control form-control-sm text-end"
                     step="0.0001"
                     min="0.0001"
                     max="<?= htmlspecialchars($row['Existencia']) ?>"
                     value="<?= htmlspecialchars($row['Existencia']) ?>">
          </td>

          <td>
              <button type="submit" class="btn btn-success btn-sm">
                Trasladar
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="row mt-3 mb-2">
    <div class="col-12 text-end">
      <small>Powered by Adventech Logística 2025</small>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#tblTrasladoAlm').DataTable({
    pageLength: 25,
    lengthChange: false,
    searching: true,
    ordering: true,
    info: true,
    scrollX: true,
    scrollY: '50vh',
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
    }
  });
});
</script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
