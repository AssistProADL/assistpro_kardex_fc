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
            $_GET['almacen'] ?? null
        ));
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

function loadZonas(PDO $pdo, $empresa, $almacen): array
{
    if (!$empresa || !$almacen) return [];

    $sql = "
        SELECT
            z.cve_almac,
            z.clave_almacen,
            z.des_almac,
            z.Cve_TipoZona,
            z.clasif_abc,
            z.Activo
        FROM c_almacen z
        INNER JOIN c_almacenp a
            ON z.cve_almacenp = a.id
        WHERE a.cve_cia = :empresa
        AND a.id = :almacen
        AND z.Activo = 1
        ORDER BY z.des_almac
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'empresa' => $empresa,
        'almacen' => $almacen
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}