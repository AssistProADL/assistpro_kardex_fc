<?php
require_once __DIR__.'/../../app/db.php';
header('Content-Type: application/json');

$in = json_decode(file_get_contents('php://input'), true);
if(!$in){ echo json_encode(['ok'=>false,'msg'=>'Payload inválido']); exit; }

$db = db_pdo();
$db->beginTransaction();

try{

  $doc  = $in['documento'];
  $prov = $in['proveedor'];
  $usr  = $in['usuario'];
  $esc  = $in['escenario'];
  $det  = $in['detalle'];

  /* 1) Documento recepción */
  db_exec("
    INSERT INTO t_recepcion(documento,proveedor,usuario,fecha)
    VALUES(?,?,?,NOW())
  ",[$doc,$prov,$usr]);
  $id_rec = $db->lastInsertId();

  /* Ubicación virtual RECEPCIÓN */
  $idy_rec = db_val("SELECT idy_ubica FROM c_ubicacion WHERE CodigoCSD='RECEPCION' LIMIT 1");

  /* Helpers */
  function nuevoLP($pref){
    return $pref.'-'.date('YmdHis').'-'.rand(100,999);
  }

  /* 2) Escenarios */
  switch($esc){

    /* =======================
       A) SOLO PIEZAS
    ======================= */
    case 'PIEZAS':
      foreach($det['piezas'] as $p){
        db_exec("
          INSERT INTO ts_existenciapiezas(idy_ubica,cve_articulo,Existencia)
          VALUES(?,?,?)
          ON DUPLICATE KEY UPDATE Existencia=Existencia+VALUES(Existencia)
        ",[$idy_rec,$p['sku'],$p['cantidad']]);
      }
    break;

    /* =======================
       B) CONTENEDORES
    ======================= */
    case 'CONTENEDORES':
      foreach($det['contenedores'] as $c){
        $lp_ct = $c['lp'] ?? nuevoLP('CT');
        db_exec("
          INSERT INTO c_charolas(clave,license_plate,tipo,activo)
          VALUES(?,?, 'C',1)
        ",[$lp_ct,$lp_ct]);

        foreach($c['piezas'] as $p){
          db_exec("
            INSERT INTO ts_existenciapiezas(idy_ubica,cve_articulo,Existencia)
            VALUES(?,?,?)
          ",[$idy_rec,$p['sku'],$p['cantidad']]);
        }
      }
    break;

    /* =======================
       C) PALLET
    ======================= */
    case 'PALLET':
      $lp_pal = $det['lp_pallet'] ?? nuevoLP('LP');
      db_exec("
        INSERT INTO c_charolas(clave,license_plate,tipo,activo)
        VALUES(?,?, 'P',1)
      ",[$lp_pal,$lp_pal]);

      foreach($det['piezas'] as $p){
        db_exec("
          INSERT INTO ts_existenciapiezas(idy_ubica,cve_articulo,Existencia)
          VALUES(?,?,?)
        ",[$idy_rec,$p['sku'],$p['cantidad']]);
      }
    break;

    /* =======================
       D) CONTENEDORES EN PALLET
    ======================= */
    case 'PALLET_CONTENEDORES':
      $lp_pal = $det['lp_pallet'] ?? nuevoLP('LP');
      db_exec("
        INSERT INTO c_charolas(clave,license_plate,tipo,activo)
        VALUES(?,?, 'P',1)
      ",[$lp_pal,$lp_pal]);

      foreach($det['contenedores'] as $c){
        $lp_ct = $c['lp'] ?? nuevoLP('CT');
        db_exec("
          INSERT INTO c_charolas(clave,license_plate,tipo,activo)
          VALUES(?,?, 'C',1)
        ",[$lp_ct,$lp_ct]);

        foreach($c['piezas'] as $p){
          db_exec("
            INSERT INTO ts_existenciapiezas(idy_ubica,cve_articulo,Existencia)
            VALUES(?,?,?)
          ",[$idy_rec,$p['sku'],$p['cantidad']]);
        }
      }
    break;

    default:
      throw new Exception('Escenario no soportado');
  }

  $db->commit();
  echo json_encode(['ok'=>true,'msg'=>'Recepción registrada','documento'=>$doc]);

}catch(Throwable $e){
  $db->rollBack();
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
