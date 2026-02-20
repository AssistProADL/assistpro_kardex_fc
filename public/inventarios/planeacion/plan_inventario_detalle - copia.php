<?php
/**
 * plan_inventario_detalle.php (refactorizado)
 * Vista “excel-like” para ejecutar inventario desde la misma pantalla:
 * - Alcance (ubicaciones/BL) a la izquierda
 * - Grilla principal (detalle teórico) a la derecha, con scroll H/V, header sticky, filtros
 * - Fuente de existencia: v_inv_existencia_multinivel (mismo core del API existencias_ubicacion_total.php)
 *
 * Reglas:
 * - NO usar t_ubicacionesinventarias / t_ubicacionesainventariar como fuente “ideal”.
 * - El BL visual es c_ubicacion.CodigoCSD y el id técnico es c_ubicacion.idy_ubica.
 * - Para LP mostrar CveLP (y opcional el IDContenedor).
 */

require_once __DIR__ . '/../../bi/_menu_global.php';

/* =========================
   Cargar DB (db.php)
   ========================= */
$dbCandidates = [
  __DIR__ . '/../../../app/db.php',
  __DIR__ . '/../../app/db.php',
  __DIR__ . '/../app/db.php',
  __DIR__ . '/../../../../app/db.php',
];

$dbLoaded = false;
foreach ($dbCandidates as $p) {
  if (file_exists($p)) { require_once $p; $dbLoaded = true; break; }
}
if (!$dbLoaded) {
  http_response_code(500);
  echo "<pre>ERROR: No se encontró app/db.php</pre>";
  exit;
}

/** helpers DB compatibles */
function _pdo() {
  if (function_exists('db')) return db();
  global $pdo;
  if ($pdo instanceof PDO) return $pdo;
  throw new Exception("No hay conexión DB disponible (db() / \$pdo).");
}
function db_one_safe($sql, $params = []) {
  $pdo = _pdo();
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}
function db_all_safe($sql, $params = []) {
  $pdo = _pdo();
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function pick($arr, $keys, $default=null){
  foreach((array)$keys as $k){
    if (is_array($arr) && array_key_exists($k,$arr) && $arr[$k] !== null) return $arr[$k];
  }
  return $default;
}

/* =========================
   Inputs
   ========================= */
$folio     = $_GET['folio'] ?? '';
$idy_ubica = $_GET['idy_ubica'] ?? null; // selección de ubicación para “foto teórica”
$q         = trim($_GET['q'] ?? '');      // filtro rápido en grilla (artículo/lote/contenedor/CveLP/tipo)
$limit     = (int)($_GET['limit'] ?? 2000);

if ($folio === '') {
  echo "<div class='container-fluid p-4'><div class='alert alert-danger'>Falta parámetro <b>folio</b>.</div></div>";
  require_once __DIR__ . '/../../bi/_menu_global_end.php';
  exit;
}

/* =========================
   Obtener cabecera plan
   =========================
   th_plan_inventarios (fuente de planeación existente)
*/
$plan = db_one_safe("SELECT * FROM th_plan_inventarios WHERE folio = :folio LIMIT 1", [':folio'=>$folio]);
if (!$plan) {
  echo "<div class='container-fluid p-4'><div class='alert alert-danger'>No existe plan para folio <b>".h($folio)."</b>.</div></div>";
  require_once __DIR__ . '/../../bi/_menu_global_end.php';
  exit;
}

$planId     = (int)pick($plan, ['id','ID','Id','id_plan'], 0);
$estado     = (string)pick($plan, ['estado','Estatus','status'], 'CREADO');
$tipo       = (string)pick($plan, ['tipo','Tipo'], 'FISICO');
$fechaPlan  = pick($plan, ['fecha_programada','FechaProgramada','fecha','fec_prog'], null);
$bls_json   = pick($plan, ['bls_json','BLs_json','bl_json','json_bls'], null);

/* =========================
   Alcance (ubicaciones)
   =========================
   Estratégicamente: mapeamos BLs planeados (CodigoCSD) -> c_ubicacion.idy_ubica.
   Si bls_json viene vacío, el alcance queda vacío (y se habilita “Generar alcance” en otra acción).
*/
$alcance = [];
$bls = [];
if ($bls_json) {
  $decoded = json_decode($bls_json, true);
  if (is_array($decoded)) {
    // soporta: ["BL001","BL002"] o [{"bl":"BL001"},...]
    foreach ($decoded as $it) {
      if (is_string($it)) $bls[] = $it;
      elseif (is_array($it)) {
        $b = $it['bl'] ?? $it['CodigoCSD'] ?? $it['codigoCSD'] ?? $it['BL'] ?? null;
        if ($b) $bls[] = $b;
      }
    }
    $bls = array_values(array_unique(array_filter($bls)));
  }
}

if ($bls) {
  // traer ubicaciones por CodigoCSD
  $in = implode(',', array_fill(0, count($bls), '?'));
  $rowsUb = db_all_safe("
    SELECT
      u.idy_ubica,
      u.CodigoCSD,
      u.cve_pasillo,
      u.cve_rack,
      u.cve_nivel,
      u.cve_almac
    FROM c_ubicacion u
    WHERE u.CodigoCSD IN ($in)
    ORDER BY u.CodigoCSD
  ", $bls);

  // index por CodigoCSD
  $idx = [];
  foreach($rowsUb as $r){ $idx[$r['CodigoCSD']] = $r; }

  // construir alcance respetando orden del json
  foreach($bls as $bl){
    $r = $idx[$bl] ?? null;
    if ($r) $alcance[] = $r;
    else {
      $alcance[] = [
        'idy_ubica'   => null,
        'CodigoCSD'   => $bl,
        'cve_pasillo' => null,
        'cve_rack'    => null,
        'cve_nivel'   => null,
        'cve_almac'   => null,
        '_missing'    => 1
      ];
    }
  }
}

$blsPlaneados = count($bls);
$ubicasGen    = count(array_filter($alcance, fn($x)=>!empty($x['idy_ubica'])));

/* =========================
   Snapshot teórico (grilla)
   =========================
   Fuente: v_inv_existencia_multinivel + c_charolas para CveLP
   Filtro por idy_ubica (selección), y filtro rápido q (articulo/lote/contenedor/CveLP/tipo)
*/
$snapHeader = null;
$snapRows   = [];
$sumRows    = [];

if ($idy_ubica !== null && $idy_ubica !== '') {
  $idy_ubica = (int)$idy_ubica;

  $snapHeader = db_one_safe("
    SELECT
      u.idy_ubica,
      u.CodigoCSD,
      u.cve_pasillo,
      u.cve_rack,
      u.cve_nivel,
      u.cve_almac
    FROM c_ubicacion u
    WHERE u.idy_ubica = :id
    LIMIT 1
  ", [':id'=>$idy_ubica]);

  // WHERE dinámico
  $where = ["v.idy_ubica = :idy"];
  $bind  = [':idy'=>$idy_ubica, ':lim'=>$limit];

  // filtro rápido tipo excel
  if ($q !== '') {
    // match flexible: articulo, lote, BL, id_caja, nTarima, CveLP
    $where[] = "(
      v.cve_articulo LIKE :q OR
      v.cve_lote LIKE :q OR
      v.bl LIKE :q OR
      CAST(v.id_caja AS CHAR) LIKE :q OR
      CAST(v.nTarima AS CHAR) LIKE :q OR
      ch.CveLP LIKE :q OR
      v.code LIKE :q OR
      v.epc LIKE :q
    )";
    $bind[':q'] = "%".$q."%";
  }

  $whereSQL = "WHERE ".implode(" AND ", $where);

  // Grilla principal (detalle)
  $snapRows = db_all_safe("
    SELECT
      v.bl,
      v.idy_ubica,
      v.cve_almac,
      v.nivel,
      v.cve_articulo,
      v.cve_lote,
      v.id_caja,
      v.nTarima,
      ch.CveLP AS CveLP,
      v.cantidad,
      v.fuente,
      v.code,
      v.epc
    FROM v_inv_existencia_multinivel v
    LEFT JOIN c_charolas ch
      ON ch.IDContenedor = v.nTarima
    $whereSQL
    AND v.cantidad > 0
    ORDER BY v.cve_articulo, v.cve_lote, v.nTarima, v.id_caja
    LIMIT :lim
  ", $bind);

  // Totales por artículo/lote (resumen)
  $sumRows = db_all_safe("
    SELECT
      v.cve_articulo,
      v.cve_lote,
      SUM(v.cantidad) AS cantidad
    FROM v_inv_existencia_multinivel v
    $whereSQL
    AND v.cantidad > 0
    GROUP BY v.cve_articulo, v.cve_lote
    ORDER BY v.cve_articulo, v.cve_lote
    LIMIT :lim
  ", $bind);
}

/* =========================
   UI
   ========================= */
?>
<style>
  .kpi-card{border:1px solid #e9eef6;border-radius:14px;background:#fff;box-shadow:0 2px 10px rgba(16,24,40,.04);}
  .kpi-title{font-size:.78rem;color:#64748b}
  .kpi-value{font-size:1.25rem;font-weight:700;color:#0f172a}
  .pill{display:inline-block;padding:.25rem .55rem;border-radius:999px;font-weight:700;font-size:.72rem}
  .pill-blue{background:#e8f1ff;color:#0b4db7;border:1px solid #cfe2ff}
  .pill-amber{background:#fff4e5;color:#92400e;border:1px solid #ffe2b8}
  .pill-green{background:#eafff3;color:#166534;border:1px solid #bbf7d0}
  .grid-wrap{border:1px solid #e9eef6;border-radius:14px;background:#fff;overflow:hidden}
  .grid-head{padding:.75rem 1rem;border-bottom:1px solid #eef2f7;background:#fbfdff}
  .table-excel{width:100%;border-collapse:separate;border-spacing:0}
  .table-excel th,.table-excel td{padding:.45rem .6rem;border-bottom:1px solid #eef2f7;white-space:nowrap;font-size:.84rem}
  .table-excel th{position:sticky;top:0;background:#ffffff;z-index:2}
  .table-excel tr:hover td{background:#f8fbff}
  .scroll-xy{max-height:62vh;overflow:auto}
  .scroll-xy.sm{max-height:45vh}
  .btn-lite{border:1px solid #e2e8f0;background:#fff}
  .mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace}
  .muted{color:#64748b}
  .missing{background:#fff1f2}
</style>

<div class="container-fluid px-4 py-3">

  <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
    <div>
      <div class="d-flex align-items-center gap-2">
        <div style="width:34px;height:34px;border-radius:10px;background:#e8f1ff;display:flex;align-items:center;justify-content:center;">
          <span class="mono" style="color:#0b4db7;font-weight:800;">INV</span>
        </div>
        <div>
          <h3 class="mb-0" style="font-weight:800;color:#0f172a;">Detalle de Inventario <span class="muted">(Planeación / Teórico)</span></h3>
          <div class="muted" style="font-size:.9rem;">
            Folio: <b><?=h($folio)?></b> · ID: <b><?=h($planId)?></b> · Tipo: <b><?=h($tipo)?></b>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-lite" href="../administracion/admin_inventarios.php">← Regresar</a>
      <!-- aquí puedes ligar tu acción real de generar alcance si ya la tienes -->
      <a class="btn btn-primary" href="<?=h($_SERVER['PHP_SELF'])?>?folio=<?=urlencode($folio)?>">Refrescar</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="kpi-card p-3">
        <div class="kpi-title">Estado</div>
        <div class="kpi-value">
          <?php
            $pill = 'pill-blue';
            if (strtoupper($estado)==='EN_CONTEO') $pill='pill-amber';
            if (strtoupper($estado)==='CERRADO') $pill='pill-green';
          ?>
          <span class="pill <?=$pill?>"><?=h($estado)?></span>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card p-3">
        <div class="kpi-title">BLs planeados</div>
        <div class="kpi-value"><?=h($blsPlaneados)?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card p-3">
        <div class="kpi-title">Ubicaciones generadas</div>
        <div class="kpi-value"><?=h($ubicasGen)?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="kpi-card p-3">
        <div class="kpi-title">Fecha programada</div>
        <div class="kpi-value"><?=h($fechaPlan ?: '—')?></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Alcance -->
    <div class="col-12 col-xl-6">
      <div class="grid-wrap">
        <div class="grid-head d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <div style="font-weight:800;color:#0f172a;">Ubicaciones a Inventariar (Alcance)</div>
            <div class="muted" style="font-size:.85rem;">Tip: usa “Ver” para cargar la grilla teórica (modo Excel).</div>
          </div>
          <div class="muted" style="font-size:.85rem;">Máx. 2000 registros</div>
        </div>

        <div class="scroll-xy sm">
          <table class="table-excel">
            <thead>
              <tr>
                <th>BL (CodigoCSD)</th>
                <th>ID Ubica</th>
                <th>Pasillo</th>
                <th>Rack</th>
                <th>Nivel</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$alcance): ?>
              <tr><td colspan="6" class="muted">Sin alcance generado (bls_json vacío o sin ubicaciones detectadas).</td></tr>
            <?php else: ?>
              <?php foreach($alcance as $a):
                $isMissing = !empty($a['_missing']) || empty($a['idy_ubica']);
                $rowCls = $isMissing ? "missing" : "";
                $link = $isMissing ? "#" : (h($_SERVER['PHP_SELF'])."?folio=".urlencode($folio)."&idy_ubica=".urlencode($a['idy_ubica'])."&q=".urlencode($q));
              ?>
                <tr class="<?=$rowCls?>">
                  <td class="mono"><?=h($a['CodigoCSD'] ?? '')?></td>
                  <td class="mono"><?=h($a['idy_ubica'] ?? '—')?></td>
                  <td><?=h($a['cve_pasillo'] ?? '')?></td>
                  <td><?=h($a['cve_rack'] ?? '')?></td>
                  <td><?=h($a['cve_nivel'] ?? '')?></td>
                  <td>
                    <?php if (!$isMissing): ?>
                      <a class="btn btn-sm btn-outline-primary" href="<?=$link?>">Ver</a>
                    <?php else: ?>
                      <span class="muted">No mapeado</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Grilla teórica (Excel) -->
    <div class="col-12 col-xl-6">
      <div class="grid-wrap">
        <div class="grid-head d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <div style="font-weight:800;color:#0f172a;">Ejecución / Teórico (Grilla tipo Excel)</div>
            <div class="muted" style="font-size:.85rem;">
              <?php if($snapHeader): ?>
                BL: <b class="mono"><?=h($snapHeader['CodigoCSD'] ?? '')?></b> · idy_ubica: <b class="mono"><?=h($snapHeader['idy_ubica'] ?? '')?></b>
              <?php else: ?>
                Selecciona una ubicación del alcance para cargar la “foto” teórica.
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <form class="d-flex gap-2" method="GET" action="<?=h($_SERVER['PHP_SELF'])?>">
              <input type="hidden" name="folio" value="<?=h($folio)?>">
              <?php if($idy_ubica): ?><input type="hidden" name="idy_ubica" value="<?=h($idy_ubica)?>"><?php endif; ?>
              <input class="form-control" style="min-width:280px" name="q" value="<?=h($q)?>" placeholder="Filtrar (artículo, lote, contenedor/CveLP, epc, code)">
              <button class="btn btn-outline-primary" type="submit">Filtrar</button>
              <a class="btn btn-lite" href="<?=h($_SERVER['PHP_SELF'])?>?folio=<?=urlencode($folio)?><?= $idy_ubica?("&idy_ubica=".urlencode($idy_ubica)):"" ?>">Limpiar</a>
            </form>
          </div>
        </div>

        <!-- Resumen por artículo/lote -->
        <div class="px-3 pt-3">
          <div class="muted" style="font-size:.85rem;font-weight:700;margin-bottom:.35rem;">Totales por artículo/lote</div>
          <div class="scroll-xy" style="max-height:18vh; border:1px solid #eef2f7;border-radius:12px;">
            <table class="table-excel">
              <thead>
                <tr>
                  <th>Artículo</th>
                  <th>Lote</th>
                  <th class="text-end">Cantidad</th>
                </tr>
              </thead>
              <tbody>
                <?php if(!$snapHeader): ?>
                  <tr><td colspan="3" class="muted">Selecciona una ubicación.</td></tr>
                <?php elseif(!$sumRows): ?>
                  <tr><td colspan="3" class="muted">Sin datos teóricos para esta ubicación.</td></tr>
                <?php else: ?>
                  <?php foreach($sumRows as $r): ?>
                    <tr>
                      <td class="mono"><?=h($r['cve_articulo'])?></td>
                      <td class="mono"><?=h($r['cve_lote'] ?? '')?></td>
                      <td class="text-end mono"><?=h(number_format((float)$r['cantidad'],4))?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Grilla Excel: BL + Producto + Contenedor + LP -->
        <div class="p-3">
          <div class="muted" style="font-size:.85rem;font-weight:700;margin-bottom:.35rem;">
            Detalle (BL + artículo + lote + tipo + contenedor + CveLP)
          </div>

          <div class="scroll-xy" style="border:1px solid #eef2f7;border-radius:12px;">
            <table class="table-excel">
              <thead>
                <tr>
                  <th>BL</th>
                  <th>ID Ubica</th>
                  <th>Artículo</th>
                  <th>Lote</th>
                  <th>Tipo</th>
                  <th>Contenedor visible</th>
                  <th>ID Caja</th>
                  <th>ID Tarima</th>
                  <th>CveLP</th>
                  <th class="text-end">Cantidad</th>
                  <th>Fuente</th>
                  <th>Code</th>
                  <th>EPC</th>
                </tr>
              </thead>
              <tbody>
              <?php if(!$snapHeader): ?>
                <tr><td colspan="13" class="muted">Selecciona una ubicación del alcance.</td></tr>
              <?php elseif(!$snapRows): ?>
                <tr><td colspan="13" class="muted">Sin detalle de existencias para esta ubicación (o todo es cero).</td></tr>
              <?php else: ?>
                <?php foreach($snapRows as $r):
                  $tipoCont = 'PIEZA';
                  if (!empty($r['nTarima'])) $tipoCont = 'LP';
                  elseif (!empty($r['id_caja'])) $tipoCont = 'CAJA';

                  $visible = '—';
                  if ($tipoCont === 'LP') $visible = ($r['CveLP'] ? $r['CveLP'] : ('IDCont: '.$r['nTarima']));
                  if ($tipoCont === 'CAJA') $visible = ('CAJA: '.$r['id_caja']);
                ?>
                  <tr>
                    <td class="mono"><?=h($r['bl'] ?? ($snapHeader['CodigoCSD'] ?? ''))?></td>
                    <td class="mono"><?=h($r['idy_ubica'])?></td>
                    <td class="mono"><?=h($r['cve_articulo'])?></td>
                    <td class="mono"><?=h($r['cve_lote'] ?? '')?></td>
                    <td><span class="pill <?= $tipoCont==='LP'?'pill-blue':($tipoCont==='CAJA'?'pill-amber':'pill-green') ?>"><?=h($tipoCont)?></span></td>
                    <td class="mono"><?=h($visible)?></td>
                    <td class="mono"><?=h($r['id_caja'] ?? '')?></td>
                    <td class="mono"><?=h($r['nTarima'] ?? '')?></td>
                    <td class="mono"><?=h($r['CveLP'] ?? '')?></td>
                    <td class="text-end mono"><?=h(number_format((float)$r['cantidad'],4))?></td>
                    <td><?=h($r['fuente'] ?? '')?></td>
                    <td class="mono"><?=h($r['code'] ?? '')?></td>
                    <td class="mono"><?=h($r['epc'] ?? '')?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="muted mt-2" style="font-size:.8rem;">
            Nota operativa: este formato ya está listo para capturar conteos en la misma grilla (C1/C2) sin cambiar de vista.
            El siguiente paso es agregar columnas editables y persistir en <span class="mono">inventario_conteo</span>.
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../bi/_menu_global_end.php'; ?>
