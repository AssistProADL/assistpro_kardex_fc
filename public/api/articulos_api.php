<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jerr($msg, $det = null)
{
  echo json_encode(['error' => $msg, 'detalles' => $det], JSON_UNESCAPED_UNICODE);
  exit;
}
function clean($v)
{
  return trim((string) $v);
}
function nint($v)
{
  $v = clean($v);
  return ($v === '' ? null : (int) $v);
}
function nd($v)
{
  $v = clean($v);
  return ($v === '' ? null : $v);
}
function norm01($v, $def = 1)
{
  $v = clean($v);
  if ($v === '')
    return $def;
  return ($v === '1' || strtolower($v) === 'true') ? 1 : 0;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---------------------------
// FALLBACK INTELIGENTE
// - si UI no manda action, no tronamos
// ---------------------------
if ($action === '') {
  if (isset($_GET['cve_articulo']) || isset($_POST['cve_articulo'])) {
    $action = 'lookup';
  } elseif (isset($_GET['q']) || isset($_POST['q'])) {
    $action = 'list';
  }
}

try {

  // ===================== LOOKUP (por clave) =====================
  // Uso:
  //   /public/api/articulos_api.php?cve_articulo=D70035100
  //   /public/api/articulos_api.php?action=lookup&cve_articulo=D70035100&cve_almac=38
  if ($action === 'lookup') {
    $cve_articulo = clean($_GET['cve_articulo'] ?? $_POST['cve_articulo'] ?? '');
    if ($cve_articulo === '')
      jerr('cve_articulo es obligatorio');

    // opcional: limitar por almacen (si aplica en tu c_articulo)
    $cve_almac = clean($_GET['cve_almac'] ?? $_POST['cve_almac'] ?? '');

    $sql = "SELECT
              a.id,
              a.cve_articulo,
              a.des_articulo,
              a.des_detallada,
              a.unidadMedida,
              um.des_umed as unidadMedida_nombre,
              a.cve_umed,
              a.num_multiplo,
              a.imp_costo,
              a.PrecioVenta,
              a.cve_almac,
              alm.clave as almacen_clave,
              alm.nombre as almacen_nombre,
              a.Activo
            FROM c_articulo a
            LEFT JOIN c_unimed um ON um.id_umed = a.unidadMedida
            LEFT JOIN c_almacenp alm ON alm.id = a.cve_almac
            WHERE a.cve_articulo = :cve ";

    $p = [':cve' => $cve_articulo];

    if ($cve_almac !== '') {
      $sql .= " AND a.cve_almac = :alm ";
      $p[':alm'] = (int)$cve_almac;
    }

    $sql .= " LIMIT 1";

    $row = db_one($sql, $p);
    if (!$row)
      jerr('articulo no encontrado');

    echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== LIST =====================
  if ($action === 'list') {
    $q = clean($_GET['q'] ?? '');
    $inactivos = (int) ($_GET['inactivos'] ?? 0);
    $page = (int) ($_GET['page'] ?? 1);
    if ($page < 1)
      $page = 1;
    $limit = (int) ($_GET['limit'] ?? 25);
    if ($limit < 1)
      $limit = 25;
    $offset = ($page - 1) * $limit;

    $where = [];
    $p = [];
    if (!$inactivos)
      $where[] = "IFNULL(a.Activo,1)=1";

    // Filtros específicos
    $grupo = clean($_GET['grupo'] ?? '');
    $tipo = clean($_GET['tipo'] ?? '');
    $clasificacion = clean($_GET['clasificacion'] ?? '');
    $compuesto = clean($_GET['compuesto'] ?? '');
    $caduca = clean($_GET['caduca'] ?? '');

    if ($grupo !== '') {
      $where[] = "a.grupo = :grupo";
      $p[':grupo'] = $grupo;
    }
    if ($tipo !== '') {
      $where[] = "a.tipo = :tipo";
      $p[':tipo'] = $tipo;
    }
    if ($clasificacion !== '') {
      $where[] = "a.clasificacion = :clasificacion";
      $p[':clasificacion'] = $clasificacion;
    }
    if ($compuesto !== '') {
      $where[] = "a.Compuesto = :compuesto";
      $p[':compuesto'] = $compuesto;
    }
    if ($caduca !== '') {
      $where[] = "a.Caduca = :caduca";
      $p[':caduca'] = $caduca;
    }

    if ($q !== '') {
      // Fix: Unique parameters for each usage to avoid HY093 on some drivers
      $where[] = "(
        a.cve_articulo LIKE :q1 OR a.des_articulo LIKE :q2 OR a.des_detallada LIKE :q3
        OR a.tipo LIKE :q4 OR a.grupo LIKE :q5 OR a.clasificacion LIKE :q6
        OR a.Cve_SAP LIKE :q7 OR a.cve_alt LIKE :q8 OR a.barras2 LIKE :q9 OR a.barras3 LIKE :q10
        OR CAST(a.id AS CHAR) LIKE :q11 OR CAST(a.cve_almac AS CHAR) LIKE :q12
        OR gpo.des_gpoart LIKE :q13
      )";
      for ($i = 1; $i <= 13; $i++)
        $p[":q$i"] = "%$q%";
    }

    // 1. Get Total Count
    $sqlCount = "SELECT COUNT(*) FROM c_articulo a
                 LEFT JOIN c_gpoarticulo gpo ON gpo.id = a.grupo
                 LEFT JOIN c_sgpoarticulo sgpo ON sgpo.id = a.clasificacion
                 LEFT JOIN c_ssgpoarticulo ssgpo ON ssgpo.id = a.tipo
                 LEFT JOIN c_unimed um ON um.id_umed = a.unidadMedida
                 LEFT JOIN c_almacenp alm ON alm.id = a.cve_almac
                 LEFT JOIN c_tipo_producto tp ON tp.clave = a.tipo_producto
                 LEFT JOIN c_proveedores prov ON prov.ID_Proveedor = a.ID_Proveedor
                 LEFT JOIN c_tipocaja tc ON tc.id_tipocaja = a.cve_tipcaja
                 LEFT JOIN c_monedas mon ON mon.Id_Moneda = a.cve_moneda
                 LEFT JOIN c_ubicacion ubi ON ubi.idy_ubica = a.mav_cveubica";
    if ($where)
      $sqlCount .= " WHERE " . implode(" AND ", $where);
    $total = (int) db_val($sqlCount, $p);

    // 2. Get Data
    $sql = "SELECT
            a.id, a.cve_articulo, a.des_articulo, a.des_detallada,
            a.unidadMedida, a.cve_umed, 
            um.des_umed as unidadMedida_nombre,
            a.imp_costo, a.PrecioVenta,
            a.tipo, a.grupo, a.grupo as grupo_raw, a.clasificacion,
            gpo.des_gpoart as grupo_nombre,
            sgpo.des_sgpoart as clasificacion_nombre,
            ssgpo.des_ssgpoart as tipo_nombre,
            a.cve_almac, alm.nombre as almacen_nombre,
            a.tipo_producto, tp.descripcion as tipo_producto_nombre,
            a.Compuesto, a.Caduca,
            a.control_lotes, a.control_numero_series, a.control_garantia, a.tipo_garantia, a.valor_garantia,
            a.ecommerce_activo, a.ecommerce_categoria, a.ecommerce_subcategoria, a.ecommerce_destacado,
            a.cve_codprov, a.barras2, a.barras3,
            a.alto, a.ancho, a.fondo, (a.alto * a.ancho * a.fondo) as volumen, a.peso,
            a.num_multiplo, a.cajas_palet, a.umas, a.cve_alt as clave_alterna,
            a.Usa_Envase,
            prov.Nombre as proveedor_nombre,
            tc.descripcion as tipo_caja_nombre,
            mon.Des_Moneda as moneda_nombre,
            ubi.Ubicacion as ubicacion_nombre,
            a.Activo
          FROM c_articulo a
          LEFT JOIN c_gpoarticulo gpo ON gpo.id = a.grupo
          LEFT JOIN c_sgpoarticulo sgpo ON sgpo.id = a.clasificacion
          LEFT JOIN c_ssgpoarticulo ssgpo ON ssgpo.id = a.tipo
          LEFT JOIN c_unimed um ON um.id_umed = a.unidadMedida
          LEFT JOIN c_almacenp alm ON alm.id = a.cve_almac
          LEFT JOIN c_tipo_producto tp ON tp.clave = a.tipo_producto
          LEFT JOIN c_proveedores prov ON prov.ID_Proveedor = a.ID_Proveedor
          LEFT JOIN c_tipocaja tc ON tc.id_tipocaja = a.cve_tipcaja
          LEFT JOIN c_monedas mon ON mon.Id_Moneda = a.cve_moneda
          LEFT JOIN c_ubicacion ubi ON ubi.idy_ubica = a.mav_cveubica";
    if ($where)
      $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY IFNULL(a.Activo,1) DESC, a.des_articulo ASC LIMIT $limit OFFSET $offset";

    $totalPages = ceil($total / $limit);

    echo json_encode([
      'rows' => db_all($sql, $p),
      'total' => $total,
      'pages' => $totalPages,
      'page' => $page
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== GET =====================
  if ($action === 'get') {
    $cve_almac = clean($_GET['cve_almac'] ?? '');
    $id = clean($_GET['id'] ?? '');
    if ($cve_almac === '' || $id === '')
      jerr('Llave inválida: cve_almac + id');

    $row = db_one("SELECT * FROM c_articulo WHERE cve_almac=:a AND id=:i LIMIT 1", [':a' => $cve_almac, ':i' => $id]);
    if (!$row)
      jerr('No existe el registro');
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== CREATE / UPDATE =====================
  if ($action === 'create' || $action === 'update') {
    $k_cve_almac = clean($_POST['k_cve_almac'] ?? '');
    $k_id = clean($_POST['k_id'] ?? '');

    $cve_almac = clean($_POST['cve_almac'] ?? '');
    $id = clean($_POST['id'] ?? '');

    $cve_articulo = clean($_POST['cve_articulo'] ?? '');
    $des_articulo = clean($_POST['des_articulo'] ?? '');

    $det = [];
    if ($cve_almac === '')
      $det[] = 'cve_almac es obligatorio.';
    if ($id === '')
      $det[] = 'id es obligatorio.';
    if ($cve_articulo === '')
      $det[] = 'cve_articulo es obligatorio.';
    if ($des_articulo === '')
      $det[] = 'des_articulo es obligatorio.';
    if ($det)
      jerr('Validación', $det);

    $data = [
      'cve_almac' => (int) $cve_almac,
      'id' => (int) $id,

      'cve_articulo' => $cve_articulo,
      'des_articulo' => $des_articulo,
      'des_detallada' => nd($_POST['des_detallada'] ?? null),

      'unidadMedida' => nint($_POST['unidadMedida'] ?? null),
      'cve_umed' => nint($_POST['cve_umed'] ?? null),

      'imp_costo' => nd($_POST['imp_costo'] ?? null),
      'PrecioVenta' => nd($_POST['PrecioVenta'] ?? null),

      'tipo' => nd($_POST['tipo'] ?? null),
      'grupo' => nd($_POST['grupo'] ?? null),
      'clasificacion' => nd($_POST['clasificacion'] ?? null),

      'Compuesto' => nd($_POST['Compuesto'] ?? null),
      'Caduca' => nd($_POST['Caduca'] ?? null),

      'control_lotes' => nd($_POST['control_lotes'] ?? null),
      'control_numero_series' => nd($_POST['control_numero_series'] ?? null),

      'control_garantia' => nd($_POST['control_garantia'] ?? null),
      'tipo_garantia' => nd($_POST['tipo_garantia'] ?? null),
      'valor_garantia' => nint($_POST['valor_garantia'] ?? null),

      'Cve_SAP' => nd($_POST['Cve_SAP'] ?? null),
      'cve_alt' => nd($_POST['cve_alt'] ?? null),
      'barras2' => nd($_POST['barras2'] ?? null),
      'barras3' => nd($_POST['barras3'] ?? null),

      'ecommerce_activo' => norm01($_POST['ecommerce_activo'] ?? 0, 0),
      'ecommerce_categoria' => nd($_POST['ecommerce_categoria'] ?? null),
      'ecommerce_subcategoria' => nd($_POST['ecommerce_subcategoria'] ?? null),
      'ecommerce_destacado' => norm01($_POST['ecommerce_destacado'] ?? 0, 0),

      'alto' => nd($_POST['alto'] ?? null),
      'ancho' => nd($_POST['ancho'] ?? null),
      'fondo' => nd($_POST['fondo'] ?? null),
      'peso' => nd($_POST['peso'] ?? null),
      'num_multiplo' => nd($_POST['num_multiplo'] ?? null),
      'cajas_palet' => nd($_POST['cajas_palet'] ?? null),

      'Usa_Envase' => nd($_POST['Usa_Envase'] ?? null),
      'tipo_producto' => nd($_POST['tipo_producto'] ?? null),
      'umas' => nd($_POST['umas'] ?? null),
      'control_abc' => nd($_POST['control_abc'] ?? null),

      'ID_Proveedor' => nd($_POST['proveedor'] ?? null),

      'Activo' => norm01($_POST['Activo'] ?? 1, 1),
    ];

    db_tx(function () use ($action, $k_cve_almac, $k_id, $cve_almac, $id, $data) {
      if ($action === 'create') {
        $ex = db_val("SELECT 1 FROM c_articulo WHERE cve_almac=:a AND id=:i LIMIT 1", [':a' => $cve_almac, ':i' => $id]);
        if ($ex)
          throw new Exception("Ya existe el artículo con esa llave (cve_almac + id).");

        $cols = array_keys($data);
        $sql = "INSERT INTO c_articulo (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $p = [];
        foreach ($data as $k => $v)
          $p[":$k"] = $v;
        dbq($sql, $p);
      } else {
        if ($k_cve_almac === '' || $k_id === '')
          throw new Exception("Llave original inválida (k_cve_almac + k_id).");

        $set = [];
        $p = [':ka' => $k_cve_almac, ':ki' => $k_id];
        foreach ($data as $k => $v) {
          $set[] = "$k=:$k";
          $p[":$k"] = $v;
        }
        dbq("UPDATE c_articulo SET " . implode(',', $set) . " WHERE cve_almac=:ka AND id=:ki", $p);
      }
    });

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== DELETE / RESTORE =====================
  if ($action === 'delete' || $action === 'restore') {
    $cve_almac = clean($_POST['cve_almac'] ?? '');
    $id = clean($_POST['id'] ?? '');
    if ($cve_almac === '' || $id === '')
      jerr('Llave inválida: cve_almac + id');

    $val = ($action === 'delete') ? 0 : 1;
    dbq("UPDATE c_articulo SET Activo=:v WHERE cve_almac=:a AND id=:i", [':v' => $val, ':a' => $cve_almac, ':i' => $id]);
    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== EXPORT =====================
  if ($action === 'export') {
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=articulos_export.csv');

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int) ($_GET['inactivos'] ?? 0);

    $where = [];
    $p = [];
    if ($inactivos)
      $where[] = "IFNULL(Activo,1)=0";
    else
      $where[] = "IFNULL(Activo,1)=1";
    if ($q !== '') {
      $where[] = "(cve_articulo LIKE :q OR des_articulo LIKE :q OR Cve_SAP LIKE :q OR CAST(id AS CHAR) LIKE :q)";
      $p[':q'] = "%$q%";
    }

    $sql = "SELECT
            cve_almac,id,cve_articulo,des_articulo,unidadMedida,cve_umed,
            imp_costo,PrecioVenta,tipo,grupo,clasificacion,Compuesto,Caduca,
            control_lotes,control_numero_series,control_garantia,tipo_garantia,valor_garantia,
            Cve_SAP,cve_alt,barras2,barras3,
            ecommerce_activo,ecommerce_categoria,ecommerce_subcategoria,ecommerce_destacado,
            Activo
          FROM c_articulo";
    if ($where)
      $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY des_articulo ASC";

    $rows = db_all($sql, $p);
    $out = fopen('php://output', 'w');

    $map = [
      'cve_almac' => 'Almacén',
      'id' => 'ID',
      'cve_articulo' => 'Clave',
      'des_articulo' => 'Descripción',
      'unidadMedida' => 'U. Medida',
      'cve_umed' => 'Clave U.M.',
      'imp_costo' => 'Costo',
      'PrecioVenta' => 'Precio',
      'tipo' => 'Tipo',
      'grupo' => 'Grupo',
      'clasificacion' => 'Clasificación',
      'Compuesto' => 'Compuesto',
      'Caduca' => 'Caduca',
      'control_lotes' => 'Lotes',
      'control_numero_series' => 'Series',
      'control_garantia' => 'Garantía',
      'tipo_garantia' => 'Tipo Garantía',
      'valor_garantia' => 'Valor Garantía',
      'Cve_SAP' => 'SAP',
      'cve_alt' => 'Clave Alterna',
      'barras2' => 'Barras 2',
      'barras3' => 'Barras 3',
      'ecommerce_activo' => 'Ecom. Activo',
      'ecommerce_categoria' => 'Ecom. Cat',
      'ecommerce_subcategoria' => 'Ecom. Sub',
      'ecommerce_destacado' => 'Destacado',
      'Activo' => 'Estatus'
    ];

    fputcsv($out, array_values($map));

    foreach ($rows as $r) {
      $line = [];
      foreach ($map as $k => $label)
        $line[] = $r[$k] ?? '';
      fputcsv($out, $line);
    }
    fclose($out);
    exit;
  }

  // ===================== AUTOCOMPLETE =====================
  if ($action == 'autocomplete') {
    $field = clean($_GET['field'] ?? '');
    $cve_almac = clean($_GET['cve_almac'] ?? '');
    $parent_id = clean($_GET['parent_id'] ?? '');

    $sql = "";
    $params = [];

    switch ($field) {
      case 'almacen':
        $sql = "SELECT id, nombre FROM c_almacenp WHERE 1=1 ORDER BY nombre";
        break;

      case 'grupo':
        $sql = "SELECT id, des_gpoart as nombre FROM c_gpoarticulo WHERE IFNULL(Activo,1)=1";
        if ($cve_almac && $cve_almac !== '' && $cve_almac !== '0') {
          $sql .= " AND (id_almacen = ? OR id_almacen IS NULL)";
          $params[] = (int) $cve_almac;
        }
        $sql .= " ORDER BY des_gpoart";
        break;

      case 'clasificacion':
        $sql = "SELECT id, des_sgpoart as nombre, cve_gpoart FROM c_sgpoarticulo WHERE IFNULL(Activo,1)=1";
        if ($cve_almac && $cve_almac !== '' && $cve_almac !== '0') {
          $sql .= " AND (id_almacen = ? OR id_almacen IS NULL)";
          $params[] = (int) $cve_almac;
        }
        if ($parent_id && $parent_id !== '' && $parent_id !== '0') {
          $sql .= " AND cve_gpoart = ?";
          $params[] = (int) $parent_id;
        }
        $sql .= " ORDER BY des_sgpoart";
        break;

      case 'tipo':
        $sql = "SELECT id, des_ssgpoart as nombre, cve_sgpoart FROM c_ssgpoarticulo WHERE IFNULL(Activo,1)=1";
        if ($cve_almac && $cve_almac !== '' && $cve_almac !== '0') {
          $sql .= " AND (id_almacen = ? OR id_almacen IS NULL)";
          $params[] = (int) $cve_almac;
        }
        if ($parent_id && $parent_id !== '' && $parent_id !== '0') {
          $sql .= " AND cve_sgpoart = ?";
          $params[] = (int) $parent_id;
        }
        $sql .= " ORDER BY des_ssgpoart";
        break;

      case 'proveedor':
        $sql = "SELECT ID_Proveedor as id, Nombre as nombre FROM c_proveedores ORDER BY Nombre";
        break;

      case 'unidadMedida':
        $sql = "SELECT id_umed as id, des_umed as nombre FROM c_unimed ORDER BY des_umed";
        break;

      case 'tipo_producto':
        $sql = "SELECT clave as id, descripcion as nombre FROM c_tipo_producto ORDER BY descripcion";
        break;

      default:
        echo json_encode(['values' => []]);
        exit;
    }

    $rows = db_all($sql, $params);
    echo json_encode(['values' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== LAYOUT =====================
  if ($action === 'layout') {
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=articulos_layout.csv');

    $out = fopen('php://output', 'w');

    $map = [
      'cve_almac' => 'Almacén',
      'id' => 'ID',
      'cve_articulo' => 'Clave',
      'des_articulo' => 'Descripción',
      'unidadMedida' => 'U. Medida',
      'cve_umed' => 'Clave U.M.',
      'imp_costo' => 'Costo',
      'PrecioVenta' => 'Precio',
      'tipo' => 'Tipo',
      'grupo' => 'Grupo',
      'clasificacion' => 'Clasificación',
      'Compuesto' => 'Compuesto',
      'Caduca' => 'Caduca',
      'control_lotes' => 'Lotes',
      'control_numero_series' => 'Series',
      'control_garantia' => 'Garantía',
      'tipo_garantia' => 'Tipo Garantía',
      'valor_garantia' => 'Valor Garantía',
      'Cve_SAP' => 'SAP',
      'cve_alt' => 'Clave Alterna',
      'barras2' => 'Barras 2',
      'barras3' => 'Barras 3',
      'ecommerce_activo' => 'Ecom. Activo',
      'ecommerce_categoria' => 'Ecom. Cat',
      'ecommerce_subcategoria' => 'Ecom. Sub',
      'ecommerce_destacado' => 'Destacado',
      'Activo' => 'Estatus'
    ];

    fputcsv($out, array_values($map));

    $ex = [
      '1','1001','ART-001','ARTICULO DEMO','1','1','12.50','18.90','PT','GPO1','CLAS1',
      'N','N','N','N','N','MESES','0','SAP-001','ALT-001','7500000000001','7500000000002',
      '0','CAT','SUB','0','1'
    ];
    fputcsv($out, $ex);

    fclose($out);
    exit;
  }

  // ===================== IMPORT (UPSERT) =====================
  if ($action === 'import') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!$payload || !isset($payload['rows']) || !is_array($payload['rows']))
      jerr('Payload inválido. Se espera rows[]');

    $rows = $payload['rows'];
    $ok = 0;
    $err = 0;
    $errs = [];

    db_tx(function () use ($rows, &$ok, &$err, &$errs) {
      foreach ($rows as $idx => $r) {
        try {
          $cve_almac = isset($r['cve_almac']) ? (int) $r['cve_almac'] : null;
          $id = isset($r['id']) ? (int) $r['id'] : null;
          $cve_art = clean($r['cve_articulo'] ?? '');
          $des = clean($r['des_articulo'] ?? '');

          if (!$cve_almac || !$id || $cve_art === '' || $des === '') {
            throw new Exception("Fila " . ($idx + 1) . ": faltan obligatorios (cve_almac,id,cve_articulo,des_articulo).");
          }

          $data = [
            'cve_almac' => $cve_almac,
            'id' => $id,
            'cve_articulo' => $cve_art,
            'des_articulo' => $des,
            'unidadMedida' => ($r['unidadMedida'] ?? null) === '' ? null : (isset($r['unidadMedida']) ? (int) $r['unidadMedida'] : null),
            'cve_umed' => ($r['cve_umed'] ?? null) === '' ? null : (isset($r['cve_umed']) ? (int) $r['cve_umed'] : null),
            'imp_costo' => nd($r['imp_costo'] ?? null),
            'PrecioVenta' => nd($r['PrecioVenta'] ?? null),
            'tipo' => nd($r['tipo'] ?? null),
            'grupo' => nd($r['grupo'] ?? null),
            'clasificacion' => nd($r['clasificacion'] ?? null),
            'Compuesto' => nd($r['Compuesto'] ?? null),
            'Caduca' => nd($r['Caduca'] ?? null),
            'control_lotes' => nd($r['control_lotes'] ?? null),
            'control_numero_series' => nd($r['control_numero_series'] ?? null),
            'control_garantia' => nd($r['control_garantia'] ?? null),
            'tipo_garantia' => nd($r['tipo_garantia'] ?? null),
            'valor_garantia' => ($r['valor_garantia'] ?? null) === '' ? null : (isset($r['valor_garantia']) ? (int) $r['valor_garantia'] : null),
            'Cve_SAP' => nd($r['Cve_SAP'] ?? null),
            'cve_alt' => nd($r['cve_alt'] ?? null),
            'barras2' => nd($r['barras2'] ?? null),
            'barras3' => nd($r['barras3'] ?? null),
            'ecommerce_activo' => isset($r['ecommerce_activo']) ? (int) norm01($r['ecommerce_activo'], 0) : 0,
            'ecommerce_categoria' => nd($r['ecommerce_categoria'] ?? null),
            'ecommerce_subcategoria' => nd($r['ecommerce_subcategoria'] ?? null),
            'ecommerce_destacado' => isset($r['ecommerce_destacado']) ? (int) norm01($r['ecommerce_destacado'], 0) : 0,
            'Activo' => isset($r['Activo']) ? (int) norm01($r['Activo'], 1) : 1,
          ];

          $ex = db_val("SELECT 1 FROM c_articulo WHERE cve_almac=:a AND id=:i LIMIT 1", [':a' => $cve_almac, ':i' => $id]);
          if ($ex) {
            $set = [];
            $p = [':a' => $cve_almac, ':i' => $id];
            foreach ($data as $k => $v) {
              if ($k === 'cve_almac' || $k === 'id')
                continue;
              $set[] = "$k=:$k";
              $p[":$k"] = $v;
            }
            dbq("UPDATE c_articulo SET " . implode(',', $set) . " WHERE cve_almac=:a AND id=:i", $p);
          } else {
            $cols = array_keys($data);
            $sql = "INSERT INTO c_articulo (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
            $p = [];
            foreach ($data as $k => $v)
              $p[":$k"] = $v;
            dbq($sql, $p);
          }

          $ok++;
        } catch (Throwable $e) {
          $err++;
          $errs[] = $e->getMessage();
        }
      }
    });

    echo json_encode(['ok' => $ok, 'err' => $err, 'errores' => $errs], JSON_UNESCAPED_UNICODE);
    exit;
  }

  jerr('Acción no soportada: ' . $action);

} catch (Throwable $e) {
  jerr('Error: ' . $e->getMessage());
}
