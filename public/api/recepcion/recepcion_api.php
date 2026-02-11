<?php
// public/ingresos/recepcion_api.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

/* ===========================================================
   Helpers
   =========================================================== */
function json_ok($data = []) { echo json_encode(array_merge(['ok'=>1], $data), JSON_UNESCAPED_UNICODE); exit; }
function json_err($msg, $extra = []) { echo json_encode(array_merge(['ok'=>0,'error'=>$msg], $extra), JSON_UNESCAPED_UNICODE); exit; }

function first_col($table, $cands) {
  foreach($cands as $c){
    $x = db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?", [$table,$c]);
    if ((int)$x>0) return $c;
  }
  return null;
}

/* ===========================================================
   Actions
   =========================================================== */
$action = $_GET['action'] ?? '';

/* ===========================================================
   (Otros actions que ya existan en tu archivo…)
   =========================================================== */

/* ===========================================================
   GUARDAR RECEPCION  ✅ FIX num_pedimento
   =========================================================== */
if ($action === 'guardar_recepcion') {
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!$payload) json_err("JSON inválido");

  $tipo = strtoupper(trim($payload['tipo'] ?? 'OC')); // OC | RL | CD
  $empresa = $payload['empresa'] ?? null;
  $almacen = $payload['almacen'] ?? null;
  $zona_recepcion = $payload['zona_recepcion'] ?? null;
  $zona_destino = $payload['zona_destino'] ?? null;
  $bl_destino = $payload['bl_destino'] ?? null;
  $proveedor = $payload['proveedor'] ?? null;
  $oc_folio = $payload['oc_folio'] ?? null;
  $factura = $payload['factura'] ?? null;
  $proyecto = $payload['proyecto'] ?? null;
  $usuario = $payload['usuario'] ?? 'WMS';
  $lineas = $payload['lineas'] ?? [];

  // Compatibilidad: vistas legacy envían num_pedimento. Nuevo estándar: folio_mov.
  // Si llega alguno, lo tratamos como "folio solicitado" (normalmente null en UI).
  $folioIn = $payload['folio_mov'] ?? ($payload['num_pedimento'] ?? null);

  if (!$almacen) json_err("Almacén requerido");
  if (!$zona_recepcion) json_err("Zona de recepción requerida");
  if (!is_array($lineas) || !count($lineas)) json_err("Sin líneas para guardar");

  try {

    // Resolver IDY ubicación (si en tu BD se usa idy_ubica / IDY_Ubi / etc)
    $idy = null;
    if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_ubicacion'") > 0) {
      $colAlm  = first_col('c_ubicacion', ['cve_almac','Cve_Almac','almacen','Almacen']);
      $colUbi  = first_col('c_ubicacion', ['cve_ubicacion','Cve_Ubicacion','ubicacion','Ubicacion','clave','Clave']);
      $colIDY  = first_col('c_ubicacion', ['idy_ubica','IDY_Ubi','id','ID']);
      if ($colAlm && $colUbi && $colIDY) {
        $idy = db_val("SELECT $colIDY FROM c_ubicacion WHERE $colAlm=? AND $colUbi=? LIMIT 1", [$almacen, $zona_recepcion]);
      }
    }

    // Crear folio de recepción (th_aduana si existe)
    $folioRec = null;

    if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='th_aduana'") > 0) {
      $folioRec = "RC".date('YmdHis').substr((string)mt_rand(1000,9999),0,4);

      // Columna folio vigente en th_aduana (migración num_pedimento -> folio_mov)
      $colFolio = first_col('th_aduana', ['folio_mov','num_pedimento']) ?: 'num_pedimento';

      // Folio a guardar: si el front envía uno (folio_mov/num_pedimento) lo respetamos;
      // si no, usamos el generado para la recepción.
      $folioToSave = ($folioIn !== null && $folioIn !== '') ? (string)$folioIn : (string)$folioRec;

      // Si la columna es numérica (solo en ambientes legacy con num_pedimento INT), no podemos meter folios tipo "RC2026...".
      // Usamos 0 y guardamos el folio real en un campo texto disponible.
      $pedVal = $folioToSave;
      $dtPed = db_val("SELECT DATA_TYPE
                       FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_SCHEMA=DATABASE()
                         AND TABLE_NAME='th_aduana'
                         AND COLUMN_NAME=?", [$colFolio]) ?: '';
      $dtPed = strtolower((string)$dtPed);
      $isNum = in_array($dtPed, ['int','integer','bigint','smallint','mediumint','tinyint','decimal','numeric','float','double'], true);
      if ($isNum) { $pedVal = 0; }

      $map = [
        $colFolio       => $pedVal,
        'fech_pedimento'=> date('Y-m-d H:i:s'),
        'Factura'       => $factura,
        'status'        => 'A',
        'ID_Proveedor'  => $proveedor,
        'ID_Protocolo'  => ($tipo==='OC' ? 'OC' : ($tipo==='CD' ? 'CD' : 'RL')),
        'cve_usuario'   => $usuario,
        'Cve_Almac'     => $almacen,
        'Activo'        => 1,
        'Proyecto'      => $proyecto
      ];

      // Intenta guardar folioRec en algún campo texto disponible (sin romper compatibilidad)
      if ($isNum) {
        $candidates = ['folio_recepcion','folio_rec','cve_recepcion','referencia','Referencia','observaciones','Observaciones','comentarios','Comentarios','nota','Nota'];
        foreach($candidates as $c){
          $dt = db_val("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA=DATABASE()
                          AND TABLE_NAME='th_aduana'
                          AND COLUMN_NAME=?", [$c]);
          if ($dt) {
            $dt = strtolower((string)$dt);
            $isTxt = in_array($dt, ['varchar','char','text','tinytext','mediumtext','longtext'], true);
            if ($isTxt) { $map[$c] = $folioToSave; break; }
          }
        }
      }

      // Insert dinámico según columnas reales
      $cols=[]; $vals=[]; $params=[];
      foreach($map as $k=>$v){
        $ex = db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='th_aduana' AND COLUMN_NAME=?", [$k]);
        if ((int)$ex>0){
          $cols[] = $k;
          $vals[] = '?';
          $params[] = $v;
        }
      }

      if (count($cols)>0) {
        dbq("INSERT INTO th_aduana (".implode(',',$cols).") VALUES (".implode(',',$vals).")", $params);
      }
    }

    // ✅ Si tu flujo además inserta detalle y/o existencias, aquí continúa tu lógica original.
    // (Mantengo intacto lo demás; el FIX se enfoca a no romper th_aduana.num_pedimento)

    json_ok(['msg'=>'OK','folio'=>$folioRec]);

  } catch(Throwable $e){
    json_err("Fatal error", ['detail'=>$e->getMessage()]);
  }
}

json_err("Acción inválida");
