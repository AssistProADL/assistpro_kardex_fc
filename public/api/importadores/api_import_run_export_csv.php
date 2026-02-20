<?php
// public/api/importadores/api_import_run_export_csv.php
header('Content-Type: text/csv; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

function norm_key($s){
  $s = (string)$s;
  $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
  $s = str_replace("\xC2\xA0", " ", $s);
  $s = str_replace(['"', "'"], '', $s);
  $s = trim($s);
  $s = preg_replace('/\s+/', ' ', $s);
  return strtoupper($s);
}

$run_id = isset($_GET['run_id']) ? intval($_GET['run_id']) : 0;
$estado = isset($_GET['estado']) ? norm_key($_GET['estado']) : 'ALL'; // OK / ERR / ALL

if ($run_id <= 0) {
  http_response_code(400);
  echo "run_id requerido";
  exit;
}

try {
  // Info corrida (para nombre de archivo)
  $st = $pdo->prepare("SELECT folio_importacion, tipo_ingreso FROM ap_import_runs WHERE id=? LIMIT 1");
  $st->execute([$run_id]);
  $run = $st->fetch(PDO::FETCH_ASSOC);
  if (!$run) {
    http_response_code(404);
    echo "run_id no encontrado";
    exit;
  }

  $folio = $run['folio_importacion'] ?: ("RUN-" . $run_id);

  // Query rows
  $where = " run_id = ? ";
  $params = [$run_id];

  if ($estado === 'OK' || $estado === 'ERR') {
    $where .= " AND estado = ? ";
    $params[] = $estado;
  }

  $st = $pdo->prepare("
    SELECT linea_num, estado, mensaje, data_json
    FROM ap_import_run_rows
    WHERE $where
    ORDER BY linea_num ASC
  ");
  $st->execute($params);

  // Nombre archivo
  $filename = "VALIDACION_" . $folio . ".csv";
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Pragma: no-cache');
  header('Expires: 0');

  $out = fopen('php://output', 'w');

  // ✅ Header corporativo de salida (ZRD_BL)
  fputcsv($out, [
    'BL_ORIGEN',
    'LP_O_PRODUCTO',
    'LOTE_SERIE',
    'CANTIDAD',
    'ZRD_BL',      // <- estándar corporativo
    'ESTADO',
    'MENSAJE',
    'LINEA_NUM'
  ]);

  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $data = [];
    if (!empty($r['data_json'])) {
      $tmp = json_decode($r['data_json'], true);
      if (is_array($tmp)) $data = $tmp;
    }

    // Entrada original trae ZONA_RECIBO_DESTINO, lo mapeamos a ZRD_BL
    $bl_origen = $data['BL_ORIGEN'] ?? '';
    $lp        = $data['LP_O_PRODUCTO'] ?? '';
    $lote      = $data['LOTE_SERIE'] ?? '';
    $cant      = $data['CANTIDAD'] ?? '';

    // ✅ Aquí la clave: tomar el campo legacy y exponerlo como ZRD_BL
    $zrd_bl    = '';
    if (isset($data['ZRD_BL'])) {
      // por si en algún run ya se guardó así
      $zrd_bl = $data['ZRD_BL'];
    } elseif (isset($data['ZONA_RECIBO_DESTINO'])) {
      $zrd_bl = $data['ZONA_RECIBO_DESTINO'];
    }

    fputcsv($out, [
      $bl_origen,
      $lp,
      $lote,
      $cant,
      $zrd_bl,
      $r['estado'] ?? '',
      $r['mensaje'] ?? '',
      $r['linea_num'] ?? ''
    ]);
  }

  fclose($out);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo "Error interno: " . $e->getMessage();
  exit;
}
