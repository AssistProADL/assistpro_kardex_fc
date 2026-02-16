<?php
// public/config_almacen/license_plate.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

// Combos
$sql_alm = "
    SELECT TRIM(id) AS id_almacen, TRIM(clave) AS clave, TRIM(nombre) AS nombre
    FROM c_almacenp
    WHERE (Activo IS NULL OR TRIM(Activo) <> '0')
    ORDER BY TRIM(clave)
";
$almacenes_list = db_all($sql_alm);

// Default: primer almacén disponible
$default_almacen = '';
if (!empty($almacenes_list)) {
  $default_almacen = (string) $almacenes_list[0]['id_almacen'];
}

// Si viene por GET, respeta; si no, usa default
$almacen_sel = trim((string) ($_GET['almacen'] ?? ''));
if ($almacen_sel === '' && $default_almacen !== '') {
  $almacen_sel = $default_almacen;
}

// Zonas (catálogo general; si quieres dependiente, lo hacemos después)
$sql_zonas = "
    SELECT DISTINCT ca.des_almac AS zona
    FROM c_almacen ca
    WHERE ca.des_almac IS NOT NULL AND ca.des_almac <> ''
    ORDER BY ca.des_almac
";
$zonas_list = db_all($sql_zonas);

$sql_tipos = "
    SELECT DISTINCT ch.tipo
    FROM c_charolas ch
    WHERE COALESCE(ch.CveLP,'') <> ''
    ORDER BY ch.tipo
";
$tipos_list = db_all($sql_tipos);

?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <title>License Plate - Charolas</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    body {
      font-size: 10px;
    }

    .table-sm td,
    .table-sm th {
      padding: .25rem;
    }

    .kpi-card {
      font-size: 10px;
      border-radius: 10px;
      border: 1px solid #e0e5f5;
    }

    .kpi-card .card-body {
      padding: .5rem .75rem;
    }

    .kpi-title {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #6c7aa0;
    }

    .kpi-value {
      font-size: 16px;
      font-weight: 700;
    }

    .kpi-sub {
      font-size: 9px;
      color: #6c7aa0;
    }

    .kpi-total {
      background: #f2f6ff;
    }

    .kpi-perm {
      background: #f5f8ff;
    }

    .kpi-temp {
      background: #f5f8ff;
    }

    .kpi-act {
      background: #f5fdf8;
    }

    .kpi-inac {
      background: #fff5f5;
    }

    .badge-perm {
      background: #0d6efd;
    }

    .badge-temp {
      background: #6c757d;
    }

    .badge-activo {
      background: #198754;
    }

    .badge-inactivo {
      background: #dc3545;
    }

    .table thead th {
      white-space: nowrap;
    }

    .sel-info {
      font-size: 10px;
    }
  </style>
</head>

<body>
  <div class="container-fluid mt-3">

    <!-- KPIs -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card kpi-card kpi-total">
          <div class="card-body">
            <div class="kpi-title">License Plates filtrados</div>
            <div class="kpi-value" id="kpi_total">0</div>
            <div class="kpi-sub">En el almacén seleccionado</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-3 col-lg-2">
        <div class="card kpi-card kpi-perm">
          <div class="card-body">
            <div class="kpi-title">LP Permanentes</div>
            <div class="kpi-value text-primary" id="kpi_perm">0</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-3 col-lg-2">
        <div class="card kpi-card kpi-temp">
          <div class="card-body">
            <div class="kpi-title">LP Temporales</div>
            <div class="kpi-value text-secondary" id="kpi_temp">0</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-3 col-lg-2">
        <div class="card kpi-card kpi-act">
          <div class="card-body">
            <div class="kpi-title">LP Activos</div>
            <div class="kpi-value text-success" id="kpi_act">0</div>
            <div class="kpi-sub">exist&gt;0</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-3 col-lg-2">
        <div class="card kpi-card kpi-inac">
          <div class="card-body">
            <div class="kpi-title">LP Inactivos</div>
            <div class="kpi-value text-danger" id="kpi_inac">0</div>
            <div class="kpi-sub">exist&lt;=0</div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form id="frmFiltros" class="row g-2 align-items-end">

          <div class="col-12 col-md-3">
            <label class="form-label mb-1">License Plate (CveLP)</label>
            <input type="text" class="form-control form-control-sm" name="lp" id="f_lp" placeholder="LP parcial">
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label mb-1">Almacén</label>
            <select class="form-select form-select-sm" name="almacen" id="f_almacen">
              <?php foreach ($almacenes_list as $a):
                $id = trim((string) $a['id_almacen']);
                $lab = trim($a['clave'] . ' - ' . $a['nombre']);
                $sel = ($almacen_sel !== '' && $almacen_sel === $id) ? 'selected' : '';
              ?>
                <option value="<?= htmlspecialchars($id) ?>" <?= $sel ?>><?= htmlspecialchars($lab) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Zona de almacenaje</label>
            <select class="form-select form-select-sm" name="zona" id="f_zona">
              <option value="">Todas</option>
              <?php foreach ($zonas_list as $z): ?>
                <option value="<?= htmlspecialchars($z['zona']) ?>"><?= htmlspecialchars($z['zona']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Tipo (Genérico/No)</label>
            <select class="form-select form-select-sm" name="tipogen" id="f_tipogen">
              <option value="">Todos</option>
              <option value="G">Genérico</option>
              <option value="N">No genérico</option>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Tipo (Pallet/Contenedor)</label>
            <select class="form-select form-select-sm" name="tipo" id="f_tipo">
              <option value="">Todos los tipos</option>
              <?php foreach ($tipos_list as $t): ?>
                <option value="<?= htmlspecialchars($t['tipo']) ?>"><?= htmlspecialchars($t['tipo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">License Plate Status</label>
            <select class="form-select form-select-sm" name="statuslp" id="f_statuslp">
              <option value="">Todos</option>
              <option value="1">Permanente</option>
              <option value="0">Temporal</option>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Activo / Inactivo</label>
            <select class="form-select form-select-sm" name="activo" id="f_activo">
              <option value="">Todos</option>
              <option value="1">Activo (exist&gt;0)</option>
              <option value="0">Inactivo</option>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <button type="button" id="btnBuscar" class="btn btn-primary btn-sm">
              <i class="fa fa-search"></i> Buscar
            </button>
            <button type="button" id="btnLimpiar" class="btn btn-outline-secondary btn-sm">
              Limpiar
            </button>
            <a href="license_plate_new.php" class="btn btn-outline-primary btn-sm">
              <i class="fa fa-plus"></i> Nuevo LP
            </a>
          </div>

          <div class="col-12">
            <div class="alert alert-warning py-2 mb-0 d-none" id="msgWarn" style="font-size:10px;"></div>
            <div class="alert alert-danger py-2 mb-0 d-none" id="msgErr" style="font-size:10px;"></div>
          </div>

        </form>
      </div>
    </div>

    <!-- GRILLA -->
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>License Plate (c_charolas) - Server-Side</span>
        <span class="small sel-info">Seleccionados: <span id="sel_count">0</span></span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tblLicensePlate" class="table table-striped table-bordered table-sm align-middle w-100">
            <thead>
              <tr>
                <th style="width:70px;">Acciones</th>
                <th>Almacén</th>
                <th>Zona Almacenaje</th>
                <th>BL (CodigoCSD)</th>
                <th>Descripción</th>
                <th>LP</th>
                <th>Tipo</th>
                <th>Contenedor</th>
                <th>Permanente</th>
                <th>Utilizado</th>
                <th>Status</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>

  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

  <script>
    $(function() {

      function filtros() {
        return {
          lp: $('#f_lp').val(),
          almacen: $('#f_almacen').val(), // ya viene seleccionado por default
          zona: $('#f_zona').val(),
          tipogen: $('#f_tipogen').val(),
          tipo: $('#f_tipo').val(),
          statuslp: $('#f_statuslp').val(),
          activo: $('#f_activo').val()
        };
      }

      function warn(msg) {
        if (!msg) {
          $('#msgWarn').addClass('d-none').text('');
          return;
        }
        $('#msgWarn').removeClass('d-none').text(msg);
      }

      function err(msg) {
        if (!msg) {
          $('#msgErr').addClass('d-none').text('');
          return;
        }
        $('#msgErr').removeClass('d-none').text(msg);
      }

      const table = $('#tblLicensePlate').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [
          [25, 50, 100],
          [25, 50, 100]
        ],
        scrollX: true,
        scrollY: '420px',
        scrollCollapse: true,
        order: [
          [5, 'asc']
        ],
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
        },
        ajax: {
          url: 'license_plate_api.php',
          data: function(d) {
            return $.extend(d, filtros());
          },
          dataSrc: function(json) {
            err('');
            warn(json.warning || '');
            if (json.error) {
              err('API error: ' + json.error);
              return [];
            }
            $('#kpi_total').text((json.recordsFiltered ?? 0).toLocaleString());
            return json.data || [];
          },
          error: function(xhr) {
            err('No se pudo consumir el API. HTTP ' + xhr.status + ' :: ' + (xhr.responseText || '').substring(0, 200));
          }
        },
        columns: [{
            data: null,
            orderable: false,
            searchable: false,
            render: function() {
              return `<span class="text-muted">—</span>`;
            }
          },
          {
            data: 'almacen'
          },
          {
            data: 'zona'
          },
          {
            data: 'bl'
          },
          {
            data: 'descripcion'
          },
          {
            data: 'lp'
          },
          {
            data: 'tipo'
          },
          {
            data: 'contenedor'
          },
          {
            data: 'permanente',
            render: function(v) {
              return (parseInt(v, 10) === 1) ?
                '<span class="badge badge-perm">Permanente</span>' :
                '<span class="badge badge-temp">Temporal</span>';
            }
          },

          // 🔵 NUEVA COLUMNA UTILIZADO
          {
            data: null,
            render: function(v, type, row) {
              const exist = parseFloat(row.existencia_total || 0);
              return (exist > 0) ?
                '<span class="badge bg-success">Sí</span>' :
                '<span class="badge bg-secondary">No</span>';
            }
          },

          // 🔴 STATUS (ajustado)
          {
            data: null,
            render: function(v, type, row) {

              const activo_db = parseInt(row.activo_flag || 0, 10); // nuevo campo del API
              const exist = parseFloat(row.existencia_total || 0);

              return (activo_db === 1 && exist > 0) ?
                '<span class="badge badge-activo">Activo</span>' :
                '<span class="badge badge-inactivo">Inactivo</span>';
            }
          }
        ]

      });

      $('#btnBuscar').on('click', function() {
        table.ajax.reload();
      });
      $('#btnLimpiar').on('click', function() {
        $('#frmFiltros')[0].reset();
        // re-selecciona el primer almacén (default)
        const first = $('#f_almacen option:first').val();
        if (first) $('#f_almacen').val(first);
        table.ajax.reload();
      });

      // CARGA INICIAL AUTOMÁTICA: ya existe almacén seleccionado por default
      table.ajax.reload();

    });
  </script>

  <?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>

</html>