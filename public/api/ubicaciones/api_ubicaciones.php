<?php
/**
 * API Ubicaciones (Enterprise)
 * - mode=json    (default) -> tabla paginada
 * - mode=cards              -> KPIs/resumen
 * - mode=grafico            -> datos para chart (por pasillo/rack/nivel/zona)
 * - mode=csv                -> exportación CSV (con protección Excel)
 *
 * Filtros:
 * - empresa (OBLIGATORIO)   -> c_compania.cve_cia
 * - almacen (opcional)      -> c_almacenp.id
 * - zona (opcional)         -> c_almacen.cve_almac
 * - codigo (opcional)       -> c_ubicacion.CodigoCSD LIKE
 * - pasillo (opcional)      -> c_ubicacion.cve_pasillo
 * - rack (opcional)         -> c_ubicacion.cve_rack
 * - nivel (opcional)        -> c_ubicacion.cve_nivel
 *
 * Paginación:
 * - page (default 1)
 * - limit (default 50, max 200)
 *
 * Orden:
 * - order: codigo|pasillo|rack|nivel|ubicacion|seccion|zona|almacen
 * - dir: ASC|DESC
 */

require_once __DIR__ . '/../../../app/db.php';

db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

try {
    // -----------------------------
    // 1) Parámetros de entrada
    // -----------------------------
    $mode    = $_GET['mode']    ?? 'json';

    $empresa = $_GET['empresa'] ?? null; // OBLIGATORIO
    $almacen = $_GET['almacen'] ?? null; // c_almacenp.id
    $zona    = $_GET['zona']    ?? null; // c_almacen.cve_almac

    $codigo  = $_GET['codigo']  ?? null;
    $pasillo = $_GET['pasillo'] ?? null;
    $rack    = $_GET['rack']    ?? null;
    $nivel   = $_GET['nivel']   ?? null;

    $page  = isset($_GET['page'])  ? (int)$_GET['page']  : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    $order = $_GET['order'] ?? 'codigo';
    $dir   = strtoupper($_GET['dir'] ?? 'ASC');
    $dir   = ($dir === 'DESC') ? 'DESC' : 'ASC';

    // Seguridad básica
    if (!$empresa) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error"   => "Debe seleccionar una empresa (empresa=...)"
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 50;
    if ($limit > 15000) $limit = 15000; // límite duro para proteger servidor

    $offset = ($page - 1) * $limit;

    // -----------------------------
    // 2) Mapa de orden permitido (whitelist)
    // -----------------------------
    $orderMap = [
        'codigo'    => 'u.CodigoCSD',
        'pasillo'   => 'u.cve_pasillo',
        'rack'      => 'u.cve_rack',
        'nivel'     => 'u.cve_nivel',
        'ubicacion' => 'u.Ubicacion',
        'seccion'   => 'u.Seccion',
        'zona'      => 'z.des_almac',
        'almacen'   => 'a.nombre',
    ];

    $orderBy = $orderMap[$order] ?? 'u.CodigoCSD';

    // -----------------------------
    // 3) Base query y filtros (reutilizable)
    // -----------------------------
    $sqlBase = "
        FROM c_ubicacion u
        INNER JOIN c_almacen  z ON z.cve_almac = u.cve_almac
        INNER JOIN c_almacenp a ON a.id = z.cve_almacenp
        INNER JOIN c_compania e ON e.cve_cia = a.cve_cia
        WHERE e.cve_cia = :empresa
    ";

    $params = [':empresa' => $empresa];

    if ($almacen !== null && $almacen !== '') {
        $sqlBase .= " AND a.id = :almacen";
        $params[':almacen'] = $almacen;
    }

    if ($zona !== null && $zona !== '') {
        $sqlBase .= " AND z.cve_almac = :zona";
        $params[':zona'] = $zona;
    }

    if ($codigo !== null && $codigo !== '') {
        $sqlBase .= " AND u.CodigoCSD LIKE :codigo";
        $params[':codigo'] = '%' . $codigo . '%';
    }

    if ($pasillo !== null && $pasillo !== '') {
        $sqlBase .= " AND u.cve_pasillo = :pasillo";
        $params[':pasillo'] = $pasillo;
    }

    if ($rack !== null && $rack !== '') {
        $sqlBase .= " AND u.cve_rack = :rack";
        $params[':rack'] = $rack;
    }

    if ($nivel !== null && $nivel !== '') {
        $sqlBase .= " AND u.cve_nivel = :nivel";
        $params[':nivel'] = $nivel;
    }

    // -----------------------------
    // 4) MODE: CARDS (KPIs)
    // -----------------------------
    if ($mode === 'cards') {


$sqlCards = "
    SELECT
        COUNT(DISTINCT u.CodigoCSD) AS total_ubicaciones,
        COUNT(DISTINCT u.cve_pasillo) AS total_pasillos,
        COUNT(DISTINCT u.cve_rack) AS total_racks,
        COUNT(DISTINCT u.cve_nivel) AS total_niveles,
        COUNT(DISTINCT z.cve_almac) AS total_zonas
    " . $sqlBase;


        $stmt = $pdo->prepare($sqlCards);
        $stmt->execute($params);

        echo json_encode([
            "success" => true,
            "filters" => [
                "empresa" => $empresa,
                "almacen" => $almacen,
                "zona"    => $zona,
                "codigo"  => $codigo,
                "pasillo" => $pasillo,
                "rack"    => $rack,
                "nivel"   => $nivel,
            ],
            "cards" => $stmt->fetch(PDO::FETCH_ASSOC)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // -----------------------------
    // 5) MODE: GRAFICO (data para chart)
    //     ?mode=grafico&chart=pasillo|rack|nivel|zona
    // -----------------------------
    if ($mode === 'grafico') {
        $chart = $_GET['chart'] ?? 'pasillo';

        $chartMap = [
            'pasillo' => ['col' => 'u.cve_pasillo', 'label' => 'pasillo'],
            'rack'    => ['col' => 'u.cve_rack',    'label' => 'rack'],
            'nivel'   => ['col' => 'u.cve_nivel',   'label' => 'nivel'],
            'zona'    => ['col' => 'z.des_almac',   'label' => 'zona'],
        ];

        $cfg = $chartMap[$chart] ?? $chartMap['pasillo'];

        $sqlChart = "
            SELECT
                {$cfg['col']} AS categoria,
                COUNT(*) AS total
            " . $sqlBase . "
            GROUP BY {$cfg['col']}
            ORDER BY total DESC
            LIMIT 50
        ";

        $stmt = $pdo->prepare($sqlChart);
        $stmt->execute($params);

        echo json_encode([
            "success" => true,
            "chart"   => $chart,
            "data"    => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // -----------------------------
    // 6) MODE: CSV (exportación)
    //     Truco Excel: ="" para mantener texto/ceros
    // -----------------------------
    if ($mode === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ubicaciones.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 para Excel
    echo "\xEF\xBB\xBF";

    $sql = "
        SELECT
            e.clave_empresa AS ClaveEmpresa,
            e.des_cia AS Empresa,
            a.clave AS ClaveAlmacen,
            a.nombre AS Almacen,
            z.des_almac AS Zona,
            u.CodigoCSD AS BL,
            u.cve_pasillo AS Pasillo,
            u.cve_rack AS Rack,
            u.cve_nivel AS Nivel
        " . $sqlBase . "
        ORDER BY $orderBy $dir
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = fopen('php://output', 'w');

    if (!empty($rows)) {

        // encabezados
        fputcsv($out, array_keys($rows[0]));

        foreach ($rows as $r) {

            foreach ($r as $k => $v) {

                $v = trim((string)$v);

                // Evitar que Excel:
                // - Quite ceros
                // - Cambie a notación científica
                // - Interprete fórmulas

                $r[$k] = '="' . str_replace('"','""',$v) . '"';
            }

            fputcsv($out, $r);
        }
    }

    fclose($out);
    exit;
}

    // -----------------------------
    // 7) MODE: JSON (tabla paginada + total)
    // -----------------------------
    // Total
   

$stmtTotal = $pdo->prepare("
    SELECT COUNT(DISTINCT u.CodigoCSD)
    " . $sqlBase
);


    $stmtTotal->execute($params);
    $total = (int)$stmtTotal->fetchColumn();

    // Data paginada
    $sql = "
        SELECT
            u.idy_ubica,
            u.CodigoCSD,
            u.cve_pasillo,
            u.cve_rack,
            u.cve_nivel,
            u.Ubicacion,
            u.Seccion,
            z.cve_almac    AS zona_id,
            z.des_almac    AS zona,
            a.id           AS almacen_id,
            a.clave        AS almacen_clave,
            a.nombre       AS almacen,
            e.cve_cia      AS empresa_id,
            e.clave_empresa AS empresa_clave,
            e.des_cia      AS empresa
        " . $sqlBase . "
        ORDER BY $orderBy $dir
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    // Bind filtros
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }

    // Bind paginación
    $stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "filters" => [
            "empresa" => $empresa,
            "almacen" => $almacen,
            "zona"    => $zona,
            "codigo"  => $codigo,
            "pasillo" => $pasillo,
            "rack"    => $rack,
            "nivel"   => $nivel,
            "order"   => $order,
            "dir"     => $dir
        ],
        "pagination" => [
            "page"        => $page,
            "limit"       => $limit,
            "total"       => $total,
            "total_pages" => ($limit > 0) ? (int)ceil($total / $limit) : 0
        ],
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
