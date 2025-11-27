<?php
@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ============================================================
   HELPERS
============================================================ */
function col_exists(string $table, string $col): bool {
    return (int)db_val(
        "SELECT COUNT(*) 
         FROM information_schema.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
           AND TABLE_NAME = ? 
           AND COLUMN_NAME = ?",
        [$table, $col]
    ) > 0;
}

$HAS_FOLIOIMPORT = col_exists('t_ordenprod', 'FolioImport');

/* ============================================================
   API AJAX
============================================================ */
$op = $_POST['op'] ?? $_GET['op'] ?? null;

if ($op) {
    header('Content-Type: application/json; charset=utf-8');

    try {

        /* ---------- CATÁLOGO DE FOLIOS DE IMPORTACIÓN ---------- */
        if ($op === 'folios_import') {

            if (!$HAS_FOLIOIMPORT) {
                echo json_encode([
                    'ok'   => true,
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rows = db_all("
                SELECT DISTINCT FolioImport
                FROM t_ordenprod
                WHERE FolioImport IS NOT NULL
                  AND FolioImport <> ''
                ORDER BY FechaReg DESC, FolioImport DESC
                LIMIT 200
            ");

            echo json_encode([
                'ok'   => true,
                'data' => $rows
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        /* ---------- BUSCAR OTs ---------- */
        if ($op === 'buscar') {

            $almacen      = trim($_POST['almacen']      ?? '');
            $status       = trim($_POST['status']       ?? '');
            $f_ini        = trim($_POST['f_ini']        ?? '');
            $f_fin        = trim($_POST['f_fin']        ?? '');
            $folioImport  = trim($_POST['folio_import'] ?? '');
            $buscar       = trim($_POST['buscar']       ?? '');
            $buscar_lp    = trim($_POST['buscar_lp']    ?? '');

            $where  = [];
            $params = [];

            if ($almacen !== '' && $almacen !== 'Todos') {
                $where[]  = 'o.cve_almac = ?';
                $params[] = $almacen;
            }

            if ($status !== '' && $status !== 'Todos') {
                $where[]  = 'o.Status = ?';
                $params[] = $status;
            }

            if ($f_ini !== '') {
                $where[]  = 'DATE(o.FechaReg) >= ?';
                $params[] = $f_ini;
            }

            if ($f_fin !== '') {
                $where[]  = 'DATE(o.FechaReg) <= ?';
                $params[] = $f_fin;
            }

            if ($HAS_FOLIOIMPORT && $folioImport !== '' && $folioImport !== 'Todos') {
                $where[]  = 'o.FolioImport = ?';
                $params[] = $folioImport;
            }

            if ($buscar !== '') {
                $where[] = "(
                    o.Folio_Pro    LIKE ?
                 OR " . ($HAS_FOLIOIMPORT ? "IFNULL(o.FolioImport,'')" : "''") . " LIKE ?
                 OR o.Cve_Articulo LIKE ?
                 OR a.des_articulo LIKE ?
                 OR IFNULL(o.Referencia,'') LIKE ?
                 OR IFNULL(o.Cve_Lote,'') LIKE ?
                )";
                $like   = "%$buscar%";
                $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
            }

            if ($buscar_lp !== '') {
                $where[] = "(
                    IFNULL(o.Cve_Lote,'')   LIKE ?
                 OR IFNULL(o.Referencia,'') LIKE ?
                 OR o.Folio_Pro            LIKE ?
                )";
                $likeLP = "%$buscar_lp%";
                $params = array_merge($params, [$likeLP, $likeLP, $likeLP]);
            }

            $selectFolioImport = $HAS_FOLIOIMPORT
                ? ", IFNULL(o.FolioImport,'') AS FolioImport"
                : ", '' AS FolioImport";

            $sql = "
                SELECT
                    o.Folio_Pro,
                    o.cve_almac                                AS Almacen,
                    o.Cve_Articulo,
                    a.des_articulo                             AS NombreProducto,
                    o.Cantidad,
                    DATE_FORMAT(o.Fecha,    '%d/%m/%Y')        AS FechaOT,
                    DATE_FORMAT(o.FechaReg, '%d/%m/%Y')        AS FechaReg,
                    o.Status,
                    IFNULL(o.Referencia,'')                    AS Referencia
                    $selectFolioImport
                FROM t_ordenprod o
                LEFT JOIN c_articulo a
                       ON a.cve_articulo = o.Cve_Articulo
            ";

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY o.FechaReg DESC, o.Folio_Pro DESC LIMIT 500';

            $rows = db_all($sql, $params);

            echo json_encode([
                'ok'   => true,
                'data' => $rows,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        /* ---------- DETALLE DE COMPONENTES POR OT ---------- */
        if ($op === 'detalle_ot') {

            $folio = trim($_POST['folio'] ?? '');
            if ($folio === '') {
                throw new Exception('Folio OT requerido.');
            }

            $sql = "
                SELECT
                    o.Folio_Pro,
                    o.cve_almac                       AS Almacen,
                    o.Cve_Articulo                    AS articulo_pt,
                    pt.des_articulo                  AS desc_pt,
                    o.Cantidad                        AS cant_ot,
                    c.Cve_ArtComponente               AS componente,
                    comp.des_articulo                 AS descripcion,
                    c.cve_umed                        AS uom,
                    c.Cantidad                        AS factor_pt,
                    (c.Cantidad * o.Cantidad)         AS requerido
                FROM t_ordenprod o
                JOIN t_artcompuesto c
                  ON c.Cve_Articulo = o.Cve_Articulo
                 AND IFNULL(c.Activo,1) = 1
                LEFT JOIN c_articulo comp
                  ON comp.cve_articulo = c.Cve_ArtComponente
                LEFT JOIN c_articulo pt
                  ON pt.cve_articulo = o.Cve_Articulo
                WHERE o.Folio_Pro = ?
                ORDER BY c.Cve_ArtComponente
            ";

            $rows = db_all($sql, [$folio]);

            echo json_encode([
                'ok'   => true,
                'data' => $rows,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        /* ---------- CONSOLIDADO DE COMPONENTES SEGÚN FILTROS ---------- */
        if ($op === 'consolidado') {

            $almacen      = trim($_POST['almacen']      ?? '');
            $status       = trim($_POST['status']       ?? '');
            $f_ini        = trim($_POST['f_ini']        ?? '');
            $f_fin        = trim($_POST['f_fin']        ?? '');
            $folioImport  = trim($_POST['folio_import'] ?? '');
            $buscar       = trim($_POST['buscar']       ?? '');
            $buscar_lp    = trim($_POST['buscar_lp']    ?? '');

            $where  = [];
            $params = [];

            if ($almacen !== '' && $almacen !== 'Todos') {
                $where[]  = 'o.cve_almac = ?';
                $params[] = $almacen;
            }

            if ($status !== '' && $status !== 'Todos') {
                $where[]  = 'o.Status = ?';
                $params[] = $status;
            }

            if ($f_ini !== '') {
                $where[]  = 'DATE(o.FechaReg) >= ?';
                $params[] = $f_ini;
            }

            if ($f_fin !== '') {
                $where[]  = 'DATE(o.FechaReg) <= ?';
                $params[] = $f_fin;
            }

            if ($HAS_FOLIOIMPORT && $folioImport !== '' && $folioImport !== 'Todos') {
                $where[]  = 'o.FolioImport = ?';
                $params[] = $folioImport;
            }

            if ($buscar !== '') {
                $where[] = "(
                    o.Folio_Pro    LIKE ?
                 OR " . ($HAS_FOLIOIMPORT ? "IFNULL(o.FolioImport,'')" : "''") . " LIKE ?
                 OR o.Cve_Articulo LIKE ?
                 OR IFNULL(o.Referencia,'') LIKE ?
                 OR IFNULL(o.Cve_Lote,'') LIKE ?
                )";
                $like   = "%$buscar%";
                $params = array_merge($params, [$like, $like, $like, $like, $like]);
            }

            if ($buscar_lp !== '') {
                $where[] = "(
                    IFNULL(o.Cve_Lote,'')   LIKE ?
                 OR IFNULL(o.Referencia,'') LIKE ?
                 OR o.Folio_Pro            LIKE ?
                )";
                $likeLP = "%$buscar_lp%";
                $params = array_merge($params, [$likeLP, $likeLP, $likeLP]);
            }

            $sql = "
                SELECT
                    c.Cve_ArtComponente               AS componente,
                    comp.des_articulo                 AS descripcion,
                    c.cve_umed                        AS uom,
                    SUM(o.Cantidad)                   AS total_ot,
                    SUM(c.Cantidad)                   AS total_factor_pt,
                    SUM(c.Cantidad * o.Cantidad)      AS requerido_total,
                    COUNT(DISTINCT o.Folio_Pro)       AS num_ots
                FROM t_ordenprod o
                JOIN t_artcompuesto c
                  ON c.Cve_Articulo = o.Cve_Articulo
                 AND IFNULL(c.Activo,1) = 1
                LEFT JOIN c_articulo comp
                  ON comp.cve_articulo = c.Cve_ArtComponente
            ";

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= "
                GROUP BY c.Cve_ArtComponente, comp.des_articulo, c.cve_umed
                ORDER BY c.Cve_ArtComponente
            ";

            $rows = db_all($sql, $params);

            echo json_encode([
                'ok'   => true,
                'data' => $rows
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        throw new Exception('Operación no soportada.');

    } catch (Throwable $e) {
        echo json_encode([
            'ok'  => false,
            'msg' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* ============================================================
   VISTA
============================================================ */
$TITLE = 'Administración de Órdenes de Trabajo';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid mt-2">

  <div class="card">
    <!-- TÍTULO CORPORATIVO -->
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="bi bi-diagram-3-fill me-2"></i>
        Administración de Órdenes de Trabajo
      </h5>
      <small class="text-light">Mostrando hasta 500 órdenes más recientes según filtros</small>
    </div>

    <div class="card-body">

      <!-- FILTROS (fila 1) -->
      <div class="row g-2 mb-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-0">Almacén</label>
          <select id="cmbAlmacen" class="form-select form-select-sm">
            <option value="">Todos</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Status</label>
          <select id="cmbStatus" class="form-select form-select-sm">
            <option value="Todos">Todos</option>
            <option value="P">P - Pendiente</option>
            <option value="T">T - Terminado</option>
            <option value="C">C - Cancelado</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Fecha inicio</label>
          <input type="date" id="fIni" class="form-control form-control-sm">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Fecha fin</label>
          <input type="date" id="fFin" class="form-control form-control-sm">
        </div>

        <div class="col-md-3 text-end">
          <button id="btnBuscar" class="btn btn-primary btn-sm">
            <i class="bi bi-search"></i> Buscar
          </button>
          <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm">
            Limpiar
          </button>
          <button id="btnConsolidado" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-collection"></i> Consolidado
          </button>
        </div>
      </div>

      <!-- FILTROS (fila 2) -->
      <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-0">Buscar (folio / producto / referencia)</label>
          <input type="text" id="fBuscar" class="form-control form-control-sm"
                 placeholder="Texto a buscar">
        </div>
        <div class="col-md-4">
          <label class="form-label mb-0">Buscar LP / lote</label>
          <input type="text" id="fBuscarLP" class="form-control form-control-sm"
                 placeholder="Lote / LP / referencia">
        </div>
        <div class="col-md-4">
          <label class="form-label mb-0">Folio de importación</label>
          <select id="cmbFolioImport" class="form-select form-select-sm">
            <option value="Todos">Todos</option>
          </select>
        </div>
      </div>

      <!-- TABLA PRINCIPAL -->
      <div class="table-responsive">
        <table id="tblOT" class="table table-sm table-striped table-bordered align-middle">
          <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Folio OT</th>
            <th>Almacén</th>
            <th>Clave producto</th>
            <th>Nombre producto</th>
            <th>Cantidad</th>
            <th>Fecha OT</th>
            <th>Fecha alta</th>
            <th>Status</th>
            <th>Referencia</th>
            <th>Folio importación</th>
            <th>Acciones</th>
          </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- MODAL: COMPONENTES X OT -->
<div class="modal fade" id="mdlComponentes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Componentes de OT</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="infoOT" class="mb-2 text-muted" style="font-size:10px;"></div>
        <div class="table-responsive">
          <table id="tblComp" class="table table-sm table-striped table-bordered align-middle">
            <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Componente</th>
              <th>Descripción</th>
              <th>UOM</th>
              <th>Factor x PT</th>
              <th>Cant. OT</th>
              <th>Requerido</th>
            </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: CONSOLIDADO -->
<div class="modal fade" id="mdlConsolidado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Consolidado de componentes (según filtros)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="infoCons" class="mb-2 text-muted" style="font-size:10px;"></div>
        <div class="table-responsive">
          <table id="tblCons" class="table table-sm table-striped table-bordered align-middle">
            <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Componente</th>
              <th>Descripción</th>
              <th>UOM</th>
              <th># OTs</th>
              <th>Total OTs (piezas PT)</th>
              <th>Total requerido</th>
            </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST SIMPLE PARA ERRORES -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
  <div id="appToast" class="toast align-items-center border-0 text-bg-danger" role="alert"
       aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="appToastBody" class="toast-body">...</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
              data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
(function () {
  'use strict';

  let dtOT   = null;
  let dtComp = null;
  let dtCons = null;
  let toastObj = null;

  function toast(msg) {
    const el   = document.getElementById('appToast');
    const body = document.getElementById('appToastBody');
    body.textContent = msg || 'Error desconocido';

    if (!toastObj) {
      toastObj = new bootstrap.Toast(el, {delay: 3500});
    }
    toastObj.show();
  }

  // =============== CARGAR ALMACENES DESDE API =================
  function cargarAlmacenes() {
    $.getJSON('../api/filtros_assistpro.php', {action: 'init'})
      .done(function (r) {
        if (!r || r.ok === false) {
          return;
        }
        const $cmb = $('#cmbAlmacen');
        $cmb.empty().append('<option value="">Todos</option>');
        const almacenes = r.almacenes || [];
        almacenes.forEach(function (a) {
          const val = a.cve_almac || a.clave_almacen || '';
          const txt = '(' + val + ') ' + (a.des_almac || val);
          $cmb.append($('<option>').val(val).text(txt));
        });
      })
      .fail(function () {
        console.warn('No se pudieron cargar almacenes desde filtros_assistpro.php');
      });
  }

  // =============== CARGAR FOLIOS DE IMPORTACIÓN =================
  function cargarFolios() {
    $.post('orden_produccion_admin.php', {op: 'folios_import'}, function (r) {
      if (!r || r.ok === false) {
        return;
      }
      const $cmb = $('#cmbFolioImport');
      $cmb.empty();
      $cmb.append('<option value="Todos">Todos</option>');
      (r.data || []).forEach(function (row) {
        const fol = row.FolioImport || '';
        if (!fol) return;
        $cmb.append($('<option>').val(fol).text(fol));
      });
    }, 'json').fail(function () {
      console.warn('No se pudieron cargar folios de importación');
    });
  }

  // =============== BUSCAR OTs =================
  function getFiltrosPayload() {
    return {
      almacen:      $('#cmbAlmacen').val()      || '',
      status:       $('#cmbStatus').val()       || '',
      f_ini:        $('#fIni').val()            || '',
      f_fin:        $('#fFin').val()            || '',
      buscar:       $('#fBuscar').val()         || '',
      buscar_lp:    $('#fBuscarLP').val()       || '',
      folio_import: $('#cmbFolioImport').val()  || ''
    };
  }

  function buscar() {
    const payload = Object.assign({op: 'buscar'}, getFiltrosPayload());

    $.post('orden_produccion_admin.php', payload, function (r) {
      if (!r || r.ok === false) {
        toast((r && r.msg) || 'Error al buscar órdenes de trabajo');
        return;
      }
      renderTabla(r.data || []);
    }, 'json').fail(function () {
      toast('Error de comunicación con el servidor (buscar)');
    });
  }

  function renderTabla(data) {
    if (dtOT) {
      dtOT.clear();
      dtOT.rows.add(data);
      dtOT.draw();
      return;
    }

    dtOT = $('#tblOT').DataTable({
      data: data,
      paging: true,
      searching: false,
      info: true,
      lengthChange: false,
      pageLength: 25,
      order: [],
      columns: [
        {
          data: null,
          className: 'text-center',
          render: function (data, type, row, meta) {
            return meta.row + 1;
          }
        },
        { data: 'Folio_Pro' },
        { data: 'Almacen' },
        { data: 'Cve_Articulo' },
        { data: 'NombreProducto' },
        {
          data: 'Cantidad',
          className: 'text-end'
        },
        { data: 'FechaOT' },
        { data: 'FechaReg' },
        { data: 'Status' },
        { data: 'Referencia' },
        { data: 'FolioImport' },
        {
          data: null,
          orderable: false,
          className: 'text-center',
          render: function () {
            return '<button type="button" class="btn btn-sm btn-outline-primary btn-comp">Componentes</button>';
          }
        }
      ]
    });
  }

  // =============== COMPONENTES POR OT =================
  function cargarComponentes(row) {
    if (!row || !row.Folio_Pro) {
      toast('Folio OT no disponible');
      return;
    }

    $('#infoOT').text(
      'Folio OT: ' + (row.Folio_Pro || '') +
      ' | Almacén: ' + (row.Almacen || '') +
      ' | Producto: ' + (row.Cve_Articulo || '') +
      ' - ' + (row.NombreProducto || '') +
      ' | Cantidad: ' + (row.Cantidad || '')
    );

    if (dtComp) {
      dtComp.clear().draw();
    }
    $('#tblComp tbody').empty();

    $.post('orden_produccion_admin.php', {
      op:    'detalle_ot',
      folio: row.Folio_Pro
    }, function (r) {
      if (!r || r.ok === false) {
        toast((r && r.msg) || 'Error al obtener componentes');
        return;
      }

      const data = r.data || [];
      if (!dtComp) {
        dtComp = $('#tblComp').DataTable({
          paging: false,
          searching: false,
          info: false,
          lengthChange: false,
          order: [],
          columns: [
            {
              data: null,
              className: 'text-center',
              render: function (data, type, row, meta) {
                return meta.row + 1;
              }
            },
            { data: 'componente' },
            { data: 'descripcion' },
            { data: 'uom' },
            {
              data: 'factor_pt',
              className: 'text-end'
            },
            {
              data: 'cant_ot',
              className: 'text-end'
            },
            {
              data: 'requerido',
              className: 'text-end'
            }
          ]
        });
      }

      dtComp.clear();
      dtComp.rows.add(data);
      dtComp.draw();

      new bootstrap.Modal(document.getElementById('mdlComponentes')).show();

    }, 'json').fail(function () {
      toast('Error de comunicación con el servidor (componentes)');
    });
  }

  // =============== CONSOLIDADO SEGÚN FILTROS =================
  function cargarConsolidado() {
    const payload = Object.assign({op: 'consolidado'}, getFiltrosPayload());

    $.post('orden_produccion_admin.php', payload, function (r) {
      if (!r || r.ok === false) {
        toast((r && r.msg) || 'Error al obtener consolidado');
        return;
      }

      const data = r.data || [];

      $('#infoCons').text(
        'Consolidado generado con los filtros actuales (almacén, status, fechas, folio de importación y búsquedas).'
      );

      if (!dtCons) {
        dtCons = $('#tblCons').DataTable({
          paging: false,
          searching: false,
          info: false,
          lengthChange: false,
          order: [],
          columns: [
            {
              data: null,
              className: 'text-center',
              render: function (data, type, row, meta) {
                return meta.row + 1;
              }
            },
            { data: 'componente' },
            { data: 'descripcion' },
            { data: 'uom' },
            {
              data: 'num_ots',
              className: 'text-end'
            },
            {
              data: 'total_ot',
              className: 'text-end'
            },
            {
              data: 'requerido_total',
              className: 'text-end'
            }
          ]
        });
      }

      dtCons.clear();
      dtCons.rows.add(data);
      dtCons.draw();

      new bootstrap.Modal(document.getElementById('mdlConsolidado')).show();

    }, 'json').fail(function () {
      toast('Error de comunicación con el servidor (consolidado)');
    });
  }

  // =============== INIT =================
  $(function () {
    // Fechas default: últimos 7 días
    const hoy   = new Date();
    const fin   = hoy.toISOString().substring(0,10);
    const iniDt = new Date(hoy);
    iniDt.setDate(iniDt.getDate() - 7);
    const ini   = iniDt.toISOString().substring(0,10);
    $('#fIni').val(ini);
    $('#fFin').val(fin);

    cargarAlmacenes();
    cargarFolios();
    buscar();

    $('#btnBuscar').on('click', function (e) {
      e.preventDefault();
      buscar();
    });

    $('#btnLimpiar').on('click', function (e) {
      e.preventDefault();
      $('#cmbAlmacen').val('');
      $('#cmbStatus').val('Todos');
      $('#fBuscar').val('');
      $('#fBuscarLP').val('');
      $('#cmbFolioImport').val('Todos');
      buscar();
    });

    $('#btnConsolidado').on('click', function (e) {
      e.preventDefault();
      cargarConsolidado();
    });

    $('#fBuscar,#fBuscarLP').on('keypress', function (e) {
      if (e.which === 13) {
        buscar();
      }
    });

    // Delegación: siempre toma la fila correcta
    $('#tblOT tbody').on('click', '.btn-comp', function () {
      const row = dtOT.row($(this).closest('tr')).data();
      cargarComponentes(row);
    });
  });

})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
