<?php
// public/api/almacenes.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        lista($pdo);
        break;

    default:
        echo json_encode([
            'ok' => false,
            'error' => 'Acción no válida'
        ], JSON_UNESCAPED_UNICODE);
        break;
}

function lista(PDO $pdo)
{
    try {
        $sql = "SELECT Id, clave, nombre
                  FROM t_almacenp
                 WHERE (Activo = 1 OR Activo IS NULL)
              ORDER BY clave";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'data' => $rows
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
