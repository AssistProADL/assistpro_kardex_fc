<?php
// public/control_patios/patios_admin.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
.badge-prio-1{ background:#dc3545; }
.badge-prio-2{ background:#ffc107; color:#000; }
.badge-prio-3{ background:#6c757d; }
</style>

<div class="container-fluid">

  <!-- ================= HEADER ================= -->
  <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <h4 class="mb-0">📅 Planeación de Citas</h4>
    <button class="btn btn-primary"
      data-bs-toggle="modal"
      data-bs-target="#mdlNuevaCita">
      + Nueva cita
    </button>
  </div>

  <!-- ================= TABLERO DE CITAS ================= -->
  <div class="card">
    <div class="card-header">
      Citas registradas
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="tblCitas">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Ventana</th>
              <th>Tipo</th>
              <th>Tercero</th>
              <th>Transporte</th>
              <th>Prioridad</th>
              <th>Estatus</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                Cargando citas...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php
// ================= MODAL NUEVA CITA =================
// (usa el modal que ya te entregué; aquí solo lo incluimos)
require_once __DIR__ . '/patios_modal_nueva_cita.php';
?>

<script>
// ================= CARGAR CITAS =================
async function cargarCitas(){
  const tbody = document.querySelector('#tblCitas tbody');

  tbody.innerHTML = `
    <tr>
      <td colspan="8" class="text-center text-muted py-4">
        Cargando citas...
      </td>
    </tr>
  `;

  const res = await fetch(
    'api/control_patios/api_patios_listar_citas.php?limit=200'
  );
  const json = await res.json();

  if(!json.ok){
    tbody.innerHTML = `
      <tr>
        <td colspan="8" class="text-danger text-center">
          Error al cargar citas
        </td>
      </tr>`;
    return;
  }

  if(!json.data.length){
    tbody.innerHTML = `
      <tr>
        <td colspan="8" class="text-center text-muted">
          No hay citas registradas
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = '';

  json.data.forEach(c => {
    const ventana = `
      <div>${c.ventana_inicio}</div>
      <small class="text-muted">${c.ventana_fin}</small>
    `;

    const tercero = c.id_proveedor
      ? 'Proveedor #' + c.id_proveedor
      : (c.id_cliente ? 'Cliente #' + c.id_cliente : '-');

    const prioBadge = `
      <span class="badge badge-prio-${c.prioridad}">
        ${c.prioridad}
      </span>
    `;

    const acciones = accionesCita(c);

    tbody.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${c.id_cita}</td>
        <td>${ventana}</td>
        <td>${c.tipo_operacion}</td>
        <td>${tercero}</td>
        <td>${c.id_transporte || '-'}</td>
        <td>${prioBadge}</td>
        <td>
          <span class="badge bg-secondary">
            ${c.estatus}
          </span>
        </td>
        <td class="text-end">${acciones}</td>
      </tr>
    `);
  });
}

// ================= ACCIONES =================
function accionesCita(c){
  if(c.estatus === 'PROGRAMADA'){
    return `
      <button class="btn btn-sm btn-success"
        onclick="confirmarCita(${c.id_cita})">
        Confirmar
      </button>
      <button class="btn btn-sm btn-outline-danger ms-1"
        onclick="cancelarCita(${c.id_cita})">
        Cancelar
      </button>
    `;
  }

  if(c.estatus === 'CONFIRMADA' && c.id_visita){
    return `
      <a class="btn btn-sm btn-primary"
        href="patios_dashboard_visita.php?id_visita=${c.id_visita}">
        Abrir visita
      </a>
    `;
  }

  return '-';
}

// ================= CONFIRMAR =================
async function confirmarCita(idCita){
  if(!confirm('¿Confirmar esta cita y crear la visita?')) return;

  const fd = new FormData();
  fd.append('id_cita', idCita);
  fd.append('crear_visita', '1');

  const res = await fetch(
    'api/control_patios/api_patios_confirmar_cita.php',
    { method:'POST', body: fd }
  );
  const json = await res.json();

  if(!json.ok){
    alert(json.error || 'Error al confirmar');
    return;
  }

  if(json.id_visita){
    window.location.href =
      'patios_dashboard_visita.php?id_visita=' + json.id_visita;
  } else {
    cargarCitas();
  }
}

// ================= CANCELAR (placeholder) =================
function cancelarCita(idCita){
  alert('Cancelar cita: API pendiente de implementar');
}

document.addEventListener('DOMContentLoaded', cargarCitas);
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
