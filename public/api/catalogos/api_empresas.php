<?php

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function response($ok, $msg = '', $data = []) {
    echo json_encode(array_merge([
        'ok'  => $ok,
        'msg' => $msg
    ], $data));
    exit;
}

/* ================= LIST ================= */

if ($action === 'list') {

    $stmt = $pdo->query("
        SELECT
            cve_cia AS id,
            clave,
            razon_social,
            tax_id,
            ciudad_std AS ciudad,
            estado_std AS estado,
            Activo
        FROM c_compania
        ORDER BY razon_social
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    response(1, 'OK', [
        'empresas' => $rows
    ]);
}

/* ================= GET ================= */

if ($action === 'get') {

    $id = $_GET['id'] ?? null;
    if (!$id) response(0, 'ID requerido');

    $stmt = $pdo->prepare("
        SELECT *
        FROM c_compania
        WHERE cve_cia = ?
    ");
    $stmt->execute([$id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) response(0, 'Empresa no encontrada');

    response(1, 'OK', [
        'empresa' => $row
    ]);
}

/* ================= CREATE ================= */

if ($action === 'create') {

    if (empty($_POST['clave']) || empty($_POST['razon_social'])) {
        response(0, 'Clave y razón social obligatorios');
    }

    $stmt = $pdo->prepare("
        INSERT INTO c_compania (
            clave,
            razon_social,
            tax_id,
            ciudad_std,
            estado_std,
            direccion_linea1,
            codigo_postal_std,
            Activo
        ) VALUES (?,?,?,?,?,?,?,1)
    ");

    $ok = $stmt->execute([
        $_POST['clave'],
        $_POST['razon_social'],
        $_POST['tax_id'] ?? null,
        $_POST['ciudad'] ?? null,
        $_POST['estado'] ?? null,
        $_POST['direccion'] ?? null,
        $_POST['codigo_postal'] ?? null
    ]);

    if (!$ok) response(0, 'No se pudo crear');

    response(1, 'Empresa creada', [
        'id' => $pdo->lastInsertId()
    ]);
}

/* ================= UPDATE ================= */

if ($action === 'update') {

    $id = $_POST['id'] ?? null;
    if (!$id) response(0, 'ID requerido');

    if (empty($_POST['clave']) || empty($_POST['razon_social'])) {
        response(0, 'Clave y razón social obligatorios');
    }

    $stmt = $pdo->prepare("
        UPDATE c_compania SET
            clave = ?,
            razon_social = ?,
            tax_id = ?,
            ciudad_std = ?,
            estado_std = ?,
            direccion_linea1 = ?,
            codigo_postal_std = ?
        WHERE cve_cia = ?
    ");

    $ok = $stmt->execute([
        $_POST['clave'],
        $_POST['razon_social'],
        $_POST['tax_id'] ?? null,
        $_POST['ciudad'] ?? null,
        $_POST['estado'] ?? null,
        $_POST['direccion'] ?? null,
        $_POST['codigo_postal'] ?? null,
        $id
    ]);

    if (!$ok) response(0, 'No se pudo actualizar');

    response(1, 'Empresa actualizada');
}

/* ================= DELETE (SOFT) ================= */

if ($action === 'delete') {

    $id = $_POST['id'] ?? null;
    if (!$id) response(0, 'ID requerido');

    $stmt = $pdo->prepare("
        UPDATE c_compania
        SET Activo = 0
        WHERE cve_cia = ?
    ");

    $ok = $stmt->execute([$id]);

    if (!$ok) response(0, 'No se pudo desactivar');

    response(1, 'Empresa desactivada');
}

response(0, 'Acción no válida');
