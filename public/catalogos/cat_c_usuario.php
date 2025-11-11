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
    $st = $pdo->prepare("SELECT column_name,data_type,column_type,is_nullable
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
    // Reglas rápidas por nombre
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
        $base   = substr($col, 0, -3); // ej: almacen_tipo
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

$pdo      = db_pdo();
$tabla    = 'c_usuario';
$titulo   = 'Usuario';
$cols     = array (
  0 => 'cve_usuario',
  1 => 'cve_cia',
  2 => 'nombre_completo',
  3 => 'email',
  4 => 'perfil',
  5 => 'des_usuario',
  6 => 'fec_ingreso',
  7 => 'pwd_usuario',
  8 => 'ban_usuario',
  9 => 'status',
  10 => 'Activo',
  11 => 'timestamp',
  12 => 'identifier',
  13 => 'image_url',
  14 => 'es_cliente',
  15 => 'cve_almacen',
  16 => 'cve_cliente',
  17 => 'cve_proveedor',
  18 => 'Id_Fcm',
  19 => 'web_apk',
);
$friendly = array (
  'id_user' => 'Id User',
  'cve_usuario' => 'Cve Usuario',
  'cve_cia' => 'Cve Cia',
  'nombre_completo' => 'Nombre Completo',
  'email' => 'Email',
  'perfil' => 'Perfil',
  'des_usuario' => 'Des Usuario',
  'fec_ingreso' => 'Fec Ingreso',
  'pwd_usuario' => 'Pwd Usuario',
  'ban_usuario' => 'Ban Usuario',
  'status' => 'Status',
  'Activo' => 'Activo',
  'timestamp' => 'Timestamp',
  'identifier' => 'Identifier',
  'image_url' => 'Image Url',
  'es_cliente' => 'Es Cliente',
  'cve_almacen' => 'Cve Almacen',
  'cve_cliente' => 'Cve Cliente',
  'cve_proveedor' => 'Cve Proveedor',
  'Id_Fcm' => 'Id Fcm',
  'web_apk' => 'Web Apk',
);
$pk       = 'id';
$meta     = col_meta($pdo,$tabla);

// columnas visibles (sin id, created_at, updated_at)
$viewCols = array_values(array_filter(
    $cols,
    fn($c)=>!in_array($c,['id','created_at','updated_at'],true)
));

// columnas especiales
$hasCreated = in_array('created_at',$cols,true);
$hasUpdated = in_array('updated_at',$cols,true);

// columna para soft delete (activo) – case insensitive
$hasActivo  = false;
foreach($meta as $cn => $m){
    if (strtolower($cn)==='activo'){ $hasActivo=true; break; }
}
$activoCol = $hasActivo ? 'activo' : null;

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
    $id = (int)$_GET['del'];
    try {
        if ($hasActivo) {
            $pdo->prepare("UPDATE $tabla SET $activoCol=0 WHERE $pk=?")->execute([$id]);
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
    $id = (int)$_GET['rec'];
    if ($hasActivo) {
        try {
            $pdo->prepare("UPDATE $tabla SET $activoCol=1 WHERE $pk=?")->execute([$id]);
            header("Location: cat_$tabla.php?show_inactivos=1");
            exit;
        } catch (Throwable $e) {
            $actionError = friendly_error($e, 'No fue posible recuperar el registro');
        }
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['op']??'')==='save') {
    $id   = (int)($_POST['__id']??0);
    $data = [];

    foreach($cols as $c){
        if (in_array($c,['id','created_at','updated_at'],true)) continue;
        if ($c==='clave' && $id>0) continue; // clave no editable

        $val = $_POST[$c] ?? null;

        // fecha alta automática si viene vacía y es nuevo
        if ($id===0 && in_array($c,$autoDateCols,true) && ($val==='' || $val===null)) {
            $val = date('Y-m-d');
        }

        $data[$c] = normalize_value($val, $meta[$c] ?? []);
    }

    try {
        if ($id>0){
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

/* EXPORT CSV (datos) */
if (isset($_GET['export']) && $_GET['export']==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$tabla.'_'.date('Ymd_His').'.csv');
    $out=fopen('php://output','w');
    fputcsv($out,$cols);

    $where = '';
    if ($hasActivo){
        $where = $showInact ? "WHERE $activoCol=0" : "WHERE ($activoCol IS NULL OR $activoCol=1)";
    }
    $rs=$pdo->query("SELECT ".implode(',', $cols)." FROM $tabla $where");
    foreach($rs as $r){
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

                            // fecha alta automática también en import si viene vacía
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

/* ========== Datos para FK selects ========== */

$fkCache=[];
foreach($viewCols as $c){
    if (substr($c,-3)==='_id'){
        $t = guess_fk_table($pdo,$c);
        if ($t){ $fkCache[$c] = fk_options($pdo,$t); }
    }
}

/* ========== Listado ========== */

$q      = trim((string)($_GET['q']??''));
$where  = [];
$pars   = [];

if ($hasActivo){
    $where[] = $showInact ? "$activoCol=0" : "($activoCol IS NULL OR $activoCol=1)";
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
$sql = "SELECT ".implode(',', array_merge([$pk],$viewCols))." FROM $tabla ".
       (empty($where)?'':'WHERE '.implode(' AND ',$where)).
       " ORDER BY $pk DESC LIMIT 500";
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
                <a title="Editar" href="?edit=<?=(int)$r[$pk]?><?=$showInact?'&show_inactivos=1':'';?>">✏️</a>
                <?php if($hasActivo && isset($r[$activoCol]) && (string)$r[$activoCol]!=='1'): ?>
                  <a title="Recuperar" href="?rec=<?=(int)$r[$pk]?>&show_inactivos=1">♻️</a>
                <?php else: ?>
                  <a title="Borrar" href="?del=<?=(int)$r[$pk]?><?=$showInact?'&show_inactivos=1':'';?>" onclick="return confirm('¿Borrar?')">🗑️</a>
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
        $id=(int)$_GET['edit'];
        $st=$pdo->prepare("SELECT ".implode(',',array_merge([$pk],$viewCols))." FROM $tabla WHERE $pk=?");
        $st->execute([$id]); $E=$st->fetch(PDO::FETCH_ASSOC) ?: [];
      ?>
        <div class="edit-block">
          <div class="title-edit">Editar #<?= (int)$id ?></div>
          <form method="post" class="grid3">
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="__id" value="<?= (int)$id ?>">
            <?php foreach($viewCols as $c): $m=$meta[$c]??[]; ?>
              <div class="cell">
                <label><?=h($friendly[$c] ?? nice_label($c))?></label>
                <?php if($c==='clave'): ?>
                  <input name="clave" value="<?=h((string)($E['clave']??''))?>" disabled>
                <?php elseif(substr($c,-3)==='_id' && !empty($fkCache[$c])):
                      $val=(string)($E[$c]??''); ?>
                  <select name="<?=h($c)?>">
                    <option value="">(nulo)</option>
                    <?php foreach($fkCache[$c] as $k=>$txt): ?>
                      <option value="<?=h((string)$k)?>" <?=$val===(string)$k?'selected':''?>><?=h($txt)?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif(strpos(strtolower($m['data_type']??''),'int')!==false): ?>
                  <input type="number" name="<?=h($c)?>" value="<?=h((string)($E[$c]??''))?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['decimal','double','float'])): ?>
                  <input type="number" step="0.01" name="<?=h($c)?>" value="<?=h((string)($E[$c]??''))?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['date'])): ?>
                  <input type="date" name="<?=h($c)?>" value="<?=h((string)($E[$c]??''))?>">
                <?php elseif(in_array(strtolower($m['data_type']??''),['datetime','timestamp'])): ?>
                  <input type="datetime-local" name="<?=h($c)?>" value="<?=h((string)($E[$c]??''))?>">
                <?php elseif($c==='email'||$c==='correo'): ?>
                  <input type="email" name="<?=h($c)?>" value="<?=h((string)($E[$c]??''))?>">
                <?php elseif(in_array($c,['activo','email_ok','requiere_factura','credito_bloqueado'],true)):
                      $val=(string)($E[$c]??''); ?>
                  <select name="<?=h($c)?>">
                    <option value="" <?=$val===''?'selected':''?>>(nulo)</option>
                    <option value="0" <?=$val==='0'?'selected':''?>>0</option>
                    <option value="1" <?=$val==='1'?'selected':''?>>1</option>
                  </select>
                <?php else: ?>
                  <input name="<?=h($c)?>" value="<?=h((string)($E[$c]??''))?>">
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
                <?php if(substr($c,-3)==='_id' && !empty($fkCache[$c])): ?>
                  <select name="<?=h($c)?>">
                    <option value="">(nulo)</option>
                    <?php foreach($fkCache[$c] as $k=>$txt): ?>
                      <option value="<?=h((string)$k)?>"><?=h($txt)?></option>
                    <?php endforeach; ?>
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
                <?php elseif(in_array($c,['activo','email_ok','requiere_factura','credito_bloqueado'],true)): ?>
                  <select name="<?=h($c)?>">
                    <option value="">(nulo)</option>
                    <option value="0">0</option>
                    <option value="1" selected>1</option>
                  </select>
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