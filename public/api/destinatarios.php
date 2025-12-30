<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'dt_dest';

/* =========================
   Helpers
========================= */
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }

function validar_destinatario($data){
  $errs=[];
  $c = trim((string)($data['Cve_Clte'] ?? ''));             // <-- OJO: Cve_Clte
  $k = trim((string)($data['clave_destinatario'] ?? ''));
  if($c==='') $errs[]='Cve_Clte es obligatorio';
  if($k==='') $errs[]='clave_destinatario es obligatorio';
  return $errs;
}

/* =========================
   EXPORT CSV
========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout'; // layout | datos
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=destinatarios_'.$tipo.'.csv');

  $out = fopen('php://output','w');
  $headers = [
    'id_destinatario','Cve_Clte','clave_destinatario','razonsocial','direccion','colonia','postal','ciudad','estado',
    'contacto','telefono','email_destinatario','cve_vendedor','latitud','longitud','dir_principal','Activo'
  ];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',', $headers)." FROM c_destinatarios ORDER BY Cve_Clte, clave_destinatario";
    foreach($pdo->query($sql) as $row){
      fputcsv($out,$row);
    }
  }
  fclose($out);
  exit;
}

/* =========================
   IMPORT CSV (UPSERT por Cve_Clte + clave_destinatario)
========================= */
if($action==='import_csv'){
  if(!isset($_FILES['file'])){
    echo json_encode(['ok'=>0,'msg'=>'Archivo no recibido']); exit;
  }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['ok'=>0,'msg'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);
  $esperadas = [
    'id_destinatario','Cve_Clte','clave_destinatario','razonsocial','direccion','colonia','postal','ciudad','estado',
    'contacto','telefono','email_destinatario','cve_vendedor','latitud','longitud','dir_principal','Activo'
  ];
  if($headers !== $esperadas){
    echo json_encode(['ok'=>0,'msg'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]); exit;
  }

  $stFind = $pdo->prepare("SELECT id_destinatario FROM c_destinatarios WHERE Cve_Clte=? AND clave_destinatario=? LIMIT 1");
  $stIns  = $pdo->prepare("
    INSERT INTO c_destinatarios
    (Cve_Clte,clave_destinatario,razonsocial,direccion,colonia,postal,ciudad,estado,contacto,telefono,email_destinatario,cve_vendedor,latitud,longitud,dir_principal,Activo)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");
  $stUpd  = $pdo->prepare("
    UPDATE c_destinatarios SET
      razonsocial=?,direccion=?,colonia=?,postal=?,ciudad=?,estado=?,contacto=?,telefono=?,email_destinatario=?,cve_vendedor=?,latitud=?,longitud=?,dir_principal=?,Activo=?
    WHERE Cve_Clte=? AND clave_destinatario=? LIMIT 1
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
      $data = array_combine($esperadas, $r);
      $errs = validar_destinatario($data);
      if($errs){
        $rows_err++; $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs)]; continue;
      }

      $Cve_Clte = trim((string)$data['Cve_Clte']);
      $clave    = trim((string)$data['clave_destinatario']);

      $act = (string)($data['Activo'] ?? '1');
      $act = ($act==='0') ? '0' : '1';

      $stFind->execute([$Cve_Clte,$clave]);
      $existe = $stFind->fetchColumn();

      $vals = [
        s($data['razonsocial']), s($data['direccion']), s($data['colonia']), s($data['postal']),
        s($data['ciudad']), s($data['estado']), s($data['contacto']), s($data['telefono']),
        s($data['email_destinatario']), s($data['cve_vendedor']), s($data['latitud']), s($data['longitud']),
        ($data['dir_principal']===''? null : (int)$data['dir_principal']),
        $act
      ];

      if($existe){
        $stUpd->execute(array_merge($vals, [$Cve_Clte,$clave]));
      }else{
        $stIns->execute(array_merge([$Cve_Clte,$clave], $vals));
      }
      $rows_ok++;
    }

    $pdo->commit();
    echo json_encode(['ok'=>1,'rows_ok'=>$rows_ok,'rows_err'=>$rows_err,'errores'=>$errores]);
  }catch(Throwable $e){
    $pdo->rollBack();
    echo json_encode(['ok'=>0,'msg'=>$e->getMessage()]);
  }
  exit;
}

/* =========================
   LIST simple (debug rápido sin DataTables)
========================= */
if($action==='list'){
  $inactivos = (int)($_GET['inactivos'] ?? 0);
  $cve = trim((string)($_GET['Cve_Clte'] ?? ''));
  $q   = trim((string)($_GET['q'] ?? ''));

  $where = "WHERE 1=1";
  if(!$inactivos) $where .= " AND Activo='1'";
  if($cve!=='')   $where .= " AND Cve_Clte=:c";
  if($q!==''){
    $where .= " AND (Cve_Clte LIKE :q OR clave_destinatario LIKE :q OR razonsocial LIKE :q OR direccion LIKE :q)";
  }

  $sql = "SELECT * FROM c_destinatarios $where ORDER BY Cve_Clte, clave_destinatario LIMIT 25";
  $st = $pdo->prepare($sql);
  if($cve!=='') $st->bindValue(':c',$cve,PDO::PARAM_STR);
  if($q!=='')   $st->bindValue(':q',"%$q%",PDO::PARAM_STR);
  $st->execute();
  echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

/* =========================
   DataTables: dt_dest
========================= */
if($action==='dt_dest'){
  $draw   = (int)($_GET['draw'] ?? 1);
  $start  = (int)($_GET['start'] ?? 0);
  $length = (int)($_GET['length'] ?? 25);
  if($length<=0) $length = 25;

  $q = trim((string)($_GET['search']['value'] ?? ($_GET['q'] ?? '')));
  $inactivos = (int)($_GET['inactivos'] ?? 0);
  $Cve_Clte  = trim((string)($_GET['Cve_Clte'] ?? ''));

  $where = "WHERE 1=1";
  if(!$inactivos) $where .= " AND Activo='1'";
  if($Cve_Clte!=='') $where .= " AND Cve_Clte=:c";
  if($q!==''){
    $where .= " AND (
      Cve_Clte LIKE :q OR clave_destinatario LIKE :q OR razonsocial LIKE :q OR direccion LIKE :q
      OR colonia LIKE :q OR ciudad LIKE :q OR estado LIKE :q OR contacto LIKE :q OR telefono LIKE :q
      OR email_destinatario LIKE :q OR cve_vendedor LIKE :q
    )";
  }

  $total = (int)$pdo->query("SELECT COUNT(*) FROM c_destinatarios")->fetchColumn();

  $st = $pdo->prepare("SELECT COUNT(*) FROM c_destinatarios $where");
  if($Cve_Clte!=='') $st->bindValue(':c',$Cve_Clte,PDO::PARAM_STR);
  if($q!=='') $st->bindValue(':q',"%$q%",PDO::PARAM_STR);
  $st->execute();
  $filtered = (int)$st->fetchColumn();

  $sql = "
    SELECT id_destinatario,Cve_Clte,clave_destinatario,razonsocial,direccion,colonia,postal,ciudad,estado,
           contacto,telefono,email_destinatario,cve_vendedor,dir_principal,Activo
    FROM c_destinatarios
    $where
    ORDER BY Cve_Clte, clave_destinatario
    LIMIT :st, :ln
  ";
  $st = $pdo->prepare($sql);
  if($Cve_Clte!=='') $st->bindValue(':c',$Cve_Clte,PDO::PARAM_STR);
  if($q!=='') $st->bindValue(':q',"%$q%",PDO::PARAM_STR);
  $st->bindValue(':st',$start,PDO::PARAM_INT);
  $st->bindValue(':ln',$length,PDO::PARAM_INT);
  $st->execute();

  echo json_encode([
    'draw'=>$draw,
    'recordsTotal'=>$total,
    'recordsFiltered'=>$filtered,
    'data'=>$st->fetchAll(PDO::FETCH_ASSOC)
  ]);
  exit;
}

/* =========================
   CRUD
========================= */
switch($action){

  case 'get':{
    $id = (int)($_GET['id_destinatario'] ?? 0);
    if($id<=0){ echo json_encode(['ok'=>0,'msg'=>'id_destinatario requerido']); exit; }
    $st=$pdo->prepare("SELECT * FROM c_destinatarios WHERE id_destinatario=?");
    $st->execute([$id]);
    echo json_encode(['ok'=>1,'data'=>$st->fetch(PDO::FETCH_ASSOC)]);
    exit;
  }

  case 'create':{
    $errs = validar_destinatario($_POST);
    if($errs){ echo json_encode(['ok'=>0,'msg'=>'Validación','detalles'=>$errs]); exit; }

    $act = (string)($_POST['Activo'] ?? '1');
    $act = ($act==='0') ? '0' : '1';

    $st=$pdo->prepare("
      INSERT INTO c_destinatarios
      (Cve_Clte,razonsocial,direccion,colonia,postal,ciudad,estado,contacto,telefono,Activo,clave_destinatario,cve_vendedor,email_destinatario,latitud,longitud,dir_principal)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $st->execute([
      trim((string)$_POST['Cve_Clte']),
      s($_POST['razonsocial'] ?? null),
      s($_POST['direccion'] ?? null),
      s($_POST['colonia'] ?? null),
      s($_POST['postal'] ?? null),
      s($_POST['ciudad'] ?? null),
      s($_POST['estado'] ?? null),
      s($_POST['contacto'] ?? null),
      s($_POST['telefono'] ?? null),
      $act,
      trim((string)$_POST['clave_destinatario']),
      s($_POST['cve_vendedor'] ?? null),
      s($_POST['email_destinatario'] ?? null),
      s($_POST['latitud'] ?? null),
      s($_POST['longitud'] ?? null),
      ($_POST['dir_principal'] ?? '')==='' ? null : (int)$_POST['dir_principal'],
    ]);

    echo json_encode(['ok'=>1,'id'=>$pdo->lastInsertId()]);
    exit;
  }

  case 'update':{
    $id = (int)($_POST['id_destinatario'] ?? 0);
    if($id<=0){ echo json_encode(['ok'=>0,'msg'=>'id_destinatario requerido']); exit; }

    $errs = validar_destinatario($_POST);
    if($errs){ echo json_encode(['ok'=>0,'msg'=>'Validación','detalles'=>$errs]); exit; }

    $act = (string)($_POST['Activo'] ?? '1');
    $act = ($act==='0') ? '0' : '1';

    $st=$pdo->prepare("
      UPDATE c_destinatarios SET
        Cve_Clte=?, razonsocial=?, direccion=?, colonia=?, postal=?, ciudad=?, estado=?,
        contacto=?, telefono=?, Activo=?, clave_destinatario=?, cve_vendedor=?, email_destinatario=?, latitud=?, longitud=?, dir_principal=?
      WHERE id_destinatario=? LIMIT 1
    ");
    $st->execute([
      trim((string)$_POST['Cve_Clte']),
      s($_POST['razonsocial'] ?? null),
      s($_POST['direccion'] ?? null),
      s($_POST['colonia'] ?? null),
      s($_POST['postal'] ?? null),
      s($_POST['ciudad'] ?? null),
      s($_POST['estado'] ?? null),
      s($_POST['contacto'] ?? null),
      s($_POST['telefono'] ?? null),
      $act,
      trim((string)$_POST['clave_destinatario']),
      s($_POST['cve_vendedor'] ?? null),
      s($_POST['email_destinatario'] ?? null),
      s($_POST['latitud'] ?? null),
      s($_POST['longitud'] ?? null),
      ($_POST['dir_principal'] ?? '')==='' ? null : (int)$_POST['dir_principal'],
      $id
    ]);

    echo json_encode(['ok'=>1]);
    exit;
  }

  case 'delete':{
    $id = (int)($_POST['id_destinatario'] ?? 0);
    if($id<=0){ echo json_encode(['ok'=>0,'msg'=>'id_destinatario requerido']); exit; }
    $pdo->prepare("UPDATE c_destinatarios SET Activo='0' WHERE id_destinatario=?")->execute([$id]);
    echo json_encode(['ok'=>1]);
    exit;
  }

  case 'restore':{
    $id = (int)($_POST['id_destinatario'] ?? 0);
    if($id<=0){ echo json_encode(['ok'=>0,'msg'=>'id_destinatario requerido']); exit; }
    $pdo->prepare("UPDATE c_destinatarios SET Activo='1' WHERE id_destinatario=?")->execute([$id]);
    echo json_encode(['ok'=>1]);
    exit;
  }

  default:
    echo json_encode(['ok'=>0,'msg'=>'Acción no válida','action'=>$action]);
    exit;
}
