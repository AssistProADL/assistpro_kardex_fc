<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$op = $_POST['op'] ?? $_GET['op'] ?? null;
if ($op) {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $dbName = db_val('SELECT DATABASE()');

    // 1) Listar tablas
    if ($op === 'list_tables') {
      $rows = db_all("SHOW TABLE STATUS");
      $out = [];
      foreach ($rows as $r) {
        $out[] = [
          'table'     => $r['Name'],
          'rows'      => (int)($r['Rows'] ?? 0),
          'engine'    => $r['Engine'] ?? '',
          'collation' => $r['Collation'] ?? '',
          'comment'   => $r['Comment'] ?? ''
        ];
      }
      echo json_encode(['ok'=>true,'data'=>$out]); exit;
    }

    // 2) Contar filas exactas
    if ($op === 'count_rows') {
      $t = trim($_POST['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $n = (int) db_val("SELECT COUNT(*) FROM `$t`", [], 0);
      echo json_encode(['ok'=>true,'count'=>$n]); exit;
    }

    // 3) Cambiar collation
    if ($op === 'set_collation') {
      $t = trim($_POST['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      dbq("ALTER TABLE `$t` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
      echo json_encode(['ok'=>true,'msg'=>"Collation de $t cambiado a utf8mb4_unicode_ci"]); exit;
    }

    // 4) Columnas
    if ($op === 'columns') {
      $t = trim($_POST['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $cols = db_all("
        SELECT COLUMN_NAME AS Field,
               COLUMN_TYPE AS Type,
               IS_NULLABLE AS `Null`,
               COLUMN_KEY AS `Key`,
               COLUMN_DEFAULT AS `Default`,
               EXTRA AS Extra
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION", [$dbName, $t]);
      echo json_encode(['ok'=>true,'columns'=>$cols]); exit;
    }

    // 5) Preview
    if ($op === 'preview') {
      $t = trim($_POST['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $rows = db_all("SELECT * FROM `$t` LIMIT 20");
      echo json_encode(['ok'=>true,'data'=>$rows]); exit;
    }

    // 6) DDL
    if ($op === 'ddl') {
      $t = trim($_POST['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $r = db_row("SHOW CREATE TABLE `$t`");
      $ddl = $r ? ($r['Create Table'] ?? array_values($r)[1] ?? '') : '';
      echo json_encode(['ok'=>true,'ddl'=>$ddl]); exit;
    }

    // fallback
    echo json_encode(['ok'=>false,'msg'=>'Operación no válida: '.$op]); exit;

  } catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
  }
}
?>
<?php include __DIR__ . "/../bi/_menu_global.php"; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Generador / Explorador de BD</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{font-size:10px;}
.table-sm td,.table-sm th{padding:.3rem .4rem;vertical-align:middle;}
.dt-right{text-align:right;} .dt-center{text-align:center;}
pre{white-space:pre-wrap;font-size:9px;background:#f7f7f9;border:1px solid #ddd;padding:6px;border-radius:5px;}
</style>
</head>
<body>
<div class="container-fluid mt-2">

<div class="card mb-2">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-database"></i> Generador / Explorador de BD</h6>
    <span class="text-muted">BD: <strong><?php echo htmlspecialchars(db_val('SELECT DATABASE()')); ?></strong></span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tblTablas" class="table table-sm table-striped table-hover w-100">
        <thead>
          <tr>
            <th>Tabla</th><th class="dt-right">Filas</th><th>Engine</th>
            <th>Collation</th><th>Comentario</th><th class="dt-center">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header py-2"><strong>Columnas</strong> <span id="lblTabla" class="text-primary"></span></div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tblCols" class="table table-sm table-striped table-hover w-100">
        <thead><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="card mt-2">
  <div class="card-header py-2"><strong>DDL</strong></div>
  <div class="card-body"><pre id="preDDL">(Seleccione una tabla)</pre></div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
<script>
(function(){
'use strict';
let tTablas,tCols,tablaSel='';

function renderTablas(rows){
  if(!tTablas){
    tTablas=new DataTable('#tblTablas',{
      data:rows,pageLength:5,order:[[0,'asc']],lengthChange:false,
      columns:[
        {data:'table'},
        {data:'rows',className:'dt-right'},
        {data:'engine'},
        {data:'collation'},
        {data:'comment'},
        {data:null,className:'dt-center',
          render:(d,t,x)=>`
          <div class='btn-group btn-group-sm'>
            <button class='btn btn-outline-secondary actCount' data-t='${x.table}'>Contar</button>
            <button class='btn btn-outline-primary actCols' data-t='${x.table}'>Cols</button>
            <button class='btn btn-outline-success actPrev' data-t='${x.table}'>Preview</button>
            <button class='btn btn-outline-warning actColl' data-t='${x.table}'>Unicode_ci</button>
          </div>`}
      ]
    });

    // Contar
    $('#tblTablas').on('click','.actCount',function(){
      const t=$(this).data('t');
      $.post('generador.php',{op:'count_rows',table:t},r=>{
        if(!r.ok){alert(r.msg||'Error');return;}
        const idx=tTablas.rows().indexes().toArray().find(i=>tTablas.row(i).data().table===t);
        if(idx!==undefined){
          const row=tTablas.row(idx).data(); row.rows=r.count;
          tTablas.row(idx).data(row).draw(false);
        }
      },'json');
    });

    // Columnas
    $('#tblTablas').on('click','.actCols',function(){
      tablaSel=$(this).data('t'); $('#lblTabla').text('— '+tablaSel);
      $.post('generador.php',{op:'columns',table:tablaSel},r=>{
        if(!r.ok){alert(r.msg||'Error');return;}
        if(tCols){tCols.clear().destroy();}
        tCols=new DataTable('#tblCols',{
          data:r.columns,paging:false,searching:false,info:false,order:[[0,'asc']],
          columns:[
            {data:'Field'},{data:'Type'},{data:'Null'},{data:'Key'},{data:'Default'},{data:'Extra'}
          ]
        });
      },'json');
      $.post('generador.php',{op:'ddl',table:tablaSel},r=>{
        $('#preDDL').text(r.ddl||'(sin DDL)');
      },'json');
    });

    // Preview
    $('#tblTablas').on('click','.actPrev',function(){
      const t=$(this).data('t');
      $.post('generador.php',{op:'preview',table:t},r=>{
        if(!r.ok){alert(r.msg||'Error');return;}
        console.table(r.data||[]);
        alert('Vista previa en consola (primeras 20 filas).');
      },'json');
    });

    // Cambiar Collation
    $('#tblTablas').on('click','.actColl',function(){
      const t=$(this).data('t');
      if(confirm('¿Convertir '+t+' a utf8mb4_unicode_ci?')){
        $.post('generador.php',{op:'set_collation',table:t},r=>{
          alert(r.msg||'OK');
          loadTablas();
        },'json');
      }
    });

  }else{
    tTablas.clear().rows.add(rows).draw(false);
  }
}

function loadTablas(){
  $.post('generador.php',{op:'list_tables'},r=>{
    if(!r.ok){alert(r.msg||'Error');return;}
    renderTablas(r.data||[]);
  },'json');
}

loadTablas();
})();
</script>
</body>
</html>
<?php include __DIR__ . "/../bi/_menu_global_end.php"; ?>
