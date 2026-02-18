<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

/* =====================================================
 * HELPERS
 * ===================================================== */
function s($v){ $v = trim((string)$v); return $v === '' ? null : $v; }
function i0($v){ return ($v === '' || $v === null) ? 0 : (int)$v; }
function i1($v){ return ($v === '' || $v === null) ? 1 : (int)$v; }
function dnull($v){ return ($v === '' || $v === null) ? null : (float)$v; }

function validar_obligatorios($data){
  $errs=[];
  if(trim((string)($data['cve_almac']??''))==='') $errs[]='cve_almac es obligatorio';
  if(trim((string)($data['Clave_Contenedor']??''))==='') $errs[]='Clave_Contenedor es obligatorio';
  return $errs;
}

function normalizar_tipo($tipo){
  $t=strtoupper(trim((string)$tipo));
  if($t==='PALLET') return 'PALLET';
  return 'CONTENEDOR';
}

/* =====================================================
 * EXPORT EXCEL (RESPETA FILTROS)
 * ===================================================== */
if($action==='export_excel'){

  $inactivos=(int)($_GET['inactivos']??0);
  $almac_clave=trim($_GET['almac_clave']??'');
  $tipo=trim($_GET['tipo']??'');
  $q=trim($_GET['q']??'');

  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="pallets_contenedores.csv"');
  echo "\xEF\xBB\xBF"; // BOM UTF8

  $where="WHERE IFNULL(ch.Activo,1)=:activo";
  $params=[':activo'=>$inactivos?0:1];

  if($almac_clave!==''){
    $where.=" AND ap.clave=:almac_clave";
    $params[':almac_clave']=$almac_clave;
  }

  if($tipo!==''){
    $where.=" AND UPPER(TRIM(ch.tipo))=:tipo";
    $params[':tipo']=strtoupper($tipo);
  }

  if($q!==''){
    $where.=" AND (
      ch.Clave_Contenedor LIKE :q OR
      ch.Pedido LIKE :q OR
      ch.CveLP LIKE :q
    )";
    $params[':q']="%$q%";
  }

  $sql="
    SELECT ap.clave,
           ch.Clave_Contenedor,
           ch.tipo,
           ch.Pedido,
           ch.CveLP,
           ch.Activo
    FROM c_charolas ch
    LEFT JOIN c_almacenp ap ON ap.id=ch.cve_almac
    $where
    ORDER BY ap.clave, ch.Clave_Contenedor
  ";

  $st=$pdo->prepare($sql);
  $st->execute($params);

  $out=fopen('php://output','w');
  fputcsv($out,['Almacén','Clave','Tipo','Pedido','LP','Activo']);

  while($r=$st->fetch(PDO::FETCH_ASSOC)){
    fputcsv($out,[
      $r['clave'],
      $r['Clave_Contenedor'],
      strtoupper($r['tipo'])==='PALLET'?'Pallet':'Contenedor',
      $r['Pedido'],
      $r['CveLP'],
      ((int)$r['Activo']===1)?'Sí':'No'
    ]);
  }

  fclose($out);
  exit;
}

/* =====================================================
 * LIST (FILTROS + PAGINACIÓN 25)
 * ===================================================== */
if($action==='list'){

  header('Content-Type: application/json; charset=utf-8');

  $inactivos=(int)($_GET['inactivos']??0);
  $almac_clave=trim($_GET['almac_clave']??'');
  $tipo=trim($_GET['tipo']??'');
  $q=trim($_GET['q']??'');

  $page=max(1,(int)($_GET['page']??1));
  $per_page=25;
  $offset=($page-1)*$per_page;

  $where="WHERE IFNULL(ch.Activo,1)=:activo";
  $params=[':activo'=>$inactivos?0:1];

  if($almac_clave!==''){
    $where.=" AND ap.clave=:almac_clave";
    $params[':almac_clave']=$almac_clave;
  }

  if($tipo!==''){
    $where.=" AND UPPER(TRIM(ch.tipo))=:tipo";
    $params[':tipo']=strtoupper($tipo);
  }

  if($q!==''){
    $where.=" AND (
      ch.Clave_Contenedor LIKE :q OR
      ch.Pedido LIKE :q OR
      ch.CveLP LIKE :q
    )";
    $params[':q']="%$q%";
  }

  // TOTAL
  $sqlCount="
    SELECT COUNT(*)
    FROM c_charolas ch
    LEFT JOIN c_almacenp ap ON ap.id=ch.cve_almac
    $where
  ";
  $stC=$pdo->prepare($sqlCount);
  $stC->execute($params);
  $total=(int)$stC->fetchColumn();
  $total_paginas=max(1,ceil($total/$per_page));

  if($page>$total_paginas){
    $page=$total_paginas;
    $offset=($page-1)*$per_page;
  }

  // DATA
  $sql="
    SELECT
      ch.IDContenedor,
      ap.clave AS almac_clave,
      ch.Clave_Contenedor,
      ch.tipo,
      ch.Pedido,
      ch.CveLP,
      ch.Activo
    FROM c_charolas ch
    LEFT JOIN c_almacenp ap ON ap.id=ch.cve_almac
    $where
    ORDER BY ap.clave, ch.Clave_Contenedor
    LIMIT $per_page OFFSET $offset
  ";

  $st=$pdo->prepare($sql);
  $st->execute($params);
  $rows=$st->fetchAll(PDO::FETCH_ASSOC);

  foreach($rows as &$r){
    $r['tipo']=strtoupper($r['tipo'])==='PALLET'?'Pallet':'Contenedor';
  }

  echo json_encode([
    'rows'=>$rows,
    'pagina'=>$page,
    'total_paginas'=>$total_paginas,
    'total'=>$total
  ]);
  exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE
 * ===================================================== */
switch($action){

case 'get':
  header('Content-Type: application/json');
  $id=(int)($_GET['IDContenedor']??0);
  $st=$pdo->prepare("SELECT * FROM c_charolas WHERE IDContenedor=?");
  $st->execute([$id]);
  echo json_encode($st->fetch());
break;

case 'create':
case 'update':

  header('Content-Type: application/json');

  $errs=validar_obligatorios($_POST);
  if($errs){
    echo json_encode(['error'=>'Validación','detalles'=>$errs]);
    exit;
  }

  $_POST['tipo']=normalizar_tipo($_POST['tipo']??'');

  if($action==='create'){

    $sql="INSERT INTO c_charolas
    (cve_almac,Clave_Contenedor,descripcion,Permanente,Pedido,sufijo,tipo,Activo,
     alto,ancho,fondo,peso,pesomax,capavol,Costo,CveLP,TipoGen)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $st=$pdo->prepare($sql);
    $st->execute([
      (int)$_POST['cve_almac'],
      $_POST['Clave_Contenedor'],
      s($_POST['descripcion']??null),
      i0($_POST['Permanente']??0),
      s($_POST['Pedido']??null),
      ($_POST['sufijo']??'')===''?null:(int)$_POST['sufijo'],
      $_POST['tipo'],
      i1($_POST['Activo']??1),
      ($_POST['alto']??'')===''?null:(int)$_POST['alto'],
      ($_POST['ancho']??'')===''?null:(int)$_POST['ancho'],
      ($_POST['fondo']??'')===''?null:(int)$_POST['fondo'],
      dnull($_POST['peso']??null),
      dnull($_POST['pesomax']??null),
      dnull($_POST['capavol']??null),
      dnull($_POST['Costo']??null),
      s($_POST['CveLP']??null),
      ($_POST['TipoGen']??'')===''?null:(int)$_POST['TipoGen']
    ]);

    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);

  } else {

    $sql="UPDATE c_charolas SET
      cve_almac=?,Clave_Contenedor=?,descripcion=?,Permanente=?,Pedido=?,sufijo=?,tipo=?,Activo=?,
      alto=?,ancho=?,fondo=?,peso=?,pesomax=?,capavol=?,Costo=?,CveLP=?,TipoGen=?
      WHERE IDContenedor=? LIMIT 1";

    $st=$pdo->prepare($sql);
    $st->execute([
      (int)$_POST['cve_almac'],
      $_POST['Clave_Contenedor'],
      s($_POST['descripcion']??null),
      i0($_POST['Permanente']??0),
      s($_POST['Pedido']??null),
      ($_POST['sufijo']??'')===''?null:(int)$_POST['sufijo'],
      $_POST['tipo'],
      i1($_POST['Activo']??1),
      ($_POST['alto']??'')===''?null:(int)$_POST['alto'],
      ($_POST['ancho']??'')===''?null:(int)$_POST['ancho'],
      ($_POST['fondo']??'')===''?null:(int)$_POST['fondo'],
      dnull($_POST['peso']??null),
      dnull($_POST['pesomax']??null),
      dnull($_POST['capavol']??null),
      dnull($_POST['Costo']??null),
      s($_POST['CveLP']??null),
      ($_POST['TipoGen']??'')===''?null:(int)$_POST['TipoGen'],
      (int)$_POST['IDContenedor']
    ]);

    echo json_encode(['success'=>true]);
  }

break;

case 'delete':
  $pdo->prepare("UPDATE c_charolas SET Activo=0 WHERE IDContenedor=?")
      ->execute([(int)$_POST['IDContenedor']]);
  echo json_encode(['success'=>true]);
break;

case 'restore':
  $pdo->prepare("UPDATE c_charolas SET Activo=1 WHERE IDContenedor=?")
      ->execute([(int)$_POST['IDContenedor']]);
  echo json_encode(['success'=>true]);
break;

default:
  header('Content-Type: application/json');
  echo json_encode(['error'=>'Acción no válida']);
}
