<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jerr($msg,$det=null){ echo json_encode(['error'=>$msg,'detalles'=>$det],JSON_UNESCAPED_UNICODE); exit; }
function clean($v){ return trim((string)$v); }
function norm01($v,$def='1'){ $v=clean($v); if($v==='') return $def; return ($v==='1')?'1':'0'; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try{
  if($action==='list'){
    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,'1')='1'";
    if($q!==''){
      $where[]="(empresa_id LIKE :q OR id LIKE :q OR clave LIKE :q OR nombre LIKE :q OR BL LIKE :q)";
      $p[':q']="%$q%";
    }

    $sql="SELECT empresa_id, id, clave, nombre, cve_cia, BL, telefono, correo, Activo
          FROM c_almacenp";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" ORDER BY IFNULL(Activo,'1') DESC, nombre ASC LIMIT 3000";

    echo json_encode(['rows'=>db_all($sql,$p)],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='get'){
    $emp = clean($_GET['empresa_id'] ?? '');
    $id = clean($_GET['id'] ?? '');
    if($emp==='' || $id==='') jerr('Llave inválida (empresa_id + id)');
    $row = db_one("SELECT * FROM c_almacenp WHERE empresa_id=:e AND id=:i LIMIT 1", [':e'=>$emp,':i'=>$id]);
    if(!$row) jerr('No existe el registro');
    echo json_encode($row,JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='create' || $action==='update'){
    $k_emp = clean($_POST['k_empresa_id'] ?? '');
    $k_id  = clean($_POST['k_id'] ?? '');

    $empresa_id = clean($_POST['empresa_id'] ?? '');
    $id = clean($_POST['id'] ?? '');
    $clave = strtoupper(clean($_POST['clave'] ?? ''));
    $nombre = clean($_POST['nombre'] ?? '');

    $det=[];
    if($empresa_id==='') $det[]='empresa_id es obligatorio.';
    if($id==='') $det[]='id es obligatorio.';
    if($clave==='') $det[]='clave es obligatoria.';
    if($nombre==='') $det[]='nombre es obligatorio.';
    $correo = clean($_POST['correo'] ?? '');
    if($correo!=='' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/',$correo)) $det[]='correo no tiene formato válido.';
    if($det) jerr('Validación',$det);

    $data = [
      'empresa_id'=>$empresa_id,
      'id'=>$id,
      'clave'=>$clave,
      'nombre'=>$nombre,
      'rut'=>clean($_POST['rut'] ?? ''),
      'codigopostal'=>clean($_POST['codigopostal'] ?? ''),
      'direccion'=>clean($_POST['direccion'] ?? ''),
      'telefono'=>clean($_POST['telefono'] ?? ''),
      'contacto'=>clean($_POST['contacto'] ?? ''),
      'correo'=>$correo,
      'comentarios'=>clean($_POST['comentarios'] ?? ''),
      'Activo'=>norm01($_POST['Activo'] ?? '1','1'),
      'distrito'=>clean($_POST['distrito'] ?? ''),
      'cve_talmacen'=>clean($_POST['cve_talmacen'] ?? ''),
      'No_Licencias'=>clean($_POST['No_Licencias'] ?? ''),
      'cve_cia'=>clean($_POST['cve_cia'] ?? ''),
      'BL'=>clean($_POST['BL'] ?? ''),
      'BL_Pasillo'=>clean($_POST['BL_Pasillo'] ?? ''),
      'BL_Rack'=>clean($_POST['BL_Rack'] ?? ''),
      'BL_Nivel'=>clean($_POST['BL_Nivel'] ?? ''),
      'BL_Seccion'=>clean($_POST['BL_Seccion'] ?? ''),
      'BL_Posicion'=>clean($_POST['BL_Posicion'] ?? ''),
      'longitud'=>clean($_POST['longitud'] ?? ''),
      'latitud'=>clean($_POST['latitud'] ?? ''),
      'interno'=>clean($_POST['interno'] ?? ''),
      'tipolp_traslado'=>clean($_POST['tipolp_traslado'] ?? ''),
    ];

    db_tx(function() use($action,$k_emp,$k_id,$data,$empresa_id,$id){
      if($action==='create'){
        $ex=db_val("SELECT 1 FROM c_almacenp WHERE empresa_id=:e AND id=:i LIMIT 1", [':e'=>$empresa_id,':i'=>$id]);
        if($ex) throw new Exception("Ya existe (empresa_id=$empresa_id, id=$id).");

        $cols=array_keys($data);
        $ins="INSERT INTO c_almacenp (".implode(',',$cols).") VALUES (:".implode(',:',$cols).")";
        $p=[]; foreach($data as $k=>$v) $p[":$k"]=$v;
        dbq($ins,$p);
      }else{
        if($k_emp==='' || $k_id==='') throw new Exception("Llave original inválida (k_empresa_id+k_id).");
        // update por llave original (permite corregir empresa_id/id)
        $set=[]; $p=[':ke'=>$k_emp,':ki'=>$k_id];
        foreach($data as $k=>$v){ $set[]="$k=:$k"; $p[":$k"]=$v; }
        dbq("UPDATE c_almacenp SET ".implode(',',$set)." WHERE empresa_id=:ke AND id=:ki", $p);
      }
    });

    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='delete' || $action==='restore'){
    $emp=clean($_POST['empresa_id'] ?? '');
    $id=clean($_POST['id'] ?? '');
    if($emp==='' || $id==='') jerr('Llave inválida (empresa_id + id)');
    $val=($action==='delete')?'0':'1';
    dbq("UPDATE c_almacenp SET Activo=:v WHERE empresa_id=:e AND id=:i", [':v'=>$val,':e'=>$emp,':i'=>$id]);
    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='export'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=almacenes_export.csv');

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,'1')='1'";
    if($q!==''){
      $where[]="(empresa_id LIKE :q OR id LIKE :q OR clave LIKE :q OR nombre LIKE :q OR BL LIKE :q)";
      $p[':q']="%$q%";
    }
    $sql="SELECT empresa_id,id,clave,nombre,rut,codigopostal,direccion,telefono,contacto,correo,comentarios,Activo,distrito,cve_talmacen,No_Licencias,cve_cia,BL
          FROM c_almacenp";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $rows=db_all($sql,$p);

    $out=fopen('php://output','w');
    fputcsv($out, array_keys($rows ? $rows[0] : ['empresa_id'=>'','id'=>'','clave'=>'','nombre'=>'','rut'=>'','codigopostal'=>'','direccion'=>'','telefono'=>'','contacto'=>'','correo'=>'','comentarios'=>'','Activo'=>'1','distrito'=>'','cve_talmacen'=>'','No_Licencias'=>'','cve_cia'=>'','BL'=>'']));
    foreach($rows as $r) fputcsv($out,$r);
    fclose($out); exit;
  }

  if($action==='layout'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=almacenes_layout.csv');
    $out=fopen('php://output','w');
    fputcsv($out, ['empresa_id','id','clave','nombre','cve_cia','BL','direccion','codigopostal','telefono','contacto','correo','comentarios','Activo']);
    fputcsv($out, ['1','10','ALM01','ALMACEN DEMO','1','BL-DEFAULT','CALLE 1','64000','8180000000','CONTACTO','correo@dominio.com','OBS','1']);
    fclose($out); exit;
  }

  if($action==='import_preview' || $action==='import'){
    if(!isset($_FILES['csv'])) jerr('No se recibió CSV');
    $tmp=$_FILES['csv']['tmp_name'];
    if(!is_uploaded_file($tmp)) jerr('Archivo inválido');

    $fh=fopen($tmp,'r'); if(!$fh) jerr('No se pudo leer');
    $header=fgetcsv($fh); if(!$header) jerr('CSV vacío');
    $header=array_map('trim',$header);

    $expected=['empresa_id','id','clave','nombre','cve_cia','BL','direccion','codigopostal','telefono','contacto','correo','comentarios','Activo'];
    $diff=array_diff($expected,$header);
    if($diff) jerr('Layout incorrecto. Faltan columnas: '.implode(', ',$diff));

    if($action==='import_preview'){
      $rows=[]; $line=1; $warn=[];
      while(($r=fgetcsv($fh))!==false && count($rows)<50){
        $line++;
        $row=array_combine($header,$r);
        $rows[]=$row;
        if(trim($row['empresa_id']??'')==='' || trim($row['id']??'')==='' || trim($row['clave']??'')==='' || trim($row['nombre']??'')===''){
          $warn[]="Línea $line: empresa_id, id, clave, nombre obligatorios.";
        }
      }
      fclose($fh);
      $txt="Preview (máx 50 filas)\n".implode(',',$header)."\n";
      foreach($rows as $rw){
        $ln=[]; foreach($header as $h) $ln[]=$rw[$h]??'';
        $txt.=implode(',',$ln)."\n";
      }
      echo json_encode(['ok'=>1,'preview_text'=>$txt,'warnings'=>$warn],JSON_UNESCAPED_UNICODE);
      exit;
    }

    // import real
    rewind($fh);
    fgetcsv($fh); // header

    $inserted=0; $updated=0; $errors=0; $errList=[]; $line=1;

    db_tx(function() use($fh,$header,&$inserted,&$updated,&$errors,&$errList,&$line){
      while(($r=fgetcsv($fh))!==false){
        $line++;
        $row=array_combine($header,$r);

        $empresa_id=trim($row['empresa_id']??'');
        $id=trim($row['id']??'');
        $clave=strtoupper(trim($row['clave']??''));
        $nombre=trim($row['nombre']??'');
        if($empresa_id===''||$id===''||$clave===''||$nombre===''){
          $errors++; $errList[]="Línea $line: empresa_id, id, clave, nombre obligatorios.";
          continue;
        }

        $correo=trim($row['correo']??'');
        if($correo!=='' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/',$correo)){
          $errors++; $errList[]="Línea $line: correo inválido.";
          continue;
        }

        $data=[
          'empresa_id'=>$empresa_id,
          'id'=>$id,
          'clave'=>$clave,
          'nombre'=>$nombre,
          'cve_cia'=>trim($row['cve_cia']??''),
          'BL'=>trim($row['BL']??''),
          'direccion'=>trim($row['direccion']??''),
          'codigopostal'=>trim($row['codigopostal']??''),
          'telefono'=>trim($row['telefono']??''),
          'contacto'=>trim($row['contacto']??''),
          'correo'=>$correo,
          'comentarios'=>trim($row['comentarios']??''),
          'Activo'=>(trim($row['Activo']??'1')==='1'?'1':'0'),
        ];

        $ex=db_val("SELECT 1 FROM c_almacenp WHERE empresa_id=:e AND id=:i LIMIT 1", [':e'=>$empresa_id,':i'=>$id]);
        if($ex){
          $set=[]; $p=[':e'=>$empresa_id,':i'=>$id];
          foreach($data as $k=>$v){ $set[]="$k=:$k"; $p[":$k"]=$v; }
          dbq("UPDATE c_almacenp SET ".implode(',',$set)." WHERE empresa_id=:e AND id=:i",$p);
          $updated++;
        }else{
          $cols=array_keys($data);
          $ins="INSERT INTO c_almacenp (".implode(',',$cols).") VALUES (:".implode(',:',$cols).")";
          $p=[]; foreach($data as $k=>$v) $p[":$k"]=$v;
          dbq($ins,$p);
          $inserted++;
        }
      }
    });

    fclose($fh);
    echo json_encode(['ok'=>1,'inserted'=>$inserted,'updated'=>$updated,'errors'=>$errors,'detalles'=>$errList],JSON_UNESCAPED_UNICODE);
    exit;
  }

  jerr('Acción no soportada: '.$action);

}catch(Throwable $e){
  jerr('Error: '.$e->getMessage());
}
