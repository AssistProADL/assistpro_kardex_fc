<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ===========================
   API ultra–ligera (safe mode)
   =========================== */
$op = $_POST['op'] ?? $_GET['op'] ?? null;
if ($op) {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $db = db_val('SELECT DATABASE()');

    // 1) Listar tablas (solo information_schema; sin COUNT, sin SHOW TABLE STATUS)
    if ($op === 'list_tables') {
      $rows = db_all("
        SELECT
          TABLE_NAME      AS table_name,
          ENGINE          AS engine,
          TABLE_COLLATION AS collation,
          TABLE_ROWS      AS approx_rows
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ?
        ORDER BY TABLE_NAME
      ", [$db]);
      echo json_encode(['ok'=>true, 'data'=>$rows]); exit;
    }

    // 2) Columnas (solo information_schema)
    if ($op === 'columns') {
      $t = trim($_POST['table'] ?? $_GET['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $cols = db_all("
        SELECT COLUMN_NAME   AS column_name,
               COLUMN_TYPE   AS column_type,
               IS_NULLABLE   AS is_nullable,
               COLUMN_KEY    AS column_key,
               COLUMN_DEFAULT AS column_default,
               EXTRA         AS extra,
               ORDINAL_POSITION AS pos
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION
      ", [$db, $t]);
      echo json_encode(['ok'=>true, 'columns'=>$cols]); exit;
    }

    // 3) Preview (LIMIT 10, orden por PK si existe, sin WHERE para evitar full scans)
    if ($op === 'preview') {
      $t = trim($_POST['table'] ?? $_GET['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');

      // Detectar PK (columnas) para ordenar
      $pkCols = db_all("
        SELECT k.COLUMN_NAME
        FROM information_schema.TABLE_CONSTRAINTS t
        JOIN information_schema.KEY_COLUMN_USAGE k
          ON t.CONSTRAINT_NAME = k.CONSTRAINT_NAME
         AND t.TABLE_SCHEMA = k.TABLE_SCHEMA
         AND t.TABLE_NAME   = k.TABLE_NAME
        WHERE t.TABLE_SCHEMA = ? AND t.TABLE_NAME = ?
          AND t.CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY k.ORDINAL_POSITION
      ", [$db, $t]);

      $orderBy = '';
      if ($pkCols) {
        $parts = array_map(function($r){ return '`'.$r['COLUMN_NAME'].'`'; }, $pkCols);
        $orderBy = ' ORDER BY '.implode(',', $parts);
      }

      // Consulta segura y corta
      $sql = "SELECT * FROM `{$t}`{$orderBy} LIMIT 10";
      $rows = db_all($sql);
      echo json_encode(['ok'=>true, 'data'=>$rows]); exit;
    }

    // 4) DDL (SHOW CREATE TABLE no bloquea)
    if ($op === 'ddl') {
      $t = trim($_POST['table'] ?? $_GET['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $r = db_row("SHOW CREATE TABLE `{$t}`");
      $ddl = $r ? ($r['Create Table'] ?? array_values($r)[1] ?? '') : '';
      echo json_encode(['ok'=>true, 'ddl'=>$ddl]); exit;
    }

    // 5) Collation (modo seguro: NO ejecuta; solo devuelve el SQL.
    //    Si confirm=1 entonces sí ejecuta bajo tu consentimiento explícito.)
    if ($op === 'set_collation') {
      $t = trim($_POST['table'] ?? $_GET['table'] ?? '');
      if ($t==='') throw new Exception('Tabla requerida');
      $sql = "ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $confirm = (int)($_POST['confirm'] ?? $_GET['confirm'] ?? 0);
      if ($confirm === 1) {
        // Ejecuta solo si confirmas con confirm=1
        dbq($sql);
        echo json_encode(['ok'=>true, 'executed'=>true, 'msg'=>"Collation de {$t} cambiado a utf8mb4_unicode_ci"]); exit;
      }
      echo json_encode(['ok'=>true, 'executed'=>false, 'sql'=>$sql, 'note'=>'Previsualización: no se ejecutó. Enviar confirm=1 para aplicar.']); exit;
    }

    echo json_encode(['ok'=>false, 'msg'=>'Operación no válida']); exit;

  } catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]); exit;
  }
}
?>
<?php include __DIR__ . "/../bi/_menu_global.php"; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Generador (Safe Mode)</title>
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
      <h6 class="mb-0"><i class="bi bi-shield-check"></i> Generador / Explorador — Modo Seguro</h6>
      <span class="text-muted">BD: <strong><?php echo htmlspecialchars(db_val('SELECT DATABASE()')); ?></strong></span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="tblTablas" class="table table-sm table-striped table-hover w-100">
          <thead>
            <tr>
              <th>Tabla</th>
              <th>Engine</th>
              <th>Collation</th>
              <th class="dt-right">Filas (aprox)</th>
              <th class="dt-center">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <small class="text-muted">* Filas es estimado de information_schema (no ejecuta COUNT).</small>
    </div>
  </div>

  <div class="card">
    <div class="card-header py-2"><strong>Columnas</strong> <span id="lblTabla" class="text-primary"></span></div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="tblCols" class="table table-sm table-striped table-hover w-100">
          <thead>
            <tr>
              <th>#</th><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="mt-2">
        <button id="btnPreview" class="btn btn-outline-success btn-sm" disabled><i class="bi bi-eye"></i> Preview (10)</button>
        <button id="btnDDL" class="btn btn-outline-dark btn-sm" disabled><i class="bi bi-braces"></i> Ver DDL</button>
        <button id="btnCollationSQL" class="btn btn-outline-warning btn-sm" disabled><i class="bi bi-arrow-repeat"></i> Unicode_ci (SQL)</button>
        <button id="btnCollationExec" class="btn btn-danger btn-sm" disabled><i class="bi bi-exclamation-triangle"></i> Aplicar Unicode_ci</button>
      </div>
      <div class="mt-2">
        <pre id="outArea" class="mb-0" style="display:none;"></pre>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
<script>
(function(){
  'use strict';

  let tTablas=null, tCols=null;
  let tablaSel = '';

  function enableActions(enabled){
    $('#btnPreview,#btnDDL,#btnCollationSQL,#btnCollationExec').prop('disabled', !enabled);
  }

  function renderTablas(rows){
    if(!tTablas){
      tTablas = new DataTable('#tblTablas', {
        data: rows,
        pageLength: 5, lengthChange: false, searching: false, order: [[0,'asc']],
        columns: [
          { data: 'table_name' },
          { data: 'engine' },
          { data: 'collation' },
          { data: 'approx_rows', className: 'dt-right',
            render: d => (d==null ? '' : d) },
          { data: null, className: 'dt-center', render: (d,t,x)=>`
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary actCols" data-t="${x.table_name}">Cols</button>
                <button class="btn btn-outline-success actPrev" data-t="${x.table_name}">Preview</button>
                <button class="btn btn-outline-dark actDDL" data-t="${x.table_name}">DDL</button>
                <button class="btn btn-outline-warning actCollSQL" data-t="${x.table_name}">Unicode_ci (SQL)</button>
              </div>` }
        ]
      });

      $('#tblTablas').on('click','.actCols', function(){
        const t = $(this).data('t');
        tablaSel = t; $('#lblTabla').text('— '+t); enableActions(true); $('#outArea').hide().text('');
        $.get('generador.php', {op:'columns', table:t}, function(r){
          if(!r.ok){ alert(r.msg||'Error'); return; }
          if(tCols){ tCols.clear().destroy(); }
          const data = (r.columns||[]).map((c,i)=>({ idx:i+1, ...c }));
          tCols = new DataTable('#tblCols', {
            data, paging:false, searching:false, info:false, order:[[0,'asc']],
            columns: [
              { data:'idx', className:'dt-right' },
              { data:'column_name' },
              { data:'column_type' },
              { data:'is_nullable' },
              { data:'column_key' },
              { data:'column_default' },
              { data:'extra' },
            ]
          });
        }, 'json');
      });

      $('#tblTablas').on('click','.actPrev', function(){
        const t = $(this).data('t'); tablaSel = t; $('#lblTabla').text('— '+t); enableActions(true);
        $.get('generador.php', {op:'preview', table:t}, function(r){
          if(!r.ok){ alert(r.msg||'Error'); return; }
          console.table(r.data||[]);
          $('#outArea').show().text('Preview (primeras 10 filas) impreso en consola.');
        }, 'json');
      });

      $('#tblTablas').on('click','.actDDL', function(){
        const t = $(this).data('t'); tablaSel = t; $('#lblTabla').text('— '+t); enableActions(true);
        $.get('generador.php', {op:'ddl', table:t}, function(r){
          if(!r.ok){ alert(r.msg||'Error'); return; }
          $('#outArea').show().text(r.ddl||'(sin DDL)');
        }, 'json');
      });

      $('#tblTablas').on('click','.actCollSQL', function(){
        const t = $(this).data('t'); tablaSel = t; $('#lblTabla').text('— '+t); enableActions(true);
        $.get('generador.php', {op:'set_collation', table:t}, function(r){
          if(!r.ok){ alert(r.msg||'Error'); return; }
          if(r.executed){ alert(r.msg||'Aplicado'); }
          $('#outArea').show().text(r.sql ? r.sql : (r.msg||'OK'));
        }, 'json');
      });

    } else {
      tTablas.clear().rows.add(rows).draw(false);
    }
  }

  function loadTablas(){
    enableActions(false);
    $('#outArea').hide().text('');
    $.get('generador.php', {op:'list_tables'}, function(r){
      if(!r.ok){ alert(r.msg||'Error'); return; }
      renderTablas(r.data||[]);
    }, 'json');
  }

  // Botones secundarios de la tarjeta de columnas
  $('#btnPreview').on('click', function(){
    if(!tablaSel) return;
    $.get('generador.php', {op:'preview', table:tablaSel}, function(r){
      if(!r.ok){ alert(r.msg||'Error'); return; }
      console.table(r.data||[]);
      $('#outArea').show().text('Preview (primeras 10 filas) impreso en consola.');
    }, 'json');
  });

  $('#btnDDL').on('click', function(){
    if(!tablaSel) return;
    $.get('generador.php', {op:'ddl', table:tablaSel}, function(r){
      if(!r.ok){ alert(r.msg||'Error'); return; }
      $('#outArea').show().text(r.ddl||'(sin DDL)');
    }, 'json');
  });

  $('#btnCollationSQL').on('click', function(){
    if(!tablaSel) return;
    $.get('generador.php', {op:'set_collation', table:tablaSel}, function(r){
      if(!r.ok){ alert(r.msg||'Error'); return; }
      $('#outArea').show().text(r.sql ? r.sql : (r.msg||'OK'));
    }, 'json');
  });

  $('#btnCollationExec').on('click', function(){
    if(!tablaSel) return;
    if(!confirm('Esto puede tardar y bloquear la tabla. ¿Aplicar ahora?')) return;
    $.post('generador.php', {op:'set_collation', table:tablaSel, confirm:1}, function(r){
      if(!r.ok){ alert(r.msg||'Error'); return; }
      alert(r.msg||'Aplicado');
      loadTablas();
    }, 'json');
  });

  // Carga inicial
  loadTablas();
})();
</script>
</body>
</html>
<?php include __DIR__ . "/../bi/_menu_global_end.php"; ?>
