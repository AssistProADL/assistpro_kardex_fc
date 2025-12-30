<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }

function validar($data){
  $errs=[];
  $cve = trim((string)($data['cve_proveedor'] ?? ''));
  $emp = trim((string)($data['Empresa'] ?? ''));
  $nom = trim((string)($data['Nombre'] ?? ''));
  $pais= trim((string)($data['pais'] ?? ''));
  if($cve==='') $errs[]='cve_proveedor es obligatorio';
  if($pais==='') $errs[]='pais es obligatorio';
  if($emp==='' && $nom==='') $errs[]='Debe capturar Empresa o Nombre';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=proveedores_'.$tipo.'.csv');
  $out = fopen('php://output','w');

  $headers = [
    'ID_Proveedor','cve_proveedor','Empresa','Nombre','RUT',
    'direccion','colonia','ciudad','estado','pais',
    'telefono1','telefono2','ID_Externo','cve_dane','latitud','longitud','Activo'
  ];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',',$headers)." FROM c_proveedores ORDER BY ID_Proveedor";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }
  fclose($out); exit;
}

/* =========================
   IMPORT CSV (UPSERT por cve_proveedor)
========================= */
if($action==='import_csv'){
  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);
  $esperadas = [
    'ID_Proveedor','cve_proveedor','Empresa','Nombre','RUT',
    'direccion','colonia','ciudad','estado','pais',
    'telefono1','telefono2','ID_Externo','cve_dane','latitud','longitud','Activo'
  ];
  if($headers !== $esperadas){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]); exit;
  }

  $stFind = $pdo->prepare("SELECT ID_Proveedor FROM c_proveedores WHERE cve_proveedor=? LIMIT 1");
  $stIns  = $pdo->prepare("
    INSERT INTO c_proveedores
    (cve_proveedor,Empresa,Nombre,RUT,direccion,colonia,ciudad,estado,pais,telefono1,telefono2,ID_Externo,cve_dane,latitud,longitud,Activo)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");
  $stUpd  = $pdo->prepare("
    UPDATE c_proveedores SET
      Empresa=?,Nombre=?,RUT=?,direccion=?,colonia=?,ciudad=?,estado=?,pais=?,telefono1=?,telefono2=?,ID_Externo=?,cve_dane=?,latitud=?,longitud=?,Activo=?
    WHERE cve_proveedor=? LIMIT 1
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

      $cve = trim((string)$data['cve_proveedor']);

      $stFind->execute([$cve]);
      $existe = $stFind->fetchColumn();

      if($existe){
        $stUpd->execute([
          s($data['Empresa']), s($data['Nombre']), s($data['RUT']), s($data['direccion']), s($data['colonia']),
          s($data['ciudad']), s($data['estado']), s($data['pais']), s($data['telefono1']), s($data['telefono2']),
          s($data['ID_Externo']), s($data['cve_dane']), s($data['latitud']), s($data['longitud']),
          i1($data['Activo']), $cve
        ]);
      }else{
        $stIns->execute([
          $cve, s($data['Empresa']), s($data['Nombre']), s($data['RUT']),
          s($data['direccion']), s($data['colonia']), s($data['ciudad']), s($data['estado']), s($data['pais']),
          s($data['telefono1']), s($data['telefono2']), s($data['ID_Externo']), s($data['cve_dane']),
          s($data['latitud']), s($data['longitud']), i1($data['Activo'])
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
  $empresa = trim((string)($_GET['empresa'] ?? ''));
  $limit  = max(1, min(200, (int)($_GET['limit'] ?? 25)));
  $offset = max(0, (int)($_GET['offset'] ?? 0));

  $where = " WHERE IFNULL(Activo,1)=:activo ";
  if($empresa!==''){
    $where .= " AND IFNULL(Empresa,'') = :empresa ";
  }
  if($q!==''){
    $where .= " AND (
      cve_proveedor LIKE :q OR Empresa LIKE :q OR Nombre LIKE :q OR RUT LIKE :q OR
      ciudad LIKE :q OR estado LIKE :q OR pais LIKE :q OR telefono1 LIKE :q OR telefono2 LIKE :q
    ) ";
  }

  $sqlCount = "SELECT COUNT(*) FROM c_proveedores $where";
  $stc = $pdo->prepare($sqlCount);
  $stc->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($empresa!=='') $stc->bindValue(':empresa', $empresa, PDO::PARAM_STR);
  if($q!=='') $stc->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $sql = "SELECT
      ID_Proveedor,cve_proveedor,Empresa,Nombre,RUT,ciudad,estado,pais,telefono1,Activo,
      direccion,colonia,telefono2,ID_Externo,cve_dane,latitud,longitud
    FROM c_proveedores
    $where
    ORDER BY ID_Proveedor DESC
    LIMIT $limit OFFSET $offset";
  $st = $pdo->prepare($sql);
  $st->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($empresa!=='') $st->bindValue(':empresa', $empresa, PDO::PARAM_STR);
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
    $st=$pdo->prepare("SELECT * FROM c_proveedores WHERE ID_Proveedor=? LIMIT 1");
    $st->execute([$id]);
    echo json_encode($st->fetch(PDO::FETCH_ASSOC)); exit;
  }
  case 'create':{
    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $st=$pdo->prepare("
      INSERT INTO c_proveedores
      (cve_proveedor,Empresa,Nombre,RUT,direccion,colonia,ciudad,estado,pais,telefono1,telefono2,ID_Externo,cve_dane,latitud,longitud,Activo)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $st->execute([
      trim((string)$_POST['cve_proveedor']),
      s($_POST['Empresa'] ?? null),
      s($_POST['Nombre'] ?? null),
      s($_POST['RUT'] ?? null),
      s($_POST['direccion'] ?? null),
      s($_POST['colonia'] ?? null),
      s($_POST['ciudad'] ?? null),
      s($_POST['estado'] ?? null),
      s($_POST['pais'] ?? null),
      s($_POST['telefono1'] ?? null),
      s($_POST['telefono2'] ?? null),
      s($_POST['ID_Externo'] ?? null),
      s($_POST['cve_dane'] ?? null),
      s($_POST['latitud'] ?? null),
      s($_POST['longitud'] ?? null),
      i1($_POST['Activo'] ?? 1),
    ]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
  }
  case 'update':{
    $id = (int)($_POST['ID_Proveedor'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'ID_Proveedor requerido']); exit; }
    $errs = validar($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $st=$pdo->prepare("
      UPDATE c_proveedores SET
        cve_proveedor=?, Empresa=?, Nombre=?, RUT=?,
        direccion=?, colonia=?, ciudad=?, estado=?, pais=?,
        telefono1=?, telefono2=?, ID_Externo=?, cve_dane=?,
        latitud=?, longitud=?, Activo=?
      WHERE ID_Proveedor=? LIMIT 1
    ");
    $st->execute([
      trim((string)$_POST['cve_proveedor']),
      s($_POST['Empresa'] ?? null),
      s($_POST['Nombre'] ?? null),
      s($_POST['RUT'] ?? null),
      s($_POST['direccion'] ?? null),
      s($_POST['colonia'] ?? null),
      s($_POST['ciudad'] ?? null),
      s($_POST['estado'] ?? null),
      s($_POST['pais'] ?? null),
      s($_POST['telefono1'] ?? null),
      s($_POST['telefono2'] ?? null),
      s($_POST['ID_Externo'] ?? null),
      s($_POST['cve_dane'] ?? null),
      s($_POST['latitud'] ?? null),
      s($_POST['longitud'] ?? null),
      i1($_POST['Activo'] ?? 1),
      $id
    ]);
    echo json_encode(['success'=>true]); exit;
  }
  case 'delete':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE c_proveedores SET Activo=0 WHERE ID_Proveedor=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }
  case 'restore':{
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE c_proveedores SET Activo=1 WHERE ID_Proveedor=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }
  default:
    echo json_encode(['error'=>'Acción no válida']); exit;
}
