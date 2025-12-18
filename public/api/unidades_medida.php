<?php
/**
 * API Catálogo Unidades de Medida
 * Tabla: c_unimed
 */

require_once __DIR__ . '/../../app/db.php';

/* =====================================================
   CAPTURA DE ACTION (POST / GET / JSON)
===================================================== */

$action = '';

if (!empty($_POST['action'])) {
    $action = $_POST['action'];
} elseif (!empty($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (isset($json['action'])) {
            $action = $json['action'];
            $_POST = $json;
        }
    }
}

/* DataTables: si no viene action → list */
if ($action === '') {
    $action = 'list';
}

try {

    switch ($action) {

        /* ===============================
           LISTADO
        =============================== */
        case 'list':

            $sql = "
                SELECT
                    id_umed,
                    cve_umed,
                    des_umed,
                    mav_cveunimed,
                    imp_cosprom,
                    Activo
                FROM c_unimed
                ORDER BY des_umed
            ";

            echo json_encode(db_all($sql));
        break;

        /* ===============================
           GUARDAR (NUEVO / EDICIÓN)
        =============================== */
        case 'save':

            $id = isset($_POST['id_umed']) ? (int)$_POST['id_umed'] : 0;

            if ($id === 0) {

                $sql = "
                    INSERT INTO c_unimed
                        (cve_umed, des_umed, mav_cveunimed, imp_cosprom, Activo)
                    VALUES
                        (:cve, :des, :mav, :imp, 1)
                ";

                db_exec($sql, [
                    'cve' => $_POST['cve_umed'] ?? null,
                    'des' => $_POST['des_umed'] ?? null,
                    'mav' => $_POST['mav_cveunimed'] ?? null,
                    'imp' => $_POST['imp_cosprom'] ?? null
                ]);

            } else {

                $sql = "
                    UPDATE c_unimed SET
                        cve_umed        = :cve,
                        des_umed        = :des,
                        mav_cveunimed   = :mav,
                        imp_cosprom     = :imp
                    WHERE id_umed = :id
                ";

                db_exec($sql, [
                    'cve' => $_POST['cve_umed'] ?? null,
                    'des' => $_POST['des_umed'] ?? null,
                    'mav' => $_POST['mav_cveunimed'] ?? null,
                    'imp' => $_POST['imp_cosprom'] ?? null,
                    'id'  => $id
                ]);
            }

            echo json_encode(['success'=>true]);
        break;

        /* ===============================
           DELETE LÓGICO
        =============================== */
        case 'delete':

            db_exec(
                "UPDATE c_unimed SET Activo=0 WHERE id_umed=:id",
                ['id'=>$_POST['id_umed']]
            );

            echo json_encode(['success'=>true]);
        break;

        /* ===============================
           RECUPERAR
        =============================== */
        case 'restore':

            db_exec(
                "UPDATE c_unimed SET Activo=1 WHERE id_umed=:id",
                ['id'=>$_POST['id_umed']]
            );

            echo json_encode(['success'=>true]);
        break;

        default:
            echo json_encode([
                'error'  => 'Acción no válida',
                'action' => $action
            ]);
    }

} catch (Throwable $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
