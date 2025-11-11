<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../app/db.php';

$alm_sesion = $_SESSION['cve_almac'] ?? $_SESSION['almacen'] ?? null;

// ==== CONSULTA ====
$where = "1=1";
$params = [];
if ($alm_sesion) {
    $where .= " AND (tc.origen = :alm OR tc.destino = :alm)";
    $params[':alm'] = $alm_sesion;
}
$where .= " AND tc.fecha >= (CURRENT_DATE - INTERVAL 30 DAY)";

$sql = "
SELECT 
  tc.id,
  tc.cve_articulo,
  tc.cve_lote,
  tc.fecha,
  tc.origen,
  tc.destino,
  tm.nombre AS tipo_mov
FROM t_cardex tc
LEFT JOIN t_tipomovimiento tm ON tm.id_TipoMovimiento = tc.id_TipoMovimiento
WHERE $where
ORDER BY tc.fecha DESC
LIMIT 200
";
$data = db_all($sql, $params);

// KPIs
$totalMov = db_val("SELECT COUNT(*) FROM t_cardex tc WHERE $where", $params);
$totalEnt = db_val("SELECT COUNT(*) FROM t_cardex tc 
LEFT JOIN t_tipomovimiento tm ON tm.id_TipoMovimiento = tc.id_TipoMovimiento 
WHERE $where AND tm.nombre LIKE 'Entrada%'", $params);
$totalSal = db_val("SELECT COUNT(*) FROM t_cardex tc 
LEFT JOIN t_tipomovimiento tm ON tm.id_TipoMovimiento = tc.id_TipoMovimiento 
WHERE $where AND tm.nombre LIKE 'Salida%'", $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Productividad Operativa</title>
<link href="/assets/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background-color: #fff;
  color: #1e293b;
  font-family: "Segoe UI", sans-serif;
  padding: 15px;
}
h3 {
  font-weight: 700;
  margin-bottom: 10px;
}
.cards-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}
.card-kpi {
  flex: 1;
  min-width: 220px;
  border-radius: 16px;
  padding: 18px;
  color: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.card-total { background-color: #2563eb; }
.card-entradas { background-color: #059669; }
.card-salidas { background-color: #be185d; }
.card-title { font-size: 0.9rem; font-weight: 600; opacity: 0.9; }
.card-value { font-size: 2.2rem; font-weight: 800; margin-top: 5px; }

.table-container {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background-color: #fff;
  padding: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.table-responsive {
  max-height: 70vh;
  overflow-x: auto;
  overflow-y: auto;
}
table {
  font-size: 10px;
  width: 100%;
  border-collapse: collapse;
}
thead {
  position: sticky;
  top: 0;
  background-color: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}
th, td {
  padding: 4px 6px;
  white-space: nowrap;
}
tbody tr:hover {
  background-color: #f1f5f9;
}
.badge {
  background-color: #e2e8f0;
  color: #334155;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.75rem;
}
</style>
</head>
<body>
  <h3>Productividad Operativa</h3>
  <div class="mb-3">
    <?= $alm_sesion ? "<span class='badge'>Almacén: " . htmlspecialchars($alm_sesion) . "</span>" : "<span class='badge bg-warning text-dark'>Sin almacén en sesión</span>" ?>
    <span class="badge">Últimos 30 días</span>
  </div>

  <div class="cards-row">
    <div class="card-kpi card-total">
      <div class="card-title">Movimientos</div>
      <div class="card-value"><?= number_format($totalMov) ?></div>
    </div>
    <div class="card-kpi card-entradas">
      <div class="card-title">Entradas</div>
      <div class="card-value"><?= number_format($totalEnt) ?></div>
    </div>
    <div class="card-kpi card-salidas">
      <div class="card-title">Salidas</div>
      <div class="card-value"><?= number_format($totalSal) ?></div>
    </div>
  </div>

  <div class="table-container">
    <h6 class="mb-2">Últimos movimientos (máx. 200)</h6>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Artículo</th>
            <th>Lote</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Tipo mov.</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($data): foreach ($data as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['id']) ?></td>
            <td><?= htmlspecialchars($r['fecha']) ?></td>
            <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
            <td><?= htmlspecialchars($r['cve_lote']) ?></td>
            <td><?= htmlspecialchars($r['origen']) ?></td>
            <td><?= htmlspecialchars($r['destino']) ?></td>
            <td><?= htmlspecialchars($r['tipo_mov']) ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="7" class="text-center text-muted">Sin movimientos en el rango.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
