<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

$pdo = db_pdo();

try {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        throw new Exception("JSON inválido");
    }

    // =========================
    // 1. LIMPIEZA Y VALIDACIÓN
    // =========================

    $id          = $input['id'] ?? null;
    $almacen     = $input['almacen'] ?? null;
    $clave       = trim($input['clave'] ?? '');
    $descripcion = trim($input['descripcion'] ?? '');
    $stagging    = $input['stagging'] ?? 'N';
    $status      = $input['status'] ?? 'A';
    $largo       = $input['largo'] ?? null;
    $ancho       = $input['ancho'] ?? null;
    $alto        = $input['alto'] ?? null;

    // Sanitizar clave (solo A-Z y 0-9)
    $clave = strtoupper(preg_replace('/[^A-Z0-9]/', '', $clave));

    if (!$almacen || !$clave || !$descripcion) {
        throw new Exception("Datos incompletos");
    }

    if (strlen($clave) < 2) {
        throw new Exception("La clave debe tener mínimo 2 caracteres");
    }

    if (!in_array($stagging, ['S','N'])) {
        $stagging = 'N';
    }

    if (!in_array($status, ['A','I'])) {
        $status = 'A';
    }

    // =========================
    // 2. VALIDAR DUPLICADOS
    // =========================

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM t_ubicacionembarque
        WHERE cve_ubicacion = ?
        AND cve_almac = ?
        AND ID_Embarque <> ?
    ");

    $stmt->execute([
        $clave,
        $almacen,
        $id ?? 0
    ]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception("La clave ya existe en esta zona");
    }

    // =========================
    // 3. INSERT / UPDATE
    // =========================

    if ($id) {

        $stmt = $pdo->prepare("
            UPDATE t_ubicacionembarque
            SET cve_ubicacion = ?,
                descripcion   = ?,
                AreaStagging  = ?,
                status        = ?,
                largo         = ?,
                ancho         = ?,
                alto          = ?
            WHERE ID_Embarque = ?
        ");

        $stmt->execute([
            $clave,
            $descripcion,
            $stagging,
            $status,
            $largo ?: null,
            $ancho ?: null,
            $alto ?: null,
            $id
        ]);

        $mensaje = "Registro actualizado correctamente";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO t_ubicacionembarque
            (cve_ubicacion, cve_almac, descripcion, AreaStagging, status, Activo, largo, ancho, alto)
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)
        ");

        $stmt->execute([
            $clave,
            $almacen,
            $descripcion,
            $stagging,
            $status,
            $largo ?: null,
            $ancho ?: null,
            $alto ?: null
        ]);

        $mensaje = "Registro creado correctamente";
    }

    echo json_encode([
        "ok" => true,
        "message" => $mensaje
    ]);

} catch (PDOException $e) {

    // Error de clave duplicada en índice único
    if ($e->errorInfo[1] == 1062) {
        echo json_encode([
            "ok" => false,
            "error" => "Clave duplicada en base de datos"
        ]);
    } else {
        echo json_encode([
            "ok" => false,
            "error" => "Error interno del sistema"
        ]);
    }

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}