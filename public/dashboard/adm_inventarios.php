<?php
// public/dashboard/adm_inventarios.php
// Administración de Inventarios – Avanzada (subset rápido + ubicaciones + cards)

require_once __DIR__ . '/../../app/db.php';

/* ========= Filtros ========= */
$tipo        = isset($_GET['tipo']) ? strtoupper($_GET['tipo']) : 'F'; // F|C|A
$almClave    = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';  // c_almacenp.clave
$f_ini       = isset($_GET['f_ini']) ? trim($_GET['f_ini']) : '';
$f_fin       = isset($_GET['f_fin']) ? trim($_GET['f_fin']) : '';
$inclCerrado = !empty($_GET['cerrados']) ? 1 : 0; // 0 = solo abiertos (default)

if ($f_ini === '' || $f_fin === '') {
    $f_fin = date('Y-m-d');
    $f_ini = date('Y-m-d', strtotime('-180 days'));
}

/* ========= Catálogo de almacenes (ALMACÉN REAL) ========= */
$almacenes = db_all("
    SELECT clave AS cve_almac, nombre
    FROM c_almacenp
    ORDER BY nombre
");

/* ========= Inventarios Físicos (subset + agregados) ========= */
function load_fisico_subset($almClave, $f_ini, $f_fin, $inclCerrado) {
    $whereSubset = ["th.Activo = 1", "th.Fecha BETWEEN :f_ini AND :f_fin"];
    $params = [
        ':f_ini' => $f_ini . " 00:00:00",
        ':f_fin' => $f_fin . " 23:59:59",
    ];

    // Solo abiertos por defecto
    if (!$inclCerrado) {
        $whereSubset[] = "th.Status = 'A'";
    }

    // Filtro por almacén (cve_almacen de th_inventario)
    if ($almClave !== '') {
        $whereSubset[]      = "th.cve_almacen = :alm";
        $params[':alm']     = $almClave;
    }

    $sql = "
    WITH inv_subset AS (
        SELECT th.ID_Inventario, th.Fecha, th.Status, th.cve_almacen, th.cve_zona
        FROM th_inventario th
        WHERE " . implode(' AND ', $whereSubset) . "
        ORDER BY th.Fecha DESC
        LIMIT 300
    ),
    det AS (
        /* Piezas globales por inventario */
        SELECT p.ID_Inventario,
               SUM(COALESCE(p.Cantidad,0))          AS pzas_cont,
               SUM(COALESCE(p.ExistenciaTeorica,0)) AS pzas_teo
        FROM t_invpiezas p
        INNER JOIN inv_subset s ON s.ID_Inventario = p.ID_Inventario
        GROUP BY p.ID_Inventario
    ),
    uai AS (
        /* Ubicaciones planeadas */
        SELECT u.ID_Inventario, COUNT(DISTINCT u.idy_ubica) AS ubic_plan
        FROM t_ubicacionesainventariar u
        INNER JOIN inv_subset s ON s.ID_Inventario = u.ID_Inventario
        GROUP BY u.ID_Inventario
    ),
    ui1 AS (
        /* Ubicaciones contadas por t_ubicacioninventario */
        SELECT u.ID_Inventario, COUNT(DISTINCT u.idy_ubica) AS ubic_cont
        FROM t_ubicacioninventario u
        INNER JOIN inv_subset s ON s.ID_Inventario = u.ID_Inventario
        GROUP BY u.ID_Inventario
    ),
    ui2 AS (
        /* Fallback: ubicaciones contadas detectadas en t_invpiezas */
        SELECT p.ID_Inventario, COUNT(DISTINCT p.idy_ubica) AS ubic_cont_piezas
        FROM t_invpiezas p
        INNER JOIN inv_subset s ON s.ID_Inventario = p.ID_Inventario
        GROUP BY p.ID_Inventario
    )
    SELECT
        s.ID_Inventario                                           AS folio_inventario,
        s.Fecha                                                   AS fecha_creacion,
        ap.nombre                                                 AS almacen,
        ca.des_almac                                              AS zona,
        CASE 
            WHEN s.Status IN ('T','O') THEN 'Cerrado'
            WHEN s.Status = 'A'        THEN 'Abierto'
            ELSE s.Status
        END                                                       AS status_inventario,
        COALESCE(uai.ubic_plan,0)                                 AS ubicaciones_planeadas,
        COALESCE(ui1.ubic_cont, ui2.ubic_cont_piezas, 0)          AS ubicaciones_contadas,
        CASE
            WHEN COALESCE(uai.ubic_plan,0)=0 THEN 0
            ELSE ROUND(COALESCE(COALESCE(ui1.ubic_cont, ui2.ubic_cont_piezas,0)*100.0)/uai.ubic_plan, 2)
        END                                                       AS avance_porcentual,
        COALESCE(det.pzas_cont,0)                                 AS piezas_contadas,
        COALESCE(det.pzas_teo,0)                                  AS piezas_teoricas,
        (COALESCE(det.pzas_cont,0)-COALESCE(det.pzas_teo,0))      AS diferencia_piezas
    FROM inv_subset s
    LEFT JOIN det  ON det.ID_Inventario  = s.ID_Inventario
    LEFT JOIN uai  ON uai.ID_Inventario  = s.ID_Inventario
    LEFT JOIN ui1  ON ui1.ID_Inventario  = s.ID_Inventario
    LEFT JOIN ui2  ON ui2.ID_Inventario  = s.ID_Inventario
    LEFT JOIN c_almacenp ap ON ap.clave  = s.cve_almacen
    LEFT JOIN c_almacen  ca ON ca.cve_almac = s.cve_zona
    ORDER BY s.Fecha DESC
    ";

    return db_all($sql, $params);
}

/* ========= Inventarios Cíclicos (si ya tienes la vista) ========= */
function load_ciclico_dash($almClave, $f_ini, $f_fin) {
    $where = ["fecha_inicio BETWEEN :f_ini AND :f_fin"];
    $params = [
        ':f_ini' => $f_ini . " 00:00:00",
        ':f_fin' => $f_fin . " 23:59:59",
    ];
    if ($almClave !== '') {
        $where[]         = "cve_almacen = :alm";
        $params[':alm']  = $almClave;
    }

    $sql = "
    SELECT folio_plan, fecha_inicio, fecha_fin, cve_almacen, des_almacen AS almacen,
           avance_porcentual, ubicaciones_planeadas, ubicaciones_contadas,
           piezas_contadas, piezas_teoricas, diferencia_piezas, estado_proceso
    FROM v_dashboard_inv_ciclico_ao
    WHERE " . implode(' AND ', $where) . "
    ORDER BY fecha_inicio DESC
    LIMIT 600
    ";
    return db_all($sql, $params);
}

/* ========= Ejecutar cargas ========= */
$fisico  = ($tipo === 'F' || $tipo === 'A') ? load_fisico_subset($almClave,$f_ini,$f_fin,$inclCerrado) : [];
$ciclico = ($tipo === 'C' || $tipo === 'A') ? load_ciclico_dash($almClave,$f_ini,$f_fin) : [];

/* ========= KPIs de físicos para cards ========= */
$k = ['inv'=>0,'u_plan'=>0,'u_cont'=>0,'p_cont'=>0,'dif_pzas'=>0];
foreach ($fisico as $r) {
    $k['inv']++;
    $k['u_plan']   += (int)$r['ubicaciones_planeadas'];
    $k['u_cont']   += (int)$r['ubicaciones_contadas'];
    $k['p_cont']   += (float)$r['piezas_contadas'];
    $k['dif_pzas'] += (float)$r['diferencia_piezas'];
}
$avance_prom = ($k['u_plan'] > 0) ? round(($k['u_cont']*100.0)/$k['u_plan'],2) : 0.0;

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3" style="font-size:10px;">

  <div class="row mb-2">
    <div class="col">
      <h5 class="mb-0">Administración de Inventarios</h5>
      <small class="text-muted">
        Subset de hasta 300 inventarios físicos por rango/almacén, con avance y diferencias.
      </small>
    </div>
  </div>

  <!-- Cards estilo RFID -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">Inventarios físicos</div>
        <div class="h5 m-0"><?= number_format($k['inv']) ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">Ubic. Plan / Cont</div>
        <div class="h6 m-0"><?= number_format($k['u_plan']) ?> / <?= number_format($k['u_cont']) ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">% Avance promedio</div>
        <div class="h6 m-0"><?= number_format($avance_prom,2) ?>%</div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm border-0"><div class="card-body p-2">
        <div class="text-muted">Pzas contadas / Dif</div>
        <div class="h6 m-0"><?= number_format($k['p_cont']) ?> / <?= number_format($k['dif_pzas']) ?></div>
      </div></div>
    </div>
  </div>

  <!-- Filtros -->
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
          <label class="form-label mb-1">Almacén</label>
          <select name="almacen" class="form-select form-select-sm">
            <option value="">[Todos]</option>
            <?php foreach ($almacenes as $a): ?>
              <option value="<?= htmlspecialchars($a['cve_almac']) ?>" <?= $almClave===$a['cve_almac']?'selected':''; ?>>
                <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['cve_almac']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Fecha inicio</label>
          <input type="date" name="f_ini" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($f_ini) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Fecha fin</label>
          <input type="date" name="f_fin" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($f_fin) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">&nbsp;</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="cerrados" value="1" <?= $inclCerrado?'checked':''; ?>>
            <label class="form-check-label">Incluir cerrados</label>
          </div>
        </div>
        <div class="col-6 col-md-1 text-end">
          <button class="btn btn-sm btn-primary px-3 w-100">Buscar</button>
        </div>
      </div>
    </div>
  </form>

  <!-- Tabla Físicos -->
  <?php if ($tipo==='F' || $tipo==='A'): ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-2">
      <div class="fw-bold mb-2">Inventarios Físicos (máx. 300 por rango/almacén)</div>
      <div class="table-responsive">
        <table id="tblFisico" class="table table-sm table-striped table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>Folio</th><th>Almacén</th><th>Zona</th>
              <th>Creación</th><th>Status</th>
              <th>% Avance</th><th>Ubic. Plan</th><th>Ubic. Cont</th>
              <th>Pzas Cont</th><th>Dif Pzas</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($fisico as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['folio_inventario']) ?></td>
              <td><?= htmlspecialchars($r['almacen']) ?></td>
              <td><?= htmlspecialchars($r['zona']) ?></td>
              <td><?= htmlspecialchars($r['fecha_creacion']) ?></td>
              <td><?= htmlspecialchars($r['status_inventario']) ?></td>
              <td class="text-end"><?= number_format((float)$r['avance_porcentual'],2) ?>%</td>
              <td class="text-end"><?= number_format((int)$r['ubicaciones_planeadas']) ?></td>
              <td class="text-end"><?= number_format((int)$r['ubicaciones_contadas']) ?></td>
              <td class="text-end"><?= number_format((float)$r['piezas_contadas']) ?></td>
              <td class="text-end"><?= number_format((float)$r['diferencia_piezas']) ?></td>
              <td>
                <a class="btn btn-xs btn-outline-primary"
                   href="adm_inventarios_det.php?tipo=F&folio=<?= urlencode($r['folio_inventario']) ?>">
                   Detalle
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabla Cíclicos (si usas la vista v_dashboard_inv_ciclico_ao) -->
  <?php if ($tipo==='C' || $tipo==='A'): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-2">
      <div class="fw-bold mb-2">Inventarios Cíclicos</div>
      <div class="table-responsive">
        <table id="tblCiclico" class="table table-sm table-striped table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>Plan</th><th>Almacén</th><th>Inicio</th><th>Fin</th>
              <th>% Avance</th><th>Ubic. Plan</th><th>Ubic. Cont</th>
              <th>Pzas Cont</th><th>Dif Pzas</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ciclico as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['folio_plan']) ?></td>
              <td><?= htmlspecialchars($r['almacen']) ?> (<?= htmlspecialchars($r['cve_almacen']) ?>)</td>
              <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
              <td><?= htmlspecialchars($r['fecha_fin']) ?></td>
              <td class="text-end"><?= number_format((float)$r['avance_porcentual'],2) ?>%</td>
              <td class="text-end"><?= number_format((int)$r['ubicaciones_planeadas']) ?></td>
              <td class="text-end"><?= number_format((int)$r['ubicaciones_contadas']) ?></td>
              <td class="text-end"><?= number_format((float)$r['piezas_contadas']) ?></td>
              <td class="text-end"><?= number_format((float)$r['diferencia_piezas']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (window.jQuery && $.fn.DataTable) {
    $('#tblFisico, #tblCiclico').DataTable({
      pageLength: 25,
      lengthChange: false,
      ordering: true,
      scrollX: true,
      language: { url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
  }
});
</script>
