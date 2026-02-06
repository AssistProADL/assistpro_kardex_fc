<?php
// ======================================================
// BOOTSTRAP REAL DEL SISTEMA
// ======================================================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

db_pdo();
global $pdo;

// ======================================================
// PROVEEDOR (flujo existente)
// ======================================================
$proveedor_id = isset($_GET['proveedor']) ? intval($_GET['proveedor']) : null;

// ======================================================
// OBTENER ÓRDENES DE COMPRA (RESPETA TU MODELO)
// ======================================================
$ocs = [];

if ($proveedor_id) {
    $sql = "
        SELECT
            ID_Aduana,
            folio_oc,
            num_pedimento,
            fech_pedimento,
            fecha_eta
        FROM th_aduana
        WHERE proveedor_id = :proveedor
          AND status = 'A'
        ORDER BY fech_pedimento DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':proveedor' => $proveedor_id
    ]);

    $ocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid mt-4">

  <h4 class="mb-3">
    Panel de Proveedor – Órdenes de Compra
  </h4>

  <div class="card">

    <div class="card-header bg-primary text-white">
      Selecciona las Órdenes de Compra
    </div>

    <div class="card-body">

      <?php if (empty($ocs)): ?>
        <div class="alert alert-warning">
          No hay Órdenes de Compra disponibles
        </div>
      <?php else: ?>

      <form method="post" action="confirmar_cita.php">

        <ul class="list-group">

          <?php foreach ($ocs as $oc): ?>
            <?php
              $idAduana = (int)$oc['ID_Aduana'];

              // Mostrar lo que exista (nuevo / legacy)
              $folioMostrar =
                $oc['folio_oc']
                ?? $oc['num_pedimento']
                ?? 'OC legacy';
            ?>

            <li class="list-group-item">

              <div class="d-flex justify-content-between align-items-start">

                <div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      name="ocs[]"
                      value="<?= $idAduana ?>"
                      id="oc_<?= $idAduana ?>"
                    >
                    <label class="form-check-label" for="oc_<?= $idAduana ?>">
                      <strong>OC:</strong>
                      <?= htmlspecialchars($folioMostrar) ?>
                    </label>
                  </div>

                  <small class="text-muted d-block mt-1">
                    Fecha OC:
                    <?= htmlspecialchars($oc['fech_pedimento'] ?? 'N/D') ?>
                    |
                    ETA:
                    <?= htmlspecialchars($oc['fecha_eta'] ?? 'N/D') ?>
                    |
                    ID Aduana:
                    <?= $idAduana ?>
                  </small>
                </div>

                <!-- BOTÓN VER DETALLE -->
                <button
                  type="button"
                  class="btn btn-sm btn-info btn-ver-detalle"
                  data-id-aduana="<?= $idAduana ?>"
                >
                  Ver detalle
                </button>

              </div>

            </li>
          <?php endforeach; ?>

        </ul>

        <button class="btn btn-success mt-3">
          Confirmar cita con OCs seleccionadas
        </button>

      </form>

      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ======================================================
     MODAL DETALLE DE OC
     ====================================================== -->
<div class="modal fade" id="modalDetalleOC" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Detalle de la Orden de Compra</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="modalDetalleBody">
        <div class="text-muted text-center">
          Selecciona una OC para ver su detalle
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ======================================================
     JS – CONSUMO REAL DEL API (ID_Aduana)
     ====================================================== -->
<script>
document.addEventListener('click', function (e) {

  if (!e.target.classList.contains('btn-ver-detalle')) return;

  const idAduana = e.target.dataset.idAduana;
  const body = document.getElementById('modalDetalleBody');

  body.innerHTML = '<div class="text-center">Cargando detalle...</div>';

  fetch(
    '/assistpro_kardex_fc/public/api/recepcion/recepcion_oc_detalle_api.php?id_oc='
    + idAduana
  )
  .then(r => r.json())
  .then(resp => {

    if (!resp.ok) {
      body.innerHTML =
        `<div class="alert alert-warning">${resp.msg}</div>`;
      return;
    }

    if (!resp.data || resp.data.length === 0) {
      body.innerHTML =
        '<div class="alert alert-info">Esta OC no tiene partidas</div>';
      return;
    }

    let html = `
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr>
            <th>Artículo</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Pendiente</th>
            <th>UM</th>
          </tr>
        </thead>
        <tbody>
    `;

    resp.data.forEach(row => {
      html += `
        <tr>
          <td>${row.cve_articulo ?? ''}</td>
          <td>${row.des_articulo ?? ''}</td>
          <td>${row.cantidad ?? ''}</td>
          <td>${row.pendiente ?? ''}</td>
          <td>${row.empq_umed_nombre ?? ''}</td>
        </tr>
      `;
    });

    html += '</tbody></table>';
    body.innerHTML = html;

  })
  .catch(() => {
    body.innerHTML =
      '<div class="alert alert-danger">Error al consultar el API</div>';
  });

  new bootstrap.Modal(
    document.getElementById('modalDetalleOC')
  ).show();

});
</script>

<?php
// ======================================================
// CIERRE REAL DEL LAYOUT
// ======================================================
require_once __DIR__ . '/../bi/_menu_global_end.php';
