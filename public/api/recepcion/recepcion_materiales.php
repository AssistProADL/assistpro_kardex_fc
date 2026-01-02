<?php
// public/ingresos/api/recepcion_materiales.php
// API para Recepción (OC / Libre / CrossDocking)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php'; // ajusta si tu estructura cambia

function jexit($arr) { echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$action = $_REQUEST['action'] ?? '';

try {

    // ---------------------------
    // HELPERS
    // ---------------------------
    $schema = db_val("SELECT DATABASE()");

    $tableExists = function($tbl) use ($schema) {
        $n = db_val("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = ? AND table_name = ?
        ", [$schema, $tbl]);
        return intval($n) > 0;
    };

    // Normaliza estatus “activa” de OC (no sabemos tu diccionario completo,
    // pero en tu tabla hay K/T; aquí tomamos NO canceladas)
    $ocStatusWhere = " (t.status IS NULL OR t.status NOT IN ('C','X','0')) ";

    // ---------------------------
    // ROUTES
    // ---------------------------
    if ($action === 'proveedores') {
        // c_proveedor: ID_Proveedor, Nombre, Activo, cve_proveedor
        $rows = db_all("
            SELECT
                ID_Proveedor AS id,
                Nombre       AS nombre,
                cve_proveedor AS clave
            FROM c_proveedor
            WHERE (Activo = 1 OR Activo = '1' OR Activo = 'S')
            ORDER BY Nombre
        ");
        jexit(['ok' => 1, 'data' => $rows]);
    }

    if ($action === 'ocs') {
        // Lista OC activas (th_aduana como encabezado de OC)
        // filtros: cve_almac (int), id_proveedor (int), q (folio/factura)
        $cve_almac    = $_GET['cve_almac'] ?? '';
        $id_proveedor = $_GET['id_proveedor'] ?? '';
        $q            = trim($_GET['q'] ?? '');

        $where = [];
        $args  = [];

        $where[] = "t.ID_Protocolo = 'OCN'";
        $where[] = "(t.Activo = 1 OR t.Activo = '1' OR t.Activo IS NULL)";
        $where[] = $ocStatusWhere;

        if ($cve_almac !== '') {
            $where[] = "t.Cve_Almac = ?";
            $args[]  = $cve_almac;
        }
        if ($id_proveedor !== '') {
            $where[] = "t.ID_Proveedor = ?";
            $args[]  = $id_proveedor;
        }
        if ($q !== '') {
            $where[] = "(CAST(t.ID_Aduana AS CHAR) LIKE ? OR t.Factura LIKE ? OR t.num_pedimento LIKE ?)";
            $args[]  = "%$q%";
            $args[]  = "%$q%";
            $args[]  = "%$q%";
        }

        $sql = "
            SELECT
                t.ID_Aduana         AS id_oc,
                t.ID_Aduana         AS folio,
                t.Factura           AS factura,
                t.fech_pedimento    AS fecha,
                t.status            AS status,
                t.Cve_Almac         AS cve_almac,
                t.ID_Proveedor      AS id_proveedor,
                p.Nombre            AS proveedor
            FROM th_aduana t
            LEFT JOIN c_proveedor p ON p.ID_Proveedor = t.ID_Proveedor
            WHERE " . implode(" AND ", $where) . "
            ORDER BY t.ID_Aduana DESC
            LIMIT 500
        ";

        $rows = db_all($sql, $args);

        // “text” para dropdowns
        foreach ($rows as &$r) {
            $r['text'] = "OC {$r['folio']} | {$r['proveedor']} | Fact: {$r['factura']}";
        }

        jexit(['ok' => 1, 'data' => $rows]);
    }

    if ($action === 'oc_detalle') {
        // Devuelve proveedor + partidas desde td_aduana
        $id_oc = $_GET['id_oc'] ?? '';
        if ($id_oc === '') jexit(['ok'=>0,'error'=>'Falta id_oc']);

        $head = db_one("
            SELECT
                t.ID_Aduana AS id_oc,
                t.Factura   AS factura,
                t.status    AS status,
                t.Cve_Almac AS cve_almac,
                t.ID_Proveedor AS id_proveedor,
                p.Nombre    AS proveedor
            FROM th_aduana t
            LEFT JOIN c_proveedor p ON p.ID_Proveedor = t.ID_Proveedor
            WHERE t.ID_Aduana = ?
            LIMIT 1
        ", [$id_oc]);

        $items = db_all("
            SELECT
                d.Id_DetAduana AS id_det,
                d.cve_articulo AS cve_articulo,
                d.cantidad     AS cantidad_solicitada,
                d.Cve_Lote     AS lote,
                d.caducidad    AS caducidad,
                d.Ingresado    AS ingresado
            FROM td_aduana d
            WHERE d.ID_Aduana = ?
            ORDER BY d.Id_DetAduana
        ", [$id_oc]);

        jexit(['ok'=>1,'head'=>$head,'items'=>$items]);
    }

    if ($action === 'zonas_recepcion') {
        // Zonas/ubicaciones por almacén (c_ubicacion). Usamos CodigoCSD como BL (regla tuya).
        $cve_almac = $_GET['cve_almac'] ?? '';
        if ($cve_almac === '') jexit(['ok'=>0,'error'=>'Falta cve_almac']);

        $rows = db_all("
            SELECT
                idy_ubica        AS idy_ubica,
                cve_almac        AS cve_almac,
                CodigoCSD        AS bl,
                claverp          AS claverp,
                Status           AS status,
                picking          AS picking,
                AcomodoMixto     AS acomodo_mixto,
                AreaStagging     AS area_stagging,
                AreaProduccion   AS area_produccion
            FROM c_ubicacion
            WHERE cve_almac = ?
              AND (Activo = 1 OR Activo = '1')
            ORDER BY (AreaStagging='S') DESC, CodigoCSD
        ", [$cve_almac]);

        jexit(['ok'=>1,'data'=>$rows]);
    }

    if ($action === 'zonas_destino') {
        // Para CrossDocking: típicamente destino no es stagging, pero lo dejamos flexible.
        $cve_almac = $_GET['cve_almac'] ?? '';
        if ($cve_almac === '') jexit(['ok'=>0,'error'=>'Falta cve_almac']);

        $rows = db_all("
            SELECT
                idy_ubica AS idy_ubica,
                cve_almac AS cve_almac,
                CodigoCSD AS bl,
                AcomodoMixto AS acomodo_mixto,
                AreaStagging AS area_stagging
            FROM c_ubicacion
            WHERE cve_almac = ?
              AND (Activo = 1 OR Activo = '1')
            ORDER BY CodigoCSD
        ", [$cve_almac]);

        jexit(['ok'=>1,'data'=>$rows]);
    }

    if ($action === 'bls_destino') {
        // BL destino = ubicaciones del almacén
        $cve_almac = $_GET['cve_almac'] ?? '';
        if ($cve_almac === '') jexit(['ok'=>0,'error'=>'Falta cve_almac']);

        $rows = db_all("
            SELECT
                idy_ubica AS id,
                CodigoCSD AS bl
            FROM c_ubicacion
            WHERE cve_almac = ?
              AND (Activo = 1 OR Activo = '1')
              AND CodigoCSD IS NOT NULL
              AND CodigoCSD <> ''
            ORDER BY CodigoCSD
        ", [$cve_almac]);

        jexit(['ok'=>1,'data'=>$rows]);
    }

    if ($action === 'guardar') {
        // Guarda header + detalles (vienen como JSON strings en POST)
        // UI manda: header, detalles :contentReference[oaicite:2]{index=2}
        $headerJson   = $_POST['header'] ?? '';
        $detallesJson = $_POST['detalles'] ?? '';

        $header   = json_decode($headerJson, true);
        $detalles = json_decode($detallesJson, true);

        if (!$header || !is_array($header))   jexit(['ok'=>0,'error'=>'Header inválido']);
        if (!$detalles || !is_array($detalles)) jexit(['ok'=>0,'error'=>'Detalles inválidos']);

        // “folio” operativo (si hay OC usamos esa; si no, TMP)
        $folioRef = $header['folio_oc'] ?: ($header['folio_tmp'] ?? ('TMP-' . date('ymd-Hi')));
        $tipo     = strtoupper($header['tipo_recepcion'] ?? 'OC');

        // tablas target
        $hasTH = $tableExists('th_entalmacen');
        $hasTD = $tableExists('td_entalmacen');

        // fallback demo (para que SIEMPRE quede evidencia y puedas ver la corrida)
        if (!$hasTH || !$hasTD) {
            if (!$tableExists('th_entalmacen_demo')) {
                dbq("
                    CREATE TABLE IF NOT EXISTS th_entalmacen_demo (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        folio_ref VARCHAR(50) NOT NULL,
                        tipo VARCHAR(10) NOT NULL,
                        empresa_id VARCHAR(20) NULL,
                        cve_almac VARCHAR(20) NULL,
                        zona_recepcion VARCHAR(50) NULL,
                        proveedor VARCHAR(255) NULL,
                        factura VARCHAR(60) NULL,
                        usuario VARCHAR(100) NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            if (!$tableExists('td_entalmacen_demo')) {
                dbq("
                    CREATE TABLE IF NOT EXISTS td_entalmacen_demo (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        th_id INT NOT NULL,
                        cve_articulo VARCHAR(60) NULL,
                        descripcion VARCHAR(255) NULL,
                        uom VARCHAR(30) NULL,
                        lote_serie VARCHAR(80) NULL,
                        caducidad VARCHAR(20) NULL,
                        cant_solicitada DECIMAL(18,4) NULL,
                        cant_recibida DECIMAL(18,4) NULL,
                        costo DECIMAL(18,4) NULL,
                        contenedor VARCHAR(60) NULL,
                        lp_contenedor VARCHAR(80) NULL,
                        pallet VARCHAR(60) NULL,
                        lp_pallet VARCHAR(80) NULL,
                        zona_recepcion VARCHAR(50) NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        KEY ix_th (th_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($hasTH && $hasTD) {
                // Inserción “real” (si tus tablas existen y son compatibles)
                // Nota: si tu estructura difiere, aquí ajustamos mapping.
                dbq("
                    INSERT INTO th_entalmacen
                        (Fol_Folio, Cve_Almac, Fec_Entrada, tipo, Cve_Usuario, Cve_Proveedor, STATUS, Referencia)
                    VALUES
                        (?, ?, NOW(), ?, ?, ?, 'A', ?)
                ", [
                    $folioRef,
                    $header['cve_almac'] ?? null,
                    $tipo,
                    $header['usuario'] ?? null,
                    $header['proveedor'] ?? null,
                    $header['factura'] ?? null
                ]);

                $thId = db_val("SELECT LAST_INSERT_ID()");

                foreach ($detalles as $d) {
                    dbq("
                        INSERT INTO td_entalmacen
                            (fol_folio, cve_articulo, cve_lote, CantidadPedida, CantidadRecibida, cve_usuario, cve_ubicacion, fecha_inicio, fecha_fin)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ", [
                        $folioRef,
                        $d['cve_articulo'] ?? null,
                        $d['lote_serie'] ?? null,
                        $d['cantidad_solicitada'] ?? 0,
                        $d['cantidad_recibida'] ?? 0,
                        $d['usuario'] ?? null,
                        $header['zona_recepcion'] ?? null
                    ]);
                }

                $pdo->commit();
                jexit(['ok'=>1,'id'=>$thId,'folio'=>$folioRef,'modo'=>'REAL']);

            } else {
                // DEMO persistente (si no existen th_entalmacen/td_entalmacen)
                dbq("
                    INSERT INTO th_entalmacen_demo
                        (folio_ref, tipo, empresa_id, cve_almac, zona_recepcion, proveedor, factura, usuario)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $folioRef,
                    $tipo,
                    $header['empresa_id'] ?? null,
                    $header['cve_almac'] ?? null,
                    $header['zona_recepcion'] ?? null,
                    $header['proveedor'] ?? null,
                    $header['factura'] ?? null,
                    $header['usuario'] ?? null
                ]);

                $thId = db_val("SELECT LAST_INSERT_ID()");

                foreach ($detalles as $d) {
                    dbq("
                        INSERT INTO td_entalmacen_demo
                            (th_id, cve_articulo, descripcion, uom, lote_serie, caducidad,
                             cant_solicitada, cant_recibida, costo, contenedor, lp_contenedor, pallet, lp_pallet, zona_recepcion)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $thId,
                        $d['cve_articulo'] ?? null,
                        $d['descripcion'] ?? null,
                        $d['uom'] ?? null,
                        $d['lote_serie'] ?? null,
                        $d['caducidad'] ?? null,
                        $d['cantidad_solicitada'] ?? 0,
                        $d['cantidad_recibida'] ?? 0,
                        $d['costo'] ?? 0,
                        $d['contenedor'] ?? null,
                        $d['lp_contenedor'] ?? null,
                        $d['pallet'] ?? null,
                        $d['lp_pallet'] ?? null,
                        $header['zona_recepcion'] ?? null
                    ]);
                }

                $pdo->commit();
                jexit(['ok'=>1,'id'=>$thId,'folio'=>$folioRef,'modo'=>'DEMO']);
            }

        } catch (Throwable $e) {
            $pdo->rollBack();
            jexit(['ok'=>0,'error'=>'Error guardando recepción','detalle'=>$e->getMessage()]);
        }
    }

    jexit(['ok'=>0,'error'=>'Acción no soportada', 'action'=>$action]);

} catch (Throwable $e) {
    jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()]);
}
