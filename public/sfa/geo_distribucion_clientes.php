<?php
// Incluir la conexión PDO
require_once('../../app/db.php');

// Incluir el archivo del menú global al inicio
include('../bi/_menu_global.php');

// Establecer la conexión a la base de datos
$pdo = db_pdo();  // Llamar a la función db_pdo() para obtener la instancia de PDO

// Consulta para obtener las rutas
$query_rutas = "SELECT ID_Ruta, descripcion FROM t_ruta";
$stmt_rutas = $pdo->prepare($query_rutas);
$stmt_rutas->execute();
$rutas = $stmt_rutas->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obtener los clientes de una ruta seleccionada
$query_clientes = "SELECT c.id_cliente, c.RazonSocial, c.latitud, c.longitud
                   FROM c_cliente c
                   JOIN relclirutas rc ON c.id_cliente = rc.IdCliente
                   WHERE rc.IdRuta = :idRuta";

// Verificar si se ha seleccionado una ruta
$clientes_data = [];
if (isset($_GET['ruta_id'])) {
    // Si la ruta está seleccionada, obtén los clientes para esa ruta
    $stmt_clientes = $pdo->prepare($query_clientes);
    $stmt_clientes->execute(['idRuta' => $_GET['ruta_id']]);
    $clientes_data = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
}

$clientes_data_json = json_encode($clientes_data); // Pasamos los datos de los clientes a formato JSON
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribución de Clientes por Ruta</title>
    <!-- Incluir la API de Google Maps con la API Key proporcionada -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC5xF7JtKzw9cTRRXcDAqTThbYnMCiYOVM&callback=initMap" async defer></script>
    <style>
        #map {
            height: 600px;
            width: 100%;
        }

        /* Estilo para el filtro de rutas */
        label {
            font-size: 18px;
        }

        select {
            font-size: 16px;
            padding: 5px;
        }
    </style>
</head>
<body>

    <h1>Distribución de Clientes en el Mapa</h1>

    <!-- Filtro de rutas -->
    <label for="ruta_select">Selecciona una Ruta:</label>
    <select id="ruta_select" onchange="updateMap()">
        <option value="">Selecciona una ruta</option>
        <?php foreach ($rutas as $ruta): ?>
            <option value="<?php echo $ruta['ID_Ruta']; ?>" <?php echo (isset($_GET['ruta_id']) && $_GET['ruta_id'] == $ruta['ID_Ruta']) ? 'selected' : ''; ?>>
                <?php echo $ruta['descripcion']; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div id="map"></div>

    <script>
        var clientes = <?php echo $clientes_data_json; ?>; // Datos de los clientes obtenidos desde PHP
        var map;
        var markers = []; // Array para almacenar los marcadores

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 10,
                center: { lat: 19.432608, lng: -99.133209 } // Coordenadas iniciales (Ciudad de México)
            });

            // Verificar si hay clientes y añadir marcadores para cada uno
            if (clientes.length > 0) {
                clientes.forEach(function(cliente) {
                    var marker = new google.maps.Marker({
                        position: { lat: parseFloat(cliente.latitud), lng: parseFloat(cliente.longitud) },
                        map: map,
                        title: cliente.RazonSocial
                    });

                    var infoWindow = new google.maps.InfoWindow({
                        content: '<b>' + cliente.RazonSocial + '</b><br>Latitud: ' + cliente.latitud + '<br>Longitud: ' + cliente.longitud
                    });

                    marker.addListener('click', function() {
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker); // Guardar el marcador en el array
                });
            } else {
                alert('No hay clientes disponibles para esta ruta.');
            }
        }

        // Función para cambiar la ruta y recargar el mapa
        function updateMap() {
            var rutaId = document.getElementById('ruta_select').value;
            if (rutaId) {
                window.location.href = '?ruta_id=' + rutaId;
            }
        }

        // Inicializar el mapa
        google.maps.event.addDomListener(window, 'load', initMap);
    </script>

    <!-- Incluir el archivo de cierre del menú global -->
    <?php include('../bi/_menu_global_end.php'); ?>

</body>
</html>
