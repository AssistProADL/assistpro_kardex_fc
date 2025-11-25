<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

/* ============================================================
   HELPERS
============================================================ */

function first_existing_col(PDO $pdo, string $table, array $candidates) {
    $in = implode("','", array_map('addslashes', $candidates));
    $sql = "
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = ?
          AND COLUMN_NAME  IN ('$in')
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['COLUMN_NAME'] : null;
}

/**
 * Stock disponible en un BL específico de manufactura
 * usando la vista v_existencias_por_ubicacion_ao
 */
function stock_disponible_en_bl(PDO $pdo, string $cve_almac, string $bl, string $cve_articulo): float {
    static $colBL = null;
    if ($colBL === null) {
        // Ajusta aquí si tu vista tiene otro nombre de campo para BL
        $colBL = first_existing_col($pdo, 'v_existencias_por_ubicacion_ao', [
            'CodigoCSD','codigocsd','BL','bl'
        ]);
        if (!$colBL) $colBL = 'CodigoCSD';
    }

    $sql = "
        SELECT SUM(existencia) AS stock
        FROM v_existencias_por_ubicacion_ao
        WHERE cve_almac    = :alm
          AND $colBL       = :bl
          AND cve_articulo = :art
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':alm' => $cve_almac,
        ':bl'  => $bl,
        ':art' => $cve_articulo
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)($row['stock'] ?? 0);
}

/* ============================================================
   API AJAX
============================================================ */

$op = $_GET['op'] ?? $_POST['op'] ?? null;

if ($op) {

    // ---------- ZONAS POR ALMACÉN ----------
    if ($op === 'zonas_by_almacen') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $alm = trim($_GET['cve_almac'] ?? $_POST['cve_almac'] ?? '');
            if ($alm === '') throw new Exception('Almacén requerido');

            $sql = "
                SELECT id_zona_almac, des_zona_almac
                FROM c_almacenp
                WHERE cve_almac = ?
                  AND (Activo = 1 OR Activo IS NULL)
                ORDER BY des_zona_almac
            ";
            $rows = db_all($sql, [$alm]);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ---------- BLs DE MANUFACTURA POR ZONA ----------
    if ($op === 'bl_by_zona') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $alm  = trim($_GET['cve_almac'] ?? $_POST['cve_almac'] ?? '');
            $zona = trim($_GET['zona_id']   ?? $_POST['zona_id']   ?? '');
            if ($alm === '' || $zona === '') throw new Exception('Almacén y zona requeridos');

            // IMPORTANT: Ajusta nombres de campos si difieren
            $sql = "
                SELECT idy_ubica, CodigoCSD AS bl
                FROM c_ubicacion
                WHERE cve_almac    = ?
                  AND id_zona_almac= ?
                  AND (AreaProduccion = 'S' OR AreaProduccion IS NULL)
                  AND (Activo = 1 OR Activo IS NULL)
                ORDER BY CodigoCSD
            ";
            $rows = db_all($sql, [$alm, $zona]);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ---------- BÚSQUEDA DE PRODUCTOS COMPUESTOS ----------
    if ($op === 'buscar_prod') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $q = trim($_GET['q'] ?? $_POST['q'] ?? '');

            $sql = "
                SELECT
                    cve_articulo,
                    des_articulo,
                    cve_umed AS unidadMedida
                FROM c_articulo
                WHERE COALESCE(Compuesto,'N')='S'
            ";
            $params = [];
            if ($q !== '') {
                $sql .= " AND (cve_articulo LIKE ? OR des_articulo LIKE ?) ";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
            $sql .= " ORDER BY des_articulo LIMIT 20";

            $rows = db_all($sql, $params);
            echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ---------- SELECCIONAR PRODUCTO COMPUESTO POR CLAVE ----------
    if ($op === 'seleccionar_prod') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $cve = trim($_GET['cve'] ?? $_POST['cve'] ?? '');
            if ($cve === '') throw new Exception('Clave de producto requerida');

            $sql = "
                SELECT
                    cve_articulo,
                    des_articulo,
                    cve_umed AS unidadMedida
                FROM c_articulo
                WHERE cve_articulo = ?
                  AND COALESCE(Compuesto,'N')='S'
            ";
            $row = db_row($sql, [$cve]);
            if (!$row) throw new Exception('Producto compuesto no encontrado o no marcado como compuesto.');

            echo json_encode(['ok'=>true,'producto'=>$row], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ---------- EXPLOSIÓN DE MATERIALES ----------
    if ($op === 'explosion') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $padre     = trim($_POST['prod']        ?? '');
            $cantidad  = (float)($_POST['cantidad'] ?? 0);
            $cve_almac = trim($_POST['cve_almac']   ?? '');
            $bl        = trim($_POST['bl']          ?? '');

            if ($padre === '' || $cantidad <= 0) {
                throw new Exception('Producto y cantidad a producir son requeridos.');
            }

            // Encabezado del producto
            $prod = db_row("
                SELECT
                    cve_articulo,
                    des_articulo,
                    cve_umed AS unidadMedida
                FROM c_articulo
                WHERE cve_articulo = ?
            ", [$padre]);

            if (!$prod) throw new Exception('Producto no encontrado.');

            // Componentes
            $sql = "
                SELECT
                    c.Cve_ArtComponente              AS componente,
                    a.des_articulo                   AS descripcion,
                    c.Cantidad                       AS cant_por_unidad,
                    COALESCE(c.cve_umed,a.cve_umed)  AS unidad,
                    COALESCE(c.Etapa,'')             AS etapa
                FROM t_artcompuesto c
                LEFT JOIN c_articulo a
                       ON a.cve_articulo = c.Cve_ArtComponente
                WHERE c.Cve_Articulo = ?
                  AND (c.Activo IS NULL OR c.Activo=1)
                ORDER BY c.Cve_ArtComponente
            ";
            $rows = db_all($sql, [$padre]);

            $detalle = [];
            foreach ($rows as $r) {
                $cantUnidad = (float)$r['cant_por_unidad'];
                $cantTotal  = $cantUnidad * $cantidad;
                $stockBL    = 0;

                if ($cve_almac !== '' && $bl !== '') {
                    $stockBL = stock_disponible_en_bl($pdo, $cve_almac, $bl, $r['componente']);
                }

                $detalle[] = [
                    'componente'        => $r['componente'],
                    'descripcion'       => $r['descripcion'],
                    'unidad'            => $r['unidad'],
                    'etapa'             => $r['etapa'],
                    'cant_por_unidad'   => $cantUnidad,
                    'cant_total'        => $cantTotal,
                    'cant_solicitada'   => $cantTotal,   // de momento igual
                    'stock_disponible'  => $stockBL
                ];
            }

            echo json_encode([
                'ok'        => true,
                'producto'  => $prod,
                'cantidad'  => $cantidad,
                'detalles'  => $detalle
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ---------- EXPORTAR CSV DE LA EXPLOSIÓN ----------
    if ($op === 'export_csv') {
        $padre     = trim($_GET['prod']        ?? '');
        $cantidad  = (float)($_GET['cantidad'] ?? 0);
        $cve_almac = trim($_GET['cve_almac']   ?? '');
        $bl        = trim($_GET['bl']          ?? '');

        if ($padre === '' || $cantidad <= 0) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "Parámetros inválidos.";
            exit;
        }

        // Reutilizamos la lógica de explosion
        $prod = db_row("
            SELECT
                cve_articulo,
                des_articulo,
                cve_umed AS unidadMedida
            FROM c_articulo
            WHERE cve_articulo = ?
        ", [$padre]);

        $sql = "
            SELECT
                c.Cve_ArtComponente              AS componente,
                a.des_articulo                   AS descripcion,
                c.Cantidad                       AS cant_por_unidad,
                COALESCE(c.cve_umed,a.cve_umed)  AS unidad,
                COALESCE(c.Etapa,'')             AS etapa
            FROM t_artcompuesto c
            LEFT JOIN c_articulo a
                   ON a.cve_articulo = c.Cve_ArtComponente
            WHERE c.Cve_Articulo = ?
              AND (c.Activo IS NULL OR c.Activo=1)
            ORDER BY c.Cve_ArtComponente
        ";
        $rows = db_all($sql, [$padre]);

        $filename = "OT_BOM_{$padre}_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $out = fopen('php://output', 'w');

        fputcsv($out, ['Orden de Producción - Explosión de materiales']);
        fputcsv($out, ['Producto compuesto', $prod['cve_articulo'] ?? $padre]);
        fputcsv($out, ['Descripción', $prod['des_articulo'] ?? '']);
        fputcsv($out, ['UMed', $prod['unidadMedida'] ?? '']);
        fputcsv($out, ['Cantidad a producir', $cantidad]);
        fputcsv($out, []);
        fputcsv($out, ['Componente','Descripción','UMed','Etapa','Cant. por unidad','Cant. total','Cant. solicitada','Stock disponible (BL)']);

        foreach ($rows as $r) {
            $cantUnidad = (float)$r['cant_por_unidad'];
            $cantTotal  = $cantUnidad * $cantidad;
            $stockBL    = 0;
            if ($cve_almac !== '' && $bl !== '') {
                $stockBL = stock_disponible_en_bl($pdo, $cve_almac, $bl, $r['componente']);
            }

            fputcsv($out, [
                $r['componente'],
                $r['descripcion'],
                $r['unidad'],
                $r['etapa'],
                $cantUnidad,
                $cantTotal,
                $cantTotal,
                $stockBL
            ]);
        }

        fclose($out);
        exit;
    }

    // Si llega aquí, operación desconocida
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'msg'=>'Operación no válida'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ============================================================
   CARGA INICIAL (HTML)
============================================================ */

// Almacenes
$almacenes = db_all("
    SELECT
        cve_almac,
        CONCAT('(',clave_almacen,') ',des_almac) AS nombre
    FROM c_almacen
    WHERE Activo = 1 OR Activo IS NULL
    ORDER BY clave_almacen, des_almac
");

include __DIR__ . '/../bi/_menu_global.php';
?>
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
  .table-sm td,.table-sm th{padding:.3rem .4rem;vertical-align:middle;}
  .dt-right{text-align:right;}
  .dt-center{text-align:center;}
  .card-header-title{font-size:14px;font-weight:600;}
  .subtext{font-size:9px;color:#6c757d;}
</style>
</head>
<body>
<div class="container-fluid mt-2">

  <div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <span class="card-header-title"><i class="bi bi-gear-wide-connected"></i> Orden de Producción</span>
        <div class="subtext">Explosión de materiales / Manufactura</div>
      </div>
      <div class="subtext">Estado: <span class="badge bg-secondary">Borrador</span></div>
    </div>
    <div class="card-body">

      <!-- DATOS GENERALES OT -->
      <div class="row g-2 mb-2">
        <div class="col-md-3">
          <label class="form-label mb-0">No. OT (interno)</label>
          <input type="text" class="form-control form-control-sm" id="txtOtInterno"
                 value="Se asignará al guardar / importar" readonly>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">OT ERP</label>
          <input type="text" class="form-control form-control-sm" id="txtOtErp"
                 placeholder="Referencia en ERP (opcional)">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Almacén</label>
          <select id="selAlmacen" class="form-select form-select-sm">
            <option value="">Seleccione almacén</option>
            <?php foreach($almacenes as $a): ?>
              <option value="<?php echo htmlspecialchars($a['cve_almac']); ?>">
                <?php echo htmlspecialchars($a['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Zona de Almacenaje</label>
          <select id="selZona" class="form-select form-select-sm">
            <option value="">Seleccione zona de almacén</option>
          </select>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-3">
          <label class="form-label mb-0">BL Manufactura</label>
          <select id="selBLManu" class="form-select form-select-sm">
            <option value="">Seleccione BL de manufactura</option>
          </select>
          <div class="subtext">
            El BL de la lista llena el campo, pero también puedes escribirlo manualmente.
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Pedido de Venta</label>
          <input type="text" id="txtPedido" class="form-control form-control-sm"
                 placeholder="Pedido (opcional)">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Fecha OT</label>
          <input type="date" id="dtFechaOT" class="form-control form-control-sm"
                 value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Fecha compromiso</label>
          <input type="date" id="dtFechaCompromiso" class="form-control form-control-sm"
                 value="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>

      <!-- PRODUCTO COMPUESTO -->
      <div class="row g-2 mb-2">
        <div class="col-md-5">
          <label class="form-label mb-0">Producto compuesto (clave o descripción)</label>
          <input id="txtProdBuscar" list="dlProductos" class="form-control form-control-sm"
                 placeholder="Escribe código o parte de la descripción">
          <datalist id="dlProductos"></datalist>
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button id="btnSeleccionarProd" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Producto compuesto seleccionado</label>
          <input type="text" id="txtProdSel" class="form-control form-control-sm" readonly>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-0">Descripción</label>
          <input type="text" id="txtProdSelDes" class="form-control form-control-sm" readonly>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-2">
          <label class="form-label mb-0">UMed</label>
          <input type="text" id="txtProdSelUmed" class="form-control form-control-sm" readonly>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-0">Cantidad a producir</label>
          <input type="number" step="0.0001" min="0" id="txtCantidadProducir"
                 class="form-control form-control-sm" value="0">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button id="btnCalcular" class="btn btn-success btn-sm w-100">
            <i class="bi bi-calculator"></i> Calcular requerimientos
          </button>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button id="btnExportarCsv" class="btn btn-outline-secondary btn-sm w-100" disabled>
            <i class="bi bi-filetype-csv"></i> Exportar CSV
          </button>
        </div>
      </div>

      <!-- RESUMEN ENCABEZADO -->
      <div id="resumenOT" class="alert alert-light border small d-none">
        <div>
          <strong>Producto compuesto:</strong> <span id="resProd"></span>
        </div>
        <div>
          <strong>Cantidad a producir:</strong> <span id="resCant"></span>
          &nbsp;|&nbsp; <strong>UMed:</strong> <span id="resUmed"></span>
          &nbsp;|&nbsp; <strong>BL Manufactura:</strong> <span id="resBL"></span>
        </div>
        <div class="subtext">
          Cantidad total = Cantidad por unidad × Cantidad a producir.
          La columna <strong>Stock disponible (BL)</strong> considera sólo el BL de manufactura seleccionado.
        </div>
      </div>

      <!-- COMPONENTES -->
      <div class="table-responsive">
        <table id="tblComponentes" class="table table-sm table-striped table-hover w-100">
          <thead>
            <tr>
              <th>Componente</th>
              <th>Descripción</th>
              <th>UMed</th>
              <th>Etapa</th>
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
  <div id="appToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="appToastBody" class="toast-body">...</div>
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

  let dtComp = null;
  let prodSel = null;
  let toastObj = null;

  function toast(msg, type='info'){
    const el   = document.getElementById('appToast');
    const body = document.getElementById('appToastBody');
    body.textContent = msg;
    el.classList.remove('text-bg-primary','text-bg-success','text-bg-danger','text-bg-warning');
    if(type === 'success') el.classList.add('text-bg-success');
    else if(type === 'danger') el.classList.add('text-bg-danger');
    else if(type === 'warning') el.classList.add('text-bg-warning');
    else el.classList.add('text-bg-primary');
    if(!toastObj){ toastObj = new bootstrap.Toast(el, {delay:3000}); }
    toastObj.show();
  }

  // ---------- Combos ----------

  $('#selAlmacen').on('change', function(){
    const alm = $(this).val();
    $('#selZona').html('<option value="">Seleccione zona de almacén</option>');
    $('#selBLManu').html('<option value="">Seleccione BL de manufactura</option>');
    if(!alm) return;

    $.getJSON('orden_produccion.php', {op:'zonas_by_almacen', cve_almac:alm}, function(r){
      if(!r.ok){ toast(r.msg || 'Error cargando zonas','danger'); return; }
      const $z = $('#selZona');
      (r.data || []).forEach(z => {
        $('<option>')
          .val(z.id_zona_almac)
          .text(z.des_zona_almac)
          .appendTo($z);
      });
    });
  });

  $('#selZona').on('change', function(){
    const alm  = $('#selAlmacen').val();
    const zona = $(this).val();
    $('#selBLManu').html('<option value="">Seleccione BL de manufactura</option>');
    if(!alm || !zona) return;

    $.getJSON('orden_produccion.php', {op:'bl_by_zona', cve_almac:alm, zona_id:zona}, function(r){
      if(!r.ok){ toast(r.msg || 'Error cargando BLs','danger'); return; }
      const $b = $('#selBLManu');
      (r.data || []).forEach(b => {
        $('<option>')
          .val(b.bl)
          .text(b.bl)
          .appendTo($b);
      });
    });
  });

  // ---------- Búsqueda de producto compuesto ----------

  function cargarSugerencias(){
    const q = $('#txtProdBuscar').val().trim();
    if(q.length < 2) return;
    $.getJSON('orden_produccion.php', {op:'buscar_prod', q:q}, function(r){
      if(!r.ok){ return; }
      const $dl = $('#dlProductos').empty();
      (r.data || []).forEach(p => {
        const opt = $('<option>')
          .attr('value', p.cve_articulo)
          .text(p.des_articulo + ' [' + (p.unidadMedida || '') + ']');
        $dl.append(opt);
      });
    });
  }

  $('#txtProdBuscar').on('keyup', function(e){
    if(e.key === 'Enter'){
      seleccionarProducto();
    } else {
      cargarSugerencias();
    }
  });

  $('#btnSeleccionarProd').on('click', function(){
    seleccionarProducto();
  });

  function seleccionarProducto(){
    const cve = $('#txtProdBuscar').val().trim();
    if(!cve){
      toast('Capture la clave o seleccione un producto compuesto de la lista.','warning');
      return;
    }
    $.getJSON('orden_produccion.php', {op:'seleccionar_prod', cve:cve}, function(r){
      if(!r.ok){
        toast(r.msg || 'Producto no encontrado','danger');
        return;
      }
      prodSel = r.producto;
      $('#txtProdSel').val(prodSel.cve_articulo);
      $('#txtProdSelDes').val(prodSel.des_articulo);
      $('#txtProdSelUmed').val(prodSel.unidadMedida || '');
      $('#resumenOT').addClass('d-none');
      if(dtComp){ dtComp.clear().draw(); }
      $('#btnExportarCsv').prop('disabled', true);
      toast('Producto compuesto seleccionado.','success');
    });
  }

  // ---------- Tabla de componentes ----------

  dtComp = new DataTable('#tblComponentes', {
    data: [],
    paging: true,
    pageLength: 25,
    lengthChange: false,
    searching: false,
    info: true,
    order: [[0, 'asc']],
    columns: [
      {data:'componente'},
      {data:'descripcion'},
      {data:'unidad'},
      {data:'etapa'},
      {data:'cant_por_unidad', className:'dt-right',
        render:(d)=> Number(d||0).toLocaleString('es-MX',{minimumFractionDigits:4,maximumFractionDigits:4})},
      {data:'cant_total', className:'dt-right',
        render:(d)=> Number(d||0).toLocaleString('es-MX',{minimumFractionDigits:4,maximumFractionDigits:4})},
      {data:'cant_solicitada', className:'dt-right',
        render:(d)=> Number(d||0).toLocaleString('es-MX',{minimumFractionDigits:4,maximumFractionDigits:4})},
      {data:'stock_disponible', className:'dt-right',
        render:(d)=> Number(d||0).toLocaleString('es-MX',{minimumFractionDigits:4,maximumFractionDigits:4})}
    ]
  });

  // ---------- Calcular requerimientos ----------

  $('#btnCalcular').on('click', function(){
    if(!prodSel){
      toast('Selecciona primero un producto compuesto.','warning');
      return;
    }
    const alm = $('#selAlmacen').val();
    const zona= $('#selZona').val();
    const bl  = $('#selBLManu').val();
    const cant= parseFloat($('#txtCantidadProducir').val() || '0');

    if(!alm){
      toast('Selecciona un almacén.','warning'); return;
    }
    if(!zona){
      toast('Selecciona una zona de almacenaje.','warning'); return;
    }
    if(!bl){
      toast('Selecciona un BL de manufactura.','warning'); return;
    }
    if(!(cant > 0)){
      toast('Captura una cantidad a producir mayor a cero.','warning'); return;
    }

    $.post('orden_produccion.php', {
      op: 'explosion',
      prod: prodSel.cve_articulo,
      cantidad: cant,
      cve_almac: alm,
      bl: bl
    }, function(r){
      if(!r.ok){
        toast(r.msg || 'Error en cálculo de requerimientos','danger');
        return;
      }
      const data = r.detalles || [];
      dtComp.clear().rows.add(data).draw();

      $('#resProd').text((r.producto.cve_articulo || '') + ' — ' + (r.producto.des_articulo || ''));
      $('#resCant').text(cant.toLocaleString('es-MX',{minimumFractionDigits:4,maximumFractionDigits:4}));
      $('#resUmed').text(r.producto.unidadMedida || '');
      $('#resBL').text(bl);
      $('#resumenOT').removeClass('d-none');

      $('#btnExportarCsv').prop('disabled', data.length === 0);
      toast('Requerimientos calculados.','success');
    }, 'json');
  });

  // ---------- Exportar CSV ----------
  $('#btnExportarCsv').on('click', function(){
    if(!prodSel){
      toast('Selecciona un producto compuesto y calcula requerimientos primero.','warning');
      return;
    }
    const alm = $('#selAlmacen').val();
    const bl  = $('#selBLManu').val();
    const cant= parseFloat($('#txtCantidadProducir').val() || '0');
    if(!(cant>0)){
      toast('Cantidad a producir inválida.','warning');
      return;
    }
    const url = 'orden_produccion.php'
      + '?op=export_csv'
      + '&prod='      + encodeURIComponent(prodSel.cve_articulo)
      + '&cantidad='  + encodeURIComponent(cant)
      + '&cve_almac=' + encodeURIComponent(alm || '')
      + '&bl='        + encodeURIComponent(bl  || '');
    window.location = url;
  });

})();
</script>
</body>
</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
