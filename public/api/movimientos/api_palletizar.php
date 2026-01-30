<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/core/TaskHelper.php';

db_pdo();
global $pdo;

// =========================
// Helpers locales
// =========================
function jexit(int $code, array $payload) {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}
function read_json(): array {
  $raw = file_get_contents('php://input');
  $d = json_decode($raw, true);
  if (!is_array($d)) jexit(400, ['error'=>'JSON inválido']);
  return $d;
}
function must($a, string $k, string $m) {
  if (!isset($a[$k]) || $a[$k]==='') jexit(400, ['error'=>$m,'field'=>$k]);
  return $a[$k];
}
function now_dt(): string { return date('Y-m-d H:i:s'); }

// =========================
// Constantes legacy
// =========================
const MOV_SALIDA  = 200; // Palletizar - Salida Origen
const MOV_ENTRADA = 201; // Palletizar - Entrada Tarima

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jexit(405, ['error'=>'Método no permitido']);
  }

  $data = read_json();

  // =========================
  // Input obligatorio
  // =========================
  $empresa_id = (int) must($data,'empresa_id','Falta empresa_id');
  $usuario    = (string) must($data,'usuario','Falta usuario');
  $cve_almac  = (int) must($data,'cve_almac','Falta cve_almac');

  $idy_origen  = (int) must($data,'idy_ubica_origen','Falta idy_ubica_origen');
  $idy_destino = (int) must($data,'idy_ubica_destino','Falta idy_ubica_destino');

  $bl_origen   = (string) must($data,'bl_origen','Falta bl_origen');
  $bl_destino  = (string) must($data,'bl_destino','Falta bl_destino');

  $lp_tarima   = (string) must($data,'lp_tarima','Falta lp_tarima');
  $detalle     = (array) must($data,'detalle','Falta detalle');

  if (count($detalle) < 1) jexit(400, ['error'=>'detalle vacío']);

  // task_id opcional pero recomendado
  $task_id = isset($data['task_id']) ? (int)$data['task_id'] : null;

  // Validar task_id si viene
  TaskHelper::validarTask($pdo, $task_id);

  $referencia = $data['referencia'] ?? ('PAL-'.date('Ymd-His'));

  $contenedor_clave = $data['contenedor_clave'] ?? null;
  $contenedor_lp    = $data['contenedor_lp'] ?? null;

  // =========================
  // Transacción
  // =========================
  $pdo->beginTransaction();

  // 1️⃣ Activar LP virgen
  $st = $pdo->prepare("
    UPDATE c_charolas
    SET Utilizado='S'
    WHERE Clave_Contenedor=? AND Utilizado='N'
  ");
  $st->execute([$lp_tarima]);
  if ($st->rowCount() !== 1) {
    throw new Exception("LP Tarima inválido o ya utilizado");
  }

  // Prepared insert cardex
  $sqlCardex = "
    INSERT INTO t_cardex
    (cve_articulo,cve_lote,fecha,origen,destino,
     cantidad,ajuste,stockinicial,id_TipoMovimiento,
     cve_usuario,Cve_Almac,Cve_Almac_Origen,Cve_Almac_Destino,
     Referencia,task_id,contenedor_clave,contenedor_lp,pallet_clave,pallet_lp,Activo)
    VALUES
    (:art,:lote,:fecha,:origen,:destino,
     :cantidad,0,:stockini,:mov,
     :usuario,:almac,:alm_origen,:alm_destino,
     :ref,:task,:cont_clave,:cont_lp,:pal_clave,:pal_lp,1)
  ";
  $insCardex = $pdo->prepare($sqlCardex);

  // =========================
  // Procesar líneas
  // =========================
  foreach ($detalle as $i=>$lin) {

    $art = $lin['cve_articulo'] ?? null;
    $cant = isset($lin['cantidad']) ? (float)$lin['cantidad'] : 0;
    $nivel = strtoupper($lin['nivel_origen'] ?? '');
    $lote = $lin['cve_lote'] ?? null;

    if (!$art || $cant<=0 || !in_array($nivel,['PIEZA','CAJA'],true)) {
      throw new Exception("Detalle inválido en línea $i");
    }

    // ===== ORIGEN =====
    if ($nivel === 'PIEZA') {
      $st = $pdo->prepare("
        SELECT Existencia FROM ts_existenciapiezas
        WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=?
        FOR UPDATE
      ");
      $st->execute([$cve_almac,$idy_origen,$art]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      $saldoIni = $row ? (float)$row['Existencia'] : 0;

      if ($saldoIni < $cant) throw new Exception("Stock insuficiente PIEZA ($art)");

      $pdo->prepare("
        UPDATE ts_existenciapiezas
        SET Existencia=Existencia-?
        WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=?
      ")->execute([$cant,$cve_almac,$idy_origen,$art]);

    } else { // CAJA
      $st = $pdo->prepare("
        SELECT PiezasXCaja FROM ts_existenciacajas
        WHERE idy_ubica=? AND cve_articulo=?
        FOR UPDATE
      ");
      $st->execute([$idy_origen,$art]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      $saldoIni = $row ? (float)$row['PiezasXCaja'] : 0;

      if ($saldoIni < $cant) throw new Exception("Stock insuficiente CAJA ($art)");

      $pdo->prepare("
        UPDATE ts_existenciacajas
        SET PiezasXCaja=PiezasXCaja-?
        WHERE idy_ubica=? AND cve_articulo=?
      ")->execute([$cant,$idy_origen,$art]);
    }

    // Kardex SALIDA
    $insCardex->execute([
      ':art'=>$art, ':lote'=>$lote, ':fecha'=>now_dt(),
      ':origen'=>$bl_origen, ':destino'=>$bl_destino,
      ':cantidad'=>-1*$cant, ':stockini'=>$saldoIni,
      ':mov'=>MOV_SALIDA, ':usuario'=>$usuario,
      ':almac'=>$cve_almac, ':alm_origen'=>$cve_almac, ':alm_destino'=>null,
      ':ref'=>$referencia, ':task'=>$task_id,
      ':cont_clave'=>$contenedor_clave, ':cont_lp'=>$contenedor_lp,
      ':pal_clave'=>null, ':pal_lp'=>$lp_tarima
    ]);

    // ===== DESTINO TARIMA =====
    $st = $pdo->prepare("
      SELECT existencia FROM ts_existenciatarima
      WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=? AND nTarima=?
      FOR UPDATE
    ");
    $st->execute([$cve_almac,$idy_destino,$art,$lp_tarima]);
    $rowT = $st->fetch(PDO::FETCH_ASSOC);
    $saldoTarIni = $rowT ? (float)$rowT['existencia'] : 0;

    if ($rowT) {
      $pdo->prepare("
        UPDATE ts_existenciatarima
        SET existencia=existencia+?
        WHERE cve_almac=? AND idy_ubica=? AND cve_articulo=? AND nTarima=?
      ")->execute([$cant,$cve_almac,$idy_destino,$art,$lp_tarima]);
    } else {
      $pdo->prepare("
        INSERT INTO ts_existenciatarima
        (cve_almac,idy_ubica,cve_articulo,lote,Fol_Folio,nTarima,capacidad,existencia,Activo)
        VALUES (?,?,?,?,0,?,0,?,1)
      ")->execute([$cve_almac,$idy_destino,$art,$lote,$lp_tarima,$cant]);
    }

    // Kardex ENTRADA
    $insCardex->execute([
      ':art'=>$art, ':lote'=>$lote, ':fecha'=>now_dt(),
      ':origen'=>$bl_origen, ':destino'=>$bl_destino,
      ':cantidad'=>$cant, ':stockini'=>$saldoTarIni,
      ':mov'=>MOV_ENTRADA, ':usuario'=>$usuario,
      ':almac'=>$cve_almac, ':alm_origen'=>null, ':alm_destino'=>$cve_almac,
      ':ref'=>$referencia, ':task'=>$task_id,
      ':cont_clave'=>$contenedor_clave, ':cont_lp'=>$contenedor_lp,
      ':pal_clave'=>$lp_tarima, ':pal_lp'=>$lp_tarima
    ]);
  }

  $pdo->commit();

  jexit(200,[
    'status'=>'OK',
    'referencia'=>$referencia,
    'task_id'=>$task_id,
    'lp_tarima'=>$lp_tarima,
    'lineas'=>count($detalle),
    'timestamp'=>now_dt()
  ]);

} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  jexit(500,['error'=>$e->getMessage()]);
}
