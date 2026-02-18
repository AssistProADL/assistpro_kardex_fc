<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jexit($arr){
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db_pdo();
$action = $_REQUEST['action'] ?? '';

try {

    /* =========================================================
       LIST
    ========================================================= */
    if ($action === 'list') {

        $q = trim($_GET['q'] ?? '');
        $inactivos = (int)($_GET['inactivos'] ?? 0);
        $cve_cia = (int)($_GET['cve_cia'] ?? 0);

        $sql = "
            SELECT 
                ap.id,
                ap.clave,
                ap.nombre,
                ap.cve_cia,
                ap.direccion,
                ap.contacto,
                ap.telefono,
                ap.correo,
                ap.Activo,
                ap.interno,
                ap.cve_talmacen,
                c.clave_empresa
            FROM c_almacenp ap
            INNER JOIN c_compania c ON c.cve_cia = ap.cve_cia
            WHERE 1=1
        ";

        $params = [];

        if (!$inactivos) {
            $sql .= " AND ap.Activo = 1 ";
        }

        if ($cve_cia > 0) {
            $sql .= " AND ap.cve_cia = :cia ";
            $params['cia'] = $cve_cia;
        }

        if ($q !== '') {
            $sql .= " AND (
                ap.clave LIKE :q OR
                ap.nombre LIKE :q OR
                ap.direccion LIKE :q
            ) ";
            $params['q'] = "%$q%";
        }

        $sql .= " ORDER BY ap.nombre ASC ";

        $rows = db_all($sql, $params);

        foreach ($rows as &$r) {
            $r['es_3pl'] = ((int)$r['interno'] === 0) ? 'Si' : 'No';
        }

        jexit(['rows' => $rows]);
    }

    /* =========================================================
       GET
    ========================================================= */
    if ($action === 'get') {

        $id = $_GET['id'] ?? '';

        $row = db_one("
            SELECT *
            FROM c_almacenp
            WHERE id = :id
        ", ['id' => $id]);

        if (!$row) {
            jexit(['error' => 'Registro no encontrado']);
        }

        $row['es_3pl'] = ((int)$row['interno'] === 0) ? 'Si' : 'No';

        jexit($row);
    }

    /* =========================================================
       CREATE
    ========================================================= */
    if ($action === 'create') {

        $clave = strtoupper(trim($_POST['clave'] ?? ''));
        $nombre = trim($_POST['nombre'] ?? '');
        $cve_cia = (int)($_POST['clave_empresa'] ?? 0);

        if (!$clave || !$nombre || !$cve_cia) {
            jexit(['error' => 'Clave, Nombre y Empresa son obligatorios']);
        }

        $sql = "
            INSERT INTO c_almacenp
            (clave, nombre, cve_cia, direccion, contacto, telefono, correo, Activo, interno, cve_talmacen)
            VALUES
            (:clave, :nombre, :cve_cia, :direccion, :contacto, :telefono, :correo, :Activo, :interno, :tipo)
        ";

        dbq($sql, [
            'clave' => $clave,
            'nombre' => $nombre,
            'cve_cia' => $cve_cia,
            'direccion' => $_POST['direccion'] ?? null,
            'contacto' => $_POST['responsable'] ?? null,
            'telefono' => $_POST['telefono'] ?? null,
            'correo' => $_POST['email'] ?? null,
            'Activo' => (int)($_POST['Activo'] ?? 1),
            'interno' => ($_POST['es_3pl'] === 'Si') ? 0 : 1,
            'tipo' => $_POST['tipo'] ?? null
        ]);

        jexit(['ok' => true]);
    }

    /* =========================================================
       UPDATE
    ========================================================= */
    if ($action === 'update') {

        $id = $_POST['k_id'] ?? '';

        $sql = "
            UPDATE c_almacenp SET
                nombre = :nombre,
                direccion = :direccion,
                contacto = :contacto,
                telefono = :telefono,
                correo = :correo,
                Activo = :Activo,
                interno = :interno,
                cve_talmacen = :tipo
            WHERE id = :id
        ";

        dbq($sql, [
            'nombre' => $_POST['nombre'],
            'direccion' => $_POST['direccion'],
            'contacto' => $_POST['responsable'],
            'telefono' => $_POST['telefono'],
            'correo' => $_POST['email'],
            'Activo' => (int)$_POST['Activo'],
            'interno' => ($_POST['es_3pl'] === 'Si') ? 0 : 1,
            'tipo' => $_POST['tipo'],
            'id' => $id
        ]);

        jexit(['ok' => true]);
    }

    /* =========================================================
       DELETE (Inactivar)
    ========================================================= */
    if ($action === 'delete') {

        dbq("UPDATE c_almacenp SET Activo = 0 WHERE id = :id", [
            'id' => $_POST['id']
        ]);

        jexit(['ok' => true]);
    }

    /* =========================================================
       RESTORE
    ========================================================= */
    if ($action === 'restore') {

        dbq("UPDATE c_almacenp SET Activo = 1 WHERE id = :id", [
            'id' => $_POST['id']
        ]);

        jexit(['ok' => true]);
    }

    jexit(['error' => 'Acción no válida']);

} catch (Exception $e) {
    jexit(['error' => $e->getMessage()]);
}
