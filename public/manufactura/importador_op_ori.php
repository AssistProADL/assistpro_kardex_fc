<?php
// public/manufactura/importador_op.php
// Importador de Órdenes de Producción (OP) - PREVIEW + VALIDACIONES
// Estilo homologado a importador_ingresos.php (cards, modal, grilla, bitácora)
// Nota: En esta versión ya incluye Fase 2 (alta de t_ordenprod/td_ordenprod y registro en t_cardex).
// Importante: La afectación es transaccional e idempotente (evita duplicar Folio_Pro).

require_once __DIR__ . '/../../app/db.php'; // ajusta si tu path difiere

// ------------------------------
// Config
// ------------------------------
$TITULO = "Importador de Órdenes de Producción (OP)";
$LAYOUT_FILENAME = "layout_importacion_ots_def.csv";

// Layout requerido (exacto lógico). La validación en código es robusta (case-insensitive + normaliza).
$REQUIRED_COLS = [
  "Folio_OP",
  "Usuario",
  "OT_Cliente",
  "Fecha_OT",
  "Fecha_Compromiso",
  "Almacen",
  "Area_Produccion",
  "MP_BL_ORIGEN",
  "Articulo_Compuesto",
  "Lote",
  "Caducidad",
  "Cantidad_a_Producir",
  "PT_BL_DESTINO",
  "LP_CONTENEDOR",
  "LP_PALLET"
];

// Defaults UI
$DEFAULT_STATUS_FILTER = "P"; // Pendiente por default (cuando luego conectemos bitácora)

// ------------------------------
// Helpers (CSV + normalización)
// ------------------------------
function ap_norm_col($s) {
  // Normaliza header: quita BOM, trim, colapsa espacios, case-insensitive, unifica separadores
  $s = (string)$s;
  $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // BOM UTF-8
  $s = trim($s);
  $s = preg_replace('/\s+/', ' ', $s);

  // Normalización agresiva: espacios -> _, y deja solo [A-Z0-9_]
  $s2 = str_replace([' ', '-', '.'], '_', $s);
  $s2 = preg_replace('/_+/', '_', $s2);
  $s2 = strtoupper($s2);

  return $s2;
}

function ap_to_float($v) {
  if ($v === null) return null;
  $v = trim((string)$v);
  if ($v === '') return null;
  // soporta "1,234.56" o "1234,56"
  $v = str_replace([' '], [''], $v);
  if (substr_count($v, ',') > 0 && substr_count($v, '.') === 0) {
    $v = str_replace(',', '.', $v);
  } else {
    // si tiene ambos, quitamos miles
    $v = str_replace(',', '', $v);
  }
  if (!is_numeric($v)) return null;
  return (float)$v;
}

function ap_parse_date($v) {
  // Acepta dd/mm/yyyy, yyyy-mm-dd, dd-mm-yyyy
  $v = trim((string)$v);
  if ($v === '') return null;

  // yyyy-mm-dd
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;

  // dd/mm/yyyy o dd-mm-yyyy
  if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $v, $m)) {
    $dd = $m[1]; $mm = $m[2]; $yy = $m[3];
    return "{$yy}-{$mm}-{$dd}";
  }
  return null;
}


function ap_to_datetime($dateYmd, $fallbackNow = true) {
  // Convierte YYYY-MM-DD a YYYY-MM-DD 00:00:00
  if ($dateYmd === null || $dateYmd === '') {
    return $fallbackNow ? date('Y-m-d H:i:s') : null;
  }
  return $dateYmd . ' 00:00:00';
}

function ap_colset(PDO $pdo, $table) {
  // Obtiene columnas reales para inserts defensivos (ambientes difieren)
  $cols = [];
  $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
  $stmt->execute([$table]);
  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cols[strtolower($r['COLUMN_NAME'])] = true;
  }
  return $cols;
}

function ap_insert_row(PDO $pdo, $table, array $data, array $colset) {
  // Filtra por columnas existentes y ejecuta INSERT.
  $cols = [];
  $vals = [];
  $phs  = [];
  foreach ($data as $k=>$v) {
    $kl = strtolower($k);
    if (!isset($colset[$kl])) continue;
    $cols[] = $k;
    $vals[] = $v;
    $phs[]  = '?';
  }
  if (!$cols) throw new Exception("No hay columnas válidas para insertar en {$table}.");
  $sql = "INSERT INTO {$table} (".implode(',', $cols).") VALUES (".implode(',', $phs).")";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($vals);
  return $pdo->lastInsertId();
}


// ------------------------------
// Folios + validaciones negocio (OT Import)
// ------------------------------
function ap_next_seq(PDO $pdo, string $table, string $col, string $prefix) : int {
  // Extrae secuencia del MAX(col) con el prefijo dado. Espera sufijo numerico.
  $sql = "SELECT MAX(`$col`) AS mx FROM `$table` WHERE `$col` LIKE ?";
  $st = $pdo->prepare($sql);
  $st->execute([$prefix.'%']);
  $mx = (string)($st->fetchColumn() ?? '');
  if ($mx === '') return 1;
  if (preg_match('/(\d+)\s*$/', $mx, $m)) return (int)$m[1] + 1;
  return 1;
}

function ap_gen_folio_import(PDO $pdo) : string {
  // OTIyyyymmdd-001
  $ymd = date('Ymd');
  $prefix = 'OTI'.$ymd.'-';
  // Si la columna FolioImport no existe, igual generamos con secuencia en memoria (fallback = 1)
  try {
    $seq = ap_next_seq($pdo, 't_ordenprod', 'FolioImport', $prefix);
  } catch (Throwable $e) {
    $seq = 1;
  }
  return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
}

function ap_gen_folio_pro(PDO $pdo) : string {
  // Algoritmo base (ajustable): OPyyyymmdd-001
  $ymd = date('Ymd');
  $prefix = 'OP'.$ymd.'-';
  $seq = ap_next_seq($pdo, 't_ordenprod', 'Folio_Pro', $prefix);
  return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
}

function ap_has_table(PDO $pdo, string $table) : bool {
  $st = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
  $st->execute([$table]);
  return (bool)$st->fetchColumn();
}

function ap_has_col(PDO $pdo, string $table, string $col) : bool {
  $st = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
  $st->execute([$table,$col]);
  return (bool)$st->fetchColumn();
}

function ap_validate_bl(PDO $pdo, string $bl) : array {
  $bl = trim($bl);
  if ($bl==='') return [false,'BL vacío'];
  if (!ap_has_table($pdo,'c_almacenp') || !ap_has_col($pdo,'c_almacenp','BL')) return [true,''];
  $sql = 'SELECT 1 FROM c_almacenp WHERE BL=?';
  if (ap_has_col($pdo,'c_almacenp','Activo')) {
    $sql .= " AND (Activo=1 OR Activo='1' OR Activo='S' OR Activo IS NULL)";
  }
  $sql .= ' LIMIT 1';
  $st = $pdo->prepare($sql);
  $st->execute([$bl]);
  return [$st->fetchColumn()?true:false, 'BL no existe/inactivo en c_almacenp'];
}

function ap_validate_lp(PDO $pdo, string $lp) : array {
  $lp = trim($lp);
  if ($lp==='') return [false,'LP vacío'];

  // Debe existir en c_charolas y estar activo (si existe el catalogo)
  if (ap_has_table($pdo,'c_charolas')) {
    $lpCol = ap_has_col($pdo,'c_charolas','LP') ? 'LP' : (ap_has_col($pdo,'c_charolas','lp') ? 'lp' : null);
    $sql = $lpCol ? "SELECT 1 FROM c_charolas WHERE $lpCol=?" : null;
    if ($sql) {
      if (ap_has_col($pdo,'c_charolas','Activo')) $sql .= " AND (Activo=1 OR Activo='1' OR Activo='S' OR Activo IS NULL)";
      $sql .= ' LIMIT 1';
      $st = $pdo->prepare($sql);
      $st->execute([$lp]);
      if (!$st->fetchColumn()) return [false,'LP no existe o está inactivo en c_charolas'];
    }
  }

  // No debe existir movimiento en t_mov_charolas (si existe tabla)
  if (ap_has_table($pdo,'t_mov_charolas')) {
    $cands = ['LP','lp','LP_Charola','lp_charola','LP_CHAROLA','pallet_lp','Pallet_LP','contenedor_lp','Contenedor_LP'];
    $col = null;
    for ($i=0;$i<count($cands);$i++) {
      if (ap_has_col($pdo,'t_mov_charolas',$cands[$i])) { $col = $cands[$i]; break; }
    }
    if ($col) {
      $st = $pdo->prepare("SELECT 1 FROM t_mov_charolas WHERE `$col`=? LIMIT 1");
      $st->execute([$lp]);
      if ($st->fetchColumn()) return [false,'LP ya tiene movimientos en t_mov_charolas'];
    }
  }

  return [true,''];
}

function ap_str50($v) {
  $v = trim((string)$v);
  if (mb_strlen($v,'UTF-8') > 50) $v = mb_substr($v,0,50,'UTF-8');
  return $v;
}


function ap_send_json($arr, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function ap_read_csv_rows($tmpPath, &$meta = []) {
  $meta = [
    'cols_original' => [],
    'cols_norm' => [],
    'map_norm_to_original' => [],
    'rows' => []
  ];

  $fh = fopen($tmpPath, 'r');
  if (!$fh) return false;

  // Detect delimiter by first line
  $first = fgets($fh);
  if ($first === false) { fclose($fh); return false; }
  rewind($fh);

  $delims = [",", ";", "\t", "|"];
  $bestDelim = ",";
  $bestCount = 0;
  foreach ($delims as $d) {
    $cnt = substr_count($first, $d);
    if ($cnt > $bestCount) { $bestCount = $cnt; $bestDelim = $d; }
  }

  $header = fgetcsv($fh, 0, $bestDelim);
  if (!$header || count($header) < 2) { fclose($fh); return false; }

  $meta['cols_original'] = $header;

  $colsNorm = [];
  $map = [];
  foreach ($header as $idx => $c) {
    $n = ap_norm_col($c);
    $colsNorm[] = $n;
    // Si se repite, prioriza el primero (evita colisiones)
    if (!isset($map[$n])) $map[$n] = $idx;
  }
  $meta['cols_norm'] = $colsNorm;
  $meta['map_norm_to_original'] = $map;

  $rows = [];
  $rowNum = 1; // header = 1
  while (($line = fgetcsv($fh, 0, $bestDelim)) !== false) {
    $rowNum++;
    if (count($line) === 1 && trim((string)$line[0]) === '') continue;

    $row = ['__rownum' => $rowNum, '__raw' => $line, '__assoc' => []];
    foreach ($map as $colNorm => $i) {
      $row['__assoc'][$colNorm] = isset($line[$i]) ? $line[$i] : null;
    }
    $rows[] = $row;
    if (count($rows) >= 5000) break; // hard cap de seguridad
  }

  fclose($fh);
  $meta['rows'] = $rows;
  $meta['delimiter'] = $bestDelim;
  return true;
}

function ap_validate_layout($meta, $requiredCols) {
  $missing = [];
  $have = array_keys($meta['map_norm_to_original']);
  $haveSet = array_fill_keys($have, true);

  foreach ($requiredCols as $c) {
    $n = ap_norm_col($c);
    if (!isset($haveSet[$n])) $missing[] = $c;
  }
  return $missing;
}

function ap_validate_rows($meta, $requiredCols) {
  $errors = [];
  $ok = 0;

  foreach ($meta['rows'] as $r) {
    $a = $r['__assoc'];
    $rowErr = [];

    // Campos clave
    $folio = trim((string)($a[ap_norm_col('Folio_OP')] ?? ''));
    $usr   = trim((string)($a[ap_norm_col('Usuario')] ?? ''));
    $artc  = trim((string)($a[ap_norm_col('Articulo_Compuesto')] ?? ''));
    $qty   = ap_to_float($a[ap_norm_col('Cantidad_a_Producir')] ?? null);

    $f_ot  = ap_parse_date($a[ap_norm_col('Fecha_OT')] ?? '');
    $f_comp= ap_parse_date($a[ap_norm_col('Fecha_Compromiso')] ?? '');

    if ($folio === '') $rowErr[] = "Folio_OP vacío";
    if ($usr === '') $rowErr[] = "Usuario vacío";
    if ($artc === '') $rowErr[] = "Articulo_Compuesto vacío";
    if ($qty === null) $rowErr[] = "Cantidad_a_Producir inválida";
    if ($f_ot === null) $rowErr[] = "Fecha_OT inválida";
    if ($f_comp === null) $rowErr[] = "Fecha_Compromiso inválida";

    // Normalizaciones opcionales
    // Caducidad: puede ser número (días) o fecha; lo dejamos como texto en preview
    // Lote puede ir vacío

    if (!empty($rowErr)) {
      $errors[] = [
        'row' => $r['__rownum'],
        'folio' => $folio,
        'msg' => implode(" | ", $rowErr)
      ];
    } else {
      $ok++;
    }
  }

  return [$ok, $errors];
}

// ------------------------------
// API Actions (AJAX)
// ------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'download_layout') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$LAYOUT_FILENAME.'"');
  // Header (exacto como negocio)
  echo implode(",", $REQUIRED_COLS) . "\n";
  // Una línea ejemplo mínima (vacía a propósito)
  echo "45906094,45906094,OTCLIENTE001,10/10/2025,10/10/2025,WH1,W1,S1SALIDA01,A6-43019-001B2-106,,12,12,S2S1,,LP20252512-00001,REF\n";
  exit;
}

if ($action === 'preview') {
  if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    ap_send_json(['ok'=>0,'error'=>'Archivo CSV no recibido o con error de carga.'], 400);
  }

  $tmp = $_FILES['csv']['tmp_name'];

  $meta = [];
  if (!ap_read_csv_rows($tmp, $meta)) {
    ap_send_json(['ok'=>0,'error'=>'No se pudo leer el CSV. Verifica delimitador y encabezados.'], 400);
  }

  $missing = ap_validate_layout($meta, $REQUIRED_COLS);
  if (!empty($missing)) {
    ap_send_json([
      'ok'=>0,
      'error'=>"Layout incorrecto. Faltan columnas: ".implode(", ", $missing),
      'missing'=>$missing,
      'cols_detectadas'=>$meta['cols_original']
    ], 200);
  }

  [$okCount, $errs] = ap_validate_rows($meta, $REQUIRED_COLS);

  // Construimos preview (top 300)
  $preview = [];
  $maxPreview = 300;
  $normKeys = [];
  foreach ($REQUIRED_COLS as $c) $normKeys[] = ap_norm_col($c);

  $i = 0;
  foreach ($meta['rows'] as $r) {
    $i++;
    if ($i > $maxPreview) break;
    $a = $r['__assoc'];

    // mapeo a nombres originales del layout (para que en UI coincida)
    $out = [
      '__rownum' => $r['__rownum'],
      'Estado' => 'OK',
      'Mensaje' => ''
    ];

    foreach ($REQUIRED_COLS as $c) {
      $out[$c] = $a[ap_norm_col($c)] ?? '';
    }

    // Marca error si existe
    // (buscamos en errs por row)
    $found = null;
    foreach ($errs as $e) {
      if ((int)$e['row'] === (int)$r['__rownum']) { $found = $e; break; }
    }
    if ($found) {
      $out['Estado'] = 'ERROR';
      $out['Mensaje'] = $found['msg'];
    }

    $preview[] = $out;
  }

  ap_send_json([
    'ok'=>1,
    'totales'=>[
      'lineas'=>count($meta['rows']),
      'ok'=>$okCount,
      'error'=>count($errs)
    ],
    'preview'=>$preview,
    'cols'=>$REQUIRED_COLS,
    'cols_detectadas'=>$meta['cols_original']
  ]);
}


if ($action === 'process_phase2') {
  if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    ap_send_json(['ok'=>0,'error'=>'Para procesar (Fase 2) debes adjuntar el CSV.'], 400);
  }

  $tmp = $_FILES['csv']['tmp_name'];

  $meta = [];
  if (!ap_read_csv_rows($tmp, $meta)) {
    ap_send_json(['ok'=>0,'error'=>'No se pudo leer el CSV. Verifica delimitador y encabezados.'], 400);
  }

  $missing = ap_validate_layout($meta, $REQUIRED_COLS);
  if (!empty($missing)) {
    ap_send_json([
      'ok'=>0,
      'error'=>"Layout incorrecto. Faltan columnas: ".implode(", ", $missing),
      'missing'=>$missing,
      'cols_detectadas'=>$meta['cols_original']
    ], 200);
  }

  [$okCount, $errs] = ap_validate_rows($meta, $REQUIRED_COLS);
  if (!empty($errs)) {
    ap_send_json([
      'ok'=>0,
      'error'=>'El archivo trae errores de validación. Corrige y vuelve a importar.',
      'totales'=>[
        'lineas'=>count($meta['rows']),
        'ok'=>$okCount,
        'error'=>count($errs)
      ],
      'errores'=>array_slice($errs, 0, 200)
    ], 200);
  }

  // ------------------------------
  // Afectación transaccional
  // ------------------------------
  try {
    $pdo = db(); // tu helper debe regresar PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cols_t_ordenprod  = ap_colset($pdo, 't_ordenprod');
    $cols_td_ordenprod = ap_colset($pdo, 'td_ordenprod');
    $cols_t_cardex     = ap_colset($pdo, 't_cardex');

    // Pre-cálculo por folio
    $byFolio = [];
    foreach ($meta['rows'] as $r) {
      $a = $r['__assoc'];
      $folio = trim((string)($a[ap_norm_col('Folio_OP')] ?? ''));
      if ($folio === '') continue;
      if (!isset($byFolio[$folio])) {
        $byFolio[$folio] = [
          'folio_op'=>$folio,
          'almacen'=>trim((string)($a[ap_norm_col('Almacen')] ?? '')),
          'usuario'=>trim((string)($a[ap_norm_col('Usuario')] ?? '')),
          'fecha_ot'=>ap_parse_date($a[ap_norm_col('Fecha_OT')] ?? ''),
          'ot_cliente'=>trim((string)($a[ap_norm_col('OT_Cliente')] ?? '')),
          'total'=>0.0,
          'rows'=>[]
        ];
      }
      $qty = ap_to_float($a[ap_norm_col('Cantidad_a_Producir')] ?? null) ?? 0.0;
      $byFolio[$folio]['total'] += (float)$qty;
      $byFolio[$folio]['rows'][] = $r;
    }

    $pdo->beginTransaction();

    $inserted = ['headers'=>0,'details'=>0,'cardex'=>0,'folios'=>[]];
    $skipped  = ['folios'=>[]];

    // --- Validaciones de negocio extra (BL + LP) + generación de folios ---

    $inserted = ['headers'=>0,'details'=>0,'cardex'=>0,'folios'=>[]];
    $skipped  = ['folios'=>[]];

    // Si el ambiente ya tiene FolioImport, lo usaremos; si no, seguimos sin bloquear
    $hasFolioImport = isset($cols_t_ordenprod['folioimport']);

    foreach ($byFolio as $folioOP => $pack) {
      $folioPro    = ap_gen_folio_pro($pdo);
      $folioImport = ap_gen_folio_import($pdo);

      $dtFechaOT = ap_to_datetime($pack['fecha_ot'], true);
      $dtNow     = date('Y-m-d H:i:s');

      // Header t_ordenprod
      $dataHead = [
        'Folio_Pro'     => $folioPro,
        'FolioImport'   => $hasFolioImport ? $folioImport : null,
        'cve_almac'     => $pack['almacen'] ?: null,
        'Cantidad'      => $pack['total'],
        'Cve_Usuario'   => $pack['usuario'] ?: null,
        'Fecha'         => $dtFechaOT,
        'FechaReg'      => $dtNow,
        'Usr_Armo'      => $pack['usuario'] ?: null,
        'Status'        => 'P',
        'Referencia'    => ap_str50($pack['ot_cliente'] ?: 'OT_IMPORT'),
        'Cve_Almac_Ori' => $pack['almacen'] ?: null,
        'Tipo'          => 'OT'
      ];

      ap_insert_row($pdo, 't_ordenprod', $dataHead, $cols_t_ordenprod);
      $inserted['headers']++;
      $inserted['folios'][] = $folioPro;

      // Details + Kardex por línea
      foreach ($pack['rows'] as $r) {
        $a = $r['__assoc'];

        $art  = trim((string)($a[ap_norm_col('Articulo_Compuesto')] ?? ''));
        $lote = trim((string)($a[ap_norm_col('Lote')] ?? ''));
        $qty  = ap_to_float($a[ap_norm_col('Cantidad_a_Producir')] ?? null);
        $usr  = trim((string)($a[ap_norm_col('Usuario')] ?? ''));

        $otCliente = trim((string)($a[ap_norm_col('OT_Cliente')] ?? ''));
        $refDet    = ap_str50($otCliente ?: $folioOP);

        $mpOri  = trim((string)($a[ap_norm_col('MP_BL_ORIGEN')] ?? ''));
        $ptDest = trim((string)($a[ap_norm_col('PT_BL_DESTINO')] ?? ''));
        $lpCont = trim((string)($a[ap_norm_col('LP_CONTENEDOR')] ?? ''));
        $lpPal  = trim((string)($a[ap_norm_col('LP_PALLET')] ?? ''));

        // Validaciones: BL producción por clave (no id)
        [$okBl1,$msgBl1] = ap_validate_bl_produccion($pdo, $mpOri);
        if (!$okBl1) throw new Exception('BL origen inválido: '.$mpOri.' | '.$msgBl1.' (fila '.$r['__rownum'].')');
        [$okBl2,$msgBl2] = ap_validate_bl_produccion($pdo, $ptDest);
        if (!$okBl2) throw new Exception('BL destino inválido: '.$ptDest.' | '.$msgBl2.' (fila '.$r['__rownum'].')');

        // Validaciones: LPs deben existir, activos y sin movimientos
        [$okLp1,$msgLp1] = ap_validate_lp($pdo, $lpCont);
        if (!$okLp1) throw new Exception('LP_CONTENEDOR inválido: '.$lpCont.' | '.$msgLp1.' (fila '.$r['__rownum'].')');
        [$okLp2,$msgLp2] = ap_validate_lp($pdo, $lpPal);
        if (!$okLp2) throw new Exception('LP_PALLET inválido: '.$lpPal.' | '.$msgLp2.' (fila '.$r['__rownum'].')');

        // td_ordenprod
        $dataDet = [
          'Folio_Pro'     => $folioPro,
          'Cve_Articulo'  => $art ?: null,
          'Cve_Lote'      => $lote ?: null,
          'Fecha_Prod'    => $dtFechaOT,
          'Cantidad'      => $qty,
          'Usr_Armo'      => $usr ?: null,
          'Activo'        => 1,
          'id_art_rel'    => null,
          'Referencia'    => $refDet,
          'Cve_Almac_Ori' => $pack['almacen'] ?: null
        ];
        ap_insert_row($pdo, 'td_ordenprod', $dataDet, $cols_td_ordenprod);
        $inserted['details']++;

        // t_cardex (registro de producción: BL->BL, con LPs validadas)
        $dataCx = [
          'cve_articulo'     => $art ?: null,
          'cve_lote'         => $lote ?: null,
          'fecha'            => $dtFechaOT,
          'origen'           => $mpOri,
          'destino'          => $ptDest,
          'cantidad'         => $qty,
          'ajuste'           => null,
          'stockinicial'     => null,
          'id_TipoMovimiento'=> null,
          'cve_usuario'      => $usr ?: null,
          'Cve_Almac'        => $pack['almacen'] ?: null,
          'Cve_Almac_Origen' => $pack['almacen'] ?: null,
          'Cve_Almac_Destino'=> $pack['almacen'] ?: null,
          'Activo'           => 1,
          'Fec_Ingreso'      => $pack['fecha_ot'],
          'Referencia'       => ap_str50($folioPro),
          'contenedor_lp'    => $lpCont ?: null,
          'pallet_lp'        => $lpPal ?: null
        ];
        ap_insert_row($pdo, 't_cardex', $dataCx, $cols_t_cardex);
        $inserted['cardex']++;
      }
    }
    $pdo->commit();

    ap_send_json([
      'ok'=>1,
      'msg'=>'Importación completada. Se generaron OPs y registros de kardex (ingreso a destino).',
      'inserted'=>$inserted,
      'skipped'=>$skipped,
      'totales'=>[
        'lineas'=>count($meta['rows']),
        'ok'=>$okCount,
        'error'=>0
      ]
    ]);

  } catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    ap_send_json(['ok'=>0,'error'=>'Error en afectación BD: '.$e->getMessage()], 200);
  }
}

 

echo '
<div class="alert alert-success">
  Importación finalizada correctamente
</div>
';

// ------------------------------
// Nota: Asumo que ya tienes _menu_global.php / _menu_global_end.php o similar.
// Si tu proyecto usa otro include, ajusta aquí.
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($TITULO) ?></title>

  <!-- Bootstrap / FontAwesome (asumiendo que ya están en tu stack) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .ap-title { font-weight:700; }
    .ap-sub { font-size:12px; color:#6c757d; }
    .ap-card-kpi { border:0; border-radius:14px; box-shadow:0 6px 18px rgba(17,24,39,.08); }
    .ap-font-10, table.dataTable { font-size:10px !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: .1rem .35rem; }
    .table-preview-wrap { overflow:auto; max-height: 340px; border:1px solid #e9ecef; border-radius:10px; }
    .modal-xl { max-width: 1100px; }
  </style>
</head>

<body>
<?php
// Si tienes menú global:
$menu = __DIR__ . '/../bi/_menu_global.php';
$menuEnd = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menu)) include $menu;
?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-upload"></i>
        <h4 class="m-0 ap-title"><?= htmlspecialchars($TITULO) ?></h4>
      </div>
      <div class="ap-sub">Previsualización + validaciones + afectación (Fase 2) a t_ordenprod/td_ordenprod y registro en t_cardex.</div>
    </div>
    <div>
      <button class="btn btn-primary" id="btnAbrirModal">
        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Importar
      </button>
    </div>
  </div>

  <!-- KPI Cards (placeholder: se llenan con el preview actual) -->
  <div class="row g-2 mt-2">
    <div class="col-6 col-md-2">
      <div class="card ap-card-kpi">
        <div class="card-body py-2">
          <div class="ap-sub">Líneas</div>
          <div class="h5 m-0" id="kpiLineas">0</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card ap-card-kpi">
        <div class="card-body py-2">
          <div class="ap-sub">OK</div>
          <div class="h5 m-0 text-success" id="kpiOk">0</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card ap-card-kpi">
        <div class="card-body py-2">
          <div class="ap-sub">Error</div>
          <div class="h5 m-0 text-danger" id="kpiErr">0</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-6">
      <div class="card ap-card-kpi">
        <div class="card-body py-2">
          <div class="ap-sub">Nota</div>
          <div class="m-0 ap-font-10">
            En Fase 2 se insertan <b>t_ordenprod / td_ordenprod</b> y se registra el movimiento en <b>t_cardex</b>.
            Solo validamos que el dataset esté listo para consolidación (evitar deadlocks por iteración).
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel: Preview -->
  <div class="card ap-card-kpi mt-3">
    <div class="card-header bg-primary text-white" style="border-top-left-radius:14px;border-top-right-radius:14px;">
      <b>Previsualización y validaciones</b>
    </div>
    <div class="card-body">

      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="ap-font-10">
          Totales: <b>Líneas:</b> <span id="txtLineas">0</span> |
          <b>OK:</b> <span id="txtOk">0</span> |
          <b>Error:</b> <span id="txtErr">0</span>
        </div>
        <div id="msgLayout" class="ap-font-10 text-danger"></div>
      </div>

      <div class="table-preview-wrap">
        <table class="table table-sm table-striped ap-font-10 m-0" id="tblPreview" style="width:100%;">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Estado</th>
              <th>Mensaje</th>
              <?php foreach ($REQUIRED_COLS as $c): ?>
                <th><?= htmlspecialchars($c) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="mt-2 ap-font-10 text-muted">
        *El preview muestra hasta 300 líneas para performance. La validación evalúa todas las líneas cargadas (hasta 5000 por seguridad).
      </div>
    </div>
  </div>

  <!-- Panel: Bitácora (stub) -->
  <div class="card ap-card-kpi mt-3">
    <div class="card-header bg-primary text-white" style="border-top-left-radius:14px;border-top-right-radius:14px;">
      <b>Bitácora de importaciones</b>
    </div>
    <div class="card-body">
      <div class="ap-font-10 text-muted">
        (Stub) En fase 2 conectamos a <b>etl_runs / etl_run_logs</b> o a la bitácora que definas para manufactura.
      </div>
    </div>
  </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="mdlImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-upload me-2"></i>Importar OP</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label ap-font-10 mb-1">Archivo CSV (layout definitivo)</label>
            <input type="file" class="form-control form-control-sm" id="csvFile" accept=".csv,text/csv">
            <div class="ap-font-10 text-muted mt-1">
              CSV requerido: <?= htmlspecialchars(implode(", ", $REQUIRED_COLS)) ?>
            </div>
          </div>
          <div class="col-md-4 d-grid">
            <a class="btn btn-outline-primary" href="?action=download_layout">
              <i class="fa-solid fa-download me-1"></i> Descargar layout
            </a>
          </div>
        </div>

        <hr>

        <div class="ap-font-10 text-danger" id="mdlMsgErr"></div>
        <div class="ap-font-10 text-success" id="mdlMsgOk"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-success" id="btnPreview">
          <i class="fa-solid fa-magnifying-glass me-1"></i> Previsualizar
        </button>
        <button class="btn btn-primary" id="btnPhase2">
          <i class="fa-solid fa-gears me-1"></i> Procesar (Fase 2)
        </button>
      </div>
    </div>
  </div>
</div>


<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
  const REQUIRED_COLS = <?= json_encode($REQUIRED_COLS, JSON_UNESCAPED_UNICODE) ?>;

  let dt = null;
  function initDT() {
    if (dt) return;
    dt = $('#tblPreview').DataTable({
      pageLength: 25,
      lengthMenu: [[25, 50, 100], [25, 50, 100]],
      scrollY: "320px",
      scrollX: true,
      scrollCollapse: true,
      order: [],
      language: {
        search: "Search:",
        lengthMenu: "_MENU_ entries per page",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "Showing 0 to 0 of 0 entries",
        emptyTable: "Sin datos",
        paginate: { previous: "Previous", next: "Next" }
      }
    });
  }

  function setTotals(t) {
    $('#kpiLineas').text(t.lineas ?? 0);
    $('#kpiOk').text(t.ok ?? 0);
    $('#kpiErr').text(t.error ?? 0);

    $('#txtLineas').text(t.lineas ?? 0);
    $('#txtOk').text(t.ok ?? 0);
    $('#txtErr').text(t.error ?? 0);
  }

  function renderPreview(rows) {
    initDT();
    dt.clear();

    for (const r of rows) {
      const row = [];
      row.push(r.__rownum ?? '');
      row.push(r.Estado ?? '');
      row.push(r.Mensaje ?? '');

      for (const c of REQUIRED_COLS) {
        row.push((r[c] ?? ''));
      }
      dt.row.add(row);
    }
    dt.draw(false);
  }

  const mdl = new bootstrap.Modal(document.getElementById('mdlImport'));

  $('#btnAbrirModal').on('click', () => {
    $('#mdlMsgErr').text('');
    $('#mdlMsgOk').text('');
    $('#csvFile').val('');
    mdl.show();
  });

  $('#btnPreview').on('click', async () => {
    $('#mdlMsgErr').text('');
    $('#mdlMsgOk').text('');
    $('#msgLayout').text('');

    const f = $('#csvFile')[0].files[0];
    if (!f) {
      $('#mdlMsgErr').text('Selecciona un archivo CSV.');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'preview');
    fd.append('csv', f);

    try {
      const res = await fetch('?action=preview', { method: 'POST', body: fd });
      const js = await res.json();

      if (!js.ok) {
        $('#mdlMsgErr').text(js.error || 'Error en preview.');
        if (js.missing && js.missing.length) {
          $('#msgLayout').text(js.error);
        }
        setTotals({lineas:0, ok:0, error:0});
        renderPreview([]);
        return;
      }

      setTotals(js.totales);
      renderPreview(js.preview || []);
      $('#mdlMsgOk').text('Preview cargado. Revisa errores antes de Fase 2.');
      mdl.hide();
    } catch (e) {
      console.error(e);
      $('#mdlMsgErr').text('Error consultando servidor (preview).');
    }
  });

  $('#btnPhase2').on('click', async () => {
    $('#mdlMsgErr').text('');
    $('#mdlMsgOk').text('');

    // Stub
    try {
      const f = $('#csvFile')[0].files[0];
    if (!f) { $('#mdlMsgErr').text('Selecciona el CSV para procesar.'); return; }
    const fd = new FormData();
    fd.append('action','process_phase2');
    fd.append('csv', f);
    const res = await fetch('?action=process_phase2', { method: 'POST', body: fd });
      const js = await res.json();
      if (!js.ok) {
        $('#mdlMsgErr').text(js.error || 'Fase 2 no disponible.');
        return;
      }
      $('#mdlMsgOk').text(js.msg || 'Procesado.');
      if (js.totales) setTotals(js.totales);
      if (js.skipped && js.skipped.folios && js.skipped.folios.length) {
        $('#msgLayout').text('Folios ya existentes (omitidos): '+js.skipped.folios.join(', '));
      }
      // refresca preview si deseas: aquí dejamos el existente
    } catch (e) {
      console.error(e);
      $('#mdlMsgErr').text('Error consultando servidor (fase 2).');
    }
  });

  // Inicializa DataTable con tabla vacía para mantener layout fijo
  $(document).ready(() => {
    initDT();
    setTotals({lineas:0, ok:0, error:0});
  });
</script>

<?php
if (file_exists($menuEnd)) include $menuEnd;
?>
</body>
</html>
