<?php
/**
 * public/index.php
 * Punto de entrada principal.
 * Redirige al login o al dashboard dependiendo de la sesión.
 */

require_once __DIR__ . '/../app/auth_check.php';

// Si auth_check no redirigió, significa que hay sesión válida.
// Redirigimos al dashboard.
header("Location: /assistpro_kardex_fc/public/dashboard/index.php");
exit;
