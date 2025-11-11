<?php
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

function nice_label(string $c): string {
    $c = str_replace('_',' ', $c);
    $c = preg_replace('/\s+id$/i','', $c);
    return mb_convert_case($c, MB_CASE_TITLE, 'UTF-8');
}

function col_meta(PDO $pdo, string $tabla): array {
    $st = $pdo->prepare("SELECT column_name,data_type,column_type,is_nullable,column_key
                         FROM information_schema.columns
                         WHERE table_schema=DATABASE() AND table_name=?");
    $st->execute([$tabla]);
    $meta = [];
    foreach($st as $r){ $meta[$r['column_name']] = $r; }
    return $meta;
}

function table_exists(PDO $pdo, string $t): bool {
    $st = $pdo->prepare("SELECT 1 FROM information_schema.tables
                         WHERE table_schema=DATABASE() AND table_name=?");
    $st->execute([$t]);
    return (bool)$st->fetchColumn();
}

function guess_fk_table(PDO $pdo, string $col): ?string {
    $map = [
        'empresa_id'       => 'c_empresa',
        'almacen_id'       => 'c_almacen',
        'almacen_tipo_id'  => 'c_almacen_tipo',
        'tipo_id'          => 'c_tipo',
        'cliente_id'       => 'c_cliente',
        'ruta_id'          => 'c_ruta',
        'uom_id'           => 'c_uom',
    ];
    if (isset($map[$col]) && table_exists($pdo,$map[$col])) return $map[$col];

    if (substr($col, -3) === '_id') {
        $base   = substr($col, 0, -3);
        $cands  = ["c_{$base}", "c_{$base}_cat", "{$base}", "{$base}_cat"];
        foreach($cands as $t){
            if (table_exists($pdo,$t)) return $t;
        }
    }
    return null;
}

function fk_options(PDO $pdo, string $table): array {
    if (!$table) return [];
    $cols = $pdo->query("SELECT column_name FROM information_schema.columns
                         WHERE table_schema=DATABASE() AND table_name='$table'
                         ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    if (!$cols) return [];

    $idCol = 'id';
    if (!in_array($idCol, $cols, true)) {
        $idCol = $cols[0];
    }

    $labelCols = [];
    foreach(['clave','nombre','descripcion','name','label'] as $c){
        if (in_array($c,$cols,true)) $labelCols[] = $c;
    }
    if (!$labelCols){
        $lab = array_slice(array_diff($cols,[$idCol]), 0, 1);
        $labelCols = $lab ?: [$idCol];
    }

    $sql = "SELECT $idCol AS id, CONCAT_WS(' - ',".implode(',',$labelCols).") AS txt FROM $table ORDER BY txt";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
}

function normalize_value($val, array $meta) {
    if ($val === '' || $val === null) return null;
    $dt = strtolower($meta['data_type'] ?? '');
    if (strpos($dt,'int') !== false) return (string)$val === '' ? null : (int)$val;
    if (in_array($dt,['decimal','double','float'])) return (string)$val === '' ? null : (float)$val;
    if (in_array($dt,['date','datetime','timestamp','time','year'])) return (string)$val === '' ? null : $val;
    return $val;
}

function friendly_error(Throwable $e, string $ctx): string {
    $msg = $e->getMessage();
    if (stripos($msg,'integrity constraint violation') !== false ||
        stripos($msg,'foreign key') !== false ||
        stripos($msg,'1451') !== false) {
        return $ctx.' (el registro está relacionado con otros datos).';
    }
    return $ctx.': '.$msg;
}

/* PK dinámica */
function get_pk(PDO $pdo, string $tabla, array $cols): string {
    $st = $pdo->prepare("SELECT column_name
                         FROM information_schema.columns
                         WHERE table_schema=DATABASE()
                           AND table_name=?
                           AND column_key='PRI'
                         ORDER BY ordinal_position
                         LIMIT 1");
    $st->execute([$tabla]);
    $pk = $st->fetchColumn();
    if (!$pk) {
        $pk = $cols[0] ?? 'id';
    }
    return $pk;
}

/* Boolean helpers */

function is_bool_like_col(string $name, array $metaCol): bool {
    $ln = strtolower($name);
    $dt = strtolower($metaCol['data_type'] ?? '');
    $ct = strtolower($metaCol['column_type'] ?? '');
    if (strpos($dt,'tinyint') !== false || $dt === 'bit') return true;
    if (($dt === 'char' || $dt === 'varchar') && strpos($ct,'(1)') !== false) return true;
    if (preg_match('/^(es_|ind_|sn_)/',$ln)) return true;
    if (in_array($ln,['activo','status','estatus','baja','cancelado'],true)) return true;
    return false;
}

function bool_style(array $metaCol): string {
    $dt = strtolower($metaCol['data_type'] ?? '');
    $ct = strtolower($metaCol['column_type'] ?? '');
    if (strpos($dt,'int') !== false || $dt === 'bit') return '01';
    if (($dt === 'char' || $dt === 'varchar') && strpos($ct,'(1)') !== false) return 'SN';
    return '01';
}

function detect_softdelete_column(array $meta): ?string {
    foreach($meta as $name=>$m){
        $ln = strtolower($name);
        if (in_array($ln,['activo','status','estatus'],true) ||
            preg_match('/_(activo|status|estatus)$/',$ln)) {
            return $name;
        }
    }
    return null;
}

$pdo      = db_pdo();
$tabla    = 'c_cliente';
$titulo   = 'Cliente';
$cols     = array (
  0 => 'id_cliente',
  1 => 'Cve_Clte',
  2 => 'RazonSocial',
  3 => 'RazonComercial',
  4 => 'CalleNumero',
  5 => 'Colonia',
  6 => 'Ciudad',
  7 => 'Estado',
  8 => 'Pais',
  9 => 'CodigoPostal',
  10 => 'RFC',
  11 => 'Telefono1',
  12 => 'Telefono2',
  13 => 'Telefono3',
  14 => 'ClienteTipo',
  15 => 'ClienteTipo2',
  16 => 'ClienteGrupo',
  17 => 'ClienteFamilia',
  18 => 'CondicionPago',
  19 => 'MedioEmbarque',
  20 => 'ViaEmbarque',
  21 => 'CondicionEmbarque',
  22 => 'ZonaVenta',
  23 => 'cve_ruta',
  24 => 'ID_Proveedor',
  25 => 'Cve_CteProv',
  26 => 'Activo',
  27 => 'Cve_Almacenp',
  28 => 'Fol_Serie',
  29 => 'Contacto',
  30 => 'id_destinatario',
  31 => 'longitud',
  32 => 'latitud',
  33 => 'IdEmpresa',
  34 => 'email_cliente',
  35 => 'Cve_SAP',
  36 => 'Encargado',
  37 => 'Referencia',
  38 => 'credito',
  39 => 'limite_credito',
  40 => 'dias_credito',
  41 => 'credito_actual',
  42 => 'saldo_inicial',
  43 => 'saldo_actual',
  44 => 'validar_gps',
  45 => 'cliente_general',
  46 => 'Id_RegFis',
  47 => 'Id_CFDI',
);
$friendly = array (
  'id_cliente' => 'Id Cliente',
  'Cve_Clte' => 'Cve Clte',
  'RazonSocial' => 'Razonsocial',
  'RazonComercial' => 'Razoncomercial',
  'CalleNumero' => 'Callenumero',
  'Colonia' => 'Colonia',
  'Ciudad' => 'Ciudad',
  'Estado' => 'Estado',
  'Pais' => 'Pais',
  'CodigoPostal' => 'Codigopostal',
  'RFC' => 'Rfc',
  'Telefono1' => 'Telefono1',
  'Telefono2' => 'Telefono2',
  'Telefono3' => 'Telefono3',
  'ClienteTipo' => 'Clientetipo',
  'ClienteTipo2' => 'Clientetipo2',
  'ClienteGrupo' => 'Clientegrupo',
  'ClienteFamilia' => 'Clientefamilia',
  'CondicionPago' => 'Condicionpago',
  'MedioEmbarque' => 'Medioembarque',
  'ViaEmbarque' => 'Viaembarque',
  'CondicionEmbarque' => 'Condicionembarque',
  'ZonaVenta' => 'Zonaventa',
  'cve_ruta' => 'Cve Ruta',
  'ID_Proveedor' => 'Id Proveedor',
  'Cve_CteProv' => 'Cve Cteprov',
  'Activo' => 'Activo',
  'Cve_Almacenp' => 'Cve Almacenp',
  'Fol_Serie' => 'Fol Serie',
  'Contacto' => 'Contacto',
  'id_destinatario' => 'Id Destinatario',
  'longitud' => 'Longitud',
  'latitud' => 'Latitud',
  'IdEmpresa' => 'Idempresa',
  'email_cliente' => 'Email Cliente',
  'Cve_SAP' => 'Cve Sap',
  'Encargado' => 'Encargado',
  'Referencia' => 'Referencia',
  'credito' => 'Credito',
  'limite_credito' => 'Limite Credito',
  'dias_credito' => 'Dias Credito',
  'credito_actual' => 'Credito Actual',
  'saldo_inicial' => 'Saldo Inicial',
  'saldo_actual' => 'Saldo Actual',
  'validar_gps' => 'Validar Gps',
  'cliente_general' => 'Cliente General',
  'Id_RegFis' => 'Id Regfis',
  'Id_CFDI' => 'Id Cfdi',
);
$meta     = col_meta($pdo,$tabla);

// PK real
$pk = get_pk($pdo,$tabla,$cols);

// columnas visibles (no ocultamos PK, sólo timestamps)
$viewCols = array_values(array_filter(
    $cols,
    fn($c)=>!in_array($c,['created_at','updated_at'],true)
));

// columnas especiales
$hasCreated = in_array('created_at',$cols,true);
$hasUpdated = in_array('updated_at',$cols,true);

// columna para soft delete
$activoCol = detect_softdelete_column($meta);
$hasActivo = $activoCol !== null;
$activoStyle = $hasActivo && isset($meta[$activoCol]) ? bool_style($meta[$activoCol]) : '01';
if ($activoStyle === 'SN') {
    $activeVal   = 'S';
    $inactiveVal = 'N';
} else {
    $activeVal   = '1';
    $inactiveVal = '0';
}

// columnas de fecha-alta automáticas (por nombre)
$autoDateCols = [];
foreach($meta as $cn => $m){
    $lc = strtolower($cn);
    if (preg_match('/fec.?alta|fecha.?alta/', $lc)) {
        $autoDateCols[] = $cn;
    }
}

$showInact    = isset($_GET['show_inactivos']) ? 1 : 0;
$actionError  = '';
$msgImport    = '';

/* ========== Acciones básicas (delete / recover / save / export / layout / import) ========== */

if (isset($_GET['del'])) {
    $id = $_GET['del'];
    try {
        if ($hasActivo) {
            $pdo->prepare("UPDATE $tabla SET $activoCol=? WHERE $pk=?")->execute([$inactiveVal,$id]);
        } else {
            $pdo->prepare("DELETE FROM $tabla WHERE $pk=?")->execute([$id]);
        }
        header("Location: cat_$tabla.php".($showInact?'?show_inactivos=1':''));
        exit;
    } catch (Throwable $e) {
        $actionError = friendly_error($e, 'No fue posible eliminar el registro');
    }
}

if (isset($_GET['rec'])) {
    $id = $_GET['rec'];
    if ($hasActivo) {
        try {
            $pdo->prepare("UPDATE $tabla SET $activoCol=? WHERE $pk=?")->execute([$activeVal,$id]);
            header("Location: cat_$tabla.php?show_inactivos=1");
            exit;
        } catch (Throwable $e) {
            $actionError = friendly_error($e, 'No fue posible recuperar el registro');
        }
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['op']??'')==='save') {
    $id    = $_POST['__id'] ?? '';
    $isNew = ($id === '' || $id === '0');
    $data  = [];

    foreach($cols as $c){
        if (in_array($c,['created_at','updated_at'],true)) continue;
        if ($c === $pk && !$isNew) continue; // PK no editable en edición

        $val = $_POST[$c] ?? null;

        // fecha alta automática si viene vacía y es nuevo
        if ($isNew && in_array($c,$autoDateCols,true) && ($val==='' || $val===null)) {
            $val = date('Y-m-d');
        }

        $data[$c] = normalize_value($val, $meta[$c] ?? []);
    }

    try {
        if (!$isNew){
            if ($hasUpdated) { $data['updated_at'] = date('Y-m-d H:i:s'); }
            if ($data){
                $set=[]; $par=[];
                foreach($data as $k=>$v){ $set[]="$k=?"; $par[]=$v; }
                $par[]=$id;
                $pdo->prepare("UPDATE $tabla SET ".implode(',',$set)." WHERE $pk=?")->execute($par);
            }
        } else {
            if ($hasCreated) $data['created_at'] = date('Y-m-d H:i:s');
            if ($hasUpdated) $data['updated_at'] = date('Y-m-d H:i:s');
            if ($data){
                $pdo->prepare(
                    "INSERT INTO $tabla (".implode(',',array_keys($data)).") VALUES (".implode(',',array_fill(0,count($data),'?')).")"
                )->execute(array_values($data));
            }
        }
        header("Location: cat_$tabla.php".($showInact?'?show_inactivos=1':''));
        exit;
    } catch (Throwable $e) {
        $actionError = friendly_error($e, 'No fue posible guardar el registro');
    }
}

/* EXPORT CSV (datos completos) */
if (isset($_GET['export']) && $_GET['export']==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$tabla.'_'.date('Ymd_His').'.csv');
    $out=fopen('php://output','w');
    fputcsv($out,$cols);

    $where = '';
    $pars  = [];
    if ($hasActivo){
        if ($showInact) {
            $where = "WHERE $activoCol <> ?";
            $pars[] = $activeVal;
        } else {
            $where = "WHERE ($activoCol IS NULL OR $activoCol=?)";
            $pars[] = $activeVal;
        }
    }
    $sql = "SELECT ".implode(',', $cols)." FROM $tabla $where";
    $st  = $pdo->prepare($sql);
    $st->execute($pars);
    while($r = $st->fetch(PDO::FETCH_ASSOC)){
        fputcsv($out, array_map(fn($k)=>$r[$k]??'', $cols));
    }
    fclose($out);
    exit;
}

/* LAYOUT CSV (sólo encabezados) */
if (isset($_GET['layout']) && $_GET['layout']==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$tabla.'_layout.csv');
    $out=fopen('php://output','w');
    fputcsv($out,$cols);
    fclose($out);
    exit;
}

/* IMPORT CSV */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['op']??'')==='import' && isset($_FILES['csv'])){
    if (is_uploaded_file($_FILES['csv']['tmp_name'])){
        $fh   = fopen($_FILES['csv']['tmp_name'],'r');
        $hdr  = fgetcsv($fh);
        if ($hdr){
            $hdr   = array_map('trim',$hdr);
            $valid = array_values(array_intersect($hdr,$cols));
            if ($valid){
                $sql = "INSERT INTO $tabla (".implode(',',$valid).") VALUES (".implode(',',array_fill(0,count($valid),'?')).")";
                $st  = $pdo->prepare($sql);
                $pdo->beginTransaction();
                try{
                    while(($row=fgetcsv($fh))!==false){
                        $vals=[];
                        foreach($valid as $c){
                            if ($c==='created_at' || $c==='updated_at'){
                                $vals[] = date('Y-m-d H:i:s');
                                continue;
                            }
                            $pos = array_search($c,$hdr);
                            $val = $pos===false? null : $row[$pos];

                            if (in_array($c,$autoDateCols,true) && ($val==='' || $val===null)) {
                                $val = date('Y-m-d');
                            }

                            $vals[] = normalize_value($val, $meta[$c] ?? []);
                        }
                        $st->execute($vals);
                    }
                    $pdo->commit();
                    $msgImport = 'Importación exitosa.';
                }catch(Throwable $e){
                    $pdo->rollBack();
                    $msgImport = friendly_error($e, 'Error en importación');
                }
            } else {
                $msgImport='El CSV no coincide con las columnas esperadas.';
            }
        } else {
            $msgImport='CSV vacío.';
        }
        fclose($fh);
    }
}

/* ========== Datos para FK selects / bool ========= */

$fkCache=[];
$boolCols=[];
foreach($viewCols as $c){
    if (substr($c,-3)==='_id'){
        $t = guess_fk_table($pdo,$c);
        if ($t){ $fkCache[$c] = fk_options($pdo,$t); }
    }
    if (isset($meta[$c]) && is_bool_like_col($c,$meta[$c])) {
        $boolCols[$c] = bool_style($meta[$c]);
    }
}

/* ========== Listado ========== */

$q      = trim((string)($_GET['q']??''));
$where  = [];
$pars   = [];

if ($hasActivo){
    if ($showInact) {
        $where[] = "$activoCol <> ?";
        $pars[]  = $activeVal;
    } else {
        $where[] = "($activoCol IS NULL OR $activoCol=?)";
        $pars[]  = $activeVal;
    }
}
if ($q!==''){
    $likes=[];
    foreach($viewCols as $c){
        $likes[]="$c LIKE ?";
        $pars[]="%$q%";
    }
    if ($likes) {
        $where[]='('.implode(' OR ',$likes).')';
    }
}
$whereSql = empty($where)? '' : 'WHERE '.implode(' AND ',$where);

$sql = "SELECT ".implode(',', array_unique(array_merge([$pk], $viewCols))).
       " FROM $tabla $whereSql ORDER BY $pk DESC LIMIT 500";
$st  = $pdo->prepare($sql);
$st->execute($pars);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* ========== UI ========== */

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">

  <style>
    :root{
      --primary:#000F9F;
      --muted:#eef1f4;
      --text:#191817;
    }
    .wrap-cat{
      min-height: calc(100vh - 80px);
      display:flex;
      flex-direction:column;
      gap:8px;
      padding:8px 4px 12px 4px;
    }
    .cat-card{
      background:#fff;
      border:1px solid var(--muted);
      border-radius:10px;
      padding:8px 10px 10px 10px;
      box-shadow:0 1px 3px rgba(0,0,0,.04);
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .cat-header{
      display:flex;
      gap:8px;
      align-items:center;
      flex-wrap:wrap;
    }
    .cat-header h3{
      margin:0;
      color:var(--primary);
      font-size:14px;
    }
    .cat-header form{
      margin-left:auto;
      display:flex;
      gap:6px;
      align-items:center;
      flex-wrap:wrap;
    }
    input,select,button{
      padding:4px 6px;
      border:1px solid var(--muted);
      border-radius:6px;
      font-size:10px;
      background:#fff;
    }
    .btn-adv{
      padding:6px 8px;
      border-radius:8px;
      background:var(--primary);
      color:#fff;
      border:0;
      cursor:pointer;
      text-decoration:none;
      font-size:10px;
    }
    .btn-adv.secondary{ background:#1f883d; }
    .btn-adv.soft{ background:#6c757d; }
    .muted{
      color:#6d757d;
      font-size:10px;
    }
    .import-row{
      display:flex;
      gap:6px;
      align-items:center;
      flex-wrap:wrap;
      margin-bottom:2px;
    }
    .table-wrap{
      flex:1;
      min-height:0;
      border:1px solid var(--muted);
      border-radius:8px;
      overflow:auto;
    }
    table{
      border-collapse:collapse;
      width:100%;
    }
    th,td{
      padding:4px 6px;
      border-bottom:1px solid var(--muted);
      text-align:left;
      white-space:nowrap;
      vertical-align:middle;
      font-size:10px;
    }
    thead th{
      position:sticky;
      top:0;
      background:#fff;
    }
    .act a{
      margin-right:6px;
      text-decoration:none;
    }
    .alert-err{
      background:#ffe6e6;
      border:1px solid #f5c2c2;
      color:#842029;
      border-radius:6px;
      padding:4px 6px;
      font-size:10px;
    }
    /* MODAL NUEVO REGISTRO */
    #modal-new{
      display:none;
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.25);
      align-items:center;
      justify-content:center;
      z-index:1050;
    }
    #modal-new .card-new{
      background:#fff;
      border:1px solid var(--muted);
      border-radius:10px;
      padding:10px;
      width:90vw;
      max-width:1100px;
      max-height:90vh;
      display:flex;
      flex-direction:column;
    }
    #modal-new .card-header-new{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
      margin-bottom:6px;
    }
    #modal-new .grid3{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
      gap:8px;
      margin-top:6px;
      overflow-y:auto;
      padding-right:2px;
    }
    #modal-new .cell{
      display:flex;
      flex-direction:column;
      gap:3px;
    }
    #modal-new .cell label{
      font-size:10px;
      color:#6d757d;
    }
    .edit-block{
      border:1px solid var(--muted);
      border-radius:8px;
      padding:8px;
      margin-top:6px;
    }
    .edit-block .title-edit{
      font-weight:700;
      margin-bottom:6px;
      font-size:10px;
    }
    .edit-block .grid3{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
      gap:8px;
      margin-top:4px;
    }
    .edit-block .cell{
      display:flex;
      flex-direction:column;
      gap:3px;
    }
    .edit-block label{
      font-size:10px;
      color:#6d757d;
    }
  </style>

  <script>
    function openNew(){
      var m = document.getElementById('modal-new');
      if (m){ m.style.display='flex'; }
    }
    function closeNew(){
      var m = document.getElementById('modal-new');
      if (m){ m.style.display='none'; }
    }
  </script>

  <div class="wrap-cat">
    <div class="cat-card">

      <div class="cat-header">
        <h3><?=h($titulo)?></h3>
        <form method="get">
          <input type="text" name="q" value="<?=h($q)?>" placeholder="Buscar…">
          <?php if($hasActivo): ?>
            <label class="muted">
              <input type="checkbox" name="show_inactivos" value="1" <?=$showInact?'checked':''?>> Ver inactivos
            </label>
          <?php endif; ?>
          <button>Buscar</button>
          <a class="btn-adv soft" href="?layout=csv">Layout CSV</a>
          <a class="btn-adv" href="?export=csv<?= $showInact?'&show_inactivos=1':''; ?>">Exportar CSV</a>
          <button type="button" class="btn-adv secondary" onclick="openNew()">Nuevo</button>
        </form>
      </div>

      <?php if($actionError): ?>
        <div class="alert-err"><?=h($actionError)?></div>
      <?php endif; ?>

      <?php if($msgImport): ?>
        <div class="muted"><?=h($msgImport)?></div>
      <?php endif; ?>

      <form class="import-row" method="post" enctype="multipart/form-data">
        <input type="hidden" name="op" value="import">
        <input type="file" name="csv" accept=".csv">
        <button>Importar CSV</button>
      </form>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:86px">Acciones</th>
              <?php foreach($viewCols as $c): ?>
                <th><?=h($friendly[$c] ?? nice_label($c))?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td class="act">
                <a title="Editar" href="?edit=<?=urlencode((string)$r[$pk])?><?=$showInact?'&show_inactivos=1':'';?>">✏️</a>
                <?php if($hasActivo):
                    $valAct = isset($r[$activoCol]) ? (string)$r[$activoCol] : ''; ?>
                  <?php if($valAct !== $activeVal): ?>
                    <a title="Recuperar" href="?rec=<?=urlencode((string)$r[$pk])?>&show_inactivos=1">♻️</a>
                  <?php else: ?>
                    <a title="Borrar" href="?del=<?=urlencode((string)$r[$pk])?><?=$showInact?'&show_inactivos=1':'';?>" onclick="return confirm('¿Borrar?')">🗑️</a>
                  <?php endif; ?>
                <?php else: ?>
                  <a title="Borrar" href="?del=<?=urlencode((string)$r[$pk])?><?=$showInact?'&show_inactivos=1':'';?>" onclick="return confirm('¿Borrar?')">🗑️</a>
                <?php endif; ?>
              </td>
              <?php foreach($viewCols as $c): ?>
                <td><?=h((string)($r[$c]??''))?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if(isset($_GET['edit'])):
        $id = $_GET['edit'];
        $st=$pdo->prepare("SELECT ".implode(',', array_unique(array_merge([$pk], $viewCols)))." FROM $tabla WHERE $pk=?");
        $st->execute([$id]); $E=$st->fetch(PDO::FETCH_ASSOC) ?: [];
      ?>
        <div class="edit-block">
          <div class="title-edit">Editar</div>
          <form method="post" class="grid3">
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="__id" value="<?=h((string)($E[$pk] ?? $id))?>">
            <?php foreach($viewCols as $c): $m=$meta[$c]??[]; ?>
              <div class="cell">
                <label><?=h($friendly[$c] ?? nice_label($c))?></label>
                <?php
                  $isPk    = ($c === $pk);
                  $isBool  = isset($boolCols[$c]);
                  $styleB  = $isBool ? $boolCols[$c] : '01';
                  $valCur  = (string)($E[$c] ?? '');
                ?>
                <?php if($isPk): ?>
                  <input name="<?=h($c)?>" value="<?=h($valCur)?>" disabled>
                <?php elseif(isset($fkCache[$c])):
                      $val=(string)($E[$c]??''); ?>
                  <select name="<?=h($c)?>">
                    <option value="">(nulo)</option>
                    <?php foreach($fkCache[$c] as $k=>$txt): ?>
                      <option value="<?=h((string)$k)?>" <?=$val===(string)$k?'selected':''?>><?=h($txt)?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif($isBool):
                    if ($styleB==='SN'){
                        $vTrue='S'; $vFalse='N';
                    } else {
                        $vTrue='1'; $vFalse='0';
                    }
                ?>
                  <select name="<?=h($c)?>">
                    <option value="" <?=$valCur===''?'selected':''?>>(nulo)</option>
                    <option value="<?=$vFalse?>" <?=$valCur===$vFalse?'selected':''?>><?=$vFalse?></option>
                    <option value="<?=$vTrue?>"  <?=$valCur===$vTrue?'selected':''?>><?=$vTrue?></option>
                  </select>
                <?php elseif(strpos(strtolower($m['data_type']??''),'int')!==false): ?>
                  <input type="number" name="<?=h($c)?>" value="<?=h($valCur)?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['decimal','double','float'])): ?>
                  <input type="number" step="0.01" name="<?=h($c)?>" value="<?=h($valCur)?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['date'])): ?>
                  <input type="date" name="<?=h($c)?>" value="<?=h($valCur)?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['datetime','timestamp'])): ?>
                  <input type="datetime-local" name="<?=h($c)?>" value="<?=h($valCur)?>">
                <?php elseif($c==='email'||$c==='correo'): ?>
                  <input type="email" name="<?=h($c)?>" value="<?=h($valCur)?>">
                <?php else: ?>
                  <input name="<?=h($c)?>" value="<?=h($valCur)?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <div class="cell" style="grid-column:1/-1;margin-top:4px">
              <button class="btn-adv secondary">Guardar cambios</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <!-- Modal nuevo -->
      <div id="modal-new" onclick="if(event.target===this)closeNew();">
        <div class="card-new">
          <div class="card-header-new">
            <div class="muted" style="font-weight:700">Nuevo registro</div>
            <button type="button" class="btn-adv" onclick="closeNew()">Cerrar</button>
          </div>
          <form method="post" class="grid3" style="margin-top:4px;">
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="__id" value="0">
            <?php foreach($viewCols as $c): $m=$meta[$c]??[]; ?>
              <div class="cell">
                <label><?=h($friendly[$c] ?? nice_label($c))?></label>
                <?php
                  $isBool  = isset($boolCols[$c]);
                  $styleB  = $isBool ? $boolCols[$c] : '01';
                  if ($styleB==='SN'){ $vTrue='S'; $vFalse='N'; } else { $vTrue='1'; $vFalse='0'; }
                  $defVal  = ($hasActivo && $c === $activoCol) ? $vTrue : '';
                ?>
                <?php if(isset($fkCache[$c])): ?>
                  <select name="<?=h($c)?>">
                    <option value="">(nulo)</option>
                    <?php foreach($fkCache[$c] as $k=>$txt): ?>
                      <option value="<?=h((string)$k)?>"><?=h($txt)?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif($isBool): ?>
                  <select name="<?=h($c)?>">
                    <option value="" <?=$defVal===''?'selected':''?>>(nulo)</option>
                    <option value="<?=$vFalse?>" <?=$defVal===$vFalse?'selected':''?>><?=$vFalse?></option>
                    <option value="<?=$vTrue?>"  <?=$defVal===$vTrue?'selected':''?>><?=$vTrue?></option>
                  </select>
                <?php elseif(strpos(strtolower($m['data_type']??''),'int')!==false): ?>
                  <input type="number" name="<?=h($c)?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['decimal','double','float'])): ?>
                  <input type="number" step="0.01" name="<?=h($c)?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['date'])): ?>
                  <input type="date" name="<?=h($c)?>" value="<?= in_array($c,$autoDateCols,true) ? h(date('Y-m-d')) : '' ?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['datetime','timestamp'])): ?>
                  <input type="datetime-local" name="<?=h($c)?>">
                <?php elseif($c==='email'||$c==='correo'): ?>
                  <input type="email" name="<?=h($c)?>">
                <?php else: ?>
                  <input name="<?=h($c)?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <div class="cell" style="grid-column:1/-1;margin-top:4px">
              <button class="btn-adv secondary">Guardar</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>