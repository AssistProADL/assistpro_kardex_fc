<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    try {
        // Aquí puedes definir tus filtros y paginación si es necesario
        $sql = "
            SELECT
              ch.IDContenedor, ch.Clave_Contenedor, ch.descripcion, ch.Permanente, ch.Pedido, ch.sufijo, ch.tipo, ch.Activo,
              ch.alto, ch.ancho, ch.fondo, ch.peso, ch.pesomax, ch.capavol, ch.Costo, ch.CveLP, ch.TipoGen,
              ap.clave AS almac_clave, ap.nombre AS almac_nombre  -- Agregado almacén clave y nombre
            FROM c_charolas ch
            LEFT JOIN c_almacenp ap ON ap.id = ch.cve_almac
            WHERE ch.TipoGen = 1
            ORDER BY ch.Clave_Contenedor
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        // Obtenemos los datos de la consulta
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Devolvemos los datos en formato JSON
        echo json_encode(['rows' => $data]);
        exit;

    } catch (PDOException $e) {
        // Manejo de errores en caso de fallo en la consulta SQL
        echo json_encode(['error' => 'Error al ejecutar la consulta', 'message' => $e->getMessage()]);
        exit;
    }
}
