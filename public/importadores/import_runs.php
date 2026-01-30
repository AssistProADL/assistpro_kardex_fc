<?php
/* =========================================================
   UI - LISTADO DE CORRIDAS DE IMPORTACIÓN
   Ruta: /public/importadores/import_runs.php
   ========================================================= */

require_once __DIR__ . '/../bi/_menu_global.php';

require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

// Filtros
$f_tipo   = isset($_GET['tipo_ingreso']) ? strtoupper(trim($_GET['tipo_ingreso'])) : '';
$f_status = isset($_GET['status']) ? strtoupper(trim($_GET['status'])) : '';
$f_folio  = isset($_GET['folio']) ? trim($_GET['folio']) : '';
$f_user   = isset($_GET['usuario']) ? strtoupper(trim($_GET['usuario'])) : '';
$f_days   = isset($_GET['days']) ? max(1, intval($_GET['days'])) : 30;

$limit = isset($_GET['limit']) ? max(20, min(500, intval($_GET['limit']))) : 100;

// Query
$where = [];
$params = [];

$where[] = "r.fecha_importacion >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$params[] = $f_days;

if ($f_tipo !== '')   { $where[] = "r.tipo_ingreso = ?"; $params[] = $f_tipo; }
if ($f_status !== '') { $where[] = "UPPER(r.status) = ?"; $params[] = $f_status; }
if ($f_folio !== '')  { $where[] = "r.folio_importacion LIKE CONCAT('%', ?, '%')"; $params[] = $f_folio; }
if ($f_user !== '')   { $where[] = "UPPER(r.usuario) LIKE CONCAT('%', ?, '%')"; $params[] = $f_user; }

$sql = "
  SELECT
    r.id,
    r.folio_importacion,
    r.tipo_ingreso,
    r.usuario,
    r.fecha_importacion,
    r.status,
    r.archivo_nombre,
    r.total_lineas,
    r.total_ok,
    r.total_err,
    i.descripcion AS importador_desc
  FROM ap_import_runs r
  LEFT JOIN c_importador i ON i.clave = r.tipo_ingreso
  WHERE " . implode(" AND ", $where) . "
  ORDER BY r.id DESC
  LIMIT $limit
";
$st = $pdo->prepare($sql);
$st->execute($params);
$runs = $st->fetchAll(PDO::FETCH_ASSOC);

// Dropdown de importadores
$st2 = $pdo->query("SELECT clave, descripcion FROM c_importador WHERE COALESCE(activo,1)=1 ORDER BY clave");
$importadores = $st2 ? $st2->fetchAll(PDO::FETCH_ASSOC) : [];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<style>
  .table-responsive { overflow-x: auto; }

  /* Evita "apachurrar" y traslapes: prioriza scroll horizontal */
  table.import-runs-table{
    width: 100%;
    min-width: 1180px;
    border-collapse: separate;
    border-spacing: 0;
  }

  /* 1 sola línea por registro */
  table.import-runs-table th,
  table.import-runs-table td{
    white-space: nowrap;
    vertical-align: middle;
  }

  /* Elipsis para textos (no aplica en acciones) */
  .cell-ellipsis{
    display:block;
    max-width: 100%;
    overflow:hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Columna sticky de acciones */
  th.col-acciones,
  td.col-acciones{
    position: sticky;
    left: 0;
    z-index: 3;
    background: #fff;
    box-shadow: 8px 0 12px -12px rgba(0,0,0,.35);
  }
  thead th.col-acciones{
    z-index: 4;
    background: #f8f9fa;
  }
  td.col-acciones{ overflow: visible !important; }

  /* Botonera icon-only */
  .acciones{
    display:flex;
    flex-wrap: nowrap;
    gap:6px;
    align-items:center;
  }
  .acciones .btn-icon{
    width: 34px;
    height: 30px;
    padding: 0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1;
  }

  /* Densidad ejecutiva */
  .import-runs-table td, .import-runs-table th { padding-top:.45rem; padding-bottom:.45rem; }
</style>

<div class="container-fluid px-4">
  <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
    <div>
      <h4 class="mb-1">Admin de Importaciones · Corridas</h4>
      <div class="text-muted">Gobierno de importaciones (validación, auditoría y ejecución).</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="importador_traslado_almacenes.php">
        <i class="fa fa-plus"></i> Nueva Importación (Traslado)
      </a>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="GET" action="import_runs.php">
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Importador (tipo_ingreso)</label>
          <select class="form-select" name="tipo_ingreso">
            <option value="">Todos</option>
            <?php foreach($importadores as $imp): ?>
              <?php $k = strtoupper((string)$imp['clave']); ?>
              <option value="<?= h($imp['clave']) ?>" <?= ($f_tipo === $k) ? 'selected' : '' ?>>
                <?= h($imp['clave']) ?> · <?= h($imp['descripcion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Status</label>
          <select class="form-select" name="status">
            <option value="">Todos</option>
            <option value="BORRADOR" <?= ($f_status==='BORRADOR')?'selected':'' ?>>BORRADOR</option>
            <option value="VALIDADO" <?= ($f_status==='VALIDADO')?'selected':'' ?>>VALIDADO</option>
            <option value="APLICADO" <?= ($f_status==='APLICADO')?'selected':'' ?>>APLICADO</option>
            <option value="ERROR"    <?= ($f_status==='ERROR')?'selected':'' ?>>ERROR</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Folio</label>
          <input class="form-control" name="folio" value="<?= h($f_folio) ?>" placeholder="TRALM-20260126-000123" />
        </div>

        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Usuario</label>
          <input class="form-control" name="usuario" value="<?= h($f_user) ?>" placeholder="AOLIVARES" />
        </div>

        <div class="col-md-1">
          <label class="form-label small text-muted mb-1">Días</label>
          <input type="number" class="form-control" name="days" value="<?= (int)$f_days ?>" min="1" max="365" />
        </div>

        <div class="col-md-1">
          <label class="form-label small text-muted mb-1">Límite</label>
          <input type="number" class="form-control" name="limit" value="<?= (int)$limit ?>" min="20" max="500" />
        </div>

        <div class="col-md-1 d-grid">
          <button class="btn btn-dark">
            <i class="fa fa-search"></i> Filtrar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="text-muted small mb-2">
        Mostrando <?= count($runs) ?> corridas (últimos <?= (int)$f_days ?> días).
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle import-runs-table">
          <thead class="table-light">
            <tr>
              <!-- Header se queda como "Acciones" -->
              <th class="col-acciones" style="width: 170px;">Acciones</th>
              <th style="width: 70px;">ID</th>
              <th style="width: 210px;">Folio</th>
              <th style="width: 80px;">Tipo</th>
              <th style="width: 230px;">Importador</th>
              <th style="width: 110px;">Status</th>
              <th style="width: 90px;" class="text-end">Líneas</th>
              <th style="width: 90px;" class="text-end">Errores</th>
              <th style="width: 140px;">Usuario</th>
              <th style="width: 190px;">Fecha</th>
            </tr>
          </thead>

          <tbody>
          <?php if(!$runs): ?>
            <tr><td colspan="10" class="text-muted">Sin corridas con esos filtros.</td></tr>
          <?php else: ?>
            <?php foreach($runs as $r):
              $rid    = (int)$r['id'];
              $tipo   = strtoupper((string)$r['tipo_ingreso']);
              $status = strtoupper((string)$r['status']);

              $badge = 'bg-secondary';
              if ($status==='VALIDADO') $badge='bg-info';
              if ($status==='APLICADO') $badge='bg-success';
              if ($status==='ERROR')    $badge='bg-danger';
              if ($status==='BORRADOR') $badge='bg-warning text-dark';

              // Links
              $url_editar  = "importador_traslado_almacenes.php?run_id=$rid";
              $url_detalle = "import_run_detalle.php?run_id=$rid";
              $url_csv     = "../api/importadores/api_import_run_export_csv.php?run_id=$rid&estado=ALL&q=";

              // Tooltip Procesar
              $procHint  = ($status==='APLICADO') ? 'Aplicado' : (($status==='ERROR') ? 'Reprocesar' : 'Procesar');
              $procClass = ($status==='APLICADO') ? 'btn-outline-secondary disabled' : 'btn-outline-primary';
              $procAttrs = ($status==='APLICADO') ? 'tabindex="-1" aria-disabled="true"' : '';
            ?>
              <tr>
                <td class="col-acciones">
                  <div class="acciones" role="group" aria-label="Acciones">
                    <!-- Procesar / Reprocesar -->
                    <a class="btn btn-sm btn-icon <?= h($procClass) ?> js-tip"
                       href="<?= h($url_editar) ?>"
                       title="<?= h($procHint) ?>"
                       data-bs-toggle="tooltip" data-bs-placement="top"
                       <?= $procAttrs ?>>
                      <i class="fa fa-bolt"></i>
                    </a>

                    <!-- Editar -->
                    <a class="btn btn-outline-dark btn-sm btn-icon js-tip"
                       href="<?= h($url_editar) ?>"
                       title="Editar"
                       data-bs-toggle="tooltip" data-bs-placement="top">
                      <i class="fa fa-pen"></i>
                    </a>

                    <!-- Detalle -->
                    <a class="btn btn-outline-secondary btn-sm btn-icon js-tip"
                       href="<?= h($url_detalle) ?>"
                       title="Detalle"
                       data-bs-toggle="tooltip" data-bs-placement="top">
                      <i class="fa fa-table"></i>
                    </a>

                    <!-- CSV -->
                    <a class="btn btn-outline-success btn-sm btn-icon js-tip"
                       href="<?= h($url_csv) ?>"
                       title="CSV"
                       data-bs-toggle="tooltip" data-bs-placement="top">
                      <i class="fa fa-file-csv"></i>
                    </a>
                  </div>
                </td>

                <td><?= $rid ?></td>

                <td>
                  <span class="cell-ellipsis" title="<?= h($r['folio_importacion']) ?>">
                    <strong><?= h($r['folio_importacion']) ?></strong>
                  </span>
                </td>

                <td><?= h($tipo) ?></td>

                <td>
                  <span class="cell-ellipsis" title="<?= h($r['importador_desc'] ?: '-') ?>">
                    <?= h($r['importador_desc'] ?: '-') ?>
                  </span>
                </td>

                <td><span class="badge <?= $badge ?>"><?= h($status ?: '-') ?></span></td>

                <td class="text-end"><?= (int)$r['total_lineas'] ?></td>
                <td class="text-end text-danger"><?= (int)$r['total_err'] ?></td>

                <td>
                  <span class="cell-ellipsis" title="<?= h($r['usuario']) ?>">
                    <?= h($r['usuario']) ?>
                  </span>
                </td>

                <td>
                  <span class="cell-ellipsis" title="<?= h($r['fecha_importacion']) ?>">
                    <?= h($r['fecha_importacion']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<script>
(function(){
  // Bootstrap 5 tooltips
  if (window.bootstrap && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
      new bootstrap.Tooltip(el);
    });
    return;
  }
  // Bootstrap 4 tooltips (jQuery)
  if (window.jQuery && jQuery.fn && jQuery.fn.tooltip) {
    jQuery(function(){
      jQuery('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').tooltip();
    });
    return;
  }
  // Si no hay Bootstrap, el title nativo funciona como hint.
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
