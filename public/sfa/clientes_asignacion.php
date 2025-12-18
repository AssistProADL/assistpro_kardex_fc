<?php
// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

// Consultar datos de almacenes
$sql_almacenes = "SELECT id, nombre FROM c_almacenp WHERE Activo = 1";
$almacenes = db_all($sql_almacenes);

// Consultar datos de rutas
$sql_rutas = "SELECT ID_Ruta, cve_ruta, descripcion FROM t_ruta WHERE Activo = 1";
$rutas = db_all($sql_rutas);

// Consultar datos de agentes/operadores (vendedores)
$sql_vendedores = "SELECT Id_Vendedor, Nombre FROM t_vendedores WHERE Activo = 1";
$vendedores = db_all($sql_vendedores);

// Días de la semana
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

// Obtener los datos de los filtros (almacén, ruta, agente, día)
$almacen_filtro = isset($_GET['almacen']) ? $_GET['almacen'] : '';
$ruta_filtro = isset($_GET['ruta']) ? $_GET['ruta'] : '';
$agente_filtro = isset($_GET['agente']) ? $_GET['agente'] : '';
$dia_filtro = isset($_GET['dia']) ? $_GET['dia'] : '';

// Pagina los resultados (10 registros por página)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Número de página
$offset = ($page - 1) * 10; // Cálculo para el OFFSET

// Consultar los datos de clientes y destinos con la consulta proporcionada
$sql = "
    SELECT DISTINCT
        IFNULL(d.id_destinatario, '--') AS id,
        '' AS Secuencia,
        IFNULL(c.Cve_Clte, '__') AS clave_cliente,
        IFNULL(c.RazonSocial, '--') AS cliente, 
        IFNULL(c.RazonComercial, '--') AS razoncomercial, 
        IFNULL(d.razonsocial, '--') AS destinatario,
        GROUP_CONCAT(DISTINCT IFNULL(t_ruta.cve_ruta,'--') SEPARATOR ', ') AS ruta,
        IF(IFNULL(d.clave_destinatario, '') = '', d.id_destinatario, d.clave_destinatario) AS clave_destinatario,
        IF(ra.cve_vendedor != '', GROUP_CONCAT(DISTINCT u.nombre_completo SEPARATOR ', '), '') AS Agente,
        IFNULL(d.direccion, '--') AS direccion,
        IFNULL(d.colonia, '--') AS colonia,
        IFNULL(d.postal, '--') AS postal,
        IFNULL(d.ciudad, '--') AS ciudad,
        IFNULL(d.estado, '--') AS estado,
        IF(d.dir_principal = 1, IFNULL(c.latitud, '--'), IFNULL(d.latitud, '--')) AS latitud,
        IF(d.dir_principal = 1, IFNULL(c.longitud, '--'), IFNULL(d.longitud, '--')) AS longitud,
        IFNULL(d.contacto, '--') AS contacto,
        IFNULL(d.telefono, '--') AS telefono
    FROM c_destinatarios d
    LEFT JOIN c_cliente c ON d.Cve_Clte = c.Cve_Clte
    LEFT JOIN t_clientexruta ON t_clientexruta.clave_cliente = d.id_destinatario
    LEFT JOIN t_ruta ON t_ruta.ID_Ruta = t_clientexruta.clave_ruta
    LEFT JOIN RelDayCli ON t_ruta.ID_Ruta = RelDayCli.Cve_Ruta   
    LEFT JOIN Rel_Ruta_Agentes ra ON ra.cve_ruta = t_ruta.ID_Ruta 
    LEFT JOIN c_usuario u ON u.id_user = ra.cve_vendedor
    LEFT JOIN c_dane cp ON cp.cod_municipio = d.postal
    WHERE d.Activo = '1' AND c.Cve_Almacenp = '1' AND c.Cve_Clte = d.Cve_Clte AND t_ruta.cve_almacenp = '1'  
";

// Agregar filtros dinámicos
if (!empty($almacen_filtro)) {
    $sql .= " AND c.Cve_Almacenp = '$almacen_filtro'";
}
if (!empty($ruta_filtro)) {
    $sql .= " AND t_ruta.cve_ruta = '$ruta_filtro'";
}
if (!empty($agente_filtro)) {
    $sql .= " AND ra.cve_vendedor = '$agente_filtro'";
}

// Filtrar por día usando las columnas de día en RelDayCli
if (!empty($dia_filtro)) {
    $dia_column = '';
    switch ($dia_filtro) {
        case 'Lunes':
            $dia_column = 'Lu';
            break;
        case 'Martes':
            $dia_column = 'Ma';
            break;
        case 'Miércoles':
            $dia_column = 'Mi';
            break;
        case 'Jueves':
            $dia_column = 'Ju';
            break;
        case 'Viernes':
            $dia_column = 'Vi';
            break;
        case 'Sábado':
            $dia_column = 'Sa';
            break;
        case 'Domingo':
            $dia_column = 'Do';
            break;
    }

    // Asegurarse de que la columna corresponda con el día de la semana
    if ($dia_column) {
        $sql .= " AND RelDayCli.$dia_column = 1";  // Asumiendo que '1' indica que la ruta está activa para ese día
    }
}

$sql .= " GROUP BY clave_cliente LIMIT 10 OFFSET $offset";

// Ejecutar la consulta
$result = db_all($sql);

// Calcular el total de registros para la paginación
$sql_count = "
    SELECT COUNT(DISTINCT c.Cve_Clte) AS total
    FROM c_destinatarios d
    LEFT JOIN c_cliente c ON d.Cve_Clte = c.Cve_Clte
    LEFT JOIN t_clientexruta ON t_clientexruta.clave_cliente = d.id_destinatario
    LEFT JOIN t_ruta ON t_ruta.ID_Ruta = t_clientexruta.clave_ruta
    LEFT JOIN RelDayCli ON t_ruta.ID_Ruta = RelDayCli.Cve_Ruta   
    LEFT JOIN Rel_Ruta_Agentes ra ON ra.cve_ruta = t_ruta.ID_Ruta 
    LEFT JOIN c_usuario u ON u.id_user = ra.cve_vendedor
    LEFT JOIN c_dane cp ON cp.cod_municipio = d.postal
    WHERE d.Activo = '1' AND c.Cve_Almacenp = '1' AND c.Cve_Clte = d.Cve_Clte AND t_ruta.cve_almacenp = '1'
";

// Contar el total de registros sin paginación
$total_result = db_all($sql_count);
$total_clients = $total_result[0]['total'];
$total_pages = ceil($total_clients / 10);

// Estilo corporativo
echo '<style>
    body {
        font-size: 10px;
        font-family: Arial, sans-serif;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #0F5AAD;
        color: white;
    }
    .table-responsive {
        max-width: 100%;
        overflow-x: auto;
    }
    .scrollable-table {
        max-height: 400px;
        overflow-y: auto;
    }
</style>';

// Mostrar los filtros en la parte superior
echo '<form method="GET" action="clientes_asignacion.php">';
echo '<div>';
echo '<label for="almacen">Almacén:</label>';
echo '<select name="almacen" id="almacen">';
echo '<option value="">Seleccione Almacén</option>';
foreach ($almacenes as $almacen) {
    echo '<option value="' . $almacen['id'] . '" ' . ($almacen_filtro == $almacen['id'] ? 'selected' : '') . '>' . $almacen['nombre'] . '</option>';
}
echo '</select>';

echo '<label for="ruta">Ruta:</label>';
echo '<select name="ruta" id="ruta">';
echo '<option value="">Seleccione Ruta</option>';
foreach ($rutas as $ruta) {
    echo '<option value="' . $ruta['ID_Ruta'] . '" ' . ($ruta_filtro == $ruta['ID_Ruta'] ? 'selected' : '') . '>' . $ruta['descripcion'] . '</option>';
}
echo '</select>';

echo '<label for="agente">Agente | Operador:</label>';
echo '<select name="agente" id="agente">';
echo '<option value="">Seleccione Agente | Operador</option>';
foreach ($vendedores as $vendedor) {
    echo '<option value="' . $vendedor['Id_Vendedor'] . '" ' . ($agente_filtro == $vendedor['Id_Vendedor'] ? 'selected' : '') . '>' . $vendedor['Nombre'] . '</option>';
}
echo '</select>';

echo '<label for="dia">Día:</label>';
echo '<select name="dia" id="dia">';
echo '<option value="">Seleccione el Día</option>';
foreach ($dias as $dia) {
    echo '<option value="' . $dia . '" ' . ($dia_filtro == $dia ? 'selected' : '') . '>' . $dia . '</option>';
}
echo '</select>';

echo '<button type="submit">Buscar</button>';
echo '</div>';
echo '</form>';

// Mostrar los resultados en una tabla HTML
echo '<div class="table-responsive">';
echo '<div class="scrollable-table">';
echo '<table class="table table-striped">';
echo '<thead>';
echo '<tr>';
echo '<th>Cliente</th>';
echo '<th>Destinatario</th>';
echo '<th>Dirección</th>';
echo '<th>Colonia</th>';
echo '<th>Código Postal</th>';
echo '<th>Ciudad</th>';
echo '<th>Estado</th>';
echo '<th>Teléfono</th>';
echo '<th>Acciones</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if ($result) {
    foreach ($result as $row) {
        echo '<tr>';
        echo '<td>' . $row['cliente'] . '</td>';
        echo '<td>' . $row['destinatario'] . '</td>';
        echo '<td>' . $row['direccion'] . '</td>';
        echo '<td>' . $row['colonia'] . '</td>';
        echo '<td>' . $row['postal'] . '</td>';
        echo '<td>' . $row['ciudad'] . '</td>';
        echo '<td>' . $row['estado'] . '</td>';
        echo '<td>' . $row['telefono'] . '</td>';
        echo '<td><button class="btn btn-edit">Editar</button></td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="9">No se encontraron resultados.</td></tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';  // Fin del scrollable-table
echo '</div>';  // Fin del table-responsive

// Paginación
echo '<div>';
for ($i = 1; $i <= $total_pages; $i++) {
    echo '<a href="?page=' . $i . '&almacen=' . $almacen_filtro . '&ruta=' . $ruta_filtro . '&agente=' . $agente_filtro . '&dia=' . $dia_filtro . '">' . $i . '</a> ';
}
echo '</div>';
?>
