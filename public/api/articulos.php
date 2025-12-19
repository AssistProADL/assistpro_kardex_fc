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

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? 'list';
$inactivos = intval($_GET['inactivos'] ?? 0);

try {

    switch ($action) {

        /* ================= LISTAR ================= */
        case 'list':

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    cve_ssgpoart,
                    cve_sgpoart,
                    des_ssgpoart,
                    id_almacen,
                    Activo
                FROM c_ssgpoarticulo
                WHERE Activo = ?
                ORDER BY des_ssgpoart
            ");
            $stmt->execute([$inactivos ? 0 : 1]);

            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
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
            header('Content-Disposition: attachment; filename=tipo_articulos.csv');
            echo "\xEF\xBB\xBF"; // BOM UTF-8

            $out = fopen('php://output', 'w');

            fputcsv($out, ['Clave','Grupo','Descripción','Almacén'], ';');

            $rows = $pdo->query("
                SELECT cve_ssgpoart, cve_sgpoart, des_ssgpoart, id_almacen
                FROM c_ssgpoarticulo
                WHERE Activo = 1
                ORDER BY des_ssgpoart
            ");

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['cve_ssgpoart'],
                    $r['cve_sgpoart'],
                    $r['des_ssgpoart'],
                    $r['id_almacen']
                ], ';');
            }

            fclose($out);
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
