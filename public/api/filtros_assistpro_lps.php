<?php
// public/api/filtros_assistpro.php
// API utilitaria de filtros AssistPro (sin cambiar lookups de LP/BL).
// Secciones: empresas, almacenes, rutas, productos, proveedores, vendedores, bls, lps,
//           zonas_recep, zonas_qa, zonas_emb, zonas_prod

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php';

function qget($k, $def=''){ return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $def; }

$sections = qget('sections','');
$sections = $sections !== '' ? array_filter(array_map('trim', explode(',', $sections))) : [];
$almacen  = qget('almacen','');  // puede venir num o clave; aquí lo tratamos como string
$empresa  = qget('empresa','');

$useAll = empty($sections);
$useSection = function($name) use ($useAll, $sections){
    return $useAll || in_array($name, $sections, true);
};

$data = ["ok"=>true];

try {

    // ===================== EMPRESAS (c_compania) =====================
    if ($useSection('empresas')) {
        try {
            $data['empresas'] = db_all("
                SELECT cve_cia, des_cia, clave_empresa
                FROM c_compania
                WHERE IFNULL(Activo,1)=1
                ORDER BY des_cia
            ");
        } catch (Throwable $e) { $data['empresas_error']=$e->getMessage(); }
    }

    // ===================== ALMACENES (c_almacenp) - legacy/operativo =====================
    if ($useSection('almacenes')) {
        try {
            $user = $_SESSION['username'] ?? '';
            $params = [];
            $where  = ["IFNULL(a.Activo,1) IN (1,'1','S','SI','TRUE')"];

            if ($empresa !== '') {
                $where[] = "a.cve_cia = :empresa";
                $params['empresa'] = $empresa;
            }

            // Si hay sesión, filtra por permisos de usuario (si no hay sesión, devuelve todo activo)
            if ($user !== '') {
                $where[] = "(
                    EXISTS (SELECT 1 FROM trel_us_alm t
                            WHERE t.cve_almac = a.clave
                              AND t.cve_usuario = :u1
                              AND IFNULL(t.Activo,'1') IN ('1','S','SI','TRUE'))
                    OR
                    EXISTS (SELECT 1 FROM t_usu_alm_pre p
                            WHERE p.cve_almac = a.clave
                              AND p.id_user = :u2)
                )";
                $params['u1']=$user;
                $params['u2']=$user;
            }

            $sql = "
                SELECT
                    a.clave  AS cve_almac,
                    a.clave  AS clave_almacen,
                    a.nombre AS nombre
                FROM c_almacenp a
                ".(count($where) ? "WHERE ".implode(" AND ",$where) : "")."
                ORDER BY a.clave
            ";
            $data['almacenes'] = db_all($sql, $params);
        } catch (Throwable $e) { $data['almacenes_error']=$e->getMessage(); }
    }

    // ===================== RUTAS (t_ruta) =====================
    if ($useSection('rutas')) {
        try {
            $data['rutas'] = db_all("
                SELECT
                    ID_Ruta, cve_ruta, descripcion, cve_almacenp,
                    venta_preventa, control_pallets_cont,
                    IFNULL(Activo,1) AS Activo
                FROM t_ruta
                WHERE IFNULL(Activo,1)=1
                ORDER BY descripcion
            ");
        } catch (Throwable $e) { $data['rutas_error']=$e->getMessage(); }
    }

    // ===================== PROVEEDORES (c_proveedores) =====================
    if ($useSection('proveedores')) {
        try {
            $data['proveedores'] = db_all("
                SELECT ID_Prov, Cve_Prov, RazonSocial, RFC, IFNULL(Activo,1) AS Activo
                FROM c_proveedores
                WHERE IFNULL(Activo,1)=1
                ORDER BY RazonSocial
            ");
        } catch (Throwable $e) { $data['proveedores_error']=$e->getMessage(); }
    }

    // ===================== VENDEDORES (t_vendedores) =====================
    if ($useSection('vendedores')) {
        try {
            $data['vendedores'] = db_all("
                SELECT id_vendedor, Cve_Vend, Nombre, IFNULL(Activo,1) AS Activo
                FROM t_vendedores
                WHERE IFNULL(Activo,1)=1
                ORDER BY Nombre
            ");
        } catch (Throwable $e) { $data['vendedores_error']=$e->getMessage(); }
    }

    // ===================== PRODUCTOS (c_articulo) =====================
    if ($useSection('productos')) {
        try {
            $data['productos'] = db_all("
                SELECT
                    id_articulo, cve_articulo, descripcion, sku_cliente, UM,
                    B_Lote, B_Serie, B_Caducidad,
                    IFNULL(Activo,1) AS Activo
                FROM c_articulo
                WHERE IFNULL(Activo,1)=1
                ORDER BY descripcion
                LIMIT 5000
            ");
        } catch (Throwable $e) { $data['productos_error']=$e->getMessage(); }
    }

    // ===================== BLs (c_ubicacion) =====================
    if ($useSection('bls')) {
        try {
            $params = [];
            $where  = ["IFNULL(Activo,1)=1", "IFNULL(CodigoCSD,'')<>''"];

            if ($almacen !== '') {
                // aquí se espera cve_almac num. Si llega clave, no filtra.
                if (ctype_digit($almacen)) {
                    $where[] = "cve_almac = :alm";
                    $params['alm'] = (int)$almacen;
                }
            }

            $data['bls'] = db_all("
                SELECT
                    idy_ubica, cve_almac,
                    CodigoCSD AS bl,
                    cve_pasillo AS pasillo, cve_rack AS rack, cve_nivel AS nivel,
                    Seccion AS seccion,
                    Status, picking,
                    AreaProduccion, AreaStagging, AcomodoMixto, Ptl,
                    clasif_abc
                FROM c_ubicacion
                ".(count($where) ? "WHERE ".implode(" AND ",$where) : "")."
                ORDER BY CodigoCSD
                LIMIT 5000
            ", $params);
        } catch (Throwable $e) { $data['bls_error']=$e->getMessage(); }
    }

    // ===================== LPs base (c_charolas) =====================
    if ($useSection('lps')) {
        try {
            $data['lps'] = db_all("
                SELECT
                    id_charola, codigo, es_pallet, es_contenedor, generica,
                    IFNULL(Activo,1) AS Activo
                FROM c_charolas
                WHERE IFNULL(Activo,1)=1
                ORDER BY codigo
                LIMIT 5000
            ");
        } catch (Throwable $e) { $data['lps_error']=$e->getMessage(); }
    }

    // ===================== ZONA RECEPCIÓN / RETENCIÓN (tubicacionesretencion) =====================
    if ($useSection('zonas_recep')) {
        try {
            $params=[];
            $where=["IFNULL(Activo,1)=1", "IFNULL(cve_ubicacion,'')<>''"];
            if ($almacen !== '' && ctype_digit($almacen)) {
                $where[]="cve_almacp = :alm";
                $params['alm']=(int)$almacen;
            }
            $data['zonas_recep'] = db_all("
                SELECT
                    id AS id_zona,
                    cve_almacp AS cve_almac,
                    cve_ubicacion,
                    desc_ubicacion AS descripcion,
                    AreaStagging
                FROM tubicacionesretencion
                ".(count($where) ? "WHERE ".implode(" AND ",$where) : "")."
                ORDER BY desc_ubicacion
            ", $params);
        } catch (Throwable $e) { $data['zonas_recep_error']=$e->getMessage(); }
    }

    // ===================== ZONA QA / REVISIÓN (t_ubicaciones_revision) =====================
    if ($useSection('zonas_qa')) {
        try {
            $params=[];
            $where=["IFNULL(Activo,1)=1", "IFNULL(cve_ubicacion,'')<>''"];
            if ($almacen !== '' && ctype_digit($almacen)) {
                $where[]="cve_almac = :alm";
                $params['alm']=(int)$almacen;
            }
            $data['zonas_qa'] = db_all("
                SELECT
                    ID_URevision AS id_zona,
                    cve_almac,
                    cve_ubicacion,
                    descripcion,
                    AreaStagging
                FROM t_ubicaciones_revision
                ".(count($where) ? "WHERE ".implode(" AND ",$where) : "")."
                ORDER BY descripcion
            ", $params);
        } catch (Throwable $e) { $data['zonas_qa_error']=$e->getMessage(); }
    }

    // ===================== ZONA EMBARQUES (t_ubicacionembarque) =====================
    if ($useSection('zonas_emb')) {
        try {
            $params=[];
            $where=["IFNULL(Activo,1)=1", "IFNULL(cve_ubicacion,'')<>''"];
            if ($almacen !== '' && ctype_digit($almacen)) {
                $where[]="cve_almac = :alm";
                $params['alm']=(int)$almacen;
            }
            $data['zonas_emb'] = db_all("
                SELECT
                    ID_Embarque AS id_zona,
                    cve_almac,
                    cve_ubicacion,
                    descripcion,
                    AreaStagging
                FROM t_ubicacionembarque
                ".(count($where) ? "WHERE ".implode(" AND ",$where) : "")."
                ORDER BY descripcion
            ", $params);
        } catch (Throwable $e) { $data['zonas_emb_error']=$e->getMessage(); }
    }

    // ===================== ZONA PRODUCCIÓN (c_ubicacion.AreaProduccion='S') =====================
    if ($useSection('zonas_prod')) {
        try {
            $params=[];
            $where=["IFNULL(Activo,1)=1", "IFNULL(CodigoCSD,'')<>''", "IFNULL(AreaProduccion,'N')='S'"];
            if ($almacen !== '' && ctype_digit($almacen)) {
                $where[]="cve_almac = :alm";
                $params['alm']=(int)$almacen;
            }
            $data['zonas_prod'] = db_all("
                SELECT
                    idy_ubica AS id_zona,
                    cve_almac,
                    CodigoCSD AS cve_ubicacion,
                    CONCAT('Producción · ', CodigoCSD) AS descripcion,
                    AreaStagging
                FROM c_ubicacion
                ".(count($where) ? "WHERE ".implode(" AND ",$where) : "")."
                ORDER BY CodigoCSD
                LIMIT 1000
            ", $params);
        } catch (Throwable $e) { $data['zonas_prod_error']=$e->getMessage(); }
    }

} catch (Throwable $e) {
    $data = ["ok"=>false, "msg"=>$e->getMessage()];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
