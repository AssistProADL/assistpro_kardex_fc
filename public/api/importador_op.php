<?php
// public/api/importador_op.php
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function jerr($msg, $extra = []) {
  echo json_encode(array_merge(['ok'=>0,'error'=>$msg], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($arr) {
  echo json_encode(array_merge(['ok'=>1], $arr), JSON_UNESCAPED_UNICODE);
  exit;
}

$layoutHeaders = [
  'Folio_OP','Usuario','OT_Cliente','Fecha_OT','Fecha_Compromiso',
  'Almacen','Area_Produccion','MP_BL_ORIGEN','Articulo_Compuesto','Lote','Caducidad',
  'Cantidad_a_Producir','PT_BL_DESTINO','LP_CONTENEDOR','LP_PALLET','Referencia'
];

if ($action === 'layout') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="layout_importacion_op.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, $layoutHeaders);
  fclose($out);
  exit;
}

if ($action === 'runs_list') {
  // Stub (fase 1)
  jok(['data'=>[]]);
}

if ($action !== 'previsualizar') {
  jerr('Acción no soportada', ['debug'=>['action'=>$action]]);
}

$empresa = $_POST['empresa_id'] ?? '';
$usr     = $_POST['usuario_importa'] ?? 'SISTEMA';
if (!$empresa) jerr('Empresa requerida.');

if (!isset($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
  jerr('Archivo CSV requerido.');
}

$tmp = $_FILES['archivo']['tmp_name'];
$fh = fopen($tmp,'r');
if (!$fh) jerr('No se pudo leer el archivo.');

$header = fgetcsv($fh);
if (!$header || count($header) < 3) jerr('CSV sin encabezados válidos.');

$header = array_map(function($h){
  $h = trim((string)$h);
  $h = str_replace(["\xEF\xBB\xBF"], [''], $h); // BOM
  return $h;
}, $header);

$idx = [];
foreach ($header as $i=>$h) $idx[$h]=$i;

$missing = [];
foreach ($layoutHeaders as $h) {
  if (!array_key_exists($h,$idx)) $missing[] = $h;
}
if ($missing) {
  jerr('Layout incorrecto. Faltan columnas: '.implode(', ',$missing));
}

$filas = [];
$total=0; $ok=0; $err=0;

$lineNo = 1;
while (($row = fgetcsv($fh)) !== false) {
  $lineNo++;
  // Saltar líneas vacías
  $allEmpty = true;
  foreach ($row as $v) { if (trim((string)$v)!=='') { $allEmpty=false; break; } }
  if ($allEmpty) continue;

  $total++;

  $r = [];
  foreach ($layoutHeaders as $h) {
    $v = $row[$idx[$h]] ?? '';
    $v = is_string($v) ? trim($v) : $v;
    $r[$h] = ($v === '' ? '' : $v);
  }

  $estado = 'OK';
  $msg = 'OK';

  // Validaciones mínimas (fase 1: robustez, no negocio completo todavía)
  if ($r['Folio_OP']==='') { $estado='ERROR'; $msg='Folio_OP requerido'; }
  if ($estado==='OK' && $r['Articulo_Compuesto']==='') { $estado='ERROR'; $msg='Articulo_Compuesto requerido'; }
  if ($estado==='OK' && $r['Almacen']==='') { $estado='ERROR'; $msg='Almacen requerido (multi-almacén por línea)'; }
  if ($estado==='OK') {
    $qty = str_replace(',','.', (string)$r['Cantidad_a_Producir']);
    if ($qty==='' || !is_numeric($qty)) { $estado='ERROR'; $msg='Cantidad_a_Producir inválida'; }
    else {
      $qty = (float)$qty;
      if ($qty <= 0) { $estado='ERROR'; $msg='Cantidad_a_Producir debe ser > 0'; }
      else {
        // 4 decimales máx (regla de negocio UI)
        $r['Cantidad_a_Producir'] = number_format($qty, 4, '.', '');
        // pero si quieres sin ceros extra, lo ajustamos en fase 2
      }
    }
  }

  // Fechas (no obligatorias, pero si vienen deben ser parseables dd/mm/yyyy o yyyy-mm-dd)
  $dateFields = ['Fecha_OT','Fecha_Compromiso','Caducidad'];
  foreach ($dateFields as $df) {
    if ($estado!=='OK') break;
    if ($r[$df] !== '') {
      $v = $r[$df];
      $okDate = false;

      // dd/mm/yyyy
      if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) $okDate = true;
      // yyyy-mm-dd
      if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $v)) $okDate = true;

      if (!$okDate) { $estado='ERROR'; $msg="Formato inválido en $df (use dd/mm/aaaa o aaaa-mm-dd)"; }
    }
  }

  // LPs opcionales pero si vienen, que no vayan “basura”
  if ($estado==='OK') {
    if ($r['LP_CONTENEDOR']!=='' && strlen($r['LP_CONTENEDOR'])<4) { $estado='ERROR'; $msg='LP_CONTENEDOR inválido'; }
    if ($r['LP_PALLET']!=='' && strlen($r['LP_PALLET'])<4) { $estado='ERROR'; $msg='LP_PALLET inválido'; }
  }

  if ($estado==='OK') $ok++; else $err++;

  $r['estado']=$estado;
  $r['mensaje']=$msg;
  $filas[]=$r;

  // protección anti cargas gigantes en preview
  if ($total >= 2000) {
    // cortamos para no saturar navegador; en fase 2 lo hacemos server-side con paginado
    break;
  }
}
fclose($fh);

$mensajeGlobal = ($err>0)
  ? "Previsualización con incidencias: revise filas en ERROR."
  : "Previsualización OK: lista para consolidación (fase 2: afectación).";

jok([
  'mensaje_global' => $mensajeGlobal,
  'total' => $total,
  'total_ok' => $ok,
  'total_err' => $err,
  'filas' => $filas,
  'debug' => [
    'empresa_id' => $empresa,
    'usuario' => $usr,
    'nota' => 'Fase 1: solo preview/validación. Sin afectación a t_ordenprod/td_ordenprod/existencias/kardex.'
  ]
]);
