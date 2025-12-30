<?php
/* ===========================================================
   public/putaway/traslado.php
   PutAway – Traslado de existencias por BL (referencia)
   Filtros: c_compania -> c_almacenp -> Zona (opcional) -> BL
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';
// @session_start();

/* ================= Frame (menú global) =================== */
$activeSection = 'operaciones';
$activeItem    = 'putaway_traslado';
$pageTitle     = 'PutAway · Traslado por BL';
include __DIR__ . '/../bi/_menu_global.php';

/* ================= Parámetros =================== */
$cve_usuario   = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? 'SISTEMA');

$cve_cia_sel       = isset($_GET['cve_cia']) ? (int)$_GET['cve_cia'] : 0;            // c_compania.cve_cia
$almacenp_sel      = isset($_GET['almacenp_id']) ? trim((string)$_GET['almacenp_id']) : ''; // c_almacenp.id (TEXT)
$zona_sel          = isset($_GET['cve_almac']) ? trim((string)$_GET['cve_almac']) : '';     // c_almacen.cve_almac (opcional)
$bl_origen_sel     = isset($_GET['bl_origen']) ? trim((string)$_GET['bl_origen']) : '';    // BL (CodigoCSD)
$bl_destino_pref   = isset($_GET['bl_destino']) ? trim((string)$_GET['bl_destino']) : '';  // sugerido

$mensaje_ok  = isset($_GET['msg_ok']) ? (string)$_GET['msg_ok'] : '';
$mensaje_err = isset($_GET['msg_err']) ? (string)$_GET['msg_err'] : '';

/* ===========================================================
   POST: REGISTRO DE TRASLADO BL->BL (Kardex)
   =========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'trasladar')) {

  $cve_almac    = trim((string)($_POST['cve_almac'] ?? ''));
  $cve_articulo = trim((string)($_POST['cve_articulo'] ?? ''));
  $cve_lote     = trim((string)($_POST['cve_lote'] ?? ''));
  $bl_origen    = trim((string)($_POST['bl_origen'] ?? ''));
  $bl_destino   = trim((string)($_POST['bl_destino'] ?? ''));
  $existencia   = (float)($_POST['existencia'] ?? 0);
  $cantidad     = (float)($_POST['cantidad_mover'] ?? 0);

  $mensaje_ok = '';
  $mensaje_err = '';

  if ($cve_almac === '' || $cve_articulo === '' || $bl_origen === '' || $bl_destino === '' || $cantidad <= 0) {
    $mensaje_err = 'Datos incompletos para registrar el traslado.';
  } elseif ($cantidad > $existencia) {
    $mensaje_err = 'La cantidad a trasladar no puede ser mayor a la existencia.';
  } else {
    try {
      db_tx(function () use ($cve_almac, $cve_articulo, $cve_lote, $bl_origen, $bl_destino, $existencia, $cantidad, $cve_usuario, &$mensaje_ok) {

        // Id_TipoMovimiento para TRASLADO BL->BL (ajusta al id real si aplica)
        $id_tipo = 3;

        dbq("
          INSERT INTO t_cardex
          (cve_articulo,cve_lote,fecha,origen,destino,
           stockinicial,cantidad,ajuste,id_TipoMovimiento,
           cve_usuario,cve_almac,Activo)
          VALUES
          (:a,:l,NOW(),:o,:d,
           :s,:c,0,:t,
           :u,:alm,1)
        ", [
          ':a'   => $cve_articulo,
          ':l'   => ($cve_lote === '' ? null : $cve_lote),
          ':o'   => $bl_origen,
          ':d'   => $bl_destino,
          ':s'   => $existencia,
          ':c'   => $cantidad,
          ':t'   => $id_tipo,
          ':u'   => $cve_usuario,
          ':alm' => $cve_almac,
        ]);

        $mensaje_ok = 'Traslado registrado correctamente en Kardex.';
      });

    } catch (Throwable $e) {
      $mensaje_err = 'Error al registrar el traslado: ' . $e->getMessage();
    }
  }

  // Mantener filtros en redirect
  $params = $_GET;
  if ($mensaje_ok)  $params['msg_ok']  = $mensaje_ok;
  if ($mensaje_err) $params['msg_err'] = $mensaje_err;

  header('Location: traslado.php?' . http_build_query($params));
  exit;
}

/* ===========================================================
   CATÁLOGOS (c_compania / c_almacenp / c_almacen / BLs)
   =========================================================== */

// 1) Compañías
$cias = db_all("
  SELECT cve_cia, clave_empresa, des_cia
  FROM c_compania
  WHERE IFNULL(Activo,1)=1
  ORDER BY des_cia ASC
");

// 2) Almacenes principales (c_almacenp) filtrados por compañía (ap.cve_cia es TEXT)
// JOIN blindado: a.cve_almacenp (INT) = CAST(ap.id AS UNSIGNED)
$almParams = [];
$almWhere  = ["COALESCE(ap.Activo,1)=1"];

if ($cve_cia_sel > 0) {
  // ✅ FIX 1267/1253: Convertimos ambos lados a TEXTO utf8mb4 antes de collation
  $almWhere[] = "TRIM(CONVERT(IFNULL(ap.cve_cia,'') USING utf8mb4)) COLLATE utf8mb4_unicode_ci
                 = TRIM(CAST(:cia AS CHAR CHARACTER SET utf8mb4)) COLLATE utf8mb4_unicode_ci";
  $almParams[':cia'] = (string)$cve_cia_sel;
}

$almacenesP = db_all("
  SELECT DISTINCT
    ap.id AS almacenp_id,
    ap.clave,
    ap.nombre
  FROM c_almacenp ap
  INNER JOIN c_almacen a
    ON a.cve_almacenp = CAST(ap.id AS UNSIGNED)
  WHERE " . implode(" AND ", $almWhere) . "
  ORDER BY ap.nombre ASC
", $almParams);

// 3) Zonas por almacén principal (opcional)
$zonas = [];
if ($almacenp_sel !== '') {
  $zonas = db_all("
    SELECT cve_almac, des_almac
    FROM c_almacen
    WHERE cve_almacenp = CAST(:id AS UNSIGNED)
      AND COALESCE(Activo,1)=1
    ORDER BY des_almac ASC
  ", [':id' => $almacenp_sel]);
}

// 4) BLs (CodigoCSD) acotados por zona si existe, o por almacén principal si no hay zona
$cat_bls = [];
$whereBL = ["COALESCE(u.Activo,1)=1", "TRIM(IFNULL(u.CodigoCSD,''))<>''"];
$paramsBL = [];

if ($zona_sel !== '') {
  $whereBL[] = "u.cve_almac = :cve_almac";
  $paramsBL[':cve_almac'] = $zona_sel;
} elseif ($almacenp_sel !== '') {
  $whereBL[] = "aZ.cve_almacenp = CAST(:cap AS UNSIGNED)";
  $paramsBL[':cap'] = $almacenp_sel;
}

$cat_bls = db_all("
  SELECT DISTINCT u.CodigoCSD
  FROM c_ubicacion u
  INNER JOIN c_almacen aZ ON aZ.cve_almac = u.cve_almac
  WHERE " . implode(" AND ", $whereBL) . "
  ORDER BY u.CodigoCSD ASC
", $paramsBL);

/* ===========================================================
   HELPERS COMBOS
   =========================================================== */
function optCias(array $rows, int $sel): string {
  $h = '<option value="">Seleccione</option>';
  foreach ($rows as $r) {
    $v = (int)($r['cve_cia'] ?? 0);
    if ($v <= 0) continue;
    $t = trim((string)($r['des_cia'] ?? ''));
    $s = ($v === $sel) ? ' selected' : '';
    $h .= '<option value="'.htmlspecialchars((string)$v).'"'.$s.'>'
        . htmlspecialchars($v.' - '.$t).'</option>';
  }
  return $h;
}

function optAlmP(array $rows, string $sel): string {
  $h = '<option value="">Seleccione</option>';
  foreach ($rows as $r) {
    $v = trim((string)($r['almacenp_id'] ?? ''));
    if ($v === '') continue;
    $t = trim((string)($r['nombre'] ?? $v));
    $s = ($v === $sel) ? ' selected' : '';
    $h .= '<option value="'.htmlspecialchars($v).'"'.$s.'>'
        . htmlspecialchars($t.' ('.$v.')').'</option>';
  }
  return $h;
}

function optZonas(array $rows, string $sel): string {
  $h = '<option value="">(Opcional) Todas</option>';
  foreach ($rows as $r) {
    $v = trim((string)($r['cve_almac'] ?? ''));
    if ($v === '') continue;
    $t = trim((string)($r['des_almac'] ?? $v));
    $s = ($v === $sel) ? ' selected' : '';
    $h .= '<option value="'.htmlspecialchars($v).'"'.$s.'>'
        . htmlspecialchars($v.' - '.$t).'</option>';
  }
  return $h;
}

function optBLTraslado(array $rows, string $sel): string {
  $h = '<option value="">Seleccione</option>';
  foreach ($rows as $r) {
    $v = trim((string)($r['CodigoCSD'] ?? ''));
    if ($v === '') continue;
    $s = ($v === $sel) ? ' selected' : '';
    $h .= '<option value="'.htmlspecialchars($v).'"'.$s.'>'
        . htmlspecialchars($v).'</option>';
  }
  return $h;
}

/* ===========================================================
   CONSULTA: EXISTENCIAS EN BL ORIGEN (BL manda)
   =========================================================== */
$lineas = [];
$total_lineas = 0;
$total_unidades = 0.0;

if ($bl_origen_sel !== '') {

  $params = [':bl' => $bl_origen_sel];
  $where  = [
    // ✅ FIX 1267/1253: BL siempre como CHAR utf8mb4 antes de comparar
    "TRIM(CONVERT(u.CodigoCSD USING utf8mb4)) COLLATE utf8mb4_unicode_ci
       = TRIM(CAST(:bl AS CHAR CHARACTER SET utf8mb4)) COLLATE utf8mb4_unicode_ci",
    "src.Existencia > 0"
  ];

  if ($zona_sel !== '') {
    $where[] = "TRIM(src.cve_almac) = TRIM(:zona)";
    $params[':zona'] = $zona_sel;
  } elseif ($almacenp_sel !== '') {
    $where[] = "aZ.cve_almacenp = CAST(:cap AS UNSIGNED)";
    $params[':cap'] = $almacenp_sel;
  }

  $sql = "
    SELECT
      src.tipo_existencia,
      src.cve_almac,
      src.Idy_Ubica,
      u.CodigoCSD AS bl_origen,
      src.cve_articulo,
      art.des_articulo,
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
        NULL AS nTarima
      FROM ts_existenciapiezas p
    ) AS src
    INNER JOIN c_ubicacion u
      ON u.idy_ubica = src.Idy_Ubica
    INNER JOIN c_almacen aZ
      ON aZ.cve_almac = src.cve_almac
    INNER JOIN c_articulo art
      ON TRIM(CONVERT(art.cve_articulo USING utf8mb4)) COLLATE utf8mb4_unicode_ci
         = TRIM(CONVERT(src.cve_articulo USING utf8mb4)) COLLATE utf8mb4_unicode_ci
    WHERE " . implode(" AND ", $where) . "
    ORDER BY art.des_articulo, src.tipo_existencia, src.nTarima
  ";

  $lineas = db_all($sql, $params);

  $total_lineas = count($lineas);
  foreach ($lineas as $r) {
    $total_unidades += (float)$r['Existencia'];
  }
}
?>
<div class="container-fluid" style="font-size:10px;">

  <h5 class="mt-2 mb-1">PutAway · Traslado por BL (referencia)</h5>
  <p class="text-muted mb-1" style="font-size:9px;">
    El <strong>BL</strong> es la referencia operativa; la grilla se llena sólo si existe <strong>existencia</strong> en ese BL.
  </p>

  <?php if ($mensaje_ok): ?>
    <div class="alert alert-success py-1"><?= htmlspecialchars($mensaje_ok) ?></div>
  <?php endif; ?>
  <?php if ($mensaje_err): ?>
    <div class="alert alert-danger py-1"><?= htmlspecialchars($mensaje_err) ?></div>
  <?php endif; ?>

  <!-- Filtros -->
  <form method="get" class="row g-2 mb-3 align-items-end">

    <div class="col-md-3">
      <label class="form-label mb-0">Compañía</label>
      <select name="cve_cia" class="form-select form-select-sm" onchange="this.form.submit()">
        <?= optCias($cias, $cve_cia_sel) ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label mb-0">Almacén</label>
      <select name="almacenp_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <?= optAlmP($almacenesP, $almacenp_sel) ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label mb-0">Zona de almacenaje (opcional)</label>
      <select name="cve_almac" class="form-select form-select-sm" onchange="this.form.submit()">
        <?= optZonas($zonas, $zona_sel) ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label mb-0">BL origen</label>
      <select name="bl_origen" class="form-select form-select-sm">
        <?= optBLTraslado($cat_bls, $bl_origen_sel) ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label mb-0">BL destino sugerido</label>
      <select name="bl_destino" class="form-select form-select-sm">
        <?= optBLTraslado($cat_bls, $bl_destino_pref) ?>
      </select>
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary btn-sm w-100">Aplicar filtros</button>
    </div>

  </form>

  <!-- Resumen -->
  <div class="row mb-3">
    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #0F5AAD;">
        <div class="card-body py-2">
          <div class="fw-semibold">Tipo movimiento</div>
          <div class="fs-6">TRASLADO BL → BL</div>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-2">
      <div class="card shadow-sm" style="border-left:4px solid #00A3E0;">
        <div class="card-body py-2">
          <div class="fw-semibold">Líneas en BL origen</div>
          <div class="fs-6"><?= (int)$total_lineas ?></div>
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
    <table id="tblTrasladoBL" class="table table-striped table-bordered table-sm" style="width:100%;">
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
          <th>BL destino</th>
          <th>Acción</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($lineas as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['tipo_existencia']) ?></td>
            <td><?= htmlspecialchars((string)$row['nTarima']) ?></td>
            <td><?= htmlspecialchars($row['cve_articulo']) ?></td>
            <td><?= htmlspecialchars($row['des_articulo']) ?></td>
            <td><?= htmlspecialchars((string)$row['cve_lote']) ?></td>
            <td><?= htmlspecialchars($row['bl_origen']) ?></td>
            <td class="text-end"><?= number_format((float)$row['Existencia'], 2) ?></td>

            <td style="min-width:120px;">
              <form method="post" class="d-flex align-items-center gap-1">
                <input type="hidden" name="accion" value="trasladar">
                <input type="hidden" name="cve_almac" value="<?= htmlspecialchars($row['cve_almac']) ?>">
                <input type="hidden" name="cve_articulo" value="<?= htmlspecialchars($row['cve_articulo']) ?>">
                <input type="hidden" name="cve_lote" value="<?= htmlspecialchars((string)$row['cve_lote']) ?>">
                <input type="hidden" name="bl_origen" value="<?= htmlspecialchars($row['bl_origen']) ?>">
                <input type="hidden" name="existencia" value="<?= htmlspecialchars((string)$row['Existencia']) ?>">

                <input type="number"
                       name="cantidad_mover"
                       class="form-control form-control-sm text-end"
                       step="0.0001"
                       min="0.0001"
                       max="<?= htmlspecialchars((string)$row['Existencia']) ?>"
                       value="<?= htmlspecialchars((string)$row['Existencia']) ?>">
            </td>

            <td style="min-width:150px;">
              <select name="bl_destino" class="form-select form-select-sm">
                <?= optBLTraslado($cat_bls, $bl_destino_pref) ?>
              </select>
            </td>

            <td>
              <button type="submit" class="btn btn-success btn-sm">Trasladar</button>
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
    if (window.jQuery && $('#tblTrasladoBL').length) {
      $('#tblTrasladoBL').DataTable({
        pageLength: 25,
        lengthChange: false,
        searching: true,
        ordering: true,
        info: true,
        scrollX: true,
        scrollY: '50vh',
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' }
      });
    }
  });
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
