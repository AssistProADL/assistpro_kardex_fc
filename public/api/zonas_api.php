<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jexit($ok, $msg='', $data=[]){
    echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    /* ======================================================
       LIST
    ====================================================== */
    if ($action === 'list') {

        $almacenp_id  = $_GET['almacenp_id'] ?? null;
        $solo_activas = (int)($_GET['solo_activas'] ?? 1);

        if (!$almacenp_id) jexit(true,'',[]);

        $where = "WHERE a.cve_almacenp = :ap";
        $params = [':ap'=>$almacenp_id];

        if ($solo_activas === 1) {
            $where .= " AND COALESCE(a.Activo,1)=1";
        }

        $sql = "
            SELECT
                a.cve_almac,
                a.clave_almacen,
                a.des_almac,
                a.Cve_TipoZona,
                a.clasif_abc,
                COALESCE(a.Activo,1) AS Activo
            FROM c_almacen a
            $where
            ORDER BY a.des_almac
        ";

        jexit(true,'',db_all($sql,$params));
    }

    /* ======================================================
       GET
    ====================================================== */
    if ($action === 'get') {

        $id = $_GET['id'] ?? null;
        if (!$id) jexit(false,'ID requerido');

        $row = db_one("
            SELECT * FROM c_almacen
            WHERE cve_almac = ?
        ", [$id]);

        jexit(true,'',$row);
    }

    /* ======================================================
       CREATE
    ====================================================== */
    if ($action === 'create') {

        $clave  = strtoupper(trim($_POST['clave_almacen'] ?? ''));
        $nombre = trim($_POST['des_almac'] ?? '');
        $almacp = $_POST['cve_almacenp'] ?? null;
        $tipo   = $_POST['Cve_TipoZona'] ?? null;
        $abc    = $_POST['clasif_abc'] ?? null;

        if (!$clave || !$nombre || !$almacp) {
            jexit(false,'Datos obligatorios incompletos');
        }

        // Validar duplicado
        $existe = db_val("
            SELECT COUNT(*) 
            FROM c_almacen
            WHERE clave_almacen = ?
            AND cve_almacenp = ?
        ", [$clave,$almacp]);

        if ($existe > 0) {
            jexit(false,'Ya existe una zona con esa clave');
        }

        db_tx(function() use($clave,$nombre,$almacp,$tipo,$abc){

            dbq("
                INSERT INTO c_almacen
                (clave_almacen,des_almac,cve_almacenp,Cve_TipoZona,clasif_abc,Activo)
                VALUES (?,?,?,?,?,1)
            ", [$clave,$nombre,$almacp,$tipo,$abc]);

        });

        jexit(true,'Zona creada correctamente');
    }

    /* ======================================================
       UPDATE
    ====================================================== */
    if ($action === 'update') {

        $id     = $_POST['cve_almac'] ?? null;
        $clave  = strtoupper(trim($_POST['clave_almacen'] ?? ''));
        $nombre = trim($_POST['des_almac'] ?? '');
        $tipo   = $_POST['Cve_TipoZona'] ?? null;
        $abc    = $_POST['clasif_abc'] ?? null;

        if (!$id || !$clave || !$nombre) {
            jexit(false,'Datos incompletos');
        }

        db_tx(function() use($id,$clave,$nombre,$tipo,$abc){

            dbq("
                UPDATE c_almacen
                SET clave_almacen=?,
                    des_almac=?,
                    Cve_TipoZona=?,
                    clasif_abc=?
                WHERE cve_almac=?
            ", [$clave,$nombre,$tipo,$abc,$id]);

        });

        jexit(true,'Zona actualizada');
    }

    /* ======================================================
       TOGGLE ACTIVO
    ====================================================== */
    if ($action === 'toggle') {

        $id = $_POST['id'] ?? null;
        if (!$id) jexit(false,'ID requerido');

        dbq("
            UPDATE c_almacen
            SET Activo = IF(COALESCE(Activo,1)=1,0,1)
            WHERE cve_almac=?
        ", [$id]);

        jexit(true,'Estado actualizado');
    }

    jexit(false,'Acción no válida');

} catch(Throwable $e) {
    jexit(false,'Error interno',$e->getMessage());
}
