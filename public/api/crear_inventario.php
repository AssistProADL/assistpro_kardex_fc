<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/db.php';

try {

    $tipo = trim($_POST['tipo'] ?? '');

    $tipos_validos = [
        'INICIAL'          => 'INVF',
        'FISICO'           => 'INVF',
        'CICLICO'          => 'INVC',
        'INICIAL_CICLICO'  => 'INVC',
    ];

    if (!isset($tipos_validos[$tipo])) {
        throw new Exception('Tipo de inventario inválido');
    }

    $prefijo = $tipos_validos[$tipo];

    /* ============================
     * 1️⃣ Verificar BORRADOR activo
     * ============================ */
    $borrador = db_row("
        SELECT ID_Inventario, Nombre
        FROM th_inventario
        WHERE Status = 'BORRADOR'
        ORDER BY Fecha DESC
        LIMIT 1
    ");

    if ($borrador) {
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Inventario en borrador reutilizado',
            'id_inventario' => $borrador['ID_Inventario'],
            'folio' => $borrador['Nombre']
        ]);
        exit;
    }

    /* ============================
     * 2️⃣ Generar nuevo folio
     * ============================ */
    $fecha = date('Ymd');

    $ultimo = db_val("
        SELECT COUNT(*) 
        FROM th_inventario
        WHERE Nombre LIKE ?
    ", ["{$prefijo}-{$fecha}-%"], 0);

    $consecutivo = (int)$ultimo + 1;
    $folio = "{$prefijo}-{$fecha}-{$consecutivo}";

    /* ============================
     * 3️⃣ Insertar BORRADOR
     * ============================ */
    dbq("
        INSERT INTO th_inventario
        (Fecha, Nombre, Status, Activo, Inv_Inicial)
        VALUES (NOW(), ?, 'BORRADOR', 1, ?)
    ", [
        $folio,
        ($tipo === 'INICIAL' || $tipo === 'INICIAL_CICLICO') ? 1 : 0
    ]);

    $id_inventario = (int)db_val("SELECT LAST_INSERT_ID()");

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Inventario creado en borrador',
        'id_inventario' => $id_inventario,
        'folio' => $folio,
        'tipo' => $tipo
    ]);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
