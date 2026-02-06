<?php
require_once __DIR__ . '/../_base.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Helpers
 */
function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function ok($data = null) {
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

/**
 * ==========================
 * GET → LISTADO
 * ==========================
 */
if ($method === 'GET') {

    $empresa_id = (int)($_GET['empresa_id'] ?? 0);
    $modulo     = trim($_GET['modulo'] ?? '');

    if ($empresa_id <= 0 || $modulo === '') {
        fail('Parámetros inválidos');
    }

    $sql = "
        SELECT id, codigo, nombre, descripcion, requiere_folio, activo
        FROM c_tipo_movimiento_doc
        WHERE empresa_id = :empresa_id
          AND modulo = :modulo
        ORDER BY nombre
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':empresa_id' => $empresa_id,
        ':modulo'     => $modulo
    ]);

    ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * ==========================
 * POST → CREATE / UPDATE / TOGGLE
 * ==========================
 */
if ($method === 'POST') {

    $empresa_id = (int)($_POST['empresa_id'] ?? 0);
    $modulo     = trim($_POST['modulo'] ?? '');
    $codigo     = trim($_POST['codigo'] ?? '');
    $accion     = $_POST['accion'] ?? '';

    if ($empresa_id <= 0 || $modulo === '' || $codigo === '') {
        fail('Datos obligatorios faltantes');
    }

    /**
     * TOGGLE / UPDATE ESTADO
     */
    if ($accion === 'toggle' || $accion === 'update') {

        if (!isset($_POST['activo'])) {
            fail('Estado no especificado');
        }

        $activo = (int)$_POST['activo'];

        $sql = "
            UPDATE c_tipo_movimiento_doc
            SET activo = :activo
            WHERE empresa_id = :empresa_id
              AND modulo = :modulo
              AND codigo = :codigo
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':activo'     => $activo,
            ':empresa_id' => $empresa_id,
            ':modulo'     => $modulo,
            ':codigo'     => $codigo
        ]);

        ok();
    }

    /**
     * CREATE
     */
    if ($accion === 'create') {

        $nombre         = trim($_POST['nombre'] ?? '');
        $descripcion    = trim($_POST['descripcion'] ?? '');
        $requiere_folio = (int)($_POST['requiere_folio'] ?? 0);
        $activo         = (int)($_POST['activo'] ?? 1);

        if ($nombre === '') {
            fail('Nombre obligatorio');
        }

        // Evitar duplicados
        $check = $pdo->prepare("
            SELECT 1
            FROM c_tipo_movimiento_doc
            WHERE empresa_id = :empresa_id
              AND modulo = :modulo
              AND codigo = :codigo
        ");
        $check->execute([
            ':empresa_id' => $empresa_id,
            ':modulo'     => $modulo,
            ':codigo'     => $codigo
        ]);

        if ($check->fetch()) {
            fail('El código ya existe');
        }

        $sql = "
            INSERT INTO c_tipo_movimiento_doc
            (empresa_id, modulo, codigo, nombre, descripcion, requiere_folio, activo)
            VALUES
            (:empresa_id, :modulo, :codigo, :nombre, :descripcion, :requiere_folio, :activo)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id'     => $empresa_id,
            ':modulo'         => $modulo,
            ':codigo'         => $codigo,
            ':nombre'         => $nombre,
            ':descripcion'    => $descripcion,
            ':requiere_folio' => $requiere_folio,
            ':activo'         => $activo
        ]);

        ok();
    }

    fail('Acción no soportada');
}

fail('Método no permitido', 405);
