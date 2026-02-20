<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

/* ================== HELPERS ================== */
function out($ok,$msg='',$extra=[]){
  echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg],$extra),JSON_UNESCAPED_UNICODE);
  exit;
}
function up($v){ return strtoupper(trim((string)$v)); }

/* ================== CONST ================== */
$USR = 'admin_web'; // IMPORTADO POR SISTEMA
$MOV_TRASLADO = 12;

/* ================== ACTION ================== */
$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ================== LAYOUT ================== */
if($action==='layout'){
  header('Content-Type:text/csv');
  header('Content-Disposition:attachment; filename=layout_traslado_almacenes.csv');
  echo "BL_ORIGEN,ITEM,CANTIDAD,LOTE_SERIE,ZONA_RECIBO_DESTINO\n";
  exit;
}

/* ================== ZONA DESTINO ================== */
function zona_destino(PDO $pdo,$zona){
  $q=$pdo->prepare("
    SELECT tr.cve_almacp, cu.idy_ubica
    FROM tubicacionesretencion tr
    JOIN c_ubicacion cu ON cu.cve_ubicacion = tr.cve_ubicacion
    WHERE tr.cve_ubicacion = ? AND tr.activo = 1
    LIMIT 1
  ");
  $q->execute([$zona]);
  return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ================== BL ORIGEN ================== */
function bl_origen(PDO $pdo,$bl){
  $q=$pdo->prepare("
    SELECT cve_almac, idy_ubica
    FROM c_ubicacion
    WHERE bl = ?
    LIMIT 1
  ");
  $q->execute([$bl]);
  return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ================== ITEM ================== */
function es_lp(PDO $pdo,$item){
  $q=$pdo->prepare("SELECT 1 FROM c_charolas WHERE Clave_Contenedor=? LIMIT 1");
  $q->execute([$item]);
  return (bool)$q->fetchColumn();
}
function producto(PDO $pdo,$sku){
  $q=$pdo->prepare("SELECT maneja_lote,maneja_serie FROM c_articulo WHERE cve_articulo=?");
  $q->execute([$sku]);
  return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ================== PREVIEW ================== */
if($action==='previsualizar'){
  if(empty($_FILES['archivo'])) out(false,'Archivo requerido');
  $rows=array_map('str_getcsv',file($_FILES['archivo']['tmp_name']));
  array_shift($rows);

  $ok=0;$err=0;$out=[];
  foreach($rows as $i=>$r){
    [$bl,$item,$qty,$lot,$zona]=array_map('up',$r);
    $st='OK';$msg='OK';

    if(!$bl||!$item||!$zona){$st='ERR';$msg='Campos obligatorios';}
    elseif(!bl_origen($pdo,$bl)){$st='ERR';$msg='BL origen inválido';}
    elseif(!zona_destino($pdo,$zona)){$st='ERR';$msg='Zona destino inválida';}
    elseif(es_lp($pdo,$item)){
      $qty='';$lot='';
    }else{
      $p=producto($pdo,$item);
      if(!$p){$st='ERR';$msg='Producto no existe';}
      elseif($qty<=0){$st='ERR';$msg='Cantidad obligatoria';}
      elseif(($p['maneja_lote']=='S'||$p['maneja_serie']=='S')&&!$lot){
        $st='ERR';$msg='Lote/Serie obligatorio';
      }
    }

    ($st==='OK')?$ok++:$err++;
    $out[]=[
      'estado'=>$st,'mensaje'=>$msg,
      'bl'=>$bl,'item'=>$item,'cantidad'=>$qty,'lote'=>$lot,'zona'=>$zona
    ];
  }
  out(true,'Preview',[
    'total'=>count($rows),
    'total_ok'=>$ok,
    'total_err'=>$err,
    'filas'=>$out
  ]);
}

/* ================== PROCESAR ================== */
if($action==='procesar'){
  if(empty($_FILES['archivo'])) out(false,'Archivo requerido');
  $rows=array_map('str_getcsv',file($_FILES['archivo']['tmp_name']));
  array_shift($rows);

  $folio='TRALM'.date('YmdHis');
  $pdo->beginTransaction();

  try{
    $pdo->prepare("
      INSERT INTO th_importacion
      (folio,tipo_importador,usuario,estatus,fecha)
      VALUES(?, 'TRALM', ?, 'OK', NOW())
    ")->execute([$folio,$USR]);

    foreach($rows as $r){
      [$bl,$item,$qty,$lot,$zona]=array_map('up',$r);

      $ori=bl_origen($pdo,$bl);
      $dst=zona_destino($pdo,$zona);
      $esLP=es_lp($pdo,$item);

      // Kardex
      $pdo->prepare("
        INSERT INTO t_cardex
        (id_TipoMovimiento,referencia,usuario,
         cve_almac_origen,cve_almac_destino,item,cantidad,lote_serie,fecha)
        VALUES(?,?,?,?,?,?,?,?,NOW())
      ")->execute([
        $MOV_TRASLADO,$folio,$USR,
        $ori['cve_almac'],$dst['cve_almacp'],
        $item,($esLP?1:$qty),$lot
      ]);

      // Aduana
      $pdo->prepare("
        INSERT INTO td_aduana
        (folio,item,lote_serie,cantidad_pedida,cantidad_recibida,protocolo)
        VALUES(?,?,?,?,?,'TRASLADO ENTRE ALMACENES')
      ")->execute([$folio,$item,$lot,($esLP?1:$qty),($esLP?1:$qty)]);
    }

    $pdo->commit();
    out(true,'Procesado',['folio'=>$folio]);

  }catch(Exception $e){
    $pdo->rollBack();
    out(false,$e->getMessage());
  }
}

out(false,'Acción inválida');
