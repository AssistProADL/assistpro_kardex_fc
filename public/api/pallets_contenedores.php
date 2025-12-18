<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function d0($v){ return ($v==='' || $v===null) ? 0.0 : (float)$v; }
function dnull($v){ return ($v==='' || $v===null) ? null : (float)$v; }

function validar_obligatorios($data){
  $errs=[];
  $alm = trim((string)($data['cve_almac'] ?? ''));
  $clv = trim((string)($data['Clave_Contenedor'] ?? ''));
  if($alm==='') $errs[]='cve_almac es obligatorio';
  if($clv==='') $errs[]='Clave_Contenedor es obligatorio';
  return $errs;
}

function table_exists($pdo, $name){
  $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
  $st->execute([$name]);
  return (int)$st->fetchColumn() > 0;
}

/* =====================================================
 * EXPORT CSV (layout / datos)
 * ===================================================== */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=charolas_'.$tipo.'.csv');

  $out = fopen('php://output','w');

  $headers = [
    'cve_almac','Clave_Contenedor','descripcion','Permanente','Pedido','sufijo','tipo','Activo',
    'alto','ancho','fondo','peso','pesomax','capavol','Costo','CveLP','TipoGen'
  ];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',', $headers)." FROM c_charolas WHERE IFNULL(Activo,1)=1 ORDER BY cve_almac, Clave_Contenedor";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }

  fclose($out);
  exit;
}

/* =====================================================
 * IMPORT CSV (UPSERT por Clave_Contenedor)
 * ===================================================== */
if($action==='import_csv'){

  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }

  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);

  $esperadas = [
    'cve_almac','Clave_Contenedor','descripcion','Permanente','Pedido','sufijo','tipo','Activo',
    'alto','ancho','fondo','peso','pesomax','capavol','Costo','CveLP','TipoGen'
  ];

  if($headers !== $esperadas){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]);
    exit;
  }

  $stFind = $pdo->prepare("SELECT IDContenedor FROM c_charolas WHERE Clave_Contenedor=? LIMIT 1");

  $stIns = $pdo->prepare("
    INSERT INTO c_charolas
    (cve_almac,Clave_Contenedor,descripcion,Permanente,Pedido,sufijo,tipo,Activo,alto,ancho,fondo,peso,pesomax,capavol,Costo,CveLP,TipoGen)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");

  $stUpd = $pdo->prepare("
    UPDATE c_charolas SET
      cve_almac=?,descripcion=?,Permanente=?,Pedido=?,sufijo=?,tipo=?,Activo=?,
      alto=?,ancho=?,fondo=?,peso=?,pesomax=?,capavol=?,Costo=?,CveLP=?,TipoGen=?
    WHERE Clave_Contenedor=?
    LIMIT 1
  ");

  $rows_ok=0; $rows_err=0; $errores=[];
  $pdo->beginTransaction();

  try{
    $linea=1;
    while(($r=fgetcsv($fh))!==false){
      $linea++;
      if(!$r || count($r)<17){
        $rows_err++;
        $errores[]=['fila'=>$linea,'motivo'=>'Fila incompleta','data'=>$r];
        continue;
      }

      $data = array_combine($esperadas, $r);
      $errs = validar_obligatorios($data);
      if($errs){
        $rows_err++;
        $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs),'data'=>$r];
        continue;
      }

      $cve_almac = (int)$data['cve_almac'];
      $Clave_Contenedor = trim((string)$data['Clave_Contenedor']);

      $descripcion = s($data['descripcion']);
      $Permanente  = i0($data['Permanente']);
      $Pedido      = s($data['Pedido']);
      $sufijo      = ($data['sufijo']===''? null : (int)$data['sufijo']);
      $tipo        = s($data['tipo']);
      $Activo      = i1($data['Activo']);

      $alto  = ($data['alto']===''? null : (int)$data['alto']);
      $ancho = ($data['ancho']===''? null : (int)$data['ancho']);
      $fondo = ($data['fondo']===''? null : (int)$data['fondo']);

      $peso   = dnull($data['peso']);
      $pesomax= dnull($data['pesomax']);
      $capavol= dnull($data['capavol']);
      $Costo  = dnull($data['Costo']);

      $CveLP  = s($data['CveLP']);
      $TipoGen= ($data['TipoGen']===''? null : (int)$data['TipoGen']);

      $stFind->execute([$Clave_Contenedor]);
      $existe = $stFind->fetchColumn();

      if($existe){
        $stUpd->execute([
          $cve_almac,$descripcion,$Permanente,$Pedido,$sufijo,$tipo,$Activo,
          $alto,$ancho,$fondo,$peso,$pesomax,$capavol,$Costo,$CveLP,$TipoGen,
          $Clave_Contenedor
        ]);
      }else{
        $stIns->execute([
          $cve_almac,$Clave_Contenedor,$descripcion,$Permanente,$Pedido,$sufijo,$tipo,$Activo,
          $alto,$ancho,$fondo,$peso,$pesomax,$capavol,$Costo,$CveLP,$TipoGen
        ]);
      }

      $rows_ok++;
    }

    $pdo->commit();
    echo json_encode(['success'=>true,'rows_ok'=>$rows_ok,'rows_err'=>$rows_err,'errores'=>$errores]);
  }catch(Throwable $e){
    $pdo->rollBack();
    echo json_encode(['error'=>$e->getMessage()]);
  }
  exit;
}

/* =====================================================
 * LIST + SEARCH
 * ===================================================== */
if($action==='list'){
  $inactivos = (int)($_GET['inactivos'] ?? 0);
  $alm = (int)($_GET['cve_almac'] ?? 0);
  $q = trim((string)($_GET['q'] ?? ''));

  $where = "WHERE IFNULL(Activo,1)=:activo";
  if($alm>0) $where .= " AND cve_almac=:alm";
  if($q!==''){
    $where .= " AND (
      Clave_Contenedor LIKE :q OR descripcion LIKE :q OR Pedido LIKE :q OR tipo LIKE :q OR
      CveLP LIKE :q OR CAST(sufijo AS CHAR) LIKE :q
    )";
  }

  $sql = "
    SELECT
      IDContenedor,cve_almac,Clave_Contenedor,descripcion,Permanente,Pedido,sufijo,tipo,Activo,
      alto,ancho,fondo,peso,pesomax,capavol,Costo,CveLP,TipoGen
    FROM c_charolas
    $where
    ORDER BY cve_almac, Clave_Contenedor
    LIMIT 25
  ";

  $st=$pdo->prepare($sql);
  $st->bindValue(':activo',$inactivos?0:1,PDO::PARAM_INT);
  if($alm>0) $st->bindValue(':alm',$alm,PDO::PARAM_INT);
  if($q!=='') $st->bindValue(':q',"%$q%",PDO::PARAM_STR);
  $st->execute();

  echo json_encode($st->fetchAll());
  exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE
 * ===================================================== */
switch($action){

  case 'get':{
    $id = $_GET['IDContenedor'] ?? null;
    if(!$id){ echo json_encode(['error'=>'IDContenedor requerido']); exit; }
    $st=$pdo->prepare("SELECT * FROM c_charolas WHERE IDContenedor=?");
    $st->execute([(int)$id]);
    echo json_encode($st->fetch());
    break;
  }

  case 'create':{
    $errs = validar_obligatorios($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $st=$pdo->prepare("
      INSERT INTO c_charolas
      (cve_almac,Clave_Contenedor,descripcion,Permanente,Pedido,sufijo,tipo,Activo,
       alto,ancho,fondo,peso,pesomax,capavol,Costo,CveLP,TipoGen)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $st->execute([
      (int)$_POST['cve_almac'],
      trim((string)$_POST['Clave_Contenedor']),
      s($_POST['descripcion'] ?? null),
      i0($_POST['Permanente'] ?? 0),
      s($_POST['Pedido'] ?? null),
      ($_POST['sufijo'] ?? '')==='' ? null : (int)$_POST['sufijo'],
      s($_POST['tipo'] ?? null),
      i1($_POST['Activo'] ?? 1),
      ($_POST['alto'] ?? '')==='' ? null : (int)$_POST['alto'],
      ($_POST['ancho'] ?? '')==='' ? null : (int)$_POST['ancho'],
      ($_POST['fondo'] ?? '')==='' ? null : (int)$_POST['fondo'],
      dnull($_POST['peso'] ?? null),
      dnull($_POST['pesomax'] ?? null),
      dnull($_POST['capavol'] ?? null),
      dnull($_POST['Costo'] ?? null),
      s($_POST['CveLP'] ?? null),
      ($_POST['TipoGen'] ?? '')==='' ? null : (int)$_POST['TipoGen']
    ]);

    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
    break;
  }

  case 'update':{
    $id = $_POST['IDContenedor'] ?? null;
    if(!$id){ echo json_encode(['error'=>'IDContenedor requerido']); exit; }

    $errs = validar_obligatorios($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $st=$pdo->prepare("
      UPDATE c_charolas SET
        cve_almac=?,Clave_Contenedor=?,descripcion=?,Permanente=?,Pedido=?,sufijo=?,tipo=?,Activo=?,
        alto=?,ancho=?,fondo=?,peso=?,pesomax=?,capavol=?,Costo=?,CveLP=?,TipoGen=?
      WHERE IDContenedor=?
      LIMIT 1
    ");

    $st->execute([
      (int)$_POST['cve_almac'],
      trim((string)$_POST['Clave_Contenedor']),
      s($_POST['descripcion'] ?? null),
      i0($_POST['Permanente'] ?? 0),
      s($_POST['Pedido'] ?? null),
      ($_POST['sufijo'] ?? '')==='' ? null : (int)$_POST['sufijo'],
      s($_POST['tipo'] ?? null),
      i1($_POST['Activo'] ?? 1),
      ($_POST['alto'] ?? '')==='' ? null : (int)$_POST['alto'],
      ($_POST['ancho'] ?? '')==='' ? null : (int)$_POST['ancho'],
      ($_POST['fondo'] ?? '')==='' ? null : (int)$_POST['fondo'],
      dnull($_POST['peso'] ?? null),
      dnull($_POST['pesomax'] ?? null),
      dnull($_POST['capavol'] ?? null),
      dnull($_POST['Costo'] ?? null),
      s($_POST['CveLP'] ?? null),
      ($_POST['TipoGen'] ?? '')==='' ? null : (int)$_POST['TipoGen'],
      (int)$id
    ]);

    echo json_encode(['success'=>true]);
    break;
  }

  case 'delete':{
    $id = $_POST['IDContenedor'] ?? null;
    if(!$id){ echo json_encode(['error'=>'IDContenedor requerido']); exit; }
    $pdo->prepare("UPDATE c_charolas SET Activo=0 WHERE IDContenedor=?")->execute([(int)$id]);
    echo json_encode(['success'=>true]);
    break;
  }

  case 'restore':{
    $id = $_POST['IDContenedor'] ?? null;
    if(!$id){ echo json_encode(['error'=>'IDContenedor requerido']); exit; }
    $pdo->prepare("UPDATE c_charolas SET Activo=1 WHERE IDContenedor=?")->execute([(int)$id]);
    echo json_encode(['success'=>true]);
    break;
  }

  // Drill-down LP (si existe tabla LP)
  case 'lp_detalle':{
    $lp = trim((string)($_GET['CveLP'] ?? ''));
    if($lp===''){ echo json_encode(['error'=>'CveLP requerido']); exit; }

    if(!table_exists($pdo,'t_license_plate') || !table_exists($pdo,'t_license_plate_items')){
      echo json_encode(['modo'=>'sin_tabla','lp'=>$lp,'items'=>[]]);
      exit;
    }

    $sql = "
      SELECT
        lp.id AS lp_id,
        lp.lp_code AS lp,
        lpi.cve_articulo,
        lpi.cve_lote,
        lpi.cantidad
      FROM t_license_plate lp
      JOIN t_license_plate_items lpi ON lpi.lp_id = lp.id
      WHERE lp.lp_code = ?
      ORDER BY lpi.cve_articulo
    ";
    $st=$pdo->prepare($sql);
    $st->execute([$lp]);

    echo json_encode(['modo'=>'license_plate','lp'=>$lp,'items'=>$st->fetchAll()]);
    break;
  }

  default:
    echo json_encode(['error'=>'Acción no válida']);
}
