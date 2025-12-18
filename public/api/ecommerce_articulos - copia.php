<?php
// public/api/ecommerce_articulos.php
//
// API de catálogo E-Commerce
// - Devuelve artículos con precio según la lista del cliente (relclilis)
// - Si el cliente no tiene lista asignada o no hay sesión,
//   usa la lista general "e-commerce" de listap.
// - Si no hay listas, muestra productos con precio 0 sin fallar.

require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok($data = [], $extra = [])
{
    http_response_code(200);
    echo json_encode(array_merge(['ok' => true, 'data' => $data], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Aceptamos GET y POST para no depender de cómo llame el front
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    json_error('Método no permitido', 405);
}

$src = $method === 'GET' ? $_GET : $_POST;

$action     = $src['action']     ?? 'list';
$q          = trim($src['q']     ?? '');
$categoria  = trim($src['cat']   ?? ($src['categoria'] ?? ''));
$destacados = trim($src['dest']  ?? ($src['destacados'] ?? ''));

// ---------------------------------------------------------------------
// 1) Determinar cliente y lista de precios efectiva
// ---------------------------------------------------------------------

// Ajusta al índice que realmente uses para el portal de clientes
$clienteId = $_SESSION['cliente_id']
    ?? $_SESSION['id_cliente']
    ?? $_SESSION['Id_Destinatario']
    ?? null;

try {

    $listaId = null;

    // 1.1 Lista asignada al cliente en relclilis (si hay cliente)
    if ($clienteId) {
        $listaId = db_val("
            SELECT NULLIF(ListaP, 0) AS ListaP
            FROM relclilis
            WHERE Id_Destinatario = ?
            ORDER BY Id DESC
            LIMIT 1
        ", [$clienteId]);
    }

    // 1.2 Fallback: lista general "e-commerce"
    if (!$listaId) {
        $listaId = db_val("
            SELECT id
            FROM listap
            WHERE Lista = 'e-commerce'
            ORDER BY id ASC
            LIMIT 1
        ");
    }

    // 1.3 Último fallback: cualquier lista (para tener algo de precio)
    if (!$listaId) {
        $listaId = db_val("
            SELECT id
            FROM listap
            ORDER BY id ASC
            LIMIT 1
        ");
    }

    // Si aun así no hay lista, se mostrará el catálogo con precio 0
    // (simplemente no se hará join por lista).

    // -----------------------------------------------------------------
    // 2) Construir JOIN de precios en función de $listaId
    // -----------------------------------------------------------------

    $joinPrecio = '';
    $paramsBase = [];

    if ($listaId) {
        // Join filtrando por la lista efectiva
        $joinPrecio = "
            LEFT JOIN detallelp dp
                   ON dp.Cve_Articulo = a.cve_articulo
                  AND dp.ListaId      = ?
        ";
        $paramsBase[] = $listaId;
    } else {
        // Sin lista: join genérico (si hubiera algún precio cualquiera)
        $joinPrecio = "
            LEFT JOIN detallelp dp
                   ON dp.Cve_Articulo = a.cve_articulo
        ";
    }

    // -----------------------------------------------------------------
    // 3) Acciones
    // -----------------------------------------------------------------

    if ($action === 'list') {

        $where  = [];
        $params = $paramsBase;

        // Solo artículos activos para e-commerce (si el flag existe)
        $where[] = "IFNULL(a.ecommerce_activo, 1) = 1";

        if ($q !== '') {
            $where[]  = "(a.cve_articulo LIKE ? OR a.des_articulo LIKE ?)";
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        // Categoría: consideramos vacío, 0, ALL, TODAS como "todas"
        $catUpper = mb_strtoupper($categoria, 'UTF-8');
        if ($categoria !== '' && $categoria !== '0' && !in_array($catUpper, ['ALL', 'TODAS', 'TODAS LAS CATEGORIAS'], true)) {
            $where[]  = "a.ecommerce_categoria = ?";
            $params[] = $categoria;
        }

        // Solo destacados si se pide
        if ($destacados !== '' && $destacados !== '0') {
            $where[] = "IFNULL(a.ecommerce_destacado,0) = 1";
        }

        $sql = "
            SELECT
                a.id,
                a.cve_articulo,
                a.des_articulo,
                IFNULL(dp.PrecioMin, 0)           AS PrecioVenta,
                a.ecommerce_categoria,
                a.ecommerce_subcategoria,
                a.ecommerce_img_principal,
                a.ecommerce_img_galeria,
                a.ecommerce_tags,
                a.ecommerce_destacado
            FROM c_articulo a
            $joinPrecio
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY a.des_articulo
            LIMIT 500
        ";

        $rows = db_all($sql, $params);
        json_ok($rows, ['listaId' => $listaId]);
    }

    if ($action === 'detail') {

        $id = isset($src['id']) ? (int)$src['id'] : 0;
        if ($id <= 0) {
            json_error('ID de artículo inválido.');
        }

        $where  = ["a.id = ?"];
        $params = array_merge($paramsBase, [$id]);

        $sql = "
            SELECT
                a.id,
                a.cve_articulo,
                a.des_articulo,
                IFNULL(dp.PrecioMin, 0)           AS PrecioVenta,
                a.ecommerce_categoria,
                a.ecommerce_subcategoria,
                a.ecommerce_img_principal,
                a.ecommerce_img_galeria,
                a.ecommerce_tags,
                a.ecommerce_destacado
            FROM c_articulo a
            $joinPrecio
            WHERE " . implode(' AND ', $where) . "
            LIMIT 1
        ";

        $row = db_one($sql, $params);
        if (!$row) {
            json_error('Artículo no encontrado.', 404);
        }

        json_ok($row, ['listaId' => $listaId]);
    }

    json_error('Acción inválida.', 400);

} catch (Throwable $e) {
    json_error($e->getMessage(), 500);
}
