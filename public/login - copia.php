<?php
/**
 * public/login.php
 * Entry point for authentication.
 * Uses Blade for rendering the UI.
 */

// 1. Bootstrap application and get Container
$container = require_once __DIR__ . '/../app/bootstrap.php';

// 2. Iniciar sesión unificada
\AssistPro\Helpers\SessionManager::init();

// 3. Redirect if already logged in
if (\AssistPro\Helpers\SessionManager::isAuthenticated()) {
  header('Location: /assistpro_kardex_fc/public/dashboard/index.php');
  exit;
}


// 4. Get Blade View Factory from Container
$view = $container->make('view');

// 5. Handle GET parameters
$error = '';
$username = '';

if (isset($_GET['err'])) {
  $error = trim((string) $_GET['err']);
}
if (isset($_GET['u'])) {
  $username = trim((string) $_GET['u']);
}

// 6. Render View
echo $view->make('auth.login', [
  'error' => $error,
  'username' => $username
])->render();

