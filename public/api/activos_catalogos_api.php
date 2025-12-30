<?php
// public/api/activos_catalogos_api.php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$cat = $_POST['cat'] ?? $_GET['cat'] ?? '';

function jerr($m, $extra=[]){ echo json_encode(['success'=>false,'error'=>$m]+$extra); exit; }
function jok($arr=[]){ echo json_encode(['success'=>true]+$arr); exit; }
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v===''||$v===null) ? 0 : (int)$v; }

// Allowlist (seguridad + mapeo)
$CATS = [
  'tipo' => [
    'table' => 'c_activo_tipo',
    'pk'    => 'id_tipo',
    'fields'=> ['nombre','descripcion','activo'],
    'required' => ['nombre'],
    'kpis' => ['total'=>'COUNT(*)','activos'=>'SUM(activo=1)']
  ],
  'propiedad' => [
    'table' => 'c_activo_propiedad',
    'pk'    => 'id_propiedad',
    'fields'=> ['clave','nombre','activo'],
    'required' => ['clave','nombre'],
    'kpis' => ['total'=>'COUNT(*)','activos'=>'SUM(activo=1)']
  ],
  'condicion' => [
    'table' => 'c_activo_condicion',
    'pk'    => 'id_condicion',
    'fields'=> ['clave','nombre','activo'],
    'required' => ['clave','nombre'],
    'kpis' => ['total'=>'COUNT(*)','activos'=>'SUM(activo=1)']
  ],
  'estado' => [
    'table' => 'c_activo_estado',
    'pk'    => 'id_estado',
    'fields'=> ['nombre','semaforo','activo'],
    'required' => ['nombre','semaforo'],
    'kpis' => ['total'=>'COUNT(*)','activos'=>'SUM(activo=1)']
  ],
];

if(!isset($CATS[$cat])) jerr("Catálogo inválido (cat).", ['cats'=>array_keys($CATS)]);
$cfg = $CATS[$cat];

$table = $cfg['table'];
$pk    = $cfg['pk'];
$fields= $cfg['fields'];

try{

  if($action==='list'){
    $q = s($_GET['q'] ?? $_POST['q'] ?? '');
    $where = " WHERE (deleted_at IS NULL OR deleted_at='0000-00-00 00:00:00') ";
    $params = [];

    if($q){
      // buscar en campos de texto
      $likes = [];
      foreach($fields as $f){
        $likes[] = "$f LIKE :q";
      }
      $where .= " AND (" . implode(" OR ", $likes) . ") ";
      $params[':q'] = "%$q%";
    }

    $sql = "SELECT $pk, ".implode(",",$fields).", created_at, updated_at
            FROM $table
            $where
            ORDER BY $pk DESC";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // KPIs
    $kpiSql = "SELECT {$cfg['kpis']['total']} total, {$cfg['kpis']['activos']} activos
               FROM $table
               WHERE (deleted_at IS NULL OR deleted_at='0000-00-00 00:00:00')";
    $kpis = $pdo->query($kpiSql)->fetch(PDO::FETCH_ASSOC);

    jok(['data'=>$rows,'kpis'=>$kpis]);
  }

  if($action==='get'){
    $id = i0($_GET['id'] ?? $_POST['id'] ?? 0);
    if(!$id) jerr("ID requerido");
    $st = $pdo->prepare("SELECT $pk, ".implode(",",$fields).", created_at, updated_at
                         FROM $table
                         WHERE $pk=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if(!$r) jerr("No encontrado");
    jok(['row'=>$r]);
  }

  if($action==='save'){
    $id = i0($_POST[$pk] ?? 0);

    // validar obligatorios
    $errs=[];
    foreach($cfg['required'] as $req){
      if(s($_POST[$req] ?? '')===null) $errs[]=$req;
    }
    if($errs) jerr("Campos obligatorios: ".implode(", ",$errs));

    // armar data
    $data = [];
    foreach($fields as $f){
      $data[$f] = $_POST[$f] ?? null;
      if($f==='activo') $data[$f] = i0($data[$f]);
      else $data[$f] = s($data[$f]);
    }

    if($id>0){
      $sets=[];
      $params=[':id'=>$id];
      foreach($fields as $f){
        $sets[]="$f=:$f";
        $params[":$f"]=$data[$f];
      }
      $sql="UPDATE $table SET ".implode(",",$sets).", updated_at=NOW() WHERE $pk=:id";
      $st=$pdo->prepare($sql);
      $st->execute($params);
      jok(['id'=>$id,'mensaje'=>'Actualizado']);
    } else {
      $cols = implode(",",$fields);
      $vals = implode(",", array_map(fn($f)=>":$f",$fields));
      $params=[];
      foreach($fields as $f){ $params[":$f"]=$data[$f]; }
      $sql="INSERT INTO $table ($cols, created_at) VALUES ($vals, NOW())";
      $st=$pdo->prepare($sql);
      $st->execute($params);
      jok(['id'=>(int)$pdo->lastInsertId(),'mensaje'=>'Creado']);
    }
  }

  if($action==='delete'){
    $id = i0($_POST['id'] ?? 0);
    if(!$id) jerr("ID requerido");
    // Soft delete
    $st = $pdo->prepare("UPDATE $table SET deleted_at=NOW(), updated_at=NOW() WHERE $pk=:id");
    $st->execute([':id'=>$id]);
    jok(['mensaje'=>'Eliminado']);
  }

  if($action==='import_csv'){
    if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) jerr("Archivo CSV requerido");
    $tmp = $_FILES['file']['tmp_name'];
    $fh = fopen($tmp,'r');
    if(!$fh) jerr("No pude leer CSV");

    $header = fgetcsv($fh);
    if(!$header) jerr("CSV vacío");
    $header = array_map('trim',$header);

    $ok=0; $err=0; $errs=[];
    $pdo->beginTransaction();

    while(($row=fgetcsv($fh))!==false){
      $line = array_combine($header,$row);
      if(!$line){ $err++; continue; }

      // mapear solo fields permitidos
      $data=[];
      foreach($fields as $f){
        $data[$f] = $line[$f] ?? null;
        if($f==='activo') $data[$f] = i0($data[$f]);
        else $data[$f] = s($data[$f]);
      }

      // validar obligatorios
      $bad=false;
      foreach($cfg['required'] as $req){
        if($data[$req]===null){ $bad=true; }
      }
      if($bad){ $err++; $errs[]=$line; continue; }

      // insert (sin upsert para no “pisar”)
      $cols = implode(",",$fields);
      $vals = implode(",", array_map(fn($f)=>":$f",$fields));
      $params=[];
      foreach($fields as $f){ $params[":$f"]=$data[$f]; }

      $st=$pdo->prepare("INSERT INTO $table ($cols, created_at) VALUES ($vals, NOW())");
      try{ $st->execute($params); $ok++; }
      catch(Exception $e){ $err++; }
    }

    $pdo->commit();
    fclose($fh);

    jok(['mensaje'=>"Importación finalizada",'total_ok'=>$ok,'total_err'=>$err]);
  }

  jerr("Acción inválida");

}catch(Exception $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  jerr("Error: ".$e->getMessage());
}
