<?php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background-color: #f5f7fa;
      color: #333;
    }

    .header {
      background-color: #005baa;
      color: white;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .header i {
      font-size: 28px;
    }

    .header h1 {
      margin: 0;
      font-size: 22px;
      font-weight: 600;
    }

    .content {
      padding: 20px;
    }

    .card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      padding: 20px;
      margin-bottom: 20px;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 15px;
    }

    .kpi-item {
      background-color: #f0f4f8;
      border-radius: 6px;
      padding: 15px;
      text-align: center;
    }

    .kpi-item h3 {
      margin: 0;
      font-size: 16px;
      color: #555;
    }

    .kpi-item p {
      margin: 5px 0 0;
      font-size: 20px;
      font-weight: bold;
      color: #005baa;
    }

    .table-container {
      max-height: 220px; /* Aproximadamente 5 filas */
      overflow-y: auto;
      overflow-x: auto;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      min-width: 600px;
    }

    th, td {
      padding: 10px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
    }

    th {
      background-color: #f1f1f1;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    tr:hover {
      background-color: #f9f9f9;
    }

  </style>
</head>
<body>

  <div class="header">
    <i class="fas fa-inbox"></i>
    <h1>Gestión de PQRS</h1>
  </div>

  <div class="content">

    <div class="card">
      <h2>Resumen de Estados</h2>
      <div class="kpi-grid">
        <?php foreach ($kpis as $estado => $total): ?>
          <div class="kpi-item">
            <h3><?php echo htmlspecialchars($estado); ?></h3>
            <p><?php echo $total; ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Listado de Casos PQRS</h2>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $cases = $pdo->query("SELECT id, cliente_nombre, status_clave, fecha FROM pqrs_case ORDER BY fecha DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cases as $case): ?>
              <tr>
                <td><?php echo htmlspecialchars($case['id']); ?></td>
                <td><?php echo htmlspecialchars($case['cliente_nombre']); ?></td>
                <td><?php echo htmlspecialchars($case['status_clave']); ?></td>
                <td><?php echo htmlspecialchars($case['fecha']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</body>
</html>
