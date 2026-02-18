<?php
<<<<<<< Updated upstream
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");

/* Obtener instancia PDO */
$pdo = db();   // <-- ESTA ES LA CLAVE

$sql = "
    SELECT i.*, 
           a.des_articulo,
           u.nombre_completo
    FROM t_instalaciones i
    INNER JOIN c_articulo a ON i.id_activo = a.id
    INNER JOIN c_usuario u ON i.id_tecnico = u.id_user
    ORDER BY i.id_instalacion DESC
";

$stmt = $pdo->query($sql);
$instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
=======
// Conexión BD (está en /app)
require_once(__DIR__ . "/../../app/db.php");

// Menú global (está en /public/bi)
require_once(__DIR__ . "/../bi/_menu_global.php");

$sql = "SELECT i.*, 
               a.marca, a.modelo,
               u.nombre_completo
        FROM t_instalaciones i
        INNER JOIN c_activos a ON i.id_activo = a.id_activo
        INNER JOIN c_usuario u ON i.id_tecnico = u.id_user
        ORDER BY i.id_instalacion DESC";

$result = mysqli_query($conn, $sql);
>>>>>>> Stashed changes
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Instalaciones</h4>
        <a href="create.php" class="btn btn-primary">
            <i class="fa fa-plus"></i> Nueva Instalación
        </a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Folio</th>
<<<<<<< Updated upstream
                        <th>Activo</th>
                        <th>Técnico</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th width="180">Acciones</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach($instalaciones as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['folio']) ?></td>
                        <td><?= htmlspecialchars($row['des_articulo']) ?></td>
                        <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($row['fecha_instalacion']) ?></td>
                        <td>
                            <span class="badge bg-<?=
                                $row['estado']=='COMPLETADO'?'success':
                                ($row['estado']=='BORRADOR'?'warning':'secondary')
                            ?>">
                                <?= htmlspecialchars($row['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id_instalacion'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                               Editar
                            </a>

                            <a href="checklist.php?id=<?= $row['id_instalacion'] ?>" 
                               class="btn btn-sm btn-outline-success">
                               Checklist
                            </a>

                            <a href="print.php?id=<?= $row['id_instalacion'] ?>" 
                               class="btn btn-sm btn-outline-dark" target="_blank">
                               PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

=======
                        <th>Unidad</th>
                        <th>Técnico</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th width="140"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['folio']) ?></td>
                            <td><?= htmlspecialchars($row['marca']." ".$row['modelo']) ?></td>
                            <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($row['fecha_instalacion']) ?></td>
                            <td>
                                <span class="badge bg-<?=
                                    $row['estado']=='COMPLETADO'?'success':
                                    ($row['estado']=='BORRADOR'?'warning':'secondary')
                                ?>">
                                    <?= htmlspecialchars($row['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit.php?id=<?= $row['id_instalacion'] ?>" 
                                   class="btn btn-sm btn-outline-primary">Editar</a>
                                <a href="print.php?id=<?= $row['id_instalacion'] ?>" 
                                   class="btn btn-sm btn-outline-dark" target="_blank">PDF</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No hay instalaciones registradas
                            </td>
                        </tr>
                    <?php endif; ?>
>>>>>>> Stashed changes
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>
