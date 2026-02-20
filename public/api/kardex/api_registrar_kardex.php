<?php
header('Content-Type: application/json; charset=utf-8');

function j_ok($data = [])
{
  echo json_encode(["ok" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
  exit;
}
function j_err($msg, $code = 400, $extra = [])
{
  http_response_code($code);
  echo json_encode(["ok" => false, "error" => $msg, "extra" => $extra], JSON_UNESCAPED_UNICODE);
  exit;
}

function load_pdo()
{
  $candidates = [__DIR__ . '/../../app/db.php', __DIR__ . '/../../../app/db.php', __DIR__ . '/../../../../app/db.php'];
  foreach ($candidates as $p) {
    if (file_exists($p)) {
      require_once $p;
      if (isset($pdo) && $pdo instanceof PDO)
        return $pdo;
      if (function_exists('db')) {
        $x = db();
        if ($x instanceof PDO)
          return $x;
      }
      if (function_exists('getPDO')) {
        $x = getPDO();
        if ($x instanceof PDO)
          return $x;
      }
    }
  }
  j_err("No se pudo cargar conexión DB (pdo) desde app/db.php", 500, ["searched" => $candidates]);
}

function cols(PDO $pdo, $table)
{
  $st = $pdo->prepare("SHOW COLUMNS FROM `$table`");
  $st->execute();
  return array_map(fn($r) => $r['Field'], $st->fetchAll(PDO::FETCH_ASSOC));
}
function pick_col($columns, $cands)
{
  $lc = array_map('strtolower', $columns);
  foreach ($cands as $c) {
    $idx = array_search(strtolower($c), $lc);
    if ($idx !== false)
      return $columns[$idx];
  }
  return null;
}

$pdo = load_pdo();
$in = json_decode(file_get_contents("php://input"), true) ?: [];

$tipo_mov = trim($in['tipo_movimiento'] ?? ($in['id_TipoMovimiento'] ?? 'PICK_TO_LP'));
$articulo = trim($in['articulo'] ?? ($in['cve_articulo'] ?? ($in['Cve_Articulo'] ?? ($in['Articulo'] ?? ''))));
$cantidad = (float) ($in['cantidad'] ?? ($in['Cantidad'] ?? ($in['qty'] ?? ($in['Qty'] ?? 0))));
$bl_o = trim($in['bl_origen'] ?? ($in['origen'] ?? ($in['Origen'] ?? '')));
$bl_d = trim($in['bl_destino'] ?? ($in['destino'] ?? ($in['Destino'] ?? '')));
$lp_o = trim($in['lp_origen'] ?? '');
$lp_d = trim($in['lp_destino'] ?? '');
$usuario = trim($in['usuario'] ?? ($in['cve_usuario'] ?? ($in['Cve_Usuario'] ?? '')));
$almacen = trim($in['almacen'] ?? ($in['Cve_Almac'] ?? ($in['cve_almac'] ?? '')));
$ref = trim($in['referencia'] ?? ($in['Referencia'] ?? ''));

if ($articulo === '' || $cantidad <= 0)
  j_err("Kardex: articulo y cantidad son obligatorios");

$table = 't_cardex';
$C = cols($pdo, $table);

$colArt = pick_col($C, ['cve_articulo', 'Cve_Articulo', 'articulo', 'Articulo', 'clave', 'Clave', 'sku', 'SKU']);
$colFec = pick_col($C, ['fecha', 'Fecha', 'created_at', 'CreatedAt']);
$colOri = pick_col($C, ['origen', 'Origen']);
$colDes = pick_col($C, ['destino', 'Destino']);
$colCan = pick_col($C, ['cantidad', 'Cantidad', 'qty', 'Qty']);
$colTip = pick_col($C, ['id_tipomovimiento', 'id_TipoMovimiento', 'tipo_movimiento', 'TipoMovimiento']);
$colUsr = pick_col($C, ['cve_usuario', 'Cve_Usuario', 'usuario', 'Usuario']);
$colAlm = pick_col($C, ['cve_almac', 'Cve_Almac', 'almacen', 'Almacen']);
$colAju = pick_col($C, ['ajuste', 'Ajuste']);
$colLot = pick_col($C, ['cve_lote', 'Cve_Lote', 'lote', 'Lote']);

if (!$colArt || !$colCan)
  j_err("No pude mapear columnas requeridas en t_cardex", 500, ["cols" => $C]);

// Origen/destino extendidos con LPs si existen:
$origenTxt = trim($bl_o . ($lp_o ? " | $lp_o" : ""));
$destinoTxt = trim($bl_d . ($lp_d ? " | $lp_d" : ""));

$insCols = [$colArt, $colCan];
$insVals = [$articulo, $cantidad];

if ($colFec) {
  $insCols[] = $colFec;
  $insVals[] = date('Y-m-d H:i:s');
}
if ($colOri) {
  $insCols[] = $colOri;
  $insVals[] = $origenTxt;
}
if ($colDes) {
  $insCols[] = $colDes;
  $insVals[] = $destinoTxt;
}
if ($colUsr && $usuario !== '') {
  $insCols[] = $colUsr;
  $insVals[] = $usuario;
}
if ($colAlm && $almacen !== '') {
  $insCols[] = $colAlm;
  $insVals[] = $almacen;
}
if ($colAju) {
  $insCols[] = $colAju;
  $insVals[] = 0;
}
if ($colLot) {
  $insCols[] = $colLot;
  $insVals[] = '';
}

// Tipo movimiento correcto: Lookup en t_tipomovimiento
if ($colTip) {
  $idTipo = 0; // Default seguro

  if (ctype_digit((string) $tipo_mov)) {
    $idTipo = (int) $tipo_mov;
  } else {
    // Buscar ID por nombre
    $sqlT = "SELECT id_TipoMovimiento FROM t_tipomovimiento WHERE nombre = ? LIMIT 1";
    $stmtT = $pdo->prepare($sqlT);
    $stmtT->execute([$tipo_mov]);
    $foundId = $stmtT->fetchColumn();
    if ($foundId) {
      $idTipo = (int) $foundId;
    } else {
      // Fallback razonable o error? Usaremos 0 para no romper, pero lo ideal es tener el catálogo.
      // Intentar 'PICK_TO_LP' default si no vino
      if ($tipo_mov === 'PICK_TO_LP') {
        // Si no existe, podemos intentar usar 51 o similar si sabemos que es estándar, pero mejor 0
        $idTipo = 9001; // Mantenemos el default legacy solo si no hay match, o 0
      } elseif ($tipo_mov === 'TRASLADO') {
        $idTipo = 108; // Traslado Interno
      }
    }
  }

  $insCols[] = $colTip;
  $insVals[] = $idTipo;
}

$place = implode(",", array_fill(0, count($insCols), "?"));
$sql = "INSERT INTO `$table` (`" . implode("`,`", $insCols) . "`) VALUES ($place)";
try {
  $st = $pdo->prepare($sql);
  $st->execute($insVals);
  j_ok(["id" => $pdo->lastInsertId(), "tipo" => $tipo_mov]);
} catch (Exception $e) {
  j_err("Error al registrar kardex: " . $e->getMessage(), 409);
}
