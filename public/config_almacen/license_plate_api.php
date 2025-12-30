<?php
// public/config_almacen/license_plate_api.php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$draw   = (int)($_GET['draw'] ?? 1);
$start  = max(0, (int)($_GET['start'] ?? 0));
$length = (int)($_GET['length'] ?? 25);
if ($length <= 0 || $length > 200) $length = 25;

// DataTables search
$searchValue = trim((string)($_GET['search']['value'] ?? ''));

// Filtros funcionales (vienen del form)
$lp_filtro  = trim((string)($_GET['lp'] ?? ''));
$almacen_f  = trim((string)($_GET['almacen'] ?? ''));     // c_almacenp.id (ojo: puede ser TEXT)
$zona_f     = trim((string)($_GET['zona'] ?? ''));        // ca.des_almac
$tipogen_f  = trim((string)($_GET['tipogen'] ?? ''));     // G | N | ''
$tipo_f     = trim((string)($_GET['tipo'] ?? ''));        // Pallet | Contenedor | ''
$statuslp_f = trim((string)($_GET['statuslp'] ?? ''));    // 1 | 0 | ''
$activo_f   = trim((string)($_GET['activo'] ?? ''));      // 1 | 0 | ''

/**
 * Regla anti “legacy freeze”:
 * Si no hay almacén y no hay LP parcial (>=2 chars), NO ejecutamos el monstruo.
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
$orderCol = (int)($_GET['order'][0]['column'] ?? 0);
$orderDir = strtolower((string)($_GET['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

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

// WHERE base
$where = [];
$params = [];

// Base: LP no vacío
$where[] = "COALESCE(ch.CveLP,'') <> ''";

// Filtro LP parcial
if ($lp_filtro !== '') {
    $where[] = "ch.CveLP LIKE :lp";
    $params['lp'] = "%{$lp_filtro}%";
}

// Search global (DataTables)
if ($searchValue !== '') {
    $where[] = "(ch.CveLP LIKE :s OR ch.Clave_Contenedor LIKE :s OR ch.descripcion LIKE :s OR u.CodigoCSD LIKE :s)";
    $params['s'] = "%{$searchValue}%";
}

// Almacén (ex.cve_almac viene de ts_existenciatarima; ap.id es TEXT => CAST para no mezclar collations)
if ($almacen_f !== '') {
    $where[] = "CAST(ex.cve_almac AS CHAR(32)) COLLATE utf8mb4_unicode_ci = CAST(:almacen AS CHAR(32)) COLLATE utf8mb4_unicode_ci";
    $params['almacen'] = $almacen_f;
}

// Zona (opcional)
if ($zona_f !== '') {
    $where[] = "ca.des_almac = :zona";
    $params['zona'] = $zona_f;
}

// TipoGen
if ($tipogen_f === 'G') $where[] = "ch.TipoGen = 1";
if ($tipogen_f === 'N') $where[] = "ch.TipoGen = 0";

// Tipo (Pallet/Contenedor)
if ($tipo_f !== '') {
    $where[] = "ch.tipo = :tipo";
    $params['tipo'] = $tipo_f;
}

// Permanente/Temporal
if ($statuslp_f !== '') {
    $where[] = "ch.Permanente = :perm";
    $params['perm'] = $statuslp_f;
}

// Activo/Inactivo por existencia
if ($activo_f === '1') $where[] = "COALESCE(ex.existencia_total,0) > 0";
if ($activo_f === '0') $where[] = "COALESCE(ex.existencia_total,0) <= 0";

$whereSql = implode(' AND ', $where);

// SUBQUERY existencias (agregado por ntarima + cve_almac)
// Nota: aquí metemos filtro por almacén si viene, para recortar el universo.
$subWhere = '';
$subParams = [];
if ($almacen_f !== '') {
    $subWhere = "WHERE cve_almac = :alm_exist";
    $subParams['alm_exist'] = (int)$almacen_f; // en ts_existenciatarima es INT
}

$from = "
FROM c_charolas ch
LEFT JOIN (
    SELECT
        ntarima,
        cve_almac,
        MAX(CASE WHEN existencia > 0 THEN idy_ubica END) AS idy_ubica,
        SUM(existencia) AS existencia_total
    FROM ts_existenciatarima
    {$subWhere}
    GROUP BY ntarima, cve_almac
) ex ON ex.ntarima = ch.IDContenedor
LEFT JOIN c_ubicacion u ON u.idy_ubica = ex.idy_ubica
LEFT JOIN c_almacen ca  ON ca.cve_almac = u.cve_almac
LEFT JOIN c_almacenp ap ON CAST(ap.id AS CHAR(32)) COLLATE utf8mb4_unicode_ci = CAST(ex.cve_almac AS CHAR(32)) COLLATE utf8mb4_unicode_ci
";

// TOTAL (sin filtros adicionales distintos a LP no vacío)
$sqlTotal = "SELECT COUNT(*) AS c FROM c_charolas ch WHERE COALESCE(ch.CveLP,'') <> ''";
$recordsTotal = (int)db_val($sqlTotal);

// FILTERED count
$sqlCount = "SELECT COUNT(*) AS c {$from} WHERE {$whereSql}";
$recordsFiltered = (int)db_val($sqlCount, array_merge($params, $subParams));

// DATA
$sqlData = "
SELECT
    ap.clave              AS clave_almacenp,
    ap.nombre             AS nombre_almacen,
    ca.des_almac          AS zona_almacenaje,
    u.CodigoCSD           AS CodigoCSD,
    ch.descripcion,
    ch.Clave_Contenedor,
    ch.Permanente,
    ch.tipo,
    ch.CveLP,
    ch.TipoGen,
    ch.IDContenedor,
    COALESCE(ex.existencia_total,0) AS existencia_total
{$from}
WHERE {$whereSql}
ORDER BY {$orderBy} {$orderDir}
LIMIT {$length} OFFSET {$start}
";

$rows = db_all($sqlData, array_merge($params, $subParams));

// Formato final (DataTables)
$data = [];
foreach ($rows as $r) {
    $nomAlm = trim((string)($r['nombre_almacen'] ?? ''));
    $claAlm = trim((string)($r['clave_almacenp'] ?? ''));
    $almLabel = trim($claAlm . ' - ' . $nomAlm);

    $data[] = [
        'almacen' => $almLabel,
        'zona' => (string)($r['zona_almacenaje'] ?? ''),
        'bl' => (string)($r['CodigoCSD'] ?? ''),
        'descripcion' => (string)($r['descripcion'] ?? ''),
        'lp' => (string)($r['CveLP'] ?? ''),
        'tipo' => (string)($r['tipo'] ?? ''),
        'contenedor' => (string)($r['Clave_Contenedor'] ?? ''),
        'permanente' => (int)($r['Permanente'] ?? 0),
        'existencia_total' => (float)($r['existencia_total'] ?? 0),
    ];
}

jexit([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);
