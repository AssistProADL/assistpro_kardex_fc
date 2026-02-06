<?php
// public/control_patios/patios_dashboard_visita.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

function e($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$id_visita = isset($_GET['id_visita']) ? (int)$_GET['id_visita'] : 0;
if ($id_visita <= 0) {
  echo "<div class='alert alert-danger m-3'>Visita inválida.</div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

/**
 * =========================
 * Cargar datos principales
 * =========================
 */
$visita = db_row("
  SELECT v.*,
         t.Nombre AS transporte_nombre,
         t.Placas AS transporte_placas
  FROM t_patio_visita v
  LEFT JOIN c_transporte t ON t.ID_Transporte = v.id_transporte
  WHERE v.id_visita = :id
", [':id'=>$id_visita]);

if (!$visita) {
  echo "<div class='alert alert-danger m-3'>La visita no existe.</div>";
  require_once __DIR__ . '/../bi/_menu_global_end.php';
  exit;
}

// OCs vinculadas
$ocs = db_all("
  SELECT d.id_doc AS id_oc, d.folio, d.proveedor, d.fecha
  FROM t_patio_doclink l
  JOIN c_documentos d ON d.id_doc = l.id_doc
  WHERE l.id_visita = :id
", [':id'=>$id_visita]);

// KPIs rápidos
$totalOCs = count($ocs);

/**
 * Packing List / ASN (placeholder lógico)
 * En el futuro aquí consultas tu tabla real
 */
$packing_generado = false; // TODO: detectar real
?>

<style>
/* ================== DASHBOARD RESPONSIVE ================== */
.visita-header{
  position:sticky; top:0; z-index:5;
  background:#fff; border-bottom:1px solid #eee;
  padding:10px 15px;
}
.visita-kpi{
  background:#f8f9fa; border-radius:10px;
  padding:10px; text-align:center;
}
.visita-kpi b{ font-size:18px; display:block; }
.card-touch{ margin-bottom:12px; }
.badge-status{ font-size:13px; padding:6px 10px; }
@media (max-width: 768px){
  .hide-mobile{ display:none; }
}
</style>

<div class="container-fluid">

  <!-- ================= HEADER ================= -->
  <div class="visita-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">
        Visita #<?= $id_visita ?>
        <span class="badge bg-primary badge-status">
          <?= e($visita['estatus']) ?>
        </span>
      </h5>
      <div class="text-muted small">
        Llegada: <?= e($visita['fecha_llegada']) ?>
      </div>
    </div>

    <div class="mt-2 mt-md-0">
      <a href="patios_admin.php" class="btn btn-sm btn-outline-secondary">
        ← Volver a tablero
      </a>
    </div>
  </div>

  <!-- ================= KPIs ================= -->
  <div class="row mt-3 g-2">
    <div class="col-6 col-md-3">
      <div class="visita-kpi">
        <b><?= $totalOCs ?></b>
        OCs
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="visita-kpi">
        <b><?= e($visita['empresa_id']) ?></b>
        Empresa
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="visita-kpi">
        <b><?= e($visita['almacenp_id']) ?></b>
        Almacén
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="visita-kpi">
        <b><?= $packing_generado ? 'Sí' : 'No' ?></b>
        Packing List
      </div>
    </div>
  </div>

  <div class="row mt-3">

    <!-- ================= COLUMNA IZQ ================= -->
    <div class="col-md-6">

      <!-- SOLICITUD -->
      <div class="card card-touch">
        <div class="card-header">🧾 Solicitud de visita</div>
        <div class="card-body">
          <p><b>Estatus:</b> <?= e($visita['estatus']) ?></p>
          <p><b>Observaciones:</b><br><?= nl2br(e($visita['observaciones'])) ?></p>
        </div>
      </div>

      <!-- TRANSPORTE -->
      <div class="card card-touch">
        <div class="card-header">🚚 Transporte</div>
        <div class="card-body">
          <p><b>Unidad:</b> <?= e($visita['transporte_nombre'] ?? 'N/D') ?></p>
          <p><b>Placas:</b> <?= e($visita['transporte_placas'] ?? 'N/D') ?></p>
        </div>
      </div>

    </div>

    <!-- ================= COLUMNA DER ================= -->
    <div class="col-md-6">

      <!-- OCS -->
      <div class="card card-touch">
        <div class="card-header">📦 Órdenes de Compra</div>
        <div class="card-body">

          <?php if (!$ocs): ?>
            <div class="text-muted">No hay OCs vinculadas.</div>
          <?php else: ?>

            <!-- Desktop -->
            <div class="table-responsive hide-mobile">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>OC</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ocs as $oc): ?>
                    <tr>
                      <td><?= e($oc['folio']) ?></td>
                      <td><?= e($oc['proveedor']) ?></td>
                      <td><?= e($oc['fecha']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile -->
            <div class="d-block d-md-none">
              <?php foreach ($ocs as $oc): ?>
                <div class="border rounded p-2 mb-2">
                  <b><?= e($oc['folio']) ?></b><br>
                  <?= e($oc['proveedor']) ?><br>
                  <small><?= e($oc['fecha']) ?></small>
                </div>
              <?php endforeach; ?>
            </div>

          <?php endif; ?>

        </div>
      </div>

      <!-- PACKING / ASN -->
      <div class="card card-touch">
        <div class="card-header">📄 Packing List / ASN</div>
        <div class="card-body">
          <?php if ($packing_generado): ?>
            <a href="#" class="btn btn-outline-primary w-100 mb-2">
              Ver Packing List (PDF)
            </a>
            <a href="#" class="btn btn-outline-success w-100">
              Ver ASN
            </a>
          <?php else: ?>
            <div class="text-muted mb-2">
              El packing list se generará al autorizar la visita.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= ACCIONES ================= -->
  <div class="card mt-3">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-end">

      <button class="btn btn-success btn-lg">
        Autorizar visita
      </button>

      <button class="btn btn-warning btn-lg">
        Reprogramar
      </button>

      <button class="btn btn-danger btn-lg">
        Rechazar
      </button>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
