<?php
// public/api/api_contactos.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../app/db.php';

function ap_json($ok, $payload = [], $http = 200) {
    http_response_code($http);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

function ap_param($k, $default = null) {
    return $_POST[$k] ?? $_GET[$k] ?? $default;
}

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = ap_param('action', 'list');

    // =========================
    // DataTables server-side LIST
    // =========================
    if ($action === 'list') {
        $draw   = (int)($_GET['draw'] ?? 1);
        $start  = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);

        $search = '';
        if (isset($_GET['search']['value'])) $search = trim((string)$_GET['search']['value']);
        else $search = trim((string)($_GET['q'] ?? ''));

        $where = "";
        $params = [];

        if ($search !== '') {
            $where = " WHERE (clave LIKE :s OR nombre LIKE :s OR apellido LIKE :s OR correo LIKE :s 
                        OR telefono1 LIKE :s OR telefono2 LIKE :s OR pais LIKE :s OR estado LIKE :s 
                        OR ciudad LIKE :s OR direccion LIKE :s)";
            $params[':s'] = "%{$search}%";
        }

        $total = (int)$pdo->query("SELECT COUNT(*) FROM c_contactos")->fetchColumn();

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM c_contactos {$where}");
        $stmtCount->execute($params);
        $filtered = (int)$stmtCount->fetchColumn();

        // Orden DataTables
        $columnsMap = [
            0 => 'id',
            1 => 'clave',
            2 => 'nombre',
            3 => 'apellido',
            4 => 'correo',
            5 => 'telefono1',
            6 => 'telefono2',
            7 => 'pais',
            8 => 'estado',
            9 => 'ciudad',
            10 => 'direccion'
        ];

        $orderColIndex = (int)($_GET['order'][0]['column'] ?? 0);
        $orderDir      = strtolower((string)($_GET['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderCol      = $columnsMap[$orderColIndex] ?? 'id';

        $sql = "SELECT id, clave, nombre, apellido, correo, telefono1, telefono2, pais, estado, ciudad, direccion
                FROM c_contactos
                {$where}
                ORDER BY {$orderCol} {$orderDir}
                LIMIT :start, :len";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':len', $length, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ap_json(true, [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows
        ]);
    }

    // =========================
    // GET ONE
    // =========================
    if ($action === 'get') {
        $id = (int)ap_param('id', 0);
        if ($id <= 0) ap_json(false, ['msg' => 'ID inválido'], 400);

        $stmt = $pdo->prepare("SELECT id, clave, nombre, apellido, correo, telefono1, telefono2, pais, estado, ciudad, direccion
                               FROM c_contactos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) ap_json(false, ['msg' => 'Registro no encontrado'], 404);
        ap_json(true, ['data' => $row]);
    }

    // =========================
    // CREATE
    // =========================
    if ($action === 'create') {
        $clave     = trim((string)ap_param('clave', ''));
        $nombre    = trim((string)ap_param('nombre', ''));
        $apellido  = trim((string)ap_param('apellido', ''));
        $correo    = trim((string)ap_param('correo', ''));
        $telefono1 = trim((string)ap_param('telefono1', ''));
        $telefono2 = trim((string)ap_param('telefono2', ''));
        $pais      = trim((string)ap_param('pais', ''));
        $estado    = trim((string)ap_param('estado', ''));
        $ciudad    = trim((string)ap_param('ciudad', ''));
        $direccion = trim((string)ap_param('direccion', ''));

        if ($clave === '' || $nombre === '') {
            ap_json(false, ['msg' => 'Campos obligatorios: clave, nombre'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO c_contactos (clave, nombre, apellido, correo, telefono1, telefono2, pais, estado, ciudad, direccion)
                               VALUES (:clave, :nombre, :apellido, :correo, :telefono1, :telefono2, :pais, :estado, :ciudad, :direccion)");
        $stmt->execute([
            ':clave' => $clave,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':correo' => $correo,
            ':telefono1' => $telefono1,
            ':telefono2' => $telefono2,
            ':pais' => $pais,
            ':estado' => $estado,
            ':ciudad' => $ciudad,
            ':direccion' => $direccion
        ]);

        ap_json(true, ['msg' => 'Contacto creado', 'id' => (int)$pdo->lastInsertId()]);
    }

    // =========================
    // UPDATE
    // =========================
    if ($action === 'update') {
        $id = (int)ap_param('id', 0);
        if ($id <= 0) ap_json(false, ['msg' => 'ID inválido'], 400);

        $clave     = trim((string)ap_param('clave', ''));
        $nombre    = trim((string)ap_param('nombre', ''));
        $apellido  = trim((string)ap_param('apellido', ''));
        $correo    = trim((string)ap_param('correo', ''));
        $telefono1 = trim((string)ap_param('telefono1', ''));
        $telefono2 = trim((string)ap_param('telefono2', ''));
        $pais      = trim((string)ap_param('pais', ''));
        $estado    = trim((string)ap_param('estado', ''));
        $ciudad    = trim((string)ap_param('ciudad', ''));
        $direccion = trim((string)ap_param('direccion', ''));

        if ($clave === '' || $nombre === '') {
            ap_json(false, ['msg' => 'Campos obligatorios: clave, nombre'], 400);
        }

        $stmt = $pdo->prepare("UPDATE c_contactos
                               SET clave=:clave, nombre=:nombre, apellido=:apellido, correo=:correo,
                                   telefono1=:telefono1, telefono2=:telefono2, pais=:pais, estado=:estado,
                                   ciudad=:ciudad, direccion=:direccion
                               WHERE id=:id");
        $stmt->execute([
            ':id' => $id,
            ':clave' => $clave,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':correo' => $correo,
            ':telefono1' => $telefono1,
            ':telefono2' => $telefono2,
            ':pais' => $pais,
            ':estado' => $estado,
            ':ciudad' => $ciudad,
            ':direccion' => $direccion
        ]);

        ap_json(true, ['msg' => 'Contacto actualizado']);
    }

    // =========================
    // DELETE (Hard delete)
    // =========================
    if ($action === 'delete') {
        $id = (int)ap_param('id', 0);
        if ($id <= 0) ap_json(false, ['msg' => 'ID inválido'], 400);

        $stmt = $pdo->prepare("DELETE FROM c_contactos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        ap_json(true, ['msg' => 'Contacto eliminado']);
    }

    // =========================
    // EXPORT CSV (same layout)
    // =========================
    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="c_contactos.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['clave','nombre','apellido','correo','telefono1','telefono2','pais','estado','ciudad','direccion']);

        $stmt = $pdo->query("SELECT clave,nombre,apellido,correo,telefono1,telefono2,pais,estado,ciudad,direccion
                             FROM c_contactos ORDER BY id DESC");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $r['clave'], $r['nombre'], $r['apellido'], $r['correo'],
                $r['telefono1'], $r['telefono2'], $r['pais'], $r['estado'], $r['ciudad'], $r['direccion']
            ]);
        }
        fclose($out);
        exit;
    }

    // =========================
    // IMPORT CSV (UPSERT by clave)
    // =========================
    if ($action === 'import_csv') {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            ap_json(false, ['msg' => 'Archivo CSV inválido'], 400);
        }

        $tmp = $_FILES['archivo']['tmp_name'];
        $fh = fopen($tmp, 'r');
        if (!$fh) ap_json(false, ['msg' => 'No se pudo leer el archivo'], 400);

        $header = fgetcsv($fh);
        if (!$header) ap_json(false, ['msg' => 'CSV vacío'], 400);

        $header = array_map('trim', $header);
        $expected = ['clave','nombre','apellido','correo','telefono1','telefono2','pais','estado','ciudad','direccion'];

        // Validación flexible (mismos nombres)
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header, true);
            if ($idx === false) {
                fclose($fh);
                ap_json(false, ['msg' => "Falta columna requerida en CSV: {$col}"], 400);
            }
            $map[$col] = $idx;
        }

        $ok = 0; $err = 0; $errores = [];

        $pdo->beginTransaction();

        $sel = $pdo->prepare("SELECT id FROM c_contactos WHERE clave = :clave LIMIT 1");
        $ins = $pdo->prepare("INSERT INTO c_contactos (clave,nombre,apellido,correo,telefono1,telefono2,pais,estado,ciudad,direccion)
                              VALUES (:clave,:nombre,:apellido,:correo,:telefono1,:telefono2,:pais,:estado,:ciudad,:direccion)");
        $upd = $pdo->prepare("UPDATE c_contactos
                              SET nombre=:nombre, apellido=:apellido, correo=:correo, telefono1=:telefono1, telefono2=:telefono2,
                                  pais=:pais, estado=:estado, ciudad=:ciudad, direccion=:direccion
                              WHERE id=:id");

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $clave  = trim((string)($row[$map['clave']] ?? ''));
            $nombre = trim((string)($row[$map['nombre']] ?? ''));

            if ($clave === '' || $nombre === '') {
                $err++;
                $errores[] = "Línea {$line}: clave y nombre son obligatorios";
                continue;
            }

            $data = [
                ':clave' => $clave,
                ':nombre' => $nombre,
                ':apellido' => trim((string)($row[$map['apellido']] ?? '')),
                ':correo' => trim((string)($row[$map['correo']] ?? '')),
                ':telefono1' => trim((string)($row[$map['telefono1']] ?? '')),
                ':telefono2' => trim((string)($row[$map['telefono2']] ?? '')),
                ':pais' => trim((string)($row[$map['pais']] ?? '')),
                ':estado' => trim((string)($row[$map['estado']] ?? '')),
                ':ciudad' => trim((string)($row[$map['ciudad']] ?? '')),
                ':direccion' => trim((string)($row[$map['direccion']] ?? '')),
            ];

            $sel->execute([':clave' => $clave]);
            $id = $sel->fetchColumn();

            if ($id) {
                $upd->execute([
                    ':id' => (int)$id,
                    ':nombre' => $data[':nombre'],
                    ':apellido' => $data[':apellido'],
                    ':correo' => $data[':correo'],
                    ':telefono1' => $data[':telefono1'],
                    ':telefono2' => $data[':telefono2'],
                    ':pais' => $data[':pais'],
                    ':estado' => $data[':estado'],
                    ':ciudad' => $data[':ciudad'],
                    ':direccion' => $data[':direccion'],
                ]);
                $ok++;
            } else {
                $ins->execute($data);
                $ok++;
            }
        }

        fclose($fh);

        if ($err > 0) {
            $pdo->rollBack();
            ap_json(false, ['msg' => 'Importación con errores. No se aplicaron cambios.', 'total_ok' => $ok, 'total_err' => $err, 'errores' => $errores], 400);
        }

        $pdo->commit();
        ap_json(true, ['msg' => 'Importación exitosa', 'total_ok' => $ok, 'total_err' => 0]);
    }

    ap_json(false, ['msg' => 'Acción no soportada'], 400);

} catch (Throwable $e) {
    ap_json(false, ['msg' => 'Error en API', 'error' => $e->getMessage()], 500);
}
