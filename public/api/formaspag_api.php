<?php
// public/api/formaspag_api.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

function jexit($ok, $msg = '', $extra = [])
{
    echo json_encode(array_merge([
        'ok' => (bool) $ok,
        'msg' => $msg
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

try {

    if ($action === 'empresas') {
        $sql = "SELECT DISTINCT IFNULL(IdEmpresa,'') AS IdEmpresa
                FROM formaspag
                WHERE IFNULL(IdEmpresa,'') <> ''
                ORDER BY IdEmpresa";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        jexit(true, '', ['data' => $rows]);
    }

    if ($action === 'get') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0)
            jexit(false, 'ID inválido');

        $stmt = $pdo->prepare("SELECT IdFpag, Forma, Clave, Status, IFNULL(IdEmpresa,'') AS IdEmpresa
                               FROM formaspag
                               WHERE IdFpag = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)
            jexit(false, 'Registro no encontrado');
        jexit(true, '', ['data' => $row]);
    }

    if ($action === 'save') {
        $id = (int) ($_POST['IdFpag'] ?? 0);
        $Forma = trim((string) ($_POST['Forma'] ?? ''));
        $Clave = trim((string) ($_POST['Clave'] ?? ''));
        $IdEmpresa = trim((string) ($_POST['IdEmpresa'] ?? ''));
        $Status = (int) ($_POST['Status'] ?? 1);

        if ($Forma === '' || $Clave === '') {
            jexit(false, 'Forma y Clave son obligatorios.');
        }

        if ($id > 0) {
            $sql = "UPDATE formaspag
                    SET Forma = :Forma,
                        Clave = :Clave,
                        IdEmpresa = :IdEmpresa,
                        Status = :Status
                    WHERE IdFpag = :IdFpag";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':Forma' => $Forma,
                ':Clave' => $Clave,
                ':IdEmpresa' => $IdEmpresa,
                ':Status' => $Status,
                ':IdFpag' => $id
            ]);
            jexit(true, 'Actualizado correctamente.');
        } else {
            $sql = "INSERT INTO formaspag (Forma, Clave, Status, IdEmpresa)
                    VALUES (:Forma, :Clave, :Status, :IdEmpresa)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':Forma' => $Forma,
                ':Clave' => $Clave,
                ':Status' => $Status,
                ':IdEmpresa' => $IdEmpresa
            ]);
            jexit(true, 'Creado correctamente.', ['id' => (int) $pdo->lastInsertId()]);
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            jexit(false, 'ID inválido');

        $stmt = $pdo->prepare("SELECT Status FROM formaspag WHERE IdFpag = ?");
        $stmt->execute([$id]);
        $cur = $stmt->fetchColumn();
        if ($cur === false)
            jexit(false, 'Registro no encontrado');

        $new = ((int) $cur === 1) ? 0 : 1;

        $upd = $pdo->prepare("UPDATE formaspag SET Status = ? WHERE IdFpag = ?");
        $upd->execute([$new, $id]);

        jexit(true, $new === 1 ? 'Recuperado correctamente.' : 'Inactivado correctamente.', ['Status' => $new]);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            jexit(false, 'ID inválido');

        $del = $pdo->prepare("DELETE FROM formaspag WHERE IdFpag = ?");
        $del->execute([$id]);

        jexit(true, 'Eliminado (Hard Delete) correctamente.');
    }

    // =========================
    // Importar CSV (carga masiva)
    // Layout esperado (encabezado recomendado):
    // Clave,Forma,IdEmpresa,Status
    // =========================
    if ($action === 'import_csv') {

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            jexit(false, 'Archivo CSV inválido o no enviado.');
        }

        $tmp = $_FILES['file']['tmp_name'];
        $fh = fopen($tmp, 'r');
        if (!$fh)
            jexit(false, 'No se pudo leer el archivo.');

        $pdo->beginTransaction();

        $ok = 0;
        $err = 0;
        $errors = [];

        // Leer primer renglón (puede ser header)
        $first = fgetcsv($fh);
        if ($first === false) {
            $pdo->rollBack();
            jexit(false, 'CSV vacío.');
        }

        // Detectar header
        $lower = array_map(fn($x) => strtolower(trim((string) $x)), $first);
        $hasHeader = in_array('clave', $lower, true) || in_array('forma', $lower, true);

        if (!$hasHeader) {
            // Si no hay header, tratamos el first como data
            rewind($fh);
        }

        // Upsert por (Clave + IdEmpresa) para evitar duplicados operativos
        $sqlFind = "SELECT IdFpag FROM formaspag WHERE Clave = ? AND IFNULL(IdEmpresa,'') = ?";
        $stmtFind = $pdo->prepare($sqlFind);

        $sqlUpd = "UPDATE formaspag
                   SET Forma = ?, Status = ?
                   WHERE IdFpag = ?";
        $stmtUpd = $pdo->prepare($sqlUpd);

        $sqlIns = "INSERT INTO formaspag (Forma, Clave, Status, IdEmpresa)
                   VALUES (?,?,?,?)";
        $stmtIns = $pdo->prepare($sqlIns);

        $line = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;

            // Permitir CSV con 4 columnas: Clave,Forma,IdEmpresa,Status
            // Si vienen más columnas, tomamos las primeras 4
            $Clave = trim((string) ($row[0] ?? ''));
            $Forma = trim((string) ($row[1] ?? ''));
            $IdEmpresa = trim((string) ($row[2] ?? ''));
            $Status = trim((string) ($row[3] ?? '1'));

            if ($Clave === '' || $Forma === '') {
                $err++;
                $errors[] = "Línea {$line}: Clave/Forma obligatorios.";
                continue;
            }

            $StatusInt = ((string) $Status === '0') ? 0 : 1;

            try {
                $stmtFind->execute([$Clave, $IdEmpresa]);
                $id = $stmtFind->fetchColumn();

                if ($id) {
                    $stmtUpd->execute([$Forma, $StatusInt, (int) $id]);
                } else {
                    $stmtIns->execute([$Forma, $Clave, $StatusInt, $IdEmpresa]);
                }
                $ok++;
            } catch (Throwable $e) {
                $err++;
                $errors[] = "Línea {$line}: " . $e->getMessage();
            }
        }

        fclose($fh);

        if ($err > 0) {
            // En carga masiva preferimos consistencia: rollback si hubo errores
            $pdo->rollBack();
            jexit(false, "Importación con errores. OK: {$ok}, ERR: {$err}", [
                'total_ok' => $ok,
                'total_err' => $err,
                'errors' => array_slice($errors, 0, 25)
            ]);
        }

        $pdo->commit();
        jexit(true, "Importación exitosa. Registros: {$ok}", ['total_ok' => $ok]);
    }

    // =========================
    // DataTables server-side
    // =========================
    if ($action === 'list') {

        $draw = (int) ($_GET['draw'] ?? 1);
        $start = (int) ($_GET['start'] ?? 0);
        $len = (int) ($_GET['length'] ?? 25);
        if ($len > 25)
            $len = 25; // max 25 como estándar AssistPro

        $search = trim((string) ($_GET['search']['value'] ?? ''));

        $fEmpresa = trim((string) ($_GET['fEmpresa'] ?? ''));
        $fStatus = trim((string) ($_GET['fStatus'] ?? ''));

        $orderCol = (int) ($_GET['order'][0]['column'] ?? 1);
        $orderDir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $cols = [
            1 => 'IdFpag',
            2 => 'Forma',
            3 => 'Clave',
            4 => 'IdEmpresa',
            5 => 'Status'
        ];
        $orderBy = $cols[$orderCol] ?? 'IdFpag';

        $where = [];
        $params = [];

        if ($fEmpresa !== '') {
            $where[] = "IFNULL(IdEmpresa,'') = :IdEmpresa";
            $params[':IdEmpresa'] = $fEmpresa;
        }
        if ($fStatus !== '' && ($fStatus === '0' || $fStatus === '1')) {
            $where[] = "Status = :Status";
            $params[':Status'] = (int) $fStatus;
        }
        if ($search !== '') {
            $where[] = "(Forma LIKE :q OR Clave LIKE :q OR IFNULL(IdEmpresa,'') LIKE :q)";
            $params[':q'] = "%{$search}%";
        }

        $whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";

        $total = (int) $pdo->query("SELECT COUNT(*) FROM formaspag")->fetchColumn();

        $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM formaspag {$whereSql}");
        $stmtCnt->execute($params);
        $filtered = (int) $stmtCnt->fetchColumn();

        $sql = "SELECT IdFpag, Forma, Clave, Status, IFNULL(IdEmpresa,'') AS IdEmpresa
                FROM formaspag
                {$whereSql}
                ORDER BY {$orderBy} {$orderDir}
                LIMIT :start, :len";
        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':len', $len, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    jexit(false, 'Acción no reconocida.');

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction())
        $pdo->rollBack();
    jexit(false, 'Error: ' . $e->getMessage());
}
