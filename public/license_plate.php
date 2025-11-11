<?php
// license_plate.php
// Inicializa aplicación y conexión
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

// Consulta principal: solo charolas con License Plate definido
$sql = "
    SELECT
        IDContenedor,
        cve_almac,
        Clave_Contenedor,
        descripcion,
        Permanente,
        Pedido,
        sufijo,
        tipo,
        Activo,
        alto,
        ancho,
        fondo,
        peso,
        pesomax,
        capavol,
        Costo,
        CveLP,
        TipoGen
    FROM c_charolas
    WHERE COALESCE(CveLP, '') <> ''
    ORDER BY cve_almac, CveLP, Clave_Contenedor
";

// Obtener datos usando helpers de AssistPro (db_all) o PDO puro según exista
if (function_exists('db_all')) {
    // Estándar AssistPro ETL
    $rows = db_all($sql);
} else {
    // Fallback por si en este proyecto se expone $pdo
    if (!isset($pdo)) {
        die('No se encontró conexión a base de datos. Verifica app/db.php.');
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>License Plate - Charolas</title>

    <!-- Bootstrap / estilos básicos (si ya los cargas en _menu_global, estos no estorban) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-size: 10px;
        }
        .table thead th {
            white-space: nowrap;
        }
        .card-header {
            background:#0F5AAD;
            color:#fff;
            font-weight:600;
        }
        .badge-perm {
            background:#198754;
        }
        .badge-temp {
            background:#6c757d;
        }
        .badge-activo {
            background:#198754;
        }
        .badge-inactivo {
            background:#dc3545;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-3">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>License Plate (c_charolas)</span>
            <span class="small">Control de charolas / contenedores</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblLicensePlate" class="table table-striped table-bordered table-sm align-middle">
                    <thead>
                    <tr>
                        <th>LP</th>
                        <th>Clave Contenedor</th>
                        <th>Descripción</th>
                        <th>Almacén</th>
                        <th>Tipo</th>
                        <th>Permanente</th>
                        <th>Activo</th>
                        <th>Dimensiones (Al x An x Fo)</th>
                        <th>Peso (kg)</th>
                        <th>Peso Máx (kg)</th>
                        <th>Cap. Vol.</th>
                        <th>Pedido</th>
                        <th>Sufijo</th>
                        <th>Costo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['CveLP']); ?></td>
                            <td><?php echo htmlspecialchars($row['Clave_Contenedor']); ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                            <td><?php echo (int)$row['cve_almac']; ?></td>
                            <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                            <td class="text-center">
                                <?php if ((int)$row['Permanente'] === 1): ?>
                                    <span class="badge badge-perm">Permanente</span>
                                <?php else: ?>
                                    <span class="badge badge-temp">Temporal</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$row['Activo'] === 1): ?>
                                    <span class="badge badge-activo">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                echo (int)$row['alto'] . ' x ' .
                                     (int)$row['ancho'] . ' x ' .
                                     (int)$row['fondo'];
                                ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['peso'], 3); ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['pesomax'], 3); ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['capavol'], 3); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['Pedido']); ?></td>
                            <td><?php echo htmlspecialchars($row['sufijo']); ?></td>
                            <td class="text-end">
                                <?php echo number_format((float)$row['Costo'], 3); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="mt-2 text-muted">
                Mostrando charolas con License Plate asignado (campo <strong>CveLP</strong> de <strong>c_charolas</strong>).
            </p>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(function () {
        $('#tblLicensePlate').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
