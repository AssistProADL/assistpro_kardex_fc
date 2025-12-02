<?php

use Illuminate\Support\Facades\Route;
use AssistPro\Http\Controllers\AlmacenController;
use AssistPro\Http\Controllers\AjustesExistenciaController;
use AssistPro\Http\Controllers\ReportesController;

use AssistPro\Http\Controllers\AuthController;

Route::get('/test', function () {
    return \AssistPro\Helpers\ApiResponse::success(['message' => 'API working']);
});

// Auth
Route::post('login', [AuthController::class, 'login']);

// Almacenes y Zonas
Route::prefix('almacen')->group(function () {
    Route::get('predeterminado', [AlmacenController::class, 'getPredeterminado']);
    Route::get('zonas', [AlmacenController::class, 'getZonas']);
    Route::get('padres', [AlmacenController::class, 'getPadres']);
});

// Ajustes de Existencia
Route::prefix('ajustes/existencias')->group(function () {
    Route::get('/', [AjustesExistenciaController::class, 'index']);
    Route::get('kpis', [AjustesExistenciaController::class, 'kpis']);
    Route::get('detalles', [AjustesExistenciaController::class, 'show']);
    Route::post('/', [AjustesExistenciaController::class, 'update']);
});

// Reportes
Route::prefix('reportes')->group(function () {
    Route::get('existencia-ubica', [ReportesController::class, 'existenciaUbica']);
});
