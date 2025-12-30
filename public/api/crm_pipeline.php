<?php
// /public/api/crm_pipeline.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function j_ok($data = []) { echo json_encode(['ok'=>true] + $data); exit; }
function j_err($msg, $extra = [], $code = 400) {
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>$msg] + $extra);
  exit;
}

function norm_tipo($t){
  $t = trim((string)$t);
  if ($t === '') return '';
  // normaliza acentos/variantes típicas
  $map = [
    'Cotizacion' => 'Cotización',
    'CotizacionFirmada' => 'CotizaciónFirmada',
  ];
  return $map[$t] ?? $t;
}

/**
 * Config maestro del pipeline (probabilidad y %cierre por etapa)
 * Ajusta aquí sin tocar lógica.
 */
$STAGES = [
  'No Contact'     => ['prob'=>0,  'pct'=>0],
  'Contacting DM'  => ['prob'=>20, 'pct'=>20],
  'Testing'        => ['prob'=>60, 'pct'=>60],
  'Pilot Order'    => ['prob'=>90, 'pct'=>90],
  'Buying'         => ['prob'=>95, 'pct'=>95],
  'Won'            => ['prob'=>100,'pct'=>100],
  'Lost'           => ['prob'=>0,  'pct'=>0],
];

/**
 * Reglas de documentos requeridos para permitir avanzar a cierta etapa.
 * - Buying requiere PO (como en tu prueba)
 * - Puedes endurecer/relajar por negocio.
 */
$DOC_REQUIRED = [
  // 'Pilot Order' => ['Cotización'],   // si quieres exigir cotización antes de Pilot
  'Buying'      => ['PO'],
  // 'Won'       => ['Contrato','PO'],  // ejemplo
];

/**
 * Detecta si existe tabla/columna (para ambientes donde aún no esté completa)
 */
function col_exists($table, $col){
  try {
    $r = db_one("SHOW COLUMNS FROM {$table} LIKE ?", [$col]);
    return !!$r;
  } catch(Throwable $e){
    return false;
  }
}
$HAS_DOCS = true;
try {
  db_one("SHOW TABLES LIKE 'crm_documentos'");
} catch(Throwable $e){
  $HAS_DOCS = false;
}

function get_opp($id_opp){
  $row = db_one("
    SELECT id_opp, id_lead, etapa, porcentaje_cierre, probabilidad, tipo, estatus
    FROM t_crm_oportunidad
    WHERE id_opp = ?
  ", [$id_opp]);
  return $row ?: null;
}

function has_required_docs($opp_id, $lead_id, array $required){
  // Si no hay tabla de docs en este ambiente, no bloqueamos
  if (!$GLOBALS['HAS_DOCS']) return [true, []];

  // busca documentos por oportunidad y/o lead (best effort)
  $req = array_map('norm_tipo', $required);
  $req = array_values(array_unique($req));
  if (empty($req)) return [true, []];

  $place = implode(',', array_fill(0, count($req), '?'));

  // Nota: crm_documentos en tu BD trae: documento_id, lead_id, oportunidad_id, tipo, archivo, fecha, usuario_id
  // Si existiera estatus, sólo Activo.
  $has_estatus = col_exists('crm_documentos','estatus');

  $sql = "
    SELECT DISTINCT tipo
    FROM crm_documentos
    WHERE (oportunidad_id = ? OR (? > 0 AND lead_id = ?))
      AND tipo IN ($place)
  ";
  $params = [$opp_id, (int)$lead_id, (int)$lead_id];
  foreach($req as $t){ $params[] = $t; }

  if ($has_estatus){
    $sql .= " AND estatus = 'Activo' ";
  }

  $rows = db_all($sql, $params);
  $found = [];
  foreach($rows as $r){
    $found[] = norm_tipo($r['tipo'] ?? '');
  }
  $found = array_values(array_unique(array_filter($found)));

  $missing = [];
  foreach($req as $t){
    if (!in_array($t, $found, true)) $missing[] = $t;
  }

  return [count($missing) === 0, $missing];
}

// ===================== ACTIONS =====================

if ($action === 'get') {
  $id_opp = (int)($_GET['id_opp'] ?? $_POST['id_opp'] ?? 0);
  if ($id_opp <= 0) j_err('id_opp requerido');

  $opp = get_opp($id_opp);
  if (!$opp) j_err('Oportunidad no encontrada', [], 404);

  j_ok(['data'=>$opp]);
}

if ($action === 'stages') {
  j_ok(['stages'=>$GLOBALS['STAGES'], 'docs_required'=>$GLOBALS['DOC_REQUIRED']]);
}

/**
 * POST move_stage:
 * - id_opp (int)
 * - etapa_nueva (string)
 * - usuario (string)   (ej. SISTEMA)
 * - comentario (string opcional)
 */
if ($action === 'move_stage') {
  $id_opp      = (int)($_POST['id_opp'] ?? $_GET['id_opp'] ?? 0);
  $etapa_nueva = trim((string)($_POST['etapa_nueva'] ?? $_GET['etapa_nueva'] ?? ''));
  $usuario     = trim((string)($_POST['usuario'] ?? $_GET['usuario'] ?? 'SISTEMA'));
  $comentario  = trim((string)($_POST['comentario'] ?? $_GET['comentario'] ?? ''));

  if ($id_opp <= 0) j_err('id_opp requerido');
  if ($etapa_nueva === '') j_err('etapa_nueva requerida');
  if (!isset($STAGES[$etapa_nueva])) j_err('Etapa inválida', ['etapa'=>$etapa_nueva, 'validas'=>array_keys($STAGES)]);

  $opp = get_opp($id_opp);
  if (!$opp) j_err('Oportunidad no encontrada', [], 404);

  $etapa_ant = (string)($opp['etapa'] ?? '');
  $id_lead   = (int)($opp['id_lead'] ?? 0);

  // Reglas de documentos requeridos por etapa destino
  $req = $DOC_REQUIRED[$etapa_nueva] ?? [];
  if (!empty($req)) {
    [$ok, $missing] = has_required_docs($id_opp, $id_lead, $req);
    if (!$ok) {
      j_err(
        "No se puede avanzar a '{$etapa_nueva}' sin documentos requeridos",
        ['requeridos'=>$req, 'faltantes'=>$missing]
      );
    }
  }

  $prob = (int)($STAGES[$etapa_nueva]['prob'] ?? 0);
  $pct  = (float)($STAGES[$etapa_nueva]['pct'] ?? $prob);

  try {
    db_tx(function() use ($id_opp, $etapa_nueva, $prob, $pct, $usuario, $comentario, $etapa_ant) {

      // Update oportunidad
      dbq("
        UPDATE t_crm_oportunidad
        SET etapa = ?,
            probabilidad = ?,
            porcentaje_cierre = ?,
            fecha_modifica = NOW()
        WHERE id_opp = ?
      ", [$etapa_nueva, $prob, $pct, $id_opp]);

      // Insert movimiento
      dbq("
        INSERT INTO t_crm_movimientos_etapa (id_opp, etapa_anterior, etapa_nueva, usuario, comentario, fecha)
        VALUES (?,?,?,?,?, NOW())
      ", [$id_opp, ($etapa_ant !== '' ? $etapa_ant : null), $etapa_nueva, ($usuario !== '' ? $usuario : 'SISTEMA'), ($comentario !== '' ? $comentario : null)]);
    });

    $opp2 = get_opp($id_opp);
    j_ok(['data'=>$opp2]);

  } catch(Throwable $e) {
    j_err('Error al mover etapa', ['detail'=>$e->getMessage()]);
  }
}

if ($action === 'history') {
  $id_opp = (int)($_GET['id_opp'] ?? $_POST['id_opp'] ?? 0);
  if ($id_opp <= 0) j_err('id_opp requerido');

  try{
    $rows = db_all("
      SELECT id_mov, id_opp, etapa_anterior, etapa_nueva, usuario, fecha, comentario
      FROM t_crm_movimientos_etapa
      WHERE id_opp = ?
      ORDER BY id_mov ASC
    ", [$id_opp]);
    j_ok(['rows'=>$rows]);
  } catch(Throwable $e){
    j_err('Error leyendo historial', ['detail'=>$e->getMessage()]);
  }
}

j_err('Acción no válida');
