<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/db.php';

try {

    // Leer JSON del body
    $input = json_decode(file_get_contents("php://input"), true);

    $id     = $input['id']     ?? null;
    $campo  = $input['campo']  ?? null;
    $valor  = $input['valor']  ?? null;

    // Validaciones básicas
    if (!$id || !$campo || $valor === null) {
        echo json_encode([
            'ok' => false,
            'error' => 'Datos incompletos'
        ]);
        exit;
    }

    // Solo permitir campos válidos (seguridad)
    $camposPermitidos = ['B_Devolucion', 'AreaStagging'];

    if (!in_array($campo, $camposPermitidos)) {
        echo json_encode([
            'ok' => false,
            'error' => 'Campo no permitido'
        ]);
        exit;
    }

    // Validar valor permitido
    if (!in_array($valor, ['S','N'])) {
        echo json_encode([
            'ok' => false,
            'error' => 'Valor inválido'
        ]);
        exit;
    }

    // Update seguro
    $sql = "UPDATE tubicacionesretencion 
            SET $campo = :valor 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':valor' => $valor,
        ':id'    => $id
    ]);

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}