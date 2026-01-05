<?php
// Ruta esperada: /public/api/sfa/clientes_asignacion_data.php
// Devuelve destinatarios/clientes + días planeados desde reldaycli.

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

// Evita que cualquier warning/noticia rompa el JSON
while (ob_get_level()) { @ob_end_clean(); }
ob_start();

try {
    // Ajusta si tu app/db.php está en otra ruta
    require_once __DIR__ . '/../../../app/db.php'; // public/api/sfa -> projectRoot/app/db.php

    // Parámetros aceptados (compatibles)
    $almacen_id = (int)($_GET['almacen_id'] ?? $_GET['almacen'] ?? 0);
    $ruta_id    = (int)($_GET['ruta_id']    ?? $_GET['ruta']    ?? 0);
    $q          = trim((string)($_GET['q'] ?? ''));

    if ($almacen_id <= 0 || $ruta_id <= 0) {
        ob_clean();
        echo json_encode([
            'ok' => 0,
            'error' => 'Parámetros incompletos (almacen_id/ruta_id).',
            'debug' => ['almacen_id' => $almacen_id, 'ruta_id' => $ruta_id]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Estrategia:
    // 1) Base de listado = destinatarios que existan en reldaycli PARA ese almacén+ruta
    //    (porque tu modelo actual no tiene una fuente 100% confiable para "todos los posibles"
    //     sin arriesgar traer miles y duplicar).
    // 2) Se une contra c_cliente y c_destinatarios para nombre/dirección.
    // 3) No rompe JSON aunque falte alguna columna (se reporta en ok:0).

    // OJO: Si quieres listar "todos los posibles" por ruta/almacén, hay que amarrarlo a una tabla
    // tipo RelClirutas (ruta->destinatario). Aquí dejamos el comportamiento seguro y consistente.

    $params = [
        ':alm' => $almacen_id,
        ':rut' => $ruta_id,
    ];

    $whereQ = "";
    if ($q !== '') {
        $whereQ = " AND (
            UPPER(c.Cve_Clte) LIKE :q OR
            UPPER(c.RazonSocial) LIKE :q OR
            UPPER(d.razonsocial) LIKE :q OR
            UPPER(IFNULL(d.colonia,'')) LIKE :q OR
            UPPER(IFNULL(d.cp,'')) LIKE :q
        )";
        $params[':q'] = '%' . mb_strtoupper($q, 'UTF-8') . '%';
    }

    // Lee días ya guardados (incluye Sec si existe en tu tabla; si no existe, quítalo aquí y en save)
    $sql = "
        SELECT
            r.Cve_Cliente,
            c.Cve_Clte,
            c.RazonSocial AS Cliente,
            r.Id_Destinatario,
            d.razonsocial AS Destinatario,

            IFNULL(d.direccion,'') AS Direccion,
            IFNULL(d.colonia,'')   AS Colonia,
            IFNULL(d.cp,'')        AS CP,
            IFNULL(d.ciudad,'')    AS Ciudad,
            IFNULL(d.estado,'')    AS Estado,

            COALESCE(r.Lu,0) AS Lu,
            COALESCE(r.Ma,0) AS Ma,
            COALESCE(r.Mi,0) AS Mi,
            COALESCE(r.Ju,0) AS Ju,
            COALESCE(r.Vi,0) AS Vi,
            COALESCE(r.Sa,0) AS Sa,
            COALESCE(r.Do,0) AS Do,

            1 AS Asignado,
            COALESCE(r.Sec,'') AS Sec
        FROM reldaycli r
        LEFT JOIN c_cliente c ON c.id_cliente = r.Cve_Cliente
        LEFT JOIN c_destinatarios d ON d.id_destinatario = r.Id_Destinatario
        WHERE r.Cve_Almac = :alm
          AND r.Cve_Ruta  = :rut
          $whereQ
        ORDER BY c.RazonSocial, d.razonsocial, r.Id DESC
        LIMIT 1000
    ";

    $rows = db_all($sql, $params);

    // Normaliza tipos
    foreach ($rows as &$it) {
        $it['Lu'] = (int)$it['Lu'];
        $it['Ma'] = (int)$it['Ma'];
        $it['Mi'] = (int)$it['Mi'];
        $it['Ju'] = (int)$it['Ju'];
        $it['Vi'] = (int)$it['Vi'];
        $it['Sa'] = (int)$it['Sa'];
        $it['Do'] = (int)$it['Do'];
        $it['Asignado'] = (int)$it['Asignado'];
    }
    unset($it);

    ob_clean();
    echo json_encode([
        'ok' => 1,
        'items' => $rows,
        'count' => count($rows),
        'debug' => [
            'almacen_id' => $almacen_id,
            'ruta_id' => $ruta_id,
            'q' => $q,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_clean();
    echo json_encode([
        'ok' => 0,
        'error' => 'Error consultando clientes.',
        'detalle' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
