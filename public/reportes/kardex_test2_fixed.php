
<?php
// ===== Backend sanity (no UI changes) =====
if (!defined('E_USER_DEPRECATED')) { /* noop */ }
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');

// PDO bootstrap (keeps your existing app/db.php if present)
if (!isset($pdo) || !($pdo instanceof PDO)) {
  $dbPath = __DIR__ . '/../app/db.php';
  if (file_exists($dbPath)) {
    require_once $dbPath;
  }
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  try {
    $pdo = new PDO(
      'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
      'root', '',
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]
    );
  } catch (Throwable $e) {
    // Evitar HTML roto en dev
    die('Error de conexión: ' . $e->getMessage());
  }
}

// Defaults to avoid undefined-variable warnings
if (!isset($TITLE))       $TITLE = '';
if (!isset($TITLE_NAME))  $TITLE_NAME = '';
if (!isset($STITLE))      $STITLE = '';
if (!isset($TITLES))      $TITLES = '';
if (!isset($sqlDetalle))  $sqlDetalle = '';
if (!isset($sqlResumen))  $sqlResumen = '';
if (!isset($sqlFiltros))  $sqlFiltros = '';
if (!isset($rows))        $rows = [];
if (!isset($kpi))         $kpi = [];
// ==========================================
?>

// ==== PDO Bootstrap (no cambia diseño) ====
if (!isset($pdo) || !($pdo instanceof PDO)) {
  $dbPath = __DIR__ . '/../app/db.php';
  if (file_exists($dbPath)) {
    require_once $dbPath;
  }
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  // Fallback directo a assistpro_etl_fc si no vino $pdo desde app/db.php
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
    'root', '',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
  );
}
// =========================================
?>

// public/kardex_test.php
require_once __DIR__ . '/../app/db.php'; // Debe exponer $pdo (PDO MySQL)
$TITLE = 'Kardex Bidireccional (Test)';

/* =========================== Parámetros =========================== */
$empresa_id   = (int)($_GET['empresa_id']   ?? 1);
$producto_id  = (int)($_GET['producto_id']  ?? 0);
$tipo_tx      = trim($_GET['tipo_tx']       ?? '');
$lote         = trim($_GET['lote']          ?? '');
$serie        = trim($_GET['serie']         ?? '');
$alm_ori_id   = (int)($_GET['alm_ori_id']   ?? 0);
$alm_dst_id   = (int)($_GET['alm_dst_id']   ?? 0);
$ubi_ori_id   = (int)($_GET['ubi_ori_id']   ?? 0);
$ubi_dst_id   = (int)($_GET['ubi_dst_id']   ?? 0);
$zona_ori_id  = (int)($_GET['zona_ori_id']  ?? 0);
$zona_dst_id  = (int)($_GET['zona_dst_id']  ?? 0);
$usuario_id   = (int)($_GET['usuario_id']   ?? 0);
$fecha_corte  = trim($_GET['fecha_corte']   ?? date('Y-m-d'));
$ajuste_id    = (int)($_GET['ajuste_id']    ?? 0);     // sólo se usa si existe en la vista
$export       = trim($_GET['export']        ?? '');

/* =========================== Helpers ============================== */
function qAll(PDO $pdo, string $sql, array $p = []) {
  $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_ASSOC);
}
function qOne(PDO $pdo, string $sql, array $p = []) {
  $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchColumn();
}
function addEq(&$sql,&$p,$expr,$param,$value){
  if($value!=='' && $value!==null && $value!==0){
    $sql.=" AND $expr = :$param"; $p[":$param"]=$value;
  }
}

/* ====== Descubrir si la vista tiene columna ajuste_id (modo flexible) ====== */
$hasAjuste = (int)qOne(
  $pdo,
  "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'v_kardex_doble_partida'
     AND COLUMN_NAME = 'ajuste_id'"
) > 0;

/* =========================== Catálogos ============================ */
$productos = qAll(
  $pdo,
  "SELECT DISTINCT producto_id id
     FROM v_kardex_doble_partida
    WHERE empresa_id=:e
    ORDER BY 1",
  [':e'=>$empresa_id]
);

$tipos = ['ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE'];

$ajustes = $hasAjuste
  ? qAll($pdo, "SELECT id, CONCAT(clave,' - ',nombre) txt FROM c_ajuste_existencias WHERE empresa_id=:e AND activo=1 ORDER BY nombre", [':e'=>$empresa_id])
  : [];

/* ========== Filtros base y joins (siempre desde la vista existente) ========= */
$sqlBase = "
FROM v_kardex_doble_partida k
LEFT JOIN c_almacen   a1 ON a1.id = k.alm_ori_id
LEFT JOIN c_almacen   a2 ON a2.id = k.alm_dst_id
LEFT JOIN c_ubicacion u1 ON u1.id = k.ubi_ori_id
LEFT JOIN c_ubicacion u2 ON u2.id = k.ubi_dst_id
LEFT JOIN th_kardex   th ON th.id = k.tx_id           -- para proyecto y ref
";
if ($hasAjuste) {
  $sqlBase .= " LEFT JOIN c_ajuste_existencias aj ON aj.id = k.ajuste_id ";
}
$sqlBase .= " WHERE k.empresa_id = :e ";
$p = [':e'=>$empresa_id];

addEq($sqlBase,$p,'k.producto_id','p',$producto_id);
addEq($sqlBase,$p,'k.tipo_tx','t',$tipo_tx);
addEq($sqlBase,$p,'k.lote','l',$lote);
addEq($sqlBase,$p,'k.serie','s',$serie);
addEq($sqlBase,$p,'k.alm_ori_id','ao',$alm_ori_id);
addEq($sqlBase,$p,'k.alm_dst_id','ad',$alm_dst_id);
addEq($sqlBase,$p,'k.ubi_ori_id','uo',$ubi_ori_id);
addEq($sqlBase,$p,'k.ubi_dst_id','ud',$ubi_dst_id);
addEq($sqlBase,$p,'u1.zona_id','zo',$zona_ori_id);
addEq($sqlBase,$p,'u2.zona_id','zd',$zona_dst_id);
addEq($sqlBase,$p,'k.usuario_id','usr',$usuario_id);
if ($hasAjuste) addEq($sqlBase,$p,'k.ajuste_id','aj',$ajuste_id);

/* =========================== Consulta principal =================== */
$selectAjuste = $hasAjuste
  ? "k.ajuste_id, aj.clave AS ajuste_clave, aj.nombre AS ajuste_nombre,"
  : "NULL AS ajuste_id, NULL AS ajuste_clave, NULL AS ajuste_nombre,";

$sqlMain = "
SELECT
  k.tx_id,
  k.fecha_hora,
  k.tipo_tx,
  th.proyecto_id,
  k.producto_id,
  k.uom, k.lote, k.serie,

  $selectAjuste

  -- ORIGEN
  k.alm_ori_id, COALESCE(a1.nombre, CONCAT('ALM ',k.alm_ori_id)) AS alm_ori,
  k.ubi_ori_id, COALESCE(u1.clave,  CONCAT('BL ',k.ubi_ori_id))  AS ubi_ori,
  u1.zona_id AS zona_ori_id,
  k.stock_ini_ori, k.mov_ori, k.stock_fin_ori,

  -- DESTINO
  k.alm_dst_id, COALESCE(a2.nombre, CONCAT('ALM ',k.alm_dst_id)) AS alm_dst,
  k.ubi_dst_id, COALESCE(u2.clave,  CONCAT('BL ',k.ubi_dst_id))  AS ubi_dst,
  u2.zona_id AS zona_dst_id,
  k.stock_ini_dst, k.mov_dst, k.stock_fin_dst,

  (COALESCE(k.mov_ori,0)+COALESCE(k.mov_dst,0)) AS mov_signed,

  k.usuario_id,
  th.referencia,
  k.notas
$sqlBase
ORDER BY k.fecha_hora, k.tx_id
";
$rows = qAll($pdo,$sqlMain,$p);

/* =========================== Cards / KPIs ========================= */
$fc_start = $fecha_corte.' 00:00:00';
$fc_end   = $fecha_corte.' 23:59:59';

$whereR = "empresa_id=:e";
$pr = [':e'=>$empresa_id];
if($producto_id>0){ $whereR.=" AND producto_id=:p"; $pr[':p']=$producto_id; }
if($lote!==''){     $whereR.=" AND lote=:l";        $pr[':l']=$lote; }
if($serie!==''){    $whereR.=" AND serie=:s";       $pr[':s']=$serie; }
if($alm_ori_id>0){  $whereR.=" AND alm_ori_id=:ao"; $pr[':ao']=$alm_ori_id; }
if($alm_dst_id>0){  $whereR.=" AND alm_dst_id=:ad"; $pr[':ad']=$alm_dst_id; }
if($ubi_ori_id>0){  $whereR.=" AND ubi_ori_id=:uo"; $pr[':uo']=$ubi_ori_id; }
if($ubi_dst_id>0){  $whereR.=" AND ubi_dst_id=:ud"; $pr[':ud']=$ubi_dst_id; }
if($zona_ori_id>0){ $whereR.=" AND ubi_ori_id IN (SELECT id FROM c_ubicacion WHERE zona_id=:zo)"; $pr[':zo']=$zona_ori_id; }
if($zona_dst_id>0){ $whereR.=" AND ubi_dst_id IN (SELECT id FROM c_ubicacion WHERE zona_id=:zd)"; $pr[':zd']=$zona_dst_id; }
if($usuario_id>0){  $whereR.=" AND usuario_id=:usr"; $pr[':usr']=$usuario_id; }
if($hasAjuste && $ajuste_id>0){ $whereR.=" AND ajuste_id=:aj"; $pr[':aj']=$ajuste_id; }

$inicial = (float)qOne($pdo,
  "SELECT COALESCE(SUM(mov_ori+mov_dst),0)
     FROM v_kardex_doble_partida
    WHERE $whereR AND fecha_hora < :c",
  $pr + [':c'=>$fc_start]
);

$entradas = (float)qOne($pdo,
  "SELECT COALESCE(SUM(mov_ori+mov_dst),0)
     FROM v_kardex_doble_partida
    WHERE $whereR AND fecha_hora BETWEEN :a AND :b AND tipo_tx='ENTRADA'",
  $pr + [':a'=>$fc_start, ':b'=>$fc_end]
);

$salidas = (float)qOne($pdo,
  "SELECT COALESCE(-SUM(mov_ori+mov_dst),0)
     FROM v_kardex_doble_partida
    WHERE $whereR AND fecha_hora BETWEEN :a AND :b AND tipo_tx='SALIDA'",
  $pr + [':a'=>$fc_start, ':b'=>$fc_end]
);

$aj_total = (float)qOne($pdo,
  "SELECT COALESCE(SUM(mov_ori+mov_dst),0)
     FROM v_kardex_doble_partida
    WHERE $whereR AND fecha_hora BETWEEN :a AND :b AND tipo_tx='AJUSTE'",
  $pr + [':a'=>$fc_start, ':b'=>$fc_end]
);

$final = $inicial + $entradas - $salidas + $aj_total;

/* =============== Export CSV (detalle + KPIs arriba) =============== */
if ($export==='csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=kardex_test_'.date('Ymd_His').'.csv');
  $out=fopen('php://output','w');
  fputcsv($out,['Kardex Bidireccional (Test)']);
  fputcsv($out,['Empresa',$empresa_id,'Fecha corte',$fecha_corte]);
  fputcsv($out,['Stock Inicial',$inicial]);
  fputcsv($out,['Entradas (día)',$entradas]);
  fputcsv($out,['Salidas (día)',$salidas]);
  fputcsv($out,['Ajustes (día)',$aj_total]);
  fputcsv($out,['Stock Final',$final]);
  fputcsv($out,[]);

  $head = ['Fecha','Tipo','Proyecto','Producto','UOM','Lote','Serie'];
  if($hasAjuste) { $head[]='Ajuste Id'; $head[]='Ajuste'; }
  array_push($head,'Alm Ori','BL Ori','Zona Ori','StockIniOri','MovOri','StockFinOri',
                    'Alm Dst','BL Dst','Zona Dst','StockIniDst','MovDst','StockFinDst',
                    'Mov Firmada','Usuario','Referencia','Notas');
  fputcsv($out,$head);

  foreach($rows as $r){
    $row = [
      $r['fecha_hora'],$r['tipo_tx'],$r['proyecto_id']??'',
      $r['producto_id'],$r['uom'],$r['lote'],$r['serie']
    ];
    if($hasAjuste){ $row[]=$r['ajuste_id']; $row[]=$r['ajuste_nombre']; }
    array_push($row,
      $r['alm_ori'].' ('.$r['alm_ori_id'].')',$r['ubi_ori'].' ('.$r['ubi_ori_id'].')',$r['zona_ori_id'],
      $r['stock_ini_ori'],$r['mov_ori'],$r['stock_fin_ori'],
      $r['alm_dst'].' ('.$r['alm_dst_id'].')',$r['ubi_dst'].' ('.$r['ubi_dst_id'].')',$r['zona_dst_id'],
      $r['stock_ini_dst'],$r['mov_dst'],$r['stock_fin_dst'],
      $r['mov_signed'],$r['usuario_id'],$r['referencia'],$r['notas']
    );
    fputcsv($out,$row);
  }
  fclose($out); exit;
}

/* =============================== HTML ============================= */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?=htmlspecialchars($TITLE)?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root{--blue:#0d6efd;--green:#059669;--amber:#b45309;--red:#dc2626;--slate:#334155}
*{box-sizing:border-box}
body{font-family:system-ui,Arial,sans-serif;font-size:12px;background:#f8fafc;margin:0}
header{background:#f5f7fb;border-bottom:1px solid #e5e7eb;padding:10px 14px}
h1{margin:0;font-size:18px;color:#0d3b66}
.container{padding:12px 14px}
.filters{display:grid;grid-template-columns:repeat(10,minmax(140px,1fr));gap:8px;align-items:end}
label{display:block;font-weight:600;margin-bottom:2px;color:#334155}
input,select{width:100%;padding:7px 8px;font-size:12px;border:1px solid #cbd5e1;border-radius:6px;background:#fff}
.btn{display:inline-block;padding:7px 10px;border-radius:6px;text-decoration:none;border:1px solid var(--blue);background:var(--blue);color:#fff;font-weight:600}
.btn.sec{background:#fff;color:var(--blue)}
.cards{display:grid;grid-template-columns:repeat(4,minmax(220px,1fr));gap:10px;margin-top:12px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px}
.card h3{margin:0 0 6px 0;font-size:13px;color:var(--slate)}
.big{font-size:20px;font-weight:800}
.sub{color:#64748b}
.kpi-green{color:var(--green)} .kpi-amber{color:var(--amber)} .kpi-red{color:#dc2626}
.tablewrap{overflow:auto;border:1px solid #e5e7eb;border-radius:8px;margin-top:12px}
table{border-collapse:separate;border-spacing:0;width:100%;min-width:2600px}
th,td{padding:7px 8px;border-bottom:1px solid #e5e7eb;white-space:nowrap}
th{position:sticky;top:0;background:#eef2f7;z-index:1;font-weight:700;color:#334155}
.group{background:#e8f3ff}
.right{text-align:right}
.badge{padding:2px 6px;border-radius:999px;font-weight:700}
.badge.E{background:#dcfce7;color:#166534} /* ENTRADA */
.badge.S{background:#fee2e2;color:#991b1b} /* SALIDA */
.badge.T{background:#e0e7ff;color:#3730a3} /* TRANSF */
.badge.A{background:#ffedd5;color:#9a3412} /* AJUSTE */
</style>
</head>
<body>
<header><h1><?=$TITLE?></h1></header>

<div class="container">
<form method="get" class="filters">
  <div><label>Empresa</label><input type="number" name="empresa_id" value="<?=$empresa_id?>"></div>
  <div><label>Producto</label>
    <select name="producto_id">
      <option value="0">Todos</option>
      <?php foreach($productos as $x): ?>
        <option value="<?=$x['id']?>" <?=$producto_id==$x['id']?'selected':''?>><?=$x['id']?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div><label>Tipo</label>
    <select name="tipo_tx">
      <option value="">Todos</option>
      <?php foreach($tipos as $t): ?>
        <option value="<?=$t?>" <?=$tipo_tx===$t?'selected':''?>><?=$t?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if($hasAjuste): ?>
  <div><label>Ajuste</label>
    <select name="ajuste_id">
      <option value="0">Todos</option>
      <?php foreach($ajustes as $a): ?>
        <option value="<?=$a['id']?>" <?=$ajuste_id==$a['id']?'selected':''?>><?=$a['txt']?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div><label>Lote</label><input name="lote" value="<?=htmlspecialchars($lote)?>"></div>
  <div><label>Serie</label><input name="serie" value="<?=htmlspecialchars($serie)?>"></div>
  <div><label>Almacén Origen</label><input type="number" name="alm_ori_id" value="<?=$alm_ori_id?>"></div>
  <div><label>BL Origen</label><input type="number" name="ubi_ori_id" value="<?=$ubi_ori_id?>"></div>
  <div><label>Almacén Destino</label><input type="number" name="alm_dst_id" value="<?=$alm_dst_id?>"></div>
  <div><label>BL Destino</label><input type="number" name="ubi_dst_id" value="<?=$ubi_dst_id?>"></div>
  <div><label>Zona Origen</label><input type="number" name="zona_ori_id" value="<?=$zona_ori_id?>"></div>
  <div><label>Zona Destino</label><input type="number" name="zona_dst_id" value="<?=$zona_dst_id?>"></div>
  <div><label>Usuario</label><input type="number" name="usuario_id" value="<?=$usuario_id?>"></div>
  <div><label>Fecha corte</label><input type="date" name="fecha_corte" value="<?=htmlspecialchars($fecha_corte)?>"></div>

  <div><label>&nbsp;</label><button class="btn" type="submit">Aplicar</button></div>
  <div><label>&nbsp;</label><a class="btn sec" href="?empresa_id=<?=$empresa_id?>&fecha_corte=<?=htmlspecialchars($fecha_corte)?>">Limpiar</a></div>
  <div><label>&nbsp;</label><a class="btn" href="?<?=http_build_query(array_merge($_GET,['export'=>'csv']))?>">Exportar CSV</a></div>
</form>

<!-- CARDS -->
<div class="cards">
  <div class="card">
    <h3>Stock Inicial</h3>
    <div class="big"><?=number_format($inicial,4)?></div>
    <div class="sub">Instantánea al <b><?=$fecha_corte?></b> 00:00</div>
  </div>
  <div class="card">
    <h3>Entradas (día)</h3>
    <div class="big kpi-green"><?=number_format($entradas,4)?></div>
    <div class="sub">Tipo = ENTRADA</div>
  </div>
  <div class="card">
    <h3>Salidas (día)</h3>
    <div class="big kpi-red"><?=number_format($salidas,4)?></div>
    <div class="sub">Tipo = SALIDA</div>
  </div>
  <div class="card">
    <h3>Stock Final</h3>
    <div class="big kpi-amber"><?=number_format($final,4)?></div>
    <div class="sub">= Inicial + Entradas − Salidas + Ajustes</div>
  </div>
</div>

<!-- GRILLA -->
<div class="tablewrap">
<table>
  <thead>
    <tr class="group">
      <th colspan="<?= $hasAjuste ? 13 : 11 ?>">Transacción</th>
      <th colspan="6">ORIGEN</th>
      <th colspan="6">DESTINO</th>
      <th colspan="3">Control</th>
    </tr>
    <tr>
      <th>Fecha</th><th>Tipo</th><th>Proyecto</th>
      <th>Producto</th><th>UOM</th><th>Lote</th><th>Serie</th>
      <?php if($hasAjuste): ?>
        <th>Aj Id</th><th>Ajuste</th>
      <?php endif; ?>
      <th>Mov Firma</th><th>Referencia</th><th>Notas</th>

      <th>Alm Ori</th><th>BL Ori</th><th>Zona Ori</th>
      <th>Stock Ini Ori</th><th>Mov Ori</th><th>Stock Fin Ori</th>

      <th>Alm Dst</th><th>BL Dst</th><th>Zona Dst</th>
      <th>Stock Ini Dst</th><th>Mov Dst</th><th>Stock Fin Dst</th>

      <th>Tx Id</th><th>Usuario</th><th>—</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($rows as $r): 
    $badge = 'A';
    if($r['tipo_tx']==='ENTRADA') $badge='E';
    elseif($r['tipo_tx']==='SALIDA') $badge='S';
    elseif($r['tipo_tx']==='TRANSFERENCIA') $badge='T';
  ?>
    <tr>
      <td><?=htmlspecialchars($r['fecha_hora'])?></td>
      <td><span class="badge <?=$badge?>"><?=htmlspecialchars($r['tipo_tx'])?></span></td>
      <td class="right"><?=htmlspecialchars($r['proyecto_id']??'')?></td>

      <td class="right"><?= (int)$r['producto_id'] ?></td>
      <td><?=htmlspecialchars($r['uom'])?></td>
      <td><?=htmlspecialchars($r['lote'])?></td>
      <td><?=htmlspecialchars($r['serie'])?></td>

      <?php if($hasAjuste): ?>
        <td class="right"><?=htmlspecialchars($r['ajuste_id'])?></td>
        <td><?=htmlspecialchars($r['ajuste_nombre'])?></td>
      <?php endif; ?>

      <td class="right"><?=number_format((float)$r['mov_signed'],4)?></td>
      <td><?=htmlspecialchars($r['referencia']??'')?></td>
      <td><?=htmlspecialchars($r['notas']??'')?></td>

      <td><?=htmlspecialchars($r['alm_ori']).' ('.(int)$r['alm_ori_id'].')'?></td>
      <td><?=htmlspecialchars($r['ubi_ori']).' ('.(int)$r['ubi_ori_id'].')'?></td>
      <td class="right"><?=htmlspecialchars($r['zona_ori_id']??'')?></td>
      <td class="right"><?=number_format((float)$r['stock_ini_ori'],4)?></td>
      <td class="right"><?=number_format((float)$r['mov_ori'],4)?></td>
      <td class="right"><?=number_format((float)$r['stock_fin_ori'],4)?></td>

      <td><?=htmlspecialchars($r['alm_dst']).' ('.(int)$r['alm_dst_id'].')'?></td>
      <td><?=htmlspecialchars($r['ubi_dst']).' ('.(int)$r['ubi_dst_id'].')'?></td>
      <td class="right"><?=htmlspecialchars($r['zona_dst_id']??'')?></td>
      <td class="right"><?=number_format((float)$r['stock_ini_dst'],4)?></td>
      <td class="right"><?=number_format((float)$r['mov_dst'],4)?></td>
      <td class="right"><?=number_format((float)$r['stock_fin_dst'],4)?></td>

      <td class="right"><?= (int)$r['tx_id'] ?></td>
      <td class="right"><?= (int)$r['usuario_id'] ?></td>
      <td></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

</div>
</body>
</html>
