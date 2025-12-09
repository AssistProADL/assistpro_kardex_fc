<?php
// public/api/ecommerce_articulos.php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_GET['action'] ?? 'list';

    /* =========================================================
       RESOLVER LISTA DE PRECIOS
       - Si viene id de cliente y tiene lista en relclilis => la usamos
       - Si NO tiene lista => usamos la general 'e-commerce'
       ========================================================= */
    $resolverListaNombre = function (?int $clienteId): string {
        // 1) Intentar lista asignada al cliente (relclilis)
        if ($clienteId && $clienteId > 0) {
            $sqlLista = "
                SELECT lp.Lista
                FROM relclilis r
                JOIN listap lp ON lp.id = r.ListaP
                WHERE r.Id_Destinatario = :cliente
                ORDER BY lp.FechaIni DESC
                LIMIT 1
            ";
            $listaCliente = db_val($sqlLista, ['cliente' => $clienteId]);
            if ($listaCliente) {
                return $listaCliente;
            }
        }

        // 2) Lista general para visitantes / clientes sin lista
        return 'e-commerce';
    };

    if ($action === 'list') {
        $q         = trim($_GET['q'] ?? '');
        $categoria = trim($_GET['categoria'] ?? '');
        $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

        $listaNombre = $resolverListaNombre($clienteId);

        // -----------------------------------------------------
        // Productos e-commerce + precio de la lista seleccionada
        // -----------------------------------------------------
        $sql = "
            SELECT 
                a.id_articulo               AS id,
                a.cve_articulo,
                a.descripcion               AS des_articulo,
                IFNULL(p.PrecioMin, 0)      AS PrecioVenta,
                a.ecommerce_categoria,
                a.ecommerce_subcategoria,
                a.ecommerce_img_principal,
                a.ecommerce_img_galeria,
                a.ecommerce_tags,
                a.ecommerce_destacado
            FROM c_articulo a
            LEFT JOIN (
                SELECT 
                    dp.Cve_Articulo,
                    dp.PrecioMin
                FROM listap lp
                JOIN detallelp dp ON dp.ListaId = lp.id
                WHERE lp.Lista    = :listaNombre
                  AND lp.Tipo     = 'N'   -- Normal
                  AND lp.TipoServ = 'P'   -- Productos
            ) AS p
              ON p.Cve_Articulo = a.cve_articulo
            WHERE IFNULL(a.ecommerce_activo,0) = 1
        ";

        $params = [
            'listaNombre' => $listaNombre,
        ];

        if ($q !== '') {
            $sql .= " 
                AND (
                    a.cve_articulo   LIKE :q
                    OR a.descripcion LIKE :q
                    OR a.sku_cliente LIKE :q
                )
            ";
            $params['q'] = '%' . $q . '%';
        }

        if ($categoria !== '' && $categoria !== 'TODAS') {
            $sql .= " AND a.ecommerce_categoria = :categoria ";
            $params['categoria'] = $categoria;
        }

        $sql .= "
            ORDER BY 
                IFNULL(a.ecommerce_destacado,0) DESC,
                a.descripcion
        ";

        $rows = db_all($sql, $params);

        echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'detail') {
        $id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'Falta id'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $listaNombre = $resolverListaNombre($clienteId);

        $sql = "
            SELECT 
                a.id_articulo               AS id,
                a.cve_articulo,
                a.descripcion               AS des_articulo,
                IFNULL(p.PrecioMin, 0)      AS PrecioVenta,
                a.ecommerce_categoria,
                a.ecommerce_subcategoria,
                a.ecommerce_img_principal,
                a.ecommerce_img_galeria,
                a.ecommerce_tags,
                a.ecommerce_destacado
            FROM c_articulo a
            LEFT JOIN (
                SELECT 
                    dp.Cve_Articulo,
                    dp.PrecioMin
                FROM listap lp
                JOIN detallelp dp ON dp.ListaId = lp.id
                WHERE lp.Lista    = :listaNombre
                  AND lp.Tipo     = 'N'
                  AND lp.TipoServ = 'P'
            ) AS p
              ON p.Cve_Articulo = a.cve_articulo
            WHERE a.id_articulo = :id
            LIMIT 1
        ";

        $row = db_one($sql, [
            'id'          => $id,
            'listaNombre' => $listaNombre,
        ]);

        echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no soportada'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
