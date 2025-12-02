<?php
// public/manufactura/ot_importar.php
//@//@session_start();

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

/* ============================================================
   HELPERS
============================================================ */
function jexit($arr)
{
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
   ENDPOINTS AJAX
============================================================ */
$op = $_POST['op'] ?? $_GET['op'] ?? null;

if ($op) {
  try {

    /* --------- ZONAS DE ALMACENAJE POR ALMACÉN (c_almacen) --------- */
    if ($op === 'get_zonas') {
      $almacenp = (int) ($_POST['almacenp'] ?? 0);
      if (!$almacenp) {
        jexit(['ok' => false, 'msg' => 'Almacén base requerido.']);
      }

      // MISMA lógica que configuracion_almacen.php
      $rows = db_all("
                SELECT DISTINCT
                    CAST(a.cve_almac AS UNSIGNED) AS cve_almac,
                    a.des_almac
                FROM c_almacen a
                JOIN c_ubicacion u
                    ON u.cve_almac = CAST(a.cve_almac AS UNSIGNED)
                WHERE a.cve_almacenp = :id_almacenp
                ORDER BY a.des_almac
            ", [':id_almacenp' => $almacenp]);

      jexit(['ok' => true, 'data' => $rows]);
    }

    /* --------- BLs DE MANUFACTURA POR ZONA (c_ubicacion) --------- */
    if ($op === 'get_bls') {
      $zona = trim($_POST['zona'] ?? '');
      if ($zona === '') {
        jexit(['ok' => false, 'msg' => 'Zona de almacenaje requerida.']);
      }

      $rows = db_all("
                SELECT
                    idy_ubica,
                    CodigoCSD AS bl
                FROM c_ubicacion
                WHERE cve_almac = ?
                  AND IFNULL(AreaProduccion,'N') = 'S'
                  AND IFNULL(Activo,0) = 1
                  AND IFNULL(CodigoCSD,'') <> ''
                ORDER BY CodigoCSD
            ", [$zona]);

      jexit(['ok' => true, 'data' => $rows]);
    }

    /* --------- PREVISUALIZAR CSV (SIN VALIDACIONES COMPLEJAS) --------- */
    if ($op === 'preview') {

      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jexit(['ok' => false, 'msg' => 'Archivo CSV no recibido.']);
      }

      $fh = fopen($_FILES['file']['tmp_name'], 'r');
      if (!$fh) {
        jexit(['ok' => false, 'msg' => 'No se pudo abrir el archivo CSV.']);
      }

      $rows = [];
      $n = 0;

      while (($cols = fgetcsv($fh, 0, ',')) !== false) {
        $n++;
        if ($n === 1) {
          // Encabezado
          continue;
        }
        if (count($cols) < 7) {
          continue;
        }

        $rows[] = [
          'ot_cliente' => trim($cols[0] ?? ''),
          'articulo' => trim($cols[1] ?? ''),
          'lote' => trim($cols[2] ?? ''),
          'caducidad' => trim($cols[3] ?? ''),
          'cantidad' => trim($cols[4] ?? ''),
          'fecha_comp' => trim($cols[5] ?? ''),
          'lp' => trim($cols[6] ?? ''),
          'status' => 'OK',
          'mensaje' => '',
        ];
      }
      fclose($fh);

      jexit([
        'ok' => true,
        'data' => $rows,
      ]);
    }

    /* --------- IMPORTAR CSV -> t_ordenprod --------- */
    if ($op === 'import') {

      $almacenp = trim($_POST['almacenp'] ?? '');
      $zona = trim($_POST['zona'] ?? '');
      $idyUbica = (int) ($_POST['idy_ubica'] ?? 0);

      if ($almacenp === '' || $zona === '' || !$idyUbica) {
        jexit([
          'ok' => false,
          'msg' => 'Selecciona Almacén, Zona y BL antes de importar.',
        ]);
      }

      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jexit(['ok' => false, 'msg' => 'Archivo CSV no recibido.']);
      }

      $fh = fopen($_FILES['file']['tmp_name'], 'r');
      if (!$fh) {
        jexit(['ok' => false, 'msg' => 'No se pudo abrir el archivo CSV.']);
      }

      $now = date('Y-m-d H:i:s');
      $user = $_SESSION['usuario'] ?? 'import';
      $totalOT = 0;
      $errores = [];

      $line = 0;
      while (($cols = fgetcsv($fh, 0, ',')) !== false) {
        $line++;
        if ($line === 1) { // encabezado
          continue;
        }
        if (count($cols) < 7) {
          $errores[] = "L{$line}: renglón incompleto.";
          continue;
        }

        $otCli = trim($cols[0] ?? '');
        $art = trim($cols[1] ?? '');
        $lote = trim($cols[2] ?? '');
        $caducidad = trim($cols[3] ?? '');
        $cantRaw = trim($cols[4] ?? '');
        $fecComp = trim($cols[5] ?? '');
        $lp = trim($cols[6] ?? '');

        // Cantidad numérica
        $cantRaw = str_replace([' ', ','], ['', '.'], $cantRaw);
        $cant = is_numeric($cantRaw) ? (float) $cantRaw : null;
        if ($cant === null || $cant <= 0) {
          $errores[] = "L{$line}: Cantidad a producir inválida.";
          continue;
        }

        // Folio_Pro = OT cliente (como habíamos acordado)
        $folioPro = $otCli !== '' ? $otCli : ('OP' . date('YmdHis') . sprintf('%03d', $line));

        // Fecha OT (si viene fecha compromiso la usamos, si no hoy)
        $fechaOT = date('Y-m-d');
        if ($fecComp !== '') {
          $fecCompNorm = str_replace('/', '-', $fecComp);
          $ts = strtotime($fecCompNorm);
          if ($ts !== false) {
            $fechaOT = date('Y-m-d', $ts);
          }
        }

        // Insert directo a t_ordenprod (SIN td_ordenprod, SIN folios extras)
        dbq("
                    INSERT INTO t_ordenprod
                        (Folio_Pro,
                         cve_almac,
                         Cve_Articulo,
                         Cve_Lote,
                         Cantidad,
                         Cant_Prod,
                         Cve_Usuario,
                         Fecha,
                         FechaReg,
                         Status,
                         Referencia,
                         id_zona_almac,
                         idy_ubica,
                         idy_ubica_dest)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ", [
          $folioPro,
          $zona,
          $art,
          $lote,
          $cant,
          $cant,
          $user,
          $fechaOT,
          $now,
          'P',       // Pendiente
          $otCli,    // referencia = OT Cliente
          $zona,
          $idyUbica,
          $idyUbica,
        ]);

        $totalOT++;
      }

      fclose($fh);

      jexit([
        'ok' => true,
        'msg' => "{$totalOT} OT(s) importadas.",
        'total' => $totalOT,
        'errores' => $errores,
      ]);
    }

    // OP no soportada
    jexit(['ok' => false, 'msg' => 'Operación no soportada.']);

  } catch (Throwable $e) {
    jexit(['ok' => false, 'msg' => 'Error general: ' . $e->getMessage()]);
  }
}

/* ============================================================
   VISTA HTML
============================================================ */

// ALMACENES (MISMA LÓGICA QUE CONFIGURACION_ALMACEN.PHP)
$almacenesP = [];
try {
  $almacenesP = db_all("
        SELECT DISTINCT
            ap.id   AS id_almacenp,
            ap.clave,
            ap.nombre
        FROM c_almacenp ap
        JOIN c_almacen a
            ON a.cve_almacenp = ap.id
        JOIN c_ubicacion u
            ON u.cve_almac = CAST(a.cve_almac AS UNSIGNED)
        ORDER BY ap.nombre
    ");
} catch (Throwable $e) {
  $almacenesP = [];
}

$TITLE = 'Importador Masivo de Órdenes de Producción';
include __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Importador Masivo de Órdenes de Producción</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-size: 10px;
    }

    .table-sm th,
    .table-sm td {
      padding: .25rem .5rem;
    }

    .mini {
      font-size: 9px;
      color: #6c757d;
    }
  </style>
</head>

<body>
  <div class="container-fluid mt-2">

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="bi bi-upload"></i> Importador Masivo de Órdenes de Producción</h5>
        <span class="mini">Explosión de materiales / Manufactura</span>
      </div>
      <div class="card-body">

        <!-- FILTROS -->
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <label class="form-label">Almacén (c_almacenp / AP)</label>
            <select id="cmbAlmacenP" class="form-select form-select-sm">
              <option value="">Seleccione almacén</option>
              <?php foreach ($almacenesP as $a): ?>
                <option value="<?php echo htmlspecialchars($a['id_almacenp']); ?>">
                  (<?php echo htmlspecialchars($a['clave']); ?>) <?php echo htmlspecialchars($a['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Zona de Almacenaje (c_almacen)</label>
            <select id="cmbZona" class="form-select form-select-sm" disabled>
              <option value="">Seleccione zona de almacén</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">BL Manufactura</label>
            <select id="cmbBL" class="form-select form-select-sm" disabled>
              <option value="">Seleccione BL de Manufactura</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Archivo CSV</label>
            <input type="file" id="fileCsv" class="form-control form-control-sm" accept=".csv">
          </div>
        </div>

        <div class="mb-2">
          <button id="btnPreview" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search"></i> Previsualizar
          </button>
          <button id="btnImport" class="btn btn-primary btn-sm">
            <i class="bi bi-cloud-upload"></i> Importar OT
          </button>
        </div>

        <div class="table-responsive">
          <table id="tblPreview" class="table table-sm table-striped table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>OT Cliente</th>
                <th>Artículo Comp</th>
                <th>Lote</th>
                <th>Caducidad</th>
                <th>Cantidad</th>
                <th>Fecha Compromiso</th>
                <th>LP</th>
                <th>Status</th>
                <th>Mensaje</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>

  </div>

  <!-- Modal resultado -->
  <div class="modal fade" id="resModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Resultado de importación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <pre id="resText" style="font-size:10px;"></pre>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div id="appToastBody" class="toast-body">...</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
          aria-label="Cerrar"></button>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script>
    (function () {
      'use strict';

      let dtPrev = null;
      let toastObj = null;

      function toast(msg, type = 'success') {
        const el = document.getElementById('appToast');
        const body = document.getElementById('appToastBody');
        body.textContent = msg;
        el.classList.remove('text-bg-success', 'text-bg-info', 'text-bg-warning', 'text-bg-danger');
        el.classList.add(
          type === 'success' ? 'text-bg-success' :
            type === 'info' ? 'text-bg-info' :
              type === 'warning' ? 'text-bg-warning' : 'text-bg-danger'
        );
        if (!toastObj) {
          toastObj = new bootstrap.Toast(el, { delay: 3500 });
        }
        toastObj.show();
      }

      // ---- combos dependientes ----
      $('#cmbAlmacenP').on('change', function () {
        const alm = $(this).val();
        $('#cmbZona').prop('disabled', true).empty()
          .append('<option value="">Seleccione zona de almacén</option>');
        $('#cmbBL').prop('disabled', true).empty()
          .append('<option value="">Seleccione BL de Manufactura</option>');

        if (!alm) return;

        $.post('ot_importar.php', { op: 'get_zonas', almacenp: alm }, function (r) {
          if (!r.ok) {
            toast(r.msg || 'Error cargando zonas', 'danger');
            return;
          }
          const $z = $('#cmbZona');
          (r.data || []).forEach(row => {
            $z.append(
              $('<option>', {
                value: row.cve_almac,
                text: '(' + row.cve_almac + ') ' + row.des_almac
              })
            );
          });
          $z.prop('disabled', false);
        }, 'json');
      });

      $('#cmbZona').on('change', function () {
        const zona = $(this).val();
        $('#cmbBL').prop('disabled', true).empty()
          .append('<option value="">Seleccione BL de Manufactura</option>');
        if (!zona) return;

        $.post('ot_importar.php', { op: 'get_bls', zona: zona }, function (r) {
          if (!r.ok) {
            toast(r.msg || 'Error cargando BLs', 'danger');
            return;
          }
          const $b = $('#cmbBL');
          (r.data || []).forEach(row => {
            $b.append(
              $('<option>', {
                value: row.idy_ubica,
                text: row.bl
              })
            );
          });
          $b.prop('disabled', false);
        }, 'json');
      });

      // ---- PREVIEW ----
      $('#btnPreview').on('click', function () {
        const f = $('#fileCsv')[0].files[0];
        if (!f) {
          toast('Selecciona un archivo CSV para previsualizar.', 'warning');
          return;
        }
        const fd = new FormData();
        fd.append('op', 'preview');
        fd.append('file', f);

        $.ajax({
          url: 'ot_importar.php',
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function (r) {
            if (!r.ok) {
              toast(r.msg || 'Error en previsualización', 'danger');
              return;
            }
            const rows = r.data || [];

            if (dtPrev) {
              dtPrev.clear().destroy();
            }

            dtPrev = $('#tblPreview').DataTable({
              data: rows,
              paging: true,
              searching: false,
              info: true,
              lengthChange: false,
              pageLength: 25,
              order: [],
              columns: [
                { data: null, render: (d, t, r, meta) => meta.row + 1 },
                { data: 'ot_cliente' },
                { data: 'articulo' },
                { data: 'lote' },
                { data: 'caducidad' },
                { data: 'cantidad' },
                { data: 'fecha_comp' },
                { data: 'lp' },
                { data: 'status' },
                { data: 'mensaje' }
              ]
            });
          },
          error: function () {
            toast('Error de comunicación (preview).', 'danger');
          }
        });
      });

      // ---- IMPORTAR ----
      $('#btnImport').on('click', function () {
        const almP = $('#cmbAlmacenP').val();
        const zona = $('#cmbZona').val();
        const idyUbi = $('#cmbBL').val();
        const f = $('#fileCsv')[0].files[0];

        if (!almP || !zona || !idyUbi) {
          toast('Selecciona Almacén, Zona y BL antes de importar.', 'warning');
          return;
        }
        if (!f) {
          toast('Selecciona un archivo CSV para importar.', 'warning');
          return;
        }

        const fd = new FormData();
        fd.append('op', 'import');
        fd.append('almacenp', almP);
        fd.append('zona', zona);
        fd.append('idy_ubica', idyUbi);
        fd.append('file', f);

        $.ajax({
          url: 'ot_importar.php',
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function (r) {
            if (!r.ok) {
              toast(r.msg || 'Error en importación', 'danger');
              return;
            }

            let txt = '';
            txt += (r.msg || '') + "\n";
            if (r.errores && r.errores.length) {
              txt += "\nErrores:\n" + r.errores.join("\n");
            }

            $('#resText').text(txt);
            new bootstrap.Modal(document.getElementById('resModal')).show();
          },
          error: function () {
            toast('Error de comunicación (import).', 'danger');
          }
        });
      });

    })();
  </script>
</body>

</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>