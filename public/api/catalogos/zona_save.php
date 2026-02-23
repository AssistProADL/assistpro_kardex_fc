<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/app/db.php';

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error conexión: ' . $e->getMessage()
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id          = isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null;
$clave       = strtoupper(trim($data['clave'] ?? ''));
$descripcion = trim($data['descripcion'] ?? '');
$tipo        = trim($data['tipo'] ?? '');
$abc         = strtoupper(trim($data['abc'] ?? ''));
$almacen     = isset($data['almacen']) ? (int)$data['almacen'] : null;

if (!$clave || !$descripcion || !$almacen) {
    echo json_encode([
        'ok' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

try {

    // 🔎 Validar duplicado (clave + almacen)
    $sqlCheck = "
        SELECT cve_almac 
        FROM c_almacen
        WHERE clave_almacen = :clave
        AND cve_almacenp = :almacen
    ";

    if ($id) {
        $sqlCheck .= " AND cve_almac != :id";
    }

    $stmtCheck = $pdo->prepare($sqlCheck);

    $paramsCheck = [
        'clave'   => $clave,
        'almacen' => $almacen
    ];

    if ($id) {
        $paramsCheck['id'] = $id;
    }

    $stmtCheck->execute($paramsCheck);

    if ($stmtCheck->fetch()) {
        echo json_encode([
            'ok' => false,
            'error' => 'La clave ya existe en este almacén'
        ]);
        exit;
    }

    if ($id) {
        // 🔄 UPDATE
        $sql = "
            UPDATE c_almacen
            SET
                clave_almacen = :clave,
                des_almac = :descripcion,
                Cve_TipoZona = :tipo,
                clasif_abc = :abc
            WHERE cve_almac = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'clave'       => $clave,
            'descripcion' => $descripcion,
            'tipo'        => $tipo ?: null,
            'abc'         => $abc ?: null,
            'id'          => $id
        ]);

    } else {
        // ➕ INSERT
        $sql = "
            INSERT INTO c_almacen
            (
                clave_almacen,
                cve_almacenp,
                des_almac,
                Cve_TipoZona,
                clasif_abc,
                Activo
            )
            VALUES
            (
                :clave,
                :almacen,
                :descripcion,
                :tipo,
                :abc,
                1
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'clave'       => $clave,
            'almacen'     => $almacen,
            'descripcion' => $descripcion,
            'tipo'        => $tipo ?: null,
            'abc'         => $abc ?: null
        ]);

        $id = $pdo->lastInsertId();
    }

    echo json_encode([
        'ok' => true,
        'id' => $id
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'ok' => false,
        'error' => 'Error DB: ' . $e->getMessage()
    ]);
}