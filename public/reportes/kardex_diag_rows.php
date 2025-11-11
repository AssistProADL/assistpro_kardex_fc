<?php
// ======= BACKEND SOLO-DATOS + DIAGNÓSTICO OPCIONAL =======

// Debug robusto (no cambia UI; solo ayuda si hay error fatal o ?debug=1)
error_reporting(E_ALL);
ini_set('display_errors', '1');

$__dbLoaded = false;
$__dbPath = __DIR__ . '/../app/db.php';
if (file_exists($__dbPath)) {
    require_once $__dbPath;
    $__dbLoaded = isset($pdo) && ($pdo instanceof PDO);
}
if (!$__dbLoaded) {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
        'root', '',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
}

function qOne($pdo, $sql, $p = array()) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
}
function qAll($pdo, $sql, $p = array()) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

// 0) Verifica que la vista exista
$viewExists = qOne($pdo, "SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'v_kardex_doble_partida'");
if (!$viewExists) {
    // Mensaje claro si la vista no está (no cambia diseño; solo imprime arriba si debug)
    if (isset($_GET['debug']) && $_GET['debug']) {
        echo "<div style='padding:8px;background:#fee;border:1px solid #f99;color:#900;font:13px/1.2 monospace'>";
        echo "La vista v_kardex_doble_partida no existe en la BD " . htmlspecialchars(qOne($pdo, 'SELECT DATABASE()')) . ".<br>";
        echo "Crea la vista y vuelve a cargar.";
        echo "</div>";
    }
    // Evita seguir con consultas vacías
    $rows = array();
    $kpi = array('total_movs'=>0,'transfer_cnt'=>0,'sum_entradas'=>0,'sum_salidas'=>0,'sum_ajustes'=>0);
    $total_registros = 0;
    return;
}

// 1) Filtros
$empresa_id   = (isset($_GET['empresa_id']) && $_GET['empresa_id'] !== '') ? trim($_GET['empresa_id']) : null;
$producto_id  = (isset($_GET['producto_id']) && $_GET['producto_id'] !== '') ? trim($_GET['producto_id']) : null;
$lote         = (isset($_GET['lote']) && $_GET['lote'] !== '') ? trim($_GET['lote']) : null;
$serie        = (isset($_GET['serie']) && $_GET['serie'] !== '') ? trim($_GET['serie']) : null;
$tipo_tx      = (isset($_GET['tipo_tx']) && $_GET['tipo_tx'] !== '') ? (array)$_GET['tipo_tx'] : array();
$fecha_desde  = (isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '') ? trim($_GET['fecha_desde']) : null;
$fecha_hasta  = (isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '') ? trim($_GET['fecha_hasta']) : null;
$limit        = (isset($_GET['limit']) && ctype_digit((string)$_GET['limit'])) ? (int)$_GET['limit'] : 200; // reduce por desempeño
$offset       = (isset($_GET['offset']) && ctype_digit((string)$_GET['offset'])) ? (int)$_GET['offset'] : 0;

$where = array();
$params = array();

if ($empresa_id !== null) { $where[] = 'empresa_id = :empresa_id'; $params[':empresa_id'] = $empresa_id; }
if ($producto_id !== null) { $where[] = 'producto_id LIKE :producto_id'; $params[':producto_id'] = '%' . $producto_id . '%'; }
if ($lote !== null) { $where[] = 'lote LIKE :lote'; $params[':lote'] = '%' . $lote . '%'; }
if ($serie !== null) { $where[] = 'serie LIKE :serie'; $params[':serie'] = '%' . $serie . '%'; }
if ($fecha_desde !== null) { $where[] = 'fecha_hora >= :fdesde'; $params[':fdesde'] = $fecha_desde . ' 00:00:00'; }
if ($fecha_hasta !== null) { $where[] = 'fecha_hora <= :fhasta'; $params[':fhasta'] = $fecha_hasta . ' 23:59:59'; }

$tipoPlaceholders = array();
if (!empty($tipo_tx)) {
    $idx = 0;
    foreach ($tipo_tx as $t) {
        $k = ':tx' . $idx; $idx++;
        $tipoPlaceholders[] = $k;
        $params[$k] = strtoupper(trim($t));
    }
    if (!empty($tipoPlaceholders)) {
        $where[] = 'UPPER(tipo_tx) IN (' . implode(',', $tipoPlaceholders) . ')';
    }
}
$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// 2) KPIs y total
$sqlKpi = "
    SELECT
        COUNT(*) AS total_movs,
        SUM(CASE WHEN UPPER(tipo_tx) = 'TRANSFERENCIA' THEN 1 ELSE 0 END) AS transfer_cnt,
        SUM(mov_dst) AS sum_entradas,
        SUM(mov_ori) AS sum_salidas,
        SUM(CASE WHEN UPPER(tipo_tx) LIKE 'AJUSTE%' THEN (mov_dst - mov_ori) ELSE 0 END) AS sum_ajustes
    FROM v_kardex_doble_partida
    $whereSql
";
$kpi = qAll($pdo, $sqlKpi, $params);
$kpi = $kpi ? $kpi[0] : array('total_movs'=>0,'transfer_cnt'=>0,'sum_entradas'=>0,'sum_salidas'=>0,'sum_ajustes'=>0);

$sqlTotal = "SELECT COUNT(*) FROM v_kardex_doble_partida $whereSql";
$total_registros = (int) qOne($pdo, $sqlTotal, $params);

// 3) Datos (paginado y con índice)
$sqlRows = "
    SELECT
        tx_id, fecha_hora, tipo_tx,
        producto_id, uom, lote, serie,
        alm_ori_id, ubi_ori_id, stock_ini_ori, mov_ori, stock_fin_ori,
        alm_dst_id, ubi_dst_id, stock_ini_dst, mov_dst, stock_fin_dst,
        referencia, notas, usuario_id, empresa_id
    FROM v_kardex_doble_partida
    $whereSql
    ORDER BY fecha_hora DESC, tx_id DESC
    LIMIT :limit OFFSET :offset
";
$paramsRows = $params;
$paramsRows[':limit'] = $limit;
$paramsRows[':offset'] = $offset;

$stRows = $pdo->prepare($sqlRows);
foreach ($paramsRows as $k=>$v) {
    if ($k === ':limit' || $k === ':offset') {
        $stRows->bindValue($k, (int)$v, PDO::PARAM_INT);
    } else {
        $stRows->bindValue($k, $v);
    }
}
$stRows->execute();
$rows = $stRows->fetchAll();

// 4) Export CSV (mismos filtros; sin límite)
if (isset($_GET['export']) && strtolower($_GET['export']) === 'csv') {
    $fname = 'kardex_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fname);
    $out = fopen('php://output', 'w');
    fputcsv($out, array(
        'tx_id','fecha_hora','tipo_tx','producto_id','uom','lote','serie',
        'alm_ori_id','ubi_ori_id','stock_ini_ori','mov_ori','stock_fin_ori',
        'alm_dst_id','ubi_dst_id','stock_ini_dst','mov_dst','stock_fin_dst',
        'referencia','notas','usuario_id','empresa_id'
    ));
    $sqlAll = "
        SELECT
            tx_id, fecha_hora, tipo_tx,
            producto_id, uom, lote, serie,
            alm_ori_id, ubi_ori_id, stock_ini_ori, mov_ori, stock_fin_ori,
            alm_dst_id, ubi_dst_id, stock_ini_dst, mov_dst, stock_fin_dst,
            referencia, notas, usuario_id, empresa_id
        FROM v_kardex_doble_partida
        $whereSql
        ORDER BY fecha_hora DESC, tx_id DESC
    ";
    $all = qAll($pdo, $sqlAll, $params);
    foreach ($all as $r) {
        fputcsv($out, array(
            $r['tx_id'], $r['fecha_hora'], $r['tipo_tx'],
            $r['producto_id'], $r['uom'], $r['lote'], $r['serie'],
            $r['alm_ori_id'], $r['ubi_ori_id'], $r['stock_ini_ori'], $r['mov_ori'], $r['stock_fin_ori'],
            $r['alm_dst_id'], $r['ubi_dst_id'], $r['stock_ini_dst'], $r['mov_dst'], $r['stock_fin_dst'],
            $r['referencia'], $r['notas'], $r['usuario_id'], $r['empresa_id']
        ));
    }
    fclose($out);
    exit;
}

// 5) Debug opcional en pantalla (?debug=1)
if (isset($_GET['debug']) && $_GET['debug']) {
    echo "<div style='padding:8px;background:#eef;border:1px solid #99f;color:#003;font:13px/1.3 monospace'>";
    echo "<b>DEBUG</b><br>";
    echo "DB: " . htmlspecialchars(qOne($pdo, 'SELECT DATABASE()')) . "<br>";
    echo "Total filtrado: " . (int)$total_registros . "<br>";
    echo "Limit/Offset: " . (int)$limit . " / " . (int)$offset . "<br>";
    echo "WHERE: " . htmlspecialchars($whereSql) . "<br>";
    echo "Params: " . htmlspecialchars(json_encode($params)) . "<br>";
    echo "</div>";
}


// --- Debug: mostrar primeras 5 filas ---
if (isset($_GET['debug']) && $_GET['debug']) {
    echo "<pre style='max-height:300px;overflow:auto;background:#f9f9f9;border:1px solid #ccc;padding:6px;font:12px monospace'>";
    echo "Primeros 5 registros (vista v_kardex_doble_partida):\n";
    $i = 0;
    foreach ($rows as $r) {
        if ($i++ >= 5) break;
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "</pre>";
}

// Variables expuestas a la UI: $rows, $kpi, $total_registros, $limit, $offset
?>