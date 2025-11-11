<?php
/* kardex_etl.php — Backend ETL basado en kardex_actual.php (acciones compatibles)
   Fuente: v_kardex_doble_partida (assistpro_etl_fc). Read-only. */
error_reporting(0);
$__dbLoaded = false;
$__dbPath = __DIR__ . '/../app/db.php';
if (file_exists($__dbPath)) { require_once $__dbPath; $__dbLoaded = isset($pdo) && ($pdo instanceof PDO); }
if (!$__dbLoaded) {
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4','root','',array(
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
}
function qAll($pdo,$sql,$p=array()){ $st=$pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); }
function qOne($pdo,$sql,$p=array()){ $st=$pdo->prepare($sql); $st->execute($p); return $st->fetchColumn(); }
function hasTable($pdo,$n){ return (int)qOne($pdo,'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$n]); }
function hasView($pdo,$n){ return (int)qOne($pdo,'SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$n]); }
$action = isset($_POST['action'])?$_POST['action']:'';

if ($action==='enter-view'){
  $almacenes=array();
  if (hasTable($pdo,'c_almacenp')){
    $almacenes=qAll($pdo,'SELECT id, clave, des_almac FROM c_almacenp WHERE Activo=1 ORDER BY des_almac');
  } elseif (hasView($pdo,'v_kardex_doble_partida')){
    $almacenes=qAll($pdo,"SELECT DISTINCT alm_id AS id, alm_id AS clave, CONCAT('ALM ', alm_id) AS des_almac
                           FROM (SELECT alm_ori_id AS alm_id FROM v_kardex_doble_partida
                                 UNION SELECT alm_dst_id FROM v_kardex_doble_partida) t
                           WHERE alm_id IS NOT NULL ORDER BY des_almac");
  }
  $options="<option value=''>Seleccione Almacén (".count($almacenes).")</option>";
  foreach($almacenes as $r){
    $v=htmlspecialchars($r['id']??$r['clave']??''); $t=htmlspecialchars(($r['clave']??'').' - '.($r['des_almac']??''));
    $options.="<option value='".$v."'>".$t."</option>";
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('success'=>true,'almacen'=>$options)); exit;
}
elseif($action==='getLotesArticulosKardex'){
  $art=isset($_POST['cve_articulo'])?trim($_POST['cve_articulo']):'';
  $emp=isset($_POST['empresa_id'])?trim($_POST['empresa_id']):'';
  if($art===''){ echo json_encode(array('success'=>true,'lotes'=>"<option value=''>Seleccione Lote | Serie (0)</option>")); exit;}
  if(!hasView($pdo,'v_kardex_doble_partida')){ echo json_encode(array('success'=>false,'lotes'=>"<option value=''>No existe v_kardex_doble_partida</option>")); exit;}
  $where='WHERE producto_id = :art AND lote IS NOT NULL AND lote <> ""'; $p=array(':art'=>$art);
  if($emp!==''){ $where.=' AND empresa_id = :emp'; $p[':emp']=$emp; }
  $lotes=qAll($pdo,"SELECT DISTINCT lote FROM v_kardex_doble_partida $where ORDER BY lote",$p);
  $i=0; $opt=''; foreach($lotes as $r){ $lv=htmlspecialchars($r['lote']); $opt.="<option value='".$lv."'>".$lv."</option>"; $i++; }
  $options="<option value=''>Seleccione Lote | Serie (".$i.")</option>".$opt;
  echo json_encode(array('success'=>true,'lotes'=>$options)); exit;
}
elseif($action==='loadDetalleCajas'){
  $page=(int)($_POST['page']??1); $limit=(int)($_POST['rows']??50);
  $sidx=$_POST['sidx']??'fecha_hora'; $sord=$_POST['sord']??'DESC';
  $almacen=$_POST['almacen']??null; $lp=$_POST['lp']??null; $emp=$_POST['empresa_id']??null;
  $start=$limit*$page-$limit; if($start<0)$start=0;
  $has_ec=hasTable($pdo,'ts_existenciacajas'); $has_u=hasTable($pdo,'c_ubicacion'); $has_ch=hasTable($pdo,'c_charolas'); $has_al=hasTable($pdo,'c_almacenp');
  if($has_ec && $has_u && $has_ch && $has_al){
    $where=' WHERE 1=1 '; $p=array();
    if($emp){ $where.=' AND ec.empresa_id = :emp'; $p[':emp']=$emp; }
    if($almacen){ $where.=' AND al.id = :alm'; $p[':alm']=$almacen; }
    if($lp){ $where.=" AND IFNULL(ch.CveLP,'') = :lp"; $p[':lp']=$lp; }
    $total=(int)qOne($pdo,"SELECT COUNT(*) FROM ts_existenciacajas ec 
                            JOIN c_almacenp al ON al.id = ec.Cve_Almac
                            LEFT JOIN c_charolas ch ON ch.cve_almac = al.id AND ec.nTarima = ch.IDContenedor 
                            LEFT JOIN c_charolas cj ON cj.cve_almac = al.id AND ec.Id_Caja = cj.IDContenedor AND cj.tipo = 'Caja'
                            LEFT JOIN c_ubicacion u ON u.cve_almac = al.id AND ec.idy_ubica = u.idy_ubica $where",$p);
    $total_pages=$total>0?ceil($total/$limit):0;
    $sql="SELECT u.CodigoCSD AS ubicacion, IFNULL(ch.CveLP,'') AS LP, IFNULL(cj.IDContenedor,'') AS Caja, ec.cve_articulo,
                 '' AS des_articulo, ec.cve_lote, NULL AS caducidad, IFNULL(cj.PiezasXCaja,0) AS PiezasXCaja
          FROM ts_existenciacajas ec 
          JOIN c_almacenp al ON al.id = ec.Cve_Almac
          LEFT JOIN c_charolas ch ON ch.cve_almac = al.id AND ec.nTarima = ch.IDContenedor 
          LEFT JOIN c_charolas cj ON cj.cve_almac = al.id AND ec.Id_Caja = cj.IDContenedor AND cj.tipo = 'Caja'
          LEFT JOIN c_ubicacion u ON u.cve_almac = al.id AND ec.idy_ubica = u.idy_ubica
          $where ORDER BY $sidx $sord LIMIT :lim OFFSET :off";
    $st=$pdo->prepare($sql); foreach($p as $k=>$v){ $st->bindValue($k,$v);} $st->bindValue(':lim',(int)$limit,PDO::PARAM_INT); $st->bindValue(':off',(int)$start,PDO::PARAM_INT);
    $st->execute(); $rows=$st->fetchAll();
    $resp=new stdClass(); $resp->page=$page; $resp->total=$total_pages; $resp->records=$total; $resp->rows=array();
    $i=0; foreach($rows as $r){ $resp->rows[$i]['id']=$i+1; $resp->rows[$i]['cell']=array($r['ubicacion'],$r['LP'],$r['Caja'],$r['cve_articulo'],$r['des_articulo'],$r['cve_lote'],$r['caducidad'],$r['PiezasXCaja']); $i++; }
    header('Content-Type: application/json; charset=utf-8'); echo json_encode($resp); exit;
  } else {
    $resp=(object)['page'=>$page,'total'=>0,'records'=>0,'rows'=>[]];
    header('Content-Type: application/json; charset=utf-8'); echo json_encode($resp); exit;
  }
}
else{
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('success'=>false,'msg'=>'Acción no soportada en ETL')); exit;
}
?>