<?php
// proveedor_panel_seleccion.php
declare(strict_types=1);
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

function get_proveedores(): array {
    $url = "http://localhost/assistpro_kardex_fc/public/api/proveedores.php?action=list";
    $json = @file_get_contents($url);
    $data = json_decode($json, true);
    return isset($data['rows']) && is_array($data['rows']) ? $data['rows'] : [];
}

function get_ocs(string $almacen, string $proveedor): array {
    $url = "http://localhost/assistpro_kardex_fc/public/api/recepcion/recepcion_oc_api.php?almacen=" . urlencode($almacen) . "&proveedor=" . urlencode($proveedor);
    $json = @file_get_contents($url);
    $data = json_decode($json, true);
    return ($data && isset($data['data']) && is_array($data['data'])) ? $data['data'] : [];
}

$almacen = 'WH8';
$proveedor_id = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
$proveedores = get_proveedores();
$ocs_pendientes = [];

if ($proveedor_id !== '') {
    $ocs_pendientes = get_ocs($almacen, $proveedor_id);
}
?>

<style>
.proveedor-panel { padding: 20px; font-size: 14px; }
.card { margin-bottom: 20px; }
</style>

<div class="proveedor-panel">
  <h4>👋 Panel de Proveedor: Simulación</h4>

  <form method="get" class="form-inline mb-4">
    <label class="mr-2">Selecciona un proveedor:</label>
    <select name="proveedor" class="form-control mr-2">
      <option value="">-- Elegir --</option>
      <?php foreach ($proveedores as $prov): ?>
        <?php
          if (!is_array($prov) || !isset($prov['ID_Proveedor'])) {
              continue;
          }
          $clave = $prov['cve_proveedor'] ?? 'SIN_CLAVE';
          $nombre = $prov['Nombre'] ?? 'SIN_NOMBRE';
          $label = "{$clave} - {$nombre}";
        ?>
        <option value="<?= $prov['ID_Proveedor'] ?>" <?= $proveedor_id === (string)$prov['ID_Proveedor'] ? "selected" : "" ?>>
          <?= htmlspecialchars($label) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Ver OCs</button>
  </form>

  <?php if ($proveedor_id && empty($ocs_pendientes)): ?>
    <div class="alert alert-warning">No se encontraron OCs abiertas para este proveedor.</div>
  <?php endif; ?>

  <?php if (!empty($ocs_pendientes)): ?>
    <form method="post" action="confirmar_cita.php">
      <input type="hidden" name="proveedor_id" value="<?= htmlspecialchars($proveedor_id) ?>">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-archive"></i> Selecciona las Órdenes de Compra que entregarás
        </div>
        <div class="card-body">
          <ul class="list-group">

<?php foreach ($ocs_pendientes as $oc): ?>
    <?php
        $id = htmlspecialchars($oc['id_oc'] ?? '');
        $folio = htmlspecialchars($oc['folio_oc'] ?? '');
        $fecha_eta = htmlspecialchars($oc['fecha_eta'] ?? '');
        $fecha_pedimento = htmlspecialchars($oc['fech_pedimento'] ?? '');
        $monto = htmlspecialchars($oc['Monto'] ?? '');
    ?>
    <li class="list-group-item">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="ocs[]" value="<?= $id ?>" id="oc_<?= $id ?>">
            <label class="form-check-label" for="oc_<?= $id ?>">
                <strong>OC:</strong> <?= $id ?> |
                <strong>Folio:</strong> <?= $folio ?><br>
                <small>
                    Fecha OC: <?= $fecha_pedimento ?> |
                    ETA: <?= $fecha_eta ?> |
                    Monto: <?= $monto ?>
                </small>
            </label>
        </div>
    </li>
<?php endforeach; ?>
          </ul>
          <button type="submit" class="btn btn-success mt-3">
            <i class="fas fa-calendar-check"></i> Confirmar cita con OCs seleccionadas
          </button>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
