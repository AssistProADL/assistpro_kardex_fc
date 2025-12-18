<?php
/**
 * API Catálogo de Grupos de Productos
 * Tabla: c_gpoarticulo
 * Estándar AssistPro
 */

require_once __DIR__ . '/../../app/db.php';

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
           LISTADO (DataTables)
        ========================================= */
        case 'list':

            $sql = "
                SELECT
                    id,
                    cve_gpoart,
                    des_gpoart,
                    por_depcont,
                    por_depfical,
                    Activo,
                    id_almacen
                FROM c_gpoarticulo
                ORDER BY des_gpoart
            ";

            echo json_encode(db_all($sql));
            break;

        /* =========================================
           GUARDAR (NUEVO / EDICIÓN)
        ========================================= */
        case 'save':

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id === 0) {

                $sql = "
                    INSERT INTO c_gpoarticulo
                        (cve_gpoart, des_gpoart, por_depcont, por_depfical, Activo, id_almacen)
                    VALUES
                        (:cve, :des, :pdc, :pdf, 1, :alm)
                ";

                db_exec($sql, [
                    'cve' => $_POST['cve_gpoart']   ?? null,
                    'des' => $_POST['des_gpoart']   ?? null,
                    'pdc' => $_POST['por_depcont']  ?? null,
                    'pdf' => $_POST['por_depfical'] ?? null,
                    'alm' => $_POST['id_almacen']   ?? null
                ]);

            } else {

                $sql = "
                    UPDATE c_gpoarticulo
                    SET
                        cve_gpoart   = :cve,
                        des_gpoart   = :des,
                        por_depcont  = :pdc,
                        por_depfical = :pdf,
                        id_almacen   = :alm
                    WHERE id = :id
                ";

                db_exec($sql, [
                    'cve' => $_POST['cve_gpoart']   ?? null,
                    'des' => $_POST['des_gpoart']   ?? null,
                    'pdc' => $_POST['por_depcont']  ?? null,
                    'pdf' => $_POST['por_depfical'] ?? null,
                    'alm' => $_POST['id_almacen']   ?? null,
                    'id'  => $id
                ]);
            }

            echo json_encode(['success' => true]);
            break;

        /* =========================================
           DELETE LÓGICO
        ========================================= */
        case 'delete':

            if (!isset($_POST['id'])) {
                throw new Exception('ID no recibido');
            }

            db_exec(
                "UPDATE c_gpoarticulo SET Activo = 0 WHERE id = :id",
                ['id' => $_POST['id']]
            );

            echo json_encode(['success' => true]);
            break;

        /* =========================================
           RECUPERAR
        ========================================= */
        case 'restore':

            if (!isset($_POST['id'])) {
                throw new Exception('ID no recibido');
            }

            db_exec(
                "UPDATE c_gpoarticulo SET Activo = 1 WHERE id = :id",
                ['id' => $_POST['id']]
            );

            echo json_encode(['success' => true]);
            break;

        /* =========================================
           SEGURIDAD (NO DEBERÍA ENTRAR AQUÍ)
        ========================================= */
        default:
            http_response_code(400);
            echo json_encode([
                'error'  => 'Acción no válida',
                'action' => $action
            ]);
    }

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
