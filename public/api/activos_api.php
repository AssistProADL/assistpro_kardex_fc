<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {

    $pdo = db_pdo();
    $action = $_REQUEST['action'] ?? '';

    // =====================================================
    // META
    // =====================================================
    if ($action === 'meta') {

        $companias = $pdo->query("
            SELECT cve_cia AS id, des_cia AS nombre
            FROM c_compania
            WHERE Activo = 1
            ORDER BY des_cia
        ")->fetchAll(PDO::FETCH_ASSOC);

        $almacenes = $pdo->query("
            SELECT cve_almac AS id,
                   clave_almacen AS clave,
                   des_almac AS nombre
            FROM c_almacen
            WHERE Activo = 1
            ORDER BY des_almac
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'companias' => $companias,
            'almacenes' => $almacenes
        ]);
        exit;
    }

    // =====================================================
    // LIST
    // =====================================================
    if ($action === 'list') {

        $cve_cia        = $_GET['cve_cia'] ?? null;
        $solo_activos   = intval($_GET['solo_activos'] ?? 1);
        $solo_asignados = intval($_GET['solo_asignados'] ?? 0);
        $q              = trim($_GET['q'] ?? '');
        $limit          = intval($_GET['pageSize'] ?? 500);

        $sql = "
            SELECT 
                a.*,
                al.clave_almacen,
                al.des_almac AS almacen_nombre,

                va.id_asignacion,
                va.id_cliente,
                va.id_destinatario,
                va.fecha_desde,
                va.fecha_hasta,
                va.vigencia,
                va.asig_latitud,
                va.asig_longitud

            FROM c_activos a

            LEFT JOIN c_almacen al 
                ON al.cve_almac = a.id_almacen

            LEFT JOIN v_activo_asignacion_actual va
                ON va.id_activo = a.id_activo
                AND va.cve_cia = a.id_compania

            WHERE 1=1
        ";

        $params = [];

        if ($cve_cia) {
            $sql .= " AND a.id_compania = :cve_cia ";
            $params[':cve_cia'] = $cve_cia;
        }

        if ($solo_activos) {
            $sql .= " AND a.activo = 1 ";
        }

        if ($solo_asignados) {
            $sql .= " AND va.id_asignacion IS NOT NULL ";
        }

        if ($q !== '') {
            $sql .= " AND (
                a.clave LIKE :q OR
                a.num_serie LIKE :q OR
                a.marca LIKE :q OR
                a.modelo LIKE :q OR
                a.descripcion LIKE :q
            )";
            $params[':q'] = "%$q%";
        }

        $sql .= " ORDER BY a.id_activo DESC LIMIT $limit";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // =====================================================
        // POST PROCESAMIENTO
        // =====================================================

        foreach ($rows as &$r) {

            // -----------------------------------------
            // Asignación visible
            // -----------------------------------------

            if (!empty($r['id_asignacion'])) {

                if (!empty($r['id_destinatario'])) {
                    $r['asignado_a'] = "Destinatario #" . $r['id_destinatario'];
                } elseif (!empty($r['id_cliente'])) {
                    $r['asignado_a'] = "Cliente #" . $r['id_cliente'];
                } else {
                    $r['asignado_a'] = "Asignado";
                }

            } else {
                $r['asignado_a'] = null;
            }

            // -----------------------------------------
            // Coordenadas (prioridad asignación)
            // -----------------------------------------

            if (!empty($r['asig_latitud']) && !empty($r['asig_longitud'])) {
                $r['latitud']  = $r['asig_latitud'];
                $r['longitud'] = $r['asig_longitud'];
            }

            // -----------------------------------------
            // Semáforo estratégico
            // -----------------------------------------

            $semaforo = 'VERDE';

            if ($r['estatus'] === 'EN_MANTTO') {
                $semaforo = 'AMARILLO';
            }

            if (!empty($r['vigencia']) && $r['vigencia'] == 0) {
                $semaforo = 'ROJO';
            }

            if (empty($r['id_asignacion'])) {
                $semaforo = 'VERDE';
            }

            $r['semaforo'] = $semaforo;
        }

        echo json_encode([
            'ok'    => true,
            'total' => count($rows),
            'rows'  => $rows,
            'data'  => $rows
        ]);
        exit;
    }

    // =====================================================
    // GET
    // =====================================================
    if ($action === 'get') {

        $id = intval($_GET['id_activo'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT *
            FROM c_activos
            WHERE id_activo = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok'  => true,
            'row' => $row
        ]);
        exit;
    }

    // =====================================================
    // CREATE
    // =====================================================
    if ($action === 'create') {

        $sql = "
        INSERT INTO c_activos
        (clave,id_compania,id_almacen,tipo_activo,num_serie,marca,modelo,descripcion,
         fecha_compra,proveedor,factura,ventas_objetivo_mensual,estatus,
         latitud,longitud,activo,notas_condicion)
        VALUES
        (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['clave'],
            $_POST['id_compania'],
            $_POST['id_almacen'],
            $_POST['tipo_activo'],
            $_POST['num_serie'],
            $_POST['marca'],
            $_POST['modelo'],
            $_POST['descripcion'],
            $_POST['fecha_compra'] ?: null,
            $_POST['proveedor'],
            $_POST['factura'],
            $_POST['ventas_objetivo_mensual'] ?: null,
            $_POST['estatus'],
            $_POST['latitud'] ?: null,
            $_POST['longitud'] ?: null,
            $_POST['activo'],
            $_POST['notas_condicion']
        ]);

        echo json_encode(['ok'=>true]);
        exit;
    }

    // =====================================================
    // UPDATE
    // =====================================================
    if ($action === 'update') {

        $sql = "
        UPDATE c_activos SET
            clave=?,
            id_compania=?,
            id_almacen=?,
            tipo_activo=?,
            num_serie=?,
            marca=?,
            modelo=?,
            descripcion=?,
            fecha_compra=?,
            proveedor=?,
            factura=?,
            ventas_objetivo_mensual=?,
            estatus=?,
            latitud=?,
            longitud=?,
            activo=?,
            notas_condicion=?
        WHERE id_activo=?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['clave'],
            $_POST['id_compania'],
            $_POST['id_almacen'],
            $_POST['tipo_activo'],
            $_POST['num_serie'],
            $_POST['marca'],
            $_POST['modelo'],
            $_POST['descripcion'],
            $_POST['fecha_compra'] ?: null,
            $_POST['proveedor'],
            $_POST['factura'],
            $_POST['ventas_objetivo_mensual'] ?: null,
            $_POST['estatus'],
            $_POST['latitud'] ?: null,
            $_POST['longitud'] ?: null,
            $_POST['activo'],
            $_POST['notas_condicion'],
            $_POST['id_activo']
        ]);

        echo json_encode(['ok'=>true]);
        exit;
    }

    // =====================================================
    // DELETE
    // =====================================================
    if ($action === 'delete') {

        $stmt = $pdo->prepare("DELETE FROM c_activos WHERE id_activo=?");
        $stmt->execute([$_POST['id_activo']]);

        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Acción inválida']);

} catch(Throwable $e){
    echo json_encode([
        'ok'=>false,
        'error'=>$e->getMessage()
    ]);
}
