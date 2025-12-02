<?php
/**
 * app/auth_check.php
 * Middleware para verificar sesión activa.
 * Incluir al inicio de cualquier script protegido.
 */

// Cargar autoloader si no está cargado
if (!class_exists('\AssistPro\Helpers\SessionManager')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Iniciar sesión unificada (maneja timeout automáticamente)
\AssistPro\Helpers\SessionManager::init();

// Verificar si el usuario está autenticado
if (!\AssistPro\Helpers\SessionManager::isAuthenticated()) {
    // Si es una petición AJAX, devolver 401
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No autenticado', 'message' => 'Debes iniciar sesión.']);
        exit;
    }

    // Redirigir al login
    header("Location: /assistpro_kardex_fc/public/login.php?err=" . urlencode("Debes iniciar sesión."));
    exit;
}

// Verificar si la sesión expiró por timeout (SessionManager lo maneja automáticamente)
// Si llegamos aquí, la sesión es válida
