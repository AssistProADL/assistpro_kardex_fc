<?php
// public/api/dispositivos.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

if (function_exists('db')) {
    $pdo = db();
} else {
    global $pdo;
    if (!$pdo instanceof PDO) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Sin conexión a BD']);
        exit;
    }
}

$action = $_REQUEST['action'] ?? 'list';

try {
    switch ($action) {
        case 'get':
            api_get($pdo);
            break;
        case 'save':
            api_save($pdo);
            break;
        case 'change_status':
            api_change_status($pdo);
            break;
        case 'list':
        default:
            api_list($pdo);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * LISTAR DISPOSITIVOS
 */
function api_list(PDO $pdo)
{
    $id_almacen_raw = $_GET['id_almacen'] ?? '';
    $estatus        = $_GET['estatus'] ?? '';

    $sql = "SELECT 
                d.id,
                d.id_almacen,
                a.clave      AS almacen_clave,
                a.nombre     AS almacen_nombre,
                d.tipo,
                d.marca,
                d.modelo,
                d.serie,
                d.imei,
                d.firmware_version,
                d.mac_wifi,
                d.mac_bt,
                d.ip,
                d.usuario_asignado,
                d.estatus,
                d.fecha_alta,
                d.comentarios
            FROM s_dispositivos d
            JOIN c_almacenp a ON a.id = d.id_almacen
            WHERE 1=1";
    $params = [];

    // Filtro por almacén (ID o WHx)
    if ($id_almacen_raw !== '') {
        $id_almacen = (int)$id_almacen_raw;
        if ($id_almacen <= 0) {
            $stmtA = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = :clave LIMIT 1");
            $stmtA->execute([':clave' => $id_almacen_raw]);
            $rowA = $stmtA->fetch(PDO::FETCH_ASSOC);
            $id_almacen = $rowA ? (int)$rowA['id'] : 0;
        }
        if ($id_almacen > 0) {
            $sql .= " AND d.id_almacen = :id_almacen";
            $params[':id_almacen'] = $id_almacen;
        }
    }

    if ($estatus !== '') {
        $sql .= " AND d.estatus = :estatus";
        $params[':estatus'] = $estatus;
    }

    $sql .= " ORDER BY a.clave, d.tipo, d.marca, d.modelo";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
}

/**
 * OBTENER UN DISPOSITIVO
 */
function api_get(PDO $pdo)
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $sql = "SELECT 
                d.id,
                d.id_almacen,
                a.clave      AS almacen_clave,
                a.nombre     AS almacen_nombre,
                d.tipo,
                d.marca,
                d.modelo,
                d.serie,
                d.imei,
                d.firmware_version,
                d.mac_wifi,
                d.mac_bt,
                d.ip,
                d.usuario_asignado,
                d.estatus,
                d.fecha_alta,
                d.comentarios
            FROM s_dispositivos d
            JOIN c_almacenp a ON a.id = d.id_almacen
            WHERE d.id = :id
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Registro no encontrado'], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
}

/**
 * GUARDAR / ACTUALIZAR DISPOSITIVO
 */
function api_save(PDO $pdo)
{
    $data = $_POST;

    $id = isset($data['id']) ? (int)$data['id'] : 0;

    // id_almacen puede venir como ID numérico o clave WHx
    $id_almacen_raw = trim($data['id_almacen'] ?? '');
    $id_almacen     = (int)$id_almacen_raw;

    if ($id_almacen <= 0 && $id_almacen_raw !== '') {
        $stmtAlm = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = :clave LIMIT 1");
        $stmtAlm->execute([':clave' => $id_almacen_raw]);
        $rowAlm = $stmtAlm->fetch(PDO::FETCH_ASSOC);
        if ($rowAlm) {
            $id_almacen = (int)$rowAlm['id'];
        }
    }

    $tipo             = $data['tipo'] ?? 'HANDHELD';
    $marca            = trim($data['marca'] ?? '');
    $modelo           = trim($data['modelo'] ?? '');
    $serie            = trim($data['serie'] ?? '');
    $imei             = trim($data['imei'] ?? '');
    $firmware_version = trim($data['firmware_version'] ?? '');
    $mac_wifi         = trim($data['mac_wifi'] ?? '');
    $mac_bt           = trim($data['mac_bt'] ?? '');
    $ip               = trim($data['ip'] ?? '');
    $usuario_asignado = trim($data['usuario_asignado'] ?? '');
    $estatus          = $data['estatus'] ?? 'ACTIVO';
    $comentarios      = trim($data['comentarios'] ?? '');

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
