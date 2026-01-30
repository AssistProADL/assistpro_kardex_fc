<?php
// public/importadores/import_run_csv_preview.php
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

include_once __DIR__ . '/../bi/_menu_global.php';

$run_id = isset($_GET['run_id']) ? intval($_GET['run_id']) : 0;
if ($run_id <= 0) {
  echo "<div class='container-fluid p-4'><div class='alert alert-danger'>run_id requerido</div></div>";
  include_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

$st = $pdo->prepare("SELECT id, folio_importacion, tipo_ingreso, status, archivo_nombre, total_lineas, total_ok, total_err, fecha_importacion
                     FROM ap_import_runs WHERE id=? LIMIT 1");
$st->execute([$run_id]);
$run = $st->fetch(PDO::FETCH_ASSOC);

if (!$run) {
  echo "<div class='container-fluid p-4'><div class='alert alert-danger'>Corrida no encontrada</div></div>";
  include_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

$API_EXPORT = "/assistpro_kardex_fc/public/api/importadores/api_import_run_export_csv.php?run_id=".$run_id."&estado=ALL";
$BACK_RUNS  = "/assistpro_kardex_fc/public/importadores/import_runs.php?id=".$run_id;

// Preview rows
$st = $pdo->prepare("SELECT linea_num, estado, mensaje, data_json
                     FROM ap_import_run_rows
                     WHERE run_id=?
                     ORDER BY linea_num ASC
                     LIMIT 200");
$st->execute([$run_id]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function safe($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function jget($json, $k){
  $a = json_decode($json, true);
  return is_array($a) && isset($a[$k]) ? $a[$k] : '';
}
?>
<style>
  html, body, .container-fluid, .card, .table, .btn, .form-control, .form-select { font-size:10px !important; }
  .ap-card { border:1px solid rgba(0,0,0,.08); border-radius:10px; }
  .ap-shadow { box-shadow: 0 6px 18px rgba(0,0,0,.06); }
  .ap-headerbar {
    background:#0b5ed7; color:#fff; border-radius:10px 10px 0 0;
    padding:10px 14px; font-weight:600;
  }
  .ap-chip { display:inline-flex; gap:6px; padding:6px 10px; border-radius:999px; border:1px solid rgba(0,0,0,.12); background:#fff; }
  .ap-table thead th{ background:#f6f8fb; font-weight:700; }
</style>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h4 class="mb-1">CSV Validación · Vista previa</h4>
      <div class="text-muted">Antes de emitir el archivo final, valida visualmente la corrida.</div>
      <div class="mt-2 d-flex flex-wrap gap-2">
        <span class="ap-chip"><strong>Run</strong> <?= safe($run['id']) ?></span>
        <span class="ap-chip"><strong>Folio</strong> <?= safe($run['folio_importacion']) ?></span>
        <span class="ap-chip"><strong>Estatus</strong> <?= safe($run['status']) ?></span>
        <span class="ap-chip"><strong>Archivo</strong> <?= safe($run['archivo_nombre']) ?></span>
        <span class="ap-chip"><strong>Layout UI</strong> BL_ORIGEN, LP_O_PRODUCTO, LOTE_SERIE, CANTIDAD, <strong>ZRD_BL</strong></span>
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-secondary" href="<?= safe($BACK_RUNS) ?>"><i class="bi bi-arrow-left"></i> Regresar</a>
      <a class="btn btn-success" href="<?= safe($API_EXPORT) ?>"><i class="bi bi-download"></i> Descargar CSV Validación</a>
      <button class="btn btn-primary" disabled><i class="bi bi-lightning-charge"></i> Aplicar Importación</button>
    </div>
  </div>

  <hr class="my-3"/>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="ap-card ap-shadow p-3">
        <div class="text-muted">Total líneas</div>
        <div style="font-size:22px;font-weight:800;"><?= intval($run['total_lineas']) ?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="ap-card ap-shadow p-3">
        <div class="text-muted">OK</div>
        <div style="font-size:22px;font-weight:800;"><?= intval($run['total_ok']) ?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="ap-card ap-shadow p-3">
        <div class="text-muted">Errores</div>
        <div style="font-size:22px;font-weight:800;color:#dc3545;"><?= intval($run['total_err']) ?></div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="ap-card ap-shadow p-3">
        <div class="text-muted">Preview</div>
        <div style="font-size:22px;font-weight:800;"><?= count($rows) ?> filas</div>
      </div>
    </div>
  </div>

  <div class="ap-card ap-shadow">
    <div class="ap-headerbar"><i class="bi bi-table"></i> Vista previa (primeras 200 líneas)</div>
    <div class="card-body">
      <div class="table-responsive ap-card" style="border-radius:10px; overflow:hidden;">
        <table class="table table-sm mb-0 ap-table">
          <thead>
            <tr>
              <th>Línea</th>
              <th>Estado</th>
              <th>BL_ORIGEN</th>
              <th>LP_O_PRODUCTO</th>
              <th>LOTE_SERIE</th>
              <th>CANTIDAD</th>
              <th>ZRD_BL</th>
              <th>Mensaje</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $json = $r['data_json'] ?? '{}';
                $bl   = jget($json,'BL_ORIGEN');
                $lp   = jget($json,'LP_O_PRODUCTO');
                $lot  = jget($json,'LOTE_SERIE');
                $cant = jget($json,'CANTIDAD');

                // estándar UI: ZRD_BL, pero puede venir guardado como ZONA_RECIBO_DESTINO
                $zrd  = jget($json,'ZRD_BL');
                if ($zrd === '') $zrd = jget($json,'ZONA_RECIBO_DESTINO');
              ?>
              <tr>
                <td><?= safe($r['linea_num']) ?></td>
                <td>
                  <?php if (($r['estado'] ?? '') === 'OK'): ?>
                    <span class="badge bg-success">OK</span>
                  <?php else: ?>
                    <span class="badge bg-danger">ERR</span>
                  <?php endif; ?>
                </td>
                <td><?= safe($bl) ?></td>
                <td><?= safe($lp) ?></td>
                <td><?= safe($lot) ?></td>
                <td><?= safe($cant) ?></td>
                <td><?= safe($zrd) ?></td>
                <td><?= safe($r['mensaje'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!count($rows)): ?>
              <tr><td colspan="8" class="text-muted">Sin líneas para mostrar.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="text-muted mt-2" style="font-size:10px;">
        * Esta vista es previa obligatoria. La descarga final se emite desde aquí.
      </div>
    </div>
  </div>

</div>

<?php include_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
