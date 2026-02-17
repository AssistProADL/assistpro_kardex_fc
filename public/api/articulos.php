<?php

/****************************************************
 * API Tipo de Artículos
 * Tabla: c_ssgpoarticulo
 * Estándar AssistPro
 ****************************************************/

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = db_pdo();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión',
        'detail' => $e->getMessage()
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? 'list';
$inactivos = intval($_GET['inactivos'] ?? 0);

try {

    switch ($action) {

        /* ================= LISTAR ================= */
        case 'list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 25));
            $offset = ($page - 1) * $limit;
            $q = $_GET['q'] ?? '';

            $where = ["Activo = ?"];
            $params = [$inactivos ? 0 : 1];

            if ($q !== '') {
                $where[] = "(cve_ssgpoart LIKE ? OR des_ssgpoart LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }

            $wStr = implode(" AND ", $where);

            // Count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM c_ssgpoarticulo WHERE $wStr");
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            $pages = ceil($total / $limit);

            // Data
            $sql = "SELECT id, cve_ssgpoart, cve_sgpoart, des_ssgpoart, id_almacen, Activo 
                    FROM c_ssgpoarticulo 
                    WHERE $wStr 
                    ORDER BY des_ssgpoart 
                    LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Check if there are any inactive records in total (to show/hide the toggle button)
            $hasInactives = $pdo->query("SELECT COUNT(*) FROM c_ssgpoarticulo WHERE Activo = 0")->fetchColumn() > 0;

            echo json_encode([
                'success' => true,
                'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'pages' => $pages,
                'page' => $page,
                'hasInactives' => $hasInactives
            ]);
            break;

        /* ================= CREAR ================= */
        case 'create':

            $stmt = $pdo->prepare("
                INSERT INTO c_ssgpoarticulo
                (cve_ssgpoart, cve_sgpoart, des_ssgpoart, id_almacen, Activo)
                VALUES (?,?,?,?,1)
            ");

            $stmt->execute([
                $input['cve_ssgpoart'],
                is_numeric($input['cve_sgpoart']) ? $input['cve_sgpoart'] : null,
                $input['des_ssgpoart'],
                is_numeric($input['id_almacen']) ? $input['id_almacen'] : null
            ]);

            echo json_encode(['success' => true]);
            break;

        /* ================= ACTUALIZAR ================= */
        case 'update':

            $stmt = $pdo->prepare("
                UPDATE c_ssgpoarticulo SET
                    cve_ssgpoart = ?,
                    cve_sgpoart  = ?,
                    des_ssgpoart = ?,
                    id_almacen  = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $input['cve_ssgpoart'],
                is_numeric($input['cve_sgpoart']) ? $input['cve_sgpoart'] : null,
                $input['des_ssgpoart'],
                is_numeric($input['id_almacen']) ? $input['id_almacen'] : null,
                $input['id']
            ]);

            echo json_encode(['success' => true]);
            break;

        /* ================= DESACTIVAR ================= */
        case 'delete':

            $pdo->prepare("
                UPDATE c_ssgpoarticulo SET Activo = 0 WHERE id = ?
            ")->execute([$input['id']]);

            echo json_encode(['success' => true]);
            break;

        /* ================= RESTAURAR ================= */
        case 'restore':

            $pdo->prepare("
                UPDATE c_ssgpoarticulo SET Activo = 1 WHERE id = ?
            ")->execute([$input['id']]);

            echo json_encode(['success' => true]);
            break;

        /* ================= EXPORTAR ================= */
        case 'export':

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=tipos_articulos.csv');
            echo "\xEF\xBB\xBF"; // BOM UTF-8

            $out = fopen('php://output', 'w');

            // Human Readable Headers
            fputcsv($out, ['Clave', 'Descripción', 'Grupo', 'Almacén'], ',');

            $rows = $pdo->query("
                SELECT cve_ssgpoart, des_ssgpoart, cve_sgpoart, id_almacen
                FROM c_ssgpoarticulo
                WHERE Activo = 1
                ORDER BY des_ssgpoart
            ");

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['cve_ssgpoart'],
                    $r['des_ssgpoart'],
                    $r['cve_sgpoart'],
                    $r['id_almacen']
                ], ',');
            }

            fclose($out);
            exit;

        case 'autocomplete':

            header('Content-Type: application/json; charset=utf-8');

            $q = trim($_GET['q'] ?? '');

            if ($q === '' || strlen($q) < 3) {
                echo json_encode([]);
                exit;
            }

            $sql = "
        SELECT cve_articulo, des_articulo
        FROM c_articulo
        WHERE IFNULL(Activo,1)=1
          AND (
                cve_articulo LIKE ?
             OR des_articulo LIKE ?
          )
        ORDER BY des_articulo
        LIMIT 25
    ";

            $st = $pdo->prepare($sql);
            $like = "%$q%";
            $st->execute([$like, $like]);

            echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);
            exit;


        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
