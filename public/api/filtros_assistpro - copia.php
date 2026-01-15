<?php
// public/api/filtros_assistpro.php
header('Content-Type: application/json; charset=utf-8');

//require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
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
            'ok' => false,
            'error' => 'Acción no soportada en filtros_assistpro.php: ' . $action
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
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
    $zona = trim($_GET['zona'] ?? $_POST['zona'] ?? '');

    // Secciones a cargar (coma separadas). Ej: empresas,almacenes,rutas
    $secciones_raw = trim($_GET['secciones'] ?? $_POST['secciones'] ?? '');
    $secciones = [];
    if ($secciones_raw !== '') {
        foreach (explode(',', $secciones_raw) as $sec) {
            $sec = strtolower(trim($sec));
            if ($sec !== '') {
                $secciones[$sec] = true;
            }
        }
    }

    // Helper: indica si se debe cargar una sección ('empresas', 'almacenes', 'rutas', etc.)
    $useSection = function ($name) use ($secciones) {
        if (empty($secciones)) {
            // Si no se especifican secciones, cargamos todas (comportamiento original).
            return true;
        }
        return isset($secciones[strtolower($name)]);
    };

    $data = [
        'ok' => true,
        'empresas' => [],
        'almacenes' => [],
        'rutas' => [],
        'clientes' => [],
        'proveedores' => [],
        'vendedores' => [],
        'productos' => [],
        'bls' => [],
        'lps' => [],
        'zonas_recep' => [],
        'zonas_qa' => [],
        'zonas_emb' => [],
        'zonas_almacenaje' => [],
        'proyectos' => [],
        'recetas' => [],
        'debug_params' => [
            'empresa' => $empresa,
            'almacen' => $almacen,
            'zona' => $zona,
        ],
    ];

    // ===================== EMPRESAS (c_compania) =====================
    if ($useSection('empresas')) {
        try {
            // Filtrar empresas según almacenes asignados al usuario
            $user = $_SESSION['username'] ?? '';
            $paramsEmp = [];
            $whereEmp = ["IFNULL(c.Activo, 1) = 1"];

            if ($user !== '') {
                // Join con almacenes del usuario para ver qué empresas puede ver
                // Asumiendo que c_almacenp tiene empresa_id o similar, o usando la lógica inversa
                // Si no hay relación directa usuario-empresa, usamos la relación usuario-almacén-empresa
                // Ajuste: Traer empresas que tengan al menos un almacén asignado al usuario
                $sqlEmp = "
                    SELECT DISTINCT
                        c.cve_cia,
                        c.des_cia,
                        c.clave_empresa
                    FROM c_compania c
                    INNER JOIN c_almacenp a ON a.cve_cia = c.cve_cia
                    LEFT JOIN trel_us_alm t ON t.cve_almac = a.clave
                    LEFT JOIN t_usu_alm_pre p ON p.cve_almac = a.clave
                    WHERE IFNULL(c.Activo, 1) = 1
                      AND (
                           (t.cve_usuario = :u1 AND IFNULL(t.Activo,'1') IN ('1','S','SI','TRUE'))
                        OR (p.id_user = :u2)
                      )
                    ORDER BY c.des_cia
                ";
                $paramsEmp['u1'] = $user;
                $paramsEmp['u2'] = $user;
            } else {
                // Fallback si no hay usuario (no debería pasar por auth_check)
                $sqlEmp = "SELECT cve_cia, des_cia, clave_empresa FROM c_compania WHERE IFNULL(Activo, 1) = 1 ORDER BY des_cia";
            }

            $data['empresas'] = db_all($sqlEmp, $paramsEmp);
        } catch (Throwable $e) {
            $data['empresas_error'] = $e->getMessage();
        }
    }

    // ===================== ALMACENES (c_almacenp) =====================
    if ($useSection('almacenes')) {
        try {
            $user = $_SESSION['username'] ?? '';
            $paramsAlm = [];
            $whereAlm = ["IFNULL(a.Activo,1) = 1"];

            // Filtrar por empresa si viene parámetro
            if ($empresa !== '') {
                $whereAlm[] = "a.cve_cia = :empresa_id";
                $paramsAlm['empresa_id'] = $empresa;
            }

            // Filtrar por usuario
            if ($user !== '') {
                $whereAlm[] = "
                    (
                        EXISTS (SELECT 1 FROM trel_us_alm t WHERE t.cve_almac = a.clave AND t.cve_usuario = :u1 AND IFNULL(t.Activo,'1') IN ('1','S','SI','TRUE'))
                        OR
                        EXISTS (SELECT 1 FROM t_usu_alm_pre p WHERE p.cve_almac = a.clave AND p.id_user = :u2)
                    )
                ";
                $paramsAlm['u1'] = $user;
                $paramsAlm['u2'] = $user;
            }

            $sqlAlm = "
                SELECT 
                    a.clave AS cve_almac,
                    a.clave AS clave_almacen,
                    a.clave AS des_almac,
                    a.nombre
                FROM c_almacenp a
                " . (count($whereAlm) ? 'WHERE ' . implode(' AND ', $whereAlm) : '') . "
                ORDER BY a.clave
            ";

            $data['almacenes'] = db_all($sqlAlm, $paramsAlm);
        } catch (Throwable $e) {
            $data['almacenes_error'] = $e->getMessage();
        }
    }

    // ===================== RUTAS (t_ruta) =====================
    if ($useSection('rutas')) {
        try {
            $params = [];
            $where = ["IFNULL(Activo,1) = 1"];

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
    }

    // ===================== Zonas de Almacenaje (c_almacen) =====================
    if ($useSection('zonas_almacenaje')) {
        try {
            $params = [];
            $where = ["IFNULL(Activo,1) = 1"];

            $sql = "
                SELECT
                    cve_almac,
                    clave_almacen,
                    des_almac,
                    cve_almacenp,
                    Cve_TipoZona,
                    clasif_abc,
                    ID_Proveedor
                FROM c_almacen
                " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
                ORDER BY des_almac
            ";

            $data['zonas_almacenaje'] = db_all($sql, $params);
        } catch (Throwable $e) {
            $data['zonas_almacenaje_error'] = $e->getMessage();
        }
    }

    // ===================== CLIENTES (c_cliente) =====================
    if ($useSection('clientes')) {
        try {
            $data['clientes'] = db_all("
                SELECT
                    id_cliente,
                    Cve_Clte,
                    RazonSocial,
                    RFC,
                    IFNULL(Activo,1) AS Activo
                FROM c_cliente
                WHERE IFNULL(Activo,1) = 1
                ORDER BY RazonSocial
            ");
        } catch (Throwable $e) {
            $data['clientes_error'] = $e->getMessage();
        }
    }

    // ===================== PROVEEDORES (c_proveedores) =====================
    if ($useSection('proveedores')) {
        try {
            $data['proveedores'] = db_all("
                SELECT
                    ID_Prov,
                    Cve_Prov,
                    RazonSocial,
                    RFC,
                    IFNULL(Activo,1) AS Activo
                FROM c_proveedores
                WHERE IFNULL(Activo,1) = 1
                ORDER BY RazonSocial
            ");
        } catch (Throwable $e) {
            $data['proveedores_error'] = $e->getMessage();
        }
    }

    // ===================== VENDEDORES (t_vendedores) =====================
    if ($useSection('vendedores')) {
        try {
            $data['vendedores'] = db_all("
                SELECT
                    id_vendedor,
                    Cve_Vend,
                    Nombre,
                    IFNULL(Activo,1) AS Activo
                FROM t_vendedores
                WHERE IFNULL(Activo,1) = 1
                ORDER BY Nombre
            ");
        } catch (Throwable $e) {
            $data['vendedores_error'] = $e->getMessage();
        }
    }

    // ===================== PRODUCTOS (c_articulo) =====================
if ($useSection('productos')) {
    try {
        $params = [];
        $where  = ["IFNULL(Activo,1) = 1"];

        $sqlProd = "
            SELECT
                id_articulo,
                cve_articulo,
                descripcion,          -- descripción comercial
                sku_cliente,
                UM,                   -- unidad de medida
                -- banderas de control de lote / serie / caducidad
                B_Lote,
                B_Serie,
                B_Caducidad,
                IFNULL(Activo,1) AS Activo
            FROM c_articulo
            " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY descripcion
            LIMIT 5000
        ";

        $data['productos'] = db_all($sqlProd, $params);
    } catch (Throwable $e) {
        $data['productos_error'] = $e->getMessage();
    }
}



    // ===================== BL / BIN LOCATIONS (c_ubicacion) =====================
    if ($useSection('bls')) {
        try {
            $params = [];
            $where = ["IFNULL(Activo,1) = 1"];

            if ($almacen !== '') {
                $where[] = "cve_almac = :almacen";
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
                    AreaProduc,
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
    }

    // ===================== LICENSE PLATE / CONTENEDORES (c_charolas) =====================
    if ($useSection('lps')) {
        try {
            $params = [];
            $where = ["IFNULL(Activo,1) = 1"];

            $sqlLp = "
                SELECT
                    id_charola,
                    codigo,
                    es_pallet,
                    es_contenedor,
                    generica,
                    IFNULL(Activo,1) AS Activo
                FROM c_charolas
                " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
                ORDER BY codigo
                LIMIT 5000
            ";

            $data['lps'] = db_all($sqlLp, $params);
        } catch (Throwable $e) {
            $data['lps_error'] = $e->getMessage();
        }
    }

    // ===================== ZONA RECEPCIÓN / RETENCIÓN (tubicacionesretencion) =====================
if ($useSection('zonas_recep')) {
    try {
        $data['zonas_recep'] = db_all("
            SELECT
                id             AS ID_URecepcion,
                cve_almacp     AS cve_almac,
                cve_ubicacion,
                desc_ubicacion AS descripcion,
                AreaStagging
            FROM tubicacionesretencion
            WHERE IFNULL(Activo,1) = 1
            ORDER BY desc_ubicacion
        ");
    } catch (Throwable $e) {
        $data['zonas_recep_error'] = $e->getMessage();
    }
}


    // ===================== ZONA QA / REVISIÓN (t_ubicaciones_revision) =====================
    if ($useSection('zonas_qa')) {
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
    }

    // ===================== ZONA EMBARQUES (t_ubicacionembarque) =====================
    if ($useSection('zonas_emb')) {
        try {
            $data['zonas_emb'] = db_all("
                SELECT
                    ID_UEmbarque,
                    cve_almac,
                    cve_ubicacion,
                    descripcion,
                    AreaStagging
                FROM t_ubicacionembarque
                WHERE IFNULL(Activo,1) = 1
                ORDER BY descripcion
            ");
        } catch (Throwable $e) {
            $data['zonas_emb_error'] = $e->getMessage();
        }
    }

    // ===================== PROYECTOS (c_proyecto) =====================
    if ($useSection('proyectos')) {
        try {
            $params = [];
            $where = ["IFNULL(Activo,1) = 1"];

            $sqlProy = "
                SELECT
                    Id_Proyecto,
                    Cve_Proyecto,
                    Des_Proyecto,
                    IFNULL(Activo,1) AS Activo
                FROM c_proyecto
                " . (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . "
                ORDER BY Des_Proyecto
            ";

            $data['proyectos'] = db_all($sqlProy, $params);
        } catch (Throwable $e) {
            $data['proyectos_error'] = $e->getMessage();
        }
    }

    // ===================== RECETAS / PLANTILLAS (ap_plantillas_filtros) =====================
    if ($useSection('recetas')) {
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
                    'id' => (int) $r['id'],
                    'modulo' => $r['modulo'],
                    'nombre' => $r['nombre'],
                    'vista_sql' => $r['vista_sql'],
                    'es_default' => (int) ($r['es_default'] ?? 0),
                    'activo' => (int) ($r['activo'] ?? 1),
                ];
            }
            $data['recetas'] = $recetas;
        } catch (Throwable $e) {
            $data['recetas_error'] = $e->getMessage();
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
