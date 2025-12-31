<?php

require_once '../../../app/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'list':
    case 'create':
    case 'update':
        require 'promociones.php';
        break;

    // ===== REGLAS =====
    case 'reglas_list':
    case 'reglas_add':
    case 'reglas_delete':
        require 'reglas.php';
        break;

    case 'scope_list':
    case 'scope_add':
    case 'scope_delete':
        require 'scope.php';
        break;

    case 'simulate':
        require 'simulate.php';
        break;

    // ===== BENEFICIOS =====
    case 'rewards_list':
    case 'rewards_add':
    case 'rewards_delete':
        require 'rewards.php';
        break;

    default:
        echo json_encode(['ok' => 0, 'msg' => 'Acción no válida']);
}
