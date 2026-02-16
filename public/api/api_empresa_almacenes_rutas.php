<?php
// public/api/filtros_assistpro.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/auth_check.php';
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
    }

    // ===================== ALMACENES (c_almacenp) =====================
    if ($useSection('almacenes')) {
        try {
            $paramsAlm = [];
            $whereAlm = ["IFNULL(a.Activo,1) = 1"];

            // Filtrar por empresa si viene parámetro
            if ($empresa !== '') {
                $whereAlm[] = "a.cve_cia = :empresa_id";
                $paramsAlm['empresa_id'] = $empresa;
            }

            // importante: para relacionar zonas de recepcion (tubicacionesretencion.cve_almacp)
            // necesitamos exponer el id numerico del almacen (c_almacenp.id) ademas de la clave (wh8)
            $sqlAlm = "
                SELECT 
                    a.id    AS idp,
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
