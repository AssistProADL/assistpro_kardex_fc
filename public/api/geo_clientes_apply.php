<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php';

try {

    $pdo = db_pdo();
    $pdo->beginTransaction();

    $raw = file_get_contents("php://input");
    $in  = json_decode($raw, true);

    if (!is_array($in)) {
        throw new Exception("Payload JSON inválido");
    }

    $almacen   = (int)($in['almacen'] ?? 0);
    $rutaNueva = (int)($in['ruta_id'] ?? 0);
    $destinatarios  = $in['destinatarios'] ?? [];

    if (!$almacen || !$rutaNueva || !is_array($destinatarios)) {
        throw new Exception("Parámetros incompletos");
    }

    $movidos = 0;

    foreach ($destinatarios as $idDest) {

        $idDest = (int)$idDest;
        if (!$idDest) continue;

        // Obtener datos actuales
        $stmt = $pdo->prepare("
            SELECT Cve_Ruta, Cve_Cliente, Cve_Vendedor
            FROM reldaycli
            WHERE Cve_Almac = ?
              AND Id_Destinatario = ?
            LIMIT 1
        ");
        $stmt->execute([$almacen, $idDest]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actual) continue;

        $rutaActual = (int)$actual['Cve_Ruta'];
        $cveCliente = $actual['Cve_Cliente'];
        $cveVendedor = $actual['Cve_Vendedor'];

        if ($rutaActual === $rutaNueva) continue;

        // 1️⃣ Borrar días actuales
        $pdo->prepare("
            DELETE FROM reldaycli
            WHERE Cve_Almac = ?
              AND Id_Destinatario = ?
        ")->execute([$almacen, $idDest]);

        // 2️⃣ Insertar nueva relación SIN días
        $pdo->prepare("
            INSERT INTO reldaycli
            (Cve_Almac, Cve_Ruta, Cve_Cliente, Id_Destinatario, Cve_Vendedor,
             Lu, Ma, Mi, Ju, Vi, Sa, Do)
            VALUES (?, ?, ?, ?, ?, 0,0,0,0,0,0,0)
        ")->execute([
            $almacen,
            $rutaNueva,
            $cveCliente,
            $idDest,
            $cveVendedor
        ]);

        // 3️⃣ Verificar si relclirutas ya tiene cliente en esa ruta
        $chk = $pdo->prepare("
            SELECT COUNT(*)
            FROM relclirutas
            WHERE IdEmpresa = ?
              AND IdRuta = ?
              AND IdCliente = ?
        ");
        $chk->execute([$almacen, $rutaNueva, $cveCliente]);
        $existe = $chk->fetchColumn();

        if (!$existe) {
            $pdo->prepare("
                INSERT INTO relclirutas
                (IdCliente, IdRuta, IdEmpresa, Fecha)
                VALUES (?, ?, ?, CURDATE())
            ")->execute([$cveCliente, $rutaNueva, $almacen]);
        }

        $movidos++;
    }

    $pdo->commit();

    echo json_encode([
        'ok' => 1,
        'movidos' => $movidos
    ]);

} catch (Throwable $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'ok' => 0,
        'error' => $e->getMessage()
    ]);
}
