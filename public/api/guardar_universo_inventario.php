<?php
declare(strict_types=1);

/*
 * Planificación de Inventario
 * Orquestador del ciclo:
 * 1. Crear inventario (BORRADOR)
 * 2. Seleccionar universo BL (PLANEADO)
 * 3. Snapshot teórico (SNAPSHOT)
 */

require_once __DIR__ . '/../../../app/db.php';

$folio  = $_GET['folio'] ?? '';
$estado = '';
$tipo   = '';

if ($folio !== '') {
  $inv = db_row(
    "SELECT estado, tipo FROM th_inventario WHERE folio = ?",
    [$folio]
  );
  if ($inv) {
    $estado = $inv['estado'];
    $tipo   = $inv['tipo'];
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Planificación de Inventario</title>
<link href="/assets/bootstrap.min.css" rel="stylesheet">
<style>
.opacity-50 { opacity: .5; pointer-events: none; }
</style>
</head>

<body class="p-3">

<h4>Planificación de Inventario</h4>

<!-- =======================
 PASO 1 – CREAR INVENTARIO
======================== -->
<div class="card mb-3">
  <div class="card-header">
    <strong>1. Crear inventario</strong>
  </div>
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Tipo de inventario</label>
        <select id="tipoInventario" class="form-select">
          <option value="FISICO">Inventario Físico</option>
          <option value="INICIAL">Inventario Inicial</option>
          <option value="CICLICO">Inventario Cíclico</option>
        </select>
      </div>

      <div class="col-md-3">
        <button class="btn btn-primary"
                onclick="crearInventario()">
          Crear
        </button>
      </div>

      <?php if ($folio): ?>
        <div class="col-md-5 text-end">
          <span class="badge bg-secondary">
            Folio: <?= htmlspecialchars($folio) ?>
          </span>
          <span class="badge bg-info">
            Estado: <?= htmlspecialchars($estado) ?>
          </span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- =======================
 PASO 2 – SELECCIONAR UNIVERSO
======================== -->
<div class="card mb-3 <?= ($estado !== 'BORRADOR') ? 'opacity-50' : '' ?>">
  <div class="card-header">
    <strong>2. Seleccionar universo (BL)</strong>
  </div>
  <div class="card-body">
    <?php if ($folio): ?>
      <div id="universoBL" class="<?= ($estado !== 'BORRADOR') ? 'd-none' : '' ?>"></div>
      <?php if ($estado !== 'BORRADOR'): ?>
        <div class="text-muted">
          Universo ya asignado
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="text-muted">
        Disponible después de crear el inventario
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- =======================
 PASO 3 – SNAPSHOT
======================== -->
<div class="card mb-3 <?= ($estado !== 'PLANEADO') ? 'opacity-50' : '' ?>">
  <div class="card-header">
    <strong>3. Snapshot teórico</strong>
  </div>
  <div class="card-body">
    <button class="btn btn-warning"
            onclick="generarSnapshot()"
            <?= ($estado !== 'PLANEADO') ? 'disabled' : '' ?>>
      Generar snapshot
    </button>
  </div>
</div>

<script>
const folio  = '<?= $folio ?>';
const estado = '<?= $estado ?>';

/* =========================
   Crear inventario
========================= */
function crearInventario() {
  const tipo = document.getElementById('tipoInventario').value;

  fetch('/public/api/crear_inventario.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ tipo })
  })
  .then(r => r.json())
  .then(resp => {
    if (!resp.ok) {
      alert(resp.error);
      return;
    }
    window.location.href =
      'planificar_inventario.php?folio=' + resp.folio;
  });
}

/* =========================
   Cargar universo BL
========================= */
function cargarUniversoBL() {
  if (!folio || estado !== 'BORRADOR') return;

  fetch('seleccionar_universo.php?folio=' + folio)
    .then(r => r.text())
    .then(html => {
      document.getElementById('universoBL').innerHTML = html;
    });
}

/* =========================
   Snapshot
========================= */
function generarSnapshot() {
  if (!confirm('¿Generar snapshot teórico?')) return;

  fetch('/public/api/generar_snapshot_inventario.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ folio })
  })
  .then(r => r.json())
  .then(resp => {
    if (!resp.ok) {
      alert(resp.error);
      return;
    }
    alert('Snapshot generado correctamente');
    location.reload();
  });
}

/* =========================
   Init
========================= */
document.addEventListener('DOMContentLoaded', () => {
  cargarUniversoBL();
});
</script>

</body>
</html>
