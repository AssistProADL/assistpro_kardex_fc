<?php

$uri = $_SERVER['REQUEST_URI'] ?? '';

// ======================================================
// 🟢 BYPASS TOTAL PARA API
// ======================================================
if (strpos($uri, '/api/') !== false) {
    // Ejecuta directamente el archivo solicitado
    $path = $_SERVER['DOCUMENT_ROOT'] . $uri;

    if (file_exists($path)) {
        require $path;
        exit;
    }

    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => 'Endpoint API no encontrado'
    ]);
    exit;
}

// ======================================================
// 🔒 UI NORMAL (CON SESIÓN)
// ======================================================
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: /assistpro_kardex_fc/public/login.php");
    exit;
}

// ======================================================
// UI routing normal
// ======================================================
require __DIR__ . '/index.php';
