<?php
// public/sfa/planeacion_rutas_data.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

try {

    switch ($action) {

        case 'rutas_por_almacen':
            $almacen = trim((string)($_GET['almacen'] ?? ''));
            $sql = "
                SELECT r.ID_Ruta, r.cve_ruta, r.descripcion
                FROM t_ruta r
                LEFT JOIN c_almacenp a ON a.id = r.cve_almacenp
                WHERE IFNULL(r.Activo,1) = 1
            ";
            $params = [];
            if ($almacen !== '') {
                $sql .= " AND a.clave = ? ";
                $params[] = $almacen;
            }
            $sql .= " ORDER BY r.cve_ruta";
            $rows = db_all($sql, $params);

            echo json_encode([
                'success' => true,
                'rows' => $rows,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'agentes_por_ruta':
            $rutaId = (int)($_GET['ruta_id'] ?? 0);
            if ($rutaId <= 0) {
                echo json_encode(['success' => true, 'rows' => []]);
                exit;
            }

            $sql = "
                SELECT v.Id_Vendedor, v.Cve_Vendedor, v.Nombre
                FROM relvendrutas rv
                JOIN t_vendedores v ON v.Id_Vendedor = rv.IdVendedor
                WHERE rv.IdRuta = ?
                ORDER BY v.Nombre
            ";
            $rows = db_all($sql, [$rutaId]);

            echo json_encode([
                'success' => true,
                'rows' => $rows,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        case 'listar':
        default:
            listarPlaneacion();
            exit;
    }

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error en servidor: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lista clientes/destinatarios planeados por ruta/día, usando:
 * - reldaycli
 * - c_destinatarios
 * - c_cliente
 * - t_ruta
 * - t_vendedores (opcional)
 */
function listarPlaneacion(): void
{
    $almacen      = trim((string)($_GET['almacen'] ?? ''));
    $cp           = trim((string)($_GET['cp'] ?? ''));
    $rutaId       = (int)($_GET['ruta_id'] ?? 0);
    $agenteId     = (int)($_GET['agente_id'] ?? 0);
    $dia          = strtoupper(trim((string)($_GET['dia'] ?? '')));
    $soloEntregas = (int)($_GET['solo_entregas'] ?? 0);
    $criterio     = trim((string)($_GET['criterio'] ?? ''));

    $where  = [];
    $params = [];

    if ($almacen !== '') {
        $where[]  = 'rdc.Cve_Almac = ?';
        $params[] = $almacen;
    }

    if ($rutaId > 0) {
        $where[]  = 'r.ID_Ruta = ?';
        $params[] = $rutaId;
    }

    if ($agenteId > 0) {
        $where[]  = 'v.Id_Vendedor = ?';
        $params[] = $agenteId;
    }

    $colDiaMap = [
        'LUN' => 'Lu',
        'MAR' => 'Ma',
        'MIE' => 'Mi',
        'JUE' => 'Ju',
        'VIE' => 'Vi',
        'SAB' => 'Sa',
        'DOM' => 'Do',
    ];
    if ($dia !== '' && isset($colDiaMap[$dia])) {
        $col    = $colDiaMap[$dia];
        $where[] = "IFNULL(rdc.`$col`,0) = 1";
    }

    if ($cp !== '') {
        $where[]  = 'd.postal = ?';
        $params[] = $cp;
    }

    if ($criterio !== '') {
        $like = '%' . $criterio . '%';
        $where[] = '('
            . 'c.Cve_Clte LIKE ? OR '
            . 'c.RazonSocial LIKE ? OR '
            . 'c.RazonComercial LIKE ? OR '
            . 'd.razonsocial LIKE ? OR '
            . 'd.clave_destinatario LIKE ?'
            . ')';
        array_push($params, $like, $like, $like, $like, $like);
    }

    if ($soloEntregas === 1) {
        // Suposición: 2 = rutas de entrega en t_ruta. Ajustable.
        $where[] = 'r.venta_preventa = 2';
    }

    $sql = "
        SELECT
            rdc.Id                AS id_reldaycli,
            rdc.Id_Destinatario   AS id_destinatario,
            rdc.Cve_Ruta,
            rdc.Cve_Cliente,
            rdc.Cve_Vendedor,
            rdc.Cve_Almac,
            rdc.Lu, rdc.Ma, rdc.Mi, rdc.Ju, rdc.Vi, rdc.Sa, rdc.Do,
            r.cve_ruta,
            r.descripcion         AS ruta_descripcion,
            c.Cve_Clte,
            c.RazonComercial,
            c.RazonSocial,
            d.clave_destinatario,
            d.razonsocial         AS destinatario,
            d.direccion,
            d.colonia,
            d.postal,
            d.ciudad,
            d.estado              AS municipio
        FROM reldaycli rdc
        JOIN c_destinatarios d ON d.id_destinatario = rdc.Id_Destinatario
        JOIN c_cliente c       ON c.Cve_Clte       = rdc.Cve_Cliente
        JOIN t_ruta r          ON r.cve_ruta       = rdc.Cve_Ruta
        LEFT JOIN t_vendedores v ON v.Cve_Vendedor = rdc.Cve_Vendedor
    ";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= '
        ORDER BY r.cve_ruta, c.Cve_Clte, d.clave_destinatario
    ';

    $rows = db_all($sql, $params);

    // KPIs
    $clientesUnicos = [];
    $rutaClientes   = [];
    $diaClientes    = [];

    foreach ($rows as $r) {
        $cveClte = (string)($r['Cve_Clte'] ?? '');
        $cveRuta = (string)($r['cve_ruta'] ?? '');

        if ($cveClte !== '') {
            $clientesUnicos[$cveClte] = true;
            $rutaClientes[$cveRuta . '|' . $cveClte] = true;
        }

        // Clientes por día
        if ($dia !== '' && isset($colDiaMap[$dia])) {
            $col = $colDiaMap[$dia];
            if (!empty($r[$col])) {
                $diaClientes[$cveClte] = true;
            }
        } else {
            // Si no hay día filtrado: cualquier día activo cuenta
            if (
                !empty($r['Lu']) || !empty($r['Ma']) || !empty($r['Mi']) ||
                !empty($r['Ju']) || !empty($r['Vi']) || !empty($r['Sa']) ||
                !empty($r['Do'])
            ) {
                $diaClientes[$cveClte] = true;
            }
        }
    }

    // Preparar salida "bonita" para front
    $outRows = [];
    foreach ($rows as $r) {
        $outRows[] = [
            'id_reldaycli'     => (int)$r['id_reldaycli'],
            'id_destinatario'  => (int)$r['id_destinatario'],
            'cve_ruta'         => $r['cve_ruta'],
            'ruta_descripcion' => $r['ruta_descripcion'],
            'Cve_Clte'         => $r['Cve_Clte'],
            'RazonComercial'   => $r['RazonComercial'],
            'RazonSocial'      => $r['RazonSocial'],
            'clave_destinatario' => $r['clave_destinatario'],
            'destinatario'     => $r['destinatario'],
            'direccion'        => $r['direccion'],
            'colonia'          => $r['colonia'],
            'postal'           => $r['postal'],
            'ciudad'           => $r['ciudad'],
            'municipio'        => $r['municipio'],
            // Por ahora usamos el Id de reldaycli como proxy de secuencia
            // En la siguiente fase lo cambiamos por la tabla/campo de secuencia real.
            'secuencia'        => (int)$r['id_reldaycli'],
            'Lu'               => (int)($r['Lu'] ?? 0),
            'Ma'               => (int)($r['Ma'] ?? 0),
            'Mi'               => (int)($r['Mi'] ?? 0),
            'Ju'               => (int)($r['Ju'] ?? 0),
            'Vi'               => (int)($r['Vi'] ?? 0),
            'Sa'               => (int)($r['Sa'] ?? 0),
            'Do'               => (int)($r['Do'] ?? 0),
        ];
    }

    echo json_encode([
        'success'            => true,
        'total_clientes'     => count($clientesUnicos),
        'clientes_por_ruta'  => count($rutaClientes),
        'clientes_por_dia'   => count($diaClientes),
        'rows'               => $outRows,
    ], JSON_UNESCAPED_UNICODE);
}
