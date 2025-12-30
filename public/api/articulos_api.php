<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jerr($msg,$det=null){ echo json_encode(['error'=>$msg,'detalles'=>$det],JSON_UNESCAPED_UNICODE); exit; }
function clean($v){ return trim((string)$v); }
function nint($v){ $v=clean($v); return ($v===''? null : (int)$v); }
function nd($v){ $v=clean($v); return ($v===''? null : $v); }
function norm01($v,$def=1){ $v=clean($v); if($v==='') return $def; return ($v==='1' || strtolower($v)==='true')?1:0; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try{

  // ===================== LIST =====================
  if($action==='list'){
    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,1)=1";
    if($q!==''){
      $where[]="(
        cve_articulo LIKE :q OR des_articulo LIKE :q OR des_detallada LIKE :q
        OR tipo LIKE :q OR grupo LIKE :q OR clasificacion LIKE :q
        OR Cve_SAP LIKE :q OR cve_alt LIKE :q OR barras2 LIKE :q OR barras3 LIKE :q
        OR CAST(id AS CHAR) LIKE :q OR CAST(cve_almac AS CHAR) LIKE :q
      )";
      $p[':q']="%$q%";
    }

    $sql="SELECT
            cve_almac, id, cve_articulo, des_articulo, des_detallada,
            unidadMedida, cve_umed, imp_costo, PrecioVenta,
            tipo, grupo, clasificacion, Compuesto, Caduca,
            control_lotes, control_numero_series, control_garantia, tipo_garantia, valor_garantia,
            ecommerce_activo, ecommerce_categoria, ecommerce_subcategoria, ecommerce_destacado,
            Activo
          FROM c_articulo";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" ORDER BY IFNULL(Activo,1) DESC, des_articulo ASC LIMIT 3000";

    echo json_encode(['rows'=>db_all($sql,$p)],JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== GET =====================
  if($action==='get'){
    $cve_almac = clean($_GET['cve_almac'] ?? '');
    $id = clean($_GET['id'] ?? '');
    if($cve_almac==='' || $id==='') jerr('Llave inválida: cve_almac + id');

    $row = db_one("SELECT * FROM c_articulo WHERE cve_almac=:a AND id=:i LIMIT 1", [':a'=>$cve_almac,':i'=>$id]);
    if(!$row) jerr('No existe el registro');
    echo json_encode($row,JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== CREATE / UPDATE =====================
  if($action==='create' || $action==='update'){
    $k_cve_almac = clean($_POST['k_cve_almac'] ?? '');
    $k_id        = clean($_POST['k_id'] ?? '');

    $cve_almac   = clean($_POST['cve_almac'] ?? '');
    $id          = clean($_POST['id'] ?? '');

    $cve_articulo = clean($_POST['cve_articulo'] ?? '');
    $des_articulo = clean($_POST['des_articulo'] ?? '');

    $det=[];
    if($cve_almac==='' ) $det[]='cve_almac es obligatorio.';
    if($id==='' )        $det[]='id es obligatorio.';
    if($cve_articulo==='') $det[]='cve_articulo es obligatorio.';
    if($des_articulo==='') $det[]='des_articulo es obligatorio.';
    if($det) jerr('Validación',$det);

    $data = [
      'cve_almac' => (int)$cve_almac,
      'id' => (int)$id,

      'cve_articulo' => $cve_articulo,
      'des_articulo' => $des_articulo,
      'des_detallada' => nd($_POST['des_detallada'] ?? null),

      'unidadMedida' => nint($_POST['unidadMedida'] ?? null),
      'cve_umed' => nint($_POST['cve_umed'] ?? null),

      'imp_costo' => nd($_POST['imp_costo'] ?? null),
      'PrecioVenta' => nd($_POST['PrecioVenta'] ?? null),

      'tipo' => nd($_POST['tipo'] ?? null),
      'grupo' => nd($_POST['grupo'] ?? null),
      'clasificacion' => nd($_POST['clasificacion'] ?? null),

      'Compuesto' => nd($_POST['Compuesto'] ?? null),
      'Caduca' => nd($_POST['Caduca'] ?? null),

      'control_lotes' => nd($_POST['control_lotes'] ?? null),
      'control_numero_series' => nd($_POST['control_numero_series'] ?? null),

      'control_garantia' => nd($_POST['control_garantia'] ?? null),
      'tipo_garantia' => nd($_POST['tipo_garantia'] ?? null),
      'valor_garantia' => nint($_POST['valor_garantia'] ?? null),

      'Cve_SAP' => nd($_POST['Cve_SAP'] ?? null),
      'cve_alt' => nd($_POST['cve_alt'] ?? null),
      'barras2' => nd($_POST['barras2'] ?? null),
      'barras3' => nd($_POST['barras3'] ?? null),

      'ecommerce_activo' => norm01($_POST['ecommerce_activo'] ?? 0, 0),
      'ecommerce_categoria' => nd($_POST['ecommerce_categoria'] ?? null),
      'ecommerce_subcategoria' => nd($_POST['ecommerce_subcategoria'] ?? null),
      'ecommerce_destacado' => norm01($_POST['ecommerce_destacado'] ?? 0, 0),

      'Activo' => norm01($_POST['Activo'] ?? 1, 1),
    ];

    db_tx(function() use($action,$k_cve_almac,$k_id,$cve_almac,$id,$data){
      if($action==='create'){
        $ex = db_val("SELECT 1 FROM c_articulo WHERE cve_almac=:a AND id=:i LIMIT 1", [':a'=>$cve_almac,':i'=>$id]);
        if($ex) throw new Exception("Ya existe el artículo con esa llave (cve_almac + id).");

        $cols=array_keys($data);
        $sql="INSERT INTO c_articulo (".implode(',',$cols).") VALUES (:".implode(',:',$cols).")";
        $p=[]; foreach($data as $k=>$v) $p[":$k"]=$v;
        dbq($sql,$p);
      }else{
        if($k_cve_almac==='' || $k_id==='') throw new Exception("Llave original inválida (k_cve_almac + k_id).");

        $set=[]; $p=[':ka'=>$k_cve_almac,':ki'=>$k_id];
        foreach($data as $k=>$v){ $set[]="$k=:$k"; $p[":$k"]=$v; }
        dbq("UPDATE c_articulo SET ".implode(',',$set)." WHERE cve_almac=:ka AND id=:ki", $p);
      }
    });

    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== DELETE / RESTORE =====================
  if($action==='delete' || $action==='restore'){
    $cve_almac = clean($_POST['cve_almac'] ?? '');
    $id = clean($_POST['id'] ?? '');
    if($cve_almac==='' || $id==='') jerr('Llave inválida: cve_almac + id');

    $val = ($action==='delete') ? 0 : 1;
    dbq("UPDATE c_articulo SET Activo=:v WHERE cve_almac=:a AND id=:i", [':v'=>$val,':a'=>$cve_almac,':i'=>$id]);
    echo json_encode(['ok'=>1],JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== EXPORT =====================
  if($action==='export'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=articulos_export.csv');

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where=[]; $p=[];
    if(!$inactivos) $where[]="IFNULL(Activo,1)=1";
    if($q!==''){
      $where[]="(cve_articulo LIKE :q OR des_articulo LIKE :q OR Cve_SAP LIKE :q OR CAST(id AS CHAR) LIKE :q)";
      $p[':q']="%$q%";
    }

    $sql="SELECT
            cve_almac,id,cve_articulo,des_articulo,unidadMedida,cve_umed,
            imp_costo,PrecioVenta,tipo,grupo,clasificacion,Compuesto,Caduca,
            control_lotes,control_numero_series,control_garantia,tipo_garantia,valor_garantia,
            Cve_SAP,cve_alt,barras2,barras3,
            ecommerce_activo,ecommerce_categoria,ecommerce_subcategoria,ecommerce_destacado,
            Activo
          FROM c_articulo";
    if($where) $sql.=" WHERE ".implode(" AND ",$where);
    $sql.=" ORDER BY des_articulo ASC";

    $rows = db_all($sql,$p);
    $out=fopen('php://output','w');

    if(!$rows){
      fputcsv($out, ['cve_almac','id','cve_articulo','des_articulo','unidadMedida','cve_umed','imp_costo','PrecioVenta','tipo','grupo','clasificacion','Compuesto','Caduca','control_lotes','control_numero_series','control_garantia','tipo_garantia','valor_garantia','Cve_SAP','cve_alt','barras2','barras3','ecommerce_activo','ecommerce_categoria','ecommerce_subcategoria','ecommerce_destacado','Activo']);
    }else{
      fputcsv($out, array_keys($rows[0]));
      foreach($rows as $r) fputcsv($out,$r);
    }
    fclose($out); exit;
  }

  // ===================== LAYOUT =====================
  if($action==='layout'){
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=articulos_layout.csv');

    $out=fopen('php://output','w');
    fputcsv($out, ['cve_almac','id','cve_articulo','des_articulo','unidadMedida','cve_umed','imp_costo','PrecioVenta','tipo','grupo','clasificacion','Compuesto','Caduca','control_lotes','control_numero_series','control_garantia','tipo_garantia','valor_garantia','Cve_SAP','cve_alt','barras2','barras3','ecommerce_activo','ecommerce_categoria','ecommerce_subcategoria','ecommerce_destacado','Activo']);
    fputcsv($out, ['1','1001','ART-001','ARTICULO DEMO','1','1','12.50','18.90','PT','GPO1','CLAS1','N','N','N','N','N','MESES','0','SAP-001','ALT-001','7500000000001','7500000000002','0','CAT','SUB','0','1']);
    fclose($out); exit;
  }

  // ===================== IMPORT (UPSERT) =====================
  if($action==='import'){
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw,true);
    if(!$payload || !isset($payload['rows']) || !is_array($payload['rows'])) jerr('Payload inválido. Se espera rows[]');

    $rows = $payload['rows'];
    $ok=0; $err=0; $errs=[];

    db_tx(function() use($rows,&$ok,&$err,&$errs){
      foreach($rows as $idx=>$r){
        try{
          $cve_almac = isset($r['cve_almac']) ? (int)$r['cve_almac'] : null;
          $id        = isset($r['id']) ? (int)$r['id'] : null;
          $cve_art   = clean($r['cve_articulo'] ?? '');
          $des       = clean($r['des_articulo'] ?? '');

          if(!$cve_almac || !$id || $cve_art==='' || $des===''){
            throw new Exception("Fila ".($idx+1).": faltan obligatorios (cve_almac,id,cve_articulo,des_articulo).");
          }

          $data = [
            'cve_almac'=>$cve_almac,
            'id'=>$id,
            'cve_articulo'=>$cve_art,
            'des_articulo'=>$des,
            'unidadMedida'=>($r['unidadMedida'] ?? null)===''?null:(isset($r['unidadMedida'])?(int)$r['unidadMedida']:null),
            'cve_umed'=>($r['cve_umed'] ?? null)===''?null:(isset($r['cve_umed'])?(int)$r['cve_umed']:null),
            'imp_costo'=>nd($r['imp_costo'] ?? null),
            'PrecioVenta'=>nd($r['PrecioVenta'] ?? null),
            'tipo'=>nd($r['tipo'] ?? null),
            'grupo'=>nd($r['grupo'] ?? null),
            'clasificacion'=>nd($r['clasificacion'] ?? null),
            'Compuesto'=>nd($r['Compuesto'] ?? null),
            'Caduca'=>nd($r['Caduca'] ?? null),
            'control_lotes'=>nd($r['control_lotes'] ?? null),
            'control_numero_series'=>nd($r['control_numero_series'] ?? null),
            'control_garantia'=>nd($r['control_garantia'] ?? null),
            'tipo_garantia'=>nd($r['tipo_garantia'] ?? null),
            'valor_garantia'=>($r['valor_garantia'] ?? null)===''?null:(isset($r['valor_garantia'])?(int)$r['valor_garantia']:null),
            'Cve_SAP'=>nd($r['Cve_SAP'] ?? null),
            'cve_alt'=>nd($r['cve_alt'] ?? null),
            'barras2'=>nd($r['barras2'] ?? null),
            'barras3'=>nd($r['barras3'] ?? null),
            'ecommerce_activo'=>isset($r['ecommerce_activo'])? (int)norm01($r['ecommerce_activo'],0) : 0,
            'ecommerce_categoria'=>nd($r['ecommerce_categoria'] ?? null),
            'ecommerce_subcategoria'=>nd($r['ecommerce_subcategoria'] ?? null),
            'ecommerce_destacado'=>isset($r['ecommerce_destacado'])? (int)norm01($r['ecommerce_destacado'],0) : 0,
            'Activo'=>isset($r['Activo'])? (int)norm01($r['Activo'],1) : 1,
          ];

          $ex = db_val("SELECT 1 FROM c_articulo WHERE cve_almac=:a AND id=:i LIMIT 1", [':a'=>$cve_almac,':i'=>$id]);
          if($ex){
            $set=[]; $p=[':a'=>$cve_almac,':i'=>$id];
            foreach($data as $k=>$v){ if($k==='cve_almac' || $k==='id') continue; $set[]="$k=:$k"; $p[":$k"]=$v; }
            dbq("UPDATE c_articulo SET ".implode(',',$set)." WHERE cve_almac=:a AND id=:i", $p);
          }else{
            $cols=array_keys($data);
            $sql="INSERT INTO c_articulo (".implode(',',$cols).") VALUES (:".implode(',:',$cols).")";
            $p=[]; foreach($data as $k=>$v) $p[":$k"]=$v;
            dbq($sql,$p);
          }

          $ok++;
        }catch(Throwable $e){
          $err++;
          $errs[] = $e->getMessage();
        }
      }
    });

    echo json_encode(['ok'=>$ok,'err'=>$err,'errores'=>$errs],JSON_UNESCAPED_UNICODE);
    exit;
  }

  jerr('Acción no soportada: '.$action);

}catch(Throwable $e){
  jerr('Error: '.$e->getMessage());
}
