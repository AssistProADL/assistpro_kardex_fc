<?php
 
/**
 * Cargar DB
 * app está al mismo nivel que public
 */
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
    if (!$pdo) {
        throw new Exception('PDO not initialized');
    }
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => 'DB connection failed',
        'detail' => $e->getMessage()
    ]);
    exit;
}
