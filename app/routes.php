<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;

/** @var \Illuminate\Routing\Router $router */

// Load Web Routes
$router->group([], function ($router) {
    require __DIR__ . '/routes/web.php';
});

// Load API Routes
$router->group(['prefix' => 'api'], function ($router) {
    require __DIR__ . '/routes/api.php';
});
