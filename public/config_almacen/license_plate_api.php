<?php
// public/config_almacen/license_plate_api.php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jexit($arr)
{
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$draw = (int) ($_GET['draw'] ?? 1);
$start = max(0, (int) ($_GET['start'] ?? 0));
$length = (int) ($_GET['length'] ?? 25);
if ($length <= 0 || $length > 200)
    $length = 25;

// DataTables search
$searchValue = trim((string) ($_GET['search']['value'] ?? ''));

// Filtros funcionales
$lp_filtro = trim((string) ($_GET['lp'] ?? ''));
$almacen_f = trim((string) ($_GET['almacen'] ?? ''));
$zona_f = trim((string) ($_GET['zona'] ?? ''));
$tipogen_f = trim((string) ($_GET['tipogen'] ?? ''));
$tipo_f = trim((string) ($_GET['tipo'] ?? ''));
$statuslp_f = trim((string) ($_GET['statuslp'] ?? ''));
$activo_f = trim((string) ($_GET['activo'] ?? ''));

/**
 * Regla anti “legacy freeze”:
 */
$lp_gate = $lp_filtro !== '' ? $lp_filtro : $searchValue;
if ($almacen_f === '' && mb_strlen($lp_gate) < 2) {
    jexit([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'warning' => 'Para performance, seleccione un Almacén o capture al menos 2 caracteres en LP.'
    ]);
}

// ORDER
$orderCol = (int) ($_GET['order'][0]['column'] ?? 0);
$orderDir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

// Mapa columnas DataTables -> SQL
$cols = [
    0 => 'ap.clave',
    1 => 'ca.des_almac',
    2 => 'u.CodigoCSD',
    3 => 'ch.descripcion',
    4 => 'ch.CveLP',
    5 => 'ch.tipo',
    6 => 'ch.Permanente',
    7 => 'existencia_total'
];
$orderBy = $cols[$orderCol] ?? 'ch.CveLP';

// ==========================================
// ESTRATEGIA "DUAL QUERY UNION" (PHP MERGE)
// ==========================================

$useRamPath = ($almacen_f !== '' && $lp_filtro === '' && $searchValue === '');

if ($useRamPath) {
    try {
        $idsA = [];
        $idsB = [];

        // FIX: Expandir Almacén Padre -> Hijos (Zonas)
        // El filtro viene como ID de c_almacenp, pero ts_existenciatarima usa cve_almac (zona)
        $sqlHijos = "SELECT cve_almac FROM c_almacen WHERE CAST(cve_almacenp AS CHAR) = :padre";
        $stH = db_pdo()->prepare($sqlHijos);
        $stH->execute([':padre' => $almacen_f]);
        $hijos = $stH->fetchAll(PDO::FETCH_COLUMN);

        // Si no tiene hijos (o es un ID directo), incluyamos el propio ID por si acaso
        $hijos[] = $almacen_f;
        $hijos = array_unique($hijos);

        // Sanitize ints para IN clause segura
        $hijosSafe = implode(',', array_map('intval', $hijos));

        // 1. Query A: Existencias Físicas (Rápido por index cve_almac IN (...))
        $sqlA = "SELECT DISTINCT ntarima FROM ts_existenciatarima WHERE cve_almac IN ($hijosSafe)";
        if ($activo_f === '1')
            $sqlA .= " AND existencia > 0";
        if ($activo_f === '0')
            $sqlA .= " AND existencia <= 0";

        $st = db_pdo()->query($sqlA);
        $idsA = $st->fetchAll(PDO::FETCH_COLUMN);

        // 2. Query B: Home (Solo si queremos ver inactivos/vacíos o todos)
        if ($activo_f !== '1') {
            $sqlB = "SELECT IDContenedor FROM c_charolas WHERE cve_almac IN ($hijosSafe)";
            if ($activo_f === '0')
                $sqlB .= " AND (Activo = 0)";

            $st = db_pdo()->query($sqlB);
            $idsB = $st->fetchAll(PDO::FETCH_COLUMN);
        }

        // 3. Merge en PHP
        $allIds = array_unique(array_merge($idsA, $idsB));

        // 4. Paginación
        $recordsFiltered = count($allIds);
        $recordsTotal = $recordsFiltered;

        $pageIds = array_slice($allIds, $start, $length);

        if (!empty($pageIds)) {
            $idsStr = implode(',', array_map('intval', $pageIds));

            // 5. Query Detalle
            $from = "
FROM c_charolas ch
LEFT JOIN ts_existenciatarima et ON et.ntarima = ch.IDContenedor
LEFT JOIN c_ubicacion u ON u.idy_ubica = et.idy_ubica
LEFT JOIN c_almacen ca  ON ca.cve_almac = u.cve_almac
LEFT JOIN c_almacenp ap ON ap.id = ca.cve_almacenp

LEFT JOIN c_almacen ca_home ON ca_home.cve_almac = ch.cve_almac
LEFT JOIN c_almacenp ap_home ON ap_home.id = ca_home.cve_almacenp
";



            $sqlData = "
                SELECT
                    COALESCE(
    MAX(ap.clave),
    MAX(ap_home.clave)
) AS clave_almacenp,

COALESCE(
    MAX(ap.nombre),
    MAX(ap_home.nombre)
) AS nombre_almacen,

COALESCE(
    MAX(ca.des_almac),
    MAX(ca_home.des_almac)
) AS zona_almacenaje,

                    MAX(CASE WHEN et.existencia > 0 THEN u.CodigoCSD ELSE u.CodigoCSD END)      AS CodigoCSD,
                    ch.descripcion,
                    ch.Clave_Contenedor,
                    ch.Permanente,
                    ch.Activo,
                    ch.tipo,
                    ch.CveLP,
                    ch.TipoGen,
                    ch.IDContenedor,
                    COALESCE(SUM(et.existencia),0) AS existencia_total
                {$from}
                WHERE ch.IDContenedor IN ({$idsStr})
                GROUP BY ch.IDContenedor, ch.CveLP, ch.descripcion, ch.Clave_Contenedor, ch.Permanente, ch.Activo, ch.tipo, ch.TipoGen
                ORDER BY {$orderBy} {$orderDir}
            ";
            $rows = db_all($sqlData);
        } else {
            $rows = [];
        }
    } catch (Exception $e) {
        $rows = []; // Fallback seguro
    }
} else {
    // FALLBACK STANDARD (Si hay búsqueda global)
    // Reconstruir WHERE standard
    $where = [];
    $params = [];
    $where[] = "COALESCE(ch.CveLP,'') <> ''";
    if ($lp_filtro !== '') {
        $where[] = "ch.CveLP LIKE :lp";
        $params['lp'] = "%{$lp_filtro}%";
    }
    if ($searchValue !== '') {
        $where[] = "(ch.CveLP LIKE :s1 OR ch.Clave_Contenedor LIKE :s2 OR ch.descripcion LIKE :s3 OR u.CodigoCSD LIKE :s4)";
        $params['s1'] = "%{$searchValue}%";
        $params['s2'] = "%{$searchValue}%";
        $params['s3'] = "%{$searchValue}%";
        $params['s4'] = "%{$searchValue}%";
    }

    // FIX hierarchy here too
    if ($almacen_f !== '') {
        // Same hierarchy fix for Standard Query
        $sqlHijos = "SELECT cve_almac FROM c_almacen WHERE CAST(cve_almacenp AS CHAR) = :padre";
        $stH = db_pdo()->prepare($sqlHijos);
        $stH->execute([':padre' => $almacen_f]);
        $hijos = $stH->fetchAll(PDO::FETCH_COLUMN);
        $hijos[] = $almacen_f;
        $hijos = array_unique($hijos);
        $hijosSafe = implode(',', array_map('intval', $hijos));

        $where[] = "( 
            (et.cve_almac IN ($hijosSafe)) 
            OR 
            (et.cve_almac IS NULL AND ch.cve_almac IN ($hijosSafe)) 
            OR 
            (COALESCE(ch.Activo,1)=0 AND ch.cve_almac IN ($hijosSafe)) 
         )";
    }

    if ($zona_f !== '') {
        $where[] = "ca.des_almac = :zona";
        $params['zona'] = $zona_f;
    }
    if ($tipogen_f === 'G')
        $where[] = "ch.TipoGen = 1";
    if ($tipogen_f === 'N')
        $where[] = "ch.TipoGen = 0";
    if ($tipo_f !== '') {
        $where[] = "ch.tipo = :tipo";
        $params['tipo'] = $tipo_f;
    }
    if ($statuslp_f !== '') {
        $where[] = "ch.Permanente = :perm";
        $params['perm'] = $statuslp_f;
    }
    if ($activo_f === '1') {
        $where[] = "COALESCE(ch.Activo,1) = 1 AND et.existencia > 0";
    } elseif ($activo_f === '0') {
        $where[] = "(COALESCE(ch.Activo,1) = 0 OR et.ntarima IS NULL OR et.existencia <= 0)";
    }

    $whereSql = implode(' AND ', $where);

    $from = "
    FROM c_charolas ch
    LEFT JOIN ts_existenciatarima et ON et.ntarima = ch.IDContenedor
    LEFT JOIN c_ubicacion u ON u.idy_ubica = et.idy_ubica
    LEFT JOIN c_almacen ca  ON ca.cve_almac = u.cve_almac
    LEFT JOIN c_almacenp ap ON ap.id = ca.cve_almacenp

    LEFT JOIN c_almacen ca_home ON ca_home.cve_almac = ch.cve_almac
    LEFT JOIN c_almacenp ap_home ON CAST(ap_home.id AS UNSIGNED) = ca_home.cve_almacenp
    ";

    $sqlCount = "SELECT COUNT(DISTINCT ch.IDContenedor) AS c {$from} WHERE {$whereSql}";
    $recordsFiltered = (int) db_val($sqlCount, $params);
    $recordsTotal = $recordsFiltered;

    $sqlData = "
        SELECT
            COALESCE(
    MAX(ap.clave),
    MAX(ap_home.clave)
) AS clave_almacenp,

COALESCE(
    MAX(ap.nombre),
    MAX(ap_home.nombre)
) AS nombre_almacen,

COALESCE(
    MAX(ca.des_almac),
    MAX(ca_home.des_almac)
) AS zona_almacenaje,

            MAX(CASE WHEN et.existencia > 0 THEN u.CodigoCSD ELSE u.CodigoCSD END)      AS CodigoCSD,
            ch.descripcion,
            ch.Clave_Contenedor,
            ch.Permanente,
            ch.Activo,
            ch.tipo,
            ch.CveLP,
            ch.TipoGen,
            ch.IDContenedor,
            COALESCE(SUM(et.existencia),0) AS existencia_total
        {$from}
        WHERE {$whereSql}
        GROUP BY ch.IDContenedor, ch.CveLP, ch.descripcion, ch.Clave_Contenedor, ch.Permanente, ch.Activo, ch.tipo, ch.TipoGen
        ORDER BY {$orderBy} {$orderDir}
        LIMIT {$length} OFFSET {$start}
    ";
    $rows = db_all($sqlData, $params);
}

// Formato final
$data = [];
foreach ($rows as $r) {
    $nomAlm = trim((string) ($r['nombre_almacen'] ?? ''));
    $claAlm = trim((string) ($r['clave_almacenp'] ?? ''));
    $almLabel = trim($claAlm . ' - ' . $nomAlm);

    $zona = trim((string) ($r['zona_almacenaje'] ?? ''));
    $bl = trim((string) ($r['CodigoCSD'] ?? ''));
    if ($bl === '')
        $bl = '-';

    $desc = trim((string) ($r['descripcion'] ?? ''));
    $lp = trim((string) ($r['CveLP'] ?? ''));
    $tipo = trim((string) ($r['tipo'] ?? ''));
    $contenedor = trim((string) ($r['Clave_Contenedor'] ?? ''));
    $perm = (int) ($r['Permanente'] ?? 0);
    $itemActivo = (int) ($r['Activo'] ?? 0);
    $existencia = (float) ($r['existencia_total'] ?? 0);

    $data[] = [
        'almacen' => $almLabel,
        'zona' => $zona,
        'bl' => $bl,
        'descripcion' => $desc,
        'lp' => $lp,
        'tipo' => $tipo,
        'contenedor' => $contenedor,
        'permanente' => $perm,
        'activo_flag' => $itemActivo,
        'existencia_total' => $existencia
    ];
}

jexit([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);
