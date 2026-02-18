<?php
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

                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>
