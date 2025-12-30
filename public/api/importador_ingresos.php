<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
if (!$pdo) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'error'=>'PDO no inicializado']);
  exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'none';

function jexit($a){ echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function table_exists(PDO $pdo, string $table): bool {
  $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
  $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name=?");
  $st->execute([$db, $table]);
  return (int)$st->fetchColumn() > 0;
}

function ensure_tables(PDO $pdo){
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS ap_import_runs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      folio_importacion VARCHAR(40) NOT NULL,
      tipo_ingreso VARCHAR(20) NOT NULL,
      empresa_id INT NULL,
      almacen_id INT NULL,
      usuario VARCHAR(50) NOT NULL,
      fecha_importacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      status VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
      total_lineas INT NOT NULL DEFAULT 0,
      total_ok INT NOT NULL DEFAULT 0,
      total_err INT NOT NULL DEFAULT 0,
      archivo_nombre VARCHAR(255) NULL,
      impacto_kardex VARCHAR(50) NULL,
      error_resumen TEXT NULL,
      KEY idx_folio (folio_importacion),
      KEY idx_fecha (fecha_importacion),
      KEY idx_tipo (tipo_ingreso)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
}

ensure_tables($pdo);

/**
 * ============================
 * DICCIONARIO DE CAMPOS (TH/TD ADUANA)
 * ============================
 * La idea: centralizar definición y a partir de ahí:
 *  - layouts (CSV) por tipo
 *  - validaciones por tipo
 *
 * Nota: como tu legacy mezcla conceptos (ej. num_pedimento a veces OC),
 * aquí separamos por intención funcional.
 */
$FIELD_CATALOG = [
  // ---- TD (detalle) ----
  ['key'=>'OC',              'label'=>'Orden Compra',                 'source'=>'TD', 'note'=>'OCN/OCI', 'required_in'=>['OCN','OCN_PUT','OCI','OCI_PUT']],
  ['key'=>'SKU',             'label'=>'Clave Artículo',               'source'=>'TD', 'note'=>'c_articulo', 'required_in'=>['OCN','OCN_PUT','OCI','OCI_PUT']],
  ['key'=>'CANTIDAD',        'label'=>'Cantidad',                     'source'=>'TD', 'note'=>'0 válido', 'required_in'=>['OCN','OCN_PUT','OCI','OCI_PUT']],
  ['key'=>'UOM',             'label'=>'UOM',                          'source'=>'TD', 'note'=>'Opcional', 'required_in'=>[]],

  // pallet / contenedor
  ['key'=>'LP_PALLET',       'label'=>'LP Pallet',                    'source'=>'TD', 'note'=>'LP-xxxx', 'required_in'=>[]],
  ['key'=>'LP_CONTENEDOR',   'label'=>'LP Contenedor',                'source'=>'TD', 'note'=>'CT-xxxx', 'required_in'=>[]],

  // destinos / putaway
  ['key'=>'BL_DESTINO',      'label'=>'BL destino',                   'source'=>'TD', 'note'=>'Solo cuando hay acomodo', 'required_in'=>['OCN_PUT','OCI_PUT']],

  // fechas
  ['key'=>'FECHA_ENTRADA',   'label'=>'Fecha Entrada (yyyy-mm-dd)',    'source'=>'TH', 'note'=>'Opcional', 'required_in'=>[]],

  // ---- TH (encabezado aduana / internacional) ----
  ['key'=>'TIPO_CAMBIO',     'label'=>'Tipo de cambio',               'source'=>'TH', 'note'=>'OCI', 'required_in'=>[]],
  ['key'=>'MONEDA',          'label'=>'Moneda',                       'source'=>'TH', 'note'=>'OCI default USD', 'required_in'=>[]],

  // Pedimentos (opcionales para OCI)
  ['key'=>'NUM_PEDIMENTO',   'label'=>'Número de pedimento',          'source'=>'TH', 'note'=>'Opcional OCI', 'required_in'=>[]],
  ['key'=>'FECH_PEDIMENTO',  'label'=>'Fecha pedimento (yyyy-mm-dd)', 'source'=>'TH', 'note'=>'Opcional OCI', 'required_in'=>[]],
  ['key'=>'ADUANA',          'label'=>'Aduana',                       'source'=>'TH', 'note'=>'Opcional OCI', 'required_in'=>[]],
  ['key'=>'FACTURA',         'label'=>'Factura',                      'source'=>'TH', 'note'=>'Opcional OCI', 'required_in'=>[]],

  // proveedor / logistica (si lo quieres habilitar en futuras fases)
  ['key'=>'ID_PROVEEDOR',    'label'=>'Proveedor (ID)',               'source'=>'TH', 'note'=>'Si no se deriva de OC', 'required_in'=>[]],
  ['key'=>'OBSERVACIONES',   'label'=>'Observaciones',                'source'=>'TH', 'note'=>'Opcional', 'required_in'=>[]],
];

function layout_for_tipo(string $tipo): array {
  // Layouts “de negocio”: mínimos y específicos (evitamos columnas que generan error humano).
  // OCN: no tipo cambio, no moneda, no pedimentos.
  // OCI: permite tipo cambio / moneda + pedimentos opcionales.
  // *_PUT: exige BL_DESTINO.

  $tipo = strtoupper(trim($tipo));
  $base = ['OC','SKU','CANTIDAD','UOM','LP_CONTENEDOR','LP_PALLET','FECHA_ENTRADA'];

  if ($tipo === 'OCN') {
    return [
      'tipo'=>$tipo,
      'headers'=>$base,
      'sample'=>['1000','123','10','PZA','CT-251205-1','LP-251205-1',date('Y-m-d')],
      'notes'=>'OCN: recepción contra OC. Precios y pesos/volúmenes se toman del sistema.'
    ];
  }
  if ($tipo === 'OCN_PUT') {
    $h = ['OC','SKU','CANTIDAD','UOM','LP_CONTENEDOR','LP_PALLET','BL_DESTINO','FECHA_ENTRADA'];
    return [
      'tipo'=>$tipo,
      'headers'=>$h,
      'sample'=>['1000','123','10','PZA','CT-251205-1','LP-251205-1','1-1-C-B-2',date('Y-m-d')],
      'notes'=>'OCN_PUT: igual que OCN pero con acomodo inmediato a BL_DESTINO.'
    ];
  }
  if ($tipo === 'OCI') {
    $h = array_merge($base, ['TIPO_CAMBIO','MONEDA','NUM_PEDIMENTO','FECH_PEDIMENTO','ADUANA','FACTURA']);
    return [
      'tipo'=>$tipo,
      'headers'=>$h,
      'sample'=>['2000','123','10','PZA','CT-251205-1','LP-251205-1',date('Y-m-d'),'17.50','USD','','','',''],
      'notes'=>'OCI: permite tipo de cambio/moneda y pedimentos opcionales.'
    ];
  }
  if ($tipo === 'OCI_PUT') {
    $h = ['OC','SKU','CANTIDAD','UOM','LP_CONTENEDOR','LP_PALLET','BL_DESTINO','FECHA_ENTRADA','TIPO_CAMBIO','MONEDA','NUM_PEDIMENTO','FECH_PEDIMENTO','ADUANA','FACTURA'];
    return [
      'tipo'=>$tipo,
      'headers'=>$h,
      'sample'=>['2000','123','10','PZA','CT-251205-1','LP-251205-1','1-1-C-B-2',date('Y-m-d'),'17.50','USD','','','',''],
      'notes'=>'OCI_PUT: OCI con acomodo inmediato.'
    ];
  }

  // placeholders de siguientes importadores (se afinan después)
  return [
    'tipo'=>$tipo ?: 'OCN',
    'headers'=>['REFERENCIA','SKU','CANTIDAD','UOM','BL_DESTINO','FECHA'],
    'sample'=>['REF001','123','10','PZA','',''.date('Y-m-d')],
    'notes'=>'Layout genérico temporal.'
  ];
}

try {
  // ============ LAYOUT SPEC (JSON: catálogo de campos y aplicabilidad) ============
  if ($action === 'layout_spec') {
    header('Content-Type: application/json; charset=utf-8');
    jexit(['ok'=>true,'fields'=>$FIELD_CATALOG]);
  }

  // ============ DOWNLOAD LAYOUT (CSV) ============
  if ($action === 'layout') {
    $tipo = s($_GET['tipo_ingreso'] ?? 'OCN') ?? 'OCN';
    $cfg = layout_for_tipo($tipo);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="layout_'.$cfg['tipo'].'.csv"');

    $out = fopen('php://output','w');
    fputcsv($out, $cfg['headers']);
    fputcsv($out, $cfg['sample']);
    fclose($out);
    exit;
  }

  // ============ BITÁCORA ============
  if ($action === 'runs_list') {
    header('Content-Type: application/json; charset=utf-8');
    $limit = (int)($_GET['limit'] ?? 20);
    $limit = max(1, min(200, $limit));

    $st = $pdo->prepare("
      SELECT folio_importacion, tipo_ingreso, empresa_id, almacen_id, usuario,
             fecha_importacion, status, impacto_kardex
      FROM ap_import_runs
      ORDER BY fecha_importacion DESC
      LIMIT {$limit}
    ");
    $st->execute();
    jexit(['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // ============ PREVIEW/PROCESS (placeholder: tu lógica existente sigue aquí) ============
  if ($action === 'previsualizar' || $action === 'procesar') {
    header('Content-Type: application/json; charset=utf-8');
    // Nota: aquí no reescribo toda tu lógica de negocio para no romper tu avance.
    // Solo dejo el API coherente: si ya tenías preview/procesar, mantenlo.
    // Si aún no lo tienes en este archivo, se conectará al que ya usas.
    jexit(['ok'=>false,'error'=>'Acción '.$action.' pendiente de integrar con tu motor de importación actual.']);
  }

  if ($action === 'rollback') {
    header('Content-Type: application/json; charset=utf-8');
    jexit(['ok'=>false,'error'=>'Rollback pendiente de integrar con tu motor de movimientos/kardex.']);
  }

  header('Content-Type: application/json; charset=utf-8');
  jexit(['ok'=>false,'error'=>'Acción no soportada: '.$action]);

} catch (Throwable $e) {
  header('Content-Type: application/json; charset=utf-8');
  jexit(['ok'=>false,'error'=>'Excepción: '.$e->getMessage()]);
}
