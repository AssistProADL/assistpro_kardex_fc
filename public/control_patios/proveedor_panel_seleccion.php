<?php
// proveedor_panel_seleccion1.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

/**
 * Helper escape seguro PHP 8+
 */
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function get_proveedores(): array {
    $url = "http://localhost/assistpro_kardex_fc/public/api/proveedores.php?action=list";
    $json = @file_get_contents($url);
    $data = json_decode($json, true);
    return isset($data['rows']) && is_array($data['rows']) ? $data['rows'] : [];
}

function get_ocs(string $almacen, string $proveedor): array {
    $url = "http://localhost/assistpro_kardex_fc/public/api/recepcion/recepcion_oc_api.php"
         . "?almacen=" . urlencode($almacen)
         . "&proveedor=" . urlencode($proveedor);

    $json = @file_get_contents($url);
    $data = json_decode($json, true);
    return ($data && isset($data['data']) && is_array($data['data'])) ? $data['data'] : [];
}

$almacen = 'WH8';
$proveedor_id = isset($_GET['proveedor']) ? trim((string)$_GET['proveedor']) : '';
$proveedores = get_proveedores();
$ocs_pendientes = [];

if ($proveedor_id !== '') {
    $ocs_pendientes = get_ocs($almacen, $proveedor_id);
}
?>

<style>
.proveedor-panel { padding:20px; font-size:14px; }
.oc-item { border:1px solid #ddd; border-radius:6px; margin-bottom:10px; }
.oc-header { display:flex; justify-content:space-between; align-items:center; }
.oc-detalle { background:#fafafa; padding:10px; border-top:1px dashed #ccc; }
</style>

<div class="proveedor-panel">
  <h4>👋 Panel de Proveedor: Simulación</h4>

  <form method="get" class="form-inline mb-4">
    <label class="mr-2">Selecciona un proveedor:</label>
    <select name="proveedor" class="form-control mr-2">
      <option value="">-- Elegir --</option>
      <?php foreach ($proveedores as $prov): ?>
        <?php
          if (!isset($prov['ID_Proveedor'])) continue;
          $clave  = $prov['cve_proveedor'] ?? 'SIN_CLAVE';
          $nombre = $prov['Nombre'] ?? 'SIN_NOMBRE';
        ?>
        <option value="<?= (int)$prov['ID_Proveedor'] ?>"
          <?= $proveedor_id === (string)$prov['ID_Proveedor'] ? 'selected' : '' ?>>
          <?= e("$clave - $nombre") ?>
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
<input type="hidden" name="proveedor_id" value="<?= e($proveedor_id) ?>">

<div class="card">
  <div class="card-header bg-primary text-white">
    Selecciona las Órdenes de Compra que entregarás
  </div>

  <div class="card-body">

<?php foreach ($ocs_pendientes as $oc): ?>
<?php
  // CAMPOS REALES DEL API
  $idOc  = (int)$oc['ID_Aduana'];              // INT → no escapar
  $folio = (string)($oc['Consec_protocolo'] ?? '');
  $fecha = (string)($oc['Fecha'] ?? '');
  $eta   = (string)($oc['ETA'] ?? '');
?>
<div class="oc-item">
  <div class="oc-header p-2">
    <div>
      <label>
        <input type="checkbox" name="ocs[]" value="<?= $idOc ?>">
        <strong>OC:</strong> <?= e($folio) ?>
      </label>
      <div class="text-muted small">
        Fecha OC: <?= e($fecha) ?> |
        ETA: <?= e($eta !== '' ? $eta : '-') ?>
      </div>
    </div>

    <button type="button"
      class="btn btn-sm btn-outline-primary"
      onclick="toggleDetalleOC(<?= $idOc ?>, this)">
      Ver detalle
    </button>
  </div>

  <div id="detalle-<?= $idOc ?>"
       class="oc-detalle"
       style="display:none"
       data-loaded="0"></div>
</div>
<?php endforeach; ?>

<button type="submit" class="btn btn-success mt-3">
  Confirmar cita con OCs seleccionadas
</button>

  </div>
</div>
</form>
<?php endif; ?>
</div>

<script>
async function toggleDetalleOC(idOc, btn){
  const cont = document.getElementById('detalle-' + idOc);
  if (!cont) return;

  if (cont.style.display === 'block') {
    cont.style.display = 'none';
    btn.innerText = 'Ver detalle';
    return;
  }

  cont.style.display = 'block';
  btn.innerText = 'Ocultar detalle';

  if (cont.dataset.loaded === '1') return;

  cont.innerHTML = '<div class="text-muted">Cargando detalle...</div>';

  try {
    const res = await fetch(
      '/assistpro_kardex_fc/public/api/recepcion/recepcion_oc_detalle_api.php?id_oc=' + idOc
    );
    const json = await res.json();

    if (!json.ok) throw new Error('Error API');

    cont.innerHTML = renderDetalleOC(json.data);
    cont.dataset.loaded = '1';

  } catch (e) {
    console.error(e);
    cont.innerHTML = '<div class="alert alert-danger">Error cargando detalle</div>';
  }
}

function renderDetalleOC(rows){
  if (!rows || rows.length === 0) {
    return '<div class="text-muted">Sin partidas</div>';
  }

  let html = `
    <table class="table table-sm table-bordered">
      <thead class="table-light">
        <tr>
          <th>Artículo</th>
          <th>Descripción</th>
          <th class="text-end">Cantidad</th>
          <th class="text-end">Ingresado</th>
          <th class="text-end">Pendiente</th>
          <th>UM</th>
        </tr>
      </thead>
      <tbody>
  `;

  rows.forEach(r => {
    html += `
      <tr>
        <td>${r.cve_articulo}</td>
        <td>${r.des_articulo ?? ''}</td>
        <td class="text-end">${r.cantidad}</td>
        <td class="text-end">${r.ingresado}</td>
        <td class="text-end"><strong>${r.pendiente}</strong></td>
        <td>${r.umed_base_nombre ?? ''}</td>
      </tr>
    `;
  });

  html += '</tbody></table>';
  return html;
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
