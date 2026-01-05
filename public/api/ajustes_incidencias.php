<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function i0($v){ $v = trim((string)$v); return $v==='' ? 0 : (int)$v; }

function tipo_guardar($v){
  $v = trim((string)$v);
  // En este catálogo Tipo_Cat es fijo A (Ajustes)
  return 'A';
}

function regla_dev_proveedor($v){
  // UI manda 'DP' (Devolver Proveedor) o 'CI' (Cierre Incidencia)
  $v = trim((string)$v);
  if($v==='DP') return 1;
  if($v==='CI') return 0;
  // si ya viene 0/1, respetar
  if($v==='1' || $v===1) return 1;
  return 0;
}

function validar($data){
  $errs=[];
  $mot = trim((string)($data['Des_Motivo'] ?? ''));
  if($mot==='') $errs[]='Des_Motivo es obligatorio';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=ajustes_incidencias_'.$tipo.'.csv');
  $out = fopen('php://output','w');

  $headers = ['id','Tipo_Cat','Des_Motivo','dev_proveedor','Activo'];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',',$headers)." FROM c_motivo WHERE IFNULL(Tipo_Cat,'A')='A' ORDER BY id";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }
  fclose($out); exit;
}

/* =========================
   IMPORT CSV (UPSERT por (Tipo_Cat + Des_Motivo))
========================= */
if($action==='import_csv'){
  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);
  $esperadas = ['id','Tipo_Cat','Des_Motivo','dev_proveedor','Activo'];
  if($headers !== $esperadas){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]); exit;
  }

  $stFind = $pdo->prepare("SELECT id FROM c_motivo WHERE Tipo_Cat=? AND Des_Motivo=? LIMIT 1");
  $stIns  = $pdo->prepare("
    INSERT INTO c_motivo (Tipo_Cat, Des_Motivo, dev_proveedor, Activo)
    VALUES (?,?,?,?)
  ");
  $stUpd  = $pdo->prepare("
    UPDATE c_motivo SET Tipo_Cat=?, Des_Motivo=?, dev_proveedor=?, Activo=?
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

      // Normalizamos: Tipo_Cat siempre 'A'
      $tipoCat = 'A';
      $data['Tipo_Cat'] = 'A';

      $errs = validar($data);
      if($errs){ $rows_err++; $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs)]; continue; }

      $motivo = trim((string)$data['Des_Motivo']);

      $stFind->execute([$tipoCat, $motivo]);
      $id = (int)($stFind->fetchColumn() ?: 0);

      $dev = i0($data['dev_proveedor'] ?? 0);
      $act = i1($data['Activo'] ?? 1);

      if($id>0){
        $stUpd->execute([$tipoCat, $motivo, $dev, $act, $id]);
      }else{
        $stIns->execute([$tipoCat, $motivo, $dev, $act]);
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
  $scope = trim((string)($_GET['scope'] ?? '')); // '' | 'DP' | 'CI'
  $limit  = max(1, min(200, (int)($_GET['limit'] ?? 25)));
  $offset = max(0, (int)($_GET['offset'] ?? 0));

  $where = " WHERE IFNULL(Tipo_Cat,'A')='A' AND IFNULL(Activo,1)=:activo ";

  if($scope==='DP'){
    $where .= " AND IFNULL(dev_proveedor,0)=1 ";
  }elseif($scope==='CI'){
    $where .= " AND IFNULL(dev_proveedor,0)=0 ";
  }

  if($q!==''){
    $where .= " AND (Des_Motivo LIKE :q OR CAST(dev_proveedor AS CHAR) LIKE :q OR Tipo_Cat LIKE :q) ";
  }

  $sqlCount = "SELECT COUNT(*) FROM c_motivo $where";
  $stc = $pdo->prepare($sqlCount);
  $stc->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($q!=='') $stc->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $sql = "SELECT id, Tipo_Cat, Des_Motivo, dev_proveedor, Activo
          FROM c_motivo
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
    $st=$pdo->prepare("SELECT * FROM c_motivo WHERE id=? LIMIT 1");
    $st->execute([$id]);
    echo json_encode($st->fetch(PDO::FETCH_ASSOC)); exit;
  }

  case 'create':{
    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $tipoCat = 'A';
    $st=$pdo->prepare("
      INSERT INTO c_motivo (Tipo_Cat, Des_Motivo, dev_proveedor, Activo)
      VALUES (?,?,?,?)
    ");

    $st->execute([
      $tipoCat,
      s($_POST['Des_Motivo'] ?? null),
      regla_dev_proveedor($_POST['scope'] ?? ($_POST['dev_proveedor'] ?? 0)),
      i1($_POST['Activo'] ?? 1),
    ]);

    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
  }

  case 'update':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }

    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $tipoCat = 'A';
    $st=$pdo->prepare("
      UPDATE c_motivo SET
        Tipo_Cat=?, Des_Motivo=?, dev_proveedor=?, Activo=?
      WHERE id=? LIMIT 1
    ");
    $st->execute([
      $tipoCat,
      s($_POST['Des_Motivo'] ?? null),
      regla_dev_proveedor($_POST['scope'] ?? ($_POST['dev_proveedor'] ?? 0)),
      i1($_POST['Activo'] ?? 1),
      $id
    ]);

    echo json_encode(['success'=>true]); exit;
  }

  case 'delete':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE c_motivo SET Activo=0 WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }

  case 'restore':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE c_motivo SET Activo=1 WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }

  default:
    echo json_encode(['error'=>'Acción no válida']); exit;
}
