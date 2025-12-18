<?php
/**
 * AssistPro – API Catálogo Grupo de Clientes
 * Tabla: c_gpoclientes
 * Campos: id (AI PK), cve_grupo, des_grupo, Activo
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

/* =========================
   Helpers
========================= */
function ap_pdo() {
    if (function_exists('db_pdo')) return db_pdo();
    if (function_exists('db')) return db();
    throw new Exception("No existe función de conexión PDO.");
}

function json_out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function req_json() {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function upper($s) {
    return mb_strtoupper(trim((string)$s), 'UTF-8');
}

/* =========================
   Init
========================= */
try {
    $pdo = ap_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $TABLE = 'c_gpoclientes';
    $PK    = 'id';

    // 🔑 ACTION (default seguro)
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    if ($action === '') {
        $action = 'meta'; // evita “Acción no válida”
    }

    /* =========================
       META / HEALTHCHECK
    ========================= */
    if ($action === 'meta') {
        json_out([
            'ok' => true,
            'modulo' => 'Catálogo Grupo de Clientes',
            'tabla' => $TABLE,
            'pk' => $PK,
            'columns' => [
                ['Field'=>'id','Type'=>'int','Key'=>'PRI'],
                ['Field'=>'cve_grupo','Type'=>'varchar(50)'],
                ['Field'=>'des_grupo','Type'=>'varchar(200)'],
                ['Field'=>'Activo','Type'=>'int'],
            ]
        ]);
    }

    /* =========================
       LIST
    ========================= */
    if ($action === 'list') {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = max(1, min(500, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $q      = trim($_GET['q'] ?? '');
        $activo = $_GET['activo'] ?? '1';

        $where  = [];
        $params = [];

        if ($activo !== '') {
            $where[] = "Activo = :a";
            $params[':a'] = (int)$activo;
        }

        if ($q !== '') {
            $where[] = "(cve_grupo LIKE :q OR des_grupo LIKE :q)";
            $params[':q'] = "%$q%";
        }

        $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

        $stc = $pdo->prepare("SELECT COUNT(*) FROM $TABLE $whereSql");
        $stc->execute($params);
        $total = (int)$stc->fetchColumn();

        $sql = "SELECT id, cve_grupo, des_grupo, Activo
                FROM $TABLE
                $whereSql
                ORDER BY id DESC
                LIMIT $limit OFFSET $offset";
        $st = $pdo->prepare($sql);
        $st->execute($params);

        json_out([
            'ok' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'data' => $st->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* =========================
       SAVE (INSERT / UPDATE)
    ========================= */
    if ($action === 'save') {
        $in   = req_json();
        $data = $in['data'] ?? $in;

        $id   = $data['id'] ?? null;
        $cve  = upper($data['cve_grupo'] ?? '');
        $des  = trim($data['des_grupo'] ?? '');
        $act  = isset($data['Activo']) ? (int)$data['Activo'] : 1;

        if ($cve === '' || $des === '') {
            json_out(['ok'=>false,'msg'=>'Clave y descripción son obligatorias.'],400);
        }

        if (!$id) {
            // Validar duplicado
            $st = $pdo->prepare("SELECT COUNT(*) FROM $TABLE WHERE UPPER(cve_grupo)=UPPER(:c)");
            $st->execute([':c'=>$cve]);
            if ($st->fetchColumn() > 0) {
                json_out(['ok'=>false,'msg'=>'La clave ya existe.'],409);
            }

            $st = $pdo->prepare(
                "INSERT INTO $TABLE (cve_grupo, des_grupo, Activo)
                 VALUES (:c,:d,:a)"
            );
            $st->execute([':c'=>$cve, ':d'=>$des, ':a'=>$act]);

            json_out(['ok'=>true,'msg'=>'Registro creado.','id'=>$pdo->lastInsertId()]);
        } else {
            // Update
            $st = $pdo->prepare(
                "UPDATE $TABLE
                 SET cve_grupo=:c, des_grupo=:d, Activo=:a
                 WHERE id=:id"
            );
            $st->execute([':c'=>$cve, ':d'=>$des, ':a'=>$act, ':id'=>$id]);

            json_out(['ok'=>true,'msg'=>'Registro actualizado.']);
        }
    }

    /* =========================
       TOGGLE ACTIVO
    ========================= */
    if ($action === 'toggle') {
        $in  = req_json();
        $id  = $in['id'] ?? $_GET['id'] ?? null;
        $val = $in['Activo'] ?? $_GET['activo'] ?? null;

        if ($id === null || $val === null) {
            json_out(['ok'=>false,'msg'=>'Parámetros incompletos.'],400);
        }

        $st = $pdo->prepare("UPDATE $TABLE SET Activo=:a WHERE id=:id");
        $st->execute([':a'=>(int)$val, ':id'=>$id]);

        json_out([
            'ok'=>true,
            'msg'=>((int)$val===1 ? 'Registro recuperado.' : 'Registro inactivado.')
        ]);
    }

    /* =========================
       DELETE (HARD)
    ========================= */
    if ($action === 'delete') {
        $in = req_json();
        $id = $in['id'] ?? $_GET['id'] ?? null;

        if ($id === null) json_out(['ok'=>false,'msg'=>'Falta id.'],400);

        $st = $pdo->prepare("DELETE FROM $TABLE WHERE id=:id");
        $st->execute([':id'=>$id]);

        json_out(['ok'=>true,'msg'=>'Registro eliminado.']);
    }

    /* =========================
       EXPORT CSV
    ========================= */
    if ($action === 'export') {
        $activo = $_GET['activo'] ?? '';
        $where  = $activo!=='' ? "WHERE Activo=:a" : "";
        $params = $activo!=='' ? [':a'=>(int)$activo] : [];

        $st = $pdo->prepare("SELECT id,cve_grupo,des_grupo,Activo FROM $TABLE $where ORDER BY id");
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $csv = "id,cve_grupo,des_grupo,Activo\r\n";
        foreach ($rows as $r) {
            $csv .= "{$r['id']},\"{$r['cve_grupo']}\",\"{$r['des_grupo']}\",{$r['Activo']}\r\n";
        }

        json_out(['ok'=>true,'filename'=>'c_gpoclientes.csv','csv'=>$csv]);
    }

    /* =========================
       IMPORT CSV
    ========================= */
    if ($action === 'import') {
        if (!isset($_FILES['file'])) {
            json_out(['ok'=>false,'msg'=>'Archivo no recibido.'],400);
        }

        $rows = array_map('str_getcsv', file($_FILES['file']['tmp_name']));
        $hdr  = array_shift($rows);

        if ($hdr !== ['id','cve_grupo','des_grupo','Activo']) {
            json_out(['ok'=>false,'msg'=>'Layout CSV incorrecto.'],400);
        }

        $ok = 0;
        foreach ($rows as $r) {
            [$id,$cve,$des,$act] = $r;
            if (!$cve || !$des) continue;

            if (!$id) {
                $st = $pdo->prepare(
                    "INSERT IGNORE INTO $TABLE (cve_grupo,des_grupo,Activo)
                     VALUES (:c,:d,:a)"
                );
                $st->execute([':c'=>upper($cve),':d'=>$des,':a'=>(int)$act]);
            } else {
                $st = $pdo->prepare(
                    "UPDATE $TABLE
                     SET cve_grupo=:c, des_grupo=:d, Activo=:a
                     WHERE id=:id"
                );
                $st->execute([':c'=>upper($cve),':d'=>$des,':a'=>(int)$act,':id'=>$id]);
            }
            $ok++;
        }

        json_out(['ok'=>true,'msg'=>"Importación completa. Registros: $ok"]);
    }

    json_out(['ok'=>false,'msg'=>'Acción no soportada.'],400);

} catch (Throwable $e) {
    json_out(['ok'=>false,'msg'=>$e->getMessage()],500);
}
