<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
include __DIR__ . '/../bi/_menu_global.php';

/* ===============================
   Validar inventario en BORRADOR
================================ */
$idInventario = intval($_GET['id'] ?? 0);

$inv = db_row("
    SELECT ID_Inventario, Status
    FROM th_inventario
    WHERE ID_Inventario = ?
", [$idInventario]);

if (!$inv || $inv['Status'] !== 'B') {
    echo '<div class="alert alert-danger m-3">Inventario no válido</div>';
    exit;
}
?>

<div class="container-fluid mt-3">

  <h4>📍 Seleccionar universo de BL (INVF-<?= htmlspecialchars((string)$idInventario) ?>)</h4>

  <!-- ===============================
       Filtros jerárquicos
  =============================== -->
  <div class="row mb-3">
    <div class="col-md-3">
      <label class="form-label">Empresa</label>
      <select id="empresa" class="form-select form-select-sm"></select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Almacén</label>
      <select id="almacen" class="form-select form-select-sm" disabled></select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Zona</label>
      <select id="zona" class="form-select form-select-sm" disabled></select>
    </div>
  </div>

  <!-- ===============================
       Grilla BL
  =============================== -->
  <div class="table-responsive" style="max-height:600px; overflow:auto;">
    <table class="table table-sm table-striped table-bordered" style="font-size:10px;">
      <thead class="table-light">
        <tr>
          <th></th>
          <th>BL</th>
          <th>Pasillo</th>
          <th>Rack</th>
          <th>Nivel</th>
          <th>Sección</th>
          <th>Posición</th>
        </tr>
      </thead>
      <tbody id="tabla-bls">
        <tr>
          <td colspan="7" class="text-center text-muted">
            Seleccione Empresa / Almacén / Zona
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    <button id="guardarUniverso" class="btn btn-success btn-sm">
      Guardar universo
    </button>
  </div>
</div>

<script>
const ID_INVENTARIO = <?= $idInventario ?>;

/* ===============================
   Cargar Empresas
================================ */
fetch('/assistpro_kardex_fc/public/api/filtros_empresas.php')
  .then(r => r.json())
  .then(data => {
    const sel = document.getElementById('empresa');
    sel.innerHTML = '<option value="">Seleccione empresa</option>';
    data.forEach(e => {
      sel.innerHTML += `<option value="${e.cve_empresa}">${e.empresa}</option>`;
    });
  });

/* ===============================
   Empresa → Almacenes
================================ */
document.getElementById('empresa').addEventListener('change', e => {
  const empresa = e.target.value;
  document.getElementById('almacen').disabled = true;
  document.getElementById('zona').disabled = true;
  document.getElementById('tabla-bls').innerHTML = '';

  if (!empresa) return;

  fetch(`/assistpro_kardex_fc/public/api/filtros_almacenes.php?empresa=${empresa}`)
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('almacen');
      sel.innerHTML = '<option value="">Seleccione almacén</option>';
      data.forEach(a => {
        sel.innerHTML += `<option value="${a.cve_almacen}">${a.almacen}</option>`;
      });
      sel.disabled = false;
    });
});

/* ===============================
   Almacén → Zonas
================================ */
document.getElementById('almacen').addEventListener('change', e => {
  const almacen = e.target.value;
  document.getElementById('zona').disabled = true;
  document.getElementById('tabla-bls').innerHTML = '';

  if (!almacen) return;

  fetch(`/assistpro_kardex_fc/public/api/filtros_zonas.php?almacen=${almacen}`)
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('zona');
      sel.innerHTML = '<option value="">Seleccione zona</option>';
      data.forEach(z => {
        sel.innerHTML += `<option value="${z.cve_zona}">${z.zona}</option>`;
      });
      sel.disabled = false;
    });
});

/* ===============================
   Zona → BLs
================================ */
document.getElementById('zona').addEventListener('change', e => {
  const zona = e.target.value;
  const tbody = document.getElementById('tabla-bls');
  tbody.innerHTML = '';

  if (!zona) return;

  fetch(`/assistpro_kardex_fc/public/api/bls_por_zona.php?zona=${zona}`)
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Sin BLs</td></tr>';
        return;
      }

      data.forEach(bl => {
        tbody.innerHTML += `
          <tr>
            <td><input type="checkbox" value="${bl.bl}"></td>
            <td>${bl.bl}</td>
            <td>${bl.pasillo || '-'}</td>
            <td>${bl.rack || '-'}</td>
            <td>${bl.nivel || '-'}</td>
            <td>${bl.seccion || '-'}</td>
            <td>${bl.posicion || '-'}</td>
          </tr>`;
      });
    });
});

/* ===============================
   Guardar universo
================================ */
document.getElementById('guardarUniverso').addEventListener('click', () => {
  const bls = Array.from(document.querySelectorAll('#tabla-bls input:checked'))
    .map(i => i.value);

  if (!bls.length) {
    alert('Seleccione al menos un BL');
    return;
  }

  fetch('/assistpro_kardex_fc/public/api/guardar_universo.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      inventario: ID_INVENTARIO,
      bls: bls
    })
  })
  .then(r => r.json())
  .then(resp => {
    if (resp.ok) {
      alert('Universo guardado correctamente');
      location.href = 'planificar_inventario.php';
    } else {
      alert(resp.error || 'Error al guardar');
    }
  });
});
</script>
