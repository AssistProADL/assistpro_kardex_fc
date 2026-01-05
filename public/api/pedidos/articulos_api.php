<?php
// public/api/pedidos/articulos_api.php
// API Artículos con precio por destinatario (lista de precios)
// GET params:
//   action=search (default)
//   q=texto
//   cve_almac=38
//   id_destinatario=330   (para obtener ListaP desde relclilis)
//   limit=50
//   debug=1

header('Content-Type: application/json; charset=utf-8');

$__base = dirname(__DIR__, 3); // /public/api/pedidos -> /public -> project root
require_once $__base . '/app/db.php';

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$action = $_GET['action'] ?? 'search';
$q = trim((string)($_GET['q'] ?? ''));
$cveAlmac = (int)($_GET['cve_almac'] ?? 0);
$idDest = (int)($_GET['id_destinatario'] ?? 0);
$limit = (int)($_GET['limit'] ?? 50);
$limit = ($limit <= 0 || $limit > 200) ? 50 : $limit;
$debug = (int)($_GET['debug'] ?? 0);

try {
    // Blindaje collation por sesión (ayuda, no rompe)
    // Si tu db.php ya lo hace, no pasa nada.
    dbq("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    if ($action !== 'search') {
        jexit(["ok"=>0,"error"=>"Acción no soportada","debug"=>$debug?$_GET:null]);
    }

    // 1) Resolver ListaId (ListaP) del destinatario (si aplica)
    $listaId = 0;
    if ($idDest > 0) {
        // relclilis: Id_Destinatario, ListaP
        $listaId = (int) db_val("
            SELECT COALESCE(ListaP,0)
            FROM relclilis
            WHERE Id_Destinatario = :id
            LIMIT 1
        ", ["id"=>$idDest]);
    }

    // 2) Armar búsqueda
    // OJO: Estructura mínima asumida en c_articulo:
    //   - Cve_Articulo (clave)
    //   - Descripcion (texto)
    //   - Activo (1/0) o Activo/Activo = 1
    //
    // Si en tu tabla el nombre difiere (ej. cve_ssgpoart / des_ssgpoart),
    // dime y lo ajusto en 2 minutos, pero la lógica queda igual.

    $where = [];
    $params = [];

    // Activos
    $where[] = "(a.Activo = 1 OR a.Activo IS NULL)";

    // Filtro por texto
    if ($q !== '') {
        $where[] = "(a.Cve_Articulo LIKE :q OR a.Descripcion LIKE :q)";
        $params["q"] = "%".$q."%";
    }

    // Siempre pedimos cve_almac para amarrar precios y/o catálogo por almacén
    if ($cveAlmac > 0) {
        $params["cve_almac"] = $cveAlmac;
    }

    // 3) Traer artículos + precio de lista (si hay listaId y cve_almac)
    // detallelp: ListaId, Cve_Articulo, PrecioMin, PrecioMax, Cve_Almac

    $sql = "
        SELECT
            a.Cve_Articulo AS cve,
            a.Descripcion AS descripcion,
            COALESCE(lp.PrecioMin, 0) AS precio,
            COALESCE(lp.PrecioMax, 0) AS precio_max,
            ".($cveAlmac>0 ? ":cve_almac" : "0")." AS cve_almac,
            ".($listaId>0 ? ":lista_id" : "0")." AS lista_id,
            CONCAT(a.Cve_Articulo,' - ',a.Descripcion) AS label
        FROM c_articulo a
        LEFT JOIN detallelp lp
          ON lp.Cve_Articulo COLLATE utf8mb4_unicode_ci = a.Cve_Articulo COLLATE utf8mb4_unicode_ci
         ".($listaId>0 ? " AND lp.ListaId = :lista_id " : "")."
         ".($cveAlmac>0 ? " AND lp.Cve_Almac = :cve_almac " : "")."
        ".(count($where) ? " WHERE ".implode(" AND ", $where) : "")."
        ORDER BY a.Cve_Articulo
        LIMIT {$limit}
    ";

    if ($listaId > 0) $params["lista_id"] = $listaId;

    $rows = db_all($sql, $params);

    jexit([
        "ok"=>1,
        "msg"=>"ok",
        "data"=>$rows,
        "meta"=>[
            "q"=>$q,
            "cve_almac"=>$cveAlmac,
            "id_destinatario"=>$idDest,
            "lista_id"=>$listaId,
            "limit"=>$limit
        ],
        "debug"=>$debug?["sql"=>$sql,"params"=>$params]:null
    ]);

} catch (Throwable $e) {
    jexit([
        "ok"=>0,
        "error"=>"Error servidor",
        "detalle"=>$e->getMessage(),
        "debug"=>$debug?["get"=>$_GET]:null
    ]);
}
