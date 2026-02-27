<?php
/**
 * app/auth_check.php
 * Middleware para verificar sesión activa.
 * Incluir al inicio de cualquier script protegido.
 */

// Cargar autoloader si no está cargado AO
if (!class_exists('\AssistPro\Helpers\SessionManager')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Iniciar sesión unificada (maneja timeout automáticamente)
\AssistPro\Helpers\SessionManager::init();

// Obtener URI actual
$uri = $_SERVER['REQUEST_URI'] ?? '';

// 🔓 EXCEPCIONES: rutas que NO requieren sesión
$publicRoutes = [
    '/api/login',
    '/api/test', // opcional (para pruebas)
    '/login.php'
];

foreach ($publicRoutes as $route) {
    if (strpos($uri, $route) !== false) {
        return; // permitir acceso sin autenticación
    }
}

// Verificar si el usuario está autenticado
if (!\AssistPro\Helpers\SessionManager::isAuthenticated()) {

    // Si es una petición AJAX, devolver 401 JSON
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'No autenticado',
            'message' => 'Debes iniciar sesión.'
        ]);
        exit;
    }

    // Si es petición normal, redirigir al login
    header("Location: /assistpro_kardex_fc/public/login.php?err=" . urlencode("Debes iniciar sesión."));
    exit;
}

// Si llegamos aquí, la sesión es válida