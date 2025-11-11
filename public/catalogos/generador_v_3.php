<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$op = $_POST['op'] ?? $_GET['op'] ?? null;
if ($op) {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $dbName = db_val('SELECT DATABASE()');

    if ($op === 'list_tables') {
      $rows = db_all("SHOW TABLE STATUS");
      $out = [];
      foreach ($rows as $r) {
        $name = $r['Name'] ?? '';
        if ($name === '') continue;
        $count = (int) db_val("SELECT COUNT(*) FROM `$name`", [], 0);
        $out[] = [
          'Name'      => $name,
          'Rows'      => $count,
          'Engine'    => $r['Engine'] ?? '',
          'Collation' => $r['Collation'] ?? '',
          'Comment'   => $r['Comment'] ?? '',
        ];
      }
      echo json_encode(['ok'=>true, 'data'=>$out]); exit;
    }

    if ($op === 'columns') {
      $t = trim($_POST['table'] ?? '');
      $cols = db_all("SHOW FULL COLUMNS FROM `$t`");
      echo json_encode(['ok'=>true, 'columns'=>$cols]); exit;
    }

    if ($op === 'set_collation') {
      $t = trim($_POST['table'] ?? '');
      dbq("ALTER TABLE `$t` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
      echo json_encode(['ok'=>true,'msg'=>'Collation actualizado']); exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Operación no válida']); exit;
  } catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Generador v3</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
body{font-size:10px;}
.table-sm td,.table-sm th{padding:.3rem .4rem;}
.dt-right{text-align:right;}
</style>
</head>
<body class="p-3">
<h6>Generador v3 – Tablas (BD: <?php echo htmlspecialchars(db_val('SELECT DATABASE()')); ?>)</h6>
<table class="table table-sm table-striped" id="tbl">
<thead><tr><th>Tabla</th><th class="dt-right">Filas</th><th>Engine</th><th>Collation</th><th>Comentario</th><th>Acciones</th></tr></thead>
<tbody></tbody></table>

<script>
function load(){
 $.post('generador_v3.php',{op:'list_tables'},function(r){
   if(!r.ok){ alert(r.msg||'Error'); return; }
   const tb=$('#tbl tbody'); tb.empty();
   r.data.forEach(x=>{
     tb.append(`<tr>
       <td>${x.Name}</td>
       <td class='dt-right'>${x.Rows}</td>
       <td>${x.Engine||''}</td>
       <td>${x.Collation||''}</td>
       <td>${x.Comment||''}</td>
       <td>
         <button class='btn btn-sm btn-outline-warning' onclick="setColl('${x.Name}')">Unicode_ci</button>
         <button class='btn btn-sm btn-outline-primary' onclick="cols('${x.Name}')">Cols</button>
       </td>
     </tr>`);
   });
 },'json');
}
function setColl(t){
 if(!confirm('¿Convertir '+t+' a utf8mb4_unicode_ci?')) return;
 $.post('generador_v3.php',{op:'set_collation',table:t},r=>{ alert(r.msg||'OK'); load(); },'json');
}
function cols(t){
 $.post('generador_v3.php',{op:'columns',table:t},r=>{
   if(!r.ok){alert(r.msg||'Error');return;}
   console.table(r.columns);
   alert('Columnas mostradas en consola.');
 },'json');
}
load();
</script>
</body></html>
