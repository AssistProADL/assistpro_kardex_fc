<?php
// Incluir la conexión a la base de datos
require_once __DIR__ . '/../../app/db.php'; 
$pdo = db_pdo();

// Incluir el menú global
include __DIR__ . '/../bi/_menu_global.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificación de Rutas</title>
    <link rel="stylesheet" href="path_to_your_css.css"> <!-- Add your custom CSS -->
    <style>
        /* Spinner style */
        .spinner {
            border: 4px solid #f3f3f3; /* Light gray */
            border-top: 4px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Planificación de Rutas</h1>

        <form action="rutas_planning.php" method="POST">
            <!-- Select Empresa -->
            <div class="form-group">
                <label for="empresa">Empresa:</label>
                <select name="empresa" id="empresa" class="form-control" onchange="updateAlmacen()">
                    <?php
                    // Fetch companies from the c_compania table
                    $sql = "SELECT cve_cia, des_cia FROM c_compania WHERE Activo = 1";
                    $result = $pdo->query($sql);
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$row['cve_cia']}'>{$row['des_cia']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Select Almacen (Warehouse) -->
            <div class="form-group">
                <label for="almacen">Almacen:</label>
                <select name="almacen" id="almacen" class="form-control">
                    <!-- Almacen options will be dynamically populated -->
                </select>
            </div>

            <!-- Select Ruta -->
            <div class="form-group">
                <label for="ruta">Ruta:</label>
                <select name="ruta" id="ruta" class="form-control">
                    <?php
                    // Fetch routes from the t_ruta table
                    $sql = "SELECT ID_Ruta, descripcion FROM t_ruta WHERE Activo = 1";
                    $result = $pdo->query($sql);
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$row['ID_Ruta']}'>{$row['descripcion']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Select Vendedor -->
            <div class="form-group">
                <label for="vendedor">Vendedor:</label>
                <select name="vendedor" id="vendedor" class="form-control">
                    <?php
                    // Fetch vendors from the t_vendedores table
                    $sql = "SELECT Id_Vendedor, Nombre FROM t_vendedores WHERE Activo = 1";
                    $result = $pdo->query($sql);
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$row['Id_Vendedor']}'>{$row['Nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Select Transporte -->
            <div class="form-group">
                <label for="transporte">Transporte:</label>
                <select name="transporte" id="transporte" class="form-control">
                    <?php
                    // Fetch transports from the t_transporte table
                    $sql = "SELECT id, Nombre FROM t_transporte WHERE Activo = 1";
                    $result = $pdo->query($sql);
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$row['id']}'>{$row['Nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="asignar" class="btn btn-primary">Asignar</button>
                <button type="reset" class="btn btn-secondary">Limpiar</button>
            </div>
        </form>

        <!-- Grilla Inferior: Tabla de rutas -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>Ruta</th> <!-- Display cve_ruta as 'Ruta' -->
                    <th>Descripcion</th> <!-- Display descripcion -->
                    <th>Clave</th> <!-- Display Id_Vendedor as 'Clave' -->
                    <th>Vendedor</th> <!-- Display Nombre as 'Vendedor' -->
                    <th>Transporte</th> <!-- Display vehicle name as 'Transporte' -->
                    <th>Estado</th> <!-- Display spinner or Active/Inactive -->
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all routes from the t_ruta table and display them
                $sql = "SELECT r.ID_Ruta, r.cve_ruta, r.descripcion AS ruta_desc, v.Id_Vendedor, v.Nombre AS vendedor_nombre, 
                               t.Nombre AS transporte_nombre, r.status, r.Activo
                        FROM t_ruta r
                        LEFT JOIN relvendrutas rv ON r.ID_Ruta = rv.IdRuta
                        LEFT JOIN t_vendedores v ON rv.IdVendedor = v.Id_Vendedor
                        LEFT JOIN rel_ruta_transporte rrt ON r.ID_Ruta = rrt.cve_ruta
                        LEFT JOIN t_transporte t ON rrt.id_transporte = t.id
                        WHERE r.Activo = 1";
                $result = $pdo->query($sql);
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $status = ($row['Activo'] == 1) ? 'Activo' : 'Inactivo';
                    $spinner = ($row['Activo'] == 1) ? "<div class='spinner'></div>" : "";
                    
                    echo "<tr>
                            <td><button class='btn btn-danger'>Eliminar</button></td>
                            <td>{$row['cve_ruta']}</td> <!-- Display cve_ruta -->
                            <td>{$row['ruta_desc']}</td> <!-- Display descripcion -->
                            <td>{$row['Id_Vendedor']}</td> <!-- Display Id_Vendedor as 'Clave' -->
                            <td>{$row['vendedor_nombre']}</td> <!-- Display Nombre as 'Vendedor' -->
                            <td>{$row['transporte_nombre']}</td> <!-- Display vehicle name as 'Transporte' -->
                            <td>{$status} {$spinner}</td> <!-- Display Active/Inactivo + Spinner if Active -->
                        </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php 
    // Incluir el archivo de cierre del menú global
    include __DIR__ . '/../bi/_menu_global_end.php'; 
    ?>

</body>

<script>
// Function to update Almacen dropdown based on selected Empresa
function updateAlmacen() {
    var empresaId = document.getElementById("empresa").value;

    // Make an AJAX request to fetch the Almacen based on selected Empresa
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'rutas_planning.php?empresa_id=' + empresaId, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("almacen").innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}
</script>

</html>

<?php
// Handling form submission for "Asignar"
if (isset($_POST['asignar'])) {
    // Capture selected values from the form
    $ruta_id = $_POST['ruta'];
    $vendedor_id = $_POST['vendedor'];
    $transporte_id = $_POST['transporte'];
    $empresa_id = $_POST['empresa'];
    $almacen_id = $_POST['almacen'];

    // Delete existing vendor and transport for the route in relvendrutas and rel_ruta_transporte
    $delete_sql_vendedor = "DELETE FROM relvendrutas WHERE IdRuta = :ruta_id";
    $stmt_vendedor = $pdo->prepare($delete_sql_vendedor);
    $stmt_vendedor->execute([':ruta_id' => $ruta_id]);

    $delete_sql_transporte = "DELETE FROM rel_ruta_transporte WHERE cve_ruta = :ruta_id";
    $stmt_transporte = $pdo->prepare($delete_sql_transporte);
    $stmt_transporte->execute([':ruta_id' => $ruta_id]);

    // Insert the new vendor into relvendrutas
    $insert_sql_vendedor = "INSERT INTO relvendrutas (IdRuta, IdVendedor, IdEmpresa, Fecha) 
                            VALUES (:ruta_id, :vendedor_id, :empresa_id, NOW())";
    $stmt_vendedor = $pdo->prepare($insert_sql_vendedor);
    $stmt_vendedor->execute([
        ':ruta_id' => $ruta_id,
        ':vendedor_id' => $vendedor_id,
        ':empresa_id' => $empresa_id
    ]);

    // Insert the new transport into rel_ruta_transporte
    $insert_sql_transporte = "INSERT INTO rel_ruta_transporte (cve_ruta, id_transporte) 
                              VALUES (:ruta_id, :transporte_id)";
    $stmt_transporte = $pdo->prepare($insert_sql_transporte);
    $stmt_transporte->execute([
        ':ruta_id' => $ruta_id,
        ':transporte_id' => $transporte_id
    ]);

    // Optionally, you can provide feedback after inserting
    echo "<script>alert('Ruta asignada exitosamente');</script>";
}
?>
