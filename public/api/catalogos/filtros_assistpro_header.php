<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'empresas':
        echo json_encode(loadEmpresas($pdo));
        break;

    case 'almacenes':
        echo json_encode(loadAlmacenes(
            $pdo,
            $_GET['empresa'] ?? null
        ));
        break;

    case 'zonas':
        echo json_encode(loadZonas(
            $pdo,
            $_GET['empresa'] ?? null,
            $_GET['almacen'] ?? null,
            $_GET['estado'] ?? 1
        ));
        break;

    case 'guardar_zona':
        echo json_encode(guardarZona($pdo));
        break;

    case 'cambiar_estado':
        echo json_encode(cambiarEstado($pdo));
        break;

    default:
        echo json_encode([]);
}



/* =========================================================
   EMPRESAS
========================================================= */

function loadEmpresas(PDO $pdo): array
{
    $sql = "
        SELECT 
            cve_cia,
            des_cia,
            clave_empresa
        FROM c_compania
        WHERE Activo = 1
        ORDER BY des_cia
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}



/* =========================================================
   ALMACENES
========================================================= */

function loadAlmacenes(PDO $pdo, $empresa): array
{
    if (!$empresa) return [];

    $sql = "
        SELECT
            id,
            clave,
            nombre
        FROM c_almacenp
        WHERE cve_cia = :empresa
        AND Activo = 1
        ORDER BY nombre
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empresa' => $empresa]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



/* =========================================================
   ZONAS
========================================================= */

function loadZonas(PDO $pdo, $empresa, $almacen, $estado): array
{
    if (!$empresa || !$almacen) return [];

    $sql = "
        SELECT
            z.cve_almac,
            z.clave_almacen,
            z.des_almac,
            z.Activo
        FROM c_almacen z
        INNER JOIN c_almacenp a
            ON z.cve_almacenp = a.id
        WHERE a.cve_cia = :empresa
        AND a.id = :almacen
        AND z.Activo = :estado
        ORDER BY z.des_almac
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'empresa' => $empresa,
        'almacen' => $almacen,
        'estado'  => (int)$estado
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



/* =========================================================
   GUARDAR / EDITAR ZONA
========================================================= */

function guardarZona(PDO $pdo): array
{
    $id         = $_POST['id'] ?? null;
    $almacen    = $_POST['almacen'] ?? null;
    $clave      = $_POST['clave'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if (!$almacen || !$clave || !$descripcion) {
        return ['ok' => false, 'msg' => 'Datos incompletos'];
    }

    // 🔒 Normalización fuerte en backend
    $clave = strtoupper($clave);
    $clave = preg_replace('/\s+/', '', $clave);
    $clave = preg_replace('/[^A-Z0-9]/', '', $clave);

    try {

        // 🔍 Validar duplicado por almacén
        $sqlDup = "
            SELECT COUNT(*) 
            FROM c_almacen
            WHERE cve_almacenp = :almacen
            AND clave_almacen = :clave
            AND (:id IS NULL OR cve_almac != :id)
        ";

        $stmtDup = $pdo->prepare($sqlDup);
        $stmtDup->execute([
            'almacen' => $almacen,
            'clave'   => $clave,
            'id'      => $id
        ]);

        if ($stmtDup->fetchColumn() > 0) {
            return ['ok' => false, 'msg' => 'La clave ya existe en este almacén'];
        }

        if ($id) {
            // UPDATE
            $sql = "
                UPDATE c_almacen
                SET clave_almacen = :clave,
                    des_almac = :descripcion
                WHERE cve_almac = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'clave' => $clave,
                'descripcion' => $descripcion,
                'id' => $id
            ]);
        } else {
            // INSERT
            $sql = "
                INSERT INTO c_almacen
                (clave_almacen, des_almac, cve_almacenp, Activo)
                VALUES
                (:clave, :descripcion, :almacen, 1)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'clave' => $clave,
                'descripcion' => $descripcion,
                'almacen' => $almacen
            ]);
        }

        return ['ok' => true];

    } catch (Throwable $e) {
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}



/* =========================================================
   ACTIVAR / INACTIVAR
========================================================= */

function cambiarEstado(PDO $pdo): array
{
    $id = $_POST['id'] ?? null;
    $estado = $_POST['estado'] ?? null;

    if (!$id || !isset($estado)) {
        return ['ok' => false];
    }

    try {

        $sql = "
            UPDATE c_almacen
            SET Activo = :estado
            WHERE cve_almac = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'estado' => (int)$estado,
            'id' => $id
        ]);

        return ['ok' => true];

    } catch (Throwable $e) {
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}