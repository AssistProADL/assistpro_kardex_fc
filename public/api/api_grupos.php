<?php
/**
 * API Catálogo de Grupos de Productos
 * Tabla: c_gpoarticulo
 * Estándar AssistPro
 */

require_once __DIR__ . '/../../app/db.php';

// Inicializar conexión
try {
    $pdo = db_pdo();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

/* =====================================================
   1. CAPTURA UNIVERSAL DEL ACTION
   (POST / GET / JSON / DataTables)
===================================================== */

$action = '';

// POST tradicional
if (isset($_POST['action']) && $_POST['action'] !== '') {
    $action = trim($_POST['action']);
}

// GET
if ($action === '' && isset($_GET['action']) && $_GET['action'] !== '') {
    $action = trim($_GET['action']);
}

// JSON (fetch / axios / DataTables modernos)
if ($action === '') {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['action'])) {
            $action = trim($json['action']);
            $_POST = $json; // normalizamos el flujo
        }
    }
}

/* =====================================================
   2. AJUSTE CLAVE (DataTables / AssistPro)
   Si NO viene action → asumimos LIST
===================================================== */

if ($action === '') {
    $action = 'list';
}

/* =====================================================
   3. ROUTER DE ACCIONES
===================================================== */

try {

    switch ($action) {

        /* =========================================
           LISTADO (Paginacion + Search)
        ========================================= */
        case 'list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 25));
            $offset = ($page - 1) * $limit;
            $q = $_GET['q'] ?? '';
            $verInactivos = intval($_GET['inactivos'] ?? 0);

            $where = ["Activo = ?"];
            $params = [$verInactivos ? 0 : 1];

            if ($q !== '') {
                $where[] = "(cve_gpoart LIKE ? OR des_gpoart LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }

            $wStr = implode(" AND ", $where);

            // Count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM c_gpoarticulo WHERE $wStr");
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            $pages = ceil($total / $limit);

            // Data
            $sql = "SELECT id, cve_gpoart, des_gpoart, por_depcont, por_depfical, id_almacen, Activo 
                    FROM c_gpoarticulo 
                    WHERE $wStr 
                    ORDER BY des_gpoart 
                    LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Check if there are any inactive records in total
            $hasInactives = $pdo->query("SELECT COUNT(*) FROM c_gpoarticulo WHERE Activo = 0")->fetchColumn() > 0;

            echo json_encode([
                'success' => true,
                'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'pages' => $pages,
                'page' => $page,
                'hasInactives' => $hasInactives
            ]);
            break;

        /* =========================================
           GUARDAR (NUEVO / EDICIÓN)
        ========================================= */
        case 'save':

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $cve = $_POST['cve_gpoart'] ?? '';
            $des = $_POST['des_gpoart'] ?? '';
            $pdc = isset($_POST['por_depcont']) && $_POST['por_depcont'] !== '' ? $_POST['por_depcont'] : null;
            $pdf = isset($_POST['por_depfical']) && $_POST['por_depfical'] !== '' ? $_POST['por_depfical'] : null;
            $alm = isset($_POST['id_almacen']) && $_POST['id_almacen'] !== '' ? $_POST['id_almacen'] : null;

            if ($id === 0) {
                $sql = "INSERT INTO c_gpoarticulo (cve_gpoart, des_gpoart, por_depcont, por_depfical, Activo, id_almacen)
                        VALUES (?, ?, ?, ?, 1, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cve, $des, $pdc, $pdf, $alm]);
            } else {
                $sql = "UPDATE c_gpoarticulo SET cve_gpoart=?, des_gpoart=?, por_depcont=?, por_depfical=?, id_almacen=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cve, $des, $pdc, $pdf, $alm, $id]);
            }

            echo json_encode(['success' => true]);
            break;

        /* =========================================
           DELETE LÓGICO
        ========================================= */
        case 'delete':
            if (!isset($_POST['id']))
                throw new Exception('ID no recibido');
            $stmt = $pdo->prepare("UPDATE c_gpoarticulo SET Activo = 0 WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true]);
            break;

        /* =========================================
           RECUPERAR
        ========================================= */
        case 'restore':
            if (!isset($_POST['id']))
                throw new Exception('ID no recibido');
            $stmt = $pdo->prepare("UPDATE c_gpoarticulo SET Activo = 1 WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true]);
            break;

        /* =========================================
           EXPORTAR
        ========================================= */
        case 'export':
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=grupos_articulos.csv');
            echo "\xEF\xBB\xBF"; // BOM UTF-8

            $out = fopen('php://output', 'w');
            fputcsv($out, ['Clave', 'Descripción', '% Contable', '% Fiscal', 'Almacén'], ',');

            $stmt = $pdo->query("SELECT cve_gpoart, des_gpoart, por_depcont, por_depfical, id_almacen FROM c_gpoarticulo WHERE Activo = 1 ORDER BY des_gpoart");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($out, [
                    $r['cve_gpoart'],
                    $r['des_gpoart'],
                    $r['por_depcont'],
                    $r['por_depfical'],
                    $r['id_almacen']
                ], ',');
            }
            fclose($out);
            exit;

        /* =========================================
           DEFAULT
        ========================================= */
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida', 'action' => $action]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
