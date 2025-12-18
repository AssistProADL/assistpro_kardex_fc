<?php
/****************************************************
 * API Catálogo Grupos de Artículos
 * Tabla: c_gpoarticulo
 * Estándar AssistPro (mismo patrón Clasificación)
 ****************************************************/

require_once __DIR__ . '/../../app/db.php';

/* ================== CONEXIÓN ================== */
try {
    $pdo = db_pdo();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error de conexión a BD',
        'detalle' => $e->getMessage()
    ]);
    exit;
}

/* ================== INPUT UNIFICADO ================== */
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? 'list');

/* ================== PAGINACIÓN (DataTables) ================== */
$start      = intval($_GET['start'] ?? 0);
$length     = intval($_GET['length'] ?? 25);
$draw       = intval($_GET['draw'] ?? 1);
$search     = trim($_GET['search']['value'] ?? '');
$inactivos  = intval($_GET['inactivos'] ?? 0);

try {

    switch ($action) {

        /* =====================================================
           LISTADO (SERVER SIDE – PAGINADO)
        ===================================================== */
        case 'list':

            header('Content-Type: application/json');

            $where  = 'WHERE Activo = ?';
            $params = [$inactivos ? 0 : 1];

            if ($search !== '') {
                $where .= " AND (
                    cve_gpoart LIKE ?
                    OR des_gpoart LIKE ?
                )";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            /* Total sin filtro */
            $stmtTotal = $pdo->prepare(
                "SELECT COUNT(*) FROM c_gpoarticulo WHERE Activo = ?"
            );
            $stmtTotal->execute([$inactivos ? 0 : 1]);
            $recordsTotal = $stmtTotal->fetchColumn();

            /* Total filtrado */
            $stmtCount = $pdo->prepare(
                "SELECT COUNT(*) FROM c_gpoarticulo $where"
            );
            $stmtCount->execute($params);
            $recordsFiltered = $stmtCount->fetchColumn();

            /* Datos */
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    cve_gpoart,
                    des_gpoart,
                    por_depcont,
                    por_depfical,
                    id_almacen,
                    Activo
                FROM c_gpoarticulo
                $where
                ORDER BY des_gpoart
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

        /* =====================================================
           CREAR / ACTUALIZAR (UPSERT CONTROLADO)
        ===================================================== */
        case 'save':

            if (empty($input['cve_gpoart'])) {
                throw new Exception('La clave del grupo es obligatoria');
            }

            /* Validar clave única */
            $sqlVal = "SELECT id FROM c_gpoarticulo WHERE cve_gpoart = ?";
            $params = [$input['cve_gpoart']];

            if (!empty($input['id'])) {
                $sqlVal .= " AND id <> ?";
                $params[] = $input['id'];
            }

            $stmtVal = $pdo->prepare($sqlVal);
            $stmtVal->execute($params);

            if ($stmtVal->fetch()) {
                throw new Exception('La clave del grupo ya existe');
            }

            if (empty($input['id'])) {

                /* INSERT */
                $stmt = $pdo->prepare("
                    INSERT INTO c_gpoarticulo
                    (
                        cve_gpoart,
                        des_gpoart,
                        por_depcont,
                        por_depfical,
                        id_almacen,
                        Activo
                    )
                    VALUES (?,?,?,?,?,1)
                ");

                $stmt->execute([
                    $input['cve_gpoart'],
                    $input['des_gpoart'] ?? null,
                    $input['por_depcont'] ?? null,
                    $input['por_depfical'] ?? null,
                    $input['id_almacen'] ?? null
                ]);

            } else {

                /* UPDATE */
                $stmt = $pdo->prepare("
                    UPDATE c_gpoarticulo SET
                        cve_gpoart   = ?,
                        des_gpoart   = ?,
                        por_depcont  = ?,
                        por_depfical = ?,
                        id_almacen   = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $input['cve_gpoart'],
                    $input['des_gpoart'] ?? null,
                    $input['por_depcont'] ?? null,
                    $input['por_depfical'] ?? null,
                    $input['id_almacen'] ?? null,
                    $input['id']
                ]);
            }

            echo json_encode(['success' => true]);
            break;

        /* =====================================================
           DESACTIVAR
        ===================================================== */
        case 'delete':

            $pdo->prepare(
                "UPDATE c_gpoarticulo SET Activo = 0 WHERE id = ?"
            )->execute([$input['id']]);

            echo json_encode(['success' => true]);
            break;

        /* =====================================================
           RESTAURAR
        ===================================================== */
        case 'restore':

            $pdo->prepare(
                "UPDATE c_gpoarticulo SET Activo = 1 WHERE id = ?"
            )->execute([$input['id']]);

            echo json_encode(['success' => true]);
            break;

        /* =====================================================
           EXPORTAR CSV
        ===================================================== */
        case 'export':

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=grupos_articulos.csv');

            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Clave Grupo',
                'Descripción',
                '% Depósito Contable',
                '% Depósito Fiscal',
                'Almacén'
            ]);

            $rows = $pdo->query("
                SELECT
                    cve_gpoart,
                    des_gpoart,
                    por_depcont,
                    por_depfical,
                    id_almacen
                FROM c_gpoarticulo
                WHERE Activo = 1
                ORDER BY des_gpoart
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
