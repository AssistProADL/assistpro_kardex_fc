<?php
// public/api/dispositivos.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error de conexión PDO: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            api_list($pdo);
            break;
        case 'get':
            api_get($pdo);
            break;
        case 'save':
            api_save($pdo);
            break;
        case 'delete':
            api_delete($pdo);
            break;
        default:
            echo json_encode([
                'ok' => false,
                'error' => 'Acción no soportada en dispositivos.php: ' . $action
            ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error general en dispositivos.php: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function api_list(PDO $pdo)
{
    $id_almacen = $_GET['id_almacen'] ?? null;
    $estatus = $_GET['estatus'] ?? null;

    $sql = "SELECT * FROM v_dispositivos WHERE 1=1";
    $params = [];

    if ($id_almacen !== null && $id_almacen !== '') {
        $sql .= " AND id_almacen = :id_almacen";
        $params[':id_almacen'] = (int) $id_almacen;
    }

    if ($estatus !== null && $estatus !== '') {
        $sql .= " AND estatus = :estatus";
        $params[':estatus'] = $estatus;
    }

    $sql .= " ORDER BY almacen_clave, tipo, marca, modelo";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
}

function api_get(PDO $pdo)
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM s_dispositivos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Dispositivo no encontrado'], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
}

function api_save(PDO $pdo)
{
    $data = $_POST;

    $id = isset($data['id']) ? (int) $data['id'] : 0;
    $id_almacen = (int) ($data['id_almacen'] ?? 0);
    $tipo = $data['tipo'] ?? 'HANDHELD';
    $marca = trim($data['marca'] ?? '');
    $modelo = trim($data['modelo'] ?? '');
    $serie = trim($data['serie'] ?? '');
    $imei = trim($data['imei'] ?? '');
    $mac_wifi = trim($data['mac_wifi'] ?? '');
    $mac_bt = trim($data['mac_bt'] ?? '');
    $ip = trim($data['ip'] ?? '');
    $usuario_asignado = trim($data['usuario_asignado'] ?? '');
    $estatus = $data['estatus'] ?? 'ACTIVO';
    $comentarios = trim($data['comentarios'] ?? '');

    if ($id_almacen <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Almacén requerido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($id > 0) {
        $sql = "UPDATE s_dispositivos
                   SET id_almacen = :id_almacen,
                       tipo = :tipo,
                       marca = :marca,
                       modelo = :modelo,
                       serie = :serie,
                       imei = :imei,
                       mac_wifi = :mac_wifi,
                       mac_bt = :mac_bt,
                       ip = :ip,
                       usuario_asignado = :usuario_asignado,
                       estatus = :estatus,
                       comentarios = :comentarios
                 WHERE id = :id";
    } else {
        $sql = "INSERT INTO s_dispositivos
                (id_almacen, tipo, marca, modelo, serie, imei,
                 mac_wifi, mac_bt, ip, usuario_asignado, estatus, comentarios)
                VALUES
                (:id_almacen, :tipo, :marca, :modelo, :serie, :imei,
                 :mac_wifi, :mac_bt, :ip, :usuario_asignado, :estatus, :comentarios)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_almacen' => $id_almacen,
        ':tipo' => $tipo,
        ':marca' => $marca,
        ':modelo' => $modelo,
        ':serie' => $serie,
        ':imei' => $imei,
        ':mac_wifi' => $mac_wifi,
        ':mac_bt' => $mac_bt,
        ':ip' => $ip,
        ':usuario_asignado' => $usuario_asignado,
        ':estatus' => $estatus,
        ':comentarios' => $comentarios,
        ':id' => $id
    ]);

    if ($id === 0) {
        $id = (int) $pdo->lastInsertId();
    }

    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
}

function api_delete(PDO $pdo)
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmt = $pdo->prepare("UPDATE s_dispositivos SET estatus = 'BAJA' WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
}
