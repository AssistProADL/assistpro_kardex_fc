<?php
// public/api/filtros_assistpro.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No existe la conexión PDO disponible ($pdo) en db.php: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'init';

if ($action === 'init') {
    init_filtros($pdo);
} elseif ($action === 'consulta') {
    consulta_vista($pdo);
} else {
    echo json_encode([
        'ok'    => false,
        'error' => 'Acción no soportada'
    ], JSON_UNESCAPED_UNICODE);
}

function init_filtros(PDO $pdo)
{
    // Recetas disponibles
    $recetas = [
        [
            'id'          => 'existencias_ubicacion',
            'nombre'      => 'Existencias por ubicación',
            'descripcion' => 'Stock por BL, producto, lote/serie y LP',
            'vista_sql'   => 'v_existencias_por_ubicacion_ao'
        ],
        // Aquí podremos ir agregando más recetas para otros reportes / procesos
    ];

    // Empresas
    $empresas = [];
    try {
        $sql = "SELECT cve_cia, des_cia 
                FROM c_compania 
                WHERE Activo = 1 OR Activo IS NULL
                ORDER BY des_cia";
        $empresas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $empresas = [];
    }

    // Almacenes (c_almacen + c_almacenp)
    $almacenes = [];
    try {
        $sql = "SELECT a.cve_almac, a.clave_almacen, a.des_almac
                FROM c_almacen a
                WHERE a.Activo = 1 OR a.Activo IS NULL
                ORDER BY a.clave_almacen, a.des_almac";
        $almacenes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $almacenes = [];
    }

    // Rutas
    $rutas = [];
    try {
        $sql = "SELECT ID_Ruta, cve_ruta, descripcion
                FROM t_ruta
                WHERE Activo = 1 OR Activo IS NULL
                ORDER BY cve_ruta";
        $rutas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rutas = [];
    }

    // Clientes (limitamos un poco para no saturar)
    $clientes = [];
    try {
        $sql = "SELECT id_cliente, Cve_Clte, RazonSocial
                FROM c_cliente
                ORDER BY RazonSocial
                LIMIT 500";
        $clientes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $clientes = [];
    }

    echo json_encode([
        'ok'        => true,
        'recetas'   => $recetas,
        'empresas'  => $empresas,
        'almacenes' => $almacenes,
        'rutas'     => $rutas,
        'clientes'  => $clientes
    ], JSON_UNESCAPED_UNICODE);
}

function consulta_vista(PDO $pdo)
{
    $vista_id = $_POST['vista_id'] ?? '';

    if ($vista_id === 'existencias_ubicacion') {
        consulta_existencias_ubicacion($pdo);
        return;
    }

    echo json_encode([
        'ok'    => false,
        'error' => 'Receta no configurada: ' . $vista_id
    ], JSON_UNESCAPED_UNICODE);
}

function consulta_existencias_ubicacion(PDO $pdo)
{
    // Filtros base
    $almacen      = trim($_POST['almacen']      ?? '');
    $bl           = trim($_POST['bl']           ?? '');
    $producto     = trim($_POST['producto']     ?? '');
    $lote         = trim($_POST['lote']         ?? '');
    $lp           = trim($_POST['lp']           ?? '');
    $tipo_control = trim($_POST['tipo_control'] ?? '');
    $solo_no_qa   = isset($_POST['solo_no_qa']) && $_POST['solo_no_qa'] === '1';

    $page     = max(1, (int)($_POST['page']     ?? 1));
    $per_page = max(1, (int)($_POST['per_page'] ?? 25));
    $offset   = ($page - 1) * $per_page;

    $where  = [];
    $params = [];

    if ($almacen !== '') {
        $where[]            = 'cve_almac = :almacen';
        $params[':almacen'] = $almacen;
    }

    // Solo ubicaciones con existencia
    $where[] = 'existencia > 0';

    if ($bl !== '') {
        $where[]       = 'bl LIKE :bl';
        $params[':bl'] = $bl . '%';
    }

    if ($producto !== '') {
        $where[]            = 'cve_articulo = :prod';
        $params[':prod']    = $producto;
    }

    if ($lote !== '') {
        $where[]            = 'cve_lote = :lote';
        $params[':lote']    = $lote;
    }

    if ($lp !== '') {
        $where[]         = 'CveLP = :lp';
        $params[':lp']   = $lp;
    }

    if ($tipo_control !== '') {
        $where[]                     = 'tipo_control = :tipo_control';
        $params[':tipo_control']     = $tipo_control;
    }

    if ($solo_no_qa) {
        $where[] = 'es_qa = 0';
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    // KPIs
    $cards = [
        'total_registros'   => 0,
        'total_lps'         => 0,
        'total_ubicaciones' => 0,
        'total_productos'   => 0
    ];

    try {
        $sqlCards = "
            SELECT
                COUNT(*) AS total_registros,
                COUNT(DISTINCT CveLP) AS total_lps,
                COUNT(DISTINCT CONCAT(cve_almac,'|',bl)) AS total_ubicaciones,
                COUNT(DISTINCT cve_articulo) AS total_productos
            FROM v_existencias_por_ubicacion_ao
            $whereSql
        ";
        $stmt = $pdo->prepare($sqlCards);
        $stmt->execute($params);
        $rowCards = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rowCards) {
            foreach ($cards as $k => $v) {
                if (isset($rowCards[$k])) {
                    $cards[$k] = (int)$rowCards[$k];
                }
            }
        }
    } catch (Throwable $e) {
        echo json_encode([
            'ok'    => false,
            'error' => 'Error en KPIs: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Total rows (para paginación)
    $total_rows = $cards['total_registros'];

    // Detalle
    $rows = [];
    try {
        $sqlDet = "
            SELECT
                bl,
                pasillo,
                rack,
                nivel,
                cve_almac,
                cve_articulo,
                des_articulo,
                tipo_control,
                cve_lote,
                Caducidad,
                existencia,
                es_qa,
                estado_bl,
                CveLP
            FROM v_existencias_por_ubicacion_ao
            $whereSql
            ORDER BY bl, cve_articulo, cve_lote
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sqlDet);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        echo json_encode([
            'ok'    => false,
            'error' => 'Error en detalle: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode([
        'ok'         => true,
        'cards'      => $cards,
        'rows'       => $rows,
        'total_rows' => $total_rows,
        'page'       => $page,
        'per_page'   => $per_page
    ], JSON_UNESCAPED_UNICODE);
}
