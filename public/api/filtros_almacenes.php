<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json');

try {

    $sql = "
        SELECT 
            id,
            nombre
        FROM c_almacenp
        WHERE Activo = 1
        ORDER BY nombre
    ";

    $data = db_all($sql);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error al cargar almacenes',
        'msg'   => $e->getMessage()
    ]);
}
