<?php
header('Content-Type: application/json; charset=utf-8');

/* =========================================================
   CONEXIÓN BD – ESTÁNDAR ASSISTPRO
   db.php vive en /app/db.php al nivel de /public
   ========================================================= */
require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

/* =========================================================
   INPUTS
   ========================================================= */
$run_id    = isset($_GET['run_id']) ? intval($_GET['run_id']) : 0;
$estado    = isset($_GET['estado']) ? strtoupper(trim($_GET['estado'])) : 'ALL';
$page      = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$page_size = isset($_GET['page_size']) ? max(1, min(1000, intval($_GET['page_size']))) : 200;
$q         = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($run_id <= 0) {
    echo json_encode(["ok" => false, "error" => "run_id requerido"]);
    exit;
}

if (!in_array($estado, ['ALL', 'OK', 'ERR'], true)) {
    $estado = 'ALL';
}

$offset = ($page - 1) * $page_size;

try {

    /* =========================================================
       CABECERA DE IMPORTACIÓN + IMPORTADOR
       ========================================================= */
    $sqlRun = "
        SELECT
            r.*,
            i.id_importador,
            i.clave AS imp_clave,
            i.descripcion AS imp_descripcion,
            i.tipo AS imp_tipo,
            i.ruta_api,
            i.permite_rollback,
            i.impacta_kardex_default,
            i.requiere_layout,
            i.requiere_bl_origen,
            i.destino_retencion_obligatorio
        FROM ap_import_runs r
        LEFT JOIN c_importador i
            ON i.clave = r.tipo_ingreso
        WHERE r.id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlRun);
    $stmt->execute([$run_id]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$run) {
        echo json_encode(["ok" => false, "error" => "Corrida no encontrada"]);
        exit;
    }

    /* =========================================================
       CONTEO PARA PAGINACIÓN
       ========================================================= */
    $sqlCount = "
        SELECT COUNT(*) 
        FROM ap_import_run_rows
        WHERE run_id = ?
          AND ( ? = 'ALL' OR estado = ? )
          AND ( ? = '' 
                OR mensaje LIKE CONCAT('%', ?, '%') 
                OR data_json LIKE CONCAT('%', ?, '%') )
    ";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute([$run_id, $estado, $estado, $q, $q, $q]);
    $total_rows = (int)$stmt->fetchColumn();
    $total_pages = (int)ceil(($total_rows > 0 ? $total_rows : 1) / $page_size);

    /* =========================================================
       FILAS
       ========================================================= */
    $sqlRows = "
        SELECT id, linea_num, estado, mensaje, data_json
        FROM ap_import_run_rows
        WHERE run_id = ?
          AND ( ? = 'ALL' OR estado = ? )
          AND ( ? = '' 
                OR mensaje LIKE CONCAT('%', ?, '%') 
                OR data_json LIKE CONCAT('%', ?, '%') )
        ORDER BY linea_num
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sqlRows);
    $stmt->execute([
        $run_id,
        $estado, $estado,
        $q, $q, $q,
        $page_size,
        $offset
    ]);

    $rows = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data = null;
        if ($r['data_json'] !== null && $r['data_json'] !== '') {
            $decoded = json_decode($r['data_json'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        $rows[] = [
            "id"        => (int)$r['id'],
            "linea_num" => (int)$r['linea_num'],
            "estado"    => $r['estado'],
            "mensaje"   => $r['mensaje'],
            "data"      => $data
        ];
    }

    /* =========================================================
       IMPORTADOR (si existe)
       ========================================================= */
    $importador = null;
    if (!empty($run['imp_clave'])) {
        $importador = [
            "clave" => $run['imp_clave'],
            "descripcion" => $run['imp_descripcion'],
            "tipo" => $run['imp_tipo'],
            "ruta_api" => $run['ruta_api'],
            "permite_rollback" => (int)$run['permite_rollback'],
            "impacta_kardex_default" => (int)$run['impacta_kardex_default'],
            "requiere_layout" => (int)$run['requiere_layout'],
            "requiere_bl_origen" => (int)$run['requiere_bl_origen'],
            "destino_retencion_obligatorio" => (int)$run['destino_retencion_obligatorio']
        ];
    }

    /* =========================================================
       RESPUESTA FINAL
       ========================================================= */
    echo json_encode([
        "ok" => true,
        "run" => [
            "id" => (int)$run['id'],
            "folio_importacion" => $run['folio_importacion'],
            "tipo_ingreso" => $run['tipo_ingreso'],
            "importador" => $importador,
            "empresa_id" => $run['empresa_id'],
            "almacen_id" => $run['almacen_id'],
            "usuario" => $run['usuario'],
            "fecha_importacion" => $run['fecha_importacion'],
            "status" => $run['status'],
            "archivo_nombre" => $run['archivo_nombre'],
            "impacto_kardex" => $run['impacto_kardex'],
            "totales" => [
                "total_lineas" => (int)$run['total_lineas'],
                "total_ok" => (int)$run['total_ok'],
                "total_err" => (int)$run['total_err']
            ],
            "error_resumen" => $run['error_resumen']
        ],
        "rows" => $rows,
        "paging" => [
            "page" => $page,
            "page_size" => $page_size,
            "total_rows" => $total_rows,
            "total_pages" => $total_pages
        ]
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "ok" => false,
        "error" => "Error interno",
        "detail" => $e->getMessage()
    ]);
}
