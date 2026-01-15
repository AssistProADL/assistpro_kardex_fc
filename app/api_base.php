<?php
// app/api_base.php
declare(strict_types=1);

/**
 * API BASE CORPORATIVO (STATELESS)
 * - Sin session
 * - Respuestas JSON estándar
 * - Helpers de params
 * - Guard de método HTTP
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function api_method(string $expected): void {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== strtoupper($expected)) {
        throw new RuntimeException("Método no permitido ($method), se esperaba $expected");
    }
}

function api_int(array $src, string $key, int $default = 0): int {
    if (!isset($src[$key]) || $src[$key] === '') return $default;
    return (int)$src[$key];
}

function api_str(array $src, string $key, string $default = ''): string {
    if (!isset($src[$key]) || $src[$key] === null) return $default;
    return trim((string)$src[$key]);
}

function api_ok(array $payload = []): void {
    echo json_encode(array_merge(['ok' => true], $payload));
    exit;
}

function api_error(Throwable $e): void {
    http_response_code(200); // legacy-friendly
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ]);
    exit;
}

/**
 * Usuario stateless:
 * enviar header opcional: X-User: "Alejandro"
 */
function api_user(): string {
    $u = trim((string)($_SERVER['HTTP_X_USER'] ?? ''));
    return $u !== '' ? $u : 'SISTEMA';
}
