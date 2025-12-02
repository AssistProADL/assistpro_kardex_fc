<?php

/** @var \Illuminate\Routing\Router $router */

$router->get('/test', function () {
    return ['message' => 'Hello from Web Routes!', 'status' => 'success'];
});

$router->get('/test-blade', function () {
    return view('test', ['name' => 'AssistPro User']);
});

$router->get('/test-layout', function () {
    return view('test_layout');
});

$router->prefix('procesos')->group(function () use ($router) {
    $router->get('/ajuste_existencias', function () {
        return view('procesos.ajuste_existencias');
    });
});