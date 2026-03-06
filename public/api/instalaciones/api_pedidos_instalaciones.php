<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/db.php';

try {
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => 0,
        'error' => 'Error de conexión BD',
        'detalle' => $e->getMessage()
    ]);
    exit;
}

function jexit(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = strtolower(trim($_GET['action'] ?? ''));

if ($action === '') {
    jexit(['ok' => 0, 'error' => 'Acción requerida'], 400);
}

/*
|--------------------------------------------------------------------------
| BUSCAR PEDIDOS (para módulo instalaciones)
|--------------------------------------------------------------------------
*/
if ($action === 'buscar') {

    $q = trim($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 15);

    if ($limit <= 0 || $limit > 50) {
        $limit = 15;
    }

    if (strlen($q) < 2) {
        jexit(['ok' => 1, 'rows' => []]);
    }

    $sql = "
        SELECT
            h.id_pedido,
            h.Fol_folio,
            h.Cve_clte,
            c.RazonSocial,
            h.cve_ubicacion,
            h.destinatario
        FROM th_pedido h
        LEFT JOIN c_cliente c 
            ON c.Cve_Clte = h.Cve_clte
        WHERE h.Fol_folio LIKE ?
          AND COALESCE(h.Activo,1)=1
        ORDER BY h.Fol_folio DESC
        LIMIT $limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$q%"]);

    jexit([
        'ok' => 1,
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

/*
|--------------------------------------------------------------------------
| CONSULTAR PEDIDO COMPLETO (header + detail)
|--------------------------------------------------------------------------
*/
if ($action === 'consultar') {

    $id = (int)($_GET['id_pedido'] ?? 0);

    if ($id <= 0) {
        jexit(['ok' => 0, 'error' => 'id_pedido inválido'], 400);
    }

    // HEADER
    $stmt = $pdo->prepare("
        SELECT
            h.*,
            c.RazonSocial
        FROM th_pedido h
        LEFT JOIN c_cliente c
            ON c.Cve_Clte = h.Cve_clte
        WHERE h.id_pedido = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);

    $header = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        jexit(['ok' => 0, 'error' => 'Pedido no encontrado'], 404);
    }

    // DETAIL
    $stmt = $pdo->prepare("
        SELECT *
        FROM td_pedido
        WHERE Fol_folio = ?
        ORDER BY itemPos ASC, id ASC
    ");
    $stmt->execute([$header['Fol_folio']]);

    $detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jexit([
        'ok' => 1,
        'header' => $header,
        'detail' => $detail
    ]);
}

jexit(['ok' => 0, 'error' => 'Acción no soportada'], 400);
