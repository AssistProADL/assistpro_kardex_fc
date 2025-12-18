<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function fnull($v){ return ($v==='' || $v===null) ? null : (float)$v; }
function dnull($v){ return ($v==='' || $v===null) ? null : (float)$v; }

function yn($v){ // para enum('S','N') y char(1)
  $v = strtoupper(trim((string)$v));
  if($v==='S' || $v==='N') return $v;
  if($v==='1') return 'S';
  if($v==='0') return 'N';
  return null;
}

function validar_obligatorios($data){
  $errs = [];

  // Obligatorios empresariales (mínimo viable):
  // cve_almac y CodigoCSD (BL)
  $alm = (string)($data['cve_almac'] ?? '');
  $bl  = trim((string)($data['CodigoCSD'] ?? ''));

  if(trim($alm)==='') $errs[] = 'cve_almac es obligatorio';
  if($bl==='') $errs[] = 'CodigoCSD (BL) es obligatorio';

  // Si viene Status/picking, validar formato
  $st = $data['Status'] ?? null;
  if($st!==null && $st!=='' && strlen((string)$st)>1) $errs[]='Status inválido';

  $pk = $data['picking'] ?? null;
  if($pk!==null && $pk!=='' && strlen((string)$pk)>1) $errs[]='picking inválido';

  return $errs;
}

/* =====================================================
 * EXPORT CSV (layout / datos) FULL
 * ===================================================== */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=ubicaciones_'.$tipo.'.csv');

  $out = fopen('php://output','w');

  $headers = [
    'cve_almac','cve_pasillo','cve_rack','cve_nivel','Seccion','Ubicacion',
    'CodigoCSD','claverp','TECNOLOGIA','Status','picking','orden_secuencia',
    'num_ancho','num_largo','num_alto','num_volumenDisp',
    'PesoMaximo','PesoOcupado',
    'Maneja_Cajas','Maneja_Piezas','Reabasto',
    'Activo','Tipo',
    'AcomodoMixto','AreaProduccion','AreaStagging','Ubicacion_CrossDocking','Staging_Pedidos','Ptl',
    'Maximo','Minimo','clasif_abc'
  ];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',', $headers)." FROM c_ubicacion WHERE IFNULL(Activo,1)=1 ORDER BY cve_almac, CodigoCSD";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }

  fclose($out);
  exit;
}

/* =====================================================
 * IMPORT CSV (UPSERT por CodigoCSD) + reporte
 * ===================================================== */
if($action==='import_csv'){
  header('Content-Type: application/json; charset=utf-8');

  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);

  $esperadas = [
    'cve_almac','cve_pasillo','cve_rack','cve_nivel','Seccion','Ubicacion',
    'CodigoCSD','claverp','TECNOLOGIA','Status','picking','orden_secuencia',
    'num_ancho','num_largo','num_alto','num_volumenDisp',
    'PesoMaximo','PesoOcupado',
    'Maneja_Cajas','Maneja_Piezas','Reabasto',
    'Activo','Tipo',
    'AcomodoMixto','AreaProduccion','AreaStagging','Ubicacion_CrossDocking','Staging_Pedidos','Ptl',
    'Maximo','Minimo','clasif_abc'
  ];

  if($headers !== $esperadas){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]);
    exit;
  }

  $stFind = $pdo->prepare("SELECT idy_ubica FROM c_ubicacion WHERE CodigoCSD=? LIMIT 1");

  $sqlIns = "
    INSERT INTO c_ubicacion
    (cve_almac,cve_pasillo,cve_rack,cve_nivel,num_ancho,num_largo,num_alto,num_volumenDisp,Status,picking,Seccion,Ubicacion,
     orden_secuencia,PesoMaximo,PesoOcupado,claverp,CodigoCSD,TECNOLOGIA,Maneja_Cajas,Maneja_Piezas,Reabasto,Activo,Tipo,
     AcomodoMixto,AreaProduccion,AreaStagging,Ubicacion_CrossDocking,Staging_Pedidos,Ptl,Maximo,Minimo,clasif_abc)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ";

  $sqlUpd = "
    UPDATE c_ubicacion SET
      cve_almac=?,cve_pasillo=?,cve_rack=?,cve_nivel=?,
      num_ancho=?,num_largo=?,num_alto=?,num_volumenDisp=?,
      Status=?,picking=?,Seccion=?,Ubicacion=?,
      orden_secuencia=?,PesoMaximo=?,PesoOcupado=?,claverp=?,TECNOLOGIA=?,
      Maneja_Cajas=?,Maneja_Piezas=?,Reabasto=?,Activo=?,Tipo=?,
      AcomodoMixto=?,AreaProduccion=?,AreaStagging=?,Ubicacion_CrossDocking=?,Staging_Pedidos=?,Ptl=?,
      Maximo=?,Minimo=?,clasif_abc=?
    WHERE CodigoCSD=?
    LIMIT 1
  ";

  $stIns = $pdo->prepare($sqlIns);
  $stUpd = $pdo->prepare($sqlUpd);

  $rows_ok=0; $rows_err=0; $errores=[];
  $pdo->beginTransaction();

  try{
    $linea=1;
    while(($r=fgetcsv($fh))!==false){
      $linea++;
      if(!$r || count($r)<32){
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

      $BL = trim((string)$data['CodigoCSD']);

      // Normalizaciones
      $val = [
        (int)$data['cve_almac'],
        s($data['cve_pasillo']), s($data['cve_rack']), s($data['cve_nivel']),
        dnull($data['num_ancho']), dnull($data['num_largo']), dnull($data['num_alto']), dnull($data['num_volumenDisp']),
        s($data['Status']), s($data['picking']),
        s($data['Seccion']), s($data['Ubicacion']),
        ($data['orden_secuencia']===''? null : (int)$data['orden_secuencia']),
        fnull($data['PesoMaximo']), fnull($data['PesoOcupado']),
        s($data['claverp']),
        $BL,
        s($data['TECNOLOGIA']),
        yn($data['Maneja_Cajas']), yn($data['Maneja_Piezas']), yn($data['Reabasto']),
        i1($data['Activo']),
        s($data['Tipo']),
        yn($data['AcomodoMixto']), yn($data['AreaProduccion']), yn($data['AreaStagging']),
        yn($data['Ubicacion_CrossDocking']) ?? 'N',
        yn($data['Staging_Pedidos']) ?? 'N',
        yn($data['Ptl']),
        ($data['Maximo']===''? null : (int)$data['Maximo']),
        ($data['Minimo']===''? null : (int)$data['Minimo']),
        s($data['clasif_abc'])
      ];

      $stFind->execute([$BL]);
      $existe = $stFind->fetchColumn();

      if($existe){
        // update sin CodigoCSD (va al final como where)
        $upd = $val;
        // remover CodigoCSD de la lista para UPDATE (posición 16 en INSERT, en $val[16])
        // UPDATE lleva todo excepto CodigoCSD (porque es WHERE), pero sí actualizamos claverp/TECNOLOGIA etc.
        // Armamos en el orden del SQL:
        $updParams = [
          $val[0],$val[1],$val[2],$val[3],
          $val[4],$val[5],$val[6],$val[7],
          $val[8],$val[9],$val[10],$val[11],
          $val[12],$val[13],$val[14],$val[15],$val[17],
          $val[18],$val[19],$val[20],$val[21],$val[22],
          $val[23],$val[24],$val[25],$val[26],$val[27],$val[28],
          $val[29],$val[30],$val[31],
          $BL
        ];
        $stUpd->execute($updParams);
      }else{
        $stIns->execute($val);
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
 * LIST + BUSCAR (q) + activos/inactivos
 * ===================================================== */
if($action==='list'){
  header('Content-Type: application/json; charset=utf-8');

  $inactivos = (int)($_GET['inactivos'] ?? 0);
  $q = trim((string)($_GET['q'] ?? ''));

  $where = "WHERE IFNULL(Activo,1)=:activo";
  if($q!==''){
    $where .= " AND (
      CodigoCSD LIKE :q OR claverp LIKE :q OR Seccion LIKE :q OR Ubicacion LIKE :q OR
      cve_pasillo LIKE :q OR cve_rack LIKE :q OR cve_nivel LIKE :q OR
      Status LIKE :q OR picking LIKE :q OR TECNOLOGIA LIKE :q OR Tipo LIKE :q OR clasif_abc LIKE :q OR
      CAST(cve_almac AS CHAR) LIKE :q
    )";
  }

  $sql = "
    SELECT
      idy_ubica,cve_almac,cve_pasillo,cve_rack,cve_nivel,Seccion,Ubicacion,CodigoCSD,
      Status,picking,Activo,Tipo,AcomodoMixto,AreaProduccion,AreaStagging,Ubicacion_CrossDocking,Staging_Pedidos,Ptl,
      Maximo,Minimo,clasif_abc,PesoMaximo,PesoOcupado,num_volumenDisp,TECNOLOGIA,claverp
    FROM c_ubicacion
    $where
    ORDER BY cve_almac, CodigoCSD
    LIMIT 25
  ";

  $st = $pdo->prepare($sql);
  $st->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
  if($q!=='') $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $st->execute();

  echo json_encode($st->fetchAll());
  exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE (FULL + VALIDACIÓN)
 * ===================================================== */
header('Content-Type: application/json; charset=utf-8');

switch($action){

  case 'get': {
    $id = $_GET['idy_ubica'] ?? null;
    if(!$id){ echo json_encode(['error'=>'idy_ubica requerido']); exit; }
    $st = $pdo->prepare("SELECT * FROM c_ubicacion WHERE idy_ubica=?");
    $st->execute([(int)$id]);
    echo json_encode($st->fetch());
    break;
  }

  case 'create': {
    $errs = validar_obligatorios($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $sql = "
      INSERT INTO c_ubicacion
      (cve_almac,cve_pasillo,cve_rack,cve_nivel,num_ancho,num_largo,num_alto,num_volumenDisp,Status,picking,Seccion,Ubicacion,
       orden_secuencia,PesoMaximo,PesoOcupado,claverp,CodigoCSD,TECNOLOGIA,Maneja_Cajas,Maneja_Piezas,Reabasto,Activo,Tipo,
       AcomodoMixto,AreaProduccion,AreaStagging,Ubicacion_CrossDocking,Staging_Pedidos,Ptl,Maximo,Minimo,clasif_abc)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ";

    $st = $pdo->prepare($sql);
    $st->execute([
      (int)$_POST['cve_almac'],
      s($_POST['cve_pasillo'] ?? null),
      s($_POST['cve_rack'] ?? null),
      s($_POST['cve_nivel'] ?? null),
      dnull($_POST['num_ancho'] ?? null),
      dnull($_POST['num_largo'] ?? null),
      dnull($_POST['num_alto'] ?? null),
      dnull($_POST['num_volumenDisp'] ?? null),
      s($_POST['Status'] ?? null),
      s($_POST['picking'] ?? null),
      s($_POST['Seccion'] ?? null),
      s($_POST['Ubicacion'] ?? null),
      ($_POST['orden_secuencia'] ?? '')==='' ? null : (int)$_POST['orden_secuencia'],
      fnull($_POST['PesoMaximo'] ?? null),
      fnull($_POST['PesoOcupado'] ?? null),
      s($_POST['claverp'] ?? null),
      trim((string)$_POST['CodigoCSD']),
      s($_POST['TECNOLOGIA'] ?? null),
      yn($_POST['Maneja_Cajas'] ?? null),
      yn($_POST['Maneja_Piezas'] ?? null),
      yn($_POST['Reabasto'] ?? null),
      i1($_POST['Activo'] ?? 1),
      s($_POST['Tipo'] ?? null),
      yn($_POST['AcomodoMixto'] ?? null),
      yn($_POST['AreaProduccion'] ?? null),
      yn($_POST['AreaStagging'] ?? null),
      yn($_POST['Ubicacion_CrossDocking'] ?? 'N') ?? 'N',
      yn($_POST['Staging_Pedidos'] ?? 'N') ?? 'N',
      yn($_POST['Ptl'] ?? null),
      ($_POST['Maximo'] ?? '')==='' ? null : (int)$_POST['Maximo'],
      ($_POST['Minimo'] ?? '')==='' ? null : (int)$_POST['Minimo'],
      s($_POST['clasif_abc'] ?? null)
    ]);

    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
    break;
  }

  case 'update': {
    $id = $_POST['idy_ubica'] ?? null;
    if(!$id){ echo json_encode(['error'=>'idy_ubica requerido']); exit; }

    $errs = validar_obligatorios($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $sql = "
      UPDATE c_ubicacion SET
        cve_almac=?,cve_pasillo=?,cve_rack=?,cve_nivel=?,
        num_ancho=?,num_largo=?,num_alto=?,num_volumenDisp=?,
        Status=?,picking=?,Seccion=?,Ubicacion=?,
        orden_secuencia=?,PesoMaximo=?,PesoOcupado=?,claverp=?,CodigoCSD=?,TECNOLOGIA=?,
        Maneja_Cajas=?,Maneja_Piezas=?,Reabasto=?,Activo=?,Tipo=?,
        AcomodoMixto=?,AreaProduccion=?,AreaStagging=?,Ubicacion_CrossDocking=?,Staging_Pedidos=?,Ptl=?,
        Maximo=?,Minimo=?,clasif_abc=?
      WHERE idy_ubica=?
      LIMIT 1
    ";

    $st = $pdo->prepare($sql);
    $st->execute([
      (int)$_POST['cve_almac'],
      s($_POST['cve_pasillo'] ?? null),
      s($_POST['cve_rack'] ?? null),
      s($_POST['cve_nivel'] ?? null),
      dnull($_POST['num_ancho'] ?? null),
      dnull($_POST['num_largo'] ?? null),
      dnull($_POST['num_alto'] ?? null),
      dnull($_POST['num_volumenDisp'] ?? null),
      s($_POST['Status'] ?? null),
      s($_POST['picking'] ?? null),
      s($_POST['Seccion'] ?? null),
      s($_POST['Ubicacion'] ?? null),
      ($_POST['orden_secuencia'] ?? '')==='' ? null : (int)$_POST['orden_secuencia'],
      fnull($_POST['PesoMaximo'] ?? null),
      fnull($_POST['PesoOcupado'] ?? null),
      s($_POST['claverp'] ?? null),
      trim((string)$_POST['CodigoCSD']),
      s($_POST['TECNOLOGIA'] ?? null),
      yn($_POST['Maneja_Cajas'] ?? null),
      yn($_POST['Maneja_Piezas'] ?? null),
      yn($_POST['Reabasto'] ?? null),
      i1($_POST['Activo'] ?? 1),
      s($_POST['Tipo'] ?? null),
      yn($_POST['AcomodoMixto'] ?? null),
      yn($_POST['AreaProduccion'] ?? null),
      yn($_POST['AreaStagging'] ?? null),
      yn($_POST['Ubicacion_CrossDocking'] ?? 'N') ?? 'N',
      yn($_POST['Staging_Pedidos'] ?? 'N') ?? 'N',
      yn($_POST['Ptl'] ?? null),
      ($_POST['Maximo'] ?? '')==='' ? null : (int)$_POST['Maximo'],
      ($_POST['Minimo'] ?? '')==='' ? null : (int)$_POST['Minimo'],
      s($_POST['clasif_abc'] ?? null),
      (int)$id
    ]);

    echo json_encode(['success'=>true]);
    break;
  }

  case 'delete': {
    $id = $_POST['idy_ubica'] ?? null;
    if(!$id){ echo json_encode(['error'=>'idy_ubica requerido']); exit; }
    $pdo->prepare("UPDATE c_ubicacion SET Activo=0 WHERE idy_ubica=?")->execute([(int)$id]);
    echo json_encode(['success'=>true]);
    break;
  }

  case 'restore': {
    $id = $_POST['idy_ubica'] ?? null;
    if(!$id){ echo json_encode(['error'=>'idy_ubica requerido']); exit; }
    $pdo->prepare("UPDATE c_ubicacion SET Activo=1 WHERE idy_ubica=?")->execute([(int)$id]);
    echo json_encode(['success'=>true]);
    break;
  }

  default:
    echo json_encode(['error'=>'Acción no válida']);
}
