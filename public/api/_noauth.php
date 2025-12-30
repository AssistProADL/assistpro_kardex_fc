<?php
// public/api/_noauth.php
declare(strict_types=1);
define('ASSISTPRO_API', true);

// Forzar respuesta JSON si hay errores fatales
ini_set('display_errors', '0');
error_reporting(E_ALL);
