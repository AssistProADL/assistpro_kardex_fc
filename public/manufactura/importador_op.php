<?php
// public/manufactura/importador_op.php
// Importador definitivo de Ordenes de Trabajo (OT) con afectacion a BD + kardex + existencias
// Reglas clave:
// - Cada LINEA del CSV = 1 OT (1 cabecera en t_ordenprod)
// - Componentes se obtienen de BOM: t_artcompuesto (Cve_Articulo = producto compuesto)
// - Redondeo SOLO para componentes "pieza" (heuristica por unidad de medida)
// - Consolidacion de MP solo para afectacion (evento global por FolioImport)
// - LPs NO obligatorios: si no existen se crean genericos en c_charolas (tipo Pallet/Contenedor)
// - PT se registra en ts_existenciacajas (contenedor), ts_existenciatarima (pallet) o ts_existenciapiezas (sin LP)
// - Hora inicio/fin se guardan en t_ordenprod.Hora_Ini/Hora_Fin y td_ordenprod (no existe hora: se guarda en Activo/Referencia via run rows)

require_once __DIR__ . '/../../app/db.php';

// ------------------------------
// Config
// ------------------------------
$TITULO = "Importador definitivo de OTs";
$LAYOUT_FILENAME = "Layout_Imp_def_AP.csv";

// Layout definitivo AP: Fol_Imp (batch) + OT_Cliente (Referencia). No existe Folio_OP en el Excel.
$REQUIRED_COLS = [
  "Fol_Imp",          // Folio de importacion (batch). Puede venir vacio, se autogenera.
  "Usuario",          // Usuario (debe existir)
  "OT_Cliente",       // Se guarda en Referencia
  "Fecha_OT",         // Fecha OT
  "Fecha_Compromiso", // Fecha compromiso
  "Almacen",          // Almacen (clave)
  "Area_Produccion",  // Area
  "MP_BL_ORIGEN",     // CodigoCSD origen
  "Articulo_Compuesto",// Producto compuesto
  "Lote",             // Lote (opcional)
  "Caducidad",        // Caducidad (opcional)
  "Cantidad_a_Producir",// Cantidad
  "PT_BL_DESTINO",    // CodigoCSD destino
  "LP_CONTENEDOR",    // LP contenedor (opcional)
  "LP_PALLET"         // LP pallet (opcional)
];

$DEFAULT_ID_PROVEEDOR = 1; // Ajustar a tu dueno default (NOT NULL en existencias)

// Unidades consideradas "pieza" (para redondeo). Ajustable.
$PIEZA_UOMS = ['PZA','PIEZAS','PIEZA','PCS','PC','UN','UND','EA'];

// ------------------------------
// Helpers base
// ------------------------------
function ap_norm_col($s) {
  $s = (string)$s;
  $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
  $s = trim($s);
  $s = preg_replace('/\s+/', ' ', $s);
  $s2 = str_replace([' ', '-', '.'], '_', $s);
  $s2 = preg_replace('/_+/', '_', $s2);
  $s2 = strtoupper($s2);
  // Alias de columnas (layout puede variar)
  $alias = [
    'FOLIO_IMPORTACION' => 'FOL_IMP',
    'FOLIO_IMPORT' => 'FOL_IMP',
    'FOLIOIMP' => 'FOL_IMP',
    'FOLIO_IMP' => 'FOL_IMP',
    'FOL_IMP' => 'FOL_IMP',
    'USUARIO_' => 'USUARIO',
    'OTCLIENTE' => 'OT_CLIENTE',
    'OT_CLIENTE' => 'OT_CLIENTE',
    'FECHA_COMP' => 'FECHA_COMPROMISO',
    'FECHA_COMPROMISO' => 'FECHA_COMPROMISO',
    'CANTIDAD_A_PRODUCIR' => 'CANTIDAD_A_PRODUCIR',
    'CANTIDAD_A_PRODUCIR_' => 'CANTIDAD_A_PRODUCIR'
  ];
  return $alias[$s2] ?? $s2;
}


function ap_send_json($arr, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function ap_to_float($v) {
  if ($v === null) return null;
  $v = trim((string)$v);
  if ($v === '') return null;
  $v = str_replace([' '], [''], $v);
  if (substr_count($v, ',') > 0 && substr_count($v, '.') === 0) {
    $v = str_replace(',', '.', $v);
  } else {
    $v = str_replace(',', '', $v);
  }
  if (!is_numeric($v)) return null;
  return (float)$v;
}

function ap_parse_date($v) {
  $v = trim((string)$v);
  if ($v === '') return null;
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
  if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $v, $m)) {
    return $m[3].'-'.$m[2].'-'.$m[1];
  }
  return null;
}

function ap_read_csv_rows($tmpPath, &$meta = []) {
  $meta = ['cols_original'=>[], 'cols_norm'=>[], 'map_norm_to_original'=>[], 'rows'=>[]];
  $fh = fopen($tmpPath, 'r');
  if (!$fh) return false;

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
  if (!$header) { fclose($fh); return false; }

  $meta['cols_original'] = $header;
  foreach ($header as $i => $h) {
    $n = ap_norm_col($h);
    $meta['cols_norm'][$i] = $n;
    $meta['map_norm_to_original'][$n] = $h;
  }

  $line = 1;
  while (($row = fgetcsv($fh, 0, $bestDelim)) !== false) {
    $line++;
    if (count($row) === 1 && trim((string)$row[0]) === '') continue;
    $assoc = ['__line__' => $line];
    foreach ($meta['cols_norm'] as $i => $ncol) {
      $assoc[$ncol] = $row[$i] ?? null;
    }
    $meta['rows'][] = $assoc;
  }
  fclose($fh);
  return true;
}

function ap_has_table(PDO $pdo, string $table): bool {
  $st = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
  $st->execute([$table]);
  return (bool)$st->fetchColumn();
}

function ap_has_col(PDO $pdo, string $table, string $col): bool {
  $st = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
  $st->execute([$table, $col]);
  return (bool)$st->fetchColumn();
}

// ------------------------------
// Folios
// ------------------------------
function ap_next_folio(PDO $pdo, string $prefix, string $col, string $table, int $pad = 3): string {
  $date = date('Ymd');
  $like = $prefix.$date.'-%';
  $sql = "SELECT MAX($col) FROM $table WHERE $col LIKE ?";
  $st = $pdo->prepare($sql);
  $st->execute([$like]);
  $max = (string)($st->fetchColumn() ?? '');
  $n = 0;
  if ($max) {
    $parts = explode('-', $max);
    $n = (int)end($parts);
  }
  $n++;
  return $prefix.$date.'-'.str_pad((string)$n, $pad, '0', STR_PAD_LEFT);
}

function ap_next_folio_import(PDO $pdo): string {
  // Fol_Imp-yyyymmdd-001
  return ap_next_folio($pdo, 'Fol_Imp-', 'FolioImport', 't_ordenprod', 3);
}

function ap_next_folio_pro(PDO $pdo): string {
  // OPyyyymmdd-001
  return ap_next_folio($pdo, 'OP', 'Folio_Pro', 't_ordenprod', 3);
}

// ------------------------------
// Catalogos
// ------------------------------
function ap_get_user(PDO $pdo, string $cve_usuario): ?array {
  $cve_usuario = trim($cve_usuario);
  $st = $pdo->prepare("SELECT id_user, cve_usuario FROM c_usuario WHERE UPPER(TRIM(cve_usuario)) = UPPER(TRIM(?)) LIMIT 1");
  $st->execute([$cve_usuario]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function ap_get_almacen(PDO $pdo, string $clave): ?array {
  $clave = trim($clave);
  if ($clave==='') return null;
  $st = $pdo->prepare("SELECT id, clave FROM c_almacenp WHERE UPPER(TRIM(clave)) = UPPER(TRIM(?)) AND (Activo IS NULL OR Activo<>0) LIMIT 1");
  $st->execute([$clave]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function ap_get_ubicacion_por_csd(PDO $pdo, string $csd): ?array {
  $csd = trim($csd);
  if ($csd==='') return null;
  $st = $pdo->prepare("SELECT idy_ubica, CodigoCSD, Activo, AreaProduccion, cve_almac FROM c_ubicacion WHERE UPPER(TRIM(CodigoCSD)) = UPPER(TRIM(?)) AND (Activo IS NULL OR Activo<>0) LIMIT 1");
  $st->execute([$csd]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function ap_get_articulo(PDO $pdo, string $cve): ?array {
  $cve = trim($cve);
  if ($cve==='') return null;
  $st = $pdo->prepare("SELECT id, cve_articulo, cve_umed, Compuesto, Activo FROM c_articulo WHERE UPPER(TRIM(cve_articulo)) = UPPER(TRIM(?)) AND (Activo IS NULL OR Activo<>0) LIMIT 1");
  $st->execute([$cve]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function ap_is_pieza_component(PDO $pdo, string $cve_articulo, array $PIEZA_UOMS): bool {
  $art = ap_get_articulo($pdo, $cve_articulo);
  if (!$art) return false;
  $idU = $art['cve_umed'] ?? null;
  if (!$idU) return false;
  $st = $pdo->prepare("SELECT cve_umed, des_umed FROM c_unimed WHERE id_umed = ? LIMIT 1");
  $st->execute([$idU]);
  $u = $st->fetch(PDO::FETCH_ASSOC);
  if (!$u) return false;
  $key = strtoupper(trim((string)($u['cve_umed'] ?? '')));
  $des = strtoupper(trim((string)($u['des_umed'] ?? '')));
  foreach ($PIEZA_UOMS as $p) {
    $p = strtoupper($p);
    if ($key === $p) return true;
    if ($p !== '' && strpos($des, $p) !== false) return true;
  }
  return false;
}

// ------------------------------
// LP (c_charolas)
// ------------------------------
function ap_get_or_create_lp(PDO $pdo, int $almacen_id, string $lp, string $tipo): array {
  // tipo: 'Pallet' o 'Contenedor'
  $lp = trim($lp);
  if ($lp==='') return [null, null];

  $st = $pdo->prepare("SELECT IDContenedor, CveLP, tipo, Activo FROM c_charolas WHERE cve_almac = ? AND UPPER(TRIM(CveLP)) = UPPER(TRIM(?)) LIMIT 1");
  $st->execute([$almacen_id, $lp]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  if ($r) return [(int)$r['IDContenedor'], $r['CveLP']];

  // Crear generico
  $desc = ($tipo==='Contenedor') ? 'Contenedor generico (import)' : 'Pallet generico (import)';
  $sql = "INSERT INTO c_charolas (cve_almac, Clave_Contenedor, descripcion, Permanente, Pedido, sufijo, tipo, Activo, CveLP, TipoGen)
          VALUES (?, ?, ?, 0, NULL, NULL, ?, 1, ?, 1)";
  $st = $pdo->prepare($sql);
  $st->execute([$almacen_id, $lp, $desc, $tipo, $lp]);
  return [(int)$pdo->lastInsertId(), $lp];
}

// ------------------------------
// BOM: t_artcompuesto
// ------------------------------
function ap_get_bom(PDO $pdo, string $producto): array {
  $producto = trim((string)$producto);
  if ($producto==='') return [];

  // BOM alineado a orden_produccion.php:
  // PT (compuesto) = t_artcompuesto.Cve_ArtComponente
  // Componentes (MP) = t_artcompuesto.Cve_Articulo
  // Para no modificar el resto del importador, devolvemos el componente en el campo Cve_ArtComponente.
  // Nota operativa: algunos ambientes traen Activo=0 aunque el BOM sea vigente; hacemos fallback sin filtro.

  $sqlBase = "SELECT Cve_Articulo AS Cve_ArtComponente, Cantidad, cve_umed, Etapa
              FROM t_artcompuesto
              WHERE REPLACE(REPLACE(UPPER(TRIM(Cve_ArtComponente)),'-',''),' ','') = REPLACE(REPLACE(UPPER(TRIM(?)),'-',''),' ','')
                AND REPLACE(REPLACE(UPPER(TRIM(Cve_Articulo)),'-',''),' ','') <> REPLACE(REPLACE(UPPER(TRIM(Cve_ArtComponente)),'-',''),' ','')
                AND COALESCE(Cantidad,0) > 0";

  $st = $pdo->prepare($sqlBase." AND (Activo IS NULL OR Activo<>0) ORDER BY Etapa, Cve_Articulo");
  $st->execute([$producto]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if ($rows) return $rows;

  $st2 = $pdo->prepare($sqlBase." ORDER BY Etapa, Cve_Articulo");
  $st2->execute([$producto]);
  return $st2->fetchAll(PDO::FETCH_ASSOC);
}
// ------------------------------
// Existencias (upsert acumulativo)
// ------------------------------
function ap_upsert_exist_piezas(PDO $pdo, int $almac_id, int $idy_ubica, string $art, string $lote, float $qty, int $id_proveedor) {
  // PK no declarada en dump, pero tratamos como llave logica: (cve_almac, idy_ubica, cve_articulo, cve_lote, ID_Proveedor)
  $sqlSel = "SELECT Existencia FROM ts_existenciapiezas WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=? AND cve_lote=? AND ID_Proveedor=? LIMIT 1";
  $st = $pdo->prepare($sqlSel);
  $st->execute([$almac_id,$idy_ubica,$art,$lote,$id_proveedor]);
  $cur = $st->fetchColumn();
  if ($cur === false) {
    // id es NOT NULL y no auto: generamos consecutivo simple
    $id = (int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM ts_existenciapiezas")->fetchColumn();
    $sqlIns = "INSERT INTO ts_existenciapiezas (cve_almac, idy_ubica, id, cve_articulo, cve_lote, Existencia, ID_Proveedor, Cuarentena) VALUES (?,?,?,?,?,?,?,0)";
    $pdo->prepare($sqlIns)->execute([$almac_id,$idy_ubica,$id,$art,$lote,$qty,$id_proveedor]);
  } else {
    $sqlUpd = "UPDATE ts_existenciapiezas SET Existencia = COALESCE(Existencia,0)+? WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=? AND cve_lote=? AND ID_Proveedor=?";
    $pdo->prepare($sqlUpd)->execute([$qty,$almac_id,$idy_ubica,$art,$lote,$id_proveedor]);
  }
}

function ap_upsert_exist_tarima(PDO $pdo, int $almac_id, int $idy_ubica, string $art, string $lote, int $ntarima, float $qty, int $id_proveedor) {
  // Llave logica: (cve_almac, idy_ubica, cve_articulo, lote, ntarima, ID_Proveedor)
  $sqlSel = "SELECT existencia FROM ts_existenciatarima WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=? AND lote=? AND ntarima=? AND ID_Proveedor=? LIMIT 1";
  $st = $pdo->prepare($sqlSel);
  $st->execute([$almac_id,$idy_ubica,$art,$lote,$ntarima,$id_proveedor]);
  $cur = $st->fetchColumn();
  if ($cur === false) {
    $sqlIns = "INSERT INTO ts_existenciatarima (cve_almac, idy_ubica, cve_articulo, lote, Fol_Folio, ntarima, capcidad, existencia, Activo, ID_Proveedor, Cuarentena)
               VALUES (?,?,?,?,0,?,0,?,1,?,0)";
    $pdo->prepare($sqlIns)->execute([$almac_id,$idy_ubica,$art,$lote,$ntarima,$qty,$id_proveedor]);
  } else {
    $sqlUpd = "UPDATE ts_existenciatarima SET existencia = COALESCE(existencia,0)+? WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=? AND lote=? AND ntarima=? AND ID_Proveedor=?";
    $pdo->prepare($sqlUpd)->execute([$qty,$almac_id,$idy_ubica,$art,$lote,$ntarima,$id_proveedor]);
  }
}

function ap_upsert_exist_caja(PDO $pdo, int $idy_ubica, string $art, string $lote, float $qty, int $id_caja, int $almac_id, ?int $ntarima=null) {
  // Llave logica: (idy_ubica, cve_articulo, cve_lote, Id_Caja, Cve_Almac)
  $sqlSel = "SELECT PiezasXCaja FROM ts_existenciacajas WHERE idy_ubica=? AND cve_articulo=? AND cve_lote=? AND Id_Caja=? AND Cve_Almac=? LIMIT 1";
  $st = $pdo->prepare($sqlSel);
  $st->execute([$idy_ubica,$art,$lote,$id_caja,$almac_id]);
  $cur = $st->fetchColumn();
  if ($cur === false) {
    $sqlIns = "INSERT INTO ts_existenciacajas (idy_ubica, cve_articulo, cve_lote, PiezasXCaja, Id_Caja, Cve_Almac, nTarima) VALUES (?,?,?,?,?,?,?)";
    $pdo->prepare($sqlIns)->execute([$idy_ubica,$art,$lote,$qty,$id_caja,$almac_id,$ntarima]);
  } else {
    $sqlUpd = "UPDATE ts_existenciacajas SET PiezasXCaja = COALESCE(PiezasXCaja,0)+?, nTarima = COALESCE(nTarima, ?) WHERE idy_ubica=? AND cve_articulo=? AND cve_lote=? AND Id_Caja=? AND Cve_Almac=?";
    $pdo->prepare($sqlUpd)->execute([$qty,$ntarima,$idy_ubica,$art,$lote,$id_caja,$almac_id]);
  }
}

// ------------------------------
// Kardex
// ------------------------------
function ap_cardex(PDO $pdo, array $row) {
  $cols = ['cve_articulo','cve_lote','fecha','origen','destino','cantidad','id_TipoMovimiento','cve_usuario','Cve_Almac','Activo','Fec_Ingreso','Referencia','contenedor_lp','pallet_lp'];
  $sql = "INSERT INTO t_cardex (".implode(',', $cols).") VALUES (?,?,?,?,?,?,?,?,?,1,?,?,?,?)";
  $st = $pdo->prepare($sql);
  $st->execute([
    $row['cve_articulo'] ?? null,
    $row['cve_lote'] ?? null,
    $row['fecha'] ?? date('Y-m-d H:i:s'),
    $row['origen'] ?? null,
    $row['destino'] ?? null,
    $row['cantidad'] ?? 0,
    $row['id_TipoMovimiento'] ?? null,
    $row['cve_usuario'] ?? null,
    $row['Cve_Almac'] ?? null,
    $row['Fec_Ingreso'] ?? date('Y-m-d'),
    $row['Referencia'] ?? null,
    $row['contenedor_lp'] ?? null,
    $row['pallet_lp'] ?? null,
  ]);
}

// ------------------------------
// Acciones API
// ------------------------------
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'layout') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename='.$LAYOUT_FILENAME);
  echo implode(',', $REQUIRED_COLS)."\n";
  echo "A0,USR001,45906094,10/10/2025,10/10/2026,WHCR,CE,CEKANBAN01,A6-43019-001B2-106,,,12,CEKANBAN02,LP_CONT_0001,LP_PAL_0001\n";
  exit;
}

if ($action === 'preview' || $action === 'process') {
  if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    ap_send_json(['ok'=>false,'msg'=>'Archivo CSV requerido'], 400);
  }

  $tmp = $_FILES['csv']['tmp_name'];
  $meta = [];
  if (!ap_read_csv_rows($tmp, $meta)) {
    ap_send_json(['ok'=>false,'msg'=>'No se pudo leer el CSV'], 400);
  }

  // validar columnas
  $present = array_values(array_unique($meta['cols_norm']));
  $missing = [];
  foreach ($REQUIRED_COLS as $c) {
    $n = ap_norm_col($c);
    if (!in_array($n, $present, true)) $missing[] = $c;
  }
  if ($missing) {
    ap_send_json(['ok'=>false,'msg'=>'Faltan columnas: '.implode(', ', $missing), 'missing'=>$missing]);
  }

  $pdo = db();
  $rows = $meta['rows'];

  // Determinar FolioImport del batch (misma para todo el archivo):
  $folioImp = '';
  foreach ($rows as $r) {
    $v = trim((string)($r['FOL_IMP'] ?? ''));
    if ($v !== '') { $folioImp = $v; break; }
  }
  if ($folioImp === '') {
    $folioImp = ap_next_folio_import($pdo);
  }

  $preview = [];
  $errors = [];

  foreach ($rows as $idx => $r) {
    $ln = (int)$r['__line__'];

    $usuario = trim((string)$r['USUARIO']);
    $otc = trim((string)$r['OT_CLIENTE']);
    $fecha = ap_parse_date($r['FECHA_OT']);
    $alm = trim((string)$r['ALMACEN']);
    $area = trim((string)$r['AREA_PRODUCCION']);
    $mpCsd = trim((string)$r['MP_BL_ORIGEN']);
    $ptCsd = trim((string)$r['PT_BL_DESTINO']);
    $prod = trim((string)$r['ARTICULO_COMPUESTO']);
    $lote = trim((string)$r['LOTE']);
    $qty = ap_to_float($r['CANTIDAD_A_PRODUCIR']);
    $lpCont = trim((string)$r['LP_CONTENEDOR']);
    $lpPal = trim((string)$r['LP_PALLET']);

    $e = [];
    if ($usuario==='') $e[] = 'Usuario vacio';
    if (!$otc) $e[] = 'OT_Cliente vacio';
    if (!$fecha) $e[] = 'Fecha_OT invalida';
    if (!$alm) $e[] = 'Almacen vacio';
    if (!$mpCsd) $e[] = 'MP_BL_ORIGEN(CodigoCSD) vacio';
    if (!$ptCsd) $e[] = 'PT_BL_DESTINO(CodigoCSD) vacio';
    if (!$prod) $e[] = 'Articulo_Compuesto vacio';
    if ($qty === null || $qty <= 0) $e[] = 'Cantidad_a_Producir invalida';

    $u = $usuario ? ap_get_user($pdo, $usuario) : null;
    if (!$u) $e[] = 'Usuario no existe';

    $a = $alm ? ap_get_almacen($pdo, $alm) : null;
    if (!$a) $e[] = 'Almacen no existe/inactivo';

    $ubO = $mpCsd ? ap_get_ubicacion_por_csd($pdo, $mpCsd) : null;
    if (!$ubO) $e[] = 'MP CodigoCSD no existe/inactivo';
    else if (($ubO['AreaProduccion'] ?? 'N') !== 'S') $e[] = 'MP CodigoCSD no es AreaProduccion';

    $ubD = $ptCsd ? ap_get_ubicacion_por_csd($pdo, $ptCsd) : null;
    if (!$ubD) $e[] = 'PT CodigoCSD no existe/inactivo';
    else if (($ubD['AreaProduccion'] ?? 'N') !== 'S') $e[] = 'PT CodigoCSD no es AreaProduccion';

    $art = $prod ? ap_get_articulo($pdo, $prod) : null;
    if (!$art) $e[] = 'Articulo no existe/inactivo';

    $bom = [];
    if ($art) {
      $compFlag = strtoupper(trim((string)($art['Compuesto'] ?? '')));
      $esCompuesto = in_array($compFlag, ['S','1','SI','Y'], true);
      if ($esCompuesto) {
        $bom = ap_get_bom($pdo, $prod);
        if (!$bom) $e[] = 'Producto compuesto sin BOM (t_artcompuesto)';
      }
    }

    if ($e) {
      $errors[] = ['line'=>$ln,'errors'=>$e];
    }

    $preview[] = [
      'line'=>$ln,
      'FolioImport'=>$folioImp,
      'Usuario'=>$usuario,
      'OT_Cliente'=>$otc,
      'Producto'=>$prod,
      'Cantidad'=>$qty,
      'MP_CodigoCSD'=>$mpCsd,
      'PT_CodigoCSD'=>$ptCsd,
      'LP_Contenedor'=>$lpCont,
      'LP_Pallet'=>$lpPal,
      'Componentes'=>count($bom)
    ];
  }

  if ($action === 'preview') {
    ap_send_json(['ok'=>true,'folioImport'=>$folioImp,'rows'=>$preview,'errors'=>$errors]);
  }

  // process
  if ($errors) {
    ap_send_json(['ok'=>false,'msg'=>'Hay errores de validacion. No se proceso.','folioImport'=>$folioImp,'errors'=>$errors], 400);
  }

  // Ejecutar importacion + produccion batch (paso 1 + paso 2) dentro de transaccion
  try {
    $pdo->beginTransaction();

    // registrar run
    $runId = null;
    if (ap_has_table($pdo,'ap_import_runs')) {
      $st = $pdo->prepare("INSERT INTO ap_import_runs (folio_importacion, tipo_ingreso, usuario, status, total_lineas, total_ok) VALUES (?,?,?,'EN_PROCESO',?,0)");
      $st->execute([$folioImp,'OT',$rows[0]['USUARIO'] ?? '', count($rows)]);
      $runId = (int)$pdo->lastInsertId();
    }

    $horaIni = date('Y-m-d H:i:s');

    $foliosPro = []; // para reporte

    foreach ($rows as $r) {
      $ln = (int)$r['__line__'];
      $usuario = trim((string)$r['USUARIO']);
      $otc = trim((string)$r['OT_CLIENTE']);
      $fecha = ap_parse_date($r['FECHA_OT']);
      $alm = trim((string)$r['ALMACEN']);
      $mpCsd = trim((string)$r['MP_BL_ORIGEN']);
      $ptCsd = trim((string)$r['PT_BL_DESTINO']);
      $prod = trim((string)$r['ARTICULO_COMPUESTO']);
      $lote = trim((string)$r['LOTE']);
      $qty = (float)ap_to_float($r['CANTIDAD_A_PRODUCIR']);
      $lpCont = trim((string)$r['LP_CONTENEDOR']);
      $lpPal = trim((string)$r['LP_PALLET']);

      $u = ap_get_user($pdo, $usuario);
      $a = ap_get_almacen($pdo, $alm);
      $ubO = ap_get_ubicacion_por_csd($pdo, $mpCsd);
      $ubD = ap_get_ubicacion_por_csd($pdo, $ptCsd);

      $folioPro = ap_next_folio_pro($pdo);
      $foliosPro[] = $folioPro;

      // Cabecera OT
      $sqlH = "INSERT INTO t_ordenprod (Folio_Pro, FolioImport, cve_almac, Cve_Articulo, Cve_Lote, Cantidad, Cant_Prod, Cve_Usuario, Fecha, Usr_Armo, Hora_Ini, Status, Referencia, Cve_Almac_Ori, Tipo, idy_ubica, idy_ubica_dest)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      $pdo->prepare($sqlH)->execute([
        $folioPro,
        $folioImp,
        $a['cve_almac'],
        $prod,
        $lote ?: null,
        $qty,
        (int)ceil($qty),
        $usuario,
        $fecha ? ($fecha.' 00:00:00') : null,
        $usuario,
        $horaIni,
        'P',
        $otc,
        $a['cve_almac'],
        $r['AREA_PRODUCCION'] ?? null,
        $ubO['idy_ubica'] ?? null,
        $ubD['idy_ubica'] ?? null,
      ]);

      // Detalle componentes
      $bom = ap_get_bom($pdo, $prod);
      foreach ($bom as $b) {
        $comp = $b['Cve_ArtComponente'];
        $factor = (float)$b['Cantidad'];
        $req = $qty * $factor;
        // redondeo SOLO si componente es pieza
        if (ap_is_pieza_component($pdo, $comp, $PIEZA_UOMS)) {
          $req = ceil($req);
        }
        $sqlD = "INSERT INTO td_ordenprod (Folio_Pro, Cve_Articulo, Cve_Lote, Fecha_Prod, Cantidad, Usr_Armo, Activo, Referencia, Cve_Almac_Ori)
                 VALUES (?,?,?,?,?,?,1,?,?)";
        $pdo->prepare($sqlD)->execute([
          $folioPro,
          $comp,
          null,
          $fecha ? ($fecha.' 00:00:00') : null,
          $req,
          $usuario,
          $otc,
          $a['cve_almac'],
        ]);
      }

      // bitacora run rows (incluye LPs)
      if ($runId && ap_has_table($pdo,'ap_import_run_rows')) {
        $data = [
          'folioPro'=>$folioPro,
          'folioImport'=>$folioImp,
          'usuario'=>$usuario,
          'ot_cliente'=>$otc,
          'producto'=>$prod,
          'cantidad'=>$qty,
          'mp_csd'=>$mpCsd,
          'pt_csd'=>$ptCsd,
          'lp_contenedor'=>$lpCont,
          'lp_pallet'=>$lpPal
        ];
        $pdo->prepare("INSERT INTO ap_import_run_rows (run_id, linea_num, estado, mensaje, data_json) VALUES (?,?, 'OK', NULL, ?)")
            ->execute([$runId, $ln, json_encode($data, JSON_UNESCAPED_UNICODE)]);
      }

      // PT existencias (empaque logico): crear LP si es necesario
      $almId = (int)($a['id'] ?? 0);
      $idProv = $DEFAULT_ID_PROVEEDOR;

      $contId = null; $palId = null;
      if ($almId > 0) {
        if ($lpCont !== '') {
          [$contId, $lpContNorm] = ap_get_or_create_lp($pdo, $almId, $lpCont, 'Contenedor');
        }
        if ($lpPal !== '') {
          [$palId, $lpPalNorm] = ap_get_or_create_lp($pdo, $almId, $lpPal, 'Pallet');
        }
      }

      $idyDest = (int)($ubD['idy_ubica'] ?? 0);
      if ($palId) {
        ap_upsert_exist_tarima($pdo, $almId, $idyDest, $prod, $lote ?: '', $palId, $qty, $idProv);
      } elseif ($contId) {
        ap_upsert_exist_caja($pdo, $idyDest, $prod, $lote ?: '', $qty, $contId, $almId, null);
      } else {
        ap_upsert_exist_piezas($pdo, $almId, $idyDest, $prod, $lote ?: '', $qty, $idProv);
      }

      // Kardex entrada PT (por OT)
      ap_cardex($pdo, [
        'cve_articulo'=>$prod,
        'cve_lote'=>$lote ?: null,
        'fecha'=>date('Y-m-d H:i:s'),
        'origen'=>null,
        'destino'=>$ptCsd,
        'cantidad'=>$qty,
        'id_TipoMovimiento'=>null,
        'cve_usuario'=>$usuario,
        'Cve_Almac'=>$alm,
        'Fec_Ingreso'=>date('Y-m-d'),
        'Referencia'=>$folioPro,
        'contenedor_lp'=>$lpCont ?: null,
        'pallet_lp'=>$lpPal ?: null,
      ]);
    }

    // Consolidacion MP por FolioImport: suma de td_ordenprod de los foliosPro generados
    // Salida MP: un evento global por componente
    if ($foliosPro) {
      $in = implode(',', array_fill(0, count($foliosPro), '?'));
      $sqlC = "SELECT d.Cve_Articulo, SUM(d.Cantidad) AS qty FROM td_ordenprod d WHERE d.Folio_Pro IN ($in) GROUP BY d.Cve_Articulo";
      $st = $pdo->prepare($sqlC);
      $st->execute($foliosPro);
      $mpRows = $st->fetchAll(PDO::FETCH_ASSOC);

      foreach ($mpRows as $m) {
        ap_cardex($pdo, [
          'cve_articulo'=>$m['Cve_Articulo'],
          'cve_lote'=>null,
          'fecha'=>date('Y-m-d H:i:s'),
          'origen'=>null,
          'destino'=>null,
          'cantidad'=>-(float)$m['qty'],
          'id_TipoMovimiento'=>null,
          'cve_usuario'=>$rows[0]['USUARIO'] ?? null,
          'Cve_Almac'=>$rows[0]['ALMACEN'] ?? null,
          'Fec_Ingreso'=>date('Y-m-d'),
          'Referencia'=>$folioImp,
        ]);
      }
    }

    $horaFin = date('Y-m-d H:i:s');
    $pdo->prepare("UPDATE t_ordenprod SET Hora_Fin=?, Status='T' WHERE FolioImport=?")
        ->execute([$horaFin, $folioImp]);

    if ($runId) {
      $pdo->prepare("UPDATE ap_import_runs SET status='OK', total_ok=total_lineas WHERE id=?")->execute([$runId]);
    }

    $pdo->commit();

    ap_send_json(['ok'=>true,'msg'=>'Importacion y produccion aplicada','folioImport'=>$folioImp,'foliosPro'=>$foliosPro]);

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    ap_send_json(['ok'=>false,'msg'=>'Error al procesar: '.$e->getMessage(), 'folioImport'=>$folioImp], 500);
  }
}

// ------------------------------
// UI (simple)
// ------------------------------
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?php echo htmlspecialchars($TITULO); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-size: 12px; }
    .card-header{ background:#0F5AAD; color:#fff; font-weight:600; }
    #overlay{position:fixed;inset:0;background:rgba(255,255,255,.75);display:none;align-items:center;justify-content:center;z-index:9999}
  </style>
</head>
<body>
<?php
  // Integracion visual estandar del sistema
  // (menu global + cierre)
  $menuPath = __DIR__ . '/../bi/_menu_global.php';
  if (is_file($menuPath)) { require $menuPath; }
?>
<div id="overlay">
  <div class="text-center">
    <div class="spinner-border" role="status"><span class="visually-hidden">Procesando...</span></div>
    <div id="overlayMsg" class="mt-2 fw-semibold">Procesando...</div>
  </div>
</div>
<div class="container-fluid mt-3">
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo htmlspecialchars($TITULO); ?></span>
      <a class="btn btn-outline-light btn-sm" href="?action=layout">Descargar layout</a>
    </div>
    <div class="card-body">

      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
          <label class="form-label">CSV</label>
          <input type="file" id="csv" class="form-control form-control-sm" accept=".csv"/>
        </div>
        <div class="col-12 col-md-6 text-end">
          <button class="btn btn-secondary btn-sm" id="btnPrev">Previsualizar</button>
          <button class="btn btn-primary btn-sm" id="btnProc">Procesar (BD + Kardex)</button>
        </div>
      </div>

      <hr/>
      <div id="msg"></div>
      <div class="table-responsive">
        <table class="table table-sm table-striped" id="tbl" style="display:none">
          <thead><tr>
            <th>Linea</th><th>FolioImport</th><th>OT Cliente</th><th>Producto</th><th>Cant</th><th>MP CSD</th><th>PT CSD</th><th>LP Cont</th><th>LP Pal</th><th>Componentes</th>
          </tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <div id="errs"></div>

    </div>
  </div>
</div>

<script>
const overlay = document.getElementById('overlay');
const overlayMsg = document.getElementById('overlayMsg');
const msg = document.getElementById('msg');
const tbl = document.getElementById('tbl');
const tbody = tbl.querySelector('tbody');
const errs = document.getElementById('errs');

function setOverlay(on, text){
  overlay.style.display = on ? 'flex' : 'none';
  if (typeof text === 'string' && text !== '') overlayMsg.textContent = text;
}
function alertBox(type, html){ return `<div class="alert alert-${type} py-2">${html}</div>`; }

async function send(action){
  const f = document.getElementById('csv').files[0];
  if(!f){ msg.innerHTML = alertBox('warning','Selecciona un CSV'); return; }
  const fd = new FormData();
  fd.append('action', action);
  fd.append('csv', f);

  setOverlay(true, (action==='preview') ? 'Validando layout y calculando componentes...' : 'Procesando importacion: creando OTs, consolidando MP y registrando PT...');
  msg.innerHTML=''; errs.innerHTML=''; tbody.innerHTML=''; tbl.style.display='none';

  try{
    const res = await fetch('', {method:'POST', body:fd});
    const data = await res.json();
    if(!res.ok || !data.ok){
      msg.innerHTML = alertBox('danger', data.msg || 'Error');
      if(data.errors){
        errs.innerHTML = alertBox('warning', `<b>Errores:</b><br>${data.errors.map(e=>`Linea ${e.line}: ${e.errors.join(' | ')}`).join('<br>')}`);
      }
      return;
    }

    msg.innerHTML = alertBox('success', `${data.ok ? 'OK' : ''} FolioImport: <b>${data.folioImport}</b>`);
    if(data.rows){
      tbl.style.display='table';
      data.rows.forEach(r=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.line}</td><td>${r.FolioImport}</td><td>${r.OT_Cliente}</td><td>${r.Producto}</td><td>${r.Cantidad}</td><td>${r.MP_CodigoCSD}</td><td>${r.PT_CodigoCSD}</td><td>${r.LP_Contenedor||''}</td><td>${r.LP_Pallet||''}</td><td>${r.Componentes}</td>`;
        tbody.appendChild(tr);
      });
    }
    if(data.foliosPro){
      msg.innerHTML += alertBox('info', `OTs generadas: <b>${data.foliosPro.length}</b> (primer folio: ${data.foliosPro[0]})`);
    }
  } finally {
    setOverlay(false);
  }
}

document.getElementById('btnPrev').addEventListener('click', ()=>send('preview'));
document.getElementById('btnProc').addEventListener('click', ()=>{ if(confirm('Esto afectara BD/Kardex/Existencias. Continuar?')) send('process'); });
</script>
</div>
<?php
  $endPath = __DIR__ . '/../bi/_end.php';
  if (is_file($endPath)) { require $endPath; }
?>
</body>
</html>
