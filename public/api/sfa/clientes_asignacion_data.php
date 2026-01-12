<?php
// api/sfa/clientes_asignacion_data.php
// Devuelve destinatarios para planeación:
// - La grilla NO se filtra por ruta (un destinatario puede estar en varias)
// - Se calcula: rutas actuales (por almacén) y si está asignado a la ruta seleccionada

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

try {
    $almacen_id = isset($_GET['almacen_id']) ? (int)$_GET['almacen_id'] : 0;
    $ruta_id    = isset($_GET['ruta_id']) ? (int)$_GET['ruta_id'] : 0;
    $q          = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

    if ($almacen_id <= 0) {
        echo json_encode([
            'ok' => 0,
            'error' => 'almacen_id requerido'
        ]);
        exit;
    }

    // Para evitar HY093 por reuso de parámetros nombrados (PDO), inyectamos ints ya casteados.
    // (Son valores controlados por UI y casteados a int.)
    $A = $almacen_id;
    $R = $ruta_id;

    $params = [];
    $whereQ = '';
    if ($q !== '') {
        $whereQ = " AND (d.razonsocial LIKE :q OR d.Cve_Clte LIKE :q OR d.colonia LIKE :q OR d.postal LIKE :q) ";
        $params['q'] = '%' . $q . '%';
    }

    $sql = "
        SELECT
            d.id_destinatario,
            d.Cve_Clte,
            d.razonsocial,
            d.direccion,
            d.colonia,
            d.postal AS cp,
            d.ciudad,
            d.estado,
            d.latitud AS lat,
            d.longitud AS lng,

            -- Rutas actuales del destinatario (por almacén)
            (
                SELECT GROUP_CONCAT(DISTINCT CONCAT(r2.cve_ruta) ORDER BY r2.cve_ruta SEPARATOR ', ')
                FROM reldaycli rdc2
                JOIN t_ruta r2 ON r2.ID_Ruta = rdc2.Cve_Ruta
                WHERE rdc2.Id_Destinatario = d.id_destinatario
                  AND rdc2.Cve_Almac = {$A}
            ) AS rutas_actuales,

            -- Bandera si está asignado a la ruta seleccionada (en este almacén)
            (
                SELECT COUNT(*)
                FROM reldaycli rdc3
                WHERE rdc3.Id_Destinatario = d.id_destinatario
                  AND rdc3.Cve_Almac = {$A}
                  AND ({$R} = 0 OR rdc3.Cve_Ruta = {$R})
            ) AS asignado_esta_ruta,

            -- Días actuales (si existe registro para ruta seleccionada; si ruta_id=0, toma cualquiera del almacén)
            (
                SELECT CONCAT(
                    IFNULL(rdc4.Lu,0), IFNULL(rdc4.Ma,0), IFNULL(rdc4.Mi,0), IFNULL(rdc4.Ju,0),
                    IFNULL(rdc4.Vi,0), IFNULL(rdc4.Sa,0), IFNULL(rdc4.Do,0)
                )
                FROM reldaycli rdc4
                WHERE rdc4.Id_Destinatario = d.id_destinatario
                  AND rdc4.Cve_Almac = {$A}
                  AND ({$R} = 0 OR rdc4.Cve_Ruta = {$R})
                ORDER BY rdc4.Id DESC
                LIMIT 1
            ) AS dias_bits

        FROM c_destinatarios d
        WHERE d.Activo = '1'
        {$whereQ}
        ORDER BY d.Cve_Clte, d.razonsocial
        LIMIT 1000
    ";

    $rows = db_all($sql, $params);

    // Normaliza tipos
    foreach ($rows as &$r) {
        $r['id_destinatario'] = (int)$r['id_destinatario'];
        $r['asignado_esta_ruta'] = (int)$r['asignado_esta_ruta'];
        $r['lat'] = ($r['lat'] === null || $r['lat'] === '') ? null : (float)$r['lat'];
        $r['lng'] = ($r['lng'] === null || $r['lng'] === '') ? null : (float)$r['lng'];
        $r['dias_bits'] = $r['dias_bits'] ?? '';
        $r['rutas_actuales'] = $r['rutas_actuales'] ?? '';
    }

    echo json_encode([
        'ok' => 1,
        'almacen_id' => $almacen_id,
        'ruta_id' => $ruta_id,
        'q' => $q,
        'total' => count($rows),
        'data' => $rows
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'ok' => 0,
        'error' => 'Error consultando clientes',
        'detalle' => $e->getMessage()
    ]);
}
