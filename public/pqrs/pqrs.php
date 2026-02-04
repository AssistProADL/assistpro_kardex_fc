<?php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

/* ===========================
   KPIs (MISMA LOGICA)
=========================== */
$kpis = [
  'NUEVA'      => 0,
  'EN_PROCESO' => 0,
  'EN_ESPERA'  => 0,
  'CERRADA'    => 0,
  'NO_PROCEDE' => 0
];

$rows_kpi = $pdo->query("
  SELECT status_clave, COUNT(*) total
  FROM pqrs_case
  GROUP BY status_clave
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows_kpi as $r) {
  if (isset($kpis[$r['status_clave']])) {
    $kpis[$r['status_clave']] = (int)$r['total'];
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>PQRS | AssistPro</title>

<style>
/* ===============================
   BASE ASSISTPRO
================================ */
body {
  background:#F5F7FA;
}

/* ===============================
   HEADER
================================ */
.page-header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:16px;
}

.page-title {
  font-size:1.4rem;
  font-weight:600;
  color:#0D6EFD;
  display:flex;
  align-items:center;
  gap:10px;
}

.page-title i {
  font-size:1.6rem;
}

/* ===============================
   KPI CARDS
================================ */
.kpi-row {
  display:grid;
  grid-template-columns: repeat(5, 1fr);
  gap:12px;
  margin-bottom:20px;
}

.kpi-card {
  background:#ffffff;
  border-radius:10px;
  padding:14px;
  text-align:center;
  border:1px solid #E3E6EB;
}

.kpi-card h6 {
  font-size:.75rem;
  color:#6C757D;
  text-transform:uppercase;
  margin-bottom:4px;
}

.kpi-card h2 {
  font-size:1.9rem;
  font-weight:600;
  margin:0;
  color:#212529;
}

/* Tonos tenues */
.kpi-nueva { border-top:4px solid #0D6EFD; }
.kpi-proceso { border-top:4px solid #6C757D; }
.kpi-espera { border-top:4px solid #ADB5BD; }
.kpi-cerrada { border-top:4px solid #198754; }
.kpi-noprocede { border-top:4px solid #DC3545; }

/* ===============================
   FILTROS
================================ */
.card-filtros {
  border-radius:10px;
  border:1px solid #E3E6EB;
  margin-bottom:16px;
}

/* ===============================
   GRID
================================ */
.grid-wrapper {
  background:#ffffff;
  border-radius:10px;
  border:1px solid #E3E6EB;
  padding:10px;
  max-height:420px;
  overflow:auto;
}

.table {
  margin-bottom:0;
}

.table thead th {
  background:#F1F3F5;
  font-size:.75rem;
  text-transform:uppercase;
  letter-spacing:.04em;
}

.table tbody tr:hover {
  background:#F8F9FA;
}

/* ===============================
   BOTONES
================================ */
.btn {
  border-radius:8px;
}
</style>
</head>

<body>

<div class="container-fluid mt-3">

<!-- ===================== -->
<!-- HEADER -->
<!-- ===================== -->
<div class="page-header">
  <div class="page-title">
    <i class="fas fa-headset"></i>
    Control de Incidencias (PQRS)
  </div>
  <a href="pqrs_new.php" class="btn btn-primary">
    + Nueva Incidencia
  </a>
</div>

<!-- ===================== -->
<!-- KPIs -->
<!-- ===================== -->
<div class="kpi-row">
  <div class="kpi-card kpi-nueva">
    <h6>Nuevas</h6>
    <h2><?= $kpis['NUEVA'] ?></h2>
  </div>
  <div class="kpi-card kpi-proceso">
    <h6>En proceso</h6>
    <h2><?= $kpis['EN_PROCESO'] ?></h2>
  </div>
  <div class="kpi-card kpi-espera">
    <h6>En espera</h6>
    <h2><?= $kpis['EN_ESPERA'] ?></h2>
  </div>
  <div class="kpi-card kpi-cerrada">
    <h6>Cerradas</h6>
    <h2><?= $kpis['CERRADA'] ?></h2>
  </div>
  <div class="kpi-card kpi-noprocede">
    <h6>No procede</h6>
    <h2><?= $kpis['NO_PROCEDE'] ?></h2>
  </div>
</div>

<!-- ===================== -->
<!-- FILTROS -->
<!-- ===================== -->
<div class="card card-filtros">
  <div class="card-body">
    <form class="row g-2">
      <div class="col-md-2">
        <input type="text" class="form-control" placeholder="Cliente">
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" placeholder="Referencia">
      </div>
      <div class="col-md-2">
        <select class="form-control">
          <option>Status</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control">
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control">
      </div>
    </form>
  </div>
</div>

<!-- ===================== -->
<!-- GRID -->
<!-- ===================== -->
<div class="grid-wrapper">
<table class="table table-hover">
  <thead>
    <tr>
      <th>Folio</th>
      <th>Cliente</th>
      <th>Referencia</th>
      <th>Tipo</th>
      <th>Status</th>
      <th>Fecha</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $rows = $pdo->query("
      SELECT id_case, fol_pqrs, cve_clte, ref_folio, tipo, status_clave, creado_en
      FROM pqrs_case
      ORDER BY id_case DESC
      LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r):
    ?>
    <tr>
      <td><?= htmlspecialchars($r['fol_pqrs']) ?></td>
      <td><?= htmlspecialchars($r['cve_clte']) ?></td>
      <td><?= htmlspecialchars($r['ref_folio']) ?></td>
      <td><?= htmlspecialchars($r['tipo']) ?></td>
      <td><?= htmlspecialchars($r['status_clave']) ?></td>
      <td><?= htmlspecialchars($r['creado_en']) ?></td>
      <td class="text-end">
        <a href="pqrs_view.php?id=<?= (int)$r['id_case'] ?>" class="btn btn-sm btn-outline-primary">
          Ver
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
