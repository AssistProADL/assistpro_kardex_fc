<?php
/**
 * Ejemplo de uso de SessionManager en archivos legacy PHP
 * 
 * Incluir este archivo al inicio de cualquier script PHP legacy
 * para usar el sistema unificado de sesiones
 */

// Cargar autoloader de Composer (ajustar ruta según ubicación del archivo)
require_once __DIR__ . '/../vendor/autoload.php';

// Iniciar sesión unificada
\AssistPro\Helpers\SessionManager::init();

// Ahora puedes usar tanto $_SESSION como SessionManager

// Ejemplos de uso:

// 1. Acceso directo (compatible con código legacy existente)
// $_SESSION['id_user'] sigue funcionando

// 2. Uso recomendado con SessionManager (más seguro)
$userId = \AssistPro\Helpers\SessionManager::getUserId();
$username = \AssistPro\Helpers\SessionManager::get('username');

// 3. Verificar autenticación
if (!\AssistPro\Helpers\SessionManager::isAuthenticated()) {
    header('Location: /login.php');
    exit;
}

// 4. Establecer valores
\AssistPro\Helpers\SessionManager::set('cve_almac', 'ALM001');

// 5. Ver tiempo restante de sesión
$minutosRestantes = \AssistPro\Helpers\SessionManager::getTimeRemaining() / 60;

// 6. Cerrar sesión
// \AssistPro\Helpers\SessionManager::destroy();
