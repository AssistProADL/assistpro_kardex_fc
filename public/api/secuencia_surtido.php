<?php
// public/api/secuencia_surtido.php
// API para administración de Secuencias de Surtido

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'No existe conexión PDO'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * Lee un archivo CSV y devuelve arreglo de filas normalizadas.
 * Formato esperado:
 * 0: ALMACEN_CLAVE
 * 1: CLAVE_SECUENCIA
 * 2: NOMBRE_SECUENCIA
 * 3: TIPO_SECUENCIA
 * 4: PROCESO
 * 5: BL
 * 6: ORDEN
 */
function ss_parse_csv($tmpPath, &$error)
{
    $rows = [];
    $error = '';

    if (!is_readable($tmpPath)) {
        $error = 'No se puede leer el archivo temporal.';
        return [];
    }

    $fh = fopen($tmpPath, 'r');
    if (!$fh) {
        $error = 'No fue posible abrir el archivo para lectura.';
        return [];
    }

    // leer encabezado
    $header = fgetcsv($fh, 0, ',');
    if ($header === false) {
        fclose($fh);
        $error = 'El archivo CSV está vacío.';
        return [];
    }

    $line = 1;
    while (($cols = fgetcsv($fh, 0, ',')) !== false) {
        $line++;
        if (count(array_filter($cols, fn($v) => trim((string) $v) !== '')) === 0) {
            continue; // fila completamente vacía
        }

        // Asegurar al menos 7 columnas
        for ($i = 0; $i < 7; $i++) {
            if (!isset($cols[$i])) {
                $cols[$i] = '';
            }
        }

        $rows[] = [
            'line' => $line,
            'almacen_clave' => strtoupper(trim($cols[0] ?? '')),
            'clave_sec' => trim($cols[1] ?? ''),
            'nombre' => trim($cols[2] ?? ''),
            'tipo_sec' => strtoupper(trim($cols[3] ?? '')),
            'proceso' => strtoupper(trim($cols[4] ?? '')),
            'bl' => trim($cols[5] ?? ''),
            'orden' => trim($cols[6] ?? ''),
        ];
    }

    fclose($fh);
    return $rows;
}

try {

    // --------------------------------------------------
    // CREAR NUEVA SECUENCIA (encabezado)
    // --------------------------------------------------
    if ($action === 'crear') {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $almacen_clave = trim($data['almacen_clave'] ?? '');
        $clave = trim($data['clave_sec'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $tipo = trim($data['tipo_sec'] ?? '');
        $proceso = trim($data['proceso'] ?? '');

        if ($almacen_clave === '' || $clave === '' || $nombre === '' || $tipo === '' || $proceso === '') {
            echo json_encode(['ok' => false, 'error' => 'Faltan datos obligatorios'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $procesoValido = ['VENTA', 'REABASTO', 'SURTIDO_INTERNO'];
        if (!in_array(strtoupper($proceso), $procesoValido, true)) {
            echo json_encode(['ok' => false, 'error' => 'Valor de proceso no permitido: ' . $proceso], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = ? LIMIT 1");
        $stmt->execute([$almacen_clave]);
        $almId = $stmt->fetchColumn();

        if (!$almId) {
            echo json_encode(['ok' => false, 'error' => 'No se encontró el almacén en c_almacenp'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "
            INSERT INTO c_secuencia_surtido
                (clave_sec, nombre, tipo_sec, proceso, almacen_id, activo)
            VALUES
                (:clave, :nombre, :tipo, :proceso, :almacen_id, 1)
        ";
        $stmtIns = $pdo->prepare($sql);
        $stmtIns->execute([
            ':clave' => $clave,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':proceso' => $proceso,
            ':almacen_id' => $almId
        ]);

        echo json_encode([
            'ok' => true,
            'sec_id' => (int) $pdo->lastInsertId(),
            'mensaje' => 'Secuencia creada correctamente'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // ACTUALIZAR ENCABEZADO DE SECUENCIA (no usado por vista actual,
    // pero se deja disponible)
    // --------------------------------------------------
    if ($action === 'actualizar') {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $secId = (int) ($data['sec_id'] ?? 0);
        $clave = trim($data['clave_sec'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $tipo = trim($data['tipo_sec'] ?? '');
        $proceso = trim($data['proceso'] ?? '');

        if ($secId <= 0 || $clave === '' || $nombre === '' || $tipo === '' || $proceso === '') {
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos para actualizar'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $procesoValido = ['VENTA', 'REABASTO', 'SURTIDO_INTERNO'];
        if (!in_array(strtoupper($proceso), $procesoValido, true)) {
            echo json_encode(['ok' => false, 'error' => 'Valor de proceso no permitido: ' . $proceso], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "
            UPDATE c_secuencia_surtido
               SET clave_sec = :clave,
                   nombre    = :nombre,
                   tipo_sec  = :tipo,
                   proceso   = :proceso
             WHERE id = :id
               AND activo = 1
        ";
        $stmtUpd = $pdo->prepare($sql);
        $stmtUpd->execute([
            ':clave' => $clave,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':proceso' => $proceso,
            ':id' => $secId
        ]);

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Secuencia actualizado correctamente'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // GUARDAR DETALLE / ORDEN
    // --------------------------------------------------
    if ($action === 'guardar_detalle') {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $secId = (int) ($data['sec_id'] ?? 0);
        $detalle = $data['detalle'] ?? [];

        if ($secId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de secuencia inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $stmtDel = $pdo->prepare("UPDATE c_secuencia_surtido_det SET activo = 0 WHERE sec_id = ?");
        $stmtDel->execute([$secId]);

        if (!empty($detalle)) {
            $stmtIns = $pdo->prepare("
                INSERT INTO c_secuencia_surtido_det
                    (sec_id, ubicacion_id, orden, activo)
                VALUES
                    (:sec_id, :ubicacion_id, :orden, 1)
            ");

            foreach ($detalle as $fila) {
                $ubicacionId = (int) ($fila['ubicacion_id'] ?? 0);
                $orden = (int) ($fila['orden'] ?? 0);

                if ($ubicacionId <= 0 || $orden <= 0) {
                    continue;
                }

                $stmtIns->execute([
                    ':sec_id' => $secId,
                    ':ubicacion_id' => $ubicacionId,
                    ':orden' => $orden
                ]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Detalle guardado correctamente'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // DATOS DE USUARIOS PARA UNA SECUENCIA
    // --------------------------------------------------
    if ($action === 'usuarios_data') {

        $secId = (int) ($_GET['sec_id'] ?? 0);
        if ($secId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de secuencia inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sqlUsers = "
            SELECT id_user, cve_usuario, nombre_completo
            FROM c_usuario
            WHERE Activo = 1 OR Activo IS NULL
            ORDER BY nombre_completo
        ";
        $stmtUsers = $pdo->query($sqlUsers);
        $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $sqlAsig = "
            SELECT usuario_id
            FROM c_secuencia_surtido_usuario
            WHERE sec_id = :sec_id
              AND activo = 1
        ";
        $stmtAsig = $pdo->prepare($sqlAsig);
        $stmtAsig->execute([':sec_id' => $secId]);
        $asignados = $stmtAsig->fetchAll(PDO::FETCH_COLUMN, 0);

        echo json_encode([
            'ok' => true,
            'usuarios' => $usuarios,
            'asignados' => array_map('intval', $asignados)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // GUARDAR USUARIOS DE UNA SECUENCIA
    // --------------------------------------------------
    if ($action === 'guardar_usuarios') {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $secId = (int) ($data['sec_id'] ?? 0);
        $usuarios = $data['usuarios'] ?? [];

        if ($secId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de secuencia inválido'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE c_secuencia_surtido_usuario SET activo = 0 WHERE sec_id = ?")
            ->execute([$secId]);

        if (!empty($usuarios)) {
            $stmtIns = $pdo->prepare("
                INSERT INTO c_secuencia_surtido_usuario
                    (sec_id, usuario_id, activo)
                VALUES
                    (:sec_id, :usuario_id, 1)
            ");

            foreach ($usuarios as $uid) {
                $uid = (int) $uid;
                if ($uid <= 0)
                    continue;

                $stmtIns->execute([
                    ':sec_id' => $secId,
                    ':usuario_id' => $uid
                ]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Usuarios asignados correctamente'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // PREVISUALIZAR IMPORTACIÓN CSV
    // --------------------------------------------------
    if ($action === 'preview_import') {

        if (empty($_FILES['file']['tmp_name'])) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió archivo para previsualizar'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $err = '';
        $rows = ss_parse_csv($tmpPath, $err);

        if ($err !== '') {
            echo json_encode(['ok' => false, 'error' => $err], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $procesoValido = ['VENTA', 'REABASTO', 'SURTIDO_INTERNO'];
        $tipoValido = ['PICKING', 'REABASTO', 'GLOBAL'];

        $cacheAlm = [];
        $cacheUb = [];

        $resultRows = [];

        foreach ($rows as $r) {
            $obs = [];

            if ($r['almacen_clave'] === '') {
                $obs[] = 'Falta ALMACEN_CLAVE';
            }
            if ($r['clave_sec'] === '') {
                $obs[] = 'Falta CLAVE_SECUENCIA';
            }
            if ($r['nombre'] === '') {
                $obs[] = 'Falta NOMBRE_SECUENCIA';
            }
            if (!in_array($r['tipo_sec'], $tipoValido, true)) {
                $obs[] = 'TIPO_SECUENCIA inválido';
            }
            if (!in_array($r['proceso'], $procesoValido, true)) {
                $obs[] = 'PROCESO inválido';
            }
            if ($r['bl'] === '') {
                $obs[] = 'Falta BL';
            }
            if ($r['orden'] === '' || !ctype_digit($r['orden']) || (int) $r['orden'] <= 0) {
                $obs[] = 'ORDEN inválido';
            }

            // validar almacén
            $almId = null;
            if ($r['almacen_clave'] !== '') {
                if (isset($cacheAlm[$r['almacen_clave']])) {
                    $almId = $cacheAlm[$r['almacen_clave']];
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = ? LIMIT 1");
                    $stmt->execute([$r['almacen_clave']]);
                    $almId = $stmt->fetchColumn();
                    $cacheAlm[$r['almacen_clave']] = $almId ?: 0;
                }

                if (!$almId) {
                    $obs[] = 'ALMACEN_CLAVE no existe en c_almacenp';
                }
            }

            // validar BL contra c_ubicacion
            if ($r['bl'] !== '' && $almId) {
                $keyUb = $r['almacen_clave'] . '|' . $r['bl'];
                if (!isset($cacheUb[$keyUb])) {
                    $sqlUb = "
                        SELECT u.idy_ubica
                        FROM c_ubicacion u
                        JOIN c_almacen a  ON a.cve_almac = u.cve_almac
                        JOIN c_almacenp ap ON ap.id = a.cve_almacenp
                        WHERE ap.clave = ?
                          AND u.CodigoCSD = ?
                        LIMIT 1
                    ";
                    $stmtUb = $pdo->prepare($sqlUb);
                    $stmtUb->execute([$r['almacen_clave'], $r['bl']]);
                    $ubId = $stmtUb->fetchColumn();
                    $cacheUb[$keyUb] = $ubId ?: 0;
                }
                if (!$cacheUb[$keyUb]) {
                    $obs[] = 'BL no encontrado en c_ubicacion para el almacén';
                }
            }

            $resultRows[] = [
                'line' => $r['line'],
                'almacen_clave' => $r['almacen_clave'],
                'clave_sec' => $r['clave_sec'],
                'nombre' => $r['nombre'],
                'tipo_sec' => $r['tipo_sec'],
                'proceso' => $r['proceso'],
                'bl' => $r['bl'],
                'orden' => $r['orden'],
                'observacion' => empty($obs) ? 'OK' : implode('. ', $obs)
            ];
        }

        echo json_encode([
            'ok' => true,
            'rows' => $resultRows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --------------------------------------------------
    // REGISTRAR IMPORTACIÓN CSV
    // --------------------------------------------------
    if ($action === 'registrar_import') {

        if (empty($_FILES['file']['tmp_name'])) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió archivo para registrar'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $err = '';
        $rows = ss_parse_csv($tmpPath, $err);

        if ($err !== '') {
            echo json_encode(['ok' => false, 'error' => $err], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $procesoValido = ['VENTA', 'REABASTO', 'SURTIDO_INTERNO'];
        $tipoValido = ['PICKING', 'REABASTO', 'GLOBAL'];

        $cacheAlm = [];
        $cacheUb = [];

        $secuencias = [];  // key: almacen_clave|clave_sec
        $errores = [];

        foreach ($rows as $r) {
            $obs = [];

            if (
                $r['almacen_clave'] === '' ||
                $r['clave_sec'] === '' ||
                $r['nombre'] === '' ||
                $r['tipo_sec'] === '' ||
                $r['proceso'] === '' ||
                $r['bl'] === '' ||
                $r['orden'] === ''
            ) {
                $obs[] = 'Campos obligatorios incompletos';
            }

            if (!in_array($r['tipo_sec'], $tipoValido, true)) {
                $obs[] = 'TIPO_SECUENCIA inválido';
            }
            if (!in_array($r['proceso'], $procesoValido, true)) {
                $obs[] = 'PROCESO inválido';
            }
            if (!ctype_digit($r['orden']) || (int) $r['orden'] <= 0) {
                $obs[] = 'ORDEN inválido';
            }

            // validar almacén
            $almId = null;
            if ($r['almacen_clave'] !== '') {
                if (isset($cacheAlm[$r['almacen_clave']])) {
                    $almId = $cacheAlm[$r['almacen_clave']];
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = ? LIMIT 1");
                    $stmt->execute([$r['almacen_clave']]);
                    $almId = $stmt->fetchColumn();
                    $cacheAlm[$r['almacen_clave']] = $almId ?: 0;
                }

                if (!$almId) {
                    $obs[] = 'ALMACEN_CLAVE no existe en c_almacenp';
                }
            }

            // validar BL contra c_ubicacion
            $ubId = null;
            if ($r['bl'] !== '' && $almId) {
                $keyUb = $r['almacen_clave'] . '|' . $r['bl'];
                if (isset($cacheUb[$keyUb])) {
                    $ubId = $cacheUb[$keyUb];
                } else {
                    $sqlUb = "
                        SELECT u.idy_ubica
                        FROM c_ubicacion u
                        JOIN c_almacen a  ON a.cve_almac = u.cve_almac
                        JOIN c_almacenp ap ON ap.id = a.cve_almacenp
                        WHERE ap.clave = ?
                          AND u.CodigoCSD = ?
                        LIMIT 1
                    ";
                    $stmtUb = $pdo->prepare($sqlUb);
                    $stmtUb->execute([$r['almacen_clave'], $r['bl']]);
                    $ubId = $stmtUb->fetchColumn();
                    $cacheUb[$keyUb] = $ubId ?: 0;
                }
                if (!$ubId) {
                    $obs[] = 'BL no encontrado en c_ubicacion para el almacén';
                }
            }

            if (!empty($obs)) {
                $errores[] = "Línea {$r['line']}: " . implode('. ', $obs);
                continue;
            }

            $key = $r['almacen_clave'] . '|' . $r['clave_sec'];

            if (!isset($secuencias[$key])) {
                $secuencias[$key] = [
                    'almacen_clave' => $r['almacen_clave'],
                    'almacen_id' => $almId,
                    'clave_sec' => $r['clave_sec'],
                    'nombre' => $r['nombre'],
                    'tipo_sec' => $r['tipo_sec'],
                    'proceso' => $r['proceso'],
                    'det' => []
                ];
            }

            $secuencias[$key]['det'][] = [
                'ubicacion_id' => (int) $ubId,
                'orden' => (int) $r['orden']
            ];
        }

        if (!empty($errores)) {
            echo json_encode([
                'ok' => false,
                'error' => 'Se encontraron errores en el archivo. No se realizó la importación.',
                'detalle' => $errores
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($secuencias)) {
            echo json_encode([
                'ok' => false,
                'error' => 'El archivo no contiene registros válidos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Registro en BD
        $pdo->beginTransaction();

        try {

            $stmtBuscaSec = $pdo->prepare("
                SELECT id
                FROM c_secuencia_surtido
                WHERE almacen_id = :almacen_id
                  AND clave_sec   = :clave_sec
                  AND activo      = 1
                LIMIT 1
            ");

            $stmtInsSec = $pdo->prepare("
                INSERT INTO c_secuencia_surtido
                    (clave_sec, nombre, tipo_sec, proceso, almacen_id, activo)
                VALUES
                    (:clave_sec, :nombre, :tipo_sec, :proceso, :almacen_id, 1)
            ");

            $stmtDelDet = $pdo->prepare("
                UPDATE c_secuencia_surtido_det
                   SET activo = 0
                 WHERE sec_id = ?
            ");

            $stmtInsDet = $pdo->prepare("
                INSERT INTO c_secuencia_surtido_det
                    (sec_id, ubicacion_id, orden, activo)
                VALUES
                    (:sec_id, :ubicacion_id, :orden, 1)
            ");

            foreach ($secuencias as $key => $sec) {

                // buscar o crear encabezado
                $stmtBuscaSec->execute([
                    ':almacen_id' => $sec['almacen_id'],
                    ':clave_sec' => $sec['clave_sec']
                ]);
                $secId = $stmtBuscaSec->fetchColumn();

                if (!$secId) {
                    $stmtInsSec->execute([
                        ':clave_sec' => $sec['clave_sec'],
                        ':nombre' => $sec['nombre'],
                        ':tipo_sec' => $sec['tipo_sec'],
                        ':proceso' => $sec['proceso'],
                        ':almacen_id' => $sec['almacen_id']
                    ]);
                    $secId = (int) $pdo->lastInsertId();
                }

                // limpiar detalle previo y registrar nuevo
                $stmtDelDet->execute([$secId]);

                usort($sec['det'], fn($a, $b) => $a['orden'] <=> $b['orden']);

                foreach ($sec['det'] as $d) {
                    if ($d['ubicacion_id'] <= 0 || $d['orden'] <= 0) {
                        continue;
                    }
                    $stmtInsDet->execute([
                        ':sec_id' => $secId,
                        ':ubicacion_id' => $d['ubicacion_id'],
                        ':orden' => $d['orden']
                    ]);
                }
            }

            $pdo->commit();

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Importación registrada correctamente.',
                'secuencias' => count($secuencias)
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            echo json_encode([
                'ok' => false,
                'error' => 'Error al registrar importación: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // --------------------------------------------------
    // Acción desconocida
    // --------------------------------------------------
    echo json_encode([
        'ok' => false,
        'error' => 'Acción no reconocida: ' . $action
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'ok' => false,
        'error' => 'Error general: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
