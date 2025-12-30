<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jerr($msg,$det=null){ echo json_encode(['error'=>$msg,'detalles'=>$det],JSON_UNESCAPED_UNICODE); exit; }
function clean($v){ return trim((string)$v); }
function norm01($v,$def='1'){ $v=clean($v); if($v==='') return $def; return ($v==='1')?'1':'0'; }

// --- Detecta si existe empresa_id en c_almacenp (tu BD actual NO la trae) ---
$hasEmpresaId = (int)db_val("
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'c_almacenp'
    AND COLUMN_NAME = 'empresa_id'
") > 0;

// Campo “empresa” operativo (si no hay empresa_id, usamos cve_cia)
$empresaCol = $hasEmpresaId ? "empresa_id" : "cve_cia";

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try{
  // ===================== LIST =====================
  if($action==='list'){
    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,'1')='1'";
    if($q!==''){
      // Nota: buscamos contra la columna de empresa que aplique
      $where[]="(($empresaCol) LIKE :q OR id LIKE :q OR clave LIKE :q OR nombre LIKE :q OR BL LIKE :q)";
      $p[':q']="%$q%";
    }

    $sql="SELECT
            ($empresaCol) AS empresa_id,
            id, clave, nombre, cve_cia, BL, telefono, correo, Activo
          FROM c_almacenp";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" ORDER BY IFNULL(Activo,'1') DESC, nombre ASC LIMIT 3000";

    echo json_encode(['rows'=>db_all($sql,$p),'meta'=>['has_empresa_id'=>$hasEmpresaId,'empresa_col'=>$empresaCol]],JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== GET =====================
  if($action==='get'){
    $emp = clean($_GET['empresa_id'] ?? '');
    $id = clean($_GET['id'] ?? '');
    if($id==='') jerr('Llave inválida: id es obligatorio');

    // Si existe empresa_id, la llave es empresa_id + id.
    // Si no existe, usamos cve_cia + id como llave operacional (evita colisiones multiempresa).
    if($hasEmpresaId){
      if($emp==='') jerr('Llave inválida: empresa_id + id');
      $row = db_one("SELECT *, ($empresaCol) AS empresa_id FROM c_almacenp WHERE empresa_id=:e AND id=:i LIMIT 1", [':e'=>$emp,':i'=>$id]);
    }else{
      if($emp==='') jerr('Llave inválida: cve_cia (empresa) + id');
      $row = db_one("SELECT *, ($empresaCol) AS empresa_id FROM c_almacenp WHERE cve_cia=:e AND id=:i LIMIT 1", [':e'=>$emp,':i'=>$id]);
    }

    if(!$row) jerr('No existe el registro');
    echo json_encode($row,JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== CREATE / UPDATE =====================
  if($action==='create' || $action==='update'){
    $k_emp = clean($_POST['k_empresa_id'] ?? '');
    $k_id  = clean($_POST['k_id'] ?? '');

    $empresa_id = clean($_POST['empresa_id'] ?? '');
    $id = clean($_POST['id'] ?? '');
    $clave = strtoupper(clean($_POST['clave'] ?? ''));
    $nombre = clean($_POST['nombre'] ?? '');

    $det=[];
    if($hasEmpresaId && $empresa_id==='') $det[]='empresa_id es obligatorio (tu tabla lo tiene).';
    if(!$hasEmpresaId && $empresa_id==='') $det[]='empresa (se usa cve_cia) es obligatorio.';
    if($id==='') $det[]='id es obligatorio.';
    if($clave==='') $det[]='clave es obligatoria.';
    if($nombre==='') $det[]='nombre es obligatorio.';

    $correo = clean($_POST['correo'] ?? '');
    if($correo!=='' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/',$correo)) $det[]='correo no tiene formato válido.';
    if($det) jerr('Validación',$det);

    // Armamos data según diccionario real
    $data = [
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
      'cve_cia'=>clean($_POST['cve_cia'] ?? ''),  // si no hay empresa_id, aquí guardamos la “empresa”
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

    // Si la tabla sí tiene empresa_id, lo incluimos; si no, lo ignoramos y usamos cve_cia.
    if($hasEmpresaId){
      $data['empresa_id'] = $empresa_id;
    }else{
      // fuerza consistencia: el “empresa_id” que llega del UI se guarda en cve_cia
      $data['cve_cia'] = $empresa_id;
    }

    db_tx(function() use($action,$hasEmpresaId,$k_emp,$k_id,$empresa_id,$id,$data){
      if($action==='create'){
        if($hasEmpresaId){
          $ex=db_val("SELECT 1 FROM c_almacenp WHERE empresa_id=:e AND id=:i LIMIT 1", [':e'=>$empresa_id,':i'=>$id]);
        }else{
          $ex=db_val("SELECT 1 FROM c_almacenp WHERE cve_cia=:e AND id=:i LIMIT 1", [':e'=>$empresa_id,':i'=>$id]);
        }
        if($ex) throw new Exception("Ya existe el almacén con esa llave (empresa + id).");

        $cols=array_keys($data);
        $ins="INSERT INTO c_almacenp (".implode(',',$cols).") VALUES (:".implode(',:',$cols).")";
        $p=[]; foreach($data as $k=>$v) $p[":$k"]=$v;
        dbq($ins,$p);

      }else{
        if($k_id==='') throw new Exception("Llave original inválida (k_id).");

        // update por llave original
        if($hasEmpresaId){
          if($k_emp==='') throw new Exception("Llave original inválida (k_empresa_id).");
          $where="WHERE empresa_id=:ke AND id=:ki";
          $p=[':ke'=>$k_emp,':ki'=>$k_id];
        }else{
          if($k_emp==='') throw new Exception("Llave original inválida (empresa/cve_cia).");
          $where="WHERE cve_cia=:ke AND id=:ki";
          $p=[':ke'=>$k_emp,':ki'=>$k_id];
        }

        $set=[];
        foreach($data as $k=>$v){ $set[]="$k=:$k"; $p[":$k"]=$v; }
        dbq("UPDATE c_almacenp SET ".implode(',',$set)." $where", $p);
      }
    });

    echo json_encode(['ok'=>1,'meta'=>['has_empresa_id'=>$hasEmpresaId]],JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== DELETE / RESTORE =====================
  if($action==='delete' || $action==='restore'){
    $emp=clean($_POST['empresa_id'] ?? '');
    $id=clean($_POST['id'] ?? '');
    if($emp==='' || $id==='') jerr('Llave inválida (empresa + id)');

    $val=($action==='delete')?'0':'1';
    if($hasEmpresaId){
      dbq("UPDATE c_almacenp SET Activo=:v WHERE empresa_id=:e AND id=:i", [':v'=>$val,':e'=>$emp,':i'=>$id]);
    }else{
      dbq("UPDATE c_almacenp SET Activo=:v WHERE cve_cia=:e AND id=:i", [':v'=>$val,':e'=>$emp,':i'=>$id]);
    }

    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== EXPORT =====================
  if($action==='export'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=almacenes_export.csv');

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,'1')='1'";
    if($q!==''){
      $where[]="(($empresaCol) LIKE :q OR id LIKE :q OR clave LIKE :q OR nombre LIKE :q OR BL LIKE :q)";
      $p[':q']="%$q%";
    }

    $sql="SELECT
            ($empresaCol) AS empresa_id,
            id, clave, nombre, cve_cia, BL, direccion, codigopostal, telefono, contacto, correo, comentarios, Activo
          FROM c_almacenp";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" ORDER BY nombre ASC";

    $rows=db_all($sql,$p);

    $out=fopen('php://output','w');
    fputcsv($out, array_keys($rows ? $rows[0] : ['empresa_id'=>'','id'=>'','clave'=>'','nombre'=>'','cve_cia'=>'','BL'=>'','direccion'=>'','codigopostal'=>'','telefono'=>'','contacto'=>'','correo'=>'','comentarios'=>'','Activo'=>'1']));
    foreach($rows as $r) fputcsv($out,$r);
    fclose($out); exit;
  }

  // ===================== LAYOUT =====================
  if($action==='layout'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=almacenes_layout.csv');
    $out=fopen('php://output','w');
    fputcsv($out, ['empresa_id','id','clave','nombre','cve_cia','BL','direccion','codigopostal','telefono','contacto','correo','comentarios','Activo']);
    fputcsv($out, ['1','10','ALM01','ALMACEN DEMO','1','BL-DEFAULT','CALLE 1','64000','8180000000','CONTACTO','correo@dominio.com','OBS','1']);
    fclose($out); exit;
  }

  jerr('Acción no soportada: '.$action);

}catch(Throwable $e){
  jerr('Error: '.$e->getMessage());
}
