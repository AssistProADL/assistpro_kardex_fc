<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ============================================================
   CONFIG / HELPERS
============================================================ */
$CFG = [
  'BL_MANUFACTURA_DEFAULT' => 'MANU-BL',   // BL por defecto para manufactura
];

function table_exists($table){
  return (int)db_val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]) > 0;
}
function col_exists($table, $col){
  return (int)db_val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col]) > 0;
}
function first_existing_col($table, $candidates){
  foreach($candidates as $c){ if(col_exists($table,$c)) return $c; }
  return null;
}

/**
 * Devuelve stock disponible de un artículo en un BL específico.
 * Si no existe tabla/columnas de stock, devuelve null para no romper la vista.
 */
function stock_disponible_en_bl($cve_articulo, $bl){
  if (!table_exists('stock_ubic')) return null;

  $colArt = first_existing_col('stock_ubic', ['Cve_Articulo','cve_articulo','Articulo','articulo','CLAVE']);
  $colBL  = first_existing_col('stock_ubic', ['BL','Bl','bl','Bin','bin','Ubicacion','ubicacion','BL_CODE','bl_code']);
  $qtyCol = first_existing_col('stock_ubic', ['Cantidad','cantidad','Existencia','existencia','Stock','stock','Disponible','disponible','qty','QTY']);

  if (!$colArt || !$colBL || !$qtyCol) return null;

  $sql = "SELECT SUM($qtyCol) AS qty FROM stock_ubic WHERE $colArt = ? AND $colBL = ?";
  $r = db_row($sql, [$cve_articulo, $bl]);
  if (!$r) return 0.0;
  return (float)($r['qty'] ?? 0.0);
}

/* ============================================================
   API AJAX
============================================================ */
$op = $_POST['op'] ?? $_GET['op'] ?? null;
if ($op) {

  // Buscar productos compuestos (clave/descr)
  if ($op === 'buscar_compuestos') {
    header('Content-Type: application/json; charset=utf-8');
    try{
      $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
      $p = [];
      $sql = "
        SELECT
          cve_articulo,
          des_articulo,
          cve_umed AS unidadMedida
        FROM c_articulo
        WHERE COALESCE(Compuesto,'N')='S'
      ";
      if ($q !== '') {
        $sql .= " AND (cve_articulo LIKE ? OR des_articulo LIKE ?) ";
        $p[] = "%$q%"; $p[] = "%$q%";
      }
      $sql .= " ORDER BY des_articulo LIMIT 30";
      $rows = db_all($sql, $p);
      echo json_encode(['ok'=>true,'data'=>$rows]); exit;
    }catch(Throwable $e){
      echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
    }
  }

  // Explosión BOM para cálculo de requerimientos
  if ($op === 'explosion') {
    header('Content-Type: application/json; charset=utf-8');
    try{
      $padre   = trim($_POST['padre'] ?? $_GET['padre'] ?? '');
      $fec_ot  = trim($_POST['fec_ot'] ?? $_GET['fec_ot'] ?? '');
      $fec_com = trim($_POST['fec_com'] ?? $_GET['fec_com'] ?? '');
      $cant    = (float)($_POST['cantidad'] ?? $_GET['cantidad'] ?? 0);
      $bl_man  = trim($_POST['bl_man'] ?? $_GET['bl_man'] ?? '');

      if ($padre==='') throw new Exception('Selecciona un producto compuesto.');
      if ($cant<=0)    throw new Exception('La cantidad a producir debe ser mayor a cero.');

      // Encabezado del padre
      $prod = db_row("
        SELECT
          cve_articulo,
          des_articulo,
          cve_umed AS unidadMedida
        FROM c_articulo
        WHERE cve_articulo = ?
      ", [$padre]);
      if (!$prod) throw new Exception('Producto no encontrado.');

      // Componentes (BOM) desde t_artcompuesto + c_articulo
      $rows = db_all("
        SELECT
          c.Cve_ArtComponente                 AS componente,
          a.des_articulo                      AS descripcion,
          COALESCE(c.cve_umed, a.cve_umed)    AS umed,
          c.Cantidad                          AS cant_por_unidad
        FROM t_artcompuesto c
        LEFT JOIN c_articulo a
               ON a.cve_articulo = c.Cve_ArtComponente
        WHERE c.Cve_Articulo = ?
          AND (c.Activo IS NULL OR c.Activo = 1)
        ORDER BY c.Cve_ArtComponente
      ", [$padre]);

      // Calcular cantidades y stock
      $det = [];
      foreach($rows as $r){
        $cant_unit  = (float)$r['cant_por_unidad'];
        $cant_total = $cant_unit * $cant;
        $stock = ($bl_man!=='') ? stock_disponible_en_bl($r['componente'], $bl_man) : null;
        $det[] = [
          'componente'      => $r['componente'],
          'descripcion'     => $r['descripcion'],
          'umed'            => $r['umed'],
          'cant_por_unidad' => $cant_unit,
          'cant_total'      => $cant_total,
          'cant_solicitada' => $cant_total, // editable en UI
          'stock_disponible'=> is_null($stock) ? null : (float)$stock,
        ];
      }

      echo json_encode([
        'ok'=>true,
        'padre'=>$prod,
        'fec_ot'=>$fec_ot,
        'fec_com'=>$fec_com,
        'cantidad'=>$cant,
        'bl_man'=>$bl_man,
        'det'=>$det
      ]); exit;

    }catch(Throwable $e){
      echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
    }
  }

  // Exportar CSV del requerimiento actual
  if ($op === 'export_req_csv') {
    try{
      $padre   = trim($_GET['padre'] ?? '');
      $cant    = (float)($_GET['cantidad'] ?? 0);
      $bl_man  = trim($_GET['bl_man'] ?? '');
      if ($padre==='' || $cant<=0) throw new Exception('Parámetros inválidos para exportar.');

      $prod = db_row("
        SELECT
          cve_articulo,
          des_articulo,
          cve_umed AS unidadMedida
        FROM c_articulo
        WHERE cve_articulo = ?
      ", [$padre]);
      if (!$prod) throw new Exception('Producto no encontrado.');

      $rows = db_all("
        SELECT
          c.Cve_ArtComponente                 AS componente,
          a.des_articulo                      AS descripcion,
          COALESCE(c.cve_umed, a.cve_umed)    AS umed,
          c.Cantidad                          AS cant_por_unidad
        FROM t_artcompuesto c
        LEFT JOIN c_articulo a
               ON a.cve_articulo = c.Cve_ArtComponente
        WHERE c.Cve_Articulo = ?
          AND (c.Activo IS NULL OR c.Activo = 1)
        ORDER BY c.Cve_ArtComponente
      ", [$padre]);

      $filename = "REQ_{$padre}_".date('Ymd_His').".csv";
      header('Content-Type: text/csv; charset=utf-8');
      header("Content-Disposition: attachment; filename=\"$filename\"");
      $out = fopen('php://output', 'w');
      fputcsv($out, ['Producto Compuesto', $prod['cve_articulo']]);
      fputcsv($out, ['Descripción',        $prod['des_articulo']]);
      fputcsv($out, ['UMed (cve_umed)',    $prod['unidadMedida']]);
      fputcsv($out, ['Cantidad a Producir',$cant]);
      fputcsv($out, ['BL Manufactura',     $bl_man]);
      fputcsv($out, ['Generado',           date('Y-m-d H:i:s')]);
      fputcsv($out, []);
      fputcsv($out, ['Componente','Descripción','UMed (cve_umed)','Cantidad por unidad','Cantidad total','Stock disponible (BL)']);
      foreach($rows as $r){
        $cant_total = (float)$r['cant_por_unidad'] * $cant;
        $stock = ($bl_man!=='') ? stock_disponible_en_bl($r['componente'], $bl_man) : null;
        fputcsv($out, [
          $r['componente'],
          $r['descripcion'],
          $r['umed'],
          $r['cant_por_unidad'],
          $cant_total,
          is_null($stock)?'':$stock
        ]);
      }
      fclose($out); exit;

    }catch(Throwable $e){
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
    }
  }

  // Operación inválida
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'msg'=>'Operación no válida']); exit;
}

?>
<?php include __DIR__ . '/../bi/_menu_global.php'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Orden de Producción</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body{font-size:10px;}
  .table-sm td,.table-sm th{padding:.35rem .45rem;vertical-align:middle;}
  .dt-right{text-align:right;} .dt-center{text-align:center;}
  .muted{font-size:9px;color:#6c757d;}
</style>
</head>
<body>
<div class="container-fluid mt-2">

  <div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="bi bi-gear-wide-connected"></i> Orden de Producción</h6>
      <span class="text-muted">Manufactura</span>
    </div>
    <div class="card-body">

      <!-- Selección de producto compuesto -->
      <div class="row g-2">
        <div class="col-md-5">
          <label class="form-label mb-0">Producto compuesto (clave o descripción)</label>
          <div class="input-group input-group-sm">
            <input id="txtBuscar" class="form-control" placeholder="Ej. 1000011321-J1, 'Cherry', etc.">
            <button id="btnBuscar" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
          </div>
          <div class="table-responsive mt-1" style="max-height:200px; overflow:auto;">
            <table id="tblProductos" class="table table-sm table-striped table-hover w-100">
              <thead><tr><th>Clave</th><th>Descripción</th><th>UMed (cve_umed)</th><th style="width:70px">Acción</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <div class="col-md-7">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label mb-0">Producto seleccionado</label>
              <input id="txtPadre" class="form-control form-control-sm" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label mb-0">Descripción</label>
              <input id="txtDesc" class="form-control form-control-sm" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label mb-0">UMed (cve_umed)</label>
              <input id="txtUM" class="form-control form-control-sm" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label mb-0">Fecha OT</label>
              <input id="txtFecOT" type="date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label mb-0">Fecha compromiso</label>
              <input id="txtFecCom" type="date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label mb-0">Cantidad a producir</label>
              <input id="txtCantidad" type="number" step="0.0001" min="0" class="form-control form-control-sm" value="1">
            </div>
            <div class="col-md-4">
              <label class="form-label mb-0">BL Manufactura</label>
              <input id="txtBL" class="form-control form-control-sm" value="<?php echo htmlspecialchars($CFG['BL_MANUFACTURA_DEFAULT']); ?>">
              <div class="muted">En este BL se garantiza el stock para consumo.</div>
            </div>
            <div class="col-md-8 d-flex align-items-end">
              <div class="btn-group btn-group-sm ms-auto">
                <button id="btnCalcular" class="btn btn-primary"><i class="bi bi-calculator"></i> Calcular requerimientos</button>
                <button id="btnExport" class="btn btn-outline-secondary" disabled><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <hr>

      <!-- Resumen encabezado -->
      <div id="resumenPanel" class="alert alert-light border d-none">
        <div><strong>Producto compuesto:</strong> <span id="r_padre">—</span> — <span id="r_desc">—</span> (<span id="r_um">—</span>)</div>
        <div><strong>Cantidad a producir:</strong> <span id="r_cant">—</span> |
             <strong>Fecha OT:</strong> <span id="r_fecot">—</span> |
             <strong>Fecha compromiso:</strong> <span id="r_feccom">—</span> |
             <strong>BL Manufactura:</strong> <span id="r_bl">—</span></div>
      </div>

      <!-- Componentes requeridos -->
      <div class="table-responsive">
        <table id="tblComp" class="table table-sm table-striped table-hover w-100">
          <thead>
            <tr>
              <th>Componente</th>
              <th>Descripción</th>
              <th>UMed (cve_umed)</th>
              <th class="dt-right">Cantidad por unidad</th>
              <th class="dt-right">Cantidad total</th>
              <th class="dt-right">Cantidad solicitada</th>
              <th class="dt-right">Stock disponible (BL)</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
  <div id="appToast" class="toast align-items-center border-0 text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="toastBody" class="toast-body">Listo</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
<script>
(function(){
  'use strict';
  let tProd=null, tComp=null, padreSel=null, descSel='', umSel='';
  let toastObj = new bootstrap.Toast(document.getElementById('appToast'), {delay:3500});

  function toast(msg, ok=true){
    const el=document.getElementById('appToast'); const body=document.getElementById('toastBody');
    body.textContent=msg;
    el.classList.remove('text-bg-success','text-bg-danger','text-bg-warning');
    el.classList.add(ok?'text-bg-success':'text-bg-danger');
    toastObj.show();
  }

  function buscar(){
    const q = $('#txtBuscar').val().trim();
    $.get('orden_produccion.php',{op:'buscar_compuestos', q}, (r)=>{
      if(!r.ok){ toast(r.msg||'Error al buscar', false); return; }
      const data=r.data||[];
      if(!tProd){
        tProd = new DataTable('#tblProductos',{
          data, paging:true, pageLength:8, lengthChange:false, searching:false, info:true, order:[[1,'asc']],
          columns:[
            {data:'cve_articulo'},
            {data:'des_articulo'},
            {data:'unidadMedida'},
            {data:null, orderable:false, className:'dt-center',
              render:(d,t,x)=>`<button class="btn btn-sm btn-outline-primary actSel" data-id="${x.cve_articulo}" data-desc="${x.des_articulo||''}" data-um="${x.unidadMedida||''}"><i class="bi bi-check2-circle"></i></button>`}
          ]
        });
        $('#tblProductos').on('click','.actSel',function(){
          padreSel = $(this).data('id'); 
          descSel  = $(this).data('desc')||''; 
          umSel    = $(this).data('um')||'';
          $('#txtPadre').val(padreSel); 
          $('#txtDesc').val(descSel); 
          $('#txtUM').val(umSel);
        });
      }else{
        tProd.clear().rows.add(data).draw(false);
      }
    },'json');
  }

  function calcular(){
    const padre = $('#txtPadre').val().trim();
    const fecot = $('#txtFecOT').val();
    const feccom= $('#txtFecCom').val();
    const cant  = parseFloat($('#txtCantidad').val()||'0');
    const bl    = $('#txtBL').val().trim();

    if(!padre){ toast('Selecciona un producto compuesto.', false); return; }
    if(isNaN(cant)||cant<=0){ toast('Cantidad a producir inválida.', false); return; }

    $.post('orden_produccion.php',{
      op:'explosion',
      padre:padre, fec_ot:fecot, fec_com:feccom,
      cantidad:cant, bl_man:bl
    },(r)=>{
      if(!r.ok){ toast(r.msg||'No fue posible calcular', false); return; }

      // Resumen
      $('#resumenPanel').removeClass('d-none');
      $('#r_padre').text(r.padre?.cve_articulo||'—');
      $('#r_desc').text(r.padre?.des_articulo||'—');
      $('#r_um').text(r.padre?.unidadMedida||'—');
      $('#r_cant').text(r.cantidad);
      $('#r_fecot').text(r.fec_ot || '—');
      $('#r_feccom').text(r.fec_com || '—');
      $('#r_bl').text(r.bl_man || '—');

      // Grilla de componentes
      const data = r.det||[];
      if(!tComp){
        tComp = new DataTable('#tblComp',{
          data,paging:false,searching:false,info:false,order:[[0,'asc']],
          columns:[
            {data:'componente'},
            {data:'descripcion'},
            {data:'umed'},
            {data:'cant_por_unidad', className:'dt-right',
              render:(v)=> Number(v||0).toLocaleString()}
            ,
            {data:'cant_total', className:'dt-right',
              render:(v)=> Number(v||0).toLocaleString()}
            ,
            // editable: cantidad solicitada (inicia igual a total)
            {data:'cant_solicitada', className:'dt-right',
              render:(v,t,x,meta)=>`<input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end inpSolic" value="${Number(v||0)}" data-row="${meta.row}">`
            },
            {data:'stock_disponible', className:'dt-right',
              render:(v)=> (v===null||v===undefined||v==='')?'—':Number(v).toLocaleString()}
          ]
        });
        // escucha cambios para actualizar el dato en memoria
        $('#tblComp').on('input','.inpSolic',function(){
          const rowIndex = parseInt($(this).data('row'),10);
          const val = parseFloat($(this).val()||'0');
          const row = tComp.row(rowIndex).data();
          row.cant_solicitada = isNaN(val)?0:val;
          tComp.row(rowIndex).data(row);
        });
      }else{
        tComp.clear().rows.add(data).draw(false);
      }

      $('#btnExport').prop('disabled', false);
      toast('Requerimiento calculado.');
    },'json');
  }

  function exportReq(){
    const padre = $('#txtPadre').val().trim();
    const cant  = $('#txtCantidad').val().trim();
    const bl    = $('#txtBL').val().trim();
    if(!padre || !cant){ toast('Nada que exportar.', false); return; }
    const url = `orden_produccion.php?op=export_req_csv&padre=${encodeURIComponent(padre)}&cantidad=${encodeURIComponent(cant)}&bl_man=${encodeURIComponent(bl)}`;
    window.location = url;
  }

  // Eventos
  $('#btnBuscar').on('click',buscar);
  $('#txtBuscar').on('keypress',e=>{ if(e.which===13) buscar(); });
  $('#btnCalcular').on('click',calcular);
  $('#btnExport').on('click',exportReq);

  // Defaults fechas
  const today = new Date().toISOString().substring(0,10);
  $('#txtFecOT').val(today);
  $('#txtFecCom').val(today);

})();
</script>
</body>
</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
