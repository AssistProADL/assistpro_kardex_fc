<?php
// ==============================
// BOOTSTRAP ASSISTPRO
// ==============================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

db_pdo();
global $pdo;

// ==============================
// CONSULTA PRINCIPAL
// ==============================
$sql = "
SELECT 
    ID_Incidencia,
    clave,
    cliente,
    responsable_caso,
    status,
    Fecha
FROM th_incidencia
WHERE Activo = 1
ORDER BY Fecha DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// KPIs
// ==============================
$kpi = [
  'A' => 0,
  'P' => 0,
  'E' => 0,
  'C' => 0
];

foreach ($rows as $r) {
  if (isset($kpi[$r['status']])) {
    $kpi[$r['status']]++;
  }
}
?>

<div class="container-fluid">
  <h3 class="mb-3">Control de Incidencias (PQRS)</h3>

  <!-- KPIs -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card border-primary">
        <div class="card-body text-center">
          <h6>Abiertas</h6>
          <h2 class="text-primary"><?= $kpi['A'] ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-warning">
        <div class="card-body text-center">
          <h6>En proceso</h6>
          <h2 class="text-warning"><?= $kpi['P'] ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-info">
        <div class="card-body text-center">
          <h6>En espera</h6>
          <h2 class="text-info"><?= $kpi['E'] ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-success">
        <div class="card-body text-center">
          <h6>Cerradas</h6>
          <h2 class="text-success"><?= $kpi['C'] ?></h2>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Responsable</th>
            <th>Estatus</th>
            <th>Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['clave']) ?></td>
              <td><?= htmlspecialchars($r['cliente']) ?></td>
              <td><?= htmlspecialchars($r['responsable_caso']) ?></td>
              <td>
                <?php
                  switch ($r['status']) {
                    case 'A': echo "<span class='badge badge-primary'>Abierto</span>"; break;
                    case 'P': echo "<span class='badge badge-warning'>En proceso</span>"; break;
                    case 'E': echo "<span class='badge badge-info'>En espera</span>"; break;
                    case 'C': echo "<span class='badge badge-success'>Cerrado</span>"; break;
                  }
                ?>
              </td>
              <td><?= date('d/m/Y H:i', strtotime($r['Fecha'])) ?></td>
              <td>
                <a href="pqrs_view.php?id=<?= $r['ID_Incidencia'] ?>" title="Ver">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <a href="pqrs_new.php" class="btn btn-success mt-3">+ Nueva Incidencia</a>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
