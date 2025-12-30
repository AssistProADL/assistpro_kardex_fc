<?php
// /public/api/crm_documentos.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function j_ok($data=[]){ echo json_encode(['ok'=>true] + $data); exit; }
function j_err($msg, $extra=[]){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$msg] + $extra);
  exit;
}

function safe_basename($name){
  $name = str_replace(["\\", "/"], "_", $name);
  $name = preg_replace('/[^a-zA-Z0-9\.\-\_\(\)\s]/', '_', $name);
  $name = preg_replace('/\s+/', '_', trim($name));
  return $name ?: ('file_' . date('Ymd_His'));
}

// Carpeta destino (recomendada)
$UPLOAD_DIR_REL = '/public/uploads/crm';               // para guardar ruta en BD
$UPLOAD_DIR_ABS = realpath(__DIR__ . '/..');           // /public/api/.. = /public
$UPLOAD_DIR_ABS = $UPLOAD_DIR_ABS ? ($UPLOAD_DIR_ABS . '/uploads/crm') : null;

if (!$UPLOAD_DIR_ABS) j_err('No se pudo resolver ruta de uploads');
if (!is_dir($UPLOAD_DIR_ABS)) @mkdir($UPLOAD_DIR_ABS, 0775, true);

// Tipos permitidos (soporta con y sin acento)
$TIPOS_VALIDOS = [
  'Contrato','Cotización','Cotizacion','PO','PI','Pago',
  'Email','Verbal','CotizaciónFirmada','CotizacionFirmada'
];

// Detecta columnas opcionales (estatus/notas)
function col_exists($table, $col){
  try{
    $r = db_one("SHOW COLUMNS FROM {$table} LIKE ?", [$col]);
    return !!$r;
  } catch(Throwable $e){ return false; }
}
$HAS_ESTATUS = col_exists('crm_documentos', 'estatus');
$HAS_NOTAS   = col_exists('crm_documentos', 'notas');

// ======================= ACTION: LIST =======================
if ($action === 'list') {
  $lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : (int)($_POST['lead_id'] ?? 0);
  $opp_id  = isset($_GET['oportunidad_id']) ? (int)$_GET['oportunidad_id'] : (int)($_POST['oportunidad_id'] ?? 0);

  if ($lead_id <= 0 && $opp_id <= 0) j_err('Envía lead_id o oportunidad_id');

  $where = [];
  $params = [];

  if ($lead_id > 0) { $where[] = "lead_id = ?"; $params[] = $lead_id; }
  if ($opp_id  > 0) { $where[] = "oportunidad_id = ?"; $params[] = $opp_id; }

  if ($HAS_ESTATUS) { $where[] = "estatus = 'Activo'"; }

  $sql = "SELECT documento_id, lead_id, oportunidad_id, tipo, archivo, fecha, usuario_id"
       . ($HAS_ESTATUS ? ", estatus" : "")
       . ($HAS_NOTAS   ? ", notas" : "")
       . " FROM crm_documentos
          WHERE " . implode(" AND ", $where) . "
          ORDER BY fecha DESC, documento_id DESC";

  $rows = db_all($sql, $params);
  j_ok(['rows'=>$rows]);
}

// ======================= ACTION: ADD =======================
if ($action === 'add') {
  // Soporta ambos: multipart FILE o "archivo" como string (para tus pruebas con curl)
  $opp_id    = (int)($_POST['oportunidad_id'] ?? 0);
  $lead_id   = (int)($_POST['lead_id'] ?? 0);
  $tipo      = trim($_POST['tipo'] ?? '');
  $usuario_id= (int)($_POST['usuario_id'] ?? 0);
  $notas     = trim($_POST['notas'] ?? '');

  if ($tipo === '') j_err('tipo requerido');
  if (!in_array($tipo, $GLOBALS['TIPOS_VALIDOS'], true)) {
    j_err('tipo no permitido', ['permitidos'=>$GLOBALS['TIPOS_VALIDOS']]);
  }
  if ($opp_id <= 0 && $lead_id <= 0) j_err('Envía oportunidad_id o lead_id');

  // 1) Resolver archivo
  $archivo_rel = null;

  // Caso A: multipart file
  if (!empty($_FILES['file']) && isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {

    $orig = $_FILES['file']['name'] ?? 'documento';
    $safe = safe_basename($orig);

    // Prefijo negocio (mejor trazabilidad)
    $pref = 'CRM';
    if ($opp_id > 0) $pref .= "_OPP{$opp_id}";
    if ($lead_id > 0) $pref .= "_LEAD{$lead_id}";
    $pref .= "_" . date('Ymd_His');

    $dest_name = $pref . "_" . $safe;
    $dest_abs  = $GLOBALS['UPLOAD_DIR_ABS'] . '/' . $dest_name;

    if (!@move_uploaded_file($_FILES['file']['tmp_name'], $dest_abs)) {
      j_err('No se pudo guardar el archivo en uploads');
    }

    $archivo_rel = $GLOBALS['UPLOAD_DIR_REL'] . '/' . $dest_name;
  }
  // Caso B: ruta manual (para curl como lo estás usando)
  else {
    $archivo_post = trim($_POST['archivo'] ?? '');
    if ($archivo_post === '') j_err('Envía archivo (string) o file (multipart)');

    // Seguridad mínima: no permitir rutas raras
    if (strpos($archivo_post, '..') !== false) j_err('archivo inválido');
    $archivo_rel = $archivo_post;
  }

  try {
    $newId = null;

    db_tx(function() use ($opp_id, $lead_id, $tipo, $archivo_rel, $usuario_id, $notas, &$newId) {

      // Insert (con columnas opcionales)
      $cols = "lead_id, oportunidad_id, tipo, archivo, usuario_id";
      $vals = "?,?,?,?,?";
      $params = [$lead_id ?: null, $opp_id ?: null, $tipo, $archivo_rel, $usuario_id ?: null];

      if ($GLOBALS['HAS_NOTAS']) {
        $cols .= ", notas";
        $vals .= ", ?";
        $params[] = ($notas !== '' ? $notas : null);
      }

      if ($GLOBALS['HAS_ESTATUS']) {
        $cols .= ", estatus";
        $vals .= ", 'Activo'";
      }

      dbq("INSERT INTO crm_documentos ($cols) VALUES ($vals)", $params);
      $newId = (int)db_val("SELECT LAST_INSERT_ID()");
    });

    j_ok(['id'=>$newId, 'archivo'=>$archivo_rel]);

  } catch (Throwable $e) {
    j_err('Error al guardar documento', ['detail'=>$e->getMessage()]);
  }
}

// ======================= ACTION: DELETE (SOFT) =======================
if ($action === 'delete') {
  $documento_id = (int)($_POST['documento_id'] ?? 0);
  if ($documento_id <= 0) j_err('documento_id requerido');

  if (!$HAS_ESTATUS) {
    // si no existe estatus, hace delete físico (solo si lo deseas)
    // para ser conservadores: bloqueamos
    j_err("crm_documentos no tiene columna estatus; agrega estatus para baja lógica.");
  }

  try{
    dbq("UPDATE crm_documentos SET estatus='Inactivo' WHERE documento_id=?", [$documento_id]);
    j_ok(['deleted'=>true]);
  } catch(Throwable $e){
    j_err('Error al eliminar', ['detail'=>$e->getMessage()]);
  }
}

// ======================= ACTION: DOWNLOAD (SEGURA) =======================
if ($action === 'download') {
  // Esta acción responde archivo binario, no JSON.
  $documento_id = (int)($_GET['documento_id'] ?? 0);
  if ($documento_id <= 0) {
    http_response_code(400);
    echo "documento_id requerido";
    exit;
  }

  $doc = db_one("SELECT documento_id, archivo, tipo FROM crm_documentos WHERE documento_id=?", [$documento_id]);
  if (!$doc) { http_response_code(404); echo "No encontrado"; exit; }

  $archivo = $doc['archivo'] ?? '';
  if ($archivo === '' || strpos($archivo, '..') !== false) { http_response_code(400); echo "Archivo inválido"; exit; }

  // Solo permitimos descargar desde /public/uploads/crm
  // Si en BD se guardó otra ruta, lo bloqueamos por seguridad.
  if (strpos($archivo, '/public/uploads/crm/') !== 0) {
    http_response_code(403);
    echo "Ruta no autorizada";
    exit;
  }

  $abs = realpath(__DIR__ . '/..' . str_replace('/public', '', $archivo)); // /public + rel
  if (!$abs || !file_exists($abs)) { http_response_code(404); echo "Archivo no existe"; exit; }

  // Headers
  $fname = basename($abs);
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  header('Content-Length: ' . filesize($abs));
  readfile($abs);
  exit;
}

j_err('Acción no válida');
