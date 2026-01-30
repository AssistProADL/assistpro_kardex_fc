<?php
/* =========================================================
   UI - LISTADO DE CORRIDAS DE IMPORTACIÓN
   Ruta: /public/importadores/import_runs.php
   Links RELATIVOS (compatibles con /assistpro_kardex_fc/public/importadores/)
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

if ($f_tipo !== '') { $where[] = "r.tipo_ingreso = ?"; $params[] = $f_tipo; }
if ($f_status !== '') { $where[] = "UPPER(r.status) = ?"; $params[] = $f_status; }
if ($f_folio !== '') { $where[] = "r.folio_importacion LIKE CONCAT('%', ?, '%')"; $params[] = $f_folio; }
if ($f_user !== '') { $where[] = "UPPER(r.usuario) LIKE CONCAT('%', ?, '%')"; $params[] = $f_user; }

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

<div class="container-fluid px-4">
  <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
    <div>
      <h4 class="mb-1">Admin de Importaciones · Corridas</h4>
      <div class="text-muted">Gobierno de importaciones (validación, auditoría y ejecución).</div>
    </div>

    <div class="d-flex gap-2">
      <!-- RELATIVO -->
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
            <option value="ERROR" <?= ($f_status==='ERROR')?'selected':'' ?>>ERROR</option>
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
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:90px;">ID</th>
              <th>Folio</th>
              <th style="width:120px;">Tipo</th>
              <th>Importador</th>
              <th style="width:140px;">Status</th>
              <th style="width:120px;">Líneas</th>
              <th style="width:120px;">Errores</th>
              <th style="width:160px;">Usuario</th>
              <th style="width:190px;">Fecha</th>
              <th style="width:260px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!$runs): ?>
            <tr><td colspan="10" class="text-muted">Sin corridas con esos filtros.</td></tr>
          <?php else: ?>
            <?php foreach($runs as $r):
              $rid = (int)$r['id'];
              $tipo = strtoupper((string)$r['tipo_ingreso']);
              $status = strtoupper((string)$r['status']);
              $badge = 'bg-secondary';
              if ($status==='VALIDADO') $badge='bg-info';
              if ($status==='APLICADO') $badge='bg-success';
              if ($status==='ERROR') $badge='bg-danger';
              if ($status==='BORRADOR') $badge='bg-warning text-dark';

              // LINKS RELATIVOS
              $url_importador = "importador_traslado_almacenes.php?run_id=$rid";
              $url_detalle    = "import_run_detalle.php?run_id=$rid";
              $url_csv        = "../api/importadores/api_import_run_export_csv.php?run_id=$rid&estado=ALL&q=";
            ?>
              <tr>
                <td><?= $rid ?></td>
                <td class="fw-semibold"><?= h($r['folio_importacion']) ?></td>
                <td><?= h($tipo) ?></td>
                <td><?= h($r['importador_desc'] ?: '-') ?></td>
                <td><span class="badge <?= $badge ?>"><?= h($status ?: '-') ?></span></td>
                <td class="text-end"><?= (int)$r['total_lineas'] ?></td>
                <td class="text-end text-danger"><?= (int)$r['total_err'] ?></td>
                <td><?= h($r['usuario']) ?></td>
                <td><?= h($r['fecha_importacion']) ?></td>
                <td>
                  <div class="btn-group">
                    <a class="btn btn-outline-secondary btn-sm" href="<?= h($url_detalle) ?>">
                      <i class="fa fa-table"></i> Detalle
                    </a>
                    <a class="btn btn-outline-success btn-sm" href="<?= h($url_csv) ?>">
                      <i class="fa fa-file-csv"></i> CSV
                    </a>
                  </div>
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

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
