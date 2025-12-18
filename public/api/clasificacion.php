<?php
/****************************************************
 * API Catálogo Clasificación de Artículos
 * Tabla: c_sgpoarticulo
 * Estándar AssistPro
 ****************************************************/

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a BD',
        'detalle' => $e->getMessage()
    ]);
    exit;
}

/* ================== INPUT UNIFICADO ================== */
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? 'list');

/* ================== PAGINACIÓN ================== */
$start  = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 25);
$draw   = intval($_GET['draw'] ?? 1);
$search = trim($_GET['search']['value'] ?? '');
$inactivos = intval($_GET['inactivos'] ?? 0);

try {

    switch ($action) {

        /* ===================== LISTAR ===================== */
        case 'list':

            header('Content-Type: application/json');

            $where = 'WHERE Activo = ?';
            $params = [$inactivos ? 0 : 1];

            if ($search !== '') {
                $where .= " AND (cve_sgpoart LIKE ? OR des_sgpoart LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $totalStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM c_sgpoarticulo WHERE Activo = ?"
            );
            $totalStmt->execute([$inactivos ? 0 : 1]);
            $recordsTotal = $totalStmt->fetchColumn();

            $countStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM c_sgpoarticulo $where"
            );
            $countStmt->execute($params);
            $recordsFiltered = $countStmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    cve_sgpoart,
                    cve_gpoart,
                    des_sgpoart,
                    id_almacen,
                    Num_Multiplo,
                    Ban_Incluye,
                    Activo
                FROM c_sgpoarticulo
                $where
                ORDER BY des_sgpoart
                LIMIT $start, $length
            ");
            $stmt->execute($params);

            echo json_encode([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        /* ===================== CREAR ===================== */
        case 'create':

            $stmt = $pdo->prepare("
                INSERT INTO c_sgpoarticulo
                (
                    cve_sgpoart,
                    cve_gpoart,
                    des_sgpoart,
                    id_almacen,
                    Num_Multiplo,
                    Ban_Incluye,
                    Activo
                )
                VALUES (?,?,?,?,?,?,1)
                ON DUPLICATE KEY UPDATE
                    cve_gpoart   = VALUES(cve_gpoart),
                    des_sgpoart  = VALUES(des_sgpoart),
                    id_almacen   = VALUES(id_almacen),
                    Num_Multiplo = VALUES(Num_Multiplo),
                    Ban_Incluye  = VALUES(Ban_Incluye)
            ");

            $stmt->execute([
                $input['cve_sgpoart'],
                $input['cve_gpoart'] ?? null,
                $input['des_sgpoart'],
                $input['id_almacen'] ?? null,
                $input['Num_Multiplo'] ?? 0,
                $input['Ban_Incluye'] ?? 0
            ]);

            echo json_encode(['success' => true]);
            break;

        /* ===================== ACTUALIZAR ===================== */
        case 'update':

            if (empty($input['id'])) {
                throw new Exception('ID requerido');
            }

            $stmt = $pdo->prepare("
                UPDATE c_sgpoarticulo SET
                    cve_sgpoart  = ?,
                    cve_gpoart   = ?,
                    des_sgpoart  = ?,
                    id_almacen   = ?,
                    Num_Multiplo = ?,
                    Ban_Incluye  = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $input['cve_sgpoart'],
                $input['cve_gpoart'] ?? null,
                $input['des_sgpoart'],
                $input['id_almacen'] ?? null,
                $input['Num_Multiplo'] ?? 0,
                $input['Ban_Incluye'] ?? 0,
                $input['id']
            ]);

            echo json_encode(['success' => true]);
            break;

        /* ===================== DESACTIVAR ===================== */
        case 'delete':

            $pdo->prepare(
                "UPDATE c_sgpoarticulo SET Activo = 0 WHERE id = ?"
            )->execute([$input['id']]);

            echo json_encode(['success' => true]);
            break;

        /* ===================== RESTAURAR ===================== */
        case 'restore':

            $pdo->prepare(
                "UPDATE c_sgpoarticulo SET Activo = 1 WHERE id = ?"
            )->execute([$input['id']]);

            echo json_encode(['success' => true]);
            break;

        /* ===================== EXPORT / LAYOUT ===================== */
        case 'export':

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=clasificacion_articulos_layout.csv');

            $out = fopen('php://output', 'w');

            /* HEADER AMIGABLE (VISIBLE AL USUARIO) */
            fputcsv($out, [
                'Clave Clasificación',
                'Grupo de Artículo',
                'Descripción',
                'Almacén',
                'Múltiplo',
                'Incluye'
            ]);

            /* DATOS (MISMO ORDEN QUE EL HEADER) */
            $rows = $pdo->query("
                SELECT
                    cve_sgpoart,
                    cve_gpoart,
                    des_sgpoart,
                    id_almacen,
                    Num_Multiplo,
                    Ban_Incluye
                FROM c_sgpoarticulo
                WHERE Activo = 1
                ORDER BY des_sgpoart
            ");

            foreach ($rows as $r) {
                fputcsv($out, $r);
            }

            fclose($out);
            exit;

        default:
            echo json_encode([
                'success' => false,
                'error'   => 'Acción no válida',
                'action'  => $action
            ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
