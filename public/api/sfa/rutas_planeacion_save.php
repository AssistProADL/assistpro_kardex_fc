<?php
// public/api/sfa/rutas_planeacion_save.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$raw = file_get_contents('php://input');
if(!$raw) jexit(['ok'=>0,'error'=>'Body JSON requerido']);
$in = json_decode($raw, true);
if(!$in) jexit(['ok'=>0,'error'=>'JSON inválido']);

$almacen_id = $in['almacen_id'] ?? null;
$ruta_id    = $in['ruta_id'] ?? null;
$dias       = $in['dias'] ?? [];
$clientes   = $in['clientes'] ?? [];

if(!$almacen_id || !$ruta_id) jexit(['ok'=>0,'error'=>'almacen_id y ruta_id requeridos']);
if(!is_array($dias)) $dias = [];
if(!is_array($clientes) || count($clientes)==0) jexit(['ok'=>0,'error'=>'clientes requerido']);

try{
  db_tx(function() use ($ruta_id, $dias, $clientes){

    // 1) Insertar relación cliente-ruta (si no existe)
    //    (Cliente puede estar en varias rutas -> NO eliminamos otras rutas)
    $insRel = "
      INSERT INTO RelClirutas (ID_Ruta, IdCliente)
      SELECT :ruta_id, :id_cliente
      WHERE NOT EXISTS (
        SELECT 1 FROM RelClirutas
         WHERE ID_Ruta = :ruta_id
           AND IdCliente = :id_cliente
      )
    ";

    // 2) Días de visita: estrategia limpia por (ruta,cliente)
    //    - borrar días previos para esa ruta/cliente
    //    - insertar días seleccionados
    $delDias = "DELETE FROM RelDayCli WHERE Cve_Ruta = :ruta_id AND IdCliente = :id_cliente";
    $insDia  = "INSERT INTO RelDayCli (Cve_Ruta, IdCliente, Dia) VALUES (:ruta_id, :id_cliente, :dia)";

    foreach($clientes as $c){
      $id_cliente = $c['id_cliente'] ?? null;
      if(!$id_cliente) continue;

      dbq($insRel, ['ruta_id'=>$ruta_id, 'id_cliente'=>$id_cliente]);

      // Días
      dbq($delDias, ['ruta_id'=>$ruta_id, 'id_cliente'=>$id_cliente]);

      foreach($dias as $dia){
        $dia = strtoupper(trim($dia));
        if($dia==='') continue;
        dbq($insDia, ['ruta_id'=>$ruta_id, 'id_cliente'=>$id_cliente, 'dia'=>$dia]);
      }
    }

  });

  jexit(['ok'=>1]);

}catch(Exception $e){
  jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()]);
}
