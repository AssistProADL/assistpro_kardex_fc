<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

/* =========================================================
   Helpers
   ========================================================= */
function norm_key($s){
  $s = (string)$s;

  // Quitar BOM UTF-8 (Excel)
  $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);

  // Normaliza NBSP (espacio raro de Excel)
  $s = str_replace("\xC2\xA0", " ", $s);

  // Quitar comillas típicas
  $s = str_replace(['"', "'"], '', $s);

  // Trim + colapsar espacios/tabs/saltos
  $s = trim($s);
  $s = preg_replace('/\s+/', ' ', $s);

  return strtoupper($s);
}

/**
 * Normaliza códigos tipo catálogo (zona recibo / BL)
 * - elimina chars invisibles/control
 * - deja A-Z 0-9 _ -
 */
function normalize_code($s){
  $s = norm_key($s);
  $s = preg_replace('/[^A-Z0-9_\-]/', '', $s);
  return $s;
}

function detect_delimiter($line){
  $candidates = [",",";","\t","|"];
  $best = ",";
  $max = -1;
  foreach ($candidates as $d){
    $cnt = substr_count($line, $d);
    if ($cnt > $max){
      $max = $cnt;
      $best = $d;
    }
  }
  return $best;
}

function safe_float($v){
  if ($v === null) return null;
  $v = trim((string)$v);
  if ($v === '') return null;
  $v = str_replace(",", ".", $v);
  return is_numeric($v) ? (float)$v : null;
}

/* =========================================================
   Inputs
   ========================================================= */
$tipo_ingreso = isset($_POST['tipo_ingreso']) ? norm_key($_POST['tipo_ingreso']) : '';
$run_id       = isset($_POST['run_id']) ? intval($_POST['run_id']) : 0;
$usuario      = isset($_POST['usuario']) ? norm_key($_POST['usuario']) : 'SYSTEM';

if ($tipo_ingreso === '' && $run_id <= 0) {
  echo json_encode(["ok"=>false,"error"=>"tipo_ingreso requerido (o run_id para revalidar)"]);
  exit;
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(["ok"=>false,"error"=>"archivo CSV requerido (campo: archivo)"]);
  exit;
}

$tmpPath  = $_FILES['archivo']['tmp_name'];
$origName = isset($_FILES['archivo']['name']) ? basename($_FILES['archivo']['name']) : 'layout.csv';

if (!is_readable($tmpPath)) {
  echo json_encode(["ok"=>false,"error"=>"No se pudo leer el archivo cargado"]);
  exit;
}

try {

  /* =========================================================
     0) Resolver tipo_ingreso si viene run_id
     ========================================================= */
  if ($run_id > 0) {
    $st = $pdo->prepare("SELECT tipo_ingreso FROM ap_import_runs WHERE id = ? LIMIT 1");
    $st->execute([$run_id]);
    $ti = $st->fetchColumn();
    if (!$ti) {
      echo json_encode(["ok"=>false,"error"=>"run_id no encontrado"]);
      exit;
    }
    $tipo_ingreso = norm_key($ti);
  }

  /* =========================================================
     1) Config importador (sin inventar columnas)
     ========================================================= */
  $st = $pdo->prepare("
    SELECT
      COALESCE(requiere_bl_origen,0) AS requiere_bl_origen
    FROM c_importador
    WHERE clave = ?
    LIMIT 1
  ");
  $st->execute([$tipo_ingreso]);
  $cfg = $st->fetch(PDO::FETCH_ASSOC);

  if (!$cfg) {
    echo json_encode(["ok"=>false,"error"=>"Importador no configurado en c_importador"]);
    exit;
  }

  /* =========================================================
     2) Crear / Revalidar corrida
     ========================================================= */
  if ($run_id <= 0) {
    $folio_tmp = $tipo_ingreso . "-" . date('YmdHis');

    $st = $pdo->prepare("
      INSERT INTO ap_import_runs
      (folio_importacion,tipo_ingreso,usuario,fecha_importacion,status,archivo_nombre,total_lineas,total_ok,total_err,error_resumen)
      VALUES (?,?,?,NOW(),'BORRADOR',?,0,0,0,NULL)
    ");
    $st->execute([$folio_tmp, $tipo_ingreso, $usuario, $origName]);
    $run_id = (int)$pdo->lastInsertId();

    $folio_final = $tipo_ingreso . "-" . date('Ymd') . "-" . str_pad((string)$run_id, 6, "0", STR_PAD_LEFT);
    $pdo->prepare("UPDATE ap_import_runs SET folio_importacion = ? WHERE id = ?")
        ->execute([$folio_final, $run_id]);

  } else {
    $pdo->prepare("DELETE FROM ap_import_run_rows WHERE run_id = ?")->execute([$run_id]);
    $pdo->prepare("
      UPDATE ap_import_runs
      SET archivo_nombre=?, status='BORRADOR', total_lineas=0,total_ok=0,total_err=0,error_resumen=NULL
      WHERE id=?
    ")->execute([$origName, $run_id]);
  }

  /* =========================================================
     3) Abrir CSV
     ========================================================= */
  $fh = fopen($tmpPath, 'r');
  if (!$fh) {
    echo json_encode(["ok"=>false,"error"=>"No se pudo abrir el CSV"]);
    exit;
  }

  $firstLine = fgets($fh);
  if ($firstLine === false) {
    fclose($fh);
    echo json_encode(["ok"=>false,"error"=>"CSV vacío"]);
    exit;
  }
  $delimiter = detect_delimiter($firstLine);
  rewind($fh);

  $header = fgetcsv($fh, 0, $delimiter);
  if (!$header || count($header) < 2) {
    fclose($fh);
    echo json_encode(["ok"=>false,"error"=>"Encabezado inválido"]);
    exit;
  }

  $map = [];
  foreach ($header as $i=>$h) $map[norm_key($h)] = $i;

  // LAYOUT ACTUAL (tu CSV): columna 5 = ZONA_RECIBO_DESTINO
  $requiredCols = ["BL_ORIGEN","LP_O_PRODUCTO","LOTE_SERIE","CANTIDAD","ZONA_RECIBO_DESTINO"];
  foreach ($requiredCols as $c){
    if (!isset($map[$c])) {
      fclose($fh);
      echo json_encode(["ok"=>false,"error"=>"Falta columna requerida: $c"]);
      exit;
    }
  }

  /* =========================================================
     4) Statements de validación (reales)
     ========================================================= */

  // BL_ORIGEN valida contra c_ubicacion.CodigoCSD
  $stBL = $pdo->prepare("
    SELECT 1
    FROM c_ubicacion
    WHERE TRIM(UPPER(CodigoCSD)) = TRIM(UPPER(?))
    LIMIT 1
  ");

  // ZONA_RECIBO_DESTINO puede ser:
  // - zona recibo: tubicacionesretencion.cve_ubicacion
  // - o BL destino: c_ubicacion.CodigoCSD
  $stAsZona = $pdo->prepare("
    SELECT 1
    FROM tubicacionesretencion
    WHERE TRIM(UPPER(cve_ubicacion)) = TRIM(UPPER(?))
    LIMIT 1
  ");

  $stAsBL = $pdo->prepare("
    SELECT 1
    FROM c_ubicacion
    WHERE TRIM(UPPER(CodigoCSD)) = TRIM(UPPER(?))
    LIMIT 1
  ");

  $stIns = $pdo->prepare("
    INSERT INTO ap_import_run_rows
    (run_id,linea_num,estado,mensaje,data_json)
    VALUES (?,?,?,?,?)
  ");

  /* =========================================================
     5) Loop rows
     ========================================================= */
  $total = 0; $ok = 0; $err = 0;
  $err_samples = [];
  $linea = 0;

  while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
    $linea++; $total++;

    $BL_ORIGEN = normalize_code($row[$map["BL_ORIGEN"]] ?? '');
    $LP        = norm_key($row[$map["LP_O_PRODUCTO"]] ?? '');
    $LOT       = trim((string)($row[$map["LOTE_SERIE"]] ?? ''));
    $CNT       = trim((string)($row[$map["CANTIDAD"]] ?? ''));

    // Col 5 real del CSV
    $ZONA_RECIBO_DESTINO = normalize_code($row[$map["ZONA_RECIBO_DESTINO"]] ?? '');

    $estado = "OK";
    $mensaje = "";

    // 1) BL_ORIGEN
    if ((int)$cfg['requiere_bl_origen'] === 1 && $BL_ORIGEN === '') {
      $estado = "ERR";
      $mensaje = "BL_ORIGEN requerido";
    } elseif ($BL_ORIGEN !== '') {
      $stBL->execute([$BL_ORIGEN]);
      if (!$stBL->fetchColumn()) {
        $estado = "ERR";
        $mensaje = "BL_ORIGEN no existe en c_ubicacion (CodigoCSD)";
      }
    }

    // 2) ZONA_RECIBO_DESTINO obligatorio y válido como zona OR BL destino
    if ($estado === "OK") {
      if ($ZONA_RECIBO_DESTINO === '') {
        $estado = "ERR";
        $mensaje = "ZONA_RECIBO_DESTINO requerido";
      } else {
        $okZona = false;
        $okBL   = false;

        $stAsZona->execute([$ZONA_RECIBO_DESTINO]);
        $okZona = (bool)$stAsZona->fetchColumn();

        if (!$okZona) {
          $stAsBL->execute([$ZONA_RECIBO_DESTINO]);
          $okBL = (bool)$stAsBL->fetchColumn();
        }

        if (!$okZona && !$okBL) {
          $estado = "ERR";
          $mensaje = "ZONA_RECIBO_DESTINO no existe ni en tubicacionesretencion(cve_ubicacion) ni en c_ubicacion(CodigoCSD)";
        }
      }
    }

    // 3) LP_O_PRODUCTO requerido
    if ($estado === "OK" && $LP === '') {
      $estado = "ERR";
      $mensaje = "LP_O_PRODUCTO requerido";
    }

    // 4) Cantidad: si viene, debe ser numérica > 0
    if ($estado === "OK" && $CNT !== '') {
      $n = safe_float($CNT);
      if ($n === null || $n <= 0) {
        $estado = "ERR";
        $mensaje = "CANTIDAD inválida (debe ser numérica > 0)";
      }
    }

    $data_json = json_encode([
      "BL_ORIGEN" => $BL_ORIGEN,
      "LP_O_PRODUCTO" => $LP,
      "LOTE_SERIE" => $LOT,
      "CANTIDAD" => ($CNT === '' ? null : $CNT),
      "ZONA_RECIBO_DESTINO" => $ZONA_RECIBO_DESTINO
    ], JSON_UNESCAPED_UNICODE);

    $stIns->execute([$run_id, $linea, $estado, ($mensaje===''?null:$mensaje), $data_json]);

    if ($estado === "OK") $ok++;
    else {
      $err++;
      if (count($err_samples) < 15) {
        $err_samples[] = ["linea"=>$linea, "mensaje"=>$mensaje];
      }
    }
  }

  fclose($fh);

  /* =========================================================
     6) Update run totals
     ========================================================= */
  $error_resumen = ($err > 0) ? ("Errores: ".$err) : null;

  $pdo->prepare("
    UPDATE ap_import_runs
    SET status='VALIDADO', total_lineas=?, total_ok=?, total_err=?, error_resumen=?
    WHERE id=?
  ")->execute([$total, $ok, $err, $error_resumen, $run_id]);

  echo json_encode([
    "ok" => true,
    "run_id" => $run_id,
    "tipo_ingreso" => $tipo_ingreso,
    "archivo" => $origName,
    "totales" => [
      "total_lineas" => $total,
      "total_ok" => $ok,
      "total_err" => $err
    ],
    "errores_sample" => $err_samples
  ]);

} catch (Throwable $e) {
  echo json_encode([
    "ok"=>false,
    "error"=>"Error interno",
    "detail"=>$e->getMessage()
  ]);
}
