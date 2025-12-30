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
      $where[]="(empresa_id LIKE :q OR ID_PERFIL LIKE :q OR PER_NOMBRE LIKE :q OR cve_cia LIKE :q)";
      $p[':q']="%$q%";
    }
    $sql="SELECT empresa_id, ID_PERFIL, PER_NOMBRE, cve_cia, Activo FROM t_perfilesusuarios";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" ORDER BY IFNULL(Activo,'1') DESC, PER_NOMBRE ASC LIMIT 3000";
    echo json_encode(['rows'=>db_all($sql,$p)],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='get'){
    $emp=clean($_GET['empresa_id'] ?? '');
    $pid=clean($_GET['ID_PERFIL'] ?? '');
    if($emp===''||$pid==='') jerr('Llave inválida (empresa_id + ID_PERFIL)');
    $row=db_one("SELECT * FROM t_perfilesusuarios WHERE empresa_id=:e AND ID_PERFIL=:p LIMIT 1", [':e'=>$emp,':p'=>$pid]);
    if(!$row) jerr('No existe el registro');
    echo json_encode($row,JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='create' || $action==='update'){
    $k_emp=clean($_POST['k_empresa_id'] ?? '');
    $k_pid=clean($_POST['k_ID_PERFIL'] ?? '');

    $empresa_id=clean($_POST['empresa_id'] ?? '');
    $ID_PERFIL=clean($_POST['ID_PERFIL'] ?? '');
    $PER_NOMBRE=clean($_POST['PER_NOMBRE'] ?? '');
    $cve_cia=clean($_POST['cve_cia'] ?? '');
    $Activo=norm01($_POST['Activo'] ?? '1','1');

    $det=[];
    if($empresa_id==='') $det[]='empresa_id obligatorio.';
    if($ID_PERFIL==='') $det[]='ID_PERFIL obligatorio.';
    if($PER_NOMBRE==='') $det[]='PER_NOMBRE obligatorio.';
    if($det) jerr('Validación',$det);

    db_tx(function() use($action,$k_emp,$k_pid,$empresa_id,$ID_PERFIL,$PER_NOMBRE,$cve_cia,$Activo){
      if($action==='create'){
        $ex=db_val("SELECT 1 FROM t_perfilesusuarios WHERE empresa_id=:e AND ID_PERFIL=:p LIMIT 1", [':e'=>$empresa_id,':p'=>$ID_PERFIL]);
        if($ex) throw new Exception("Ya existe (empresa_id=$empresa_id, ID_PERFIL=$ID_PERFIL).");
        dbq("INSERT INTO t_perfilesusuarios (empresa_id, ID_PERFIL, PER_NOMBRE, cve_cia, Activo)
             VALUES (:e,:p,:n,:c,:a)", [':e'=>$empresa_id,':p'=>$ID_PERFIL,':n'=>$PER_NOMBRE,':c'=>$cve_cia,':a'=>$Activo]);
      }else{
        if($k_emp===''||$k_pid==='') throw new Exception("Llave original inválida (k_empresa_id+k_ID_PERFIL).");
        dbq("UPDATE t_perfilesusuarios
             SET empresa_id=:e, ID_PERFIL=:p, PER_NOMBRE=:n, cve_cia=:c, Activo=:a
             WHERE empresa_id=:ke AND ID_PERFIL=:kp",
             [':e'=>$empresa_id,':p'=>$ID_PERFIL,':n'=>$PER_NOMBRE,':c'=>$cve_cia,':a'=>$Activo,':ke'=>$k_emp,':kp'=>$k_pid]);
      }
    });

    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='delete' || $action==='restore'){
    $emp=clean($_POST['empresa_id'] ?? '');
    $pid=clean($_POST['ID_PERFIL'] ?? '');
    if($emp===''||$pid==='') jerr('Llave inválida (empresa_id + ID_PERFIL)');
    $val=($action==='delete')?'0':'1';
    dbq("UPDATE t_perfilesusuarios SET Activo=:v WHERE empresa_id=:e AND ID_PERFIL=:p", [':v'=>$val,':e'=>$emp,':p'=>$pid]);
    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  if($action==='export'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=perfiles_export.csv');

    $q=clean($_GET['q'] ?? '');
    $inactivos=(int)($_GET['inactivos'] ?? 0);
    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,'1')='1'";
    if($q!==''){
      $where[]="(empresa_id LIKE :q OR ID_PERFIL LIKE :q OR PER_NOMBRE LIKE :q OR cve_cia LIKE :q)";
      $p[':q']="%$q%";
    }
    $sql="SELECT empresa_id, ID_PERFIL, PER_NOMBRE, cve_cia, Activo FROM t_perfilesusuarios";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $rows=db_all($sql,$p);

    $out=fopen('php://output','w');
    fputcsv($out, array_keys($rows ? $rows[0] : ['empresa_id'=>'','ID_PERFIL'=>'','PER_NOMBRE'=>'','cve_cia'=>'','Activo'=>'1']));
    foreach($rows as $r) fputcsv($out,$r);
    fclose($out); exit;
  }

  if($action==='layout'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=perfiles_layout.csv');
    $out=fopen('php://output','w');
    fputcsv($out, ['empresa_id','ID_PERFIL','PER_NOMBRE','cve_cia','Activo']);
    fputcsv($out, ['1','ADMIN','ADMINISTRADOR','1','1']);
    fclose($out); exit;
  }

  if($action==='import_preview' || $action==='import'){
    if(!isset($_FILES['csv'])) jerr('No se recibió CSV');
    $tmp=$_FILES['csv']['tmp_name'];
    if(!is_uploaded_file($tmp)) jerr('Archivo inválido');

    $fh=fopen($tmp,'r'); if(!$fh) jerr('No se pudo leer');
    $header=fgetcsv($fh); if(!$header) jerr('CSV vacío');
    $header=array_map('trim',$header);

    $expected=['empresa_id','ID_PERFIL','PER_NOMBRE','cve_cia','Activo'];
    $diff=array_diff($expected,$header);
    if($diff) jerr('Layout incorrecto. Faltan columnas: '.implode(', ',$diff));

    if($action==='import_preview'){
      $rows=[]; $line=1; $warn=[];
      while(($r=fgetcsv($fh))!==false && count($rows)<50){
        $line++;
        $row=array_combine($header,$r);
        $rows[]=$row;
        if(trim($row['empresa_id']??'')==='' || trim($row['ID_PERFIL']??'')==='' || trim($row['PER_NOMBRE']??'')===''){
          $warn[]="Línea $line: empresa_id, ID_PERFIL, PER_NOMBRE obligatorios.";
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

    rewind($fh);
    fgetcsv($fh);

    $inserted=0; $updated=0; $errors=0; $errList=[]; $line=1;

    db_tx(function() use($fh,$header,&$inserted,&$updated,&$errors,&$errList,&$line){
      while(($r=fgetcsv($fh))!==false){
        $line++;
        $row=array_combine($header,$r);

        $empresa_id=trim($row['empresa_id']??'');
        $ID_PERFIL=trim($row['ID_PERFIL']??'');
        $PER_NOMBRE=trim($row['PER_NOMBRE']??'');
        $cve_cia=trim($row['cve_cia']??'');
        $Activo=(trim($row['Activo']??'1')==='1'?'1':'0');

        if($empresa_id===''||$ID_PERFIL===''||$PER_NOMBRE===''){
          $errors++; $errList[]="Línea $line: empresa_id, ID_PERFIL, PER_NOMBRE obligatorios.";
          continue;
        }

        $ex=db_val("SELECT 1 FROM t_perfilesusuarios WHERE empresa_id=:e AND ID_PERFIL=:p LIMIT 1", [':e'=>$empresa_id,':p'=>$ID_PERFIL]);
        if($ex){
          dbq("UPDATE t_perfilesusuarios SET PER_NOMBRE=:n, cve_cia=:c, Activo=:a WHERE empresa_id=:e AND ID_PERFIL=:p",
              [':n'=>$PER_NOMBRE,':c'=>$cve_cia,':a'=>$Activo,':e'=>$empresa_id,':p'=>$ID_PERFIL]);
          $updated++;
        }else{
          dbq("INSERT INTO t_perfilesusuarios (empresa_id, ID_PERFIL, PER_NOMBRE, cve_cia, Activo)
               VALUES (:e,:p,:n,:c,:a)",
              [':e'=>$empresa_id,':p'=>$ID_PERFIL,':n'=>$PER_NOMBRE,':c'=>$cve_cia,':a'=>$Activo]);
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
