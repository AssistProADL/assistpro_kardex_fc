<?php
// public/api/articulos_lookup.php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jexit($arr, $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
function clean($v){ return trim((string)$v); }
function nint($v){ $v=clean($v); return ($v===''? null : (int)$v); }

try{
  $pdo = db_pdo();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $action = $_GET['action'] ?? $_POST['action'] ?? 'search';

  // =========================
  // SEARCH (typeahead / datalist / select2)
  // =========================
  if($action === 'search'){
    $q         = clean($_GET['q'] ?? '');
    $cve_almac = nint($_GET['cve_almac'] ?? null); // recomendado
    $cve_clte  = clean($_GET['cve_clte'] ?? '');   // V2 lista precios
    $limit     = (int)($_GET['limit'] ?? 30);
    if($limit <= 0 || $limit > 100) $limit = 30;

    $where = ["COALESCE(a.Activo,1)=1"];
    $p = [];

    // Si viene almacén, filtra por almacén (estilo OC)
    if($cve_almac !== null){
      $where[] = "a.cve_almac = :alm";
      $p[':alm'] = $cve_almac;
    }

    if($q !== ''){
      $where[] = "(
        a.cve_articulo LIKE :q OR
        a.des_articulo LIKE :q OR
        COALESCE(a.barras2,'') LIKE :q OR
        COALESCE(a.barras3,'') LIKE :q OR
        COALESCE(a.Cve_SAP,'') LIKE :q OR
        COALESCE(a.cve_alt,'') LIKE :q
      )";
      $p[':q'] = "%$q%";
    }

    // =========================
    // PRECIO (base hoy = PrecioVenta)
    // V2: aquí se puede resolver por lista de precios por cliente:
    //   - identificar lista desde c_cliente
    //   - join a tabla de precios (si existe) y sustituir PrecioVenta
    // =========================
    $sql = "
      SELECT
        a.id,
        a.cve_almac,
        a.cve_articulo,
        a.des_articulo,
        a.cve_umed,
        a.mav_pctiva,
        a.PrecioVenta,
        a.imp_costo,
        a.barras2,
        a.barras3
      FROM c_articulo a
      WHERE " . implode(" AND ", $where) . "
      ORDER BY
        CASE WHEN a.cve_articulo = :exact THEN 0 ELSE 1 END,
        a.des_articulo
      LIMIT $limit
    ";
    $p[':exact'] = $q;

    $st = $pdo->prepare($sql);
    $st->execute($p);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // Formato amigable para UI (datalist/select)
    $out = [];
    foreach($rows as $r){
      $label = trim(($r['cve_articulo'] ?? '').' - '.($r['des_articulo'] ?? ''));
      $out[] = [
        'id'          => (int)$r['id'],
        'cve_almac'   => isset($r['cve_almac']) ? (int)$r['cve_almac'] : null,
        'cve'         => $r['cve_articulo'],
        'descripcion' => $r['des_articulo'],
        'label'       => $label,
        'cve_umed'    => isset($r['cve_umed']) ? (int)$r['cve_umed'] : null,
        'iva_pct'     => isset($r['mav_pctiva']) ? (float)$r['mav_pctiva'] : 0,
        'precio'      => isset($r['PrecioVenta']) ? (float)$r['PrecioVenta'] : 0,
        'costo'       => isset($r['imp_costo']) ? (float)$r['imp_costo'] : 0,
        'barras2'     => $r['barras2'] ?? null,
        'barras3'     => $r['barras3'] ?? null,
      ];
    }

    jexit([
      'ok' => 1,
      'q'  => $q,
      'cve_almac' => $cve_almac,
      'cve_clte'  => $cve_clte,
      'rows' => $out
    ]);
  }

  // =========================
  // GET (detalle de un artículo por cve o id)
  // =========================
  if($action === 'get'){
    $id         = nint($_GET['id'] ?? null);
    $cve        = clean($_GET['cve_articulo'] ?? '');
    $cve_almac  = nint($_GET['cve_almac'] ?? null);

    if($id === null && $cve === ''){
      jexit(['ok'=>0,'error'=>'Falta id o cve_articulo'], 400);
    }

    $where = ["COALESCE(a.Activo,1)=1"];
    $p = [];

    if($cve_almac !== null){
      $where[] = "a.cve_almac = :alm";
      $p[':alm'] = $cve_almac;
    }

    if($id !== null){
      $where[] = "a.id = :id";
      $p[':id'] = $id;
    }else{
      $where[] = "a.cve_articulo = :cve";
      $p[':cve'] = $cve;
    }

    $sql = "
      SELECT
        a.*
      FROM c_articulo a
      WHERE " . implode(" AND ", $where) . "
      LIMIT 1
    ";
    $row = db_one($sql, $p);
    if(!$row) jexit(['ok'=>0,'error'=>'No encontrado'], 404);

    jexit(['ok'=>1,'row'=>$row]);
  }

  jexit(['ok'=>0,'error'=>'Acción no soportada','action'=>$action], 400);

}catch(Throwable $e){
  jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()], 500);
}
