<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function i0null($v){ $v = trim((string)$v); return $v==='' ? null : (int)$v; }

function validar($data){
  $errs=[];
  $clave = trim((string)($data['Clave'] ?? ''));
  $desc  = trim((string)($data['Descripcion'] ?? ''));
  $prio  = trim((string)($data['Prioridad'] ?? ''));
  if($clave==='') $errs[]='Clave es obligatoria';
  if($desc==='')  $errs[]='Descripcion es obligatoria';
  if($prio!=='' && !ctype_digit($prio)) $errs[]='Prioridad debe ser numérica';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=tipos_prioridad_'.$tipo.'.csv');
  $out = fopen('php://output','w');

  $headers = ['ID_Tipoprioridad','Clave','Descripcion','Prioridad','Status','Activo'];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',',$headers)." FROM t_tiposprioridad ORDER BY ID_Tipoprioridad";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }
  fclose($out); exit;
}

/* =========================
   IMPORT CSV (UPSERT por Clave)
========================= */
if($action==='import_csv'){
  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);
  $esperadas = ['ID_Tipoprioridad','Clave','Descripcion','Prioridad','Status','Activo'];
  if($headers !== $esperadas){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]); exit;
  }

  $stFind = $pdo->prepare("SELECT ID_Tipoprioridad FROM t_tiposprioridad WHERE Clave=? LIMIT 1");

  $stIns  = $pdo->prepare("
    INSERT INTO t_tiposprioridad (Descripcion, Prioridad, Status, Activo, Clave)
    VALUES (?,?,?,?,?)
  ");
  $stUpd  = $pdo->prepare("
    UPDATE t_tiposprioridad SET
      Descripcion=?, Prioridad=?, Status=?, Activo=?
    WHERE ID_Tipoprioridad=? LIMIT 1
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

      $clave = trim((string)$data['Clave']);
      $stFind->execute([$clave]);
      $id = (int)($stFind->fetchColumn() ?: 0);

      $status = s($data['Status'] ?? null);
      if($status!==null && $status!=='A' && $status!=='B') $status = 'B';

      if($id>0){
        $stUpd->execute([
          s($data['Descripcion']),
          i0null($data['Prioridad'] ?? null),
          $status,
          i1($data['Activo'] ?? 1),
          $id
        ]);
      }else{
        $stIns->execute([
          s($data['Descripcion']),
          i0null($data['Prioridad'] ?? null),
          $status ?? 'B',
          i1($data['Activo'] ?? 1),
          $clave
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
    $where .= " AND (
      Clave LIKE :q OR Descripcion LIKE :q OR CAST(Prioridad AS CHAR) LIKE :q OR Status LIKE :q
    ) ";
  }

  $sqlCount = "SELECT COUNT(*) FROM t_tiposprioridad $where";
  $stc = $pdo->prepare($sqlCount);
  $stc->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($q!=='') $stc->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $sql = "SELECT
      ID_Tipoprioridad,Clave,Descripcion,Prioridad,Status,Activo
    FROM t_tiposprioridad
    $where
    ORDER BY Prioridad IS NULL, Prioridad ASC, ID_Tipoprioridad DESC
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
    $st=$pdo->prepare("SELECT * FROM t_tiposprioridad WHERE ID_Tipoprioridad=? LIMIT 1");
    $st->execute([$id]);
    echo json_encode($st->fetch(PDO::FETCH_ASSOC)); exit;
  }
  case 'create':{
    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $status = s($_POST['Status'] ?? null);
    if($status!==null && $status!=='A' && $status!=='B') $status='B';

    $st=$pdo->prepare("
      INSERT INTO t_tiposprioridad (Descripcion, Prioridad, Status, Activo, Clave)
      VALUES (?,?,?,?,?)
    ");
    $st->execute([
      s($_POST['Descripcion'] ?? null),
      i0null($_POST['Prioridad'] ?? null),
      $status ?? 'B',
      i1($_POST['Activo'] ?? 1),
      trim((string)$_POST['Clave'])
    ]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
  }
  case 'update':{
    $id = (int)($_POST['ID_Tipoprioridad'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'ID_Tipoprioridad requerido']); exit; }

    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $status = s($_POST['Status'] ?? null);
    if($status!==null && $status!=='A' && $status!=='B') $status='B';

    $st=$pdo->prepare("
      UPDATE t_tiposprioridad SET
        Clave=?, Descripcion=?, Prioridad=?, Status=?, Activo=?
      WHERE ID_Tipoprioridad=? LIMIT 1
    ");
    $st->execute([
      trim((string)$_POST['Clave']),
      s($_POST['Descripcion'] ?? null),
      i0null($_POST['Prioridad'] ?? null),
      $status ?? 'B',
      i1($_POST['Activo'] ?? 1),
      $id
    ]);
    echo json_encode(['success'=>true]); exit;
  }
  case 'delete':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE t_tiposprioridad SET Activo=0 WHERE ID_Tipoprioridad=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }
  case 'restore':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE t_tiposprioridad SET Activo=1 WHERE ID_Tipoprioridad=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }
  default:
    echo json_encode(['error'=>'Acción no válida']); exit;
}
