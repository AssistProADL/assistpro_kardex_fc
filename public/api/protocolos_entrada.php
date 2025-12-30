<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function i0null($v){ $v=trim((string)$v); return $v==='' ? null : (int)$v; }

function validar($data){
  $errs=[];
  $idp = trim((string)($data['ID_Protocolo'] ?? ''));
  $des = trim((string)($data['descripcion'] ?? ''));
  if($idp==='') $errs[]='ID_Protocolo es obligatorio';
  if($des==='') $errs[]='descripcion es obligatoria';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=protocolos_entrada_'.$tipo.'.csv');
  $out = fopen('php://output','w');

  $headers = ['id','ID_Protocolo','descripcion','FOLIO','Activo'];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',',$headers)." FROM t_protocolo ORDER BY id";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }
  fclose($out); exit;
}

/* =========================
   IMPORT CSV (UPSERT por ID_Protocolo)
========================= */
if($action==='import_csv'){
  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);
  $esperadas = ['id','ID_Protocolo','descripcion','FOLIO','Activo'];
  if($headers !== $esperadas){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]); exit;
  }

  // Buscamos por ID_Protocolo (clave natural)
  $stFind = $pdo->prepare("SELECT id FROM t_protocolo WHERE ID_Protocolo=? LIMIT 1");

  $stIns  = $pdo->prepare("
    INSERT INTO t_protocolo (ID_Protocolo, descripcion, FOLIO, Activo)
    VALUES (?,?,?,?)
  ");
  $stUpd  = $pdo->prepare("
    UPDATE t_protocolo SET
      descripcion=?, FOLIO=?, Activo=?
    WHERE id=? LIMIT 1
  ");

  $rows_ok=0; $rows_err=0; $errores=[];
  $pdo->beginTransaction();
  try{
    $linea=1;
    while(($r=fgetcsv($fh))!==false){
      $linea++;
      if(!$r || count($r)<count($esperadas)){
        $rows_err++; $errores[]=['fila'=>$linea,'motivo'=>'Fila incompleta']; continue;
      }
      $data = array_combine($esperadas,$r);

      $errs = validar($data);
      if($errs){ $rows_err++; $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs)]; continue; }

      $idp = trim((string)$data['ID_Protocolo']);
      $stFind->execute([$idp]);
      $id = (int)($stFind->fetchColumn() ?: 0);

      $folio = i0null($data['FOLIO'] ?? null);

      if($id>0){
        $stUpd->execute([
          s($data['descripcion']),
          $folio,
          i1($data['Activo'] ?? 1),
          $id
        ]);
      }else{
        $stIns->execute([
          $idp,
          s($data['descripcion']),
          $folio,
          i1($data['Activo'] ?? 1)
        ]);
      }
      $rows_ok++;
    }
    $pdo->commit();
    echo json_encode(['success'=>true,'rows_ok'=>$rows_ok,'rows_err'=>$rows_err,'errores'=>$errores]); exit;
  }catch(Throwable $e){
    $pdo->rollBack();
    echo json_encode(['error'=>$e->getMessage()]); exit;
  }
}

/* =========================
   LIST (paginado server-side)
========================= */
if($action==='list'){
  $inactivos = (int)($_GET['inactivos'] ?? 0);
  $q = trim((string)($_GET['q'] ?? ''));
  $limit  = max(1, min(200, (int)($_GET['limit'] ?? 25)));
  $offset = max(0, (int)($_GET['offset'] ?? 0));

  $where = " WHERE IFNULL(Activo,1)=:activo ";
  if($q!==''){
    $where .= " AND (ID_Protocolo LIKE :q OR descripcion LIKE :q OR CAST(FOLIO AS CHAR) LIKE :q) ";
  }

  $sqlCount = "SELECT COUNT(*) FROM t_protocolo $where";
  $stc = $pdo->prepare($sqlCount);
  $stc->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($q!=='') $stc->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $sql = "SELECT id,ID_Protocolo,descripcion,FOLIO,Activo
          FROM t_protocolo
          $where
          ORDER BY id DESC
          LIMIT $limit OFFSET $offset";
  $st = $pdo->prepare($sql);
  $st->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($q!=='') $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $st->execute();

  echo json_encode(['rows'=>$st->fetchAll(PDO::FETCH_ASSOC),'total'=>$total]); exit;
}

/* =========================
   CRUD
========================= */
switch($action){
  case 'get':{
    $id = (int)($_GET['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $st=$pdo->prepare("SELECT * FROM t_protocolo WHERE id=? LIMIT 1");
    $st->execute([$id]);
    echo json_encode($st->fetch(PDO::FETCH_ASSOC)); exit;
  }
  case 'create':{
    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $st=$pdo->prepare("
      INSERT INTO t_protocolo (ID_Protocolo, descripcion, FOLIO, Activo)
      VALUES (?,?,?,?)
    ");
    $st->execute([
      trim((string)$_POST['ID_Protocolo']),
      s($_POST['descripcion'] ?? null),
      i0null($_POST['FOLIO'] ?? null),
      i1($_POST['Activo'] ?? 1)
    ]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
  }
  case 'update':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }

    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $st=$pdo->prepare("
      UPDATE t_protocolo SET
        ID_Protocolo=?, descripcion=?, FOLIO=?, Activo=?
      WHERE id=? LIMIT 1
    ");
    $st->execute([
      trim((string)$_POST['ID_Protocolo']),
      s($_POST['descripcion'] ?? null),
      i0null($_POST['FOLIO'] ?? null),
      i1($_POST['Activo'] ?? 1),
      $id
    ]);
    echo json_encode(['success'=>true]); exit;
  }
  case 'delete':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE t_protocolo SET Activo=0 WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }
  case 'restore':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE t_protocolo SET Activo=1 WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }
  default:
    echo json_encode(['error'=>'Acción no válida']); exit;
}
