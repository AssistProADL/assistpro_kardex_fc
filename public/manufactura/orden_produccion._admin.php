<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ============================================================
   HELPERS
============================================================ */
function table_exists($t){
  return (int)db_val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$t]) > 0;
}

/* ============================================================
   API AJAX
============================================================ */
$op = $_POST['op'] ?? $_GET['op'] ?? null;
if ($op) {

  header('Content-Type: application/json; charset=utf-8');

  try {

    /* ---------- CATÁLOGOS PARA FILTROS ---------- */
    if ($op === 'get_filtros') {
      $almacenes = [];
      $status    = [];

      if (table_exists('t_ordenprod')) {
        $almacenes = db_all("
          SELECT DISTINCT cve_almac AS id, cve_almac AS nombre
          FROM t_ordenprod
          WHERE cve_almac IS NOT NULL AND cve_almac <> ''
          ORDER BY cve_almac
        ");

        $status = db_all("
          SELECT DISTINCT Status AS id, Status AS nombre
          FROM t_ordenprod
          WHERE Status IS NOT NULL AND Status <> ''
          ORDER BY Status
        ");
      }

      echo json_encode(['ok'=>true,'almacenes'=>$almacenes,'status'=>$status]); exit;
    }

    /* ---------- LISTA DE OTs (ADMIN GRID) ---------- */
    if ($op === 'list_ots') {
      $almacen   = trim($_POST['almacen']   ?? $_GET['almacen']   ?? '');
      $status    = trim($_POST['status']    ?? $_GET['status']    ?? '');
      $f_ini     = trim($_POST['f_ini']     ?? $_GET['f_ini']     ?? '');
      $f_fin     = trim($_POST['f_fin']     ?? $_GET['f_fin']     ?? '');
      $buscar    = trim($_POST['buscar']    ?? $_GET['buscar']    ?? '');
      $buscar_lp = trim($_POST['buscar_lp'] ?? $_GET['buscar_lp'] ?? '');

      $where = [];
      $params = [];

      if ($almacen !== '') {
        $where[] = "o.cve_almac = ?";
        $params[] = $almacen;
      }
      if ($status !== '') {
        $where[] = "o.Status = ?";
        $params[] = $status;
      }
      if ($f_ini !== '') {
        $where[] = "DATE(o.FechaReg) >= ?";
        $params[] = $f_ini;
      }
      if ($f_fin !== '') {
        $where[] = "DATE(o.FechaReg) <= ?";
        $params[] = $f_fin;
      }
      if ($buscar !== '') {
        $where[] = "(o.Folio_Pro LIKE ? OR o.Cve_Articulo LIKE ? OR a.des_articulo LIKE ? OR o.Referencia LIKE ? OR o.Cve_Lote LIKE ?)";
        $params = array_merge($params, array_fill(0,5,"%$buscar%"));
      }
      if ($buscar_lp !== '') {
        // Buscamos LP en Referencia o Cve_Lote (ajustable a tu modelo)
        $where[] = "(o.Referencia LIKE ? OR o.Cve_Lote LIKE ?)";
        $params[] = "%$buscar_lp%";
        $params[] = "%$buscar_lp%";
      }

      $sql = "
        SELECT
          o.Folio_Pro,
          o.FechaReg,
          o.Hora_Ini,
          o.cve_almac,
          o.Cve_Articulo,
          o.Cve_Lote,
          o.Cantidad,
          o.Status,
          o.Referencia,
          a.des_articulo
        FROM t_ordenprod o
        LEFT JOIN c_articulo a
               ON a.cve_articulo = o.Cve_Articulo
      ";

      if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
      }

      $sql .= " ORDER BY o.FechaReg DESC, o.Hora_Ini DESC, o.Folio_Pro DESC LIMIT 500";

      $rows = db_all($sql, $params);

      echo json_encode(['ok'=>true,'data'=>$rows]); exit;
    }

    /* ---------- DETALLE DE COMPONENTES POR OT ---------- */
    if ($op === 'detalle_ot') {
      $folio = trim($_POST['folio'] ?? $_GET['folio'] ?? '');
      if ($folio === '') throw new Exception('Folio requerido.');

      $rows = db_all("
        SELECT
          d.Cve_Articulo        AS componente,
          a.des_articulo        AS descripcion,
          d.Cantidad            AS cantidad,
          d.Cve_Lote            AS lote,
          d.Cve_Almac_Ori       AS cve_almac_ori,
          d.Activo              AS activo
        FROM td_ordenprod d
        LEFT JOIN c_articulo a
               ON a.cve_articulo = d.Cve_Articulo
        WHERE d.Folio_Pro = ?
        ORDER BY d.Cve_Articulo
      ", [$folio]);

      echo json_encode(['ok'=>true,'data'=>$rows]); exit;
    }

    /* ---------- EXPORTAR CSV DE OTs (CON FILTROS) ---------- */
    if ($op === 'export_csv') {
      // aquí NO usamos header JSON
      ob_end_clean();
      $almacen   = trim($_GET['almacen']   ?? '');
      $status    = trim($_GET['status']    ?? '');
      $f_ini     = trim($_GET['f_ini']     ?? '');
      $f_fin     = trim($_GET['f_fin']     ?? '');
      $buscar    = trim($_GET['buscar']    ?? '');
      $buscar_lp = trim($_GET['buscar_lp'] ?? '');

      $where = [];
      $params = [];

      if ($almacen !== '') {
        $where[] = "o.cve_almac = ?";
        $params[] = $almacen;
      }
      if ($status !== '') {
        $where[] = "o.Status = ?";
        $params[] = $status;
      }
      if ($f_ini !== '') {
        $where[] = "DATE(o.FechaReg) >= ?";
        $params[] = $f_ini;
      }
      if ($f_fin !== '') {
        $where[] = "DATE(o.FechaReg) <= ?";
        $params[] = $f_fin;
      }
      if ($buscar !== '') {
        $where[] = "(o.Folio_Pro LIKE ? OR o.Cve_Articulo LIKE ? OR a.des_articulo LIKE ? OR o.Referencia LIKE ? OR o.Cve_Lote LIKE ?)";
        $params = array_merge($params, array_fill(0,5,"%$buscar%"));
      }
      if ($buscar_lp !== '') {
        $where[] = "(o.Referencia LIKE ? OR o.Cve_Lote LIKE ?)";
        $params[] = "%$buscar_lp%";
        $params[] = "%$buscar_lp%";
      }

      $sql = "
        SELECT
          o.Folio_Pro,
          o.FechaReg,
          o.Hora_Ini,
          o.cve_almac,
          o.Cve_Articulo,
          a.des_articulo,
          o.Cantidad,
          o.Status,
          o.Referencia,
          o.Cve_Lote
        FROM t_ordenprod o
        LEFT JOIN c_articulo a
               ON a.cve_articulo = o.Cve_Articulo
      ";

      if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
      }

      $sql .= " ORDER BY o.FechaReg DESC, o.Hora_Ini DESC, o.Folio_Pro DESC";

      $rows = db_all($sql, $params);

      $filename = "OTs_".date('Ymd_His').".csv";
      header('Content-Type: text/csv; charset=utf-8');
      header("Content-Disposition: attachment; filename=\"$filename\"");

      $out = fopen('php://output', 'w');
      fputcsv($out, ['Fecha OT','Hora OT','Folio OT','Almacén','Clave producto','Nombre producto','Cantidad','Status','Pedido / Ref','Lote / Serie']);

      foreach($rows as $r){
        fputcsv($out, [
          $r['FechaReg'],
          $r['Hora_Ini'],
          $r['Folio_Pro'],
          $r['cve_almac'],
          $r['Cve_Articulo'],
          $r['des_articulo'],
          $r['Cantidad'],
          $r['Status'],
          $r['Referencia'],
          $r['Cve_Lote']
        ]);
      }
      fclose($out);
      exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Operación no válida']); exit;

  } catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
  }
}
?>
<?php include __DIR__ . '/../bi/_menu_global.php'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Administración de Órdenes de Producción</title>
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

  <h5 class="mb-2">Administración de Órdenes de Trabajo</h5>

  <div class="card mb-2">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold"><i class="bi bi-clipboard2-check"></i> Filtros</span>
        <button id="btnExport" class="btn btn-primary btn-sm">
          <i class="bi bi-file-earmark-excel"></i> Exportar OT Pendientes
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label mb-0">Seleccione un almacén</label>
          <select id="fAlmacen" class="form-select form-select-sm">
            <option value="">Todos</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Status</label>
          <select id="fStatus" class="form-select form-select-sm">
            <option value="">Todos</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-0">Fecha inicio</label>
          <input id="fIni" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-0">Fecha fin</label>
          <input id="fFin" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button id="btnBuscar" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search"></i> Buscar
          </button>
        </div>
      </div>
      <div class="row g-2 mt-2">
        <div class="col-md-4">
          <label class="form-label mb-0">Buscar</label>
          <input id="fBuscar" class="form-control form-control-sm" placeholder="Folio, producto, descripción, pedido...">
        </div>
        <div class="col-md-4">
          <label class="form-label mb-0">Buscar LP</label>
          <input id="fBuscarLP" class="form-control form-control-sm" placeholder="Lote / LP / referencia">
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="tblOT" class="table table-sm table-striped table-hover w-100">
          <thead>
            <tr>
              <th class="dt-center">Acciones</th>
              <th>Fecha OT</th>
              <th>Hora OT</th>
              <th>Folio OT</th>
              <th>Almacén</th>
              <th>Pedido / Ref</th>
              <th>Clave producto</th>
              <th>Nombre producto</th>
              <th class="dt-right">Cantidad</th>
              <th>Status</th>
              <th>Lote | Serie</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="muted mt-1">Mostrando hasta 500 órdenes más recientes según filtros.</div>
    </div>
  </div>

</div>

<!-- Modal: detalle de componentes -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-diagram-3"></i> Componentes de la OT <span id="mFol"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="tblDet" class="table table-sm table-striped table-hover w-100">
            <thead>
              <tr>
                <th>Componente</th>
                <th>Descripción</th>
                <th class="dt-right">Cantidad</th>
                <th>Lote</th>
                <th>Almacén origen</th>
                <th>Activo</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
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
  let tOT=null, tDet=null;
  const toastObj = new bootstrap.Toast(document.getElementById('appToast'), {delay:3500});
  function toast(msg, ok=true){
    const el=document.getElementById('appToast');
    const body=document.getElementById('toastBody');
    body.textContent=msg;
    el.classList.remove('text-bg-success','text-bg-danger');
    el.classList.add(ok?'text-bg-success':'text-bg-danger');
    toastObj.show();
  }

  function cargarFiltros(){
    $.get('orden_produccion_admin.php',{op:'get_filtros'},function(r){
      if(!r.ok){toast(r.msg||'Error cargando filtros',false);return;}
      const $a = $('#fAlmacen').empty().append('<option value="">Todos</option>');
      (r.almacenes||[]).forEach(x=>{
        $a.append(`<option value="${x.id}">${x.id}</option>`);
      });
      const $s = $('#fStatus').empty().append('<option value="">Todos</option>');
      (r.status||[]).forEach(x=>{
        $s.append(`<option value="${x.id}">${x.id}</option>`);
      });
    },'json');
  }

  function buscar(){
    const params = {
      op:'list_ots',
      almacen: $('#fAlmacen').val(),
      status:  $('#fStatus').val(),
      f_ini:   $('#fIni').val(),
      f_fin:   $('#fFin').val(),
      buscar:  $('#fBuscar').val(),
      buscar_lp: $('#fBuscarLP').val()
    };
    $.post('orden_produccion_admin.php',params,function(r){
      if(!r.ok){toast(r.msg||'Error consultando OTs',false);return;}
      const data=r.data||[];
      if(!tOT){
        tOT = new DataTable('#tblOT',{
          data,
          pageLength:15,
          lengthChange:false,
          searching:false,
          info:true,
          order:[[1,'desc'],[2,'desc']],
          columns:[
            {data:null,className:'dt-center',orderable:false,
              render:(d,t,x)=>`
                <button class="btn btn-sm btn-outline-primary actVer" data-folio="${x.Folio_Pro}">
                  <i class="bi bi-search"></i>
                </button>
              `
            },
            {data:'FechaReg'},
            {data:'Hora_Ini'},
            {data:'Folio_Pro'},
            {data:'cve_almac'},
            {data:'Referencia'},
            {data:'Cve_Articulo'},
            {data:'des_articulo'},
            {data:'Cantidad',className:'dt-right',
              render:(v)=> Number(v||0).toLocaleString()
            },
            {data:'Status'},
            {data:'Cve_Lote'}
          ]
        });
        $('#tblOT').on('click','.actVer',function(){
          const fol=$(this).data('folio');
          verDetalle(fol);
        });
      }else{
        tOT.clear().rows.add(data).draw(false);
      }
    },'json');
  }

  function verDetalle(folio){
    $('#mFol').text(folio);
    $.get('orden_produccion_admin.php',{op:'detalle_ot',folio},function(r){
      if(!r.ok){toast(r.msg||'Error obteniendo detalle',false);return;}
      const data=r.data||[];
      if(!tDet){
        tDet = new DataTable('#tblDet',{
          data,
          paging:false,
          searching:false,
          info:false,
          order:[[0,'asc']],
          columns:[
            {data:'componente'},
            {data:'descripcion'},
            {data:'cantidad',className:'dt-right',
              render:(v)=> Number(v||0).toLocaleString()
            },
            {data:'lote'},
            {data:'cve_almac_ori'},
            {data:'activo',className:'dt-center',
              render:(v)=> v==1||v==='1'?'Sí':'No'
            }
          ]
        });
      }else{
        tDet.clear().rows.add(data).draw(false);
      }
      new bootstrap.Modal(document.getElementById('detalleModal')).show();
    },'json');
  }

  function exportar(){
    const qs = new URLSearchParams({
      op:'export_csv',
      almacen: $('#fAlmacen').val()||'',
      status:  $('#fStatus').val()||'',
      f_ini:   $('#fIni').val()||'',
      f_fin:   $('#fFin').val()||'',
      buscar:  $('#fBuscar').val()||'',
      buscar_lp: $('#fBuscarLP').val()||''
    }).toString();
    window.location = 'orden_produccion_admin.php?'+qs;
  }

  $('#btnBuscar').on('click',buscar);
  $('#btnExport').on('click',exportar);
  $('#fBuscar,#fBuscarLP').on('keypress',e=>{ if(e.which===13) buscar(); });

  // Fechas default: hoy
  const hoy = new Date().toISOString().substring(0,10);
  $('#fIni').val(hoy);
  $('#fFin').val(hoy);

  cargarFiltros();
  buscar();
})();
</script>
</body>
</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
