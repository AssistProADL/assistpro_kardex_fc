<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

try {

    $idInventario = (int)$_POST['id_inventario'];
    $usuario      = (int)$_POST['usuario'];

    if (!$idInventario || !$usuario) {
        throw new Exception('Parámetros inválidos');
    }

    $pdo->beginTransaction();

    // Bloquear inventario
    $inv = $pdo->prepare("
        SELECT estado
        FROM inventario
        WHERE id_inventario = ?
        FOR UPDATE
    ");
    $inv->execute([$idInventario]);
    $estado = $inv->fetchColumn();

    if (!in_array($estado, ['EN_CONTEO','EN_VALIDACION'])) {
        throw new Exception('Inventario no puede cerrarse en este estado');
    }

    // Limpiar resultados previos si existieran
    $pdo->prepare("
        DELETE FROM inventario_resultado
        WHERE id_inventario = ?
    ")->execute([$idInventario]);

    // Consolidar resultados
    $sql = "
    SELECT
        io.tipo_objeto,
        io.id_referencia,
        io.snapshot_teorico,
        COUNT(ic.id_conteo) AS total_conteos,
        AVG(ic.cantidad) AS cantidad_contada
    FROM inventario_objeto io
    LEFT JOIN inventario_conteo ic
           ON ic.id_inventario = io.id_inventario
          AND ic.tipo_objeto = io.tipo_objeto
          AND ic.id_referencia = io.id_referencia
    WHERE io.id_inventario = ?
    GROUP BY io.tipo_objeto, io.id_referencia, io.snapshot_teorico
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idInventario]);

    $ins = $pdo->prepare("
        INSERT INTO inventario_resultado
        (id_inventario, tipo_objeto, id_referencia,
         cantidad_teorica, cantidad_contada, diferencia,
         conteos_validos, estatus)
        VALUES (?,?,?,?,?,?,?,?)
    ");

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

        if ((int)$r['total_conteos'] < 2) {
            throw new Exception(
                'No todos los objetos tienen mínimo 2 conteos'
            );
        }

        $contada = round((float)$r['cantidad_contada'], 4);
        $teorica = (float)$r['snapshot_teorico'];
        $dif = round($contada - $teorica, 4);

        $estatus = ($dif == 0.0) ? 'OK' : 'DIFERENCIA';

        $ins->execute([
            $idInventario,
            $r['tipo_objeto'],
            $r['id_referencia'],
            $teorica,
            $contada,
            $dif,
            (int)$r['total_conteos'],
            $estatus
        ]);
    }

    // Cerrar inventario
    $pdo->prepare("
        UPDATE inventario
        SET estado = 'CERRADO'
        WHERE id_inventario = ?
    ")->execute([$idInventario]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Inventario cerrado correctamente'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
