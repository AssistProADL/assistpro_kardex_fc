<?php
//@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ============================================================
   API AJAX
============================================================ */
$op = $_POST['op'] ?? $_GET['op'] ?? null;

if ($op) {
  header('Content-Type: application/json; charset=utf-8');

  try {

    /* =========================================
       COMÚN: construir WHERE de filtros
    ==========================================*/
    function build_where_ots(&$params)
    {
      $almacen = trim($_POST['almacen'] ?? $_GET['almacen'] ?? '');
      $status = trim($_POST['status'] ?? $_GET['status'] ?? '');
      $f_ini = trim($_POST['f_ini'] ?? $_GET['f_ini'] ?? '');
      $f_fin = trim($_POST['f_fin'] ?? $_GET['f_fin'] ?? '');
      $buscar = trim($_POST['buscar'] ?? $_GET['buscar'] ?? '');

      $where = [];

      if ($almacen !== '') {
        $where[] = "o.cve_almac = ?";
        $params[] = $almacen;
      }

      if ($status !== '' && strtoupper($status) !== 'TODOS') {
        $where[] = "IFNULL(o.Status,'') = ?";
        $params[] = $status;
      }

      if ($f_ini !== '' && $f_fin !== '') {
        $where[] = "DATE(o.FechaReg) BETWEEN ? AND ?";
        $params[] = $f_ini;
        $params[] = $f_fin;
      } elseif ($f_ini !== '') {
        $where[] = "DATE(o.FechaReg) >= ?";
        $params[] = $f_ini;
      } elseif ($f_fin !== '') {
        $where[] = "DATE(o.FechaReg) <= ?";
        $params[] = $f_fin;
      }

      if ($buscar !== '') {
        $where[] = "(o.Folio_Pro LIKE ? OR o.Referencia LIKE ? OR o.Cve_Articulo LIKE ?)";
        $like = '%' . $buscar . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
      }

      return count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    }

    /* =========================================
       LISTA DE OTs (ADMIN GRID)
    ==========================================*/
    if ($op === 'list_ots') {

      $params = [];
      $whereSql = build_where_ots($params);

      $sql = "
                SELECT
                    o.id,
                    o.Folio_Pro,
                    o.Cve_Articulo,
                    o.Cant_Prod,
                    o.cve_almac,
                    DATE_FORMAT(o.Fecha,    '%d/%m/%Y') AS fecha_ot,
                    DATE_FORMAT(o.FechaReg, '%d/%m/%Y') AS fecha_reg,
                    IFNULL(o.Status,'')                 AS status,
                    IFNULL(o.Referencia,'')             AS referencia
                FROM t_ordenprod o
                $whereSql
                ORDER BY o.FechaReg DESC, o.id DESC
                LIMIT 500
            ";

      $rows = db_all($sql, $params);

      echo json_encode([
        'ok' => true,
        'data' => $rows
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }

    /* =========================================
       DETALLE DE COMPONENTES POR OT
       (usa t_artcompuesto)
    ==========================================*/
    if ($op === 'componentes_ot') {
      $id_ot = intval($_POST['id_ot'] ?? $_GET['id_ot'] ?? 0);
      if ($id_ot <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'OT inválida'], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $sql = "
                SELECT
                    ac.Cve_ArtComponente           AS cve_componente,
                    ca.des_articulo                AS desc_componente,
                    IFNULL(ac.cve_umed, ca.cve_umed) AS uom,
                    ac.Cantidad                    AS factor,
                    o.Cant_Prod                    AS cant_ot,
                    (ac.Cantidad * o.Cant_Prod)    AS cant_requerida
                FROM t_ordenprod o
                JOIN t_artcompuesto ac
                    ON ac.Cve_Articulo = o.Cve_Articulo
                LEFT JOIN c_articulo ca
                    ON ca.cve_articulo = ac.Cve_ArtComponente
                WHERE o.id = ?
                  AND (ac.Activo IS NULL OR ac.Activo <> 0)
                ORDER BY ca.des_articulo, ac.Cve_ArtComponente
            ";

      $rows = db_all($sql, [$id_ot]);

      echo json_encode([
        'ok' => true,
        'data' => $rows
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }

    /* =========================================
       CONSOLIDADO DE COMPONENTES (según filtros)
    ==========================================*/
    if ($op === 'componentes_consol') {
      $params = [];
      $whereSql = build_where_ots($params);

      $sql = "
                SELECT
                    ac.Cve_ArtComponente           AS cve_componente,
                    ca.des_articulo                AS desc_componente,
                    IFNULL(ac.cve_umed, ca.cve_umed) AS uom,
                    SUM(o.Cant_Prod * ac.Cantidad) AS cant_total
                FROM t_ordenprod o
                JOIN t_artcompuesto ac
                    ON ac.Cve_Articulo = o.Cve_Articulo
                LEFT JOIN c_articulo ca
                    ON ca.cve_articulo = ac.Cve_ArtComponente
                $whereSql
                  AND (ac.Activo IS NULL OR ac.Activo <> 0)
                GROUP BY ac.Cve_ArtComponente, ca.des_articulo, uom
                ORDER BY ca.des_articulo, ac.Cve_ArtComponente
            ";

      $rows = db_all($sql, $params);

      echo json_encode([
        'ok' => true,
        'data' => $rows
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Operación no soportada'], JSON_UNESCAPED_UNICODE);
  } catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
  }
  exit;
}

/* ============================================================
   VISTA HTML
============================================================ */
$TITLE = 'Administración de Órdenes de Trabajo';
include __DIR__ . '/../bi/_menu_global.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Administración de Órdenes de Trabajo</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet">

  <style>
    body {
      font-size: 10px;
    }

    .table-sm td,
    .table-sm th {
      padding: .30rem;
    }

    .card-header {
      font-weight: bold;
    }
  </style>
</head>

<body>

  <div class="container-fluid mt-2">

    <div class="card">
      <div class="card-header">
        Administración de Órdenes de Trabajo
      </div>
      <div class="card-body">

        <!-- Filtros -->
        <div class="card mb-3">
          <div class="card-body">
            <div class="row g-2 align-items-end">

              <div class="col-md-3">
                <label for="fAlmacen" class="form-label">Almacén</label>
                <select id="fAlmacen" class="form-select form-select-sm">
                  <option value="">Todos</option>
                </select>
              </div>

              <div class="col-md-2">
                <label for="fStatus" class="form-label">Status</label>
                <select id="fStatus" class="form-select form-select-sm">
                  <option value="">Todos</option>
                  <option value="P">Pendiente</option>
                  <option value="A">En proceso</option>
                  <option value="T">Terminado</option>
                </select>
              </div>

              <div class="col-md-2">
                <label for="fIni" class="form-label">Fecha inicio</label>
                <input type="date" id="fIni" class="form-control form-control-sm">
              </div>

              <div class="col-md-2">
                <label for="fFin" class="form-label">Fecha fin</label>
                <input type="date" id="fFin" class="form-control form-control-sm">
              </div>

              <div class="col-md-3">
                <label for="fBuscar" class="form-label">Buscar (folio / producto / referencia)</label>
                <input type="text" id="fBuscar" class="form-control form-control-sm" placeholder="Texto a buscar">
              </div>

            </div>

            <div class="row mt-3">
              <div class="col">
                <button id="btnBuscar" class="btn btn-primary btn-sm">
                  Buscar
                </button>
                <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm">
                  Limpiar
                </button>
              </div>
              <div class="col text-end">
                <button id="btnExport" class="btn btn-outline-primary btn-sm">
                  Exportar OT Pendientes
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Grid -->
        <div class="table-responsive">
          <table id="tblOT" class="table table-sm table-striped table-bordered align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Folio OT</th>
                <th>Almacén</th>
                <th>Clave producto</th>
                <th>Cantidad</th>
                <th>Fecha OT</th>
                <th>Fecha alta</th>
                <th>Status</th>
                <th>Referencia</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>

  </div> <!-- container -->

  <!-- Modal Componentes -->
  <div class="modal fade" id="mdlComponentes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Componentes de OT</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <div id="otInfo" class="mb-3 small">
            <!-- aquí se pinta folio, producto, cantidad -->
          </div>

          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-detalle-ot" data-bs-toggle="tab"
                data-bs-target="#panel-detalle-ot" type="button" role="tab">
                Detalle OT
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-consol" data-bs-toggle="tab" data-bs-target="#panel-consol" type="button"
                role="tab">
                Consolidado (según filtros)
              </button>
            </li>
          </ul>

          <div class="tab-content mt-3">

            <!-- Detalle OT -->
            <div class="tab-pane fade show active" id="panel-detalle-ot" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-bordered" id="tblCompOT">
                  <thead>
                    <tr>
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

            <!-- Consolidado -->
            <div class="tab-pane fade" id="panel-consol" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-sm table-bordered" id="tblCompConsol">
                  <thead>
                    <tr>
                      <th>Componente</th>
                      <th>Descripción</th>
                      <th>UOM</th>
                      <th>Cantidad total requerida</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>

          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    (function () {

      let tbl = null;
      let mdlComponentes = null;

      // Cargar almacenes desde el API general (mismas claves que el importador)
      function cargarAlmacenes() {
        $.ajax({
          url: '../api/filtros_assistpro.php',
          method: 'GET',
          dataType: 'json',
          data: { action: 'init' },
          success: function (r) {
            const $c = $('#fAlmacen');
            $c.empty().append('<option value="">Todos</option>');
            if (!r || !r.ok) return;

            (r.almacenes || []).forEach(function (a) {
              const val = a.cve_almac;
              const text = '(' + (a.clave_almacen || a.cve_almac) + ') ' + (a.des_almac || a.cve_almac);
              $c.append($('<option>').val(val).text(text));
            });
          }
        });
      }

      function initTabla() {
        if (tbl) return;

        tbl = $('#tblOT').DataTable({
          pageLength: 25,
          searching: false,
          info: true,
          autoWidth: false,
          columns: [
            { data: null },            // # (se llenará en draw)
            { data: 'Folio_Pro' },
            { data: 'cve_almac' },
            { data: 'Cve_Articulo' },
            { data: 'Cant_Prod' },
            { data: 'fecha_ot' },
            { data: 'fecha_reg' },
            { data: 'status' },
            { data: 'referencia' },
            {
              data: null,
              orderable: false,
              render: function (data, type, row) {
                return '<button class="btn btn-sm btn-outline-primary btnComponentes">Componentes</button>';
              }
            }
          ],
          order: [[6, 'desc']],
          createdRow: function (row, data) {
            if (data.status === 'P') {
              $(row).addClass('table-warning');
            }
          }
        });

        // Numerar filas
        tbl.on('order.dt search.dt draw.dt', function () {
          let i = 1;
          tbl.rows({ page: 'current' }).every(function () {
            const cell = this.cell(this.index(), 0).node();
            $(cell).text(i++);
          });
        });

        // Click en botón Componentes
        $('#tblOT tbody').on('click', '.btnComponentes', function () {
          const rowData = tbl.row($(this).closest('tr')).data();
          if (!rowData) return;
          abrirModalComponentes(rowData);
        });
      }

      function obtenerFiltros() {
        return {
          almacen: $('#fAlmacen').val() || '',
          status: $('#fStatus').val() || '',
          f_ini: $('#fIni').val() || '',
          f_fin: $('#fFin').val() || '',
          buscar: $('#fBuscar').val() || ''
        };
      }

      function buscar() {
        initTabla();

        const params = Object.assign({ op: 'list_ots' }, obtenerFiltros());

        $.post('orden_produccion_admin.php', params, function (r) {
          if (!r || !r.ok) {
            alert(r && r.msg ? r.msg : 'Error al consultar OTs');
            return;
          }
          tbl.clear().rows.add(r.data || []).draw();
        }, 'json');
      }

      function limpiar() {
        $('#fAlmacen').val('');
        $('#fStatus').val('');
        const hoy = new Date().toISOString().substring(0, 10);
        $('#fIni').val(hoy);
        $('#fFin').val(hoy);
        $('#fBuscar').val('');
      }

      $('#btnBuscar').on('click', buscar);
      $('#btnLimpiar').on('click', function () {
        limpiar();
        buscar();
      });
      $('#fBuscar').on('keypress', function (e) {
        if (e.which === 13) buscar();
      });

      $('#btnExport').on('click', function () {
        alert('Exportar OT pendientes (pendiente de amarrar al reporte real).');
      });

      // Modal componentes
      function abrirModalComponentes(ot) {
        if (!mdlComponentes) {
          mdlComponentes = new bootstrap.Modal(document.getElementById('mdlComponentes'));
        }

        $('#otInfo').html(
          '<b>Folio OT:</b> ' + (ot.Folio_Pro || '') +
          ' &nbsp; | &nbsp; <b>Producto:</b> ' + (ot.Cve_Articulo || '') +
          ' &nbsp; | &nbsp; <b>Cantidad:</b> ' + (ot.Cant_Prod || '') +
          ' &nbsp; | &nbsp; <b>Almacén:</b> ' + (ot.cve_almac || '')
        );

        // Limpia tablas
        $('#tblCompOT tbody').empty();
        $('#tblCompConsol tbody').empty();

        // Detalle OT
        $.post('orden_produccion_admin.php', { op: 'componentes_ot', id_ot: ot.id }, function (r) {
          if (r && r.ok) {
            const $tb = $('#tblCompOT tbody');
            (r.data || []).forEach(function (c) {
              $tb.append(
                '<tr>' +
                '<td>' + (c.cve_componente || '') + '</td>' +
                '<td>' + (c.desc_componente || '') + '</td>' +
                '<td>' + (c.uom || '') + '</td>' +
                '<td class="text-end">' + (c.factor ?? '') + '</td>' +
                '<td class="text-end">' + (c.cant_ot ?? '') + '</td>' +
                '<td class="text-end">' + (c.cant_requerida ?? '') + '</td>' +
                '</tr>'
              );
            });
          } else {
            alert(r && r.msg ? r.msg : 'Error al obtener componentes de la OT');
          }
        }, 'json');

        // Consolidado según filtros actuales
        const filtros = obtenerFiltros();
        filtros.op = 'componentes_consol';

        $.post('orden_produccion_admin.php', filtros, function (r) {
          if (r && r.ok) {
            const $tb = $('#tblCompConsol tbody');
            (r.data || []).forEach(function (c) {
              $tb.append(
                '<tr>' +
                '<td>' + (c.cve_componente || '') + '</td>' +
                '<td>' + (c.desc_componente || '') + '</td>' +
                '<td>' + (c.uom || '') + '</td>' +
                '<td class="text-end">' + (c.cant_total ?? '') + '</td>' +
                '</tr>'
              );
            });
          } else {
            console.error('Error componentes_consol', r ? r.msg : '');
          }
        }, 'json');

        mdlComponentes.show();
      }

      // Fechas por default: hoy
      const hoy = new Date().toISOString().substring(0, 10);
      $('#fIni').val(hoy);
      $('#fFin').val(hoy);

      cargarAlmacenes();
      buscar();

    })();
  </script>

</body>

</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>