<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/db.php';
$pdo = db_pdo();

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["ok"=>false,"error"=>"JSON inválido"]);
    exit;
}

$id          = $input['id'] ?? null;
$almacen     = $input['almacen'] ?? null;
$clave       = trim($input['clave'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');
$devolucion  = $input['devolucion'] ?? 'N';
$crossdock   = $input['crossdock'] ?? 'N';

if (!$almacen || !$clave || !$descripcion) {
    echo json_encode(["ok"=>false,"error"=>"Datos incompletos"]);
    exit;
}

try {

    if ($id) {

        $stmt = $pdo->prepare("
            UPDATE tubicacionesretencion
            SET cve_ubicacion = ?,
                desc_ubicacion = ?,
                B_Devolucion = ?,
                AreaStagging = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $clave,
            $descripcion,
            $devolucion,
            $crossdock,
            $id
        ]);

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO tubicacionesretencion
            (cve_ubicacion, cve_almacp, desc_ubicacion, Activo, B_Devolucion, AreaStagging)
            VALUES (?,?,?,?,?,?)
        ");

        $stmt->execute([
            $clave,
            $almacen,
            $descripcion,
            1,
            $devolucion,
            $crossdock
        ]);
    }

    echo json_encode(["ok"=>true]);

} catch (Exception $e) {

    echo json_encode([
        "ok"=>false,
        "error"=>$e->getMessage()
    ]);
}