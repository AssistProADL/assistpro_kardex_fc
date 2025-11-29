<?php
// /public/api/ecommerce_articulos.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$q = trim($_GET['q'] ?? $_POST['q'] ?? '');
$categ = trim($_GET['categoria'] ?? $_POST['categoria'] ?? '');
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// TODO: Seguridad por token si deseas
// $token = $_GET['token'] ?? '';
// validar_token($token) ...

try {
    if ($action === 'list') {
        $where = ["1=1"];
        $params = [];

        if ($q !== '') {
            $where[] = "(cve_articulo LIKE :q OR des_articulo LIKE :q OR ecommerce_tags LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if ($categ !== '') {
            $where[] = "ecommerce_categoria = :cat";
            $params[':cat'] = $categ;
        }

        $sql = "
            SELECT 
                id, cve_articulo, des_articulo, PrecioVenta,
                ecommerce_categoria, ecommerce_subcategoria,
                ecommerce_img_principal, ecommerce_img_galeria,
                ecommerce_tags, ecommerce_destacado
            FROM v_ecommerce_articulos
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ecommerce_destacado DESC, des_articulo ASC
            LIMIT 250;
        ";

        $rows = db_all($sql, $params);
        echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'detail') {
        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'Falta id'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $sql = "
            SELECT 
                id, cve_articulo, des_articulo, PrecioVenta,
                ecommerce_categoria, ecommerce_subcategoria,
                ecommerce_img_principal, ecommerce_img_galeria,
                ecommerce_tags, ecommerce_destacado
            FROM v_ecommerce_articulos
            WHERE id = :id
            LIMIT 1;
        ";
        $row = db_one($sql, [':id' => $id]);
        echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no soportada'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
