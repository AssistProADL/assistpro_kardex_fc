<?php
/* kardex.php — AssistPro Kardex FC – Kardex Bidireccional ETL (v9.3)
 * - BD: assistpro_etl_fc (PDO root/127.0.0.1)
 * - Cascada: Empresa -> Almacén -> Zona -> Proyecto -> Proveedor -> Producto -> Lote
 * - Fechas por defecto: últimos 7 días
 * - No consulta hasta presionar "Filtrar"
 * - Límite 200 (o 50 con &debuglimit=1)
 * - KPIs + CSV + depuración (?ping ?diag ?sql&run=1 ?explain&run=1)
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');

/* ===== Conexión PDO ===== */
try {
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
    'root','',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]
  );
} catch (Throwable $e) { die('Error de conexión a assistpro_etl_fc: '.htmlspecialchars($e->getMessage())); }

/* ===== Helpers debug (opcionales) ===== */
function json_out($a){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($a, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); exit; }
$DBG = [
  'ping'=>isset($_GET['ping']),
  'diag'=>isset($_GET['diag']),
  'sql'=> isset($_GET['sql']) && ($_GET['run']??'')==='1',
  'explain'=>isset($_GET['explain']) && ($_GET['run']??'')==='1',
];
if($DBG['ping']){
  try{$r=$pdo->query("SELECT 1")->fetchColumn(); json_out(['ok'=>true,'db'=>'assistpro_etl_fc','one'=>$r]);}
  catch(Throwable $e){json_out(['ok'=>false,'err'=>$e->getMessage()]);}
}
if($DBG['diag']){
  try{
    $out=[];
    $out['empresas']=$pdo->query("SELECT empresa_id,des_cia FROM c_compania LIMIT 10")->fetchAll();
    $out['almacenp']=$pdo->query("SELECT id,clave,nombre FROM c_almacenp LIMIT 10")->fetchAll();
    $out['almacen']=$pdo->query("SELECT cve_almacenp,cve_almac,des_almac FROM c_almacen LIMIT 10")->fetchAll();
    $out['proyecto']=$pdo->query("SELECT Id,Cve_Proyecto,Des_Proyecto,id_almacen FROM c_proyecto LIMIT 10")->fetchAll();
    $out['proveedor']=$pdo->query("SELECT cve_proveedor,Nombre FROM c_proveedores LIMIT 10")->fetchAll();
    $out['vista_existe']=(bool)$pdo->query("SHOW FULL TABLES WHERE Table_type='VIEW' AND Tables_in_assistpro_etl_fc='v_kardex_doble_partida'")->fetch();
    json_out(['ok'=>true]+$out);
  }catch(Throwable $e){json_out(['ok'=>false,'err'=>$e->getMessage()]);}
}

/* ===== Filtros ===== */
$empresa_id    = $_GET['empresa_id']    ?? '';
$alm_clave     = $_GET['alm_clave']     ?? ''; // c_almacenp.clave (texto)
$zona_codigo   = $_GET['zona_codigo']   ?? ''; // c_almacen.cve_almac (texto)
$proyecto_id   = $_GET['proyecto_id']   ?? ''; // c_proyecto.Id (texto)
$proveedor_clv = $_GET['proveedor']     ?? '';
$producto_id   = $_GET['producto_id']   ?? '';
$lote          = $_GET['lote']          ?? '';
$tipo_tx       = $_GET['tipo_tx']       ?? '';
$fini          = $_GET['fini']          ?? '';
$ffin          = $_GET['ffin']          ?? '';

$limit  = (isset($_GET['limit']) && ctype_digit((string)$_GET['limit'])) ? (int)$_GET['limit'] : 200;
$page   = (isset($_GET['page'])  && ctype_digit((string)$_GET['page']))  ? (int)$_GET['page']  : 1;
$offset = max(0, ($page - 1) * $limit);
$run    = isset($_GET['run']) && $_GET['run']=='1';
if (isset($_GET['debuglimit']) && $_GET['debuglimit']=='1') $limit = min($limit, 50);

/* ===== Fechas por defecto ===== */
if ($fini==='' || $ffin===''){ $fini=$fini?:date('Y-m-d',strtotime('-7 days')); $ffin=$ffin?:date('Y-m-d'); }

/* ===== Catálogos ===== */
// Empresas
$empList = $pdo->query("SELECT empresa_id, des_cia FROM c_compania ORDER BY empresa_id")->fetchAll();
if ($empresa_id==='' && count($empList)===1) $empresa_id=(string)$empList[0]['empresa_id'];

// Almacenes por empresa (c_almacenp)
$alm_opts=[];
if ($empresa_id!=='') {
  $stA=$pdo->prepare("SELECT id,clave,nombre FROM c_almacenp WHERE CONVERT(empresa_id USING utf8mb4)=CONVERT(:emp USING utf8mb4) ORDER BY nombre");
  $stA->execute([':emp'=>$empresa_id]);
  $alm_opts=$stA->fetchAll();
}

// Zonas por almacén — IMPORTANT: usando la CLAVE del almacén padre
$zona_opts=[];
if ($alm_clave!=='') {
  $stZ=$pdo->prepare("
    SELECT cve_almac AS codigo, des_almac AS nombre
    FROM c_almacen
    WHERE CONVERT(cve_almacenp USING utf8mb4) = CONVERT(:almclave USING utf8mb4)
    ORDER BY des_almac
  ");
  $stZ->execute([':almclave'=>$alm_clave]);
  $zona_opts=$stZ->fetchAll();
}

// Tipos de movimiento
$tipos_opts=$pdo->query("SELECT DISTINCT nombre FROM t_tipomovimiento ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

// Proyectos — IMPORTANT: id_almacen almacena la CLAVE del almacén padre
$proy_opts=[];
if ($empresa_id!=='') {
  if ($alm_clave!=='') {
    $stP=$pdo->prepare("
      SELECT Id, Cve_Proyecto, Des_Proyecto
      FROM c_proyecto
      WHERE CONVERT(empresa_id USING utf8mb4)=CONVERT(:emp USING utf8mb4)
        AND CONVERT(id_almacen USING utf8mb4)=CONVERT(:almclave USING utf8mb4)
      ORDER BY Des_Proyecto
    ");
    $stP->execute([':emp'=>$empresa_id, ':almclave'=>$alm_clave]);
    $proy_opts=$stP->fetchAll();
  }
  if (!$proy_opts) {
    $stP=$pdo->prepare("
      SELECT Id, Cve_Proyecto, Des_Proyecto
      FROM c_proyecto
      WHERE CONVERT(empresa_id USING utf8mb4)=CONVERT(:emp USING utf8mb4)
      ORDER BY Des_Proyecto
    ");
    $stP->execute([':emp'=>$empresa_id]);
    $proy_opts=$stP->fetchAll();
  }
}

// Proveedores por empresa
$prov_opts=[];
if ($empresa_id!=='') {
  $stV=$pdo->prepare("SELECT cve_proveedor AS clave, Nombre AS nombre FROM c_proveedores WHERE CONVERT(empresa_id USING utf8mb4)=CONVERT(:emp USING utf8mb4) ORDER BY nombre");
  $stV->execute([':emp'=>$empresa_id]);
  $prov_opts=$stV->fetchAll();
}

// Productos (ligero)
$prod_opts=[];
if ($empresa_id!=='') {
  $stAp=$pdo->prepare("SELECT cve_articulo AS cve, des_articulo AS nom FROM c_articulo WHERE CONVERT(empresa_id USING utf8mb4)=CONVERT(:emp USING utf8mb4) ORDER BY des_articulo LIMIT 300");
  $stAp->execute([':emp'=>$empresa_id]);
  $prod_opts=$stAp->fetchAll();
}

/* ===== Lotes sugerencias
   - Solo cuando hay PRODUCTO y EMPRESA
   - Opcionalmente afinar por almacén/zona
===== */
$lote_opts=[];
if ($empresa_id!=='' && $producto_id!=='') {
  try{
    $pL=[':emp'=>$empresa_id, ':pr'=>'%'.$producto_id.'%'];
    $cL='empresa_id=:emp AND producto_id LIKE :pr';
    if ($alm_clave!==''){ $cL.=" AND alm_clave=:ac"; $pL[':ac']=$alm_clave; }
    if ($zona_codigo!==''){ $cL.=" AND alm_codigo=:zc"; $pL[':zc']=$zona_codigo; }
    $stL=$pdo->prepare("SELECT DISTINCT lote FROM v_kardex_doble_partida WHERE $cL AND lote<>'' ORDER BY lote LIMIT 300");
    $stL->execute($pL);
    $lote_opts=$stL->fetchAll(PDO::FETCH_COLUMN);
  }catch(Throwable $e){}
}

/* ===== WHERE principal ===== */
$where=[]; $p=[];
if($empresa_id!==''){ $where[]='empresa_id=:emp'; $p[':emp']=$empresa_id; }
if($alm_clave!==''){ $where[]='alm_clave=:alm'; $p[':alm']=$alm_clave; }
if($zona_codigo!==''){ $where[]='alm_codigo=:zon'; $p[':zon']=$zona_codigo; }
if($proyecto_id!==''){ $where[]='CAST(proyecto_id AS CHAR)=:pry'; $p[':pry']=$proyecto_id; }
if($proveedor_clv!==''){ /* aplica cuando la vista lo incluya */ }
if($producto_id!==''){ $where[]='producto_id LIKE :prd'; $p[':prd']='%'.$producto_id.'%'; }
if($lote!==''){ $where[]='lote LIKE :lot'; $p[':lot']='%'.$lote.'%'; }
if($tipo_tx!==''){ $where[]='UPPER(tipo_tx)=:ttx'; $p[':ttx']=strtoupper($tipo_tx); }
$where[]='fecha_hora>=:f1 AND fecha_hora<=:f2';
$p[':f1']=$fini.' 00:00:00'; $p[':f2']=$ffin.' 23:59:59';
$wsql='WHERE '.implode(' AND ',$where);

/* ===== Guard anti-scan ===== */
$notes=[];
if($run && $empresa_id===''){ $notes[]="Selecciona una Empresa para ejecutar."; $run=false; }

/* ===== SQL base ===== */
$sqlRows="
SELECT
  fecha_hora,
  tipo_tx,
  COALESCE(empresa_nombre,CAST(empresa_id AS CHAR)) AS empresa,
  alm_nombre,
  zona_nombre,
  '' AS proveedor,  -- cuando la vista lo traiga cámbialo a proveedor_nombre
  proyecto_nombre,
  producto_id,
  producto_nombre,
  lote,
  mov_ori,
  mov_dst
FROM v_kardex_doble_partida
$wsql
ORDER BY fecha_hora DESC, tx_id DESC
LIMIT :lim OFFSET :off
";

/* ===== Depuración ===== */
if($DBG['sql'])     json_out(['sql'=>$sqlRows,'params'=>$p,'limit'=>$limit,'offset'=>$offset]);
if($DBG['explain']){
  $sqlExplain=preg_replace('/LIMIT\s*:\w+\s*OFFSET\s*:\w+/i','',$sqlRows);
  $stE=$pdo->prepare("EXPLAIN ".$sqlExplain); foreach($p as $k=>$v)$stE->bindValue($k,$v); $stE->execute();
  json_out(['plan'=>$stE->fetchAll(),'params'=>$p]);
}

/* ===== CSV ===== */
if($run && (($_GET['export']??'')==='csv')){
  $fname='kardex_'.date('Ymd_His').'.csv';
  header('Content-Type:text/csv; charset=utf-8');
  header('Content-Disposition:attachment;filename='.$fname);
  $out=fopen('php://output','w');
  fputcsv($out,['Fecha','Tipo Movimiento','Empresa','Almacén','Zona','Proveedor','Proyecto','Producto','Descripción','Lote','Mov Origen','Mov Destino']);
  $sqlCsv=str_replace('LIMIT :lim OFFSET :off','',$sqlRows);
  $st=$pdo->prepare($sqlCsv); foreach($p as $k=>$v)$st->bindValue($k,$v); $st->execute();
  while($r=$st->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
  fclose($out); exit;
}

/* ===== Ejecución ===== */
$rows=[]; $has_next=false; $has_prev=false;
if($run){
  try{
    $st=$pdo->prepare($sqlRows);
    foreach($p as $k=>$v)$st->bindValue($k,$v);
    $st->bindValue(':lim',$limit+1,PDO::PARAM_INT);
    $st->bindValue(':off',$offset,PDO::PARAM_INT);
    $st->execute();
    $rows=$st->fetchAll();
    $has_next=count($rows)>$limit; if($has_next) array_pop($rows);
    $has_prev=$page>1;
  }catch(Throwable $e){ $notes[]='Error SQL: '.$e->getMessage(); }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro Kardex FC — Kardex Bidireccional ETL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#f9fafb;margin:0}
.container{max-width:1500px;margin:24px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
h2{margin:0 0 12px}
.grid{display:grid;grid-template-columns:repeat(8,minmax(140px,1fr));gap:8px}
select,input,button{padding:8px;border:1px solid #cfd3d8;border-radius:8px;font-size:12px;background:#fff}
.table-wrap{overflow:auto;max-height:65vh;border:1px solid #e5e7eb;border-radius:8px}
table{border-collapse:collapse;width:100%;font-size:11px}
th,td{padding:8px;border-bottom:1px solid #eee;white-space:nowrap;text-align:left}
th{background:#fafafa;position:sticky;top:0}
.note{background:#ecfeff;border:1px solid #06b6d4;border-radius:8px;padding:8px;margin:8px 0}
.badge{display:inline-block;background:#eef;border:1px solid #99f;color:#003;border-radius:999px;padding:4px 8px;margin-right:8px}
</style>
<script>
function reloadPartial(setObj){
  const f=document.getElementById('f');
  const params=new URLSearchParams(new FormData(f));
  params.delete('run'); // no ejecutar todavía
  for(const[k,v] of Object.entries(setObj)){
    if(v===null) params.delete(k); else params.set(k,v);
  }
  window.location.search=params.toString();
}
</script>
</head>
<body>
<div class="container">
  <h2>AssistPro Kardex FC — Kardex Bidireccional ETL</h2>

  <div class="badge">DB: assistpro_etl_fc</div>
  <?php foreach(($notes??[]) as $n){ echo '<div class="note">'.htmlspecialchars($n).'</div>'; } ?>

  <form method="get" id="f">
    <input type="hidden" name="run" value="1">
    <div class="grid">
      <!-- Empresa -->
      <select name="empresa_id" onchange="reloadPartial({empresa_id:this.value,alm_clave:null,zona_codigo:null,proyecto_id:null,proveedor:null,producto_id:null,lote:null})">
        <option value="">Empresa</option>
        <?php foreach($empList as $e){ $sel=((string)$empresa_id===(string)$e['empresa_id'])?' selected':''; 
          echo '<option value="'.htmlspecialchars($e['empresa_id']).'"'.$sel.'>'.htmlspecialchars($e['empresa_id'].' — '.$e['des_cia']).'</option>'; } ?>
      </select>

      <!-- Almacén -->
      <select name="alm_clave" onchange="reloadPartial({alm_clave:this.value,zona_codigo:null,proyecto_id:null})">
        <option value="">Almacén</option>
        <?php foreach($alm_opts as $a){ $sel=((string)$alm_clave===(string)$a['clave'])?' selected':''; 
          echo '<option value="'.htmlspecialchars($a['clave']).'"'.$sel.'>'.htmlspecialchars($a['clave'].' — '.$a['nombre']).'</option>'; } ?>
      </select>

      <!-- Zona -->
      <select name="zona_codigo">
        <option value="">Zona</option>
        <?php foreach($zona_opts as $z){ $sel=((string)$zona_codigo===(string)$z['codigo'])?' selected':''; 
          echo '<option value="'.htmlspecialchars($z['codigo']).'"'.$sel.'>'.htmlspecialchars($z['codigo'].' — '.$z['nombre']).'</option>'; } ?>
      </select>

      <!-- Proyecto -->
      <select name="proyecto_id">
        <option value="">Proyecto</option>
        <?php foreach($proy_opts as $p){ $sel=((string)$proyecto_id===(string)$p['Id'])?' selected':''; 
          echo '<option value="'.htmlspecialchars($p['Id']).'"'.$sel.'>'.htmlspecialchars($p['Cve_Proyecto'].' — '.$p['Des_Proyecto']).'</option>'; } ?>
      </select>

      <!-- Proveedor -->
      <select name="proveedor">
        <option value="">Proveedor</option>
        <?php foreach($prov_opts as $v){ $sel=((string)$proveedor_clv===(string)$v['clave'])?' selected':''; 
          echo '<option value="'.htmlspecialchars($v['clave']).'"'.$sel.'>'.htmlspecialchars($v['clave'].' — '.$v['nombre']).'</option>'; } ?>
      </select>

      <!-- Tipo movimiento -->
      <select name="tipo_tx">
        <option value="">Tipo (todos)</option>
        <?php foreach($tipos_opts as $t){ $sel=($tipo_tx===$t)?' selected':''; echo '<option value="'.htmlspecialchars($t).'"'.$sel.'>'.htmlspecialchars($t).'</option>'; } ?>
      </select>

      <!-- Producto -->
      <input list="productos" name="producto_id" placeholder="Producto" value="<?php echo htmlspecialchars($producto_id); ?>">
      <datalist id="productos">
        <?php foreach($prod_opts as $po){ echo '<option value="'.htmlspecialchars($po['cve']).'">'.htmlspecialchars($po['cve'].' — '.$po['nom']).'</option>'; } ?>
      </datalist>

      <!-- Lote (datalist depende de Producto) -->
      <input list="lotes" name="lote" placeholder="Lote/Serie" value="<?php echo htmlspecialchars($lote); ?>">
      <datalist id="lotes">
        <?php foreach($lote_opts as $lv){ echo '<option value="'.htmlspecialchars($lv).'">'; } ?>
      </datalist>

      <!-- Fechas -->
      <input type="date" name="fini" value="<?php echo htmlspecialchars($fini); ?>">
      <input type="date" name="ffin" value="<?php echo htmlspecialchars($ffin); ?>">
    </div>
    <div style="margin-top:8px"><button type="submit">Filtrar</button></div>
  </form>

  <div class="table-wrap" style="margin-top:10px">
    <table>
      <thead><tr>
        <th>Fecha</th><th>Tipo Movimiento</th><th>Empresa</th><th>Almacén</th><th>Zona</th><th>Proveedor</th>
        <th>Proyecto</th><th>Producto</th><th>Descripción</th><th>Lote</th><th>Mov Origen</th><th>Mov Destino</th>
      </tr></thead>
      <tbody>
      <?php if(!$run): ?>
        <tr><td colspan="12" style="color:#6b7280">Selecciona filtros y presiona Filtrar.</td></tr>
      <?php elseif(!$rows): ?>
        <tr><td colspan="12">Sin resultados para el filtro actual.</td></tr>
      <?php else: foreach($rows as $r): ?>
        <tr>
          <td><?=htmlspecialchars($r['fecha_hora'])?></td>
          <td><?=htmlspecialchars($r['tipo_tx'])?></td>
          <td><?=htmlspecialchars($r['empresa'])?></td>
          <td><?=htmlspecialchars($r['alm_nombre'])?></td>
          <td><?=htmlspecialchars($r['zona_nombre'])?></td>
          <td><?=htmlspecialchars($r['proveedor'])?></td>
          <td><?=htmlspecialchars($r['proyecto_nombre'])?></td>
          <td><?=htmlspecialchars($r['producto_id'])?></td>
          <td><?=htmlspecialchars($r['producto_nombre'])?></td>
          <td><?=htmlspecialchars($r['lote'])?></td>
          <td><?=htmlspecialchars($r['mov_ori'])?></td>
          <td><?=htmlspecialchars($r['mov_dst'])?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
