<?php
/**
 * public/logout.php
 * Cerrar sesión del usuario
 */

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Destruir sesión usando SessionManager
\AssistPro\Helpers\SessionManager::destroy();

// Redirigir al login
header('Location: /assistpro_kardex_fc/public/login.php');
exit;
