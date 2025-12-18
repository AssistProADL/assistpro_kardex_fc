<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function dnull($v){ return ($v==='' || $v===null) ? null : (float)$v; }

function validar_obligatorios($data){
  $errs=[];
  $alm = trim((string)($data['cve_almac'] ?? ''));
  $cve = trim((string)($data['cve_articulo'] ?? ''));
  $des = trim((string)($data['des_articulo'] ?? ''));
  if($alm==='') $errs[]='cve_almac es obligatorio';
  if($cve==='') $errs[]='cve_articulo es obligatorio';
  if($des==='') $errs[]='des_articulo es obligatorio';
  return $errs;
}

function cols_full(){
  // Full layout = todos los campos definidos en tu DDL (en el orden que manejaremos en CSV)
  return [
    'id',
    'cve_almac',
    'cve_articulo',
    'des_articulo',
    'des_detallada',
    'cve_umed',
    'cve_ssgpo',
    'fec_altaart',
    'imp_costo',
    'des_tipo',
    'comp_cveumed',
    'empq_cveumed',
    'num_multiplo',
    'des_observ',
    'mav_almacenable',
    'cve_moneda',
    'mav_cveubica',
    'mav_delinea',
    'mav_obsoleto',
    'mav_pctiva',
    'IEPS',
    'PrecioVenta',
    'cve_tipcaja',
    'ban_condic',
    'num_volxpal',
    'cve_codprov',
    'remplazo',
    'ID_Proveedor',
    'peso',
    'num_multiploch',
    'barras2',
    'Caduca',
    'Compuesto',
    'Max_Cajas',
    'Activo',
    'barras3',
    'cajas_palet',
    'control_lotes',
    'control_numero_series',
    'control_garantia',
    'tipo_garantia',
    'valor_garantia',
    'control_peso',
    'control_volumen',
    'req_refrigeracion',
    'mat_peligroso',
    'grupo',
    'clasificacion',
    'tipo',
    'tipo_caja',
    'alto',
    'fondo',
    'ancho',
    'costo',
    'tipo_producto',
    'umas',
    'unidadMedida',
    'costoPromedio',
    'Cve_SAP',
    'Ban_Envase',
    'Usa_Envase',
    'Tipo_Envase',
    'control_abc',
    'cve_alt',
    'ecommerce_activo',
    'ecommerce_categoria',
    'ecommerce_subcategoria',
    'ecommerce_img_principal',
    'ecommerce_img_galeria',
    'ecommerce_tags',
    'ecommerce_destacado'
  ];
}

/* =====================================================
 * KPI (cards por almacén)
 * ===================================================== */
if($action==='kpi'){
  // KPIs “gerenciales” por almacén (útiles para cards)
  $sql = "
    SELECT
      cve_almac,
      COUNT(*) AS total,
      SUM(CASE WHEN IFNULL(Activo,1)=1 THEN 1 ELSE 0 END) AS activas,
      SUM(CASE WHEN IFNULL(Activo,1)=0 THEN 1 ELSE 0 END) AS inactivas,
      SUM(CASE WHEN IFNULL(control_lotes,'N')='S' THEN 1 ELSE 0 END) AS con_lotes,
      SUM(CASE WHEN IFNULL(control_numero_series,'N')='S' THEN 1 ELSE 0 END) AS con_series,
      SUM(CASE WHEN IFNULL(control_garantia,'N')='S' THEN 1 ELSE 0 END) AS con_garantia,
      SUM(CASE WHEN IFNULL(ecommerce_activo,0)=1 THEN 1 ELSE 0 END) AS ecommerce_activos,
      SUM(CASE WHEN IFNULL(ecommerce_destacado,0)=1 THEN 1 ELSE 0 END) AS ecommerce_destacados
    FROM c_articulo
    GROUP BY cve_almac
    ORDER BY cve_almac
  ";
  echo json_encode($pdo->query($sql)->fetchAll());
  exit;
}

/* =====================================================
 * EXPORT CSV (layout / datos)
 * ===================================================== */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout'; // layout|datos
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=articulos_'.$tipo.'.csv');

  $out = fopen('php://output','w');
  $cols = cols_full();

  // Para CSV: quitamos id y fec_altaart si quieres “operativo”, pero tú pediste FULL -> lo dejamos completo.
  fputcsv($out,$cols);

  if($tipo==='datos'){
    $inactivos = (int)($_GET['inactivos'] ?? 0);
    $alm = (int)($_GET['cve_almac'] ?? 0);
    $q = trim((string)($_GET['q'] ?? ''));

    $where = "WHERE IFNULL(Activo,1)=:activo";
    if($alm>0) $where .= " AND cve_almac=:alm";
    if($q!==''){
      $where .= " AND (
        cve_articulo LIKE :q OR des_articulo LIKE :q OR Cve_SAP LIKE :q OR
        grupo LIKE :q OR clasificacion LIKE :q OR tipo LIKE :q OR tipo_producto LIKE :q
      )";
    }

    $sql = "SELECT ".implode(',', $cols)." FROM c_articulo $where ORDER BY cve_almac, cve_articulo";
    $st=$pdo->prepare($sql);
    $st->bindValue(':activo',$inactivos?0:1,PDO::PARAM_INT);
    if($alm>0) $st->bindValue(':alm',$alm,PDO::PARAM_INT);
    if($q!=='') $st->bindValue(':q',"%$q%",PDO::PARAM_STR);
    $st->execute();
    while($row = $st->fetch(PDO::FETCH_ASSOC)){
      // Asegurar orden exacto de columnas
      $line=[];
      foreach($cols as $c) $line[] = $row[$c] ?? null;
      fputcsv($out,$line);
    }
  }

  fclose($out);
  exit;
}

/* =====================================================
 * IMPORT CSV (UPSERT por cve_almac + cve_articulo)
 * ===================================================== */
if($action==='import_csv'){
  if(!isset($_FILES['file'])){ echo json_encode(['error'=>'Archivo no recibido']); exit; }
  $fh = fopen($_FILES['file']['tmp_name'],'r');
  if(!$fh){ echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

  $headers = fgetcsv($fh);
  $cols = cols_full();
  if($headers !== $cols){
    echo json_encode(['error'=>'Layout incorrecto','esperado'=>$cols,'recibido'=>$headers]);
    exit;
  }

  $stFind = $pdo->prepare("SELECT id FROM c_articulo WHERE cve_almac=? AND cve_articulo=? LIMIT 1");

  // insert: omitimos id cuando venga vacío o 0; fec_altaart si viene vacío se setea NOW()
  $insertCols = $cols;
  // id lo dejamos insertable por si tu CSV trae id (legacy). Si falla por PK/AI, lo ajustas: mejor lo ignoramos.
  // Para evitar conflictos, no insertamos id.
  $insertCols = array_values(array_filter($insertCols, fn($c)=>$c!=='id'));

  $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
  $stIns = $pdo->prepare("
    INSERT INTO c_articulo (".implode(',', $insertCols).")
    VALUES ($placeholders)
  ");

  // update: actualiza todo excepto id, cve_almac, cve_articulo
  $updCols = array_values(array_filter($cols, fn($c)=>!in_array($c,['id','cve_almac','cve_articulo'])));
  $set = implode(',', array_map(fn($c)=>"$c=?", $updCols));

  $stUpd = $pdo->prepare("
    UPDATE c_articulo SET $set
    WHERE cve_almac=? AND cve_articulo=?
    LIMIT 1
  ");

  $rows_ok=0; $rows_err=0; $errores=[];
  $pdo->beginTransaction();

  try{
    $linea=1;
    while(($r=fgetcsv($fh))!==false){
      $linea++;
      if(!$r || count($r)<count($cols)){
        $rows_err++;
        $errores[]=['fila'=>$linea,'motivo'=>'Fila incompleta','data'=>$r];
        continue;
      }

      $data = array_combine($cols, $r);

      // Normalizaciones mínimas
      $data['cve_almac'] = (int)($data['cve_almac'] ?? 0);
      $data['cve_articulo'] = trim((string)($data['cve_articulo'] ?? ''));
      $data['des_articulo'] = trim((string)($data['des_articulo'] ?? ''));

      $errs = validar_obligatorios($data);
      if($errs){
        $rows_err++;
        $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs),'data'=>$r];
        continue;
      }

      // Defaults operativos
      if(trim((string)($data['fec_altaart'] ?? ''))===''){
        $data['fec_altaart'] = date('Y-m-d H:i:s');
      }
      if(trim((string)($data['Activo'] ?? ''))===''){
        $data['Activo'] = 1;
      }
      if(trim((string)($data['control_garantia'] ?? ''))===''){
        $data['control_garantia'] = 'N';
      }
      if(trim((string)($data['tipo_garantia'] ?? ''))===''){
        $data['tipo_garantia'] = 'MESES';
      }

      $stFind->execute([$data['cve_almac'], $data['cve_articulo']]);
      $existe = $stFind->fetchColumn();

      if($existe){
        $vals=[];
        foreach($updCols as $c){
          $vals[] = ($data[$c] === '') ? null : $data[$c];
        }
        $vals[] = $data['cve_almac'];
        $vals[] = $data['cve_articulo'];
        $stUpd->execute($vals);
      }else{
        $vals=[];
        foreach($insertCols as $c){
          $vals[] = ($data[$c] === '') ? null : $data[$c];
        }
        $stIns->execute($vals);
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
 * LIST paginado + SEARCH
 * ===================================================== */
if($action==='list'){
  $inactivos = (int)($_GET['inactivos'] ?? 0);
  $alm = (int)($_GET['cve_almac'] ?? 0);
  $q = trim((string)($_GET['q'] ?? ''));

  $page = max(1, (int)($_GET['page'] ?? 1));
  $per  = max(1, min(200, (int)($_GET['per_page'] ?? 25)));
  $off  = ($page-1)*$per;

  $where = "WHERE IFNULL(Activo,1)=:activo";
  if($alm>0) $where .= " AND cve_almac=:alm";
  if($q!==''){
    $where .= " AND (
      cve_articulo LIKE :q OR des_articulo LIKE :q OR des_tipo LIKE :q OR
      grupo LIKE :q OR clasificacion LIKE :q OR tipo LIKE :q OR tipo_producto LIKE :q OR
      Cve_SAP LIKE :q OR cve_alt LIKE :q OR barras2 LIKE :q OR barras3 LIKE :q
    )";
  }

  $sqlCount = "SELECT COUNT(*) FROM c_articulo $where";
  $stc=$pdo->prepare($sqlCount);
  $stc->bindValue(':activo',$inactivos?0:1,PDO::PARAM_INT);
  if($alm>0) $stc->bindValue(':alm',$alm,PDO::PARAM_INT);
  if($q!=='') $stc->bindValue(':q',"%$q%",PDO::PARAM_STR);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  // Grid: columnas "ejecutivas" (la ficha completa va en modal get)
  $sql = "
    SELECT
      id,cve_almac,cve_articulo,des_articulo,des_tipo,
      grupo,clasificacion,tipo,tipo_producto,
      costo,costoPromedio,PrecioVenta,imp_costo,
      control_lotes,control_numero_series,control_garantia,
      Cve_SAP,
      ecommerce_activo,ecommerce_destacado,
      IFNULL(Activo,1) AS Activo
    FROM c_articulo
    $where
    ORDER BY cve_almac, cve_articulo
    LIMIT :lim OFFSET :off
  ";

  $st=$pdo->prepare($sql);
  $st->bindValue(':activo',$inactivos?0:1,PDO::PARAM_INT);
  if($alm>0) $st->bindValue(':alm',$alm,PDO::PARAM_INT);
  if($q!=='') $st->bindValue(':q',"%$q%",PDO::PARAM_STR);
  $st->bindValue(':lim',$per,PDO::PARAM_INT);
  $st->bindValue(':off',$off,PDO::PARAM_INT);
  $st->execute();

  echo json_encode([
    'page'=>$page,
    'per_page'=>$per,
    'total'=>$total,
    'pages'=> $per ? (int)ceil($total/$per) : 1,
    'data'=>$st->fetchAll()
  ]);
  exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE
 * ===================================================== */
switch($action){

  case 'get':{
    $id = $_GET['id'] ?? null;
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $st=$pdo->prepare("SELECT * FROM c_articulo WHERE id=? LIMIT 1");
    $st->execute([(int)$id]);
    echo json_encode($st->fetch());
    break;
  }

  case 'create':{
    $errs = validar_obligatorios($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $cols = cols_full();
    // insert: omitimos id, y si no viene fec_altaart -> NOW()
    $insCols = array_values(array_filter($cols, fn($c)=>$c!=='id'));
    // aseguramos fec_altaart
    if(trim((string)($_POST['fec_altaart'] ?? ''))==='') $_POST['fec_altaart'] = date('Y-m-d H:i:s');
    if(trim((string)($_POST['Activo'] ?? ''))==='') $_POST['Activo'] = 1;
    if(trim((string)($_POST['control_garantia'] ?? ''))==='') $_POST['control_garantia'] = 'N';
    if(trim((string)($_POST['tipo_garantia'] ?? ''))==='') $_POST['tipo_garantia'] = 'MESES';

    $ph = implode(',', array_fill(0, count($insCols), '?'));
    $st=$pdo->prepare("INSERT INTO c_articulo (".implode(',', $insCols).") VALUES ($ph)");

    $vals=[];
    foreach($insCols as $c){
      $v = $_POST[$c] ?? null;
      $vals[] = ($v==='' ? null : $v);
    }
    $st->execute($vals);

    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
    break;
  }

  case 'update':{
    $id = $_POST['id'] ?? null;
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }

    $errs = validar_obligatorios($_POST);
    if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

    $cols = cols_full();
    $updCols = array_values(array_filter($cols, fn($c)=>$c!=='id')); // puedes actualizar todo excepto id
    $set = implode(',', array_map(fn($c)=>"$c=?", $updCols));

    $st=$pdo->prepare("UPDATE c_articulo SET $set WHERE id=? LIMIT 1");

    if(trim((string)($_POST['Activo'] ?? ''))==='') $_POST['Activo'] = 1;
    if(trim((string)($_POST['control_garantia'] ?? ''))==='') $_POST['control_garantia'] = 'N';
    if(trim((string)($_POST['tipo_garantia'] ?? ''))==='') $_POST['tipo_garantia'] = 'MESES';

    $vals=[];
    foreach($updCols as $c){
      $v = $_POST[$c] ?? null;
      $vals[] = ($v==='' ? null : $v);
    }
    $vals[] = (int)$id;

    $st->execute($vals);
    echo json_encode(['success'=>true]);
    break;
  }

  case 'delete':{
    $id = $_POST['id'] ?? null;
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE c_articulo SET Activo=0 WHERE id=?")->execute([(int)$id]);
    echo json_encode(['success'=>true]);
    break;
  }

  case 'restore':{
    $id = $_POST['id'] ?? null;
    if(!$id){ echo json_encode(['error'=>'id requerido']); exit; }
    $pdo->prepare("UPDATE c_articulo SET Activo=1 WHERE id=?")->execute([(int)$id]);
    echo json_encode(['success'=>true]);
    break;
  }

  default:
    echo json_encode(['error'=>'Acción no válida']);
}
