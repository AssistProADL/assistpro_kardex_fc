<?php
require_once '../../../app/db.php';
header('Content-Type: application/json');

/* ===== DESACTIVAR WARNINGS EN RESPUESTA ===== */
ini_set('display_errors', 0);
error_reporting(0);

$action = $_GET['action'] ?? '';

/* ===== LISTAR PROMOCIONES ===== */
if ($action === 'list') {
    $rows = db_all("SELECT id, Lista, Descripcion, FechaI, FechaF, Tipo, Activa FROM ListaPromo ORDER BY id DESC");
    echo json_encode(['ok' => 1, 'data' => $rows]);
    exit;
}

/* ===== OBTENER UNA PROMOCIÓN ===== */
if ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    $row = db_row("SELECT * FROM ListaPromo WHERE id = ?", [$id]);
    if ($row) {
        echo json_encode(['ok' => 1, 'data' => $row]);
    } else {
        echo json_encode(['ok' => 0, 'msg' => 'No encontrada']);
    }
    exit;
}

/* ===== CREAR PROMOCIÓN ===== */
if ($action === 'create') {

    $raw = file_get_contents('php://input');
    $d = $raw ? json_decode($raw, true) : null;

    if (!$d) {
        echo json_encode(['ok' => 0, 'msg' => 'JSON inválido']);
        exit;
    }

    if (
        empty($d['nombre']) ||
        empty($d['fecha_ini']) ||
        empty($d['fecha_fin'])
    ) {
        echo json_encode(['ok' => 0, 'msg' => 'Datos obligatorios faltantes']);
        exit;
    }

    try {

        dbq(
            "INSERT INTO ListaPromo
       (Lista, Descripcion, FechaI, FechaF, Tipo, Activa)
       VALUES (?,?,?,?,?,1)",
            [
                $d['nombre'],
                $d['descripcion'] ?? '',
                $d['fecha_ini'],
                $d['fecha_fin'],
                $d['tipo'] ?? 'MONTO'
            ]
        );

        echo json_encode(['ok' => 1]);

    } catch (Exception $e) {

        echo json_encode([
            'ok' => 0,
            'msg' => 'Error BD',
            'error' => $e->getMessage()
        ]);
    }

    exit;
}

/* ===== DEFAULT ===== */
echo json_encode(['ok' => 0, 'msg' => 'Acción inválida']);
