<?php
// public/logout.php
if (session_status() === PHP_SESSION_NONE)
  session_start();

// Destruir todas las variables de sesión
$_SESSION = [];

// Borrar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    '/', // Forzar ruta raíz
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: /assistpro_kardex_fc/public/login.php');
exit;
