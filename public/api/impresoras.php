<?php
// public/api/impresoras.php
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
        case 'test':
            api_test_print($pdo);
            break;
        default:
            echo json_encode([
                'ok' => false,
                'error' => 'Acción no soportada en impresoras.php: ' . $action
            ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error general en impresoras.php: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Lista impresoras (con filtros simples opcionales)
 */
function api_list(PDO $pdo)
{
    $id_almacen = $_GET['id_almacen'] ?? null;
    $activo = $_GET['activo'] ?? null;

    $sql = "SELECT * FROM v_impresoras WHERE 1=1";
    $params = [];

    if ($id_almacen !== null && $id_almacen !== '') {
        $sql .= " AND id_almacen = :id_almacen";
        $params[':id_almacen'] = (int) $id_almacen;
    }

    if ($activo !== null && $activo !== '') {
        $sql .= " AND Activo = :activo";
        $params[':activo'] = (int) $activo;
    }

    $sql .= " ORDER BY almacen_clave, NOMBRE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => $rows
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Obtiene una impresora por id
 */
function api_get(PDO $pdo)
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM s_impresoras WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Impresora no encontrada'], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
}

/**
 * Inserta / Actualiza impresora
 */
function api_save(PDO $pdo)
{
    $data = $_POST;

    $id = isset($data['id']) ? (int) $data['id'] : 0;
    $id_almacen = (int) ($data['id_almacen'] ?? 0);
    $IP = trim($data['IP'] ?? '');
    $TIPO_IMPRESORA = $data['TIPO_IMPRESORA'] ?? 'ZPL';
    $NOMBRE = trim($data['NOMBRE'] ?? '');
    $Marca = trim($data['Marca'] ?? '');
    $Modelo = trim($data['Modelo'] ?? '');
    $Densidad_Imp = (int) ($data['Densidad_Imp'] ?? 203);
    $TIPO_CONEXION = $data['TIPO_CONEXION'] ?? 'USB';
    $PUERTO = (int) ($data['PUERTO'] ?? 0);
    $TiempoEspera = (int) ($data['TiempoEspera'] ?? 0);
    $Activo = (int) ($data['Activo'] ?? 1);

    if ($id_almacen <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Almacén requerido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($id > 0) {
        // Update
        $sql = "UPDATE s_impresoras
                   SET id_almacen = :id_almacen,
                       IP = :IP,
                       TIPO_IMPRESORA = :TIPO_IMPRESORA,
                       NOMBRE = :NOMBRE,
                       Marca = :Marca,
                       Modelo = :Modelo,
                       Densidad_Imp = :Densidad_Imp,
                       TIPO_CONEXION = :TIPO_CONEXION,
                       PUERTO = :PUERTO,
                       TiempoEspera = :TiempoEspera,
                       Activo = :Activo
                 WHERE id = :id";
    } else {
        // Insert
        $sql = "INSERT INTO s_impresoras
                (id_almacen, IP, TIPO_IMPRESORA, NOMBRE, Marca, Modelo,
                 Densidad_Imp, TIPO_CONEXION, PUERTO, TiempoEspera, Activo)
                VALUES
                (:id_almacen, :IP, :TIPO_IMPRESORA, :NOMBRE, :Marca, :Modelo,
                 :Densidad_Imp, :TIPO_CONEXION, :PUERTO, :TiempoEspera, :Activo)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_almacen' => $id_almacen,
        ':IP' => $IP,
        ':TIPO_IMPRESORA' => $TIPO_IMPRESORA,
        ':NOMBRE' => $NOMBRE,
        ':Marca' => $Marca,
        ':Modelo' => $Modelo,
        ':Densidad_Imp' => $Densidad_Imp,
        ':TIPO_CONEXION' => $TIPO_CONEXION,
        ':PUERTO' => $PUERTO,
        ':TiempoEspera' => $TiempoEspera,
        ':Activo' => $Activo,
        ':id' => $id
    ]);

    if ($id === 0) {
        $id = (int) $pdo->lastInsertId();
    }

    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
}

/**
 * Baja lógica
 */
function api_delete(PDO $pdo)
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmt = $pdo->prepare("UPDATE s_impresoras SET Activo = 0 WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
}

/**
 * Probar impresión: envía una etiqueta de prueba ZPL a la impresora
 */
function api_test_print(PDO $pdo)
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM s_impresoras WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $imp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$imp) {
        echo json_encode(['ok' => false, 'error' => 'Impresora no encontrada'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $ip = trim($imp['IP'] ?? '');
    $puerto = (int) ($imp['PUERTO'] ?? 0);
    $tipo = $imp['TIPO_IMPRESORA'] ?? 'ZPL';
    $nombre = $imp['NOMBRE'] ?? '';
    $modelo = $imp['Modelo'] ?? '';

    if ($ip === '') {
        echo json_encode(['ok' => false, 'error' => 'La impresora no tiene IP configurada'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if ($puerto <= 0) {
        $puerto = 9100; // default Zebra
    }

    if ($tipo !== 'ZPL') {
        echo json_encode(['ok' => false, 'error' => 'Solo se soporta prueba ZPL por ahora (impresora configurada como ' . $tipo . ')'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Etiqueta ZPL de prueba
    $zpl = "^XA";
    $zpl .= "^CF0,40,40";
    $zpl .= "^FO50,50^FDTEST IMPRESORA ASSISTPRO^FS";
    $zpl .= "^CF0,30,30";
    $zpl .= "^FO50,110^FDNombre: " . substr($nombre, 0, 20) . "^FS";
    $zpl .= "^FO50,150^FDModelo: " . substr($modelo, 0, 20) . "^FS";
    $zpl .= "^FO50,200^FDFecha: " . date('d/m/Y H:i') . "^FS";
    $zpl .= "^XZ";

    $errno = 0;
    $errstr = '';
    $timeout = 5;

    $fp = @fsockopen($ip, $puerto, $errno, $errstr, $timeout);
    if (!$fp) {
        echo json_encode([
            'ok' => false,
            'error' => "No se pudo conectar a {$ip}:{$puerto} ({$errno} - {$errstr})"
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    stream_set_timeout($fp, $timeout);
    $bytes = fwrite($fp, $zpl);
    fclose($fp);

    if ($bytes === false || $bytes <= 0) {
        echo json_encode([
            'ok' => false,
            'error' => 'No se pudo enviar datos a la impresora'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Etiqueta de prueba enviada correctamente'
    ], JSON_UNESCAPED_UNICODE);
}
