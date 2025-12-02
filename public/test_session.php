<?php
/**
 * public/test_session.php
 * Script de prueba para verificar el estado de la sesión
 */

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Iniciar sesión
\AssistPro\Helpers\SessionManager::init();

// Mostrar información de sesión
header('Content-Type: application/json');
echo json_encode([
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_name' => session_name(),
    'is_authenticated' => \AssistPro\Helpers\SessionManager::isAuthenticated(),
    'user_id' => \AssistPro\Helpers\SessionManager::getUserId(),
    'time_remaining' => \AssistPro\Helpers\SessionManager::getTimeRemaining(),
    'all_session_data' => \AssistPro\Helpers\SessionManager::all()
]);
