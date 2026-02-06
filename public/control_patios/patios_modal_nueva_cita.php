<?php
// Modal: Nueva Cita (Planeación)
?>
<div class="modal fade" id="mdlNuevaCita" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">📅 Nueva cita</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="frmNuevaCita">

          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Empresa</label>
              <select class="form-select" name="empresa_id" required>
                <option value="">Seleccione</option>
                <?php
                $empresas = db_all("SELECT id, nombre FROM c_compania ORDER BY nombre");
                foreach ($empresas as $e) {
                  echo "<option value='{$e['id']}'>{$e['nombre']}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Almacén / Patio</label>
              <select class="form-select" name="almacenp_id" required>
                <option value="">Seleccione</option>
                <?php
                $alm = db_all("SELECT id, nombre FROM c_almacenp ORDER BY nombre");
                foreach ($alm as $a) {
                  echo "<option value='{$a['id']}'>{$a['nombre']}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipo de operación</label>
              <select class="form-select" name="tipo_operacion">
                <option value="RECEPCION">Recepción</option>
                <option value="EMBARQUE">Embarque</option>
                <option value="MIXTA">Mixta</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Ventana inicio</label>
              <input type="datetime-local" class="form-control"
                     name="ventana_inicio" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Ventana fin</label>
              <input type="datetime-local" class="form-control"
                     name="ventana_fin" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Prioridad</label>
              <select class="form-select" name="prioridad">
                <option value="1">Alta</option>
                <option value="2">Media</option>
                <option value="3" selected>Baja</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Proveedor</label>
              <input type="number" class="form-control"
                     name="id_proveedor"
                     placeholder="ID proveedor (opcional)">
            </div>

            <div class="col-md-4">
              <label class="form-label">Cliente</label>
              <input type="number" class="form-control"
                     name="id_cliente"
                     placeholder="ID cliente (opcional)">
            </div>

            <div class="col-md-6">
              <label class="form-label">Transporte</label>
              <select class="form-select" name="id_transporte">
                <option value="">(Opcional)</option>
                <?php
                $trs = db_all("
                  SELECT id, Nombre, Placas
                  FROM t_transporte
                  WHERE Activo = 1
                  ORDER BY Nombre
                ");
                foreach ($trs as $t) {
                  $lbl = trim($t['Nombre'].' '.$t['Placas']);
                  echo "<option value='{$t['id']}'>{$lbl}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Referencia documento</label>
              <input type="text" class="form-control"
                     name="referencia_doc"
                     placeholder="OC / ASN / Folio">
            </div>

            <div class="col-12">
              <label class="form-label">Comentarios</label>
              <textarea class="form-control"
                        name="comentarios"
                        rows="3"></textarea>
            </div>

          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary"
                data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"
                onclick="guardarNuevaCita()">Guardar</button>
      </div>

    </div>
  </div>
</div>

<script>
async function guardarNuevaCita(){
  const form = document.getElementById('frmNuevaCita');
  const fd = new FormData(form);

  const res = await fetch(
    'api/control_patios/api_patios_nueva_cita.php',
    { method:'POST', body: fd }
  );

  const json = await res.json();

  if(!json.ok){
    alert(json.error || 'Error al guardar cita');
    return;
  }

  bootstrap.Modal.getInstance(
    document.getElementById('mdlNuevaCita')
  ).hide();

  form.reset();
  cargarCitas();
}
</script>
