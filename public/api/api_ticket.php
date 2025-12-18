<?php
// public/api/api_ticket.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php'; // Debe exponer $pdo (PDO) o helpers dbq/db_all

// ---- helpers mínimos (compatibles con AssistPro: $pdo o helpers) ----
function ap_pdo() {
    // Si tu app/db.php define $pdo global, úsalo.
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
    // Si tu app/db.php define función db() o similar, ajusta aquí.
    if (function_exists('db')) return db();
    throw new Exception("No se encontró conexión PDO. Revisa app/db.php");
}

function jexit($ok, $msg = "", $extra = []) {
    echo json_encode(array_merge([
        "success" => (bool)$ok,
        "message" => $msg
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function post($k, $d=null) { return isset($_POST[$k]) ? $_POST[$k] : $d; }
function getv($k, $d=null) { return isset($_GET[$k]) ? $_GET[$k] : $d; }

function logo_to_blob_from_base64($b64) {
    $b64 = trim((string)$b64);
    if ($b64 === '') return null;

    // acepta "data:image/png;base64,AAAA"
    if (strpos($b64, 'base64,') !== false) {
        $b64 = substr($b64, strpos($b64, 'base64,') + 7);
    }
    $bin = base64_decode($b64, true);
    return ($bin === false) ? null : $bin;
}

function blob_to_base64($blob) {
    if ($blob === null) return "";
    return base64_encode($blob);
}

// ---- routing ----
$action = post('action', getv('action', 'list'));

try {
    $pdo = ap_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'list') {

        // DataTables params
        $draw   = (int) getv('draw', 1);
        $start  = (int) getv('start', 0);
        $length = (int) getv('length', 25);

        $search = getv('search', []);
        $searchValue = '';
        if (is_array($search) && isset($search['value'])) $searchValue = trim($search['value']);

        $order = getv('order', []);
        $orderCol = 0;
        $orderDir = 'ASC';
        if (is_array($order) && isset($order[0]['column'])) $orderCol = (int)$order[0]['column'];
        if (is_array($order) && isset($order[0]['dir'])) {
            $orderDir = strtoupper($order[0]['dir']) === 'DESC' ? 'DESC' : 'ASC';
        }

        // filtros
        $idEmpresa = trim((string)getv('IdEmpresa', ''));
        $includeInactive = (int)getv('include_inactive', 0); // 0 solo activos (MLiq=1), 1 todos

        // columnas DataTables (ojo: en UI la primera es "Acciones", entonces desplazamos)
        $cols = [
            'ID',
            'Linea1',
            'Linea2',
            'Linea3',
            'Linea4',
            'Mensaje',
            'Tdv',
            'MLiq',
            'IdEmpresa'
        ];

        // mapeo: DataTables col 0 es Acciones, col 1 -> ID, etc.
        $colIndex = max(0, $orderCol - 1);
        $orderBy = $cols[min($colIndex, count($cols)-1)];

        $where = [];
        $params = [];

        if (!$includeInactive) {
            $where[] = "IFNULL(MLiq,1)=1";
        }
        if ($idEmpresa !== '') {
            $where[] = "IdEmpresa = :IdEmpresa";
            $params[':IdEmpresa'] = $idEmpresa;
        }
        if ($searchValue !== '') {
            $where[] = "(Linea1 LIKE :q OR Linea2 LIKE :q OR Linea3 LIKE :q OR Linea4 LIKE :q OR Mensaje LIKE :q OR IdEmpresa LIKE :q)";
            $params[':q'] = "%{$searchValue}%";
        }

        $whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";

        // totals
        $sqlTotal = "SELECT COUNT(*) FROM ctiket";
        $totalRecords = (int)$pdo->query($sqlTotal)->fetchColumn();

        $sqlFiltered = "SELECT COUNT(*) FROM ctiket {$whereSql}";
        $stF = $pdo->prepare($sqlFiltered);
        $stF->execute($params);
        $recordsFiltered = (int)$stF->fetchColumn();

        $sql = "
            SELECT ID, Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, MLiq, IdEmpresa
            FROM ctiket
            {$whereSql}
            ORDER BY {$orderBy} {$orderDir}
            LIMIT :lim OFFSET :off
        ";
        $st = $pdo->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':lim', $length, PDO::PARAM_INT);
        $st->bindValue(':off', $start, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, "", [
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $recordsFiltered,
            "data" => $rows
        ]);
    }

    if ($action === 'get') {
        $id = (int)post('ID', 0);
        if ($id <= 0) jexit(false, "ID inválido");

        $st = $pdo->prepare("SELECT ID, Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, MLiq, IdEmpresa, LOGO FROM ctiket WHERE ID = :id");
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) jexit(false, "Registro no encontrado");

        $r['LOGO_BASE64'] = blob_to_base64($r['LOGO']);
        unset($r['LOGO']);

        jexit(true, "", ["row" => $r]);
    }

    if ($action === 'create') {
        $Linea1 = trim((string)post('Linea1',''));
        $Linea2 = trim((string)post('Linea2',''));
        $Linea3 = trim((string)post('Linea3',''));
        $Linea4 = trim((string)post('Linea4',''));
        $Mensaje = trim((string)post('Mensaje',''));
        $Tdv = (int)post('Tdv', 0);
        $MLiq = (int)post('MLiq', 1);
        $IdEmpresa = trim((string)post('IdEmpresa',''));

        $logoB64 = (string)post('LOGO_BASE64','');
        $logoBlob = logo_to_blob_from_base64($logoB64);

        $sql = "INSERT INTO ctiket (Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, LOGO, MLiq, IdEmpresa)
                VALUES (:Linea1,:Linea2,:Linea3,:Linea4,:Mensaje,:Tdv,:LOGO,:MLiq,:IdEmpresa)";
        $st = $pdo->prepare($sql);
        $st->bindValue(':Linea1', $Linea1);
        $st->bindValue(':Linea2', $Linea2);
        $st->bindValue(':Linea3', $Linea3);
        $st->bindValue(':Linea4', $Linea4);
        $st->bindValue(':Mensaje', $Mensaje);
        $st->bindValue(':Tdv', $Tdv, PDO::PARAM_INT);
        $st->bindValue(':LOGO', $logoBlob, $logoBlob === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $st->bindValue(':MLiq', $MLiq, PDO::PARAM_INT);
        $st->bindValue(':IdEmpresa', $IdEmpresa);
        $st->execute();

        jexit(true, "Ticket creado", ["ID" => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $ID = (int)post('ID', 0);
        if ($ID <= 0) jexit(false, "ID inválido");

        $Linea1 = trim((string)post('Linea1',''));
        $Linea2 = trim((string)post('Linea2',''));
        $Linea3 = trim((string)post('Linea3',''));
        $Linea4 = trim((string)post('Linea4',''));
        $Mensaje = trim((string)post('Mensaje',''));
        $Tdv = (int)post('Tdv', 0);
        $MLiq = (int)post('MLiq', 1);
        $IdEmpresa = trim((string)post('IdEmpresa',''));

        $logoB64 = (string)post('LOGO_BASE64', '__NOCHANGE__');

        $pdo->beginTransaction();

        if ($logoB64 !== '__NOCHANGE__') {
            $logoBlob = logo_to_blob_from_base64($logoB64);
            $sql = "UPDATE ctiket
                    SET Linea1=:Linea1, Linea2=:Linea2, Linea3=:Linea3, Linea4=:Linea4,
                        Mensaje=:Mensaje, Tdv=:Tdv, LOGO=:LOGO, MLiq=:MLiq, IdEmpresa=:IdEmpresa
                    WHERE ID=:ID";
            $st = $pdo->prepare($sql);
            $st->bindValue(':LOGO', $logoBlob, $logoBlob === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        } else {
            $sql = "UPDATE ctiket
                    SET Linea1=:Linea1, Linea2=:Linea2, Linea3=:Linea3, Linea4=:Linea4,
                        Mensaje=:Mensaje, Tdv=:Tdv, MLiq=:MLiq, IdEmpresa=:IdEmpresa
                    WHERE ID=:ID";
            $st = $pdo->prepare($sql);
        }

        $st->bindValue(':Linea1', $Linea1);
        $st->bindValue(':Linea2', $Linea2);
        $st->bindValue(':Linea3', $Linea3);
        $st->bindValue(':Linea4', $Linea4);
        $st->bindValue(':Mensaje', $Mensaje);
        $st->bindValue(':Tdv', $Tdv, PDO::PARAM_INT);
        $st->bindValue(':MLiq', $MLiq, PDO::PARAM_INT);
        $st->bindValue(':IdEmpresa', $IdEmpresa);
        $st->bindValue(':ID', $ID, PDO::PARAM_INT);
        $st->execute();

        $pdo->commit();

        jexit(true, "Ticket actualizado");
    }

    if ($action === 'inactivate') {
        $ID = (int)post('ID', 0);
        if ($ID <= 0) jexit(false, "ID inválido");

        $st = $pdo->prepare("UPDATE ctiket SET MLiq=0 WHERE ID=:ID");
        $st->execute([':ID' => $ID]);
        jexit(true, "Ticket inactivado");
    }

    if ($action === 'restore') {
        $ID = (int)post('ID', 0);
        if ($ID <= 0) jexit(false, "ID inválido");

        $st = $pdo->prepare("UPDATE ctiket SET MLiq=1 WHERE ID=:ID");
        $st->execute([':ID' => $ID]);
        jexit(true, "Ticket recuperado");
    }

    if ($action === 'delete') {
        $ID = (int)post('ID', 0);
        if ($ID <= 0) jexit(false, "ID inválido");

        $st = $pdo->prepare("DELETE FROM ctiket WHERE ID=:ID");
        $st->execute([':ID' => $ID]);
        jexit(true, "Ticket eliminado (hard delete)");
    }

    if ($action === 'export_csv') {
        // CSV con mismo layout + LOGO_BASE64
        $idEmpresa = trim((string)getv('IdEmpresa', ''));
        $includeInactive = (int)getv('include_inactive', 1);

        $where = [];
        $params = [];

        if (!$includeInactive) $where[] = "IFNULL(MLiq,1)=1";
        if ($idEmpresa !== '') { $where[] = "IdEmpresa = :IdEmpresa"; $params[':IdEmpresa'] = $idEmpresa; }

        $whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";

        $sql = "SELECT ID, Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, LOGO, MLiq, IdEmpresa
                FROM ctiket {$whereSql} ORDER BY ID ASC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ctiket_export.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Linea1','Linea2','Linea3','Linea4','Mensaje','Tdv','LOGO_BASE64','MLiq','IdEmpresa']);

        foreach ($rows as $r) {
            $r['LOGO_BASE64'] = blob_to_base64($r['LOGO']);
            fputcsv($out, [
                $r['ID'],
                $r['Linea1'],
                $r['Linea2'],
                $r['Linea3'],
                $r['Linea4'],
                $r['Mensaje'],
                $r['Tdv'],
                $r['LOGO_BASE64'],
                $r['MLiq'],
                $r['IdEmpresa']
            ]);
        }
        fclose($out);
        exit;
    }

    if ($action === 'import_csv') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            jexit(false, "Archivo CSV inválido");
        }

        $tmp = $_FILES['file']['tmp_name'];
        $fh = fopen($tmp, 'r');
        if (!$fh) jexit(false, "No se pudo leer el archivo");

        $header = fgetcsv($fh);
        if (!$header) jexit(false, "CSV vacío");

        // esperado
        $expected = ['ID','Linea1','Linea2','Linea3','Linea4','Mensaje','Tdv','LOGO_BASE64','MLiq','IdEmpresa'];

        // Normaliza header
        $map = [];
        foreach ($header as $idx => $col) {
            $col = trim((string)$col);
            $map[$col] = $idx;
        }
        foreach ($expected as $col) {
            if (!array_key_exists($col, $map)) {
                jexit(false, "Layout CSV no coincide. Falta columna: {$col}");
            }
        }

        $ok = 0; $err = 0; $errs = [];

        $pdo->beginTransaction();

        while (($row = fgetcsv($fh)) !== false) {
            try {
                $ID = (int)($row[$map['ID']] ?? 0);

                $Linea1 = (string)($row[$map['Linea1']] ?? '');
                $Linea2 = (string)($row[$map['Linea2']] ?? '');
                $Linea3 = (string)($row[$map['Linea3']] ?? '');
                $Linea4 = (string)($row[$map['Linea4']] ?? '');
                $Mensaje = (string)($row[$map['Mensaje']] ?? '');
                $Tdv = (int)($row[$map['Tdv']] ?? 0);
                $MLiq = (int)($row[$map['MLiq']] ?? 1);
                $IdEmpresa = (string)($row[$map['IdEmpresa']] ?? '');

                $logoB64 = (string)($row[$map['LOGO_BASE64']] ?? '');
                $logoBlob = logo_to_blob_from_base64($logoB64);

                if ($ID > 0) {
                    // upsert por ID (si existe -> update, si no -> insert con ID)
                    $stE = $pdo->prepare("SELECT COUNT(*) FROM ctiket WHERE ID=:ID");
                    $stE->execute([':ID' => $ID]);
                    $exists = (int)$stE->fetchColumn() > 0;

                    if ($exists) {
                        $sql = "UPDATE ctiket
                                SET Linea1=:Linea1, Linea2=:Linea2, Linea3=:Linea3, Linea4=:Linea4,
                                    Mensaje=:Mensaje, Tdv=:Tdv, LOGO=:LOGO, MLiq=:MLiq, IdEmpresa=:IdEmpresa
                                WHERE ID=:ID";
                    } else {
                        $sql = "INSERT INTO ctiket (ID, Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, LOGO, MLiq, IdEmpresa)
                                VALUES (:ID,:Linea1,:Linea2,:Linea3,:Linea4,:Mensaje,:Tdv,:LOGO,:MLiq,:IdEmpresa)";
                    }

                    $st = $pdo->prepare($sql);
                    $st->bindValue(':ID', $ID, PDO::PARAM_INT);
                    $st->bindValue(':Linea1', $Linea1);
                    $st->bindValue(':Linea2', $Linea2);
                    $st->bindValue(':Linea3', $Linea3);
                    $st->bindValue(':Linea4', $Linea4);
                    $st->bindValue(':Mensaje', $Mensaje);
                    $st->bindValue(':Tdv', $Tdv, PDO::PARAM_INT);
                    $st->bindValue(':LOGO', $logoBlob, $logoBlob === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
                    $st->bindValue(':MLiq', $MLiq, PDO::PARAM_INT);
                    $st->bindValue(':IdEmpresa', $IdEmpresa);
                    $st->execute();
                } else {
                    // insert normal
                    $sql = "INSERT INTO ctiket (Linea1, Linea2, Linea3, Linea4, Mensaje, Tdv, LOGO, MLiq, IdEmpresa)
                            VALUES (:Linea1,:Linea2,:Linea3,:Linea4,:Mensaje,:Tdv,:LOGO,:MLiq,:IdEmpresa)";
                    $st = $pdo->prepare($sql);
                    $st->bindValue(':Linea1', $Linea1);
                    $st->bindValue(':Linea2', $Linea2);
                    $st->bindValue(':Linea3', $Linea3);
                    $st->bindValue(':Linea4', $Linea4);
                    $st->bindValue(':Mensaje', $Mensaje);
                    $st->bindValue(':Tdv', $Tdv, PDO::PARAM_INT);
                    $st->bindValue(':LOGO', $logoBlob, $logoBlob === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
                    $st->bindValue(':MLiq', $MLiq, PDO::PARAM_INT);
                    $st->bindValue(':IdEmpresa', $IdEmpresa);
                    $st->execute();
                }

                $ok++;
            } catch (Throwable $e) {
                $err++;
                $errs[] = $e->getMessage();
            }
        }

        fclose($fh);
        $pdo->commit();

        jexit(true, "Importación finalizada", [
            "total_ok" => $ok,
            "total_err" => $err,
            "errors" => array_slice($errs, 0, 20)
        ]);
    }

    jexit(false, "Acción no soportada: {$action}");

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    jexit(false, $e->getMessage());
}
