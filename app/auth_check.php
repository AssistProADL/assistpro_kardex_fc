<?php
/**
 * app/auth_check.php
 * Middleware para verificar sesión activa.
 * Incluir al inicio de cualquier script protegido.
 */

// Configuración de tiempo de vida de la sesión (15 minutos = 900 segundos)
$timeout_duration = 900;

if (session_status() === PHP_SESSION_NONE) {
    // Asegurar que la cookie de sesión dure lo suficiente
    ini_set('session.gc_maxlifetime', (string) $timeout_duration);
    session_set_cookie_params($timeout_duration, '/');
    session_start();
}

// Verificar timeout por inactividad
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    // Sesión expirada
    session_unset();     // liberar variables
    session_destroy();   // destruir datos de sesión

    // Redirigir al login con mensaje
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }
    header("Location: /assistpro_kardex_fc/public/login.php?err=" . urlencode("Tu sesión ha expirado por inactividad."));
    exit;
}

// Actualizar tiempo de última actividad (regenerar/extender sesión)
$_SESSION['LAST_ACTIVITY'] = time();

// Verificar si existe la variable de sesión 'username'
if (empty($_SESSION['username'])) {
    // Si es una petición AJAX, devolver 401
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }

    // Redirigir al login
    header("Location: /assistpro_kardex_fc/public/login.php?err=" . urlencode("Debes iniciar sesión."));
    exit;
}

