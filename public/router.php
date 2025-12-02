<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;

// Bootstrap and get container
$container = require_once __DIR__ . '/../app/bootstrap.php';

// Iniciar sesión unificada (compatible con legacy y nuevo sistema)
// - Timeout de 15 minutos de inactividad
// - Una sola sesión por usuario
// - Compartida entre ambos sistemas
\AssistPro\Helpers\SessionManager::init();

// Create Request
$request = Request::capture();

// Bind the captured request to the container
$container->instance('request', $request);
$container->instance('Illuminate\Http\Request', $request);

// Create Router
$events = $container->make('Illuminate\Contracts\Events\Dispatcher');
$router = new Router($events, $container);

// Bind router to container for Facades
$container->instance('router', $router);
$container->alias('router', 'Illuminate\Routing\Router');

// Enable Facades
\Illuminate\Support\Facades\Facade::setFacadeApplication($container);

// Load Routes
require_once __DIR__ . '/../app/routes.php';

// Dispatch Request
try {
    $response = $router->dispatch($request);
    $response->send();
} catch (\Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Error: " . $e->getMessage();
}
