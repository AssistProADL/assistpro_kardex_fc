<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';

/* ============================================================
   CONEXIÓN
============================================================ */
$pdo = db_pdo();

/* ============================================================
   HELPERS
============================================================ */
function table_exists(string $table): bool {
    return (int)db_val("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = ?
    ", [$table]) > 0;
}

/**
 * Stock disponible en un BL (zona + idy_ubica) para un artículo.
 * Suma ts_existenciapiezas + ts_existenciatarima (solo activos / no QA).
 */
function stock_disponible_en_bl(string $articulo, int $cve_almac, int $idy_ubica, array &$debug = null): float
{
    $total = 0.0;
    $dbg   = [];

    // Piezas
    if (table_exists('ts_existenciapiezas')) {
        $sql1 = "
            SELECT SUM(Existencia)
            FROM ts_existenciapiezas
            WHERE cve_almac    = ?
              AND idy_ubica    = ?
              AND cve_articulo = ?
              AND IFNULL(Cuarentena,0) = 0
        ";
        $p1  = [$cve_almac, $idy_ubica, $articulo];
        $v1  = (float)db_val($sql1, $p1);
        $total += $v1;
        $dbg['piezas'] = ['sql' => $sql1, 'params' => $p1, 'resultado' => $v1];
    }

    // Tarimas
    if (table_exists('ts_existenciatarima')) {
        $sql2 = "
            SELECT SUM(existencia)
            FROM ts_existenciatarima
            WHERE cve_almac    = ?
              AND idy_ubica    = ?
              AND cve_articulo = ?
              AND IFNULL(Cuarentena,0) = 0
              AND IFNULL(Activo,1) = 1
        ";
        $p2  = [$cve_almac, $idy_ubica, $articulo];
        $v2  = (float)db_val($sql2, $p2);
        $total += $v2;
        $dbg['tarima'] = ['sql' => $sql2, 'params' => $p2, 'resultado' => $v2];
    }

    if ($debug !== null) {
        $debug = $dbg;
    }

    return $total;
}

/* ============================================================
   API AJAX (JSON)
============================================================ */
$op = $_GET['op'] ?? $_POST['op'] ?? null;

if ($op) {
    header('Content-Type: application/json; charset=utf-8');

    try {

        /* ---------- ZONAS POR ALMACÉN (c_almacenp -> c_almacen) ---------- */
        if ($op === 'listar_zonas') {
            $almacenp = (int)($_GET['almacenp'] ?? $_POST['almacenp'] ?? 0);

            if (!$almacenp) {
                echo json_encode(['ok' => false, 'error' => 'Almacén requerido']); exit;
            }

            if (!table_exists('c_almacen')) {
                echo json_encode(['ok' => false, 'error' => 'Tabla c_almacen no existe']); exit;
            }

            $rows = db_all("
                SELECT
                    cve_almac,
                    clave_almacen,
                    des_almac
                FROM c_almacen
                WHERE IFNULL(Activo,1) = 1
                  AND cve_almacenp     = ?
                ORDER BY clave_almacen, des_almac
            ", [$almacenp]);

            echo json_encode(['ok' => true, 'data' => $rows]); exit;
        }

        /* ---------- BL DE MANUFACTURA POR ZONA (c_ubicacion) ---------- */
        if ($op === 'listar_bls') {
            $zona = (int)($_GET['zona'] ?? $_POST['zona'] ?? 0);

            if (!$zona) {
                echo json_encode(['ok' => false, 'error' => 'Zona de almacén requerida']); exit;
            }

            if (!table_exists('c_ubicacion')) {
                echo json_encode(['ok' => false, 'error' => 'Tabla c_ubicacion no existe']); exit;
            }

            // AJUSTE: AreaProduccion 'S'/'N' y Activo numérico 0/1
            $rows = db_all("
                SELECT
                    idy_ubica,
                    CodigoCSD AS bl
                FROM c_ubicacion
                WHERE cve_almac                 = ?
                  AND IFNULL(AreaProduccion,'N') = 'S'
                  AND IFNULL(Activo,1)           = 1
                ORDER BY CodigoCSD
            ", [$zona]);

            echo json_encode(['ok' => true, 'data' => $rows]); exit;
        }

        /* ---------- CÁLCULO DE REQUERIMIENTOS ---------- */
        if ($op === 'calc_requerimientos') {
            $producto   = trim($_POST['producto']   ?? '');
            $cantidad   = (float)($_POST['cantidad'] ?? 0);
            $cve_almac  = (int)($_POST['cve_almac']  ?? 0);   // zona (c_almacen.cve_almac)
            $idy_ubica  = (int)($_POST['idy_ubica']  ?? 0);   // BL (c_ubicacion.idy_ubica)
            $wantDebug  = !empty($_POST['debug_stock']);

            if ($producto === '' || $cantidad <= 0) {
                echo json_encode(['ok' => false, 'error' => 'Producto compuesto y cantidad son requeridos']); exit;
            }

            if (!table_exists('t_artcompuesto')) {
                echo json_encode(['ok' => false, 'error' => 'Tabla t_artcompuesto no existe']); exit;
            }
            if (!table_exists('c_articulo')) {
                echo json_encode(['ok' => false, 'error' => 'Tabla c_articulo no existe']); exit;
            }

            // Componentes del BOM (idéntico a bom.php)
            $componentes = db_all("
                SELECT
                    c.Cve_ArtComponente              AS componente,
                    a.des_articulo                   AS descripcion,
                    c.Cantidad                       AS cantidad,
                    COALESCE(c.cve_umed, a.cve_umed) AS unidad
                FROM t_artcompuesto c
                LEFT JOIN c_articulo a
                       ON a.cve_articulo = c.Cve_ArtComponente
                WHERE c.Cve_Articulo = ?
                  AND (c.Activo IS NULL OR c.Activo = 1)
                ORDER BY c.Cve_ArtComponente
            ", [$producto]);

            if (!$componentes) {
                echo json_encode(['ok' => false, 'error' => 'El producto no tiene componentes configurados en t_artcompuesto']); exit;
            }

            $detalles    = [];
            $debugStock  = [];

            foreach ($componentes as $idx => $c) {
                $debugRow   = null;
                $stock_bl   = 0.0;

                if ($cve_almac && $idy_ubica) {
                    $stock_bl = stock_disponible_en_bl($c['componente'], $cve_almac, $idy_ubica, $debugRow);
                }

                $cant_por_uni = (float)$c['cantidad'];
                $cant_total   = $cant_por_uni * $cantidad;

                $detalles[] = [
                    'componente'        => $c['componente'],
                    'descripcion'       => $c['descripcion'],
                    'umed'              => $c['unidad'],
                    'cantidad_por_uni'  => $cant_por_uni,
                    'cantidad_total'    => $cant_total,
                    'cantidad_solic'    => $cant_total, // por ahora igual
                    'stock_bl'          => $stock_bl
                ];

                // Para no inflar la respuesta, mandamos el debug del primer componente
                if ($wantDebug && $idx === 0) {
                    $debugStock = $debugRow;
                }
            }

            echo json_encode([
                'ok'          => true,
                'detalles'    => $detalles,
                'debug_stock' => $debugStock
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['ok' => false, 'error' => 'Operación no válida']); exit;

    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]); exit;
    }
}

/* ============================================================
   CARGA INICIAL (HTML)
============================================================ */

// Almacenes (c_almacenp) -> value = id (clave interna)
$almacenesP = [];
if (table_exists('c_almacenp')) {
    $almacenesP = db_all("
        SELECT id, clave, nombre
        FROM c_almacenp
        WHERE IFNULL(Activo,1) = 1
        ORDER BY clave, nombre
    ");
}

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
  .section-title{font-size:11px;font-weight:600;color:#555;margin-bottom:4px;}
  .card-header small{font-size:9px;color:#777;}
  .table-sm td,.table-sm th{padding:.3rem .4rem;vertical-align:middle;}
  .dt-right{text-align:right;}
  .dt-center{text-align:center;}
</style>
</head>
<body>
<div class="container-fluid mt-2">

  <div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <strong><i class="bi bi-diagram-3-fill"></i> Orden de Producción</strong>
        <small class="ms-2 text-muted">Explosión de materiales / Manufactura</small>
      </div>
      <span class="badge bg-secondary">Borrador</span>
    </div>
    <div class="card-body">

      <!-- ENCABEZADO OT -->
      <div class="row g-2 mb-2">
        <div class="col-md-3">
          <label class="form-label section-title">No. OT (interno)</label>
          <input type="text" class="form-control form-control-sm" id="txtOtInterno"
                 value="Se asignará al guardar / importar" readonly>
        </div>
        <div class="col-md-3">
          <label class="form-label section-title">OT ERP</label>
          <input type="text" class="form-control form-control-sm" id="txtOtErp"
                 placeholder="Referencia en ERP (opcional)">
        </div>
        <div class="col-md-3">
          <label class="form-label section-title">Pedido de venta</label>
          <input type="text" class="form-control form-control-sm" id="txtPedido"
                 placeholder="(opcional)">
        </div>
      </div>

      <!-- ALMACÉN / ZONA / BL -->
      <div class="row g-2 mb-3">
        <div class="col-md-3">
          <label class="form-label section-title">Almacén</label>
          <select class="form-select form-select-sm" id="selAlmacen">
            <option value="">Seleccione almacén</option>
            <?php foreach ($almacenesP as $a): ?>
              <option value="<?= (int)$a['id'] ?>">
                (<?= htmlspecialchars($a['clave']) ?>) <?= htmlspecialchars($a['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label section-title">Zona de Almacenaje</label>
          <select class="form-select form-select-sm" id="selZona">
            <option value="">Seleccione zona de almacén</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label section-title">BL Manufactura</label>
          <select class="form-select form-select-sm" id="selBL">
            <option value="">Seleccione BL de Manufactura</option>
          </select>
          <div class="form-text" style="font-size:9px;">
            Se listan únicamente BL marcados como área de producción.
          </div>
        </div>
      </div>

      <!-- PRODUCTO COMPUESTO -->
      <div class="row g-2 mb-2">
        <div class="col-md-6">
          <label class="form-label section-title">Producto compuesto (clave o descripción)</label>
          <div class="input-group input-group-sm">
            <input type="text" class="form-control" id="txtBuscarProd"
                   placeholder="Escribe código o parte de la descripción">
            <button class="btn btn-primary" id="btnBuscarProd">
              <i class="bi bi-search"></i>
            </button>
          </div>
          <div class="form-text" style="font-size:9px;">
            Se busca en c_articulo donde Compuesto = 'S'.
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label section-title">Producto compuesto seleccionado</label>
          <input type="text" class="form-control form-control-sm" id="txtProdSel" readonly>
        </div>
        <div class="col-md-3">
          <label class="form-label section-title">Descripción</label>
          <input type="text" class="form-control form-control-sm" id="txtProdDesc" readonly>
        </div>
      </div>

      <div class="row g-2 mb-2">
        <div class="col-md-2">
          <label class="form-label section-title">UMed</label>
          <input type="text" class="form-control form-control-sm" id="txtUMed" readonly>
        </div>
        <div class="col-md-2">
          <label class="form-label section-title">Fecha OT</label>
          <input type="date" class="form-control form-control-sm" id="fecOT"
                 value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label section-title">Fecha compromiso</label>
          <input type="date" class="form-control form-control-sm" id="fecComp"
                 value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label section-title">Cantidad a producir</label>
          <input type="number" min="1" step="1" class="form-control form-control-sm" id="txtCantidad" value="10">
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
          <button class="btn btn-primary btn-sm" id="btnCalc">
            <i class="bi bi-calculator"></i> Calcular requerimientos
          </button>
          <button class="btn btn-outline-secondary btn-sm" id="btnCsv" disabled>
            <i class="bi bi-filetype-csv"></i> Exportar CSV
          </button>
        </div>
      </div>

      <hr>

      <!-- RESUMEN CABECERA SELECCIONADA -->
      <div id="resCabecera" class="mb-2" style="font-size:10px; display:none;">
        <strong>Producto compuesto:</strong> <span id="lblProd"></span> —
        <strong>Cantidad a producir:</strong> <span id="lblCant"></span> |
        <strong>Fecha OT:</strong> <span id="lblFecOT"></span> |
        <strong>Fecha compromiso:</strong> <span id="lblFecComp"></span> |
        <strong>BL Manufactura:</strong> <span id="lblBL"></span>
      </div>

      <!-- TABLA COMPONENTES -->
      <div class="table-responsive">
        <table class="table table-sm table-striped table-hover w-100" id="tblComponentes">
          <thead>
            <tr>
              <th>Componente</th>
              <th>Descripción</th>
              <th>UMed</th>
              <th class="dt-right">Cantidad por unidad</th>
              <th class="dt-right">Cantidad total</th>
              <th class="dt-right">Cantidad solicitada</th>
              <th class="dt-right">Stock disponible (BL)</th>
            </tr>
          </thead>
          <tbody>
            <!-- se llena por JS -->
          </tbody>
        </table>
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

  let dtComp = null;
  let prodSel = null; // código del producto compuesto

  function alertMsg(msg){
    window.alert(msg);
  }

  /* ==========================
     COMBOS: ALMACÉN → ZONA → BL
  ========================== */
  function cargarZonas() {
    const alm = $('#selAlmacen').val();
    $('#selZona').empty().append('<option value="">Seleccione zona de almacén</option>');
    $('#selBL').empty().append('<option value="">Seleccione BL de Manufactura</option>');
    if (!alm) return;

    $.get('orden_produccion.php', { op:'listar_zonas', almacenp: alm }, function(r){
      if (!r.ok) {
        console.log('DEBUG zonas:', r);
        alertMsg(r.error || 'Error al cargar zonas');
        return;
      }
      (r.data || []).forEach(z => {
        $('#selZona').append(
          $('<option>', {
            value: z.cve_almac,
            text: '(' + z.clave_almacen + ') ' + z.des_almac
          })
        );
      });
    }, 'json');
  }

  function cargarBL() {
    const zona = $('#selZona').val();
    $('#selBL').empty().append('<option value="">Seleccione BL de Manufactura</option>');
    if (!zona) return;

    $.get('orden_produccion.php', { op:'listar_bls', zona: zona }, function(r){
      if (!r.ok) {
        console.log('DEBUG BL:', r);
        alertMsg(r.error || 'Error al cargar BL');
        return;
      }
      (r.data || []).forEach(b => {
        $('#selBL').append(
          $('<option>', {
            value: b.idy_ubica,
            text: b.bl
          })
        );
      });
    }, 'json');
  }

  $('#selAlmacen').on('change', cargarZonas);
  $('#selZona').on('change', cargarBL);

  /* ==========================
     BÚSQUEDA DE PRODUCTO (usa bom.php)
  ========================== */
  function buscarProducto() {
    const q = $('#txtBuscarProd').val().trim();
    if (!q) {
      alertMsg('Escribe parte de la clave o descripción.');
      return;
    }
    $.get('bom.php', { op:'buscar_compuestos', q:q }, function(r){
      if (!r.ok) {
        alertMsg(r.msg || 'Error en búsqueda');
        return;
      }
      const data = r.data || [];
      if (data.length === 0) {
        alertMsg('No se encontraron productos compuestos que coincidan.');
        return;
      }
      // Tomamos el primero
      const p = data[0];
      prodSel = p.cve_articulo;
      $('#txtProdSel').val(p.cve_articulo);
      $('#txtProdDesc').val(p.des_articulo);
      $('#txtUMed').val(p.unidadMedida || '');
    }, 'json');
  }

  $('#btnBuscarProd').on('click', buscarProducto);
  $('#txtBuscarProd').on('keypress', function(e){
    if (e.which === 13) {
      e.preventDefault();
      buscarProducto();
    }
  });

  /* ==========================
     CALCULAR REQUERIMIENTOS
  ========================== */
  function calcular(){
    if (!prodSel) {
      alertMsg('Selecciona primero un producto compuesto.');
      return;
    }
    const cant = parseFloat($('#txtCantidad').val() || '0');
    if (!(cant > 0)) {
      alertMsg('Captura una cantidad a producir válida.');
      return;
    }

    const zona = $('#selZona').val();
    const bl   = $('#selBL').val();

    $.post('orden_produccion.php', {
      op: 'calc_requerimientos',
      producto: prodSel,
      cantidad: cant,
      cve_almac: zona,
      idy_ubica: bl,
      debug_stock: 1
    }, function(r){
      console.log('DEBUG calc_requerimientos:', r.debug_stock || {});
      if (!r.ok) {
        alertMsg(r.error || 'Error al calcular requerimientos');
        return;
      }

      const det = r.detalles || [];

      if (dtComp) {
        dtComp.clear().destroy();
        $('#tblComponentes tbody').empty();
      }

      det.forEach(d => {
        $('#tblComponentes tbody').append(
          `<tr>
             <td>${d.componente}</td>
             <td>${d.descripcion || ''}</td>
             <td>${d.umed || ''}</td>
             <td class="dt-right">${d.cantidad_por_uni}</td>
             <td class="dt-right">${d.cantidad_total}</td>
             <td class="dt-right">${d.cantidad_solic}</td>
             <td class="dt-right">${d.stock_bl}</td>
           </tr>`
        );
      });

      dtComp = new DataTable('#tblComponentes', {
        paging: false,
        searching: false,
        info: false,
        order: [[0, 'asc']]
      });

      // resumen
      $('#lblProd').text($('#txtProdSel').val() + ' — ' + ($('#txtProdDesc').val() || ''));
      $('#lblCant').text(cant);
      $('#lblFecOT').text($('#fecOT').val());
      $('#lblFecComp').text($('#fecComp').val());
      $('#lblBL').text($('#selBL option:selected').text() || '');
      $('#resCabecera').show();

      $('#btnCsv').prop('disabled', det.length === 0);

    }, 'json');
  }

  $('#btnCalc').on('click', calcular);

  /* ==========================
     EXPORTAR CSV (simple, solo front)
  ========================== */
  $('#btnCsv').on('click', function(){
    if (!dtComp) {
      alertMsg('No hay datos para exportar.');
      return;
    }
    let csv = 'Componente,Descripcion,UMed,Cantidad_por_unidad,Cantidad_total,Cantidad_solicitada,Stock_BL\n';
    $('#tblComponentes tbody tr').each(function(){
      const tds = $(this).find('td').map(function(){ return $(this).text().trim(); }).get();
      csv += tds.map(v => `"${v.replace(/"/g,'""')}"`).join(',') + "\n";
    });
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'OT_componentes.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

})();
</script>
</body>
</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
