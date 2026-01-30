<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

function out(array $a){ echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

try {
  $importador = strtoupper(trim($_POST['importador'] ?? 'TRALM'));
  $run_id = (int)($_POST['run_id'] ?? 0);

  // Si vienen con archivo, generamos una corrida nueva (o reusamos si run_id > 0)
  $hasFile = isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name']);

  if (!$hasFile && $run_id <= 0) {
    out(['ok'=>false,'error'=>'Debes enviar archivo o run_id para revalidar.']);
  }

  // Tablas de gobierno
  // ap_import_runs (cabecera) / ap_import_run_rows (detalle) ya existen en tu BD (se ven en phpMyAdmin).
  // Creamos corrida si no viene run_id.
  if ($run_id <= 0) {
    $archivo = $hasFile ? ($_FILES['file']['name'] ?? 'archivo.csv') : 'revalidacion';
    $stmt = $pdo->prepare("INSERT INTO ap_import_runs (importador_clave, archivo, status, created_at) VALUES (?, ?, 'BORRADOR', NOW())");
    $stmt->execute([$importador, $archivo]);
    $run_id = (int)$pdo->lastInsertId();
  } else {
    // si revalida, limpiamos rows para recalcular
    $pdo->prepare("DELETE FROM ap_import_run_rows WHERE run_id = ?")->execute([$run_id]);
  }

  $archivoNombre = '';
  $rows = [];
  if ($hasFile) {
    $archivoNombre = $_FILES['file']['name'] ?? '';
    $csv = file_get_contents($_FILES['file']['tmp_name']);
    if ($csv === false || trim($csv)==='') {
      out(['ok'=>false,'error'=>'Archivo vacío.']);
    }

    $lines = preg_split("/\r\n|\n|\r/", $csv);
    $lines = array_values(array_filter($lines, fn($l)=>trim($l)!==''));
    if (!count($lines)) out(['ok'=>false,'error'=>'Archivo sin renglones.']);

    $first = $lines[0];
    $comma = substr_count($first, ',');
    $semi  = substr_count($first, ';');
    $delim = ($semi > $comma) ? ';' : ',';

    $hdr = array_map('trim', str_getcsv($lines[0], $delim));
    $hasHeader = in_array('BL_ORIGEN',$hdr,true) || in_array('LP_O_PRODUCTO',$hdr,true) || in_array('ZRD_BL',$hdr,true);

    $start = $hasHeader ? 1 : 0;

    for ($i=$start; $i<count($lines); $i++) {
      $cols = str_getcsv($lines[$i], $delim);
      $cols = array_map(fn($x)=>trim((string)$x), $cols);
      if (count($cols) < 5) continue;

      $rows[] = [
        'linea_num'     => ($i+1),
        'bl_origen'     => strtoupper($cols[0] ?? ''),
        'lp_o_producto' => strtoupper($cols[1] ?? ''),
        'lote_serie'    => strtoupper($cols[2] ?? ''),
        'cantidad'      => (string)($cols[3] ?? ''),
        'zrd_bl'        => strtoupper($cols[4] ?? ''),
      ];
    }
  } else {
    // Revalidación: tomamos lo ya cargado anteriormente (si existiera)
    $q = $pdo->prepare("SELECT linea_num, data_json FROM ap_import_run_rows WHERE run_id=? ORDER BY linea_num ASC");
    $q->execute([$run_id]);
    while($r=$q->fetch(PDO::FETCH_ASSOC)){
      $d = json_decode($r['data_json'] ?? '{}', true) ?: [];
      $rows[] = [
        'linea_num'     => (int)$r['linea_num'],
        'bl_origen'     => strtoupper($d['BL_ORIGEN'] ?? ''),
        'lp_o_producto' => strtoupper($d['LP_O_PRODUCTO'] ?? ''),
        'lote_serie'    => strtoupper($d['LOTE_SERIE'] ?? ''),
        'cantidad'      => (string)($d['CANTIDAD'] ?? ''),
        'zrd_bl'        => strtoupper($d['ZRD_BL'] ?? ''),
      ];
    }
    if (!count($rows)) out(['ok'=>false,'error'=>'No hay líneas para revalidar en esta corrida.']);
  }

  // Validación: layout único requerido
  $errores = 0;
  $errores_muestra = [];

  $ins = $pdo->prepare("
    INSERT INTO ap_import_run_rows (run_id, linea_num, status, mensaje, data_json, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
  ");

  // Preparar queries (sin filtros de almacén, búsqueda global)
  $qBL = $pdo->prepare("SELECT id FROM c_ubicacion WHERE CodigoCSD = ? LIMIT 1");
  $qZRD1 = $pdo->prepare("SELECT id FROM tubicacionesretencion WHERE cve_ubicacion = ? LIMIT 1");
  $qZRD2 = $pdo->prepare("SELECT id FROM c_ubicacion WHERE CodigoCSD = ? LIMIT 1");

  foreach ($rows as $idx => $r) {
    $msg = [];
    $ok = true;

    if ($r['bl_origen'] === '') { $ok=false; $msg[]='BL_ORIGEN vacío'; }
    if ($r['lp_o_producto'] === '') { $ok=false; $msg[]='LP_O_PRODUCTO vacío'; }
    if ($r['cantidad'] === '') { $ok=false; $msg[]='CANTIDAD vacía'; }
    if ($r['zrd_bl'] === '') { $ok=false; $msg[]='ZRD_BL vacío'; }

    // BL_ORIGEN contra c_ubicacion.CodigoCSD
    if ($ok) {
      $qBL->execute([$r['bl_origen']]);
      if (!$qBL->fetchColumn()) { $ok=false; $msg[]='BL_ORIGEN no existe en c_ubicacion (CodigoCSD)'; }
    }

    // ZRD_BL contra tubicacionesretencion.cve_ubicacion OR c_ubicacion.CodigoCSD
    if ($ok) {
      $qZRD1->execute([$r['zrd_bl']]);
      $id1 = $qZRD1->fetchColumn();
      if (!$id1) {
        $qZRD2->execute([$r['zrd_bl']]);
        $id2 = $qZRD2->fetchColumn();
        if (!$id2) { $ok=false; $msg[]='ZRD_BL no existe en tubicacionesretencion (cve_ubicacion) ni c_ubicacion (CodigoCSD)'; }
      }
    }

    $status = $ok ? 'OK' : 'ERR';
    $mensaje = implode(' | ', $msg);

    if (!$ok) {
      $errores++;
      if (count($errores_muestra) < 10) {
        $errores_muestra[] = "Línea {$r['linea_num']}: {$mensaje}";
      }
    }

    $data_json = json_encode([
      'BL_ORIGEN' => $r['bl_origen'],
      'LP_O_PRODUCTO' => $r['lp_o_producto'],
      'LOTE_SERIE' => $r['lote_serie'],
      'CANTIDAD' => $r['cantidad'],
      'ZRD_BL' => $r['zrd_bl'],
    ], JSON_UNESCAPED_UNICODE);

    $ins->execute([$run_id, (int)$r['linea_num'], $status, $mensaje, $data_json]);
  }

  $total = count($rows);
  $status_run = ($errores > 0) ? 'VALIDADO_CON_ERROR' : 'VALIDADO';

  // Folio simple (si ya traes otro folio generator, aquí lo conectas)
  $folio = "TRALM-" . date('Ymd') . "-" . str_pad((string)$run_id, 6, '0', STR_PAD_LEFT);

  $pdo->prepare("UPDATE ap_import_runs SET status=?, folio=?, archivo=COALESCE(NULLIF(archivo,''),?), updated_at=NOW() WHERE id=?")
      ->execute([$status_run, $folio, $archivoNombre, $run_id]);

  out([
    'ok'=>true,
    'run_id'=>$run_id,
    'folio'=>$folio,
    'archivo'=>$archivoNombre,
    'status'=>$status_run,
    'total_lineas'=>$total,
    'total_errores'=>$errores,
    'resumen'=>"Traslado entre almacenes · {$total} líneas · ".($total-$errores)." OK / {$errores} ERR",
    'errores_muestra'=>$errores_muestra,
  ]);

} catch (Throwable $e) {
  out(['ok'=>false,'error'=>$e->getMessage()]);
}
