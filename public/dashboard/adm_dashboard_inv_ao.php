<?php
// public/dashboard/adm_dashboard_inv_ao.php
// Dashboard gráfico de Inventarios (Físico / Cíclico) – versión AO
// Filtros por NOMBRE de almacén para compatibilidad total.

require_once __DIR__ . '/../../app/db.php';

if (!function_exists('db_all')) {
    function db_all($sql, $params = []) {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$tipo     = isset($_GET['tipo']) ? strtoupper($_GET['tipo']) : 'F'; // F | C | A
$alm_nom  = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';  // nombre de almacén
$f_ini    = isset($_GET['f_ini']) ? trim($_GET['f_ini']) : '';
$f_fin    = isset($_GET['f_fin']) ? trim($_GET['f_fin']) : '';

$almacenes = db_all("
    SELECT nombre FROM (
        SELECT DISTINCT almacen AS nombre FROM v_dashboard_inv_fisico_ao WHERE almacen IS NOT NULL AND almacen <> ''
        UNION
        SELECT DISTINCT des_almacen AS nombre FROM v_dashboard_inv_ciclico_ao WHERE des_almacen IS NOT NULL AND des_almacen <> ''
    ) x ORDER BY nombre
");

function load_fisico($alm_nom, $f_ini, $f_fin) {
    $where = []; $par = [];
    if ($alm_nom !== '') { $where[] = "almacen = ?"; $par[] = $alm_nom; }
    if ($f_ini !== '' && $f_fin !== '') { $where[] = "fecha_creacion BETWEEN ? AND ?"; $par[] = $f_ini.' 00:00:00'; $par[] = $f_fin.' 23:59:59'; }
    $sql = "SELECT folio_inventario, fecha_creacion, nombre_inventario, status_inventario,
                   cve_almacen, almacen, avance_porcentual, estado_proceso,
                   ubicaciones_planeadas, ubicaciones_contadas,
                   piezas_contadas, piezas_teoricas, diferencia_piezas
            FROM v_dashboard_inv_fisico_ao";
    if ($where) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY fecha_creacion DESC LIMIT 500";
    return db_all($sql, $par);
}

function load_ciclico($alm_nom, $f_ini, $f_fin) {
    $where = []; $par = [];
    if ($alm_nom !== '') { $where[] = "des_almacen = ?"; $par[] = $alm_nom; }
    if ($f_ini !== '' && $f_fin !== '') { $where[] = "fecha_inicio BETWEEN ? AND ?"; $par[] = $f_ini.' 00:00:00'; $par[] = $f_fin.' 23:59:59'; }
    $sql = "SELECT folio_plan, fecha_inicio, fecha_fin, cve_almacen, des_almacen AS almacen,
                   avance_porcentual, estado_proceso,
                   ubicaciones_planeadas, ubicaciones_contadas,
                   piezas_contadas, piezas_teoricas, diferencia_piezas
            FROM v_dashboard_inv_ciclico_ao";
    if ($where) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY fecha_inicio DESC LIMIT 500";
    return db_all($sql, $par);
}

$fisico  = ($tipo==='F'||$tipo==='A') ? load_fisico($alm_nom,$f_ini,$f_fin) : [];
$ciclico = ($tipo==='C'||$tipo==='A') ? load_ciclico($alm_nom,$f_ini,$f_fin) : [];

function agg_estado($rows) {
    $est = ['Planeado'=>0,'En ejecución'=>0,'Completado'=>0,'Cerrado'=>0];
    foreach ($rows as $r) { $e = $r['estado_proceso'] ?? ''; if (!isset($est[$e])) $est[$e]=0; $est[$e]++; }
    return $est;
}
function labels_values_avance($rows, $folioCampo) {
    $labels=[]; $values=[];
    foreach ($rows as $r) { $labels[] = (string)$r[$folioCampo]; $values[] = (float)($r['avance_porcentual'] ?? 0); }
    return [$labels, $values];
}

$estFis = agg_estado($fisico);
$estCic = agg_estado($ciclico);
list($labFis, $valFis) = labels_values_avance($fisico, 'folio_inventario');
list($labCic, $valCic) = labels_values_avance($ciclico, 'folio_plan');

$tot_fis = count($fisico);
$tot_cic = count($ciclico);
$sum_pzas_f = array_sum(array_map(fn($r)=>(float)($r['piezas_contadas'] ?? 0), $fisico));
$sum_difp_f = array_sum(array_map(fn($r)=>(float)($r['diferencia_piezas'] ?? 0), $fisico));
$sum_pzas_c = array_sum(array_map(fn($r)=>(float)($r['piezas_contadas'] ?? 0), $ciclico));
$sum_difp_c = array_sum(array_map(fn($r)=>(float)($r['diferencia_piezas'] ?? 0), $ciclico));

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3" style="font-size:10px;">
  <div class="row mb-2">
    <div class="col">
      <h5 class="mb-0">Dashboard de Inventarios (Gráfico)</h5>
      <small class="text-muted">Planeados · En ejecución · Completados · Cerrados</small>
    </div>
  </div>

  <form method="get" class="card mb-3 shadow-sm border-0">
    <div class="card-body p-2">
      <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Tipo</label>
          <select name="tipo" class="form-select form-select-sm">
            <option value="F" <?= $tipo==='F'?'selected':''; ?>>Físico</option>
            <option value="C" <?= $tipo==='C'?'selected':''; ?>>Cíclico</option>
            <option value="A" <?= $tipo==='A'?'selected':''; ?>>Ambos</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label mb-1">Almacén (nombre)</label>
          <select name="almacen" class="form-select form-select-sm">
            <option value="">[Todos]</option>
            <?php foreach ($almacenes as $a): ?>
              <option value="<?= htmlspecialchars($a['nombre']) ?>" <?= $alm_nom===$a['nombre']?'selected':''; ?>>
                <?= htmlspecialchars($a['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Fecha inicio</label>
          <input type="date" name="f_ini" class="form-control form-control-sm" value="<?= htmlspecialchars($f_ini) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Fecha fin</label>
          <input type="date" name="f_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($f_fin) ?>">
        </div>
        <div class="col-6 col-md-1 text-end">
          <button class="btn btn-sm btn-primary px-3">Aplicar</button>
        </div>
      </div>
    </div>
  </form>

  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body p-2">
        <div class="fw-bold">Inventarios físicos</div>
        <div class="h5 mb-0"><?= number_format($tot_fis) ?></div>
        <small class="text-muted">Pzas: <?= number_format($sum_pzas_f) ?> · Dif: <?= number_format($sum_difp_f) ?></small>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body p-2">
        <div class="fw-bold">Inventarios cíclicos</div>
        <div class="h5 mb-0"><?= number_format($tot_cic) ?></div>
        <small class="text-muted">Pzas: <?= number_format($sum_pzas_c) ?> · Dif: <?= number_format($sum_difp_c) ?></small>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body p-2">
        <div class="fw-bold">Físico · En ejecución</div>
        <div class="h6 mb-0"><?= number_format($estFis['En ejecución'] ?? 0) ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body p-2">
        <div class="fw-bold">Cíclico · En ejecución</div>
        <div class="h6 mb-0"><?= number_format($estCic['En ejecución'] ?? 0) ?></div>
      </div></div>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm"><div class="card-body p-2">
        <div class="fw-bold mb-2">Distribución por estado</div>
        <canvas id="chartEstados" height="160"></canvas>
        <small class="text-muted">Planeado / En ejecución / Completado / Cerrado</small>
      </div></div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm"><div class="card-body p-2">
        <div class="fw-bold mb-2">Avance por folio</div>
        <canvas id="chartAvance" height="160"></canvas>
        <small class="text-muted">% de ubicaciones contadas respecto a planeadas</small>
      </div></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table id="tblResumen" class="table table-sm table-striped table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>Tipo</th><th>Folio</th><th>Almacén</th><th>Fecha</th><th>Estado</th>
              <th>% Avance</th><th>Ubic. Plan</th><th>Ubic. Cont</th><th>Pzas Cont</th><th>Dif Pzs</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($tipo==='F' || $tipo==='A'): foreach ($fisico as $r): ?>
              <tr>
                <td>Físico</td>
                <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
                <td><?= htmlspecialchars($r['almacen']) ?></td>
                <td><?= htmlspecialchars($r['fecha_creacion']) ?></td>
                <td><?= htmlspecialchars($r['estado_proceso']) ?></td>
                <td class="text-end"><?= number_format($r['avance_porcentual'] ?? 0, 2) ?>%</td>
                <td class="text-end"><?= number_format($r['ubicaciones_planeadas'] ?? 0) ?></td>
                <td class="text-end"><?= number_format($r['ubicaciones_contadas'] ?? 0) ?></td>
                <td class="text-end"><?= number_format($r['piezas_contadas'] ?? 0) ?></td>
                <td class="text-end"><?= number_format($r['diferencia_piezas'] ?? 0) ?></td>
                <td>
                  <a class="btn btn-xs btn-outline-primary"
                     href="adm_inventarios_det.php?tipo=F&view=det&folio=<?= urlencode($r['folio_inventario']) ?>">Detalle</a>
                  <a class="btn btn-xs btn-outline-danger"
                     href="adm_inventarios_det.php?tipo=F&view=dif&folio=<?= urlencode($r['folio_inventario']) ?>">Diferencias</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>

            <?php if ($tipo==='C' || $tipo==='A'): foreach ($ciclico as $r): ?>
              <tr>
                <td>Cíclico</td>
                <td><?= htmlspecialchars($r['folio_plan']) ?></td>
                <td><?= htmlspecialchars($r['almacen']) ?></td>
                <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
                <td><?= htmlspecialchars($r['estado_proceso']) ?></td>
                <td class="text-end"><?= number_format($r['avance_porcentual'] ?? 0, 2) ?>%</td>
                <td class="text-end"><?= number_format($r['ubicaciones_planeadas'] ?? 0) ?></td>
                <td class="text-end"><?= number_format($r['ubicaciones_contadas'] ?? 0) ?></td>
                <td class="text-end"><?= number_format($r['piezas_contadas'] ?? 0) ?></td>
                <td class="text-end"><?= number_format($r['diferencia_piezas'] ?? 0) ?></td>
                <td>
                  <a class="btn btn-xs btn-outline-primary"
                     href="adm_inventarios_det.php?tipo=C&view=det&folio=<?= urlencode($r['folio_plan']) ?>">Detalle</a>
                  <a class="btn btn-xs btn-outline-danger"
                     href="adm_inventarios_det.php?tipo=C&view=dif&folio=<?= urlencode($r['folio_plan']) ?>">Diferencias</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  const tipo = <?= json_encode($tipo) ?>;
  const estFis = <?= json_encode(agg_estado($fisico)) ?>;
  const estCic = <?= json_encode(agg_estado($ciclico)) ?>;
  const labels = ['Planeado','En ejecución','Completado','Cerrado'];
  let dataEstados = [0,0,0,0];
  if (tipo==='F') dataEstados = labels.map(l=>estFis[l]||0);
  else if (tipo==='C') dataEstados = labels.map(l=>estCic[l]||0);
  else dataEstados = labels.map(l=>(estFis[l]||0)+(estCic[l]||0));

  const labFis = <?= json_encode($labFis) ?>;
  const valFis = <?= json_encode($valFis) ?>;
  const labCic = <?= json_encode($labCic) ?>;
  const valCic = <?= json_encode($valCic) ?>;
  let labelsAv = [], valuesAv = [];
  if (tipo==='F') { labelsAv = labFis; valuesAv = valFis; }
  else if (tipo==='C') { labelsAv = labCic; valuesAv = valCic; }
  else { labelsAv = labFis.concat(labCic.map(v=>'P'+v)); valuesAv = valFis.concat(valCic); }

  const ctx1 = document.getElementById('chartEstados');
  if (ctx1) new Chart(ctx1, { type: 'doughnut',
    data:{ labels, datasets:[{ data: dataEstados }] },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
  });

  const ctx2 = document.getElementById('chartAvance');
  if (ctx2) new Chart(ctx2, { type: 'bar',
    data:{ labels:labelsAv, datasets:[{ label:'% Avance', data: valuesAv }] },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, max:100 } }, plugins:{ legend:{ display:false } } }
  });

  if (window.jQuery && $.fn.DataTable) {
    $('#tblResumen').DataTable({
      pageLength: 25, scrollX: true, lengthChange: false, ordering: true,
      language:{ url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
  }
})();
</script>
