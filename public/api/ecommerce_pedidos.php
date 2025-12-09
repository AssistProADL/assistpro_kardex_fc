<?php
// /public/api/ecommerce_pedidos.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Solo se permite POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$items      = $data['items'] ?? [];
$cliente_id = isset($data['cliente_id']) ? (int)$data['cliente_id'] : 0;

// Si en algún momento manejas cliente por sesión:
if (!$cliente_id && isset($_SESSION['cliente_id'])) {
    $cliente_id = (int)$_SESSION['cliente_id'];
}

$usuario = $_SESSION['username'] ?? 'PORTAL';

if (!is_array($items) || !count($items)) {
    echo json_encode(['ok' => false, 'error' => 'Carrito vacío'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Normalizar items y calcular total
$total = 0.0;
foreach ($items as &$it) {
    $it['articulo_id']  = (int)($it['id'] ?? $it['articulo_id'] ?? 0);
    $it['cve_articulo'] = (string)($it['cve_articulo'] ?? '');
    $it['des_articulo'] = (string)($it['des_articulo'] ?? '');
    $it['cantidad']     = (float)($it['cantidad'] ?? 0);
    $it['precio']       = (float)($it['precio'] ?? 0);

    if ($it['cantidad'] <= 0) {
        $it['cantidad'] = 1;
    }

    $total += $it['cantidad'] * $it['precio'];
}
unset($it);

if ($total <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Total del pedido inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ENCABEZADO
    dbq(
        "INSERT INTO assistpro_etl_fc.t_pedido_web
           (fecha, cliente_id, usuario, total, estatus)
         VALUES
           (NOW(), :cliente_id, :usuario, :total, 'CAPTURADO')",
        [
            ':cliente_id' => $cliente_id ?: null,
            ':usuario'    => $usuario,
            ':total'      => $total
        ]
    );

    $row       = db_one("SELECT LAST_INSERT_ID() AS id", []);
    $pedido_id = (int)$row['id'];

    // DETALLE
    foreach ($items as $it) {
        dbq(
            "INSERT INTO assistpro_etl_fc.t_pedido_web_det
               (pedido_id, articulo_id, cve_articulo, des_articulo,
                cantidad, precio_unit, total_renglon)
             VALUES
               (:pedido_id, :articulo_id, :cve_articulo, :des_articulo,
                :cantidad, :precio_unit, :total_renglon)",
            [
                ':pedido_id'    => $pedido_id,
                ':articulo_id'  => $it['articulo_id'],
                ':cve_articulo' => $it['cve_articulo'],
                ':des_articulo' => $it['des_articulo'],
                ':cantidad'     => $it['cantidad'],
                ':precio_unit'  => $it['precio'],
                ':total_renglon'=> $it['cantidad'] * $it['precio'],
            ]
        );
    }

    echo json_encode(
        ['ok' => true, 'pedido_id' => $pedido_id, 'total' => $total],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {
    echo json_encode(
        ['ok' => false, 'error' => 'Error al guardar pedido: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
