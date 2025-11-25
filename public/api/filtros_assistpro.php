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

try {
    if ($action === 'init') {
        init_filtros($pdo);
    } else {
        echo json_encode([
            'ok'    => false,
            'error' => 'Acción no soportada en filtros_assistpro.php: ' . $action
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Error general en filtros_assistpro.php: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Acción principal: devolver todos los catálogos necesarios
 * para los filtros estándar AssistPro.
 */
function init_filtros(PDO $pdo): void
{
    // Parámetros opcionales para filtrar algunos catálogos (BL, LP, etc.)
    $empresa = trim($_GET['empresa'] ?? $_POST['empresa'] ?? '');
    $almacen = trim($_GET['almacen'] ?? $_POST['almacen'] ?? '');
    $zona    = trim($_GET['zona']    ?? $_POST['zona']    ?? '');

    $data = [
        'ok'           => true,
        'empresas'     => [],
        'almacenes'    => [],
        'rutas'        => [],
        'clientes'     => [],
        'proveedores'  => [],
        'vendedores'   => [],
        'productos'    => [],
        'bls'          => [],
        'lps'          => [],
        'zonas_recep'  => [],
        'zonas_qa'     => [],
        'zonas_emb'    => [],
        'proyectos'    => [],
        'recetas'      => [],
        'zonas_almacenaje' => [],
        'debug_params' => [
            'empresa' => $empresa,
            'almacen' => $almacen,
            'zona'    => $zona,
        ],
    ];

    // ===================== EMPRESAS (c_compania) =====================
    try {
        $data['empresas'] = db_all("
            SELECT
                cve_cia,
                des_cia,
                clave_empresa
            FROM c_compania
            WHERE IFNULL(Activo, 1) = 1
            ORDER BY des_cia
        ");
    } catch (Throwable $e) {
        $data['empresas_error'] = $e->getMessage();
    }

    // ===================== ALMACENES (c_almacenp) =====================
    // Se exponen almacenes lógicos (padre). Convención:
    // - id_almacenp : PK numérica de c_almacenp
    // - cve_almac   : clave corta tipo WHCR (para combos)
    // - clave_almacen : alias de la misma clave (compatibilidad)
    // - des_almac   : nombre descriptivo
    try {
        $data['almacenes'] = db_all("
            SELECT 
                id         AS id_almacenp,
                clave      AS cve_almac,
                clave      AS clave_almacen,
                nombre     AS des_almac
            FROM c_almacenp
            WHERE IFNULL(Activo,1) = 1
            ORDER BY clave
        ");
    } catch (Throwable $e) {
        $data['almacenes_error'] = $e->getMessage();
    }

    // ===================== RUTAS (t_ruta) =====================
    try {
        $params = [];
        $where  = ["IFNULL(Activo,1) = 1"];

        // Más adelante, si quieres filtrar por almacén lógico, aquí podemos
        // mapear cve_almacenp contra c_almacenp, por ahora traemos todas.
        $sqlRutas = "
            SELECT
                ID_Ruta,
                cve_ruta,
                descripcion,
                cve_almacenp,
                venta_preventa,
                control_pallets_cont,
                IFNULL(Activo,1) AS Activo
            FROM t_ruta
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY descripcion
        ";

        $data['rutas'] = db_all($sqlRutas, $params);
    } catch (Throwable $e) {
        $data['rutas_error'] = $e->getMessage();
    }

    // ===================== Zonas de Almacenaje (c_almacen) =====================
    // Zonas hijas por almacén padre (c_almacenp):
    // - Si se envía $almacen (clave WHCR, WHMX, etc.), filtramos por c_almacenp.clave.
    // - Si no se envía, devolvemos todas las zonas activas.
    try {
        $params = [];
        $where  = ["IFNULL(a.Activo,1) = 1"];

        if ($almacen !== '') {
            $where[]                 = 'ap.clave = :almacen_clave';
            $params['almacen_clave'] = $almacen;
        }

        $sql = "
            SELECT
                a.cve_almac,
                a.clave_almacen,
                a.des_almac,
                a.cve_almacenp,
                ap.clave  AS almac_clave,
                ap.nombre AS almac_nombre,
                a.Cve_TipoZona,
                a.clasif_abc,
                a.ID_Proveedor
            FROM c_almacen a
            LEFT JOIN c_almacenp ap
                   ON ap.id = a.cve_almacenp
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY a.des_almac
        ";

        $data['zonas_almacenaje'] = db_all($sql, $params);
    } catch (Throwable $e) {
        $data['zonas_almacenaje_error'] = $e->getMessage();
    }

    // ===================== CLIENTES (c_cliente) =====================
    try {
        $data['clientes'] = db_all("
            SELECT
                id_cliente,
                Cve_Clte,
                RazonSocial
            FROM c_cliente
            WHERE IFNULL(Activo, 1) = 1
            ORDER BY RazonSocial
            LIMIT 2000
        ");
    } catch (Throwable $e) {
        $data['clientes_error'] = $e->getMessage();
    }

    // ===================== PROVEEDORES (c_proveedores) =====================
    try {
        $data['proveedores'] = db_all("
            SELECT
                ID_Proveedor,
                cve_proveedor,
                Nombre
            FROM c_proveedores
            WHERE IFNULL(Activo, 1) = 1
            ORDER BY Nombre
        ");
    } catch (Throwable $e) {
        $data['proveedores_error'] = $e->getMessage();
    }

    // ===================== VENDEDORES (t_vendedores) =====================
    try {
        $data['vendedores'] = db_all("
            SELECT
                Id_Vendedor,
                Cve_Vendedor,
                Nombre
            FROM t_vendedores
            WHERE IFNULL(Activo, 1) = 1
            ORDER BY Nombre
        ");
    } catch (Throwable $e) {
        $data['vendedores_error'] = $e->getMessage();
    }

    // ===================== PRODUCTOS (c_articulo) =====================
    try {
        $params = [];
        $where  = [];

        // Si quisieras filtrar por almacén (cve_almac en c_articulo)
        if ($almacen !== '') {
            $where[]           = 'cve_almac = :almacen';
            $params['almacen'] = $almacen;
        }

        $sqlProd = "
            SELECT
                cve_articulo,
                des_articulo,
                cve_almac,
                ID_Proveedor,
                mav_obsoleto
            FROM c_articulo
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY des_articulo
            LIMIT 5000
        ";
        $data['productos'] = db_all($sqlProd, $params);
    } catch (Throwable $e) {
        $data['productos_error'] = $e->getMessage();
    }

    // ===================== BL / BIN LOCATIONS (c_ubicacion) =====================
    // BL = Bin Locations. Convenciones de filtro:
    // - Si se envía $zona, filtramos por cve_almac = zona (zona específica).
    // - Si no hay zona pero sí $almacen, se asume que almacén ya viene como cve_almac.
    try {
        $params = [];
        $where  = ["IFNULL(Activo,1) = 1", "IFNULL(CodigoCSD,'') <> ''"];

        if ($zona !== '') {
            $where[]         = 'cve_almac = :zona';
            $params['zona']  = $zona;
        } elseif ($almacen !== '') {
            $where[]          = 'cve_almac = :almacen';
            $params['almacen'] = $almacen;
        }

        $sqlBl = "
            SELECT
                idy_ubica,
                cve_almac,
                CodigoCSD      AS bl,
                cve_pasillo    AS pasillo,
                cve_rack       AS rack,
                cve_nivel      AS nivel,
                Seccion        AS seccion,
                Status,
                picking,
                AreaProduccion,
                AreaStagging,
                clasif_abc
            FROM c_ubicacion
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY CodigoCSD
            LIMIT 5000
        ";

        $data['bls'] = db_all($sqlBl, $params);
    } catch (Throwable $e) {
        $data['bls_error'] = $e->getMessage();
    }

    // ===================== LICENSE PLATE / CONTENEDORES (c_charolas) =====================
    try {
        $params = [];
        $where  = ["IFNULL(Activo,1) = 1"];

        if ($almacen !== '') {
            $where[]           = 'cve_almac = :almacen';
            $params['almacen'] = $almacen;
        }

        $sqlLp = "
            SELECT
                IDContenedor,
                cve_almac,
                CveLP,
                Clave_Contenedor,
                descripcion,
                tipo,
                Permanente
            FROM c_charolas
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY CveLP
            LIMIT 5000
        ";
        $data['lps'] = db_all($sqlLp, $params);
    } catch (Throwable $e) {
        $data['lps_error'] = $e->getMessage();
    }

    // ===================== ZONA RECEPCIÓN / RETENCIÓN (tubicacionesretencion) =====================
    try {
        $data['zonas_recep'] = db_all("
            SELECT
                id,
                cve_ubicacion,
                cve_almacp,
                desc_ubicacion,
                AreaStagging,
                B_Devolucion
            FROM tubicacionesretencion
            WHERE IFNULL(Activo,1) = 1
            ORDER BY desc_ubicacion
        ");
    } catch (Throwable $e) {
        $data['zonas_recep_error'] = $e->getMessage();
    }

    // ===================== ZONA QA / REVISIÓN (t_ubicaciones_revision) =====================
    try {
        $data['zonas_qa'] = db_all("
            SELECT
                ID_URevision,
                cve_almac,
                cve_ubicacion,
                descripcion,
                AreaStagging
            FROM t_ubicaciones_revision
            WHERE IFNULL(Activo,1) = 1
            ORDER BY descripcion
        ");
    } catch (Throwable $e) {
        $data['zonas_qa_error'] = $e->getMessage();
    }

    // ===================== ZONA EMBARQUES (t_ubicacionembarque) =====================
    try {
        $data['zonas_emb'] = db_all("
            SELECT
                ID_Embarque,
                cve_ubicacion
            FROM t_ubicacionembarque
            ORDER BY ID_Embarque
        ");
    } catch (Throwable $e) {
        $data['zonas_emb_error'] = $e->getMessage();
    }

    // ===================== PROYECTOS (c_proyecto) =====================
    try {
        $params = [];
        $where  = [];

        if ($almacen !== '') {
            $where[]            = 'id_almacen = :almacen';
            $params['almacen']  = $almacen;
        }

        $sqlProy = "
            SELECT
                Id,
                Cve_Proyecto,
                Des_Proyecto,
                id_almacen
            FROM c_proyecto
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY Des_Proyecto
        ";

        $data['proyectos'] = db_all($sqlProy, $params);
    } catch (Throwable $e) {
        $data['proyectos_error'] = $e->getMessage();
    }

    // ===================== RECETAS / PLANTILLAS (ap_plantillas_filtros) =====================
    try {
        // Opcional: si no existe la tabla, saltará al catch
        $rows = db_all("
            SELECT
                id,
                modulo,
                nombre,
                vista_sql,
                es_default,
                activo
            FROM ap_plantillas_filtros
            ORDER BY modulo, es_default DESC, nombre
        ");

        $recetas = [];
        foreach ($rows as $r) {
            $recetas[] = [
                'id'         => (int)$r['id'],
                'modulo'     => $r['modulo'],
                'nombre'     => $r['nombre'],
                'vista_sql'  => $r['vista_sql'],
                'es_default' => (int)($r['es_default'] ?? 0),
                'activo'     => (int)($r['activo'] ?? 1),
            ];
        }
        $data['recetas'] = $recetas;
    } catch (Throwable $e) {
        $data['recetas_error'] = $e->getMessage();
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
