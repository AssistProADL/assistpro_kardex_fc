<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

/* =========================
   Parámetros
   ========================= */
$almacen = isset($_GET['almacen']) ? intval($_GET['almacen']) : null;
$almacenp_id = isset($_GET['almacenp_id']) ? intval($_GET['almacenp_id']) : null;
$ubicacion = isset($_GET['ubicacion']) ? trim($_GET['ubicacion']) : null;

// Si viene almacenp_id, convertirlo a cve_almac
if ($almacenp_id && !$almacen) {
    $stAlm = $pdo->prepare("SELECT DISTINCT cve_almac FROM c_almacen WHERE cve_almacenp = ? LIMIT 1");
    $stAlm->execute([$almacenp_id]);
    $almacen = $stAlm->fetchColumn();

    if (!$almacen) {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontró almacén para el ID proporcionado'
        ]);
        exit;
    }
}

if (!$almacen || !$ubicacion) {
    echo json_encode([
        'success' => false,
        'error' => 'Parámetros almacén y ubicación requeridos'
    ]);
    exit;
}

try {

    /* =========================
       1️⃣ Validar ubicación
       ========================= */
    $sqlUbic = "
        SELECT cve_ubicacion, AreaStagging
        FROM t_ubicacionembarque
        WHERE cve_almac = ?
          AND cve_ubicacion = ?
          AND Activo = 1
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlUbic);
    $stmt->execute([$almacen, $ubicacion]);
    $ubic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ubic) {
        echo json_encode([
            'success' => true,
            'total' => 0,
            'data' => []
        ]);
        exit;
    }

    /* =========================
       2️⃣ Pedidos en status C
       ========================= */
    $sql = "
        SELECT
            p.Fol_Folio,
            p.Cve_clte,
            p.ruta,
            p.cve_almac,
            sp.Sufijo,
            sp.status AS status_subpedido
        FROM th_pedido p
        INNER JOIN th_subpedido sp
            ON sp.Fol_Folio = p.Fol_Folio
           AND sp.cve_almac = p.cve_almac
        INNER JOIN rel_uembarquepedido re
            ON re.Fol_Folio = p.Fol_Folio
           AND re.Sufijo = sp.Sufijo
           AND re.cve_almac = p.cve_almac
           AND re.cve_ubicacion = ?
           AND re.Activo = 1
        WHERE p.cve_almac = ?
          AND p.status = 'C'
          AND p.Activo = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ubicacion, $almacen]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode([
            'success' => true,
            'total' => 0,
            'data' => []
        ]);
        exit;
    }

    /* =========================
       3️⃣ Validar subpedidos
       ========================= */
    $pedidos = [];

    foreach ($rows as $r) {
        $folio = $r['Fol_Folio'];

        if (!isset($pedidos[$folio])) {
            $pedidos[$folio] = [
                'Fol_Folio' => $folio,
                'cve_almac' => $r['cve_almac'],
                'Cve_clte' => $r['Cve_clte'],
                'ruta' => $r['ruta'],
                'subpedidos' => [],
                'valido' => true
            ];
        }

        if ($r['status_subpedido'] !== 'C') {
            $pedidos[$folio]['valido'] = false;
        }

        $pedidos[$folio]['subpedidos'][] = $r['Sufijo'];
    }

    /* =========================
       4️⃣ Respuesta final
       ========================= */
    $data = [];

    foreach ($pedidos as $p) {
        if (!$p['valido'])
            continue;

        foreach ($p['subpedidos'] as $sufijo) {
            $data[] = [
                'Fol_Folio' => $p['Fol_Folio'],
                'Sufijo' => $sufijo,
                'cve_almac' => $p['cve_almac'],
                'Cve_clte' => $p['Cve_clte'],
                'ruta' => $p['ruta'],
                'cve_ubicacion' => $ubic['cve_ubicacion'],
                'AreaStagging' => $ubic['AreaStagging'],
                'status_pedido' => 'C',
                'status_subpedido' => 'C'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'total' => count($data),
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener pedidos embarcables',
        'msg' => $e->getMessage()
    ]);
}
