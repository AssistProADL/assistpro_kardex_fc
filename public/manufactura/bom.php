<?php
//@//@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* --------------------------
   Helpers locales
---------------------------*/
function table_exists($table)
{
  return (int) db_val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]) > 0;
}

/* --------------------------
   API AJAX
---------------------------*/
$op = $_POST['op'] ?? $_GET['op'] ?? null;
if ($op) {

  // Descargar layout CSV (A=producto_compuesto, B=componente, C=cantidad)
  if ($op === 'download_layout') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="layout_bom.csv"');
    echo "producto_compuesto,componente,cantidad\n";
    echo "BUL-0001,AG1-CH-105,1.0000\n";
    echo "BUL-0001,AG1-CH-108,0.5000\n";
    echo "KIT-XYZ,MP000001,2.0000\n";
    exit;
  }

  header('Content-Type: application/json; charset=utf-8');

  try {
    // KPI: total compuestos
    if ($op === 'kpi_compuestos') {
      $n = (int) db_val("SELECT COUNT(*) FROM c_articulo WHERE COALESCE(Compuesto,'N')='S'");
      echo json_encode(['ok' => true, 'total' => $n]);
      exit;
    }
    // KPI: total artículos
    if ($op === 'kpi_total_articulos') {
      $n = (int) db_val("SELECT COUNT(*) FROM c_articulo");
      echo json_encode(['ok' => true, 'total' => $n]);
      exit;
    }

    // Buscar producto compuesto (clave/descr)
    if ($op === 'buscar_compuestos') {
      $q = trim($_POST['q'] ?? $_GET['q'] ?? '');
      $p = [];
      $sql = "
        SELECT cve_articulo, des_articulo, unidadMedida
        FROM c_articulo
        WHERE COALESCE(Compuesto,'N')='S'
      ";
      if ($q !== '') {
        $sql .= " AND (cve_articulo LIKE ? OR des_articulo LIKE ?) ";
        $p[] = "%$q%";
        $p[] = "%$q%";
      }
      $sql .= " ORDER BY des_articulo LIMIT 20";
      $rows = db_all($sql, $p);
      echo json_encode(['ok' => true, 'data' => $rows]);
      exit;
    }

    // Encabezado de producto compuesto
    if ($op === 'get_producto') {
      $padre = trim($_POST['padre'] ?? $_GET['padre'] ?? '');
      if ($padre === '')
        throw new Exception('Artículo requerido');
      $r = db_row("SELECT cve_articulo, des_articulo, unidadMedida FROM c_articulo WHERE cve_articulo = ?", [$padre]);
      if (!$r)
        throw new Exception('Artículo no encontrado');
      echo json_encode(['ok' => true, 'producto' => $r]);
      exit;
    }

    // Componentes del producto compuesto
    if ($op === 'get_componentes') {
      $padre = trim($_POST['padre'] ?? $_GET['padre'] ?? '');
      if ($padre === '')
        throw new Exception('Artículo compuesto requerido');
      $sql = "
        SELECT
          c.Cve_Articulo AS componente,
          a.des_articulo AS descripcion,
          c.Cantidad AS cantidad,
          COALESCE(a.unidadMedida, c.cve_umed) AS unidad,
          COALESCE(c.Etapa,'') AS etapa
        FROM t_artcompuesto c
        LEFT JOIN c_articulo a ON a.cve_articulo = c.Cve_Articulo
        WHERE c.Cve_Articulo = ?
        ORDER BY c.Cve_Articulo
      ";
      $rows = db_all($sql, [$padre]);
      echo json_encode(['ok' => true, 'data' => $rows]);
      exit;
    }

    /* ---------- EXPORTACIONES ---------- */

    // Exportar CSV de componentes por padre
    if ($op === 'export_csv') {
      $padre = trim($_GET['padre'] ?? '');
      if ($padre === '')
        throw new Exception('Artículo compuesto requerido');

      $rows = db_all("
        SELECT
          c.Cve_Articulo AS componente,
          a.des_articulo AS descripcion,
          c.Cantidad AS cantidad,
          COALESCE(a.unidadMedida, c.cve_umed) AS unidad,
          COALESCE(c.Etapa,'') AS etapa
        FROM t_artcompuesto c
        LEFT JOIN c_articulo a ON a.cve_articulo = c.Cve_Articulo
        WHERE c.Cve_Articulo = ?
        ORDER BY c.Cve_Articulo
      ", [$padre]);

      $producto = db_row("SELECT cve_articulo, des_articulo, unidadMedida FROM c_articulo WHERE cve_articulo = ?", [$padre]);

      $filename = "BOM_{$padre}_" . date('Ymd_His') . ".csv";
      header('Content-Type: text/csv; charset=utf-8');
      header("Content-Disposition: attachment; filename=\"$filename\"");
      $out = fopen('php://output', 'w');
      fputcsv($out, ['Producto compuesto', $padre]);
      fputcsv($out, ['Descripción', $producto['des_articulo'] ?? '']);
      fputcsv($out, ['UMed', $producto['unidadMedida'] ?? '']);
      fputcsv($out, ['Generado', date('Y-m-d H:i:s')]);
      fputcsv($out, []); // línea vacía
      fputcsv($out, ['Componente', 'Descripción', 'Cantidad', 'UMed', 'Etapa']);
      foreach ($rows as $r) {
        fputcsv($out, [$r['componente'], $r['descripcion'], $r['cantidad'], $r['unidad'], $r['etapa']]);
      }
      fclose($out);
      exit;
    }

    // Exportar PDF/HTML de componentes por padre (con logo y sello de tiempo)
    if ($op === 'export_pdf') {
      $padre = trim($_GET['padre'] ?? '');
      if ($padre === '')
        throw new Exception('Artículo compuesto requerido');

      $producto = db_row("SELECT cve_articulo, des_articulo, unidadMedida FROM c_articulo WHERE cve_articulo = ?", [$padre]);
      $rows = db_all("
        SELECT
          c.Cve_Articulo AS componente,
          a.des_articulo AS descripcion,
          c.Cantidad AS cantidad,
          COALESCE(a.unidadMedida, c.cve_umed) AS unidad,
          COALESCE(c.Etapa,'') AS etapa
        FROM t_artcompuesto c
        LEFT JOIN c_articulo a ON a.cve_articulo = c.Cve_Articulo
        WHERE c.Cve_Articulo = ?
        ORDER BY c.Cve_Articulo
      ", [$padre]);

      $logoPath = __DIR__ . '/../../public/assets/logo.png'; // ajusta si tu logo está en otra ruta
      $logoUrl = file_exists($logoPath) ? (dirname($_SERVER['SCRIPT_NAME']) . '/assets/logo.png') : '';

      ob_start(); ?>
      <html>

      <head>
        <meta charset="utf-8">
        <style>
          body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #222;
          }

          .hdr {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
          }

          .hdr img {
            height: 36px
          }

          h2 {
            margin: 0 0 4px 0;
            font-size: 16px
          }

          .muted {
            color: #666;
            font-size: 11px
          }

          table {
            border-collapse: collapse;
            width: 100%
          }

          th,
          td {
            border: 1px solid #ddd;
            padding: 6px 8px
          }

          th {
            background: #f2f6fb
          }

          .right {
            text-align: right
          }
        </style>
      </head>

      <body>
        <div class="hdr">
          <?php if ($logoUrl): ?><img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="logo"><?php endif; ?>
          <div>
            <h2>BOM — <?php echo htmlspecialchars($padre); ?></h2>
            <div class="muted"><?php echo htmlspecialchars($producto['des_articulo'] ?? ''); ?> | UMed:
              <?php echo htmlspecialchars($producto['unidadMedida'] ?? ''); ?>
            </div>
            <div class="muted">Generado: <?php echo date('Y-m-d H:i:s'); ?></div>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th style="width:22%">Componente</th>
              <th>Descripción</th>
              <th style="width:12%" class="right">Cantidad</th>
              <th style="width:10%">UMed</th>
              <th style="width:12%">Etapa</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['componente']); ?></td>
                <td><?php echo htmlspecialchars($r['descripcion']); ?></td>
                <td class="right"><?php echo htmlspecialchars($r['cantidad']); ?></td>
                <td><?php echo htmlspecialchars($r['unidad']); ?></td>
                <td><?php echo htmlspecialchars($r['etapa']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </body>

      </html>
      <?php
      $html = ob_get_clean();

      $dompdf_ok = false;
      $vendor = __DIR__ . '/../../vendor/autoload.php';
      if (file_exists($vendor)) {
        require_once $vendor;
        if (class_exists('\Dompdf\Dompdf')) {
          $dompdf_ok = true;
          $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
          $dompdf->loadHtml($html, 'UTF-8');
          $dompdf->setPaper('letter', 'portrait');
          $dompdf->render();
          $filename = "BOM_{$padre}_" . date('Ymd_His') . ".pdf";
          $dompdf->stream($filename, ['Attachment' => true]);
          exit;
        }
      }
      header('Content-Type: text/html; charset=utf-8');
      echo $html;
      exit;
    }

    /* ---------- IMPORTADOR (validación total + replace por padre + N×N) ---------- */

    if ($op === 'import_bom') {
      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Archivo no recibido.');
      }

      // 1) Leer CSV y validar forma
      $fh = fopen($_FILES['file']['tmp_name'], 'r');
      if (!$fh)
        throw new Exception('No fue posible leer el archivo.');

      $peek = fgetcsv($fh);
      if ($peek === false)
        throw new Exception('Archivo vacío.');
      $isHeader = false;
      if (count($peek) >= 2) {
        $a0 = mb_strtolower(trim($peek[0] ?? ''));
        $a1 = mb_strtolower(trim($peek[1] ?? ''));
        if (!preg_match('/[0-9]/', $a0) && !preg_match('/[0-9]/', $a1))
          $isHeader = true;
      }
      if (!$isHeader) {
        rewind($fh);
      }

      $rowsByParent = []; // padre => [ [hijo, cantidad], ... ]
      $parentsSet = [];
      $componentsSet = [];
      $errors = [];
      $line = 0;

      while (($row = fgetcsv($fh)) !== false) {
        $line++;
        $padre = trim($row[0] ?? '');
        $hijo = trim($row[1] ?? '');
        $cantRaw = trim($row[2] ?? '');

        if ($padre === '' || $hijo === '') {
          $errors[] = "L{$line}: producto compuesto / componente vacío";
          continue;
        }

        $cantRaw = str_replace([' ', ','], ['', '.'], $cantRaw);
        $cantidad = is_numeric($cantRaw) ? (float) $cantRaw : null;
        if ($cantidad === null) {
          $errors[] = "L{$line}: cantidad inválida";
          continue;
        }

        $rowsByParent[$padre] = $rowsByParent[$padre] ?? [];
        $rowsByParent[$padre][] = [$hijo, $cantidad];

        $parentsSet[$padre] = true;
        $componentsSet[$hijo] = true;
      }
      fclose($fh);

      if (empty($rowsByParent)) {
        echo json_encode(['ok' => false, 'msg' => 'No hay filas válidas para importar.', 'errors' => $errors]);
        exit;
      }

      // 2) PRE-VALIDACIÓN en BD: existencia de padres y componentes
      $todos = array_values(array_unique(array_merge(array_keys($parentsSet), array_keys($componentsSet))));
      $existentes = [];
      if (!empty($todos)) {
        $place = implode(',', array_fill(0, count($todos), '?'));
        $rows = db_all("SELECT cve_articulo FROM c_articulo WHERE cve_articulo IN ($place)", $todos);
        foreach ($rows as $r)
          $existentes[$r['cve_articulo']] = true;
      }

      foreach (array_keys($parentsSet) as $p) {
        if (!isset($existentes[$p]))
          $errors[] = "Padre no existe en c_articulo: {$p}";
      }
      foreach (array_keys($componentsSet) as $c) {
        if (!isset($existentes[$c]))
          $errors[] = "Componente no existe en c_articulo: {$c}";
      }

      if (!empty($errors)) {
        echo json_encode([
          'ok' => false,
          'msg' => 'Validación fallida. Corrige los errores y vuelve a importar.',
          'errors' => $errors,
          'replaced_parents' => 0,
          'inserted_stg' => 0,
          'inserted_prod' => 0,
          'synced_prod' => table_exists('t_artcompuesto'),
          'parents_detail' => []
        ]);
        exit;
      }

      // 3) Transacción: borrar + reinsertar (REPLACE total por cada padre)
      $pdo = db_pdo();
      $pdo->beginTransaction();
      try {
        $has_prod = table_exists('t_artcompuesto');
        $inserted_stg = 0;
        $inserted_prod = 0;
        $parents_detail = []; // [ ['padre'=>..., 'lineas'=>N], ... ]

        foreach ($rowsByParent as $padre => $items) {
          dbq("DELETE FROM t_artcompuesto WHERE Cve_Articulo=?", [$padre]);
          if ($has_prod)
            dbq("DELETE FROM t_artcompuesto WHERE Cve_Articulo=?", [$padre]);

          $countLines = 0;
          foreach ($items as [$hijo, $cantidad]) {
            $umed = db_val("SELECT unidadMedida FROM c_articulo WHERE cve_articulo = ?", [$hijo]);

            dbq("INSERT INTO t_artcompuesto
                   (Cve_Articulo, Cve_ArtComponente, Cantidad, cve_umed, Activo)
                 VALUES (?,?,?,?, '1')",
              [$padre, $hijo, $cantidad, $umed]
            );
            $inserted_stg++;
            $countLines++;

            if ($has_prod) {
              dbq("INSERT INTO t_artcompuesto
                     (Cve_Articulo, Cve_ArtComponente, Cantidad, cve_umed, Activo)
                   VALUES (?,?,?,?, '1')",
                [$padre, $hijo, $cantidad, $umed]
              );
              $inserted_prod++;
            }
          }
          $parents_detail[] = ['padre' => $padre, 'lineas' => $countLines];
        }

        $pdo->commit();
        echo json_encode([
          'ok' => true,
          'replaced_parents' => count($rowsByParent),
          'inserted_stg' => $inserted_stg,
          'inserted_prod' => $inserted_prod,
          'errors' => [],
          'synced_prod' => $has_prod,
          'parents_detail' => $parents_detail
        ]);
        exit;

      } catch (Throwable $e) {
        if ($pdo->inTransaction())
          $pdo->rollBack();
        throw $e;
      }
    }

    echo json_encode(['ok' => false, 'msg' => 'Operación no válida']);
    exit;

  } catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    exit;
  }
}
?>
<?php include __DIR__ . '/../bi/_menu_global.php'; ?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Análisis de Artículos Compuestos Kitting</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-size: 10px;
    }

    .table-sm td,
    .table-sm th {
      padding: .3rem .4rem;
      vertical-align: middle;
    }

    .dt-right {
      text-align: right;
    }

    .dt-center {
      text-align: center;
    }

    .kpi-card {
      background: #f8fbff;
      border: 1px solid #e6eef8;
      border-radius: .75rem;
    }

    .kpi-title {
      font-size: 10px;
      color: #6c757d;
      margin-bottom: 2px;
    }

    .kpi-value {
      font-size: 22px;
      font-weight: 700;
      color: #0F5AAD;
      line-height: 1;
    }

    .muted {
      font-size: 9px;
      color: #6c757d;
    }

    .mini {
      font-size: 9px;
    }

    .prewrap {
      white-space: pre-wrap;
    }
  </style>
</head>

<body>
  <div class="container-fluid mt-2">

    <!-- KPIs -->
    <div class="row g-2 mb-2">
      <div class="col-sm-3">
        <div class="kpi-card p-3 h-100">
          <div class="kpi-title">Productos compuestos</div>
          <div id="kpiComp" class="kpi-value">—</div>
          <div class="text-muted mini">c_articulo · Compuesto = 'S'</div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="kpi-card p-3 h-100">
          <div class="kpi-title">Total de productos en BD</div>
          <div id="kpiTot" class="kpi-value">—</div>
          <div class="text-muted mini">c_articulo</div>
        </div>
      </div>
    </div>

    <div class="card mb-2">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Análisis de Artículos Compuestos</h6>
        <span class="text-muted">Manufactura o Kitting</span>
      </div>
      <div class="card-body">

        <!-- Búsqueda -->
        <div class="row g-2 align-items-end mb-2">
          <div class="col-md-6">
            <label class="form-label mb-0">Buscar <strong>producto compuesto</strong> (clave o descripción)</label>
            <input id="txtBuscar" class="form-control form-control-sm" placeholder="Ej. BUL-0012, 'Cherry', etc.">
          </div>
          <div class="col-md-2">
            <button id="btnBuscar" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Buscar</button>
          </div>
        </div>

        <!-- Resultados -->
        <div class="table-responsive mb-3">
          <table id="tblResultados" class="table table-sm table-striped table-hover w-100">
            <thead>
              <tr>
                <th class="dt-center">Acciones</th>
                <th>Clave</th>
                <th>Descripción</th>
                <th>UMed</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- Encabezado -->
        <div id="panelPadre" class="alert alert-light border d-none">
          <div class="d-flex flex-wrap gap-3 align-items-center">
            <div><strong>Producto compuesto:</strong> <span id="p_cve">—</span></div>
            <div><strong>Descripción:</strong> <span id="p_des">—</span></div>
            <div><strong>UMed:</strong> <span id="p_ume">—</span></div>
          </div>
          <div class="muted mt-1">Componentes activos del producto seleccionado.</div>
        </div>

        <!-- Componentes -->
        <div class="table-responsive">
          <table id="tblComponentes" class="table table-sm table-striped table-hover w-100">
            <thead>
              <tr>
                <th>Componente</th>
                <th>Descripción</th>
                <th class="dt-right">Cantidad</th>
                <th>Unidad</th>
                <th>Etapa</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <div class="d-flex gap-2 mt-2">
            <button id="btnCsv" class="btn btn-outline-secondary btn-sm" disabled>
              <i class="bi bi-filetype-csv"></i> Exportar CSV
            </button>
            <button id="btnPdf" class="btn btn-outline-secondary btn-sm" disabled>
              <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
            </button>
          </div>
        </div>

        <!-- Importador -->
        <hr>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h6 class="mb-0"><i class="bi bi-upload"></i> Importar BOM (CSV)</h6>
          <div class="btn-group btn-group-sm">
            <a class="btn btn-outline-secondary" href="bom.php?op=download_layout"><i class="bi bi-filetype-csv"></i>
              Descargar layout</a>
          </div>
        </div>
        <div class="row g-2 align-items-center mt-1">
          <div class="col-md-6">
            <input id="fileCsv" type="file" accept=".csv,text/csv" class="form-control form-control-sm">
            <div class="muted mt-1">
              Formato: <strong>A</strong>=producto_compuesto, <strong>B</strong>=componente,
              <strong>C</strong>=cantidad.
              La unidad de medida se toma de <code>c_articulo.unidadMedida</code> del componente.
            </div>
          </div>
          <div class="col-md-3">
            <div class="btn-group w-100">
              <button id="btnPreview" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i>
                Previsualizar</button>
              <button id="btnImport" class="btn btn-success btn-sm" disabled><i class="bi bi-cloud-upload"></i>
                Importar</button>
            </div>
          </div>
        </div>

        <div id="impPanel" class="mt-2 d-none">
          <div class="alert alert-info p-2 mb-2">
            <div id="impSummary" class="mb-1"></div>
            <div id="impErrors" class="mini text-danger prewrap"></div>
          </div>
          <div class="table-responsive">
            <table id="tblPreview" class="table table-sm table-striped table-hover w-100">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Producto compuesto</th>
                  <th>Componente</th>
                  <th>Cantidad</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

  </div>

  <!-- Toasts -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div id="appToastBody" class="toast-body">...</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
          aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!-- Modal Resumen de Importación -->
  <div class="modal fade" id="impModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-end">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title"><i class="bi bi-clipboard-check"></i> Resumen de importación</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <ul class="mb-2" style="font-size:11px;">
            <li><strong>Productos compuestos reemplazados:</strong> <span id="m_replaced">0</span></li>
            <li><strong>Líneas insertadas (staging):</strong> <span id="m_ins_stg">0</span></li>
            <li><strong>Líneas insertadas (producción):</strong> <span id="m_ins_prod">0</span></li>
            <li><strong>Sincronizado a producción:</strong> <span id="m_sync">No</span></li>
          </ul>
          <div>
            <strong>Errores (si hubo):</strong>
            <pre id="m_errs" class="mt-1"
              style="font-size:10px; white-space:pre-wrap; max-height:160px; overflow:auto;"></pre>
          </div>
          <div class="table-responsive mt-2">
            <table class="table table-sm table-striped" style="font-size:10px;">
              <thead>
                <tr>
                  <th>Producto compuesto (padre)</th>
                  <th class="text-end">Líneas cargadas</th>
                </tr>
              </thead>
              <tbody id="m_parents"><!-- se llena desde JS --></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary btn-sm" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
  <script>
    (function () {
      'use strict';
      let tRes = null, tComp = null, tPrev = null, padreSel = '';
      let toastObj = null;

      function toast(msg, type = 'success') {
        const el = document.getElementById('appToast');
        const body = document.getElementById('appToastBody');
        body.textContent = msg;
        el.classList.remove('text-bg-success', 'text-bg-info', 'text-bg-warning', 'text-bg-danger');
        el.classList.add(type === 'success' ? 'text-bg-success' : type === 'info' ? 'text-bg-info' : type === 'warning' ? 'text-bg-warning' : 'text-bg-danger');
        if (!toastObj) { toastObj = new bootstrap.Toast(el, { delay: 3500 }); }
        toastObj.show();
      }

      function kpis() {
        $.get('bom.php', { op: 'kpi_compuestos' }, r => $('#kpiComp').text(r.ok ? (r.total || 0) : '—'), 'json');
        $.get('bom.php', { op: 'kpi_total_articulos' }, r => $('#kpiTot').text(r.ok ? (r.total || 0) : '—'), 'json');
      }

      function buscar() {
        const q = $('#txtBuscar').val().trim();
        $.get('bom.php', { op: 'buscar_compuestos', q }, r => {
          if (!r.ok) { alert(r.msg || 'Error'); return; }
          const data = r.data || [];
          if (!tRes) {
            tRes = new DataTable('#tblResultados', {
              data, pageLength: 5, lengthChange: false, searching: false, info: true, order: [[1, 'asc']],
              columns: [
                {
                  data: null, className: 'dt-center', render: (d, t, x) => `
              <button class="btn btn-sm btn-outline-primary actVer" data-id="${x.cve_articulo}">
                <i class="bi bi-eye"></i> Ver
              </button>`},
                { data: 'cve_articulo' },
                { data: 'des_articulo' },
                { data: 'unidadMedida' }
              ]
            });
            $('#tblResultados').on('click', '.actVer', function () {
              const padre = $(this).data('id');
              padreSel = padre;
              cargarProducto(padre);
              cargarComponentes(padre);
            });
          } else {
            tRes.clear().rows.add(data).draw(false);
          }
          if (tComp) { tComp.clear().draw(); }
          $('#panelPadre').addClass('d-none');
        }, 'json');
      }

      function cargarProducto(padre) {
        $.get('bom.php', { op: 'get_producto', padre }, r => {
          if (!r.ok) { alert(r.msg || 'Error'); return; }
          const p = r.producto;
          $('#p_cve').text(p.cve_articulo || '—');
          $('#p_des').text(p.des_articulo || '—');
          $('#p_ume').text(p.unidadMedida || '—');
          $('#btnCsv, #btnPdf').prop('disabled', false);
          $('#panelPadre').removeClass('d-none');
        }, 'json');
      }

      function cargarComponentes(padre) {
        $.get('bom.php', { op: 'get_componentes', padre }, r => {
          if (!r.ok) { alert(r.msg || 'Error'); return; }
          const data = r.data || [];
          if (!tComp) {
            tComp = new DataTable('#tblComponentes', {
              data, paging: false, searching: false, info: false, order: [[0, 'asc']],
              columns: [
                { data: 'componente' },
                { data: 'descripcion' },
                { data: 'cantidad', className: 'dt-right' },
                { data: 'unidad' },
                { data: 'etapa' }
              ]
            });
          } else {
            tComp.clear().rows.add(data).draw(false);
          }
        }, 'json');
      }

      function parseCSV(text) {
        return text.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0).map(l => l.split(','));
      }
      function previewCSV() {
        const f = $('#fileCsv')[0].files[0];
        if (!f) { alert('Selecciona un archivo CSV.'); return; }
        const reader = new FileReader();
        reader.onload = (e) => {
          const rows = parseCSV(e.target.result);
          if (rows.length === 0) { alert('Archivo vacío.'); return; }
          let i = 0; if (rows[0] && rows[0][0] && isNaN(rows[0][0])) i = 1;
          let ok = 0, bad = 0, errs = [], data = [];
          for (let idx = i; idx < rows.length; idx++) {
            const r = rows[idx];
            const padre = (r[0] || '').trim();
            const hijo = (r[1] || '').trim();
            let cantRaw = (r[2] || '').trim();
            let status = 'OK';
            if (!padre || !hijo) { status = 'ERROR: producto compuesto / componente vacío'; }
            cantRaw = cantRaw.replace(/\s+/g, '').replace(',', '.');
            const cantidad = cantRaw === '' ? NaN : Number(cantRaw);
            if (status === 'OK' && isNaN(cantidad)) status = 'ERROR: cantidad inválida';
            if (status === 'OK') { ok++; } else { bad++; errs.push(`L${idx + 1}: ${status}`); }
            data.push({ idx: (idx - i + 1), padre, hijo, cantidad: isNaN(cantidad) ? (r[2] || '') : cantidad, status });
          }
          $('#impPanel').removeClass('d-none');
          $('#impSummary').html(`<strong>Previsualización:</strong> ${ok} válidas, ${bad} con error.`);
          $('#impErrors').html(errs.slice(0, 80).map(x => '• ' + x).join('\n'));
          if (tPrev) { tPrev.clear().destroy(); }
          tPrev = new DataTable('#tblPreview', {
            data, paging: true, pageLength: 10, lengthChange: false, searching: false, info: true, order: [[0, 'asc']],
            columns: [
              { data: 'idx', className: 'dt-right' },
              { data: 'padre' },
              { data: 'hijo' },
              { data: 'cantidad', className: 'dt-right' },
              { data: 'status' }
            ]
          });
          $('#btnImport').prop('disabled', bad > 0);
          toast('Previsualización lista ' + (bad > 0 ? '(con errores)' : ''), bad > 0 ? 'warning' : 'success');
        };
        reader.readAsText(f, 'UTF-8');
      }

      function importar() {
        const f = $('#fileCsv')[0].files[0];
        if (!f) { alert('Selecciona un archivo CSV.'); return; }
        if ($('#btnImport').prop('disabled')) { alert('Corrige los errores antes de importar.'); return; }
        const fd = new FormData(); fd.append('op', 'import_bom'); fd.append('file', f);

        $.ajax({
          url: 'bom.php', method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
          success: (r) => {
            const fillParents = (arr) => {
              const tb = $('#m_parents'); tb.empty();
              (arr || []).forEach(it => {
                tb.append(`<tr><td>${it.padre}</td><td class="text-end">${it.lineas}</td></tr>`);
              });
            };

            if (!r.ok) {
              $('#m_replaced').text(r.replaced_parents || 0);
              $('#m_ins_stg').text(r.inserted_stg || 0);
              $('#m_ins_prod').text(r.inserted_prod || 0);
              $('#m_sync').text(r.synced_prod ? 'Sí' : 'No');
              $('#m_errs').text((r.errors || [r.msg || 'Error desconocido']).join("\n"));
              fillParents(r.parents_detail);
              new bootstrap.Modal(document.getElementById('impModal')).show();
              toast(r.msg || 'Validación fallida. Revisa el detalle.', 'danger');
              return;
            }

            const info = `Importación OK: ${r.inserted_stg} líneas (staging), ${r.replaced_parents} productos reemplazados${r.synced_prod ? ' (sincronizado a producción)' : ''}.`;
            $('#impSummary').html(`<strong>${info}</strong>`);
            $('#impErrors').html('');
            $('#m_replaced').text(r.replaced_parents || 0);
            $('#m_ins_stg').text(r.inserted_stg || 0);
            $('#m_ins_prod').text(r.inserted_prod || 0);
            $('#m_sync').text(r.synced_prod ? 'Sí' : 'No');
            $('#m_errs').text('');
            fillParents(r.parents_detail);
            new bootstrap.Modal(document.getElementById('impModal')).show();

            if (padreSel) { cargarComponentes(padreSel); }
            kpis();
            toast('Recetas reemplazadas correctamente.', 'success');
          },
          error: () => toast('Fallo la carga del archivo.', 'danger')
        });
      }

      $('#btnBuscar').on('click', buscar);
      $('#txtBuscar').on('keypress', e => { if (e.which === 13) buscar(); });
      $('#btnPreview').on('click', previewCSV);
      $('#btnImport').on('click', importar);

      $('#btnCsv').on('click', function () {
        if (!padreSel) { toast('Selecciona un producto compuesto.', 'warning'); return; }
        window.location = 'bom.php?op=export_csv&padre=' + encodeURIComponent(padreSel);
      });
      $('#btnPdf').on('click', function () {
        if (!padreSel) { toast('Selecciona un producto compuesto.', 'warning'); return; }
        window.open('bom.php?op=export_pdf&padre=' + encodeURIComponent(padreSel), '_blank');
      });

      kpis();
    })();
  </script>
</body>

</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>